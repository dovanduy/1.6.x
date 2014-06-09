<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["NOMAIL"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--nomail#",implode(" ",$argv))){$GLOBALS["NOMAIL"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


$unix=new unix();
$PidRestore="/etc/artica-postfix/pids/zarafaRestore.pid";
$pid=$unix->get_pid_from_file($PidRestore);
if($unix->process_exists($pid,basename(__FILE__))){die();}



if($argv[1]=="--remove-database"){remove_database();exit;}
if($argv[1]=="--relink-to"){relinkto($argv[2],$argv[3]);exit;}
if(system_is_overloaded(basename(__FILE__))){echo "Overloaded, die()";die();}
if($argv[1]=="--orphans"){orphans();die();}
if($argv[1]=="--emergency"){emergency_user($argv[2]);die();}
if($argv[1]=="--export-hash"){user_status_table();die();}
if($argv[1]=="--view-hash"){view_hash();die();}
if($argv[1]=="--config"){config();die();}
if($argv[1]=="--ldap-config"){ldap_config();die();}
if($argv[1]=="--exoprhs"){export_orphans();die();}

if($argv[1]=="--yaffas"){yaffas();exit;}
if($argv[1]=="--users-status"){user_status_table();exit;}





die();
sync_users();
function sync_users(){
$unix=new unix();
$zarafaadmin=$unix->find_program("zarafa-admin");

echo "Synchronize external datas\n";
shell_exec("$zarafaadmin --sync");
shell_exec("$zarafaadmin --list-companies");
shell_exec("$zarafaadmin -s");

exec("$zarafaadmin -l",$array);

while (list ($index, $line) = each ($array) ){
	if(preg_match("#\s+(.+?)\s+\s+(.+)#",$line,$re)){
		if(trim($re[1])=="username"){continue;}
		$usernames[]=trim($re[1]);
	}
	
}
if(!is_array($usernames)){return;}


while (list ($index, $user) = each ($usernames) ){
	echo "Create store for $user\n";
	if(system_is_overloaded(basename(__FILE__))){system_admin_events("Task stopped, overloaded system", __FUNCTION__, __FILE__, __LINE__, "zarafa");die();}
	shell_exec("$zarafaadmin --create-store $user");
}

}

function export_orphans(){
	
	if(isset($GLOBALS["export_orphans_executed"])){
		if($GLOBALS["VERBOSE"]){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				echo "export_orphans Already executed $called\n";
			}
			}
			return;
	}
	$GLOBALS["export_orphans_executed"]=true;
	
	
if(system_is_overloaded(basename(__FILE__))){
	if($GLOBALS["VERBOSE"]){echo "System overloaded\n";}
	system_admin_events("Task stopped, overloaded system", __FUNCTION__, __FILE__, __LINE__, "zarafa");
	die();
}
$unix=new unix();
$q=new mysql();
$q->BuildTables();
$q->QUERY_SQL("TRUNCATE TABLE `zarafa_orphaned`","artica_backup");
$zarafaadmin=$unix->find_program("zarafa-admin");


$kill=$unix->find_program("kill");
$pids=$unix->PIDOF_PATTERN_ALL("zarafa-admin --list-orphans");
if(count($pids)>0){
	while (list ($pid, $line) = each ($pids) ){
		$time=$unix->PROCESS_TTL($pid);
		if($time>15){
			$unix->_syslog("killing zarafa-admin --list-orphans pid $pid ({$time}mn)", basename(__FILE__));
			unix_system_kill_force($pid);
		}
		
	}
	
}

$pid=$unix->PIDOF_PATTERN("zarafa-admin --list-orphans");
if($unix->process_exists($pid)){
	$unix->_syslog("zarafa-admin --list-orphans pid $pid still running", basename(__FILE__));
}


$cmd="$zarafaadmin --list-orphans 2>&1";

exec($cmd,$array);
if($GLOBALS["VERBOSE"]){echo "$cmd --> ".count($array)."\n";}



while (list ($index, $line) = each ($array) ){
	$store=null;
	
	
	if(preg_match("#([A-Z0-9]+)\s+(.+)\s+([0-9\/]+)\s+([0-9:]+)\s+([A-Z]+)\s+([0-9]+)\s+([A-Z]+)#", $line,$re)){
		if($GLOBALS["VERBOSE"]){echo "$line --> match Pattern 1\n";}
		$store=$re[1];
		$user=$re[2];
		
		$date=strtotime("{$re[3]} {$re[4]} {$re[5]}");
		$distanceOfTimeInWords=$unix->distanceOfTimeInWords($date,time());
		$size=$re[6];
		$unit=$re[7];		
	}

	if($store==null){
		if(preg_match("#([A-Z0-9]+)\s+(.+?)\s+([0-9\/]+)\s+([0-9:]+)\s+([0-9]+)\s+([A-Z]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$line --> match Pattern 2\n";}
			$store=$re[1];
			$user=$re[2];
			$date=strtotime("{$re[3]} {$re[4]}");	
			$size=$re[5];
			$unit=$re[6];	
		}			
		
	}
	
	if($store==null){
		if(preg_match("#([A-Z0-9]+)\s+(.+?)\s+([0-9\/]+)\s+([0-9:]+)\s+unlimited#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$line --> match Pattern 3\n";}
			$store=$re[1];
			$user=$re[2];
			$date=strtotime("{$re[3]} {$re[4]}");	
			$size="10240000000000";
			$unit="B";
		}
	}
	if($store==null){
		if(preg_match("#([A-Z0-9]+)\s+(.*?)\s+<unknown>\s+([0-9\.]+)\s+([A-Z]+)\s+([a-z]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$line --> match Pattern 4\n";}
			$store=$re[1];
			$user=$re[2];
			$date=strtotime("0000/00/00 00:00:00");
			$size=$re[3];
			$unit=$re[4];	
		}
	}	
	if($store==null){
		if(preg_match("#([A-Z0-9]+)\s+(.*?)\s+(.*?)\s+([0-9\.]+)\s+([A-Z]+)\s+([a-z]+)#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "$line --> match Pattern 4\n";}
			$store=$re[1];
			$user=$re[2];
			$date=strtotime($re[3]);
			$size=$re[4];
			$unit=$re[5];
		}
	}	
	
	
	
	if($store==null){
		if($GLOBALS["VERBOSE"]){echo "$line --> No match ALL\n";}
		$arraylo[]="No match $line";
		continue;
	}
	
		$distanceOfTimeInWords=$unix->distanceOfTimeInWords($date,time());
		if($unit=="MB"){$size=$size*1000;$size=$size*1024;$unit="B";}
		if($unit=="KB"){$size=$size*1024;$unit="B";}
		if($unit=="GB"){$size=$size*1000;$size=$size*1000;$size=$size*1024;}
		$date=date("Y-m-d H:i:s",$date);
		$textsize=FormatBytes($size/1024);
		$textsize=str_replace("&nbsp;", "", $textsize);
		
		if($GLOBALS["VERBOSE"]){echo "Store $store ($textsize) for user $user is unlinked since $date ($distanceOfTimeInWords)\n";}
		
		$f[]="Store $store ($textsize) for user $user is unlinked since $date ($distanceOfTimeInWords)";
		$sql="INSERT IGNORE INTO zarafa_orphaned (storeid,size,zDate,uid) VALUES ('$store','$size','$date','$user')";
		$arraylo[]=$sql;
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			$unix->send_email_events("Zarafa orphaned status mysql error", $q->mysql_error." will wait a new cycle", "mailbox");
			echo $q->mysql_error."\n";
			return;
		}
		


}

if($GLOBALS["VERBOSE"]){echo "Save /tmp/zarafa.scan.txt \n";}
@file_put_contents("/tmp/zarafa.scan.txt", @implode("\n", $array)."\n".@implode("\n", $arraylo));

if($GLOBALS["VERBOSE"]){echo "--> user_status_table()\n";}
user_status_table();

if(!$GLOBALS["NOMAIL"]){
	if(count($f)>0){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($unix->file_time_min($timefile)<300){return;}
		@unlink($timefile);
		@file_put_contents($timefile, time());		
		$unix->send_email_events(count($f)." orphaned store(s)", @implode("\n", $f), "mailbox");
	}
}
	
}



