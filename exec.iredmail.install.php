<?php
$GLOBALS["OUTPUT"]=true;
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



install();

function python_verify_modules($modulename){
	$unix=new unix();
	$python=$unix->find_program("python");
	exec("$python -c \"import $modulename\" 2>&1",$results);
	while (list ($index, $line) = each ($results)){
		if(preg_match("#ImportError:#i", $line)){return false;}

	}
	return true;


}

function install(){
	$unix=new unix();
	$DISTRICODE=$unix->LINUX_CODE_NAME();
	$arch=$unix->LINUX_ARCHITECTURE();
	$VERS=$unix->LINUX_VERS();
	$dpkg=$unix->find_program("dpkg");
	echo "DISTRICODE:$DISTRICODE \n";
	
	if(!python_verify_modules("MySQLdb")){
		$unix->DEBIAN_INSTALL_PACKAGE("python-mysqldb");
	}
	
	if(!python_verify_modules("ldap")){
		echo "Installing python-ldap\n";
		if($DISTRICODE=="DEBIAN"){
			
				if($arch==64){
					if($VERS[0]==6){
						if(is_file("/usr/share/artica-postfix/bin/install/postfix/python-6-ldap-amd64.deb")){
							shell_exec("$dpkg -i --force-all /usr/share/artica-postfix/bin/install/postfix/python-6-ldap-amd64.deb");
						}
					}
					if($VERS[0]==7){
						if(is_file("/usr/share/artica-postfix/bin/install/postfix/python-7-ldap-amd64.deb")){
							shell_exec("$dpkg -i --force-all /usr/share/artica-postfix/bin/install/postfix/python-7-ldap-amd64.deb");
						}
					}				
				
			}
			
		}
	}
	
	
	if(!python_verify_modules("ldap")){
		$unix->DEBIAN_INSTALL_PACKAGE("python-ldap");
	}
	

	if(!python_verify_modules("ldap")){
		echo "Warning, ldap/python-ldap not installed...\n";
		return;
	}
	
	if(!python_verify_modules("MySQLdb")){
		echo "Warning, MySQLdb/python-mysqldb not installed...\n";
		return;
	}
	
	
	
	
	echo "MySQLdb / python-mysqldb OK\n";
	echo "LDAP / python-ldap OK\n";
	
	
	$tmpdir=$unix->TEMP_DIR()."/iredmail";
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	if(!is_file("/usr/share/artica-postfix/bin/install/postfix/iredapd.tar.gz")){return;}
	@mkdir($tmpdir,0755,true);
	shell_exec("$tar xf /usr/share/artica-postfix/bin/install/postfix/iredapd.tar.gz -C /" );
	if(!is_file("/opt/iRedAPD/iredapd.py")){return;}
	@chmod("/opt/iRedAPD/iredapd.py",0755);
	

	
	
	
	
	
	
	
}
