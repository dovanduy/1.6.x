<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";die();}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');


if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--register"){register();die();}
if($argv[1]=="--uuid"){uuid_check();die();}
if($argv[1]=="--register-lic"){register_lic();die();}


if($argv[1]=="--uuid"){$sock=new sockets();echo base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"))."\n";die();}
if(!ifMustBeExecuted()){die();}
if($argv[1]=="--patterns"){die();}
if($argv[1]=="--sitesinfos"){die();}
if($argv[1]=="--groupby"){die();}
if($argv[1]=="--import"){import();die();}
if($argv[1]=="--export"){export(true);die();}
if($argv[1]=="--export-deleted"){export_deleted_categories(true);die();}
if($argv[1]=="--export-weighted"){Export_Weighted(true);die();}
if($argv[1]=="--export-perso-cats"){ExportPersonalCategories(true);die();}
if($argv[1]=="--export-not-categorized"){ExportNoCategorized(true);die();}





	$t=time();
	$sock=new sockets();
	$users=new usersMenus();
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if($EnableRemoteStatisticsAppliance==1){if($GLOBALS["VERBOSE"]){echo "Use the Web statistics appliance aborting...\n";}die();}
	$EnableSquidRemoteMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL");
	if(!is_numeric($EnableSquidRemoteMySQL)){$EnableSquidRemoteMySQL=0;}
	if($EnableSquidRemoteMySQL==1){die();}
	
	
	$system_is_overloaded=system_is_overloaded();
	if($system_is_overloaded){
		$unix=new unix();
		WriteMyLogs("Overloaded system, [{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}] Web filtering maintenance databases tasks aborted (general)","MAIN",__FILE__,__LINE__);
		$unix->send_email_events("Overloaded system, [{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}] Web filtering maintenance databases tasks aborted (general)",
		 "Artica will wait a new better time...", "proxy");
		die();
	}
	

	$WebCommunityUpdatePool=$sock->GET_INFO("WebCommunityUpdatePool");
	if(!is_numeric($WebCommunityUpdatePool)){$WebCommunityUpdatePool=360;$sock->SET_INFO("WebCommunityUpdatePool",360);}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$myFile=basename(__FILE__);	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){
		
		WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);
		die();
	}
	
	$filetime=file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){
		if($filetime<$WebCommunityUpdatePool){WriteMyLogs("{$filetime}Mn need {$WebCommunityUpdatePool}Mn, aborting...",__FUNCTION__,__FILE__,__LINE__);die();}
	}
	
	WriteMyLogs("-> EXECUTE....","MAIN",__FILE__,__LINE__);
	@mkdir(dirname($cachetime),0755,true);
	@unlink($cachetime);
	@file_put_contents($cachetime,"#");
	$GLOBALS["MYPID"]=getmypid();
	@file_put_contents($pidfile,$GLOBALS["MYPID"]);
	
	WriteMyLogs("-> Export()","MAIN",null,__LINE__);
	Export();
	WriteMyLogs("-> Import()","MAIN",null,__LINE__);
	import();
	

	$distanceOfTimeInWords=$unix->distanceOfTimeInWords($t,time());
	$unix->send_email_events("Web filtering maintenance databases tasks success",
		 "Exporting websites, importing websites calculate categories took $distanceOfTimeInWords", "proxy");
	
	
function register(){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$WizardSavedSettingsSend=$sock->GET_INFO("WizardSavedSettingsSend");
	if(count($WizardSavedSettings)<2){return;}
	if(!isset($WizardSavedSettings["company_name"])){$WizardSavedSettings["company_name"]=null;}
	if($WizardSavedSettings["company_name"]==null){return;}
	
	if(!is_numeric($WizardSavedSettingsSend)){$WizardSavedSettingsSend=0;}
	if($WizardSavedSettingsSend==1){return;}
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$WizardSavedSettings["UUID"]=$uuid;
	$WizardSavedSettings["CPUS_NUMBER"]=XZCPU_NUMBER();
	$WizardSavedSettings["MEMORY"]=$unix->SYSTEM_GET_MEMORY_MB()."MB";
	$WizardSavedSettings["LINUX_DISTRI"]=$unix->LINUX_DISTRIBUTION();
	
	if(is_file("/etc/artica-postfix/dmidecode.cache.url")){
		$final_array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/dmidecode.cache.url")));
		while (list ($a, $b) = each ($final_array)){
			$WizardSavedSettings[$a]=$b;
		}
	}
	
	@file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", base64_encode(serialize($WizardSavedSettings)));
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["REGISTER"]=base64_encode(serialize($WizardSavedSettings));
	$curl->get();
	echo $curl->data;
	
	if(preg_match("#GOOD#s", $curl->data)){
		$sock->SET_INFO("WizardSavedSettingsSend", 1);
	}
	
	
}	

