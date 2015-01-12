<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(!$GLOBALS["AS_ROOT"]){session_start();}
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

if(isset($_GET["parameters"])){parameters();exit;}
if(isset($_GET["parameters-smtp"])){SMTP_PAGE();exit;}


if(isset($_GET["client"])){clientmode();exit;}
if(isset($_POST["ArticaMetaPooling"])){save_server_params();exit;}
if(isset($_POST["EnableArticaMetaClient"])){save_client_params();exit;}



if(isset($_POST["smtp_server_name"])){save_server_smtp();exit;}
tabs();




function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["client"]="{client}";
	
	
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	
	if($EnableArticaMetaClient==0){
		$array["parameters"]="{server}";
		$array["parameters-smtp"]="{smtp_notifications}";
	}
	
	if($EnableArticaMetaServer==0){
		$array["client"]="{client}";
		unset($array["parameters-smtp"]);
	}	
	
	if($EnableArticaMetaServer==1){
		unset($array["client"]);
	}
	
	$fontsize=22;
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="hosts"){
			$tab[]="<li><a href=\"artica-meta.hosts.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_artica_meta",915)."<script>LeftDesign('management-console-256.png');</script>";
	
	
	
}

function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$t=time();
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	$ArticaMetaServerUsername=$sock->GET_INFO("ArticaMetaServerUsername");
	$ArticaMetaServerPassword=$sock->GET_INFO("ArticaMetaServerPassword");
	$ArticaMetaPooling=intval($sock->GET_INFO("ArticaMetaPooling"));
	$ArticaMetaUseLocalProxy=intval($sock->GET_INFO("ArticaMetaUseLocalProxy"));
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$ArticaMetaTimeOut=intval($sock->GET_INFO("ArticaMetaTimeOut"));
	if($ArticaMetaTimeOut==0){$ArticaMetaTimeOut=300;}
	$ldap=new clladp();
	if($ArticaMetaServerUsername==null){$ArticaMetaServerUsername=$ldap->ldap_admin;}
	if($ArticaMetaServerPassword==null){$ArticaMetaServerPassword=$ldap->ldap_password;}
	
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	if($ArticaMetaPooling==0){$ArticaMetaPooling=15;}
	$AsMetaServer=intval($sock->GET_INFO("AsMetaServer"));
	
	
	$HashTime[2]="02 {minutes}";
	$HashTime[3]="03 {minutes}";
	$HashTime[5]="05 {minutes}";
	$HashTime[10]="10 {minutes}";
	$HashTime[15]="15 {minutes}";
	$HashTime[20]="20 {minutes}";
	$HashTime[30]="30 {minutes}";
	$HashTime[60]="01 {hour}";
	$HashTime[120]="02 {hours}";
	$HashTime[240]="04 {hours}";
	
	$html="	<div style='width:98%' class=form>
	<div style='width:70%;margin:10px;text-align:right;float:right'>". button("{repair_tables}","Loadjs('artica-meta.repairtables.php')",18)."</div>		
	". Paragraphe_switch_img("{enable_artica_meta}", "{artica_meta_howto}","EnableArticaMetaServer-$t",$EnableArticaMetaServer,null,650)."		
			
	
		<table style='width:100%'>
			
		<tr>
			<td class=legend style='font-size:18px'>{use_local_proxy}:</td>
			<td style='font-size:18px'>". Field_checkbox("ArticaMetaUseLocalProxy-$t",1,$ArticaMetaUseLocalProxy)."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{timeout2} HTTP:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaTimeOut-$t",$ArticaMetaTimeOut,"font-size:18px;width:110px")."&nbsp;{minutes}</td>		
		</tr>							
		<tr>
			<td class=legend style='font-size:18px'>{send_orders}:</td>
			<td style='font-size:18px'>". Field_checkbox("ArticaMetaUseSendClient-$t",1,$ArticaMetaUseSendClient)."</td>		
		</tr>				
		<tr>
			<td class=legend style='font-size:18px'>{username}:</td>
			<td style='font-size:18px'>". Field_text("username-$t",$ArticaMetaServerUsername,"font-size:18px;width:240px")."</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{password}:</td>
			<td style='font-size:18px'>". Field_password("password-$t",$ArticaMetaServerPassword,"font-size:18px;width:240px")."</td>		
		</tr>			
		<tr>
			<td class=legend style='font-size:18px'>{popminpoll}:</td>
			<td style='font-size:18px'>". Field_array_Hash($HashTime, "ArticaMetaPooling-$t",$ArticaMetaPooling,"style:font-size:18px")."&nbsp;{minutes}</td>		
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{local_storage}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaStorage-$t",$ArticaMetaStorage,"font-size:18px;width:310px")."</td>		
		</tr>					
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
		</tr>
		</table>
	<script>

	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		window.location.reload(); 
	}	
	
	
	function Save$t(){
		var AsMetaServer=$AsMetaServer;
		var XHR = new XHRConnection();
		if(document.getElementById('ArticaMetaUseLocalProxy-$t').checked){
			XHR.appendData('ArticaMetaUseLocalProxy',1);
		}else{
			XHR.appendData('ArticaMetaUseLocalProxy',0);
		}
		if(document.getElementById('ArticaMetaUseSendClient-$t').checked){
			XHR.appendData('ArticaMetaUseSendClient',1);
		}else{
			XHR.appendData('ArticaMetaUseSendClient',0);
		}		
		
		
		XHR.appendData('ArticaMetaTimeOut',document.getElementById('ArticaMetaTimeOut-$t').value);
		if(AsMetaServer==0){XHR.appendData('EnableArticaMetaServer',document.getElementById('EnableArticaMetaServer-$t').value);}
		XHR.appendData('ArticaMetaPooling',document.getElementById('ArticaMetaPooling-$t').value);
		XHR.appendData('ArticaMetaServerUsername',document.getElementById('username-$t').value);
		XHR.appendData('ArticaMetaServerPassword',encodeURIComponent(document.getElementById('password-$t').value));
		XHR.appendData('ArticaMetaStorage',document.getElementById('ArticaMetaStorage-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
								
	</script>				
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save_server_params(){
	
	$sock=new sockets();
	$_POST["ArticaMetaServerPassword"]=url_decode_special_tool($_POST["ArticaMetaServerPassword"]);
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num, $ligne);
	}
	$sock->getFrameWork("system.php?artica-status-restart=yes");
}

function clientmode(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	$ArticaMetaTimeOut=intval($sock->GET_INFO("ArticaMetaTimeOut"));
	if($ArticaMetaTimeOut==0){$ArticaMetaTimeOut=300;}
	
	$ArticaMetaUsername=$sock->GET_INFO("ArticaMetaUsername");
	$ArticaMetaPassword=$sock->GET_INFO("ArticaMetaPassword");
	$ArticaMetaHost=$sock->GET_INFO("ArticaMetaHost");
	$ArticaMetaPort=intval($sock->GET_INFO("ArticaMetaPort"));
	if($ArticaMetaPort==0){$ArticaMetaPort=9000;}
	$ArticaMetaTimeOut=intval($sock->GET_INFO("ArticaMetaTimeOut"));
	if($ArticaMetaTimeOut==0){$ArticaMetaTimeOut=300;}
	
	
	$html="	<div style='width:98%' class=form>
		
	". Paragraphe_switch_img("{enable_artica_meta}", "{artica_meta_client_howto}","EnableArticaMetaClient-$t",$EnableArticaMetaClient,null,650)."
		
	
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{timeout2} HTTP:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaTimeOut-$t",$ArticaMetaTimeOut,"font-size:18px;width:110px")."&nbsp;{minutes}</td>		
		</tr>				
		<tr>
			<td class=legend style='font-size:18px'>{hostname}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaHost-$t",$ArticaMetaHost,"font-size:18px;width:240px")."</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:18px'>{port}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaPort-$t",$ArticaMetaPort,"font-size:18px;width:110px")."</td>
		</tr>				
			
		<tr>
			<td class=legend style='font-size:18px'>{username}:</td>
			<td style='font-size:18px'>". Field_text("username-$t",$ArticaMetaUsername,"font-size:18px;width:240px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{password}:</td>
			<td style='font-size:18px'>". Field_password("password-$t",$ArticaMetaPassword,"font-size:18px;width:240px")."</td>
		</tr>

		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",24)."</td>
		</tr>
		</table>
				<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		Loadjs('artica-meta.Register.progress.php');
		
	}
	
	
	function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaMetaClient',document.getElementById('EnableArticaMetaClient-$t').value);
	XHR.appendData('ArticaMetaHost',document.getElementById('ArticaMetaHost-$t').value);
	XHR.appendData('ArticaMetaPort',document.getElementById('ArticaMetaPort-$t').value);
	XHR.appendData('ArticaMetaTimeOut',document.getElementById('ArticaMetaTimeOut-$t').value);
	
	XHR.appendData('ArticaMetaUsername',document.getElementById('username-$t').value);
	XHR.appendData('ArticaMetaPassword',encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function save_client_params(){
	$sock=new sockets();
	$_POST["ArticaMetaPassword"]=url_decode_special_tool($_POST["ArticaMetaPassword"]);
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num, $ligne);
	}
	
	$sock->getFrameWork("system.php?artica-status-restart=yes");
	
	
	
}

