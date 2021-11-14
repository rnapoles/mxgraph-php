<?php

declare(strict_types=1);

namespace Mxgraph\Model;

use Mxgraph\Util\mxUtils;

/**
 * Copyright (c) 2006-2013, Gaudenz Alder.
 */
class mxCell
{
    /**
     * Class: mxCell.
     *
     * Cells are the elements of the graph model. They represent the state
     * of the groups, vertices and edges in a graph.
     *
     * Variable: id
     *
     * Holds the Id. Default is null.
     */
    public $id;

    /**
     * Variable: value.
     *
     * Holds the user object. Default is null.
     */
    public $value;

    /**
     * Variable: geometry.
     *
     * Holds the <mxGeometry>. Default is null.
     */
    public $geometry;

    /**
     * Variable: style.
     *
     * Holds the style as a string of the form [(stylename|key=value);].
     * Default is null.
     */
    public $style;

    /**
     * Variable: vertex.
     *
     * Specifies whether the cell is a vertex. Default is false.
     */
    public $vertex = false;

    /**
     * Variable: edge.
     *
     * Specifies whether the cell is an edge. Default is false.
     */
    public $edge = false;

    /**
     * Variable: connectable.
     *
     * Specifies whether the cell is connectable. Default is true.
     */
    public $connectable = true;

    /**
     * Variable: visible.
     *
     * Specifies whether the cell is visible. Default is true.
     */
    public $visible = true;

    /**
     * Variable: collapsed.
     *
     * Specifies whether the cell is collapsed. Default is false.
     */
    public $collapsed = false;

    /**
     * Variable: parent.
     *
     * Reference to the parent cell.
     */
    public $parent;

    /**
     * Variable: source.
     *
     * Reference to the source terminal.
     */
    public $source;

    /**
     * Variable: target.
     *
     * Reference to the target terminal.
     */
    public $target;

    /**
     * Variable: children.
     *
     * Holds the child cells.
     */
    public $children; // transient

    /**
     * Variable: edges.
     *
     * Holds the edges.
     */
    public $edges; // transient

    /**
     * Constructor: mxCell.
     *
     * Constructs a new cell to be used in a graph model.
     * This method invokes <onInit> upon completion.
     *
     * Parameters:
     *
     * value - Optional object that represents the cell value.
     * geometry - Optional <mxGeometry> that specifies the geometry.
     * style - Optional formatted string that defines the style.
     *
     * @param null|mixed $value
     * @param null|mixed $geometry
     * @param null|mixed $style
     */
    public function __construct($value = null, $geometry = null, $style = null)
    {
        $this->setValue($value);
        $this->setGeometry($geometry);
        $this->setStyle($style);
    }

    /**
     * Function: getId.
     *
     * Returns the Id of the cell as a string.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Function: setId.
     *
     * Sets the Id of the cell to the given string.
     *
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Function: getValue.
     *
     * Returns the user object of the cell. The user
     * object is stored in <value>.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Function: setValue.
     *
     * Sets the user object of the cell. The user object
     * is stored in <value>.
     *
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Function: getGeometry.
     *
     * Returns the <mxGeometry> that describes the <geometry>.
     */
    public function getGeometry()
    {
        return $this->geometry;
    }

    /**
     * Function: setGeometry.
     *
     * Sets the <mxGeometry> to be used as the <geometry>.
     *
     * @param mixed $geometry
     */
    public function setGeometry($geometry): void
    {
        $this->geometry = $geometry;
    }

    /**
     * Function: getStyle.
     *
     * Returns a string that describes the <style>.
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Function: setStyle.
     *
     * Sets the string to be used as the <style>.
     *
     * @param mixed $style
     */
    public function setStyle($style): void
    {
        $this->style = $style;
    }

    /**
     * Function: isVertex.
     *
     * Returns true if the cell is a vertex.
     */
    public function isVertex()
    {
        return $this->vertex;
    }

    /**
     * Function: setVertex.
     *
     * Specifies if the cell is a vertex. This should only be assigned at
     * construction of the cell and not be changed during its lifecycle.
     *
     * Parameters:
     *
     * vertex - Boolean that specifies if the cell is a vertex.
     *
     * @param mixed $vertex
     */
    public function setVertex($vertex): void
    {
        $this->vertex = $vertex;
    }

    /**
     * Function: isEdge.
     *
     * Returns true if the cell is an edge.
     */
    public function isEdge()
    {
        return $this->edge;
    }

    /**
     * Function: setEdge.
     *
     * Specifies if the cell is an edge. This should only be assigned at
     * construction of the cell and not be changed during its lifecycle.
     *
     * Parameters:
     *
     * edge - Boolean that specifies if the cell is an edge.
     *
     * @param mixed $edge
     */
    public function setEdge($edge): void
    {
        $this->edge = $edge;
    }

