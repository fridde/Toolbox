<?php 
	/**
		* Returns the timestamp of the most recent commit to a given Github repo.
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return string The commit date given as a string in the format ISO 8601
	*/ 
	function getRecentCommitTime($user, $repo){
		// github doesn't allow for requests without user agent
		$options  = array('http' => array('user_agent' => 'fridde')); 
		$context  = stream_context_create($options);
		
		$url = "https://api.github.com/repos/". $user. "/". $repo ."/git/refs/heads/master";
		
		$request_1 = file_get_contents($url, false, $context);
		$response_1 = json_decode($request_1, true);
		
		$request_2 = file_get_contents($response_1["object"]["url"], false, $context);
		$response_2 = json_decode($request_2, true);
		
		$commit_date = new DateTime($response_2["committer"]["date"]);
		
		return $commit_date->format('c'); // ISO 8601
	}
	/**
		* [Summary].
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return [type] [name] [description]
	*/ 
	function is_older_than($time, $age, $unit = "s"){
		
		$conversion_factors = array("s" => 1, "min" => 60, "h" => 3600, "d" => 86400);
		
		$now = strtotime("now");
		$old_time = strtotime($time);
		$diff = round(($now - $old_time) / $conversion_factors[$unit],2);
		return ($diff > $age);
	}
	
	/**
		* [Summary].
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return [type] [name] [description]
	*/ 
	function update_file_from_repo($file, $user, $repo, $folder = "src"){
		
		$local_file_name = "";
		$url = https://raw.githubusercontent.com/"
		$url .= $user . "/" . $repo ."/master/";
		if($folder != ""){
			$url .= $folder . "/";
			$local_file_name .= $folder . "/";
		}
		$url .= $file;
		$local_file_name .= $file;
		copy($url, $url);
	}
	
	/*
		
		
		$diff = $commit_date->diff(new DateTime('now'));
		$diff = $diff->format('c'); //
		
		echo $diff;
	*/
	
	
	
