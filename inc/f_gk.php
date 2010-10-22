<?php
//fonctions utilitaires generiques gk
function f_urldomclean($v_url) {
	$v_url=preg_replace("|^http://|","",$v_url);
	$v_url=preg_replace("|^([^/]*)/.*$|","$1",$v_url);
	$v_url=strtolower($v_url);
	$v_url=preg_replace("|[^a-z0-9\-\.]|","",$v_url);
	$v_url=preg_replace("|^www\.|","",$v_url);
	if (strlen($v_url)<2) return(null);
	if (!strpos("x".substr($v_url,-5),".")) $v_url.=".com";
	return($v_url);
}
function f_urlclean($v_url) {
	$v_url=f_urldomclean($v_url);
	if (!preg_match("|\..*\.|",$v_url)) $v_url="www.".$v_url;
	return($v_url);
}
/* in: "yyyy-mm-dd [hh:mm:ss]" or "dd/mm/yyyy [hh:mm:ss]" */
//print_r(sscanf($v_date,"%d/%d/%d %d:%d:%d"));print "$v_date \n";
function f_date2timestamp($v_date){
	$v_date=preg_replace("|[^0-9:\-\/ ]|","",trim($v_date));
	if (!preg_match("|:|",$v_date)) $v_date.=' 00';
	$v_date=preg_replace("|\s+|"," ",$v_date);
	if (preg_match("| \d+$|",$v_date)) $v_date.=':00';
	if (preg_match("| \d+:\d+$|",$v_date)) $v_date.=':00';
	if (preg_match("|^\d+/\d+[^/]|",$v_date)) 
		preg_replace("|^(\d+/\d+)([^/].*$)|","$1/".strftime("%Y")."$2",$v_date);

	if (preg_match("|^\d+-\d+-\d+ \d+:\d+:\d+$|",$v_date))
		list($year,$month, $day, $hour, $min, $sec) = sscanf($v_date,"%u-%u-%u %u:%u:%u");
	if (preg_match("|^\d+/\d+/\d+ \d+:\d+:\d+$|",$v_date))
		list($day,$month,$year,$hour,$min,$sec) = sscanf($v_date,"%u/%u/%u %u:%u:%u");
	if (!$day) return null;
	if ($year<1000) $year+=($year<50)?2000:1900;
	return(mktime ( $hour,$min,$sec,$month,$day,$year));
}
function f_timestamp2sql($v_timestamp){
	if (!$v_timestamp) return("null");
	return(strftime("'%Y-%m-%d %H:%M:%S'",$v_timestamp));
}
function f_date2sql($v_date){
	return(f_timestamp2sql(f_date2timestamp($v_date)));
}
function f_time2sql($v_time){
	if (!$v_time) return("null");
	list($hh,$mm,$ss)=split(":",mysql_escape_string($v_time));
	if (!is_numeric($hh)) return("null");
	if (!is_numeric($mm)) $mm=0;
	if (!is_numeric($ss)) $ss=0;
	return "'".$hh.":".$mm.":".$ss."'";
}
function f_sql2date($v_date){
	if (!preg_match("/^[\'\"]*\d+-\d+-\d+/",$v_date)) return($v_date);
	$v_date=preg_replace("/^[\'\"]*(\d+-\d+-\d+) .*$/","$1",$v_date);
	list($aa,$mm,$jj)=split("-",$v_date);
	return $jj."/".$mm."/".substr($aa,2,2);
}
function f_timestamp2datetime($v_timestamp=-2){
	if (!$v_timestamp) return(null);
	if ($v_timestamp==-2) $v_timestamp=time();
	return(strftime("%d/%m/%Y %H:%M:%S",$v_timestamp));
}
function f_sql2datetime($v_datetime){
	return(f_timestamp2datetime(f_date2timestamp($v_datetime)));
}

/* ajoute v_nbjouv jours ouvrés à la date v_timestamp (compte +1 si >v_heurelimite)  , renvoie date (h=0) */
function f_datejourouvres($v_nbjouv,$v_timestamp=null,$v_heurelimite=0){
	$nbjreel=$v_nbjouv;
	$j=($v_timestamp)?$v_timestamp:time();//jour de depart
	$jn=getdate($j);
	if ($jn["hours"]>=$v_heurelimite) $nbjreel++;// +1 si commande apres midi
	$jk=0;
	while($nbjreel>=0){
		$j=mktime(0,0,0,$jn["mon"],$jn["mday"]+$jk,$jn["year"]);
		$jd=getdate($j);
		/* sauf samedi dimanche et jours feries */
		if (!(
			   ($jd["wday"]==6) or ($jd["wday"]==0)
			or (($jd["mon"]==1) and ($jd["mday"]==1))
			or (($jd["mon"]==5) and ($jd["mday"]==1))
			or (($jd["mon"]==5) and ($jd["mday"]==8))
			or (($jd["mon"]==5) and ($jd["mday"]==21))
			or (($jd["mon"]==7) and ($jd["mday"]==14))
			or (($jd["mon"]==8) and ($jd["mday"]==15))
			or (($jd["mon"]==11) and ($jd["mday"]==11))
			or (($jd["mon"]==12) and ($jd["mday"]==25))
			))
			$nbjreel--;
		$jk++;
	}
	return($j);
}


