<?php 
	
	function activateDebug()
	{
		$GLOBALS["debug"] = true;
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		function print_r2($var){
			echo "<pre>"; print_r($var); echo "</pre>";
		}
	}
	
	function getSettings($file = "settings.json"){
		$json_string = file_get_contents($file);
		if(!$json_string){
			throw new \Exception("The file $file could not be read or found!");
		}
		return json_decode($json_string, true);
	}
	
	/**
		* [Summary].
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return [type] [name] [description]
	*/ 
	
	function updateAllFromRepo()
	{
		$config_array = getSettings();
		if(is_readable("includables.ini")){
			
			$repo_files = parse_ini_file("includables.ini", true);
			$repo_files = $repo_files["repo_files"];
			
			$files_to_update = $config_array["autoload"]["update"] ?? [];
			array_walk($files_to_update, "trim");
			
			foreach($files_to_update as $file_shortcut){
				$file_variables = explode(",", $repo_files[$file_shortcut]);
				$file_variables = array_map("trim", $file_variables);
				updateFileFromRepo($file_variables[3], $file_variables[0], $file_variables[1], $file_variables[2]);
			}
			
		}
	}
	
	/**
		* Quickly include multiple php files.
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return [type] [name] [description]
	*/
	
	function inc($inclusionString, $return = FALSE){
		$inclusionArray = array_map("trim", explode(",", $inclusionString));
		$includables = getIncludables();
		
		foreach($inclusionArray as $inc){
			$ext = pathinfo($inc, PATHINFO_EXTENSION);
			if($ext == "php"){ // e.g. "myCustomFolder/myCustomFile.php"
				include($inc);
			}
			else { 
				if($ext != ""){	// e.g. "jQuery.min.js"
					$error = "The function inc() provided in autoload.php can only be used for php-files. You provided '." . $ext . "'";
				}
				else{ // e.g. "sql", a possible abbreviation given in includables.ini
					if($includables){ // includables.ini does exist and creates no errors
						$is_repo = isset($includables["repo_files"][$inc]);
						$is_php_local = isset($includables["php_local"][$inc]);
						
						if($is_repo){
							$path = $includables["repo_files"][$inc];
							$repo_parts = array_map("trim", explode(",", $path));
							$path = pathinfo($repo_parts[3], PATHINFO_FILENAME) . ".php";
							if(!is_readable($path)){
								updateFileFromRepo($repo_parts[3], $repo_parts[0], $repo_parts[1], $repo_parts[2]);
							}
							include($path);
						}
						else if($is_php_local){
							$path = $includables["php_local"][$inc];
							include($path);
						} 
						else { // e.g. "jquery"
							$error = "The function inc() was provided with a nonexisting abbreviation (among php-files): " . $inc;
						}
					}
					else { 
						$error = "The function inc() was provided with an abbreviation, but no corresponding includables.ini within the same folder.";
					}
				}
			}
			if(isset($error)){
				throw new Exception($error);
			}
		}
	}
	
	function updateFileFromRepo($file, $user, $repo, $folder = "src"){
		
		$local_file_name = "";
		$url = "https://raw.githubusercontent.com/";
		$url .= $user . "/" . $repo ."/master/";
		if($folder != ""){
			$url .= $folder . "/";
			$local_file_name .= $folder . "/";
			if (!file_exists($folder)) {
				mkdir($folder, 0777, true);
			}
		}
		$url .= $file;
		$local_file_name .= $file;
		
		copy($url, $local_file_name);
	}	
	
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
	
	function isYoungerThan($time, $age, $unit = "s"){
		
		$conversion_factors = array("s" => 1, "min" => 60, "h" => 3600, "d" => 86400);
		
		$now = strtotime("now");
		$old_time = strtotime($time);
		$diff = round(($now - $old_time) / $conversion_factors[$unit],2);
		return ($diff < $age);
	}
	
	function getIncludables($file = "includables.ini")
	{
		if(is_readable($file)){
			$inc_ini_array = parse_ini_file("includables.ini", true);
			$check_array = array();
			
			foreach($inc_ini_array as $type => $entries){
				foreach($entries as $abbreviation => $filepath){
					$check_array[$type . ":" . $abbreviation] = "";
					if(isset($check_array[$abbreviation])){
						throw new Exception("includables.ini has duplicate keys! Abbreviation could not be uniquely resolved. Duplicate key: " .  $key);
						return false;
					}
				}
			}
			return $inc_ini_array;
		}
		else { //file not readable
			return false;
		}
	}
