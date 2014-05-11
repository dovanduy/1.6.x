<?php
if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
if(function_exists("debug_mem")){debug_mem();}
include_once(dirname(__FILE__)."/class.mysql.inc");
include_once(dirname(__FILE__)."/class.system.nics.inc");
		
class mysql_shorewall{
	private 	$MySQLConnectionType=1;
	private 	$mysql_admin="root";
	private 	$mysql_password=null;
	private  	$mysql_port;
	private 	$mysql_server;
	public  	$mysql_error=null;
	private  	$mysql_errornum=0;
	private  	$hostname;
	public 		$ok=false;
	private  	$CurrentPageName;
	private  	$mysql_connection;
	public 		$last_id;
	private  	$UseMysql=true;
	private 	$MySQLSyslogType=1;
	public 		$affected_rows=0;
	private  	$TestIngConnectionPerformed=false;
	private  	$SocketName="/var/run/mysqld/shorewall-db.sock";
	private 	$database="shorewall";
	private 	$MySQLLogsTypeTextAR=array("{not_set}","{server}","{client}");
	public 		$INTERFACE_OPT=array();
	public 		$ZONES_RESERVED_WORDS=array();
	public 		$POLICIES=array();
	public 		$RULES_POLICIES=array();
	public		$RULES_POLICIES_GROUP_TYPE=array();
	private 	$SHOREWALL_INSTALLED=false;
	public 		$zones_type=array("ipv4"=>"{shorewall_ipv4}","ipsec"=>"{shorewall_ipsec}",
			"firewall"=>"{shorewall_firewall}");
	
	public function mysql_shorewall(){
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
		$settings=dirname(__FILE__).'/settings.inc';
		$phpPage=basename($_SERVER["SCRIPT_FILENAME"]);
	  	$this->CurrentPageName=$phpPage;
		$this->PrepareMySQLClass();
		$this->CREATE_DATABASE($this->database);
		$this->INTERFACE_OPT["dhcp"]=true;
		$this->INTERFACE_OPT["noping"]=true;
		$this->INTERFACE_OPT["filterping"]=true;
		$this->INTERFACE_OPT["norfc1918"]=true;
		$this->INTERFACE_OPT["multi"]=true;
		$this->INTERFACE_OPT["routefilter"]=true;
		$this->INTERFACE_OPT["dropunclean"]=true;
		$this->INTERFACE_OPT["logunclean"]=true;
		$this->INTERFACE_OPT["blacklist"]=true;
		$this->INTERFACE_OPT["maclist"]=true;
		$this->INTERFACE_OPT["tcpflags"]=true;
		$this->INTERFACE_OPT["proxyarp"]=true;
		$this->INTERFACE_OPT["logmartians"]=true;
		$this->INTERFACE_OPT["routeback"]=true;
		$this->INTERFACE_OPT["arp_filter"]=true;
		//$this->INTERFACE_OPT["arp_ignore"]=true;
		$this->INTERFACE_OPT["nosmurfs"]=true;
		//$this->INTERFACE_OPT["detectnets"]=true;
		$this->INTERFACE_OPT["upnp"]=true;
		$this->INTERFACE_OPT["rpfilter"]=true;
		
		$this->POLICIES["ACCEPT"]="{FW_ACCEPT}";
		$this->POLICIES["REJECT"]="{FW_REJECT}";
		$this->POLICIES["QUEUE"]="{FW_QUEUE}";
		$this->POLICIES["CONTINUE"]="{FW_CONTINUE}";
		$this->POLICIES["NONE"]="{FW_NONE}";
		
		$this->ZONES_RESERVED_WORDS["all"]=true;
		$this->ZONES_RESERVED_WORDS["none"]=true;
		$this->ZONES_RESERVED_WORDS["any"]=true;
		$this->ZONES_RESERVED_WORDS["SOURCE"]=true;
		$this->ZONES_RESERVED_WORDS["DEST"]=true;
		
		
		$this->RULES_POLICIES["ACCEPT"]="{FW_ACCEPT}";
		$this->RULES_POLICIES["REJECT"]="{FW_REJECT}";
		$this->RULES_POLICIES["DNAT"]="{FW_DNAT}";
		$this->RULES_POLICIES["REDIRECT"]="{FW_REDIRECT}";
		$this->RULES_POLICIES["DROP"]="{FW_DROP}";
		$this->RULES_POLICIES["LOG"]="{FW_LOG}";
		
		$this->RULES_POLICIES_ICON["ACCEPT"]="ok24.png";
		$this->RULES_POLICIES_ICON["REJECT"]="warn-red-24.png";
		$this->RULES_POLICIES_ICON["DNAT"]="24-redirect.png";
		$this->RULES_POLICIES_ICON["DROP"]="24-red.png";
		$this->RULES_POLICIES_ICON["LOG"]="24-logs.png";
		
		$this->RULES_POLICIES_GROUP_TYPE["net"]="{ips_and_ranges}";
		$this->RULES_POLICIES_GROUP_TYPE["mac"]="{MAC}";
		$this->RULES_POLICIES_GROUP_TYPE["port"]="{ports}";
		@include($settings);
		if(isset($_GLOBAL["SHOREWALL_INSTALLED"])){$this->SHOREWALL_INSTALLED=$_GLOBAL["SHOREWALL_INSTALLED"];}
		
		
	}
		
		
	private function PrepareMySQLClass(){
			$sock=new sockets();
			unset($GLOBALS["MYSQL_PARAMETERS"]);
			unset($_SESSION["MYSQL_PARAMETERS"]);
			$this->MySQLConnectionType=$sock->GET_INFO("MySQLShorewallType");
			if(!is_numeric($this->MySQLSyslogType)){$this->MySQLSyslogType=1;}
			if($this->MySQLConnectionType==0){$this->MySQLConnectionType=1;}
			$this->LogRotatePath=$sock->GET_INFO("LogRotatePath");
			if($this->LogRotatePath==null){$this->LogRotatePath="/home/logrotate";}
			$this->MYSQLTypeText=$this->MySQLLogsTypeTextAR[$this->MySQLSyslogType];
			$this->OutputDebugVerbose("MySQLSyslogType=$this->MySQLSyslogType");
		
			if($this->MySQLConnectionType==1){
				$def["SocketPath"]="/var/run/mysqld/shorewall-db.sock";
				if(!is_file("/var/run/mysqld/shorewall-db.sock")){return;}
				$this->SocketName=$def["SocketPath"];
				$def["mysql_admin"]="root";
				$def["mysql_password"]=null;
				$def["TryTCP"]=false;
				$this->SocketName=$def["SocketPath"];
				$this->mysql_admin=$def["mysql_admin"];
				$this->mysql_password=$def["mysql_password"];
				$this->mysql_port=$def["mysql_port"];
				$this->mysql_server="127.0.0.1";
				$this->ClassSQL=new mysql($def);
				$this->MYSQL_CMDLINES="--protocol=socket --socket={$def["SocketPath"]} -u root";
				$this->dbidsn="['DBI:mysql:database=$this->database;mysql_socket=$this->SocketName', '$this->mysql_admin', '$this->mysql_password']";
			}
		
		
			if($this->MySQLConnectionType==2){
				$this->EnableSyslogDB=1;
				$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLShoreWallParams")));
				$username=$TuningParameters["username"];
				$password=$TuningParameters["password"];
				$mysqlserver=$TuningParameters["mysqlserver"];
				$RemotePort=$TuningParameters["RemotePort"];
				$def["SocketPath"]=null;
				$def["mysql_admin"]=$username;
				$def["mysql_password"]=$password;
				$def["TryTCP"]=true;
				$def["mysql_port"]=$RemotePort;
				$def["mysql_server"]=$mysqlserver;
				$this->SocketName=$def["SocketPath"];
				$this->mysql_admin=$def["mysql_admin"];
				$this->mysql_password=$def["mysql_password"];
				$this->mysql_port=$def["mysql_port"];
				$this->mysql_server=$mysqlserver;
				$this->ClassSQL=new mysql($def);
				$pwd=null;
				if($password<>null){ $pwd=" -p$password";}
				$this->MYSQL_CMDLINES="--protocol=tcp --user=$username$pwd --host=$mysqlserver --port=$RemotePort";
				$this->dbidsn="['DBI:mysql:database=$this->database;host=$mysqlserver;port=$RemotePort', '$username', '$password']";
			}
			
		}		
	
	
	
	
	private function writelogs($text=null,$function=null,$line=0){
			$file_source="/usr/share/artica-postfix/ressources/logs/web/mysql.showrewall.debug";
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
			@fwrite($f, "$date:[".__CLASS__."/$function()][{$_SERVER['REMOTE_ADDR']}]:: $text (L.$line)\n");
			@fclose($f);
		}

