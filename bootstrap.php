<?php
require_once __DIR__.'/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
require_once __DIR__."/config.php"; // must be bellow Dontenv\
