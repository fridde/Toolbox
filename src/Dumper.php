<?php
	namespace Fridde;
	
	class Dumper extends \MySQLDump
	{
		public $conn_settings;
		public $file_name;
		
		public function __construct($config_file = "settings.json"){
			$this->setConfiguration($config_file);
			$c = $this->conn_settings;
			$this->file_name = $c["db_name"] . ".sql";
			$this->connection = new \mysqli($c["db_host"], $c["db_username"], $c["db_password"], $c["db_name"]);
			parent::__construct($this->connection);
		}
		
		private function setConfiguration($config_file)
		{
			if(is_readable($config_file)){
				$configuration = json_decode(file_get_contents($config_file), true);
				if(isset($configuration["Connection_Details"])){
					$this->conn_settings = $configuration["Connection_Details"];
				}
				else {
					throw new \Exception("No connection details found in config-file");
				}
			}
			else {
				throw new \Exception("No valid ". $config_file . " was found.");				
			}
		}
		
		public function export()
		{
			$this->save("temp/" . $this->file_name);
		}
		
		public function import()
		{
			$sql_text = file_get_contents("temp/" . $this->file_name);
			$result = $this->connection->multi_query($sql_text);
			var_dump($result);
		}
		
	}					