<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\CloudflareApi;
use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;
use Ironcreed\CloudflareOps\RulesetManager;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $options = getopt('', ['apply']);
    $apply = array_key_exists('apply', $options);
    $project = new Project();
    $manager = new RulesetManager($project, new PolicyCompiler($project));
    $api = $apply ? CloudflareApi::fromWriteToken() : CloudflareApi::fromReadToken();

    foreach ($project->zones() as $zone) {
        if ($apply) {
            $result = $manager->apply($zone, $api);
            printf("%s\t%s\n", $result['result'], $zone);
            continue;
        }

        $plan = $manager->inspect($zone, $api);
        printf("%s\t%s\n", $plan['changed'] ? 'CHANGE' : 'NO_CHANGE', $zone);
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
