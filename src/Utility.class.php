<?php
	
	namespace Fridde;
	
	class Utility{
		
		/**
			* SUMMARY OF redirect
			*
			* DESCRIPTION
			*
			* @param TYPE ($to) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function redirect($to)
		{
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
		/**
			* SUMMARY OF get_all_files
			*
			* DESCRIPTION
			*
			* @param TYPE ($dir = 'files') ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function get_all_files($dir = 'files')
		{
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
		/**
			* SUMMARY OF echop
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function echop($array)
		{
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
		/**
			* SUMMARY OF curPageURL
			*
			* DESCRIPTION
			*
			* @param TYPE () ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function curPageURL()
		{
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
		/**
			* SUMMARY OF print_r2
			*
			* DESCRIPTION
			*
			* @param TYPE ($Array, $Name = '', $size = 2, $depth = '', $Tab = '', $Sub = '', $c = 0) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function print_r2($Array, $Name = '', $size = 2, $depth = '', $Tab = '', $Sub = '', $c = 0)
		{
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
				echo $CR . '<script>function poke_that_array(dir){x=document.getElementById(dir);if(x.style.display == "none")
				{x.style.display = "";}else{x.style.display = "none";}}</script>' . $CR;
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
		/**
			* SUMMARY OF csvstring_to_array
			*
			* DESCRIPTION
			*
			* @param TYPE ($string, $separatorChar = ',', $enclosureChar = '"', $newlineChar = "\n") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function csvstring_to_array($string, $separatorChar = ',', $enclosureChar = '"', $newlineChar = "\n")
		{
			
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
		/**
			* SUMMARY OF remove_whitelines
			*
			* DESCRIPTION
			*
			* @param TYPE ($array) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function remove_whitelines($array)
		{
			
			foreach ($array as $key => $row) {
				if (strlen(trim(implode($row))) == 0) {
					$array[$key] = NULL;
				}
			}
			$array = array_filter($array);
			return $array;
		}
		/**
			* SUMMARY OF dateRange
			*
			* DESCRIPTION
			*
			* @param TYPE ($first, $last, $step = "+1 day", $format = "Y-m-d", $addLast = TRUE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function dateRange($first, $last, $step = "+1 day", $format = "Y-m-d", $addLast = TRUE)
		{
			
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
		/**
			* SUMMARY OF filter_dates
			*
			* DESCRIPTION
			*
			* @param TYPE ($dates, $constantDate, $after = TRUE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function filter_dates($dates, $constantDate, $after = TRUE)
		{
			
			$returnDates = array();
			foreach ($dates as $dateToCheck) {
				$dateIsAfter = strtotime($dateToCheck) > strtotime($constantDate);
				if ($after == $dateIsAfter) {
					$returnDates[] = $dateToCheck;
				}
			}
			
			return $returnDates;
		}
		/**
			* SUMMARY OF create_download
			*
			* DESCRIPTION
			*
			* @param TYPE ($source, $filename = "export.csv") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function create_download($source, $filename = "export.csv")
		{
			
			$textFromFile = file_get_contents($source);
			$f = fopen('php://memory', 'w');
			fwrite($f, $textFromFile);
			fseek($f, 0);
			
			header('Content-Type: text/plain');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
			// make php send the generated csv lines to the browser
			fpassthru($f);
		}
		/**
			* SUMMARY OF write_to_config
			*
			* DESCRIPTION
			*
			* @param TYPE ($configArray) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function write_to_config($configArray)
		{
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
		/**
			* SUMMARY OF find_most_similar
			*
			* DESCRIPTION
			*
			* @param TYPE ($needle, $haystack, $alwaysFindSomething = TRUE) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function find_most_similar($needle, $haystack, $alwaysFindSomething = TRUE)
		{
			
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
		/**
			* SUMMARY OF logg
			*
			* DESCRIPTION
			*
			* @param TYPE ($data, $infoText = "", $filename = "logg.txt") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function logg($data, $infoText = "", $filename = "logg.txt")
		{
			
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
		/**
			* SUMMARY OF activate_all_errors
			*
			* DESCRIPTION
			*
			* @param TYPE () ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function activate_all_errors()
		{
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}
		/**
			* SUMMARY OF DMStoDEC
			*
			* DESCRIPTION
			*
			* @param TYPE ($deg,$min,$sec) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function DMStoDEC($deg,$min,$sec)
		{
			
			// Converts DMS ( Degrees / minutes / seconds )
			// to decimal format longitude / latitude
			
			return $deg+((($min*60)+($sec))/3600);
		}
		/**
			* SUMMARY OF DECtoDMS
			*
			* DESCRIPTION
			*
			* @param TYPE ($dec) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function DECtoDMS($dec)
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
		/**
			* SUMMARY OF array_walk_values
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $function) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function array_walk_values($array, $function)
		{
			/* will return an array where a function that accepts a single parameter has been applied
			it's practically a simplification of array_walk. Note that it returns a value!*/
			
			$returnArray = array();
			foreach($array as $index => $value){
				$returnArray[$index] = $function($value);
			}
			
			return $returnArray;
		}
		/**
			* SUMMARY OF generateRandomString
			*
			* DESCRIPTION
			*
			* @param TYPE ($length = 10) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function generateRandomString($length = 10)
		{
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		/**
			* SUMMARY OF extract_request($translationArray = array
			*
			* DESCRIPTION
			*
			* @param TYPE (), $prefix = "req_") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public static function extract_request($translationArray = array(), $prefix = "req_")
		{
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
	}	