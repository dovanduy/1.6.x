<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);



if(isset($_GET["build-server-config"])){build_config_server();exit;}
if(isset($_GET["volumes"])){volumes();exit;}
if(isset($_GET["iscsi-search"])){iscsi_search();exit;}
if(isset($_GET["iscsi-sessions"])){iscsi_client_sessions();exit;}



while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function volumes(){
@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/proc.net.iet.volume",@file_get_contents("/proc/net/iet/volume"));
@chmod("/usr/share/artica-postfix/ressources/logs/web/proc.net.iet.volume",0755);
}


function build_config_server(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system_disks_iscsi_progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system_disks_iscsi_progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.iscsi.php --build --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	
	
}
function iscsi_search(){
	
	$unix=new unix();
	$uuid=$unix->GetUniqueID();
	$hostname=$unix->hostname_g();
	$hostnameR=explode(".",$hostname);
	krsort($hostnameR);
	$hostname=@implode(".", $hostnameR);
	
	@file_put_contents("/etc/iscsi/initiatorname.iscsi","GenerateName=yes\n");
	
	
	$ip=$_GET["iscsi-search"];
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm --mode discovery --type sendtargets --portal $ip 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$array=array();
	while (list ($index, $line) = each ($results)){
		if(preg_match("#Invalid Initiatorname#", $line)){
			shell_exec("/etc/init.d/open-iscsi restart");
			return;
		}
		
		if(!preg_match("#([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){continue;}
		$array[$re[1]][]=array("PORT"=>$re[2],"ID"=>$re[3],"ISCSI"=>$re[4],"FOLDER"=>$re[5],"IP"=>$re[1]);
	}

	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi-search.array", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/iscsi-search.array", 0755);
}
function iscsi_client_sessions(){
	$unix=new unix();
	$iscsiadm=$unix->find_program("iscsiadm");
	$cmd="$iscsiadm -m session 2>&1";
	exec($cmd,$results);
	writelogs_framework("$cmd = ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$array=array();
	while (list ($index, $line) = each ($results)){
		if(!preg_match("#([0-9\.]+):([0-9]+),([0-9]+)\s+(.+?):(.+)#",$line,$re)){continue;}
		$array[$re[1]][]=array("PORT"=>$re[2],"ID"=>$re[3],"ISCSI"=>$re[4],"FOLDER"=>$re[5],"IP"=>$re[1]);
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi-sessions.array", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/iscsi-sessions.array", 0755);
	

}