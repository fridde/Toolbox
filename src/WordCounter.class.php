<?php 
	
	namespace Fridde;
	/**
		* SUMMARY OF filter_words
		*
		* DESCRIPTION
		*
		* @param TYPE ($wordArray, $rules) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function filter_words($wordArray, $rules)
	{
		
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
	/**
		* SUMMARY OF rectify_wordArray
		*
		* DESCRIPTION
		*
		* @param TYPE ($wordArray) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	
	
	function rectify_wordArray($wordArray)
	{
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
	/**
		* SUMMARY OF create_rules_from_ini
		*
		* DESCRIPTION
		*
		* @param TYPE ($ini_array) ARGDESCRIPTION
		*
		* @return TYPE NAME DESCRIPTION
	*/
	
	function create_rules_from_ini($ini_array)
	{
		
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
