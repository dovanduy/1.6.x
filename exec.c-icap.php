<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["BY_SYSLOG"]=false;
$GLOBALS["ALL"]=false;
$GLOBALS["SCHEDULE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--syslog#",implode(" ",$argv),$re)){$GLOBALS["BY_SYSLOG"]=true;}
if(preg_match("#--all#",implode(" ",$argv),$re)){$GLOBALS["ALL"]=true;}
if(preg_match("#--schedule#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE"]=true;}

if($GLOBALS["VERBOSE"]){echo "Loading includes...\n";}


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.c-icap.squidguard.inc');

if($argv[1]=="--memboost"){memboost();exit;}
if($argv[1]=="--template"){gen_template();reconfigure();exit;}
if($argv[1]=="--db-maintenance"){dbMaintenance();exit;}
if($argv[1]=="--maint-schedule"){dbMaintenanceSchedule();exit;}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit;}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--purge"){$GLOBALS["OUTPUT"]=true;purge();die();}
if($argv[1]=="--webf"){$GLOBALS["OUTPUT"]=true;webfilter();die();}
if($argv[1]=="--webdb"){$GLOBALS["OUTPUT"]=true;webdbs();die();}

if($GLOBALS["VERBOSE"]){echo "????\n";}


function reload($aspid=false){
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if(!$aspid){
		if($unix->process_exists($pid)){
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
			return;
		}
		@file_put_contents($pidfile,getmypid());
	}

	$echo=$unix->find_program("echo");
	if(!is_running()){
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service not running...\n";
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap Starting C-ICAP service...\n";
			start(true);
			return;
	}
	$PID=PID_NUM();
	$PROCESS_TTL=$unix->PROCESS_TTL($PID);
	checkFilesAndSecurity();
	echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service running since {$PROCESS_TTL}Mn\n";
	shell_exec("$echo -n \"reconfigure\" > /var/run/c-icap/c-icap.ctl");
	
}

function checkFilesAndSecurity(){
	$unix=new unix();
	if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." [INIT]: c-icap check files and security\n";}
	$owned[]="/var/lib/squidguard";
	$owned[]="/home/c-icap/blacklists";
	$owned[]="/usr/lib/c_icap";
	$owned[]="/var/run/c-icap";
	$owned[]="/var/lib/c_icap/temporary";
	
	while (list ($num, $directory) = each ($owned) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." [INIT]: c-icap $directory\n";}
		if(!is_dir($directory)){
			@mkdir($directory,0755,true);
		}
		@chmod($directory,0755);
		$unix->chmod_func(0755, "$directory/*");
		$unix->chown_func("squid","squid", "$directory/*");
	
	}
	
	
	$filesowned[]="/usr/etc/c-icap.magic";
	
	while (list ($num, $filepath) = each ($filesowned) ){
		if($GLOBALS["OUTPUT"]){echo "Preparing.....: ".date("H:i:s")." [INIT]: c-icap $filepath\n";}
		if(!is_file($filepath)){
			@touch($filepath);
		}
		@chmod($filepath,0755);
		$unix->chmod_func(0755, "$filepath*");
		$unix->chown_func("squid","squid", "$filepath");
	
	}
	
}

function build($aspid=false){
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if(!$aspid){
		if($unix->process_exists($pid)){
			echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
			return;
		}
		@file_put_contents($pidfile,getmypid());
	}
	
	$chmod=$unix->find_program("chmod");
	$cicap=new cicap();
	$cicap->buildconf();
	$ln=$unix->find_program("ln");
	if(is_file("/opt/kaspersky/khse/libexec/libframework.so")){if(!is_file("/lib/libframework.so")){shell_exec("$ln -s /opt/kaspersky/khse/libexec/libframework.so /lib/libframework.so");}}
	if(is_file("/opt/kaspersky/khse/libexec/libyaml-cpp.so.0.2")){if(!is_file("/lib/libyaml-cpp.so.0.2")){shell_exec("$ln -s /opt/kaspersky/khse/libexec/libyaml-cpp.so.0.2 /lib/libyaml-cpp.so.0.2");}}	
	
	if(!is_file("/lib/libbz2.so.1.0")){
		if(is_file("/usr/lib/c_icap/libbz2.so.1.0.4")){
			shell_exec("$ln -s /usr/lib/c_icap/libbz2.so.1.0.4 /lib/libbz2.so.1.0");
		}
	}
	
	
	if(is_dir("/opt/kaspersky/khse/libexec")){
		shell_exec("$chmod 755 /opt/kaspersky/khse/libexec >/dev/null 2>&1");
		shell_exec("$chmod -R 755 /opt/kaspersky/khse/libexec/ >/dev/null 2>&1");
		@unlink("/opt/kaspersky/khse/etc/notify/object_infected");
		shell_exec("$ln -s /opt/kaspersky/kav4proxy/share/notify/object_infected /opt/kaspersky/khse/etc/notify/object_infected");
	}
	
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$unix->SystemCreateUser("clamav","clamav");
	if(!$unix->SystemUserExists("squid")){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service creating user `squid`\n";
		$unix->SystemCreateUser("squid","squid");
	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service user `squid` exists...\n";
	}
	
	
	if(is_file($squidbin)){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service squid binary is `$squidbin`\n";
	}
	
	if(is_file($squidbin)){
		$squid=new squidbee();
		$conf=$squid->BuildSquidConf();
		memboost();
		$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service reconfigure squid done...\n";
		@file_put_contents($SQUID_CONFIG_PATH,$conf);
	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service skip reconfigure squid (not installed)...\n";
	}
	
	@mkdir("/usr/etc",0755,true);
	CicapMagic("/usr/etc/c-icap.magic");
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service generate template...\n";
	gen_template();
	if(is_file($squidbin)){
		dbMaintenanceSchedule();
	}
	
}


