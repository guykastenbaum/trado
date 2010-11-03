<?php
include "includes.php";

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
	function trado_creatediff($v_filintxt, $v_filouttxt)
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
			if (strstr($match[2][0],$v_rech))
			{
				$replaced=str_replace($v_rech,$v_repl,$match[2][0]);
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
	function trado_replaceintext($v_filtxt, $v_rech, $v_repl)
	{
	    if ($this->trado_isrechdelim($v_rech, $v_repl))
		return(str_replace($v_rech, $v_repl,$v_filtxt));
	    if (strpos($v_filtxt,'"'.$v_rech.'"'))
		return(str_replace('"'.$v_rech.'"', '"'.$v_repl.'"',$v_filtxt));

	    return($this->trado_replaceintext_ht($v_filtxt, $v_rech, $v_repl));
	}
	function trado_getfiles($v_dir,$v_filtre)
	{
		$filtres=$tdir=array();
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
					$tdir=array_merge($tdir,$this->trado_getfiles($fildir,$v_filtre));
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

	function appliquetrad()
	{
		$this->filouttxt=$this->filintxt;
		foreach(array_merge($this->gdif,$this->tdif) as $dif)
			$this->filouttxt=$this->trado_replaceintext(
				$this->filouttxt, $dif["in"],$dif["out"]);
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
		if (!$v_conf)
		    if ($this->ttrado)
			return($this->ttrado);//nochg
		if (!$_SESSION["trado"])
			if ($_COOKIE["trado"]) 
			    $_SESSION["trado"]=$_COOKIE["trado"];
		if (!$_SESSION["trado"])
			$_SESSION["trado"]=$cf0;
		if ($_SESSION["trado"])
		    if ($v_conf)
			if ($_SESSION["trado"]["conftrado"]!=$v_conf)
			    $_SESSION["trado"]=$cf[$v_conf];
		if ($v_conf) $_SESSION["trado"]["conftrado"]=$v_conf;
		if ($_REQUEST["reload"]==1){
		    $_SESSION["trado"]=$cf[$this->ttrado["conftrado"]];
		    header("Location: trado.php");
		    exit;
		}
		if (!$_SESSION["trado"]) $_SESSION["trado"]=$cf0;
		$this->ttrado=$_SESSION["trado"];
		return($this->ttrado);
	}

	function trado_init($ttrado=null)
	{
		$globtrad="globtrad";//fichier global
		if (!$ttrado) $ttrado=$this->trado_get();
		$tradirou=preg_replace(":^.*/:","",$ttrado["dirout"]);//TODO un www it
		$tradirin=preg_replace(":^.*/:","",$ttrado["dirin"]);
		$globcsv="trad/".$tradirou."/".$globtrad.".csv";
		$filcsv="trad/".$tradirou."/".$ttrado["filin"].".csv";
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
		if (!is_dir(preg_replace(":/[^/]*$:","",$this->filout)))
			mkdir(preg_replace(":/[^/]*$:","",$this->filout),0755,true);
		$f=fopen($this->filout,"w");
		fwrite($f,$this->filouttxt);
		fclose($f);
	}
	function checkactutxt()
	{
		if (!is_dir(preg_replace(":/[^/]*$:","",$this->filout))) return(null);
		if (!file_exists($this->filout)) return(null);
		if (!is_file($this->filout)) return(null);
		$filactu=implode("",file($this->filout));
		if($filactu==$this->filouttxt) return null;//==
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

	function filelist()
	{
		$filelist=$this->trado_ddd(
			$this->trado_getfiles(
				$this->ttrado["dirin"],$this->ttrado["pattern"]),
				$this->ttrado["dirin"]."/",
			$this->globtrad);
		return($filelist);
	}



//actions
	function action($v_action)
	{
	if (!$this->ttrado) $this->trado_get();
	if ($v_action=="updateconfig"){
		$this->trado_get($_REQUEST["config"]);//reload
		$this->trado_init();
		$_COOKIE["trado"]=$_SESSION["trado"]=$this->ttrado;
		return("OK");
	}

	if ($v_action=="updatesession"){
		$this->ttrado[f_str2iso($_REQUEST["key"])]=f_str2iso($_REQUEST["val"]);
		$this->trado_init();
		$_COOKIE["trado"]=$_SESSION["trado"]=$this->ttrado;
		return("OK");
	}

	if ($v_action=="updatetrad"){
		$this->tdif[$_REQUEST["i"]][f_str2iso($_REQUEST["key"])]=f_str2iso($_REQUEST["val"]);
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
	if ($v_action=="resettrad"){
		$this->tdif=$this->trado_creatediff($this->filintxt,$this->filouttxt);
		$this->savetrad();
		return("OK - reset");
	}
	if ($v_action=="trad"){
		$this->appliquetrad();
		return("OK - trad");
	}
	if ($v_action=="cretrds"){
		set_time_limit(90);
		$filelist=$this->filelist();
		$savettrado=$this->ttrado;
		$ttrado=$this->ttrado;
		foreach($filelist as $file)
		{
			$ttrado["filin"]=$file;
			$this->trado_init($ttrado);
		}
		$this->trado_init($savettrado);
		return("OK - all tr created");
	}
	if ($v_action=="chktrds"){
		set_time_limit(90);
		$filelist=$this->filelist();
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
		$filelist=$this->filelist();
		$savettrado=$this->ttrado;
		$ttrado=$this->ttrado;
		foreach($filelist as $file)
		{
			$ttrado["filin"]=$file;
			$this->trado_init($ttrado);
			$this->appliquetrad();
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

//INIT
$ot=new trado;
$ttrado=$ot->trado_get();
$ot->trado_init($ttrado);
//print_r($ot);die;
//AJAX
if ($_REQUEST["action"]) die($ot->action($_REQUEST["action"]));

//AFFICHAGE DE LA PAGE HTML

$tt["dirin"]=$ot->ttrado["dirin"];
$tt["dirout"]=$ot->ttrado["dirout"];
$tt["menu_filin"]=f_sql2menu("menu_filin",
	$ot->ttrado["filin"],
	$ot->filelist(),"class='updatesession'");
$tt["menu_config"]=f_sql2menu("menu_config",$ot->conftrado,
	array_keys($conf_defs[$_SERVER["HTTP_HOST"]]),
	"class='updateconfig'");

if ($ot->ttrado["filin"])
{
	$tt["trad"]=$ot->tdif;
	foreach($tt["trad"] as $i=>$v) {
		$tt["trad"][$i]["i"]=$i;
		$tt["trad"][$i]["in_h"]=htmlentities($v["in"]);
		$tt["trad"][$i]["out_h"]=htmlentities($v["out"]);
		$tt["trad"][$i]["n"]=count(explode("\n",$v["in"]));
	}
	$tt["filtrad"]=$ot->filcsv;
	$tt["filin"]=$ot->ttrado["filin"];
	$tt["filintxt"]=$ot->filintxt;
	$tt["filouttxt"]=$ot->filouttxt;
	$tt["filintxt_h"]=htmlentities($ot->filintxt);
	$tt["filouttxt_h"]=htmlentities($ot->filouttxt);
}

//print_r($ot);print_r($tt);die;
$tp=array("contenu"=>filtpl_compile(f_getpagenamehtm(),$tt));
print(filtpl_compile(f_getpagenamehtm("template.htm"),$tp));
?>
