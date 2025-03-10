<?php

declare(strict_types=1);

namespace Mxgraph\Util;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxPoint
{
    /**
     * Class: mxPoint.
     *
     * Implements a 2-dimensional point with double precision coordinates.
     *
     * Variable: x
     *
     * Holds the x-coordinate of the point. Default is 0.
     */
    public $x = 0;

    /**
     * Variable: y.
     *
     * Holds the y-coordinate of the point. Default is 0.
     */
    public $y = 0;

    /**
     * Constructor: mxPoint.
     *
     * Constructs a new point for the optional x and y coordinates. If no
     * coordinates are given, then the default values for <x> and <y> are used.
     *
     * @param mixed $x
     * @param mixed $y
     */
    public function __construct($x = 0, $y = 0)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Function: equals.
     *
     * Returns true if the given object equals this point.
     *
     * @param mixed $obj
     */
    public function equals($obj)
    {
        if ($obj instanceof self) {
            return $obj->x == $this->x
                && $obj->y == $this->y;
        }

        return false;
    }

    /**
     * Function: copy.
     *
     * Returns a copy of this <mxPoint>.
     */
    public function copy()
    {
        return new self($this->x, $this->y);
    }
}
