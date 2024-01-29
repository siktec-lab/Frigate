<?php

declare(strict_types=1);

namespace Frigate\Tests\Application;

use PHPUnit\Framework\TestCase;
use Frigate\FrigateApp as App;
use Frigate\Exceptions\FrigateException;

/**
 * @runTestsInSeparateProcesses
 */
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

    public function testAppConstantsInit() : void
    {
        $this->unsetAllEnv();

        App::init(
            root : __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.nested_root" ],
            extra_env : [],
            load_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );

        $expected = [
            "APP_ROOT"          => __DIR__,
            "APP_VENDOR"        => __DIR__ . DIRECTORY_SEPARATOR . "vendor",
            "APP_BASE_PATH"     => DIRECTORY_SEPARATOR . "web" . DIRECTORY_SEPARATOR . "files",
            "APP_BASE_URI"      => "/web/files",
            "APP_BASE_URL"      => "http://localhost/web/files",
            "APP_VERSION"       => "1.0.0",
            "APP_LOG_ERRORS"    => true,
            "APP_EXPOSE_ERRORS" => true,
        ];
        // Assert that all required environment variables are set:
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, App::$globals);
            $this->assertEquals($value, App::$globals[$key]);
            if (defined($key)) {
                $this->assertEquals($value, constant($key));
            } else {
                $this->fail("Constant $key is not defined");
            }
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
