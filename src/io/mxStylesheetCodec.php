<?php

declare(strict_types=1);

namespace Mxgraph\Io;

use Mxgraph\Util\mxUtils;
use Mxgraph\View\mxStylesheet;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxStylesheetCodec extends mxObjectCodec
{
    /**
     * Class: mxStylesheetCodec.
     *
     * Codec for <mxStylesheets>. This class is created and registered
     * dynamically at load time and used implicitely via <mxCodec>
     * and the <mxCodecRegistry>.
     *
     * Constructor: mxObjectCodec
     *
     * Constructs a new codec for the specified template object.
     * The variables in the optional exclude array are ignored by
     * the codec. Variables in the optional idrefs array are
     * turned into references in the XML. The optional mapping
     * may be used to map from variable names to XML attributes.
     *
     * Parameters:
     *
     * template - Prototypical instance of the object to be
     * encoded/decoded.
     * exclude - Optional array of fieldnames to be ignored.
     * idrefs - Optional array of fieldnames to be converted to/from
     * references.
     * mapping - Optional mapping from field- to attributenames.
     *
     * @param mixed $template
     */
    public function __construct($template)
    {
        parent::__construct($template);
    }

    /**
     * Override <mxObjectCodec.encode>.
     *
     * @param mixed $enc
     * @param mixed $obj
     */
    public function encode($enc, $obj)
    {
        $node = $enc->document->createElement($this->getName());

        foreach ($obj->styles as $i => $value) {
            $styleNode = $enc->document->createElement('add');

            if (isset($i)) {
                $styleNode->setAttribute('as', $i);

                foreach ($style as $j => $value) {
                    $value = $this->getStringValue($j, $value);

                    if (isset($value)) {
                        $entry = $enc->document->createElement('add');
                        $entry->setAttribute('value', $value);
                        $entry->setAttribute('as', $j);
                        $styleNode->appendChild($entry);
                    }
                }

                if ($styleNode->getChildCount() > 0) {
                    $node->appendChild($styleNode);
                }
            }
        }

        return node;
    }

    /**
     * Returns the string for encoding the given value.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function getStringValue($key, $value)
    {
        return (!\function_exists($value) && !\is_object($value)) ? $value : null;
    }

    /**
     * Override <mxObjectCodec.decode>.
     *
     * @param mixed      $dec
     * @param mixed      $node
     * @param null|mixed $into
     */
    public function decode($dec, $node, &$into = null)
    {
        $id = $node->getAttribute('id');
        $obj = (\in_array($id, $dec->objects, true)) ? $dec->objects[$id] : null;

        if (!isset($obj)) {
            if (isset($into)) {
                $obj = $into;
            } else {
                $tmp = \get_class($this->template);
                $obj = new $tmp();
            }

            if (isset($id)) {
                $dec->putObject($id, $obj);
            }
        }

        $node = $node->firstChild;

        while (isset($node)) {
            if (!$this->processInclude($dec, $node, $obj)
                && 'add' == $node->nodeName) {
                $as = $node->getAttribute('as');

                if ('' !== $as) {
                    $extend = $node->getAttribute('extend');

                    $style = ('' !== $extend
                        && isset($obj->styles[$extend])) ?
                        \array_slice($obj->styles[$extend], 0) :
                        null;

                    if (!isset($style)) {
                        $style = [];
                    }

                    $entry = $node->firstChild;

                    while (isset($entry)) {
                        if (\XML_ELEMENT_NODE == $entry->nodeType) {
                            $key = $entry->getAttribute('as');

                            if ('add' == $entry->nodeName) {
                                $text = $entry->textContent;
                                $value = null;

                                if (isset($text) && '' !== $text) {
                                    $value = mxUtils::evaluate($text);
                                } else {
                                    $value = $entry->getAttribute('value');
                                }

                                if (null != $value) {
                                    $style[$key] = $value;
                                }
                            } elseif ('remove' == $entry->nodeName) {
                                unset($style[$key]);
                            }
                        }

                        $entry = $entry->nextSibling;
                    }

                    $obj->putCellStyle($as, $style);
                }
            }

            $node = $node->nextSibling;
        }

        return $obj;
    }
}

mxCodecRegistry::register(new mxStylesheetCodec(new mxStylesheet()));
