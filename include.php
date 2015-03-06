<?php
	function inc($inclusionString){
		
		$inclusionArray = explode(",", $inclusionString);
		$inclusionArray = array_map("trim", $inclusionArray);
		
		$files = array(
		/* remote php files that have to be copied to the local server first */
		"000" => "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/functions",
		"001" => "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/sql_functions",
		/* local php files */
		"100" => "inc/misc_functions",
		/* remote javascript files hosted by other servers */
		"200" => "//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min",
		"201" => "",
		"202" => "//cdn.datatables.net/1.10.4/js/jquery.dataTables.min",
		"203" => "//cdn.datatables.net/responsive/1.0.1/js/dataTables.responsive",
		"204" => "//cdn.datatables.net/tabletools/2.2.3/js/dataTables.tableTools.min",
		"205" => "//code.jquery.com/ui/1.11.2/jquery-ui.min",
		"206" => "//cdn.datatables.net/fixedheader/2.1.2/js/dataTables.fixedHeader.min",
		/* local, already existing javascript files */
		"300" => "/lib/DataTables/extensions/Editor-1.3.3/js/dataTables.editor",
		"301" => "/bostad/inc/datatables_init",
		"302" => "inc/datatables_init",
		/* remote css files */
		"400" => "//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui",	
		/* local, already existing css-files*/
		"500" => "/lib/DataTables/media/css/jquery.dataTables",
		"501" => "/lib/DataTables/extensions/TableTools/css/dataTables.tableTools",
		"502" => "/lib/DataTables/extensions/Editor-1.3.3/css/dataTables.editor",
		"503" => "inc/stylesheet",
		);
		
		foreach($inclusionArray as $searchValue){
			$file = $files[$searchValue];
			
			$type = floor($searchValue / 100.0);
			switch($type){ 
				case "0":
				$file .=  ".php";
				$content = file_get_contents($file);
				$name = explode("/", $file);
				$name = "inc/" . end($name);
				if($content != FALSE){
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
				$file = "//" . $_SERVER["HTTP_HOST"] . $file . ".js";
				echo '<script src="' . $file .  '"> </script>' . PHP_EOL;
				break;
				
				case "4":
				$file .= ".css";
				echo '<link rel="stylesheet" type="text/css" href="' .  $file . '.css">';
				
				break;
				
				case "5":
				$file = "//" . $_SERVER["HTTP_HOST"] . $file . ".css";
				echo '<link rel="stylesheet" type="text/css" href="' .  $file . '.css">';
				break;
				
			}
		}
	}		
