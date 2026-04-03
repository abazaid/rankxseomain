<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\SaaSRepository;
use App\Repositories\StoreRepository;
use App\Services\DataForSeoClient;
use App\Services\OpenAICostCalculator;
use App\Services\OpenAIClient;
use App\Services\SubscriptionManager;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;

final class StandaloneController
{
    public function subscription(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $manager = new SubscriptionManager();
        $store = $manager->refreshPeriodIfNeeded($store);

        Response::json([
            'success' => true,
            'merchant_id' => $store['merchant_id'] ?? null,
            'subscription' => $manager->summary($store),
        ]);
    }

    public function settings(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        Response::json([
            'success' => true,
            'settings' => $this->normalizeSettings((array) ($store['settings'] ?? [])),
        ]);
    }

    public function saveSettings(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = Request::input();
        $current = (array) ($store['settings'] ?? []);
        $merged = array_merge($current, is_array($input) ? $input : []);
        $settings = $this->normalizeSettings($merged);

        $this->saveStore((string) ($store['merchant_id'] ?? ''), [
            'settings' => $settings,
        ]);

        Response::json([
            'success' => true,
            'message' => 'Settings saved.',
            'settings' => $settings,
        ]);
    }

    public function listItems(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $type = $this->normalizeItemType((string) Request::query('type', 'product'));
        $items = (array) (($store['standalone_items'][$type] ?? []));

        Response::json([
            'success' => true,
            'type' => $type,
            'items' => array_values(array_reverse($items)),
        ]);
    }

    public function generateItem(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = Request::input();
        $type = $this->normalizeItemType((string) ($input['type'] ?? 'product'));
        $keyword = trim((string) ($input['keyword'] ?? ''));
        $context = trim((string) ($input['context'] ?? ''));

        if ($keyword === '') {
            Response::json(['success' => false, 'message' => 'Keyword is required.'], 422);
            return;
        }

        $quotaByType = [
            'product' => 'product_description',
            'brand' => 'brand_seo',
            'category' => 'category_seo',
        ];
        $modeByType = [
            'product' => 'description',
            'brand' => 'brand_seo',
            'category' => 'category_seo',
        ];

        $manager = new SubscriptionManager();
        $store = $manager->refreshPeriodIfNeeded($store);
        $quotaType = $quotaByType[$type];
        if (!$manager->canOptimize($store, $quotaType)) {
            Response::json([
                'success' => false,
                'message' => 'Quota exceeded for this feature.',
                'subscription' => $manager->summary($store),
            ], 402);
            return;
        }

        $settings = $this->normalizeSettings((array) ($store['settings'] ?? []));
        $generated = [];
        $usage = [];
        $model = '';

        try {
            $openAI = new OpenAIClient();

            if ($type === 'product') {
                $payload = [
                    'name' => $keyword,
                    'description' => $context,
                    'metadata' => [
                        'title' => '',
                        'description' => '',
                    ],
                ];
                $result = $openAI->generateProductContent($payload, $settings, 'all');
                $generated = [
                    'title' => $keyword,
                    'description' => (string) ($result['description'] ?? ''),
                    'meta_title' => (string) ($result['metadata_title'] ?? ''),
                    'meta_description' => (string) ($result['metadata_description'] ?? ''),
                ];
                $usage = is_array($result['_usage'] ?? null) ? $result['_usage'] : [];
                $model = (string) ($result['_model'] ?? '');
            } elseif ($type === 'brand') {
                $payload = [
                    'name' => $keyword,
                    'description' => $context,
                ];
                $result = $openAI->generateBrandSeo($payload, $settings);
                $generated = [
                    'title' => $keyword,
                    'description' => $context,
                    'meta_title' => (string) ($result['meta_title'] ?? ''),
                    'meta_description' => (string) ($result['meta_description'] ?? ''),
                ];
                $usage = is_array($result['_usage'] ?? null) ? $result['_usage'] : [];
                $model = (string) ($result['_model'] ?? '');
            } else {
                $payload = [
                    'name' => $keyword,
                    'description' => $context,
                ];
                $result = $openAI->generateCategorySeo($payload, $settings);
                $generated = [
                    'title' => $keyword,
                    'description' => $context,
                    'meta_title' => (string) ($result['meta_title'] ?? ''),
                    'meta_description' => (string) ($result['meta_description'] ?? ''),
                ];
                $usage = is_array($result['_usage'] ?? null) ? $result['_usage'] : [];
                $model = (string) ($result['_model'] ?? '');
            }
        } catch (\Throwable $exception) {
            Response::json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
            return;
        }

        $itemsRoot = is_array($store['standalone_items'] ?? null) ? (array) $store['standalone_items'] : [];
        $items = is_array($itemsRoot[$type] ?? null) ? array_values((array) $itemsRoot[$type]) : [];
        $newId = $this->nextItemId($items);

        $row = [
            'id' => $newId,
            'type' => $type,
            'keyword' => $keyword,
            'context' => $context,
            'title' => $generated['title'],
            'description' => $generated['description'],
            'meta_title' => $generated['meta_title'],
            'meta_description' => $generated['meta_description'],
            'created_at' => date(DATE_ATOM),
        ];
        $items[] = $row;
        $itemsRoot[$type] = array_slice($items, -300);

        $store = $manager->recordOptimization($store, $newId, $keyword, $modeByType[$type], 'completed');
        $this->saveStore((string) ($store['merchant_id'] ?? ''), [
            'standalone_items' => $itemsRoot,
        ]);

        $dbStore = $this->resolveDbStore();
        if ($dbStore && $model !== '' && $usage !== []) {
            $cost = (new OpenAICostCalculator())->calculate($usage);
            (new SaaSRepository())->logAiUsage((int) $dbStore['id'], $newId, $model, $cost, $modeByType[$type]);
        }

        Response::json([
            'success' => true,
            'item' => $row,
            'subscription' => $manager->summary($store),
        ]);
    }

