<?php
$CONFIG_OS=(preg_match("/\;/",ini_get("include_path"))?"windows":"linux");
$config_include_path=".;".$_SERVER["DOCUMENT_ROOT"];
if ($CONFIG_OS=="windows")
	$config_include_path=preg_replace(":/:","\\",$config_include_path);
else
	$config_include_path=preg_replace(":;:",":",$config_include_path);
ini_set("include_path",$config_include_path);

include "config.php";
include "inc/f_gk.php";
include "inc/filtpl.php";

session_start();
$pagedir=$_SERVER["PATH_INFO"];
if (!$pagedir) $pagedir=$_SERVER["PHP_SELF"];
if (!$pagedir) $pagedir=$_SERVER["SCRIPT_FILENAME"];
if (!$pagedir) $pagedir=$_SERVER["SCRIPT_NAME"];
$pagename=f_str2iso(preg_replace(":^.*/:","",$pagedir));
$pagenoext=preg_replace(":\.[^\.]*$:","",$pagename);

$tt=array(
	"HTTP_HOST" => $_SERVER["HTTP_HOST"],
	"pagedir" => $pagedir,
	);
?>
