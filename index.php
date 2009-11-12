<?php
/*
 * Matt Newman
 * 28 Sep 2009
 *
 * This is a prototype document to turn the UltimaDisplays XML feed into semantic HTML
 */

//turn on output buffering (as may not be set at the server level in php.ini)
ob_start();

// this code is to time the script
require_once('ScriptTimer.php');
ScriptTimer::timing_milestone('begin');

//include the Config class
require_once('UltimaConfig.php');
//include the UltimaFeed class
require_once('UltimaFeed.php');
//include the transformation class
require_once('UltimaTransformation.php');

$config = UltimaConfig::getInstance();

//TODO: preflight checks: check last time script was run - if it's never been run do some cursory check of the environment - loaded extenstions etc


//$UltimaFeed = new UltimaFeed("http://www.ultimadisplays.co.uk/ssxml_feed.asp?queryname=topcategories&accesscode=XML1808092003267");
//$UltimaFeed->getAllSubCategoriesAndItems();

UltimaTransformation::transformEntireFeed();

// this code shows how long the script took to execute
ScriptTimer::dump_profile();

//send the buffered output to the page
ob_end_flush();

?>
