<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\Cli;
use Ironcreed\CloudflareOps\CloudflareApi;
use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;
use Ironcreed\CloudflareOps\RulesetManager;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $options = getopt('', ['zone:']);
    $project = new Project();
    $zone = Cli::zone($options);
    $manager = new RulesetManager($project, new PolicyCompiler($project));
    $plan = $manager->inspect($zone, CloudflareApi::fromReadToken());
    Cli::printJson($manager->summary($plan));
    exit($plan['changed'] ? 2 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
