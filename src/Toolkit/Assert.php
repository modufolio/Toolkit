<?php

namespace Modufolio\Toolkit;

use Modufolio\Exception\Exception;
use Modufolio\Exception\InvalidArgumentException;


/**
 * A set of assertion methods
 *
 * @package   Modufolio Toolkit
 * @author    Maarten Thiebou
 * @copyright Modufolio
 * @license   https://opensource.org/licenses/MIT
 *
 * @method static array($value, string $message = null)t * @method static bool ($value, string $message = null)
 * @method static bit ($value, string $message = null)
 * @method static blank ($value, string $message = null)
 * @method static callable ($value, string $message = null)
 * @method static date ($value, string $message = null)
 * @method static file($value, string $message = null)
 * @method static float ($value, string $message = null)
 * @method static int ($value, string $message = null)
 * @method static iterable ($value, string $message = null)
 * @method static json ($value, string $message = null)
 * @method static null ($value, string $message = null)
 * @method static numeric ($value, string $message = null)
 * @method static object ($value, string $message = null)
 * @method static resource ($value, string $message = null)
 * @method static scalar ($value, string $message = null)
 * @method static string ($value, string $message = null)
 * @method static xml ($value, string $message = null)
 *
 */
class Assert
{

    private static array $methods = [
        'array' => 'is_array',
        'bool' => 'is_bool',
        'bit' => 'is_bit',
        'blank' => 'is_blank',
        'callable' => 'is_callable',
        'date' => 'is_date',
        'file' => 'is_file',
        'float' => 'is_float',
        'int' => 'is_int',
        'iterable' => 'is_iterable',
        'json' => 'is_json',
        'null' => 'is_null',
        'numeric' => 'is_numeric',
        'object' => 'is_object',
        'resource' => 'is_resource',
        'scalar' => 'is_scalar',
        'string' => 'is_string',
        'xml' => 'is_xml',

    ];

    /**
     * @throws Exception
     */
    public static function __callStatic(string $method, array $parameters)
    {
        if (!array_key_exists($method, self::$methods)) {
            throw new Exception('The ' . $method . ' is not supported.');
        }

        if (call_user_func_array(self::$methods[$method], $parameters) === false) {
            throw new InvalidArgumentException('Invalid argument: value is not    ' . $method);
        }
    }

}