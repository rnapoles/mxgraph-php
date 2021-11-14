<?php

declare(strict_types=1);

namespace Mxgraph\Io;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCodecRegistry
{
    /**
     * Class: mxCodecRegistry.
     *
     * A class to register codecs for objects.
     *
     * Variable: codecs
     *
     * Maps from constructor names to codecs.
     */
    public static $codecs = [];

    /**
     * Variable: aliases.
     *
     * Maps from classnames to codecnames.
     */
    public static $aliases = [];

    /**
     * Function: register.
     *
     * Registers a new codec and associates the name of the template constructor
     * in the codec with the codec object. Automatically creates an alias if the
     * codename and the classname are not equal.
     *
     * Parameters:
     *
     * codec - <mxObjectCodec> to be registered.
     *
     * @param mixed $codec
     */
    public static function register($codec)
    {
        if (isset($codec)) {
            $name = $codec->getName();
            self::$codecs[$name] = $codec;

            $classname = self::getName($codec->template);

            if ($classname != $name) {
                self::addAlias($classname, $name);
            }
        }

        return $codec;
    }

    /**
     * Function: addAlias.
     *
     * Adds an alias for mapping a classname to a codecname.
     *
     * @param mixed $classname
     * @param mixed $codecname
     */
    public static function addAlias($classname, $codecname): void
    {
        self::$aliases[$classname] = $codecname;
    }

    /**
     * Function: getCodec.
     *
     * Returns a codec that handles objects that are constructed
     * using the given ctor.
     *
     * Parameters:
     *
     * ctor - JavaScript constructor function.
     *
     * @param mixed $name
     */
    public static function getCodec($name)
    {
        $codec = null;

        if (isset($name)) {
            if (isset(self::$aliases[$name])) {
                $tmp = self::$aliases[$name];

                if ('' !== $tmp) {
                    $name = $tmp;
                }
            }

            $codec = (isset(self::$codecs[$name])) ?
                self::$codecs[$name] : null;

            // Registers a new default codec for the given constructor
            // if no codec has been previously defined.
            if (!isset($codec)) {
                try {
                    $obj = self::getInstanceForName($name);

                    if (isset($obj)) {
                        $codec = new mxObjectCodec($obj);
                        self::register($codec);
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        return $codec;
    }

    /**
     * Function: getInstanceForName.
     *
     * Creates and returns a new instance for the given class name.
     *
     * @param mixed $name
     */
    public static function getInstanceForName($name)
    {
        if (class_exists($name)) {
            return new $name();
        }

        foreach (get_declared_classes() as $class) {
            if (substr($class, -\strlen($name)) == $name) {
                return new $class();
            }
        }

        return null;
    }

    /**
     * Function: getName.
     *
     * Returns the codec name for the given object instance.
     *
     * Parameters:
     *
     * obj - PHP object to return the codec name for.
     *
     * @param mixed $obj
     */
    public static function getName($obj)
    {
        if (\is_array($obj)) {
            return 'Array';
        }

        $name = explode('\\', \get_class($obj));

        return array_pop($name);
    }
}
