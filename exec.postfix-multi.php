<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.postfix-multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.assp-multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');


$_GET["LOGFILE"]="/usr/share/artica-postfix/ressources/logs/web/interface-postfix.log";
if(!is_file("/usr/share/artica-postfix/ressources/settings.inc")){shell_exec("/usr/share/artica-postfix/bin/process1 --force --verbose");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}

$unix=new unix();
$GLOBALS["postmulti"]=$unix->find_program("postmulti");
$GLOBALS["postconf"]=$unix->find_program("postconf");
$GLOBALS["postmap"]=$unix->find_program("postmap");
$GLOBALS["postalias"]=$unix->find_program("postalias");
$GLOBALS["postfix"]=$unix->find_program("postfix");

	if(class_exists("clladp")){
		$ldap=new clladp();
		if($ldap->ldapFailed){
			WriteToSyslogMail("Fatal: connecting to ldap server $ldap->ldap_host",basename(__FILE__),true);
			echo "Starting......: failed connecting to ldap server $ldap->ldap_host\n";
			$unix->send_email_events("Postfix user databases aborted (ldap failed)", "The process has been scheduled to start in few seconds.", "postfix"); 
			$unix->THREAD_COMMAND_SET(trim($unix->LOCATE_PHP5_BIN()." ".__FILE__. " {$argv[1]}"));
			die();
		}
	}
	
	if(class_exists("mysql")){	
		$mysql=new mysql();
		if(!$mysql->TestingConnection()){
			WriteToSyslogMail("Fatal: connecting to MySQL server $mysql->mysql_error",basename(__FILE__),true);
			echo "Starting......: failed connecting to ldap server $mysql->mysql_error\n";
			$unix->send_email_events("Postfix user databases aborted (MySQL failed)", "The process has been scheduled to start in few seconds.", "postfix"); 
			$unix->THREAD_COMMAND_SET(trim($unix->LOCATE_PHP5_BIN()." ".__FILE__. " {$argv[1]}"));
			die();		
		}
	}


if($argv[1]=='--reconfigure-all'){reconfigure();die();}
if($argv[1]=='--restart-all'){restart_all_instances();die();}
if($argv[1]=='--aliases'){build_all_aliases();die();}
if($argv[1]=='--instance-memory'){reconfigure_instance_tmpfs($argv[2],$argv[3]);die();}
if($argv[1]=='--instance-memory-kill'){reconfigure_instance_tmpfs_umount($argv[2]);die();}
if($argv[1]=='--destroy'){DestroyInstance($argv[2]);die();}




$sock=new sockets();
$GLOBALS["EnablePostfixMultiInstance"]=$sock->GET_INFO("EnablePostfixMultiInstance");
if($GLOBALS["EnablePostfixMultiInstance"]<>1){
		echo "Starting......: Multi-instances is not enabled ({$GLOBALS["EnablePostfixMultiInstance"]})\n";
		PostfixMultiDisable();
		die();
}
$unix=new unix();

	echo "Starting......: Enable Postfix multi-instances\n";
	
	$pidfile="/etc/artica-postfix/".basename(__FILE__)." ". md5(implode("",$argv)).".pid";
	$oldPid=@file_get_contents($pidfile);
	if($unix->process_exists($oldPid,basename(__FILE__))){
		echo "Starting......: multi-instances configurator already executed PID $oldPid\n";
		die();
	}

	$pid=getmypid();
	echo "Starting......: Postfix multi-instances configurator running $pid\n";
	file_put_contents($pidfile,$pid);	


writelogs("receive ". implode(",",$argv),"MAIN",__FILE__,__LINE__);

