<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');


$user=new usersMenus();
if($user->AsMailBoxAdministrator==false){
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
}

if(isset($_GET["logs"])){logs();exit;}

table_main();





function table_main(){

	
$button="	buttons : [
	{name: '$new', bclass: 'add', onpress : NewRule$t},
	{name: '$apply', bclass: 'Apply', onpress : Apply$t},

	],";
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$iptable=$_GET["table"];
	$title=$tpl->javascript_parse_text("{APP_CYRUS} &laquo;{events}&raquo;");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rule=$tpl->javascript_parse_text("{rule}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$date=$tpl->javascript_parse_text("{zDate}");
	$from=$tpl->javascript_parse_text("{from}");
	$interface=$tpl->javascript_parse_text("{interface}");
	$to=$tpl->javascript_parse_text("{to}");
	$daemon=$tpl->javascript_parse_text("{daemon}");
	$event=$tpl->javascript_parse_text("{event}");
	$t=time();
	$button=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>

function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?logs=yes&eth=$eth',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'date', width :152, sortable : false, align: 'left'},
	{display: '$daemon', name : 'type1', width : 80, sortable : false, align: 'left'},
	{display: '$event', name : 'type2', width : 717, sortable : false, align: 'left'}
	],

	searchitems : [
		{display: '$event', name : 'rulename'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [30, 50,100,200,500]
	});
}

LoadTable$t();
</script>
";
	echo $html;

}

function logs(){
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$logfile="/usr/share/artica-postfix/ressources/logs/web/cyrus.log";
	$searchstring=urlencode(string_to_flexregex());
	if(!isset($_POST["rp"])){$_POST["rp"]=100;}
	$sock->getFrameWork("cyrus.php?cyrus-events=yes&rp={$_POST["rp"]}&search=$searchstring");
	$tpl=new templates();
	$results=explode("\n",@file_get_contents($logfile));
	@unlink($logfile);
	
	if(count($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($results);
	$data['rows'] = array();
	$q=new mysql_squid_builder();
	krsort($results);
	$c=0;
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		
		$color="black";
		if($GLOBALS["VERBOSE"]){echo "$line<hr>";}
		
		if(preg_match("#(.*?)\s+([0-9]+)\s+([0-9\:]+)\s+.*?cyrus\/(.*?)\[.*?\]:\s+(.*)#",$line,$re)){
			$date=strtotime("{$re[1]} {$re[2]} {$re[3]}");
			$datetext=time_to_date($date,true);
			if($GLOBALS["VERBOSE"]){print_r($re);}
			$service=$re[4];
			$line=trim($re[5]);
		}
		if($GLOBALS["VERBOSE"]){echo "$line<hr>";}

			
		$mkey=md5($line);
		$c++;
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<span style='font-size:12px;font-weight:normal;color:$color'>$datetext</span>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$service</span>",
						"<span style='font-size:12px;font-weight:normal;color:$color'>$line</center>",

					)
		);
		
	}
	if(count($c)==0){json_error_show("no data");}
	echo json_encode($data);
	
}
function time_to_date($xtime,$time=false){
	if(!class_exists("templates")){return;}
	$tpl=new templates();
	$dateT=date("{l} {F} d",$xtime);
	if($time){$dateT=date("{l} {F} d H:i:s",$xtime);}
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);if($time){$dateT=date("{l} d {F} H:i:s",$xtime);}}
	return $tpl->_ENGINE_parse_body($dateT);

}