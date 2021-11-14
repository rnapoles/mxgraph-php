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

class mxRhombusPerimeter implements mxPerimeterFunction
{
    /**
     *
     */
    public function apply($bounds, $vertex, $next, $orthogonal)
    {
        $x = $bounds->x;
        $y = $bounds->y;
        $w = $bounds->width;
        $h = $bounds->height;

        $cx = $x + $w / 2;
        $cy = $y + $h / 2;

        $px = $next->x;
        $py = $next->y;

        // Special case for intersecting the diamond's corners
        if ($cx == $px)
        {
            if ($cy > $py)
            {
                return new mxPoint($cx, $y); // top
            }
            else
            {
                return new mxPoint($cx, $y + $h); // bottom
            }
        }
        else if ($cy == $py)
        {
            if ($cx > $px)
            {
                return new mxPoint($x, $cy); // left
            }
            else
            {
                return new mxPoint($x + $w, $cy); // right
            }
        }

        $tx = $cx;
        $ty = $cy;

        if ($orthogonal)
        {
            if ($px >= $x && $px <= $x + $w)
            {
                $tx = $px;
            }
            else if ($py >= $y && $py <= $y + $h)
            {
                $ty = $py;
            }
        }

        // In which quadrant will the intersection be?
        // set the slope and offset of the border line accordingly
        if ($px < $cx)
        {
            if ($py < $cy)
            {
                return mxUtils::intersection($px, $py,
                    $tx, $ty, $cx, $y, $x, $cy);
            }
            else
            {
                return mxUtils::intersection($px, $py,
                    $tx, $ty, $cx, $y + $h, $x, $cy);
            }
        }
        else if ($py < $cy)
        {
            return mxUtils::intersection($px, $py,
                $tx, $ty, $cx, $y, $x + $w, $cy);
        }
        else
        {
            return mxUtils::intersection($px, $py,
                $tx, $ty, $cx, $y + $h, $x + $w, $cy);
        }
    }
}