if($argv[1]=='--removes'){PostfixMultiDisable();die();}
if($argv[1]=='--instance-reconfigure'){reconfigure_instance($argv[2]);postfix_bubble();die();}
if($argv[1]=='--instance-relayhost'){reconfigure_instance_relayhost($argv[2]);postfix_bubble();die();}
if($argv[1]=='--instance-ssl'){reconfigure_instance_ssl($argv[2]);postfix_bubble();die();}
if($argv[1]=='--instance-settings'){reconfigure_instance_minimal($argv[2]);postfix_bubble();die();}
if($argv[1]=='--instance-mastercf'){reconfigure_instance_mastercf($argv[2]);postfix_bubble();die();}
if($argv[1]=='--clean'){remove_old_instances();postfix_bubble();die();}
if($argv[1]=='--mime-header-checks'){reconfigure_instance_mime_checks($argv[2]);die();}
if($argv[1]=='--from-main-maincf'){die();}
if($argv[1]=='--instance-start'){_start_instance($argv[2]);die();}
if($argv[1]=='--instance-aiguilleuse'){aiguilleuse($argv[2]);die();}
if($argv[1]=='--reload-all'){CheckInstances();postfix_bubble();die();}
if($argv[1]=='--postscreen'){postscreen($argv[2]);die();}
reconfigure();
postfix_bubble();


function postfix_bubble(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec($nohup." $php5 ".dirname(__FILE__)."/exec.postfix.multi.bubble.php >/dev/null 2>&1 &");
	
}




function restart_all_instances(){
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	$sock=new sockets();
	$GLOBALS["postmulti"]=$unix->find_program("postmulti");
	echo "Starting......: Stopping master instance\n";
	system("$postfix stop");
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){
		$main=new maincf_multi(null);
		$main->PostfixMainCfDefaultInstance();
	}	
	
	echo "Starting......: checking first instance security\n";
	system("$postfix -c /etc/postfix set-permissions");
	
	if($sock->GET_INFO("EnablePostfixMultiInstance")==1){
		echo "Starting......: checking all instances security\n";
		MysqlInstancesList();
		if(is_array($GLOBALS["INSTANCES_LIST"])){
			while (list ($num, $ligne) = each ($GLOBALS["INSTANCES_LIST"]) ){
				echo "Starting......: Postfix \"$ligne\" checking instance security\n";
				system("$postfix -c /etc/postfix-$ligne set-permissions");
			}
		}
		

		
		echo "Starting......: Starting master\n";
		system("$postfix stop");
		system("$postfix start");
		reset($GLOBALS["INSTANCES_LIST"]);
		while (list ($num, $hostname) = each ($GLOBALS["INSTANCES_LIST"]) ){
			
			_start_instance($hostname);
		}
		
	
	}else{
		echo "Starting......: Starting master\n";
		system("$postfix start");
	}
	
}



function reconfigure(){
	shell_exec("{$GLOBALS["postmulti"]} -e init >/dev/null 2>&1");	
	InstancesList();
	remove_old_instances();
	CheckInstances();
	
}


function InstancesList(){
	$unix=new unix();
	if($GLOBALS["postmulti"]==null){
		$GLOBALS["postmulti"]=$unix->find_program("postmulti");
	}
	if(is_dir("/etc/postfix-hub")){
		if(!is_file("/etc/postfix-hub/dynamicmaps.cf")){@file_put_contents("/etc/postfix-hub/dynamicmaps.cf","#");}
	}
	exec("{$GLOBALS["postmulti"]} -l -a",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#^(.+?)\s+#",$ligne,$re)){
			$re[1]=trim($re[1]);
			if($re[1]=='-'){continue;}
			echo "Starting......: Detecting instance {$re[1]}\n";
			$GLOBALS["INSTANCE"][$re[1]]=true;
			
			
		}
	}
	$tmpstr=$unix->FILE_TEMP();
	shell_exec("{$GLOBALS["postmulti"]} -p status >$tmpstr 2>&1");
	echo @file_get_contents($tmpstr);
	

	
}

function MysqlInstancesList(){
		$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname' GROUP BY `value`";	
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Starting......: Postfix error $q->mysql_error\n";}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$myhostname=trim($ligne["value"]);
			if($myhostname==null){continue;}
			if($myhostname=="master"){continue;}
			$main=new maincf_multi($myhostname);
			if($main->GET("DisabledInstance")==1){continue;}
			$GLOBALS["INSTANCES_LIST"][]=$myhostname;
		}	
	
}

