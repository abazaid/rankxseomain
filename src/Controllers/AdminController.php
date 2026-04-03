<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Config;
use App\Repositories\SaaSRepository;
use App\Repositories\StoreRepository;
use App\Support\Database;
use App\Support\Response;
use App\Support\View;
use App\Support\Plans;

final class AdminController
{
    public function loginForm(): void
    {
        Response::html(View::render('Admin Login', <<<HTML
<div class="card">
  <h1>لوحة الأدمن</h1>
  <p class="muted">تسجيل دخول مدير النظام.</p>
  <form method="post" action="/admin/login">
    <label><strong>البريد الإلكتروني</strong></label>
    <input name="email" type="email" style="width:100%;padding:12px;margin:8px 0 16px;border-radius:12px;border:1px solid #E2E8F0;" required>
    <label><strong>كلمة المرور</strong></label>
    <input name="password" type="password" style="width:100%;padding:12px;margin:8px 0 16px;border-radius:12px;border:1px solid #E2E8F0;" required>
    <button style="background:linear-gradient(135deg, #3B82F6, #6366F1);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;box-shadow:0 0 20px rgba(99, 102, 241, 0.35);">دخول الأدمن</button>
  </form>
</div>
HTML));
    }

    public function loginSubmit(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $adminEmail = (string) Config::get('ADMIN_EMAIL', '');
        $adminPassword = (string) Config::get('ADMIN_PASSWORD', '');

        if ($email !== $adminEmail || $password !== $adminPassword) {
            Response::html(View::render('Admin Login', '<div class="card"><h1>فشل دخول الأدمن</h1><p class="muted">تحقق من ADMIN_EMAIL و ADMIN_PASSWORD في ملف البيئة.</p><p><a class="btn" href="/admin/login">المحاولة مجددًا</a></p></div>'), 401);
            return;
        }

        $_SESSION['admin_logged_in'] = true;
        if (Database::isAvailable()) {
            (new SaaSRepository())->logAdminActivity($adminEmail, 'admin.login');
        }
        header('Location: /admin');
    }

    public function logout(): void
    {
        unset($_SESSION['admin_logged_in']);
        header('Location: /admin/login');
    }

