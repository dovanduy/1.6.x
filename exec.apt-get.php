<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


$_GET["APT-GET"]="/usr/bin/apt-get";

if($GLOBALS["VERBOSE"]){echo "Checks {$argv[1]}\n";}

if($argv[1]=='--sources-list'){CheckSourcesList();die();}
if($argv[1]=='--wsgate'){wsgate_debian();die();}
if($argv[1]=='--phpfpm'){php_fpm();die();}
if($argv[1]=='--phpfpm-daemon'){php_fpm(true);die();}
if($argv[1]=='--nginx'){check_nginx(true);die();}
if($argv[1]=='--pkg-upgrade'){UPGRADE_FROM_INTERFACE();die();}


if(system_is_overloaded(basename(__FILE__))){
	system_admin_events("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"system-update");
	die();
}

if(!is_file($_GET["APT-GET"])){
	if(is_file("/usr/bin/yum")){CheckYum();die();exit;}
	die();
}




$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5($argv[1]).".pid";
$oldpid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($oldpid,basename(__FILE__))){
	$timefile=$unix->file_time_min($pidfile);
	//$text,$function,$file,$line,$category,$taskid=0
	system_admin_events(basename(__FILE__).": Already executed pid $oldpid since $timefile minutes.. aborting the process","MAIN",__FILE__,__LINE__,"update");
	die();
}
@unlink($pidfile);
@file_put_contents($pidfile, getmypid());

if($argv[1]=='--update'){GetUpdates();die();}
if($argv[1]=='--upgrade'){UPGRADE();die();}
if($argv[1]=='--clean-upgrade'){clean_upgrade();die();}



function clean_upgrade(){
	@unlink("/etc/artica-postfix/apt.upgrade.cache");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE syspackages_updt","artica_backup");
	echo "Packages \"to upgrade\" list as been flushed....\n";
	
}

