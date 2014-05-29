<?php
include_once(dirname(__FILE__).'/class.nginx-sources.inc');
$GLOBALS["TYPES"][0]="{local_ldap}";
$GLOBALS["TYPES"][1]="{ldap}";
$GLOBALS["TYPES"][2]="{ActiveDirectory}";
$GLOBALS["TYPES"][3]="{trace_connexions}";
$GLOBALS["TYPES"][4]="{redirect_uri}";
$GLOBALS["TYPES"][5]="{CAS_AUTH_LABEL}";

$GLOBALS["SOURCE_TYPE"][0]="{all_cases}";
$GLOBALS["SOURCE_TYPE"][1]="{network_text}";
$GLOBALS["SOURCE_TYPE"][2]="{country}";
$GLOBALS["SOURCE_TYPE"][3]="{cookie}";

$GLOBALS["DEST_TYPES"][0]="{local_ldap}";
$GLOBALS["DEST_TYPES"][1]="{ldap}";
$GLOBALS["DEST_TYPES"][2]="{ActiveDirectory}";

class squid_reverse{
	private $hostname=null;
	private $port=80;
	private $ipaddr=null;
	private $aclname=0;
	private $ACLS_BY_HOSTS=array();
	private $never_direct_allow=array();
	private $cache_peer_domain=array();
	private $cache_peer_access_allow=array();
	private $miss_access_allow=array();
	private $cache_peer_defs=array();
	private $http_access_allow=array();
	public $errors_page=array(400,401,403,404,500,501,502,503,504,505,507,509,510);
	
	public function squid_reverse($hostname=null){
		if($hostname<>null){
			$this->hostname=$hostname;
			$this->loadparams();
		}
		$this->patchtables();
		$sock=new sockets();
		$this->FreeWebDisableSSL=$sock->GET_INFO("FreeWebDisableSSL");
		if(!is_numeric($this->FreeWebDisableSSL)){$this->FreeWebDisableSSL=0;}
	}
	
	public function compile(){
		
		$f[]="acl {$this->aclname} dstdomain $this->hostname";
		

		
	}
	
	
	private function loadparams(){
		$q=new mysql_squid_builder();
		
		$sql="SELECT * FROM reverse_www WHERE `servername`='$this->hostname`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$this->ipaddr=$ligne["ipaddr"];
		$this->port=$ligne["port"];
		$this->aclname="www".$ligne["aclname"];
	}
	
