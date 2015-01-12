<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MAIN_PATH"]="/usr/share/artica-postfix/ressources/conf/meta";
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");



if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--repair-tables"){repair_tables();exit;}
if($argv[1]=="--build-orders"){build_orders();exit;}
if($argv[1]=="--build-proxy"){build_proxy_configs();exit;}
if($argv[1]=="--extract"){extract_tgz($argv[2]);exit;}
if($argv[1]=="--syslog"){rotate_client($argv[2]);exit;}
if($argv[1]=="--psaux"){psaux_client($argv[2]);exit;}
if($argv[1]=="--philesight"){philesight_client($argv[2]);exit;}
if($argv[1]=="--metaevents"){metaevents_client($argv[2]);exit;}
if($argv[1]=="--metaevents2"){metaevents_client2($argv[2]);exit;}



if($argv[1]=="--syslaerts"){sysalerts_client($argv[2]);exit;}
if($argv[1]=="--smtp"){sysalerts_smtp_client($argv[2]);exit;}
if($argv[1]=="--snapshot"){snapshot_client($argv[2]);exit;}
if($argv[1]=="--articadaemons"){articadaemons_client($argv[2]);exit;}
if($argv[1]=="--squid-quota-size"){articasquid_quota_size_client($argv[2]);exit;}





if($argv[1]=="--status-ini"){global_status_ini($argv[2]);exit;}
if($argv[1]=="--scan-repo"){scan_software_repo($argv[2]);exit;}
if($argv[1]=="--delete-repo"){delete_software_repo($argv[2]);exit;}
if($argv[1]=="--delete-articapkg"){delete_artica_repo($argv[2],$argv[3]);exit;}
if($argv[1]=="--add-node"){add_node();exit;}
if($argv[1]=="--tests-notifs"){send_notifications("Test From Artica Meta","This is a test");exit;}
if($argv[1]=="--ping-host"){ping_host($argv[2]);exit;}
if($argv[1]=="--ping-group"){ping_group($argv[2]);exit;}

if(strlen($argv[1])>2){meta_events("Unable to understand command {$argv[1]}");}


execute();

function execute(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "cachetime:$cachetime\n";}
	
	$pid=@file_get_contents($pidfile);
	
	if($unix->process_exists($pid)){die();}
	
	$TimeEx=$unix->file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){if($TimeEx<20){return;}}
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	@file_put_contents($pidfile,getmypid());
	scan_repos();
	scan_software_repo();
	extract_all_tgz();
	checkufdb();
	clean_tables();
	
}

function checkufdb(){
	if($GLOBALS["VERBOSE"]){echo "checkufdb()\n";}
	$sock=new sockets();
	$unix=new unix();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	
	$timeFile="/etc/artica-postfix/pids/exec.artica-meta-server.php.checkufdb.time";
	$unix=new unix();
	$time=$unix->file_time_min($timeFile);
	if($time<1440){return;}
	@unlink($timeFile);
	@file_put_contents($timeFile, time());
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if($GLOBALS["VERBOSE"]){echo "$php /usr/share/artica-postfix/exec.squid.blacklists.php --ufdbmaster\n";}
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.blacklists.php --ufdbmaster >/dev/null 2>&1 &");
	
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	if(!is_file("$ArticaMetaStorage/webfiltering/ufdbartica.txt")){
		
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$ArticaMetaStorage/webfiltering/ufdbartica.txt OK\n";}
	}
}


function build_orders(){
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){die();}
	$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
	if($EnableArticaMetaServer==0){return;}
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	@file_put_contents($pidfile,getmypid());
	$ARRAY=array();
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta/hosts",0755,true);
	$q=new mysql_meta();
	
	$unix=new unix();
	$files=$unix->DirFiles("/usr/share/artica-postfix/ressources/conf/meta/hosts");
	while (list ($filename, $main) = each ($files) ){
		if($GLOBALS["VERBOSE"]){echo "Removing /usr/share/artica-postfix/ressources/conf/meta/hosts/$filename\n";}
		@unlink("/usr/share/artica-postfix/ressources/conf/meta/hosts/$filename");
	}
	
	$sql="SELECT * FROM metaorders ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		meta_admin_mysql(0, "MySQL error while building orders", $q->mysql_error,__FILE__,__LINE__);
		return;
	}
	
	if(mysql_num_rows($results)==0){
		meta_events("No order saved..Aborting ping computers");
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		$orderid=$ligne["orderid"];
		$ordersubject=$ligne["ordersubject"];
		$ordercontent=$ligne["ordercontent"];
		$FULL_SERVERS[$uuid]=true;
		$ARRAY[$uuid][$orderid]=array("SUBJECT"=>$ordersubject,"CONTENT"=>$ordercontent);
		
	}
	
	while (list ($uuid, $main) = each ($ARRAY) ){
		$filepath="/usr/share/artica-postfix/ressources/conf/meta/hosts/$uuid.orders";
		@file_put_contents($filepath, base64_encode(serialize($main)));
		
		
	}
	
	$ArticaMetaUseSendClient=intval($sock->GET_INFO("ArticaMetaUseSendClient"));
	if($ArticaMetaUseSendClient==0){return;}
	
	$sql="SELECT * FROM metahosts WHERE blacklisted=0";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$public_ip=$ligne['public_ip'];
		$uuid=$ligne["uuid"];
		if(!isset($FULL_SERVERS[$uuid])){continue;}
		$uri="https://$public_ip:9000/artica.meta.listener.php?wakeup=yes";
		$curl=new ccurl($uri,true);
		$curl->NoHTTP_POST=true;
		$curl->Timeout=5;
		if(!$curl->get()){
			meta_admin_mysql(1, "$uuid: Unable to ping $public_ip:9000", $q->mysql_error,__FILE__,__LINE__);
			continue;
		}
		
		if(!preg_match("#<ARTICA_META>SUCCESS</ARTICA_META>#is", $curl->data)){
			meta_admin_mysql(1, "$uuid: ping $public_ip:9000 failed",$curl->errors,__FILE__,__LINE__);
		}else{
			meta_admin_mysql(2, "$uuid: Ping $public_ip:9000 success",$curl->errors,__FILE__,__LINE__);
		}
		
	}
	
}

