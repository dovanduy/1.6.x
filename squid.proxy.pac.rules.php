<?php
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
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup-script"])){popup();exit;}
	if(isset($_GET["popup-tabs"])){popup_tabs();exit;}
	
	
	if(isset($_GET["popup-add-proxy"])){popup_add_proxy();exit;}
	if(isset($_GET["proxy_addr"])){popup_add_proxy_save();exit;}
	if(isset($_GET["proxylist"])){popup_add_proxy_list();exit;}
	if(isset($_GET["del_proxy_addr"])){popup_del_proxy_list();exit;}
	if(isset($_GET["view"])){popup_script();exit;}
	if(isset($_GET["popup-view-script"])){popup_script();exit;}
	
	if(isset($_GET["final_proxy_addr"])){popup_add_final_proxy_save();exit;}
	if(isset($_GET["popup-final-proxy"])){popup_add_final_proxy();exit;}
	if(isset($_GET["del_proxy_final_addr"])){popup_del_final_proxy_list();exit;}
	
	
	if(isset($_GET["localHostOrDomainIs"])){popup_localHostOrDomainIs();exit;}
	if(isset($_GET["localHostOrDomainIs-add"])){popup_localHostOrDomainIs_add();exit;}
	if(isset($_GET["localHostOrDomainIs-list"])){popup_localHostOrDomainIs_list();exit;}
	if(isset($_GET["localHostOrDomainIs-del"])){popup_localHostOrDomainIs_del();exit;}
	
	if(isset($_GET["isInNet"])){popup_isInNet();exit;}
	if(isset($_GET["isInNet-add"])){popup_isInNet_add();exit;}
	if(isset($_GET["isInNet-list"])){popup_isInNet_list();exit;}
	if(isset($_GET["isInNet-del"])){popup_isInNet_del();exit;}
	if(isset($_GET["isInNet-del"])){popup_isInNet_del();exit;}
	
	if(isset($_GET["add_condition_plus"])){popup_add_condition_add();exit;}
	if(isset($_GET["add_condition"])){popup_add_condition();exit;}
	if(isset($_GET["DeleteCondition"])){popup_del_condition();exit;}
	
	if(isset($_POST["ProxyPacRemoveProxyListAtTheEnd"])){ProxyPacRemoveProxyListAtTheEnd();exit;}
	
	
	
js();