    public function dashboard(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        if (!Database::isAvailable()) {
            Response::html(View::render('Admin Dashboard', '<div class="card"><h1>قاعدة البيانات غير متاحة</h1></div>'), 500);
            return;
        }

        $repository = new SaaSRepository();
        $stats = $repository->dashboardStats();
        $aiUsage = $repository->aiUsageSummary();
        $aiUsageByMode = $repository->aiUsageSummaryByMode();
        $aiUsageLogs = $repository->listAiUsageLogs(200);
        $dataForSeoUsage = $repository->dataForSeoUsageSummary();
        $dataForSeoUsageByMode = $repository->dataForSeoUsageSummaryByMode();
        $dataForSeoUsageLogs = $repository->listDataForSeoUsageLogs(200);
        $stores = array_slice($repository->listStores(), 0, 6);
        $rows = '';

        foreach ($stores as $store) {
            $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string) ($store['subscription_status'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $planId = (string) ($store['plan_name'] ?? Plans::BUDGET_TRIAL);
            $plan = Plans::get($planId);
            $planDisplay = $plan !== null ? $plan['icon'] . ' ' . $plan['name_ar'] : $planId;
            $used = (int) ($store['used_products'] ?? 0);
            $quota = (int) ($store['product_quota'] ?? 0);
            $rows .= "<tr><td>{$storeName}</td><td><code>{$merchantId}</code></td><td>{$planDisplay}</td><td>{$status}</td><td>{$used} / {$quota}</td><td><a href=\"/admin/stores/{$store['id']}\">فتح</a></td></tr>";
        }

        Response::html(View::render('Admin Dashboard', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>لوحة الأدمن</h1>
      <p class="muted">إدارة المشتركين والمتاجر والاستهلاك من مكان واحد.</p>
    </div>
    <a class="btn" href="/admin/logout">تسجيل الخروج</a>
  </div>
  <div class="grid">
    <div class="card"><h2>المتاجر</h2><p>{$stats['stores_count']}</p></div>
    <div class="card"><h2>نشط</h2><p>{$stats['active_subscriptions']}</p></div>
    <div class="card"><h2>تجريبي</h2><p>{$stats['trial_subscriptions']}</p></div>
    <div class="card"><h2>الاستهلاك</h2><p>{$stats['total_used']} / {$stats['total_quota']}</p></div>
    <div class="card"><h2>تكلفة OpenAI</h2><p>$ {$aiUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>AI Runs</h2><p>{$aiUsage['runs_count']}</p></div>
    <div class="card"><h2>تكلفة DataForSEO</h2><p>$ {$dataForSeoUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>DataForSEO Requests</h2><p>{$dataForSeoUsage['requests_count']}</p></div>
  </div>
  {$this->renderAiUsageByModeCard($aiUsageByMode, 'تكلفة OpenAI حسب نوع التوليد')}
  {$this->renderAiPricingTypeSummaryCard($aiUsageByMode, 'ملخص التسعير لكل نوع')}
  {$this->renderAiUsageLogsCard($aiUsageLogs, 'تفاصيل تكلفة كل عملية AI')}
  {$this->renderDataForSeoUsageByModeCard($dataForSeoUsageByMode, 'تكلفة DataForSEO حسب نوع العملية')}
  {$this->renderDataForSeoUsageLogsCard($dataForSeoUsageLogs, 'تفاصيل تكلفة كل عملية DataForSEO')}
  <div class="card" style="margin-top:16px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
      <h2>آخر المتاجر</h2>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <form method="post" action="/admin/email-test" style="display:inline;">
          <button class="btn" type="submit">اختبار الإيميل</button>
        </form>
        <a class="btn" href="/admin/activity">سجل الأدمن</a>
        <a class="btn" href="/admin/stores">عرض جميع المتاجر</a>
      </div>
    </div>
    <table style="width:100%;border-collapse:collapse;margin-top:12px;">
      <thead><tr><th style="text-align:right;padding:10px;">المتجر</th><th style="text-align:right;padding:10px;">Merchant ID</th><th style="text-align:right;padding:10px;">الباقة</th><th style="text-align:right;padding:10px;">الحالة</th><th style="text-align:right;padding:10px;">الاستهلاك</th><th style="text-align:right;padding:10px;">التفاصيل</th></tr></thead>
      <tbody>{$rows}</tbody>
    </table>
  </div>
</div>
HTML));
    }

    public function stores(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        if (!Database::isAvailable()) {
            Response::html(View::render('Admin Stores', '<div class="card"><h1>قاعدة البيانات غير متاحة</h1></div>'), 500);
            return;
        }

        $repository = new SaaSRepository();
        $stores = $repository->listStores();
        $cards = '';

        foreach ($stores as $store) {
            $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $ownerEmail = htmlspecialchars((string) ($store['owner_email'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars((string) ($store['subscription_status'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $used = (int) ($store['used_products'] ?? 0);
            $quota = (int) ($store['product_quota'] ?? 0);

            $cards .= <<<HTML
<div class="card">
  <h2>{$storeName}</h2>
  <p class="muted">{$ownerEmail}</p>
  <p>Merchant ID: <code>{$merchantId}</code></p>
  <p>الاشتراك: <strong>{$status}</strong></p>
  <p>الاستهلاك: <strong>{$used} / {$quota}</strong></p>
  <p><a class="btn" href="/admin/stores/{$store['id']}">إدارة المتجر</a></p>
</div>
HTML;
        }

        Response::html(View::render('Admin Stores', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>المشتركين والمتاجر</h1>
      <p class="muted">عرض سريع لكل المشتركين والاشتراكات.</p>
    </div>
    <a class="btn" href="/admin">العودة للوحة الأدمن</a>
  </div>
  <div class="grid">{$cards}</div>
</div>
HTML));
    }

    public function store(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            Response::html(View::render('Store Not Found', '<div class="card"><h1>المتجر غير موجود</h1></div>'), 404);
            return;
        }

        $storeName = htmlspecialchars((string) ($store['store_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $ownerEmail = htmlspecialchars((string) ($store['owner_email'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $merchantId = htmlspecialchars((string) ($store['merchant_id'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars((string) ($store['subscription_status'] ?? 'trial'), ENT_QUOTES, 'UTF-8');
        $planId = (string) ($store['plan_name'] ?? Plans::BUDGET_TRIAL);
        $currentPlan = Plans::get($planId) ?? Plans::get(Plans::BUDGET_TRIAL);
        $periodStart = htmlspecialchars((string) ($store['period_started_at'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8');
        $periodEnd = htmlspecialchars((string) ($store['period_ends_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'))), ENT_QUOTES, 'UTF-8');
        $used = (int) ($store['used_products'] ?? 0);
        $quota = (int) ($store['product_quota'] ?? 0);
        $jsonStore = (new StoreRepository())->find((string) ($store['merchant_id'] ?? '')) ?? [];

        $planOptions = '';
        foreach (Plans::all() as $plan) {
            $selected = $plan['id'] === $planId ? 'selected' : '';
            $planOptions .= '<option value="' . $plan['id'] . '" ' . $selected . '>' . $plan['icon'] . ' ' . $plan['name_ar'] . ' - ' . $plan['price_sar'] . ' ر.س</option>';
        }

        $statusOptions = '';
        $statuses = ['trial' => 'تجربة', 'active' => 'نشط', 'inactive' => 'متوقف', 'expired' => 'منتهي'];
        foreach ($statuses as $key => $label) {
            $selected = $key === $status ? 'selected' : '';
            $statusOptions .= '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
        }

        $planQuotasJson = json_encode(array_map(fn($p) => $p['quotas'], Plans::all()));

        $quotaKeys = ['product_description', 'product_seo', 'image_alt', 'keyword_research', 'domain_seo', 'brand_seo', 'category_seo'];
        $currentUsed = [];
        $currentQuota = [];
        foreach ($quotaKeys as $key) {
            $usedKey = 'used_' . $key;
            $quotaKey = 'quota_' . $key;
            $defaultQuota = $currentPlan['quotas'][$key] ?? 0;
            $currentUsed[$key] = (int) ($store[$usedKey] ?? 0);
            $currentQuota[$key] = (int) ($store[$quotaKey] ?? $defaultQuota);
        }
        $currentUsedJson = json_encode($currentUsed);
        $currentQuotaJson = json_encode($currentQuota);
        $planName = $currentPlan !== null
            ? ($currentPlan['icon'] . ' ' . $currentPlan['name_ar'])
            : htmlspecialchars($planId, ENT_QUOTES, 'UTF-8');
        $aiUsage = $repository->storeAiUsageSummary($storeId);
        $aiUsageByMode = $repository->storeAiUsageSummaryByMode($storeId);
        $aiUsageLogs = $repository->listStoreAiUsageLogs($storeId, 200);
        $dataForSeoUsage = $repository->storeDataForSeoUsageSummary($storeId);
        $dataForSeoUsageByMode = $repository->storeDataForSeoUsageSummaryByMode($storeId);
        $dataForSeoUsageLogs = $repository->listStoreDataForSeoUsageLogs($storeId, 200);

        Response::html(View::render('Admin Store', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>إدارة متجر: {$storeName}</h1>
      <p class="muted">{$ownerEmail} | <code>{$merchantId}</code></p>
    </div>
    <a class="btn" href="/admin/stores">العودة للمتاجر</a>
  </div>
  <div class="grid">
    <div class="card"><h2>الحالة</h2><p>{$status}</p></div>
    <div class="card"><h2>الباقة</h2><p>{$planName}</p></div>
    <div class="card"><h2>الاستهلاك</h2><p>{$used} / {$quota}</p></div>
    <div class="card"><h2>تكلفة OpenAI</h2><p>$ {$aiUsage['total_cost_usd']}</p></div>
    <div class="card"><h2>تكلفة DataForSEO</h2><p>$ {$dataForSeoUsage['total_cost_usd']}</p></div>
  </div>
  {$this->renderAiUsageByModeCard($aiUsageByMode, 'تكلفة OpenAI لهذا المتجر حسب النوع')}
  {$this->renderAiPricingTypeSummaryCard($aiUsageByMode, 'ملخص التسعير لهذا المتجر حسب النوع')}
  {$this->renderAiUsageLogsCard($aiUsageLogs, 'تفاصيل تكلفة كل عملية لهذا المتجر')}
  {$this->renderDataForSeoUsageByModeCard($dataForSeoUsageByMode, 'تكلفة DataForSEO لهذا المتجر حسب النوع')}
  {$this->renderDataForSeoUsageLogsCard($dataForSeoUsageLogs, 'تفاصيل تكلفة DataForSEO لهذا المتجر')}
    <div class="card" style="margin-top:16px;">
    <h2>تعديل الاشتراك</h2>
    <form method="post" id="subscription-form">
      <div class="grid">
        <div>
          <label>الحالة</label>
          <select name="status" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
            {$statusOptions}
          </select>
        </div>
        <div>
          <label>الباقة</label>
          <select name="plan_id" id="plan-select" onchange="updatePlanQuotas()" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
            {$planOptions}
          </select>
        </div>
        <div>
          <label>إجمالي الحصة</label>
          <input name="product_quota" type="number" value="{$quota}" id="product-quota" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>المستخدم</label>
          <input name="used_products" type="number" value="{$used}" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>بداية الفترة</label>
          <input name="period_started_at" value="{$periodStart}" type="datetime-local" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
        <div>
          <label>نهاية الفترة</label>
          <input name="period_ends_at" value="{$periodEnd}" type="datetime-local" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
        </div>
      </div>
      <div id="plan-details" class="card" style="margin-top:16px;background:#EEF2FF;border:none;">
        <h3 style="margin:0 0 12px;">تفاصيل الباقة: {$currentPlan['icon']} {$currentPlan['name_ar']}</h3>
        <p style="margin:0;color:#64748B;font-size:14px;">{$currentPlan['description_ar']}</p>
      </div>
      <button type="submit" formaction="/admin/stores/{$store['id']}/subscription" style="background:linear-gradient(135deg, #3B82F6, #6366F1);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;margin-top:12px;box-shadow:0 0 20px rgba(99, 102, 241, 0.35);">حفظ التعديلات</button>
    </form>
  </div>

  <div class="card" style="margin-top:16px;background:#FEF3C7;border:1px solid #F59E0B;">
    <h2 style="margin-top:0;">تعديل الحصص الفردية</h2>
    <p class="muted">أضف أو انقص من حصة كل عملية على حدة. القيم السالبة تنقص من المستخدم.</p>
    <form method="post" action="/admin/stores/{$store['id']}/adjust-quotas">
      <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <div>
          <label>تحسين وصف منتج</label>
          <input name="quota_product_description" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['product_description']} / {$currentQuota['product_description']}</small>
        </div>
        <div>
          <label>تحسين SEO منتج</label>
          <input name="quota_product_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['product_seo']} / {$currentQuota['product_seo']}</small>
        </div>
        <div>
          <label>تحسين ALT صور</label>
          <input name="quota_image_alt" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['image_alt']} / {$currentQuota['image_alt']}</small>
        </div>
        <div>
          <label>كلمات مفتاحية</label>
          <input name="quota_keyword_research" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['keyword_research']} / {$currentQuota['keyword_research']}</small>
        </div>
        <div>
          <label>تحليل سيو دومين</label>
          <input name="quota_domain_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['domain_seo']} / {$currentQuota['domain_seo']}</small>
        </div>
        <div>
          <label>تحسين SEO ماركة</label>
          <input name="quota_brand_seo" type="number" value="0" placeholder="0" style="width:100%;padding:12px;margin-top:8px;border-radius:12px;border:1px solid #E2E8F0;">
          <small class="muted">الحالي: {$currentUsed['brand_seo']} / {$currentQuota['brand_seo']}</small>
        </div>
      </div>
      <p class="muted" style="margin-top:12px;font-size:13px;">💡 أدخل قيمة موجبة (+) لإضافة أو سالبة (-) لإنقاص من الحصة الحالية.</p>
      <button type="submit" style="background:linear-gradient(135deg, #F59E0B, #D97706);color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;margin-top:8px;">تطبيق التعديلات</button>
    </form>
  </div>

  <script>
    const planQuotas = {$planQuotasJson};
    
    function updatePlanQuotas() {
      const planId = document.getElementById('plan-select').value;
      const quota = planQuotas[planId];
      if (quota) {
        const total = Object.values(quota).reduce((a, b) => a + b, 0);
        document.getElementById('product-quota').value = total;
      }
    }
  </script>
  <div class="card danger-zone" style="margin-top:16px;">
    <h2>منطقة خطرة</h2>
    <p class="muted">حذف المتجر سيزيله من قاعدة البيانات ولوحة الأدمن. لا يتم إلغاء تثبيته تلقائيًا من سلة.</p>
    <form method="post" action="/admin/stores/{$store['id']}/delete" onsubmit="return confirm('هل أنت متأكد من حذف هذا المتجر؟');">
      <button style="background:#EF4444;color:#fff;border:none;padding:12px 18px;border-radius:12px;cursor:pointer;box-shadow:0 10px 24px rgba(239, 68, 68, 0.22);">حذف المتجر</button>
    </form>
  </div>
  <div class="card" style="margin-top:16px;">
    <h2>سجل الاستخدام</h2>
    <table style="width:100%;border-collapse:collapse;">
      <thead><tr><th style="text-align:right;padding:10px;">المنتج</th><th style="text-align:right;padding:10px;">وقت الاستخدام</th></tr></thead>
      <tbody>{$logHtml}</tbody>
    </table>
  </div>
</div>
HTML));
    }

    public function updateSubscription(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            Response::html(View::render('Store Not Found', '<div class="card"><h1>المتجر غير موجود</h1></div>'), 404);
            return;
        }

        $planId = trim((string) ($_POST['plan_id'] ?? Plans::BUDGET_TRIAL));
        $plan = Plans::get($planId);
        if ($plan === null) {
            $plan = Plans::get(Plans::BUDGET_TRIAL);
            $planId = Plans::BUDGET_TRIAL;
        }

        $periodStartedAt = trim((string) ($_POST['period_started_at'] ?? ''));
        $periodEndsAt = trim((string) ($_POST['period_ends_at'] ?? ''));
        if ($periodStartedAt !== '') {
            $periodStartedAt = date('Y-m-d H:i:s', strtotime($periodStartedAt));
        }
        if ($periodEndsAt !== '') {
            $periodEndsAt = date('Y-m-d H:i:s', strtotime($periodEndsAt));
        }

        $payload = [
            'status' => trim((string) ($_POST['status'] ?? 'trial')),
            'plan_name' => $planId,
            'product_quota' => (int) ($_POST['product_quota'] ?? array_sum($plan['quotas'])),
            'used_products' => (int) ($_POST['used_products'] ?? 0),
            'period_started_at' => $periodStartedAt,
            'period_ends_at' => $periodEndsAt,
        ];

        foreach ($plan['quotas'] as $key => $value) {
            $payload['quota_' . $key] = $value;
        }

        $repository->updateStoreSubscription($storeId, $payload);
        $repository->logAdminActivity(
            (string) Config::get('ADMIN_EMAIL', 'admin'),
            'subscription.updated',
            'store',
            (string) $storeId,
            $payload
        );

        $jsonStore = (new StoreRepository())->find((string) $store['merchant_id']) ?? [];
        (new StoreRepository())->save((string) $store['merchant_id'], [
            'subscription' => array_merge($jsonStore['subscription'] ?? [], $payload),
        ]);

        header('Location: /admin/stores/' . $storeId);
    }

    public function adjustQuotas(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if (!$store) {
            header('Location: /admin/stores');
            return;
        }

        $quotaKeys = ['product_description', 'product_seo', 'image_alt', 'keyword_research', 'domain_seo', 'brand_seo', 'category_seo'];
        $updates = [];

        foreach ($quotaKeys as $key) {
            $usedKey = 'used_' . $key;
            $quotaKey = 'quota_' . $key;
            $adjustValue = (int) ($_POST['quota_' . $key] ?? 0);
            
            if ($adjustValue !== 0) {
                $currentUsed = (int) ($store[$usedKey] ?? 0);
                $currentQuota = (int) ($store[$quotaKey] ?? 0);
                
                $updates[$usedKey] = max(0, $currentUsed + $adjustValue);
                $updates[$quotaKey] = max(0, $currentQuota + $adjustValue);
            }
        }

        if (!empty($updates)) {
            $repository->updateStoreSubscription($storeId, $updates);
            $repository->logAdminActivity(
                (string) Config::get('ADMIN_EMAIL', 'admin'),
                'quotas.adjusted',
                'store',
                (string) $storeId,
                $updates
            );

            $jsonStore = (new StoreRepository())->find((string) $store['merchant_id']) ?? [];
            $currentSub = $jsonStore['subscription'] ?? [];
            foreach ($updates as $key => $value) {
                $currentSub[$key] = $value;
            }
            (new StoreRepository())->save((string) $store['merchant_id'], [
                'subscription' => $currentSub,
            ]);
        }

        header('Location: /admin/stores/' . $storeId);
    }

    public function deleteStore(array $params): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $storeId = (int) ($params['id'] ?? 0);
        $repository = new SaaSRepository();
        $store = $repository->findStoreById($storeId);

        if ($store) {
            $merchantId = (string) ($store['merchant_id'] ?? '');
            $repository->deleteStore($storeId);
            $repository->logAdminActivity(
                (string) Config::get('ADMIN_EMAIL', 'admin'),
                'store.deleted',
                'store',
                (string) $storeId,
                ['merchant_id' => $merchantId, 'store_name' => $store['store_name'] ?? null]
            );

            if ($merchantId !== '') {
                (new StoreRepository())->delete($merchantId);
            }
        }

        header('Location: /admin/stores');
    }

    public function activity(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $logs = Database::isAvailable() ? (new SaaSRepository())->listAdminActivityLogs(200) : [];
        $rows = '';

        foreach ($logs as $log) {
            $details = htmlspecialchars((string) ($log['details_json'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['created_at'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['admin_email'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) $log['action'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) ($log['target_type'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars((string) ($log['target_id'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;max-width:340px;word-break:break-word;">' . $details . '</td>'
                . '</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="6" style="padding:10px;">لا توجد نشاطات مسجلة بعد.</td></tr>';
        }

        Response::html(View::render('Admin Activity', <<<HTML
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
    <div>
      <h1>سجل نشاط الأدمن</h1>
      <p class="muted">كل التعديلات الإدارية المهمة تظهر هنا.</p>
    </div>
    <a class="btn" href="/admin">العودة للوحة الأدمن</a>
  </div>
  <table style="width:100%;border-collapse:collapse;margin-top:12px;">
    <thead><tr><th style="text-align:right;padding:10px;">الوقت</th><th style="text-align:right;padding:10px;">الأدمن</th><th style="text-align:right;padding:10px;">الإجراء</th><th style="text-align:right;padding:10px;">النوع</th><th style="text-align:right;padding:10px;">المعرف</th><th style="text-align:right;padding:10px;">التفاصيل</th></tr></thead>
    <tbody>{$rows}</tbody>
  </table>
</div>
HTML));
    }

    public function sendTestEmail(): void
    {
        if (!$this->ensureAdmin()) {
            return;
        }

        $adminEmail = (string) Config::get('ADMIN_EMAIL', '');
        $targetEmail = (string) Config::get('MAIL_FROM_ADDRESS', $adminEmail);

        try {
            (new \App\Services\Mailer())->sendTestEmail($targetEmail);

            if (Database::isAvailable()) {
                (new SaaSRepository())->logAdminActivity(
                    $adminEmail,
                    'email.test.sent',
                    'mail',
                    $targetEmail
                );
            }

            Response::html(View::render('Email Test', '<div class="card"><h1>تم إرسال بريد الاختبار</h1><p class="muted">تحقق من صندوق البريد: <strong>' . htmlspecialchars($targetEmail, ENT_QUOTES, 'UTF-8') . '</strong></p><p><a class="btn" href="/admin">العودة للوحة الأدمن</a></p></div>'));
        } catch (\Throwable $exception) {
            Response::html(View::render('Email Test Failed', '<div class="card"><h1>فشل إرسال بريد الاختبار</h1><p class="muted">' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8') . '</p><p><a class="btn" href="/admin">العودة</a></p></div>'), 500);
        }
    }

    private function renderAiUsageByModeCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد بيانات تكلفة بعد.</p></div>';
        }

        $tableRows = '';
        $totalRuns = 0;
        $totalCost = 0.0;

        foreach ($rows as $row) {
            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $runs = (int) ($row['runs_count'] ?? 0);
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $inputTokens = number_format((int) ($row['input_tokens'] ?? 0));
            $outputTokens = number_format((int) ($row['output_tokens'] ?? 0));
            $totalRuns += $runs;
            $totalCost += $cost;

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . $label . '</td>'
                . '<td style="padding:10px;">' . number_format($runs) . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '<td style="padding:10px;">' . $inputTokens . '</td>'
                . '<td style="padding:10px;">' . $outputTokens . '</td>'
                . '</tr>';
        }

        $footer = '<tr style="font-weight:700;background:#EEF2FF;">'
            . '<td style="padding:10px;">الإجمالي</td>'
            . '<td style="padding:10px;">' . number_format($totalRuns) . '</td>'
            . '<td style="padding:10px;">$ ' . $this->formatUsd($totalCost) . '</td>'
            . '<td style="padding:10px;">-</td>'
            . '<td style="padding:10px;">-</td>'
            . '</tr>';

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">النوع</th>'
            . '<th style="text-align:right;padding:10px;">عدد العمليات</th>'
            . '<th style="text-align:right;padding:10px;">التكلفة (USD)</th>'
            . '<th style="text-align:right;padding:10px;">Input Tokens</th>'
            . '<th style="text-align:right;padding:10px;">Output Tokens</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . $footer . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderAiUsageLogsCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد عمليات AI مسجلة بعد.</p></div>';
        }

        $modeLabels = [
            'description' => 'وصف المنتج',
            'seo' => 'سيو المنتج',
            'all' => 'وصف + سيو المنتج',
            'image_alt' => 'ALT الصور',
            'image_alt_bulk' => 'ALT الصور (جملة)',
            'store_seo' => 'سيو المتجر',
            'unknown' => 'غير مصنف',
        ];

        $tableRows = '';
        foreach ($rows as $row) {
            $mode = (string) ($row['mode'] ?? 'unknown');
            $label = $modeLabels[$mode] ?? $mode;
            $storeName = (string) ($row['store_name'] ?? '-');
            $merchantId = (string) ($row['merchant_id'] ?? '-');
            $productId = (string) ($row['product_id'] ?? '-');
            $inputTokens = number_format((int) ($row['input_tokens'] ?? 0));
            $outputTokens = number_format((int) ($row['output_tokens'] ?? 0));
            $totalTokens = number_format((int) ($row['total_tokens'] ?? 0));
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '-');

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') . '<br><code>' . htmlspecialchars($merchantId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;"><code>' . htmlspecialchars($productId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;">' . $inputTokens . ' / ' . $outputTokens . ' / ' . $totalTokens . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">الوقت</th>'
            . '<th style="text-align:right;padding:10px;">نوع العملية</th>'
            . '<th style="text-align:right;padding:10px;">المتجر</th>'
            . '<th style="text-align:right;padding:10px;">Product ID</th>'
            . '<th style="text-align:right;padding:10px;">Input/Output/Total Tokens</th>'
            . '<th style="text-align:right;padding:10px;">التكلفة (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderAiPricingTypeSummaryCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد بيانات كافية للتسعير بعد.</p></div>';
        }

        usort($rows, static function (array $a, array $b): int {
            return ((float) ($b['total_cost_usd'] ?? 0)) <=> ((float) ($a['total_cost_usd'] ?? 0));
        });

        $cards = '';
        foreach ($rows as $row) {
            $runs = (int) ($row['runs_count'] ?? 0);
            if ($runs <= 0) {
                continue;
            }

            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $totalCost = (float) ($row['total_cost_usd'] ?? 0);
            $avgCost = $totalCost / $runs;

            $cards .= '<div class="card" style="margin:0;">'
                . '<h3 style="margin:0 0 10px 0;">' . $label . '</h3>'
                . '<p style="margin:0 0 6px 0;">عدد العمليات: <strong>' . number_format($runs) . '</strong></p>'
                . '<p style="margin:0 0 6px 0;">إجمالي التكلفة: <strong>$ ' . $this->formatUsd($totalCost) . '</strong></p>'
                . '<p style="margin:0;">متوسط تكلفة العملية: <strong>$ ' . $this->formatUsd($avgCost) . '</strong></p>'
                . '</div>';
        }

        if ($cards === '') {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد عمليات مولدة حتى الآن.</p></div>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p class="muted">هذا الملخص يساعدك في التسعير لاحقًا بحسب كل نوع توليد.</p>'
            . '<div class="grid" style="margin-top:12px;">' . $cards . '</div>'
            . '</div>';
    }

    private function renderDataForSeoUsageByModeCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد بيانات تكلفة DataForSEO بعد.</p></div>';
        }

        $tableRows = '';
        $totalRuns = 0;
        $totalRequests = 0;
        $totalCost = 0.0;

        foreach ($rows as $row) {
            $label = htmlspecialchars((string) ($row['label'] ?? $row['mode'] ?? '-'), ENT_QUOTES, 'UTF-8');
            $runs = (int) ($row['runs_count'] ?? 0);
            $requestsCount = (int) ($row['requests_count'] ?? 0);
            $cost = (float) ($row['total_cost_usd'] ?? 0);

            $totalRuns += $runs;
            $totalRequests += $requestsCount;
            $totalCost += $cost;

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . $label . '</td>'
                . '<td style="padding:10px;">' . number_format($runs) . '</td>'
                . '<td style="padding:10px;">' . number_format($requestsCount) . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        $footer = '<tr style="font-weight:700;background:#EEF2FF;">'
            . '<td style="padding:10px;">الإجمالي</td>'
            . '<td style="padding:10px;">' . number_format($totalRuns) . '</td>'
            . '<td style="padding:10px;">' . number_format($totalRequests) . '</td>'
            . '<td style="padding:10px;">$ ' . $this->formatUsd($totalCost) . '</td>'
            . '</tr>';

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">النوع</th>'
            . '<th style="text-align:right;padding:10px;">عدد العمليات</th>'
            . '<th style="text-align:right;padding:10px;">عدد طلبات API</th>'
            . '<th style="text-align:right;padding:10px;">التكلفة (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . $footer . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function renderDataForSeoUsageLogsCard(array $rows, string $title): string
    {
        if ($rows === []) {
            return '<div class="card" style="margin-top:16px;"><h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2><p class="muted">لا توجد عمليات DataForSEO مسجلة بعد.</p></div>';
        }

        $modeLabels = [
            'keyword_research' => 'بحث الكلمات المفتاحية',
            'domain_seo' => 'تحليل سيو دومين',
            'unknown' => 'غير مصنف',
        ];

        $tableRows = '';
        foreach ($rows as $row) {
            $mode = (string) ($row['mode'] ?? 'unknown');
            $label = $modeLabels[$mode] ?? $mode;
            $storeName = (string) ($row['store_name'] ?? '-');
            $merchantId = (string) ($row['merchant_id'] ?? '-');
            $target = (string) ($row['target'] ?? '-');
            $requestsCount = number_format((int) ($row['requests_count'] ?? 0));
            $cost = (float) ($row['total_cost_usd'] ?? 0);
            $createdAt = (string) ($row['created_at'] ?? '-');

            $tableRows .= '<tr>'
                . '<td style="padding:10px;">' . htmlspecialchars($createdAt, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="padding:10px;">' . htmlspecialchars($storeName, ENT_QUOTES, 'UTF-8') . '<br><code>' . htmlspecialchars($merchantId, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;"><code>' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '</code></td>'
                . '<td style="padding:10px;">' . $requestsCount . '</td>'
                . '<td style="padding:10px;">$ ' . $this->formatUsd($cost) . '</td>'
                . '</tr>';
        }

        return '<div class="card" style="margin-top:16px;">'
            . '<h2>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px;">'
            . '<thead><tr>'
            . '<th style="text-align:right;padding:10px;">الوقت</th>'
            . '<th style="text-align:right;padding:10px;">نوع العملية</th>'
            . '<th style="text-align:right;padding:10px;">المتجر</th>'
            . '<th style="text-align:right;padding:10px;">القيمة المدخلة</th>'
            . '<th style="text-align:right;padding:10px;">عدد طلبات API</th>'
            . '<th style="text-align:right;padding:10px;">التكلفة (USD)</th>'
            . '</tr></thead>'
            . '<tbody>' . $tableRows . '</tbody>'
            . '</table>'
            . '</div>';
    }

    private function formatUsd(float $value): string
    {
        return number_format($value, 6, '.', '');
    }

    private function ensureAdmin(): bool
    {
        if (!($_SESSION['admin_logged_in'] ?? false)) {
            header('Location: /admin/login');
            return false;
        }

        return true;
    }
}
