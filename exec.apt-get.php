<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


$_GET["APT-GET"]="/usr/bin/apt-get";

if($argv[1]=='--sources-list'){CheckSourcesList();die();}

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
	system_admin_events(basename(__FILE__).": Already executed pid $oldpid since $timefile minutes.. aborting the process","MAIN",__FILE__,__LINE__);
	die();
}
@unlink($pidfile);
@file_put_contents($pidfile, getmypid());

if($argv[1]=='--update'){GetUpdates();die();}
if($argv[1]=='--upgrade'){UPGRADE();die();}
if($argv[1]=='--pkg-upgrade'){UPGRADE_FROM_INTERFACE();die();}
if($argv[1]=='--clean-upgrade'){clean_upgrade();die();}



function clean_upgrade(){
	@unlink("/etc/artica-postfix/apt.upgrade.cache");
	@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE syspackages_updt","artica_backup");
	echo "Packages \"to upgrade\" list as been flushed....\n";
	
}

function GetUpdates(){
if(system_is_overloaded(basename(__FILE__))){
	system_admin_events("This system is too many overloaded, die()",__FUNCTION__,__FILE__,__LINE__,"system-update");
	die();
}	
	
@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);
@unlink("/usr/share/artica-postfix/ressources/logs/web/debian.update.html");

$unix=new unix();
$tmpf=$unix->FILE_TEMP();
CheckSourcesList();
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
if($GLOBALS["VERBOSE"]){system_admin_events("Running apt-check",__FUNCTION__,__FILE__,__LINE__,"system-update");}
exec("{$_GET["APT-GET"]} check 2>&1",$results);
if($GLOBALS["VERBOSE"]){system_admin_events("Running apt-check -> " . count($results) . " items",__FUNCTION__,__FILE__,__LINE__,"system-update");}

while (list ($num, $line) = each ($results) ){
		if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: $line",__FUNCTION__,__FILE__,__LINE__,"system-update");}
		if(preg_match("#dpkg --configure -a#", $line)){
				$cmd="DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confold 2>&1";
				if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: Executing $cmd",__FUNCTION__,__FILE__,__LINE__,"system-update");}					
				exec("$cmd",$results1);
				while (list ($num1, $line1) = each ($results1) ){
					if(preg_match("#hardlink between a file in.+?backuppc#", $line1)){
						if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: remove backuppc",__FUNCTION__,__FILE__,__LINE__,"system-update");}
						shell_exec("{$_GET["APT-GET"]} -y remove backuppc --force-yes ");
					}
					
				}
				
				
				system_admin_events("dpkg was interrupted\nReconfigure has been performed\n".@implode("\n",$results1),__FUNCTION__,__FILE__,__LINE__,"system-update","system-update");
				if($GLOBALS["VERBOSE"]){system_admin_events("apt-check: reconfigure:\n".@implode("\n",$results1),__FUNCTION__,__FILE__,__LINE__,"system-update");}
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
				send_email_events("dpkg was interrupted","Reconfigure all will be performed\n$val","system");
				shell_exec("DEBIAN_FRONTEND=noninteractive dpkg --configure -a --force-confold >/dev/null");
				return;
			}
			
			if(preg_match("#dpkg --configure -a#", $val)){
		 		send_email_events("dpkg was interrupted","Reconfigure all will be performed\n$val","system");
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
		system_admin_events("New upgrade $count packages(s) ready $text",__FUNCTION__,__FILE__,__LINE__,"system-update");
		send_email_events("new upgrade $count packages(s) ready",$text,"system");
		
		$paragraph=ParagrapheTEXT('32-infos.png',"$count {system_packages}",
		"$count {system_packages_can_be_upgraded}","javascript:Loadjs('artica.update.php');
		","{system_packages_can_be_upgraded}",300,80);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/debian.update.html", $paragraph);
		shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/debian.update.html");
		
		if($AUTOUPDATE["auto_apt"]=="yes"){UPGRADE(true);}
	}else{
		system_admin_events("No new packages...",__FUNCTION__,__FILE__,__LINE__,"system-update");
		
		@unlink("/etc/artica-postfix/apt.upgrade.cache");
	}
	
	exec("/usr/share/artica-postfix/bin/setup-ubuntu --check-base-system 2>&1",$results2);
	system_admin_events("Checks Artica required packages done\n".@implode("\n", $results2),__FUNCTION__,__FILE__,__LINE__,"system-update");



}


function UPGRADE_FROM_INTERFACE(){
	
if(system_is_overloaded(basename(__FILE__))){system_admin_events("Overloaded system... aborting task...",__FUNCTION__,__FILE__,__LINE__,"system-update");die();}	
	
	$unix=new unix();
	$aptitude=$unix->find_program("aptitude");
	if(!is_file($aptitude)){return;}
	if(system_is_overloaded()){$unix->events(basename(__FILE__).": UPGRADE_FROM_INTERFACE() system is overloaded aborting");return;}
	
	
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
			if(system_is_overloaded()){$unix->events(basename(__FILE__).": UPGRADE_FROM_INTERFACE() system is overloaded aborting");return;}
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

send_email_events("Debian/Ubuntu System upgrade operation",$datas,"system");
INSERT_DEB_PACKAGES();
if($EnableRebootAfterUpgrade==1){
	send_email_events("Rebooting after upgrade operation","reboot command has been performed","system");
	shell_exec("reboot");
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
		send_email_events("new upgrade $count packages(s) ready",$text,"system");
		
		$paragraph=Paragraphe('64-infos.png',"$count {system_packages}",
		"$count {system_packages_can_be_upgraded}",null,"{system_packages_can_be_upgraded}",300,80);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/debian.update.html", $paragraph);
		shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/debian.update.html");		
	}
	
}



?>