<?php

require_once __DIR__ . '/vendor/autoload.php';

use Piwik\IntellijPlugins\PiwikStorm\Runner\Script;

$application = new Script();
$application->run();