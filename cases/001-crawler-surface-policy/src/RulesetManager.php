<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;

final class RulesetManager
{
    public function __construct(
        private readonly Project $project,
        private readonly PolicyCompiler $compiler
    ) {
    }

    public function inspect(string $zone, CloudflareApi $api): array
    {
        $compiled = $this->compiler->compile($zone);
        $zoneId = $api->resolveZoneId($zone);
        $live = $api->getEntrypoint($zoneId, $this->project->phase());
        $currentRules = is_array($live) ? ($live['rules'] ?? []) : [];
        $desiredManaged = array_map([$this, 'stripInternalFields'], $compiled['rules']);
        $unmanaged = array_values(array_filter(
            $currentRules,
            fn (array $rule): bool => !$this->isManaged($rule)
        ));
        $currentManaged = array_values(array_filter(
            $currentRules,
            fn (array $rule): bool => $this->isManaged($rule)
        ));
        $finalRules = $this->replaceManagedBlock($currentRules, $desiredManaged);
        $limit = $this->project->maxPhysicalRules();
        $overLimit = $limit !== null && count($finalRules) > $limit;

        return [
            'zone' => $zone,
            'zone_id' => $zoneId,
            'phase' => $this->project->phase(),
            'entrypoint_exists' => $live !== null,
            'entrypoint_id' => $live['id'] ?? null,
            'current_total' => count($currentRules),
            'current_managed' => count($currentManaged),
            'preserved_unmanaged' => count($unmanaged),
            'desired_managed' => count($desiredManaged),
            'final_total' => count($finalRules),
            'max_physical_rules' => $limit,
            'over_limit' => $overLimit,
            'changed' => !$this->equivalentRuleLists(
                array_map([$this, 'ruleForWrite'], $currentRules),
                $finalRules
            ),
            'compiled' => $compiled,
            'live' => $live,
            'final_rules' => $finalRules,
        ];
    }

    public function apply(string $zone, CloudflareApi $api): array
    {
        $plan = $this->inspect($zone, $api);

        if ($plan['over_limit'] === true) {
            throw new RuntimeException(
                "Refusing write for {$zone}: final rule count {$plan['final_total']} exceeds configured limit {$plan['max_physical_rules']}"
            );
        }

        if ($plan['changed'] !== true) {
            return ['plan' => $plan, 'result' => 'NO_CHANGE'];
        }

        $stamp = gmdate('Ymd-His') . '-' . bin2hex(random_bytes(6));
        $base = $this->project->runtimeDir . '/deployments/' . $stamp . '--' . $this->safeName($zone);
        Json::write($base . '/before.json', $this->snapshotFromPlan($plan));

        if ($plan['entrypoint_exists']) {
            $result = $api->updateEntrypoint($plan['zone_id'], $plan['phase'], $plan['final_rules']);
        } else {
            $result = $api->createEntrypoint($plan['zone_id'], $plan['phase'], $plan['final_rules']);
        }

        Json::write($base . '/write-response.json', $result);

        $after = $this->inspect($zone, $api);
        Json::write($base . '/after.json', $this->snapshotFromPlan($after));

        if ($after['changed'] === true) {
            throw new RuntimeException("Post-write verification failed for {$zone}");
        }

        return ['plan' => $after, 'result' => 'APPLIED', 'evidence_dir' => $base];
    }

    public function summary(array $plan): array
    {
        return [
            'zone' => $plan['zone'],
            'entrypoint_exists' => $plan['entrypoint_exists'],
            'current_total' => $plan['current_total'],
            'current_managed' => $plan['current_managed'],
            'preserved_unmanaged' => $plan['preserved_unmanaged'],
            'desired_managed' => $plan['desired_managed'],
            'final_total' => $plan['final_total'],
            'max_physical_rules' => $plan['max_physical_rules'],
            'over_limit' => $plan['over_limit'],
            'changed' => $plan['changed'],
        ];
    }

    private function replaceManagedBlock(array $currentRules, array $desiredManaged): array
    {
        $final = [];
        $inserted = false;

        foreach ($currentRules as $rule) {
            if ($this->isManaged($rule)) {
                if (!$inserted) {
                    foreach ($desiredManaged as $desiredRule) {
                        $final[] = $desiredRule;
                    }
                    $inserted = true;
                }

                continue;
            }

            $final[] = $this->ruleForWrite($rule);
        }

        if (!$inserted) {
            foreach ($desiredManaged as $desiredRule) {
                $final[] = $desiredRule;
            }
        }

        return array_values($final);
    }

    private function isManaged(array $rule): bool
    {
        return str_starts_with(
            (string) ($rule['description'] ?? ''),
            $this->project->managedPrefix()
        );
    }

    private function stripInternalFields(array $rule): array
    {
        unset($rule['_sources']);
        return $this->ruleForWrite($rule);
    }

    private function ruleForWrite(array $rule): array
    {
        $out = [
            'action' => strtolower((string) ($rule['action'] ?? '')),
            'expression' => trim((string) ($rule['expression'] ?? '')),
            'description' => (string) ($rule['description'] ?? ''),
            'enabled' => (bool) ($rule['enabled'] ?? true),
        ];

        foreach (['action_parameters', 'logging'] as $optional) {
            if (array_key_exists($optional, $rule)) {
                $out[$optional] = $rule[$optional];
            }
        }

        return $out;
    }

    private function equivalentRuleLists(array $left, array $right): bool
    {
        if (count($left) !== count($right)) {
            return false;
        }

        foreach ($left as $index => $rule) {
            if (!isset($right[$index])) {
                return false;
            }

            if ($this->comparableRule($rule) !== $this->comparableRule($right[$index])) {
                return false;
            }
        }

        return true;
    }

    private function comparableRule(array $rule): array
    {
        $rule = $this->ruleForWrite($rule);
        // Whitespace inside quoted Cloudflare string literals changes meaning.
        // Compare conservatively; formatting-only changes may require a review.
        ksort($rule);
        return $rule;
    }

    private function snapshotFromPlan(array $plan): array
    {
        return [
            'zone' => $plan['zone'],
            'zone_id' => $plan['zone_id'],
            'phase' => $plan['phase'],
            'compiled' => $plan['compiled'],
            'live' => $plan['live'],
            'final_rules' => $plan['final_rules'],
        ];
    }

    private function safeName(string $zone): string
    {
        return preg_replace('/[^a-z0-9.-]+/i', '-', $zone) ?: 'zone';
    }
}