    public function keywordResearch(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = Request::input();
        $keyword = trim((string) ($input['keyword'] ?? ''));
        $country = strtolower(trim((string) ($input['country'] ?? 'sa')));
        $device = strtolower(trim((string) ($input['device'] ?? 'desktop')));

        if ($keyword === '') {
            Response::json(['success' => false, 'message' => 'Keyword is required.'], 422);
            return;
        }

        $manager = new SubscriptionManager();
        $store = $manager->refreshPeriodIfNeeded($store);
        if (!$manager->canOptimize($store, 'keyword_research')) {
            Response::json([
                'success' => false,
                'message' => 'Keyword quota exceeded.',
                'subscription' => $manager->summary($store),
            ], 402);
            return;
        }

        $settings = $this->normalizeSettings((array) ($store['settings'] ?? []));
        $language = (string) ($settings['output_language'] ?? 'ar');

        try {
            $result = (new DataForSeoClient())->keywordOverview($keyword, $device, $country, $language);
        } catch (\Throwable $exception) {
            Response::json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
            return;
        }

        $history = is_array($store['settings']['keyword_history'] ?? null) ? (array) $store['settings']['keyword_history'] : [];
        $history[] = [
            'keyword' => $keyword,
            'country' => $country,
            'device' => $device,
            'created_at' => date(DATE_ATOM),
            'result' => $result,
        ];
        $settings['keyword_history'] = array_slice($history, -100);

        $store = $manager->recordOptimization($store, 0, $keyword, 'keyword_research', 'completed');
        $this->saveStore((string) ($store['merchant_id'] ?? ''), [
            'settings' => $settings,
        ]);

        $dbStore = $this->resolveDbStore();
        if ($dbStore && is_array($result['_usage'] ?? null)) {
            (new SaaSRepository())->logDataForSeoUsage(
                (int) $dbStore['id'],
                $keyword,
                'keyword_research',
                (array) $result['_usage']
            );
        }

        Response::json([
            'success' => true,
            'result' => $result,
            'subscription' => $manager->summary($store),
        ]);
    }

    public function keywordHistory(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $history = is_array($store['settings']['keyword_history'] ?? null) ? (array) $store['settings']['keyword_history'] : [];
        Response::json([
            'success' => true,
            'history' => array_values(array_reverse($history)),
        ]);
    }

