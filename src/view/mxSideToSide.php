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

use Mxgraph\Util\mxPoint;
use Mxgraph\Util\mxUtils;

/**
 * Class: mxSideToSide
 *
 * Implements a vertical elbow edge. See <EntityRelation> for a description
 * of the parameters.
 */
class mxSideToSide implements mxEdgeStyleFunction
{
    /**
     *
     */
    public function apply($state, $source, $target, $points, &$result)
    {
        $view = $state->view;
        $pt = ($points != null && sizeof($points) > 0) ? $points[0] : null;
        $pts = $state->absolutePoints;
        $p0 = $pts[0];
        $pe = $pts[sizeof($pts) - 1];

        if ($pt != null) {
            $pt = $view->transformControlPoint($state, $pt);
        }

        if (isset($p0)) {
            $source = new mxCellState();
            $source->x = $p0->x;
            $source->y = $p0->y;
        }

        if (isset($pe)) {
            $target = new mxCellState();
            $target->x = $pe->x;
            $target->y = $pe->y;
        }

        if (isset($source) && isset($target)) {
            $l = max($source->x, $target->x);
            $r = min($source->x+$source->width, $target->x+$target->width);

            $x = ($pt != null) ? $pt->x : $r + ($l-$r)/2;

            $y1 = $view->getRoutingCenterY($source);
            $y2 = $view->getRoutingCenterY($target);

            if ($pt != null) {
                if ($pt->y >= $source->y &&
                    $pt->y <= $source->y + $source->height) {
                    $y1 = $pt->y;
                }

                if ($pt->y >= $target->y &&
                    $pt->y <= $target->y + $target->height) {
                    $y2 = $pt->y;
                }
            }

            if (!mxUtils::contains($target, $x, $y1) &&
                !mxUtils::contains($source, $x, $y1)) {
                array_push($result, new mxPoint($x, $y1));
            }

            if (!mxUtils::contains($target, $x, $y2) &&
                !mxUtils::contains($source, $x, $y2)) {
                array_push($result, new mxPoint($x, $y2));
            }

            if (sizeof($result) == 1) {
                if (isset($pt)) {
                    array_push($result, new mxPoint($x, $pt->y));
                } else {
                    $t = max($source->y, $target->y);
                    $b = min($source->y+$source->height, $target->y+$target->height);

                    array_push($result, new mxPoint($x, $t + ($b - $t) / 2));
                }
            }
        }
    }
}
