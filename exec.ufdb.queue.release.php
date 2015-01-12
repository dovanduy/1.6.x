<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



xstart();





function xstart(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$dbfile="/var/log/squid/ufdbgclient.unlock.db";
	$pid=@file_get_contents($pidfile);
	
	
	if($GLOBALS["VERBOSE"]){
		echo "$pidtime\n";
	}
	
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		events("Already executed pid $pid since {$time}mn-> DIE");
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid since {$time}mn\n";}
		die();
	}
	$mypid=getmypid();
	@file_put_contents($pidfile,$mypid);
	
	$timefile=$unix->file_time_min($pidtime);
	if($GLOBALS["VERBOSE"]){echo "Timelock:$pidtime $timefile Mn\n";}
	
	if(!$GLOBALS["FORCE"]){
		if($timefile<5){
			if($GLOBALS["VERBOSE"]){echo "{$timefile}mn require 5mn\n";}
			return;
		}
	}
	
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	$q=new mysql_squid_builder();
	
	$sock=new sockets();
	$EnableUfdbGuardArtica=$sock->EnableUfdbGuardArtica();
	
	
	$Count=$q->COUNT_ROWS("ufdbunlock");
	if($Count==0){
		if($GLOBALS["VERBOSE"]){echo "ufdbunlock = 0 rows\n";}
		@unlink($dbfile);
		if($EnableUfdbGuardArtica==0){
			if($GLOBALS["FORCE"]){
				squid_admin_mysql(2, "Reconfigure Proxy service in order to release blocked {$_GET["reconfigure-unlock"]} website(s)", null,__FILE__,__LINE__);
				system("/etc/init.d/squid reload --script=exec.ufdb.queue.release.php/".__LINE__);
			}
		}
		die();
	}
	
	if($EnableUfdbGuardArtica==1){
		squid_admin_mysql(2, "Reconfigure ufdbgclient  service in order to re-block blocked $count3 websites", null,__FILE__,__LINE__);
		unlock_ufdbguard_artica();
		return;
	
	}
	
	$q->QUERY_SQL("DELETE FROM ufdbunlock WHERE finaltime <".time());
	$Count2=$q->COUNT_ROWS("ufdbunlock");
	
	if($Count==$Count2){
		if($Count2==0){@unlink($dbfile);}
		if($GLOBALS["VERBOSE"]){echo "***** NOTHING ******\n";}
		if($GLOBALS["FORCE"]){
			squid_admin_mysql(2, "Reconfigure Proxy service in order to release blocked {$_GET["reconfigure-unlock"]} website(s)", null,__FILE__,__LINE__);
			system("/etc/init.d/squid reload --script=exec.ufdb.queue.release.php/".__LINE__);
		}
		return;
	}
	
	$count3=$Count-$Count2;
	
	
	

	
	$unix=new unix();
	$squid=$unix->LOCATE_SQUID_BIN();
	squid_admin_mysql(2, "Reconfigure Proxy service in order to re-block blocked $count3 websites", null,__FILE__,__LINE__);
	system("/etc/init.d/squid reload --script=exec.ufdb.queue.release.php/".__LINE__);
	
	$sock=new sockets();
	$EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
	if($EnableTransparent27==1){
		system("/etc/init.d/squid-nat reload --script=".basename(__FILE__));
	}
	
}

function unlock_ufdbguard_artica(){
	$dbfile="/var/log/squid/ufdbgclient.unlock.db";
	$unix=new unix();
	
	
	
	
	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM ufdbunlock WHERE finaltime > ".time();
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	@unlink($dbfile);
	
	try {
		echo "berekley_db:: Creating $dbfile database\n";
		$db_desttmp = @dba_open($dbfile, "c","db4");
				@dba_close($db_desttmp);
	}
		catch (Exception $e) {
		$error=$e->getMessage();
		echo "berekley_db::FATAL ERROR $error on $dbfile\n";
		return;
	}
	$db_con = @dba_open($dbfile, "c","db4");
	
	echo mysql_num_rows($results)." Rows... $sql\n";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md5=$ligne["md5"];
		$finaltime=$ligne["finaltime"];
		$uid=$ligne["uid"];
		$ipaddr=$ligne["ipaddr"];
		$www=$ligne["www"];
		if($GLOBALS["VERBOSE"]){echo "***** $md5 $www $ipaddr******\n";}
		
		$array["finaltime"]=$finaltime;
		$array["uid"]=$uid;
		$array["ipaddr"]=$ipaddr;
		$array["www"]=$www;
		
		@dba_replace($md5,serialize($array),$db_con);
		
		
		
	}
	@dba_close($db_con);
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.ufdbclient.reload.php");
}