	FUNCTION TABLE_STATUS($table){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		return @mysql_fetch_array($this->QUERY_SQL("SHOW TABLE STATUS WHERE Name='$table'",$this->database));		
	}
	
	public function BuildHourTable(){
		$function=__FUNCTION__;
		$currentHour=date("YmdH");
		$sql="CREATE TABLE IF NOT EXISTS `FWH_$currentHour` (
		`ZDATE` DATETIME,
		ZONE_FROM VARCHAR(20),
		ZONE_TO VARCHAR(20),
		ACTION VARCHAR(20),
		NIC_IN VARCHAR(20),
		NIC_OUT VARCHAR(20),
		MAC_SRC VARCHAR(40),
		IP_SRC VARCHAR(40),
		IP_DST VARCHAR(40),
		PROTO VARCHAR(20),
		PORT INT(20),
		 KEY `ZDATE` (`ZDATE`),
		 KEY `ZONE_FROM` (`ZONE_FROM`),
		 KEY `ZONE_TO` (`ZONE_TO`),
		 KEY `ACTION` (`ACTION`),
		 KEY `NIC_IN` (`NIC_IN`),
		 KEY `NIC_OUT` (`NIC_OUT`),
		 KEY `MAC_SRC` (`MAC_SRC`),
		 KEY `IP_SRC` (`IP_SRC`),
		 KEY `IP_DST` (`IP_DST`),
		 KEY `PROTO` (`PROTO`),
		 KEY `PORT` (`PORT`)
		 ) ENGINE=MEMORY;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);
		return false;}
		

	}
	
	public function NIC_UPDATE($eth){
		if(preg_match("#^virt([0-9]+)#", $eth,$re)){
			$q=new mysql();
			$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE ID='{$re[1]}'","artica_backup"));
			$this->UPDATE_ZONE($eth,$ligne["netzone"]);
			
		}
		$this->UPDATE_INTERFACE($eth);
		
	}
	
	private function UPDATE_ZONE($eth,$zone,$type=null){
		$zone=replace_accents($zone);
		$zone=str_replace(" ", "", $zone);
		$zone=substr($zone, 0,15);
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL("SELECT * FROM fw_zones WHERE eth='$eth'"));
		if($ligne["zone"]==null){
			if($type==null){$type="ipv4";}
			$this->QUERY_SQL("INSERT INTO fw_zones (`zone`,`eth`,`type`) VALUES ('$zone','$eth','$type')");
			
		}else{
			if($type==null){$type=$ligne["type"];}
			$this->QUERY_SQL("UPDATE fw_zones SET `zone`='$zone',`type`='$type' WHERE `eth`='$eth'");
		}
	}
	
	public function FIND_NET($eth){
		
		if(preg_match("#^virt([0-9]+)#", $eth,$re)){
			$q=new mysql();
			$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nics_switch WHERE ID='{$re[1]}'","artica_backup"));
			$cdir=$ligne["cdir"];
			if($cdir<>null){return $cdir;}
			$nic=new system_nic();
			return $nic->GetCDIRNetwork($ligne["ipaddr"], $ligne["netmask"]);
			
		}
		
		if(preg_match("#^eth[0-9]+$#", $eth,$re)){
			$nic=new system_nic($eth);
			return $$nic->GetCDIRNetwork($nic->IPADDR, $nic->NETMASK);
		}
		
		
		
	}
	
	
	private function UPDATE_INTERFACE($eth){
		$ligne=@mysql_fetch_array($this->QUERY_SQL("SELECT eth FROM fw_interfaces WHERE eth='$eth'"));
		if($ligne["eth"]==null){
			$this->QUERY_SQL("INSERT IGNORE INTO `fw_interfaces` (`eth`) VALUES ('$eth')");
		}
	}
	
	
	public function BuildDayTable($xtime){
		$function=__FUNCTION__;
		$currentDay=date("Ymd",$xtime);
		$sql="CREATE TABLE IF NOT EXISTS `FWD_$currentDay` (
		`ZMD5` VARCHAR(90) NOT NULL,
		`HITS` BIGINT UNSIGNED NOT NULL,
		`HOUR` smallint(2) NOT NULL,
		ZONE_FROM VARCHAR(20),
		ZONE_TO VARCHAR(20),
		ACTION VARCHAR(20),
		NIC_IN VARCHAR(20),
		NIC_OUT VARCHAR(20),
		MAC_SRC VARCHAR(40),
		IP_SRC VARCHAR(40),
		IP_DST VARCHAR(40),
		PROTO VARCHAR(20),
		PORT INT(20),
		UNIQUE KEY `ZMD5` (`ZMD5`),
		KEY `HOUR` (`HOUR`),
		KEY `ZONE_FROM` (`ZONE_FROM`),
		KEY `ZONE_TO` (`ZONE_TO`),
		KEY `ACTION` (`ACTION`),
		KEY `NIC_IN` (`NIC_IN`),
		KEY `NIC_OUT` (`NIC_OUT`),
		KEY `MAC_SRC` (`MAC_SRC`),
		KEY `IP_SRC` (`IP_SRC`),
		KEY `IP_DST` (`IP_DST`),
		KEY `PROTO` (`PROTO`),
		KEY `PORT` (`PORT`)
		) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);
		return false;}
	
		return true;
	
		}	
	
	
	
	public function LIST_TABLES_HOURS(){
		if(isset($GLOBALS["FW_LIST_TABLES_HOURS"])){return $GLOBALS["FW_LIST_TABLES_HOURS"];}
		$array=array();
		$sql="SELECT table_name as c FROM information_schema.tables 
		WHERE table_schema = '$this->database' AND table_name LIKE 'FWH_%'";
		$results=$this->QUERY_SQL($sql);
		if(!$this->ok){writelogs("Fatal Error: $this->mysql_error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);return array();}
		if($GLOBALS["VERBOSE"]){echo $sql." => ". mysql_num_rows($results)."\n";}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(preg_match("#FWH_([0-9]+)#", $ligne["c"],$re)){
				$numeric=$this->TIME_FROM_PREFIX_HOUR_TABLE($re[1]);
				$GLOBALS["FW_LIST_TABLES_HOURS"][$ligne["c"]]=$numeric;
				$array[$ligne["c"]]=$numeric;
			}
		}
		return $array;
	}
	
	private function TIME_FROM_PREFIX_HOUR_TABLE($numeric){
		$intval=$numeric;
		$Cyear=substr($intval, 0,4);
		$CMonth=substr($intval,4,2);
		$CDay=substr($intval,6,2);
		$CDay=str_replace("_", "", $CDay);
		$CHour=substr($intval,8,2);
		return strtotime("$Cyear-$CMonth-$CDay $CHour:00:00");
	}
	
	public function CheckTables(){
		$function=__FUNCTION__;
		$sql="CREATE TABLE IF NOT EXISTS `fw_zones` (
		`ID` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`zone` varchar(5) NOT NULL,
		`eth` varchar(20) NULL,
		`type` varchar(90) NOT NULL,
		`zOrder` INT(3) NOT NULL DEFAULT 0,
		`comment` varchar(255) NOT NULL,
		`OPTIONS` varchar(128) NOT NULL,
		`IN_OPTIONS` varchar(128) NOT NULL,
		`OUT_OPTIONS` varchar(128) NOT NULL,
		 KEY `zone` (`zone`),
		 KEY `type` (`type`),
		 KEY `eth` (`eth`),
		 KEY `zOrder` (`zOrder`),
		 KEY `comment` (`comment`)
		) ENGINE=MYISAM;";
		
		
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);return false;}
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_interfaces` (
		`eth` varchar(20) NOT NULL PRIMARY KEY,
		`BRD` varchar(128) NOT NULL,
		`dhcp` smallint(1) NOT NULL DEFAULT 0,
		`noping` smallint(1) NOT NULL DEFAULT 0,
		`filterping` smallint(1) NOT NULL DEFAULT 0,
		`routestopped` smallint(1) NOT NULL DEFAULT 0,
		`norfc1918` smallint(1) NOT NULL DEFAULT 0,
		`multi` smallint(1) NOT NULL DEFAULT 0,
		`routefilter` smallint(1) NOT NULL DEFAULT 0,
		`dropunclean` smallint(1) NOT NULL DEFAULT 0,
		`logunclean` smallint(1) NOT NULL DEFAULT 0,
		`blacklist` smallint(1) NOT NULL DEFAULT 0,
		`maclist` smallint(1) NOT NULL DEFAULT 0,
		`tcpflags` smallint(1) NOT NULL DEFAULT 0,
		`proxyarp` smallint(1) NOT NULL DEFAULT 0,
		`logmartians` smallint(1) NOT NULL DEFAULT 0,
		`routeback` smallint(1) NOT NULL DEFAULT 0,
		`arp_filter` smallint(1) NOT NULL DEFAULT 0,
		`arp_ignore` smallint(1) NOT NULL DEFAULT 0,
		`nosmurfs` smallint(1) NOT NULL DEFAULT 0,
		`detectnets` smallint(1) NOT NULL DEFAULT 0,
		`upnp` smallint(1) NOT NULL DEFAULT 0 ) ENGINE=MYISAM;";
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);return false;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `zones_policies` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`zone_id_from` INT UNSIGNED,
			`zone_id_to` INT UNSIGNED,
			`policy_name` VARCHAR( 255 ) NOT NULL,
			`policy` VARCHAR(40) NOT NULL DEFAULT 'ACCEPT',
			`log` smallint(1) NOT NULL DEFAULT 0,
			`zOrder` INT(3) NOT NULL DEFAULT 0,
			 KEY `zone_id_from` (`zone_id_from`),
			 KEY `zone_id_to` (`zone_id_to`),
			 KEY `log` (`log`),
			 KEY `policy_name` (`policy_name`),
			 KEY `zOrder` (`zOrder`)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);
		return false;}
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_rules` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`zone_id_from` INT UNSIGNED,
			`zone_id_to` INT UNSIGNED,
			`rulename` VARCHAR( 255 ) NOT NULL,
			`ACTION` VARCHAR(40) NOT NULL DEFAULT 'ACCEPT',
			`PROTO` VARCHAR(40) NOT NULL DEFAULT 'tcp',
			`RATELIM` smallint(1) NOT NULL DEFAULT 0,
			`RATELIMIT` VARCHAR(100),
			`log` smallint(1) NOT NULL DEFAULT 0,
			`zOrder` INT(3) NOT NULL DEFAULT 0,
			 KEY `zone_id_from` (`zone_id_from`),
			 KEY `RATELIM` (`RATELIM`),
			 KEY `zone_id_to` (`zone_id_to`),
			 KEY `log` (`log`),
			 KEY `rulename` (`rulename`),
			 KEY `ACTION` (`ACTION`),
			 KEY `zOrder` (`zOrder`)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);}
		
		
				
		$sql="CREATE TABLE IF NOT EXISTS `fw_objects` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`groupname` VARCHAR( 255 ) NOT NULL,
			`grouptype` VARCHAR(20) NOT NULL,
			 KEY `groupname` (`groupname`),
			 KEY `grouptype` (`grouptype`)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_objects_lnk` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`groupid`  INT( 100 )  NOT NULL,
			`ruleid`  INT( 100 )  NOT NULL,
			`reverse`  smallint(1) NOT NULL DEFAULT 0,
			`INOUT`  smallint(1) NOT NULL DEFAULT 0,
			 KEY `groupid` (`groupid`),
			 KEY `ruleid` (`ruleid`),
			 KEY `INOUT` (`INOUT`)
			) ENGINE=MYISAM;";	

		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_items` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`item` VARCHAR( 255 ) NOT NULL,
			`groupid`  INT( 100 )  NOT NULL,
			 KEY `groupid` (`groupid`),
			 KEY `item` (`item`)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);return false;}

		
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_providers` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`NAME` VARCHAR( 255 ) NOT NULL,
			`NUMBER`  smallint( 2 )  NOT NULL,
			`MARK` VARCHAR(20),
			`DUPLICATE` VARCHAR( 255 ) NOT NULL,
			`INTERFACE` VARCHAR( 20 ) NOT NULL,
			`GATEWAY` VARCHAR( 90 ) NOT NULL,
			`track` smallint( 3 )  NOT NULL,
			`tproxy` smallint( 1 )  NOT NULL,
			`balance` smallint( 1 )  NOT NULL,
			`fallback` smallint( 3 )  NOT NULL,
			 KEY `NAME` (`NAME`),
			 KEY `NUMBER` (`NUMBER`)
			) ENGINE=MYISAM;";
		
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);return false;}
		
		
		$sql="CREATE TABLE IF NOT EXISTS `fw_masq` (
			`ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
			`INTERFACE` VARCHAR( 50 ) NOT NULL,
			`eth`  VARCHAR(20)  NOT NULL,
			`SOURCE` VARCHAR(90),
			`ADDRESS` VARCHAR( 90 ) NOT NULL,
			`PROTO` VARCHAR( 20 ) NOT NULL,
			`PORT` VARCHAR( 255 ) NOT NULL,
			`zOrder` INT(5) NOT NULL,
			 KEY `INTERFACE` (`INTERFACE`),
			 KEY `eth` (`eth`),
			 KEY `SOURCE` (`SOURCE`),
			 KEY `ADDRESS` (`ADDRESS`)
			) ENGINE=MYISAM;";
		
		$this->QUERY_SQL($sql,$this->database);
		if(!$this->ok){$this->ToSyslog("$function(): $this->mysql_error in line ".__LINE__);return false;}		
		
		
		
		
		while (list ($key, $value) = each ($this->ZONES_RESERVED_WORDS) ){
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM fw_zones WHERE zone='$key'"));
			if($ligne["ID"]<1){
				$this->QUERY_SQL("INSERT INTO fw_zones (zone,type) VALUES ('$key','ipv4')");
			}
		}
		
		return true;
		
	}
	
	public function JS_INTERFACE($eth,$fontsize=16){
		$t=$_GET["t"];
		return "<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('shorewall.interface.php?interface-js=yes&ID=$eth&t=$t');\" 
		style='font-size:{$fontsize}px;text-decoration:underline'>";
	}
	public function JS_PROVIDER($ID,$fontsize=16){
		$t=$_GET["t"];
		return "<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('shorewall.providers.php?provider-js=yes&ID=$ID&t=$t');\"
		style='font-size:{$fontsize}px;text-decoration:underline'>";
	}	
	
	
	public function ZONE_DELETE($ID){
		
		$sql="SELECT ID FROM `fw_rules` WHERE zone_id_from='$ID'";
		$results = $this->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {$this->RULE_DELETE($ligne["ID"]);}

		$sql="SELECT ID FROM `fw_rules` WHERE zone_id_to='$ID'";
		$results = $this->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {$this->RULE_DELETE($ligne["ID"]);}		
		
		$this->QUERY_SQL("DELETE FROM zones_policies WHERE zone_id_from='$ID'");
		$this->QUERY_SQL("DELETE FROM zones_policies WHERE zone_id_to='$ID'");
		$this->QUERY_SQL("DELETE FROM fw_zones WHERE ID='$ID'");
		if(!$this->q){echo $this->mysql_error;return;}
		
	}
	public function INTERFACE_DELETE($eth){
		$sql="SELECT ID FROM `fw_providers` WHERE INTERFACE='$eth'";
		$results = $this->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {$this->PROVIDER_DELETE($ligne["ID"]);}
		
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT ID FROM `fw_zones` WHERE `eth`='$eth'"));
		$this->ZONE_DELETE($ligne["ID"]);
		
		$this->QUERY_SQL("DELETE FROM fw_interfaces WHERE eth='$eth'");
		$this->QUERY_SQL("DELETE FROM fw_masq WHERE eth='$eth'");
		
	}
	
	public function RULE_DELETE($ID){
		$this->QUERY_SQL("DELETE FROM fw_rules WHERE ID='$ID'");
		$this->QUERY_SQL("DELETE FROM fw_objects_lnk WHERE ruleid='$ID'");
		
	}
	
	public function PROVIDER_DELETE($ID){
		$ligne=mysql_fetch_array($this->QUERY_SQL("SELECT NAME FROM fw_providers WHERE ID='$ID'"));
		$NAME=$ligne["NAME"];
		$this->QUERY_SQL("DELETE FROM fw_routes WHERE PROVIDER_ID='$ID'");
		$this->QUERY_SQL("UPDATE fw_providers SET DUPLICATE='main' WHERE DUPLICATE='$NAME'");
		$this->QUERY_SQL("DELETE FROM fw_providers WHERE ID='$ID'");
	}
	
	public function GROUP_DELETE($ID){
		$this->QUERY_SQL("DELETE FROM fw_items WHERE groupid='$ID'");
		$this->QUERY_SQL("DELETE FROM fw_objects_lnk WHERE groupid='$ID'");
		$this->QUERY_SQL("DELETE FROM fw_objects WHERE ID='$ID'");
		
	}
	
	public function POLICY_DELETE($ID){
		$this->QUERY_SQL("DELETE FROM zones_policies WHERE ID='$ID'");
		if(!$this->q){echo $this->mysql_error;return;}
	}
	
	function DATABASE_STATUS(){
		if(!function_exists('mysql_connect')){
			$this->writelogs("Error, mysql_connect function does not exists...",__FUNCTION__,__LINE__);
			return false;
		}
		$results=$this->QUERY_SQL("SHOW TABLE STATUS",$this->database);
		$html="<table style='width:100%;margin:5px;border:1px solid #CCCCCC'>
		<tr>
				<td colspan=4 align='left' style='background-color:#CCCCCC'><strong style='font-size:14px'>$database</strong></td>
			</tr>";
		while ($row = mysql_fetch_array($results)) { 
 			$dbsize += $row['Data_length'] + $row['Index_length']; 
 			$html=$html."<tr>
 			<td width=1%><img src='img/fw_bold.gif'></td>
 			<td><strong>{$row["Name"]}</strong></td>
 			<td><strong>{$row["Rows"]}</strong></td>
 			<td><strong>" . FormatBytes($row["Data_length"]/1024)."</td>
 			</tr>";
			
			} 
			
			$html=$html . "
			<tr>
				<td colspan=4 align='right' style='background-color:#CCCCCC'><strong style='font-size:13px'>".FormatBytes($dbsize/1024)."</strong></td>
			</tr>
			
			</table>";
		return $html;
	}
	
	function REPAIR_TABLES(){
		$this->THIS_BD_CONNECT();
		$mysql_result = mysql_query("SHOW TABLES;",$this->mysql_connection);
 		while ($ligne = mysql_fetch_row($mysql_result)){
 			$table_name=$ligne[0];
 			echo "<div><code>Repair $database/$table_name</code></div>";
 			$this->REPAIR_TABLE($this->database,$table_name);
     	}
	}
	
	public function ReadBinaryFile($filepath){
		$filesize=filesize($filepath);
		$fileHandle = fopen($filepath, "r");
		$fileContent = fread($fileHandle, $filesize);
		$fileContent = addslashes($fileContent);
		return $fileContent;
		}
		
		
		

	
	public function EXECUTE_SQL($sql){
		@mysql_close();
		$this->THIS_BD_CONNECT();
		@mysql_unbuffered_query($sql,$this->mysql_connection);
		$errnum=@mysql_errno($this->mysql_connection);
		if($errnum<>0){
				$time=date('h:i:s');
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="Error query to $MysqlServerLog with username $this->mysql_admin Number ($errnum) ($des)";
				writelogs("Error query to $MysqlServerLog with username $this->mysql_admin Number `$errnum`, $this->mysql_error pass:".strlen($this->mysql_password)." length",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				@mysql_close($this->mysql_connection);
				return false;				
		}
		
		@mysql_close($this->mysql_connection);		
		return true;
	}
	
	
	
	private function SendErrorOutput($text,$die=false){
		if($GLOBALS["AS_ROOT"]){return;}
		$currentpage=CurrentPageName();
		$NOOUT["logon.php"]=true;
		$NOOUT["admin.index.php"]=true;
		$NOOUT["admin.top.menus.php"]=true;
		$NOOUT["quicklinks.php"]=true;
		$NOOUT["admin.tabs.php"]=true;
		$NOOUT["admin.index.loadvg.php"]=true;
		$NOOUT["admin.index.status-infos.php"]=true;
		$NOOUT["system.internal.disks.php"]=true;
		$NOOUT["system.mysql.php"]=true;
		$NOOUT["admin.left.php"]=true;
		$NOOUT["Inotify.php"]=true;
		$NOOUT["SambaBrowse.php"]=true;
		if(isset($NOOUT[$currentpage])){return;}
		echo $text;
		if($die){die();}
		
	}
	
	
	
	private function ToSyslog($text,$error=false){
		$text=str_replace("\n", " ", $text);
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("mysql-shorewall", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}
	
	private function THIS_BD_CONNECT($noretry=false,$called=null){
		if($called==null){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
				}
			}
		}
	
		
		if($this->THIS_TestingConnection(false,$called)){
			
			return true;
		}
		if($GLOBALS["VERBOSE"]){echo "THIS_TestingConnection() -> FALSE<br>\n";}
		return false;
	}	
	
	private function is_socket($fpath){
		$results=@stat($fpath);
		$ts=array(0140000=>'ssocket',0120000=>'llink',0100000=>'-file',0060000=>'bblock',0040000=>'ddir',0020000=>'cchar',0010000=>'pfifo');
		$t=decoct($results['mode'] & 0170000); // File Encoding Bit
		if(substr($ts[octdec($t)],1)=="socket"){return true;}
		return false;
	}
	
	private function THIS_TestingConnection($noretry=false,$called=null){
	
		if($this->mysql_connection){return true;}
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
		if($this->MySQLConnectionType==1){
			
				
			if(!$this->is_socket($this->SocketName)){
				$this->mysql_error="$this->SocketName no such socket";
				$this->ToSyslog("$this->SocketName no such socket");
				$this->OutputDebugVerbose("SocketName !!! false");
				$GLOBALS["THIS_TestingConnection"]=false;
				return false;
			}
				
			$bd=@mysql_connect(":$this->SocketName","root",null);
				
			if($bd){
				$this->mysql_connection=$bd;
				$GLOBALS["THIS_TestingConnection"]=true;
				return true;
			}
			if($GLOBALS["VERBOSE"]){echo "mysql_connect $this->SocketName -> error<br>\n";}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->ToSyslog("failed (N:$errnum) \"$des\" $called");
			$this->writelogs("mysql_connect(\":$this->SocketName\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
	
		if($this->MySQLConnectionType==2){
			$bd=@mysql_connect("$this->mysql_server:$this->mysql_port",$this->mysql_admin,$this->mysql_password);
			if($bd){$this->mysql_connection=$bd;return true;}
			$des=@mysql_error(); $errnum=@mysql_errno();
			$this->writelogs("mysql_connect(\":$this->mysql_server:$this->mysql_port\",$this->mysql_admin,$this->mysql_password..failed (N:$errnum) \"$des\" $called ",__FUNCTION__,__LINE__);
			return false;
		}
			
	}	
	

	
	function trace_to_string($array){
		while (list ($num, $ligne) = each ($array) ){
			try {
				if(is_object($ligne)){continue;}
				if(strpos("  $ligne", '/')>0){$ligne=basename($ligne);}
				if(is_array($ligne)){$r=@implode(" ", $ligne);unset($ligne);$ligne=$r;}
			} catch (Exception $e) {
			}
			
			$f[]="$num: $ligne";
		}
		return @implode("\n",$f);
	}
	
	function _mysql_close(){
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
	}
	
	
function myisamchk($db,$table){
	
	$keytask=md5(__FUNCTION__.__CLASS__.$db.$table);
	
	if(isset($GLOBALS[$keytask])){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::Already done";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: Already done...\n";}return;}
		
	$GLOBALS[$keytask]=true;		
		
		
	if($db==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::DB is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: db $db is null, aborting\n";}
		return;
	}
	
	if($table==null){
		$this->mysql_error=$this->mysql_error. ";myisamchk()::table is null, abort";
		if($GLOBALS["VERBOSE"]){echo "myisamchk:: table is null, aborting\n";}
		return;
	}
		
	$GLOBALS[md5(__FUNCTION__.__CLASS__.$db.$table)]=true;

	
	$sock=new sockets();
	$this->mysql_error=$this->mysql_error.base64_decode($sock->getFrameWork("cmd.php?myisamchk=yes&database=$db&table=$table"));
	
}	
	
	function REPAIR_TABLE($database,$table){
		if(!$GLOBALS["AS_ROOT"]){
			echo "<li>REPAIR_TABLE: DB::$database, Table:$table</li>";
		}
		$this->writeLogs("Try to repair $table in database $database",__CLASS__.'/'.__FUNCTION__,__LINE__);
		$sock=new sockets();
		$results=explode("\n", base64_decode($sock->getFrameWork("cmd.php?myisamchk=yes&table=$table&database=$database&MyCURLTIMEOUT=120")));
		if(!$GLOBALS["AS_ROOT"]){
			echo "<li>REPAIR_TABLE: myisamchk results:".@implode("<br>\n", $results)."</li>";
		}		
		
		$sqlerror[]=$this->mysql_error."myisamchk results:".@implode("<br>\n", $results);
		$this->writeLogs("cmd.php?myisamchk=yes&table=$table&database=$database",__CLASS__.'/'.__FUNCTION__,__LINE__);
		
		$sql="REPAIR TABLE $table use_frm";
		$sqlerror[]="REPAIR TABLE $table use_frm<br>\n";
		$this->THIS_BD_CONNECT();
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		
		
		$sql="REPAIR TABLE $table EXTENDED";
		$sqlerror[]="REPAIR TABLE $table EXTENDED";
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
	
	
		$sqlerror[]="myisamchk...";
		$sock->getFrameWork("cmd.php?myisamchk=yes&table=$table&database=$database&nohup=yes");
		
		$sqlerror[]="TABLE_STATUS...";
		$ligne=$this->TABLE_STATUS($table,$database);
		$failed=false;
		$sqlerror[]="TABLE_STATUS:Comment=`{$ligne["Comment"]}`..";
		$this->writeLogs("REPAIR_TABLE: TABLE_STATUS -> `{$ligne["Comment"]}`",__CLASS__.'/'.__FUNCTION__,__LINE__);
		if(preg_match("#is marked as crashed#",$ligne["Comment"])){$failed=true;}
		if(preg_match("#Incorrect file format#",$ligne["Comment"])){$failed=true;}
		
		if($failed){
			$this->writeLogs("REPAIR_TABLE:$table failed -> Try other thing... ",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$sock=new sockets();
			$sqlerror[]="Lauching system repair for table $table in $database mysql-myd-file=yes";
			if(function_exists("system_admin_events")){system_admin_events("Lauching system repair for table $table in $database", __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$results=trim($sock->getFrameWork("cmd.php?mysql-myd-file=yes&table=$table&database=$database"));
			if(function_exists("system_admin_events")){system_admin_events("repair for $database.$table\nResult:=".$results, __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE FAILED, checking MYD: \"$results\"";
			$this->writeLogs("REPAIR_TABLE FAILED, checking MYD: \"$results\"",__CLASS__.'/'.__FUNCTION__,__LINE__);
			if($results=="NO"){
				$GLOBALS["REPAIR_MYSQL_TABLE"][]="REPAIR_TABLE: Failed -> REPAIR_TABLE_FAILED($database,$table)";
				$this->REPAIR_TABLE_FAILED($database,$table);			
			}
		}
		$sqlerror[]="Should success...in line ". __LINE__." ,class:".__CLASS__;
		$sqlerror[]="Optimize table...";
		$sql="OPTIMIZE TABLE $table";
		$this->QUERY_SQL($sql,$database);
		if(!$this->ok){$sqlerror[]=$this->mysql_error;}else{$sqlerror[]="Should OK...";}
		$this->mysql_error=@implode("<br>\n", $sqlerror);
		$GLOBALS["REPAIR_MYSQL_TABLE"]=$sqlerror;
		$sock->getFrameWork("cmd.php?mysql-check=yes&database=$database&table=$table&instance-id=0");
	}
	
	private function REPAIR_TABLE_FAILED($database,$table){
		
		

		$this->BuildTables();
			if(function_exists("send_email_events")){
				$subject="Table $table in database $database definitively crashed (repaired)";
				$text[]="The Mysql table $table was crashed and the file $table.MYD was not stored on the disk";
				$text[]="In this case, datas was not recoverable.";
				$text[]="Artica has deleted the table and rebuild it.";
				$text[]="But no datas can be retrieved.";
				$text[]="If you want to recover datas, use your backup.";
				send_email_events($subject,@implode("\n",$text),"system");
			}
			if(function_exists("system_admin_events")){
				system_admin_events($subject."\n". @implode("\n",$text)."\n".@implode("\n", $GLOBALS["REPAIR_MYSQL_TABLE"]), __FUNCTION__, __FILE__, __LINE__, "mysql-error");
			}
			
		}
	
	
	function DELETE_USER_INMYSQL($user){
		$this->THIS_BD_CONNECT();
		if($user==$this->mysql_admin){return;}
		if(!$this->mysql_connection){return;}
	    $results=@mysql_query("DROP USER '$user'@'localhost';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
		$results=@mysql_query("DROP USER '$user'@'127.0.0.1';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}

		$results=@mysql_query("DROP USER '$user'@'%';",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$errnum=@mysql_errno();
				$des=@mysql_error($this->mysql_connection);
				$this->mysql_error="DROP USER '$user'@'localhost' -> Error Number ($errnum) ($des)";
				writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return ;
			}		
	}


	
	function TABLE_EXISTS($table){
		
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		
		if(!$this->DATABASE_EXISTS($this->database)){
			$this->writeLogs("Database $this->database does not exists...create it",__CLASS__.'/'.__FUNCTION__,__FILE__);
			if(!$this->CREATE_DATABASE($this->database)){
				$this->writeLogs("Unable to create $this->database database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				return false;
			}
		}
		
		$sql="SHOW TABLES";
		$results=$this->QUERY_SQL($sql);
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($table)==strtolower($ligne["Tables_in_$this->database"])){return true;}
		}
		
		return false;
		
	}
	
	
	function DATABASE_EXISTS($database,$nocache=false){
		if(!$nocache){
			if(isset($_SESSION["MYSQL_DATABASE_EXISTS"][$database])){
			if($_SESSION["MYSQL_DATABASE_EXISTS"][$database]==true){return true;}
			}
		}
		$sql="SHOW DATABASES";
		$this->THIS_BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(strtolower($database)==strtolower($ligne["Database"])){
				$_SESSION["MYSQL_DATABASE_EXISTS"][$database]=true;
				return true;
			}
		}
		
		return false;
		
	}
	
	
	function PRIVILEGES_DELETE($database){
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed  \"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}	
		return true;
	}
	
	
	function PRIVILEGES($user,$password,$database){
		$server_mysql_password=$this->mysql_password;
		$OrginalPassword=$this->mysql_password;
		$server_database=$database;
		$mysql_password=$password;
		$sql="SELECT User FROM user WHERE User='$user'";
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,'mysql'));	
		$userfound=$ligne["User"];
		$sql="DELETE FROM `mysql`.`db` WHERE `db`.`Db` = '$server_database'";
		$this->QUERY_SQL($sql,"mysql");
		if(!$this->ok){
			writelogs("Failed to delete privileges FROM $server_database\"$this->mysql_error\"",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;	
		}
	
		if($userfound==null){
			$sql="CREATE USER '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password';";
			$this->EXECUTE_SQL($sql);
			if(!$this->ok){
					$this->mysql_admin="root";
					$this->mysql_password=null;
					$this->EXECUTE_SQL($sql);
					$this->ok=true;
					if(!$this->ok){
						echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
						$this->mysql_admin="root";
						$this->mysql_password=$server_mysql_password;
						$this->ok=true;
						$this->EXECUTE_SQL($sql);
						if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
					}
					
			}
		}
	
		$sql="CREATE USER '$user'@'localhost' IDENTIFIED BY '$mysql_password';";
		$this->EXECUTE_SQL($sql);	
		if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){
					echo "CREATE USER $user Failed with root/root+Password\n `$this->mysql_error`\n";
					return false;
				}			
			}
			
		}
	
	$server_database2=str_replace("_","\_",$server_database);
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'$this->mysql_server' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT USAGE ON `$server_database`. *  TO '$user'@'localhost' IDENTIFIED BY '$mysql_password' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}	
	
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'$this->mysql_server' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	$sql="GRANT ALL PRIVILEGES ON `$server_database2` . * TO '$user'@'localhost' WITH GRANT OPTION ;";
	$this->EXECUTE_SQL($sql);
	if(!$this->ok){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->EXECUTE_SQL($sql);
			$this->ok=true;
			if(!$this->ok){
				echo "CREATE USER $user Failed with root\n `$this->mysql_error`\n";
				$this->mysql_admin="root";
				$this->mysql_password=$server_mysql_password;
				$this->ok=true;
				$this->EXECUTE_SQL($sql);
				if(!$this->ok){echo "GRANT USAGE ON $user Failed with root/root+Password\n `$this->mysql_error`\n";return false;}			
			}
			
	}
	
	writelogs("success set privileges on $server_database",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
	
	$sql="FLUSH PRIVILEGES";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->EXECUTE_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->EXECUTE_SQL($sql,"mysql")){
					echo "$sql\n\n$q->mysql_error\n";
					return;
				}
			}			
		}			
	
		
	$this->mysql_password=$OrginalPassword;	
	return $this->check_write_privs($user,$password,$database);		
		
}

	private function check_write_privs($user,$password,$database){
		$OrginalPassword=$this->mysql_password;
		$write["Insert_priv"]=true;
		$write["Update_priv"]=true;
		$write["Delete_priv"]=true;
		$write["Create_priv"]=true;
		$write["Drop_priv"]=true;
		$write["Index_priv"]=true;
		$write["Alter_priv"]=true;
		$write["Create_tmp_table_priv"]=true;
		$write["Create_view_priv"]=true;
		$write["Show_view_priv"]=true;			
		$USERS=array();
		$sql="SELECT `User` FROM `db` WHERE `Db`='$database'";
		$results=$this->QUERY_SQL($sql,"mysql");
		if(@mysql_num_rows($results)>0){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$USERS[$ligne["User"]]=true;
			}
			
		}
		
		if(isset($USERS[$user])){return true;}
		while (list ($num, $none) = each ($write) ){
			$fileds[]="`$num`";
			$dd[]="'Y'";
		}
	
	
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','127.0.0.1',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}
		$sql="INSERT INTO `db` (`Db`,`User`,`Host`,".@implode(",", $fileds).") VALUES('$database','$user','localhost',".@implode(",", $dd).")";
		$this->mysql_password=$OrginalPassword;
		if(!$this->QUERY_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=$OrginalPassword;
			$this->ok=true;
			if(!$this->QUERY_SQL($sql,"mysql")){
				$this->mysql_admin="root";
				$this->mysql_password=null;
				$this->ok=true;	
				if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}

		}

	$sql="FLUSH PRIVILEGES";	
	$this->mysql_password=$OrginalPassword;
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	if(!$this->EXECUTE_SQL($sql,"mysql")){
		$this->mysql_admin="root";
		$this->mysql_password=$OrginalPassword;
		$this->ok=true;
		if(!$this->EXECUTE_SQL($sql,"mysql")){
			$this->mysql_admin="root";
			$this->mysql_password=null;
			$this->ok=true;	
			if(!$this->QUERY_SQL($sql,"mysql")){echo "$sql $this->mysql_error ";return false;}
			}			
		}

		return true;
	}

	public function SHOW_VARIABLES(){
		$sql="SHOW VARIABLES;";
		$this->THIS_BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Variable_name=$ligne["Variable_name"];
			$array[$Variable_name]=$ligne["Value"];
			}
			return $array;
		}
		
	function SET_VARIABLES($key,$value){
		$this->THIS_BD_CONNECT();
		$results=@mysql_query("SET GLOBAL $key = $value",$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		}	

		
	function DATABASE_LIST_SIMPLE(){
		$sql="SHOW DATABASES";
		$this->THIS_BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=true;
			}
			return $array;
		}		

		
	function DATABASE_LIST(){
		$sql="SHOW DATABASES";
		$this->THIS_BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;
		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$Database=$ligne["Database"];
			$array[$Database]=$this->DATABASE_INFOS($Database);
			}
			return $array;
		}
		
	function FLUSH_PRIVILEGES(){
		$sql="FLUSH PRIVILEGES";
		$this->THIS_BD_CONNECT();
		$results=@mysql_query($sql,$this->mysql_connection);
		$errnum=@mysql_error($this->mysql_connection);
    	$des=@mysql_error($this->mysql_connection);
    	$this->mysql_error=$des;

		}		
		
		
	function TABLES_LIST(){
		$sql="SHOW TABLE STATUS";
		$results=$this->QUERY_SQL($sql);
		$array=array();
		if(!$this->ok){return array();}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize= $ligne['Data_length'] + $ligne['Index_length']; 
			$dbsize_text=ParseBytes($dbsize);
			$Name=$ligne["Name"];
			$Rows=$ligne["Rows"];
			$Max_data_length=$ligne["Max_data_length"];
			$array[$Name]=array($dbsize,$dbsize_text,$Rows,$Max_data_length);
			
		}
		
		return $array;
		
	}
	
	
	private function DATABASE_INFOS($database){
		$sql="show TABLE STATUS";
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length']; 
			$count=$count+1;}
		return array($count,ParseBytes($dbsize));
	}
	
	
	FUNCTION CREATE_DATABASE($database,$nocache=false){
		
		if(!$this->THIS_BD_CONNECT()){
			$this->ToSyslog("CREATE_DATABASE Connection failed");
			writelogs("CREATE_DATABASE Connection failed",__FUNCTION__."/".__CLASS__,__FILE__,__LINE__);
			return false;
		}
	
  		$results=@mysql_query("CREATE DATABASE IF NOT EXISTS `$database`",$this->mysql_connection);
		if(@mysql_error($this->mysql_connection)){
			$time=date('h:i:s');
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connectiond);
			if(preg_match("#database exists#", $des)){$this->ok=true;return true;}
			
			$this->mysql_error="CREATE DATABASE $database -> Error Number ($errnum) ($des)";
			$this->ToSyslog($this->mysql_error);
			writelogs("($errnum) $des $this->mysql_admin@$this->mysql_server",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			return false;
		}

		$this->ok=true;
		return true;
		
	}
	
	FUNCTION DELETE_DATABASE($database){
		if(!function_exists("mysql_connect")){$this->ok=false;return 0;}
		$this->ok=true;
		$this->THIS_BD_CONNECT();
		
		
		
		$results=@mysql_query("DROP DATABASE `$database`",$this->mysql_connection);	
		
		if(@mysql_error($this->mysql_connection)){
				$time=date('h:i:s');
				$this->ok=false;
				$errnum=@mysql_errno($this->mysql_connection);
				$des=@mysql_error($this->mysql_connection);
							
			if(function_exists("debug_backtrace")){
					try {
						$trace=@debug_backtrace();
						if(isset($trace[1])){$called="\ncalled by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
					} catch (Exception $e) {$this->writeLogs("QUERY_SQL:: Fatal: ".$e->getMessage(),__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				}
				
				$this->mysql_error="Error Number ($errnum) ($des)$called";
				return false;

		}
		$this->ok=true;
		return true;
		
	}
	
	FUNCTION DELETE_TABLE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL table $database/$table was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");}
		$this->QUERY_SQL("DROP TABLE `$table`",$database);
		$this->QUERY_SQL("FLUSH TABLES",$database);
		unset($GLOBALS["__MYSQL_TABLE_EXISTS"]);
		unset($_SESSION["__MYSQL_TABLE_EXISTS"]);
	}	
	
	
	FUNCTION COUNT_ROWS($table,$database=null){
		$table=str_replace("`", "", $table);
		$table=str_replace("'", "", $table);
		$table=str_replace("\"", "", $table);
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Rows"]==null){$ligne["Rows"]=0;}
		return $ligne["Rows"];
		}
		
	FUNCTION TABLE_SIZE($table,$database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="show TABLE STATUS WHERE Name='$table'";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		if($ligne["Data_length"]==null){$ligne["Data_length"]=0;}
		if($ligne["Index_length"]==null){$ligne["Index_length"]=0;}
		return $ligne["Index_length"]+$ligne["Data_length"];		
		
	}
	
	

		
	FUNCTION DATABASE_SIZE($database){
		if(!function_exists("mysql_connect")){return 0;}
		$sql="SHOW table STATUS";
		$dbsize=0;
		$results=$this->QUERY_SQL($sql,$database);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$dbsize += $ligne['Data_length'] + $ligne['Index_length'];
		}
		$dbsize=round(($dbsize/1024),2);
		if($dbsize>1000){
			$dbsize=round($dbsize/1000,1) . " Mb";
		}else{
			$dbsize=$dbsize . " kb";
		}
		return $dbsize;
		
	}
	
	
	
	FUNCTION INDEX_EXISTS($table,$index,$database){
		
		if(isset($_SESSION["MYSQL_INDEX_EXISTS"])){
			if($_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]==true){return true;}
		}
		$sql="SHOW INDEX FROM $table WHERE Key_name='$index'";
		
		
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		
		
		
		if($ligne["Key_name"]<>null){
			$_SESSION["MYSQL_INDEX_EXISTS"][$database][$table][$index]=true;
			return true;
		}else{return true;}
	}
	
	
	FUNCTION FIELD_EXISTS($table,$field,$database=null){
		if($database==null){$database=$this->database;}
		$field=trim($field);
		if(isset($GLOBALS["__FIELD_EXISTS"])){
				if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table])){
					if(isset($GLOBALS["__FIELD_EXISTS"][$database][$table][$field])){
						if($GLOBALS["__FIELD_EXISTS"][$database][$table][$field]==true){return true;}
					}
				}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		
		if(trim($ligne["Field"])<>null){
			$GLOBALS["__FIELD_EXISTS"][$database][$table][trim($field)]=true;
			return true;
		}else{
			$this->writelogs("\"$field\" does not exists in table $table  in $database",__FUNCTION__,__LINE__);
			$this->writelogs("$sql",__FUNCTION__,__LINE__);
			return false;
		}
		
		
	}
	

	
	
	function FIELD_TYPE($table,$field,$database){
		if(isset($GLOBALS["__FIELD_TYPE"])){
			if(isset($GLOBALS["__FIELD_TYPE"][$database][$table][$field])){
				if($GLOBALS["__FIELD_TYPE"][$database][$table][$field]<>null){return $GLOBALS["__FIELD_TYPE"][$database][$table][$field];}
			}
		}
		$sql="SHOW FULL FIELDS FROM $table WHERE Field='$field';";
		$ligne=@mysql_fetch_array($this->QUERY_SQL($sql,$database));
		$GLOBALS["__FIELD_TYPE"][$database][$table][$field]=strtolower($ligne["Type"]);
		return strtolower($ligne["Type"]);
	}
	
	
	FUNCTION CREATE_INDEX($table,$index,$database){
		$sql="ALTER TABLE `$table` ADD INDEX ( `$index` )";
		$this->QUERY_SQL($sql,$database);
	}
	
	
	
	public function QUERY_SQL($sql,$database=null,$called=null){
		if(!$this->SHOREWALL_INSTALLED){return false;}
		$database=trim($database);
		$CLASS=__CLASS__;
		$FUNCTION=__FUNCTION__;
		$FILENAME=basename(__FILE__);
		$LOGPRF="$FILENAME::$CLASS/$FUNCTION";
		$this->ok=false;
		
		if($called==null){if(function_exists("debug_backtrace")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}}
	
		if(!$this->THIS_BD_CONNECT(false,$called)){
			if($GLOBALS["VERBOSE"]){echo "Unable to BD_CONNECT class mysql/QUERY_SQL\n";}
			if(function_exists("system_admin_events")){$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}system_admin_events("MySQL error DB:\"$database\" Error, unable to connect to MySQL server, request failed\n$called" , __FUNCTION__, __FILE__, __LINE__, "mysql-error");}
			$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error, unable to connect to MySQL server, request failed",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->ok=false;
			$this->mysql_error=$this->mysql_error ." Error, unable to connect to MySQL server";
			$this->ToSyslog($this->mysql_error);
			return false;
		}
	
		if(preg_match("#DROP TABLE\s+(.+)$#i", $sql,$re)){
			$TableDropped=$re[1];
			if(function_exists("system_admin_events")){
				$trace=@debug_backtrace();if(isset($trace[1])){$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
				system_admin_events("MySQL table $database/$TableDropped was deleted $called" , __FUNCTION__, __FILE__, __LINE__, "mysql-delete");
			}
		}
	
	
		
		$ok=@mysql_select_db($this->database,$this->mysql_connection);
		if (!$ok){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_select_db [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			$this->mysql_errornum=$errnum;
			$this->mysql_error=$des;
			$time=date('h:i:s');
			$this->writeLogs("$LOGPRF Line:".__LINE__.": DB:\"$database\" Error Number ($errnum) ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin ($called)",__CLASS__.'/'.__FUNCTION__,__LINE__);
			$this->mysql_error="$LOGPRF Line:".__LINE__.": mysql_select_db:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
			$this->ok=false;
			$this->ToSyslog($this->mysql_error);
			@mysql_close($this->mysql_connection);
			return null;
		}
	
		$results=@mysql_query($sql,$this->mysql_connection);
		
		if(!$results){
			$errnum=@mysql_errno($this->mysql_connection);
			$des=@mysql_error($this->mysql_connection);
			
			if(preg_match('#Duplicate entry#',$des)){
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" Error $errnum $des line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writeLogs("QUERY_SQL:".__LINE__.": DB:\"$database\" ". substr($sql,0,255)."...line:".__LINE__,__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->writelogs($sql,__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->ok=true;
				@mysql_close($this->mysql_connection);
				return true;
			}
			$this->mysql_errornum=$errnum;
			$this->mysql_error="QUERY_SQL:".__LINE__.": mysql_query:: Error $errnum ($des) config:$this->mysql_server:$this->mysql_port@$this->mysql_admin line:".__LINE__;
			$this->ToSyslog($this->mysql_error);
			if($GLOBALS["VERBOSE"]){echo "$LOGPRF mysql_query [FAILED] N.$errnum DESC:$des mysql/QUERY_SQL\n";}
			@mysql_free_result($this->mysql_connection);
			@mysql_close($this->mysql_connection);
			$this->ok=false;
			return null;
	
		}
	
		$this->ok=true;
		$this->last_id=@mysql_insert_id($this->mysql_connection);
		$result_return=$results;
		@mysql_free_result($this->mysql_connection);
		@mysql_close($this->mysql_connection);
		$this->mysql_connection=false;
		return $result_return;
	
	
	}	
	private function OutputDebugVerbose($text){
		if(!$GLOBALS["VERBOSE"]){return;}
		if(function_exists("debug_backtrace")){
			$trace=@debug_backtrace();
			if(isset($trace[1])){
				$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}
		}
		$filename=basename($trace[1]["file"]);
		$function=$trace[1]["function"];
		$line=$trace[1]["line"];
		echo "$filename:: $function $text in line $line<br>\n";
	}
}
?>