function XZCPU_NUMBER(){
	$unix=new unix();
	$cat=$unix->find_program("cat");
	$grep=$unix->find_program("grep");
	$cut=$unix->find_program("cut");
	$wc=$unix->find_program("wc");
	$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
	$CPUNUM=exec($cmd);
	
	return $CPUNUM;
}

function uuid_check(){
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	echo $uuid."\n";
}

function CheckLic($array1=array(),$array2=array()){
	$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$WORKFILE=base64_decode('LmxpYw==');
	$WORKPATH="$WORKDIR/$WORKFILE";
	$sock=new sockets();	
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["REGISTER-LIC"]=base64_encode(serialize($array1));
	$curl->parms["REGISTER-OLD"]=base64_encode(serialize($array2));
	$curl->get();

	if(preg_match("#REGISTRATION_DELETE_NOW#s", $curl->data,$re)){
		@unlink($WORKPATH);
		$array1["license_status"]="{license_invalid}";
		$array1["license_number"]=null;
		$array1["UNLOCKLIC"]=null;
		$array1["TIME"]=time();
		$sock->SaveConfigFile(base64_encode(serialize($array1)), "LicenseInfos");
		return;
	}	
	
}

function register_lic(){
	$sock=new sockets();
	$unix=new unix();
	$WORKDIR=base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2E=");
	$WORKFILE=base64_decode('LmxpYw==');
	$WORKPATH="$WORKDIR/$WORKFILE";
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){echo "License information: Already executed PID:$pid, die()\n";die();}
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	$cmdADD=null;
	if($EnableRemoteStatisticsAppliance==1){
		$cmdADD="$nohup ".$unix->LOCATE_PHP5_BIN()." ".dirname(__FILE__)."/exec.netagent.php >/dev/null 2>&1 &";
	}
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	
	$LicenseInfos["COMPANY"]=str_replace("%uFFFD", "é", $LicenseInfos["COMPANY"]);
	
	
	
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	if(!is_numeric($LicenseInfos["REGISTER"])){echo "License information: server is not registered\n";}
	if($LicenseInfos["REGISTER"]<>1){echo "License information: server is not registered\n";die();}	
	$LicenseInfos["UUID"]=$uuid;

	
	
	
	
	//if($GLOBALS["VERBOSE"]){$curl->parms["VERBOSE"]="yes";}
	if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."\n";}
	if($LicenseInfos["license_number"]=="--"){$LicenseInfos["license_number"]=null;}
	
	if(strpos($LicenseInfos["license_number"], "(")>0){$LicenseInfos["license_number"]=null;}
	@mkdir($WORKDIR,640,true);
	
	
	if(isset($LicenseInfos["UNLOCKLIC"])){
		if(strlen($LicenseInfos["UNLOCKLIC"])>4){
			if(isset($LicenseInfos["license_number"])){
				if(strlen($LicenseInfos["license_number"])>4){
					$manulic=aef00vh567($uuid)."-".aef00vh567($LicenseInfos["license_number"]);
					if($manulic==$LicenseInfos["UNLOCKLIC"]){
						@file_put_contents($WORKPATH, "TRUE");
						$LicenseInfos["license_status"]="{license_active}";
						$LicenseInfos["TIME"]=time();
						$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
						if($cmdADD<>null){shell_exec($cmdADD);}
						CheckLic($LicenseInfos,$WizardSavedSettings);
						return;
					}
				}
			}
				
		}
	}	
	
	
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["REGISTER-LIC"]=base64_encode(serialize($LicenseInfos));
	$curl->parms["REGISTER-OLD"]=base64_encode(serialize($WizardSavedSettings));
	$curl->get();
	
	if(preg_match("#REGISTRATION_OK:\[(.+?)\]#s", $curl->data,$re)){
			$LicenseInfos["license_status"]="{waiting_approval}";
			$LicenseInfos["license_number"]=$re[1];
			$LicenseInfos["TIME"]=time();
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			@unlink($WORKPATH);
			if($cmdADD<>null){shell_exec($cmdADD);}
			return;
	}
	if(preg_match("#LICENSE_OK:\[(.+?)\]#s", $curl->data,$re)){
			@file_put_contents($WORKPATH, "TRUE");
			$LicenseInfos["license_status"]="{license_active}";
			$LicenseInfos["TIME"]=time();
			$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
			if($cmdADD<>null){shell_exec($cmdADD);}
			return;
	}
	if(preg_match("#REGISTRATION_INVALID#s", $curl->data,$re)){
		@unlink($WORKPATH);
		$LicenseInfos["license_status"]="{license_invalid}";
		$LicenseInfos["license_number"]=null;
		$LicenseInfos["UNLOCKLIC"]=null;
		$LicenseInfos["TIME"]=time();
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		if($cmdADD<>null){shell_exec($cmdADD);}
		return;
	}	

	if(preg_match("#REGISTRATION_DELETE_NOW#s", $curl->data,$re)){
		@unlink($WORKPATH);
		$LicenseInfos["license_status"]="{license_invalid}";
		$LicenseInfos["license_number"]=null;
		$LicenseInfos["UNLOCKLIC"]=null;
		$LicenseInfos["TIME"]=time();
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
		return;
	}	
		
	if($curl->error<>null){
		system_admin_events("License registration failed with error $curl->error", "GetLicense", "license", 0, "license");
	}
	if(!is_file($WORKPATH)){
		$LicenseInfos["TIME"];
		$LicenseInfos["license_status"]="{registration_failed} $curl->error";
		$sock->SaveConfigFile(base64_encode(serialize($LicenseInfos)), "LicenseInfos");
	}
	if($cmdADD<>null){shell_exec($cmdADD);}
}
	
