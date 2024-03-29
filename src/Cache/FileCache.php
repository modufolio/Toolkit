<?php

namespace Modufolio\Cache;

use Modufolio\Exception\Exception;
use Modufolio\Toolkit\Dir;
use Modufolio\Toolkit\F;
use Modufolio\Toolkit\Str;

/**
 * File System Cache Driver
 *
 * @package   Kirby Cache
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier GmbH
 * @license   https://opensource.org/licenses/MIT
 */
class FileCache extends Cache
{
    /**
     * Full root including prefix
     *
     * @var string
     */
    protected $root;

    /**
     * Sets all parameters which are needed for the file cache
     *
     * @param array $options 'root' (required)
     *                       'prefix' (default: none)
     *                       'extension' (file extension for cache files, default: none)
     */
    public function __construct(array $options)
    {
        $defaults = [
            'root' => null,
            'prefix' => null,
            'extension' => null
        ];

        parent::__construct(array_merge($defaults, $options));

        // build the full root including prefix
        $this->root = $this->options['root'];
        if (empty($this->options['prefix']) === false) {
            $this->root .= '/' . $this->options['prefix'];
        }

        // try to create the directory
        Dir::make($this->root, true);
    }

    /**
     * Returns the full root including prefix
     *
     * @return string
     */
    public function root(): string
    {
        return $this->root;
    }

    /**
     * Returns the full path to a file for a given key
     *
     * @param string $key
     * @return string
     */
    protected function file(string $key): string
    {
        // strip out invalid characters in each path segment
        // split by slash or backslash
        $keyParts = [];
        foreach (preg_split('#([\/\\\\])#', $key, 0, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            switch ($part) {
                // forward slashes don't need special treatment
                case '/':
                    break;

                // backslashes get their own marker in the path
                // to differentiate the cache key from one with forward slashes
                case '\\':
                    $keyParts[] = '_backslash';
                    break;

                // empty part means two slashes in a row;
                // special marker like for backslashes
                case '':
                    $keyParts[] = '_empty';
                    break;

                // an actual path segment
                default:
                    // check if the segment only contains safe characters;
                    // underscores are *not* safe to guarantee uniqueness
                    // as they are used in the special cases
                    if (preg_match('/^[a-zA-Z0-9-]+$/', $part) === 1) {
                        $keyParts[] = $part;
                    } else {
                        $keyParts[] = Str::slug($part) . '_' . sha1($part);
                    }
            }
        }

        $file = $this->root . '/' . implode('/', $keyParts);

        if (isset($this->options['extension'])) {
            return $file . '.' . $this->options['extension'];
        } else {
            return $file;
        }
    }

    /**
     * Writes an item to the cache for a given number of minutes and
     * returns whether the operation was successful
     *
     * <code>
     *   // put an item in the cache for 15 minutes
     *   $cache->set('value', 'my value', 15);
     * </code>
     *
     * @param string $key
     * @param mixed $value
     * @param int $minutes
     * @return bool
     */
    public function set(string $key, $value, int $minutes = 0): bool
    {
        $file = $this->file($key);

        return F::write($file, (new Value($value, $minutes))->toJson());
    }

    /**
     * Internal method to retrieve the raw cache value;
     * needs to return a Value object or null if not found
     *
     * @param string $key
     * @return Value|null
     */
    public function retrieve(string $key)
    {
        $file = $this->file($key);
        $value = F::read($file);

        return $value ? Value::fromJson($value) : null;
    }

    /**
     * Checks when the cache has been created;
     * returns the creation timestamp on success
     * and false if the item does not exist
     *
     * @param string $key
     * @return mixed
     */
    public function created(string $key)
    {
        // use the modification timestamp
        // as indicator when the cache has been created/overwritten
        clearstatcache();

        // get the file for this cache key
        $file = $this->file($key);
        return file_exists($file) ? filemtime($this->file($key)) : false;
    }

    /**
     * Removes an item from the cache and returns
     * whether the operation was successful
     *
     * @param string $key
     * @return bool
     */
    public function remove(string $key): bool
    {
        $file = $this->file($key);

        if (is_file($file) === true && F::remove($file) === true) {
            $this->removeEmptyDirectories(dirname($file));
            return true;
        }

        return false;
    }

    /**
     * Removes empty directories safely by checking each directory
     * up to the root directory
     *
     * @param string $dir
     * @return void
     */
    protected function removeEmptyDirectories(string $dir): void
    {
        try {
            // ensure the path doesn't end with a slash for the next comparison
            $dir = rtrim($dir, '/\/');

            // checks all directory segments until reaching the root directory
            while (Str::startsWith($dir, $this->root()) === true && $dir !== $this->root()) {
                $files = array_diff(scandir($dir) ?? [], ['.', '..']);

                if (empty($files) === true && Dir::remove($dir) === true) {
                    // continue with the next level up
                    $dir = dirname($dir);
                } else {
                    // no need to continue with the next level up as `$dir` was not deleted
                    break;
                }
            }
        } catch (Exception $e) { // @codeCoverageIgnore
            // silently stops the process
        }
    }

    /**
     * Flushes the entire cache and returns
     * whether the operation was successful
     *
     * @return bool
     */
    public function flush(): bool
    {
        if (Dir::remove($this->root) === true && Dir::make($this->root) === true) {
            return true;
        }

        return false; // @codeCoverageIgnore
    }
}
