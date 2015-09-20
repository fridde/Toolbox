# friddes_php_functions
My collected PHP functions in one file

To include these and other files, put this preamble above your code:
```
	/* PREAMBLE */
	$url = "https://raw.githubusercontent.com/fridde/friddes_php_functions/master/include.php";
	$filename = "include.php";
	copy($url, $filename);
	include $filename;
	/* END OF PREAMBLE */

```

Now the function ```inc()``` is defined and you can include from several sources given in ```include.php``` by using, for example,
```
inc("000, 001"); // OR
inc("fnc, sql"); // if defined 
```
Observe that includes from javascript and CSS have to be made in the header of the html-file, not before.
Also: Many files are placed into the folder ```inc/``` that has to exist.

If you are also using the sql-functions, make sure to create a config.ini-file matching the given template in the application's root folder.
