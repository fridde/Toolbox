echo "Hello";

function easy(){
	return "I work!";
	
}

$cdnFiles = 	array(
  "https://cdn.rawgit.com/fridde/friddes_php_functions/master/functions.php",
  "https://cdn.rawgit.com/fridde/friddes_php_functions/master/sql_functions.php"
  );
$altFiles = array(
  "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/functions.php",
  "https://github.com/fridde/friddes_php_functions/blob/master/sql_functions.php"
  );
	
foreach($cdnFiles as $index => $cdnFile) {
  $includeFile = (file_exists($cdnFile) ? $cdnFile : $altFiles[$index]);
  $code = file_get_contents($includeFile);
  eval($code);	
}
