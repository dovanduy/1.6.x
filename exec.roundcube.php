<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.roundcube.inc');
include_once(dirname(__FILE__) . '/ressources/class.apache.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$bd="roundcubemail";
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["MYSQL_DB"]=$bd;	
$GLOBALS["SERVICE_NAME"]="RoundCube Web";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--sieverules"){plugin_sieverules();die();}
if($argv[1]=="--calendar"){plugin_calendar();die();}
if($argv[1]=="--database"){check_databases($bd);die();}
if($argv[1]=="--contextmenu"){plugin_contextmenu();die();}
if($argv[1]=="--build"){build();die();}
if($argv[1]=="--addressbook"){plugin_globaladdressbook();die();}
if($argv[1]=="--verifyTables"){verifyTables();die();}
if($argv[1]=="--hacks"){RoundCubeHacks();die();}
if($argv[1]=="--tableslist"){RoundCubeMysqlTablesList();die();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}


popuplate();


function popuplate(){
	
	$unix=new unix();
	
	if(!is_dir($unix->LOCATE_ROUNDCUBE_WEBFOLDER())){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Not installed\n";}
		die();
	}

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already pid running $pid\n";}
		die();
	}
	
	$timExc=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){
		if($timExc<120){return;}
		@unlink($pidTime);
		@file_put_contents($pidTime, time());
	}
	
	$pid=getmypid();
	@file_put_contents($pidfile,$pid);

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Get user list....\n";}
	
	
	$ldap=new clladp();
	$GLOBALS["LDAP_USERS"]=$ldap->Hash_GetALLUsers();
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: ". count($GLOBALS["LDAP_USERS"])." user(s) to scan\n";}
	
	
	if(!is_array($GLOBALS["LDAP_USERS"])){
		writelogs("No users stored in local database, aborting ","MAIN",__FILE__,__LINE__);
		die();
	}	
	popuplate_db();
	
	$q=new mysql();
	$sql="SELECT mysql_database FROM freeweb WHERE groupware='ROUNDCUBE'";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		popuplate_db($ligne["mysql_database"]);
	}
	
	
}

function popuplate_db($database=null){
	$q=new roundcube();
	if($database==null){$database=$q->database;}
	if(isset($GLOBALS["Populated$database"])){return;}
	$GLOBALS["Populated$database"]=true;
	$users=$GLOBALS["LDAP_USERS"];
	$unix=new unix();
	$mailhost=$unix->hostname_g();
	




$count=0;
while (list ($num, $val) = each ($users) ){
		usleep(400000);
		$user_id=GetidFromUser($database,$num);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: user \"$num\" $val user_id=$user_id\n";}
		$sql="UPDATE identities SET `email`='$val', `reply-to`='$val' WHERE name='$num';";
		echo $sql."\n";
		$q->QUERY_SQL($sql);	
		if(!$q->ok){echo "$sql \n$q->mysql_error\n";}	
		
		if($user_id==0){
			CreateRoundCubeUser($database,$num,$val,'127.0.0.1');
			$user_id=GetidFromUser($database,$num);
		}
		
		if($user_id==0){continue;}
		$identity_id=GetidentityFromuser_id($database,$user_id);
		if($identity_id==0){
			CreateRoundCubeIdentity($database,$user_id,$num,$val);
			$identity_id=GetidentityFromuser_id($database,$user_id);
			}
		
		if($identity_id==0){continue;}
		
		$count=$count+1;
		UpdateRoundCubeIdentity($database,$identity_id,$val);
}

echo "\n\nsuccess ".$count." user(s) updated\n";
}


function GetidFromUser($bd,$uid){
	$sql="SELECT user_id FROM users where username='$uid'";
	$q=new roundcube($bd);
	$userid=array();
	$results=$q->QUERY_SQL($sql,$bd);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$userid[]=$ligne["user_id"];
	}
	
	if(count($userid)==0){return 0;}else{return $userid[0];}
			
	
	
}



function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	@mkdir("/var/run/lighttpd",0755,true);
	$roundcube=new roundcube();
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Building MySQL Path $roundcube->root_path\n";}
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Building MySQL config\n";}
	$roundcube->db_inc_php();	
	@file_put_contents("$roundcube->root_path/config/main.inc.php", $roundcube->RoundCubeConfig());   
	verifyTables();
	start(true);
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=LIGHTTPD_PID();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
		return;
	}
	$pid=LIGHTTPD_PID();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=LIGHTTPD_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=LIGHTTPD_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$ROUNDCUBE_MAIN_FOLDER=ROUNDCUBE_MAIN_FOLDER();
	if(!is_dir(ROUNDCUBE_MAIN_FOLDER())){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	$RoundCubeHTTPEngineEnabled=intval($sock->GET_INFO("RoundCubeHTTPEngineEnabled"));
	
	
	$pid=LIGHTTPD_PID();
	if($RoundCubeHTTPEngineEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} disabled (RoundCubeHTTPEngineEnabled)..\n";}
		if($unix->process_exists($pid)){stop(true);}
		return;
	}
	
	
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	$RoundCubeHTTPSPort=intval($sock->GET_INFO("RoundCubeHTTPSPort"));
	$RoundCubeHTTPPort=intval($sock->GET_INFO("RoundCubeHTTPPort"));
	$RoundCubeUseSSL=intval($sock->GET_INFO("RoundCubeUseSSL"));
	
	if($RoundCubeHTTPSPort==0){$RoundCubeHTTPSPort=449;}
	if($RoundCubeHTTPPort==0){$RoundCubeHTTPPort=8888;}
	
	
	if(!is_file("/opt/artica/ssl/certs/lighttpd.pem")){
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} creating SSL certificate..\n";}
		exec("/usr/share/artica-postfix/bin/artica-install -lighttpd-cert 2>&1",$results);
		while (list ($num, $line) = each ($results) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $line\n";}
		}
	}
	
	apache_config();
	if($RoundCubeUseSSL==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Get PID from PORT HTTPS/TCP:$RoundCubeHTTPSPort\n";}
		$pids=$unix->PIDOF_BY_PORT($RoundCubeHTTPSPort);
		if(count($pids)>0){
			while (list ($pid, $line) = each ($pids) ){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill PID $pid that listens $RoundCubeHTTPSPort\n";} 
				$unix->KILL_PROCESS($pid,9);
			}
		}
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Get PID from PORT HTTP/TCP:$RoundCubeHTTPPort\n";}
	$pids=$unix->PIDOF_BY_PORT($RoundCubeHTTPPort);
	if(count($pids)>0){
		while (list ($pid, $line) = each ($pids) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} kill PID $pid that listens $RoundCubeHTTPPort\n";}
			$unix->KILL_PROCESS($pid,9);
		}
	}
	
	$cmd="$apache2ctl -f $LIGHTTPD_CONF_PATH -k start";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	
	
	
	
	for($i=0;$i<6;$i++){
		$pid=LIGHTTPD_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/6...\n";}
		sleep(1);
	}
	
	$pid=LIGHTTPD_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}

		
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}
	
	
	
}

