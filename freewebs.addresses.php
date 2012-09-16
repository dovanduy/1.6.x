<?php
	session_start();
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	include_once('ressources/class.pdns.inc');
	
	

	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
		
	if(isset($_POST["enable_ldap_authentication"])){SaveConfig();exit;}
	
	if(isset($_GET["listen-popup"])){listen_popup();exit;}
	if(isset($_POST["Listen-save"])){listen_save();exit;}
	if(isset($_POST["Listen-del"])){listen_del();exit;}
	
	if(isset($_GET["defaults"])){defaults_form();exit;}
	if(isset($_POST["FreeWebListen-default"])){defaults_save();exit;}
	
	if(isset($_GET["query"])){Query();exit;}
	if(isset($_POST["authip-add"])){authip_add();exit;}
	if(isset($_POST["authip-del"])){authip_del();exit;}
	if(isset($_POST["authip-list"])){authip_list();exit;}
	if(isset($_POST["LimitByIp"])){authip_enable();exit;}	
	params();
	
	
	
	
function defaults_form(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$users=new usersMenus();	
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebLeftMenu=$sock->GET_INFO("FreeWebLeftMenu");
	$FreeWebDisableSSL=$sock->GET_INFO("FreeWebDisableSSL");
	if($FreeWebListen==null){$FreeWebListen="*";}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	if($FreeWebListenPort==null){$FreeWebListenPort=80;}
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}	
	$tcp=new networking();
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	$ips["*"]="{all}";	
	$t=time();
	$html="
	<div id='$t-defaults'></div>
	<table style='width:99%' class=form>
			<tr>
				<td class=legend style='font-size:16px' nowrap>{listen_ip}:</td>
				<td width=99%>". Field_array_Hash($ips,"FreeWebListen-$t",$FreeWebListen,"style:font-size:16px;padding:3px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px' nowrap>{listen_port}:</td>
				<td>". Field_text("FreeWebListenPort-$t",$FreeWebListenPort,"font-size:16px;padding:3px;width:60px")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:16px' nowrap>{listen_port} SSL:</td>
				<td>". Field_text("FreeWebListenSSLPort-$t",$FreeWebListenSSLPort,"font-size:16px;padding:3px;width:60px")."</td>
			</tr>
			<tr>			
				<td class=legend style='font-size:16px' nowrap>{disable_SSL_port}:</td>
				<td width=1%>". Field_checkbox("FreeWebDisableSSL-$t",1,$FreeWebDisableSSL,"FreeWebDisableSSLCheck$t()")."</td>
			</tr>
			<tr>			
				<td colspan=2 align='right'>". button("{apply}","SaveDefaultPorts()",18)."</td>
			</tr>						
	</table>
	
<script>
	var x_EnableFreeWebSave$t=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}
			RefreshTableFreeWebsListen();
			YahooWin2Hide();
		}	
		
		function SaveDefaultPorts(){
			var XHR = new XHRConnection();
    		XHR.appendData('FreeWebListen-default',document.getElementById('FreeWebListen-$t').value);
    		XHR.appendData('FreeWebListenPort-default',document.getElementById('FreeWebListenPort-$t').value);
    		XHR.appendData('FreeWebListenSSLPort-default',document.getElementById('FreeWebListenSSLPort-$t').value);
    		if(document.getElementById('FreeWebDisableSSL-$t').checked){XHR.appendData('FreeWebDisableSSL',1);}else{XHR.appendData('FreeWebDisableSSL',0);}
 			AnimateDiv('$t-defaults');
    		XHR.sendAndLoad('$page', 'POST',x_EnableFreeWebSave$t);
			
		}	
		
		function FreeWebDisableSSLCheck$t(){
			if(document.getElementById('FreeWebDisableSSL-$t').checked){
				document.getElementById('FreeWebListenSSLPort-$t').disabled=true;
				return;
			}
			document.getElementById('FreeWebListenSSLPort-$t').disabled=false;
		}
		
FreeWebDisableSSLCheck$t();		
		
		
</script>	
	
	
	";

	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function defaults_save(){
	$sock=new sockets();
	$sock->SET_INFO("FreeWebListen",$_POST["FreeWebListen-default"]);
	$sock->SET_INFO("FreeWebListenPort",$_GET["FreeWebListenPort"]);
	$sock->SET_INFO("FreeWebListenSSLPort",$_GET["FreeWebListenPort-default"]);
	$sock->SET_INFO("FreeWebDisableSSL", $_GET["FreeWebDisableSSL"]);
	$sock->getFrameWork("cmd.php?freeweb-restart=yes");
	$sock->getFrameWork("cmd.php?pure-ftpd-restart=yes");
	}
	


