<?php

declare(strict_types=1);

namespace Frigate\Tests\Application;

use PHPUnit\Framework\TestCase;
use Frigate\FrigateApp as App;
use Frigate\Exceptions\FrigateException;

class AppEnvTest extends TestCase
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
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
        // Assert that all required environment variables are set:
        foreach (App::REQUIRED_ENV as $key => $value) {
            $this->assertArrayHasKey($key, $_ENV);
        }
    }

    public function testEnvFromDefaultRoot() : void
    {
        $this->unsetAllEnv();

        App::init(
            root : self::ENV_FOLDER,
            env : null,
            extra_env : [],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
        // Assert that all required environment variables are set:
        foreach (App::REQUIRED_ENV as $key => $value) {
            $this->assertArrayHasKey($key, $_ENV);
        }
    }
    public function testEnvFromMultipleFiles() : void
    {
        $this->unsetAllEnv();

        App::init(
            root : __DIR__,
            env : [ self::ENV_FOLDER , [ ".env.test.working", ".env.test.extra" ] ],
            extra_env : [ "MANUALLY_ASSIGNED" => "yes" ],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
        // Assert that all required environment variables are set:
        foreach (App::REQUIRED_ENV as $key => $value) {
            $this->assertArrayHasKey($key, $_ENV);
        }
        $this->assertEquals("My Website", $_ENV["WEBSITE_NAME"]);
        $this->assertEquals("yes", $_SERVER["TESTING"]);
        $this->assertEquals("yes", $_ENV["MANUALLY_ASSIGNED"]);
    }

    public function testEnvSelfReference() : void 
    {
        $this->unsetAllEnv();

        App::init(
            root : __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.self_ref" ],
            extra_env : [],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );

        // All references should be resolved:
        $this->assertEquals("http://localhost/", $_ENV["API_DOMAIN"]);
        $this->assertEquals("1.0.0", $_ENV["API_VERSION"]);
        $this->assertEquals("no", $_ENV["API_DEBUG"]);
    }

    public function testRetrivalHelpers() : void 
    {
        $this->unsetAllEnv();

        App::init(
            root : __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.working" ],
            extra_env : [
                "TRUE_YES" => "yes",
                "FALSE_NO" => "no",
                "TRUE_1" => "1",
                "FALSE_0" => "0",
                "TRUE_TRUE" => "true",
                "FALSE_FALSE" => "false",
                "TRUE_ON" => "on",
                "FALSE_OFF" => "off",
                "INT_34" => "34",
                "INT_0" => "0",
                "INT_NEG_1" => "-1",
                "FLOAT_34" => "34.4",
                "FLOAT_0" => "0.0",
                "FLOAT_NEG_1" => "-1.1"
            ],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );

        // All references should be resolved:
        $root = App::ENV_STR("FRIGATE_ROOT_FOLDER");
        $this->assertEquals("", $root);
        $domain = App::ENV_STR("FRIGATE_BASE_URL"); 
        $this->assertEquals("http://localhost/", $domain);
        $version = App::ENV_STR("FRIGATE_APP_VERSION");
        $this->assertEquals("1.0.0", $version);
        $debug = App::ENV_BOOL("FRIGATE_DEBUG_ROUTER");
        $this->assertEquals(false, $debug);
        $debug = App::ENV_BOOL("FRIGATE_EXPOSE_ERRORS");
        $this->assertEquals(true, $debug);
        $non_existent = App::ENV_STR("NON_EXISTENT", "default");
        $this->assertEquals("default", $non_existent);

        // Test boolean helpers:
        $this->assertEquals(true, App::ENV_BOOL("TRUE_YES"));
        $this->assertEquals(false, App::ENV_BOOL("FALSE_NO"));
        $this->assertEquals(true, App::ENV_BOOL("TRUE_1"));
        $this->assertEquals(false, App::ENV_BOOL("FALSE_0"));
        $this->assertEquals(true, App::ENV_BOOL("TRUE_TRUE"));
        $this->assertEquals(false, App::ENV_BOOL("FALSE_FALSE"));
        $this->assertEquals(true, App::ENV_BOOL("TRUE_ON"));
        $this->assertEquals(false, App::ENV_BOOL("FALSE_OFF"));
        $this->assertEquals(34, App::ENV_INT("INT_34"));
        $this->assertEquals(0, App::ENV_INT("INT_0"));
        $this->assertEquals(-1, App::ENV_INT("INT_NEG_1"));
        $this->assertEquals(34.4, App::ENV_FLOAT("FLOAT_34"));
        $this->assertEquals(0.0, App::ENV_FLOAT("FLOAT_0"));
        $this->assertEquals(-1.1, App::ENV_FLOAT("FLOAT_NEG_1"));
    }

    public function testEnvCustomRequiredArray() : void 
    {
        $this->unsetAllEnv();

        App::$application_env = [
            "MY_ENV_VAR"    => "not-empty",
            "MY_ENV_VAR2"   => "string",
            "MY_ENV_VAR3"   => "int",
            "MY_ENV_VAR4"   => "bool"
        ];

        $expected = [
            "MY_ENV_VAR"    => "I'm not empty",
            "MY_ENV_VAR2"   => "I'm a string",
            "MY_ENV_VAR3"   => "34",
            "MY_ENV_VAR4"   => "true"
        ];

        App::init(
            root: __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.working" ],
            extra_env : $expected,
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );

        // Check that all required variables are set:
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $_ENV);
            $this->assertEquals($value, $_ENV[$key]);
            $this->assertArrayHasKey($key, $_SERVER);
            $this->assertEquals($value, $_SERVER[$key]);
        }

    }
    public function testEnvEmptyEnvError() : void
    {
        $this->unsetAllEnv();

        $this->expectException(FrigateException::class);
        $this->expectExceptionCode(FrigateException::CODE_FRIGATE_ENV_ERROR);
        $this->expectExceptionMessageMatches("/FRIGATE_BASE_URL/");

        App::init(
            root: __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.empty_domain" ],
            extra_env : [],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
    }

    public function testEnvMissingEnvError() : void
    {
        $this->unsetAllEnv();

        $this->expectException(FrigateException::class);
        $this->expectExceptionCode(FrigateException::CODE_FRIGATE_ENV_ERROR);
        $this->expectExceptionMessageMatches("/FRIGATE_DEBUG_ROUTER/");

        App::init(
            root: __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.missing_required" ],
            extra_env : [],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
    }

    public function testEnvInvalidValueError() : void
    {
        $this->unsetAllEnv();

        $this->expectException(FrigateException::class);
        $this->expectExceptionCode(FrigateException::CODE_FRIGATE_ENV_ERROR);
        $this->expectExceptionMessageMatches("/FRIGATE_EXPOSE_ERRORS/");

        App::init(
            root: __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.not_bool" ],
            extra_env : [],
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
    }

    public function testEnvInvalidValueApplicationEnvError() : void
    {
        $this->unsetAllEnv();

        $this->expectException(FrigateException::class);
        $this->expectExceptionCode(FrigateException::CODE_FRIGATE_ENV_ERROR);
        $this->expectExceptionMessageMatches("/MY_ENV_VAR3/");

        App::$application_env = [
            "MY_ENV_VAR"    => "not-empty",
            "MY_ENV_VAR2"   => "string",
            "MY_ENV_VAR3"   => "int",
            "MY_ENV_VAR4"   => "bool"
        ];

        $expected = [
            "MY_ENV_VAR"    => "I'm not empty",
            "MY_ENV_VAR2"   => "I'm a string",
            "MY_ENV_VAR3"   => "should be an int", // This is the error
            "MY_ENV_VAR4"   => "true"
        ];

        App::init(
            root: __DIR__,
            env : [ self::ENV_FOLDER , ".env.test.working" ],
            extra_env : $expected,
            start_session : false,
            start_page_buffer : false,
            adjust_ini : false
        );
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
