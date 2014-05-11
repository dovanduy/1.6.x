<?php
$GLOBALS["SIMULATE"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SYS_CAT"]="cyrus-backup";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--simulate#",implode(" ",$argv))){$GLOBALS["SIMULATE"]=true;}
if(preg_match("#--notime#",implode(" ",$argv))){$GLOBALS["NOTIME"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["LOGFILE"]="/var/log/cyrus-backup.debug";
$GLOBALS["MOUNT_POINT"]="/mnt/cyrus-mount-backup";
$GLOBALS["SYSTEM_INTERNAL_LOAD"]=0;
$GLOBALS["MOUNTED_PATH_FINAL"]=null;
$_GET["LOGFILE"]=$GLOBALS["LOGFILE"];
$unix=new unix();
$sock=new sockets();
$GLOBALS["CLASS_UNIX"]=$unix;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$x=$unix->process_number_me($argv);
if($x>0){die("This process is already executed $x times\n\n");}


if($argv[1]=="--testnas"){tests_nas().killNas();die();}
if($argv[1]=="--test-nas"){tests_nas().killNas();die();}
backup();

die();

function tests_nas(){
	$sock=new sockets();
	$unix=new unix();
	$failed="***********************\n** FAILED **\n***********************\n";
	$success="***********************\n******* SUCCESS *******\n***********************\n";
	if(!isset($GLOBALS["CyrusBackupNas"])){$GLOBALS["CyrusBackupNas"]=unserialize(base64_decode($sock->GET_INFO("CyrusBackupNas")));}
	$CyrusBackupNas=$GLOBALS["CyrusBackupNas"];
	if(!isset($CyrusBackupNas["hostname"])){return;}
	if($CyrusBackupNas["hostname"]==null){return;}
	if(!is_numeric($CyrusBackupNas["notifs"])){$CyrusBackupNas["notifs"]=0;}

	if($GLOBALS["VERBOSE"]){
		if($CyrusBackupNas["notifs"]==1){
			$unix->SendEmailConfigured($CyrusBackupNas,"Test-message","This is a content");
		}
	}
	
	
	if($GLOBALS["VERBOSE"]){
		while (list ($index, $line) = each ($CyrusBackupNas) ){
			echo "$index.........: $line\n";
		}
	}

	$mount=new mount($GLOBALS["LOGFILE"]);
	$NasFolder=$CyrusBackupNas["folder"];
	$NasFolder=str_replace('\\\\', '/', $NasFolder);
	if(strpos($NasFolder, "/")>0){$f=explode("/",$NasFolder);$NasFolder=$f[0];}
	

	
	
	
	if($mount->ismounted($GLOBALS["MOUNT_POINT"])){
		if($GLOBALS["VERBOSE"]){echo $success.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);}
		return true;
	}
	
	if(!$mount->smb_mount($GLOBALS["MOUNT_POINT"],$CyrusBackupNas["hostname"],
		$CyrusBackupNas["username"],$CyrusBackupNas["password"],$NasFolder)){
		if($GLOBALS["VERBOSE"]){echo $failed.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);return;}
	}
	
	if($GLOBALS["VERBOSE"]){echo $success.@implode("\n", $GLOBALS["MOUNT_EVENTS"]);}
	return true;

}
function killNas(){
	$sock=new sockets();
	$mount=new mount($GLOBALS["LOGFILE"]);
	if($mount->ismounted($GLOBALS["MOUNT_POINT"])){$mount->umount($GLOBALS["MOUNT_POINT"]);}

}

