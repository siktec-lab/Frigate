<?php

declare(strict_types=1);

//Max memory
ini_set('memory_limit', '64M');
ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

/*
 * Bootstrap file for PHPUnit tests.
 */

require_once __DIR__ . '/../vendor/autoload.php';
