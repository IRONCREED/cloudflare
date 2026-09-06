<?php

declare(strict_types=1);

namespace Ironcreed\CloudflareOps;

use RuntimeException;

final class PolicyCompiler
{
    public function __construct(private readonly Project $project)
    {
    }

    public function compile(string $zone): array
    {
        $zoneConfig = $this->project->zoneConfig($zone);
        $profileName = trim((string) ($zoneConfig['profile'] ?? 'default')) ?: 'default';
        $profile = $this->project->profile($profileName);

        $policyIds = array_values(array_unique(array_merge(
            array_map('strval', $profile['policies'] ?? []),
            array_map('strval', $zoneConfig['additional_policies'] ?? [])
        )));

        $disabled = array_map('strval', $zoneConfig['disabled_policies'] ?? []);
        $policyIds = array_values(array_diff($policyIds, $disabled));

        $logicalRules = [];

        foreach ($policyIds as $policyId) {
            $policy = $this->project->policy($policyId);

            if (($policy['id'] ?? '') !== $policyId) {
                throw new RuntimeException("Policy id mismatch: {$policyId}");
            }

            foreach ($policy['rules'] ?? [] as $rule) {
                if (!is_array($rule) || ($rule['enabled'] ?? true) !== true) {
                    continue;
                }

                $this->assertRule($rule, $policyId);
                $rule['_policy_id'] = $policyId;
                $logicalRules[] = $rule;
            }
        }

        foreach ($zoneConfig['inline_rules'] ?? [] as $rule) {
            if (!is_array($rule) || ($rule['enabled'] ?? true) !== true) {
                continue;
            }

            $this->assertRule($rule, 'zone:' . $zone);
            $rule['_policy_id'] = 'zone:' . $zone;
            $logicalRules[] = $rule;
        }

        $managedRules = $this->physicalRules($logicalRules);

        return [
            'schema_version' => 1,
            'compiled_for' => $zone,
            'compiled_profile' => $profileName,
            'phase' => $this->project->phase(),
            'managed_prefix' => $this->project->managedPrefix(),
            'policies' => $policyIds,
            'logical_rule_count' => count($logicalRules),
            'rules' => $managedRules,
        ];
    }

    private function assertRule(array $rule, string $policyId): void
    {
        foreach (['id', 'name', 'action', 'expression'] as $field) {
            if (trim((string) ($rule[$field] ?? '')) === '') {
                throw new RuntimeException("Policy {$policyId} has a rule without {$field}");
            }
        }
    }

    private function physicalRules(array $logicalRules): array
    {
        $groups = [];
        $standalone = [];

        foreach ($logicalRules as $rule) {
            $physicalGroup = trim((string) ($rule['physical_group'] ?? ''));

            if ($physicalGroup === '') {
                $standalone[] = $this->toPhysicalRule($rule);
                continue;
            }

            $action = strtolower((string) $rule['action']);
            $key = $action . ':' . $physicalGroup;
            $groups[$key]['action'] = $action;
            $groups[$key]['group'] = $physicalGroup;
            $groups[$key]['expressions'][] = trim((string) $rule['expression']);
            $groups[$key]['sources'][] = (string) $rule['_policy_id'] . '/' . (string) $rule['id'];
        }

        $merged = [];

        foreach ($groups as $group) {
            $expressions = array_values(array_unique($group['expressions']));
            $sources = array_values(array_unique($group['sources']));
            $merged[] = [
                'action' => $group['action'],
                'expression' => $this->joinOr($expressions),
                'description' => $this->project->managedPrefix() . ' ' . ucfirst($group['group']) . ' policy',
                'enabled' => true,
                '_sources' => $sources,
            ];
        }

        return array_values(array_merge($merged, $standalone));
    }

    private function toPhysicalRule(array $rule): array
    {
        return [
            'action' => strtolower((string) $rule['action']),
            'expression' => trim((string) $rule['expression']),
            'description' => $this->project->managedPrefix() . ' ' . trim((string) $rule['name']),
            'enabled' => true,
            '_sources' => [(string) $rule['_policy_id'] . '/' . (string) $rule['id']],
        ];
    }

    private function joinOr(array $expressions): string
    {
        if (count($expressions) === 1) {
            return $expressions[0];
        }

        return "(\n  " . implode("\n  or\n  ", array_map(
            static fn (string $expression): string => '(' . $expression . ')',
            $expressions
        )) . "\n)";
    }
}
