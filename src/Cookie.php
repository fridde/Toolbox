<?php
	
	namespace Fridde;
	
	class Cookie
	{
		
		/**
			* Build a single cookie value from several variables.
			*
			* [Description]
			
			* @param array $array The values to store in a single cookie. 
			*
			* @return [type] [name] [description]
		*/ 
		public function prepare($var_array) {
			
			if (is_array($var_array)) {
				$return_string = http_build_query($array);
			} 
			else {
				throw new \Exception("The parameter given to prepare_cookie() should be given as an array."); 
			}
			return $return_string;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function parse($cookie_string) {
			parse_str($cookie_string, $return_array);
			
			return $return_array;
		}
		
		public static function set($array, $cookie_name = "userSettings")
		{
			$expiration = strtotime("+6 months");
			$host = $_SERVER["host"];
			
			
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