function SMTP_PAGE(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ArticaMetaSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("ArticaMetaSMTPNotifs")));
	$t=time();

	$html="<div style='width:98%' class=form>
			
". Paragraphe_switch_img("{tls_enabled}", "{tls_enabled_explain}",
						"tls_enabled-$t",$ArticaMetaSMTPNotifs["tls_enabled"],null,600)."
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_server_name}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_server_name-$t",$ArticaMetaSMTPNotifs["smtp_server_name"],
				"font-size:18px;width:350px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_server_port}:</td>
<td	style='font-size:18px'>". Field_text("smtp_server_port-$t",$ArticaMetaSMTPNotifs["smtp_server_port"],
		"font-size:18px;width:110px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_sender}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_sender-$t",$ArticaMetaSMTPNotifs["smtp_sender"],
				"font-size:18px;width:290px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_dest}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_dest-$t",$ArticaMetaSMTPNotifs["smtp_dest"],
				"font-size:18px;width:290px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_auth_user}:</td>
		<td	style='font-size:18px'>". Field_text("smtp_auth_user-$t",$ArticaMetaSMTPNotifs["smtp_auth_user"],
				"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{smtp_auth_passwd}:</td>
		<td	style='font-size:18px'>". Field_password("smtp_auth_passwd-$t",$ArticaMetaSMTPNotifs["smtp_auth_passwd"],
				"font-size:18px;width:190px")."&nbsp;</td>
		<td width=1%></td>
	</tr>
	<tr>
