<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["items-list"])){items();exit;}
js();
function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$wpad=null;
	$title=$tpl->_ENGINE_parse_body("{APP_MONIT}");
	$html="YahooWin2('850','$page?popup=yes','$title')";
	echo $html;
}

function popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$services=$tpl->_ENGINE_parse_body("{services}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$uptime=$tpl->_ENGINE_parse_body("{uptime}");
	$children=$tpl->_ENGINE_parse_body("{children}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$cpu=$tpl->_ENGINE_parse_body("{cpu}");
	$title=$tpl->javascript_parse_text("{APP_MONIT}");
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	$sock=new sockets();
	$sock->getFrameWork("monit.php?chock-status=yes");
	if(is_file($cache_file)){
		$min=file_get_time_min($cache_file);
	}
	$t=time();
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
	<script>
	var DeleteGroupItemTemp=0;
	$(document).ready(function(){
	$('#table-$t').flexigrid({
	url: '$page?items-list=yes&ID=$ID',
	dataType: 'json',
	colModel : [
	{display: '$services', name : 'pattern', width : 304, sortable : true, align: 'left'},
	{display: '$status', name : 'none2', width : 99, sortable : false, align: 'left'},
	{display: '$uptime', name : 'none3', width : 111, sortable : false, align: 'left'},
	{display: '$children', name : 'none4', width : 47, sortable : false, align: 'left'},
	{display: '$memory', name : 'none4', width : 98, sortable : false, align: 'left'},
	{display: '$cpu', name : 'none5', width : 77, sortable : false, align: 'left'},
	
	],
	
	
	sortname: 'pattern',
	sortorder: 'asc',
	usepager: true,
	title: '$title {$min}mn',
	useRp: true,
	rp: 200,
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
	});
	
</script>
	
	";
	
	echo $html;
}
function items(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$FORCE_FILTER=null;
	$cache_file="/usr/share/artica-postfix/ressources/logs/web/monit.status.all";
	$sock=new sockets();
	
	if(!is_file($cache_file)){
		
		$sock->getFrameWork("monit.php?chock-status=yes");
		json_error_show("No cache file");
	}
	
	$array=unserialize(@file_get_contents($cache_file));
	
	$sock->getFrameWork("monit.php?chock-status=yes");
	if(count($array)==0){json_error_show("No data");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexregex();
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($array);
	$data['rows'] = array();
	
	
	while (list ($product, $array2) = each ($array) ){
	if(trim($product)==null){continue;}
	$val=0;
	$id=md5($product);
	$product=$tpl->_ENGINE_parse_body("{{$product}}");
	$status=$array2["status"];
	$uptime=$array2["uptime"];
	$children=$array2["children"];
	$mem=$array2["memory kilobytes total"];
	$cpu=$array2["cpu percent total"];
	$mem=FormatBytes($mem);
	$data['rows'][] = array(
					'id' => "$id",
					'cell' => array(
					"<span style='font-size:14px;'>$product</a></span>",
					"<span style='font-size:14px;'>$status</span>",
					"<span style='font-size:14px;'>$uptime</span>",
					"<span style='font-size:14px;'>$children</span>",
					"<span style='font-size:14px;'>$mem</span>",
					"<span style='font-size:14px;'>{$cpu}</span>",
	
			)
					);
	}
	
	
					echo json_encode($data);
	}

