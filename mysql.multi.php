<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.mysql-multi.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.system.network.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		$line=__LINE__;$file=CurrentPageName();
		echo $tpl->_ENGINE_parse_body("alert('$line:$file::{ERROR_NO_PRIVS}');");
		die();
	}	
	
	if(isset($_POST["mysql-delete-id"])){mysql_instance_delete_perform();exit;}
	if(isset($_GET["mysql-instances-search"])){mysql_instance_list();exit;}
	if(isset($_GET["mysql-server-js"])){mysql_instance_js();exit;}
	if(isset($_GET["mysql-server-id"])){mysql_instance_tabs();exit;}
	if(isset($_GET["mysql-server-params"])){mysql_instance_params();exit;}
	if(isset($_POST["hostname"])){mysql_instance_save();exit;}
	if(isset($_GET["mysql-delete-js"])){mysql_instance_delete_js();exit;}
	if(isset($_GET["mysql-server-service-js"])){mysql_instance_service_js();exit;}
	if(isset($_POST["mysql-server-service-perf"])){mysql_instance_service_perf();exit;}
	if(isset($_POST["log"])){mysql_instance_log();exit;}
	
	if(isset($_GET["root-account-js"])){root_account_js();exit;}
	if(isset($_GET["root-account-popup"])){root_account_popup();exit;}
	if(isset($_POST["root-account-save"])){root_account_save();exit;}
	
page();

function root_account_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";}
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM mysqlmulti WHERE ID='{$_GET["instance-id"]}'","artica_backup"));
		$title=$ligne["servername"];
	}
	
	$Layer="YahooWin2";
	
	$title=$tpl->_ENGINE_parse_body($title);
	echo "$Layer('550','$page?root-account-popup=yes&instance-id={$_GET["instance-id"]}&layer=$Layer','[{$_GET["instance-id"]}]:{$ligne["servername"]}::{root_account}')";	
	
}

function root_account_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$mysql=new mysql_multi($_GET["instance-id"]);
	
	$html="
	<div id='$t'></div>
		<table style='width:100%'>
	<tr>
		<td valign='top'>
			<img src='img/change-mysql-128.png'>
		</td>
		<td valign='top'><div class=text-info>{MYSQL_PASSWORD_USER_TEXT}</div>
			<table style='width:99.5%' class=form>
				<tr>
					<td valign='top' class=legend nowrap>{username}:</td>
					<td valign='top'>". Field_text("username-{$_GET["instance-id"]}","root","font-size:16px;padding:3px")."</td>
				</tr>
				<tr>
					<td valign='top' class=legend>{password}:</td>
					<td valign='top'>". Field_password("password-{$_GET["instance-id"]}",$mysql->mysql_password,"font-size:16px;padding:3px;width:120px")."</td>
				</tr>
				<tr>
					<td colspan=2 align='right'>
						<hr>". button("{change}","ChangeMysqlInstancePassword()",16)."
					</td>
				</tr>
			</table>		
		</td>
	</tr>
	</table>
	
	<script>
	var x_ChangeMysqlInstancePassword= function (obj) {
		var results=obj.responseText;
		document.getElementById('$t').innerHTML=results;
		if(document.getElementById('mysql-instances-table')){FlexReloadMysqlInstanceTable();}
		
	
	}
		
	function ChangeMysqlInstancePassword(){
		var username=document.getElementById('username-{$_GET["instance-id"]}').value;
		var password=document.getElementById('password-{$_GET["instance-id"]}').value;
		var XHR = new XHRConnection();
		XHR.appendData('root-account-save',username);	
		XHR.appendData('mysql_admin',username);
		XHR.appendData('mysql_password',password);
		XHR.appendData('instance-id','{$_GET["instance-id"]}');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_ChangeMysqlInstancePassword);			
	
	}	
	
	document.getElementById('username-{$_GET["instance-id"]}').disabled=true;
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function root_account_save(){
	$q=new mysqlserver_multi($_POST["instance-id"]);
	$q->mysql_admin=$_POST["mysql_admin"];
	$q->mysql_password=$_POST["mysql_password"];
	$q->save(true);
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("mysql.php?multi-root=yes&instance-id={$_POST["instance-id"]}")));
	echo "<div style='width:100%;height:250px;overflow:auto'>";
	
	while (list ($index, $ligne) = each ($datas) ){
		if(trim($ligne)==null){continue;}
		echo "<div><code style='font-size:11px'>".htmlentities($ligne)."</code></div>";
	}
	
	echo "</div>";
	
	
}


