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

        // echo $tree.PHP_EOL;

        $expected = [
            //path, name, exec, context
            ["/", "/", "root", []],
            ["/test", "test", "test", []],
            ["/test/1", "1", "test-1", []],
            ["/program/1", "{id}", "program", ["id" => 1]],
            ["/program/1/episode/1", "{episode}", "episode", ["id" => 1, "episode" => 1]],
            ["/program/dashboard", "dashboard", "dashboard", []],
        ];

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
        }
    }

    public function testOverridePath() : void {

        $tree = new PathTree();
        $tree->define("filter/text/?with/?keywords", "expression1"); // Will also expand to filter/text
        $tree->define("filter/text", "expression2"); // Redefine the path will throw an exception

        $expected = [
            //path, name, exec, context
            ["filter/text", "text", "expression2", []],
            ["filter/text/with", "with", "expression1", []],
            ["filter/text/with/keywords", "keywords", "expression1", []],
        ];

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
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

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path, $default_context);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
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

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
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

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
        }
    }

    public function testArgTypePath() : void 
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

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
        }
    }

    public function testExtendArgTypePath() : void 
    {
        $tree = new PathTree();
        $tree->define("files/{file:path}", "expression");
        $tree->define("files/folder", "expression"); // folder will have precedence over path

        $expected = [
            ["files/storage.txt", "{file}", "expression", ["file" => "storage.txt"]],
            ["files/other/file.txt", "{file}", "expression", ["file" => "other/file.txt"]],
            ["files/folder", "folder", "expression", []],
            ["files/folder/one", null, null, []],
            ["files/folder/storage.txt", null, null, []],
        ];

        foreach ($expected as $expect) {
            [$path, $expect_name, $expect_exp, $expect_context] = $expect;
            [$branch, $context] = $tree->eval($path);
            $this->assertEquals($expect_name, $branch?->name);
            $this->assertEquals($expect_exp, $branch?->exp);
            $this->assertEquals($expect_context, $context);
        }
    }

    public function testShadowExpressions() : void 
    {
        $tree = new PathTree();
        $tree->define("api^/test/find^/another", "expression");

        // Make sure the shadow expressions cannot be accessed directly:
        $this->assertNull($tree->get("api"));
        $this->assertNull($tree->get("api/test"));
        $this->assertNull($tree->get("api/test/find"));
        
        // Only another is a valid path:
        $another = $tree->get("api/test/find/another");
        $this->assertEquals("expression", $another?->exp);
        
        // Another should have 2 shadow expressions:
        $all_shadows = $another->getShadowExpressions();
        $this->assertEquals(
            ["expression","expression"], 
            $all_shadows
        );
        
        // Same but with variables and split:
        $tree = new PathTree();
        $tree->define("api^", "expression_api");
        $tree->define("api/{action}", "expression_api_action");
        $tree->define("api/{action}^", "expression_api_action_shadow");
        $tree->define("api/{action}/{id:int}", "expression_api_action_id");

        $this->assertNull($tree->eval("api")[0]);
        [$action, $context] = $tree->eval("api/act");
        [$action_id, $context_id] = $tree->eval("api/act/1");
        $this->assertEquals("expression_api_action", $action?->exp);
        $this->assertCount(2, $action->getShadowExpressions());
        $this->assertEquals("expression_api_action_id", $action_id?->exp);
        $this->assertCount(2, $action_id->getShadowExpressions());
        $this->assertEquals([
            "expression_api", "expression_api_action_shadow"
        ], $action_id->getShadowExpressions());
        
    }

    public function testShadowExpressionOnRoot() : void
    {

        $tree = new PathTree();
        $tree->define("/", "exp");
        $tree->define("/^", "exp1");
        $tree->define("^", "exp2");
        $tree->define("/^/test", "exp3");
        $tree->define("^/test", "exp4");
        $tree->define("/^/test/another", "exp5");
        $tree->define("^/test/another", "exp6");
        $tree->define("/^/test/another/{id:int}", "exp7");

        // echo $tree.PHP_EOL;
        
        $root = $tree->eval("/")[0];
        $this->assertCount(7, $root->getShadowExpressions());
        
        [$final, $context] = $tree->eval("/test/another/1");
        $shadows = $final?->getShadowExpressions() ?? [];
        $this->assertEquals("exp7", $final?->exp);
        $this->assertCount(7, $shadows);
        $this->assertEquals([ "exp1", "exp2", "exp3", "exp4", "exp5", "exp6", "exp7" ], $shadows);
        $this->assertEquals([ "id" => 1 ], $context);
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

    public static function setUpBeforeClass() : void
    {
        return;
    }

    public static function tearDownAfterClass() : void
    {
        return;
    }
}
