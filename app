#!/usr/bin/env php
<?php

use Hotaruma\PackagistSync\Command\SyncPackageCommand;
use Symfony\Component\Console\Application;

if (php_sapi_name() !== 'cli') {
    exit;
}

require_once __DIR__ . "/vendor/autoload.php";

$application = new Application();

$application->add(
    new SyncPackageCommand()
);

try {
    $application->run();
} catch (Exception $e) {
    fwrite(STDOUT, $e->getMessage());
    exit(1);
}