function ping_group($gpid){
	if(!is_numeric($gpid)){return;}
	if($gpid==0){return;}
	$q=new mysql_meta();
	$sql="SELECT uuid FROM metagroups_link WHERE gpid=$gpid";
	$results = $q->QUERY_SQL($sql);
	
	if(!$this->ok){
		meta_admin_mysql(1, "MySQL error",$q->mysql_error,__FILE__,__LINE__);
		echo $q->mysql_error."\n$sql\n";return;
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		ping_host($uuid);
	}
}

function ping_host($uuid){
	
	$artica_meta=new mysql_meta();
	meta_events("ping_host to $uuid");
	$public_ip=$artica_meta->uuid_to_public_ip($uuid);
	if($public_ip==null){
		meta_admin_mysql(1, "$uuid: ping Failed public_ip is null",$curl->errors,__FILE__,__LINE__);
		return;
	}
	
	$uri="https://$public_ip:9000/artica.meta.listener.php?wakeup=yes";
	$curl=new ccurl($uri,true);
	$curl->NoHTTP_POST=true;
	$curl->Timeout=5;
	if(!$curl->get()){
		meta_admin_mysql(1, "$uuid: Unable to ping $public_ip:9000", $q->mysql_error,__FILE__,__LINE__);
		continue;
	}
	
	if(!preg_match("#<ARTICA_META>SUCCESS</ARTICA_META>#is", $curl->data)){
		meta_admin_mysql(1, "$uuid: ping $public_ip:9000 failed",$curl->errors,__FILE__,__LINE__);
	}else{
		meta_admin_mysql(2, "$uuid: Ping $public_ip:9000 success",$curl->errors,__FILE__,__LINE__);
	}
	
	meta_events("ping_host done");
	
}



function delete_artica_repo($filename,$repo){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);

	if($repo=="RELEASES"){$src="$ArticaMetaStorage/releases/$filename";}
	if($repo=="NIGHTLY"){$src="$ArticaMetaStorage/nightlys/$filename";}
	
	$splited_Dirname=dirname($src);
	$splited_FileName=basename($src);
	$splited_FileDir=str_replace(".tgz", "", $splited_FileName);
	$splited_TarGetDirectory="$splited_Dirname/$splited_FileDir";
	
	if(is_dir($splited_TarGetDirectory)){
		$rm=$unix->find_program("rm");
		shell_exec("rm -rf $splited_TarGetDirectory");
	}
	
	if(!is_file($src)){
		meta_events("$src no such file");
		if($GLOBALS["VERBOSE"]){echo "$src no such file\n";}
		return;
	}
	@unlink($src);
	scan_repos();
}

function meta_events($text){
	$unix=new unix();
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
		
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
		
		
	
	}
	$unix->events($text,"/var/log/artica-meta.log",false,$function,$line,$file);
	
}

function scan_repos_split($source_path){
	
	if(!is_file($source_path)){
		meta_events("$source_path no such file...");
		return array();
	}
	
	$unix=new unix();
	$split=$unix->find_program("split");
	$Dirname=dirname($source_path);
	$FileName=basename($source_path);
	$FileDir=str_replace(".tgz", "", $FileName);
	$TarGetDirectory="$Dirname/$FileDir";
	if(!is_dir($TarGetDirectory)){
		meta_events("Creating directory $TarGetDirectory");
		if(!@mkdir($TarGetDirectory,0755,true)){
			meta_events("Creating directory $TarGetDirectory FAILED");
			return;
		}
	}
	
	if(is_file("$TarGetDirectory/metaindex.txt")){
		
		return unserialize(@file_get_contents("$TarGetDirectory/metaindex.txt"));
	}
	$ARRAY=array();
	chdir("$TarGetDirectory");
	system("cd $TarGetDirectory");
	meta_events("$split -b 1m -d $source_path $FileName.");
	shell_exec("$split -b 1m -d $source_path $FileName.");
	chdir("/root");
	system("cd /root");
	
	meta_events("Scanning directory $TarGetDirectory");
	$files=$unix->DirFiles("$TarGetDirectory");
	while (list ($num, $ligne) = each ($files) ){
		$Splited_path="$TarGetDirectory/$num";
		$Splited_md5=md5_file($Splited_path);
		$ARRAY["$FileDir/$num"]=$Splited_md5;
	}
	
	if(count($ARRAY)>0){
		@file_put_contents("$TarGetDirectory/metaindex.txt", serialize($ARRAY));
		return $ARRAY;
	}
	
}



function delete_software_repo($filename=null){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);	
	if($filename<>null){
		$filepath="$ArticaMetaStorage/softwares/$filename";
		if(is_file($filepath)){
			@unlink($filepath);
			scan_software_repo();
		}
	
	}
}

function scan_software_repo($filename=null){
	$unix=new unix();
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/softwares",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);	
	
	if($filename<>null){
		$filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";
		if(is_file($filepath)){
			@unlink("$ArticaMetaStorage/softwares/$filename");
			@copy($filepath, "$ArticaMetaStorage/softwares/$filename");
			@unlink($filepath);
		}
		
	}
	
	$files=$unix->DirFiles("$ArticaMetaStorage/softwares");
	while (list ($num, $ligne) = each ($files) ){
		if($GLOBALS["VERBOSE"]){echo "Found $ArticaMetaStorage/softwares/$num\n";}
		$ARRAY[$num]["SIZE"]=@filesize("$ArticaMetaStorage/softwares/$num");
	}
	
	@file_put_contents("{$GLOBALS["MAIN_PATH"]}/softwares.db", base64_encode(serialize($ARRAY)));
	@chown("{$GLOBALS["MAIN_PATH"]}/softwares.db",$APACHE_ACCOUNT);
	
	
}