function f_qqsq($v_str){return(mysql_escape_string(stripslashes($v_str)));}
function f_qqsql($v_str){return(((!is_null($v_str)) and (is_numeric($v_str)))?f_qqsq($v_str):"NULL");}
function f_qqsqlq($v_str){return((!is_null($v_str))?"'".f_qqsq($v_str)."'":"NULL");}
function f_qqsqlr($v_str){return(f_qqsql($_REQUEST[$v_str]));}
function f_qqsqlqr($v_str){return(f_qqsqlq($_REQUEST[$v_str]));}
function f_date2sqlr($v_date){return(f_date2sql($_REQUEST[$v_date]));}
function f_time2sqlr($v_time){return(f_time2sql($_REQUEST[$v_time]));}
function f_menu_dyn($v_menu_id,$v_menu_sel,&$v_menu_tab,$v_javascript="")
{
	$mendynmulti=(preg_match("/multiple/i",$v_javascript));
	$menu_dyn='<select name="'.$v_menu_id.(($mendynmulti)?"[]":"").'" id="'.$v_menu_id.'" '.$v_javascript.'>'."\n";
	foreach(array_keys($v_menu_tab) as $v_menu_i)
	{
		//teste xy==xy pour ambiguite "" et 0
		$mendynsel=($mendynmulti)?
			preg_match("/[,;\|:]".$v_menu_i."[,;\|:]/",",".$v_menu_sel.","):
			("x".$v_menu_i=="x".$v_menu_sel);
		$menu_dyn.='<option value="'.$v_menu_i.'" '.(($mendynsel)?' selected ':'').'>'.
			$v_menu_tab[$v_menu_i].'</option>'."\n";
	}
	$menu_dyn.='</select>'."\n";
return($menu_dyn);
}
//menu avec parents à partir de sql, attention select id,libelle,parent
function f_ttr2menutab_compare($a, $b) {return strcmp($a["pathname"], $b["pathname"]);}
function f_ttr2menutab($v_menu_tab,$v_orphan=null){
	if (!$v_menu_tab) return($tt2menutab);
	$path_sep="/";
	$menu_tab_in=$v_menu_tab;
	$menu_tab_keys=array_keys($v_menu_tab[0]);
	$menu_tab_id=$menu_tab_keys[0];
	$menu_tab_val=$menu_tab_keys[1];
	$menu_tab_parent=$menu_tab_keys[2];
	$menu_tab_rang=(count($menu_tab_keys>3))?$menu_tab_keys[3]:$menu_tab_id;
	//creation d'un index "id"=>$item
	$menu_tab_idx=Array();
	foreach($menu_tab_in as $menu_path_i => $menu_path_item)
		$menu_tab_idx[$menu_path_item[$menu_tab_id]]=$menu_path_item;
	// calcul du path
	foreach($menu_tab_in as $menu_path_i => $menu_path_item)
	{
		$path=$menu_path_item["path"];
		$pathnice="";
		if ($path=="")
		{
			$menu_path_par=$menu_path_item[$menu_tab_parent];
			$menu_path_order=substr("00000".$menu_path_item[$menu_tab_rang],-5).$menu_path_item[$menu_tab_id];
			$ilimit=100;
			while ("x".$menu_path_par!="x") // boucle juskalaracine
			{
				$path=$menu_path_order.$path_sep.$path;
				$pathnice=$menu_path_par.$path_sep.$pathnice;
				$menu_item_par=$menu_tab_idx[$menu_path_par];
				if (!$menu_item_par) {
					$menu_path_par=null;
					$menu_path_order="orphans";//.$path_sep.$path;
				} else {
					$menu_path_order=substr("00000".$menu_item_par[$menu_tab_rang],-5).$menu_item_par[$menu_tab_id];
					$menu_path_par=$menu_item_par[$menu_tab_parent];
				}
				if (!$ilimit--) die("erreur recursion $path");//un peu hard
			}
			$pathnice=substr($menu_path_par.$path_sep.$pathnice,0,-1);
			$path=substr($menu_path_order.$path_sep.$path,0,-1);
			$menu_tab_in[$menu_path_i]["path"]=$path;//ajout du champ path
			$menu_tab_in[$menu_path_i]["pathname"]=$path;//.$menu_path_item[$menu_tab_id];//ajout du champ path
			$menu_tab_in[$menu_path_i]["pathnice"]=$pathnice;//.$menu_path_item[$menu_tab_id];//ajout du champ path
		}
	}
	usort($menu_tab_in, "f_ttr2menutab_compare");//tri par path
	//debug print_r($menu_tab_in);
	//prefixe par des "-"
	foreach($menu_tab_in as $menu_tab_i=>$menu_tab_item)
		$menu_tab_in[$menu_tab_i]["pathprefix"]=
			preg_replace(":".$path_sep.":","-",
				preg_replace(":[^".$path_sep."]*:","",$menu_tab_item["path"]));

	if ($v_orphan) // si on garde les orphelins
	{
		$menu_tab_orphan=-1;
		foreach($menu_tab_in as $menu_tab_i=>$menu_tab_item)
			if (($menu_tab_orphan==-1) and (preg_match("/^orphans/",$menu_tab_item["path"])))
				$menu_tab_orphan=$menu_tab_i;
		if ($menu_tab_orphan>=0)
			array_splice($menu_tab_in, $menu_tab_orphan,0,array(array(
				"path"=>"orphanage","pathprefix"=>"",$menu_tab_id=>$v_orphan,$menu_tab_val=>$v_orphan)));
	}
	else
	{
		foreach($menu_tab_in as $menu_tab_i=>$menu_tab_item)
			if (preg_match("/^orphans/",$menu_tab_item["path"]))
				unset($menu_tab_in[$menu_tab_i]);
	}

	//calcul de menutab classique
	$tt2menutab=Array();
	foreach($menu_tab_in as $menu_tab_item)
		$tt2menutab[$menu_tab_item[$menu_tab_id]]=$menu_tab_item["pathprefix"].$menu_tab_item[$menu_tab_val];
	return($tt2menutab);
}
//menu à partir de sql, attention select id,libelle
function f_tt2menutab($v_menu_tab){
	$tt2menutab=Array();
	if (!$v_menu_tab) return($tt2menutab);
	$menu_tab_keys=array_keys($v_menu_tab[0]);
	$menu_tab_id=$menu_tab_keys[0];
	$menu_tab_val=$menu_tab_keys[1];
	foreach($v_menu_tab as $menu_tab_item)
		$tt2menutab[$menu_tab_item[$menu_tab_id]]=$menu_tab_item[$menu_tab_val];
	return($tt2menutab);
}
/*
OLDf_sql2menu($v_menu_id,$v_menu_sel,$v_tab_or_sql,$v_javascript=""){
	if (is_array($v_tab_or_sql))
		return(f_menu_dyn($v_menu_id,$v_menu_sel,$v_tab_or_sql,$v_javascript));
	return(f_menu_dyn($v_menu_id,$v_menu_sel,f_tt2menutab(f_sql2tt($v_tab_or_sql)),$v_javascript));
}
*/
//ajout menu par string: change x:y;z:a;.. par array(x=>y, etc)
function f_sql2menu_keyval($v_tab_or_sql)
{
	if (preg_match("/^[a-zA-Z0-9 \_\-\.']*[:=]/",$v_tab_or_sql))
	{
		$v_tab=array();
		foreach(explode(";",$v_tab_or_sql) as $sql2menu_kvm2a0)
			$v_tab[]=array("key"=>preg_replace("/[:=].*$/","",$sql2menu_kvm2a0),
					"value"=>preg_replace("/^[^:=]*[:=]/","",$sql2menu_kvm2a0));
	}
	return($v_tab);
}
//menu (string keyval, select ou tableau) => menu
function f_sql2menutab($v_tab_or_sql,$v_orphan=null)
{
	if (is_array($v_tab_or_sql))
		$v_tab=$v_tab_or_sql;
	else
	{
		if (preg_match("/^[a-zA-Z0-9 \_\-\.']*[:=]/",$v_tab_or_sql))
			$v_tab=f_sql2menu_keyval($v_tab_or_sql);
		else
			$v_tab=f_sql2tt($v_tab_or_sql);
		$v_tab0=($v_tab[0])?$v_tab[0]:array("key"=>"","val"=>"");
		foreach(array_keys($v_tab0) as $v_tabk) $v_tab0[$v_tabk]="";
		if (!array_key_exists("",$v_tab)) array_unshift($v_tab,$v_tab0);
		if (count($v_tab0)<=2)  // id nom
			$v_tab=f_tt2menutab($v_tab);
		if ((count($v_tab0)==3) or (count($v_tab0)==4)) //id nom parent
			$v_tab=f_ttr2menutab($v_tab,$v_orphan);
	}
	return($v_tab);
}
function f_sql2menu($v_menu_id,$v_menu_sel,$v_tab_or_sql,$v_javascript="",$v_prefix=null,$v_orphan=null)
{
	$v_tab=f_sql2menutab($v_tab_or_sql,$v_orphan);
	if ($v_prefix)
		foreach($v_tab as $tabk=>$tabv)
			$v_tab[$tabk]=str_repeat($v_prefix,strlen(
				preg_replace("/^(\-*)([^\-].*)$/","$1",$tabv))).
				preg_replace("/^(\-*)([^\-].*)$/","$2",$tabv);
	return(f_menu_dyn($v_menu_id,$v_menu_sel,$v_tab,$v_javascript));
}
//---------------

