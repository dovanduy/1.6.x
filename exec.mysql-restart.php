<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
$unix=new unix();
if($unix->process_exists($pid,(basename(__FILE__)))){
	system_admin_events("Starting......:Already executed PID $pid...", __FUNCTION__, __FILE__, __LINE__, "mysql");
	die();
}
@file_put_contents($pidfile, getmypid());

$t=time();
$unix=new unix();
$mysql_server_script=mysql_server_script();
if(!is_file($mysql_server_script)){
	$results[]="Unable to stat mysql.server script, trying with Artica script";
	exec("/etc/init.artica-postfix restart mysql 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
system_admin_events("Restarting MySQL service done took $took:\n".@implode("\n", $results)."\n".
@file_get_contents("/var/log/mysql/mysql.start.log")
, __FUNCTION__, __FILE__, __LINE__, "mysql");
	die();
} 
exec("$mysql_server_script stop 2>&1",$results);
exec("/etc/init.artica-postfix start mysql 2>&1",$results);



system_admin_events("Restarting MySQL service done took $took:\n".@implode("\n", $results)."\n".
@file_get_contents("/var/log/mysql/mysql.start.log")
, __FUNCTION__, __FILE__, __LINE__, "mysql");




function mysql_server_script(){
	
	$f[]="/usr/share/mysql/mysql.server";
	$f[]="/usr/local/mysql/support-files/mysql.server";
	while (list ($num, $ligne) = each ($f) ){
		if(is_file($ligne)){return $ligne;}
		
	}
}