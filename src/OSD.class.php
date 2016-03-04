<?php 
	/**
		* A class containing functions to make the Oil Sands Database work.
		*
		* [Description]
	*/ 
	
	namespace Fridde;
	/**
		* SUMMARY OF check_inclusion_according_to_tag
		*
		* DESCRIPTION
		*
		* @param TYPE ($row, $tags, $givenPasswords, $truePasswords) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function check_inclusion_according_to_tag($row, $tags, $givenPasswords, $truePasswords)
	{
		
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
	/**
		* SUMMARY OF calculate_frequencies
		*
		* DESCRIPTION
		*
		* @param TYPE ($wordArray) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function calculate_frequencies($wordArray)
	{
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
	/**
		* SUMMARY OF power_perms
		*
		* DESCRIPTION
		*
		* @param TYPE ($arr) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function power_perms($arr)
	{
		
		$power_set = power_set($arr);
		$result = array();
		foreach ($power_set as $set) {
			$perms = perms($set);
			$result = array_merge($result, $perms);
		}
		return $result;
	}
	/**
		* SUMMARY OF power_set
		*
		* DESCRIPTION
		*
		* @param TYPE ($in, $minLength = 1) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function power_set($in, $minLength = 1)
	{
		
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
	/**
		* SUMMARY OF factorial
		*
		* DESCRIPTION
		*
		* @param TYPE ($int) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function factorial($int)
	{
		if ($int < 2) {
			return 1;
		}
		
		for ($f = 2; $int - 1 > 1; $f *= $int--);
		
		return $f;
	}
	/**
		* SUMMARY OF perm
		*
		* DESCRIPTION
		*
		* @param TYPE ($arr, $nth = null) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function perm($arr, $nth = null)
	{
		
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
	/**
		* SUMMARY OF perms
		*
		* DESCRIPTION
		*
		* @param TYPE ($arr) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function perms($arr)
	{
		$p = array();
		for ($i = 0; $i < factorial(count($arr)); $i++) {
			$p[] = perm($arr, $i);
		}
		return $p;
	}
	/**
		* SUMMARY OF make_comparer
		*
		* DESCRIPTION
		*
		* @param TYPE () ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	
	function make_comparer()
	{
		// Normalize criteria up front so that the comparer finds everything tidy
		$criteria = func_get_args();
		foreach ($criteria as $index => $criterion) {
			$criteria[$index] = is_array($criterion) ? array_pad($criterion, 3, null) : array(
			$criterion,
			SORT_ASC,
			null
			);
		}
		
		return function($first, $second) use (&$criteria)
		{
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
	/**
		* SUMMARY OF parse
		*
		* DESCRIPTION
		*
		* @param TYPE ($rawText, $code) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function parse($rawText, $code)
	{
		
		$text = $rawText;
		
		call_user_func($code, $text);
		
		return $text;
	}
	/**
		* SUMMARY OF html_to_csv
		*
		* DESCRIPTION
		*
		* @param TYPE ($htmlString, $number = 0) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	
	
	
	
	function html_to_csv($htmlString, $number = 0)
	{
		
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
	/**
		* SUMMARY OF convert_project_plan
		*
		* DESCRIPTION
		*
		* @param TYPE ($array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	
	function convert_project_plan($array)
	{
		
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
	/**
		* SUMMARY OF find_phase_keys
		*
		* DESCRIPTION
		*
		* @param TYPE ($array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function find_phase_keys($array)
	{
		
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
	/**
		* SUMMARY OF is_phase
		*
		* DESCRIPTION
		*
		* @param TYPE ($string) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function is_phase($string)
	{
		
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
	/**
		* SUMMARY OF find_project_keys
		*
		* DESCRIPTION
		*
		* @param TYPE ($array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function find_project_keys($array)
	{
		
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
	/**
		* SUMMARY OF is_project_owner
		*
		* DESCRIPTION
		*
		* @param TYPE ($string) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function is_project_owner($string)
	{
		
		return (preg_match("~\d%\)~", $string) == 1);
	}
	
	/*
		
	*/
	/**
		* SUMMARY OF is_project_date
		*
		* DESCRIPTION
		*
		* @param TYPE ($string) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function is_project_date($string)
	{
		
		return (preg_match("~[[:upper:]]{1}[[:lower:]]{2}[[:blank:]]{1}\d{4}:~", $string) == 1);
	}
	/**
		* SUMMARY OF convert_date
		*
		* DESCRIPTION
		*
		* @param TYPE ($date, $type) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function convert_date($date, $type)
	{
		
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
		
	*/
	/**
		* SUMMARY OF convert_month
		*
		* DESCRIPTION
		*
		* @param TYPE ($month) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function convert_month($month)
	{
		
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
	/**
		* SUMMARY OF sql_to_barrels_per_day
		*
		* DESCRIPTION
		*
		* @param TYPE ($sourceId, $dataTable) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function sql_to_barrels_per_day($sourceId, $dataTable)
	{
		
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
	/**
		* SUMMARY OF interpolate_table
		*
		* DESCRIPTION
		*
		* @param TYPE ($sourceId) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function interpolate_table($sourceId)
	{
		
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
	/**
		* SUMMARY OF add_or_subtract
		*
		* DESCRIPTION
		*
		* @param TYPE ($array, $method, $onlyCommonDates = TRUE) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function add_or_subtract($array, $method, $onlyCommonDates = TRUE)
	{
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
		
	*/
	/**
		* SUMMARY OF concat_time_series
		*
		* DESCRIPTION
		*
		* @param TYPE ($array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function concat_time_series($array)
	{
		
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
		
	*/
	/**
		* SUMMARY OF combine_data
		*
		* DESCRIPTION
		*
		* @param TYPE ($compilationIdArray, $method, $newName, $changeArray, $onlyCommonDates) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function combine_data($compilationIdArray, $method, $newName, $changeArray, $onlyCommonDates)
	{
		
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
		
	*/
	/**
		* SUMMARY OF shorten_names
		*
		* DESCRIPTION
		*
		* @param TYPE ($inputArray) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function shorten_names($inputArray)
	{
		
		$ORM = ORM::for_table("osdb_synonyms") -> find_array();
		$returnArray = $inputArray;
		
		// flatten array one level
		
		foreach ($ORM as $ORMKey => $ORMRow) {
			$returnArray = str_replace($ORMRow["Synonym"], $ORMRow["Replacement"], $returnArray);
		}
		return $returnArray;
	}
	/**
		* SUMMARY OF add_tags
		*
		* DESCRIPTION
		*
		* @param TYPE ($compilationIdArray, $tagArray, $newTags) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function add_tags($compilationIdArray, $tagArray, $newTags)
	{
		
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
		
	*/
	/**
		* SUMMARY OF remove_tags
		*
		* DESCRIPTION
		*
		* @param TYPE ($compilationIdArray, $tagArray) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function remove_tags($compilationIdArray, $tagArray)
	{
		
		foreach ($compilationIdArray as $compilationId) {
			foreach ($tagArray as $tag) {
				ORM::for_table('osdb_tags') -> where('Name', $tag) -> where('Compilation_Id', $compilationId) -> delete_many();
			}
		}
	}
	
	/*
		
		* needs function make_comparer
		/**
		* SUMMARY OF sort_by
		*
		* DESCRIPTION
		*
		* @param TYPE ($arrayToSort, $column, $order = SORT_ASC) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function sort_by($arrayToSort, $column, $order = SORT_ASC)
	{
		$array = $arrayToSort;
		usort($array, make_comparer(array(
		$column,
		$order
		)));
		
		return $array;
	}
	
	/*
		
	*/
	/**
		* SUMMARY OF calculate_errors
		*
		* DESCRIPTION
		*
		* @param TYPE ($mainId, $compilationId, $startDate, $endDate) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function calculate_errors($mainId, $compilationId, $startDate, $endDate)
	{
		
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
	/**
		* SUMMARY OF establish_calculation_table
		*
		* DESCRIPTION
		*
		* @param TYPE ($type, $stepLength = 0) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function establish_calculation_table($type, $stepLength = 0)
	{
		
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
		
	*/
	/**
		* SUMMARY OF calculate_ranking
		*
		* DESCRIPTION
		*
		* @param TYPE ($mainCompId, $compId1, $compId2, $Day) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function calculate_ranking($mainCompId, $compId1, $compId2, $Day)
	{
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
		
	*/
	/**
		* SUMMARY OF autocovariance
		*
		* DESCRIPTION
		*
		* @param TYPE ($array, $stepSize = 1) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function autocovariance($array, $stepSize = 1)
	{
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
		
	*/
	/**
		* SUMMARY OF remove_source_from_database
		*
		* DESCRIPTION
		*
		* @param TYPE ($sourceId, $archive = TRUE) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function remove_source_from_database($sourceId, $archive = TRUE)
	{
		
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
		
	*/
	/**
		* SUMMARY OF remove_compilation_from_database
		*
		* DESCRIPTION
		*
		* @param TYPE ($compilationId) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function remove_compilation_from_database($compilationId)
	{
		
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
		
	*/
	/**
		* SUMMARY OF create_matchings
		*
		* DESCRIPTION
		*
		* @param TYPE ($array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function create_matchings($array)
	{
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
		
	*/
	
	/*
		
	*/
	
	/*
		
	*/
	/**
		* SUMMARY OF find_matching_project
		*
		* DESCRIPTION
		*
		* @param TYPE ($projectNameArray, $format = "I Y - P - C P") ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	function find_matching_project($projectNameArray, $format = "I Y - P - C P")
	{
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
		
	*/
	/**
		* SUMMARY OF create_relevant_day_array
		*
		* DESCRIPTION
		*
		* @param TYPE ($dayArray) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function create_relevant_day_array($dayArray)
	{
		
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