function f_format_texte_long($v_f_format_texte){
	$v_f_format_texte=stripslashes($v_f_format_texte);
	if (substr($v_f_format_texte,0,1)!="<")
	{
		$v_f_format_texte=html_entity_decode($v_f_format_texte);
		//ici traiter une mise en page ....
		$v_fft_txt="";
		$v_fft_txtul=0;
		foreach (split("\n",$v_f_format_texte) as $v_fft_txtline)
		{
			if (substr($v_fft_txtline,0,2)=="- "){
				$v_fft_txtline="<li>".substr($v_fft_txtline,2)."</li>\n";
				if ($v_fft_txtul==0)
				$v_fft_txtline="<ul>".$v_fft_txtline;
				$v_fft_txtul=1;
				$v_fft_txt.=$v_fft_txtline."\n";
			}else{
				if ($v_fft_txtul==1){
					$v_fft_txtline="</ul>".$v_fft_txtline;
					$v_fft_txt=0;
				}
				$v_fft_txt.=$v_fft_txtline."<br>\n";
			}//"- "
		}
		$v_fft_txt=preg_replace("/<br>\n$/","",$v_fft_txt);
		$v_f_format_texte=$v_fft_txt;
	}
	return $v_f_format_texte;
}
function f_format_texte_court($v_f_format_texte){
	$v_f_format_texte=stripslashes($v_f_format_texte);
	return $v_f_format_texte;
}
function f_rskeys($v_rs)
{
	$rskeys=array();
	foreach(array_keys($v_rs) as $rsk)
			if (!is_int($rsk))
				$rskeys[$rsk]=$v_rs[$rsk];
	return($rskeys);
}
function f_sql2tt($v_sql)
{
	$f_sql2tt= array();
	if (array_key_exists("debug",$_SESSION) and ($_SESSION["debug"]==1)) 
		print(nl2br(preg_replace("/[\t ]/","&nbsp;&nbsp;","Debug ".date("H:i:s").": $v_sql \n")));
	($lpag = mysql_query ($v_sql) ) or die (mysql_error());
	if (!is_bool($lpag))
		while ($rs=mysql_fetch_array($lpag))
			array_push($f_sql2tt,f_rskeys($rs));
	if (array_key_exists("debug",$_SESSION) and ($_SESSION["debug"]==1)) 
		print(nl2br(preg_replace("/[\t ]/","&nbsp;&nbsp;",print_r($f_sql2tt,true))));
	return($f_sql2tt);
}
function tt_utf8_decode(&$v_tt,$v_champ)
{
	for($i=0;$i<count($v_tt);$i++) 
		$v_tt[$i][$v_champ]=utf8_decode($v_tt[$i][$v_champ]);
}
function f_sql2tt0($v_sql){
	$f_sql2tt0=f_sql2tt($v_sql);
	if(!$f_sql2tt0) return(null);
	if(!$f_sql2tt0[0]) return(null);
	return($f_sql2tt0[0]);
}
function f_str2iso($v_str){
	return ($v_str != utf8_encode(utf8_decode($v_str)))?
		$v_str:utf8_decode($v_str);
}
function f_str2utf8($v_str){
	return ($v_str != utf8_encode(utf8_decode($v_str)))?
		utf8_encode($v_str):$v_str;
}
//renvoie le nom de la page html/php
function f_getpagename(){
	$server_script_name=$_SERVER["PHP_SELF"];
	if (!$server_script_name) $server_script_name=$_SERVER["SCRIPT_NAME"];
	if (!$server_script_name) $server_script_name=$_SERVER["SCRIPT_FILENAME"];
	$server_script_name=f_str2iso(preg_replace(":^.*/:","",$server_script_name));
return($server_script_name);
}
//renvoie le contenu html
function f_getfilenamehtm($v_str=null){
	$f_gpn_htm=($v_str)?$v_str:f_getpagename();
	$f_gpn_htm=preg_replace("/\.[^\.]*$/","",$f_gpn_htm);
	$f_gpn_ext="htm";
	if (!file_exists($f_gpn_htm.".".$f_gpn_ext)) $f_gpn_ext="html";
	if (!file_exists($f_gpn_htm.".".$f_gpn_ext)) return(null);
	return($f_gpn_htm.".".$f_gpn_ext);
}
function f_getpagenamehtm($v_str=null){
	$f_gpn_htm=f_getfilenamehtm($v_str);
	if (!$f_gpn_htm) return(null);
	return(implode("",file("$f_gpn_htm")));
}
function f_desaccentuation($v_str)
{
	//et si j'utilisait htmlentities pour les accents !?: référence devient reference
	return(preg_replace("/\&(.)[^\;]*\;/","$1",htmlentities($v_str)));
}
function f_lib_js_htm()
{
	$f_lib_js_htm="";
	foreach(array("ah_js.js","prototype.js","scriptaculous.js","behaviour.js","builder.js","effects.js","controls.js","dragdrop.js","slider.js") as $jsfile)
		$f_lib_js_htm.='<script src="/inc/lib_js/'.$jsfile.'" type="text/javascript"></script>'."\n";
	return($f_lib_js_htm);
}

