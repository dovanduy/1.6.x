<?php
if(!isset($GLOBALS["AS_ROOT"])){$GLOBALS["AS_ROOT"]=false;}
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
include_once(dirname(__FILE__)."/class.squid.acls.useragents.inc");
include_once(dirname(__FILE__)."/class.squid.booster.inc");
include_once(dirname(__FILE__)."/class.squidworkers.inc");
include_once(dirname(__FILE__)."/class.system.nics.inc");
include_once(dirname(__FILE__)."/class.haarp.inc");
include_once(dirname(__FILE__)."/class.squid.caches.rules.inc");
include_once(dirname(__FILE__)."/class.squid.caches.inc");
include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
include_once(dirname(__FILE__)."/class.tcpip.inc");
class squid{
	

}
class squidbee{
	public $dn;
	public $network_array=array();
	public $dns_array=array();
	public $squid_conf;
	public $ArticaSquidParameters;
	private $SquidReverseDefaultWebSite=null;
	private $EnableSquidSSLCRTD=0;
	public $ldap_error;
	public $listen_port;
	public $second_listen_port=0;
	public $smartphones_port=0;
	public $visible_hostname;
	public $enable_kavproxy;
	public $enable_cicap;
	public $enable_dansguardian;
	public $enable_squidguard;
	public $enable_UfdbGuard;
	public $LDAP_AUTH=0;
	public $NTLM_AUTH=0;
	public $SSL_BUMP=0;
	public $EnableClamavInCiCap=1;
	public $CACHE_SIZE=2000;
	public $CACHE_PATH;
	public $CACHE_TYPE="aufs";
	public $alt_listen_port;
	public $kav_accept=false;
	public $kav_accept_why='';
	public $SquidBlockSites='';
	public $array_block_istes=array();
	public $global_conf_array=array();
	public $cache_list=array();
	public $acl_times=array();
	public $hasProxyTransparent;
	public $EnableTransparent27=0;
	public $ASROOT=false;
	public $SQUID_BIN_VERSION=0;
	public $SQUID_ICAP_ENABLED=false;
	public $SQUID_VERSION="";
	public $KAV4PROXY_INSTALLED=false;
	public $EnableParentProxy=0;
	public $prefer_direct=0;
	public $nonhierarchical_direct=0;
	public $LDAP_EXTERNAL_AUTH=0;
	public $EXTERNAL_LDAP_AUTH_PARAMS=array();
	public $IS_27=false;
	public $IS_30=false;
	public $IS_31=false;
	public $IS_32=false;
	public $IS_33=false;
	public $IS_34=false;
	public $intvalVersion=false;
	public $ICAP_SERVICES_COUNT=0;
	public $FTP_PARAMS=array();
	public $wccp2_enabled=0;
	public $wccp2_router=null;
	public $wccp2_forwarding_method=1;
	public $wccp2_return_method=1;
	public $wccp2_assignment_method="hash";
	public $enable_ftp_restrictions=0;
	public $EnableUserAgentBanAll=0;
	public $enable_adzapper=0;
	public $enable_squidclamav=0;
	public $enable_metascanner=0;
	public $enable_streamcache=0;
	public $ACL_ARP_ENABLED=false;
	public $EnableChangeRequestSize=0;
	public $allow_squid_localhost=0;
	public $ignore_expect_100=0;
	public $EnableKerbAuth=0;
	public $EnableKavICAPRemote=0;
	public $KavICAPRemoteAddr=null;
	public $KavICAPRemotePort=0;
	public $SquidDisableAllFilters=0;
	public $url_rewrite_bypass=0;
	public $ICP_PORT=0;
	public $HTCP_PORT=0;
	public $enable_ecapav=0;
	public $CPU_NUMBER;
	public $uuid;
	public $EnableICPPort=0;
	public $UseTProxyMode=0;
	public $store_dir_minsize=null;
	public $SquidBoosterOnly=0;
	public $ssl_port=0;
	public $certificate_center=null;
	public $second_listen_portForTransparent=0;
	public $EnableKerberosAuthentication=0;
	public $SSL_BUMP_WHITE_LIST=0;
	public $EnableSquidCSV=0;
	public $SquidActHasReverse=0;
	public $EnableSplashScreen=0;
	public $AsSquidLoadBalancer=0;
	
	public $max_filedesc=8192;
	public $snmp_community=null;
	public $snmp_enable=0;
	public $snmp_port=3401;
	public $snmp_access_ip=null;
	public $SquidLdapAuthEnableGroups=0;
	public $EnableRemoteStatisticsAppliance=0;
	private $TRANSPARENT_PORTS_LIST=array();
	public $forwarded_for="transparent";
	private $ProxyDedicateMicrosoftRules=0;
	private $AntivirusEnabledCentral=1;
	private $UfdbEnabledCentral=1;	
	private $Kav4ProxyEnabledCentral=0;
	private $EnableMacAddressFilterCentral=1;
	private $EnableMacAddressFilter=1;
	private $SquidBubbleMode=0;
	private $MyPortsArray=array();
	private $MyStandPort=null;
	private $SquidClientParams=array();
	private $ForceWindowsUpdateCaching=0;
	private $MGR_LISTEN_PORT=0;
	private $ChilliEnabled=false;
	private $DisableAnyCache=0;
	private $DisableSSLStandardPort=0;
	private $SquidBinIpaddr=null;
	private $SquidAllowSmartPhones=0;
	private $WCCPListenPort=0;
	private $SquidUrgency=0;
	private $CacheManagement2=0;
	private $ArticaHotSpotPort=0;
	private $ArticaSSLHotSpotPort=0;
	private $UtDNSEnable=0;
	
	public $peer_connect_timeout=30;
	public $connect_timeout=120;
	public $persistent_request_timeout=60;
	public $client_lifetime=60000;
	public $forward_max_tries=30;
	public $forward_timeout=240;
	public $pconn_timeout=120;
	public $dns_timeout=120;
	public $dead_peer_timeout=10;
	public $shutdown_lifetime=10;
	public $read_timeout=900;
	public $incoming_rate=30;	
	private $SQUID_REVERSE_APPLIANCE;
	private $SquidExternalAclQuota=0;
	private $CnTLMPORT=0;
	private $EnableCNTLM=0;
	private $second_port_transparent=null;
	private $transparent=null;
	private $sslcrtd_program=null;
	private $DisableWinbindd=0;
	private $WCCPHash=array();
	
	public $nodes_names=array();
	
	function squidbee(){
		
		if(posix_getuid()==0){$this->ASROOT=true;}
		$GLOBALS["VERBOSED"]=false;
		if(!$this->ASROOT){if($GLOBALS["VERBOSE"]){$GLOBALS["VERBOSED"]=true;}}
		$this->VerifyLdapBranch();
		if($GLOBALS["VERBOSED"]){echo __LINE__.":: ->ParseConfig()<br>\n";}
		$this->ParseConfig();
		$this->kav_accept=$this->isicap();
		$sock=new sockets();
		$this->uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->CPU_NUMBER=$users->CPU_NUMBER;
		$this->SQUID_BIN_VERSION=$users->SQUID_BIN_VERSION;
		if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$sock->getFrameWork("squid.php?full-version=yes");}
		$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
		$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		$this->MEMORY_INSTALLEE=$users->MEM_TOTAL_INSTALLEE;

		$EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		if($EnableClamavInCiCap==null){$EnableClamavInCiCap=1;$sock->SET_INFO("EnableClamavInCiCap","1");}
		$this->enable_UfdbGuard=$this->EnableUfdbGuard();
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
		$this->EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$this->SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
		$this->AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		$this->Kav4ProxyEnabledCentral=$sock->GET_INFO("Kav4ProxyEnabledCentral");
		$this->EnableMacAddressFilter=$sock->GET_INFO("EnableMacAddressFilter");
		$this->EnableMacAddressFilterCentral=$sock->GET_INFO("EnableMacAddressFilterCentral");
		$this->SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
		
		$this->SquidClientParams=unserialize(base64_decode($sock->GET_INFO("SquidClientParams")));
		$this->ForceWindowsUpdateCaching=$sock->GET_INFO("ForceWindowsUpdateCaching");
		$this->SquidReverseDefaultWebSite=$sock->GET_INFO("SquidReverseDefaultWebSite");
		$this->SquidExternalAclQuota=$sock->GET_INFO("SquidExternalAclQuota");
		$this->DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
		$this->SquidAllowSmartPhones=$sock->GET_INFO("SquidAllowSmartPhones");
		$this->smartphones_port=$sock->GET_INFO("smartphones_port");
		$this->EnableSquidSSLCRTD=$sock->GET_INFO("EnableSquidSSLCRTD");
		if($this->SquidReverseDefaultWebSite==null){$this->SquidReverseDefaultWebSite=$this->visible_hostnameF();}
		$this->DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
		$this->SquidUrgency=$sock->GET_INFO("SquidUrgency");
		$this->ProxyDedicateMicrosoftRules=$sock->GET_INFO("ProxyDedicateMicrosoftRules");
		$this->WCCPListenPort=$sock->GET_INFO("WCCPListenPort");
		$this->CacheManagement2=$sock->GET_INFO("CacheManagement2");
		$this->UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
		