function ExportPersonalCategories($asPid=false){
	$unix=new unix();
	$restartProcess=false;
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$restart_cmd=trim("$nohup $php5 ".__FILE__." --export >/dev/null 2>&1 &");
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}

	$q=new mysql_squid_builder();
	$sql="SELECT * FROM personal_categories WHERE sended=0";
	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return;}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$PERSONALSCATS[$ligne["category"]]["DESC"]=$ligne["category_description"];
		$PERSONALSCATS[$ligne["category"]]["UUID"]=$uuid;
	}	
	
	WriteMyLogs("Exporting ". count($PERSONALSCATS)." personal category",__FUNCTION__,__FILE__,__LINE__);
	$f=base64_encode(serialize($PERSONALSCATS));
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["PERSO_CAT_POST"]=$f;

	if(!$curl->get()){
		writelogs("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		$unix->send_email_events("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",null,"proxy");
		writelogs_squid("Failed exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
		return null;
	}

	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("Exporting success ". count($PERSONALSCATS)." personal categories",__FUNCTION__,__FILE__,__LINE__);
		writelogs_squid("Success exporting ".count($PERSONALSCATS)." personal categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");	
		$q->QUERY_SQL("UPDATE personal_categories SET sended=1 WHERE sended=0");
	}
	
	
}	