    public function domainSeo(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $domainSeo = is_array($store['settings']['domain_seo'] ?? null) ? (array) $store['settings']['domain_seo'] : [
            'domain' => '',
            'country' => 'sa',
            'device' => 'desktop',
            'saved_at' => '',
            'refreshed_at' => '',
            'refresh_count' => 0,
            'last_data' => null,
        ];

        Response::json([
            'success' => true,
            'domain_seo' => $domainSeo,
        ]);
    }

    public function saveDomainSeo(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $input = Request::input();
        $domain = trim((string) ($input['domain'] ?? ''));
        $country = strtolower(trim((string) ($input['country'] ?? 'sa')));
        $device = strtolower(trim((string) ($input['device'] ?? 'desktop'))) === 'mobile' ? 'mobile' : 'desktop';

        $settings = is_array($store['settings'] ?? null) ? (array) $store['settings'] : [];
        $existing = is_array($settings['domain_seo'] ?? null) ? (array) $settings['domain_seo'] : [];

        $settings['domain_seo'] = array_merge($existing, [
            'domain' => $domain,
            'country' => $country !== '' ? $country : 'sa',
            'device' => $device,
            'saved_at' => date(DATE_ATOM),
        ]);

        $this->saveStore((string) ($store['merchant_id'] ?? ''), [
            'settings' => $settings,
        ]);

        Response::json([
            'success' => true,
            'domain_seo' => $settings['domain_seo'],
        ]);
    }

    public function refreshDomainSeo(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $manager = new SubscriptionManager();
        $store = $manager->refreshPeriodIfNeeded($store);
        if (!$manager->canOptimize($store, 'domain_seo')) {
            Response::json([
                'success' => false,
                'message' => 'Domain SEO quota exceeded.',
                'subscription' => $manager->summary($store),
            ], 402);
            return;
        }

        $input = Request::input();
        $settings = is_array($store['settings'] ?? null) ? (array) $store['settings'] : [];
        $existing = is_array($settings['domain_seo'] ?? null) ? (array) $settings['domain_seo'] : [];

        $domain = trim((string) ($input['domain'] ?? ($existing['domain'] ?? '')));
        $device = strtolower(trim((string) ($input['device'] ?? ($existing['device'] ?? 'desktop')))) === 'mobile' ? 'mobile' : 'desktop';

        if ($domain === '') {
            Response::json(['success' => false, 'message' => 'Domain is required.'], 422);
            return;
        }

        try {
            $result = (new DataForSeoClient())->domainOverview($domain, $device);
        } catch (\Throwable $exception) {
            Response::json(['success' => false, 'message' => $exception->getMessage()], 500);
            return;
        }

        $settings['domain_seo'] = array_merge($existing, [
            'domain' => $domain,
            'country' => 'sa',
            'device' => $device,
            'refreshed_at' => date(DATE_ATOM),
            'refresh_count' => (int) ($existing['refresh_count'] ?? 0) + 1,
            'last_data' => $result,
        ]);

        $history = is_array($settings['domain_seo_history'] ?? null) ? (array) $settings['domain_seo_history'] : [];
        $history[] = [
            'domain' => $domain,
            'device' => $device,
            'created_at' => date(DATE_ATOM),
            'result' => $result,
        ];
        $settings['domain_seo_history'] = array_slice($history, -100);

        $store = $manager->recordOptimization($store, 0, $domain, 'domain_seo', 'completed');
        $this->saveStore((string) ($store['merchant_id'] ?? ''), [
            'settings' => $settings,
        ]);

        $dbStore = $this->resolveDbStore();
        if ($dbStore && is_array($result['_usage'] ?? null)) {
            (new SaaSRepository())->logDataForSeoUsage((int) $dbStore['id'], $domain, 'domain_seo', (array) $result['_usage']);
        }

        Response::json([
            'success' => true,
            'domain_seo' => $settings['domain_seo'],
            'subscription' => $manager->summary($store),
        ]);
    }

    public function domainSeoHistory(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $history = is_array($store['settings']['domain_seo_history'] ?? null) ? (array) $store['settings']['domain_seo_history'] : [];
        Response::json([
            'success' => true,
            'history' => array_values(array_reverse($history)),
        ]);
    }