function js(){
	
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{proxy_pac_rules}");
	$add_a_proxy=$tpl->_ENGINE_parse_body("{add_a_proxy}");
	$add_localHostOrDomainIs=$tpl->_ENGINE_parse_body("{add_localHostOrDomainIs}");
	$add_condition=$tpl->_ENGINE_parse_body("{add_condition}");
	$isInNetProxy=$tpl->_ENGINE_parse_body("{isInNetProxy}");
	$view_script=$tpl->_ENGINE_parse_body("{view_script}");
	$final_proxy=$tpl->_ENGINE_parse_body("{final_proxy}");
	$page=CurrentPageName();
	$html="
		function squid_proxy_pac_rules_load(){
			YahooWin3('750','$page?popup-tabs=yes','$title');
		
		}
		
		
		
		
		var x_squid_reverse_proxy_save= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			CacheOff();
			YahooWin3Hide();
			RefreshTablesPacs();
			RefreshTab('squid_main_config');
		}		
		
		function squid_reverse_proxy_save(){
		 	var XHR = new XHRConnection();
			XHR.appendData('SquidActHasReverse',document.getElementById('SquidActHasReverse').value);
			XHR.appendData('listen_port',document.getElementById('listen_port').value);
			document.getElementById('reversid').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';	
			XHR.sendAndLoad('$page', 'GET',x_squid_reverse_proxy_save);
		}
		
		function squid_proxy_pacc_add_proxy(){
			YahooWin4('500','$page?popup-add-proxy=yes','$add_a_proxy');
		}
		
		function localHostOrDomainIs(){
			YahooWin4('500','$page?localHostOrDomainIs=yes','$add_localHostOrDomainIs');
		}
		
		function isInNet(){
			YahooWin4('500','$page?isInNet=yes','$isInNetProxy');
		}
		
		function ViewProxyPac(){
			YahooWin5('700','$page?view=yes','$view_script');
		}

		function AddProxycondition(ID){
			YahooWin4('500','$page?add_condition='+ID,'$add_condition');
		}
		
		function FinalProxyPacList(){
			YahooWin4('500','$page?popup-final-proxy=yes','$final_proxy');
		}
		
		var x_squid_proxy_pacc_add_proxy_perform= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin4Hide();
			RefreshTablesPacs();
		}
		
		
	function DeleteCondition(num,key){
			var XHR = new XHRConnection();
			XHR.appendData('DeleteCondition',num);
			XHR.appendData('DeleteConditionKey',key);
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pacc_add_proxy_perform);
		}		

		
		
		
		
		function squid_proxy_pacc_add_proxy_perform(){
			var XHR = new XHRConnection();
			XHR.appendData('proxy_addr',document.getElementById('proxy_addr').value);
			XHR.appendData('listen_port',document.getElementById('proxy_port').value);
			AnimateDiv('popup_add_proxy_div');
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pacc_add_proxy_perform);
		}
		
		function squid_proxy_pacc_add_finalproxy_perform(){
			var XHR = new XHRConnection();
			XHR.appendData('final_proxy_addr',document.getElementById('proxy_addr').value);
			XHR.appendData('listen_port',document.getElementById('proxy_port').value);
			AnimateDiv('popup_add_proxy_div');	
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pacc_add_proxy_perform);		
		}
		
		function squid_proxy_pacc_del_proxy(num){
			var XHR = new XHRConnection();
			XHR.appendData('del_proxy_addr',num);
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pacc_add_proxy_perform);
		}
		
		function squid_proxy_pacc_del_final_proxy(num){
			var XHR = new XHRConnection();
			XHR.appendData('del_proxy_final_addr',num);
			XHR.sendAndLoad('$page', 'GET',x_squid_proxy_pacc_add_proxy_perform);
		}		
		
		
		function localHostOrDomainIsKeyPress(e){if(checkEnter(e)){localHostOrDomainIsAdd();}}
		function isInNetKeyPress(e){if(checkEnter(e)){isInNetAdd();}}
		
		
		
		var  x_localHostOrDomainIsAdd= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin4Hide();
			RefreshTablesPacs();
			
		}
		

	function localHostOrDomainIsAdd(){
			var XHR = new XHRConnection();
			var value=document.getElementById('localHostOrDomainIs').value;
			if(document.getElementById('dnsDomainIs').checked){value='dnsDomainIs:'+value;}
			XHR.appendData('localHostOrDomainIs-add',value);
			XHR.sendAndLoad('$page', 'GET',x_localHostOrDomainIsAdd);
		}

		function localHostOrDomainIsDel(num){
			var XHR = new XHRConnection();
			XHR.appendData('localHostOrDomainIs-del',num);
			XHR.sendAndLoad('$page', 'GET',x_localHostOrDomainIsAdd);		
		}
		
		
	var  x_isInNetAdd= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			YahooWin4Hide();
			RefreshTablesPacs();
		}		
		
	function isInNetAdd(){
			var XHR = new XHRConnection();
			XHR.appendData('isInNet-add',document.getElementById('tcp_addr').value);
			XHR.appendData('isInNet-mask',document.getElementById('mask').value);
			XHR.sendAndLoad('$page', 'GET',x_isInNetAdd);
		}
		
	function isInNetDel(num){
			var XHR = new XHRConnection();
			XHR.appendData('isInNet-del',num);
			XHR.sendAndLoad('$page', 'GET',x_isInNetAdd);		
	}
	squid_proxy_pac_rules_load();";
	
	echo $html;
	
}


function popup_tabs(){
	
	$page=CurrentPageName();
	$array["service"]='{service_parameters}';
	$array["script"]='{proxy_pac_rules}';
	$array["view-script"]='{view_script}';
	
	
	
	$tpl=new templates();

	while (list ($num, $ligne) = each ($array) ){
		if($num=="service"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.proxy.pac.php?popup=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?popup-$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_proxypac style='width:100%;height:730px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_proxypac').tabs();
				});
		</script>";		
	
	
	
	
}


