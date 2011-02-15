<?php
if ($argc)
{
	$_SERVER["HTTP_HOST"]=$argv[1];
	$_REQUEST["config"]=$argv[2];
	$_REQUEST["action"]=$argv[3];
}

include "includes.php";
function f_htmlentities_with_code($v_str){
	$s_htmlentities_with_code=$v_str;
	$s_htmlentities_with_code=f_str2iso($s_htmlentities_with_code);
	$s_htmlentities_with_code=htmlentities($s_htmlentities_with_code,ENT_NOQUOTES);
	$s_htmlentities_with_code=str_replace("&gt;",">",$s_htmlentities_with_code);
	$s_htmlentities_with_code=str_replace("&lt;","<",$s_htmlentities_with_code);
	$s_htmlentities_with_code=preg_replace("/&amp;([A-Za-z]+;)/","&$1", $s_htmlentities_with_code);
	return($s_htmlentities_with_code);
}
class trado
{
var $ttrado; // donnees en entree : dirin dirout filin conftrado

var $tradirou;
var $tradirin;
var $globcsv;
var $filcsv;
//var $filin; : dans trado
var $filout;
var $filintxt;
var $filouttxt;
var $tdif;
var $gdif;
var $pattern;//"#!cache#!admin#!old#!tmp#\.htm$#\.html$#\.tpl#"