function mysql_instance_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$height=908;
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){$title="{new_server}";$height=560;}
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM mysqlmulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
		$title=$ligne["servername"];
		
	}
	$title=$tpl->_ENGINE_parse_body($title);
	$Layer="YahooWin2";
	echo "$Layer($height,'$page?mysql-server-id=yes&ID={$_GET["ID"]}&layer=$Layer','[{$_GET["ID"]}]:$title')";
}

function mysql_instance_service_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$t=time();
	
	$html="var x_{$_GET["ID"]}_CheckGeneric= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_instance_mysql_multi');
		if(document.getElementById('mysql-instances-table')){FlexReloadMysqlInstanceTable();}
		if(document.getElementById('freeweb-mysql-instances')){freeweb_mysql_instances();}
		}		
	
	function InstanceMySqlService{$_GET["ID"]}(){
		var XHR = new XHRConnection();	
		document.getElementById('animate-service-instance-{$_GET["ID"]}').innerHTML='<img src=\"img/ajax-menus-loader.gif\">';
		XHR.appendData('mysql-server-service-perf','{$_GET["ID"]}');
		XHR.appendData('action','{$_GET["action"]}');
		XHR.sendAndLoad('$page', 'POST',x_{$_GET["ID"]}_CheckGeneric);
	}
	
 InstanceMySqlService{$_GET["ID"]}();
";
header("Content-type: text/javascript");
echo $html;	
	
}

function mysql_instance_service_perf(){
	
	$sock=new sockets();
	$datas=$sock->getFrameWork("mysql.php?instance-service=yes&instance_id={$_POST["mysql-server-service-perf"]}&action={$_POST["action"]}");
	echo @implode("\n",unserialize(base64_decode($datas)));
}

function mysql_instance_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$t=time();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT servername FROM mysqlmulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	
	$html="var x_{$_GET["ID"]}_CheckGeneric= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_instance_mysql_multi');
		if(document.getElementById('mysql-instances-table')){FlexReloadMysqlInstanceTable();}
		}		
	
	function InstanceMySqlDelete{$_GET["ID"]}(){
		if(confirm('$are_you_sure_to_delete {$ligne["servername"]} ?')){
			var XHR = new XHRConnection();	
			XHR.appendData('mysql-delete-id','{$_GET["ID"]}');
			XHR.sendAndLoad('$page', 'POST',x_{$_GET["ID"]}_CheckGeneric);
		}	
	}
	
 InstanceMySqlDelete{$_GET["ID"]}();
";
header("Content-type: text/javascript");
echo $html;
	
}

function mysql_instance_delete_perform(){
	$sock=new sockets();
	$q=new mysql();
	$sql="DELETE FROM mysqlmulti WHERE ID='{$_POST["mysql-delete-id"]}'";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return ;}
	$sock->getFrameWork("mysql.php?instance-delete=yes&instance_id={$_POST["mysql-delete-id"]}");
}

function mysql_instance_tabs(){
	if(!isset($_GET["tab"])){$_GET["tab"]=0;};
	$page=CurrentPageName();
	$tpl=new templates();
	$height=640;
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;}	
	if($_GET["ID"]==0){
		$title="{new_server}";
		$height=500;
		
	}
	$array["mysql-server-params"]="{global_parameters}";
	
	
	
	if($_GET["ID"]>0){
		$array["mysql-server-members"]="{mysql_users}";
		$array["mysql-server-settings"]="{settings}";
		$array["mysql-perso"]="{mysql_perso_conf}";
		$array["ssl"]='{ssl}';
		$array["globals"]='{globals_values}';
		$array["log"]='Log';
	}
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=='mysql-server-members'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.php?members=yes&instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=='globals'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.globals.php?instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=='mysql-perso'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.perso.php?popup=yes&instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=='ssl'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.ssl.php?instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		if($num=='log'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"system.mysql.events.php?mysql-events=yes&instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		

		

		if($num=='mysql-server-settings'){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mysql.settings.php?popup=yes&instance-id={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}&layer={$_GET["layer"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_instance_mysql_multi style='width:100%;height:{$height}px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_instance_mysql_multi').tabs();
				});
		</script>";			
}

