<?php
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["DEBUG_PRIVS"]=true;
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.mysql.inc');

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){echo "<H1>No right!!!</H1>";die();}
if(isset($_GET["search"])){search();exit;}


page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if(!isset($_COOKIE["squid-nodes-search"])){
		if(isset($_SESSION["squid-nodes-search"])){
			$_COOKIE["squid-nodes-search"]=$_SESSION["squid-nodes-search"];
		}
	}
	
	$html="
	
	<table style='width:95%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{search}:</td>
		<td class=android>
		". Field_text("nodes-search",$_COOKIE["squid-nodes-search"],"font-size:18px;font-weight:bold",null,null,null,null,"SquidNodesCheck(event)")."
		</td>
	</tr>
	</table>
		
	
	<div id='squid-nodes-results'></div>
	<script>
		
	function SquidNodesCheck(e){
		if(!checkEnter(e)){return;}
		SquidNodesSearch();
	}
	
	function SquidNodesSearch(){
		Set_Cookie('squid-nodes-search', document.getElementById('nodes-search').value, '3600', '/', '', '');
		var pp=encodeURIComponent(document.getElementById('nodes-search').value);
		LoadAjax('squid-nodes-results','$page?search='+pp);
	
	}
	SquidNodesSearch();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	
	
	$query=url_decode_special_tool(trim($_GET["search"]));
	$_SESSION["squid-nodes-search"]=$query;
	if($query<>null){
		$query="*$query*";
		$query=str_replace("**", "*", $query);
		$query=str_replace("*", "%", $query);
		$query="WHERE ( (UserAgent LIKE '$query') OR (ipaddr LIKE '$query') OR (uid LIKE '$query')  OR (MAC LIKE '$query') OR (hostname LIKE '$query') )";
		
	}
	
	$sql="SELECT * FROM  UserAutDB $query LIMIT 0,50";
	$results=$q->QUERY_SQL($sql); 	
	$classtr=null;
	
		$html="
		
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:95%'>
	<thead class='thead'>
		<tr>
		<th>{uid}</th>
		<th>{hostname}</th>
		</tr>
	</thead>
	<tbody class='tbody'>";	
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(strlen($ligne["UserAgent"])>95){$ligne["UserAgent"]=substr($ligne["UserAgent"], 0,90)."...";}
		
		if($ligne["MAC"]<>null){
			$ligne["MAC"]="<span style='font-size:16px'>&nbsp;|&nbsp;</span><a href=\"javascript:Loadjs('android.squid.show.statsfrom.php?field=MAC&value={$ligne["MAC"]}')\" style='font-size:16px;text-decoration:underline'>{$ligne["MAC"]}</a>";
		}
		
		if($ligne["ipaddr"]<>null){
			$ligne["ipaddr"]="<a href=\"javascript:Loadjs('android.squid.show.statsfrom.php?field=ipaddr&value={$ligne["ipaddr"]}')\" style='font-size:16px;text-decoration:underline'>{$ligne["ipaddr"]}</a>";
		}
		
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $ligne["hostname"])){
			$ligne["ipaddr"]=null;
			$ligne["hostname"]="<a href=\"javascript:Loadjs('android.squid.show.statsfrom.php?field=ipaddr&value={$ligne["hostname"]}')\" style='font-size:16px;text-decoration:underline'>{$ligne["hostname"]}</a>";
		}else{
			$ligne["hostname"]="<a href=\"javascript:Loadjs('android.squid.show.statsfrom.php?field=hostname&value={$ligne["hostname"]}')\" style='font-size:16px;text-decoration:underline'>{$ligne["hostname"]}</a>";
		}
		
		if($ligne["UserAgent"]<>null){$ligne["UserAgent"]="<br><span style='font-size:11px'>{$ligne["UserAgent"]}</span>";}
		
		$infos="<br>
		<i>{$ligne["ipaddr"]}{$ligne["MAC"]}{$ligne["UserAgent"]}</i>
		</span>";
		
		if($ligne["uid"]=="-"){$ligne["uid"]=null;}
		
		
		$html=$html."
		<tr class=$classtr>
			<td style='font-size:16px' width=1% nowrap>{$ligne["uid"]}</td>
			<td style='font-size:16px' width=99%>{$ligne["hostname"]}$infos</td>
		</tr>
		
		";
	}
	$html=$html."</table>";			
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


