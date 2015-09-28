<?php

include_once(dirname(__FILE__)."/class.mysql-meta.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");

class squid_meta{
	
	
	public function squid_meta(){
		
	}
	
	private function chown_object($path){
		@chown($path,"squid");
		@chgrp($path, "squid");
	}
	
	function ToSyslog($text,$error=false){
	
		if(!$error){$LOG_SEV=LOG_INFO;}else{$LOG_SEV=LOG_ERR;}
		if(function_exists("openlog")){openlog("monit", LOG_PID , LOG_SYSLOG);}
		if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
		if(function_exists("closelog")){closelog();}
	}	
	
	private function ACLS_WHITELIST(){
		$ipClass=new IP();
		$DOMS=array();
		$IPS=array();
		$FINAL_IPS=array();
		$FINAL_DOMS=array();
		$q=new mysql_meta();
		$results=$q->QUERY_SQL("SELECT * FROM squid_whitelists ORDER BY `pattern`");
		if(!$q->ok){$this->ToSyslog("MySQL ERROR: ".__LINE__.": ".$q->mysql_error);return;}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$pattern=$ligne["pattern"];
			if($ipClass->isIPAddressOrRange($pattern)){$IPS[$pattern]=$pattern; continue; }
			$DOMS[]=$pattern;
		}
		
		if(count($IPS)>0){ while (list ($ipaddr, $none) = each ($IPS) ){$FINAL_IPS[]=$ipaddr; } }
		
		$acls=new squid_acls();
		if(count($DOMS)>0){
			$FINAL_DOMS=$acls->clean_dstdomains($DOMS);
		}
		
		@mkdir("/etc/squid3/artica-meta",0755,true);
		$this->chown_object("/etc/squid3/artica-meta");
		@file_put_contents("/etc/squid3/artica-meta/whitelist-domains.db", @implode("\n", $FINAL_DOMS));
		@file_put_contents("/etc/squid3/artica-meta/whitelist-nets.db", @implode("\n", $FINAL_IPS));
		$this->chown_object("/etc/squid3/artica-meta/whitelist-domains.db");
		$this->chown_object("/etc/squid3/artica-meta/whitelist-nets.db");
		
		
	}
	
	public function build_acls(){
		$this->ACLS_WHITELIST();
		$f[]="# --------- Meta Server ACLs [".__CLASS__."/".__FUNCTION__."/".__LINE__."]";
		$f[]="acl ArticaMetaWhiteDoms dstdomain \"/etc/squid3/artica-meta/whitelist-domains.db\"";
		$f[]="acl ArticaMetaWhiteIPs dst \"/etc/squid3/artica-meta/whitelist-nets.db\"";
		$f[]="";
		return @implode("\n",$f);
	}
	
	
	public function http_access(){
		$f[]="# --------- Meta Server rules [".__CLASS__."/".__FUNCTION__."/".__LINE__."]";
		$f[]="http_access allow ArticaMetaWhiteDoms";
		$f[]="http_access allow ArticaMetaWhiteIPs";
		
		$f[]="";
		return @implode("\n",$f);
		
	}
	
	
	
}