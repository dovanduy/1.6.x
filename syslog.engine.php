<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}


	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["master"])){master_index();exit;}
	if(isset($_GET["client"])){client_index();exit;}
	if(isset($_GET["ActAsASyslogServer"])){ActAsASyslogServerSave();exit;}
	if(isset($_GET["ActAsASyslogClient"])){ActAsASyslogClientSave();exit;}
	if(isset($_GET["syslog-servers-list"])){SyslogServerList();exit;}
	if(isset($_GET["syslog-host"])){SyslogServerListAdd();exit;}
	if(isset($_GET["syslog-host-delete"])){SyslogServerListDel();exit;}
	if(isset($_GET["localx"])){SyslogServerLocalx();exit;}
	if(isset($_GET["localx-list"])){SyslogServerLocalx_list();exit;}
	if(isset($_GET["localx-popup"])){SyslogServerLocalxPopup();exit;}
	if(isset($_POST["local"])){SyslogServerLocalxSave();exit;}
	if(isset($_POST["local-rm"])){SyslogServerLocalxRemove();exit;}
js();

function js(){
		$jsstart="syslogConfigLoad()";
		if(isset($_GET["windows"])){$jsstart="syslogConfigLoadPopup()";}
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{system_log}");
		$html="
		
		
		function syslogConfigLoad(){
			$('#BodyContent').load('$page?tabs=yes');
			}
			
		function syslogConfigLoadPopup(){
			YahooWin4(700,'$page?tabs=yes','$title');
			}			
			
		$jsstart;
		";
		echo $html;
		
	
}


function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["master"]='{syslog_server}';
	$array["localx"]='localx';
	$array["client"]='{client}';
	
	
	
	

	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:16px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_syslog style='width:100%;height:570px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_syslog\").tabs();});
		</script>";		
		
	
}

function master_index(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	
	if($ActAsASyslogServer==1){
		$ActAsSMTPGatewayStatistics=$sock->GET_INFO("ActAsSMTPGatewayStatistics");
		if(!is_numeric($ActAsSMTPGatewayStatistics)){$ActAsSMTPGatewayStatistics=0;}
		$enableSMTPStats=Paragraphe_switch_img("{ActAsSMTPGatewayStatistics}","{ActAsSMTPGatewayStatistics_text}",
		"ActAsSMTPGatewayStatistics","$ActAsSMTPGatewayStatistics",null,540);
		$enableSMTPStats="<br>$enableSMTPStats";
		
	}
	
	$enable=Paragraphe_switch_img("{enable_syslog_server}","{enable_syslog_server_text}","ActAsASyslogServer","$ActAsASyslogServer",null,540);
	
	$html="
	<div id='ActAsASyslogServerDiv' style='width:95%' class=form>
	$enable
	$enableSMTPStats
	<div style='text-align:right'>". button("{apply}","ActAsASyslogServerSave()",16)."</div>
	</div>
	
	<script>
	var SMTPSTATS=0;
	
	var x_ActAsASyslogServerSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('main_config_syslog');
			if(SMTPSTATS==1){CacheOff();}
		}			
		
	function ActAsASyslogServerSave(){
		var XHR = new XHRConnection();
		XHR.appendData('ActAsASyslogServer',document.getElementById('ActAsASyslogServer').value);
		if(document.getElementById('ActAsSMTPGatewayStatistics')){
			SMTPSTATS=document.getElementById('ActAsSMTPGatewayStatistics').value;
			XHR.appendData('ActAsSMTPGatewayStatistics',document.getElementById('ActAsSMTPGatewayStatistics').value);}
		AnimateDiv('ActAsASyslogServerDiv');
		XHR.sendAndLoad('$page', 'GET',x_ActAsASyslogServerSave);		
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function client_index(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$enable=Paragraphe_switch_img("{enable_syslog_client}","{enable_syslog_client_text}","ActAsASyslogClient","$ActAsASyslogClient",null,540);
	
	$html="
	<div id='ActAsASyslogClientDiv'>
	$enable
	<div style='text-align:right'><hr>". button("{apply}","ActAsASyslogClientSave()")."</div>
	</div>
	<p>&nbsp;</p>
	
	<table style='width:99%' class=form>
		<tr>
			<td class=legend>{address}:</td>
			<td>". Field_text("syslog-host",null,"font-size:14px;font-weight:bold;width:210px")."</td>
			<td class=legend>{port}:</td>
			<td>". Field_text("syslog-port",514,"font-size:14px;font-weight:bold;width:60px")."</td>	
			<td width=1%>". button("{add}","AddServerSyslogHost()")."</td>
		</tr>
	</table>
	
	<div id='syslog-servers-list' style='width:100%;height:255px;overflow:auto'></div>
	
	<script>
		var x_ActAsASyslogClientSave= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			RefreshTab('main_config_syslog');
		}	

		var x_AddServerSyslogHost= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)};
			SyslogServerListRefresh();
		}			
		
	function ActAsASyslogClientSave(){
		var XHR = new XHRConnection();
		XHR.appendData('ActAsASyslogClient',document.getElementById('ActAsASyslogClient').value);
		document.getElementById('ActAsASyslogClientDiv').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_ActAsASyslogClientSave);		
		}
		
	function AddServerSyslogHost(){
		var XHR = new XHRConnection();
		XHR.appendData('syslog-host',document.getElementById('syslog-host').value);
		XHR.appendData('syslog-port',document.getElementById('syslog-port').value);
		
		document.getElementById('syslog-servers-list').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
		XHR.sendAndLoad('$page', 'GET',x_AddServerSyslogHost);		
	}
		
	function SyslogServerListRefresh(){
		LoadAjax('syslog-servers-list','$page?syslog-servers-list=yes');
	
	}
	
	SyslogServerListRefresh();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SyslogServerListAdd(){
	$sock=new sockets();
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	$serversList["{$_GET["syslog-host"]}:{$_GET["syslog-port"]}"]="{$_GET["syslog-host"]}:{$_GET["syslog-port"]}";
	$sock->SaveConfigFile(base64_encode(serialize($serversList)),"ActAsASyslogClientServersList");
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");
}
function SyslogServerListDel(){
	$sock=new sockets();
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	unset($serversList[$_GET["syslog-host-delete"]]);
	$sock->SaveConfigFile(base64_encode(serialize($serversList)),"ActAsASyslogClientServersList");
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");	
}

