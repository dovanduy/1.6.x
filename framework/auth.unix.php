<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.tcpip.inc");


$HTTP_CLIENT_IP=$_SERVER["HTTP_CLIENT_IP"];
$HTTP_AUTH_LOGIN_ATTEMPT=$_SERVER["HTTP_AUTH_LOGIN_ATTEMPT"];
$HTTP_AUTH_PROTOCOL=$_SERVER["HTTP_AUTH_PROTOCOL"];
$HTTP_AUTH_USER=$_SERVER["HTTP_AUTH_USER"];
$HTTP_HOST=$_SERVER["HTTP_HOST"];
$HTTP_AUTH_METHOD=$_SERVER["HTTP_AUTH_METHOD"];
$HTTP_AUTH_SMTP_FROM=$_SERVER["HTTP_AUTH_SMTP_FROM"];
$HTTP_AUTH_SMTP_HELO=$_SERVER["HTTP_AUTH_SMTP_HELO"];
$HTTP_AUTH_SMTP_TO=$_SERVER["HTTP_AUTH_SMTP_TO"];

if(preg_match("#.*?:\s+(.+)#", $HTTP_AUTH_SMTP_FROM,$re)){
	$HTTP_AUTH_SMTP_FROM=$re[1];
}
if(preg_match("#.*?:\s+(.+)#", $HTTP_AUTH_SMTP_TO,$re)){
	$HTTP_AUTH_SMTP_TO=$re[1];
}

/*Auth-SMTP-Helo: client.example.org
Auth-SMTP-From: MAIL FROM: <>
Auth-SMTP-To: RCPT TO: <postmaster@mail.example.com>


while (list ($num, $line) = each ($_SERVER)){
	ngx_mail_events("$num  = '$line'",__LINE__);
}
*/

if($HTTP_AUTH_PROTOCOL=="smtp"){
	$HTTP_AUTH_USER=$HTTP_AUTH_SMTP_FROM;
	ngx_mail_events("Receive request from $HTTP_AUTH_SMTP_FROM to $HTTP_AUTH_SMTP_TO",__LINE__);
	
}else{
	ngx_mail_events("Receive request from $HTTP_HOST/$HTTP_AUTH_USER/$HTTP_CLIENT_IP ($HTTP_AUTH_PROTOCOL)",__LINE__);
}



//zmd5,username,ipsrc,protocol,backend,backend_port,enabled,destination
$results=ngx_mail_auth($HTTP_AUTH_USER,$HTTP_CLIENT_IP,$HTTP_AUTH_PROTOCOL,$HTTP_AUTH_SMTP_TO);
if(!$results){
	header("HTTP/1.0 200 OK");
	header("Auth-Status: Invalid login or password");
	header("Auth-Wait: 3");
	die();
}

$backend=$results[0];
$port=$results[1];
$ip=new IP();
if(!$ip->isValid($backend)){
	ngx_mail_events("Resolving $backend",__LINE__);
	$backend=gethostbyname($backend);
	ngx_mail_events("Resolved $backend",__LINE__);
}

if($HTTP_AUTH_PROTOCOL=="imap"){
	ngx_mail_events("[SUCCESS]: $HTTP_AUTH_USER/$HTTP_CLIENT_IP $HTTP_AUTH_PROTOCOL IMAP: $backend:$port ",__LINE__);
	header("HTTP/1.0 200 OK");
	header("Auth-Status: OK");
	header("Auth-Server: $backend");
	header("Auth-Port: $port");
	die();
}





ngx_mail_events("[FAILED]: $HTTP_AUTH_USER/$HTTP_CLIENT_IP $HTTP_AUTH_PROTOCOL ",__LINE__);





function ngx_mail_events($text,$line){
	$unix=new unix();
	$unix->events($text,"/var/log/artica-proxy-mail.log",false,"MAIN",$line);
	
	
}


