<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");}
if($argv[1]=="--memboost"){memboost();exit;}
if($argv[1]=="--template"){gen_template();reconfigure();exit;}
if($argv[1]=="--db-maintenance"){dbMaintenance();exit;}
if($argv[1]=="--maint-schedule"){dbMaintenanceSchedule();exit;}
if($argv[1]=="--build"){build();exit;}


if($GLOBALS["VERBOSE"]){echo "????\n";}

function build(){
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid)){
		echo "Starting......: c-icap ". __FUNCTION__."() already running PID:$oldpid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());
	if(!is_dir("/var/lib/squidguard")){@mkdir("/var/lib/squidguard",0755,true);}
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
	echo "Starting......: c-icap squid binary is `$squidbin`\n";
	if(is_file($squidbin)){
		$squid=new squidbee();
		$conf=$squid->BuildSquidConf();
		memboost();
		$SQUID_CONFIG_PATH=$unix->SQUID_CONFIG_PATH();
		echo "Starting......: c-icap reconfigure squid done...\n";
		@file_put_contents($SQUID_CONFIG_PATH,$conf);
	}else{
		echo "Starting......: c-icap skip reconfigure squid (not installed)...\n";
	}
	
	@mkdir("/usr/etc",0755,true);
	CicapMagic("/usr/etc/c-icap.magic");
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
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
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
	return;
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid)){
		echo "Starting......: c-icap ". __FUNCTION__."() already running PID:$oldpid\n";
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
@file_put_contents($path,@implode("\n",$f));
}

