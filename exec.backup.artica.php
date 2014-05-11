<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["COMMANDLINE"]=@implode(" ", $argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--restore"){restore();die();}

execBackup();

function TestNas(){
	$sock=new sockets();
	$unix=new unix();
	$hostname=$unix->hostname_g();
	$BackupArticaBackUseNas=intval($sock->GET_INFO("BackupArticaBackUseNas"));
	if($BackupArticaBackUseNas==0){if($GLOBALS["VERBOSE"]){echo "BackupArticaBackUseNas = $BackupArticaBackUseNas\n";} return false;}
	$BackupArticaBackNASIpaddr=$sock->GET_INFO("BackupArticaBackNASIpaddr");
	$BackupArticaBackNASFolder=$sock->GET_INFO("BackupArticaBackNASFolder");
	$BackupArticaBackNASUser=$sock->GET_INFO("BackupArticaBackNASUser");
	$BackupArticaBackNASPassword=$sock->GET_INFO("BackupArticaBackNASPassword");
	
	
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaBackNAS";
	
	if($mount->ismounted($mountPoint)){ return true; }
	
	
	
	if(!$mount->smb_mount($mountPoint,$BackupArticaBackNASIpaddr,$BackupArticaBackNASUser,$BackupArticaBackNASPassword,$BackupArticaBackNASFolder)){
		system_admin_events("Mounting //$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		return false;
				
	}
	
	if(!$mount->ismounted($mountPoint)){
		return false;
	}
	
	$t=time();
	@file_put_contents("$mountPoint/$t", "#");
	if(!is_file("$mountPoint/$t")){
		system_admin_events("$BackupArticaBackNASUser@$BackupArticaBackNASIpaddr/$BackupArticaBackNASFolder/* permission denied.\n",__FUNCTION__,__FILE__,__LINE__);
		$mount->umount($mountPoint);
		return false;
	}
	@unlink("$mountPoint/$t");	
	@mkdir("$mountPoint/$hostname/system-backup",0755,true);
	return true;
}

function execBackup(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid)){
		$TTL=$unix->PROCESS_TTL($oldpid);
		if($TTL<240){return;}
		$kill=$unix->find_program("kill");
		shell_exec("$kill -9 $oldpid 2>&1");
	}
	
	@file_put_contents($pidfile, getmypid());
	$hostname=$unix->hostname_g();
	
	
	progress(10,"{mounting}");
	if(!TestNas()){
		system_admin_events("Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
		progress(100,"{disabled}");
		return;
		
	}
	
	$t=time();
	$mountPoint="/mnt/BackupArticaBackNAS";
	$Workdir="$mountPoint/$hostname/system-backup/".date("Y-m-d-H");
	@mkdir($Workdir,0755,true);
	progress(20,"{backup_ldap}");
	backup_ldap($Workdir);
	progress(30,"{backup_artica_settings}");
	backup_artica_settings($Workdir);
	progress(60,"{backup_artica_database}");
	backup_mysql_artica_backup($Workdir);
	progress(70,"{backup_proxy_databases}");
	backup_mysql_artica_squidlogs($Workdir);
	@file_put_contents("$Workdir/BKVERSION.txt", time());
	@file_put_contents("$Workdir/DURATION.txt", $unix->distanceOfTimeInWords($t,time(),true));
	progress(90,"{backup_done}");
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaBackNAS";
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	progress(100,"{done}");
}


function backup_ldap($Workdir){
	$unix=new unix();
	$slapcat=$unix->find_program("slapcat");
	$gzip=$unix->find_program("gzip");
	$nice=$unix->EXEC_NICE();
	if(!is_file($slapcat)){ system_admin_events("Error, slapcat, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }	
	$cmd=trim("$nice $slapcat|$gzip >$Workdir/ldap_database.gz 2>&1");
	exec($cmd,$results);
	
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap_database.gz");
	$size=$size/1024;
	$size=round($size/1024,2);
	system_admin_events("ldap_database.gz ({$size}M)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
	
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$results=array();
	$cmd=trim("$nice $gzip -c $SLAPD_CONF > /$Workdir/ldap.conf.gz 2>&1");
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n".@implode("\n", $results)."\n";}
	
	$size=filesize("$Workdir/ldap.conf.gz");
	$size=$size/1024;
	$size=round($size,2);
	system_admin_events("ldap.conf.gz ({$size}K)\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
}

function backup_artica_settings($BaseWorkDir){
	$BLACKLIST["x86info_mhz"]=true;
	$BLACKLIST["WizardSavedSettings"]=true;
	$BLACKLIST["WifiCardOk"]=true;
	$BLACKLIST["SystemCpuNumber"]=true;
	$BLACKLIST["HOSTID"]=true;
	$BLACKLIST["PublicIPAddress"]=true;
	$BLACKLIST["WizardSavedSettingsSend"]=true;
	$BLACKLIST["LicenseInfos"]=true;
	$BLACKLIST["LinuxDistribution"]=true;
	$BLACKLIST["LinuxDistributionName"]=true;
	$BLACKLIST["myhostname"]=true;
	$BLACKLIST["WgetBindIpAddress"]=true;
	$BLACKLIST["SystemTotalSize"]=true;
	$BLACKLIST["SYSTEMID"]=true;
	$BLACKLIST["SoftwaresListCached"]=true;
	
	if (!$handle = opendir("/etc/artica-postfix/settings/Daemons")) {echo "Failed open /etc/artica-postfix/settings/Daemons\n";return;}
	@mkdir("$BaseWorkDir/Daemons",0755,true);
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(is_dir($targetFile)){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		if(is_file("$BaseWorkDir/Daemons/$filename")){@unlink("$BaseWorkDir/Daemons/$filename");}
		if($GLOBALS["VERBOSE"]){echo "$targetFile -> $BaseWorkDir/Daemons/$filename\n";}
		copy($targetFile, "$BaseWorkDir/Daemons/$filename");
	}
	
	system_admin_events("settings/Daemons done\n",__FUNCTION__,__FILE__,__LINE__);
	
}

function backup_mysql_artica_backup($BaseWorkDir){
	$unix=new unix();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	
	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ system_admin_events("Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	$LIST_TABLES_ARTICA_BACKUP=$q->LIST_TABLES_ARTICA_BACKUP();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");
	@mkdir("$BaseWorkDir/artica_backup",0755,true);
	$c=0;
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_BACKUP)){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/artica_backup/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
			
	}
	
	
	
	$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password squidlogs");
	@mkdir("$BaseWorkDir/squidlogs",0755,true);
	$BLACKLIST["tables_day"]=true;
	$BLACKLIST["quotachecked"]=true;
	$BLACKLIST["cached_total"]=true;
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_SQUIDLOGS)){
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#^www_#", $table_name)){continue;}
		if(preg_match("#^visited_#", $table_name)){continue;}
		if(preg_match("#^youtube_#", $table_name)){continue;}
		if(isset($BLACKLIST[$table_name])){continue;}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}
	
	$LIST_TABLES_ARTICA_OCSWEB=$q->LIST_TABLES_ARTICA_OCSWEB();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");
	@mkdir("$BaseWorkDir/ocsweb",0755,true);
	
	
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_OCSWEB)){
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/ocsweb/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}	
	
	
	
	system_admin_events("Artica Databases $c tables done\n",__FUNCTION__,__FILE__,__LINE__);
	
}
function backup_mysql_artica_squidlogs($BaseWorkDir){
	$unix=new unix();
	$sock=new sockets();
	$password=null;
	$mysqldump=$unix->find_program("mysqldump");
	$gzip=$unix->find_program("gzip");
	$BackupArticaBackCategory=$sock->GET_INFO("BackupArticaBackCategory");
	if(!is_numeric($BackupArticaBackCategory)){$BackupArticaBackCategory=1;}

	if(!is_file($gzip)){ system_admin_events("Error, gzip, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }
	if(!is_file($mysqldump)){ system_admin_events("Error, mysqldump, no such binary",__FUNCTION__,__FILE__,__LINE__); return false; }

	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){system_admin_events("Error,/var/run/mysqld/squid-db.sock no such socket",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql_squid_builder();
	$LIST_TABLES_ARTICA_SQUIDLOGS=$q->LIST_TABLES_ARTICA_SQUIDLOGS();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}

	$prefix=trim("$nice $mysqldump --add-drop-table --force --single-transaction --insert-ignore -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");
	@mkdir("$BaseWorkDir/squidlogs",0755,true);

	$BLACKLIST["tables_day"]=true;
	$BLACKLIST["quotachecked"]=true;
	$BLACKLIST["cached_total"]=true;
	$c=0;
	while (list ($table_name, $val) = each ($LIST_TABLES_ARTICA_SQUIDLOGS)){
		if(preg_match("#[0-9]+#", $table_name)){continue;}
		if(preg_match("#^www_#", $table_name)){continue;}
		if(preg_match("#^visited_#", $table_name)){continue;}
		if(preg_match("#^youtube_#", $table_name)){continue;}
		if($BackupArticaBackCategory==0){if(preg_match("#^category_#", $table_name)){continue;} }
		
		if(isset($BLACKLIST[$table_name])){continue;}
		if(is_file("$BaseWorkDir/squidlogs/$table_name.gz")){@unlink("$BaseWorkDir/squidlogs/$table_name.gz");}
		$cmd="$prefix $table_name | $gzip > $BaseWorkDir/squidlogs/$table_name.gz";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
		$c++;
	}

	system_admin_events("Proxy Database $c tables done\n",__FUNCTION__,__FILE__,__LINE__);

}


function progress($purc,$text){
	$array=array("POURC"=>$purc,"TEXT"=>$text);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress",0755);
}

function restore_TestNas(){
	$sock=new sockets();
	$unix=new unix();
	$sock=new sockets();
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");


	$mount=new mount("/var/log/artica-postfix/backup.debug");
	$mountPoint="/mnt/BackupArticaRestoreNAS";

	if($mount->ismounted($mountPoint)){ return true; }

	@mkdir($mountPoint,0755,true);

	if(!$mount->smb_mount($mountPoint,$BackupArticaRestoreNASIpaddr,$BackupArticaRestoreNASUser,$BackupArticaRestoreNASPassword,$BackupArticaRestoreNASFolder)){
		system_admin_events("Mounting //$BackupArticaRestoreNASIpaddr/$BackupArticaRestoreNASFolder failed",__FUNCTION__,__FILE__,__LINE__);
		return false;

	}

	if(!$mount->ismounted($mountPoint)){
		return false;
	}
	return true;
}


function restore(){
	
	$sock=new sockets();
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($GLOBALS["VERBOSE"]){
		echo "PID: $pidfile\n";
	}
	
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid)){
		$TTL=$unix->PROCESS_TTL($oldpid);
		if($TTL<240){return;}
		$kill=$unix->find_program("kill");
		shell_exec("$kill -9 $oldpid 2>&1");
	}
	
	@file_put_contents($pidfile, getmypid());
	$hostname=$unix->hostname_g();
	
	
	progress(10,"{mounting}");
	if(!restore_TestNas()){
		system_admin_events("Mounting NAS filesystem report false",__FUNCTION__,__FILE__,__LINE__);
		progress(100,"{failed}");
		return;
	
	}
	
	$BackupArticaRestoreNASIpaddr=$sock->GET_INFO("BackupArticaRestoreNASIpaddr");
	$BackupArticaRestoreNASFolder=$sock->GET_INFO("BackupArticaRestoreNASFolder");
	$BackupArticaRestoreNASUser=$sock->GET_INFO("BackupArticaRestoreNASUser");
	$BackupArticaRestoreNASPassword=$sock->GET_INFO("BackupArticaRestoreNASPassword");
	$BackupArticaRestoreNASFolderSource=$sock->GET_INFO("BackupArticaRestoreNASFolderSource");
	$BackupArticaRestoreNetwork=$sock->GET_INFO("BackupArticaRestoreNetwork");
	$mountPoint="/mnt/BackupArticaRestoreNAS";	
	$BackupArticaRestoreNASFolderSource=str_replace("\\", "/", $BackupArticaRestoreNASFolderSource);
	
	$sourceDir="$mountPoint/$BackupArticaRestoreNASFolderSource";
	$sourceDir=str_replace("//", "/", $sourceDir);
	
	if(!is_file("$sourceDir/BKVERSION.txt")){
		progress(100,"{failed} BKVERSION.txt no such file");
		$mount=new mount("/var/log/artica-postfix/backup.debug");
		if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
		return;
	}
	
	$time=trim(@file_get_contents("$sourceDir/BKVERSION.txt"));
	progress(15,"{backup} ".date("Y-m-d H:i:s"));
	progress(20,"{restoring_ldap_database}, {please_wait}...");
	Restore_ldap($sourceDir);
	progress(40,"{restoring_artica_settings}, {please_wait}...");
	restore_artica_settings($sourceDir);
	progress(50,"{restoring_artica_databases}, {please_wait}...");
	restore_artica_backup($sourceDir);	
	progress(60,"{restoring_artica_databases}, {please_wait}...");
	restore_ocsweb($sourceDir);	
	progress(80,"{restoring_artica_databases}, {please_wait}...");
	restore_squidlogs($sourceDir);	
	progress(90,"{reconfigure_server}, {please_wait}...");
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($squidbin)){shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force"); }
	progress(100,"{success}");
	$mount=new mount("/var/log/artica-postfix/backup.debug");
	if($mount->ismounted($mountPoint)){ $mount->umount($mountPoint);}
	
	if($BackupArticaRestoreNetwork==1){
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.virtuals-ip.php --build");
	}
	
	return;
	
}

