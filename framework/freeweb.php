<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["mode-security-log"])){mod_security_logs();exit;}
if(isset($_GET["reconfigure"])){freeweb_reconfigure();exit;}
if(isset($_GET["loaded-modules"])){freeweb_modules();exit;}
if(isset($_GET["force-resolv"])){force_resolv();exit;}
if(isset($_GET["rebuild-vhost"])){rebuild_vhost();exit;}
if(isset($_GET["getidof"])){getidof();exit;}
if(isset($_GET["ApacheAccount"])){ApacheAccount();exit;}
if(isset($_GET["rouncube-plugins"])){roundcube_plugins_list();exit;}

if(isset($_GET["checks-site"])){FreeWebsCheck();exit;}
if(isset($_GET["apache-cmds"])){apache_service_cmds();exit;}
if(isset($_GET["users-webdav"])){apache_webdavusrs();exit;}
if(isset($_GET["watchdog-config"])){apache_watchdog();exit;}
if(isset($_GET["changeinit-on"])){change_init_on();exit;}
if(isset($_GET["changeinit-off"])){change_init_off();exit;}
if(isset($_GET["articaget"])){articaget();exit;}
if(isset($_GET["restore-site"])){restore_site();exit;}
if(isset($_GET["ScanSize"])){ScanSize();exit;}
if(isset($_GET["roudce-replic-host"])){roundcube_replic_single();exit;}
if(isset($_GET["display-config"])){display_config();exit;}

while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();

function apache_service_cmds(){
	$cmds=$_GET["apache-cmds"];
	exec("/etc/init.d/artica-postfix $cmds apachesrc --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function force_resolv(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --resolv --force >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function apache_watchdog(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --monit >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function apache_webdavusrs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.webdav.users.php >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function rebuild_vhost(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$servername=$_GET["servername"];
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function ScanSize(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --ScanSize >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function getidof(){
	$unix=new unix();
	$uid=trim(base64_decode($_GET["getidof"]));
	if($uid==null){return;}
	$id=$unix->find_program("id");
	exec("$id \"$uid\" 2>&1",$results);
	writelogs_framework("$id \"$uid\" 2>&1 ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	$datas=trim(@implode("", $results));
	if(!preg_match("#uid=([0-9]+).+?#", $datas)){echo "<articadatascgi>FALSE</articadatascgi>";}else{echo "<articadatascgi>TRUE</articadatascgi>";}
}

function ApacheAccount(){
	$unix=new unix();
	$array=array($unix->APACHE_SRC_ACCOUNT(),$unix->APACHE_SRC_GROUP());
	echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
	return;
}

function mod_security_logs(){
	$servername=$_GET["servername"];
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$cmd="$tail -n 500 /var/log/apache2/$servername/modsec_debug_log 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}

function freeweb_reconfigure(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --build >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function freeweb_modules(){
	$unix=new unix();
	$apache2ctl=$unix->find_program("apache2ctl");
	if(!is_file($apache2ctl)){echo "<articadatascgi>".base64_encode(serialize(array("apache2ctl no such file")))."</articadatascgi>";return;}
	$cmd="$apache2ctl -t -D DUMP_MODULES 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function roundcube_plugins_list(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --rouncube-plugins {$_GET["servername"]} 2>&1";
	exec("$cmd",$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}

function FreeWebsCheck(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sitename=$_GET["sitename"];
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --sitename \"$sitename\" --no-httpd-conf --noreload --verbose";
	$results[]=$cmd;
	exec($cmd,$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}
function change_init_on(){
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	$service="apache2";
	if(is_file("/etc/init.d/apache2")){$service="apache2";}
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service remove >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --del $service >/dev/null 2>&1");}
	
	
}

function change_init_off(){
	$unix=new unix();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	$service="apache2";
	if(is_file("/etc/init.d/apache2")){$service="apache2";}
	if(is_file("/etc/init.d/apache")){$service="apache";}
	if(is_file("/etc/init.d/httpd")){$service="httpd";}
	if(is_file($debianbin)){shell_exec("$debianbin -f $service defaults >/dev/null 2>&1");}
	if(is_file($redhatbin)){shell_exec("$redhatbin --add $service >/dev/null 2>&1");}	
	
}
function articaget(){
	$sitename=$_GET["articaget"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$results[]="FRAMEWORK ORDER TO BACKUP [$sitename]";
	$cmd="$php /usr/share/artica-postfix/exec.freeweb.php --backupsite \"$sitename\" --verbose 2>&1";	
	exec($cmd,$results);
	writelogs_framework("$cmd ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";		
}

function restore_site(){
	$path=trim(base64_decode($_GET["path"]));
	$sitename=trim(base64_decode($_GET["sitename"]));
	if($sitename==null){$sitename="DEFAULT";}
	$instance_id=trim($_GET["instance-id"]);
	if(!is_numeric($instance_id)){$instance_id=0;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd="$nohup $php /usr/share/artica-postfix/exec.freeweb.php --restore \"$sitename\" \"$path\" $instance_id --verbose >>/usr/share/artica-postfix/ressources/logs/web/freewebs.restore 2>&1 &";	
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
}
function roundcube_replic_single(){
	$serv=$_GET["servername"];
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/xec.freeweb.rdcube-replic.php --host $serv >/dev/null 2>&1 &");
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function display_config(){
	
	$conf="/etc/apache2/sites-enabled/artica-{$_GET["servername"]}.conf";
	if(!is_file($conf)){
		echo "<articadatascgi>".base64_encode("$conf no such file")."</articadatascgi>";	
		return;	
	}
	echo "<articadatascgi>".base64_encode(@file_get_contents($conf))."</articadatascgi>";	
	
}

?>