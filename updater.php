<?php 
	$options  = array('http' => array('user_agent' => 'fridde'));
	$context  = stream_context_create($options);
	
	
	$request_1 = file_get_contents("https://api.github.com/repos/fridde/friddes_php_functions/git/refs/heads/master", false, $context);
	$response_1 = json_decode($request_1, true);
	
	$request_2 = file_get_contents($response_1["object"]["url"], false, $context);
	$response_2 = json_decode($request_2, true);
	
	$commit_date = new DateTime($response_2["committer"]["date"]);
	$diff = $commit_date->diff(new DateTime('now'));
	$diff = $diff->format('%d');
	
	echo $diff;
	
	
	
	