function SyslogServerList(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$ActAsASyslogClient=$sock->GET_INFO("ActAsASyslogClient");
	$serversList=unserialize(base64_decode($sock->GET_INFO("ActAsASyslogClientServersList")));
	if(count($serversList)==0){return;}
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>&nbsp;</th>
		<th>{server}</th>
		<th>{status}</th>
		<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";		

	$icon="dns-cp-22.png";
if(is_array($serversList)){	
	while (list ($num, $server) = each ($serversList) ){
		if($server==null){continue;}
		$color="black";
		$udp="UNKNOWN";
		if($ActAsASyslogClient==1){
		if(preg_match("#(.+?):([0-9]+)#",$server,$re)){
			$udp=$sock->getFrameWork("cmd.php?IsUDPport=yes&host={$re[1]}&port={$re[2]}");}
		}
		if($udp=="UNKNOWN"){$udp_img="warning24.png";}
		if($udp=="OK"){$udp_img="ok24.png";}
		if($udp=="FAILED"){$udp_img="danger24.png";}
		
		
		
		if($ActAsASyslogClient<>1){$color="#CCCCCC";}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$delete=imgtootltip("delete-32.png","{delete}","SyslogServerDelete('$server')");
		$html=$html . "
		<tr  class=$classtr>
			<td width=1%><img src='img/$icon'></td>
			<td width=99%><strong style='font-size:14px'><code style='color:$color'>$server</code></td>
			<td width=1% align='center'><img src='img/$udp_img'></td>
			<td width=1%>$delete</td>
		</td>
		</tr>";
		
	}
}
	
	$html=$html."</tbody></table>
	<script>

	var x_SyslogServerDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		SyslogServerListRefresh();
	}		
	
	function SyslogServerDelete(key){
		var XHR = new XHRConnection();
		XHR.appendData('syslog-host-delete',key);	
		document.getElementById('syslog-servers-list').innerHTML='<center style=\"width:100%\"><img src=img/wait_verybig.gif></center>';
		XHR.sendAndLoad('$page', 'GET',x_SyslogServerDelete);
		}	

	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}


function ActAsASyslogServerSave(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogServer",$_GET["ActAsASyslogServer"]);
	$sock->getFrameWork("cmd.php?syslog-master-mode=yes");
	
	if(isset($_GET["ActAsSMTPGatewayStatistics"])){
		$sock->SET_INFO("ActAsSMTPGatewayStatistics", $_GET["ActAsSMTPGatewayStatistics"]);
		$sock->getFrameWork("cmd.php?restart-artica-maillog=yes");
	}
	
}

