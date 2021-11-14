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
 * Class: mxTopToBottom
 *
 * Implements a horizontal elbow edge. See <EntityRelation> for a
 * description of the parameters.
 */
class mxTopToBottom implements mxEdgeStyleFunction
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
            $t = max($source->y, $target->y);
            $b = min($source->y+$source->height, $target->y+$target->height);

            $x = $view->getRoutingCenterX($source);

            if ($pt != null &&
                $pt->x >= $source->x &&
                $pt->x <= $source->x + $source->width) {
                $x = $pt->x;
            }

            $y = ($pt != null) ? $pt->y : $b + ($t - $b) / 2;

            if (!mxUtils::contains($target, $x, $y) &&
                !mxUtils::contains($source, $x, $y)) {
                array_push($result, new mxPoint($x, $y));
            }

            if ($pt != null &&
                $pt->x >= $target->x &&
                $pt->x <= $target->x + $target->width) {
                $x = $pt->x;
            } else {
                $x = $view->getRoutingCenterX($target);
            }

            if (!mxUtils::contains($target, $x, $y) &&
                !mxUtils::contains($source, $x, $y)) {
                array_push($result, new mxPoint($x, $y));
            }

            if (sizeof($result) == 1) {
                if ($pt == null) {
                    array_push($result, new mxPoint($x, $y));
                } else {
                    $l = max($source->x, $target->x);
                    $r = min($source->x + $source->width, $target->x + $target->width);

                    array_push($result, new mxPoint($r + ($r - $l) / 2, $y));
                }
            }
        }
    }
}
