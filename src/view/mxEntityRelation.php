<?php
/**
 * EXB R5 - Business suite
 * Copyright (C) EXB Software 2020 - All Rights Reserved.
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

use Mxgraph\Util\mxConstants;
use Mxgraph\Util\mxPoint;
use Mxgraph\Util\mxUtils;

/**
 * Class: mxEntityRelation.
 *
 * Implements an entity relation style for edges (as used in database
 * schema diagrams).  At the time the function is called, the result
 * array contains a placeholder (null) for the first absolute point,
 * that is, the point where the edge and source terminal are connected.
 * The implementation of the style then adds all intermediate waypoints
 * except for the last point, that is, the connection point between the
 * edge and the target terminal. The first ant the last point in the
 * result array are then replaced with mxPoints that take into account
 * the terminal's perimeter and next point on the edge.
 */
class mxEntityRelation implements mxEdgeStyleFunction
{
    /**
     * @param mixed $state
     * @param mixed $source
     * @param mixed $target
     * @param mixed $points
     * @param mixed $result
     */
    public function apply($state, $source, $target, $points, &$result): void
    {
        $view = $state->view;
        $graph = $view->graph;
        $segment = mxUtils::getValue(
            $state->style,
            mxConstants::$STYLE_SEGMENT,
            mxConstants::$ENTITY_SEGMENT
        ) * $view->scale;

        $pts = $state->absolutePoints;
        $p0 = $pts[0];
        $pe = $pts[\count($pts) - 1];

        $isSourceLeft = false;

        if (isset($p0)) {
            $source = new mxCellState();
            $source->x = $p0->x;
            $source->y = $p0->y;
        } elseif (isset($source)) {
            $sourceGeometry = $graph->getCellGeometry($source->cell);

            if ($sourceGeometry->relative) {
                $isSourceLeft = $sourceGeometry->x <= 0.5;
            } elseif (null != $target) {
                $isSourceLeft = $target->x + $target->width < $source->x;
            }
        }

        $isTargetLeft = true;

        if (isset($pe)) {
            $target = new mxCellState();
            $target->x = $pe->x;
            $target->y = $pe->y;
        } elseif (isset($target)) {
            $targetGeometry = $graph->getCellGeometry($target->cell);

            if ($targetGeometry->relative) {
                $isTargetLeft = $targetGeometry->x <= 0.5;
            } elseif (null != $source) {
                $isTargetLeft = $source->x + $source->width < $target->x;
            }
        }

        if (isset($source, $target)) {
            $x0 = ($isSourceLeft) ? $source->x : $source->x + $source->width;
            $y0 = $view->getRoutingCenterY($source);

            $xe = ($isTargetLeft) ? $target->x : $target->x + $target->width;
            $ye = $view->getRoutingCenterY($target);

            $seg = $segment;

            $dx = ($isSourceLeft) ? -$seg : $seg;
            $dep = new mxPoint($x0 + $dx, $y0);
            $result[] = $dep;

            $dx = ($isTargetLeft) ? -$seg : $seg;
            $arr = new mxPoint($xe + $dx, $ye);

            // Adds intermediate points if both go out on same side
            if ($isSourceLeft == $isTargetLeft) {
                $x = ($isSourceLeft) ?
                    min($x0, $xe) - $segment :
                    max($x0, $xe) + $segment;
                $result[] = new mxPoint($x, $y0);
                $result[] = new mxPoint($x, $ye);
            } elseif (($dep->x < $arr->x) == $isSourceLeft) {
                $midY = $y0 + ($ye - $y0) / 2;
                $result[] = new mxPoint($dep->x, $midY);
                $result[] = new mxPoint($arr->x, $midY);
            }

            $result[] = $arr;
        }
    }
}
