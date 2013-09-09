<?php


class nginx_sources{
	public $servername;
	public $ipaddr;
	public $port;
	public $ssl;
	public $sslport;
	public $enabled;
	public $ForceRedirect=0;
	public $IsCas=0;
	public $ssl_commname=null;
	public $forceddomain=0;
	public $proxy_read_timeout=300;
	
	function nginx_source($cache_peer_id=0){
		if(!is_numeric($cache_peer_id)){return;}
		if($cache_peer_id>0){$this->load($cache_peer_id);}
		
	}
	
	
	private function load($cache_peer_id){
		
		if(!isset($GLOBALS["nginx_sources"][$cache_peer_id])){
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM reverse_sources WHERE ID='$cache_peer_id'"));
			$GLOBALS["nginx_sources"][$cache_peer_id]=$ligne;
		}else{
			$ligne=$GLOBALS["nginx_sources"][$cache_peer_id];
		}
			
		$this->servername=$ligne["servername"];
		$this->ipaddr=$ligne["ipaddr"];
		$this->port=$ligne["port"];
		$this->ssl=$ligne["ssl"];
		$this->sslport=$ligne["sslport"];
		$this->enabled=$ligne["enabled"];
		$this->ForceRedirect=$ligne["ForceRedirect"];
		$this->IsCas=$ligne["IsCas"];
		$this->ssl_commname=$ligne["ssl_commname"];
		$this->forceddomain=$ligne["forceddomain"];
		$this->proxy_read_timeout=$ligne["proxy_read_timeout"];

	}
	
	public function PatchTables(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return;}
		$q=new mysql_squid_builder();
		$sql="CREATE TABLE IF NOT EXISTS `reverse_sources` (
			  `ID` INT NOT NULL AUTO_INCREMENT,
			  `servername` CHAR(255)  NOT NULL,
			  `ipaddr` CHAR(60)  NOT NULL,
			  `port` smallint(3)  NOT NULL,
			  `ssl` smallint(1)  NOT NULL,
			  `sslport` smallint(3)  NOT NULL DEFAULT '443',
			  `enabled` smallint(1)  NOT NULL DEFAULT '1',
			  `ForceRedirect` smallint(1)  NOT NULL DEFAULT '1',
			  `IsCas` smallint(1)  NOT NULL DEFAULT '0',
			  `ssl_commname` CHAR(128) NULL,
			  `forceddomain` CHAR(255) NULL,
			  PRIMARY KEY (`ID`),
			  KEY `ipaddr` (`ipaddr`),
			  KEY `port` (`port`),
			  KEY `ssl` (`ssl`),
			  KEY `IsCas` (`IsCas`),
			  KEY `sslport` (`sslport`),
			  KEY `enabled` (`enabled`)
			)  ENGINE = MYISAM;";
			$q->QUERY_SQL($sql,$this->database);
			
			$GLOBALS[__CLASS__.__FUNCTION__]=true;
			if(!$q->ok){return;}
			$this->PatchFields();
	}
	
	private function PatchFields(){
		if(isset($GLOBALS[__CLASS__.__FUNCTION__])){return;}
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("reverse_sources", "sslport")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL DEFAULT '443', ADD INDEX ( `sslport`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error\nALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL, ADD INDEX ( `sslport` )</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "sslport")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL DEFAULT '443', ADD INDEX ( `sslport`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error\nALTER TABLE `reverse_sources` ADD `sslport` smallint(3) NOT NULL, ADD INDEX ( `sslport` )</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "ForceRedirect")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ForceRedirect` smallint(1) NOT NULL DEFAULT '1' , ADD INDEX ( `ForceRedirect`)");if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}}
		if(!$q->FIELD_EXISTS("reverse_sources", "ssl_commname")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `ssl_commname` CHAR(128) NULL");}
		if(!$q->FIELD_EXISTS("reverse_sources", "IsCas")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `IsCas` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `IsCas`)");}	
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccess")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccess` smallint(1) NOT NULL DEFAULT '1', ADD INDEX ( `isSuccess`)");}
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccesstxt")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccesstxt` TEXT");}
		if(!$q->FIELD_EXISTS("reverse_sources", "isSuccessTime")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `isSuccessTime` datetime");}
		if(!$q->FIELD_EXISTS("reverse_sources", "proxy_read_timeout")){$q->QUERY_SQL("ALTER TABLE `reverse_sources` ADD `proxy_read_timeout` SMALLINT(5) NOT NULL DEFAULT '300'");}
		
		$GLOBALS[__CLASS__.__FUNCTION__]=true;
	}
	
	private function IsSubstitutions(){
		if(isset($GLOBALS["IsSubstitutions"])){return $GLOBALS["IsSubstitutions"];}
		$GLOBALS["IsSubstitutions"]=false;
		$ARRAY=$this->LoadParams();
		while (list ($a, $b) = each ($ARRAY["MODULES"]) ){
			if(preg_match("#http_substitutions_filter#", $a)){
				$GLOBALS["IsSubstitutions"]=true;
				break;
			}
	
		}
		return $GLOBALS["IsSubstitutions"];
	}

	private function LoadParams(){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Loading\n";}
		$unix=new unix();
		return $unix->NGINX_COMPILE_PARAMS();
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__.".".__LINE__.":Done...\n";}
	}
	
}