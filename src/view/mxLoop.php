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
 * Class: mxLoop.
 *
 * Implements a self-reference, aka. loop.
 */
class mxLoop implements mxEdgeStyleFunction
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
        if (null != $source) {
            $view = $state->view;
            $graph = $view->graph;
            $pt = (null != $points && \count($points) > 0) ? $points[0] : null;

            if (null != $pt) {
                $pt = $view->transformControlPoint($state, $pt);

                if (mxUtils::contains($source, $pt->x, $pt->y)) {
                    $pt = null;
                }
            }

            $x = 0;
            $dx = 0;
            $y = 0;
            $dy = 0;

            $seg = mxUtils::getValue(
                $state->style,
                mxConstants::$STYLE_SEGMENT,
                $graph->gridSize
            )
                * $view->scale;
            $dir = mxUtils::getValue(
                $state->style,
                mxConstants::$STYLE_DIRECTION,
                mxConstants::$DIRECTION_WEST
            );

            if ($dir == mxConstants::$DIRECTION_NORTH
                || $dir == mxConstants::$DIRECTION_SOUTH) {
                $x = $view->getRoutingCenterX($source);
                $dx = $seg;
            } else {
                $y = $view->getRoutingCenterY($source);
                $dy = $seg;
            }

            if (null == $pt
                || $pt->x < $source->x
                || $pt->x > $source->x + $source->width) {
                if (null != $pt) {
                    $x = $pt->x;
                    $dy = max(abs($y - $pt->y), $dy);
                } else {
                    if ($dir == mxConstants::$DIRECTION_NORTH) {
                        $y = $source->y - 2 * $dx;
                    } elseif ($dir == mxConstants::$DIRECTION_SOUTH) {
                        $y = $source->y + $source->height + 2 * $dx;
                    } elseif ($dir == mxConstants::$DIRECTION_EAST) {
                        $x = $source->x - 2 * $dy;
                    } else {
                        $x = $source->x + $source->width + 2 * $dy;
                    }
                }
            } elseif (null != $pt) {
                $x = $view->getRoutingCenterX($source);
                $dx = max(abs($x - $pt->x), $dy);
                $y = $pt->y;
                $dy = 0;
            }

            $result[] = new mxPoint($x - $dx, $y - $dy);
            $result[] = new mxPoint($x + $dx, $y + $dy);
        }
    }
}
