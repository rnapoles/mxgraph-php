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

class mxRectanglePerimeter implements mxPerimeterFunction
{
    /**
     *
     */
    public function apply($bounds, $vertex, $next, $orthogonal)
    {
        $cx = $bounds->x + $bounds->width / 2;
        $cy = $bounds->y + $bounds->height / 2;
        $dx = $next->x - $cx;
        $dy = $next->y - $cy;
        $alpha = atan2($dy, $dx);
        $p = new mxPoint(0, 0);
        $pi = pi();
        $pi2 = $pi / 2;
        $beta = $pi2 - $alpha;
        $t = atan2($bounds->height, $bounds->width);

        if ($alpha < - $pi + $t || $alpha > $pi - $t)
        {
            // Left side
            $p->x = $bounds->x;
            $p->y = $cy - $bounds->width * tan($alpha) / 2;
        }
        else if ($alpha < -$t)
        {
            // Top side
            $p->y = $bounds->y;
            $p->x = $cx - $bounds->height * tan($beta) / 2;
        }
        else if ($alpha < $t)
        {
            // Right side
            $p->x = $bounds->x + $bounds->width;
            $p->y = $cy + $bounds->width * tan($alpha) / 2;
        }
        else
        {
            // Bottom side
            $p->y = $bounds->y + $bounds->height;
            $p->x = $cx + $bounds->height * tan($beta) / 2;
        }

        if ($orthogonal)
        {
            if ($next->x >= $bounds->x &&
                $next->x <= $bounds->x + $bounds->width)
            {
                $p->x = $next->x;
            }
            else if ($next->y >= $bounds->y &&
                $next->y <= $bounds->y + $bounds->height)
            {
                $p->y = $next->y;
            }

            if ($next->x < $bounds->x)
            {
                $p->x = $bounds->x;
            }
            else if ($next->x > $bounds->x + $bounds->width)
            {
                $p->x = $bounds->x + $bounds->width + 1;
            }

            if ($next->y < $bounds->y)
            {
                $p->y = $bounds->y;
            }
            else if ($next->y > $bounds->y + $bounds->height)
            {
                $p->y = $bounds->y + $bounds->height + 1;
            }
        }

        return $p;
    }
}