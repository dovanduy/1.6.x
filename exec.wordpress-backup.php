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
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--exec"){start();die();}
if($argv[1]=="--dirs"){ScanDirs();die();}
if($argv[1]=="--remove-dirs"){RemoveDirs();die();}
if($argv[1]=="--ftp"){ftp_backup();die();}



function start(){
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$unix=new unix();
	$me=basename(__FILE__);
	
	if($unix->process_exists(@file_get_contents($pidfile),$me)){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. ". @file_get_contents($pidfile). " aborting the process\n";}
		system_admin_events("--> Already executed.. ". @file_get_contents($pidfile). " aborting the process", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		die();
	}
	
	@file_put_contents($pidfile, getmypid());
	
	
	$WordpressBackupParams=unserialize(base64_decode($sock->GET_INFO("WordpressBackupParams")));
	if($WordpressBackupParams["DEST"]==null){$WordpressBackupParams["DEST"]="/home/wordpress-backup";}
	
	ScanFreeWebs($WordpressBackupParams);
	$t=time();
	ftp_backup($WordpressBackupParams);

}

function ScanFreeWebs($WordpressBackupParams){
	
	
	$sql="SELECT * FROM freeweb WHERE groupware='WORDPRESS'";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	@mkdir($WordpressBackupParams["DEST"]);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$servername=$ligne["servername"];
		mysql_backup($WordpressBackupParams,$servername);
		directory_backup($WordpressBackupParams,$servername);
		$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
		@file_put_contents("$BaseWorkDir/config.serialize", base64_encode(serialize($ligne)));
	}
	
	
}

function mysql_backup($WordpressBackupParams,$servername){
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$q=new mysql();
	$free=new freeweb($servername);
	$password=null;
	$gzip=$unix->find_program("gzip");
	$database=$free->mysql_database;
	echo "Backup database $database";
	if(!$q->DATABASE_EXISTS($database)){
		apache_admin_mysql(0, "$servername cannot backup a non-existent database $database", null,__FILE__,__LINE__);
		return false;
	}
	$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
	@mkdir("$BaseWorkDir",0755,true);
	$nice=$unix->EXEC_NICE();
	$q=new mysql();
	if($q->mysql_password<>null){$password=" -p".$unix->shellEscapeChars($q->mysql_password);}
	
	$t=time();
	$prefix=trim("$nice $mysqldump --add-drop-table --single-transaction --force --insert-ignore -S /var/run/mysqld/mysqld.sock -u {$q->mysql_admin}$password $database");
	$cmdline="$prefix | $gzip > $BaseWorkDir/database.gz";
	shell_exec($cmdline);	
	
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/database.gz")/1024);
	apache_admin_mysql(2, "$servername database $database backuped $size (took $took)", null,__FILE__,__LINE__);
	
	
}
function directory_backup($WordpressBackupParams,$servername){
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$q=new mysql();
	$free=new freeweb($servername);
	$gzip=$unix->find_program("gzip");
	$WORKDIR=$free->www_dir;
	echo "Backup directory $WORKDIR";
	if(!is_dir($WORKDIR)){
		apache_admin_mysql(0, "$servername cannot backup a non-existent directory $WORKDIR", null,__FILE__,__LINE__);
		return false;
	}
	$BaseWorkDir=$WordpressBackupParams["DEST"]."/$servername/".date("Y-m-d-H")."h";
	@mkdir("$BaseWorkDir",0755,true);
	$nice=$unix->EXEC_NICE();
	$t=time();
	chdir($WORKDIR);
	shell_exec("$nice $tar cfz $BaseWorkDir/wordpress.tar.gz *");
	$took=$unix->distanceOfTimeInWords($t,time());
	$size=FormatBytes(@filesize("$BaseWorkDir/wordpress.tar.gz")/1024);
	apache_admin_mysql(2, "$servername directory backuped $size (took $took)", null,__FILE__,__LINE__);


}

