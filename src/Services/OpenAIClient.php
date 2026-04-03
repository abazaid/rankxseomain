<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

final class OpenAIClient
{
    private const API_BASE = 'https://api.openai.com/v1';

    public function __construct(
        private readonly HttpClient $httpClient = new HttpClient()
    ) {
    }

    public function generateProductDescription(array $product, array $settings = []): string
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');

        $response = $this->httpClient->post(self::API_BASE . '/responses', [
            'model' => $model,
            'reasoning' => [
                'effort' => $reasoningEffort,
            ],
            'input' => $this->buildPrompt($product, $settings),
        ], [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ], 90);

        $body = $response['body'];
        $text = $this->extractText($body);

        if ($text === '') {
            throw new RuntimeException('OpenAI returned an empty description.');
        }

        return trim($text);
    }

    public function generateProductContent(array $product, array $settings = [], string $mode = 'all'): array
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');
        $mode = in_array($mode, ['description', 'seo', 'all'], true) ? $mode : 'all';

        $response = $this->httpClient->post(self::API_BASE . '/responses', [
            'model' => $model,
            'reasoning' => [
                'effort' => $reasoningEffort,
            ],
            'input' => $this->buildContentPrompt($product, $settings, $mode),
        ], [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ], 90);

        $body = $response['body'];
        $text = $this->extractText($body);

        if ($text === '') {
            throw new RuntimeException('OpenAI returned empty content.');
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON content.');
        }

        $currentDescription = trim(strip_tags((string) ($product['description'] ?? '')));
        $currentMetadataTitle = trim((string) ($product['metadata']['title'] ?? ''));
        $currentMetadataDescription = trim((string) ($product['metadata']['description'] ?? ''));
        $currentSeoSlug = trim((string) ($product['seo_slug'] ?? ''));

        $description = trim((string) ($decoded['description'] ?? ''));
        $metadataTitle = trim((string) ($decoded['metadata_title'] ?? ''));
        $metadataDescription = trim((string) ($decoded['metadata_description'] ?? ''));
        $seoSlug = $this->buildSeoSlug((string) ($decoded['seo_slug'] ?? ($product['name'] ?? '')));

        if ($mode === 'description') {
            $description = $this->ensureHtmlDescription($description);
            $metadataTitle = $currentMetadataTitle;
            $metadataDescription = $currentMetadataDescription;
            $seoSlug = $currentSeoSlug !== '' ? $this->buildSeoSlug($currentSeoSlug) : $seoSlug;
        } elseif ($mode === 'seo') {
            $description = $currentDescription;
        } else {
            $description = $this->ensureHtmlDescription($description);
        }

        $usage = is_array($body['usage'] ?? null) ? $body['usage'] : [];

        return [
            'description' => $description,
            'metadata_title' => $metadataTitle,
            'metadata_description' => $metadataDescription,
            'seo_slug' => $seoSlug,
            '_usage' => $usage,
            '_model' => $model,
        ];
    }

    public function generateImageAlt(array $product, array $image, array $settings = []): array
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $imageAltInstructions = trim((string) ($settings['image_alt_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $userContentText = "Generate one ALT text in language={$language} as an SEO professional.\nRules:\n- Length target: 55-70 characters.\n- Mention the product clearly and naturally.\n- Must be a complete, readable phrase (not cut off).\n- No keyword stuffing.\n- No promotional phrases.\n- Use letters, numbers and spaces only.\n- Return only ALT text.\n"
            . $this->buildInstructionBlock('Global merchant instructions', $globalInstructions)
            . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
            . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
            . $this->buildInstructionBlock('Image ALT instructions', $imageAltInstructions)
            . "\nProduct name: " . (string) ($product['name'] ?? 'Product') . "\nCurrent image alt: " . (string) ($image['alt'] ?? '');

        $imageUrl = (string) ($image['url'] ?? '');

        try {
            $contentWithImage = [
                [
                    'type' => 'input_text',
                    'text' => $userContentText,
                ],
            ];

            if ($imageUrl !== '') {
                $contentWithImage[] = [
                    'type' => 'input_image',
                    'image_url' => $imageUrl,
                ];
            }

            $response = $this->httpClient->post(self::API_BASE . '/responses', [
                'model' => $model,
                'reasoning' => [
                    'effort' => $reasoningEffort,
                ],
                'input' => [
                    [
                        'role' => 'system',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => 'You are an expert ecommerce SEO specialist writing image ALT text. Write concise, natural, descriptive ALT text for product images. Return only plain ALT text without quotes, JSON, markdown, emojis, hashtags, or extra commentary.',
                            ],
                        ],
                    ],
                    [
                        'role' => 'user',
                        'content' => $contentWithImage,
                    ],
                ],
            ], [
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
            ], 90);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'does not support image input') || str_contains($e->getMessage(), 'Cannot read')) {
                $response = $this->httpClient->post(self::API_BASE . '/responses', [
                    'model' => $model,
                    'reasoning' => [
                        'effort' => $reasoningEffort,
                    ],
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => 'You are an expert ecommerce SEO specialist writing image ALT text. Write concise, natural, descriptive ALT text for product images. Return only plain ALT text without quotes, JSON, markdown, emojis, hashtags, or extra commentary.',
                                ],
                            ],
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => $userContentText,
                                ],
                            ],
                        ],
                    ],
                ], [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'application/json',
                ], 90);
            } else {
                throw $e;
            }
        }

        $body = $response['body'];
        $text = trim($this->extractText($body));

        if ($text === '') {
            throw new RuntimeException('OpenAI returned an empty image alt text.');
        }

        $text = $this->limitAtWordBoundary($text, 70);

        $usage = is_array($body['usage'] ?? null) ? $body['usage'] : [];

        return [
            'alt' => $text,
            '_usage' => $usage,
            '_model' => $model,
        ];
    }

    public function generateStoreSeo(array $storeContext, array $currentSeo = [], array $settings = []): array
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $storeSeoInstructions = trim((string) ($settings['store_seo_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $response = $this->httpClient->post(self::API_BASE . '/responses', [
            'model' => $model,
            'reasoning' => [
                'effort' => $reasoningEffort,
            ],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'You are a senior ecommerce SEO strategist. Return valid JSON only with keys: title, description, keywords. First analyze existing SEO and improve it (do not rewrite blindly). Infer the store niche from product names/topics. Keep claims factual and specific to the store context.',
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Generate homepage SEO settings in language={$language}. Return JSON only.\nRules:\n- Title should be around 35-65 characters and include core intent.\n- Description should be around 120-160 characters, compelling but factual.\n- Keywords should be 6-12 high-intent terms, comma-separated, no stuffing.\n- Reuse useful existing terms if they are relevant.\n- Reflect the actual store activity from products sample/topics.\n"
                                . $this->buildInstructionBlock('Global merchant instructions', $globalInstructions)
                                . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
                                . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
                                . $this->buildInstructionBlock('Store SEO instructions', $storeSeoInstructions)
                                . "\nStore context:\n" . json_encode([
                                    'store_name' => $storeContext['store_name'] ?? null,
                                    'merchant_id' => $storeContext['merchant_id'] ?? null,
                                    'store_url' => $storeContext['store_url'] ?? null,
                                    'products_count' => $storeContext['products_count'] ?? null,
                                    'products_sample' => $storeContext['products_sample'] ?? [],
                                    'product_topics' => $storeContext['product_topics'] ?? [],
                                    'existing_title' => $currentSeo['title'] ?? null,
                                    'existing_description' => $currentSeo['description'] ?? null,
                                    'existing_keywords' => $currentSeo['keywords'] ?? null,
                                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                        ],
                    ],
                ],
            ],
        ], [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ], 90);

        $body = $response['body'];
        $text = $this->extractText($body);

        if ($text === '') {
            throw new RuntimeException('OpenAI returned empty store SEO content.');
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON for store SEO.');
        }

        $title = $this->limitText(trim((string) ($decoded['title'] ?? '')), 70);
        $description = $this->limitText(trim((string) ($decoded['description'] ?? '')), 300);
        $keywords = $this->normalizeKeywords((string) ($decoded['keywords'] ?? ''));

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            '_usage' => is_array($body['usage'] ?? null) ? $body['usage'] : [],
            '_model' => $model,
        ];
    }

    public function generateBrandSeo(array $brand, array $settings = []): array
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $brandSeoInstructions = trim((string) ($settings['brand_seo_instructions'] ?? ''));
        $metaTitleInstructions = trim((string) ($settings['meta_title_instructions'] ?? ''));
        $metaDescriptionInstructions = trim((string) ($settings['meta_description_instructions'] ?? ''));
        $seoPageUrlInstructions = trim((string) ($settings['seo_page_url_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $response = $this->httpClient->post(self::API_BASE . '/responses', [
            'model' => $model,
            'reasoning' => [
                'effort' => $reasoningEffort,
            ],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'You are an expert ecommerce SEO specialist writing brand content in Arabic for Saudi customers. Return ONLY valid JSON.',
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Generate SEO-optimized content for a brand in language={$language}.

Brand Information:
- Name: " . ($brand['name'] ?? 'Brand') . "
- Current Description: " . ($brand['description'] ?? 'No description') . "

Rules:
- Write compelling meta_title (max 60 characters)
- Write meta_description (max 160 characters)
- Write a SHORT plain-text brand description (no HTML tags, no headings, no markdown, max 255 characters)
- Include value proposition and relevance to the brand keyword
- Generate SEO slug from brand name only (no extra words), lowercase, hyphen-separated, URL-safe, max 90 chars
- Target Saudi Arabian market
- Use natural Arabic
- Keep content strictly relevant to the merchant business profile and brand keyword (no generic filler)
"
                                . $this->buildInstructionBlock('Global instructions', $globalInstructions)
                                . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
                                . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
                                . $this->buildInstructionBlock('Meta Title rules', $metaTitleInstructions)
                                . $this->buildInstructionBlock('Meta Description rules', $metaDescriptionInstructions)
                                . $this->buildInstructionBlock('SEO Page URL rules', $seoPageUrlInstructions)
                                . $this->buildInstructionBlock('Brand SEO instructions', $brandSeoInstructions)
                                . "\nReturn ONLY JSON: {\"meta_title\": \"...\", \"meta_description\": \"...\", \"description\": \"...\", \"seo_slug\": \"...\"}",
                        ],
                    ],
                ],
            ],
        ], [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ], 90);

        $body = $response['body'];
        $text = $this->extractText($body);

        if ($text === '') {
            throw new RuntimeException('OpenAI returned empty brand SEO content.');
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON for brand SEO.');
        }

        $descriptionRaw = trim((string) (
            $decoded['description']
            ?? $decoded['brand_description']
            ?? ''
        ));
        $description = $this->limitText(trim(strip_tags($descriptionRaw)), 255);
        if ($description === '') {
            throw new RuntimeException('OpenAI returned empty brand description content.');
        }
        $slugSource = trim((string) ($decoded['seo_slug'] ?? ($brand['name'] ?? '')));
        $seoSlug = $this->buildSeoSlug($slugSource);

        return [
            'meta_title' => $this->limitText(trim((string) ($decoded['meta_title'] ?? '')), 60),
            'meta_description' => $this->limitText(trim((string) ($decoded['meta_description'] ?? '')), 160),
            'description' => $description,
            'seo_slug' => $seoSlug,
            '_usage' => is_array($body['usage'] ?? null) ? $body['usage'] : [],
            '_model' => $model,
        ];
    }

    public function generateCategorySeo(array $category, array $settings = []): array
    {
        $apiKey = Config::get('OPENAI_API_KEY');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is missing.');
        }

        $model = Config::get('OPENAI_MODEL', 'gpt-5-mini');
        $reasoningEffort = Config::get('OPENAI_REASONING_EFFORT', 'low');
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $categorySeoInstructions = trim((string) ($settings['category_seo_instructions'] ?? ''));
        $metaTitleInstructions = trim((string) ($settings['meta_title_instructions'] ?? ''));
        $metaDescriptionInstructions = trim((string) ($settings['meta_description_instructions'] ?? ''));
        $seoPageUrlInstructions = trim((string) ($settings['seo_page_url_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $response = $this->httpClient->post(self::API_BASE . '/responses', [
            'model' => $model,
            'reasoning' => [
                'effort' => $reasoningEffort,
            ],
            'input' => [
                [
                    'role' => 'system',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => 'You are an expert ecommerce SEO specialist writing category SEO content in Arabic for Saudi customers. Return ONLY valid JSON.',
                        ],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => "Generate SEO-optimized category content in language={$language}.

Category Information:
- Name: " . ($category['name'] ?? 'Category') . "
- Current Meta Title: " . ($category['meta_title'] ?? 'None') . "
- Current Meta Description: " . ($category['meta_description'] ?? 'None') . "

Rules:
- Write compelling meta_title (max 60 characters)
- Write meta_description (max 160 characters)
- Generate SEO slug from category name only (no extra words), lowercase, hyphen-separated, URL-safe, max 90 chars
- Target Saudi Arabian market
- Use natural Arabic
- Focus on the category name and what products it contains
- Keep content strictly relevant to the merchant business profile and category keyword (no generic filler)
"
                                . $this->buildInstructionBlock('Global instructions', $globalInstructions)
                                . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
                                . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
                                . $this->buildInstructionBlock('Meta Title rules', $metaTitleInstructions)
                                . $this->buildInstructionBlock('Meta Description rules', $metaDescriptionInstructions)
                                . $this->buildInstructionBlock('SEO Page URL rules', $seoPageUrlInstructions)
                                . $this->buildInstructionBlock('Category SEO instructions', $categorySeoInstructions)
                                . "\nReturn ONLY JSON: {\"meta_title\": \"...\", \"meta_description\": \"...\", \"seo_slug\": \"...\"}",
                        ],
                    ],
                ],
            ],
        ], [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ], 90);

        $body = $response['body'];
        $text = $this->extractText($body);

        if ($text === '') {
            throw new RuntimeException('OpenAI returned empty category SEO content.');
        }

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON for category SEO.');
        }

        $slugSource = trim((string) ($decoded['seo_slug'] ?? ($category['name'] ?? '')));
        $seoSlug = $this->buildSeoSlug($slugSource);

        return [
            'meta_title' => $this->limitText(trim((string) ($decoded['meta_title'] ?? '')), 60),
            'meta_description' => $this->limitText(trim((string) ($decoded['meta_description'] ?? '')), 160),
            'description' => '',
            'seo_slug' => $seoSlug,
            '_usage' => is_array($body['usage'] ?? null) ? $body['usage'] : [],
            '_model' => $model,
        ];
    }

    private function normalizeKeywords(string $keywords): string
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return '';
        }

        $parts = preg_split('/[,،]+/u', $keywords) ?: [];
        $clean = [];

        foreach ($parts as $part) {
            $token = trim($part);
            if ($token === '') {
                continue;
            }
            $token = preg_replace('/\s+/u', ' ', $token) ?? $token;
            $lower = function_exists('mb_strtolower') ? mb_strtolower($token, 'UTF-8') : strtolower($token);
            if (!isset($clean[$lower])) {
                $clean[$lower] = $token;
            }
        }

        return implode(', ', array_slice(array_values($clean), 0, 12));
    }

    private function buildPrompt(array $product, array $settings): array
    {
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $productInstructions = trim((string) ($settings['product_description_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $productSummary = [
            'id' => $product['id'] ?? null,
            'name' => $product['name'] ?? null,
            'sku' => $product['sku'] ?? null,
            'price' => $product['price']['amount'] ?? $product['price'] ?? null,
            'quantity' => $product['quantity'] ?? null,
            'currency' => $product['currency'] ?? 'SAR',
            'description' => strip_tags((string) ($product['description'] ?? '')),
            'status' => $product['status'] ?? null,
        ];

        return [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'You are a veteran ecommerce SEO strategist with 50+ years of practical experience in search content. Your mission is to maximize ranking potential and push this product toward position #1 with high-quality, trustworthy content. Write detailed, SEO-optimized product descriptions following EXACTLY the structure provided in the instructions.

IMPORTANT STRUCTURE RULES:
1. Start with an introduction paragraph (no heading) - introduce the product, brand, and main benefit
2. Use <h2> for ALL section headings (never skip headings or change order)
3. Use <p> for paragraphs and <ul><li> for bullet points
4. Use <strong> to highlight key terms
5. Use <a href="...">...</a> ONLY for internal links when provided
6. NEVER use markdown, emojis, or H2: labels
7. NEVER fabricate specifications - only use provided product data
8. Target 800-1200 words minimum (detailed content)

MANDATORY SECTION ORDER (DO NOT SKIP OR REORDER):
1. Introduction (no heading) - product name, brand, main benefit
2. <h2>نظرة عامة على المنتج</h2>
3. <h2>أهم المميزات</h2> (bullet points)
4. <h2>المواصفات</h2>
5. <h2>التصميم وجودة التصنيع</h2>
6. <h2>الأداء وتجربة الاستخدام</h2>
7. <h2>تقييمنا للمنتج</h2>
8. <h2>طريقة الاستخدام</h2>
9. <h2>لماذا يختار العملاء هذا المنتج</h2>
10. <h2>لمن يناسب هذا المنتج</h2>
11. <h2>لماذا تشتري من متجرنا</h2>
12. <h2>منتجات قد تهمك</h2> (with internal links)
13. <h2>الأسئلة الشائعة</h2>

Return ONLY clean HTML without any labels, comments, or explanations.',
                    ],
                ],
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                            'text' => "Generate a detailed product description in language={$language}.\n\nFollow the merchant's specific instructions EXACTLY and maintain the mandatory section order shown in the system prompt.\n\n"
                            . $this->buildInstructionBlock('Internal links', $this->buildInternalLinksPromptBlock($product, $settings, true))
                            . $this->buildInstructionBlock('Merchant Style Guide', $globalInstructions)
                            . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
                            . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
                            . $this->buildInstructionBlock('Description Template & Rules', $productInstructions)
                            . "\nProduct Data (use ONLY provided data, do NOT fabricate):\n" . json_encode($productSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                    ],
                ],
            ],
        ];
    }

    private function limitText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '' || $maxLength <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') <= $maxLength) {
                return $value;
            }

            return rtrim(mb_substr($value, 0, $maxLength, 'UTF-8'));
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(substr($value, 0, $maxLength));
    }

    private function limitAtWordBoundary(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($value === '' || $maxLength <= 0) {
            return '';
        }

        $limited = $this->limitText($value, $maxLength);
        if ($limited === $value) {
            return $limited;
        }

        $chunks = preg_split('/\s+/u', $limited) ?: [];
        if (count($chunks) <= 1) {
            return $limited;
        }

        array_pop($chunks);
        $wordSafe = trim(implode(' ', $chunks));

        return $wordSafe !== '' ? $wordSafe : $limited;
    }

    private function buildContentPrompt(array $product, array $settings, string $mode): array
    {
        $language = trim((string) ($settings['output_language'] ?? ''));
        if ($language === '') {
            $language = 'ar';
        }
        $globalInstructions = trim((string) ($settings['global_instructions'] ?? ''));
        $productInstructions = trim((string) ($settings['product_description_instructions'] ?? ''));
        $metaTitleInstructions = trim((string) ($settings['meta_title_instructions'] ?? ''));
        $metaDescriptionInstructions = trim((string) ($settings['meta_description_instructions'] ?? ''));
        $seoPageUrlInstructions = trim((string) ($settings['seo_page_url_instructions'] ?? ''));
        $businessContextBlock = $this->buildBusinessContextBlock($settings);

        $productSummary = [
            'id' => $product['id'] ?? null,
            'name' => $product['name'] ?? null,
            'sku' => $product['sku'] ?? null,
            'price' => $product['price']['amount'] ?? $product['price'] ?? null,
            'quantity' => $product['quantity'] ?? null,
            'currency' => $product['currency'] ?? 'SAR',
            'description' => strip_tags((string) ($product['description'] ?? '')),
            'metadata_title' => $product['metadata']['title'] ?? null,
            'metadata_description' => $product['metadata']['description'] ?? null,
            'status' => $product['status'] ?? null,
            'competitor_insights' => is_array($product['competitor_insights'] ?? null) ? (array) $product['competitor_insights'] : null,
        ];

        $modeInstruction = match ($mode) {
            'description' => 'Focus on rewriting the product description only. Keep metadata_title and metadata_description unchanged from the input when returning JSON.',
            'seo' => 'Focus on generating only SEO metadata. Keep the description unchanged from the input when returning JSON.',
            default => 'Generate both an improved product description and improved SEO metadata.',
        };

        return [
            [
                'role' => 'system',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => 'You are a veteran ecommerce SEO strategist with 50+ years of practical experience. Your mission is to maximize ranking potential and push this product toward position #1 through superior structure, intent-match, and usefulness. Return valid JSON with EXACTLY these keys: description, metadata_title, metadata_description, seo_slug.

DESCRIPTION STRUCTURE (CRITICAL):
1. Start with introduction paragraph (no heading)
2. Use <h2> for ALL section headings
3. Use <p> for paragraphs, <ul><li> for bullets
4. Use <strong> for key terms
5. Use <a href="...">...</a> ONLY for internal links
6. NEVER use markdown, emojis, or "H2:" labels
7. NEVER fabricate specs - only use provided data
8. Target 800-1200 words minimum

MANDATORY SECTION ORDER (DO NOT SKIP):
1. Introduction (no heading)
2. <h2>نظرة عامة على المنتج</h2>
3. <h2>أهم المميزات</h2> (bullets)
4. <h2>المواصفات</h2>
5. <h2>التصميم وجودة التصنيع</h2>
6. <h2>الأداء وتجربة الاستخدام</h2>
7. <h2>تقييمنا للمنتج</h2>
8. <h2>طريقة الاستخدام</h2>
9. <h2>لماذا يختار العملاء هذا المنتج</h2>
10. <h2>لمن يناسب هذا المنتج</h2>
11. <h2>لماذا تشتري من متجرنا</h2>
12. <h2>منتجات قد تهمك</h2> (with links)
13. <h2>الأسئلة الشائعة</h2>

METADATA: metadata_title (50-60 chars, start with product name), metadata_description (140-155 chars, include product name and CTA).
SEO PAGE URL: seo_slug must be generated from the product name only (no extra words), lowercase, hyphen-separated, URL-safe, supports Arabic/English mixed names, max 90 chars.

COMPETITOR MODE (when competitor_insights is provided):
- Analyze competitor patterns to produce stronger, richer structure and coverage.
- Do NOT copy competitor brand names, store names, proprietary claims, or misleading facts.
- Keep content aligned with merchant identity and provided business profile only.
- Use competitor data as inspiration for structure and missing angles only.',
                    ],
                ],
            ],
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                            'text' => "Generate product content in language={$language}. {$modeInstruction} Return valid JSON only.\n"
                            . $this->buildInstructionBlock('Internal links', $this->buildInternalLinksPromptBlock($product, $settings, $mode !== 'seo'))
                            . $this->buildInstructionBlock('Style Guide', $globalInstructions)
                            . $this->buildInstructionBlock('Merchant business profile', $businessContextBlock)
                            . $this->buildInstructionBlock('Natural Writing Style', $this->buildNaturalWritingBlock())
                            . $this->buildInstructionBlock('Description Template', $productInstructions)
                            . $this->buildInstructionBlock('Meta Title Rules', $metaTitleInstructions)
                            . $this->buildInstructionBlock('Meta Description Rules', $metaDescriptionInstructions)
                            . $this->buildInstructionBlock('SEO Page URL Rules', $seoPageUrlInstructions)
                            . $this->buildInstructionBlock('Competitor Analysis Guidance', $this->buildCompetitorGuidanceBlock($product))
                            . $this->buildInstructionBlock('Competitor Analysis Input', $this->buildCompetitorInputBlock($product))
                            . "\nProduct Data:\n" . json_encode($productSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
                    ],
                ],
            ],
        ];
    }

    private function buildInstructionBlock(string $label, string $instructions): string
    {
        $instructions = trim($instructions);
        if ($instructions === '') {
            return '';
        }

        return "\n{$label}:\n{$instructions}\n";
    }

    private function buildBusinessContextBlock(array $settings): string
    {
        $brandName = trim((string) ($settings['business_brand_name'] ?? ''));
        $overview = trim((string) ($settings['business_overview'] ?? ''));

        if ($brandName === '' && $overview === '') {
            return '';
        }

        $lines = [];
        if ($brandName !== '') {
            $lines[] = 'Store/Brand Name: ' . $brandName;
        }
        if ($overview !== '') {
            $lines[] = 'Business Overview: ' . $overview;
        }

        return implode("\n", $lines);
    }

    private function buildNaturalWritingBlock(): string
    {
        return "Write in a genuinely human, editorial style that feels natural to Arabic readers in Saudi Arabia.\n"
            . "Vary sentence length and rhythm: mix short and medium sentences, and avoid repetitive openings.\n"
            . "Use concrete, product-specific details and practical phrasing instead of generic marketing wording.\n"
            . "Avoid robotic connectors and over-structured transitions (e.g., repetitive 'أولاً/ثانياً/ثالثاً' unless truly needed).\n"
            . "Do not sound like a template. Each paragraph should add a distinct, useful point.\n"
            . "Use natural lexical variation and avoid repeating the same adjectives and sentence patterns.\n"
            . "Keep claims factual, balanced, and credible; avoid exaggerated promises.\n"
            . "Maintain brand voice and audience-fit, while keeping readability and clarity as top priorities.";
    }

    private function buildCompetitorGuidanceBlock(array $product): string
    {
        $insights = is_array($product['competitor_insights'] ?? null) ? (array) $product['competitor_insights'] : [];
        if ($insights === []) {
            return '';
        }

        return "Use competitor_insights to outperform top 10 results in structure and depth.\n"
            . "You may borrow topical ideas and heading patterns, but never copy text verbatim.\n"
            . "Never mention competitor brands or competitor store details.\n"
            . "Keep all output faithful to merchant brand/context and product data only.\n"
            . "Extract heading intent from common_h2_patterns/top_pages_analysis and convert it into better original headings.\n"
            . "Include at least 4 useful <h3> subheadings distributed across the main sections when competitor_insights is present.\n"
            . "Create stronger, clearer metadata by comparing competitor title/description patterns, then improving clarity, intent match, and CTR potential.";
    }

    private function buildCompetitorInputBlock(array $product): string
    {
        $insights = is_array($product['competitor_insights'] ?? null) ? (array) $product['competitor_insights'] : [];
        if ($insights === []) {
            return '';
        }

        $summary = [
            'keyword' => (string) ($insights['keyword'] ?? ''),
            'country' => (string) ($insights['country'] ?? ''),
            'language' => (string) ($insights['language'] ?? ''),
            'device' => (string) ($insights['device'] ?? ''),
            'common_h2_patterns' => array_values(array_slice((array) ($insights['common_h2_patterns'] ?? []), 0, 12)),
            'competitor_titles_sample' => array_values(array_slice((array) ($insights['competitor_titles_sample'] ?? []), 0, 10)),
            'competitor_descriptions_sample' => array_values(array_slice((array) ($insights['competitor_descriptions_sample'] ?? []), 0, 10)),
            'top_results' => array_values(array_slice((array) ($insights['top_results'] ?? []), 0, 10)),
            'top_pages_analysis' => array_values(array_slice((array) ($insights['top_pages_analysis'] ?? []), 0, 10)),
        ];

        return "This block is mandatory input for writing decisions when present.\n"
            . "Use it to compare, identify gaps, and produce better original structure + metadata.\n"
            . "Do not copy competitor text verbatim.\n"
            . json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /**
     * @return array<int, array{url:string,title:string,type:string,score:int}>
     */
    private function pickRelevantSitemapLinks(array $product, array $settings, int $max = 3): array
    {
        $rows = is_array($settings['sitemap_links_cache'] ?? null)
            ? (array) $settings['sitemap_links_cache']
            : [];

        if ($rows === [] || $max <= 0) {
            return [];
        }

        $tokens = $this->extractKeywordsFromProduct($product);
        $currentUrl = trim((string) ($product['url'] ?? ($product['urls']['customer'] ?? '')));
        $scored = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $url = trim((string) ($row['url'] ?? ''));
            if ($url === '' || ($currentUrl !== '' && strcasecmp($currentUrl, $url) === 0)) {
                continue;
            }

            $type = trim((string) ($row['type'] ?? 'page'));
            if (!in_array($type, ['product', 'category', 'brand', 'page'], true)) {
                $type = 'page';
            }
            $title = trim((string) ($row['title'] ?? ''));
            $haystack = $this->normalizeComparableText($title . ' ' . $url);

            $score = 0;
            if ($type === 'category') {
                $score += 20;
            } elseif ($type === 'brand') {
                $score += 18;
            } elseif ($type === 'product') {
                $score += 12;
            } else {
                $score += 6;
            }

            foreach ($tokens as $token) {
                $token = is_string($token) ? trim($token) : (string) $token;
                if ($token !== '' && $this->containsText($haystack, $token)) {
                    $length = function_exists('mb_strlen') ? mb_strlen($token, 'UTF-8') : strlen($token);
                    $score += $length >= 5 ? 8 : 4;
                }
            }

            if ($score <= 6) {
                continue;
            }

            $scored[] = [
                'url' => $url,
                'title' => $title,
                'type' => $type,
                'score' => $score,
            ];
        }

        if ($scored === []) {
            return [];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $deduped = [];
        $seen = [];
        foreach ($scored as $row) {
            $key = strtolower($row['url']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $deduped[] = $row;
            if (count($deduped) >= $max) {
                break;
            }
        }

        return $deduped;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeywordsFromProduct(array $product): array
    {
        $source = trim(implode(' ', [
            (string) ($product['name'] ?? ''),
            strip_tags((string) ($product['description'] ?? '')),
            (string) ($product['metadata']['title'] ?? ''),
            (string) ($product['metadata']['description'] ?? ''),
        ]));

        if ($source === '') {
            return [];
        }

        $source = $this->normalizeComparableText($source);
        $parts = preg_split('/\s+/u', $source) ?: [];
        $stopWords = [
            'the', 'and', 'for', 'with', 'this', 'that', 'from', 'your',
            'على', 'من', 'إلى', 'في', 'عن', 'هذا', 'هذه', 'مع', 'أو', 'و', 'ثم', 'الى'
        ];
        $stop = array_fill_keys($stopWords, true);
        $tokens = [];

        foreach ($parts as $part) {
            $token = trim($part);
            $length = function_exists('mb_strlen') ? mb_strlen($token, 'UTF-8') : strlen($token);
            if ($token === '' || $length < 3) {
                continue;
            }
            // Skip pure numeric tokens (e.g. sizes like 36/110) to avoid noisy matching.
            if (preg_match('/^\d+$/', $token) === 1) {
                continue;
            }
            if (isset($stop[$token])) {
                continue;
            }
            $tokens[$token] = true;
            if (count($tokens) >= 18) {
                break;
            }
        }

        return array_keys($tokens);
    }

    private function normalizeComparableText(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }
        $value = preg_replace('/[^\p{L}\p{N}\s\-\/]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    private function buildInternalLinksPromptBlock(array $product, array $settings, bool $descriptionMode): string
    {
        if (!$descriptionMode) {
            return '';
        }

        $links = $this->pickRelevantSitemapLinks($product, $settings, 220);
        if ($links === []) {
            return 'If no internal links list is provided, skip internal links and continue normally.';
        }

        $byType = $this->groupLinksByType($links);
        $categoryLinks = array_slice($byType['category'], 0, 8);
        $brandLinks = array_slice($byType['brand'], 0, 8);
        $productLinks = array_slice($byType['product'], 0, 16);
        $pageLinks = array_slice($byType['page'], 0, 8);

        $rows = [];
        foreach ($categoryLinks as $index => $link) {
            $label = $link['title'] !== '' ? $link['title'] : ('Category Link ' . ($index + 1));
            $rows[] = '- [CATEGORY] ' . $label . ' | ' . $link['url'];
        }
        foreach ($brandLinks as $index => $link) {
            $label = $link['title'] !== '' ? $link['title'] : ('Brand Link ' . ($index + 1));
            $rows[] = '- [BRAND] ' . $label . ' | ' . $link['url'];
        }
        foreach ($productLinks as $index => $link) {
            $label = $link['title'] !== '' ? $link['title'] : ('Product Link ' . ($index + 1));
            $rows[] = '- [PRODUCT] ' . $label . ' | ' . $link['url'];
        }
        foreach ($pageLinks as $index => $link) {
            $label = $link['title'] !== '' ? $link['title'] : ('Page Link ' . ($index + 1));
            $rows[] = '- [PAGE] ' . $label . ' | ' . $link['url'];
        }

        $requirements = [];
        if ($categoryLinks !== []) {
            $requirements[] = '- Include at least 1 category internal link.';
        }
        if ($brandLinks !== []) {
            $requirements[] = '- Include at least 1 brand internal link.';
        }
        if (count($productLinks) >= 2) {
            $requirements[] = '- Include 2-3 product internal links.';
        } elseif (count($productLinks) === 1) {
            $requirements[] = '- Include exactly 1 product internal link (only one candidate exists).';
        }
        if ($requirements === []) {
            $requirements[] = '- Include relevant internal links from the list naturally.';
        }

        return "Use internal links from this list only (never external links, never duplicate the same link):\n"
            . implode("\n", $rows)
            . "\nInternal link composition rules:\n"
            . implode("\n", $requirements)
            . "\nPlace links naturally in suitable sections using HTML anchor tags."
            . "\nNever link to out-of-stock/unavailable products."
            . "\nIf product availability is unknown, prefer category/brand/page links instead of product links.";
    }

    /**
     * @param array<int, array{url:string,title:string,type:string,score:int}> $links
     * @return array{category:array<int, array{url:string,title:string,type:string,score:int}>,brand:array<int, array{url:string,title:string,type:string,score:int}>,product:array<int, array{url:string,title:string,type:string,score:int}>,page:array<int, array{url:string,title:string,type:string,score:int}>}
     */
    private function groupLinksByType(array $links): array
    {
        $result = [
            'category' => [],
            'brand' => [],
            'product' => [],
            'page' => [],
        ];

        foreach ($links as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = (string) ($row['type'] ?? 'page');
            if (!array_key_exists($type, $result)) {
                $type = 'page';
            }
            $result[$type][] = $row;
        }

        return $result;
    }

    private function extractText(array $body): string
    {
        if (isset($body['output_text']) && is_string($body['output_text'])) {
            return trim($body['output_text']);
        }

        $chunks = [];

        foreach (($body['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (isset($content['text']) && is_string($content['text'])) {
                    $chunks[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $chunks));
    }

    private function ensureHtmlDescription(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (preg_match('/<(h2|p|ul|li|strong)\b/i', $value) === 1) {
            return $value;
        }

        $lines = preg_split('/\R/u', $value) ?: [];
        $html = [];
        $inList = false;

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                continue;
            }

            if (preg_match('/^H2\s*[:\-]\s*(.+)$/iu', $line, $matches) === 1 || preg_match('/^##\s*(.+)$/u', $line, $matches) === 1) {
                if ($inList) {
                    $html[] = '</ul>';
                    $inList = false;
                }
                $html[] = '<h2>' . $this->escapeHtml(trim((string) $matches[1])) . '</h2>';
                continue;
            }

            if (preg_match('/^[-*•]\s+(.+)$/u', $line, $matches) === 1 || preg_match('/^\d+[\.\)\-]\s+(.+)$/u', $line, $matches) === 1) {
                if (!$inList) {
                    $html[] = '<ul>';
                    $inList = true;
                }
                $html[] = '<li>' . $this->escapeHtml(trim((string) $matches[1])) . '</li>';
                continue;
            }

            if ($inList) {
                $html[] = '</ul>';
                $inList = false;
            }

            $html[] = '<p>' . $this->escapeHtml($line) . '</p>';
        }

        if ($inList) {
            $html[] = '</ul>';
        }

        return trim(implode("\n", $html));
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function containsText(mixed $haystack, mixed $needle): bool
    {
        $haystackText = is_string($haystack) ? $haystack : (string) $haystack;
        $needleText = is_string($needle) ? $needle : (string) $needle;
        if ($needleText === '') {
            return false;
        }

        return strpos($haystackText, $needleText) !== false;
    }

    private function buildSeoSlug(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        $value = preg_replace('/[^\p{L}\p{N}\s\-]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[\s_]+/u', '-', trim($value)) ?? $value;
        $value = preg_replace('/-+/u', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $this->limitText($value, 90);
    }
}
