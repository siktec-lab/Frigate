<?php

declare(strict_types=1);

namespace Frigate\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Frigate\FrigateApp as App;
use Frigate\Routing\Router;
use Frigate\Routing\Routes\Route;
use Frigate\Routing\Http\Methods;

class RoutingInitTest extends TestCase
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

        App::init( 
            root : __DIR__,
            env : [ __DIR__ . "/../resources/env" , ".env.test.working" ],
            extra_env: [ "FRIGATE_DEBUG_ROUTER" => "true" ],
            start_session: false,
            start_page_buffer: false,
            adjust_ini: false
        );

        Router::init( load_request: false); // Should be in debug mode

        $this->assertTrue( Router::debug() );

        Router::init( load_request: false, debug: false ); // Should disable debug mode manually

        $this->assertFalse( Router::debug() );

        $this->unsetAllEnv();

        App::init( 
            root : __DIR__,
            env : [ __DIR__ . "/../resources/env" , ".env.test.working" ],
            extra_env: [ "FRIGATE_DEBUG_ROUTER" => "off" ], // Should disable debug mode
            start_session: false,
            start_page_buffer: false,
            adjust_ini: false
        );

        Router::init(load_request: false); // Should not be in debug mode

        $this->assertFalse( Router::debug() );

    }

    public function testDefineRouteGet() : void 
    {
        $this->unsetAllEnv();
        App::init( 
            root : __DIR__,
            env : [ __DIR__ . "/../resources/env" , ".env.test.working" ],
            extra_env: [ "FRIGATE_DEBUG_ROUTER" => "true" ],
            start_session: false,
            start_page_buffer: false,
            adjust_ini: false
        );

        // Define a route
        Router::reset();
        Router::define( "GET", new Route( 
            path : "/test/{id:int}", 
            exp  : function() { return "test"; } 
        ));

        // Get the route
        $context = [];
        $branch = Router::getRouteBranch( "GET", "/test/21", $context);

        $this->assertNotNull( $branch );
        $this->assertEquals( "{id}", $branch->name );
        $this->assertEquals( 21, $context["id"] ?? null );

        // Not existing route
        $branch1 = Router::getRouteBranch( "GET", "/test/21/22");
        $branch2 = Router::getRouteBranch( "POST", "/test/21");
        $branch3 = Router::getRouteBranch( "GET", "/test/frigate");

        $this->assertNull( $branch1 );
        $this->assertNull( $branch2 );
        $this->assertNull( $branch3 );

    }

    public function testDefineRoutePost() : void 
    {
        $this->unsetAllEnv();
        App::init( 
            root : __DIR__,
            env : [ __DIR__ . "/../resources/env" , ".env.test.working" ],
            extra_env: [ "FRIGATE_DEBUG_ROUTER" => "true" ],
            start_session: false,
            start_page_buffer: false,
            adjust_ini: false
        );

        // Define a route
        Router::reset();
        Router::define(Methods::POST, new Route( 
            path : "/{name:str}/{age:int}", 
            exp  : function() { return "test"; } 
        ));

        // Get the route
        $context = [];
        $branch = Router::getRouteBranch(Methods::POST, "/test/32", $context);

        $this->assertNotNull( $branch );
        $this->assertEquals( "{age}", $branch->name );
        $this->assertEquals( 32, $context["age"] ?? null );
        $this->assertEquals( "test", $context["name"] ?? null );

        // Not existing route
        $branch1 = Router::getRouteBranch( "GET", "/test/21");
        $branch2 = Router::getRouteBranch( "POST", "/test/more_strings");
        $branch3 = Router::getRouteBranch( "POST", "/test/21/22");

        $this->assertNull( $branch1 );
        $this->assertNull( $branch2 );
        $this->assertNull( $branch3 );

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
