<?php
$GLOBALS["FORCE"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: Overloaded system,die()","MAIN",__FILE__,__LINE__);die();}
if($argv[1]=="--install"){install_apps();exit;}
if($argv[1]=="--install-status"){install_status();exit;}


ParseProducts();


function install_apps(){
	if(system_is_overloaded(basename(__FILE__))){writelogs("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__);die();}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	$timefile=$unix->file_time_min($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		writelogs(basename(__FILE__).": Already executed pid $oldpid since $timefile minutes.. aborting the process","MAIN",__FILE__,__LINE__);
		die();
	}

	if($timefile<1){if($GLOBALS["VERBOSE"]){echo "At least 1mn...aborting";die();}}

	
	$q=new mysql();
	$sql="SELECT CODE_NAME FROM setup_center WHERE `upgrade`=1 AND `progress`<10";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);}
	if(mysql_num_rows($results)>0){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$CODE_NAME=$ligne["CODE_NAME"];
			$q->QUERY_SQL("UPDATE setup_center SET `progress`=10 WHERE `CODE_NAME`='{$ligne["CODE_NAME"]}'","artica_backup");
			install_single_app($CODE_NAME);
			if(system_is_overloaded(basename(__FILE__))){writelogs("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__);die();}
		}
	}
	
	$sql="SELECT CODE_NAME FROM setup_center WHERE `upgrade`=1 AND progress<10";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)==0){
		@unlink("/etc/cron.d/apps-upgrade");
		$q->QUERY_SQL("UPDATE setup_center SET `upgrade`=0 WHERE `upgrade`=1","artica_backup");
		ParseProducts();
	}

	install_status();
	
}



function install_single_app($CODE_NAME){
	$app=$CODE_NAME;
	writelogs("$app to install",__FUNCTION__,__FILE__,__LINE__);
	if(trim($app)==null){return;}
	$unix=new unix();
	$su=$unix->find_program("su");
	$cmd="/usr/share/artica-postfix/bin/artica-install --install-status $app";
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	while (list ($num, $ligne) = each ($results) ){
		writelogs("$ligne",__FUNCTION__,__FILE__,__LINE__);
	}
	$tmpfile="/usr/share/artica-postfix/ressources/install/$app.dbg";
	
	@file_put_contents("Scheduled","/usr/share/artica-postfix/ressources/install/$app.dbg");
	shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/install/$app.dbg");
	
	writelogs("Schedule /usr/share/artica-postfix/bin/artica-make $app >$tmpfile 2>&1",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$su -c \"/usr/share/artica-postfix/bin/artica-make $app >$tmpfile\" -l root 2>&1 &");	
	
}