function CheckInstances(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Starting......: CheckInstances function already executed PID ". @file_get_contents($pidfile)."\n";
		die();
	}

		$pid=getmypid();
		echo "Starting......: CheckInstances configurator running $pid\n";
		file_put_contents($pidfile,$pid);		
	
		$maincf=new maincf_multi("");
		$maincf->PostfixMainCfDefaultInstance();
		$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname' GROUP BY `value`";
		echo "Starting......: Postfix activate HUB(s)\n";

		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$myhostname=trim($ligne["value"]);
			if($myhostname==null){continue;}
			if($myhostname=="master"){continue;}
			echo "Starting......: Postfix \"$myhostname\" checking HUB\n";
			ConfigureMainCF($myhostname);
			
		}
	@unlink($pidfile);
}



function reconfigure_instance($hostname){
	$GLOBALS["UMOUNT_COUNT"]=0;
	if($hostname=="master"){return;}
	$users=new usersMenus();
	$unix=new unix();
	writelogs("reconfigure instance $hostname",__FUNCTION__,__FILE__,__LINE__);
	echo "Starting......: Postfix \"$hostname\" checking instance\n";
	$instance_path="/etc/postfix-$hostname";	
	$maincf=new maincf_multi($hostname);
	if($maincf->GET("DisabledInstance")==1){return;}
	$postmap=$unix->find_program("postmap");
	echo "Starting......: Postfix \"$hostname\" IP: $maincf->ip_addr\n";
	
	$maincf->buildconf();	
	$maincf->buildmaster();
	aiguilleuse($hostname);
	
	if(!is_file("/etc/postfix-$hostname/relay_domains_restricted.db")){
		@file_put_contents("/etc/postfix-$hostname/relay_domains_restricted", "\n");
		shell_exec("$postmap hash:/etc/postfix-$hostname/relay_domains_restricted");
	}
	
	
	writelogs("Building configuration done",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1");
	
	//shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p start");	
	_start_instance($hostname);
	
	
}


function reconfigure_instance_tmpfs($hostname,$mem){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".".$hostname.".pid";
	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Starting......: multi-instances configurator already executed PID ". @file_get_contents($pidfile)."\n";
		die();
	}

	$pid=getmypid();
	echo "Starting......: Postfix multi-instances configurator running $pid\n";
	file_put_contents($pidfile,$pid);		
	
	if(!is_numeric($mem)){
		echo "Starting......: Postfix multi-instances Memory set \"$mem\" is not an integer\n";
		return;
	}
	if($mem<5){return null;}
	$directory="/var/spool/postfix-$hostname";
	if($hostname=="master"){$directory="/var/spool/postfix";}
		
	$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
	if($MOUNTED_TMPFS_MEM>0){
		echo "Starting......: Postfix \"$hostname\" mounted memory $mem/{$MOUNTED_TMPFS_MEM}MB\n";
		if($mem>$MOUNTED_TMPFS_MEM){$diff=$mem-$MOUNTED_TMPFS_MEM;}
		if($mem<$MOUNTED_TMPFS_MEM){$diff=$MOUNTED_TMPFS_MEM-$mem;}
		if($diff>20){
			echo "Starting......: Postfix \"$hostname\" diff={$diff}M\"\n"; 
			reconfigure_instance_tmpfs_umount($hostname);
			reconfigure_instance_tmpfs_mount($hostname,$mem);
		}
		
	}else{
		echo "Starting......: Postfix \"$hostname\" directory is not mounted has tmpfs\n";
		reconfigure_instance_tmpfs_mount($hostname,$mem);
		
	}
	
	@unlink($pidfile);

}

function reconfigure_instance_tmpfs_mount($hostname,$mem){
		$unix=new unix();
		$directory="/var/spool/postfix-$hostname";
		if($hostname=="master"){$directory="/var/spool/postfix";}
		
		
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			echo "Starting......: Postfix \"$hostname\" Already mounted\n";
			return;
		}
		
		
		$mount=$unix->find_program("mount");
		@mkdir("/var/spool/backup/postfix-$hostname",0755,true);
		echo "Starting......: Postfix \"$hostname\" backup $directory\n";
		shell_exec("/bin/cp -pr $directory/* /var/spool/backup/postfix-$hostname/");
		shell_exec("/bin/rm -rf $directory/*");
		echo "Starting......: Postfix \"$hostname\" mounting $directory\n";
		$cmd="$mount -t tmpfs -o size={$mem}M tmpfs \"$directory\"";
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec("$cmd");
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			echo "Starting......: Postfix \"$hostname\" mounted memory $mem/{$MOUNTED_TMPFS_MEM}MB\n";	
		}else{
			echo "Starting......: Postfix \"$hostname\" mounted memory FAILED\n";
				
		}	
		
	shell_exec("/bin/cp -pr /var/spool/backup/postfix-$hostname/* $directory/");
	shell_exec("/bin/rm -rf /var/spool/backup/postfix-$hostname");					
	
}

