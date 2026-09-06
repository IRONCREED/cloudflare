<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;

final class Project
{
    public readonly string $configDir;
    public readonly string $runtimeDir;
    public readonly array $inventory;

    public function __construct(?string $configDir = null, ?string $runtimeDir = null)
    {
        $caseRoot = dirname(__DIR__);
        $this->configDir = rtrim($configDir ?: (getenv('CFOPS_CONFIG_DIR') ?: $caseRoot . '/examples'), '/');
        $this->runtimeDir = rtrim($runtimeDir ?: (getenv('CFOPS_RUNTIME_DIR') ?: $caseRoot . '/runtime'), '/');
        $this->inventory = Json::read($this->configDir . '/inventory.json');

        if (($this->inventory['phase'] ?? '') !== 'http_request_firewall_custom') {
            throw new RuntimeException('Case 001 supports only http_request_firewall_custom');
        }

        if (trim((string) ($this->inventory['managed_prefix'] ?? '')) === '') {
            throw new RuntimeException('managed_prefix is required');
        }
    }

    public function phase(): string
    {
        return (string) $this->inventory['phase'];
    }

    public function managedPrefix(): string
    {
        return (string) $this->inventory['managed_prefix'];
    }

    public function zones(): array
    {
        return array_values(array_map('strval', $this->inventory['zones'] ?? []));
    }

    public function maxPhysicalRules(): ?int
    {
        if (!array_key_exists('max_physical_rules', $this->inventory)) {
            return null;
        }

        $limit = (int) $this->inventory['max_physical_rules'];

        if ($limit < 1) {
            throw new RuntimeException('max_physical_rules must be a positive integer');
        }

        return $limit;
    }

    public function zoneConfig(string $zone): array
    {
        $file = $this->configDir . '/zones/' . $zone . '.json';
        $config = Json::read($file);

        if (($config['zone'] ?? '') !== $zone) {
            throw new RuntimeException("Zone config mismatch: {$file}");
        }

        return $config;
    }

    public function profile(string $name): array
    {
        return Json::read($this->configDir . '/profiles/' . $name . '.json');
    }

    public function policy(string $id): array
    {
        return Json::read($this->configDir . '/policies/' . $id . '.json');
    }
}
