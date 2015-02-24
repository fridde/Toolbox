<?php
	$remoteFileArray = array(
	"https://raw.githubusercontent.com/fridde/friddes_php_functions/master/functions.php",
	"https://raw.githubusercontent.com/fridde/friddes_php_functions/master/sql_functions.php");
	$localFileArray = array();
	foreach($remoteFileArray as $file){
		$content = file_get_contents($file);
		$name = explode("/", $file);
		$name = "inc/" . end($name);
		$localFileArray[] = $name;
		file_put_contents($name, $content);
	}
	
	foreach($localFileArray as $file){
		include $file;
	}
