<?php
	namespace Fridde;
	
	class Dumper extends \MySQLDump
	{
		public $conn_settings;
		public $file_name;
		public $configuration_file = "settings.toml";
		
		public function __construct(){
			$this->setConfiguration();
			$c = $this->conn_settings;
			$this->file_name = $c["db_name"] . ".sql";
			$this->connection = new \mysqli($c["db_host"], $c["db_username"], $c["db_password"], $c["db_name"]);
			parent::__construct($this->connection);
		}
		
		private function setConfiguration()
		{
			
			$file_name = $this->configuration_file;
			$toml_class = "Yosymfony\Toml\Toml";
			if(is_readable($file_name)){
				if(class_exists($toml_class)){
					$parseFunction = $toml_class . "::Parse";
					$local_conn_settings = $parseFunction($file_name);
					$this->conn_settings = $local_conn_settings["Connection_Details"];
				}
				else {
					throw new \Exception("Tried to parse a toml-configuration file without a parser class defined.");
				}
			}
			else {
				throw new \Exception("File <" . $file_name . "> not readable or doesn't exist.");
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