function install_status(){
	if(system_is_overloaded(basename(__FILE__))){system_admin_events("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__,"setup");die();}
	$q=new mysql();
	
	$c=0;
	
	foreach (glob("/usr/share/artica-postfix/ressources/install/*.ini") as $filename) {
		$base=basename($filename);
		$CODE_NAME=str_replace(".ini", "", $base);
		if($CODE_NAME==null){@unlink($filename);continue;}
		$ini=new Bs_IniHandler();
		$data=@file_get_contents($filename);
		$ini->loadString($data);
		$progress=$ini->_params["INSTALL"]["STATUS"];	
		$progress_text=addslashes($ini->_params["INSTALL"]["INFO"]);
		
		$debug=addslashes(@file_get_contents("/usr/share/artica-postfix/ressources/install/$CODE_NAME.dbg"));
		$tt[]="$CODE_NAME {$progress}% `$progress_text` ".strlen($debug)." bytes events";
		$sql="UPDATE setup_center SET `progress`=$progress,`upgrade`=1,`progress_text`='$progress_text',`events`='$debug' WHERE `CODE_NAME`='$CODE_NAME'";
		$c++;
		if($progress>99){
			$sql="UPDATE setup_center SET `progress`=$progress,`upgrade`=0,`progress_text`='$progress_text',`events`='$debug' WHERE `CODE_NAME`='$CODE_NAME'";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){system_admin_events("Failed for progress of $CODE_NAME $q->mysql_error\nfile: /usr/share/artica-postfix/ressources/install/$CODE_NAME.dbg",__FUNCTION__,__FILE__,__LINE__,"setup");continue;}
			@unlink($filename);
			@unlink("/usr/share/artica-postfix/ressources/install/$CODE_NAME.dbg");
			continue;
		}
		$q->QUERY_SQL($sql,"artica_backup");
		if(system_is_overloaded(basename(__FILE__))){writelogs("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__);die();}
		
		
	}
	if(count($tt)){
		system_admin_events("Parsing installation status done: ". count($tt)." installation status..\n".@implode($tt, "\n"),__FUNCTION__,__FILE__,__LINE__,"setup");
	}
	
	if($c==0){
		foreach (glob("/usr/share/artica-postfix/ressources/install/*.dbg") as $filename) {
			$base=basename($filename);
			$CODE_NAME=str_replace(".dbg", "", $base);
			if($CODE_NAME==null){@unlink($filename);continue;}
			$debug=addslashes(@file_get_contents($filename));
			$ff[]="$CODE_NAME events ".strlen($debug)." bytes events";
			$sql="UPDATE setup_center SET `events`='$debug' WHERE `CODE_NAME`='$CODE_NAME'";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){system_admin_events("Failed for status of $CODE_NAME $q->mysql_error\nfile:$filename\n",__FUNCTION__,__FILE__,__LINE__,"setup");continue;}
			@unlink($filename);
			if(system_is_overloaded(basename(__FILE__))){writelogs("Overloaded system, aborting...",__FUNCTION__,__FILE__,__LINE__);die();}
		}
		
	}
	
	if(count($tt)){
		system_admin_events("Parsing installation status ". count($tt)." events files...: \n".@implode($tt, "\n"),__FUNCTION__,__FILE__,__LINE__,"setup");
	}
	
	
}


function ParseProducts(){
	$unix=new unix();
	if(!$GLOBALS["FORCE"]){
		$TimePid="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".time";
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$timefile=$unix->file_time_min($pidfile);
			system_admin_events(basename(__FILE__).": Already executed pid $oldpid since $timefile minutes.. aborting the process","MAIN",__FILE__,__LINE__,"setup");
			die();
		}
		
		$timefile=$unix->file_time_min($TimePid);
		if($timefile<30){return;}
	}
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	if($GLOBALS["FORCE"]){
		echo "Scanning local softwares....\n";
		shell_exec("/usr/share/artica-postfix/bin/artica-install --write-versions >/dev/null 2>&1");
	}

	$q=new mysql();
	if(!$q->TABLE_EXISTS("setup_center", "artica_backup")){$q->BuildTables();}
	if(!$q->TABLE_EXISTS("setup_center", "artica_backup",true)){echo "setup_center no such table\n";return;}
	
	$index_ini=dirname(__FILE__). '/ressources/index.ini';
	if(!is_file($index_ini)){shell_exec("/usr/share/artica-postfix/bin/artica-update --index");}
	if(!is_file($index_ini)){echo "$index_ini no such file\n";return;}
	
	$Softs=GetProductsArray();
	BuildVersions();
	if(!isset($GLOBALS["INDEXFF"])){$GLOBALS["INDEXFF"]=null;}
	if($GLOBALS["INDEXFF"]==null){$GLOBALS["INDEXFF"]=@file_get_contents($index_ini);}
	$ini=new Bs_IniHandler();
	if($GLOBALS["VERBOSE"]){echo "INDEFF =  ".strlen($GLOBALS["INDEXFF"])." bytes..\n";}
	$ini->loadString($GLOBALS["INDEXFF"]);	
	if(count($ini->_params["NEXT"])<5){echo "$index_ini corrupted\n";return;}
	if($GLOBALS["GLOBAL_VERSIONS_CONF"]<3){echo "ressources/logs/global.versions.conf corrupted\n";return;}
	$t=time();
	while (list ($num, $ProductsArray) = each ($Softs) ){
			$CODE_NAME=$ProductsArray["PRODUCT_CODE"];
			$CurrentVersion=ParseAppli($CODE_NAME);
			$NextVersion=$ini->_params["NEXT"][$ProductsArray["REPO_CODE"]];
			if(!isset($ProductsArray["ABOUT"])){$ProductsArray["ABOUT"]=null;}
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `CODE_NAME` FROM setup_center WHERE `CODE_NAME`='$CODE_NAME'","artica_backup"));
		
			$ttA[]="$CODE_NAME: Current version: [$CurrentVersion], available: [$NextVersion]";	
			$sql="UPDATE setup_center SET `curversionstring`='$CurrentVersion',`nextversionstring`='$NextVersion' ,`CODE_NAME_ABOUT`='{$ProductsArray["ABOUT"]}' WHERE `CODE_NAME`='$CODE_NAME'";
			
			if(trim($ligne["CODE_NAME"])==null){
				$sql="INSERT INTO setup_center (`CODE_NAME`,`curversionstring`,`nextversionstring`,`REPO_CODE`,`FAMILY`,`SUBFAMILY`,`CODE_NAME_ABOUT`) 
				VALUES ('$CODE_NAME','$CurrentVersion','$NextVersion','{$ProductsArray["REPO_CODE"]}','{$ProductsArray["FAMILY"]}','{$ProductsArray["SUBFAMILY"]}','{$ProductsArray["ABOUT"]}')";
			}
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){
				if(preg_match("#Error Unknown column#",$q->mysql_error)){$q->BuildTables();}
			}
			
			echo "$CODE_NAME: $CurrentVersion <> $NextVersion \n";
	}
	
	$ttA_md5=md5(serialize($ttA));
	$sock=new sockets();
	$ttACache=$sock->GET_INFO("SoftwaresListCached");
	
	if($ttA_md5<>$ttACache){
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		system_admin_events("INFO, ". count($ttA) ." softwares parsed, took: $took\n".@implode($ttA, "\n"),__FUNCTION__,__FILE__,__LINE__,"setup");
		$sock->SET_INFO("SoftwaresListCached",$ttA_md5);
	}

}

