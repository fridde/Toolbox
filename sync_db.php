<?php
	include("autoload.php");
	activateDebug();
	inc("vendor");
	
	use \Fridde\Dumper as D;
	
	$D = new D;
	
	if(!isset($_REQUEST["direction"])){
		echo "Error: direction parameter not set.";
		exit();
	}
	
	if($_REQUEST["direction"] == "export"){
		$D->export();
	}
	else if($_REQUEST["direction"] == "import"){
		$D->import();
	}
	else {
		echo $_REQUEST["direction"] . " is not a valid value for \"direction\"";
	}