    public function operations(): void
    {
        $store = $this->resolveStore();
        if ($store === null) {
            Response::json(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        $logs = array_reverse((array) ($store['usage_logs'] ?? []));

        Response::json([
            'success' => true,
            'operations' => array_slice($logs, 0, 100),
        ]);
    }

    private function resolveStore(): ?array
    {
        $sessionUserId = (int) ($_SESSION['user_id'] ?? 0);
        $sessionStoreId = (int) ($_SESSION['store_id'] ?? 0);

        if ($sessionUserId <= 0 || $sessionStoreId <= 0 || !Database::isAvailable()) {
            return null;
        }

        $dbStore = (new SaaSRepository())->findStoreById($sessionStoreId);
        $merchantId = (string) ($dbStore['merchant_id'] ?? '');
        if ($merchantId === '') {
            return null;
        }

        $stores = (new StoreRepository())->all();
        return is_array($stores[$merchantId] ?? null) ? (array) $stores[$merchantId] : null;
    }

    private function resolveDbStore(): ?array
    {
        $sessionStoreId = (int) ($_SESSION['store_id'] ?? 0);
        if ($sessionStoreId <= 0 || !Database::isAvailable()) {
            return null;
        }
        return (new SaaSRepository())->findStoreById($sessionStoreId);
    }

    private function normalizeItemType(string $type): string
    {
        $value = strtolower(trim($type));
        if (!in_array($value, ['product', 'brand', 'category'], true)) {
            return 'product';
        }
        return $value;
    }

    private function nextItemId(array $items): int
    {
        $max = 0;
        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > $max) {
                $max = $id;
            }
        }
        return $max + 1;
    }

    private function saveStore(string $merchantId, array $data): void
    {
        if ($merchantId === '') {
            return;
        }
        (new StoreRepository())->save($merchantId, $data);
    }

    private function normalizeSettings(array $settings): array
    {
        $defaults = $this->defaultSettings();
        $language = strtolower(trim((string) ($settings['output_language'] ?? $defaults['output_language'])));
        if (!in_array($language, ['ar', 'en'], true)) {
            $language = 'ar';
        }

        return [
            'output_language' => $language,
            'global_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'global_instructions', (string) $defaults['global_instructions']), 5000),
            'product_description_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'product_description_instructions', (string) $defaults['product_description_instructions']), 5000),
            'meta_title_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'meta_title_instructions', (string) $defaults['meta_title_instructions']), 3000),
            'meta_description_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'meta_description_instructions', (string) $defaults['meta_description_instructions']), 3000),
            'store_seo_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'store_seo_instructions', (string) $defaults['store_seo_instructions']), 5000),
            'brand_seo_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'brand_seo_instructions', (string) $defaults['brand_seo_instructions']), 3000),
            'category_seo_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'category_seo_instructions', (string) $defaults['category_seo_instructions']), 3000),
            'blog_seo_instructions' => $this->normalizeText($this->pickInstructionWithDefault($settings, 'blog_seo_instructions', (string) $defaults['blog_seo_instructions']), 5000),
            'business_brand_name' => $this->normalizeText((string) ($settings['business_brand_name'] ?? ''), 160),
            'business_overview' => $this->normalizeText((string) ($settings['business_overview'] ?? ''), 1500),
            'keyword_history' => is_array($settings['keyword_history'] ?? null) ? array_slice((array) $settings['keyword_history'], -100) : [],
            'domain_seo' => is_array($settings['domain_seo'] ?? null) ? (array) $settings['domain_seo'] : [
                'domain' => '',
                'country' => 'sa',
                'device' => 'desktop',
                'saved_at' => '',
                'refreshed_at' => '',
                'refresh_count' => 0,
                'last_data' => null,
            ],
            'domain_seo_history' => is_array($settings['domain_seo_history'] ?? null) ? array_slice((array) $settings['domain_seo_history'], -100) : [],
        ];
    }

