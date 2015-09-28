<?php
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["install-key-file"])){install_key_file();exit;}
if(isset($_GET["hook-local-service"])){hook_local_service();exit;}
if(isset($_GET["unhook-local-service"])){unhook_local_service();exit;}

if(isset($_GET["pattern-date"])){kav4ProxyPatternDate();exit;}
if(isset($_GET["license-infos"])){license_infos();exit;}
if(isset($_GET["is-installed"])){is_installed();exit;}


while (list ($num, $line) = each ($_GET)){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die();


function services_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	exec("$php /usr/share/artica-postfix/exec.status.php --hotspot 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}

function is_installed(){
	
	if(!is_file("/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager")){
		echo "<articadatascgi>NO</articadatascgi>";
		return;
	}
	echo "<articadatascgi>YES</articadatascgi>";
}



function install_key_file(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/kav4license.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/kav4license.install.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.kav4proxy.license-manager.php --install-key \"{$_GET["install-key-file"]}\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function hook_local_service(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/kav4Proxy.enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/kav4Proxy.enable.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.kav4proxy.php --enable-icap >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function unhook_local_service(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/kav4Proxy.enable.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/kav4Proxy.enable.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.kav4proxy.php --disable-icap >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function license_infos(){
	$unix=new unix();
	$tmpstr=$unix->FILE_TEMP();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$kav4proxyCache="/etc/artica-postfix/KAV4PROXY_LICENSE_INFO";
	

	if(!is_file($kav4proxyCache)){
		shell_exec2("$nohup $php5 /usr/share/artica-postfix/exec.kav4proxy.php --license >/dev/null 2>&1 &");
	}
	echo "<articadatascgi>". base64_encode(@file_get_contents($kav4proxyCache))."</articadatascgi>";


}

function kav4ProxyPatternDatePath(){
	$unix=new unix();
	$Kav4ProxyDatabasePath=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/Kav4ProxyDatabasePath"));
	if($Kav4ProxyDatabasePath==null){$Kav4ProxyDatabasePath="/home/artica/squid/kav4proxy/bases";}
	
	
	
	$XMLS["master.xml"]=true;
	$XMLS["u0607g.xml"]=true;
	$XMLS["upd-0607g.xml"]=true;
	$XMLS["masterv2.xml"]=true;
	$XMLS["av-i386-0607g.xml"]=true;
	$XMLS["kdb-i386-0607g.xml"]=true;
	$XMLS["kdb-i386-1211g.xml"]=true;
	$XMLS["kdb-i386-1211g.xml"]=true;
	
	while (list ($num, $ligne) = each ($XMLS) ){
		if(is_file("$Kav4ProxyDatabasePath/$num")){
			writelogs_framework("Detected `$num` in $Kav4ProxyDatabasePath",__FUNCTION__,__FILE__,__LINE__);
			return "$Kav4ProxyDatabasePath/$num";
		}
		writelogs_framework("\"$Kav4ProxyDatabasePath/$num\" no such file",__FUNCTION__,__FILE__,__LINE__);
	}
	
	return "$Kav4ProxyDatabasePath/master.xml";
}



function kav4ProxyPatternDate(){
	$unix=new unix();
	$base=kav4ProxyPatternDatePath();
	
	if(!is_file($base)){writelogs_framework("$base no such file",__FUNCTION__,__FILE__,__LINE__); return;}
		$f=explode("\n",@file_get_contents($base));
		$reg='#UpdateDate="([0-9]+)\s+([0-9]+)"#';

		while (list ($num, $ligne) = each ($f) ){
			if(preg_match($reg,$ligne,$re)){
				writelogs_framework("Found {$re[1]} {$re[2]}",__FUNCTION__,__FILE__,__LINE__);
				echo "<articadatascgi>". base64_encode(trim($re[1]).";".trim($re[2]))."</articadatascgi>";
				return;
			}
		}
		writelogs_framework("Not found",__FUNCTION__,__FILE__,__LINE__);
}