function GetUpdates(){
	if(system_is_overloaded(basename(__FILE__))){system_admin_events("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"system-update");die();}	
	$sock=new sockets();
	$EnableSystemUpdates=$sock->GET_INFO("EnableSystemUpdates");
	if(!is_numeric($EnableSystemUpdates)){$EnableSystemUpdates=0;}
	if($EnableSystemUpdates==0){clean_upgrade();return;}
	
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
	@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");

	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();
	exim_remove();
	CheckSourcesList();
	wsgate_debian();
	
	$sock=new sockets();	
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$configDisk=trim($sock->GET_INFO('ArticaAutoUpdateConfig'));	
	$ini->loadString($configDisk);	
	$AUTOUPDATE=$ini->_params["AUTOUPDATE"];
	$EXEC_NICE=EXEC_NICE();
	$nohup=$unix->find_program("nohup");
	if(trim($AUTOUPDATE["auto_apt"])==null){$AUTOUPDATE["auto_apt"]="no";}
	$q=new mysql();
	php_fpm();
	
	if($GLOBALS["VERBOSE"]){system_admin_events("Running apt-check",__FUNCTION__,__FILE__,__LINE__,"system-update");}
	exec("{$_GET["APT-GET"]} check 2>&1",$results);
	if($GLOBALS["VERBOSE"]){system_admin_events("Running apt-check -> " . count($results) . " items",__FUNCTION__,__FILE__,__LINE__,"update");}

	while (list ($num, $line) = each ($results) ){
			if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: $line",__FUNCTION__,__FILE__,__LINE__,"update");}
			if(preg_match("#dpkg --configure -a#", $line)){
					$cmd="DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confold 2>&1";
					if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: Executing $cmd",__FUNCTION__,__FILE__,__LINE__,"update");}					
					exec("$cmd",$results1);
					while (list ($num1, $line1) = each ($results1) ){
						if(preg_match("#hardlink between a file in.+?backuppc#", $line1)){
							if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: remove backuppc",__FUNCTION__,__FILE__,__LINE__,"update");}
							shell_exec("{$_GET["APT-GET"]} -y remove backuppc --force-yes ");
						}
						
					}
					
					
					system_admin_events("dpkg was interrupted\nReconfigure has been performed\n".@implode("\n",$results1),__FUNCTION__,__FILE__,__LINE__,"update","update");
					if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: reconfigure:\n".@implode("\n",$results1),__FUNCTION__,__FILE__,__LINE__,"update");}
					return ;
				}
					
	}

	


	exec("{$_GET["APT-GET"]} update 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){system_admin_events("update: $line",__FUNCTION__,__FILE__,__LINE__,"system-update");}
	}

$results=array();
exec("{$_GET["APT-GET"]} -f install --force-yes 2>&1",$results);
while (list ($num, $line) = each ($results) ){
		if(preg_match("#hardlink between a file in.+?backuppc#", $line)){
			if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: remove backuppc \"{$_GET["APT-GET"]} remove backuppc --force-yes\"",__FUNCTION__,__FILE__,__LINE__,"system-update");}
			shell_exec("{$_GET["APT-GET"]} -y remove backuppc --force-yes ");
		}	
	if($GLOBALS["VERBOSE"]){system_admin_events("-f install: $line",__FUNCTION__,__FILE__,__LINE__,"system-update");}
	
}

if(COUNT_REPOS()==0){
	if($GLOBALS["VERBOSE"]){system_admin_events(" -> INSERT_DEB_PACKAGES()",__FUNCTION__,__FILE__,__LINE__,"system-update");}
	INSERT_DEB_PACKAGES();
}



shell_exec("{$_GET["APT-GET"]} -f install --force-yes >/dev/null 2>&1");
shell_exec("{$_GET["APT-GET"]} upgrade -s >$tmpf 2>&1");
	
$datas=@file_get_contents($tmpf);
$tbl=explode("\n",$datas);
system_admin_events("Found ". strlen($datas)." bytes for apt",__FUNCTION__,__FILE__,__LINE__,"system-update");
@unlink($tmpf);

$q->QUERY_SQL("TRUNCATE TABLE syspackages_updt","artica_backup");

	while (list ($num, $val) = each ($tbl) ){

		
		if($val==null){continue;}
		if(preg_match("#^Inst\s+(.+?)\s+#",$val,$re)){
			$packages[]=$re[1];
			if(preg_match("#libclamav#", $re[1])){if($users->KASPERSKY_WEB_APPLIANCE){shell_exec("$EXEC_NICE{$_GET["APT-GET"]} remove -y -q libclamav* clamav* --purge");continue;}}
			system_admin_events("Found {$re[1]} new package",__FUNCTION__,__FILE__,__LINE__,"system-update");
			$q->QUERY_SQL("INSERT IGNORE INTO syspackages_updt (package) VALUES('".addslashes(trim($re[1]))."')","artica_backup");
			if(!$q->ok){echo "$q->mysql_error\n";}
			if(!$q->ok){if(preg_match("#doesn't exist#", $q->mysql_error)){$q->BuildTables();$q->QUERY_SQL("INSERT IGNORE INTO syspackages_updt (package) VALUES('".trim($re[1])."')","artica_backup");}}
			
			
			
		}else{
			if(preg_match("#dpkg was interrupted.+?dpkg --configure -a#",$val)){
				send_email_events("dpkg was interrupted","Reconfigure all will be performed\n$val",
				"update");
				shell_exec("DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confold >/dev/null");
				return;
			}
			
			if(preg_match("#dpkg --configure -a#", $val)){
		 		send_email_events("dpkg was interrupted",
		 		"Reconfigure all will be performed\n$val","update");
				shell_exec("DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confold >/dev/null");
				return ;
			}			
			
			system_admin_events("Garbage \"$val\"",__FUNCTION__,__FILE__,__LINE__,"system-update");
		}
		
	}

	$count=count($packages);
	if($count>0){
		@file_put_contents("/etc/artica-postfix/apt.upgrade.cache",implode("\n",$packages));
		$text="You can perform upgrade of linux packages for\n".@file_get_contents("/etc/artica-postfix/apt.upgrade.cache");
		system_admin_events("New upgrade $count packages(s) ready $text",__FUNCTION__,__FILE__,__LINE__,"update");
		send_email_events("new upgrade $count packages(s) ready",$text,"update");
		
		$paragraph=ParagrapheTEXT('32-infos.png',"$count {system_packages}",
		"$count {system_packages_can_be_upgraded}","javascript:Loadjs('artica.update.php');
		","{system_packages_can_be_upgraded}",300,80);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/debian.update.html", $paragraph);
		shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/debian.update.html");
		
		if($AUTOUPDATE["auto_apt"]=="yes"){UPGRADE(true);}
	}else{
		system_admin_events("No new packages...",__FUNCTION__,__FILE__,__LINE__,"update");
		
		@unlink("/etc/artica-postfix/apt.upgrade.cache");
	}
	
	exec("/usr/share/artica-postfix/bin/setup-ubuntu --check-base-system 2>&1",$results2);
	system_admin_events("Checks Artica required packages done\n".@implode("\n", $results2),__FUNCTION__,__FILE__,__LINE__,"update");



}



