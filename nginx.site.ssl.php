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
	
	$page=CurrentPageName();
	$tpl=new templates();
	$squid_reverse=new squid_reverse();
	$sslcertificates=$squid_reverse->ssl_certificates_list();
	$you_need_to_compile=$tpl->javascript_parse_text("{you_need_to_compile}");
	$servername=$_GET["servername"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE servername='$servername'"));
	if(!is_numeric($ligne["ssl_backend_port"])){$ligne["ssl_backend_port"]=443;}
	$t=time();
	$ssl_use_rcert=0;
	$to=null;
	$AS_PEER_CERTIFICATE=0;
	$AS_PEER_CERTIFICATE_EXPLAIN=null;
	$cache_peer_id=$ligne["cache_peer_id"];
	
	if($cache_peer_id>0){
		if(!$q->FIELD_EXISTS("reverse_sources", "ssl_remotecert")){
			$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ssl_remotecert` smallint(1) NOT NULL DEFAULT '0'");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT servername,ssl_remotecert FROM reverse_sources WHERE ID='$cache_peer_id'"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$to=" {to} &laquo;{$ligne2["servername"]}&raquo;";
	}
	
	
	if(intval($cache_peer_id)>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT certificate FROM reverse_sources WHERE ID='$cache_peer_id'"));
		if(trim($ligne2["certificate"]<>null)){
			$ligne["certificate"]=$ligne2["certificate"];
			$AS_PEER_CERTIFICATE=1;
		}
	}
	
	if($AS_PEER_CERTIFICATE==1){
		$AS_PEER_CERTIFICATE_EXPLAIN="<div class=text-info>{reverse_proxy_use_destination_server_certificate}</div>";
		
	}
	
	$html[]="<div style='width:98%' class=form>$AS_PEER_CERTIFICATE_EXPLAIN";
	$html[]="<table style='width:100%'>";
	$html[]="<tr><td colspan=2 style='font-size:28px;padding-bottom:20px'>{port}:{$ligne["port"]} &laquo;$servername&raquo;$to</td></tr>";
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{reverse_proxy_ssl}", "{NGINX_USE_SSL_EXPLAIN}",
			"ssl-$t",$ligne["ssl"],null,700,"SwitchOffCertificate$t")."</td></tr>";
	
	$html[]=Field_list_table("certificate-$t","{certificate}",$ligne["certificate"],22,$sslcertificates,null,450);
	
	if($cache_peer_id>0){
		
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{destination_use_ssl}", "{NGINX_USE_SSL_EXPLAIN2}",
				"ssl_backend-$t",$ligne["ssl_backend"],null,700)."</td></tr>";		
		
	$html[]="<tr><td colspan=2>". Paragraphe_switch_img("{SSL_CLIENT_VERIFICATION}", "{SSL_CLIENT_VERIFICATION_EXPLAIN}",
			"ssl_client_certificate-$t",$ligne["ssl_client_certificate"],null,700)."</td></tr>";
	
	
	
	
	}
	
	
	$html[]=Field_button_table_autonome("{apply}","Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#NGINX_MAIN_TABLE').flexReload();
	alert('$you_need_to_compile');
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	var AS_PEER_CERTIFICATE=$AS_PEER_CERTIFICATE;
	XHR.appendData('servername','$servername');
	XHR.appendData('cache_peer_id','$cache_peer_id');
	XHR.appendData('ssl',document.getElementById('ssl-$t').value);
	if(document.getElementById('ssl_backend-$t')){
		XHR.appendData('ssl_backend',document.getElementById('ssl_backend-$t').value);
	}
	if(document.getElementById('ssl_client_certificate-$t')){
		XHR.appendData('ssl_client_certificate',document.getElementById('ssl_client_certificate-$t').value);
	}	
	
	
	
	XHR.appendData('certificate',document.getElementById('certificate-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}

function SwitchOffCertificate$t(){
	var ssl=document.getElementById('ssl-$t').value;
	
}

function Check$t(){
	var AS_PEER_CERTIFICATE=$AS_PEER_CERTIFICATE;
	if( AS_PEER_CERTIFICATE==1){
		document.getElementById('certificate-$t').disabled=true;
	}
	SwitchOffCertificate$t();
}
Check$t();
</script>
	
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}	

function Save(){
	
	$q=new mysql_squid_builder();
	
	if(isset($_POST["cache_peer_id"])){
		$cache_peer_id=$_POST["cache_peer_id"];
		unset($_POST["cache_peer_id"]);
	}

	
	
	
	$sqlz=FORM_CONSTRUCT_SQL_FROM_POST("reverse_www","servername");
	
	$q->QUERY_SQL($sqlz[1]);
	
	if(!$q->ok){echo $sq->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("nginx.php?compile-single=yes&servername=".urlencode($_POST["servername"]));
	
}