function dbMaintenance(){
	$sock=new sockets();
	$unix=new unix();
	$users=new usersMenus();
	$verbose=$GLOBALS["VERBOSE"];
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	if(!$users->SQUIDGUARD_INSTALLED){
		if(!$users->APP_UFDBGUARD_INSTALLED){
			if($verbose){echo "SQUIDGUARD_INSTALLED  =  FALSE\n";}
		}
		return; 
	}
	
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Already instance ".@file_get_contents($pidfile)." exists\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());	
	
	
	$db_recover=$unix->LOCATE_DB_RECOVER();
	$db_stat=$unix->LOCATE_DB_STAT();
	
	if(strlen($db_recover)<3){
		echo "db_recover no such file\n";
		return;
	}
	
if($verbose){echo "db_recover:$db_recover\n";}
if($verbose){echo "db_stat:$db_stat\n";}
	
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";

	
	echo "Stopping c-icap\n";
	shell_exec("/etc/init.d/artica-postfix stop cicap");
		
	echo "Checking databases used\n";
	
	$datas=explode("\n",@file_get_contents("/etc/c-icap.conf"));
	while (list ($num, $line) = each ($datas)){
		if(preg_match("#url_check\.LoadSquidGuardDB\s+(.+?)\s+(.+)#",$line,$re)){
			$dir=trim($re[2]);
			
			if(substr($dir,strlen($dir)-1,1)=='/'){$dir=substr($dir,0,strlen($dir)-1);}
			$array[$dir]=$re[1];
		}
		
		
	}
	
	$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
	while (list ($num, $line) = each ($datas)){
		if(preg_match("#domainlist\s+(.+)#",$line,$re)){
			$re[1]=trim($re[1]);
			$re[1]=dirname($re[1]);
			$dir="/var/lib/squidguard/".trim($re[1]);
			if(substr($dir,strlen($dir)-1,1)=='/'){$dir=substr($dir,0,strlen($dir)-1);}
			$array[$dir]="SquidGuard DB {$re[1]}";
		}
		
		
	}	
	
	if(!is_array($array)){
		echo "No databases, aborting\n";
		return;
	}
	
	while (list ( $directory,$dbname) = each ($array)){
		echo "\nChecking DB $dbname in $directory\n==============================\n";
		$cmd="$db_recover -h $directory/ -v 2>&1";
		if($verbose){echo "$cmd\n";}
		exec($cmd,$results);
		if($verbose){$LOGS[]=$cmd;}
		$LOGS[]="\nmaintenance on $dbname\n==============================\n".@implode("\n",$results);
		unset($results);
		if(is_file("$directory/urls.db")){
			$cmd="$db_stat -d $directory/urls.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/urls.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{
			$LOGS[]="\nstatistics on $directory/urls.db no such file";		
		}
		
		if(is_file("$directory/domains.db")){
			$cmd="$db_stat -d $directory/domains.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/domains.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{
			$LOGS[]="\nstatistics on $directory/domains.db no such file";		
		}
		
		if(is_file("$directory/expressions.db")){
			$cmd="$db_stat -d $directory/expressions.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/expressions.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{
					
		}		
		
	}

	sys_THREAD_COMMAND_SET("/etc/init.d/artica-postfix restart cicap");
	
	
	send_email_events("Maintenance on Web Proxy urls Databases: ". count($array)." database(s)",@implode("\n",$LOGS)."\n","system");
	if($verbose){echo @implode("\n",$LOGS)."\n";}	

	
}

function dbMaintenanceSchedule(){
	if(is_file("/etc/cron.d/artica-cron-squidguarddb")){@unlink("/etc/cron.d/artica-cron-squidguarddb");}
	return;
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());	
	
	@unlink("/etc/crond.d/artica-cron-squidguarddb");
	$users=new usersMenus();
	if(!$users->SQUIDGUARD_INSTALLED){
		if(!$users->APP_UFDBGUARD_INSTALLED){
			writelogs("SQUIDGUARD_INSTALLED -> FALSE",__FUNCTION__,__FILE__,__LINE__);
			return null;
	}}
	$sock=new sockets();
	$time=unserialize(base64_decode($sock->GET_INFO("SquidGuardMaintenanceTime")));	
	if($time["DBH"]==null){$time["DBH"]=23;}
	if($time["DBM"]==null){$time["DBM"]=45;}	
	
	$h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
	$h[]="MAILTO=\"\"";
	$h[]="{$time["DBM"]} {$time["DBH"]} * * *  root ". LOCATE_PHP5_BIN2()." ".__FILE__." --db-maintenance";
	$h[]="";
	@file_put_contents("/etc/cron.d/artica-cron-squidguarddb",@implode("\n",$h));
	writelogs("/etc/crond.d/artica-cron-squidguarddb DONE",__FUNCTION__,__FILE__,__LINE__);
	@chmod("/etc/crond.d/artica-cron-squidguarddb",640);
	shell_exec("/bin/chown root:root /etc/cron.d/artica-cron-squidguarddb");
}

