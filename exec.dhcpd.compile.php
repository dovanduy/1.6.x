<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
if(is_array($argv)){
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.dhcpd.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.iptables-chains.inc');
include_once(dirname(__FILE__) . '/ressources/class.baseunix.inc');
include_once(dirname(__FILE__) . '/ressources/class.bind9.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["ASROOT"]=true;
if($argv[1]=='--bind'){compile_bind();die();}



BuildDHCP();

function BuildDHCP(){
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$unix=new unix();
	if(!$GLOBALS["FORCE"]){
		if($unix->file_time_min($timefile)<2){
			if($GLOBALS["VERBOSE"]){echo "$timefile -> is less than 2mn\n";}
			return;
		}
	}
	
	$ldap=new clladp();
	if($ldap->ldapFailed){echo "Starting......: DHCP SERVER ldap connection failed,aborting\n";return;}
	if(!$ldap->ExistsDN("dc=organizations,$ldap->suffix")){echo "Starting......: DHCP SERVER dc=organizations,$ldap->suffix no such branch, aborting\n";return;	}
	echo "Starting......: DHCP SERVER ldap connection success\n";
	$dhcpd=new dhcpd();
	$conf=$dhcpd->BuildConf();
	$confpath=dhcp3Config();
	$unix=new unix();
	@mkdir(dirname($confpath),null,true);
	@file_put_contents($confpath,$conf);
	echo "Starting......: DHCP SERVER saving \"$confpath\" (". strlen($conf)." bytes) done\n";
	
	if(!$unix->UnixUserExists("dhcpd")){
		$unix->CreateUnixUser("dhcpd","dhcpd");
	}
	if(!is_dir("/var/lib/dhcp3")){@mkdir("/var/lib/dhcp3",0755,true);}
	$unix->chown_func("dhcpd","dhcpd", "/var/lib/dhcp3/*");
	$unix->chmod_func(0755, "/var/lib/dhcp3");
	$complain=$unix->find_program("aa-complain");
	
	if(is_file($complain)){
		$dhcpd3=$unix->find_program("dhcpd3");
		if(is_file($dhcpd3)){shell_exec("$complain $dhcpd3 >/dev/null 2>&1");}
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
}

function compile_bind(){
	$bind=new bind9();
	$bind->Compile();
	$bind->SaveToLdap();
}


function dhcp3Config(){
	
	$f[]="/etc/dhcp3/dhcpd.conf";
	$f[]="/etc/dhcpd.conf";
	$f[]="/etc/dhcpd/dhcpd.conf";
	while (list ($index, $filename) = each ($f) ){
		if(is_file($filename)){return $filename;}
	} 
	return "/etc/dhcp3/dhcpd.conf";
	
}
?>