//pagination
//in=$pagination_nbpp = nb d'elts par page // $page = url actuelle // $pagination_nb = nb total  // max de liens le reste est en ...
//out $ttpagin avec infos pour $sqllimit=" limit $pagination_deb,$pagination_pag ";
/* pagination , exemple : 
$pagination_nbpp=9;$ttnb=f_sql2tt0($sqlcount.$sqlwhere);
$tt["pagination"]=f_pagination($pagination_nbpp,$ttnb["nb"],$page);
$sqllimit=" limit ".$tt["pagination"]["pagination_deb"].",".$tt["pagination"]["pagination_pag"];
<div id="nbr_pages">
<!-- BEGIN pagination -->
<!-- BEGIN pagination_prec --><a href="{pagination_url}">{pagination}</a><!-- END pagination_prec -->
<!-- BEGIN pagination_act --><span>{pagination}</span><!-- END pagination_act -->
<!-- BEGIN pagination_suiv --><a href="{pagination_url}">{pagination}</a><!-- END pagination_suiv -->
<!-- END pagination -->
</div>
#nbr_pages{clear: both;font: normal 11px "Trebuchet MS", Verdana, sans-serif;margin: 10px 0 0 5px;}
#nbr_pages a{font: normal 11px "Trebuchet MS", Verdana, sans-serif;display: inline;
	padding: 0 3px 0 3px;background: #666;color: #FFF;text-decoration: none;margin: 0 5px 0 0;}
#nbr_pages a:hover{font: normal 11px "Trebuchet MS", Verdana, sans-serif;color: #FFF;background: #F00;text-decoration: none;}
#nbr_pages span{background: #EEE;color: #000;margin: 0;padding: 0 3px 0 3px;text-decoration: none;}
req(pagination)=numero de page courante ;; pg_nb=nombre d'items ;; pg_nbpp ou req(pg_pag) si exist=nb d'itzm pas page
*/
function f_pagination($pagination_nbpp,$pagination_nb,$v_page,$v_maxpages=10)
{
	$ttpagination=array();
	$pagination_url=$v_page."?";
	foreach(array_keys($_REQUEST) as $req)
		if ((! preg_match("/(SESSID|pagination)/",$req)) and (is_string($_REQUEST[$req])))
			$pagination_url.=$req."=".urlencode($_REQUEST[$req])."&";
	$ttpagination["pagination_url"]=$pagination_url;
	$ttpagination["pagination_nb"]=$pagination_nb;
	$ttpagination["pagination_pag"]=$pagination_pag=
		($_REQUEST["pagination_pag"]>0)?$_REQUEST["pagination_pag"]:(($pagination_nbpp)?$pagination_nbpp:20);
	$ttpagination["pagination"]=$pagination=
		($_REQUEST["pagination"]>0)?$_REQUEST["pagination"]:1;
	$ttpagination["pagination_nb_act_premier"]=
		(1+($pagination-1)*$pagination_pag > $pagination_nb)?$pagination_nb:1+($pagination-1)*$pagination_pag;
	$ttpagination["pagination_nb_act_dernier"]=
		($pagination*$pagination_pag>$pagination_nb)?$pagination_nb:$pagination*$pagination_pag;
	$ttpagination["pagination_prec"]=array();
	$ttpagination["pagination_suiv"]=array();
	$pagination_nbpag=floor(($pagination_nb+$pagination_pag-1)/$pagination_pag);
	//$pagination_maxpag=($v_maxpages<$pagination_nbpag)?$v_maxpages:$pagination_nbpag;
	$pagination_maxpag=$v_maxpages;
	if ($pagination_nbpag>1)
		for ($i_pag=1;$i_pag<=$pagination_nbpag;$i_pag++)
		{
			$pagination_urlpag=$pagination_url."&pagination=".$i_pag;
	        if ($i_pag < $pagination) //and ($i_pag > $pagination-$pagination_maxpag))
	                array_push($ttpagination["pagination_prec"],array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag));
	        if (($i_pag == $pagination))
	                $ttpagination["pagination_act"]=array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag);
	        if ($i_pag > $pagination) //and ($i_pag < $pagination+$pagination_maxpag))
	                array_push($ttpagination["pagination_suiv"],array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag));
	        if (($i_pag == $pagination - 1))
	                $ttpagination["pagination_pageprec"]=array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag);
	        if (($i_pag == $ttpagination["pagination"] + 1))
	                $ttpagination["pagination_pagesuiv"]=array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag);
	        if (($i_pag == 1))
	                $ttpagination["pagination_pagepremiere"]=array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag);
	        if (($i_pag == $pagination_nbpag))
	                $ttpagination["pagination_pagederniere"]=array("pagination_url"=>$pagination_urlpag,"pagination"=>$i_pag);
		}
	$pagedeb=$pagination_pag*($ttpagination["pagination"]-1);
	if ($pagedeb<0) $pagedeb=0;
	if (count($ttpagination["pagination_suiv"])>$pagination_maxpag)
	{
		array_splice($ttpagination["pagination_suiv"],$pagination_maxpag,count($ttpagination["pagination_suiv"])-$pagination_maxpag-2);
		$ttpagination["pagination_suiv"][$pagination_maxpag]["pagination"]="...";
	}
	if (count($ttpagination["pagination_prec"])>$pagination_maxpag)
	{
		array_splice($ttpagination["pagination_prec"],1,count($ttpagination["pagination_prec"])-$pagination_maxpag-1);
		$ttpagination["pagination_prec"][1]["pagination"]="...";
	}
	if ($pagedeb>$pagination_nb) $pagedeb=$pagination_nb;
	$ttpagination["pagination_deb"]=$pagedeb;
	return($ttpagination);
}