	function trado_defaulttrad()
	{
		return(array("in"=>"TRAD_".$this->ttrado["dirin"],
			"out"=>"TRAD_".$this->ttrado["dirout"]));
	}
	function trado_createtdf($v_filtxt)
	{
		//POUR /script on verra .... 
		$filtxt=f_str2iso($v_filtxt);
		$filtxt=preg_replace("/`/","'",$filtxt);

		$filtxt=preg_replace(":[°²]:","",$filtxt);
		
		//vire les scripts
		$filtxt=preg_replace(":<script:is","°",$filtxt);
		$filtxt=preg_replace(":</script>:is","²",$filtxt);
		$filtxt=preg_replace(":°[^°]*²:is","",$filtxt);
		$filtxt=preg_replace(":[²°]:is","",$filtxt);
		//$filtxt=preg_replace(":<script.*</script>:is","",$filtxt);

		//vire les commentaires
		$filtxt=preg_replace(":<!--:is","°",$filtxt);
		$filtxt=preg_replace(":-->:is","²",$filtxt);
		$filtxt=preg_replace(":°[^°]*²:is","",$filtxt);
		$filtxt=preg_replace(":[²°]:is","",$filtxt);

		//extrait les alt et title
		$filtxt=preg_replace(":(alt|title|value)=\"([^\"]*)\":is",">$2<",$filtxt);

		//vire les balises
		$filtxt=preg_replace("/<[^>]*>/","`",$filtxt);

		//nettoie ce qui reste
		$filtxt=preg_replace("/&nbsp;/"," ",$filtxt);
		$filtxt=preg_replace("/[ \t\n\r]+/s"," ",$filtxt);
		$filtxt=preg_replace("/`+ +/","`",$filtxt);
		$filtxt=preg_replace("/ +`+/","`",$filtxt);
		$filtxt=preg_replace("/`+/","`",$filtxt);
		$filtxt=preg_replace("/^`/s","",$filtxt);
		$filtxt=preg_replace("/`$/s","",$filtxt);

		return(explode("`",$filtxt));
	}
	function trado_creatediff($v_filintxt, $v_filouttxt, $v_previousdif=null)
	{
	/*
		$filin="dir/".$v_ttrado["dirin"]."/".$v_ttrado["filin"];
		$filout="dir/".$v_ttrado["dirout"]."/".$v_ttrado["filin"];
	*/
		//$fildiff=xdiff_string_diff($v_filintxt, $v_filouttxt, 0);//context=0
		//print_r($fildiff);die;
		$tdin=$this->trado_createtdf($v_filintxt);
		$tdou=$this->trado_createtdf($v_filouttxt);
		//print_r($tdou);print_r($tdin);gk;
		$tdif=array();
		foreach($tdin as $i=>$in)
		{
			$out=$tdou[$i];
			if (!$out) $out=$tdin[$i];
			if (($in!=$out) or (
				!(preg_match("/^\{[^\{\}]*\}$/",$in))
			and !(preg_match("/^[^a-zA-Z]*$/",$in))
			))
				$tdif[]=array("in"=>$in,"out"=>$out);
		}
		$tdif=$this->trado_removeduplicates($tdif);
		if ($v_previousdif)//recup ancienne trad
			foreach($v_previousdif as $prevdif)
				foreach($tdif as $i=>$dif)
					if ($dif["in"]==$prevdif["in"]){
						$tdif[$i]["out"]=$prevdif["out"];
						break;
					}
		if (!$tdif) return(array($this->trado_defaulttrad()));
		return($tdif);
	}
	function trado_removeduplicates($v_tdif)
	{
		$tdifk=array();
		$tdif2=array();
		foreach($v_tdif as $tdifi=>$tdif) 
		    if (!$tdifk[$tdif["in"]]){
			$tdifk[$tdif["in"]]=$tdifi;
			$tdif2[]=$tdif;
			}
		return($tdif2);
	}
	function trado_removeglobdifs($v_tdif, $v_gdif, $v_only_if_same=false)
	{
		foreach($v_gdif as $in=>$out)
			if ($v_tdif[$in])
				if ((!$v_only_if_same) or ($v_tdif[$in]==$out))
					unset($v_tdif[$in]);
		return($v_tdif);
	}
	function trado_replaceintext_ht($v_filtxt, $v_rech, $v_repl)
	{
		$txtout=$v_filtxt;
		preg_match_all("/([^>]*>)([^<>]*)</s",$v_filtxt,$matches,PREG_OFFSET_CAPTURE|PREG_SET_ORDER);
		$ofs=0;
		foreach($matches as $i=>$match)
		{//0=<avant> //1:phrase //2:<apres
			//compare sans " " ni \n, mais sans trim !
			$txtpart=(preg_replace("/\s+/"," ",$match[2][0]));
			$v_rech=(preg_replace("/\s+/"," ",$v_rech));
		if (strstr($txtpart,$v_rech))
			{
				$replaced=str_replace($v_rech,$v_repl,$txtpart);
				$txtout=substr($txtout,0,$match[2][1]+$ofs).$replaced.
					substr($txtout,$match[2][1]+$ofs+strlen($match[2][0]));
				$ofs+=strlen($replaced)-strlen($match[2][0]);
			}
		}
		return($txtout);
	}
	function trado_isrechdelim($v_rech, $v_repl)
	{
		$r_rech=$v_rech;/* accepte ='kjhkjh' */
		if (substr($r_rech,0,1)=="=") $r_rech=substr($r_rech,1);
		if (substr($r_rech,0,1)!=substr($v_repl,0,1)) return false;
		if (substr($r_rech,-1,1)!=substr($v_repl,-1,1)) return false;
		if (!preg_match("/[\"\'\(\<\{\[]/",substr($r_rech,-1,1))) return false;
		if (!preg_match("/[\"\'\)\>\}\[]/",substr($r_rech,-1,1))) return false;
		return true;
	}
	function trado_replaceintext_1($v_filtxt, $v_rech, $v_repl)
	{
	    if ($this->trado_isrechdelim($v_rech, $v_repl))
		return(str_replace($v_rech, $v_repl,$v_filtxt));
	    if (strpos($v_filtxt,'"'.$v_rech.'"'))
		return(str_replace('"'.$v_rech.'"', '"'.$v_repl.'"',$v_filtxt));
	    return($this->trado_replaceintext_ht($v_filtxt, $v_rech, $v_repl));
	}
	function trado_replaceintext($v_filtxt, $v_rech, $v_repl)
	{
	    $rech=f_str2iso($v_rech);
	    $repl=f_str2iso($v_repl);
	    //$rech=str_replace("\\n","\n",str_replace("\\t","\t",$rech));
	    //$repl=str_replace("\\n","\n",str_replace("\\t","\t",$repl));

	    $filtxt=$this->trado_replaceintext_1($v_filtxt, $rech, $repl);
	    if ($filtxt!=$v_filtxt) return($filtxt);
	    $filtxt=$this->trado_replaceintext_1($v_filtxt, htmlentities($rech), $repl);
	    if ($filtxt!=$v_filtxt) return($filtxt);
	    $filtxt=$this->trado_replaceintext_1($v_filtxt, html_entity_decode($rech), $repl);
	    if ($filtxt!=$v_filtxt) return($filtxt);
	    $filtxt=$this->trado_replaceintext_1($v_filtxt, f_str2utf8($rech), $repl);
	    if ($filtxt!=$v_filtxt) return($filtxt);
/*
		$filtxt=$this->trado_replaceintext_1($v_filtxt, f_str2utf8($rech), f_str2iso($repl));
	    if ($filtxt!=$v_filtxt) return($filtxt);
	    $filtxt=$this->trado_replaceintext_1($v_filtxt, f_str2iso($rech), f_str2iso($repl));
	    if ($filtxt!=$v_filtxt) return($filtxt);
	    $filtxt=$this->trado_replaceintext_1($v_filtxt, htmlentities($rech), htmlentities($repl));
	    if ($filtxt!=$v_filtxt) return($filtxt);
*/
	return($v_filtxt);
	}
	function trado_getfiles($v_dir,$v_filtre,$depth=0)
	{
		$filtres=$tdir=array();
		if ($depth>10) return($tdir);//gardefou
		if ($v_filtre) 
		{
			$sep=substr($v_filtre,0,1);
			$filtres=explode($sep,substr($v_filtre,1,-1));
		}
	//print_r($filtres);die;
	//print(":".$v_dir."\n");
	//	if (!is_dir($v_dir)) return(array($v_dir));#!cache#htm$#
		foreach(glob($v_dir."/*") as $fildir){
			$inc=$exc=0;
			foreach($filtres as $filtre)
				if (substr($filtre,0,1)=="!"){
					if (preg_match($sep.substr($filtre,1).$sep,$fildir)) $exc++;
				}else{
					if (preg_match($sep.$filtre.$sep,$fildir)) $inc++;
					//if (preg_match($sep.$filtre.$sep,$fildir)) 
					//print ("preg_match($sep.$filtre.$sep,$fildir)") ;
				}
			//print "($inc and !$exc) $fildir <br/>";
			if (is_dir($fildir))
			{
				if (!$exc)
					$tdir=array_merge($tdir,$this->trado_getfiles($fildir,$v_filtre,$depth++));
			}
			if (is_file($fildir))
			{
				if ($inc and !$exc) 
					$tdir[]=$fildir;
			}
		}
		return($tdir);
	}

