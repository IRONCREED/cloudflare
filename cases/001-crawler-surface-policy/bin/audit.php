<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\CloudflareApi;
use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;
use Ironcreed\CloudflareOps\RulesetManager;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $project = new Project();
    $manager = new RulesetManager($project, new PolicyCompiler($project));
    $api = CloudflareApi::fromReadToken();
    $ok = 0;
    $drift = 0;

    foreach ($project->zones() as $zone) {
        $plan = $manager->inspect($zone, $api);
        printf("%s\t%s\n", $plan['changed'] ? 'DRIFT' : 'OK', $zone);
        $plan['changed'] ? $drift++ : $ok++;
    }

    printf("SUMMARY\tOK=%d\tDRIFT=%d\n", $ok, $drift);
    exit($drift > 0 ? 2 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
