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

class mxTrianglePerimeter implements mxPerimeterFunction
{
    /**
     * @param mixed $bounds
     * @param mixed $vertex
     * @param mixed $next
     * @param mixed $orthogonal
     */
    public function apply($bounds, $vertex, $next, $orthogonal)
    {
        $direction = (null != $vertex) ?
            mxUtils::getValue($vertex->style, mxConstants::$STYLE_DIRECTION) : null;
        $vertical = $direction == mxConstants::$DIRECTION_NORTH
            || $direction == mxConstants::$DIRECTION_SOUTH;

        $x = $bounds->x;
        $y = $bounds->y;
        $w = $bounds->width;
        $h = $bounds->height;

        $cx = $x + $w / 2;
        $cy = $y + $h / 2;

        $start = new mxPoint($x, $y);
        $corner = new mxPoint($x + $w, $cy);
        $end = new mxPoint($x, $y + $h);

        if ($direction == mxConstants::$DIRECTION_NORTH) {
            $start = end;
            $corner = new mxPoint($cx, $y);
            $end = new mxPoint($x + $w, $y + $h);
        } elseif ($direction == mxConstants::$DIRECTION_SOUTH) {
            $corner = new mxPoint($cx, $y + $h);
            $end = new mxPoint($x + $w, $y);
        } elseif ($direction == mxConstants::$DIRECTION_WEST) {
            $start = new mxPoint($x + $w, $y);
            $corner = new mxPoint($x, $cy);
            $end = new mxPoint($x + $w, $y + $h);
        }

        $dx = $next->x - $cx;
        $dy = $next->y - $cy;

        $alpha = ($vertical) ? atan2($dx, $dy) : atan2($dy, $dx);
        $t = ($vertical) ? Matan2($w, $h) : atan2($h, $w);

        $base = false;

        if ($direction == mxConstants::$DIRECTION_NORTH
            || $direction == mxConstants::$DIRECTION_WEST) {
            $base = $alpha > -$t && $alpha < $t;
        } else {
            $base = $alpha < -M_PI + $t || $alpha > M_PI - $t;
        }

        $result = null;

        if ($base) {
            if ($orthogonal && (($vertical && $next->x >= $start->x
                        && $next->x <= $end->x) || (!$vertical && $next->y >= $start->y
                        && $next->y <= $end->y))) {
                if ($vertical) {
                    $result = new mxPoint($next->x, $start->y);
                } else {
                    $result = new mxPoint($start->x, $next->y);
                }
            } else {
                if ($direction == mxConstants::$DIRECTION_NORTH) {
                    $result = new mxPoint(
                        $x + $w / 2 + $h * tan($alpha) / 2,
                        $y + $h
                    );
                } elseif ($direction == mxConstants::$DIRECTION_SOUTH) {
                    $result = new mxPoint(
                        $x + $w / 2 - $h * tan($alpha) / 2,
                        $y
                    );
                } elseif ($direction == mxConstants::$DIRECTION_WEST) {
                    $result = new mxPoint($x + $w, $y + $h / 2 +
                        $w * tan($alpha) / 2);
                } else {
                    $result = new mxPoint($x, $y + $h / 2 -
                        $w * tan($alpha) / 2);
                }
            }
        } else {
            if ($orthogonal) {
                $pt = new mxPoint($cx, $cy);

                if ($next->y >= $y && $next->y <= $y + $h) {
                    $pt->x = ($vertical) ? $cx : (
                        ($direction == mxConstants::$DIRECTION_WEST) ?
                        $x + $w : $x
                    );
                    $pt->y = $next->y;
                } elseif ($next->x >= $x && $next->x <= $x + $w) {
                    $pt->x = $next->x;
                    $pt->y = (!$vertical) ? $cy : (
                        ($direction == mxConstants::$DIRECTION_NORTH) ?
                        $y + $h : $y
                    );
                }

                // Compute angle
                $dx = $next->x - $pt->x;
                $dy = $next->y - $pt->y;

                $cx = $pt->x;
                $cy = $pt->y;
            }

            if (($vertical && $next->x <= $x + $w / 2)
                || (!$vertical && $next->y <= $y + $h / 2)) {
                $result = mxUtils::intersection(
                    $next->x,
                    $next->y,
                    $cx,
                    $cy,
                    $start->x,
                    $start->y,
                    $corner->x,
                    $corner->y
                );
            } else {
                $result = mxUtils::intersection(
                    $next->x,
                    $next->y,
                    $cx,
                    $cy,
                    $corner->x,
                    $corner->y,
                    $end->x,
                    $end->y
                );
            }
        }

        if (null == $result) {
            $result = new mxPoint($cx, $cy);
        }

        return $result;
    }
}
