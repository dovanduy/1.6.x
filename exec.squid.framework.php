<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.ufdbguard.inc');


if(!ifMustBeExecuted()){die();}

Execute();

function Execute(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$myFile=basename(__FILE__);
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,$myFile)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);die();}	
	
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS("framework_orders")==0){if($GLOBALS["VERBOSE"]){echo "Table framework_orders as no row\n";}die();}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$nice=EXEC_NICE();	
	
	
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT * FROM framework_orders");
	if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results=$q->QUERY_SQL("SELECT * FROM framework_orders");}}
	if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"framework");die();}
	$reconfigure_plugins=false;
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "ORDER: {$ligne["ORDER"]} -> {$ligne["zmd5"]}\n";}
		if(preg_match("#COMPILEDB:(.+)#", $ligne["ORDER"],$re)){
			if(preg_match("#english-(.+)#", $re[1])){$q->QUERY_SQL("DELETE FROM framework_orders WHERE zmd5='{$ligne["zmd5"]}'");continue;}
			
			ufdbguard_admin_events("LAUNCH: category {$re[1]} compilation",__FUNCTION__,__FILE__,__LINE__,"framework");
			$re[1]=trim($re[1]);
			$table="category_".$q->category_transform_name($re[1]);
			if($GLOBALS["VERBOSE"]){echo "order to compile database {$re[1]} (table $table)\n";}
			if(!$q->TABLE_EXISTS($table)){
				ufdbguard_admin_events("Fatal: $table no suche table, create it",__FUNCTION__,__FILE__,__LINE__,"framework");
				$q->CreateCategoryTable(null,$table);
			}
			
			$cmd="$nice $php5 /usr/share/artica-postfix/exec.squidguard.php --compile-category \"{$re[1]}\"";
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$q->QUERY_SQL("DELETE FROM framework_orders WHERE zmd5='{$ligne["zmd5"]}'");
			if(!$q->ok){ufdbguard_admin_events("Fatal: $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"framework");die();}
			shell_exec($cmd);
			$reconfigure_plugins=true;
		}
		
	}	
	
	if($reconfigure_plugins){
		ufdbguard_admin_events("LAUNCH: filters reconfiguration",__FUNCTION__,__FILE__,__LINE__,"framework");
		shell_exec("$nice $php5 /usr/share/artica-postfix/exec.squidguard.php --build");
	}
	
	
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
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}

function ifMustBeExecuted(){
	$users=new usersMenus();
	$sock=new sockets();
	$update=true;
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if(!is_numeric($CategoriesRepositoryEnable)){$CategoriesRepositoryEnable=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($EnableWebProxyStatsAppliance==1){return true;}	
	$CategoriesRepositoryEnable=$sock->GET_INFO("CategoriesRepositoryEnable");
	if($CategoriesRepositoryEnable==1){return true;}
	if(!$users->SQUID_INSTALLED){$update=false;}
	return $update;
}