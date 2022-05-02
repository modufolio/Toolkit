<?php

namespace Modufolio\Toolkit;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class Reflection
{
    /**
     * @throws ReflectionException
     */
    public static function setProperties(array $array, $obj) {
        $rc = new ReflectionClass($obj);

        foreach ($array as $propertyToSet => $value) {
            if ( !property_exists ( $obj , $propertyToSet ) ){
                continue;
            }
            $property = $rc->getProperty($propertyToSet);

            if ($property instanceof ReflectionProperty) {
                $property->setValue($obj, $value);
            }
        }
    }
}