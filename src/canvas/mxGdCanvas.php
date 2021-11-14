<?php

declare(strict_types=1);

namespace Mxgraph\Canvas;

use Mxgraph\Util\mxConstants;
use Mxgraph\Util\mxPoint;
use Mxgraph\Util\mxUtils;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxGdCanvas
{
    /**
     * Variable: antialias.
     *
     * Specifies if image aspect should be preserved in drawImage. Default is true.
     */
    public static $PRESERVE_IMAGE_ASPECT = true;

    /**
     * Class: mxGdCanvas.
     *
     * Canvas for drawing graphs using the GD library. This class requires GD
     * support (GDLib). Note that rounded corners, gradients and word wrapping
     * are not supported by GD.
     *
     * Variable: antialias
     *
     * Specifies if antialiasing should be enabled. Default is false. NOTE: GD
     * has a known bug where strokeWidths are ignored if this is enabled.
     */
    public $antialias = false;

    /**
     * Variable: enableTtf.
     *
     * Specifies if truetype fonts are enabled if available. Default is <mxConstants.TTF_ENABLED>.
     */
    public $enableTtf;

    /**
     * Variable: shadowColor.
     *
     * Holds the color object for the shadow color defined in
     * <mxConstants.W3C_SHADOWCOLOR>.
     */
    public $shadowColor;

    /**
     * Defines the base path for images with relative paths. Trailing slash
     * is required. Default is an empty string.
     */
    public $imageBasePath;

    /**
     * Variable: imageCache.
     *
     * Holds the image cache.
     */
    public $imageCache = [];

    /**
     * Variable: image.
     *
     * Holds the image.
     */
    public $image;

    /**
     * Variable: height.
     *
     * Holds the height.
     */
    public $scale;

    /**
     * Constructor: mxGdCanvas.
     *
     * Constructs a new GD canvas. Use a HTML color definition for
     * the optional background parameter, eg. white or #FFFFFF.
     * The buffered <image> is only created if the given
     * width and height are greater than 0.
     *
     * @param mixed      $width
     * @param mixed      $height
     * @param mixed      $scale
     * @param null|mixed $background
     * @param mixed      $imageBasePath
     */
    public function __construct(
        $width = 0,
        $height = 0,
        $scale = 1,
        $background = null,
        $imageBasePath = ''
    ) {
        $this->enableTtf = mxConstants::$TTF_ENABLED;
        $this->imageBasePath = $imageBasePath;
        $this->scale = $scale;

        if ($width > 0 && $height > 0) {
            $this->image = @imagecreatetruecolor($width, $height);

            if ($this->antialias
                && \function_exists('imageantialias')) {
                imageantialias($this->image, true);
            }

            if (isset($background)) {
                $color = $this->getColor($background);
                imagefilledrectangle(
                    $this->image,
                    0,
                    0,
                    $width,
                    $height,
                    $color
                );
            }

            $this->shadowColor = $this->getColor(mxConstants::$W3C_SHADOWCOLOR);
        }
    }

    /**
     * Function: loadImage.
     *
     * Returns an image instance for the given URL. If the URL has
     * been loaded before than an instance of the same instance is
     * returned as in the previous call.
     *
     * @param mixed $image
     */
    public function loadImage($image)
    {
        $img = (\array_key_exists($image, $this->imageCache)) ? $this->imageCache[$image] : null;

        if (!isset($img)) {
            $img = mxUtils::loadImage($image);

            if (isset($img)) {
                $this->imageCache[$image] = $img;
            }
        }

        return $img;
    }

    /**
     * Function: drawCell.
     *
     * Draws the given cell state.
     *
     * @param mixed $state
     */
    public function drawCell($state): void
    {
        $style = $state->style;

        if (null != $state->absolutePoints && \count($state->absolutePoints) > 1) {
            $dashed = mxUtils::getNumber($style, mxConstants::$STYLE_DASHED);
            $stroke = mxUtils::getValue($style, mxConstants::$STYLE_STROKECOLOR);
            $strokeWidth = mxUtils::getNumber($style, mxConstants::$STYLE_STROKEWIDTH, 1) * $this->scale;

            if ('none' == $stroke) {
                $stroke = null;
            }

            if (isset($this->image)) {
                // KNOWN: Stroke widths are ignored by GD if antialias is on
                imagesetthickness($this->image, $strokeWidth);
            }

            // Draws the start marker
            $marker = mxUtils::getValue($style, mxConstants::$STYLE_STARTARROW);

            $pts = $state->absolutePoints;
            $p0 = $pts[0];
            $pt = $pts[1];
            $offset = null;

            if (isset($marker)) {
                $size = mxUtils::getNumber(
                    $style,
                    mxConstants::$STYLE_STARTSIZE,
                    mxConstants::$DEFAULT_MARKERSIZE
                );
                $offset = $this->drawMarker($marker, $pt, $p0, $size, $stroke);
            } else {
                $dx = $pt->x - $p0->x;
                $dy = $pt->y - $p0->y;

                $dist = max(1, sqrt($dx * $dx + $dy * $dy));
                $nx = $dx * $strokeWidth / $dist;
                $ny = $dy * $strokeWidth / $dist;

                $offset = new mxPoint($nx / 2, $ny / 2);
            }

            // Applies offset to the point
            if (isset($offset)) {
                $p0 = $p0->copy();
                $p0->x += $offset->x;
                $p0->y += $offset->y;

                unset($offset);
            }

            // Draws the end marker
            $len = \count($pts);
            $marker = mxUtils::getValue($style, mxConstants::$STYLE_ENDARROW);

            $pe = $pts[$len - 1];
            $pt = $pts[$len - 2];
            $offset = null;

            if (isset($marker)) {
                $size = mxUtils::getNumber(
                    $style,
                    mxConstants::$STYLE_ENDSIZE,
                    mxConstants::$DEFAULT_MARKERSIZE
                );
                $offset = $this->drawMarker($marker, $pt, $pe, $size, $stroke);
            } else {
                $dx = $pt->x - $p0->x;
                $dy = $pt->y - $p0->y;

                $dist = max(1, sqrt($dx * $dx + $dy * $dy));
                $nx = $dx * $strokeWidth / $dist;
                $ny = $dy * $strokeWidth / $dist;

                $offset = new mxPoint($nx / 2, $ny / 2);
            }

            // Applies offset to the point
            if (isset($offset)) {
                $pe = $pe->copy();
                $pe->x += $offset->x;
                $pe->y += $offset->y;

                unset($offset);
            }

            // Draws the line segments
            $pt = $p0;

            for ($i = 1; $i < $len - 1; ++$i) {
                $tmp = $pts[$i];
                $this->drawLine($pt->x, $pt->y, $tmp->x, $tmp->y, $stroke, $dashed);
                $pt = $tmp;
            }

            $this->drawLine($pt->x, $pt->y, $pe->x, $pe->y, $stroke, $dashed);

            if (isset($this->image)) {
                imagesetthickness($this->image, 1);
            }
        } else {
            $x = $state->x;
            $y = $state->y;
            $w = $state->width;
            $h = $state->height;

            // Draws the vertex
            if (mxUtils::getValue($style, mxConstants::$STYLE_SHAPE, '') !=
                mxConstants::$SHAPE_SWIMLANE) {
                $this->drawShape($x, $y, $w, $h, $style);
            } else {
                $start = mxUtils::getNumber(
                    $style,
                    mxConstants::$STYLE_STARTSIZE,
                    mxConstants::$DEFAULT_STARTSIZE
                ) * $this->scale;

                // Removes some styles to draw the content area
                $cloned = \array_slice($style, 0);
                $cloned[mxConstants::$STYLE_FILLCOLOR] = $cloned[mxConstants::$STYLE_SWIMLANE_FILLCOLOR];
                unset($cloned[mxConstants::$STYLE_GRADIENTCOLOR], $cloned[mxConstants::$STYLE_ROUNDED]);

                // TODO: Clone style, remove fill and rounded and take into account
                // the label orientation
                //if (mxUtils::getValue($style, mxConstants::$STYLE_HORIZONTAL, true))

                $this->drawShape($x, $y, $w, $h, $cloned);
                $this->drawShape($x, $y, $w, min($h, $start), $style);
            }
        }
    }

    /**
     * Function: drawLabel.
     *
     * Draws the given label.
     *
     * @param mixed $text
     * @param mixed $state
     * @param mixed $html
     */
    public function drawLabel($text, $state, $html = false): void
    {
        $bounds = $state->labelBounds;

        if (isset($bounds)) {
            $x = $bounds->x;
            $y = $bounds->y;
            $w = $bounds->width;
            $h = $bounds->height;

            $this->drawText($text, $x, $y, $w, $h, $state->style);
        }
    }

    /**
     * Function: drawMarker.
     *
     * Draws the specified marker.
     *
     * @param mixed $type
     * @param mixed $p0
     * @param mixed $pe
     * @param mixed $size
     * @param mixed $stroke
     */
    public function drawMarker($type, $p0, $pe, $size, $stroke)
    {
        $offset = null;

        // Computes the norm and the inverse norm
        $dx = $pe->x - $p0->x;
        $dy = $pe->y - $p0->y;

        $dist = max(1, sqrt($dx * $dx + $dy * $dy));
        $absSize = $size * $this->scale;
        $nx = $dx * $absSize / $dist;
        $ny = $dy * $absSize / $dist;

        $pe = $pe->copy();
        $pe->x -= $nx / (2 * $size);
        $pe->y -= $ny / (2 * $size);

        if ($type == mxConstants::$ARROW_CLASSIC
            || $type == mxConstants::$ARROW_BLOCK) {
            $poly = [$pe->x, $pe->y,
                $pe->x - $nx - $ny / 2,
                $pe->y - $ny + $nx / 2, ];

            if ($type == mxConstants::$ARROW_CLASSIC) {
                array_push($poly, $pe->x - $nx * 3 / 4, $pe->y - $ny * 3 / 4);
            }

            array_push($poly, $pe->x + $ny / 2 - $nx, $pe->y - $ny - $nx / 2);
            $this->drawPolygon($poly, $stroke, $stroke, false);

            $offset = new mxPoint(-$nx * 3 / 4, -$ny * 3 / 4);
        } elseif ($type == mxConstants::$ARROW_OPEN) {
            $nx *= 1.2;
            $ny *= 1.2;

            $this->drawLine(
                $pe->x - $nx - $ny / 2,
                $pe->y - $ny + $nx / 2,
                $pe->x - $nx / 6,
                $pe->y - $ny / 6,
                $stroke
            );
            $this->drawLine(
                $pe->x - $nx / 6,
                $pe->y - $ny / 6,
                $pe->x + $ny / 2 - $nx,
                $pe->y - $ny - $nx / 2,
                $stroke
            );

            $offset = new mxPoint(-$nx / 4, -$ny / 4);
        } elseif ($type == mxConstants::$ARROW_OVAL) {
            $nx *= 1.2;
            $ny *= 1.2;
            $absSize *= 1.2;

            $tmp = $absSize / 2;
            $x = $pe->x - $nx / 2 - $tmp;
            $y = $pe->y - $ny / 2 - $tmp;

            $this->drawOval($x, $y, $absSize, $absSize, $stroke, $stroke, false);

            $offset = new mxPoint(-$nx / 2, -$ny / 2);
        } elseif ($type == mxConstants::$ARROW_DIAMOND) {
            $nx *= 1.2;
            $ny *= 1.2;

            $poly = [$pe->x + $nx / 2, $pe->y + $ny / 2,
                $pe->x - $ny / 2, $pe->y + $nx / 2,
                $pe->x - $nx / 2, $pe->y - $ny / 2,
                $pe->x + $ny, $pe->y - $nx / 2, ];
            $this->drawPolygon($poly, $stroke, $stroke, false);
        }

        return $offset;
    }

    /**
     * Function: getImage.
     *
     * Returns an image that represents this canvas.
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Function: setImage.
     *
     * Sets the image that represents the canvas.
     *
     * @param mixed $img
     */
    public function setImage($img): void
    {
        $this->image = $img;
    }

    /**
     * Function: getImageForStyle.
     *
     * Returns an image that represents this canvas.
     *
     * @param mixed $style
     */
    public function getImageForStyle($style)
    {
        $filename = mxUtils::getValue($style, mxConstants::$STYLE_IMAGE, '');

        if (null != $filename && strpos($filename, '/') > 0) {
            $filename = $this->imageBasePath.$filename;
        }

        return $filename;
    }

    /**
     * Function: drawLine.
     *
     * Draws the given line.
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
        $stroke = $this->getColor($stroke, 'black');

        if ($dashed) {
            // ImageDashedLine only works for vertical lines and
            // ImageSetStyle doesnt work with antialiasing.
            if ($this->antialias
                && \function_exists('imageantialias')) {
                imageantialias($this->image, false);
            }

            $st = [$stroke, $stroke, $stroke, $stroke,
                \IMG_COLOR_TRANSPARENT, \IMG_COLOR_TRANSPARENT,
                \IMG_COLOR_TRANSPARENT, \IMG_COLOR_TRANSPARENT, ];
            imagesetstyle($this->image, $st);
            imageline($this->image, $x0, $y0, $x1, $y1, \IMG_COLOR_STYLED);

            if ($this->antialias
                && \function_exists('imageantialias')) {
                imageantialias($this->image, true);
            }
        } else {
            imageline($this->image, $x0, $y0, $x1, $y1, $stroke);
        }
    }

    /**
     * Function: drawShape.
     *
     * Draws the given shape.
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $style
     */
    public function drawShape($x, $y, $w, $h, $style): void
    {
        // Draws the shape
        $shape = mxUtils::getValue($style, mxConstants::$STYLE_SHAPE);
        $image = $shape == mxConstants::$SHAPE_IMAGE;

        // Redirects background styles for image shapes
        $fillStyle = ($image) ? mxConstants::$STYLE_IMAGE_BACKGROUND : mxConstants::$STYLE_FILLCOLOR;
        $strokeStyle = ($image) ? mxConstants::$STYLE_IMAGE_BORDER : mxConstants::$STYLE_STROKECOLOR;

        // Prepares the background and foreground
        $stroke = mxUtils::getValue($style, $strokeStyle);
        $fill = mxUtils::getValue($style, $fillStyle);

        if ('none' == $stroke) {
            $stroke = null;
        }

        if ('none' == $fill) {
            $fill = null;
        }

        if (null != $fill || null != $stroke || $image) {
            $shadow = mxUtils::getValue($style, mxConstants::$STYLE_SHADOW);
            $strokeWidth = mxUtils::getValue($style, mxConstants::$STYLE_STROKEWIDTH, 1) * $this->scale;

            if (isset($this->image)) {
                imagesetthickness($this->image, $strokeWidth);
            }

            if ($shape == mxConstants::$SHAPE_ELLIPSE) {
                $this->drawOval($x, $y, $w, $h, $fill, $stroke, $shadow);
            } elseif ($shape == mxConstants::$SHAPE_LINE) {
                $direction = mxUtils::getValue($style, mxConstants::$STYLE_DIRECTION, mxConstants::$DIRECTION_EAST);

                if ($direction == mxConstants::$DIRECTION_EAST || $direction == mxConstants::$DIRECTION_WEST) {
                    $mid = $y + $h / 2;
                    $this->drawLine($x, $mid, $x + $w, $mid, $stroke);
                } else {
                    $mid = $x + $w / 2;
                    $this->drawLine($mid, $y, $mid, $y + $h, $stroke);
                }
            } elseif ($shape == mxConstants::$SHAPE_DOUBLE_ELLIPSE) {
                $this->drawOval($x, $y, $w, $h, $fill, $stroke, $shadow);

                $inset = (3 + 1) * $this->scale;
                $x += $inset;
                $y += $inset;
                $w -= 2 * $inset;
                $h -= 2 * $inset;
                $this->drawOval($x, $y, $w, $h, null, $stroke, false);
            } elseif ($shape == mxConstants::$SHAPE_RHOMBUS) {
                $this->drawRhombus($x, $y, $w, $h, $fill, $stroke, $shadow);
            } elseif ($shape == mxConstants::$SHAPE_TRIANGLE) {
                $dir = mxUtils::getValue($style, mxConstants::$STYLE_DIRECTION);
                $this->drawTriangle($x, $y, $w, $h, $fill, $stroke, $shadow, $dir);
            } elseif ($shape == mxConstants::$SHAPE_HEXAGON) {
                $dir = mxUtils::getValue($style, mxConstants::$STYLE_DIRECTION);
                $this->drawHexagon($x, $y, $w, $h, $fill, $stroke, $shadow, $dir);
            } elseif ($shape == mxConstants::$SHAPE_CYLINDER) {
                $this->drawCylinder($x, $y, $w, $h, $fill, $stroke, $shadow);
            } elseif ($shape == mxConstants::$SHAPE_CLOUD) {
                $this->drawCloud($x, $y, $w, $h, $fill, $stroke, $shadow);
            } elseif ($shape == mxConstants::$SHAPE_ACTOR) {
                $this->drawActor($x, $y, $w, $h, $fill, $stroke, $shadow);
            } else {
                $rounded = mxUtils::getValue($style, mxConstants::$STYLE_ROUNDED);
                $dashed = mxUtils::getNumber($style, mxConstants::$STYLE_DASHED);
                $this->drawRect($x, $y, $w, $h, $fill, $stroke, $shadow, $rounded, $dashed);

                // Draws the image as a shape
                if ($image) {
                    $img = $this->getImageForStyle($style);

                    if (null != $img) {
                        $aspect = self::$PRESERVE_IMAGE_ASPECT;
                        $flipH = mxUtils::getValue($style, mxConstants::$STYLE_IMAGE_FLIPH, 0);
                        $flipV = mxUtils::getValue($style, mxConstants::$STYLE_IMAGE_FLIPV, 0);

                        $this->drawImage($x, $y, $w, $h, $img, $aspect, $flipH, $flipV);
                    }
                }

                // Draws the image of the label inside the label shape
                if ($shape == mxConstants::$SHAPE_LABEL) {
                    $image = $this->getImageForStyle($style);

                    if (null != $image) {
                        $imgAlign = mxUtils::getValue($style, mxConstants::$STYLE_IMAGE_ALIGN);
                        $imgValign = mxUtils::getValue($style, mxConstants::$STYLE_IMAGE_VERTICAL_ALIGN);
                        $imgWidth = mxUtils::getNumber(
                            $style,
                            mxConstants::$STYLE_IMAGE_WIDTH,
                            mxConstants::$DEFAULT_IMAGESIZE
                        ) * $this->scale;
                        $imgHeight = mxUtils::getNumber(
                            $style,
                            mxConstants::$STYLE_IMAGE_HEIGHT,
                            mxConstants::$DEFAULT_IMAGESIZE
                        ) * $this->scale;
                        $spacing = mxUtils::getNumber($style, mxConstants::$STYLE_SPACING, 2) * $this->scale;

                        $imgX = $x;

                        if ($imgAlign == mxConstants::$ALIGN_LEFT) {
                            $imgX += $spacing;
                        } elseif ($imgAlign == mxConstants::$ALIGN_RIGHT) {
                            $imgX += $w - $imgWidth - $spacing;
                        } else { // CENTER
                            $imgX += ($w - $imgWidth) / 2;
                        }

                        $imgY = $y;

                        if ($imgValign == mxConstants::$ALIGN_TOP) {
                            $imgY += $spacing;
                        } elseif ($imgValign == mxConstants::$ALIGN_BOTTOM) {
                            $imgY += $h - $imgHeight - $spacing;
                        } else { // MIDDLE
                            $imgY += ($h - $imgHeight) / 2;
                        }

                        $this->drawImage($imgX, $imgY, $imgWidth, $imgHeight, $image);
                    }
                }
            }

            if (isset($this->image)) {
                imagesetthickness($this->image, 1);
            }
        }
    }

    /**
     * Function: drawPolygon.
     *
     * Draws the given polygon.
     *
     * @param mixed      $points
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawPolygon($points, $fill = null, $stroke = null, $shadow = false): void
    {
        if (isset($this->image)) {
            $n = \count($points) / 2;

            if (isset($fill)) {
                if ($shadow) {
                    imagefilledpolygon(
                        $this->image,
                        $this->offset($points),
                        $n,
                        $this->shadowColor
                    );
                }

                $fill = $this->getColor($fill);
                imagefilledpolygon($this->image, $points, $n, $fill);
            }

            if (isset($stroke)) {
                $stroke = $this->getColor($stroke);
                imagepolygon($this->image, $points, $n, $stroke);
            }
        }
    }

    /**
     * Function: drawRect.
     *
     * Draws then given rectangle. Rounded is currently ignored.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     * @param mixed      $rounded
     * @param mixed      $dashed
     */
    public function drawRect(
        $x,
        $y,
        $w,
        $h,
        $fill = null,
        $stroke = null,
        $shadow = false,
        $rounded = false,
        $dashed = false
    ): void {
        // TODO: Rounded rectangles
        if (isset($fill)) {
            if ($shadow) {
                imagefilledrectangle(
                    $this->image,
                    $x + mxConstants::$SHADOW_OFFSETX,
                    $y + mxConstants::$SHADOW_OFFSETY,
                    $x + mxConstants::$SHADOW_OFFSETX + $w,
                    $y + mxConstants::$SHADOW_OFFSETX + $h,
                    $this->shadowColor
                );
            }

            $fill = $this->getColor($fill);
            imagefilledrectangle($this->image, $x, $y, $x + $w, $y + $h, $fill);
        }

        if (isset($stroke)) {
            if ($dashed) {
                $this->drawLine($x, $y, $x + $w, $y, $stroke, $dashed);
                $this->drawLine($x + $w, $y, $x + $w, $y + $h, $stroke, $dashed);
                $this->drawLine($x, $y + $h, $x + $w, $y + $h, $stroke, $dashed);
                $this->drawLine($x, $y + $h, $x, $y, $stroke, $dashed);
            } else {
                $stroke = $this->getColor($stroke);
                imagerectangle($this->image, $x, $y, $x + $w, $y + $h, $stroke);
            }
        }
    }

    /**
     * Function: drawOval.
     *
     * Draws then given ellipse.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawOval($x, $y, $w, $h, $fill = null, $stroke = null, $shadow = false): void
    {
        if (isset($fill)) {
            if ($shadow) {
                imagefilledellipse(
                    $this->image,
                    $x + $w / 2 + mxConstants::$SHADOW_OFFSETX,
                    $y + $h / 2 + mxConstants::$SHADOW_OFFSETY,
                    $w,
                    $h,
                    $this->shadowColor
                );
            }

            $fill = $this->getColor($fill);
            imagefilledellipse(
                $this->image,
                $x + $w / 2,
                $y + $h / 2,
                $w,
                $h,
                $fill
            );
        }

        if (isset($stroke)) {
            $stroke = $this->getColor($stroke);
            imageellipse(
                $this->image,
                $x + $w / 2,
                $y + $h / 2,
                $w,
                $h,
                $stroke
            );
        }
    }

    /**
     * Function: drawRhombus.
     *
     * Draws then given rhombus.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawRhombus($x, $y, $w, $h, $fill = null, $stroke = null, $shadow = false): void
    {
        $halfWidth = $x + $w / 2;
        $halfHeight = $y + $h / 2;

        $points = [$halfWidth, $y, $x + $w, $halfHeight,
            $halfWidth, $y + $h, $x, $halfHeight, $halfWidth, $y, ];

        $this->drawPolygon($points, $fill, $stroke, $shadow);
    }

    /**
     * Function: drawTriangle.
     *
     * Draws then given triangle.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     * @param null|mixed $direction
     */
    public function drawTriangle(
        $x,
        $y,
        $w,
        $h,
        $fill = null,
        $stroke = null,
        $shadow = false,
        $direction = null
    ): void {
        if ($direction == mxConstants::$DIRECTION_NORTH) {
            $points = [$x, $y + $h, $x + $w / 2, $y,
                $x + $w, $y + $h, $x, $y + $h, ];
        } elseif ($direction == mxConstants::$DIRECTION_SOUTH) {
            $points = [$x, $y, $x + $w / 2, $y + $h,
                $x + $w, $y, $x, $y, ];
        } elseif ($direction == mxConstants::$DIRECTION_WEST) {
            $points = [$x + $w, $y, $x, $y + $h / 2,
                $x + $w, $y + $h, $x + $w, $y, ];
        } else { // east
            $points = [$x, $y, $x + $w, $y + $h / 2,
                $x, $y + $h, $x, $y, ];
        }

        $this->drawPolygon($points, $fill, $stroke, $shadow);
    }

    /**
     * Function: drawHexagon.
     *
     * Draws then given haxagon.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     * @param null|mixed $direction
     */
    public function drawHexagon(
        $x,
        $y,
        $w,
        $h,
        $fill = null,
        $stroke = null,
        $shadow = false,
        $direction = null
    ): void {
        if ($direction == mxConstants::$DIRECTION_NORTH
            || $direction == mxConstants::$DIRECTION_SOUTH) {
            $points = [$x + 0.5 * $w, $y, $x + $w, $y + 0.25 * $h,
                $x + $w, $y + 0.75 * $h, $x + 0.5 * $w, $y + $h,
                $x, $y + 0.75 * $h, $x, $y + 0.25 * $h, ];
        } else {
            $points = [$x + 0.25 * $w, $y, $x + 0.75 * $w, $y,
                $x + $w, $y + 0.5 * $h, $x + 0.75 * $w, $y + $h,
                $x + 0.25 * $w, $y + $h, $x, $y + 0.5 * $h, ];
        }

        $this->drawPolygon($points, $fill, $stroke, $shadow);
    }

    /**
     * Function: drawCylinder.
     *
     * Draws then given cylinder.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawCylinder($x, $y, $w, $h, $fill = null, $stroke = null, $shadow = false): void
    {
        $h4 = $h / 4;
        $h8 = $h4 / 2;

        if (isset($fill)) {
            $this->drawOval($x, $y, $w, $h4, $fill, null, $shadow);
            $this->drawRect($x, $y + $h8, $w, $h - $h4, $fill, null, $shadow);
            $this->drawOval($x, $y + $h - $h4, $w, $h4, $fill, null, $shadow);
        }

        if (isset($stroke)) {
            $this->drawOval($x, $y, $w, $h4, null, $stroke, false);
            $this->drawLine($x, $y + $h8, $x, $y + $h - $h8, $stroke);
            $this->drawLine($x + $w, $y + $h8, $x + $w, $y + $h - $h8, $stroke);
            $this->drawOval($x, $y + $h - $h4, $w, $h4, null, $stroke, false);
        }

        // Hides lower arc for filled cylinder
        if (isset($fill, $stroke)) {
            $this->drawRect($x + 1, $y + $h - $h4, $w - 2, $h8, $fill, null, false);
        }
    }

    /**
     * Function: drawCloud.
     *
     * Draws then given cloud.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawCloud($x, $y, $w, $h, $fill = null, $stroke = null, $shadow = false): void
    {
        if (isset($fill)) {
            if ($shadow) {
                $dx = mxConstants::$SHADOW_OFFSETX;
                $dy = mxConstants::$SHADOW_OFFSETY;

                imagefilledellipse($this->image, $x + 0.2 * $w + $dx, $y + 0.42 * $h + $dy, 0.3 * $w, 0.29 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.4 * $w + $dx, $y + 0.25 * $h + $dy, 0.4 * $w, 0.4 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.75 * $w + $dx, $y + 0.35 * $h + $dy, 0.5 * $w, 0.4 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.2 * $w + $dx, $y + 0.65 * $h + $dy, 0.3 * $w, 0.3 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.55 * $w + $dx, $y + 0.62 * $h + $dy, 0.6 * $w, 0.6 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.88 * $w + $dx, $y + 0.63 * $h + $dy, 0.3 * $w, 0.3 * $h, $this->shadowColor);
            }

            $fill = $this->getColor($fill);
            imagefilledellipse($this->image, $x + 0.2 * $w, $y + 0.42 * $h, 0.3 * $w, 0.29 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.4 * $w, $y + 0.25 * $h, 0.4 * $w, 0.4 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.75 * $w, $y + 0.35 * $h, 0.5 * $w, 0.4 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.2 * $w, $y + 0.65 * $h, 0.3 * $w, 0.3 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.55 * $w, $y + 0.62 * $h, 0.6 * $w, 0.6 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.88 * $w, $y + 0.63 * $h, 0.3 * $w, 0.3 * $h, $fill);
        }

        if (isset($stroke)) {
            $stroke = $this->getColor($stroke);
            imagearc($this->image, $x + 0.2 * $w, $y + 0.42 * $h, 0.3 * $w, 0.29 * $h, 125, 270, $stroke);
            imagearc($this->image, $x + 0.4 * $w, $y + 0.25 * $h, 0.4 * $w, 0.4 * $h, 170, 345, $stroke);
            imagearc($this->image, $x + 0.75 * $w, $y + 0.35 * $h, 0.5 * $w, 0.4 * $h, 230, 55, $stroke);
            imagearc($this->image, $x + 0.2 * $w, $y + 0.65 * $h, 0.3 * $w, 0.3 * $h, 50, 235, $stroke);
            imagearc($this->image, $x + 0.55 * $w, $y + 0.62 * $h, 0.6 * $w, 0.6 * $h, 33, 145, $stroke);
            imagearc($this->image, $x + 0.88 * $w, $y + 0.63 * $h, 0.3 * $w, 0.3 * $h, 290, 120, $stroke);
        }
    }

    /**
     * Function: drawActor.
     *
     * Draws then given cloud.
     *
     * @param mixed      $x
     * @param mixed      $y
     * @param mixed      $w
     * @param mixed      $h
     * @param null|mixed $fill
     * @param null|mixed $stroke
     * @param mixed      $shadow
     */
    public function drawActor($x, $y, $w, $h, $fill = null, $stroke = null, $shadow = false): void
    {
        if (isset($fill)) {
            if ($shadow) {
                $dx = mxConstants::$SHADOW_OFFSETX;
                $dy = mxConstants::$SHADOW_OFFSETY;

                imagefilledellipse($this->image, $x + 0.5 * $w + $dx, $y + 0.2 * $h + $dy, 0.4 * $w, 0.4 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.2 * $w + $dx, $y + 0.6 * $h + $dy, 0.4 * $w, 0.4 * $h, $this->shadowColor);
                imagefilledellipse($this->image, $x + 0.8 * $w + $dx, $y + 0.6 * $h + $dy, 0.4 * $w, 0.4 * $h, $this->shadowColor);
                imagefilledrectangle($this->image, $x + 0.2 * $w + $dx, $y + 0.4 * $h + $dy, $x + 0.8 * $w + $dx, $y + 0.6 * $h + $dy, $this->shadowColor);
                imagefilledrectangle($this->image, $x + $dx, $y + 0.6 * $h + $dy, $x + $w + $dx, $y + $h + $dy, $this->shadowColor);
            }

            $fill = $this->getColor($fill);
            imagefilledellipse($this->image, $x + 0.5 * $w, $y + 0.2 * $h, 0.4 * $w, 0.4 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.2 * $w, $y + 0.6 * $h, 0.4 * $w, 0.4 * $h, $fill);
            imagefilledellipse($this->image, $x + 0.8 * $w, $y + 0.6 * $h, 0.4 * $w, 0.4 * $h, $fill);
            imagefilledrectangle($this->image, $x + 0.2 * $w, $y + 0.4 * $h, $x + 0.8 * $w, $y + 0.6 * $h, $fill);
            imagefilledrectangle($this->image, $x, $y + 0.6 * $h, $x + $w, $y + $h, $fill);
        }

        if (null != $stroke) {
            $stroke = $this->getColor($stroke);
            imageellipse($this->image, $x + 0.5 * $w, $y + 0.2 * $h, 0.4 * $w, 0.4 * $h, $stroke);
            imageline($this->image, $x + 0.2 * $w, $y + 0.4 * $h, $x + 0.8 * $w, $y + 0.4 * $h, $stroke);
            imagearc($this->image, $x + 0.2 * $w, $y + 0.6 * $h, 0.4 * $w, 0.4 * $h, 180, 270, $stroke);
            imagearc($this->image, $x + 0.8 * $w, $y + 0.6 * $h, 0.4 * $w, 0.4 * $h, 270, 360, $stroke);
            imageline($this->image, $x, $y + 0.6 * $h, $x, $y + $h, $stroke);
            imageline($this->image, $x, $y + $h, $x + $w, $y + $h, $stroke);
            imageline($this->image, $x + $w, $y + $h, $x + $w, $y + 0.6 * $h, $stroke);
        }
    }

    /**
     * Function: drawImage.
     *
     * Draws a given image.
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
        $img = $this->loadImage($image);

        if (null != $img) {
            $iw = imagesx($img);
            $ih = imagesy($img);

            // Horizontal and vertical image flipping
            if ($flipH || $flipV) {
                $img = mxUtils::flipImage($img, $flipH, $flipV);
            }

            // Preserved aspect ratio
            if ($aspect) {
                $s = min($w / $iw, $h / $ih);
                $x0 = ($w - $iw * $s) / 2;
                $y0 = ($h - $ih * $s) / 2;

                imagecopyresized(
                    $this->image,
                    $img,
                    $x0 + $x,
                    $y0 + $y,
                    0,
                    0,
                    $iw * $s,
                    $ih * $s,
                    $iw,
                    $ih
                );
            } else {
                imagecopyresized(
                    $this->image,
                    $img,
                    $x,
                    $y,
                    0,
                    0,
                    $w,
                    $h,
                    $iw,
                    $ih
                );
            }
        }
    }

    /**
     * Function: drawText.
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
        if ('string' == \gettype($string) && '' !== $string) {
            // Draws the label background and border
            $bg = mxUtils::getValue($style, mxConstants::$STYLE_LABEL_BACKGROUNDCOLOR);
            $border = mxUtils::getValue($style, mxConstants::$STYLE_LABEL_BORDERCOLOR);

            if (null != $bg || null != $border) {
                $bounds->width += 2;
                $bounds->x -= 2;
                --$bounds->y;

                $this->drawRect($x, $y, $w, $h, $bg, $border, false);
            }

            // Draws the label string
            if ($this->enableTtf && \function_exists('imagettftext')) {
                $this->drawTtfText($string, $x, $y, $w, $h, $style);
            } else {
                $this->drawFixedText($string, $x, $y, $w, $h, $style);
            }
        }
    }

    /**
     * Function: getTrueTypeFont.
     *
     * Returns the truetype font to be used to draw the text with the given style.
     *
     * @param mixed $style
     */
    public function getTrueTypeFont($style)
    {
        return mxUtils::getTrueTypeFont($style);
    }

    /**
     * Function: getTrueTypeFontSize.
     *
     * Returns the truetype font size to be used to draw the text with the
     * given style. This returns the fontSize in the style of the default
     * fontsize multiplied with <ttfSizeFactor>.
     *
     * @param mixed $style
     */
    public function getTrueTypeFontSize($style)
    {
        return mxUtils::getTrueTypeFontSize(
            mxUtils::getValue(
                $style,
                mxConstants::$STYLE_FONTSIZE,
                mxConstants::$DEFAULT_FONTSIZE
            ) * $this->scale
        );
    }

    /**
     * Function: drawTtfText.
     *
     * @param mixed $string
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $style
     */
    public function drawTtfText($string, $x, $y, $w, $h, $style): void
    {
        $lines = explode("\n", $string);
        $lineCount = \count($lines);

        if ($lineCount > 0) {
            // Gets the orientation and alignment
            $horizontal = mxUtils::getValue($style, mxConstants::$STYLE_HORIZONTAL, true);
            $align = mxUtils::getValue($style, mxConstants::$STYLE_ALIGN, mxConstants::$ALIGN_CENTER);

            if ($align == mxConstants::$ALIGN_LEFT) {
                if ($horizontal) {
                    $x += mxConstants::$LABEL_INSET;
                } else {
                    $y -= mxConstants::$LABEL_INSET;
                }
            } elseif ($align == mxConstants::$ALIGN_RIGHT) {
                if ($horizontal) {
                    $x -= mxConstants::$LABEL_INSET;
                } else {
                    $y += mxConstants::$LABEL_INSET;
                }
            }

            // Gets the font
            $fontSize = $this->getTrueTypeFontSize($style);
            $font = $this->getTrueTypeFont($style);

            // Gets the color
            $fontColor = mxUtils::getValue($style, mxConstants::$STYLE_FONTCOLOR);
            $color = $this->getColor($fontColor, 'black');

            $dy = ((($horizontal) ? $h : $w) - 2 * mxConstants::$LABEL_INSET) / $lineCount;

            if ($horizontal) {
                $y += 0.8 * $dy + mxConstants::$LABEL_INSET;
            } else {
                $y += $h;
                $x += $dy;
            }

            // Draws the text line by line
            for ($i = 0; $i < $lineCount; ++$i) {
                $left = $x;
                $top = $y;
                $tmp = imagettfbbox($fontSize, 0, $font, $lines[$i]);
                $lineWidth = $tmp[2] - $tmp[0];

                if ($align == mxConstants::$ALIGN_CENTER) {
                    if ($horizontal) {
                        $left += ($w - $lineWidth) / 2;
                    } else {
                        $top -= ($h - $lineWidth) / 2;
                    }
                } elseif ($align == mxConstants::$ALIGN_RIGHT) {
                    if ($horizontal) {
                        $left += $w - $lineWidth;
                    } else {
                        $top -= $h - $lineWidth;
                    }
                }

                $this->drawTtfTextLine(
                    $lines[$i],
                    $left,
                    $top,
                    $w,
                    $h,
                    $color,
                    $fontSize,
                    $font,
                    ($horizontal) ? 0 : 90
                );

                if ($horizontal) {
                    $y += $dy;
                } else {
                    $x += $dy;
                }
            }
        }
    }

    /**
     * Function: drawTtxTextLine.
     *
     * Draws a single line of the given true type font text. The w and h are
     * the width and height of the complete text box that contains this line.
     *
     * @param mixed $line
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $color
     * @param mixed $fontSize
     * @param mixed $font
     * @param mixed $rot
     */
    public function drawTtfTextLine($line, $x, $y, $w, $h, $color, $fontSize, $font, $rot): void
    {
        imagettftext($this->image, $fontSize, $rot, $x, $y, $color, $font, $line);
    }

    /**
     * Function: getFixedFontSize.
     *
     * Returns the fixed font size for GD (1 t0 5) for the given font properties
     *
     * @param mixed      $fontSize
     * @param mixed      $fontFamily
     * @param null|mixed $fontStyle
     */
    public function getFixedFontSize($fontSize, $fontFamily, $fontStyle = null)
    {
        return mxUtils::getFixedFontSize($fontSize, $fontFamily);
    }

    /**
     * Function: drawString.
     *
     * @param mixed $string
     * @param mixed $x
     * @param mixed $y
     * @param mixed $w
     * @param mixed $h
     * @param mixed $style
     */
    public function drawFixedText($string, $x, $y, $w, $h, $style): void
    {
        $lines = explode("\n", $string);
        $lineCount = \count($lines);

        if ($lineCount > 0) {
            // Gets the orientation and alignment
            $horizontal = mxUtils::getValue($style, mxConstants::$STYLE_HORIZONTAL, true);
            $align = mxUtils::getValue($style, mxConstants::$STYLE_ALIGN, mxConstants::$ALIGN_CENTER);

            if ($align == mxConstants::$ALIGN_LEFT) {
                if ($horizontal) {
                    $x += mxConstants::$LABEL_INSET;
                } else {
                    $y -= mxConstants::$LABEL_INSET;
                }
            } elseif ($align == mxConstants::$ALIGN_RIGHT) {
                if ($horizontal) {
                    $x -= mxConstants::$LABEL_INSET;
                } else {
                    $y += mxConstants::$LABEL_INSET;
                }
            }

            if ($horizontal) {
                $y += 2 * mxConstants::$LABEL_INSET;
            } else {
                $y += $h;
                $x += 2 * mxConstants::$LABEL_INSET;
            }

            // Gets the font
            $fontSize = mxUtils::getValue(
                $style,
                mxConstants::$STYLE_FONTSIZE,
                mxConstants::$DEFAULT_FONTSIZE
            ) * $this->scale;
            $fontFamily = mxUtils::getValue(
                $style,
                mxConstants::$STYLE_FONTFAMILY,
                mxConstants::$DEFAULT_FONTFAMILY
            );
            $font = $this->getFixedFontSize($fontSize, $fontFamily);

            // Gets the color
            $fontColor = mxUtils::getValue($style, mxConstants::$STYLE_FONTCOLOR);
            $color = $this->getColor($fontColor, 'black');

            $dx = imagefontwidth($font);
            $dy = ((($horizontal) ? $h : $w) - 2 * mxConstants::$LABEL_INSET) / $lineCount;

            // Draws the text line by line
            for ($i = 0; $i < $lineCount; ++$i) {
                $left = $x;
                $top = $y;
                $lineWidth = \strlen($lines[$i]) * $dx;

                if ($align == mxConstants::$ALIGN_CENTER) {
                    if ($horizontal) {
                        $left += ($w - $lineWidth) / 2;
                    } else {
                        $top -= ($h - $lineWidth) / 2;
                    }
                } elseif ($align == mxConstants::$ALIGN_RIGHT) {
                    if ($horizontal) {
                        $left += $w - $lineWidth;
                    } else {
                        $top -= $h - $lineWidth;
                    }
                }

                $this->drawFixedTextLine(
                    $lines[$i],
                    $font,
                    $left,
                    $top,
                    $color,
                    $horizontal
                );

                if ($horizontal) {
                    $y += $dy;
                } else {
                    $x += $dy;
                }
            }
        }
    }

    /**
     * Function: drawFixedTextLine.
     *
     * Draws the given fixed text line.
     *
     * @param mixed $text
     * @param mixed $font
     * @param mixed $left
     * @param mixed $top
     * @param mixed $color
     * @param mixed $horizontal
     */
    public function drawFixedTextLine($text, $font, $left, $top, $color, $horizontal = true): void
    {
        if ($horizontal) {
            imagestring(
                $this->image,
                $font,
                $left,
                $top,
                $text,
                $color
            );
        } else {
            imagestringup(
                $this->image,
                $font,
                $left,
                $top,
                $text,
                $color
            );
        }
    }

    /**
     * Function: getColor.
     *
     * Allocates the given color and returns a reference to it. Supported
     * color names are black, red, green, blue, orange, yellow, pink,
     * turqoise, white, gray and any hex codes between 000000 and FFFFFF.
     *
     * @param mixed      $hex
     * @param null|mixed $default
     */
    public function getColor($hex, $default = null)
    {
        if (!isset($hex)) {
            $hex = $default;
        }

        $result = null;
        $hex = strtolower($hex);

        if ('black' == $hex) {
            $result = imagecolorallocate($this->image, 0, 0, 0);
        } elseif ('red' == $hex) {
            $result = imagecolorallocate($this->image, 255, 0, 0);
        } elseif ('green' == $hex) {
            $result = imagecolorallocate($this->image, 0, 255, 0);
        } elseif ('blue' == $hex) {
            $result = imagecolorallocate($this->image, 0, 0, 255);
        } elseif ('orange' == $hex) {
            $result = imagecolorallocate($this->image, 255, 128, 64);
        } elseif ('yellow' == $hex) {
            $result = imagecolorallocate($this->image, 255, 255, 0);
        } elseif ('pink' == $hex) {
            $result = imagecolorallocate($this->image, 255, 0, 255);
        } elseif ('turqoise' == $hex) {
            $result = imagecolorallocate($this->image, 0, 255, 255);
        } elseif ('white' == $hex) {
            $result = imagecolorallocate($this->image, 255, 255, 255);
        } elseif ('gray' == $hex) {
            $result = imagecolorallocate($this->image, 128, 128, 128);
        } elseif ('none' == $hex) {
            $result = null;
        } else {
            $rgb = array_map('hexdec', explode('|', wordwrap(substr($hex, 1), 2, '|', 1)));

            if (\count($rgb) > 2) {
                $result = imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]);
            } else {
                $result = imagecolorallocate($this->image, 0, 0, 0);
            }
        }

        return $result;
    }

    /**
     * Function: offset.
     *
     * Creates a new array of x, y sequences where the each coordinate is
     * translated by dx and dy, respectively.
     *
     * @param mixed      $points
     * @param null|mixed $dx
     * @param null|mixed $dy
     */
    public function offset($points, $dx = null, $dy = null)
    {
        $result = [];

        if (null != $points) {
            if (!isset($dx)) {
                $dx = mxConstants::$SHADOW_OFFSETX;
            }

            if (!isset($dy)) {
                $dy = mxConstants::$SHADOW_OFFSETY;
            }

            for ($i = 0; $i < \count($points) - 1; $i = $i + 2) {
                $result[] = $points[$i] + $dx;
                $result[] = $points[$i + 1] + $dy;
            }
        }

        return $result;
    }

    /**
     * Destructor: destroy.
     *
     * Destroys all allocated resources.
     */
    public function destroy(): void
    {
        imagedestroy($this->image);
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
        if (!isset($clip)) {
            $clip = $graph->getGraphBounds();
        }

        // TODO: Support custom origin in mxGdCanvas
        // $x = round($clip->x);
        // $y = round($clip->y);
        // $width = round($clip->width - $x + $clip->x) + 1;
        // $height = round($clip->height - $y + $clip->y) + 1;
        $width = round($clip->width + $clip->x) + 1;
        $height = round($clip->width + $clip->x) + 1;

        $canvas = new self($width, $height, $graph->view->scale, $bg);

        $graph->drawGraph($canvas);

        return $canvas->getImage();
        //TODO: $canvas->destroy();
    }
}