	function trado_ddd($dd,$v_rmd="",$v_glob=""){
		$ddd=array($v_glob=>$v_glob);
		foreach($dd as $tf)
		{
			$tf=preg_replace("|^$v_rmd|","",$tf);
			$ddd[$tf]=$tf;
		}
		return($ddd);
	}

	static function tdiflongerfirst($a, $b)
	{
	    return((strlen($a["in"])==strlen($b["in"]))?0:
		((strlen($a["in"])<strlen($b["in"]))?1:-1));
	}
	function appliquetrad()
	{
		if (($this->tdif[0]) and ($this->tdif[0]["in"]=="FILE"))
		{
			$this->filouttxt=$this->tdif[0]["out"];
		}
		else
		{
			$this->filouttxt=$this->filintxt;
			//d'abord les tdif, dans un ordre de taille, ensuite globals
			$tdiforder=$this->tdif;
			usort($tdiforder, array("trado", "tdiflongerfirst"));
			foreach($tdiforder as $dif)
				$this->filouttxt=$this->trado_replaceintext(
					$this->filouttxt, $dif["in"],$dif["out"]);
			$tdiforder=$this->gdif;
			usort($tdiforder, array("trado", "tdiflongerfirst"));
			foreach($tdiforder as $dif)
				$this->filouttxt=$this->trado_replaceintext(
					$this->filouttxt, $dif["in"],$dif["out"]);
		}
		$this->savetxt();
	}

