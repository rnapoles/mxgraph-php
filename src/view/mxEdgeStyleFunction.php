<?php
/**
 * EXB R5 - Business suite
 * Copyright (C) EXB Software 2020 - All Rights Reserved
 *
 * This file is part of EXB R5.
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @author emiel <e.goor@exb-software.com>
 */
declare(strict_types=1);

namespace Mxgraph\View;

interface mxEdgeStyleFunction
{
    /**
     * Interface: mxEdgeStyleFunction
     *
     * Defines the requirements for an edge style function.
     *
     * Function: apply
     *
     * Implements an edge style function. At the time the function is called, the result
     * array contains a placeholder (null) for the first absolute point,
     * that is, the point where the edge and source terminal are connected.
     * The implementation of the style then adds all intermediate waypoints
     * except for the last point, that is, the connection point between the
     * edge and the target terminal. The first ant the last point in the
     * result array are then replaced with mxPoints that take into account
     * the terminal's perimeter and next point on the edge.
     *
     * Parameters:
     *
     * state - <mxCellState> that represents the edge to be updated.
     * source - <mxCellState> that represents the source terminal.
     * target - <mxCellState> that represents the target terminal.
     * points - List of relative control points.
     * result - Array of <mxPoints> that represent the actual points of the
     * edge.
     */
    public function apply($state, $source, $target, $points, &$result);
}