		if(!is_numeric($this->ProxyDedicateMicrosoftRules)){$this->ProxyDedicateMicrosoftRules=0;}
		if(!is_numeric($this->DisableWinbindd)){$this->DisableWinbindd=0;}
		if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->SquidExternalAclQuota)){$this->SquidExternalAclQuota=0;}
		if(!is_numeric($this->DisableSSLStandardPort)){$this->DisableSSLStandardPort=1;}
		if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->AsSquidLoadBalancer)){$this->AsSquidLoadBalancer=0;}
		if(!is_numeric($this->SquidActHasReverse)){$this->SquidActHasReverse=0;}
		if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->SquidBubbleMode)){$this->SquidBubbleMode=0;}
		if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}
		if(!is_numeric($this->SquidDisableAllFilters)){$this->SquidDisableAllFilters=0;}
		if(!is_numeric($this->UseTProxyMode)){$this->UseTProxyMode=0;}
		if(!is_numeric($this->EnableSquidCSV)){$this->EnableSquidCSV=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		if(!is_numeric($this->EnableKerberosAuthentication)){$this->EnableKerberosAuthentication=0;}
		if(!is_numeric($this->EnableSplashScreen)){$this->EnableSplashScreen=0;}
		if(!is_numeric($this->Kav4ProxyEnabledCentral)){$this->Kav4ProxyEnabledCentral=0;}
		if(!is_numeric($this->EnableMacAddressFilter)){$this->EnableMacAddressFilter=1;}
		if(!is_numeric($this->EnableMacAddressFilterCentral)){$this->EnableMacAddressFilterCentral=1;}
		if(!is_numeric($this->SquidAllowSmartPhones)){$this->SquidAllowSmartPhones=0;}
		if(!is_numeric($this->smartphones_port)){$this->smartphones_port=0;}
		if(!is_numeric($this->EnableSquidSSLCRTD)){$this->EnableSquidSSLCRTD=1;}
		if(!is_numeric($this->CacheManagement2)){$this->CacheManagement2=0;}
		if(!is_numeric($this->UtDNSEnable)){$this->UtDNSEnable=0;}
		
		
		
		if($this->smartphones_port>0){$this->SquidAllowSmartPhones=0;}
		if($this->EnableKerberosAuthentication==1){$this->EnableKerbAuth=0;}
		
		if(!is_numeric($this->SquidUrgency)){$this->SquidUrgency=0;}
		
		
		
		if(!isset($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!isset($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!isset($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=1;}
		if(!isset($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!isset($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!isset($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!isset($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!isset($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!isset($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!isset($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}		
		
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_children"])){$this->SquidClientParams["auth_param_ntlm_children"]=20;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_startup"])){$this->SquidClientParams["auth_param_ntlm_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["auth_param_ntlm_idle"])){$this->SquidClientParams["auth_param_ntlm_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["auth_param_basic_children"])){$this->SquidClientParams["auth_param_basic_children"]=3;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_startup"])){$this->SquidClientParams["auth_param_basic_startup"]=2;}
		if(!is_numeric($this->SquidClientParams["auth_param_basic_idle"])){$this->SquidClientParams["auth_param_basic_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["url_rewrite_children"])){$this->SquidClientParams["url_rewrite_children"]=10;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_startup"])){$this->SquidClientParams["url_rewrite_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["url_rewrite_idle"])){$this->SquidClientParams["url_rewrite_idle"]=1;}
		
		if(!is_numeric($this->SquidClientParams["external_acl_children"])){$this->SquidClientParams["external_acl_children"]=5;}
		if(!is_numeric($this->SquidClientParams["external_acl_startup"])){$this->SquidClientParams["external_acl_startup"]=1;}
		if(!is_numeric($this->SquidClientParams["external_acl_idle"])){$this->SquidClientParams["external_acl_idle"]=1;}		
		if($this->SquidClientParams["external_acl_startup"]<1){$this->SquidClientParams["external_acl_startup"]=2;}
		
		if($this->ASROOT){
			$this->sslcrtd_program=$this->sslcrtd_program_path();
		}
		
		//Disable StreamCache Patch 2012-11-20
		$this->enable_streamcache=0;
		$this->WCCPHash=unserialize(base64_decode($sock->GET_INFO("WCCPHash")));
		
		if( (!is_numeric($this->WCCPListenPort)) OR ($this->WCCPListenPort<1024) ) {
			$this->WCCPListenPort=rand(18999, 65550);
			$sock->SET_INFO("WCCPListenPort", $this->WCCPListenPort);
		}
		
		
		$users=new usersMenus();
		if($users->SQUID_REVERSE_APPLIANCE){
			$this->SquidActHasReverse=1;
			$this->ssl_port=443;
			$this->SSL_BUMP=1;
		}
		
		
		
		
		$wks=new squidworkers();
		$this->CheckVersion();
		$this->hasProxyTransparent=intval($sock->GET_INFO("hasProxyTransparent"));
		$this->EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
		
		$this->SquidBoosterOnly=$wks->zcheck_squidbooster_value();
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}	
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->enable_UfdbGuard=$this->UfdbEnabledCentral;
			$this->EnableKavICAPRemote=$this->Kav4ProxyEnabledCentral;
			$this->enable_cicap=$this->AntivirusEnabledCentral;
			$this->enable_kavproxy=$this->Kav4ProxyEnabledCentral;
			$this->EnableMacAddressFilter=$this->EnableMacAddressFilterCentral;
			$this->enable_squidclamav=0;
		}
		if($this->SquidActHasReverse==1){$this->enable_UfdbGuard=0;}
		if($this->AsSquidLoadBalancer==1){$this->EnableParentProxy=1;}
		
		
		if(!$this->IS_33){
			if($this->hasProxyTransparent==1){$this->EnableSquidSSLCRTD=0;}
		}
		if($this->SSL_BUMP==1){
			if($this->ssl_port==0){
				$this->ssl_port=$this->get_ssl_port();
			}
		}
		
		
		// Urgency ********************* always at end.
		if($this->SquidUrgency==1){
			$this->enable_UfdbGuard=0;
			$this->DisableAnyCache=1;
			$this->SquidBubbleMode=0;
			$this->SquidDisableAllFilters=1;
		}
		// Urgency ********************* always at end.
		
		
		
		
	}
	
	private function EnableUfdbGuard(){
		$sock=new sockets();
		return $sock->EnableUfdbGuard();
		
	}

	private function sslcrtd_program_path(){
		if(isset($GLOBALS["sslcrtd_program"])){return $GLOBALS["sslcrtd_program"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		 $GLOBALS["sslcrtd_program"] = $unix->squid_locate_generic_bin("ssl_crtd");
		return  $GLOBALS["sslcrtd_program"];
	}
	
	
	function VerifStreamProxyBindIP(){
		$unix=new unix();
		$sock=new sockets();
		$StreamCacheBindProxy_src=$sock->GET_INFO("StreamCacheBindProxy");
		if($StreamCacheBindProxy_src=="127.0.0.1"){return "127.0.0.1";}
		if($StreamCacheBindProxy_src==null){return "127.0.0.1";}
		$StreamCacheBindProxy=$StreamCacheBindProxy_src;
		$IpClass=new IP();
		if(!$IpClass->isIPAddress($StreamCacheBindProxy)){$StreamCacheBindProxy=null;}
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
		unset($NETWORK_ALL_INTERFACES["127.0.0.1"]);
		unset($NETWORK_ALL_INTERFACES["0.0.0.0"]);
		if(!isset($NETWORK_ALL_INTERFACES[$StreamCacheBindProxy])){$StreamCacheBindProxy=null;}
	
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] StreamCacheBindProxy:$StreamCacheBindProxy\n";}
		
		
		if($StreamCacheBindProxy<>null){
			if($StreamCacheBindProxy<>$StreamCacheBindProxy_src){$sock->SET_INFO("StreamCacheBindProxy",$StreamCacheBindProxy);}
			return $StreamCacheBindProxy;
		}
	
		if($StreamCacheBindProxy==null){$StreamCacheBindProxy=$unix->NETWORK_DEFAULT_IP_ADDR();}		
		if($GLOBALS["VERBOSE"]){echo "[".__LINE__."] StreamCacheBindProxy:$StreamCacheBindProxy\n";}
		
		
		
		if($StreamCacheBindProxy<>null){
			if($StreamCacheBindProxy<>$StreamCacheBindProxy_src){$sock->SET_INFO("StreamCacheBindProxy",$StreamCacheBindProxy);}
		}
	
		return $StreamCacheBindProxy;
	}	
	
	
	private function compilation_params(){
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS["COMPILE_SQUID_TOKENS"])){return $GLOBALS["COMPILE_SQUID_TOKENS"];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}	
		exec("$squidbin -v 2>&1",$results);
		$text=@implode("\n", $results);
		if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
		if(preg_match_all("#'(.+?)'#is", $text, $re)){
		while (list ($index, $line) = each ($re[1])){
				if(preg_match("#(.+?)=(.*)#", $line,$ri)){
					$key=$ri[1];
					$value=$ri[2];
					$key=str_replace("--", "", $key);
					if(trim($value)==null){$value=true;}
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

	

	
	private function log_access_acls_csv(){
		$f[]="#--------- CVS log rules";
		$q=new mysql_squid_builder();
		$MACFILER="%>eui";
		$CLIENTSOURCE="%<a";
		if($this->EnableMacAddressFilter==0){$MACFILER='00:00:00:00:00:00';}
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		if(($this->IS_31) && (!$this->IS_32)){$MACFILER='00:00:00:00:00:00';$CLIENTSOURCE="0.0.0.0";}
		
		$f[]="logformat csv_acls \"%{%Y-%m-%d}tl\";\"%{%H:%M:%S}tl\";\"%>a\";\"%>A\";\"$MACFILER\";\"$CLIENTSOURCE\";\"%<A\";\"%[un\";\"%rm\";\"%ru\";\"%rv\";\"%>Hs\";\"%<st\";\"%Ss:%Sh\";\"%{User-Agent}>h\";\"%{X-Forwarded-For}>h\"";
		$acls=new squid_acls_groups();
		$denylogsTXT=null;
		$log_access=$acls->buildacls_bytype("log_access");
		$denylogs=$acls->buildacls_bytype("deny_log");
		if(count($denylogs)>0){$denylogsTXT=@implode(" ", $denylogs);}
		echo "Starting......: ".date("H:i:s")." [LOG ]: Engine access_log allow ".count($log_access)." rules..\n";
		if(count($log_access)>0){
			while (list ($index, $line) = each ($log_access) ){
				$f[]="access_log $line $prefixacls$denylogsTXT";
			}
		}else{
			echo "Starting......: ".date("H:i:s")." [LOG ]: ACL Engine access_log allow No rules..\n";
		}

		if($this->SquidBubbleMode==1){
			$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$denylogsTXT=null;
				$log_access=$acls->buildacls_bytype("log_access",$ligne["aclport"]);
				$denylogs=$acls->buildacls_bytype("deny_log",$ligne["aclport"]);
				if(count($denylogs)>0){$denylogsTXT=@implode(" ", $denylogs);}
				echo "Starting......: ".date("H:i:s")." [LOG ]: Engine access_log allow Port:{$ligne["aclport"]}: ".count($log_access)." rules..\n";
				if(count($log_access)>0){
					while (list ($index, $line) = each ($log_access) ){
						$f[]="access_log $line MyPort{$ligne["aclport"]} $denylogsTXT";
					}
				}
			}
		}				
		
		
		
		return @implode("\n", $f)."\n\n";
	}
	
	

	
	
	private function SNMP(){
		$params=$this->compilation_params();
		if(!isset($params["enable-snmp"])){return "#--------- SNMP\n#--------- Not compiled\n";}
		if($this->snmp_enable==0){return "#--------- SNMP\n#--------- Not enabled\n";}
		if($this->snmp_access_ip==null){return "#--------- SNMP\n#--------- No IP access defined\n";}
		if($this->SquidUrgency==1){return "#--------- SNMP\n#--------- Urgency Enabled\n";}
		
		include_once(dirname(__FILE__)."/class.tcpip.inc");
		$tcp=new IP();
		if(!$tcp->isValid($this->snmp_access_ip)){return "#--------- SNMP\n# `$this->snmp_access_ip` not an ipv4 or ipv6 address\n";}
			
		$aclfront=null;
		$f[]="#--------- SNMP";
		$f[]="snmp_port $this->snmp_port";
		if($this->snmp_community<>null){
			$f[]="acl snmppublic snmp_community $this->snmp_community";
			$aclfront=" snmppublic";
		}
		$f[]="acl snmpConsole src $this->snmp_access_ip";
		$f[]="snmp_access allow{$aclfront} snmpConsole";
		
		$acls=new squid_acls_groups();
		$snmp_access_allow=$acls->buildacls_bytype("snmp_access_allow");
		if(count($snmp_access_allow)>0){
			echo "Starting......: ".date("H:i:s")." [SNMP]: Engine snmp_access allow ".count($snmp_access_allow)." rules..\n";
			while (list ($index, $line) = each ($snmp_access_allow) ){
				$f[]="snmp_access allow $line";
			}
		}else{
			echo "Starting......: ".date("H:i:s")." [SNMP]: ACL Engine snmp_access allow No rules..\n";
		}	
		$f[]="snmp_access deny all";
		return @implode("\n", $f)."\n\n";
	}
	
	
	private function request_header_add(){
		if($this->SquidUrgency==1){"#--------- Add Headers:\n#--------- Urgency enable\n";return;}
		if(!$this->IS_33){"#--------- Add Headers:\n#--------- Not a 3.3x version\n";return;}
		$f[]="#--------- Add Headers:";
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		$acls=new squid_acls_groups();
		$request_header_add=$acls->buildacls_bytype("request_header_add");
		if(count($request_header_add)>0){
			echo "Starting......: ".date("H:i:s")." [ACLS]: Engine request_header_add ".count($request_header_add)." rules..\n";
			while (list ($index, $line) = each ($request_header_add) ){
				$f[]="request_header_add $line";
			}			
		}else{
			echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine request_header_add No rules..\n";
		}
		return @implode("\n", $f)."\n\n";
		
	}
	
	
	public function RockStore($maincachedir=null){
		if($this->SquidUrgency==1){return;}
		if(!$this->IS_32){return;}
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidUseRockStore=$sock->GET_INFO("SquidUseRockStore");
		if(!is_numeric($SquidUseRockStore)){$SquidUseRockStore=0;}
		 
		
		if($SquidUseRockStore==0){
			$s=new squidworkers();
			$Workers=$s->Workers($maincachedir);
			if($s->CPU_FINAL==0){return null;}
			$SquidEnableRockStore=$sock->GET_INFO("SquidEnableRockStore");
			$SquidRockStoreSize=$sock->GET_INFO("SquidRockStoreSize");
			$SquidRockStorePath=$sock->GET_INFO("SquidRockStorePath");
			if(!is_numeric($SquidEnableRockStore)){$SquidEnableRockStore=0;}
			if(!is_numeric($SquidRockStoreSize)){$SquidRockStoreSize=2000;}
			if($SquidRockStorePath==null){$SquidRockStorePath="/home/squid";}
			if($SquidEnableRockStore==0){return $Workers;}
			
			$f[]="# --------- Rock Store: $SquidEnableRockStore - {$SquidRockStoreSize}M";
			if(!is_dir("$SquidRockStorePath/cache-rock")){
				@mkdir("$SquidRockStorePath/cache-rock",0755);
				@chown("$SquidRockStorePath/cache-rock", "squid");
				@chgrp("$SquidRockStorePath/cache-rock", "squid");
			}
			$f[]="cache_dir rock $SquidRockStorePath/cache-rock $SquidRockStoreSize min-size=0 max-size=32768\n";
			$f[]=$Workers;
			
			return @implode("\n", $f);
			
		}
		
		
		$MustAddCaches=false;
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$chown=$unix->find_program("chown");
		$squidbin=$unix->find_program("squid");
		if($squidbin==null){$squidbin=$unix->find_program("squid3");}					
		//see http://wiki.squid-cache.org/Features/RockStore
		if(!$GLOBALS["NOSQUIDOUTPUT"]){
			if(!$this->IS_32){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid this is not a 3.2 version, RockStore cannot be enabled\n";}return;}
		}
		$params=$this->compilation_params();
		if(!$GLOBALS["NOSQUIDOUTPUT"]){
			if(!preg_match("#rock#", $params["enable-storeio"])){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid RockStore is not enabled in this build ({$params["enable-storeio"]}), please check the --enable-storeio compilation token\n";}return;}
		}
		$sock=new sockets();
		$SquidDisableRockStore=$sock->GET_INFO("SquidDisableRockStore");
		if(!is_numeric($SquidDisableRockStore)){$SquidDisableRockStore=0;}
		if(!$GLOBALS["NOSQUIDOUTPUT"]){
			if($SquidDisableRockStore==1){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid RockStore is disabled\n";}return;}
		}
		if($this->CPU_NUMBER<2){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Only $this->CPU_NUMBER CPU RockStore is disabled\n";}return;}
		$RockStoreDirectory=$sock->GET_INFO("RockStoreDirectory");
		$RockStoreSize=$sock->GET_INFO("RockStoreSize");
		if(!is_numeric($RockStoreSize)){$RockStoreSize=256;}
		if($RockStoreDirectory==null){$RockStoreDirectory="/home/squid/cache-rock";}
		$f[]="workers $this->CPU_NUMBER"; 
		
		if(!$GLOBALS["NOSQUIDOUTPUT"]){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid RockStore $this->CPU_NUMBER CPUs shared cache of {$RockStoreSize}MB on $RockStoreDirectory\n";}
		}
		
			
		
		for($i=0;$i<$this->CPU_NUMBER;$i++){
			$f[]="cache_dir rock $RockStoreDirectory-$i $RockStoreSize max-size=32768"; 
			if(!is_dir($RockStoreDirectory-$i)){
				@mkdir("$RockStoreDirectory-$i",0755,true);
				shell_exec("/bin/chown squid:squid $RockStoreDirectory-$i");
				$MustAddCaches=true;
			}
			
		}
		
		if(!is_dir($RockStoreDirectory)){
			@mkdir("$RockStoreDirectory",0755,true);
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
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if($squidbin==null){$squidbin=trim($unix->find_program("squid3"));}
		if(!is_file($squidbin)){return;}
		exec("$squidbin -v 2>&1",$results);
		while (list ($num, $val) = each ($results)){
			if(preg_match("#Squid Cache: Version.*?([0-9\.]+)#", $val,$re)){
				if($re[1]=="2.7."){$re[1]="2.7.0";}
				if($re[1]=="3.0."){$re[1]="3.0.0";}
				if($re[1]=="3.1."){$re[1]="3.1.0";}
				if($re[1]=="3.2."){$re[1]="3.2.0";}
				if($re[1]=="3.3."){$re[1]="3.3.0";}
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					echo "Starting......: ".date("H:i:s")." Squid : Version (as root) '{$re[1]}'\n";
				}
				$GLOBALS[__FUNCTION__]=$re[1];
				return $re[1];
			}
		}
		
		$version=$unix->CACHE_VERSIONS("APP_SQUID");
		
		if($version<>null){
			$GLOBALS[__FUNCTION__]=$version;
			return $version;
		}
		
	}
	
	public function visible_hostnameF(){
		if($this->EnableRemoteStatisticsAppliance==0){return $this->visible_hostname;}
		$hostid=$this->hostid();
		if(isset($this->nodes_names[$hostid])){return $this->nodes_names[$hostid];}
		return $this->visible_hostname;
	}
	private function hostid(){
		if(!$this->ASROOT){$sock=new sockets();return $sock->GET_INFO("HOSTID");}
		$hostid=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/HOSTID"));
		if($hostid<>null){return $hostid;}
			
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$hostidbin=$unix->find_program("hostid");
		if(is_file($hostidbin)){
			exec("$hostidbin 2>&1",$results);
			$hostid=md5(trim(@implode("", $results).time()));
			if($hostid<>"00000000"){@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);return $hostid;}
		}
			
		$hostid=md5(time());
		@file_put_contents("/etc/artica-postfix/settings/Daemons/HOSTID", $hostid);
		return $hostid;
	}	
	
	
	private function CheckVersion(){
		if(!isset($GLOBALS["intvalVersion"])){$GLOBALS["intvalVersion"]=null;}
		if(  (isset($GLOBALS["SQUID_IS_VERSION"])) && (trim($GLOBALS["intvalVersion"])<>null)   ){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: {$GLOBALS["intvalVersion"]} From memory\n";}
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
			if(!$GLOBALS["NOSQUIDOUTPUT"]){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: FATAL UNABLE TO DETERMINE VERSION\n";}
			}
		}
		
		if($this->intvalVersion>0){
			
		if($re[1]==3){
			if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
				if(!isset($GLOBALS["OUTPUTED_VER"])){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: Is a 3.{$re[2]} version ($this->intvalVersion)\n";}}
			}
			if($re[2]==0){
				$this->IS_30=true;
				$this->IS_31=false;
				$this->IS_32=false;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=true;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;				
				return;
			}
			
			
			if($re[2]==1){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=false;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=false;		
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;						
				return;
			}
			if($re[2]==2){
				$this->IS_30=false;
				$this->IS_31=true;
				$this->IS_32=true;
				$this->IS_33=false;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=true;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=false;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;
				return;
			}
			if($re[2]>=3){
				$this->IS_30=false;
				$this->IS_31=false;
				$this->IS_32=true;
				$this->IS_33=true;
				$this->IS_34=false;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=true;
				$GLOBALS["SQUID_IS_VERSION"][34]=false;
				return;
			}	

			if($re[2]>=4){
				$this->IS_30=false;
				$this->IS_31=false;
				$this->IS_32=true;
				$this->IS_33=true;
				$this->IS_34=true;
				$GLOBALS["SQUID_IS_VERSION"][30]=false;
				$GLOBALS["SQUID_IS_VERSION"][31]=false;
				$GLOBALS["SQUID_IS_VERSION"][32]=true;
				$GLOBALS["SQUID_IS_VERSION"][33]=true;
				$GLOBALS["SQUID_IS_VERSION"][34]=true;
				return;
			}			
			
			
		}
		
		if($re[1]==2){
			if(!isset($GLOBALS["OUTPUTED_VER"])){
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: Is a 2.{$re[2]} version ($this->intvalVersion)\n";}
				}
			}
			$this->IS_30=false;
			$this->IS_31=false;
			$this->IS_32=false;
			$this->IS_33=false;
			$this->IS_34=false;
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
			$GLOBALS["OUTPUTED_VER"]=true;
		
	}
	
	
	function isicap(){
		if(trim($this->SQUID_VERSION)==null){
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			if($this->ASROOT){$this->SQUID_VERSION=$this->root_squid_version();}else{$this->SQUID_VERSION=$users->SQUID_VERSION;}
			$this->SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
			$this->KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		}
		if(!isset($GLOBALS["NOSQUIDOUTPUT"])){writelogs("SQUID:: SQUID_VERSION::=$this->SQUID_VERSION",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		if(preg_match('#([0-9\.]+)#',$this->SQUID_VERSION,$re)){$this->SQUID_VERSION=$re[1];}
		if($this->SQUID_VERSION>=3){
			if($this->SQUID_ICAP_ENABLED){
				if($this->EnableKavICAPRemote==1){return true;}
				
				if($this->KAV4PROXY_INSTALLED){
					return true;
				}else{
					
					$this->kav_accept_why='{error_kavproxy_not_installed}';
				}
			}else{
				
				$this->kav_accept_why='{error_squid_icap_not_compliance}';
			}
		}else{
			writelogs("SQUID:: error_squid_upto_three::version=$this->SQUID_VERSION",__CLASS__.'/'.__FUNCTION__,__FILE__);
			$this->kav_accept_why='{error_squid_upto_three}<br><strong>'.$this->SQUID_VERSION."</strong>";
		}
		
		if(!isset($GLOBALS["NOSQUIDOUTPUT"])){writelogs("SQUID:: version=$this->SQUID_VERSION::",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		
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
			if(class_exists("unix")){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();return @file_get_contents($unix->LOCATE_SQUID_CONF());}
		}		
	
	
	function ParseConfig(){

		
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		
		if($this->ACL_ARP_ENABLED){
			if($this->ASROOT){
					if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP OK, acls with Mac addresses enabled\n";}}
					}else{
						if($this->ASROOT){
							if(!isset($GLOBALS["OUTPUTED_ARP"])){echo "Starting......: ".date("H:i:s")." [MAC]: ARP Not enabled, acls with Mac addresses disabled\n";}
						}
				}
		}
		
		
		$network=null;
		$dns=null;
		$GLOBALS["OUTPUTED_ARP"]=true;
		if($this->ArticaSquidParameters==null){$this->ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');}
		
		if($GLOBALS["VERBOSED"]){echo __LINE__." ArticaSquidParameters ->". strlen($this->ArticaSquidParameters)." bytes<br>\n";}
		
		if(!isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
			$ini->loadString($this->ArticaSquidParameters);
			if(isset($ini->_params)){
				$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]=$ini->_params;
			}
		}else{
			$ini->_params=$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"];
		}
		if(isset($ini->_params)){
			if(!isset($ini->_params["NETWORK"]["HTCP_PORT"])){$ini->_params["NETWORK"]["HTCP_PORT"]=0;}
			if(!isset($ini->_params["NETWORK"]["NODES_NAME"])){$ini->_params["NETWORK"]["NODES_NAME"]=null;}
			
			
			$network=explode(';',$ini->_params["NETWORK"]["cdir"]);
			
			$this->listen_port=$ini->_params["NETWORK"]["LISTEN_PORT"];
			$this->second_listen_port=$ini->_params["NETWORK"]["SECOND_PORT"];
			$this->ICP_PORT=intval(trim($ini->_params["NETWORK"]["ICP_PORT"]));
			$this->HTCP_PORT=intval(trim($ini->_params["NETWORK"]["HTCP_PORT"]));
			$this->visible_hostname=$ini->_params["NETWORK"]["visible_hostname"];
			$this->LDAP_AUTH=$ini->_params["NETWORK"]["LDAP_AUTH"];
			
			$this->SquidLdapAuthEnableGroups=$sock->GET_INFO("SquidLdapAuthEnableGroups");
			$this->LDAP_AUTH=$sock->GET_INFO("SquidStandardLDAPAuth");
			
			if($this->ASROOT){
				include_once("/usr/share/artica-postfix/framework/class.unix.inc");
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				$nginx=$unix->find_program("nginx");
				if(is_file($nginx)){
					$sock=new sockets();
					$DisableNginxProxy=$sock->GET_INFO("DisableNginxProxy");
					if(!is_numeric($DisableNginxProxy)){$DisableNginxProxy=0;}
					if($DisableNginxProxy==0){
						if($this->listen_port==80){$this->listen_port=8080;}
						if($this->ssl_port==443){$this->ssl_port=4443;}
					}
				}
			}
			
			
			
			if($this->ASROOT){
				if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
					echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP_AUTH = $this->LDAP_AUTH\n";
				}
			}
			
			$this->NTLM_AUTH=$ini->_params["NETWORK"]["NTLM_AUTH"];
		
			$this->LDAP_EXTERNAL_AUTH=$ini->_params["NETWORK"]["LDAP_EXTERNAL_AUTH"];
			$this->EnableParentProxy=$ini->_params["NETWORK"]["EnableParentProxy"];
			if(isset($ini->_params["NETWORK"]["prefer_direct"])){$this->prefer_direct=$ini->_params["NETWORK"]["prefer_direct"];}
			if(isset($ini->_params["NETWORK"]["nonhierarchical_direct"])){$this->nonhierarchical_direct=$ini->_params["NETWORK"]["nonhierarchical_direct"];}
			if(isset($ini->_params["NETWORK"]["max_filedesc"])){$this->max_filedesc=$ini->_params["NETWORK"]["max_filedesc"];}
			if(!isset($ini->_params["NETWORK"]["forwarded_for"])){$ini->_params["NETWORK"]["forwarded_for"]="on";}
			
			if(!isset($ini->_params["NETWORK"]["snmp_community"])){$ini->_params["NETWORK"]["snmp_community"]="";}
			if(!isset($ini->_params["NETWORK"]["snmp_enable"])){$ini->_params["NETWORK"]["snmp_enable"]=0;}
			if(!isset($ini->_params["NETWORK"]["snmp_port"])){$ini->_params["NETWORK"]["snmp_port"]=null;}
			if(!isset($ini->_params["NETWORK"]["snmp_access_ip"])){$ini->_params["NETWORK"]["snmp_access_ip"]=null;}
			if(!isset($ini->_params["NETWORK"]["certificate_center"])){$ini->_params["NETWORK"]["certificate_center"]=null;}
			
			
			$this->snmp_community=$ini->_params["NETWORK"]["snmp_community"];
			$this->snmp_enable=$ini->_params["NETWORK"]["snmp_enable"];
			$this->snmp_port=$ini->_params["NETWORK"]["snmp_port"];
			$this->snmp_access_ip=$ini->_params["NETWORK"]["snmp_access_ip"];
			
			$this->certificate_center=$ini->_params["NETWORK"]["certificate_center"];
			$this->ssl_port=$ini->_params["NETWORK"]["SSL_PORT"];
			$this->SSL_BUMP_WHITE_LIST=$ini->_params["NETWORK"]["SSL_BUMP_WHITE_LIST"];
			$this->enable_kavproxy=$ini->_params["KAV"]["enabled"];
			$this->enable_dansguardian=$ini->_params["DANSGUARDIAN"]["enable_dansguardian"];
			$this->alt_listen_port=$ini->_params["NETWORK"]["ALT_PORT"];
			$this->CACHE_SIZE=$ini->_params["CACHE"]["CACHE_SIZE"];
			$this->CACHE_PATH=$ini->_params["CACHE"]["CACHE_PATH"];
			$this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
			$this->CACHE_TYPE=$ini->_params["CACHE"]["CACHE_TYPE"];
			$this->EXTERNAL_LDAP_AUTH_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidExternalAuth")));
			$this->FTP_PARAMS=unserialize(base64_decode($sock->GET_INFO("SquidFTPParams")));
			$this->SSL_BUMP=$ini->_params["NETWORK"]["SSL_BUMP"];
			
			$this->wccp2_enabled=$sock->GET_INFO("SquidWCCPEnabled");
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
			$this->forwarded_for=$ini->_params["NETWORK"]["forwarded_for"];
			
			if(isset($ini->_params["NETWORK"]["ignore_expect_100"])){$this->ignore_expect_100=$ini->_params["NETWORK"]["ignore_expect_100"];}
			
			if(!isset($ini->_params["NETWORK"]["peer_connect_timeout"])){$ini->_params["NETWORK"]["peer_connect_timeout"]=30;}
			$this->peer_connect_timeout=$ini->_params["NETWORK"]["peer_connect_timeout"];
			if(!is_numeric($this->peer_connect_timeout)){$this->peer_connect_timeout=30;}
			
			if(!isset($ini->_params["NETWORK"]["connect_timeout"])){$ini->_params["NETWORK"]["connect_timeout"]=30;}
			$this->connect_timeout=$ini->_params["NETWORK"]["connect_timeout"];
			if(!is_numeric($this->connect_timeout)){$this->connect_timeout=120;}

			if(!isset($ini->_params["NETWORK"]["persistent_request_timeout"])){$ini->_params["NETWORK"]["persistent_request_timeout"]=60;}
			$this->persistent_request_timeout=$ini->_params["NETWORK"]["persistent_request_timeout"];
			if(!is_numeric($this->persistent_request_timeout)){$this->persistent_request_timeout=60;}

			if(!isset($ini->_params["NETWORK"]["client_lifetime"])){$ini->_params["NETWORK"]["client_lifetime"]=60000;}
			$this->client_lifetime=$ini->_params["NETWORK"]["client_lifetime"];
			if(!is_numeric($this->client_lifetime)){$this->client_lifetime=60000;}	
			
			if(!isset($ini->_params["NETWORK"]["dns_timeout"])){$ini->_params["NETWORK"]["dns_timeout"]=120;}
			$this->dns_timeout=$ini->_params["NETWORK"]["dns_timeout"];
			if(!is_numeric($this->dns_timeout)){$this->dns_timeout=120;}

			if(!isset($ini->_params["NETWORK"]["pconn_timeout"])){$ini->_params["NETWORK"]["pconn_timeout"]=120;}
			$this->pconn_timeout=$ini->_params["NETWORK"]["pconn_timeout"];
			if(!is_numeric($this->pconn_timeout)){$this->pconn_timeout=120;}

			
			if(!isset($ini->_params["NETWORK"]["forward_max_tries"])){$ini->_params["NETWORK"]["forward_max_tries"]=30;}
			$this->forward_max_tries=$ini->_params["NETWORK"]["forward_max_tries"];
			if(!is_numeric($this->forward_max_tries)){$this->forward_max_tries=30;}

			if(!isset($ini->_params["NETWORK"]["forward_timeout"])){$ini->_params["NETWORK"]["forward_timeout"]=240;}
			$this->forward_timeout=$ini->_params["NETWORK"]["forward_timeout"];
			if(!is_numeric($this->forward_timeout)){$this->forward_timeout=240;}			
			

			

			if(!isset($ini->_params["NETWORK"]["dead_peer_timeout"])){$ini->_params["NETWORK"]["dead_peer_timeout"]=10;}
			$this->dead_peer_timeout=$ini->_params["NETWORK"]["dead_peer_timeout"];
			if(!is_numeric($this->dead_peer_timeout)){$this->dead_peer_timeout=10;}			
			
			if(!isset($ini->_params["NETWORK"]["shutdown_lifetime"])){$ini->_params["NETWORK"]["shutdown_lifetime"]=30;}
			$this->shutdown_lifetime=$ini->_params["NETWORK"]["shutdown_lifetime"];
			if(!is_numeric($this->shutdown_lifetime)){$this->shutdown_lifetime=10;}			
			
			
			if(!isset($ini->_params["NETWORK"]["read_timeout"])){$ini->_params["NETWORK"]["read_timeout"]=900;}
			$this->read_timeout=$ini->_params["NETWORK"]["read_timeout"];
			if(!is_numeric($this->read_timeout)){$this->read_timeout=900;}

			if(!isset($ini->_params["NETWORK"]["incoming_rate"])){$ini->_params["NETWORK"]["incoming_rate"]=30;}
			$this->incoming_rate=$ini->_params["NETWORK"]["incoming_rate"];
			if(!is_numeric($this->incoming_rate)){$this->incoming_rate=30;}	
			
			
			
			
			if(!is_numeric($this->SquidLdapAuthEnableGroups)){$this->SquidLdapAuthEnableGroups=0;}
			if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
			if(!is_numeric($this->second_listen_port)){$this->second_listen_port=0;}
			if(!is_numeric($this->url_rewrite_bypass)){$this->url_rewrite_bypass=0;}
			if(!is_numeric($this->ICP_PORT)){$this->ICP_PORT=0;}
			if(!is_numeric($this->HTCP_PORT)){$this->HTCP_PORT=0;}
			if(!is_numeric($this->SSL_BUMP_WHITE_LIST)){$this->SSL_BUMP_WHITE_LIST=0;}
			if(!is_numeric($this->nonhierarchical_direct)){$this->nonhierarchical_direct=1;}
			if(!is_numeric($this->prefer_direct)){$this->prefer_direct=0;}
			if(!is_numeric($this->snmp_enable)){$this->snmp_enable=0;}
			if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
			if(!isset($ini->_params["NODES_NAME"])){$ini->_params["NODES_NAME"]=null;}
			
			$this->nodes_names=$ini->_params["NODES_NAME"];
			
				if(is_array($ini->_params)){
					reset($ini->_params);
					
					while (list ($num, $val) = each ($ini->_params)){
						if(preg_match('#cache:(.+)#',$num,$re)){
							if($GLOBALS["VERBOSE"]){echo "Found cache: {$re[1]}<br>\n";}		
							$re[1]=str_replace(" ", "_", $re[1]);
							
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
		if($this->visible_hostname=="proxyweb"){$this->visible_hostname=null;}
		if($this->visible_hostname==null){if(class_exists("unix")){if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();$this->visible_hostname=$unix->hostname_g();}}
		if($this->visible_hostname==null){$this->visible_hostname=$users->hostname;}
		if(!is_numeric($this->LDAP_AUTH)){$this->LDAP_AUTH=0;}
		if(!is_numeric($this->NTLM_AUTH)){$this->NTLM_AUTH=0;}
		if($this->enable_kavproxy==null){$this->enable_kavproxy=0;}
		if($this->enable_dansguardian==null){$this->enable_dansguardian=0;}
		if($this->CACHE_SIZE==null){$this->CACHE_SIZE=2000;}
		if($this->CACHE_PATH==null){$this->CACHE_PATH="/home/squid/cache-default";}
		if($this->CACHE_TYPE==null){$this->CACHE_TYPE="aufs";}
		if($this->SSL_BUMP==null){$this->SSL_BUMP=0;}
		
		if(preg_match("#([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
    		if($re[1]<3){$this->SSL_BUMP=0;}else{if($re[2]<1){$this->SSL_BUMP=0;}}
		}
		
		

		if(is_array($network)){
			while (list ($num, $cidr) = each ($network)){
					if(trim($cidr)==null){continue;}
					$this->network_array[]=$cidr;
				}
		}
		
		$this->FILL_DNS();
		
		
		
		
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
		
		
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if(!isset($this->global_conf_array["request_header_max_size"])){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["reply_header_max_size"])){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		if(!isset($this->global_conf_array["client_request_buffer_max_size"])){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if(!isset($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]=0;}
		
		if(!isset($this->global_conf_array["reply_body_max_size"])){$this->global_conf_array["reply_body_max_size"]=0;}
		if(!isset($this->global_conf_array["positive_dns_ttl"])){$this->global_conf_array["positive_dns_ttl"]="6 hours";}
		if(!isset($this->global_conf_array["negative_dns_ttl"])){$this->global_conf_array["negative_dns_ttl"]="300 seconds";}
		if(!isset($this->global_conf_array["read_ahead_gap"])){$this->global_conf_array["read_ahead_gap"]="1 MB";}
		
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="5000";}
		if($this->global_conf_array["maximum_object_size"]==null){$this->global_conf_array["maximum_object_size"]="300 MB";}
		if($this->global_conf_array["cache_mem"]==null){$this->global_conf_array["cache_mem"]="256 MB";}
		
		if($this->global_conf_array["read_ahead_gap"]==null){$this->global_conf_array["read_ahead_gap"]="1 MB";}
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
		if($this->global_conf_array["maximum_object_size_in_memory"]==null){$this->global_conf_array["maximum_object_size_in_memory"]="100 M";}
		if($this->global_conf_array["cache_mem"]==null){$this->global_conf_array["cache_mem"]="8 M";}
		if($this->global_conf_array["cache_swap_high"]==null){$this->global_conf_array["cache_swap_high"]="97";}
		if($this->global_conf_array["cache_swap_low"]==null){$this->global_conf_array["cache_swap_low"]="95";}

		
		
		
		
		if(!is_numeric($this->global_conf_array["persistent_request_timeout"])){$this->global_conf_array["persistent_request_timeout"]=60;}
		
		if($this->global_conf_array["request_header_max_size"]==null){$this->global_conf_array["request_header_max_size"]="64 KB";}
		if(!is_numeric($this->global_conf_array["request_body_max_size"])){$this->global_conf_array["request_body_max_size"]="0";}
		if($this->global_conf_array["client_request_buffer_max_size"]==null){$this->global_conf_array["client_request_buffer_max_size"]="512 KB";}
		if($this->global_conf_array["reply_header_max_size"]==null){$this->global_conf_array["reply_header_max_size"]="64 KB";}
		
		
		
		if($this->global_conf_array["reply_body_max_size"]==null){$this->global_conf_array["reply_body_max_size"]="0";}
		if(!isset($GLOBALS["NOSQUIDOUTPUT"])){writelogs("Parsing squid.conf ". count($this->global_conf_array)." parameters",__CLASS__.'/'.__FUNCTION__,__FILE__);}
		$CacheMemCentral=0;
		
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}		
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$CacheMemCentral=$sock->GET_INFO("CacheMemCentral");
			if(!is_numeric($CacheMemCentral)){$CacheMemCentral=0;}
			if($CacheMemCentral>0){
				$this->global_conf_array["cache_mem"]="$CacheMemCentral MB";
				
			}
		}
		
		$this->enable_kavproxy=$sock->GET_INFO('kavicapserverEnabled');
		$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
		$this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		
		if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
		if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}
		if(!is_numeric($this->enable_squidguard)){$this->enable_squidguard=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->enable_UfdbGuard=$this->UfdbEnabledCentral;
			$this->EnableKavICAPRemote=$this->AntivirusEnabledCentral;
			$this->enable_cicap=$this->AntivirusEnabledCentral;
			$this->enable_kavproxy=$this->AntivirusEnabledCentral;
			$this->enable_squidclamav=0;
		}		
		if($this->DisableWinbindd==1){$this->EnableKerbAuth=0;$this->NTLM_AUTH=0;}
		
		
		if($this->ASROOT){
			if(!isset($GLOBALS["NOSQUIDOUTPUT"])){
				echo "Starting......: ".date("H:i:s")." [MEM]: Cache Memory: {$this->global_conf_array["cache_mem"]} remote appliance:$this->EnableRemoteStatisticsAppliance,Cache Central:$CacheMemCentral\n";
			}
		}
		
		if($this->SquidUrgency==1){
			$this->enable_cicap=0;
			$this->enable_kavproxy=0;
			$this->enable_squidclamav=0;
			$this->NTLM_AUTH=0;
		}
		
		if($this->SSL_BUMP==0){
			$this->EnableSquidSSLCRTD=0;
		}
		
		
	}
	
	private function FILL_DNS(){
		if(isset($GLOBALS["squid_dns_array"])){
			if(count($GLOBALS["squid_dns_array"])>0){
				$this->dns_array=$GLOBALS["squid_dns_array"];
				return;
			}
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Filling DNS...\n";}
		$filldef=false;
		$dns_array=array();
		$sock=new sockets();
		$this->UtDNSEnable=intval($sock->GET_INFO("UtDNSEnable"));
		
		$q=new mysql_squid_builder();
		
		if($this->UtDNSEnable==1){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Web filter SaaS enabled\n";}
			$sock=new sockets();
			$UtDNSArticaUser=json_decode(base64_decode($sock->GET_INFO("UtDNSArticaUser")));
			if($UtDNSArticaUser->success){
				$dns_array[]=$UtDNSArticaUser->prim;
				$dns_array[]=$UtDNSArticaUser->sec;
				
			}else{
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: Web filter SaaS report failed\n";}
			}
		}
		
		
		if(!$q->TABLE_EXISTS("dns_servers")){
			$filldef=true;
			$q->QUERY_SQL("CREATE TABLE `squidlogs`.`dns_servers` ( `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY , `dnsserver` VARCHAR( 90 ) NOT NULL , `zOrder` SMALLINT( 2 ) NOT NULL, 
						INDEX (`zOrder`), UNIQUE KEY `dnsserver` (`dnsserver`) ) ENGINE=MyISAM;" );
			
		}
		
		if($q->COUNT_ROWS("dns_servers")==0){$filldef=true;}
		
		if($filldef){
			if(isset($GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"])){
				$dns=explode(';',$GLOBALS["SQUID_MEMORYCONF"]["INI_ARRAY"]["NETWORK"]["dns_servers"]);
				if(is_array($dns)){
					while (list ($num, $serv) = each ($dns)){
						if(trim($serv)==null){continue;}
						$dns_array[]=$serv;
						$q->QUERY_SQL("INSERT INTO `dns_servers` (`dnsserver`,`zOrder`) VALUES ('$serv','$num')");
					}
				}
			}
		}
		
		$sql="SELECT dnsserver FROM `dns_servers` ORDER BY `zOrder`";
		$results=$q->QUERY_SQL($sql);
		if($q->ok){
			while ($ligne = mysql_fetch_assoc($results)) {
				$dns_array[]=$ligne["dnsserver"];
			}
		}
		$nameserver=array();
		while (list ($num, $ip) = each ($dns_array) ){$nameserver[$ip]=true;}
		while (list ($ip, $none) = each ($nameserver) ){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [DNS]: $ip\n";}
			$GLOBALS["squid_dns_array"][]=$ip;
			$this->dns_array[]=$ip;
		}
	}
	

	
	private function append_domain(){
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidAppendDomain=trim($sock->GET_INFO("SquidAppendDomain"));
		
		$f[]="# Append domain: SquidAppendDomain = $SquidAppendDomain [".__LINE__."]";
		
		if($SquidAppendDomain==null){
			$MainArray=unserialize(base64_decode($sock->GET_INFO("resolvConf")));
			$SquidAppendDomain=trim($MainArray["DOMAINS1"]);
			$f[]="# Append domain: resolvConf = $SquidAppendDomain [".__LINE__."]";
			if($SquidAppendDomain=="localhost.local"){$SquidAppendDomain=null;}
		}
		
		if($SquidAppendDomain==null){
			$unix=new unix();
			$hostname=trim($unix->hostname_g());
			$hostname2=explode('.',$hostname);
			unset($hostname2[0]);
			$SquidAppendDomain=trim(@implode(".", $hostname2));
			$f[]="# Append domain: $hostname = $SquidAppendDomain [".__LINE__."]";
			
		}
		
		echo "Starting......: ".date("H:i:s")." [ CONF ]: Append netbios names with \"$SquidAppendDomain\"\n";
		if($SquidAppendDomain==null){return @implode("\n", $f);}
		
		$f[]="append_domain .$SquidAppendDomain";
		return @implode("\n", $f);
	}
	
	
	public function cache_deny_array(){
		$sock=new sockets();
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		if($SquidCacheLevel==0){
			return "# Do not cache ALL domains: SquidCacheLevel:$SquidCacheLevel\n";
		}
		
		
		if($this->SquidUrgency==1){
			$acl[]="# Emergency enabled, deny ACL `DoNotCache`";
			return null;
		}
		include_once(dirname(__FILE__)."/class.squid.acls.inc");
		$already=array();
		$acl=array();
		$sql="SELECT pattern,PatternType FROM webfilters_blkwhlts WHERE blockType=4 AND enabled=1";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		
		$acl[]="# Do not cache domains: ". mysql_num_rows($results)." items in ".__CLASS__."/".__FUNCTION__." Line:".__LINE__;
		$acl[]="acl DoNotCache dstdomain s497977761.onlinehome.fr";
		$acl[]="acl DoNotCache dstdomain .articatech.com";
		$acl[]="acl DoNotCache dstdomain .unveiltech.com";
		$acl[]="acl DoNotCache dstdomain .artica.fr";
		$acl[]="acl DoNotCache dstdomain .articatech.net";
		
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
	
	public function backends_list(){
		$tr=array();
		if(isset($GLOBALS["BACKENDS"])){return $GLOBALS["BACKENDS"];}
		$GLOBALS["BACKENDS"][]="127.0.0.1";
		
		
		$sql="SELECT ipsrc FROM squid_balancers WHERE enabled=1";
		$q=new mysql();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: $q->mysql_error\n";}return $GLOBALS["BACKENDS"];}
		if(mysql_num_rows($results)==0){$GLOBALS["BACKENDS"]=array();if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: no Load-balancer to trust...\n";}return $GLOBALS["BACKENDS"];}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["ipsrc"]==null){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: SKIP a null value\n";}
				continue;}
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: Trusting {$ligne["ipsrc"]}\n";}
				$GLOBALS["BACKENDS"][]=$ligne["ipsrc"];
				$tr[]=$ligne["ipsrc"];
		}
		
		
		if(count($tr)==0){
			$GLOBALS["BACKENDS"]=array();
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: no Load-balancer to trust...\n";}
			return;
		}	

			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: ". count($tr)." to trust...\n";}
			return $GLOBALS["BACKENDS"];
		
	}
	
	
	private function _follow_x_forwarded_for(){
		
		$tr=$this->backends_list();
		if(count($tr)==0){return;}
		$sock=new sockets();
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		
		
		$f[]="acl artica_backend_proxys src ".@implode(" ", $tr);
		
		if($SquidAsMasterFollowxForward==1){
			$f[]="follow_x_forwarded_for allow artica_backend_proxys";
			$f[]="follow_x_forwarded_for allow ChildProxysIPs";
		}
		if($this->ICP_PORT>0){
			$f[]="icp_access allow artica_backend_proxys";
			$f[]="icp_access allow ChildProxysIPs";
		}
		
		if($this->HTCP_PORT>0){
			$f[]="htcp_access allow artica_backend_proxys";
			$f[]="htcp_access allow ChildProxysIPs";
		}
		
		$GLOBAL["artica_backend_proxys"]=true;
		
		return @implode("\n", $f);
	}
	
	
	
	private function external_acl_childrens($MacProc=0,$NoNegCache=false,$ForceConf=array()){
		if($this->SquidUrgency==1){return null;}
		$array["CACHE_TIME"]=360;
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		
		$negative_ttl=$array["CACHE_TIME"];
		$ttl=$array["CACHE_TIME"];
		
		if(isset($ForceConf["ttl"])){$ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["negative_ttl"])){$negative_ttl=$ForceConf["ttl"];}
		if(isset($ForceConf["children-startup"])){$external_acl_startup=$ForceConf["children-startup"];}
		if(isset($ForceConf["children-idle"])){$external_acl_idle=$ForceConf["children-idle"];}
		
		
		
		$f[]="ttl=$ttl";
		if(!$NoNegCache){ 
			$f[]="negative_ttl=$negative_ttl";
		}else{
			$f[]="negative_ttl=0";
		}
		if($this->IS_32){
			$f[]="children-startup={$external_acl_startup}";
			$f[]="children-idle={$external_acl_idle}";
			if($MacProc>0){
				$f[]="children-max=$MacProc";
			}
			$f[]="ipv4";
		}else{
			if($external_acl_startup==1){$external_acl_startup=20;}
			if($external_acl_startup>1){
				$f[]="children={$external_acl_startup}";
			}
		}
		
		return @implode(" ", $f);
		
	}
	
	private function external_acl_helper_MacToUid(){
		if($this->SquidUrgency==1){return null;}
		$sock=new sockets();
		$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		$q=new mysql();
		
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$helper="/usr/share/artica-postfix/external_acl_squid.php";
		@chmod($helper,0755);
		
		if($EnableArticaHotSpot==0){
			$count=$q->COUNT_ROWS("hostsusers","artica_backup");
			$ERR[]="# external_acl_helper_MacToUid hostsusers table $count elements";
			if(!$q->ok){$q->mysql_error=str_replace("\n", " ", $q->mysql_error);$ERR[]="# external_acl_helper_MacToUid $q->mysql_error";}
			
	
			$q=new mysql_squid_builder();
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(MAC) as tcount FROM webfilters_nodes WHERE LENGTH(uid)>1"));
			if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
			$ERR[]="# external_acl_helper_MacToUid webfilters_nodes table {$ligne["tcount"]} elements";
			if(!$q->ok){
				$q->mysql_error=str_replace("\n", " ", $q->mysql_error);
				$ERR[]="# external_acl_helper_MacToUid $q->mysql_error";
			}
			
			$count=$count+$ligne["tcount"];
			
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ipaddr) as tcount FROM webfilters_ipaddr WHERE LENGTH(uid)>1"));
			if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
			$count=$count+$ligne["tcount"];
			
			if($count==0){return @implode("\n", $ERR)."\n";}
		
		}else{
			$ArticaSplashHotSpotCacheAuth=$sock->GET_INFO("ArticaSplashHotSpotCacheAuth");
			if(!is_numeric($ArticaSplashHotSpotCacheAuth)){$ArticaSplashHotSpotCacheAuth=60;}
			$array["CACHE_TIME"]=$ArticaSplashHotSpotCacheAuth*60;
		}
		
		
		if($this->is_auth()){$L[]="%LOGIN";}
	
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>{X-Forwarded-For}";
		
		if($this->IS_32){$ipv4=" ipv4";}
		
		
		$external_acl_childrens=$this->external_acl_childrens();
		$f[]="external_acl_type MacToUid  $external_acl_childrens ".@implode(" ", $L)." $helper --mactouid";
		$f[]="acl MacToUid_acl external MacToUid\n";
		$GLOBALS["HTTP_ACCESS"]["MacToUid"]="http_access deny $this->MyStandPort!MacToUid_acl";
		
		if(class_exists("unix")){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			echo "Starting......: ".date("H:i:s")." [MAC]: Mac To uid, building database...\n";
			if($GLOBALS["VERBOSE"]){
				system("$php /usr/share/artica-postfix/exec.squid.quotasbuild.php --verbose");
			}else{
				shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.quotasbuild.php >/dev/null 2>&1 &");
			}
		}
		
		
		return @implode("\n", $f);		
		
	}
	
	private function external_acl_helper_time_quota_acl(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$acl=new squid_acls_quotas_time();
		return $acl->build()."\n";
	}
	
	
	public function external_acl_helper_categories(){
		$GLOBALS["ArtcExtrnCategoryScanner"]=false;
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$array["CACHE_TIME"]=360;
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categories $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [CATZ]: Categories {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return;}
		
		
		$SRCEUI48="%SRCEUI48";
		if(!$this->ACL_ARP_ENABLED){$SRCEUI48=null;}
		if($this->IS_31){$SRCEUI48=null;}
		if($SRCEUI48==null){$SRCEUI48="%%";}
		
		$L[]="%SRC";
		$L[]=$SRCEUI48;
		$L[]="%>{X-Forwarded-For}";
		$CMDILES=@implode(" ", $L);
		
		$GLOBALS["ArtcExtrnCategoryScanner"]=true;
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		$external_acl_childrens=$this->external_acl_childrens(0);
		$sql="SELECT webfilters_sqgroups.*,webfilters_sqacls.aclname
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'categories'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$results=$q->QUERY_SQL($sql);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$rulename=$ligne["aclname"];
			$this->external_acl_helper_categories_items($ligne["ID"],$rulename);
			$f[]="external_acl_type ArtCatz{$ligne["ID"]} $external_acl_childrens $CMDILES %URI /usr/share/artica-postfix/external_acl_dynamic.php --categories {$ligne["ID"]}\n";
		}
		return @implode("\n", $f);
		
	}
	
	private function external_acl_helper_categories_items($gpid,$rulename){
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$tablename=$q->cat_totablename($ligne["pattern"]);
			$HASH[$tablename]="Rule $rulename category {$ligne["pattern"]}";	
		}
		
		@file_put_contents("/etc/squid3/acls/catz_gpid{$gpid}.acl", serialize($HASH));
		
		
	}
	
	
	private function external_acl_helper_nudity_scanner(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$SquidNuditScanParams=unserialize(base64_decode($sock->GET_INFO("SquidNudityScanParams")));
		if(!isset($SquidNuditScanParams["ProcessesNumber"])){$SquidNuditScanParams["ProcessesNumber"]=30;}
		$ProcessesNumber=$SquidNuditScanParams["ProcessesNumber"];
		
		$array["CACHE_TIME"]=360;
		if($this->is_auth()){$L[]="%LOGIN";}
		$q=new mysql_squid_builder();
		$sql="SELECT COUNT( * ) as tcount
			FROM webfilters_sqgroups, webfilters_sqacls, webfilters_sqacllinks
			WHERE webfilters_sqacllinks.aclid = webfilters_sqacls.ID
			AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
			AND webfilters_sqgroups.GroupType = 'NudityScan'
			AND webfilters_sqgroups.enabled =1
			AND webfilters_sqacls.enabled =1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];

		if(!is_numeric($ProcessesNumber)){$ProcessesNumber=$external_acl_children;}
		
		if(!$q->ok){if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [NUDE]: NudityScanner $q->mysql_error\n";}return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [NUDE]: NudityScanner {$ligne["tcount"]} items\n";}
		if($ligne["tcount"]==0){return;}
		if($this->is_auth()){$L[]="%LOGIN";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}	
		$GLOBALS["ArtcExtrnNudeScanner"]=true;
		
		$external_acl_childrens=$this->external_acl_childrens($ProcessesNumber);
		
		$f[]="external_acl_type ArtcExtrnNudeScanner $external_acl_childrens ".@implode(" ", $L)." %URI /usr/share/artica-postfix/picporn.php\n";
		return @implode("\n", $f);
		
		
	}
	private function external_acl_helper_ITChart(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}$sock=new sockets();
		$EnableITChart=$sock->GET_INFO("EnableITChart");
		if(!is_numeric($EnableITChart)){$EnableITChart=0;}
		
		if($EnableITChart==0){return;}
		$array["CACHE_TIME"]=360;
		if($this->is_auth()){$L[]="%LOGIN";}
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		$ProcessesNumber=$external_acl_children;
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		$port=0;
		$ItChartFreeWeb=$sock->GET_INFO("ItChartFreeWeb");
		
		if(preg_match("#(.+?):([0-9]+)#", $ItChartFreeWeb,$re)){
			$sitename=$re[1];
			$port=$re[2];
		}else{
			$sitename=$ItChartFreeWeb;
		}
		
		$acl_SplashPage="acl ItChartPage dstdomain .$sitename";
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $sitename)){
			$acl_SplashPage="acl ItChartPage dst $sitename";
		}
		
		if($port>0){
			$SplashScreenURI="https://$sitename:$port/public.squid.error.php";
		}else{
			$SplashScreenURI="http://$sitename:$port/public.squid.error.php";
		}
		
		$external_acl_childrens=$this->external_acl_childrens($ProcessesNumber,true);
		
		$f[]="#-----------------------------------------------------------------\n";
		$GLOBALS["ArtcExtrnItChart"]=true;
		$f[]="external_acl_type ArtcExtrnItChart  $external_acl_childrens ".@implode(" ", $L)." /usr/share/artica-postfix/external_acl_dynamic.php --itchart\n";
		$f[]="acl ItChartAcl external ArtcExtrnItChart";
		$f[]="$acl_SplashPage";
		$f[]="deny_info $SplashScreenURI?ItChart=yes&request=%o&src=%u ItChartAcl";
		//$f[]="deny_info 511:/etc/squid3/ArtcExtrnItChart.html ItChartAcl";
		$GLOBALS["HTTP_ACCESS"]["ItChart"]="http_access allow ItChartPage\n\nhttp_access deny $this->MyStandPort!CONNECT !ItChartAcl";
		$f[]="#-----------------------------------------------------------------\n";

		$ssp[]="
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<html>
    <head>
    <title>Redirect...</title>
     <meta http-equiv=\"refresh\" content=\"1; URL=$SplashScreenURI?ItChart=yes&request=%o&src=%u\"> 
     </head>
    <body style=\"margin: 0pt auto; height:100%; background:white;\"></body>
</html>";
		
		@file_put_contents("/etc/squid3/ArtcExtrnItChart.html", @implode("\n", $ssp));
		
		
		return @implode("\n", $f);
		
	}
	
	private function external_acl_helper_splash_interface(){
		$ArticaHotSpotInterface=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaHotSpotInterface");
		$ArticaSplashHotSpotPort=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPort");
		$ArticaSplashHotSpotPortSSL=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaSplashHotSpotPortSSL");
		if($ArticaHotSpotInterface==null){$ArticaHotSpotInterface="eth0";}
		
		if(!is_numeric($ArticaSplashHotSpotPort)){$ArticaSplashHotSpotPort=16080;}
		if(!is_numeric($ArticaSplashHotSpotPortSSL)){$ArticaSplashHotSpotPortSSL=16443;}
		
		
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		$ipaddr=$NETWORK_ALL_INTERFACES[$ArticaHotSpotInterface]["IPADDR"];
		return $ipaddr;
	}
	
	
	
	private function external_acl_helper_splash_screen(){
		return;
		if($this->SquidUrgency==1){return null;}
		if($this->EnableSplashScreen==0){return;}
		
		
		if(!$this->ASROOT){return;}
	
		$sock=new sockets();
		$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
		if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
		if($EnableArticaHotSpot==0){return;}
		
		
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$array=unserialize(base64_decode($sock->GET_INFO("HotSpotConfig")));
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$external_acl_helper_splash_interface=$this->external_acl_helper_splash_interface();
		
		if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
		if($this->is_auth()){$L[]="%LOGIN";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}	
		$acl_SplashPage="acl SplashPage dst $external_acl_helper_splash_interface";
		
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];		
	
		$helper="/usr/share/artica-postfix/external_acl_squid.php";
		@chmod($helper,0755);
		
		@mkdir("/var/lib/squid/SplashScreenSessions",0755,true);
		$unix->chown_func("squid","squid", "/var/lib/squid/SplashScreenSessions/*");
		$f[]="\n#----------- Splash Screen authentication mode ----------------";
		
		$external_acl_childrens=$this->external_acl_childrens(0,true);
		$f[]="external_acl_type SplashScreenAuthDef  $external_acl_childrens ".@implode(" ", $L)." %URI $helper --splash session-time={$array["CACHE_TIME"]}";
		$f[]="acl SplashScreenAuthAcl external SplashScreenAuthDef";
		$f[]="$acl_SplashPage";
		
		
		
		$f[]="deny_info 303:$SplashScreenURI?fromproxy=yes&request=%U SplashScreenAuthAcl";
		$GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"]="http_access allow SplashScreenAuthAcl";
		$f[]="#-----------------------------------------------------------------\n";
		
		if($EnableSplashScreenAsObject==0){
			$accesses[]="# ------ SplashScreen Rules ------";
			//$accesses[]="http_access allow SplashPage";
			$accesses[]="http_access deny $this->MyStandPort!SplashScreenAuthAcl CONNECT";
			$accesses[]="http_access deny $this->MyStandPort!SplashPage !SplashScreenAuthAcl";
			$accesses[]="#\n";
		}
		
		$GLOBALS["HTTP_ACCESS"]["SPLASH"]=@implode("\n", $accesses);
		return @implode("\n", $f);
	}
	
	private function external_acl_dynamic_helper(){
		
		$q=new mysql_squid_builder();
		$logprepend="[ACLS]";
		if($GLOBALS["VERBOSE"]){$logprepend="[DYNAACLS]";}
		$sock=new sockets();
		$sql="SELECT COUNT( * ) AS TCOUNT FROM (
				SELECT webfilters_sqaclaccess.aclid, webfilters_sqacllinks.gpid, webfilters_sqgroups.ID
				FROM webfilters_sqaclaccess, webfilters_sqacllinks, webfilters_sqgroups
				WHERE webfilters_sqaclaccess.aclid = webfilters_sqacllinks.aclid
				AND webfilters_sqacllinks.gpid = webfilters_sqgroups.ID
				AND webfilters_sqgroups.GroupType = 'dynamic_acls'
				AND webfilters_sqgroups.enabled = 1
		) AS t";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		if(!$q->ok){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: MySQL Error: $q->mysql_error\n";}
		}
		
		if($ligne["TCOUNT"]==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: Dynamic ACLS ({$ligne["TCOUNT"]} item)\n";}
			return;
		}
		$ACLS_OPTIONS=unserialize(base64_decode($sock->GET_INFO("AclsOptions")));
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];		
		
		if(!isset($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
		if(!isset($ACLS_OPTIONS["CHILDREN_STARTUP"])){$ACLS_OPTIONS["CHILDREN_STARTUP"]=$external_acl_startup;}
		if(!isset($ACLS_OPTIONS["CHILDREN_IDLE"])){$ACLS_OPTIONS["CHILDREN_IDLE"]=$external_acl_idle;}		
		if(!is_numeric($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
		if(!is_numeric($ACLS_OPTIONS["CHILDREN_STARTUP"])){$ACLS_OPTIONS["CHILDREN_STARTUP"]=$external_acl_startup;}
		if(!is_numeric($ACLS_OPTIONS["CHILDREN_IDLE"])){$ACLS_OPTIONS["CHILDREN_IDLE"]=$external_acl_idle;}
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." $logprepend: Dynamic ACLS ({$ligne["TCOUNT"]} item(s))\n";}
		
		
		if($this->is_auth()){$L[]="%LOGIN";}

		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}
		$L[]="%>{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		$L[]="%DST";
		
		
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
		
		
		$ForceConf["ttl"]=$ACLS_OPTIONS["DYN_TTL"];
		$ForceConf["negative_ttl"]=$ACLS_OPTIONS["DYN_TTL"];
		$ForceConf["children-startup"]=$ACLS_OPTIONS["CHILDREN_STARTUP"];
		$ForceConf["children-idle"]=$ACLS_OPTIONS["CHILDREN_IDLE"];
		
		$external_acl_childrens=$this->external_acl_childrens(0,true,$ForceConf);
		
		
		
		$f[]="external_acl_type DynamicAcls ".@implode(" ", $L)." /usr/share/artica-postfix/external_acl_dynamic.php";
		return @implode("\n", $f)."\n";
	}	
	
	
	
	private function external_acl_helper_quota(){
		if($this->SquidUrgency==1){return null;}
		$q=new mysql_squid_builder();
		
		$sql="SELECT COUNT(*) as tcount FROM webfilters_quotas WHERE enabled=1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["tcount"]==0){return;}
		$sock=new sockets();
		$acls=new squid_acls_groups();
		$acls->external_acl_helper_quota_builder();
		$array=unserialize(base64_decode($sock->GET_INFO("SquidQuotasParams")));
		if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
		if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}
		if($array["DISABLE_MODULE"]==1){return;}
		if($array["TEMPLATE"]==null){$array["TEMPLATE"]="ERR_ACCESS_DENIED";}		
		if($this->is_auth()){$L[]="%LOGIN";}else{$L[]="%%";}
		$L[]="%SRC";
		if($this->ACL_ARP_ENABLED){$L[]="%SRCEUI48";}else{$L[]="%SRCEUI48";}
		$L[]="%>{X-Forwarded-For}";
		if($this->IS_32){$ipv4=" ipv4";}
		

		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
				
		if(preg_match("#^ERR_(.+)#", $array["TEMPLATE"],$re)){
			$array["TEMPLATE"]=$re[1];
		}
		
		$array["children-startup"]=5;
		$array["children-idle"]=5;
		$L[]="%DST";
		$external_acl_childrens=$this->external_acl_childrens(0,false,$array);
		$f[]="# ***** Quotas {$ligne["tcount"]} enabled rules ****";
		$f[]="external_acl_type quotas $external_acl_childrens ".@implode(" ", $L)." /usr/share/artica-postfix/external_acl_quota.php";
		$f[]="acl quotas_acl external quotas\n";
		$f[]="deny_info ERR_{$array["TEMPLATE"]} quotas_acl\n";
		$GLOBALS["HTTP_ACCESS"]["QUOTAS"]="http_access deny $this->MyStandPort!quotas_acl all";
		@chmod("/usr/share/artica-postfix/external_acl_quota.php", 0755);
		return @implode("\n", $f);
		
	}
	
	private function request_header_access(){
		if($this->SquidUrgency==1){return null;}
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
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$squidbin=$unix->find_program("squid3");
		if(strlen($squidbin)<5){$squidbin=$unix->find_program("squid");}
		exec("$squidbin -v 2>&1",$results);
		if($GLOBALS["VERBOSE"]){echo "[DEBUG]: $squidbin -v -> ".count($results). "rows\n";}
		$GLOBALS[__FUNCTION__]=$results;
		return $results;
	}
	


	
	
	public function BuildBlockedSites(){
			if($this->SquidUrgency==1){return null;}
			$sql="SELECT uri FROM squid_block ORDER BY ID DESC";
			$q=new mysql();
			$tb=array();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){if(posix_getuid()==0){echo "Starting......: ".date("H:i:s")." [ERR]: Mysql database, error, skip blocked sites\n";}}
			
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
			if(posix_getuid()==0){echo "Starting......: ".date("H:i:s")." [ACLS]: $countdesite blocked site(s)\n";}
			
			if(is_array($tb)){$conf=implode("\n",$tb);}else{$conf="";}
			@file_put_contents("/etc/squid3/squid-block.acl",$conf);
			return $conf;
	}
		
function SaveToLdap($norestart=false){
			if(!$this->kav_accept){
				writelogs("SQUID:: kavaccept = FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__);
				$this->enable_kavproxy=0;
			}
	
			$sock=new sockets();
			if(!is_numeric($this->snmp_port)){$this->snmp_port=3401;}
			$ini=null;
			$ini=$ini."[NETWORK]\n";
			$ini=$ini."cdir=".implode(";",$this->network_array)."\n";
			$ini=$ini."dns_servers=".implode(";",$this->dns_array)."\n";
			$ini=$ini."LISTEN_PORT=$this->listen_port\n";
			$ini=$ini."SECOND_PORT=$this->second_listen_port\n";
			$ini=$ini."ICP_PORT=$this->ICP_PORT\n";
			$ini=$ini."HTCP_PORT=$this->HTCP_PORT\n";
			$ini=$ini."SSL_PORT=$this->ssl_port\n";
			$ini=$ini."certificate_center=$this->certificate_center\n";
			$ini=$ini."SSL_BUMP_WHITE_LIST=$this->SSL_BUMP_WHITE_LIST\n";

			$ini=$ini."peer_connect_timeout=$this->peer_connect_timeout\n";
			$ini=$ini."connect_timeout=$this->connect_timeout\n";
			$ini=$ini."persistent_request_timeout=$this->persistent_request_timeout\n";
			$ini=$ini."client_lifetime=$this->client_lifetime\n";
			$ini=$ini."pconn_timeout=$this->pconn_timeout\n";
			$ini=$ini."dns_timeout=$this->dns_timeout\n";
			
			$ini=$ini."dead_peer_timeout=$this->dead_peer_timeout\n";
			$ini=$ini."shutdown_lifetime=$this->shutdown_lifetime\n";
			$ini=$ini."read_timeout=$this->read_timeout\n";
			$ini=$ini."incoming_rate=$this->incoming_rate\n";
			
			
			
			$ini=$ini."EnableParentProxy=$this->EnableParentProxy\n";
			$ini=$ini."prefer_direct=$this->prefer_direct\n";
			$ini=$ini."nonhierarchical_direct=$this->nonhierarchical_direct\n";
			$ini=$ini."max_filedesc=$this->max_filedesc\n";
			$ini=$ini."snmp_community=$this->snmp_community\n";
			$ini=$ini."snmp_enable=$this->snmp_enable\n";
			$ini=$ini."snmp_port=$this->snmp_port\n";
			$ini=$ini."snmp_access_ip=$this->snmp_access_ip\n";
	
			
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
			$ini=$ini."forwarded_for=$this->forwarded_for\n";
			
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [VER]: is 3.2.x OK\n";}}
			
			if(($this->IS_30) && (!$this->IS_32)){
				$ini=$ini."ignore_expect_100=$this->ignore_expect_100\n";
			}
			
			
			
			
			$ini=$ini."ALT_PORT=$this->alt_listen_port\n";
			$ini=$ini."visible_hostname=$this->visible_hostname\n";
			
			
			
			writelogs("Save LDAP_AUTH=$this->LDAP_AUTH",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$sock->SET_INFO("SquidStandardLDAPAuth", $this->LDAP_AUTH);
			$sock->SET_INFO("SquidLdapAuthEnableGroups", $this->SquidLdapAuthEnableGroups);
			$ini=$ini."LDAP_AUTH=$this->LDAP_AUTH\n";
			$ini=$ini."NTLM_AUTH=$this->NTLM_AUTH\n";
			$ini=$ini."LDAP_EXTERNAL_AUTH=$this->LDAP_EXTERNAL_AUTH\n";
			$ini=$ini."SSL_BUMP=$this->SSL_BUMP\n";
			
			
			$this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
			$ini=$ini."[KAV]\n";
			$ini=$ini."enabled=$this->enable_kavproxy\n";
			$ini=$ini."[DANSGUARDIAN]\n";
			$ini=$ini."enable_dansguardian=$this->enable_dansguardian\n";
			$ini=$ini."[CACHE]\n";
			$ini=$ini."CACHE_SIZE=$this->CACHE_SIZE\n";
			$ini=$ini."CACHE_PATH=$this->CACHE_PATH\n";
			$ini=$ini."CACHE_TYPE=$this->CACHE_TYPE\n";
			
			if(is_array($this->nodes_names)){
				reset($this->nodes_names);
				$ini=$ini."[NODES_NAME]\n";
				while (list ($num, $val) = each ($this->nodes_names)){
					$ini=$ini."$num=$val\n";
				}
			}
							
	

			if(is_array($this->cache_list)){
				reset($this->cache_list);
				$num=null;
				$val=null;
				while (list ($num, $val) = each ($this->cache_list)){
					$num=str_replace(" ", "_", $num);
					$ini=$ini."[cache:$num]\n";
					$ini=$ini."cache_type={$val["cache_type"]}\n";
					$ini=$ini."cache_dir_level1={$val["cache_dir_level1"]}\n";
					$ini=$ini."cache_dir_level2={$val["cache_dir_level2"]}\n";
					$ini=$ini."cache_size={$val["cache_size"]}\n";
					$ini=$ini."cache_maxsize={$val["cache_maxsize"]}\n";
				}
			}else{
				writelogs("cache_list, not an array...",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);
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
			$sock->SET_INFO("EnableAdZapper",$this->enable_adzapper);
			$sock->SET_INFO("EnableSquidClamav",$this->enable_squidclamav);
			$sock->SET_INFO("KavMetascannerEnable",$this->enable_metascanner);
			$sock->SET_INFO("SquidEnableStreamCache",$this->enable_streamcache);
			$sock->SET_INFO("SquideCapAVEnabled",$this->enable_ecapav);
			
			
			
			
			$sock->SaveConfigFile(base64_encode(serialize($this->EXTERNAL_LDAP_AUTH_PARAMS)),"SquidExternalAuth");
			$sock->SaveConfigFile(base64_encode(serialize($this->FTP_PARAMS)),"SquidFTPParams");
			$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
			if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
			
			if(class_exists("usersMenus")){
				$users=new usersMenus();
				if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
			}
			
			if($this->enable_dansguardian==1){
				writelogs("",__FUNCTION__,__FILE__);
				writelogs("Save Dansguardian configuration...",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$dans=new dansguardian();
				$dans->SaveSettings();
			}
			
			if($this->enable_kavproxy==1){
				$sock->getFrameWork("cmd.php?kav4proxy-reconfigure=yes");
			}
			
			if($EnableWebProxyStatsAppliance==1){
				$sock=new sockets();
				$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
				return true;
			}	
			
			if(!$norestart){
				$sock->getFrameWork("squid.php?build-smooth=yes");
			}
			return true;
			
		}
		
		function SaveToServer($norestart=false){
			$sock=new sockets();
			$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
			if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
			
			if(class_exists("usersMenus")){
				$users=new usersMenus();
				if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
			}		

			if($EnableWebProxyStatsAppliance==1){
				$sock=new sockets();
				$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
				return;
			}
			
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

		
		public function is_auth(){
			if($this->SquidUrgency==1){return false;}
			if(isset($GLOBALS["squid_is_auth"])){return $GLOBALS["squid_is_auth"];}
			
			$GLOBALS["squid_is_auth"]=$this->_is_auth();
			
		}

		private function _is_auth(){
			if($this->SquidUrgency==1){return false;}
			if($this->LDAP_AUTH==1){return true;}
			if($this->LDAP_EXTERNAL_AUTH==1){return true;}
			if($this->EnableKerbAuth==1){return true;}
			$sock=new sockets();
			$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
			if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
			if($EnableArticaHotSpot==1){return true;}
			$acl=new squid_acls();
			if($acl->IsObjectAuth()){return true;}
			return false;
		}
		
		
		private function ldap_auth_conf($NokerbCheck=false){
			if($this->SquidUrgency==1){return null;}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP_AUTH = $this->LDAP_AUTH\n";}
			if(!is_array($this->global_conf_array)){$this->ParseConfig();}
			if($GLOBALS["VERBOSE"]){if(!$this->ASROOT){$GLOBALS["VERBOSE"]=false;}}
			$users=new usersMenus();
			$sock=new sockets();
			$childrens=$this->SquidClientParams["auth_param_basic_children"];
			$startup=$this->SquidClientParams["auth_param_basic_startup"];
			$idle=$this->SquidClientParams["url_rewrite_idle"];
			if($this->IS_32){$auth_param_child=" startup={$startup} idle={$idle}";}
			$auth_param_basic_children=$this->SquidClientParams["auth_param_basic_children"];
			
			
			if(!$NokerbCheck){
				if($this->EnableKerbAuth==1){
						if($this->LDAP_AUTH==0){
							if(is_file($users->SQUID_NTLM_AUTH)){
								$conf[]= "#Basic authentication for other browser that did not supports NTLM: (KerbAuthMethod = $KerbAuthMethod )";
								$conf[]= "auth_param basic program $users->SQUID_NTLM_AUTH --helper-protocol=squid-2.5-basic";
								$conf[]= "auth_param basic children {$auth_param_basic_children}$auth_param_child";
								$conf[]= "auth_param basic realm Basic Identification";
								$conf[]= "auth_param basic credentialsttl 2 hours\n";
								$conf[]= "";
							}else{
								$conf[]= "#Basic authentication SQUID_NTLM_AUTH no such binary\n";
								
							}
						return @implode("\n", $conf);
						}
					
				}
				if($GLOBALS["VERBOSE"]){
					echo "DEBUG : LDAP_AUTH:$this->LDAP_AUTH\n";
					echo "DEBUG : LDAP_EXTERNAL_AUTH:$this->LDAP_EXTERNAL_AUTH\n";
				}
			}
			
			if($this->LDAP_AUTH==0){
				if($this->LDAP_EXTERNAL_AUTH<>1){
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: LDAP authentication method is disabled\n";}
					return "#--------- LDAP authentication method is disabled\n";
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
			$auth_banner="Basic credentials, Please logon...";
			$SquidLdapAuthBanner=$sock->GET_INFO("SquidLdapAuthBanner");
			if($SquidLdapAuthBanner<>null){$auth_banner=$SquidLdapAuthBanner;}
			
			if($this->LDAP_EXTERNAL_AUTH==1){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: Using remote LDAP database\n";}
				$ldap_host=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_server"];
				$ldap_port=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_port"];	
				$ldap_password=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_password"];
				$ldap_admin=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_user"];		
				$suffix=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_suffix"];
				$user_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_users"];
				$group_filter=$this->EXTERNAL_LDAP_AUTH_PARAMS["ldap_filter_group"];
				$auth_banner=$this->EXTERNAL_LDAP_AUTH_PARAMS["auth_banner"];
				$this->SquidLdapAuthEnableGroups=1;					
			}else{
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: Using local LDAP database\n";}
			}
			
			

					

			
			$SQUID_LDAP_AUTH=$this->squid_ldap_auth_path();
			$squid_ldap_group_path=$this->squid_ldap_group_path();
			$conf[]= "#--------- LDAP AUTH settings\n";
			$conf[]= "#Authentification mode, building using squid compiled for $ldap_host:$ldap_port";
			if(trim($SQUID_LDAP_AUTH)<>null){
				
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: Using User:`$SQUID_LDAP_AUTH`, Group `$squid_ldap_group_path`\n";}
				$conf[]= "auth_param basic program $SQUID_LDAP_AUTH -b \"$suffix\" -D \"$ldap_admin\" -w \"$ldap_password\" -f \"$user_filter\" -v 3 -h $ldap_host -p $ldap_port";
				$conf[]= "#--------- GLOBAL";
				if($this->SquidLdapAuthEnableGroups==1){
					$conf[]= "external_acl_type ldap_group %LOGIN " . $this->squid_ldap_group_path()." -D \"$ldap_admin\" -w \"$ldap_password\" -b \"$suffix\"  -f \"$group_filter\" -S -v 3 -h $ldap_host -p $ldap_port";
				}
				$conf[]= "auth_param basic children {$childrens}$auth_param_child";
				$conf[]= "auth_param basic credentialsttl 2 hour";
				$conf[]= "auth_param basic realm $auth_banner";
				$conf[]= "authenticate_ttl 1 hour";
				$conf[]= "authenticate_ip_ttl 60 seconds";					
				$conf[]= "acl ldapauth proxy_auth REQUIRED";
				$conf[]=$this->WHITELISTED_AUTH_BROWSERS();
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access allow {$this->MyStandPort}ldapauth";
				
				$conf[]="";
			}else{
				$conf[]= "#No LDAP auth_param basic program found !\n";
				$conf[]="";
			}

			return implode("\n",$conf);
			
		}
		
		private function ACL_FTP_RESTRICTIONS(){
			if($this->SquidUrgency==1){return null;}
			if($this->enable_ftp_restrictions<>1){return;}
			return "acl clients_ftp src \"/etc/squid3/clients_ftp.acl\"\n";
		}
		
		private function squid_ldap_group_path(){
			if($this->SquidUrgency==1){return null;}
			$basic=$this->squid_ldap_auth_path();
			if(strlen($basic)==0){return;}
			$basic=dirname($basic);
			if(is_file("$basic/squid_ldap_group")){return "$basic/squid_ldap_group";}
			if(is_file("$basic/ext_ldap_group_acl")){return "$basic/ext_ldap_group_acl";}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [AUTH]: WARNING : no ext group found in `$basic` path !!!\n";}
			
			
			
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
		
		
		private function squid_ext_session_acl_path(){
			if(is_file('/lib/squid3/ext_session_acl')){return '/lib/squid3/ext_session_acl';}
			if(is_file('/usr/lib/squid3/ext_session_acl')){return '/usr/lib/squid3/ext_session_acl';}
			if(is_file('/usr/lib64/squid3/ext_session_acl')){return '/usr/lib64/squid3/ext_session_acl';}
			if(is_file('/lib/squid3/ext_session_acl')){return '/lib/squid3/ext_session_acl';}
			if(is_file('/lib64/squid3/ext_session_acl')){return '/lib64/squid3/ext_session_acl';}
			if(is_file('/usr/lib/squid/ext_session_acl')){return '/usr/lib/squid/ext_session_acl';}
			if(is_file('/usr/lib/squid/ext_session_acl')){return '/usr/lib/squid/ext_session_acl';}
			if(is_file('/usr/lib64/squid/ext_session_acl')){return '/usr/lib64/squid/ext_session_acl';}
			if(is_file('/usr/lib64/squid/ext_session_acl')){return '/usr/lib64/squid/ext_session_acl';}
			if(is_file('/usr/local/lib/squid/ext_session_acl')){return '/usr/local/lib/squid/ext_session_acl';}
			if(is_file('/usr/local/lib64/squid/ext_session_acl')){return '/usr/local/lib64/squid/ext_session_acl';}
			if(is_file('/opt/artica/libexec/ext_session_acl')){return '/opt/artica/libexec/ext_session_acl';} 				
		}
	
		
		public function ACL_BANNED_COMPUTERS_IP(){
			if($this->SquidUrgency==1){return null;}
			@file_put_contents("/etc/squid3/banned-computers-by-mac.acl","");
			@file_put_contents("/etc/squid3/banned-computers.acl","");
			
			
			$f=array();
			$MC=array();
			if(isset($GLOBALS["ACL_BANNED_COMPUTERS_IP"])){return $GLOBALS["ACL_BANNED_COMPUTERS_IP"];}
			$returned=null;
			$sock=new sockets();
			if(!$this->ACL_ARP_ENABLED){
				echo "Starting......: ".date("H:i:s")." [MAC]: acl ARP are disabled\n";
			}else{
				echo "Starting......: ".date("H:i:s")." [MAC]: acl ARP are enabled\n";
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
					echo "Starting......: ".date("H:i:s")." [MAC]: NO Mac address for {$ligne["pattern"]}\n";
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
			
			$returned=null;
			if(count($f)>0){
				$f[]="";
				$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS"]="http_access deny {$this->MyStandPort}banned_computers";
				@file_put_contents("/etc/squid3/banned-computers.acl",@implode("\n",$f));
				$returned="acl banned_computers src \"/etc/squid3/banned-computers.acl\"\n";
			}
			
			if(count($MC)>0){
				
				$GLOBALS["HTTP_ACCESS"]["BANNED_COMPUTERS_MAC"]="http_access deny {$this->MyStandPort}banned_mac_computers";
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
			echo "Starting......: ".date("H:i:s")." Squid : Checking whitelisted addresses\n";
			if(isset($GLOBALS["ACL_WHITE_COMPUTERS_IP"])){echo "Starting......: ".date("H:i:s")." [ACLS]: Checking whitelisted addresses Already done.\n";return $GLOBALS["ACL_WHITE_COMPUTERS_IP"];}
			
			
			$returned=null;
			$sock=new sockets();
			if(!$this->ACL_ARP_ENABLED){
				echo "Starting......: ".date("H:i:s")." [MAC]: acl ARP are disabled\n";
			}else{
				echo "Starting......: ".date("H:i:s")." [MAC]: acl ARP are enabled\n";
			}
			
			$sql="SELECT ID,pattern,uid FROM dansguardian_files WHERE filename='exceptioniplist' AND RuleID=1 ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			$numnum=mysql_num_rows($results);
			echo "Starting......: ".date("H:i:s")." [ACLS]: Checking whitelisted $numnum entries\n";
			
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
					echo "Starting......: ".date("H:i:s")." [MAC]: NO Mac address for {$ligne["pattern"]}\n";
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
			
			echo "Starting......: ".date("H:i:s")." [ACLS]: ". count($f)." whitelisted addresse(s)\n";
			
			if(count($f)>0){
				$f[]="";
				$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS"]="http_access allow {$this->MyStandPort}whitelisted_computers";
				$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny {$this->MyStandPort}whitelisted_computers";
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
						$myStandPort=$this->MyStandPort;
						if(preg_match("#MyPort[0-9]+#", $line)){$myStandPort=null;}
						$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny $myStandPort$line";
					}
				}
				if($GLOBALS["VERBOSE"]){echo "url_rewrite_access_deny SquidBubbleMode:$this->SquidBubbleMode\n";}
				if($this->SquidBubbleMode==1){
					$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
					$q=new mysql_squid_builder();
					$results = $q->QUERY_SQL($sql);
					while ($ligne = mysql_fetch_assoc($results)) {
						if($GLOBALS["VERBOSE"]){echo "url_rewrite_access_deny SquidBubbleMode:Port:{$ligne["aclport"]}\n";}
						$acls_deny=$acls->buildacls_bytype("url_rewrite_access_deny",$ligne["aclport"]);
						$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="# BUBBLE {$ligne["portname"]} ". __FUNCTION__."({$ligne["aclport"]}) ".count($acls_deny)." items $acls->error";
						if(count($acls_deny)>0){
							$aclport=$ligne["aclport"];
							while (list ($index, $line) = each ($acls_deny) ){
								$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny MyPort{$aclport} $line";
							}
						}
					}
				}
			}			
			
			
			$GLOBALS["ACL_WHITE_COMPUTERS_IP"]=$returned;
			$GLOBALS[__FUNCTION__]=$returned;
			return $returned;
			
		}		
		
		private function WCCP(){
			$ipClass=new IP();
			if($this->wccp2_enabled<>1){return "#Cisco's Web Cache Coordination Protocol is not enabled\n";}
			$conf[]="";	
			$sock=new sockets();
			
			$wccp2_router=array();
			$wccp2_routers=$this->WCCPHash["wccp2_router"];
			if(strpos($wccp2_routers, ",")>0){
				$wccp2_router=explode(",",$wccp2_routers);
			}else{
				$wccp2_router[]=$wccp2_routers;
			}
			
			$conf[]="#Cisco's Web Cache Coordination Protocol";
			while (list ($none, $ipaddr) = each ($wccp2_router) ){
				if($ipClass->isValid($ipaddr)){
					$conf[]="wccp2_router $ipaddr";
				}
				
			}
			
			$conf[]="wccp2_address {$this->WCCPHash["listen_address"]}";
			$conf[]="wccp2_forwarding_method {$this->WCCPHash["wccp2_forwarding_method"]}";
			$conf[]="wccp2_return_method {$this->WCCPHash["wccp2_return_method"]}";
			$conf[]="wccp2_assignment_method {$this->WCCPHash["wccp2_assignment_method"]}";
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
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$unix->chown_func("root","root" ,$pinger);
			$unix->chmod_func(04755, $pinger);
			
		}
		
		
		public function ACL_MESSENGERS(){
			if($this->SquidUrgency==1){return null;}
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
				$GLOBALS["HTTP_ACCESS"]["AOL_MESSENGERS"]="http_access allow {$this->MyStandPort}AIM_methods AIM_ports AIM_nets\nhttp_access allow AIM_methods AIM_ports AIM_hosts";				
			}
			
			if($SquidMessengers["IRC"]==1){	
				$conf[]="";		
				$conf[]="# Permit IRC";
				$conf[]="acl IRC_ports port 6667";
				$conf[]="acl IRC_domains dstdomain .freenode.net";
				$conf[]="acl IRC_hosts dstdomain  irc.freenode.net";
				$conf[]="acl IRC_methods method CONNECT";		
				$GLOBALS["HTTP_ACCESS"]["IRC_MESSENGERS"]="http_access allow {$this->MyStandPort}IRC_methods IRC_ports IRC_hosts\nhttp_access allow IRC_methods IRC_ports IRC_domains";
				}
		
			if($SquidMessengers["YAHOO"]==1){
				$conf[]="";				
				$conf[]="# Permit Yahoo Messenger";
				$conf[]="acl YIM_ports port 5050";
				$conf[]="acl YIM_domains dstdomain .yahoo.com .yahoo.co.jp";
				$conf[]="acl YIM_hosts dstdomain scs.msg.yahoo.com cs.yahoo.co.jp";
				$conf[]="acl YIM_methods method CONNECT";
				$GLOBALS["HTTP_ACCESS"]["YAHOO_MESSENGERS"]="http_access allow {$this->MyStandPort}YIM_methods YIM_ports YIM_hosts\nhttp_access allow YIM_methods YIM_ports YIM_domains";
			}
			
			if($SquidMessengers["GOOGLE"]==1){
				$conf[]="";
				$conf[]="# Permit Google Talk";
				$conf[]="acl GTALK_ports port 5222 5050 443";
				$conf[]="acl GTALK_domains dstdomain .google.com";
				$conf[]="acl GTALK_hosts dstdomain talk.google.com";
				$conf[]="acl GTALK_methods method CONNECT";		

				
				
				$GLOBALS["HTTP_ACCESS"]["GOOGLE_MESSENGERS"]="http_access allow  {$this->MyStandPort}GTALK_ports GTALK_hosts GTALK_methods\n
				http_access allow {$this->MyStandPort}GTALK_methods GTALK_ports GTALK_domains";
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
				$SquidMessengers_httpaccess[]="http_access allow {$this->MyStandPort}MSN_ports MSN_domains MSN_methods";
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
		
    private function transparent_token(){
    	$_transparent=" transparent";
    	if($this->IS_30){$_transparent=" transparent";}
    	if($this->IS_31){$_transparent=" intercept";}
    	if($this->IS_32){$_transparent=" intercept";}
    	if($this->IS_33){$_transparent=" intercept";}
    	if($this->IS_34){$_transparent=" intercept";}
    	if($this->UseTProxyMode==1){$_transparent=" tproxy";}   	
    	return $_transparent;
    }		

    private function CoovaChilliAcls(){
    	if($this->SquidUrgency==1){return null;}
    	if(!$this->CoovaChilliEnabled()){return false;}
    	$ChilliConf=$this->CoovaChilliConf();
    	if(!is_numeric($ChilliConf["SQUID_HTTP_PORT"])){return;}
    	$http_port=$ChilliConf["SQUID_HTTP_PORT"];
    	$token=$this->myport_token();
    	$f[]="acl ChilliPort $token $http_port";
    	return @implode("\n", $f);
    }
    
    private function myport_token(){
    	$token="myport";
    	if($this->IS_30){$token="myportname";}
    	if($this->IS_33){$token="localport";}
    	if($this->IS_34){$token="localport";}
    	return $token;
    }
    
    private function memory_pools(){
    	$sock=new sockets();
    	$SquidMemoryPools=$sock->GET_INFO("SquidMemoryPools");
    	
    	if(!is_numeric($SquidMemoryPools)){$SquidMemoryPools=1;}
    	if($SquidMemoryPools==0){return "memory_pools off";}
    	
    	$memory_pools_limit_suffix=null;
    	$SquidMemoryPoolsLimit=intval($sock->GET_INFO("SquidMemoryPoolsLimit"));
    	if($SquidMemoryPoolsLimit>0){$memory_pools_limit_suffix=" MB";}
    	$f[]="memory_pools on";
    	if($SquidMemoryPoolsLimit==0){
    		$f[]="memory_pools_limit none";
    	}else{
    		$f[]="memory_pools_limit $SquidMemoryPoolsLimit$memory_pools_limit_suffix";
    	}
    	return @implode("\n", $f);
    }
    
    
    
    private function CoovaChilli_tcp_outgoing_address(){
    	if($this->SquidUrgency==1){return null;}
    	return null;
    	if(!$this->CoovaChilliEnabled()){return false;}
    	$ChilliConf=$this->CoovaChilliConf();
    	if(!is_numeric($ChilliConf["SQUID_HTTP_PORT"])){return;}
    	
    	$nics=new system_nic($ChilliConf["HS_WANIF"]);
    	$ListenIP=null;
    	if($nics->IPADDR<>null){$ListenIP=$nics->IPADDR;}
    	$f[]="# Coova outgoing Interface:{$ChilliConf["HS_WANIF"]} [$ListenIP]";
    	$f[]="tcp_outgoing_address $ListenIP ChilliPort";
    	return @implode("\n", $f);
    }
    
		
	private function CoovaTransparent(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->CoovaChilliEnabled()){return false;}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$ListenIP="0.0.0.0";
		//$ListenIP="127.0.0.1";
		$ChilliConf=$this->CoovaChilliConf();
		$http_port=$ChilliConf["SQUID_HTTP_PORT"];
		$https_port=$ChilliConf["SQUID_HTTPS_PORT"];		
	/*	$nics=new system_nic($ChilliConf["HS_LANIF"]);
		if($nics->IPADDR<>null){$ListenIP=$nics->IPADDR;}
		if(!is_numeric($ChilliConf["SQUID_HTTP_PORT"])){return;}
		*** Test made using the Wan interface and did not working. ***
		*
		*
	*/	
		
		$params=$this->compilation_params();
		if(!isset($params["enable-ssl"])){
			echo "Starting......: ".date("H:i:s")." [HOT]: Coova, FATAL: Not compiled...\n";
			$https_port=0;
		}		
		
		
		$ListenIPTXT="$ListenIP:";
		$transparent_token=$this->transparent_token();
		
		$transparent_token=null;
		$ListenIPTXT=null;
		
		if($http_port>0){		
			$this->MyPortsArray[$http_port]=true;
			
			$http_port_line[]="http_port $ListenIPTXT$http_port $transparent_token";
			echo "Starting......: ".date("H:i:s")." [HOT]: HTTP: $ListenIPTXT$http_port $transparent_token\n";
		}
		
		if($https_port>0){
			echo "Starting......: ".date("H:i:s")." [HOT]: HTTPS: $ListenIP:$https_port\n";
			$this->MyPortsArray[$https_port]=true;
			if($ChilliConf["certificate_center"]<>null){
				echo "Starting......: ".date("H:i:s")." [HOT]: SSL Chilli {$ChilliConf["certificate_center"]}\n";
				$certificates=$this->SaveCertificate($ChilliConf["certificate_center"]);
			}else{
				echo "Starting......: ".date("H:i:s")." [HOT]: SSL No certificate set for the proxy itself, set the default one\n";
				$certificates=$this->SaveCertificateDefault();
			}
			
			$ssltoken=$this->ssl_bump_token();
			$ssltoken=null;
			$this->TRANSPARENT_PORTS_LIST[]=$https_port;
			$http_port_line[]=trim("https_port $ListenIPTXT$https_port $transparent_token $ssltoken $certificates");
			echo "Starting......: ".date("H:i:s")." [HOT]: SSL: $ListenIPTXT$https_port $transparent_token $ssltoken $certificates\n";
			
		}else{
			echo "Starting......: ".date("H:i:s")." [HOT]: SSL Not configured\n";
		}
				
		return @implode("\n",$http_port_line);
		
	}

	private function CoovaChilliConf(){
		if(isset($GLOBALS["ChilliConf"])){return $GLOBALS["ChilliConf"];}
		$sock=new sockets();
		$ChilliConf=unserialize(base64_decode($sock->GET_INFO("ChilliConf")));
		$GLOBALS["ChilliConf"]=$ChilliConf;
		return $GLOBALS["ChilliConf"];
	}
		
	private function CoovaChilliEnabled(){
		if($this->SquidUrgency==1){return false;}
		if($this->ChilliEnabled){return true;}
		if(isset($GLOBALS["ChilliEnabled"])){return $GLOBALS["ChilliEnabled"];}
		if(!$this->ASROOT){return;}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$chilli=$unix->find_program("chilli");
		if(!is_file($chilli)){$GLOBALS["ChilliEnabled"]=false;return;}
		$sock=new sockets();
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}
		if($EnableChilli==1){$GLOBALS["ChilliEnabled"]=true;$this->ChilliEnabled=true;return true;}
		$GLOBALS["ChilliEnabled"]=false;
		return false;
	}
			
			
			
		
	public function WHITELISTED_AUTH(){
		if($this->SquidUrgency==1){return null;}
		if(isset($GLOBALS["WHITELISTED_AUTH_ARRAY"])){return $GLOBALS["WHITELISTED_AUTH_ARRAY"];}
		$q=new mysql();
		$l=array();
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: check whitelisted site(s)\n";}
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
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: ". count($l)." whitelisted site(s)\n";}
			$GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"]="http_access allow {$this->MyStandPort}WhiteListAuth";
			$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"][]="url_rewrite_access deny {$this->MyStandPort}WhiteListAuth";
			$GLOBALS["ICAP"]["WHITEAUTH"]="adaptation_access ANTIVIRUS_CHAINS deny {$this->MyStandPort}WhiteListAuth";			
		}
		
			$GLOBALS["WHITELISTED_AUTH_ARRAY"]=@implode("\n",$l);
		
			return $GLOBALS["WHITELISTED_AUTH_ARRAY"];
		}
		
		
		public function StringToRegex($pattern){
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
			if($this->SquidUrgency==1){return null;}
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
			if($this->SquidUrgency==1){return null;}
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
		
	private function ACL_HOTSPOT_URIS(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		$acls=array();
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
				
		$sql="SELECT servername FROM freeweb WHERE groupware='SPLASHSQUID'";
		
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=$ligne["servername"];
			$acls[]=".$servername";
		}
		if(count($acls)==0){$GLOBALS[__CLASS__][__FUNCTION__]=null;return;}
		@file_put_contents("/etc/squid3/acls/SplashScreens.txt", @implode("\n", $acls)."\n");
		$results="acl SplashScreens dstdomain \"/etc/squid3/acls/SplashScreens.txt\"\n";
		$GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"]="http_access allow {$this->MyStandPort}SplashScreens";
		$GLOBALS[__CLASS__][__FUNCTION__]=$results;
		return $results;
		
	}
		
	private function ACL_TEMPLATES_URIS(){
		if($this->SquidUrgency==1){return null;}
		if(!$this->ASROOT){return;}
		if(isset($GLOBALS[__CLASS__][__FUNCTION__])){return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);}
		$q=new mysql_squid_builder();
		$sql="SELECT template_uri FROM squidtpls WHERE template_link=1";
		$results = $q->QUERY_SQL($sql);
		$acls=array();
		$ALREADY=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			if(trim($ligne["template_uri"])==null){return;}
			
			$uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($ligne["template_uri"]);
			if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
			$acls[]=$uri;
		}
		
		$GetUfdBguardRedirect=$this->GetUfdBguardRedirect();
		
		while (list ($tpl, $line) = each ($GetUfdBguardRedirect)){
			$uri=$this->ACL_TEMPLATES_URIS_TO_DSTDOMAIN($tpl);
			if(isset($ALREADY[$uri])){continue;}$ALREADY[$uri]=true;
			$acls[]=$uri;
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: ".count($acls)." error templates URI(s)\n";}
		if(count($acls)==0){$GLOBALS[__CLASS__][__FUNCTION__]=null;return;}
		@file_put_contents("/etc/squid3/acls/templatesacls.txt", @implode("\n", $acls)."\n");
		$GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]="http_access allow {$this->MyStandPort}AllowedUrisTemplates";
		$results="acl AllowedUrisTemplates dstdomain \"/etc/squid3/acls/templatesacls.txt\"\n";
		$GLOBALS[__CLASS__][__FUNCTION__]=$results;
		return $results;
		
	}
	
	private function ACL_TEMPLATES_URIS_VERIF($uri){
		$uri=str_replace("%FREEWEBS%", "", $uri);
		if(strpos("  $uri", "http")==0){$uri="http://$uri";}
		if(preg_match("#^([0-9]+):(.+)#", $uri,$re)){$uri=$re[2];}
		$URLAR=parse_url($uri);
		$scheme=$URLAR["scheme"];
		$sitename=$URLAR["host"];
		return "$scheme://$sitename";
		
	}
	
	private function ACL_TEMPLATES_URIS_TO_DSTDOMAIN($uri){
		$uri=str_replace("%FREEWEBS%", "", $uri);
		if(strpos("  $uri", "http")==0){$uri="http://$uri";}
		if(preg_match("#^([0-9]+):(.+)#", $uri,$re)){$uri=$re[2];}
		$URLAR=parse_url($uri);
		$scheme=$URLAR["scheme"];
		$sitename=$URLAR["host"];
		if($sitename==null){return;}
		if(preg_match("#^(.+?):[0-9]+$#", $sitename)){return $re[1];}
		return $sitename;
	
	}	
	
	private function GetUfdBguardRedirect(){
		if(!is_file("/etc/squid3/ufdbGuard.conf")){return array();}
		$array=array();
		$f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
		while (list ($num, $line) = each ($f)){
			if(preg_match("#redirect\s+(.+)#", $line,$re)){
				$uri=trim($re[1]);if(preg_match("#^[0-9]+:(.+)#", $uri,$re)){$uri=$re[1];}
				$array[$uri]=$uri;
				continue;
				}
				
			}
		
		return $array;
	}
	
	
	private function string_to_regex($pattern){
		if(trim($pattern)==null){return null;}
		$pattern=str_replace(".", "\.", $pattern);
		$pattern=str_replace("[", "\[", $pattern);
		$pattern=str_replace("]", "\]", $pattern);
		$pattern=str_replace("(", "\(", $pattern);
		$pattern=str_replace(")", "\)", $pattern);
		$pattern=str_replace("$", "\$", $pattern);
		$pattern=str_replace("?", "\?", $pattern);
		$pattern=str_replace("#", "\#", $pattern);
		$pattern=str_replace("{", "\{", $pattern);
		$pattern=str_replace("}", "\}", $pattern);
		$pattern=str_replace("!", "\!", $pattern);
	
		return $pattern;
		
	}		

		private function ACL_WHITELISTED_FOR_ANTIVIRUS(){
			if($this->SquidUrgency==1){return null;}
			if(!class_exists("mysql_squid_builder")){include_once(dirname(__FILE__)."/class.mysql.squid.builder.php");}
			if(isset($GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"])){return @implode("\n", $GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"]);}
			$q=new mysql_squid_builder();
			$sql="SELECT websitename FROM webfilter_avwhitedoms";
			$results = $q->QUERY_SQL($sql);
			$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="# --------- webfilter_avwhitedoms ".mysql_num_rows($results)." item(s)";
			if(!$q->ok){
				$q->mysql_error=str_replace("\n", " ", $q->mysql_error);
				$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="# --------- $q->mysql_error";
				return @implode("\n", $f);
			}
			$dst=array();
			$dstdomain=array();
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$websitename=trim($ligne["websitename"]);
			if($websitename==null){continue;}
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $websitename)){$dst[$websitename]=$websitename;continue;}
			if(preg_match("#^www\.(.+)#", $websitename,$re[1])){$websitename=".$websitename";$dstdomain[$websitename]=$websitename;continue;}
			if(preg_match("#.+?\..+?#", $websitename)){
				if(substr($websitename, 0,1)<>'.'){$websitename=".$websitename";}
				$dstdomain[$websitename]=$websitename;
				continue;
			}
		}


		if(count($dst)>0){
			$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="# --------- ByPass ICAP AV ". count($dst). " IP ADDR(s)";
			$r=array();
			while (list ($a, $b) = each ($dst) ){$r[]=$a;}
			$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="acl ByPassAVICAPdst dst \"/etc/squid3/acls/ByPassAVICAPdst.txt\"";
			@file_put_contents("/etc/squid3/acls/ByPassAVICAPdst.txt", @implode("\n", $r)."\n\n");
			$GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"][]="ByPassAVICAPdst";
			//$ZE[]="adaptation_access ANTIVIRUS_CHAINS deny ByPassAVICAPdst";
		}			
		if(count($dstdomain)>0){
			$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="# --------- ByPass ICAP AV -> ". count($dstdomain)." domain(s)";
			$r=array();
			while (list ($a, $b) = each ($dstdomain) ){$r[]=$a;}
			$GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"][]="acl ByPassAVICAPdms dstdomain \"/etc/squid3/acls/ByPassAVICAPdms.txt\"";
			@file_put_contents("/etc/squid3/acls/ByPassAVICAPdms.txt", @implode("\n", $r)."\n\n");
			$GLOBALS["ACL_WHITELISTED_FOR_ANTIVIRUS"][]="ByPassAVICAPdms";
			//$ZE[]="adaptation_access ANTIVIRUS_CHAINS deny ByPassAVICAPdms";
		}			
			
		return @implode("\n", $GLOBALS["D_ACL_WHITELISTED_FOR_ANTIVIRUS"]);
		
			
		}
		
		
		private function WHITELISTED_AUTH_BROWSERS(){
			if($this->SquidUrgency==1){return null;}
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
				$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"]="http_access allow {$this->MyStandPort}WhiteListedUserAgents";
				return @implode("\n",$GLOBALS[__CLASS__][__FUNCTION__]);
			}
			$GLOBALS[__CLASS__][__FUNCTION__]=null;
			return null;
			
		}
		
		private function my_full_hostname(){
			if(isset($GLOBALS["my_full_hostname"])){return  $GLOBALS["my_full_hostname"];}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
			$unix=new unix();
			$hostname_bin=$unix->find_program("hostname");
			exec("$hostname_bin -f 2>&1",$results);
			$GLOBALS["my_full_hostname"]=trim(@implode("", $results));
			return $GLOBALS["my_full_hostname"]; 
		}
		
		
		private function krb_auth_conf(){
			if($this->SquidUrgency==1){return null;}
			if(!$this->ASROOT){return;}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$sock=new sockets();
			$myFullHostname=$this->my_full_hostname();
			if($this->EnableKerberosAuthentication==0){return null;}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$ntlm_auth=$unix->find_program("ntlm_auth");
			$binary=$users->squid_kerb_auth_path;
			$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
			if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
			if(strlen($binary)<2){return "#No squid_kerb_auth in compilation !\n";}
			
			echo "Starting......: ".date("H:i:s")." Kerberos Authentification enabled\n";
			if(is_file("/etc/krb5.keytab")){
				$chown=$unix->find_program("chown");
				$chmod=$unix->find_program("chmod");
				shell_exec("$chown squid:squid /etc/krb5.keytab");
				shell_exec("$chmod 666 /etc/krb5.keytab");
			}
			
			if(is_file("/usr/local/bin/negotiate_wrapper")){
				if(is_file("$ntlm_auth")){$ntlm=" --ntlm /usr/bin/ntlm_auth";}
				$t[]="auth_param negotiate program /usr/local/bin/negotiate_wrapper -d $ntlm --diagnostics --helper-protocol=squid-2.5-ntlmssp --domain=EXAMPLE --kerberos /usr/lib/squid3/squid_kerb_auth -d -s GSS_C_NO_NAME";
				
			}
			
			$keep_alive="off";
			if($SquidNTLMKeepAlive==1){$keep_alive="on";}
			$children=$this->SquidClientParams["auth_param_ntlm_children"];
	
			$conf[]= "\n\n#--------- kerberos AUTH settings myhostname $myFullHostname\n";
			//$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d -s HTTP/$myFullHostname";
			$conf[]= "auth_param negotiate program $users->squid_kerb_auth_path -d";
			$conf[]= "auth_param negotiate children {$children}";
			$conf[]= "auth_param negotiate keep_alive $keep_alive";
			$conf[]= "auth_param basic realm Squid proxy-caching web server";
			$conf[]= "auth_param basic credentialsttl 2 hour";
			$conf[]= "authenticate_ip_ttl 60 seconds";			
			$conf[]= "authenticate_cache_garbage_interval 10 seconds";
			$conf[]= "authenticate_ttl 0 hour";
			$GLOBALS["ACL_AUTHENTICATED"]="acl AUTHENTICATED proxy_auth REQUIRED";
			$conf[]="";	
			$conf[]="";
			return @implode("\n", $conf);		
		}
		
		
		private function BaseNameWebSite($www){
			if(trim($www)==null){return;}
			if(is_numeric(trim($www))){return;}
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
			if($this->SquidUrgency==1){return null;}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$sock=new sockets();
			$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
			$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
			$SquidNTLMKeepAlive=$sock->GET_INFO("SquidNTLMKeepAlive");
			
			$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
			if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
			
			if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
			if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
			if(!is_numeric($SquidNTLMKeepAlive)){$SquidNTLMKeepAlive=1;}
			
			if($this->DisableWinbindd==1){$this->EnableKerbAuth=0;$this->NTLM_AUTH=0;}
			
			if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}
			
			if($this->EnableKerbAuth==1){$UseDynamicGroupsAcls=1;}
			
	
			$keep_alive="off";
			if($SquidNTLMKeepAlive==0){$keep_alive="on";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DisableWinbindd=$this->DisableWinbindd\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): UseDynamicGroupsAcls=$UseDynamicGroupsAcls\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): DynamicGroupsAclsTTL=$DynamicGroupsAclsTTL\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): KerbAuthMethod=$KerbAuthMethod\n";}	
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): EnableKerbAuth=$this->EnableKerbAuth\n";}
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): keep_alive=$keep_alive\n";}
			
			// voir permissions on /var/lib/samba/winbindd_privileged
			// http://www.cyberciti.biz/faq/squid-ntlm-authentication-configuration-howto/
			
			if($this->EnableKerbAuth==1){
				if(!$users->WINBINDD_INSTALLED){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): unable to stat winbind\n";}
					return "#--------- NTLM AUTH settings\n#squid_kerb_auth enabled\n";}
				$this->NTLM_AUTH=1;
			}
			
			
			if($this->NTLM_AUTH==0){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): NTLM_AUTH=0, no ntlm is set\n";}
				return "#--------- NTLM NTLM_AUTH=0 / DisableWinbindd=$this->DisableWinbindd.\n";
			}
			
			if($this->ASROOT){
				if(is_file("/usr/bin/ntlm_auth")){
					$users->SQUID_NTLM_ENABLED=true;
					$users->SQUID_NTLM_AUTH="/usr/bin/ntlm_auth";
				}
				if(!is_file("/etc/squid3/net_ads_group.pl")){
					if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
					$php=$unix->LOCATE_PHP5_BIN();
					shell_exec("$php /usr/share/artica-postfix/exec.squid.netads.php >/dev/null 2>&1");
				}
			}
			
			if(!$users->SQUID_NTLM_ENABLED){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): No NTLM in compilation\n";}
				$this->NTLM_AUTH=0;return "#No NTLM in compilation !\n";
			}
			if(trim($users->SQUID_NTLM_AUTH)==null){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] ntlm_auth_conf(): No NTLM auth_param basic program found\n";}
				$this->NTLM_AUTH=0;return "#No NTLM auth_param basic program found !\n";
			}
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
			
			
			$auth_param_ntlm_children=$this->SquidClientParams["auth_param_ntlm_children"];
			$auth_param_ntlm_startup=$this->SquidClientParams["auth_param_ntlm_startup"];
			$auth_param_ntlm_idle=$this->SquidClientParams["auth_param_ntlm_idle"];
			if($auth_param_ntlm_children>151){$auth_param_ntlm_children=150;}
			if($auth_param_ntlm_startup>100){$auth_param_ntlm_startup=100;}
			if($auth_param_ntlm_idle+$auth_param_ntlm_children>151){$auth_param_ntlm_idle=1;}
			
			$external_acl_children=$this->SquidClientParams["external_acl_children"];
			$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
			$external_acl_idle=$this->SquidClientParams["external_acl_idle"];
			
			if($this->IS_32){
				$external_childs=" children-max=$external_acl_children children-startup=$external_acl_startup children-idle=$external_acl_idle";
				$auth_param_child=" startup=$auth_param_ntlm_startup idle=$auth_param_ntlm_idle";
			}
			
			$conf[]= "#--------- NTLM AUTH settings (line:".__LINE__.")\n";
			$conf[]= "\n#Authentification mode, building using squid compiled for artica";
			$conf[]= "";
			$conf[]= "#NTLM authentication: (KerbAuthMethod = $KerbAuthMethod)";

			if(($KerbAuthMethod==0) OR ($KerbAuthMethod==1)){
				$conf[]= "auth_param ntlm program $users->SQUID_NTLM_AUTH$basicNTLMDomain --helper-protocol=squid-2.5-ntlmssp";
				$conf[]= "auth_param ntlm children $auth_param_ntlm_children$auth_param_child";
				$conf[]= "auth_param ntlm keep_alive $keep_alive";
			
				if(!$this->IS_32){
					$conf[]= "auth_param ntlm use_ntlm_negotiate on";
					$conf[]= "auth_param ntlm max_challenge_reuses 5";
					$conf[]= "auth_param ntlm max_challenge_lifetime 10 minutes";
				}
				$conf[]= "";
			}
			
			$conf[]= "";
			$conf[]= "#Dynamic ACLs groups Enabled: [$UseDynamicGroupsAcls]";
			if($UseDynamicGroupsAcls==1){
				$conf[]= "external_acl_type ads_group ttl=$DynamicGroupsAclsTTL$external_childs %LOGIN /usr/share/artica-postfix/external_acl_squid_ldap.php";
				$conf[]= "";
			}
			$conf[]= "#Other settings";
			$conf[]= "authenticate_ttl 1 hour\n";
			$conf[]= "authenticate_cache_garbage_interval 10 seconds";
			$conf[]= "authenticate_ip_ttl 60 seconds\n";	
			$conf[]="# END NTLM Parameters --------------------------------\n";
			$GLOBALS["ACL_AUTHENTICATED"]="#--------- ACL AUTHENTICATED for authentication method\nacl AUTHENTICATED proxy_auth REQUIRED\n";
			return implode("\n",$conf);

		}
		
	private function auth_param_basic($direct=false){
		if($this->SquidUrgency==1){return null;}
		$conf=array();
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}

		$users=$GLOBALS["CLASS_USERS"];
		if(!is_file($users->SQUID_NTLM_AUTH)){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$users->SQUID_NTLM_AUTH=$unix->find_program("ntlm_auth");
		}
		echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: get definitions\n";
		
		
		$KerbAuthMethod=$sock->GET_INFO("KerbAuthMethod");
		if(!is_numeric($KerbAuthMethod)){$KerbAuthMethod=0;}
		

		$auth_param_basic_children=$this->SquidClientParams["auth_param_basic_children"];
		$auth_param_basic_startup=$this->SquidClientParams["auth_param_basic_startup"];
		$auth_param_basic_idle=$this->SquidClientParams["auth_param_basic_idle"];
		
		
		$external_acl_children=$this->SquidClientParams["external_acl_children"];
		$external_acl_startup=$this->SquidClientParams["external_acl_startup"];
		$external_acl_idle=$this->SquidClientParams["external_acl_idle"];		
		
		
		if($this->IS_32){$auth_param_child=" startup=$auth_param_basic_startup idle=$auth_param_basic_idle";}
		if($this->IS_33){$auth_param_child=" startup=$auth_param_basic_startup idle=$auth_param_basic_idle";}
		$Multiple=false;
		if($this->NTLM_AUTH==1){
			if(!$direct){
				
					if( ($KerbAuthMethod==0) OR ($KerbAuthMethod=2) ){
						$conf[]=$this->ldap_auth_conf(true);
					}else{
							$conf[]= "#Basic authentication for other browser is disabled KerbAuthMethod=$KerbAuthMethod";
						}
			}
		}
		
		$acl=new squid_acls();
		if($acl->IsObjectAuth()){
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication enabled\n";
			$Multiple=true;
		}else{
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: Objects authentication is disabled\n";
		}
		if($this->LDAP_AUTH==1){return ;}
		if($this->LDAP_EXTERNAL_AUTH==1){return ;}
		
		if($Multiple){
			$conf=array();
			echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: multiple authentication methods enabled\n";
			if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
			$basic_fake_auth=$this->locate_generic_bin("basic_fake_auth");
			if($this->NTLM_AUTH==1){$conf[]= "#Basic authentication for other browsers that did not supports NTLM:";}
			if($this->NTLM_AUTH==0){$conf[]= "#Basic authentication for other browsers";}
			$conf[]= "auth_param basic program $basic_fake_auth";
			$conf[]= "auth_param basic children {$auth_param_basic_children}$auth_param_child";
			$conf[]= "auth_param basic realm Basic Identification";
			$conf[]= "auth_param basic credentialsttl 2 hours";	
			
			$external_acl_childrens=$this->external_acl_childrens(0,true);	
			$conf[]= "external_acl_type BasicFakeAuth $external_acl_childrens %SRC %LOGIN %{Proxy-Authorization} /usr/share/artica-postfix/external_acl_basic_auth.php\n";
		}
		
		echo "Starting......: ".date("H:i:s")." [AUTH]: Basic: ".count($conf)." rows\n";
		
		return @implode("\n", $conf)."\n";
		
	}
	
	private function locate_generic_bin($program){
		if(!$this->ASROOT){return;}
		$possibleDirs[]="/lib/squid3";
		$possibleDirs[]="/lib64/squid3";
		$possibleDirs[]="/lib/squid";
		$possibleDirs[]="/lib64/squid";
		$possibleDirs[]="/usr/lib/squid";
		$possibleDirs[]="/usr/lib64/squid";
		$possibleDirs[]="/usr/local/squid/libexec";
		while (list ($num, $directory) = each ($possibleDirs) ){
			if(is_file("$directory/$program")){return "$directory/$program";}
		}
		
		echo "\n!!!!! ******************* !!!!!\nStarting......: ".date("H:i:s")." [ERR]: locate_generic_bin() Unable to find $program\n!!!!! ******************* !!!!!\n";
	
	}	
			
			
	private function ISFiltersEnabled(){
		if($this->SquidUrgency==1){return false;}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if($users->DANSGUARDIAN_INSTALLED){if($this->enable_dansguardian==1){return true;}}
		if($users->SQUIDGUARD_INSTALLED){if($this->enable_squidguard==1){return true;}}
	}
	
	private function url_rewrite_programs(){
		if($this->SquidUrgency==1){return null;}
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
		if($this->SquidUrgency==1){return null;}
		$redirect=$this->url_rewrite_programs();
		$sock=new sockets();
		$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));


		
		if($this->ASROOT){
			if(!is_file("/usr/bin/zapchain")){
				shell_exec("/bin/cp /usr/share/artica-postfix/bin/zapchain /usr/bin/zapchain");
				shell_exec("/bin/chmod 755 /usr/bin/zapchain");
			}
		}
		
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: ". count($redirect)." redirector(s)\n";}


		if(count($redirect)>1){
			$conf[]= "url_rewrite_program /usr/bin/zapchain ". implode(" ",$redirect);
			$conf[]= $this->url_rewrite_children();	
			$conf[]="acl UrlRewriteDenyList dstdomain \"/etc/squid3/url_rewrite_program.deny.db\"";
			if($SquidDebugPort==1){$conf[]="url_rewrite_access deny MyTestPort"; }
			$conf[]="url_rewrite_access deny MgRDest";
			$conf[]="url_rewrite_access deny MgRPort";
			$conf[]="url_rewrite_access deny ToArticaWWW";
			$conf[]="url_rewrite_access deny UrlRewriteDenyList";			
		}else{
			$conf[]= "url_rewrite_program ".implode("",$redirect);
			$conf[]= $this->url_rewrite_children();		
			$conf[]="acl UrlRewriteDenyList dstdomain \"/etc/squid3/url_rewrite_program.deny.db\"";
			if($SquidDebugPort==1){$conf[]="url_rewrite_access deny MyTestPort"; }
			$conf[]="url_rewrite_access deny MgRDest";
			$conf[]="url_rewrite_access deny MgRPort";
			$conf[]="url_rewrite_access deny ToArticaWWW";
			$conf[]="url_rewrite_access deny UrlRewriteDenyList";				
		}
		
		return @implode("\n",$conf);
	}
	
	private function zapchain_streamcache(){
		// Disabled...
		return;
		if($this->enable_streamcache==0){return;}
		if(!$this->ASROOT){return;}
		shell_exec("/bin/cp /usr/share/artica-postfix/streamcache.php /usr/sbin/streamcache.php");
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
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
	
	public function zapchain_ufdbguard(){
		if($this->SquidUrgency==1){return null;}
		if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->APP_UFDBGUARD_INSTALLED){return null;}
		
		
		if($this->enable_UfdbGuard==null){$this->enable_UfdbGuard=0;}
		if($this->enable_UfdbGuard==0){return null;}
		if(strlen($users->ufdbgclient_path)==0){return null;}
		
		
		
		$moinsC=null;
		
		$sock=new sockets();
		$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		
		if(!isset($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!isset($datas["remote_port"])){$datas["remote_port"]=3977;}
		if(!isset($datas["remote_server"])){$datas["remote_server"]=null;}
		if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
		if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=1;}
		if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
		if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}		
		
		if(!is_numeric($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
		if($datas["url_rewrite_children_concurrency"]>1){$moinsC="-C ";}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
			if($this->ASROOT){
				echo "Starting......: ".date("H:i:s")." [UFDB]: Using remote appliance {$RemoteStatisticsApplianceSettings["SERVER"]}:{$datas["listen_port"]} as Web filtering engine\n";
			}
			
			$datas["remote_server"]=$RemoteStatisticsApplianceSettings["SERVER"];
			$datas["UseRemoteUfdbguardService"]=1;
			$datas["remote_port"]=$datas["listen_port"];
		}		
		
		$binary=$users->ufdbgclient_path;
		$log="-l /var/log/squid";
 		
 		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$major=$unix->UFDBGUARDD_MAJOR();
		$minor=$unix->UFDBGUARDD_MINOR();
		
		

		
		
		if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=1;}			
		if(!is_numeric($datas["UseRemoteUfdbguardService"])){$datas["UseRemoteUfdbguardService"]=0;}
		if(!is_numeric($datas["remote_port"])){$datas["remote_port"]=3977;}
		
		if($datas["remote_port"]==null){$datas["UseRemoteUfdbguardService"]=0;}
		if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
		if($datas["listen_addr"]=="all"){$datas["listen_addr"]="127.0.0.1";}
		
		
		
		
		
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Major:$major, Minor:$minor use TCP socket:{$datas["tcpsockets"]}\n";
		if($datas["UseRemoteUfdbguardService"]==1){
			if(trim($datas["remote_server"]==null)){$datas["remote_server"]="127.0.0.1";}
			
			$address="-S {$datas["remote_server"]} -p {$datas["remote_port"]} ";	
			echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: {$datas["remote_server"]}:{$datas["remote_port"]}\n";
			$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
			return "$binary $moinsC$address $log";
		}
		
		$effective_port=$this->ufdbguard_value("port");
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Effective port:`$effective_port`\n";
		if(is_numeric($effective_port)){$datas["tcpsockets"]=1;}
		
		
		if($major>0){
			if($minor>26){
				if($datas["tcpsockets"]==1){
					if(trim($datas["listen_addr"]==null)){$datas["listen_addr"]="127.0.0.1";}
					echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: {$datas["listen_addr"]}:{$datas["remote_port"]}\n";
					$address="-S {$datas["listen_addr"]} -p {$datas["listen_port"]} ";	
					$GLOBALS[__FUNCTION__]="$binary $moinsC$address $log";	
					return "$binary $moinsC$address $log";		
				}	
			}
		}
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Use remote ufdbguard service: $binary $moinsC$log\n";
		$GLOBALS[__FUNCTION__]="$binary $moinsC$log";
		return "$binary $moinsC$log";	
	}
	
	
	
	private function zapchain_squidclamav(){
		if($this->SquidUrgency==1){return null;}
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
		if(!$users->APP_SQUIDCLAMAV_INSTALLED){return null;}
		if($this->enable_squidclamav==null){$this->enable_squidclamav=0;}
		if($this->enable_squidclamav==0){return null;}
		return "$users->squidclamav_path /etc/squidclamav.conf";
	}	
	
	

	
	
	private function zapchain_squidguard(){
		if($this->SquidUrgency==1){return null;}
	if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
	$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard Installed: FALSE\n";}
			return null;
		}
		if($this->enable_squidguard==0){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard enabled: FALSE\n";}
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

		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard in C-ICAP=$EnableSquidGuardInCiCAP\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." zapchain: squidGuard Enabled=$this->enable_squidguard\n";}
		
		if($users->APP_UFDBGUARD_INSTALLED){if($this->enable_UfdbGuard==1){$this->enable_squidguard=0;}}
		if($this->enable_squidguard==0){return null;}
		$bin=trim($users->SQUIDGUARD_BIN_PATH);
		if($bin==null){
			$sock=new sockets();
			$bin=base64_decode($sock->getFrameWork("cmd.php?find-program=squidGard"));
			if(trim($bin)==null){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Unable to activate squidGuard no path found\n";}
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
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Activate AdZapper\n";}
				return $this->zapchain();
				}
			}
			
		$conf[]= "\n#--------- squidClamAv";
		$conf[]= "\n#IS C-ICAP enabled = $this->enable_cicap";
		$conf[]= "url_rewrite_program $users->squidclamav_path /etc/squidclamav.conf";
		$conf[]= $this->url_rewrite_children();
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Activate squidClamav \"$users->squidclamav_path\"\n";}
		return implode("\n",$conf);		
		}
		
	private function ufdbguard_concurrency(){
		return false;
		$sock=new sockets();
		$RedirectorsArray=unserialize(base64_decode($sock->GET_INFO("SquidRedirectorsOptions")));
		if(!isset($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if(!is_numeric($RedirectorsArray["url_rewrite_concurrency"])){$RedirectorsArray["url_rewrite_concurrency"]=0;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." redirectors rewrite concurrency \"{$RedirectorsArray["url_rewrite_concurrency"]}\"\n";}
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
		if($this->SquidUrgency==1){return null;}
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$sock=new sockets();
		$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
		
		if($users->APP_SQUIDCLAMAV_INSTALLED){
			if($this->enable_squidclamav==1){
				return $this->squidclamav();
			}
		}
		
		if($this->enable_streamcache==1){
			$conf[]= "url_rewrite_program ".$this->zapchain_streamcache();	
			$conf[]= $this->url_rewrite_children();
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Activate StreamCache\n";}
			return implode("\n",$conf);
		}
		
		if(!$users->SQUIDGUARD_INSTALLED){if(!$users->APP_UFDBGUARD_INSTALLED){if(!$users->ADZAPPER_INSTALLED){return null;}}}
		
		$redirects=$this->url_rewrite_programs();
		if(count($redirects)>1){return $this->zapchain();}
				
		$sock=new sockets();
		$this->enable_UfdbGuard=$sock->EnableUfdbGuard();
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
					$datas=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
					if(!isset($datas["url_rewrite_children_concurrency"])){$datas["url_rewrite_children_concurrency"]=2;}
					if(!isset($datas["url_rewrite_children_startup"])){$datas["url_rewrite_children_startup"]=5;}
					if(!isset($datas["url_rewrite_children_idle"])){$datas["url_rewrite_children_idle"]=5;}
					if(!isset($datas["url_rewrite_children_max"])){$datas["url_rewrite_children_max"]=20;}
					$conf[]= "url_rewrite_program $zapchain_ufdbguard";	
					$conf[]= "url_rewrite_children {$datas["url_rewrite_children_max"]} startup={$datas["url_rewrite_children_startup"]} idle={$datas["url_rewrite_children_idle"]} concurrency={$datas["url_rewrite_children_concurrency"]}";
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: \"$zapchain_ufdbguard\"\n";}
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Max processes        : \"{$datas["url_rewrite_children_max"]}\"\n";}
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Startup processes    : \"{$datas["url_rewrite_children_startup"]}\"\n";}
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Idle processes       : \"{$datas["url_rewrite_children_idle"]}\"\n";}
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." ufdbguardd: Concurrency processes: \"{$datas["url_rewrite_children_concurrency"]}\"\n";}
					$conf[]="acl UrlRewriteDenyList dstdomain \"/etc/squid3/url_rewrite_program.deny.db\"";
					if($SquidDebugPort==1){$conf[]="url_rewrite_access deny MyTestPort"; }
					$conf[]="url_rewrite_access deny MgRDest";
					$conf[]="url_rewrite_access deny ToArticaWWW";
					$conf[]="url_rewrite_access deny MgRPort";
					$conf[]="url_rewrite_access deny UrlRewriteDenyList";
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
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Unable to activate squidGuard no path found\n";}
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
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." disable squidGuard \"". count($array)."\" databases not compiled\n";}
				return implode("\n",$conf);
				
			}
		}
			$conf[]= "\n#--------- squidGard";
			$conf[]= "\n#IS C-ICAP enabled = $this->enable_cicap";
			$conf[]= "url_rewrite_program $users->SQUIDGUARD_BIN_PATH";
			$conf[]= $this->url_rewrite_children();
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Activate squidGuard \"$bin\"\n";}
			return implode("\n",$conf);
	}
	
	
	private function cache_peer_extract_options($optionsDecoded){
		$array=$optionsDecoded;
		if(!is_array($array)){return null;}
		if(count($array)==0){return null;}
		$login=null;$parmType=null;
		while (list($num,$val)=each($array)){
			
			if($num=="loginPASSTHRU"){$login="login=PASSTHRU";unset($array[$num]);continue;}
			
			if($num=="login"){
				if(!preg_match("#(.+?):(.+)#", $val)){unset($array[$num]);continue;}
				$login="login=$val";
				unset($array[$num]);
				continue;
			}
			if($num=="loginPASS"){$login="login=PASS";unset($array[$num]);continue;}
			if($num=="connection-auth"){
				$val=strtolower($val);
				$val=str_replace($val,"1","on");
				$val=str_replace($val,"0","off");
				$val=str_replace($val,"yes","on");
				$val=str_replace($val,"no","off");
				if(!preg_match("#(on|off)#i", $val)){$val="on";}
				$array[$num]=$val;
			}
			if ($num=="proxy-only"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="round-robin"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="userhash"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="sourcehash"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="multicast-siblings"){$parmType=$num;unset($array[$num]);continue;}
			if ($num=="weighted-round-robin"){$parmType=$num;unset($array[$num]);continue;}
		
		
		}
		if($login<>null){$array[$login]=null;}
		if($parmType<>null){$parmType=" $parmType ";}
		reset($array);
		
		while (list($num,$val)=each($array)){
			if(trim($num)==null){continue;}
			if($num=="carp"){$num="carp carp-key=host,port,path";}
			if(trim($val)<>null){$z[]="$num=$val";}else{$z[]=$num;}
		}
		return implode(" ",$z);		
		
	}
	
	
	public function cache_peer_acls(){
		$f=array();
		$acls=new squid_acls_groups();
		$cache_parents=$acls->buildacls_bytype("cache_parent");
		if(count($cache_parents)==0){
			$f[]="# --------- SQUID Parents in ACLS = No row [".__LINE__."]";
			return @implode("\n", $f);
		}
			
		while (list ($index, $MAINS) = each ($cache_parents) ){
			$f[]=$MAINS["LINE"];
			$PortsAcls=$MAINS["PORTS_ACLS"];
			$PeerName=$MAINS["NAME"];
			$f[]="# --------- SQUID Parents in ACLS $PeerName [".__LINE__."]";
			if($PortsAcls<>null){$PortsAcls="$PortsAcls ";}
				while (list ($index, $groups) = each ($MAINS["GROUPS"]) ){
						$f[]="cache_peer_access $PeerName allow $PortsAcls".@implode(" ", $groups);
				}
				$f[]="cache_peer_access $PeerName deny all";
			}
			
	
		return @implode("\n", $f);
		
		
		
	}
	
	
	
	
	public function cache_peer(){
		if(!$this->ASROOT){return;}
		$sock=new sockets();
		$ini=new Bs_IniHandler();
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini->loadString($sock->GET_INFO('ArticaSquidParameters'));
		$EnableParentProxy=intval($ini->_params["NETWORK"]["EnableParentProxy"]);
		$EnableParentProxy2=intval($sock->GET_INFO("EnableParentProxy"));
		if($EnableParentProxy2==1){$EnableParentProxy=1;}
		
		
		$f[]="#";
		$f[]="# --------- SQUID PARENTS --------- [".__LINE__."]";
		$f[]="# --------- EnableParentProxy ini=NETWORK/EnableParentProxy = $EnableParentProxy [".__LINE__."]";
		$f[]=$this->cache_peer_acls();
		
		if($EnableParentProxy==0){
			echo "Starting......: ".date("H:i:s")." [ LB ]: Load-balancing mode is disabled\n";
			$f[]="# --------- (feature not enabled) EnableParentProxy = 0 [".__LINE__."]\n";
			return @implode("\n", $f);
		}
		
		$sql="SELECT * FROM squid_parents WHERE enabled=1 ORDER BY weight DESC";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		echo "Starting......: ".date("H:i:s")." [ LB ]: ". mysql_num_rows($results)." Proxy backends...\n";
		if(!$q->ok){
			if(preg_match("#Unknown column#", $q->mysql_error)){
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `enabled` INT( 1 ) NOT NULL DEFAULT '1',ADD INDEX ( `enabled` )","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `htcp_port` INT( 3 ) NOT NULL","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `weight` INT( 1 ) NOT NULL DEFAULT '1'","artica_backup");
				$q->QUERY_SQL("ALTER TABLE `squid_parents` ADD `zOrder` INT( 1 ) NOT NULL DEFAULT '1'","artica_backup");
				$results=$q->QUERY_SQL($sql,"artica_backup");
			}
		}
		
		
		if(!$q->ok){
			$q->mysql_error=str_replace("\n", " ", $q->mysql_error);
			if(!$q->ok){return "\n\n\n#--------- SQUID PARENTS (MySQL ERROR $q->mysql_error)\n";}
		}
		
		if( mysql_num_rows($results)==0){return "\n\n\n#--------- SQUID PARENTS 0 ITEM ENABLED\n";}
		
		$f[]="";
		$f[]="";
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
		$acls=new squid_acls_groups();
		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			
			
			if(trim($ligne["servername"])==null){continue;}
			if(trim($ligne["server_port"])==null){continue;}
			if(trim($ligne["server_type"])==null){$ligne["server_type"]="parent";}
			if(strlen(trim($ligne["icp_port"]))==0){$ligne["icp_port"]=0;}
			$MAIN_RESULTS=$acls->cache_peer_explode($ligne["ID"]);
			if(!is_array($MAIN_RESULTS)){continue;}
			if(!isset($MAIN_RESULTS["NET"])){continue;}
			$NET=$MAIN_RESULTS["NET"];
			$f[]=$MAIN_RESULTS["LINE"];
			

			}
			
			
		$cache_peer_domains=$this->cache_peer_domains();
		
			
		if($cache_peer_domains==null){
			$f[]="#--------- RULES THAT FORCE TO PASS trough parent proxies except FTP";
			$f[]="always_direct allow FTP";
			if($this->SSL_BUMP==0){$f[]="always_direct deny all";$f[]="prefer_direct off";}
			$f[]="never_direct allow all";
		}else{
			$f[]=$cache_peer_domains;
		}	
		$f[]="";
		
		return implode("\n",$f);
		
	}
	
	

	
	
	
	private function cache_peer_streamcache(){
		$sock=new sockets();
		@unlink("/etc/squid3/vc_dom.db");
		$enableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
		if($enableStreamCache==0){
			$f[]="#";
			$f[]="# --------- LOCAL PARENT FOR VIDEO CACHE - disabled - [".__LINE__."]";
			$f[]="#";
			return @implode("\n", $f);
		}

		$domz[]=".stream.aol.com";
		$domz[]=".5min.com";
		$domz[]=".msn.com";
		$domz[]=".blip.tv";
		$domz[]=".dmcdn.net";
		$domz[]=".break.com";
		$domz[]=".vimeo.com";
		$domz[]=".vimeocdn.com ";
		$domz[]="video.thestaticvube.com";
		$domz[]=".dailymotion.com";
		$domz[]=".c.wrzuta.pl";
		$domz[]=".v.imwx.com";
		$domz[]=".mccont.com";
		$domz[]=".myspacecdn.com video-http.media-imdb.com fcache.veoh.com";
		$domz[]=".hardsextube.com";
		$domz[]=".public.extremetube.phncdn.com";
		$domz[]=".redtubefiles.com";
		$domz[]=".video.pornhub.phncdn.com";
		$domz[]=".videos.videobash.com";
		$domz[]=".public.keezmovies.com";
		$domz[]=".public.keezmovies.phncdn.com";
		$domz[]=".slutload-media.com";
		$domz[]=".public.spankwire.com";
		$domz[]=".xtube.com";
		$domz[]=".public.youporn.phncdn.com";
		$domz[]=".xvideos.com";
		$domz[]=".tube8.com";
		$domz[]=".public.spankwire.phncdn.com";
		$domz[]=".pornhub.com";
		$domz[]=".youtube.com";	
		$domz[]=".googlevideo.com";
		$domz[]="";
		
		$StreamCachePort=intval($sock->GET_INFO("StreamCachePort"));
		$StreamCacheICPPort=intval($sock->GET_INFO("StreamCacheICPPort"));
		$StreamCacheBindProxy=$this->VerifStreamProxyBindIP();
		if($StreamCachePort==0){$StreamCachePort=5559;}
		if($StreamCacheICPPort==0){$StreamCacheICPPort=5562;}
		$f[]="#";
		$f[]="# --------- LOCAL PARENT FOR VIDEO CACHE $StreamCacheBindProxy:$StreamCachePort [".__LINE__."]";
		

		
		$f[]="acl vc_dom dstdomain \"/etc/squid3/vc_dom.db\"";
		@file_put_contents("/etc/squid3/vc_dom.db", @implode("\n", $domz));
		
		$f[]="cache_peer $StreamCacheBindProxy\tparent\t$StreamCachePort\t0\tdefault name=vc_peer";
		$f[]="cache_peer_access vc_peer deny vc_deny_dom";
		$f[]="cache_peer_access vc_peer allow vc_dom";
		$f[]="never_direct deny vc_deny_dom";
		$f[]="never_direct allow vc_dom";
		
		$f[]="#";
		$f[]="#";
		return @implode("\n", $f);
	}
	
	
	public function AclsStreamCache(){
		$sock=new sockets();
		$enableStreamCache=intval($sock->GET_INFO("EnableStreamCache"));
		if($enableStreamCache==0){return null;}

		$f[]="";
		$f[]="acl vc_deny_dom dstdomain .manifest.youtube.com .manifest.googlevideo.com";
		$f[]="acl vc_deny_dom dstdomain .redirector.googlevideo.com .redirector.youtube.com";
		$f[]="";
		$f[]="acl vc_deny_url url_regex -i crossdomain.xml";
		$f[]="";
		return @implode("\n", $f);
		
		
		
	}
	
	
	private function cache_peer_domains(){
		if($this->SquidUrgency==1){return null;}
		$q=new mysql();
		$sql="SELECT * FROM cache_peer_domain WHERE `enabled`=1";
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			return;
		}
		echo "Starting......: ".date("H:i:s")." [ LB ]: ". mysql_num_rows($results)." backends domains...\n";
		if(mysql_num_rows($results)==0){return;}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$servername=trim($ligne["servername"]);
			if($ligne["reverse"]==1){
				$ARRAY[$servername][]="!.".$ligne["domain"];
				continue;
			}
			$ARRAY[$servername][]=".".$ligne["domain"];
			
				
		}
		
		while (list($peer,$zvals)=each($ARRAY)){
			$f[]="cache_peer_domain $peer ".@implode(" ", $zvals);
			
		}
		return @implode("\n",$f);
		
	}
	
	
	private function x_forwarded_for(){
		if(!$this->ASROOT){return;}
		$enabled=false;
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$results=$this->_compile_parmz();
		while (list($num,$val)=each($results)){if(preg_match("#enable-follow-x-forwarded-for#", $val)){$enabled=true;}}
		if(!$enabled){if($GLOBALS["VERBOSE"]){echo "DEBUG : enable-follow-x-forwarded-for no such compilation option !\n";}return;}
		if($GLOBALS["VERBOSE"]){echo "DEBUG :forwarded_for enable-follow-x-forwarded-for OK\n";}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: enable-follow-x-forwarded-for OK\n";}
		$f[]="# --------- Use x-forwarded-for for load balancers";
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG :forwarded_for this->_follow_x_forwarded_for()\n";}
		$follow_x_forwarded_for=$this->_follow_x_forwarded_for(); 
		
		if($follow_x_forwarded_for<>null){$f[]=$follow_x_forwarded_for;}
		$users=new usersMenus();
		
		
			
		
		
		if( ($users->DANSGUARDIAN_INSTALLED) OR ($this->EnableTransparent27==1)){
			$f[]="follow_x_forwarded_for allow localhost";
		}
		$f[]="acl_uses_indirect_client         on";
		$f[]="delay_pool_uses_indirect_client  on";
		$f[]="log_uses_indirect_client         on";
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: return a set of rules...\n";}
		return @implode("\n", $f)."\n";
	}

