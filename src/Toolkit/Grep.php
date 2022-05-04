<?php

namespace Modufolio\Toolkit;

class Grep
{

    /**
     * Return array entries that contains the needle
     * @param string $needle
     * @param array $haystack
     * @return array|false
     */
    public static function contains(string $needle, array $haystack)
    {
        return preg_grep("/$needle/i", $haystack);
    }

    /**
     * Return array entries that contains the needle
     * @param string $needle
     * @param array $haystack
     * @return array|false
     */
    public static function startsWith(string $needle, array $haystack)
    {
        return preg_grep("/^$needle/i", $haystack);
    }

}