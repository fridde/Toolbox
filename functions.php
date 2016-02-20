<?php
	
	function redirect($to) {
		@session_write_close();
		if (!headers_sent()) {
			header("Location: $to");
			flush();
			exit();
			} else {
			print "<html><head><META http-equiv='refresh' content='0;URL=$to'></head><body><a href='$to'>$to</a></body></html>";
			flush();
			exit();
		}
	}
	
	function array_change_col_names($array, $translationArray) {
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
	
	function clean_sql($string) {
		
		$search = array("\'");
		$replace = array("\'\'");
		
		$string = str_replace($search, $replace, $string);
		
		return $string;
	}
	
	function create_htmltable_from_array($array, $id = "sortable", $class = "display stripe") {
		
		/* check for emtpy array */
		$noRows = count($array) == 0;
		$oneRowButNoContent = count($array) == 1 && count(reset($array)) == 0;
		if($noRows || $oneRowButNoContent) {return "";}
		
		$colNames = array_keys(reset($array));
		$html = '<table id="';
		$html .= $id;
		$html .= '" class="';
		$html .= $class;
		$html .= '"><thead>';
		$html .= '<tr>';
		foreach ($colNames as $colname) {
			$html .= "<th>" . strtoupper($colname) . "</th>";
		}
		
		$html .= "</tr>
		</thead>
		<tbody>";
		foreach ($array as $rowIndex => $row) {
			$html .= "<tr>";
			foreach ($row as $colIndex => $cell) {
				$html .= "<td>" . $cell . "</td>";
			}
			$html .= "</tr>";
		}
		$html .= "</tbody></table>";
		
		return $html;
	}
	
	function check_inclusion_according_to_tag($row, $tags, $givenPasswords, $truePasswords) {
		
		//echo print_r($givenPasswords);
		$tagArray = explode(",", $tags);
		array_walk($tagArray, "trim");
		
		$any_match = FALSE;
		//echo print_r($truePasswords);
		
		if (count(array_filter($tagArray)) > 0) {
			foreach ($tagArray as $tag) {
				
				if (key_exists($tag, $truePasswords) && in_array($truePasswords[$tag]["password"], $givenPasswords)) {
					$any_match = TRUE;
				}
			}
			} else {
			/* if the link has no tags, it can be considered to be free */
			$any_match = TRUE;
		}
		return $any_match;
	}
	
	function col_to_index($array, $columnToIndex) {
		/* assumes a certain column to contain unique values and makes these values
			* the key of each row
		*/
		$newArray = array();
		foreach ($array as $oldRowIndex => $row) {
			$newArray[$row[$columnToIndex]] = $row;
		}
		return $newArray;
	}
	
	function filter_words($wordArray, $rules) {
		
		foreach ($rules as $rule) {
			$rule = array_map("trim", explode("=", $rule));
			//echo print_r($rule) . "<br>";
			$newArray = array();
			switch ($rule[0]) {
				
				case 'Case_insensitive' :
				if ($rule[1]) {
					foreach ($wordArray as $word => $occurrences) {
						if ($word === strtolower($word)) {
							if (isset($wordArray[ucwords($word)])) {
								$newArray[$word] = $wordArray[ucwords($word)] + $occurrences;
							}
							else {
								$newArray[$word] = $occurrences;
							}
						}
					}
					arsort($newArray);
					$wordArray = $newArray;
				}
				break;
				
				case "higher_than" :
				foreach ($wordArray as $word => $occurrences) {
					if ($occurrences > $rule[1]) {
						$newArray[$word] = $occurrences;
					}
				}
				$wordArray = $newArray;
				break;
				
				case "exclude" :
				$wordsToExclude = array_map("trim", explode(",", $rule[1]));
				
				foreach ($wordArray as $word => $occurrences) {
					if (!(in_array($word, $wordsToExclude))) {
						$newArray[$word] = $occurrences;
					}
				}
				$wordArray = $newArray;
				break;
				
				case "longer_than" :
				foreach ($wordArray as $word => $occurrences) {
					if (strlen($word) > $rule[1]) {
						$newArray[$word] = $occurrences;
					}
				}
				$wordArray = $newArray;
				
				break;
				
				case "max" :
				$i = 0;
				foreach ($wordArray as $word => $occurrences) {
					if ($i < $rule[1]) {
						$newArray[$word] = $occurrences;
					}
					$i++;
				}
				$wordArray = $newArray;
				break;
			}
		}
		
		return $wordArray;
	}
	
	function calculate_frequencies($wordArray) {
		foreach ($wordArray as $fileName => $content) {
			$wordCount = $content["wordCount"];
			$frequencyArray = $content["frequencies"];
			foreach ($frequencyArray as $word => $occurrences) {
				$frequencyArray[$word] = round(($occurrences / $wordCount) * 100, 2);
			}
			$wordArray[$fileName]["frequencies"] = $frequencyArray;
		}
		return $wordArray;
	}
	
	function sort_according_to($wordArray, $name) {
		
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
	
	function rectify_wordArray($wordArray) {
		$allWords = array();
		foreach ($wordArray as $file => $array) {
			$frequencies = $array["frequencies"];
			$allWords = array_merge($allWords, array_keys($frequencies));
		}
		$allWords = array_unique($allWords);
		
		foreach ($wordArray as $file => $array) {
			$frequencies = $array["frequencies"];
			$newArray = array();
			foreach ($allWords as $word) {
				if (isset($frequencys[$word])) {
					$newArray[$word] = $frequencys[$word];
				}
				else {
					$newArray[$word] = NULL;
				}
				$wordArray[$file] = $newArray;
			}
		}
		
		return $wordArray;
	}
	
	function create_rules_from_ini($ini_array) {
		
		$rulesOptions = array(
		"Case_insensitive",
		"higher_than",
		"exclude",
		"longer_than",
		"max"
		);
		
		$rulesArray = array();
		foreach ($ini_array as $key => $value) {
			if (in_array($key, $rulesOptions)) {
				$rulesArray[] = $key . " = " . $value;
			}
		}
		return $rulesArray;
	}
	
	function get_all_files($dir = 'files') {
		$fileArray = array();
		$handle = opendir($dir);
		
		while (false !== ($entry = readdir($handle))) {
			if (!in_array($entry, array(
			".",
			".."
			))) {
				$fileArray[] = $entry;
			}
		}
		closedir($handle);
		sort($fileArray);
		
		return $fileArray;
	}
	
	function echop($array) {
		/* extends echo by nicely printing arrays*/
		if (gettype($array) != "array") {
			echo "<br>" . $array . "<br>";
		}
		else {
			
			foreach ($array as $key => $element) {
				if (gettype($element) == "string") {
					echo $key . " => " . $element . "<br>";
				}
				else {
					echo $key . " => ";
					echo print_r($element) . "<br>";
				}
			}
			echo "<br>";
		}
	}
	
	function power_perms($arr) {
		
		$power_set = power_set($arr);
		$result = array();
		foreach ($power_set as $set) {
			$perms = perms($set);
			$result = array_merge($result, $perms);
		}
		return $result;
	}
	
	function power_set($in, $minLength = 1) {
		
		$count = count($in);
		$members = pow(2, $count);
		$return = array();
		for ($i = 0; $i < $members; $i++) {
			$b = sprintf("%0" . $count . "b", $i);
			$out = array();
			for ($j = 0; $j < $count; $j++) {
				if ($b{$j} == '1')
				$out[] = $in[$j];
			}
			if (count($out) >= $minLength) {
				$return[] = $out;
			}
		}
		
		//usort($return,"cmp");  //can sort here by length
		return $return;
	}
	
	function factorial($int) {
		if ($int < 2) {
			return 1;
		}
		
		for ($f = 2; $int - 1 > 1; $f *= $int--);
		
		return $f;
	}
	
	function perm($arr, $nth = null) {
		
		if ($nth === null) {
			return perms($arr);
		}
		
		$result = array();
		$length = count($arr);
		
		while ($length--) {
			$f = factorial($length);
			$p = floor($nth / $f);
			$result[] = $arr[$p];
			array_delete_by_key($arr, $p);
			$nth -= $p * $f;
		}
		
		$result = array_merge($result, $arr);
		return $result;
	}
	
	function perms($arr) {
		$p = array();
		for ($i = 0; $i < factorial(count($arr)); $i++) {
			$p[] = perm($arr, $i);
		}
		return $p;
	}
	
	function array_delete_by_key(&$array, $delete_key, $use_old_keys = FALSE) {
		
		unset($array[$delete_key]);
		
		if (!$use_old_keys) {
			$array = array_values($array);
		}
		
		return TRUE;
	}
	
	function make_comparer() {
		// Normalize criteria up front so that the comparer finds everything tidy
		$criteria = func_get_args();
		foreach ($criteria as $index => $criterion) {
			$criteria[$index] = is_array($criterion) ? array_pad($criterion, 3, null) : array(
			$criterion,
			SORT_ASC,
			null
			);
		}
		
		return function($first, $second) use (&$criteria) {
			foreach ($criteria as $criterion) {
				// How will we compare this round?
				list($column, $sortOrder, $projection) = $criterion;
				$sortOrder = $sortOrder === SORT_DESC ? -1 : 1;
				
				// If a projection was defined project the values now
				if ($projection) {
					$lhs = call_user_func($projection, $first[$column]);
					$rhs = call_user_func($projection, $second[$column]);
				}
				else {
					$lhs = $first[$column];
					$rhs = $second[$column];
				}
				
				// Do the actual comparison; do not return if equal
				if ($lhs < $rhs) {
					return -1 * $sortOrder;
				}
				else if ($lhs > $rhs) {
					return 1 * $sortOrder;
				}
			}
			
			return 0;
			// tiebreakers exhausted, so $first == $second
		};
	}
	
	function curPageURL() {
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
			$pageURL .= "s";
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		}
		else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}
	
	function echo_link_for($url, $label = "", $class = "") {
		/* wrapper for "link_for" */
		echo link_for($url, $label, $class);
	}
	
	function link_for($url, $label = "", $class = "") {
		/* wrapper to build links and the ability to define a class*/
		$returnString = '<a href="' . $url . '" ';
		if ($class != "") {
			$returnString .= 'class="' . $class . '"';
		}
		$returnString .= '>';
		if ($label == "") {
			$returnString .= $url;
		}
		else {
			$returnString .= $label;
		}
		$returnString .= "</a>";
		
		return $returnString;
	}
	
	
	function print_r2($Array, $Name = '', $size = 2, $depth = '', $Tab = '', $Sub = '', $c = 0) {
		/** wrote to display with large multi dimensional arrays, // Dave Husk , easyphpscripts.com
			* print_r2($Array,'Name_for_array'(optional));
		*/
		if (!is_array($Array))
		return (FALSE);
		if ($Name && $depth == '')
		$Name1 = '$' . $Name;
		$CR = "\r\n";
		if ($c == 0) {
			$display = '';
			//defualt to open at start
			echo $CR . '<script>function poke_that_array(dir){x=document.getElementById(dir);if(x.style.display == "none"){x.style.display = "";}else{x.style.display = "none";}}</script>' . $CR;
		}
		else
		$display = 'none';
		$BR = '<br>';
		$Red = '<font color="#DD0000" size=' . $size . '>';
		$Green = '<font color="#007700" size=' . $size . '>';
		$Blue = '<font color="#0000BB" size=' . $size . '>';
		$Black = '<font color="#000000" size=' . $size . '>';
		$Orange = '<font color="#FF9900" size=' . $size . '>';
		$Font_end = '</font>';
		$Left = $Green . '' . '[' . $Font_end;
		$Right = $Green . ']' . $Font_end;
		$At = $Black . ' => ' . $Font_end;
		$lSub = $Sub;
		$c++;
		foreach ($Array as $Key => $Val) {
			if ($Key) { $output = 1;
				$rKey = rand(100, 10000);
				echo $CR . '<div><a name="print_r2' . $rKey . $c . '">' . $Tab . '' . $Green . $Font_end . ' ' . $At . '<a href="#print_r2' . $rKey . $c . '" onClick=poke_that_array("print_r2' . $rKey . $c . '")><font  size=' . $size . '>Array(' . $Sub . '</font></a>' . $CR . '<div style="display:' . $display . ';" id="print_r2' . $rKey . $c . '">' . $CR;
				break;
			}
		}
		foreach ($Array as $Key => $Val) { $c++;
			$Type = gettype($Val);
			$q = '';
			if (is_array($Array[$Key]))
			$Sub = $Orange . ' /** [' . @htmlentities($Key) . '] */' . $Font_end;
			if (!is_numeric($Key))
			$q = '"';
			if (!is_numeric($Val) & !is_array($Val) & $Type != 'boolean')
			$Val = '"' . $Val . '"';
			if ($Type == 'NULL')
			$Val = 'NULL';
			if ($Type == 'boolean')
			$Val = ($Val == 1) ? 'TRUE' : 'FALSE';
			if (!is_array($Val)) { $At = $Blue . ' = ' . $Font_end;
				$e = ';';
			}
			if (is_array($Array[$Key]))
			$At = '';
			echo $CR . $Tab . (chr(9)) . '&nbsp;&nbsp;' . $depth . $Left . $Blue . $q . @htmlentities($Key) . $q . $Font_end . $Right . $At . $Red . @htmlentities($Val) . $Font_end . $e . $BR . $CR;
			if ($depth == '')
			unset($lSub);
			$e = '';
			if (is_array($Array[$Key]))
			print_r2($Array[$Key], $Name, $size, $depth . $Left . $Blue . $q . @htmlentities($Key) . $q . $Font_end . $Right, (chr(9)) . '&nbsp;&nbsp;&nbsp;' . $Tab, $Sub, $c);
		}
		if ($output)
		echo $CR . '</div>' . $Tab . '<font  size=' . $size . '>)' . $lSub . '</font></div>' . $CR;
		
	}
	
	class Table {
		
		protected $opentable = "\n<table cellspacing=\"0\" cellpadding=\"0\">\n";
		protected $closetable = "</table>\n";
		protected $openrow = "\t<tr>\n";
		protected $closerow = "\t</tr>\n";
		
		function __construct($data) {
			$this -> string = $this -> opentable;
			foreach ($data as $row) {
				$this -> string .= $this -> buildrow($row);
			}
			$this -> string .= $this -> closetable;
		}
		
		function addfield($field, $style = "null") {
			if ($style == "null") {
				$html = "\t\t<td>" . $field . "</td>\n";
			}
			else {
				$html = "\t\t<td class=\"" . $style . "\">" . $field . "</td>\n";
			}
			return $html;
		}
		
		function buildrow($row) {
			$html .= $this -> openrow;
			foreach ($row as $field) {
				$html .= $this -> addfield($field);
			}
			$html .= $this -> closerow;
			return $html;
		}
		
		function draw() {
			echo $this -> string;
		}
		
	}
	
	class CSV_DataSource {
		
		public function __construct($string) {
			$file = "temp/current.csv";
			$handle = fopen($file, "w+");
			fwrite($handle, $string);
			$csv = new File_CSV_DataSource;
			$csv -> load($file);
			
		}
		
	}
	
	function count_col_row($array) {
		/* will return the number of rows(number of arrays in $array)
		* and columns (length of the longest array within $array)*/
		$rows = count($array);
		$cols = 0;
		foreach ($array as $currentRow) {
			$cols = max($cols, count($currentRow));
		}
		
		return array(
		"col" => $cols,
		"row" => $rows
		);
	}
	
	function fill_array($array, $cols = NULL) {
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
	
	function array_to_csv($dataArray, $filePointer = NULL, $delimiter = ',', $enclosure = '"', $encloseAll = TRUE, $nullToMysqlNull = false) {
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
	
	function csvstring_to_array($string, $separatorChar = ',', $enclosureChar = '"', $newlineChar = "\n") {
		
		$array = array();
		$size = strlen($string);
		$columnIndex = 0;
		$rowIndex = 0;
		$fieldValue = "";
		$isEnclosured = false;
		for ($i = 0; $i < $size; $i++) {
			
			$char = $string{$i};
			$addChar = "";
			
			if ($isEnclosured) {
				if ($char == $enclosureChar) {
					
					if ($i + 1 < $size && $string{$i + 1} == $enclosureChar) {
						// escaped char
						$addChar = $char;
						$i++;
						// dont check next char
					}
					else {
						$isEnclosured = false;
					}
				}
				else {
					$addChar = $char;
				}
			}
			else {
				if ($char == $enclosureChar) {
					$isEnclosured = true;
				}
				else {
					
					if ($char == $separatorChar) {
						$array[$rowIndex][$columnIndex] = $fieldValue;
						$fieldValue = "";
						
						$columnIndex++;
					}
					elseif ($char == $newlineChar) {
						$array[$rowIndex][$columnIndex] = $fieldValue;
						$fieldValue = "";
						$columnIndex = 0;
						$rowIndex++;
					}
					else {
						$addChar = $char;
					}
				}
			}
			if ($addChar != "") {
				$fieldValue .= $addChar;
				
			}
		}
		
		if ($fieldValue) {// save last field
			
			$array[$rowIndex][$columnIndex] = $fieldValue;
		}
		
		return $array;
	}
	
	function parse($rawText, $code) {
		
		$text = $rawText;
		
		call_user_func($code, $text);
		
		return $text;
	}
	
	function create_html_from_csv($csv) {
		$array = Helper::csvstring_to_array($csv);
		return Helper::create_html_from_array($array);
	}
	
	function create_html_from_array($array) {
		$col_row = Helper::count_col_row($array);
		$cols = $col_row["col"];
		$rows = $col_row["row"];
		
		$html = "<p><table border = '1'>";
		for ($i = 0; $i < $rows; $i++) {
			$html .= '<tr><td class="index">[' . $i . "]</td>";
			for ($j = 0; $j < $cols; $j++) {
				$html .= "<td>" . stripcslashes($array[$i][$j]) . "</td>";
			}
			$html .= "</tr>";
		}
		$html .= "</table></p>";
		
		return $html;
	}
	
	function jump($array, $pivotColumn, $copy) {
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
	
	function copy_column($array, $column) {
		/* will copy the content of a column to the last column */
		return Helper::jump($array, $column, $copy = "TRUE");
	}
	
	function remove_whitelines($array) {
		
		foreach ($array as $key => $row) {
			if (strlen(trim(implode($row))) == 0) {
				$array[$key] = NULL;
			}
		}
		$array = array_filter($array);
		return $array;
	}
	
	function fill_from_above($array, $pivotCols, $pivotRows) {
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
	
	function remove_lines($array, $lines) {
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
	
	function html_to_csv($htmlString, $number = 0) {
		
		$html = str_get_html($htmlString);
		//$table =  $html->find('table');
		
		$csv = "";
		
		foreach ($html->find('tr') as $element) {
			$td = array();
			foreach ($element->find('th') as $row) {
				$td[] = trim($row -> plaintext);
			}
			$csv .= implode(",", $td) . PHP_EOL;
			
			$td = array();
			foreach ($element->find('td') as $row) {
				$cell = addslashes(trim($row -> plaintext));
				$td[] = $cell;
			}
			$csv .= '"' . implode('","', $td) . '"' . PHP_EOL;
		}
		
		return $csv;
	}
	
	function remove_columns($array, $columns, $keepKeys) {
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
	
	function empty_columns($array) {
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
	
	function interject_rows($array, $number, $copy) {
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
	
	function add_column($csv, $cols) {
		if ($cols == "") {
			$cols = 1;
		};
		return Helper::fill_array($csv, $cols);
	}
	
	function copy_where($array, $col, $regex) {
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
	
	function remove_from($array, $col, $regex) {
		
		$col = explode(",", $col);
		$regex = explode(",", $regex);
		foreach ($col as $colKey => $colValue) {
			foreach ($array as $rowKey => $row) {
				
				$array[$rowKey][$colValue] = preg_replace($regex[$colKey], "", $row[$colValue]);
			}
		}
		
		return $array;
	}
	
	function nonempty_keys($array, $col = 0) {
		$nonEmpty = array();
		foreach ($array as $rowKey => $row) {
			if (!(empty($row[$col]))) {
				$nonEmpty[] = $rowKey;
			}
		}
		return $nonEmpty;
	}
	
	function nonempty_columns($array) {
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
	
	function slice_at($array, $startpoints) {
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
	
	function convert_project_plan($array) {
		
		$nonEmpty = Helper::nonempty_keys($array);
		$array = Helper::slice_at($array, $nonEmpty);
		
		foreach ($array as $key => $Company) {
			$array[$key] = call_user_func_array('array_merge', array_values($Company));
			$array[$key] = array_values(array_filter($array[$key], 'strlen'));
		}
		
		$newArray = array();
		foreach ($array as $companyKey => $Company) {
			$CompanyName = $Company[0];
			$projectKeys = Helper::find_project_keys($Company);
			$Projects = Helper::slice_at($Company, $projectKeys);
			
			foreach ($Projects as $projectKey => $Project) {
				$ProjectName = $Project[0];
				
				$phaseKeys = Helper::find_phase_keys($Project);
				$Phases = Helper::slice_at($Project, $phaseKeys);
				$Phases = array_values($Phases);
				
				foreach ($Phases as $phaseKey => $Phase) {
					array_unshift($Phase, $CompanyName, $ProjectName);
					$newArray[] = $Phase;
				}
			}
		}
		$newArray = Helper::fill_array($newArray);
		$col_row = Helper::count_col_row($newArray);
		$col1 = $col_row["col"] - 2;
		$col2 = $col_row["col"] - 1;
		Helper::merge_columns($newArray, $col1, $col2);
		
		return $newArray;
	}
	
	function find_phase_keys($array) {
		
		$returnArray = array();
		foreach ($array as $key => $value) {
			if (intval($key) < 2) {
				continue;
			}
			
			if (Helper::is_phase($value)) {
				$returnArray[] = $key;
				
			}
			
		}
		return $returnArray;
	}
	
	function is_phase($string) {
		
		$limit = 30;
		$validPhases = array(
		"stage",
		"phase",
		"pilot",
		"demonstration",
		"bottleneck",
		"MR2",
		"Expansion",
		"Extension",
		"Leismer ",
		"Thornbury",
		"Pod One",
		"Hangingstone",
		"Reliability Tranche 2",
		"Millennium Mine"
		);
		$invalidPhases = array(
		"Hangingstone Pilot",
		"Single cycle pilot"
		);
		$is_phase = FALSE;
		
		foreach ($validPhases as $key => $value) {
			if (gettype(stripos($string, $value)) == "integer") {
				$is_phase = TRUE;
			}
		}
		$is_invalid = FALSE;
		foreach ($invalidPhases as $key => $value) {
			if (gettype(stripos($string, $value)) == "integer" || strlen($string) > $limit) {
				$is_invalid = TRUE;
			}
		}
		
		if ($is_phase && !($is_invalid)) {
			return TRUE;
			
		}
		else {
			return FALSE;
		}
		
	}
	
	function find_project_keys($array) {
		
		$nameArray = array();
		$array = array_values($array);
		
		foreach ($array as $key => $value) {
			$is_owner = Helper::is_project_owner($value);
			
			if ($is_owner) {
				$is_first_owner = !(Helper::is_project_owner($array[$key - 1]));
			}
			
			if ($is_owner && $is_first_owner) {
				
				if (Helper::is_project_date($array[$key - 2])) {
					$nameArray[] = $key - 3;
				}
				else {
					$nameArray[] = $key - 1;
				}
			}
		}
		return $nameArray;
	}
	
	function is_project_owner($string) {
		
		return (preg_match("~\d%\)~", $string) == 1);
	}
	
	/*
		/* ###################################################
		/* is_project_date
		/* ###################################################
	*/
	function is_project_date($string) {
		
		return (preg_match("~[[:upper:]]{1}[[:lower:]]{2}[[:blank:]]{1}\d{4}:~", $string) == 1);
	}
	
	/*
		/* ###################################################
		/* array_each_add
		/* ###################################################
	*/
	function array_each_add($array, $number) {
		foreach ($array as $key => $value) {
			$array[$key] = intval($array[$key]) + $number;
		}
		return $array;
	}
	
	/*
		/* ###################################################
		/* multiple_search
		/* ###################################################
	*/
	function multiple_search($haystack_array, $needle_array, $length_limit = FALSE) {
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
		/* ###################################################
		/* merge_columns
		/* ###################################################
	*/
	function merge_columns($array, $col1, $col2) {
		
		foreach ($array as $rowKey => $row) {
			
			$array[$rowKey][$col1] = $array[$rowKey][$col1] . $array[$rowKey][$col2];
		}
		$array = Helper::remove_columns($array, $columns = $col2);
		
		return $array;
	}
	
	/*
		/* ###################################################
		/* split_at
		/* ###################################################
	*/
	function split_at($array, $col, $char) {
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
	
	/*
		/* ###################################################
		/* transpose
		/* ###################################################
	*/
	function transpose($array) {
		array_unshift($array, null);
		return call_user_func_array('array_map', $array);
	}
	
	function add_header($array, $header) {
		$header = explode(",", $header);
		
		array_unshift($array, $header);
		
		return $array;
	}
	
	function remove_duplicates($array) {
		
		return array_unique($array, SORT_REGULAR);
	}
	
	function draw_left($array, $targetCol) {
		
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
	
	/*
		/* ###################################################
		/* find_rows
		/* ###################################################
	*/
	function find_rows($array, $regex, $col) {
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
	
	/*
		/* ###################################################
		/* split_row_at
		/* ###################################################
	*/
	function split_row_at($array, $colRowRegex, $delimiterRegex) {
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
	
	/*
		/* ###################################################
		/* remove_lines_where
		/* ###################################################
	*/
	function remove_lines_where($array, $regex, $col) {
		
		$linesToRemove = Helper::find_rows($array, $regex, $col);
		
		$array = Helper::remove_lines($array, $linesToRemove);
		return $array;
	}
	
	/*
		/* ###################################################
		/* fill_from_above_where
		/* ###################################################
	*/
	function fill_from_above_where($array, $pivotCols, $regex) {
		$pivotRows = Helper::find_rows($array, $regex, $pivotCols);
		$array = Helper::fill_from_above($array, $pivotCols, $pivotRows);
		return $array;
	}
	
	/*
		/* ###################################################
		/* remove_if_other_col
		/* ###################################################
	*/
	function remove_if_other_col($array, $checkCol, $removeCol) {
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
	
	/*
		/* ###################################################
		/* melt
		/* ###################################################
	*/
	function melt($array, $colsToSplit, $header) {
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
	
	
	
	/*
		/* ###################################################
		/* result_column_to_array
		/* ###################################################
	*/
	function result_column_to_array($resultSet, $colName) {
		
		$returnArray = array();
		foreach ($resultSet as $result) {
			$returnArray[] = $result -> $colName;
		}
		return $returnArray;
	}
	
	/*
		/* ###################################################
		/* get_array_column
		/* ###################################################
	*/
	function get_array_column($array, $col, $hasHeader) {
		
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
	
	/*
		/* ###################################################
		/* sql_remove_duplicates
		/* ###################################################
	*/
	function sql_remove_duplicates($sqlTable, $ignoreArray = array("id")) {
		
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
	
	/*
		/* ###################################################
		/* sql_convert_dates
		/* ###################################################
	*/
	function sql_convert_dates($sourceId, $tableName) {
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
	
	/*
		/* ###################################################
		/* convert_date
		/* ###################################################
	*/
	function convert_date($date, $type) {
		
		$qArray = array(
		"02" => 'Q1',
		"05" => 'Q2',
		"08" => 'Q3',
		"11" => 'Q4'
		);
		
		switch ($type) {
			case 'dec' :
			$Year = floor($date);
			$remainder = fmod($date, 1);
			$days = floor($remainder * 365.0);
			$interval = new DateInterval("P" . $days . "D");
			$firstDay = date_create($Year . "-01-01");
			$returnDate = date_format(date_add($firstDay, $interval), 'Y-m-d');
			break;
			case 'int' :
			$date = trim($date);
			$returnDate = $date . "-07-01";
			break;
			case 'YMD' :
			$year = trim($date["Year"]);
			$month = Helper::convert_month($date["Month"]);
			$day = trim($date["Day"]);
			$returnDate = $year . "-" . $month . "-" . $day;
			break;
			
			case 'Q' :
			$date = explode(" ", $date);
			$year = trim($date[1]);
			$month = array_search($date[0], $qArray);
			$day = "15";
			
			$returnDate = $year . "-" . $month . "-" . $day;
			
			break;
		}
		return $returnDate;
		
	}
	
	/*
		/* ###################################################
		/* convert_month
		/* ###################################################
	*/
	function convert_month($month) {
		
		if (strlen($month) == 2 || strlen($month) == 0) {
			return $month;
		}
		elseif (strlen($month) == 1) {
			return "0" . $month;
		}
		else {
			$monthNumbers = array(
			'01',
			'02',
			'03',
			'04',
			'05',
			'06',
			'07',
			'08',
			'09',
			'10',
			'11',
			'12'
			);
			$monthNames = array(
			"jan",
			"feb",
			"mar",
			"apr",
			"may",
			"jun",
			"jul",
			"aug",
			"sep",
			"oct",
			"nov",
			"dec"
			);
			$allMonths = array_combine($monthNumbers, $monthNames);
			$month = strtolower(substr($month, 0, 3));
			$newMonth = array_search($month, $allMonths);
			
			if ($newMonth != FALSE) {
				return $newMonth;
			}
			else {
				return "Error";
			}
		}
		
	}
	
	/*
		/* ###################################################
		/* sql_to_barrels_per_day
		/* ###################################################
	*/
	function sql_to_barrels_per_day($sourceId, $dataTable) {
		
		$ORM = ORM::for_table($dataTable) -> where("Source_Id", $sourceId) -> find_many();
		
		$barrel_per_cubic_meter = 1 / 0.158987295;
		$thousand = 1000;
		$one_per_thousand = 1 / 1000;
		$days_per_year = 365;
		$years_per_day = 1 / $days_per_year;
		$months_per_day = 1 / 30.417;
		
		$unitFactors["Thousand Cubic Metres per year"] = $thousand * $barrel_per_cubic_meter * $years_per_day;
		$unitFactors["Thousand barrels per day"] = $thousand;
		$unitFactors["Barrels per day"] = 1;
		$unitFactors["Million barrels per day"] = $thousand * $thousand;
		$unitFactors["Cubic metres per year"] = $barrel_per_cubic_meter * $years_per_day;
		$unitFactors["Thousand Cubic meters per day"] = $thousand * $barrel_per_cubic_meter;
		$unitFactors["Thousand Cubic metres per month"] = $thousand * $barrel_per_cubic_meter * $months_per_day;
		$unitFactors["Billion barrels per year"] = $thousand * $thousand * $thousand * $years_per_day;
		
		$possibleUnits = array_keys($unitFactors);
		
		foreach ($ORM as $row) {
			
			$unit = $row -> Unit;
			$value = $row -> Value;
			
			if (!in_array($unit, $possibleUnits)) {
				// if the unit is omitted, the standard unit should be assumed
				if (trim($unit) == "") {
					$unit = "Barrels per day";
				}
				else {
					$unit = Helper::find_most_similar($unit, $possibleUnits);
				}
			}
			$factor = $unitFactors[$unit];
			
			$row -> Value = $value * $factor;
			$row -> Unit = NULL;
			
			$row -> save();
		}
	}
	
	/*
		/* ###################################################
		/* interpolate_table
		/* ###################################################
	*/
	function interpolate_table($sourceId) {
		
		/* chooses all rows of a given source from the table osdb_data, interpolates the values with a step
			length of one day and inserts these values into osdb_working
			
			since every source may contain series for several different subgrupts (scenario, products or both),
			the interpolation has to be done for each subgroup individually
			
		All columns that are not within $ignoreArray (the standard columns) are considered to contain subgroups */
		$ignoreArray = array(
		"id",
		"Source_Id",
		"Date",
		"Value"
		);
		
		$ORMArray = ORM::for_table('osdb_data') -> where("Source_Id", $sourceId) -> find_array();
		
		// $subGroupHeaders = array_keys(array_filter(reset($ORMArray)));
		$subGroupHeaders = Helper::nonempty_columns($ORMArray);
		
		// creates an array of all non-standard column headers
		$subGroupHeaders = array_diff($subGroupHeaders, $ignoreArray);
		
		// now $subGroupHeaders contains all column-names that contain subgroups
		if (count($subGroupHeaders) > 0) {
			$subGroupArray = Helper::sql_select_columns($ORMArray, $subGroupHeaders, TRUE);
			$subGroupArray = array_unique($subGroupArray, SORT_REGULAR);
			
			Helper::sql_add_columns("osdb_working", $subGroupHeaders);
		}
		else {
			$subGroupArray = array(NULL);
		}
		$workingTableHeaders = Helper::sql_get_columnNames("osdb_working");
		/* each row of $subGroupArray now contains  the names of a different dataset that should or could
			be interpolated
			
		now the interpolation begins */
		foreach ($subGroupArray as $subGroup) {
			$queryArray = array();
			$currentORM = $ORMArray;
			
			/* to create a unique name for the compilation, the ShortName is prepended to a row of unique values */
			$currentCompilationName = ORM::for_table('osdb_sources') -> find_one($sourceId);
			$currentCompilationName = $currentCompilationName -> ShortName;
			
			/* removes all element that are not part of the current Subgroup
			filtering is not required if the table only contains one type of data */
			if ($subGroup != NULL) {
				foreach ($subGroup as $key => $element) {
					// echo $key . " => " . $element . " <br>";
					$currentORM = Helper::filter_for_value($currentORM, $key, $element);
					if ($key == 'Time_Accuracy') {
						$currentCompilationName .= " - " . $element . " months accuracy";
					}
					else {
						$currentCompilationName .= " - " . $element;
					}
				}
			}
			// interpolation is only applicable if there is more than one datapoint to start from
			if (count($currentORM) > 1) {
				
				$currentORM = Helper::sort_by($currentORM, "Date");
				$currentORM = array_values($currentORM);
				
				$firstRow = reset($currentORM);
				$lastRow = end($currentORM);
				$firstRowDate = explode("-", $firstRow["Date"]);
				$lastRowDate = explode("-", $lastRow["Date"]);
				$timePeriod = $firstRowDate[0] . "-" . $lastRowDate[0];
				
				// At the same time, a new Compilation has to be defined for this subgroup
				$newCompilation = ORM::for_table('osdb_compilations') -> create();
				$newCompilation -> Name = $currentCompilationName;
				$newCompilation -> Source_Id = $sourceId;
				$newCompilation -> TimePeriod = $timePeriod;
				$newCompilation -> save();
				$compilationId = $newCompilation -> id();
				
				// since $currentORM is still in the form of [Data_row_Id]=>array(), we only filter out the values
				
				foreach ($currentORM as $rowKey => $row) {
					
					// traverse all rows in the current ORM except for the last
					if ($rowKey != count($currentORM) - 1) {
						$firstDay = date_create_from_format("Y-m-d", $row["Date"]);
						$firstValue = $row["Value"];
						$lastDay = date_create_from_format("Y-m-d", $currentORM[$rowKey + 1]["Date"]);
						$lastValue = $currentORM[$rowKey + 1]["Value"];
						$valueDiff = $lastValue - $firstValue;
						
						$interval = $firstDay -> diff($lastDay);
						$intInterval = intval($interval -> format("%a"));
						
						$currentDay = $firstDay;
						$timeFraction = 0;
						
						while ($currentDay < $lastDay) {
							$newRow = array();
							foreach ($workingTableHeaders as $header) {
								switch($header) {
									case "id" :
									// we let MySQL decide the new id
									$newRow[$header] = "";
									break;
									case "Date" :
									$newRow[$header] = $currentDay -> format('Y-m-d');
									break;
									case "Value" :
									$newRow[$header] = $firstValue + ($timeFraction * $valueDiff);
									break;
									case "Compilation_Id" :
									$newRow[$header] = $compilationId;
									break;
									default :
									$newRow[$header] = $row[$header];
								}
							}
							
							$queryArray[] = $newRow;
							
							// preparing for the next step
							$currentDay = $currentDay -> modify('+ 1 day');
							$timeFraction = $timeFraction + (1 / $intInterval);
						}
					}
				}
				Helper::sql_insert_array($queryArray, "osdb_working");
				
			}
			echo "<br>";
		}
		
	}
	
	/*
		/* ###################################################
		/* sql_add_columns
		/* ###################################################
	*/
	function sql_add_columns($sqlTable, $columns) {
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
	
	/*
		/* ###################################################
		/* filter_for_value
		/* ###################################################
	*/
	function filter_for_value($array, $key, $values) {
		// goes through each element of an 2d-array and returns only those rows where the element $key is $value
		
		if (gettype($values) != "array") {
			$values = array($values);
		}
		
		$newArray = array_filter($array, function($arrayRow) use ($key, $values) {
			return (is_array($arrayRow) && in_array($arrayRow[$key], $values));
		});
		
		return $newArray;
	}
	
	/*
		/* ###################################################
		/* sql_select_columns
		/* ###################################################
	*/
	function sql_select_columns($array, $columns, $alwaysAsArray = FALSE) {
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
		/* ###################################################
		/* sql_get_columnNames
		/* ###################################################
	*/
	function sql_get_columnNames($sqlTable) {
		$dummyRow = ORM::for_table($sqlTable) -> create();
		$dummyRow -> save();
		$dummyRowId = $dummyRow -> id();
		$sqlColumns = array_keys(ORM::for_table($sqlTable) -> find_one() -> as_array());
		
		$dummyRow = ORM::for_table($sqlTable) -> find_one($dummyRowId);
		$dummyRow -> delete();
		
		return $sqlColumns;
	}
	
	/*
		/* ###################################################
		/* add_or_subtract
		/* ###################################################
	*/
	function add_or_subtract($array, $method, $onlyCommonDates = TRUE) {
		$allDates = array();
		foreach ($array as $compilationId => $rowsBelongingToCompilation) {
			$array[$compilationId] = Helper::rebuild_keys($array[$compilationId], "Date");
			// $newDates = Helper::sql_select_columns($rowsBelongingToCompilation, "Date");
			$allDates = array_merge($allDates, array_keys($array[$compilationId]));
			
		}
		$allDates = array_unique($allDates);
		sort($allDates);
		
		$returnArray = array();
		
		foreach ($allDates as $date) {
			$newRow = array();
			foreach ($array as $compilationId => $rowsBelongingToCompilation) {
				if (isset($array[$compilationId][$date])) {
					$newRow[] = $array[$compilationId][$date]["Value"];
				}
				else {
					$newRow[] = NULL;
				}
			}
			if (!$onlyCommonDates || array_filter($newRow) === $newRow) {
				if ($method == "Subtract") {
					for ($i = 1; $i < count($newRow); $i++) {
						$newRow[$i] = -1 * $newRow[$i];
					}
				}
				$returnArray[] = array(
				"Date" => $date,
				"Value" => array_sum($newRow)
				);
			}
		}
		return $returnArray;
	}
	
	/*
		/* ###################################################
		/* concat_time_series
		/* ###################################################
	*/
	function concat_time_series($array) {
		
		$allDates = array();
		$array = call_user_func_array("array_merge", $array);
		$allDates = array_unique(Helper::sql_select_columns($array, "Date"));
		sort($allDates);
		
		$newArray = array();
		foreach ($allDates as $dateKey => $date) {
			$rowsWithRightDate = Helper::filter_for_value($array, "Date", $date);
			
			if (count($rowsWithRightDate) == 1) {
				$rowsWithRightDate = reset($rowsWithRightDate);
				$newArray[] = array(
				"Date" => $date,
				"Value" => $rowsWithRightDate["Value"],
				"Time_Accuracy" => $rowsWithRightDate["Time_Accuracy"]
				);
				
			}
			else {
				$lowestAccuracy = min(Helper::sql_select_columns($rowsWithRightDate, "Time_Accuracy"));
				$rowWithLowestAccuracy = reset(Helper::filter_for_value($rowsWithRightDate, "Time_Accuracy", $lowestAccuracy));
				$newArray[] = array(
				"Date" => $date,
				"Value" => $rowWithLowestAccuracy["Value"],
				"Time_Accuracy" => $rowWithLowestAccuracy["Time_Accuracy"]
				);
			}
		}
		
		return $newArray;
	}
	
	/*
		/* ###################################################
		/* combine_data
		/* ###################################################
	*/
	function combine_data($compilationIdArray, $method, $newName, $changeArray, $onlyCommonDates) {
		
		//creating a subgroup with only the relevant compilations
		foreach ($compilationIdArray as $compilationId) {
			$array[$compilationId] = ORM::for_table("osdb_working") -> order_by_asc('Date') -> where("Compilation_Id", $compilationId) -> find_array();
		}
		
		$firstRow = reset(reset($array));
		$sourceId = $firstRow["Source_Id"];
		
		if (in_array($method, array(
		"Add",
		"Subtract"
		))) {
			$newDateAndValues = Helper::add_or_subtract($array, $method, $onlyCommonDates);
			
		}
		elseif ($method == "Concatenate") {
			$newDateAndValues = Helper::concat_time_series($array);
		}
		
		$firstRow = reset($newDateAndValues);
		$lastRow = end($newDateAndValues);
		$timePeriod = reset(explode("-", $firstRow["Date"])) . "-" . reset(explode("-", $lastRow["Date"]));
		
		$newCompilation = ORM::for_table('osdb_compilations') -> create();
		$newCompilation -> Name = $newName;
		$newCompilation -> Source_Id = $sourceId;
		$newCompilation -> TimePeriod = $timePeriod;
		$newCompilation -> save();
		$newCompilationId = $newCompilation -> id();
		
		$headers = Helper::sql_get_columnNames("osdb_working");
		$changeArray = array_combine($headers, $changeArray);
		$changeArray = array_filter($changeArray);
		
		foreach ($changeArray as $key => $valueToChange) {
			$firstRow[$key] = $valueToChange;
		}
		foreach ($newDateAndValues as $rowKey => $row) {
			foreach ($headers as $headerKey => $header) {
				
				switch ($header) {
					case 'id' :
					$value = "";
					break;
					case 'Compilation_Id' :
					$value = $newCompilationId;
					break;
					case 'Source_Id' :
					$value = $sourceId;
					break;
					case 'Date' :
					$value = $row["Date"];
					break;
					case 'Value' :
					$value = $row["Value"];
					break;
					case 'Time_Accuracy' :
					if ($method == "Concatenate") {
						$value = $row["Time_Accuracy"];
					}
					
					default :
					if (isset($firstRow[$header]) && !isset($value)) {
						$value = $firstRow[$header];
					}
					else {
						$value = NULL;
					}
					
					break;
				}
				
				$newArray[$rowKey][$header] = $value;
			}
		}
		Helper::sql_insert_array($newArray, "osdb_working");
		
	}
	
	/*
		/* ###################################################
		/* shorten_names
		/* ###################################################
	*/
	function shorten_names($inputArray) {
		
		$ORM = ORM::for_table("osdb_synonyms") -> find_array();
		$returnArray = $inputArray;
		
		// flatten array one level
		
		foreach ($ORM as $ORMKey => $ORMRow) {
			$returnArray = str_replace($ORMRow["Synonym"], $ORMRow["Replacement"], $returnArray);
		}
		return $returnArray;
	}
	
	/*
		/* ###################################################
		/* sql_insert_array
		/* ###################################################
	*/
	function sql_insert_array($array, $sqlTable, $maxString = 5000, $updateLog = TRUE) {
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
	
	/*
		/* ###################################################
		/* add_tags
		/* ###################################################
	*/
	function add_tags($compilationIdArray, $tagArray, $newTags) {
		
		$newTags = explode(",", $newTags);
		
		$tagArray = array_merge($tagArray, $newTags);
		$tagArray = array_filter($tagArray);
		foreach ($compilationIdArray as $compilationId) {
			foreach ($tagArray as $tag) {
				$queryArray[] = array(
				"id" => "",
				"Name" => $tag,
				"Compilation_Id" => $compilationId
				);
			}
		}
		Helper::sql_insert_array($queryArray, "osdb_tags");
		Helper::sql_remove_duplicates("osdb_tags");
	}
	
	/*
		/* ###################################################
		/* remove_tags
		/* ###################################################
	*/
	function remove_tags($compilationIdArray, $tagArray) {
		
		foreach ($compilationIdArray as $compilationId) {
			foreach ($tagArray as $tag) {
				ORM::for_table('osdb_tags') -> where('Name', $tag) -> where('Compilation_Id', $compilationId) -> delete_many();
			}
		}
	}
	
	/*
		/* ###################################################
		/* sort_by
		/* ###################################################
		* needs function make_comparer
		
		function sort_by($arrayToSort, $column, $order = SORT_ASC) {
		$array = $arrayToSort;
		usort($array, make_comparer(array(
		$column,
		$order
		)));
		
		return $array;
		}
		
		/*
		/* ###################################################
		/* calculate_errors
		/* ###################################################
	*/
	function calculate_errors($mainId, $compilationId, $startDate, $endDate) {
		
		$mainArray = ORM::for_table("osdb_working") -> where("Compilation_Id", $mainId) -> where_gte("Date", $startDate) -> where_lt("Date", $endDate) -> find_array();
		if (count($mainArray) > 0) {
			$mainArray = Helper::rebuild_keys($mainArray, "Date");
			$mainDates = array_keys($mainArray);
			
			$compArray = ORM::for_table("osdb_working") -> where("Compilation_Id", $compilationId) -> where_gte("Date", $startDate) -> where_lt("Date", $endDate) -> find_array();
			
			if (count($compArray) > 0) {
				$compArray = Helper::rebuild_keys($compArray, "Date");
				$firstRow = reset($compArray);
				$publicationDate = ORM::for_table("osdb_sources") -> find_one($firstRow["Source_Id"]) -> PublicationDate;
				$prognosisDates = Helper::filter_dates($mainDates, $publicationDate);
				if (count($prognosisDates) > 0) {
					$publicationDate = new DateTime($publicationDate);
					
					$errorArray = array();
					foreach ($prognosisDates as $date) {
						if (isset($compArray[$date])) {
							$yRow = $compArray[$date];
							
							$xRow = $mainArray[$date];
							
							$errorRow["Date"] = $xRow["Date"];
							$errorRow["Error"] = $yRow["Value"] - $xRow["Value"];
							if ($xRow["Value"] != 0) {
								$errorRow["ErrorPercentage"] = $errorRow["Error"] / $xRow["Value"];
							}
							else {
								$errorRow["ErrorPercentage"] = "";
							}
							
							$xDate = new DateTime($xRow["Date"]);
							$diff = $xDate -> diff($publicationDate);
							
							$errorRow["Day"] = $diff -> format('%a');
							
							$errorRow["Main_Id"] = $mainId;
							$errorRow["Compilation_Id"] = $compilationId;
							
							$errorArray[] = $errorRow;
						}
					}
					Helper::sql_insert_array($errorArray, "osdb_errors");
				}
				
			}
		}
		
	}
	
	/*
		/* ###################################################
		/* dateRange
		/* ###################################################
	*/
	function dateRange($first, $last, $step = "+1 day", $format = "Y-m-d", $addLast = TRUE) {
		
		$step = date_interval_create_from_date_string($step);
		
		$dates = array();
		$current = date_create_from_format($format, $first);
		$last = date_create_from_format($format, $last);
		
		while ($current <= $last) {
			$dates[] = $current -> format($format);
			$current = date_add($current, $step);
		}
		
		if ($addLast && end($dates) != $last) {
			$dates[] = $last -> format($format);
		}
		
		return $dates;
	}
	
	/*
		/* ###################################################
		/* filter_dates
		/* ###################################################
	*/
	function filter_dates($dates, $constantDate, $after = TRUE) {
		
		$returnDates = array();
		foreach ($dates as $dateToCheck) {
			$dateIsAfter = strtotime($dateToCheck) > strtotime($constantDate);
			if ($after == $dateIsAfter) {
				$returnDates[] = $dateToCheck;
			}
		}
		
		return $returnDates;
	}
	
	/*
		/* ###################################################
		/* rebuild_keys
		/* ###################################################
	*/
	function rebuild_keys($array, $key) {
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
	
	/*
		/* ###################################################
		/* establish_calculation_table
		/* ###################################################
	*/
	function establish_calculation_table($type, $stepLength = 0) {
		
		/* first we establish the queue for the error calculation */
		if ($type == "errors") {
			// number of days that are analyzed in each day. If memory problems appear, reduce the step size
			$stepLength = "+ " . $stepLength . " days";
			
			/* First, create combination Array with all possible
			* combinations of compilations that have to be compared to other mainCompilations */
			
			$compilationIdArray = Helper::sql_select_columns(ORM::for_table('osdb_tags') -> distinct() -> where("Name", "analyzed") -> find_array(), "Compilation_Id");
			$mainCompIdArray = Helper::sql_select_columns(ORM::for_table('osdb_tags') -> distinct() -> where("Name", "Basis") -> find_array(), "Compilation_Id");
			
			/* Now, create a $combinationArray that contains all possible combinations of a mainCompilation (the reference)
			* and a normal compilation */
			$combinationArray = array();
			foreach ($mainCompIdArray as $mainId) {
				foreach ($compilationIdArray as $compId) {
					$combinationArray[] = array(
					$mainId,
					$compId
					);
				}
			}
			/* Now, create an array allPossibleDates that divides the whole time series into
			* shorter steps to minimize calculation costs for each step */
			foreach ($compilationIdArray as $key => $compilationId) {
				$newFirstDate[$key] = Helper::sql_select_columns(ORM::for_table("osdb_working") -> where("Compilation_Id", $compilationId) -> order_by_asc('Date') -> find_one() -> as_array(), "Date");
				$newLastDate[$key] = Helper::sql_select_columns(ORM::for_table("osdb_working") -> where("Compilation_Id", $compilationId) -> order_by_desc('Date') -> find_one() -> as_array(), "Date");
			}
			sort($newFirstDate);
			sort($newLastDate);
			$firstDate = reset($newFirstDate);
			$lastDate = end($newLastDate);
			$allPossibleDates = Helper::dateRange($firstDate["Date"], $lastDate["Date"], $stepLength);
			for ($i = 1; $i < count($allPossibleDates); $i++) {
				$inputArray = array();
				$startDate = $allPossibleDates[$i - 1];
				$endDate = $allPossibleDates[$i];
				foreach ($combinationArray as $combination) {
					$inputArray[] = array(
					"type" => "errors",
					"startDate" => $startDate,
					"endDate" => $endDate,
					"mainCompId" => $combination[0],
					"compId1" => $combination[1],
					"compId2" => "",
					"Day" => ""
					);
				}
				Helper::sql_insert_array($inputArray, "osdb_errors_to_calculate");
			}
		}
		/* # statistics ################################################## */
		
		elseif ($type == "statistics") {
			/* secondly we establish the queue for the performance statistics of each compilation in comparison to
			* the "real" values  */
			
			$combinationIdArray = ORM::for_table('osdb_errors') -> distinct() -> select_many("Main_Id", "Compilation_Id") -> find_array();
			/* now we'll try to find the maximum of computable days for each error series.
			* These will be the "categories"*/
			foreach ($combinationIdArray as $combination) {
				$maxDay = ORM::for_table('osdb_errors') -> where("Main_Id", $combination["Main_Id"]) -> where("Compilation_Id", $combination["Compilation_Id"]) -> order_by_desc('Day') -> find_one();
				$maxDayArray[] = $maxDay -> Day;
			}
			$maxDayArray = array_unique($maxDayArray);
			sort($maxDayArray);
			$ini_array = parse_ini_file("config.ini");
			
			$maxDayArray = Helper::create_relevant_day_array($maxDayArray);
			$ini_array["maxDayArray"] = implode(",", $maxDayArray);
			Helper::write_to_config($ini_array);
			
			$mainIdArray = array_unique(Helper::sql_select_columns($combinationIdArray, "Main_Id"));
			
			foreach ($mainIdArray as $key => $mainCompId) {
				$validErrorCompilations = Helper::filter_for_value($combinationIdArray, "Main_Id", $mainCompId);
				$validErrorCompilations = Helper::sql_select_columns($validErrorCompilations, "Compilation_Id");
				$validErrorCompilations = array_unique($validErrorCompilations);
				$compilationsToCompare = Helper::create_matchings($validErrorCompilations);
				$inputArray = array();
				
				/* now all possible combinations of two compilations
				* are compared against each other, like in a tournament */
				foreach ($compilationsToCompare as $combination) {
					/* we'll find out which is the maximum amount of days that these two compilations can
					* be compared against each other */
					$compId1 = $combination[0];
					$compId2 = $combination[1];
					$maxDay1 = ORM::for_table('osdb_errors') -> where("Main_Id", $mainCompId) -> where("Compilation_Id", $compId1) -> order_by_desc('Day') -> find_one() -> Day;
					$maxDay2 = ORM::for_table('osdb_errors') -> where("Main_Id", $mainCompId) -> where("Compilation_Id", $compId2) -> order_by_desc('Day') -> find_one() -> Day;
					$maxDay = min($maxDay1, $maxDay2);
					$inputArray[] = array(
					"type" => "statistics",
					"startDate" => "",
					"endDate" => "",
					"mainCompId" => $mainCompId,
					"compId1" => $compId1,
					"compId2" => $compId2,
					"Day" => $maxDay
					);
					echop(reset($inputArray));
				}
				Helper::sql_insert_array($inputArray, "osdb_errors_to_calculate");
			}
			
		}
	}
	
	/*
		/* ###################################################
		/* calculate_ranking
		/* ###################################################
	*/
	function calculate_ranking($mainCompId, $compId1, $compId2, $Day) {
		/* evaluates the errors in the table "errors" and calculates a mean differential and some other
			* statistical values for a certain array of days.
			* The results are entered into osdb_ranking
			*
			* See chapter "Comparing prognoses" in  https://github.com/fridde/PerformanceRecordsArticle
		* */
		$ini_array = parse_ini_file("config.ini");
		$allRelevantDays = explode(",", $ini_array["maxDayArray"]);
		
		$firstCompilation = ORM::for_table('osdb_errors') -> where("Main_Id", $mainCompId) -> where("Compilation_Id", $compId1) -> order_by_asc('Day') -> find_array();
		$firstCompilation = Helper::rebuild_keys($firstCompilation, "Day");
		$secondCompilation = ORM::for_table('osdb_errors') -> where("Main_Id", $mainCompId) -> where("Compilation_Id", $compId2) -> order_by_asc('Day') -> find_array();
		$secondCompilation = Helper::rebuild_keys($secondCompilation, "Day");
		
		$today = 0;
		$errorDiff = array();
		$arrayToAdd = array();
		while ($today <= $Day) {
			$today++;
			if (isset($firstCompilation[$today]) && isset($secondCompilation[$today])) {
				$errorDiff[$today] = pow($firstCompilation[$today]["ErrorPercentage"], 2) - pow($secondCompilation[$today]["ErrorPercentage"], 2);
			}
			// below are default values
			$meanDifferential = 0;
			$errorStatistic = 0;
			if (count($errorDiff) > 0) {
				$meanDifferential = array_sum($errorDiff) / count($errorDiff);
				$autocovariance = Helper::autocovariance($errorDiff);
				if ($autocovariance != 0) {
					$errorStatistic = $meanDifferential / sqrt($autocovariance);
				}
			}
			
			if (in_array($today, $allRelevantDays) && $meanDifferential != 0 && $errorStatistic != 0) {
				$arrayToAdd[] = array(
				"Main_Id" => $mainCompId,
				"Compilation_1" => $compId1,
				"Compilation_2" => $compId2,
				"Day" => $today,
				"Mean_Differential" => $meanDifferential,
				"ErrorStatistic" => $errorStatistic
				);
			}
		}
		if (count($arrayToAdd) > 0) {
			Helper::sql_insert_array($arrayToAdd, "osdb_ranking");
		}
	}
	
	/*
		/* ###################################################
		/* autocovariance
		/* ###################################################
	*/
	function autocovariance($array, $stepSize = 1) {
		$array = array_values($array);
		$mean = array_sum($array) / count($array);
		$sum = 0;
		for ($i = 0; $i < count($array) - $stepSize; $i++) {
			$sum += ($array[$i] - $mean) * ($array[$i + $stepSize] - $mean);
		}
		$covariance = $sum / count($array);
		return $covariance;
	}
	
	/*
		
		/*
		/* ###################################################
		/* remove_source_from_database
		/* ###################################################
	*/
	function remove_source_from_database($sourceId, $archive = TRUE) {
		
		$compilations = ORM::for_table("osdb_working") -> where("Source_Id", $sourceId) -> select("Compilation_Id") -> find_array();
		$compilations = array_unique($compilations, SORT_REGULAR);
		$tables = array(
		"compilations" => "Source_Id",
		"data" => "Source_Id",
		"errors" => array(
		"Main_Id",
		"Compilation_Id"
		),
		"ranking" => array(
		"Main_Id",
		"Compilation_1",
		"Compilation_2"
		),
		"tags" => "Compilation_Id",
		"working" => array(
		"Compilation_Id",
		"Source_Id"
		)
		);
		
		foreach ($tables as $tableName => $columns) {
			if (gettype($columns) == "string") {
				$columns = array($columns);
			}
			foreach ($columns as $columnName) {
				if (in_array($columnName, array(
				"Main_Id",
				"Compilation_Id",
				"Compilation_1",
				"Compilation_2"
				))) {
					foreach ($compilations as $compilationId) {
						$compilationId = $compilationId["Compilation_Id"];
						ORM::for_table('osdb_' . $tableName) -> where_equal($columnName, $compilationId) -> delete_many();
					}
				}
				else {
					ORM::for_table('osdb_' . $tableName) -> where_equal($columnName, $sourceId) -> delete_many();
				}
			}
		}
		if ($archive) {
			$source = ORM::for_table("osdb_sources") -> find_one($sourceId);
			$source -> Archived = 1;
			$source -> save();
		}
		
	}
	
	/*
		/* ###################################################
		/* remove_compilation_from_database
		/* ###################################################
	*/
	function remove_compilation_from_database($compilationId) {
		
		$tables = array(
		"compilations" => "id",
		"errors" => "Compilation_Id",
		"ranking" => array(
		"Main_Id",
		"Compilation_1",
		"Compilation_2"
		),
		"tags" => "Compilation_Id",
		"working" => "Compilation_Id"
		);
		
		foreach ($tables as $tableName => $columns) {
			if (gettype($columns) == "string") {
				$columns = array($columns);
			}
			foreach ($columns as $columnName) {
				ORM::for_table('osdb_' . $tableName) -> where_equal($columnName, $compilationId) -> delete_many();
			}
		}
	}
	
	/*
		/* ###################################################
		/* create_matchings
		/* ###################################################
	*/
	function create_matchings($array) {
		/* creates a 1-on-1 matching for each element in the array, so that every element is paired with each element once  */
		$returnArray = array();
		foreach ($array as $firstNumber) {
			foreach ($array as $secondNumber) {
				if ($firstNumber < $secondNumber) {
					$arrayToAdd = array(
					$firstNumber,
					$secondNumber
					);
				}
				else {
					$arrayToAdd = array(
					$secondNumber,
					$firstNumber
					);
				}
				if ($firstNumber != $secondNumber && !in_array($arrayToAdd, $returnArray)) {
					$returnArray[] = $arrayToAdd;
				}
			}
		}
		return $returnArray;
	}
	
	/*
		/* ###################################################
		/* array_to_csv_download
		/* ###################################################
	*/
	function array_to_csv_download($array, $filename = "export.csv", $delimiter = ";") {
		
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
	
	/*
		/* ###################################################
		/* create_download
		/* ###################################################
	*/
	function create_download($source, $filename = "export.csv") {
		
		$textFromFile = file_get_contents($source);
		$f = fopen('php://memory', 'w');
		fwrite($f, $textFromFile);
		fseek($f, 0);
		
		header('Content-Type: text/plain');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		// make php send the generated csv lines to the browser
		fpassthru($f);
	}
	
	/*
		/* ###################################################
		/* find_matching_project
		/* ###################################################
	*/
	function find_matching_project($projectNameArray, $format = "I Y - P - C P") {
		/* tries to find the best matching Oil Sand Project for an array of project names.
			* The "official" project List is in a SQL-Table called osdb_projects
		* the format abbreviations are Institution Year - Product - Company Project */
		
		if (gettype($projectNameArray) != "array") {
			$projectNameArray = array($projectNameArray);
		}
		
		$projectTable = ORM::for_table("osdb_projects") -> find_array();
		$projectTable = Helper::rebuild_keys($projectTable, "id");
		
		switch($format) {
			case "I Y - P - C P" :
			foreach ($projectNameArray as $key => $value) {
				$value = explode("-", $value);
				if (count($value) == 3) {
					$projectNameArray[$key] = trim($value[2]);
				}
			}
			foreach ($projectTable as $key => $projectRow) {
				$projectTable[$key]["SearchFor"] = $projectRow["Company"] . " " . $projectRow["Project"];
			}
			break;
		}
		
		$searchFor = Helper::sql_select_columns($projectTable, "SearchFor");
		
		$returnArray = array();
		
		foreach ($projectNameArray as $projectName) {
			
			$bestMatch = Helper::find_most_similar($projectName, $searchFor, FALSE);
			$matchingRow = Helper::filter_for_value($projectTable, "SearchFor", $bestMatch);
			
			if (count($matchingRow) == 1) {
				$returnArray[] = $matchingRow;
			}
			else {
				$returnArray[] = array();
			}
			
		}
		return $returnArray;
		
	}
	
	/*
		/* ###################################################
		/* write_to_config
		/* ###################################################
	*/
	function write_to_config($configArray) {
		$filename = "config.ini";
		$text = "";
		foreach ($configArray as $key => $value) {
			if (gettype($value) == "array") {
				$value = implode(",", $value);
			}
			$text .= $key . " = " . $value . "\r\n";
		}
		
		$fh = fopen($filename, "w") or die("Could not open log file.");
		fwrite($fh, $text) or die("Could not write file!");
		fclose($fh);
	}
	
	function create_relevant_day_array($dayArray) {
		
		$returnArray = array(reset($dayArray));
		foreach ($dayArray as $day) {
			$difference = $day - end($returnArray);
			$cond1 = $day < 100 && $difference > 30;
			$cond2 = $day < 1000 && $difference > 300;
			$cond3 = $day >= 1000 && $difference > 600;
			
			if ($cond1 || $cond2 || $cond3) {
				$returnArray[] = $day;
			}
		}
		
		return $returnArray;
		
	}
	
	
	
	/*
		/* ###################################################
		/* find_most_similar
		/* ###################################################
	*/
	function find_most_similar($needle, $haystack, $alwaysFindSomething = TRUE) {
		
		if ($alwaysFindSomething) {
			$bestWord = reset($haystack);
			similar_text($needle, $bestWord, $bestPercentage);
		}
		else {
			$bestWord = "";
			$bestPercentage = 0;
		}
		
		foreach ($haystack as $key => $value) {
			similar_text($needle, $value, $thisPercentage);
			
			if ($thisPercentage > $bestPercentage) {
				$bestWord = $value;
				$bestPercentage = $thisPercentage;
			}
		}
		return $bestWord;
	}
	
	function logg($data, $infoText = "", $filename = "logg.txt") {
		
		$string = "\n--------------------------------\n";
		$string .= date("Y-m-d H:i:s") . "\n";
		$string .= "####" . $infoText;
		$string .= "\n--------------------------------\n";
		
		if (is_string($data)) {
			$string .= $data;
			} elseif (is_array($data)) {
			$string .= print_r($data, TRUE);
			} else {
			$string .= var_export($data, TRUE);
		}
		$string .= "\n----------------------------\n";
		
		file_put_contents($filename, $string, FILE_APPEND);
	}
	
	function remove_this_function($sqlTable, $criteria = "", $headers = "all", $debug = FALSE) {
		
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
		
		// set the resulting array to associative
		$queryResult = $stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		$resultArray = array();
		foreach ($stmt->fetchAll() as $row) {
			$resultArray[] = $row;
		}
		$conn = null;
		
		return $resultArray;
	}
	
	/*
		/* ###################################################
		/* array_select_where
		/* ###################################################
	*/
	
	/* will search a 2-d array for rows where the criteria is matched, mimicking a sql-select-where statement
		
		the criteria is given as an array in the form array("relevantColumn1" => "neededValue1", ...)
		
		the negationArray contains all the columns where the criteria must NOT be matched
		
	*/
	function array_select_where($array, $criteria, $headers = "all", $onlyFirst = FALSE){
		
		$returnArray = array();
		foreach($array as $index => $row){
			$rowIncluded = TRUE;
			
			foreach($criteria as $criteriumToCheck => $valueToCheck){
				if(strtolower(substr($valueToCheck, 0, 4)) == "not:"){
					$valueToCheck = substr($valueToCheck, 4);
					if($row[$criteriumToCheck] == $valueToCheck){
						$rowIncluded = FALSE;
					}
					
					} else {
					if($row[$criteriumToCheck] != $valueToCheck){
						$rowIncluded = FALSE;
					}
				}
			}
			if($rowIncluded){
				$returnArray[$index] = $row;
			}
		}
		
		if($onlyFirst){
			$returnValue = reset($returnArray);
			} else {
			$returnValue = $returnArray;
		}
		
		return $returnValue;
	}
	
	function activate_all_errors(){
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
	}
	
	function DMStoDEC($deg,$min,$sec){
		
		// Converts DMS ( Degrees / minutes / seconds )
		// to decimal format longitude / latitude
		
		return $deg+((($min*60)+($sec))/3600);
	}
	
	function DECtoDMS($dec)
	{
		
		// Converts decimal longitude / latitude to DMS
		// ( Degrees / minutes / seconds )
		
		// This is the piece of code which may appear to
		// be inefficient, but to avoid issues with floating
		// point math we extract the integer part and the float
		// part by using a string function.
		
		$vars = explode(".",$dec);
		$deg = $vars[0];
		$tempma = "0.".$vars[1];
		
		$tempma = $tempma * 3600;
		$min = floor($tempma / 60);
		$sec = $tempma - ($min*60);
		
		return array("deg"=>$deg,"min"=>$min,"sec"=>$sec);
	}
	
	function array_walk_values($array, $function){
		/* will return an array where a function that accepts a single parameter has been applied
		it's practically a simplification of array_walk. Note that it returns a value!*/
		
		$returnArray = array();
		foreach($array as $index => $value){
			$returnArray[$index] = $function($value);
		}
		
		return $returnArray;
	}
	
	function array_choose_columns($array, $columns, $remove = FALSE){
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
	
	function generateRandomString($length = 10) {
		$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	
	function echo_tag(){
		
		$tagArray = func_get_args();
		$returnString = "";
		
		foreach($tagArray as $tag){
			$returnString .= "<" . $tag . ">";
		}
		echo $returnString;
		
	}
	
	function extract_request($translationArray = array(), $prefix = "req_"){
		global $_REQUEST;
		$returnArray = array();
		
		$newTranslationArray = array();
		foreach($translationArray as $key => $value){
			if(gettype($key) == "integer"){
				$newTranslationArray[$value] = $value;
			}
			else {
				$newTranslationArray[$key] = $value;
			}
		}
		$translationArray = $newTranslationArray;
		
		foreach($_REQUEST as $key => $value){
			if(isset($translationArray[$key])){
				$varName = $translationArray[$key];
			}
			else {
				$varName = $prefix . $key;
			}
			$returnArray[$varName] = $value;
		}
		foreach($translationArray as $key => $value){
			if(!isset($returnArray[$value])){
				$returnArray[$value] = FALSE;
			}
		}
		
		return $returnArray;
		
	}
	
	function add_hidden_fields($array){
		
		/* will return hidden fields. The array has to have the names given as array-keys and the values given as array-values */
		$output = "";
		foreach($array as $name => $value){
			$output .= qtag("hidden", $value, $name);
		}
		return $output;
	}
	
	function array_orderby(){
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
	
	function tag($tagName, $content = "", $attributes = array(), $id = ""){
		
		/* args: string $tagName, [string $content, array $attributes OR string $class]
			
		*/
		/* these elements don't need to be closed*/
		$void_elements = array("area","base","br","col","command","embed","hr","img","input","link","meta","param","source");
		/* these elements will be included in case no attribute-array was given*/
		
		$close = substr($tagName, 0, 1) == '/';
		
		$output = '<' . $tagName;
		if(!$close){
			$atts = array();
			if(gettype($attributes) == "string"){
				$atts["class"] = $attributes;
				if($id != ""){
					$atts["id"] = $id;
				}
			}
			elseif(gettype($attributes) == "array" && count($attributes) > 0){
				$atts = $attributes;
			}
			
			foreach($atts as $att => $attVal){
				if(is_int($att)){
					$output .= ' ' . $attVal;
				}
				else {
					$output .= ' ' . $att . '="' . $attVal . '"';
				}
			}
			$output .= '>';
			
			if(!in_array($tagName, $void_elements)){
				$output .= $content . '</' . $tagName . '>';
			}
		}
		
		return $output;
	}
	
	function qtag(){
		/* will create a html-tag and chose from a set of standard variables
			// argument 1 has to be the type, the rest of the arguments will be interpreted according to the standard
			specified in the switch-case
		*/
		$maxNumberOfArgs = 10;
		
		$args =  func_get_args();
		$arguments = $args;
		$args = array(); // we want to preserve $args, but as an array with exactly $maxNumberOfArgs arguments
		/* creates a number of variables called arg1, arg2, ..., arg9 (if $maxNumberOfArgs = 10)*/
		for($i = 0; $i < $maxNumberOfArgs ; $i++){
			$argname = "arg" . $i;
			$$argname = array_shift($arguments);
			$$argname = (is_null($$argname) ? FALSE : $$argname);
			$args[] = $$argname;
		}
		$pseudoTag = ($arg0 ? $arg0 : "");
		$tagName = $pseudoTag;
		$atts = array();
		$content = "";
		$additionalText = "";
		
		switch($pseudoTag){
			case "textinput":
			$tagName = "input";
			$atts["type"] = "text";
			if($arg1){$atts["name"] = $arg1;}
			if($arg2){$atts["placeholder"] = $arg2;}
			if($arg3){$atts["class"] = $arg3;}
			if($arg4){$atts["value"] = $arg4;}
			if($arg5){$atts["id"] = $arg5;
				if($arg6){
					$additionalText = tag("label", array("for" => $arg4), $arg5);
				}
			}
			break;
			
			case "meta":
			if($arg1){
				$atts = $arg1;
			}
			else {
				$atts = array("http-equiv" => "Content-Type", "content" => "text/html; charset=UTF-8");
			}
			break;
			
			case "hidden":
			$tagName = "input";
			$atts[] = "hidden";
			if($arg1){$atts["value"] = $arg1;}
			if($arg2){$atts["name"] = $arg2;}
			if($arg3){$atts["id"] = $arg3;}
			
			break;
			
			case "submit":
			$tagName = "input";
			$atts["type"] = "submit";
			if($arg1){$atts["value"] = $arg1;}
			break;
			
			case "checkbox":
			$tagName = "input";
			$atts["type"] = "checkbox";
			if($arg1){
			$atts["name"] = $arg1 . '[]';} //should be serealized
			if($arg2){$atts["value"] = $arg2;}
			if($arg3){$atts[] = "checked";}
			
			break;
			
			case "div":
			if($arg1){$content = $arg1;}
			if($arg2){$atts["class"] = $arg2;}
			if($arg3){$atts["id"] = $arg3;}
			break;
			
			case "nav":
			$nav_args = array_slice($args, 1);
			$navBarOutput = create_bootstrap_navbar($nav_args);
			$content = $navBarOutput["content"];
			$atts = $navBarOutput["attributes"];
			break;
			
			case "a":
			if($arg1){$content = $arg1;}
			if($arg2){$atts["href"] = $arg2;}
			if($arg3){$atts["class"] = $arg3;}
			if($arg4){$atts["id"] = $arg4;}
			break;
			
			case "uicon":
			$tagName = "span";
			$atts["class"] = "ui-icon ui-icon-" . $arg1;
			if($arg2){$atts["id"] = $arg2;}
			
			break;
			
			case "fa": //font-awesome
			$tagName = "i";
			$atts["class"] = "fa fa-" . $arg1;
			if($arg2){$atts["class"] .= " fa-" . $arg2;}  //size
			if($arg3){$atts["id"] = $arg3;}
			break;
			
			case "tabs":
			$tab_args = array_slice($args, 1);
			$tabOutput = create_bootstrap_tabs($tab_args);
			$tagName = "div";
			$content = $tabOutput["content"];
			$atts = $tabOutput["attributes"];
			break;
			
			default:
			if($pseudoTag == ""){
				return "ERROR: You must at least provide ONE argument to the function qtag()";
			}
			else {
				if($arg1){$content = $arg1;}
				if($arg2){$atts["class"] = $arg2;}
				if($arg3){$atts["id"] = $arg3;}
				
			}
			break;
			
		}
		return tag($tagName, $content, $atts) . $additionalText;
		
	}
	
	function create_bootstrap_navbar($nav_args){
		/* will return an array with a the matching arguments for a bootstrap-navbar
			the incoming arguments should be given as following
			0: (string) type of navbar. Possible types: "" (for default), fixed (for fixed header)
			1: (array) links: in the form of "Name to Show" => "link to lead to"
			If a menu-item should have a dropdown instead, build a recursive array, e.g. array("Homepage" => "index.html", "Topics" => array("Cars" => "cars.html", "Horses" => "horses.html"), "About me" => "about.html")
			If your navbar should contain a left and right menu, the link-array should contain exactly two arrays with the keys given as LEFT and RIGHT
			2: (string) id of the navbar
			3: (array) header of the site given as a double
		*/
		$type = $nav_args[0];
		$links = $nav_args[1];
		$id = $nav_args[2];
		$headerArray = $nav_args[3];
		$attributes = array("class" => "navbar");
		if($id){$attributes["id"] = $id;}
		
		switch($type){
			case "fixed":
			$attributes["class"] .= " navbar-default navbar-fixed-top";
			break;
			
			default:
			$attributes["class"] .= " navbar-default";
			break;
		}
		
		$header = "";
		if($headerArray){
			$displayName = array_keys($headerArray);
			$displayName = $displayName[0];
			$link = $headerArray[$displayName];
			$header .= tag("a", $displayName, array("href" => $link, "class" => "navbar-brand"));
		}
		$linkContent = array("LEFT" => "", "RIGHT" => "");
		if(!(count($links) == 2 && isset($links["LEFT"]) && isset($links["RIGHT"]))){
			$links = array("LEFT" => $links, "RIGHT" => array());
		}
		
		foreach($links as $side => $linkList){
			foreach($linkList as $showName => $link){
				if(gettype($link) == "array"){
					$dd_preText = tag("a", $showName . qtag("span", "" , "caret"), array("class" => "dropdown-toggle", "data-toggle"=> "dropdown", "href" => "#"));
					$dd_menu = "";
					foreach($link as $ddShowName => $dropdownListLink){
						$a = qtag("a", $ddShowName, $dropdownListLink);
						$l = tag("li", $a);
						$dd_menu .= $l;
					}
					$dd_list = qtag("ul", $dd_menu ,"dropdown-menu");
					$l = tag("li", $dd_preText . $dd_list, "dropdown");
					$linkContent[$side] .= $l;
				}
				else {
					$a = qtag("a", $showName, $link);
					$l = tag("li", $a);
					$linkContent[$side] .= $l;
				}
			}
		}
		
		
		$navbarContent = qtag("ul", $linkContent["LEFT"] , "nav navbar-nav");
		if($linkContent["RIGHT"] != ""){
			$navbarContent .= qtag("ul", $linkContent["RIGHT"], "nav navbar-nav navbar-right");
		}
		$div0_1 = qtag("div", $header, "navbar-header");
		$div0_2 = qtag("div", $navbarContent);
		$div0 = qtag("div", $div0_1 . $div0_2, "container-fluid");
		$content = $div0;
		$resultArray = array("content" => $content, "attributes" => $attributes);
		return $resultArray;
		
		
	}
	
	function create_bootstrap_tabs($tab_args){
		
		$type = $tab_args[0]; // yet unused
		$tabContent = $tab_args[1];
		$id = $tab_args[2];
		$attributes = array("class" => "container");
		if($id){$attributes["id"] = $id;}
		
		list($content, $ul, $contentDiv) = array_fill(0,20,"");
		$i = 0;
		$firstElement = get_element($tabContent);
		foreach($tabContent as $showName => $text){
			$i++;
			$tab_id = "tab_id_" . $i;
			$liAtts = array();
			$contentElementAtts = array("id" => $tab_id, "class" => "tab-pane fade");
			if($showName == $firstElement){
				$liAtts["class"] = "active";
				$contentElementAtts["class"] .= " in active";
			}
			$li = tag("a", $showName, array("data-toggle" => "tab", "href" => "#" . $tab_id));
			$ul .= tag("li", $li, $liAtts);
			
			$contentDiv .= tag("div", $text, $contentElementAtts);
		}
		$content .= qtag("ul", $ul, "nav nav-tabs");
		
		$content .= qtag("div", $contentDiv, "tab-content");
		
		$resultArray = array("content" => $content, "attributes" => $attributes);
		return $resultArray;
	}
	
	function get_element($array, $type = "index", $number = 0){
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
	
	
	class HTML extends DOMDocument{
		function __construct(){
			parent::__construct('1.0','iso-8859-1' );
			$this->formatOutput = true;
		}
		
		public  function saveHTML(){
			return  html_entity_decode(parent::saveHTML());
		}
		
		public function create(){
			
			$args = func_get_args();
			$content = (isset($args[1]) ? $args[1] : "");
			$attributes = (isset($args[2]) ? $args[2] : array());
			$element = $this->createElement($args[0], $content);
			
			foreach($attributes as $attName => $attValue){
				$element->setAttribute($attName, $attValue);
			}
			$this->appendChild($element);
			
			return $element;
		}
		
		public function button(){
			$temp= $this->createElement('input');
			$temp->setAttribute('type','button');
			return $temp;
		}
	}