function backup(){
	$unix=new unix();
	if(!tests_nas()){
		system_admin_events("Unable to backup cyrus-mailboxes",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	$hostname=$unix->hostname_g();
	$GLOBALS["DIRBYTES"]=date("YmdH");
	$GLOBALS["MOUNTED_PATH__BACKUPDIR"]="{$GLOBALS["MOUNT_POINT"]}/$hostname";
	$GLOBALS["MOUNTED_PATH_FINAL"]="{$GLOBALS["MOUNT_POINT"]}/$hostname/{$GLOBALS["DIRBYTES"]}";
	if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
		@mkdir($GLOBALS["MOUNTED_PATH_FINAL"],0755,true);
		if(!is_dir($GLOBALS["MOUNTED_PATH_FINAL"])){
			sendEmail("Unable to backup: Permission denied on NAS", "Unable to create {$GLOBALS["MOUNTED_PATH_FINAL"]} on your NAS system");
			system_admin_events("Unable to backup: Permission denied on NAS",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	backup_ldap();
	backup_cyrus();
	remove_containers();
	killNas();
}

function remove_containers(){
	$q=new mysql();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$hostname=$unix->hostname_g();
	if(!is_numeric($GLOBALS["CyrusBackupNas"]["maxcontainer"])){$GLOBALS["CyrusBackupNas"]["maxcontainer"]=3;}
	$sql="SELECT * FROM cyrus_backup WHERE hostname='$hostname' ORDER BY directory DESC";
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(mysql_num_rows($results) < $GLOBALS["CyrusBackupNas"]["maxcontainer"] ) {return;}
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		if($c<$GLOBALS["CyrusBackupNas"]["maxcontainer"]){continue;}
		$directory=$ligne["directory"];
		if(!is_dir("{$GLOBALS["MOUNTED_PATH__BACKUPDIR"]}/$directory")){
			$q->QUERY_SQL("DELETE FROM cyrus_backup WHERE directory='$directory' AND hostname='$hostname'","artica_events");
			continue;
		}
		shell_exec("$rm -rf {$GLOBALS["MOUNTED_PATH__BACKUPDIR"]}/$directory");
		system_admin_events("Deleted container $directory",__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL("DELETE FROM cyrus_backup WHERE directory='$directory' AND hostname='$hostname'","artica_events");
	}	
}

function backup_cyrus(){
	$unix=new unix();
	$tempdir=$unix->TEMP_DIR();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		$TIMEF=$unix->PROCCESS_TIME_MIN($pid);
		system_admin_events("Aready task is currently running PID $pid since {$TIMEF}Mn",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	$date_start=time();
	$q=new mysql();
	
	$users=new usersMenus();
	if(!$users->cyrus_imapd_installed){
		system_admin_events("Unable to backup: cyrus-impad NOT Installed",__FUNCTION__,__FILE__,__LINE__);
		return true;
	}

	$partition_default=$users->cyr_partition_default;
	$config_directory=$users->cyr_config_directory;
	$tar=$unix->find_program("tar");
	$su=$unix->find_program("su");
	

	@mkdir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap",0755,true);
	
	if(!is_dir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap")){
		
		system_admin_events(__LINE__."]: Unable to backup: Permission denied on NAS {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap no such directory",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap OK\n";}
		

	if(!is_file("$users->ctl_mboxlist")){
		system_admin_events("Unable to backup: ctl_mboxlist no such binary",__FUNCTION__,__FILE__,__LINE__);
		return;	
	}
	$L=explode("\n",@file_get_contents("/etc/security/limits.conf"));
	$T=array();
	while (list ($index, $line) = each ($L)){
		$line=trim($line);
		if(trim($line)==null){continue;}
		if(substr($line, 0,1)=="#"){continue;}
		if(preg_match("#^cyrus#", $line)){continue;}
		$T[]=$line;
	}
	
	$T[]="cyrus       soft    nofile   64000";
	$T[]="cyrus       hard    nofile   64000";
	
	
	@file_put_contents("/etc/security/limits.conf", @implode("\n", $T)."\n");
	$L=array();
	$T=array();
	
	
	
	$cmd="$su - cyrus -c \"$users->ctl_mboxlist -d >$tempdir/mailboxlist.txt\"";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	
	if(!is_file("$tempdir/mailboxlist.txt")){
		sendEmail("Unable to backup: Permission denied on NAS", "Unable to create $tempdir/mailboxlist.txt on your NAS system\n$cmd".@implode("\n", $results));
		system_admin_events("Unable to backup: unable to export mailbox list\nfile $tempdir/mailboxlist.txt not exists\n****\n$cmd\n****\n\n".implode("\n",$results),__FUNCTION__,__FILE__,__LINE__);
	}else{
		
		if(!@copy("$tempdir/mailboxlist.txt", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mailboxlist.txt")){
			sendEmail("Unable to backup: Permission denied on NAS", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap permission denied" );
			system_admin_events("Unable to backup: Permission denied on NAS\n{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap permission denied");
		}
		
		@unlink("$tempdir/mailboxlist.txt");
	}
	
	
	
	$results=array();
	@chdir($partition_default);
	$cmd="$tar -Pcjf {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2 * 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	if(!is_file("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2")){
		sendEmail("Unable to backup: Permission denied on NAS", "Unable to create {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/mail-data-backup.tar.bz2 on your NAS system\n$cmd\n".@implode("\n", $results));
		system_admin_events("Unable to backup: mail-data-backup.tar.bz2 Permission denied or compression failed\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$results=array();
	@chdir($config_directory);
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	$cmd="$tar -Pcjf {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2 * 2>&1";
	exec($cmd,$results);
	if(!is_file("{$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2")){
		sendEmail("Unable to backup: Permission denied on NAS", "Unable to create {$GLOBALS["MOUNTED_PATH_FINAL"]}/cyrus-imap/configdirectory.tar.bz2 on your NAS system\n$cmd\n".@implode("\n", $results));
		system_admin_events("Unable to backup: configdirectory.tar.bz2 Permission denied or compression failed\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
	}
		
	$size=$unix->DIRSIZE_BYTES("{$GLOBALS["MOUNTED_PATH_FINAL"]}");
	$date_end=time();
	$calculate=$unix->distanceOfTimeInWords($date_start,$date_end);
	$zDate=date("Y-m-d H:i:s");
	$hostname=$unix->hostname_g();
	$sql="CREATE TABLE IF NOT EXISTS `artica_events`.`cyrus_backup` (
				`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				`zDate` DATETIME NOT NULL ,
				`hostname` VARCHAR( 128 ) NOT NULL ,
				`duration` VARCHAR( 256 ) NOT NULL ,
				`directory` BIGINT UNSIGNED,
				`size` BIGINT UNSIGNED ,
				INDEX ( `zDate` , `directory` , `size` ,`hostname`)
				);";
	$q->QUERY_SQL($sql,'artica_events');
	$q->QUERY_SQL("INSERT IGNORE INTO `cyrus_backup` (`zDate`,`hostname`,`duration`,`directory`,`size`) VALUES('$zDate','$hostname','$calculate','{$GLOBALS["DIRBYTES"]}','$size')","artica_events");
	
	if(!$q->ok){system_admin_events("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
	
	$size=$size/1024;
	$size=$size/1024;
	
	system_admin_events("Cyrus backup: Success $calculate in {$GLOBALS["MOUNTED_PATH_FINAL"]}",__FUNCTION__,__FILE__,__LINE__);
	sendEmail("Cyrus backup: Success $calculate - {$size}MB", "Backup created in {$GLOBALS["MOUNTED_PATH_FINAL"]}/on your NAS system\n".@implode("\n", $results));
}


function backup_ldap(){
	$unix=new unix();
	$slapcat=$unix->find_program("slapcat");
	if($slapcat==null){
		system_admin_events("Unable to find slapcat binary",__FUNCTION__,__FILE__,__LINE__);
		return false;
	}
	$tempdir=$unix->TEMP_DIR();
	shell_exec("$slapcat -l $tempdir/ldap.ldif");

	@mkdir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup",0755,true);
	if(!is_dir("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup")){
		system_admin_events("Unable to backup: Permission denied on NAS",__FUNCTION__,__FILE__,__LINE__);
		sendEmail("Unable to backup: Permission denied on NAS", "Unable to create {$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup on your NAS system");
		@unlink("$tempdir/ldap.ldif");
		return false;
	}

	if(!@copy("$tempdir/ldap.ldif", "{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/ldap.ldif")){
		system_admin_events("Unable to backup: Permission denied on NAS",__FUNCTION__,__FILE__,__LINE__);
		sendEmail("Unable to backup: Permission denied on NAS", "Unable to create {$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/ldap.ldif on your NAS system");
		@unlink("$tempdir/ldap.ldif");
		return false;
	}
	
	
	$ldap=new clladp();
	if(!@file_put_contents("{$GLOBALS["MOUNTED_PATH_FINAL"]}/ldap_backup/suffix",$ldap->suffix)){
		system_admin_events("Unable to backup: Permission denied on NAS",__FUNCTION__,__FILE__,__LINE__);
	}
	@unlink("$tempdir/ldap.ldif");
}

function SendeMail($subject,$content){
	if(!isset($GLOBALS["CyrusBackupNas"])){
		$sock=new sockets();
		$GLOBALS["CyrusBackupNas"]=unserialize(base64_decode($sock->GET_INFO("CyrusBackupNas")));
	}
	
	if(!is_numeric($GLOBALS["CyrusBackupNas"]["notifs"])){return;}
	$unix=new unix();
	$unix->SendEmailConfigured($GLOBALS["CyrusBackupNas"],$subject,$content);
}
