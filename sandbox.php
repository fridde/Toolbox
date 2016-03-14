<?php

// to enable debugging in plugin DBGp, add "?XDEBUG_SESSION_START=test" to your url
include("autoload.php");
inc("test");

use \Fridde\HTML as H;

$H = new H;
/*
	

$div = $H->add($H->body, "div");
$H->add($div, "h1", "Super title"); 
$p = $H->add($div, "p", "Some beautiful text");
$H->add_hidden_input($p, array("animal" => "cat", "adress" => "main road"));
$H->add($div, "h1");

$H->render();


*/
