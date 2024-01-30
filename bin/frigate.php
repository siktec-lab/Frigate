#!/usr/bin/env php
<?php

// Exit if not running from command line:
if (php_sapi_name() !== 'cli') {
    exit;
}

require_once $_composer_autoload_path ?? __DIR__ . '/../vendor/autoload.php';
$vendor_bin = $_composer_bin_dir ?? __DIR__ . '/../vendor/bin';

const DS = DIRECTORY_SEPARATOR;
const FRIGATE_PATH = ["siktec", "frigate"];

// Locate our package directory (which is our installation in vendor) we may be also runing directly from the package directory:
function locate_frigate_source() : string
{
    if (isset($_composer_autoload_path)) {
        return dirname($_composer_autoload_path).DS.implode(DS, FRIGATE_PATH);
    }
    return dirname(__DIR__);
}

$source = locate_frigate_source();

use JCli\Application;

echo "Frigate source: $source\n";