function FIX_DEBIAN_MULTIMEDIA(){
	if(!is_file("/etc/apt/sources.list")){return;}
	$f=@explode("\n",@file_get_contents("/etc/apt/sources.list"));
	
	$WRITE=false;
	while (list ($num, $val) = each ($f) ){
		
			if(preg_match("#debian-multimedia#",$val)){
				$f[$num]=str_replace("www.debian-multimedia.org", "www.deb-multimedia.org", $val);
				$WRITE=TRUE;
				return;
			}
	}
	
	if($WRITE){
		@file_put_contents("/etc/apt/sources.list", @implode("\n", $f));
	}
			
	
}

function UPGRADE_FROM_INTERFACE(){
	
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timefile=$unix->file_time_min($pidfile);
		//$text,$function,$file,$line,$category,$taskid=0
		system_admin_events(basename(__FILE__).": Already executed pid $oldpid since $timefile minutes.. aborting the process",__FUNCTION__,__FILE__,__LINE__,"update");
		die();
	}
	
//if(system_is_overloaded(basename(__FILE__))){system_admin_events("Overloaded system... aborting task...",__FUNCTION__,__FILE__,__LINE__,"system-update");die();}	
	
	
	$aptitude=$unix->find_program("aptitude");
	if(!is_file($aptitude)){return;}
	//if(system_is_overloaded()){$unix->events(basename(__FILE__).": UPGRADE_FROM_INTERFACE() system is overloaded aborting");return;}
	
	
		$q=new mysql();
		$sql="SELECT * FROM syspackages_updt WHERE upgrade=1 AND progress<90";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(mysql_num_rows($results)==0){return;}
		
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["package"]==null){$q->QUERY_SQL("DELETE FROM syspackages_updt  WHERE package=''");continue;}	
			$q->QUERY_SQL("UPDATE syspackages_updt SET progress=50  WHERE package='{$ligne["package"]}'","artica_backup");
			$results2=array();
			$cmd="$aptitude --safe-resolver --allow-untrusted --allow-new-upgrades -q -y full-upgrade {$ligne["package"]} 2>&1";
			exec($cmd,$results2);
			update_events("Results on upgrade {$ligne["package"]}\n\n". @implode("\n", $results2),__FUNCTION__,__FILE__,__LINE__,"system-update","system_update");	
			$q->QUERY_SQL("UPDATE syspackages_updt SET progress=100  WHERE package='{$ligne["package"]}'","artica_backup");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n".@implode("\n", $results2);}
			if(system_is_overloaded()){
				$unix->events(basename(__FILE__).": UPGRADE_FROM_INTERFACE() system is overloaded aborting");
				$unix->THREAD_COMMAND_SET("$php5 ".__FILE__." --pkg-upgrade");
				return;
			}
		}
		if(!is_file("/etc/cron.d/pkg-upgrade")){@unlink("/etc/cron.d/pkg-upgrade");}
		GetUpdates();
}


function dpkg_configure_a(){
	if(system_is_overloaded(basename(__FILE__))){system_admin_events("Overloaded system... aborting task...",__FUNCTION__,__FILE__,__LINE__,"system-update");die();}
	$unix=new unix();
	$binpath=$unix->find_program("dpkg-reconfigure");
	if(strlen($binpath)==null){return;}
	exec("$binpath -a -f -p 2>&1",$results);
	while (list ($num, $val) = each ($results) ){
		$val=strip_error_perl($val);
		if($val==null){continue;}
		if(preg_match("#-reconfigure:\s+(.+?)\s+is broken or not fully installed#",$val,$re)){
			$f[]="ERROR DETECTED! on {$re[1]} package, see artica support \"$val\"";
			continue;
		}
		$f[]=$val;
	}
	
	if(count($f)>0){
		system_admin_events("Failed: DPKG reconfigure results\nIt seems that the system need to reconfigure package, this is the results:".@implode("\n",$f),__FUNCTION__,__FILE__,__LINE__,"system-update");
		
	}
	
}


