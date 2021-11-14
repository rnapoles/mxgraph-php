<?php
/**
 * Copyright (c) 2006, Gaudenz Alder
 */

use Mxgraph\Io\mxCodec;
use Mxgraph\Model\mxGraphModel;
use Mxgraph\Util\mxConstants;
use Mxgraph\Util\mxPoint;
use Mxgraph\Util\mxUtils;
use Mxgraph\View\mxGraph;

include_once __DIR__ . "/../vendor/autoload.php";

error_reporting(E_ALL);
ini_set('display_errors', 'on');

/**
 * Function: main
 * 
 * Creates a graph using the API and converts it into a PNG image.
 */
function main()
{
	$targetXml = '<mxGraphModel><root><mxCell id="0"/><mxCell id="1" parent="0"/><mxCell id="4" value="e1" edge="1" parent="1" source="2" target="3"><mxGeometry relative="1" as="geometry"><Array as="points"><mxPoint x="10" y="10"/></Array></mxGeometry></mxCell><mxCell id="5" value="v3" style="shape=ellipse" vertex="1" parent="4"><mxGeometry relative="1" width="40" height="40" as="geometry"><mxPoint x="-20" y="-20" as="offset"/></mxGeometry></mxCell><mxCell id="2" value="Hello" vertex="1" parent="1"><mxGeometry width="80" height="30" x="20" y="20" as="geometry"/></mxCell><mxCell id="3" value="World!" vertex="1" parent="1"><mxGeometry width="80" height="30" x="200" y="150" as="geometry"/></mxCell></root></mxGraphModel>';

	// Creates graph with model
	$model = new mxGraphModel();
	$graph = new mxGraph($model);
	$parent = $graph->getDefaultParent();

	// Adds cells into the model
	$model->beginUpdate();
	try
	{
		$v1 = $graph->insertVertex($parent, null, "Hello", 20, 20, 80, 30);
		$v2 = $graph->insertVertex($parent, null, "World!", 200, 150, 80, 30);
		$e1 = $graph->insertEdge($parent, null, "e1", $v1, $v2);
		$e1->getGeometry()->points = array(new mxPoint(10, 10));

		$v3 = $graph->insertVertex($e1, null, "v3", 0, 0, 40, 40, "shape=ellipse");
		$v3->getGeometry()->relative = true;
		$v3->getGeometry()->offset = new mxPoint(-20, -20);
		
		$model->add($parent, $e1, 0);
	}
	catch (\Exception $e)
	{
		$model->endUpdate();
		throw($e);
	}
	$model->endUpdate();

	$doc = mxUtils::createXmlDocument();
	$enc = new mxCodec($doc);
	$node = $enc->encode($model);
	$xml1 = $doc->saveXML($node);

	$doc = mxUtils::parseXml($xml1);
	$dec = new mxCodec($doc);
	$dec->decode($doc->documentElement, $model);

	$doc = mxUtils::createXmlDocument();
	$enc = new mxCodec($doc);
	$node = $enc->encode($model);
	$xml2 = $doc->saveXML($node);

	if ($xml1 == $xml2 && $xml1 == $targetXml)
	{
		echo "Test Passed: ".htmlentities($xml1);
	}
	else
	{
		echo "<br /><br />Test Failed: <br>xml1=".htmlentities($xml1)."<br /><br /><hr />" .htmlentities($targetXml);
	}
}

// Uses a local font so that all examples work on all platforms. This can be
// changed to vera on Mac or arial on Windows systems.
mxConstants::$DEFAULT_FONTFAMILY = "verah";
\Mxgraph\Util\mxLog::$printLog = true;

putenv("GDFONTPATH=".realpath("../examples/ttf"));

// If you can't get the fonts to render try using one of the following:
//mxConstants::$DEFAULT_FONTFAMILY = "C:\WINDOWS\Fonts\arial.ttf";
//mxConstants::$TTF_ENABLED = false;

main();
?>