	function checktrad()
	{
		$this->filouttxt=$this->filintxt;
		foreach(array_merge($this->gdif,$this->tdif) as $dif)
			$this->filouttxt=$this->trado_replaceintext(
				$this->filouttxt, $dif["in"],$dif["out"]);
		return($this->checkactutxt());
	}

	function trado_get($v_conf=null)
	{
	    global $conf_defs;
	    $cf=$conf_defs[$_SERVER["HTTP_HOST"]];
	    
	    $cfk=array_keys($cf);
	    $cfk0=$cfk[0];
	    $cf0=$cf[$cfk0];
	    $cf0["conftrado"]=$cfk0;
	    $reloadme=$_REQUEST["reload"];
	    
	    $confactu=null;
	    if ($this->ttrado) $confactu=$this->ttrado["conftrado"];
	    if ((!$confactu) and ($_SESSION["trado"])) $confactu=$_SESSION["trado"]["conftrado"];
    
		if (!$v_conf) $v_conf=$confactu;
		if (!$v_conf) $v_conf=$cfk0;
	    if (!$cf[$v_conf]) $v_conf=$cfk0;
	    if (!$v_conf) $v_conf=$cfk0;
	    
		if ((!$this->ttrado) and ($_SESSION["trado"])
			and ($_SESSION["trado"]["conftrado"]==$v_conf))
				$this->ttrado=$_SESSION["trado"];
	    if (($this->ttrado) and ($v_conf) 
			and ($this->ttrado["conftrado"]==$v_conf) and (!$reloadme))
				return($this->ttrado);//nochg
	    if ($v_conf!=$confactu) $reloadme=1;
/*
if (!$_SESSION["trado"])
			if ($_COOKIE["trado"]) 
			    $_SESSION["trado"]=$_COOKIE["trado"];
		if (!$_SESSION["trado"]) $_SESSION["trado"]=$cf0;
		if ($_SESSION["trado"])
		    if ($v_conf)
				if ($_SESSION["trado"]["conftrado"]!=$v_conf)
					$reloadme=1;
$_SESSION["trado"]=$cf[$v_conf];
		if ($v_conf) $_SESSION["trado"]["conftrado"]=$v_conf;
*/
		if ($reloadme)
		{
		    $_SESSION["trado"]=$cf[$v_conf];
		    $_SESSION["trado"]["conftrado"]=$v_conf;
		    //header("Location: trado.php");exit;
		}
		//if (!$_SESSION["trado"]) $_SESSION["trado"]=$cf0;
		$this->ttrado=$_SESSION["trado"];
		//print($v_conf);print_r($this->ttrado);die;
	    if ($_REQUEST["reload"]){header("Location:trado.php");exit;}
		return($this->ttrado);
	}

