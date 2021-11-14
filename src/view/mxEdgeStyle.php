<?php
namespace Mxgraph\View;

/**	
 *
 * Class: mxEdgeStyle
 * 
 * Provides various edge styles to be used as the values for
 * <mxConstants.STYLE_EDGE> in a cell style.
 */
class mxEdgeStyle
{

	/**
	 * Variable: EntityRelation
	 *
	 * Provides an entity relation style for edges (as used in database
	 * schema diagrams).
	 */
	public static $EntityRelation;

	/**
	 * Variable: Loop
	 *
	 * Provides a self-reference, aka. loop.
	 */
	public static $Loop;

	/**
	 * Variable: ElbowConnector
	 *
	 * Provides an elbow connector.
	 */
	public static $ElbowConnector;
	
	/**
	 * Variable: SideToSide
	 *
	 * Provides a side to side connector.
	 */
	public static $SideToSide;

	/**
	 * Variable: TopToBottom
	 *
	 * Provides a top to bottom connector.
	 */
	public static $TopToBottom;

}