function gen_template($reconfigure=false){
	$path=base64_decode("L3Vzci9zaGFyZS9jX2ljYXAvdGVtcGxhdGVzL3ZpcnVzX3NjYW4vZW4vVklSVVNfRk9VTkQ=");
	if(!class_exists("usersMenus")){include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");}
	$users=new usersMenus();
	if($users->CORP_LICENSE){
		$sock=new sockets();
		$CiCApErrorPage=base64_decode($sock->GET_INFO("CiCApErrorPage"));
		if($CiCApErrorPage<>null){
			@file_put_contents($path, $CiCApErrorPage);	
			return;
		}
		
	}
	
$template="YTo4MTp7aTowO3M6NjoiPGh0bWw+IjtpOjE7czo2OiI8aGVhZD4iO2k6MjtzOjY3OiIJPG1ldGEgaHR0cC1lcXVpdj0nY29udGVudC10eXBlJyBjb250ZW50PSd0ZXh0L2h0bWw7Y2hhcnNldD11dGYtOCc+IjtpOjM7czoyNzoiCTx0aXRsZT5WSVJVUyBGT1VORDwvdGl0bGU+IjtpOjQ7czoyNDoiCTxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+IjtpOjU7czoyMzoiCWh0bWwsYm9keXtoZWlnaHQ6MTAwJX0iO2k6NjtzOjY6Iglib2R5eyI7aTo3O3M6NjE6Igl3aWR0aDoxMDAlO21pbi1oZWlnaHQ6MTAwJTttYXJnaW46MDtwYWRkaW5nOjA7Y29sb3I6IzJDMkMyQzsiO2k6ODtzOjMzOiIJZm9udC1mYW1pbHk6J1RhaG9tYScsICdWZXJkYW5hJzsiO2k6OTtzOjE2OiIJZm9udC1zaXplOjExcHg7IjtpOjEwO3M6MTY6IgliYWNrZ3JvdW5kOiNGRkYiO2k6MTE7czoyOiIJfSI7aToxMjtzOjE1OiIJZm9ybXttYXJnaW46MH0iO2k6MTM7czo0NDoiCXRhYmxlLGlucHV0LHNlbGVjdHtmb250Om5vcm1hbCAxMDAlIHRhaG9tYX0iO2k6MTQ7czoyMzoiCWltZ3tib3JkZXI6MDttYXJnaW46MH0iO2k6MTU7czozMjoiCXRhYmxle2JvcmRlci1jb2xsYXBzZTpjb2xsYXBzZX0iO2k6MTY7czoxNzoiCWF7Y29sb3I6IzYyNzA3RH0iO2k6MTc7czozMToiCS50LHRyLnQgdGR7dmVydGljYWwtYWxpZ246dG9wfSI7aToxODtzOjI2OiIJLm17dmVydGljYWwtYWxpZ246bWlkZGxlfSI7aToxOTtzOjM0OiIJLmIsdHIuYiB0ZHt2ZXJ0aWNhbC1hbGlnbjpib3R0b219IjtpOjIwO3M6NDM6Igl0ci50IHRkIHRkLHRyLmIgdGQgdGR7dmVydGljYWwtYWxpZ246YXV0b30iO2k6MjE7czoyMDoiCS5se3RleHQtYWxpZ246bGVmdH0iO2k6MjI7czoyMjoiCS5je3RleHQtYWxpZ246Y2VudGVyfSI7aToyMztzOjIxOiIJLnJ7dGV4dC1hbGlnbjpyaWdodH0iO2k6MjQ7czoyNjoiCS5ub2Jye3doaXRlLXNwYWNlOm5vd3JhcH0iO2k6MjU7czoyNDoiCS5yZWx7cG9zaXRpb246cmVsYXRpdmV9IjtpOjI2O3M6MjQ6IgkuYWJze3Bvc2l0aW9uOmFic29sdXRlfSI7aToyNztzOjE2OiIJLmZse2Zsb2F0OmxlZnR9IjtpOjI4O3M6MTc6IgkuZnJ7ZmxvYXQ6cmlnaHR9IjtpOjI5O3M6MTY6IgkuY2x7Y2xlYXI6Ym90aH0iO2k6MzA7czoxODoiCS53MTAwe3dpZHRoOjEwMCV9IjtpOjMxO3M6MTk6IgkuaDEwMHtoZWlnaHQ6MTAwJX0iO2k6MzI7czoyNToiCWJpZywuYmlne2ZvbnQtc2l6ZToxMjUlfSI7aTozMztzOjI4OiIJc21hbGwsLnNtYWxse2ZvbnQtc2l6ZTo5NSV9IjtpOjM0O3M6NDI6IgkubWljcm97Y29sb3I6I0RERDtmb250Om5vcm1hbCA5cHggdGFob21hfSI7aTozNTtzOjM1OiIJaDF7Zm9udDpib2xkIDIwcHggYXJpYWw7IG1hcmdpbjowfSI7aTozNjtzOjM1OiIJaDR7Zm9udDpib2xkIDEycHggYXJpYWw7IG1hcmdpbjowfSI7aTozNztzOjU3OiIJcHt0ZXh0LWFsaWduOmp1c3RpZnk7bGluZS1oZWlnaHQ6MS4zO21hcmdpbjowIDAgMC41ZW0gMH0iO2k6Mzg7czoyNToiCS56e2JvcmRlcjoxcHggc29saWQgcmVkfSI7aTozOTtzOjQ4OiIJLmgxcHh7aGVpZ2h0OjFweDtmb250LXNpemU6MXB4O2xpbmUtaGVpZ2h0OjFweH0iO2k6NDA7czozNjoiCXVse21hcmdpbjo2cHggMCA2cHggMjBweDtwYWRkaW5nOjB9IjtpOjQxO3M6MjA6Igl1bCBsaXttYXJnaW46M3B4IDB9IjtpOjQyO3M6ODoiPC9zdHlsZT4iO2k6NDM7czo3OiI8L2hlYWQ+IjtpOjQ0O3M6OToiPGJvZHk+77u/IjtpOjQ1O3M6MjU6Ijx0YWJsZSBjbGFzcz0idzEwMCBoMTAwIj4iO2k6NDY7czo1OiIJPHRyPiI7aTo0NztzOjE4OiIJCTx0ZCBjbGFzcz0iYyBtIj4iO2k6NDg7czo1NzoiCQkJPHRhYmxlIHN0eWxlPSJtYXJnaW46MCBhdXRvO2JvcmRlcjpzb2xpZCAxcHggIzU2MDAwMCI+IjtpOjQ5O3M6ODoiCQkJCTx0cj4iO2k6NTA7czozOToiCQkJCQk8dGQgY2xhc3M9ImwiIHN0eWxlPSJwYWRkaW5nOjFweCI+IjtpOjUxO3M6NTA6IgkJCQkJCTxkaXYgc3R5bGU9IndpZHRoOjM0NnB4O2JhY2tncm91bmQ6I0UzMzYzMCI+IjtpOjUyO3M6MzI6IgkJCQkJCQk8ZGl2IHN0eWxlPSJwYWRkaW5nOjNweCI+IjtpOjUzO3M6ODU6IgkJCQkJCQkJPGRpdiBzdHlsZT0iYmFja2dyb3VuZDojQkYwQTBBO3BhZGRpbmc6OHB4O2JvcmRlcjpzb2xpZCAxcHggI0ZGRjtjb2xvcjojRkZGIj4iO2k6NTQ7czozMDoiCQkJCQkJCQkJPGg0PlZpcnVzIEZvdW5kOjwvaDQ+IjtpOjU1O3M6MjI6IgkJCQkJCQkJCTxoMT4lVlZOPC9oMT4iO2k6NTY7czoxNDoiCQkJCQkJCQk8L2Rpdj4iO2k6NTc7czoxMjE6IgkJCQkJCQkJPGRpdiBjbGFzcz0iYyIgc3R5bGU9ImZvbnQ6Ym9sZCAxM3B4IGFyaWFsO3RleHQtdHJhbnNmb3JtOnVwcGVyY2FzZTtjb2xvcjojRkZGO3BhZGRpbmc6OHB4IDAiPkFjY2VzcyBkZW5pZWQ8L2Rpdj4iO2k6NTg7czo2MzoiCQkJCQkJCQk8ZGl2IHN0eWxlPSJiYWNrZ3JvdW5kOiNGN0Y3Rjc7cGFkZGluZzoyMHB4IDI4cHggMzZweCI+IjtpOjU5O3M6NDg6IlRoZSByZXF1ZXN0ZWQgVVJMIGNhbm5vdCBiZSBwcm92aWRlZDxicj48YnI+IDxiPiI7aTo2MDtzOjU3OiJUaGUgcmVxdWVzdGVkIG9iamVjdCBhdCB0aGUgVVJMOjwvYj48YnI+PGJyPiVodW88YnI+PGJyPiAiO2k6NjE7czo3MzoiPGI+VmlydXMgZGV0ZWN0ZWQ6PC9iPjxicj4gPGJyPjxkaXYgc3R5bGU9J2ZvbnQtc2l6ZToxMnB4Jz4lVlZOPC9kaXY+PGJyPiI7aTo2MjtzOjgwOiI8L2k+PHN0cm9uZz5UaGlzIG1lc3NhZ2UgZ2VuZXJhdGVkIGJ5IEMtSUNBUCBzZXJ2aWNlOjwvYj46Jm5ic3A7PC9zdHJvbmc+JWl1PC9pPiI7aTo2MztzOjY6IjwvZGl2PiI7aTo2NDtzOjU4OiIJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0Y3RjdGNztwYWRkaW5nOjAgMnB4IDJweCI+IjtpOjY1O3M6NjQ6IgkJCQkJCQkJCTxkaXYgc3R5bGU9ImJhY2tncm91bmQ6I0U5RTlFOTtwYWRkaW5nOjEycHggMzBweCAxNHB4Ij4iO2k6NjY7czoxNDE6IgkJCQkJCQkJCTxiPkNsYW1hdiBhbnRpdmlydXMgZW5naW5lOiA8Yj4gJVZWViA8L2I+PC9hPjwvYj4gPGJyPgoJCQkJCQkJCTxhIGhyZWY9Imh0dHA6Ly9wcm94eS1hcHBsaWFuY2Uub3JnIj5BYm91dCBBcnRpY2EgUHJveHkgQXBwbGlhbmNlPC9hPiI7aTo2NztzOjE0OiIJCQkJCQkJCTwvZGl2PiI7aTo2ODtzOjE0OiIJCQkJCQkJCTwvZGl2PiI7aTo2OTtzOjEzOiIJCQkJCQkJPC9kaXY+IjtpOjcwO3M6MTI6IgkJCQkJCTwvZGl2PiI7aTo3MTtzOjEwOiIJCQkJCTwvdGQ+IjtpOjcyO3M6OToiCQkJCTwvdHI+IjtpOjczO3M6MTE6IgkJCTwvdGFibGU+IjtpOjc0O3M6NzoiCQk8L3RkPiI7aTo3NTtzOjY6Igk8L3RyPiI7aTo3NjtzOjg6IjwvdGFibGU+IjtpOjc3O3M6MDoiIjtpOjc4O3M6MDoiIjtpOjc5O3M6NzoiPC9ib2R5PiI7aTo4MDtzOjc6IjwvaHRtbD4iO30=";
$f=unserialize(base64_decode($template));
@file_put_contents($path, @implode("\n", $f));	
	
}

function is_running(){
	$unix=new unix();
	$binpath=$unix->find_program("c-icap");
	$master_pid=trim(@file_get_contents("/var/run/c-icap.pid"));
	if($master_pid==null){$master_pid=$unix->PIDOF($binpath);}
	if(!$unix->process_exists($master_pid)){$master_pid=$unix->PIDOF($binpath);}
	
	if(!$unix->process_exists($master_pid)){return false;}
	return true;	
}

function reconfigure(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	if(!is_running()){echo "Starting......: c-icap, not running...\n";return;}
	echo "Starting......: c-icap reconfigure service...\n"; 
	shell_exec("echo -n \"reconfigure\" > /var/run/c-icap/c-icap.ctl");
	
}
function memboost(){
	
	$workdir="/var/lib/c_icap/temporary";
	
	
	$sock=new sockets();
	$unix=new unix();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		echo "Starting......:c-icap MemBoost license inactive...\n";
		$mountedM=tmpfs_mounted_size();
		if($mountedM>1){shell_exec("$umount -l $workdir");}
		return;
	}
	echo "Starting......:c-icap MemBoost `$workdir`\n";
	$umount=$unix->find_program("umount");
	$mount=$unix->find_program("mount");
	$rm=$unix->find_program("rm");
	$idbin=$unix->find_program("id");
	$CiCAPMemBoost=$sock->GET_INFO("CiCAPMemBoost");
	if(!is_numeric($CiCAPMemBoost)){$CiCAPMemBoost=0;}
	
	if($GLOBALS["VERBOSE"]){echo "Starting......:c-icap MemBoost -> $CiCAPMemBoost Mb\n";}
	$mountedM=tmpfs_mounted_size();
	
	if($GLOBALS["VERBOSE"]){echo "Starting......:c-icap mounted -> $mountedM Mb\n";}		 
	if($CiCAPMemBoost<2){
		if($mountedM>1){shell_exec("$umount -l $workdir");}
		return;
	}
	
	if($CiCAPMemBoost==$mountedM){return;}
	$unix->SystemCreateUser("clamav","clamav");
	
	exec("$idbin clamav 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......:c-icap MemBoost clamav no such user...\n";return;}
	
	shell_exec("$umount -l $workdir");
	$uid=$re[1];
	$gid=$re[2];	
	shell_exec("$rm -rf $workdir");
	@mkdir($workdir,0755);	
	echo "Starting......:c-icap MemBoost clamav ($uid/$gid)\n";	
	shell_exec("$mount -t tmpfs -o size={$CiCAPMemBoost}M,noauto,user,exec,uid=$uid,gid=$gid tmpfs $workdir");
	$mountedM=tmpfs_mounted_size();
	if($mountedM>1){echo "Starting......:c-icap MemBoost mounted with {$mountedM}M\n";}else{
		echo "Starting......:c-icap MemBoost mounted failed\n";
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

?>