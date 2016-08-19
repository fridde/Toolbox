<?php 
	
	namespace Fridde;
	
	class SMS
	{
		public $settings;
		public $to;
		public $message;
		public $api = "smsgateway";
		public $curl;
		public $config_file = "config.ini";
		public $response;
		public $error;
		
		function __construct()
		{
			$default_params = ["message", "to", "from", "api"];
			$fn_params = array_pad(func_get_args(), count($default_params), null);
			$this->logg($fn_params);
			$conf_params = array();
			foreach($fn_params as $key=>$param){
				$conf_params[$default_params[$key]] = $param;
			}
			$this->setConfiguration($conf_params);
		}
		
		public function setConfiguration($conf)
		{
			/* TODO: Refactor to match the new system using toml-files
			
			
			if(is_readable($this->config_file)){
				$ini = parse_ini_file($this->config_file, TRUE);
				$this->api = (isset($conf["api"])) ? $conf["api"] : $this->api ;
				if(isset($ini["sms_settings_" . $this->api])){
					$ini = $ini["sms_settings_" . $this->api];
					foreach($ini as $setting_name => $setting_value){
						$this->settings[$this->api][$setting_name] = $setting_value;
					}
					$this->message = $conf["message"];
					$this->to = $conf["to"];
				}
				else {
					throw new \Exception("No sms_settings found in ". $this->config_file);
				}
			}
			else {
				throw new \Exception("No valid " . $this->config_file . " was found.");				
			}
			*/
			throw new \Exception("TODO: Refactor setConfiguration first!");
		}
		
		public function send()
		{
			$url = $this->getUrl("send");
			$query_fields = $this->prepareQueryFields();
			$query = http_build_query($query_fields);
			$headers = $this->prepareHeaders();
			
			$this->logg($query_fields);
			
			$curl_options = ["url" => $url, "post" => 1, "postfields" => $query, "httpheader" => $headers];
			
			$this->curl = curl_init();
			$this->setCurlOptions("send", $curl_options);		
			
			$this->response = curl_exec($this->curl);
			curl_close($this->curl);
			
			return $this->response;
		}
		
		
		public function prepareQueryFields()
		{
			$type = @func_get_arg(0);
			$type = ($type) ? $this->api . "_" . $type : $this->api . "_send";
			$settings = $this->settings[$this->api];
			
			switch($type){
				case "46elks_send":
				$q["from"] = $this->standardizeMobNr($settings["from"]);
				$q["to"] = $this->standardizeMobNr($this->to);
				$q["message"] = $this->message;
				break;
				
				case "smsgateway_send":
				$q["email"] = $settings["email"];
				$q["password"] = $settings["password"];
				$q["device"] = $settings["device_id"];
				$q["number"] = $this->standardizeMobNr($this->to);
				$q["message"] = $this->message;
				$q["send_at"] = strtotime($settings["send_at"]);
				$q["expires_at"] = strtotime($settings["expires_at"]);
			break;
			
			default:
			throw new \Exception("No API endpoint url defined for " . $type);
		}
		return $q;
	}
	
	public function prepareHeaders()
	{
		if(!isset($this->api)){
			throw new \Exception("Headers can't be prepared if no API is defined.");
		}
		$h = array(); // headers_array
		$api = $this->api;
		
		switch($api){
			
			case "46elks":
			$h[] = "Authorization: Basic " . base64_encode($this->username . ":" . $this->password);
			
			break;
		}
		$h[] = "Content-type: application/x-www-form-urlencoded";
		
		return $h;
	}
	
	public function getUrl()
	{
		$type = @func_get_arg(0);
		$type = ($type) ? $this->api . "_" . $type : $this->api . "_send";
		$url = $this->settings[$this->api]["url"];
		
		switch($type){
			case "46elks_send":
			$url = $this->settings[$this->api]["url"];
			$url .= "SMS";
			break;
			
			case "smsgateway_send":
			$url = $this->settings[$this->api]["url"];
			$url .= "messages/send";
			break;
			
			default: 
			throw new \Exception("No API endpoint url defined for " . $type);
		}
		
		return $url;
	}
	
	public function setCurlOptions($type = "send", $options = [])
	{
		$standard_curl_options = [ "post" => 1, "returntransfer" => 1, "header" => false, "ssl_verifypeer" => false, "timeout" => 10];
		$curl_options = array_merge($standard_curl_options, $options);
		
		foreach($curl_options as $option_name => $option_value){
			curl_setopt($this->curl, constant(strtoupper("curlopt_" . $option_name)), $option_value);
		}
		return $this;
	}
	
	public function standardizeMobNr($number){
		
		$nr = $number;
		$nr = preg_replace("/[^0-9]/", "", $nr);
		$trim_characters = ["0", "4", "6"]; // we need to trim from left to right order
		foreach($trim_characters as $char){
			$nr = ltrim($nr, $char);
		}
		if(in_array(substr($nr, 0, 2), ["70", "72", "73", "76"])){
			$nr = "+46" . $nr;
		}
		else if($nr != ""){
			$this->error = 'The number "' . $number . '" is probably not a swedish mobile number.';
			$nr = false;
		}
		return $nr;
	}
	
	public function logg($data, $infoText = "", $filename = "toolbox.log")
	{
		$debug_info = array_reverse(debug_backtrace());
		$chainFunctions = function($p,$n){
			$class = (isset($n["class"]) ? "(". $n["class"] . ")" : "");
			$p.='->' . $class . $n['function'] . ":" . $n["line"];
			return $p;
		};
		$calling_functions = ltrim(array_reduce($debug_info, $chainFunctions), "->");
		$file = pathinfo(reset($debug_info)["file"], PATHINFO_BASENAME);
		
		$string = "\n\n####\n--------------------------------\n";
		$string .= date("Y-m-d H:i:s");
		$string .= ($infoText != "") ? "\n" . $infoText : "" ;
		$string .= "\n--------------------------------\n";
		
		if (is_string($data)) {
			$string .= $data;
		} 
		else {
			$string .= print_r($data, true);
		}
		$string .= "\n----------------------------\n";
		$string .= "Calling stack: " . $calling_functions . "\n"; 
		$string .= $file . " produced this log entry";
		
		file_put_contents($filename, $string, FILE_APPEND);
	}
	
}


