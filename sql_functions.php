<?php
	
	/*
		* Contains custom SQL-functions that use PDO
		* assumes that a config.ini -file exists matching the template given in this folder
		
	*/
	
	function get_conn_details($ini_file = "config.ini"){
		
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
	
	
	function sql_connect($nonStandardDB = FALSE) {
		/* expects an array that contains the following values (in that order)
			* array($db_host, $db_name, db_username, $db_password);    *
		*/
		
		if ($nonStandardDB == FALSE) {
			
			$connectionDetails = get_conn_details();
			$cd = $connectionDetails; // abbreviation
			} else {
			$cd = $nonStandardDB;
		}
		echop($cd);
		try {
			$conn = new PDO("mysql:host=$cd[0];dbname=$cd[1];", $cd[2], $cd[3]);
			$conn->exec("SET NAMES utf8");
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e) {
			echo $e->getMessage();
		}
		return $conn;
	}
	
	function sql_get_highest_id($sqlTable, $idHeader = "id") {
		
		$query = "SELECT " . $idHeader . " FROM " . $sqlTable;
		$query .= " ORDER BY " . $idHeader . " DESC LIMIT 1;";
		
		$conn = sql_connect();
		$stmt = $conn->prepare($query);
		$stmt->execute();
		
		// set the resulting array to associative
		$queryResult = $stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		$resultArray = $stmt->fetchAll();
		$conn = null;
		return $resultArray[0][$idHeader];
	}
	
	function sql_insert_rows($sqlTable, $array, $forceId = FALSE, $maxString = 5000) {
		
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
				$conn->exec($totalQuery);
				
				$query = "";
			}
		}
		//add the rest
		if (strlen($query) > 2) {
			$totalQuery = $queryStart . rtrim($query, ",") . ";";
			
			$conn->exec($totalQuery);
		}
		$conn = null;
		
	}
	
	function sql_select($sqlTable, $criteria = "", $headers = "all", $not = FALSE) {
		
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
		
		$equalSign = ($not ? "<>" : "=");
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
            $criteriaString = $criteria;
            break;
			
			case "string_and_array":
            $criteriaString = " WHERE ";
            $glue = strtoupper($criteria[0]) . " ";
            $criteriaArray = array();
            foreach ($criteria[1] as $left => $right) {
                $criteriaArray[] = $left . " " . $equalSign . " '" . $right . "' ";
			}
            $criteriaString .= implode($glue, $criteriaArray);
            break;
			
			case "two_strings":
            $criteriaString = " WHERE ";
            $criteriaString .= $criteria[0] . " " . $equalSign . ' \'' . $criteria[1] . '\'';
            break;
			
			case "atomic_array":
            $criteriaString = " WHERE ";
            $key = array_keys($criteria);
            $key = $key[0];
            $criteriaString .= $key . " " . $equalSign  . ' \'' . $criteria[$key] . '\'';
            break;
		}
		
		$query .= $criteriaString . " ;";
		
		$conn = sql_connect();
		$stmt = $conn->prepare($query);
		$stmt->execute();
		
		// set the resulting array to associative
		$queryResult = $stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		$resultArray = array();
		foreach ($stmt->fetchAll() as $row) {
			$resultArray[] = $row;
		}
		$conn = null;
		
		return $resultArray;
	}
	
	function sql_update_row($id, $sqlTable, $row, $idHeader = "id") {
		
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
		sql_quick_execute($query);
	}
	
	function sql_quick_execute($query) {
		
		$conn = sql_connect();
		$stmt = $conn->prepare($query);
		$stmt->execute();
		$conn = null;
	}
	
	function sql_truncate($sqlTable) {
		$query = 'TRUNCATE ' . $sqlTable . ' ;';
		sql_quick_execute($query);
	}
	
	function sql_get_headers($sqlTable) {
		
		$connectionDetails = get_conn_details();
		$cd = $connectionDetails;
		echop($cd);
		$otherDB = array($cd[0], "information_schema", $cd[2], $cd[3]);
		
		$quote = "'"; // stupid workaround
		$query = 'SELECT COLUMN_NAME FROM COLUMNS WHERE ';
		$query .= 'TABLE_NAME = ' . $quote . $sqlTable . $quote . ' ;';
		
		$conn = sql_connect($otherDB);
		$stmt = $conn->prepare($query);
		$stmt->execute();
		
		// set the resulting array to associative
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
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
	
	function sql_delete($sqlTable, $criteria = "", $not = FALSE){
		
		$idColumnName = get_primary_column_name($sqlTable);
		//echo $idColumnName . "<br>";
		$array = sql_select($sqlTable, $criteria, $headers = "all", $not);
		if(count($array) > 0){
			$query = 'DELETE FROM ' . $sqlTable . ' WHERE ' ;
			
			$orArray = array();
			foreach($array as $row){
				$orArray[] = $idColumnName . ' = ' . $row[$idColumnName];
			}
			
			$query .= implode(" OR ", $orArray);
			$query .= ";";
			
			//echo $query;
			sql_quick_execute($query);
		}
	} 
	
	function get_primary_column_name($sqlTable){
		
		$query = 'SELECT COLUMN_NAME FROM COLUMNS  
		WHERE 
		TABLE_NAME = \'' . $sqlTable . '\' AND COLUMN_KEY = \'PRI\';' ;
		
		$connectionDetails = get_conn_details();
		$cd = $connectionDetails;
		
		$otherDB = array($cd[0], "information_schema", $cd[2], $cd[3]);
		
		$conn = sql_connect($otherDB);
		$stmt = $conn->prepare($query);
		$stmt->execute();
		
		// set the resulting array to associative
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		$queryResult = $stmt->fetchAll();
		
		$primaryHeader = $queryResult[0]["COLUMN_NAME"];
		
		$conn = null;
		
		return $primaryHeader;
		
		
	}
