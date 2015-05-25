<?php

	function inc($inclusionString, $debug = FALSE){
		
		$inclusionArray = explode(",", $inclusionString);
		$inclusionArray = array_map("trim", $inclusionArray);
		
		/* a string to simplify finding a matching key */
		$translationString = "000:fnc ; 001:sql ; 101:cal 200:jquery ; 202:DTjQ ; 204:DTTT ; 205:jqueryUIjs ;
		206:DTfH ; 207:bootjs ; 302:DTin ; 400:jqueryUIcss ; 401:DTcss ; 402:DTfHcss ; 404:DTTTcss ; 405:bootcss ;
		503:css"; 
		$translationArray = array();
		foreach(explode(";", $translationString) as $pair){
			$thisPair = explode(":", $pair);
			$translationArray[trim($thisPair[1])] = trim($thisPair[0]);
		}
		
		$files = array(
		/* remote php files that have to be copied to the local server first */
		"000" => "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/functions",
		"001" => "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/sql_functions",
		/* local php files */
		"100" => "inc/misc_functions",
		"101" => "inc/calendar_functions",
		/* remote javascript files hosted by other servers */
		"200" => "//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min",
		"201" => "",
		"202" => "//cdn.datatables.net/1.10.4/js/jquery.dataTables.min",
		"203" => "//cdn.datatables.net/responsive/1.0.1/js/dataTables.responsive",
		"204" => "//cdn.datatables.net/tabletools/2.2.3/js/dataTables.tableTools.min",
		"205" => "//code.jquery.com/ui/1.11.2/jquery-ui.min",
		"206" => "//cdn.datatables.net/fixedheader/2.1.2/js/dataTables.fixedHeader.min",
		"207" => "//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min",
		/* local, already existing javascript files */
		"300" => "/lib/DataTables/extensions/Editor-1.3.3/js/dataTables.editor",
		"301" => "/bostad/inc/datatables_init",
		"302" => "/inc/datatables_init",
		/* remote css files */
		"400" => "//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui",
		"401" => "//cdn.datatables.net/1.10.7/css/jquery.dataTables.min",
		"402" => "//cdn.datatables.net/fixedheader/2.1.2/css/dataTables.fixedHeader",
		"403" => "//cdn.datatables.net/responsive/1.0.6/css/dataTables.responsive",
		"404" => "//cdn.datatables.net/tabletools/2.2.4/css/dataTables.tableTools",
		"405" => "//maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min",
		/* local, already existing css-files*/
		"500" => "/lib/DataTables/media/css/jquery.dataTables",
		"501" => "/lib/DataTables/extensions/TableTools/css/dataTables.tableTools",
		"502" => "/lib/DataTables/extensions/Editor-1.3.3/css/dataTables.editor",
		"503" => "/inc/stylesheet",
		);
		
		$subdir = get_current_subfolder();
		$alreadyIncluded = array();
		
		foreach($inclusionArray as $searchValue){
			$file = "";
			
			if(isset($translationArray[$searchValue])){
				$searchValue = $translationArray[$searchValue];
			}
			
			if(isset($files[$searchValue])){
				$file = $files[$searchValue];
			}
			if(in_array($searchValue, $alreadyIncluded)){
				$type = "skip";
			}
			else {
				$type = floor($searchValue / 100.0);
				$alreadyIncluded[] = $searchValue;
			}
			
			switch($type){ 
				case "0":
				$file .=  ".php";
				$content = file_get_contents($file);
				$name = explode("/", $file);
				$name = "inc/" . end($name);
				if($content != FALSE && !$debug){
					file_put_contents($name, $content);
				}
				include $name;
				break;
				
				case "1":
				$file .= ".php";
				include $file;
				break;
				
				case "2":
				$file .= ".js";
				echo '<script src="' . $file . '"> </script>' . PHP_EOL;
				break;
				
				case "3":
				$file = $subdir . $file . ".js";
				echo '<script src="' . $file .  '"> </script>' . PHP_EOL;
				break;
				
				case "4":
				$file .= ".css";
				echo '<link rel="stylesheet" type="text/css" href="' .  $file . '">';
				
				break;
				
				case "5":
				$file = $subdir . $file . ".css";
				echo '<link rel="stylesheet" type="text/css" href="' .  $file . '">';
				break;
				
				default:
				if($type == "skip"){
					// Do nothing!
				}
				else {
					echo '<!-- The index "'. $type . '" could not be found in the include.php file. -->' ;
				}
				break;
			}
		}
	}		
	
	function get_current_subfolder(){

		$path = explode("/", $_SERVER['PHP_SELF']);
		array_pop($path);
		return implode("/", $path);
	}
