<?php
include_once(dirname(__FILE__).'/class.dansguardian.inc');
include_once(dirname(__FILE__).'/class.kav4proxy.inc');
include_once(dirname(__FILE__).'/class.sockets.inc');
include_once(dirname(__FILE__)."/class.c-icap-filter.inc");	




class icap{
	var $ASROOT=false;
	var $IS_30=false;
	var $IS_31=false;
	var $IS_32=false;
	var $ICAP_SERVICES_COUNT=0;
	var $enable_kavproxy=0;
	var $enable_cicap=0;
	var $enable_squidguard=0;
	var $EnableClamavInCiCap=1;
	var $icap_final_string;
	var $EnableSquidGuardInCiCAP=0;
	var $EnableKavICAPRemote=0;
	var $KavICAPRemoteAddr=null;
	var $KavICAPRemotePort=0;	
	var $enable_metascanner=0;
	var $EnableRemoteStatisticsAppliance=0;
	var $RemoteApplianceServer=null;
	var $CicapEnabled=0;
	var $IcapServerAddress="127.0.0.1";
	
	function icap(){
		$users=new usersMenus();
		$ini=new Bs_IniHandler();
		$sock=new sockets();
		$SQUID_ICAP_ENABLED=$users->SQUID_ICAP_ENABLED;
		$KAV4PROXY_INSTALLED=$users->KAV4PROXY_INSTALLED;
		$ArticaSquidParameters=$sock->GET_INFO('ArticaSquidParameters');
		$ini->loadString($ArticaSquidParameters);
		$this->enable_cicap=$sock->GET_INFO('CicapEnabled');
		if(!is_numeric($this->enable_cicap)){$this->enable_cicap=0;}
		$this->enable_kavproxy=$sock->GET_INFO('kavicapserverEnabled');
		$this->enable_squidguard=$sock->GET_INFO('squidGuardEnabled');
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO('EnableRemoteStatisticsAppliance');
		
		$this->EnableClamavInCiCap=$sock->GET_INFO("EnableClamavInCiCap");
		$this->EnableSquidGuardInCiCAP=$sock->GET_INFO("EnableSquidGuardInCiCAP");
		$this->EnableKavICAPRemote=$sock->GET_INFO("EnableKavICAPRemote");
		$this->KavICAPRemoteAddr=$sock->GET_INFO("KavICAPRemoteAddr");
		$this->KavICAPRemotePort=$sock->GET_INFO("KavICAPRemotePort");
		$this->enable_metascanner=$sock->GET_INFO("KavMetascannerEnable");
		
		if($this->KavICAPRemoteAddr==null){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->KavICAPRemotePort)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->enable_kavproxy)){$this->enable_kavproxy=0;}
		if(!is_numeric($this->EnableKavICAPRemote)){$this->EnableKavICAPRemote=0;}
		if(!is_numeric($this->enable_metascanner)){$this->enable_metascanner=0;}			
		if(!is_numeric($this->EnableClamavInCiCap)){$this->EnableClamavInCiCap=1;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->EnableSquidGuardInCiCAP)){$this->EnableSquidGuardInCiCAP=0;$sock->SET_INFO("EnableSquidGuardInCiCAP","0");}
		
		
		
		echo "Starting......: [ICAP]: ClamAV in ICAP:$this->EnableClamavInCiCap\n";
		echo "Starting......: [ICAP]: squidGuard in ICAP:$this->EnableSquidGuardInCiCAP\n";
		echo "Starting......: [ICAP]: Remote Kaspersky ICAP Server:$this->EnableKavICAPRemote ($this->KavICAPRemoteAddr:$this->KavICAPRemotePort)\n";  
		
		if(posix_getuid()==0){$this->ASROOT=true;}
		if(isset($GLOBALS["AS_ROOT"])){if($GLOBALS["AS_ROOT"]){$this->ASROOT=true;}}
		
		
		if(preg_match('#^([0-9]+)\.([0-9]+)#',$users->SQUID_VERSION,$re)){
		if($re[1]==3){
			$this->IS_30=true;
			if($re[2]==1){$this->IS_31=true;}
			if($re[2]>=2){$this->IS_32=true;$this->IS_31=true;}
			}
		}
		
		if(!$users->KAV4PROXY_INSTALLED){$this->enable_kavproxy=0;}
		if(!$users->C_ICAP_INSTALLED){$this->enable_cicap=0;}
		if(!$users->SQUIDGUARD_INSTALLED){$this->EnableSquidGuardInCiCAP=0;}
		if($this->EnableKavICAPRemote==1){$this->enable_kavproxy=1;}
		
		if($this->EnableRemoteStatisticsAppliance==1){
			$this->CicapEnabled=$sock->GET_INFO("CicapEnabled");
			if(!is_numeric($this->CicapEnabled)){$this->CicapEnabled=0;}
			if($this->CicapEnabled==1){$this->enable_cicap=1;}
			$RemoteStatisticsApplianceSettings=unserialize(base64_decode($sock->GET_INFO("RemoteStatisticsApplianceSettings")));
			$this->RemoteApplianceServer=$RemoteStatisticsApplianceSettings["SERVER"];
			$this->IcapServerAddress=$this->RemoteApplianceServer;
			if($this->ASROOT){echo "Starting......: [ICAP]: Using remote appliance {$this->RemoteApplianceServer} as Web Antivirus engine\n";}
		}			
		
		
		$this->buildCicapConfig();
		
		
	}
	
	private function buildCicapConfig(){
		if(!$this->IS_30){return null;}
		if(!$this->IS_31){
			if($this->ASROOT){echo "Starting......: [ICAP]: building for 3.0.x - > IS_31 = FALSE\n";}
			writelogs("building for 3.0.x - > IS_31 = FALSE",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$icap[]=$this->C_ICAP_30();
			$icap[]=$this->KAV_ICAP_30();
			$icap[]=$this->ICAP_HEADERS();
			$icap[]="";
			$icap[]="";
			$this->icap_final_string=@implode("\n\n",$icap);
			return;
			}

		if($this->ASROOT){echo "Starting......: [ICAP]: building for 3.1.x\n";}
		$icap[]=$this->C_ICAP_31();
		$icap[]=$this->KAV_ICAP_31();
		$icap[]=$this->ADAPTATION_31();	
		$icap[]=$this->ICAP_HEADERS();
		$icap[]="";
		$icap[]="";
		$this->icap_final_string=@implode("\n\n",$icap);	
		
	}
	

	
	private function C_ICAP_30(){
		if($this->enable_cicap<>1){return null;}
		if($this->enable_kavproxy==1){
			$this->enable_cicap=0;
			return null;
		}
		$users=new usersMenus();
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
		if($this->ASROOT){echo "Starting......: Squid activating C-ICAP 3.0.x\n";}
		
		if($this->enable_metascanner==1){$this->EnableClamavInCiCap=0;}
		
		$kav_rules[]="# --------- icap_service C-ICAP (mode 3.0.x)";
		
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_service\tservice_req reqmod_precache O icap://$this->IcapServerAddress:1345/srv_url_check";}
		if(!$users->KASPERSKY_WEB_APPLIANCE){
			if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_service\tservice_avi respmod_precache 1 icap://$this->IcapServerAddress:1345/srv_clamav";}
		}
		if($this->enable_metascanner==1){$kav_rules[]="icap_service\tservice_khse respmod_precache 1 icap://$this->IcapServerAddress:1345/khse";}		
		
		
		$kav_rules[]="";
		if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_class\tclass_antivirus service_avi";}
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_class\tclass_antivirus_req service_req";}	
		if($this->EnableClamavInCiCap==1){$kav_rules[]="icap_access\tclass_antivirus allow all";}
		if($this->EnableSquidGuardInCiCAP==1){$kav_rules[]="icap_access\tclass_antivirus_req allow all";}
		
		if($this->enable_metascanner==1){
			$kav_rules[]="icap_class\tclass_khse service_khse";
			$kav_rules[]="icap_access\tclass_khse allow all";
		}
		
		$kav_rules[]="";
		return implode("\n",$kav_rules);
	}
	
		private function ICAP_HEADERS($kaspersky=0){
			if($this->ICAP_SERVICES_COUNT==0){return;}
			$conf[]="icap_enable on";
			$conf[]="icap_preview_size 128";
			$conf[]="icap_service_failure_limit -1";
			$conf[]="icap_preview_enable on";
			$conf[]="icap_send_client_ip on";
			$conf[]="icap_send_client_username on";
			$conf[]="icap_client_username_header X-Authenticated-User";
			$conf[]="icap_client_username_encode on";
			return implode("\n",$conf);
			}	
	
	private function KAV_ICAP_30(){
		if($this->enable_kavproxy<>1){
				if($this->ASROOT){echo "Starting......: Squid Kaspersky ICAP is disabled\n";}
				return;
			}
		
		$IpAddrr=$this->IcapServerAddress;$port=1344;
		if($this->EnableKavICAPRemote==1){$IpAddrr=$this->KavICAPRemoteAddr;$port=$this->KavICAPRemotePort;}
		if($this->EnableKavICAPRemote==0){$kav=new Kav4Proxy();if(preg_match("#(.+?):([0-9]+)#", $kav->main_array["ListenAddress"],$re)){$IpAddrr=$re[1];$port=$re[2];}}
		
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;			
		if($this->ASROOT){echo "Starting......: Squid activating Kaspersky ICAP Mode\n";}
		$kav_rules[]="# --------- icap_service KASPERSKY mode 3.0.x";
		$kav_rules[]="";
		$kav_rules[]="icap_service\tis_kav_resp respmod_precache 0 icap://$IpAddrr:$port/av/respmod";
		$kav_rules[]="icap_service\tis_kav_req reqmod_precache 0 icap://$IpAddrr:$port/av/reqmod";
		$kav_rules[]="";
		$kav_rules[]="# --------- icap_class";
		$kav_rules[]="icap_class\tic_kav_resp is_kav_resp";
		$kav_rules[]="icap_class\tic_kav_req is_kav_req"; 
		$kav_rules[]="";
		$kav_rules[]="# --------- icap_access";
		$kav_rules[]="icap_access\tic_kav_resp allow all";
		$kav_rules[]="icap_access\tic_kav_req allow all !acl_kav_GET";			
		$kav_rules[]="";	
		return implode("\n",$kav_rules);	
	}
	
	
	private function ADAPTATION_31(){
		if($this->ICAP_SERVICES_COUNT<2){
			$r[]=$this->C_ICAP_ADAPTATION();
			$r[]=$this->KAV_ADAPTATION();
			return @implode("\n",$r);
		}
		
		return $this->ICAP_CHAINS();
		
		
	}
	
	private function C_ICAP_ADAPTATION(){
		if($this->enable_cicap<>1){return null;}
		$sock=new sockets();
		$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}		
		$kav_rules[]="# --------- adaptation for C-ICAP service";
		$kav_rules[]="";
		$class_url_check=FALSE;
		
		if($this->enable_squidguard==1){	
			if($this->EnableSquidGuardInCiCAP==1){	
				$class_url_check=true;
				$kav_rules[]="adaptation_service_set  class_url_check  service_url_check";
				$kav_rules[]="adaptation_access  class_url_check  allow all";
			}
		}
		
		if($this->C_ICAP_31_icap_service()){
				$kav_rules[]="adaptation_service_set class_antivirus service_antivir";
				if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP, ". count(count($GLOBALS["WHITELISTED_MIMETYPE"]))." exclusion(s)\n";}
				if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){
					$kav_rules[]="adaptation_access class_antivirus deny StreamMimeWhite";
				}				
				$kav_rules[]="adaptation_access class_antivirus allow all";
			
		}else{
			if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP, adaptation_service, skipping\n";}
		}

		if(!$class_url_check){
			if($DisableCicapDNBSL==0){
				$kav_rules[]="adaptation_service_set  class_url_check  service_url_check";
				$kav_rules[]="adaptation_access  class_url_check  allow all";				
			}
			
		}
		
		
		return @implode("\n",$kav_rules);	
	}
	
	private function KAV_ADAPTATION(){
		if($this->enable_kavproxy<>1){return null;}
		$kav_rules[]="# --------- adaptation For Kaspersky Antivirus";
		$kav_rules[]="";
		$kav_rules[]="adaptation_service_set class_antivirus_kav_resp is_kav_resp";
		$kav_rules[]="adaptation_service_set class_antivirus_kav_req is_kav_req";	
		//$kav_rules[]="adaptation_access class_antivirus_kav_req deny MULTIMEDIA";
		
			$acls=new squid_acls_groups();
			$acls_deny=$acls->buildacls_bytype("adaptation_access_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){
					$kav_rules[]="adaptation_access class_antivirus_kav_req deny $line";
					$kav_rules[]="adaptation_access class_antivirus_kav_resp deny $line";
				}
			}		
		
		$kav_rules[]="adaptation_access class_antivirus_kav_req allow all";
		$kav_rules[]="adaptation_access class_antivirus_kav_resp allow all";
		$kav_rules[]="";			
		return @implode("\n",$kav_rules);	
	}	
	
	
	private function ICAP_CHAINS(){
		$f[]="# --------- adaptation chain services.";
		$class_url_check=FALSE;
		$kav_rules[]="";
		if($this->enable_cicap){
			$sock=new sockets();
			$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
			if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=0;}	
			
			if($this->enable_metascanner==1){$a[]="khse";}			
			if($this->EnableClamavInCiCap==1){$a[]="service_antivir";}
			
			if($this->enable_squidguard==1){
				if($this->EnableSquidGuardInCiCAP==1){
					$class_url_check=true;
					$b[]="service_url_check";
				}
			}
			

			
			if(!$class_url_check){if($DisableCicapDNBSL==0){$b[]="service_url_check";}}
	
		}
			
		if($this->enable_kavproxy){$a[]="is_kav_resp";$b[]="is_kav_req";}
		if(count($a)>0){
			$f[]="adaptation_service_chain ANTIVIRUS_CHAINS ".@implode(" ",$a);
			//$f[]="adaptation_access ANTIVIRUS_CHAINS deny MULTIMEDIA";
			if(isset($GLOBALS["ICAP"]["WHITEAUTH"])){
				if($GLOBALS["ICAP"]["WHITEAUTH"]<>null){
					$f[]=$GLOBALS["ICAP"]["WHITEAUTH"];
				}
			}
			
			if($this->ASROOT){echo "Starting......: [ICAP]: ".count($GLOBALS["WHITELISTED_MIMETYPE"])." exclusion(s)\n";}
			
			if(count($GLOBALS["WHITELISTED_MIMETYPE"])>0){
				$f[]="adaptation_access ANTIVIRUS_CHAINS deny StreamMimeWhite";
			}

			$acls=new squid_acls_groups();
			$acls_deny=$acls->buildacls_bytype("adaptation_access_deny");
			if(count($acls_deny)>0){
				while (list ($index, $line) = each ($acls_deny) ){$f[]="adaptation_access ANTIVIRUS_CHAINS deny $line";}
			}			
			
			
			$f[]="adaptation_access ANTIVIRUS_CHAINS allow all";
		}
		
		if(is_array($b)){
		 if(count($b)>0){
				$f[]="";
				$f[]="adaptation_service_chain REQ_CHAINS ".@implode(" ",$b);
				$f[]="adaptation_access REQ_CHAINS allow all";
			}
		}
		return @implode("\n",$f);	
	}
	
	private function C_ICAP_31_icap_service(){
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if($this->EnableRemoteStatisticsAppliance==0){
			if($this->enable_metascanner==1){
				if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP Metascanner is enabled, skipping\n";}
				return;}
			if($this->EnableClamavInCiCap==0){
				if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP ClamAV is disabled inside, skipping\n";}
				return;}
			if(!$this->C_ICAP_31_is_scanner_exists()){
				if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP, did not find any scanner, skipping\n";}
				return;
			}
		}
		if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP OK all parameters done...\n";}
		return true;
		
	}
	
	
	private function C_ICAP_31_is_scanner_exists(){
		$modules_path=$this->LOCATE_C_ICAP_MODULES_DIR();
		$result=false;
		if(!is_dir($modules_path)){
			if($this->ASROOT){echo "Starting......: [ICAP]: library path not found `$modules_path`\n";}
			return false;
		}
		if(is_file("$modules_path/srv_clamav.so")){$result=true;}
		if($this->ASROOT){echo "Starting......: [ICAP]: $modules_path/srv_clamav.so no such file\n";}
		if(is_file("$modules_path/virus_scan.so")){$result=true;}
		if($this->ASROOT){echo "Starting......: [ICAP]: $modules_path/virus_scan.so no such file\n";}
		if(!$result){if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP Failed, no library found in `$modules_path`\n";}}
		return $result;
	}

	
	private function C_ICAP_31(){
		$users=new usersMenus();
		$class_url_check=FALSE;
		if($this->enable_cicap==0){
			if($this->ASROOT){echo "Starting......: [ICAP]: C-ICAP disabled...\n";}
			return null;
		}
		$sock=new sockets();
		$DisableCicapDNBSL=$sock->GET_INFO("DisableCicapDNBSL");
		if(!is_numeric($DisableCicapDNBSL)){$DisableCicapDNBSL=1;}			
		
		if($this->ASROOT){echo "Starting......: [ICAP]: Activating C-ICAP\n";}
		$kav_rules[]="# --------- icap_service C-ICAP mode 3.1.x";
		

		if($this->enable_squidguard==1){
			if($this->EnableSquidGuardInCiCAP==1){
				$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
				$class_url_check=true;
				$kav_rules[]="# --------- icap_service C-ICAP + SquidGuard";
				$kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";
				}
			}
			
			
		if(!$class_url_check){
			if($DisableCicapDNBSL==0){
				$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
				$kav_rules[]="# --------- icap_service C-ICAP + DNSBL";
				$kav_rules[]="icap_service\tservice_url_check reqmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/url_check";				
			}
		}

		if($this->EnableClamavInCiCap==0){
			if($this->ASROOT){echo "Starting......: [ICAP]: EnableClamavInCiCap is disabled\n";}
		}
		
		
		
		if($this->C_ICAP_31_icap_service()){
			$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
			$kav_rules[]="icap_service  service_antivir respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/srv_clamav";
		}

			
			
	   if($this->enable_metascanner==1){
	   		if(is_file("/opt/kaspersky/khse/libexec/libms-icap.so")){
	   			$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
	   			$kav_rules[]="icap_service  khse respmod_precache routing=on bypass=on icap://$this->IcapServerAddress:1345/khse";
	   		}
	   }		
		return implode("\n",$kav_rules);		
	}
			

		
	private function KAV_ICAP_31(){
		if($this->enable_kavproxy<>1){
				if($this->ASROOT){echo "Starting......: Squid Kaspersky ICAP is disabled\n";}
				return;
			}		
		$this->ICAP_SERVICES_COUNT=$this->ICAP_SERVICES_COUNT+1;
		if($this->ASROOT){echo "Starting......: Squid adaptation enabled\n";}
		
		$IpAddrr=$this->IcapServerAddress;$port=1344;
		if($this->EnableKavICAPRemote==1){$IpAddrr=$this->KavICAPRemoteAddr;$port=$this->KavICAPRemotePort;}
		if($this->EnableKavICAPRemote==0){$kav=new Kav4Proxy();if(preg_match("#(.+?):([0-9]+)#", $kav->main_array["ListenAddress"],$re)){$IpAddrr=$re[1];$port=$re[2];}}
		
		$kav_rules[]="# --------- icap_service KASPERSKY mode 3.1.1";
		$kav_rules[]="";
		$kav_rules[]="icap_service\tis_kav_resp respmod_precache routing=on bypass=on icap://$IpAddrr:$port/av/respmod";
		$kav_rules[]="icap_service\tis_kav_req reqmod_precache routing=on bypass=on icap://$IpAddrr:$port/av/reqmod";

		return implode("\n",$kav_rules);	
	}

	public function LOCATE_C_ICAP_MODULES_DIR(){
		$f[]="virus_scan.so";
		$f[]="sys_logger.so";
		$f[]="srv_echo.so";
		
		$possibleDirs[]="/usr/lib/c_icap";
		$possibleDirs[]="/var/lib/c_icap";
		
		while (list ($index, $dir) = each ($possibleDirs) ){
			
			reset($f);
			while (list ($a, $filename) = each ($f) ){
				if(is_file("$dir/$filename")){return $dir;}
				if($GLOBALS["VERBOSE"]){
					if($this->ASROOT){echo "Starting......: [ICAP]: $dir/$filename no such file\n";}
				}
			}
		}
		
		if($this->ASROOT){echo "Starting......: [ICAP]: Warning, return default C-ICAP folder `/usr/lib/c_icap`\n";}
		return "/usr/lib/c_icap";
	}	
	
	
	
}
?>