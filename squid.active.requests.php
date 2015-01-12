<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){echo header("content-type: application/x-javascript");"alert('No privs!');";die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["list"])){showlist();exit;}
	if(isset($_GET["refresh"])){refresh();exit;}
js();



function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("{active_requests}");
	$page=CurrentPageName();
	$html="
	function Start$t(){
		YahooWin5('950','$page?popup=yes','$title')
	}
	
	Start$t();";
	
	echo $html;
	
	
}
function popup(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$uid=$tpl->javascript_parse_text("{uid}");
	$size=$tpl->javascript_parse_text("{size}");
	// ipaddr        | familysite            | servername                                | uid               | MAC               | size
	$t=time();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$title=$tpl->javascript_parse_text("{active_requests}::$ActiveRequestsNumber");
	$html="
<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
<script>
function StartLogsSquidTable$t(){

$('#flexRT$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
	{display: '$uid', name : 'uid', width : 141, sortable : false, align: 'left'},
	{display: '$ipaddr', name : 'ipaddr', width :95, sortable : false, align: 'left'},
	{display: '$familysite', name : 'familysite', width : 349, sortable : false, align: 'left'},
	{display: '$size', name : 'size', width : 114, sortable : false, align: 'right'},
	{display: '$duration', name : 'size2', width : 142, sortable : false, align: 'right'},
	],

	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$familysite', name : 'familysite'},
	{display: '$uid', name : 'uid'},
	
	],
	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span id=title-$t style=font-size:22px>$title</span>',
	useRp: true,
	rp: 500,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

StartLogsSquidTable$t();
Loadjs('$page?refresh=yes&id=$t');
</script>
";
echo $html;
}

function refresh(){
	header("content-type: application/x-javascript");
	$t=$_GET["id"];
	$page=CurrentPageName();
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{active_requests}::$ActiveRequestsNumber");
	
	echo "function RefreshActiveRequests(){
				if(!document.getElementById('flexRT$t')){return;}
				if(!YahooWin5Open()){return;}
				document.getElementById('title-$t').innerHTML='$title';
				$('#flexRT$t').flexReload();
				Loadjs('$page?refresh=yes&id=$t');
			}
			
	setTimeout('RefreshActiveRequests()',3000);";
	
	
}


function showlist(){
	$page=1;
	
	$sock=new sockets();
	$sock->getFrameWork("squid.php?shock-active-requests=yes");
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$q=new mysql_squid_builder();
	$searchstring=string_to_flexregex();
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$data = array();
	$data['page'] = 1;
	$data['total'] = 0;
	$data['rows'] = array();

	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
$c=0;
	while (list ($num, $ligne) = each ($ActiveRequestsR["connections"]) ){
		if($searchstring<>null){if(!preg_match("#$searchstring#", serialize($ligne))){continue;}}
		$c++;
		$ipaddr=$ligne["IPS"];
		$uri=$ligne["uri"];
		$arrayURI=parse_url($uri);
		$familysite=$arrayURI["host"];
		$uid=$ligne["USERS"];
		$bytes=$ligne["bytes"];
		$seconds=$ligne["seconds"];
		
		$avg_speed = $bytes / 1024;
		if ($seconds > 0) {
			$avg_speed /= $seconds;
		}
		
		$duration=duration($seconds);
		$size=FormatBytes($bytes/1024);
		$data['rows'][] = array(
				'id' => md5(serialize($ligne)),
				'cell' => array("<span style='font-size:14px'>$uid</span>",
						"<span style='font-size:14px'>$ipaddr</span>",
						"<span style='font-size:14px'>$familysite</span>",
						"<span style='font-size:14px'>$size</span>",
						"<span style='font-size:14px'>$duration</span>",
						
						)
		);
	}

	$data['total'] = $c;
	echo json_encode($data);
}
function duration ($seconds) {
	$takes_time = array(604800,86400,3600,60,0);
	$suffixes = array("w","d","h","m","s");
	$output = "";
	foreach ($takes_time as $key=>$val) {
		${$suffixes[$key]} = ($val == 0) ? $seconds : floor(($seconds/$val));
		$seconds -= ${$suffixes[$key]} * $val;
		if (${$suffixes[$key]} > 0) {
			$output .=  ${$suffixes[$key]};
			$output .= $suffixes[$key]." ";
		}
	}
	return trim($output);
}