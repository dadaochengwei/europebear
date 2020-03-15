<?php
/**
 * Created by PhpStorm.
 * User: Tianjun
 * Date: 2020/3/15
 * Time: 20:00
 */

namespace dadaochengwei\europebear;


class Container
{
    static $registry = [];

    static function bind($name, \Closure $closure)
    {
        self::$registry[$name] = $closure;
    }

    static function make($name)
    {
        $closure = self::$registry[$name];
        return $closure();
    }
}