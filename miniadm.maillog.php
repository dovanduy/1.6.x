<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

$users=new usersMenus();
if(!$users->AllowUserMaillog){header("location:miniadm.messaging.php");die();}


if(isset($_GET["content"])){content();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["table-list"])){table_list();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
$html="<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{organization}&nbsp&raquo;{$_SESSION["ou"]}</H1>
		<p>{messaging_events_text}</p>
	</div>
<div class=BodyContentWork id='$t'></div>

<script>LoadAjax('$t','$page?table=yes')</script>

";	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$t=time();
	$domain=$tpl->_ENGINE_parse_body("{domain}");
	$title=$tpl->_ENGINE_parse_body("{POSTFIX_EVENTS}");
	$relay=$tpl->javascript_parse_text("{relay}");
	$MX_lookups=$tpl->javascript_parse_text("{MX_lookups}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$InternetDomainsAsOnlySubdomains=$sock->GET_INFO("InternetDomainsAsOnlySubdomains");
	if(!is_numeric($InternetDomainsAsOnlySubdomains)){$InternetDomainsAsOnlySubdomains=0;}
	$add_local_domain_form_text=$tpl->javascript_parse_text("{add_local_domain_form}");
	$add_local_domain=$tpl->_ENGINE_parse_body("{add_local_domain}");
	$sender_dependent_relayhost_maps_title=$tpl->_ENGINE_parse_body("{sender_dependent_relayhost_maps_title}");
	$ouescape=urlencode($ou);
	$destination=$tpl->javascript_parse_text("{destination}");
	$events=$tpl->javascript_parse_text("{events}");
	$hostname=$_GET["hostname"];
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$host=$tpl->_ENGINE_parse_body("{host}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$form="<div style='width:900px' class=form>";
	if(isset($_GET["noform"])){$form="<div style='margin-left:-15px'>";}
	if($_GET["mimedefang-filter"]=="yes"){
		$title=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{events}");
	}
$html="
<div style='margin-left:-20px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
</div>
	
<script>
var memid='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?table-list=yes&hostname=$hostname&t=$t&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width : 58, sortable : true, align: 'left'},
		{display: '$service', name : 'host', width : 71, sortable : true, align: 'left'},
		{display: '$events', name : 'events', width :768, sortable : true, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'zDate'},
		],
	sortname: 'events',
	sortorder: 'asc',
	usepager: true,
	title: '$title {$_SESSION["uid"]}',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 954,
	height: 600,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

</script>
";
	
	echo $html;
}

function table_list(){
	
		$ct=new user($_SESSION["uid"]);
		$emails_addresses=base64_encode(serialize($ct->HASH_ALL_MAILS));
	
	$sock=new sockets();
	$users=new usersMenus();
	$maillog_path=$users->maillog_path;
	$query=base64_encode($_POST["query"]);
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?query-maillog=yes&filter=$query&maillog=$maillog_path&rp={$_POST["rp"]}&zarafa-filter={$_GET["zarafa-filter"]}&mimedefang-filter={$_GET["mimedefang-filter"]}&emails=$emails_addresses")));
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	
	while (list ($index, $line) = each ($array) ){
	
		if(preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?)\[([0-9]+)\]:(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=$re[4];
			$line=$re[5];
		}elseif (preg_match("#^[a-zA-Z]+\s+[0-9]+\s+([0-9\:]+)\s+(.+?)\s+(.+?):\s+(.+)#", $line,$re)){
			$date="{$re[1]}";
			$host=$re[2];
			$service=$re[3];
			$pid=0;
			$line=$re[4];	
		}
		
		
		
		$line=str_replace("127.0.0.1[127.0.0.1]:2003", "mailbox", $line);
		$line=str_replace("[127.0.0.1][127.0.0.1]", "local", $line);
		$line=str_replace("127.0.0.1", "local", $line);
		
		$line=str_replace("(unknown id)","", $line);
		
	
	$data['rows'][] = array(
				'id' => "dom$m5",
				'cell' => array("
				<span style='font-size:12px'>$date</span>",
				"<span style='font-size:12px'>$service</span>",
				"<span style='font-size:12px'>$line</span>")
				);	

				
	}
	$data['page'] = 1;
	$data['total'] =count($array);
	echo json_encode($data);		
	
}

