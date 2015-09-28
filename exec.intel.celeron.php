#!/usr/bin/php -q
<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



xstart();

function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.optimize.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}


function xstart(){
$sock=new sockets();
$unix=new unix();
$ProxyUseArticaDB=$sock->GET_INFO("ProxyUseArticaDB");
$EnableIRQBalance=intval($sock->GET_INFO("EnableIRQBalance"));
$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
$php=$unix->LOCATE_PHP5_BIN();

if($EnableIRQBalance==0){
	build_progress("{stopping} IRQ Balance...",3);
	system("/etc/init.d/irqbalance stop");
}else{
	build_progress("{starting} IRQ Balance...",3);
	system("/etc/init.d/irqbalance start");
}

build_progress("{restarting} Status...",4);
system("/etc/init.d/artica-status restart --force");


if($EnableIntelCeleron==0){
	build_progress("{reconfiguring_proxy_service}",50);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reconfiguring_web_interface}",90);
	$sock->SET_INFO("EnableSquidGuardHTTPToArtica", 0);
	system("$php /usr/share/artica-postfix/exec.lighttpd.php --apache-build");
	echo "EnableIntelCeleron: 0 -> Stop procedure\n";
	build_progress("{done}...",100);
	sleep(5);
	system("/etc/init.d/artica-webconsole restart");
	system("/etc/init.d/squidguard-http restart");
	return;
}

$sock->SET_INFO("EnableSquidGuardHTTPToArtica", 0);

if($ProxyUseArticaDB==1){
	build_progress("{remove_dedicated_database}",5);
	if(!transfert_tomysql()){
		build_progress("{failed}",110);
		return;
	}
	
	$sock->SET_INFO("ProxyUseArticaDB", 0);
	build_progress("{stopping_mysql_service}",25);
	system("/etc/init.d/squid-db stop");
	
}


$php5=$unix->LOCATE_PHP5_BIN();
$sock->SET_INFO("EnableISCSI",0);
$sock->SET_INFO("EnableMemcached",0);




build_progress("{stopping} ISCSI...",30);
system("/etc/init.d/iscsitarget stop");
system("/etc/init.d/open-iscsi stop");
build_progress("{stopping} Memcached...",35);
system("/etc/init.d/artica-memcache stop");
$sock->SET_INFO("EnableMemcached",0);
$sock->SET_INFO("EnableSquidGuardHTTPToArtica",1);
system("/etc/init.d/squidguard-http stop");
$sock->SET_INFO("EnableArticaFrontEndToNGninx",0);
$sock->SET_INFO("EnableArticaFrontEndToApache",1);
$sock->SET_INFO("EnableSquidGuardHTTPToArtica", 1);
build_progress("{reconfiguring_web_interface}",40);
system("$php5 /usr/share/artica-postfix/exec.lighttpd.php --apache-build");

$sock->SET_INFO("CicapEnabled", 0);
$q=new mysql_squid_builder();
$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");


$sock->SET_INFO("HyperCacheStoreID",0);
build_progress("{reconfiguring_proxy_service}",45);
system("/etc/init.d/artica-status restart --force");
system("/etc/init.d/c-icap stop");
system("/etc/init.d/clamav-daemon stop");
system("/etc/init.d/slapd stop");
system("/etc/init.d/apache2 stop");
build_progress("{reconfiguring_proxy_service}",46);

build_progress("{reconfiguring_proxy_service}",47);
system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
build_progress("{limit_artica_processes}",50);
system("$php5 /usr/share/artica-postfix/exec.cgroups.php --start");
build_progress("{done}",100);
sleep(5);
system("/etc/init.d/artica-webconsole restart");
system("/etc/init.d/squidguard-http restart");

}

function transfert_tomysql(){
	$q=new mysql_squid_builder();
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$mysql=$unix->find_program("mysql");
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysqldump --add-drop-table --force --single-transaction --insert-ignore -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");
	
	build_progress("{exporting}",10);
	shell_exec("$prefix >/home/toMysql.sql");
	
	
	$q=new mysql();
	build_progress("{creating_database}",15);
	$q->CREATE_DATABASE("squidlogs");
	if(!$q->ok){
		build_progress("{creating_database} {failed}",15);
		return;
	}
	
	system("$mysql -e \"CREATE DATABASE IF NOT EXISTS squidlogs\"");
	
	
	$MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
	$MYSQL_CMDLINES="$mysql $MYSQL_CMDLINES squidlogs < /home/toMysql.sql";
	build_progress("{importing}",20);
	system($MYSQL_CMDLINES);
	return true;
	
	
	
}