function scan_repos(){
	
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$sock=new sockets();
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);
	
	meta_events("Scanning $ArticaMetaStorage/releases");
	
	$unix=new unix();
	$files=$unix->DirFiles("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($files) ){
		meta_events("Found $ArticaMetaStorage/releases/$num");
		$ARRAY["RELEASES"][$num]=scan_repos_split("$ArticaMetaStorage/releases/$num");
	}
	
	$files=$unix->DirFiles("$ArticaMetaStorage/nightlys");
	while (list ($num, $ligne) = each ($files) ){
		meta_events("Found $ArticaMetaStorage/nightlys/$num");
		$ARRAY["NIGHTLY"][$num]=scan_repos_split("$ArticaMetaStorage/nightlys/$num");
	}	
	
	$dirs=$unix->dirdir("$ArticaMetaStorage/nightlys");
	while (list ($num, $ligne) = each ($dirs) ){
		$dirname=basename($num);
		if(!preg_match("#^artica-[\.0-9]+#", $dirname)){continue;}
		meta_events("Found $num");
		if(!is_file("$num/metaindex.txt")){
			meta_events("Remove $num metaindex not found");
			shell_exec("$rm -rf $num");
			continue;
		}
		$arr=unserialize(@file_get_contents("$num/metaindex.txt"));
		if(count($arr)<2){
			meta_events("Remove $num metaindex not enough files");
			shell_exec("$rm -rf $num");
			continue;
		}
		
	}
	$dirs=$unix->dirdir("$ArticaMetaStorage/releases");
	while (list ($num, $ligne) = each ($dirs) ){
		$dirname=basename($num);
		if(!preg_match("#^artica-[\.0-9]+#", $dirname)){continue;}
		meta_events("Found $num");
		if(!is_file("$num/metaindex.txt")){
			meta_events("Remove $num metaindex not found");
			shell_exec("$rm -rf $num");
			continue;
		}
		$arr=unserialize(@file_get_contents("$num/metaindex.txt"));
		if(count($arr)<2){
			meta_events("Remove $num metaindex not enough files");
			shell_exec("$rm -rf $num");
			continue;
		}
	
	}	
	
	meta_events("Saving {$GLOBALS["MAIN_PATH"]}/updates.db");
	@file_put_contents("{$GLOBALS["MAIN_PATH"]}/updates.db", base64_encode(serialize($ARRAY)));
	@chown("{$GLOBALS["MAIN_PATH"]}/updates.db",$APACHE_ACCOUNT);
	@chmod("{$GLOBALS["MAIN_PATH"]}/updates.db",0755);
	
}

function build_proxy_configs(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){die();}
	
	
	
	$sock=new sockets();
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	@mkdir("$ArticaMetaStorage/nightlys",0755,true);
	@mkdir("$ArticaMetaStorage/releases",0755,true);
	@mkdir("$ArticaMetaStorage/proxy",0755,true);
	$APACHE_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
	$q=new mysql_meta();
	@mkdir($GLOBALS["MAIN_PATH"],0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/conf/meta",0755,true);
	$results=$q->QUERY_SQL("SELECT * FROM squid_whitelists ORDER BY `pattern`");
	while ($ligne = mysql_fetch_assoc($results)) {$f[]="('{$ligne["zMD5"]}','{$ligne["pattern"]}')";}
	$prefix="INSERT IGNORE INTO `squid_whitelists` (`zMD5`,`pattern`) VALUES ".@implode(",", $f);
	@file_put_contents("$ArticaMetaStorage/proxy/squid_whitelists.db", base64_encode($prefix));
	@chown("$ArticaMetaStorage/proxy/squid_whitelists.db",$APACHE_ACCOUNT);
	
	$results=$q->QUERY_SQL("SELECT uuid FROM metahosts WHERE PROXY=1");
	while ($ligne = mysql_fetch_assoc($results)) {
		$q->CreateOrder($ligne["uuid"], "PROXY_PARAMS");
	}
	
}

function extract_all_tgz(){
	
	$sql="SELECT * FROM metahosts WHERE blacklisted=0";
	$q=new mysql_meta();
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$uuid=$ligne["uuid"];
		extract_tgz($uuid);
		global_status_ini($uuid);
		rotate_client($uuid);
		psaux_client($uuid);
		philesight_client($uuid);
		metaevents_client($uuid);
		metaevents_client2($uuid);
		sysalerts_smtp_client($uuid);
		articadaemons_client($uuid);
		bandwidth_squid_calc($uuid);
		articasquid_quota_size_client($uuid);
		$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT/snapshot.tar.gz";
		if(is_file($workingfile)){snapshot_client($uuid);}
	}
}

function rotate_client($uuid){
	$unix=new unix();
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/syslog";

	$syslog=new mysql_storelogs();
	$files=$unix->DirFiles($workingdir);
	while (list ($basepath, $none) = each ($files) ){
		$syslog->events("META: Rotate $basepath",__FUNCTION__,__LINE__);
		$syslog->ROTATE_ACCESS_TOMYSQL("$workingdir/$basepath");
	}
	
	
}


function sysalerts_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SYS_ALERTS";
	if(!is_dir($workingdir)){
		meta_events("$uuid: $workingdir no such directory");
		sysalerts_client_scan($uuid);
		return;
	}

	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SYS_ALERTS";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
	
	}
	sysalerts_client_scan($uuid);
	
}

function sysalerts_smtp_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SMTP_NOTIF";
	if(!is_dir($workingdir)){
		meta_events("$uuid: $workingdir no such directory");
		sysalerts_smtp_client_scan($uuid);
		return;
	}
	
	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SMTP_NOTIF";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
	
	}
	sysalerts_smtp_client_scan($uuid);	
	
}



function metaevents_client($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/META_EVENTS";
	if(!is_dir($workingdir)){return;}
	
	$unix=new unix();
	$sock=new sockets();
	$files=$unix->DirFiles($workingdir);
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/META_EVENTS";
	@mkdir($destdir,0755,true);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$workingdir/$filename";
		$destfile="$destdir/$filename";
		@unlink($destfile);
		@copy($sourcefile, $destfile);
		if(is_file($destfile)){@unlink($sourcefile);}
		
	}
	metaevents_client_scan($uuid);
	
	
}

function metaevents_client2($uuid){
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/CLIENT_META_EVENTS";
	if(!is_dir($workingDir)){return;}
	$unix=new unix();
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	$metaSql=new mysql_meta();
	$hostname=$metaSql->uuid_to_host($uuid);
	$q=new mysql();
	$prefix="INSERT IGNORE INTO `meta_admin_mysql` (`zmd5`,`uuid`,`zDate`,`content`,`hostname`,`subject`,`function`,`filename`,`line` ,	`severity` ,`sended`) VALUES ";
	
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		$targetfile="$workingfile.array";
		if(!$unix->uncompress($workingfile, $targetfile)){
			@unlink($workingfile);
			@unlink($targetfile);
			continue;
		}
		
		$BIG_ARRAY=unserialize(@file_get_contents($targetfile));
		@unlink($workingfile);
		@unlink($targetfile);

		$f=array();		
		while (list ($md5, $array) = each ($BIG_ARRAY) ){
			if(!is_array($array)){continue;}
			if(!is_numeric($array["TASKID"])){$array["TASKID"]=0;}
			$content=mysql_escape_string2($array["text"]);
			$subject=mysql_escape_string2($array["subject"]);
			$hostname=
			$zdate=$array["zdate"];
			$function=$array["function"];
			$file=$array["file"];
			$line=$array["line"];
			$TASKID=$array["TASKID"];
			$severity=$array["severity"];
			$md5=md5(serialize($array));
			$f[]="('$md5','$uuid','$zdate','$content','$hostname','$subject','$function','$file','$line','$severity',0)";
		}
		
		
		if(count($f)>0){
			$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
			$f=array();
			if(!$q->ok){
				meta_admin_mysql(0, "MySQL error", "$q->mysql_error",__FILE__,__LINE__);
			}
		}
	}	
	
}