	function trado_init($ttrado=null)
	{
		$globtrad="globtrad";//fichier global
		if (!$ttrado) $ttrado=$this->trado_get();
		$tradirou=preg_replace(":^.*/:","",$ttrado["dirout"]);//TODO un www it
		$tradirin=preg_replace(":^.*/:","",$ttrado["dirin"]);
		$dirtrad=($ttrado["dirtrad"])?$ttrado["dirtrad"]:"trad";
		$globcsv=$dirtrad."/".$tradirou."/".$globtrad.".csv";
		$filcsv=$dirtrad."/".$tradirou."/".$ttrado["filin"].".csv";
		$filin=$ttrado["dirin"]."/".$ttrado["filin"];
		$filout=$ttrado["dirout"]."/".$ttrado["filin"];
		if (file_exists($filin) and is_file($filin)) $filintxt=implode("",file($filin));
		if (file_exists($filout) and is_file($filout)) $filouttxt=implode("",file($filout));
		$this->ttrado=$ttrado;
		$this->tradirou=$tradirou;
		$this->tradirin=$tradirin;
		$this->globtrad=$globtrad;
		$this->globcsv=$globcsv;
		$this->filcsv=$filcsv;
		//$this->filin=$filin;
		$this->filout=$filout;
		$this->filintxt=$filintxt;
		$this->filouttxt=$filouttxt;
		if (file_exists($globcsv)) $gdif=f_import_xls2tt($globcsv); else $gdif=array();
		$this->gdif=$gdif;
		if (($ttrado["filin"]) and (!file_exists($filcsv)))
		{
			$tdif=$this->trado_creatediff($filintxt,$filouttxt);
			$tdif=$this->trado_removeglobdifs($tdif, $gdif);
			$this->savetrad();
		}
		//die($filcsv);
		if (file_exists($filcsv)) $tdif=f_import_xls2tt($filcsv); else $tdif=array();
		$this->tdif=$tdif;
	}

	function savetrad()
	{
		if (!is_dir(preg_replace(":/[^/]*$:","",$this->filcsv)))
			mkdir(preg_replace(":/[^/]*$:","",$this->filcsv),0755,true);
		$f=fopen($this->filcsv,"w");
		fwrite($f,f_export_tt2xls($this->tdif));
		fclose($f);
	}
	function savetxt()
	{
		$filactu=$this->getfilactu();
		if (($filactu) and ($filactu==$this->filouttxt)) return;//==
		if (!is_dir(preg_replace(":/[^/]*$:","",$this->filout)))
			mkdir(preg_replace(":/[^/]*$:","",$this->filout),0755,true);
		$f=fopen($this->filout,"w");
		if (!$f) return;
		fwrite($f,$this->filouttxt);
		fclose($f);
	}
	function getfilactu()
	{
		if (!is_dir(preg_replace(":/[^/]*$:","",$this->filout))) return(null);
		if (!file_exists($this->filout)) return(null);
		if (!is_file($this->filout)) return(null);
		$filactu=implode("",file($this->filout));
		return($filactu);//fichier actuel
	}
	function checkactutxt()
	{
		$filactu=$this->getfilactu();
		if (!$filactu) return(null);
		if ($filactu==$this->filouttxt) return(array());
		$chkdif=$this->trado_creatediff($this->filouttxt, $filactu);
		$chkdif=$this->difremoveeq($chkdif);
		return($chkdif);//liste les diffs
	}
	function difremoveeq($vdif)
	{
	    $vdifr=array();
	    foreach($vdif as $vdifx)
		if ($vdifx["in"]!=$vdifx["out"])
		    $vdifr[]=$vdifx;
	    return($vdifr);
	}
	function chktab2htm($vdif)
	{
	    if ((!$vdif) or (count($vdif)==0)) return("");
	    return(filtpl_compile("<table width='75%' cellspacing=0 border=1>".
    		"<tr><th>will be translated as</th><th>on line now is</th></tr>".
		 "<!-- BEGIN dif -->".
		"<tr><td>{in}</td><td>{out}</td></tr>".
		"<!-- END dif --></table>",array("dif"=>$vdif)));
	}

