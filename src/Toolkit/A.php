<?php

namespace Modufolio\Toolkit;

use Exception;

/**
 * Most of the methods in this file come from illuminate/support and getkirby/kirby
 * thanks to Laravel Team and Kirby Team.
 */

class A
{
    /**
     * Appends the given array
     *
     * @param array $array
     * @param array $append
     * @return array
     */
    public static function append(array $array, array $append): array
    {
        return $array + $append;
    }

    /**
     * Recursively loops through the array and
     * resolves any item defined as `Closure`,
     * applying the passed parameters
     *
     * @param array $array
     * @param mixed ...$args Parameters to pass to the closures
     * @return array
     */
    public static function apply(array $array, ...$args): array
    {
        array_walk_recursive($array, function (&$item) use ($args) {
            if (is_a($item, 'Closure')) {
                $item = $item(...$args);
            }
        });

        return $array;
    }

    /**
     * Returns the average value of an array
     *
     * @param array $array The source array
     * @param int $decimals The number of decimals to return
     * @return float The average value
     */
    public static function average(array $array, int $decimals = 0): float
    {
        return round((array_sum($array) / sizeof($array)), $decimals);
    }

    /**
     * Return array entries that contain the needle
     * @param string $needle
     * @param array $haystack
     * @return array|null
     */
    public static function contains(string $needle, array $haystack): ?array
    {
        return preg_grep("/$needle/i", $haystack) ?? null;
    }