function strip_error_perl($line){
	
if(strpos($line,"warning: Setting locale failed.")>0){return null;}
if(strpos($line,"Please check that your locale settings")>0){return null;}
if(strpos($line,"LANGUAGE =")>0){return null;}
if(strpos($line,"LC_ALL =")>0){return null;}
if(strpos($line,'LANG = "')>0){return null;}
if(strpos($line,"supported and installed on your system.")>0){return null;}
return $line;	
	
	
}

function COUNT_REPOS(){
	$q=new mysql();
	return $q->COUNT_ROWS("debian_packages", "artica_backup");
}



function INSERT_DEB_PACKAGES(){
	if(system_is_overloaded(basename(__FILE__))){
		system_admin_events("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"system-update");
		die();
	}	
	
	$sock=new sockets();
	$EnableSystemUpdates=$sock->GET_INFO("EnableSystemUpdates");
	if(!is_numeric($EnableSystemUpdates)){$EnableSystemUpdates=0;}
	if($EnableSystemUpdates==0){return;}
	
	if(!is_file("/usr/bin/dpkg")){die();}
	$sql="TRUNCATE TABLE `debian_packages`";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();	
	shell_exec("/usr/bin/dpkg -l >$tmpf 2>&1");
	$datas=@file_get_contents($tmpf);
	@unlink($tmpf);
	$tbl=explode("\n",$datas);
	
	$prefix="INSERT IGNORE INTO debian_packages(package_status,package_name,package_version,package_info,package_description) VALUES ";
	
	while (list ($num, $val) = each ($tbl) ){
		if($val==null){continue;}
		$c++;
	if(preg_match("#^([a-z]+)\s+(.+?)\s+(.+?)\s+(.+)#",$val,$re)){
			$content=addslashes($re[4]);
			$pname=$re[2];
			$package_description=addslashes(PACKAGE_EXTRA_INFO($pname));
			
			$tr[]="('{$re[1]}','$pname','{$re[3]}','$content','$package_description')";
  			if(count($tr)>500){
  				$sql=$prefix.@implode(",", $tr);
  				$tr=array();
  				$q->QUERY_SQL($sql,"artica_backup");
  				if(!$q->ok){echo $q->mysql_error;}
  			}  			
			
		}	
	}
	
  			if(count($tr)>0){
  				$sql=$prefix.@implode(",", $tr);
  				$tr=array();
  				$q->QUERY_SQL($sql,"artica_backup");
  				if(!$q->ok){echo $q->mysql_error;}
  			}  	
	
}

function PACKAGE_EXTRA_INFO($pname){
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();		
	shell_exec("/usr/bin/dpkg-query -p $pname >$tmpf 2>&1");
	$datas=@file_get_contents($tmpf);
	@unlink($tmpf);
}

function UPGRADE($noupdate=false){
if(system_is_overloaded(basename(__FILE__))){
	system_admin_events("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"system-update");
	die();
}	
	
	$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	
	system_admin_events("Running UPGRADE $called",__FUNCTION__,__FILE__,__LINE__,"system-update");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");
	$unix=new unix();
	$sock=new sockets();
	$EnableRebootAfterUpgrade=$sock->GET_INFO("EnableRebootAfterUpgrade");
	if(!is_numeric($EnableRebootAfterUpgrade)){$EnableRebootAfterUpgrade=0;}
	$tmpf=$unix->FILE_TEMP();		
	$txt="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin\n";
	$txt=$txt."echo \$PATH >$tmpf 2>&1\n";
	$txt=$txt."rm -f $tmpf\n";

$tmpf=$unix->FILE_TEMP();	
@file_put_contents($tmpf,$txt);
@chmod($tmpf,'0777');
shell_exec($tmpf);

$tmpf=$unix->FILE_TEMP();
$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\" --force-yes update >$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);


$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes install -f >$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);


$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes upgrade >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes dist-upgrade >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\" --force-yes --yes autoremove >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$datas=@file_get_contents($tmpf);
$datassql=addslashes($datas);


$q=new mysql();
$sql="INSERT IGNORE INTO debian_packages_logs(zDate,package_name,events,install_type) VALUES(NOW(),'artica-upgrade','$datassql','upgrade');";
$q->QUERY_SQL($sql,"artica_backup");  	
@unlink('/etc/artica-postfix/apt.upgrade.cache');
if(!$noupdate){GetUpdates();}

send_email_events("Debian/Ubuntu System upgrade operation",$datas,"update");
INSERT_DEB_PACKAGES();
if($EnableRebootAfterUpgrade==1){
	send_email_events("Rebooting after upgrade operation",
	"reboot command has been performed","update");
	shell_exec("reboot");
}

}

function check_nginx(){
	

	
	$unix=new unix();
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){return;}
	if(is_file("/etc/lsb-release")){if($GLOBALS["VERBOSE"]){ "CheckSourcesList: Ubuntu system, aborting\n";}}
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){$Major=6;}
	$Major=$re[1];
	if($Major<>6){
		echo "CheckSourcesList: Debian version <> $Major aborting...\n";
		return;
	}

	$update=false;
	$FOUND=false;
	$f=@explode("\n",@file_get_contents("/etc/apt/sources.list"));
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#packages\.nginx\.org#",$val)){
			echo "CheckSourcesList:  /etc/apt/sources.list correct with nginx repository\n";
			$FOUND=true;
			break;
		}
	}
	if(!$FOUND){
		$update=true;
		echo "CheckSourcesList: adding nginx repositories...\n";
		$f[]="deb http://nginx.org/packages/debian/ squeeze nginx";
		$f[]="deb-src http://nginx.org/packages/debian/ squeeze nginx";
		@file_put_contents("/etc/apt/sources.list", @implode("\n", $f));
	}	
	$KEY="2048R\/7BD9BF62";
	$aptkey=$unix->find_program("apt-key");
	$aptget=$unix->find_program("apt-get");
	$wget=$unix->find_program("wget");
	exec("$aptkey list 2>&1",$results);
	$FOUND=false;
	while (list ($num, $val) = each ($results) ){
		if(preg_match("#$KEY#", $val)){
			echo "CheckSourcesList: key $KEY correct with dotdeb repository\n";
			$FOUND=true;
			break;
		}
	}
	
	if(!$FOUND){
	$update=true;
	echo "CheckSourcesList: Adding new key $KEY...\n";
	shell_exec("$wget 'http://nginx.org/packages/keys/nginx_signing.key' --quiet --output-document=- | $aptkey add -");
	
	}	
	
	if($update){
		echo "CheckSourcesList: updating repository\n";
		shell_exec("$aptget update");
	}

	
	
}


