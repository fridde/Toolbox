<?php
	
	namespace Fridde;
	
	class SQL extends \PHPixie\Database
	{
		private $settings;
		public $settings_file = "settings.json";
		public $conn;
		public $query;
		public $table_name;

		
		function __construct ()
		{
			$this->setConfiguration();
			$slice = new \PHPixie\Slice();
			parent::__construct($slice->arrayData($this->settings));
			$this->conn = $this->get("default");								
		}
		
		public function setConfiguration()
		{
			if(is_readable($this->settings_file)){
				$settings = json_decode(file_get_contents($this->settings_file), true);


				$det = $settings["Connection_Details"] ?? false;
				if(!$det) {


					throw new \Exception("No connection details found in settings-file");
				}
			}
			else {
				throw new \Exception("No valid settings.json was found.");				
			}
			$conn_string_default = "mysql:host=" . $det["db_host"] . ";dbname=" . $det["db_name"];
			$conn_string_info = "mysql:host=" . $det["db_host"] . ";dbname=INFORMATION_SCHEMA";
			$settings_default = ['driver' => 'pdo', 'connection' => $conn_string_default,
			'user' => $det["db_username"], 'password' => $det["db_password"]];
			$settings_info = $settings_default;
			$settings_info["connection"] = $conn_string_info;
			$config = ["default" => $settings_default, "info" => $settings_info];
			$this->database = $det["db_name"];
			$def_table = $det["default_table"] ?? false;
			$this->setTable($def_table);
			$this->settings = $config;
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
			return $this->conn->insertId();
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
			if($data == "" || count($data) == 0){
				throw new \Exception("data for insertion was empty");
			}
			$return_data = [[], []];
			
			
			$data_values = array_values($data);
			$data_keys = array_keys($data);
			$first_is_array = is_array($data_values[0]);
			$column_names_as_keys = false;
			if($first_is_array){
				$column_names_as_keys = count(array_filter($data_values[0],"is_numeric",ARRAY_FILTER_USE_KEY)) == 0;
			}
			/* $data is given as batch data
				Example: $data = [["id" => 1, "name" => "Adam", "job" => "zoo keeper"], ["id" => 2, "name" => "Eve", "job" => "gardener"]], where column names are repeated
			*/
			if($first_is_array && $column_names_as_keys){  
				$return_data[0] = array_keys($data_values[0]);
				$return_data[1] = array_map("array_values", $data);
			}
			/* $data is given as batch data, but with the first array containing the indices, and the second array containing the data (without keys)
				Example: $data = [["id", "name", "job"],[[1, "Adam", "zoo keeper"], [2, "Eve", "gardener"]]]
			*/ 
			else if(count($data) == 2 && $first_is_array && is_array($data_values[1][0])){
				$return_data = $data;
			}
			/* $data is given as a single row. 
				Example: $data = ["id" => 1, "name" => "Adam", "job" => "zoo keeper"]
			*/ 
			else if(!$first_is_array){
				$return_data[0] = $data_keys;
				$return_data[1] = [$data_values];
			}
			else {
				throw new \Exception("The insert argument didn't conform to the standards. See documentation for PHPixie/Database's insertQuery->batchData().");
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
			if($this->table_name){
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
		public function getFirst($result, $columns = true)
		{
			$first = reset($result);
			if(count($result) == 0){
				$values = array();
			}
			elseif($columns === true){
				$values = $first;
			}
			elseif($columns === false){
				$values = $result;
			}
			elseif(is_string($columns)){
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
		
		public function getColumnNames($table = null)
		{
			$this->conn = $this->get("info");
			$this->setTable("COLUMNS");
			$this->query = $this->select();
			$this->query->where("TABLE_SCHEMA", $this->database)->and("TABLE_NAME", $table)->execute();
			$results = $this->fetch();
			$this->conn = $this->get("default");
			$results = array_map(function($i){return $i["COLUMN_NAME"];}, $results);
			return $results;
		}
		
	}																																																																															
