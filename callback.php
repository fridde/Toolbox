<?php
	include("autoload.php");
	activateDebug();
	inc("vendor");
	use \Fridde\Utility as U;
	use \Fridde\SMS as SMS;
	
	
	$response_pattern = "Vi ses";
	
	U::extractRequest();
	$message = (isset($message)) ? strtolower($message) : "" ;
	$sender_nr = (isset($contact["number"])) ? $contact["number"] : false;
	
	if($type == "message_received" && strpos($message, strtolower($response_pattern)) !== false){
		$send_message = "Tack! Vi ses 15 juni!";
		U::logg($send_message);
		U::logg($sender_nr);
		$SMS = new SMS($send_message, $sender_nr);
		$response = $SMS->send();
		U::logg($response);
	}
	else if($type == "message_updated"){
		U::logg($_REQUEST);
	}
	else {
		U::logg($response_pattern . " not included in sms");
	}		