function ParseAppli($key){
	if(!isset($GLOBALS["GLOBAL_VERSIONS_CONF"])){BuildVersions();}
	if(!is_array($GLOBALS["GLOBAL_VERSIONS_CONF"])){BuildVersions();}
	if(!isset($GLOBALS["GLOBAL_VERSIONS_CONF"][$key])){return null;}
	return $GLOBALS["GLOBAL_VERSIONS_CONF"][$key];	
}


function BuildVersions(){
	$sourcefile=dirname(__FILE__)."/ressources/logs/global.versions.conf";
	if($GLOBALS["VERBOSE"]){echo "BuildVersions():: \"$sourcefile\"\n";}
	if(is_file($sourcefile)){
		$GlobalApplicationsStatus=@file_get_contents($sourcefile);
	}else{
		if(is_file(dirname(__FILE__)."/ressources/logs/web/global.versions.conf")){
			$GlobalApplicationsStatus=@file_get_contents(dirname(__FILE__)."ressources/logs/web/global.versions.conf");
		}
	}
	$tb=explode("\n",$GlobalApplicationsStatus);
	while (list ($num, $line) = each ($tb) ){
		if(preg_match('#\[(.+?)\]\s+"(.+?)"#',$line,$re)){
			$GLOBALS["GLOBAL_VERSIONS_CONF"][trim($re[1])]=trim($re[2]);
		}
		
	}
if($GLOBALS["VERBOSE"]){echo "BuildVersions():: return Array of ". count($GLOBALS["GLOBAL_VERSIONS_CONF"]) ." elements in ". strlen($GlobalApplicationsStatus)." bytes\n";}
	return $GLOBALS["GLOBAL_VERSIONS_CONF"];
	
}