function listen_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tcp=new networking();
	$sock=new sockets();
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	$FreeWebDisableSSL=$sock->GET_INFO("FreeWebDisableSSL");
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	$t=time();
	$html="
	<div id='$t'>
	<table class=form style='width:99%'>
		<tr>
			<td class=legend style='font-size:16px'>{listen_ip}:</td>
			<td>". Field_array_Hash($ips,"FreeWebListen-$t",null,"style:font-size:16px;padding:3px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{listen_port}:</td>
			<td>". Field_text("FreeWebListenPort-$t",null,"font-size:16px;padding:3px;width:65px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{UseSSL}:</td>
			<td>". Field_checkbox("UseSSL-$t",1,0)."</td>
		</tr>	
		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","SaveNewFreeWebListenAddr()",18)."</td>
		</tr>						
		</table>
	</div>
		
<script>
	var x_SaveNewFreeWebListenAddr=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);}
			YahooWin2Hide();
			RefreshTableFreeWebsListen();
		}	
		
		function SaveNewFreeWebListenAddr(){
			var XHR = new XHRConnection();
			XHR.appendData('Listen-save','ues');
    		XHR.appendData('FreeWebListen',document.getElementById('FreeWebListen-$t').value);
    		XHR.appendData('FreeWebListenPort',document.getElementById('FreeWebListenPort-$t').value);
    		if(document.getElementById('UseSSL-$t').checked){XHR.appendData('UseSSL',1);}else{XHR.appendData('UseSSL',0);}
 			AnimateDiv('$t');
    		XHR.sendAndLoad('$page', 'POST',x_SaveNewFreeWebListenAddr);
			
		}	
		
		function CheckSSL$t(){
			var FreeWebDisableSSL=$FreeWebDisableSSL;
			if(FreeWebDisableSSL==1){document.getElementById('UseSSL-$t').disabled=true;}
		
		}
CheckSSL$t();
</script>
		
		";
	
echo $tpl->_ENGINE_parse_body($html);

}


function params(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$t=time();


	$add=$tpl->_ENGINE_parse_body("{add}");
	$address=$tpl->_ENGINE_parse_body("{address}");	
	$UseSSL=$tpl->_ENGINE_parse_body("{UseSSL}");	
	$default=$tpl->_ENGINE_parse_body("{default}");
	$tablewidht="850";
	$row1="685";
	if($_GET["force-groupware"]=="ZARAFA-WEBS"){
		$tablewidht="690";
		$row1="515";
	}
		
		
		
$buttons="buttons : [
	{name: '$add', bclass: 'Add', onpress : ApacheListenAdd},
		],	";


	
echo "
<div id='freewebs-defaults' style='margin-bottom:10px'></div>
<table class='$t' style='display: none' id='$t' style='width:99%;text-align:left'></table>
<script>
var MEMMDAUTHL='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?query=yes',
	dataType: 'json',
	colModel : [
			{display: '&nbsp;', name : 'zDate', width : 40, sortable : false, align: 'left'},	
			{display: '$address', name : 'address', width :$row1, sortable : false, align: 'left'},
			{display: 'SSL', name : 'ssl', width :35, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none2', width : 40, sortable : false, align: 'left'},
		
	],
$buttons
	searchitems : [
		{display: '$address', name : 'address'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $tablewidht,
	height: 300,
	singleSelect: true
	
	});   
});
	

	
		function RefreshTableFreeWebsListen(){
			$('#$t').flexReload();
		}
		
		function enable_ip_authentication_save$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('LimitByIp').checked){XHR.appendData('LimitByIp',1);}else{XHR.appendData('LimitByIp',0);}
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_AuthIpAdd$t);
		}
		
		var x_AuthIpAdd$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshAuthIp();			
		}

		var x_ApacheListenDel$t=function (obj) {
			var results=obj.responseText;
			if(results.length>2){alert(results);return;}
			$('#row'+MEMMDAUTHL).remove();		
		}		
		
		function ApacheListenAdd(){
			YahooWin2('550','$page?listen-popup=yes','$add');
		}
		
		function FreeWebDefaultPort(){
			YahooWin2('400','$page?defaults=yes','$default');
		}
		
		function ApacheListenDel(ip,id){
				MEMMDAUTHL=id;
				var XHR = new XHRConnection();
				XHR.appendData('Listen-del',ip);
				XHR.sendAndLoad('$page', 'POST',x_ApacheListenDel$t);
			
		}

		
	

	</script>
	
	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	}
	
	
	function authip_add(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->LimitByIp_add($_POST["authip-add"]);
	
}

function authip_del(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->LimitByIp_del($_POST["authip-del"]);	
}

