<?php
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/class.mysql.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__).'/class.samba.inc');	
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");
include_once(dirname(__FILE__)."/class.icap.inc");		
include_once(dirname(__FILE__)."/class.squid.bandwith.inc");
include_once(dirname(__FILE__)."/class.computers.inc");
include_once(dirname(__FILE__)."/class.squid.acls.inc");
include_once(dirname(__FILE__)."/class.ecap.inc");
include_once(dirname(__FILE__)."/class.system.network.inc");
include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");


class squid{
	

}
class squidbee{
	var $dn;
	var $network_array=array();
	var $dns_array=array();
	var $squid_conf;
	var $ArticaSquidParameters;
	var $ldap_error;
	var $listen_port;
	var $second_listen_port=0;
	var $visible_hostname;
	var $enable_kavproxy;
	var $enable_cicap;
	var $enable_dansguardian;
	var $enable_squidguard;
	var $enable_UfdbGuard;
	var $LDAP_AUTH=0;
	var $NTLM_AUTH=0;
	var $SSL_BUMP=0;
	var $EnableClamavInCiCap=1;
	var $CACHE_SIZE=2000;
	var $CACHE_PATH;
	var $CACHE_TYPE="ufs";
	var $alt_listen_port;
	var $kav_accept=false;
	var $kav_accept_why='';
	var $SquidBlockSites='';
	var $array_block_istes=array();
	var $global_conf_array=array();
	var $cache_list=array();
	var $acl_times=array();
	var $hasProxyTransparent;
	var $ASROOT=false;
	var $SQUID_BIN_VERSION=0;
	var $SQUID_ICAP_ENABLED=false;
	var $SQUID_VERSION="";
	var $KAV4PROXY_INSTALLED=false;
	var $EnableParentProxy=0;
	var $prefer_direct=0;
	var $nonhierarchical_direct=0;
	var $LDAP_EXTERNAL_AUTH=0;
	var $EXTERNAL_LDAP_AUTH_PARAMS=array();
	var $IS_27=false;
	var $IS_30=false;
	var $IS_31=false;
	var $IS_32=false;
	var $intvalVersion=false;
	var $ICAP_SERVICES_COUNT=0;
	var $FTP_PARAMS=array();
	var $wccp2_enabled=0;
	var $wccp2_router=null;
	var $wccp2_forwarding_method=1;
	var $wccp2_return_method=1;
	var $wccp2_assignment_method="hash";
	var $enable_ftp_restrictions=0;
	var $EnableUserAgentBanAll=0;
	var $enable_adzapper=0;
	var $enable_squidclamav=0;
	var $enable_metascanner=0;
	var $enable_streamcache=0;
	var $ACL_ARP_ENABLED=false;
	var $EnableChangeRequestSize=0;
	var $allow_squid_localhost=0;
	var $ignore_expect_100=0;
	var $EnableKerbAuth=0;
	var $EnableKavICAPRemote=0;
	var $KavICAPRemoteAddr=null;
	var $KavICAPRemotePort=0;
	var $SquidDisableAllFilters=0;
	var $url_rewrite_bypass=0;
	var $ICP_PORT=0;
	var $enable_ecapav=0;
	var $CPU_NUMBER;
	var $uuid;
	var $EnableICPPort=0;
	var $UseTProxyMode=0;
	var $store_dir_minsize=null;
	var $SquidBoosterOnly=0;
	var $ssl_port=0;
	var $second_listen_portForTransparent=0;
	var $EnableKerberosAuthentication=0;
	var $SSL_BUMP_WHITE_LIST=0;
	var $EnableSquidCSV=0;
	var $EnableRemoteStatisticsAppliance=0;
	
	function squidbee(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$this->VerifyLdapBranch();
		$this->ParseConfig();
		$this->kav_accept=$this->isicap();
		$sock=new sockets();
		$this->uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		$this->SQUID_BIN_VERSION=$users->SQUID_BIN_VERSION;
		if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
		$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
		$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		$this->SquidAcountUpdate();
		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if($EnableClamavInCiCap==null){$EnableClamavInCiCap=1;$sock->SET_INFO("EnableClamavInCiCap","1");}
		$this->enable_UfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
		$this->enable_adzapper=$sock->GET_INFO("EnableAdZapper");
		$this->enable_squidclamav=$sock->GET_INFO("EnableSquidClamav");
		$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		$this->EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
		$this->KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
		$this->KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		$this->enable_streamcache=$sock->GET_INFO("SquidEnableStreamCache");
		$this->SquidDisableAllFilters=$sock->GET_INFO("SquidDisableAllFilters");
		$this->enable_ecapav=$sock->GET_INFO("SquideCapAVEnabled");
		$this->UseTProxyMode=$sock->GET_INFO("UseTProxyMode");
		$this->EnableSquidCSV=$sock->GET_INFO("EnableSquidCSV");
		$this->EnableKerberosAuthentication=$sock->GET_INFO("EnableKerberosAuthentication");
		
		if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}
		if(!is_numeric($this->SquidDisableAllFilters)){$this->SquidDisableAllFilters=0;}
		if(!is_numeric($this->UseTProxyMode)){$this->UseTProxyMode=0;}
		if(!is_numeric($this->EnableSquidCSV)){$this->EnableSquidCSV=0;}
		if(!is_numeric($this->EnableKerberosAuthentication)){$this->EnableKerberosAuthentication=0;}
		if($this->EnableKerberosAuthentication==1){$this->EnableKerbAuth=0;}
		$users=new usersMenus();
		
		
		$this->CheckVersion();
		$this->hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
		if($this->hasProxyTransparent==null){$this->hasProxyTransparent=0;}
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}	
		if($this->EnableRemoteStatisticsAppliance==1){$this->enable_UfdbGuard=1;}
		
	}
	
	private function compilation_params(){
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}	
		exec("$squidbin -v 2>&1",$results);
		$text=@implode("\n", $results);
		if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
		if(preg_match_all("#'(.+?)'#is", $text, $re)){
		while (list ($index, $line) = each ($re[1])){
				if(preg_match("#(.+?)=(.+)#", $line,$ri)){
					$key=$ri[1];
					$value=$ri[2];
					$key=str_replace("--", "", $key);
					$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
					continue;
				}
				$key=$line;
				$value=1;
				$key=str_replace("--", "", $key);
				if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
				$GLOBALS["COMPILE_SQUID_TOKENS"][$key]=$value;
			}
			
		}
		return $GLOBALS["COMPILE_SQUID_TOKENS"];
	}

	
	private function WorkersDefault(){
		$users=new usersMenus();
		$sock=new sockets();
		$q=new mysql_squid_builder();
		$q->CheckTables();
		$hostname=trim($sock->GET_INFO("myhostname"));
		if($hostname==null){$hostname=$users->fqdn;}		
		$CPU=$this->CPU_NUMBER;
		$cachesDirectory="/var/cache/squid2";
		$globalCachesize=5000;
		$sql="INSERT IGNORE INTO cacheconfig (`uuid`,`workers`,`globalCachesize`,`cachesDirectory`,`hostname`) 
		VALUES('$this->uuid','$CPU','$globalCachesize','$cachesDirectory','$hostname')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){$sock->getFrameWork("squid.php?rebuild-caches=yes");}
		
	}
	
	private function zcheck_squidbooster_value(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		$sock=new sockets();
		$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$SquidBoosterOnly=$sock->GET_INFO("SquidBoosterOnly");
		if(!is_numeric($SquidBoosterOnly)){$SquidBoosterOnly=0;}
		if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		if($SquidBoosterMem==0){$SquidBoosterOnly=0;}
		$GLOBALS[__FUNCTION__]=$SquidBoosterOnly;
		return $SquidBoosterOnly;
	}
	
	
	private function Workers(){
		if(!$this->IS_32){return;}
		$this->SquidBoosterOnly=$this->zcheck_squidbooster_value();
		echo "Starting......: Squid Use only Booster:$this->SquidBoosterOnly\n"; 
		if($this->SquidBoosterOnly==1){return;}
		$sock=new sockets();
		$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
		if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
		
		$q=new mysql_squid_builder();
		$sql="SELECT uuid FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["uuid"]==null){$this->WorkersDefault();}
		$sql="SELECT * FROM cacheconfig WHERE `uuid`='$this->uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$CPUS=$ligne["workers"];
		
		echo "Starting......: Squid $CPUS workers\n"; 		
		

		

		
		
		$cachesDirectory=$ligne["cachesDirectory"];
		$globalCachesize=$ligne["globalCachesize"];
		
		
		if(!is_numeric($globalCachesize)){$globalCachesize=5000;}
		if($cachesDirectory==null){$cachesDirectory="/var/cache";}		
		
		
		if(!is_numeric($CPUS)){$CPUS=1;}
		if($CPUS==0){$CPUS=1;}
		if($CPUS==1){$DisableSquidSNMPMode=1;}
		
		if($DisableSquidSNMPMode==1){
			$f[]="#--------- Multiple cpus -- (disabled)";
			$f[]="workers 1";
			return @implode("\n", $f);
		}		
		

		$f[]="#--------- Multiple cpus -- (enabled)";
		$f[]="workers $CPUS";
		for($i=1;$i<$CPUS+1;$i++){
			$f[]="if \${process_number} = $i";
			$f[]="cache_dir aufs $cachesDirectory-$i $globalCachesize 128 512 $this->store_dir_minsize";
			$f[]="endif";
		}
		$f[]="#------------------";
		
		return @implode("\n", $f);
	}
	
	
	
	private function RockStore(){
		if(!$this->IS_32){return;}
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidUseRockStore=$sock->GET_INFO("SquidUseRockStore");
		if(!is_numeric($SquidUseRockStore)){$SquidUseRockStore=0;}
		echo "Starting......: Squid RockStore enabled = $SquidUseRockStore\n"; 
		
		if($SquidUseRockStore==0){return $this->Workers();}
		$MustAddCaches=false;
		$unix=new unix();
		$chown=$unix->find_program("chown");
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}					
		//see http://wiki.squid-cache.org/Features/RockStore
		if(!$this->IS_32){if($this->ASROOT){echo "Starting......: Squid this is not a 3.2 version, RockStore cannot be enabled\n";}return;}
		$params=$this->compilation_params();
		if(!preg_match("#rock#", $params["enable-storeio"])){if($this->ASROOT){echo "Starting......: Squid RockStore is not enabled in this build ({$params["enable-storeio"]}), please check the --enable-storeio compilation token\n";}return;}
		$sock=new sockets();
		$SquidDisableRockStore=$sock->GET_INFO("SquidDisableRockStore");
		if(!is_numeric($SquidDisableRockStore)){$SquidDisableRockStore=0;}
		if($SquidDisableRockStore==1){if($this->ASROOT){echo "Starting......: Squid RockStore is disabled\n";}return;}
		if($this->CPU_NUMBER<2){if($this->ASROOT){echo "Starting......: Squid Only $this->CPU_NUMBER CPU RockStore is disabled\n";}return;}
		$RockStoreDirectory=$sock->GET_INFO("RockStoreDirectory");
		$RockStoreSize=$sock->GET_INFO("RockStoreSize");
		if(!is_numeric($RockStoreSize)){$RockStoreSize=256;}
		if($RockStoreDirectory==null){$RockStoreDirectory="/var/cache/RockStore";}
		$f[]="workers $this->CPU_NUMBER"; 
		
		if($this->ASROOT){echo "Starting......: Squid RockStore $this->CPU_NUMBER CPUs shared cache of {$RockStoreSize}MB on $RockStoreDirectory\n";}
		
			
		
		for($i=0;$i<$this->CPU_NUMBER;$i++){
			$f[]="cache_dir rock $RockStoreDirectory-$i $RockStoreSize max-size=32768"; 
			if(!is_dir($RockStoreDirectory-$i)){
				@mkdir("$RockStoreDirectory-$i",755,true);
				shell_exec("/bin/chown squid:squid $RockStoreDirectory-$i");
				$MustAddCaches=true;
			}
			
		}
		
		if(!is_dir($RockStoreDirectory)){
			@mkdir("$RockStoreDirectory",755,true);
			$MustAddCaches=true;
		}

		if($MustAddCaches){			
			shell_exec("/bin/chown squid:squid $RockStoreDirectory");
			shell_exec("/etc/init.d/artica-postfix stop squid-cache");
			shell_exec("/bin/chown -R squid:squid $RockStoreDirectory");
			shell_exec("$squidbin -z");
		}
		
		return @implode("\n",$f);
	}
	
	
	private function root_squid_version(){
		$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}
		exec("$squidbin -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				echo "Starting......: Squid : Version (as root) '{$re[1]}'\n";
				return $re[1];
			}
		}
		
		$version=$unix->CACHE_VERSIONS("APP_SQUID");
		if($version<>null){return $version;}
		
		
		echo "Warning !!!!!! cannot find version in $squidbin ! !!\n";
	}
	
	private function CheckVersion(){
		if(!isset($GLOBALS["intvalVersion"])){$GLOBALS["intvalVersion"]=null;}
		if(  (isset($GLOBALS["SQUID_IS_VERSION"])) && (trim($GLOBALS["intvalVersion"])<>null)   ){
			if($this->ASROOT){echo "Starting......: Squid : {$GLOBALS["intvalVersion"]} From memory\n";}
			$this->IS_30=$GLOBALS["SQUID_IS_VERSION"][30];
			$this->IS_31=$GLOBALS["SQUID_IS_VERSION"][31];
			$this->IS_32=$GLOBALS["SQUID_IS_VERSION"][32];
			$this->intvalVersion=$GLOBALS["intvalVersion"];
			return;
		}
		$this->intvalVersion=0;
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){	
			$this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
		}
		if($this->intvalVersion==0){
			if(preg_match('#^([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){	
				$this->intvalVersion=intval($re[1]).intval($re[2]).'0';	
			}
			
		}
		if($this->intvalVersion==0){
			if($this->ASROOT){echo "Starting......: Squid : FATAL UNABLE TO DETERMINE VERSION\n";}
		}
		
		if($this->intvalVersion>0){
			
		if($re[1]==3){
			if($this->ASROOT){echo "Starting......: Squid : Is a 3.{$re[2]} version ($this->intvalVersion)\n";}
			
			if($re[2]==0){
				$this->IS_30=true;
				$this->IS_31=false;
				$this->IS_32=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=true;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;
				return;
			}
			
			
			if($re[2]==1){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;				
				return;
			}
			if($re[2]>=2){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=true;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;				
				return;
			}
		}
		
		if($re[1]==2){
			if($this->ASROOT){echo "Starting......: Squid : Is a 2.{$re[2]} version ($this->intvalVersion)\n";}
			$this->IS_30=false;
			$this->IS_31=false;
			$this->IS_32=false;
			if($re[2]>=7){
				$this->IS_27=true;
			}
		}
		
		
		if(preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)#',$this->SQUID_VERSION,$re)){
			$this->intvalVersion=intval($re[1]).intval($re[2]).intval($re[3]);
			$GLOBALS["intvalVersion"]=$this->intvalVersion;
		}else{

			if(preg_match('#^([0-9]+)\.([0-9]+)\.STABLE#',$this->SQUID_VERSION,$re)){
				$this->intvalVersion=intval($re[1]).intval($re[2]).'0';
				$GLOBALS["intvalVersion"]=$this->intvalVersion;
			}	

		}
		
	}
			
		
	}
	
	
	function isicap(){
		if(trim($this->SQUID_VERSION)==null){
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
			$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
			$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		}
		writelogs("SQUID:: SQUID_VERSION::=$this->SQUID_VERSION",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(preg_match('#([0-9\.]+)#',$this->SQUID_VERSION,$re)){$this->SQUID_VERSION=$re[1];}
		if($this->SQUID_VERSION>=3){
			if($this->SQUID_ICAP_ENABLED){
				if($this->EnableKavICAPRemote==1){return true;}
				
				if($this->KAV4PROXY_INSTALLED){
					return true;
				}else{
					writelogs("SQUID:: error_kavproxy_not_installed::",__CLASS__.'/'.__FUNCTION__,__FILE__);
					$this->kav_accept_why='{error_kavproxy_not_installed}';
				}
			}else{
				writelogs("SQUID:: error_squid_icap_not_compliance::",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->kav_accept_why='{error_squid_icap_not_compliance}';
			}
		}else{
			writelogs("SQUID:: error_squid_upto_three::version=$this->SQUID_VERSION",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->kav_accept_why='{error_squid_upto_three}<br><strong>'.$this->SQUID_VERSION."</strong>";
		}
		
		writelogs("SQUID:: version=$this->SQUID_VERSION::",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
	}
	
	
	private function KavICAPremote(){
		if($this->EnableKavICAPRemote==0){return false;}
		
		
	}
	
	function VerifyLdapBranch(){
		if(isset($GLOBALS["SQUID_MEMORYCONF"])){
			$this->squid_conf=$GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"];
			$this->ArticaSquidParameters=$GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"];
			$this->SquidBlockSites=$GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"];
			$this->EnableKav4Proxy=$GLOBALS["SQUID_MEMORYCONF"]["ArticaEnableKav4ProxyInSquid"];
			return;
		}
		
		$sock=new sockets();
		$this->squid_conf=$sock->GET_INFO('GlobalSquidConf');
		if($this->squid_conf==null){$this->squid_conf=$this->LOAD_CONF_ROOT();}
			
		$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$this->SquidBlockSites=$sock->GET_INFO('SquidBlockSites');
		$this->EnableKav4Proxy=$sock->GET_INFO('ArticaEnableKav4ProxyInSquid');
		
		$GLOBALS["SQUID_MEMORYCONF"]["GlobalSquidConf"]=$this->squid_conf;
		$GLOBALS["SQUID_MEMORYCONF"]["ArticaSquidParameters"]=$this->ArticaSquidParameters;
		$GLOBALS["SQUID_MEMORYCONF"]["SquidBlockSites"]=$this->SquidBlockSites;
		$GLOBALS["SQUID_MEMORYCONF"]["ArticaEnableKav4ProxyInSquid"]=$this->EnableKav4Proxy;
		
		}
		
		private function LOAD_CONF_ROOT(){
			if(!$GLOBALS["AS_ROOT"]){$sock=new sockets();return base64_decode($sock->getFrameWork('cmd.php?squid-GetOrginalSquidConf'));}
			if(class_exists("unix")){$unix=new unix();return @file_get_contents($unix->LOCATE_SQUID_CONF());}
		}		
	
	
	function ParseConfig(){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		if($this->ACL_ARP_ENABLED){if($this->ASROOT){echo "Starting......: Squid : ARP OK, acls with Mac addresses enabled\n";}}else{if($this->ASROOT){echo "Starting......: Squid : ARP Not enabled, acls with Mac addresses disabled\n";}}
		
		
		$network=null;
		$dns=null;
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
			$network=explode(';',$ini->_params["NETWORK"]["cdir"]);
			$dns=explode(';',$ini->_params["NETWORK"]["dns_servers"]);
			$this->listen_port=$ini->_params["NETWORK"]["LISTEN_PORT"];
			$this->second_listen_port=$ini->_params["NETWORK"]["SECOND_PORT"];
			$this->ICP_PORT=intval(trim($ini->_params["NETWORK"]["ICP_PORT"]));
			$this->visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
			$this->LDAP_AUTH=$ini->_params["NETWORK"]["LDAP_AUTH"];
			$this->NTLM_AUTH=$ini->_params["NETWORK"]["NTLM_AUTH"];
			$this->LDAP_EXTERNAL_AUTH=$ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"];
			$this->EnableParentProxy=$ini->_params["NETWORK"]["EnableParentProxy"];
			if(isset($ini->_params["NETWORK"]["prefer_direct"])){$this->prefer_direct=$ini->_params["NETWORK"]["prefer_direct"];}
			if(isset($ini->_params["NETWORK"]["nonhierarchical_direct"])){$this->nonhierarchical_direct=$ini->_params["NETWORK"]["nonhierarchical_direct"];}
			
			$this->ssl_port=$ini->_params["NETWORK"]["SSL_PORT"];
			$this->SSL_BUMP_WHITE_LIST=$ini->_params["NETWORK"]["SSL_BUMP_WHITE_LIST"];
			$this->enable_kavproxy=$ini->_params["KAV"]["enabled"];
			$this->enable_dansguardian=$ini->_params["DANSGUARDIAN"]["enable_dansguardian"];
			$this->alt_listen_port=$ini->_params["NETWORK"]["ALT_PORT"];
			$this->CACHE_SIZE=$ini->_params["CACHE"]["CACHE_SIZE"];
			$this->CACHE_PATH=$ini->_params["CACHE"]["CACHE_PATH"];
			$this->CACHE_TYPE=$ini->_params["CACHE"]["CACHE_TYPE"];
			$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
			$this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
			$this->EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidExternalAuth")));
			$this->FTP_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidFTPParams")));
			$this->SSL_BUMP=$ini->_params["NETWORK"]["SSL_BUMP"];
			
			$this->wccp2_enabled=$ini->_params["NETWORK"]["wccp2_enabled"];
			$this->wccp2_router=$ini->_params["NETWORK"]["wccp2_router"];
			$this->wccp2_forwarding_method=$ini->_params["NETWORK"]["wccp2_forwarding_method"];
			$this->wccp2_return_method=$ini->_params["NETWORK"]["wccp2_return_method"];
			$this->wccp2_assignment_method=$ini->_params["NETWORK"]["wccp2_assignment_method"];
			$this->enable_ftp_restrictions=$ini->_params["NETWORK"]["enable_ftp_restrictions"];
			$this->EnableUserAgentBanAll=$ini->_params["NETWORK"]["EnableUserAgentBanAll"];
			$this->EnableChangeRequestSize=$ini->_params["NETWORK"]["EnableChangeRequestSize"];
			$this->allow_squid_localhost=$ini->_params["NETWORK"]["allow_squid_localhost"];
			$this->EnableICPPort=$ini->_params["NETWORK"]["EnableICPPort"];
			$this->url_rewrite_bypass=$ini->_params["NETWORK"]["url_rewrite_bypass"];
			
			
			if(isset($ini->_params["NETWORK"]["ignore_expect_100"])){$this->ignore_expect_100=$ini->_params["NETWORK"]["ignore_expect_100"];}
			
			$this->enable_kavproxy=$sock->GET_INFO('kavicapserverEnabled');
			if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
			if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
			if(!is_numeric($this->url_rewrite_bypass)){$this->url_rewrite_bypass=0;}
			if(!is_numeric($this->ICP_PORT)){$this->ICP_PORT=0;}
			if(!is_numeric($this->SSL_BUMP_WHITE_LIST)){$this->SSL_BUMP_WHITE_LIST=0;}
			if(!is_numeric($this->nonhierarchical_direct)){$this->nonhierarchical_direct=1;}
			if(!is_numeric($this->prefer_direct)){$this->prefer_direct=0;}
			
			
				if(is_array($ini->_params)){
					while (list ($num, $val) = each ($ini->_params)){
						if(preg_match('#cache:(.+)#',$num,$re)){
							$this->cache_list[$re[1]]=array(
							"cache_type"=>$ini->_params[$num]["cache_type"],
							"cache_dir_level1"=>$ini->_params[$num]["cache_dir_level1"],
							"cache_dir_level2"=>$ini->_params[$num]["cache_dir_level2"],
							"cache_size"=>$ini->_params[$num]["cache_size"],
							"cache_maxsize"=>$ini->_params[$num]["cache_maxsize"],
							);
						}
						
						if(preg_match('#time:([0-9]+):(.+)#',$num,$re)){
							while (list ($a, $b) = each ($ini->_params[$num])){
								$this->acl_times[$num][$a]=$b;
							}
						}
					}
				}			
			
		}
		
		if($this->ignore_expect_100==null){$this->ignore_expect_100="0";}
		if($this->wccp2_enabled==null){$this->wccp2_enabled="0";}
		if($this->wccp2_forwarding_method==null){$this->wccp2_forwarding_method="1";}
		if($this->wccp2_return_method==null){$this->wccp2_return_method="1";}
		if($this->wccp2_assignment_method==null){$this->wccp2_assignment_method="hash";}
		if($this->enable_ftp_restrictions==null){$this->enable_ftp_restrictions="0";}
		if($this->EnableChangeRequestSize==null){$this->EnableChangeRequestSize="0";}
		if($this->enable_cicap==null){$this->enable_cicap=0;}
		if($this->listen_port==null){$this->listen_port=3128;}
		if($this->visible_hostname==null){$this->visible_hostname="proxyweb";}
		if($this->LDAP_AUTH==null){$this->LDAP_AUTH=0;}
		if($this->NTLM_AUTH==null){$this->NTLM_AUTH=0;}
		if($this->enable_kavproxy==null){$this->enable_kavproxy=0;}
		if($this->enable_dansguardian==null){$this->enable_dansguardian=0;}
		if($this->CACHE_SIZE==null){$this->CACHE_SIZE=2000;}
		if($this->CACHE_PATH==null){$this->CACHE_PATH="/var/cache/squid";}
		if($this->CACHE_TYPE==null){$this->CACHE_TYPE="aufs";}
		if($this->SSL_BUMP==null){$this->SSL_BUMP=0;}
		
		if(!preg_match("#([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
    		if($re[1]<3){$this->SSL_BUMP=0;}else{if($re[2]<1){$this->SSL_BUMP=0;}}
		}
		
		

		if(is_array($network)){
			while (list ($num, $cidr) = each ($network)){
					if(trim($cidr)==null){continue;}
					$this->network_array[]=$cidr;
				}
		}
		
		if(is_array($dns)){
			while (list ($num, $serv) = each ($dns)){
				if(trim($serv)<>null){
					$this->dns_array[]=$serv;
				}
				
			}	
		}
		
		if($this->squid_conf==null){$this->squid_conf=$sock->GET_INFO("GlobalSquidConf");}
		
		$this->global_conf_array=unserialize(base64_decode($sock->GET_INFO("SquidGlobalConfArray")));
		if(!is_array($this->global_conf_array)){
			$tb=explode("\n",$this->squid_conf);
			writelogs("global_conf_array is not an array, parsing ". count($tb).
		 " lines.. ". strlen($this->squid_conf)." bytes",__CLASS__.'/'.__FUNCTION__,__FILE__);
			
			while (list ($num, $val) = each ($tb)){
			if(preg_match('#^([a-z\_]+)\s+(.+)#',$val,$re)){
				if($re[1]=="acl"){continue;}
				if($re[1]=="http_access"){continue;}
				if($re[1]=="auth_param"){continue;}
				$this->global_conf_array[$re[1]]=$re[2];
				}
			}
		}	
		
		if(!isset($this->global_conf_array["connect_timeout"])){$this->global_conf_array["connect_timeout"]="60 seconds";}
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if(!isset($this->global_conf_array["request_header_max_size"])){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["reply_header_max_size"])){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["client_request_buffer_max_size"])){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]=0;}
		if(!isset($this->global_conf_array["peer_connect_timeout"])){$this->global_conf_array["peer_connect_timeout"]="3 minutes";}
		if(!isset($this->global_conf_array["reply_body_max_size"])){$this->global_conf_array["reply_body_max_size"]=0;}
		
		
		if(!isset($this->global_conf_array["positive_dns_ttl"])){$this->global_conf_array["positive_dns_ttl"]="6 hours";}
		if(!isset($this->global_conf_array["negative_dns_ttl"])){$this->global_conf_array["negative_dns_ttl"]="300 seconds";}
		
		

		
		
	
		
		if($this->global_conf_array["dead_peer_timeout"]==null){$this->global_conf_array["dead_peer_timeout"]="10 seconds";}
		if($this->global_conf_array["dns_timeout"]==null){$this->global_conf_array["dns_timeout"]="2 minutes";}
		if($this->global_conf_array["connect_timeout"]==null){$this->global_conf_array["connect_timeout"]="60 seconds";}
		if($this->global_conf_array["peer_connect_timeout"]==null){$this->global_conf_array["peer_connect_timeout"]="3 minutes";}
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if($this->global_conf_array["maximum_object_size"]==null){$this->global_conf_array["maximum_object_size"]="300 MB";}
		if($this->global_conf_array["cache_mem"]==null){$this->global_conf_array["cache_mem"]="256 MB";}
		if($this->global_conf_array["cache_swap_high"]==null){$this->global_conf_array["cache_swap_high"]="90";}
		if($this->global_conf_array["cache_swap_low"]==null){$this->global_conf_array["cache_swap_low"]="95";}
		if($this->global_conf_array["minimum_object_size"]==null){$this->global_conf_array["minimum_object_size"]="0 KB";}
		if($this->global_conf_array["maximum_object_size_in_memory"]==null){$this->global_conf_array["maximum_object_size_in_memory"]="8 KB";}
		if($this->global_conf_array["ipcache_size"]==null){$this->global_conf_array["ipcache_size"]=1024;}
		if($this->global_conf_array["ipcache_low"]==null){$this->global_conf_array["ipcache_low"]=90;}
		if($this->global_conf_array["ipcache_high"]==null){$this->global_conf_array["ipcache_high"]=95;}
		if($this->global_conf_array["fqdncache_size"]==null){$this->global_conf_array["fqdncache_size"]=1024;}
		if($this->global_conf_array["positive_dns_ttl"]==null){$this->global_conf_array["positive_dns_ttl"]="6 hours";}
		if($this->global_conf_array["negative_dns_ttl"]==null){$this->global_conf_array["negative_dns_ttl"]="300 seconds";}		
		
		
		if($this->global_conf_array["request_header_max_size"]==null){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="0";}
		if($this->global_conf_array["client_request_buffer_max_size"]==null){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if($this->global_conf_array["reply_header_max_size"]==null){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		
		
		
		if($this->global_conf_array["reply_body_max_size"]==null){$this->global_conf_array["reply_body_max_size"]="0";}
		writelogs("Parsing squid.conf ". count($this->global_conf_array)." parameters",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
		
		
	}
	
	
	function SquidAcountUpdate(){
		$ldap=new clladp();
		$upd=array();
		$dn="cn=squidinternalauth,dc=organizations,$ldap->suffix";
		if(!$ldap->ExistsDN($dn)){
			$upd["objectClass"][]="top";
			$upd["objectClass"][]="userAccount";
			$upd["cn"][0]="squidinternalauth";
			$upd["userid"][0]="squidinternalauth";
			$upd["accountActive"][0]="TRUE";
			$upd["accountgroup"][0]="1000";
			$upd["domainname"][0]="nodomain";
			$upd["domainname"][0]="nodomain";
			$upd["homedirectory"][0]="/dev/null";
			$upd["maildir"][0]="/dev/null";
			$upd["sn"][0]="squidinternalauth";	
			$upd["userpassword"][0]=$ldap->ldap_password;
			if(!$ldap->ldap_add($dn,$upd)){
				if(posix_getuid()==0){
					echo "SquidAcountUpdate():: error $ldap->ldap_last_error\n";
				}
			}
		}else{
			$upd["userpassword"][0]=$ldap->ldap_password;
			$ldap->Ldap_modify($dn,$upd);
		}
	}
	
	
	public function cache_deny_array(){
		include_once(dirname(__FILE__)."/class.squid.acls.inc");
		$already=array();
		$acl=array();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=4 AND enabled=1";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["pattern"]);
			if($www==null){continue;}
			if($ligne["PatternType"]==2){
				$sq=new squid_acls();
				$sqgroups=$sq->GetItems($www,"dstdomain");
				while (list ($indexed, $www) = each ($sqgroups) ){$www=$this->BaseNameWebSite($www);if($www==null){continue;}if(isset($already[$www])){continue;}$already[$www]=true;$acl[]="acl DoNotCache dstdomain .$www";continue;}
				continue;
			}
			if($ligne["PatternType"]<>0){continue;}
			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($already[$www])){continue;}
			$already[$www]=true;
			$acl[]="acl DoNotCache dstdomain .$www";
			
		}

		return $acl;
		
	}
	
	
	private function _follow_x_forwarded_for(){
		$q=new mysql();
		$tr=array();
		$sql="SELECT ipsrc FROM squid_balancers WHERE enabled=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return;}
		if(mysql_num_rows($results)==0){return;}
	
	

		while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["ipsrc"]==null){continue;}
			$tr[]=$ligne["ipsrc"];
		}
		if(count($tr)==0){return;}
		
		
		$f[]="acl artica_backend_proxys src ".@implode(" ", $tr);
		$f[]="follow_x_forwarded_for allow artica_backend_proxys";
		return @implode("\n", $f);
	}
	
	private function request_header_access(){
		if($this->IS_27){return "#request_header_access/request_header_replace not supported in 2.7x\n";}
		if(!$this->ASROOT){return;}
		$results=$this->_compile_parmz();
		$enabled=false;
		while (list($num,$val)=each($results)){if(preg_match("#enable-http-violations#", $val)){$enabled=true;}}
		if(!$enabled){return "# --------- enable-http-violations no such compilation option in ".count($results)." rows";}	
		$q=new mysql();
		$tr=array();
		$ALLOWED=array();
		$BANNED=array();
		$CHANGE=array();
		$final=null;
		$t=array();
		$sql="SELECT * FROM squid_header_access WHERE active=1";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){return "# --------- MySql error detected\n";}
		if(mysql_num_rows($results)==0){return "# --------- 0 active entry\n";}	
		while ($ligne = mysql_fetch_assoc($results)) {
			
			if($ligne["allow"]==1){
				if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} allow all";continue;}
				$ALLOWED[]="request_header_access {$ligne["header"]} allow all";
				
			}
			if($ligne["allow"]==0){
				if($ligne["header"]=="All"){$final="request_header_access {$ligne["header"]} deny all";continue;}
				$BANNED[]="request_header_access {$ligne["header"]} deny all";
			}
			
			if(strlen(trim($ligne["replacewith"]))>2){
				$ligne["replacewith"]=trim($ligne["replacewith"]);
				$CHANGE[]="request_header_replace {$ligne["header"]} {$ligne["replacewith"]}";
			}
			
		}
		
		$t[]="# --------- allow: ".count($ALLOWED)." deny:".count($BANNED)." modify: ".count($CHANGE);
		
		if(count($ALLOWED)>0){
			$t[]=@implode("\n", $ALLOWED);
		}
		if(count($BANNED)>0){
			$t[]=@implode("\n", $BANNED);
		}		
		if($final<>null){
			$t[]=$final;
		}
		if(count($CHANGE)>0){
			$t[]=@implode("\n", $CHANGE);
		}
		if(count($t)>0){return @implode("\n", $t);}
		
	}
	
	
	private function _compile_parmz(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!$this->ASROOT){return;}
		$unix=new unix();
		$squidbin=$unix->find_program("squid3");
		if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
		exec("$squidbin -v 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $squidbin -v -> ".count($results). "rows\n";}
		$GLOBALS[__FUNCTION__]=$results;
		return $results;
	}
	


	
	
	public function BuildBlockedSites(){
			$sql="SELECT uri FROM squid_block ORDER BY ID DESC";
			$q=new mysql();
			$tb=array();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){if(posix_getuid()==0){echo "Starting......: Squid Mysql database, error, skip blocked sites\n";}}
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["uri"])==null){continue;}
				$pattern=$ligne["uri"];
				$pattern=str_replace(".","\.",$pattern);
				$pattern=str_replace("*",".*",$pattern);
				
				if(!preg_match("#^(http|ftp|ftps)\:#",$pattern)){
					$pattern="^.+?$pattern";
				}else{
					$pattern="^$pattern";
				}			
				$tb[]=$pattern;
			}
			
			$q=new mysql_squid_builder();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=3 AND enabled=1";
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["pattern"])==null){continue;}
				$pattern=$ligne["pattern"];
				$pattern=str_replace(".","\.",$pattern);
				$pattern=str_replace("*",".*",$pattern);
				if(!preg_match("#^(http|ftp|ftps)\:#",$pattern)){$pattern="^.+?$pattern";}else{$pattern="^$pattern";}			
				$tb[]=$pattern;
			}				
				
			
			
			
			$countdesite=count($tb);
			writelogs("$countdesite rows",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			if(posix_getuid()==0){echo "Starting......: Squid $countdesite blocked site(s)\n";}
			
			if(is_array($tb)){$conf=implode("\n",$tb);}else{$conf="";}
			@file_put_contents("/etc/squid3/squid-block.acl",$conf);
			return $conf;
	}
		