function GetProductsArray(){
	$users=new usersMenus();
	$products[]=array(
	"PRODUCT_CODE"=>"APP_CYRUS_IMAP",
	"REPO_CODE"=>"cyrus-imapd",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"CORE",
	);
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ROUNDCUBE3",
	"REPO_CODE"=>"roundcubemail3",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"WEBMAIL",
	);	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ZARAFA",
	"REPO_CODE"=>"zarafa",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"CORE",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ZARAFA",
	"REPO_CODE"=>"zarafa",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"CORE",
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_YAFFAS",
	"REPO_CODE"=>"yaffas",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"ADMIN",
	);		

$products[]=array(
	"PRODUCT_CODE"=>"APP_SPREED",
	"REPO_CODE"=>"spreedsrc",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"PLUGIN",
	"ABOUT"=>"SPREED_ABOUT"
	);
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ZARAFA6",
	"REPO_CODE"=>"zarafa6-i386",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"CORE",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_FETCHMAIL",
	"REPO_CODE"=>"fetchmail",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"CORE",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_IMAPSYNC",
	"REPO_CODE"=>"imapsync",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"BACKUP",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_OFFLINEIMAP",
	"REPO_CODE"=>"offlineimap",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"BACKUP",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_CLUEBRINGER",
	"REPO_CODE"=>"cluebringer",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"SECURITY",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_OPENDKIM",
	"REPO_CODE"=>"opendkim",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"SECURITY",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_MILTER_DKIM",
	"REPO_CODE"=>"dkim-milter",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"SECURITY","ABOUT"=>"{dkim_about}<br>{dkim_about2}"
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_CROSSROADS",
	"REPO_CODE"=>"crossroads",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ARKEIA",
	"REPO_CODE"=>"arkeia-debian6-i386",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SYSTEM",
	"ABOUT"=>"{APP_ARKEIA_TXT}"
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_HAPROXY",
	"REPO_CODE"=>"haproxy",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY",
	"ABOUT"=>"{APP_HAPROXY_ABOUT}"
	);		
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_SPAMASSASSIN",
	"REPO_CODE"=>"Mail-SpamAssassin",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_AMAVISD_MILTER",
	"REPO_CODE"=>"amavisd-milter",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_MIMEDEFANG",
	"REPO_CODE"=>"mimedefang",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_AMAVISD_NEW",
	"REPO_CODE"=>"amavisd-new",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ASSP",
	"REPO_CODE"=>"assp",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_CLAMAV_MILTER",
	"REPO_CODE"=>"clamav",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AV",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_POSTFIX",
	"REPO_CODE"=>"postfix",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"CORE",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_KAS3",
	"REPO_CODE"=>"kas",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_KAVMILTER",
	"REPO_CODE"=>"kavmilter",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AV",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_MILTERGREYLIST",
	"REPO_CODE"=>"milter-greylist",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"AS",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_Z_PUSH",
	"REPO_CODE"=>"z-push",
	"FAMILY"=>"MAILBOX",
	"SUBFAMILY"=>"zarafa",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_OPENEMM",
	"REPO_CODE"=>"OpenEMM",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"mailling",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_POMMO",
	"REPO_CODE"=>"pommo",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"mailling",
	);			
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_OPENEMM_SENDMAIL",
	"REPO_CODE"=>"sendmail",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"mailling",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_ALTERMIME",
	"REPO_CODE"=>"altermime",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"SECURITY",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_EMAILRELAY",
	"REPO_CODE"=>"emailrelay",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"mailling",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_STUNNEL",
	"REPO_CODE"=>"stunnel",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"SECURITY",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_MAILSPY",
	"REPO_CODE"=>"mailspy",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"BACKUP",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_PFLOGSUMM",
	"REPO_CODE"=>"pflogsumm",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"STATS",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ISOQLOG",
	"REPO_CODE"=>"isoqlog",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"STATS",
	);	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_AWSTATS",
	"REPO_CODE"=>"awstats",
	"FAMILY"=>"STATS",
	"SUBFAMILY"=>"STATS",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_COLLECTD",
	"REPO_CODE"=>"collectd",
	"FAMILY"=>"STATS",
	"SUBFAMILY"=>"STATS",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_GNUPLOT",
	"REPO_CODE"=>"gnuplot",
	"FAMILY"=>"STATS",
	"SUBFAMILY"=>"STATS",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_DSTAT",
	"REPO_CODE"=>"dstat",
	"FAMILY"=>"STATS",
	"SUBFAMILY"=>"STATS",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_VNSTAT",
	"REPO_CODE"=>"vnstat",
	"FAMILY"=>"STATS",
	"SUBFAMILY"=>"NET",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_PUREFTPD",
	"REPO_CODE"=>"pure-ftpd",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"WEB",
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_PHPMYADMIN",
	"REPO_CODE"=>"phpMyAdmin",
	"FAMILY"=>"DATABASE",
	"SUBFAMILY"=>"ADMIN",
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_PHPLDAPADMIN",
	"REPO_CODE"=>"phpldapadmin",
	"FAMILY"=>"DATABASE",
	"SUBFAMILY"=>"ADMIN",
	);	
	
	

