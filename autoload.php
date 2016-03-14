<?php 
	
	if(is_readable("config.ini") && is_readable("includables.ini")){
		$config_array = parse_ini_file("config.ini", true);
		$repo_files = parse_ini_file("includables.ini", true);
		$repo_files = $repo_files["repo_files"];
		
		if(isset($config_array["autoload"]["updatable"]) && isset($config_array["autoload"]["update"])){
			$files_to_update = array_map("trim", explode(",", $config_array["autoload"]["update"]));
			
			foreach($files_to_update as $file_shortcut){
				$file_variables = array_map("trim", explode(",", $repo_files[$file_shortcut]));
				update_file_from_repo($file_variables[3], $file_variables[0], $file_variables[1], $file_variables[2]);
			}
		}
	}
	
	spl_autoload_register('friddes_autoloader');
	
	/*
		Supporting functions for the autoloader-logic
	*/
	
	function friddes_autoloader($class){
		
		// project-specific namespace prefix
		$prefix = 'Fridde\\';
		
		// base directory for the namespace prefix
		$base_dir = __DIR__ . '\src\\';
		
		// does the class use the namespace prefix?
		$len = strlen($prefix);
		if (strncmp($prefix, $class, $len) !== 0) {
			// no, move to the next registered autoloader
			return;
		}
		
		// get the relative class name
		$relative_class = substr($class, $len);
		
		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $base_dir . $relative_class . '.class.php';
		
		
		// if the file exists, require it
		if (file_exists($file)) {
			require $file;
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
	function inc($inclusionString, $default_folder = "src/", $return = FALSE){
		$inclusionArray = array_map("trim", explode(",", $inclusionString));
		$includables_with_types = get_includables_with_types();
		
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
					if($includables_with_types != false){ // includables.ini does exist and creates no errors
						if(!isset($includables_with_types[$inc])){
							$error = "The function inc() was provided with a nonexisting abbreviation: " . $inc;
						}
						else {
							$type = $includables_with_types[$inc]["type"];
							$path = $includables_with_types[$inc]["path"];
							if($type == "repo_files"){
								$repo_parts = array_map("trim", explode(",", $path));
								$path = $default_folder . pathinfo($repo_parts[3], PATHINFO_FILENAME) . ".php";
								if(!is_readable($path)){
									update_file_from_repo($repo_parts[3], $repo_parts[0], $repo_parts[1], $repo_parts[2]);
								}
								include($path);
							}
							else if($type == "php_local"){
								include($default_folder . $path);
							} 
							else { // e.g. "jquery"
								$error = "The function inc() was provided with a valid abbreviation, but invalid filetype. Only php-files are valid. You provided .". $type;
							}
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
	
	
	function update_file_from_repo($file, $user, $repo, $folder = "src"){
		
		$local_file_name = "";
		$url = "https://raw.githubusercontent.com/";
		$url .= $user . "/" . $repo ."/master/";
		if($folder != ""){
			$url .= $folder . "/";
			$local_file_name .= $folder . "/";
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
	function is_younger_than($time, $age, $unit = "s"){
		
		$conversion_factors = array("s" => 1, "min" => 60, "h" => 3600, "d" => 86400);
		
		$now = strtotime("now");
		$old_time = strtotime($time);
		$diff = round(($now - $old_time) / $conversion_factors[$unit],2);
		return ($diff < $age);
	}
	
	function get_includables_with_types($file = "includables.ini"){
		if(is_readable("includables.ini")){
			$inc_ini_array = parse_ini_file("includables.ini", true);
			$return_array = array();
			
			foreach($inc_ini_array as $type => $entries){
				foreach($entries as $abbreviation => $filepath){
					if(!isset($return_array[$abbreviation])){
						
						$return_array[$abbreviation]["type"] = $type;
						$return_array[$abbreviation]["path"] = $filepath;
					}
					else {
						throw new Exception("includables.ini has duplicate keys! Abbreviation could not be uniquely resolved. Duplicate key: " .  $abbreviation);
						return false;
					}
				}
			}
			return $return_array;
		}
		else {
			return false;
		}
	}
		