<?php

//include("include.php");
include("autoload.php");
//include("update.php");

//echo getRecentCommitTime("fridde", "friddes_php_functions");

$time = "2016-03-08T07:40:52+00:00";
$age = 7;
$unit = "h";

//echo var_dump(is_older_than($time, $age, $unit));


use \Fridde\HTML as H;

$H = new H;

$div = $H->add($H->body, "div");
$H->add($div, "h1", "Super title"); 
$p = $H->add($div, "p", "Some beautiful text");
$H->add_hidden_input($p, array("animal" => "cat", "adress" => "main road"));
$H->add($div, "h1");

$H->render();

 //echo "Hej!"; 
//activate_all_errors();

//$grupper = sql_select("grupper");
//$selected_groups = array_select_where($grupper, array("g_arskurs" => "NOT:2/3"), "all", TRUE);
//echop($selected_groups);

//sql_delete("testtable");

//$criteria = array("AND", array("d5" => "NOT:", "status" => "NOT:archived"));
//$lararTable = sql_select("larare", $criteria);
// $sqlTable, $criteria = "", $headers = "all", $not = FALSE, $debug = FALSE

//echop($lararTable);
/*
$html = new HTML();

//$atts = array("type" => "button", 'value' => 'My Button', 'style' => 'width:125px');
$atts = array("class" => "superclass", 'style' => 'width:125px');


$elem = $html->create("div", "", $atts );
$sub_element = $elem->create("form");
//$html->appendChild($elem);
echo $html->saveHTML();
*/
