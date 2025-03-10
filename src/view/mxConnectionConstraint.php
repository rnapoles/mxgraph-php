<?php

declare(strict_types=1);

namespace Mxgraph\View;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxConnectionConstraint
{
    /**
     * Class: mxConnectionConstraint.
     *
     * Defines an object that contains the constraints about how to connect one
     * side of an edge to its terminal.
     *
     * Variable: point
     *
     * <mxPoint> that specifies the fixed location of the connection point.
     */
    public $point;

    /**
     * Variable: perimeter.
     *
     * Boolean that specifies if the point should be projected onto the perimeter
     * of the terminal.
     */
    public $perimeter;

    /**
     * Constructor: mxConnectionConstraint.
     *
     * Constructs a new connection constraint for the given point and boolean
     * arguments.
     *
     * Parameters:
     *
     * point - Optional <mxPoint> that specifies the fixed location of the point
     * in relative coordinates. Default is null.
     * perimeter - Optional boolean that specifies if the fixed point should be
     * projected onto the perimeter of the terminal. Default is true.
     *
     * @param null|mixed $point
     * @param mixed      $perimeter
     */
    public function __construct($point = null, $perimeter = true)
    {
        $this->point = $point;
        $this->perimeter = $perimeter;
    }
}
