<?php

declare(strict_types=1);

namespace Mxgraph\View;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxStyleRegistry
{
    /**
     * Class: mxStyleRegistry.
     *
     * Singleton class that acts as a global converter from string to object values
     * in a style. This is currently only used to perimeters and edge styles.
     *
     *
     * Variable: values
     *
     * Maps from strings to objects.
     */
    public static $values = [];

    /**
     * Function: putValue.
     *
     * Puts the given object into the registry under the given name.
     *
     * @param mixed $name
     * @param mixed $value
     */
    public static function putValue($name, $value): void
    {
        self::$values[$name] = $value;
    }

    /**
     * Function: getValue.
     *
     * Returns the value associated with the given name.
     *
     * @param mixed $name
     */
    public static function getValue($name)
    {
        return (isset(self::$values[$name])) ? self::$values[$name] : null;
    }

    /**
     * Function: getName.
     *
     * Returns the name for the given value.
     *
     * @param mixed $value
     */
    public static function getName($value)
    {
        foreach (self::$values as $key => $val) {
            if ($value === $val) {
                return $key;
            }
        }

        return null;
    }
}
