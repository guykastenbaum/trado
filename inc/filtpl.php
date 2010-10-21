<?
// FILNET GUY KASTENBAUM 10 NOVEMBRE 2005 , V1.0
// 20 12 2006 ajout escape pour le cas ou les valeurs de tableau contiennent des {}
// filtpl.php un template aussi bien que les autres
//$tt=array(a => "aaa",b => "bbb",t => array(0=>array(e=>"e{f}e"),1=>array(e=>"fff")));
//$tf="111{a}<!-- BEGIN t -->{e}<!-- END t --><!-- BEGIN u -->u{e}u<!-- END u -->222{b}";
//print filtpl_compile($tf,$tt);	

function filtpl_compile($filtpl_tf,$filtpl_tt){
	if (!$filtpl_tt) return("");//unwanted branch I presume
	if (!is_array($filtpl_tt)) return($filtpl_tf);//like a bug
	$filtpl_before=$filtpl_tf;//assume all
	$filtpl_end="";
	if (preg_match('#<!-- BEGIN (\w+) -->#i', $filtpl_tf , $filtpl_m , PREG_OFFSET_CAPTURE))
	{
		$filtpl_before=substr($filtpl_tf,0,$filtpl_m[0][1]);
		$filtpl_tag=$filtpl_m[1][0];//tag for first begin
		$filtpl_after=substr($filtpl_tf,$filtpl_m[0][1]+strlen("<!-- BEGIN $filtpl_tag -->"));
		if (!preg_match("#<!-- END $filtpl_tag -->#i",$filtpl_after, $filtpl_m , PREG_OFFSET_CAPTURE)) die("begin $filtpl_tag with no end");
		$filtpl_loop=substr($filtpl_after,0,$filtpl_m[0][1]);
		$filtpl_after=substr($filtpl_after,$filtpl_m[0][1]+strlen("<!-- END $filtpl_tag -->"));
		//string is splitted in before.loop.after -- lets recurse!
		//is it a loop or just a if ? (ugly test : if [0] exists it is supposed to be a loop)
		if ($filtpl_tt[$filtpl_tag][0])
			foreach ($filtpl_tt[$filtpl_tag] as $filtpl_tt_sub) //lets loop
				$filtpl_end.=filtpl_compile($filtpl_loop,$filtpl_tt_sub);
		else // lets do the unique loop
			$filtpl_end.=filtpl_compile($filtpl_loop,$filtpl_tt[$filtpl_tag]);
		$filtpl_end.=filtpl_compile($filtpl_after,$filtpl_tt); //recurse the afterloop
	}
	//substitute known values of root level
	foreach ($filtpl_tt as $filtpl_key=>$filtpl_val)
		if (!is_array($filtpl_val))
			$filtpl_before=preg_replace("/\{$filtpl_key\}/i",preg_replace("/\{/","{*",$filtpl_val),$filtpl_before);
	$filtpl_before=preg_replace("/\{\w+\}/i","" ,$filtpl_before);//zap unknown values
	$filtpl_before=preg_replace("/\{\*/"    ,"{",$filtpl_before);//for escaped values containing {}
	return($filtpl_before.$filtpl_end);
}

/*--- example , just for the eyes ---
//$tf=join("",file("example.htm")); 
$tf="{title}
<!-- BEGIN prd_list -->
{column_one} {column_two}
	<!-- BEGIN if_ok -->{msg}<!-- END if_ok -->
<!-- END prd_list -->
";
$tt=array();//create a string and an array
$tt["titre"]= "hello world";//root level
$tt["prd_list"]= array();//loop
$list=mysql_query("select column_one,column_two from a_table");
while ($rs=mysql_fetch_array($list)){
	$tt_prd_list= array();//loop level data
	foreach (array_keys($rs) as $chp)
		$tt_prd_list[$chp]=$rs[$chp]; //fill each items
	if ($rs["column_one"]==1) // "if" clause
		$tt_prd_list["if_ok"]=array("msg"=>"OK");
	array_push($tt["prd_list"],$tt_prd_list);//fill loop
}
//debug : print"<pre>";print_r($tt);print"</pre>";
print filtpl_compile($tf,$tt);//pparse in other langages
*/
?>