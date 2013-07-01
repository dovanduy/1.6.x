<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


if(isset($_GET["verbose"])){$GLOBALS["DEBUG_PRIVS"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
BuildSessionAuth();
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsProxyMonitor){senderrors("Access denied");}

if(isset($_GET["maintenant"])){maintenant();exit;}
if(isset($_GET["today"])){today();exit;}
if(isset($_GET["search-today"])){today_search();exit;}

if(isset($_GET["search"])){search();exit;}
page();

function page(){
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$array["{now}"]="$page?maintenant=yes";
	$array["{today}"]="$page?today=yes";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
	
}

function maintenant(){

	$boot=new boostrap_form();
	echo $boot->SearchFormGen("uid,ipaddr,sitename,words","search");

}
function today(){

	$boot=new boostrap_form();
	echo $boot->SearchFormGen("uid,ipaddr,sitename,words","search-today");

}

function search(){
	
	$now=date("YmdH");
	$tablename="searchwords_$now";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($tablename)){senderror("$tablename no such table");}
	$tpl=new templates();
	$search=string_to_flexquery("search");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$sql="SELECT * FROM $tablename WHERE 1 $search ORDER BY zDate DESC LIMIT 0,500";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}
	
			
	while ($ligne = mysql_fetch_assoc($results)) {
		//$ligne["words"]=utf8_decode($ligne["words"]);
		$time=strtotime($ligne["zDate"]);
		$ligne["zDate"]=date("H:i:s",$time);
		$tr[]="
		<tr>
		<td nowrap><i class='icon-time' ></i>&nbsp;{$ligne["zDate"]}</a></td>
		<td nowrap><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</a></td>
		<td>{$ligne["ipaddr"]}</a></td>
		<td>{$ligne["words"]}</td>
		<td><i class='icon-info-globe'></i>&nbsp;{$ligne["sitename"]}</td>
		<td nowrap><i class='icon-info-globe'></i>&nbsp;{$ligne["familysite"]}</td>
		</tr>";
		
	}
		
	echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{member}</th>
					<th>{ipaddr}</th>
					<th>{words}</th>
					<th colspan=2>{sitename}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";	
}

function today_search(){

	$now=date("Ymd");
	$tablename="searchwordsD_$now";
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($tablename)){senderror("$tablename no such table");}
	$tpl=new templates();
	$search=string_to_flexquery("search-today");
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}

	$sql="SELECT * FROM $tablename WHERE 1 $search ORDER BY `hour` DESC LIMIT 0,500";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error);}

		
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["words"]=utf8_decode($ligne["words"]);
		$time=strtotime($ligne["zDate"]);
		$ligne["zDate"]=date("H:i:s",$time);
		$tr[]="
		<tr>
		<td nowrap><i class='icon-time' ></i>&nbsp;{$ligne["hour"]}h</a></td>
		<td nowrap><i class='icon-user'></i>&nbsp;{$ligne["uid"]}</a></td>
		<td>{$ligne["ipaddr"]}</a></td>
		<td>{$ligne["words"]}</td>
		<td><i class='icon-info-globe'></i>&nbsp;{$ligne["sitename"]}</td>
		<td nowrap><i class='icon-info-globe'></i>&nbsp;{$ligne["familysite"]}</td>
		</tr>";

	}

	echo $tpl->_ENGINE_parse_body("

				<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{date}</th>
					<th>{member}</th>
					<th>{ipaddr}</th>
					<th>{words}</th>
					<th colspan=2>{sitename}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>";
}