<?php // /home/nuov2ituldev/public_html/scripts/findPartsFromDiagrams.php

use scripts\Itul\SVGReader;

/*
    This file is run from cron job or command line and will search all the svg files and save the model numbers to
    the database.
*/

define('ROOT_DIR', dirname(__DIR__)."/");
include_once ROOT_DIR.'autoloader.php';

$dbName = "**********"; // obscured for sample code.
$dbUser = "**********"; // obscured for sample code.
$dbPass = "**********"; // obscured for sample code.
$dbHost = "localhost";
$db     = new \mysqli($dbHost, $dbUser, $dbPass, $dbName, '3306');

if ($db->connect_error) {
    trigger_error('Error: Could not make a database link (' . $db->connect_errno . ') ' . $db->connect_error);
    exit();
}

$reader = new SVGReader($db);
$reader->run();