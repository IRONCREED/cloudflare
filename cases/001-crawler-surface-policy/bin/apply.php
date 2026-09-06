<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\Cli;
use Ironcreed\CloudflareOps\CloudflareApi;
use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;
use Ironcreed\CloudflareOps\RulesetManager;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $options = getopt('', ['zone:', 'apply']);

    if (!array_key_exists('apply', $options)) {
        throw new RuntimeException('Refusing write without explicit --apply');
    }

    $project = new Project();
    $zone = Cli::zone($options);
    $manager = new RulesetManager($project, new PolicyCompiler($project));
    $result = $manager->apply($zone, CloudflareApi::fromWriteToken());
    Cli::printJson([
        'zone' => $zone,
        'result' => $result['result'],
        'evidence_dir' => $result['evidence_dir'] ?? null,
    ]);
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
