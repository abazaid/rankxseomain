<?php

declare(strict_types=1);

namespace App\Support;

final class Request
{
    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function input(): array
    {
        $content = file_get_contents('php://input') ?: '';
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function rawBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }
}
