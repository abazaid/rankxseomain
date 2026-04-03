<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;
use RuntimeException;

final class DataForSeoClient
{
    private const BASE_URL = 'https://api.dataforseo.com/v3';

    private HttpClient $http;
    private string $login;
    private string $password;
    /** @var array<int, array<string, mixed>> */
    private array $usageEvents = [];

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?? new HttpClient();
        $this->login = (string) Config::get('DATAFORSEO_LOGIN', Config::get('DATAFORSEO_USERNAME', ''));
        $this->password = (string) Config::get('DATAFORSEO_PASSWORD', '');
    }

    public function keywordOverview(string $keyword, string $device = 'desktop', string $country = 'sa', ?string $language = null): array
    {
        $this->usageEvents = [];
        $this->assertCredentials();

        $normalizedKeyword = trim($keyword);
        if ($normalizedKeyword === '') {
            throw new RuntimeException('Keyword is required.');
        }

        $normalizedDevice = $this->normalizeDevice($device);
        $normalizedCountry = $this->normalizeCountry($country);
        $normalizedLanguage = $this->normalizeKeywordLanguage($language) ?? 'ar';
        $targetingOptions = $this->buildKeywordTargetingOptions($normalizedCountry, $normalizedLanguage);

        $volumeTaskPayload = [[
            'keywords' => [$normalizedKeyword],
            'search_partners' => false,
        ]];
        $volumeTaskPayload[0] = array_merge($volumeTaskPayload[0], $targetingOptions);
        $volumeTask = $this->post('/keywords_data/google_ads/search_volume/live', $volumeTaskPayload);

        $keywordMetrics = $this->extractKeywordMetrics($volumeTask, $normalizedKeyword);

        $serpTaskPayload = [[
            'keyword' => $normalizedKeyword,
            'device' => $normalizedDevice,
            'os' => $normalizedDevice === 'mobile' ? 'android' : 'windows',
            'depth' => 10,
        ]];
        $serpTaskPayload[0] = array_merge($serpTaskPayload[0], $targetingOptions);
        $serpTask = $this->post('/serp/google/organic/live/advanced', $serpTaskPayload);

        $serp = $this->extractSerp($serpTask);

        // Prefer DataForSEO Labs related keywords endpoint for richer/faster related terms.
        $relatedKeywords = [];
        $keywordSuggestions = [];
        try {
            $labsRelatedPayload = [[
                'keyword' => $normalizedKeyword,
                'limit' => 50,
            ]];
            $labsRelatedPayload[0] = array_merge($labsRelatedPayload[0], $targetingOptions);
            $labsRelatedTask = $this->post('/dataforseo_labs/google/related_keywords/live', $labsRelatedPayload);
            $relatedKeywords = $this->extractRelatedKeywords($labsRelatedTask);
        } catch (\Throwable $exception) {
            // Fallback to Google Ads keywords_for_keywords endpoint if labs response is unavailable.
            $relatedTaskPayload = [[
                'keywords' => [$normalizedKeyword],
                'limit' => 50,
                'sort_by' => 'relevance',
            ]];
            $relatedTaskPayload[0] = array_merge($relatedTaskPayload[0], $targetingOptions);
            $relatedTask = $this->post('/keywords_data/google_ads/keywords_for_keywords/live', $relatedTaskPayload);
            $relatedKeywords = $this->extractRelatedKeywords($relatedTask);
        }

        try {
            $suggestionsPayload = [[
                'keyword' => $normalizedKeyword,
                'limit' => 50,
            ]];
            $suggestionsPayload[0] = array_merge($suggestionsPayload[0], $targetingOptions);
            $suggestionsTask = $this->post('/dataforseo_labs/google/keyword_suggestions/live', $suggestionsPayload);
            $keywordSuggestions = $this->extractKeywordSuggestions($suggestionsTask);
        } catch (\Throwable $exception) {
            $keywordSuggestions = [];
        }

        return [
            'keyword' => $normalizedKeyword,
            'country' => $normalizedCountry,
            'country_name' => $this->countryLabel($normalizedCountry),
            'language' => $normalizedLanguage,
            'language_name' => $this->languageLabel($normalizedLanguage),
            'device' => $normalizedDevice,
            'metrics' => $keywordMetrics['metrics'],
            'trend' => $keywordMetrics['trend'],
            'serp' => $serp,
            'related_keywords' => $relatedKeywords,
            'keyword_suggestions' => $keywordSuggestions,
            '_usage' => $this->usageSummary(),
            'fetched_at' => date(DATE_ATOM),
        ];
    }

    public function domainOverview(string $domain, string $device = 'desktop'): array
    {
        $this->usageEvents = [];
        $this->assertCredentials();

        $normalizedDomain = $this->normalizeDomain($domain);
        if ($normalizedDomain === '') {
            throw new RuntimeException('Domain is required.');
        }

        $normalizedDevice = $this->normalizeDevice($device);

        $overviewTask = $this->post('/dataforseo_labs/google/domain_rank_overview/live', [[
            'target' => $normalizedDomain,
            'location_name' => 'Saudi Arabia',
            'language_name' => 'Arabic',
            'item_types' => ['organic', 'paid'],
        ]]);

        $competitorsTask = $this->post('/dataforseo_labs/google/competitors_domain/live', [
            $this->buildDomainCompetitorsPayload($normalizedDomain, $normalizedDevice, 'Arabic'),
        ]);
        $competitors = $this->extractDomainCompetitors($competitorsTask, $normalizedDomain);
        if ($competitors === []) {
            $competitorsTask = $this->post('/dataforseo_labs/google/competitors_domain/live', [
                $this->buildDomainCompetitorsPayload($normalizedDomain, $normalizedDevice, 'English'),
            ]);
            $competitors = $this->extractDomainCompetitors($competitorsTask, $normalizedDomain);
        }

        $keywordsTask = $this->post('/dataforseo_labs/google/ranked_keywords/live', [
            $this->buildDomainKeywordsPayload($normalizedDomain, $normalizedDevice, 100, 'Arabic'),
        ]);
        $allKeywords = $this->extractDomainKeywords($keywordsTask);
        if ($allKeywords === []) {
            $keywordsTask = $this->post('/dataforseo_labs/google/ranked_keywords/live', [
                $this->buildDomainKeywordsPayload($normalizedDomain, $normalizedDevice, 100, 'English'),
            ]);
            $allKeywords = $this->extractDomainKeywords($keywordsTask);
        }

        return [
            'domain' => $normalizedDomain,
            'country' => 'sa',
            'country_name' => 'Saudi Arabia',
            'device' => $normalizedDevice,
            'overview' => $this->extractDomainOverview($overviewTask),
            'competitors' => $competitors,
            'top_keywords' => array_slice($allKeywords, 0, 12),
            'all_keywords' => $allKeywords,
            '_usage' => $this->usageSummary(),
            'fetched_at' => date(DATE_ATOM),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     */
    private function post(string $path, array $payload): array
    {
        $auth = base64_encode($this->login . ':' . $this->password);
        $response = $this->http->post(
            self::BASE_URL . $path,
            $payload,
            [
                'Authorization' => 'Basic ' . $auth,
            ]
        );

        $body = (array) ($response['body'] ?? []);
        $statusCode = (int) ($body['status_code'] ?? 0);

        if ($statusCode !== 20000) {
            $message = (string) ($body['status_message'] ?? 'DataForSEO request failed.');
            throw new RuntimeException($message);
        }

        $this->usageEvents[] = [
            'endpoint' => $path,
            'cost_usd' => $this->extractResponseCostUsd($body),
        ];

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function usageSummary(): array
    {
        $requestsCount = count($this->usageEvents);
        $totalCost = 0.0;
        $byEndpoint = [];

        foreach ($this->usageEvents as $event) {
            $endpoint = (string) ($event['endpoint'] ?? 'unknown');
            $cost = (float) ($event['cost_usd'] ?? 0);
            $totalCost += $cost;

            if (!isset($byEndpoint[$endpoint])) {
                $byEndpoint[$endpoint] = [
                    'endpoint' => $endpoint,
                    'requests_count' => 0,
                    'total_cost_usd' => 0.0,
                ];
            }

            $byEndpoint[$endpoint]['requests_count']++;
            $byEndpoint[$endpoint]['total_cost_usd'] += $cost;
        }

        return [
            'provider' => 'dataforseo',
            'requests_count' => $requestsCount,
            'total_cost_usd' => round($totalCost, 6),
            'by_endpoint' => array_values(array_map(static function (array $row): array {
                $row['total_cost_usd'] = round((float) ($row['total_cost_usd'] ?? 0), 6);
                return $row;
            }, $byEndpoint)),
        ];
    }

    private function extractResponseCostUsd(array $body): float
    {
        $totalCost = 0.0;
        $tasks = array_values(array_filter((array) ($body['tasks'] ?? []), 'is_array'));

        foreach ($tasks as $task) {
            $totalCost += (float) ($task['cost'] ?? 0);
        }

        if ($totalCost <= 0.0) {
            $totalCost = (float) ($body['cost'] ?? 0);
        }

        return round($totalCost, 6);
    }

    private function assertCredentials(): void
    {
        if ($this->login === '' || $this->password === '') {
            throw new RuntimeException('DataForSEO credentials are missing. Add DATAFORSEO_LOGIN and DATAFORSEO_PASSWORD in .env');
        }
    }

    private function normalizeDevice(string $device): string
    {
        return $device === 'mobile' ? 'mobile' : 'desktop';
    }

    private function normalizeCountry(string $country): string
    {
        return strtolower(trim($country)) === 'sa' ? 'sa' : 'sa';
    }

    private function normalizeKeywordLanguage(?string $language): ?string
    {
        if ($language === null) {
            return null;
        }

        $value = strtolower(trim($language));
        if ($value === '' || $value === 'auto') {
            return null;
        }

        return in_array($value, ['ar', 'en'], true) ? $value : null;
    }

    /**
     * @return array<string, string>
     */
    private function buildKeywordTargetingOptions(string $country, ?string $language): array
    {
        $payload = [
            'location_code' => $country === 'sa' ? 2682 : 2682,
        ];

        if ($language === 'ar') {
            $payload['language_code'] = 'ar';
        } elseif ($language === 'en') {
            $payload['language_code'] = 'en';
        }

        return $payload;
    }

    private function countryLabel(string $country): string
    {
        return $country === 'sa' ? 'Saudi Arabia' : strtoupper($country);
    }

    private function languageLabel(?string $language): string
    {
        return match ($language) {
            'ar' => 'Arabic',
            'en' => 'English',
            default => 'Arabic',
        };
    }

    private function normalizeDomain(string $domain): string
    {
        $value = trim($domain);
        if ($value === '') {
            return '';
        }

        if (!str_contains($value, '://')) {
            $value = 'https://' . $value;
        }

        $host = (string) parse_url($value, PHP_URL_HOST);
        $host = strtolower(trim($host));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $host = trim($host, '.');

        if ($host === '' || !str_contains($host, '.')) {
            return '';
        }

        return $host;
    }

    private function extractKeywordMetrics(array $response, string $keyword): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $resultSet = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($resultSet[0] ?? []);

        // Endpoint may return the keyword row directly in result[0],
        // or nested under result[0].items[0] in some responses.
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));
        $item = $items !== [] ? (array) ($items[0] ?? []) : $firstResult;

        if ($item === []) {
            return [
                'metrics' => [
                    'search_volume' => 0,
                    'competition' => 0,
                    'competition_level' => null,
                    'cpc' => 0.0,
                    'low_bid' => 0.0,
                    'high_bid' => 0.0,
                    'monthly_searches_count' => 0,
                ],
                'trend' => [],
            ];
        }

        $monthly = array_values(array_map(static function (array $row): array {
            return [
                'year' => (int) ($row['year'] ?? 0),
                'month' => (int) ($row['month'] ?? 0),
                'search_volume' => (int) ($row['search_volume'] ?? 0),
            ];
        }, array_filter((array) ($item['monthly_searches'] ?? []), static fn ($row): bool => is_array($row))));

        usort($monthly, static function (array $a, array $b): int {
            return [$a['year'], $a['month']] <=> [$b['year'], $b['month']];
        });

        return [
            'metrics' => [
                'keyword' => (string) ($item['keyword'] ?? $keyword),
                'search_volume' => (int) ($item['search_volume'] ?? 0),
                'competition' => (float) ($item['competition_index'] ?? 0),
                'competition_level' => isset($item['competition']) ? (string) $item['competition'] : null,
                'cpc' => (float) ($item['cpc'] ?? 0),
                'low_bid' => (float) ($item['low_top_of_page_bid'] ?? 0),
                'high_bid' => (float) ($item['high_top_of_page_bid'] ?? 0),
                'monthly_searches_count' => count($monthly),
            ],
            'trend' => $monthly,
        ];
    }

    private function extractSerp(array $response): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = (array) ($task['result'] ?? []);
        $firstResult = (array) ($results[0] ?? []);
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), static function ($item): bool {
            return is_array($item) && in_array((string) ($item['type'] ?? ''), ['organic', 'answer_box', 'people_also_ask', 'images'], true);
        }));

        $mapped = array_map(static function (array $item): array {
            return [
                'type' => (string) ($item['type'] ?? ''),
                'rank_group' => (int) ($item['rank_group'] ?? 0),
                'rank_absolute' => (int) ($item['rank_absolute'] ?? 0),
                'title' => (string) ($item['title'] ?? ''),
                'url' => (string) ($item['url'] ?? ''),
                'domain' => (string) ($item['domain'] ?? ''),
                'description' => (string) ($item['description'] ?? ''),
            ];
        }, $items);

        return [
            'se_type' => (string) ($firstResult['se_type'] ?? 'google'),
            'location_name' => (string) ($firstResult['location_name'] ?? 'Saudi Arabia'),
            'language_name' => (string) ($firstResult['language_name'] ?? ''),
            'total_count' => (int) ($firstResult['items_count'] ?? count($mapped)),
            'items' => array_slice($mapped, 0, 10),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractRelatedKeywords(array $response): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($results[0] ?? []);
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));

        $mapped = array_map(static function (array $item): array {
            // Works with both:
            // 1) keywords_data/google_ads/keywords_for_keywords/live
            // 2) dataforseo_labs/google/related_keywords/live
            $keywordData = is_array($item['keyword_data'] ?? null) ? (array) $item['keyword_data'] : [];
            $keywordInfo = is_array($keywordData['keyword_info'] ?? null) ? (array) $keywordData['keyword_info'] : [];
            $keywordValue = (string) ($item['keyword'] ?? ($keywordData['keyword'] ?? ''));
            $searchVolume = (int) ($item['search_volume'] ?? ($keywordInfo['search_volume'] ?? 0));
            $competitionIndex = (float) ($item['competition_index'] ?? ($item['competition'] ?? ($keywordInfo['competition'] ?? 0)));
            $competitionLevel = (string) ($item['competition'] ?? ($keywordInfo['competition_level'] ?? ''));
            $cpc = (float) ($item['cpc'] ?? ($keywordInfo['cpc'] ?? 0));
            $monthly = $item['monthly_searches'] ?? ($keywordInfo['monthly_searches'] ?? []);

            return [
                'keyword' => $keywordValue,
                'search_volume' => $searchVolume,
                'competition' => $competitionIndex,
                'competition_level' => $competitionLevel,
                'cpc' => $cpc,
                'monthly_searches' => is_array($monthly) ? $monthly : [],
            ];
        }, $items);

        $mapped = array_values(array_filter($mapped, static function (array $row): bool {
            return trim((string) ($row['keyword'] ?? '')) !== '';
        }));

        return array_slice($mapped, 0, 30);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractKeywordSuggestions(array $response): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($results[0] ?? []);
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));

        $mapped = array_map(static function (array $item): array {
            $keywordInfo = is_array($item['keyword_info'] ?? null) ? (array) $item['keyword_info'] : [];
            $keywordValue = (string) ($item['keyword'] ?? '');
            $searchVolume = (int) ($keywordInfo['search_volume'] ?? 0);
            $competitionIndex = (float) ($keywordInfo['competition'] ?? 0);
            $competitionLevel = (string) ($keywordInfo['competition_level'] ?? '');
            $cpc = (float) ($keywordInfo['cpc'] ?? 0);

            return [
                'keyword' => $keywordValue,
                'search_volume' => $searchVolume,
                'competition' => $competitionIndex,
                'competition_level' => $competitionLevel,
                'cpc' => $cpc,
            ];
        }, $items);

        $mapped = array_values(array_filter($mapped, static function (array $row): bool {
            return trim((string) ($row['keyword'] ?? '')) !== '';
        }));

        return array_slice($mapped, 0, 50);
    }

    private function extractDomainOverview(array $response): array
    {
        $firstResult = $this->extractFirstResultItem($response);
        $metrics = (array) ($firstResult['metrics'] ?? []);
        $organic = (array) ($metrics['organic'] ?? []);
        $paid = (array) ($metrics['paid'] ?? []);

        return [
            'organic' => [
                'keywords_count' => (int) ($organic['count'] ?? 0),
                'traffic' => (float) ($organic['etv'] ?? 0),
                'traffic_cost' => (float) ($organic['estimated_paid_traffic_cost'] ?? 0),
                'new' => (int) ($organic['is_new'] ?? 0),
                'up' => (int) ($organic['is_up'] ?? 0),
                'down' => (int) ($organic['is_down'] ?? 0),
                'lost' => (int) ($organic['is_lost'] ?? 0),
                'positions' => [
                    'top_3' => (int) (($organic['pos_1'] ?? 0) + ($organic['pos_2_3'] ?? 0)),
                    'top_10' => (int) (($organic['pos_1'] ?? 0) + ($organic['pos_2_3'] ?? 0) + ($organic['pos_4_10'] ?? 0)),
                    'top_20' => (int) (
                        ($organic['pos_1'] ?? 0) +
                        ($organic['pos_2_3'] ?? 0) +
                        ($organic['pos_4_10'] ?? 0) +
                        ($organic['pos_11_20'] ?? 0)
                    ),
                    'top_100' => (int) (
                        ($organic['pos_1'] ?? 0) +
                        ($organic['pos_2_3'] ?? 0) +
                        ($organic['pos_4_10'] ?? 0) +
                        ($organic['pos_11_20'] ?? 0) +
                        ($organic['pos_21_30'] ?? 0) +
                        ($organic['pos_31_40'] ?? 0) +
                        ($organic['pos_41_50'] ?? 0) +
                        ($organic['pos_51_60'] ?? 0) +
                        ($organic['pos_61_70'] ?? 0) +
                        ($organic['pos_71_80'] ?? 0) +
                        ($organic['pos_81_90'] ?? 0) +
                        ($organic['pos_91_100'] ?? 0)
                    ),
                ],
            ],
            'paid' => [
                'keywords_count' => (int) ($paid['count'] ?? 0),
                'traffic' => (float) ($paid['etv'] ?? 0),
                'traffic_cost' => (float) ($paid['estimated_paid_traffic_cost'] ?? 0),
                'new' => (int) ($paid['is_new'] ?? 0),
                'up' => (int) ($paid['is_up'] ?? 0),
                'down' => (int) ($paid['is_down'] ?? 0),
                'lost' => (int) ($paid['is_lost'] ?? 0),
            ],
        ];
    }

    private function extractDomainCompetitors(array $response, string $targetDomain): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($results[0] ?? []);
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));
        if ($items === [] && $firstResult !== []) {
            // Some responses can return the competitor rows directly in result[0].
            $items = [$firstResult];
        }

        $normalizedTarget = $this->normalizeComparableDomain($targetDomain);
        $mapped = array_values(array_filter(array_map(static function (array $item): array {
            $fullMetrics = (array) ($item['full_domain_metrics'] ?? []);
            $organic = (array) ($fullMetrics['organic'] ?? []);

            return [
                'domain' => (string) ($item['domain'] ?? ''),
                'intersections' => (int) ($item['intersections'] ?? 0),
                'avg_position' => (float) ($item['avg_position'] ?? 0),
                'organic_keywords' => (int) ($organic['count'] ?? 0),
                'organic_traffic' => (float) ($organic['etv'] ?? 0),
                'organic_cost' => (float) ($organic['estimated_paid_traffic_cost'] ?? 0),
            ];
        }, $items), static function (array $item) use ($normalizedTarget): bool {
            $candidate = strtolower(trim((string) ($item['domain'] ?? '')));
            if ($candidate === '') {
                return false;
            }

            $normalizedCandidate = preg_replace('/^www\./', '', trim($candidate, '.')) ?? $candidate;
            if ($normalizedTarget !== '' && $normalizedCandidate === $normalizedTarget) {
                return false;
            }

            return true;
        }));

        return array_slice($mapped, 0, 20);
    }

    private function extractDomainKeywords(array $response): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($results[0] ?? []);
        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));
        if ($items === [] && $firstResult !== []) {
            // Some responses can return a single keyword row directly in result[0].
            $items = [$firstResult];
        }

        $mapped = array_map(static function (array $item): array {
            $keywordData = (array) ($item['keyword_data'] ?? []);
            $keywordInfo = (array) ($keywordData['keyword_info'] ?? []);
            $rankedSerp = (array) ($item['ranked_serp_element'] ?? []);
            $serpItem = (array) ($rankedSerp['serp_item'] ?? []);
            $keyword = (string) ($keywordData['keyword'] ?? ($item['keyword'] ?? ''));
            $position = (int) ($serpItem['rank_group'] ?? ($item['rank_group'] ?? ($item['rank_absolute'] ?? 0)));
            $searchVolume = (int) ($keywordInfo['search_volume'] ?? ($item['search_volume'] ?? 0));
            $cpc = (float) ($keywordInfo['cpc'] ?? ($item['cpc'] ?? 0));
            $competition = (float) ($keywordInfo['competition'] ?? ($item['competition'] ?? 0));
            $intent = (string) (($keywordData['search_intent_info']['main_intent'] ?? ($item['search_intent'] ?? $item['intent'] ?? '')));

            return [
                'keyword' => $keyword,
                'position' => $position,
                'search_volume' => $searchVolume,
                'cpc' => $cpc,
                'competition' => $competition,
                'intent' => $intent,
            ];
        }, $items);

        $mapped = array_values(array_filter($mapped, static function (array $row): bool {
            return trim((string) ($row['keyword'] ?? '')) !== '';
        }));

        usort($mapped, static function (array $a, array $b): int {
            return ($a['position'] ?? PHP_INT_MAX) <=> ($b['position'] ?? PHP_INT_MAX);
        });

        return $mapped;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDomainCompetitorsPayload(string $domain, string $device, string $languageName): array
    {
        return [
            'target' => $domain,
            'location_name' => 'Saudi Arabia',
            'language_name' => $languageName,
            'exclude_top_domains' => false,
            'max_rank_group' => $device === 'mobile' ? 20 : 10,
            'limit' => 20,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDomainKeywordsPayload(string $domain, string $device, int $limit, string $languageName): array
    {
        return [
            'target' => $domain,
            'location_name' => 'Saudi Arabia',
            'language_name' => $languageName,
            'limit' => max(12, min(200, $limit)),
            'order_by' => ['keyword_data.keyword_info.search_volume,desc'],
            'filters' => [
                ['ranked_serp_element.serp_item.rank_group', '<=', $device === 'mobile' ? 50 : 30],
                'and',
                ['ranked_serp_element.serp_item.type', '<>', 'paid'],
            ],
        ];
    }

    private function extractFirstResultItem(array $response): array
    {
        $tasks = (array) ($response['tasks'] ?? []);
        $task = (array) ($tasks[0] ?? []);
        $results = array_values(array_filter((array) ($task['result'] ?? []), 'is_array'));
        $firstResult = (array) ($results[0] ?? []);

        $items = array_values(array_filter((array) ($firstResult['items'] ?? []), 'is_array'));
        if ($items !== []) {
            $firstItem = (array) ($items[0] ?? []);
            if ($firstItem !== []) {
                return $firstItem;
            }
        }

        return $firstResult;
    }

    private function normalizeComparableDomain(string $domain): string
    {
        $value = strtolower(trim($domain));
        if ($value === '') {
            return '';
        }

        $value = trim($value, '.');
        return preg_replace('/^www\./', '', $value) ?? $value;
    }
}
