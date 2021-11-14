<?php

declare(strict_types=1);

namespace Mxgraph\Reader;

use Mxgraph\Canvas\mxHtmlCanvas;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxGraphViewHtmlReader extends mxGraphViewImageReader
{
    /**
     * Class: mxGraphViewHtmlReader.
     *
     * A display XML to HTML converter. This allows to create an image of a graph
     * without having to parse and create the graph model using the XML file
     * created for the mxGraphView object in the thin client.
     *
     * Constructor: mxGraphViewHtmlReader
     *
     * Constructs a new HTML graph view reader.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Function: createCanvas.
     *
     * Returns the canvas to be used for rendering.
     *
     * @param mixed $attrs
     */
    public function createCanvas($attrs)
    {
        return new mxHtmlCanvas($this->scale);
    }

    /**
     * Function: convert.
     *
     * Creates the HTML markup for the given display XML string.
     *
     * @param mixed      $string
     * @param null|mixed $background
     */
    public static function convert($string, $background = null)
    {
        $viewReader = new self();

        $viewReader->read($string);
        $html = $viewReader->canvas->getHtml();
        $viewReader->destroy();

        return $html;
    }

    /**
     * Function: convertFile.
     *
     * Creates the HTML markup for the given display XML file.
     *
     * @param mixed      $filename
     * @param null|mixed $background
     */
    public static function convertFile($filename, $background = null)
    {
        $viewReader = new self();

        $viewReader->readFile($filename);
        $html = $viewReader->canvas->getHtml();
        $viewReader->destroy();

        return $html;
    }
}
