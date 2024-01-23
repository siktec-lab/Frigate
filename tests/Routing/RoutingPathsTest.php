<?php

declare(strict_types=1);

namespace QDM\Tests\Visibility;

use PHPUnit\Framework\TestCase;
use Frigate\Routing\Paths\PathTree;

class RoutingPathsTest extends TestCase
{
    public function setUp() : void
    {
        return;
    }

    public function tearDown() : void
    {
        return;
    }

    public function testRoutingBasic() : void
    {
        
        $tree = new PathTree(); // Base tree
        $tree->define("/", "root"); // Define root
        $tree->define("/test", "test"); // Define test
        $tree->define("/test/1", "test-1"); // Define test1
        $tree->define("/program/{id:int}", "program"); // Define program
        $tree->define("/program/{id:int}/episode/{episode:int}", "episode");
        $tree->define("/program/dashboard", "dashboard");
        $expected = [
            //path, name, exec, context
            ["/", "/", "root", []],
            ["/test", "test", "test", []],
            ["/test/1", "1", "test-1", []],
            ["/program/1", "{id}", "program", ["id" => 1]],
            ["/program/1/episode/1", "{episode}", "episode", ["id" => 1, "episode" => 1]],
            ["/program/dashboard", "dashboard", "dashboard", []],
        ];

        foreach ($expected as $exp) {
            [$path, $exp_name, $exp_exec, $exp_context] = $exp;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($exp_name, $branch?->name);
            $this->assertEquals($exp_exec, $branch?->exec);
            $this->assertEquals($exp_context, $context);
        }
    }

    public function testContext() : void
    {
        $tree = new PathTree(); // Base tree
        $tree->define("/{id:int}", "program"); // Define program
        $tree->define("/{id:int}/episode/{episode:int}", "episode");

        $default_context = ["id" => 1000, "episode" => 1000];
        $expected = [
            //path, name, exec, context
            ["/5", "{id}", "program", [ "id" => 5, "episode" => 1000 ]],
            [ "/9/episode/19", "{episode}", "episode", [ "id" => 9, "episode" => 19 ]],
            [ "/9/episode/19/notset/3", null, null, [ "id" => 9, "episode" => 19 ]], // Latest context was 9, 19
        ];

        foreach ($expected as $exp) {
            [$path, $exp_name, $exp_exec, $exp_context] = $exp;
            [$branch, $context] = $tree->eval($path, $default_context);
            $this->assertEquals($exp_name, $branch?->name);
            $this->assertEquals($exp_exec, $branch?->exec);
            $this->assertEquals($exp_context, $context);
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