    private function defaultSettings(): array
    {
        return [
            'output_language' => 'ar',
            'global_instructions' => "اكتب محتوى عربي احترافي موجه للعميل السعودي.
ركّز على مساعدة العميل في اتخاذ قرار الشراء.
اجعل النص:
- واضح
- سهل القراءة
- عملي (يفيد العميل فعليًا)

القواعد:
- لا تنسخ من المنافسين
- لا تخترع معلومات أو مواصفات
- استخدم اسم المنتج + البراند بشكل طبيعي
- ركّز على الفوائد (مو الوصف فقط)
- تجنب الحشو والكلمات الفارغة
- لا تذكر مواقع أو منافسين
- لا تضع روابط خارجية (فقط روابط داخلية)

الهدف:
- رفع التحويل (Conversion)
- تحسين SEO",
            'product_description_instructions' => "🧩 أهم نقطة: تحديد نوع المنتج

قبل كتابة أي وصف لازم تحدد نوع المنتج:
• ملابس (رجالي / نسائي)
• أحذية
• إكسسوارات
• إلكترونيات
• أدوات منزلية

🧠 قواعد حسب نوع المنتج (عام)

إذا المنتج ملابس:
ركّز على:
- الخامة
- المقاس
- الراحة
- الاستخدام (يومي / رسمي)

إذا المنتج إلكتروني:
ركّز على:
- الأداء
- المواصفات
- الاستخدام العملي

إذا المنتج تجميلي:
ركّز على:
- النتائج
- المكونات
- الأمان

القاعدة الذهبية:
👉 كل نوع له زاوية بيع مختلفة — لا تكتب وصف عام

🧾 وصف المنتج (الزبدة العملية)

الهدف:
- محتوى مقنع + SEO
- يساعد العميل يشتري

الطول:
800 – 1200 كلمة (أو أقل بدون حشو)

🔗 الربط الداخلي (الزبدة)
استخدم 2–3 روابط فقط من نفس المتجر مرتبطة مباشرة بالمنتج
مثال (ملابس):
- رابط فئة (فساتين)
- رابط براند
- رابط منتج مشابه

🧱 هيكل الوصف (مهم جدًا)

1. مقدمة (بدون عنوان)
   - تعريف بالمنتج
   - اسم المنتج
   - البراند
   - أهم ميزة

2. H2: نظرة عامة على المنتج
   - الشركة
   - الفئة
   - الاستخدام

3. H2: أهم المميزات
   - نقاط Bullet فقط

4. H2: المواصفات
   - فقط معلومات مؤكدة

5. H2: التصميم وجودة التصنيع
   - الشكل
   - الخامة
   - الراحة

6. H2: الأداء وتجربة الاستخدام
   (حسب نوع المنتج)
   مثال ملابس:
   - الراحة
   - الحركة
   - الاستخدام اليومي

7. H2: تقييمنا للمنتج
   - رأي واقعي بدون مبالغة

8. H2: طريقة الاستخدام
   - كيف يستخدم المنتج

9. H2: مقارنة مع منتجات مشابهة
   - فرق حقيقي فقط

10. H2: لماذا يختار العملاء هذا المنتج
    - نقاط إقناع

11. H2: لمن يناسب هذا المنتج
    - تحديد الجمهور

12. H2: لماذا تشتري من متجرنا
    - سرعة الشحن
    - جودة
    - ضمان

13. H2: منتجات قد تهمك
    - روابط داخلية فقط

14. H2: الأسئلة الشائعة
    - 5–7 أسئلة حقيقية

⚠️ أهم الأخطاء (لازم تتجنبها)

❌ كتابة وصف عام يصلح لأي منتج
❌ اختراع مواصفات
❌ تكرار الكلمات المفتاحية
❌ حشو بدون فائدة
❌ نسخ من المنافسين",
            'meta_title_instructions' => "🏷️ Meta Title
المطلوب:
- 50-60 حرف
- يبدأ باسم المنتج

الصيغة: اسم المنتج + الفئة + ميزة قوية

مثال (ملابس):
فستان سهرة ساتان نسائي تصميم أنيق وقصة مريحة

مثال (إلكترونيات):
سماعة بلوتوث لاسلكية بجودة صوت عالية وعمر بطارية طويل

تجنب:
- التكرار
- الكلمات المبالغ فيها
- الحشو",
            'meta_description_instructions' => "📝 Meta Description
المطلوب:
- 140-155 حرف
- يحتوي اسم المنتج
- يحفّز على الشراء

الصيغة: اشتري + المنتج + ميزة + فائدة + عنصر ثقة

مثال (ملابس):
اشتري فستان سهرة ساتان نسائي بتصميم أنيق وخامة ناعمة مريحة. مثالي للمناسبات ويوفر لك إطلالة راقية بجودة عالية.

مثال (إلكترونيات):
اشتري سماعة بلوتوث لاسلكية بصوت واضح ونقي مع عزل ضوضاء متقدم. بطارية تدوم 24 ساعة وشحن سريع عبر USB-C.

تجنب:
- التكرار
- الكلمات المبالغ فيها
- الحشو",
            'image_alt_instructions' => "🖼️ ALT للصور - القاعدة الذهبية:
\"كل نوع له زاوية بيع مختلفة\"

أمثلة حسب نوع المنتج:
• ملابس: \"صورة فستان سهرة نسائي ساتان أرجواني، تصميم سهرة أنيق\"
• إلكترونيات: \"سماعة بلوتوث لاسلكية بيضاء مع علبة شحن\"
• تجميلي: \"عبوة كريم مرطب للوجه 50ml بتركيبة فيتامين E\"

القواعد:
- دقيق: يصف الصورة بشكل صحيح
- طبيعي: يبدو كجملة عادية
- واضح: يفهم منه محتوى الصورة
- يتضمن اسم المنتج عند الإمكان
- 70-125 حرف تقريبًا",
            'store_seo_instructions' => $this->getDefaultStoreSeoInstructions(),
            'brand_seo_instructions' => $this->getDefaultBrandSeoInstructions(),
            'category_seo_instructions' => $this->getDefaultCategorySeoInstructions(),
            'blog_seo_instructions' => $this->getDefaultBlogSeoInstructions(),
            'business_brand_name' => '',
            'business_overview' => '',
            'sitemap_url' => '',
            'sitemap_links_count' => 0,
            'sitemap_last_fetched_at' => '',
        ];
    }

