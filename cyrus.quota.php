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
	$title=$tpl->javascript_parse_text("{APP_CYRUS} &laquo;{cyrquota}&raquo;");
	$user=$tpl->javascript_parse_text("{user}");
	$quota=$tpl->javascript_parse_text("{quota}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$used=$tpl->javascript_parse_text("{used}");
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
	<div style='font-size:16px' class=explain>{cyrquota_table_explain}</div>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>


function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?logs=yes&eth=$eth',
	dataType: 'json',
	colModel : [
	{display: '$user', name : 'user', width :371, sortable : false, align: 'left'},
	{display: '$quota', name : 'quota', width : 246, sortable : false, align: 'left'},
	{display: '$used', name : 'quota', width : 147, sortable : false, align: 'left'},
	{display: '$used', name : 'used', width : 147, sortable : false, align: 'left'}
	],

	searchitems : [
		{display: '$user', name : 'rulename'},
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
	$logfile="/usr/share/artica-postfix/ressources/logs/web/cyrquota.log";
	$searchstring=urlencode(string_to_flexregex());
	if(!isset($_POST["rp"])){$_POST["rp"]=100;}
	$sock->getFrameWork('cyrus.php?cyrquota=yes');
	$tpl=new templates();
	
	if($GLOBALS["VERBOSE"]){echo "<H1>Filesize:".filesize($logfile)."</H1>\n";}
	$results=explode("\n",@file_get_contents($logfile));
	@unlink($logfile);
	
	if(count($results)==0){json_error_show("no data");}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($results);
	$data['rows'] = array();
	
	$c=0;
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		
		$color="black";
		if($GLOBALS["VERBOSE"]){echo "$line<hr>";}
		
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $line)){continue;}
		}
		
		if(!preg_match('#(.*?)\s+(.*?)\s+(.*?)\s+user\/(.+)#',$line,$re)){continue;}
		
			if(trim($re[1])==null){$re[1]=$tpl->javascript_parse_text("{illimited}");}
			if(trim($re[2])<>null){$re[2]=$re[2]."%";}

			if(is_numeric($re[1])){$re[1]=FormatBytes($re[1]);}
		$mkey=md5($line);
		$c++;
		$data['rows'][] = array(
				'id' => "$mkey",
				'cell' => array(
						"<span style='font-size:18px;font-weight:normal;color:$color'>{$re[4]}</span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>{$re[1]}</span>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>{$re[2]}</center>",
						"<span style='font-size:18px;font-weight:normal;color:$color'>{$re[3]}</center>",

					)
		);
		
	}
	$data['total'] = count($data['rows']);
	if(count($data['rows'])==0){json_error_show("no data");}
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