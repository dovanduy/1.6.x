<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["dns-linker"])){dns_linker();exit;}
if(isset($_GET["swap-init"])){swap_init();exit;}
if(isset($_GET["dirdir"])){dirdir();exit;}
if(isset($_GET["process1"])){process1();exit;}
if(isset($_GET["restart-ldap"])){restart_ldap();exit;}
if(isset($_GET["all-services"])){all_services();exit;}
if(isset($_GET["generic-start"])){generic_start();exit;}
if(isset($_GET["parse-blocked"])){parse_blocked();exit;}
if(isset($_GET["meminfo"])){meminfo();exit;}
if(isset($_GET["HugePages"])){HugePages();exit;}
if(isset($_GET["zoneinfo-set"])){zone_info_set();exit;}
if(isset($_GET["uidNumber"])){uidNumber();exit;}
if(isset($_GET["tune2fs-values"])){tune2fs_values();exit;}
if(isset($_GET["INODES_MAX"])){INODES_MAX();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();



function dns_linker(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.dnslinker.php >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function swap_init(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/init.d/artica-swap")){
		$cmd=trim("$php /usr/share/artica-postfix/exec.initd-swap.php >/dev/null 2>&1");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
				
	}
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.initd-swap.php --start >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	$cmd="$nohup $rm -rf /usr/share/artica-postfix/ressources/logs/* >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function dirdir(){
	$path=base64_decode($_GET["dirdir"]);
	$unix=new unix();
	$array=$unix->dirdir($path);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function process1(){
	shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose --".time());
}

function restart_ldap(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php >/dev/null 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
	$cmd=trim("$nohup $php /etc/init.d/slapd restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function parse_blocked(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.dansguardian.injector.php --blocked >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function all_services(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php /usr/share/artica-postfix/exec.status.php --all --nowachdog 2>&1");
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}



function generic_start(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$key=$_GET["key"];
	$action=$_GET["action"];
	$token=$_GET["cmd"];
	$file="/usr/share/artica-postfix/ressources/logs/web/$key.log";
	@unlink($file);
	@file_put_contents($file, "{$action} Please wait....\n/etc/init.d/artica-postfix $action $token\n");
	@chmod($file, 0777);
	$cmd="$nohup /etc/init.d/artica-postfix $action $token >> $file 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function meminfo(){
	$f=file("/proc/meminfo");
	while (list ($num, $ligne) = each ($f) ){
		if(!preg_match("#(.*?):\s+([0-9]+)\s+#", $ligne,$re)){continue;}
		$TotalKbytes=$re[2];
		$TotalBytes=$TotalKbytes*1024;
		$key=strtoupper($re[1]);
		$array[$key]=$TotalBytes;
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function HugePages(){
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.HugePages.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function zone_info_set(){
	$zone=base64_decode($_GET["zoneinfo-set"]);
	$sourcefile="/usr/share/zoneinfo/$zone";
	if(!is_file($sourcefile)){
		writelogs_framework("$sourcefile no such file!!",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	writelogs_framework("$sourcefile -> /etc/localtime",__FUNCTION__,__FILE__,__LINE__);
	@copy($sourcefile, "/etc/localtime");
}

function uidNumber(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.uidMember.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function tune2fs_values(){
	$dev=base64_decode($_GET["tune2fs-values"]);
	$unix=new unix();
	echo "<articadatascgi>". base64_encode(serialize($unix->tune2fs_values($dev)))."</articadatascgi>";
}

function INODES_MAX(){
	$unix=new unix();
	$dev=base64_decode($_GET["dev"]);
	$INODES_MAX=$_GET["INODES_MAX"];
	$INODE_SIZE=$_GET["INODE_SIZE"];
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$mke2fs=$unix->find_program("mke2fs");
	exec("$umount -l $dev",$results);
	exec("$mke2fs -I $INODE_SIZE -N $INODES_MAX $dev 2>&1",$results);
	exec("$mount $dev 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