function Check_dotdeb(){
	FIX_DEBIAN_MULTIMEDIA();
	$unix=new unix();
	if(is_file("/etc/lsb-release")){if($GLOBALS["VERBOSE"]){ "CheckSourcesList: Ubuntu system, aborting\n";}}
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){$Major=6;}
	$Major=$re[1];
	if($Major<>6){
		echo "CheckSourcesList: Debian version <> $Major aborting...\n";
		return;
	}	
	$update=false;
	$FOUND=false;
	$f=@explode("\n",@file_get_contents("/etc/apt/sources.list"));
	while (list ($num, $val) = each ($f) ){
		if(preg_match("#packages\.dotdeb\.org#",$val)){
			echo "CheckSourcesList:  /etc/apt/sources.list correct with ngninx repository\n";
			$FOUND=true;
			break;
		}
	}
	if(!$FOUND){
		$update=true;
		echo "CheckSourcesList: adding dotdeb repositories...\n";

		$f[]="deb-src http://packages.dotdeb.org squeeze all";
		$f[]="deb http://packages.dotdeb.org squeeze all";		
		@file_put_contents("/etc/apt/sources.list", @implode("\n", $f));
	}
	
	$KEY="4096R\/89DF5277";
	$aptkey=$unix->find_program("apt-key");
	$aptget=$unix->find_program("apt-get");
	$wget=$unix->find_program("wget");
	exec("$aptkey list 2>&1",$results);
	$FOUND=false;
	while (list ($num, $val) = each ($results) ){
		if(preg_match("#$KEY#", $val)){
			echo "CheckSourcesList: key $KEY correct with dotdeb repository\n";
			$FOUND=true;
			break;
			}
	}
	
	if(!$FOUND){
		$update=true;
		echo "CheckSourcesList: Adding new key $KEY...\n";
		shell_exec("$wget 'http://www.dotdeb.org/dotdeb.gpg' --quiet --output-document=- | $aptkey add -");
		
	}
	
	
	$PACKAGES="libapache2-mod-php5 libapache2-mod-php5filter php5-cgi php5-cli php5-common php5-curl php5-dbg php5-dev php5-enchant php5-fpm php5-gd php5-gmp php5-imap php5-interbase php5-intl php5-ldap php5-mcrypt php5-mysql php5-odbc php5-pgsql php5-pspell php5-recode php5-snmp php5-sqlite php5-sybase php5-tidy php5-xmlrpc php5-xsl";
	$PECL="php5-apc php5-ffmpeg php5-gearman php5-geoip php5-http php5-imagick php5-memcache php5-memcached php5-pinba php5-redis php5-spplus php5-ssh2 php5-suhosin php5-xcache php5-xdebug php5-xhprof";
	$ALL="php-pear php5";
	if(!is_file("/etc/apt/preferences.d/dotdeb-org")){
		$t[]="Package: $PACKAGES $PECL $ALL";
		$t[]="Pin: origin packages.dotdeb.org";
		$t[]="Pin-Priority: 500";
		echo "CheckSourcesList: /etc/apt/preferences.d/dotdeb-org done...\n";
		@mkdir("/etc/apt/preferences.d",0755,true);
		@file_put_contents("/etc/apt/preferences.d/dotdeb-org", @implode("\n", $t));
	}
	


	
	if($update){
		echo "CheckSourcesList: updating repository\n";
		shell_exec("$aptget update");
	}
}