$products[]=array(
	"PRODUCT_CODE"=>"APP_MOD_PAGESPEED",
	"REPO_CODE"=>"mod-pagespeedDEBi386",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APACHE",
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_DOTCLEAR",
	"REPO_CODE"=>"dotclear",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_LMB",
	"REPO_CODE"=>"lmb",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array("PRODUCT_CODE"=>"APP_OPENGOO",
	"REPO_CODE"=>"opengoo",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_GROUPOFFICE",
	"REPO_CODE"=>"groupoffice-com",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_DRUPAL",
	"REPO_CODE"=>"drupal",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_DRUPAL7",
	"REPO_CODE"=>"drupal7",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_DRUSH7",
		"REPO_CODE"=>"drush7",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_PIWIGO",
	"REPO_CODE"=>"piwigo",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_WORDPRESS",
	"REPO_CODE"=>"wordpress",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_CONCRETE5",
	"REPO_CODE"=>"concrete5",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_PYAUTHENNTLM",
	"REPO_CODE"=>"PyAuthenNTLM2",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
);


	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_SABNZBDPLUS",
	"REPO_CODE"=>"sabnzbd",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_PIWIK",
	"REPO_CODE"=>"piwik",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_PIWIK",
	"REPO_CODE"=>"piwik",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_ZARAFA_WEB",
	"REPO_CODE"=>"zarafa",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_Z_PUSH_WEB",
	"REPO_CODE"=>"z-push",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_ZARAFA_WEBAPP",
	"REPO_CODE"=>"zarafa-webapp",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_WEBAPP",
	"REPO_CODE"=>"webapp",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_SUGARCRM",
	"REPO_CODE"=>"SugarCE",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_JOOMLA",
	"REPO_CODE"=>"joomla",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_JOOMLA17",
	"REPO_CODE"=>"joomla17",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"APP",
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_SQUID",
	"REPO_CODE"=>"squid3",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"CORE",

);

$products[]=array(
	"PRODUCT_CODE"=>"APP_SQUID31",
	"REPO_CODE"=>"squid3",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"CORE",

);

$products[]=array(
	"PRODUCT_CODE"=>"APP_SQUID0",
	"REPO_CODE"=>"squid2",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"CORE",

);


$products[]=array(
	"PRODUCT_CODE"=>"APP_SARG",
	"REPO_CODE"=>"sarg",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"STAT"
	);	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_SAMBA",
	"REPO_CODE"=>"samba",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_SAMBA35",
	"REPO_CODE"=>"samba35",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_SAMBA36",
	"REPO_CODE"=>"samba36",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_XAPIAN",
	"REPO_CODE"=>"xapian-core",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_CUPS_DRV",
	"REPO_CODE"=>"cups-drv",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"
	);		
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_MSKTUTIL",
	"REPO_CODE"=>"msktutil",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_UFDBGUARD",
	"REPO_CODE"=>"ufdbGuard",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);