//importe un fichier tabulé en un tableau associatif (clés=1ere ligne des champs)
// sans clés en 1ere ligne si nohead=1
function f_import_xls2tt($v_file,$v_nohead=0)
{
	if (file_exists($v_file))
		$prodfilel=file($v_file);//fichier de tableau de lignes
	else
		$prodfilel=$v_file;//tableau de lignes
	$prodhdrl=trim(array_shift($prodfilel));
	$sep="\t";//detection \t ; et "
	if (preg_match("/;/",$prodhdrl)) $sep=";";//bof
	if (preg_match("/\t/",$prodhdrl)) $sep="\t";
	$prodhdrl=preg_replace("/".$sep."$/","",$prodhdrl);//trim
	$prodhdr=explode($sep,$prodhdrl);//1ere ligne=nom des champs
	if ($v_nohead)
	{
		$prodhdr0=array();
		for ($prodhdri=0;$prodhdri<count($prodhdr);$prodhdri++) 
			$prodhdr0[$prodhdri]=$prodhdri;
		array_push($prodfilel,$prodhdrl);
		$prodhdr=$prodhdr0;
	}
	foreach($prodhdr as $prodhdri=>$prodhdrv)
		$prodhdr[$prodhdri]=preg_replace('/^"(.*)"$/',"$1",$prodhdrv);
	$prodtab=array();
	foreach($prodfilel as $prod_l)
	{
		if (trim($prod_l)!="")
		{
			$ilig++;
			$prod_l=preg_replace("/".$sep."$/","",trim($prod_l));//trim
			$prod_a=explode($sep,$prod_l);
			$prod_h=array();
			foreach($prodhdr as $prodhdri) $prod_h[$prodhdri]="";//init vide
			for($i=0;$i<count($prod_a);$i++)
			{
				$val=$prod_a[$i]; //prod_h(col)=>item
				$val=str_replace("\\n",chr(10),$val);//remplace \\n par \n
				$val=str_replace("\\t",chr(9),$val);//remplace \\t par \t
				$val=preg_replace('/^"(.*)"$/',"$1",$val);//vire "truc"
				$prod_h[$prodhdr[$i]]=$val;
			}
			$prodtab[]=$prod_h;//empile
		}
	}
	return($prodtab);
}

