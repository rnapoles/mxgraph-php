<?php

namespace Mxgraph\Io;

use Mxgraph\Model\mxGraphModel;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder
 */
class mxCodecRegistry
{
    /**
     * Class: mxCodecRegistry
     *
     * A class to register codecs for objects.
     *
     * Variable: codecs
     *
     * Maps from constructor names to codecs.
     */
    public static $codecs = array();

    /**
     * Variable: aliases
     *
     * Maps from classnames to codecnames.
     */
    public static $aliases = array();

    /**
     * Function: register
     *
     * Registers a new codec and associates the name of the template constructor
     * in the codec with the codec object. Automatically creates an alias if the
     * codename and the classname are not equal.
     *
     * Parameters:
     *
     * codec - <mxObjectCodec> to be registered.
     */
    public static function register($codec)
    {
        if (isset($codec)) {
            $name = $codec->getName();
            mxCodecRegistry::$codecs[$name] = $codec;

            $classname = mxCodecRegistry::getName($codec->template);

            if ($classname != $name) {
                mxCodecRegistry::addAlias($classname, $name);
            }
        }

        return $codec;
    }

    /**
     * Function: addAlias
     *
     * Adds an alias for mapping a classname to a codecname.
     */
    public static function addAlias($classname, $codecname)
    {
        mxCodecRegistry::$aliases[$classname] = $codecname;
    }
    /**
     * Function: getCodec
     *
     * Returns a codec that handles objects that are constructed
     * using the given ctor.
     *
     * Parameters:
     *
     * ctor - JavaScript constructor function.
     */
    public static function getCodec($name)
    {
        $codec = null;

        if (isset($name)) {
            if (isset(mxCodecRegistry::$aliases[$name])) {
                $tmp = mxCodecRegistry::$aliases[$name];

                if (strlen($tmp) > 0) {
                    $name = $tmp;
                }
            }

            $codec = (isset(mxCodecRegistry::$codecs[$name])) ?
                mxCodecRegistry::$codecs[$name] : null;

            // Registers a new default codec for the given constructor
            // if no codec has been previously defined.
            if (!isset($codec)) {
                try {
                    $obj = mxCodecRegistry::getInstanceForName($name);

                    if (isset($obj)) {
                        $codec = new mxObjectCodec($obj);
                        mxCodecRegistry::register($codec);
                    }
                } catch (\Exception $e) {
                    // ignore
                }
            }
        }

        return $codec;
    }

    /**
     * Function: getInstanceForName
     *
     * Creates and returns a new instance for the given class name.
     */
    public static function getInstanceForName($name)
    {
        if (class_exists($name)) {
            return new $name();
        }

        foreach (get_declared_classes() as $class) {
            if (substr($class, -strlen($name)) == $name) {
                return new $class();
            }
        }

        return null;
    }

    /**
     * Function: getName
     *
     * Returns the codec name for the given object instance.
     *
     * Parameters:
     *
     * obj - PHP object to return the codec name for.
     */
    public static function getName($obj)
    {
        if (is_array($obj)) {
            return "Array";
        }

        $name = explode("\\", get_class($obj));
        return array_pop($name);
    }
}
