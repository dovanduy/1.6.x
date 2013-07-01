<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}


include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
$PRIV=GetPrivs();if(!$PRIV){header("location:miniadm.index.php");die();}

if(isset($_GET["section"])){artica_section();exit;}
if(isset($_GET["search"])){artica_search();exit;}
artica_section();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats-start.php';</script>", $content);
		echo $content;	
		return;
	}	
	*/
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function GetPrivs(){
	$users=new usersMenus();
	
		if($_GET["context"]=="webcopy"){
			if($users->AsWebMaster ){return true;}
			if($users->AsSystemWebMaster ){return true;}
		}
	
		
		if($users->AsSystemAdministrator){return true;}
}
function artica_section(){
	$EXPLAIN=array();
	$boot=new boostrap_form();
	$tpl=new templates();
	//$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_website}", "YahooWin5('690','freewebs.HTTrack.php?item-id=0&t=0','WebCopy::{new_website}');"));
	echo $boot->SearchFormGen("context,text,process,content","search","&context={$_GET["context"]}&category={$_GET["category"]}");
}


function artica_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$title=$tpl->_ENGINE_parse_body("{event}");
	$q=new mysql();
	$searchstring=string_to_flexquery("search");
	$today=$tpl->_ENGINE_parse_body("{today}");
	$contextQ="1";
	if($_GET["context"]<>null){
		$contextQ=" category='{$_GET["context"]}'";
	}
	
	if($q->TABLE_EXISTS("Taskev0", "artica_events"))
	$sql="SELECT *  FROM `Taskev0` WHERE $contextQ AND 
	description LIKE '%". string_to_sql_search($_GET["search"])."%' ORDER BY zDate DESC LIMIT 0,500";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}
	
	$tt=date("Y-m-d");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,$today,$ligne["zDate"]);		
		$affiche_text=$ligne["description"];
		$trClass=LineToClass($affiche_text);
		
		$tooltip="
		<div style='font-size:11px'>
		<strong>{date}:&nbsp;$original_date&nbsp;|&nbsp;
		<strong>{function}:&nbsp;{$ligne["function"]} in line {$ligne["line"]}</strong>&nbsp;|&nbsp;
		<strong>{process}:&nbsp;{$ligne["filename"]}</strong>";		
		$tooltip=$tpl->_ENGINE_parse_body($tooltip);
		
		$tr[]="
		<tr class=$trClass>
		<td width=1% nowrap>{$ligne["zDate"]}</td>
		<td width=1% nowrap>{$ligne["category"]}</td>
		<td width=1% nowrap>$affiche_text$tooltip</td>
		</tr>
		";		
		
	}	
	
	
	
	
	$contextQ="1";
	if($_GET["context"]<>null){
		$contextQ=" context='{$_GET["context"]}'";
	}
	
	$sql="SELECT *  FROM `events` WHERE $contextQ $searchstring ORDER BY zDate DESC LIMIT 0,1000";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){senderror($q->mysql_error."<br>$sql");}	

	$tt=date("Y-m-d");

	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["process"]==null){$ligne["process"]="{unknown}";}
		$original_date=$ligne["zDate"];
		$ligne["zDate"]=str_replace($tt,$today,$ligne["zDate"]);
		if($ligne["process"]==null){$ligne["process"]="{unknown}";}
		$ligne["context"]=$tpl->_ENGINE_parse_body($ligne["context"]);
		$ligne["content"]=$tpl->_ENGINE_parse_body($ligne["content"]);
		
	
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\s+:(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\:\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+\((.+?)\)\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\((.+?)\)\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[2];$computer=$re[1];}
		if(preg_match("#\[.+?\]:\s+\[.+?\]:\s+(.+)#",$ligne["text"],$re)){$ligne["text"]=$re[1];}
	
		$affiche_text=$ligne["text"];
		$trClass=LineToClass($affiche_text);
		
	
		$tooltip="
		<div style='font-size:11px'>
			<strong>{date}:&nbsp;$original_date&nbsp;|&nbsp;
			<strong>{computer}:&nbsp;$computer</strong>&nbsp;|&nbsp;
			<strong>{process}:&nbsp;{$ligne["process"]}</strong>";
	
		
		if(preg_match("#<body>(.+?)</body>#is",$ligne["content"],$re)){$content=strip_tags($re[1]);}else{$content=strip_tags($ligne["content"]);}
		if(strlen($content)>300){$content=substr($content,0,290)."...";}
	
		$ID=$ligne["ID"];
		$js="articaShowEvent($ID);";
	
		$color="5C81A7";
		if(preg_match("#(error|fatal|unable)#i",$affiche_text)){$color="B50113";}
	
		
		
		$time=strtotime($original_date." 00:00:00");
		
		$tooltip=$tpl->_ENGINE_parse_body($tooltip);
		
	
		$OBS="<div style='font-weight:bold;margin:0px;padding:0px'>$affiche_text</div><div>$content</div>
		";
		$ligne["zDate"]=$tpl->_ENGINE_parse_body($ligne["zDate"]);
		
		$tr[]="
		<tr class=$trClass>
		<td width=1% nowrap>{$ligne["zDate"]}</td>
		<td width=1% nowrap>{$ligne["context"]}</td>
		<td width=1% nowrap>$OBS$tooltip</td>
		</tr>
		";		
		

	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{context}</th>
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody>
			 </table>
<script>
			 		
function articaShowEvent(ID){
		 YahooWin6('900','artica.events.php?ShowID='+ID,'$title::'+ID);
	}			

</script>
";
	

}