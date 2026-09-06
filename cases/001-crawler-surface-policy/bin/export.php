<?php

declare(strict_types=1);

use Ironcreed\CloudflareOps\CloudflareApi;
use Ironcreed\CloudflareOps\Json;
use Ironcreed\CloudflareOps\Project;

require dirname(__DIR__) . '/src/bootstrap.php';

try {
    $project = new Project();
    $api = CloudflareApi::fromReadToken();
    $stamp = gmdate('Ymd-His');
    $root = $project->runtimeDir . '/exports/' . $stamp;

    foreach ($project->zones() as $zone) {
        $zoneId = $api->resolveZoneId($zone);
        $entrypoint = $api->getEntrypoint($zoneId, $project->phase());
        Json::write($root . '/' . $zone . '.json', [
            'zone' => $zone,
            'zone_id' => $zoneId,
            'phase' => $project->phase(),
            'entrypoint' => $entrypoint,
        ]);
        echo "EXPORTED\t{$zone}\n";
    }

    echo "EXPORT_DIR\t{$root}\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'ERROR: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
