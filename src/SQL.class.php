<?php
	
	namespace Fridde;
	
	class SQL{
		
		/**
			* SUMMARY OF clean_sql
			*
			* DESCRIPTION
			*
			* @param TYPE ($string) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  clean_sql($string)
		{
			
			$search = array("\'");
			$replace = array("\'\'");
			
			$string = str_replace($search, $replace, $string);
			
			return $string;
		}
		/**
			* SUMMARY OF transpose
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function  transpose($array)
		{
			array_unshift($array, null);
			return call_user_func_array('array_map', $array);
		}
		/**
			* SUMMARY OF sql_remove_duplicates($sqlTable, $ignoreArray = array
			*
			* DESCRIPTION
			*
			* @param TYPE ("id")) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		
		public static function  sql_remove_duplicates($sqlTable, $ignoreArray = array("id"))
		{
			
			$rowsBefore = ORM::for_table($sqlTable) -> count();
			
			$headers = Helper::sql_get_columnNames($sqlTable);
			$headers = array_diff($headers, $ignoreArray);
			echo print_r($headers) . "<br><br>";
			$query = " ALTER TABLE " . $sqlTable . " ADD COLUMN tmp_col TEXT(300); ";
			$query .= ' UPDATE ' . $sqlTable . ' SET tmp_col = concat_ws(",", ' . implode(", ", $headers) . ' ); ';
			$query .= "CREATE TABLE tmp LIKE " . $sqlTable . " ; ";
			$query .= " ALTER TABLE tmp ADD UNIQUE (tmp_col(300)) ; ";
			$query .= "INSERT IGNORE INTO tmp SELECT * FROM " . $sqlTable . " ; ";
			$query .= "RENAME TABLE " . $sqlTable . " TO deleteme, tmp TO " . $sqlTable . " ; ";
			$query .= " ALTER TABLE " . $sqlTable . " DROP COLUMN tmp_col ; ";
			$query .= "DROP TABLE deleteme ;";
			ORM::for_table($sqlTable) -> raw_execute($query);
			
			$rowsAfter = ORM::for_table($sqlTable) -> count();
			
			return $rowsBefore - $rowsAfter;
		}
		*/
		/**
			* SUMMARY OF sql_convert_dates
			*
			* DESCRIPTION
			*
			* @param TYPE ($sourceId, $tableName) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		
		public static function  sql_convert_dates($sourceId, $tableName)
		{
			$ORM = ORM::for_table($tableName) -> where("Source_Id", $sourceId) -> find_many();
			
			$dateHeaders = array(
			"Year",
			"Date",
			"Actual_Application",
			"Expected_Approval",
			"Regulatory_Approval",
			"Construction_Start",
			"First_Steam",
			"Production_Start",
			"Month",
			"Startup_Date"
			);
			$quartalHeaders = array(
			"Actual_Application",
			"Expected_Approval",
			"Regulatory_Approval",
			"Construction_Start",
			"First_Steam",
			"Production_Start"
			);
			
			// Year (Decimal or integer)
			// Date (Decimal or integer)
			// Actual_Application (Q4 2012)
			// Expected_Approval (Q4 2015 or integer)
			// Regulatory_Approval (Q4 2015 or integer)
			// Construction_Start (Q4 2015 or integer)
			// First_Steam (Q4 2015 or integer)
			// Production_Start (Q4 2015 or integer)
			// Month (November, nov, Nov)
			// Startup_Date (2013-07-01)
			foreach ($ORM as $row) {
				$existsArray = array();
				foreach ($dateHeaders as $header) {
					$exists = $row -> $header == NULL || $row -> $header == "" || $row -> $header == "NULL";
					$existsArray[$header] = !($exists);
				}
				$existsMonthYear = var_export($existsArray["Month"], TRUE) . var_export($existsArray["Year"], TRUE);
				
				switch ($existsMonthYear) {
					case 'truetrue' :
					$date = array(
					"Year" => $row -> Year,
					"Month" => $row -> Month,
					"Day" => "01"
					);
					$newDate = Helper::convert_date($date, "YMD");
					break;
					
					case 'falsetrue' :
					$date = $row -> Year;
					if (fmod(floatval($date), 1.0) == 0) {
						$newDate = Helper::convert_date($date, "int");
					}
					else {
						$newDate = Helper::convert_date($date, "dec");
					}
					break;
					
					case "falsefalse" :
					$date = $row -> Date;
					
					$date = explode("-", $date);
					if (count($date) == 3) {
						
						$date = array(
						"Year" => $date[0],
						"Month" => $date[1],
						"Day" => $date[2]
						);
						$newDate = Helper::convert_date($date, "YMD");
					}
					if (count($date) == 2) {
						$date = array(
						"Year" => $date[0],
						"Month" => $row -> Month,
						"Day" => "01"
						);
						$newDate = Helper::convert_date($date, "YMD");
					}
					if (count($date) == 1) {
						$date = $date[0];
						if (strlen($date) == 0) {
							$newDate = NULL;
						}
						else {
							if (fmod($date, 1) == 0) {
								$newDate = Helper::convert_date($date, "int");
							}
							else {
								$newDate = Helper::convert_date($date, "dec");
							}
						}
					}
					
					break;
				}
				$row -> Date = $newDate;
				foreach ($quartalHeaders as $qHeader) {
					if ($existsArray[$qHeader]) {
						$date = $row -> $qHeader;
						
						if (substr($date, 0, 1) == "Q") {
							$newDate = Helper::convert_date($date, "Q");
						}
						else {
							$newDate = Helper::convert_date($date, "int");
						}
						$row -> $qHeader = $newDate;
					}
				}
				$row -> Month = NULL;
				$row -> Year = NULL;
				
				$row -> save();
			}
			
		}
		*/
		/**
			* SUMMARY OF sql_add_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $columns) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		
		public static function  sql_add_columns($sqlTable, $columns)
		{
			$dummyRow = ORM::for_table($sqlTable) -> create();
			$dummyRow -> save();
			$dummyRowId = $dummyRow -> id();
			$sql_table = ORM::for_table($sqlTable) -> find_one() -> as_array();
			
			foreach ($columns as $column) {
				if (!(array_key_exists($column, $sql_table))) {
					$query = "ALTER TABLE " . $sqlTable . " ADD COLUMN " . $column . " TINYTEXT";
					ORM::for_table($sqlTable) -> raw_execute($query);
				}
			}
			
			$dummyRow = ORM::for_table($sqlTable) -> find_one($dummyRowId);
			$dummyRow -> delete();
			
		}
		*/
		
		/*
			
		*/
		/**
			* SUMMARY OF sql_select_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $columns, $alwaysAsArray = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function  sql_select_columns($array, $columns, $alwaysAsArray = FALSE)
		{
			if (gettype($columns) != "array") {
				$columns = array($columns);
			}
			$newArray = array();
			
			foreach ($array as $rowKey => $row) {
				if (gettype($row) != "array") {
					$row = array($rowKey => $row);
				}
				
				foreach ($row as $colKey => $cell) {
					if (in_array($colKey, $columns)) {
						if (count($columns) > 1 || $alwaysAsArray) {
							$newArray[$rowKey][$colKey] = $cell;
						}
						else {
							$newArray[$rowKey] = $cell;
						}
					}
				}
			}
			return $newArray;
		}
		
		/*
			
		*/
		/**
			* SUMMARY OF sql_get_columnNames
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		
		public static function  sql_get_columnNames($sqlTable)
		{
			$dummyRow = ORM::for_table($sqlTable) -> create();
			$dummyRow -> save();
			$dummyRowId = $dummyRow -> id();
			$sqlColumns = array_keys(ORM::for_table($sqlTable) -> find_one() -> as_array());
			
			$dummyRow = ORM::for_table($sqlTable) -> find_one($dummyRowId);
			$dummyRow -> delete();
			
			return $sqlColumns;
		}
		*/
		
		/**
			* SUMMARY OF sql_insert_array
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $sqlTable, $maxString = 5000, $updateLog = TRUE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		
		
		public static function  sql_insert_array($array, $sqlTable, $maxString = 5000, $updateLog = TRUE)
		{
			//echo print_r($array) .  "<br>";
			if (count($array) < 1) {
				echo "Empty array given! <br>";
				return;
			}
			
			$headers = array_keys(reset($array));
			
			Helper::sql_add_columns($sqlTable, $headers);
			
			$queryStart = "INSERT INTO " . $sqlTable . " (" . implode(" , ", $headers) . ") VALUES ";
			$query = "";
			
			foreach ($array as $rowKey => $row) {
				$newRow = array();
				foreach ($row as $colKey => $cell) {
					switch ($colKey) {
						case "id" :
						// we let MySQL decide the new id
						$newRow[$colKey] = "";
						break;
						default :
						$newRow[$colKey] = $cell;
					}
				}
				$newRow = "('" . implode("' , '", $newRow) . "'),";
				$query .= $newRow;
				
				if (strlen($query) > $maxString) {
					$totalQuery = $queryStart . rtrim($query, ",") . ";";
					ORM::for_table($sqlTable) -> raw_execute($totalQuery);
					$query = "";
				}
			}
			//add the rest
			if (strlen($query) > 2) {
				$totalQuery = $queryStart . rtrim($query, ",") . ";";
				
				ORM::for_table($sqlTable) -> raw_execute($totalQuery);
			}
			if ($updateLog) {
				if (ORM::for_table('osdb_logs') -> count() > 50000) {
					ORM::for_table("osdb_logs") -> raw_execute("TRUNCATE TABLE osdb_logs;");
				}
				date_default_timezone_set("UTC");
				$newEntry = ORM::for_table("osdb_logs") -> create();
				$newEntry -> Table = $sqlTable;
				$newEntry -> Timestamp = date("Y-m-d\TH:i:s", time());
				$newEntry -> save();
			}
		}
		*/
		/*
			* Contains custom SQL-function s that use \PDO
			* assumes that a config.ini -file exists matching the template given in this folder
			
		*/
		/**
			* SUMMARY OF get_conn_details
			*
			* DESCRIPTION
			*
			* @param TYPE ($ini_file = "config.ini") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  get_conn_details($ini_file = "config.ini")
		{
			
			if(file_exists($ini_file)){
				$ini_array = parse_ini_file($ini_file, TRUE);
				$cD = $ini_array["Connection_Details"];
				$connectionDetails = array($cD["db_host"], $cD["db_name"], $cD["db_username"], $cD["db_password"]);
				return $connectionDetails;
			}
			else {
				$errorText = "No valid config.ini was found. Please see https://github.com/fridde/friddes_php_functions for a template.";
				echo $errorText;
				return $errorText;
			}
		}
		/**
			* SUMMARY OF sql_connect
			*
			* DESCRIPTION
			*
			* @param TYPE ($nonStandardDB = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		
		public static function  sql_connect($nonStandardDB = FALSE) {
			/* expects an array that contains the following values (in that order)
				* array($db_host, $db_name, db_username, $db_password);    *
			*/
			
			if ($nonStandardDB == FALSE) {
				
				$connectionDetails = get_conn_details();
				$cd = $connectionDetails; // abbreviation
				} else {
				$cd = $nonStandardDB;
			}
			
			try {
				$conn = new \PDO("mysql:host=$cd[0];dbname=$cd[1];", $cd[2], $cd[3]);
				$conn->exec("SET NAMES utf8");
				$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				$conn->setFetchMode(\PDO::FETCH_ASSOC)
				} catch (\PDOException $e) {
				echo $e->getMessage();
			}
			return $conn;
		}
		/**
			* SUMMARY OF sql_get_highest_id
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $idHeader = "id", $debug = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_get_highest_id($sqlTable, $idHeader = "id", $debug = FALSE) {
			
			$query = "SELECT " . $idHeader . " FROM " . $sqlTable;
			$query .= " ORDER BY " . $idHeader . " DESC LIMIT 1;";
			
			$conn = sql_connect();
			if($debug){echo $query;}
			$stmt = $conn->prepare($query);
			$stmt->execute();
			
			// set the resulting array to associative
			
			$resultArray = $stmt->fetchAll();
			$conn = null;
			return $resultArray[0][$idHeader];
		}
		/**
			* SUMMARY OF sql_insert_rows
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $array, $forceId = FALSE, $maxString = 5000, $debug = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_insert_rows($sqlTable, $array, $forceId = FALSE, $maxString = 5000, $debug = FALSE) {
			
			// sometimes only a single row is given that is not contained in an array
			$first_element = reset($array);
			if (is_string($first_element)) {
				$array = array($array);
			}
			
			$sqlHeaders = sql_get_headers($sqlTable);
			$conn = sql_connect();
			//echo print_r($array);
			if (count($array) < 1) {
				echo "Empty array given! <br>";
				return;
			}
			
			$queryStart = "INSERT INTO " . $sqlTable . " (" . implode(" , ", $sqlHeaders) . ") VALUES ";
			$query = "";
			
			foreach ($array as $row) {
				
				$newRow = array();
				foreach ($sqlHeaders as $sqlHeader) {
					switch ($sqlHeader) {
						case "id" :
						if ($forceId != FALSE) {
							$newRow[] = $row[$sqlHeader];
							} else {
							// we let MySQL decide the new id
							$newRow[] = "";
						}
						break;
						default :
						if (isset($row[$sqlHeader])) {
							$newRow[] = addslashes($row[$sqlHeader]);
							} else {
							$newRow[] = "";
						}
					}
				}
				$newRow = "('" . implode("' , '", $newRow) . "'),";
				
				$query .= $newRow;
				
				if (strlen($query) > $maxString) {
					$totalQuery = $queryStart . rtrim($query, ",") . ";";
					if($debug){echo $totalQuery;}
					$conn->exec($totalQuery);
					
					$query = "";
				}
			}
			//add the rest
			if (strlen($query) > 2) {
				$totalQuery = $queryStart . rtrim($query, ",") . ";";
				if($debug){echo $totalQuery;}
				$conn->exec($totalQuery);
			}
			$conn = null;
			
		}
		/**
			* SUMMARY OF sql_select
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $criteria = "", $headers = "all", $debug = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_select($sqlTable, $criteria = "", $headers = "all", $debug = FALSE) {
			
			/* criteria can be given in 3 different forms
				* 1. as a string written in SQL
				* 2. as a single array with only one key and one value, written as array("header" => "value").
				*          That array will be translated to "WHERE header = 'value'
				* 3. As an array with two elements: the first element is a string, either "AND" or "OR"
				*          and the second element is an array where all keys are headers and all values are
				*          the corresponding wanted values.
				*          That recent subarray is the glued together with the string.
				*          Example: array("AND", array("header1" => "value1", "header2" => "value2", etc)) becomes
				*          "WHERE header1 = 'value1' AND header2 = "value2" AND ...
			*  */
			
			$equalSign = "=";
			$notEqualSign = "<>";
			$query = "SELECT ";
			if ($headers === "all") {
				$query .= "* ";
				} else {
				$query .= implode(", ", $headers);
				$query .= " ";
			}
			$query .= "FROM " . $sqlTable;
			
			if (is_array($criteria)) {
				if (count($criteria) == 2) {
					if (is_array($criteria[1])) {
						$criteriaType = "string_and_array";
						} else {
						$criteriaType = "two_strings";
					}
					} else {
					$criteriaType = "atomic_array";
				}
				} else {
				$criteriaType = "string";
			}
			
			switch ($criteriaType) {
				
				case "string":
				// i.e. "WHERE City = Berlin"
				$criteriaString = $criteria;
				break;
				
				case "string_and_array":
				// i.e. array("AND", array("City" => "Berlin", "Job" => "Carpenter"))
				$criteriaString = " WHERE ";
				$glue = strtoupper($criteria[0]) . " ";
				$criteriaArray = array();
				foreach ($criteria[1] as $left => $rightArray) {
					if(!is_array($rightArray)){
						$rightArray = array($rightArray);
					}
					foreach($rightArray as $right){
						if(strtolower(substr($right, 0, 4)) == "not:"){
							$right = substr($right, 4);
							$criteriaArray[] = '`' . $left . "` " . $notEqualSign . " '" . $right . "' ";
						}
						else {
							$criteriaArray[] = '`' . $left . "` " . $equalSign . " '" . $right . "' ";
						}
					}
				}
				$criteriaString .= implode($glue, $criteriaArray);
				break;
				
				case "two_strings":
				$criteriaString = " WHERE ";
				$left = $criteria[0];
				$right = $criteria[1];
				if(strtolower(substr($right, 0, 4)) == "not:"){
					$right = substr($right, 4);
					$criteriaString .= '`' . $left . "` " . $notEqualSign . " '" . $right . "' ";
				}
				else {
					$criteriaString .= '`' . $left . "` " . $equalSign . " '" . $right . "' ";
				}
				break;
				
				case "atomic_array":
				$criteriaString = " WHERE ";
				$left = array_keys($criteria);
				$left = $left[0];
				$right = $criteria[$left];
				if(strtolower(substr($right, 0, 4)) == "not:"){
					$right = substr($right, 4);
					$criteriaString .= '`' . $left . "` " . $notEqualSign . " '" . $right . "' ";
				}
				else {
					$criteriaString .= '`' . $left . "` " . $equalSign . " '" . $right . "' ";
				}
				
				break;
			}
			
			$query .= $criteriaString . " ;";
			if($debug){echo $query;}
			$conn = sql_connect();
			$stmt = $conn->prepare($query);
			$stmt->execute();
			
			$resultArray = array();
			foreach ($stmt->fetchAll() as $row) {
				$resultArray[] = $row;
			}
			$conn = null;
			
			return $resultArray;
		}
		/**
			* SUMMARY OF sql_update_row
			*
			* DESCRIPTION
			*
			* @param TYPE ($id, $sqlTable, $row, $idHeader = "id", $debug = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		
		public static function  sql_update_row($id, $sqlTable, $row, $idHeader = "id", $debug = FALSE) {
			
			$headers = sql_get_headers($sqlTable);
			
			$query = "UPDATE " . $sqlTable . ' SET ';
			$valueArray = array();
			foreach ($headers as $header) {
				if ($header != $idHeader && isset($row[$header])) {
					$valueArray[] = $header . '=\'' . $row[$header] . '\'';
				}
			}
			$query .= implode(", ", $valueArray);
			$query .= ' WHERE ' . $idHeader . '=\'' . $id . '\' ;';
			if($debug){echo $query;}
			sql_quick_execute($query);
		}
		/**
			* SUMMARY OF sql_quick_execute
			*
			* DESCRIPTION
			*
			* @param TYPE ($query) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_quick_execute($query) {
			
			$conn = sql_connect();
			$stmt = $conn->prepare($query);
			$stmt->execute();
			$conn = null;
		}
		/**
			* SUMMARY OF sql_truncate
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_truncate($sqlTable) {
			$query = 'TRUNCATE ' . $sqlTable . ' ;';
			sql_quick_execute($query);
		}
		/**
			* SUMMARY OF sql_get_headers
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_get_headers($sqlTable) {
			
			$connectionDetails = get_conn_details();
			$cd = $connectionDetails;
			$otherDB = array($cd[0], "information_schema", $cd[2], $cd[3]);
			
			$quote = "'"; // stupid workaround
			$query = 'SELECT COLUMN_NAME FROM COLUMNS WHERE ';
			$query .= 'TABLE_NAME = ' . $quote . $sqlTable . $quote . ' ;';
			
			$conn = sql_connect($otherDB);
			$stmt = $conn->prepare($query);
			$stmt->execute();
			
			$queryResult = $stmt->fetchAll();
			
			$headers = array();
			foreach ($queryResult as $row) {
				$headers[] = $row["COLUMN_NAME"];
			}
			$conn = null;
			
			return $headers;
		}
		
		/* Works only for tables with ONE primary key
		*/
		/**
			* SUMMARY OF sql_delete
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $criteria = "", $not = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_delete($sqlTable, $criteria = "", $not = FALSE)
		{
			
			$query = "";
			if($criteria == ""){
				$query .= "TRUNCATE TABLE " . $sqlTable;
			} 
			else {
				$idColumnName = get_primary_column_name($sqlTable);
				//echo $idColumnName . "<br>";
				$array = sql_select($sqlTable, $criteria, $headers = "all", $not);
				if(count($array) > 0){
					$query .= 'DELETE FROM ' . $sqlTable . ' WHERE ' ;
					
					$orArray = array();
					foreach($array as $row){
						$orArray[] = $idColumnName . ' = ' . $row[$idColumnName];
					}
					
					$query .= implode(" OR ", $orArray);
					$query .= ";";
				}
			}
			sql_quick_execute($query);
		}
		/**
			* SUMMARY OF get_primary_column_name
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  get_primary_column_name($sqlTable)
		{
			
			$query = 'SELECT COLUMN_NAME FROM COLUMNS
			WHERE
			TABLE_NAME = \'' . $sqlTable . '\' AND COLUMN_KEY = \'PRI\';' ;
			
			$connectionDetails = get_conn_details();
			$cd = $connectionDetails;
			
			$otherDB = array($cd[0], "information_schema", $cd[2], $cd[3]);
			
			$conn = sql_connect($otherDB);
			$stmt = $conn->prepare($query);
			$stmt->execute();
			
			$queryResult = $stmt->fetchAll();
			
			$primaryHeader = $queryResult[0]["COLUMN_NAME"];
			
			$conn = null;
			
			return $primaryHeader;
			
		}
		
		/* will retrieve an id of  */
		/**
			* SUMMARY OF sql_get_id
			*
			* DESCRIPTION
			*
			* @param TYPE ($sqlTable, $criteria, $id_header_name = "id", $not = FALSE, $debug = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function  sql_get_id($sqlTable, $criteria, $id_header_name = "id", $not = FALSE, $debug = FALSE)
		{
			
			$queryResult = sql_select($sqlTable, $criteria, $id_header_name, $not, $debug);
			if(count($queryResult) == 1){
				$onlyResult = reset($queryResult);
				$result = $onlyResult[$id_header_name];
			}
			else {
				$result = FALSE;
			}
			
			return $result;
		}
	}	