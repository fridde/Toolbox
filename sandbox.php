<?php

//include("include.php");
include("autoload.php");

use \Fridde\Utility as U;

$U = new U;

$U->redirect("http://www.uu.se");
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