function mysql_instance_params(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ip=new networking();
	$q=new mysql();
	$ServerToAdd=0;
	if(!is_numeric($_GET["ID"])){$_GET["ID"]=0;$ServerToAdd=1;}	
	if($_GET["ID"]==0){$title="{new_server}";$ServerToAdd=1;}
	
	$button="{add}";
	if($_GET["ID"]>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM mysqlmulti WHERE ID='{$_GET["ID"]}'","artica_backup"));
		$title=$ligne["servername"];
		$button="{apply}";
		$params=unserialize(base64_decode($ligne["params"]));
	}	
	
	$MonitConfig=$params["MONIT"];
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=100;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1000;}
	
	
	$ips=$ip->ALL_IPS_GET_ARRAY();	
	$ips["0.0.0.0"]="{all}";
	$t=time();
	$users=new usersMenus();
	$MONIT_INSTALLED=1;
	if(!$users->MONIT_INSTALLED){$MONIT_INSTALLED=0;}
	
	$GetLastInstanceNum=GetLastInstanceNum()+1;
	if(!is_numeric($ligne["listen_port"])){$ligne["listen_port"]=GetLastPort()+1;}
	if($ligne["Dir"]==null){$ligne["Dir"]="$MYSQL_DATA_DIR-$GetLastInstanceNum";}
	
	$nets=Field_array_Hash($ips,"$t-addr",$ligne["listen_addr"],"style:font-size:14px;padding:3px");
	$html="
	<div id='$t' ><span style='font-size:16px'>$title</span>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td>". Field_checkbox("$t-enabled", 1,$ligne["enabled"],"InstanceChecKenabled{$t}()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{hostname}:</td>
		<td>". Field_text("$t-hostname",$ligne["servername"],"font-size:14px;padding:3px;width:220px")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{UseNetworkCard}:</td>
		<td>". Field_checkbox("$t-usesocket", 1,$ligne["usesocket"],"InstanceCheckUsesocket{$t}()")."</td>
		<td>&nbsp;</td>
	</tr>		
	
	<tr>
		<td class=legend style='font-size:14px'>{listen_address}:</td>
		<td>$nets</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("$t-listen_port",$ligne["listen_port"],"font-size:14px;padding:3px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{directory}:</td>
		<td>". Field_text("$t-Dir",$ligne["Dir"],"font-size:14px;padding:3px;width:220px")."</td>
		<td><input type='button' value='{browse}&nbsp;&raquo;' OnClick=\"javascript:Loadjs('tree.php?select-dir=yes&target-form=$t-Dir');\"></td>
	</tr>

	
	
	
	<tr>
		<td colspan=3 align='right'><hr>". button($button, "SaveInstance{$t}()",16)."</td>
	</tr>
	</tbody>
	</table>
	<hr>
<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{enable_watchdog}:</td>
		<td>". Field_checkbox("$t-watchdog", 1,$ligne["watchdog"],"InstanceCheckWatchdog{$t}()")."</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{notify_when_cpu_exceed}:</td>
		<td style='font-size:14px'>". Field_text("$t-watchdogCPU", $MonitConfig["watchdogCPU"],"font-size:14px;width:60px")."&nbsp;%</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{notify_when_memory_exceed}:</td>
		<td style='font-size:14px'>". Field_text("$t-watchdogMEM", $MonitConfig["watchdogMEM"],"font-size:14px;width:60px")."&nbsp;MB</td>
		<td>&nbsp;</td>
	</tr>
<tr>
		<td colspan=3 align='right'><hr>". button($button, "SaveInstance{$t}()",16)."</td>
	</tr>	
	</tbody>
</table>		

</div>
	
	
	
	
	<script>
	
	var x_{$t}_SaveInstance= function (obj) {
		var results=obj.responseText;
		var ServerToAdd=$ServerToAdd;
		if(results.length>0){alert(results);}
		RefreshTab('main_config_instance_mysql_multi');
		if(document.getElementById('mysql-instances-table')){FlexReloadMysqlInstanceTable();}
		if(ServerToAdd==1){{$_GET["layer"]}Hide();}
		if(document.getElementById('freeweb-mysql-instances')){freeweb_mysql_instances();}
		}	
	
	function SaveInstance{$t}(){
		var XHR = new XHRConnection();	
		if(document.getElementById('$t-enabled').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		if(document.getElementById('$t-usesocket').checked){XHR.appendData('usesocket',1);}else{XHR.appendData('usesocket',0);}
		if(document.getElementById('$t-watchdog').checked){XHR.appendData('watchdog',1);}else{XHR.appendData('watchdog',0);}
		
		
		XHR.appendData('watchdogMEM',document.getElementById('$t-watchdogMEM').value);
		XHR.appendData('watchdogCPU',document.getElementById('$t-watchdogCPU').value);
		
		XHR.appendData('hostname',document.getElementById('$t-hostname').value);
		XHR.appendData('listen_addr',document.getElementById('$t-addr').value);
		XHR.appendData('listen_port',document.getElementById('$t-listen_port').value);
		XHR.appendData('Dir',document.getElementById('$t-Dir').value);
		XHR.appendData('ID','{$_GET["ID"]}');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_{$t}_SaveInstance);
	}
	
	
	function InstanceChecKenabled{$t}(){
		document.getElementById('$t-usesocket').disabled=true;
		document.getElementById('$t-hostname').disabled=true;
		document.getElementById('$t-addr').disabled=true;
		document.getElementById('$t-listen_port').disabled=true;
		document.getElementById('$t-Dir').disabled=true;
	
		if(document.getElementById('$t-enabled').checked){
			document.getElementById('$t-hostname').disabled=false;
			document.getElementById('$t-Dir').disabled=false;
			document.getElementById('$t-usesocket').disabled=false;
		}
		InstanceCheckUsesocket{$t}();
	}
	
	function InstanceCheckUsesocket{$t}(){
		if(!document.getElementById('$t-enabled').checked){return;}
			document.getElementById('$t-addr').disabled=true;
			document.getElementById('$t-listen_port').disabled=true;		
		if(document.getElementById('$t-usesocket').checked){
			document.getElementById('$t-addr').disabled=false;
			document.getElementById('$t-listen_port').disabled=false;
		}
	
	}
	
	function InstanceCheckWatchdog{$t}(){
		var MONIT_INSTALLED=$MONIT_INSTALLED;
		document.getElementById('$t-watchdog').disabled=true;
		document.getElementById('$t-watchdogMEM').disabled=true;
		document.getElementById('$t-watchdogCPU').disabled=true;
		if(MONIT_INSTALLED==0){return;}
		document.getElementById('$t-watchdog').disabled=false;
		if(!document.getElementById('$t-watchdog').checked){return;}
		document.getElementById('$t-watchdogMEM').disabled=false;
		document.getElementById('$t-watchdogCPU').disabled=false;		
	
	}
	
	
	InstanceChecKenabled{$t}();
	InstanceCheckWatchdog{$t}()
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mysql_instance_save(){
	$q=new mysql();
	
	if($_POST["ID"]<1){
		
		$params["MONIT"]["watchdogCPU"]=$_POST["watchdogCPU"];
		$params["MONIT"]["watchdogMEM"]=$_POST["watchdogMEM"];
		$paramsNew=base64_encode(serialize($params));
		$sql="INSERT IGNORE INTO mysqlmulti (servername,enabled,listen_addr,listen_port,Dir,usesocket,watchdog,params)
		VALUES('{$_POST["hostname"]}','{$_POST["enabled"]}','{$_POST["listen_addr"]}','{$_POST["listen_port"]}',
		'{$_POST["Dir"]}','{$_POST["usesocket"]}','{$_POST["watchdog"]}','$paramsNew')";
		
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT params FROM mysqlmulti WHERE ID='{$_POST["ID"]}'","artica_backup"));
		$params=unserialize(base64_decode($ligne["params"]));
		$params["MONIT"]["watchdogCPU"]=$_POST["watchdogCPU"];
		$params["MONIT"]["watchdogMEM"]=$_POST["watchdogMEM"];
		$paramsNew=base64_encode(serialize($params));
		$sql="UPDATE mysqlmulti SET 
			servername='{$_POST["hostname"]}',
			enabled='{$_POST["enabled"]}',	
			listen_addr='{$_POST["listen_addr"]}',
			listen_port='{$_POST["listen_port"]}',
			Dir='{$_POST["Dir"]}',
			usesocket='{$_POST["usesocket"]}',
			watchdog='{$_POST["watchdog"]}',
			params='$paramsNew'
			WHERE ID={$_POST["ID"]}";
		
		
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){
		if(strpos(" ".$q->mysql_error,"Unknown column")>0){$q->BuildTables();$q->QUERY_SQL($sql,"artica_backup");}
	}
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	
	$sock=new sockets();
	if($_POST["ID"]>0){
		$sock->getFrameWork("mysql.php?instance-reconfigure=yes&instance-id={$_POST["ID"]}");
	}else{
		$sock->getFrameWork("mysql.php?instance-reconfigure=yes&instance-id=$q->last_id");
	}	
		
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$listen_address=$tpl->_ENGINE_parse_body("{listen_address}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$status=$tpl->_ENGINE_parse_body("{status}");
	$mysql_multi_explain=$tpl->_ENGINE_parse_body("{mysql_multi_explain}");
	$buttons="
	buttons : [
	{name: '$new_server', bclass: 'add', onpress : AddMysqlServer},
	],";		
		
	

$html="
<span id='mysql-instances-table'></span>
<div class=text-info style='font-size:14px'>$mysql_multi_explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?mysql-instances-search=yes',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'servername', width : 183, sortable : false, align: 'left'},	
		{display: '$listen_address', name : 'listen_addr', width :150, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'description', width :247, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'browse', width :32, sortable : false, align: 'center'},
		{display: 'stats', name : 'stats', width :32, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'stats1', width :32, sortable : false, align: 'left'},
		{display: '$status', name : 'enabled', width : 25, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'enabled', width : 25, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 32, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'servername'},
		],
	sortname: 'servername',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 880,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		FlexReloadblk();
		if(document.getElementById('rules-toolbox')){RulesToolBox();}
	}

	function FlexReloadMysqlInstanceTable(){
		$('#flexRT$t').flexReload();
	}



function AddMysqlServer(){
	Loadjs('$page?mysql-server-js=yes&ID=');

}

function MysqlInstanceDelete(ID){
	
}




</script>

";	
	echo $html;
	
}
function mysql_instance_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	
	$search='%';
	$table="mysqlmulti";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table,'artica_backup')==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,'artica_backup');
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	// http://www.techrepublic.com/blog/opensource/10-mysql-variables-that-you-should-monitor/56
	$sock=new sockets();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["ID"];
		$RSS=null;
		$icon_status="danger24.png";
		$icon_stop=imgsimple("24-stop.png","{stop}","Loadjs('$MyPage?mysql-server-service-js=yes&ID={$ligne["ID"]}&action=stop');");
		$icon_stopoff=imgsimple("24-stop-grey.png","{stop}","");
		$icon_run=imgsimple("24-run.png","{run}","Loadjs('$MyPage?mysql-server-service-js=yes&ID={$ligne["ID"]}&action=start');");
		
		$icon_stats=imgsimple("statistics-24.png","{run}","Loadjs('system.mysql.graphs.php?instance-id={$ligne["ID"]}');");
		
		if(!isset($ligne["usesocket"])){$ligne["usesocket"]=0;}
		$GBSTAT=trim($sock->getFrameWork("mysql.php?instance-status=yes&instance_id=$id"));
		
		if($GBSTAT=="ON"){
			$run="{running}";
			$MEMAR=unserialize(base64_decode($sock->getFrameWork("mysql.php?instance-memory=yes&instance-id=$id")));
			$RSS="<br>{memory}: <strong>".FormatBytes($MEMAR[0])."</strong><br>{virtual_memory}: <strong>".FormatBytes($MEMAR[1])."</strong>";
			$icon_status="ok24.png";
		}else{
			$icon_stop=$icon_run;
			$run="{stopped}";
		}
		
		$qq=new mysql_multi($ligne["ID"]);
		$qr=$qq->GLOBAL_STATUS();
		
		$explain="<strong>{$qr["Threads_created"]}</strong> {threads_created},
		<br><strong>{$qr["Threads_running"]}</strong> {threads_running}<br>{since}:". UptimeString($qr["Uptime"])."$RSS";
		
		if($GBSTAT<>"ON"){$explain="{stopped}";}
		
		
		$run=$tpl->_ENGINE_parse_body($run);
		$explain=$tpl->_ENGINE_parse_body($explain);
		$tt=@implode(",", $f);
		$js="Loadjs('$MyPage?mysql-server-js=yes&ID={$ligne["ID"]}');";
		$delete=imgsimple("delete-24.png","{delete} {$ligne["pattern"]}","Loadjs('$MyPage?mysql-delete-js=yes&ID={$ligne["ID"]}');");
		
		$net="{$ligne["listen_addr"]}:{$ligne["listen_port"]}";
		if($ligne["usesocket"]==0){$net=".../mysqld{$ligne["ID"]}.sock";}
		$member=imgsimple("members-priv-24.png","{member}","Loadjs('$MyPage?root-account-js=yes&instance-id={$ligne["ID"]}')");
		$browse=imgsimple("table-show-24.png","{member}","Loadjs('mysql.browse.php?instance-id={$ligne["ID"]}')");
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:16px;text-decoration:underline'>{$ligne["servername"]}</span></a><br><i>$run</i>"
		,"<span style='font-size:16px'>$net</span>",
		"<span style='font-size:14px'>$explain</span>",
		"<div style=margin-top:15px>$browse</div>",
		"<div style=margin-top:15px>$icon_stats",
		"<div style=margin-top:15px>$member",
		"<div style=margin-top:15px><img src='img/$icon_status'></div>",
		"<div style=margin-top:15px><span id='animate-service-instance-{$ligne["ID"]}'>$icon_stop</span></div>",
		"<div style=margin-top:15px><span id='animate-service-instance-{$ligne["ID"]}'>$delete</div>" )
		);
	}
	
	
echo json_encode($data);		

}

function UptimeString($Uptime){
// Make a pretty uptime string
$seconds = $Uptime % 60;
$minutes = floor(($Uptime % 3600) / 60);
$hours = floor(($Uptime % 86400) / (3600));
$days = floor($Uptime / (86400));
if ($days > 0) {
	$Uptimestring = "${days}d ${hours}h ${minutes}m ${seconds}s";
} elseif ($hours > 0) {
	$Uptimestring = "${hours}h ${minutes}m ${seconds}s";
} elseif ($minutes > 0) {
	$Uptimestring = "${minutes}m ${seconds}s";
} else {
	$Uptimestring = "${seconds}s";
}

return $Uptimestring;

}

function GetLastPort(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT listen_port FROM mysqlmulti ORDER BY listen_port DESC LIMIT 0,1","artica_backup"));
	if(!is_numeric($ligne["listen_port"])){$ligne["listen_port"]=3306;}
	return $ligne["listen_port"];
}
function GetLastInstanceNum(){
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM mysqlmulti ORDER BY ID DESC LIMIT 0,1","artica_backup"));
	if(!is_numeric($ligne["ID"])){$ligne["ID"]=0;}	
	return $ligne["ID"];
}

function mysql_instance_log(){
	
	
}

