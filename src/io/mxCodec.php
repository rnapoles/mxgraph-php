<?php

declare(strict_types=1);

namespace Mxgraph\Io;

use Mxgraph\Model\mxCellPath;
use Mxgraph\Util\mxLog;
use Mxgraph\Util\mxUtils;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCodec
{
    /**
     * Class: mxCodec.
     *
     * XML codec for PHP object graphs. In order to resolve forward references
     * when reading files the XML document that contains the data must be passed
     * to the constructor.
     *
     * Variable: document
     *
     * The owner document of the codec.
     */
    public $document;

    /**
     * Variable: objects.
     *
     * Maps from IDs to objects.
     */
    public $objects = [];

    /**
     * Variable: elements.
     *
     * Maps from IDs to elements.
     */
    public $elements;

    /**
     * Variable: encodeDefaults.
     *
     * Specifies if default values should be encoded.
     * Default is false.
     */
    public $encodeDefaults = false;

    /**
     * Constructor: mxGraphViewHtmlReader.
     *
     * Constructs a new HTML graph view reader.
     *
     * @param null|mixed $document
     */
    public function __construct($document = null)
    {
        if (null == $document) {
            $document = mxUtils::createXmlDocument();
        }

        $this->document = $document;
    }

    /**
     * Function: putObject.
     *
     * Assoiates the given object with the given ID.
     *
     * Parameters
     *
     * id - ID for the object to be associated with.
     * obj - Object to be associated with the ID.
     *
     * @param mixed $id
     * @param mixed $object
     */
    public function putObject($id, $object)
    {
        $this->objects[$id] = $object;

        return $object;
    }

    /**
     * Function: getObject.
     *
     * Returns the decoded object for the element with the specified ID in
     * <document>. If the object is not known then <lookup> is used to find an
     * object. If no object is found, then the element with the respective ID
     * from the document is parsed using <decode>.
     *
     * @param mixed $id
     */
    public function getObject($id)
    {
        $obj = null;

        if (isset($id)) {
            $obj = $this->objects[$id];

            if (!isset($obj)) {
                $obj = $this->lookup($id);

                if (!isset($obj)) {
                    $node = $this->getElementById($id);

                    if (isset($node)) {
                        $obj = $this->decode($node);
                    }
                }
            }
        }

        return $obj;
    }

    /**
     * Function: lookup.
     *
     * Hook for subclassers to implement a custom lookup
     * mechanism for cell IDs. This implementation always
     * returns null.
     *
     * Parameters:
     *
     * id - ID of the object to be returned.
     *
     * @param mixed $id
     */
    public function lookup($id)
    {
        return null;
    }

    /**
     * Function: getElementById.
     *
     * Returns the element with the given ID from <document>.
     *
     * Parameters:
     *
     * id - String that contains the ID.
     *
     * @param mixed $id
     */
    public function getElementById($id)
    {
        if (null == $this->elements) {
            $this->elements = [];
            $this->addElement($this->document->documentElement);
        }

        return $this->elements[$id];
    }

    /**
     * Function: addElement.
     *
     * Adds the given element to <elements> if it has an ID.
     *
     * @param mixed $node
     */
    public function addElement($node): void
    {
        if ($node instanceof \DOMElement) {
            $id = $node->getAttribute('id');

            if (null != $id && false == \array_key_exists($id, $this->elements)) {
                $this->elements[$id] = $node;
            }
        }

        $node = $node->firstChild;

        while (null != $node) {
            $this->addElement($node);
            $node = $node->nextSibling;
        }
    }

    /**
     * Function: getId.
     *
     * Returns the ID of the specified object. This implementation
     * calls <reference> first and if that returns null handles
     * the object as an <mxCell> by returning their IDs using
     * <mxCell.getId>. If no ID exists for the given cell, then
     * an on-the-fly ID is generated using <mxCellPath.create>.
     *
     * Parameters:
     *
     * obj - Object to return the ID for.
     *
     * @param mixed $obj
     */
    public function getId($obj)
    {
        $id = null;

        if (isset($obj)) {
            $id = $this->reference($obj);

            if (!isset($id) && 'mxCell' == mxCodecRegistry::getName($obj)) {
                $id = $obj->getId();

                if (!isset($id)) {
                    // Uses an on-the-fly Id
                    $id = mxCellPath::create($obj);

                    if (0 == \strlen($id)) {
                        $id = 'root';
                    }
                }
            }
        }

        return $id; //str_replace("\\", "_", $id);
    }

    /**
     * Function: reference.
     *
     * Hook for subclassers to implement a custom method
     * for retrieving IDs from objects. This implementation
     * always returns null.
     *
     * Parameters:
     *
     * obj - Object whose ID should be returned.
     *
     * @param mixed $obj
     */
    public function reference($obj)
    {
        return null;
    }

    /**
     * Function: encode.
     *
     * Encodes the specified object and returns the resulting
     * XML node.
     *
     * Parameters:
     *
     * obj - Object to be encoded.
     *
     * @param mixed $obj
     */
    public function encode($obj)
    {
        $node = null;

        if (\is_object($obj) || \is_array($obj)) {
            if (\is_array($obj)) {
                $enc = new mxObjectCodec([]);
            } else {
                $enc = mxCodecRegistry::getCodec(
                    mxCodecRegistry::getName($obj)
                );
            }

            if (isset($enc)) {
                $node = $enc->encode($this, $obj);
            } else {
                if ('DOMElement' == \get_class($obj)) {
                    /** @var \DOMElement $node */
                    $node = $obj->cloneNode(true);
                } else {
                    mxLog::warn('mxCodec.encode: No codec for '.
                        mxCodecRegistry::getName($obj));
                }
            }
        }

        return $node;
    }

    /**
     * Function: decode.
     *
     * Decodes the given XML node. The optional "into"
     * argument specifies an existing object to be
     * used. If no object is given, then a new instance
     * is created using the constructor from the codec.
     *
     * The function returns the passed in object or
     * the new instance if no object was given.
     *
     * Parameters:
     *
     * node - XML node to be decoded.
     * into - Optional object to be decodec into.
     *
     * @param mixed      $node
     * @param null|mixed $into
     */
    public function decode($node, $into = null)
    {
        $obj = null;

        if (isset($node) && \XML_ELEMENT_NODE == $node->nodeType) {
            $dec = mxCodecRegistry::getCodec($node->nodeName);

            try {
                if (isset($dec)) {
                    $obj = $dec->decode($this, $node, $into);
                } else {
                    $obj = $node->cloneNode(true);
                    $obj->removeAttribute('as');
                }
            } catch (\Exception $ex) {
                // ignore
                mxLog::debug('Cannot decode '.$node->nodeName.": {$ex}");

                throw $ex;
            }
        }

        return $obj;
    }

    /**
     * Function: encodeCell.
     *
     * Encoding of cell hierarchies is built-into the core, but
     * is a higher-level function that needs to be explicitely
     * used by the respective object encoders (eg. <mxModelCodec>,
     * <mxChildChangeCodec> and <mxRootChangeCodec>). This
     * implementation writes the given cell and its children as a
     * (flat) sequence into the given node. The children are not
     * encoded if the optional includeChildren is false. The
     * function is in charge of adding the result into the
     * given node and has no return value.
     *
     * Parameters:
     *
     * cell - <mxCell> to be encoded.
     * node - Parent XML node to add the encoded cell into.
     * includeChildren - Optional boolean indicating if the
     * function should include all descendents. Default is true.
     *
     * @param mixed $cell
     * @param mixed $node
     * @param mixed $includeChildren
     */
    public function encodeCell($cell, $node, $includeChildren = true): void
    {
        $node->appendChild($this->encode($cell));

        if ($includeChildren) {
            $childCount = $cell->getChildCount();

            for ($i = 0; $i < $childCount; ++$i) {
                $this->encodeCell($cell->getChildAt($i), $node);
            }
        }
    }

    /**
     * Function: decodeCell.
     *
     * Decodes cells that have been encoded using inversion, ie.
     * where the user object is the enclosing node in the XML,
     * and restores the group and graph structure in the cells.
     * Returns a new <mxCell> instance that represents the
     * given node.
     *
     * Parameters:
     *
     * node - XML node that contains the cell data.
     * restoreStructures - Optional boolean indicating whether
     * the graph structure should be restored by calling insert
     * and insertEdge on the parent and terminals, respectively.
     * Default is true.
     *
     * @param mixed $node
     * @param mixed $restoreStructures
     */
    public function decodeCell($node, $restoreStructures = true)
    {
        $cell = null;

        if (isset($node) && \XML_ELEMENT_NODE == $node->nodeType) {
            // Tries to find a codec for the given node name. If that does
            // not return a codec then the node is the user object (an XML node
            // that contains the mxCell, aka inversion).
            $decoder = mxCodecRegistry::getCodec($node->nodeName);

            // Tries to find the codec for the cell inside the user object.
            // This assumes all node names inside the user object are either
            // not registered or they correspond to a class for cells.
            if (!isset($decoder)) {
                $child = $node->firstChild;

                while (isset($child) && !($decoder instanceof mxCellCodec)) {
                    $decoder = mxCodecRegistry::getCodec($child->nodeName);
                    $child = $child->nextSibling;
                }
            }

            if (!($decoder instanceof mxCellCodec)) {
                $decoder = mxCodecRegistry::getCodec('mxCell');
            }

            $cell = $decoder->decode($this, $node);

            if ($restoreStructures) {
                $this->insertIntoGraph($cell);
            }
        }

        return $cell;
    }

    /**
     * Function: insertIntoGraph.
     *
     * Inserts the given cell into its parent and terminal cells.
     *
     * @param mixed $cell
     */
    public function insertIntoGraph($cell): void
    {
        $parent = $cell->getParent();
        $source = $cell->getTerminal(true);
        $target = $cell->getTerminal(false);

        // Fixes possible inconsistencies during insert into graph
        $cell->setTerminal(null, false);
        $cell->setTerminal(null, true);
        $cell->setParent(null);

        if (isset($parent)) {
            $parent->insert($cell);
        }

        if (isset($source)) {
            $source->insertEdge($cell, true);
        }

        if (isset($target)) {
            $target->insertEdge($cell, false);
        }
    }

    /**
     * Function: setAttribute.
     *
     * Sets the attribute on the specified node to value. This is a
     * helper method that makes sure the attribute and value arguments
     * are not null.
     *
     * Parameters:
     *
     * node - XML node to set the attribute for.
     * attributes - Attributename to be set.
     * value - New value of the attribute.
     *
     * @param mixed $node
     * @param mixed $attribute
     * @param mixed $value
     */
    public function setAttribute($node, $attribute, $value): void
    {
        if (\is_array($value)) {
            error_log("cannot write array {$attribute}");
        } elseif (isset($attribute, $value)) {
            $node->setAttribute($attribute, $value);
        }
    }
}