function f_export_tt2xls_send($v_export,$v_exportname="export",$v_sep="\t")
{
	header("Content-type: application/xls");
	header(strftime("Content-Disposition: attachment; filename=".$v_exportname."_%y%m%d%H%M%S.xls"));
	print f_export_tt2xls($v_export,$v_sep);
	exit;
}
function f_export_tt2xls($v_export,$v_sep="\t")
{
	$conf_replace_tab_bakt=0;
	$conf_replace_tab_space=1;
	$conf_replace_cr_bakn=1;
	$conf_replace_cr_space=0;
	$conf_replace_vr_space=0;
	$conf_replace_pv_space=0;
	$conf_exportname="export_".$table;
	$keyst=array();
	for($i=0;$i<min(50,count($v_export));$i++) foreach(array_keys($v_export[$i]) as $k) $keyst[$k]=1;
	$keys=array_keys($keyst);
	$export=(implode($v_sep,$keys))."\n";
	for($i=0;$i<count($v_export);$i++){
		for($j=0;$j<count($keys);$j++){
			$val=$v_export[$i][$keys[$j]];
			if ($conf_replace_tab_bakt) $val=preg_replace("/\t/m","\\t",$val);
			if ($conf_replace_tab_space) $val=preg_replace("/\t/m"," ",$val);
			$val=preg_replace("/\r\n/s","\n",$val);
			$val=preg_replace("/\n\r/s","\n",$val);
			$val=preg_replace("/\r/s","\n",$val);
			if ($conf_replace_cr_bakn) $val=preg_replace("/\n/s","\\n",$val);
			if ($conf_replace_cr_space) $val=preg_replace("/\n/s"," ",$val);
			if ($conf_replace_vr_space) $val=preg_replace("/,/s"," ",$val);
			if ($conf_replace_pv_space) $val=preg_replace("/;/s"," ",$val);
			if ($j!=0) $export.=$v_sep;
			$export.=$val;
		}
		$export.="\n";
	}
return($export);
}

