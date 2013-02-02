<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.sockets.inc');
include_once(dirname(__FILE__) . '/ressources/class.netagent.inc');



$sock=new sockets();
$EnableDNSLinker=$sock->GET_INFO("EnableDNSLinker");
$EnableDNSLinkerCreds=base64_decode($sock->GET_INFO("EnableDNSLinkerCreds"));
if(!is_numeric($EnableDNSLinker)){$EnableDNSLinker=0;}
if($EnableDNSLinker==0){die();}
$EnableDNSLinkerCreds=unserialize(base64_decode($sock->GET_INFO("EnableDNSLinkerCreds")));
if(preg_match("#^(.+?):#", $EnableDNSLinkerCreds["CREDS"],$re)){$SuperAdmin=$re[1];}
$hostname=$EnableDNSLinkerCreds["hostname"];
$listen_port=$EnableDNSLinkerCreds["listen_port"];
$listen_addr=$EnableDNSLinkerCreds["listen_addr"];
$send_listen_ip=$EnableDNSLinkerCreds["send_listen_ip"];
if(!is_numeric($listen_port)){$listen_port=9000;}

$curl=new ccurl("https://$hostname:$listen_port/nodes.listener.php?PING=YES");
if($send_listen_ip<>null){$curl->interface=$send_listen_ip;}
$curlparms["listen_addr"]=$listen_addr;

$curl=new ccurl("https://$hostname:$listen_port/nodes.listener.php");
if($send_listen_ip<>null){$curl->interface=$send_listen_ip;}
$curlparms["listen_addr"]=$listen_addr;
$unix=new unix();
$curlparms["hostname"]=$unix->hostname_g();

$sql="SELECT servername FROM freeweb";
$q=new mysql();
$results=$q->QUERY_SQL($sql,'artica_backup');
while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
	$curlparms["FREEWEBS_SRV"][$ligne["servername"]]=true;
	
}

@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/com.txt", base64_encode(serialize($curlparms)));
$net=new netagent();
$net->compress("/usr/share/artica-postfix/ressources/logs/web/com.txt","/usr/share/artica-postfix/ressources/logs/web/com.txt.gz");
@unlink("/usr/share/artica-postfix/ressources/logs/web/com.txt");
$curl->x_www_form_urlencoded=true;
if(!$curl->postFile("DNS_LINKER","/usr/share/artica-postfix/ressources/logs/web/com.txt.gz",array("CREDS"=>$EnableDNSLinkerCreds["CREDS"],"VERBOSE"=>"TRUE"))){
	echo "Posting informations Failed $curl->error...\n";
	@unlink("/usr/share/artica-postfix/ressources/logs/web/com.txt.gz");
}

if($GLOBALS["VERBOSE"]){echo $curl->data."\n";}
