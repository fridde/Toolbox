<?php
	
	namespace Fridde;
	
	class SQLPixie extends \Pixie\QueryBuilder\QueryBuilderHandler{
		
		private $configuration;
		public $table_name;
		public $table_obj;
		public $config_file = "config.ini";
		
		function __construct ()
		{
			$args = func_get_args();
			if(isset($args[0])){
				$this->table_name = $args[0];
			}
			$this->setConfiguration();
			$connection = new \Pixie\Connection('mysql', $this->configuration);
			parent::__construct($connection);						
		}
		
		public function setConfiguration()
		{
			if(is_readable($this->config_file)){
				$configuration = parse_ini_file($this->config_file, TRUE);
				if(isset($configuration["Connection_Details"])){
					$det = $configuration["Connection_Details"];
				}
				else {
					throw new \Exception("No connection details found in config-file");
				}
			}
			else {
				throw new \Exception("No valid config.ini was found.");				
			}
			$config = ['driver' => 'mysql', 'host' => $det["db_host"], 'database' => $det["db_name"],
			'username' => $det["db_username"], 'password' => $det["db_password"], 'charset'   => 'utf8',
			'collation' => 'utf8_unicode_ci'];
			$this->table_name = $det["default_table"];
			$this->configuration = $config;
		}
		
		public function getty()
		{
			return $this->table($this->table_name)->get();
			
		}
		/*
		public function __call($method, $args) {
			
			return $this->table->$method($args);
		}
		*/
		/*
			select
			delete
			count
			insert
			update
			
			
		*/
		
		/*
			public function select()
			{
			$query
			}
		*/
	}																																							