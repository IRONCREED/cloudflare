<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;
use Ironcreed\CloudflareOps\RulesetManager;

require dirname(__DIR__) . '/src/bootstrap.php';

$failures = 0;
$checks = 0;

function check(bool $condition, string $message): void
{
    global $failures, $checks;

    $checks++;

    if ($condition) {
        echo "PASS\t{$message}\n";
        return;
    }

    $failures++;
    echo "FAIL\t{$message}\n";
}

try {
    $project = new Project(dirname(__DIR__) . '/examples', sys_get_temp_dir() . '/cfops-tests');
    $compiler = new PolicyCompiler($project);

    $default = $compiler->compile('domain-01.example');
    check($default['compiled_profile'] === 'default', 'default profile resolves');
    check(count($default['rules']) === 1, 'default profile compiles to one physical rule');
    check($default['rules'][0]['action'] === 'managed_challenge', 'scanner rule is a challenge');

    $commerce = $compiler->compile('domain-02.example');
    check($commerce['compiled_profile'] === 'commerce', 'commerce profile resolves');
    check(count($commerce['rules']) === 2, 'commerce rules merge to two physical groups');
    check($commerce['logical_rule_count'] === 3, 'commerce keeps three logical rules before merge');

    $block = array_values(array_filter(
        $commerce['rules'],
        static fn (array $rule): bool => $rule['action'] === 'block'
    ));
    check(count($block) === 1, 'commerce block policies merge into one physical rule');
    check(str_contains($block[0]['expression'] ?? '', '/cart'), 'crawler surface remains in merged block expression');
    check(str_contains($block[0]['expression'] ?? '', 'AI Crawler'), 'bot policy remains in merged block expression');

    $geo = $compiler->compile('domain-03.example');
    check(count($geo['rules']) === 2, 'geo profile compiles challenge and block groups');
    check($geo['logical_rule_count'] === 4, 'geo profile includes hostname-specific inline rule before merge');
    check(str_contains(json_encode($geo), 'forum.domain-03.example'), 'hostname-specific example is present');
    check(str_contains(json_encode($geo), 'CN'), 'country example is present');
    check(str_contains(json_encode($geo), 'T1'), 'network example is present');

    $manager = new RulesetManager($project, $compiler);
    $compare = new ReflectionMethod(RulesetManager::class, 'equivalentRuleLists');
    $a = ['action' => 'block', 'expression' => 'http.user_agent contains "foo bar"', 'description' => '[CFOPS] Block policy', 'enabled' => true];
    $b = $a;
    $b['expression'] = 'http.user_agent contains "foobar"';
    check(!$compare->invoke($manager, [$a], [$b]), 'literal whitespace changes are detected as drift');
    check($compare->invoke($manager, [$a], [$a]), 'identical rule states remain a no-op');
} catch (Throwable $error) {
    $failures++;
    echo 'FAIL\tunhandled exception: ' . $error->getMessage() . PHP_EOL;
}

printf("SUMMARY\tPASS=%d\tFAIL=%d\n", $checks - $failures, $failures);
exit($failures > 0 ? 1 : 0);
