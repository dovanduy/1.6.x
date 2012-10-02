<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_mailarchive_builder{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="mailarchive";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	
	
	function mysql_mailarchive_builder(){
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
		
	}
	
	public function BuildDayTable($tablename){
		if($this->TABLE_EXISTS($tablename)){return true;}
		
			if(!$this->TABLE_EXISTS($tablename)){		
			$sql="CREATE TABLE IF NOT EXISTS `$tablename` (
				  `MessageID` varchar(80) NOT NULL,
				  `zDate` datetime NOT NULL,
				  `mailfrom` varchar(255) NOT NULL,
				  `subject` varchar(255) NOT NULL,
				  `MessageBody` text NOT NULL,
				  `organization` varchar(80) NOT NULL,
				  `uid` varchar(50) NOT NULL,
				  `file_path` varchar(255) DEFAULT NULL,
				  `original_messageid` varchar(80) NOT NULL,
				  `message_size` int(10) NOT NULL,
				  `mailfrom_domain` varchar(128) NOT NULL,
				  `mailto` varchar(255) NOT NULL,
				  `BinMessg` longblob NOT NULL,
				  `filename` varchar(128) NOT NULL,
				  `filesize` int(10) NOT NULL,
				  PRIMARY KEY (`MessageID`),
				  KEY `mailfrom` (`mailfrom`),
				  KEY `organization` (`organization`),
				  KEY `uid` (`uid`),
				  KEY `original_messageid` (`original_messageid`),
				  KEY `mailfrom_domain` (`mailfrom_domain`),
				  KEY `mailto` (`mailto`),
				  KEY `filename` (`filename`),
				  FULLTEXT KEY `MessageBody` (`MessageBody`)
				) ;";
				
				$this->QUERY_SQL($sql);
				if(!$this->ok){return false;}
				}

		
		return true;
		
	}
	
	
	public function BuildSummaryTable(){
		if(!$this->TABLE_EXISTS("indextables")){		
			$sql="CREATE TABLE IF NOT EXISTS `indextables` (
			 `tablename` varchar(80) NOT NULL,
			 `rowsnum` BIGINT(100) NOT NULL,
			 `xday`DATE NOT NULL,
			 `size` BIGINT(100) NOT NULL,
			 PRIMARY KEY (`tablename`),
			 KEY `rowsnum` (`rowsnum`),
			 KEY `size` (`size`)
			);";
		$this->QUERY_SQL($sql);if(!$this->ok){return false;}}
		return true;
	}
	
	
		
		
	public function LIST_BACKUP_TABLES(){
		if(isset($GLOBALS["LIST_BACKUP_TABLES"])){return $GLOBALS["LIST_BACKUP_TABLES"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = '$this->database'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
		$thishour=date("YmdH");
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(!is_numeric($ligne["c"])){continue;}
			$number=$ligne["c"];
			$year=substr($number, 0,4);
			$month=substr($number, 4,2);
			$day=substr($number, 6,2);
			$GLOBALS["LIST_BACKUP_TABLES"][$ligne["c"]]="$year-$month-$day";
			$array[$ligne["c"]]=$ligne["c"];
		}
		
		return $GLOBALS["LIST_BACKUP_TABLES"];	
		
		
	}
	
	
}