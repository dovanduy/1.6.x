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
	if(preg_match("#version:\s+([0-9\.]+)#i", @implode("", $results),$re)){return $re[1];}
}




function create_package(){
	$Architecture=Architecture();
	if($Architecture==64){$Architecture="x64";}
	if($Architecture==32){$Architecture="i386";}
	$WORKDIR=$GLOBALS["ROOT-DIR"];
	$version=PDNS_VERSION();
	@mkdir("$WORKDIR/sbin",0755,true);
	@mkdir("$WORKDIR/usr/sbin",0755,true);

	
	$fdir[]="/usr/lib/powerdns";
	$fdir[]="/etc/powerdns";
	$fdir[]="/usr/share/poweradmin";
	
	while (list ($num, $ligne) = each ($fdir) ){
		@mkdir($ligne,0755,true);
		shell_exec("/bin/cp -rfd $ligne/* $WORKDIR/$ligne/");
	}
	
	
	
	$f[]="/usr/sbin/pdns_recursor";
	$f[]="/usr/sbin/pdns_server";
	$f[]="/usr/bin/pdnssec";
	$f[]="/usr/bin/dnsreplay";
	$f[]="/usr/bin/pdns_control";
	$f[]="/usr/bin/rec_control";
	$f[]="/etc/init.d/pdns-recursor";
	$f[]="/etc/init.d/pdns";
	

	while (list ($num, $ligne) = each ($f) ){
		if(!is_file($ligne)){echo "$ligne no such file\n";continue;}
		$dir=dirname($ligne);
		echo "Installing $ligne in $WORKDIR$dir/\n";
		if(!is_dir("$WORKDIR$dir")){@mkdir("$WORKDIR$dir",0755,true);}
		shell_exec("/bin/cp -fd $ligne $WORKDIR$dir/");
		
	}
	
	
	echo "Creating package done....\n";
	echo "Building package Arch:$Architecture Version:$version\n";
	echo "Going to $WORKDIR\n";
	@chdir("$WORKDIR");
	echo "Compressing pdnsc-$Architecture-$version.tar.gz\n";
	if(is_file("/root/pdnsc-$Architecture-$version.tar.gz")){@unlink("/root/pdnsc-$Architecture-$version.tar.gz");}
	shell_exec("tar -czf /root/pdnsc-$Architecture-$version.tar.gz *");
	echo "Compressing /root/pdnsc-$Architecture-$version.tar.gz Done...\n";	
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








function factorize($path){
	$f=explode("\n",@file_get_contents($path));
	while (list ($num, $val) = each ($f)){
		$newarray[$val]=$val;
		
	}
	while (list ($num, $val) = each ($newarray)){
		echo "$val\n";
	}
	
}










