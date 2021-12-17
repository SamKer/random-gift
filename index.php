<?php
namespace RandomGift;
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;


define("PROJECT_DIR", __DIR__ );
define("PROJECT_NAME", "RANDOM GIFT");
define("PROJECT_CONFIG", __DIR__."/config.yaml");
define("PROJECT_VERSION", json_decode(file_get_contents('./composer.json'))->version);

$application = new Application(PROJECT_NAME, PROJECT_VERSION);

$application->addCommands([
    new Send()
]);
$application->run();
