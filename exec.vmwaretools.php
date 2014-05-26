<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/vmtools.debug";
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

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

	build_progress(5, "Mounting media...");
	
	if(!is_media_mounted()){
		events("Mount the CD-ROM on /media/cdrom0...");
		exec("$mount /media/cdrom0 2>&1",$results);
		while (list ($i, $line) = each ($results)){events("$line");}
	}
	
	if(!is_media_mounted()){
		build_progress(110, "Failed to Mount the CD-ROM");
		events("Failed to Mount the CD-ROM on /media/cdrom0...");
		return;
	}
	
	
	$SourceFile=LatestVmSourcePackage("/media/cdrom0");
	build_progress(10, "$SourceFile");
	
	
	if($SourceFile==null){
		build_progress(110, "Failed to find VMWare Tools Source");
		events("Failed to find VMWare Tools Source package File on /media/cdrom0");
		shell_exec("$umount -l /media/cdrom0 &");
		return;	
	}
	installbyPath($SourceFile);
	shell_exec("$umount -l /media/cdrom0 &");
	
	
}

function installbyPath($SourceFile){
	
	$unix=new unix();
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");	
	if(!is_file($SourceFile)){
		build_progress(110, "$SourceFile no such file");
		events("Failed $SourceFile no such file...");
		return;
	}
	
	build_progress(15, "Extracting ".basename($SourceFile));
	events("Extract ". basename($SourceFile)." Source package...");
	if(is_dir("/root/VMwareArticaInstall")){shell_exec("$rm -rf /root/VMwareArticaInstall");}
	@mkdir("/root/VMwareArticaInstall",0640,true);
	shell_exec("$tar -xf $SourceFile -C /root/VMwareArticaInstall/");
	events("Extract ". basename($SourceFile)." Source package done");
	build_progress(20, "Extracting ".basename($SourceFile)." success");
	
	if(!is_file("/root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl")){
		build_progress(110, "vmware-install.pl no such file");
		events("Failed /root/VMwareArticaInstall/vmware-tools-distrib/vmware-install.pl no such file");
		shell_exec("$rm -rf /root/VMwareArticaInstall");
		return;
	}
	
	build_progress(25, "Execute the setup...");
	events("Launch setup vmware-install.pl");
	if(!is_dir("/root/VMwareArticaInstall/vmware-tools-distrib")){
		events("Failed /root/VMwareArticaInstall/vmware-tools-distrib no such directory");
		build_progress(110, "vmware-tools-distrib no such directory");
		return;
	}
	
	chdir("/root/VMwareArticaInstall/vmware-tools-distrib");
	events("Installing VMWare Tools....");
	$results=array();
	exec("./vmware-install.pl --default 2>&1",$results);
	while (list ($i, $line) = each ($results)){events("$line");}
	build_progress(50, "Removing package");
	shell_exec("$rm -rf /root/VMwareArticaInstall");
	
	if(file_exists("/etc/init.d/vmware-tools")){
		build_progress(55, "Starting VMWare Tools service");
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
	build_progress(80, "Indexing softwares database");
	events("Indexing softwares database");
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose ".time());
	
}

function LatestVmSourcePackage($path){
	echo "Scanning $path\n";
	foreach (glob("$path/*.gz") as $filename) {
		echo "Checks $filename\n";
		if(preg_match("#VMwareTools(.+?)\.tar\.gz#", $filename)){return $filename;}
	}
	
	
}


function is_media_mounted(){
	
		$mount=new mount();
		return $mount->ismounted("/media/cdrom0");
}




function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/vmware.install.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}







function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		
		echo "$date [$pid]:".basename(__FILE__).": $text\n";
		$size=@filesize($GLOBALS["LOGFILE"]);
		if($size>1000000){@unlink($GLOBALS["LOGFILE"]);}
		$f = @fopen($GLOBALS["LOGFILE"], 'a');
		@fwrite($f, "$date [$pid]:".basename(__FILE__).": $text\n");
		@fclose($f);
		@chmod($GLOBALS["LOGFILE"], 0777);	
		}

