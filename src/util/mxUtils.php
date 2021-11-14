<?php

declare(strict_types=1);

namespace Mxgraph\Util;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxUtils
{
    /**
     * Class: mxUtils.
     *
     * Helper methods.
     *
     * Function: getLabelSize
     *
     * Returns the size of the given label.
     *
     * @param mixed $label
     * @param mixed $style
     */
    public static function getLabelSize($label, $style)
    {
        $fontSize = self::getValue(
            $style,
            mxConstants::$STYLE_FONTSIZE,
            mxConstants::$DEFAULT_FONTSIZE
        );
        $fontFamily = self::getValue(
            $style,
            mxConstants::$STYLE_FONTFAMILY,
            mxConstants::$DEFAULT_FONTFAMILY
        );

        return self::getSizeForString($label, $fontSize, $fontFamily);
    }

    /**
     * Function: getLabelPaintBounds.
     *
     * Returns the paint bounds for the given label.
     *
     * @param mixed $label
     * @param mixed $style
     * @param mixed $isHtml
     * @param mixed $offset
     * @param mixed $vertexBounds
     * @param mixed $scale
     */
    public static function getLabelPaintBounds(
        $label,
        $style,
        $isHtml,
        $offset,
        $vertexBounds,
        $scale
    ) {
        $size = self::getLabelSize($label, $style);

        $x = $offset->x;
        $y = $offset->y;
        $width = 0;
        $height = 0;

        if (isset($vertexBounds)) {
            $x += $vertexBounds->x;
            $y += $vertexBounds->y;

            if (self::getValue($style, mxConstants::$STYLE_SHAPE, '') ==
                mxConstants::$SHAPE_SWIMLANE) {
                // Limits the label to the swimlane title
                $start = self::getNumber(
                    $style,
                    mxConstants::$STYLE_STARTSIZE,
                    mxConstants::$DEFAULT_STARTSIZE
                ) * $scale;

                if (self::getValue($style, mxConstants::$STYLE_HORIZONTAL, true)) {
                    $width += $vertexBounds->width;
                    $height += $start;
                } else {
                    $width += $start;
                    $height += $vertexBounds->height;
                }
            } else {
                $height += $vertexBounds->height;
                $width += $vertexBounds->width;
            }
        }

        return self::getScaledLabelBounds(
            $x,
            $y,
            $size,
            $width,
            $height,
            $style,
            $scale
        );
    }

