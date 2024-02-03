#!/usr/bin/env php
<?php

//------------------------------------------------------------
// Cli setup
//------------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    exit;
}
// Ini settings:
set_time_limit(0);
ini_set('memory_limit', '128M');

//------------------------------------------------------------
// Composer autoload + constants
//------------------------------------------------------------
const DS = DIRECTORY_SEPARATOR;
const FRIGATE_PATH = ["siktec", "frigate"];
const CWD  = __DIR__ ;
require_once $_composer_autoload_path ?? CWD . '/../vendor/autoload.php';
$VENDOR_BIN = $_composer_bin_dir ?? CWD . '/../vendor/bin';
$SOURCE     = isset($_composer_autoload_path) ? 
                dirname($_composer_autoload_path).DS.implode(DS, FRIGATE_PATH) :
                dirname(CWD);

//------------------------------------------------------------
// Frigate CLI
//------------------------------------------------------------
use Frigate\Cli\CliApp;
use Frigate\Tools\Paths\PathHelpers as Path;

// Create the CLI application:
$frigate_cli = new CliApp(
    name    : FrigateBin\App\About::NAME,
    version : FrigateBin\App\About::VERSION
);

// Add commands:
$loaded = $frigate_cli->autoLoadCommands(
    "FrigateBin\\App\\Commands", 
    Path::path($SOURCE, "bin", "App", "Commands")
);

// Handle the CLI automatically:
$frigate_cli->handle();