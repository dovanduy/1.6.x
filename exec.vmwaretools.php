<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/vmtools.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$oldpid=@file_get_contents($pidfile);
	if($oldpid<100){$oldpid=null;}
	$unix=new unix();
	if($unix->process_exists($oldpid,basename(__FILE__))){events("PID: $oldpid Already exists....");die();}


if($argv[1]=="--cd"){installbycd();die();}
if($argv[1]=="--path"){@unlink($GLOBALS["LOGFILE"]);installbyPath($argv[2]);die();}


function installbycd(){
	@unlink($GLOBALS["LOGFILE"]);
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	if(!is_media_mounted()){
		events("Mount the CD-ROM on /media/cdrom0...");
		exec("$mount /media/cdrom0 2>&1",$results);
		while (list ($i, $line) = each ($results)){events("$line");}
	}
	
	if(!is_media_mounted()){
		events("Failed to Mount the CD-ROM on /media/cdrom0...");
		return;
	}
	
	$SourceFile=LatestVmSourcePackage("/media/cdrom0");
	if($SourceFile==null){
		events("Failed to find VMWare Tools Source package File on /media/cdrom0");
		return;	
	}
	installbyPath($SourceFile);
	
	
	
}

function installbyPath($SourceFile){
	
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");	
	if(!is_file($SourceFile)){
		events("Failed $SourceFile no such file...");
		return;
	}
	
	events("Extract ". basename($SourceFile)." Source package...");
	if(is_dir("/root/VMwareArticaInstall")){shell_exec("$rm -rf /root/VMwareArticaInstall");}
	@mkdir("/root/VMwareArticaInstall",0640,true);
	shell_exec("$tar -xf $SourceFile -C /root/VMwareArticaInstall/");
	
	if(!is_file("/root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl")){
		events("Failed /root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl no such file");
		shell_exec("$rm -rf /root/VMwareArticaInstall");
		return;
	}
	chdir("/root/VMwareArticaInstall/vmware-tools-distrib");
	events("Installing VMWare Tools....");
	$results=array();
	exec("./vmware-install.pl --default 2>&1",$results);
	while (list ($i, $line) = each ($results)){events("$line");}
	shell_exec("$rm -rf /root/VMwareArticaInstall");
	
	if(file_exists("/etc/init.d/vmware-tools")){
		events("Starting VMWare Tools service");
		$results=array();
		exec("/etc/init.d/vmware-tools start",$results);
		while (list ($i, $line) = each ($results)){events("$line");}		
		
	}
	
	if(file_exists("/usr/bin/vmware-toolbox-cmd")){
	events("VMWare Tools installed");
		$results=array();
		exec("/usr/bin/vmware-toolbox-cmd -v 2>&1",$results);
		while (list ($i, $line) = each ($results)){events("$line");}
		
	}

	if(is_dir("/root/VMwareArticaInstall")){shell_exec("$rm -rf /root/VMwareArticaInstall");}
	
}

function LatestVmSourcePackage($path){
	foreach (glob("$path/*.gz") as $filename) {
		if(preg_match("#VMwareTools(.+?)\.tar\.gz#", $filename)){return $filename;}
	}
	
	
}


function is_media_mounted(){
	
	$f=file("/proc/mounts");
	while (list ($i, $line) = each ($f)){
		if(preg_match("#\/cdrom.*?\s+iso9660#", $line)){return true;}
		
	}
	return false;
}












function events($text){
		$pid=@getmypid();
		$date=@date("h:i:s");
		
		if($GLOBALS["VERBOSE"]){echo "$date [$pid]:".basename(__FILE__).": $text\n";}
		$size=@filesize($GLOBALS["LOGFILE"]);
		if($size>1000000){@unlink($GLOBALS["LOGFILE"]);}
		$f = @fopen($GLOBALS["LOGFILE"], 'a');
		@fwrite($f, "$date [$pid]:".basename(__FILE__).": $text\n");
		@fclose($f);
		@chmod($GLOBALS["LOGFILE"], 0777);	
		}

