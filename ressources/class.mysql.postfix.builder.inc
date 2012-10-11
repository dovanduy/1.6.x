<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_postfix_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="postfixlog";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	
	
	function mysql_postfix_builder(){
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;		
		
		if(!$this->TestingConnection()){$this->MysqlFailed=true;}
		
				
		
	}
	
	public function TestingConnection(){
		$this->ok=true;
		$this->ClassSQL->ok=true;
		$a=$this->ClassSQL->TestingConnection();
		$this->mysql_error=$this->ClassSQL->mysql_error;
		return $a;
	}

	public function COUNT_ROWS($table,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$count=$this->ClassSQL->COUNT_ROWS($table,$database);
		if(!$this->ClassSQL->ok){
			if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		}
		return $count;
	}

	public function TABLE_SIZE($table,$database=null){
			if($database<>$this->database){$database=$this->database;}
			return $this->ClassSQL->TABLE_SIZE($table,$database);		
		}
	
	public function TABLE_EXISTS($table,$database=null){
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		if(!$a){if(function_exists("debug_backtrace")){try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("TABLE_EXISTS:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		return $a;
		
	}
	private function DATABASE_EXISTS($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->DATABASE_EXISTS($database);
	}
	
	private function FIELD_EXISTS($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_EXISTS($table,$field,$database);
	}

	public function QUERY_SQL($sql,$database=null){
		if($database<>$this->database){$database=$this->database;}
		$results=$this->ClassSQL->QUERY_SQL($sql,$database);
		$this->ok=$this->ClassSQL->ok;
		$this->mysql_error=$this->ClassSQL->mysql_error;
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}

	public function EVENTS_SUM(){
		$sql="SELECT SUM(TABLE_ROWS) as tsum FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE 'dansguardian_events_%'";
		$ligne=mysql_fetch_array($this->QUERY_SQL($sql));
		if(!$this->ok){writelogs("$q->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		writelogs("{$ligne["tsum"]} : $sql",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return $ligne["tsum"];
		
	}	
	
	public function CheckTables(){
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		$this->TableDays();
	}
	
	public function BuildHourTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `zhour` tinyint(2) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `mailsize` bigint(100) NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `ztime` (`ztime`,`zhour`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}
	
	public function milter_BuildHourTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `ztime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			  `zhour` tinyint(2) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `failed` varchar(15) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `ztime` (`ztime`,`zhour`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `failed` (`failed`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}	
	
	public function BuildDayTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `zhour` tinyint(2) NOT NULL,
			  `hits` BIGINT(100) NOT NULL,
			  `size` BIGINT(100) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zhour` (`zhour`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){return false;}
		return true;
		
	}	
	
	public function BuildMonthTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
		$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
			  `zmd5` varchar(90) NOT NULL,
			  `zday` tinyint(2) NOT NULL,
			  `hits` BIGINT(100) NOT NULL,
			  `size` BIGINT(100) NOT NULL,
			  `mailfrom` varchar(255) NOT NULL,
			  `instancename` varchar(255) NOT NULL,
			  `mailto` varchar(255) NOT NULL,
			  `domainfrom` varchar(128) NOT NULL,
			  `domainto` varchar(128) NOT NULL,
			  `senderhost` varchar(128) NOT NULL,
			  `recipienthost` varchar(128) NOT NULL,
			  `smtpcode` tinyint(3) NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `zday` (`zday`,`smtpcode`),
			  KEY `mailfrom` (`mailfrom`),
			  KEY `size` (`size`),
			  KEY `hits` (`hits`),
			  KEY `mailto` (`mailto`),
			  KEY `domainfrom` (`domainfrom`),
			  KEY `domainto` (`domainto`),
			  KEY `senderhost` (`senderhost`),
			  KEY `instancename` (`instancename`),
			  KEY `recipienthost` (`recipienthost`)
			)";

		if(!$this->QUERY_SQL($sql)){
			if($GLOBALS["VERBOSE"]){echo "Failed to create $tablename\n";}
			return false;}
		return true;
		
	}	
	
	private function TableDays(){
		$sql="CREATE TABLE IF NOT EXISTS `TableDays` (
			`zDays` DATE NOT NULL ,
			`size` BIGINT( 100 ) NOT NULL ,
			`DayBuilded` SMALLINT( 1 ) NOT NULL DEFAULT '0',
			`MonthBuilded` SMALLINT( 1 ) NOT NULL DEFAULT '0',
			`events` BIGINT( 100 ) NOT NULL ,
			PRIMARY KEY ( `zDays` ) ,
			INDEX ( `size` , `DayBuilded` ),
			KEY `MonthBuilded` (`MonthBuilded`)
			)"; 
			if(!$this->QUERY_SQL($sql)){
			writelogs("$this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			}
			
			if(!$this->FIELD_EXISTS("TableDays", "MonthBuilded")){$this->QUERY_SQL("ALTER TABLE `TableDays` ADD `MonthBuilded` smallint( 1 ) NOT NULL,ADD INDEX (`MonthBuilded`)");}
			
		}
		
		
	public function LIST_HOUR_TABLES(){
		if(isset($GLOBALS["POSTFIX_LIST_HOUR_TABLES"])){return $GLOBALS["POSTFIX_LIST_HOUR_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database' AND table_name LIKE '%_hour'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#([0-9]+)_hour#", $ligne["c"],$re)){
				if($re[1]==$thishour){continue;}
				$number=$re[1];
				$year=substr($number, 0,4);
				$month=substr($number, 4,2);
				$day=substr($number, 6,2);
				$hour=substr($number, 8,2);
				$GLOBALS["POSTFIX_LIST_HOUR_TABLES"][$ligne["c"]]="$year-$month-$day $hour:00:00";
				$array[$ligne["c"]]=$ligne["c"];
			}
		}
		return $GLOBALS["POSTFIX_LIST_HOUR_TABLES"];	
		
		
	}
	
	
}