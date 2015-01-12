<?php

class mysql_stats{
	
	private $database;
	private $mysql_connection;
	public $ok=false;
	public $mysql_error=null;
	public $last_id=0;
	
	function mysql_stats($uuid){
		$this->database=$uuid;
		if(!$this->DATABASE_EXISTS()){$this->CREATE_DATABASE();
		}
		
	}
	
	
	private FUNCTION CREATE_DATABASE(){
		if(!$this->BD_CONNECT()){
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
	
		if($this->DATABASE_EXISTS($this->database)){
			writelogs("CREATE_DATABASE $this->database Already exists aborting",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			$this->ok=true;
			return true;
		}
		
		
		$results=@mysql_query("CREATE DATABASE `$this->database`",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			$this->mysql_error="CREATE DATABASE $this->database -> Error Number ($errnum) ($des)";
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}
	
		$this->ok=true;
		return true;
	}	
	
	
	private function DATABASE_EXISTS(){
		$database=trim($this->database);
		
		$sql="SHOW DATABASES";
		$this->BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		@mysql_close($this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){return true;}
		}
		
		return false;
	}
	
	private function SERVER_CONNECT(){
		
		$bd=@mysql_connect(":/var/run/mysqld/squid-db.sock","root",null);
		
		if($bd){
			$this->mysql_connection=$bd;
			return true;
		}
		
	}
	
	private function BD_CONNECT(){
		if($this->SERVER_CONNECT()){return true;}
		@mysql_close();
		usleep(500);
		if($this->SERVER_CONNECT()){return true;}
		@mysql_close();
		usleep(500);
		if($this->SERVER_CONNECT()){return true;}
		return false;
	
	
	}
	
	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		$text=str_replace("\r", " ", $text);
	
	
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$function="{$trace[1]["function"]}()";
				$line="{$trace[1]["line"]}";
			}
		}
	
		$text="{$function}[$line]:$text";
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("mysql-stats", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	private function writelogs($text=null,$function=null,$line=0){
		$file_source="/var/log/mysql.squid-stats.log";
		@mkdir(dirname($file_source));
		if(!is_numeric($line)){$line=0;}
		if(function_exists("writelogs")){
			writelogs("$text (L.$line)",__CLASS__."/$function",__FILE__,$line);
		}
		if(!$GLOBALS["VERBOSE"]){return;}
		$logFile=$file_source;
		if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
		if (is_file($logFile)) {$size=filesize($logFile);if($size>1000000){unlink($logFile);}}
		$f = @fopen($logFile, 'a');
		$date=date("Y-m-d H:i:s");
		@fwrite($f, "$date:[".__CLASS__."/$function()]:: $text (L.$line)\n");
		@fclose($f);
	}	

	
	public function QUERY_SQL($sql){
	
		if(!$this->BD_CONNECT()){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$this->database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->BD_CONNECT_ERROR ." Error, unable to connect to MySQL server";
			$this->ToSyslog($this->mysql_error);
			return false;
		}

		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if(!is_numeric($errnum)){
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				$this->BD_CONNECT(false,$called);
				$ok=@mysql_select_db($this->database,$this->mysql_connection);
				if (!$ok){
					$this->ok=false;
					return false;
				}
			}
		}
		
		$mysql_unbuffered_query_log=null;
		if(preg_match("#^(UPDATE|DELETE)#i", $sql)){
			$mysql_unbuffered_query_log="mysql_unbuffered_query";
			if($GLOBALS["DEBUG_SQL"]){echo "mysql_unbuffered_query()\n";}
			$results=@mysql_unbuffered_query($sql,$this->mysql_connection);
			
		}else{
			$mysql_unbuffered_query_log="mysql_query";
			if($GLOBALS["DEBUG_SQL"]){echo "mysql_query()\n";}
			$results=@mysql_query($sql,$this->mysql_connection);
			$this->last_id=@mysql_insert_id($this->mysql_connection);
			
		}
		
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
				
			if(preg_match('#Duplicate entry#',$des)){
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$this->database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$this->database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->ok=true;
				@mysql_close($this->mysql_connection);
				$this->mysql_connection=false;
				return true;
			}
			$this->mysql_errornum=$errnum;
			$this->mysql_error="QUERY_SQL:".__LINE__.": $mysql_unbuffered_query_log:: $called Error $errnum ($des) line:".__LINE__;
			$this->ToSyslog($this->mysql_error);
			$sql=str_replace("\n", " ", $sql);
			$sql=str_replace("\t", " ", $sql);
			$sql=str_replace("  ", " ", $sql);
			$this->ToSyslog($sql);
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log/".__LINE__." [FAILED] N.$errnum DESC:$des $called\n";}
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF $mysql_unbuffered_query_log".__LINE__." [FAILED] $sql\n";}
			@mysql_free_result($this->mysql_connection);
			@mysql_close($this->mysql_connection);
			$this->mysql_connection=false;
			$this->ok=false;
			return null;
		
		}

		if($GLOBALS["DEBUG_SQL"]){echo "SUCCESS\n";}
		$this->ok=true;
		if($this->last_id==0){$this->last_id=@mysql_insert_id($this->mysql_connection);}
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		$this->mysql_connection=false;
		return $result_return;
	}
	
}