function buil_init_squid_tail(){}	
	
private function isRemoteStatsAppliance(){
	$sock=new sockets();
	$EnableRemoteSyslogStatsAppliance=$sock->GET_INFO("EnableRemoteSyslogStatsAppliance");
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($EnableRemoteSyslogStatsAppliance)){$EnableRemoteSyslogStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if(is_file("/etc/artica-postfix/PROXYTINY_APPLIANCE")){$DisableArticaProxyStatistics=1;}
	if($DisableArticaProxyStatistics==1){return true;}
	if($EnableRemoteSyslogStatsAppliance==1){return true;}
	if($EnableRemoteStatisticsAppliance==1){return true;}
	return false;
	
}	


private function access_logs(){
	$compile_params=$this->compilation_params();
	$ExternalSyslog=$this->syslog_remote();
	$sock=new sockets();
	$stdio=null;
	$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
	$ActAsASyslogServer=$sock->GET_INFO("ActAsASyslogServer");
	$SquidStoreLogLogging=$sock->GET_INFO("SquidStoreLogLogging");
	$SquidAsMasterCacheChilds=$sock->GET_INFO("SquidAsMasterCacheChilds");
	if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
	if(!is_numeric($ActAsASyslogServer)){$ActAsASyslogServer=0;}
	if(!is_numeric($SquidStoreLogLogging)){$SquidStoreLogLogging=0;}
	if(!is_numeric($SquidAsMasterCacheChilds)){$SquidAsMasterCacheChilds=1;}
	$isRemoteStatsAppliance=$this->isRemoteStatsAppliance();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	$SquidAsMasterLogChilds=intval($sock->GET_INFO("SquidAsMasterLogChilds"));
	$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
	
	$ChildProxysIPs=null;
	
	$acls=new squid_acls_groups();
	$denylogsTXT=null;
	
	if($SquidAsMasterPeer==1){
		if($SquidAsMasterLogChilds==0){
			if($SquidAsMasterFollowxForward==0){
				$ChildProxysIPs="!ChildProxysIPs ";
			}else{
				$ChildProxysIPs="!ChildProxyPorts";
			}
		}
	}
	
	$denylogs=$acls->buildacls_bytype("deny_log");
	if(count($denylogs)>0){$denylogsTXT=trim(@implode(" ", $denylogs));}
	if(strlen($denylogsTXT)>2){$denylogsTXT=" $denylogsTXT";}
	if($this->IS_32){$stdio="stdio:";}
	if(isset($compile_params["enable-icmp"])){
		$conf[]="netdb_filename $stdio/var/log/squid/netdb.state";
	}else{
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Main cache enable-icmp not compiled, skip it\n";}
	}

	
	$conf[]="# --------- ActAsASyslogServer 	 = $ActAsASyslogServer [".__LINE__."]";
	$conf[]="# --------- EnableSargGenerator	 = $EnableSargGenerator [".__LINE__."]";
	$conf[]="# --------- isRemoteStatsAppliance	 = $isRemoteStatsAppliance [".__LINE__."]";;
	$conf[]="# --------- SquidAsMasterPeer		 = $SquidAsMasterPeer [".__LINE__."]";
	$conf[]="# --------- SquidAsMasterLogChilds	 = $SquidAsMasterLogChilds [".__LINE__."]";
	$conf[]="# --------- EnableMacAddressFilter	 = $this->EnableMacAddressFilter [".__LINE__."]";
	$conf[]="# --------- SquidAsMasterCacheChilds= $SquidAsMasterLogChilds [".__LINE__."]";
	$conf[]="# --------- Is a 3.4 version		 = $this->IS_34 [".__LINE__."]";
	if($this->EnableMacAddressFilter==1){$conf[]="eui_lookup on";}
	if($this->EnableMacAddressFilter==0){$conf[]="eui_lookup off";}
	
	
	if($this->IS_34){
		$options34=" on-error=drop buffer-size=64KB ";
	}
	
	
	if($ExternalSyslog<>null){$conf[]=$ExternalSyslog;}
	
	if($this->IS_27){
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 2.7x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
		if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
		$conf[]="access_log syslog:authpriv.info  common $ChildProxysIPs$denylogsTXT";
		return @implode("\n", $conf)."\n";
	}
	
	
	if(($this->IS_30) && (!$this->IS_32)){
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.0x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
		if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
		$conf[]="access_log syslog:authpriv.info common $ChildProxysIPs$denylogsTXT";
		$conf[]="access_log /var/log/squid/access.log squid $ChildProxysIPs$denylogsTXT";
		return @implode("\n", $conf)."\n";
		
	}
	
	$csvlog=$this->access_log_csv();
	if($csvlog<>null){
		$conf[]="# --------- CSVLOG line:".__LINE__;
		$conf[]="$csvlog";
	}
				
	$log_access_acls_csv=$this->log_access_acls_csv();
	if($log_access_acls_csv<>null){
		$conf[]=$log_access_acls_csv;
	}
	
	$log_childs=$this->access_log_childs();
	if($log_childs<>null){
		$conf[]=$log_childs;
	}	
	
	
	if(($this->IS_31) && (!$this->IS_32)){
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.1x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
		if($SquidStoreLogLogging==1){$conf[]="cache_store_log	/var/log/squid/store.log";}
		$MACFILER='00:00:00:00:00:00';
		$conf[]="# --------- on a 3.1x, MAC cannot be logged..\n";
		$conf[]="logformat $MACFILER:::%>a:::%[ui:::%[un:::%tl:::%rm:::%ru:::HTTP/%rv:::%>Hs:::%<st:::%Ss:%Sh:::%{User-Agent}>h:::%{X-Forwarded-For}>h%<A:::%>A:::%tr";
		$conf[]="access_log syslog:authpriv.info common $ChildProxysIPs$denylogsTXT";
		$conf[]="access_log /var/log/squid/access.log squid $ChildProxysIPs$denylogsTXT";
		return @implode("\n", $conf)."\n";
		
	}
	
	if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [LOG ]: Build log for 3.2x or 3.3x: EnableMacAddressFilter=$this->EnableMacAddressFilter\n";}
	$MACFILER="%>eui";
	
	$logfile_daemon_ext=null;
	if($this->SquidActHasReverse==1){$logfile_daemon_ext=" --reverse";}
	if($isRemoteStatsAppliance){$logfile_daemon_ext=" --no-disk";}
	
	if($this->EnableMacAddressFilter==0){$MACFILER='00:00:00:00:00:00';}
	
	$conf[]="# LOG FORMAT MODE 3.2/3.3/3.4";
	$conf[]="logfile_daemon /usr/share/artica-postfix/exec.logfile_daemon.php";
	$conf[]="logformat common $MACFILER:::%>a:::%[ui:::%[un:::%tl:::%rm:::%ru:::HTTP/%rv:::%>Hs:::%<st:::%Ss:%Sh:::%{User-Agent}>h:::%{X-Forwarded-For}>h:::%<A:::%>A:::%tr";
	if($SquidStoreLogLogging==1){$conf[]="cache_store_log	stdio:/var/log/squid/store.log\n";}
	$conf[]="access_log $stdio/var/log/squid/access.log squid $ChildProxysIPs$denylogsTXT$options34";
	$conf[]="access_log daemon: common $options34!MgRPort $ChildProxysIPs$denylogsTXT$options34";
	return @implode("\n", $conf)."\n";
		
}