    /**
     * Function: getScaledLabelBounds.
     *
     * Returns the bounds for a label for the given location and size, taking
     * into account the alignment and spacing in the specified style, as well
     * as the width and height of the rectangle that contains the label.
     * (For edge labels this width and height is 0.) The scale is used to scale
     * the given size and the spacings in the specified style.
     *
     * @param mixed $x
     * @param mixed $y
     * @param mixed $size
     * @param mixed $outerWidth
     * @param mixed $outerHeight
     * @param mixed $style
     * @param mixed $scale
     */
    public static function getScaledLabelBounds($x, $y, $size, $outerWidth, $outerHeight, $style, $scale)
    {
        // Adds an inset of 3 pixels
        $inset = mxConstants::$LABEL_INSET * $scale;

        // Scales the size of the label
        $width = $size->width * $scale + 2 * $inset;
        $height = $size->height * $scale;

        // Gets the global spacing and orientation
        $horizontal = self::getValue($style, mxConstants::$STYLE_HORIZONTAL, true);
        $spacing = self::getNumber($style, mxConstants::$STYLE_SPACING) * $scale;

        // Gets the alignment settings
        $align = self::getValue(
            $style,
            mxConstants::$STYLE_ALIGN,
            mxConstants::$ALIGN_CENTER
        );
        $valign = self::getValue(
            $style,
            mxConstants::$STYLE_VERTICAL_ALIGN,
            mxConstants::$ALIGN_MIDDLE
        );

        // Gets the vertical spacing
        $top = self::getNumber($style, mxConstants::$STYLE_SPACING_TOP) * $scale;
        $bottom = self::getNumber($style, mxConstants::$STYLE_SPACING_BOTTOM) * $scale;

        // Gets the horizontal spacing
        $left = self::getNumber($style, mxConstants::$STYLE_SPACING_LEFT) * $scale;
        $right = self::getNumber($style, mxConstants::$STYLE_SPACING_RIGHT) * $scale;

        // Applies the orientation to the spacings and dimension
        if (!$horizontal) {
            $tmp = $top;
            $top = $right;
            $right = $bottom;
            $bottom = $left;
            $left = $tmp;

            $tmp = $width;
            $width = $height;
            $height = $tmp;
        }

        // Computes the position of the label for the horizontal alignment
        if (($horizontal && $align == mxConstants::$ALIGN_CENTER)
            || (!$horizontal && $valign == mxConstants::$ALIGN_MIDDLE)) {
            $x += ($outerWidth - $width) / 2 + $left - $right;
        } elseif (($horizontal && $align == mxConstants::$ALIGN_RIGHT)
            || (!$horizontal && $valign == mxConstants::$ALIGN_BOTTOM)) {
            $x += $outerWidth - $width - $spacing - $right;
        } else {
            $x += $spacing + $left;
        }

        // Computes the position of the label for the vertical alignment
        if ((!$horizontal && $align == mxConstants::$ALIGN_CENTER)
            || ($horizontal && $valign == mxConstants::$ALIGN_MIDDLE)) {
            $y += ($outerHeight - $height) / 2 + $top - $bottom;
        } elseif ((!$horizontal && $align == mxConstants::$ALIGN_LEFT)
            || ($horizontal && $valign == mxConstants::$ALIGN_BOTTOM)) {
            $y += $outerHeight - $height - $spacing - $bottom;
        } else {
            $y += $spacing + $top;
        }

        return new mxRectangle($x, $y, $width, $height);
    }

    /**
     * Function: getSizeForString.
     *
     * Returns an <mxRectangle> with the size (width and height in pixels) of
     * the given string. The string may contain HTML markup. Newlines should be
     * converted to <br> before calling this method.
     *
     * Parameters:
     *
     * text - String whose size should be returned.
     * fontSize - Integer that specifies the font size in pixels. Default is
     * <mxConstants.DEFAULT_FONTSIZE>.
     * fontFamily - String that specifies the name of the font famil.y Default
     * is <mxConstants.DEFAULT_FONTFAMILY>.
     *
     * @param mixed      $text
     * @param mixed      $fontSize
     * @param null|mixed $fontFamily
     */
    public static function getSizeForString($text, $fontSize = 0, $fontFamily = null)
    {
        if (\is_string($text) && '' !== $text) {
            if (0 == $fontSize) {
                $fontSize = mxConstants::$DEFAULT_FONTSIZE;
            }

            if (null == $fontFamily) {
                $fontFamily = mxConstants::$DEFAULT_FONTFAMILY;
            }

            $lines = explode("\n", $text);
            $lineCount = \count($lines);

            if (mxConstants::$TTF_ENABLED
                && \function_exists('imagettfbbox')) {
                $bbox = imagettfbbox($fontSize * mxConstants::$TTF_SIZEFACTOR, 0, $fontFamily, $text);
                $textWidth = $bbox[2] - $bbox[0];
                $textHeight = ($fontSize + mxConstants::$DEFAULT_LINESPACING) * $lineCount;

                return new mxRectangle(0, 0, $textWidth, $textHeight);
            }
            if (\function_exists('imageFontHeight')
                && \function_exists('imageFontWidth')) {
                $font = self::getFixedFontSize($fontSize, $fontFamily);
                $textHeight = (imagefontheight($font) + mxConstants::$DEFAULT_LINESPACING) * $lineCount;
                $charWidth = imagefontwidth($font);
                $textWidth = 0;

                for ($i = 0; $i < \count($lines); ++$i) {
                    $textWidth = max($textWidth, $charWidth * \strlen($lines[$i]));
                }

                return new mxRectangle(0, 0, $textWidth, $textHeight);
            }
        }

        return new mxRectangle();
    }