	function filelist($without_globtrad=null)
	{
		if (!$this->ttrado["dirin"]) die("arg");
		$filelist=$this->trado_ddd(
			$this->trado_getfiles(
				$this->ttrado["dirin"],$this->ttrado["pattern"]),
				$this->ttrado["dirin"]."/",
			$this->globtrad);
		if ($without_globtrad)
			unset($filelist["globtrad"]);
		return($filelist);
	}



//actions
	function action($v_action)
	{
	if (!$this->ttrado) $this->trado_get();
	
	if ($_REQUEST["config"]){
		$this->trado_get($_REQUEST["config"]);//reload
		$this->trado_init();
		$_COOKIE["trado"]=$_SESSION["trado"]=$this->ttrado;
		//and continue ....
	}

	if ($v_action=="updateconfig"){
		$this->trado_get($_REQUEST["config"]);//reload
		$this->trado_init();
		$_COOKIE["trado"]=$_SESSION["trado"]=$this->ttrado;
		return("OK");
	}

	if ($v_action=="updatesession"){
		$this->ttrado[f_htmlentities_with_code($_REQUEST["key"])]=f_htmlentities_with_code($_REQUEST["val"]);
		$this->trado_init();
		$_COOKIE["trado"]=$_SESSION["trado"]=$this->ttrado;
		return("OK");
	}

	if ($v_action=="updatetrad"){
		$this->tdif[$_REQUEST["i"]][f_htmlentities_with_code($_REQUEST["key"])]=f_htmlentities_with_code($_REQUEST["val"]);
		$this->savetrad();
		return("OK");
	}
	if ($v_action=="add1trad"){
		$this->tdif[]=$this->trado_defaulttrad($this->ttrado);
		$this->savetrad();
		return("OK");
	}
	if ($v_action=="del1trad"){
		array_splice($this->tdif,$_REQUEST["trad"],1);
		$this->savetrad();
		return("OK");
	}
	if ($v_action=="zaptrad"){
		$this->tdif=array();
		$this->savetrad();
		return("OK - zapped");
	}
	if ($v_action=="file"){
		$this->tdif=array(array("in"=>"FILE","out"=>$this->filintxt));
		$this->savetrad();
		return("OK - zapped");
	}
	if ($v_action=="resettrad"){
		$this->tdif=$this->trado_creatediff($this->filintxt,$this->filouttxt);
		$this->savetrad();
		return("OK - reset");
	}
	if ($v_action=="rescantrad"){
		$this->tdif=$this->trado_creatediff($this->filintxt,$this->filouttxt,$this->tdif);
		$this->savetrad();
		return("OK - reset");
	}
	if ($v_action=="trad"){
		$this->appliquetrad();
		return("OK - trad");
	}
	if ($v_action=="cretrds"){
		set_time_limit(90);
		$filelist=$this->filelist("without_global");
		$savettrado=$this->ttrado;
		$ttrado=$this->ttrado;
		foreach($filelist as $file)
		{
			$ttrado["filin"]=$file;
			$this->trado_init($ttrado);
			$this->tdif=$this->trado_creatediff($this->filintxt,$this->filouttxt,$this->tdif);
			$this->savetrad();
		}
		$this->trado_init($savettrado);
		return("OK - all tr created");
	}
	if ($v_action=="chktrds"){
		set_time_limit(90);
		$filelist=$this->filelist("without_global");
		$savettrado=$this->ttrado;
		$ttrado=$this->ttrado;
		$msg="";
		foreach($filelist as $file)
		{
			$ttrado["filin"]=$file;
			$this->trado_init($ttrado);
			$difs=$this->checktrad();
			if (count($difs)>0)
			    $msg.="<a href=# class='pfile'>$file</a> : ".count($difs)."<br/>";
			//if (count($difs)<2)
			//   $msg.=$this->chktab2htm($difs);
		}
		$this->trado_init($savettrado);
		return("<html>\n<body>\n".$msg."OK - all tr checked
		<script src='inc/lib_js/jquery-1.4.2.min.js'></script> 
		<script>
		\$(function(){
		\$('a.pfile').click(function(){
		    file=$(this).html();
		    \$.get('trado.php',{action:'updatesession',key:'filin',val:file},
			function(){
			    if (window.opener) window.opener.location.reload()
			    }
			);
			return(false);
		    })
		})
		 </script>
		 </body>
		 </html>
		");
	}
	if ($v_action=="cretxts"){
		set_time_limit(90);
		$filelist=$this->filelist("without_global");
		$savettrado=$this->ttrado;
		$ttrado=$this->ttrado;
		foreach($filelist as $file)
		{
			$ttrado["filin"]=$file;
			$this->trado_init($ttrado);
			$this->appliquetrad();
			print ".";
		}
		$this->trado_init($savettrado);
		return("OK - all tr applied");
	}
	if ($v_action=="save"){
		$this->filouttxt=f_str2iso($_REQUEST["page"]);
		$this->savetxt();
		return("OK - saved");
	}
	if ($v_action=="check"){
		$this->filouttxt=f_str2iso($_REQUEST["page"]);
		return($this->chktab2htm($this->checktrad())."OK - checked");
	}
    }
}	

if ($_REQUEST["ajax_htmlentities"]) die(htmlentities(f_str2iso($_REQUEST["ajax_htmlentities"],ENT_QUOTES,"UTF-8")));
if ($_REQUEST["ajax_html_entity_decode"]) die(html_entity_decode(f_str2iso($_REQUEST["ajax_html_entity_decode"],ENT_QUOTES,"UTF-8")));
//INIT
$ot=new trado;
$ttrado=$ot->trado_get($_REQUEST["config"]);
$ot->trado_init($ttrado);
//debug var_dump($ot);die;
//AJAX
if ($_REQUEST["action"]) die($ot->action($_REQUEST["action"]));

//AFFICHAGE DE LA PAGE HTML

$tt["txsel"]=($_REQUEST["txed"])?"txta":"txed";
$tt["dirin"]=$ot->ttrado["dirin"];
$tt["dirout"]=$ot->ttrado["dirout"];
$tt["menu_filin"]=f_sql2menu("menu_filin",
	$ot->ttrado["filin"],
	$ot->filelist(),"class='updatesession'");
$mnuconf=array();
foreach($conf_defs[$_SERVER["HTTP_HOST"]] as $cf=>$tcf)
	$mnuconf[$cf]=$cf;
$tt["menu_config"]=f_sql2menu("menu_config",$ot->ttrado["conftrado"],$mnuconf,"class='updateconfig'");

if ($ot->ttrado["filin"])
{
	$tt["trad"]=$ot->tdif;
	foreach($tt["trad"] as $i=>$v) {
		$istfile=($v["in"]=="FILE");
		$tt["trad"][$i]["i"]=$i;
		$tt["trad"][$i]["in_h"]=($v["in"]);
		$tt["trad"][$i]["out_h"]=($v["out"]);
		$tt["trad"][$i]["in_ht"]=htmlentities($v["in"]);
		$tt["trad"][$i]["out_ht"]=htmlentities($v["out"]);
		$tt["trad"][$i]["n_in"]=count(explode("\n",$v["in"]))+(strlen($v["in"])/50);
		$tt["trad"][$i]["n_out"]=count(explode("\n",$v["out"]))+(strlen($v["out"])/(($istfile)?120:50));
		$tt["trad"][$i]["pix_in"]=10*$tt["trad"][$i]["n_in"];
		$tt["trad"][$i]["pix_out"]=10*$tt["trad"][$i]["n_out"];
		$tt["trad"][$i]["isfilein"]=($istfile)?"isfilein":"isnotfilein";
		$tt["trad"][$i]["isfileout"]=($istfile)?"isfileout":"isnotfileout";
	}
	$tt["filtrad"]=$ot->filcsv;
	$tt["filin"]=$ot->ttrado["filin"];
	$tt["filout"]=$ot->filout;
	$tt["filintxt"]=$ot->filintxt;
	$tt["filouttxt"]=$ot->filouttxt;
	$tt["filintxt_h"]=htmlentities($ot->filintxt);
	$tt["filouttxt_h"]=htmlentities($ot->filouttxt);
	$tt["filinphp"]=preg_replace("/\.html*$/",".php",$ot->ttrado["filin"]);
	$tt["filoutphp"]=preg_replace("/\.html*$/",".php",$ot->filout);
}

//print_r($ot);print_r($tt);die;
$tp=array("contenu"=>filtpl_compile(f_getpagenamehtm(),$tt));
print(filtpl_compile(f_getpagenamehtm("template.htm"),$tp));
?>
