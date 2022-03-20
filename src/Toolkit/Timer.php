<?php

namespace Modufolio\Toolkit;

class Timer
{

    public static function getExecutionTime($decimals = 2): string
    {
        $time = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
        return number_format($time * 1000, $decimals);
    }
}