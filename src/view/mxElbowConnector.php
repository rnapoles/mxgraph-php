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
use Mxgraph\Util\mxUtils;

/**
 * Class: mxElbowConnector.
 *
 * Uses either <SideToSide> or <TopToBottom> depending on the horizontal
 * flag in the cell style. <SideToSide> is used if horizontal is true or
 * unspecified. See <EntityRelation> for a description of the
 * parameters.
 */
class mxElbowConnector implements mxEdgeStyleFunction
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
        $pt = (null != $points && \count($points) > 0) ? $points[0] : null;

        $vertical = false;
        $horizontal = false;

        if (null != $source && null != $target) {
            if (null != $pt) {
                $left = min($source->x, $target->x);
                $right = max(
                    $source->x + $source->width,
                    $target->x + $target->width
                );

                $top = min($source->y, $target->y);
                $bottom = max(
                    $source->y + $source->height,
                    $target->y + $target->height
                );

                $pt = $state->view->transformControlPoint($state, $pt);

                $vertical = $pt->y < $top || $pt->y > $bottom;
                $horizontal = $pt->x < $left || $pt->x > $right;
            } else {
                $left = max($source->x, $target->x);
                $right = min(
                    $source->x + $source->width,
                    $target->x + $target->width
                );

                $vertical = $left == $right;

                if (!$vertical) {
                    $top = max($source->y, $target->y);
                    $bottom = min(
                        $source->y + $source->height,
                        $target->y + $target->height
                    );

                    $horizontal = $top == $bottom;
                }
            }
        }

        if (!$horizontal && ($vertical
                || mxUtils::getValue($state->style, mxConstants::$STYLE_ELBOW) == mxConstants::$ELBOW_VERTICAL)) {
            mxEdgeStyle::$TopToBottom->apply($state, $source, $target, $points, $result);
        } else {
            mxEdgeStyle::$SideToSide->apply($state, $source, $target, $points, $result);
        }
    }
}