function CicapMagic($path){
$f[]="# In this file defined the types of files and the groups of file types. ";
$f[]="# The predefined data types, which are not included in this file, ";
$f[]="# are ASCII, ISO-8859, EXT-ASCII, UTF (not implemented yet), HTML ";
$f[]="# which are belongs to TEXT predefined group and BINARY which ";
$f[]="# belongs to DATA predefined group.";
$f[]="#";
$f[]="# The line format of magic file is:";
$f[]="#";
$f[]="# offset:Magic:Type:Short Description:Group1[:Group2[:Group3]...]";
$f[]="#";
$f[]="# CURRENT GROUPS are :TEXT DATA EXECUTABLE ARCHIVE GRAPHICS STREAM DOCUMENT";
$f[]="";
$f[]="0:MZ:MSEXE:DOS/W32 executable/library/driver:EXECUTABLE";
$f[]="0:LZ:DOSEXE:MS-DOS executable:EXECUTABLE";
$f[]="0:\177ELF:ELF:ELF unix executable:EXECUTABLE";
$f[]="0:\312\376\272\276:JavaClass:Compiled Java class:EXECUTABLE";
$f[]="";
$f[]="#Archives";
$f[]="0:Rar!:RAR:Rar archive:ARCHIVE";
$f[]="0:PK\003\004:ZIP:Zip archive:ARCHIVE";
$f[]="0:PK00PK\003\004:ZIP:Zip archive:ARCHIVE";
$f[]="0:\037\213:GZip:Gzip compressed file:ARCHIVE";
$f[]="0:BZh:BZip:BZip compressed file:ARCHIVE";
$f[]="0:SZDD:Compress.exe:MS Copmress.exe'd compressed data:ARCHIVE";
$f[]="0:\037\235:Compress:UNIX compress:ARCHIVE";
$f[]="0:MSCF:MSCAB:Microsoft cabinet file:ARCHIVE";
$f[]="257:ustar:TAR:Tar archive file:ARCHIVE";
$f[]="0:\355\253\356\333:RPM:Linux RPM file:ARCHIVE";
$f[]="#Other type of Archives";
$f[]="0:ITSF:MSCHM:MS Windows Html Help:ARCHIVE";
$f[]="0:!<arch>\012debian:debian:Debian package:ARCHIVE";
$f[]="";
$f[]="# Graphics";
$f[]="0:GIF8:GIF:GIF image data:GRAPHICS";
$f[]="0:BM:BMP:BMP image data:GRAPHICS";
$f[]="0:\377\330:JPEG:JPEG image data:GRAPHICS";
$f[]="0:\211PNG:PNG:PNG image data:GRAPHICS";
$f[]="0:\000\000\001\000:ICO:MS Windows icon resource:GRAPHICS";
$f[]="0:FWS:SWF:Shockwave Flash data:GRAPHICS";
$f[]="0:CWS:SWF:Shockwave Flash data:GRAPHICS";
$f[]="";
$f[]="#STREAM";
$f[]="0:\000\000\001\263:MPEG:MPEG video stream:STREAM";
$f[]="0:\000\000\001\272:MPEG::STREAM";
$f[]="0:RIFF:RIFF:RIFF video/audio stream:STREAM";
$f[]="0:OggS:OGG:Ogg Stream:STREAM";
$f[]="0:ID3:MP3:MP3 audio stream:STREAM";
$f[]="0:\377\373:MP3:MP3 audio stream:STREAM";
$f[]="0:\377\372:MP3:MP3 audio stream:STREAM";
$f[]="0:\060\046\262\165\216\146\317:ASF:WMA/WMV/ASF:STREAM";
$f[]="0:.RMF:RMF:Real Media File:STREAM";
$f[]="";
$f[]="#Responce from stream server :-)";
$f[]="0:ICY 200 OK:ShouthCast:Shouthcast audio stream:STREAM";
$f[]="";
$f[]="#Documents";
$f[]="0:\320\317\021\340\241\261\032\341:MSOFFICE:MS Office Document:DOCUMENT";
$f[]="0:\208\207\017\224\161\177\026\225\000:MSOFFICE::DOCUMENT";
$f[]="4:Standard Jet DB:MSOFFICE:MS Access Database:DOCUMENT";
$f[]="0:%PDF-:PDF:PDF document:DOCUMENT";
$f[]="0:%!:PS:PostScript document:DOCUMENT";
$f[]="";
$f[]="";
echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service $path done...\n";
@file_put_contents($path,@implode("\n",$f));
}