function export_deleted_categories($asPid=false){
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if($asPid){
		
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		
	}	
	
	@file_put_contents($pidfile,getmypid());
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	$q=new mysql_squid_builder();
	$ALLCOUNT=$q->COUNT_ROWS("categorize_delete");
	if($ALLCOUNT==0){return;}
	

	
	$results=$q->QUERY_SQL("SELECT * FROM categorize_delete");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		if($ligne["sitename"]==null){continue;}
		if($ligne["zmd5"]==null){continue;}
		
		$array[$ligne["zmd5"]]=array(
				"category"=>$ligne["category"],
				"sitename"=>$ligne["sitename"],
			    "uuid"=>$uuid
		);
	}


	
	$f=base64_encode(serialize($array));
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["COMMUNITY_POST_CATEGORIES_DELETE"]=$f;

	if(!$curl->get()){
		writelogs("Failed exporting ".count($array)." deleted websites from categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		$unix->send_email_events("Failed exporting ".count($array)." deleted websites from categories to Artica cloud repository servers",null,"proxy");
		writelogs_squid("Failed exporting ".count($array)." deleted websites from categories to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
		return null;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("Exporting success ". count($array)." deleted websites from categories",__FUNCTION__,__FILE__,__LINE__);
		writelogs_squid("Success exporting ".count($array)." deleted websites from categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");
		$q->QUERY_SQL("TRUNCATE TABLE categorize_delete");
	}

}

function ExportNoCategorized($asPid=false){

	$unix=new unix();
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}	
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM visited_sites WHERE LENGTH(category)=0 AND NotVisitedSended=0 LIMIT 0,5000";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){if(strpos($q->mysql_error, "Unknown column 'NotVisitedSended'")>0){$q->CheckTables();}$results=$q->QUERY_SQL($sql);}
	if(mysql_num_rows($results)==0){return;}
	
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5("$uuid{$ligne["sitename"]}{$ligne["familysite"]}");
		$ligne["sitename"]=addslashes($ligne["sitename"]);
		$array[]="('$md5','$uuid','{$ligne["sitename"]}','{$ligne["HitsNumber"]}','{$ligne["familysite"]}')";
		
	}
	$f=base64_encode(serialize($array));
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	
	if($GLOBALS["VERBOSE"]){echo "COMMUNITY_POST_VISITED = array of ". count($array)." elements\n";}
	$curl->parms["COMMUNITY_POST_VISITED"]=$f;
	if(!$curl->get()){
		writelogs("Failed exporting ".count($array)." not categorized websites from categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		$unix->send_email_events("Failed exporting ".count($array)." not categorized websites from categories to Artica cloud repository servers",null,"proxy");
		writelogs_squid("Failed exporting ".count($array)." Not categorized websites from categories to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
		return null;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		if($GLOBALS["VERBOSE"]){echo "Success...\n";}
		WriteMyLogs("Exporting success ". count($array)." Not categorized websites from categories",__FUNCTION__,__FILE__,__LINE__);
		writelogs_squid("Success exporting ".count($array)." Not categorized websites from categories to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");
		$q->QUERY_SQL("UPDATE visited_sites SET NotVisitedSended=1 WHERE LENGTH(category)=0 AND NotVisitedSended=0 LIMIT 5000");
	}	
	
	if($GLOBALS["VERBOSE"]){echo "Returned datas:\n\n$curl->data\n\n";}
	
	
}

function Export_Weighted(){
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_WEIGHTED();
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	$count=count($tables);
	$unix=new unix();
	echo count($tables)." tables\n";
	while (list ($table, $www) = each ($tables)){
		$c++;
		echo "Push $table $c/$count\n";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1"));
		if($ligne["tcount"]==0){continue;}
		$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 and enabled=1 ORDER BY zDate LIMIT 0,1000");
		$array=array();
		while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne2["category"]==null){continue;}
			if($ligne2["pattern"]==null){continue;}
			if($ligne2["zmd5"]==null){continue;}		
			$array[$ligne2["zmd5"]]=array("category"=>$ligne2["category"],"pattern"=>$ligne2["pattern"],"score"=>$ligne2["score"],"uuid"=>$ligne2["uuid"]);	
						
			
		}

		if(!is_array($array)){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}
		if(count($array)==0){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}	
		$f=base64_encode(serialize($array));
		$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
		echo "Push $table -> " .count($array)." entries\n";
		$curl->parms["WEIGHTED_POST"]=$f;
		
		if(!$curl->get()){
			writelogs("Failed exporting ".count($array)." weighted patterns to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
			$unix->send_email_events("Failed exporting ".count($array)." weighted patterns to Artica cloud repository servers",null,"proxy");
			writelogs_squid("Failed exporting ".count($array)." weighted patterns to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
			return null;
		}
		
		
		if($GLOBALS["VERBOSE"]){echo $curl->data;}
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
			WriteMyLogs("Exporting success ". count($array)." weighted",__FUNCTION__,__FILE__,__LINE__);
			if(count($logsExp)<10){$textadd=@implode(",", $logsExp);}
			writelogs_squid("Success exporting ".count($array)." weighted patterns to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");
			writelogs("Deleting export tasks...",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0 ORDER BY zDate LIMIT 1000");
	
		}
	
	
	}	
	
	
}

	
function Export($asPid=false){
	$unix=new unix();
	$restartProcess=false;
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$restart_cmd=trim("$nohup $php5 ".__FILE__." --export >/dev/null 2>&1 &");
	$sock=new sockets();
	
	shell_exec(trim("$nohup $php5 ".__FILE__." --export-not-categorized >/dev/null 2>&1 &"));
	
	if($asPid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$unix=new unix();	
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
		@file_put_contents($pidfile,getmypid());
	}
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	export_deleted_categories();
	$q=new mysql_squid_builder();
	$tables=$q->LIST_TABLES_CATEGORIES();
	while (list ($table, $www) = each ($tables)){
		$limit=null;
		$limitupate=null;
		$sql="SELECT COUNT(zmd5) as tcount FROM $table WHERE sended=0 and enabled=1";
		$q->CreateCategoryTable(null,$table);
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$prefix="INSERT IGNORE INTO categorize (zmd5 ,pattern,zDate,uuid,category) VALUES";
		if($ligne["tcount"]>0){
			writelogs("$table {$ligne["tcount"]} items to export",__FUNCTION__,__FILE__,__LINE__);
			if($ligne["tcount"]>5000){$limit="LIMIT 0,5000";$limitupate="LIMIT 5000";}
			$results=$q->QUERY_SQL("SELECT * FROM $table WHERE sended=0 AND enabled=1 $limit");
			while($ligne2=mysql_fetch_array($results,MYSQL_ASSOC)){
				$md5=md5("{$ligne2["category"]}{$ligne2["pattern"]}");
				$f[]="('$md5','{$ligne2["pattern"]}','{$ligne2["zDate"]}','$uuid','{$ligne2["category"]}')";
				$c++;
				if(count($f)>1000){
					$q->QUERY_SQL($prefix.@implode(",",$f));
					if(!$q->ok){echo $q->mysql_error."\n";return;}
					$f=array();
				}
				
			}
		$q->QUERY_SQL("UPDATE $table SET sended=1 WHERE sended=0 $limitupate");
		}
		
	}	
	
	if(count($f)>0){$q->QUERY_SQL($prefix.@implode(",",$f));$f=array();	}
			
	
	$ALLCOUNT=$q->COUNT_ROWS("categorize");
	if($GLOBALS["VERBOSE"]){echo "Total row in categorize table: $ALLCOUNT\n";}
	if($ALLCOUNT>2000){$restartProcess=true;}
	$sql="SELECT * FROM categorize ORDER BY zDate DESC LIMIT 0,2000";
	if($GLOBALS["VERBOSE"]){echo "Execute query\n";}
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql\n";return;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		if($ligne["pattern"]==null){continue;}
		if($ligne["zmd5"]==null){continue;}
		$logsExp[]="{$ligne["pattern"]}:{$ligne["category"]}";
		$array[$ligne["zmd5"]]=array(
				"category"=>$ligne["category"],
				"pattern"=>$ligne["pattern"],
			    "uuid"=>$ligne["uuid"]
		);
	}

if(!is_array($array)){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}
if(count($array)==0){WriteMyLogs("Nothing to export",__FUNCTION__,__FILE__,__LINE__);return;}

$WHITELISTED["1636b7346f2e261c5b21abfcaef45a69"]=true;
$WHITELISTED["8cdd119c-2dc1-452d-b9d0-451c6046464f"]=true;

	if(!isset($WHITELISTED[$uuid])){
		if(count($array)>500){
			$q->QUERY_SQL("TRUNCATE TABLE categorize_delete");
			writelogs_squid("Too much categories to export ".count($array).">500, aborting",__FUNCTION__,__FILE__,__LINE__,"export");
		}
	}	

	WriteMyLogs("Exporting ". count($array)." websites",__FUNCTION__,__FILE__,__LINE__);
	$f=base64_encode(serialize($array));
	if($GLOBALS["VERBOSE"]){echo "Sending ". strlen($f)." bytes to repository server\n";}
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["COMMUNITY_POST"]=$f;
	
	if(!$curl->get()){
		writelogs("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__);
		$unix->send_email_events("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers",null,"proxy");
		writelogs_squid("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers \"$curl->error\"",__FUNCTION__,__FILE__,__LINE__,"export");
		return null;
	}
	
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("Exporting success ". count($array)." websites",__FUNCTION__,__FILE__,__LINE__);
		if(count($logsExp)<10){$textadd=@implode(",", $logsExp);}
		writelogs_squid("Success exporting ".count($array)." categorized websites to Artica cloud repository servers",__FUNCTION__,__FILE__,__LINE__,"export");
		$curl=new ccurl("http://www.artica.fr/webfilters-instant.php?checks=yes");
		$curl->NoHTTP_POST=true;
		if(!$curl->get()){
			writelogs_squid("Failed to order to build webfilter instant with HTTP ERROR: `$curl->error`",__FUNCTION__,__FILE__,__LINE__,"export");
		}
		
		if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
			writelogs_squid("Success to order to build webfilter instant",__FUNCTION__,__FILE__,__LINE__,"export");
		}else{
			writelogs_squid("Failed to order to build webfilter instant ANSWER NOT OK in server response.",__FUNCTION__,__FILE__,__LINE__,"export");
			if($GLOBALS["VERBOSE"]){echo $curl->data;}
		}
		
		writelogs("Deleting websites...",__FUNCTION__,__FILE__,__LINE__);
		while (list ($md5, $datas) = each ($array) ){
			$sql="DELETE FROM categorize WHERE zmd5='$md5'";
			$q->QUERY_SQL($sql,"artica_backup");
		}
		
		if($restartProcess){
			writelogs("$restart_cmd",__FUNCTION__,__FILE__,__LINE__);
			shell_exec($restart_cmd);
		}else{
			$q->QUERY_SQL("OPTIMIZE TABLE categorize","artica_backup");
		}
	}else{
		WriteMyLogs("Failed exporting ".count($array)." categorized websites to Artica cloud repository servers \"$curl->data\"",__FUNCTION__,__FILE__,__LINE__,"export");
	}
	
	
	
}



