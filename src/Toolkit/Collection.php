<?php

namespace Modufolio\Toolkit;

use Closure;
use Countable;
use Exception;

/**
 * The collection class provides a nicer
 * interface around arrays of arrays or objects,
 * with advanced filters, sorting, navigation and more.
 *
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier GmbH
 * @license   https://opensource.org/licenses/MIT
 */
class Collection extends Iterator implements Countable
{
    /**
     * All registered collection filters
     *
     * @var array
     */
    public static $filters = [];

    /**
     * Whether the collection keys should be
     * treated as case-sensitive
     *
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * Pagination object
     * @var Pagination
     */
    protected $pagination;

    /**
     * Magic getter function
     *
     * @param string $key
     * @param mixed $arguments
     * @return mixed
     */
    public function __call(string $key, $arguments)
    {
        return $this->__get($key);
    }

    /**
     * Constructor
     *
     * @param array $data
     * @param bool $caseSensitive Whether the collection keys should be
     *                            treated as case-sensitive
     */
    public function __construct(array $data = [], bool $caseSensitive = false)
    {
        $this->caseSensitive = $caseSensitive;
        $this->set($data);
    }

    /**
     * Improve var_dump() output
     *
     * @return array
     */
    public function __debugInfo(): array
    {
        return $this->keys();
    }

    /**
     * Low-level getter for elements
     *
     * @param mixed $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->caseSensitive === true) {
            return $this->data[$key] ?? null;
        }

        return $this->data[$key] ?? $this->data[strtolower($key)] ?? null;
    }

    /**
     * Low-level setter for elements
     *
     * @param string $key string or array
     * @param mixed $value
     * @return $this
     */
    public function __set(string $key, $value)
    {
        if ($this->caseSensitive === true) {
            $this->data[$key] = $value;
        } else {
            $this->data[strtolower($key)] = $value;
        }

        return $this;
    }

    /**
     * Makes it possible to echo the entire object
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Low-level element remover
     *
     * @param mixed $key the name of the key
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Appends an element
     *
     * @param mixed $key
     * @param mixed $item
     * @param mixed ...$args
     * @return $this
     */
    public function append(...$args)
    {
        if (count($args) === 1) {
            $this->data[] = $args[0];
        } elseif (count($args) > 1) {
            $this->set($args[0], $args[1]);
        }

        return $this;
    }

    /**
     * Creates chunks of the same size.
     * The last chunk may be smaller
     *
     * @param int $size Number of elements per chunk
     * @return static A new collection with an element for each chunk and
     *                a sub collection in each chunk
     */
    public function chunk(int $size)
    {
        // create a multidimensional array that is chunked with the given
        // chunk size keep keys of the elements
        $chunks = array_chunk($this->data, $size, true);

        // convert each chunk to a sub collection
        $collection = [];

        foreach ($chunks as $items) {
            // we clone $this instead of creating a new object because
            // different objects may have different constructors
            $clone = clone $this;
            $clone->data = $items;

            $collection[] = $clone;
        }

        // convert the array of chunks to a collection
        $result = clone $this;
        $result->data = $collection;

        return $result;
    }

    /**
     * Returns a cloned instance of the collection
     *
     * @return $this
     */
    public function clone()
    {
        return clone $this;
    }

    /**
     * Getter and setter for the data
     *
     * @param array|null $data
     * @return array|$this
     */
    public function data(array $data = null)
    {
        if ($data === null) {
            return $this->data;
        }

        // clear all previous data
        $this->data = [];

        // overwrite the data array
        $this->data = $data;

        return $this;
    }

    /**
     * Clone and remove all elements from the collection
     *
     * @return static
     */
    public function empty()
    {
        $collection = clone $this;
        $collection->data = [];

        return $collection;
    }

    /**
     * Adds all elements to the collection
     *
     * @param mixed $items
     * @return static
     */
    public function extend($items)
    {
        $collection = clone $this;
        return $collection->set($items);
    }

    /**
     * Filters elements by one of the
     * predefined filter methods, by a
     * custom filter function or an array of filters
     *
     * @param string|array|Closure $field
     * @param mixed ...$args
     * @return static
     */
    public function filter(Closure $closure)
    {
        $collection = clone $this;
        $collection->data = array_filter($collection->data, $closure);

        return $collection;
    }

