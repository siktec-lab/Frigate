<?php

declare(strict_types=1);

namespace Frigate\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Frigate\FrigateApp as App;
use Frigate\Routing\Router;
use Frigate\Routing\Routes\Route;
use Frigate\Routing\Http\Methods;

class RoutingStaticTest extends TestCase
{
    public function setUp() : void
    {
        return;
    }

    public function tearDown() : void
    {
        return;
    }

    public function testRoutingDefaultDebug() : void
    {
        
        $this->unsetAllEnv();

        $this->assertTrue(true);

    }

    public static function setUpBeforeClass() : void
    {
        return;
    }

    public static function tearDownAfterClass() : void
    {
        return;
    }

    private function unsetAllEnv() : void
    {
        global $_ENV, $_SERVER;
        $_ENV = [];
        $_SERVER = [];
        App::$env = null;
        App::$application_env = [];
    }
}
