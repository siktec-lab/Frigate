<?php

declare(strict_types=1);

namespace Frigate\Tests\Routing;

use PHPUnit\Framework\TestCase;
use Frigate\Exceptions;
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
        $tree = new PathTree();
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

    public function testMultipleArgsInPath() : void 
    {
        $tree = new PathTree(); // Base tree
        $tree->define("users/{name:string}/{id:int}/{action}", "expression");

        $expected = [
            //path, name, exec, context
            ["users/John/1/edit", "{action}", "expression", ["name" => "john", "id" => 1, "action" => "edit"]],
            ["users/John/1", null, null, ["name" => "john", "id" => 1]],
            ["users/John", null, null, ["name" => "john"]],
            ["users", null, null, []],
        ];

        foreach ($expected as $exp) {
            [$path, $exp_name, $exp_exec, $exp_context] = $exp;
            [$branch, $context] = $tree->eval($path);
            // printf("Path: %s\n    - Name: %s\n    - Exec: %s\n    - Context: %s\n", 
            //     $path, $branch?->name, $branch?->exec, json_encode($context)
            // );
            $this->assertEquals($exp_name, $branch?->name);
            $this->assertEquals($exp_exec, $branch?->exec);
            $this->assertEquals($exp_context, $context);
        }

        // With conditional: //TODO: implement this conditional group....
        // $tree->define("users/?{name:string}/?{id:int}/?{action}", "expression");
    }

    public function testPathOptionalVariationMacro() : void 
    {
        $tree = new PathTree();
        $tree->define("users/?{name:string}/{id:int}/?{action}", "expression");

        $expected = [
            //path, name, exec, context
            ["users/John/1/edit", "{action}", "expression", ["name" => "john", "id" => 1, "action" => "edit"]],
            ["users/John/1", "{id}", "expression", ["name" => "john", "id" => 1]],
            ["users/John", null, null, ["name" => "john"]],
            ["users", "users", "expression", []],
        ];

        foreach ($expected as $exp) {
            [$path, $exp_name, $exp_exec, $exp_context] = $exp;
            [$branch, $context] = $tree->eval($path);
            // printf("Path: %s\n    - Name: %s\n    - Exec: %s\n    - Context: %s\n", 
            //     $path, $branch?->name, $branch?->exec, json_encode($context)
            // );
            $this->assertEquals($exp_name, $branch?->name);
            $this->assertEquals($exp_exec, $branch?->exec);
            $this->assertEquals($exp_context, $context);
        }
    }

    public function testRemainOfPartType() : void 
    {
        $tree = new PathTree();
        $tree->define("files/{file:path}", "expression");

        $expected = [
            //path, name, exec, context
            ["files/John/1/edit", "{file}", "expression", ["file" => "john/1/edit"]],
            ["files/John/1", "{file}", "expression", ["file" => "john/1"]],
            ["files/John", "{file}", "expression", ["file" => "john"]],
            ["files", null, null, []],
        ];

        foreach ($expected as $exp) {
            [$path, $exp_name, $exp_exec, $exp_context] = $exp;
            [$branch, $context] = $tree->eval($path);

            // printf("Path: %s\n    - Name: %s\n    - Exec: %s\n    - Context: %s\n", 
            //     $path, $branch?->name, $branch?->exec, json_encode($context)
            // );
            $this->assertEquals($exp_name, $branch?->name);
            $this->assertEquals($exp_exec, $branch?->exec);
            $this->assertEquals($exp_context, $context);
        }
    }

    public function testRoutePathsAfterStopageExceptions() : void {

        // ----------------- Test Extra path after path type -----------------
        $this->expectException(Exceptions\FrigatePathException::class);
        $this->expectExceptionCode(Exceptions\FrigatePathException::CODE_FRIGATE_EXTRA_PATH_AFTER_PATH_TYPE);
        $tree = new PathTree();
        //Not valid after stopage path we have extra path
        $tree->define("files/{token}/find/{file:path}/{size}", "expression");

    }

    public function testRoutePathsSameLevelArgument() : void {

        // ----------------- Test Multiple args in path level -----------------
        $this->expectException(Exceptions\FrigatePathException::class);
        $this->expectExceptionCode(Exceptions\FrigatePathException::CODE_FRIGATE_PATH_MULTIPLE_ARGS);
        $tree = new PathTree();
        // Same level arguments of the same type in path
        $tree->define("filter/{id:int}", "expression");
        $tree->define("filter/{limit:int}", "expression");
    }

    public function testRoutePathsSameException() : void {

        // ----------------- Test Multiple args in path level -----------------
        $this->expectException(Exceptions\FrigatePathException::class);
        $this->expectExceptionCode(Exceptions\FrigatePathException::CODE_FRIGATE_REDEFINE_PATH);
        $tree = new PathTree();
        $tree->define("filter/text/?with/?keywords", "expression"); // Will also expand to filter/text
        $tree->define("filter/text", "expression"); // Redefine the path will throw an exception
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
