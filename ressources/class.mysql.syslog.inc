<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
include_once(dirname(__FILE__).'/class.users.menus.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
class mysql_syslog{
	var $ClassSQL;
	var $ok=false;
	var $mysql_error;
	var $UseMysql=true;
	var $database="syslogstore";
	var $mysql_server;
	var $mysql_admin;
	var $mysql_password;
	var $mysql_port;
	var $MysqlFailed=false;
	var $EnableRemoteStatisticsAppliance=0;
	var $last_id;
	
	function mysql_syslog(){
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
	
	
	public function CheckDefaults(){
		$this->CheckTables();
		$php5=exec("whereis php");
		$prefix="INSERT IGNORE INTO logrotate (RotateFiles,RotateType,RotateFreq,MaxSize,RotateCount,postrotate,description,enabled) VALUES ";
		
		$f[]="('/var/log/syslog',1,'daily','100',5,'%PHP% /usr/share/artica-postfix/exec.syslog-engine.php --restart-syslog','{syslog_events}',1)";
		
		$t=$this->syslog_array();
		
		$f1[]="  /etc/init.d/syslog restart > /dev/null";
		$f1[]="  /etc/init.d/artica-postfix restart sysloger > /dev/null";
		$f1[]="  /etc/init.d/artica-postfix restart postfix-logger > /dev/null";
		$f1[]="  %POSTFIX% reload > /dev/null";
		$defaultrestart=mysql_escape_string(@implode("\n", $f1));
		
		$f2[]="  /etc/init.d/syslog restart > /dev/null";
		$f2[]="  /etc/init.d/artica-postfix restart fetchmail-logger > /dev/null";
		$f2[]="  /etc/init.d/artica-postfix restart fetchmail > /dev/null";		
		$defaultFtechmailrestart=mysql_escape_string(@implode("\n", $f2));
		$f[]="('/var/log/fetchmail.log',1,'daily','100',5,'$defaultFtechmailrestart','{APP_FETCHMAIL}',1)";
		$f[]="('/var/log/squid/*.log',1,'daily','100',5,'%SQUIDBIN% -k rotate\n/etc/init.d/ufdb rotatelog\n/etc/init.d/artica-postfix restart ufdb-tail','{APP_SQUID}',1)";
		$f[]="('/var/log/php.log',1,'daily','100',5,'','{APP_PHP5}',1)";
		$f[]="('/var/log/apache2/*.log',1,'daily','100',5,'%PHP% /usr/share/artica-postfix/exec.freeweb.php --reload','{APP_APACHE}',1)";
		$f[]="('/var/log/mail-backup/*',1,'daily','100',5,'','Mail statistics',1)";
		$f[]="('/var/log/pymsnt/pymsnt.log',1,'daily','100',5,'/etc/init.d/pymsnt reload','MSN Module for ejabberd',1)";
		$f[]="('/var/log/ejabberd/*',1,'daily','100',5,'/etc/init.d/artica-postfix ejabberd restart','{APP_EJABBERD}',1)";
		$f[]="('/var/log/kaspersky/kav4proxy/*',1,'daily','100',5,'/etc/init.d/kav4proxy restart','{APP_KAV4PROXY}',1)";
		$f[]="('/var/log/lighttpd/*',1,'daily','100',5,'/etc/init.d/artica-postfix restart apache','Lighttpd',1)";
		$f[]="('/var/log/apt/*',1,'daily','100',5,'/etc/init.d/artica-postfix restart apache','apt-get',1)";
		
		
		while (list ($num, $path) = each ($t) ){		
			$f[]="('$path',1,'daily','100',5,'$defaultrestart','Rotate $path',1)";
		}
		
		$this->QUERY_SQL($prefix.@implode(",", $f));
		if(!$this->ok){echo $this->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("services.php?rotatebuild=yes");
		
	}
	
	private function syslog_array(){
		$f[]="/var/log/mail.info";
		$f[]="/var/log/mail.warn";
		$f[]="/var/log/mail.err";
		$f[]="/var/log/mail.log";
		$f[]="/var/log/daemon.log";
		$f[]="/var/log/kern.log";
		$f[]="/var/log/auth.log";
		$f[]="/var/log/user.log";
		$f[]="/var/log/lpr.log";
		$f[]="/var/log/cron.log";
		$f[]="/var/log/Xorg.*.log";
		$f[]="/var/log/debug";
		$f[]="/var/log/messages";
		$f[]="/var/log/warn";
		$f[]="/var/log/mail";
		$f[]="/var/log/uucp.log";
		return $f;	
	}	
	

	
	public function CheckTables(){
		
		if(!$this->TABLE_EXISTS("logrotate")){
			$sql="CREATE TABLE IF NOT EXISTS `logrotate` (
			  `ID` int(11) NOT NULL AUTO_INCREMENT,
			  `RotateType` INT(5) NOT NULL,
			  `RotateFiles` varchar(255) NOT NULL,
			  `RotateFreq` varchar(40) NOT NULL,
			  `MaxSize` BIGINT(100) NOT NULL,
			  `RotateCount` int(5) NOT NULL,
			  `postrotate` TEXT NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `description` TEXT NOT NULL,
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY (`RotateFiles`),
			  KEY `RotateType` (`RotateFreq`,`MaxSize`,`RotateCount`),
			  KEY `enabled` (`enabled`)
			  
			)";
		
			$this->QUERY_SQL($sql);
			}
			
		if(!$this->FIELD_EXISTS("logrotate", "RotateFiles")){$this->QUERY_SQL("ALTER TABLE `logrotate` ADD `RotateFiles` VARCHAR( 255 ) NOT NULL , ADD UNIQUE KEY (`RotateFiles`)");}
		
		if(!$this->TABLE_EXISTS("store")){
			$sql="CREATE TABLE IF NOT EXISTS `store` (
					  `filename` varchar(128) NOT NULL,
					  `taskid` int(10) NOT NULL,
					  `filesize` bigint(100) NOT NULL,
					  `filedata` longblob NOT NULL,
					  `filetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					  `SavedInDisk` SMALLINT( 1 ) NOT NULL,
					  `FileStorePath` VARCHAR( 255 ) NOT NULL ,
					  PRIMARY KEY (`filename`),
					  KEY `taskid` (`taskid`,`filesize`,`SavedInDisk`),
					  KEY `filetime` (`filetime`),
					  KEY `FileStorePath` (`FileStorePath`)
					)";
			
			$this->QUERY_SQL($sql);
		}
		
		if(!$this->FIELD_EXISTS("store", "SavedInDisk")){$this->QUERY_SQL("ALTER TABLE `store` ADD `SavedInDisk` SMALLINT( 1 ) NOT NULL ,ADD `FileStorePath` VARCHAR( 255 ) NOT NULL , ADD INDEX ( `SavedInDisk` , `FileStorePath` )");}
		
		
		
	}
	
}
	


	

	
	