function pushit(){
	$curl=new ccurl("http://www.artica.fr/shalla-orders.php");
	$curl->parms["ORDER_EXPORT"]="yes";
	$curl->get();
	if(preg_match("#<ANSWER>OK</ANSWER>#is",$curl->data)){
		WriteMyLogs("success",__FUNCTION__,__FILE__,__LINE__);
	}else{
		WriteMyLogs("failed\n$curl->data" ,__FUNCTION__,__FILE__,__LINE__);	
	}
}

function import(){return;}

function ParseGzSqlFile($filepath){
	
	
	if($GLOBALS["MYSQLCOMMAND"]==null){
		$unix=new unix();
		$mysql=$unix->find_program("mysql");
		$q=new mysql();
		if($q->mysql_password<>null){
			$password=" --password=$q->mysql_password";
		}
		$nice=EXEC_NICE();
		$cmd="$nice$mysql --batch --user=$q->mysql_admin $password --port=$q->mysql_port";
		$cmd=$cmd." --host=$q->mysql_server --database=artica_backup";
		$cmd=$cmd." --max_allowed_packet=500M";
		$GLOBALS["MYSQLCOMMAND"]=$cmd;
	}else{
		$cmd=$GLOBALS["MYSQLCOMMAND"];
	}
	
	//echo $cmd." <$filepath\n";
	echo "Starting......: [ParseGzSqlFile]:: Artica database community running importation (". basename($filepath).")\n";
	exec("$cmd <$filepath 2>&1",$results);
	
	
	
	if(count($results)>0){
		while (list ($num, $ligne) = each ($results) ){
			if(!preg_match("#Duplicate entry#",$ligne)){
				echo "Starting......: Artica database community $ligne\n";
				if(preg_match("#ERROR\s+[0-9]+#",$ligne)){
					echo "Starting......: Artica database community error detected\n";
					$GLOBALS["NEWFILES"][]=$ligne;
					$unix->send_email_events("Web community mysql error", "Unable to import data file $filepath\n$ligne","proxy");
					return false;
				}
			}
		}
	}
	return true;
	@unlink($filepath);
	
}


