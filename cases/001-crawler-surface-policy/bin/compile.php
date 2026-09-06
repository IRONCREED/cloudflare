<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\Cli;
use Ironcreed\CloudflareOps\PolicyCompiler;
use Ironcreed\CloudflareOps\Project;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $options = getopt('', ['zone:']);
    $project = new Project();
    $zone = Cli::zone($options);
    Cli::printJson((new PolicyCompiler($project))->compile($zone));
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
