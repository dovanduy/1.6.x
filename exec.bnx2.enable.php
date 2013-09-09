<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');



$users=new usersMenus();
if(!$users->LinuxDistriCode<>"DEBIAN"){
	echo "$users->LinuxDistriCode not supported\n";
	die();
}

$unix=new unix();
$unmame=$unix->find_program("uname");
$kernel=exec("uname -r 2>&1");

echo "Kernel version: $kernel\n";

if(!is_file("/lib/modules/$kernel/kernel/drivers/net/bnx2.ko")){
	echo "/lib/modules/$kernel/kernel/drivers/net/bnx2.ko no such module.. ( aptitude install firmware-bnx2  )\n";
	die();
	
}
$rmmod=$unix->find_program("rmmod");
$modprobe=$unix->find_program("modprobe");
$updateinitramfs=$unix->find_program("update-initramfs");

echo "Installing Broadcom drivers\n";
shell_exec("$modprobe bnx2");
shell_exec("$updateinitramfs -u -k all");

echo "You need to reboot in order to make it works. Perform ? [y/n]";
$answer = trim(strtolower(fgets(STDIN)));

if(trim(strtolower($answer))=="yes"){
	shell_exec($unix->find_program("reboot"));
}

die();