function exim_remove(){
	$unix=new unix();
	$f[]="/usr/lib/exim4/exim4";
	$f[]="/usr/sbin/exim";
	$f[]="/usr/sbin/exim4";
	$f[]="/etc/init.d/exim4";
	$f[]="/usr/sbin/exim";
	$removeexim=false;
	while (list ($num, $val) = each ($f) ){
		if(is_file($val)){
			$removeexim=true;
		}
		
	}
	
	$eximp[]="exim4";
	$eximp[]="exim4-base";
	$eximp[]="exim4-config";
	$eximp[]="exim4-daemon-light";
	
	if($removeexim){
		$aptget=$unix->find_program("apt-get");
		$echo=$unix->find_program("echo");
		$dpkg=$unix->find_program("dpkg");
		while (list ($num, $val) = each ($eximp) ){
			shell_exec("$echo $val hold|$dpkg --set-selections");
		}
		   
		
		
		$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y remove exim* 2>&1";
		shell_exec($cmd);
	}
	sendmail_remove();
}

function sendmail_remove(){
	
	$f[]="/etc/init.d/sendmail";
	$removeexim=false;
	while (list ($num, $val) = each ($f) ){
		if(is_file($val)){
			$removeexim=true;
		}
	
	}
	if($removeexim){
		shell_exec("/etc/init.d/sendmail stop");
		shell_exec("/usr/sbin/update-rc.d -f sendmail remove");
	}	
}


