<?php

namespace Modufolio\Toolkit;

/**
 * Timer class
 *
 * @package   Modufolio Toolkit
 * @author    Maarten Thiebou
 * @copyright Modufolio
 * @license   https://opensource.org/licenses/MIT
 */
class Timer
{
    public static array $timers = [];

    public static function get(string $name, $decimals = 2): float
    {
       if(!isset(self::$timers[$name])) {
           return 0;
       }

       return number_format(static::$timers[$name] * 1000, $decimals);
    }

    public static function start(string $name): void
    {
        static::$timers[$name] = microtime(true);
    }

    public static function stop(string $name): void
    {
        static::$timers[$name] = microtime(true) - static::$timers[$name];
    }

    public static function reset(string $name): void
    {
        static::$timers[$name] = 0;
    }


}