function sysalerts_smtp_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	$meta=new mysql_meta();
	$hostname=$meta->uuid_to_host($uuid);
	$tag=$meta->uuid_to_tag($uuid);
	
	if($tag<>null){$hostname="$hostname/$tag";}
	
	$severityT[0]="Alert";
	$severityT[1]="Warning";
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SMTP_NOTIF";
	$files=$unix->DirFiles($destdir);
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		
		$filetime=$unix->file_time_min($sourcefile);
		if($filetime>240){
			@unlink($sourcefile);
			continue;
		}
		
		$array=unserialize(@file_get_contents($sourcefile));
		if(!is_array($array)){
			meta_events("$sourcefile no such array");
			@unlink($sourcefile);
			continue;
		}
		
		
		
		
		$zdate=$array["zdate"];
		$subject=$array["subject"];
		$text=$array["text"];
		$severity=$array["severity"];
		$function=$array["function"];
		$file=$array["file"];
		$line=$array["line"];
		
		$md5=md5("$uuid$subject");
		
		if(isset($GLOBALS["NOTIFS"][$md5])){@unlink($sourcefile);continue;}
		$GLOBALS["NOTIFS"][$md5]=true;
		
		$subject="[$hostname]:{$severityT[$severity]} $subject";
		$content="Operation created by $hostname on $zdate by $file ($function in line:$line)\nuuid:$uuid\n$text\n";
		
		if(!send_notifications($subject,$content)){
			continue;
		}
		@unlink($sourcefile);
		
	}
	
}


function sysalerts_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/SYS_ALERTS";
	$files=$unix->DirFiles($destdir);
	
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		$destfile="$destdir/$filename.sql";
		meta_events("$uuid: Uncompress $sourcefile...");
		$unix->uncompress($sourcefile, $destfile);
		if(!is_file($destfile)){continue;}
		$q->QUERY_SQL(@file_get_contents($destfile),"artica_events");
		if(!$q->ok){
			meta_events("$uuid:$q->mysql_error");
			@unlink($destfile);
			continue;
		}
		@unlink($sourcefile);
		@unlink($destfile);
	}	
	
	
}


function metaevents_client_scan($uuid){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	if(!$q->test_mysql_connection()){return;}
	
	$ArticaMetaStorage=$sock->GET_INFO("ArticaMetaStorage");
	if($ArticaMetaStorage==null){$ArticaMetaStorage="/home/artica-meta";}
	$destdir="$ArticaMetaStorage/$uuid/META_EVENTS";
	$files=$unix->DirFiles($destdir);
	
	if(!$q->FIELD_EXISTS("meta_admin_mysql", "zmd5", "artica_events")){
		meta_events("Patching meta_admin_mysql");
		$meta=new mysql_meta();
		$q->QUERY_SQL("DROP TABLE `meta_admin_mysql`","artica_events");
		$meta->create_table_meta_admin_mysql();
	}
	
	
	while (list ($filename, $ARRAY) = each ($files) ){
		$sourcefile="$destdir/$filename";
		$destfile="$destdir/$filename.sql";
		meta_events("$uuid: Uncompress $sourcefile...");
		$unix->uncompress($sourcefile, $destfile);
		if(!is_file($destfile)){continue;}
		$q->QUERY_SQL(@file_get_contents($destfile),"artica_events");
		if(!$q->ok){
			meta_events("$uuid:$q->mysql_error");
			@unlink($destfile);
			continue;
		}
		@unlink($sourcefile);
		@unlink($destfile);
	}
	
}

function articadaemons_client($uuid){
	$unix=new unix();
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/ARTICA_DAEMONS";
	if(!is_dir($workingDir)){meta_events("$uuid: $workingDir no such directory");return;}	

	$sql="CREATE TABLE IF NOT EXISTS `localconfig` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`filekey` VARCHAR(90) NOT NULL,
			`fileData` TEXT NOT NULL,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `filekey` ( `filekey` ),
			 KEY `uuid` ( `uuid` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_events("$uuid: $workingDir $q->mysql_error");
		return;
	}
	
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}	
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		if(!$unix->uncompress("$workingfile", "$workingfile.array")){
			@unlink($workingfile);
			continue;
		}
		@unlink($workingfile);
		
		$MAIN_ARRAY=unserialize(@file_get_contents("$workingfile.array"));
		if(!is_array($MAIN_ARRAY)){
			meta_events("$uuid: $workingfile.array is not an array");
			@unlink("$workingfile.array");
			return;
		}
		@unlink("$workingfile.array");
		
		$prefix="INSERT IGNORE INTO `localconfig` (`zmd5`,`uuid`,`filekey`,`fileData`) VALUES ";
		$f=array();
		$q->QUERY_SQL("DELETE FROM `localconfig` WHERE `uuid`='$uuid'");
		while (list ($key, $data) = each ($MAIN_ARRAY)){
			$md5=md5("$uuid$key");
			$data=mysql_escape_string2($data);
			$f[]="('$md5','$uuid','$key','$data')";
			
		}
		
		if(count($f)==0){continue;}
		if(!$q->QUERY_SQL($prefix.@implode(",", $f))){
			meta_events("$uuid: $q->mysql_error");
			continue;
		}
		meta_events("$uuid: Injecting ".count($f)." items");
		$f=array();
		
		
	}	
	
	
}

