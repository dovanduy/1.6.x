<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--restart#",implode(" ",$argv))){$GLOBALS["RESTART"]=true;}


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');

if($argv[1]=="--init"){init();die();}

startx();

function init(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	
	shell_exec("$rm -rf /var/lib/squid/session/ssl >/dev/null 2>&1");
	@mkdir("/var/lib/squid/session/ssl",0755,true);
	@chown("/var/lib/squid/session/ssl","squid");
	@chgrp("/var/lib/squid/session/ssl", "squid");
	$sslcrtd_program=$unix->squid_locate_generic_bin("ssl_crtd");
	$chown=$unix->find_program("chown");
	exec("$sslcrtd_program -c -s /var/lib/squid/session/ssl/ssl_db 2>&1",$results);
	shell_exec("$chown -R squid:squid /var/lib/squid/session");
	squid_admin_mysql(1, "SSL database initialized", @implode("\n", $results),__FILE__,__LINE__);
	
}

function build_progress($text,$pourc){



	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.build.progress";

	if($GLOBALS["AD_PROGRESS"]>0){

		$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/AdConnnection.status"));
		$array["PRC"]=$GLOBALS["AD_PROGRESS"];
		$array["TITLE"]=$text;
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile, 0755);
		return;
	}

	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function startx(){
	
	build_progress("{rebuild_ssl_cache}: Removing SSL cache",10);
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$chown=$unix->find_program("chown");
	
	if($GLOBALS["RESTART"]){
		build_progress("{rebuild_ssl_cache}: {stopping_proxy_service}",30);
		system("/etc/init.d/squid stop --script=".basename(__FILE__));
		
	}
	
	echo "Remove /var/lib/squid/session/ssl/ssl_db\n";
	shell_exec("$rm -rf /var/lib/squid/session/ssl/ssl_db");
	build_progress("{rebuild_ssl_cache}: Reconstruct SSL cache",50);
	$sslcrtd_program=$unix->squid_locate_generic_bin("ssl_crtd");
	system("$sslcrtd_program -c -s /var/lib/squid/session/ssl/ssl_db");
	system("$chown -R squid:squid /var/lib/squid/session");
	build_progress("{rebuild_ssl_cache}: Reload Proxy service",60);
	system("/etc/init.d/cache-tail restart --force");
	
	if($GLOBALS["RESTART"]){
		build_progress("{rebuild_ssl_cache}: {starting_proxy_service}",30);
		system("/etc/init.d/squid start --script=".basename(__FILE__));
		build_progress("{rebuild_ssl_cache}: {done}",100);
		return;
	}

	build_progress("{rebuild_ssl_cache}: Reload Proxy service",70);
	system("/etc/init.d/squid reload --force --script=".basename(__FILE__));
	build_progress("{rebuild_ssl_cache}: {done}",100);
}


