# friddes_php_functions
My collected PHP functions in one file

To include these and other files, put this preamble above your code:
```
	/* PREAMBLE */
	$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
	$content = file_get_contents($url);
	file_put_contents("include.php", $content);
	include "include.php";
	/* END OF PREAMBLE */

```

Now the function ```inc()``` is defined and you can include from several sources given in ```include.php``` by using, for example,
```
inc("000, 001");
```
Observe that includes from javascript and CSS have to be made in the header of the html-file, not before.
