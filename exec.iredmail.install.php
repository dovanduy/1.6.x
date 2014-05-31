<?php
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.zarafadb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');




function install(){
	
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR()."/iredmail";
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	if(!is_file("/usr/share/artica-postfix/bin/install/postfix/iredmail.tar.gz")){return;}
	@mkdir($tmpdir,0755,true);
	shell_exec("$tar xf /usr/share/artica-postfix/bin/install/postfix/iredmail.tar.gz -C $tmpdir/" );
	if(!is_file("$tmpdir/iRedMail.sh")){
		shell_exec("$rm -rf $tmpdir");
		return;}
	@chmod("$tmpdir/iRedMail.sh",0755);
	@chdir("$tmpdir");
	
	
	
	
	
	
	
	
}