private function build_http_debug_port(){
	$this->NETWORK_ALL_INTERFACES();
	$sock=new sockets();
	$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
	$conf[]="# --------- http port for debug = $SquidDebugPort [".__LINE__."]\n";
	if($SquidDebugPort==0){return @implode("\n", $conf);}
	$SquidDebugPortNum=intval($sock->GET_INFO("SquidDebugPortNum"));
	$SquidDebugPortInterface=intval($sock->GET_INFO("SquidDebugPortInterface"));
	if($SquidDebugPortNum==0){$SquidDebugPortNum=rand(55678, 65000);$sock->SET_INFO("SquidDebugPortInterface",$SquidDebugPortNum); }
	if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidDebugPortInterface])){$SquidDebugPortInterface="0.0.0.0";}
	$conf[]="http_port $SquidDebugPortInterface:$SquidDebugPortNum";
	$token=$this->myport_token();
	$conf[]="acl MyTestPort $token $SquidDebugPortNum";
	$conf[]="cache deny MyTestPort";
	return @implode("\n", $conf);
	
}


private function build_http_ports(){
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$http_port_sslbump=$this->ssl_bump_port();
	$http_port_sslbump_2=$http_port_sslbump;
	$http_port_sslbump_3=$http_port_sslbump;
	$CooVaHTTPPort=$this->CoovaTransparent();
	$ArticaHotSpotPort=$this->ArticaHotSpot();
	$ssl_bump_token=$this->ssl_bump_token();
	$transparent_token=$this->transparent_token();
	$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
	$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	$this->NETWORK_ALL_INTERFACES();
	if( (!is_numeric($this->MGR_LISTEN_PORT)) OR ($this->MGR_LISTEN_PORT==0)){
		$this->MGR_LISTEN_PORT=$sock->GET_INFO("SquidMgrListenPort");
		if(!is_numeric($this->MGR_LISTEN_PORT)){
			$this->MGR_LISTEN_PORT=rand(50000,64000);
			$sock->SET_INFO("SquidMgrListenPort", $this->MGR_LISTEN_PORT);
		}
	}

	
	if($SquidAsMasterPeerPort==0){$SquidAsMasterPeerPort=8050;}
	if($SquidAsMasterPeerPortSSL==0){$SquidAsMasterPeerPortSSL=8051;}
	$build_http_debug_port=$this->build_http_debug_port();
	$conf[]="#";
	$conf[]="#";
	if($build_http_debug_port<>null){$conf[]=$build_http_debug_port;}
	$conf[]="# --------- http/https ports Second:$this->second_listen_port, SmartPhones:$this->smartphones_port, WCCP=$this->WCCPListenPort [".__LINE__."]";
	$conf[]="# --------- DisableSSLStandardPort = $this->DisableSSLStandardPort SSL token:$ssl_bump_token [".__LINE__."]";
	$conf[]="# --------- SquidAsMasterPeer = $SquidAsMasterPeer $SquidAsMasterPeerPort/$SquidAsMasterPeerPortSSL [".__LINE__."]";
	$conf[]="# --------- Manager Port =  $this->MGR_LISTEN_PORT [".__LINE__."]";
	$conf[]="# --------- NAT compatible =  $this->EnableTransparent27 [".__LINE__."]";
	$conf[]="# --------- Transparent =  $this->hasProxyTransparent [".__LINE__."]";
	$myportToKen=$this->myport_token();
	$sql="SELECT * FROM proxy_ports WHERE enabled=1";
	$results = $q->QUERY_SQL($sql);
	$transparent_token=$this->transparent_token();
	$conf[]="# --------- proxy_ports ". mysql_num_rows($results)." ports";
	
	if( $this->EnableTransparent27==1){
		if($this->ICP_PORT==0){$this->ICP_PORT=3130;}
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$port=$ligne["port"];
		$transparent_text=null;
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$ipaddr])){ 
			$conf[]="# --------- table proxy_ports $ipaddr:$port -> Hardware Error [".__LINE__."]\n";
			$conf[]="# --------- http $ipaddr -> Hardware Error [".__LINE__."]\n";
			$ipaddr="0.0.0.0"; 
		}
		
		if($ligne["transparent"]==1){
			if($this->EnableTransparent27==1){continue;}
			$this->TRANSPARENT_PORTS_LIST[]=$port;
			$transparent_text=$transparent_token;
		}else{
			$this->MyPortsArray[]=$port;
		}
		
		$conf[]="http_port $ipaddr:$port $transparent_text";
		$conf[]="acl MyPort{$ligne["ID"]} $myportToKen $port";
	}
	
	
	$conf[]="http_port 0.0.0.0:$this->MGR_LISTEN_PORT";
	if($SquidAsMasterPeerIPAddr==null){$SquidAsMasterPeerIPAddr="0.0.0.0";}
	if($SquidAsMasterPeerIPAddr<>"0.0.0.0"){
		if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidAsMasterPeerIPAddr])){
			$conf[]="# --------- SquidAsMasterPeerIPAddr = $SquidAsMasterPeerIPAddr -> INTERFACE ERROR back to default \"0.0.0.0\"  [".__LINE__."]";
			$SquidAsMasterPeerIPAddr="0.0.0.0";
		}
	}
	
	
	
	
	$conf[]="# --------- SSL_BUMP = $this->SSL_BUMP  [".__LINE__."]";
	if($CooVaHTTPPort<>null){
		$conf[]="# --------- http port for hotspot [".__LINE__."]\n";
		$conf[]=$CooVaHTTPPort;
	}
	
	if($ArticaHotSpotPort<>null){
		$conf[]="# --------- http port for Artica hotspot [".__LINE__."]\n";
		$conf[]=$ArticaHotSpotPort;
		$this->TRANSPARENT_PORTS_LIST[]=$this->ArticaHotSpotPort;
		$this->TRANSPARENT_PORTS_LIST[]=$this->ArticaSSLHotSpotPort;
		
	}
	
	
	
	if($this->SSL_BUMP==0){
		$this->EnableSquidSSLCRTD=0; 
		$http_port_sslbump_2=null; 
		$http_port_sslbump=null;
		$http_port_sslbump_3=null;
	}
	
	if($this->wccp2_enabled==1){
		$conf[]="# --------- http port ( $transparent_token ) for WCCP2 [".__LINE__."]\n";
		$conf[]="http_port {$this->WCCPHash["listen_address"]}:$this->WCCPListenPort$transparent_token\n";
		$this->TRANSPARENT_PORTS_LIST[]=$this->WCCPListenPort;
	}
	
	
	if($this->hasProxyTransparent==0){
		$this->MyPortsArray[$this->listen_port]=$this->listen_port;
		$conf[]="# --------- http port ( $this->listen_port ) Transparent = No [".__LINE__."]\n";
		$conf[]="http_port $this->SquidBinIpaddr$this->listen_port$http_port_sslbump\n";
	}else{
		$this->TRANSPARENT_PORTS_LIST[]=$this->listen_port;
		if($this->EnableTransparent27==0){
			$conf[]="# --------- http port ( $this->listen_port ) Transparent = Yes [".__LINE__."]\n";
			$conf[]="http_port $this->SquidBinIpaddr$this->listen_port$this->transparent\n";
		}
		
		if($this->EnableTransparent27==1){
			$conf[]="# --------- http port ( $this->listen_port ) Transparent/NAT compatible = Yes [".__LINE__."]\n";
			$conf[]="http_port 127.0.0.1:$this->listen_port$http_port_sslbump_3\n";			
		}
	}
	
	if($this->second_port_transparent<>null){$conf[]="$this->second_port_transparent$http_port_sslbump_2\n";}
	
	if($this->second_listen_port>0){
		
		$this->MyPortsArray[$this->second_listen_port]=$this->second_listen_port;
		if($this->DisableSSLStandardPort==1){
			$conf[]="# Second listen port With SSL `DISABLED` on standard port[".__LINE__."]";
			$conf[]="http_port $this->SquidBinIpaddr$this->second_listen_port\n";
		}else{
			$conf[]="# Second listen port With SSL `ENABLED` on standard port[".__LINE__."]";
			$conf[]="http_port $this->SquidBinIpaddr$this->second_listen_port$http_port_sslbump_2\n";
		}
	}
	
	if($this->SquidBubbleMode==1){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclport=$ligne["aclport"];
			$Interface=$ligne["interface"];
			if($Interface==null){$Interface="0.0.0.0";}
			$conf[]="http_port {$Interface}:$aclport\n";
		}
	
	}
	
	if($this->smartphones_port>0){
		$conf[]="# Smartphones listen port [".__LINE__."]";
		$conf[]="http_port $this->SquidBinIpaddr$this->smartphones_port$http_port_sslbump_2";
	}
	
	if($SquidAsMasterPeer==1){
		$conf[]="# --------- http port Master peer [".__LINE__."]\n";
		$conf[]="http_port $SquidAsMasterPeerIPAddr:$SquidAsMasterPeerPort";
		
	}
	
	
	$ssl_port_text=$this->https_port();
	
	if($GLOBALS["VERBOSE"]){
		echo "\n\n ********************* SSL PORT (1) ********************\n";
		echo "$ssl_port_text\n";
		echo "************************************************************\n\n";
	}
	
	if($ssl_port_text<>null){
		$conf[]="\n";
		$conf[]="# --------- SSL Listen Port [".__LINE__."]";
		$conf[]="$ssl_port_text";
	}
	
	
	
	$conf[]="# --------- ICP Port: $this->ICP_PORT [".__LINE__."]";
	if($this->ICP_PORT>10){
		if($this->HTCP_PORT>10){
			if($this->ICP_PORT==$this->HTCP_PORT){$this->ICP_PORT=$this->ICP_PORT+1;}
			}
			$conf[]="icp_port $this->ICP_PORT";
		}
		if($this->HTCP_PORT>10){
		
		$conf[]="htcp_port $this->HTCP_PORT"; // should 4827
		}	
		
	$conf[]="#";
	$conf[]="#";
	return @implode("\n",$conf)."\n";
	
}

