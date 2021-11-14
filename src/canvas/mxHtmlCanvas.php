<?php

declare(strict_types=1);

namespace Mxgraph\Canvas;

use Mxgraph\Util\mxConstants;
use Mxgraph\Util\mxUtils;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxHtmlCanvas extends mxGdCanvas
{
    /**
     * Class: mxHtmlCanvas.
     *
     * Canvas for drawing graphs using HTML.
     *
     * Variable: html
     *
     * Holds the html markup.
     */
    public $html;

    /**
     * Constructor: mxGdCanvas.
     *
     * Constructs a new GD canvas. Use a HTML color definition for
     * the optional background parameter, eg. white or #FFFFFF.
     *
     * @param mixed $scale
     * @param mixed $basePath
     */
    public function __construct($scale = 1, $basePath = '')
    {
        parent::__construct(null, null, $scale, null, $basePath);
        $this->html = '';
    }

    /**
     * Function: getHtml.
     *
     * Gets the HTML that represents the canvas.
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Function: out.
     *
     * Adds the specified string to the output.
     *
     * @param mixed $string
     */
    public function out($string)
    {
        return $this->html .= "{$string}\n";
    }

    /**
     * Function: drawLine.
     *
     * Draws the specified line.
     *
     * @param mixed      $x0
     * @param mixed      $y0
     * @param mixed      $x1
     * @param mixed      $y1
     * @param null|mixed $stroke
     * @param mixed      $dashed
     */
    public function drawLine($x0, $y0, $x1, $y1, $stroke = null, $dashed = false): void
    {
        $tmpX = min($x0, $x1);
        $tmpY = min($y0, $y1);
        $w = max($x0, $x1) - $tmpX;
        $h = max($y0, $y1) - $tmpY;
        $x0 = $tmpX;
        $y0 = $tmpY;

        if (0 == $w || 0 == $h) {
            $style = 'position:absolute;'.
                'overflow:hidden;'.
                'left:'.$x0.'px;'.
                'top:'.$y0.'px;'.
                'width:'.$w.'px;'.
                'height:'.$h.'px;'.
                "border-color:{$stroke};".
                'border-style:solid;'.
                'border-width:1 1 0 0px';
            $this->out("<DIV STYLE='{$style}'></DIV>");
        } else {
            $x = $x0 + ($x1 - $x0) / 2;
            $this->drawLine($x0, $y0, $x, $y0);
            $this->drawLine($x, $y0, $x, $y1);
            $this->drawLine($x, $y1, $x1, $y1);
        }
    }

    /**
     * Function: drawShape.
     *
     * Draws the specified shape.
     *
     * @param mixed      $shape
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $stroke
     * @param null|mixed $fill
     */
    public function drawShape($shape, $x, $y, $w, $h, $stroke = null, $fill = null): void
    {
        $style = 'position:absolute;'.
            'left:'.$x.'px;'.
            'top:'.$y.'px;'.
            'width:'.$w.'px;'.
            'height:'.$h.'px;'.
            'border-style:solid;'.
            "border-color:{$stroke};".
            'border-width:1px;'.
            "background-color:{$fill};";
        $this->out("<DIV STYLE='{$style}'></DIV>");
    }

    /**
     * Function: drawImage.
     *
     * Draws the specified image.
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $image
     * @param mixed $aspect
     * @param mixed $flipH
     * @param mixed $flipV
     */
    public function drawImage($x, $y, $w, $h, $image, $aspect = true, $flipH = false, $flipV = false): void
    {
        $style = 'position:absolute;'.
            'left:'.$x.'px;'.
            'top:'.$y.'px;'.
            'width:'.$w.'px;'.
            'height:'.$h.'px;';
        $this->out("<IMAGE SRC='{$image}' STYLE='{$style}'/>");
    }

    /**
     * Function: drawText.
     *
     * Draws the specified text.
     *
     * @param mixed $string
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $style
     */
    public function drawText($string, $x, $y, $w, $h, $style): void
    {
        $horizontal = mxUtils::getValue($style, mxConstants::$STYLE_HORIZONTAL, 1);
        $font = mxUtils::getValue(
            $style,
            mxConstants::$STYLE_FONTFAMILY,
            mxConstants::$W3C_DEFAULT_FONTFAMILY
        );
        $fontSize = mxUtils::getValue(
            $style,
            mxConstants::$STYLE_FONTSIZE,
            mxConstants::$DEFAULT_FONTSIZE
        ) * $this->scale;
        $color = mxUtils::getValue($style, mxConstants::$STYLE_FONTCOLOR, 'black');
        $align = mxUtils::getValue($style, mxConstants::$STYLE_ALIGN, 'center');
        $valign = mxUtils::getValue($style, mxConstants::$STYLE_VERTICAL_ALIGN, 'middle');

        $style = 'position:absolute;'.
            'overflow:hidden;'.
            'left:'.($x - 4).'px;'.
            'width:'.$w.'px;'.
            'height:'.$h.'px;'.
            "font-family:{$font};".
            'font-size:'.$fontSize.'px;'.
            "color:{$color};";

        if ($horizontal) {
            $style .= 'top:'.($y - 5).'px;';
        } else {
            $style .= 'top:'.($y - $h).'px;';
        }

        $string = htmlentities($string);
        $string = str_replace("\n", '<br>', $string);
        $this->out("<TABLE STYLE='{$style}'>".
            "<TR><TD ALIGN='{$align}' VALIGN='{$valign}'>".
            "{$string}</TD></TR></TABLE>");
    }

    /**
     * Destructor: destroy.
     *
     * Destroy all allocated resources.
     */
    public function destroy(): void
    {
        $this->html = '';
    }

    /**
     * Function: drawGraph.
     *
     * Draws the given graph using this canvas.
     *
     * @param mixed      $graph
     * @param null|mixed $clip
     * @param null|mixed $bg
     */
    public static function drawGraph($graph, $clip = null, $bg = null)
    {
        $graph->view->validate();

        $canvas = new self($graph->view->scale);
        $graph->drawGraph($canvas);

        return $canvas->getHtml();
    }
}