    private function pickInstructionWithDefault(array $settings, string $key, string $default): string
    {
        $value = trim((string) ($settings[$key] ?? ''));
        return $value !== '' ? $value : $default;
    }

    private function getDefaultStoreSeoInstructions(): string
    {
        return <<<'TEXT'
تعليمات سيو المتجر 
🎯 الهدف:
إنشاء محتوى الصفحة الرئيسية لمتجر إلكتروني بطريقة محسنة لمحركات البحث وتزيد معدل التحويل.

🧠 آلية العمل:
اقرأ بيانات المتجر (اسم، فئات، منتجات)، واستنتج:
- النشاط
- الفئة المستهدفة
- الكلمات المفتاحية الأساسية

━━━━━━━━━━━━━━━

✍️ القواعد:

✔️ اجمع بين SEO + التسويق
✔️ ركز على الفائدة
✔️ استخدم كلمات طبيعية

🚫 تجنب:
- الحشو
- الكلام العام
- تكرار نفس الجمل

━━━━━━━━━━━━━━━

🧱 المطلوب:

1️⃣ Meta Title
- يحتوي:
اسم المتجر + النشاط + ميزة

2️⃣ Meta Description
- وصف المتجر + ميزة + CTA

3️⃣ Hero Title
- جملة قوية تحتوي الكلمة المفتاحية

4️⃣ Hero Description
- ماذا يقدم المتجر + لماذا تختاره

5️⃣ Sections:
- الفئات
- لماذا نحن
- المنتجات المميزة

6️⃣ SEO Paragraph (150–300 كلمة)
- كلمات أساسية + طويلة + محلية

7️⃣ CTA

━━━━━━━━━━━━━━━

📤 المخرجات:

Meta Title:
...

Meta Description:
...

Hero Title:
...

Hero Description:
...

Sections:
...

SEO Paragraph:
...

CTA:
...
TEXT;
    }

