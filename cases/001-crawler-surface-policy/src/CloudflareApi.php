<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;
use Throwable;

final class CloudflareApi
{
    private const BASE = 'https://api.cloudflare.com/client/v4';

    public function __construct(private readonly string $token)
    {
        if ($this->token === '') {
            throw new RuntimeException('Cloudflare token is empty');
        }
    }

    public static function fromReadToken(): self
    {
        return new self(trim((string) getenv('CLOUDFLARE_READ_TOKEN')));
    }

    public static function fromWriteToken(): self
    {
        return new self(trim((string) getenv('CLOUDFLARE_WRITE_TOKEN')));
    }

    public function resolveZoneId(string $zone): string
    {
        $response = $this->request('GET', '/zones?name=' . rawurlencode($zone) . '&per_page=50');
        $matches = array_values(array_filter(
            $response['result'] ?? [],
            static fn (array $item): bool => strtolower((string) ($item['name'] ?? '')) === strtolower($zone)
        ));

        if (count($matches) !== 1 || trim((string) ($matches[0]['id'] ?? '')) === '') {
            throw new RuntimeException("Expected exactly one Cloudflare zone named {$zone}");
        }

        return (string) $matches[0]['id'];
    }

    public function getEntrypoint(string $zoneId, string $phase): ?array
    {
        $response = $this->request(
            'GET',
            '/zones/' . rawurlencode($zoneId) . '/rulesets/phases/' . rawurlencode($phase) . '/entrypoint',
            null,
            [404]
        );

        if ($response['_http'] === 404) {
            return null;
        }

        return $response['result'] ?? null;
    }

    public function createEntrypoint(string $zoneId, string $phase, array $rules): array
    {
        $body = [
            'name' => 'Zone-level phase entry point',
            'description' => 'Managed through Cloudflare Operations Case 001',
            'kind' => 'zone',
            'phase' => $phase,
            'rules' => $rules,
        ];

        $response = $this->request('POST', '/zones/' . rawurlencode($zoneId) . '/rulesets', $body);

        return $response['result'] ?? [];
    }

    public function updateEntrypoint(string $zoneId, string $phase, array $rules): array
    {
        $body = [
            'description' => 'Zone-level phase entry point managed with Cloudflare Operations Case 001',
            'rules' => $rules,
        ];

        $response = $this->request(
            'PUT',
            '/zones/' . rawurlencode($zoneId) . '/rulesets/phases/' . rawurlencode($phase) . '/entrypoint',
            $body
        );

        return $response['result'] ?? [];
    }

    private function request(string $method, string $path, ?array $body = null, array $allowedHttp = []): array
    {
        $ch = curl_init(self::BASE . $path);

        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 60,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode(
                $body,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);

        if ($raw === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException("Cloudflare transport error: {$error}");
        }

        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        try {
            $json = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $error) {
            throw new RuntimeException("Cloudflare returned invalid JSON; HTTP {$http}");
        }

        if (in_array($http, $allowedHttp, true)) {
            $json['_http'] = $http;
            return $json;
        }

        if ($http < 200 || $http >= 300 || ($json['success'] ?? false) !== true) {
            $errors = json_encode($json['errors'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            throw new RuntimeException("Cloudflare API error HTTP {$http}: {$errors}");
        }

        $json['_http'] = $http;
        return $json;
    }
}