    /**
     * Alias for `Modufolio\Toolkit\Collection::filter`
     *
     * @param Closure $closure
     * @return static
     */
    public function filterBy(Closure $closure)
    {
        return $this->filter($closure);
    }

    /**
     * Find one or multiple elements by id
     *
     * @param string ...$keys
     * @return mixed
     */
    public function find(...$keys)
    {
        if (count($keys) === 1) {
            if (is_array($keys[0]) === true) {
                $keys = $keys[0];
            } else {
                return $this->findByKey($keys[0]);
            }
        }

        $result = [];

        foreach ($keys as $key) {
            if ($item = $this->findByKey($key)) {
                if (is_object($item) && method_exists($item, 'id') === true) {
                    $key = $item->id();
                }
                $result[$key] = $item;
            }
        }

        $collection = clone $this;
        $collection->data = $result;
        return $collection;
    }

    /**
     * Find a single element by an attribute and its value
     *
     * @param string $attribute
     * @param mixed $value
     * @return mixed|null
     */
    public function findBy(string $attribute, $value)
    {
        foreach ($this->data as $item) {
            if ($this->getAttribute($item, $attribute) == $value) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Find a single element by key (id)
     *
     * @param string $key
     * @return mixed
     */
    public function findByKey(string $key)
    {
        return $this->get($key);
    }

    /**
     * Returns the first element
     *
     * @return mixed
     */
    public function first()
    {
        $array = $this->data;
        return array_shift($array);
    }

    /**
     * Returns the elements in reverse order
     *
     * @return static
     */
    public function flip()
    {
        $collection = clone $this;
        $collection->data = array_reverse($this->data, true);
        return $collection;
    }

    /**
     * Getter
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->__get($key) ?? $default;
    }

    /**
     * Extracts an attribute value from the given element
     * in the collection. This is useful if elements in the collection
     * might be objects, arrays or anything else and you need to
     * get the value independently from that. We use it for `filter`.
     *
     * @param array|object $item
     * @param string $attribute
     * @param bool $split
     * @param mixed $related
     * @return mixed
     */
    public function getAttribute($item, string $attribute, bool $split = false, $related = null)
    {
        $value = $this->{'getAttributeFrom' . gettype($item)}($item, $attribute);

        if ($split !== false) {
            return Str::split($value, $split === true ? ',' : $split);
        }

        if ($related !== null) {
            return Str::toType((string)$value, $related);
        }

        return $value;
    }


    /**
     * Groups the elements by a given field or callback function
     *
     * @param string|Closure $field
     * @param bool $i
     * @return Collection A new collection with an element for
     *                                   each group and a subcollection in
     *                                   each group
     * @throws Exception if $field is not a string nor a callback function
     */
    public function group($field, bool $i = true)
    {

        // group by field name
        if (is_string($field) === true) {
            return $this->group(function ($item) use ($field, $i) {
                $value = $this->getAttribute($item, $field);

                // ignore upper/lowercase for group names
                return $i === true ? Str::lower($value) : $value;
            });
        }

        // group via callback function
        if (is_callable($field) === true) {
            $groups = [];

            foreach ($this->data as $key => $item) {

                // get the value to group by
                $value = $field($item);

                // make sure that there's always a proper value to group by
                if (!$value) {
                    throw new Exception('Invalid grouping value for key: ' . $key);
                }

                // make sure we have a proper key for each group
                if (is_array($value) === true) {
                    throw new Exception('You cannot group by arrays or objects');
                } elseif (is_object($value) === true) {
                    if (method_exists($value, '__toString') === false) {
                        throw new Exception('You cannot group by arrays or objects');
                    } else {
                        $value = (string)$value;
                    }
                }

                if (isset($groups[$value]) === false) {
                    // create a new entry for the group if it does not exist yet
                    $groups[$value] = new static([$key => $item]);
                } else {
                    // add the element to an existing group
                    $groups[$value]->set($key, $item);
                }
            }

            return new Collection($groups);
        }

        throw new Exception('Can only group by string values or by providing a callback function');
    }

    /**
     * Alias for `Modufolio\Toolkit\Collection::group`
     *
     * @param string|Closure $field
     * @param bool $i
     * @return Collection A new collection with an element for
     *                                   each group and a sub collection in
     *                                   each group
     * @throws Exception
     */
    public function groupBy(...$args)
    {
        return $this->group(...$args);
    }

    /**
     * Returns a Collection with the intersection of the given elements
     * @param Collection $other
     * @return static
     *
     */
    public function intersection(Collection $other)
    {
        return $other->find($this->keys());
    }

    /**
     * Checks if there is an intersection between the given collection and this collection
     * @param Collection $other
     * @return bool
     *
     */
    public function intersects(Collection $other): bool
    {
        foreach ($this->keys() as $key) {
            if ($other->has($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the number of elements is zero
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Checks if the number of elements is even
     *
     * @return bool
     */
    public function isEven(): bool
    {
        return $this->count() % 2 === 0;
    }

    /**
     * Checks if the number of elements is more than zero
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Checks if the number of elements is odd
     *
     * @return bool
     */
    public function isOdd(): bool
    {
        return $this->count() % 2 !== 0;
    }

    /**
     * Returns the last element
     *
     * @return mixed
     */
    public function last()
    {
        $array = $this->data;
        return array_pop($array);
    }

    /**
     * Returns a new object with a limited number of elements
     *
     * @param int $limit The number of elements to return
     * @return static
     */
    public function limit(int $limit)
    {
        return $this->slice(0, $limit);
    }

    /**
     * Map a function to each element
     *
     * @param callable $callback
     * @return $this
     */
    public function map(callable $callback)
    {
        $this->data = array_map($callback, $this->data);
        return $this;
    }

    /**
     * Returns the nth element from the collection
     *
     * @param int $n
     * @return mixed
     */
    public function nth(int $n)
    {
        return array_values($this->data)[$n] ?? null;
    }

    /**
     * Returns a Collection without the given element(s)
     *
     * @param string ...$keys any number of keys, passed as individual arguments
     * @return static
     */
    public function not(...$keys)
    {
        $collection = clone $this;
        foreach ($keys as $key) {
            unset($collection->data[$key]);
        }
        return $collection;
    }

    /**
     * Returns a new object starting from the given offset
     *
     * @param int $offset The index to start from
     * @return static
     */
    public function offset(int $offset)
    {
        return $this->slice($offset);
    }

    /**
     * Add pagination
     *
     * @param array ...$arguments
     * @return static a sliced set of data
     */
    public function paginate(...$arguments)
    {
        $this->pagination = Pagination::for($this, ...$arguments);

        // slice and clone the collection according to the pagination
        return $this->slice($this->pagination->offset(), $this->pagination->limit());
    }

    /**
     * Get the previously added pagination object
     *
     * @return Pagination|null
     */
    public function pagination()
    {
        return $this->pagination;
    }

    /**
     * Extracts all values for a single field into
     * a new array
     *
     * @param string $field
     * @param string|null $split
     * @param bool $unique
     * @return array
     */
    public function pluck(string $field, string $split = null, bool $unique = false): array
    {
        $result = [];

        foreach ($this->data as $item) {
            $row = $this->getAttribute($item, $field);

            if ($split !== null) {
                $result = array_merge($result, Str::split($row, $split));
            } else {
                $result[] = $row;
            }
        }

        if ($unique === true) {
            $result = array_unique($result);
        }

        return array_values($result);
    }

    /**
     * Prepends an element to the data array
     *
     * @param mixed $key
     * @param mixed $item
     * @param mixed ...$args
     * @return $this
     */
    public function prepend(...$args)
    {
        if (count($args) === 1) {
            array_unshift($this->data, $args[0]);
        } elseif (count($args) > 1) {
            $data = $this->data;
            $this->data = [];
            $this->set($args[0], $args[1]);
            $this->data += $data;
        }

        return $this;
    }

    /**
     * Runs a combination of filter, sort, not,
     * offset, limit and paginate on the collection.
     * Any part of the query is optional.
     *
     * @param array $arguments
     * @return static
     */
    public function query(array $arguments = [])
    {
        $result = clone $this;

        if (isset($arguments['not']) === true) {
            $result = $result->not(...$arguments['not']);
        }

        if ($filters = $arguments['filterBy'] ?? $arguments['filter'] ?? null) {
            foreach ($filters as $filter) {
                if (
                    isset($filter['field']) === true &&
                    isset($filter['value']) === true
                ) {
                    $result = $result->filter(
                        $filter['field'],
                        $filter['operator'] ?? '==',
                        $filter['value']
                    );
                }
            }
        }

        if (isset($arguments['offset']) === true) {
            $result = $result->offset($arguments['offset']);
        }

        if (isset($arguments['limit']) === true) {
            $result = $result->limit($arguments['limit']);
        }

        if ($sort = $arguments['sortBy'] ?? $arguments['sort'] ?? null) {
            if (is_array($sort)) {
                $sort = explode(' ', implode(' ', $sort));
            } else {
                // if there are commas in the sort argument, removes it
                if (Str::contains($sort, ',') === true) {
                    $sort = Str::replace($sort, ',', '');
                }

                $sort = explode(' ', $sort);
            }
            $result = $result->sort(...$sort);
        }

        if (isset($arguments['paginate']) === true) {
            $result = $result->paginate($arguments['paginate']);
        }

        return $result;
    }

    /**
     * Removes an element from the array by key
     *
     * @param mixed $key the name of the key
     * @return $this
     */
    public function remove($key)
    {
        $this->__unset($key);
        return $this;
    }

    /**
     * Adds a new element to the collection
     *
     * @param mixed $key string or array
     * @param mixed $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->__set($k, $v);
            }
        } else {
            $this->__set($key, $value);
        }
        return $this;
    }

    /**
     * Shuffle all elements
     *
     * @return static
     */
    public function shuffle()
    {
        $data = $this->data;
        $keys = $this->keys();
        shuffle($keys);

        $collection = clone $this;
        $collection->data = [];

        foreach ($keys as $key) {
            $collection->data[$key] = $data[$key];
        }

        return $collection;
    }

    /**
     * Returns a slice of the object
     *
     * @param int $offset The optional index to start the slice from
     * @param int|null $limit The optional number of elements to return
     * @return $this|static
     */
    public function slice(int $offset = 0, int $limit = null)
    {
        if ($offset === 0 && $limit === null) {
            return $this;
        }

        $collection = clone $this;
        $collection->data = array_slice($this->data, $offset, $limit);
        return $collection;
    }

    /**
     * Get sort arguments from a string
     *
     * @param string $sort
     * @return array
     */
    public static function sortArgs(string $sort): array
    {
        // if there are commas in the sortBy argument, removes it
        if (Str::contains($sort, ',') === true) {
            $sort = Str::replace($sort, ',', '');
        }

        $sortArgs = Str::split($sort, ' ');

        // fill in PHP constants
        array_walk($sortArgs, function (string &$value) {
            if (Str::startsWith($value, 'SORT_') === true && defined($value) === true) {
                $value = constant($value);
            }
        });

        return $sortArgs;
    }

    /**
     * Sorts the elements by any number of fields
     *
     * @param string|callable $field Field name or value callback to sort by
     * @param string $direction asc or desc
     * @param int $method The sort flag, SORT_REGULAR, SORT_NUMERIC etc.
     * @return $this|static
     */
    public function sort()
    {
        // there is no need to sort empty collections
        if (empty($this->data) === true) {
            return $this;
        }

        $args = func_get_args();
        $array = $this->data;
        $collection = $this->clone();

        // loop through all method arguments and find sets of fields to sort by
        $fields = [];

        foreach ($args as $arg) {

            // get the index of the latest field array inside the $fields array
            $currentField = $fields ? count($fields) - 1 : 0;

            // detect the type of argument
            // sorting direction
            $argLower = is_string($arg) ? strtolower($arg) : null;

            if ($arg === SORT_ASC || $argLower === 'asc') {
                $fields[$currentField]['direction'] = SORT_ASC;
            } elseif ($arg === SORT_DESC || $argLower === 'desc') {
                $fields[$currentField]['direction'] = SORT_DESC;

                // other string: the field name
            } elseif (is_string($arg) === true) {
                $values = [];

                foreach ($array as $key => $value) {
                    $value = $collection->getAttribute($value, $arg);

                    // make sure that we return something sortable
                    // but don't convert other scalars (especially numbers) to strings!
                    $values[$key] = is_scalar($value) === true ? $value : (string)$value;
                }

                $fields[] = ['field' => $arg, 'values' => $values];

                // callable: custom field values
            } elseif (is_callable($arg) === true) {
                $values = [];

                foreach ($array as $key => $value) {
                    $value = $arg($value);

                    // make sure that we return something sortable
                    // but don't convert other scalars (especially numbers) to strings!
                    $values[$key] = is_scalar($value) === true ? $value : (string)$value;
                }

                $fields[] = ['field' => null, 'values' => $values];

                // flags
            } else {
                $fields[$currentField]['flags'] = $arg;
            }
        }

        // build the multisort params in the right order
        $params = [];

        foreach ($fields as $field) {
            $params[] = $field['values'] ?? [];
            $params[] = $field['direction'] ?? SORT_ASC;
            $params[] = $field['flags'] ?? SORT_NATURAL | SORT_FLAG_CASE;
        }

        // check what kind of collection items we have; only check for the first
        // item for better performance (we assume that all collection items are
        // of the same type)
        $firstItem = $collection->first();
        if (is_object($firstItem) === true) {
            // avoid the "Nesting level too deep - recursive dependency?" error
            // when PHP tries to sort by the objects directly (in case all other
            // fields are 100 % equal for some elements)
            if (method_exists($firstItem, '__toString') === true) {
                // PHP can easily convert the objects to strings, so it should
                // compare them as strings instead of as objects to avoid the recursion
                $params[] = &$array;
                $params[] = SORT_STRING;
            } else {
                // we can't convert the objects to strings, so we need a fallback:
                // custom fictional field that is guaranteed to have a unique value
                // for each item; WARNING: may lead to slightly wrong sorting results
                // and is therefore only used as a fallback if we don't have another way
                $params[] = range(1, count($array));
                $params[] = SORT_ASC;
                $params[] = SORT_NUMERIC;

                $params[] = &$array;
            }
        } else {
            // collection items are scalar or array; no correction necessary
            $params[] = &$array;
        }

        // array_multisort receives $params as separate params
        array_multisort(...$params);

        // $array has been overwritten by array_multisort
        $collection->data = $array;
        return $collection;
    }

    /**
     * Alias for `Modufolio\Toolkit\Collection::sort`
     *
     * @param string|callable $field Field name or value callback to sort by
     * @param string $direction asc or desc
     * @param int $method The sort flag, SORT_REGULAR, SORT_NUMERIC etc.
     * @return $this|static
     */
    public function sortBy(...$args)
    {
        return $this->sort(...$args);
    }

    /**
     * Converts the object into an array
     *
     * @param Closure|null $map
     * @return array
     */
    public function toArray(Closure $map = null): array
    {
        if ($map !== null) {
            return array_map($map, $this->data);
        }

        return $this->data;
    }

    /**
     * Converts the object into a JSON string
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Converts the object to a string
     *
     * @return string
     */
    public function toString(): string
    {
        return implode('<br />', $this->keys());
    }

    /**
     * Returns an non-associative array
     * with all values
     *
     * @return array
     */
    public function values(): array
    {
        return array_values($this->data);
    }

    /**
     * The when method only executes the given Closure when the first parameter
     * is true. If the first parameter is false, the Closure will not be executed.
     * You may pass another Closure as the third parameter to the when method.
     * This Closure will execute if the first parameter evaluates as false
     *
     * @param mixed $condition
     * @param Closure $callback
     * @param Closure|null $fallback
     * @return mixed|Collection
     */
    public function when($condition, Closure $callback, Closure $fallback = null)
    {
        if ($condition) {
            return $callback->call($this, $condition);
        }

        if ($fallback !== null) {
            return $fallback->call($this, $condition);
        }

        return $this;
    }

    /**
     * Alias for $this->not()
     *
     * @param string ...$keys any number of keys, passed as individual arguments
     * @return static
     */
    public function without(...$keys)
    {
        return $this->not(...$keys);
    }
}
