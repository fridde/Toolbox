<?php 
	
	namespace Fridde;
	
	class ArrayTools
	{
		
		public $Array;
		
		public function __construct ($array = []){
			$this->Array = $array;
		}
		
		public function __invoke()
		{
			return $this->Array;
		}
		
		function add_header($array, $header)
		{
			$header = explode(",", $header);
			array_unshift($array, $header);
			return $array;
		}
		/**
			* SUMMARY OF remove_duplicates
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function remove_duplicates($array)
		{
			
			return array_unique($array, SORT_REGULAR);
		}
		/**
			* SUMMARY OF draw_left
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $targetCol) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function draw_left($array, $targetCol)
		{
			
			if ($targetCol == "") {
				$targetCol = 0;
			}
			
			foreach ($array as $rowKey => $row) {
				foreach ($row as $colKey => $cell) {
					if ($cell != "" && $targetCol <= $colKey) {
						$array[$rowKey] = array_splice($row, $colKey);
						continue 2;
					}
				}
			}
			return $array;
		}
		/**
			* SUMMARY OF find_rows
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $regex, $col) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function find_rows($array, $regex, $col)
		{
			if ($col == "") {
				$col = 0;
			}
			
			$returnArray = array();
			foreach ($array as $rowKey => $row) {
				if ($row[$col] == "" && $regex == "") {
					$returnArray[] = $rowKey;
				}
				else {
					if (preg_match($regex, $row[$col]) == 1) {
						$returnArray[] = $rowKey;
					}
				}
			}
			return $returnArray;
		}
		/**
			* SUMMARY OF split_row_at
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $colRowRegex, $delimiterRegex) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function split_row_at($array, $colRowRegex, $delimiterRegex)
		{
			$colRowRegex = explode(",", $colRowRegex, 2);
			$col = $colRowRegex[0];
			if (count($colRowRegex) > 1) {
				$rowRegex = $colRowRegex[1];
			}
			else {
				$rowRegex = "%\w+%";
				//means "at least something"
			}
			
			$returnArray = array();
			$rows = Helper::find_rows($array, $rowRegex, $col);
			foreach ($array as $rowKey => $row) {
				if (in_array($rowKey, $rows)) {
					$splitCells = array_keys(preg_grep($delimiterRegex, $array[$rowKey]));
					$added_arrays = Helper::slice_at($row, $splitCells);
				}
				else {
					$added_arrays = $row;
				}
				foreach ($added_arrays as $singleRow) {
					$returnArray[] = $singleRow;
				}
			}
			return $returnArray;
		}
		/**
			* SUMMARY OF remove_lines_where
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $regex, $col) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function remove_lines_where($array, $regex, $col)
		{
			
			$linesToRemove = Helper::find_rows($array, $regex, $col);
			
			$array = Helper::remove_lines($array, $linesToRemove);
			return $array;
		}
		/**
			* SUMMARY OF fill_from_above_where
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $pivotCols, $regex) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function fill_from_above_where($array, $pivotCols, $regex)
		{
			$pivotRows = Helper::find_rows($array, $regex, $pivotCols);
			$array = Helper::fill_from_above($array, $pivotCols, $pivotRows);
			return $array;
		}
		/**
			* SUMMARY OF remove_if_other_col
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $checkCol, $removeCol) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function remove_if_other_col($array, $checkCol, $removeCol)
		{
			$checkCol = explode(",", $checkCol, 2);
			$regex = "%\w+%";
			$checkCol = $checkCol[0];
			if (count($checkCol) > 1) {
				$regex = $checkCol[1];
			}
			
			foreach ($array as $rowKey => $row) {
				if (preg_match($regex, $row[$checkCol]) == 1) {
					$array[$rowKey][$removeCol] = "";
				}
			}
			
			return $array;
		}
		/**
			* SUMMARY OF melt
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $colsToSplit, $header) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function melt($array, $colsToSplit, $header)
		{
			/* will "melt" an array according to Hadley Wickham's paper http://www.jstatsoft.org/v21/i12/paper */
			
			// header contains only the words used for the value and the word of differentiation, for example "Value" and "Product"
			$colsToSplit = explode(",", $colsToSplit);
			$header = explode(",", $header);
			
			$newHeader = array_diff_key($array[0], array_flip($colsToSplit));
			$newHeader = array_merge(array_values($newHeader), $header);
			
			$newArray = array($newHeader);
			
			foreach ($colsToSplit as $splitCol) {
				$property = $array[0][$splitCol];
				foreach ($array as $rowKey => $row) {
					if ($rowKey == 0) {//don't include old header
						continue 1;
					}
					$newRow = array();
					foreach ($row as $colKey => $cell) {
						if ($colKey == $splitCol) {
							$propertyValue = $cell;
						}
						if (!(in_array($colKey, $colsToSplit))) {
							$newRow[] = $cell;
						}
					}
					$newRow[] = $propertyValue;
					$newRow[] = $property;
					$newArray[] = $newRow;
				}
			}
			return $newArray;
		}
		/**
			* SUMMARY OF get_array_column
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col, $hasHeader) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function get_array_column($array, $col, $hasHeader)
		{
			
			$newArray = array();
			
			if (gettype($hasHeader) == "boolean" && $hasHeader == TRUE) {
				$newHeader = $array[0][$col];
				$array = array_slice($array, 1);
			}
			elseif (gettype($hasHeader) == "string" && strlen($hasHeader) > 0) {
				$newHeader = $hasHeader;
				$array = array_slice($array, 1);
			}
			
			foreach ($array as $rowKey => $row) {
				$newArray[] = $row[$col];
			}
			$returnArray = array(
			"header" => $newHeader,
			"values" => $newArray
			);
			
			return $returnArray;
		}
		/**
			* SUMMARY OF filter_for_value
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $key, $values) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function filter_for_value($array, $key, $values)
		{
			// goes through each element of an 2d-array and returns only those rows where the element $key is $value
			
			if (gettype($values) != "array") {
				$values = array($values);
			}
			
			$newArray = array_filter($array, function($arrayRow) use ($key, $values)
			{
				return (is_array($arrayRow) && in_array($arrayRow[$key], $values));
			});
			
			return $newArray;
		}
		/**
			* SUMMARY OF rebuild_keys
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $key) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function rebuild_keys($array, $key)
		{
			/* rebuilds a two-dimensional array to have a certain value from each "row" as each key
				usage: $array = array([0]=>array("Fruit"=>"Banana", "Taste"=>"good"),
				[1]=>array("Fruit"=>"Apple", "Taste"=>"boring"));
			$newArray = rebuild_keys($array, "Fruit"); */
			
			$newArray = array();
			foreach ($array as $rowKey => $arrayRow) {
				if (isset($newArray[$arrayRow[$key]])) {
					$duplicate[] = $rowKey;
				}
				else {
					$newArray[$arrayRow[$key]] = $arrayRow;
				}
			}
			if (isset($duplicate)) {
				echo "Error: The key you specified is not unique. Some values appear at least twice. Invalid keys at row " . implode(", ", $duplicate);
			}
			else {
				return $newArray;
			}
		}
		/**
			* SUMMARY OF array_to_csv_download
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $filename = "export.csv", $delimiter = ";") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_to_csv_download($array, $filename = "export.csv", $delimiter = ";")
		{
			
			// open raw memory as file so no temp files needed, you might run out of memory though
			$f = fopen('php://memory', 'w');
			// loop over the input array
			foreach ($array as $line) {
				// generate csv lines from the inner arrays
				fputcsv($f, $line, $delimiter, '"');
			}
			// rewind the "file" with the csv lines
			fseek($f, 0);
			// tell the browser it's going to be a csv file
			header('Content-Type: application/csv');
			// tell the browser we want to save it instead of displaying it
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			// make php send the generated csv lines to the browser
			fpassthru($f);
		}
		/**
			* SUMMARY OF where
			*
			* DESCRIPTION
			*
			* @param array $criteria ["column", "value"] or ["column", "=", "value"] 
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public function where()
		{
			$c = func_get_args();
			if(count($c) == 2){
				$c = [$c[0], "=", $c[1]];
			}
			$compare = function($v) use ($c){
				switch($c[1]){
					case "=":
					return $v[$c[0]] == $c[2];
					break;
					case "in":
					return in_array($v[$c[0]], $c[2]);
					break;
					
					default:
					return eval('return("' . $v[$c[0]] . '" ' . $c[1] . ' "' . $c[2] . '");');					
				}
			};
			$this->Array = array_filter($this->Array, $compare);
			return $this;
		}
		/**
			* SUMMARY OF array_choose_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $columns, $remove = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_choose_columns($array, $columns, $remove = FALSE)
		{
			/* will take a rectangular array and choose or remove certain columns.
				array: the array to choose from
				columns: an array that contains all the columns
				remove: a boolean that tells whether the columns given in the parameter "columns" should be selected or removed from the array. If TRUE, all columns EXCEPT the given columns are chosen.
			*/
			$resultArray = array();
			foreach($array as $row){
				$rowToAdd = array();
				foreach($row as $key => $value){
					$inArray = in_array($key, $columns);
					if(($inArray && !$remove) || (!$inArray && $remove)){
						$rowToAdd[$key] = $value;
					}
					
				}
				$resultArray[] = $rowToAdd;
			}
			
			return $resultArray;
		}
		/**
			* SUMMARY OF array_orderby
			*
			* DESCRIPTION
			*
			* @param TYPE () ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_orderby()
		{
			/* Will sort a 2d-array according to own rules
				Pass the array, followed by the column names and sort flags
				$sorted = array_orderby($data, 'volume', SORT_DESC, 'edition', SORT_ASC);
			*/
			
			$args = func_get_args();
			$data = array_shift($args);
			foreach ($args as $n => $field) {
				if (is_string($field)) {
					$tmp = array();
					foreach ($data as $key => $row)
					$tmp[$key] = $row[$field];
					$args[$n] = $tmp;
				}
			}
			$args[] = &$data;
			call_user_func_array('array_multisort', $args);
			return array_pop($args);
		}
		/**
			* SUMMARY OF get_element
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $type = "index", $number = 0) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function get_element($array, $type = "index", $number = 0)
		{
			/* will return an element of an array adressed by number instead of key*/
			$returnObject = "";
			
			if($type == "index"){
				$array_keys = array_keys($array);
				$returnObject = $array_keys[$number];
			}
			elseif($type = "key") {
				$array_values = array_values($array);
				$returnObject = $array_values[$number];
			}
			
			return $returnObject;
		}
		/**
			* SUMMARY OF array_change_col_names
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $translationArray) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_change_col_names($array, $translationArray)
		{
			/* takes an array simulating a table in the format
				* array(
				*   row1 => array(nameCol1 => valueRow1Col1, nameCol2 => valueRow1Col2, ...),
				*   row2 => array(nameCol1 => valueRow2Col1, nameCol2 => valueRow2Col2, ...),
				*   ...
				* )
				* and exchanges the columnnames according to the translationArray in the format
				* array(
				*   oldColName1 => newColName1, oldColName2 => newColName2, ...
				* )
			*/
			$newArray = array();
			foreach ($array as $rowIndex => $row) {
				$newRow = array();
				foreach ($row as $colName => $value) {
					if (isset($translationArray[$colName]) && trim($translationArray[$colName]) != "") {
						$newRow[$translationArray[$colName]] = $value;
						} else {
						$newRow[$colName] = $value;
					}
				}
				$newArray[$rowIndex] = $newRow;
			}
			
			return $newArray;
		}
		/**
			* SUMMARY OF col_to_index
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $columnToIndex) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function col_to_index($array, $columnToIndex)
		{
			/* assumes a certain column to contain unique values and makes these values
				* the key of each row
			*/
			$newArray = array();
			foreach ($array as $oldRowIndex => $row) {
				$newArray[$row[$columnToIndex]] = $row;
			}
			return $newArray;
		}
		/**
			* SUMMARY OF sort_according_to
			*
			* DESCRIPTION
			*
			* @param TYPE ($wordArray, $name) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function sort_according_to($wordArray, $name)
		{
			
			$normArray = array($name => $wordArray[$name]);
			$otherArrays = array_diff_key($wordArray, $normArray);
			
			foreach ($otherArrays as $file => $frequencies) {
				$frequencies = $frequencies["frequencies"];
				$newArray = array();
				
				foreach ($normArray[$name]["frequencies"] as $word => $frequency) {
					if (isset($frequencies[$word])) {
						$newArray[$word] = $frequencies[$word];
					}
					else {
						$newArray[$word] = NULL;
					}
				}
				$otherArrays[$file]["frequencies"] = $newArray;
				
			}
			$returnArray = array_merge($normArray, $otherArrays);
			return $returnArray;
		}
		/**
			* SUMMARY OF array_delete_by_key
			*
			* DESCRIPTION
			*
			* @param TYPE (&$array, $delete_key, $use_old_keys = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_delete_by_key(&$array, $delete_key, $use_old_keys = FALSE)
		{
			
			unset($array[$delete_key]);
			
			if (!$use_old_keys) {
				$array = array_values($array);
			}
			
			return TRUE;
		}
		/**
			* will return the number of rows(number of arrays in $array)
			* and columns (length of the longest array within $array)
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function countColRow($array)
		{
			$rows = count($array);
			$cols = max(array_map('count', $array));
			return [$cols, $rows];
		}
		/**
			* SUMMARY OF fill_array
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $cols = NULL) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function fill_array($array, $cols = NULL)
		{
			/* for a given $array of arrays, each array is padded with NULL-values
				* to equalize lengths.
			* The parameter $cols */
			$col_row = Helper::count_col_row($array);
			if (is_null($cols)) {
				$cols = $col_row["col"];
			}
			else {
				$cols = $col_row["col"] + intval($cols);
			}
			
			foreach ($array as $key => $row) {
				$array[$key] = array_pad($row, $cols, NULL);
			}
			
			return $array;
		}
		/**
			* SUMMARY OF array_to_csv
			*
			* DESCRIPTION
			*
			* @param TYPE ($dataArray, $filePointer = NULL, $delimiter = ',', $enclosure = '"', $encloseAll = TRUE, $nullToMysqlNull = false) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function array_to_csv($dataArray, $filePointer = NULL, $delimiter = ',', $enclosure = '"', $encloseAll = TRUE, $nullToMysqlNull = false)
		{
			$csvstring = "";
			if (isset($filePointer)) {
				$filePointer = fopen($filePointer, "w+");
			}
			
			$delimiter_esc = preg_quote($delimiter, '/');
			$enclosure_esc = preg_quote($enclosure, '/');
			
			foreach ($dataArray as $row) {
				if (empty($row)) {
					continue;
				}
				$output = array();
				foreach ($row as $field) {
					if ($field === null && $nullToMysqlNull) {
						$output[] = 'NULL';
						continue;
					}
					$field = trim($field);
					
					// Enclose fields containing $delimiter, $enclosure or whitespace
					if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|[[:blank:]])/", $field)) {
						$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
					}
					else {
						$output[] = $field;
					}
				}
				
				$csvstring .= implode($delimiter, $output) . PHP_EOL;
			}
			if (isset($filePointer)) {
				fwrite($filePointer, $csvstring);
				fclose($filePointer);
			}
			
			return $csvstring;
		}
		/**
			* SUMMARY OF jump
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $pivotColumn, $copy) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function jump($array, $pivotColumn, $copy)
		{
			/* sends the content of the cell in a certain column, the pivotColumn,
				* to the last column available if its cell to the right is empty or "copy" is set to true
			* */
			if ($pivotColumn == "") {
				$pivotColumn = 0;
			}
			if ($copy == "") {
				$copy = FALSE;
			}
			$cols_rows = Helper::count_col_row($array);
			$cols = $cols_rows["col"];
			
			foreach ($array as $key => $row) {
				
				if ($copy == "TRUE" || empty($row[$pivotColumn + 1])) {
					$row[$cols - 1] = $row[$pivotColumn];
					$array[$key] = $row;
				}
			}
			
			return $array;
		}
		/**
			* SUMMARY OF copy_column
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $column) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function copy_column($array, $column)
		{
			/* will copy the content of a column to the last column */
			return Helper::jump($array, $column, $copy = "TRUE");
		}
		/**
			* SUMMARY OF fill_from_above
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $pivotCols, $pivotRows) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function fill_from_above($array, $pivotCols, $pivotRows)
		{
			/* will choose the specified rows in the specified column and copy onto the cells below all the way down
				* until the next specified row is reached
			*  */
			$cols_rows = Helper::count_col_row($array);
			if ($pivotCols == "") {
				$pivotCols = array("0");
			}
			else {
				$pivotCols = explode(",", $pivotCols);
			}
			
			if (gettype($pivotRows) == "string" && $pivotRows != "") {
				$pivotRows = explode(",", $pivotRows);
			}
			else {
				$pivotRows = array();
				foreach ($pivotCols as $col) {
					foreach ($array as $rowKey => $row) {
						if (trim($row[$col]) != "") {
							$pivotRows[] = $rowKey;
						}
						
					}
				}
			}
			// echop($pivotRows);
			foreach ($array as $rowKey => $row) {
				if (!(in_array($rowKey, $pivotRows))) {
					foreach ($row as $colKey => $cellValue) {
						if (in_array($colKey, $pivotCols)) {
							$array[$rowKey][$colKey] = $array[$rowKey - 1][$colKey];
						}
					}
				}
			}
			// echop($array);
			return $array;
		}
		/**
			* SUMMARY OF remove_lines
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $lines) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function remove_lines($array, $lines)
		{
			if (gettype($lines) == "string") {
				$lines = explode(",", $lines);
			}
			$newArray = array();
			
			foreach ($array as $key => $row) {
				if (!(in_array($key, $lines)))
				$newArray[] = $row;
			}
			return $newArray;
		}
		/**
			* SUMMARY OF remove_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $columns, $keepKeys) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function remove_columns($array, $columns, $keepKeys)
		{
			if ($columns === "") {
				$columns = Helper::empty_columns($array);
			}
			else {
				$columns = explode(",", $columns);
			}
			
			$newArray = array();
			foreach ($array as $rowKey => $row) {
				foreach ($row as $colKey => $cell) {
					if (!(in_array($colKey, $columns))) {
						if ($keepKeys == "true") {
							$newArray[$rowKey][$colKey] = $cell;
						}
						else {
							$newArray[$rowKey][] = $cell;
						}
					}
				}
			}
			
			return $newArray;
		}
		/**
			* SUMMARY OF empty_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function empty_columns($array)
		{
			$columns = $array[0];
			$resColumns = array();
			
			foreach ($columns as $colKey => $col) {
				$colArray = array();
				foreach ($array as $rowKey => $row) {
					if (trim($array[$rowKey][$colKey]) != "") {
						$colArray[] = $array[$rowKey][$colKey];
					}
				}
				if (implode($colArray) == "") {
					$resColumns[] = $colKey;
				}
			}
			return $resColumns;
		}
		/**
			* SUMMARY OF interject_rows
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $number, $copy) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function interject_rows($array, $number, $copy)
		{
			if ($number == "") {
				$number = 1;
			}
			if ($copy == "") {
				$copy = FALSE;
			}
			$emptyRow = array_fill(0, count($array[0]), NULL);
			
			$newArray = array();
			
			foreach ($array as $rowKey => $row) {
				$newArray[] = $row;
				
				for ($i = 0; $i < $number; $i++) {
					if ($copy == "TRUE") {
						$newArray[] = $newArray[$rowKey];
					}
					else {
						$newArray[] = $emptyRow;
					}
				}
			}
			
			return $newArray;
		}
		/**
			* SUMMARY OF add_column
			*
			* DESCRIPTION
			*
			* @param TYPE ($csv, $cols) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function add_column($csv, $cols)
		{
			if ($cols == "") {
				$cols = 1;
			};
			return Helper::fill_array($csv, $cols);
		}
		/**
			* SUMMARY OF copy_where
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col, $regex) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function copy_where($array, $col, $regex)
		{
			if ($col == "") {
				$col = 0;
			}
			if ($regex == "") {
				$regex = "%\w+%";
			}
			
			$endCol = count($array[0]) - 1;
			foreach ($array as $rowKey => $row) {
				if (preg_match($regex, $row[$col]) == 1) {
					$array[$rowKey][$endCol] = $array[$rowKey][$col];
				}
			}
			
			return $array;
		}
		/**
			* SUMMARY OF remove_from
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col, $regex) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function remove_from($array, $col, $regex)
		{
			
			$col = explode(",", $col);
			$regex = explode(",", $regex);
			foreach ($col as $colKey => $colValue) {
				foreach ($array as $rowKey => $row) {
					
					$array[$rowKey][$colValue] = preg_replace($regex[$colKey], "", $row[$colValue]);
				}
			}
			
			return $array;
		}
		/**
			* SUMMARY OF nonempty_keys
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col = 0) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function nonempty_keys($array, $col = 0)
		{
			$nonEmpty = array();
			foreach ($array as $rowKey => $row) {
				if (!(empty($row[$col]))) {
					$nonEmpty[] = $rowKey;
				}
			}
			return $nonEmpty;
		}
		/**
			* SUMMARY OF nonempty_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function nonempty_columns($array)
		{
			$returnArray = array();
			$headers = array_keys(reset($array));
			foreach ($headers as $header) {
				$col = Helper::sql_select_columns($array, $header);
				if (array_filter($col) != NULL) {
					$returnArray[] = $header;
				}
			}
			return $returnArray;
		}
		/**
			* SUMMARY OF slice_at
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $startpoints) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		function slice_at($array, $startpoints)
		{
			$diffpoints = array();
			$startpoints = array_unique($startpoints);
			
			foreach ($startpoints as $key => $value) {
				if ($key != count($startpoints) - 1) {
					$diffpoints[] = $startpoints[$key + 1] - $value;
				}
			}
			$diffpoints[] = count($array) - end($diffpoints);
			$newArray = array();
			foreach ($startpoints as $key => $value) {
				$newArray[] = array_slice($array, $value, $diffpoints[$key]);
			}
			$newArray = array_values($newArray);
			return $newArray;
		}
		/**
			* SUMMARY OF array_each_add
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $number) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function array_each_add($array, $number)
		{
			foreach ($array as $key => $value) {
				$array[$key] = intval($array[$key]) + $number;
			}
			return $array;
		}
		
		/*
			
		*/
		/**
			* SUMMARY OF multiple_search
			*
			* DESCRIPTION
			*
			* @param TYPE ($haystack_array, $needle_array, $length_limit = FALSE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function multiple_search($haystack_array, $needle_array, $length_limit = FALSE)
		{
			$returnArray = array();
			if (gettype($needle_array) != "array") {
				$needle_array = array($needle_array);
			}
			
			foreach ($needle_array as $needleKey => $needleValue) {
				foreach ($haystack_array as $haystackKey => $haystackValue) {
					$is_found = gettype(stripos($haystackValue, $needleValue)) == "integer";
					$is_short = $length_limit == FALSE || strlen($haystackValue) < $length_limit;
					if ($is_found && $is_short) {
						$returnArray[] = $haystackKey;
					}
				}
			}
			$returnArray = array_unique(array_values($returnArray));
			sort($returnArray);
			
			return $returnArray;
		}
		
		/*
			
		*/
		/**
			* SUMMARY OF merge_columns
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col1, $col2) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function merge_columns($array, $col1, $col2)
		{
			
			foreach ($array as $rowKey => $row) {
				
				$array[$rowKey][$col1] = $array[$rowKey][$col1] . $array[$rowKey][$col2];
			}
			$array = Helper::remove_columns($array, $columns = $col2);
			
			return $array;
		}
		
		/*
			
		*/
		/**
			* SUMMARY OF split_at
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $col, $char) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		function split_at($array, $col, $char)
		{
			$col = intval($col);
			if (strlen(trim($char)) < 1) {
				$char = "%[[:blank:]]{1}%";
			}
			else {
				$char = "%" . $char . "%";
			}
			
			foreach ($array as $rowKey => $row) {
				
				$cell = $row[$col];
				
				$splitArray = preg_split($char, $cell);
				$leftCell = array($splitArray[0]);
				$rightCell = array($splitArray[1]);
				
				if ($col == 0) {
					$array[$rowKey] = array_merge($leftCell, $rightCell, array_slice($row, 2));
					
				}
				else {
					$leftPart = array_slice($row, 0, $col - 1);
					$rightPart = array_slice($row, $col + 1);
					$array[$rowKey] = array_merge($leftPart, $leftCell, $rightCell, $rightPart);
				}
			}
			
			return $array;
		}
	}								