function QUERY_MYSQL($sql){
	$socket=null;
	$user=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
	$password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
	$mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
	$mysql_port=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/port")));
	
	$ProxyUseArticaDB=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB"));
	$EnableSquidRemoteMySQL=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSquidRemoteMySQL"));
	$socket="/var/run/mysqld/mysqld.sock";

	
	
	if($user==null){$user="root";}
	if($mysql_server==null){$mysql_server="127.0.0.1";}
	if($mysql_server=="localhost"){$mysql_server="127.0.0.1";}
	if($mysql_server=="127.0.0.1"){$socket="/var/run/mysqld/mysqld.sock";}
	
	
	if($ProxyUseArticaDB==1){
		$socket="/var/run/mysqld/squid-db.sock";
	}
	
	if($EnableSquidRemoteMySQL==1){
		$socket=null;
		$mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsServer"));
		$mysql_port=intval(trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPort")));
		$squidRemostatisticsUser=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsUser"));
		$squidRemostatisticsPassword=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/squidRemostatisticsPassword"));
		
	}
	
	if($mysql_port==0){$mysql_port=3306;}
	
	if($socket==null){
		$logcnx="$user@$mysql_server:$mysql_port";
		$bd=@mysql_connect("$mysql_server:$mysql_port",$user,$password);
		
	}else{
		$logcnx="root@$socket";
		$bd=@mysql_connect(":$socket","root",null);
	}
	if(!$bd){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ngx_mail_events("$logcnx MySQL error: $errnum $des",__LINE__);
		return;
	}
	$ok=@mysql_select_db("squidlogs",$bd);
	if(!$ok){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ngx_mail_events("$logcnx MySQL error: $errnum $des",__LINE__);
		@mysql_close($bd);
		return;
	}
	$results=@mysql_query($sql,$bd);
	if(!$results){
		$des=@mysql_error();
		$errnum=@mysql_errno();
		ngx_mail_events("$logcnx MySQL error: $errnum $des",__LINE__);
	}
	return $results;
	@mysql_close($bd);

}
function mysql_escape_string3($line){

	$search=array("\\","\0","\n","\r","\x1a","'",'"');
	$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
	return str_replace($search,$replace,$line);
}

function ngx_mail_auth($username,$ip,$proto,$rcptto){
	
	
	$HTTP_CLIENT_IP_NET=explode(".",$ip);
	$HTTP_CLIENT_NET="{$HTTP_CLIENT_IP_NET[0]}.{$HTTP_CLIENT_IP_NET[1]}.{$HTTP_CLIENT_IP_NET[1]}.0";
	
	$username=mysql_escape_string3($username);
	$sql="SELECT backend,backend_port FROM reverse_mailauth WHERE
			username='$username'
				AND ipsrc='$ip'
				AND protocol='$proto'
				AND enabled=1";
	
	$ligne=mysql_fetch_array(QUERY_MYSQL($sql));
	if($ligne["backend"]<>null){ return array($ligne["backend"],$ligne["backend_port"]); }
	
	ngx_mail_events("[NONE]: $username $ip $proto ",__LINE__);
	
	
	$sql="SELECT backend,backend_port FROM reverse_mailauth WHERE
	username='$username'
	AND ipsrc='$HTTP_CLIENT_NET'
	AND protocol='$proto'
	AND enabled=1";
	
	$ligne=mysql_fetch_array(QUERY_MYSQL($sql));
	if($ligne["backend"]<>null){ return array($ligne["backend"],$ligne["backend_port"]); }	
	
	ngx_mail_events("[NONE]: $username $HTTP_CLIENT_NET $proto ",__LINE__);
	
	$sql="SELECT backend,backend_port FROM reverse_mailauth WHERE
	username='$username'
	AND protocol='$proto'
	AND enabled=1";
	
	$ligne=mysql_fetch_array(QUERY_MYSQL($sql));
	if($ligne["backend"]<>null){ return array($ligne["backend"],$ligne["backend_port"]); }	
	
	ngx_mail_events("[FAILED]: $username $proto ",__LINE__);
	
	return false;
}

