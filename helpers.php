<?php

if (! function_exists('is_bit')) {
    /**
     * Determine if the given value is a bit.
     *
     * @param  mixed  $value
     * @return bool
     */
    function is_bit($value): bool
    {
        return ($value === 0 || $value === 1);
    }
}


if (! function_exists('is_blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
     * @return bool
     */
    function is_blank($value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (! function_exists('is_date')) {
    /**
     * Determine if the given value is a date.
     *
     * @param  mixed  $value
     * @return bool
     */
    function is_date($value): bool
    {
        $date = date_parse($value);

        return $date !== false && $date['error_count'] === 0 && $date['warning_count'] === 0;
    }
}

if (! function_exists('is_json')) {
    /**
     * Determine if the given value is a valid json string.
     * @param  mixed  $value
     * @return bool
     */
    function is_json($value): bool
    {
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (! function_exists('is_xml')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param string $xml
     * @return bool
     */
    function is_xml(string $xml): bool
    {
        $prev = libxml_use_internal_errors(true);

        $doc = simplexml_load_string($xml);
        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($doc === false || !empty($errors)) {
            return false;
        }

        return true;
    }
}

