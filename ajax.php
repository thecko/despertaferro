<?php
session_start();

include("./includes/config.php");
include("./includes/functions.php");
include("./includes/objects.php");

header("Content-Type: text/html; charset=UTF-8");

$func = getVar("func","");
$langCom = parseFile("./data/" . $_SESSION["lang"] . "/common.php");

if ( function_exists($func) ){
	echo $func();
}
?>
