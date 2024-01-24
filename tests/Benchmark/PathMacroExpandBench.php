<?php

namespace Frigate\Tests\Benchmark;

/*
    All of these methods are fast almost the same, but the most readable is the array_map method

    Fastest to slowest:

    1. expandWithForeachKeepLast
    2. expandWithArrayMapKeepLast <-- We choose this one because its more readable
    3. expandWithForeachEnd
    4. expandWithForeachShiftCount

Benchmark with:

PHP 8.2.0 (cli) (built: Dec  6 2022 15:31:23) (ZTS Visual C++ 2019 x64)

Warmup 0: 
+----------------------+-------------------------------+-----+------+-----+----------+---------+---------+
| benchmark            | subject                       | set | revs | its | mem_peak | mode    | rstdev  |
+----------------------+-------------------------------+-----+------+-----+----------+---------+---------+
| PathMacroExpandBench | benchConsumeArrayMap          |     | 3    | 50  | 1.741mb  | 4.054μs | ±20.80% |
| PathMacroExpandBench | benchConsumeForeachKeepLast   |     | 3    | 50  | 1.742mb  | 4.282μs | ±90.16% |
| PathMacroExpandBench | benchConsumeForeachEnd        |     | 3    | 50  | 1.741mb  | 4.494μs | ±26.36% |
| PathMacroExpandBench | benchConsumeForeachShiftCount |     | 3    | 50  | 1.742mb  | 5.449μs | ±60.46% |
+----------------------+-------------------------------+-----+------+-----+----------+---------+---------+

Warmup 2:
+----------------------+-------------------------------+-----+------+-----+----------+---------+----------+
| benchmark            | subject                       | set | revs | its | mem_peak | mode    | rstdev   |
+----------------------+-------------------------------+-----+------+-----+----------+---------+----------+
| PathMacroExpandBench | benchConsumeArrayMap          |     | 3    | 50  | 1.741mb  | 0.539μs | ±60.84%  |
| PathMacroExpandBench | benchConsumeForeachKeepLast   |     | 3    | 50  | 1.742mb  | 0.327μs | ±96.61%  |
| PathMacroExpandBench | benchConsumeForeachEnd        |     | 3    | 50  | 1.741mb  | 0.496μs | ±138.29% |
| PathMacroExpandBench | benchConsumeForeachShiftCount |     | 3    | 50  | 1.742mb  | 0.337μs | ±59.54%  |
+----------------------+-------------------------------+-----+------+-----+----------+---------+----------+

Single Shot:
+----------------------+-------------------------------+-----+------+-----+----------+----------+--------+
| benchmark            | subject                       | set | revs | its | mem_peak | mode     | rstdev |
+----------------------+-------------------------------+-----+------+-----+----------+----------+--------+
| PathMacroExpandBench | benchConsumeArrayMap          |     | 1    | 1   | 1.741mb  | 10.000μs | ±0.00% |
| PathMacroExpandBench | benchConsumeForeachKeepLast   |     | 1    | 1   | 1.742mb  | 10.000μs | ±0.00% |
| PathMacroExpandBench | benchConsumeForeachEnd        |     | 1    | 1   | 1.741mb  | 10.000μs | ±0.00% |
| PathMacroExpandBench | benchConsumeForeachShiftCount |     | 1    | 1   | 1.742mb  | 17.000μs | ±0.00% |
+----------------------+-------------------------------+-----+------+-----+----------+----------+--------+

*/
class PathMacroExpandBench
{

    public const DEBUG = true;

    public static function debug(string $from, mixed $arg) : void 
    {
        if (self::DEBUG) {
            echo PHP_EOL. "Debug -> {$from}" . PHP_EOL;
            print_r($arg);
        }
    }

    public function expandWithArrayMapKeepLast(string $path) : array
    {
        $last = "";
        return array_map(function(string $slice) use (&$last) {
            return $last = $last . $slice;
        }, explode('?', $path));
    }

    public function expandWithForeachKeepLast(string $path) : array
    {
        $sliced = explode('?', $path);
        $last = "";
        foreach ($sliced as $slice) {
            $paths[] = $last = $last . $slice;
        }
        return $paths;
    }

    public function expandWithForeachEnd(string $path) : array
    {
        $parts = explode('?', $path);
        $paths = [];
        foreach ($parts as $slice) {
            $paths[] = end($paths) . $slice;
        }
        return $paths;
    }

    public function expandWithForeachShiftCount(string $path) : array
    {
        $sliced = explode('?', $path);
        $paths = [ array_shift($sliced) ];
        foreach ($sliced as $slice) {
            $paths[] = end($paths). $slice; // last inserted path + slice
        }
        return $paths;
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     * @warmup(0)
     */
    public function benchConsumeArrayMap() : void
    {
        
        $return = $this->expandWithArrayMapKeepLast("users/?{name:string}/{id:int}/?{action}");
        // self::debug(__METHOD__, $return);
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     * @warmup(0)
     */
    public function benchConsumeForeachKeepLast() : void
    {
        $return = $this->expandWithForeachKeepLast("users/?{name:string}/{id:int}/?{action}");
        // self::debug(__METHOD__, $return);
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     * @warmup(0)
     */
    public function benchConsumeForeachEnd() : void
    {
        $return = $this->expandWithForeachEnd("users/?{name:string}/{id:int}/?{action}");
        // self::debug(__METHOD__, $return);
    }

    /**
     * @Revs(1)
     * @Iterations(1)
     * @warmup(0)
     */
    public function benchConsumeForeachShiftCount() : void
    {
        $return = $this->expandWithForeachShiftCount("users/?{name:string}/{id:int}/?{action}");
        // self::debug(__METHOD__, $return);
    }
}