function uncompress($srcName, $dstName) {
	$string = implode("", gzfile($srcName));
	$fp = fopen($dstName, "w");
	fwrite($fp, $string, strlen($string));
	fclose($fp);
} 
	



function WriteCategory($category){
	$squidguard=new squidguard();
	$q=new mysql_squid_builder();
	echo "Starting......: Artica database writing category $category\n";
	echo "Starting......: Artica database /etc/dansguardian/lists/blacklist-artica/$category/domains\n";
	echo "Starting......: Artica database /var/lib/squidguard/blacklist-artica/$category\n";
	@mkdir("/etc/dansguardian/lists/blacklist-artica/$category",0755,true);
	@mkdir("/var/lib/squidguard/blacklist-artica/$category",0755,true);
	
	if(!is_dir("/var/lib/squidguard/$category")){@mkdir("/var/lib/squidguard/$category",0755,true);}
	if(!is_dir("/etc/dansguardian/lists/blacklist/$category/urls")){@mkdir("/etc/dansguardian/lists/blacklist/$category/urls",755,true);}
	if(!is_file("/etc/dansguardian/lists/blacklist/$category/urls")){@file_put_contents("/etc/dansguardian/lists/blacklist/$category/urls","\n");}
	if(!is_file("/var/lib/squidguard/$category/urls")){@file_put_contents("/var/lib/squidguard/$category/urls","\n");}
	$tablesource="category_".$q->category_transform_name($category);	
	$sql="SELECT pattern FROM $tablesource WHERE enabled=1";
	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Starting......: Artica database $q->mysql_error\n";return;}
	$num=mysql_num_rows($results);
	echo "Starting......: Artica database $num domains\n";
	
	$domain_path_1="/etc/dansguardian/lists/blacklist/$category/domains";
	$domain_path_2="/var/lib/squidguard/$category/domains";
	$fh1 = fopen($domain_path_1, 'w+');
	$fh2 = fopen($domain_path_2, 'w+');
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["pattern"]==null){continue;}
		 if(!$squidguard->VerifyDomainCompiledPattern($ligne["pattern"])){continue;}
		 fwrite($fh1, $ligne["pattern"]."\n");
		 fwrite($fh2, $ligne["pattern"]."\n");
	}
	
	fclose($fh1);
	fclose($fh2);
	
	echo "Starting......: finish\n\n";
		
}



