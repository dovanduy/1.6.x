<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.reverse.inc');
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "<p class=text-error>". $tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}")."</p>";
		die();exit();
	}
	
	if(isset($_POST["ssl"])){Save();exit;}
	
	
page();	
	
function page(){
	
	FORM_START();
	$tpl=new templates();
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	if(!is_numeric($ligne["ssl_backend_port"])){$ligne["ssl_backend_port"]=443;}
	
	FORM_ADD_TITLE("{port}:{$ligne["port"]} &laquo;$servername&raquo;");
	FORM_START_TABLE();
	FORM_ADD_PARAGRAPH(
		"ssl",$ligne["ssl"],"{UseSSL}","{NGINX_USE_SSL_EXPLAIN}",700
	);
	
	FORM_ADD_ARRAY_HASH($sslcertificates,"certificate",$ligne["certificate"],"{certificate}");
	
	FORM_ADD_HIDDEN("servername",$servername);
	FORM_ADD_PARAGRAPH(
	"ssl_backend",$ligne["ssl_backend"],"{destination_use_ssl}","{NGINX_USE_SSL_EXPLAIN2}",700
	);
	
	FORM_ADD_FIELD("ssl_backend_port",$ligne["ssl_backend_port"],"{remote_ssl_port}",null,110);
	FORM_COMPILE(CurrentPageName(),"{apply}");
	
	
	
	
	
}

function Save(){
	
	$sqlz=FORM_CONSTRUCT_SQL_FROM_POST("reverse_www","servername");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sqlz[1]);
	echo $sqlz[1];
	if(!$q->ok){echo $sq->mysql_error;}
	
}






