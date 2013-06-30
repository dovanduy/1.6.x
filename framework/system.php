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
if(isset($_GET["HardDriveDiskSizeMB"])){HardDriveDiskSizeMB();exit;}
if(isset($_GET["TOTAL_MEMORY_MB"])){TOTAL_MEMORY_MB();exit;}
if(isset($_GET["archiverlogs"])){archiverlogs();exit;}
if(isset($_GET["squid-db-query"])){squiddb_query();exit;}
if(isset($_GET["wizard-execute"])){wizard_execute();exit;}
if(isset($_GET["ucarp-compile"])){ucarp_compile();exit;}
if(isset($_GET["ucarp-status"])){ucarp_status();exit;}
if(isset($_GET["ucarp-start-tenir"])){ucarp_start();exit;}
if(isset($_GET["ucarp-stop-tenir"])){ucarp_stop();exit;}
if(isset($_GET["syslogdb-restart"])){syslogdb_restart();exit;}
if(isset($_GET["syslogdb-status"])){syslogdb_status();exit;}
if(isset($_GET["syslogdb-query"])){syslogdb_query();exit;}
if(isset($_GET["logrotate-query"])){logrotate_query();exit;}
if(isset($_GET["BuildCSR"])){BuildCSR();exit;}
if(isset($_GET["SYSTEMS_ALL_PARTITIONS"])){SYSTEMS_ALL_PARTITIONS();exit;}
if(isset($_GET["apply-patch"])){APPLY_PATCH();exit;}
if(isset($_GET["apply-soft"])){APPLY_SOFT();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function TOTAL_MEMORY_MB(){
	$unix=new unix();
	echo "<articadatascgi>". $unix->TOTAL_MEMORY_MB()."</articadatascgi>";
}

function SYSTEMS_ALL_PARTITIONS(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode(serialize($unix->SYSTEMS_ALL_PARTITIONS()))."</articadatascgi>";
}

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
	writelogs_framework("token $token -> $action",__FUNCTION__,__FILE__,__LINE__);
	
	$binary="/etc/init.d/artica-postfix";
	if(strpos("$token", "init.d")>0){
		$binary=$token;
		writelogs_framework("change binary to $token",__FUNCTION__,__FILE__,__LINE__);
		$token=null;
	}else{
		$token=" $token";
	}
		
	
	@file_put_contents($file, "{$action} Please wait....\n$binary $action$token\n");
	@chmod($file, 0777);
	$cmd="$nohup $binary $action$token >> $file 2>&1 &";
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
	$unix=new unix();
	if(isset($_GET["dirscan"])){
		$dirscan=base64_decode($_GET["dirscan"]);
		$unix->dirdir($dirscan);
	}
	$dev=base64_decode($_GET["tune2fs-values"]);
	
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

function HardDriveDiskSizeMB(){
	$unix=new unix();
	$path=$unix->shellEscapeChars(base64_decode($_GET["HardDriveDiskSizeMB"]));
	$df=$unix->find_program("df");
	$cmd="$df -B 1000000 $path 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	while (list ($num, $line) = each ($results)){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^(.*?)([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)%\s+(.+)#",$line,$re)){
			writelogs_framework("No match `$line`",__FUNCTION__,__FILE__,__LINE__);
			continue;}
		$array["DEV"]=trim($re[1]);
		$array["SIZE"]=trim($re[2]);
		$array["USED"]=trim($re[3]);
		$array["AVAILABLE"]=trim($re[4]);
		$array["POURC"]=trim($re[5]);
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
		
}

function archiverlogs(){
	$filelog="/var/log/artica-postfix/artica-mailarchive.debug";
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}	
	
	if($search<>null){
		$prefix="$grep -i -E '$search' $filelog| ";
		
	}
	
	if($search<>null){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		$cmd="$grep -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
}
function logrotate_query(){
	$filelog="/var/log/artica-postfix/logrotate.debug";
	
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
		$prefix="$grep -i -E '$search' $filelog| ";
	
	}
	
	if($search<>null){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		$cmd="$grep -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
	
}

