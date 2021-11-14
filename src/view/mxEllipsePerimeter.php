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

use Mxgraph\Util\mxPoint;

class mxEllipsePerimeter implements mxPerimeterFunction
{
    /**
     * @param mixed $bounds
     * @param mixed $vertex
     * @param mixed $next
     * @param mixed $orthogonal
     */
    public function apply($bounds, $vertex, $next, $orthogonal)
    {
        $x = $bounds->x;
        $y = $bounds->y;
        $a = $bounds->width / 2;
        $b = $bounds->height / 2;
        $cx = $x + $a;
        $cy = $y + $b;
        $px = $next->x;
        $py = $next->y;

        // Calculates straight line equation through
        // point and ellipse center y = d * x + h
        $dx = (int) ($px - $cx);
        $dy = (int) ($py - $cy);

        if (0 == $dx && 0 != $dy) {
            return new mxPoint($cx, $cy + $b * $dy / abs($dy));
        }
        if (0 == $dx && 0 == $dy) {
            return new mxPoint($px, $py);
        }

        if ($orthogonal) {
            if ($py >= $y && $py <= $y + $bounds->height) {
                $ty = $py - $cy;
                $tx = sqrt($a * $a * (1 - ($ty * $ty) / ($b * $b)));

                if (is_nan($tx)) {
                    $tx = 0;
                }

                if ($px <= $x) {
                    $tx = -$tx;
                }

                return new mxPoint($cx + $tx, $py);
            }

            if ($px >= $x && $px <= $x + $bounds->width) {
                $tx = $px - $cx;
                $ty = sqrt($b * $b * (1 - ($tx * $tx) / ($a * $a)));

                if (is_nan($ty)) {
                    $ty = 0;
                }

                if ($py <= $y) {
                    $ty = -$ty;
                }

                return new mxPoint($px, $cy + $ty);
            }
        }

        // Calculates intersection
        $d = $dy / $dx;
        $h = $cy - $d * $cx;
        $e = $a * $a * $d * $d + $b * $b;
        $f = -2 * $cx * $e;
        $g = $a * $a * $d * $d * $cx * $cx +
            $b * $b * $cx * $cx -
            $a * $a * $b * $b;
        $det = sqrt($f * $f - 4 * $e * $g);

        // Two solutions (perimeter points)
        $xout1 = (-$f + $det) / (2 * $e);
        $xout2 = (-$f - $det) / (2 * $e);
        $yout1 = $d * $xout1 + $h;
        $yout2 = $d * $xout2 + $h;
        $dist1 = sqrt(($xout1 - $px) ** 2
            + ($yout1 - $py) ** 2);
        $dist2 = sqrt(($xout2 - $px) ** 2
            + ($yout2 - $py) ** 2);

        // Correct solution
        $xout = 0;
        $yout = 0;
        if ($dist1 < $dist2) {
            $xout = $xout1;
            $yout = $yout1;
        } else {
            $xout = $xout2;
            $yout = $yout2;
        }

        return new mxPoint($xout, $yout);
    }
}
