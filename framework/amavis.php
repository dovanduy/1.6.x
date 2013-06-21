<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["reload-tenir"])){reload_tenir();exit;}
if(isset($_GET["per-user-mysql"])){per_user_mysql();exit;}

function service_cmds(){
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/artica-postfix $cmds amavis --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function reload_tenir(){
	exec("/usr/share/artica-postfix/bin/artica-install --amavis-reload",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}
function per_user_mysql(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.amavis-db.php --init";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	shell_exec("/etc/init.d/amavis-db start");
	
}


