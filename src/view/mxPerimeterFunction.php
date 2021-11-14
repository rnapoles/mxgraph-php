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

interface mxPerimeterFunction
{
    /**
     * Interface: mxPerimeterFunction
     *
     * Defines the requirements for a perimeter function.
     *
     * Function: apply
     *
     * Implements a perimeter function.
     *
     * Parameters:
     *
     * bounds - <mxRectangle> that represents the absolute bounds of the
     * vertex.
     * vertex - <mxCellState> that represents the vertex.
     * next - <mxPoint> that represents the nearest neighbour point on the
     * given edge.
     * orthogonal - Boolean that specifies if the orthogonal projection onto
     * the perimeter should be returned. If this is false then the intersection
     * of the perimeter and the line between the next and the center point is
     * returned.
     */
    public function apply($bounds, $vertex, $next, $orthogonal);
}