function SaveToLdap($norestart=false){
			if(!$this->kav_accept){
				writelogs("SQUID:: kavaccept = FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->enable_kavproxy=0;
			}
			if($this->hasProxyTransparent==1){$this->LDAP_AUTH=0;}else{
				$this->wccp2_enabled=0;
			}
			$sock=new sockets();
			$ini=$ini."[NETWORK]\n";
			$ini=$ini."cdir=".implode(";",$this->network_array)."\n";
			$ini=$ini."dns_servers=".implode(";",$this->dns_array)."\n";
			$ini=$ini."LISTEN_PORT=$this->listen_port\n";
			$ini=$ini."SECOND_PORT=$this->second_listen_port\n";
			$ini=$ini."ICP_PORT=$this->ICP_PORT\n";
			$ini=$ini."SSL_PORT=$this->ssl_port\n";
			$ini=$ini."SSL_BUMP_WHITE_LIST=$this->SSL_BUMP_WHITE_LIST\n";
			
			
			
			$ini=$ini."EnableParentProxy=$this->EnableParentProxy\n";
			$ini=$ini."prefer_direct=$this->prefer_direct\n";
			$ini=$ini."nonhierarchical_direct=$this->nonhierarchical_direct\n";
			
			
			
			if($this->enable_dansguardian==1){
					$this->alt_listen_port=$sock->RandomPort();
					$dansguardian=new dansguardian();
					$dansguardian->SaveSettings();
			}
			$ini=$ini."wccp2_enabled=$this->wccp2_enabled\n";
			$ini=$ini."wccp2_router=$this->wccp2_router\n";
			$ini=$ini."wccp2_forwarding_method=$this->wccp2_forwarding_method\n";
			$ini=$ini."wccp2_return_method=$this->wccp2_return_method\n";
			$ini=$ini."wccp2_assignment_method=$this->wccp2_assignment_method\n";
			$ini=$ini."enable_ftp_restrictions=$this->enable_ftp_restrictions\n";
			$ini=$ini."EnableUserAgentBanAll=$this->EnableUserAgentBanAll\n";
			$ini=$ini."EnableChangeRequestSize=$this->EnableChangeRequestSize\n";
			$ini=$ini."allow_squid_localhost=$this->allow_squid_localhost\n";
			$ini=$ini."url_rewrite_bypass=$this->url_rewrite_bypass\n";
			$ini=$ini."EnableICPPort=$this->EnableICPPort\n";
			
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is 3.2.x OK\n";}}
			
			if(($this->IS_30) && (!$this->IS_32)){
				$ini=$ini."ignore_expect_100=$this->ignore_expect_100\n";
			}
			
			
			
			
			$ini=$ini."ALT_PORT=$this->alt_listen_port\n";
			$ini=$ini."visible_hostname=$this->visible_hostname\n";
			$ini=$ini."LDAP_AUTH=$this->LDAP_AUTH\n";
			$ini=$ini."NTLM_AUTH=$this->NTLM_AUTH\n";
			$ini=$ini."LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH\n";
			$ini=$ini."SSL_BUMP=$this->SSL_BUMP\n";
			
			
			
			$ini=$ini."[KAV]\n";
			$ini=$ini."enabled=$this->enable_kavproxy\n";
			$ini=$ini."[DANSGUARDIAN]\n";
			$ini=$ini."enable_dansguardian=$this->enable_dansguardian\n";
			$ini=$ini."[CACHE]\n";
			$ini=$ini."CACHE_SIZE=$this->CACHE_SIZE\n";
			$ini=$ini."CACHE_PATH=$this->CACHE_PATH\n";
			$ini=$ini."CACHE_TYPE=$this->CACHE_TYPE\n";
			
			
							
	

			if(is_array($this->cache_list)){
				reset($this->cache_list);
				$num=null;
				$val=null;
				while (list ($num, $val) = each ($this->cache_list)){
					$ini=$ini."[cache:$num]\n";
					$ini=$ini."cache_type={$val["cache_type"]}\n";
					$ini=$ini."cache_dir_level1={$val["cache_dir_level1"]}\n";
					$ini=$ini."cache_dir_level2={$val["cache_dir_level2"]}\n";
					$ini=$ini."cache_size={$val["cache_size"]}\n";
					$ini=$ini."cache_maxsize={$val["cache_maxsize"]}\n";
				}
			}
			
			if(is_array($this->acl_times)){
				reset($this->acl_times);
				while (list ($num, $array) = each ($this->acl_times)){
					$ini=$ini."[$num]\n";
					while (list ($a, $b) = each ($array)){
						$ini=$ini."$a=$b\n";
					}
					
				}
				
			}
			$sock=new sockets();
			writelogs("Save SquidGlobalConfArray ".count($this->global_conf_array)." parameters",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sock->SaveConfigFile(base64_encode(serialize($this->global_conf_array)),"SquidGlobalConfArray");
			$sock->SET_INFO("DansGuardianEnabled",$this->enable_dansguardian);
			$sock->SET_INFO('kavicapserverEnabled',$this->enable_kavproxy);
			$sock->SET_INFO("hasProxyTransparent",$this->hasProxyTransparent);
			$sock->SaveConfigFile($ini,"ArticaSquidParameters");
			//$sock->SaveConfigFile($this->BuildSquidConf(),"GlobalSquidConf");
			$sock->SET_INFO("ArticaEnableKav4ProxyInSquid",$this->enable_kavproxy);
			$sock->SET_INFO("CicapEnabled",$this->enable_cicap);
			$sock->SET_INFO("squidGuardEnabled",$this->enable_squidguard);
			$sock->SET_INFO("EnableUfdbGuard",$this->enable_UfdbGuard);
			$sock->SET_INFO("EnableAdZapper",$this->enable_adzapper);
			$sock->SET_INFO("EnableSquidClamav",$this->enable_squidclamav);
			$sock->SET_INFO("KavMetascannerEnable",$this->enable_metascanner);
			$sock->SET_INFO("SquidEnableStreamCache",$this->enable_streamcache);
			$sock->SET_INFO("SquideCapAVEnabled",$this->enable_ecapav);
			
			
			
			
			$sock->SaveConfigFile(base64_encode(serialize($this->EXTERNAL_LDAP_AUTH_PARAMS)),"SquidExternalAuth");
			$sock->SaveConfigFile(base64_encode(serialize($this->FTP_PARAMS)),"SquidFTPParams");
			

			
				
			
			
			if($this->enable_dansguardian==1){
				writelogs("",__FUNCTION__,__FILE__);
				writelogs("Save Dansguardian configuration...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$dans=new dansguardian();
				$dans->SaveSettings();
			}
			
			if($this->enable_kavproxy==1){
				$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");
			}
			
			if(!$norestart){
				$sock->getFrameWork("squid.php?build-smooth=yes");
			}
			return true;
			
		}
		
		function SaveToServer($norestart=false){
			if(!$norestart){
				$sock=new sockets();
				$sock->getFrameWork("cmd.php?squidnewbee=yes");	
			}
		}
		
		

		
		public function SquidGuardDatabasesStatus($all=0){
			$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
			while (list ($a, $b) = each ($datas)){
				if(preg_match("#domainlist.+?(.+)#",$b,$re)){
					$file="/var/lib/squidguard/{$re[1]}";
					if(filesize($file)==0){continue;}
					$f[]=$file;
					continue;
				}
						
				if(preg_match("#urllist.+?(.+)#",$b,$re)){
					$file="/var/lib/squidguard/{$re[1]}";
					if(filesize($file)==0){continue;}
					$f[]=$file;
					continue;
				}
				
				
			}
			
			if($all==0){while (list ($a, $b) = each ($f)){
				if(!is_file("$b.db")){
					$array[]="$b.db";
				}
				
			}}else{
				while (list ($a, $b) = each ($f)){
					$array[]="$b.db";
				}
			}
			
			
			return $array;
			
		}		
		
		
		private function ldap_auth_conf(){
			if(!is_array($this->global_conf_array)){$this->ParseConfig();}
			if($GLOBALS["VERBOSE"]){if(!$this->ASROOT){$GLOBALS["VERBOSE"]=false;}}
			if($this->EnableKerbAuth==1){return "#--------- LDAP AUTH settings\n#squid_kerb_auth enabled\n";}
			if($GLOBALS["VERBOSE"]){
				echo "DEBUG : LDAP_AUTH:$this->LDAP_AUTH\n";
				echo "DEBUG : LDAP_EXTERNAL_AUTH:$this->LDAP_EXTERNAL_AUTH\n";
			}
			
			if($this->LDAP_AUTH==0){
				if($this->LDAP_EXTERNAL_AUTH<>1){
					if($this->ASROOT){echo "Starting......: Squid : LDAP authentication method is disabled\n";}
					return null;
				}
			}
			
			
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$ldap=new clladp();
			if($ldap->ldap_host==null){$ldap->ldap_host="127.0.0.1";}
			
			$ldap_host=$ldap->ldap_host;
			$ldap_port=$ldap->ldap_port;
			$suffix=$ldap->suffix;
			$ldap_admin="cn=$ldap->ldap_admin,$ldap->suffix";
			$ldap_password=$ldap->ldap_password;
			$user_filter="(&(objectClass=userAccount)(uid=%s))";
			$group_filter="(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))";
			$auth_banner="Squid proxy-caching web server";
			
			
			if($this->LDAP_EXTERNAL_AUTH==1){
				if($this->ASROOT){echo "Starting......: Squid : Using remote LDAP database\n";}
				$ldap_host=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
				$ldap_port=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];	
				$ldap_password=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
				$ldap_admin=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];		
				$suffix=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
				$user_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
				$group_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
				$auth_banner=$this->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];					
			}else{
				if($this->ASROOT){echo "Starting......: Squid : Using local LDAP database\n";}
			}
					
			$SQUID_LDAP_AUTH=$this->squid_ldap_auth_path();
			$squid_ldap_group_path=$this->squid_ldap_group_path();
			$conf[]= "#--------- LDAP AUTH settings\n";
			$conf[]= "#Authentification mode, building using squid compiled for $ldap_host:$ldap_port";
			if(trim($SQUID_LDAP_AUTH)<>null){
				
				if($this->ASROOT){echo "Starting......: Squid : Using User:`$SQUID_LDAP_AUTH`, Group `$squid_ldap_group_path`\n";}
				$conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";
				$conf[]= "#--------- GLOBAL";
				$conf[]= "external_acl_type ldap_group %LOGIN " . $this->squid_ldap_group_path()." -D \"$ldap_admin\" -w \"$ldap_password\" -b \"$suffix\"  -f \"$group_filter\" -S -v 3 -h $ldap_host -p $ldap_port";
				$conf[]= "auth_param basic children 5";
				$conf[]= "auth_param basic credentialsttl 2 hour";
				$conf[]= "auth_param basic realm $auth_banner";
				$conf[]= "authenticate_ttl 1 hour";
				$conf[]= "authenticate_ip_ttl 60 seconds";					
				$conf[]= "acl ldapauth proxy_auth REQUIRED";
				$conf[]=$this->WHITELISTED_AUTH_BROWSERS();
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow ldapauth";
				
				$conf[]="";
			}else{
				$conf[]= "#No LDAP auth_param basic program found !";
				$conf[]="";
			}

			return implode("\n",$conf);
			
		}
		
		private function ACL_FTP_RESTRICTIONS(){
			if($this->enable_ftp_restrictions<>1){return;}
			return "acl clients_ftp src \"/etc/squid3/clients_ftp.acl\"\n";
		}
		
		private function squid_ldap_group_path(){
			$basic=$this->squid_ldap_auth_path();
			if(strlen($basic)==0){return;}
			$basic=dirname($basic);
			if(is_file("$basic/squid_ldap_group")){return "$basic/squid_ldap_group";}
			if(is_file("$basic/ext_ldap_group_acl")){return "$basic/ext_ldap_group_acl";}
			if($this->ASROOT){echo "Starting......: WARNING : no ext group found in `$basic` path !!!\n";}
			
			
			
		}
		
		private function squid_ldap_auth_path(){
			if(is_file('/lib/squid3/basic_ldap_auth')){return '/lib/squid3/basic_ldap_auth';}
			if(is_file('/usr/lib/squid3/squid_ldap_auth')){return '/usr/lib/squid3/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid3/squid_ldap_auth')){return '/usr/lib64/squid3/squid_ldap_auth';}
			if(is_file('/lib/squid3/squid_ldap_auth')){return '/lib/squid3/squid_ldap_auth';}
			if(is_file('/lib64/squid3/squid_ldap_auth')){return '/lib64/squid3/squid_ldap_auth';}
			if(is_file('/usr/lib/squid/ldap_auth')){return '/usr/lib/squid/ldap_auth';}
			if(is_file('/usr/lib/squid/squid_ldap_auth')){return '/usr/lib/squid/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid/squid_ldap_auth')){return '/usr/lib64/squid/squid_ldap_auth';}
			if(is_file('/usr/lib64/squid/ldap_auth')){return '/usr/lib64/squid/ldap_auth';}
			if(is_file('/usr/local/lib/squid/ldap_auth')){return '/usr/local/lib/squid/ldap_auth';}
			if(is_file('/usr/local/lib64/squid/ldap_auth')){return '/usr/local/lib64/squid/ldap_auth';}
			if(is_file('/opt/artica/libexec/squid_ldap_auth')){return '/opt/artica/libexec/squid_ldap_auth';} 			
	
		}
		
		
		
		
		
		private function ACL_BANNED_COMPUTERS_IP(){
			@file_put_contents("/etc/squid3/banned-computers-by-mac.acl","");
			@file_put_contents("/etc/squid3/banned-computers.acl","");
			
			
			$f=array();
			$MC=array();
			if(isset($GLOBALS["ACL_BANNED_COMPUTERS_IP"])){return $GLOBALS["ACL_BANNED_COMPUTERS_IP"];}
			$returned=null;
			$sock=new sockets();
			if(!$this->ACL_ARP_ENABLED){
				echo "Starting......: Squid : acl ARP are disabled\n";
			}else{
				echo "Starting......: Squid : acl ARP are enabled\n";
			}
			
			
			$sql="SELECT ID,pattern,uid FROM dansguardian_files WHERE filename='bannediplist' AND RuleID=1 ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$MAC=null;
				if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$ligne["pattern"])){continue;}
				if(trim($ligne["pattern"])==null){continue;}
				if($this->ACL_ARP_ENABLED){
					if(trim($ligne["uid"])<>null){
						$cmp=new computers($ligne["uid"]);
						$MAC=$cmp->ComputerMacAddress;
						$ligne["pattern"]=$cmp->ComputerIP;
					}
					
					if(!IsPhysicalAddress($MAC)){$MAC=$sock->getFrameWork("cmd.php?ip-to-mac={$ligne["pattern"]}");if(!IsPhysicalAddress($MAC)){$MAC=null;}}				
					if($MAC<>null){$MC[]=$MAC;continue;}
					echo "Starting......: Squid : NO Mac address for {$ligne["pattern"]}\n";
				}
				
				$f[]=trim($ligne["pattern"]);
				
			}
			
			$q=new mysql_squid_builder();
			$acls=new squid_acls();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=0 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($ligne["PatternType"]==1){if($this->ACL_ARP_ENABLED){$MC[]=$ligne["pattern"];continue;}}
				if($ligne["PatternType"]==0){$f[]=trim($ligne["pattern"]);}
				if($ligne["PatternType"]==2){
					$tempf=array();
					$tempf=$acls->GetItems($ligne["pattern"],"src");
					if(count($tempf)>0){$f[]=$tempf;}
					$tempf=array();
					$tempf=$acls->GetItems($ligne["pattern"],"arp");
					if(count($tempf)>0){$MC[]=@implode("\n", $tempf);}
				}				
			}
			
			
			if(count($f)>0){
				$f[]="";
				$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"]="http_access deny banned_computers";
				@file_put_contents("/etc/squid3/banned-computers.acl",@implode("\n",$f));
				$returned="acl banned_computers src \"/etc/squid3/banned-computers.acl\"\n";
			}
			
			if(count($MC)>0){
				$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"]="http_access deny banned_mac_computers";
				@file_put_contents("/etc/squid3/banned-computers-by-mac.acl",@implode("\n",$MC));
				$returned=$returned."acl banned_mac_computers arp \"/etc/squid3/banned-computers-by-mac.acl\"\n";
			}

			$GLOBALS["ACL_BANNED_COMPUTERS_IP"]=$returned;
			return $returned;
			
		}
		
		private function ACL_WHITE_COMPUTERS_IP(){
			if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
			@file_put_contents("/etc/squid3/whitelisted-computers-by-mac.acl","");
			@file_put_contents("/etc/squid3/whitelisted-computers.acl","");
			
			
			$f=array();
			$MC=array();
			echo "Starting......: Squid : Checking whitelisted addresses\n";
			if(isset($GLOBALS["ACL_WHITE_COMPUTERS_IP"])){echo "Starting......: Squid : Checking whitelisted addresses Already done.\n";return $GLOBALS["ACL_WHITE_COMPUTERS_IP"];}
			
			
			$returned=null;
			$sock=new sockets();
			if(!$this->ACL_ARP_ENABLED){
				echo "Starting......: Squid : acl ARP are disabled\n";
			}else{
				echo "Starting......: Squid : acl ARP are enabled\n";
			}
			
			$sql="SELECT ID,pattern,uid FROM dansguardian_files WHERE filename='exceptioniplist' AND RuleID=1 ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			$numnum=mysql_num_rows($results);
			echo "Starting......: Squid : Checking whitelisted $numnum entries\n";
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$MAC=null;
				if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#",$ligne["pattern"])){continue;}
				if(trim($ligne["pattern"])==null){continue;}
				
				
				if($this->ACL_ARP_ENABLED){
					if(trim($ligne["uid"])<>null){
						$cmp=new computers($ligne["uid"]);
						$MAC=$cmp->ComputerMacAddress;
						$ligne["pattern"]=$cmp->ComputerIP;
						
					}
				
					if(!IsPhysicalAddress($MAC)){
						$MAC=$sock->getFrameWork("cmd.php?ip-to-mac={$ligne["pattern"]}");
						if(!IsPhysicalAddress($MAC)){$MAC=null;}
					}				
					if($MAC<>null){$MC[]=$MAC;continue;}
					echo "Starting......: Squid : NO Mac address for {$ligne["pattern"]}\n";
				}
				
				$f[]=trim($ligne["pattern"]);
				
			}
			
			$q=new mysql_squid_builder();
			$acls=new squid_acls();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=1 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if($ligne["PatternType"]==1){if($this->ACL_ARP_ENABLED){$MC[]=$ligne["pattern"];continue;}}
				if($ligne["PatternType"]==0){$f[]=trim($ligne["pattern"]);}
				if($ligne["PatternType"]==2){
					$tempf=array();
					$tempf=$acls->GetItems($ligne["pattern"],"src");
					if(count($tempf)>0){$f[]=$tempf;}
					$tempf=array();
					$tempf=$acls->GetItems($ligne["pattern"],"arp");
					if(count($tempf)>0){$MC[]=@implode("\n", $tempf);}
				}
			}			
			
			echo "Starting......: Squid : ". count($f)." whitelisted addresse(s)\n";
			
			if(count($f)>0){
				$f[]="";
				$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"]="http_access allow whitelisted_computers";
				$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny whitelisted_computers";
				@file_put_contents("/etc/squid3/whitelisted-computers.acl",@implode("\n",$f));
				$returned="\nacl whitelisted_computers src \"/etc/squid3/whitelisted-computers.acl\n";
			}

			
			
				//$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"]="http_access allow whitelisted_mac_computers";
				@file_put_contents("/etc/squid3/whitelisted-computers-by-mac.acl",@implode("\n",$MC));
				//$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny whitelisted_mac_computers";
				//$returned=$returned."acl whitelisted_mac_computers arp \"/etc/squid3/whitelisted-computers-by-mac.acl\n";
			
			
			if(!is_file(dirname(__FILE__)."/class.squid.acls.groups.inc")){$sock->TOP_NOTIFY("WARNING class.squid.acls.groups.inc no such class contact support !!!");}else{include_once(dirname(__FILE__)."/class.squid.acls.groups.inc");}
			if(!class_exists("squid_acls_groups")){$sock->TOP_NOTIFY("WARNING !!!!!!!!!!!!!!!!!!! squid_acls_groups no such class contact support !!!");echo "WARNING !!!!!!!!!!!!!!!!!!! squid_acls_groups no such class !!!\n";}
			
			if(class_exists("squid_acls_groups")){
				$acls=new squid_acls_groups();
				$acls_deny=$acls->buildacls_bytype("url_rewrite_access_deny");
				if(count($acls_deny)>0){
					while (list ($index, $line) = each ($acls_deny) ){
						$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny $line";
					}
				}
			}			
			
			
			$GLOBALS["ACL_WHITE_COMPUTERS_IP"]=$returned;
			$GLOBALS[__FUNCTION__]=$returned;
			return $returned;
			
		}		
		
		private function WCCP(){
			if($this->hasProxyTransparent<>1){return;}
			if($this->wccp2_enabled<>1){return;}
			$conf[]="";	
			$conf[]="#Cisco's Web Cache Coordination Protocol";
			$conf[]="wccp2_router $this->wccp2_router";
			$conf[]="wccp2_forwarding_method $this->wccp2_forwarding_method";
			$conf[]="wccp2_return_method $this->wccp2_return_method";
			$conf[]="wccp2_assignment_method $this->wccp2_assignment_method";
			$conf[]="";	
			return implode("\n",$conf);
		}
		
		private function find_program($binaryname){
			$f["/lib/squid3"]=true; 
			$f["/usr/lib/squid3"]=true; 
			$f["/usr/lib64/squid3"]=true; 
			$f["/lib/squid3"]=true; 
			$f["/lib64/squid3"]=true; 
			$f["/usr/lib/squid"]=true; 
			$f["/usr/lib64/squid"]=true; 
			$f["/usr/local/lib/squid"]=true; 
			$f["/usr/local/lib64/squid"]=true; 
			$f["/opt/artica/libexec"]=true; 
			while (list ($dir, $none) = each ($f) ){if(is_file("$dir/$binaryname")){return "$dir/$binaryname";}}			
			
			
		}
		
		
		private function pinger(){
			if(!$this->ASROOT){return;}
			$pinger=$this->find_program("pinger");
			if(!is_file($pinger)){return;}
			$f[]="#ICMP Pinger ------------- (". __FUNCTION__." L.".__LINE__.")";
			$f[]="pinger_program $pinger";
			$f[]="";
			$unix=new unix();
			$unix->chown_func("root","root" ,$pinger);
			$unix->chmod_func(04755, $pinger);
			
		}
		
		
		public function ACL_MESSENGERS(){
			$sock=new sockets();
			$SquidMessengers=unserialize(base64_decode($sock->GET_INFO("SquidMessengers")));
			if($SquidMessengers["AOL"]==1){
				$conf[]="";
				$conf[]="# AOL Instant Messenger to connect to oscar.aol.com";
				$conf[]="acl AIM_ports port 5190 9898";
				$conf[]="acl AIM_domains dstdomain .oscar.aol.com .blue.aol.com";
				$conf[]="acl AIM_domains dstdomain .messaging.aol.com .aim.com";
				$conf[]="acl AIM_hosts dstdomain login.oscar.aol.com login.glogin.messaging.aol.com toc.oscar.aol.com";
				$conf[]="acl AIM_nets dst 64.12.0.0/255.255.0.0";
				$conf[]="acl AIM_methods method CONNECT";
				$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"]="http_access allow AIM_methods AIM_ports AIM_nets\nhttp_access allow AIM_methods AIM_ports AIM_hosts";				
			}
			
			if($SquidMessengers["IRC"]==1){	
				$conf[]="";		
				$conf[]="# Permit IRC";
				$conf[]="acl IRC_ports port 6667";
				$conf[]="acl IRC_domains dstdomain .freenode.net";
				$conf[]="acl IRC_hosts dstdomain  irc.freenode.net";
				$conf[]="acl IRC_methods method CONNECT";		
				$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"]="http_access allow IRC_methods IRC_ports IRC_hosts\nhttp_access allow IRC_methods IRC_ports IRC_domains";
				}
		
			if($SquidMessengers["YAHOO"]==1){
				$conf[]="";				
				$conf[]="# Permit Yahoo Messenger";
				$conf[]="acl YIM_ports port 5050";
				$conf[]="acl YIM_domains dstdomain .yahoo.com .yahoo.co.jp";
				$conf[]="acl YIM_hosts dstdomain scs.msg.yahoo.com cs.yahoo.co.jp";
				$conf[]="acl YIM_methods method CONNECT";
				$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"]="http_access allow YIM_methods YIM_ports YIM_hosts\nhttp_access allow YIM_methods YIM_ports YIM_domains";
			}
			
			if($SquidMessengers["GOOGLE"]==1){
				$conf[]="";
				$conf[]="# Permit Google Talk";
				$conf[]="acl GTALK_ports port 5222 5050 443";
				$conf[]="acl GTALK_domains dstdomain .google.com";
				$conf[]="acl GTALK_hosts dstdomain talk.google.com";
				$conf[]="acl GTALK_methods method CONNECT";		

				
				
				$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"]="http_access allow  GTALK_ports GTALK_hosts GTALK_methods\n
				http_access allow GTALK_methods GTALK_ports GTALK_domains";
			}
			if($SquidMessengers["MSN"]==1){
				$conf[]="";
				$conf[]="# Permit MSN";
				$conf[]="acl MSN_ports port 1863 443 1503";
				$conf[]="acl MSN_domains dstdomain .microsoft.com .hotmail.com .live.com .msft.net .msn.com .passport.com";
				//$conf[]="acl MSN_hosts dstdomain messenger.hotmail.com";
				//$conf[]="acl MSN_nets dst 207.46.111.0/255.255.255.0";
				$conf[]="acl MSN_methods method CONNECT";	
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_hosts";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_hosts";
				$SquidMessengers_httpaccess[]="http_access allow MSN_ports MSN_domains MSN_methods";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_domains";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_domains";
				//$SquidMessengers_httpaccess[]="http_access allow MSN_methods MSN_ports MSN_nets";
				$SquidMessengers_httpaccess[]="";
				
				$GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"]=@implode("\n",$SquidMessengers_httpaccess);
			}
			
			if(!isset($conf)){return null;}
			if(!is_array($conf)){return null;}
			return @implode("\n",$conf)."\n\n";
			
		}
		
		

			
			
			
		
	public function WHITELISTED_AUTH(){
		if(isset($GLOBALS["WHITELISTED_AUTH_ARRAY"])){return $GLOBALS["WHITELISTED_AUTH_ARRAY"];}
		$q=new mysql();
		$l=array();
		if($this->ASROOT){echo "Starting......: Squid check whitelisted site(s)\n";}
		$sql="SELECT * FROM squid_white WHERE task_type='AUTH' ORDER BY uri";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH squid_white=".mysql_num_rows($results)." rows\n";}
		$ALREADYBASE=array();
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["uri"]);
			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($GLOBALS["HTTP_ACCESS"]["ALREADY"][$www])){continue;}
			$GLOBALS["HTTP_ACCESS"]["ALREADY"][$www]=true;
			$l[]="acl WhiteListAuth dstdomain .$www";
		}
		
		$q=new mysql_squid_builder();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=2 and enabled=1";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts=".mysql_num_rows($results)." rows\n";}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$www=trim($ligne["pattern"]);
			if($www==null){if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts skip `{$ligne["pattern"]}` is null\n";}continue;}
			$www=$this->BaseNameWebSite($www);
			if($www==null){continue;}
			if(isset($GLOBALS["HTTP_ACCESS"]["ALREADY"][$www])){continue;}
			$GLOBALS["HTTP_ACCESS"]["ALREADY"][$www]=true;			
			$l[]="acl WhiteListAuth dstdomain .$www";
		}		
			
		
		if(count($l)>0){
			if($this->ASROOT){echo "Starting......: Squid ". count($l)." whitelisted site(s)\n";}
			$GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"]="http_access allow WhiteListAuth";
			$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny WhiteListAuth";
			$GLOBALS["ICAP"]["WHITEAUTH"]="adaptation_access ANTIVIRUS_CHAINS deny WhiteListAuth";			
		}
		
			$GLOBALS["WHITELISTED_AUTH_ARRAY"]=@implode("\n",$l);
		
			return $GLOBALS["WHITELISTED_AUTH_ARRAY"];
		}
		
		
		private function StringToRegex($pattern){
			$pattern=str_replace("/", "\/", $pattern);
			$pattern=str_replace(".", "\.", $pattern);
			$pattern=str_replace("(", "\(", $pattern);
			$pattern=str_replace(")", "\)", $pattern);
			$pattern=str_replace("+", "\+", $pattern);
			$pattern=str_replace("?", "\?", $pattern);
			$pattern=str_replace("[", "\[", $pattern);
			$pattern=str_replace("]", "\]", $pattern);
			$pattern=str_replace("*", ".*", $pattern);
			return $pattern;
			
		}
		
		private function ACLS_USERS_ASKS(){
			if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
			$q=new mysql_squid_builder();
			$ident=false;
			if($this->LDAP_AUTH==1){$ident=true;}
			if($this->LDAP_EXTERNAL_AUTH==1){$ident=true;}
				
			
			
			if($q->COUNT_ROWS("webfilters_usersasks")==0){return;}
			@mkdir("/etc/squid3/users-asks",0755,true);
			
			$sql="SELECT * FROM webfilters_usersasks";
			$results=$q->QUERY_SQL($sql);
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$ipaddr=$ligne["ipaddr"];
				
				$website=$ligne["sitename"];
				$ipaddrName=str_replace(".", "", $ipaddr);
				$ARRAY[$ipaddrName]["UID"]=null;
				$ARRAY[$ipaddrName]["IPADDR"]=$ipaddr;
				if($ident){$ARRAY[$ipaddrName]["UID"]=$ligne["uid"];}
				$ARRAY[$ipaddrName]["WEBSITES"][$website]=true;
				
			}
			$c=0;
			while (list ($aclname, $arrayDef) = each ($ARRAY)){
				$c++;
				//if($arrayDef["UID"]<>null){
					//$f[]="acl AutoW{$c}a ident {$arrayDef["UID"]}";
				//}else{
					$f[]="acl AutoW{$c}a src {$arrayDef["IPADDR"]}";
				//}
				
				
				$tt=array();
				while (list ($sitename, $none) = each ($arrayDef["WEBSITES"])){$tt[]=".$sitename";}
				if(count($tt)>20){
					@file_put_contents("/etc/squid3/users-asks/{$aclname}dest.conf", @implode("\n", $tt)."\n");
					@chown("/etc/squid3/users-asks/{$aclname}dest.conf", "squid");
					$f[]="acl AutoW{$c}b dstdomain \"/etc/squid3/users-asks/{$aclname}dest.conf\"";
				}else{
					$f[]="acl AutoW{$c}b dstdomain ".@implode(" ", $tt);
				}
				$GLOBALS["url_rewrite_access_users"][]="url_rewrite_access deny AutoW{$c}a AutoW{$c}b";
			
			
			}

			$GLOBALS[__FUNCTION__]=@implode("\n", $f);
			return $GLOBALS[__FUNCTION__];
			
		}
	
		
		
		private function ACL_WHITELISTED_MIMETYPES(){	
			if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
			$q=new mysql_squid_builder();
			$myMimes=array();
			$myOthers=array();
			$finals=array();
			$GLOBALS["WHITELISTED_MIMETYPE"]=array();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=6 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			
			if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_MIMETYPES webfilters_blkwhlts=".mysql_num_rows($results)." rows\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$mime=trim(strtolower($ligne["pattern"]));
				if($mime==null){continue;}
				if(preg_match("#^(.+?)\/(.+)#", $mime,$re)){$myMimes[$re[1]][]=$re[2];continue;}
				if($GLOBALS["VERBOSE"]){echo "[DEBUG]: MIME TYPE: `$mime` -> other\n";}
				$myOthers[]=$mime;
			}
			
			if(count($myMimes)>0){
				while (list ($key, $array) = each ($myMimes)){
					$GLOBALS["WHITELISTED_MIMETYPE"][]="acl StreamMimeWhite rep_mime_type -i $key/(".@implode("|", $array).")";
				}
			}
			
			if(count($myOthers)>0){
				$GLOBALS["WHITELISTED_MIMETYPE"][]="acl StreamMimeWhite rep_mime_type -i ". @implode("|", $myOthers);
				
			}
			
			$GLOBALS[__CLASS__][__FUNCTION__]=$GLOBALS["WHITELISTED_MIMETYPE"];	
			
			if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){	
				return @implode("\n",$GLOBALS["WHITELISTED_MIMETYPE"]);		
			}	
			
			
			return null;
		}	
		
		private function WHITELISTED_AUTH_BROWSERS(){
			if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
			$arrayUserAgents=array();
			$sql="SELECT uri FROM squid_white WHERE task_type='AUTH_WL_USERAGENTS'";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["uri"])==null){continue;}
				$ligne["uri"]=$this->StringToRegex($ligne["uri"]);
				if(isset($arrayUserAgents[$ligne["uri"]])){continue;}
				$arrayUserAgents[$ligne["uri"]]=1;
			}
			
			
			$q=new mysql_squid_builder();
			$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=5 and enabled=1";
			$results=$q->QUERY_SQL($sql);
			if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts=".mysql_num_rows($results)." rows\n";}
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$www=trim($ligne["pattern"]);
				if($www==null){if($GLOBALS["VERBOSE"]){echo "DEBUG: WHITELISTED_AUTH webfilters_blkwhlts skip `{$ligne["pattern"]}` is null\n";}continue;}
				if(preg_match("#regex:(.+)#", $www,$re)){$www=$re[1];}else{$www=$this->StringToRegex($www);}
				if(isset($arrayUserAgents[$www])){continue;}
				$arrayUserAgents[$www]=1;
			}		
			
			$f=array();
			while (list ($UserAgent, $Notused) = each ($arrayUserAgents) ){$f[]="acl WhiteListedUserAgents browser $UserAgent";}					
			
			if(count($f)>0){
				$GLOBALS[__CLASS__][__FUNCTION__]=$f;
				$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"]="http_access allow WhiteListedUserAgents";
				return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);
			}
			$GLOBALS[__CLASS__][__FUNCTION__]=null;
			return null;
			
		}
		
		private function krb_auth_conf(){
			if(!$this->ASROOT){return;}
			$unix=new unix();
			$hostname_bin=$unix->find_program("hostname");
			exec("$hostname_bin -f 2>&1",$results);
			$myFullHostname=trim(@implode("", $results));
			if($this->EnableKerberosAuthentication==0){return null;}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$ntlm_auth=$unix->find_program("ntlm_auth");
			$binary=$users->squid_kerb_auth_path;
			if(strlen($binary)<2){
				return "#No squid_kerb_auth in compilation !\n";
			}
			
			echo "Starting......: Kerberos Authentification enabled\n";
			if(is_file("/etc/krb5.keytab")){
				$chown=$unix->find_program("chown");
				$chmod=$unix->find_program("chmod");
				shell_exec("$chown squid:squid /etc/krb5.keytab");
				shell_exec("$chmod 666 /etc/krb5.keytab");
			}
			
			if(is_file("/usr/local/bin/negotiate_wrapper")){
				//$users->squid_kerb_auth_path="/usr/local/bin/negotiate_wrapper";
				if(is_file("$ntlm_auth")){
					$ntlm=" --ntlm /usr/bin/ntlm_auth";
				}
				$t[]="auth_param negotiate program /usr/local/bin/negotiate_wrapper -d $ntlm --diagnostics --helper-protocol=squid-2.5-ntlmssp --domain=EXAMPLE --kerberos /usr/lib/squid3/squid_kerb_auth -d -s GSS_C_NO_NAME";
				
			}
	
			$conf[]= "#--------- kerberos AUTH settings myhostname $myFullHostname\n";
			//$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d -s HTTP/$myFullHostname";
			$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d";
			$conf[]= "auth_param negotiate children 10";
			$conf[]= "auth_param negotiate keep_alive on";
			$conf[]= "auth_param basic realm Squid proxy-caching web server";
			$conf[]= "auth_param basic credentialsttl 2 hour";
			$conf[]= "authenticate_ip_ttl 60 seconds";			
			$conf[]= "authenticate_cache_garbage_interval 10 seconds";
			$conf[]= "authenticate_ttl 0 hour";
			$conf[]= "#--------- kerberos ACL settings";
			$conf[]= "acl AUTHENTICATED proxy_auth REQUIRED";
			$conf[]=$this->WHITELISTED_AUTH_BROWSERS();	
			$conf[]="#------------------------------------------------------------------------- ";
			$conf[]="";	
			return @implode("\n", $conf);		
		}
		
		
		private function BaseNameWebSite($www){
			if(trim($www)==null){return;}
			if(is_numeric(trim($www))){continue;}
			if(preg_match("#http.+?\/\/(.+)#", $www,$re)){$www=$re[1];}
			if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
			if(substr($www,0,1)<>"."){$www=".$www";}
			if(substr($www,strlen($www)-1,1)=="/"){$www=substr($www, 0,strlen($www)-1);}
			$exploded=explode(".", $www);
			$sitebase=$exploded[count($exploded)-2].".".$exploded[count($exploded)-1];
			if(substr($sitebase, 0,1)=="."){$sitebase=substr($sitebase, 1,strlen($sitebase));}		
			return $sitebase;
		}
		
		
		
		private function ntlm_auth_conf(){
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$sock=new sockets();
			$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
			if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
			// voir permissions on /var/lib/samba/winbindd_privileged
			// http://www.cyberciti.biz/faq/squid-ntlm-authentication-configuration-howto/
			
			if($this->EnableKerbAuth==1){
				if(!$users->WINBINDD_INSTALLED){return "#--------- NTLM AUTH settings\n#squid_kerb_auth enabled\n";}
				$this->NTLM_AUTH=1;
			}
			
			
			if($this->NTLM_AUTH==0){return null;}
			
			if($this->ASROOT){
				if(is_file("/usr/bin/ntlm_auth")){$users->SQUID_NTLM_ENABLED=true;$users->SQUID_NTLM_AUTH="/usr/bin/ntlm_auth";}
				if(!is_file("/etc/squid3/net_ads_group.pl")){
					$unix=new unix();
					$php=$unix->LOCATE_PHP5_BIN();
					shell_exec("$php /usr/share/artica-postfix/exec.squid.netads.php >/dev/null 2>&1");
				}
			}
			
			if(!$users->SQUID_NTLM_ENABLED){$this->NTLM_AUTH=0;return "#No NTLM in compilation !\n";}
			if(trim($users->SQUID_NTLM_AUTH)==null){$this->NTLM_AUTH=0;return "#No NTLM auth_param basic program found !\n";}
			$array=unserialize(base64_decode($sock->GET_INFO("KerbAuthInfos")));
			if(!isset($array["LDAP_NONTLM_DOMAIN"])){$array["LDAP_NONTLM_DOMAIN"]=null;}
			if(!isset($array["WINDOWS_DNS_SUFFIX"])){$array["WINDOWS_DNS_SUFFIX"]=null;}
			
			$WINDOWS_DNS_SUFFIX=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
			$LDAP_NONTLM_DOMAIN=strtoupper(trim($array["LDAP_NONTLM_DOMAIN"]));
			
			if($WINDOWS_DNS_SUFFIX<>null){
					$basicDomain=" --domain=$WINDOWS_DNS_SUFFIX";
					$basicNTLMDomain=" --domain=$WINDOWS_DNS_SUFFIX";
			}
			if($LDAP_NONTLM_DOMAIN=="NONE"){$basicDomain=null;$LDAP_NONTLM_DOMAIN=null;}
			if($LDAP_NONTLM_DOMAIN<>null){$basicDomain=" --domain=$LDAP_NONTLM_DOMAIN";}
			
			$conf[]= "#--------- NTLM AUTH settings\n";
			$conf[]= "\n#Authentification mode, building using squid compiled for artica";
	
			$conf[]= "";
			$conf[]= "#NTLM authentication:";
			$conf[]= "auth_param ntlm program $users->SQUID_NTLM_AUTH$basicNTLMDomain --helper-protocol=squid-2.5-ntlmssp";
			$conf[]= "auth_param ntlm children 30";
			if(!$this->IS_32){
				$conf[]= "auth_param ntlm max_challenge_reuses 0";
				$conf[]= "auth_param ntlm max_challenge_lifetime 2 minutes";
			}
			$conf[]= "";
			$conf[]= "#Basic authentication for other browser that did not supports NTLM:";
			$conf[]= "auth_param basic program $users->SQUID_NTLM_AUTH --helper-protocol=squid-2.5-basic";
			$conf[]= "auth_param basic children 30";
			$conf[]= "auth_param basic realm $WINDOWS_DNS_SUFFIX";
			$conf[]= "auth_param basic credentialsttl 2 hours";
			$conf[]= "";
			$conf[]= "#Dynamic ACLs groups Enabled: [$UseDynamicGroupsAcls]";
			$conf[]= "external_acl_type ads_group ttl=0 %LOGIN /etc/squid3/net_ads_group.pl";
			$conf[]= "";
			$conf[]= "#Other settings";
			$conf[]= "authenticate_ttl 1 hour\n";
			$conf[]= "authenticate_cache_garbage_interval 10 seconds";
			$conf[]= "authenticate_ip_ttl 60 seconds\n";			
			$conf[]= "#--------- ACL AUTHENTICATED for authentication method";
			$conf[]= "acl AUTHENTICATED proxy_auth REQUIRED";
			//$conf[]= "client_persistent_connections off\n";
			$conf[]="";
			return implode("\n",$conf);
			if($this->ASROOT){
				$unix=new unix();
				$chmod=$unix->find_program("chmod");		
				$usermod=$unix->find_program("usermod");
				$chgrp=$unix->find_program("chgrp");
				
				$possibleDirs[]="/var/lib/samba/winbindd_privileged";
				$possibleDirs[]="/var/lib64/samba/winbindd_privileged";
				$possibleDirs[]="/var/run/samba/winbindd_privileged";			
				shell_exec("$usermod -G winbindd_priv squid >/dev/null 2>&1");
				
				while (list ($index, $dirs) = each ($possibleDirs) ){
					if(is_dir($dirs)){
						shell_exec("$chgrp winbindd_priv $dirs >/dev/null 2>&1");
						shell_exec("$chmod 0750 $dirs/ >/dev/null 2>&1");
					}
				}
			}
		}
			
			
	private function ISFiltersEnabled(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if($users->DANSGUARDIAN_INSTALLED){if($this->enable_dansguardian==1){return true;}}
		if($users->SQUIDGUARD_INSTALLED){if($this->enable_squidguard==1){return true;}}
	}
	
	private function url_rewrite_programs(){
		$redirect=array();
		if($this->enable_adzapper==1){
			if(is_file("/usr/bin/wrapzap")){$redirect[]="\"/usr/bin/wrapzap\"";}
		}		
		$streamcache=$this->zapchain_streamcache();
		$squidguard=$this->zapchain_squidguard();
		$ufdbguard=$this->zapchain_ufdbguard();
		$squidclamav=$this->zapchain_squidclamav();
		if($squidclamav<>null){$redirect[]="\"$squidclamav\"";}
		if($squidguard<>null){$redirect[]="\"$squidguard\"";}
		if($ufdbguard<>null){$redirect[]="\"$ufdbguard\"";}
		if($streamcache<>null){$redirect[]="\"$streamcache\"";}		
		return $redirect;
		
	}
	
	private function zapchain(){
		$redirect=$this->url_rewrite_programs();


		
		if($this->ASROOT){
			if(!is_file("/usr/bin/zapchain")){
				shell_exec("/bin/cp /usr/share/artica-postfix/bin/zapchain /usr/bin/zapchain");
				shell_exec("/bin/chmod 755 /usr/bin/zapchain");
			}
		}
		
		
		
		if($this->ASROOT){echo "Starting......: zapchain: ". count($redirect)." redirector(s)\n";}


		if(count($redirect)>1){
			$conf[]= "url_rewrite_program /usr/bin/zapchain ". implode(" ",$redirect);
			$conf[]= $this->url_rewrite_children();	
		}else{
			$conf[]= "url_rewrite_program ".implode("",$redirect);
			$conf[]= $this->url_rewrite_children();			
		}
		
		return @implode("\n",$conf);
	}
	
	private function zapchain_streamcache(){
		if($this->enable_streamcache==0){return;}
		if(!$this->ASROOT){return;}
		shell_exec("/bin/cp /usr/share/artica-postfix/streamcache.php /usr/sbin/streamcache.php");
		$unix=new unix();
		$chmod=$unix->find_program("chmod");
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 775 /usr/sbin/streamcache.php");
		return "/usr/sbin/streamcache.php";
		
		
	}
	
	private function ufdbguard_value($key){
		if(!is_file("/etc/squid3/ufdbGuard.conf")){return null;}
		if(isset($GLOBALS[__FUNCTION__][$key])){return $GLOBALS[__FUNCTION__][$key];}
		if(!isset($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
		if(!is_array($GLOBALS["UFDGUARDDATAFILE"])){$GLOBALS["UFDGUARDDATAFILE"]=file("/etc/squid3/ufdbGuard.conf");}
		while (list ($num, $ligne) = each ($GLOBALS["UFDGUARDDATAFILE"]) ){
			if(preg_match("#^$key\s+(.*)#", $ligne,$re)){
				$GLOBALS[__FUNCTION__][$key]=$re[1];
				return $re[1];}
		}
		
	}
	
	private function zapchain_ufdbguard(){
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->APP_UFDBGUARD_INSTALLED){return null;}
		
		
		if($this->enable_UfdbGuard==null){$this->enable_UfdbGuard=0;}
		if($this->enable_UfdbGuard==0){return null;}
		if(strlen($users->ufdbgclient_path)==0){return null;}
		
		
		
		$moinsC=null;
		if($this->ufdbguard_concurrency()){$moinsC="-C ";}
		$sock=new sockets();
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		
		if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
		if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
		if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
		if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}		
		
		if($this->EnableRemoteStatisticsAppliance==1){
			if($this->ASROOT){
				echo "Starting......: [UFDB]: Using remote appliance {$RemoteStatisticsApplianceSettings["SERVER"]}:{$datas["listen_port"]} as Web filtering engine\n";
			}
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
			$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
			$datas["UseRemoteUfdbguardService"]=1;
			$datas["remote_port"]=$datas["listen_port"];
		}		
		
		$binary=$users->ufdbgclient_path;
		$log="-l /var/log/squid";
 		
 		$unix=new unix();
		$major=$unix->UFDBGUARDD_MAJOR();
		$minor=$unix->UFDBGUARDD_MINOR();
		
		

		
		
		if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}			
		if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
		
		if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
		if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
		if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
		
		
		
		
		
		echo "Starting......: ufdbguardd: Major:$major, Minor:$minor use TCP socket:{$datas["tcpsockets"]}\n";
		if($datas["UseRemoteUfdbguardService"]==1){
			$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";	
			echo "Starting......: ufdbguardd: Use remote ufdbguard service: {$datas["remote_server"]}:{$datas["remote_port"]}\n";
			$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
			return "$binary $moinsC$address $log";
		}
		
		$effective_port=$this->ufdbguard_value("port");
		echo "Starting......: ufdbguardd: Effective port:`$effective_port`\n";
		if(is_numeric($effective_port)){$datas["tcpsockets"]=1;}
		
		
		if($major>0){
			if($minor>26){
				if($datas["tcpsockets"]==1){
					echo "Starting......: ufdbguardd: Use remote ufdbguard service: {$datas["listen_addr"]}:{$datas["remote_port"]}\n";
					$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";	
					$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
					return "$binary $moinsC$address $log";		
				}	
			}
		}
		echo "Starting......: ufdbguardd: Use remote ufdbguard service: $binary $moinsC$log\n";
		$GLOBALS[__FUNCTION__]="$binary $moinsC$log";
		return "$binary $moinsC$log";	
	}
	
	
	
	private function zapchain_squidclamav(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
		if(!$users->APP_SQUIDCLAMAV_INSTALLED){return null;}
		if($this->enable_squidclamav==null){$this->enable_squidclamav=0;}
		if($this->enable_squidclamav==0){return null;}
		return "$users->squidclamav_path /etc/squidclamav.conf";
	}	
	
	
	private function zapchain_squidguard(){
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){
			if($this->ASROOT){echo "Starting......: zapchain: squidGuard Installed: FALSE\n";}
			return null;
		}
		if($this->enable_squidguard==0){
			if($this->ASROOT){echo "Starting......: zapchain: squidGuard enabled: FALSE\n";}
			return null;
		}
		
		
		$sock=new sockets();
		if($users->C_ICAP_INSTALLED){
			if($this->enable_cicap==1){
				$EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
				if($EnableSquidGuardInCiCAP==null){$EnableSquidGuardInCiCAP=1;}
				if($EnableSquidGuardInCiCAP==1){$this->enable_squidguard=0;}
			}
		}

		if($this->ASROOT){echo "Starting......: zapchain: squidGuard in C-ICAP=$EnableSquidGuardInCiCAP\n";}
		if($this->ASROOT){echo "Starting......: zapchain: squidGuard Enabled=$this->enable_squidguard\n";}
		
		if($users->APP_UFDBGUARD_INSTALLED){if($this->enable_UfdbGuard==1){$this->enable_squidguard=0;}}
		if($this->enable_squidguard==0){return null;}
		$bin=trim($users->SQUIDGUARD_BIN_PATH);
		if($bin==null){
			$sock=new sockets();
			$bin=base64_decode($sock->getFrameWork("cmd.php?find-program=squidGard"));
			if(trim($bin)==null){
				if($this->ASROOT){echo "Starting......: Unable to activate squidGuard no path found\n";}
				return null;
			}
		}
		return "$bin -c /etc/squid/squidGuard.conf";
	}
	
	
	private function squidclamav(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if($this->enable_adzapper==1){
			if($users->ADZAPPER_INSTALLED){
				if($this->ASROOT){echo "Starting......: Activate AdZapper\n";}
				return $this->zapchain();
				}
			}
			
		$conf[]= "\n#--------- squidClamAv";
		$conf[]= "\n#IS C-ICAP enabled = $this->enable_cicap";
		$conf[]= "url_rewrite_program $users->squidclamav_path /etc/squidclamav.conf";
		$conf[]= $this->url_rewrite_children();
		if($this->ASROOT){echo "Starting......: Activate squidClamav \"$users->squidclamav_path\"\n";}
		return implode("\n",$conf);		
		}
		
	private function ufdbguard_concurrency(){
		return false;
		$sock=new sockets();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if($this->ASROOT){echo "Starting......: redirectors rewrite concurrency \"{$RedirectorsArray["url_rewrite_concurrency"]}\"\n";}
		if($RedirectorsArray["url_rewrite_concurrency"]>0){return true;}
		return false;
	}
		
	private function url_rewrite_children(){
		if(isset($GLOBALS["squimem_url_rewrite_children"])){return $GLOBALS["squimem_url_rewrite_children"];}
		$sock=new sockets();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!isset($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		if(!isset($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!isset($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_children"])){$RedirectorsArray["url_rewrite_children"]=20;}
		if(!is_numeric($RedirectorsArray["url_rewrite_startup"])){$RedirectorsArray["url_rewrite_startup"]=5;}
		if(!is_numeric($RedirectorsArray["url_rewrite_idle"])){$RedirectorsArray["url_rewrite_idle"]=1;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}	
		
		

		if($this->enable_UfdbGuard==1){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		
		$url_rewrite_children="url_rewrite_children {$RedirectorsArray["url_rewrite_children"]} startup={$RedirectorsArray["url_rewrite_startup"]} idle={$RedirectorsArray["url_rewrite_idle"]} concurrency={$RedirectorsArray["url_rewrite_concurrency"]}";
		$GLOBALS["squimem_url_rewrite_children"]=$url_rewrite_children;		
		}
	
	private function squidGuard(){
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		
		if($users->APP_SQUIDCLAMAV_INSTALLED){
			if($this->enable_squidclamav==1){
				return $this->squidclamav();
			}
		}
		
		if($this->enable_streamcache==1){
			$conf[]= "url_rewrite_program ".$this->zapchain_streamcache();	
			$conf[]= $this->url_rewrite_children();
			if($this->ASROOT){echo "Starting......: Activate StreamCache\n";}
			return implode("\n",$conf);
		}
		
		if(!$users->SQUIDGUARD_INSTALLED){if(!$users->APP_UFDBGUARD_INSTALLED){if(!$users->ADZAPPER_INSTALLED){return null;}}}
		
		$redirects=$this->url_rewrite_programs();
		if(count($redirects)>1){return $this->zapchain();}
				
		$sock=new sockets();
	
		$EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		if($EnableSquidGuardInCiCAP==null){$EnableSquidGuardInCiCAP=1;}
		if(!$users->C_ICAP_INSTALLED){$EnableSquidGuardInCiCAP=0;}
		if($this->enable_UfdbGuard==1){$this->enable_squidguard=0;$EnableSquidGuardInCiCAP=0;}
		
		
		
		if($EnableSquidGuardInCiCAP==1){
			if($this->enable_cicap==1){
				$conf[]= "\n#--------- squidGuard";
				$conf[]= "\n#transfered to C-ICAP -> EnableSquidGuardInCiCAP=1";
				return implode("\n",$conf);
			}
		}
		
		if($users->APP_UFDBGUARD_INSTALLED){
			if($this->enable_UfdbGuard<>1){
				$conf[]= "\n#--------- UfdbGuard";
				$conf[]= "\n#Disabled enable_UfdbGuard=$this->enable_UfdbGuard";
			}
			
			if($this->enable_UfdbGuard==1){
				$this->enable_dansguardian=0;
				$conf[]= "\n#--------- UfdbGuard";
				$zapchain_ufdbguard=$this->zapchain_ufdbguard();
				if($zapchain_ufdbguard<>null){						
					$conf[]= "url_rewrite_program $zapchain_ufdbguard";	
					$conf[]= $this->url_rewrite_children();
					if($this->ASROOT){echo "Starting......: ufdbguardd: \"$zapchain_ufdbguard\"\n";}
				}
				return implode("\n",$conf);	
				}
		}

			
		
		
		
		if($this->enable_squidguard<>1){
			$conf[]= "\n#--------- squidGuard";
			$conf[]= "\n#Disabled enable_squidguard= $this->enable_squidguard";
			return implode("\n",$conf);			
			return null;
		
		}
		$this->enable_dansguardian=0;
		$bin=trim($users->SQUIDGUARD_BIN_PATH);
		if($bin==null){
			$sock=new sockets();
			$bin=base64_decode($sock->getFrameWork("cmd.php?find-program=squidGard"));
			if(trim($bin)==null){
				if($this->ASROOT){echo "Starting......: Unable to activate squidGuard no path found\n";}
				return null;
			}
		}
		
		if(posix_getuid()==0){
			$array=$this->SquidGuardDatabasesStatus();
			if(is_array($array)){
				$conf[]= "\n#--------- squidGard";
				while (list ($num, $val) = each ($array) ){
					$conf[]="#not compiled $val";
					
				}
				if($this->ASROOT){echo "Starting......: disable squidGuard \"". count($array)."\" databases not compiled\n";}
				return implode("\n",$conf);
				
			}
		}
			$conf[]= "\n#--------- squidGard";
			$conf[]= "\n#IS C-ICAP enabled = $this->enable_cicap";
			$conf[]= "url_rewrite_program $users->SQUIDGUARD_BIN_PATH";
			$conf[]= $this->url_rewrite_children();
			if($this->ASROOT){echo "Starting......: Activate squidGuard \"$bin\"\n";}
			return implode("\n",$conf);
	}
	
	
	private function cache_peer(){
		if($this->EnableParentProxy==0){
			return "\n#--------- SQUID PARENTS (feature not enabled)\n";
		}
		
		$sql="SELECT * FROM squid_parents ORDER BY ID DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			return "\n#--------- SQUID PARENTS (ERROR $q->mysql_error)\n";
		}
		$f[]="";
		$f[]="";
		$f[]="#--------- SQUID PARENTS";
		$f[]="# using ssl ? http://hwoarang.silverarrow.org/2011/02/22/ssl-encryption-between-parent-and-child-squid-proxy/";
		
		if($this->prefer_direct==0){
			$f[]="prefer_direct off";
		}else{
			$f[]="prefer_direct on";
		}
		
		if($this->nonhierarchical_direct==0){
			$f[]="nonhierarchical_direct off";
		}else{
			$f[]="nonhierarchical_direct on";
		}		
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["servername"])==null){continue;}
			if(trim($ligne["server_port"])==null){continue;}
			if(trim($ligne["server_type"])==null){$ligne["server_type"]="parent";}
			if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
			if($ALREADY_CACHE_PEER[$ligne["servername"]]){continue;}
			$array=unserialize(base64_decode($ligne["options"]));
			
			if(!is_array($array)){
				if((!is_numeric($ligne["icp_port"]))  OR ($ligne["icp_port"]==0)){
						$array["no-query"]=null;
						$array["no-digest"]=null;
				}else{
					$array=array();
				}
				
			}
					
			while (list($num,$val)=each($array)){
				if(trim($num)==null){continue;}
				if(trim($val)<>null){$z[]="$num=$val";}
				else{$z[]=$num;}
			}
			$options=implode(" ",$z);
			unset($z);unset($array);
			$f[]="cache_peer {$ligne["servername"]} {$ligne["server_type"]} {$ligne["server_port"]} {$ligne["icp_port"]} $options";
			$ALREADY_CACHE_PEER[$ligne["servername"]]=true;
			}
		
		$f[]="never_direct allow all";	
		$f[]="";
		
		return implode("\n",$f);
		
	}
	
	
	private function x_forwarded_for(){
		if(!$this->ASROOT){return;}
		$enabled=false;
		$unix=new unix();
		$results=$this->_compile_parmz();
		while (list($num,$val)=each($results)){if(preg_match("#enable-follow-x-forwarded-for#", $val)){$enabled=true;}}
		if(!$enabled){if($GLOBALS["VERBOSE"]){echo "DEBUG : enable-follow-x-forwarded-for no such compilation option !\n";}return;}
		if($GLOBALS["VERBOSE"]){echo "DEBUG : enable-follow-x-forwarded-for OK\n";}
		$f[]="# --------- Use x-forwarded-for for load balancers";
		$follow_x_forwarded_for=$this->_follow_x_forwarded_for(); 
		if($follow_x_forwarded_for<>null){$f[]=$follow_x_forwarded_for;}
		$users=new usersMenus();
		if($users->DANSGUARDIAN_INSTALLED){
			$f[]="follow_x_forwarded_for allow localhost";
		}
		$f[]="acl_uses_indirect_client         on";
		$f[]="delay_pool_uses_indirect_client  on";
		$f[]="log_uses_indirect_client         on";
		return @implode("\n", $f)."\n";
	}

function buil_init_squid_tail(){
	$unix=new unix();
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$chmod=$unix->find_program("chmod");	
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/squid-tail";
	$conf[]="#";
	$conf[]="# squid-tail-sock Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          squid-tail";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     2 3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch squid-tail server";
	$conf[]="# Description:       Launch squid-tail server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    /etc/init.d/artica-postfix start squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    /etc/init.d/artica-postfix stop squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="     /etc/init.d/artica-postfix restart squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     /etc/init.d/artica-postfix restart squid-tail-sock \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";	
	@file_put_contents("/etc/init.d/squid-tail",@implode("\n",$conf));	
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	shell_exec("$chmod +x /etc/init.d/squid-tail >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f squid-tail defaults >/dev/null 2>&1");
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add squid-tail >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 squid-tail on >/dev/null 2>&1");
	}
}	
	
		
		
