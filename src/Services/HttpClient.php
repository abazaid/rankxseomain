<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class HttpClient
{
    public function get(string $url, array $headers = [], int $timeoutSeconds = 30): array
    {
        return $this->request('GET', $url, null, $headers, true, $timeoutSeconds);
    }

    public function post(string $url, ?array $payload = null, array $headers = [], int $timeoutSeconds = 30): array
    {
        return $this->request('POST', $url, $payload, $headers, true, $timeoutSeconds);
    }

    public function put(string $url, ?array $payload = null, array $headers = [], int $timeoutSeconds = 30): array
    {
        return $this->request('PUT', $url, $payload, $headers, true, $timeoutSeconds);
    }

    public function postForm(string $url, array $payload, array $headers = [], int $timeoutSeconds = 30): array
    {
        return $this->request('POST', $url, $payload, $headers, false, $timeoutSeconds);
    }

    private function request(
        string $method,
        string $url,
        ?array $payload,
        array $headers,
        bool $jsonPayload = true,
        int $timeoutSeconds = 30
    ): array
    {
        $timeoutSeconds = max(10, $timeoutSeconds);
        $attempts = 2;
        $lastStatus = 0;
        $lastRawResponse = '';
        $lastCurlError = '';
        $lastDecoded = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new RuntimeException('Unable to initialize HTTP request.');
            }

            $preparedHeaders = [];
            foreach ($headers as $name => $value) {
                $preparedHeaders[] = $name . ': ' . $value;
            }

            if ($payload !== null && $jsonPayload) {
                $preparedHeaders[] = 'Content-Type: application/json';
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $preparedHeaders,
                CURLOPT_TIMEOUT => $timeoutSeconds,
                CURLOPT_CONNECTTIMEOUT => min(20, $timeoutSeconds),
            ]);

            if ($payload !== null) {
                curl_setopt(
                    $ch,
                    CURLOPT_POSTFIELDS,
                    $jsonPayload
                        ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : $payload
                );
            }

            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $error = (string) curl_error($ch);
            curl_close($ch);

            $lastStatus = $status;
            $lastRawResponse = is_string($response) ? $response : '';
            $lastCurlError = $error;
            $lastDecoded = is_string($response) ? json_decode($response, true) : null;

            if ($response === false) {
                $isTimeout = stripos($error, 'timed out') !== false;
                if ($isTimeout && $attempt < $attempts) {
                    // Give one retry with a higher timeout for transient slow providers.
                    $timeoutSeconds = (int) min(180, $timeoutSeconds * 2);
                    usleep(400000);
                    continue;
                }
                throw new RuntimeException($error !== '' ? $error : 'HTTP request failed.');
            }

            if (($status === 429 || $status >= 500) && $attempt < $attempts) {
                usleep(500000);
                continue;
            }

            if ($status >= 400) {
                $message = 'HTTP request failed with status ' . $status . '.';

                if (is_array($lastDecoded)) {
                    $candidate = $lastDecoded['message']
                        ?? $lastDecoded['error_description']
                        ?? $lastDecoded['error']
                        ?? null;

                    if (is_string($candidate) && $candidate !== '') {
                        $message = $candidate;
                    } elseif (is_array($candidate)) {
                        $message = json_encode($candidate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $message;
                    }
                }

                throw new RuntimeException($message);
            }

            return [
                'status' => $status,
                'body' => is_array($lastDecoded) ? $lastDecoded : ['raw' => (string) $response],
            ];
        }

        // Fallback safety (should not be reached due returns/throws above).
        if ($lastStatus >= 400) {
            throw new RuntimeException('HTTP request failed with status ' . $lastStatus . '.');
        }
        if ($lastRawResponse === '' && $lastCurlError !== '') {
            throw new RuntimeException($lastCurlError);
        }

        return [
            'status' => $lastStatus,
            'body' => is_array($lastDecoded) ? $lastDecoded : ['raw' => $lastRawResponse],
        ];
    }
}