function ROUNDCUBE_MAIN_FOLDER(){
	
	$f[]="/usr/share/roundcubemail/index.php";
	$f[]="/usr/share/roundcube/index.php";
	$f[]="/var/lib/roundcube/index.php";
	while (list ($num, $filename) = each ($f) ){
		if(is_file($filename)){return dirname($filename);}
		
	}	

}


function LIGHTTPD_CONF_PATH(){
	return "/etc/artica-postfix/apache-roundcube.conf";
}


function LIGHTTPD_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/roundcube-apache/apache.pid');
	if($unix->process_exists($pid)){return $pid;}
	$apache2ctl=$unix->LOCATE_APACHE_CTL();
	return $unix->PIDOF_PATTERN($apache2ctl." -f.*?apache-roundcube.conf");
}
function build(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	if(is_dir("/usr/share/roundcube/plugins/remember_me")){shell_exec("$rm -rf /usr/share/roundcube/plugins/remember_me");}
	
	
	$r=new roundcube();
	$conf=$r->RoundCubeConfig();
	if(is_file("/var/log/lighttpd/roundcube-access.log")){@unlink("/var/log/lighttpd/roundcube-access.log");}
	if(is_file("/var/log/lighttpd/roundcube-error.log")){@unlink("/var/log/lighttpd/roundcube-error.log");}
	$users=new usersMenus();
	$roundcube_folder=$users->roundcube_folder;
	if(!@file_put_contents("$roundcube_folder/config/main.inc.php",$conf)){
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Saving main.inc.php failed. [".__LINE__."]\n";}
	}else{
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Saving main.inc.php Success.[".__LINE__."]\n";}
	}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Building main configuration done.[".__LINE__."]\n";}
	RoundCubeHacks();
}


function CreateRoundCubeUser($bd,$user_id,$email,$mailhost){
	$date=date('Y-m-d H:i:s');
	$sql="INSERT INTO `users` (`username`, `mail_host`, `language`,`created`) VALUES 
	('$user_id','127.0.0.1','en_US','$date');
	";
	$q=new roundcube($bd);
	$q->QUERY_SQL($sql,$bd);
	if(!$q->ok){
		echo $q->mysql_error."\n";
	}
	
}

function CreateRoundCubeIdentity($bd,$user_id,$num,$val){
	$sql="INSERT INTO `identities` (`user_id`, `del`, `standard`, `name`, `organization`, `email`, `reply-to`) VALUES ('$user_id','0','1','$num','','$val','$val');";
	$q=new roundcube($bd);
	$q->QUERY_SQL($sql,$bd);
}


function GetidentityFromuser_id($bd,$user_id){
	$id=array();
	$sql="SELECT identity_id FROM identities where user_id='$user_id'";
	$q=new roundcube($bd);
	$results=$q->QUERY_SQL($sql,$bd);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$id[]=$ligne["identity_id"];
	}
	
	if(count($id)==0){return 0;}else{return $id[0];}
}

function UpdateRoundCubeIdentity($bd,$identity_id,$val){
	echo "Update $identity_id to $val\n";
	$sql="UPDATE identities SET email='$val', `reply-to`='$val' WHERE identity_id='$identity_id'";
	$q=new roundcube($bd);
	$q->QUERY_SQL($sql,$bd);
	
}

