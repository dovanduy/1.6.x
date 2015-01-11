<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');

$unix=new unix();

$GLOBALS["SHOW_COMPILE_ONLY"]=false;
$GLOBALS["NO_COMPILE"]=false;
$GLOBALS["REPOS"]=false;
if($argv[1]=='--compile'){$GLOBALS["SHOW_COMPILE_ONLY"]=true;}
if(preg_match("#--no-compile#", @implode(" ", $argv))){$GLOBALS["NO_COMPILE"]=true;}
if(preg_match("#--verbose#", @implode(" ", $argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--repos#", @implode(" ", $argv))){$GLOBALS["REPOS"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}


/* wget https://downloads.powerdns.com/releases/pdns-3.4.1.tar.bz2
./configure --prefix=/usr --sysconfdir=/etc/powerdns --mandir=\${prefix}/share/man --infodir=\${prefix}/share/info --libdir=''${prefix}/lib/powerdns'' --libexecdir=''${prefix}/lib'' --with-dynmodules="ldap pipe gmysql geo" --without-sqlite3


wget https://downloads.powerdns.com/releases/pdns-recursor-3.6.2.tar.bz2

wget "http://downloads.sourceforge.net/project/poweradmin/poweradmin-2.1.7.tgz?r=http%3A%2F%2Fwww.poweradmin.org%2F&ts=1415924225&use_mirror=skylink" -O poweradmin-2.1.7.tgz
tar -xf poweradmin-2.1.7.tgz

*/
if($argv[1]=="--version"){echo PDNS_VERSION();exit;}
if($argv[1]=="--factorize"){factorize($argv[2]);exit;}
if($argv[1]=="--serialize"){serialize_tests();exit;}
if($argv[1]=="--latests"){latests();exit;}
if($argv[1]=="--latest"){echo "Latest:". latests()."\n";exit;}
if($argv[1]=="--create-package"){create_package();exit;}
if($argv[1]=="--parse-install"){parse_install($argv[2]);exit;}



$wget=$unix->find_program("wget");
$tar=$unix->find_program("tar");
$rm=$unix->find_program("rm");
$cp=$unix->find_program("cp");


$dirsrc="pdns-0.0.0";
$GLOBALS["ROOT-DIR"]="/root/pdns-builder";


if(is_dir($GLOBALS["ROOT-DIR"])){shell_exec("$rm -rf {$GLOBALS["ROOT-DIR"]}");}
create_package();


function PDNS_VERSION(){
	$unix=new unix();
	$pdns_recursor=$unix->find_program("pdns_recursor");
	exec("$pdns_recursor --version 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#version:\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
		if(preg_match("#PowerDNS Recursor\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
	}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);
	@mkdir("$WORKDIR//usr/lib/powerdns/",0755,true);
	
	if(is_dir("/lib/powerdns")){
		shell_exec("/bin/cp -rfd /lib/powerdns/* $WORKDIR/usr/lib/powerdns/");
	}
	
	$fdir[]="/usr/lib/powerdns";
	$fdir[]="/lib/powerdns";
	$fdir[]="/etc/powerdns";
	$fdir[]="/usr/share/poweradmin";
	$fdir[]="/usr/share/doc/pdns";
	$fdir[]="/usr/lib/powerdns";
	while (list ($num, $ligne) = each ($fdir) ){
		@mkdir("$WORKDIR$ligne",0755,true);
		echo "Installing $ligne in $WORKDIR$ligne/\n";
		shell_exec("/bin/cp -rfd $ligne/* $WORKDIR$ligne/");
	}
	
	
	
	$f[]="/usr/sbin/pdns_recursor";
	$f[]="/usr/sbin/pdns_server";
	$f[]="/usr/bin/pdnssec";
	$f[]="/usr/bin/dnsreplay";
	$f[]="/usr/bin/pdns_control";
	$f[]="/usr/bin/rec_control";
	$f[]="/etc/init.d/pdns-recursor";
	$f[]="/usr/bin/zone2sql";
	$f[]="/usr/bin/zone2ldap";
	$f[]="/usr/bin/zone2json";
	$f[]="/etc/init.d/pdns";
	$f[]="/usr/share/man/man8/pdns_control.8"; 
	$f[]="/usr/share/man/man8/pdnssec.8";  
	$f[]="/usr/share/man/man8/pdns_server.8";
	$f[]="/usr/share/man/man1/pdns_recursor.1";
	$f[]="/usr/share/man/man1/rec_control.1";
	

	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
		
	}
	
	$version=PDNS_VERSION();
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	$debianv=DebianVersion();
	
	if($debianv>6){
		$debianv="-debian$debianv";
	}
	
	$TARGET_TGZ="/root/pdnsc-$Architecture$debianv-$version.tar.gz";
	
	
	
	
	echo "Compressing $TARGET_TGZ\n";
	if(is_file($TARGET_TGZ)){@unlink($TARGET_TGZ);}
	shell_exec("tar -czf $TARGET_TGZ *");
	echo "Compressing $TARGET_TGZ Done...\n";	
}


	

function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	while (list ($num, $val) = each ($results)){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}


function DebianVersion(){

	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	return $re[1];

}





function factorize($path){
	$f=explode("\n",@file_get_contents($path));
	while (list ($num, $val) = each ($f)){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}