function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	$ProxyPacRemoveProxyListAtTheEnd=$sock->GET_INFO("ProxyPacRemoveProxyListAtTheEnd");
	$listen_port=$sock->GET_INFO("SquidProxyPacPort");

	if($datas["DisableLocalNetwork"]==null){
		$datas["DisableLocalNetwork"]=1;
	}
	
	
	$proxys_list=$tpl->_ENGINE_parse_body("{proxys_list}");
	$add_a_proxy=$tpl->_ENGINE_parse_body("{add_a_proxy}");
	$isInNetProxy=$tpl->_ENGINE_parse_body("{isInNetProxy}");
	$final_proxy=$tpl->_ENGINE_parse_body("{final_proxy}");
	$add_localHostOrDomainIs=$tpl->_ENGINE_parse_body("{add_localHostOrDomainIs}");
	$hosts_without_proxy=$tpl->_ENGINE_parse_body("{hosts_without_proxy}");
	$whitelisted_networks=$tpl->_ENGINE_parse_body("{whitelisted_networks}");
	$view_script=$tpl->_ENGINE_parse_body("{view_script}");
	$TB_WIDTH=695;
	
	
	$isiniNet="	<table style='width:100%'>
	<tr>
	<td class=legend font-size:13px'>{do_not_use_proxy_for_local_net}:</td>
	<td>". Field_checkbox("DisableLocalNetwork",1,$datas["DisableLocalNetwork"])."</td>
	</tr>
	</table>
	<hr>";
	
	
	$html="
	<table class='ProxyPacTable' style='display: none' id='ProxyPacTable' style='width:99%'></table>
	<table class='LocalHostWhitePacTable' style='display: none' id='LocalHostWhitePacTable' style='width:99%'></table>
	<table class='isInNetListPacTable' style='display: none' id='isInNetListPacTable' style='width:99%'></table>
	<table style='width:100%'>
	<tr>
		<td valign='top' class=legend>{RemoveProxyListAtTheEnd}:</td>
		<td width=1%>". Field_checkbox("ProxyPacRemoveProxyListAtTheEnd",1,$ProxyPacRemoveProxyListAtTheEnd,"ProxyPacRemoveProxyListAtTheEndCheck()")."</td>
	</tr>
	</table>
	
	<script>
var ITEMIDMEM=0;
$(document).ready(function(){
$('#ProxyPacTable').flexigrid({
	url: '$page?proxylist=yes',
	dataType: 'json',
	colModel : [
		{display: '$proxys_list', name : 'item', width : 261, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'delete2', width : 305, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'delete3', width : 32, sortable : false, align: 'center'},
	],
	
buttons : [
		{name: '$add_a_proxy', bclass: 'add', onpress : squid_proxy_pacc_add_proxy},
		{name: '$add_localHostOrDomainIs', bclass: 'add', onpress : localHostOrDomainIs},
		{name: '$isInNetProxy', bclass: 'add', onpress : isInNet},
		{name: '$final_proxy', bclass: 'add', onpress : FinalProxyPacList},
		{separator: true},
		{name: '$view_script', bclass: 'Script', onpress : FinalProxyList},
		],		

	sortname: 'item',
	sortorder: 'asc',
	usepager: false,
	title: '',
	useRp: false,
	rp: 100,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 150,
	singleSelect: true
	
	});   
});	


$(document).ready(function(){
$('#LocalHostWhitePacTable').flexigrid({
	url: '$page?localHostOrDomainIs-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$hosts_without_proxy', name : 'item', width : 584, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'delete3', width : 32, sortable : false, align: 'center'},
	],
	sortname: 'item',
	sortorder: 'asc',
	usepager: false,
	title: '',
	useRp: false,
	rp: 100,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 150,
	singleSelect: true
	
	});   
});
$(document).ready(function(){
$('#isInNetListPacTable').flexigrid({
	url: '$page?isInNet-list=yes',
	dataType: 'json',
	colModel : [
		{display: '$whitelisted_networks', name : 'item', width : 584, sortable : true, align: 'left'},	
		{display: '&nbsp;', name : 'delete3', width : 32, sortable : false, align: 'center'},
	],
	sortname: 'item',
	sortorder: 'asc',
	usepager: false,
	title: '',
	useRp: false,
	rp: 100,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 150,
	singleSelect: true
	
	});   
});
	function RefreshTablesPacs(){
		$('#ProxyPacTable').flexReload();
		$('#LocalHostWhitePacTable').flexReload();
		$('#isInNetListPacTable').flexReload();
		
		
	}
	
	function FinalProxyList(){
		YahooWin2('600','$page?popup-view-script=yes','$view_script');
	}
	
		
		function ProxyPacRemoveProxyListAtTheEndCheck(){
			var XHR = new XHRConnection();
			if(document.getElementById('ProxyPacRemoveProxyListAtTheEnd').checked){
				XHR.appendData('ProxyPacRemoveProxyListAtTheEnd',1);
			}else{
				XHR.appendData('ProxyPacRemoveProxyListAtTheEnd',0);
			}
			XHR.sendAndLoad('$page', 'POST');	
			
		}
