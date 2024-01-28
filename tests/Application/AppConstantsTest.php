<?php

declare(strict_types=1);

namespace Frigate\Tests\Application;

use PHPUnit\Framework\TestCase;
use Frigate\FrigateApp as App;
use Frigate\Exceptions\FrigateException;

class AppConstantsTest extends TestCase
{

    public const RES_FOLDER = __DIR__ . "/../resources";
    public const ENV_FOLDER = self::RES_FOLDER . "/env";

    public function setUp() : void
    {
        return;
    }

    public function tearDown() : void
    {
        return;
    }

    private function unsetAllEnv() : void
    {
        global $_ENV, $_SERVER;
        $_ENV = [];
        $_SERVER = [];
        App::$env = null;
    }

    public function testEnvFromFile() : void
    {
        $this->unsetAllEnv();

        App::init(
            root : __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.working" ],
            extra_env : [],
            load_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
        // Assert that all required environment variables are set:
        foreach (App::REQUIRED_ENV as $key => $value) {
            $this->assertArrayHasKey($key, $_ENV);
        }
    }

    public static function setUpBeforeClass() : void
    {
        return;
    }

    public static function tearDownAfterClass() : void
    {
        return;
    }
}