function CheckSourcesList(){
if(is_file("/etc/lsb-release")){if($GLOBALS["VERBOSE"]){ "CheckSourcesList: Ubuntu system, aborting\n";}}	
if(!is_file("/etc/debian_version")){return;}
$ver=trim(@file_get_contents("/etc/debian_version"));
preg_match("#^([0-9]+)\.#",$ver,$re);
if(preg_match("#squeeze\/sid#",$ver)){$Major=6;}
$Major=$re[1];
echo "CheckSourcesList: Debian version $Major\n";
if(!is_numeric($Major)){ echo "CheckSourcesList: Debian version failed \"$ver\"\n";return;}

$f=@explode("\n",@file_get_contents("/etc/apt/sources.list"));
$detected=false;
while (list ($num, $val) = each ($f) ){
	if($Major==5){
		if(preg_match("#deb\s+http:.+?archive#",$val)){
			echo "CheckSourcesList:  /etc/apt/sources.list correct, return\n";
			return;
		}
		continue;
	}
	
	if(preg_match("#deb\s+http:.+?#",$val)){
			echo "CheckSourcesList:  /etc/apt/sources.list correct, return\n";
			Check_dotdeb();
			return;
	}
}

$f=array();
if($Major==5){
	$f[]="deb http://archive.debian.org/debian-archive/debian/ lenny main contrib non-free";
	$f[]="deb-src http://archive.debian.org/debian-archive/debian/ lenny main contrib non-free";
	@file_put_contents("/etc/apt/sources.list",@implode("\n",$f));
	echo "CheckSourcesList:  /etc/apt/sources.list configured, done...\n";
}
if($Major==6){
		$f[]="deb http://ftp.fr.debian.org/debian/ squeeze main";
		$f[]="deb-src http://ftp.fr.debian.org/debian/ squeeze main";
		$f[]="deb http://security.debian.org/ squeeze/updates main";
		$f[]="deb-src http://security.debian.org/ squeeze/updates main";
		$f[]="deb http://ftp.fr.debian.org/debian/ squeeze-updates main";
		$f[]="deb-src http://ftp.fr.debian.org/debian/ squeeze-updates main";
		$f[]="deb http://ftp.debian.org/debian/ squeeze main non-free";
		$f[]="deb-src http://ftp.debian.org/debian/ squeeze main non-free";
		$f[]="deb http://ftp.debian.org/debian squeeze main";
		$f[]="deb-src http://packages.dotdeb.org squeeze all";
		$f[]="deb http://packages.dotdeb.org squeeze all";
		
		@file_put_contents("/etc/apt/sources.list",@implode("\n",$f));
		echo "CheckSourcesList:  /etc/apt/sources.list configured, done...\n";	
}

}

function CheckYum(){
	@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");
	exec("/usr/bin/yum check-updates 2>&1",$results);
	while (list ($num, $val) = each ($results) ){
	if(preg_match("#(.+?)\s+(.+?)\s+updates#", $val)){$p[$re[1]]=true;$packages[]=$re[1];}}
		
	$count=count($p);
	if($count>0){
		@file_put_contents("/etc/artica-postfix/apt.upgrade.cache",implode("\n",$packages));
		$text="You can perform upgrade of linux packages for\n".@file_get_contents("/etc/artica-postfix/apt.upgrade.cache");
		send_email_events("new upgrade $count packages(s) ready",$text,"update");
		
		$paragraph=Paragraphe('64-infos.png',"$count {system_packages}",
		"$count {system_packages_can_be_upgraded}",null,"{system_packages_can_be_upgraded}",300,80);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/debian.update.html", $paragraph);
		shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/debian.update.html");		
	}
	
}

