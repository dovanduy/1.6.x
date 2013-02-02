<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
include_once(dirname(__FILE__) . "/ressources/class.tcpip.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if($user->AsDnsAdministrator==false){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_POST["RemoteAddAddr"])){ipv4_add();exit;}
if(isset($_POST["RemoteDelAddr"])){ipv4_del();exit;}
if(isset($_POST["RemoteAddAddrv6"])){ipv6_add();exit;}
if(isset($_POST["RemoteDelAddrv6"])){ipv6_del();exit;}


if(isset($_GET["ipv4-list"])){ipv4_list();exit;}
if(isset($_GET["ipv6-list"])){ipv6_list();exit;}
if(isset($_GET["ipv4"])){ipv4_table();exit;}
if(isset($_GET["ipv6"])){ipv6_table();exit;}


tabs();



function tabs(){
	
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$array["ipv4"]='ipV4';
	$EnableipV6=$sock->GET_INFO("EnableipV6");
	if(!is_numeric($EnableipV6)){$EnableipV6=0;}
	
	if($EnableipV6==1){
		$array["ipv6"]='ipV6';
	}
	
	$fontsize="style='font-size:16px'";
	
	while (list ($num, $ligne) = each ($array) ){
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span $fontsize>$ligne</span></a></li>\n");
	}
	
	
	echo $tpl->_ENGINE_parse_body("
	<div style='font-size:14px' class=explain>{pdns_listen_ip_explain}</div>")."
	<div id=main_config_pdns_lip>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_pdns_lip\").tabs();});
		</script>";		
		
	
}
function ipv6_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$groups=$tpl->_ENGINE_parse_body("{groups}:{ComputerMacAddress}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$new_interface=$tpl->_ENGINE_parse_body("{new_nic}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$mimetype=$tpl->_ENGINE_parse_body("{ExcludeMimeType}");
	$listen_ip=$tpl->javascript_parse_text("{listen_ip}");
	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var md$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?ipv6-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'center'},
		{display: '$listen_ip', name : 'data', width :669, sortable : true, align: 'left'},
		{display: '$delete', name : 'country', width : 70, sortable : false, align: 'center'},
		

		],
		
buttons : [
		{name: '$new_interface', bclass: 'add', onpress : PdnsRemoteAddAddr$t},
		],			
	
	searchitems : [
		{display: '$listen_ip', name : 'data'},
		],
	sortname: 'data',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 830,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_pdnsRemoteAddAddr$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
    	$('#flexRT$t').flexReload();	
	}

	function PdnsRemoteAddAddr$t(){
			var ip=prompt('$listen_ip');
			if(ip){
				var XHR = new XHRConnection();
				XHR.appendData('RemoteAddAddrv6',ip);
				XHR.sendAndLoad('$page', 'POST',x_pdnsRemoteAddAddr$t);
			}
		}
		
	var x_PdnsRemoteDelAddr$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;};
	    $('#row'+md$t).remove();	
	}		
		
	function PdnsRemoteDelAddr$t(ip,id){
		md$t=id;
		var XHR = new XHRConnection();
		XHR.appendData('RemoteDelAddrv6',ip);
		XHR.sendAndLoad('$page', 'POST',x_PdnsRemoteDelAddr$t);
	
	}
</script>
	
	
	";
	
	echo $html;
}

function ipv4_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$groups=$tpl->_ENGINE_parse_body("{groups}:{ComputerMacAddress}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$time=$tpl->_ENGINE_parse_body("{time}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$new_interface=$tpl->_ENGINE_parse_body("{new_nic}");
	$title=$tpl->_ENGINE_parse_body("{today}: {requests} {since} ".date("H")."h");
	$mimetype=$tpl->_ENGINE_parse_body("{ExcludeMimeType}");
	$listen_ip=$tpl->javascript_parse_text("{listen_ip}");
	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var md$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?ipv4-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'none', width :31, sortable : true, align: 'center'},
		{display: '$listen_ip (ipV4)', name : 'data', width :669, sortable : true, align: 'left'},
		{display: '$delete', name : 'country', width : 70, sortable : false, align: 'center'},
		

		],
		
buttons : [
		{name: '$new_interface', bclass: 'add', onpress : PdnsRemoteAddAddr$t},
		],			
	
	searchitems : [
		{display: '$listen_ip', name : 'data'},
		],
	sortname: 'data',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 830,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_pdnsRemoteAddAddr$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
    	$('#flexRT$t').flexReload();	
	}

	function PdnsRemoteAddAddr$t(){
			var ip=prompt('$listen_ip');
			if(ip){
				var XHR = new XHRConnection();
				XHR.appendData('RemoteAddAddr',ip);
				XHR.sendAndLoad('$page', 'POST',x_pdnsRemoteAddAddr$t);
			}
		}
		
	var x_PdnsRemoteDelAddr$t=function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;};
	    $('#row'+md$t).remove();	
	}		
		
	function PdnsRemoteDelAddr$t(ip,id){
		md$t=id;
		var XHR = new XHRConnection();
		XHR.appendData('RemoteDelAddr',ip);
		XHR.sendAndLoad('$page', 'POST',x_PdnsRemoteDelAddr$t);
	
	}
