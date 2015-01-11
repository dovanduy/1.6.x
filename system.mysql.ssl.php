<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}
	if(isset($_GET["ssl_client-keys"])){ssl_client_key();exit;}
	if(isset($_POST["mysqlSSL"])){mysqlSSLSave();exit;}
	if(isset($_POST["GenerateMysqlSSLKeys"])){GenerateMysqlSSLKeys();exit;}
page();


function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$ssl_conf=Paragraphe("64-ssl-key-params.png","{ssl_certificate}","{ssl_certificate_text}","javascript:Loadjs('postfix.tls.php?js-certificate=yes')");
	
	if($instance_id>0){
		$q=new mysqlserver_multi($instance_id);
		if($q->usesocket==0){echo $tpl->_ENGINE_parse_body("<H2>{cannot_use_ssl_no_networking_set}</H2>");return;}
		$mysqlSSL=$q->mysqlSSL;
		$ssl_path="/etc/ssl/certs/mysql-instance-$instance_id-client-download";
		$ssl_conf=null;
	}else{
		$sock=new sockets();
		$mysqlSSL=$sock->GET_INFO("mysqlSSL");
		$ssl_path="/etc/ssl/certs/mysql-client-download";
	}
	if(!is_numeric($mysqlSSL)){$mysqlSSL=0;}
	
	
	
	
	if(is_file("$ssl_path/mysql-ssl-client.tar")){
		$tar=Paragraphe("tar-icon-64.png", "{ssl_keys}", "{ssl_mysql_client_keys}","javascript:s_PopUp('$page?ssl_client-keys=yes&instance-id=$instance_id',1,1)");
		
	}
	
	$gen=Paragraphe("64-ssl-key.png", "{generate_ssl_keys}", "{generate_ssl_keys_text}","javascript:GenerateMysqlSSLKeys()");

	$enable=Paragraphe_switch_img("{UseSSL}", "{mysql_explain_enable_ssl}","mysqlSSL$instance_id",$mysqlSSL,400);
	
	
	$html="
	<input type='hidden' id='mysqlfile_text' value='{mysql_ssl_explain}'>
	<div class=text-info id='mysqlfile'>{mysql_ssl_explain}</div>
	<table style='width:100%'>
	<tr>
		<td valign='top' width=1%>$tar$gen$ssl_conf</td>
		<td valign='top'>$enable<div style='text-align:right'><hr>". button("{apply}","SaveSSLMysql()")."</div></td>
	</tr>
	</table>
	
	
	
	<script>
	
	var x_SaveSSLMysql=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			document.getElementById('mysqlfile').innerHTML=document.getElementById('mysqlfile_text').value;
			var instance_id=$instance_id;
			if(instance_id>0){RefreshTab('main_config_instance_mysql_multi');return;}
			RefreshTab('main_config_mysql');
		}	
		
	var x_GenerateMysqlSSLKeys=function (obj) {
			var results=obj.responseText;
			if (results.length>0){alert(results);}
			document.getElementById('mysqlfile').innerHTML=document.getElementById('mysqlfile_text').value;
			var instance_id=$instance_id;
			if(instance_id>0){RefreshTab('main_config_instance_mysql_multi');return;}			
			RefreshTab('main_config_mysql');
		}		
	
			
		function SaveSSLMysql(){
			var XHR = new XHRConnection();
			XHR.appendData('mysqlSSL',document.getElementById('mysqlSSL$instance_id').value);
			XHR.appendData('instance-id','$instance_id');
			AnimateDiv('mysqlfile');
			XHR.sendAndLoad('$page', 'POST',x_SaveSSLMysql);
		
		}
		
		function GenerateMysqlSSLKeys(){
			var XHR = new XHRConnection();
			XHR.appendData('GenerateMysqlSSLKeys','yes');
			XHR.appendData('instance-id','$instance_id');
			AnimateDiv('mysqlfile');
			XHR.sendAndLoad('$page', 'POST',x_GenerateMysqlSSLKeys);		
		}
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function mysqlSSLSave(){
	$instance_id=$_POST["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	if($instance_id>0){
		$q=new mysqlserver_multi($instance_id);
		$q->mysqlSSL=$_POST["mysqlSSL"];
		$q->save();
		return;
	}
	
	$sock=new sockets();
	$sock->SET_INFO("mysqlSSL", $_POST["mysqlSSL"]);
	
}

function GenerateMysqlSSLKeys(){
	$instance_id=$_POST["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}	
	$sock=new sockets();
	$sock->getFrameWork("services.php?mysql-ssl-keys=yes&instance-id=$instance_id");
}

function ssl_client_key(){
	$instance_id=$_GET["instance-id"];
	if(!is_numeric($instance_id)){$instance_id=0;}
	$file="/etc/ssl/certs/mysql-client-download/mysql-ssl-client.tar";
	if($instance_id>0){
		$file="/etc/ssl/certs/mysql-instance-$instance_id-client-download/mysql-ssl-client.tar";
	}

$size = filesize($file);
header("Content-Type: application/force-download; name=\"" . basename($file) . "\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: $size");
header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
header("Expires: 0");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
readfile($file);
exit();
} 
