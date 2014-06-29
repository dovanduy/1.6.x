<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
install($argv[2]);exit;



function install($filename){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.install.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.install.progress.txt";
	
	$unix=new unix();
	$LINUX_CODE_NAME=$unix->LINUX_CODE_NAME();
	$LINUX_DISTRIBUTION=$unix->LINUX_DISTRIBUTION();
	$LINUX_VERS=$unix->LINUX_VERS();
	$LINUX_ARCHITECTURE=$unix->LINUX_ARCHITECTURE();
	$APACHEUSER=$unix->APACHE_SRC_ACCOUNT();
	$DebianVer="debian{$LINUX_VERS[0]}";
	$TMP_DIR=$unix->TEMP_DIR();
	$ORGV=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$PATCH_VER=null;
	$tarballs_file="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	echo "Package $tarballs_file\n";
	$size=filesize($tarballs_file);
	

	
	echo "Size....................: ".FormatBytes($size/1024)."\n";
		
	build_progress("Analyze...",10);
		
	echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
	echo "Package.................: $filename\n";
	echo "Temp dir................: $TMP_DIR\n";
	
	
	
	if(!is_file($tarballs_file)){
		echo "$tarballs_file no such file...\n";
		build_progress("No such file...",110);
		return;
	}
	echo "Uncompressing $tarballs_file...\n";
	build_progress("{extracting} $filename...",20);
	
	
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squid=$unix->LOCATE_SQUID_BIN();
	build_progress("{extracting} $filename...",50);
	
	system("$tar xf $tarballs_file -C /");
	echo "Removing $tarballs_file...\n";
	@unlink($tarballs_file);
	shell_exec("$rm -rf /usr/share/artica-postfix/ressources/conf/upload/*");
	
	
	
	
	build_progress("{restarting} Squid-cache...",60);
	system("/etc/init.d/squid restart --force");
	build_progress("{reconfiguring} Squid-cache...",65);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reconfiguring} {APP_UFDBGUARD}...",70);
	system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
	build_progress("Refresh local versions...",80);
	system('/usr/share/artica-postfix/bin/process1 --force --verbose --'.time());
	$squid_version=x_squid_version();
	build_progress("{success} v.$squid_version...",100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";
		
	
	
	
}
function x_squid_version(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	exec("$squidbin -v 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
			return trim($re[1]);
		}
	}

}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
?>