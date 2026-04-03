<?php

declare(strict_types=1);

namespace App\Support;

use App\Config;
use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function isConfigured(): bool
    {
        return Config::get('DB_CONNECTION') === 'mysql'
            && (string) Config::get('DB_HOST', '') !== ''
            && (string) Config::get('DB_DATABASE', '') !== ''
            && (string) Config::get('DB_USERNAME', '') !== '';
    }

    public static function isAvailable(): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        try {
            self::pdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        if (!self::isConfigured()) {
            throw new RuntimeException('Database is not configured.');
        }

        $host = (string) Config::get('DB_HOST');
        $port = (string) Config::get('DB_PORT', '3306');
        $database = (string) Config::get('DB_DATABASE');
        $username = (string) Config::get('DB_USERNAME');
        $password = (string) Config::get('DB_PASSWORD', '');

        try {
            self::$pdo = new PDO(
                "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed: ' . $exception->getMessage());
        }

        return self::$pdo;
    }
}
