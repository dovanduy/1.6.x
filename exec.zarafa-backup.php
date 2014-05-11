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
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--exec"){start();die();}
if($argv[1]=="--dirs"){ScanDirs();die();}
if($argv[1]=="--remove-dirs"){RemoveDirs();die();}



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
	
	
	$zarafaserv=$unix->find_program("zarafa-server");
	if(!is_file($zarafaserv)){
		system_admin_events("zarafa-server, no such binary aborting...", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		die();
	}
	
	$ZarafaBackupParams=unserialize(base64_decode($sock->GET_INFO("ZarafaBackupParams")));
	if($ZarafaBackupParams["DEST"]==null){$ZarafaBackupParams["DEST"]="/home/zarafa-backup";}
	if(!is_numeric($ZarafaBackupParams["DELETE_OLD_BACKUPS"])){$ZarafaBackupParams["DELETE_OLD_BACKUPS"]=1;}
	if(!is_numeric($ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"])){$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]=10;}
		
	
	build_script();
	$t=time();
	if($GLOBALS["VERBOSE"]){echo " -> /bin/artica-zarafa-backup.sh\n";}
	exec("/bin/artica-zarafa-backup.sh 2>&1",$results);
	if($GLOBALS["VERBOSE"]){echo @implode("\n", $results)."\n";}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	system_admin_events("Backup done took:$took\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "zarafa");
	$NOW=date("Ymd");
	
	$stamp="{$ZarafaBackupParams["DEST"]}/$NOW/took.txt";
	$datestamp="{$ZarafaBackupParams["DEST"]}/$NOW/time.txt";
	@file_put_contents($stamp, $took);
	@file_put_contents($datestamp, date("Y-m-d H:i:s"));
	ScanDirs();
	RemoveDirs();

}
function ScanDirs(){
	$sock=new sockets();
	$ZarafaBackupParams=unserialize(base64_decode($sock->GET_INFO("ZarafaBackupParams")));
	if($ZarafaBackupParams["DEST"]==null){$ZarafaBackupParams["DEST"]="/home/zarafa-backup";}
	if(!is_numeric($ZarafaBackupParams["DELETE_OLD_BACKUPS"])){$ZarafaBackupParams["DELETE_OLD_BACKUPS"]=1;}
	if(!is_numeric($ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"])){$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]=10;}
			
	$unix=new unix();
	$directories=$unix->dirdir($ZarafaBackupParams["DEST"]);
	
	while (list ($directory, $ext) = each ($directories) ){if(is_file("$directory/zarafa.gz")){$Gooddirs[$directory]=true;}}
	
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE zarafa_backup","artica_backup");
	$prefix="INSERT INTO zarafa_backup (`filepath`,`filesize`,`ztime`,`zDate`) VALUES ";
	
	while (list ($directory, $ext) = each ($Gooddirs) ){
		$date=null;
		$stamp="$directory/took.txt";
		$datestamp="$directory/time.txt";		
		$size=$unix->file_size("$directory/zarafa.gz");
		if(is_file($datestamp)){$date=@file_get_contents($datestamp);}
		if($date==null){$date=date("Y-m-d H:i:s",filemtime("$directory/zarafa.gz") );}
		if(is_file($stamp)){$took=@file_get_contents($stamp);}
		
		if($GLOBALS["VERBOSE"]){
			$sizeDBG=round(($size/1024)/1000,2);
			echo "Found: Container saved on: $date took:$took $directory/zarafa.gz Size:$sizeDBG MB\n";
		}
		$directory=addslashes($directory);
		$f[]="('$directory','$size','$took','$date')";
	}
	
	if(count($f)>0){
		$sql="$prefix". @implode(",", $f);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){system_admin_events("Fatal $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "backup");}
	}
	
	echo count($f)." container(s) found...\n";
	
}

function RemoveDirs(){
	$sock=new sockets();
	$database="artica_backup";
	$ZarafaBackupParams=unserialize(base64_decode($sock->GET_INFO("ZarafaBackupParams")));
	if($ZarafaBackupParams["DEST"]==null){$ZarafaBackupParams["DEST"]="/home/zarafa-backup";}
	if(!is_numeric($ZarafaBackupParams["DELETE_OLD_BACKUPS"])){$ZarafaBackupParams["DELETE_OLD_BACKUPS"]=1;}
	if(!is_numeric($ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"])){$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]=10;}
	if($ZarafaBackupParams["DELETE_OLD_BACKUPS"]==0){return;}

	$maxDays=$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"];
	$sql="SELECT filepath,zDate FROM zarafa_backup WHERE zDate<DATE_SUB(NOW(),INTERVAL $maxDays DAY)";
	$q=new mysql();
	$unix=new unix();
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){echo "$q->mysql_error\n";return;}
	
	$rm=$unix->find_program("rm");
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$filepath=$ligne["filepath"];
		
		if(is_dir($filepath)){
			$c++;
			echo "Removing $filepath ({$ligne["zDate"]})\n";
			$tt[]=$filepath;
			shell_exec("$rm -rf $filepath");
			$q->QUERY_SQL("DELETE FROM zarafa_backup WHERE `filepath`='$filepath'","artica_backup");
		}
		
		
	}
	echo $c." container(s) deleted, max day(s):$maxDays ...\n";
	if($c>0){
		system_admin_events("$c container(s) deleted, max day(s):$maxDays ...\n".@implode("\n", $tt), __FUNCTION__, __FILE__, __LINE__, "zarafa");
	}
}


function build_script(){
	$sock=new sockets();
	$unix=new unix();
	$ZarafaBackupParams=unserialize(base64_decode($sock->GET_INFO("ZarafaBackupParams")));
	
	if($ZarafaBackupParams["DEST"]==null){$ZarafaBackupParams["DEST"]="/home/zarafa-backup";}
	if(!is_numeric($ZarafaBackupParams["DELETE_OLD_BACKUPS"])){$ZarafaBackupParams["DELETE_OLD_BACKUPS"]=1;}
	if(!is_numeric($ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"])){$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]=10;}
	$q=new mysql();	
	$nopass=0;
	if(trim($q->mysql_password)==null){
		$nopass=1;
	}
	$SLAPCATE=null;
	$slapcat=$unix->find_program("slapcat");
	if(is_file($slapcat)){
		$slapdconf=$unix->LOCATE_SLPAD_CONF();
		$SLAPCATE="$slapcat -f $slapdconf";
		} 	
	
	if($GLOBALS["VERBOSE"]){echo "$q->mysql_server; no pass:$nopass DEST:{$ZarafaBackupParams["DEST"]}\n";}
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	$MYSQL_SOCKET="/var/run/mysqld/mysqld.sock";
	if($ZarafaDedicateMySQLServer==1){
		$MYSQL_SOCKET="/var/run/mysqld/zarafa-db.sock";
		$nopass=1;
	}
	
	
$f[]="#!/bin/bash";
$f[]="#";
$f[]="";
$f[]="# Modify the variables below to your need";
$f[]="";
$f[]="# Mysql Credentials";
if($ZarafaDedicateMySQLServer==0){
$f[]="MyUSER=\"$q->mysql_admin\"";
$f[]="MyPASS=\"$q->mysql_password\"";
$f[]="MyHOST=\"$q->mysql_server\"";
}else{
	$f[]="MyUSER=\"root\"";
}
$f[]="NO_PASS=$nopass";
$f[]="";
$f[]="# Owner of mysql backup dir";
$f[]="OWNER=\"root\"";
$f[]="# Group of mysql backup dir";
$f[]="GROUP=\"root\"";
$f[]="";
$f[]="# Which databases to backup";
$f[]="DBS=\"zarafa\"";
$f[]="# Or get all databases";
$f[]="#DBS=\"\$(\$MYSQL -u \$MyUSER -h \$MyHOST -p\$MyPASS -Bse 'show databases')\"";
$f[]="";
$f[]="# DO NOT BACKUP these databases";
$f[]="IGGY=\"test\"";
$f[]="";
$f[]="# Backup Dest directory, change this if you have someother location";
$f[]="DEST=\"{$ZarafaBackupParams["DEST"]}\"";
$f[]="";
$f[]="# mysqldump parameters";
$f[]="DUMP_OPTS=\"-Q --single-transaction -S $MYSQL_SOCKET --max-allowed-packet=100M\"";
$f[]="";
$f[]="# Send Result EMail";
$f[]="SEND_EMAIL=0";
$f[]="NOTIFY_EMAIL=\"user@domain.com\"";
$f[]="NOTIFY_SUBJECT=\"MySQL Backup Notification\"";
$f[]="";
$f[]="# Delete old backups";
$f[]="DELETE_OLD_BACKUPS=0";
$f[]="DELETE_BACKUPS_OLDER_THAN_DAYS={$ZarafaBackupParams["DELETE_BACKUPS_OLDER_THAN_DAYS"]}";
$f[]="";
$f[]="# Usually there is no need to modify the variables below";
$f[]="";
$f[]="# Linux bin paths, change this if it can't be autodetected via which command";
$f[]="MYSQL=\"\$(which mysql)\"";
$f[]="MYSQLDUMP=\"\$(which mysqldump)\"";
$f[]="GREP=\"\$(which grep)\"";
$f[]="CHOWN=\"\$(which chown)\"";
$f[]="CHMOD=\"\$(which chmod)\"";
$f[]="GZIP=\"\$(which gzip)\"";
$f[]="MAIL=\"\$(which mail)\"";
$f[]="FIND=\"\$(which find)\"";
$f[]="DF=\"\$(which df)\"";
$f[]="";
$f[]="# Get hostname";
$f[]="HOST=\"\$(hostname)\"";
$f[]="";
$f[]="# Get data in yyyy-mm-dd format";
$f[]="NOW=\"\$(date +\"%Y%m%d\")\"";
$f[]="";
$f[]="# Function for generating Email";
$f[]="function gen_email {";
$f[]="  DO_SEND=\$1";
$f[]="  TMP_FILE=\$2";
$f[]="  NEW_LINE=\$3";
$f[]="  LINE=\$4";
$f[]="  if [ \$DO_SEND -eq 1 ]; then";
$f[]="    if [ \$NEW_LINE -eq 1 ]; then";
$f[]="      echo \"\$LINE\" >> \$TMP_FILE";
$f[]="    else";
$f[]="      echo -n \"\$LINE\" >> \$TMP_FILE";
$f[]="    fi";
$f[]="  fi";
$f[]="}";
$f[]="";
$f[]="# Main directory where backup will be stored";
$f[]="if [ ! -d \$DEST ]; then ";
$f[]="  mkdir -p \$DEST";
$f[]="  # Only \$OWNER.\$GROUP can access it!";
$f[]="  \$CHOWN \$OWNER:\$GROUP -R \$DEST";
$f[]="  \$CHMOD 0750 \$DEST";
$f[]="fi";
$f[]="";
$f[]="# Create backup directory";
$f[]="MBD=\"\$DEST/\$NOW\"";
$f[]="if [ ! -d \"\$MBD\" ]; then";
$f[]="  mkdir \"\$MBD\"";
$f[]="  # Only \$OWNER.\$GROUP can access it!";
$f[]="  \$CHOWN \$OWNER:\$GROUP -R \$MBD";
$f[]="  \$CHMOD 0750 \$MBD";
$f[]="fi";
$f[]="";
$f[]="# Temp Message file";
$f[]="TMP_MSG_FILE=\"/tmp/\$RANDOM.msg\"";
$f[]="if [ \$SEND_EMAIL -eq 1 -a -f \"\$TMP_MSG_FILE\" ]; then";
$f[]="  rm -f \"\$TMP_MSG_FILE\"";
$f[]="fi";
$f[]="";
$f[]="set -o pipefail";
$f[]="";
$f[]="# Start backing up databases";
$f[]="STARTTIME=\$(date +%s)";
$f[]="for db in \$DBS";
$f[]="do";
$f[]="    skipdb=-1";
$f[]="    if [ \"\$IGGY\" != \"\" ];";
$f[]="    then";
$f[]="	for i in \$IGGY";
$f[]="	do";
$f[]="	    [ \"\$db\" == \"\$i\" ] && skipdb=1 || :";
$f[]="	done";
$f[]="    fi";
$f[]="    ";
$f[]="    if [ \"\$skipdb\" == \"-1\" ] ; then";
$f[]="	FILE=\"\$MBD/zarafa\"";
$f[]="	echo \"Creating container \$FILE\"";
$f[]="	# do all inone job in pipe,";
$f[]="	# connect to mysql using mysqldump for select mysql database";
$f[]="	# and pipe it out to gz file in backup dir :)";
$f[]="		if [ \$NO_PASS -eq 1 ]; then";
$f[]="        \$MYSQLDUMP \$DUMP_OPTS -u \$MyUSER -h \$MyHOST \$db | \$GZIP -9 > \"\$FILE.gz\"";
$f[]="      fi";
$f[]="		if [ \$NO_PASS -eq 0 ]; then";
$f[]="        \$MYSQLDUMP \$DUMP_OPTS -u \$MyUSER -h \$MyHOST -p\$MyPASS \$db | \$GZIP -9 > \"\$FILE.gz\"";
$f[]="      fi";
$f[]="        ERR=\$?";
$f[]="        if [ \$ERR != 0 ]; then";
$f[]="	  NOTIFY_MESSAGE=\"Error: \$ERR, while backing up database: \$db\"	";
$f[]="	else";
$f[]="	  NOTIFY_MESSAGE=\"Successfully backed up database: \$db\"";
$f[]="	  $SLAPCATE -l \$MBD/ldap.ldif >/dev/null";
$f[]="	fi	";
$f[]="        gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"\$NOTIFY_MESSAGE\"";
$f[]="        echo \$NOTIFY_MESSAGE";
$f[]="    fi";
$f[]="done";
$f[]="ENDTIME=\$(date +%s)";
$f[]="DIFFTIME=\$(( \$ENDTIME - \$STARTTIME ))";
$f[]="DUMPTIME=\"\$((\$DIFFTIME / 60)) minutes and \$((\$DIFFTIME % 60)) seconds.\"";
$f[]="";
$f[]="# Empty line in email and stdout";
$f[]="gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"\"";
$f[]="echo \"\"";
$f[]="";
$f[]="# Log Time";
$f[]="gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"mysqldump took: \${DUMPTIME}\"";
$f[]="echo \"mysqldump took: \${DUMPTIME}\"";
$f[]="";
$f[]="# Empty line in email and stdout";
$f[]="gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"\"";
$f[]="echo \"\"";
$f[]="";
$f[]="# Delete old backups";
$f[]="if [ \$DELETE_OLD_BACKUPS -eq 1 ]; then";
$f[]="  find \"\$DEST\" -maxdepth 1 -mtime +\$DELETE_BACKUPS_OLDER_THAN_DAYS -type d | \$GREP -v \"^\$DEST\$\" | while read DIR; do";
$f[]="    gen_email \$SEND_EMAIL \$TMP_MSG_FILE 0 \"Deleting: \$DIR: \"";
$f[]="    echo -n \"Deleting: \$DIR: \"";
$f[]="    rm -rf \"\$DIR\" ";
$f[]="    ERR=\$?";
$f[]="    if [ \$ERR != 0 ]; then";
$f[]="      NOTIFY_MESSAGE=\"ERROR\"";
$f[]="    else";
$f[]="      NOTIFY_MESSAGE=\"OK\"";
$f[]="    fi";
$f[]="    gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"\$NOTIFY_MESSAGE\"";
$f[]="    echo \"\$NOTIFY_MESSAGE\"";
$f[]="  done";
$f[]="fi";
$f[]="";
$f[]="# Empty line in email and stdout";
$f[]="gen_email \$SEND_EMAIL \$TMP_MSG_FILE 1 \"\"";
$f[]="echo \"\"";
$f[]="";
$f[]="# Add disk space stats of backup filesystem";
$f[]="if [ \$SEND_EMAIL -eq 1 ]; then";
$f[]="  \$DF -h \"\$DEST\" >> \"\$TMP_MSG_FILE\"  ";
$f[]="fi";
$f[]="\$DF -h \"\$DEST\"";
$f[]="";
$f[]="# Sending notification email";
$f[]="if [ \$SEND_EMAIL -eq 1 ]; then";
$f[]="  \$MAIL -s \"\$NOTIFY_SUBJECT\" \"\$NOTIFY_EMAIL\" < \"\$TMP_MSG_FILE\"";
$f[]="  rm -f \"\$TMP_MSG_FILE\"";
$f[]="fi";
@file_put_contents("/bin/artica-zarafa-backup.sh", @implode("\n", $f));
@chmod("/bin/artica-zarafa-backup.sh",0755);
	
}