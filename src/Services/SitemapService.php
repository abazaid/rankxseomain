<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SitemapService
{
    private const MAX_SITEMAPS = 8;
    private const MAX_URLS = 1500;

    public function __construct(
        private readonly HttpClient $httpClient = new HttpClient()
    ) {
    }

    /**
     * @return array{
     *   sitemap_url:string,
     *   links:array<int,array{url:string,title:string,type:string}>,
     *   links_count:int,
     *   fetched_at:string
     * }
     */
    public function fetchAndParse(string $sitemapUrl): array
    {
        $normalized = $this->normalizeSitemapUrl($sitemapUrl);
        if ($normalized === '') {
            throw new RuntimeException('رابط السايت ماب غير صالح.');
        }

        $queue = $this->buildInitialSitemapQueue($normalized);
        $visited = [];
        $urlRows = [];
        $baseHost = (string) parse_url($normalized, PHP_URL_HOST);
        $sitemapCounter = 0;
        $lastDownloadError = '';
        $parsedAtLeastOneSitemap = false;

        while ($queue !== [] && $sitemapCounter < self::MAX_SITEMAPS && count($urlRows) < self::MAX_URLS) {
            $current = array_shift($queue);
            if (!is_string($current) || $current === '' || isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;
            $sitemapCounter++;

            try {
                $xml = $this->downloadXml($current, $normalized);
                [$childSitemaps, $urls] = $this->parseSitemapXml($xml);
                $parsedAtLeastOneSitemap = true;
            } catch (\Throwable $exception) {
                $lastDownloadError = $exception->getMessage();
                continue;
            }

            foreach ($childSitemaps as $child) {
                if (count($visited) >= self::MAX_SITEMAPS) {
                    break;
                }
                $child = $this->normalizeSitemapUrl($this->resolveRelativeUrl($current, $child));
                if ($child !== '' && !isset($visited[$child])) {
                    $queue[] = $child;
                }
            }

            foreach ($urls as $url) {
                if (count($urlRows) >= self::MAX_URLS) {
                    break;
                }
                $normalizedUrl = $this->normalizeSiteUrl($url);
                if ($normalizedUrl === '') {
                    continue;
                }

                $host = (string) parse_url($normalizedUrl, PHP_URL_HOST);
                if ($baseHost !== '' && $host !== '' && strcasecmp($host, $baseHost) !== 0) {
                    continue;
                }

                $urlRows[$normalizedUrl] = [
                    'url' => $normalizedUrl,
                    'title' => $this->buildTitleFromUrl($normalizedUrl),
                    'type' => $this->inferTypeFromUrl($normalizedUrl),
                ];
            }
        }

        if (!$parsedAtLeastOneSitemap && $urlRows === []) {
            throw new RuntimeException($lastDownloadError !== '' ? $lastDownloadError : 'تعذر قراءة السايت ماب من أي رابط متاح.');
        }

        return [
            'sitemap_url' => $normalized,
            'links' => array_values($urlRows),
            'links_count' => count($urlRows),
            'fetched_at' => date(DATE_ATOM),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function buildInitialSitemapQueue(string $normalized): array
    {
        $queue = [$normalized];

        $parts = parse_url($normalized);
        if (!is_array($parts)) {
            return $queue;
        }

        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            return $queue;
        }

        $rootSitemap = $scheme . '://' . $host . '/sitemap.xml';
        if (!in_array($rootSitemap, $queue, true)) {
            $queue[] = $rootSitemap;
        }

        $robotsUrl = $scheme . '://' . $host . '/robots.txt';
        try {
            $robots = $this->downloadText($robotsUrl, $normalized);
            foreach (preg_split('/\R/u', $robots) ?: [] as $line) {
                if (!is_string($line)) {
                    continue;
                }
                $line = trim($line);
                if ($line === '' || stripos($line, 'sitemap:') !== 0) {
                    continue;
                }

                $candidate = trim(substr($line, 8));
                $candidate = $this->normalizeSitemapUrl($this->resolveRelativeUrl($robotsUrl, $candidate));
                if ($candidate !== '' && !in_array($candidate, $queue, true)) {
                    $queue[] = $candidate;
                }
            }
        } catch (\Throwable) {
            // Ignore robots discovery failure and continue.
        }

        return $queue;
    }

    private function downloadXml(string $url, string $baseUrl): string
    {
        $raw = $this->downloadText($url, $baseUrl);
        if (trim($raw) === '') {
            throw new RuntimeException('تعذر قراءة ملف السايت ماب، الرابط لا يحتوي بيانات.');
        }

        return $raw;
    }

    private function downloadText(string $url, ?string $baseUrl = null): string
    {
        $attempts = [
            [
                'Accept' => 'application/xml,text/xml,text/plain,*/*',
                'Accept-Language' => 'ar,en-US;q=0.9,en;q=0.8',
                'User-Agent' => 'Mozilla/5.0 (compatible; RankXSEO-Bot/1.0; +https://app.rankxseo.com)',
            ],
            [
                'Accept' => 'application/xml,text/xml,text/plain,*/*',
                'User-Agent' => 'curl/8.5.0',
            ],
        ];

        if (is_string($baseUrl) && $baseUrl !== '') {
            foreach ($attempts as $index => $headers) {
                $attempts[$index]['Referer'] = $baseUrl;
            }
        }

        $lastError = '';
        $lastStatus = 0;

        foreach ($attempts as $headers) {
            [$status, $body, $error] = $this->curlGet($url, $headers);
            if ($error !== '') {
                $lastError = $error;
                continue;
            }

            $lastStatus = $status;
            if ($status >= 200 && $status < 300) {
                $decodedBody = $this->decodeIfGzip($body);
                if (trim($decodedBody) !== '') {
                    return $decodedBody;
                }
            }
        }

        if ($lastStatus === 403) {
            throw new RuntimeException('تم رفض قراءة السايت ماب (403). جرّب رابط sitemap.xml مباشر أو عطّل حماية البوت لهذا الرابط.');
        }

        if ($lastStatus > 0) {
            throw new RuntimeException('فشل تحميل السايت ماب. HTTP status: ' . $lastStatus);
        }

        if ($lastError !== '') {
            throw new RuntimeException('فشل تحميل السايت ماب: ' . $lastError);
        }

        throw new RuntimeException('تعذر تحميل السايت ماب من الرابط المحدد.');
    }

    /**
     * @param array<string,string> $headers
     * @return array{0:int,1:string,2:string}
     */
    private function curlGet(string $url, array $headers): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return [0, '', 'cURL init failed'];
        }

        $preparedHeaders = [];
        foreach ($headers as $name => $value) {
            $preparedHeaders[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => $preparedHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 6,
            CURLOPT_ENCODING => '',
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = (string) curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return [$status, '', $error !== '' ? $error : 'HTTP request failed'];
        }

        return [$status, (string) $body, ''];
    }

    private function decodeIfGzip(string $body): string
    {
        if ($body === '') {
            return '';
        }

        $isGzip = strlen($body) >= 2 && ord($body[0]) === 0x1f && ord($body[1]) === 0x8b;
        if ($isGzip && function_exists('gzdecode')) {
            $decoded = gzdecode($body);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $body;
    }

    private function resolveRelativeUrl(string $baseUrl, string $candidate): string
    {
        $candidate = trim($candidate);
        if ($candidate === '') {
            return '';
        }

        if (str_contains($candidate, '://')) {
            return $candidate;
        }

        $parts = parse_url($baseUrl);
        if (!is_array($parts)) {
            return $candidate;
        }

        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? '');
        if ($host === '') {
            return $candidate;
        }

        if (str_starts_with($candidate, '/')) {
            return $scheme . '://' . $host . $candidate;
        }

        $basePath = (string) ($parts['path'] ?? '/');
        $baseDir = rtrim(str_replace('\\', '/', dirname($basePath)), '/');
        if ($baseDir === '.') {
            $baseDir = '';
        }

        return $scheme . '://' . $host . ($baseDir !== '' ? $baseDir . '/' : '/') . ltrim($candidate, '/');
    }

    /**
     * @return array{0:array<int,string>,1:array<int,string>}
     */
    private function parseSitemapXml(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        if ($doc === false) {
            throw new RuntimeException('تعذر تحليل XML للسايت ماب.');
        }

        $childSitemaps = [];
        $urls = [];

        $root = strtolower($doc->getName());
        if ($root === 'sitemapindex') {
            foreach ($doc->sitemap as $sitemap) {
                $loc = trim((string) ($sitemap->loc ?? ''));
                if ($loc !== '') {
                    $childSitemaps[] = $loc;
                }
            }
        } elseif ($root === 'urlset') {
            foreach ($doc->url as $url) {
                $loc = trim((string) ($url->loc ?? ''));
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
        } else {
            foreach ($doc->xpath('//loc') ?: [] as $locNode) {
                $loc = trim((string) $locNode);
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
        }

        return [$childSitemaps, $urls];
    }

    private function normalizeSitemapUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return '';
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '') {
            $path = '/sitemap.xml';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? ('?' . $parts['query']) : '';
        return $scheme . '://' . $host . $path . $query;
    }

    private function normalizeSiteUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!str_contains($url, '://')) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return '';
        }

        $path = (string) ($parts['path'] ?? '/');
        $path = preg_replace('~/+~', '/', $path) ?? $path;
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        $query = isset($parts['query']) && $parts['query'] !== '' ? ('?' . $parts['query']) : '';
        return $scheme . '://' . $host . $path . $query;
    }

    private function buildTitleFromUrl(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = trim($path, '/');
        if ($path === '') {
            return 'الصفحة الرئيسية';
        }

        $segments = explode('/', $path);
        $last = (string) end($segments);
        $last = urldecode($last);
        $last = preg_replace('/[-_]+/u', ' ', $last) ?? $last;
        $last = preg_replace('/\s+/u', ' ', $last) ?? $last;
        $last = trim($last);
        if ($last === '') {
            return 'رابط داخلي';
        }

        return $last;
    }

    private function inferTypeFromUrl(string $url): string
    {
        $path = strtolower((string) parse_url($url, PHP_URL_PATH));
        if (str_contains($path, '/p') || preg_match('~/(product|products)/~', $path) === 1) {
            return 'product';
        }
        if (preg_match('~/(category|categories|c)/~', $path) === 1) {
            return 'category';
        }

        return 'page';
    }
}
