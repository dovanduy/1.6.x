<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");


if(isset($_GET["section"])){section();exit;}
if(isset($_GET["search"])){search();exit;}


function section(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen(null,"search",null);
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($SearchQuery);	
	
	
}
function CheckRights(){
	$user=new usersMenus();
	if($user->AsPostfixAdministrator){return true;}
	if($user->AsMailBoxAdministrator){return true;}
	return false;
}

function search(){
	if(!CheckRights()){senderror("{ERROR_NO_PRIVS}");}
	$boot=new boostrap_form();
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$tpl=new templates();
	$t=time();
	$query=base64_encode($_GET["search"]);
	if(!is_numeric($_POST["rp"])){$_POST["rp"]=500;}
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_POST["rp"]}&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}")));
	krsort($array);
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$hostTXT=$tpl->_ENGINE_parse_body("{host}");
	$serviceTXT=$tpl->_ENGINE_parse_body("{servicew}");
	$eventsTXT=$tpl->_ENGINE_parse_body("{events}");
	
	while (list ($index, $line) = each ($array) ){
		$lineenc=base64_encode($line);
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
		}
	
		$class=LineToClass($line);
		$img=statusLogs($line);
	
		$loupejs="ZoomEvents('$lineenc')";
		
		$trSwitch=$boot->trswitch("blur()");
		
		$tr[]="
		<tr id='$id' class=$class $trSwitch>
		<td style='font-size:12px' $trSwitch width=1% nowrap><i class='icon-time'></i>&nbsp;$date</td>
		<td style='font-size:12px' nowrap $trSwitch width=1% nowrap><i class='icon-arrow-right'></i>&nbsp;$host</td>
		<td style='font-size:12px' nowrap $trSwitch width=1% nowrap>$service</td>
		<td style='text-align:center;font-size:12px' width=1% nowrap>$pid</td>
		<td style='text-align:center;font-size:12px' width=1% nowrap><img src='$img'></td>
		<td style='font-size:12px' nowrap $trSwitch width=99% nowrap>$line</td>
		</tr>";		
		
	}
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>$zDate</th>
					<th>$hostTXT</th>
					<th>$serviceTXT</th>
					<th>PID</th>
					<th colspan=2>$eventsTXT</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
				</table>
				<script>

	</script>
	";	
	
}