<td colspan=3 align='right'><hr>". button("{test}","Save$t(true)",24)."&nbsp;|&nbsp;". button("{apply}","Save$t(false)",24)."</td>
</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	UnlockPage();
	if(results.length>3){alert(results);}
	RefreshTab('main_artica_meta');
}

function Save$t(astests){
	var XHR = new XHRConnection();
	XHR.appendData('smtp_server_name',document.getElementById('smtp_server_name-$t').value);
	XHR.appendData('smtp_server_port',document.getElementById('smtp_server_port-$t').value);
	XHR.appendData('smtp_sender',document.getElementById('smtp_sender-$t').value);
	XHR.appendData('smtp_dest',document.getElementById('smtp_dest-$t').value);
	XHR.appendData('smtp_auth_user',document.getElementById('smtp_auth_user-$t').value);
	XHR.appendData('smtp_auth_passwd',encodeURIComponent(document.getElementById('smtp_auth_passwd-$t').value));
	XHR.appendData('tls_enabled',document.getElementById('tls_enabled-$t').value);
	if(astests){
	XHR.appendData('TESTS_IT','TRUE');
	}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function save_server_smtp(){
	
	if(isset($_POST["smtp_auth_passwd"])){$_POST["smtp_auth_passwd"]=url_decode_special_tool($_POST["smtp_auth_passwd"]);}
	
	
	while (list ($num, $ligne) = each ($_POST) ){
		$ArticaMetaSMTPNotifs[$num]=$ligne;
	
	}
	$newparam=base64_encode(serialize($ArticaMetaSMTPNotifs));
	$sock=new sockets();
	$sock->SaveConfigFile($newparam, "ArticaMetaSMTPNotifs");
	if(isset($_POST["TESTS_IT"])){
		echo $sock->getFrameWork("artica.php?meta-tests-smtp=yes");
	}
	
}