    /**
     * Function: isConnectable.
     *
     * Returns true if the cell is connectable.
     */
    public function isConnectable()
    {
        return $this->connectable;
    }

    /**
     * Function: setConnectable.
     *
     * Sets the connectable state.
     *
     * Parameters:
     *
     * connectable - Boolean that specifies the new connectable state.
     *
     * @param mixed $connectable
     */
    public function setConnectable($connectable): void
    {
        $this->connectable = $connectable;
    }

    /**
     * Function: isVisible.
     *
     * Returns true if the cell is visibile.
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * Function: setVisible.
     *
     * Specifies if the cell is visible.
     *
     * Parameters:
     *
     * visible - Boolean that specifies the new visible state.
     *
     * @param mixed $visible
     */
    public function setVisible($visible): void
    {
        $this->visible = $visible;
    }

    /**
     * Function: isCollapsed.
     *
     * Returns true if the cell is collapsed.
     */
    public function isCollapsed()
    {
        return $this->collapsed;
    }

    /**
     * Function: setCollapsed.
     *
     * Sets the collapsed state.
     *
     * Parameters:
     *
     * collapsed - Boolean that specifies the new collapsed state.
     *
     * @param mixed $collapsed
     */
    public function setCollapsed($collapsed): void
    {
        $this->collapsed = $collapsed;
    }