function reconfigure_instance_tmpfs_umount($hostname){
		$directory="/var/spool/postfix-$hostname";
		if($hostname=="master"){$directory="/var/spool/postfix";}
		$results=array();
		$unix=new unix();
		$umount=$unix->find_program("umount");
		if($GLOBALS["UMOUNT_COUNT"]==0){
			@mkdir("/var/spool/backup/postfix-$hostname",0755,true);
			echo "Starting......: Postfix \"$hostname\" backup files and directories.\n";
			shell_exec("/bin/cp -pr $directory/* /var/spool/backup/postfix-$hostname/ >/dev/null 2>&1");
			shell_exec("/bin/rm -rf $directory/*");
		}
		
		echo "Starting......: Postfix \"$hostname\" stopping postfix\n";
		$cmd="{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1";
		if($hostname=="master"){$cmd="{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1";}
		
		shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop >/dev/null 2>&1");
		
		$pids=trim(@implode(" ",$unix->LSOF_PIDS($directory)));
		if(strlen($pids)>2){
			echo "Starting......: Postfix \"$hostname\" kill processes $pids\n";
			shell_exec("/bin/kill -9 $pids >/dev/null 2>&1");
		}
		
		
		$cmd="$umount -l \"$directory\"";
		
		
		if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
		exec("$cmd 2>&1",$results);
		while (list ($num, $ligne) = each ($results) ){
			echo "Starting......: Postfix \"$hostname\" $umount: $ligne\n"; 
		}
		
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM==0){
			echo "Starting......: Postfix \"$hostname\" umounted memory {$MOUNTED_TMPFS_MEM}MB\n";	
			
		}else{
			echo "Starting......: Postfix \"$hostname\" failed to umount {$GLOBALS["UMOUNT_COUNT"]}/10\n";
			$GLOBALS["UMOUNT_COUNT"]=$GLOBALS["UMOUNT_COUNT"]+1;
			if($GLOBALS["UMOUNT_COUNT"]<20){
				reconfigure_instance_tmpfs_umount($hostname);
				return;
			}else{
				echo "Starting......: Postfix \"$hostname\" timeout\n";
				shell_exec("/bin/cp -pr /var/spool/backup/postfix-$hostname/* $directory/ >/dev/null 2>&1");
				shell_exec("/bin/rm -rf /var/spool/backup/postfix-$hostname");
				return;	
			}
		}
}

function reconfigure_instance_relayhost($hostname){
	if($hostname=="master"){return;}
	$maincf=new maincf_multi($hostname);
	$maincf->buildconf();	
	$maincf->CheckDirectories($hostname);
	
	_start_instance($hostname);
}


	

function reconfigure_instance_ssl($hostname){
	if($hostname=="master"){return;}
	$maincf=new maincf_multi($hostname);
	$maincf->certificate_generate();
	$maincf->buildconf();	
	$maincf->buildmaster();
	echo "Starting......: restarting Postfix {$GLOBALS["postmulti"]} -i postfix-$hostname -p stop\n";		
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop");
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p start");
	
}

function reconfigure_instance_minimal($hostname){
	if($hostname=="master"){return;}
	$maincf=new maincf_multi($hostname);
	$maincf->buildconf();	
	$maincf->buildmaster();
	echo "Starting......: Postfix {$GLOBALS["postmulti"]} -i postfix-$hostname -p reload\n";		
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p reload");		
}
function reconfigure_instance_mastercf($hostname){
	if($hostname=="master"){return;}
	$maincf=new maincf_multi($hostname);
	$maincf->buildmaster();
	$sock=new sockets();
	echo "Starting......: restarting Postfix {$GLOBALS["postmulti"]} -i postfix-$hostname -p stop\n";		
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p stop");
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p start");	
	$sock->getFrameWork("cmd.php?amavis-restart=yes");
}


