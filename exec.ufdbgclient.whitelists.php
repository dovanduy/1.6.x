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

build_whitelist();

function build_whitelist(){
	
	build_progress_wb("{compiling}",30);
	urlrewriteaccessdeny();
	build_progress_wb("{compiling}",35);
	urlrewriteaccessdeny_squid();
	build_progress_wb("{compiling}",40);
	build_blacklists();
	build_progress_wb("{done}",100);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.ufdbclient.reload.php");
}

function build_blacklists($aspid=false){
	$unix=new unix();
	$FINALARRAY=array();
	$f=array();
	$PidFile="/etc/artica-postfix/pids/squid_build_blacklists.pid";
	$dbfile="/var/log/squid/ufdbgclient.black.db";
	if($aspid){
		$pid=$unix->get_pid_from_file($PidFile);
		if($pid<>getmypid()){
			if($unix->process_exists($pid,basename(__FILE__))){
				echo "Starting......: ".date("H:i:s")." Blacklists: Another artica script running pid $pid, aborting ...\n";
				WriteToSyslogMail("build_blacklists():: Another artica script running pid $pid, aborting ...", basename(__FILE__));
				return;
			}
		}
	}
	
	
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
	


	$q=new mysql_squid_builder();
	$array=array();
	$db_con = @dba_open($dbfile, "c","db4");
	$sql="SELECT * FROM deny_websites";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){ echo "Starting......: ".date("H:i:s")." [ACLS]: $q->mysql_error\n"; return; }
	@unlink("/etc/squid3/www-blacklists.db");
	while ($ligne = mysql_fetch_assoc($results)) {
		if($ligne["items"]==null){continue;}
		$item=$ligne["items"];
		$item=str_replace("/", "\/", $item);
		$item=str_replace(".", "\.", $item);
		$item=str_replace("*", ".*?", $item);
		@dba_replace($item,$item,$db_con);
		$array[]=$ligne["items"];

	}
	@dba_close($db_con);
	
	@file_put_contents("/var/log/squid/ufdbgclient.reload", "#");
	@chown("/var/log/squid/ufdbgclient.reload", "squid");
	@chgrp("/var/log/squid/ufdbgclient.reload","squid");
	
	
	$acl=new squid_acls();
	$url_rewrite_program=$acl->clean_dstdomains($array);




	echo "Starting......: ".date("H:i:s")." [ACLS]: ".count($url_rewrite_program)." blacklisted webistes\n";
	@file_put_contents("/etc/squid3/www-blacklists.db", @implode("\n", $url_rewrite_program)."\n");
	@chown("/etc/squid3/www-blacklists.db", "squid");
	@chgrp("/etc/squid3/www-blacklists.db","squid");


}


function urlrewriteaccessdeny(){
	$q=new mysql();
	$dbfile="/var/log/squid/ufdbgclient.white.db";

	$sql="SELECT * FROM urlrewriteaccessdeny";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." [ACLS]: $q->mysql_error\n";return; }
	
	@unlink($dbfile);
	if(!is_file($dbfile)){
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
	
	
	}
	
	$db_con = @dba_open($dbfile, "c","db4");
	if(!$db_con){
		echo "berekley_db_size:: FATAL!!!::$dbfile, unable to open\n";
		return false;
	}

	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["items"]=trim($ligne["items"]);
		if($ligne["items"]==null){continue;}
		$c++;
		echo "Starting......: ".date("H:i:s")." [ACLS]: {$ligne["items"]}\n";
		$ligne["items"]=str_replace("/", "\/", $ligne["items"]);
		$ligne["items"]=str_replace(".", "\.", $ligne["items"]);
		$ligne["items"]=str_replace("*", ".*?", $ligne["items"]);
		@dba_replace($ligne["items"],$ligne["items"],$db_con);
		
	}

	
	@dba_close($db_con);
	if($c==0){@unlink($dbfile);}


	echo "Starting......: ".date("H:i:s")." [ACLS]: $c Whitelisted webistes from webfiltering\n";
	@chown($dbfile, "squid");
	@chgrp($dbfile,"squid");
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.ufdbclient.reload.php");

}

function urlrewriteaccessdeny_squid(){
	$q=new mysql();
	$q2=new mysql_squid_builder();
	$acl=new squid_acls();
	$sql="SELECT * FROM urlrewriteaccessdeny";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: ".date("H:i:s")." [ACLS]: $q->mysql_error\n";return; }


	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["items"]=trim($ligne["items"]);
		if($ligne["items"]==null){continue;}
		$array[]=$ligne["items"];
	}

	$acl=new squid_acls();
	$url_rewrite_program=$acl->clean_dstdomains($array);



	echo "Starting......: ".date("H:i:s")." [ACLS]: ".count($url_rewrite_program)." Whitelisted webistes from webfiltering\n";
	@file_put_contents("/etc/squid3/url_rewrite_program.deny.db", @implode("\n", $url_rewrite_program)."\n");
	@chown("/etc/squid3/url_rewrite_program.deny.db", "squid");
	@chgrp("/etc/squid3/url_rewrite_program.deny.db","squid");

}

function build_progress_wb($text,$pourc){

	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.wb.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}
}