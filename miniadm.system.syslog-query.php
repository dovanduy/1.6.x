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

if(isset($_GET["section"])){syslog_section();exit;}
if(isset($_GET["search"])){syslog_search();exit;}
syslog_section();

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
		if($users->AsSystemAdministrator){return true;}
}
function syslog_section(){
	$EXPLAIN=array();
	$boot=new boostrap_form();
	$tpl=new templates();
	//$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_website}", "YahooWin5('690','freewebs.HTTrack.php?item-id=0&t=0','WebCopy::{new_website}');"));
	echo $boot->SearchFormGen("service","search",null);
}


function syslog_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$pattern=base64_encode(string_to_flexregex("search"));
	$sock=new sockets();
	$removeService=false;


	if($pattern<>null){

		$search=base64_encode($_POST["query"]);
		$sock->getFrameWork("cmd.php?syslog-query=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}");
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);

	}else{
		$sock->getFrameWork("cmd.php?syslog-query=&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}");
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);
	}

	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	$today=$tpl->_ENGINE_parse_body("{today}");
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
		$date=null;
		$host=null;
		$service=null;
		$pid=null;

		$trClass=LineToClass($line);
		
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?)\[([0-9]+)\]:\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=$re[6];
			$line=$re[7];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
		
				
			
			$tr[]="
			<tr class=$trClass>
			<td width=1% nowrap>$date</td>
			<td width=1% nowrap>$service</td>
			<td width=1% nowrap>$pid</td>
			<td width=80%>$line</td>
			</tr>
			";			
			
			
			continue;
		}

		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?):\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=null;
			$line=$re[6];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
			$tr[]="
			<tr class=$trClass>
			<td width=1% nowrap>$date</td>
			<td width=1% nowrap>$service</td>
			<td width=1% nowrap>$pid</td>
			<td width=80%>$line</td>
			</tr>
			";	
				
			
			continue;
		}

		
			$tr[]="
			<tr class=$trClass>
				<td width=1% nowrap>$date</td>
				<td width=1% nowrap>$service</td>
				<td width=1% nowrap>$pid</td>
				<td width=80%>$line</td>
			</tr>
			";	

		


	}
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{service}</th>
					<th>PID</th>
					<th>{event} ( $total {events} )</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody>
			 </table>
			 ";

	

}