$products[]=array(
	"PRODUCT_CODE"=>"APP_SQUIDGUARD",
	"REPO_CODE"=>"squidGuard",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);


$products[]=array(
	"PRODUCT_CODE"=>"APP_SQUIDCLAMAV",
	"REPO_CODE"=>"squidclamav",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);
$products[]=array(
	"PRODUCT_CODE"=>"APP_CLAMAV",
	"REPO_CODE"=>"clamav",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);
$products[]=array(
	"PRODUCT_CODE"=>"APP_C_ICAP",
	"REPO_CODE"=>"c-icap",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);	
		
$products[]=array(
	"PRODUCT_CODE"=>"APP_KAV4PROXY",
	"REPO_CODE"=>"kav4proxy",
	"FAMILY"=>"PROXY",
	"SUBFAMILY"=>"SECURITY"	
);	



$products[]=array(
	"PRODUCT_CODE"=>"APP_GLUSTER"
	,"REPO_CODE"=>"glusterfs",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SECURITY"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_GREYHOLE"
	,"REPO_CODE"=>"greyhole",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"BACKUP"		
	);
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_CUPS_DRV"
	,"REPO_CODE"=>"cups-drv",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_CUPS_BROTHER"
	,"REPO_CODE"=>"brother-drivers",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_HPINLINUX"
	,"REPO_CODE"=>"hpinlinux",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"CORE"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_SCANNED_ONLY"
	,"REPO_CODE"=>"scannedonly",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"AV"		
	);		

$products[]=array(
	"PRODUCT_CODE"=>"APP_BACKUPPC"
	,"REPO_CODE"=>"BackupPC",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"BACKUP"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_MLDONKEY"
	,"REPO_CODE"=>"mldonkey",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"HOME"		
	);
$products[]=array(
	"PRODUCT_CODE"=>"APP_DROPBOX"
	,"REPO_CODE"=>"dropbox-32",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"BACKUP"		
	);
/*$products[]=array(
	"PRODUCT_CODE"=>"APP_NETATALK"
	,"REPO_CODE"=>"netatalk-debian6-32",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"BACKUP",
	"ABOUT"=>"ABOUT_NETATALK"		
	);	
*/


$products[]=array(
	"PRODUCT_CODE"=>"APP_VMTOOLS",
	"REPO_CODE"=>"VMwareTools",
	"FAMILY"=>"VIRTUALIZATION",
	"SUBFAMILY"=>"SYSTEM"		
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_VBOXADDITIONS",
	"REPO_CODE"=>"VBoxLinuxAdditions-$users->ArchStruct",
	"FAMILY"=>"VIRTUALIZATION",
	"SUBFAMILY"=>"SYSTEM"		
	);

			
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_LXC",
	"REPO_CODE"=>"lxc",
	"FAMILY"=>"VIRTUALIZATION",
	"SUBFAMILY"=>"CORE"		
	);
		

$products[]=array(
	"PRODUCT_CODE"=>"APP_MYSQL",
	"REPO_CODE"=>"mysql-server",
	"FAMILY"=>"DATABASE",
	"SUBFAMILY"=>"CORE"		
	);
	
	

$products[]=array(
	"PRODUCT_CODE"=>"APP_GREENSQL",
	"REPO_CODE"=>"greensql-fw",
	"FAMILY"=>"DATABASE",
	"SUBFAMILY"=>"SECURIY"		
	);
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_TOMCAT",
	"REPO_CODE"=>"apache-tomcat",
	"FAMILY"=>"WEB",
	"SUBFAMILY"=>"CORE"		
	);