function BuildSquidConf(){
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$conf=null;
		$acl_access_time=null;
		$transparent=null;
		$second_port_transparent=null;
		$http_access_time=null;
		$acl_groups=array();
		$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
		if($SquidBinIpaddr<>null){$SquidBinIpaddr="$SquidBinIpaddr:";}
		$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$SquidBoosterOnly=$this->zcheck_squidbooster_value();
		if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
		
		
		
		if($SquidBoosterMem>0){
			$SquidBoosterMemK=$SquidBoosterMemK*1024;
			$this->store_dir_minsize="min-size=$SquidBoosterMemK";
		}
		
		if($this->EnableUserAgentBanAll==1){
			$conf=$conf. "acl AllowedBrowsers browser \"/etc/squid3/allowed-user-agents.acl\"\n";
		}
		
		$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
		$EnableOpenDNSInProxy=$sock->GET_INFO("EnableOpenDNSInProxy");
		if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
		
		
		if($this->ASROOT){
			echo "Starting......: Squid Version $this->SQUID_BIN_VERSION\n";
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: Squid : is 3.2.x OK\n";}}
		}
		if($this->IS_30){$conf=$conf . "# IS 3.0 YES\n";}
		if($this->IS_32){$conf=$conf . "# IS 3.2 YES\n";}
		if($this->IS_31){
			$conf=$conf . "# IS 3.1 YES\n";
			$this->store_dir_minsize=null;
		}  
		
		if($SquidActHasReverse<>1){
			if($this->SquidDisableAllFilters==0){
				$squidGuard=$this->squidGuard();
			}
		}
		
		if(!$this->IS_31){
			$conf=$conf . "#Squid version is under 3.1x or 3.2x version...\n";
			if($this->enable_kavproxy==1){
				if($this->enable_cicap==1){
					$conf=$conf . "#C-ICAP was removed, this squid version did not accept more that 1 ICAP server\n";
					$this->enable_cicap=0;
		}}}else{
			if($this->enable_kavproxy==1){$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;}
			if($this->enable_cicap==1){$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;}
		}
		
		
		
		$kav_acl="acl acl_kav_GET method GET\n";
		if($this->IS_27){
			$conf=$conf."acl all src 0.0.0.0/0\n";
		}
		$conf=$conf."acl localhost src 127.0.0.1/32\n";
		$conf=$conf."acl to_localhost dst 127.0.0.0/8 0.0.0.0/32\n";
		$conf=$conf."acl squidclient proto cache_object\n";
		$isp_networks=$this->isp_networks();
		if($isp_networks<>null){$conf=$conf."$isp_networks\n";}
		
		$kav_acl=$this->acls_multimedias();
		if($this->SquidDisableAllFilters==0){
			$acl_blocked_sites="acl blockedsites url_regex \"/etc/squid3/squid-block.acl\"\n";
		}
			
		if($this->hasProxyTransparent==1){$this->LDAP_AUTH=0;$this->NTLM_AUTH=0;}
		$squid_sessions_builder=$this->squid_sessions_builder();
		
		$conf=$conf . $this->ntlm_auth_conf();
		$conf=$conf . $this->ldap_auth_conf();
		$conf=$conf . $this->krb_auth_conf();
		if($squid_sessions_builder<>null){$conf=$conf . $squid_sessions_builder."\n";}
		$conf=$conf . "#--------- TWEEKS PERFORMANCES\n";
		$conf=$conf . "# http://blog.last.fm/2007/08/30/squid-optimization-guide\n";
		$conf=$conf . "memory_pools off\n";
		$conf=$conf . "quick_abort_min 0 KB\n";
		$conf=$conf . "quick_abort_max 0 KB\n";
		$conf=$conf . "log_icp_queries off\n";
		$conf=$conf . "client_db off\n";
		$conf=$conf . "buffered_logs on\n";
		$conf=$conf . "half_closed_clients off\n";

		
		
		if($this->SquidDisableAllFilters==0){
			$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"]="http_access deny blockedsites";
		}
		
		// UfdbGuard web filter section
		$conf=$conf.$squidGuard."\n";
		if(!$this->IS_27){
			if($this->url_rewrite_bypass==1){$conf=$conf."url_rewrite_bypass on\n";}else{$conf=$conf."url_rewrite_bypass off\n";}
		}
		
		
		$conf=$conf . $this->cache_peer();
		
		

		$conf=$conf  ."\n#--------- acls\n";
		if($SquidActHasReverse<>1){
			if($EnableMalwarePatrol==1){
				if($this->SquidDisableAllFilters==0){
					$conf=$conf  ."acl malware_block_list url_regex -i \"/etc/squid3/malwares.acl\"\n";
					$this->check_malwares_acl();
				}
			}
		}
		
		
		$conf=$conf  .$acl_blocked_sites;
		$conf=$conf. "acl CONNECT method CONNECT\n";
		$conf=$conf. "acl purge method PURGE\n";
		$conf=$conf. "acl FTP proto FTP\n";
		$ACLS_USERS_ASKS=$this->ACLS_USERS_ASKS();
		if($ACLS_USERS_ASKS<>null){$conf=$conf. $ACLS_USERS_ASKS."\n";}
		
		
		
		if(trim($users->SQUID_LDAP_AUTH)==1){
				if(is_array($this->acl_times)){
					$conf=$conf."#--------- TIME RESTRICTON\n";
					reset($this->acl_times);
					while (list ($num, $val) = each ($this->acl_times) ){
						if(!preg_match("#time:([0-9]+):(.+)#",$num,$re)){continue;}
							$re[2]=trim($re[2]);
							while (list ($a, $b) = each ($val)){
								$c[]="$a $b"; 
							}
							$conf=$conf."acl {$re[1]}_{$re[2]}_time time ". implode(" " ,$c)."\n";
							$http_access_time_array[]="http_access deny ldap_{$re[1]}_{$re[2]} !{$re[1]}_{$re[2]}_time";
							
							$acgroup="acl ldap_{$re[1]}_{$re[2]} external ldap_group {$re[1]}";
							$acl_groups[$acgroup]=$acgroup;
							
							}
							
					if(is_array($http_access_time_array)){
						$http_access_time=implode("\n",$http_access_time_array)."\n";
					}
				}
		}

		$conf=$conf . $kav_acl;
		$conf=$conf . "\n".$this->SQUID_LOCAL_NETWORKS()."\n";
		
	
		if($this->LDAP_AUTH==1){
			if($this->EnableKerbAuth==0){
				$conf=$conf ."# LDAP_AUTH =1  EnableKerbAuth = 0\n";
				$conf=$conf ."acl group_password external ldap_group\n";
			}
			
			if(count($acl_groups)>0){
				while (list ($a, $b) = each ($acl_groups)){
					$gprs[]=$b;
				}
				$conf=$conf ."\n#--------- GROUPS definition\n";
				$conf=$conf .implode("\n",$gprs);
			}else{
				$conf=$conf ."\n#--------- GROUPS definition\n";
				$conf=$conf ."#no groups";
			}
		}

		$conf=$conf."\n\n#--------- MAIN RULES...\n";

		$conf=$conf."always_direct allow FTP\n";		
		if($this->EnableParentProxy==1){
			$conf=$conf."\n#--------- RULES THAT FORCE TO PASS trough parent proxies except FTP\n";
			if($this->SSL_BUMP==0){
				$conf=$conf."always_direct deny all\n";
				$conf=$conf."prefer_direct off\n\n";
			}else{
				$conf=$conf."\n#--------- SSL bump is enabled always_direct is not set\n";
			}
		}		
		
		if($this->LDAP_AUTH==1){
				$acl_access_time=$http_access_time;
				if($this->EnableKerbAuth==0){
					$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"]="http_access allow group_password";
				}
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow ldapauth\n";
		}
		
		if($this->NTLM_AUTH==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow ntlm_users\n";
				@mkdir("/var/cache/samba/winbindd_privileged",0750,true);
				$office_network=null;
		}	

		if($this->EnableKerbAuth==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow AUTHENTICATED\n";
				$office_network=null;
		}

		if($this->EnableKerberosAuthentication==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow AUTHENTICATED\n";
				$office_network=null;			
		}
		
		
		if($SquidActHasReverse<>1){
			if($this->enable_dansguardian==1){
				$office_network=null;
			}
		}		
		

		if($this->hasProxyTransparent==1){
			$transparent=" transparent";
			if($this->IS_30){$transparent=" transparent";}
			if($this->IS_31){$transparent=" intercept";}
			if($this->IS_32){$transparent=" intercept";}
			if($this->UseTProxyMode==1){$transparent=" tproxy";}
			$this->second_listen_portForTransparent=$this->listen_port+1;
			if($this->second_listen_portForTransparent==$this->ssl_port){$second_port_transparent=$this->ssl_port+1;}
			$second_port_transparent="http_port $SquidBinIpaddr$this->second_listen_portForTransparent";
			if($this->ASROOT){echo "Starting......: Squid : Transparent: Adding a second port $this->second_listen_portForTransparent,transparent mode:$transparent\n";}
			
		}
		
		$listen_port=intval(trim($this->listen_port));
		if($this->enable_dansguardian==1){
			$listen_port=$this->alt_listen_port;	
		}
		
		$conf=$conf."# --------- SAFE ports\n";
		$conf=$conf.$this->SAFE_PORTS();
		

		$conf=$conf."#\n";

		if($EnableMalwarePatrol==1){
			if($this->SquidDisableAllFilters==0){
				$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"]="http_access deny malware_block_list";
			}
		}
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["request_header_max_size"],$re)){
				$request_header_max_size=$re[1];
				if(!is_numeric($request_header_max_size)){$request_header_max_size=0;}
				if($request_header_max_size>0){
					if($request_header_max_size<256){$request_header_max_size=256;}
					
				}
		}
		
	if(preg_match("#([0-9+])#",$this->global_conf_array["client_request_buffer_max_size"],$re)){
				$client_request_buffer_max_size=$re[1];
				if(!is_numeric($client_request_buffer_max_size)){$client_request_buffer_max_size=512;}
				if($client_request_buffer_max_size>0){
					if($client_request_buffer_max_size<512){$client_request_buffer_max_size=512;}
				}
		}		
		
		
		
		
		if($this->ASROOT){echo "Starting......: Squid request header max size {$this->global_conf_array["request_header_max_size"]} $request_header_max_size:KB\n";}			
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["reply_body_max_size"],$re)){
				$reply_body_max_size=$re[1];
				if($request_header_max_size==0){$reply_body_max_size=0;}
				if(!is_numeric($reply_body_max_size)){$reply_body_max_size=0;}
				if($reply_body_max_size<$request_header_max_size){$reply_body_max_size=$reply_body_max_size*2;}
			}
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["request_body_max_size"],$re)){
			$request_body_max_size=$re[1];
			if(!is_numeric($request_body_max_size)){$request_body_max_size=0;}			
		}
		if(preg_match("#([0-9+])#",$this->global_conf_array["reply_header_max_size"],$re)){
			$reply_header_max_size=$re[1];
			if(!is_numeric($reply_header_max_size)){$reply_header_max_size=64;}			
		}
		
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["maximum_object_size_in_memory"],$re)){
			$maximum_object_size_in_memory=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["maximum_object_size_in_memory"],$re)){$maximum_object_size_in_memory_unit=$re[1];}
			if($maximum_object_size_in_memory_unit==null){$maximum_object_size_in_memory_unit="KB";}
			if($maximum_object_size_in_memory_unit=="KB"){
				if($maximum_object_size_in_memory<512){$maximum_object_size_in_memory=1024;}
			}
		}
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["minimum_object_size"],$re)){
			$minimum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
			if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
			if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		}		
		
		
		
		if(preg_match("#([0-9+])#",$this->global_conf_array["maximum_object_size"],$re)){
			$maximum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["maximum_object_size"],$re)){$maximum_object_size_unit=$re[1];}
			if($maximum_object_size_unit==null){$maximum_object_size_unit="KB";}
			if($maximum_object_size_unit=="KB"){
				if($maximum_object_size<4096){$maximum_object_size=4096;}
			}
				if($maximum_object_size_unit=="MB"){
					if($maximum_object_size<4){$maximum_object_size=4;}
				}
		}		
		
		$this->CheckVersion();
		$conf=$conf. $this->SSL_PORTS();
		if($this->ignore_expect_100==1){$ignore_expect_100="on";}else{$ignore_expect_100="off";}
		
		$request_header_access=$this->request_header_access();
		$conf=$conf ."# --------- Change HTTP headers:\n";
		if($request_header_access<>null){$conf=$conf."$request_header_access\n";}

		$x_forwarded_for=$this->x_forwarded_for();
		if($x_forwarded_for<>null){$conf=$conf .$x_forwarded_for."\n";}
		
		$conf=$conf .$acl_access_time;
		
		$conf=$conf .$this->ACL_FTP_RESTRICTIONS();
		$conf=$conf. $this->WHITELISTED_AUTH();
		$conf=$conf .$this->ACL_WHITE_COMPUTERS_IP();
		if($this->ACL_ARP_ENABLED){
			$conf=$conf ."\nacl whitelisted_mac_computers arp \"/etc/squid3/whitelisted-computers-by-mac.acl\n";
		}
		$conf=$conf ."\n".$this->ACL_WHITELISTED_MIMETYPES()."\n";
		if($this->SquidDisableAllFilters==0){
			$conf=$conf .$this->ACL_BANNED_COMPUTERS_IP();
			$conf=$conf .$this->ACL_MESSENGERS();
		}
		if($this->ASROOT){
			$GLOBALS["aclGen"]=new squid_acls();
			$GLOBALS["aclGen"]->Build_Acls();
			echo "Starting......: Squid : ".count($GLOBALS["aclGen"]->acls_array)." ACLs\n";
			if(count($GLOBALS["aclGen"]->acls_array)>0){
				$ACLS_TO_ADD=@implode("\n",$GLOBALS["aclGen"]->acls_array);
				if($GLOBALS["VERBOSE"]){echo "......................\nACLS to add in squid from Acls engine \n$ACLS_TO_ADD\n......................\n";}
				$conf=$conf ."\n$ACLS_TO_ADD\n";
			}
		}
		
		$conf=$conf ."\n\n";
		if($this->SquidDisableAllFilters==0){
			$bandwith=new squid_bandwith_builder();
			$conf=$conf. $bandwith->compile()."\n";
		}
		
		$conf=$conf."\n# ---------  RULES DEFINITIONS\n";
		$conf=$conf."\nhttp_access allow purge localhost\n";
		$conf=$conf.$this->http_access();
		if($this->SquidDisableAllFilters==0){
			$icap=new icap();
			$conf=$conf."# --------- ICAP Services.($icap->ICAP_SERVICES_COUNT service(s))\n";
			$conf=$conf.$icap->icap_final_string;
			
			$ecap=new ecap();
			$conf=$conf."# --------- eCAP Services\n";
			$conf=$conf.$ecap->build();
			
		}
		$conf=$conf."\n";
		$conf=$conf.$this->WCCP();
		$conf=$conf."# --------- ident_lookup_access\n";
		$conf=$conf."hierarchy_stoplist cgi-bin ?\n";
		$conf=$conf."\n";
		$conf=$conf."# --------- General settings \n";
		$conf=$conf."visible_hostname $this->visible_hostname\n";
		if(($this->IS_30) && (!$this->IS_32)){
			$conf=$conf."ignore_expect_100 $ignore_expect_100\n";
		}
		
		if(!preg_match("#^[0-9]+\s+[a-z]+#", $this->global_conf_array["connect_timeout"])){$this->global_conf_array["connect_timeout"]="60 seconds";}
		
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."# --------- time-out \n";
		$conf=$conf."dead_peer_timeout {$this->global_conf_array["dead_peer_timeout"]}\n";
		$conf=$conf."dns_timeout {$this->global_conf_array["dns_timeout"]}\n";
		$conf=$conf."connect_timeout {$this->global_conf_array["connect_timeout"]}\n";
		$conf=$conf."persistent_request_timeout 3 minutes\n";
		$conf=$conf."pconn_timeout 1600 seconds\n"; 		
		$conf=$conf."\n";
		$conf=$conf."\n";
		if($this->EnableChangeRequestSize==1){
			$conf=$conf."# --------- Objects limits \n";
			$conf=$conf."request_body_max_size $request_body_max_size KB\n";
			$conf=$conf."reply_body_max_size $reply_body_max_size KB\n";
			$conf=$conf."request_header_max_size $request_header_max_size KB\n";
			$conf=$conf."reply_header_max_size $reply_header_max_size KB\n";
			$conf=$conf."client_request_buffer_max_size $client_request_buffer_max_size KB\n";
			
		}
		
		$conf=$conf."maximum_object_size {$this->global_conf_array["maximum_object_size"]}\n";
		$conf=$conf."minimum_object_size $minimum_object_size $minimum_object_size_unit\n";
		$conf=$conf."maximum_object_size_in_memory $maximum_object_size_in_memory $maximum_object_size_in_memory_unit\n";

		
		$conf=$conf."\n";
		$conf=$conf."\n";
		
		if($SquidActHasReverse==1){
			$conf=$conf."\n# -> Reverse Proxy is enabled <-\n";
			$transparent=" accel vhost";
			$listen_port=intval(trim($sock->GET_INFO("SquidActHasReverseListenPort")));
			if($listen_port<3){$listen_port=80;}
			$conf=$conf.$this->squid_reverse_websites();
		}
		
		$http_port_sslbump=$this->ssl_bump_port();
		
		
		$conf=$conf."#http/https ports\n";
		$conf=$conf."http_port $SquidBinIpaddr$listen_port$transparent$http_port_sslbump\n";
		if($second_port_transparent<>null){$conf=$conf."$second_port_transparent$http_port_sslbump\n";}
		
		if($this->second_listen_port>0){
			$conf=$conf."http_port $SquidBinIpaddr$this->second_listen_port$http_port_sslbump\n";
		}
		
		
		
		if($this->ICP_PORT>0){
			$conf=$conf."icp_port $this->ICP_PORT\n";
		}
		
		$conf=$conf.$this->ssl_bump_tranparent($listen_port);
		$conf=$conf.$this->https_port();
		
		if(!isset($this->global_conf_array["tcp_outgoing_address"])){$this->global_conf_array["tcp_outgoing_address"]=null;}
		
		
		if(trim($this->global_conf_array["tcp_outgoing_address"])<>null){
			$conf=$conf."tcp_outgoing_address {$this->global_conf_array["tcp_outgoing_address"]}\n";	
		}
		
		$conf=$conf."\n";
		$conf=$conf ."# --------- SSL Rules \n";
		$conf=$conf .$this->ssl_bump_access();
		$conf=$conf."\n";
		
		$conf=$conf ."# --------- Caches \n\n";
		$cache_deny_array=$this->cache_deny_array();
		if(count($cache_deny_array)>0){
			$conf=$conf ."# --------- Do not cache these ". count($cache_deny_array)." website(s)\n";
			$conf=$conf .@implode("\n", $cache_deny_array)."\n";
			$conf=$conf ."cache deny DoNotCache\n"; 
			
		}
		
		$acls=new squid_acls_groups();
		$acls_deny=$acls->buildacls_bytype("cache_deny");
		if(count($acls_deny)>0){
			while (list ($index, $line) = each ($acls_deny) ){$conf=$conf ."cache deny $line\n";}
		}		
		
		
		
		$conf=$conf . "cache_effective_user squid\n";
		$conf=$conf ."#cache_replacement_policy heap LFUDA\n";
		$conf=$conf .$this->CacheManager();
		
		$conf=$conf ."cache_mem {$this->global_conf_array["cache_mem"]}\n";
		$conf=$conf ."cache_swap_high {$this->global_conf_array["cache_swap_high"]}\n";
		$conf=$conf ."cache_swap_low {$this->global_conf_array["cache_swap_low"]}\n";
	
		$conf=$conf."# --------- DNS and ip caches \n";
		$conf=$conf ."ipcache_size {$this->global_conf_array["ipcache_size"]}\n";
		$conf=$conf ."ipcache_low {$this->global_conf_array["ipcache_low"]}\n";		
		$conf=$conf ."ipcache_high {$this->global_conf_array["ipcache_high"]}\n";				
		$conf=$conf ."fqdncache_size {$this->global_conf_array["fqdncache_size"]}\n";
		$conf=$conf ."positive_dns_ttl {$this->global_conf_array["positive_dns_ttl"]}\n";
		$conf=$conf ."negative_dns_ttl {$this->global_conf_array["negative_dns_ttl"]}\n";
		$conf=$conf."# Personal settings \n";
		$conf=$conf."# To add your own tokens, just create a file under /etc/squid3/squid-me.conf,\n";
		$conf=$conf."# it will be merged here \n";
		if(is_file("/etc/squid3/squid-me.conf")){$conf=$conf."\n". @file_get_contents("/etc/squid3/squid-me.conf")."\n";}
		
		

		writelogs("dns server: ".count($this->dns_array) . ' rows',__CLASS__.'/'.__FUNCTION__,__FILE__);
		writelogs("OpenDNS: $EnableOpenDNSInProxy",__CLASS__.'/'.__FUNCTION__,__FILE__);
		
		if($EnableOpenDNSInProxy==1){
			$conf=$conf ."\n\n# --------- OpenDNS DNS SERVERS \n";
			$arrayOpenDNS=unserialize(base64_decode($sock->GET_INFO("OpenDNSConfig")));
			if($arrayOpenDNS["OpenDNS1"]==null){$arrayOpenDNS["OpenDNS1"]="208.67.222.222";}
			if($arrayOpenDNS["OpenDNS2"]==null){$arrayOpenDNS["OpenDNS2"]="208.67.220.220";}
			writelogs("OpenDNS: {$arrayOpenDNS["OpenDNS2"]},{$arrayOpenDNS["OpenDNS2"]} ",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$conf=$conf . "dns_nameservers " . $arrayOpenDNS["OpenDNS1"]."\n";
			$conf=$conf . "dns_nameservers " . $arrayOpenDNS["OpenDNS2"]."\n";
			
		}else{
			if(is_array($this->dns_array)){
				reset($this->dns_array);
				$conf=$conf ."\n\n# --------- SPECIFIC DNS SERVERS \n";
				
				while (list ($num, $val) = each ($this->dns_array) ){
					
					if(preg_match("#resolv#", $val)){
						$dns_nameservers_from_resolv=$this->dns_nameservers_from_resolv();
						if($dns_nameservers_from_resolv<>null){$conf=$conf .$dns_nameservers_from_resolv."\n";}
						continue;
					}
					$conf=$conf . "dns_nameservers " . $val . "\n";
					
				}
			}
		}

		$conf=$conf.$this->ftp_parameters();
		$conf=$conf."debug_options ALL,1\n";
		//$conf=$conf."cache deny QUERY\n";
		$conf=$conf."refresh_pattern ^ftp:		1440	20%	10080\n";
		$conf=$conf."refresh_pattern ^gopher:	1440	0%	1440\n";
		$conf=$conf.$this->refresh_pattern_list();
		$conf=$conf."refresh_pattern .		   0	20%	4320\n";
		$conf=$conf ."refresh_pattern -i (/cg-bin/|\?) 0 0% 0\n";
		
		if($this->EnableICPPort==1){$conf=$conf."icp_port 3130\n";}
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."#Logs-------------------------------------------------\n";
		$conf=$conf."#We want to log the full URI for Youtube categorization.\n";
		$conf=$conf."strip_query_terms no\n";
		//$conf=$conf."emulate_httpd_log on\n";
		
		if(($this->IS_30) && (!$this->IS_32)){
			if($this->ISFiltersEnabled()){
				$conf=$conf."#fqdn is disabled to provide IP addresses to filters\n";
				$conf=$conf."log_fqdn off\n";
			}else{
				$conf=$conf."#fqdn is disabled For sarg.\n";
				$conf=$conf."log_fqdn off\n";
			}
		}
		
		
		$conf=$conf."coredump_dir	/var/squid/cache\n";
		$ExternalSyslog=$this->syslog_remote();	
		$conf=$conf."cache_log	/var/log/squid/cache.log\n";
		$conf=$conf."pid_filename	/var/run/squid.pid\n";
		$compile_params=$this->compilation_params();
		$pinger=$this->pinger();
		if($pinger<>null){$conf=$conf.$pinger;}
		
		if(isset($compile_params["enable-icmp"])){
			$conf=$conf."netdb_filename stdio:/var/log/squid/netdb.state\n";
		}else{
			if($this->ASROOT){echo "Starting......: Squid Main cache enable-icmp not compiled, skip it\n";}
		}
		//$conf=$conf."error_directory /usr/share/squid-langpack/en\n";
		$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
		if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
		
		if(($this->IS_30) && (!$this->IS_32)){
			$conf=$conf."cache_store_log	/var/log/squid/store.log\n";
			$conf=$conf."access_log syslog:authpriv.info common !squidclient\n";
			if($EnableSargGenerator==1){$conf=$conf."access_log /var/log/squid/sarg.log squid !squidclient\n";}
			if($ExternalSyslog<>null){$conf=$conf.$ExternalSyslog;}
		}
		
		if(($this->IS_31) && (!$this->IS_32)){
			$csvlog=$this->access_log_csv();
			if($csvlog<>null){$conf=$conf."$csvlog\n";}
			$conf=$conf."cache_store_log	/var/log/squid/store.log\n";
			$conf=$conf."access_log syslog:authpriv.info  common !squidclient\n";
			if($EnableSargGenerator==1){$conf=$conf."access_log /var/log/squid/sarg.log squid !squidclient\n";}
			if($ExternalSyslog<>null){$conf=$conf.$ExternalSyslog;}
		}	

		if($this->IS_27){
			$conf=$conf."cache_store_log	/var/log/squid/store.log\n";
			$conf=$conf."access_log syslog:authpriv.info  common\n";
			$conf=$conf."access_log /var/log/squid/sarg.log squid\n";
			if($ExternalSyslog<>null){$conf=$conf.$ExternalSyslog;}
		}		
		
		if($this->IS_32){
			$csvlog=$this->access_log_csv();
			if($csvlog<>null){$conf=$conf."$csvlog\n";}
			$conf=$conf."logformat common MAC:%>eui %>a %[ui %[un [%tl] \"%rm %ru HTTP/%rv\" %>Hs %<st %Ss:%Sh UserAgent:\"%{User-Agent}>h\" Forwarded:\"%{X-Forwarded-For}>h\"\n"; 
			$conf=$conf."cache_store_log	stdio:/var/log/squid/store.log\n";
			$conf=$conf."access_log syslog:authpriv.info common !squidclient\n";
			if($EnableSargGenerator==1){$conf=$conf."access_log stdio:/var/log/squid/sarg.log squid !squidclient\n";}
			if($ExternalSyslog<>null){$conf=$conf.$ExternalSyslog;}
		}
		
		
		if($this->SquidDisableAllFilters==0){
			if($this->enable_cicap==1){
				$conf=$conf."icap_log /var/log/squid/icap_access.log\n";
			}
		}
		
		
		if($this->ASROOT){echo "Starting......: Squid Main cache \"{$this->CACHE_PATH}\" ({$this->CACHE_TYPE})\n";}
		$conf=$conf."\n";
		
		$RockStore=$this->RockStore();
		$cache_booster=$this->cache_booster();
		$maincachedir="cache_dir	{$this->CACHE_TYPE} {$this->CACHE_PATH} {$this->CACHE_SIZE} 16 256 $this->store_dir_minsize\n";
		if($cache_booster<>null){
			$conf=$conf."$cache_booster\n";
			if($this->SquidBoosterOnly==1){$maincachedir=null;}
		}
		
		if($this->IS_31){$this->store_dir_minsize=null;}
		
		if($maincachedir<>null){
			if($RockStore<>null){$conf=$conf.$RockStore."\n";}
			$conf=$conf.$maincachedir;
			$conf=$conf."# --------- OTHER CACHES\n";
			if(is_array($this->cache_list)){
				reset($this->cache_list);
				while (list ($num, $val) = each ($this->cache_list)){
					if($this->ASROOT){echo "Starting......: other cache \"{$num}\" ({$this->cache_list[$num]["cache_type"]})\n";}
					if($this->cache_list[$num]["cache_type"]=="rock"){
						$mxsize=$this->cache_list[$num]["cache_maxsize"]*1000;
						$mxsize=$mxsize*1024;
						//$conf=$conf."cache_dir {$this->cache_list[$num]["cache_type"]} $num {$this->cache_list[$num]["cache_size"]} max-size=32768\n";
						continue;
					}
					
					$conf=$conf."cache_dir {$this->cache_list[$num]["cache_type"]} $num {$this->cache_list[$num]["cache_size"]} {$this->cache_list[$num]["cache_dir_level1"]} {$this->cache_list[$num]["cache_dir_level2"]} $this->store_dir_minsize \n";
				}
			}
		}
		
		$conf=$conf."\n";
		$conf=str_replace("\n\n", "\n", $conf);
		return $conf;	
	}
	
	
	private function access_log_csv(){
		if($this->EnableSquidCSV==0){return;}
		if($this->ASROOT){echo "Starting......: Squid create logs in csv enabed\n";}
		$sock=new sockets();
		$SquidCsvParams=unserialize(base64_decode($sock->GET_INFO("SquidCsvParams")));
		
		
		$CSV["a1"]="\"%>a\"";
		$CSV["a2"]="\"%>A\"";
		$CSV["a3"]="\"%>eui\"";
		$CSV["a4"]="\"%<a\"";
		$CSV["a5"]="\"%<A\"";
		$CSV["a6"]="\"%[un\"";
		$CSV["a7"]="\"%rm\"";
		$CSV["a8"]="\"%ru\"";
		$CSV["a9"]="\"%rv\"";
		$CSV["a10"]="\"%>Hs\"";
		$CSV["a11"]="\"%<st\"";
		$CSV["a12"]="\"%Ss:%Sh\"";
		$CSV["a13"]="\"%{User-Agent}>h\"";
		$CSV["a14"]="\"%{X-Forwarded-For}>h\"";
		
		if(($this->IS_31) && (!$this->IS_32)){
			unset($CSV["a4"]);
			unset($CSV["a3"]);
			
		}
		
		if( (count($SquidCsvParams)==0) OR !is_array($SquidCsvParams)){while (list ($code, $explain) = each ($CSV) ){$SquidCsvParams[$code]=1;}reset($CSV);}
	
		$access[]="\"%{%Y-%m-%d}tl\"";
		$access[]="\"%{%H:%M:%S}tl\"";
		
		while (list ($code, $value) = each ($CSV) ){
			if($SquidCsvParams[$code]==1){
				$ll[]=$code;
				$access[]=$value;
			}
		}
		//$conf[]="logformat csv \"%tl\",\"%>eui\",\"%>a\",\"%>A\",\"%[ui\",\"%[un\",\"[%tl]\",\"%rm %ru HTTP/%rv\" %>Hs %<st %Ss:%Sh UserAgent:\"%{User-Agent}>h\" Forwarded:\"%{X-Forwarded-For}>h\"\n";
		if($this->ASROOT){echo "Starting......: Squid enabled CSV fields:".@implode(", ",$ll)."\n";}
		$conf[]="logformat csv ".@implode(",", $access);
		if(($this->IS_31) && (!$this->IS_32)){
			$conf[]="access_log /var/log/squid/access.csv csv !squidclient\n";
		}
		
		if($this->IS_32){
			$conf[]="access_log stdio:/var/log/squid/access.csv csv !squidclient\n";
		}
		return @implode("\n", $conf);
	}
	
	
	private function syslog_remote(){
		$sock=new sockets();
		if(!$this->ASROOT){return;}
		$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
		if(!isset($array["ENABLE"])){
			echo "Starting......: Squid remote syslog is disabled\n";
			return;}
		if(!is_numeric($array["ENABLE"])){$array["ENABLE"]==0;}
		if($array["ENABLE"]==0){
			echo "Starting......: Squid remote syslog is disabled\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");}
			return;
		}
		if($array["SERVER"]==null){
			echo "Starting......: Squid remote syslog is disabled (no server set)\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");}
			return;
		}
		$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		echo "Starting......: Squid remote syslog exec.syslog-engine.php --squidsys\n";
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --squidsys >/dev/null 2>&1 &");
		return "access_log syslog:local6.info common\n";
	}
	
	
	private function isp_networks(){
		$sock=new sockets();
		$SquidEnableISPMode=$sock->GET_INFO("SquidEnableISPMode");
		if(!is_numeric($SquidEnableISPMode)){return null;}
		if($SquidEnableISPMode==0){return null;}
		$q=new mysql_squid_builder();
		$sql="SELECT publicip FROM usersisp WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$f[]=$ligne["publicip"];
		}
		if(count($f)>0){
			@file_put_contents("/etc/squid3/isp_acls_src",@implode("\n", $f));
			$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"]="http_access allow ISP_CLIENTS";
			return "acl ISP_CLIENTS src \"/etc/squid3/isp_acls_src\"";
		
		}
		
	}
	
	
	
	private function cache_booster(){
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
		if($SquidBoosterMem<1){
			$this->cache_booster_umount();
			return null;
		}
		
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: Cache Booster:: Need to boost for {$SquidBoosterMem}M, current {$mountedram}M\n";
		if($SquidBoosterMem<>$mountedram){
			$this->cache_booster_umount();
			$this->cache_booster_mount($SquidBoosterMem);
		}
		
		$mountedram=$this->cache_booster_ram();
		echo "Starting......: Cache Booster:: current {$mountedram}M\n";
		if($mountedram==0){return null;}
		$SquidBoosterMemK=$SquidBoosterMemK*1024;
		$maxsize=" max-size=$SquidBoosterMemK";
		
		
		return "cache_dir aufs /var/squid/cache_booster $mountedram 16 256$maxsize";
		if($this->IS_32){
			return "cache_dir rock /var/squid/cache_booster $mountedram max-size=$SquidBoosterMemK";
		}else{
			return "cache_dir diskd /var/squid/cache_booster $mountedram 16 256 max-size=$SquidBoosterMemK";
		}
		
	
	}
	
	private function check_malwares_acl(){
		$f=file("/etc/squid3/malwares.acl");
		echo "Starting......: ACL malwares ". count($f)." rules..\n";
		$rebuild=false;
		while (list ($num, $val) = each ($f)){
			$val=str_replace("\r", "", $val);
			$val=str_replace("\n", "", $val);
			$val=trim($val);
			if($val==null){continue;$rebuild=true;}
			if(preg_match("#try\{#", $val)){
				echo "Starting......: ACL malwares skipping line $num\n";
				$rebuild=true;
				continue;
			}
			$t[]=$val;
		}
		if($rebuild){
			echo "Starting......: ACL malwares saving ". count($t)." rules..\n";
			@file_put_contents("/etc/squid3/malwares.acl", @implode("\n", $t));
		}
		
	}
	
	
	private function cache_booster_umount(){
		$unix=new unix();
		$umount=$unix->find_program("umount");
		echo "Starting......: Cache Booster:: dismount /var/squid/cache_booster\n";
		shell_exec("$umount -l /var/squid/cache_booster >/dev/null 2>&1");
		
	}
	
	private function cache_booster_mount($RAM){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		@mkdir("/var/squid/cache_booster",0755,true);
		@chown("/var/squid/cache_booster", "squid");
		@chgrp("/var/squid/cache_booster", "squid");
		shell_exec("$mount -t tmpfs -o size={$RAM}M tmpfs /var/squid/cache_booster >/dev/null 2>&1");
		
	}	
	
	private function cache_booster_ram(){
		$f=file("/proc/mounts");
		while (list ($num, $val) = each ($f)){
			if(preg_match("#^tmpfs.+?\/var\/squid\/cache_booster.+?size=([0-9]+)([a-zA-Z]+)#", $val,$re)){
				$size=$re[1];
				$unit=strtolower($re[2]);
				if($unit=="k"){return round($size/1024);}
				if($unit=="m"){return $size;}
				
			}
		}
		
		return 0;
		
	}
	
	
	public function squid_sessions_builder(){
		return null;
		$sock=new sockets();
		$users=new usersMenus();
		$SquidEnableSessionEngine=$sock->GET_INFO("SquidEnableSessionEngine");
		$SquidSessionEngineTimeOut=$sock->GET_INFO("SquidSessionEngineTimeOut");
		$SquidSessionEngineExternalUrl=$sock->GET_INFO("SquidSessionEngineExternalUrl");
		if(!is_numeric($SquidEnableSessionEngine)){$SquidEnableSessionEngine=0;}
		if(!is_numeric($SquidSessionEngineTimeOut)){$SquidSessionEngineTimeOut=3600;}
		if($SquidEnableSessionEngine==0){return null;}
		$f[]="external_acl_type ArticaAclSessions concurrency=100 ttl=3 %SRC %URI %>{Host} %>{Cookie} %LOGIN $users->PHP_BIN_PATH /usr/share/artica-postfix/squid-helper.php";
		$f[]="acl ArticaAclAuthenticated external ArticaAclSessions LOGIN";
		$f[]="deny_info $SquidSessionEngineExternalUrl ArticaAclAuthenticated";
		$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"]="http_access deny !ArticaAclAuthenticated";
		return @implode("\n",$f);
		
	}
	
	
	
	
		
		public function dns_nameservers_from_resolv(){
			if(!$this->ASROOT){return;}
			$s=array();
			$f=file("/etc/resolv.conf");
			while (list ($num, $val) = each ($f)){
				if(preg_match("#nameserver\s+(.+)#", $val)){
					$s[]="dns_nameservers ".trim($re[1]);
				}
			}
			
			if(count($s)>0){return @implode("\n", $s);}
			
		}
		
		private function CacheManager(){
			$sock=new sockets();
			$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
			$cachemgr_passwd=$sock->GET_INFO("cachemgr_passwd");
			if($cache_mgr_user<>null){
				if($this->ASROOT){echo "Starting......: Cache Manager set has $cache_mgr_user\n";} 
				$conf[]="";
				$conf[]="#Cache Manager Authentication";
				$conf[]="cache_mgr $cache_mgr_user";
				$conf[]="cachemgr_passwd $cachemgr_passwd all";
				$conf[]="";
			}else{
				if($this->ASROOT){echo "Starting......: Cache Manager is not set\n";} 
			}
			
		}
		
		private function ACL_MANAGER(){
			if($this->IS_32){return;}
			return "acl manager proto cache_object";
			
		}
		
		private function http_access(){
			if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
			if($this->enable_dansguardian==1){$this->allow_squid_localhost=1;}
			$this->allow_squid_localhost=1;
			$ACL_MANAGER=$this->ACL_MANAGER();
			if($ACL_MANAGER<>null){$conf[]=$ACL_MANAGER;}
			if($this->ACL_ARP_ENABLED){
				$conf[]="http_access allow whitelisted_mac_computers";
				$conf[]="url_rewrite_access deny whitelisted_mac_computers";
				
			}
			
			
			$conf[]="http_access allow squidclient manager";
			$acls=new squid_acls_groups();
			
			$tcp_outgoing_tos=$acls->buildacls_bytype("tcp_outgoing_tos");
			if(count($tcp_outgoing_tos)>0){
				echo "Starting......: Squid : ACL Engine tcp_outgoing_tos ".count($tcp_outgoing_tos)." rules..\n";
				while (list ($index, $line) = each ($tcp_outgoing_tos) ){$conf[]="tcp_outgoing_tos $line";}
			}else{
				echo "Starting......: Squid : ACL Engine tcp_outgoing_tos No rules..\n";
			}	

			
			$deny_access_except=$acls->buildacls_bytype("deny_access_except");
			if(count($deny_access_except)>0){
				echo "Starting......: Squid : ACL Engine deny_access_except ".count($deny_access_except)." rules..\n";
				while (list ($index, $line) = each ($deny_access_except) ){
					$conf[]="http_access deny $line";
				}
			}else{
				echo "Starting......: Squid : ACL Engine deny_access_except No rules..\n";
			}				
			
			
			
			
			$acls_allows=$acls->buildacls_bytype("access_allow");
			if(count($acls_allows)>0){
				echo "Starting......: Squid : ACL Engine access_allow ".count($deny_access_except)." rules..\n";
				while (list ($index, $line) = each ($acls_allows) ){
					$conf[]="http_access allow $line";
				}
			}else{
				echo "Starting......: Squid : ACL Engine access_allow No rules..\n";
			}
			
			
			$acls_deny=$acls->buildacls_bytype("access_deny");
			if(count($acls_deny)>0){
				echo "Starting......: Squid : ACL Engine access_deny ".count($deny_access_except)." rules..\n";
				while (list ($index, $line) = each ($acls_deny) ){
					$conf[]="http_access deny $line";
				}
			}else{
				echo "Starting......: Squid : ACL Engine access_deny No rules..\n";
			}
			
			
			
			if(!isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])){$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]=array();}
			//if($this->allow_squid_localhost<>1){$conf[]="http_access deny to_localhost";}else{$conf[]="http_access allow to_localhost";}
			$conf[]="http_access allow to_localhost";
			$conf[]="url_rewrite_access deny localhost";
			if(count($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])>0){
				$conf[]=implode("\n",$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]);
			}
			if(isset($GLOBALS["url_rewrite_access_users"])){
				if(count($GLOBALS["url_rewrite_access_users"])>0){
					$conf[]=implode("\n",$GLOBALS["url_rewrite_access_users"]);
				}
			}
			
			
			$conf[]="url_rewrite_access deny squidclient";
			$conf[]="url_rewrite_access allow all";
			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_MAC"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MSN_MESSENGERS"];}
			
			
			
			
			if($this->enable_ftp_restrictions){
				$conf[]="http_access allow FTP clients_ftp";
				$conf[]="http_access allow CONNECT clients_ftp";
			}	

			
			if($this->IS_27){
				
			}

			$conf[]="http_access deny !Safe_ports";
			$conf[]="http_access deny CONNECT !SSL_ports";
			$conf[]="http_access allow localhost";
			
			$conf[]="http_access allow purge localhost";
			$conf[]="http_access deny purge";			
			if($this->EnableUserAgentBanAll==1){$conf[]="http_access deny !AllowedBrowsers";}		
			if(isset($GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"];}	
			if(isset($GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"];}			
			
			
			if($this->ASROOT){
				$acls_rules=$GLOBALS["aclGen"]->build_http_access();
				echo "Starting......: Squid : ACL ". count($acls_rules)." rule(s) from engine\n";}
				if(count($acls_rules)>0){$conf[]=@implode("\n", $acls_rules);
			}
				
			if(isset($GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"];}
			$conf[]="http_access deny all";
			while (list ($num, $val) = each ($conf) ){if(trim($val)==null){continue;}$f[]=$val;}
			$final="\n".@implode("\n",$f)."\n";
			$GLOBALS[__FUNCTION__]=$final;
			return $final;
		}
		
		
		private function https_port(){
			if($this->SSL_BUMP==1){return;}
			if($this->ssl_port==0){return;}
			
			if($this->hasProxyTransparent==1){
				$this->SSL_BUMP=1;
				return $this->ssl_bump_tranparent($this->ssl_port);
			}
			$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
			if($SquidBinIpaddr<>null){$SquidBinIpaddr="$SquidBinIpaddr:";}
			return "https_port $SquidBinIpaddr$this->ssl_port";
			
		}
		
		
		private function ssl_bump_port(){
			$addedlog=null;
			if(!is_numeric($this->intvalVersion)){$this->CheckVersion();}
			if($this->IS_32){$addedlog=";is 3.2x:yes";}
			if($this->IS_31){$addedlog=$addedlog.";is 3.1x:yes";}
			if($this->IS_27){$addedlog=$addedlog.";is 2.7x:yes";}
			
			if($this->ASROOT){echo "Starting......: Squid Check *** SSL BUMP - [$this->intvalVersion$addedlog] - ***\n";}
			
			if($this->IS_27){
				if($this->ASROOT){echo "Starting......: SSLBUMP:: Not supported in 2.7x\n";}
				return null;
			}
			
			if($this->intvalVersion<290){
				if($this->ASROOT){echo "Starting......: SSLBUMP:: Not supported in 2.x\n";}
				return null;		
			}			
			
			if($this->SSL_BUMP==null){return null;}
			if($this->SSL_BUMP==0){return null;}
			writelogs("SQUID INTVAL: $this->intvalVersion",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			if($this->IS_32){
				if($this->ASROOT){echo "Starting......: SSLBUMP:: Is a squid 3.2\n";}
				return " ssl-bump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
			}
			
			if($this->intvalVersion>316){
				if($this->ASROOT){echo "Starting......: SSLBUMP:: Is a squid 3.2\n";}
				return " ssl-bump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
			}
			if($this->ASROOT){echo "Starting......: SSLBUMP:: return sslBump\n";}
			

			
			return " sslBump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
		}
		
		public function get_ssl_port(){
			if($this->ssl_port>0){return $this->ssl_port;}
			if($this->second_listen_portForTransparent>0){return 
				$this->ssl_port=$this->second_listen_portForTransparent+1;
				$this->SaveToLdap();
				return $this->ssl_port;
			}
			
			if($this->ssl_port==0){
				$this->ssl_port=$this->listen_port+2;
				return $this->listen_port;
			}
		}
		
		private function ssl_bump_tranparent($listen_port){
			$listen_port=0;
			if($this->SSL_BUMP==null){return null;}
			if($this->SSL_BUMP==0){return null;}
			if($this->hasProxyTransparent==0){return null;}
			$listen_port=$this->get_ssl_port();
			if($this->ASROOT){echo "Starting......: SSLBUMP:: SSL Port: $listen_port\n";}
			$sock=new sockets();
			$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
			if($SquidBinIpaddr<>null){$SquidBinIpaddr="$SquidBinIpaddr:";}
			writelogs("SQUID INTVAL: $this->intvalVersion",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			if($this->intvalVersion>=320){
				return "https_port $SquidBinIpaddr$listen_port intercept ssl-bump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
			}
			
			if($this->intvalVersion>316){
				return "https_port $SquidBinIpaddr$listen_port intercept ssl-bump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
			}
			
			if($this->intvalVersion<290){
				return "https_port $SquidBinIpaddr$listen_port transparent cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem";
			}
			
			return "https_port $SquidBinIpaddr$listen_port transparent sslBump cert=/etc/squid3/ssl/cacert.pem key=/etc/squid3/ssl/privkey.pem\n";  
		}
		
		private function ssl_bump_access(){
			if($this->SSL_BUMP==0){return null;}
			if($this->IS_27){return null;}
			if(!$this->ASROOT){return;}
			if($this->SSL_BUMP_WHITE_LIST==1){
				$array[]="ssl_bump deny all";
				$array[]="always_direct allow all";
				$array[]="sslproxy_flags DONT_VERIFY_PEER";
				return @implode("\n",$array);
			}
			$ALREADY=array();
			$sql="SELECT website_name FROM squid_ssl WHERE `type`='ssl-bump-wl' AND enabled=1 ORDER BY website_name LIMIT 0,50";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			$c=0;
			if(mysql_num_rows($results)>0){
				while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					if($ligne["website_name"]==null){continue;}
					$c=$c+1;
					$ligne["website_name"]=str_replace("https://","",$ligne["website_name"]);
					$www=$ligne["website_name"];
					$www_tbl=explode(".",$ligne["website_name"]);
					if(count($www_tbl)>2){
						unset($www_tbl[0]);
						$www_table[]=".".@implode(".",$www_tbl);
					}else{
						$www_table[]=".".$www;
					}
					if(isset($ALREADY[$www])){continue;}
					
					$ALREADY[$www]=true;
				}
			}
			
			if(count($www_table)>0){
				@file_put_contents("/etc/squid3/no_ssl_bump", @implode("\n", $www_table));
				if($this->ASROOT){echo "Starting......: SSLBUMP:: no bump for ". count($www_table)." domains\n";}
				$array[]="acl no_ssl_bump dstdomain \"/etc/squid3/no_ssl_bump\"";
				$array[]="ssl_bump deny no_ssl_bump";
				
			}
			$array[]="ssl_bump allow all";
			$array[]="always_direct allow all";
			$array[]="sslproxy_flags DONT_VERIFY_PEER";
			$array[]="";
			$array[]="";
			return @implode("\n",$array);
		}
		

		
		
		
		public function ftp_parameters(){	
				if(!isset($this->FTP_PARAMS["ftp_list_width"])){$this->FTP_PARAMS["ftp_list_width"]=32;}
				if(!isset($this->FTP_PARAMS["ftp_sanitycheck"])){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
				if(!isset($this->FTP_PARAMS["ftp_epsv"])){$this->FTP_PARAMS["ftp_epsv"]='yes';}
				if(!isset($this->FTP_PARAMS["ftp_epsv_all"])){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
				if(!isset($this->FTP_PARAMS["ftp_telnet_protocol"])){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}		
				if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}
				if(!isset($this->FTP_PARAMS["ftp_passive"])){$this->FTP_PARAMS["ftp_passive"]=1;}						
				
				
				if(!is_numeric($this->FTP_PARAMS["ftp_list_width"])){$this->FTP_PARAMS["ftp_list_width"]=32;}
				if($this->FTP_PARAMS["ftp_sanitycheck"]==null){$this->FTP_PARAMS["ftp_sanitycheck"]='yes';}
				if($this->FTP_PARAMS["ftp_epsv"]==null){$this->FTP_PARAMS["ftp_epsv"]='yes';}
				if($this->FTP_PARAMS["ftp_epsv_all"]==null){$this->FTP_PARAMS["ftp_epsv_all"]='no';}
				if($this->FTP_PARAMS["ftp_telnet_protocol"]==null){$this->FTP_PARAMS["ftp_telnet_protocol"]='yes';}		
				if(!isset($this->FTP_PARAMS["ftp_user"])){$this->FTP_PARAMS["ftp_user"]=null;}			
				
				$ftp_passive=$this->FTP_PARAMS["ftp_passive"];
				$ftp_sanitycheck=$this->FTP_PARAMS["ftp_sanitycheck"];
				$ftp_epsv=$this->FTP_PARAMS["ftp_sanitycheck"];
				$ftp_epsv_all=$this->FTP_PARAMS["ftp_epsv_all"];
				$ftp_telnet_protocol=$this->FTP_PARAMS["ftp_telnet_protocol"];
				
				if($ftp_passive==null){$ftp_passive=1;}	
				if($ftp_passive==1){$ftp_passive="on";}else{$ftp_passive="off";}
				
				if($ftp_sanitycheck==null){$ftp_sanitycheck=1;}	
				if($ftp_sanitycheck==1){$ftp_sanitycheck="on";}else{$ftp_sanitycheck="off";}				
				
				if($ftp_epsv==null){$ftp_epsv=1;}	
				if($ftp_epsv==1){$ftp_epsv="on";}else{$ftp_epsv="off";}				
				
				if($ftp_epsv_all==null){$ftp_epsv_all=0;}	
				if($ftp_epsv_all==1){$ftp_epsv_all="on";}else{$ftp_epsv_all="off";}				
								
				if($ftp_telnet_protocol==null){$ftp_telnet_protocol=0;}	
				if($ftp_telnet_protocol==1){$ftp_telnet_protocol="on";}else{$ftp_telnet_protocol="off";}		

				
				$conf[]="";
				$conf[]="#--------- FTP specific parameters";
				if(preg_match("#(.+?)@#",$this->FTP_PARAMS["ftp_user"])){
					$conf[]="ftp_user {$this->FTP_PARAMS["ftp_user"]}";
				}
				
				if(($this->IS_30) && (!$this->IS_32)){
					$conf[]="ftp_list_width {$this->FTP_PARAMS["ftp_list_width"]}";
				}
				$conf[]="ftp_passive $ftp_passive";
				$conf[]="ftp_sanitycheck $ftp_sanitycheck";
				if($this->IS_31){
					$conf[]="ftp_epsv $ftp_epsv";
					$conf[]="ftp_epsv_all $ftp_epsv_all";
				}
				$conf[]="ftp_telnet_protocol $ftp_telnet_protocol";
				$conf[]="";
				$conf[]="";
				return @implode("\n",$conf);
		}
		
		
		
	private function squid_reverse_websites(){
		
			$sql="SELECT * FROM squid_accel ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				$website_name=$ligne["website_name"];
				$website_name=str_replace("http://","",$website_name);
				$website_name=str_replace("https://","",$website_name);
				if(preg_match("#^.+?\.(.+?)\.(.+?)$#",$website_name,$re)){$acl_addwww=" .{$re[1]}.{$re[2]}";}else{$acl_addwww=$website_name;}
				$ip=$ligne["website_ip"];
				$port=$ligne["website_port"];
				if($port==null){$port=80;}
				if($website_name==null){continue;}
				if($ip==null){continue;}
				$website_name_name=str_replace(".","_",$website_name);
				$conf[]="cache_peer $ip parent $port 0 no-query no-digest originserver name=$website_name_name";
				$acls[]="acl acl_$website_name_name dstdomain $acl_addwww";
				$cache_peer_access[]="cache_peer_access $website_name_name allow acl_$website_name_name";
				$http_access[]="http_access allow acl_$website_name_name all";
				
			}
		
			if(is_array($conf)){
				$ii[]=implode("\n",$conf);
				$ii[]="";
				$ii[]=implode("\n",$acls);
				$ii[]="";
				$ii[]=implode("\n",$cache_peer_access);	
				$ii[]="";
				$ii[]=implode("\n",$http_access);		
				return "\n".implode("\n",$ii)."\n";						
			}
		
	}
		
public function refresh_pattern_list(){
	$sql="SELECT *  FROM websites_caches_params WHERE `sitename` IS NOT NULL";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	$notpoint=true;
	$options[0]="";
	$options[1]="ignore-no-cache ignore-no-store ignore-private refresh-ims";
	$options[2]="override-expire ignore-no-cache ignore-no-store ignore-private override-lastmod ignore-auth ignore-reload";

		$conf[]="refresh_pattern ^ftp:		1440	20%	10080";
		$conf[]="refresh_pattern ^gopher:	1440	0%	1440";
		

	
	//sitename,MIN_AGE,MAX_AGE,PERCENT,options
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=trim($ligne["sitename"]);
		$pattern=str_replace(".","\.",$pattern);
		if($pattern=='.'){continue;}
		$already[$pattern]=true;
		$already[".$pattern"]=true;
		$conf[]="refresh_pattern .$pattern  {$ligne["MIN_AGE"]}    {$ligne["PERCENT"]}%     {$ligne["MAX_AGE"]} {$options[$ligne["options"]]}";
	}
	
	$sql="SELECT * FROM `squid_speed` WHERE `domain` IS NOT NULL";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=trim($ligne["domain"]);
		if(isset($already[".$pattern"])){continue;}
		if($pattern=='.'){
			$notpoint=false;
			$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]} {$ligne["refresh_pattern_options"]}";
			continue;
		}
		
		
		
		$pattern=str_replace(".","\.",$pattern);
		$pattern=str_replace("*",".*",$pattern);
		$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]}  {$ligne["refresh_pattern_options"]}";
		
	}
	if($notpoint){$conf[]="refresh_pattern .		   0	20%	4320";}
	$conf[]="refresh_pattern -i (/cg-bin/|\?) 0 0% 0";
	if(is_array($conf)){return implode("\n",$conf)."\n";}
	
}

