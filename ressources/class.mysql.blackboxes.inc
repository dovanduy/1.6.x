<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_blackbox{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="blackboxes";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	
	
	function mysql_blackbox(){
		$sock=new sockets();
		$this->ClassSQL=new mysql();
		$this->UseMysql=$this->ClassSQL->UseMysql;
		$this->mysql_admin=$this->ClassSQL->mysql_admin;
		$this->mysql_password=$this->ClassSQL->mysql_password;
		$this->mysql_port=$this->ClassSQL->mysql_port;
		$this->mysql_server=$this->ClassSQL->mysql_server;
		if(!$this->DATABASE_EXISTS($this->database)){$this->CREATE_DATABASE($this->database);}
		if($this->TestingConnection()){}else{$this->MysqlFailed=true;}
		
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
		if($table=="category_teans"){$table="category_teens";}
		if($database==null){$database=$this->database;}
		if($database<>$this->database){$database=$this->database;}
		$a=$this->ClassSQL->TABLE_EXISTS($table,$database);
		if(!$a){
				if(function_exists("debug_backtrace")){
				try {$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}} catch (Exception $e) {writelogs("TABLE_EXISTS:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}}
				writelogs($called,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);	
		}
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
		$this->last_id=$this->ClassSQL->last_id;
		return $results;
	}
	
	private function FIELD_TYPE($table,$field,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->FIELD_TYPE($table,$field,$database);
	}
	
	private FUNCTION INDEX_EXISTS($table,$index,$database=null){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->INDEX_EXISTS($table,$index,$database);
	}
	
	private FUNCTION CREATE_DATABASE($database){
		if($database<>$this->database){$database=$this->database;}
		return $this->ClassSQL->CREATE_DATABASE($database);
	}
	
	public function BuildCachelogs($nodeid){
		if(!$this->TABLE_EXISTS("cachelogs$nodeid")){
			$sql="CREATE TABLE IF NOT EXISTS `cachelogs$nodeid` (
			`line` varchar(255) NOT NULL,
			`zDate` DATETIME NOT NULL,
			 KEY `zDate` (`zDate`),
			 KEY `line` (`line`)
			 )";
			
			$this->QUERY_SQL($sql);
		}
		
	}
	
	public function build_cached_items_table($hostid){
		if(!$this->TABLE_EXISTS("cacheitems_$hostid")){
			$sql="CREATE TABLE IF NOT EXISTS `cacheitems_$hostid` (
			`sitename` varchar(255) NOT NULL,
			`familysite` varchar(255) NOT NULL,
			`size` BIGINT(100),`items` BIGINT(100),
			 PRIMARY KEY (`sitename`),
			 KEY `familysite` (`familysite`),
			 KEY `size` (`size`),
			 KEY `items` (`items`)
			 )";
			$this->QUERY_SQL($sql);
		}
		
		if(!$this->TABLE_EXISTS("pms$hostid",$this->database)){	
			$sql="CREATE TABLE `pms$hostid` (
					`zkey` VARCHAR( 255 ) NOT NULL ,
					`value` TEXT NOT NULL ,
					 PRIMARY KEY (`zkey`)
					 ) ";
			$this->QUERY_SQL($sql,$this->database);
		}		
		
		
	}
	
	
	public function CheckTables(){
		
		if(!$this->TABLE_EXISTS("nodes")){
			$sql="CREATE TABLE IF NOT EXISTS `nodes` (
			  `nodeid` int(11) NOT NULL AUTO_INCREMENT,
			  `hostid` varchar(90) NOT NULL,
			  `hostname` varchar(128) NOT NULL,
			  `ipaddress` varchar(50) NOT NULL,
			  `port` int(5) NOT NULL,
			  `ssl` smallint(1) NOT NULL,
			  `laststatus` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `memory` int(10) NOT NULL,
			  `numproc` int(1) NOT NULL,
			  `perfs` TEXT NOT NULL,
			  `prodstatus` TEXT NOT NULL,
			  `squidconf` TEXT NOT NULL,
			  `squidver` varchar(50) NOT NULL,
			  `statusnum` tinyint(1) NOT NULL,
			  `settingsinc` TEXT NOT NULL,
			  `articaver` varchar(40) NOT NULL,
			  `BigArtica` smallint(1) NOT NULL,
			  `arch` smallint(2),
			  `UfdbClientLogs` TEXT NOT NULL,
			  PRIMARY KEY (`nodeid`),
			  UNIQUE KEY `hostid` (`hostid`),
			  KEY `port` (`port`,`memory`,`numproc`,`statusnum`),
			  KEY `ipaddress` (`ipaddress`),
			  KEY `arch` (`arch`),
			  KEY `squidver` (`squidver`),
			  KEY `BigArtica` (`BigArtica`),
			  KEY `hostname` (`hostname`)
			)";
		
			$this->QUERY_SQL($sql);
			}
			
			if(!$this->FIELD_EXISTS("nodes", "prodstatus")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `prodstatus` TEXT");}
			if(!$this->FIELD_EXISTS("nodes", "articaver")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `articaver` varchar(40) NOT NULL");}
			if(!$this->FIELD_EXISTS("nodes", "hostid")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `hostid` varchar(90) NOT NULL,ADD UNIQUE KEY `hostid` (`hostid`)");}
			if(!$this->FIELD_EXISTS("nodes", "squidconf")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `squidconf` TEXT NOT NULL");}
			if(!$this->FIELD_EXISTS("nodes", "squidver")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `squidver` varchar(50) NOT NULL,ADD KEY `squidver` (`squidver`)");}
			if(!$this->FIELD_EXISTS("nodes", "arch")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `arch` smallint(2) NOT NULL,ADD KEY `arch` (`arch`)");}
			if(!$this->FIELD_EXISTS("nodes", "BigArtica")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `BigArtica` smallint(1) NOT NULL,ADD KEY `BigArtica` (`BigArtica`)");}
			if(!$this->FIELD_EXISTS("nodes", "UfdbClientLogs")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `UfdbClientLogs` TEXT NOT NULL");}
			if(!$this->FIELD_EXISTS("nodes", "ssl")){$this->QUERY_SQL("ALTER TABLE `nodes` ADD `ssl` smallint(1) NOT NULL");}
			
			if(!$this->TABLE_EXISTS("squidconf")){
			$sql="CREATE TABLE IF NOT EXISTS `squidconf` (
			  `nodeid` int(11) NOT NULL,
			  `key` varchar(50) NOT NULL,
			  `value` TINYTEXT NOT NULL,
			  INDEX ( `nodeid` , `key` )
			  )";
		
			$this->QUERY_SQL($sql);
			}	

		if(!$this->TABLE_EXISTS('cachestatus',$this->database)){	
			$sql="CREATE TABLE `cachestatus` (
					`nodeid` int(11) NOT NULL,
					`cachedir` VARCHAR( 255 ) NOT NULL ,
					`maxsize` BIGINT( 100 ) NOT NULL ,
					`currentsize` BIGINT( 100 ) NOT NULL ,
					`pourc` VARCHAR( 20 ) NOT NULL,
					INDEX ( `maxsize` , `currentsize` , `pourc` ),
					KEY `nodeid` (`nodeid`),
					KEY `cachedir` (`cachedir`)
					) ";
			$this->QUERY_SQL($sql,$this->database);
		}	

		
		if(!$this->TABLE_EXISTS('poolorders',$this->database)){	
			$sql="CREATE TABLE `poolorders` (
					`hostid` VARCHAR(90) NOT NULL,
					`nodeid` int(11) NOT NULL,
					`orderid` VARCHAR( 255 ) NOT NULL ,
					`orderdata` TEXT NOT NULL ,
					 PRIMARY KEY (`orderid`),
					KEY `hostid` (`hostid`),
					KEY `nodeid` (`nodeid`)
					) ";
			$this->QUERY_SQL($sql,$this->database);
		}

		
	
	
	
	if(!$this->TABLE_EXISTS("softwares")){
			$sql="CREATE TABLE `blackboxes`.`softwares` (
			`nodeid` TINYINT( 5 ) NOT NULL ,
			`software` VARCHAR( 255 ) NOT NULL ,
			`version` VARCHAR( 20 ) NOT NULL ,
			INDEX ( `nodeid` , `version` ),
			KEY `software` (`software`)
			)";
			$this->QUERY_SQL($sql);
			}

	if(!$this->TABLE_EXISTS("nics")){
			$sql="CREATE TABLE IF NOT EXISTS `nics` (
			  `nodeid` int(10) NOT NULL,
			  `nic` varchar(20)  NOT NULL,
			  `ipaddr` varchar(50)  NOT NULL,
			  `netmask` varchar(50)  NOT NULL,
			  `gateway` varchar(50)  NOT NULL,
			  `broadcast` varchar(50)  NOT NULL,
			  `DNS1` varchar(50)  NOT NULL,
			  `DNS2` varchar(50)  NOT NULL,
			  `mac` varchar(60)  NOT NULL,
			  UNIQUE KEY `mac` (`mac`),
			  KEY `nodeid` (`nodeid`,`nic`,`ipaddr`,`netmask`,`gateway`,`broadcast`)
			)";		
			$this->QUERY_SQL($sql);
			}
	
	
	
	
	if(!$this->TABLE_EXISTS("nodesstatus")){
		$sql="CREATE TABLE IF NOT EXISTS `nodesstatus` (
		`nodeid` int(10) NOT NULL,
		`service_name` varchar(50)  NOT NULL,
		`master_version` varchar(50)  NOT NULL,
		`master_pid` int(10) NOT NULL,
		`master_memory` int(10) NOT NULL,
		`master_cached_memory` int(10) NOT NULL,
		`processes_number` int(10) NOT NULL,
		`running` smallint(1) NOT NULL,
		`installed` smallint(1) NOT NULL,
		KEY `nodeid` (`nodeid`),
		KEY `service_name` (`service_name`),
		KEY `master_memory` (`master_memory`),
		KEY `master_cached_memory` (`master_cached_memory`),
		KEY `running` (`running`),
		KEY `installed` (`installed`)
		)";
		$this->QUERY_SQL($sql);
	}
	
	if(!$this->TABLE_EXISTS("harddrives")){
		$sql="CREATE TABLE IF NOT EXISTS `harddrives` (
		`nodeid` int(10) NOT NULL,
		`path` varchar(90)  NOT NULL,
		`size` BIGINT(50) NOT NULL,
		`ID_FS_LABEL` varchar(90)  NOT NULL,
		`ID_MODEL_1` varchar(90)  NOT NULL,
		`ID_MODEL_2` varchar(90)  NOT NULL,
		`ID_VENDOR`  varchar(90)  NOT NULL,
		`PARTITIONS` TEXT NOT NULL,
		 KEY `nodeid` (`nodeid`),
		 KEY `path` (`path`),
		 KEY `size` (`size`)
		 )";
		$this->QUERY_SQL($sql);
		
	}
	
	
	}
	
	
}


	

	
	

