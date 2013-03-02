<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["status"])){status();exit;}
if(isset($_GET["watchdog"])){watchdog();exit;}
if(isset($_GET["legal-license"])){getlegallicense();exit;}
if(isset($_GET["get-task-list"])){get_task_list();exit;}
if(isset($_GET["task-start"])){task_start();exit;}
if(isset($_GET["task-stop"])){task_stop();exit;}
if(isset($_GET["license-status"])){license_status();exit;}
if(isset($_GET["license-install"])){license_install();exit;}
if(isset($_GET["license-remove"])){license_remove();exit;}
if(isset($_GET["as-info"])){as_info();exit;}
if(isset($_GET["av-info"])){av_info();exit;}
if(isset($_GET["build"])){build_conf();exit;}
if(isset($_GET["syslog-query"])){maillog_query();exit;}
if(isset($_GET["reset-web-password"])){reset_web_password();exit;}
if(isset($_GET["apply-config"])){apply_config();exit;}
if(isset($_GET["setup"])){setup();exit;}
if(isset($_GET["get-task-logs"])){task_logs_list();exit;}
if(isset($_GET["get-task-events"])){task_logs_events();exit;}
if(isset($_GET["reset-password"])){reset_password();exit;}



reset($_GET);
while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --klms --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
}
function InfoToSyslog(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.klms.php --InfoToSyslog 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	
}


function watchdog(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.klms.php --watchdog 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	InfoToSyslog();
}

function getlegallicense(){
	$f=@file_get_contents("/opt/kaspersky/klms/share/doc/LICENSE");
	$datas=base64_encode($f);
	echo "<articadatascgi>$datas</articadatascgi>";
}

function get_task_list(){
	exec("/opt/kaspersky/klms/bin/klms-control --get-task-list 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#Name:(.+)#", $ligne,$re)){
			$taskname=trim($re[1]);
			continue;
		}
		
		if(preg_match("#^ID:.*?([0-9]+)#", trim($ligne),$re)){
			if(isset($al[$re[1]])){continue;}
			$array[$taskname]["ID"]=$re[1];
			$al[$re[1]]=true;
			continue;
		}
		
		if(preg_match("#State:(.+)#", $ligne,$re)){
			$array[$taskname]["State"]=trim($re[1]);
			continue;
		}
		if(preg_match("#Runtime ID:.*?([0-9]+)#", $ligne,$re)){
			$array[$taskname]["RID"]=$re[1];
			continue;
		}
							
	}

	writelogs_framework(count($array)." items of ". count($results)." lines",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>"; 
	
}
function task_start(){
	$id=$_GET["task-start"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$cmd="/opt/kaspersky/klms/bin/klms-control --start-task $id >/usr/share/artica-postfix/ressources/logs/web/KlmsTask$id.txt 2>&1 &";
	shell_exec("$cmd");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	sleep(1);
	writelogs_framework("done...",__FUNCTION__,__FILE__,__LINE__);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/KlmsTask$id.txt", 0777);
	
}
function task_stop(){
	$id=$_GET["task-stop"];
	exec("/opt/kaspersky/klms/bin/klms-control --stop-task $id 2>&1",$results);
	writelogs_framework("/opt/kaspersky/klms/bin/klms-control --stop-task $id",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function license_status(){
	$cmd="/opt/kaspersky/klms/bin/klms-control -l --query-status 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}
function license_install(){
	$key_path=base64_decode($_GET["key-path"]);
	$cmd="/opt/kaspersky/klms/bin/klms-control -l --install-active-key \"$key_path\" 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	InfoToSyslog();	
	
}
function license_remove(){
	$key_path=base64_decode($_GET["key-path"]);
	$cmd="/opt/kaspersky/klms/bin/klms-control -l --revoke-active-key 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
	InfoToSyslog();	
	
}
function as_info(){
	$cmd="/opt/kaspersky/klms/bin/klms-control --get-asp-bases-info 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}
function av_info(){
	$cmd="/opt/kaspersky/klms/bin/klms-control --get-avs-bases-info 2>&1";
	
	exec($cmd,$results);
	writelogs_framework($cmd." ".@implode(" >",$results),__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(trim(@implode(" ",$results)))."</articadatascgi>";	
}

function build_conf(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.klms.php --build 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}
function maillog_query(){
	$unix=new unix();
	$head=$unix->find_program("head");
	$grep=$unix->find_program("grep");
	$cat=$unix->find_program("cat");
	$tail=$unix->find_program("tail");
	$pattern=base64_decode($_GET["syslog-query"]);
	$path=$_GET["maillog"];
	
	
	if($pattern<>null){
		$cmd="$grep -E '[0-9:]+\s+.*?\s+KLMS:\s+' $path|$grep -E '$pattern'|$tail -n {$_GET["rp"]}";
	}else{
		$cmd="$grep -E '[0-9:]+\s+.*?\s+KLMS:\s+' $path|$tail -n {$_GET["rp"]}";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}
function reset_web_password(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.klms.php --resetpwd 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}
function apply_config(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.klms.php --build-restart 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}
function setup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.klms.php --setup 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}
function task_logs_list(){
	
	$unix=new unix();
	$files=$unix->DirFiles("/var/log/kaspersky/klms");
	while (list ($file, $ligne) = each ($files) ){
		if(preg_match("#^(.*?)_[0-9\-]+#", $file,$re)){
			$array[$re[1]][]=$file;
		}
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
	
}
function task_logs_events(){
	$taskfile=$_GET["taskfile"];
	$rp=$_GET["rp"];
	$search=base64_decode($_GET["search"]);
	$unix=new unix();
	$head=$unix->find_program("head");
	$grep=$unix->find_program("grep");
	$cat=$unix->find_program("cat");
	$tail=$unix->find_program("tail");

	$path="/var/log/kaspersky/klms/$taskfile";
	if(!is_file($path)){
		$array[]="$path no such file";
		writelogs_framework("$path no such file",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
	if($search<>null){$search=" -E '$search' ";
	$cmd="grep$search $path|$tail -n $rp";
	}else{
		$cmd="$tail -n $rp $path";
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
	
}

function reset_password(){
	$password=base64_decode($_GET["reset-password"]);
	$unix=new unix();
	$password=$unix->shellEscapeChars($password);
	$cmd="/opt/kaspersky/klms/bin/klms-control --set-web-admin-password $password 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

?>