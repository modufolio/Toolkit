<?php

namespace Modufolio\Data;

use Modufolio\Exception\Exception;
use Modufolio\Toolkit\F;

/**
 * The `Data` class provides readers and
 * writers for data. The class comes with
 * handlers for `json`, `php`, `txt`, `xml`
 * and `yaml` encoded data, but can be
 * extended and customized.
 *
 * The read and write methods automatically
 * detect which data handler to use in order
 * to correctly encode and decode passed data.
 *
 * @package   Kirby
 * @author    Bastian Allgeier <bastian@getkirby.com>
 * @link      https://getkirby.com
 * @copyright Bastian Allgeier GmbH
 * @license   https://opensource.org/licenses/MIT
 */
class Data
{
    /**
     * Handler Type Aliases
     *
     * @var array
     */
    public static $aliases = [
        'md' => 'txt',
        'mdown' => 'txt',
        'rss' => 'xml',
        'yml' => 'yaml',
    ];

    /**
     * All registered handlers
     *
     * @var array
     */
    public static $handlers = [
        'json' => 'Modufolio\Data\Json',
        'php' => 'Modufolio\Data\PHP',
        'txt' => 'Modufolio\Data\Txt',
        'xml' => 'Modufolio\Data\Xml',
        'yaml' => 'Modufolio\Data\Yaml',
    ];

    /**
     * Handler getter
     *
     * @param string $type
     * @return Handler
     */
    public static function handler(string $type)
    {
        // normalize the type
        $type = strtolower($type);

        // find a handler or alias
        $handler = static::$handlers[$type] ??
            static::$handlers[static::$aliases[$type] ?? null] ??
            null;

        if (class_exists($handler)) {
            return new $handler();
        }

        throw new Exception('Missing handler for type: "' . $type . '"');
    }

    /**
     * Decodes data with the specified handler
     *
     * @param mixed $string
     * @param string $type
     * @return array
     */
    public static function decode($string, string $type): array
    {
        return static::handler($type)->decode($string);
    }

    /**
     * Encodes data with the specified handler
     *
     * @param mixed $data
     * @param string $type
     * @return string
     */
    public static function encode($data, string $type): string
    {
        return static::handler($type)->encode($data);
    }

    /**
     * Reads data from a file;
     * the data handler is automatically chosen by
     * the extension if not specified
     *
     * @param string $file
     * @param string $type
     * @return array
     */
    public static function read(string $file, string $type = null): array
    {
        return static::handler($type ?? F::extension($file))->read($file);
    }

    /**
     * Writes data to a file;
     * the data handler is automatically chosen by
     * the extension if not specified
     *
     * @param string $file
     * @param mixed $data
     * @param string $type
     * @return bool
     */
    public static function write(string $file = null, $data = [], string $type = null): bool
    {
        return static::handler($type ?? F::extension($file))->write($file, $data);
    }
}