function gen_template($reconfigure=false){
	if(isset($_GET["gen_templatecicap"])){return;}
	$_GET["gen_templatecicap"]=true;
	$path=base64_decode("L3Vzci9zaGFyZS9jX2ljYXAvdGVtcGxhdGVzL3ZpcnVzX3NjYW4vZW4vVklSVVNfRk9VTkQ=");
	if(!class_exists("usersMenus")){include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");}
	$users=new usersMenus();
	if($users->CORP_LICENSE){
		$sock=new sockets();
		$CiCApErrorPage=base64_decode($sock->GET_INFO("CiCApErrorPage"));
		if($CiCApErrorPage<>null){
			@file_put_contents($path, $CiCApErrorPage);	
			if(!is_file($path)){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service template failed\n";}
			return;
		}
		
	}
	
$template="YTo4MTp7aTowO3M6NjoiPGh0bWw+IjtpOjE7czo2OiI8aGVhZD4iO2k6MjtzOjY3OiIJPG1ldGEgaHR0cC1lcXVpdj0nY29udGVudC10eXBlJyBjb250ZW50PSd0ZXh0L2h0bWw7Y2hhcnNldD11dGYtOCc+IjtpOjM7czoyNzoiCTx0aXRsZT5WSVJVUyBGT1VORDwvdGl0bGU+IjtpOjQ7czoyNDoiCTxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+IjtpOjU7czoyMzoiCWh0bWwsYm9keXtoZWlnaHQ6MTAwJX0iO2k6NjtzOjY6Iglib2R5eyI7aTo3O3M6NjE6Igl3aWR0aDoxMDAlO21pbi1oZWlnaHQ6MTAwJTttYXJnaW46MDtwYWRkaW5nOjA7Y29sb3I6IzJDMkMyQzsiO2k6ODtzOjMzOiIJZm9udC1mYW1pbHk6J1RhaG9tYScsICdWZXJkYW5hJzsiO2k6OTtzOjE2OiIJZm9udC1zaXplOjExcHg7IjtpOjEwO3M6MTY6IgliYWNrZ3JvdW5kOiNGRkYiO2k6MTE7czoyOiIJfSI7aToxMjtzOjE1OiIJZm9ybXttYXJnaW46MH0iO2k6MTM7czo0NDoiCXRhYmxlLGlucHV0LHNlbGVjdHtmb250Om5vcm1hbCAxMDAlIHRhaG9tYX0iO2k6MTQ7czoyMzoiCWltZ3tib3JkZXI6MDttYXJnaW46MH0iO2k6MTU7czozMjoiCXRhYmxle2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZX0iO2k6MTY7czoxNzoiCWF7Y29sb3I6IzYyNzA3RH0iO2k6MTc7czozMToiCS50LHRyLnQgdGR7dmVydGljYWwtYWxpZ246dG9wfSI7aToxODtzOjI2OiIJLm17dmVydGljYWwtYWxpZ246bWlkZGxlfSI7aToxOTtzOjM0OiIJLmIsdHIuYiB0ZHt2ZXJ0aWNhbC1hbGlnbjpib3R0b219IjtpOjIwO3M6NDM6Igl0ci50IHRkIHRkLHRyLmIgdGQgdGR7dmVydGljYWwtYWxpZ246YXV0b30iO2k6MjE7czoyMDoiCS5se3RleHQtYWxpZ246bGVmdH0iO2k6MjI7czoyMjoiCS5je3RleHQtYWxpZ246Y2VudGVyfSI7aToyMztzOjIxOiIJLnJ7dGV4dC1hbGlnbjpyaWdodH0iO2k6MjQ7czoyNjoiCS5ub2Jye3doaXRlLXNwYWNlOm5vd3JhcH0iO2k6MjU7czoyNDoiCS5yZWx7cG9zaXRpb246cmVsYXRpdmV9IjtpOjI2O3M6MjQ6IgkuYWJze3Bvc2l0aW9uOmFic29sdXRlfSI7aToyNztzOjE2OiIJLmZse2Zsb2F0OmxlZnR9IjtpOjI4O3M6MTc6IgkuZnJ7ZmxvYXQ6cmlnaHR9IjtpOjI5O3M6MTY6IgkuY2x7Y2xlYXI6Ym90aH0iO2k6MzA7czoxODoiCS53MTAwe3dpZHRoOjEwMCV9IjtpOjMxO3M6MTk6IgkuaDEwMHtoZWlnaHQ6MTAwJX0iO2k6MzI7czoyNToiCWJpZywuYmlne2ZvbnQtc2l6ZToxMjUlfSI7aTozMztzOjI4OiIJc21hbGwsLnNtYWxse2ZvbnQtc2l6ZTo5NSV9IjtpOjM0O3M6NDI6IgkubWljcm97Y29sb3I6I0RERDtmb250Om5vcm1hbCA5cHggdGFob21hfSI7aTozNTtzOjM1OiIJaDF7Zm9udDpib2xkIDIwcHggYXJpYWw7IG1hcmdpbjowfSI7aTozNjtzOjM1OiIJaDR7Zm9udDpib2xkIDEycHggYXJpYWw7IG1hcmdpbjowfSI7aTozNztzOjU3OiIJcHt0ZXh0LWFsaWduOmp1c3RpZnk7bGluZS1oZWlnaHQ6MS4zO21hcmdpbjowIDAgMC41ZW0gMH0iO2k6Mzg7czoyNToiCS56e2JvcmRlcjoxcHggc29saWQgcmVkfSI7aTozOTtzOjQ4OiIJLmgxcHh7aGVpZ2h0OjFweDtmb250LXNpemU6MXB4O2xpbmUtaGVpZ2h0OjFweH0iO2k6NDA7czozNjoiCXVse21hcmdpbjo2cHggMCA2cHggMjBweDtwYWRkaW5nOjB9IjtpOjQxO3M6MjA6Igl1bCBsaXttYXJnaW46M3B4IDB9IjtpOjQyO3M6ODoiPC9zdHlsZT4iO2k6NDM7czo3OiI8L2hlYWQ+IjtpOjQ0O3M6OToiPGJvZHk+77u/IjtpOjQ1O3M6MjU6Ijx0YWJsZSBjbGFzcz0idzEwMCBoMTAwIj4iO2k6NDY7czo1OiIJPHRyPiI7aTo0NztzOjE4OiIJCTx0ZCBjbGFzcz0iYyBtIj4iO2k6NDg7czo1NzoiCQkJPHRhYmxlIHN0eWxlPSJtYXJnaW46MCBhdXRvO2JvcmRlcjpzb2xpZCAxcHggIzU2MDAwMCI+IjtpOjQ5O3M6ODoiCQkJCTx0cj4iO2k6NTA7czozOToiCQkJCQk8dGQgY2xhc3M9ImwiIHN0eWxlPSJwYWRkaW5nOjFweCI+IjtpOjUxO3M6NTA6IgkJCQkJCTxkaXYgc3R5bGU9IndpZHRoOjM0NnB4O2JhY2tncm91bmQ6I0UzMzYzMCI+IjtpOjUyO3M6MzI6IgkJCQkJCQk8ZGl2IHN0eWxlPSJwYWRkaW5nOjNweCI+IjtpOjUzO3M6ODU6IgkJCQkJCQkJPGRpdiBzdHlsZT0iYmFja2dyb3VuZDojQkYwQTBBO3BhZGRpbmc6OHB4O2JvcmRlcjpzb2xpZCAxcHggI0ZGRjtjb2xvcjojRkZGIj4iO2k6NTQ7czozMDoiCQkJCQkJCQkJPGg0PlZpcnVzIEZvdW5kOjwvaDQ+IjtpOjU1O3M6MjI6IgkJCQkJCQkJCTxoMT4lVlZOPC9oMT4iO2k6NTY7czoxNDoiCQkJCQkJCQk8L2Rpdj4iO2k6NTc7czoxMjE6IgkJCQkJCQkJPGRpdiBjbGFzcz0iYyIgc3R5bGU9ImZvbnQ6Ym9sZCAxM3B4IGFyaWFsO3RleHQtdHJhbnNmb3JtOnVwcGVyY2FzZTtjb2xvcjojRkZGO3BhZGRpbmc6OHB4IDAiPkFjY2VzcyBkZW5pZWQ8L2Rpdj4iO2k6NTg7czo2MzoiCQkJCQkJCQk8ZGl2IHN0eWxlPSJiYWNrZ3JvdW5kOiNGN0Y3Rjc7cGFkZGluZzoyMHB4IDI4cHggMzZweCI+IjtpOjU5O3M6NDg6IlRoZSByZXF1ZXN0ZWQgVVJMIGNhbm5vdCBiZSBwcm92aWRlZDxicj48YnI+IDxiPiI7aTo2MDtzOjU3OiJUaGUgcmVxdWVzdGVkIG9iamVjdCBhdCB0aGUgVVJMOjwvYj48YnI+PGJyPiVodW88YnI+PGJyPiAiO2k6NjE7czo3MzoiPGI+VmlydXMgZGV0ZWN0ZWQ6PC9iPjxicj4gPGJyPjxkaXYgc3R5bGU9J2ZvbnQtc2l6ZToxMnB4Jz4lVlZOPC9kaXY+PGJyPiI7aTo2MjtzOjgwOiI8L2k+PHN0cm9uZz5UaGlzIG1lc3NhZ2UgZ2VuZXJhdGVkIGJ5IEMtSUNBUCBzZXJ2aWNlOjwvYj46Jm5ic3A7PC9zdHJvbmc+JWl1PC9pPiI7aTo2MztzOjY6IjwvZGl2PiI7aTo2NDtzOjU4OiIJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0Y3RjdGNztwYWRkaW5nOjAgMnB4IDJweCI+IjtpOjY1O3M6NjQ6IgkJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0U5RTlFOTtwYWRkaW5nOjEycHggMzBweCAxNHB4Ij4iO2k6NjY7czoxNDE6IgkJCQkJCQkJCTxiPkNsYW1hdiBhbnRpdmlydXMgZW5naW5lOiA8Yj4gJVZWViA8L2I+PC9hPjwvYj4gPGJyPgoJCQkJCQkJCTxhIGhyZWY9Imh0dHA6Ly9wcm94eS1hcHBsaWFuY2Uub3JnIj5BYm91dCBBcnRpY2EgUHJveHkgQXBwbGlhbmNlPC9hPiI7aTo2NztzOjE0OiIJCQkJCQkJCTwvZGl2PiI7aTo2ODtzOjE0OiIJCQkJCQkJCTwvZGl2PiI7aTo2OTtzOjEzOiIJCQkJCQkJPC9kaXY+IjtpOjcwO3M6MTI6IgkJCQkJCTwvZGl2PiI7aTo3MTtzOjEwOiIJCQkJCTwvdGQ+IjtpOjcyO3M6OToiCQkJCTwvdHI+IjtpOjczO3M6MTE6IgkJCTwvdGFibGU+IjtpOjc0O3M6NzoiCQk8L3RkPiI7aTo3NTtzOjY6Igk8L3RyPiI7aTo3NjtzOjg6IjwvdGFibGU+IjtpOjc3O3M6MDoiIjtpOjc4O3M6MDoiIjtpOjc5O3M6NzoiPC9ib2R5PiI7aTo4MDtzOjc6IjwvaHRtbD4iO30=";
$f=unserialize(base64_decode($template));
@file_put_contents($path, @implode("\n", $f));	
if(!is_file($path)){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service template failed\n";return;}
echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service template $path done\n";
	
}

function is_running(){
	$unix=new unix();
	$binpath=$unix->find_program("c-icap");
	$master_pid=trim(@file_get_contents("/var/run/c-icap/c-icap.pid"));
	if($master_pid==null){$master_pid=$unix->PIDOF($binpath);}
	if(!$unix->process_exists($master_pid)){$master_pid=$unix->PIDOF($binpath);}
	
	if(!$unix->process_exists($master_pid)){return false;}
	return true;	
}

function reconfigure(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	checkFilesAndSecurity();
	if(!is_running()){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service not running...\n";return;}
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service reconfigure service...\n"; 
	shell_exec("$echo -n \"reconfigure\" > /var/run/c-icap/c-icap.ctl");
	
}
function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("c-icap"));
}
//##############################################################################
function PID_PATH(){
	return '/var/run/c-icap/c-icap.pid';
}
//##############################################################################
function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	start(true);

}
//##############################################################################
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["CLASS_SOCKETS"]=$sock;
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$cicapbin=$unix->find_program("c-icap");
	if(!is_file($cicapbin)){
		$nohup=$unix->find_program("nohup");
		if(is_file("/home/artica/c-icap.tar.gz.old")){
			$tar=$unix->find_program("tar");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service re-install C-ICAP...\n";}
			shell_exec("$tar xf /home/artica/c-icap.tar.gz.old -C /");
		}else{
			if($GLOBALS["VERBOSE"]){echo "/home/artica/c-icap.tar.gz.old no such file\n";}
			if(is_dir("/var/run/c-icap")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service compile C-ICAP...\n";}
				shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
			}else{
				if($GLOBALS["VERBOSE"]){echo "/var/run/c-icap no such dir\n";}
				$CicapEnabled=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled");
				if($CicapEnabled==1){
					if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service compile C-ICAP...\n";}
					shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_C_ICAP >/dev/null 2>&1 &");
				}
			}
		}
	}	
	
	
	$daemonbin=$unix->find_program("c-icap");
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service, not installed\n";}
		return;
	}



	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service already started $pid since {$timepid}Mn...\n";}
		return;
	}

	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	if(is_file("/etc/artica-postfix/WEBSTATS_APPLIANCE")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service WebStats Appliance..\n";}	
		$CicapEnabled=1;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Proxy service enabled:$SQUIDEnable\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service C-ICAP service enabled:$CicapEnabled\n";}
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$CicapEnabled=0;}
	
	
	
	if($CicapEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service disabled ( see CicapEnabled )\n";}
		return;
	}


	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$tmpdir=$unix->TEMP_DIR();
	build(true);
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Generate template..\n";
	gen_template();
	@chmod("/var/run",0777);
	@mkdir("/var/run/c-icap",0755,true);
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Apply permissions..\n";
	$unix->chown_func("squid", "squid","/var/run/c-icap");
	libicapapi();
	$rm=$unix->find_program("rm");

	shell_exec("$rm -f /var/lib/c_icap/temporary/* >/dev/null 2>&1");
	
	$cmd="$nohup $daemonbin -f /etc/c-icap.conf -d 10 >$tmpdir/c_icap_start 2>&1 &";
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service run daemon\n";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<6;$i++){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting $i/6...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Success service started pid:$pid...\n";}
		@unlink("$tmpdir/c_icap_start");
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service failed analyze...\n";}
	$f=explode("\n",@file_get_contents("$tmpdir/c_icap_start"));
	while (list ( $index,$line) = each ($f)){
		$line=trim($line);
		if($line==null){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service $line\n";}
		if(preg_match("#error while loading shared libraries#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." **************************************************\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service please re-install package...\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." **************************************************\n";}
			$sock->SET_INFO("CicapEnabled",0);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service restarting watchdogs\n";}
			shell_exec("/etc/init.d/monit restart");
			shell_exec("/etc/init.d/artica-status reload");
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}


	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}

}
//##############################################################################
function purge($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	
	$workdir="/var/lib/c_icap/temporary";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$rm=$unix->find_program("rm");
	if($GLOBALS["VERBOSE"]){
		echo "pidfile: $pidfile\n";
		echo "pidTime: $pidTime\n";
	}
	
	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		
	}
