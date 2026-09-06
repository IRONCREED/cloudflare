<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;

final class Cli
{
    public static function zone(array $options): string
    {
        $zone = strtolower(trim((string) ($options['zone'] ?? '')));

        if ($zone === '' || !preg_match('/^[a-z0-9.-]+$/', $zone)) {
            throw new RuntimeException('Provide --zone=example.com');
        }

        return $zone;
    }

    public static function printJson(array $data): void
    {
        echo json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ) . PHP_EOL;
    }
}
