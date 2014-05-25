<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["uncompress"])){uncompress();exit;}
if(isset($_GET["save-client-config"])){save_client_config();exit;}
if(isset($_GET["set-backup-server"])){save_client_server();exit;}




while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function uncompress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$filename=$_GET["uncompress"];
	
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	
	if(!is_file($FilePath)){
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	writelogs_framework("$tar -xf $FilePath -C /usr/share/",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$tar -xf $FilePath -C /usr/share/");
	$VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart >/dev/null 2>&1 &");
	
	
	
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
	
}
function save_client_config(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --comps >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function save_client_server(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --backup-server >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