</script>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function ProxyPacRemoveProxyListAtTheEnd(){
	$sock=new sockets();
	$sock->SET_INFO("ProxyPacRemoveProxyListAtTheEnd",$_POST["ProxyPacRemoveProxyListAtTheEnd"]);
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_add_proxy_save(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));	
	$datas["PROXYS"][]="{$_GET["proxy_addr"]}:{$_GET["listen_port"]}";
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
	
}

function popup_add_final_proxy_save(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	$datas["FINAL_PROXY"][]="{$_GET["final_proxy_addr"]}:{$_GET["listen_port"]}";	
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_del_proxy_list(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	unset($datas["PROXYS"][$_GET["del_proxy_addr"]]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_del_final_proxy_list(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	unset($datas["FINAL_PROXY"][$_GET["del_proxy_final_addr"]]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_localHostOrDomainIs_add(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));	
	$datas["localHostOrDomainIs"][]=$_GET["localHostOrDomainIs-add"];
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");	
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}
function popup_localHostOrDomainIs_del(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	unset($datas["localHostOrDomainIs"][$_GET["localHostOrDomainIs-del"]]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");	
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_isInNet_add(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	$datas["isInNet"][]=array($_GET["isInNet-add"],$_GET["isInNet-mask"]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}
function popup_isInNet_del(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	unset($datas["isInNet"][$_GET["isInNet-del"]]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");	
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_localHostOrDomainIs_list(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));		
	if(!is_array($datas["localHostOrDomainIs"])){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return;}
	$tpl=new templates();

	
	$final_proxy=$tpl->_ENGINE_parse_body("{final_proxy}");
	$deleteTxt=$tpl->_ENGINE_parse_body("{delete}");
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($datas["localHostOrDomainIs"]);
	$data['rows'] = array();	

	
	
	while (list ($num, $uri) = each ($datas["localHostOrDomainIs"])){
		$id=md5($num);
		$conditions=null;
		
		
		if(preg_match("#dnsDomainIs:(.+?)$#",trim($uri),$re)){
			$uri=$re[1]." <i>{IE8_compatibility}</i>";
		}
	$data['rows'][] = array(
		'id' => "Proxy$num",
		'cell' => array(
		"<span style='font-size:16px;font-weight:bold;color:black;'>$uri</a></span>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:localHostOrDomainIsDel('$num')\"><img src='img/delete-24.png'></a>" )
		);
	}	
	


	echo json_encode($data);
}


function popup_add_proxy_list(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));		
	if(!is_array($datas["PROXYS"])){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	$tpl=new templates();
	$final_proxy=$tpl->_ENGINE_parse_body("{final_proxy}");
	$deleteTxt=$tpl->_ENGINE_parse_body("{delete}");
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($datas["PROXYS"])+count($datas["FINAL_PROXY"]);
	$data['rows'] = array();	

	
	
	while (list ($num, $uri) = each ($datas["PROXYS"])){
		$id=md5($num);
		$conditions=null;
		$delete=imgtootltip("delete-24.png","$deleteTxt $uri","squid_proxy_pacc_del_proxy('$num')");
		$JSGROUP="AddProxycondition($num)";
		$conditions=popup_add_proxy_list_conditions($num,$datas["CONDITIONS"][$num]);
		
		
	$data['rows'][] = array(
		'id' => "Proxy$num",
		'cell' => array(
		"<a href=\"javascript:blur();\" OnClick=\"$JSGROUP\"  style='font-size:16px;font-weight:bold;color:black;text-decoration:underline'>$uri</a></span>",
		$conditions,
		$delete )
		);
	}	
	
	
	if(count($datas["FINAL_PROXY"])>0){

		while (list ($num, $uri) = each ($datas["FINAL_PROXY"])){
			$delete=imgtootltip("delete-24.png","$deleteTxt $uri","squid_proxy_pacc_del_final_proxy('$num')");
			
			$data['rows'][] = array(
				'id' => "Proxy$num",
				'cell' => array(
				"<span style='font-size:16px;font-weight:bold;color:black;'>$final_proxy:$uri</a></span>",
				"&nbsp;",
				$delete )
				);			

		}	
	}

	echo json_encode($data);
}

function popup_add_proxy_list_conditions($num,$array){
	$tpl=new templates();
	if(!is_array($array)){return null;}	
	$html="<table style='width:80%'>";
	while (list ($index, $condition) = each ($array)){

		if($condition["dnsDomainIs"]<>null){
			$html=$html."<tr ". CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'>
			<td nowrap>{dnsDomainIs}:&laquo;{$condition["dnsDomainIs"]}&raquo;</td>
			<td width=1%>". imgtootltip("ed_delete.gif","{delete}","DeleteCondition($num,$index)")."</td>
			</tr>";
		}

		if($condition["isPlainhost"]<>null){
			$html=$html."<tr ". CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'>
			<td nowrap>{isPlainhost}:&laquo;{$condition["isPlainhost"]}&raquo;</td>
			<td width=1%>". imgtootltip("ed_delete.gif","{delete}","DeleteCondition($num,$index)")."</td>
			</tr>";
		}	

		if($condition["FailOverProxy"]<>null){
			$html=$html."<tr ". CellRollOver().">
			<td width=1%><img src='img/fw_bold.gif'>
			<td nowrap>{other_proxy}:&laquo;{$condition["FailOverProxy"]}&raquo;</td>
			<td width=1%>". imgtootltip("ed_delete.gif","{delete}","DeleteCondition($num,$index)")."</td>
			</tr>";
		}			
		
		
		
		
		
	}
	
	$html=$html."</table>";
	return  $tpl->_ENGINE_parse_body($html);
}



function popup_isInNet_list(){
	$sock=new sockets();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));		
	if(!is_array($datas["isInNet"])){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return;}
	
	$final_proxy=$tpl->_ENGINE_parse_body("{final_proxy}");
	$deleteTxt=$tpl->_ENGINE_parse_body("{delete}");
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($datas["isInNet"]);
	$data['rows'] = array();	

	
	
	while (list ($num, $uri) = each ($datas["isInNet"])){
		$id=md5($num);
		$conditions=null;
		$delete=imgtootltip("delete-24.png","$deleteTxt $uri","isInNetDel('$num')");

	$data['rows'][] = array(
		'id' => "Proxy$num",
		'cell' => array(
		"<span style='font-size:16px;font-weight:bold;color:black;'>{$uri[0]}&nbsp;-&nbsp;{$uri[1]}</a></span>",
		$delete )
		);
	}	
	


	echo json_encode($data);	
	
}

function popup_localHostOrDomainIs(){
	$html="
	<div id='popup_localHostOrDomainIs_div'>
	<div style='font-size:14px' class=explain>{localHostOrDomainIs_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}:</td>
		<td>". Field_text("localHostOrDomainIs",null,"font-size:16px;padding:3px",null,null,null,false,"localHostOrDomainIsKeyPress(event)")."</td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:16px'>{IE8_compatibility}</td>
		<td>". Field_checkbox("dnsDomainIs",1,0,null)."</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>
		<hr>". button("{add}","localHostOrDomainIsAdd()",18)."</td>
	</tr>
	</table>	
	</div>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function popup_isInNet(){
	$html="
	<div id='isInNet_div'>
	<div style='font-size:14px' class=explain>{isInNet_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{ip_address}:</td>
		<td>". Field_text("tcp_addr",null,"font-size:16px;padding:3px",null,null,null,false,"isInNetKeyPress(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{mask}:</td>
		<td>". Field_text("mask",null,"font-size:16px;padding:3px",null,null,null,false,"isInNetKeyPress(event)")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'>
		<hr>". button("{add}","isInNetAdd()",18)."</td>
	</tr>
	</table>	
	</div>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
		
	
}




function popup_add_proxy(){
	
	$html="
	<div id='popup_add_proxy_div'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{proxy_addr}:</td>
		<td>". Field_text("proxy_addr",null,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("proxy_port",null,"width:90px;font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{add}","squid_proxy_pacc_add_proxy_perform()",18)."</td>
	</tr>
	</table>	
	</div>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
}


function popup_add_final_proxy(){
	$html="
	<div id='popup_add_proxy_div'>
	<div class=explain>{proxy_pac_final_proxy_text}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{proxy_addr}:</td>
		<td>". Field_text("proxy_addr",null,"font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td>". Field_text("proxy_port",null,"width:90px;font-size:16px;padding:3px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{add}","squid_proxy_pacc_add_finalproxy_perform()",18)."</td>
	</tr>
	</table>	
	</div>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}

function popup_add_condition(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	$page=CurrentPageName();
	$proxyname=$datas["PROXYS"][$_GET["add_condition"]];
	
	$html="
	<div id='addconditiondiv'>
	<div style='font-size:16px'>{add_condition}:$proxyname</div>
	<table style='width:99%' class=form>
	<tr>
	<td style='font-size:14px' class=legend>{dnsDomainIs}:</td>
	<td>". Field_text("dnsDomainIs",null,'font-size:14px;padding:3px:width:120px')."</td>
	</tr><td colspan=2 class=legend><i style='font-size:12px'>{dnsDomainIs_text}</i></td></tr>
	<tr>
	<td style='font-size:14px' class=legend>{isPlainhost}:</td>
	<td>". Field_checkbox("isPlainhost",1,0)."</td>
	</tr>
	<td colspan=2 class=legend><i style='font-size:12px'>{isPlainhost_text}</i></td>
	</tr>
	<tr><td colspan=2 align='right'><hr>". button("{add}","ProxyAddCondition()",16)."</td></tr>
	</table>
	
	
	
	<table style='width:99%' class=form>
	<tr>
	<td style='font-size:16px'>{other_proxy}:</td>
	<td>
		<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:14px'>{proxy_addr}:</td>
				<td>". Field_text("other_proxy_addr",null,"font-size:14px;padding:3px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:14px'>{listen_port}:</td>
				<td>". Field_text("other_proxy_port",null,"width:90px;font-size:14px;padding:3px")."</td>
			</tr>
		</table>
	</td>
	</tr>
	<td colspan=2 class=legend><i style='font-size:12px'>{proxy_pac_other_proxy_text}</i></td>	

	<tr><td colspan=2 align='right'><hr>". button("{add}","ProxyAddCondition()",16)."</td></tr>
	
	</table>
	</div>
	<script>
	
	var  x_ProxyAddCondition= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			ProxysPacList();
			YahooWin4Hide();
		}
	
	
		function ProxyAddCondition(){
			var XHR = new XHRConnection();
			XHR.appendData('add_condition_plus',{$_GET["add_condition"]});
			XHR.appendData('dnsDomainIs',document.getElementById('dnsDomainIs').value);
			
			if(document.getElementById('isPlainhost').checked){
				XHR.appendData('isPlainhost','{yes}');
			}
			
			XHR.appendData('other_proxy_addr',document.getElementById('other_proxy_addr').value);
			XHR.appendData('other_proxy_port',document.getElementById('other_proxy_port').value);
			
			document.getElementById('addconditiondiv').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';	
			XHR.sendAndLoad('$page', 'GET',x_ProxyAddCondition);			
		}
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function popup_add_condition_add(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));
	if(trim($_GET["dnsDomainIs"])<>null){
		$datas["CONDITIONS"][$_GET["add_condition_plus"]][]["dnsDomainIs"]=$_GET["dnsDomainIs"];
	}

	if(trim($_GET["isPlainhost"])<>null){
		$datas["CONDITIONS"][$_GET["add_condition_plus"]][]["isPlainhost"]=$_GET["isPlainhost"];
	}		
	
	if(trim($_GET["other_proxy_addr"])<>null){
		$datas["CONDITIONS"][$_GET["add_condition_plus"]][]["FailOverProxy"]="{$_GET["other_proxy_addr"]}:{$_GET["other_proxy_port"]}";
	}	
	
	
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_del_condition(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("ProxyPacDatas")));	
	unset($datas["CONDITIONS"][$_GET["DeleteCondition"]][$_GET["DeleteConditionKey"]]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)),"ProxyPacDatas");
	$sock->getFrameWork("cmd.php?proxy-pac-build=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-wpad=yes");
}

function popup_script(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("cmd.php?proxy-pac-show=yes"));	
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:14px'>$datas</textarea>";
	echo $html;
	
	
}


?>