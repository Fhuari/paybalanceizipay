<?php
namespace PearPay\Admin\Izipay;

class IzipayConfig
{
    private const DEFAULT_API_URL = 'https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment';
    private static bool $envLoaded = false;

    public static function env(): string
    {
        self::loadDotEnv();
        $env = self::getEnv('PEARPAY_IZIPAY_ENV', '');
        if ($env === '') {
            $env = 'dev';
        }
        return $env === 'production' ? 'production' : 'dev';
    }

    public static function apiUrl(): string
    {
        self::loadDotEnv();
        if (defined('PEARPAY_IZIPAY_API_URL')) {
            return (string) PEARPAY_IZIPAY_API_URL;
        }
        $envUrl = self::getEnv('PEARPAY_IZIPAY_API_URL', '');
        if ($envUrl !== '') {
            return $envUrl;
        }
        return self::DEFAULT_API_URL;
    }

    public static function credentials(): array
    {
        self::loadDotEnv();
        $env = self::env();
        $prefix = $env === 'production' ? 'PEARPAY_IZIPAY_PROD_' : 'PEARPAY_IZIPAY_DEV_';
        return [
            'username' => self::getCredential($prefix . 'USERNAME', ''),
            'password' => self::getCredential($prefix . 'PASSWORD', ''),
            'public_key' => self::getCredential($prefix . 'PUBLIC_KEY', ''),
            'hmac_sha256' => self::getCredential($prefix . 'HMAC_SHA256', ''),
            'api_url' => self::apiUrl(),
        ];
    }

    private static function getCredential(string $name, string $fallback): string
    {
        $constant = self::getConstant($name,'');
        if ($constant !== '') {
            return $constant;
        }
        $env = self::getEnv($name, '');
        return $env !== '' ? $env : $fallback;
    }

    private static function getConstant(string $name, string $fallback): string
    {
        return defined($name) ? (string) constant($name) : $fallback;
    }

    private static function getEnv(string $name, string $fallback): string
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }
        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return (string) $_ENV[$name];
        }
        return $fallback;
    }

    private static function loadDotEnv(): void
    {
        if (self::$envLoaded) {
            return;
        }
        self::$envLoaded = true;

        $envFile = __DIR__ . '/.env';
        if (!is_readable($envFile)) {
            return;
        }
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key !== '' && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
            }
        }
    }
}
