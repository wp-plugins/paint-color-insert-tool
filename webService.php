<?php
/**
 * @package Paint Color Insert Tool
 * @copyright Copyright (C) 2011 My Perfect Color. All rights reserved.
 * @version 1.0
 */

/* FindWPConfig - searching for a root of wp */
function FindWPConfig($directory){
	global $confroot;
	foreach(glob($directory."/*") as $f){

		if (basename($f) == 'wp-config.php' ){
			$confroot = str_replace("\\", "/", dirname($f));
			return true;
		}

		if (is_dir($f)){
			$newdir = dirname(dirname($f));
		}
	}

	if (isset($newdir) && $newdir != $directory){
		if (FindWPConfig($newdir)){
			return false;
		}
	}
	return false;
}


if (!isset($table_prefix)){
	global $confroot;
	FindWPConfig(dirname(dirname(__FILE__)));
	include_once $confroot."/wp-config.php";
	include_once $confroot."/wp-load.php";
}


$xml = simplexml_load_file($_GET['url'] . "?component=" . $_GET['component'] . "&controller=" . $_GET['controller'] . "&action=" . $_GET['action'] . "&keyword=" . $_GET['keyword'] . "&resource=" . $_GET['resource'] . "&brand=" . $_GET['brand'] . "&from=" . $_GET['from'] . "&step=" . $_GET['step'] . "&plugin=" . $_GET['plugin'] . "&email=" . $_GET['email']);

if ($xml === FALSE) {

	$xmlStr = wp_remote_fopen($_GET['url'] . "?component=" . $_GET['component'] . "&controller=" . $_GET['controller'] . "&action=" . $_GET['action'] . "&keyword=" . $_GET['keyword'] . "&resource=" . $_GET['resource'] . "&brand=" . $_GET['brand'] . "&from=" . $_GET['from'] . "&step=" . $_GET['step'] . "&plugin=" . $_GET['plugin'] . "&email=" . $_GET['email'], 'r');

	if ($xmlStr) {
		$xml = new SimpleXMLElement($xmlStr);

		if ($xml === FALSE) {
			// These lines show xml errors
			libxml_use_internal_errors(true);
			$errors = libxml_get_errors();
			print_r(($errors));
			libxml_clear_errors();
			exit();
		} else {
			header('Content-type: text/xml');
			echo $xml->asXML();
			exit();
		}
	}

} else {
	header('Content-type: text/xml');
	echo $xml->asXML();
	exit();
}