function orphans(){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$zarafaadmin=$unix->find_program("zarafa-admin");
	if(!is_file($zarafaadmin)){return ;}
		//$mns=$unix->file_time_min($timefile);
		if(!$GLOBALS["FORCE"]){
			if(system_is_overloaded(basename(__FILE__))){
				system_admin_events("Overloaded system, aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
				return;
			}
			
			$pid=$unix->get_pid_from_file($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				$timeProcess=$unix->PROCCESS_TIME_MIN($pid);
				system_admin_events("$pid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
				if($timeProcess<15){
					return;
				}
				
				$kill=$unix->find_program("kill");
				unix_system_kill_force($pid);
				system_admin_events("$pid, killed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
			}		
			
		}

@file_put_contents($pidfile, getmypid());

$kill=$unix->find_program("kill");
$pids=$unix->PIDOF_PATTERN_ALL("zarafa-admin --list-orphans");
if(count($pids)>0){
	while (list ($pid, $line) = each ($pids) ){
		$time=$unix->PROCESS_TTL($pid);
		if($time>15){
			$unix->_syslog("killing zarafa-admin --list-orphans pid $pid ({$time}mn)", basename(__FILE__));
			unix_system_kill_force($pid);
		}

	}

}

$pid=$unix->PIDOF_PATTERN("zarafa-admin --list-orphans");
if($unix->process_exists($pid)){
	$unix->_syslog("zarafa-admin --list-orphans pid $pid still running", basename(__FILE__));
	return;
}



exec("$zarafaadmin --list-orphans 2>&1",$array);
$users=array();
$ff=false;
while (list ($index, $line) = each ($array) ){
	if(preg_match("#Users without stores#",$line)){$ff=true;}
	if(!$ff){continue;}
	if(preg_match("#\s+[0-9+\.\-a-zA-Z@]+$#",$line,$re)){
		$re[1]==trim($re[1]);
		if($re[1]=="Username"){continue;}
		if(strpos($re[1],"---")>0){continue;}
		if($re[1]=="--------------------------------------------------------"){continue;}
		if($re[1]=="---------------"){continue;}
		if($re[1]=="without stores:"){continue;}
		if($GLOBALS["VERBOSE"]){echo "found \"{$re[1]}\"\n";}
		
		$users[$re[1]]=$re[1];
	}
	
}


if(count($users)>1){
	while (list ($uid, $line) = each ($users) ){
		exec("$zarafaadmin --create-store $uid",$results);
		$logs[]="Create store for $uid";
		while (list ($a, $b) = each ($results) ){$logs[]="$b";}
		unset($results);
	}
	
	if($GLOBALS["VERBOSE"]){
		echo @implode("\n",$logs);
	}
	send_email_events("Creating store for ". count($users),"Artica has successfully created store in zarafa server:\n".@implode("\n",$logs));
	
}

	
}

function emergency_user($uid){
	if($uid==null){return;}
	if($GLOBALS["VERBOSE"]){echo "Checking uid:$uid\n";}
	$user=new user($uid);
	$ou=$user->ou;
	if($GLOBALS["VERBOSE"]){echo "Checking OU:$ou\n";}
	if($ou==null){echo "Checking $uid no such organization\n";return;}
	$ldap=new clladp();
	
	$info=$ldap->OUDatas($ou);
	$zarafaEnabled=1;
	if(!$info["objectClass"]["zarafa-company"]){
		$dn="ou=$ou,dc=organizations,$ldap->suffix";
		$upd["objectClass"]="zarafa-company";
		if(!$ldap->Ldap_add_mod("$dn",$upd)){
			echo $ldap->ldap_last_error;
			return;
		}
	}
	
	sync_users();
	orphans();
	
}


function view_hash(){
	
	print_r(unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/zarafa-export.db"))));
	
}

function export_hash_users($company){
	$array=array();
	exec("{$GLOBALS["zarafa_admin"]} -l -I \"$company\" 2>&1",$results);
	while (list ($index, $line) = each ($results) ){
		usleep(5000);
		if($line==null){continue;}
		if(preg_match("#------#",$line)){continue;}
		if(preg_match("#User.+?list#",$line)){continue;}
		if(preg_match("#username\s+fullname#",$line)){continue;}
		if(preg_match("#\s+(.+?)\s+(.+?)$#",$line,$re)){
			$username=trim($re[1]);
			exec("{$GLOBALS["zarafa_admin"]} --details \"$username\" 2>&1",$users_results);
			while (list ($num, $user_line) = each ($users_results) ){
				if(preg_match("#(.+?):(.+?)$#",$user_line,$ri)){
					$field=trim($ri[1]);
					$field=str_replace(" ","_",$field);
					$field=strtoupper($field);
					$array[$username][$field]=trim($ri[2]);
				}
				
			}
			
			
		}
		
		
	}

	return $array;
	
}

function get_version_array(){
	$unix=new unix();
	$zarafa_server=$unix->find_program("zarafa-server");
	exec("$zarafa_server -V 2>&1",$results);
	while (list ($num, $user_line) = each ($results) ){
		if(preg_match("#Product version:\s+([0-9]+),([0-9]+),([0-9]+)#", $user_line,$re)){
			return array("MAJOR"=>$re[1],"MINOR"=>$re[2],"REV"=>$re[3]);
		}
	}
}


function config(){
	$unix=new unix();
	$sock=new sockets();
	$ZarafaAspellEnabled=$sock->GET_INFO("ZarafaAspellEnabled");
	$ZarafaWebNTLM=$sock->GET_INFO("ZarafaWebNTLM");
	$ZarafaEnablePlugins=$sock->GET_INFO("ZarafaEnablePlugins");
	
	if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
	if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
	if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
	
	//7,1,3,40304
	
	$users=new usersMenus();

	
	
	
$f[]="<?php";
$f[]="ini_set(\"zend.ze1_compatibility_mode\", false);";
$f[]="ini_set(\"max_execution_time\", 300); // 5 minutes";
$f[]="ini_set(\"display_errors\", false);";
$f[]="define(\"CONFIG_CHECK\", TRUE);";
$f[]="define(\"DEFAULT_SERVER\",\"file:///var/run/zarafa\");";
$f[]="define(\"SSLCERT_FILE\", NULL);";
$f[]="define(\"SSLCERT_PASS\", NULL);";
if($ZarafaWebNTLM==1){
	$f[]="define(\"LOGINNAME_STRIP_DOMAIN\", true);";
}else{
	$f[]="define(\"LOGINNAME_STRIP_DOMAIN\", false);";
}
$f[]="if (isset(\$_GET[\"external\"]) && preg_match(\"/[a-z][a-z0-9_]+/i\",\$_GET[\"external\"])){define(\"COOKIE_NAME\",\$_GET[\"external\"]);}else{define(\"COOKIE_NAME\",\"ZARAFA_WEBACCESS\");}";
$f[]="define(\"THEME_COLOR\", \"default\");\$base_url = dirname(\$_SERVER[\"PHP_SELF\"]);if(substr(\$base_url,-1)!=\"/\") \$base_url .=\"/\";";
$f[]="define(\"BASE_URL\", \$base_url);";
$f[]="define(\"BASE_PATH\", dirname(\$_SERVER[\"SCRIPT_FILENAME\"]) . \"/\");";
$f[]="define(\"MIME_TYPES\", BASE_PATH . \"server/mimetypes.dat\");";
$f[]="define(\"TMP_PATH\", \"/var/lib/zarafa-webaccess/tmp\");";
$f[]="set_include_path(BASE_PATH. PATH_SEPARATOR . BASE_PATH.\"server/PEAR/\" .  PATH_SEPARATOR . \"/usr/share/php/\");";
$f[]="define(\"DIALOG_URL\", \"index.php?load=dialog&\");";
$f[]="define(\"DND_FILEUPLOAD_URL\", \"index.php?load=upload_attachment&\");";
$f[]="define(\"PATH_PLUGIN_DIR\", \"plugins\");";
if($ZarafaEnablePlugins==1){
	$f[]="define(\"ENABLE_PLUGINS\", true);";
}else{
	$f[]="define(\"ENABLE_PLUGINS\", false);";	
}
$f[]="define(\"DISABLED_PLUGINS_LIST\", \"\");";
$f[]="define(\"DISABLE_FULL_GAB\", false);";
$f[]="define(\"DISABLE_FULL_CONTACTLIST_THRESHOLD\", -1);";
$f[]="define(\"ENABLE_GAB_ALPHABETBAR\", false);";
$f[]="define(\"FREEBUSY_DAYBEFORE_COUNT\", 7);";
$f[]="define(\"FREEBUSY_NUMBEROFDAYS_COUNT\", 90);";
$f[]="define(\"BLOCK_SIZE\", 1048576);";
$f[]="define(\"CLIENT_TIMEOUT\", 5*60*1000);";
$f[]="define(\"EXPIRES_TIME\", 60*60*24*7*13);";
$f[]="define(\"UPLOADED_ATTACHMENT_MAX_LIFETIME\", 6*60*60);";
$f[]="define(\"FCKEDITOR_PATH\",dirname(\$_SERVER[\"SCRIPT_FILENAME\"]).\"/client/widgets/fckeditor\");";
$f[]="define(\"FCKEDITOR_JS_PATH\",\"client/widgets/fckeditor\");";

	if($ZarafaAspellEnabled==1){
		$asspellbin=$unix->find_program("aspell");
		$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", true);";
		$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"$asspellbin\");";	
		echo "Starting zarafa..............: Aspell checker is enabled\n";		
		
	}else{
		$f[]="define(\"FCKEDITOR_SPELLCHECKER_ENABLED\", false);";	
		$f[]="define(\"FCKEDITOR_SPELLCHECKER_PATH\", \"/usr/bin/aspell\");";
		echo "Starting zarafa..............: Aspell checker is disabled\n";
	}

$f[]="define(\"FCKEDITOR_SPELLCHECKER_LANGUAGE\", FALSE); // set FALSE to use the language chosen by the user, but make sure that these languages are installed with aspell!";
$f[]="define(\"LANGUAGE_DIR\", \"server/language/\");";
$f[]="if (isset(\$_ENV[\"LANG\"]) && \$_ENV[\"LANG\"]!=\"C\"){";
$f[]="	define(\"LANG\", \$_ENV[\"LANG\"]); // This means the server environment language determines the web client language.";
$f[]="	}else{";
$f[]="define(\"LANG\", \"en_EN\"); // default fallback language";
$f[]="	}";
$f[]="";
$f[]="if (function_exists(\"date_default_timezone_set\")){date_default_timezone_set(\"Europe/London\");}";
$f[]="error_reporting(0);";
$f[]="if (file_exists(\"debug.php\")){include(\"debug.php\");}else{function dump(){}}";
$f[]="?>";	

@file_put_contents("/usr/share/zarafa-webaccess/config.php",@implode("\n",$f));
echo "Starting zarafa..............: web config.php done\n";	
}


function ldap_config(){
	
	$sock=new sockets();
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	$prefix="dc=organizations,";
	$ldap_user_type_attribute_value="posixAccount";
	$ldap_user_search_filter="(objectClass=userAccount)";
	
	$ldap_user_search_filter="(objectClass=zarafa-user)";
	
	$Is713Sup=false;
	if(!$users->ASPELL_INSTALLED){$ZarafaAspellEnabled=0;}
	
	$version2=get_version_array();
	
	echo "Starting zarafa..............: MAJOR:{$version2["MAJOR"]}, MINOR:{$version2["MINOR"]}, REV:{$version2["REV"]}\n";
	
	if($version2["MAJOR"]>6){
		if($version2["MINOR"]>0){
			if($version2["REV"]>2){
				$Is713Sup=true;
				echo "Starting zarafa..............: 7.1.3 version or above...\n";
			}
		}
	}	
	
	$ldap_user_unique_attribute="uidNumber";
	$ldap_user_unique_attribute_type = "text";
	$ldap=new clladp();
	$user="cn=$ldap->ldap_admin,$ldap->suffix";
	$ldap_loginname_attribute="uid";
	$ldap_password_attribute="userPassword";
	$ldap_nonactive_attribute="zarafaSharedStoreOnly";
	//$ldap_group_search_filter = "(objectClass=posixGroup)";
	$ldap_group_unique_attribute = "gidNumber";
	$ldap_group_unique_attribute_type="text";
	$ldap_groupname_attribute="cn";	
	$ldap_addresslist_search_filter = "(objectClass=zarafaAddressList)";
	$ldap_contact_type_attribute_value="zarafa-contact";
	$ldap_groupmembers_attribute="memberUid";
	$ldap_groupmembers_attribute_type="text";
	$ldap_groupmembers_relation_attribute="uid";
	$ldap_emailaliases_attribute="mailAlias";
	$ldap_user_sendas_relation_attribute="uidNumber";
	$prefixAddresses="dc=NAB,";
	
	if($CyrusToAD==1){
		$ldap=new ldapAD();
		$prefix=null;
		$user="$ldap->ldap_admin,$ldap->suffix";
		$ldap_user_type_attribute_value="sAMAccountName";
		$ldap_user_sendas_relation_attribute="sAMAccountName";
		$ldap_user_search_filter="(zarafaAccount=1)";
		$ldap_user_unique_attribute="objectGUID";
		$ldap_user_unique_attribute_type = "binary";
		$ldap_loginname_attribute="sAMAccountName";
		$ldap_password_attribute=null;
		$ldap_group_search_filter=null;
		$ldap_group_unique_attribute="objectSid";
		$ldap_group_unique_attribute_type="binary";
		$ldap_groupname_attribute="dn";
		$ldap_addresslist_search_filter=null;
		$ldap_contact_type_attribute_value="Contact";
		$ldap_groupmembers_attribute="member";
		$ldap_groupmembers_attribute_type="dn";
		$ldap_groupmembers_relation_attribute=null;
		$ldap_emailaliases_attribute ="otherMailbox";
		$prefixAddresses=null;
		
	}
	
	
	
$f[]="# ---------- GENERAL ------------#";
$f[]="ldap_host = $ldap->ldap_host";
$f[]="ldap_port = $ldap->ldap_port";
$f[]="ldap_search_base = $prefix$ldap->suffix";
$f[]="ldap_protocol = ldap";
$f[]="ldap_server_charset = utf-8";
$f[]="ldap_bind_user = $user";
$f[]="ldap_bind_passwd = $ldap->ldap_password";
$f[]="ldap_network_timeout = 30";
$f[]="ldap_object_type_attribute = objectClass";
$f[]="";
if($CyrusToAD==1){
	$f[]="ldap_user_type_attribute_value = User";
	$f[]="ldap_group_type_attribute_value = Group";
	$f[]="ldap_company_type_attribute_value = ou";
	$f[]="ldap_addresslist_type_attribute_value = zarafa-addresslist";
	$f[]="ldap_dynamicgroup_type_attribute_value = zarafa-dynamicgroup";
}
$f[]="ldap_contact_type_attribute_value = $ldap_contact_type_attribute_value";
$f[]="# ---------- USERS ------------#";
$f[]="ldap_user_search_base =  $prefix$ldap->suffix";
$f[]="ldap_user_scope = sub";
$f[]="ldap_user_type_attribute_value = $ldap_user_type_attribute_value";
$f[]="ldap_user_search_filter = $ldap_user_search_filter";
$f[]="";
$f[]="ldap_user_unique_attribute = $ldap_user_unique_attribute";
$f[]="ldap_user_unique_attribute_type = $ldap_user_unique_attribute_type";
$f[]="";
$f[]="ldap_user_sendas_attribute = zarafaSendAsPrivilege";
$f[]="ldap_user_sendas_attribute_type = text";
$f[]="ldap_user_sendas_relation_attribute = $ldap_user_sendas_relation_attribute";
$f[]="";
$f[]="ldap_sendas_attribute = zarafaSendAsPrivilege";
$f[]="ldap_sendas_attribute_type = text";
$f[]="ldap_sendas_relation_attribute = $ldap_user_sendas_relation_attribute";

$f[]="";
$f[]="ldap_user_certificate_attribute = userCertificate";
$f[]="ldap_fullname_attribute = displayName";
$f[]="ldap_authentication_method = password";
$f[]="ldap_loginname_attribute = $ldap_loginname_attribute";
$f[]="ldap_password_attribute = $ldap_password_attribute";
$f[]="ldap_emailaddress_attribute = mail";
$f[]="ldap_emailaliases_attribute = $ldap_emailaliases_attribute";

$f[]="ldap_isadmin_attribute = zarafaAdmin";
$f[]="ldap_nonactive_attribute =$ldap_nonactive_attribute";
$f[]="";
$f[]="# ---------- GROUPS ------------#";
$f[]="ldap_group_search_base = $prefix$ldap->suffix";
$f[]="ldap_group_scope = sub";
$f[]="ldap_group_search_filter = $ldap_group_search_filter";
$f[]="ldap_group_unique_attribute = $ldap_group_unique_attribute";
$f[]="ldap_group_unique_attribute_type = $ldap_group_unique_attribute_type";
$f[]="ldap_groupname_attribute = $ldap_groupname_attribute";
if($CyrusToAD==0){
	$f[]="ldap_group_type_attribute_value = posixGroup";
}else{
	$f[]="ldap_group_security_attribute = groupType";
	$f[]="ldap_group_security_attribute_type = ads";
}
$f[]="ldap_groupmembers_attribute = $ldap_groupmembers_attribute";
$f[]="ldap_groupmembers_attribute_type = $ldap_groupmembers_attribute_type";
$f[]="ldap_groupmembers_relation_attribute =$ldap_groupmembers_relation_attribute";

$f[]="";
$f[]="";
$f[]="# ---------- COMPAGNIES ------------#";
$f[]="ldap_company_unique_attribute = ou";
$f[]="ldap_company_search_base = $prefix$ldap->suffix";
$f[]="ldap_company_scope = base";
$f[]="ldap_company_search_filter =(&(objectclass=organizationalUnit)(objectClass=zarafa-company))";
$f[]="ldap_company_type_attribute_value = organizationalUnit";
$f[]="";
$f[]="ldap_companyname_attribute = ou";
$f[]="";
$f[]="ldap_company_view_attribute = zarafaViewPrivilege";
$f[]="ldap_company_view_attribute_type = text";
$f[]="ldap_company_view_relation_attribute =";
$f[]="";
$f[]="ldap_company_admin_attribute = zarafaAdminPrivilege";
$f[]="ldap_company_admin_attribute_type = text";
$f[]="ldap_company_admin_relation_attribute = $ldap_user_sendas_relation_attribute ";
$f[]="";
$f[]="ldap_company_system_admin_attribute = zarafaSystemAdmin";
$f[]="ldap_company_system_admin_attribute_type = text";
$f[]="ldap_company_system_admin_relation_attribute =";
$f[]="";
$f[]="";


$f[]="ldap_quota_userwarning_recipients_attribute = zarafaQuotaUserWarningRecipients";
$f[]="ldap_quota_userwarning_recipients_attribute_type = text";
$f[]="ldap_quota_userwarning_recipients_relation_attribute =";
$f[]="ldap_quota_companywarning_recipients_attribute = zarafaQuotaCompanyWarningRecipients";
$f[]="ldap_quota_companywarning_recipients_attribute_type = text";
$f[]="ldap_quota_companywarning_recipients_relation_attribute=";
$f[]="";
$f[]="";
$f[]="ldap_quotaoverride_attribute = zarafaQuotaOverride";
$f[]="ldap_warnquota_attribute = zarafaQuotaWarn";
$f[]="ldap_softquota_attribute = zarafaQuotaSoft";
$f[]="ldap_hardquota_attribute = zarafaQuotaHard";
$f[]="ldap_userdefault_quotaoverride_attribute = zarafaUserDefaultQuotaOverride";
$f[]="ldap_userdefault_warnquota_attribute = zarafaUserDefaultQuotaWarn";
$f[]="ldap_userdefault_softquota_attribute = zarafaUserDefaultQuotaSoft";
$f[]="ldap_userdefault_hardquota_attribute = zarafaUserDefaultQuotaHard";
$f[]="";
$f[]="";
$f[]="ldap_quota_multiplier = 1048576";
$f[]="";
$f[]="";
if(!$Is713Sup){
	$f[]="ldap_user_department_attribute = departmentNumber";
	$f[]="ldap_user_location_attribute = physicalDeliveryOfficeName";
	$f[]="ldap_user_telephone_attribute = telephoneNumber";
	$f[]="ldap_user_fax_attribute = facsimileTelephoneNumber";
}
$f[]="ldap_last_modification_attribute = modifyTimestamp";
$f[]="ldap_object_search_filter =(|(mail=%s*)(uid=%s*)(cn=*%s*)(sAMAccountName=*%s*)(fullname=*%s*)(givenname=*%s*)(lastname=*%s*)(sn=*%s*)) ";
$f[]="ldap_filter_cutoff_elements = 1000";
$f[]="ldap_addresslist_search_base = $prefixAddresses$ldap->suffix";
$f[]="ldap_addresslist_scope = sub";
$f[]="ldap_addresslist_search_filter = $ldap_addresslist_search_filter";
$f[]="ldap_addresslist_unique_attribute = cn";
$f[]="ldap_addresslist_unique_attribute_type = text";
$f[]="ldap_addresslist_filter_attribute = zarafaFilter";
$f[]="ldap_addresslist_name_attribute = cn";




if(is_file('/etc/zarafa/ldap.propmap.cfg')){
	$f[]="";
	$f[]="!propmap /etc/zarafa/ldap.propmap.cfg";
}
      


$f[]="";
$f[]="";
if(!is_dir("/etc/zarafa")){@mkdir("/etc/zarafa");}
@file_put_contents("/etc/zarafa/ldap.openldap.cfg",@implode("\n",$f));
echo "Starting zarafa..............: LDAP config done (".basename(__FILE__).")\n";

	
}
function remove_database(){
	$q=new mysql();
	$unix=new unix();
	
	$sock=new sockets();
	
	$ZarafaDedicateMySQLServer=$sock->GET_INFO("ZarafaDedicateMySQLServer");
	if(!is_numeric($ZarafaDedicateMySQLServer)){$ZarafaDedicateMySQLServer=0;}
	
	if($ZarafaDedicateMySQLServer==1){
		shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.zarafa-db.php --remove-database");
		return;
		
	}
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$q->DELETE_DATABASE("zarafa");
	if(!$q->ok){
		echo "Error while removing zarafa database...$q->mysql_error\n";
		return;
	}
	
	if(!is_dir($MYSQL_DATA_DIR)){
		echo "Failed to locate $MYSQL_DATA_DIR\n";
		return;
	}
	if(is_file("/etc/artica-postfix/ZARFA_FIRST_INSTALL")){@unlink("/etc/artica-postfix/ZARFA_FIRST_INSTALL");}
	$kill=$unix->find_program("kill");
	$pidof=$unix->find_program("pidof");
	$zarafa_server=$unix->find_program("zarafa-server");
	shell_exec("$kill -9 `$pidof $zarafa_server` >/dev/null 2>&1");
	
	
	echo "Starting zarafa..............: remove $MYSQL_DATA_DIR/ib_logfile*\n";
	shell_exec("/bin/rm -f $MYSQL_DATA_DIR/ib_logfile*");
	shell_exec("/bin/rm -f $MYSQL_DATA_DIR/ibdata*");
	echo "Starting zarafa..............: remove $MYSQL_DATA_DIR/zarafa*\n";
	shell_exec("/bin/rm -rf $MYSQL_DATA_DIR/zarafa");
	echo "Starting zarafa..............: restart MySQL\n";
	shell_exec("/etc/init.d/mysql restart >/tmp/zarafa_removedb 2>&1");
	echo "Starting zarafa..............: restart Zarafa server\n";
	shell_exec("/etc/init.d/zarafa-server restart >>/tmp/zarafa_removedb 2>&1");
	
	$unix->send_email_events("Success removing zarafa databases", 
	"removed $MYSQL_DATA_DIR/ib_logfile*\nremoved $MYSQL_DATA_DIR/ibdata*\nremoved $MYSQL_DATA_DIR/zarafa\n\n".@file_get_contents("/tmp/zarafa_removedb"), "mailbox");
}

function yaffas(){
	if(!is_file("/opt/yaffas/lib/perl5/Yaffas/Constant.pm")){return;}
	echo "Starting Yaffas..............: Checking Constant.pm\n";	
	$patch=false;
	$f=explode("\n", @file_get_contents("/opt/yaffas/lib/perl5/Yaffas/Constant.pm"));
	while (list ($num, $line) = each ($f) ){	
		if(preg_match("#case.+?Debian#", $line,$re)){echo "Starting Yaffas..............: Already patched\n";break;}
		
		if(preg_match("#case qr\/Ubuntu\/#", $line,$re)){
			echo "Starting Yaffas..............: Patching Constant.pm\n";	
			$patch=true;
			$f[$num]="\tcase qr/Ubuntu|Debian/ { return \"Ubuntu\"; }";
		}
		
	}
	
	if($patch){@file_get_contents("/opt/yaffas/lib/perl5/Yaffas/Constant.pm",@implode("\n", $f));}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	echo "Starting Yaffas..............: checking symbolic links...\n";
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/assets /opt/yaffas/webmin/yaffastheme/assets >/dev/null 2>&1");
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/config /opt/yaffas/webmin/yaffastheme/config  >/dev/null 2>&1");
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/globals.cgi /opt/yaffas/webmin/yaffastheme/globals.cgi  >/dev/null 2>&1");
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/index.cgi /opt/yaffas/webmin/yaffastheme/index.cgi >/dev/null 2>&1");
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/javascript /opt/yaffas/webmin/yaffastheme/javascript >/dev/null 2>&1");
	shell_exec("$ln -s /opt/yaffas/webmin/theme-core/session_login.cgi /opt/yaffas/webmin/yaffastheme/session_login.cgi >/dev/null 2>&1");
	echo "Starting Yaffas..............: Config done...\n";
	
	
	
}

function relinkto($from,$to){
	if($from==null){system_admin_events("Unhooking store failed, from is not specified", __FUNCTION__, __FILE__, __LINE__, "zarafa");return;}
	if($to==null){system_admin_events("Unhooking store failed, recipient is not specified", __FUNCTION__, __FILE__, __LINE__, "zarafa");return;}
	$unix=new unix();
	
	$kill=$unix->find_program("kill");
	$pids=$unix->PIDOF_PATTERN_ALL("zarafa-admin --list-orphans");
	if(count($pids)>0){
		while (list ($pid, $line) = each ($pids) ){
			$time=$unix->PROCESS_TTL($pid);
			if($time>15){
				$unix->_syslog("killing zarafa-admin --list-orphans pid $pid ({$time}mn)", basename(__FILE__));
				unix_system_kill_force($pid);
			}
	
		}
	
	}
	
	$pid=$unix->PIDOF_PATTERN("zarafa-admin --list-orphans");
	if($unix->process_exists($pid)){
		$unix->_syslog("zarafa-admin --list-orphans pid $pid still running", basename(__FILE__));
		return;
	}
	
	
	$zarafaadmin=$unix->find_program("zarafa-admin");
	$fromRegex=$from;
	$fromRegex=str_replace(".", "\.", $fromRegex);
	$store_guid=array();
	exec("$zarafaadmin --unhook-store \"$from\" 2>&1",$results);
	system_admin_events("Unhooking store for $from:\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "zarafa");
	$pattern="#([A-Z0-9]+)\s+$fromRegex\s+#";
	exec("$zarafaadmin --list-orphans 2>&1",$array);	
	while (list ($num, $line) = each ($array) ){
		if(preg_match($pattern, $line,$re)){
			$store_guid[]=$re[1];
			break;
		}
	}	
	
	if(count($store_guid)==0){
		system_admin_events("Failed, Unable to get unhooked store from $from !!!", __FUNCTION__, __FILE__, __LINE__, "zarafa");
		return;
	}
	while (list ($index, $storeid) = each ($store_guid) ){
		$results=array();
		exec("$zarafaadmin --hook-store $storeid -u $to --copyto-public 2>&1",$results);
		system_admin_events("hook store $storeid for $from to public folder of $to:\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "zarafa");
	}

}

function user_status_table(){
	if(isset($GLOBALS["user_status_table_executed"])){
		if($GLOBALS["VERBOSE"]){
			$trace=debug_backtrace();
			if(isset($trace[1])){
				$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				echo "user_status_table Already executed $called\n";
			}
		}
		return;
	}
	$GLOBALS["user_status_table_executed"]=true;
	$unix=new unix();
	$sock=new sockets();
	$timefile="/usr/share/artica-postfix/ressources/databases/ZARAFA_DB_STATUS.db";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	

	
	$mns=$unix->file_time_min($timefile);
	if($GLOBALS["VERBOSE"]){echo "$timefile = {$mns}Mn\n";}
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(basename(__FILE__))){
			system_admin_events("Overload system, aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
			return;
		}
		if($mns<180){return;}
		
		
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeProcess=$unix->PROCCESS_TIME_MIN($pid);
			system_admin_events("$pid, task is already executed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
			if($timeProcess<15){
				return;
			}
			
			$kill=$unix->find_program("kill");
			unix_system_kill_force($pid);
			system_admin_events("$pid, killed (since {$timeProcess}Mn}), aborting" , __FUNCTION__, __FILE__, __LINE__, "zarafa");
		}		
		
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$ZarafaIndexPath=$sock->GET_INFO("ZarafaIndexPath");
	$ZarafaStoreOutsidePath=$sock->GET_INFO("ZarafaStoreOutsidePath");
	$ZarafaMySQLServiceType=$sock->GET_INFO("ZarafaMySQLServiceType");
	if(!is_numeric($ZarafaMySQLServiceType)){$ZarafaMySQLServiceType=1;}
	// $ZarafaMySQLServiceType =1 ou 2 /var/lib/mysql
	// $ZarafaMySQLServiceType =3 --> dedicated instance  
	
	
	if($ZarafaIndexPath==null){$ZarafaIndexPath="/var/lib/zarafa/index";}
	if($ZarafaStoreOutsidePath==null){$ZarafaStoreOutsidePath="/var/lib/zarafa";}
	
	
	
	
	$ARRAY["ZARAFA_INDEX"]=$unix->DIRSIZE_BYTES($ZarafaIndexPath);
	if ( ($ZarafaMySQLServiceType==1) OR ($ZarafaMySQLServiceType==2) ){
		$ARRAY["ZARAFA_DB"]=$unix->DIRSIZE_BYTES("/var/lib/mysql");
	}
	
	
	if ( $ZarafaMySQLServiceType==3) {
		$WORKDIR=$sock->GET_INFO("ZarafaDedicateMySQLWorkDir");
		if($WORKDIR==null){$WORKDIR="/home/zarafa-db";}
		$ARRAY["ZARAFA_DB"]=$unix->DIRSIZE_BYTES($WORKDIR);
	}
	
	$ARRAY["ATTACHS"]=$unix->DIRSIZE_BYTES($ZarafaStoreOutsidePath);
	
	
	@file_put_contents($timefile, serialize($ARRAY));
	@chmod($timefile,0750);
	unset($ARRAY);
	$zarafaadmin=$unix->find_program("zarafa-admin");	
	
	
	$kill=$unix->find_program("kill");
	$pids=$unix->PIDOF_PATTERN_ALL("zarafa-admin -l");
	if(count($pids)>0){
		while (list ($pid, $line) = each ($pids) ){
			$time=$unix->PROCESS_TTL($pid);
			if($time>15){
				$unix->_syslog("killing zarafa-admin -l pid $pid ({$time}mn)", basename(__FILE__));
				unix_system_kill_force($pid);
			}
	
		}
	
	}
	
	$pid=$unix->PIDOF_PATTERN("zarafa-admin -l");
	if($unix->process_exists($pid)){
		$unix->_syslog("zarafa-admin -l pid $pid still running", basename(__FILE__));
	}	
	
	
	if($GLOBALS["VERBOSE"]){echo "$zarafaadmin -l 2>&1\n--------------------------------------------------------------------\n";}
	exec("$zarafaadmin -l 2>&1",$results);
	
	
	
	while (list ($num, $line) = each ($results) ){
		$line=trim($line);
		if($GLOBALS["VERBOSE"]){echo "\"$line\"\n";}
		if(preg_match("#User list for\s+(.+?)\(#i",$line,$re)){$ou=$re[1];continue;}
		if(preg_match("#Username#", $line)){continue;}
		if(preg_match("#SYSTEM#", $line)){continue;}
		if(preg_match("#^(.+?)\s+.+?#", $line,$re)){
			if($GLOBALS["VERBOSE"]){echo "\"$ou\" -> \"{$re[1]}\" -> \"_user_status_table_info({$re[1]},$zarafaadmin)\"\n";}
			$array[$ou][$re[1]]=_user_status_table_info($re[1],$zarafaadmin);
		}
	}
	
	$q=new mysql();

	if(!$q->TABLE_EXISTS('zarafauserss','artica_events')){	
		$sql="CREATE TABLE IF NOT EXISTS `zarafauserss` (
			  `zmd5` varchar(90) NOT NULL PRIMARY KEY,
			  `uid` varchar(128) NOT NULL,
			  `ou` varchar(128) NOT NULL,
			  `mail` varchar(255) NOT NULL,
			  `license` smallint(1) NOT NULL,
			  `NONACTIVETYPE` varchar(60) NOT NULL,
			  `storesize` BINT(100) UNSIGNED NOT NULL,
			  KEY `uid` (`uid`),
			  KEY `ou` (`ou`),
			  KEY `mail` (`mail`),
			  KEY `license` (`license`),
			  KEY `NONACTIVETYPE` (`NONACTIVETYPE`),
			  KEY `storesize` (`storesize`)
			) ";
		$q->QUERY_SQL($sql,'artica_events');	
		if(!$q->ok){echo $q->mysql_error."\n";return;}	
		
	}
		
		$prefix="INSERT IGNORE INTO zarafauserss (zmd5,uid,ou,mail,license,NONACTIVETYPE,storesize) VALUES ";
		while (list ($ou, $members) = each ($array) ){	
			while (list ($uid, $main) = each ($members) ){	
				$md5=md5("$uid$ou");
				if(!isset($main["NONACTIVETYPE"])){$main["NONACTIVETYPE"]='';}
				if($GLOBALS["VERBOSE"]){echo "\"('$md5','$uid','$ou','{$main["MAIL"]}','{$main["ACTIVE"]}','{$main["NONACTIVETYPE"]}','{$main["STORE_SIZE"]}')\n";}
				$f[]="('$md5','$uid','$ou','{$main["MAIL"]}','{$main["ACTIVE"]}','{$main["NONACTIVETYPE"]}','{$main["STORE_SIZE"]}')";
			}	
				
		}
			
		
	if(count($f)==0){return;}
		
	$q->QUERY_SQL("TRUNCATE TABLE zarafauserss","artica_events");
	$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	if($GLOBALS["VERBOSE"]){echo "FINISH\n--------------------------------------------------------------------\n";}
}

function _user_status_table_info($uid,$zarafaadmin){
	
	
	
	$array=array();
	if($GLOBALS["VERBOSE"]){echo "$zarafaadmin --details \"$uid\" 2>&1\n";}
	exec("$zarafaadmin --details \"$uid\" 2>&1",$results);
	while (list ($num, $line) = each ($results) ){
		if(preg_match("#Emailaddress:\s+(.+)#", $line,$re)){$array["MAIL"]=trim($re[1]);continue;}
		if(preg_match("#Active:\s+(.+)#i", $line,$re)){
			$res=0;
			$active=trim(strtolower($re[1]));
			if($GLOBALS["VERBOSE"]){echo "$uid: Active: $active\n";}
			if(is_numeric($active)){$res=$active;}else{if($active=="yes"){$res=1;}}
			if($GLOBALS["VERBOSE"]){echo "$uid: Active: $res\n";}
			$array["ACTIVE"]=$res;continue;
		}
		
		if(preg_match("#Non-active type:\s+(.+)#i", $line,$re)){
			$array["NONACTIVETYPE"]=trim($re[1]);continue;
		}
		
		if(preg_match("#Current store size:\s+([0-9\.\,\s]+)\s+([A-Z]+)#i", $line,$re)){
			$size = intval(trim($re[1]));
			$unit=trim($re[2]);
			if($GLOBALS["VERBOSE"]){echo "$uid: Found $size -> Unit $unit\n";}
			
			if($unit=="MB"){$size=$size*1000;$size=$size*1024;$unit="B";}
			if($unit=="KB"){$size=$size*1024;$unit="B";}
			if($unit=="GB"){$size=$size*1000;$size=$size*1000;$size=$size*1024;}
			if($unit=="MiB"){$size=$size*1000;$size=$size*1024;$unit="B";}
			if($GLOBALS["VERBOSE"]){echo "$uid: OK $size -> Unit:$unit Bytes\n";}
			
			$array["STORE_SIZE"]=$size;continue;
		}
		

	}

	
return $array;	
	
	
}



?>