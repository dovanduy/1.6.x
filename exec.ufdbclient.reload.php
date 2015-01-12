<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;

ReloadMacHelpers();

function ReloadMacHelpers($output=false){

	@mkdir("/var/log/squid/reload",0755,true);
	$unix=new unix();
	$pgrep=$unix->find_program("pgrep");
	$rm=$unix->find_program("rm");
	shell_exec("$rm /var/log/squid/reload/*.ufdbgclient.php");
	if(is_file("/var/log/squid/UfdbguardCache.db")){@unlink("/var/log/squid/UfdbguardCache.db"); }

	exec("$pgrep -l -f \"ufdbgclient.php\" 2>&1",$results);

	while (list ($index, $ligne) = each ($results) ){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		echo "Starting......: ".date("H:i:s")." [INIT]: Webfilter client reloading PID {$re[1]}\n";
		@touch("/var/log/squid/reload/{$re[1]}.ufdbgclient.php");
		@chown("/var/log/squid/reload/{$re[1]}.ufdbgclient.php","squid");
		@chgrp("/var/log/squid/reload/{$re[1]}.ufdbgclient.php", "squid");

	}
}