function ConfigureMainCF($hostname,$nostart=false){
	if($hostname=="master"){return;}	
	if(strlen(trim($hostname))<3){return null;}
	$users=new usersMenus();
	$unix=new unix();
	echo "Starting......: Postfix \"$hostname\" checking instance\n";
	

	
	$instance_path="/etc/postfix-$hostname";
	if(!is_dir($instance_path)){@mkdir("$instance_path",0755,true);}
	if(!is_file("$instance_path/main.cf")){@file_put_contents("$instance_path/main.cf", "\n");}
	
	if(!is_file("$instance_path/dynamicmaps.cf")){
		echo "Starting......: Postfix $hostname creating dynamicmaps.cf\n";
		@file_put_contents("$instance_path/dynamicmaps.cf","#");
	}
	
	
	$maincf=new maincf_multi($hostname);
	reconfigure_instance_mime_checks($hostname);
	aiguilleuse($hostname);
	$maincf->buildconf();
	$assp=new assp_multi($maincf->ou);
	if($assp->AsspEnabled==1){
		shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.assp-multi.php --org \"$maincf->ou\"");
	}
	
	echo "Starting......: Postfix $hostname enable it into the Postfix main system\n";
	shell_exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -e enable >/dev/null 2>&1");
	if(!$nostart){_start_instance($hostname);}
}

function isInstanceRunning($hostname){
	if($hostname=="master"){return;}
	$pidfile="/var/spool/postfix-$hostname/pid/master.pid";
	$unix=new unix();	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return true;}
	return false;	
	
}

