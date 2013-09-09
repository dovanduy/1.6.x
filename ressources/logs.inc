<?php
if(function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get"))
	@date_default_timezone_set(@date_default_timezone_get());
function WriteToSyslogMail($text,$file=null,$error=false){
	if(!function_exists("syslog")){return;}
	if($file==null){
		if(function_exists("debug_backtrace")){
			$trace=debug_backtrace();
			$file=basename($trace[1]["file"]);
		}
	}
	$file=basename($file);
	if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
	openlog($file, LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
	}


function writeToFile($text,$filepath){
	$f = @fopen($filepath, 'w');
	@fwrite($f,$text);
	@fclose($f);
}
function writelogs($text=null,$function=null,$file_source=null,$line=null){
	$name=null;
	$ARRAY_NET["listener.php"]=true;
	$file_source=basename($file_source);
	$lineToSave="$text in $file_source function $function line $line";
	if($file_source=="splashproxy.php"){
		$servername=$_SERVER["SERVER_NAME"];
		$f = @fopen("/var/log/apache2/$servername/$file_source.log", 'a');
		@fwrite($f, "$lineToSave\n");
		@fclose($f);
	}
	
	if(isset($ARRAY_NET[$file_source])){
		$servername=$_SERVER["SERVER_NAME"];
		$from=$_SERVER["REMOTE_ADDR"];
		$lineToSave=date('H:i:s')." [$from] $file_source $text function $function line $line";
		$f = @fopen("/var/log/artica-netagent.log", 'a');
		@fwrite($f, "$lineToSave\n");
		@fclose($f);
		
	}
	
	if(isset($_SERVER["SERVER_SOFTWARE"])){
		if(preg_match("#^Apache#", $_SERVER["SERVER_SOFTWARE"])){
			error_log("$text in $file_source function $function line $line");
			return;
		}
	}
	

	
	if(preg_match("#netagent#", $file_source)){
		WriteToSyslogMail("[$function::$line]: $text","netagent");
	}
	
	if(!isset($GLOBALS["VERBOSE"])){$GLOBALS["VERBOSE"]=false;}
	if(isset($_SESSION["uid"])){$name="[{$_SESSION["uid"]}]";}
	if($file_source==null){$file_source= __FILE__ ;}
	$prefix="[CONSOLE]::";
	if(function_exists("posix_getuid")){if(posix_getuid()==0){$prefix="[DAEMON]::";}}
	if(function_exists("getmypid")){$pid=getmypid();}
	$class=null;
	if(preg_match("#(.+?)=>(.+)#",$function,$re)){$function="{$re[1]}/{$re[2]}";}
	
	$p=dirname(__FILE__);
	$p=str_replace("/ressources","",$p);
	$file_source=basename($file_source);
	$file_source=str_replace("$p/","",$file_source);
	
	if($line<>null){$line=" line $line";}
	$file_source=str_replace("/usr/share/artica-postfix/","",$file_source);
	$file_source=str_replace("/home/dtouzeau/developpement/artica-postfix/","",$file_source);
	if($GLOBALS["VERBOSE"]){echo "[$pid] $prefix::$function:: $text in $file_source$line\n";}
	$GLOBALS["MEMORY_LOGS"][]="[$pid] $prefix::$function:: $text in $file_source$line";
	if(!isset($_SERVER["REMOTE_ADDR"])){$_SERVER["REMOTE_ADDR"]="LOCAL";}
	$IP=$_SERVER["REMOTE_ADDR"];
	$SCRIPT_FILENAME=basename($_SERVER["SCRIPT_FILENAME"]);
	if(function_exists("posix_getuid")){if(posix_getuid()>0){if($_SESSION["uid"]==-100){$name="[SuperAdmin]";}}}
	
	if(!isset($name)){$name=null;}
	$text2=date('H:i:s')." $file_source [$IP]::$name $function:: $text line: $line source file:$SCRIPT_FILENAME";
	$text=date('H:i:s')." [$pid] $prefix::$name:$function:: $text in $file_source$line";
	writeOtherlogs(dirname(__FILE__)."/logs/web/interface.debug",$text2);
	if(isset($GLOBALS["ADDLOG"])){writeOtherlogs($GLOBALS["ADDLOG"],$text2);}
	if(isset($GLOBALS["AS_NET_AGENT"])){writeOtherlogs("/var/log/artica-netagent.log",$text2);}
	
	phpxlog("$text");
	if(basename($file_source)=="exec.backup.php"){writeOtherlogs("/var/log/artica-postfix/exec.backup.".date("Y-m-d").".log",$text);}
	$arr=explode("/",$function);
	if(is_array($arr)){if(count($arr)>0){if(isset($arr[0])){$class=$arr[0];if(isset($arr[1])){$function=$arr[1];}}}}
	writeClasslogs($text,$function,$class);

}
function phpxlog($text){
	$f = @fopen("/var/log/php.log", 'a');
	@fwrite($f, "$text\n");
	@fclose($f);	
	
}

function writeOtherlogs($file,$text=null){
	@mkdir(dirname($file));
	$logFile=$file;
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$text\n");
	@fclose($f);
}function writeClasslogs($text=null,$function=null,$class=null){
	
	@mkdir('/usr/share/artica-postfix/ressources/logs/web');
	$logFile="/usr/share/artica-postfix/ressources/logs/web/class.$class.log";
	if($class==null){$logFile="/usr/share/artica-postfix/ressources/logs/web/artica.log";}
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>1000000){unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date: [$function()]:: $text\n");
		@fclose($f);
}function writepostfixlogs($text=null,$function=null,$file_source=null){
	
if($file_source==null){$file_source= __FILE__ ;}
		$file_source=basename($file_source);
			@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		    $logFile="/usr/share/artica-postfix/ressources/logs/web/interface-postfix.log";
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>1000000){unlink($logFile);}
   		}
		$logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		if(!isset($_SERVER['REMOTE_ADDR'])){$_SERVER['REMOTE_ADDR']="127.0.0.1";}
		$text="$date:($file_source) [$function()][{$_SERVER['REMOTE_ADDR']}]:: $text\n";
		if($GLOBALS["DEBUG"]){echo $text;}
		@fwrite($f,$text );
		@fclose($f);}function writesquidlogs($text=null,$function=null,$file_source=null){
	
if($file_source==null){$file_source= __FILE__ ;}
		$file_source=basename($file_source);
			@mkdir('/usr/share/artica-postfix/ressources/logs/web');
		    $logFile="/usr/share/artica-postfix/ressources/logs/web/interface-squid.log";
		    if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   		if (is_file($logFile)) { 
   			$size=filesize($logFile);
		    	if($size>1000000){unlink($logFile);}
   		}
		 $logFile=str_replace("//","/",$logFile);
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:($file_source) [$function()][{$_SERVER['REMOTE_ADDR']}]:: $text\n");
		@fclose($f);}function write_syslog($text,$file){$file=basename($file);if(!function_exists('syslog')){return null;}openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);syslog(LOG_INFO, $text);closelog();}
		
?>
