<?php

use Psc\Boot\BootLoader;
use Psc\PSC;

/**
 * Bootstrap and Autoload whole application
 *
 * you can use this file to bootstrap for tests or bootstrap for scripts / others
 */
$ds = DIRECTORY_SEPARATOR;

require_once __DIR__.$ds.'package.boot.php';
$bootLoader = new BootLoader(__DIR__);
$bootLoader->loadComposer();

?>