    /**
     * Function: getParent.
     *
     * Returns the cell's parent.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Function: setParent.
     *
     * Sets the parent cell.
     *
     * Parameters:
     *
     * parent - <mxCell> that represents the new parent.
     *
     * @param mixed $parent
     */
    public function setParent($parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Function: getTerminal.
     *
     * Returns the source or target terminal.
     *
     * Parameters:
     *
     * source - Boolean that specifies if the source terminal should be
     * returned.
     *
     * @param mixed $source
     */
    public function getTerminal($source)
    {
        if ($source) {
            return $this->source;
        }

        return $this->target;
    }

    /**
     * Function: setTerminal.
     *
     * Sets the source or target terminal and returns the new terminal.
     *
     * Parameters:
     *
     * terminal - <mxCell> that represents the new source or target terminal.
     * source - Boolean that specifies if the source or target terminal
     * should be set.
     *
     * @param mixed $terminal
     * @param mixed $source
     */
    public function setTerminal($terminal, $source)
    {
        if ($source) {
            $this->source = $terminal;
        } else {
            $this->target = $terminal;
        }

        return $terminal;
    }

    /**
     * Function: getChildCount.
     *
     * Returns the number of child cells.
     */
    public function getChildCount()
    {
        return (null == $this->children) ? 0 : \count($this->children);
    }

    /**
     * Function: getIndex.
     *
     * Returns the index of the specified child in the child array.
     *
     * Parameters:
     *
     * child - Child whose index should be returned.
     *
     * @param mixed $child
     */
    public function getIndex($child)
    {
        return mxUtils::indexOf($this->children, $child);
    }

    /**
     * Function: getChildAt.
     *
     * Returns the child at the specified index.
     *
     * Parameters:
     *
     * index - Integer that specifies the child to be returned.
     *
     * @param mixed $index
     */
    public function getChildAt($index)
    {
        if (null != $this->children) {
            return $this->children[$index];
        }

        return null;
    }

    /**
     * Function: insert.
     *
     * Inserts the specified child into the child array at the specified index
     * and updates the parent reference of the child. If not childIndex is
     * specified then the child is appended to the child array. Returns the
     * inserted child.
     *
     * Parameters:
     *
     * child - <mxCell> to be inserted or appended to the child array.
     * index - Optional integer that specifies the index at which the child
     * should be inserted into the child array.
     *
     * @param mixed      $child
     * @param null|mixed $index
     */
    public function insert($child, $index = null)
    {
        if (isset($child)) {
            if (!isset($index)) {
                $index = $this->getChildCount();

                if ($child->getParent() === $this) {
                    --$index;
                }
            }

            $child->removeFromParent();
            $child->setParent($this);

            if (null == $this->children) {
                $this->children = [];
                $this->children[] = $child;
            } else {
                array_splice($this->children, $index, 0, [$child]);
            }
        }

        return $child;
    }

    /**
     * Function: remove.
     *
     * Removes the child at the specified index from the child array and
     * returns the child that was removed. Will remove the parent reference of
     * the child.
     *
     * Parameters:
     *
     * index - Integer that specifies the index of the child to be
     * removed.
     *
     * @param mixed $index
     */
    public function remove($index)
    {
        if (null != $this->children && $index >= 0) {
            $child = $this->getChildAt($index);

            if (null != $child) {
                array_splice($this->children, $index, 1);
                $child->setParent(null);
            }

            return $child;
        }

        return null;
    }

    /**
     * Function: removeFromParent.
     *
     * Removes the cell from its parent.
     */
    public function removeFromParent(): void
    {
        if (isset($this->parent)) {
            $index = $this->parent->getIndex($this);
            $this->parent->remove($index);
        }
    }

    /**
     * Function: getEdgeCount.
     *
     * Returns the number of edges in the edge array.
     */
    public function getEdgeCount()
    {
        return (null == $this->edges) ? 0 : \count($this->edges);
    }

    /**
     * Function: getEdgeIndex.
     *
     * Returns the index of the specified edge in <edges>.
     *
     * Parameters:
     *
     * edge - <mxCell> whose index in <edges> should be returned.
     *
     * @param mixed $edge
     */
    public function getEdgeIndex($edge)
    {
        return mxUtils::indexOf($this->edges, $edge);
    }

    /**
     * Function: getEdgeAt.
     *
     * Returns the edge at the specified index in <edges>.
     *
     * Parameters:
     *
     * index - Integer that specifies the index of the edge to be returned.
     *
     * @param mixed $index
     */
    public function getEdgeAt($index)
    {
        if (null != $this->edges) {
            return $this->edges[$index];
        }

        return null;
    }

    /**
     * Function: insertEdge.
     *
     * Inserts the specified edge into the edge array and returns the edge.
     * Will update the respective terminal reference of the edge.
     *
     * Parameters:
     *
     * edge - <mxCell> to be inserted into the edge array.
     * outgoing - Boolean that specifies if the edge is outgoing.
     *
     * @param mixed $edge
     * @param mixed $outgoing
     */
    public function insertEdge($edge, $outgoing)
    {
        if (isset($edge)) {
            $edge->removeFromTerminal($outgoing);
            $edge->setTerminal($this, $outgoing);

            if (null == $this->edges
                || $edge->getTerminal(!$outgoing) !== $this
                || mxUtils::indexOf($this->edges, $edge) < 0) {
                if (null == $this->edges) {
                    $this->edges = [];
                }

                $this->edges[] = $edge;
            }
        }

        return $edge;
    }

    /**
     * Function: removeEdge.
     *
     * Removes the specified edge from the edge array and returns the edge.
     * Will remove the respective terminal reference from the edge.
     *
     * Parameters:
     *
     * edge - <mxCell> to be removed from the edge array.
     * outgoing - Boolean that specifies if the edge is outgoing.
     *
     * @param mixed $edge
     * @param mixed $outgoing
     */
    public function removeEdge($edge, $outgoing)
    {
        if (isset($edge)) {
            if ($edge->getTerminal(!$outgoing) !== $this
                && null != $this->edges) {
                $index = $this->getEdgeIndex($edge);

                if ($index >= 0) {
                    array_splice($this->edges, $index, 1);
                }
            }

            $edge->setTerminal(null, $outgoing);
        }

        return $edge;
    }

    /**
     * Function: removeFromTerminal.
     *
     * Removes the edge from its source or target terminal.
     *
     * Parameters:
     *
     * source - Boolean that specifies if the edge should be removed from its
     * source or target terminal.
     *
     * @param mixed $source
     */
    public function removeFromTerminal($source): void
    {
        $terminal = $this->getTerminal($source);

        if (isset($terminal)) {
            $terminal->removeEdge($this, $source);
        }
    }

    /**
     * Function: getAttribute.
     *
     * Returns the specified attribute from the user object if it is an XML
     * node.
     *
     * @param mixed      $key
     * @param null|mixed $defaultValue
     */
    public function getAttribute($key, $defaultValue = null)
    {
        $userObject = $this->getValue();

        $value = (\is_object($userObject)
            && \XML_ELEMENT_NODE == $userObject->nodeType) ?
            $userObject->getAttribute($key) : null;

        if (!isset($value)) {
            $value = $defaultValue;
        }

        return $value;
    }

    /**
     * Function: setAttribute.
     *
     * Sets the specified attribute on the user object if it is an XML node.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function setAttribute($key, $value): void
    {
        $userObject = $this->getValue();

        if (\is_object($userObject)
            && \XML_ELEMENT_NODE == $userObject->nodeType) {
            $userObject->setAttribute($key, $value);
        }
    }

    /**
     * Function: copy.
     *
     * Returns a clone of the cell. Uses <cloneValue> to clone
     * the user object.
     */
    public function copy()
    {
        $clone = new self($this->copyValue(), null, $this->style);
        $clone->vertex = $this->vertex;
        $clone->edge = $this->edge;
        $clone->connectable = $this->connectable;
        $clone->visible = $this->visible;
        $clone->collapsed = $this->collapsed;

        // Clones the geometry
        if (isset($this->geometry)) {
            $clone->geometry = $this->geometry->copy();
        }

        return $clone;
    }

    /**
     * Function: copyValue.
     *
     * Returns a clone of the cell's user object.
     */
    public function copyValue()
    {
        $value = $this->getValue();

        if (isset($value)) {
            if (method_exists($value, 'cloneNode')) {
                $value = $value->cloneNode(true);
            }
        }

        return $value;
    }
}
