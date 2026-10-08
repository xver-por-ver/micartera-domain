<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Xver\MiCartera\Domain\Kernel;

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    new Dotenv()->bootEnv(dirname(__DIR__) . '/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0o000);
}

$kernel = new Kernel('test', false);
$kernel->boot();

// Prepare test database for testing
$testsuite = getopt('', ['testsuite:']);
if (is_array($testsuite) && isset($testsuite['testsuite'])) {
    $testsuites = explode(',', $testsuite['testsuite']);
    if (
        in_array('integration', $testsuites)
        || in_array('application', $testsuites)
        || in_array('all', $testsuites)
    ) {
        require_once dirname(__DIR__) . '/tests/TestDbSetup.php';
    }
}
