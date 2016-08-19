<?php 
	
	$autoload_path = "vendor/autoload.php";
	if(is_readable($autoload_path)){
		include($autoload_path);
	}
	
	
	function activateDebug()
	{
		$GLOBALS["debug"] = true;
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		
		function print_r2($var){
			echo "<pre>"; print_r($var); echo "</pre>";
		}
	}
	
	function getSettings($file = "settings.toml"){
		
		$settings = false;
		$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
		if (!is_readable($file)){
			throw new \Exception("The file " . $file . " is not readable or doesn't exist.");
		}
		$toml_class = "Yosymfony\Toml\Toml";
		if ($ext == "toml"){
			if (class_exists($toml_class)){
				$parseFunction = $toml_class . "::Parse";
				$settings = $parseFunction($file);
			}
			else {
				throw new \Exception("Tried to parse a toml-configuration file without a parser class defined.");
			}
		}
		elseif ($ext == "json"){
			$json_string = file_get_contents($file);
			if (!$json_string){
				throw new \Exception("The file $file could not be read or found!");
			}
			$settings = json_decode($json_string, true);
		}
		elseif ($ext == "ini"){
			$settings = parse_ini_file($file, true);
		}
		else {
			throw new \Exception("The function getSettings has no implementation for the file extension <" . $ext . ">");
		}
		
		return $settings; 
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
		$files_to_update = $config_array["autoload"]["update"] ?? [];
		if (is_readable("includables.toml") && $files_to_update){
			
			$repos = getSettings("includables.toml");
			$repos = $repos["repos"];
			
			foreach($files_to_update as $file_shortcut){				
				foreach($repos as $repo_name => $repo_array){
					$path = $repo_array["paths"][$file_shortcut] ?? null;
					if($path){
						$user = $repo_array["user"];
						updateFileFromRepo($path, $user, $repo_name);
					}
				}				
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
	
	function inc($inclusionString, $return = false){
		$inclusionArray = array_map("trim", explode(",", $inclusionString));
		$includables = getIncludables();
		
		foreach($inclusionArray as $inc){
			$ext = pathinfo($inc, PATHINFO_EXTENSION);
			if ($ext == "php"){ // e.g. "myCustomFolder/myCustomFile.php"
				include($inc);
			}
			else { 
				if ($ext != ""){	// e.g. "jQuery.min.js"
					$error = "The function inc() provided in autoload.php can only be used for php-files. You provided '." . $ext . "'";
				}
				else { // e.g. "sql", a possible abbreviation given in includables.toml
					if ($includables){ // includables.toml does exist and creates no errors
						$repo_info = identifyRepo($includables, $inc);
						//$is_repo = isset($includables["repos"][$inc]);
						$is_php_local = isset($includables["php_local"][$inc]);
						
						if ($repo_info){
							list($repo_user, $repo_name) = $repo_info;
							$path = $includables["repos"][$repo_name][$inc];
							if (!is_readable($path)){
								// file, user, repo
								updateFileFromRepo($path, $repo_user, $repo_name);
							}
							include($path);
						}
						else if ($is_php_local){
							$path = $includables["php_local"][$inc];
							include($path);
						} 
						else { // e.g. "jquery"
							$error = "The function inc() was provided with a nonexisting abbreviation (among php-files): " . $inc;
						}
					}
				}
			}
			if (isset($error)){
				throw new Exception($error);
			}
		}
	}
	
	/* returns an array containing user and repo name if defined as a repo file in includables.toml
		returns false otherwise
	*/
	function identifyRepo($includables, $abbreviation)
	{
		foreach($includables["repos"] as $repo_name => $repo_array){
			if(isset($repo_array["paths"][$abbreviation])){
				$repo_info = [$repo_array["user"], $repo_name];
				return $repo_info;
			}
			else {
				return false;
			}
		}
	}
	
	function updateFileFromRepo($file, $user, $repo, $folder = "src"){
		
		$local_file_name = "";
		$url = "https://raw.githubusercontent.com/";
		$url .= $user . "/" . $repo ."/master/";
		if ($folder != ""){
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
		
		$conversion_factors = ["s" => 1, "min" => 60, "h" => 3600, "d" => 86400];
		
		$now = strtotime("now");
		$old_time = strtotime($time);
		$diff = round(($now - $old_time) / $conversion_factors[$unit],2);
		return ($diff < $age);
	}
	
	function getIncludables($file = "includables.toml")
	{
		$includables = getSettings($file);
		$check_array = [];
		
		// doublecheck to ensure the abbreviation is uniquely defined 
		foreach($includables as $type => $entries){
			foreach($entries as $abbreviation => $filepath){
				$check_array[$type . ":" . $abbreviation] = "";
				if (isset($check_array[$abbreviation])){
					throw new Exception("includables.ini has duplicate keys! Abbreviation could not be uniquely resolved. Duplicate key: " .  $key);
					return false;
				}
			}
		}
		return $includables;
	}