function Restore_ldap($sourceDir){
	
	$unix=new unix();
	$gunzip=$unix->find_program("gunzip");
	$slapadd=$unix->find_program("slapadd");
	$rm=$unix->find_program("rm");
	$ldap_databases="/var/lib/ldap";
	$SLAPD_CONF=$unix->SLAPD_CONF_PATH();
	$SLAPD_CONF_GZ="$sourceDir/ldap.conf.gz";
	$LDAP_DB="$sourceDir/ldap_database.gz";
	$TMP=$unix->FILE_TEMP();
	if(!is_file($SLAPD_CONF_GZ)){
		system_admin_events("{failed} ldap.conf.gz no such file",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	if($GLOBALS["VERBOSE"]){echo "Extract $LDAP_DB\n";}
	shell_exec("$gunzip $LDAP_DB -c >$TMP");
	
	
	if($GLOBALS["VERBOSE"]){echo "Stopping LDAP\n";}
	shell_exec("/etc/init.d/slapd stop --force");
	if($GLOBALS["VERBOSE"]){echo "Restoring slapd.conf\n";}
	shell_exec("$gunzip $SLAPD_CONF_GZ -c >$SLAPD_CONF");
	if($GLOBALS["VERBOSE"]){echo "Removing $ldap_databases\n";}
	shell_exec("$rm -f  $ldap_databases/* >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Restoring database....\n";}
	shell_exec("$slapadd -v -c -l $TMP -f $SLAPD_CONF >/dev/null 2>&1");
	if($GLOBALS["VERBOSE"]){echo "Starting slapd\n";}
	shell_exec("/etc/init.d/slapd start --force");
	@unlink($TMP);
}

function restore_artica_settings($sourceDir){
	if (!$handle = opendir("$sourceDir/Daemons")) {echo "Failed open $sourceDir/Daemons\n";return;}

	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$SourceFile="$sourceDir/Daemons/$filename";
		$targetFile="/etc/artica-postfix/settings/Daemons/$filename";
		if(is_dir($SourceFile)){continue;}
		
		if(is_file($targetFile)){@unlink($targetFile);}
		if($GLOBALS["VERBOSE"]){echo "$SourceFile -> $targetFile\n";}
		copy($SourceFile, $targetFile);
	}
	
	system_admin_events("settings/Daemons done\n",__FUNCTION__,__FILE__,__LINE__);	
}
function restore_artica_backup($sourceDir){

	if (!$handle = opendir("$sourceDir/artica_backup")) {echo "Failed open $sourceDir/artica_backup\n";return;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$BLACKLIST["zarafa_orphaned.gz"]=true;
	if($BackupArticaRestoreNetwork==0){
		$BLACKLIST["nic_routes.gz"]=true;
		$BLACKLIST["nics.gz"]=true;
		$BLACKLIST["nics_bridge.gz"]=true;
		$BLACKLIST["nics_roles.gz"]=true;
		$BLACKLIST["nics_switch.gz"]=true;
		$BLACKLIST["nics_vde.gz"]=true;
		$BLACKLIST["nics_virtuals.gz"]=true;
		$BLACKLIST["nics_vlan.gz"]=true;
		$BLACKLIST["networks_infos.gz"]=true;
	}
	
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password artica_backup");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/artica_backup/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}

	system_admin_events("Restoring artica_backup done\n",__FUNCTION__,__FILE__,__LINE__);
}


function restore_ocsweb($sourceDir){

	if (!$handle = opendir("$sourceDir/ocsweb")) {echo "Failed open $sourceDir/ocsweb\n";return;}
	$password=null;
	$unix=new unix();
	$sock=new sockets();
	$BackupArticaRestoreNetwork=intval($sock->GET_INFO("BackupArticaRestoreNetwork"));
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql();

	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password ocsweb");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/ocsweb/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}

	system_admin_events("Restoring ocsweb done\n",__FUNCTION__,__FILE__,__LINE__);
}
function restore_squidlogs($sourceDir){

	if (!$handle = opendir("$sourceDir/squidlogs")) {echo "Failed open $sourceDir/squidlogs\n";return;}
	$password=null;
	$unix=new unix();
	
	if(!$unix->is_socket("/var/run/mysqld/squid-db.sock")){system_admin_events("Error,/var/run/mysqld/squid-db.sock no such socket",__FUNCTION__,__FILE__,__LINE__); return false; }
	
	$sock=new sockets();
	$gunzip=$unix->find_program("gunzip");
	$mysql=$unix->find_program("mysql");
	$BLACKLIST=array();
	$nice=$unix->EXEC_NICE();
	$q=new mysql_squid_builder();

	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	$prefix=trim("$mysql --force -S /var/run/mysqld/squid-db.sock -u {$q->mysql_admin}$password squidlogs");

	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if(isset($BLACKLIST[$filename])){continue;}
		$SourceFile="$sourceDir/squidlogs/$filename";
		if(is_dir($SourceFile)){continue;}
		$cmd=trim("$nice $gunzip -c $SourceFile |$prefix");
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		shell_exec($cmd);
	}

	system_admin_events("Restoring squidlogs done\n",__FUNCTION__,__FILE__,__LINE__);
}