function _start_instance($hostname){
	if($hostname=="master"){return;}
	if(trim($hostname)==null){return;}
	$unix=new unix();
	$main=new maincf_multi($hostname);
	$PostFixEnableQueueInMemory=$main->GET("PostFixEnableQueueInMemory");
	$PostFixQueueInMemory=$main->GET("PostFixQueueInMemory");
	$directory="/var/spool/postfix-$hostname";
	$postfixbin=$unix->find_program("postfix");
	if($PostFixEnableQueueInMemory==1){
		reconfigure_instance_tmpfs($hostname,$PostFixQueueInMemory);
	}else{
		$MOUNTED_TMPFS_MEM=$unix->MOUNTED_TMPFS_MEM($directory);
		if($MOUNTED_TMPFS_MEM>0){
			reconfigure_instance_tmpfs_umount($hostname);
		}
	}
	
	if(!is_file("/etc/postfix-$hostname/main.cf")){
		echo "Starting......: Postfix \"$hostname\" /etc/postfix-$hostname/main.cf no such file (reconfigure)\n";
		ConfigureMainCF($hostname,true);
	}	
	
	
	$pidfile="/var/spool/postfix-$hostname/pid/master.pid";
	
	if($GLOBALS["postmulti"]==null){$GLOBALS["postmulti"]=$unix->find_program("postmulti");}
	$pid=$unix->get_pid_from_file($pidfile);
	
	writelogs("$hostname:: Checking directories IP address=$main->ip_addr",__FUNCTION__,__FILE__,__LINE__);
	$main->CheckDirectories($hostname);
	writelogs("$hostname:: $pidfile=$pid",__FUNCTION__,__FILE__,__LINE__);
	
	if($unix->process_exists($pid)){
		echo "Starting......: Postfix \"$hostname\" reloading\n";
		writelogs("$hostname::reloading postfix {$GLOBALS["postmulti"]} -i postfix-$hostname -p reload",__FUNCTION__,__FILE__,__LINE__);
		exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p reload 2>&1",$results);
		while (list ($num, $line) = each ($results) ){
			writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
			echo "Starting......: Postfix \"$hostname\" $line\n";
			
			if(preg_match("#fatal: open /etc/postfix-(.+?)\/main\.cf#",$line,$re)){
				echo "Starting......: Postfix reconfigure \"{$re[1]}\"\n";
				reconfigure_instance($re[1]);
			}
			
		}
		
		return;
	}
	
	
	
	echo "Starting......: Postfix starting \"$hostname\"\n";
	writelogs("$hostname::Starting postfix {$GLOBALS["postmulti"]} -i postfix-$hostname -p start",__FUNCTION__,__FILE__,__LINE__);
	exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p start 2>&1",$results);
	writelogs("$hostname::Starting LOG=".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	
		while (list ($num, $line) = each ($results) ){
			if(preg_match("#unused parameter:#", $line)){continue;}
			writelogs("$line",__FUNCTION__,__FILE__,__LINE__);
			echo "Starting......: Postfix \"$hostname\" $line\n";
			if(preg_match("#fatal: open /etc/postfix-(.+?)\/main\.cf#",$line,$re)){
				echo "Starting......: Postfix reconfigure \"{$re[1]}\"\n";
				reconfigure_instance($re[1]);
			}			
	}

	
	$pid=$unix->get_pid_from_file($pidfile);
	for($i=0;$i<10;$i++){
		if($GLOBALS["VERBOSE"]){echo "Starting......: Postfix \"$hostname\" DEBUG open \"$pidfile\"\n";}
		if($unix->process_exists($pid)){break;}
		echo "Starting......: Postfix \"$hostname\" waiting run ($pid)\n";
		sleep(1);
		$pid=$unix->get_pid_from_file($pidfile);
	}
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		echo "Starting......: Postfix \"$hostname\" SUCCESS with PID=$pid\n";
		writelogs("$hostname::DONE",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	echo "Starting......: Postfix \"$hostname\" FAILED\n";
	writelogs("$hostname::FAILED",__FUNCTION__,__FILE__,__LINE__);
	
	
	
	
}


function ConfigureMainMaster(){
	$main=new main_cf();
	$main->save_conf_to_server(1);
	if(!is_file("/etc/postfix/hash_files/header_checks.cf")){@file_put_contents("/etc/postfix/hash_files/header_checks.cf","#");}
	file_put_contents('/etc/postfix/main.cf',$main->main_cf_datas);
	$unix=new unix();
	$postfix=$unix->find_program("postfix");
	shell_exec("$postfix reload");
	}
	
function DestroyInstance($instance){
		echo "Starting......: Postfix destroy \"$instance\"\n";
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -p stop");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e disable");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e destroy");	
	
}
	
function PostfixMultiDisable(){
	InstancesList();
	
	while (list ($instance, $ou) = each ($GLOBALS["INSTANCE"]) ){
		if($instance==null){continue;}
		if($instance=="-"){continue;}
		echo "Starting......: Postfix destroy \"$instance\"\n";
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -p stop");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e disable");
		shell_exec("{$GLOBALS["postmulti"]} -i $instance -e destroy");
	}
	
	$unix=new unix();
	$unix->POSTCONF_SET("multi_instance_enable","no");
	$unix->POSTCONF_SET("inet_interfaces","all");
	$unix->POSTCONF_SET("multi_instance_directories","");
	system(LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.postfix.maincf.php --reconfigure");
	
	
}

function remove_old_instances(){
	
		$sql="SELECT `value` FROM postfix_multi WHERE `key`='myhostname' GROUP BY `value`";
		$restart=false;
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
			$array[$ligne["value"]]=true;
		}
	
	
	foreach (glob("/etc/postfix-*",GLOB_ONLYDIR) as $dirname) {
		if(preg_match("#postfix-(.+)#",$dirname,$re)){
			$hostname=trim($re[1]);
			if($hostname==null){continue;}
			if($hostname=="hub"){continue;}
			if($hostname=="master"){continue;}
			if(!$array[$hostname]){
				$restart=true;
				echo "Starting......: Postfix remove old instance $hostname\n";
				shell_exec("/bin/rm -rf /etc/postfix-$hostname");
				shell_exec("/bin/rm -rf /var/lib/postfix-$hostname");
				shell_exec("/bin/rm -rf /var/spool/postfix-$hostname");
			}
				
		}
	
	}
	
	if($restart){shell_exec("/etc/init.d/artica-postfix stop postfix");}
	
}