public function SQUID_LOCAL_NETWORKS(){
		$sock=new sockets();
		$k=array();
		$conf=array();
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
		
	if(count($k)==0){return null;}
	while (list ($m, $l) = each ($k)){$s[]=$l;}
	$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow office_network";
	$conf[]="acl office_network src " . implode(" ",$s);
	return @implode("\n",$conf);
}
		
		
		
function SAFE_PORTS(){
	$sock=new sockets();
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
	
	
	if($this->enable_ftp_restrictions<>1){
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

		
function acls_multimedias(){
		return null;
		$conf=null;
		$conf=$conf."acl MULTIMEDIA rep_mime_type -i ^(audio\/x-mpegurl|audio\/mpeg|video\/flv|video\/x-flv|application\/x-shockwave-flash|audio\/ogg|video\/ogg|application\/ogg)$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^video/x-ms-asf$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/vnd.ms.wms-hdr.asfv1$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-mms-framed$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^image/\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^video\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^audio\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-dvi$\n";
		$conf=$conf."acl multimedia_rep rep_mime_type -i ^application/x-isoview\n";
		$conf=$conf."acl multimedia_browsers browser -i ^.*player\n";
		$conf=$conf."acl bigfiles_types urlpath_regex -i \.(deb|rpm|iso|tar\.gz|gz|bz|tar|cue|nrg|crf|bwi|bwt|lcd|ccd|mdf|mds|vcd|cif|vdi|img)((\?|&).*)?$";

		return $conf;
		}
		
function external_ldap_ou($path){
	$ldap=new clladp();
	$ou_arr=$ldap->hash_get_ou();
	
	if(is_array($ou_arr)){
		while (list ($num, $val) = each ($ou_arr)){		
			$conf=$conf . "external_acl_type ldap_{$val} %LOGIN " . dirname($path)."/squid_ldap_group -D \"cn=$ldap->ldap_admin,$ldap->suffix\"";
			$conf=$conf . " -w $ldap->ldap_password -b \"ou=$val,$ldap->suffix\"  -f \"(&(objectClass=posixGroup)(gidNumber=%a)(memberUid=%v))\" -S -v 3 -h 127.0.0.1\n";
			}
		return "$conf\n";
		}
	}
	

	
}




class cicap{
	var $main_array=array();
	var $EnableClamavInCiCap=1;
	var $EnableCicapToSyslog=0;
	var $EnableSquidGuardInCiCAP=1;
	var $EnableUfdbGuard=0;
	var $enable_metascanner=0;
	
	function cicap(){
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$ini->loadString($sock->GET_INFO('CicapInternalConfiguration'));
		$this->main_array=$ini->_params;
		$this->BuildDefaults();
		$this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		$this->EnableCicapToSyslog=$sock->GET_INFO("EnableCicapToSyslog");
		$this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		$this->EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		
		if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=1;}
		if(!is_numeric($this->EnableClamavInCiCap==null)){$this->EnableClamavInCiCap=0;}
		if(!is_numeric($this->EnableCicapToSyslog==null)){$this->EnableCicapToSyslog=1;}
		
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
		if($users->APP_UFDBGUARD_INSTALLED){
			if($this->EnableUfdbGuard==1){
				echo "Starting......: c-icap disabling squidGuard databases, UfdbGuard is enabled\n";
				$this->EnableSquidGuardInCiCAP=0;
			}
		}

		if(!$users->APP_KHSE_INSTALLED){$this->enable_metascanner=0;}
			
			
			
		}
	
	function BuildDefaults(){
		if($this->main_array["CONF"]["Timeout"]==null){$this->main_array["CONF"]["Timeout"]=300;}
		if($this->main_array["CONF"]["KeepAlive"]==null){$this->main_array["CONF"]["KeepAlive"]="on";}
		if($this->main_array["CONF"]["MaxKeepAliveRequests"]==null){$this->main_array["CONF"]["MaxKeepAliveRequests"]="100";}
		if($this->main_array["CONF"]["KeepAliveTimeout"]==null){$this->main_array["CONF"]["KeepAliveTimeout"]="600";}
		if($this->main_array["CONF"]["StartServers"]==null){$this->main_array["CONF"]["StartServers"]="3";}
		if($this->main_array["CONF"]["MaxServers"]==null){$this->main_array["CONF"]["MaxServers"]="10";}
		if($this->main_array["CONF"]["MinSpareThreads"]==null){$this->main_array["CONF"]["MinSpareThreads"]="10";}
		if($this->main_array["CONF"]["MaxSpareThreads"]==null){$this->main_array["CONF"]["MaxSpareThreads"]="20";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==null){$this->main_array["CONF"]["ThreadsPerChild"]="0";}
		if($this->main_array["CONF"]["MaxRequestsPerChild"]==null){$this->main_array["CONF"]["MaxRequestsPerChild"]="0";}
		if($this->main_array["CONF"]["srv_clamav.SendPercentData"]==null){$this->main_array["CONF"]["srv_clamav.SendPercentData"]="5";}
		if($this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]==null){$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]="2";}
		if($this->main_array["CONF"]["srv_clamav.MaxObjectSize"]==null){$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]="5";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]="0";}
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]="100";}		
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]="5";}
		if($this->main_array["CONF"]["ThreadsPerChild"]==0){$this->main_array["CONF"]["ThreadsPerChild"]=10;}
		if($this->main_array["CONF"]["VirSaveDir"]==null){$this->main_array["CONF"]["VirSaveDir"]="/opt/artica/share/www/squid-attachments";}
		if($this->main_array["CONF"]["VirHTTPServer"]==null){$this->main_array["CONF"]["VirHTTPServer"]="https:///exec.cicap.php?usename=%f&remove=1&file=";}
		if($this->main_array["CONF"]["DebugLevel"]==null){$this->main_array["CONF"]["DebugLevel"]="3";}
		if($this->main_array["CONF"]["ViralatorMode"]==null){$this->main_array["CONF"]["ViralatorMode"]="0";}
		
		
		
	
	}
	
	private function LOCATE_CLAMD_CONF(){
		if(is_file("/etc/clamav/clamd.conf")){return "/etc/clamav/clamd.conf";}
		if(is_file("/etc/clamd.conf")){return "/etc/clamd.conf";}
	}
	
	public function LOCATE_MODULES_DIR(){
		if(is_file("/usr/lib/c_icap/sys_logger.so")){return "/usr/lib/c_icap";}
		if(is_file("/var/lib/c_icap/sys_logger.so")){return "/var/lib/c_icap";}
	}
	
	private function CLAMAV_USER(){
		$tbl=@explode("\n",@file_get_contents($this->LOCATE_CLAMD_CONF()));
		while (list ($num, $val) = each ($tbl)){	
			if(preg_match("#User\s+(.+)#",$val,$re)){return trim($re[1]);}
		}
	}
	
	private function DNBSL_LIST(){
		include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");
		$sock=new sockets();
		$datas=explode("\n",$sock->GET_INFO("CicapDNSBL"));
		while (list ($num, $line) = each ($datas)){
			if(strlen($line)<4){continue;}
			$ALLRD[$line]=true;
			$servicename=str_replace(".","",$line);
			$conf[]="url_check.LookupTableDB $servicename  domain dnsbl:$line";
			$servicenames[]=$servicename;
			}
			
			
		$sql="SELECT dnsbl FROM webfilter_dnsbl WHERE enabled=1";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if(isset($ALLRD[$line])){continue;}
			$servicename=str_replace(".","",$ligne["dnsbl"]);
			$conf[]="url_check.LookupTableDB $servicename  domain dnsbl:{$ligne["dnsbl"]}";
			$servicenames[]=$servicename;
			
		}
			
		if(!is_array($conf)){return array();}
		
		$conf[]="url_check.Profile default block ". implode(" ",$servicenames);
		
		return $conf;
	}
	
	
	public function buildconf(){
			$this->BuildDefaults();
			$sock=new sockets();
			$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
			$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
			if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=0;}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$user="squid";
			$modules_path=$this->LOCATE_MODULES_DIR();
			if(!$users->KASPERSKY_WEB_APPLIANCE){
				echo "Starting......: c-icap using modules path in $modules_path\n";
				if(is_file("$modules_path/srv_clamav.so")){
					echo "Starting......: c-icap using ClamAV antivirus\n";
					$user=$this->CLAMAV_USER();
				
				}
			}
			
			if(is_dir($GLOBALS["guarddb"])){shell_exec("/bin/chown -R $user:$user {$GLOBALS["guarddb"]}/*");}
			if(trim($usermenus->hostname==null)){
				if($GLOBALS["AS_ROOT"]){
					$unix=new unix();
					$usermenus->hostname=$unix->hostname_g();
				}
			}
			
			
			echo "Starting......: c-icap using User $user ($usermenus->hostname)\n";
			$conf[]="PidFile /var/run/c-icap.pid";
			$conf[]="CommandsSocket /var/run/c-icap/c-icap.ctl";
			$conf[]="Timeout {$this->main_array["CONF"]["Timeout"]}";
			//$conf[]="KeepAlive Off";
			$conf[]="MaxKeepAliveRequests {$this->main_array["CONF"]["MaxKeepAliveRequests"]}";
			$conf[]="KeepAliveTimeout {$this->main_array["CONF"]["KeepAliveTimeout"]}";
			$conf[]="StartServers {$this->main_array["CONF"]["StartServers"]}";
			$conf[]="MaxServers {$this->main_array["CONF"]["MaxServers"]}";
			$conf[]="MinSpareThreads     {$this->main_array["CONF"]["MinSpareThreads"]}";
			$conf[]="MaxSpareThreads     {$this->main_array["CONF"]["MaxSpareThreads"]}";
			$conf[]="ThreadsPerChild     {$this->main_array["CONF"]["ThreadsPerChild"]}";
			$conf[]="MaxRequestsPerChild  {$this->main_array["CONF"]["MaxRequestsPerChild"]}";
			$conf[]="MaxMemObject 131072";
			$conf[]="Port 1345 ";
			$conf[]="User $user";
			$conf[]="Group $user";
			$conf[]="ServerAdmin you@your.address";
			$conf[]="ServerName $usermenus->hostname";
			$conf[]="TmpDir /var/lib/c_icap/temporary";
			$conf[]="DebugLevel {$this->main_array["CONF"]["DebugLevel"]}"; #11 whas very verbose
			$conf[]="ModulesDir $modules_path";
			$conf[]="ServicesDir $modules_path";
			$conf[]="TemplateDir /usr/share/c_icap/templates/";		
			$conf[]="LoadMagicFile /etc/c-icap.magic";
			$conf[]="TemplateDefaultLanguage en";
			$conf[]="#TemplateReloadTime 360";
			$conf[]="#TemplateCacheSize 20";
			$conf[]="#TemplateMemBufSize 8192";
			
			$conf[]="";
			$conf[]="acl all src 0.0.0.0/0.0.0.0";
			$conf[]="icap_access allow all";
			//$conf[]="acl loopback src 127.0.0.1";
			$conf[]="";
			
			
			$conf[]="RemoteProxyUsers on";
			$conf[]="RemoteProxyUserHeader X-Authenticated-User";
			$conf[]="RemoteProxyUserHeaderEncoded on";
			$conf[]="LogFormat allFormat \"%tl;%a;%un;%iu;%is;%huo\""; 	
			//$conf[]="echo.PreviewSize 512";
			//$conf[]="echo.TransferIgnore gif, jpeg";
			$filelogger=true;
			if($this->EnableSquidGuardInCiCAP==1){
				echo "Starting......: c-icap using squidGuard databases\n";
				$icc=new cicap_filter();
				$conf[]=$icc->Buildacls();
			}else{
				echo "Starting......: c-icap disable squidGuard databases\n";
			}
			

			
			
			
			if($this->EnableCicapToSyslog==1){
			if(is_file("$modules_path/sys_logger.so")){
				$filelogger=false;
				echo "Starting......: c-icap using Sysloger\n";
				$conf[]="#Sysloger";
				$conf[]="Module logger sys_logger.so";
				$conf[]="Logger sys_logger";
				$conf[]="sys_logger.server_priority crit";				
				$conf[]="sys_logger.access_priority crit";
				$conf[]="sys_logger.Prefix \"C-ICAP:\"";
				$conf[]="sys_logger.Facility local1";
				$conf[]="sys_logger.LogFormat \"%tl;%a;%un;%iu;%is;%huo\"";
				$conf[]="";	
			}}
			if($filelogger){
				$conf[]="Logger file_logger";
				$conf[]="ServerLog /var/log/c-icap/server.log";
				$conf[]="AccessLog /var/log/c-icap/access.log allFormat all";				
				
			}
			
			if($this->enable_metascanner==1){
				$conf[]="Service khse /opt/kaspersky/khse/libexec/libms-icap.so";
			}			
			
			if($SquidActHasReverse<>1){
				if(is_file("$modules_path/dnsbl_tables.so")){$conf[]="Module common bdb_tables.so";}
				$conf[]="Module common dnsbl_tables.so";
				$conf[]="Service url_check_module srv_url_check.so";
				$conf[]="";	
				
				if(is_file("$modules_path/dnsbl_tables.so")){
					if($DisableCicapDNBSL==0){
						$array=$this->DNBSL_LIST();
						echo "Starting......: c-icap using ". count($array) ." DNSBL services\n";
						if(count($array)>0){
							$this->cicap_whitelisted_servers();
							echo "Starting......: c-icap using DNSBL\n";
							$conf[]="#DNSBL";
							$conf[]="url_check.LookupTableDB whitelist domain hash:/etc/squid3/c-icap.whitelist.txt";
							$conf[]="url_check.Profile default pass whitelist";
							$conf[]=implode("\n",$this->DNBSL_LIST());
							$conf[]="";	
						}
					}else{
						echo "Starting......: c-icap Disabled DNSBL service\n";
					}
				}			
		
				if($this->EnableSquidGuardInCiCAP==1){$conf[]=$icc->GetSquidGuarddbs();}
			}
			
			if(!$users->KASPERSKY_WEB_APPLIANCE){
			echo "Starting......: c-icap clamav library: $modules_path/srv_clamav.so\n";
			if(is_file("$modules_path/srv_clamav.so")){
				$sock=new sockets();
				$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
				if(!is_numeric($EnableClamavInCiCap)){$EnableClamavInCiCap=0;}
				if($EnableClamavInCiCap==1){
						if($this->main_array["CONF"]["VirSaveDir"]<>null){
							if(!is_dir($this->main_array["CONF"]["VirSaveDir"])){
								@mkdir($this->main_array["CONF"]["VirSaveDir"],0755,true);
							}
						}
						$conf[]="";
						$conf[]="#Clamav";
						$conf[]="Service antivirus_module srv_clamav.so srv_url_check.so";
						$conf[]="ServiceAlias  avscan srv_clamav?allow204=off&sizelimit=off&mode=simple";
						$conf[]="srv_clamav.ScanFileTypes TEXT DATA EXECUTABLE ARCHIVE MSOFFICE";
						$conf[]="srv_clamav.TransferIgnore flv, f4v, f4p, f4a, f4b, mpeg, mp2, mp3";
						$conf[]="srv_clamav.SendPercentData {$this->main_array["CONF"]["srv_clamav.SendPercentData"]}";
						$conf[]="srv_clamav.StartSendPercentDataAfter {$this->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"]}M";
						$conf[]="srv_clamav.Allow204Responces off";
						$conf[]="srv_clamav.MaxObjectSize  {$this->main_array["CONF"]["srv_clamav.MaxObjectSize"]}M";
						$conf[]="srv_clamav.ClamAvTmpDir /var/tmp";
						$conf[]="srv_clamav.ClamAvMaxFilesInArchive {$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]}";
						$conf[]="srv_clamav.ClamAvMaxFileSizeInArchive {$this->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"]}M";
						$conf[]="srv_clamav.ClamAvMaxRecLevel {$this->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"]}";
						
						
						if($this->main_array["CONF"]["ViralatorMode"]==1){
							$sock->SET_INFO("CiCapViralatorMode",1);
							echo "Starting......: c-icap VirSaveDir: \"{$this->main_array["CONF"]["VirSaveDir"]}\"\n";
							$conf[]="srv_clamav.VirSaveDir {$this->main_array["CONF"]["VirSaveDir"]}";
							$conf[]="srv_clamav.VirHTTPServer  \"{$this->main_array["CONF"]["VirHTTPServer"]}\"";
							$conf[]="srv_clamav.VirUpdateTime   15";
							$conf[]="srv_clamav.VirScanFileTypes ARCHIVE EXECUTABLE";
						}else{
							$sock->SET_INFO("CiCapViralatorMode",0);	
						}
					}
				}
			}
				
				
				
				@mkdir("/var/log/c-icap",0666,true);
				@mkdir("/var/lib/c_icap/temporary",0666,true);
				@mkdir("/var/run/c-icap",0666,true);
				@mkdir("/var/tmp",0666,true);
				@mkdir("/usr/share/c_icap/templates",0666,true);
				//if(!is_file("/var/log/c-icap/server.log")){@file_put_contents("/var/log/c-icap/server.log"," ");}
				//if(!is_file("/var/log/c-icap/access.log")){@file_put_contents("/var/log/c-icap/access.log"," ");}
				
				shell_exec("/bin/chmod -R 0755 /var/log/c-icap");
				shell_exec("/bin/chmod -R 0755 /var/run/c-icap");
				shell_exec("/bin/chmod -R 0755 /var/lib/c_icap");
				shell_exec("/bin/chmod -R 0755 /var/tmp");
				shell_exec("/bin/chmod -R 0755 /usr/share/c_icap");
				
				shell_exec("/bin/chown -R $user:$user /var/log/c-icap");
				shell_exec("/bin/chown -R $user:$user /var/run/c-icap");
				shell_exec("/bin/chown -R $user:$user /var/lib/c_icap");
				shell_exec("/bin/chown -R $user:$user /var/tmp");
				shell_exec("/bin/chown -R $user:$user /usr/share/c_icap");
				if(is_dir($this->main_array["CONF"]["VirSaveDir"])){
					shell_exec("/bin/chown -R $user:$user {$this->main_array["CONF"]["VirSaveDir"]}");
				}
				
				echo "Starting......: c-icap apply securities on user \"$user\" done\n";
				@file_put_contents("/etc/c-icap.conf",@implode("\n",$conf));
		
	}
	
	
	
	private function cicap_whitelisted_servers(){
		$sql="SELECT * FROM dansguardian_files WHERE filename='exceptionsitelist' AND RuleID=1 AND enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$t[]=$ligne["pattern"];
		}
		
		@file_put_contents("/etc/squid3/c-icap.whitelist.txt",@implode("\n",$t));
		
	}
	
	
	function Save(){
		$ini=new Bs_IniHandler();
		$ini->_params=$this->main_array;
		$confArtica=$ini->toString();
		$sock=new sockets();
		$sock->SaveConfigFile($confArtica,'CicapInternalConfiguration');
		$sock->getFrameWork("cmd.php?cicap-reconfigure=yes");
		$sock->getFrameWork("cmd.php?cicap-reload=yes");
		}
	
}
?>
