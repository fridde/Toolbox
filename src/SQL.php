<?php
	
	namespace Fridde;
	
	class SQL extends \PHPixie\Database
	{
		private $configuration;
		public $conn;
		public $query;
		public $table_name;
		public $config_file = "config.ini";
		
		function __construct ()
		{
			$args = func_get_args();
			$this->setConfiguration();
			$slice = new \PHPixie\Slice();
			parent::__construct($slice->arrayData($this->configuration));
			$this->conn = $this->get("default");								
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
			$conn_string = "mysql:host=" . $det["db_host"] . ";dbname=" . $det["db_name"];
			$config = ["default" => ['driver' => 'pdo', 'connection' => $conn_string,
			'user' => $det["db_username"], 'password' => $det["db_password"]]];
			$this->setTable($det["default_table"]);
			$this->configuration = $config;
		}
		
		public function setTable($table)
		{
			$this->table_name = $table;
		}
		
		
		
		
		public function select()
		{
			return $this->defineQuery("select");
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [boolean] $as_object_array (optional) If set to true, the array returned contains objects instead of arrays.
			*
			* @return [type] [name] [description]
		*/ 
		public function fetch()
		{
			$args = func_get_args();
			$this->query = (isset($this->query) ? $this->query : $this->select());
			$this->query = $this->query->execute();
			if(count($args) == 0){
				$this->results = array_map(function($obj){return (array) $obj;}, $this->query->asArray());
			}
			else if($args[0] === true){
				$this->results = $this->query->asArray();
			}
			else if(is_string($args[0])){
				$this->results = $this->query->getField($args[0]);
			}
			else if(is_array($args[0])){
				$this->results = $this->query->getFields($args[0]);
			}
			else {
				throw new \Exception("Invalid arguments given to function get()");
			}
			return $this->results;
		}
		
		/**
			* Executes a delete, insert or update-query. Can't be chained.
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function ex()
		{
			$this->query->execute();
			return $this->query;
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function delete()
		{
			return $this->defineQuery("delete");
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function insert($data)
		{
			$data = $this->prepareDataForInsert($data);
			$this->defineQuery("insert");
			$this->query->batchData($data[0], $data[1])->execute();
		}
		/**
			* Converts data to conform to PHPixie\Database's insertQuery->batchData()
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function prepareDataForInsert($data)
		{
			$return_data = [[], []];
			/* $data is given as batch data
				Example: $data = [["id" => 1, "name" => "Adam", "job" => "zoo keeper"], ["id" => 2, "name" => "Eve", "job" => "gardener"]], where column names are repeated
			*/
			if(count($data) > 0 && is_array(reset($data)) && !is_numeric(key(reset($data)))){  
				$return_data[0] = array_keys(reset($data));
				$return_data[1] = array_map("array_values", $data);
			}
			/* $data is given as batch data, but with the first array containing the indices, and the second array containing the data (without keys)
				Example: $data = [["id", "name", "job"],[[1, "Adam", "zoo keeper"], [2, "Eve", "gardener"]]]
			*/ 
			else if(count($data) == 2 && is_array($data[0]) && is_array($data[1]) && isset($data[0][0])){
				$return_data = $data;
			}
			/* $data is given as a single row. 
				Example: $data = ["id" => 1, "name" => "Adam", "job" => "zoo keeper"]
			*/ 
			else if(!is_array(reset($data))){
				$return_data[0] = array_keys($data);
				$return_data[1] = [array_values($data)];
			}
			else {
				throw new \Exception("The insert argument didn't conform to the standards. See documentation for PHPixie\Database's insertQuery->batchData().");
			}
			
			return $return_data;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function count()
		{
			return $this->defineQuery("count");
		}
		
		public function update()
		{
			return $this->defineQuery("update");
		}
		
		
		private function defineQuery($type)
		{
			if(isset($this->table_name)){
				$query_name = $type . "Query";
				$this->query = $this->conn->$query_name()->table($this->table_name);				
			}
			else {
				throw new \Exception("No table name defined for the query.");
			}
			return $this->query;
		}
		
		/**
			* [Summary].
			*
			
			* [Description]
			
			
			* @param array $data The input data. Each row MUST be given as an associative array, i.e. $data = [["id" => "2", "name" => "Adam"],["name" => "Eve"]]
			*
			
			* @return [type] [name] [description]
		*/ 
		public function updateOrInsert($data, $id_column = "id")
		{
			// TODO: fix this so it works
			
			//$data = $this->prepareDataForInsert($data);
			$insert_array = array();
			foreach($data as $row){
				$id_given = isset($row[$id_column]);
				$row_exists = false;
				if($id_given){
					$this->query = $this->select()->where($id_column, $row[$id_column]);
					if(count($this->fetch()) > 0){
						$row_exists = true;
					}
				}
				if($id_given && $row_exists){
					$query = $this->update()->where($id_column, $row[$id_column]);
					foreach(array_keys($row) as $col_name){
						if($col_name != $id_column){
							$query->set($col_name, $row[$col_name]);
						}
					}
					$query->execute();
				}
				else {
					$row[$id_column] = null;
					$insert_array[] = $row;
				}
			}
			if(count($insert_array) > 0){
				$this->insert($insert_array);
			}
		}
		
		/**
			* [Summary].
			*
			* [Description]
			*
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/
		public function getFirst($result, $columns = null)
		{
			$first = $result[0];
			
			if(!isset($columns)){
				$values = $first;
			}
			elseif(is_string($values)){
				$values = $first[$columns];
			}
			elseif(is_array($columns)){
				$values = array_intersect_key($first, array_flip($columns));
			}
			else {
				throw new \Exception("The parameter $columns was given in an invalid form");
			}
			return $values;
		}
		
	}																																																																															