    /**
     * Cross join the given arrays, returning all possible permutations.
     *
     * @param array ...$arrays
     */
    public static function crossJoin(...$arrays): array
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }
            $results = $append;
        }
        return $results;
    }
    
    /**
     * Find duplicates
     *
     * @param array $array
     * @return array
     */
    public static function duplicates(array $array): array
    {
        return array_unique(array_diff_assoc($array, array_unique($array)));
    }



    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param array $array
     * @return array
     */
    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    public static function dot(array $array, string $prepend = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }
        return $results;
    }

    /**
     * Return array entries that end with the needle
     * @param string $needle
     * @param array $haystack
     * @return array|null
     */
    public static function endsWith(string $needle, array $haystack): ?array
    {
        return preg_grep("/$needle$/i", $haystack) ?? null;
    }

    /**
     * Merges arrays recursively
     *
     * @param array ...$arrays
     * @return array
     */
    public static function extend(...$arrays): array
    {
        return array_merge_recursive(...$arrays);
    }

    /**
     * Fills an array up with additional elements to certain amount.
     *
     * @param array $array The source array
     * @param int $limit The number of elements the array should
     *                   contain after filling it up.
     * @param mixed $fill The element, which should be used to
     *                    fill the array
     * @return array The filled-up result array
     */
    public static function fill(array $array, int $limit, $fill = 'placeholder'): array
    {
        if (count($array) < $limit) {
            $diff = $limit - count($array);
            for ($x = 0; $x < $diff; $x++) {
                $array[] = $fill;
            }
        }
        return $array;
    }

    /**
     * Filter the array using the given callback
     * using both value and key
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public static function filter(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Returns the first element of an array
     *
     * @param array $array The source array
     * @return mixed The first element
     */
    public static function first(array $array)
    {
        return array_shift($array);
    }



    /**
     * Gets an element of an array by key
     *
     * @param array $array The source array
     * @param mixed $key The key to look for
     * @param mixed $default Optional default value, which should be
     *                       returned if no element has been found
     * @return mixed
     */
    public static function get(array $array, $key, $default = null)
    {
        if (is_array($array) === false) {
            return $array;
        }

        // return the entire array if the key is null
        if ($key === null) {
            return $array;
        }

        // get an array of keys
        if (is_array($key) === true) {
            $result = [];
            foreach ($key as $k) {
                $result[$k] = static::get($array, $k, $default);
            }
            return $result;
        }

        if (isset($array[$key]) === true) {
            return $array[$key];
        }

        // extract data from nested array structures using the dot notation
        if (strpos($key, '.') !== false) {
            $keys     = explode('.', $key);
            $firstKey = array_shift($keys);

            // if the input array also uses dot notation, try to find a subset of the $keys
            if (isset($array[$firstKey]) === false) {
                $currentKey = $firstKey;

                while ($innerKey = array_shift($keys)) {
                    $currentKey .= '.' . $innerKey;

                    // the element needs to exist and also needs to be an array; otherwise
                    // we cannot find the remaining keys within it (invalid array structure)
                    if (isset($array[$currentKey]) === true && is_array($array[$currentKey]) === true) {
                        // $keys only holds the remaining keys that have not been shifted off yet
                        return static::get($array[$currentKey], implode('.', $keys), $default);
                    }
                }

                // searching through the full chain of keys wasn't successful
                return $default;
            }

            // if the input array uses a completely nested structure,
            // recursively progress layer by layer
            if (is_array($array[$firstKey]) === true) {
                return static::get($array[$firstKey], implode('.', $keys), $default);
            }

            // the $firstKey element was found, but isn't an array, so we cannot
            // find the remaining keys within it (invalid array structure)
            return $default;
        }

        return $default;
    }

    /**
     * Function that groups an array of associative arrays by some key.
     * these will contain the original values.
     *
     * @param string $key The key to group by
     * @param array $array The array to group
     * @return array
     */
    public static function groupBy(string $key, array $array): array
    {
        $output = [];
        foreach ($array as $a)
        {
            if(array_key_exists($key, $a)){
                $output[$a[$key]][] = $a;
            }
        }
        return $output;
    }

    /**
     * Determines if an array is associative.
     *
     * An array is "associative" if it doesn't have sequential numerical keys beginning with zero.
     *
     * @param  array  $array
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        $keys = array_keys($array);

        return array_keys($keys) !== $keys;
    }

    /**
     * Determines if an array is a list.
     *
     * An array is a "list" if all array keys are sequential integers starting from 0 with no gaps in between.
     *
     * @param array $array
     * @return bool
     */
    public static function isList(array $array): bool
    {
        return ! self::isAssoc($array);
    }

    /**
     * Checks whether an array is associative or not
     *
     * @param array $array The array to analyze
     * @return bool true: The array is associative false: It's not
     */
    public static function isAssociative(array $array): bool
    {
        return ctype_digit(implode('', array_keys($array))) === false;
    }

    /**
     * @param mixed $value
     * @param mixed $separator
     * @return string
     */
    public static function join($value, $separator = ', ')
    {
        if (is_string($value) === true) {
            return $value;
        }
        return implode($separator, $value);
    }



    /**
     * Returns the last element of an array
     *
     * @param array $array The source array
     * @return mixed The last element
     */
    public static function last(array $array)
    {
        return array_pop($array);
    }



    /**
     * A simple wrapper around array_map
     * with a sane argument order
     *
     * @param array $array
     * @param callable $map
     * @return array
     */
    public static function map(array $array, callable $map): array
    {
        return array_map($map, $array);
    }

    const MERGE_OVERWRITE = 0;
    const MERGE_APPEND    = 1;
    const MERGE_REPLACE   = 2;

    /**
     * Merges arrays recursively
     *
     * @param array $array1
     * @param array $array2
     * @param int $mode Behavior for elements with numeric keys;
     *                  A::MERGE_APPEND:    elements are appended, keys are reset;
     *                  A::MERGE_OVERWRITE: elements are overwritten, keys are preserved
     *                  A::MERGE_REPLACE:   non-associative arrays are completely replaced
     * @return array
     */
    public static function merge(array $array1, array $array2, int $mode = A::MERGE_APPEND): array
    {
        $merged = $array1;

        if (static::isAssociative($array1) === false && $mode === static::MERGE_REPLACE) {
            return $array2;
        }

        foreach ($array2 as $key => $value) {

            // append to the merged array, don't overwrite numeric keys
            if (is_int($key) === true && $mode === static::MERGE_APPEND) {
                $merged[] = $value;

                // recursively merge the two array values
            } elseif (is_array($value) === true && isset($merged[$key]) === true && is_array($merged[$key]) === true) {
                $merged[$key] = static::merge($merged[$key], $value, $mode);

                // simply overwrite with the value from the second array
            } else {
                $merged[$key] = $value;
            }
        }

        if ($mode === static::MERGE_APPEND) {
            // the keys don't make sense anymore, reset them
            // array_merge() is the simplest way to renumber
            // arrays that have both numeric and string keys;
            // besides the keys, nothing changes here
            $merged = array_merge($merged, []);
        }

        return $merged;
    }

    /**
     * Move an array item to a new index
     *
     * @param array $array
     * @param int $from
     * @param int $to
     * @return array
     * @throws Exception
     */
    public static function move(array $array, int $from, int $to): array
    {
        $total = count($array);

        if ($from >= $total || $from < 0) {
            throw new Exception('Invalid "from" index');
        }

        if ($to >= $total || $to < 0) {
            throw new Exception('Invalid "to" index');
        }

        // remove the item from the array
        $item = array_splice($array, $from, 1);

        // inject it at the new position
        array_splice($array, $to, 0, $item);

        return $array;
    }

    /**
     * Checks for missing elements in an array
     *
     * @param array $array The source array
     * @param array $required An array of required keys
     * @return array An array of missing fields. If this
     *               is empty, nothing is missing.
     */
    public static function missing(array $array, array $required = []): array
    {
        return array_values(array_diff($required, array_keys($array)));
    }

    /**
     * Normalizes an array into a nested form by converting
     * dot notation in keys to nested structures
     *
     * @param array $array
     * @param array $ignore List of keys in dot notation that should
     *                      not be converted to a nested structure
     * @return array
     */
    public static function nest(array $array, array $ignore = []): array
    {
        // convert a simple ignore list to a nested $key => true array
        if (isset($ignore[0]) === true) {
            $ignore = array_map(fn () => true, array_flip($ignore));
            $ignore = A::nest($ignore);
        }

        $result = [];

        foreach ($array as $fullKey => $value) {
            // extract the first part of a multi-level key, keep the others
            $subKeys = explode('.', $fullKey);
            $key     = array_shift($subKeys);

            // skip the magic for ignored keys
            if (isset($ignore[$key]) === true && $ignore[$key] === true) {
                $result[$fullKey] = $value;
                continue;
            }

            // untangle elements where the key uses dot notation
            if (count($subKeys) > 0) {
                $value = static::nestByKeys($value, $subKeys);
            }

            // now recursively do the same for each array level if needed
            if (is_array($value) === true) {
                $value = static::nest($value, $ignore[$key] ?? []);
            }

            // merge arrays with previous results if necessary
            // (needed when the same keys are used both with and without dot notation)
            if (
                isset($result[$key]) === true &&
                is_array($result[$key]) === true &&
                is_array($value) === true
            ) {
                $result[$key] = array_replace_recursive($result[$key], $value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Recursively creates a nested array from a set of keys
     * with a key on each level
     *
     * @param mixed $value Arbitrary value that will end up at the bottom of the tree
     * @param array $keys List of keys to use sorted from the topmost level
     * @return array|mixed Nested array or (if `$keys` is empty) the input `$value`
     */
    public static function nestByKeys($value, array $keys)
    {
        // shift off the first key from the list
        $firstKey = array_shift($keys);

        // stop further recursion if there are no more keys
        if ($firstKey === null) {
            return $value;
        }

        // return one level of the output tree, recurse further
        return [
            $firstKey => static::nestByKeys($value, $keys)
        ];
    }

    /**
     * Get a subset of the items from the given array.
     *
     * @param array $array
     * @param  array|string  $keys
     * @return array
     */
    public static function only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Plucks a single column from an array
     *
     * @param array $array The source array
     * @param string $key The key name of the column to extract
     * @return array The result array with all values
     *               from that column.
     */
    public static function pluck(array $array, string $key): array
    {
        $output = [];
        foreach ($array as $a) {
            if (isset($a[$key]) === true) {
                $output[] = $a[$key];
            }
        }

        return $output;
    }

    /**
     * Prepends the given array
     *
     * @param array $array
     * @param array $prepend
     * @return array
     */
    public static function prepend(array $array, array $prepend): array
    {
        return $prepend + $array;
    }

    /**
     * Convert the array into a query string.
     */
    public static function query(array $array): string
    {
        return http_build_query($array, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Deep search for a value in an array
     *
     * @param $key
     * @param array $array
     * @return array|null
     */
    public static function search($key, array $array): ?array
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach ($array as $value) {
            if (is_array($value) && ($result = self::search($key, $value))) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Sorts a multi-dimensional array by a certain column
     *
     * @param array $array The source array
     * @param string $field The name of the column
     * @param string $direction desc (descending) or asc (ascending)
     * @param int $method A PHP sort method flag or 'natural' for
     *                    natural sorting, which is not supported in
     *                    PHP by sort flags
     * @return array The sorted array
     */
    public static function sort(array $array, string $field, string $direction = 'desc', $method = SORT_REGULAR): array
    {
        $direction = strtolower($direction) === 'desc' ? SORT_DESC : SORT_ASC;
        $helper    = [];
        $result    = [];

        // build the helper array
        foreach ($array as $key => $row) {
            $helper[$key] = $row[$field];
        }

        // natural sorting
        if ($direction === SORT_DESC) {
            arsort($helper, $method);
        } else {
            asort($helper, $method);
        }

        // rebuild the original array
        foreach ($helper as $key => $val) {
            $result[$key] = $array[$key];
        }

        return $result;
    }

    /**
     * Shuffles an array and keeps the keys
     *
     * @param array $array The source array
     * @return array The shuffled result array
     */
    public static function shuffle(array $array): array
    {
        $keys = array_keys($array);
        $new  = [];

        shuffle($keys);

        // resort the array
        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }

        return $new;
    }

    /**
     * Return array entries that start with the needle
     * @param string $needle
     * @param array $haystack
     * @return array|null
     */
    public static function startsWith(string $needle, array $haystack): ?array
    {
        return preg_grep("/^$needle/i", $haystack) ?? null;
    }


    /**
     * Update an array with a second array
     * The second array can contain callbacks as values,
     * which will get the original values as argument
     *
     * @param array $array
     * @param array $update
     * @return array
     */
    public static function update(array $array, array $update): array
    {
        foreach ($update as $key => $value) {
            if (is_a($value, 'Closure') === true) {
                $array[$key] = call_user_func($value, static::get($array, $key));
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    public static function unique(array $array): array
    {
        return array_unique($array);
    }


    /**
     * Remove key(s) from an array
     *
     * @param array $array
     * @param int|string|array $keys
     * @return array
     */
    public static function without(array $array, $keys): array
    {
        if (is_int($keys) || is_string($keys)) {
            $keys = static::wrap($keys);
        }

        return static::filter($array, function ($value, $key) use ($keys) {
            return in_array($key, $keys, true) === false;
        });
    }


    /**
     * Filter the array using the given callback.
     */
    public static function where(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Wraps the given value in an array
     * if it's not an array yet.
     *
     * @param $value
     * @return array
     */
    public static function wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }
        return ! is_array($value) ? [$value] : $value;
    }


}