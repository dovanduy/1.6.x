<?php
include_once(dirname(__FILE__)."/class.mysql.blackboxes.inc");
include_once(dirname(__FILE__)."/class.ccurl.inc");
include_once(dirname(__FILE__)."/class.blackboxes.inc");
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.squid.inc");
include_once(dirname(__FILE__)."/class.system.network.inc");
class squidnodes{
	var $nodeid=0;
	var $second_listen_port=0;
	var $listen_port=3128;
	var $visible_hostname=null;
	var $enable_ftp_restrictions=0;
	var $EnableUserAgentBanAll=0;
	var $ArticaSquidParameters=null;
	var $hasProxyTransparent=0;
	var $cpu_number=1;
	var $SQUID_VERSION=0;
	var $EnableChangeRequestSize=0;
	var $IS_30=false;
	var $IS_31=false;
	var $IS_32=false;	
	var $intvalVersion=0;
	function squidnodes($nodeid){
		$GLOBALS["CLASS_SQUIDBEE"]=new squidbee();
		if(is_numeric($nodeid)){
			$this->nodeid=$nodeid;
			$this->DefaultValues();
		}
	}
	
	private function visible_hostname(){
		$visible_hostname=$this->GET("visible_hostname");
		
		if($visible_hostname==null){
			$blackbox=new blackboxes($this->nodeid);
			$visible_hostname=$blackbox->hostname;
		}
		$this->visible_hostname=$visible_hostname;
		if($visible_hostname==null){return null;}
		return "visible_hostname $visible_hostname";
		
	}
	
	public function GET($key){
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `value` FROM squidconf WHERE nodeid=$this->nodeid AND `key`='$key'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		return base64_decode($ligne["value"]);
	}
	
