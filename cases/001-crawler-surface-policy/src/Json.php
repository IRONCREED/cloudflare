<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;
use Throwable;

final class Json
{
    public static function read(string $file): array
    {
        if (!is_file($file)) {
            throw new RuntimeException("JSON file not found: {$file}");
        }

        try {
            $data = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $error) {
            throw new RuntimeException("Invalid JSON {$file}: {$error->getMessage()}");
        }

        if (!is_array($data)) {
            throw new RuntimeException("JSON root must be an object: {$file}");
        }

        return $data;
    }

    public static function write(string $file, array $data): void
    {
        $dir = dirname($file);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create directory: {$dir}");
        }

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        if (file_put_contents($file, $json . PHP_EOL) === false) {
            throw new RuntimeException("Cannot write JSON: {$file}");
        }
    }
}
