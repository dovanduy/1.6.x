<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
session_start();
include_once("ressources/class.templates.inc");
include_once("ressources/class.ldap.inc");
$user=new usersMenus();
$tpl=new Templates();
if($user->AsPostfixAdministrator==false){echo $tpl->_ENGINE_parse_body('{no privileges}');exit;}
if(isset($_GET["post"])){echo events();exit;}


if(isset($_GET["events-list"])){events_search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");

	$title=$tpl->_ENGINE_parse_body("{today}: {fetchmail_events} ".date("H")."h");
	
	$t=time();
	$html="
	<div style='margin:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes',
	dataType: 'json',
	colModel : [
		
		{display: '$events', name : 'events', width : 859, sortable : true, align: 'left'},
		],
	
	searchitems : [
		{display: '$events', name : 'events'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 896,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	 

</script>
	
	
	";
	
	echo $html;
	
}

function events_search(){
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();


	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?fetchmail-logs=yes&search=$search&rp={$_POST["rp"]}")));
		$total=count($datas);
		
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?fetchmail-logs=yes&rp={$_POST["rp"]}")));
		$total=count($datas);
	}
	
		
	$pageStart = ($page-1)*$rp;
	
	if(isset($_POST["sortname"])){
		if($_POST["sortorder"]=="asc"){krsort($datas);}
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$c=0;

	while (list ($key, $line) = each ($datas) ){
		$line=utf8_encode($line);
		if(trim($line)==null){continue;}
		$c++;
		if(preg_match("#(FATAL|failed|error|failure|AUTHFAIL)#i", $line)){$line="<span style='color:#680000'>$line</line>";}
		if(preg_match("#abnormally#i", $line)){$line="<span style='color:#680000'>$line</line>";}
		if(preg_match("#Reconfiguring#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		if(preg_match("#Accepting HTTP#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		if(preg_match("#Ready to serve requests#i", $line)){$line="<span style='color:#003D0D;font-weight:bold'>$line</line>";}
		
			
	$data['rows'][] = array(
		'id' => md5($line),
		'cell' => array(
			"<div style='font-size:13.5px'>$line</div>"
			)
		);

	}
	
	$data['total'] = $c;
	echo json_encode($data);	
}
?>