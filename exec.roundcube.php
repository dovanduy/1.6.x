<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.roundcube.inc');
include_once(dirname(__FILE__) . '/ressources/class.apache.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
	@file_put_contents($LIGHTTPD_CONF_PATH, $roundcube->Build_lighthttp());	
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
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
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
	$RoundCubeHTTPEngineEnabled=$sock->GET_INFO("RoundCubeHTTPEngineEnabled");
	if(!is_numeric($RoundCubeHTTPEngineEnabled)){$RoundCubeHTTPEngineEnabled=0;}
	
	$pid=LIGHTTPD_PID();
	if($RoundCubeHTTPEngineEnabled==1){
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
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	
	@mkdir("/var/run/lighttpd",0755,true);
	

	$cmd="$lighttpd_bin -f $LIGHTTPD_CONF_PATH";
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
	return "/etc/artica-postfix/lighttpd-roundcube.conf";
}

function LIGHTTPD_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/lighttpd/lighttpd-roundcube.pid');
	if($unix->process_exists($pid)){return $pid;}
	$lighttpd_bin=$unix->find_program("lighttpd");
	$LIGHTTPD_CONF_PATH=LIGHTTPD_CONF_PATH();
	return $unix->PIDOF_PATTERN($lighttpd_bin." -f $LIGHTTPD_CONF_PATH");
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
		echo "Starting......: ".date("H:i:s")." Roundcube saving main.inc.php failed.\n";
	}else{
		echo "Starting......: ".date("H:i:s")." Roundcube saving main.inc.php Success.\n";
	}	
	
	echo "Starting......: ".date("H:i:s")." Roundcube building main configuration done.\n";
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
	
	echo "Starting......: ".date("H:i:s")." Roundcube anti-hack ". count($iptables)." iptables rule(s)\n";
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
		$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]=$unix->LIGHTTPD_PORT("/etc/artica-postfix/lighttpd-roundcube.conf");
		
		
	}
	
	if($GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]==null){$GLOBALS["LIGHTTPD_PORT_ROUNDCUBE"]=443;}
	
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

	

			
			









?>