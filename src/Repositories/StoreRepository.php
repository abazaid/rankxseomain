<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Config;

final class StoreRepository
{
    private string $file;

    public function __construct()
    {
        $storagePath = Config::get('STORAGE_PATH', dirname(__DIR__, 2) . '/storage');

        if (!preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/])/', (string) $storagePath)) {
            $basePath = \defined('APP_BASE_PATH') ? APP_BASE_PATH : dirname(__DIR__, 2);
            $storagePath = $basePath . '/' . ltrim((string) $storagePath, '/\\');
        }

        $this->file = rtrim($storagePath, '/\\') . '/stores.json';
        $this->ensureStorageReady();
    }

    public function find(string|int $merchantId): ?array
    {
        $items = $this->all();
        return $items[(string) $merchantId] ?? null;
    }

    public function save(string|int $merchantId, array $data): void
    {
        $this->ensureStorageReady();
        $items = $this->all();
        $key = (string) $merchantId;
        $items[$key] = array_merge($items[$key] ?? [], $data);
        file_put_contents($this->file, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function delete(string|int $merchantId): void
    {
        $items = $this->all();
        unset($items[(string) $merchantId]);
        file_put_contents($this->file, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public function all(): array
    {
        if (!is_file($this->file)) {
            return [];
        }

        $content = file_get_contents($this->file);
        $decoded = json_decode($content ?: '{}', true);
        return is_array($decoded) ? $decoded : [];
    }

    private function ensureStorageReady(): void
    {
        $directory = dirname($this->file);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        if (!is_file($this->file)) {
            file_put_contents($this->file, '{}');
        }
    }
}
