#!/usr/bin/env php
<?php

//------------------------------------------------------------
// Cli setup
//------------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    exit;
}
set_time_limit(0);
ini_set('memory_limit', '128M');

//------------------------------------------------------------
// Composer autoload + constants
//------------------------------------------------------------
const DS = DIRECTORY_SEPARATOR;
$cwd  = getcwd() ?: __DIR__;
$source = realpath(__DIR__ . DS . "..");
// Composer autoload
if (!isset($_composer_autoload_path)) {
    // Main Project:
    $path_autoload_main = __DIR__ . '/../vendor/autoload.php'; 
    // Installed as a dependency:
    $path_autoload_package = __DIR__ . '/../../../autoload.php';
    // Check if the composer autoload file exists:
    if (file_exists($path_autoload_main)) {
        $_composer_autoload_path = $path_autoload_main;
        $_composer_bin_dir = __DIR__ . '/../vendor/bin';
    } elseif (file_exists($path_autoload_package)) {
        $_composer_autoload_path = $path_autoload_package;
        $_composer_bin_dir = __DIR__ . '/../../../bin';
    } else {
        throw new \Exception("Composer autoload file not found");
    }
}
// Define the constants:
define("CLI_VENDOR_BIN", $_composer_bin_dir);
define("FRIGATE_SOURCE", $source);
define("CLI_CWD", $cwd);

// Load the composer autoload:
require_once $_composer_autoload_path;

//------------------------------------------------------------
// Frigate CLI
//------------------------------------------------------------
use Frigate\Cli\CliApp;
use Frigate\Helpers\Paths;

// Create the CLI application:
$frigate_cli = new CliApp(
    name    : FrigateBin\App\About::NAME,
    version : FrigateBin\App\About::VERSION
);

// Add commands:
$loaded = $frigate_cli->autoLoadCommands(
    namespace: "FrigateBin\\App\\Commands", 
    folder: Paths::join($source, "bin", "App", "Commands")
);

// Handle the CLI automatically:
$frigate_cli->handle();