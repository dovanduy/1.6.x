<?php
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--simple"){execute_mysql($argv[2]);exit;}


execute_mysql(0);


function execute_mysql($OnlyID=0){
	$GLOBALS["INDEXED"]=0;
	$GLOBALS["SKIPPED"]=0;	
	$GLOBALS["DIRS"]=array();
	$unix=new unix();
	$httrack=$unix->find_program("httrack");
	if(!is_file($httrack)){system_admin_events("httrack no such binary",__FUNCTION__,__FILE__,__LINE__,"webcopy");return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){system_admin_events("Already instance executed pid:$olpid",__FUNCTION__,__FILE__,__LINE__,"webcopy");return;}
	
	$getmypid=getmypid();
	@file_put_contents($pidfile, $getmypid);		
	$q=new mysql();
	$nice=EXEC_NICE();
	$sql="SELECT * FROM httrack_sites WHERE enabled=1";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){system_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"webcopy");return;}
	$t1=time();
	$count=0;
	
	if($OnlyID>0){
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT sitename FROM httrack_sites WHERE ID=$OnlyID","artica_backup"));
		$log_exp=" only for [{$ligne2["sitename"]}] ";
	}
	
	system_admin_events("Starting executing WebCopy task $log_exp pid:$getmypid",__FUNCTION__,__FILE__,__LINE__,"webcopy");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		if($OnlyID>0){if($ligne["ID"]<>$OnlyID){continue;}}
		$t=time();	
		$count++;
		$workingdir=$ligne["workingdir"];
		$sitename=$ligne["sitename"];
		$maxsize=$ligne["maxsize"];
		$minrate=$ligne["minrate"];
		$maxfilesize=$ligne["maxfilesize"];
		$maxsitesize=$ligne["maxsitesize"];
		$maxfilesize=$maxfilesize*1000;
		$maxsitesize=$maxsitesize*1000;
		$minrate=$minrate*1000;
		$update=null;
		$resultsCMD=array();
		if(!is_dir($workingdir)){@mkdir($workingdir,0755,true);}
		if(is_file("$workingdir/hts-cache")){$update=" --update";}
		$cmdline="$httrack \"$sitename\" --quiet$update --max-files=$maxfilesize --max-size=$maxsitesize --max-rate=$minrate -O \"$workingdir\" 2>&1";
		if($GLOBALS["VERBOSE"]){echo"$cmdline\n";}
		exec($cmdline,$resultsCMD);
		if($GLOBALS["VERBOSE"]){echo @implode("\n", $resultsCMD);}
		$dirsize=$unix->DIRSIZE_BYTES($workingdir);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		$dirsizeText=round((($dirsize/1024)/1000),2);
		system_admin_events("$sitename scrapped took $took size=$dirsizeText MB",__FUNCTION__,__FILE__,__LINE__,"webcopy");
		$q->QUERY_SQL("UPDATE httrack_sites SET size='$dirsize' WHERE ID={$ligne["ID"]}","artica_backup");
		
	}
	$took=$unix->distanceOfTimeInWords($t1,time(),true);
	system_admin_events("$count web sites scrapped took $took",__FUNCTION__,__FILE__,__LINE__,"webcopy");
}

