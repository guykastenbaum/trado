<?php
$pattern_espcli="#!cache#!admin#!old#!tmp".
		    "#\.htm$#\.html$#\.tpl".
		    "#!OLD#!SAV#!ES#!gk#!bizinf#!dossiers/.*/#!factures/.*/".
		    "#!/client/#!/sav/#!_sav/#";

$conf_defs=array(
"localhost"=>array(
    "example"=>array(
		"dirin"=>"web-source",
		"dirout"=>"web-target",
		"pattern"=>$pattern_espcli,
		),
    ),
"www.realwebsite.com"=>array(
    "subdir1"=>array(
		"dirin"=>"/var/www/site/somewhere1",
		"dirout"=>"/var/www/fr/somewhere1",
		"pattern"=>$pattern_espcli,
		),
    "subdirdk"=>array(
		"dirin"=>"/var/www/site/somewhere1",
		"dirout"=>"/var/www/dk/somewhere1",
		"pattern"=>$pattern_espcli,
		),
    "subdir2"=>array(
		"dirin"=>"/var/www/site/somewhere2",
		"dirout"=>"/var/www/fr/somewhere2",
		"pattern"=>$pattern_espcli,
		),
    ),
);

if (!$conf_defs[$_SERVER["HTTP_HOST"]]) die('conf['.$_SERVER["HTTP_HOST"].']');

?>