if(!$GLOBALS["FORCE"]){	
	if(isset($GLOBALS["SCHEDULE"])){
		$Time=$unix->file_time_min($pidTime);
		if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn\n";}
		if($Time<20){
			if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn < 20 -> Die();\n";}
			return;}
		
	}
}

if($GLOBALS["FORCE"]){
	$Time=$unix->file_time_min($pidTime);
	if($Time<1){
		if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn < 1 -> Die();\n";}
		return;
	}
}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	$MaxCICAPWorkTimeMin=$sock->GET_INFO("MaxCICAPWorkTimeMin");
	$MaxCICAPWorkSize=$sock->GET_INFO("MaxCICAPWorkSize");
	if(!is_numeric($MaxCICAPWorkTimeMin)){$MaxCICAPWorkTimeMin=1440;}
	if(!is_numeric($MaxCICAPWorkSize)){$MaxCICAPWorkSize=5000;}
	$size=round($unix->DIRSIZE_KO($workdir)/1024,2);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service `$workdir` {$size}MB/$MaxCICAPWorkSize\n";}
	
	
	@file_put_contents($pidfile, getmypid());
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$sync=$unix->find_program("sync");

	if($size>$MaxCICAPWorkSize){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$size}MB exceed size!\n";}
		squid_admin_mysql(0, "C-ICAP: `$workdir` {$size}MB exceed size!","Artica will remove all files..\n",__FILE__,__LINE__);
		shell_exec("$rm $workdir/*");
		shell_exec($sync);
		stop(true);
		start(true);
		squid_admin_mysql(2, "Reconfiguring proxy service\n",__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		return;
	}
	
	if($GLOBALS["ALL"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$size}MB exceed size!\n";}
		squid_admin_mysql(0, "C-ICAP: `$workdir` {$size}MB exceed size!","Artica will remove all files..\n",__FILE__,__LINE__);
		shell_exec("$rm $workdir/*");
		shell_exec($sync);
		stop(true);
		start(true);
		squid_admin_mysql(2, "Reconfiguring proxy service\n",__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		return;		
	}
	
	
	if (!$handle = opendir($workdir)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		if(is_dir($workdir)){continue;}
		$path="$workdir/$file";
		$size=@filesize($path);
		$size=$size/1024;
		$size=$size/1024;
		if($unix->is_socket($path)){continue;}
		$time=$unix->file_time_min($path);
		if($GLOBALS["ALL"]){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service removing `$path` ( {$size}M )\n";}
			@unlink($path);
			continue;
		}
		
		if($time>$MaxCICAPWorkTimeMin){
			squid_admin_mysql(1, "C-ICAP: Removing temporary file $path ( {$time}Mn/{$size}M )", "It exceed rule of {$MaxCICAPWorkTimeMin}Mn ( {$time}Mn )",__FILE__,__LINE__);
			@unlink($path);
			continue;
		}
	}
	
	$REMOVED=false;
	$workdir="/var/clamav/tmp";
	if(is_dir($workdir)){
		if (!$handle = opendir($workdir)) {return;}
		while (false !== ($file = readdir($handle))) {
			if ($file == "."){continue;}
			if ($file == ".."){continue;}
			if(is_dir($workdir)){continue;}
			$path="$workdir/$file";
			$size=@filesize($path);
			$size=$size/1024;
			$size=$size/1024;
			if($unix->is_socket($path)){continue;}
			$time=$unix->file_time_min($path);
			if($GLOBALS["ALL"]){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service removing `$path` ( {$size}M )\n";}
				$REMOVED=true;
				@unlink($path);
				continue;
			}
		
			if($time>$MaxCICAPWorkTimeMin){
				squid_admin_mysql(1, "C-ICAP: Removing temporary file $path ( {$time}Mn/{$size}M )", "It exceed rule of {$MaxCICAPWorkTimeMin}Mn ( {$time}Mn )",__FILE__,__LINE__);
				$REMOVED=true;
				@unlink($path);
				continue;
			}
		}		
	}
	
	if($REMOVED){shell_exec($sync);}
}


