<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}

if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');

$unix=new unix();
$NICE=$unix->EXEC_NICE();
$GetCachesInsquidConf=$unix->SQUID_CACHE_FROM_SQUIDCONF();
$chown=$unix->find_program("chown");
$chmod=$unix->find_program("chmod");
$t=time();
$c=0;
while (list ($CacheDirectory, $type) = each ($GetCachesInsquidConf)){
	if(trim($CacheDirectory)==null){continue;}
	if(!is_dir($CacheDirectory)){continue;}
	$c++;
	$F[]=$CacheDirectory;
	shell_exec("$NICE $chown -R squid:squid $CacheDirectory");
	shell_exec("$NICE $chmod -R 0755 $CacheDirectory");
}
if($c>0){
	$took=distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "Privileges was reset on $c caches directories took $took", @implode("\n",$F),__FILE__,__LINE__);
}