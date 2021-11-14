<?php

declare(strict_types=1);

namespace Mxgraph\Io;

use Mxgraph\Model\mxCell;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCellCodec extends mxObjectCodec
{
    /**
     * Class: mxCellCodec.
     *
     * Codec for <mxCell>s. This class is created and registered
     * dynamically at load time and used implicitely via <mxCodec>
     * and the <mxCodecRegistry>.
     *
     * Transient Fields:
     *
     * - children
     * - edges
     * - states
     * - overlay
     * - mxTransient
     *
     * Reference Fields:
     *
     * - parent
     * - source
     * - target
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
        parent::__construct($template, ['children', 'edges', 'states',
            'overlay', 'mxTransient', ], ['parent',
                'source', 'target', ]);
    }

    /**
     * Override <mxObjectCodec.isExcluded>.
     *
     * @param mixed $obj
     * @param mixed $attr
     * @param mixed $value
     * @param mixed $isWrite
     */
    public function isExcluded($obj, $attr, $value, $isWrite)
    {
        return parent::isExcluded($obj, $attr, $value, $isWrite)
                || ($isWrite && 'value' == $attr && \is_object($value)
               && 'DOMElement' == \get_class($value));
    }

    /**
     * Override <mxObjectCodec.afterEncode>.
     *
     * @param mixed $enc
     * @param mixed $obj
     * @param mixed $node
     */
    public function afterEncode($enc, $obj, $node)
    {
        if (\is_object($obj->value) && 'DOMElement' == \get_class($obj->value)) {
            // Wraps the graphical annotation up in the
            // user object (inversion) by putting the
            // result of the default encoding into
            // a clone of the user object (node type 1)
            // and returning this cloned user object.
            $tmp = $node;

            $node = $enc->document->importNode($obj->value, true);
            $node->appendChild($tmp);

            // Moves the id attribute to the outermost
            // XML node, namely the node which denotes
            // the object boundaries in the file.
            $id = $tmp->getAttribute('id');
            $node->setAttribute('id', $id);
            $tmp->removeAttribute('id');
        }

        return $node;
    }

    /**
     * Override <mxObjectCodec.beforeDecode>.
     *
     * @param mixed $dec
     * @param mixed $node
     * @param mixed $obj
     */
    public function beforeDecode($dec, $node, &$obj)
    {
        $inner = $node;
        $classname = $this->getName();

        if ($node->nodeName != $classname) {
            // Passes the inner graphical annotation node to the
            // object codec for further processing of the cell.
            $tmp = $node->getElementsByTagName($classname)->item(0);

            if (isset($tmp) && $tmp->parentNode == $node) {
                $inner = $tmp;

                // Removes annotation and whitespace from node
                $tmp2 = $tmp->previousSibling;

                while (isset($tmp2) && \XML_TEXT_NODE == $tmp2->nodeType) {
                    $tmp3 = $tmp2->previousSibling;

                    if (0 == \strlen(trim($tmp2->textContent))) {
                        $tmp2->parentNode->removeChild($tmp2);
                    }

                    $tmp2 = $tmp3;
                }

                // Removes more whitespace
                $tmp2 = $tmp->nextSibling;

                while (isset($tmp2) && \XML_TEXT_NODE == $tmp2->nodeType) {
                    $tmp3 = $tmp2->previousSibling;

                    if (0 == \strlen(trim($tmp2->textContent))) {
                        $tmp2->parentNode->removeChild($tmp2);
                    }

                    $tmp2 = $tmp3;
                }

                $tmp->parentNode->removeChild($tmp);
            } else {
                $inner = null;
            }

            // Creates the user object out of the XML node
            $obj->value = $node->cloneNode(true);
            $id = $obj->value->getAttribute('id');

            if ('' !== $id) {
                $obj->setId($id);
                $obj->value->removeAttribute('id');
            }
        } else {
            $obj->setId($node->getAttribute('id'));
        }

        // Preprocesses and removes all Id-references
        // in order to use the correct encoder (this)
        // for the known references to cells (all).
        if (isset($inner)) {
            for ($i = 0; $i < \count($this->idrefs); ++$i) {
                $attr = $this->idrefs[$i];
                $ref = $inner->getAttribute($attr);

                if ('' !== $ref) {
                    $inner->removeAttribute($attr);
                    $object = (isset($dec->objects[$ref])) ? $dec->objects[$ref] : null;

                    if (!isset($object)) {
                        $object = $dec->lookup($ref);
                    }

                    if (!isset($object)) {
                        // Needs to decode forward reference
                        $element = $dec->getElementById($ref);

                        if (isset($element)) {
                            $decoder = mxCodecRegistry::$codecs[$element->nodeName];

                            if (!isset($decoder)) {
                                $decoder = $this;
                            }

                            $object = $decoder->decode($dec, $element);
                        }
                    }

                    $obj->{$attr} = $object;
                }
            }
        }

        return $inner;
    }
}