function libicapapi(){
	$unix=new unix();
	
	$ln=$unix->find_program("ln");
	
	if(is_file("/usr/lib/libicapapi.so.3.0.1")){
		if(!is_file("/usr/lib/libicapapi.so.3")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap linking libicapapi.so.3.0.1\n";}
			shell_exec("ln -s /usr/lib/libicapapi.so.3.0.1 /usr/lib/libicapapi.so.3");
		}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap libicapapi.so.3.0.1 not found\n";}
	}
	$f[]="/usr/lib/libicapapi.so.2";
}




//##############################################################################
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$kill=$unix->find_program("kill");
	shell_exec("$echo -n \"stop\"  > /var/run/c-icap/c-icap.ctl");

	
	for($i=0;$i<3;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/3...\n";}
		sleep(1);
	}	
	

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service Shutdown pid $pid...\n";}
	
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service success...\n";}
		$GLOBALS["ALL"]=true;
		purge(true);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service failed...\n";}

}


function memboost(){
	
	$workdir="/var/lib/c_icap/temporary";
	
	
	$sock=new sockets();
	$unix=new unix();
	$users=new usersMenus();
	
	
	if(!$users->CORP_LICENSE){
		$umount=$unix->find_program("umount");
		echo "Starting......: ".date("H:i:s")."c-icap MemBoost license inactive...\n";
		$mountedM=tmpfs_mounted_size();
		if($mountedM>1){shell_exec("$umount -l $workdir");}
		return;
	}
	echo "Starting......: ".date("H:i:s")."c-icap MemBoost `$workdir`\n";
	$umount=$unix->find_program("umount");
	$mount=$unix->find_program("mount");
	$rm=$unix->find_program("rm");
	$idbin=$unix->find_program("id");
	$CiCAPMemBoost=$sock->GET_INFO("CiCAPMemBoost");
	if(!is_numeric($CiCAPMemBoost)){$CiCAPMemBoost=0;}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."c-icap MemBoost -> $CiCAPMemBoost Mb\n";}
	$mountedM=tmpfs_mounted_size();
	
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."c-icap mounted -> $mountedM Mb\n";}		 
	if($CiCAPMemBoost<2){
		if($mountedM>1){shell_exec("$umount -l $workdir");}
		return;
	}
	
	if($CiCAPMemBoost==$mountedM){return;}
	$unix->SystemCreateUser("clamav","clamav");
	
	exec("$idbin clamav 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."c-icap MemBoost clamav no such user...\n";return;}
	
	shell_exec("$umount -l $workdir");
	$uid=$re[1];
	$gid=$re[2];	
	recursive_remove_directory("$workdir");
	@mkdir($workdir,0755);	
	echo "Starting......: ".date("H:i:s")."c-icap MemBoost clamav ($uid/$gid)\n";	
	shell_exec("$mount -t tmpfs -o size={$CiCAPMemBoost}M,noauto,user,exec,uid=$uid,gid=$gid tmpfs $workdir");
	$mountedM=tmpfs_mounted_size();
	if($mountedM>1){echo "Starting......: ".date("H:i:s")."c-icap MemBoost mounted with {$mountedM}M\n";}else{
		echo "Starting......: ".date("H:i:s")."c-icap MemBoost mounted failed\n";
	}		
}
	
