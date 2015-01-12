<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["REPLIC_CONF"]=false;
$GLOBALS["NO_RELOAD"]=false;
$GLOBALS["NO_BUILD_MAIN"]=false;
$GLOBALS["pidStampReload"]="/etc/artica-postfix/pids/".basename(__FILE__).".Stamp.reload.time";
$GLOBALS["debug"]=true;

if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--replic-conf#",implode(" ",$argv),$re)){$GLOBALS["REPLIC_CONF"]=true;}
if(preg_match("#--no-reload#",implode(" ",$argv),$re)){$GLOBALS["NO_RELOAD"]=true;}
if(preg_match("#--no-buildmain#",implode(" ",$argv),$re)){$GLOBALS["NO_BUILD_MAIN"]=true;}




$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');

execute_autconfig();

function build_progress($text,$pourc){
	$filename=basename(__FILE__);
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid-autoconf.progress";
	echo "[{$pourc}%] $filename: $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["OUTPUT"]){usleep(5000);}


}

function execute_autconfig(){
	$sock=new sockets();
	build_progress("Execute....",5);
	
	build_progress("Loading settings....",5);
	$SquidAutoconfWizard=unserialize($sock->GET_INFO("SquidAutoconfWizard"));
	
	$DOMAIN=$SquidAutoconfWizard["DOMAIN"];
	$LOCALNET=$SquidAutoconfWizard["LOCALNET"];
	$PROXY=$SquidAutoconfWizard["PROXY"];
	$PORT=$SquidAutoconfWizard["PORT"];
	
	echo "DOMAIN.........: $DOMAIN\n";
	echo "LOCALNET.......: $LOCALNET\n";
	echo "PROXY..........: $PROXY:$PORT\n";
	
	if($DOMAIN==null){
		build_progress("Missing domain....",110);
		return;
	}
	if($LOCALNET==null){
		build_progress("Missing LOCALNET....",110);
		return;
	}	
	if($PROXY==null){
		build_progress("Missing PROXY....",110);
		return;
	}	
	if(!is_numeric($PORT)){build_progress("Missing PROXY PORT....",110);
		return;
	}

	build_progress("Creating wpad.$DOMAIN....",10);
	
	$webserver="wpad.$DOMAIN";
	$sock->SET_INFO("EnableFreeWeb",1);
	build_progress("Creating wpad.$DOMAIN (loading class)",11);
	$free=new freeweb($webserver);
	$free->servername=$webserver;
	$free->groupware="WPADDYN";
	$free->Params["ServerAlias"]["wpad"]=true;
	$free->CreateSite();
	build_progress("Building wpad.$DOMAIN and alias wpad",15);
	build_progress("Creating wpad.$DOMAIN (saving configuration)",12);
	
	build_progress("Creating wpad.$DOMAIN (reloading configuration)",13);
	rebuild_vhost($webserver);
	build_progress("Creating wpad.$DOMAIN (reloading configuration {done})",14);
	
	build_progress("Building first rule...",15);
	$rulnename=mysql_escape_string2("Wizard - all to $PROXY:$PORT");
	
	
	
	$sql="INSERT IGNORE INTO `wpad_rules` (`rulename`,`enabled`,`zorder`,`dntlhstname`) VALUES ('$rulnename',1,0,1)";
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("wpad_rules", "zorder")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
	}
	if(!$q->FIELD_EXISTS("wpad_sources_link", "zorder")){
		$q->QUERY_SQL("ALTER TABLE `wpad_sources_link` ADD `zorder`  smallint( 2 ) DEFAULT '0',ADD INDEX (`zorder`)");
	}
	if(!$q->FIELD_EXISTS("wpad_rules", "dntlhstname")){
		$q->QUERY_SQL("ALTER TABLE `wpad_rules` ADD `dntlhstname`  smallint( 1 ) DEFAULT '0'");
	}
	if(!$q->FIELD_EXISTS("wpad_destination_rules", "rulename")){
		$q->QUERY_SQL("ALTER TABLE `wpad_destination_rules` ADD `rulename` VARCHAR(255) NOT NULL, ADD INDEX (`rulename`)");
		build_progress("Building first rule...MySQL error",110);
		if(!$q->ok){echo $q->mysql_error."\n";}
		return;
	}
	
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";
		build_progress("Building first rule...MySQL error",110);
		return;
	}
	
	$MAIN_RULE_ID=intval($q->last_id);
	if($MAIN_RULE_ID==0){
		build_progress("Building first rule...MAIN_RULE_ID = 0!",110);
		return;
	}
	
	$zmd5=md5("$MAIN_RULE_ID$PROXY$PORT");
	build_progress("Add destination $PROXY:$PORT",20);
	
	$q->QUERY_SQL("INSERT IGNORE INTO wpad_destination (zmd5,aclid,proxyserver,proxyport,zorder)
			VALUES ('$zmd5','$MAIN_RULE_ID','$PROXY','$PORT',0)");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("Add destination $PROXY:$PORT MySQL error",110);
		return;
	}
	
	build_progress("Creating Proxy object `Everyone`",25);
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqgroups WHERE `GroupType`='all'"));
	$SourceGroupID=intval($ligne["ID"]);
	if($SourceGroupID==0){
		$sql="INSERT IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`) VALUES ('Everyone','all','1','','');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("Creating Proxy object `Everyone` MySQL error",110);
			return;
		}
		$SourceGroupID=intval($q->last_id);
	}
	
	if($SourceGroupID==0){
		build_progress("Creating Proxy object `Everyone` SourceGroupID = 0!",110);
		return;
	}
	
	build_progress("Creating Proxy object `WPAD - Local networks`",25);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID FROM webfilters_sqgroups WHERE `GroupName`='WPAD - Local networks'"));
	$NetWorkGroupID=intval($ligne["ID"]);
	
	if($NetWorkGroupID==0){
		$sql="INSERT IGNORE INTO webfilters_sqgroups (GroupName,GroupType,enabled,`acltpl`,`params`) 
				VALUES ('WPAD - Local networks','src','1','','');";
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress("Creating Proxy object `WPAD - Local networks` MySQL error",110);
			return;
		}
		$NetWorkGroupID=intval($q->last_id);
		
		
	}
	
	if($NetWorkGroupID==0){
		build_progress("Creating Proxy object `WPAD - Local networks` NetWorkGroupID = 0!",110);
		return;
	}
	
	$IP=new IP();
	$LOCALNET_ARRAY=array();
	if(strpos($LOCALNET, ",")>0){
		$LOCALNET_ARRAY_TEMP=explode(",",$LOCALNET);
		while (list ($none, $line) = each ($LOCALNET_ARRAY_TEMP) ){
			$line=trim($line);
			if(!$IP->isIPAddressOrRange($line)){continue;}
			$LOCALNET_ARRAY[]="('$line','$NetWorkGroupID','1','')";
		}
	}else{
		if($IP->isIPAddressOrRange(trim($LOCALNET))){
			$LOCALNET_ARRAY[]="('$LOCALNET','$NetWorkGroupID','1','')";
		}
	}
	
	build_progress("Filling Proxy object `WPAD - Local networks`",30);
	
	
	$q->QUERY_SQL("DELETE FROM webfilters_sqitems WHERE gpid=$NetWorkGroupID");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("Filling Proxy object `WPAD - Local networks` MySQL error",110);
		return;
	}
	
	$sql="INSERT INTO webfilters_sqitems (pattern,gpid,enabled,other)
	VALUES ".@implode(",", $LOCALNET_ARRAY);
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("Filling Proxy object `WPAD - Local networks` MySQL error",110);
		return;
	}
	
	
	build_progress("Linking Everyone - $SourceGroupID - to rule $MAIN_RULE_ID",30);
	$zmd5=md5("$MAIN_RULE_ID$SourceGroupID");
	$q->QUERY_SQL("INSERT INTO wpad_sources_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$MAIN_RULE_ID','0','$SourceGroupID',1)");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL error",110);
		return;
	}
	
	
	$zmd5=md5("$MAIN_RULE_ID$NetWorkGroupID");
	build_progress("Linking WPAD - Local networks - $NetWorkGroupID - to rule $MAIN_RULE_ID",50);
	$q->QUERY_SQL("INSERT INTO wpad_white_link (zmd5,aclid,negation,gpid,zorder) VALUES ('$zmd5','$MAIN_RULE_ID','0','$NetWorkGroupID',1)");
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL error",110);
		return;
	}
	build_progress("{success}",100);
		
}

function rebuild_vhost($servername){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername >/dev/null 2>&1");
	shell_exec($cmd);
	$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.freeweb.php --sitename $servername");

}
?>