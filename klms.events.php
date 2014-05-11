<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["search"])){search();exit;}
	
	popup();

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$empty=$tpl->_ENGINE_parse_body("{empty}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$check_recipients=$tpl->_ENGINE_parse_body("{check_recipients}");
	$TB_WIDTH=872;
	$TB2_WIDTH=610;
	$TB_HEIGHT=600;
	$TB_EV=734;
	if(is_numeric($_GET["TB_WIDTH"])){$TB_WIDTH=$_GET["TB_WIDTH"];}
	if(is_numeric($_GET["TB_HEIGHT"])){$TB_HEIGHT=$_GET["TB_HEIGHT"];}
	if(is_numeric($_GET["TB_EV"])){$TB_EV=$_GET["TB_EV"];}
	$t=time();
	$SE_SERV="{display: '$service', name : 'service'},";
//	$TB_SERV="{display: '$service', name : 'zDate', width :97, sortable : false, align: 'left'},";
	if($_GET["force-prefix"]<>null){$MasterTitle=$_GET["force-prefix"];$TB_SERV=null;$SE_SERV=null;}
	if($_GET["prepend"]<>null){$MasterTitle=$MasterTitle."&raquo;{$_GET["prepend"]}";}
	
	
	
	$buttons="
	buttons : [
	{name: '$check_recipients', bclass: 'eMail', onpress : check_recipients},
	
		],	";
	
	
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?search=yes&prepend={$_GET["prepend"]}&force-prefix={$_GET["force-prefix"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width :93, sortable : true, align: 'left'},
		$TB_SERV
		
		{display: '$events', name : 'events', width : $TB_EV, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: '$events', name : 'events'},
		
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$MasterTitle',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height:$TB_HEIGHT ,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function check_recipients(){
	Loadjs('postfix.debug.mx.php?t=$t');
}
	
</script>";
	
	echo $html;
	return ;

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$users=new usersMenus();
	$page=1;
	$total=0;
	
	
	$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	$removeService=false;
	$maillogpath=$users->maillog_path;
	
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
	


	if($_POST["query"]<>null){

		$search=base64_encode(string_to_regex($_POST["query"]));
		$sock->getFrameWork("klms.php?syslog-query=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}&maillog=$maillogpath");
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);
		
	}else{
		$sock->getFrameWork("klms.php?syslog-query=&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}&maillog=$maillogpath");
		$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
		$total = count($array);
	}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}
	
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if(trim($line)==null){continue;}
			$date=null;
			$host=null;
			$service=null;
			$pid=null;
			$color="black";
			if(preg_match("#(ERROR|WARN|FATAL|UNABLE|crashed|out of date|expires soon)#i", $line)){$color="#D61010";}
				
			$style="<span style='color:$color'>";
			$styleoff="</span>";
			

		
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?):\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=null;
			$line=$re[6];
			$date=date('m-d H:i:s',strtotime($date));
			if($_GET["prefix"]=="haproxy"){$line=Parse_haproxy($line);}
			$lines=array();
			$lines[]="$style$date$styleoff";
			$lines[]="$style$line$styleoff";			
			
			$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
			continue;				
		}
		
			if($_GET["prefix"]=="haproxy"){$line=Parse_haproxy($line);}
			$lines=array();
			$lines[]="$style$date$styleoff";
			if(!$removeService){$lines[]="$style$service$styleoff";}
			$lines[]="$style$pid$styleoff";
			$lines[]="$style$line$styleoff";			
		
		$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
		

	}
	$data['total'] = $c;
	
echo json_encode($data);		

}