    private function getDefaultBrandSeoInstructions(): string
    {
        return <<<'TEXT'
تعليمات سيو الماركات 
🎯 الهدف:
إنشاء صفحة SEO لبراند داخل متجر إلكتروني بهدف السيطرة على نتائج البحث الخاصة بالبراند.

🧠 آلية العمل:
اقرأ بيانات البراند والمنتجات المرتبطة به واستنتج:
- نوع المنتجات
- شهرة البراند
- نية البحث (شراء / معلومات)

━━━━━━━━━━━━━━━

✍️ القواعد:

✔️ ركز على اسم البراند + نوع المنتجات
✔️ اجعل الصفحة مرجع للبراند
✔️ اكتب محتوى مفيد وليس تسويقي فقط

🚫 تجنب:
- معلومات غير مؤكدة
- مبالغة
- حشو

━━━━━━━━━━━━━━━

🧱 المطلوب:

1️⃣ Meta Title
- اسم البراند + نوع المنتجات + ميزة

2️⃣ Meta Description
- تعريف + فائدة + CTA

3️⃣ H1
- اسم البراند + الفئة

4️⃣ وصف البراند (300–600 كلمة)
يشمل:
- تعريف بالبراند
- نوع المنتجات
- لماذا مميز
- الفئة المستهدفة

5️⃣ لماذا تختار هذا البراند
- نقاط واضحة

6️⃣ منتجات البراند
- وصف يدعم SEO

7️⃣ FAQ (3–5 أسئلة)

━━━━━━━━━━━━━━━

📤 المخرجات:

Meta Title:
...

Meta Description:
...

H1:
...

Brand Description:
...

Why This Brand:
...

FAQ:
...
TEXT;
    }

    private function getDefaultCategorySeoInstructions(): string
    {
        return <<<'TEXT'
تعليمات سيو الأقسام 
🎯 الهدف:
إنشاء محتوى SEO احترافي لصفحات الأقسام في متجر إلكتروني يركز على رفع الترتيب في Google وزيادة المبيعات.

🧠 آلية العمل:
اقرأ بيانات المتجر (الفئات، المنتجات، الأسماء، الوصف) واستنتج:
- نوع القسم
- الكلمات المفتاحية الأساسية
- نية البحث (شراء / تصفح / مقارنة)

━━━━━━━━━━━━━━━

✍️ القواعد:

✔️ ركز على الكلمة المفتاحية الرئيسية للقسم
✔️ استخدم كلمات طويلة (Long-tail)
✔️ اكتب محتوى يخدم قرار الشراء
✔️ النص يكون طبيعي بدون حشو

🚫 تجنب:
- تكرار اسم المتجر
- كتابة محتوى عام
- حشو كلمات مفتاحية
- نسخ محتوى

━━━━━━━━━━━━━━━

🧱 المطلوب:

1️⃣ Meta Title
- 50–60 حرف
- الصيغة:
اسم القسم + ميزة + موقع (اختياري) + | اسم المتجر (اختياري)

2️⃣ Meta Description
- 140–155 حرف
- يحتوي:
الكلمة + فائدة + CTA

3️⃣ H1
- يحتوي الكلمة المفتاحية فقط (بدون اسم المتجر)

4️⃣ وصف القسم (200–400 كلمة)
يجب أن يحتوي:
- تعريف واضح
- أنواع المنتجات
- كيف يختار العميل
- استخدام الكلمات المفتاحية بشكل طبيعي

5️⃣ FAQ (3–5 أسئلة)
- أسئلة حقيقية من نية البحث

━━━━━━━━━━━━━━━

📤 المخرجات:

Meta Title:
...

Meta Description:
...

H1:
...

Description:
...

FAQ:
...
TEXT;
    }

    private function getDefaultBlogSeoInstructions(): string
    {
        return <<<'TEXT'
تعليمات كتابة مقالات المدونة (قريبًا)
🎯 الهدف:
إنشاء مقالات متوافقة مع تحسين محركات البحث وجاهزة للنشر في مدونة سلة.

🧠 آلية العمل:
- اختيار كلمة مفتاحية رئيسية للمقال
- استخراج كلمات طويلة داعمة (Long-tail)
- بناء هيكل H1 / H2 / H3 واضح
- إضافة ربط داخلي مع المنتجات والأقسام ذات الصلة

✍️ القواعد:
✔️ محتوى مفيد وعملي للقارئ
✔️ لغة طبيعية بدون حشو
✔️ مراعاة نية البحث
✔️ خاتمة مع CTA واضح
TEXT;
    }


    private function normalizeText(string $value, int $maxLen): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_substr($trimmed, 0, $maxLen, 'UTF-8');
        }
        return substr($trimmed, 0, $maxLen);
    }
}

