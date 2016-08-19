<?php 
	
	namespace Fridde;
	
	class Mailer extends \PHPMailer\PHPMailer\PHPMailer
	{
		private $configuration;
		public $configuration_file = "settings.toml";
		public $smtp_settings_index = "smtp_settings";
		
		function __construct ()
		{
			parent::__construct();
			$this->setConfiguration();
			$this->initialize();
			$args = func_get_args();
			if(count($args > 0)){
				$c_args = [null, "", "", ""];
				foreach($args as $k => $arg){
					$c_args[$k] = $arg;
				}
				$this->compose($c_args[0],$c_args[1],$c_args[2],$c_args[3]);
			}
		}
		
		private function initialize()
		{
			$def = ["Host", "Username","Password"];
			$args = func_get_args();
			$configuration = (isset($this->configuration[$this->smtp_settings_index]) ? $this->configuration[$this->smtp_settings_index] : null);
			foreach($def as $i => $arg){
				$conf_variable_name = strtolower($arg);
				if(isset($configuration[$conf_variable_name])){
					$this->$arg = $configuration[$conf_variable_name];
				}
				else if(isset($args[$i])){
					$this->$arg = $args[$i];
				}
				else {
					throw new \Exception($arg . " is not set. Mailer won't work without. Check your configurations or use initialize() to set values");
				}
			}
			
			$this->isSMTP();
			if(isset($GLOBALS["debug"]) && $GLOBALS["debug"] == true){
				$this->SMTPDebug = 4;
			} 
			else {
				$this->SMTPDebug = 0;
			}
			$this->Debugoutput = 'html';
			$this->Port = 587;
			$this->SMTPSecure = 'tsl';
			$this->SMTPAuth = true;
			$this->CharSet = 'UTF-8';
			$this->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function compose(){
			// to, message, subject, from
			$args = func_get_args();
			
			// to
			if(is_array($args[0])){
				$this->addAddress($args[0][0], $args[0][1]);
			}
			else if(is_string($args[0]) && strpos($args[0], "@")){
				$this->addAddress($args[0]);
			}
			else {
				throw new \Exception("The recipients adress MUST be set (and valid)!");
			}
			
			// message
			if(isset($args[1]) && is_object($args[1])){
				$this->msgHTML($args[1]->saveHtml());
			}
			else if(isset($args[1]) && is_string($args[1])){
				$message = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head><body>';
				$message .= $args[1];
				$message .= '</body></html>';
				$this->msgHTML($message);
			}
			else{
				$this->msgHTML("");
			}
			// subject
			$this->Subject = (isset($args[2]) ? $args[2] : "");
			
			// from
			if(isset($args[3]) && is_array($args[3])){
				$this->setFrom($args[3][0], $args[3][1]);
			}
			else if(isset($args[3])){
				$this->setFrom($args[3]);
			}
			else if(isset($this->configuration[$this->smtp_settings_index]["from"])){
				$from = explode(";", $this->configuration[$this->smtp_settings_index]["from"]);
				$from_name = (isset($from[1]) ? $from[1] : "");
				$this->setFrom($from[0], $from_name);
			}
			else{
				throw new \Exception("The sender adress is neither predefined nor given!");
			}
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function setConfiguration()
		{
			$file_name = $this->configuration_file;
			$toml_class = "Yosymfony\Toml\Toml";
			if(is_readable($file_name)){
				if(class_exists($toml_class)){
					$parseFunction = $toml_class . "::Parse";
					$this->configuration = $parseFunction($file_name);
				}
				else {
					throw new \Exception("Tried to parse a toml-configuration file without a parser class defined.");
				}
			}
			else {
				throw new \Exception("File <" . $file_name . "> not readable or doesn't exist.");
			}
			return $configuration;
		} 
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function send() 
		{
			$success = parent::send();
			if (!$success) {
				echo "Mailer Error: " . $this->ErrorInfo;
				} else {
				echo "Message sent!";
			}
		}
	}																								