    /**
     * Function: flipImage.
     *
     * Flips the given image horizontally and/or vertically and returns a new
     * image instance.
     *
     * @param mixed $img
     * @param mixed $flipH
     * @param mixed $flipV
     */
    public static function flipImage($img, $flipH, $flipV)
    {
        $w = imagesx($img);
        $h = imagesy($img);

        $sx = 0;
        $sy = 0;
        $sw = $w;
        $sh = $h;

        if ($flipH) {
            $sx = $w - 1;
            $sw = -$w;
        }

        if ($flipV) {
            $sy = $h - 1;
            $sh = -$h;
        }

        $dst = imagecreatetruecolor($w, $h);

        // Fills the background with transparent white
        $bg = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefill($dst, 0, 0, $bg);

        if (imagecopyresampled($dst, $img, 0, 0, $sx, $sy, $w, $h, $sw, $sh)) {
            return $dst;
        }

        return $img;
    }

    /**
     * Function: toRadians.
     *
     * Converts the given degree to radians.
     *
     * @param mixed $deg
     */
    public static function toRadians($deg)
    {
        return M_PI * $deg / 180;
    }

    /**
     * Function: getBoundingBox.
     *
     * Returns the bounding box for the rotated rectangle.
     *
     * @param mixed $rect
     * @param mixed $rotation
     */
    public static function getBoundingBox($rect, $rotation)
    {
        $result = null;

        if (null != $rect && null != $rotation && 0 != $rotation) {
            $rad = self::toRadians($rotation);
            $cos = cos($rad);
            $sin = sin($rad);

            $cx = new mxPoint(
                $rect->x + $rect->width / 2,
                $rect->y + $rect->height / 2
            );

            $p1 = new mxPoint($rect->x, $rect->y);
            $p2 = new mxPoint($rect->x + $rect->width, $rect->y);
            $p3 = new mxPoint($p2->x, $rect->y + $rect->height);
            $p4 = new mxPoint($rect->x, $p3->y);

            $p1 = self::getRotatedPoint($p1, $cos, $sin, $cx);
            $p2 = self::getRotatedPoint($p2, $cos, $sin, $cx);
            $p3 = self::getRotatedPoint($p3, $cos, $sin, $cx);
            $p4 = self::getRotatedPoint($p4, $cos, $sin, $cx);

            $result = new mxRectangle($p1->x, $p1->y, 0, 0);
            $result->add(new mxRectangle($p2->x, $p2->y, 0, 0));
            $result->add(new mxRectangle($p3->x, $p3->y, 0, 0));
            $result->add(new mxRectangle($p4->x, $p4->y, 0, 0));
        }

        return $result;
    }

    /**
     * Function: getRotatedPoint.
     *
     * Rotates the given point by the given cos and sin.
     *
     * @param mixed      $pt
     * @param mixed      $cos
     * @param mixed      $sin
     * @param null|mixed $cx
     */
    public static function getRotatedPoint($pt, $cos, $sin, $cx = null)
    {
        $cx = (null != $cx) ? $cx : new mxPoint();

        $x = $pt->x - $c->x;
        $y = $pt->y - $c->y;

        $x1 = $x * $cos - $y * $sin;
        $y1 = $y * $cos + $x * $sin;

        return new mxPoint($x1 + $c->x, $y1 + $c->y);
    }

    /**
     * Function: translatePoints.
     *
     * Creates a new list of new points obtained by translating the points in
     * the given list by the given vector. Elements that are not mxPoints are
     * added to the result as-is.
     *
     * @param mixed $pts
     * @param mixed $dx
     * @param mixed $dy
     */
    public static function translatePoints($pts, $dx, $dy)
    {
        $result = null;

        if (null != $pts) {
            $result = [];
            $pointCount = \count($pts);

            for ($i = 0; $i < $pointCount; ++$i) {
                $obj = $pts[$i];

                if ($obj instanceof mxPoint) {
                    $point = $obj->copy();

                    $point->x += $dx;
                    $point->y += $dy;

                    $result[] = $point;
                } else {
                    $result[] = $obj;
                }
            }
        }

        return $result;
    }