</script>
	
	
	";
	
	echo $html;
}	

function ipv4_list(){
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$database="artica_backup";		
	$t=$_GET["t"];
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddr"));
	
	if(count($datas)==0){json_error_show("All....");}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$QUERY=string_to_regex($_POST["query"]);
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	
	

	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	$data['total'] = count($datas);
	$style="style='font-size:16px'";
	
	$c=0;
	while (list ($index, $ipmask) = each ($datas) ){
		if(trim($ipmask)==null){continue;}
		$id=md5($ipmask);
		$enabled=0;
 		$delete=imgsimple("delete-32.png","{delete}","PdnsRemoteDelAddr$t('$ipmask','$id')");
 		if($QUERY<>null){if(!preg_match("#$QUERY#", $ipmask)){continue;}}
		$c++;
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style><img src='img/folder-network-32.png'></span>",
			"<span $style>$ipmask</span>",
			"<span $style>$delete</span>",

			)
			);		
		
		
	}
$data['total'] = $c;
echo json_encode($data);	
		
}

function ipv6_list(){
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$database="artica_backup";		
	$t=$_GET["t"];
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddrV6"));
	
	if(count($datas)==0){json_error_show("All....");}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$QUERY=string_to_regex($_POST["query"]);
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	
	

	$data = array();
	$data['page'] = 0;
	$data['total'] = $total;
	$data['rows'] = array();	
	$data['total'] = count($datas);
	$style="style='font-size:16px'";
	
	
	while (list ($index, $ipmask) = each ($datas) ){
		if(trim($ipmask)==null){continue;}
		$id=md5($ipmask);
		$enabled=0;
 		$delete=imgsimple("delete-32.png","{delete}","PdnsRemoteDelAddr$t('$ipmask','$id')");
		if($QUERY<>null){if(!preg_match("#$QUERY#", $ipmask)){continue;}}
		$c++;
		$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
			"<span $style><img src='img/folder-network-32.png'></span>",
			"<span $style>$ipmask</span>",
			"<span $style>$delete</span>",

			)
			);		
		
		
	}
$data['total'] = $c;
echo json_encode($data);	
}

function ipv4_add(){
	$ip=new IP();
	if(!$ip->isIPv4($_POST["RemoteAddAddr"])){echo "No an IPv4 address...\n";return;}
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddr"));
	while (list ($index, $ipmask) = each ($datas) ){$array[$ipmask]=$ipmask;}
	$array[$_POST["RemoteAddAddr"]]=$_POST["RemoteAddAddr"];
	while (list ($index, $ipmask) = each ($array) ){$f[]=$ipmask;}
	$sock->SaveConfigFile(@implode("\n",$f), "PowerDNSListenAddr");
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
}

function ipv6_add(){
	$ip=new IP();
	if(!$ip->isIPv6($_POST["RemoteAddAddrv6"])){echo "No an IPv6 address...\n";return;}
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddrV6"));
	while (list ($index, $ipmask) = each ($datas) ){$array[$ipmask]=$ipmask;}
	$array[$_POST["RemoteAddAddrv6"]]=$_POST["RemoteAddAddrv6"];
	while (list ($index, $ipmask) = each ($array) ){$f[]=$ipmask;}
	$sock->SaveConfigFile(@implode("\n",$f), "PowerDNSListenAddrV6");
	$sock->getFrameWork("cmd.php?pdns-restart=yes");	
	
}
function ipv6_del(){
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddrV6"));
	while (list ($index, $ipmask) = each ($datas) ){$array[$ipmask]=$ipmask;}
	unset($array[$_POST["RemoteDelAddrv6"]]);
	while (list ($index, $ipmask) = each ($array) ){$f[]=$ipmask;}
	$sock->SaveConfigFile(@implode("\n",$f), "PowerDNSListenAddrV6");	
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
}

function ipv4_del(){
	$sock=new sockets();
	$datas=explode("\n",$sock->GET_INFO("PowerDNSListenAddr"));
	while (list ($index, $ipmask) = each ($datas) ){$array[$ipmask]=$ipmask;}
	unset($array[$_POST["RemoteDelAddr"]]);
	while (list ($index, $ipmask) = each ($array) ){$f[]=$ipmask;}
	$sock->SaveConfigFile(@implode("\n",$f), "PowerDNSListenAddr");	
	$sock->getFrameWork("cmd.php?pdns-restart=yes");
}