$products[]=array(
	"PRODUCT_CODE"=>"APP_MSMTP",
	"REPO_CODE"=>"msmtp",
	"FAMILY"=>"SMTP",
	"SUBFAMILY"=>"CORE"
	);	
	
		

$products[]=array(
	"PRODUCT_CODE"=>"APP_DHCP",
	"REPO_CODE"=>"dhcp",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_PDNS",
	"REPO_CODE"=>"pdns",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
		"PRODUCT_CODE"=>"APP_PDNS_STATIC",
		"REPO_CODE"=>"pdns",
		"FAMILY"=>"NETWORK",
		"SUBFAMILY"=>"CORE"
);	


	
	$products[]=array(
	"PRODUCT_CODE"=>"APP_POWERADMIN",
	"REPO_CODE"=>"poweradmin",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_OPENVPN",
	"REPO_CODE"=>"openvpn",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY"
	);
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_HAMACHI",
	"REPO_CODE"=>"logmein-hamachi-i386",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY",
	"ABOUT"=>"APP_HAMACHI_ABOUT"
	);	

	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_IPTACCOUNT",
	"REPO_CODE"=>"iptaccount",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"STAT"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_AMANDA",
	"REPO_CODE"=>"amanda",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_DROPBOX",
	"REPO_CODE"=>"dropbox-32",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_FUSE",
	"REPO_CODE"=>"fuse",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_ZFS_FUSE",
	"REPO_CODE"=>"zfs-fuse",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_TOKYOCABINET",
	"REPO_CODE"=>"tokyocabinet",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_LESSFS",
	"REPO_CODE"=>"lessfs",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_DAR",
	"REPO_CODE"=>"dar",
	"FAMILY"=>"BACKUP",
	"SUBFAMILY"=>"CORE"
	);	
		
$products[]=array(
	"PRODUCT_CODE"=>"APP_CLAMAV",
	"REPO_CODE"=>"clamav",
	"FAMILY"=>"SYSTEM",
	"SUBFAMILY"=>"AV"
	);	
	
	
 $products[]=array(
	"PRODUCT_CODE"=>"APP_SNORT",
	"REPO_CODE"=>"snort",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY"
	);	
 $products[]=array(
	"PRODUCT_CODE"=>"APP_NMAP",
	"REPO_CODE"=>"nmap",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"SECURITY"
	);	
	
	
 $products[]=array(
	"PRODUCT_CODE"=>"APP_SMARTMONTOOLS",
	"REPO_CODE"=>"smartmontools",
	"FAMILY"=>"SYSTEM",
	"SUBFAMILY"=>"SECURITY"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_WINEXE",
	"REPO_CODE"=>"winexe-static",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	
	
	
$products[]=array(
	"PRODUCT_CODE"=>"APP_OCSI",
	"REPO_CODE"=>"OCSNG_UNIX_SERVER",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_OCSI2",
	"REPO_CODE"=>"OCSNG_UNIX_SERVER2",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_OCSI_LINUX_CLIENT",
	"REPO_CODE"=>"OCSNG_LINUX_AGENT",
	"FAMILY"=>"NETWORK",
	"SUBFAMILY"=>"CORE"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_XAPIAN",
	"REPO_CODE"=>"xapian-core",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_XAPIAN_OMEGA",
	"REPO_CODE"=>"xapian-omega",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_XAPIAN_PHP",
	"REPO_CODE"=>"xapian-bindings",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_XPDF",
	"REPO_CODE"=>"xpdf",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	

$products[]=array(
	"PRODUCT_CODE"=>"APP_UNRTF",
	"REPO_CODE"=>"unrtf",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);	
$products[]=array(
	"PRODUCT_CODE"=>"APP_CATDOC",
	"REPO_CODE"=>"catdoc",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);		
$products[]=array(
	"PRODUCT_CODE"=>"APP_ANTIWORD",
	"REPO_CODE"=>"antiword",
	"FAMILY"=>"FILESHARE",
	"SUBFAMILY"=>"SEARCH"
	);		
return $products;
	
}