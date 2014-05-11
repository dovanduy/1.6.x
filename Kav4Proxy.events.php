<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.artica.graphs.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["service"])){service();exit;}
	if(isset($_GET["service-search"])){service_search();exit;}
	
	if(isset($_GET["requests"])){requests();exit;}
	if(isset($_GET["requests-search"])){requests_search();exit;}	
	
tabs();

function tabs(){
	
	$sock=new sockets();
	$tpl=new templates();
	$INSTALLED=trim($sock->getFrameWork("squid.php?kaspersky-is-installed=yes"));
	if($INSTALLED<>"TRUE"){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{not_installed}"));
		return;
	}
	
		$font_size=$_GET["font-size"];
		if($font_size==null){$font_size="100%";}
		$tpl=new templates();
		$page=CurrentPageName();
		$users=new usersMenus();
		$array["service"]="{service_events}";
		$array["update"]="{update_events}";
		$font_size="16px";
		https://192.168.1.205:9000/
		
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="update"){
			$tab[]="<li><a href=\"Kav4Proxy.Update.Events.php?popup=yes\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
			continue;
		}
		
		
			$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:$font_size'>$ligne</span></a></li>\n";
		}

	echo build_artica_tabs($tab, "main_kav4proxyevents");
	
	
}
function requests(){
	$page=CurrentPageName();
	$t=time();
	$html="
	<table style='width:100%'>
	<tr>
		<td class=legend valign='middle'>{search}:</td>
		<td>". Field_text("$t-search",null,"font-size:13px;padding:3px;",null,null,null,false,"SearchPress$t(event)")."</td>
		<td align='right' width=1%>". imgtootltip("32-refresh.png","{refresh}","Kav4ProxyRequestsRefresh()")."</td>
	</tr>
	</table>
	
	<div style='widht:99%;height:550px;overflow:auto;margin:5px' id='$t-table'></div>
	<script>
		function SearchPress$t(e){
			if(checkEnter(e)){Search$t();}
		}
	
	
		function Search$t(){
			var pat=escape(document.getElementById('$t-search').value);
			LoadAjax('$t-table','$page?request-search='+pat);
		
		}
		
		function Kav4ProxyRequestsRefresh(){
			Search$t();
		}
	
	Search$t();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function service(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$type=$tpl->_ENGINE_parse_body("{xtype}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$html="
	
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?service-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'date', width : 116, sortable : true, align: 'left'},
		{display: '$type', name : 'GroupType', width : 31, sortable : false, align: 'center'},
		{display: '$events', name : 'items', width : 633, sortable : false, align: 'left'},
		
	],
	searchitems : [
		{display: '$events', name : 'events'},
		],
	sortname: 'GroupName',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 100,
	rpOptions: [10, 20, 30, 50,100,200,500],
	showTableToggleBtn: false,
	width: 835,
	height: 500,
	singleSelect: true
	
	});   
});	
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function service_search(){
	
	$sock=new sockets();
	
	if($_POST["query"]<>null){$search=base64_encode($_POST["query"]);}	
	
	
	
	$sock->getFrameWork("cmd.php?syslog-query=$search&syslog-path=/var/log/kaspersky/kav4proxy/kavicapserver.log&rp={$_POST["rp"]}");
	$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
	if(!is_array($array)){json_error_show("No data");}
	
	if($_POST["sortname"]<>null){
		if($_POST["sortorder"]=="desc"){krsort($array);}else{ksort($array);}
	}	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	krsort($array);
	$c=0;
	while (list ($key, $line) = each ($array) ){
		if($line==null){continue;}
		$color="black";
		$date=null;
		$letter=null;
		
		if(preg_match("#\[(.+?)\s+([A-Z]+)\]\s+(.+)#", $line,$re)){
				$date=$re[1];
				$letter=$re[2];
				$line=$re[3];
		}
		
		if($letter=="E"){$color="#DA1111";}
		if($letter=="F"){$color="#DA1111";}
		
		$c++;
$data['rows'][] = array(
		'id' => "group$c",
		'cell' => array("<span style='font-size:12px;color:$color'>$date</span>",
		"<span style='font-size:12px;;color:$color'>$letter</span>",
		"<span style='font-size:12px;;color:$color'>$line</span>")
		);		
		
			
		
	}
	
	
	$data['total'] = $c;

	echo json_encode($data);	
}