	private function patchtables(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__.":: patching tables...\n";}
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS("reverse_www")){
			$sql="CREATE TABLE IF NOT EXISTS `reverse_www` (
			  `servername` CHAR(255)  NOT NULL,
			  `ipaddr` CHAR(60)  NOT NULL,
			  `cache_peer_id` INT(10)  NOT NULL DEFAULT '0',
			  `cacheid` INT(10)  NOT NULL DEFAULT '0',
			  `replaceid` INT(10)  NOT NULL DEFAULT '0',
			  `poolid` INT(10)  NOT NULL DEFAULT '0',
			  `port` smallint(3)  NOT NULL,
			  `sslport` smallint(3)  NOT NULL,
			  `ssl` smallint(1)  NOT NULL,
			  `enabled` smallint(1)  NOT NULL DEFAULT '1',
			  `owa` smallint(1)  NOT NULL DEFAULT '0',
			  `aclname` INT NOT NULL AUTO_INCREMENT,
			  `certificate` VARCHAR(255),
			  `default_server` smallint(1),
			  `tmperrordisabled` smallint(1),
			  PRIMARY KEY (`servername`),
			  UNIQUE KEY (`aclname`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `port` (`port`),
			  KEY `ssl` (`ssl`),
			  KEY `default_server` (`default_server`),
			  KEY `sslport` (`sslport`),
			  KEY `cache_peer_id` (`cache_peer_id`),
			  KEY `replaceid` (`replaceid`),
			  KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){echo $q->mysql_error_html();return;}
		}
		
		
			$sql="CREATE TABLE IF NOT EXISTS `reverse_dirs` (
			  `folderid` INT NOT NULL AUTO_INCREMENT,
			  `replaceid` INT(10)  NOT NULL DEFAULT '0',
			  `servername` CHAR(255)  NOT NULL,
			  `directory` CHAR(255)  NOT NULL,
			  `localdirectory` CHAR(255)  NOT NULL,
			  `webpageid` INT UNSIGNED  NOT NULL,
			  `hostweb` CHAR(255)  NOT NULL,
			  `local` smallint(1)  NOT NULL,
			  `cache_peer_id` INT(10)  NOT NULL DEFAULT '0',
			  `cacheid` INT(10)  NOT NULL DEFAULT '0',
			  `enabled` smallint(1)  NOT NULL DEFAULT '1',
			  `ForceRedirect` smallint(1)  NOT NULL DEFAULT '1',
			  PRIMARY KEY (`folderid`),
			  KEY `webpageid` (`webpageid`),
			  KEY `servername` (`servername`),
			  KEY `directory` (`directory`),
			  KEY `local` (`local`),
			  KEY `cache_peer_id` (`cache_peer_id`),
			  KEY `cacheid` (`cacheid`),
			  KEY `replaceid` (`replaceid`),
			  KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){echo $q->mysql_error_html();return;}
			
			
			$sql="CREATE TABLE IF NOT EXISTS `reverse_pages_cms` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `subject`  CHAR(255),
			  `headers` TEXT  NOT NULL,
			  `footers` TEXT NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY (`subject`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){echo $q->mysql_error_html();return;}			
			
			
			$sql="CREATE TABLE IF NOT EXISTS `reverse_pages_content` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `subject`  CHAR(255),
			  `content` TEXT  NOT NULL,
			  `zDate` DATETIME NOT NULL,
			  `cachemin` smallint(5) NOT NULL DEFAULT '5',
			  PRIMARY KEY (`ID`),
			  KEY (`subject`),
			  KEY (`cachemin`),
			  KEY `zDate` (`zDate`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){echo $q->mysql_error_html();return;}	


		if(!$q->FIELD_EXISTS("reverse_dirs", "limit_rate_after")){$q->QUERY_SQL("ALTER TABLE `reverse_dirs`
		ADD `limit_rate_after` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
			
		if(!$q->FIELD_EXISTS("reverse_dirs", "limit_rate")){$q->QUERY_SQL("ALTER TABLE `reverse_dirs`
		ADD `limit_rate` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
			
						
			
		if(!$q->FIELD_EXISTS("reverse_dirs", "webpageid")){
			$q->QUERY_SQL("ALTER TABLE `reverse_dirs` ADD `webpageid` INT UNSIGNED, ADD INDEX ( `webpageid`)");
			if(!$q->ok){echo $q->mysql_error_html();return;}
		}	

		if(!$q->FIELD_EXISTS("reverse_pages_content", "cachemin")){
			$q->QUERY_SQL("ALTER TABLE `reverse_pages_content` ADD `cachemin` smallint(5), ADD INDEX ( `cachemin`)");
			if(!$q->ok){echo $q->mysql_error_html();return;}
		}		
		

		if(!$q->FIELD_EXISTS("reverse_dirs", "localdirectory")){
			$q->QUERY_SQL("ALTER TABLE `reverse_dirs` ADD `localdirectory` CHAR(255)");
			if(!$q->ok){echo $q->mysql_error_html();return;}
		}

		if(!$q->FIELD_EXISTS("reverse_dirs", "local")){
			$q->QUERY_SQL("ALTER TABLE `reverse_dirs` ADD `local` smallint(1) NOT NULL, ADD INDEX ( `local`)");
			if(!$q->ok){echo $q->mysql_error_html();return;}
		}		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_caches` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `directory` CHAR(255)  NOT NULL,
			  `levels` CHAR(20)  NOT NULL,
			  `keys_zone` CHAR(40)  NOT NULL,
			  `keys_zone_size` smallint(1)  NOT NULL,
			  `inactive` smallint(3)  NOT NULL DEFAULT '10',
			  `max_size` smallint(1)  NOT NULL DEFAULT '1',
			  `CurrentSize` BIGINT UNSIGNED  NOT NULL DEFAULT '0',
			  `loader_files` INT(5) NOT NULL DEFAULT '100',
			  `loader_sleep` INT(10) NOT NULL DEFAULT '50',
			  `loader_threshold` INT(10) NOT NULL DEFAULT '200',
			  PRIMARY KEY (`ID`),
			  UNIQUE KEY `directory` (`directory`),
			  KEY `CurrentSize` (`CurrentSize`),
			  UNIQUE KEY `keys_zone` (`keys_zone`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){echo $q->mysql_error_html();return;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_aliases` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `alias` CHAR(255)  NOT NULL,
			  `servername` CHAR(255)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `servername` (`servername`),
			  UNIQUE KEY `alias` (`alias`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);		
		if(!$q->ok){echo $q->mysql_error_html();return;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_replace_www` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `rulename` CHAR(255)  NOT NULL,
			  `stringtosearch` TEXT  NOT NULL,
			  `servername` CHAR(255)  NOT NULL,
			  `tokens` CHAR(20)  NOT NULL,
			  `replaceby` TEXT,
			  PRIMARY KEY (`ID`),
			  KEY `rulename` (`rulename`),
			  KEY `servername` (`servername`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);		
		if(!$q->ok){echo $q->mysql_error_html();return;}
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_replace_folder` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `rulename` CHAR(255)  NOT NULL,
			  `folderid` INT(10)  NOT NULL,
			  `zorder` INT(10)  NOT NULL,
			  `stringtosearch` TEXT  NOT NULL,
			  `servername` CHAR(255)  NOT NULL,
			  `tokens` CHAR(20)  NOT NULL,
			  `replaceby` TEXT,
			  PRIMARY KEY (`ID`),
			  KEY `folderid` (`folderid`),
			  KEY `zorder` (`zorder`),
			  KEY `rulename` (`rulename`),
			  KEY `servername` (`servername`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>\n";}		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_replace` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `rulename` CHAR(255)  NOT NULL,
			  `groupid` INT NOT NULL DEFAULT '0',
			  `stringtosearch` CHAR(255)  NOT NULL,
			  `tokens` CHAR(20)  NOT NULL,
			  `replaceby` TEXT,	  
			  PRIMARY KEY (`ID`),
			  KEY `rulename` (`rulename`),
			  KEY `groupid` (`groupid`),
			  KEY `stringtosearch` (`stringtosearch`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_exploits` (
			  `zmd5` VARCHAR(90) NOT NULL,
			  `groupid` INT  NOT NULL,
			  `servername` CHAR(255)  NOT NULL,
			  PRIMARY KEY (`zmd5`),
			  KEY `servername` (`servername`),
			  KEY `groupid` (`groupid`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}		
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_exploits_groups` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `groupname` CHAR(255)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupname` (`groupname`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}
		

		$sql="CREATE TABLE IF NOT EXISTS `nginx_exploits_items` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `groupid` INT NOT NULL DEFAULT '0',
			  `enabled` smallint(1)  NOT NULL,
			  `reverse` smallint(1)  NOT NULL DEFAULT 0,
			  `pattern` TEXT,
			  `token` CHAR(20)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupid` (`groupid`),
			  KEY `enabled` (`enabled`),
			  KEY `token` (`token`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}	

		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_exploits_fw` (
		`servername` CHAR(255) NOT NULL  PRIMARY KEY,
		`maxaccess` smallint(1)  NOT NULL DEFAULT 0,
		`sendlogs` smallint(1)  NOT NULL DEFAULT 0,
		KEY `maxaccess` (`maxaccess`),
		KEY `sendlogs` (`sendlogs`)
		)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_exploits_fwev` (
		`zmd5` CHAR(255) NOT NULL  PRIMARY KEY,
		`zDate` DATETIME NOT NULL,
		`servername` CHAR(255) NOT NULL,
		`ipaddr` CHAR(255) NOT NULL,
		`hostname`  CHAR(255)  NOT NULL,
		`country` CHAR(128),
		KEY `zDate` (`zDate`),
		KEY `servername` (`servername`),
		KEY `ipaddr` (`ipaddr`),
		KEY `hostname` (`hostname`),
		KEY `country` (`country`)
		)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}		
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_replace_group` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `groupname` CHAR(255)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupname` (`groupname`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);	
		if(!$q->ok){return;}	
		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_error_pages` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `pagename` CHAR(255)  NOT NULL,
			  `title` CHAR(255)  NOT NULL,
			  `error_code` INT(10) NOT NULL DEFAULT '404',
			  `headers` TEXT,
			  `body` TEXT,
			  PRIMARY KEY (`ID`),
			  KEY `pagename` (`pagename`),
			  KEY `error_code` (`error_code`),
			  KEY `title` (`title`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}		

		
		$sql="CREATE TABLE IF NOT EXISTS `nginx_pools` (
			  `ID` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			  `poolname` CHAR(255)  NOT NULL,
			  `hashtype` CHAR(128)  NOT NULL,
			  `keepalive` INT(10) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`ID`),
			  KEY `poolname` (`poolname`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);
		if(!$q->ok){return;}

		$sql="CREATE TABLE IF NOT EXISTS `nginx_pools_list` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `zorder` smallint(1) NOT NULL DEFAULT '1',
			  `poolid` INT UNSIGNED NOT NULL,
			  `sourceid` INT UNSIGNED NOT NULL,
			  `max_fails` INT UNSIGNED NOT NULL,
			  `fail_timeout` INT UNSIGNED NOT NULL,
			  `backuptype` CHAR(40) NOT NULL DEFAULT 'backup',
			  PRIMARY KEY (`ID`),
			  KEY `zorder` (`zorder`),
			  KEY `poolid` (`poolid`),
			  KEY `sourceid` (`sourceid`)
			)  ENGINE = MYISAM;";
		$q->QUERY_SQL($sql,$this->database);		
		if(!$q->ok){return;}
		


		if(!$q->TABLE_EXISTS("authenticator_rules")){
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_rules` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `rulename` CHAR(255)  NOT NULL,
			  `explain` TEXT,
			  `enabled` smallint(1)  NOT NULL DEFAULT '1',
			  `cachetime` INT NOT NULL DEFAULT '15',
			  PRIMARY KEY (`ID`),
			  KEY `rulename` (`rulename`),
			  KEY `cachetime` (`cachetime`),
			  KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
		}		
		if(!$q->TABLE_EXISTS("authenticator_sourceslnk")){
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_sourceslnk` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `groupid` INT NOT NULL,
			  `ruleid` INT NOT NULL,
			  `zorder` smallint(2) NOT NULL,
			  `zmd5` VARCHAR(90)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupid` (`groupid`),
			  KEY `ruleid` (`ruleid`),
			  KEY `zorder` (`zorder`),
			  UNIQUE KEY `zmd5` (`zmd5`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}
		}
		
		if(!$q->TABLE_EXISTS("authenticator_authlnk")){
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_authlnk` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `groupid` INT NOT NULL,
			  `ruleid` INT NOT NULL,
			  `zorder` smallint(2) NOT NULL,
			  `zmd5` VARCHAR(90)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupid` (`groupid`),
			  KEY `ruleid` (`ruleid`),
			  KEY `zorder` (`zorder`),
			  UNIQUE KEY `zmd5` (`zmd5`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}
		}		
		
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_groups` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `group_type` INT NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `groupname` VARCHAR(128)  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `group_type` (`group_type`),
			  KEY `enabled` (`enabled`),
			  KEY `groupname` (`groupname`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}
			
			
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_auth` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `group_type` INT NOT NULL,
			  `enabled` smallint(1) NOT NULL,
			  `groupname` VARCHAR(128)  NOT NULL,
			  `params` TEXT  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `group_type` (`group_type`),
			  KEY `enabled` (`enabled`),
			  KEY `groupname` (`groupname`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}			
			
			
			$sql="CREATE TABLE IF NOT EXISTS `authenticator_items` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `pattern` VARCHAR(255) NOT NULL,
			  `groupid` INT  NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `groupid` (`groupid`),
			  KEY `pattern` (`pattern`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}			
				
		
		$sql="CREATE TABLE IF NOT EXISTS `reverse_privs` (
			 `ID` INT NOT NULL AUTO_INCREMENT,
			 `servername` CHAR(255)  NOT NULL,
			 `uid` CHAR(255)  NOT NULL,
			 `sourceid` INT NOT NULL,
			 PRIMARY KEY (`ID`),
			 KEY `servername` (`servername`),
			 KEY `sourceid` (`sourceid`),
			 KEY `uid` (`uid`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			if(!$q->ok){return;}
			
			
			$nginx_sources=new nginx_sources(0);
			$nginx_sources->PatchTables();
			
		
		if(!$q->FIELD_EXISTS("reverse_privs", "sourceid")){
			$q->QUERY_SQL("ALTER TABLE `reverse_privs` ADD `sourceid` INT NOT NULL, ADD INDEX ( `sourceid`)");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		
		if(!$q->FIELD_EXISTS("reverse_www", "enabled")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `enabled` TINYINT( 1 ) NOT NULL DEFAULT '1', ADD INDEX ( `enabled`)");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		if(!$q->FIELD_EXISTS("reverse_www", "poolid")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `poolid` INT( 10 ) NOT NULL DEFAULT '0', ADD INDEX ( `poolid`)");
			if(!$q->ok){echo $q->mysql_error_html();}
		}

		if(!$q->FIELD_EXISTS("reverse_www", "tmperrordisabled")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `tmperrordisabled` smallint(1) NOT NULL DEFAULT '0', ADD INDEX ( `tmperrordisabled`)");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		

		
		
			
		if(!$q->FIELD_EXISTS("reverse_www", "cacheid")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `cacheid` INT( 10 ) NOT NULL DEFAULT '0', ADD INDEX ( `cacheid` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		if(!$q->FIELD_EXISTS("reverse_www", "default_server")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `default_server` smallint( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `default_server` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		
		$errors=$this->errors_page;
		while (list ($key, $value) = each ($errors) ){
			if(!$q->FIELD_EXISTS("reverse_www", "$value")){
				$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `$value` INT( 10 ) NOT NULL DEFAULT '0', ADD INDEX ( `$value` )");
				if(!$q->ok){echo $q->mysql_error_html();}
			}
		}
		
		
		if(!$q->FIELD_EXISTS("reverse_www", "replaceid")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `replaceid` INT( 10 ) NOT NULL DEFAULT '0', ADD INDEX ( `replaceid` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		if(!$q->FIELD_EXISTS("nginx_replace", "groupid")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace` ADD `groupid` INT NOT NULL DEFAULT '0', ADD INDEX ( `groupid` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}	

		if(!$q->FIELD_EXISTS("reverse_dirs", "ForceRedirect")){
			$q->QUERY_SQL("ALTER TABLE `reverse_dirs` ADD `ForceRedirect` smallint(1) NOT NULL DEFAULT '1' , ADD INDEX ( `ForceRedirect`)");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		
		if(!$q->FIELD_EXISTS("reverse_www", "owa")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `owa` smallint( 1 ) NOT NULL DEFAULT '0', ADD INDEX ( `owa` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		

		if(!$q->FIELD_EXISTS("nginx_caches", "CurrentSize")){
			$q->QUERY_SQL("ALTER TABLE `nginx_caches` ADD `CurrentSize` BIGINT UNSIGNED DEFAULT '0', ADD INDEX ( `CurrentSize` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		if(!$q->FIELD_EXISTS("nginx_pools", "keepalive")){
			$q->QUERY_SQL("ALTER TABLE `nginx_pools` ADD `keepalive` BIGINT UNSIGNED DEFAULT '0', ADD INDEX ( `keepalive` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}	
		if(!$q->FIELD_EXISTS("nginx_replace_www", "tokens")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `tokens` CHAR( 20 ) NOT NULL DEFAULT 'g'");
			if(!$q->ok){echo $q->mysql_error_html();}
		}
		if(!$q->FIELD_EXISTS("nginx_replace_www", "zorder")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `zorder` INT( 10 ) NOT NULL DEFAULT '0',ADD INDEX ( `zorder` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}

		if(!$q->FIELD_EXISTS("nginx_replace_www", "AsRegex")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_www` ADD `AsRegex` smallint( 1 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `AsRegex` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		if(!$q->FIELD_EXISTS("nginx_replace_folder", "AsRegex")){
			$q->QUERY_SQL("ALTER TABLE `nginx_replace_folder` ADD `AsRegex` smallint( 1 ) NOT NULL DEFAULT '0' ,ADD INDEX ( `AsRegex` )");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
		
		if(!$q->FIELD_EXISTS("reverse_www", "limit_rate_after")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `limit_rate_after` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "limit_rate")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `limit_rate` smallint(10) NOT NULL DEFAULT 0");if(!$q->ok){echo $q->mysql_error_html();}}
		 
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_read_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_read_timeout` smallint(3) NOT NULL DEFAULT 300");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_send_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_send_timeout` smallint(3) NOT NULL DEFAULT 300");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffers")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffers` VARCHAR(20) NOT NULL DEFAULT '8 8k'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffer_size")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffer_size` smallint(10) NOT NULL DEFAULT '8'");if(!$q->ok){echo $q->mysql_error_html();}}

		if(!$q->FIELD_EXISTS("reverse_www", "proxy_cache_min_uses")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_cache_min_uses` smallint(3) NOT NULL DEFAULT '1'");if(!$q->ok){echo $q->mysql_error_html();}}

		if(!$q->FIELD_EXISTS("reverse_www", "proxy_cache_min_uses")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_cache_min_uses` smallint(3) NOT NULL DEFAULT '1'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_buffering")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_buffering` smallint(1) NOT NULL DEFAULT '1'");if(!$q->ok){echo $q->mysql_error_html();}}	

		if(!$q->FIELD_EXISTS("reverse_www", "RedirectQueries")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `RedirectQueries` VARCHAR(255)");if(!$q->ok){echo $q->mysql_error_html();}}
			
		if(!$q->FIELD_EXISTS("reverse_www", "EnableHSTS")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableHSTS` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "RestrictIFrames")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `RestrictIFrames` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "XSSBrowser")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `XSSBrowser` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "EnableCSP")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableCSP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "proxy_cache_valid")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `proxy_cache_valid` smallint(3) NOT NULL DEFAULT '4320'");if(!$q->ok){echo $q->mysql_error_html();}}		
		
		
		
		
		if(!$q->FIELD_EXISTS("reverse_www", "EnableSSLOCSP")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `EnableSSLOCSP` smallint(1) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "ssl_protocols")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `ssl_protocols` VARCHAR(255)");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "ssl_ciphers")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `ssl_ciphers` TEXT");if(!$q->ok){echo $q->mysql_error_html();}}		
		
		if(!$q->FIELD_EXISTS("reverse_www", "LimitCnx")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `LimitCnx` smallint(5) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
		if(!$q->FIELD_EXISTS("reverse_www", "LimitReqs")){$q->QUERY_SQL("ALTER TABLE `reverse_www`
		ADD `LimitReqs` smallint(5) NOT NULL DEFAULT '0'");if(!$q->ok){echo $q->mysql_error_html();}}
		
			
		if(!$q->FIELD_EXISTS("reverse_www", "certificate")){$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `certificate` VARCHAR( 255 )");if(!$q->ok){echo $q->mysql_error_html();}}

		if(!$q->FIELD_EXISTS("reverse_www", "debug")){
			$q->QUERY_SQL("ALTER TABLE `reverse_www` ADD `debug` smallint(1) NOT NULL DEFAULT 0");
			if(!$q->ok){echo $q->mysql_error_html();}
		}		
			
			
		
	}
	
	private function KeyNameFromServerName($servername){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM reverse_www WHERE `servername`='$servername'"));
		return $ligne["aclname"];
		
		
	}
	
	private function cache_peer_build($servername){
		$squidR=new squidbee();
		$ServerKey=$this->KeyNameFromServerName($servername);
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_www WHERE `servername`='$servername'"));
		$cache_peer_id=$ligne["cache_peer_id"];
		$certificate=$ligne["certificate"];
		$prefix="ssl_";
		
		if($cache_peer_id==0){
			$GenerateSSL=1;
			$hostname="127.0.0.1";
			$port=82;
			$ssl=1;
			$q2=new mysql();
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT `useSSL`,`sslcertificate` FROM `freeweb` WHERE `servername`='$servername'","artica_backup"));
			if($ligne2["useSSL"]==1){$GenerateSSL=0;$port=447;}
			if($ligne2["sslcertificate"]<>null){$certificate=$ligne2["sslcertificate"];}
			
			
		}else{
			
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `ssl`,`ipaddr`,`port`,`forceddomain` FROM `reverse_sources` WHERE `ID`='$cache_peer_id'"));
			$hostname=$ligne2["ipaddr"];
			$ssl=$ligne2["ssl"];
			$port=$ligne2["port"];
			$forceddomain=$ligne2["forceddomain"];
		}
		
		
		echo "Starting......: ".date("H:i:s")." [PEE] $ServerKey: $hostname:$port cert:{$certificate}\n";
		
		if($forceddomain==null){$forceddomain=$servername;}
		
		
		if(strpos($forceddomain,"/")>0){
			$forceddomain=str_replace("http://", "", $forceddomain);
			$forceddomain=str_replace("https://", "", $forceddomain);
			$uri="http://$forceddomain";
			$urlEXt=parse_url($forceddomain);
			$forceddomain=$urlEXt["host"];
		}
		
	
		
		$this->cache_peer_defs[]="cache_peer $hostname parent $port 0 name=$ServerKey no-query no-digest no-netdb-exchange originserver login=PASS forceddomain=$forceddomain";
		
		$CertificatesToken=$squidR->SaveCertificate($certificate,true);
		if($ssl==1){
			if($GenerateSSL==0){
				$ssllags="ssl sslflags=NO_SESSION_REUSE,DONT_VERIFY_PEER,DONT_VERIFY_DOMAIN,NO_DEFAULT_CA";
			}
			$this->cache_peer_defs[]="cache_peer $hostname parent $port 0 front-end-https=on $ssllags $CertificatesToken name={$prefix}$ServerKey ssldomain=$forceddomain forceddomain=$forceddomain no-query no-digest no-netdb-exchange originserver login=PASS";
		}
		
		
	}
	
	private function acl_destination($servername,$cache_peer_id,$ssl=0){
		$squid=new squidbee();
		$ServerKey=$this->KeyNameFromServerName($servername);
		$prefix=null;$ACCESS_PREFIX="!CONNECTREV ";
		$urls=null;
		if($ssl==1){
				$prefix="ssl_";
				$urls="s";
				$ACCESS_PREFIX="CONNECTREV ";
				if($cache_peer_id==0){
					if($this->FreeWebDisableSSL==1){return;}
				}
		}

		
		
		if($servername)
		
		echo "Starting......: ".date("H:i:s")." [PEE] $ServerKey $servername ID:$cache_peer_id ssl=$ssl\n";
		$AClVhostName="{$prefix}Dom{$ServerKey}";
		$servernameregex=$squid->StringToRegex("http{$urls}://$servername");
		$this->ACLS_BY_HOSTS[$AClVhostName]="acl $AClVhostName url_regex -i ^$servernameregex";
		$this->cache_peer_domain[$prefix.$ServerKey][$servername]=true;
		$this->never_direct_allow[$AClVhostName]=$AClVhostName;
		
		$this->cache_peer_access_allow[$prefix.$ServerKey]=$AClVhostName;
		$this->miss_access_allow[$AClVhostName]=$AClVhostName;
		$this->http_access_allow[$AClVhostName]=$AClVhostName;
		
		
	}
	
	private function cache_peer_access_denyother($AClVhostName){
		$cache_peer_access_allow=$this->ACLS_BY_HOSTS;
		unset($cache_peer_access_allow[$AClVhostName]);
		while (list ($PeerName, $line) = each ($cache_peer_access_allow) ){
			if($PeerName==$AClVhostName){continue;}
			$f[]="!$PeerName";
		}
		
		return @implode(" ", $f);
		
	}
	
	
	public function acl_by_cache_peer(){
		$unix=new unix();
		
		$sql="SELECT `servername`,`cache_peer_id` FROM `reverse_www` WHERE `enabled`=1";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){if($GLOBALS['AS_ROOT']){echo "Starting......: ".date("H:i:s")." $sql failed -> die()\n";die();}}
		
		if(mysql_num_rows($results)==0){return null;}
		
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne["servername"]=trim($ligne["servername"]);
			$ServerKey=$this->KeyNameFromServerName($ligne["servername"]);
			if($ligne["servername"]==null){continue;}
			$cache_peer_id=$ligne["cache_peer_id"];
			$servername=$ligne["servername"];
			if($cache_peer_id==1){$ligne["ssl"]=1;}
			
			$this->acl_destination($servername,$cache_peer_id,0);
			if($cache_peer_id>0){
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT `ssl`,`ipaddr`,`port` FROM `reverse_sources` WHERE `ID`='$cache_peer_id'"));
				if($ligne["ssl"]==1){
					$this->acl_destination($servername,$cache_peer_id,1);
				}
			}else{
				$this->acl_destination($servername,0,1);
			}
			
			
			$this->cache_peer_build($servername);
		}
		
		$f[]="# Reverse Proxy build on ". date("Y-m-d H:i:s");
		$f[]="##############################################";
		if( count($this->cache_peer_defs)==0 ){$f[]="# cache_peer_defs -> 0";return @implode("\n", $f)."\n";}
		$f[]="# Peers definitions...";
		$f[]=@implode("\n", $this->cache_peer_defs);
		$f[]="";
		
		$f[]="\n# ACLs definitions...";
		$f[]="acl badreq http_status 400";
		$f[]="acl CONNECTREV localport 443";
		while (list ($PeerName, $aclsText) = each ($this->ACLS_BY_HOSTS) ){
			$f[]=$aclsText;
			
		}
		
		$f[]="\n# Peer domains definitions...";
		while (list ($PeerName, $array) = each ($this->cache_peer_domain) ){
			$t=array();
			while (list ($domainname, $none) = each ($array) ){$t[]=$domainname;}
			//$f[]="cache_peer_domain $PeerName ".@implode($t);
		}

		$f[]="\n# Peer access definitions...";
		$cache_peer_access_allow=$this->cache_peer_access_allow;
		while (list ($PeerName, $line) = each ($cache_peer_access_allow) ){
			$deny=$this->cache_peer_access_denyother($line);
			$f[]="cache_peer_access $PeerName allow $deny $line";
			$f[]="cache_peer_access $PeerName deny all";
		}
			
		
			
		

		$f[]="\n# do not try to browse outside the peer";
		while (list ($index, $line) = each ($this->never_direct_allow) ){
			$f[]="never_direct allow $line";
		}
		while (list ($index, $line) = each ($this->miss_access_allow) ){
			$f[]="miss_access allow $line";
		}		
		
		$f[]="\n# Allow Access definitions";
		while (list ($index, $line) = each ($this->http_access_allow) ){
			$f[]="http_access allow $line";
		}	
		$f[]="\n# Other definitions for reverse mode";
		$f[]="ssl_unclean_shutdown on";
		$f[]="negative_ttl 0";
		$f[]="cache deny badreq";		
		$f[]="##############################################";
		$f[]="";
		return @implode("\n",$f);
	}
	
	
}