function reconfigure_instance_mime_checks($hostname){
	if($hostname=="master"){return;}
	echo "Starting......: Postfix \"$hostname\" check mime_checks\n";
	$users=new usersMenus();
	$f=array();
	$unix=new unix();
	if($GLOBALS["postconf"]==null){$GLOBALS["postconf"]=$unix->find_program("postconf");}
	if($GLOBALS["postmulti"]==null){$GLOBALS["postmulti"]=$unix->find_program("postmulti");}	
	
	if($users->AMAVIS_INSTALLED){
		$main=new maincf_multi($hostname);
		$array_filters=unserialize(base64_decode($main->GET_BIGDATA("PluginsEnabled")));
		if($array_filters["APP_AMAVIS"]==1){
			@unlink("/etc/postfix-$hostname/mime_header_checks");
			
			shell_exec("{$GLOBALS["postconf"]} -c \"/etc/postfix-$hostname\" -e \"mime_header_checks = \"");
			system("/usr/share/artica-postfix/bin/artica-install --amavis-reload");
			_start_instance($hostname);
			return;
		}
	}
	
	
	
	
	$sql="SELECT * FROM smtp_attachments_blocking WHERE ou='{$_GET["ou"]}' AND hostname='$hostname' ORDER BY IncludeByName";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["IncludeByName"]==null){continue;}
			$f[]=$ligne["IncludeByName"];
		
	}
	if(count($f)==0){
		@unlink("/etc/postfix-$hostname/mime_header_checks");
		shell_exec("{$GLOBALS["postconf"]} -c \"/etc/postfix-$hostname\" -e \"mime_header_checks = \"");
		_start_instance($hostname);
		return;
	}
	
	$strings=implode("|",$f);
	echo "Starting......: Postfix \"$hostname\" ". count($f)." extensions blocked\n";
	$pattern[]="/^\s*Content-(Disposition|Type).*name\s*=\s*\"?(.+\.($strings))\"?\s*$/\tREJECT file attachment types is not allowed. File \"$2\" has the unacceptable extension \"$3\"";	
	$pattern[]="";
	@file_put_contents("/etc/postfix-$hostname/mime_header_checks",implode("\n",$pattern));	
	shell_exec("{$GLOBALS["postconf"]} -c \"/etc/postfix-$hostname\" -e \"mime_header_checks = regexp:/etc/postfix-$hostname/mime_header_checks\"");
	
}

function aiguilleuse($hostname){
	$maincf=new maincf_multi($hostname);
	$PostFixEnableAiguilleuse=$maincf->GET("PostFixEnableAiguilleuse");
	if($PostFixEnableAiguilleuse<>1){return;}
	if(!is_dir("/etc/postfix-$hostname")){@mkdir("/etc/postfix-$hostname",0755,true);}
	echo "Starting......: Postfix \"$hostname\" save internal-routed parameters\n";
	@file_put_contents("/etc/postfix-$hostname/aiguilleur.db",
	base64_decode($maincf->GET_BIGDATA("PostFixAiguilleuseServers")));
	
}

function postscreen($hostname){
	$user=new usersMenus();
	if(!$user->POSTSCREEN_INSTALLED){echo "Starting......: $hostname PostScreen is not installed, you should upgrade to 2.8 postfix version\n";return;}
	$maincf=new maincf_multi($hostname);
	$maincf->buildconf();
	_start_instance($hostname);
	
	
}

function build_all_aliases(){
	
MysqlInstancesList();
		if(!is_array($GLOBALS["INSTANCES_LIST"])){
			echo "Starting......: Postfix No instances, aborting\n";
			return;
			
		}

		reset($GLOBALS["INSTANCES_LIST"]);
		while (list ($num, $ligne) = each ($GLOBALS["INSTANCES_LIST"]) ){
				$hostname=$ligne;
				echo "Starting......: Postfix \"$hostname\" checking aliases\n";
				$maincf=new maincf_multi($hostname);
				$maincf->buildconf();
				$results=array();
				exec("{$GLOBALS["postmulti"]} -i postfix-$hostname -p reload 2>&1",$results);
				while (list ($a, $b) = each ($results) ){echo "Starting......: Postfix \"$hostname\" $b\n";}
		}
			
	
	
}







?>