function articasquid_quota_size_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_QUOTASIZE/SQUID_QUOTASIZE.gz";
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SQUID_QUOTASIZE";
	if(!is_dir($workingDir)){
		meta_events("$workingDir no such directroy");
		return;
	}	
	
	$TEMP_DIR=$unix->TEMP_DIR();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		
		meta_events("Analyze $workingfile (".(@filesize($workingfile)/1024) .")");
		$UnCompressed_path="$TEMP_DIR/squidqsize.$file.$uuid.db";
	
		if(!$unix->uncompress($workingfile,$UnCompressed_path )){
			meta_admin_mysql(1, "Unable to uncompress SQUID_QUOTASIZE.gz", null,__FILE__,__LINE__);
			return;
		}
		@unlink($workingfile);
		meta_events("-> exec.squid.interface-size.php --meta \"$UnCompressed_path\" $uuid");
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.interface-size.php --meta \"$UnCompressed_path\" $uuid >/dev/null 2>&1 &");
	
	}
	
	bandwidth_squid_calc($uuid);
	
}

function bandwidth_squid_calc($uuid){
	$q=new mysql_meta();
	if(!$q->TABLE_EXISTS("{$uuid}_WEEK_RTTH")){return;}
	$sql="SELECT SUM(`size`) as tcount FROM `{$uuid}_WEEK_RTTH`";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$size=$ligne["tcount"];
	$q->QUERY_SQL("UPDATE metahosts SET BANDWIDTH='$size' WHERE `uuid`='$uuid'");
	
}


function snapshot_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT/snapshot.tar.gz";
	$workingDir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/SNAPSHOT";
	if(!is_dir($workingDir)){meta_events("$uuid: $workingDir no such directory");return;}
	
	
	$sql="CREATE TABLE IF NOT EXISTS `snapshots` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`size` INT UNSIGNED NOT NULL,
			`zDate` DATETIME NOT NULL,
			`content` longblob NOT NULL,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `zDate` ( `zDate` ),
			 KEY `uuid` ( `uuid` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		meta_events("$uuid: $workingfile $q->mysql_error");
		return;
	}
	
	if (!$handle = opendir($workingDir)){
		meta_events("$uuid: $workingDir failed to parse");
		return;
	}
	
	while (false !== ($file = readdir($handle))) {
		if ($file == "." ){continue;}
		if ($file == ".." ){continue;}
		$workingfile="$workingDir/$file";
		if(is_dir($workingfile)){continue;}
		$date=date("Y-m-d H:i:s",filemtime($workingfile));
		$filemd5=md5_file($workingfile);
		$size=@filesize($workingfile);
		meta_events("$uuid: Injecting $file ($size bytes)");
		$content=mysql_escape_string2(@file_get_contents($workingfile));
		$prefix="INSERT INTO `snapshots` (zmd5,uuid,zDate,content,`size`) VALUES ('$filemd5','$uuid','$date','$content','$size')";
		if(!$q->QUERY_SQL($prefix)){
			meta_events("$uuid: $workingfile $q->mysql_error");
			continue;
		}
	
	
		@unlink($workingfile);
	
	}
	
}


function philesight_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/philesight.tgz";
	if(!is_file($workingfile)){
		meta_events("$uuid: $workingfile no such file");
		return;}
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	
	$sql="CREATE TABLE IF NOT EXISTS `philesight` (
			`zmd5` VARCHAR( 90 ) NOT NULL,
			`uuid` VARCHAR( 90 ) NOT NULL,
			`directory` VARCHAR( 255 ) NOT NULL,
			`partition` VARCHAR( 128 ) NOT NULL,
			`image` longblob NOT NULL,
			`hd` VARCHAR( 60 ) NOT NULL,
			`lastscan` INT(10) NOT NULL DEFAULT 0,
			`USED` FLOAT NOT NULL DEFAULT 0,
			`FREEMB` INT UNSIGNED NOT NULL DEFAULT 0,
			 PRIMARY KEY ( `zmd5` ),
			 KEY `lastscan` ( `lastscan` ),
			 KEY `partition` ( `partition` ),
			 KEY `uuid` ( `uuid` ),
			 KEY `directory` ( `directory` ),
			 KEY `hd` ( `hd` )
			) ENGINE=MYISAM;";
	$q=new mysql_meta();
	$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	
	$temppath=$unix->TEMP_DIR()."/$uuid-philesight";
	@mkdir($temppath,0755,true);
	shell_exec("$tar xf $workingfile -C $temppath/");
	$prefix="INSERT INTO `philesight` (zmd5,uuid,directory,partition,image,lastscan,USED,FREEMB,hd) VALUES ";
	
	if(!is_file("$temppath/dump.db")){
		meta_admin_mysql(1, "$uuid: philesight $temppath/dump.db no such file", $workingfile,__FILE__,__LINE__);
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		return;
		
	}
	$data=unserialize(@file_get_contents("$temppath/dump.db"));
	
	if(!is_array($data)){
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		meta_admin_mysql(1, "$uuid: philesight not an array", $workingfile,__FILE__,__LINE__);
		return;
	}
	
	if(count($data)==0){
		@unlink($workingfile);
		shell_exec("$rm -rf $temppath");
		meta_admin_mysql(1, "$uuid: philesight empty array", $workingfile,__FILE__,__LINE__);
		return;
	}
	
	
	$TR=array();
	while (list ($directory, $ARRAY) = each ($data) ){
		$md5=md5($uuid.$directory);
		$md5_image=$ARRAY["MD5"];
		$directory=mysql_escape_string2($directory);
		$partition=$ARRAY["PARTITION"];
		$image=@file_get_contents("$temppath/$md5_image.png");
		$lastscan=$ARRAY["lastscan"];
		$image=mysql_escape_string2($image);
		$USED=$ARRAY["USED"];
		$hd=$ARRAY["HD"];
		$FREEMB=$ARRAY["FREEMB"];
		$TR[]="('$md5','$uuid','$directory','$partition','$image','$lastscan','$USED','$FREEMB','$hd')";
		
		
	}
	
	if(count($TR)>0){
		if($GLOBALS["VERBOSE"]){echo "Adding ".count($TR)." rows\n";}
		$q->QUERY_SQL("DELETE FROM `philesight` WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $TR));
		meta_events("$uuid: INSERTING ".count($TR)." elements...");
	}
	
	@unlink($workingfile);
	shell_exec("$rm -rf $temppath");
	
	
}

