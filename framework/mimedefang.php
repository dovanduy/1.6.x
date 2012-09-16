<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["reload-tenir"])){reload_tenir();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["postfix-milter"])){postfix_milter();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/mimedefang $cmds 2>&1",$results);
	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function reload_tenir(){
	writelogs_framework("Reloading mimedefang...",__FUNCTION__,__FILE__,__LINE__);	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.mimedefang.php 2>&1",$results);
	exec("/etc/init.d/mimedefang reload 2>&1",$results);
	writelogs_framework("Reloading mimedefang done",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/mimedefang restart 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}
function postfix_milter(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}



function status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --mimedefang --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function getramtmpfs(){
	$dir="/var/spool/MIMEDefang";
	if($dir==null){return;}
	$unix=new unix();
	$df=$unix->find_program("df");
	$cmd="$df -h \"$dir\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$df -h \"$dir\" 2>&1",$results);
	while (list ($key, $value) = each ($results) ){
		
		if(!preg_match("#tmpfs\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.]+)%\s+.*?MIMEDefang#", $value,$re)){
			writelogs_framework("$value no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		writelogs_framework("{$re[2]}:{$array["PURC"]}%",__FUNCTION__,__FILE__,__LINE__);
			$array["SIZE"]=$re[1];
			$array["PURC"]=$re[4];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			return;
		
	}
		
}
