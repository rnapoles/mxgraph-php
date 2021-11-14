<?php

namespace Mxgraph;

use Mxgraph\Io\mxCellCodec;
use Mxgraph\Io\mxCodecRegistry;
use Mxgraph\Io\mxModelCodec;
use Mxgraph\Model\mxCell;
use Mxgraph\Model\mxGraphModel;
use Mxgraph\Util\mxConstants;
use Mxgraph\View\mxEdgeStyle;
use Mxgraph\View\mxElbowConnector;
use Mxgraph\View\mxEllipsePerimeter;
use Mxgraph\View\mxEntityRelation;
use Mxgraph\View\mxLoop;
use Mxgraph\View\mxPerimeter;
use Mxgraph\View\mxRectanglePerimeter;
use Mxgraph\View\mxRhombusPerimeter;
use Mxgraph\View\mxSideToSide;
use Mxgraph\View\mxStyleRegistry;
use Mxgraph\View\mxTopToBottom;
use Mxgraph\View\mxTrianglePerimeter;

/**
 * Copyright (c) 2006, Gaudenz Alder
 *
 * Class: mxServer
 *
 * Bootstrapping for the PHP backend. This is version @MXGRAPH-VERSION@
 * of mxGraph.
 *
 * Variable: MXGRAPH-VERSION
 *
 * Constant that holds the current mxGraph version. The current version
 * is @MXGRAPH-VERSION@.
 */

class mxServer
{
    public static function init()
    {
        if (defined('MXGRAPH-VERSION') == false) {
            define("MXGRAPH-VERSION", "@MXGRAPH-VERSION@");
            libxml_disable_entity_loader(true);

            // original from mxCellCodec
            mxCodecRegistry::register(new mxCellCodec(new mxCell()));

            // orignal from mxModelCodec
            mxCodecRegistry::register(new mxModelCodec(new mxGraphModel()));

            // Original from mxPerimeter
            mxPerimeter::$RectanglePerimeter = new mxRectanglePerimeter();
            mxPerimeter::$EllipsePerimeter = new mxEllipsePerimeter();
            mxPerimeter::$RhombusPerimeter = new mxRhombusPerimeter();
            mxPerimeter::$TrianglePerimeter = new mxTrianglePerimeter();

            // Original from mxEdgeStyle
            mxEdgeStyle::$EntityRelation = new mxEntityRelation();
            mxEdgeStyle::$Loop = new mxLoop();
            mxEdgeStyle::$ElbowConnector = new mxElbowConnector();
            mxEdgeStyle::$SideToSide = new mxSideToSide();
            mxEdgeStyle::$TopToBottom = new mxTopToBottom();

            // Orignal from mxStyleRegistry
            mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_ELBOW, mxEdgeStyle::$ElbowConnector);
            mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_ENTITY_RELATION, mxEdgeStyle::$EntityRelation);
            mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_LOOP, mxEdgeStyle::$Loop);
            mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_SIDETOSIDE, mxEdgeStyle::$SideToSide);
            mxStyleRegistry::putValue(mxConstants::$EDGESTYLE_TOPTOBOTTOM, mxEdgeStyle::$TopToBottom);

            mxStyleRegistry::putValue(mxConstants::$PERIMETER_ELLIPSE, mxPerimeter::$EllipsePerimeter);
            mxStyleRegistry::putValue(mxConstants::$PERIMETER_RECTANGLE, mxPerimeter::$RectanglePerimeter);
            mxStyleRegistry::putValue(mxConstants::$PERIMETER_RHOMBUS, mxPerimeter::$RhombusPerimeter);
            mxStyleRegistry::putValue(mxConstants::$PERIMETER_TRIANGLE, mxPerimeter::$TrianglePerimeter);
        }
    }
}
