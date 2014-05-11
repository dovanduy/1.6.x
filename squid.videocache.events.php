<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.tcpip.inc');
include_once('ressources/class.system.network.inc');

$user=new usersMenus();

if($user->AsSquidAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["search"])){search();exit;}

page();

function page(){

	$page=CurrentPageName();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$context=$tpl->_ENGINE_parse_body("{context}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$service=$tpl->_ENGINE_parse_body("{servicew}");
	$client=$tpl->_ENGINE_parse_body("{client}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	$uuid=$tpl->javascript_parse_text("{uuid}");
	$uri=$tpl->javascript_parse_text("{uri}");
	$t=time();
	$SE_SERV="{display: '$service', name : 'service'},";
	$TB_SERV="{display: '$service', name : 'zDate', width :97, sortable : false, align: 'left'},";
	if($_GET["force-prefix"]<>null){$MasterTitle=$_GET["force-prefix"];$TB_SERV=null;$SE_SERV=null;}
	if($_GET["prepend"]<>null){$MasterTitle=$MasterTitle."&raquo;{$_GET["prepend"]}";}
	$MasterTitle=$tpl->javascript_parse_text("VideoCache {events}");


$buttons="
buttons : [
{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},

],	";

$buttons=null;

$html="
<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
url: '$page?search=yes&prepend={$_GET["prepend"]}&force-prefix={$_GET["force-prefix"]}',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width :93, sortable : true, align: 'left'},
	{display: 'PID', name : 'zDate', width :40, sortable : false, align: 'left'},
	{display: '$client', name : 'ipaddr', width : 88, sortable : false, align: 'left'},
	{display: '$sitename', name : 'sitename', width : 69, sortable : false, align: 'left'},
	{display: 'HIT', name : 'HIT', width : 81, sortable : false, align: 'left'},
	{display: '$uuid', name : 'sitename', width : 128, sortable : false, align: 'left'},
	{display: '$uri', name : 'uri', width : 396, sortable : false, align: 'left'},
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
width: '99%',
height:450 ,
singleSelect: true,
rpOptions: [10, 20, 30, 50,100,200,500]

});
});

</script>";

echo $html;
return ;

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$page=1;
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



	

$search=base64_encode($_POST["query"]);
$sock->getFrameWork("squid.php?videocache-query=$search&prepend={$_GET["prepend"]}&rp={$_POST["rp"]}");
$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/videocache-query"));
$total = count($array);
$data = array();
$data['page'] = $page;
$data['total'] = $total;
$data['rows'] = array();
if($_POST["sortname"]<>null){
	if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
}
$today=$tpl->_ENGINE_parse_body("{today}");
$c=0;
if($GLOBALS["VERBOSE"]){echo "<H1>Array of ".count($array)." Lines</H1>\n";}
while (list ($key, $line) = each ($array) ){
	if(trim($line)==null){continue;}
	$date=null;
	$style="<span>";
	$styleoff="</span>";
	if(!preg_match("#(.+?)\s+([0-9]+)\s+([A-Z]+)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+(.*?)\s+#",$line,$re)){continue;}
	$xdate="{$re[1]}";
	$pid=$re[2];
	$logtyp=$re[3];
	$ipaddr=$re[4];
	$WEBSITE=$re[5];
	$HIT=$re[6];
	$uid=$re[7];
	$uri=$re[8];
		
	if(preg_match("#([0-9]+)\/(.+?)\/([0-9]+):([0-9]+):([0-9]+):([0-9]+)#", $xdate,$ri)){
		$day=$ri[1];
		$Month=$ri[2];
		$year=$ri[3];
		$hour=$ri[4];
		$min=$ri[5];
		$sec=$ri[6];
		$strtime="$day $Month $year $hour:$min:$sec";
		$strtotime=strtotime($strtime);
	}

	$zdate=date("Y-m-d H:i:s",$date);
	if(date("Y-m-d",$strtotime)==date("Y-m-d")){
		$date=$today." ".date('H:i:s',strtotime($date));
	}else{
		$date=date('m-d H:i:s',strtotime($date));
	}
		
			
	$lines=array();
	$lines[]="$style$date$styleoff";
	$lines[]="$style$pid$styleoff";
	$lines[]="$style$ipaddr$styleoff";
	$lines[]="$style$WEBSITE$styleoff";
	$lines[]="$style$HIT$styleoff";
	$lines[]="$style$uid$styleoff";
	$lines[]="$style$uri$styleoff";
			
	$c++;$data['rows'][] = array('id' => md5($line),'cell' => $lines);
	
}
	

if($c==0){json_error_show("no data");}
$data['total'] = $c;
echo json_encode($data);
}