    /**
     * Function: contains.
     *
     * Returns true if the specified point (x, y) is contained in the given rectangle.
     *
     * Parameters:
     *
     * bounds - <mxRectangle> that represents the area.
     * x - X-coordinate of the point.
     * y - Y-coordinate of the point.
     *
     * @param mixed $state
     * @param mixed $x
     * @param mixed $y
     */
    public static function contains($state, $x, $y)
    {
        return $state->x <= $x && $state->x + $state->width >= $x
                && $state->y <= $y && $state->y + $state->height >= $y;
    }

    /**
     * Function: intersection.
     *
     * Returns the intersection of two lines as an <mxPoint>.
     *
     * Parameters:
     *
     * x0 - X-coordinate of the first line's startpoint.
     * y0 - X-coordinate of the first line's startpoint.
     * x1 - X-coordinate of the first line's endpoint.
     * y1 - Y-coordinate of the first line's endpoint.
     * x2 - X-coordinate of the second line's startpoint.
     * y2 - Y-coordinate of the second line's startpoint.
     * x3 - X-coordinate of the second line's endpoint.
     * y3 - Y-coordinate of the second line's endpoint.
     *
     * @param mixed $x0
     * @param mixed $y0
     * @param mixed $x1
     * @param mixed $y1
     * @param mixed $x2
     * @param mixed $y2
     * @param mixed $x3
     * @param mixed $y3
     */
    public static function intersection($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
    {
        $denom = (($y3 - $y2) * ($x1 - $x0)) - (($x3 - $x2) * ($y1 - $y0));
        $nume_a = (($x3 - $x2) * ($y0 - $y2)) - (($y3 - $y2) * ($x0 - $x2));
        $nume_b = (($x1 - $x0) * ($y0 - $y2)) - (($y1 - $y0) * ($x0 - $x2));

        $ua = $nume_a / $denom;
        $ub = $nume_b / $denom;

        if ($ua >= 0.0 && $ua <= 1.0 && $ub >= 0.0 && $ub <= 1.0) {
            // Get the intersection point
            $intersectionX = $x0 + $ua * ($x1 - $x0);
            $intersectionY = $y0 + $ua * ($y1 - $y0);

            return new mxPoint($intersectionX, $intersectionY);
        }

        // No intersection
        return null;
    }

    /**
     * Function: encodeImage.
     *
     * Encodes the given image using the GD image encoding routines.
     * Supported formats are gif, jpg and png (default).
     *
     * Parameters:
     *
     * image - GD image to be encoded.
     * format - String that defines the encoding format. Default is png.
     *
     * @param mixed      $image
     * @param null|mixed $format
     */
    public static function encodeImage($image, $format = null)
    {
        if ('gif' == $format) {
            return imagegif($image);
        }
        if ('jpg' == $format) {
            return imagejpeg($image);
        }

        return imagepng($image);
    }

    /**
     * Function: getStylename.
     *
     * Returns the stylename in a style of the form [stylename;|key=value;] or
     * an empty string if the given style does not contain a stylename.
     *
     * Parameters:
     *
     * style - String of the form [stylename;|key=value;].
     *
     * @param mixed $style
     */
    public static function getStylename($style)
    {
        if (isset($style)) {
            $pairs = explode(';', $style);
            $stylename = $pairs[0];

            if (false === strpos($stylename, '=')) {
                return $stylename;
            }
        }

        return '';
    }

    /**
     * Function: getStylenames.
     *
     * Returns the stylenames in a style of the form [stylename;|key=value;] or
     * an empty array if the given style does not contain any stylenames.
     *
     * Parameters:
     *
     * style - String of the form [stylename;|key=value;].
     *
     * @param mixed $style
     */
    public static function getStylenames($style)
    {
        $result = [];

        if (isset($style)) {
            $pairs = explode(';', $style);

            for ($i = 0; $i < \count($pairs); ++$i) {
                if (false === strpos($pairs[$i], '=')) {
                    $result[] = $pairs[$i];
                }
            }
        }

        return $result;
    }

    /**
     * Function: indexOfStylename.
     *
     * Returns the index of the given stylename in the given style. This
     * returns -1 if the given stylename does not occur (as a stylename) in the
     * given style, otherwise it returns the index of the first character.
     *
     * @param mixed $style
     * @param mixed $stylename
     */
    public static function indexOfStylename($style, $stylename)
    {
        if (isset($style, $stylename)) {
            $tokens = explode(';', $style);
            $tokenCount = \count($tokens);
            $pos = 0;

            for ($i = 0; $i < $tokenCount; ++$i) {
                if ($tokens[$i] == $stylename) {
                    return $pos;
                }

                $pos += \strlen($tokens[$i]) + 1;
            }
        }

        return -1;
    }

    /**
     * Function: addStylename.
     *
     * Adds the specified stylename to the given style if it does not already
     * contain the stylename.
     *
     * @param mixed $style
     * @param mixed $stylename
     */
    public static function addStylename($style, $stylename)
    {
        if (self::indexOfStylename($style, $stylename) < 0) {
            if (!isset($style)) {
                $style = '';
            } elseif ('' !== $style && ';' != $style[\strlen($style) - 1]) {
                $style .= ';';
            }

            $style .= $stylename;
        }

        return $style;
    }

    /**
     * Function: removeStylename.
     *
     * Removes all occurrences of the specified stylename in the given style
     * and returns the updated style. Trailing semicolons are preserved.
     *
     * @param mixed $style
     * @param mixed $stylename
     */
    public static function removeStylename($style, $stylename)
    {
        $result = '';

        if (isset($style)) {
            $tokens = explode(';', $style);
            $tokenCount = \count($tokens);

            for ($i = 0; $i < $tokenCount; ++$i) {
                if ($tokens[$i] != $stylename) {
                    $result .= $tokens[$i].';';
                }
            }
        }

        $len = \strlen($result);

        return ($len > 1) ? substr($result, 0, $len - 1) : $result;
    }

    /**
     * Function: removeAllStylenames.
     *
     * Removes all stylenames from the given style and returns the updated
     * style.
     *
     * @param mixed $style
     */
    public static function removeAllStylenames($style)
    {
        $result = '';

        if (isset($style)) {
            $tokens = explode(';', $style);
            $tokenCount = \count($tokens);

            for ($i = 0; $i < $tokenCount; ++$i) {
                if (false !== strpos($tokens[$i], '=')) {
                    $result .= $tokens[$i].';';
                }
            }
        }

        $len = \strlen($result);

        return ($len > 1) ? substr($result, 0, $len - 1) : $result;
    }

    /**
     * Function: setCellStyles.
     *
     * Assigns the value for the given key in the styles of the given cells, or
     * removes the key from the styles if the value is null.
     *
     * Parameters:
     *
     * model - <mxGraphModel> to execute the transaction in.
     * cells - Array of <mxCells> to be updated.
     * key - Key of the style to be changed.
     * value - New value for the given key.
     *
     * @param mixed $model
     * @param mixed $cells
     * @param mixed $key
     * @param mixed $value
     */
    public static function setCellStyles($model, $cells, $key, $value): void
    {
        if (null != $cells && \count($cells) > 0) {
            $model->beginUpdate();

            try {
                for ($i = 0; $i < \count($cells); ++$i) {
                    if (isset($cells[$i])) {
                        $style = self::setStyle(
                            $model->getStyle($cells[$i]),
                            $key,
                            $value
                        );
                        $model->setStyle($cells[$i], $style);
                    }
                }
            } catch (\Exception $e) {
                $model->endUpdate();

                throw ($e);
            }
            $model->endUpdate();
        }
    }

    /**
     * Function: setStyle.
     *
     * Adds or removes the given key, value pair to the style and returns the
     * new style. If value is null or zero length then the key is removed from
     * the style.
     *
     * Parameters:
     *
     * style - String of the form stylename[;key=value]
     * key - Key of the style to be changed.
     * value - New value for the given key.
     *
     * @param mixed $style
     * @param mixed $key
     * @param mixed $value
     */
    public static function setStyle($style, $key, $value)
    {
        $isValue = null != $value && (!\is_string($value)
            || '' !== $value);

        if (0 == \strlen($style)) {
            if ($isValue) {
                $style = "{$key}={$value}";
            }
        } else {
            $index = strpos($style, "{$key}=");

            if (false === $index) {
                if ($isValue) {
                    $sep = (';' == $style[\strlen($style) - 1]) ? '' : ';';
                    $style = "{$style}$sep{$key}={$value}";
                }
            } else {
                $tmp = ($isValue) ? "{$key}={$value}" : '';
                $cont = strpos($style, ';', $index);

                if (!$isValue) {
                    ++$cont;
                }

                $style = substr($style, 0, $index).$tmp.
                    (($cont > $index) ? substr($style, $cont) : '');
            }
        }

        return $style;
    }

    /**
     * Function: setCellStyleFlags.
     *
     * Sets or toggles the flag bit for the given key in the cell's styles.
     * If value is null then the flag is toggled.
     *
     * Example:
     *
     * (code)
     * var cells = graph.getSelectionCells();
     * mxUtils.setCellStyleFlags(graph.model,
     * 			cells,
     * 			mxConstants.STYLE_FONTSTYLE,
     * 			mxConstants.FONT_BOLD);
     * (end)
     *
     * Toggles the bold font style.
     *
     * Parameters:
     *
     * model - <mxGraphModel> that contains the cells.
     * cells - Array of <mxCells> to change the style for.
     * key - Key of the style to be changed.
     * flag - Integer for the bit to be changed.
     * value - Optional boolean value for the flag.
     *
     * @param mixed $model
     * @param mixed $cells
     * @param mixed $key
     * @param mixed $flag
     * @param mixed $value
     */
    public static function setCellStyleFlags($model, $cells, $key, $flag, $value): void
    {
        if (null != $cells && \count($cells) > 0) {
            $model->beginUpdate();

            try {
                for ($i = 0; $i < \count($cells); ++$i) {
                    if (isset($cells[$i])) {
                        $style = self::setStyleFlag(
                            $model->getStyle($cells[$i]),
                            $key,
                            $flag,
                            $value
                        );
                        $model->setStyle($cells[$i], $style);
                    }
                }
            } catch (\Exception $e) {
                $model->endUpdate();

                throw ($e);
            }
            $model->endUpdate();
        }
    }

    /**
     * Function: setStyleFlag.
     *
     * Sets or removes the given key from the specified style and returns the
     * new style. If value is null then the flag is toggled.
     *
     * Parameters:
     *
     * style - String of the form stylename[;key=value].
     * key - Key of the style to be changed.
     * flag - Integer for the bit to be changed.
     * value - Optional boolean value for the given flag.
     *
     * @param mixed $style
     * @param mixed $key
     * @param mixed $flag
     * @param mixed $value
     */
    public static function setStyleFlag($style, $key, $flag, $value)
    {
        if (0 == \strlen($style)) {
            if (null == $value || true === $value) {
                $style = "{$key}={$flag}";
            } else {
                $style = "{$key}=0";
            }
        } else {
            $index = strpos($style, "{$key}=");

            if (false === $index) {
                $sep = (';' == $style[\strlen($style) - 1]) ? '' : ';';

                if (null == $value || true === $value) {
                    $style = "{$style}$sep{$key}={$flag}";
                } else {
                    $style = "{$style}$sep{$key}=0";
                }
            } else {
                $cont = strpos($style, ';', $index);
                $tmp = '';

                if (false === $cont) {
                    $tmp = substr($style, $index + \strlen($key) + 1);
                } else {
                    $tmp = substr($style, $index + \strlen($key) + 1, $cont);
                }

                if (null == $value) {
                    $tmp = $tmp ^ $flag;
                } elseif (true === $value) {
                    $tmp = $tmp | $flag;
                } else {
                    $tmp = $tmp & ~$flag;
                }

                $style = substr($style, 0, $index)."{$key}={$tmp}".
                    (($cont >= 0) ? substr($style, $cont) : '');
            }
        }

        return $style;
    }

    /**
     * Function: getValue.
     *
     * Returns the value for key in dictionary or the given default value if no
     * value is defined for the key.
     *
     * Parameters:
     *
     * dict - Dictionary that contains the key, value pairs.
     * key - Key whose value should be returned.
     * default - Default value to return if the key is undefined. Default is null.
     *
     * @param mixed      $dict
     * @param mixed      $key
     * @param null|mixed $default
     */
    public static function getValue($dict, $key, $default = null)
    {
        $value = null;

        if (isset($dict[$key])) {
            $value = $dict[$key];
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Function: getNumber.
     *
     * Returns the value for key in dictionary or 0 if no value is defined for
     * the key.
     *
     * Parameters:
     *
     * dict - Dictionary that contains the key, value pairs.
     * key - Key whose value should be returned.
     * default - Optional default value to return if no value is defined for
     * the given key. Default is 0.
     *
     * @param mixed $dict
     * @param mixed $key
     * @param mixed $default
     */
    public static function getNumber($dict, $key, $default = 0)
    {
        return self::getValue($dict, $key, $default);
    }

    /**
     * Function: indexOf.
     *
     * Returns the index of obj in array or -1 if the array does not contains
     * the given object.
     *
     * Parameters:
     *
     * array - Array to check for the given obj.
     * obj - Object to find in the given array.
     *
     * @param mixed $array
     * @param mixed $object
     */
    public static function indexOf($array, $object)
    {
        if (null != $array) {
            $len = \count($array);

            for ($i = 0; $i < $len; ++$i) {
                if ($array[$i] === $object) {
                    return $i;
                }
            }
        }

        return -1;
    }

    /**
     * Function: readFile.
     *
     * Reads the given filename into a string. Shortcut for file_get_contents.
     *
     * Parameters:
     *
     * filename - The name of the file to read.
     *
     * @param mixed $filename
     */
    public static function readFile($filename)
    {
        return file_get_contents($filename);
    }

    /**
     * Function: isNode.
     *
     * Returns true if the given value is an XML node with the node name
     * and if the optional attribute has the specified value.
     *
     * This implementation assumes that the given value is a DOM node if the
     * nodeName property is not null.
     *
     * Parameters:
     *
     * value - Object that should be examined as a node.
     * nodeName - String that specifies the node name.
     * attributeName - Optional attribute name to check.
     * attributeValue - Optional attribute value to check.
     *
     * @param mixed      $value
     * @param null|mixed $nodeName
     * @param null|mixed $attributeName
     * @param null|mixed $attributeValue
     */
    public static function isNode($value, $nodeName = null, $attributeName = null, $attributeValue = null)
    {
        if (null != $value && (null == $nodeName
            || 0 == strcasecmp($value->nodeName, $nodeName))) {
            return null == $attributeName
                || $value->getAttribute($attributeName) == $attributeValue;
        }

        return false;
    }

    /**
     * Function: loadImage.
     *
     * Loads an image from the local filesystem, a data URI or any other URL.
     *
     * @param mixed $url
     */
    public static function loadImage($url)
    {
        $img = null;

        if (isset($url)) {
            // Parses data URIs of the form data:image/format;base64,xxx
            if (0 === strpos($url, 'data:image/')) {
                $comma = strpos($url, ',');
                $data = base64_decode(substr($url, $comma + 1), true);
                $img = imagecreatefromstring($data);
            } elseif (preg_match('/.jpg/i', "{$url}")) {
                $img = imagecreatefromjpeg($url);
            } elseif (preg_match('/.png/i', "{$url}")) {
                $img = imagecreatefrompng($url);
            } elseif (preg_match('/.gif/i', "{$url}")) {
                $img = imagecreatefromgif($url);
            }
        }

        return $img;
    }

    /**
     * Function: createXmlDocument.
     *
     * Returns a new, empty XML document.
     */
    public static function createXmlDocument()
    {
        return new \DOMDocument('1.0');
    }

    /**
     * Function: loadXmlDocument.
     *
     * Returns a new DOM document for the given URI.
     *
     * @param mixed $uri
     */
    public static function loadXmlDocument($uri)
    {
        $doc = self::createXmlDocument();
        $doc->load($uri);

        return $doc;
    }

    /**
     * Function: parseXml.
     *
     * Returns a new DOM document for the given XML string.
     *
     * @param mixed $xml
     */
    public static function parseXml($xml)
    {
        $doc = self::createXmlDocument();
        $doc->loadXML($xml);

        return $doc;
    }

    /**
     * Function getXml.
     *
     * Returns the XML of the given node as a string.
     *
     * Parameters:
     *
     * node - DOM node to return the XML for.
     * linefeed - Optional string that linefeeds are converted into. Default is
     * &#xa;
     *
     * @param mixed $node
     * @param mixed $linefeed
     */
    public static function getXml($node, $linefeed = '&#xa;')
    {
        // SaveXML converts linefeeds to &#10; internally
        return str_replace('&#10;', '&#xa;', $node->ownerDocument->saveXML($node));
    }

    /**
     * Function: evaluate.
     *
     * Evaluates an expression to a class member. The range of supported
     * expressions is limited to static class members with a dot-notation,
     * such as mxEdgeStyle.ElbowConnector.
     *
     * @param mixed $expression
     */
    public static function evaluate($expression)
    {
        $pos = strpos($expression, '.');

        if (false !== $pos) {
            $class = substr($expression, 0, $pos);
            $field = substr($expression, $pos + 1);
            $vars = get_class_vars($class);

            if (isset($vars[$field])) {
                return $vars[$field];
            }
        }

        return eval('return '.$expression.';');
    }

    /**
     * Function: findNode.
     *
     * Returns the first node where attr equals value.
     * This implementation does not use XPath.
     *
     * @param mixed $node
     * @param mixed $attr
     * @param mixed $value
     */
    public static function findNode($node, $attr, $value)
    {
        $tmp = $node->getAttribute($attr);

        if (isset($tmp) && $tmp == $value) {
            return $node;
        }

        $node = $node->firstChild;

        while (isset($node)) {
            $result = self::findNode($node, $attr, $value);

            if (isset($result)) {
                return $result;
            }

            $node = $node->nextSibling;
        }

        return null;
    }

    /**
     * Function: getTrueTypeFont.
     *
     * Returns the truetype font to be used to draw the text with the given style.
     *
     * @param mixed $style
     */
    public static function getTrueTypeFont($style)
    {
        return self::getValue(
            $style,
            mxConstants::$STYLE_FONTFAMILY,
            mxConstants::$DEFAULT_FONTFAMILY
        );
    }

    /**
     * Function: getTrueTypeFontSize.
     *
     * Returns the truetype font size to be used to draw the text with the
     * given style. This returns the fontSize in the style of the default
     * fontsize multiplied with <ttfSizeFactor>.
     *
     * @param mixed $size
     */
    public static function getTrueTypeFontSize($size)
    {
        return $size * mxConstants::$TTF_SIZEFACTOR;
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
    public static function getFixedFontSize($fontSize, $fontFamily, $fontStyle = null)
    {
        $font = 5;

        if ($fontSize <= 12) {
            $font = 1;
        } elseif ($fontSize <= 14) {
            $font = 2;
        } elseif ($fontSize <= 16) {
            $font = 3;
        } elseif ($fontSize <= 18) {
            $font = 4;
        }

        return $font;
    }

    /**
     * Function: stackTrace.
     *
     * Prints a simple stack trace in the error log.
     */
    public static function stackTrace(): void
    {
        $arr = debug_backtrace();

        foreach ($arr as $value) {
            error_log($value['class'].'.'.$value['function']);
        }
    }
}