public function dns_nameservers($dnsmasq=false){
	$sock=new sockets();
	$noresol=false;
	$EnableLocalDNSMASQ=$sock->GET_INFO('EnableLocalDNSMASQ');
	if(!is_numeric($EnableLocalDNSMASQ)){$EnableLocalDNSMASQ=0;}
	writelogs("dns server: ".count($this->dns_array) . ' rows',__CLASS__.'/'.__FUNCTION__,__FILE__);
	
	$dns_nameservers=array();
	if(!$dnsmasq){
		if($EnableLocalDNSMASQ==1){
			if($GLOBALS["VERBOSE"]){echo " ***** DNS: add 127.0.0.1 at the top\n";}
			$dns_nameservers[]="127.0.0.1";
		}
	}
	
	if(count($this->dns_array)==0){
		$dns_nameservers_from_resolv=$this->dns_nameservers_from_resolv(true);
		while (list ($num, $val) = each ($dns_nameservers_from_resolv) ){
			$dns_nameservers[]=$val;
		}
		$noresol=true;
	}
	

		
	if(count($this->dns_array)>0){
		reset($this->dns_array);
		if($GLOBALS["VERBOSE"]){echo " ***** DNS: start loop ".count($this->dns_array) ." items\n";}
		while (list ($num, $val) = each ($this->dns_array) ){
			if(preg_match("#resolv#", $val)){
				if(!$noresol){
					$dns_nameservers_from_resolv=$this->dns_nameservers_from_resolv(true);
					while (list ($num, $val) = each ($dns_nameservers_from_resolv) ){
						$dns_nameservers[]=$val;
					}
				}
				continue;
			}
			if($GLOBALS["VERBOSE"]){echo " ***** DNS: $val\n";}
			$dns_nameservers[]=$val;
		}
	}else{
		if($GLOBALS["VERBOSE"]){echo " ***** DNS: dns_array 0 items\n";}
	}
	
	while (list ($num, $val) = each ($dns_nameservers) ){
		$CLEAN[$val]=true;
	}
	
	$dns_nameservers=array();
	while (list ($num, $val) = each ($CLEAN) ){
		$dns_nameservers[]=$num;
	}
	
	if($dnsmasq){
		if(count($dns_nameservers)==0){return array();}
		return $dns_nameservers;
	}
	
	
	
	
	if(count($dns_nameservers)>0){
		return "dns_nameservers " . @implode(" ", $dns_nameservers) . "\n";
	}	
}

		
function BuildSquidConf(){
		$sock=new sockets();
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		$conf=null;
		$acl_access_time=null;
		$this->transparent=null;
		$this->second_port_transparent=null;
		$http_access_time=null;
		
		$prefixacls=null;
		$acl_groups=array();
		$ws=new squidworkers();
		$this->SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if($this->SquidBinIpaddr==null){$this->SquidBinIpaddr="0.0.0.0";}
		if($this->SquidBinIpaddr<>null){$this->SquidBinIpaddr="$this->SquidBinIpaddr:";}
		
		$this->NETWORK_ALL_INTERFACES();if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidBinIpaddr])){$SquidBinIpaddr="0.0.0.0";}
		$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
		$SquidBoosterMemK=$sock->GET_INFO("SquidBoosterMemK");
		$SquidBoosterOnly=$ws->zcheck_squidbooster_value();
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		
		$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
		$this->EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
		$this->CnTLMPORT=$sock->GET_INFO("CnTLMPORT");
		$this->ArticaHotSpotPort=$sock->GET_INFO("ArticaHotSpotPort");
		$this->ArticaSSLHotSpotPort=$sock->GET_INFO("ArticaSSLHotSpotPort");
		if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
		if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
		if(!is_numeric($SquidBoosterMemK)){$SquidBoosterMemK=50;}
		if(!is_numeric($this->max_filedesc)){$this->max_filedesc=8192;}
		if(!is_numeric($this->EnableCNTLM)){$this->EnableCNTLM=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		if($this->isNGnx()){$this->SquidActHasReverse=0;}
		if(!is_numeric($this->CnTLMPORT)){$this->CnTLMPORT=0;}
		if(!is_numeric($this->ArticaHotSpotPort)){$this->ArticaHotSpotPort=0;}
		if($this->CnTLMPORT==0){$this->CnTLMPORT=rand(35000, 64000);$sock->SET_INFO("CnTLMPORT", $this->CnTLMPORT);}
		if($this->ArticaHotSpotPort==0){$this->ArticaHotSpotPort=rand(38000, 64000);$sock->SET_INFO("ArticaHotSpotPort", $this->ArticaHotSpotPort);}
		if($this->ArticaSSLHotSpotPort==0){$this->ArticaSSLHotSpotPort=rand(38500, 64000);$sock->SET_INFO("ArticaSSLHotSpotPort", $this->ArticaSSLHotSpotPort);}
		$SquidAsMasterCacheChilds=$sock->GET_INFO("SquidAsMasterCacheChilds");
		$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
		$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
		$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
		if(!is_numeric($SquidAsMasterCacheChilds)){$SquidAsMasterCacheChilds=1;}
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		if($SquidCacheLevel==0){$this->DisableAnyCache=1;}
		if($this->SquidUrgency==1){
			$SquidCacheLevel=0;
			$this->DisableAnyCache=1;
		}

		
		$this->listen_port=intval(trim($this->listen_port));
		if($this->enable_dansguardian==1){$this->listen_port=$this->alt_listen_port;}
		
		if($this->hasProxyTransparent==1){
			if($this->EnableTransparent27==0){
				$this->transparent=$this->transparent_token();
				$this->second_listen_portForTransparent=$this->listen_port+1;
				if($this->second_listen_portForTransparent==$this->ssl_port){$this->second_port_transparent=$this->ssl_port+1;}
				$this->second_port_transparent="http_port $this->SquidBinIpaddr$this->second_listen_portForTransparent";
				$this->MyPortsArray[$this->second_listen_portForTransparent]=$this->second_listen_portForTransparent;
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid : Transparent: Adding a second port $this->second_listen_portForTransparent\n";}
				$this->TRANSPARENT_PORTS_LIST[]=$this->listen_port;
			}
		}
		
		
		$this->haarp_config();
		
		$compilation_params=$this->compilation_params();
		if(!isset($compilation_params["enable-arp-acl"])){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [VER]: --enable-arp-acl no such token disable MAC features..\n";}
			$this->EnableMacAddressFilter=0;
		}
	
		
		if($this->DisableAnyCache==1){$SquidBoosterMem=0;}

		
		
		if($this->ASROOT){
			echo "Starting......: ".date("H:i:s")." Squid checking google non-ssl...\n";
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$php5=$unix->LOCATE_PHP5_BIN();
			shell_exec("$php5 /usr/share/artica-postfix/exec.nosslsearch.google.com.php --run");
		}
		
		if($SquidBoosterMem>0){
			
			$SquidBoosterMemK=$SquidBoosterMemK*1024;
			$this->store_dir_minsize="min-size=$SquidBoosterMemK";
		}
		
		if($this->EnableUserAgentBanAll==1){
			$conf=$conf. "acl AllowedBrowsers browser \"/etc/squid3/allowed-user-agents.acl\"\n";
		}
		
		
		
		$EnableMalwarePatrol=$sock->GET_INFO("EnableMalwarePatrol");
		
		
		if(!is_numeric($EnableMalwarePatrol)){$EnableMalwarePatrol=0;}
		// Disable Malware Patrol.... Patch 2012-11-20
		$EnableMalwarePatrol=0;
		
		if($this->ASROOT){
			$articaver=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
			echo "Starting......: ".date("H:i:s")." Squid Version $this->SQUID_BIN_VERSION / Artica Version $articaver\n";
			$conf=$conf . "# Squid version $this->SQUID_BIN_VERSION\n";
			$conf=$conf . "# Builded by Artica v.$articaver\n";
			$conf=$conf . "# Date: ".date("Y-m-d H:i:s")."\n";
			if($this->IS_30){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.x OK\n";}}
			if(!$this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is NOT 3.2.x OK\n";}}
			if($this->IS_32){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." Squid : is 3.2.x OK\n";}}
		}
		
		
		if($this->IS_30){$conf=$conf . "# IS 3.0 YES\n";}
		if($this->IS_32){$conf=$conf . "# IS 3.2 YES\n";}
		if($this->IS_31){$conf=$conf . "# IS 3.1 YES\n";$this->store_dir_minsize=null;}  
		
		if($this->SquidActHasReverse<>1){
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
		

		
	if($this->SquidActHasReverse==1){
			$conf=$conf."\n# -> Reverse Proxy is enabled <-\n";
			$conf=$conf."include /etc/squid3/reverse.conf\n";
			if(!is_file("/etc/squid3/reverse.conf")){@file_put_contents("/etc/squid3/reverse.conf", "#\n");}
			if($this->SquidReverseDefaultWebSite<>null){$defaultWWW=" defaultsite=$this->SquidReverseDefaultWebSite ";}
			$this->transparent=" accel vhost vport $defaultWWW";
			$this->listen_port=intval(trim($sock->GET_INFO("SquidActHasReverseListenPort")));
			if($this->listen_port<3){$this->listen_port=80;}
			
			
			if($this->ASROOT){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				$nginx=$unix->find_program("nginx");
				if(is_file($nginx)){
					$sock=new sockets();
					$DisableNginxProxy=$sock->GET_INFO("DisableNginxProxy");
					if(!is_numeric($DisableNginxProxy)){$DisableNginxProxy=0;}
					if($DisableNginxProxy==0){
						echo "Starting......: ".date("H:i:s")." [REVERSE]: Nginx is installed and enabled...\n";
						if($this->listen_port==80){$this->listen_port=8080;}
						if($this->ssl_port==443){$this->ssl_port=4443;}
					}
				}
			}			
			
			
			$conf=$conf.$this->squid_reverse_websites();
		}		
		
		
		$conf=$conf.$this->build_http_ports();
		
		
		// Local port ( without transparent mode )
		
		
		if($this->hasProxyTransparent==0){
			
			$this->MyPortsArray[$this->listen_port]=$this->listen_port;
			$this->MyPortsArray[$this->get_ssl_port()]=true;
		}
		if($this->EnableTransparent27==1){
			$this->MyPortsArray[$this->listen_port]=$this->listen_port;
			$this->MyPortsArray[$this->get_ssl_port()]=true;			
		}
		
		
		$this->MyStandPort=null;
		$d=array();
		$token=$this->myport_token();
		
		$conf=$conf."# --------- NormalPorts ".count($this->MyPortsArray)." Item(s) [".__LINE__."]\n";
		
		while (list ($port, $ligne) = each ($this->MyPortsArray) ){if(!is_numeric($port)){continue;}$d[]=$port;}
		$StandardPorts=@implode(" ", $d);
	
		
		$CoovaChilliAcls=$this->CoovaChilliAcls();
		if($CoovaChilliAcls<>null){$conf=$conf. "$CoovaChilliAcls\n";}
		$kav_acl="acl acl_kav_GET method GET\n";
		$conf=$conf. "acl FTP proto FTP\n";
		if($this->IS_27){$conf=$conf."acl all src 0.0.0.0/0\n";}
		$conf=$conf."acl localhost src 127.0.0.1/32\n";
		$conf=$conf."acl to_localhost dst 127.0.0.0/8\n";
		$conf=$conf."acl NormalPorts $token $StandardPorts\n";
		if($this->IS_33){
			$conf=$conf."acl MobilePhones browser -i (CFNetwork|iPhone|iPad|Mobile|Android|BlackBerry|PalmSource|SymbianOS|IEMobile|Opera Mobi|Apple-iPhone|Apple-iPad|Apple-iPod)\n";
			$conf=$conf."acl MobilePhones browser -i (android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i || substr($ENV{'HTTP_USER_AGENT'}, 0, 4) =~ m/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i)\n";
		}
		$conf=$conf."acl ToArticaWWW dstdomain .artica.fr .articatech.net .articatech.com\n";
		$conf=$conf."acl DenyBlacksites dstdomain \"/etc/squid3/www-blacklists.db\"\n";
		if(!is_file("/etc/squid3/www-blacklists.db")){@touch("/etc/squid3/www-blacklists.db");}
		$conf=$conf .$this->ACL_ChildProxysIPs()."\n";
		$conf=$conf .$this->ACL_MyLocalIpsDest();"\n";
		
		if($SquidAsMasterPeer==1){
			$conf=$conf."acl ChildProxyPorts $token $SquidAsMasterPeerPort $SquidAsMasterPeerPortSSL\n";

		
		}
		
		if($this->smartphones_port>0){
			$conf=$conf . "acl MyMobilesPort $token $this->smartphones_port\n";
		}
		
		if($GLOBALS["HAARP_ACL"]<>null){$conf=$conf."{$GLOBALS["HAARP_ACL"]}\n";}
		$isp_networks=$this->isp_networks();
		if($isp_networks<>null){$conf=$conf."$isp_networks\n";}
		
		$kav_acl=$this->acls_multimedias();
		if($this->SquidDisableAllFilters==0){
			$acl_blocked_sites="acl blockedsites url_regex \"/etc/squid3/squid-block.acl\"\n";
		}
			
		$conf=$conf.$this->ACLS_DROPBOX()."\n";
		$conf=$conf.$this->ACLS_SKYPE()."\n";
		$conf=$conf.$this->ACLS_OFFICE365()."\n";
		$conf=$conf.$this->ACLS_GOOGLE()."\n";
		$conf=$conf.$this->ACLS_BROWSERS()."\n";
		$squid_sessions_builder=$this->squid_sessions_builder();
	
		$conf=$conf . "#--------- Authentication methods (v 2013 - Sept 16)\n";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] Building NTLM Settings\n";}
		

		$ntlm_auth_conf=$this->ntlm_auth_conf();
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [AUTH] Building NTLM  = ".strlen($ntlm_auth_conf)." bytes\n";}
		if(strlen($ntlm_auth_conf)>0){$conf=$conf . $ntlm_auth_conf;}
		
		
		$conf=$conf . $this->ldap_auth_conf();
		$conf=$conf . $this->auth_param_basic(true);

		


		//$conf=$conf . $this->krb_auth_conf();
		if(isset($GLOBALS["ACL_AUTHENTICATED"])){
			$conf=$conf.$GLOBALS["ACL_AUTHENTICATED"]."\n";
		}
		
		
		$conf=$conf . $this->AclsStreamCache();
		$conf=$conf . $this->external_acl_helper_MacToUid();
		$conf=$conf.  $this->external_acl_helper_splash_screen();
		$conf=$conf.  $this->external_acl_helper_nudity_scanner();
		$conf=$conf.  $this->external_acl_dynamic_helper();
		$conf=$conf.  $this->external_acl_helper_time_quota_acl();
		$conf=$conf.  $this->external_acl_helper_ITChart();
		$conf=$conf.  $this->external_acl_helper_categories();
		$conf=$conf . $this->external_acl_helper_quota();
		
		$conf=$conf . "#--------- Bubble Mode ($this->SquidBubbleMode)\n";
		
		if($this->SquidBubbleMode==1){
			$conf=$conf . "acl MyStandPort $token $StandardPorts\n";
			$this->MyStandPort="MyStandPort ";
			$q=new mysql_squid_builder();
			$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$aclport=$ligne["aclport"];
				$conf=$conf."acl MyPort{$ligne["aclport"]} $token $aclport\n";
			}	
		}
		
		if($squid_sessions_builder<>null){$conf=$conf . $squid_sessions_builder."\n";}
		
		$quick_abort_min="0 KB";
		if(($this->ForceWindowsUpdateCaching==1) OR ($this->ProxyDedicateMicrosoftRules==1)){
			$quick_abort_min="-1 KB";
		}
		

		
		$conf=$conf . "#--------- TWEEKS PERFORMANCES\n";
		$conf=$conf . "# http://blog.last.fm/2007/08/30/squid-optimization-guide\n";
		$conf=$conf.$this->memory_pools()."\n";
		
		

		
		$conf=$conf . "quick_abort_min $quick_abort_min\n";
		$conf=$conf . "quick_abort_max 0 KB\n";
		if(($this->ForceWindowsUpdateCaching==1) OR ($this->ProxyDedicateMicrosoftRules==1)){
			$conf=$conf . "range_offset_limit -1\n";
		}
		$conf=$conf . "log_icp_queries off\n";
		$conf=$conf . "client_db on\n";
		$conf=$conf . "buffered_logs off\n";
		$conf=$conf . "half_closed_clients off\n";
		if($this->max_filedesc>1024 ){$conf=$conf . "max_filedesc $this->max_filedesc\n";}
		
		

		
		$conf=$conf . "#--------- User defined if /etc/squid3/myconf.conf exists [".__LINE__."]\n";
		if(is_file("/etc/squid3/myconf.conf")){
			@chown("/etc/squid3/myconf.conf", "squid");
			$conf=$conf."include /etc/squid3/myconf.conf\n";
		}

		
		
		if($this->SquidDisableAllFilters==0){
			$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"]="http_access deny {$this->MyStandPort}blockedsites";
		}
		
		$conf=$conf.$this->mgr_port();
		// UfdbGuard web filter section
		$conf=$conf.$squidGuard."\n";
		if(!$this->IS_27){
			if($this->url_rewrite_bypass==1){$conf=$conf."url_rewrite_bypass on\n";}else{$conf=$conf."url_rewrite_bypass off\n";}
		}
		
		
		$conf=$conf. "acl CONNECT method CONNECT\n";
		$conf=$conf. "acl purge method PURGE\n";
		$conf=$conf . "#\n";
		$conf=$conf  ."\n#--------- acls\n";
		$conf=$conf  .$acl_blocked_sites;
		
		
		
		
		$http_port_transparent_acl=$this->http_port_transparent_acl();
		if($http_port_transparent_acl<>null){$conf=$conf.$http_port_transparent_acl."\n";}
		
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
							$http_access_time_array[]="http_access deny {$this->MyStandPort}ldap_{$re[1]}_{$re[2]} !{$re[1]}_{$re[2]}_time";
							
								if($this->SquidLdapAuthEnableGroups==1){
									$acgroup="acl ldap_{$re[1]}_{$re[2]} external ldap_group {$re[1]}";
									$acl_groups[$acgroup]=$acgroup;
								}
							
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
				$conf=$conf ."# LDAP_AUTH =1  EnableKerbAuth = 0 SquidLdapAuthEnableGroups=$this->SquidLdapAuthEnableGroups\n";
				if($this->SquidLdapAuthEnableGroups==1){
					$conf=$conf ."acl group_password external ldap_group\n";
				}
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
		
		$conf=$conf."\n\n#--------- http_access: MAIN RULES... [".__LINE__."]\n";
		
		if($this->ProxyDedicateMicrosoftRules==1){
			$conf=$conf . "#--------- On TOP: Microsoft dedicated rules [".__LINE__."]\n";
			$this->MICROSOFT_ACLS();
			@chown("/etc/squid3/microsoft.conf", "squid");
			$conf=$conf."include /etc/squid3/microsoft.conf\n";
		}else{
			$conf=$conf . "#--------- http_access: Microsoft dedicated rules are disabled: `$this->ProxyDedicateMicrosoftRules` [".__LINE__."]\n";
		}
		
		
		
		

		$conf=$conf."\n\n#--------- http_access: on TOP: Authentication rules...\n";
		$NormalPorts="NormalPorts ";
		if($this->MyStandPort<>null){$NormalPorts=$this->MyStandPort;}
		$MobilePhones=null;
		$MyMobilesPort=null;
		if($this->IS_33){
			if($this->SquidAllowSmartPhones==1){$MobilePhones="!MobilePhones ";}
		}
		if($this->smartphones_port>0){$MobilePhones="!MyMobilesPort ";}
		
		if($this->LDAP_AUTH==1){
				$acl_access_time=$http_access_time;
				if($this->EnableKerbAuth==0){
					if($this->SquidLdapAuthEnableGroups==1){
						$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"]="http_access deny $NormalPorts$MobilePhones!group_password";
					}
				}
				
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts$MobilePhones!ldapauth\n";
		}
		
		if($this->NTLM_AUTH==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts$MobilePhones!ntlm_users all\n";
				@mkdir("/var/cache/samba/winbindd_privileged",0750,true);
				$office_network=null;
		}

		

		if($this->EnableKerbAuth==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts$MobilePhones!AUTHENTICATED all\n";
				$office_network=null;
		}

		if($this->EnableKerberosAuthentication==1){
				$acl_access_time=$http_access_time;
				$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"]="http_access deny $NormalPorts$MobilePhones!AUTHENTICATED all\n";
				$office_network=null;			
		}
		

		
		if($this->SquidActHasReverse<>1){
			if($this->enable_dansguardian==1){
				$office_network=null;
			}
		}		
		


		
	
		
		$conf=$conf."\n# --------- SAFE ports\n";
		$conf=$conf.$this->SAFE_PORTS();
		

		$conf=$conf."#\n";

		/*if($EnableMalwarePatrol==1){
			if($this->SquidDisableAllFilters==0){
				//$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"]="http_access deny malware_block_list";
			}
		}*/
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["request_header_max_size"],$re)){
				$request_header_max_size=$re[1];
				if(!is_numeric($request_header_max_size)){$request_header_max_size=0;}
				if($request_header_max_size>0){
					if($request_header_max_size<256){$request_header_max_size=256;}
					
				}
		}
		
	if(preg_match("#([0-9]+)#",$this->global_conf_array["client_request_buffer_max_size"],$re)){
				$client_request_buffer_max_size=$re[1];
				if(!is_numeric($client_request_buffer_max_size)){$client_request_buffer_max_size=512;}
				if($client_request_buffer_max_size>0){
					if($client_request_buffer_max_size<512){$client_request_buffer_max_size=512;}
				}
		}		
		
		
		
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid request header max size {$this->global_conf_array["request_header_max_size"]} $request_header_max_size:KB\n";}			
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["reply_body_max_size"],$re)){
				$reply_body_max_size=$re[1];
				if($request_header_max_size==0){$reply_body_max_size=0;}
				if(!is_numeric($reply_body_max_size)){$reply_body_max_size=0;}
				if($reply_body_max_size<$request_header_max_size){$reply_body_max_size=$reply_body_max_size*2;}
			}
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["request_body_max_size"],$re)){
			$request_body_max_size=$re[1];
			if(!is_numeric($request_body_max_size)){$request_body_max_size=0;}			
		}
		if(preg_match("#([0-9]+)#",$this->global_conf_array["reply_header_max_size"],$re)){
			$reply_header_max_size=$re[1];
			if(!is_numeric($reply_header_max_size)){$reply_header_max_size=64;}			
		}
		
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid maximum object size in memory {$this->global_conf_array["maximum_object_size_in_memory"]}\n";}
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["maximum_object_size_in_memory"],$re)){
			$maximum_object_size_in_memory=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["maximum_object_size_in_memory"],$re)){
					$maximum_object_size_in_memory_unit=$re[1];
			}
			
			
			
			if($maximum_object_size_in_memory_unit==null){$maximum_object_size_in_memory_unit="KB";}
			if($maximum_object_size_in_memory_unit=="KB"){
				if($maximum_object_size_in_memory<512){$maximum_object_size_in_memory=1024;}
			}
		}
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["minimum_object_size"],$re)){
			$minimum_object_size=$re[1];
			if(preg_match("#([A-Z]+)#",$this->global_conf_array["minimum_object_size"],$re)){$minimum_object_size_unit=$re[1];}
			if($minimum_object_size_unit==null){$minimum_object_size_unit="KB";}
			if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		}		
		
		
		
		if(preg_match("#([0-9]+)#",$this->global_conf_array["maximum_object_size"],$re)){
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

		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: checks declared Load-balancers...\n";}
		$x_forwarded_for=$this->x_forwarded_for();
		if($x_forwarded_for<>null){$conf=$conf .$x_forwarded_for."\n";}else{
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ LB ]: NULL Rules...\n";}
		}
		
		$conf=$conf .$acl_access_time;
		
		$conf=$conf .$this->ACL_FTP_RESTRICTIONS();
		$conf=$conf. $this->WHITELISTED_AUTH();
		$conf=$conf .$this->ACL_WHITE_COMPUTERS_IP();
		if($this->ACL_ARP_ENABLED){
			$conf=$conf ."\nacl whitelisted_mac_computers arp \"/etc/squid3/whitelisted-computers-by-mac.acl\"\n";
		}
		$ACL_TEMPLATES_URIS=$this->ACL_TEMPLATES_URIS();
		$ACL_HOTSPOT_URIS=$this->ACL_HOTSPOT_URIS();
		$ACL_WHITELISTED_FOR_ANTIVIRUS=$this->ACL_WHITELISTED_FOR_ANTIVIRUS();
		$ACL_WHITELISTED_MIMETYPES=$this->ACL_WHITELISTED_MIMETYPES();
		if($ACL_WHITELISTED_MIMETYPES<>null){$conf=$conf ."\n$ACL_WHITELISTED_MIMETYPES\n";}
		if($ACL_WHITELISTED_FOR_ANTIVIRUS<>null){$conf=$conf ."\n$ACL_WHITELISTED_FOR_ANTIVIRUS\n";}
		if($ACL_TEMPLATES_URIS<>null){$conf=$conf ."\n$ACL_TEMPLATES_URIS\n";}
		if($ACL_HOTSPOT_URIS<>null){$conf=$conf ."\n$ACL_HOTSPOT_URIS\n";}		
		
		if($this->SquidDisableAllFilters==0){
			$conf=$conf .$this->ACL_BANNED_COMPUTERS_IP();
			$conf=$conf .$this->ACL_MESSENGERS();
		}
		if($this->ASROOT){
			$GLOBALS["aclGen"]=new squid_acls();
			$GLOBALS["aclGen"]->Build_Acls();
			echo "Starting......: ".date("H:i:s")." [ACLS]: (class.squid.inc) L.".__LINE__." ".count($GLOBALS["aclGen"]->acls_array)." ACLs\n";
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
		
		$conf=$conf . "\n# # # # # # # # # # # # # # # # # # EXTERNAL CACHES # # # # # # # # # # # # # # # # # #\n";
		$conf=$conf . "#\n";
		$conf=$conf . $this->cache_peer_streamcache()."\n";
		if(isset($GLOBALS["HAARP_CACHE_PEER"])){$conf=$conf . "{$GLOBALS["HAARP_CACHE_PEER"]}\n";}else{$conf=$conf . "# HAARP_CACHE_PEER not set\n";}
		$conf=$conf . $this->cache_peer()."\n";
		$conf=$conf . "#\n";
		$conf=$conf . "#\n";
		
		
		$conf=$conf."\n# # # # # # # # # # # # # # # # # # RULES DEFINITIONS # # # # # # # # # # # # # # # # # #\n";
		
		$conf=$conf.$this->http_access();
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [ICAP]: SquidDisableAllFilters=$this->SquidDisableAllFilters\n";}
		if($this->SquidDisableAllFilters==0){
			$icap=new icap();
			$icap->build_services();
			$conf=$conf."# --------- ICAP Services: $icap->CountDeServices service(s) [".__LINE__."]\n";
			$conf=$conf.@implode("\n", $icap->icap_lines)."\n";
			
			//$ecap=new ecap();
			$conf=$conf."# --------- eCAP Services - not suppoorted yet...\n";
			//$conf=$conf.$ecap->build();
			
		}else{
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid : WARNING, `Disable all filters` is enabled, No icap services will be linked...\n";}
		}
		
		
		
		
		$conf=$conf."\n";
		$conf=$conf.$this->WCCP();
		$conf=$conf.$this->SNMP();
		$conf=$conf.$this->request_header_add();
		$conf=$conf."\n";
		$conf=$conf."# --------- ident_lookup_access\n";
		$conf=$conf."hierarchy_stoplist cgi-bin ?\n";
		$conf=$conf."\n";
		$conf=$conf."# --------- General settings \n";
		$conf=$conf."visible_hostname ".$this->visible_hostnameF()."\n";
		$conf=$conf."unique_hostname ".$this->my_full_hostname()."\n";
		if(($this->IS_30) && (!$this->IS_32)){
			$conf=$conf."ignore_expect_100 $ignore_expect_100\n";
		}
		
		$EnableSplashScreenX=$this->EnableSplashScreen;
		if($EnableSplashScreenAsObject==1){$EnableSplashScreenX=1;}
		
		$conf=$conf."# --------- Add X-Forwarded-for in headers [$EnableSplashScreenX]? \n";
		if($EnableSplashScreenX==1){$this->forwarded_for="on";}
		
		if($EnableSplashScreenX==1){
			if($this->IS_33){
				if(isset($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"])){
					$conf=$conf."# --------- SplashScreen enabled, add  X-forwarded-for for FreeWebs\n";
					$conf=$conf."request_header_add X-forwarded-for %>a SplashScreens\n";
				}
			}
		}
		
		if($this->forwarded_for<>null){
			$conf=$conf."forwarded_for $this->forwarded_for\n";
		}
		
		
		
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."# --------- time-out \n";
		if(isset($GLOBALS["dead_peer_timeout"])){
			$conf=$conf."dead_peer_timeout {$GLOBALS["dead_peer_timeout"]} seconds\n";	
		}else{
			$conf=$conf."dead_peer_timeout {$this->dead_peer_timeout} seconds\n";
		}
		
		
		
		$conf=$conf."forward_max_tries {$this->forward_max_tries}\n";
		$conf=$conf."forward_timeout {$this->forward_timeout} seconds\n";
		$conf=$conf."connect_timeout {$this->connect_timeout} seconds\n";
		$conf=$conf."persistent_request_timeout $this->persistent_request_timeout seconds\n";
		$conf=$conf."pconn_timeout $this->pconn_timeout seconds\n"; 
		$conf=$conf."shutdown_lifetime $this->shutdown_lifetime seconds\n";
		$conf=$conf."read_timeout $this->read_timeout seconds\n";
		
		if($this->IS_27){
			$conf=$conf."incoming_rate $this->incoming_rate\n";
		}
		
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
		
		if($maximum_object_size_in_memory_unit==null){$maximum_object_size_in_memory_unit="MB";}
		if($minimum_object_size_unit==null){$minimum_object_size_unit="MB";}
		if($maximum_object_size_unit==null){$maximum_object_size_unit="MB";}
		if(!is_numeric($maximum_object_size)){$maximum_object_size=34;}
		if(!is_numeric($maximum_object_size_in_memory)){$maximum_object_size_in_memory=5;}
		if(!is_numeric($minimum_object_size)){$minimum_object_size=0;}
		
		
		if($SquidCacheLevel==0){
			$maximum_object_size=0;
			$maximum_object_size_unit="KB";
			$maximum_object_size_in_memory=0;
			$maximum_object_size_in_memory_unit="KB";
		}
		
		
		$conf=$conf."maximum_object_size $maximum_object_size $maximum_object_size_unit\n";
		$conf=$conf."minimum_object_size $minimum_object_size $minimum_object_size_unit\n";
		
		if($this->MEMORY_INSTALLEE<624288){$maximum_object_size_in_memory=128;$maximum_object_size_in_memory_unit="KB";}
		$conf=$conf."maximum_object_size_in_memory $maximum_object_size_in_memory $maximum_object_size_in_memory_unit\n";

		
		$conf=$conf."\n";
		$conf=$conf."\n";
		

		
		
		

		
		
		if(!isset($this->global_conf_array["tcp_outgoing_address"])){$this->global_conf_array["tcp_outgoing_address"]=null;}
		$CoovaChilli_tcp_outgoing_address=$this->CoovaChilli_tcp_outgoing_address();
		
		if($CoovaChilli_tcp_outgoing_address<>null){
			$conf=$conf."$CoovaChilli_tcp_outgoing_address\n";
		}
		
		if(trim($this->global_conf_array["tcp_outgoing_address"])<>null){
			$conf=$conf."tcp_outgoing_address {$this->global_conf_array["tcp_outgoing_address"]}\n";	
		}
		if($this->SquidBubbleMode==1){$prefixacls=" MyStandPort ";}
		$conf=$conf."\n";
		$conf=$conf ."# -----------------------------\n";
		$conf=$conf ."# --------- SSL Rules ---------[".__LINE__."]\n";
		$conf=$conf ."# -----------------------------\n";
		$conf=$conf .$this->ssl_dropbox();
		$conf=$conf .$this->ssl_skype();
		$conf=$conf. $this->ssl_office365();
		$conf=$conf .$this->ssl_bump_access();
		$conf=$conf."\n\n";
		$conf=$conf ."# --------- Caches \n\n";
		if(isset($GLOBALS["HAARP_CACHE_DENY"])){
			$conf=$conf ."{$GLOBALS["HAARP_CACHE_DENY"]}\n";
		}
		
		$conf=$conf ."# --------- SquidCacheLevel	=  $SquidCacheLevel [". __LINE__."]\n";
		$conf=$conf ."# --------- DisableAnyCache 	=  $this->DisableAnyCache [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterPeer =  $SquidAsMasterPeer [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterCacheChilds =  $SquidAsMasterCacheChilds [". __LINE__."]\n";
		$conf=$conf ."# --------- SquidAsMasterFollowxForward =  $SquidAsMasterFollowxForward [". __LINE__."]\n";
		
		
		if($SquidCacheLevel==0){
			$conf=$conf ."# --------- Do not cache ALL SITES [". __LINE__."]\n";
			$conf=$conf ."cache deny all\n";
		}
		
		if($this->DisableAnyCache==0){
			if($SquidAsMasterPeer==1){
				if($SquidAsMasterCacheChilds==0){
					if($SquidAsMasterFollowxForward==0){
						$conf=$conf ."# --------- Do not cache requests from Proxy children  [". __LINE__."]\n";
						$conf=$conf ."cache deny ChildProxysIPs\n";
					}else{
						$conf=$conf ."# --------- Do not cache requests from Proxy Master Port  [". __LINE__."]\n";
						$conf=$conf ."cache deny ChildProxyPorts\n";
					}
				}
			}
			
		

		
		
		$conf=$conf ."# --------- Do not cache from Browsers [". __LINE__."]\n";
		$conf=$conf ."cache deny BrowsersNoCache\n";
		
			
			
			$cache_deny_array=$this->cache_deny_array();
			$conf=$conf ."# --------- Do not cache these ". count($cache_deny_array)." website(s) [". __LINE__."]\n";
			if(count($cache_deny_array)>0){
				$conf=$conf .@implode("\n", $cache_deny_array)."\n";
				$conf=$conf ."cache deny {$prefixacls}DoNotCache\n"; 
				
			}
		
			$q=new mysql_squid_builder();
			$acls=new squid_acls_groups();
			$acls_deny=$acls->buildacls_bytype("cache_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){$conf=$conf ."cache deny {$prefixacls}$line\n";}
			}
			if($this->SquidBubbleMode==1){
				$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
				$results = $q->QUERY_SQL($sql);
				while ($ligne = mysql_fetch_assoc($results)) {
					$aclport=$ligne["aclport"];
					$MyPortObject="MyPort{$ligne["aclport"]}";
					$acls_deny=$acls->buildacls_bytype("cache_deny",$aclport);
					if(count($acls_deny)>0){
						while (list ($index, $line) = each ($acls_deny) ){$conf=$conf ."cache deny $MyPortObject$line\n";}
					}					
				}
			}
		}
	
		
		if(!preg_match("#^[0-9]+\s+[A-Z]+#", $this->global_conf_array["read_ahead_gap"])){
			$this->global_conf_array["read_ahead_gap"]="1 MB";
		}
		
		
		$conf=$conf . "cache_effective_user squid\n";
		$conf=$conf ."# --------- CACHE MANAGEMENT AND MEMORY [".__LINE__."]\n";
		
		
		if($this->MEMORY_INSTALLEE<624288){$this->global_conf_array["cache_mem"]="8 MB";}
		
		if($this->SquidUrgency==0){
			if(!preg_match("#([0-9]+)\s+([A-Za-z]+)#", $this->global_conf_array["cache_mem"])){
				$this->global_conf_array["cache_mem"]="{$this->global_conf_array["cache_mem"]} MB";
			}
			
			
			
			if($SquidCacheLevel==0){ $conf=$conf . "cache_mem 8 MB\n";}
			
			if($SquidCacheLevel>0){
				$conf=$conf . "cache_mem {$this->global_conf_array["cache_mem"]}\n";
				$conf=$conf . "read_ahead_gap {$this->global_conf_array["read_ahead_gap"]}\n";
				$conf=$conf . "cache_swap_high {$this->global_conf_array["cache_swap_high"]}\n";
				$conf=$conf . "cache_swap_low {$this->global_conf_array["cache_swap_low"]}\n";
				$conf=$conf . "store_avg_object_size 32 KB\n";
				$conf=$conf . "store_objects_per_bucket 50\n";
				$conf=$conf . "memory_replacement_policy lru\n";
			}
		
			if($this->DisableAnyCache==0){
				$CacheReplacementPolicyZ["lru"]="lru";
				$CacheReplacementPolicyZ["heap_GDSF"]="heap GDSF";
				$CacheReplacementPolicyZ["heap_LFUDA"]="heap LFUDA";
				$CacheReplacementPolicyZ["heap_LRU"]="heap LRU";
				$CacheReplacementPolicy=$sock->GET_INFO("CacheReplacementPolicy");
				if($CacheReplacementPolicy==null){$CacheReplacementPolicy="heap_LFUDA";}
				if(is_numeric($CacheReplacementPolicy)){$CacheReplacementPolicy="heap_LFUDA";}
				$CacheReplacementPolicyT=$CacheReplacementPolicyZ[$CacheReplacementPolicy];
				if($CacheReplacementPolicyT==null){$CacheReplacementPolicyT="heap LFUDA";}
				$conf=$conf ."#Policy $CacheReplacementPolicy\n";
				$conf=$conf ."cache_replacement_policy $CacheReplacementPolicyT\n";
				$conf=$conf .$this->CacheManager();
			}
	

		}
		
		$conf=$conf."# --------- DNS AND IP CACHES [".__LINE__."]\n";
		$conf=$conf .$this->append_domain()."\n";
		$conf=$conf ."dns_timeout {$this->dns_timeout} seconds\n";
		$conf=$conf ."ipcache_size {$this->global_conf_array["ipcache_size"]}\n";
		$conf=$conf ."ipcache_low {$this->global_conf_array["ipcache_low"]}\n";
		$conf=$conf ."ipcache_high {$this->global_conf_array["ipcache_high"]}\n";
		$conf=$conf ."fqdncache_size {$this->global_conf_array["fqdncache_size"]}\n";
		$conf=$conf ."positive_dns_ttl {$this->global_conf_array["positive_dns_ttl"]}\n";
		$conf=$conf ."negative_dns_ttl {$this->global_conf_array["negative_dns_ttl"]}\n";

		$conf=$conf ."\n\n# --------- SPECIFIC DNS SERVERS [".__LINE__."]\n";
		$conf=$conf.$this->dns_nameservers();
		
		$conf=$conf."# --------- PERSONNAL SETTINGS [".__LINE__."]\n";
		$conf=$conf."# To add your own tokens, just create a file under /etc/squid3/squid-me.conf,\n";
		$conf=$conf."# it will be merged here \n";
		if(is_file("/etc/squid3/squid-me.conf")){$conf=$conf."\n". @file_get_contents("/etc/squid3/squid-me.conf")."\n";}
		
		

		$conf=$conf.$this->ftp_parameters();
		$conf=$conf."debug_options ".$this->debug_options()."\n";
		
		$conf=$conf."# --------- CACHES RULES SquidCacheLevel:$SquidCacheLevel [".__LINE__."]\n";
		
		$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
		if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
		
		if($SquidCacheLevel>2){
			$conf=$conf."include /etc/squid3/refresh_patterns.conf\n";
			if(!is_file("/etc/squid3/refresh_patterns.conf")){@touch("/etc/squid3/refresh_patterns.conf");}
			@chown("/etc/squid3/refresh_patterns.conf","squid");
		}
		
		$refresh_pattern_list=$this->refresh_pattern_list();
		$conf=$conf.$refresh_pattern_list;
		
		

		
		if($this->EnableICPPort==1){$conf=$conf."icp_port 3130\n";}
		$conf=$conf."\n";
		$conf=$conf."\n";
		$conf=$conf."#Logs-------------------------------------------------\n";
		$conf=$conf."#We want to log the full URI for Youtube categorization.\n";
		$conf=$conf."strip_query_terms off\n";
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
		if($this->ASROOT){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$php=$unix->LOCATE_PHP5_BIN();
			$sh[]="#!/bin/sh";
			$sh[]="/bin/echo \"$1\n\" >>/var/log/squid/sslask.txt\n";
			$sh[]="/bin/echo `$php /usr/share/artica-postfix/exec.squid.sslpassword_program.php \$1`\n";
			@file_put_contents("/etc/squid3/ssldiscover", @implode("\n", $sh));
			@chmod("/etc/squid3/ssldiscover",0777);
		}
		
		if(isset($compilation_params["enable-ssl"])){
			$conf=$conf."sslpassword_program /etc/squid3/ssldiscover\n";
		}
		
		
		$conf=$conf."coredump_dir	/var/squid/cache\n";
		
		$conf=$conf."cache_log	/var/log/squid/cache.log\n";
		$conf=$conf."pid_filename	/var/run/squid/squid.pid\n";
		$compile_params=$this->compilation_params();
		$pinger=$this->pinger();
		if($pinger<>null){$conf=$conf.$pinger;}
		$conf=$conf.$this->access_logs();		
		$conf=$conf."# --------- CacheManagement2 = $this->CacheManagement2 DisableAnyCache = \"$this->DisableAnyCache\"/Urgency=\"$this->SquidUrgency\" [". __LINE__."]\n";
		$conf=$conf.$this->cache_dir_method_1()."\n";
		$conf=$conf.$this->cache_dir_method_2()."\n";
		$conf=$conf."\n";
		$conf=str_replace("\n\n", "\n", $conf);
		return $conf;	
	}
	
	
	
	public function cache_dir_method_0(){
		$f[]=$this->cache_dir_method_1();
		$f[]=$this->cache_dir_method_2();
		return @implode("\n", $f);
	}
	
	
	private function cache_dir_method_2(){
		if($this->CacheManagement2==0){return;}
		if($this->SquidUrgency==1){$this->DisableAnyCache=1;}
		if($this->DisableAnyCache==1){return;}
		$cache=new SquidCacheCenter();
		return $cache->build();
	}
	
	
	private function cache_dir_method_1(){
		if($this->CacheManagement2==1){return;}
		if($this->SquidUrgency==1){$this->DisableAnyCache=1;}
		if($this->DisableAnyCache==1){return;}
		if($this->CACHE_TYPE=="ufs"){$this->CACHE_TYPE="aufs";}
		$users=new usersMenus();
		$maincachedir="cache_dir	{$this->CACHE_TYPE} {$this->CACHE_PATH} {$this->CACHE_SIZE} 16 256 $this->store_dir_minsize\n";
		
		if(preg_match("#^\/dev\/#", $maincachedir)){
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Main cache \"$maincachedir\" Invalid cache path...!!\n";}
			$maincachedir=null;
		}
		
		$RockStore=$this->RockStore($maincachedir);
		$this->CACHE_PATH=str_replace(" ", "_", $this->CACHE_PATH);
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid Main cache \"{$this->CACHE_PATH}\" ({$this->CACHE_TYPE})\n";}
		$conf[]="\n";
		$boost=new squidbooster();
		$cache_booster=$boost->cache_booster();
				
		if($cache_booster<>null){
			$conf[]="$cache_booster\n";
			if($this->SquidBoosterOnly==1){$maincachedir=null;}
		}
		
		
		
		if($this->IS_31){$this->store_dir_minsize=null;}
		if($maincachedir==null){
			$conf[]="# --------- Main cache is null...??\n";
		}
		
		if($maincachedir<>null){
				if($RockStore<>null){
					$conf[]="# --------- Rockstore ".strlen($RockStore)."\n";
					$conf[]=$RockStore."\n";
					$this->cache_list=array();
				}
				else{
					$conf[]="# --------- Main cache\n";
					$conf[]=$maincachedir;
				}
					
				$conf[]="# --------- OTHER CACHES\n";
				$conf[]="# --------- Disable all caches: $this->DisableAnyCache\n";
		
				if(!$users->CORP_LICENSE){
					$conf[]="# --------- MISSING LICENSE (limited to one default cache)\n";
					if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Missing license: limited to one default cache.\n";}
					$this->cache_list=array();
				}
		
				if(is_array($this->cache_list)){
					reset($this->cache_list);
					while (list ($num, $val) = each ($this->cache_list)){
						if(trim($num)==null){continue;}
						if($this->ASROOT){echo "Starting......: ".date("H:i:s")." other cache \"{$num}\" ({$this->cache_list[$num]["cache_type"]})\n";}
		
						if(preg_match("#^\/dev\/#", $num)){
							if($this->ASROOT){echo "Starting......: ".date("H:i:s")." other cache \"$num\" Invalid cache path...!!\n";}
							continue;
						}
		
						if($this->cache_list[$num]["cache_type"]=="rock"){
							$mxsize=$this->cache_list[$num]["cache_maxsize"]*1000;
							$mxsize=$mxsize*1024;
							continue;
						}
						$num=str_replace(" ", "_", $num);
						if($this->cache_list[$num]["cache_type"]=="ufs"){$this->cache_list[$num]["cache_type"]="aufs";}
						$conf[]="cache_dir {$this->cache_list[$num]["cache_type"]} $num {$this->cache_list[$num]["cache_size"]} {$this->cache_list[$num]["cache_dir_level1"]} {$this->cache_list[$num]["cache_dir_level2"]} $this->store_dir_minsize \n";
					}
				}else {
					$conf[]="# --------- Cache liste !array();\n";
				}
			}
		
		
		
		
		
		if($users->CORP_LICENSE){
			$conf[]="# --------- CORP_LICENSE: Active F:".__FUNCTION__." line:".__LINE__."\n";
		}else{
			$conf[]="# --------- CORP_LICENSE: Inactive\n";
		}
		
		
		if(!$users->CORP_LICENSE){
			$conf[]="# --------- MISSING LICENSE (limited to no processor)\n";
		}else{
			$workers=$this->CPU_NUMBER();
			if($RockStore<>null){
				$conf[]="$RockStore\n";
			}else{
				if($workers>1){
					$conf[]="workers $workers\n";
				}
			}
			
		}
				
		return @implode("\n",$conf);
		
		
	}
	
	public function CPU_NUMBER(){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$cat=$unix->find_program("cat");
		$grep=$unix->find_program("grep");
		$cut=$unix->find_program("cut");
		$wc=$unix->find_program("wc");
		$cmd="$cat /proc/cpuinfo |$grep \"model name\" |$cut -d: -f2|$wc -l 2>&1";
		$CPUNUM=exec($cmd);
		return $CPUNUM;
	}	
	
	public function isNGnx(){
		if(isset($GLOBALS["isNGnx"])){return $GLOBALS["isNGnx"];}
		$users=new usersMenus();
	
		if(!$users->NGINX_INSTALLED){$GLOBALS["isNGnx"]=false;return $GLOBALS["isNGnx"];}
		$sock=new sockets();
		$EnableNginx=$sock->GET_INFO("EnableNginx");
		if(!is_numeric($EnableNginx)){$EnableNginx=1;}
		if($EnableNginx==1){
			$GLOBALS["isNGnx"]=true;
			return $GLOBALS["isNGnx"];
		}
			
		$GLOBALS["isNGnx"]=false;
		return $GLOBALS["isNGnx"];
		
	}
	
	private function access_log_childs(){
		$sock=new sockets();
		$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
		if($SquidAsMasterPeer==0){return null;}
		$SquidAsMasterLogExtern=intval($sock->GET_INFO("SquidAsMasterLogExtern"));
		if($SquidAsMasterLogExtern==0){return null;}
		$SquidAsMasterLogChilds=intval($sock->GET_INFO("SquidAsMasterLogChilds"));
		if($SquidAsMasterLogChilds==1){return null;}
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		
		if($this->IS_34){
			$options34=" on-error=drop buffer-size=64KB ";
		}
		
		if($SquidAsMasterFollowxForward==1){
			$acls="ChildProxyPorts";
		}else{
			$acls="ChildProxyPorts ChildProxysIPs";
		}
		
		$conf[]="logformat childs %ts.%03tu %6tr %>a %Ss/%03>Hs %<st %rm %ru %[un %Sh/%<a %mt %{X-Forwarded-For}>h";
		if($this->IS_31){
			$access_log="access_log /var/log/squid/childs-access.log childs $acls";
		}
		
		if($this->IS_32){
			$access_log="access_log stdio:/var/log/squid/childs-access.log childs $acls";
		}
		
		if($this->IS_34){
			$access_log="access_log stdio:/var/log/squid/childs-access.log on-error=drop logformat=childs $options34 $acls";
		}		
		
		$conf[]=$access_log;
		return @implode("\n", $conf);
	}
	
	
	private function access_log_csv(){
		if($this->EnableSquidCSV==0){return;}
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid create logs in csv enabed\n";}
		$sock=new sockets();
		$SquidCsvParams=unserialize(base64_decode($sock->GET_INFO("SquidCsvParams")));
		$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
		$SquidAsMasterLogChilds=intval($sock->GET_INFO("SquidAsMasterLogChilds"));
		$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
		
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
		
		
		if($this->EnableMacAddressFilter==0){unset($CSV["a3"]);}
		
		
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
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." Squid enabled CSV fields:".@implode(", ",$ll)."\n";}
		$conf[]="logformat csv ".@implode(",", $access);
		$acls=new squid_acls_groups();
		$denylogsTXT=null;
		$denylogs=$acls->buildacls_bytype("deny_log");
		if(count($denylogs)>0){$denylogsTXT=trim(@implode(" ", $denylogs));}
		if(strlen($denylogsTXT)>2){$denylogsTXT=" $denylogsTXT";}
		
		if($SquidAsMasterPeer==1){
			if($SquidAsMasterLogChilds==0){
				if($SquidAsMasterFollowxForward==0){
					$ChildProxysIPs="!ChildProxysIPs ";
				}else{
					$ChildProxysIPs="!ChildProxyPorts ";
				}
			}
		}
		
		
		if(($this->IS_31) && (!$this->IS_32)){
			$conf[]="access_log /var/log/squid/access.csv csv $ChildProxysIPs$denylogsTXT\n";
		}
		
		if($this->IS_32){
			$conf[]="access_log stdio:/var/log/squid/access.csv csv $ChildProxysIPs!MgRPort$denylogsTXT\n";
		}
		return @implode("\n", $conf);
	}
	
	
	private function syslog_remote(){
		$sock=new sockets();
		if(!$this->ASROOT){return;}
		$array=unserialize(base64_decode($sock->GET_INFO("SquidSyslogAdd")));
		if(!isset($array["ENABLE"])){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled\n";
			return;}
		if(!is_numeric($array["ENABLE"])){$array["ENABLE"]==0;}
		if($array["ENABLE"]==0){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");}
			return;
		}
		if($array["SERVER"]==null){
			echo "Starting......: ".date("H:i:s")." Squid remote syslog is disabled (no server set)\n";
			if(is_file("/etc/rsyslog.d/artica-client-local6.conf")){@unlink("/etc/rsyslog.d/artica-client-local6.conf");}
			return;
		}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		echo "Starting......: ".date("H:i:s")." Squid remote syslog exec.syslog-engine.php --squidsys\n";
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.syslog-engine.php --squidsys >/dev/null 2>&1 &");
		return "access_log syslog:local6.info common\n";
	}
	
	
	private function isp_networks(){
		if($this->SquidUrgency==1){return null;}
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
			$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"]="http_access allow {$this->MyStandPort}ISP_CLIENTS";
			return "acl ISP_CLIENTS src \"/etc/squid3/isp_acls_src\"";
		
		}
		
	}
	
	
	

	
	private function check_malwares_acl(){
		if($this->SquidUrgency==1){return null;}
		$f=file("/etc/squid3/malwares.acl");
		echo "Starting......: ".date("H:i:s")." ACL malwares ". count($f)." rules..\n";
		$rebuild=false;
		while (list ($num, $val) = each ($f)){
			$val=str_replace("\r", "", $val);
			$val=str_replace("\n", "", $val);
			$val=trim($val);
			if($val==null){continue;$rebuild=true;}
			if(preg_match("#try\{#", $val)){
				echo "Starting......: ".date("H:i:s")." ACL malwares skipping line $num\n";
				$rebuild=true;
				continue;
			}
			$t[]=$val;
		}
		if($rebuild){
			echo "Starting......: ".date("H:i:s")." ACL malwares saving ". count($t)." rules..\n";
			@file_put_contents("/etc/squid3/malwares.acl", @implode("\n", $t));
		}
		
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
		$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"]="http_access deny {$this->MyStandPort}!ArticaAclAuthenticated";
		return @implode("\n",$f);
		
	}
	
	
		private function debug_options(){
			if($this->SquidUrgency==1){return;}
			$sock=new sockets();
			$acldebg=null;
			$SquidDebugCacheProcdbg=null;
			//see http://wiki.squid-cache.org/KnowledgeBase/DebugSections
			$SquidDebugAcls=$sock->GET_INFO("SquidDebugAcls");
			$SquidDebugCacheProc=$sock->GET_INFO("SquidDebugCacheProc");
			
			if(!is_numeric($SquidDebugAcls)){$SquidDebugAcls=0;}
			if(!is_numeric($SquidDebugCacheProc)){$SquidDebugCacheProc=0;}
			
			
			if($SquidDebugAcls==1){
				$acldebg=" 33,2 28,9";
			}
			if($SquidDebugCacheProc==1){
				$SquidDebugCacheProcdbg=" 20,9";
			}

			
			return "ALL,1$acldebg$SquidDebugCacheProcdbg";
			
		}
	
		
		public function dns_nameservers_from_resolv($asArray=false){
			if($this->SquidUrgency==1){return null;}
			if(!$this->ASROOT){return;}
			$s=array();
			$f=file("/etc/resolv.conf");
			while (list ($num, $val) = each ($f)){
				if(preg_match("#nameserver\s+(.+)#", $val,$re)){
					$s[]=trim($re[1]);
				}
			}
			
			if(count($s)==0){return array();}
			if($asArray){return $s;}
			if(count($s)>0){return @implode(" ", $s);}
			
		}
		
		private function CacheManager(){
			$sock=new sockets();
			$cache_mgr_user=$sock->GET_INFO("cache_mgr_user");
			$cachemgr_passwd=$sock->GET_INFO("cachemgr_passwd");
			if($cache_mgr_user<>null){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [MGR]: Cache Manager set has $cache_mgr_user\n";} 
				$conf[]="";
				$conf[]="#Cache Manager Authentication";
				$conf[]="cache_mgr $cache_mgr_user";
				$conf[]="cachemgr_passwd $cachemgr_passwd all";
				$conf[]="";
			}else{
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [MGR]: Cache Manager is not set\n";} 
			}
			
		}
		
		private function ACL_MANAGER(){
			if( ($this->IS_31) OR ($this->IS_30) OR ($this->IS_27)){
				if(!$this->IS_32){
					if(!$this->IS_33){
						return "acl manager proto cache_object";
					}
				}
			}
		}
		
		private function ACL_ChildProxysIPs(){
			// ChildProxysIPs
			$backends=$this->backends_list();
			$f[]="# ACL_ChildProxysIPs: Backends = ".count($backends);
			if(count($backends)==0){
				$sock=new sockets();
				$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
				$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
				$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
				$token=$this->myport_token();
				$f[]="# ACL_ChildProxysIPs: Backends = > Only set myport $SquidAsMasterPeerPort/$SquidAsMasterPeerPortSSL";
				if($SquidAsMasterPeerPort>0){
					$f[]="acl ChildProxysIPs $token $SquidAsMasterPeerPort $SquidAsMasterPeerPortSSL";
				}else{
					$f[]="# ACL_ChildProxysIPs: Backends = > Myport not set => 127.0.0.1";
					$f[]="acl ChildProxysIPs src 127.0.0.1";
				}
				return @implode("\n", $f);
			}
			$f[]="acl ChildProxysIPs src ".@implode(" ", $backends);
			return @implode("\n", $f);
			
		}
		
		private function ACL_MyLocalIpsDest(){
			$NETWORK_ALL_INTERFACES=$this->NETWORK_ALL_INTERFACES();
			unset($NETWORK_ALL_INTERFACES["0.0.0.0"]);
			unset($NETWORK_ALL_INTERFACES["127.0.0.1"]);
			$tcp=new IP();
			while (list ($ipaddr, $line) = each ($NETWORK_ALL_INTERFACES) ){
				if(!$tcp->isIPAddress($ipaddr)){continue;}
				$f[]=$ipaddr;
			}
			
			if(count($f)==0){
				return "acl MyLocalIpsDest dst 127.0.0.1\n"; 
			}
			
			
			return "acl MyLocalIpsDest dst ".@implode(" ", $f)."\n";
			
		}
		
		
		private function http_access_debugport(){
			$sock=new sockets();
			$q=new mysql_squid_builder();
			$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
			if($SquidDebugPort==0){return;}
			$f[]="#";
			$f[]="#--------- Disable Web filtering for the Test Port [".__LINE__."]";
			$f[]="url_rewrite_access deny MyTestPort";
			if(!$q->TABLE_EXISTS("debugport_addr")){
				$f[]="#--------- debugport_addr no such table";
				$f[]="http_access allow MyTestPort all";
				return @implode("\n", $f);
			}
			
			if($q->COUNT_ROWS("debugport_addr")==0){
				$f[]="#--------- debugport_addr no item";
				$f[]="http_access allow MyTestPort all";
				return @implode("\n", $f);
			}
			
			$sql="SELECT *  FROM debugport_addr";
			$results = $q->QUERY_SQL($sql);
			$f[]="#--------- debugport_addr ".mysql_numrows($results)." item(s)";
			while ($ligne = mysql_fetch_assoc($results)) { $t[]=$ligne["ipaddr"]; }
			$f[]="acl MyTestPortAdrr src ".@implode(" ", $t);
			$f[]="http_access deny MyTestPort !MyTestPortAdrr";
			$f[]="http_access allow MyTestPort";
			$f[]="#------------------------------------[".__LINE__."]";
			$f[]="#";
			return @implode("\n", $f);
		}
		
		
		
		private function http_access(){
			$sock=new sockets();
			$SquidAsMasterPeer=intval($sock->GET_INFO("SquidAsMasterPeer"));
			$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
			$SquidAsMasterPeerPortSSL=intval($sock->GET_INFO("SquidAsMasterPeerPortSSL"));
			$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
			$SquidAsMasterFollowxForward=intval($sock->GET_INFO("SquidAsMasterFollowxForward"));
			
			
			
			if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
			$conf[]="# # # # # # # # # # MAIN RULES # # # # # # # # # # [".__LINE__."]";
			$conf[]="#--------- Dedicated rule that allow Access to Artica repositories";
			$conf[]="#--------- in any cases for updates [".__LINE__."]";
			$conf[]="http_access allow ToArticaWWW";
			$conf[]="http_access allow MyLocalIpsDest";
			$conf[]="url_rewrite_access deny MyLocalIpsDest";
			$conf[]="http_access deny DenyBlacksites";
			if($this->SquidUrgency==0){
				$conf[]="#--------- http_access: Dedicated rule that deny UserAgents [".__LINE__."]";
				$conf[]="http_access deny BrowsersDeny all";
				$conf[]="url_rewrite_access deny BrowsersNoWebF";
			}
			
			
			

			
			
			if($SquidAsMasterPeer==1){
				$conf[]="#--------- Dedicated rule that allow childs proxys to pass trough the Parent";
				if($SquidAsMasterFollowxForward==0){
					$conf[]="http_access allow ChildProxyPorts ChildProxysIPs";
					$conf[]="http_access deny ChildProxyPorts all";
				}
			}
			
			$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
			$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
			$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
			$conf[]="#--------- AllowSquidDropBox:$AllowSquidDropBox, AllowSquidSkype:$AllowSquidSkype, AllowSquidOffice365:$AllowSquidOffice365 F:".__FUNCTION__." line:".__LINE__;
			if($AllowSquidDropBox==1){
				$conf[]="http_access allow dropbox_ips";
				$conf[]="http_access allow dropbox_www";
				$conf[]="url_rewrite_access deny dropbox_ips";
				$conf[]="url_rewrite_access deny dropbox_www";
			}
			if($AllowSquidSkype==1){
				$conf[]="http_access allow skype_ips";
				$conf[]="http_access allow skype_www";
				$conf[]="url_rewrite_access deny skype_ips";
				$conf[]="url_rewrite_access deny skype_ips";
			}
			if($AllowSquidOffice365==1){
				$conf[]="http_access allow office365_ips";
				$conf[]="http_access allow office365_www";
				$conf[]="url_rewrite_access deny office365_ips";
				$conf[]="url_rewrite_access deny office365_www";
			}
			if($AllowSquidGoogle==1){
				$conf[]="http_access allow google_ips";
				$conf[]="http_access allow google_www";
				$conf[]="url_rewrite_access deny google_ips";
				$conf[]="url_rewrite_access deny google_www";
			}			
			
			
			if($this->SquidUrgency==1){
				$conf[]="#--------- URGENCY ENABLED !!! F:".__FUNCTION__." line:".__LINE__;
				$conf[]="http_access allow all\n";
				$GLOBALS[__FUNCTION__]=@implode("\n", $conf);
				return $GLOBALS[__FUNCTION__];
			}
			if($this->enable_dansguardian==1){$this->allow_squid_localhost=1;}
			$this->allow_squid_localhost=1;
			$ACL_MANAGER=$this->ACL_MANAGER();
			$sock=new sockets();
			$EnableSquidPortsRestrictions=$sock->GET_INFO("EnableSquidPortsRestrictions");
			if(!is_numeric($EnableSquidPortsRestrictions)){$EnableSquidPortsRestrictions=0;}
			if($ACL_MANAGER<>null){$conf[]=$ACL_MANAGER;}
			
			if(isset($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"])){
				if($GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"]<>null){
					$conf[]="#--------- Allow remote error pages in all ways...F:".__FUNCTION__." line:".__LINE__;
					$conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_TEMPLATES"];
				}
			}
			
			if(isset($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"])){
				if($GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"]<>null){
					$conf[]="#--------- Allow Splash authentications websites for HotSpot...F:".__FUNCTION__." line:".__LINE__;
					$conf[]=$GLOBALS["HTTP_ACCESS_ALLOW_SPLASHS"];
				}
			}
			
			
			
			if(isset($GLOBALS["HTTP_ACCESS"]["ExtrnQuota"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ExtrnQuota"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["MacToUid"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MacToUid"];}
			
			if($this->ACL_ARP_ENABLED){
				$conf[]="http_access allow {$this->MyStandPort}whitelisted_mac_computers";
				$conf[]="url_rewrite_access deny {$this->MyStandPort}whitelisted_mac_computers";
				
			}

			
			$conf[]="http_access allow squidclient manager";
			$conf[]="http_access allow squidclient MgrInfoUri";

			$acls=new squid_acls_groups();
			
			$tcp_outgoing_tos=$acls->buildacls_bytype("tcp_outgoing_tos");
			if(count($tcp_outgoing_tos)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: Engine tcp_outgoing_tos ".count($tcp_outgoing_tos)." rules..\n";
				while (list ($index, $line) = each ($tcp_outgoing_tos) ){$conf[]="tcp_outgoing_tos $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine tcp_outgoing_tos No rules..\n";
			}	
			
			
			$reply_body_max_size=$acls->buildacls_bytype("reply_body_max_size");
			if(count($reply_body_max_size)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: reply_body_max_size ".count($reply_body_max_size)." rules..\n";
				while (list ($index, $line) = each ($reply_body_max_size) ){$conf[]="reply_body_max_size $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine reply_body_max_size No rules..\n";
			}			
			
			$tcp_outgoing_address=$acls->buildacls_bytype("tcp_outgoing_address");
			$conf[]="# tcp_outgoing_address ".count($tcp_outgoing_address)." rule(s) [".__LINE__."]";
			if(isset($GLOBALS["tcp_outgoing_address_errors"])){
				if(count($GLOBALS["tcp_outgoing_address_errors"])>0){while (list ($index, $line) = each ($GLOBALS["tcp_outgoing_address_errors"]) ){$conf[]="# $line";}}
			}
			
			if(count($tcp_outgoing_address)>0){
				echo "Starting......: ".date("H:i:s")." [ACLS]: Engine tcp_outgoing_address ".count($tcp_outgoing_address)." rules..\n";
				while (list ($index, $line) = each ($tcp_outgoing_address) ){$conf[]="tcp_outgoing_address $line";}
			}else{
				echo "Starting......: ".date("H:i:s")." [ACLS]: ACL Engine tcp_outgoing_address No rules..\n";
			}			
			
			
			if(isset($GLOBALS["HTTP_ACCESS"]["QUOTAS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["QUOTAS"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["SPLASH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["SPLASH"];}
					
			
			
			
			if(!isset($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])){$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]=array();}
			//if($this->allow_squid_localhost<>1){$conf[]="http_access deny to_localhost";}else{$conf[]="http_access allow to_localhost";}
			$conf[]="http_access allow to_localhost";
			$conf[]="url_rewrite_access deny MgRPort localhost";
			if(count($GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"])>0){
				$conf[]=implode("\n",$GLOBALS["HTTP_ACCESS"]["WHITE_COMPUTERS_REDIRECTOR"]);
			}
			if(isset($GLOBALS["url_rewrite_access_users"])){
				if(count($GLOBALS["url_rewrite_access_users"])>0){
					$conf[]=implode("\n",$GLOBALS["url_rewrite_access_users"]);
				}
			}
			
			
			$conf[]="url_rewrite_access deny MgRPort squidclient";
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
				$conf[]="http_access allow {$this->MyStandPort}FTP clients_ftp";
				$conf[]="http_access allow {$this->MyStandPort}CONNECT clients_ftp";
			}	

			$conf[]="http_access deny purge";			
			if($this->EnableUserAgentBanAll==1){$conf[]="http_access deny !AllowedBrowsers";}		
			if(isset($GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["BLOCKEDSITES"];}	
			if(isset($GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["MALWARE_PATROL"];}			
			if(isset($GLOBALS["HTTP_ACCESS"]["ItChart"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ItChart"];}
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: Building http_access rules... (Line ".__LINE__.")\n";}
			
			$conf[]="#--------- ACLS added by administrators F:".__FUNCTION__." line:".__LINE__;
			$conf[]=$acls->buildacls_order(0);
			
			if($this->SquidBubbleMode==1){
				$conf[]="#--------- BUBBLE MODE:";
				$q=new mysql_squid_builder();
				$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
				$results = $q->QUERY_SQL($sql);
				while ($ligne = mysql_fetch_assoc($results)) {
					$conf[]="#--------- BUBBLE: ACLS {$ligne["portname"]}";
					$conf[]=$acls->buildacls_order($ligne["aclport"]);
				}
			}
					
			$conf[]="#--------- Others mandatories ACLS";
			if($EnableSquidPortsRestrictions==1){
				$conf[]="http_access deny {$this->MyStandPort}!Safe_ports";
				$conf[]="http_access deny {$this->MyStandPort}CONNECT !SSL_ports";
			}
				
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["WHITELISTED_BROWSERS"];}
			if(isset($GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"])){
				$conf[]="#--------- HotSpot allowed accesses";
				$conf[]=$GLOBALS["HTTP_ACCESS_SPLASH_SCREEN"];
			}
				
			$conf[]="#--------- Is Transparent ? ". count($this->TRANSPARENT_PORTS_LIST)." items";
			if(count($this->TRANSPARENT_PORTS_LIST)>0){
				if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){
					$conf[]="http_access allow office_network MyTransparentPort";
				}else{
					$conf[]="http_access allow MyTransparentPort";
				}
					
			}
				
			if($this->smartphones_port>0){
				$conf[]="#--------- Dedicated rule that allow Mobile phones (SSL is allowed for all)";
				$conf[]="http_access allow MyMobilesPort SSL_ports all";
				if($this->IS_33){
					$conf[]="http_access deny MyMobilesPort !MobilePhones";
				}
			}
			$conf[]="#--------- http_access: Dedicated rule that allow UserAgents [".__LINE__."]";
			$conf[]="http_access allow BrowsersByPass";

			
				
			$conf[]="#--------- Is must be authenticated ?";
			if(isset($GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["EXTERNAL_ARTICA_HELPER"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_AUTH"];}
			if(isset($GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["LDAP_GROUP"];}
							
			if($this->ASROOT){
				$acls_rules=$GLOBALS["aclGen"]->build_http_access(0);
				echo "Starting......: ".date("H:i:s")." [ACLS]: ". count($acls_rules)." rule(s) from engine (Line ".__LINE__.")\n";}
				if(count($acls_rules)>0){
					$conf[]="# Builded acls from engine...";
					$conf[]=@implode("\n", $acls_rules);
				}
					
				if($this->SquidBubbleMode==1){
					$q=new mysql_squid_builder();
					$sql="SELECT * FROM webfilters_sqaclsports WHERE enabled=1 ORDER BY aclport";
					$results = $q->QUERY_SQL($sql);
					while ($ligne = mysql_fetch_assoc($results)) {
						$conf[]="#--------- BUBBLE: ACLS build_http_access() {$ligne["portname"]} (line ".__LINE__.")";
						$acls_rules=$GLOBALS["aclGen"]->build_http_access($ligne["aclport"]);
						$DENYBUBBLE[]="http_access deny MyPort{$ligne["aclport"]} all";
						if(count($acls_rules)>0){
							$conf[]="# Builded acls from engine {$ligne["portname"]}...";
							$conf[]=@implode("\n", $acls_rules);
						}
					}
				}
					
				if(isset($GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["ISP_NETWORK"];}
				if(isset($GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"])){$conf[]=$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"];}
				
				if(isset($GLOBALS["HTTP_ACCESS_REVERSE"])){$conf[]=$GLOBALS["HTTP_ACCESS_REVERSE"];}
				
				$conf[]="http_access deny {$this->MyStandPort}all";
				while (list ($num, $val) = each ($conf) ){if(trim($val)==null){continue;}$f[]=$val;}
				if($this->SquidBubbleMode==1){
					while (list ($num, $val) = each ($DENYBUBBLE) ){
						$f[]=$val;
					}
				}
			
			$final="\n".@implode("\n",$f)."\n";
			$GLOBALS[__FUNCTION__]=$final;
			return $final;
		}
		
		private function SaveCertificateDefault($nginx=false,$coova=false,$squid27=false){
			$prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: Default";
			if($nginx){$prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: Default";}

			
			@mkdir("/etc/squid3/ssl",0755,true);
			if(!is_file("/etc/squid3/ssl/cacert.pem")){
				if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
				echo "$prefixOut invoke to create a faked Self-signed certificate\n";
				shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.php --certificate");
			}
			
			$CertToken="cert=/etc/squid3/ssl/cacert.pem";
			$KeyToken="key=/etc/squid3/ssl/privkey.pem";
			
			$certificates=" $CertToken $KeyToken";
			if($coova){
				return  "HS_SSLCERTFILE=/etc/squid3/ssl/cacert.pem\nHS_SSLKEYFILE=/etc/squid3/ssl/privkey.pem";
			}
			
			if($nginx){return  "\tssl_certificate /etc/squid3/ssl/cacert.pem;\n\tssl_certificate_key /etc/squid3/ssl/privkey.pem;";}
			
			if($squid27){
				$tt[]="sslproxy_client_certificate /etc/squid3/ssl/cacert.pem";
				$tt[]="sslproxy_client_key  /etc/squid3/ssl/privkey.pem";
				$tt[]="sslproxy_version 1";
				$tt[]="sslproxy_flags DONT_VERIFY_PEER";
				echo "$prefixOut return 2.7v\n";
				return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/ssl/cacert.pem","key"=>"/etc/squid3/ssl/privkey.pem"));
			}
			
			
			return $certificates;
			
		}
		
		private function https_port_reverse(){
			if(!$this->ASROOT){return;}
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return null;
			}
			
			if($this->ssl_port==0){$this->ssl_port=443;}
			$certificate=trim($this->certificate_center);
			
			
			if($this->isNGnx()){
				echo "Starting......: ".date("H:i:s")." [REVERSE]: Nginx is installed and enabled...\n";
				if($this->listen_port==80){$this->listen_port=8080;}
				if($this->ssl_port==443){$this->ssl_port=4443;}
			}
				
						
			
			
			$defaultWWW=null;
			$cert=null;
			echo "Starting......: ".date("H:i:s")." [REVERSE]: Port:$this->ssl_port certificate:`$certificate`\n";
			if($certificate<>null){$tokens=$this->SaveCertificate($certificate);}
			if($certificate==null){
				echo "Starting......: ".date("H:i:s")." [REVERSE]: No certificate set for the proxy itself, set the default one\n";
				$tokens=$this->SaveCertificateDefault();
			}
				
			if($this->SquidReverseDefaultWebSite<>null){$defaultWWW=" defaultsite=$this->SquidReverseDefaultWebSite ";}
			return "https_port $this->ssl_port accel $tokens vhost vport$defaultWWW";
		}
		
		
private function build_dynamic_certificate($pathToCert){
	$f=@file_get_contents($pathToCert);
	if(!preg_match("#-----BEGIN CERTIFICATE-----(.*?)-----END CERTIFICATE-----#s", $f,$re)){
		echo "Starting......: ".date("H:i:s")." [SSL] :: Unable to stat Certificate in $pathToCert\n";
		return;}
	$certificate="-----BEGIN CERTIFICATE-----{$re[1]}-----END CERTIFICATE-----";
	if(!preg_match("#-----BEGIN RSA PRIVATE KEY-----(.*?)-----END RSA PRIVATE KEY-----#s", $f,$re)){
		echo "Starting......: ".date("H:i:s")." [SSL] :: Unable to stat PRIVATE KEY in $pathToCert\n";
		return;}
	$privatekey="-----BEGIN RSA PRIVATE KEY-----{$re[1]}-----END RSA PRIVATE KEY-----";
	@file_put_contents("$pathToCert.dyn","$privatekey\n$certificate\n");
	return "$pathToCert.dyn";
	
	
}

private function BuildSquidCertificateCRTD_default($commname=null){
	if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
	$sock=new sockets();
	if($commname==null){$commname=$unix->hostname_g();}
	
	$openssl=$unix->find_program("openssl");
	$certificate_filename=str_replace("*", "_ALL_", $commname);
	
	$keyout="/etc/squid3/ssl/$certificate_filename.dyn";
	if(is_file($keyout)){
		echo "Starting......: ".date("H:i:s")." [SSL]: $keyout OK\n";
		if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
			$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
			echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
			shell_exec($cmd);
			if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){ echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n"; }
		}
		echo "Starting......: ".date("H:i:s")." [SSL]: Already generated OK\n";
		return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout";
	}
	
	$DefaultSSLParams=unserialize(base64_decode($sock->GET_INFO("DefaultSSLParams")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	if($DefaultSSLParams["countryName"]==null){$DefaultSSLParams["countryName"]="US";}
	if($DefaultSSLParams["stateOrProvinceName"]==null){$DefaultSSLParams["stateOrProvinceName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["localityName"]==null){$DefaultSSLParams["localityName"]=$WizardSavedSettings["city"];}
	if($DefaultSSLParams["organizationName"]==null){$DefaultSSLParams["organizationName"]=$LicenseInfos["COMPANY"];}
	if($DefaultSSLParams["organizationalUnitName"]==null){$DefaultSSLParams["organizationalUnitName"]=$WizardSavedSettings["organization"];}
	if(!is_numeric($DefaultSSLParams["CertificateMaxDays"])){$DefaultSSLParams["CertificateMaxDays"]=730;}
	
	$openssl=$unix->find_program("openssl");
	$countryName=$DefaultSSLParams["countryName"];
	$stateOrProvinceName=$DefaultSSLParams["stateOrProvinceName"];
	$localityName=$DefaultSSLParams["localityName"];
	$OrganizationName=$DefaultSSLParams["organizationName"];
	$OrganizationalUnit=$DefaultSSLParams["organizationalUnitName"];
	$CertificateMaxDays=$DefaultSSLParams["CertificateMaxDays"];

	$DefaultSubject="-subj \"/C=$countryName/ST=$stateOrProvinceName/L=$localityName/O=$OrganizationName/OU=$OrganizationalUnit/CN=*\"";
	echo "Starting......: ".date("H:i:s")." [SSL]: Building certificate $DefaultSubject\n";
	
	$cmd="$openssl req -new -newkey rsa:1024 $DefaultSubject -days 365 -nodes -x509 -keyout $keyout -out $keyout";
	shell_exec($cmd);
	
	echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
	
	if(!is_file("$keyout")){
		echo "Starting......: ".date("H:i:s")." [SSL]: Warning !!! $keyout no such file\n";
		return null;}
	
	echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
	$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
	echo "Starting......: ".date("H:i:s")." [SSL]: $cmd\n";
	shell_exec($cmd);
	if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
		echo "Starting......: ".date("H:i:s")." [SSL]: Warning /usr/share/artica-postfix/ressources/squid/certificate.der no such file\n";
	}
	
	return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout";
}


private function  BuildSquidCertificateCRTD($commname){
	if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
	$openssl=$unix->find_program("openssl");
	$certificate_filename=str_replace("*", "_ALL_", $commname);
	$keyout="/etc/squid3/ssl/$certificate_filename.dyn";
	if(is_file($keyout)){
		echo "Starting......: ".date("H:i:s")." [SSL]: $keyout OK\n";
		if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
			echo "Starting......: ".date("H:i:s")." [SSL]: create certificate.der\n";
			$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
			shell_exec($cmd);
		}
		return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout";}
	$q=new mysql_squid_builder();
	
	$sql="SELECT `DynamicCert` FROM sslcertificates WHERE CommonName='$commname'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["DynamicCert"]==null){
		echo "Starting......: ".date("H:i:s")." [SSL] :: $commname DynamicCert IS NULL in `sslcertificates` table\n";
		echo "Starting......: ".date("H:i:s")." [SSL] :: $commname Build a default one\n";
		return $this->BuildSquidCertificateCRTD_default();
	}
	@file_put_contents("$keyout", $ligne["DynamicCert"]);
	
	if(!is_file("/usr/share/artica-postfix/ressources/squid/certificate.der")){
		$cmd="$openssl x509 -in $keyout -outform DER -out /usr/share/artica-postfix/ressources/squid/certificate.der";
		shell_exec($cmd);
	}
	
	
	return " generate-host-certificates=on dynamic_cert_mem_cache_size=4MB cert=$keyout";

	
}


public function BuildSquidCertificate($commname=null){
	if(!$this->ASROOT){return;}
	@mkdir("/etc/squid3",0755,true);
	@mkdir("/etc/squid3/ssl",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/squid",0755,true);
	if($commname==null){
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		$commname=$unix->hostname_g();
	}
	
	if(strlen($this->sslcrtd_program)==0){$this->EnableSquidSSLCRTD=0;}
	
	echo "Starting......: ".date("H:i:s")." [SSL]: CommonName: `$commname`, EnableSquidSSLCRTD=$this->EnableSquidSSLCRTD\n";
	if($this->EnableSquidSSLCRTD==1){
		return $this->BuildSquidCertificateCRTD($commname);
	}
	return $this->SaveCertificate($commname);
	
}

		
public function SaveCertificate($commname=null,$cache_peer=false,$nginx=false,$coova=false,$squid27=false){
		if(!$this->ASROOT){return;}
		if($commname==null){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$commname=$unix->hostname_g();
		}
		$EnableSquidSSLCRTD=$this->EnableSquidSSLCRTD;
		
		$prefixOut="Starting......: ".date("H:i:s")." [  SSL  ]: $commname";
		if($nginx){
			$prefixOut="Starting......: ".date("H:i:s")." [INIT]: nginx SSL: $commname";
			$EnableSquidSSLCRTD=0;
		}
		
		
		@mkdir("/etc/squid3/ssl",0755,true);
		$cert_token="cert";
		$key_token="key";
		if($cache_peer){$cert_token="sslcert";$key_token="sslkey";}
		$q=new mysql();
		if(!$q->FIELD_EXISTS("sslcertificates","DynamicCert","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `DynamicCert` TEXT NOT NULL";$q->QUERY_SQL($sql,'artica_backup');}
		if(!$q->FIELD_EXISTS("sslcertificates","UsePrivKeyCrt","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `UsePrivKeyCrt` smallint(1) DEFAULT 0";$q->QUERY_SQL($sql,'artica_backup');}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
		
		
		$certificate_filename=str_replace("*", "_ALL_", $commname);
		$sql="SELECT `UsePrivKeyCrt`,`crt`,`DynamicCert`,`privkey`,`SquidCert`,`Squidkey`,`bundle` FROM sslcertificates WHERE CommonName='$commname'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		if($ligne["UsePrivKeyCrt"]==1){
			@file_put_contents("/etc/squid3/$certificate_filename.pem", $ligne["crt"]);
			@file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["privkey"]);
		}
		
		
		
		if(is_file("/etc/squid3/$certificate_filename.pem")){
				if($unix->file_time_min("/etc/squid3/$certificate_filename.pem")<5){
					
					if($coova){
						return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.pem\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
					}
					
					if($nginx){
						echo "Starting......: ".date("H:i:s")." [INIT]: nginx, using certificate /etc/squid3/$certificate_filename.pem\n";
						echo "Starting......: ".date("H:i:s")." [INIT]: nginx, using Key /etc/squid3/$certificate_filename.key\n";
						return  "\tssl_certificate /etc/squid3/$certificate_filename.pem;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;"; 
					}
					$KeyToken="key=/etc/squid3/$certificate_filename.key";
					$CertToken="cert=/etc/squid3/$certificate_filename.pem";
					
					return " $CertToken $KeyToken";
				}
			}
			
			
			
		
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [SSL] :: $commname = $q->mysql_error\n";}
			
			if(strlen($ligne["SquidCert"])==0){
				if(strlen($ligne["crt"])>10){
					$ligne["SquidCert"]=$ligne["crt"];
					$ligne["Squidkey"]=$ligne["privkey"];
				}
			}
			
			
			echo "$prefixOut certificate: ". strlen($ligne["SquidCert"])." bytes...\n";
			echo "$prefixOut key........: ". strlen($ligne["Squidkey"])." bytes...\n";
			echo "$prefixOut Chain......: ". strlen($ligne["bundle"])." bytes...\n";
			
			$certificate_filename=str_replace("*", "_ALL_", $commname);
			
			if(strlen($ligne["SquidCert"])==0){
				if($cache_peer){return null;}
				echo "$prefixOut [FATAL]: Certificate not set for $commname, return default one\n";
				return $this->SaveCertificateDefault($nginx,$coova,$squid27);
			}
			
			if( strlen($ligne["Squidkey"])==0 ){
				echo "$prefixOut [FATAL]: Key does not exists swith to default one\n";
				return $this->SaveCertificateDefault($nginx,$coova,$squid27);
			}			
		
			if( strlen($ligne["bundle"])>10 ){
				echo "$prefixOut Integrate chain certificate inside certificate\n";
				$ligne["SquidCert"]=$ligne["SquidCert"].$ligne["Squidkey"];
			}
			@file_put_contents("/etc/squid3/$certificate_filename.crt", $ligne["SquidCert"]);
			@file_put_contents("/etc/squid3/$certificate_filename.key", $ligne["Squidkey"]);
			
			if($nginx){return  "\tssl_certificate /etc/squid3/$certificate_filename.crt;\n\tssl_certificate_key /etc/squid3/$certificate_filename.key;";}
			
			if($coova){
				return  "HS_SSLCERTFILE=/etc/squid3/$certificate_filename.crt\nHS_SSLKEYFILE=/etc/squid3/$certificate_filename.key";
			}
			
			if($squid27){
				$tt[]="sslproxy_client_certificate /etc/squid3/$certificate_filename.crt";
				$tt[]="sslproxy_client_key  /etc/squid3/$certificate_filename.key";
				$tt[]="sslproxy_version 1";
				$tt[]="sslproxy_flags DONT_VERIFY_PEER";
				return array(@implode("\n", $tt),array("certificate"=>"/etc/squid3/$certificate_filename.crt","key"=>"/etc/squid3/$certificate_filename.key"));
			}
			
			$KeyToken2="key=/etc/squid3/$certificate_filename.key";
			$CertToken2="$cert_token=/etc/squid3/$certificate_filename.crt";
			return " $CertToken2 $KeyToken=$KeyToken2";		
		}
		
		private function NETWORK_ALL_INTERFACES(){
			if(isset($GLOBALS["NETWORK_ALL_INTERFACES"])){return $GLOBALS["NETWORK_ALL_INTERFACES"];}
			if(!$this->ASROOT){return;}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$GLOBALS["NETWORK_ALL_INTERFACES"]=$unix->NETWORK_ALL_INTERFACES(true);
			$GLOBALS["NETWORK_ALL_INTERFACES"]["0.0.0.0"]=true;
		}
		
		
		private function https_port(){
			
			
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return "# enable-ssl not compiled... SSL port disabled\n";
			}
			// require --enable-ssl in compilation mode... // sslpassword_program
			
			if($this->SquidActHasReverse==1){
				return $this->https_port_reverse();
			}
			$sock=new sockets();
			$http_port_sslbump=null;
			
			if($this->SSL_BUMP==1){
				if($this->ssl_port==0){
					$this->ssl_port=$this->get_ssl_port();
				}
			}

			
			if($this->ssl_port==0){
				echo "Starting......: ".date("H:i:s")." [SSL]: ssl_port = 0...\n";
				return "# ssl_port = 0 SSL port disabled, SSL_BUMP=$this->SSL_BUMP [".__LINE__."]\n";
			}
			
			if($this->hasProxyTransparent==1){
				$this->SSL_BUMP=1;
				echo "Starting......: ".date("H:i:s")." [SSL]: Transparent Mode [".__LINE__."]...\n";
				return "# ssl_port = As transparent...\n".$this->ssl_bump_tranparent();
				return null;
			}
			$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
			if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
			
			$this->NETWORK_ALL_INTERFACES();
			if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidBinIpaddr])){$SquidBinIpaddr="0.0.0.0";}
			
			if($SquidBinIpaddr<>null){$SquidBinIpaddr="$SquidBinIpaddr:";}
			echo "Starting......: ".date("H:i:s")." [SSL]: SSL_BUMP ? Bind `$SquidBinIpaddr` = $this->SSL_BUMP\n";
			if($this->SSL_BUMP==1){$http_port_sslbump=$this->ssl_bump_port();}
			if($http_port_sslbump==null){
				if(($this->IS_32) or ($this->IS_33)){
					echo "Starting......: ".date("H:i:s")." [SSL]: SSL_BUMP -> https_certificates_token\n";
					$http_port_sslbump=$this->https_certificates_token();
				}
			}
			
			if($this->isNGnx()){
				if($this->listen_port==80){$this->listen_port=8080;}
				if($this->ssl_port==443){$this->ssl_port=4443;}
			}			
			$kettoken=$this->ssl_bump_token();
			$f[]="# ---------  SSL PORT = Not transparent ( $kettoken will be removed )...";
			$http_port_sslbump=str_replace($kettoken, "", $http_port_sslbump);
			$f[]="https_port $SquidBinIpaddr$this->ssl_port$http_port_sslbump";
			return @implode("\n", $f);
		}
		
		private function http_port_transparent_acl(){
			$f[]="# TRANSPARENT_PORTS_LIST = ".@implode(",", $this->TRANSPARENT_PORTS_LIST);
			if(count($this->TRANSPARENT_PORTS_LIST)==0){return;}
			$tt=array();
			$token=$this->myport_token();
			
				while (list ($index, $port) = each ($this->TRANSPARENT_PORTS_LIST) ){if($port>0){$tt[$port]=$port;}}
				if(count($tt)>0){
					$d=array();
					while (list ($port, $ligne) = each ($tt) ){if(!is_numeric($port)){continue;}$d[]=$port;}
				}
					
			if(count($d)==0){return;}
			$f[]="acl MyTransparentPort $token ". @implode(" ", $d); 
			return @implode("\n",$f);
			
		}
		
		
		private function https_certificates_token(){
			echo "Starting......: ".date("H:i:s")." [SSL]: $this->certificate_center\n";
			return $this->BuildSquidCertificate($this->certificate_center);
		}
		
		
		private function mgr_port(){
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$token=$this->myport_token();
			
			
			if($this->MGR_LISTEN_PORT==0){
				$sock=new sockets();
				$this->MGR_LISTEN_PORT=$sock->GET_INFO("SquidMgrListenPort");
				if(!is_numeric($this->MGR_LISTEN_PORT)){
					$this->MGR_LISTEN_PORT=rand(50000,64000);
					$sock->SET_INFO("SquidMgrListenPort", $this->MGR_LISTEN_PORT);
				}
			}
			$f[]="# ---------";
			$f[]="# --------- Serve Artica for status and checkup Squid service";
			$f[]="acl MgrInfoUri urlpath_regex /squid-internal-dynamic/";
			$f[]="acl MgrInfoUri urlpath_regex /squid-internal-mgr/";
			$f[]="acl squidclient proto cache_object";
			$f[]="acl PURGE method PURGE";
			
			
			$f[]="acl MgRPort $token $this->MGR_LISTEN_PORT";
			$f[]="acl MgRClient src 127.0.0.1";
			$ips=$unix->NETWORK_ALL_INTERFACES(true);
			while (list ($ipI, $ligne) = each ($ips) ){
				if($ipI=="127.0.0.1"){continue;}
				$f[]="acl MgRClient src $ipI";
				
			}
			
			
			$f[]="acl MgRDest dst 127.0.0.1\n";
			$f[]="http_access allow MgRPort squidclient all";
			$f[]="http_access allow MgRPort PURGE all";
			$f[]="http_access allow MgRPort MgRClient all";
			$f[]="http_access deny  MgRPort !MgRClient";
			
			$http_access_debugport=$this->http_access_debugport();
			if($http_access_debugport<>null){$f[]=$http_access_debugport."\n";}
			$f[]="# ---------";
			$f[]="# ---------";
			$f[]="";
			$f[]="";
			return @implode("\n", $f);
			
		}
		
		private function ssl_bump_token(){

			if($this->IS_32){return "ssl-bump";}
			if($this->IS_33){return "ssl-bump";}
			if($this->IS_34){return "ssl-bump";}
			if($this->intvalVersion>316){return "ssl-bump";}
			return "sslBump";
			
		}
		
		
		private function ssl_bump_port(){
			$addedlog=null;
			if(!is_numeric($this->intvalVersion)){$this->CheckVersion();}
			if($this->IS_32){$addedlog=";is 3.2x:yes";}
			if($this->IS_31){$addedlog=$addedlog.";is 3.1x:yes";}
			if($this->IS_27){$addedlog=$addedlog.";is 2.7x:yes";}
			if($this->IS_33){$addedlog=$addedlog.";is 3.3x:yes";}
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: Squid Check *** SSL BUMP - [$this->intvalVersion$addedlog] - ***\n";}
			
			if($this->IS_27){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.7x\n";}
				return null;
			}
			
			if($this->intvalVersion<290){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Not supported in 2.x\n";}
				return null;		
			}			
			if(!is_numeric($this->SSL_BUMP)){return null;}
			if($this->SSL_BUMP==null){return null;}
			if($this->SSL_BUMP==0){return null;}
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: ENABLED ($this->SSL_BUMP)\n";}
			
			writelogs("SQUID INTVAL: $this->intvalVersion",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			
			$certificates=$this->BuildSquidCertificate($this->certificate_center);
			
			$token=" ".$this->ssl_bump_token();
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [SSL]: SSLBUMP:: Token $token L.".__LINE__."\n";}
			$f[]="$token $certificates";
			if(strlen($this->sslcrtd_program)>3){
				if($this->EnableSquidSSLCRTD==1){
					@mkdir("/var/lib/squid/session/ssl",0755,true);
					@chown("/var/lib/squid/session/ssl","squid");
					@chgrp("/var/lib/squid/session/ssl", "squid");
					$f[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 4MB";
					shell_exec("$this->sslcrtd_program -c -s /var/lib/squid/session/ssl/ssl_db >/dev/null 2>&1");
				}
			}
			
			return @implode("\n", $f);
			
		}
		
		private function ACLS_GOOGLE(){
			$sock=new sockets();
			$AllowSquidGoogle=intval($sock->GET_INFO("AllowSquidGoogle"));
			if($AllowSquidGoogle==0){return;}
			$ips=new products_ip_ranges();
			$ips->google_networks();
			$f[]="# --------- AllowSquidGoogle = $AllowSquidGoogle [".__LINE__."]";
			$f[]="acl google_ips dst \"/etc/squid3/acls/google-nets.acl\"";
			$f[]="acl google_www dstdomain  .google.com .google.fr .google.de .google.be .google.co.uk";
			return @implode("\n", $f)."\n";
		}

		
		private function ACLS_BROWSERS(){
			$f=new useragents();
			return $f->ACLS_ALL()."\n";
			
		}
		
		private function ACLS_DROPBOX(){
			$sock=new sockets();
			$AllowSquidSSLDropBox=intval($sock->GET_INFO("AllowSquidSSLDropBox"));
			$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
			if($AllowSquidDropBox==1){$AllowSquidSSLDropBox=1;}
			if($AllowSquidSSLDropBox==1){$AllowSquidDropBox=1;}
			if($AllowSquidDropBox==0){return;}
			$ips=new products_ip_ranges();
			$ips->dropbox_networks();
			$f[]="# --------- AllowSquidSSLDropBox = $AllowSquidDropBox AllowSquidDropBox = $AllowSquidDropBox [".__LINE__."]";
			$f[]="acl dropbox_ips dst \"/etc/squid3/acls/dropbox-nets.acl\"";
			$f[]="acl dropbox_www dstdomain  .dropbox.com";
			return @implode("\n", $f)."\n";
		}
		
		private function ACLS_SKYPE(){
			$sock=new sockets();
			$AllowSquidSSLSkype=intval($sock->GET_INFO("AllowSquidSSLSkype"));
			$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
			if($AllowSquidSkype==1){$AllowSquidSSLSkype=1;}
			if($AllowSquidSSLSkype==1){$AllowSquidSkype=1;}
			if($AllowSquidSkype==0){return;}
			$ips=new products_ip_ranges();
			$ips->skype_networks();
			$f[]="# --------- AllowSquidSSLSkype = $AllowSquidSSLSkype AllowSquidSSLSkype = $AllowSquidSSLSkype [".__LINE__."]";
			$f[]="acl skype_www dstdomain  .live.com  .skypeassets.com";
			$f[]="acl skype_ips dst \"/etc/squid3/acls/skype-nets.acl\"";
			return @implode("\n", $f)."\n";
		}

		private function ACLS_OFFICE365(){
			$sock=new sockets();
			
			$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			if($AllowSquidOffice365==0){return;}
			$ips=new products_ip_ranges();
			
			$ips->office365_networks(); 
			$ips->office365_domains(); 
			
			
			$f[]="# --------- Office365 [".__LINE__."]";
			$f[]="acl office365_www dstdomain \"/etc/squid3/acls/office365-domains.acl\"";
			$f[]="acl office365_ips dst \"/etc/squid3/acls/office365-nets.acl\"";
			return @implode("\n", $f)."\n";
		}	

		private function ssl_office365(){
			$sock=new sockets();
			$AllowSquidOffice365=intval($sock->GET_INFO("AllowSquidOffice365"));
			
			if($AllowSquidOffice365==0){return "# --------- Office365 not trusted [".__LINE__."]\n";}
			include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
				
			$f[]="# --------- no bumping SSL for Office365 [".__LINE__."]";
			$f[]="ssl_bump none office365_ips";
			$f[]="ssl_bump none office365_www";
			return @implode("\n", $f)."\n";
		}		
		
		
		private function ssl_dropbox(){
			$sock=new sockets();
			$AllowSquidSSLDropBox=intval($sock->GET_INFO("AllowSquidSSLDropBox"));
			$AllowSquidDropBox=intval($sock->GET_INFO("AllowSquidDropBox"));
			if($AllowSquidDropBox==1){$AllowSquidSSLDropBox=1;}
			if($AllowSquidSSLDropBox==0){return "# --------- DropBox not trusted [".__LINE__."]\n";}
			include_once(dirname(__FILE__)."/class.products-ip-ranges.inc");
			
			$f[]="# --------- no bumping SSL for dropbox [".__LINE__."]";
			$f[]="ssl_bump none dropbox_ips";
			$f[]="ssl_bump none dropbox_www";
			return @implode("\n", $f)."\n";
		}
		private function ssl_skype(){
			$sock=new sockets();
			$AllowSquidSSLSkype=intval($sock->GET_INFO("AllowSquidSSLSkype"));
			$AllowSquidSkype=intval($sock->GET_INFO("AllowSquidSkype"));
			if($AllowSquidSkype==1){$AllowSquidSSLSkype=1;}
			if($AllowSquidSSLSkype==0){return "# --------- Skype not trusted [".__LINE__."]\n";}
			$f[]="# --------- no bumping SSL for Skype [".__LINE__."]";

			$f[]="ssl_bump none skype_ips";
			$f[]="ssl_bump none skype_www";
			return @implode("\n", $f)."\n";
		}		

		
		
		
		
		public function get_ssl_port(){
			
			if($this->ssl_port>0){
				if($this->isNGnx()){if($this->ssl_port==443){$this->ssl_port=10443;}}
				return $this->ssl_port;
			}
			
			if($this->second_listen_portForTransparent>0){ 
				$this->ssl_port=$this->second_listen_portForTransparent+1;
				$this->SaveToLdap();
				return $this->ssl_port;
			}
			
			if($this->ssl_port==0){
				$this->ssl_port=$this->listen_port+2;
				return $this->ssl_port;
			}
		}
		
		private function MICROSOFT_ACLS(){
			if($this->SquidUrgency==1){return;}
			$f[]="acl wuCONNECT dstdomain www.update.microsoft.com";
			$f[]="acl windowsupdate dstdomain windowsupdate.microsoft.com";
			$f[]="acl windowsupdate dstdomain .update.microsoft.com";
			$f[]="acl windowsupdate dstdomain download.windowsupdate.com";
			$f[]="acl windowsupdate dstdomain redir.metaservices.microsoft.com";
			$f[]="acl windowsupdate dstdomain images.metaservices.microsoft.com";
			$f[]="acl windowsupdate dstdomain c.microsoft.com";
			$f[]="acl windowsupdate dstdomain www.download.windowsupdate.com";
			$f[]="acl windowsupdate dstdomain wustat.windows.com";
			$f[]="acl windowsupdate dstdomain crl.microsoft.com";
			$f[]="";
			$f[]="http_access allow CONNECT wuCONNECT all";
			$f[]="http_access allow windowsupdate all";
			$f[]="";
			@file_put_contents("/etc/squid3/microsoft.conf", @implode("\n", $f));
			
		}
		
		
		
		private function ssl_bump_tranparent($listen_port=null){
			$listen_port=0;
			if($this->SSL_BUMP==null){return null;}
			if($this->SSL_BUMP==0){return null;}
			if($this->hasProxyTransparent==0){return null;}
			if($this->EnableTransparent27==1){return "# No SSL bump for transparent nat compatitbility [".__LINE__."]\n";}
			
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return null;
			}
			
			
			$listen_port=$this->ssl_port;
			$logs[]="# EnableSquidSSLCRTD = $this->EnableSquidSSLCRTD [".__LINE__."]";
			$logs[]="# SSL_BUMP = $this->SSL_BUMP [".__LINE__."]";
			$logs[]="# SQUID INTVAL = $this->intvalVersion [".__LINE__."]";
			$logs[]="# sslcrtd_program = $this->sslcrtd_program (".strlen($this->sslcrtd_program).") [".__LINE__."]";
			
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." SSLBUMP:: SSL Port: $listen_port\n";}
			$sock=new sockets();
			$SquidBinIpaddr=trim($sock->GET_INFO("SquidBinIpaddr"));
			if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
			$this->NETWORK_ALL_INTERFACES();
			if(!isset($GLOBALS["NETWORK_ALL_INTERFACES"][$SquidBinIpaddr])){$SquidBinIpaddr="0.0.0.0";}
			
			if($SquidBinIpaddr<>null){$SquidBinIpaddr="$SquidBinIpaddr:";}
			writelogs("SQUID INTVAL: $this->intvalVersion",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				
			if($this->EnableSquidSSLCRTD==1){
				if(strlen($this->sslcrtd_program)>3){
					$Chain=@file_get_contents("/etc/squid3/privkey.pem")."\n".@file_get_contents("/etc/squid3/cacert.pem");
					@file_put_contents("/etc/squid3/ssl/chain-default.pem", $Chain);
					$CertToken="generate-host-certificates=on cert=/etc/squid3/ssl/chain-default.pem";
					$KeyToken="dynamic_cert_mem_cache_size=4MB";
				}
			}
				

			
	
			$logs[]="# certificate = $this->certificate_center [".__LINE__."]";
			$logs[]="# EnableTransparent27 = $this->EnableTransparent27 [".__LINE__."]";
			
			
			$logsTXT=@implode("\n", $logs)."\n";
			
			
			$certificates=$this->BuildSquidCertificate($this->certificate_center);
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [SSL]: this->TRANSPARENT_PORTS_LIST[]=$listen_port\n";}
			
				
			
			
			$this->TRANSPARENT_PORTS_LIST[]=$listen_port;
			
			
			
			if($this->intvalVersion>=329){
				return "{$logsTXT}https_port $SquidBinIpaddr$listen_port intercept ssl-bump $certificates\n";
			}
			
			if($this->intvalVersion>=320){
				return "{$logsTXT}https_port $SquidBinIpaddr$listen_port intercept ssl-bump $certificates\n";
			}
			
			if($this->intvalVersion>316){
				return "{$logsTXT}https_port $SquidBinIpaddr$listen_port intercept ssl-bump $certificates\n";
			}
			
			if($this->intvalVersion<290){
				return "{$logsTXT}https_port $SquidBinIpaddr$listen_port transparent $certificates\n";
			}
			
			return "{$logsTXT}https_port $SquidBinIpaddr$listen_port transparent sslBump $certificates\n";  
		}
		
		private function ssl_bump_access(){
			if($this->SSL_BUMP==0){return null;}
			if($this->IS_27){return null;}
			if(!$this->ASROOT){return;}
			
			$params=$this->compilation_params();
			if(!isset($params["enable-ssl"])){
				echo "Starting......: ".date("H:i:s")." [SSL]: FATAL: Not compiled...\n";
				return null;
			}			
			
			$deny="deny";
			$allow="allow";
			
			if($this->IS_33){
				$deny="none";
				$allow="client-first";
				if($this->EnableSquidSSLCRTD==1){
					$allow="server-first";
				}
			}
			
			if($this->EnableSquidSSLCRTD==1){
				if(strlen($this->sslcrtd_program)>3){
					$array[]="sslcrtd_program $this->sslcrtd_program -s /var/lib/squid/session/ssl/ssl_db -M 4MB";
					
				}
			}
			
			
			
			$www_table=array();
			if($this->SSL_BUMP_WHITE_LIST==1){
				$array[]="ssl_bump $deny all";
				$array[]="sslproxy_cert_error allow all";
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
					if(preg_match("#^www\.(.+)#", $www,$re)){$www=".".$re[1];}
					if(substr($www, 0,1)<>"."){$www=".".$www;}
					if(isset($ALREADY[$www])){continue;}
					$ALREADY[$www]=true;
					$www_table[]=$www;
				}
			}
			
			if(count($www_table)>0){
				@file_put_contents("/etc/squid3/no_ssl_bump", @implode("\n", $www_table));
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." SSLBUMP:: no bump for ". count($www_table)." domains\n";}
				$array[]="acl no_ssl_bump dstdomain \"/etc/squid3/no_ssl_bump\"";
				$array[]="ssl_bump $deny no_ssl_bump";
				
			}
			$array[]="ssl_bump $allow all";
			$array[]="sslproxy_flags DONT_VERIFY_PEER";
			$array[]="sslproxy_cert_error allow all";
			$array[]="";
			$array[]="";
			return @implode("\n",$array);
		}
		
		private function ArticaHotSpot(){
			if(!$this->ASROOT){return;}
			$sock=new sockets();
			$EnableArticaHotSpot=$sock->GET_INFO("EnableArticaHotSpot");
			if(!is_numeric($EnableArticaHotSpot)){$EnableArticaHotSpot=0;}
			if($EnableArticaHotSpot==0){return;}
			$this->EnableSquidSSLCRTD=1;
			$transparent_token=$this->transparent_token();
			$ssl_bump_token=$this->ssl_bump_token();
			$f[]="http_port 0.0.0.0:$this->ArticaHotSpotPort $transparent_token";
			
			$http_port_sslbump=$this->https_certificates_token();
			$ssl_bump_token=$this->ssl_bump_token();
			$f[]="https_port 0.0.0.0:$this->ArticaSSLHotSpotPort $transparent_token $ssl_bump_token$http_port_sslbump";
			return @implode("\n", $f);
			
		}


		private function haarp_config(){
			if(!$this->ASROOT){return;}
			
			if(isset($GLOBALS["HAARP_ACL"])){return;}
			if($this->SquidUrgency==1){return;}
			if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
			$sock=new sockets();
			$Masterbin=$unix->find_program("haarp");
			if(!is_file($Masterbin)){
				echo "Starting......: ".date("H:i:s")." [HAA]: Not Installed\n";
				$GLOBALS["HAARP_ACL"]=null;
				return;
			}
			$EnableHaarp=$sock->GET_INFO("EnableHaarp");
			if(!is_numeric($EnableHaarp)){$EnableHaarp=0;}
			if($EnableHaarp==0){
				echo "Starting......: ".date("H:i:s")." [HAA]: Not Activated\n";
				$GLOBALS["HAARP_ACL"]=null;return;}
			
			
			$haarp=new haarp(true);
			$q=new mysql_squid_builder();
			$sql="SELECT pattern FROM haarp_redirpats";
			$results=$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." [HAA]: FATAL ! $q->mysql_error\n";}
			$Countrules=@mysql_num_rows($results);
			echo "Starting......: ".date("H:i:s")." [HAA]: $Countrules redirect rules\n";
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				if(trim($ligne["pattern"])==null){continue;}
				$t[]=trim($ligne["pattern"]);
				
			}
			
			$HaarpPort=$sock->GET_INFO("HaarpPort");
			
			$myport_token=$this->myport_token();
			
			if(!is_numeric($HaarpPort)){$HaarpPort=0;}
			if($HaarpPort==0){
				$HaarpPort=rand(35000, 64000);
				$sock->SET_INFO("HaarpPort", $HaarpPort);
			}
			
			
			$HaarpSquidPort=$sock->GET_INFO("HaarpSquidPort");
			if(!is_numeric($HaarpSquidPort)){$HaarpSquidPort=0;}
			if($HaarpSquidPort==0){
				$HaarpSquidPort=rand(38000, 64000);
				$sock->SET_INFO("HaarpSquidPort", $HaarpSquidPort);
			}			
			
			echo "Starting......: ".date("H:i:s")." [HAA]: Haarp listen on 127.0.0.1:$HaarpPort -> 127.0.0.1:$HaarpSquidPort\n";
			@file_put_contents("/etc/squid3/haarp.acl", @implode("\n",$t));
			
			
			$acls[]="acl haarp_lst url_regex -i \"/etc/squid3/haarp.acl\"";
			//$acls[]="acl haarp_port $myport_token $HaarpSquidPort";
			//$acls[]="http_port 127.0.0.1:$HaarpSquidPort";
			$GLOBALS["HAARP_ACL"]=@implode("\n", $acls);
			$GLOBALS["dead_peer_timeout"]=30;
					
			$GLOBALS["HAARP_CACHE_DENY"]="cache deny haarp_lst";
			$f[]="# --------- Intelligent cache..";
			$f[]="cache_peer 127.0.0.1 parent $HaarpPort 0 proxy-only no-digest no-query name=HaarpPeer";
			$f[]="cache_peer_access HaarpPeer allow haarp_lst";
			$f[]="cache_peer_access HaarpPeer deny all";
			$f[]="never_direct allow haarp_lst\n";
			$f[]="always_direct deny all\n";
			$f[]="# ---------";
			$GLOBALS["HAARP_CACHE_PEER"]=@implode("\n", $f);
			
		}
		
		
		public function ftp_parameters(){	
				if($this->SquidUrgency==1){return;}
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
		
		
	private function squid_reverse_websites_vhosts($keyname,$content){
		$array=unserialize(base64_decode($content));
		if(count($array)==0){return null;}
		$acl_addwww=array();
		while (list ($website_name, $cidr) = each ($array)){
			$website_name=str_replace("http://","",$website_name);
			$website_name=str_replace("https://","",$website_name);			
			$acl_addwww[]=$website_name;
		}
		if(count($acl_addwww)>0){
			$GLOBALS["Rvhosts"][$keyname]=" ".@implode(" ", $acl_addwww);
			return "cache_peer_domain $keyname ".@implode(" ", $acl_addwww);
			
		}
		
	}
	
	private function squid_reverse_websites_buildkey($website_name){
		$maxChar=31-4;
		$website_name=str_replace("http://","",$website_name);
		$website_name=str_replace("https://","",$website_name);		
		$website_name=str_replace(".","",$website_name);
		$website_name=str_replace("-","",$website_name);
		if(strlen($website_name)>$maxChar){
			$website_name=substr($website_name, 0,$maxChar);
		}
		return $website_name;
	}
		
	private function squid_reverse_websites(){
			if(!$this->ASROOT){return;}
			$sql="SELECT * FROM squid_accel WHERE `enabled`=1 ORDER BY ID DESC ";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			
			echo "Starting......: ".date("H:i:s")." [REVERSE]: UseSSL=0 ". mysql_num_rows($results)." items\n";
			
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				
				
				$arrayOPTS=unserialize(base64_decode($ligne["options"]));
				$ExtraOptions=$this->cache_peer_extract_options($arrayOPTS);
				if($ExtraOptions==null){$ExtraOptions="no-query no-digest";}
				if($ExtraOptions<>null){$ExtraOptions=" $ExtraOptions ";}
				
				if($ligne["UseSSL"]==1){
					echo "Starting......: ".date("H:i:s")." [REVERSE]:NON-SSL UseSSL={$ligne["UseSSL"]} Skip {$ligne["website_name"]} item\n";
					continue;
				}
				$frontend_options=null;
				$website_name=$ligne["website_name"];
				$website_name=str_replace("http://","",$website_name);
				$website_name=str_replace("https://","",$website_name);
				//if(preg_match("#^.+?\.(.+?)\.(.+?)$#",$website_name,$re)){$acl_addwww=" .{$re[1]}.{$re[2]}";}else{$acl_addwww=$website_name;}
				$acl_addwww=$website_name;
				$ip=$ligne["website_ip"];
				$port=$ligne["website_port"];
				if(!is_numeric($port)){$port=80;}
				if($port==0){$port=80;}
				if($website_name==null){continue;}
				if($ip==null){continue;}
				$website_name_name=$this->squid_reverse_websites_buildkey($website_name);
				if(isset($ALREDYPEER[$website_name_name])){continue;}
				if($port==443){
					$frontend_options="front-end-https=on ssl sslflags=DONT_VERIFY_PEER".$this->SaveCertificate($website_name,true);
				}
				$ALREDYPEER[$website_name_name]=true;
				$conf[]="cache_peer $ip parent $port 0 $ExtraOptions originserver $frontend_options name=$website_name_name ";
				if($ligne["UseVirtualHosts"]==1){
					$UseVirtualHosts=$this->squid_reverse_websites_vhosts($website_name_name,$ligne["virtualhosts"]);
					if($UseVirtualHosts<>null){
						$conf[]=$UseVirtualHosts;
					}
				}
				
				if(!isset($GLOBALS["Rvhosts"][$website_name_name])){$GLOBALS["Rvhosts"][$website_name_name]=null;}
				
				$acls[]="acl acl_$website_name_name dstdomain $acl_addwww{$GLOBALS["Rvhosts"][$website_name_name]}";
				$cache_peer_domain[]="cache_peer_domain $website_name_name $website_name";
				$cache_peer_access[]="cache_peer_access $website_name_name allow acl_$website_name_name";
				$cache_peer_access[]="cache_peer_access $website_name_name deny all\n";
				$http_access[]="http_access allow acl_$website_name_name all";
				
			}
			
			
			$sql="SELECT * FROM squid_accel WHERE UseSSL='1' AND `enabled`=1 ORDER BY ID DESC";
			$q=new mysql();
			$results=$q->QUERY_SQL($sql,"artica_backup");
			echo "Starting......: ".date("H:i:s")." [REVERSE]: UseSSL=1 ". mysql_num_rows($results)." items\n";
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
				if(!is_numeric($ligne["UseSSL"])){$ligne["UseSSL"]=0;}
				$commonname=$ligne["certificate"];
				echo "Starting......: ".date("H:i:s")." [REVERSE]:SSL `{$ligne["website_name"]}` Cert:`$commonname`\n";
				if($ligne["UseSSL"]==0){echo "Starting......: ".date("H:i:s")." [REVERSE]:SSL UseSSL={$ligne["UseSSL"]} Skip {$ligne["website_name"]} item\n";continue;}
				
				
				$arrayOPTS=unserialize(base64_decode($ligne["options"]));
				$ExtraOptions=$this->cache_peer_extract_options($arrayOPTS);
				if($ExtraOptions==null){$ExtraOptions="no-query no-digest";}
				if($ExtraOptions<>null){$ExtraOptions=" $ExtraOptions ";}				
				
				
				$website_name=$ligne["website_name"];
				$website_name=str_replace("http://","",$website_name);
				$website_name=str_replace("https://","",$website_name);
				$acl_addwww=$website_name;
				//if(preg_match("#^.+?\.(.+?)\.(.+?)$#",$website_name,$re)){$acl_addwww=" .{$re[1]}.{$re[2]}";}else{$acl_addwww=$website_name;}
				$ip=$ligne["website_ip"];
				$port=$ligne["website_port"];
				if(!is_numeric($port)){$port=433;}
				if($port==0){$port=443;}
				
				if($website_name==null){continue;}
				if($ip==null){continue;}
				
				$website_name_name="S".$this->squid_reverse_websites_buildkey($website_name);
				if(isset($ALREDYPEER[$website_name_name])){continue;}
				$ALREDYPEER[$website_name_name]=true;				
				$frontend_options="front-end-https=on ssl sslflags=DONT_VERIFY_PEER".$this->SaveCertificate($commonname,true);
			    //if($port==80){$frontend_options=null;}
				
				if($ligne["UseVirtualHosts"]==1){
					$UseVirtualHosts=$this->squid_reverse_websites_vhosts("$website_name_name",$ligne["virtualhosts"]);
					if($UseVirtualHosts<>null){
						$conf[]=$UseVirtualHosts;
					}
				}				
				
				if(!isset($GLOBALS["Rvhosts"]["$website_name_name"])){$GLOBALS["Rvhosts"]["$website_name_name"]=null;}
				$conf[]="cache_peer $ip parent $port 0 $ExtraOptions $frontend_options originserver name=$website_name_name";
				$acls[]="acl acl_$website_name_name dstdomain $acl_addwww{$GLOBALS["Rvhosts"][$website_name_name]}";
				$cache_peer_access[]="cache_peer_access $website_name_name allow acl_$website_name_name";
				$cache_peer_access[]="cache_peer_access $website_name_name deny all\n";
				$cache_peer_domain[]="cache_peer_domain $website_name_name $website_name";
				$http_access[]="http_access allow acl_$website_name_name all";
				
			}			
			
			$GLOBALS["HTTP_ACCESS_REVERSE"]=implode("\n",$http_access);
		
			if(is_array($conf)){
				$ii[]=implode("\n",$conf);
				$ii[]="";
				$ii[]=implode("\n",$acls);
				$ii[]="";
				
				$ii[]=implode("\n",$cache_peer_domain);	
				$ii[]="";
				$ii[]=implode("\n",$cache_peer_access);	
				$ii[]="";
						
				return "\n".implode("\n",$ii)."\n";						
			}
		
	}
		
public function refresh_pattern_list(){
	
	$sock=new sockets();
	$users=new usersMenus();
	$this->DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if($SquidCacheLevel==0){
		$this->DisableAnyCache=1;
		$conf[]="reload_into_ims off";
		$conf[]="refresh_pattern -i . 0 100% 0";
		return @implode("\n", $conf);
	}

	if(!is_numeric($this->DisableAnyCache)){$this->DisableAnyCache=0;}
	if($this->DisableAnyCache==1){return;}
	
	$reload_into_ims=$sock->GET_INFO("SquidReloadIntoIMS");
	if(!is_numeric($reload_into_ims)){$reload_into_ims=1;}
	
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=4320;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_max=43200;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=40;}
	
	if($SquidCacheLevel>2){
		$compilation_params=$this->compilation_params();
		if(isset($compilation_params["enable-http-violations"])){
			if($reload_into_ims==1){
				$conf[]="reload_into_ims on";
			}else{
				$conf[]="reload_into_ims off";
			}
		}
	}
	
	
	if(!$users->CORP_LICENSE){
		$conf[]="# --------- No license, keep default one for minimalist settings";
		$conf[]=$this->refresh_pattern_defaults();
		return implode("\n",$conf)."\n";
	}
	

	if($this->ForceWindowsUpdateCaching==1){
		$conf[]="# --------- Make Windows Updates cache";
		$conff[]="refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$conf[]="refresh_pattern -i windows.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
	
	}
	
	if($SquidCacheLevel<3){
		$conf[]="reload_into_ims off";
	}
	
	if($SquidCacheLevel==1){
		$conf[]="# --------- SquidCacheLevel:$SquidCacheLevel, Cache level is set to minimalist";
		$conf[]=$this->refresh_pattern_defaults();
		return implode("\n",$conf)."\n";
	
	}
	
	
	$q=new mysql_squid_builder();
	$count=$q->COUNT_ROWS("main_cache_rules");
	
	$CountOfUsersRules=0;
	
	
	
	if($count>0){
		$rules=new squid_caches_rules();
		$rules->build();
		$CountOfUsersRules=count($rules->final_array);
		$conf[]="# --------- $count Cache rules: ".count($rules->final_array)." Lines";
		$conf[]=@implode("\n", $rules->final_array);
		$conf[]="refresh_pattern .	$refresh_pattern_def_min\t{$refresh_pattern_def_perc}%\t$refresh_pattern_def_max";
		return implode("\n",$conf)."\n";
	}
	
	$sql="SELECT *  FROM websites_caches_params WHERE `sitename` IS NOT NULL";
	
	$results=$q->QUERY_SQL($sql);
	
	$ignore_no_cache="ignore-no-cache ";
	if($this->IS_33){$ignore_no_cache=null;}
	
	$notpoint=true;
	$options[0]="";
	$options[1]="{$ignore_no_cache}ignore-no-store ignore-private refresh-ims";
	$options[2]="override-expire {$ignore_no_cache}ignore-no-store ignore-private override-lastmod ignore-auth ignore-reload";

	$CountOfUsersRules=$CountOfUsersRules+mysql_num_rows($results);

	$c=0;
	//sitename,MIN_AGE,MAX_AGE,PERCENT,options
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=trim($ligne["sitename"]);
		$pattern=str_replace(".","\.",$pattern);
		if($pattern=='.'){continue;}
		$c++;
		$already[$pattern]=true;
		$already[".$pattern"]=true;
		
		$conf[]="refresh_pattern .$pattern\t{$ligne["MIN_AGE"]}\t{$ligne["PERCENT"]}%\t{$ligne["MAX_AGE"]} {$options[$ligne["options"]]}";
	}
	
	$sql="SELECT * FROM `squid_speed` WHERE `domain` IS NOT NULL";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$CountOfUsersRules=$CountOfUsersRules+mysql_num_rows($results);
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pattern=trim($ligne["domain"]);
		if(strpos("  $pattern  ", " -i ")==0){$pattern="-i $pattern";} 
		if(isset($already[".$pattern"])){continue;}
		
		if($this->IS_33){
			if($ligne["refresh_pattern_perc"]>100){$ligne["refresh_pattern_perc"]=100;}
			$ligne["refresh_pattern_options"]=trim(str_replace("ignore-no-cache", "", $ligne["refresh_pattern_options"]));
		}
		
		if($pattern=='.'){
			$notpoint=false;
			continue;
			$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]} {$ligne["refresh_pattern_options"]}";
		}
		
		
		$c++;
		$pattern=str_replace(".","\.",$pattern);
		$pattern=str_replace("*",".*",$pattern);
		$conf[]="refresh_pattern $pattern  {$ligne["refresh_pattern_min"]}    {$ligne["refresh_pattern_perc"]}%     {$ligne["refresh_pattern_max"]}  {$ligne["refresh_pattern_options"]}";
		
	}
	if($CountOfUsersRules>0){
		$conf[]="refresh_pattern .	$refresh_pattern_def_min\t{$refresh_pattern_def_perc}%\t$refresh_pattern_def_max reload-into-ims ignore-reload";
		return implode("\n",$conf)."\n";
	}
	
	

	
	$conf[]="# --------- License OK but no rules defined, add minimalistics patterns";
	$conf[]=$this->refresh_pattern_defaults();
	return implode("\n",$conf)."\n";
}

private function refresh_pattern_defaults(){
	$sock=new sockets();
	$final="refresh_pattern -i . 0 10% 5760 reload-into-ims ignore-reload";
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	
	if($SquidCacheLevel<4){
		$final="refresh_pattern -i . 0 50% 4320";
	}
	
	$ignore_no_cache="ignore-no-cache ";
	if($this->IS_33){$ignore_no_cache=null;}
	if($SquidCacheLevel>2){
		
		$f[]="refresh_pattern -i microsoft.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="refresh_pattern -i windowsupdate.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="refresh_pattern -i my.windowsupdate.website.com/.*\.(cab|exe|ms[i|u|f]|asf|wm[v|a]|dat|zip) 4320 80% 43200 reload-into-ims";
		$f[]="";
		$f[]="##images facebook";
		$f[]="refresh_pattern ((facebook.com)|(173.252.110.27)).*\.(jpg|png|gif) 10800 80% 10800 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern -i \.fbcdn.net.*\.(jpg|gif|png|swf|mp3) 10800 80% 10800 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern  static\.ak\.fbcdn\.net*\.(jpg|gif|png) 10800 80% 10800 ignore-reload  override-expire ignore-no-store";
		$f[]="refresh_pattern ^http:\/\/profile\.ak\.fbcdn.net*\.(jpg|gif|png) 10800 80% 10800 ignore-reload  override-expire ignore-no-store";
		$f[]="";
		$f[]="## ANTI VIRUS";
		$f[]="refresh_pattern guru.avg.com/.*\.(bin)                      10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern (avgate|avira).*(idx|gz)$ 10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern kaspersky.*\.avc$ 10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern kaspersky 10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern update.nai.com/.*\.(gem|zip|mcs) 10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="refresh_pattern ^http:\/\/liveupdate.symantecliveupdate.com.*\(zip) 10800 80% 10800 ignore-no-store  ignore-reload  reload-into-ims";
		$f[]="";
		$f[]="##Musics";
		$f[]="refresh_pattern -i";
		$f[]="\.(mp2|mp3|mid|midi|mp[234]|wav|ram|ra|rm|au|3gp|m4r|m4a)(\?.*|$) 10080   90%   43200 override-expire ignore-reload reload-into-ims ignore-private ";
		$f[]="## Videos";
		$f[]="refresh_pattern -i";
		$f[]="\.(mpg|mpeg|mp4|m4v|mov|avi|asf|wmv|wma|dat|flv|swf)(\?.*|$) 10080 90%   43200 override-expire ignore-reload reload-into-ims ignore-private";
		$f[]="## Pictures";
		$f[]="refresh_pattern -i \.(gif|png|jpg|jpeg|ico)(\?.*|$) 3600 90% 43200 override-expire ignore-no-store ignore-private";
		$f[]="refresh_pattern -i \.(gif|png|jpg|jpeg|ico|bmp|tiff|webp|bif|ver|pict) 220000 90% 300000 override-expire reload-into-ims ignore-reload {$ignore_no_cache} ignore-private ignore-no-store";
		$f[]="## Musics";
		$f[]="refresh_pattern -i \.(mp[34g]|swf|wav)$ 43200 90% 432000";		
		
		
		$f[]="refresh_pattern -i \.(swf|js|wav|css|class|dat|zsci|do|ver|advcs|woff|eps|ttf|svg|svgz|ps|acsm) 220000 90% 300000 override-expire reload-into-ims ignore-reload {$ignore_no_cache} ignore-no-store ignore-private";
		$f[]="refresh_pattern -i \.(html|htm|crl) 9440 90% 100000 override-expire reload-into-ims ignore-reload {$ignore_no_cache} ignore-no-store ignore-private ignore-must-revalidate";
		$f[]="refresh_pattern -i \.(xml|flow) 0 90% 100000 reload-into-ims";
		$f[]="refresh_pattern -i \.(json|json\?) 1440 90% 5760 reload-into-ims";
		$f[]="refresh_pattern -i (/cgi-bin/|\?) 0 0% 0";
		$f[]="refresh_pattern -i ^http:\/\/liveupdate.symantecliveupdate.com.*\(zip) 0 0% 0";
		$f[]="refresh_pattern -i \.(bin|deb|rpm|drpm|exe|zip|tar|tgz|bz2|ipa|bz|ram|rar|bin|uxx|gz|crl|msi|dll|hz|cab|psf|vidt|apk|wtex|hz) 220000 90% 500000 override-expire reload-into-ims ignore-reload {$ignore_no_cache} ignore-no-store ignore-private ignore-must-revalidate";
		$f[]="refresh_pattern -i \.(ppt|ppt\?|pptx|doc|docx|pdf|xls|xlsx|csv|txt) 220000 90% 200000 override-expire reload-into-ims ignore-reload {$ignore_no_cache} ignore-no-store ignore-private ignore-must-revalidate";
		$f[]="refresh_pattern -i ^ftp: 66000 90% 200000";
		$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
	}
	
	if($SquidCacheLevel<3){
		$f[]="refresh_pattern -i ^ftp: 1440 20% 10080";
		$f[]="refresh_pattern -i ^gopher: 1440 0% 1440";
		$f[]="refresh_pattern -i (/cgi-bin/|\?) 0 0% 0";
	}	
	
	$f[]=$final;
	return @implode("\n", $f);
}



public function SQUID_LOCAL_NETWORKS(){
		$sock=new sockets();
		$k=array();
		$conf=array();
		$AllowAllNetworksInSquid=$sock->GET_INFO("AllowAllNetworksInSquid");
		if(!is_numeric($AllowAllNetworksInSquid)){$AllowAllNetworksInSquid=1;}
		if($AllowAllNetworksInSquid==1){
			$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow {$this->MyStandPort}office_network";
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
	$GLOBALS["HTTP_ACCESS"]["OFFICE_NETWORK"]="http_access allow {$this->MyStandPort}office_network";
	$conf[]="acl office_network src " . implode(" ",$s);
	return @implode("\n",$conf);
}
		
		
		
function SAFE_PORTS(){
	$sock=new sockets();
	$add=false;
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
	
	$portsSSL=unserialize(base64_decode($sock->GET_INFO("SquidSafePortsSSLList")));
	if(is_array($portsSSL)){
		while (list ($num, $val) = each ($portsSSL) ){$ports[$num]=$val;}
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
	public $main_array=array();
	public $EnableClamavInCiCap=1;
	public $EnableCicapToSyslog=0;
	public $EnableSquidGuardInCiCAP=1;
	public $EnableUfdbGuard=0;
	public $enable_metascanner=0;
	private $EnableRemoteStatisticsAppliance=0;
	private $AntivirusEnabledCentral=1;
	private $UfdbEnabledCentral=1;
	
	function cicap(){
		
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$ini->loadString($sock->GET_INFO('CicapInternalConfiguration'));
		$this->main_array=$ini->_params;
		$this->BuildDefaults();
		$this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		$this->EnableCicapToSyslog=$sock->GET_INFO("EnableCicapToSyslog");
		$this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		$this->EnableUfdbGuard=$sock->EnableUfdbGuard();
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$this->UfdbEnabledCentral=$sock->GET_INFO("UfdbEnabledCentral");
		$this->AntivirusEnabledCentral=$sock->GET_INFO("AntivirusEnabledCentral");
		
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->UfdbEnabledCentral)){$this->UfdbEnabledCentral=1;}
		if(!is_numeric($this->AntivirusEnabledCentral)){$this->AntivirusEnabledCentral=1;}
		
		if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=1;}
		if(!is_numeric($this->EnableClamavInCiCap)){$this->EnableClamavInCiCap=1;}
		if(!is_numeric($this->EnableCicapToSyslog)){$this->EnableCicapToSyslog=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->EnableUfdbGuard=$this->UfdbEnabledCentral;
		}
		
		if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
		$users=$GLOBALS["CLASS_USERS"];
		if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
		if($users->APP_UFDBGUARD_INSTALLED){
			if($this->EnableUfdbGuard==1){
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." c-icap disabling squidGuard databases, UfdbGuard is enabled\n";}
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
		if($this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]==null){$this->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"]="10";}
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
	
	private function syslogger(){
		if($this->EnableCicapToSyslog==0){return;}
		$modules_path=$this->LOCATE_MODULES_DIR();
		if(!is_file("$modules_path/sys_logger.so")){return;}
		$conf[]="";
		$conf[]="#Sysloger";
		$conf[]="Module logger sys_logger.so";
		$conf[]="Logger sys_logger";
		$conf[]="sys_logger.Prefix \"C-ICAP:\"";
		$conf[]="sys_logger.Facility local1";
		# TAG: sys_logger.Facility
		# Format: sys_logger.Facility daemon|user|local1|local2|local3|local4|local5|local6|local7
		# Description:
		#	specifies the facility type of syslog.
		# Default:
		#	sys_logger.Facility daemon
		
		# TAG: sys_logger.access_priority
		# Format: sys_logger.access_priority alert|crit|debug|emerg|err|info|notice|warning
		# Description:
		#	determines  the  importance  of the access log message
		# Default:
		#	sys_logger.access_priority info
		$conf[]="sys_logger.access_priority debug";
		
		# TAG: sys_logger.server_priority
		# Format: sys_logger.server_priority alert|crit|debug|emerg|err|info|notice|warning
		# Description:
		#	determines  the  importance  of the server log message
		# Default:
		#	sys_logger.server_priority crit
		$conf[]="sys_logger.server_priority debug";
		# TAG: sys_logger.LogFormat
		# Format: sys_logger.LogFormat LOGFORMAT
		# Description:
		#	The log format to use. If no log format defined then
		#	the following will be used:
		#	    "%la %a %im %iu %is"
		# Default:
		#	None set
		# Example:
		#	Logformat BasicFormat "%la %a %im %iu %is"
		#	sys_logger.LogFormat BasicFormat
		$conf[]="sys_logger.LogFormat \"%tl;%a;%un;%iu;%is;%huo\"";
		# TAG: sys_logger.access
		# Format: sys_logger.access [!]acl1 ...
		# Description:
		#	Allow selecting ICAP requests to be logged using acls.
		#	By default all requests will be logged.
		# Default:
		#	None set
		# Example:
		$conf[]="acl URLCHECK service url_check";
		$conf[]="acl URLBLOCKED icap_resp_header{X-Response-Info} /BLOCKED/";
		$conf[]="sys_logger.access URLCHECK URLBLOCKED";
		$conf[]="sys_logger.access all";
		
		$conf[]="# End module: sys_logger";
		$conf[]="";
		return @implode("\n", $conf);	
		
	}
	
	
	public function buildconf(){
			$this->BuildDefaults();
			include_once(dirname(__FILE__))."/class.c-icap.virus_scan.inc";
			$sock=new sockets();
			$CICAPEnableSquidGuard=intval($sock->GET_INFO("CICAPEnableSquidGuard"));
			
			$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
			$CICAPListenAddress=$sock->GET_INFO("CICAPListenAddress");
			if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}
			if($CICAPListenAddress==null){$CICAPListenAddress="127.0.0.1";}
			
			if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
			if(!isset($GLOBALS["CLASS_USERS"])){$GLOBALS["CLASS_USERS"]=new usersMenus();}
			$users=$GLOBALS["CLASS_USERS"];
			$user="squid";
			$modules_path=$this->LOCATE_MODULES_DIR();
			if(!$users->KASPERSKY_WEB_APPLIANCE){
				echo "Starting......: ".date("H:i:s")." c-icap using modules path in $modules_path\n";
				if(is_file("$modules_path/srv_clamav.so")){
					echo "Starting......: ".date("H:i:s")." c-icap using ClamAV antivirus\n";
					$user=$this->CLAMAV_USER();
				
				}
			}
			
			if(is_dir($GLOBALS["guarddb"])){shell_exec("/bin/chown -R $user:$user {$GLOBALS["guarddb"]}/*");}
			if(trim($usermenus->hostname==null)){
				if($GLOBALS["AS_ROOT"]){
					if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}$unix=new unix();
					$usermenus->hostname=$unix->hostname_g();
				}
			}
			
			
			if($GLOBALS["AS_ROOT"]){
				$NETWORK_ALL_INTERFACES=$GLOBALS["CLASS_UNIX"]->NETWORK_ALL_INTERFACES(true);
				if(!isset($NETWORK_ALL_INTERFACES[$CICAPListenAddress])){$CICAPListenAddress="127.0.0.1";}
			}
			
			echo "Starting......: ".date("H:i:s")." c-icap using User $user ($usermenus->hostname)\n";
			
			
			
			
			$conf[]="PidFile /var/run/c-icap/c-icap.pid";
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
			$conf[]="ListenAddress $CICAPListenAddress";
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
			
			$conf[]="RemoteProxyUsers on";
			$conf[]="RemoteProxyUserHeader X-Authenticated-User";
			$conf[]="RemoteProxyUserHeaderEncoded on";
			$conf[]="LogFormat allFormat \"%tl;%a;%un;%iu;%is;%huo\"";
			
			if(is_file("/etc/c-icap-perso.conf")){
				$conf[]="Include /etc/c-icap-perso.conf";
			}
			
			if($CICAPEnableSquidGuard==1){
				$conf[]="# Web Filtering is enabled";
				$conf[]="Include /etc/srv_url_check.conf";
			}else{
				$conf[]="# Web Filtering is disabled";
			}
			
			$clamd=new cicap_av($modules_path);
			
			$conf[]=$clamd->build();
			$conf[]="";
			
			$conf[]="acl all src 0.0.0.0/0.0.0.0";
			$conf[]="icap_access allow all";
			$conf[]="";
			$conf[]=$this->syslogger();
			
			if($this->enable_metascanner==1){
				$conf[]="Service khse /opt/kaspersky/khse/libexec/libms-icap.so";
			}			

				
		$conf[]="";
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
				
				echo "Starting......: ".date("H:i:s")." c-icap apply securities on user \"$user\" done\n";
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