/* unserialize with fix string length http://www.biggnuts.com/fix-corrupt-serialized-data/ */
function f_unserialize_fix($v_data)
{
	$splits = preg_split("/s:([0-9]*):/", $v_data);
	preg_match_all("/s:([0-9]*):/", $v_data, $lengths);
	$lengths = $lengths[1];
	for($i = 0; $i < sizeof($splits)-1; $i++){
		$text = $splits[$i+1];
		$pos = strpos($text, '";');
		$text = substr($text, 1, $pos-1);
		$text_len = strlen($text);
		if($lengths[$i] != $text_len)
			$lengths[$i] = $text_len;
		//echo "{$lengths[$i]} -> $text_len\n";
	}
	$return = $splits[0];
	for($i = 0; $i < sizeof($splits)-1; $i++){
		$return .= "s:{$lengths[$i]}:";
		$return .= $splits[$i+1];
	}
	return $return;
}
function f_unserialize($v_data){
	$unserialized_data=unserialize($v_data);
	if ($unserialized_data) return($unserialized_data);
	$v_data=f_unserialize_fix($v_data);
	$unserialized_data=unserialize($v_data);
	return($unserialized_data);
}
//nom d'image en iso ou utf8
function f_formatnamejpg($v_namejpg,$v_fscode="ISO")
{
	$v_namejpg=preg_replace("/\.(jpeg|jpg)$/i",".jpg",$v_namejpg);
	$v_namejpg=preg_replace("/\.png$/i",".png",$v_namejpg);
	$v_namejpg=preg_replace("/\.gif$/i",".gif",$v_namejpg);
	if (!preg_match("/\.(gif|jpg|png)$/",$v_namejpg)) $v_namejpg=$v_namejpg.".jpg";
	if ($v_fscode=="ISO")
		$v_namejpg=f_str2iso($v_namejpg);
	else
		$v_namejpg=f_str2utf8($v_namejpg);
	return(preg_replace("/%2F/i","/",rawurlencode($v_namejpg)));
}
// resize des photos crée des br_xxx.jpg (br mr hr etc au choix) 
// syntaxe com = brw=150,mrw=280,hrw=500 br=XX W ou H ou les deux = nb px maxi
function f_mkbrmrhr($v_dir,$v_name,$v_com)
{
	$tcomt=$tcom2t=array();
	foreach(split("[ ,;]",$v_com) as $vcomkv)
	{
		list($vcomk,$vcomv)=split("[:=]",$vcomkv);
		$tcomt[$vcomk]=$vcomv;
		$tcomt2[substr($vcomk,0,2)]=1;
	}
	$fname = $v_dir."/".$v_name;
	$fvdir=preg_replace(":/[^/]*$:","",$fname);
	$fvname=preg_replace(":\.jpg:i","",preg_replace(":^.*/:","",$fname)).".jpg";
	$img=imagecreatefromjpeg($fname);
	list($largeur,$hauteur)=getimagesize($fname);
	foreach(array_keys($tcomt2) as $pfxi)
	{
		if ($tcomt[$pfxi."h"] and $tcomt[$pfxi."w"])
			$cotefix=($largeur/$tcomt[$pfxi."w"]>$hauteur/$tcomt[$pfxi."h"])?"w":"h";//plus gd cote
		else
			$cotefix=($tcomt[$pfxi."h"])?"h":"w";
		if ($cotefix=="w")
		{
			$largeur2=$tcomt[$pfxi."w"];
			$hauteur2=round(($largeur2/$largeur)*$hauteur);
			$stretch=($hauteur2>$hauteur)?1:0;
		}
		if ($cotefix=="h")
		{
			$hauteur2=$tcomt[$pfxi."h"];
			$largeur2=round(($hauteur2/$hauteur)*$largeur);
			$stretch=($largeur2>$largeur)?1:0;
		}
		if ($stretch) // on ne fait pas de streching // TODO faut le mettre en config ....
		{
			$hauteur2=$hauteur;
			$largeur2=$largeur;
		}
		$img3=imagecreatetruecolor($largeur2,$hauteur2);
		//imagecopyresized mais en mieux
		imagecopyresampled($img3,$img,0,0,0,0,$largeur2,$hauteur2,$largeur,$hauteur);
		imagejpeg($img3,$fvdir."/".$pfxi."_".$fvname);//qualite 70%
	}
}
/* renvoie . ou ./.. ou ./../.. etc jusquau root level */
function f_getrootrelpath()
{
	$droot=strtolower(str_replace("\\","/",realpath($_SERVER["DOCUMENT_ROOT"])));
	$droot=preg_replace(":/$:","",$droot);
	for ($rootrelpath=".",$i=0;$i<20;$i++,$rootrelpath.="/.."){
		$rroot=strtolower(str_replace("\\","/",realpath($rootrelpath)));
		if ($rroot==$droot) return($rootrelpath);
	}
	return(".");//oups raté
}
//execute une fonction recursivement sur un arbre (max 20 niveaux)
//faudra peut etre un truc avec 2 parametres pour la clé
function f_array_recurse($x_tt,$v_fx,$v_levelmax=20)
{
	if ($v_levelmax<=0) die("f_filtpl_rx too many recursion");
	if (!is_array($x_tt)) return($v_fx($x_tt));
	foreach($x_tt as $x_ttk=>$x_ttv)
		$x_tt[$x_ttk]=f_array_recurse($x_ttv,$v_fx,$v_levelmax-1);
	return($x_tt);
}

function f_redim_hw($imgh,$imgw,$maxh,$maxw)
{
	if ((!$imgh) or (!$imgw) or (!$maxh) or (!$maxw)) return(array($imgh,$imgw));
	if (($imgh/$imgw)>($maxh/$maxw))
		return(array(ceil($maxh),ceil($imgw*($maxh/$imgh))));
	else
		return(array(ceil($imgh*($maxw/$imgw)),ceil($maxw)));
}
function f_redim_attr($imgh,$imgw,$v_maxh,$v_maxw){
	list($imgh,$imgw)=f_redim_hw($imgh,$imgw,$v_maxh,$v_maxw);
	return(' height="'.$imgh.'" width="'.$imgw.'" ');
}
function f_wget_curl($v_url){
	$ch = curl_init($v_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);//gk221206
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);//gk180707
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$contenu_html=curl_exec($ch);
	$code=curl_getinfo ( $ch,CURLINFO_HTTP_CODE);
	$curl_errno=curl_errno($ch);
	curl_close($ch);
	if ($curl_errno) return(null);//err (404?)
	return($contenu_html);
}
?>