function syslogdb_query(){
	$filelog=@file_get_contents("/etc/artica-postfix/settings/Daemons/MySQLSyslogWorkDir");
	if($filelog==null){$filelog="/home/syslogsdb";}	
	$filelog="$filelog/error.log";
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
		$prefix="$grep -i -E '$search' $filelog| ";
	
	}
	
	if($search<>null){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		$cmd="$grep -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
	
}

function squiddb_query(){
	$filelog="/opt/squidsql/error.log";
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=$_GET["rp"];}
	
	if($search<>null){
		$prefix="$grep -i -E '$search' $filelog| ";
	
	}
	
	if($search<>null){
		$search=str_replace(".","\.",$search);
		$search=str_replace("*",".*?",$search);
		$search=str_replace("(","\(",$search);
		$search=str_replace(")","\)",$search);
		$search=str_replace("[","\[",$search);
		$search=str_replace("]","\]",$search);
		$cmd="$grep -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
	
}

function wizard_execute(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.wizard-install.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function ucarp_compile(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup /etc/init.d/artica-failover restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function ucarp_status(){
	$unix=new unix();
	$eth=$_GET["ucarp-status"];
	$pgrep=$unix->find_program("pgrep");
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	
	$pid=$unix->PIDOF_PATTERN("$ucarp_bin$eth");
	writelogs_framework("$pid = PIDOF_PATTERN($ucarp_bin$eth)",__FUNCTION__,__FILE__,__LINE__);
	if(!$unix->process_exists($pid)){
		writelogs_framework("$pid = NOT IN MEMORY",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";	
		return;
	}
	writelogs_framework("$pid =OK",__FUNCTION__,__FILE__,__LINE__);
	$pidtim=$unix->PROCCESS_TIME_MIN($pid);
	echo "<articadatascgi>". base64_encode(serialize(array("PID"=>$pid,"TIME"=>$pidtim)))."</articadatascgi>";
	
	
}
function ucarp_start(){
	$unix=new unix();
	if(!is_file("/etc/init.d/artica-failover")){
		
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	exec("/etc/init.d/artica-failover start 2>&1",$results);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	return;
}
function ucarp_stop(){
	$unix=new unix();
	if(!is_file("/etc/init.d/artica-failover")){

		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php ". dirname(__FILE__)."/exec.initslapd.php --failover");
	}
	exec("/etc/init.d/artica-failover stop 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	return;
}
function syslogdb_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.logs-db.php --init";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	$cmd=trim("$nohup /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function BuildCSR(){
	$unix=new unix();
	$commonName=$_GET["BuildCSR"];
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.openssl.php --BuildCSR $commonName 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function syslogdb_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --syslog-db --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";

}
function APPLY_PATCH(){
	$filename="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["apply-patch"]}";
	if(!is_file($filename)){
		echo "<articadatascgi>". base64_encode(serialize(array("$filename no such file")))."</articadatascgi>";
		return;
	}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	exec("$tar -xvf $filename -C /usr/share/artica-postfix/ 2>&1",$results);
	@unlink($filename);
	$results[]="Done...";
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	
}
function APPLY_SOFT(){
	$filename="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["apply-soft"]}";
	if(!is_file($filename)){
		echo "<articadatascgi>". base64_encode(serialize(array("$filename no such file")))."</articadatascgi>";
		return;
	}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$results[]="Copy to $filename to /root ";
	@copy($filename, "/root/" .basename($filename));
	@unlink($filename);
	chdir("/root");
	exec("$tar -xvf /root/".basename($filename)." -C / 2>&1",$results);
	$results[]="Done...";
	
	if(preg_match("#^nginx-#", $filename)){
		$results[]="Ask to restarting nginx";
		shell_exec("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	}
	
	@unlink($filename);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
	shell_exec("$nohup /usr/share/artica-postfix/bin/process1 --force ".time()." >/dev/null 2>&1 &");
	
}