function ftp_backup($WordpressBackupParams){
	$sock=new sockets();
	$mount=new mount();
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$FTP_ENABLE=intval($WordpressBackupParams["FTP_ENABLE"]);
	if($FTP_ENABLE==0){ echo "FTP disbabled\n"; return;}
	
	
	$FTP_SERVER=$WordpressBackupParams["FTP_ENABLE"];
	$FTP_USER=$WordpressBackupParams["FTP_USER"];
	$FTP_PASS=$WordpressBackupParams["FTP_PASS"];
	$FTP_SERVER=$WordpressBackupParams["FTP_SERVER"];
	$mntDir="/home/artica/mnt-wordpress-".time();
	@mkdir($mntDir,0755,true);
	
	if(!$mount->ftp_mount($mntDir, $FTP_SERVER, $FTP_USER, $FTP_PASS)){
		apache_admin_mysql(0,"Unable to mount FTP $FTP_USER@$FTP_SERVER",null,__FILE__,__LINE__);
		return;
	}
	
	$FTPDir="$mntDir/".$unix->hostname_g()."/wordpress-backup";
	
	
	echo "Starting copy... in $FTPDir\n";
	if($GLOBALS["VERBOSE"]){echo "Checks $FTPDir\n"; }
	if(!is_dir($FTPDir)){
		if($GLOBALS["VERBOSE"]){echo "$FTPDir no such directory\n";}
		@mkdir($FTPDir,0755,true);
	}
	
	
	if(!is_dir($FTPDir)){
		
		apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $FTPDir permission denied",null,__FILE__,__LINE__);
		$mount->umount($mntDir);
		@rmdir($mntDir);
		
		return;
	}
	


	
	$directories_servernames=$unix->dirdir($WordpressBackupParams["DEST"]);
	$cp=$unix->find_program("cp");
	
	while (list ($directory, $ext) = each ($directories_servernames) ){
		$dirRoot=basename($directory);
		$TargetDirectory="$FTPDir/$dirRoot";
		
		if(!is_dir($TargetDirectory)){
			if($GLOBALS["VERBOSE"]){echo "Create directory $TargetDirectory\n";}
			@mkdir($TargetDirectory,0755,true);
		}
		
		if(!is_dir($TargetDirectory)){
				apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $TargetDirectory permission denied",__FILE__,__LINE__); 
				continue; 
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "Scaning $directory\n";}
		$directories_conteners=$unix->dirdir($directory);
		while (list ($directoryTime, $ext) = each ($directories_conteners) ){
			$dirRootTime=basename($directoryTime);
			$TargetDirectory="$FTPDir/$dirRoot/$dirRootTime";
			@mkdir($TargetDirectory,0755,true);
			if(!is_dir($TargetDirectory)){apache_admin_mysql(0,"Fatal FTP $FTP_USER@$FTP_SERVER $TargetDirectory permission denied",__FILE__,__LINE__); continue; }
			if(!is_file("$directoryTime/database.gz")){
				apache_admin_mysql(0,"Fatal $directoryTime/database.gz no such file, skip",null,__FILE__,__LINE__); 
				continue; 
			}
			
			$t=time();
			$results=array();
			if($GLOBALS["VERBOSE"]){echo "Copy $directoryTime/* -> $TargetDirectory\n";}
			exec("$cp -rf $directoryTime/* $TargetDirectory/ 2>&1",$results);
			while (list ($a, $b) = each ($results) ){
				if(preg_match("#cannot#i",$b)){
					apache_admin_mysql(0,"Fatal Copy error $b, skip",$b,__FILE__,__LINE__);
				}
			}
			
			
			$took=$unix->distanceOfTimeInWords($t,time());
			if(!is_file("$TargetDirectory/database.gz")){
				apache_admin_mysql(0,"Fatal $TargetDirectory/database.gz permission denied, skip",null,__FILE__,__LINE__);
				continue;
			}
			shell_exec("$rm -rf $directoryTime");
		}
	}
		
	$mount->umount($mntDir);
	@rmdir($mntDir);
	return;		
}