function GetCategory($www){
$q=new mysql_squid_builder();
return $q->GET_CATEGORIES($www);
}


function mycnf_get_value($key){
	$unix=new unix();
	$cnf=$unix->MYSQL_MYCNF_PATH();
	$f=explode("\n",@file_get_contents($cnf));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#$key(.*?)=(.*)#",$line,$re)){
			$re[2]=trim($re[2]);
			return $re[2];
			}
		}
	}


function mycnf_change_value($key,$value_to_modify){
	$unix=new unix();
	$value_to_modify=trim($value_to_modify);
	$cnf=$unix->MYSQL_MYCNF_PATH();
	$f=explode("\n",@file_get_contents($cnf));
	while (list ($index, $line) = each ($f) ){
		if(preg_match("#$key(.*?)=(.*)#",$line,$re)){
			$re[2]=trim($re[2]);
			echo "Starting......: Artica database community line $index $key = {$re[2]} change to $value_to_modify\n";
			$f[$index]="$key = $value_to_modify";
			$found=true;
			}
		}
	@file_put_contents($cnf,@implode("\n",$f));
	
	
	
	}
	

function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}
function ifMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}	
function aef00vh567($string){
	$ascii=NULL;
	$serial=NULL;
	$secret_num=1;
	$bds[33]=true;
	$bds[34]=true;
	$bds[35]=true;
	$bds[36]=true;
	$bds[37]=true;
	$bds[38]=true;
	$bds[39]=true;
	$bds[40]=true;
	$bds[41]=true;
	$bds[42]=true;
	$bds[43]=true;
	$bds[44]=true;
	$bds[45]=true;
	$bds[46]=true;
	$bds[47]=true;
	$bds[58]=true;
	$bds[59]=true;
	$bds[60]=true;
	$bds[61]=true;
	$bds[62]=true;
	$bds[63]=true;
	$bds[64]=true;
	$bds[91]=true;
	$bds[92]=true;
	$bds[93]=true;
	$bds[94]=true;
	$bds[95]=true;
	$bds[96]=true;




	for ($i = 0; $i < strlen($string); $i++)
	{
		$ascii .= $secret_num+ ord($string[$i]);
	}
	$ascii=substr($ascii,0,20);
	for ($i = 0; $i < strlen($ascii); $i+=2){
		$string=substr($ascii,$i,2);



		switch($string){
		 case $string>122:
				$string-=40;
				break;
			case $string<=48:
				$string+=40;
				break;
		}
		if(isset($bds[$string])){continue;}
		if($string>122){continue;}

		$serial .= chr($string);
	}
	return $serial;
}	


?>