function authip_enable(){
	$freeweb=new freeweb($_POST["servername"]);
	$freeweb->Params["LimitByIp"]["enabled"]=$_POST["LimitByIp"];
	$freeweb->SaveParams();
	
}

function listen_save(){
	$sock=new sockets();
	$hash=unserialize(base64_decode($sock->GET_INFO("FreeWebsApacheListenTable")));
	if(!is_numeric($_POST["FreeWebListenPort"])){return;}
	$pattern="{$_POST["FreeWebListen"]}:{$_POST["FreeWebListenPort"]}";
	$hash[$pattern]["SSL"]=$_POST["UseSSL"];
	$NewHash=base64_encode(serialize($hash));
	$sock->SaveConfigFile($NewHash, "FreeWebsApacheListenTable");
}

function listen_del(){
	$pattern=$_POST["Listen-del"];
	if(!preg_match("#(.+?):([0-9]+)#" , $pattern,$re)){writelogs("preg_match failed -> `$pattern`",__FUNCTION__,__FILE__,__LINE__);echo "preg_match failed...\n";return;}
	$ServerIP=$re[1];$ServerPort=$re[2];
	$sql="SELECT servername FROM freeweb WHERE ServerIP='{$ServerIP}' AND ServerPort='{$ServerPort}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$ff=array();
	$results=$q->QUERY_SQL($sql,'artica_backup');	
	if(!$q->ok){
		writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		echo $q->mysql_error;return;
	}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$ff[]=$ligne["servername"];}
	if(count($ff)>0){$tpl=new templates();echo $tpl->javascript_parse_text("{cannot_delete_items_used_srvwbs}")."\n".@implode("\n", $ff);return;}
	$sock=new sockets();
	$hash=unserialize(base64_decode($sock->GET_INFO("FreeWebsApacheListenTable")));
	unset($hash[$pattern]);
	$NewHash=base64_encode(serialize($hash));
	$sock->SaveConfigFile($NewHash, "FreeWebsApacheListenTable");	
}

function Query(){
	$MyPage=CurrentPageName();
	$freeweb=new freeweb($_GET["servername"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hash=unserialize(base64_decode($sock->GET_INFO("FreeWebsApacheListenTable")));
	

	
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$users=new usersMenus();	
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebListenPort=$sock->GET_INFO("FreeWebListenPort");
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	$FreeWebLeftMenu=$sock->GET_INFO("FreeWebLeftMenu");
	$FreeWebDisableSSL=$sock->GET_INFO("FreeWebDisableSSL");
	if($FreeWebListen==null){$FreeWebListen="*";}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($FreeWebDisableSSL)){$FreeWebDisableSSL=0;}
	if($FreeWebListenPort==null){$FreeWebListenPort=80;}
	if($FreeWebListenSSLPort==null){$FreeWebListenSSLPort=443;}	
	$tcp=new networking();
	$ips=$tcp->ALL_IPS_GET_ARRAY();
	$ips["*"]="{all}";	

	
	
	if($_POST["query"]<>null){
		$_POST["query"]="*{$_POST["query"]}*";
		$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);	
		
	}
	$page=1;
	$COUNT_ROWS=count($hash);
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$_POST["query"]=trim($_POST["query"]);
	$total = $COUNT_ROWS;
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	
	
	$defaultjs="<a href=\"javascript:blur();\" OnClick=\"javascript:FreeWebDefaultPort()\" style='font-size:18px;text-decoration:underline'>";
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("
		<img src='img/folder-network-32.png'>",
		$tpl->_ENGINE_parse_body("<span style='font-size:18px'>$defaultjs$FreeWebListen:$FreeWebListenPort <span style='font-size:11px'>(SSL:$FreeWebListen:$FreeWebListenSSLPort)</span></a></span>"),
		"<img src='img/20-check.png' style='margin-top:5px'>",
		"&nbsp;")
		);	
	
	
	while (list ($num, $ligne) = each ($hash) ){
		if($ligne==null){continue;}
		if($_POST["query"]<>null){if(!preg_match("#{$_POST["query"]}#", $num)){continue;}}
		
		$ssl="&nbsp;";
		$added=null;
		$id=md5($num);
		$delete=imgtootltip("delete-24.png","{delete}","ApacheListenDel('$num','$id')");
		if($ligne["SSL"]==1){
			$ssl="<img src='img/20-check.png' style='margin-top:5px'>";
		}
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("
		<img src='img/folder-network-32.png'>",
		"<span style='font-size:18px'>$num</a></span>",
		$ssl,
		$delete)
		);
	}
	
	
echo json_encode($data);	
}	