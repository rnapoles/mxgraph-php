<?php

declare(strict_types=1);

namespace Mxgraph\View;

use Mxgraph\Util\mxConstants;
use Mxgraph\Util\mxEvent;
use Mxgraph\Util\mxEventObject;
use Mxgraph\Util\mxEventSource;
use Mxgraph\Util\mxPoint;
use Mxgraph\Util\mxRectangle;
use Mxgraph\Util\mxUtils;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxGraphView extends mxEventSource
{
    /**
     * Class: mxGraphView.
     *
     * Implements a view for the graph. Fires scale and translate events
     * if one of the values change.
     *
     * This class fires the following events:
     *
     * mxEvent.SCALE fires after the scale was changed in setScale. The
     * <code>scale</code> and <code>previousScale</code> arguments contain the
     * new and previous scale.
     *
     * mxEvent.TRANSLATE fires after the translate was changed in setTranslate. The
     * <code>translate</code> and <code>previousTranslate</code> arguments contain
     * the new and previous value for translate.
     *
     * Variable: EMPTY_POINT
     *
     * An empty <mxPoint> instance.
     */
    public $EMPTY_POINT;

    /**
     * Variable: graph.
     *
     * Holds the <mxGraph>.
     */
    public $graph;

    /**
     * Variable: graphBounds.
     *
     * Holds the bounds of the current view.
     */
    public $graphBounds;

    /**
     * Variable: scale.
     *
     * Holds the current scale.
     */
    public $scale = 1;

    /**
     * Variable: translate.
     *
     * Holds the current translate.
     */
    public $translate;

    /**
     * Variable: states.
     *
     * Maps from cells to states.
     */
    public $states = [];

    /**
     * Constructor: mxGraphView.
     *
     * Constructs a new view for the specified <mxGraph>.
     *
     * @param mixed $graph
     */
    public function __construct($graph)
    {
        $this->EMPTY_POINT = new mxPoint();
        $this->graph = $graph;
        $this->translate = new mxPoint();
        $this->graphBounds = new mxRectangle();
    }

    /**
     * Function: setScale.
     *
     * Sets the scale, revalidates the view and fires
     * a scale event.
     *
     * @param mixed $scale
     */
    public function setScale($scale): void
    {
        $previous = $this->scale;

        if ($this->scale != $scale) {
            $this->scale = $scale;
            $this->revalidate();
        }

        $this->fireEvent(new mxEventObject(mxEvent::$SCALE, 'scale', $scale, 'previousScale', $previous));
    }

    /**
     * Function: setTranslate.
     *
     * Sets the translation, revalidates the view and
     * fires a translate event.
     *
     * @param mixed $translate
     */
    public function setTranslate($translate): void
    {
        $previous = $this->translate;

        if ($this->translate->x != $translate->x
            || $this->translate->y != $translate->y) {
            $this->translate = $translate;
            $this->revalidate();
        }

        $this->fireEvent(new mxEventObject(mxEvent::$TRANSLATE, 'translate', $translate, 'previousTranslate', $previous));
    }

    /**
     * Function: getGraphBounds.
     *
     * Returns <graphBounds>.
     */
    public function getGraphBounds()
    {
        return $this->graphBounds;
    }

    /**
     * Function: setGraphBounds.
     *
     * Sets <graphBounds>.
     *
     * @param mixed $value
     */
    public function setGraphBounds($value): void
    {
        $this->graphBounds = $value;
    }

    /**
     * Function: getBounds.
     *
     * Returns the bounding for for an array of cells or null, if no cells are
     * specified.
     *
     * @param mixed $cells
     * @param mixed $boundingBox
     */
    public function getBounds($cells, $boundingBox = false)
    {
        $cellCount = \count($cells);
        $result = null;

        if ($cellCount > 0) {
            $model = $this->graph->getModel();

            for ($i = 0; $i < $cellCount; ++$i) {
                if ($model->isVertex($cells[$i]) || $model->isEdge($cells[$i])) {
                    $state = $this->getState($cells[$i]);

                    if (null != $state) {
                        $bounds = ($boundingBox) ? $state->boundingBox : $state;

                        if (null != $bounds) {
                            if (null == $result) {
                                $result = new mxRectangle(
                                    $bounds->x,
                                    $bounds->y,
                                    $bounds->width,
                                    $bounds->height
                                );
                            } else {
                                $result->add($bounds);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Function: invalidate.
     */
    public function revalidate(): void
    {
        $this->invalidate();
        $this->validate();
    }

    /**
     * Function: invalidate.
     *
     * Invalidates the cached cell states.
     */
    public function invalidate(): void
    {
        // LATER: Invalidate cell states recursively
        $this->states = [];
    }

    /**
     * Function: validate.
     *
     * Calls <validateCell> and <validateCellState> and updates the <graphBounds>
     * using <getBoundingBox>. Finally the background is validated using
     * <validateBackground>.
     *
     * Parameters:
     *
     * cell - Optional <mxCell> to be used as the root of the validation.
     * Default is the root of the model.
     *
     * @param null|mixed $cell
     */
    public function validate($cell = null): void
    {
        // Checks if cache is invalid
        if (0 == \count($this->states)) {
            $graphBounds = $this->getBoundingBox($this->validateCellState(
                $this->validateCell((null != $cell) ? $cell : $this->graph->model->root)
            ));
            $this->setGraphBounds($graphBounds ?? new mxRectangle());
        }
    }

    /**
     * Function: getBoundingBox.
     *
     * Returns the bounding box of the shape and the label for the given
     * <mxCellState> and its children if recurse is true.
     *
     * Parameters:
     *
     * state - <mxCellState> whose bounding box should be returned.
     * recurse - Optional boolean indicating if the children should be included.
     * Default is true.
     *
     * @param mixed $state
     * @param mixed $recurse
     */
    public function getBoundingBox($state, $recurse = true)
    {
        $bbox = null;

        if (null != $state) {
            if (null != $state->boundingBox) {
                $bbox = $state->boundingBox->copy();
            }

            if ($recurse) {
                $model = $this->graph->getModel();
                $childCount = $model->getChildCount($state->cell);

                for ($i = 0; $i < $childCount; ++$i) {
                    $bounds = $this->getBoundingBox($this->getState($model->getChildAt($state->cell, $i)));

                    if (null != $bounds) {
                        if (null == $bbox) {
                            $bbox = $bounds;
                        } else {
                            $bbox->add($bounds);
                        }
                    }
                }
            }
        }

        return $bbox;
    }

    /**
     * Function: validateCell.
     *
     * Recursively creates the cell state for the given cell if visible is true and
     * the given cell is visible. If the cell is not visible but the state exists
     * then it is removed using <removeState>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose <mxCellState> should be created.
     * visible - Optional boolean indicating if the cell should be visible. Default
     * is true.
     *
     * @param mixed $cell
     * @param mixed $visible
     */
    public function validateCell($cell, $visible = true)
    {
        if (null != $cell) {
            $visible = $visible && $this->graph->isCellVisible($cell);
            $state = $this->getState($cell, $visible);

            if (null != $state && !$visible) {
                $this->removeState($cell);
            } else {
                $model = $this->graph->getModel();
                $childCount = $model->getChildCount($cell);

                for ($i = 0; $i < $childCount; ++$i) {
                    $this->validateCell($model->getChildAt($cell, $i), $visible
                        && !$this->graph->isCellCollapsed($cell));
                }
            }
        }

        return $cell;
    }

    /**
     * Function: validateCellStates.
     *
     * Validates and repaints the <mxCellState> for the given <mxCell>.
     *
     * Parameters:
     *
     * cell - <mxCell> whose <mxCellState> should be validated.
     * recurse - Optional boolean indicating if the children of the cell should be
     * validated. Default is true.
     *
     * @param mixed $cell
     * @param mixed $recurse
     */
    public function validateCellState($cell, $recurse = true)
    {
        $state = null;

        if (null != $cell) {
            $state = $this->getState($cell);

            if (null != $state) {
                $model = $this->graph->getModel();

                if ($state->invalid) {
                    $state->invalid = false;

                    $this->validateCellState($model->getParent($cell), false);
                    $source = $this->validateCellState($this->getVisibleTerminal($cell, true), false);
                    $target = $this->validateCellState($this->getVisibleTerminal($cell, false), false);

                    $this->updateCellState($state, $source, $target);

                    if ($model->isEdge($cell) || $model->isVertex($cell)) {
                        $this->updateLabelBounds($state);
                        $this->updateBoundingBox($state);
                    }
                }

                if ($recurse) {
                    $childCount = $model->getChildCount($cell);

                    for ($i = 0; $i < $childCount; ++$i) {
                        $this->validateCellState($model->getChildAt($cell, $i));
                    }
                }
            }
        }

        return $state;
    }

    /**
     * Function: updateCellState.
     *
     * Updates the given <mxCellState>.
     *
     * Parameters:
     *
     * state - <mxCellState> to be updated.
     * source - <mxCellState> that represents the visible source.
     * target - <mxCellState> that represents the visible target.
     *
     * @param mixed $state
     * @param mixed $source
     * @param mixed $target
     */
    public function updateCellState($state, $source, $target): void
    {
        $state->absoluteOffset->x = 0;
        $state->absoluteOffset->y = 0;
        $state->origin->x = 0;
        $state->origin->y = 0;
        $state->length = 0;

        $model = $this->graph->getModel();
        $pState = $this->getState($model->getParent($state->cell));

        if (null != $pState) {
            $state->origin->x += $pState->origin->x;
            $state->origin->y += $pState->origin->y;
        }

        $offset = $this->graph->getChildOffsetForCell($state->cell);

        if (null != $offset) {
            $state->origin->x += $offset->x;
            $state->origin->y += $offset->y;
        }

        $geo = $this->graph->getCellGeometry($state->cell);

        if (null != $geo) {
            if (!$model->isEdge($state->cell)) {
                $offset = $geo->offset;

                if (null == $offset) {
                    $offset = $this->EMPTY_POINT;
                }

                if ($geo->relative && null != $pState) {
                    if ($model->isEdge($pState->cell)) {
                        $origin = $this->getPoint($pState, $geo);

                        if (null != $origin) {
                            $state->origin->x += ($origin->x / $this->scale) - $pState->origin->x - $this->translate->x;
                            $state->origin->y += ($origin->y / $this->scale) - $pState->origin->y - $this->translate->y;
                        }
                    } else {
                        $state->origin->x += $geo->x * $pState->width / $this->scale + $offset->x;
                        $state->origin->y += $geo->y * $pState->height / $this->scale + $offset->y;
                    }
                } else {
                    $state->absoluteOffset->x = $this->scale * $offset->x;
                    $state->absoluteOffset->y = $this->scale * $offset->y;
                    $state->origin->x += $geo->x;
                    $state->origin->y += $geo->y;
                }
            }

            $state->x = $this->scale * ($this->translate->x + $state->origin->x);
            $state->y = $this->scale * ($this->translate->y + $state->origin->y);
            $state->width = $this->scale * $geo->width;
            $state->height = $this->scale * $geo->height;

            if ($model->isVertex($state->cell)) {
                $this->updateVertexState($state, $geo);
            }

            if ($model->isEdge($state->cell)) {
                $this->updateEdgeState($state, $geo, $source, $target);
            }
        }
    }

    /**
     * Function: updateVertexState.
     *
     * Validates the given cell state.
     *
     * @param mixed $state
     * @param mixed $geo
     */
    public function updateVertexState($state, $geo): void
    {
        // LATER: Add support for rotation
        $this->updateVertexLabelOffset($state);
    }

    /**
     * Function: updateEdgeState.
     *
     * Validates the given cell state.
     *
     * @param mixed $state
     * @param mixed $geo
     * @param mixed $source
     * @param mixed $target
     */
    public function updateEdgeState($state, $geo, $source, $target): void
    {
        // This will remove edges with no terminals and no terminal points
        // as such edges are invalid and produce NPEs in the edge styles.
        // Also removes connected edges that have no visible terminals.
        if ((null != $this->graph->model->getTerminal($state->cell, true) && null == $source)
            || (null == $source && null == $geo->getTerminalPoint(true))
            || (null != $this->graph->model->getTerminal($state->cell, false) && null == $target)
            || (null == $target && null == $geo->getTerminalPoint(false))) {
            $this->removeState($state->cell, true);
        } else {
            $this->updateFixedTerminalPoints($state, $source, $target);
            $this->updatePoints($state, $geo->points, $source, $target);
            $this->updateFloatingTerminalPoints($state, $source, $target);

            $pts = $state->absolutePoints;

            if (null == $pts || \count($pts) < 1 || null == $pts[0] || null == $pts[\count($pts) - 1]) {
                // This will remove edges with invalid points from the list of states in the view.
                // Happens if the one of the terminals and the corresponding terminal point is null.
                $this->removeState($state->cell, true);
            } else {
                $this->updateEdgeBounds($state);
                $state->absoluteOffset = $this->getPoint($state, $geo);
            }
        }
    }

    /**
     * Function: updateVertexLabelOffset.
     *
     * Updates the absoluteOffset of the given vertex cell state. This takes
     * into account the label position styles.
     *
     * Parameters:
     *
     * state - <mxCellState> whose absolute offset should be updated.
     *
     * @param mixed $state
     */
    public function updateVertexLabelOffset($state): void
    {
        $horizontal = mxUtils::getValue(
            $state->style,
            mxConstants::$STYLE_LABEL_POSITION,
            mxConstants::$ALIGN_CENTER
        );

        if ($horizontal == mxConstants::$ALIGN_LEFT) {
            $state->absoluteOffset->x -= $state->width;
        } elseif ($horizontal == mxConstants::$ALIGN_RIGHT) {
            $state->absoluteOffset->x += $state->width;
        }

        $vertical = mxUtils::getValue(
            $state->style,
            mxConstants::$STYLE_VERTICAL_LABEL_POSITION,
            mxConstants::$ALIGN_MIDDLE
        );

        if ($vertical == mxConstants::$ALIGN_TOP) {
            $state->absoluteOffset->y -= $state->height;
        } elseif ($vertical == mxConstants::$ALIGN_BOTTOM) {
            $state->absoluteOffset->y += $state->height;
        }
    }

    /**
     * Function: updateLabelBounds.
     *
     * Updates the label bounds in the given state.
     *
     * @param mixed $state
     */
    public function updateLabelBounds($state): void
    {
        $cell = $state->cell;
        $style = $state->style;

        if ('fill' == mxUtils::getValue($style, mxConstants::$STYLE_OVERFLOW)) {
            $state->labelBounds = new mxRectangle($state->x, $state->y, $state->width, $state->height);
        } else {
            $label = $this->graph->getLabel($cell);
            $vertexBounds = (!$this->graph->model->isEdge($cell)) ? $state : null;
            $state->labelBounds = mxUtils::getLabelPaintBounds(
                $label,
                $style,
                false,
                $state->absoluteOffset,
                $vertexBounds,
                $this->scale
            );
        }
    }

    /**
     * Function: updateBoundingBox.
     *
     * Updates the bounding box in the given cell state.
     *
     * @param mixed $state
     */
    public function updateBoundingBox($state)
    {
        // Gets the cell bounds and adds shadows and markers
        $rect = new mxRectangle($state->x, $state->y, $state->width, $state->height);
        $style = $state->style;

        // Adds extra pixels for the marker and stroke assuming
        // that the border stroke is centered around the bounds
        // and the first pixel is drawn inside the bounds
        $strokeWidth = max(1, mxUtils::getNumber(
            $style,
            mxConstants::$STYLE_STROKEWIDTH,
            1
        ) * $this->scale);
        $strokeWidth -= max(1, $strokeWidth / 2);

        if ($this->graph->model->isEdge($state->cell)) {
            $ms = 0;

            if (isset($style[mxConstants::$STYLE_ENDARROW])
                    || isset($style[mxConstants::$STYLE_STARTARROW])) {
                $ms = round(mxConstants::$DEFAULT_MARKERSIZE * $this->scale);
            }

            // Adds the strokewidth
            $rect->grow($ms + $strokeWidth);

            // Adds worst case border for an arrow shape
            if (mxUtils::getValue($style, mxConstants::$STYLE_SHAPE) ==
                    mxConstants::$SHAPE_ARROW) {
                $rect->grow(mxConstants::$ARROW_WIDTH / 2);
            }
        } else {
            $rect->grow($strokeWidth);
        }

        // Adds extra pixels for the shadow
        if (true == mxUtils::getValue($style, mxConstants::$STYLE_SHADOW, false)) {
            $rect->width += mxConstants::$SHADOW_OFFSETX;
            $rect->height += mxConstants::$SHADOW_OFFSETY;
        }

        // Adds oversize images in labels
        if (mxUtils::getValue($style, mxConstants::$STYLE_SHAPE) ==
            mxConstants::$SHAPE_LABEL) {
            if (null != mxUtils::getValue($style, mxConstants::$STYLE_IMAGE)) {
                $w = mxUtils::getValue(
                    $style,
                    mxConstants::$STYLE_IMAGE_WIDTH,
                    mxConstants::$DEFAULT_IMAGESIZE
                ) * $this->scale;
                $h = mxUtils::getValue(
                    $style,
                    mxConstants::$STYLE_IMAGE_HEIGHT,
                    mxConstants::$DEFAULT_IMAGESIZE
                ) * $this->scale;

                $x = $state->x;
                $y = 0;

                $imgAlign = mxUtils::getValue(
                    $style,
                    mxConstants::$STYLE_IMAGE_ALIGN,
                    mxConstants::$ALIGN_CENTER
                );
                $imgValign = mxUtils::getValue(
                    style,
                    mxConstants::$STYLE_IMAGE_VERTICAL_ALIGN,
                    mxConstants::$ALIGN_MIDDLE
                );

                if ($imgAlign == mxConstants::$ALIGN_RIGHT) {
                    $x += $state->width - $w;
                } elseif ($imgAlign == mxConstants::$ALIGN_CENTER) {
                    $x += ($state->width - $w) / 2;
                }

                if ($imgValign == mxConstants::$ALIGN_TOP) {
                    $y = $state->y;
                } elseif ($imgValign == mxConstants::$ALIGN_BOTTOM) {
                    $y = $state->y + $state->height - $h;
                } else {
                    $y = $state->y + ($state->height - $h) / 2;
                }

                $rect->add(new mxRectangle($x, $y, $w, $h));
            }
        }

        // No need to add rotated rectangle bounds here because
        // GD does not support rotation

        // Unifies the cell bounds and the label bounds
        $rect->add($state->labelBounds);
        $state->boundingBox = $rect;

        return $rect;
    }

    /**
     * Function: updateFixedTerminalPoints.
     *
     * Sets the initial absolute terminal points in the given state before the edge
     * style is computed.
     *
     * Parameters:
     *
     * edge - <mxCellState> whose initial terminal points should be updated.
     * source - <mxCellState> which represents the source terminal.
     * target - <mxCellState> which represents the target terminal.
     *
     * @param mixed $edge
     * @param mixed $source
     * @param mixed $target
     */
    public function updateFixedTerminalPoints($edge, $source, $target): void
    {
        $this->updateFixedTerminalPoint(
            $edge,
            $source,
            true,
            $this->graph->getConnectionConstraint($edge, $source, true)
        );
        $this->updateFixedTerminalPoint(
            $edge,
            $target,
            false,
            $this->graph->getConnectionConstraint($edge, $target, false)
        );
    }

    /**
     * Function: updateFixedTerminalPoint.
     *
     * Sets the fixed source or target terminal point on the given edge.
     *
     * Parameters:
     *
     * edge - <mxCellState> whose terminal point should be updated.
     * terminal - <mxCellState> which represents the actual terminal.
     * source - Boolean that specifies if the terminal is the source.
     * constraint - <mxConnectionConstraint> that specifies the connection.
     *
     * @param mixed $edge
     * @param mixed $terminal
     * @param mixed $source
     * @param mixed $constraint
     */
    public function updateFixedTerminalPoint($edge, $terminal, $source, $constraint): void
    {
        $pt = null;

        if (isset($constraint)) {
            $pt = $this->graph->getConnectionPoint($terminal, $constraint);
        }

        if (!isset($pt) && !isset($terminal)) {
            $s = $this->scale;
            $tr = $this->translate;
            $orig = $edge->origin;
            $geo = $this->graph->getCellGeometry($edge->cell);
            $pt = $geo->getTerminalPoint($source);

            if (isset($pt)) {
                $pt = new mxPoint(
                    $s * ($tr->x + $pt->x + $orig->x),
                    $s * ($tr->y + $pt->y + $orig->y)
                );
            }
        }

        if (!\is_array($edge->absolutePoints)) {
            $edge->absolutePoints = [];
        }

        $n = \count($edge->absolutePoints);

        if ($source) {
            if ($n > 0) {
                $state->absolutePoints[0] = $pt;
            } else {
                $edge->absolutePoints[] = $pt;
            }
        } else {
            $n = \count($edge->absolutePoints);

            if ($n > 1) {
                $edge->absolutePoints[$n - 1] = $pt;
            } else {
                $edge->absolutePoints[] = $pt;
            }
        }
    }

    /**
     * Function: updatePoints.
     *
     * Updates the absolute points in the given state using the specified array
     * of <mxPoints> as the relative points.
     *
     * Parameters:
     *
     * edge - <mxCellState> whose absolute points should be updated.
     * points - Array of <mxPoints> that constitute the relative points.
     * source - <mxCellState> that represents the source terminal.
     * target - <mxCellState> that represents the target terminal.
     *
     * @param mixed $edge
     * @param mixed $points
     * @param mixed $source
     * @param mixed $target
     */
    public function updatePoints($edge, $points, $source, $target): void
    {
        if (isset($edge)) {
            $pts = [];
            $pts[] = $edge->absolutePoints[0];
            $edgeStyle = $this->getEdgeStyle($edge, $points, $source, $target);

            if (isset($edgeStyle)) {
                $src = $this->getTerminalPort($edge, $source, true);
                $trg = $this->getTerminalPort($edge, $target, false);

                $edgeStyle->apply($edge, $src, $trg, $points, $pts);
            } elseif (isset($points)) {
                for ($i = 0; $i < \count($points); ++$i) {
                    if (isset($points[$i])) {
                        $pt = $points[$i]->copy();
                        $pts[] = $this->transformControlPoint($edge, $pt);
                    }
                }
            }

            $n = \count($edge->absolutePoints);
            $pts[] = $edge->absolutePoints[$n - 1];

            $edge->absolutePoints = $pts;
        }
    }

    /**
     * Function: transformControlPoint.
     *
     * Transforms the given control point to an absolute point.
     *
     * @param mixed $state
     * @param mixed $pt
     */
    public function transformControlPoint($state, $pt)
    {
        $orig = $state->origin;

        return new mxPoint(
            $this->scale * ($pt->x + $this->translate->x + $orig->x),
            $this->scale * ($pt->y + $this->translate->y + $orig->y)
        );
    }

    /**
     * Function: getEdgeStyle.
     *
     * Returns the edge style function to be used to render the given edge
     * state.
     *
     * @param mixed $edge
     * @param mixed $points
     * @param mixed $source
     * @param mixed $target
     */
    public function getEdgeStyle($edge, $points, $source, $target)
    {
        $edgeStyle = null;

        if (isset($source) && $source === $target) {
            $edgeStyle = mxUtils::getValue($edge->style, mxConstants::$STYLE_LOOP);

            if (!isset($edgeStyle)) {
                $edgeStyle = $this->graph->defaultLoopStyle;
            }
        } elseif (!mxUtils::getValue($edge->style, mxConstants::$STYLE_NOEDGESTYLE, false)) {
            $edgeStyle = mxUtils::getValue($edge->style, mxConstants::$STYLE_EDGE);
        }

        // Converts string values to objects
        if (\is_string($edgeStyle)) {
            $tmp = mxStyleRegistry::getValue($edgeStyle);

            if (null == $tmp && false !== strpos($edgeStyle, '.')) {
                $tmp = mxUtils::evaluate($edgeStyle);
            }

            $edgeStyle = $tmp;
        }

        if ($edgeStyle instanceof mxEdgeStyleFunction) {
            return $edgeStyle;
        }

        return null;
    }

    /**
     * Function: updateFloatingTerminalPoints.
     *
     * Updates the terminal points in the given state after the edge style was
     * computed for the edge.
     *
     * Parameters:
     *
     * state - <mxCellState> whose terminal points should be updated.
     * source - <mxCellState> that represents the source terminal.
     * target - <mxCellState> that represents the target terminal.
     *
     * @param mixed $state
     * @param mixed $source
     * @param mixed $target
     */
    public function updateFloatingTerminalPoints($state, $source, $target): void
    {
        $pts = $state->absolutePoints;
        $p0 = $pts[0];
        $pe = $pts[\count($pts) - 1];

        if (!isset($pe) && isset($target)) {
            $this->updateFloatingTerminalPoint($state, $target, $source, false);
        }

        if (!isset($p0) && isset($source)) {
            $this->updateFloatingTerminalPoint($state, $source, $target, true);
        }
    }

    /**
     * Function: updateFloatingTerminalPoint.
     *
     * Updates the absolute terminal point in the given state for the given
     * start and end state, where start is the source if source is true.
     *
     * Parameters:
     *
     * edge - <mxCellState> whose terminal point should be updated.
     * start - <mxCellState> for the terminal on "this" side of the edge.
     * end - <mxCellState> for the terminal on the other side of the edge.
     * source - Boolean indicating if start is the source terminal state.
     *
     * @param mixed $edge
     * @param mixed $start
     * @param mixed $end
     * @param mixed $source
     */
    public function updateFloatingTerminalPoint($edge, $start, $end, $source): void
    {
        $start = $this->getTerminalPort($edge, $start, $source);
        $next = $this->getNextPoint($edge, $end, $source);
        $border = mxUtils::getNumber($edge->style, mxConstants::$STYLE_PERIMETER_SPACING);
        $border = mxUtils::getNumber($edge->style, ($source) ?
            mxConstants::$STYLE_SOURCE_PERIMETER_SPACING :
            mxConstants::$STYLE_TARGET_PERIMETER_SPACING);
        $pt = $this->getPerimeterPoint($start, $next, $this->graph->isOrthogonal($edge), $border);
        $index = ($source) ? 0 : \count($edge->absolutePoints) - 1;
        $edge->absolutePoints[$index] = $pt;
    }

    /**
     * Function: getTerminalPort.
     *
     * Returns an <mxCellState> that represents the source or target terminal or
     * port for the given edge.
     *
     * Parameters:
     *
     * state - <mxCellState> that represents the state of the edge.
     * terminal - <mxCellState> that represents the terminal.
     * source - Boolean indicating if the given terminal is the source terminal.
     *
     * @param mixed $state
     * @param mixed $terminal
     * @param mixed $source
     */
    public function getTerminalPort($state, $terminal, $source)
    {
        $key = ($source) ? mxConstants::$STYLE_SOURCE_PORT
                : mxConstants::$STYLE_TARGET_PORT;
        $id = mxUtils::getValue($state->style, $key);

        if (null != $id) {
            $tmp = $this->getState($this->graph->model->getCell($id));

            // Only uses ports where a cell state exists
            if (isset($tmp)) {
                $terminal = $tmp;
            }
        }

        return $terminal;
    }

    /**
     * Function: getPerimeterPoint.
     *
     * Returns an <mxPoint> that defines the location of the intersection point between
     * the perimeter and the line between the center of the shape and the given point.
     *
     * Parameters:
     *
     * terminal - <mxCellState> for the source or target terminal.
     * next - <mxPoint> that lies outside of the given terminal.
     * orthogonal - Boolean that specifies if the orthogonal projection onto
     * the perimeter should be returned. If this is false then the intersection
     * of the perimeter and the line between the next and the center point is
     * returned.
     * border - Optional border between the perimeter and the shape.
     *
     * @param mixed      $terminal
     * @param mixed      $next
     * @param mixed      $orthogonal
     * @param null|mixed $border
     */
    public function getPerimeterPoint($terminal, $next, $orthogonal, $border = null)
    {
        $point = null;

        if (null != $terminal) {
            $perimeter = $this->getPerimeterFunction($terminal);

            if (isset($perimeter, $next)) {
                $bounds = $this->getPerimeterBounds($terminal, $border);

                if ($bounds->width > 0 || $bounds->height > 0) {
                    $point = $perimeter->apply($bounds, $terminal, $next, $orthogonal);
                }
            }

            if (!isset($point)) {
                $point = $this->getPoint($terminal);
            }
        }

        return $point;
    }

    /**
     * Function: getRoutingCenterX.
     *
     * Returns the x-coordinate of the center point for automatic routing.
     *
     * @param mixed $state
     */
    public function getRoutingCenterX($state)
    {
        $f = (null != $state->style) ? mxUtils::getNumber(
            $state->style,
            mxConstants::$STYLE_ROUTING_CENTER_X
        ) : 0;

        return $state->getCenterX() + $f * $state->width;
    }

    /**
     * Function: getRoutingCenterY.
     *
     * Returns the y-coordinate of the center point for automatic routing.
     *
     * @param mixed $state
     */
    public function getRoutingCenterY($state)
    {
        $f = (null != $state->style) ? mxUtils::getNumber(
            $state->style,
            mxConstants::$STYLE_ROUTING_CENTER_Y
        ) : 0;

        return $state->getCenterY() + $f * $state->height;
    }

    /**
     * Function: getPerimeterBounds.
     *
     * Returns the perimeter bounds for the given terminal, edge pair as an
     * <mxRectangle>.
     *
     * Parameters:
     *
     * terminal - <mxCellState> that represents the terminal.
     * border - Number that adds a border between the shape and the perimeter.
     *
     * @param mixed $terminal
     * @param mixed $border
     */
    public function getPerimeterBounds($terminal, $border = 0)
    {
        if (null != $terminal) {
            $border += mxUtils::getNumber($terminal->style, mxConstants::$STYLE_PERIMETER_SPACING);
        }

        return $terminal->getPerimeterBounds($border * $this->scale);
    }

    /**
     * Function: getPerimeterFunction.
     *
     * Returns the perimeter function for the given state.
     *
     * @param mixed $state
     */
    public function getPerimeterFunction($state)
    {
        $perimeter = mxUtils::getValue($state->style, mxConstants::$STYLE_PERIMETER);

        // Converts string values to objects
        if (\is_string($perimeter)) {
            $tmp = mxStyleRegistry::getValue($perimeter);

            if (null == $tmp && false !== strpos($perimeter, '.')) {
                $tmp = mxUtils::evaluate($perimeter);
            }

            $perimeter = $tmp;
        }

        if ($perimeter instanceof mxPerimeterFunction) {
            return $perimeter;
        }

        return null;
    }

    /**
     * Function: getNextPoint.
     *
     * Returns the nearest point in the list of absolute points or the center
     * of the opposite terminal.
     *
     * Parameters:
     *
     * edge - <mxCellState> that represents the edge.
     * opposite - <mxCellState> that represents the opposite terminal.
     * source - Boolean indicating if the next point for the source or target
     * should be returned.
     *
     * @param mixed $edge
     * @param mixed $opposite
     * @param mixed $source
     */
    public function getNextPoint($edge, $opposite, $source)
    {
        $pts = $edge->absolutePoints;
        $point = null;

        if (null != $pts && \count($pts) >= 2) {
            $count = \count($pts);
            $index = ($source) ? min(1, $count - 1) : max(0, $count - 2);
            $point = $pts[$index];
        }

        if (!isset($point) && isset($opposite)) {
            $point = new mxPoint($opposite->getCenterX(), $opposite->getCenterY());
        }

        return $point;
    }

    /**
     * Function: getVisibleTerminal.
     *
     * Returns the nearest ancestor terminal that is visible. The edge appears
     * to be connected to this terminal on the display.
     *
     * Parameters:
     *
     * edge - <mxCell> whose visible terminal should be returned.
     * source - Boolean that specifies if the source or target terminal
     * should be returned.
     *
     * @param mixed $edge
     * @param mixed $source
     */
    public function getVisibleTerminal($edge, $source)
    {
        $model = $this->graph->model;
        $result = $model->getTerminal($edge, $source);
        $best = $result;

        while (null != $result) {
            if (!$this->graph->isCellVisible($best)
                || $this->graph->isCellCollapsed($result)) {
                $best = $result;
            }

            $result = $model->getParent($result);
        }

        // Checks if the result is not a layer
        if ($model->getParent($best) === $model->getRoot()) {
            $best = null;
        }

        return $best;
    }

    /**
     * Function: updateEdgeBounds.
     *
     * Updates the bounds of the specified state based on the
     * absolute points in the state.
     *
     * @param mixed $state
     */
    public function updateEdgeBounds($state): void
    {
        $points = $state->absolutePoints;
        $p0 = $points[0];
        $n = \count($points);
        $pe = $points[$n - 1];

        if ($p0->x != $pe->x || $p0->y != $pe->y) {
            $dx = $pe->x - $p0->x;
            $dy = $pe->y - $p0->y;
            $state->terminalDistance = sqrt($dx * $dx + $dy * $dy);
        } else {
            $state->terminalDistance = 0;
        }

        $length = 0;
        $segments = [];
        $pt = $p0;

        if (null != $pt) {
            $minX = $pt->x;
            $minY = $pt->y;
            $maxX = $minX;
            $maxY = $minY;

            for ($i = 1; $i < $n; ++$i) {
                $tmp = $points[$i];
                if (null != $tmp) {
                    $dx = $pt->x - $tmp->x;
                    $dy = $pt->y - $tmp->y;

                    $segment = sqrt($dx * $dx + $dy * $dy);
                    $segments[] = $segment;
                    $length += $segment;
                    $pt = $tmp;

                    $minX = min($pt->x, $minX);
                    $minY = min($pt->y, $minY);
                    $maxX = max($pt->x, $maxX);
                    $maxY = max($pt->y, $maxY);
                }
            }

            $state->length = $length;
            $state->segments = $segments;

            $state->x = $minX;
            $state->y = $minY;
            $state->width = $maxX - $minX;
            $state->height = $maxY - $minY;
        }
    }

    /**
     * Function: getPoint.
     *
     * Returns the absolute point on the edge for the given relative
     * <mxGeometry> as an <mxPoint>. The edge is represented by the given
     * <mxCellState>.
     *
     * Parameters:
     *
     * state - <mxCellState> that represents the state of the parent edge.
     * geometry - <mxGeometry> that represents the relative location.
     *
     * @param mixed      $state
     * @param null|mixed $geometry
     */
    public function getPoint($state, $geometry = null)
    {
        $x = $state->getCenterX();
        $y = $state->getCenterY();

        if (isset($state->segments) && (!isset($geometry) || $geometry->relative)) {
            $gx = (isset($geometry)) ? $geometry->x / 2 : 0;
            $pointCount = \count($state->absolutePoints);
            $dist = ($gx + 0.5) * $state->length;
            $segments = $state->segments;
            $segment = $segments[0];
            $length = 0;
            $index = 1;

            while ($dist > $length + $segment && $index < $pointCount - 1) {
                $length += $segment;
                $segment = $segments[$index++];
            }

            $factor = (0 == $segment) ? 0 : ($dist - $length) / $segment;
            $p0 = $state->absolutePoints[$index - 1];
            $pe = $state->absolutePoints[$index];

            if (null != $p0 && null != $pe) {
                $gy = 0;
                $offsetX = 0;
                $offsetY = 0;

                if (isset($geometry)) {
                    $gy = $geometry->y;
                    $offset = $geometry->offset;

                    if (isset($offset)) {
                        $offsetX = $offset->x;
                        $offsetY = $offset->y;
                    }
                }

                $dx = $pe->x - $p0->x;
                $dy = $pe->y - $p0->y;
                $nx = (0 == $segment) ? 0 : $dy / $segment;
                $ny = (0 == $segment) ? 0 : $dx / $segment;

                $x = $p0->x + $dx * $factor + ($nx * $gy + $offsetX) * $this->scale;
                $y = $p0->y + $dy * $factor - ($ny * $gy - $offsetY) * $this->scale;
            }
        } elseif (isset($geometry)) {
            $offset = $geometry->offset;

            if (isset($offset)) {
                $x += $offset->x;
                $y += $offset->y;
            }
        }

        return new mxPoint($x, $y);
    }

    /**
     * Function: getState.
     *
     * Returns the cell state for the specified cell. If
     * create is true then the state is created and added
     * to the cache if it does not yet exist.
     *
     * @param mixed $cell
     * @param mixed $create
     */
    public function getState($cell, $create = false)
    {
        $state = null;

        if (null != $cell) {
            $id = $this->getHashCode($cell);
            $state = (isset($this->states[$id])) ? $this->states[$id] : null;

            if (null == $state && $create && $this->graph->isCellVisible($cell)) {
                $state = $this->createState($cell);
                $this->states[$id] = $state;
            }
        }

        return $state;
    }

    /**
     * Function: getHashCode.
     *
     * Returns a unique string that represents the given instance.
     *
     * @param mixed $cell
     */
    public function getHashCode($cell)
    {
        // PHP >= 5.2
        if (\function_exists('spl_object_hash')) {
            return spl_object_hash($cell);
        }

        return (string) $cell;
    }

    /**
     * Function: getStates.
     *
     * Returns the <mxCellStates> for the given array of <mxCells>. The array
     * contains all states that are not null, that is, the returned array may
     * have less elements than the given array.
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * Function: getStates.
     *
     * Returns the <mxCellStates> for the given array of <mxCells>. The array
     * contains all states that are not null, that is, the returned array may
     * have less elements than the given array.
     *
     * @param mixed $cells
     */
    public function getCellStates($cells)
    {
        $result = [];
        $count = \count($cells);

        for ($i = 0; $i < $count; ++$i) {
            $state = $this->getState($cells[$i]);

            if (null != $state) {
                $result[] = $state;
            }
        }

        return $result;
    }

    /**
     * Function: removeState.
     *
     * Removes and returns the mxCellState for the given cell.
     *
     * @param mixed $cell
     * @param mixed $recurse
     */
    public function removeState($cell, $recurse = false)
    {
        if ($recurse) {
            $model = $this->graph->getModel();
            $childCount = $model->getChildCount($cell);

            for ($i = 0; $i < $childCount; ++$i) {
                $this->removeState($model->getChildAt($cell, $i), true);
            }
        }

        $state = null;

        if (null != $cell) {
            $id = $this->getHashCode($cell);
            $state = $this->states[$id];
            unset($this->states[$id]);
        }

        return $state;
    }

    /**
     * Function: createState.
     *
     * Creates the state for the specified cell.
     *
     * @param mixed $cell
     */
    public function createState($cell)
    {
        $style = $this->graph->getCellStyle($cell);

        return new mxCellState($this, $cell, $style);
    }
}