function wsgate_debian(){
	if($GLOBALS["VERBOSE"]){echo "Load unix class...\n";}
	$unix=new unix();
	
	if($GLOBALS["VERBOSE"]){echo "Load unix class done..\n";}
	if(!is_dir("/etc/apt/sources.list.d")){
		if($GLOBALS["VERBOSE"]){echo "/etc/apt/sources.list.d, no such directory\n";}
		return;
	}
	
	if(is_file("/etc/apt/sources.list.d/freerdp.list")){@unlink("/etc/apt/sources.list.d/freerdp.list");}
	
	if(is_file("/etc/apt/sources.list.d/freerdp1.list")){
		if($GLOBALS["VERBOSE"]){echo "/etc/apt/sources.list.d/freerdp1.list already set\n";}
		return;			
	}	
	

	
	$sourcelist=null;
	$LINUX_CODE_NAME=$unix->LINUX_CODE_NAME();
	$LINUXVER=$unix->LINUX_VERS();
	
	if($GLOBALS["VERBOSE"]){echo "$LINUX_CODE_NAME {$LINUXVER[0]}.{$LINUXVER[1]}\n";}
	
	if($LINUX_CODE_NAME=="DEBIAN"){
		if($LINUXVER[0]>5){
			$sourcelist="deb http://download.opensuse.org/repositories/home:/felfert/Debian_6.0 ./";
		}
	}
	
	if($LINUX_CODE_NAME=="UBUNTU"){
		if($LINUXVER[0]>9){
			if($LINUXVER[1]>9){
				$sourcelist="deb http://download.opensuse.org/repositories/home:/felfert/xUbuntu_10.10 ./";
			}
		}
		
		if($LINUXVER[0]>10){
			if($LINUXVER[1]>9){
				$sourcelist="deb http://download.opensuse.org/repositories/home:/felfert/xUbuntu_11.10 ./";
			}
		}

		if($LINUXVER[0]>11){
			if($LINUXVER[1]>3){
				$sourcelist="deb http://download.opensuse.org/repositories/home:/felfert/xUbuntu_12.04 ./";
			}			
		}
		
	}
	if($sourcelist==null){if($GLOBALS["VERBOSE"]){echo "sourcelist is null\n";}return;}
	
	$wget=$unix->find_program("wget");
	$aptkey=$unix->find_program("apt-key");
	$aptget=$unix->find_program("apt-get");
	
	$cmd="$wget -O - http://download.opensuse.org/repositories/home:/felfert/Debian_6.0/Release.key | $aptkey add -";
	shell_exec($cmd);
	@file_put_contents("/etc/apt/sources.list.d/freerdp1.list", $sourcelist);
	
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y update 2>&1";
	exec($cmd,$results);
	system_admin_events($cmd."\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"system-update");
	shell_exec($cmd);	
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install wsgate 2>&1";
	exec($cmd,$results);
	system_admin_events($cmd."\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__,"system-update");
	shell_exec($cmd);		
	
}

function php_fpm($aspid=false){
	
	$unix=new unix();
	if(is_file("/etc/lsb-release")){if($GLOBALS["VERBOSE"]){ "CheckSourcesList: Ubuntu system, aborting\n";}}
	if(!is_file("/etc/debian_version")){return;}
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	if(is_file($phpfpm)){nginx();return;}
	
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$kill=$unix->find_program("kill");
		$timexec=$unix->file_time_min($pidTime);
		if($timexec<240){return;}
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($time<30){return;}
			shell_exec("$kill -9 $oldpid >/dev/null 2>&1");
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){$Major=6;}
	$Major=$re[1];
	if($Major<>6){
		echo "CheckSourcesList: Debian version <> $Major aborting...\n";
		return;
	}	
	
	Check_dotdeb();
	$unix=new unix();
	$aptget=$unix->find_program("apt-get");
	echo "CheckSourcesList: Installing php5-fpm\n";
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install php5-fpm libapache2-mod-fastcgi 2>&1";
	echo "CheckSourcesList: $cmd\n";
	shell_exec($cmd);	
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	if(is_file($phpfpm)){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --phppfm");
		shell_exec("/etc/init.d/php5-fpm restart");
		shell_exec("/etc/init.d/artica-postfix restart apache");
		shell_exec("/etc/init.d/artica-postfix restart artica-status");
		shell_exec("/etc/init.d/artica-framework restart");
		shell_exec("$php /usr/share/artica-postfix/exec.freeweb.php --build");
	}
	
	
	
}
function nginx($aspid=false){

	$unix=new unix();
	if(is_file("/etc/lsb-release")){if($GLOBALS["VERBOSE"]){ "CheckSourcesList: Ubuntu system, aborting\n";}}
	if(!is_file("/etc/debian_version")){return;}
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){return;}

	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$kill=$unix->find_program("kill");
		$timexec=$unix->file_time_min($pidTime);
		if($timexec<240){return;}
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
		$oldpid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($oldpid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($oldpid);
			if($time<30){return;}
			shell_exec("$kill -9 $oldpid >/dev/null 2>&1");
		}
		@file_put_contents($pidfile, getmypid());
	}



	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){$Major=6;}
	$Major=$re[1];
	if($Major<>6){
		echo "CheckSourcesList: Debian version <> $Major aborting...\n";
		return;
	}

	check_nginx();
	$unix=new unix();
	$aptget=$unix->find_program("apt-get");
	echo "CheckSourcesList: Installing nginx\n";
	$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install nginx 2>&1";
	echo "CheckSourcesList: $cmd\n";
	shell_exec($cmd);
	$nginx=$unix->find_program("nginx");
	if(is_file($nginx)){
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --nginx");
		shell_exec("/etc/init.d/nginx restart");

	}



}




?>