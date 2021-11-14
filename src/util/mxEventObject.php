<?php

namespace Mxgraph\Util;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder
 */
class mxEventObject
{
    /**
     * Class: mxEventObject
     *
     * Base class for all events.
     *
     * Variable: name
     *
     * Holds the name of the event.
     */
    public $name;

    /**
     * Variable: properties
     *
     * Holds the event properties in an associative array that maps from string
     * (key) to object (value).
     */
    public $properties;

    /**
     * Variable: consumed
     *
     * Holds the consumed state of the event. Default is false.
     */
    public $consumed = false;

    /**
     * Constructor: mxEventObject
     *
     * Constructs a new event for the given name and properties. The optional
     * properties are specified using a sequence of keys and values, eg.
     * new mxEventObject($name, $key1, $value1, $key2, $value2, .., $keyN, $valueN)
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->properties = array();
        $args = func_get_args();

        for ($i = 1; $i < sizeof($args); $i += 2) {
            if (isset($args[$i + 1])) {
                $this->properties[$args[$i]] = $args[$i + 1];
            }
        }
    }

    /**
     * Function: getName
     *
     * Returns <name>.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Function: getProperties
     *
     * Returns <properties>.
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Function: getProperty
     *
     * Returns the property value for the given key.
     */
    public function getProperty($key)
    {
        return $this->properties[$key];
    }

    /**
     * Function: isConsumed
     *
     * Returns true if the event has been consumed.
     */
    public function isConsumed()
    {
        return $this->consumed;
    }

    /**
     * Function: consume
     *
     * Consumes the event.
     */
    public function consume()
    {
        $this->consumed = true;
    }
}
