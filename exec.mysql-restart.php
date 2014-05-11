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
	system_admin_events("Starting......: ".date("H:i:s")."Already executed PID $pid...", __FUNCTION__, __FILE__, __LINE__, "mysql");
	die();
}
@file_put_contents($pidfile, getmypid());

$t=time();
$unix=new unix();
exec("/etc/init.d/mysql restart 2>&1",$results);
$took=$unix->distanceOfTimeInWords($t,time());
system_admin_events("Restarting MySQL service done took $took:\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "mysql");