function psaux_client($uuid){
	$unix=new unix();
	$workingfile="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid/PSAUX.gz";
	if(!is_file($workingfile)){return;}
	$tmpfile=$unix->FILE_TEMP();
	$unix->uncompress($workingfile, $tmpfile);
	$data=explode("\n",@file_get_contents($tmpfile));
	$q=new mysql_meta();
	
	
	
	$sql="CREATE TABLE IF NOT EXISTS `psaux` (
					uuid VARCHAR( 90 ) NOT NULL,
					user VARCHAR( 40 ) NOT NULL,
					pid INT( 10 ) NOT NULL,
					CPU FLOAT NOT NULL,
					MEM FLOAT NOT NULL,
					VSZ INT(100) NOT NULL,
					RSS INT(100) NOT NULL,
					pTIME VARCHAR( 40 ) NOT NULL,
					pcmd VARCHAR( 255 ) NOT NULL,
					KEY `user` (`user`),
					KEY `CPU` (`CPU`),
					KEY `uuid` (`uuid`),
					KEY `MEM` (`MEM`),
					KEY `VSZ` (`VSZ`),
					KEY `RSS` (`RSS`)
					) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql);
	
	$prefix="INSERT INTO `psaux` (uuid,user,pid,CPU,MEM,VSZ,RSS,pTIME,pcmd) VALUES ";
	$TR=array();
	while (list ($index, $line) = each ($data) ){
		if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+(.+?)\s+([0-9\:]+)\s+(.*)#", $line,$re)){continue;}
		$re[11]=mysql_escape_string2($re[11]);
		$TR[]="('$uuid','{$re[1]}','{$re[2]}','{$re[3]}','{$re[4]}','{$re[5]}','{$re[6]}','{$re[10]}','{$re[11]}')";
		
		
	}
	
	if(count($TR)>0){
		if($GLOBALS["VERBOSE"]){echo "Adding ".count($TR)." rows\n";}
		$q->QUERY_SQL("DELETE FROM `psaux` WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $TR));
	}
	
	@unlink($workingfile);
	
}

function clean_tables(){
	
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$TImExec=$unix->file_time_min($pidTime);
	if($TImExec<1440){return;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$array["squid_admin_mysql"]=true;
	$sock=new sockets();
	$settings=unserialize(base64_decode($sock->GET_INFO("FcronSchedulesParams")));
	if(!is_numeric($settings["max_events"])){$settings["max_events"]="10000";}
	$q=new mysql_meta();
	if($GLOBALS["VERBOSE"]){echo "Checking $c tables\n";}
	$c=0;
	$max=count($array);
	while (list ($table, $lib) = each ($array) ){
		$c++;
	
	
		if(!$q->TABLE_EXISTS($table, "articameta")){
			if($GLOBALS["VERBOSE"]){echo "$table: No such table\n";}
			continue;
		}
		$FileData="/var/lib/mysql/articameta/$table.MYD";
		$NumRows=$q->COUNT_ROWS($table, "articameta");
		$size=@filesize($FileData);
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		if($GLOBALS["VERBOSE"]){echo "$table:[$c/$max] $NumRows rows, {$size}MB Max rows:{$settings["max_events"]}\n";}
	
		if($size>500){
			if($GLOBALS["VERBOSE"]){echo "$table is more than 500MB > purge it\n";}
			$q->QUERY_SQL("TRUNCATE TABLE `$table` ","articameta");
			continue;
		}
	
		if($NumRows>$settings["max_events"]){
			$toDelete=$NumRows-$settings["max_events"];
			if($GLOBALS["VERBOSE"]){echo "$table DELETING $toDelete rows\n";}
			$q->QUERY_SQL("DELETE FROM `$table` ORDER BY zDate LIMIT $toDelete","articameta");
			continue;
		}
		$q->QUERY_SQL("DELETE FROM `$table` WHERE zDate<DATE_SUB(NOW(),INTERVAL 60 DAY)","articameta");
	}
	
}


function extract_tgz($uuid){
	$unix=new unix();
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	if(is_file("$workingdir/status.tgz")){
		
		$tar=$unix->find_program("tar");
		shell_exec("$tar -xf $workingdir/status.tgz -C $workingdir/");
		@unlink("$workingdir/status.tgz");
	}
	$unix->chown_func($unix->APACHE_SRC_ACCOUNT(),$unix->APACHE_SRC_GROUP(),"$workingdir/*");
	
	if(is_file("$workingdir/squid_admin_mysql.db")){
		$q=new mysql_meta();
		if(!$q->TABLE_EXISTS("squid_admin_mysql")){ $q->CheckTables(); }
		$data=trim(@file_get_contents("$workingdir/squid_admin_mysql.db"));
		if($data<>null){
			$q->QUERY_SQL(@file_get_contents("$workingdir/squid_admin_mysql.db"));
			if(!$q->ok){
				meta_admin_mysql(0, "Failed to import $workingdir/squid_admin_mysql.db", $q->mysql_error,__FILE__,__LINE__);
			}else{
				@unlink("$workingdir/squid_admin_mysql.db");
			}
		}else{
			@unlink("$workingdir/squid_admin_mysql.db");
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo "$workingdir/squid_admin_mysql.db ( no such file )\n";}
	}
	
	
	if(is_file("$workingdir/network_hosts.db")){
		__network_hosts($uuid,"$workingdir/network_hosts.db");
	}else{
		if($GLOBALS["VERBOSE"]){echo "$workingdir/network_hosts.db ( no such file )\n";}
	}
	
	global_status_ini($uuid);
	
}

function __network_hosts($uuid,$filepath){
	
	$ARRAY=unserialize(@file_get_contents($filepath));
	$f=array();
	$prefix="INSERT IGNORE INTO networks_hosts (MAC,IPADDR,IPINT,uuid,hostname,username,OSNAME) VALUES ";
	if(count($ARRAY)==0){
		@unlink($filepath);
		return;
	}
	while (list ($MAC, $subarray) = each ($ARRAY) ){
		
		while (list ($a, $b) = each ($subarray) ){
			$subarray[$a]=mysql_escape_string2($b);
		}

		
		$f[]="('$MAC','{$subarray["IPADDR"]}','{$subarray["IPINT"]}','{$uuid}','{$subarray["hostname"]}',
		'{$subarray["username"]}','{$subarray["OSNAME"]}')";
	}
	
	if(count($f)==0){@unlink($filepath);return;}
	
	$q=new mysql_meta();
	$q->CheckTables();
	if(!$q->FIELD_EXISTS("networks_hosts", "OSNAME")){
		$q->QUERY_SQL("ALTER TABLE `networks_hosts` ADD `OSNAME` varchar(255)");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
	$q->QUERY_SQL($prefix.@implode(",", $f));
	if($q->ok){@unlink($filepath);return;}
		
	
	
}

function global_status_ini($uuid){
	$workingdir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	if(!is_file("$workingdir/global.status.ini")){
		if($GLOBALS["VERBOSE"]){echo "$workingdir/global.status.ini ( no such file )\n";}
		return;}
	$ini=new Bs_IniHandler("$workingdir/global.status.ini");
	
	$f=array();
	$prefix="INSERT IGNORE INTO global_status (
	`uuid`,`MAIN`,`service_name`,`service_cmd`,`service_disabled`,`watchdog_features`,`binpath`,
	`explain`,`running`,`installed`,`master_pid`,`master_memory`,`master_cached_memory`,`processes_number`,`uptime`,`master_version` ) VALUES ";
	
	while (list ($MAIN, $subarray) = each ($ini->_params) ){
		
		if(!isset($subarray["installed"])){
			if($subarray["master_memory"]<>null){
				$subarray["installed"]=1;
			}else{
				$subarray["installed"]=0;
			}
		}
		
		$f[]="('$uuid','$MAIN','{$subarray["service_name"]}',
		'{$subarray["service_cmd"]}',
		'{$subarray["service_disabled"]}',
		'{$subarray["watchdog_features"]}',
		'{$subarray["binpath"]}',
		'{$subarray["explain"]}',
		'{$subarray["running"]}',
		'{$subarray["installed"]}',
		'{$subarray["master_pid"]}',
		'{$subarray["master_memory"]}',
		'{$subarray["master_cached_memory"]}',
		'{$subarray["processes_number"]}',
		'{$subarray["uptime"]}',
		'{$subarray["master_version"]}'
		
		)";
		
	}
	
	
	if(count($f)>0){
		$q=new mysql_meta();
		$q->CheckTables();
		$q->QUERY_SQL("DELETE FROM global_status WHERE uuid='$uuid'");
		$q->QUERY_SQL($prefix.@implode(",", $f));
		if(!$q->ok){return false;}
		@unlink("$workingdir/global.status.ini");
	}
}