function plugin_sieverules(){
	$users=new usersMenus();
	if(!$users->roundcube_installed){
		writelogs("RoundCube is not installed",__FUNCTION__,__FILE__,__LINE__);
		return ;
	}
	
	$dir=$users->roundcube_folder."/plugins";
	if(!is_dir($dir)){
		writelogs("Unable to stat directory '$dir'",__FUNCTION__,__FILE__,__LINE__);
		return ;		
	}
	writelogs("Roundcube plugins: $dir",__FUNCTION__,__FILE__,__LINE__);
	
	writelogs("remove $dir/sieverules",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/bin/rm -rf $dir/sieverules >/dev/null 2>&1");
	writelogs("Installing in $dir/sieverules",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("$dir/sieverules",0755,true);
	shell_exec("/bin/cp -rf /usr/share/artica-postfix/bin/install/roundcube/sieverules/* $dir/sieverules/");
	shell_exec("/bin/chmod -R 755 $dir/sieverules");
	writelogs("Installing in $dir/sieverules done...",__FUNCTION__,__FILE__,__LINE__);
	
	///usr/share/roundcube/plugins
	
	
}

function plugin_contextmenu(){
	$users=new usersMenus();
	if(!$users->roundcube_installed){
		writelogs("RoundCube is not installed",__FUNCTION__,__FILE__,__LINE__);
		return ;
	}
	
	$dir=$users->roundcube_folder."/plugins";
	if(!is_dir($dir)){
		writelogs("Unable to stat directory '$dir'",__FUNCTION__,__FILE__,__LINE__);
		return ;		
	}
	writelogs("Roundcube plugins: $dir",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file("$dir/contextmenu/contextmenu.php")){
		writelogs("Installing in $dir/contextmenu",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$dir/contextmenu",0755,true);
		shell_exec("/bin/cp -rf /usr/share/artica-postfix/bin/install/roundcube/contextmenu/* $dir/contextmenu/");
	}
	

	shell_exec("/bin/chmod -R 755 $dir/contextmenu");
	writelogs("Installing in $dir/contextmenu done...",__FUNCTION__,__FILE__,__LINE__);
	
	///usr/share/roundcube/plugins
}


function plugin_globaladdressbook(){
	include_once(dirname(__FILE__) . '/ressources/class.apache.inc');
	$users=new usersMenus();
	if(!$users->roundcube_installed){
		writelogs("RoundCube is not installed",__FUNCTION__,__FILE__,__LINE__);
		return ;
	}
	
	$dir=$users->roundcube_folder."/plugins";
	if(!is_dir($dir)){
		writelogs("Unable to stat directory '$dir'",__FUNCTION__,__FILE__,__LINE__);
		return ;		
	}
	writelogs("Roundcube plugins: $dir",__FUNCTION__,__FILE__,__LINE__);
	if(!is_file("$dir/globaladdressbook/globaladdressbook.php")){
		writelogs("Installing in $dir/globaladdressbook",__FUNCTION__,__FILE__,__LINE__);
		@mkdir("$dir/globaladdressbook",0755,true);
		shell_exec("/bin/cp -rf /usr/share/artica-postfix/bin/install/roundcube/globaladdressbook/* $dir/globaladdressbook/");
		
	}
	

	
	$r=new roundcube_globaladdressbook("MAIN_INSTANCE");
	$config=$r->BuildConfig();
	@file_put_contents("$dir/globaladdressbook/config.inc.php",$config);
	$q=new mysql();
	$q->checkRoundCubeTables($GLOBALS["MYSQL_DB"]);
	
	shell_exec("/bin/chmod -R 755 $dir/globaladdressbook");
	shell_exec("/bin/chmod -R 770 $dir/plugins/globaladdressbook");
	shell_exec("/bin/chmod 660 $dir/plugins/globaladdressbook/*.php");
	
	writelogs("Installing in $dir/globaladdressbook done...",__FUNCTION__,__FILE__,__LINE__);	
	plugin_contextmenu();

	
	
}


function plugin_calendar(){
	$users=new usersMenus();
	if(!$users->roundcube_installed){
		writelogs("RoundCube is not installed",__FUNCTION__,__FILE__,__LINE__);
		return ;
	}
	
	$dir=$users->roundcube_folder."/plugins";
	if(!is_dir($dir)){
		writelogs("Unable to stat directory '$dir'",__FUNCTION__,__FILE__,__LINE__);
		return ;		
	}
	writelogs("Roundcube plugins: $dir",__FUNCTION__,__FILE__,__LINE__);
	
	writelogs("remove $dir/sieverules",__FUNCTION__,__FILE__,__LINE__);
	shell_exec("/bin/rm -rf $dir/calendar >/dev/null 2>&1");
	writelogs("Installing in $dir/calendar",__FUNCTION__,__FILE__,__LINE__);
	@mkdir("$dir/calendar",0755,true);
	shell_exec("/bin/cp -rf /usr/share/artica-postfix/bin/install/roundcube/calendar/* $dir/calendar/");
	shell_exec("/bin/chmod -R 755 $dir/calendar");
	
	
	$sql="CREATE TABLE `events` (
  `event_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `start` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `end` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `summary` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(255) NOT NULL DEFAULT '',
  `all_day` smallint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY(`event_id`),
  CONSTRAINT `user_id_fk_events` FOREIGN KEY (`user_id`)
    REFERENCES `users`(`user_id`)
    /*!40008
      ON DELETE CASCADE
      ON UPDATE CASCADE */
)";
	
	$q=new mysql();
	$q->QUERY_SQL($sql,$GLOBALS["MYSQL_DB"]);
	
	writelogs("Installing in $dir/calendar done...",__FUNCTION__,__FILE__,__LINE__);
	
	///usr/share/roundcube/plugins
	
	
}

function check_databases($bd){
	include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
	if(systemMaxOverloaded()){die();}
	$q=new mysql();
	$q->checkRoundCubeTables($bd);
	
}

function verifyTables(){
	$mysqlfile="/usr/share/roundcube/SQL/mysql.initial.sql";
	$mysqlupdatefile="/usr/share/roundcube/SQL/mysql.update.sql";
	if(!is_file($mysqlfile)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [$roundcube->database] $mysqlfile no such file !!\n";}
		return null;
	}
	$q=new mysql();
	$users=new usersMenus();
	$f=RoundCubeMysqlTablesList();
	
	$unix=new unix();
	$mysqlbin=$unix->find_program("mysql");
	$roundcube=new roundcube();
	
	$cmdline="$mysqlbin ". $roundcube->MYSQL_CMDLINES;
	
	
	$verif=true;
	while (list ($num, $table) = each ($f) ){
		if(!$roundcube->TABLE_EXISTS($table,$GLOBALS["MYSQL_DB"])){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [$roundcube->database] \"$table\" no such table\n";}
			$verif=false;
		}
	}
	
if(!$verif){
	$initial=$cmdline." $roundcube->database < ". $mysqlfile;
	shell_exec($initial);
	if(is_file($mysqlupdatefile)){
		$update=$cmdline." $roundcube->database < $mysqlupdatefile";
		shell_exec($update);
	}
	return;
}	

	unset($f);
	$f[]="contactgroupmembers";
	$f[]="contactgroups";
	$verif=true;
	$roundcube=new roundcube();
	while (list ($num, $table) = each ($f) ){
		if(!$roundcube->TABLE_EXISTS($table)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} [$roundcube->database] \"$table\" no such table\n";}
			$verif=false;
		}
	}	
	
if(!$verif){
	$update=$cmdline." $roundcube->database < $mysqlupdatefile";
	$roundcube->QUERY_SQL($update,$GLOBALS["MYSQL_DB"]);
	shell_exec($update);
	if($GLOBALS["VERBOSE"]){echo "$update\n";}
	return;
}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} DB: [$roundcube->database]: All are ok, nothing to do...\n";}
	
}


function RoundCubeHacks(){
	$sock=new sockets();
	$unix=new unix();
	$unix->IPTABLES_DELETE_REGEX_ENTRIES("RoundCubeHacks");
	$RoundCubeHackEnabled=$sock->GET_INFO("RoundCubeHackEnabled");
	if($RoundCubeHackEnabled==null){$RoundCubeHackEnabled=1;}
	if($RoundCubeHackEnabled==0){echo "Starting......: ".date("H:i:s")." Roundcube anti-hack is disabled\n";return;}
	$RoundCubeHackConfig=unserialize(base64_decode($sock->GET_INFO("RoundCubeHackConfig")));
	if(!is_array($RoundCubeHackConfig)){if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: Not an array::RoundCubeHackConfig\n";}return;}
	if(count($RoundCubeHackConfig)==0){if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: O rows\n";}return;}
	
		
	if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: array of ". count($RoundCubeHackConfig)." rows\n";}
	while (list ($instance, $conf) = each ($RoundCubeHackConfig) ){
		if(!is_array($conf)){continue;}
		while (list ($ip, $enabled) = each ($conf) ){
			if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: instance $instance [$ip] enabled=$enabled\n";}
			if(!$enabled){continue;}
			if($instance=="master"){$iptables[]=RoundCubeHacks_master($ip);continue;}
			$iptables[]=RoundCubeHacks_vhosts($instance,$ip);
			}
	}

	if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: array of ". count($iptables)." iptables commands\n";}
	if(count($iptables)==0){return;}
	
	$unix=new unix();
	$iptables_bin=$unix->find_program("iptables");
	if(!is_file($iptables_bin)){
		if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: no iptables installed, aborting\n";}
		return;
	}
	
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ". count($iptables)." iptables rule(s)\n";}
	while (list ($num, $cmd) = each ($iptables) ){
		$cmd="$iptables_bin $cmd";
		if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks:: $cmd\n";}
		shell_exec("$cmd >/dev/null 2>&1");
		
	}
	
	
	
}

function RoundCubeHacks_master($ip){
	if($GLOBALS["LIGHTTPD_PORT"]==null){
		$unix=new unix();
		$GLOBALS["LIGHTTPD_PORT"]=$unix->LIGHTTPD_PORT();
		$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]=$unix->LIGHTTPD_PORT("/etc/artica-postfix/apache-roundcube.conf");
		
		
	}
	
	if($GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]==null){
		$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]=intval($sock->GET_INFO("RoundCubeHTTPSPort"));
		if($GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]==0){$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]=449;}
	}
	
	if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks_master:: Artica port: {$GLOBALS["LIGHTTPD_PORT"]}: Roundcube instance port:{$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]} \n";}
	return "-A INPUT -s $ip -p tcp -m multiport --dport {$GLOBALS["LIGHTTPD_PORT"]},{$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]} -j DROP -m comment --comment \"RoundCubeHacks\"";
}

function RoundCubeHacks_vhosts($instance,$ip){
	
	if($GLOBALS["ApacheGroupWarePort"]==null){
		$sock=new sockets();
		$GLOBALS["ApacheGroupWarePort"]=$sock->GET_INFO("ApacheGroupWarePort");
	}
	if($GLOBALS["VERBOSE"]){echo "RoundCubeHacks_vhosts:: $instance port = {$GLOBALS["ApacheGroupWarePort"]}\n";}
	return "-A INPUT -s $ip -p tcp --dport {$GLOBALS["ApacheGroupWarePort"]} -j DROP -m comment --comment \"RoundCubeHacks\"";
	
	
}


function RoundCubeMysqlTablesList(){
	$mysqlfile="/usr/share/roundcube/SQL/mysql.initial.sql";
	
	if(!is_file($mysqlfile)){
		echo "$mysqlfile No such file";
		return;
	}
	
	$f=explode("\n", @file_get_contents($mysqlfile));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#CREATE TABLE[\s+|`]+(.+?)[\s+|`]#", $line,$re)){
			$array[]=$re[1];
		}
	}
	return $array;
	
}

	
function apache_config(){
		$sock=new sockets();
		$unix=new unix();
		$EnablePHPFPM=0;
		@mkdir("/var/run/apache2",0755,true);
		@mkdir("/var/run/artica-roundcube",0755,true);
		@mkdir("/var/run/roundcube-apache",0755,true);
		@mkdir("/var/log/apache2",0755,true);
		$APACHE_SRC_ACCOUNT=$unix->APACHE_SRC_ACCOUNT();
		$APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();
		$APACHE_MODULES_PATH=$unix->APACHE_MODULES_PATH();
		$pydio_installed=false;
		if(is_file(" /etc/php5/cli/conf.d/ming.ini")){@unlink(" /etc/php5/cli/conf.d/ming.ini");}
		@unlink("/var/log/apache2/roundcube-error.log");
		@touch("/var/log/apache2/roundcube-error.log");
		@chmod("/var/log/apache2/roundcube-error.log",0755);
		$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/log/apache2/*");
		$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/usr/share/artica-postfix/ressources/logs/*");
	
		
		$RoundCubeHTTPPort=8888;
		$RoundCubeHTTPSPort=449;
		$NoLDAPInLighttpdd=0;
		
	
		$RoundCubeHTTPSPort=intval($sock->GET_INFO("RoundCubeHTTPSPort"));
		$RoundCubeHTTPPort=intval($sock->GET_INFO("RoundCubeHTTPPort"));
		$RoundCubeUseSSL=intval($sock->GET_INFO("RoundCubeUseSSL"));
		
		$RoundCubeUploadMaxFilesize=intval($sock->GET_INFO("RoundCubeUploadMaxFilesize"));
		if($RoundCubeUploadMaxFilesize==0){$RoundCubeUploadMaxFilesize=128;}
		
		if($RoundCubeHTTPSPort==0){$RoundCubeHTTPSPort=449;}
		if($RoundCubeHTTPPort==0){$RoundCubeHTTPPort=8888;}
		
		$RoundCubeListenIP=$sock->GET_INFO("RoundCubeListenIP");
	
		$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
		$php=$unix->LOCATE_PHP5_BIN();
		$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
		if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
		if(!is_file($phpfpm)){$EnableArticaApachePHPFPM=0;}
	
		$EnablePHPFPM=intval($sock->GET_INFO("EnablePHPFPM"));
		if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
		if($EnablePHPFPM==0){$EnableArticaApachePHPFPM=0;}
	
	
		$unix->chown_func($APACHE_SRC_ACCOUNT, $APACHE_SRC_GROUP,"/var/run/artica-roundcube");
		$apache_LOCATE_MIME_TYPES=$unix->apache_LOCATE_MIME_TYPES();
		
		
		
	
		if($EnableArticaApachePHPFPM==1){
			if(!is_file("$APACHE_MODULES_PATH/mod_fastcgi.so")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} mod_fastcgi.so is required to use PHP5-FPM\n";}
				$EnableArticaApachePHPFPM=0;
			}
		}
		
		if($APACHE_SRC_ACCOUNT==null){
			$APACHE_SRC_ACCOUNT="www-data";
			$APACHE_SRC_GROUP="www-data";
			$unix->CreateUnixUser($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,"Apache username");
		}
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Run as $APACHE_SRC_ACCOUNT:$APACHE_SRC_GROUP\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM: $EnablePHPFPM\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP-FPM Enabled: $EnableArticaApachePHPFPM\n";}

	
	
		$open_basedir[]="/usr/share/artica-postfix";
		$open_basedir[]="/etc/artica-postfix";
		$open_basedir[]="/etc/artica-postfix/settings";
		$open_basedir[]="/var/log";
		$open_basedir[]="/var/run/mysqld";
		$open_basedir[]="/usr/share/php";
		$open_basedir[]="/usr/share/php5";
		$open_basedir[]="/var/lib/php5";
		$open_basedir[]="/var/lighttpd/upload";
		$open_basedir[]="/usr/share/artica-postfix/ressources";
		$open_basedir[]="/usr/share/artica-postfix/framework";
		$open_basedir[]="/etc/ssl/certs/mysql-client-download";
		$open_basedir[]="/var/run";
		$open_basedir[]="/bin";
		$open_basedir[]="/tmp";
		$open_basedir[]="/usr/sbin";
		$open_basedir[]="/home";
		
		$f[]="<Directory \"/usr/share/roundcube\">";
		$f[]="\tOptions Indexes FollowSymLinks";
		$f[]="\tphp_value open_basedir \"/usr/share/roundcube\"";
		$f[]="\tphp_admin_value upload_tmp_dir \"/usr/share/roundcube/uploads\"";
		$f[]="\tphp_flag register_globals off";
		$f[]="\tphp_flag magic_quotes_gpc off";
		$f[]="\tphp_flag magic_quotes_runtime off";
		$f[]="\tphp_value post_max_size {$RoundCubeUploadMaxFilesize}M";
		$f[]="\tphp_value upload_max_filesize {$RoundCubeUploadMaxFilesize}M";
		$f[]="\tphp_flag short_open_tag on";
		$f[]="\tphp_flag safe_mode off";
		
		$f[]="\tDirectoryIndex index.php";
		$f[]="\tSSLOptions +StdEnvVars";
		$f[]="\tOptions Indexes FollowSymLinks";
		$f[]="\tAllowOverride None";
		$f[]="</Directory>";
		
		$MyDirectory=@implode("\n", $f);
		$f=array();
	
	
		//$f[]="php_value open_basedir \"".@implode(":", $open_basedir)."\"";
		//$f[]="php_value output_buffering Off";
		//$f[]="php_flag magic_quotes_gpc Off";
	
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen Port: $RoundCubeHTTPSPort\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Listen IP: $RoundCubeListenIP\n";}
	
	
		if($RoundCubeListenIP<>null){
			$unix=new unix();
			$IPS=$unix->NETWORK_ALL_INTERFACES(true);
			if(!isset($IPS[$RoundCubeListenIP])){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ERROR! Listen IP: $RoundCubeListenIP -> FALSE !!\n";}
				$RoundCubeListenIP=null;
			}
		}
	
	
		if($RoundCubeListenIP==null){$RoundCubeListenIP="*";}
	
	
		if($RoundCubeListenIP<>null){
			$RoundCubeHTTPSPort="$RoundCubeListenIP:$RoundCubeHTTPSPort";
		}
		$f[]="";
		$f[]="LockFile /var/run/apache2/artica-accept.lock";
		$f[]="PidFile /var/run/roundcube-apache/apache.pid";
		$f[]="DocumentRoot /usr/share/roundcube";
		$f[]="Listen $RoundCubeListenIP:$RoundCubeHTTPPort";
		$f[]="NameVirtualHost $RoundCubeListenIP:$RoundCubeHTTPPort";
		if($RoundCubeUseSSL==1){
		$f[]="Listen $RoundCubeHTTPSPort";
		$f[]="NameVirtualHost $RoundCubeHTTPSPort";
		}
		$MaxClients=20;
	
		$f[]="<IfModule mpm_prefork_module>";
		$f[]="\tStartServers 1";
		$f[]="\tMinSpareServers 2";
		$f[]="\tMaxSpareServers 3";
		$f[]="\tMaxClients $MaxClients";
		$f[]="\tServerLimit $MaxClients";
		$f[]="\tMaxRequestsPerChild 100";
		$f[]="</IfModule>";
		$f[]="<IfModule mpm_worker_module>";
		$f[]="\tMinSpareThreads      25";
		$f[]="\tMaxSpareThreads      75 ";
		$f[]="\tThreadLimit          64";
		$f[]="\tThreadsPerChild      25";
		$f[]="</IfModule>";
		$f[]="<IfModule mpm_event_module>";
		$f[]="\tMinSpareThreads      25";
		$f[]="\tMaxSpareThreads      75 ";
		$f[]="\tThreadLimit          64";
		$f[]="\tThreadsPerChild      25";
		$f[]="</IfModule>";
		$f[]="AccessFileName .htaccess";
		$f[]="DefaultType text/plain";
		$f[]="HostnameLookups Off";
		$f[]="User				   $APACHE_SRC_ACCOUNT";
		$f[]="Group				   $APACHE_SRC_GROUP";
		$f[]="Timeout              300";
		$f[]="KeepAlive            Off";
		$f[]="KeepAliveTimeout     15";
		$f[]="StartServers         1";
		$f[]="MaxClients           $MaxClients";
		$f[]="MinSpareServers      2";
		$f[]="MaxSpareServers      3";
		$f[]="MaxRequestsPerChild  100";
		$f[]="MaxKeepAliveRequests 100";
		$ServerName=$unix->hostname_g();
		if($ServerName==null){$ServerName="localhost.localdomain";}
	
		$f[]="ServerName $ServerName";
	
	
		
		
	
	
		$f[]="AddType application/x-httpd-php .php";
		if($EnableArticaApachePHPFPM==0){
			$f[]="php_value error_log \"/var/log/php.log\"";
		}
	
		@chown("/var/log/php.log", $APACHE_SRC_ACCOUNT);
	
		$f[]="<IfModule mod_fcgid.c>";
		$f[]="	PHP_Fix_Pathinfo_Enable 1";
		$f[]="</IfModule>";
	
		$f[]="<IfModule mod_php5.c>";
		$f[]="    <FilesMatch \"\.ph(p3?|tml)$\">";
		$f[]="	SetHandler application/x-httpd-php";
		$f[]="    </FilesMatch>";
		$f[]="    <FilesMatch \"\.phps$\">";
		$f[]="	SetHandler application/x-httpd-php-source";
		$f[]="    </FilesMatch>";
		$f[]="</IfModule>";
	
		$f[]="<IfModule mod_mime.c>";
		$f[]="\tTypesConfig /etc/mime.types";
		$f[]="\tAddType application/x-compress .Z";
		$f[]="\tAddType application/x-gzip .gz .tgz";
		$f[]="\tAddType application/x-bzip2 .bz2";
		$f[]="\tAddType application/x-httpd-php .php .phtml";
		$f[]="\tAddType application/x-httpd-php-source .phps";
		$f[]="\tAddLanguage ca .ca";
		$f[]="\tAddLanguage cs .cz .cs";
		$f[]="\tAddLanguage da .dk";
		$f[]="\tAddLanguage de .de";
		$f[]="\tAddLanguage el .el";
		$f[]="\tAddLanguage en .en";
		$f[]="\tAddLanguage eo .eo";
		$f[]="\tRemoveType  es";
		$f[]="\tAddLanguage es .es";
		$f[]="\tAddLanguage et .et";
		$f[]="\tAddLanguage fr .fr";
		$f[]="\tAddLanguage he .he";
		$f[]="\tAddLanguage hr .hr";
		$f[]="\tAddLanguage it .it";
		$f[]="\tAddLanguage ja .ja";
		$f[]="\tAddLanguage ko .ko";
		$f[]="\tAddLanguage ltz .ltz";
		$f[]="\tAddLanguage nl .nl";
		$f[]="\tAddLanguage nn .nn";
		$f[]="\tAddLanguage no .no";
		$f[]="\tAddLanguage pl .po";
		$f[]="\tAddLanguage pt .pt";
		$f[]="\tAddLanguage pt-BR .pt-br";
		$f[]="\tAddLanguage ru .ru";
		$f[]="\tAddLanguage sv .sv";
		$f[]="\tRemoveType  tr";
		$f[]="\tAddLanguage tr .tr";
		$f[]="\tAddLanguage zh-CN .zh-cn";
		$f[]="\tAddLanguage zh-TW .zh-tw";
		$f[]="\tAddCharset us-ascii    .ascii .us-ascii";
		$f[]="\tAddCharset ISO-8859-1  .iso8859-1  .latin1";
		$f[]="\tAddCharset ISO-8859-2  .iso8859-2  .latin2 .cen";
		$f[]="\tAddCharset ISO-8859-3  .iso8859-3  .latin3";
		$f[]="\tAddCharset ISO-8859-4  .iso8859-4  .latin4";
		$f[]="\tAddCharset ISO-8859-5  .iso8859-5  .cyr .iso-ru";
		$f[]="\tAddCharset ISO-8859-6  .iso8859-6  .arb .arabic";
		$f[]="\tAddCharset ISO-8859-7  .iso8859-7  .grk .greek";
		$f[]="\tAddCharset ISO-8859-8  .iso8859-8  .heb .hebrew";
		$f[]="\tAddCharset ISO-8859-9  .iso8859-9  .latin5 .trk";
		$f[]="\tAddCharset ISO-8859-10  .iso8859-10  .latin6";
		$f[]="\tAddCharset ISO-8859-13  .iso8859-13";
		$f[]="\tAddCharset ISO-8859-14  .iso8859-14  .latin8";
		$f[]="\tAddCharset ISO-8859-15  .iso8859-15  .latin9";
		$f[]="\tAddCharset ISO-8859-16  .iso8859-16  .latin10";
		$f[]="\tAddCharset ISO-2022-JP .iso2022-jp .jis";
		$f[]="\tAddCharset ISO-2022-KR .iso2022-kr .kis";
		$f[]="\tAddCharset ISO-2022-CN .iso2022-cn .cis";
		$f[]="\tAddCharset Big5        .Big5       .big5 .b5";
		$f[]="\tAddCharset cn-Big5     .cn-big5";
		$f[]="\t# For russian, more than one charset is used (depends on client, mostly):";
		$f[]="\tAddCharset WINDOWS-1251 .cp-1251   .win-1251";
		$f[]="\tAddCharset CP866       .cp866";
		$f[]="\tAddCharset KOI8      .koi8";
		$f[]="\tAddCharset KOI8-E      .koi8-e";
		$f[]="\tAddCharset KOI8-r      .koi8-r .koi8-ru";
		$f[]="\tAddCharset KOI8-U      .koi8-u";
		$f[]="\tAddCharset KOI8-ru     .koi8-uk .ua";
		$f[]="\tAddCharset ISO-10646-UCS-2 .ucs2";
		$f[]="\tAddCharset ISO-10646-UCS-4 .ucs4";
		$f[]="\tAddCharset UTF-7       .utf7";
		$f[]="\tAddCharset UTF-8       .utf8";
		$f[]="\tAddCharset UTF-16      .utf16";
		$f[]="\tAddCharset UTF-16BE    .utf16be";
		$f[]="\tAddCharset UTF-16LE    .utf16le";
		$f[]="\tAddCharset UTF-32      .utf32";
		$f[]="\tAddCharset UTF-32BE    .utf32be";
		$f[]="\tAddCharset UTF-32LE    .utf32le";
		$f[]="\tAddCharset euc-cn      .euc-cn";
		$f[]="\tAddCharset euc-gb      .euc-gb";
		$f[]="\tAddCharset euc-jp      .euc-jp";
		$f[]="\tAddCharset euc-kr      .euc-kr";
		$f[]="\tAddCharset EUC-TW      .euc-tw";
		$f[]="\tAddCharset gb2312      .gb2312 .gb";
		$f[]="\tAddCharset iso-10646-ucs-2 .ucs-2 .iso-10646-ucs-2";
		$f[]="\tAddCharset iso-10646-ucs-4 .ucs-4 .iso-10646-ucs-4";
		$f[]="\tAddCharset shift_jis   .shift_jis .sjis";
		$f[]="\tAddType text/html .shtml";
		$f[]="\tAddOutputFilter INCLUDES .shtml";
		$f[]="</IfModule>";

		
		
		
		$f[]="<VirtualHost $RoundCubeListenIP:$RoundCubeHTTPPort>";
		$f[]="ServerAdmin webmaster@none.tld";
		$f[]="DocumentRoot /usr/share/roundcube";
		$f[]="$MyDirectory";
		$f[]="</VirtualHost>";
		

		if($RoundCubeUseSSL==1){
			$f[]="AcceptMutex flock";
			$f[]="SSLCertificateFile \"/etc/ssl/certs/apache/server.crt\"";
			$f[]="SSLCertificateKeyFile \"/etc/ssl/certs/apache/server.key\"";
			$f[]="SSLVerifyClient none";
			$f[]="ServerSignature Off";
			$f[]="SSLRandomSeed startup file:/dev/urandom  256";
			$f[]="SSLRandomSeed connect builtin";
			
			$f[]="\tSSLRandomSeed connect builtin";
			$f[]="\tSSLRandomSeed connect file:/dev/urandom 256";
			$f[]="\tAddType application/x-x509-ca-cert .crt";
			$f[]="\tAddType application/x-pkcs7-crl    .crl";
			$f[]="\tSSLPassPhraseDialog  builtin";
			$f[]="\tSSLSessionCache        shmcb:/var/run/apache2/ssl_scache-artica(512000)";
			$f[]="\tSSLSessionCacheTimeout  300";
			$f[]="\tSSLSessionCacheTimeout  300";
			
			$f[]="\tSSLCipherSuite HIGH:MEDIUM:!ADH";
			$f[]="\tSSLProtocol all -SSLv2";			
			
			
			$f[]="<VirtualHost $RoundCubeHTTPSPort>";
			$f[]="ServerAdmin webmaster@none.tld";
			$f[]="DocumentRoot /usr/share/roundcube";
			$f[]="$MyDirectory";
			$mknod=$unix->find_program("mknod");
			shell_exec("$mknod /dev/random c 1 9 >/dev/null 2>&1");
			$f[]="SSLEngine on";
			
			$f[]="</VirtualHost>";
			$f[]="";
		
		}
		
		if(!is_file("/etc/ssl/certs/apache/server.crt")){shell_exec("/usr/share/artica-postfix/bin/artica-install --apache-ssl-cert");}
		
	
	
	

	
		if($EnableArticaApachePHPFPM==1){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Activate PHP5-FPM\n";}
			shell_exec("$php /usr/share/artica-postfix/exec.initslapd.php --phppfm");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Restarting PHP5-FPM\n";}
			shell_exec("/etc/init.d/php5-fpm restart");
			$f[]="\tAlias /php5.fastcgi /var/run/artica-roundcube/php5.fastcgi";
			$f[]="\tAddHandler php-script .php";
			$f[]="\tFastCGIExternalServer /var/run/artica-roundcube/php5.fastcgi -socket /var/run/php-fpm.sock -idle-timeout 610";
			$f[]="\tAction php-script /php5.fastcgi virtual";
			$f[]="\t<Directory /var/run/artica-roundcube>";
			$f[]="\t\t<Files php5.fastcgi>";
			//$f[]="\t\tOrder deny,allow";
			//$f[]="\t\tAllow from all";
			$f[]="\t\t</Files>";
			$f[]="\t</Directory>";
		}else{
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} PHP5-FPM is disabled\n";}
		}
	
	
		$f[]="Loglevel info";
		$f[]="ErrorLog /var/log/apache2/roundcube-error.log";
		$f[]="LogFormat \"%h %l %u %t \\\"%r\\\" %<s %b\" common";
		$f[]="CustomLog /var/log/apache2/roundcube-access.log common";
	
		if($EnableArticaApachePHPFPM==0){$array["php5_module"]="libphp5.so";}
	
	
		$array["actions_module"]="mod_actions.so";
		$array["expires_module"]="mod_expires.so";
		$array["rewrite_module"]="mod_rewrite.so";
		$array["dir_module"]="mod_dir.so";
		$array["mime_module"]="mod_mime.so";
		$array["alias_module"]="mod_alias.so";
		$array["auth_basic_module"]="mod_auth_basic.so";
		$array["authn_file_module"]="mod_authn_file.so";
		//$array["authz_host_module"]="mod_authz_host.so";
		$array["autoindex_module"]="mod_autoindex.so";
		$array["negotiation_module"]="mod_negotiation.so";
		$array["ssl_module"]="mod_ssl.so";
		$array["headers_module"]="mod_headers.so";
		$array["ldap_module"]="mod_ldap.so";
	
		if($EnableArticaApachePHPFPM==1){$array["fastcgi_module"]="mod_fastcgi.so";}
	
		if(is_dir("/etc/apache2")){
			if(!is_file("/etc/apache2/mime.types")){
				if($apache_LOCATE_MIME_TYPES<>"/etc/apache2/mime.types"){
					@copy($apache_LOCATE_MIME_TYPES, "/etc/apache2/mime.types");
				}
			}
	
		}
	
	
	
	
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Mime types path.......: $apache_LOCATE_MIME_TYPES\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Modules path..........: $APACHE_MODULES_PATH\n";}
	
		while (list ($module, $lib) = each ($array) ){
	
			if(is_file("$APACHE_MODULES_PATH/$lib")){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} include module \"$module\"\n";}
				$f[]="LoadModule $module $APACHE_MODULES_PATH/$lib";
			}else{
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} skip module \"$module\"\n";}
			}
	
		}
	
		$f[]="\n\n";
		@file_put_contents("/etc/artica-postfix/apache-roundcube.conf", @implode("\n", $f));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/artica-postfix/apache-roundcube.conf done\n";}
	
		
		$apachename=$unix->APACHE_SRC_ACCOUNT();
		$apachegroup=$unix->APACHE_SRC_GROUP();
		@mkdir("/usr/share/roundcube/uploads",0755,true);
		$dirs=$unix->dirdir("/usr/share/roundcube");
		while (list ($dirname, $lib) = each ($dirs) ){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Privileges on $dirname\n";}
			$unix->chown_func($APACHE_SRC_ACCOUNT,$APACHE_SRC_GROUP,$dirname);
			$unix->chmod_func(0755, $dirname);
		}
		
		
		
		
		
		
	}

			
			









?>