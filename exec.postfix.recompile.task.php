<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');


$unix=new unix();
$users=new usersMenus();
if(!$users->POSTFIX_INSTALLED){die();}


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){
	system_admin_events("Already instance executed, aborting\n".@implode("\n", $results), "MAIN", __FILE__,"postfix");
	die();}
$pid=getmypid();
file_put_contents($pidfile,$pid);




$php5=$unix->LOCATE_PHP5_BIN();

$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}


if($EnablePostfixMultiInstance==0){
	$t=time();
	exec("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("{reconfigure} postfix done took $took\n".@implode("\n", $results), "MAIN", __FILE__,
	 __LINE__, "postfix");
}
if($EnablePostfixMultiInstance==1){
	
	$sql="SELECT ou, ip_address, `key` , `value` FROM postfix_multi WHERE (`key` = 'myhostname')";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){system_admin_events( "Fatal, $q->mysql_error\n","MAIN", __FILE__,__LINE__, "postfix");return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$hostname=$ligne["value"];
		if(strlen($hostname)<4){continue;}
		$t=time();
		$ttA=array();
		exec("$php5 /usr/share/artica-postfix/exec.postfix-multi.php --instance-reconfigure \"$hostname\" 2>&1",$ttA);
		$took=$unix->distanceOfTimeInWords($t,time(),true);	 
		system_admin_events("{reconfigure} postfix $hostname instance done took $took\n".@implode("\n", $ttA), "MAIN", __FILE__,__LINE__, "postfix");
	}	
	
}

