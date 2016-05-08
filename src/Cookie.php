<?php
	
	namespace Fridde;
	
	class Cookie
	{
		
		/**
		* Build a single cookie value from several variables.
		*
		* [Description]
		
		* @param array $var_array The values to store in a single cookie. The total length of all letters should not exceed 
		*
		* @return [type] [name] [description]
		*/ 
		function prepare_cookie($var_array) {
			
			if (is_array($var_array)) {
				foreach ($var_array as $index => $data) {
					$out.= ($data!="") ? $index."=".$data."|" : "";
				}
			} 
			else {
				throw new \Exception("The parameter given to prepare_cookie() should be given as an array."); 
				}
			return rtrim($out,"|");
		}
		
		/**
		* [Summary].
		*
		* [Description]
		
		* @param [Type] $[Name] [Argument description]
		*
		* @return [type] [name] [description]
		*/ 
		function break_cookie ($cookie_string) {
			$array=explode("|",$cookie_string);
			foreach ($array as $i=>$stuff) {
				$stuff=explode("=",$stuff);
				$array[$stuff[0]]=$stuff[1];
				unset($array[$i]);
			}
			return $array;
		}
		
		//this assumes that the user has just logged in
		/****Creating an identification string****/
		
		$username; //normally the username would be known after login
		
		//create a digest from two random values and the username
		$digest = sha1(strval(rand(0,microtime(true)) + $username + strval(microtime(true)); 
		
		//save to database (assuming connection is already made)
		mysql_query('UPDATE users SET reloginDigest="'.$digest.'" WHERE username="'.$username.'"');  
		
		//set the cookie
		setcookie( 'reloginID', $digest, time()+60*60*24*7,'/', 'test.example.com', false, true); 
		
		
		//this assumes that the user is logged out and cookie is set
		/****Verifying users through the cookie****/
		
		$digest = $_COOKIE['reloginID'];
		$digest = mysql_real_escape_string($digest); //filter any malicious content
		
		//check database for digest
		$result = mysql_query('SELECT username FROM users WHERE reloginDigest="'.$digest.'"');
		//check if a digest was found
		if(mysql_num_rows($result) == 1){
			$userdata  = mysql_fetch_object($result);
			$username = $userdata->username;
			
			//here you should set a new digest for the next relogin using the above code!
			
			echo 'You have successfully logged in, '.$username;
			
			} else{
			//digest didn't exist (or more of the same digests were found, but that's not going to happen)
			echo "failed to login!";
		}
	}
?>