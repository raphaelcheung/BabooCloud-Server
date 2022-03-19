<?php

use app\lib\Base;

// Set the content type to Javascript
\header("Content-type: text/javascript;charset=UTF-8");

// Disallow caching
\header("Cache-Control: no-cache, must-revalidate");
\header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

$array = [];
//$tmp = $GLOBALS['baseinfo'];
$array['g_base'] = \json_encode([
	'name' => '巴布云',
	'title' => 'BabooCloud',
]);


foreach ($array as  $setting => $value) {
	echo "var ". $setting ."=".$value.";\n";
}