function tmpfs_mounted_size(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	exec("$mount 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^tmpfs on.*?c_icap\/temporary.*?tmpfs\s+\(.*?size=([0-9]+)M#", $ligne,$re)){return $re[1];}}
	return null;
}

function webfilter(){
	
	$tpl="PGh0bWw+CjxoZWFkPgoJPG1ldGEgaHR0cC1lcXVpdj0nY29udGVudC10eXBlJyBjb250ZW50PSd0ZXh0L2h0bWw7Y2hhcnNldD11dGYtOCc+Cgk8dGl0bGU+QUNDRVNTIERFTklFRDwvdGl0bGU+Cgk8c3R5bGUgdHlwZT0idGV4dC9jc3MiPgoJaHRtbCxib2R5e2hlaWdodDoxMDAlfQoJYm9keXsKCXdpZHRoOjEwMCU7bWluLWhlaWdodDoxMDAlO21hcmdpbjowO3BhZGRpbmc6MDtjb2xvcjojMkMyQzJDOwoJZm9udC1mYW1pbHk6J1RhaG9tYScsICdWZXJkYW5hJzsKCWZvbnQtc2l6ZToxMXB4OwoJYmFja2dyb3VuZDojRkZGCgl9Cglmb3Jte21hcmdpbjowfQoJdGFibGUsaW5wdXQsc2VsZWN0e2ZvbnQ6bm9ybWFsIDEwMCUgdGFob21hfQoJaW1ne2JvcmRlcjowO21hcmdpbjowfQoJdGFibGV7Ym9yZGVyLWNvbGxhcHNlOmNvbGxhcHNlfQoJYXtjb2xvcjojNjI3MDdEfQoJLnQsdHIudCB0ZHt2ZXJ0aWNhbC1hbGlnbjp0b3B9CgkubXt2ZXJ0aWNhbC1hbGlnbjptaWRkbGV9CgkuYix0ci5iIHRke3ZlcnRpY2FsLWFsaWduOmJvdHRvbX0KCXRyLnQgdGQgdGQsdHIuYiB0ZCB0ZHt2ZXJ0aWNhbC1hbGlnbjphdXRvfQoJLmx7dGV4dC1hbGlnbjpsZWZ0fQoJLmN7dGV4dC1hbGlnbjpjZW50ZXJ9Cgkucnt0ZXh0LWFsaWduOnJpZ2h0fQoJLm5vYnJ7d2hpdGUtc3BhY2U6bm93cmFwfQoJLnJlbHtwb3NpdGlvbjpyZWxhdGl2ZX0KCS5hYnN7cG9zaXRpb246YWJzb2x1dGV9CgkuZmx7ZmxvYXQ6bGVmdH0KCS5mcntmbG9hdDpyaWdodH0KCS5jbHtjbGVhcjpib3RofQoJLncxMDB7d2lkdGg6MTAwJX0KCS5oMTAwe2hlaWdodDoxMDAlfQoJYmlnLC5iaWd7Zm9udC1zaXplOjEyNSV9CglzbWFsbCwuc21hbGx7Zm9udC1zaXplOjk1JX0KCS5taWNyb3tjb2xvcjojREREO2ZvbnQ6bm9ybWFsIDlweCB0YWhvbWF9CgloMXtmb250OmJvbGQgMjBweCBhcmlhbDsgbWFyZ2luOjB9CgloNHtmb250OmJvbGQgMTJweCBhcmlhbDsgbWFyZ2luOjB9Cglwe3RleHQtYWxpZ246anVzdGlmeTtsaW5lLWhlaWdodDoxLjM7bWFyZ2luOjAgMCAwLjVlbSAwfQoJLnp7Ym9yZGVyOjFweCBzb2xpZCByZWR9CgkuaDFweHtoZWlnaHQ6MXB4O2ZvbnQtc2l6ZToxcHg7bGluZS1oZWlnaHQ6MXB4fQoJdWx7bWFyZ2luOjZweCAwIDZweCAyMHB4O3BhZGRpbmc6MH0KCXVsIGxpe21hcmdpbjozcHggMH0KPC9zdHlsZT4KPC9oZWFkPgo8Ym9keT7vu78KPHRhYmxlIGNsYXNzPSJ3MTAwIGgxMDAiPgoJPHRyPgoJCTx0ZCBjbGFzcz0iYyBtIj4KCQkJPHRhYmxlIHN0eWxlPSJtYXJnaW46MCBhdXRvO2JvcmRlcjpzb2xpZCAxcHggIzU2MDAwMCI+CgkJCQk8dHI+CgkJCQkJPHRkIGNsYXNzPSJsIiBzdHlsZT0icGFkZGluZzoxcHgiPgoJCQkJCQk8ZGl2IHN0eWxlPSJ3aWR0aDozNDZweDtiYWNrZ3JvdW5kOiNFMzM2MzAiPgoJCQkJCQkJPGRpdiBzdHlsZT0icGFkZGluZzozcHgiPgoJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0JGMEEwQTtwYWRkaW5nOjhweDtib3JkZXI6c29saWQgMXB4ICNGRkY7Y29sb3I6I0ZGRiI+CgkJCQkJCQkJCTxoND5BY2Nlc3MgZGVuaWVkOjwvaDQ+CgkJCQkJCQkJCTxoMT4lVUIgKCVVRCk8L2gxPgoJCQkJCQkJCTwvZGl2PgoJCQkJCQkJCTxkaXYgY2xhc3M9ImMiIHN0eWxlPSJmb250OmJvbGQgMTNweCBhcmlhbDt0ZXh0LXRyYW5zZm9ybTp1cHBlcmNhc2U7Y29sb3I6I0ZGRjtwYWRkaW5nOjhweCAwIj5BY2Nlc3MgZGVuaWVkPC9kaXY+CgkJCQkJCQkJPGRpdiBzdHlsZT0iYmFja2dyb3VuZDojRjdGN0Y3O3BhZGRpbmc6MjBweCAyOHB4IDM2cHgiPgpUaGUgcmVxdWVzdGVkIFVSTCBjYW5ub3QgYmUgcHJvdmlkZWQ8YnI+PGJyPiA8Yj4KVGhlIHJlcXVlc3RlZCBvYmplY3QgYXQgdGhlIFVSTDo8L2I+PGJyPjxicj4lVVU8YnI+PGJyPiAKPGI+SXMgbm90IHBlcm1pdHRlZDo8L2I+PGJyPiA8YnI+PGRpdiBzdHlsZT0nZm9udC1zaXplOjEycHgnPiVVTTwvZGl2Pjxicj4KPC9pPjxzdHJvbmc+VGhpcyBtZXNzYWdlIGdlbmVyYXRlZCBieSBJQ0FQIHNlcnZpY2U6PC9iPjombmJzcDs8L3N0cm9uZz4laXU8L2k+CjwvZGl2PgoJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0Y3RjdGNztwYWRkaW5nOjAgMnB4IDJweCI+CgkJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0U5RTlFOTtwYWRkaW5nOjEycHggMzBweCAxNHB4Ij4KCQkJCQkJCQkJPGJyPgoJCQkJCQkJCTxhIGhyZWY9Imh0dHA6Ly9wcm94eS1hcHBsaWFuY2Uub3JnIj5BYm91dCBBcnRpY2EgUHJveHkgQXBwbGlhbmNlPC9hPgoJCQkJCQkJCTwvZGl2PgoJCQkJCQkJCTwvZGl2PgoJCQkJCQkJPC9kaXY+CgkJCQkJCTwvZGl2PgoJCQkJCTwvdGQ+CgkJCQk8L3RyPgoJCQk8L3RhYmxlPgoJCTwvdGQ+Cgk8L3RyPgo8L3RhYmxlPgo=";
	$template_path="L3Vzci9zaGFyZS9jX2ljYXAvdGVtcGxhdGVzL3Nydl91cmxfY2hlY2svZW4vREVOWQ==";
	
	@file_put_contents(base64_decode($template_path), base64_decode($tpl));
	echo "Building......: ".date("H:i:s")." c-icap ".base64_decode($template_path)."\n";
	
	$cicap=new cicap_squidguard();
	$data=$cicap->build();
	echo "Building......: ".date("H:i:s")." c-icap /etc/srv_url_check.conf\n";
	@file_put_contents("/etc/srv_url_check.conf", $data);
	reload(true);
	
}

function webdbs(){
	$unix=new unix();
	@touch("/temp/toot");
	$unix->chmod_func(0777, "/temp/toot");
	$unix->chmod_func(0640, "/temp/toot");
	$cicap=new cicap_squidguard();
	$cicap->Construct_personal_categories();
	reload(true);
}

?>