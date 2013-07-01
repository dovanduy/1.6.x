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
if(isset($_GET["search-events"])){search_events();exit;}

page();

function GetPrivs(){
	$users=new usersMenus();
	
	if($_GET["prepend"]=="coova-chilli"){
		if($users->AsHotSpotManager){return true;}
	}
	
	if($_GET["prepend"]=="C-ICAP"){
		if($users->AsDansGuardianAdministrator){return true;}
	}	
	
	if($users->AsSystemAdministrator){return true;}
}

function page(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen(null,"search-events","&prepend={$_GET["prepend"]}");
	
}

function search_events(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$total=0;
	
	
	$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	$removeService=false;
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}
	
	if($_POST["qtype"]=="service"){
		if($_POST["query"]<>null){
			$_GET["prefix"]=$_POST["query"];
			$_POST["query"]=null;
		}
	}
	
	if($_GET["force-prefix"]<>null){
		if($_POST["qtype"]=="service"){$_POST["query"]=null;}
		$_GET["prefix"]=$_GET["force-prefix"];
		$removeService=true;
	}
	
	
	
	if($_GET["search-events"]<>null){
	
		$search=base64_encode($_GET["search-events"]);
		$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?syslog-query=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));
		$total = count($array);
	
	}else{
		$array=unserialize(base64_decode($sock->getFrameWork("cmd.php?syslog-query=&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));
		$total = count($array);
	}
	krsort($array);
	$today=$tpl->_ENGINE_parse_body("{today}");
	$c=0;
while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
			$date=null;
			$host=null;
			$service=null;
			$pid=null;
			$color="black";
			
			$class=LineToClass($line);
			$style="<span style='color:$color'>";
			$styleoff="</span>";
			
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?)\[([0-9]+)\]:\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=strtolower($re[5]);
			$pid=$re[6];
			$line=$re[7];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
			$line=zClean($line);
			$tr[]="
			<tr class='$class'>
			<td width=5% nowrap>$date</td>
			<td nowrap>$service</td>
			<td nowrap>$pid</td>
			<td width=95%>$line</td>
			</tr>
			";	
			continue;
			
			}
		
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?):\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=strtolower($re[5]);
			$pid=null;
			$line=$re[6];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){
				$date=$today." ".date('H:i:s',strtotime($date));
			}else{
				$date=date('m-d H:i:s',strtotime($date));
			}
			$line=zClean($line);
			$tr[]="
			<tr class='$class'>
			<td width=5% nowrap>$date</td>
			<td nowrap>$service</td>
			<td nowrap>$pid</td>
			<td width=95%>$line</td>
			</tr>
			";
			continue;				
		}
		$service=strtolower($re[5]);
		$line=zClean($line);
			$tr[]="
			<tr class='$class'>
			<td width=5% nowrap>$date</td>
			<td nowrap>$service</td>
			<td nowrap>$pid</td>
			<td width=95%>$line</td>
			</tr>
			";
		
		

	}

	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
	
			<thead>
				<tr>
					<th width=1%>{date}</th>
					<th width=1%>&nbsp;</th>
					<th width=1%>&nbsp;</th>
					<th width=99%>{events}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
	
			</table>";
	
}
function zClean($line){
	
	$line=trim($line);
	if(substr($line,0,1)==":"){$line=substr($line, 1,strlen($line));}
	$line=trim($line);
	$line=str_replace("#012", "", $line);
	return $line;
}