	private function CheckVersion(){
		
		if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
			$this->intvalVersion=intval($re[1]).intval($re[2])."0";
		if($re[1]==3){
			
			
			if($re[2]==0){
				$this->IS_30=true;
				$this->IS_31=false;
				$this->IS_32=false;
				return;
			}
			
			
			if($re[2]==1){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=false;			
				return;
			}
			if($re[2]>=2){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=true;			
				return;
			}
		}
		
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
			$this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
		}		
		
	}
			
		
	}
	
	public function GET_INFO($key){return $this->GET($key);}	
	public function SET_INFO($key,$value){$this->SET($key,$value);}
	
	public function SET($key,$value){
		writelogs("Saving $key =" . strlen($value)." bytes",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `key` FROM squidconf WHERE nodeid=$this->nodeid AND `key`='$key'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["key"]==null){
			$sql="INSERT INTO squidconf (`key`,`value`,`nodeid`) VALUES ('$key','".base64_encode($value)."',$this->nodeid)";
		}else{
			$sql="UPDATE squidconf SET `value`='".base64_encode($value)."' WHERE nodeid=$this->nodeid AND `key`='$key'";
		}
		if(!$q->TABLE_EXISTS("squidconf")){$q->CheckTables();}
		writelogs($sql,__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	public function DumpDatabases(){
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `key`,`value` FROM squidconf WHERE nodeid=$this->nodeid";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$array[$ligne["key"]]=$ligne["value"];
		}
		return $array;
	}
	private function SQUID_VERSION(){
		if(!isset($GLOBALS["CLASS_MYSQLBLK"])){$GLOBALS["CLASS_MYSQLBLK"]=new mysql_blackbox();$q=$GLOBALS["CLASS_MYSQLBLK"];}else{$q=$GLOBALS["CLASS_MYSQLBLK"];}
		$sql="SELECT `master_version` FROM nodesstatus WHERE nodeid=$this->nodeid AND `service_name`='APP_SQUID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){writelogs("ERROR::$sql $q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		if($ligne["master_version"]==0){
			writelogs("ERROR::$sql ->{$ligne["master_version"]}",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		return $ligne["master_version"];
	}
	

	
	
	private function DefaultValues(){
		$sock=new sockets();
		$squid=$GLOBALS["CLASS_SQUIDBEE"];
		$ini=new Bs_IniHandler();
		$this->second_listen_port=$this->GET("second_listen_port");
		$this->listen_port=$this->GET("listen_port");
		$this->hasProxyTransparent=$this->GET("hasProxyTransparent");
		if(!is_numeric($this->hasProxyTransparent)){$this->hasProxyTransparent=0;}
		$blackbox=new blackboxes($this->nodeid);
		$this->cpu_number=$blackbox->settings_inc["CPU_NUMBER"];
		$this->SQUID_VERSION=$this->SQUID_VERSION();
		$this->CheckVersion();
		$GLOBALS["CLASS_SQUIDBEE"]->IS_30=$this->IS_30;
		$GLOBALS["CLASS_SQUIDBEE"]->IS_31=$this->IS_31;
		$GLOBALS["CLASS_SQUIDBEE"]->IS_32=$this->IS_32;

		
		
		$this->EnableChangeRequestSize=$squid->EnableChangeRequestSize;
		if($this->ArticaSquidParameters==null){$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');}
		
			if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
				$ini->loadString($this->ArticaSquidParameters);
				if(isset($ini->_params)){
				$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
				}
			}else{
				$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
			}
		if(isset($ini->_params)){
			
			$this->LDAP_AUTH=$ini->_params["NETWORK"]["LDAP_AUTH"];
			$this->NTLM_AUTH=$ini->_params["NETWORK"]["NTLM_AUTH"];
			$this->LDAP_EXTERNAL_AUTH=$ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"];
			$this->EnableParentProxy=$ini->_params["NETWORK"]["EnableParentProxy"];
			$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
			$this->FTP_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidFTPParams")));
			$this->SSL_BUMP=$ini->_params["NETWORK"]["SSL_BUMP"];
			$this->enable_ftp_restrictions=$ini->_params["NETWORK"]["enable_ftp_restrictions"];
			$this->EnableUserAgentBanAll=$ini->_params["NETWORK"]["EnableUserAgentBanAll"];
			$this->EnableChangeRequestSize=$ini->_params["NETWORK"]["EnableChangeRequestSize"];
			$this->allow_squid_localhost=$ini->_params["NETWORK"]["allow_squid_localhost"];
			$this->EnableICPPort=$ini->_params["NETWORK"]["EnableICPPort"];
			$this->url_rewrite_bypass=$ini->_params["NETWORK"]["url_rewrite_bypass"];
			if(isset($ini->_params["NETWORK"]["ignore_expect_100"])){$this->ignore_expect_100=$ini->_params["NETWORK"]["ignore_expect_100"];}
		}
			$this->kavicapserverEnabled=$sock->GET_INFO('kavicapserverEnabled');
			if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
			if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
			if(!is_numeric($this->url_rewrite_bypass)){$this->url_rewrite_bypass=0;}
			if(!is_numeric($this->enable_ftp_restrictions)){$this->enable_ftp_restrictions=0;}
			if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
			if(!is_numeric($this->listen_port)){$this->listen_port=3128;}	
			$this->visible_hostname();	
		
	}
	
	public function SaveToLdap(){
		$nodes=new blackboxes($this->nodeid);
		$nodes->NotifySquid();
	}
	
	public function RebuildCaches(){
		$nodes=new blackboxes($this->nodeid);
		$nodes->NotifyNode("CACHES_REBUILD");		
		
	}
	
	private function http_port(){
			$transparent=null;
			if($this->hasProxyTransparent==1){
				$transparent=" intercept";
			}	
		
		
			if($this->listen_port>0){
				$conf[]="http_port $this->listen_port{$transparent}";
				$nextport=$this->listen_port+1;
			}
			if($this->second_listen_port>0){
				$conf[]="http_port $this->second_listen_port";
				$nextport=$this->second_listen_port+1;
				}
			
		$conf[]="http_port $nextport";
		return @implode("\n", $conf);
	}
	
	
	function build(){
			writelogs("Starting build configuration file for node $this->nodeid",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$squid=$GLOBALS["CLASS_SQUIDBEE"];
			$GLOBALS["CLASS_SQUIDBEE"]->WHITELISTED_AUTH();
			$GLOBALS["CLASS_SQUIDBEE"]->ACL_MESSENGERS();
			
			$users=new usersMenus();
			$GLOBALS["aclGen"]=new squid_acls();
			$cache_mem=$this->GET("cache_mem");
			if(!is_numeric($cache_mem)){$cache_mem=128;}
			
			$conf[]="# Artica Postfix generated by $users->hostname on ". date("Y-m-d H:i:s");
			$conf[]="acl localhost src 127.0.0.1/32";
			$conf[]="acl to_localhost dst 127.0.0.0/8 0.0.0.0/32";
			$conf[]="#--------- TWEEKS PERFORMANCES";
			$conf[]="# http://blog.last.fm/2007/08/30/squid-optimization-guide";
			$conf[]="memory_pools off";
			$conf[]="quick_abort_min 0 KB";
			$conf[]="quick_abort_max 0 KB";
			$conf[]="log_icp_queries off";
			$conf[]="client_db off";
			$conf[]="buffered_logs on";
			$conf[]="half_closed_clients off";
			$conf[]="#--------- WebFiltering";
			$conf[]=$this->ufdbguard();
			$conf[]=$this->ICAP_31();
			$conf[]=$this->ICAP_CHAINS();
			$conf[]="#--------- SQUID PARENTS (feature not enabled)";
			$conf[]="#--------- acls";
			$conf[]="acl blockedsites url_regex \"/etc/squid3/squid-block.acl\"";
			$conf[]="acl CONNECT method CONNECT";
			$conf[]="acl purge method PURGE";
			$conf[]="acl FTP proto FTP";
			$conf[]=$this->SQUID_LOCAL_NETWORKS();
			$conf[]="";
			$conf[]="#--------- MAIN RULES...";
			$conf[]="always_direct allow FTP";
			$conf[]="# --------- SAFE ports";
			$conf[]=$this->SAFE_PORTS();
			$conf[]="# --------- Use x-forwarded-for for local Dansguardian or load balancers";
			$conf[]="log_uses_indirect_client	on";
			$conf[]="follow_x_forwarded_for allow localhost";
			$conf[]=$this->SSL_PORTS();
			$conf[]="";
			$conf[]="";
			$conf[]="# ---------  RULES DEFINITIONS";
			$conf[]=$this->http_access();
			$conf[]="# --------- ICAP Services.(0 service(s))";
			$conf[]="hierarchy_stoplist cgi-bin ?";
			$conf[]="# --------- General settings ";
			$conf[]=$this->visible_hostname();
			$conf[]=$this->timeout();
			$conf[]="";
			$conf[]="#http/https ports";
			$conf[]=$this->http_port();
			$conf[]="# --------- Caches ";
			$conf[]="cache_effective_user squid";
			$conf[]="#cache_replacement_policy heap LFUDA";
			$conf[]="cache_mem $cache_mem MB";
			$conf[]="cache_swap_high 90";
			$conf[]="cache_swap_low 95";
			$conf[]=$this->dns_nameserver();
			$conf[]="#--------- FTP specific parameters";
			$conf[]=$GLOBALS["CLASS_SQUIDBEE"]->ftp_parameters();
			$conf[]="debug_options ALL,1";
			$conf=$conf ."# --------- Caches \n\n";
			$conf[]=$this->Workers();
			$cache_deny_array=$GLOBALS["CLASS_SQUIDBEE"]->cache_deny_array();
			$refresh_pattern_list=$GLOBALS["CLASS_SQUIDBEE"]->refresh_pattern_list();
			if(count($cache_deny_array)>0){
				$conf[]="# --------- Do not cache these ". count($cache_deny_array)." website(s)\n";
				$conf[]=@implode("\n", $cache_deny_array)."\n";
				$conf[]="cache deny DoNotCache\n"; 
			}			
			if($refresh_pattern_list<>null){$conf[]=$refresh_pattern_list;}
			$conf[]="";
			$conf[]="#--------- LOGS --";
			$conf[]="coredump_dir	/var/squid/cache";
			$conf[]="cache_log	stdio:/var/log/squid/cache.log";
			$conf[]="pid_filename	/var/run/squid.pid";
			$conf[]="error_directory /usr/share/squid-langpack/en";
			$conf[]="logformat common MAC:%>eui %>a %[ui %[un [%tl] \"%rm %ru HTTP/%rv\" %>Hs %<st %Ss:%Sh UserAgent:\"%{User-Agent}>h\"";
			$conf[]="cache_store_log	stdio:/var/log/squid/store.log";
			$conf[]="access_log syslog:authpriv.info common";
			
			
			writelogs("finishing build configuration file for node $this->nodeid",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return @implode("\n", $conf);
		
	}
	
	private function ufdbguard(){
		$EnableUfdbGuard=$this->GET("EnableUfdbGuard");
		if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
		if($EnableUfdbGuard==0){
			writelogs("Ufdbguard is not enabled...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			return null;
		}
		if(isset($GLOBALS["squimem_url_rewrite_children"])){return $GLOBALS["squimem_url_rewrite_children"];}
		$sock=new sockets();
		$RedirectorsArray=unserialize($this->GET("SquidRedirectorsOptions"));
		if(!is_array($RedirectorsArray)){
			writelogs("Ufdbguard no config detected...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		}
		
		if(!isset($RedirectorsArray["url_rewrite_children"])){
			writelogs("RedirectorsArray[\"url_rewrite_children\"] Assume 20...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
			$RedirectorsArray["url_rewrite_children"]=20;
		}
		if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}	
		
		writelogs("RedirectorsArray: startup:{$RedirectorsArray["url_rewrite_startup"]} url_rewrite_concurrency:{$RedirectorsArray["url_rewrite_concurrency"]}  ",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		$listen_addr=trim($ufdbguardConfig["listen_addr"]);
		if(!is_numeric($ufdbguardConfig["listen_port"])){$ufdbguardConfig["listen_port"]="3977";}
		$listen_port=$ufdbguardConfig["listen_port"];
		
		if($listen_addr==null){
			$sys=new networking();
			$ips=$sys->ALL_IPS_GET_ARRAY();
			while (list ($num, $val) = each ($ips) ){
				writelogs("Listen IP detected: \"$num\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if($num=="127.0.0.1"){continue;}
				$listen_addr=$num;
				break;
			}
		}
			
		writelogs("Listen IP: \"$listen_addr\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		
		
		if($this->$EnableUfdbGuard==1){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		$url_rewrite_children[]="url_rewrite_program /usr/bin/ufdbgclient -S $listen_addr -p $listen_port  -l /var/log/squid";
		$url_rewrite_children[]="url_rewrite_children {$RedirectorsArray["url_rewrite_children"]} startup={$RedirectorsArray["url_rewrite_startup"]} idle={$RedirectorsArray["url_rewrite_idle"]} concurrency={$RedirectorsArray["url_rewrite_concurrency"]}";
		$GLOBALS["squimem_url_rewrite_children"]=@implode("\n", $url_rewrite_children);		
		return $GLOBALS["squimem_url_rewrite_children"];
		}	
	
	
	private function Workers(){
		$CPUS=$this->cpu_number;
		
		if(!is_numeric($CPUS)){$CPUS=1;}
		if($CPUS==0){$CPUS=1;}
		$cachesDirectory=$this->GET("caches_directory");
		$globalCachesize=$this->GET("caches_size_global");
		if($cachesDirectory==null){$cachesDirectory="/var/cache/squid";}
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($CPUS==1){
			return "cache_dir	ufs $cachesDirectory-0 $globalCachesize 16 256";
			
		}
		
		$f[]="#--------- Multiple cpus --";
		$f[]="workers $CPUS";
		for($i=1;$i<$CPUS+1;$i++){
			$f[]="if \${process_number} = $i";
			$f[]="cache_dir aufs $cachesDirectory-$i $globalCachesize 128 512";
			$f[]="endif";
		}
		$f[]="#------------------";
		
		return @implode("\n", $f);		
		
		
	}
	

	
	
	private function timeout(){
		$squid=$GLOBALS["CLASS_SQUIDBEE"];
		$conf[]="# --------- time-out";
		$conf[]="dead_peer_timeout {$squid->global_conf_array["dead_peer_timeout"]}";
		$conf[]="dns_timeout {$squid->global_conf_array["dns_timeout"]}";
		$conf[]="connect_timeout {$squid->global_conf_array["connect_timeout"]}";
		$conf[]="persistent_request_timeout 3 minutes";
		$conf[]="pconn_timeout 1600 seconds"; 			
		return @implode("\n", $conf);
		
	}
	
	private function ObjectLimits(){
		$squid=$GLOBALS["CLASS_SQUIDBEE"];
			if(preg_match("#([0-9+])#",$squid->global_conf_array["request_header_max_size"],$re)){
				$request_header_max_size=$re[1];
				if(!is_numeric($request_header_max_size)){$request_header_max_size=0;}
				if($request_header_max_size>0){
					if($request_header_max_size<256){$request_header_max_size=256;}
					
				}
		}
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["client_request_buffer_max_size"],$re)){
				$client_request_buffer_max_size=$re[1];
				if(!is_numeric($client_request_buffer_max_size)){$client_request_buffer_max_size=512;}
				if($client_request_buffer_max_size>0){
					if($client_request_buffer_max_size<512){$client_request_buffer_max_size=512;}
				}
		}	

		if(preg_match("#([0-9+])#",$squid->global_conf_array["reply_body_max_size"],$re)){
				$reply_body_max_size=$re[1];
				if($request_header_max_size==0){$reply_body_max_size=0;}
				if(!is_numeric($reply_body_max_size)){$reply_body_max_size=0;}
				if($reply_body_max_size<$request_header_max_size){$reply_body_max_size=$reply_body_max_size*2;}
			}
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["request_body_max_size"],$re)){
			$request_body_max_size=$re[1];
			if(!is_numeric($request_body_max_size)){$request_body_max_size=0;}			
		}
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["reply_header_max_size"],$re)){
			$reply_header_max_size=$re[1];
			if(!is_numeric($reply_header_max_size)){$reply_header_max_size=64;}			
		}
		
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){
			$maximum_object_size_in_memory=$re[1];
			if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size_in_memory"],$re)){$maximum_object_size_in_memory_unit=$re[1];}
			if($maximum_object_size_in_memory_unit==null){$maximum_object_size_in_memory_unit="KB";}
			if($maximum_object_size_in_memory_unit=="KB"){
				if($maximum_object_size_in_memory<512){$maximum_object_size_in_memory=1024;}
			}
		}
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["minimum_object_size"],$re)){
			$minimum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$squid->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
			if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
			if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		}		
		
		
		
		if(preg_match("#([0-9+])#",$squid->global_conf_array["maximum_object_size"],$re)){
			$maximum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$squid->global_conf_array["maximum_object_size"],$re)){$maximum_object_size_unit=$re[1];}
			if($maximum_object_size_unit==null){$maximum_object_size_unit="KB";}
			if($maximum_object_size_unit=="KB"){
				if($maximum_object_size<4096){$maximum_object_size=4096;}
			}
				if($maximum_object_size_unit=="MB"){
					if($maximum_object_size<4){$maximum_object_size=4;}
				}
		}				
		
		
		
		$conf[]="# --------- Objects limits";
		if($this->EnableChangeRequestSize==1){
			$conf[]="request_body_max_size $request_body_max_size KB";
			$conf[]="reply_body_max_size $reply_body_max_size KB";
			$conf[]="request_header_max_size $request_header_max_size KB";
			$conf[]="reply_header_max_size $reply_header_max_size KB";
			$conf[]="client_request_buffer_max_size $client_request_buffer_max_size KB";
		}
		
		$conf[]="maximum_object_size {$squid->global_conf_array["maximum_object_size"]}";
		$conf[]="minimum_object_size $minimum_object_size $minimum_object_size_unit";
		$conf[]="maximum_object_size_in_memory $maximum_object_size_in_memory $maximum_object_size_in_memory_unit";
	
	}
	
	
	private function dns_nameserver(){
		$sock=new sockets();
		$squid=$GLOBALS["CLASS_SQUIDBEE"];
		$conf=array();
		$conf[]="# --------- DNS and ip caches ";
		$conf[]="ipcache_size {$squid->global_conf_array["ipcache_size"]}";
		$conf[]="ipcache_low {$squid->global_conf_array["ipcache_low"]}";		
		$conf[]="ipcache_high {$squid->global_conf_array["ipcache_high"]}";				
		$conf[]="fqdncache_size {$squid->global_conf_array["fqdncache_size"]}";
		$conf[]="positive_dns_ttl {$squid->global_conf_array["positive_dns_ttl"]}";
		$conf[]="negative_dns_ttl {$squid->global_conf_array["negative_dns_ttl"]}";	
		
		
		$EnableOpenDNSInProxy=$sock->GET_INFO("EnableOpenDNSInProxy");
		if(!is_numeric($EnableOpenDNSInProxy)){$EnableOpenDNSInProxy=0;}
		if($EnableOpenDNSInProxy==1){
			$conf[]="\n# --------- OpenDNS DNS SERVERS \n";
			$arrayOpenDNS=unserialize(base64_decode($sock->GET_INFO("OpenDNSConfig")));
			if($arrayOpenDNS["OpenDNS1"]==null){$arrayOpenDNS["OpenDNS1"]="208.67.222.222";}
			if($arrayOpenDNS["OpenDNS2"]==null){$arrayOpenDNS["OpenDNS2"]="208.67.220.220";}
			writelogs("OpenDNS: {$arrayOpenDNS["OpenDNS2"]},{$arrayOpenDNS["OpenDNS2"]} ",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$conf[]="dns_nameservers " . $arrayOpenDNS["OpenDNS1"];
			$conf[]="dns_nameservers " . $arrayOpenDNS["OpenDNS2"];
			return @implode("\n", $conf);
		}
			
		if(!is_array($squid->dns_array)){return null;}
		if(count($squid->dns_array)==0){return null;}
		reset($squid->dns_array);
		
		$conf[]="\n# --------- SPECIFIC DNS SERVERS";
		while (list ($num, $val) = each ($squid->dns_array) ){
			if(preg_match("#resolv#", $val)){
				$dns_nameservers_from_resolv=$squid->dns_nameservers_from_resolv();
				if($dns_nameservers_from_resolv<>null){$conf[]=$dns_nameservers_from_resolv;}
				continue;
			}
			$conf[]="dns_nameservers $val";
					
		}
			
		if(count($conf)==0){return null;}
		return @implode("\n", $conf);		
	}
	
		function SAFE_PORTS(){
			$sock=new sockets();
			$squid=$GLOBALS["CLASS_SQUIDBEE"];
			$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsList")));
			if(!is_array($ports)){$add=true;}
			if(count($ports)<2){$add=true;}
			if($add){
				$ports["80"]="http";
				$ports["22"]="ssh";
				$ports["443 563"]="https, snews";
				$ports["1863"]="msn";
				$ports["70"]="gopher";
				$ports["210"]="wais";
				$ports["1025-65535"]="unregistered ports";
				$ports["280"]="http-mgmt";
				$ports["488"]="gss-http";
				$ports["591"]="filemaker";
				$ports["777"]="multiling http";
				$ports["631"]="cups";
				$ports["873"]="rsync";
				$ports["901"]="SWAT";		
			}		
			
			
			if($squid->enable_ftp_restrictions<>1){
				$ports["20"]="ftp-data";
				$ports["21"]="ftp";
			}
		
			while (list ($num, $val) = each ($ports) ){
				$conf[]="acl Safe_ports port $num\t#$val";		
			}
			
			return implode("\n",$conf);
			
		}


		private function SSL_PORTS(){
			$sock=new sockets();
			$ports=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
			if(!is_array($ports)){
				$ports["9000"]="Artica";
				$ports["443"]="HTTPS";
				$ports["563"]="https, snews";
				$ports["6667"]="tchat";
			}
			
			if(count($ports)==0){
				$ports["9000"]="Artica";
				$ports["443"]="HTTPS";
				$ports["563"]="https, snews";
				$ports["6667"]="tchat";	
			}
			
			while (list ($num, $val) = each ($ports) ){
				$conf[]="acl SSL_ports port $num\t#$val";		
			}
			$conf[]="";
			return implode("\n",$conf);
			
		}	
	
	
	private function SQUID_LOCAL_NETWORKS(){
			$sock=new sockets();
			$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
			if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}
			if($AllowAllNetworksInSquid==1){
				$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow office_network";
				$conf[]="acl office_network src all";
				return @implode("\n",$conf);
			}
			$NetworkScannerMasks=$sock->GET_INFO('NetworkScannerMasks');
			$tbl=explode("\n",$NetworkScannerMasks);	
			if(is_array($tbl)){
				while (list ($num, $cidr) = each ($tbl)){
					if(trim($cidr)==null){continue;}
					$k[$cidr]=$cidr;
				}
			}
	
		if(count($this->network_array)>0){
				while (list ($num, $val) = each ($this->network_array)){
					if($val==null){continue;}
					$k[$val]=$val;
				}
			}
			
		if(!is_array($k)){return null;}
		while (list ($m, $l) = each ($k)){$s[]=$l;}
		$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow office_network";
		$conf[]="acl office_network src " . implode(" ",$s);
		return @implode("\n",$conf);
	}

	private function http_access(){

			
			if(!isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])){$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]=array();}
			//if($this->allow_squid_localhost<>1){$conf[]="http_access deny to_localhost";}else{$conf[]="http_access allow to_localhost";}
			$conf[]="http_access allow to_localhost";
			$conf[]="url_rewrite_access deny localhost";
			if(count($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])>0){
				$conf[]=implode("\n",$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]);
			}
			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"];}
			
			
			
			
			if($GLOBALS["CLASS_SQUIDBEE"]->enable_ftp_restrictions){
				$conf[]="http_access allow FTP clients_ftp";
				$conf[]="http_access allow CONNECT clients_ftp";
			}			

			$conf[]="http_access deny !Safe_ports";
			$conf[]="http_access deny CONNECT !SSL_ports";
			$conf[]="http_access allow localhost";
			$conf[]="http_access allow purge localhost";
			$conf[]="http_access deny purge";			
			if($GLOBALS["CLASS_SQUIDBEE"]->EnableUserAgentBanAll==1){$conf[]="http_access deny !AllowedBrowsers";}		
			if(isset($GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"];}	
			if(isset($GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"];}		
			$acls_rules=$GLOBALS["aclGen"]->build_http_access();
			if(count($acls_rules)>0){$conf[]=@implode("\n", $acls_rules);}
			if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"];}
			$conf[]="http_access deny all";
			while (list ($num, $val) = each ($conf) ){if(trim($val)==null){continue;}$f[]=$val;}
			return "\n".@implode("\n",$f)."\n";
		}


	
	private function ICAP_31(){
		
		$EnableCicap=$this->GET("EnableCicap");
		$sock=new sockets();
		$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=0;}
		if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=0;}
		if(!is_numeric($EnableCicap)){$EnableCicap=0;}		
		
		if($EnableCicap==0){return null;}
		$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		$listen_addr=trim($ufdbguardConfig["listen_addr"]);
		
		if($listen_addr==null){
			$sys=new networking();
			$ips=$sys->ALL_IPS_GET_ARRAY();
			while (list ($num, $val) = each ($ips) ){
				writelogs("Listen IP detected: \"$num\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
				if($num=="127.0.0.1"){continue;}
				$listen_addr=$num;
				break;
			}
		}
			
		writelogs("Listen IP: \"$listen_addr\"",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);		
		
		
		$kav_rules[]="# --------- icap_service C-ICAP mode 3.1.x";	
		
		
		if($EnableClamavInCiCap==1){
			$modules_path=$this->ICAP_MODULES_CICAP_DIR();
			if(is_file("$modules_path/srv_clamav.so")){
				$kav_rules[]="icap_service  service_antivir respmod_precache routing=on bypass=on icap://$listen_addr:1345/srv_clamav";
				$kav_rules[]="adaptation_service_set  class_antivirus service_antivir";
				$kav_rules[]="adaptation_access       class_antivirus allow all";
			}
		}		
		writelogs("ICAP_31 done...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return implode("\n",$kav_rules);		
		}	
		
	private function ICAP_CHAINS(){
		$sock=new sockets();
		$EnableCicap=$this->GET("EnableCicap");
		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=0;}
		if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=0;}
		if(!is_numeric($EnableCicap)){$EnableCicap=0;}	

		if($EnableCicap==1){
			if($EnableClamavInCiCap==1){
				$modules_path=$this->ICAP_MODULES_CICAP_DIR();
				if(is_file("$modules_path/srv_clamav.so")){
					$a[]="service_antivir";
				}
			}
		}
			
		if(count($a)==0){writelogs("No icap services...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		
		
		$f[]="# --------- adaptation chain services.";
		$f[]="adaptation_service_chain ANTIVIRUS_CHAINS ".@implode(" ",$a);
		if(isset($GLOBALS["ICAP"]["WHITEAUTH"])){
			if($GLOBALS["ICAP"]["WHITEAUTH"]<>null){
				$f[]=$GLOBALS["ICAP"]["WHITEAUTH"];
			}
		}
			
		if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){
			$f[]="adaptation_access ANTIVIRUS_CHAINS deny StreamMimeWhite";
		}			
			
		$f[]="adaptation_access ANTIVIRUS_CHAINS allow all";
		writelogs("icap services done...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
		return @implode("\n",$f);	
	}		
	
	
	private function ICAP_MODULES_CICAP_DIR(){
		if(is_file("/usr/lib/c_icap/sys_logger.so")){return "/usr/lib/c_icap";}
		if(is_file("/var/lib/c_icap/sys_logger.so")){return "/var/lib/c_icap";}
	}		
	
	
}