function add_node_progress($text,$prc){
	$file="/usr/share/artica-postfix/ressources/logs/web/artica-meta.NewServ.php.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);
	sleep(1);
	

}
function repair_tables_progress($text,$prc){
	echo "{$prc}% $text\n";
	$file="/usr/share/artica-postfix/ressources/logs/web/artica-meta.RepairTables.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);


}
function add_node(){
	$sock=new sockets();
	
	$ArticaMetaAddNewServ=unserialize($sock->GET_INFO("ArticaMetaAddNewServ"));
	$ArticaMetaHost=$ArticaMetaAddNewServ["ArticaMetaHost"];
	$ArticaMetaPort=$ArticaMetaAddNewServ["ArticaMetaPort"];
	$ArticaMetaUsername=$ArticaMetaAddNewServ["ArticaMetaUsername"];
	$ArticaMetaPassword=$ArticaMetaAddNewServ["ArticaMetaPassword"];
	$change_uuid=$ArticaMetaAddNewServ["change_uuid"];
	
	echo "ArticaMetaHost.........: $ArticaMetaHost\n";
	echo "ArticaMetaPort.........: $ArticaMetaPort\n";
	echo "ArticaMetaUsername.....: $ArticaMetaUsername\n";
	
	echo "Testing authentication...\n";
	add_node_progress("Authenticate to $ArticaMetaHost:$ArticaMetaPort",10);
	
	$array["username"]=$ArticaMetaUsername;
	$array["password"]=$ArticaMetaPassword;
	$ident=urlencode(base64_encode(serialize($array)));
	
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?test-local-ident=$ident");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}
	
	
	if(!preg_match("#<ARTICA_META>(.+?)</ARTICA_META>#is", $curl->data,$re)){
		echo "Expected <ARTICA_META>Someting...</ARTICA_META>";
		add_node_progress("Communication: {failed}",110);
		die();
	}
	
	if($re[1]<>"SUCCESS"){
		add_node_progress("Authenticate: {failed}",110);
		die();
	}
	
	echo "Testing authentication - success -...\n";
	
	if($change_uuid==1){
		echo "Ask to remote server to change UUID...\n";
		add_node_progress("{change_uuid}",20);
		
		$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&chuuid=yes");
		$curl->NoHTTP_POST=true;
		$curl->NoLocalProxy();
		$curl->Timeout=120;
		if(!$curl->get()){
			echo @implode("\n", $curl->errors);
			add_node_progress("$curl->error",110);
			die();
		}
		
		
		if(!preg_match("#<ARTICA_META>(.+?):(.+?)</ARTICA_META>#is", $curl->data,$re)){
			add_node_progress("{change_uuid}: Communication: {failed}",110);
			die();
		}
		add_node_progress("{change_uuid}:{$re[2]} {success}",25);
		echo "Ask to remote server to change UUID - success -...\n";
		
	}
	
	echo "Ask UUID to remote server...\n";
	add_node_progress("{uuid}:",30);
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&GetYourUUID=yes");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}	
	
	if(!preg_match("#<ARTICA_META>(.+?):(.+?)</ARTICA_META>#is", $curl->data,$re)){
		add_node_progress("{uuid}: Communication: {failed}",110);
		die();
	}
	$RESULT=$re[1];
	$uuid=$re[2];
	echo "UUID results:\n---------------------------------------------\n$uuid\n$RESULT\n---------------------------------------------\n";
	if($RESULT=="SUCCESS"){
		add_node_progress("{uuid}: $uuid {success}",35);
	}else{
		add_node_progress("{uuid}: {failed}",110);
		return;
	}
	
	echo "Ask to remote server to register to Artica Meta server ...\n";
	add_node_progress("{order}: -> {register}",50);
	
	$ArticaMetaServerUsername=$sock->GET_INFO("ArticaMetaServerUsername");
	$ArticaMetaServerPassword=$sock->GET_INFO("ArticaMetaServerPassword");
	$ArticaMetaAddNewServ["ArticaMetaUsername"]=$ArticaMetaServerUsername;
	$ArticaMetaAddNewServ["ArticaMetaPassword"]=$ArticaMetaServerPassword;
	
	if($GLOBALS["VERBOSE"]){$verbosed="&verbose=yes";}
	$ArticaMetaAddNewServ_enc=urlencode(base64_encode(serialize($ArticaMetaAddNewServ)));
	$curl=new ccurl("https://$ArticaMetaHost:$ArticaMetaPort/artica.meta.listener.php?local-ident=$ident&registerby=$ArticaMetaAddNewServ_enc$verbosed");
	$curl->NoHTTP_POST=true;
	$curl->NoLocalProxy();
	$curl->Timeout=120;
	
	if(!$curl->get()){
		echo @implode("\n", $curl->errors);
		add_node_progress("$curl->error",110);
		die();
	}
	
	if($GLOBALS["VERBOSE"]){echo "***********************************\n$curl->data\n***********************************\n";}
	
	if(!preg_match("#<ARTICA_META>(.+?):(.+?)</ARTICA_META>#is", $curl->data,$re)){
		add_node_progress("{register}: Communication: {failed}",110);
		die();
	}
	
	$RESULT=$re[1];
	$DATA=$re[2];
	echo $DATA;
	
	echo "Register results:\n---------------------------------------------\n$DATA\n$RESULT\n---------------------------------------------\n";
	
	if($RESULT=="SUCCESS"){
		add_node_progress("{register}: {success}",99);
	}else{
		add_node_progress("{register}: {failed}",110);
		return;
	}
	
	$artica_meta=new mysql_meta();
	add_node_progress("{waiting}: $ArticaMetaHost {to_return_back}",79);
	for($i=0;$i<20;$i++){
		if($artica_meta->isExists($uuid)){break;}
		echo "Waiting $ArticaMetaHost $uuid to register to Artica Meta...$i/20 second\n";
		$prc=79+$i;
		add_node_progress("{waiting}: $ArticaMetaHost {to_return_back}",$prc);
		sleep(1);
		
		
	}
	
	
	if($artica_meta->isExists($uuid)){
		$hostname=$artica_meta->uuid_to_host($uuid);
		add_node_progress("{register}: {success} `$hostname`",100);
		return;
	}
	
	add_node_progress("{register}: {failed}",110);
}