function ActAsASyslogClientSave(){
	$sock=new sockets();
	$sock->SET_INFO("ActAsASyslogClient",$_GET["ActAsASyslogClient"]);
	$sock->getFrameWork("cmd.php?syslog-client-mode=yes");	
	
}

function SyslogServerLocalx(){
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$add_rule=$tpl->javascript_parse_text("{add}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	
	$buttons="
	buttons : [
	{name: '$add_rule', bclass: 'add', onpress : AddLocalx},

	
	],";	
	
	
	$html="
	<div style='margin-right:-10px;margin-left:-15px'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>	
	<script>
var mm$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?localx-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: 'localx', name : 'localx', width :70, sortable : true, align: 'left'},
		{display: '$filename', name : 'fileanme', width : 512, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 38, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: 'localx', name : 'localx'},
		
		
		],
	sortname: 'localx',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 676,
	height: 370,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function AddLocalx(){
	YahooWin2('450','$page?localx-popup=yes&local=&t=$t','$add_rule');
}

		var x_DeleteSyslogLocal = function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);return;}		
			$('#row'+mm$t).remove();
		}

function DeleteSyslogLocal(local){
	mm$t=local;
	if(confirm('Delete:'+local+' ?')){
		var XHR = new XHRConnection();
		XHR.appendData('local-rm',local);
		XHR.sendAndLoad('$page', 'POST',x_DeleteSyslogLocal);		
	}
}


</script>	
";
	
	echo $html;
	
}

function SyslogServerLocalxPopup(){
	$t=time();
	$page=CurrentPageName();
	$sock=new sockets();
	$tpl=new templates();	
	for($i=0;$i<8;$i++){$hash["local$i"]="local$i";}
	$tt=$_GET["t"];
	$array=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	
	$html="
	<div id='$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>local:</td>
		<td>". Field_array_Hash($hash, "local-$t",$_GET["local"],'style:font-size:14px')."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{path}:</td>
		<td>". Field_text("path-$t",$array[$_GET["local"]],"font-size:14px;width:220px")."</td>
		<td>". button_browse("path-$t")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right' style='padding-top:10px'>". button("{apply}","SaveLocalxA$t()", 16)."</td>
	</tr>
	</table>
	<script>
		var x_SaveLocalxA$t = function (obj) {
			document.getElementById('$t').innerHTML='';
			$('#flexRT$tt').flexReload();
			YahooWin2Hide();
		}
	
	
	function SaveLocalxA$t(){
		var XHR = new XHRConnection();
		XHR.appendData('local',document.getElementById('local-$t').value);
		XHR.appendData('path',document.getElementById('path-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveLocalxA$t);
	}
	</script>

	
	";
echo $tpl->_ENGINE_parse_body($html);
}

function SyslogServerLocalxSave(){
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	$datas[$_POST["local"]]=$_POST["path"];
	$sock->SaveConfigFile(base64_encode(serialize($datas)), "SyslogLocals");
	$sock->getFrameWork("services.php?localx=yes");
}
function SyslogServerLocalxRemove(){
	$rm=$_POST["local-rm"];
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	unset($datas[$rm]);
	$sock->SaveConfigFile(base64_encode(serialize($datas)), "SyslogLocals");
	$sock->getFrameWork("services.php?localx=yes");
}


function SyslogServerLocalx_list(){
	
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->GET_INFO("SyslogLocals")));
	if(count($datas)==0){json_error_show("No data",1);}
	$MyPage=CurrentPageName();
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();
	$c=0;
	while (list ($local, $path) = each ($datas) ){
		$jsfiche=null;
		$herf=null;
		$md5=$local;
		if(!preg_match("#\.[a-z]+$#", $path)){
			$path=$path."/$local.log";
		}
		
		$delete=imgsimple("delete-32.png",null,"DeleteSyslogLocal('$local')");
		$c++;
		
		
		$herf="<a href=\"javascript:blur();\" OnClick=\"javascript:YahooWin2('450','$MyPage?localx-popup=yes&local=$local&t={$_GET["t"]}','$local');\"
		style='font-size:18px;text-decoration:underline'>";
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array("<span style='font-size:18px'>$herf{$local}</a></span>"
		,"<span style='font-size:18px'>$herf{$path}</a></span>",
		$delete )
		);
	}
	$data['total'] = $c;
	echo json_encode($data);		
	
	
}