function repair_tables(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Alreay executed\n";
		repair_tables_progress("{done}",110);
		die();}
	
	
	$files=$unix->DirFiles("/var/lib/mysql/articameta","\.MYI");
	
	$myisamchk=$unix->find_program("myisamchk");
	
	$count=count($files);
	$c=0;
	while (list($filename,$notused)=each($files)){
		$filepath="/var/lib/mysql/articameta/$filename";
		$c++;
		$prc=round($c/$count,2);
		$prc=$prc*100;
		if($prc>90){$prc=90;}
		$MYD=str_replace("MYI", "MYD", $filename);
		$FRM=str_replace("MYI", "frm", $filename);
		$size=@filesize($filepath)+@filesize("/var/lib/mysql/articameta/$MYD")+@filesize("/var/lib/mysql/articameta/$FRM");
		
		$size=FormatBytes($size/1024,true);
		echo "Repair $filename $c/$count/$prc\n";
		
		repair_tables_progress("{repair} $filename ($size)",$prc);
		sleep(1);
		system("$myisamchk --safe-recover --backup $filepath");
		
	}
	
	$files=$unix->DirFiles("/var/lib/mysql/articameta","\.BAK");
	while (list($filename,$notused)=each($files)){
		$filepath="/var/lib/mysql/articameta/$filename";
		echo "Remove $filename\n";
		$size=@filesize($filepath);
		$size=FormatBytes($size/1024,true);
		repair_tables_progress("{remove} $filename ($size)",95);
		@unlink($filename);
		usleep(500);
	}
	
	repair_tables_progress("{done} $filename",100);
	
}

function send_notifications($subject,$content){
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
	$unix=new unix();
	$sock=new sockets();
	
	$ArticaMetaSMTPNotifs=unserialize(base64_decode($sock->GET_INFO("ArticaMetaSMTPNotifs")));
	include_once(dirname(__FILE__) . '/ressources/class.mail.inc');
	include_once(dirname(__FILE__)."/ressources/smtp/class.phpmailer.inc");
	$users=new usersMenus();
	$smtp_dest=$ArticaMetaSMTPNotifs["smtp_dest"];
	$smtp_sender=$ArticaMetaSMTPNotifs["smtp_sender"];
	if($smtp_dest==null){return;}
	if($smtp_sender==null){
		$users=new usersMenus();
		$smtp_sender="artica-meta@$users->hostname";
	}

	if(!isset($ArticaMetaSMTPNotifs["ssl_enabled"])){$ArticaMetaSMTPNotifs["ssl_enabled"]=0;}
	
	$text=str_replace("\n", "\r\n", $content);
	$mail = new PHPMailer(true);
	$mail->IsSMTP();
	$mail->AddAddress($smtp_dest,$smtp_dest);
	$mail->AddReplyTo($smtp_sender,$smtp_sender);
	$mail->From=$smtp_sender;
	$mail->Subject=$subject;
	$mail->Body=$text;
	$mail->Host=$ArticaMetaSMTPNotifs["smtp_server_name"];
	$mail->Port=$ArticaMetaSMTPNotifs["smtp_server_port"];

	if(($ArticaMetaSMTPNotifs["smtp_auth_user"]<>null) && ($ArticaMetaSMTPNotifs["smtp_auth_passwd"]<>null)){
		$mail->SMTPAuth=true;
		$mail->Username=$ArticaMetaSMTPNotifs["smtp_auth_user"];
		$mail->Password=$ArticaMetaSMTPNotifs["smtp_auth_passwd"];
		if($ArticaMetaSMTPNotifs["tls_enabled"]==1){$mail->SMTPSecure = 'tls';}
		if($ArticaMetaSMTPNotifs["ssl_enabled"]==1){$mail->SMTPSecure = 'ssl';}


	}


	
	if($mail->Send()){
		if($GLOBALS["VERBOSE"]){
			echo " ************ SUCCESS *************\n";
		}
		$unix->events("SMTP SEND From <$smtp_sender> to <$smtp_dest> $subject",
				"/var/log/meta.smtp.log",false,__FUNCTION__,__LINE__,__FILE__);
		return true;
	}else{
		$unix->events("SMTP FAILED From <$smtp_sender> to <$smtp_dest> $subject",
				"/var/log/meta.smtp.log",false,__FUNCTION__,__LINE__,__FILE__);
		if($GLOBALS["VERBOSE"]){echo " ************ !!! FAILED !!! *************\n";}
	}
	return false;

}


