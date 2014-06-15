<?php
include_once(dirname(__FILE__)."/class.mysql.inc");
include_once(dirname(__FILE__)."/class.groups.inc");
include_once(dirname(__FILE__)."/class.compile.ufdbguard.expressions.inc");
include_once(dirname(__FILE__)."/class.ActiveDirectory.inc");
include_once(dirname(__FILE__)."/class.squid.inc");

class compile_ufdbguard{
	var $SquidGuardIPWeb;
	var $SquidGuardIPWebSSL;
	var $EnableRemoteStatisticsAppliance=0;
	var $EnableWebProxyStatsAppliance=0;
	var $SquidGuardWebFollowExtensions=1;
	var $UfdbGuardRedirectCategories=array();
	var $DansGuardianDefaultMainRule=array();
	var $AVAILABLE_REWRITE_RULES=array();
	var $BANNED_FILES_RULES=array();
	var $BANNED_EXTDOMS_RULES=array();
	var $AVAILABLE_REGEX_RULES=array();
	var $UpdateUtilityRedirectEnable=0;
	var $SquidDatabasesArticaEnable=0;
	var $SquidGuardWebUseExternalUri=0;
	var $SquidGuardWebExternalUri=null;
	var $SquidGuardWebExternalUriSSL=null;
	var $UseDynamicGroupsAcls=0;
	var $KerbAuthDisableNsswitch=0;
	var $licensed=0;
	var $Ufdbguard_Version=null;
	var $Notrefreshdomainlist=0;
	var $DisableExpressionList=1;
	var $NoMalwareUris=1;
	var $Is31=false;
	
	
	function compile_ufdbguard(){
		if(!isset($GLOBALS["OUTPUT"])){$GLOBALS["OUTPUT"]=false;}
		$GLOBALS["SRC"]=array();
		$sock=new sockets();
		$usrs=new usersMenus();
		$MAIN_INTERFACE=null;
		if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
		if($GLOBALS["AS_ROOT"]){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		
		$this->SquidGuardIPWeb=$sock->GET_INFO("SquidGuardIPWeb");
		$this->EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		$this->EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
		$this->SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
		$this->SquidDatabasesArticaEnable=$sock->GET_INFO("SquidDatabasesArticaEnable");
		$this->SquidGuardWebUseExternalUri=$sock->GET_INFO("SquidGuardWebUseExternalUri");
		$this->SquidGuardWebExternalUri=$sock->GET_INFO("SquidGuardWebExternalUri");
		$this->SquidGuardWebExternalUriSSL=$sock->GET_INFO("SquidGuardWebExternalUriSSL");
		$this->KerbAuthDisableNsswitch=$sock->GET_INFO("KerbAuthDisableNsswitch");

		$this->SquidGuardWebFollowExtensions=$sock->GET_INFO("SquidGuardWebFollowExtensions");
		$this->UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
		$this->UfdbGuardRedirectCategories=unserialize(base64_decode($sock->GET_INFO("UfdbGuardRedirectCategories")));
		$this->DansGuardianDefaultMainRule=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		
		$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		if(!isset($ufdbguardConfig["DisableExpressionList"])){$ufdbguardConfig["DisableExpressionList"]=1;}
		if(!is_numeric($ufdbguardConfig["NoMalwareUris"])){$ufdbguardConfig["NoMalwareUris"]=1;}
		if(!is_numeric($ufdbguardConfig["DisableExpressionList"])){$ufdbguardConfig["DisableExpressionList"]=1;}
		$this->Notrefreshdomainlist=$ufdbguardConfig["Notrefreshdomainlist"];
		$this->DisableExpressionList=$ufdbguardConfig["DisableExpressionList"];
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[CNF] DisableExpressionList:`{$ufdbguardConfig["DisableExpressionList"]}`\n";
		
		$this->UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=0;}		
		
		if(!is_numeric($this->SquidGuardWebUseExternalUri)){$this->SquidGuardWebUseExternalUri=0;}
		if(!is_numeric($this->UpdateUtilityRedirectEnable)){$this->UpdateUtilityRedirectEnable=0;}
		if(!is_numeric($this->EnableRemoteStatisticsAppliance)){$this->EnableRemoteStatisticsAppliance=0;}
		if(!is_numeric($this->SquidDatabasesUtlseEnable)){$this->SquidDatabasesUtlseEnable=1;}
		if(!is_numeric($this->EnableWebProxyStatsAppliance)){$this->EnableWebProxyStatsAppliance=0;}
		if(!is_numeric($this->SquidGuardWebFollowExtensions)){$this->SquidGuardWebFollowExtensions=1;}
		if(!is_numeric($this->SquidDatabasesArticaEnable)){$this->SquidDatabasesArticaEnable=1;}
		if(!is_numeric($this->KerbAuthDisableNsswitch)){$this->KerbAuthDisableNsswitch=1;}
		
		$this->Check_SquidGuardIPWeb();
		
		if($this->SquidGuardWebUseExternalUri==1){
				if(strlen($this->SquidGuardWebExternalUri)>3){
						$this->SquidGuardIPWeb=$this->SquidGuardWebExternalUri;
				}
				if(strlen($this->SquidGuardWebExternalUriSSL)>3){
					$this->SquidGuardIPWebSSL=$this->SquidGuardWebExternalUriSSL;
				}				
				
		}
		
		
		
		if(@file_get_contents("/usr/local/share/artica/.lic")=="TRUE"){$this->licensed=1;}else{$this->SquidDatabasesArticaEnable=0;}
		
	
		$this->Ufdbguard_Version=$this->ufdbguardVersion();
		echo "Starting......: ".date("H:i:s")." ufdbGuard version: $this->Ufdbguard_Version\n";
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $this->Ufdbguard_Version,$re)){
			if($re[1]>0){
				if($re[2]>30){
					$this->Is31=true;
				}
			}
		}
		
	}
	

	private function Check_SquidGuardIPWeb(){
		if($this->SquidGuardWebUseExternalUri==1){return;}
		include_once(dirname(__FILE__)."/class.tcpip.inc");
		$ipClass=new IP();
		$IPS=array();
		$MAIN_INTERFACE=null;
		
		if($GLOBALS["AS_ROOT"]){
			if(class_exists("unix")){
				$unix=new unix();
				$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
			
			while (list ($interface, $xmain) = each ($NETWORK_ALL_INTERFACES) ){
				if(!isset($xmain["IPADDR"])){
					unset($NETWORK_ALL_INTERFACES[$interface]);
					continue;}
				if($xmain["IPADDR"]=="0.0.0.0"){
					unset($NETWORK_ALL_INTERFACES[$interface]);
					continue;}
				echo "Starting......: ".date("H:i:s")." ufdbGuard IP available {$xmain["IPADDR"]} for $interface\n";
				$IPS[$xmain["IPADDR"]]=$interface;
				
			}
			
				if(isset($NETWORK_ALL_INTERFACES["eth0"]["IPADDR"])){$MAIN_INTERFACE=$NETWORK_ALL_INTERFACES["eth0"]["IPADDR"];}
				if($MAIN_INTERFACE==null){if(isset($NETWORK_ALL_INTERFACES["br1"]["IPADDR"])){$MAIN_INTERFACE=$NETWORK_ALL_INTERFACES["br1"]["IPADDR"];} }
				if($MAIN_INTERFACE==null){if(isset($NETWORK_ALL_INTERFACES["eth1"]["IPADDR"])){$MAIN_INTERFACE=$NETWORK_ALL_INTERFACES["eth1"]["IPADDR"];} }
			}
		}
		
		
		
		if($this->SquidGuardIPWeb==null){
			$this->SquidGuardIPWeb="http://$MAIN_INTERFACE:9020/exec.squidguard.php";
		}
		if($this->SquidGuardIPWebSSL==null){
			$this->SquidGuardIPWebSSL="https://$MAIN_INTERFACE:9025/exec.squidguard.php";
		}
		
		
		$arrayURI=parse_url($this->SquidGuardIPWeb);
		$hostname=$arrayURI["host"];
		if(preg_match("#(.*?):([0-9]+)#", $hostname,$re)){
			$hostname=$re[1];
		}
		echo "Starting......: ".date("H:i:s")." ufdbGuard SquidGuardIPWeb = `$this->SquidGuardIPWeb` hostname=`$hostname`\n";
		if(!$ipClass->isValid($hostname)){ 
			echo "Starting......: ".date("H:i:s")." ufdbGuard hostname=`$hostname` INVALID\n"; 
			$hostname=null;
			if($MAIN_INTERFACE<>null){
				$this->SquidGuardIPWeb="http://$MAIN_INTERFACE:9020/exec.squidguard.php";
				$this->SquidGuardIPWebSSL="https://$MAIN_INTERFACE:9025/exec.squidguard.php";
				return;
			}
			
		}
		
		if(count($IPS)>0){
			if(!isset($IPS[$hostname])){
				if($MAIN_INTERFACE<>null){
					echo "Starting......: ".date("H:i:s")." ufdbGuard IPADDR `$hostname` Not found !\n";
					$this->SquidGuardIPWeb="http://$MAIN_INTERFACE:9020/exec.squidguard.php";
					$this->SquidGuardIPWebSSL="https://$MAIN_INTERFACE:9025/exec.squidguard.php";
					return;
				}
			}
		}
		
		
	}
	
	private function ufdbguardVersion(){
		$unix=new unix();
		$ufdbguardd=$unix->find_program("ufdbguardd");
		exec("$ufdbguardd -v 2>&1",$results);
		while (list ($num, $line) = each ($results)){
			if(preg_match("#ufdbguardd:\s+([0-9\.]+)#", $line,$re)){return $re[1];}
		}
	}
	
	
	private function BuildCertificates(){
		if(isset($GLOBALS["BuildCertificatesF"])){return;}
		$GLOBALS["BuildCertificatesF"]=true;
		$q=new mysql_squid_builder();
		echo "Starting......: ".date("H:i:s")." ufdbGuard building certificates\n";
		if(!$q->TABLE_EXISTS("webfilter_certs")){$q->CheckTables();}
		if($q->COUNT_ROWS("webfilter_certs")==0){$q->fill_webfilter_certs();}
		$sql="SELECT * FROM webfilter_certs";
		$results=$q->QUERY_SQL($sql);
		$mainArray=array();
		$f=array();
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard ". mysql_num_rows($results)." SSL certificates\n";
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["certname"]=="Default"){
				$f[]=base64_decode($ligne["certdata"]);
				continue;
			}
			
			$f[]="-----BEGIN CERTIFICATE-----".base64_decode($ligne["certdata"])."-----END CERTIFICATE-----";
			
		}		
		$f[]="";
		if(is_dir("/var/lib/squidguard/security/cacerts")){@rmdir("/var/lib/squidguard/security/cacerts");}
		@mkdir("/var/lib/squidguard/security",0755,true);
		@mkdir("/var/lib/squidguard/security/none",0755,true);
		@chmod("/var/lib/squidguard/security",0755);
		@chown("squid","/var/lib/squidguard/security");
		@chown("squid","/var/lib/squidguard/security/none");
		@file_put_contents("/var/lib/squidguard/security/cacerts", @implode("\n", $f));
		
	}
	
	
	public function buildConfig(){
		$sock=new sockets();
		$sources=$this->build_sources();
		$rewrite=$this->build_rewrites();
		$times=$this->build_times();
		$categories=$this->build_categories();
		$bannedFiles=$this->BuildBannedFiles();
		$BannexExtDomains=$this->BuildBannedDomains();
		$build_expressions_list=$this->build_expressions_list();
		$acls=$this->build_acls();
		$EnableYoutubeForSchools=$sock->GET_INFO("EnableYoutubeForSchools");
		$EnableGoogleSafeSearch=$sock->GET_INFO("EnableGoogleSafeSearch");
		$YoutubeForSchoolsID=$sock->GET_INFO("YoutubeForSchoolsID");
		$UfdbForceSquidVersion=$sock->GET_INFO("UfdbForceSquidVersion");
		if(!is_numeric($EnableYoutubeForSchools)){$EnableYoutubeForSchools=0;}
		if(!is_numeric($EnableGoogleSafeSearch)){$EnableGoogleSafeSearch=1;}		
		$unix=new unix();
		$major=$unix->UFDBGUARDD_MAJOR();
		$minor=$unix->UFDBGUARDD_MINOR();
		$squidversion=$unix->squid_version();	
		if(!preg_match("#^([0-9]+)\.([0-9]+)#", $UfdbForceSquidVersion)){$UfdbForceSquidVersion=null;}
		if($UfdbForceSquidVersion<>null){$squidversion=$UfdbForceSquidVersion;}
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $squidversion,$re)){$squidversion=$re[1].".".$re[2];}		
		
		if($this->SquidGuardWebUseExternalUri==0){
			if(!preg_match("#squidguard\.php#",$this->SquidGuardIPWeb)){$this->SquidGuardIPWeb=$this->SquidGuardIPWeb."/exec.squidguard.php?";}
			if(!preg_match("#squidguard\.php#",$this->SquidGuardIPWebSSL)){$this->SquidGuardIPWebSSL=$this->SquidGuardIPWebSSL."/exec.squidguard.php?";}
		}
		
		if($this->SquidGuardWebUseExternalUri==1){
			if(strlen($this->SquidGuardWebExternalUri)>3){
				$this->SquidGuardIPWeb=$this->SquidGuardWebExternalUri;
			}
			if(strlen($this->SquidGuardWebExternalUriSSL)>3){
				$this->SquidGuardIPWebSSL=$this->SquidGuardWebExternalUriSSL;
			}
		
		}
		
		$conf[]="# Artica builder on " .date('Y-m-d H:i:s');
		$conf[]="dbhome /var/lib/squidguard";
		$conf[]="logdir /var/log/squid";
		
		$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		$datas=$ufdbguardConfig;
		
		if(!isset($ufdbguardConfig["enforce-https-with-hostname"])){$ufdbguardConfig["enforce-https-with-hostname"]=0;}
		if(!isset($ufdbguardConfig["enforce-https-official-certificate"])){$ufdbguardConfig["enforce-https-official-certificate"]=0;}
		if(!isset($ufdbguardConfig["https-prohibit-insecure-sslv2"])){$ufdbguardConfig["https-prohibit-insecure-sslv2"]=0;}
		if(!isset($ufdbguardConfig["url-lookup-result-during-database-reload"])){$ufdbguardConfig["url-lookup-result-during-database-reload"]=1;}
		if(!isset($ufdbguardConfig["url-lookup-result-when-fatal-error"])){$ufdbguardConfig["url-lookup-result-when-fatal-error"]=1;}
		if(!isset($ufdbguardConfig["check-proxy-tunnel"])){$ufdbguardConfig["check-proxy-tunnel"]=1;}
		if(!isset($ufdbguardConfig["strip-domain-from-username"])){$ufdbguardConfig["strip-domain-from-username"]=0;}
		if(!isset($ufdbguardConfig["refreshuserlist"])){$ufdbguardConfig["refreshuserlist"]=15;}
		if(!isset($ufdbguardConfig["DisableExpressionList"])){$ufdbguardConfig["DisableExpressionList"]=0;}
		if(!isset($ufdbguardConfig["allow-unknown-protocol-over-https"])){$ufdbguardConfig["allow-unknown-protocol-over-https"]=0;}
		if(!isset($ufdbguardConfig["refreshdomainlist"])){$ufdbguardConfig["refreshdomainlist"]=15;}
		if(!isset($ufdbguardConfig["Notrefreshdomainlist"])){$ufdbguardConfig["Notrefreshdomainlist"]=0;}
		if(!isset($ufdbguardConfig["DebugAll"])){$ufdbguardConfig["DebugAll"]=0;}
		if(!isset($ufdbguardConfig["NoMalwareUris"])){$ufdbguardConfig["NoMalwareUris"]=0;}
		
		if($ufdbguardConfig["enforce-https-with-hostname"]==null){$ufdbguardConfig["enforce-https-with-hostname"]=0;}
		if($ufdbguardConfig["enforce-https-official-certificate"]==null){$ufdbguardConfig["enforce-https-official-certificate"]=0;}
		if($ufdbguardConfig["https-prohibit-insecure-sslv2"]==null){$ufdbguardConfig["https-prohibit-insecure-sslv2"]=0;}	
		if(!is_numeric($ufdbguardConfig["url-lookup-result-during-database-reload"])){$ufdbguardConfig["url-lookup-result-during-database-reload"]=1;}
		if(!is_numeric($ufdbguardConfig["url-lookup-result-when-fatal-error"])){$ufdbguardConfig["url-lookup-result-when-fatal-error"]=1;}				
		if(!is_numeric($ufdbguardConfig["check-proxy-tunnel"])){$ufdbguardConfig["check-proxy-tunnel"]=1;}
		if(!is_numeric($ufdbguardConfig["strip-domain-from-username"])){$ufdbguardConfig["strip-domain-from-username"]=0;}
		if(!is_numeric($ufdbguardConfig["refreshuserlist"])){$ufdbguardConfig["refreshuserlist"]=15;}
		if(!is_numeric($ufdbguardConfig["refreshdomainlist"])){$ufdbguardConfig["refreshdomainlist"]=15;}
		if(!is_numeric($ufdbguardConfig["Notrefreshdomainlist"])){$ufdbguardConfig["Notrefreshdomainlist"]=0;}
		if(!is_numeric($ufdbguardConfig["DebugAll"])){$ufdbguardConfig["DebugAll"]=0;}
		if(!is_numeric($ufdbguardConfig["NoMalwareUris"])){$ufdbguardConfig["NoMalwareUris"]=1;}
		if(!is_numeric($ufdbguardConfig["DisableExpressionList"])){$ufdbguardConfig["DisableExpressionList"]=0;}
		
		
		$this->DisableExpressionList=$ufdbguardConfig["DisableExpressionList"];
		$this->NoMalwareUris=$ufdbguardConfig["NoMalwareUris"];
		
		if(!is_file("/var/lib/ufdbartica/category_socialnet/domains.ufdb")){
			$php5=$unix->LOCATE_PHP5_BIN();
			$nohup=$unix->find_program("nohup");
			shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.squid.blacklists.php --ufdb --force >/dev/null 2>&1 &");
		}
		
		
		if(!$this->Is31){$ufdbguardConfig["Notrefreshdomainlist"]=1;}
		$this->Notrefreshdomainlist=$ufdbguardConfig["Notrefreshdomainlist"];
		
		if($ufdbguardConfig["refreshuserlist"]<5){$ufdbguardConfig["refreshuserlist"]=5;}
		
		if(!isset($datas["listen_addr"])){$datas["listen_addr"]="127.0.0.1";}
		if(!isset($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!isset($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
		if($datas["listen_addr"]==null){$datas["listen_addr"]="127.0.0.1";}
		if(!is_numeric($datas["listen_port"])){$datas["listen_port"]="3977";}
		if(!is_numeric($datas["tcpsockets"])){$datas["tcpsockets"]=0;}
		echo "Starting......: ".date("H:i:s")." ufdbguardd: Major:$major, Minor:$minor\n";
		
		if($this->EnableWebProxyStatsAppliance==1){
			if($datas["listen_addr"]=="127.0.0.1"){
				echo "Starting......: ".date("H:i:s")." ufdbguardd: Listen all addresses and not 127.0.0.1\n";
				$datas["listen_addr"]="all";
			}
		}


		
		
		if($major>0){if($minor>26){$datas["tcpsockets"]=1;}}
		
			
		if($ufdbguardConfig["url-lookup-result-during-database-reload"]==1){
			$conf[]="url-lookup-result-during-database-reload deny";
			$conf[]="redirect-loading-database \"$this->SquidGuardIPWeb?loading-database=yes\"";
		}else{
			$conf[]="url-lookup-result-during-database-reload allow";
			$conf[]="redirect-loading-database \"$this->SquidGuardIPWeb?loading-database=yes\"";
		}
		
		if($ufdbguardConfig["url-lookup-result-when-fatal-error"]==1){
			$conf[]="url-lookup-result-when-fatal-error deny";
			$conf[]="redirect-fatal-error \"$this->SquidGuardIPWeb?fatalerror=yes\"";
		}else{
			$conf[]="url-lookup-result-when-fatal-error allow";
			$conf[]="redirect-fatal-error \"$this->SquidGuardIPWeb?fatalerror=yes\"";
		}
		
		if($ufdbguardConfig["check-proxy-tunnel"]==1){
			$conf[]="check-proxy-tunnels queue-checks";
		}
		
		
		$squid_version=$unix->squid_version();
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $squid_version,$re)){
			if(intval($re[2])>4){$re[2]=4;}
			$squid_version="{$re[1]}.{$re[2]}";
		}
		$squidversion="3.3";
		echo "Starting......: ".date("H:i:s")." ufdbguardd: is 3.1x ? -> $this->Is31; Squid-Cache v.$squid_version\n";
		if($squid_version=="3.4"){$squidversion="3.4";}
		
		if($this->Is31){
			$conf[]="redirect-https \"$this->SquidGuardIPWebSSL\"";
			$conf[]="squid-version \"$squidversion\"";
		}
		
		
		if($EnableYoutubeForSchools==1){
			
			echo "Starting......: ".date("H:i:s")." ufdbguardd: Youtube For Schools enabled with ID:$YoutubeForSchoolsID\n";
			if($YoutubeForSchoolsID<>null){
				$conf[]="youtube-edufilter on";
				$conf[]="youtube-edufilter-id \"$YoutubeForSchoolsID\"";
			}else{
				echo "Starting......: ".date("H:i:s")." ufdbguardd: WARNING Youtube For Schools have no ID\n";
			}
		}else{
			echo "Starting......: ".date("H:i:s")." ufdbguardd: Youtube For Schools disabled\n";
		}

		
		
		
		while (list ($key, $line) = each ($ufdbguardConfig) ){
				if($key=="refreshdomainlist"){continue;}
				if($line==0){$ufdbguardConfig[$key]="off";}
				if($line==1){$ufdbguardConfig[$key]="on";}
				if($line==null){$ufdbguardConfig[$key]="off";}
			}
			
			
		$conf[]="upload-stats off";
		$conf[]="logblock on";
		$conf[]="logall {$ufdbguardConfig["DebugAll"]}";
		$conf[]="ufdb-debug-filter {$ufdbguardConfig["DebugAll"]}";
		$conf[]="ufdb-debug-external-scripts {$ufdbguardConfig["DebugAll"]}";
		if($ufdbguardConfig["strip-domain-from-username"]=="on"){$conf[]="strip-domain-from-username on";}
		$conf[]="max-logfile-size  20000000";
		$conf[]="analyse-uncategorised-urls off";
		$conf[]="ufdb-show-url-details on";
		$conf[]="ufdb-expression-optimisation off";
		$conf[]="refreshuserlist {$ufdbguardConfig["refreshuserlist"]}";
		
		if($this->Is31){
			if(!is_numeric($ufdbguardConfig["refreshdomainlist"])){$ufdbguardConfig["refreshdomainlist"]=15;}
			if($ufdbguardConfig["refreshdomainlist"]<5){$ufdbguardConfig["refreshdomainlist"]=5;}
			$conf[]="refreshdomainlist {$ufdbguardConfig["refreshdomainlist"]}";
			
		}
		
		
		if($EnableGoogleSafeSearch==1){$conf[]="safe-search on";}else{$conf[]="safe-search off";}
		if($datas["tcpsockets"]==1){
			$conf[]="port {$datas["listen_port"]}";
			$conf[]="interface {$datas["listen_addr"]}";
		}
		
		if($EnableGoogleSafeSearch==0){
			$conf[]="category safesearch {";
			$conf[]="\toption safe-search on";
			$conf[]="}";
		}
		
		
		$conf[]="";
		if($rewrite<>null){$conf[]=$rewrite;}
 		$conf[]=$sources;
 		$conf[]=$times;
 		$conf[]=$bannedFiles;
 		$conf[]=$BannexExtDomains;
 		$conf[]=$build_expressions_list;
 		$conf[]=$categories;
 		$conf[]=$acls;				
 		$this->BuildCertificates();
 		return @implode("\n", $conf);
 		
		
	}
	
	
	private function build_expressions_list(){
		$main_path="/var/lib/squidguard";
		
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilter_ufdbexpr WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		$mainArray=array();
		$f=array();
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard ". mysql_num_rows($results)." expressions rules\n";
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] ->compile_ufdbguard_expression({$ligne["ID"]})\n";}
			$t=new compile_ufdbguard_expression($ligne["ID"]);
			if(count($t->REGEX_TABLE)==0){continue;}
			$this->AVAILABLE_REGEX_RULES[$ligne["ruleid"]]="RegexRule{$ligne["ruleid"]}";
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] build_expressions_list()->REGEX_TABLE = ". count($t->REGEX_TABLE)." items\n";}
			$finalMainArray=@implode(".*", $t->REGEX_TABLE);
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUGREGEX:". __LINE__."] build_expressions_list()->finalMainArray= \"$finalMainArray\"\n";}
			$mainArray[$ligne["ruleid"]][]=$finalMainArray;
		}
		
		while (list ($ruleid, $patternsArray) = each ($mainArray)){
			@mkdir("$main_path/RegexRule$ruleid",0755,true);
			echo "Starting......: ".date("H:i:s")." ufdbGuard Rule:$ruleid -> ". count($patternsArray)." expressions rules\n";
			
			
			@file_put_contents("$main_path/RegexRule$ruleid/expressions", @implode("\n", $patternsArray));
			$f[]="category \"RegexRule$ruleid\"{";
			$f[]="expressionlist	\"$main_path/RegexRule$ruleid/expressions\"";
			$f[]="}";
			$f[]="";			
			
		}
		
		return @implode("\n", $f);
		
	}
	
	
	
	private function build_rewrites(){
		$f=array();
		$q=new mysql_squid_builder();
		
		if($this->UpdateUtilityRedirectEnable==1){
			$sock=new sockets();
			$UpdateUtilityExternWbsrv=$sock->GET_INFO("UpdateUtilityExternWbsrv");
			$UpdateUtilityHTTPSRV=$sock->GET_INFO("UpdateUtilityHTTPSRV");
			$UpdateUtilityExternWbsrvAddr=$sock->GET_INFO("UpdateUtilityExternWbsrvAddr");
			if(!is_numeric($UpdateUtilityExternWbsrv)){$UpdateUtilityExternWbsrv=0;}	
			if($UpdateUtilityExternWbsrv==1){$UpdateUtilityHTTPSRV=$UpdateUtilityExternWbsrvAddr;}		
			
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard UpdateUtility is enabled\n";}
				
				
				if($UpdateUtilityHTTPSRV<>null){
					if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard UpdateUtility redirect to `$UpdateUtilityHTTPSRV`\n";}
					$f[]="rewrite ReWriteKasper{";
					include_once(dirname(__FILE__)."/class.updateutility-list.inc");
					while (list ($sitenum, $cat) = each ($UPDATEUTILITY_SITES) ){$f[]="\ts@$sitenum@$UpdateUtilityHTTPSRV@i";}
					$f[]="}";
				
			}else{
				$this->UpdateUtilityRedirectEnable==0;
				if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard UpdateUtility no webserver set...\n";}
			}
		}else{
			if($GLOBALS["AS_ROOT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard UpdateUtility is not enabled\n";}
		}
		
		
		$sql="SELECT * FROM webfilters_rewriterules WHERE enabled=1 AND ItemsCount>0";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return null;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$rules=$this->build_rewrites_items($ligne["ID"]);
			if(count($rules)==0){continue;}
			$this->AVAILABLE_REWRITE_RULES[$ligne["ID"]]=true;
			$f[]="rewrite ReWriteRule{$ligne["ID"]}{";
			$f[]=@implode("\n", $rules);
			$f[]="}";
			
			
		}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] build_rewrites() -> ".count($f)." lines\n";}
		if(count($f)>0){return @implode("\n",$f)."\n";}
	}
	
	private function build_rewrites_items($ruleid){
		$q=new mysql_squid_builder();
		$items=array();
		$sql="SELECT * FROM webfilters_rewriteitems WHERE enabled=1 AND ruleid=$ruleid";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";return $items;}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$t[]="\ts@{$ligne["frompattern"]}@{$ligne["topattern"]}@i";
		}
		return $t;
	}
	
	private function BuildBannedFiles(){
		$main_path="/var/lib/squidguard";
		$q=new mysql_squid_builder();
		$sql="SELECT ruleid,ext FROM webfilter_bannedexts WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		$banned=array();
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$ligne["ext"]=trim($ligne["ext"]);
			if(trim($ligne["ext"])==null){continue;}
			$ligne["ext"]=str_replace(".", "", $ligne["ext"]);
			$ligne["ext"]=str_replace("*", "", $ligne["ext"]);
			$banned[$ligne["ruleid"]][]=$ligne["ext"];
			$this->BANNED_FILES_RULES[$ligne["ruleid"]]="FileBlock{$ligne["ruleid"]}";
		}
		$f=array();
		while (list ($ruleid, $exts) = each ($banned)){
			@mkdir("$main_path/FileBlock$ruleid",0755,true);
			$FINAL=array();
			
			while (list ($a, $b) = each ($exts) ){
				if(trim($b)==null){continue;}
				$FINAL[]=".*?\/.*\.{$b}$";
			}
			
			
			@file_put_contents("$main_path/FileBlock{$ruleid}/expressions",@implode("\n", $FINAL));
			$f[]="category \"FileBlock{$ruleid}\"{";
			$f[]="expressionlist	\"$main_path/FileBlock{$ruleid}/expressions\"";
			$f[]="}";
			$f[]="";
			
		}
		
		return @implode("\n", $f);
		
	}
	
	private function BuildBannedDomains(){
		$main_path="/var/lib/squidguard";
		$q=new mysql_squid_builder();
		$sql="SELECT ruleid,ext FROM webfilter_bannedextsdoms WHERE enabled=1";
		$results=$q->QUERY_SQL($sql);
		$banned=array();
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if(trim($ligne["ext"])==null){continue;}
			$ligne["ext"]=str_replace(".", "", $ligne["ext"]);
			$ligne["ext"]=str_replace("*", "", $ligne["ext"]);
			$banned[$ligne["ruleid"]][]="([0-9a-z\-\_]+\.{$ligne["ext"]})$";
			$this->BANNED_EXTDOMS_RULES[$ligne["ruleid"]]="DomExtBlock{$ligne["ruleid"]}";
		}
		$f=array();
		while (list ($ruleid, $exts) = each ($banned)){
			@mkdir("$main_path/DomExtBlock{$ruleid}",0755,true);
			$pattern=@implode("\n", $exts);
			@file_put_contents("$main_path/DomExtBlock{$ruleid}/expressions", $pattern);
			$f[]="category \"DomExtBlock{$ruleid}\"{";
			$f[]="expressionlist	\"$main_path/DomExtBlock{$ruleid}/expressions\"";
			$f[]="}";
			$f[]="";
		}
		
		return @implode("\n", $f);
		
	}	
	
	private function build_times(){
		$GLOBALS["TIMESRULES"]=array();
		if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : build TIMES ....\n";}
		
		
		$sock=new sockets();
		$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
		$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
		if(is_array($TimeSpace)){
			if(is_array($TimeSpace["TIMES"])){
				if(count($TimeSpace["TIMES"])>0){
					if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
					if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}
					if($TimeSpace["RuleMatchTime"]<>null){
						
						$R=array();
						while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
							if(count($array["DAYS"])==0){continue;}
							$dd=array();
							while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]=$day;}}
							if(count($dd)==0){continue;}
							if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
							if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
							if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
							if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
							$R[]="\tweekly ".@implode("", $dd)." {$array["BEGINH"]}:{$array["BEGINM"]} - {$array["ENDH"]}:{$array["ENDM"]}";
						}
						if(count($R)>0){
							$f[]="time timeR0 {";
							$f[]=@implode("\n", $R);
							$f[]="}";
							if($TimeSpace["RuleMatchTime"]=="inside"){$TimeSpace["RuleMatchTime"]="within";}
							$GLOBALS["TIMESRULES"][0]["MATCH"]=$TimeSpace["RuleMatchTime"];
							$GLOBALS["TIMESRULES"][0]["NEXT"]=$TimeSpace["RuleAlternate"];
						}
						
						
					}
				
				}
				
			}
			
			
		}
		
		
		
		$q=new mysql_squid_builder();
		$sql="SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard fatal error $q->mysql_error\n";}
		
		if(mysql_num_rows($results)==0){
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = 0 rules\n";}
		}else{
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : SELECT ID,TimeSpace FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder = ".mysql_num_rows($results)." rules\n";}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$TimeSpace=unserialize(base64_decode($ligne["TimeSpace"]));
			if(!is_array($TimeSpace)){continue;}
			if(!is_array($TimeSpace["TIMES"])){continue;}
			
			print_r($TimeSpace);
			
			if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
			if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}	
			if($TimeSpace["RuleMatchTime"]=="none"){
				if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : RuleMatchTime:{$TimeSpace["RuleMatchTime"]} -> none\n";}
				continue;}
			
			$CountOfTimeSpace=count($TimeSpace["TIMES"]);
			if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : TimeSpaces: $CountOfTimeSpace\n";}
			
			if(count($TimeSpace["TIMES"])==0){continue;}
			
			$R=array();
			while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
				if(count($array["DAYS"])==0){continue;}
				$dd=array();
				while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]=$day;}}
				if(count($dd)==0){continue;}
				if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
				if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
				if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
				if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}
				$R[]="\tweekly ".@implode("", $dd)." {$array["BEGINH"]}:{$array["BEGINM"]} - {$array["ENDH"]}:{$array["ENDM"]}";
			}
			if(count($R)==0){continue;}
			$f[]="time timeR{$ligne["ID"]} {";
			$f[]=@implode("\n", $R);
			$f[]="}";
			if($TimeSpace["RuleMatchTime"]=="inside"){$TimeSpace["RuleMatchTime"]="within";}
			$GLOBALS["TIMESRULES"][$ligne["ID"]]["MATCH"]=$TimeSpace["RuleMatchTime"];
			$GLOBALS["TIMESRULES"][$ligne["ID"]]["NEXT"]=$TimeSpace["RuleAlternate"];
			
		}
		
		return @implode("\n", $f);
		
		
	}
	
	
function ParseLDAPSubUsersGroups($LDAPID,$dn){
	if($GLOBALS["VERBOSE"]){writelogs("LDAPID: $LDAPID, Parsing DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	$ad=new ActiveDirectory($LDAPID);
	$Array=$ad->search_users_from_group($dn);
	if($GLOBALS["VERBOSE"]){writelogs("DN=$dn items=`". count($Array)."`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	if($ad->ldap_last_error<>null){writelogs("FAILED to lookup dn $dn",__FUNCTION__,__FILE__,__LINE__);}
	
	if(count($Array)==0){
		writelogs("Lookup dn $dn return no user !!!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
		return array();
	}
	
	
	if($GLOBALS["VERBOSE"]){writelogs("DN=$dn START LOOP",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
	
	while (list ($dn, $GPARR) = each ($Array) ){
		$dnEnc=base64_encode($dn);
		$type=$GPARR["TYPE"];
		if($GLOBALS["VERBOSE"]){writelogs("DN=$dn type=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if($type=="group"){
			if($ad->LDAP_RECURSIVE==1){
				writelogs("Group -> ParseUsersGroups($dn,$search)",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
				if(count($newrow>0)){
					while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
				}
			}
			continue;
		}
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			$cn=trim($GPARR["uid"]);
			if(trim($cn)==null){continue;}
			$cn=str_replace(" ", "%20", $cn);
			$f[]="\tuser\t\"$cn\"";
		}
	
	
	return $f;
}	
	
	
	private function members_from_activedirectory($dn){
		if(trim($dn)==null){return;}
		if(preg_match("#AD:(.*?):(.+)#", $dn,$re)){
				$dnEnc=$re[2];
				$LDAPID=$re[1];
				$dn=base64_decode($dnEnc);
				if($GLOBALS["VERBOSE"]){writelogs("LDAPID:$LDAPID DN=`$dn`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$ad=new ActiveDirectory($LDAPID);
				$USERS=$ad->search_users_from_group($dn,0);
		}else{
			ufdbguard_admin_events("Failed to lookup $dn", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		if($GLOBALS["VERBOSE"]){writelogs("Array(USERS) = ".count($USERS)." items",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		
		if(count($USERS)==0){
			ufdbguard_admin_events("Failed to lookup $dn no such user", __FUNCTION__, __FILE__, __LINE__, "activedirectory");
			return null;
		}
		
		while (list ($dn, $Props) = each ($USERS) ){
			$type=$Props["TYPE"];
			if($GLOBALS["VERBOSE"]){writelogs("DN=$dn TYPE=`$type`",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			
			
			if($type=="group"){
				if($ad->LDAP_RECURSIVE==1){
					writelogs("Group -> ParseLDAPSubUsersGroups($LDAPID,$dn)",__FUNCTION__,__FILE__,__LINE__);
					$newrow=$this->ParseLDAPSubUsersGroups($LDAPID,$dn);
					if(count($newrow>0)){
						while (list ($a, $b) = each ($newrow) ){$f[]=$b;}
					}
				}
				continue;
			}			
			
			if(trim($Props["uid"]==null)){continue;}
			$Props["uid"]=trim(strtolower($Props["uid"]));
			$Props["uid"]=str_replace(" ", "%20", $Props["uid"]);
			$f[]="\tuser\t\"{$Props["uid"]}\"";
			
		}
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Active Directory group $dn return ". count($f) ." items\n";}
		return @implode("\n", $f)."\n";
	}	
	
	
	private function ActiveDirectoryToName($groupname){
		$groupname=trim($groupname);
		$groupname=strtolower($groupname);
		$groupname=str_replace(" ", "_", $groupname);
		$userinfo = @posix_getgrnam($groupname);
		if(!isset($userinfo["gid"])){return null;}
		if(!is_numeric($userinfo["gid"])){return null;}
		if($userinfo["gid"]<1){return null;}
		return $groupname;
	}
	
	
	public function isDynamic($ruleid){
		$sql="SELECT webfilter_group.* FROM webfilter_group,webfilter_assoc_groups
		WHERE webfilter_assoc_groups.group_id=webfilter_group.ID
		AND webfilter_assoc_groups.webfilter_id=$ruleid
		AND webfilter_group.enabled=1";		
		$c=0;
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			if($ligne["localldap"]==0){
				$c++;
			}
			
			if($ligne["localldap"]==2){
				$c++;
			}
		}
		
		if($c>0){return true;}
		
	}
	
	
	public function build_membersrule($ruleid){
		$UseDynamicGroupsAcls=$this->UseDynamicGroupsAcls;
		if($this->KerbAuthDisableNsswitch==1){$UseDynamicGroupsAcls=0;}
		$array=array();
		$q=new mysql_squid_builder();
		if($ruleid>0){
			$sql="SELECT AllSystems FROM webfilter_rules WHERE ID=$ruleid";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			if($ligne["AllSystems"]==1){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $ruleid] is an all systems rule\n";}
				return "\tip\t0.0.0.0 - 255.255.255.255";
			}
		}
		
		
		
		$sql="SELECT webfilter_group.* FROM webfilter_group,webfilter_assoc_groups
		 WHERE webfilter_assoc_groups.group_id=webfilter_group.ID
		 AND webfilter_assoc_groups.webfilter_id=$ruleid
		 AND webfilter_group.enabled=1";
		
		
		$results=$q->QUERY_SQL($sql);
		$arrayGROUPS=array();
		if($GLOBALS["VERBOSE"]){writelogs("$ruleid] has ".mysql_num_rows($results)." groups",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
		if(isset($GLOBALS["PHP5_BIN"])){
			$unix=new unix();
			$GLOBALS["PHP5_BIN"]=$unix->LOCATE_PHP5_BIN();
		}
		
		$isDynamic=$this->isDynamic($ruleid);
		if($isDynamic){
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." $ruleid] store dynamic groups\n";}
		}
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
				$groupname=$ligne["groupname"];
				if($GLOBALS["VERBOSE"]){writelogs("$ruleid]LDAP :Type:{$ligne["localldap"]}: $groupname LDAP?={$ligne["localldap"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				if($ligne["localldap"]==0){
					if($ligne["dn"]<>null){
						$squid=new squidbee();
						$arrayGROUPS["EXTLDAP"][]=array("DN"=>$ligne["dn"],"CONF"=>$squid->EXTERNAL_LDAP_AUTH_PARAMS);
						continue;
					}
					
					$arrayGROUPS["LDAP"][]=$ligne["gpid"];
					continue;
				}
			
			if($ligne["localldap"]==2){
				if(preg_match("#AD:[0-9]+:(.+)#", $ligne["dn"],$re)){
					if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}: $groupname ACTIVE DIRECTORY={$ligne["localldap"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					$arrayGROUPS["AD"][]=$re[1];
					continue;
				}
			

				if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}:$groupname Is an ACTIVE DIRECTORY group -> `{$ligne["dn"]}` ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				$DN=base64_encode($ligne["dn"]);
				$arrayGROUPS["AD"][]=$DN;
				continue;
			}
		
			if($GLOBALS["VERBOSE"]){writelogs("$ruleid] Type:{$ligne["localldap"]}: $groupname Is an ACTIVE DIRECTORY group -> `{$ligne["dn"]}` ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			
			
		
			if(!$isDynamic){
				$sql="SELECT * FROM webfilter_members WHERE enabled=1 AND groupid={$ligne["ID"]}";
				$results2=$q->QUERY_SQL($sql);
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $groupname({$ligne["ID"]}) webfilter_members = ". mysql_num_rows($results2)." items\n";}
				while($ligne2=mysql_fetch_array($results2,MYSQL_ASSOC)){
					if(trim($ligne2["pattern"])==null){continue;}
					if($GLOBALS["VERBOSE"]){writelogs("$groupname {$ligne2["pattern"]}={$ligne2["membertype"]}",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
					if($ligne2["membertype"]==0){$array[]="\tip\t{$ligne2["pattern"]}";continue;}
					if($ligne2["membertype"]==2){$array[]="\tip\t{$ligne2["pattern"]}";continue;}
					if($ligne2["membertype"]==1){$array[]="\tuser\t\"{$ligne2["pattern"]}\"";continue;}			
				}
			}
		
		
		}
		
		if($isDynamic){
			if(count($arrayGROUPS)>0){
			@file_put_contents("/etc/squid3/ufdb.groups.$ruleid.db", serialize($arrayGROUPS));
			}
			$array[]="\texecuserlist\t\"/usr/share/artica-postfix/external_acl_squid_ldap.php --db $ruleid\"";
		}
		if(count($array)>0){return @implode("\n", $array);}
		
	}
	
	
	private function build_sources(){
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard build SOURCES ....\n";}
		$q=new mysql_squid_builder();
		
		$sql="SELECT ID,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY zOrder";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$RULE_ID=$ligne["ID"];
			$rulename=$ligne["groupname"];
			$members=$this->build_membersrule($RULE_ID);
			if($members==null){
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard skip `$rulename` (no members)\n";}
				continue;
			}
			$rulename=str_replace(" ", "-", $rulename);
			$q10=new mysql_squid_builder();
			$rulename=$q10->StripBadChars_hostname($rulename);
			$array[]="source \"$rulename\" {";
			$array[]=$members;
			$array[]="}";
			$array[]="";
			$GLOBALS["SRC"][$ligne["ID"]]=$rulename;
			
		}
		
		$sql="SELECT publicip,wwwname,userid FROM usersisp WHERE enabled=1 AND LENGTH(publicip)>3";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$rulename="account{$ligne["userid"]}";
			$array[]="source \"$rulename\" {";
			$array[]="\tip\t{$ligne["publicip"]}";
			if($ligne["wwwname"]<>null){
				$GLOBALS["ISP_ERROR_PAGE"]["account{$ligne["userid"]}"]=$ligne["wwwname"];
				$GLOBALS["ISP_ERROR_PAGE"]["DEFAULT"]=$ligne["wwwname"];
			}
			$array[]="}";
			$array[]="";
		}
		
		

		return @implode("\n", $array);
		
		}
		
	public function build_categories_uris($category,$destinationfile){
		$unix=new unix();
		
		if($category=="malware"){
			if($this->NoMalwareUris==1){
				echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: is denied\n";
				$GLOBALS["build_categories_uris"][$category]=null;
				return $GLOBALS["build_categories_uris"][$category];
			}
		}
		
		if(isset($GLOBALS["build_categories_uris"][$category])){return $GLOBALS["build_categories_uris"][$category];}
		$urls_table="categoryuris_".$this->TransFormCategoryName($category);
		$q=new mysql_squid_builder();
		if(!$q->TABLE_EXISTS($urls_table)){
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` no such table...\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return $GLOBALS["build_categories_uris"][$category];
		}
		
		if($q->COUNT_ROWS($urls_table)==0){
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` now row...\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return $GLOBALS["build_categories_uris"][$category];
		}
		
		$tmpfile=$unix->FILE_TEMP();
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 0777 ".dirname($tmpfile));
		@unlink($tmpfile);
		$sql="SELECT pattern FROM $urls_table WHERE enabled=1 INTO OUTFILE '$tmpfile' LINES TERMINATED BY '\n';";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			@unlink("$tmpfile");
			echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$urls_table` $q->mysql_error\n";
			$GLOBALS["build_categories_uris"][$category]=null;
			return null;
		}
		
		if(!is_dir(dirname($destinationfile))){@mkdir(dirname($destinationfile),0755,true);}
		@copy("$tmpfile", $destinationfile);
		@unlink("$tmpfile");
		echo "Starting......: ".date("H:i:s")." ".__LINE__."[URI] $category: `$destinationfile` OK...\n";
		$GLOBALS["build_categories_uris"][$category]=$destinationfile;
		return $destinationfile;
	}
		
		
	private function build_categories_paragraph($category){
		$OrginalCategoryNamed=$category;
		$RedirectUriSSL=null;
		if($this->licensed==0){
			$this->SquidDatabasesArticaEnable=0;
		}
		if($category=="isp"){echo "Starting......: ".date("H:i:s")." WARN ufdbGuard Checking category `$category` is banned, SKIP\n";return ;}
		if($category=="english-malware"){echo "Starting......: ".date("H:i:s")." WARN ufdbGuard Checking category `$category` is banned, SKIP\n";return ;}
		if($category=="audio-video"){
			if(is_dir("/var/lib/ftpunivtlse1fr/audio-video")){
				if(!is_dir("/var/lib/ftpunivtlse1fr/audiovideo")){
					shell_exec("/bin/ln -sf /var/lib/ftpunivtlse1fr/audio-video /var/lib/ftpunivtlse1fr/audiovideo");
				}
			}
			
		}
		
		
		$sock=new sockets();
		$this->SquidDatabasesUtlseEnable=$sock->GET_INFO("SquidDatabasesUtlseEnable");
		if(!is_numeric($this->SquidDatabasesUtlseEnable)){$this->SquidDatabasesUtlseEnable=1;}
		$OrigninalCategoryName=$category;
		if(isset($GLOBALS["CATEGORIES_BUILDED"][$OrigninalCategoryName])){return;}
		echo "Starting......: ".date("H:i:s")." ufdbGuard Checking category `$category`\n";
		echo "Starting......: ".date("H:i:s")." ufdbGuard Toulouse University Enabled `$this->SquidDatabasesUtlseEnable`\n";
		if($this->SquidGuardWebFollowExtensions==1){$follow_ext="&followext=yes";}
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		$q=$GLOBALS["Q"];
		$category_table=$q->cat_totablename($category);
		$table="category_".$q->category_transform_name($category);
		echo "Starting......: ".date("H:i:s")." ufdbGuard Enabling $category table `$category_table` OR `$table`\n";
		
		
		if($this->SquidGuardWebUseExternalUri==0){
			if(!preg_match("#^http.*?\/\/#",$this->SquidGuardIPWeb)){
				$this->SquidGuardIPWeb="http://$this->SquidGuardIPWeb";
				if(!preg_match("#squidguard\.php#",$this->SquidGuardIPWeb)){$this->SquidGuardIPWeb=$this->SquidGuardIPWeb."/exec.squidguard.php";}
			}

			if(!preg_match("#^http.*?\/\/#",$this->SquidGuardIPWebSSL)){
				$this->SquidGuardIPWebSSL="https://$this->SquidGuardIPWebSSL";
				if(!preg_match("#squidguard\.php#",$this->SquidGuardIPWebSSL)){$this->SquidGuardIPWebSSL=$this->SquidGuardIPWebSSL."/exec.squidguard.php";}
			}			
			
		}	
		
		$RedirectUri="\tredirect $this->SquidGuardIPWeb?category=". urlencode($category)."&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		
		
		
		$RedirectArray=$this->UfdbGuardRedirectCategories[$category];
		if(!isset($RedirectArray["external_uri"])){$RedirectArray["external_uri"]=0;}
		if(!isset($RedirectArray["redirect_url"])){$RedirectArray["redirect_url"]=null;}
		if(!isset($RedirectArray["enable"])){$RedirectArray["enable"]=0;}
		if(!is_numeric($RedirectArray["external_uri"])){$RedirectArray["external_uri"]=0;}		
		
		$main_path="/var/lib/squidguard";
		$main_artica_path="/var/lib/ufdbartica";		
		$BASETLSE="/var/lib/ftpunivtlse1fr/".$q->category_transform_name_toulouse($category);
		$categorypathTLSE="$BASETLSE/domains.ufdb";
		$categorypathTLSEUrls="$BASETLSE/urls";
			
		$BASEArt="$main_artica_path/$category_table";
		$categorypathArtica="$BASEArt/domains.ufdb";
		$categorypathArticaUrls="$BASEArt/urls";
			
		if($RedirectArray["external_uri"]==1){$RedirectUri="\tredirect {$RedirectArray["external_uri"]}";}
		if($RedirectArray["enable"]==0){$RedirectUri=null;}
			
			
		$categoryNameTLSE="tls".$q->category_transform_name_toulouse($category);
		$categoryNameArt="art".$q->category_transform_name_toulouse($category);
	
		
		$categorynamePerso=$this->TransFormCategoryName($category);
		$categorynamePersoBAse="$main_path/$categorynamePerso";
		$categorynamePersoPathBase="$categorynamePersoBAse/domains.ufdb";
		$categorynamePersoPathUrls="$categorynamePersoBAse/urls";
		
		if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
		$categoryname=$re[1];
		$IS_CAT_ADDED=false;
		$array=array();
		
		$BANNARTICA["isp"]=true;
		
// ***********************************************************************************************************************************************************		
		if($this->SquidDatabasesArticaEnable==1){
			if(!isset($GLOBALS["ADDED_CATEGORY_TOFILE"][$categoryNameArt])){
			if(!isset($BANNARTICA[$OrigninalCategoryName])){
					if(is_file($categorypathArtica)){
						if(!is_file("$BASEArt/expressions")){@file_put_contents("$BASEArt/expressions", "\n");}
						if(!isset($GLOBALS["UFDBART"][$categoryname])){
							$GLOBALS["UFDBART"][$categoryname]=$categoryNameArt;
							echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname` $categoryNameArt Added\n";
							echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname` $BASEArt\n";
							$GLOBALS["ADDED_CATEGORY_TOFILE"][$categoryNameArt]=true;
							$array[]="category \"$categoryNameArt\" {";
							if($RedirectUri<>null){$array[]=$RedirectUri;}
							$categorypathArtica=str_replace(".ufdb", "", $categorypathArtica);
							$IS_CAT_ADDED=true;
							$array[]="\tdomainlist\t\"$categorypathArtica\"";
							
							echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] DisableExpressionList:`$this->DisableExpressionList`\n";
							if($this->DisableExpressionList==0){
								if(is_file("$BASEArt/expressions")){
									$isize=@filesize("$BASEArt/expressions");
									echo "Starting......: ".date("H:i:s")." ".__LINE__."[ART] `$categoryname` $BASEArt/expressions ( $isize bytes) \n";
									if($isize>10){
										$array[]="\texpressionlist \"$BASEArt/expressions\"";
									}
								}
							}
							$array[]="}\n";	
						}
					}
			}else{ echo "Starting......: ".date("H:i:s")." [ART] `$OrigninalCategoryName` ( Already defined )\n"; }
			 
		}else{ echo "Starting......: ".date("H:i:s")." [ART] `$OrigninalCategoryName` ( Already defined )\n"; }
	}else{echo "Starting......: ".date("H:i:s")." [ART] `$category` ufdbGuard Artica Databases are disabled (see SquidDatabasesArticaEnable)\n"; }
	
	
// ***********************************************************************************************************************************************************
		if($this->SquidDatabasesUtlseEnable==1){
			if(!isset($GLOBALS["ADDED_CATEGORY_TOFILE"][$categoryNameTLSE])){
					if(is_file($categorypathTLSE)){
						$IS_CAT_ADDED=true;
						$GLOBALS["ADDED_CATEGORY_TOFILE"][$categoryNameTLSE]=true;
						if(!is_file("$BASETLSE/expressions")){@file_put_contents("$BASETLSE/expressions", "\n");}
						if(!isset($GLOBALS["UNIVTOULOUSE"][$categoryname])){
							$GLOBALS["UNIVTOULOUSE"][$categoryname]=$categoryNameTLSE;
							echo "Starting......: ".date("H:i:s")." [TLS] `$categoryname` $categoryNameTLSE Added\n";
							$array[]="category \"$categoryNameTLSE\" {";
							if($RedirectUri<>null){$array[]=$RedirectUri;}
							$categorypathTLSE=str_replace(".ufdb", "", $categorypathTLSE);
							echo "Starting......: ".date("H:i:s")." ufdbGuard Adding $categoryNameTLSE (Toulouse U.)\n";
							$array[]="\tdomainlist\t\"$categorypathTLSE\"";
							$array[]="}\n";	
							}else{
								echo "Starting......: ".date("H:i:s")." [TLS] WARN ufdbGuard Checking category `$category` $categorypathTLSE No such file\n";
							}
					}else{ echo "Starting......: ".date("H:i:s")." [TLS] WARN category `$category` Toulouse University $main_path/$categoryname/domains no such file\n"; }
				}else{ echo "Starting......: ".date("H:i:s")." [TLS] `$categoryNameTLSE` ( Already defined )\n"; }
	
			}{echo "Starting......: ".date("H:i:s")." [TLS] `$categoryNameTLSE` ufdbGuard Toulouse Databases are disabled (see SquidDatabasesUtlseEnable)\n"; }
// ***********************************************************************************************************************************************************				
				
			if(!is_file("$main_path/$categoryname/domains")){
					echo "Starting......: ".date("H:i:s")." FAILED ufdbGuard $categoryname/domains, no such file\n";
					$order="COMPILEDB:$category";$md5=md5($order);
					$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");
					if(!$q->ok){if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$q->QUERY_SQL("INSERT IGNORE INTO framework_orders (`zmd5`,`ORDER`) VALUES('$md5','$order')");}}
					ufdbguard_admin_events("Fatal category $categoryname $main_path/$categoryname/domains no such file",__FUNCTION__,__FILE__,__LINE__,"config");
				}		

		
			
				$size=@filesize("$main_path/$categoryname/domains");
				echo "Starting......: ".date("H:i:s")." INFO ufdbGuard $categoryname/domains = $size bytes length\n";
				if($size<12){
					$rowsnum=$q->COUNT_ROWS($table);
					if($rowsnum>0){
						if($rowsnum<100000){
							echo "Starting......: ".date("H:i:s")." ufdbGuard $categoryname/domains $rowsnum rows -> Compile\n";
							$GLOBALS["OUTPUT"]=true;
							$this->compile_category($categoryname);
						}
					}
				}
		
				$PeroZ=$this->BuildPersoCategories($categorynamePersoPathBase,$categorynamePersoBAse,$categoryname,$categorynamePerso,$RedirectUri,$OrginalCategoryNamed,$RedirectUriSSL);
				if($PeroZ<>null){
					$IS_CAT_ADDED=true;
					$array[]=$PeroZ;
				}
		echo "Starting......: ".date("H:i:s")." ufdbGuard $categoryname Added in list\n";
		$GLOBALS["CATEGORIES_BUILDED"][$categoryname]=true;
		if(!$IS_CAT_ADDED){$GLOBALS["CATEGORIES_SKIPPED"][$categoryname]=true;}else{$GLOBALS["CATEGORIES_ADDED"][$categoryname]=true;}
		if(count($array)>0){return @implode("\n", $array);}
		
	}
	
	private function BuildPersoCategories($supposed_path,$categorynamePersoBAse,$categoryname,$categorynamePerso,$RedirectUri,$OrginalCategoryNamed,$RedirectUriSSL){
		$q=new mysql_squid_builder();
		$extLOG=null;
		if(!$this->Is31){$extLOG=" (version <1.31)";$this->Notrefreshdomainlist=1;}
		
		$category_table=$q->cat_totablename($OrginalCategoryNamed);
		$OrginalCategoryNamedKey=$this->TransFormCategoryName($OrginalCategoryNamed);
		
		if(isset($GLOBALS["PERSONALCATZ"][$OrginalCategoryNamedKey])){
			echo "Starting......: ".date("H:i:s")." ufdbGuar checking $OrginalCategoryNamedKey (PERSO) already parsed...\n";
			return;
		}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuar checking $OrginalCategoryNamed (PERSO) \"$supposed_path\"\n";
		
		if(!preg_match("#^category_.*#", $category_table)){
			$category_table="category_$OrginalCategoryNamed";
		}
		if(!$q->TABLE_EXISTS($category_table)){
				echo "Starting......: ".date("H:i:s")." ufdbGuard WARNING $categorynamePerso (PERSO) ($category_table), no such table\n";
				return;
		}
		$COUNT_ROWS=$q->COUNT_ROWS($category_table);
		if($COUNT_ROWS==0){
			if(!is_file($supposed_path)){
				echo "Starting......: ".date("H:i:s")." ufdbGuard WARNING $categorynamePerso (PERSO) No entry in table $category_table\n";
				return;		
			}	
		}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard Adding $categorynamePerso (PERSO) ($COUNT_ROWS entries) $extLOG\n";
		if(!is_file("$categorynamePersoBAse/expressions")){
				@file_put_contents("$categorynamePersoBAse/expressions", "\n");
		}
		
		
		
		if($this->Notrefreshdomainlist==1){
			if(!is_file($supposed_path)){
				echo "Starting......: ".date("H:i:s")." ufdbGuard $categorynamePerso $supposed_path (PERSO) no such file\n";
				$GLOBALS["CATEGORIES_SKIPPED"][$OrginalCategoryNamedKey]=true;
				return;
			}
		}
		
		$categorynameP=$categoryname;
		
		if( ( $categorynameP=="agressivecat")  OR ($categorynameP=="agressive") ){
			$supposed_path=str_replace("agressivecat", "agressive", $supposed_path);
		}
		
		if($categorynameP=="agressive"){$categorynameP="agressivecat";}
		
		
		if(isset($GLOBALS["ADDED_CATEGORY_TOFILE"][$categorynameP])){return;}
		$array[]="category \"$categorynameP\" {";
		if($RedirectUri<>null){$array[]=$RedirectUri;}
		$GLOBALS["ADDED_CATEGORY_TOFILE"][$categorynameP]=true;
		
		$categorynamePersoPathBaseDOM=str_replace(".ufdb", "", $supposed_path);
		if($this->Notrefreshdomainlist==0){
			$array[]="\texecdomainlist\t\"/usr/share/artica-postfix/execdomainlist.php $OrginalCategoryNamed $categorynamePersoPathBaseDOM\"";
			$array[]="\tdomainlist\t\"$categorynamePersoPathBaseDOM\"";
			
			
		}else{
			
			$array[]="\tdomainlist\t\"$categorynamePersoPathBaseDOM\"";
		}
		$array[]="}\n";
		$GLOBALS["PERSONALCATZ"][$OrginalCategoryNamedKey]=$categoryname;
		$GLOBALS["CATEGORIES_ADDED"][$OrginalCategoryNamedKey]=$OrginalCategoryNamedKey;
		return @implode("\n", $array);
	}
	
	
		
	function build_categories(){
		$q=new mysql_squid_builder();
		$sock=new sockets();
		$main_path="/var/lib/squidguard";
		$main_artica_path="/var/lib/ufdbartica";
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: main_path=$main_path main_artica_path=$main_artica_path\n";}
		
		
		
		$add_ufdbgard_secu=false;
		$sql="SELECT category FROM webfilter_blks GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::webfilter_blks: `".mysql_num_rows($results)."` items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}
		
		$sql="SELECT category FROM webfilter_blkcnt GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::webfilter_blks: `".mysql_num_rows($results)."` items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}		
		
		
		$sql="SELECT category FROM usersisp_catztables GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::usersisp_catztables: `".mysql_num_rows($results)."` items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}

		$sql="SELECT category FROM usersisp_blkwcatz GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::usersisp_blkwcatz: `".mysql_num_rows($results)."` items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}			
		
		$sql="SELECT category FROM usersisp_blkcatz GROUP BY category";
		$results=$q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG::".__FUNCTION__."::usersisp_blkcatz: `".mysql_num_rows($results)."` items\n";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$category=$ligne["category"];
			$Builded=$this->build_categories_paragraph($category);
			if($Builded<>null){$array[]=$Builded;}
		}			
		
		
		
		
	
		$ufdbguardConfig=unserialize(base64_decode($sock->GET_INFO("ufdbguardConfig")));
		if(!isset($ufdbguardConfig["enforce-https-official-certificate"])){$ufdbguardConfig["enforce-https-official-certificate"]=null;}
		if(!isset($ufdbguardConfig["enforce-https-with-hostname"])){$ufdbguardConfig["enforce-https-with-hostname"]=null;}
		if(!isset($ufdbguardConfig["https-prohibit-insecure-sslv2"])){$ufdbguardConfig["https-prohibit-insecure-sslv2"]=null;}
		if(!isset($ufdbguardConfig["allow-unknown-protocol-over-https"])){$ufdbguardConfig["allow-unknown-protocol-over-https"]=null;}
			
		if($ufdbguardConfig["enforce-https-with-hostname"]==null){$ufdbguardConfig["enforce-https-with-hostname"]=0;}
		if($ufdbguardConfig["enforce-https-official-certificate"]==null){$ufdbguardConfig["enforce-https-official-certificate"]=0;}
		if($ufdbguardConfig["https-prohibit-insecure-sslv2"]==null){$ufdbguardConfig["https-prohibit-insecure-sslv2"]=0;}
		if($ufdbguardConfig["allow-unknown-protocol-over-https"]==null){$ufdbguardConfig["allow-unknown-protocol-over-https"]=1;}			
			
		while (list ($key, $line) = each ($ufdbguardConfig) ){
			if($line==0){$ufdbguardConfig[$key]="off";}
			if($line==1){$ufdbguardConfig[$key]="on";}
			if($line==null){$ufdbguardConfig[$key]="off";}
		}
			
		if($ufdbguardConfig["enforce-https-with-hostname"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["enforce-https-official-certificate"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["https-prohibit-insecure-sslv2"]=="on"){$add_ufdbgard_secu=true;}
		if($ufdbguardConfig["allow-unknown-protocol-over-https"]=="off"){$add_ufdbgard_secu=true;}
		
		
		
		if($add_ufdbgard_secu){$GLOBALS["ADD_UFDBGUARD_SSL"]=true;}
			
		$array[]="\ncategory security {";
		//$array[]="\tdomainlist	security/domains";
		$array[]="\tcacerts \"/var/lib/squidguard/security/cacerts\"";
		$array[]="\toption enforce-https-with-hostname {$ufdbguardConfig["enforce-https-with-hostname"]}";
		$array[]="\toption enforce-https-official-certificate {$ufdbguardConfig["enforce-https-official-certificate"]}";
		$array[]="\toption https-prohibit-insecure-sslv2 {$ufdbguardConfig["https-prohibit-insecure-sslv2"]}";
		$array[]="\toption allow-unknown-protocol-over-https {$ufdbguardConfig["allow-unknown-protocol-over-https"]}";
		//$array[]="\tallow-citrixonline-over-https on";
		$array[]="}\n";	
			
		//$array[]="\ncategory checked{";
		//$array[]="domainlist	checked/domains";
		//@mkdir("/var/lib/squidguard/checked",0755,true);
		//$array[]="}\n";
	
		return @implode("\n", $array);
	
	

	}
	
	private function build_acls_uriFromRuleID($ID){
		$RedirectUriDEF="$this->SquidGuardIPWeb";
		$RedirectUriDEFSSL=$this->SquidGuardIPWebSSL;
		$redirect_pattern="?rule-id=$ID&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		
		if($ID==0){
			if($this->DansGuardianDefaultMainRule["UseExternalWebPage"]==1){
				if(preg_match("#^http.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
					$RedirectUriDEF=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
				}
				if(preg_match("#^https.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
					$RedirectUriDEFSSL=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
				}

				return array("$RedirectUriDEF","$RedirectUriDEFSSL");
					
			}
			
			
			if($this->DansGuardianDefaultMainRule["freeweb"]<>null){
				$RedirectUriDEF="http://{$this->DansGuardianDefaultMainRule["freeweb"]}$redirect_pattern";
				$RedirectUriDEFSSL="https://{$this->DansGuardianDefaultMainRule["freeweb"]}$redirect_pattern";
				return array("$RedirectUriDEF","$RedirectUriDEFSSL");
			}	

		
		}
		
		$q=new mysql_squid_builder();
		
		
		
		$sql="SELECT freeweb,ExternalWebPage,UseExternalWebPage FROM webfilter_rules WHERE ID=$ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		
		echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] UseExternalWebPage: {$ligne["UseExternalWebPage"]}\n";
		echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] freeweb: {$ligne["freeweb"]}\n";
		
		if($ligne["UseExternalWebPage"]==0){
			if($ligne["freeweb"]==null){
				echo "Starting......: ".date("H:i:s")." [ACL]: [$ID]:SquidGuardIPWeb: $this->SquidGuardIPWeb\n";
				echo "Starting......: ".date("H:i:s")." [ACL]: [$ID] $RedirectUriDEF$redirect_pattern\n";
				return array("$RedirectUriDEF$redirect_pattern","$RedirectUriDEFSSL$redirect_pattern");
			}
		}
		
		
		if($ligne["UseExternalWebPage"]==0){
			if($ligne["freeweb"]<>null){
					return array("http://{$ligne["freeweb"]}$redirect_pattern","https://{$ligne["freeweb"]}$redirect_pattern");
			}
			return array($RedirectUriDEF.$redirect_pattern,$RedirectUriDEFSSL.$redirect_pattern);
		}
		if(trim($ligne["ExternalWebPage"])==null){
			if($ligne["freeweb"]<>null){
					return array("http://{$ligne["freeweb"]}$redirect_pattern","https://{$ligne["freeweb"]}$redirect_pattern");
			}
			return array("$RedirectUriDEF$redirect_pattern","$RedirectUriDEFSSL$redirect_pattern");
		}
		
		
		if(!preg_match("#^http.*?:#",$ligne["ExternalWebPage"])){return array($RedirectUriDEF,$RedirectUriDEFSSL);}
		return array($ligne["ExternalWebPage"],$RedirectUriDEFSSL);
	}
	
	
	function build_acls(){
		$allows=array();
		$banns=array();		
		$q=new mysql_squid_builder();
		$time=null;
		if(!isset($GLOBALS["EnableGoogleSafeSearch"])){$sock=new sockets();$GLOBALS["EnableGoogleSafeSearch"]=$sock->GET_INFO("EnableGoogleSafeSearch");if(!is_numeric($GLOBALS["EnableGoogleSafeSearch"])){$GLOBALS["EnableGoogleSafeSearch"]=1;}}
		if($this->SquidGuardWebFollowExtensions==1){$follow_ext="&followext=yes";}
		$redirect_pattern="rule-id=0&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
		$rewrite_rules=null;
		$bannedExtDomains=null;
		$bannedfiles=null;
		$bannedRegexP=null;	
		if($GLOBALS["EnableGoogleSafeSearch"]==1){$this->DansGuardianDefaultMainRule["GoogleSafeSearch"]=0;}
		$MyRuleGoogleSafeSearch=$this->DansGuardianDefaultMainRule["GoogleSafeSearch"];	
		if(!isset($this->DansGuardianDefaultMainRule["endofrule"])){$this->DansGuardianDefaultMainRule["endofrule"]="any";}
		if(!isset($this->DansGuardianDefaultMainRule["UseExternalWebPage"])){$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["ExternalWebPage"])){$this->DansGuardianDefaultMainRule["ExternalWebPage"]=null;$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if($this->DansGuardianDefaultMainRule["ExternalWebPage"]==null){$this->DansGuardianDefaultMainRule["UseExternalWebPage"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["UseSecurity"])){$this->DansGuardianDefaultMainRule["UseSecurity"]=0;}
		if(!isset($this->DansGuardianDefaultMainRule["freeweb"])){$this->DansGuardianDefaultMainRule["freeweb"]=null;}
	
		
		$endofrule=$this->DansGuardianDefaultMainRule["endofrule"];
		if($endofrule<>null){$endofrule=" $endofrule";}
		
		if($this->SquidGuardWebUseExternalUri==0){
			if(!preg_match("#^http.*?\/\/#",$this->SquidGuardIPWeb)){
				$this->SquidGuardIPWeb="https://$this->SquidGuardIPWeb";
				if(!preg_match("#squidguard\.php#",$this->SquidGuardIPWeb)){$this->SquidGuardIPWeb=$this->SquidGuardIPWeb."/exec.squidguard.php?$redirect_pattern";}
			}		
		}
		
		$RedirectUriDEF="$this->SquidGuardIPWeb";
		$RedirectUriDEFSSL=$this->SquidGuardIPWebSSL;
		
		
		if($this->DansGuardianDefaultMainRule["UseExternalWebPage"]==1){
			if(preg_match("#^http.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
				$RedirectUriDEF=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
			}
			
			if(preg_match("#^https.*?:#", $this->DansGuardianDefaultMainRule["ExternalWebPage"])){
				$RedirectUriDEFSSL=$this->DansGuardianDefaultMainRule["ExternalWebPage"];
			}			
			
		}

		if($this->DansGuardianDefaultMainRule["freeweb"]<>null){
			$RedirectUriDEF="http://{$this->DansGuardianDefaultMainRule["freeweb"]}";
			$RedirectUriDEFSSL="https://{$this->DansGuardianDefaultMainRule["freeweb"]}";
		}
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect banned to \"$RedirectUriDEF\"\n";
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard redirect banned to SSL \"$RedirectUriDEFSSL\"\n";
		
		$array[]="acl{";
		$sql="SELECT category,modeblk FROM webfilter_blks WHERE webfilter_id=0";
		$results=$q->QUERY_SQL($sql);
		echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS for default rule ". mysql_num_rows($results)." items...\n";
		
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
			$bannedRegexP=null;
			$category=trim($ligne["category"]);
			if($category==null){continue;}
			if($category=="english_malware"){echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build ACLS:$category skipped\n";continue;}
			
			$category=$this->TransFormCategoryName($category);
			if(isset($GLOBALS["CATEGORIES_SKIPPED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category skipped\n";continue;}
			if(!isset($GLOBALS["CATEGORIES_ADDED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}
			
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEFAULT]: ufdbGuard build DEFAULT: ACLS:$category banned={$ligne["modeblk"]}\n";}
			
			if(!isset($GLOBALS["PERSONALCATZ"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build DEFAULT (PERSO) no such category \"$category\"\n";}else{
				echo "Starting......: ".date("H:i:s")." ufdbGuard build DEFAULT (PERSO) category \"$category\" = {$GLOBALS["PERSONALCATZ"][$category]}\n";
			}
			
			
			if($ligne["modeblk"]==0){
				if(isset($GLOBALS["PERSONALCATZ"][$category])){$banns[$category]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
				if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$banns[$category]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
				if(isset($GLOBALS["UFDBART"][$category])){$banns[$category]="!{$GLOBALS["UFDBART"][$category]}";}
			}
			
			if($ligne["modeblk"]==1){
				if(isset($GLOBALS["PERSONALCATZ"][$category])){$allows[$category]="{$GLOBALS["PERSONALCATZ"][$category]}";}
				if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$allows[$category]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
				if(isset($GLOBALS["UFDBART"][$category])){$allows[$category]="{$GLOBALS["UFDBART"][$category]}";}
			}			
		}
		
		
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for 0 \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=0";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:0 FATAL !!! $q->mysql_error\n";}
		while($blks=mysql_fetch_array($results,MYSQL_ASSOC)){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM webfilter_blkgp WHERE ID=$groupid"));
			if($ligne2["enabled"]==0){continue;}
		
			$sql="SELECT `category` FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
			$resultsCatz=$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:0 webfilter_blkid=$groupid FATAL !!! $q->mysql_error\n";continue;}
			while($categoryLine=mysql_fetch_array($resultsCatz,MYSQL_ASSOC)){
				
				$category=$categoryLine["category"];
				
				
				$category=$q->CategoryShellEscape($category);
				if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: Category: {$categoryLine["category"]}=$category blacklist=$blacklist\n";}
				
				if($blacklist==0){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$banns[$category]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$banns[$category]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$banns[$category]="!{$GLOBALS["UFDBART"][$category]}";}
				}
		
				if($blacklist==1){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$allows[$category]="{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$allows[$category]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$allows[$category]="{$GLOBALS["UFDBART"][$category]}";}
				}
		
			}
				
		}
		
		if(isset($GLOBALS["TIMESRULES"][0])){
			if($GLOBALS["TIMESRULES"][0]["MATCH"]<>null){
				if($GLOBALS["TIMESRULES"][0]["MATCH"]=="none"){$GLOBALS["TIMESRULES"][0]["MATCH"]="within";}
					$time=" {$GLOBALS["TIMESRULES"][0]["MATCH"]} timeR0 ";
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule to time timeR0...\n";
				}else{
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule has no time...\n";
				}
			}
		
		
		$RewriteRules=unserialize(base64_decode($this->DansGuardianDefaultMainRule["RewriteRules"]));
		$RR=array();
		
		if($this->UpdateUtilityRedirectEnable==1){$RR[]="ReWriteKasper";}
		
		if( (isset($RewriteRules)) && (is_array($RewriteRules)))   {
			while (list ($rewrite_rule_id,$nomatch) = each ($RewriteRules) ){
				if(!is_numeric($rewrite_rule_id)){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] \$rewrite_rule_id=$rewrite_rule_id is not a numeric\n";}continue;}
				if(!isset($this->AVAILABLE_REWRITE_RULES[$rewrite_rule_id])){if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard [DEBUG] \$rewrite_rule_id=$rewrite_rule_id is not set\n";}continue;}
				$RR[]="ReWriteRule{$rewrite_rule_id}";
			}
			
			if(count($RR)>0){$rewrite_rules="\t\trewrite ".@implode(" ", $RR);}
			
		}
		
		if(isset($this->BANNED_FILES_RULES[0])){
			$bannedfiles=" !".$this->BANNED_FILES_RULES[0]." ";
		}
		
		if(isset($this->BANNED_EXTDOMS_RULES[0])){
			$bannedExtDomains=" !".$this->BANNED_EXTDOMS_RULES[0]." ";
		}

		if(isset($this->AVAILABLE_REGEX_RULES[0])){
			$bannedRegexP=" !".$this->AVAILABLE_REGEX_RULES[0]." ";
		}
		
		if($MyRuleGoogleSafeSearch==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule safesearch is enabled...\n";
			$banns["safesearch"]="!safesearch";
		}
		
		if($this->DansGuardianDefaultMainRule["UseSecurity"]==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS default rule Check SSL is enabled...\n";
			$banns["security"]="!security";			
		}

		$bannsFINAL=array();
		$allowsFINAL=array();
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($banns)." items\n";}
		
		while (list ($category, $line) = each ($banns) ){$bannsFINAL[]=$line;}
		while (list ($category, $line) = each ($allows) ){$allowsFINAL[]=$line;}
				
		if($GLOBALS["VERBOSE"]){echo "DEBUG: Default rule: BANN: ". count($bannsFINAL)." items\n";}
		$arrayDEF[]="\t\"default\"$time{";
		if($rewrite_rules<>null){$arrayDEF[]=$rewrite_rules;}
		$arrayDEF[]="\t\tpass ".trim(@implode(" ", $allowsFINAL)." $bannedExtDomains$bannedfiles$bannedRegexP".@implode(" ", $bannsFINAL)).$endofrule;
		$arrayDEF[]="\t\tredirect $RedirectUriDEF?$redirect_pattern";
		$arrayDEF[]="\t}";	
		
		$acls_default=@implode("\n", $arrayDEF);
		$DefaultPosition=$this->DansGuardianDefaultMainRule["defaultPosition"];
		if(!is_numeric($DefaultPosition)){$DefaultPosition=0;}
		
		if($DefaultPosition==0){
			$array[]=$acls_default;
		}
		
		echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS DefaultPosition = $DefaultPosition\n";
		$bannedfiles=null;
		if(is_array($GLOBALS["SRC"])){
			while (list ($RULE_ID,$groupname) = each ($GLOBALS["SRC"]) ){
				echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS `$groupname` ID:$RULE_ID\n";
				$allows=array();
				$banns=array();
				$time=null;
				$else=null;
				echo "Starting......: ".date("H:i:s")." ufdbGuard rule `$groupname`\n";
				
				$redirect_pattern="?rule-id=$RULE_ID&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
				$RedirectUriArray=$this->build_acls_uriFromRuleID($RULE_ID);
				$RedirectUri=$RedirectUriArray[0];
				$RedirectUriSSL=$RedirectUriArray[1];
				if(!isset($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"])){$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]=null;}
				
				if($RedirectUri<>$this->SquidGuardIPWeb){$redirect_pattern=null;}
				if($RedirectUriSSL<>$this->SquidGuardIPWebSSL){$redirect_pattern=null;}
				
				if($GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]<>null){
						$time=" {$GLOBALS["TIMESRULES"][$RULE_ID]["MATCH"]} timeR{$RULE_ID} ";
					}else{
					if($GLOBALS["VERBOSE"]){echo " ***** ***** TIME : $groupname: $RULE_ID rule has no time\n";}
					echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS rule:`$groupname` $RULE_ID rule has no time...\n";
				}			
				
				if(isset($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
					if(is_numeric($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])){
						$else=" else {\n".$this->compile_categories_fromruleid($GLOBALS["TIMESRULES"][$RULE_ID]["NEXT"])."\n\t\tredirect $RedirectUri$redirect_pattern\n\t}";
					}
				}
				
				$groupname=str_replace(" ", "-", $groupname);
				$compile_categories_fromruleid=$this->compile_categories_fromruleid($RULE_ID);
				$compile_rewriterules_fromruleid=$this->compile_rewriterules_fromruleid($RULE_ID);
				
				
				$q10=new mysql_squid_builder();
				$groupname=$q10->StripBadChars_hostname($groupname);
				$array[]="\t\"$groupname\"$time{";
				if($compile_rewriterules_fromruleid<>null){$array[]=$compile_rewriterules_fromruleid;}
				if($compile_categories_fromruleid<>null){$array[]=$compile_categories_fromruleid;}
				$array[]="\t\tredirect $RedirectUri$redirect_pattern";
				$array[]="\t}$else\n";	
			}
			
		}
		
		$array[]=$this->build_acls_isp();
		
		if($DefaultPosition==1){
			$array[]=$acls_default;
		}		
		
		$array[]="}\n\n";
		
		
		return @implode("\n", $array);
		
		
	}
	
	private function TransFormCategoryName($category){
			$category=trim(strtolower($category));
			$category=str_replace("/", "_", $category);
			$category=str_replace("-", "_", $category);
			$category=str_replace(" ", "_", $category);
			if($category=="agressive"){$category="agressivecat";}
			return $category;		
		
	}
	
	
	private function build_acls_isp(){
		
		$q=new mysql_squid_builder();
		$sql="SELECT userid FROM usersisp WHERE enabled=1 AND LENGTH(publicip)>3";
		$results=$q->QUERY_SQL($sql);
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
			$array[]=$this->build_acls_isp_catz($ligne["userid"],$q);
			
			
		}
		
		return @implode("\n", $array);
		
	}
	
	private function build_acls_isp_catz($userid,$q){
		
		  if(!isset($GLOBALS["usersisp_blkcatz"])){
		  		$GLOBALS["usersisp_blkcatz"]["ALLOW"]=array();
		  		$GLOBALS["usersisp_blkcatz"]["BANN"]=array();
				$sql="SELECT `category` FROM usersisp_blkcatz";
				$results=$q->QUERY_SQL($sql);	
				while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
					$category=$this->TransFormCategoryName($ligne["category"]);
					if(isset($GLOBALS["CATEGORIES_SKIPPED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category skipped\n";continue;}
					if(!isset($GLOBALS["CATEGORIES_ADDED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}					
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$GLOBALS["usersisp_blkcatz"]["BANN"][]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$GLOBALS["usersisp_blkcatz"]["BANN"][]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$GLOBALS["usersisp_blkcatz"]["BANN"][]="!{$GLOBALS["UFDBART"][$category]}";}				
				}
				
				$sql="SELECT `category` FROM usersisp_blkwcatz";
		 		$results=$q->QUERY_SQL($sql);	
				while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
					$category=$this->TransFormCategoryName($ligne["category"]);
					if(isset($GLOBALS["CATEGORIES_SKIPPED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category skipped\n";continue;}
					if(!isset($GLOBALS["CATEGORIES_ADDED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}					
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$GLOBALS["usersisp_blkcatz"]["ALLOW"][]="{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$GLOBALS["usersisp_blkcatz"]["ALLOW"][]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$GLOBALS["usersisp_blkcatz"]["ALLOW"][]="{$GLOBALS["UFDBART"][$category]}";}				
				}				
		  }
			
				
			$sql="SELECT `category`,blck FROM usersisp_catztables WHERE userid=$userid";
			$results=$q->QUERY_SQL($sql);
			while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){	
				
				$category=$this->TransFormCategoryName($ligne["category"]);
				if($GLOBALS["VERBOSE"]){echo "DEBUG:: userid:$userid category:{$ligne["category"]} ($category)\n";}
				if($category==null){continue;}
				if(isset($GLOBALS["CATEGORIES_SKIPPED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category skipped\n";continue;}
				if(!isset($GLOBALS["CATEGORIES_ADDED"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build ACLS:$category not added\n";continue;}
			
				if($ligne["blck"]==0){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$banns[]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$banns[]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$banns[]="!{$GLOBALS["UFDBART"][$category]}";}
				}
				if($ligne["blck"]==1){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$allows[]="{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$allows[]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$allows[]="{$GLOBALS["UFDBART"][$category]}";}
				}					
				
			}
			
			//cleaning
			$ZALLOW=array();
			$ZBANN=array();
			$CLEAN_ALLOW=array();
			$CLEAN_BANN=array();
			
			reset($GLOBALS["usersisp_blkcatz"]["ALLOW"]);
			while (list ($a,$b) = each ($GLOBALS["usersisp_blkcatz"]["ALLOW"]) ){$CLEAN_ALLOW[$b]=$b;}
			while (list ($a,$b) = each ($allows) ){$CLEAN_ALLOW[$b]=$b;}
			
			reset($GLOBALS["usersisp_blkcatz"]["BANN"]);
			while (list ($a,$b) = each ($GLOBALS["usersisp_blkcatz"]["BANN"]) ){$CLEAN_BANN[$b]=$b;}
			while (list ($a,$b) = each ($banns) ){$CLEAN_BANN[$b]=$b;}

			while (list ($a,$b) = each ($CLEAN_ALLOW) ){$ZALLOW[]=$b;}
			while (list ($a,$b) = each ($CLEAN_BANN) ){$ZBANN[]=$b;}
			
			
			$redirect_pattern="rule-id=account-$userid&clientaddr=%a&clientname=%n&clientuser=%i&clientgroup=%s&targetgroup=%t&url=%u";
			$groupname="account$userid";

			$array[]="\t\"$groupname\"{";
			
			if(!isset($GLOBALS["ISP_ERROR_PAGE"]["account$userid"])){
				$GLOBALS["ISP_ERROR_PAGE"]["account$userid"]=$GLOBALS["ISP_ERROR_PAGE"]["DEFAULT"];
			}
			
				$array[]="\t\tpass ".trim(@implode(" ", $ZALLOW)."$bannedExtDomains$bannedfiles$bannedRegexP ".@implode(" ", $ZBANN))." all";
				$array[]="\t\tredirect {$GLOBALS["ISP_ERROR_PAGE"]["account-$userid"]}/exec.squidguard.php?$redirect_pattern";
			$array[]="\t}\n";				
			
		return @implode("\n", $array);
			
	}			
	
	
	private function compile_rewriterules_fromruleid($RULE_ID){
		$q=new mysql_squid_builder();
		$sql="SELECT RewriteRules FROM webfilter_rules WHERE ID=$RULE_ID";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$RewriteRules=unserialize(base64_decode($ligne["RewriteRules"]));
		$rewrite_rules=null;
		
		if($this->UpdateUtilityRedirectEnable==1){
			$rewrite_rules="\t\trewrite ReWriteKasper";
		}
		
		if(  (isset($RewriteRules)) && is_array($RewriteRules) ){
			
			if($this->UpdateUtilityRedirectEnable==1){
				$RR[]="ReWriteKasper";
			}
			
			while (list ($rewrite_rule_id,$nomatch) = each ($RewriteRules) ){
				if(!is_numeric($rewrite_rule_id)){continue;}
				if(isset($this->AVAILABLE_REWRITE_RULES[$rewrite_rule_id])){$RR[]="ReWriteRule{$rewrite_rule_id}";}
			}
			
			
			
			if(count($RR)>0){$rewrite_rules="\t\trewrite ".@implode(" ", $RR);}
		}
		
		return $rewrite_rules;
				
	}
	
	
	
	
	
	private function compile_categories_fromruleid($RULE_ID){
		$q=new mysql_squid_builder();
		$allows=array();
		$banns=array();
		if(!isset($GLOBALS["EnableGoogleSafeSearch"])){$sock=new sockets();$GLOBALS["EnableGoogleSafeSearch"]=$sock->GET_INFO("EnableGoogleSafeSearch");if(!is_numeric($GLOBALS["EnableGoogleSafeSearch"])){$GLOBALS["EnableGoogleSafeSearch"]=1;}}
		$sql="SELECT GoogleSafeSearch,groupmode,endofrule,UseSecurity FROM webfilter_rules WHERE ID='$RULE_ID'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["groupmode"]==0){
			echo "Starting......: ".date("H:i:s")." ufdbGuard rule:$RULE_ID deny all users...\n";
			return "\t\tpass none";
		}
		$endofrule=$ligne["endofrule"];
		if($endofrule<>null){$endofrule=" $endofrule";}
		if($GLOBALS["EnableGoogleSafeSearch"]==1){$ligne["GoogleSafeSearch"]=0;}
		$MyRuleGoogleSafeSearch=$ligne["GoogleSafeSearch"];
		$UseSecurity=$ligne["UseSecurity"];
		if(!is_numeric($UseSecurity)){$UseSecurity=0;}
			
		if($GLOBALS["VERBOSE"]){echo "\n\nStarting......: ".date("H:i:s")."  ***** CHECK RULE $RULE_ID *****\n";}
		
		$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$RULE_ID";
		$q=new mysql_squid_builder();
		$results2=$q->QUERY_SQL($sql);
		
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		while($blks=mysql_fetch_array($results2,MYSQL_ASSOC)){
				$bannedExtDomains=null;
				$bannedfiles=null;
				$bannedRegexP=null;
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID category `{$blks["category"]}\n";}
				if(isset($ARRAY["{$blks["modeblk"]}$RULE_ID{$blks["category"]}"])){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." {$blks["category"]} Alreay set, SKIP\n";}
					continue;}
				$category=$q->CategoryShellEscape($blks["category"]);
				if($category=="agressive"){$category="agressivecat";}
				if($category=="english_malware"){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID category `$category` skipped\n";continue;}
				
				
				if(!isset($GLOBALS["CATEGORIES_ADDED"][$category])){
					if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID category `$category` ({$blks["category"]}) is skipped Not added in GLOBALS[\"CATEGORIES_ADDED\"][$category]\n";}
					continue;
				}
				
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard build RULE:$RULE_ID : ACLS:$category banned={$blks["modeblk"]}\n";}
				
			if(!isset($GLOBALS["PERSONALCATZ"][$category])){echo "Starting......: ".date("H:i:s")." ufdbGuard build DEFAULT (PERSO) no such category \"$category\"\n";}else{
				echo "Starting......: ".date("H:i:s")." ufdbGuard build  RULE:$RULE_ID (PERSO) category \"$category\" = {$GLOBALS["PERSONALCATZ"][$category]}\n";
			}
				
				
				if($blks["modeblk"]==0){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$banns[$category]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$banns[$category]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$banns[$category]="!{$GLOBALS["UFDBART"][$category]}";}
				}
				if($blks["modeblk"]==1){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$allows[$category]="{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$allows[$category]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$allows[$category]="{$GLOBALS["UFDBART"][$category]}";}
				}				
				
				
				$ARRAY["{$blks["modeblk"]}$RULE_ID{$blks["category"]}"]=true;
			}
			
		if($GLOBALS["VERBOSE"]){echo "\n\n ****************** \n\n Checks Groups for $RULE_ID \n\n ****************** \n\n";}
			
		$sql="SELECT webfilter_blkid,blacklist FROM webfilter_blklnk WHERE webfilter_ruleid=$RULE_ID";
		$q=new mysql_squid_builder();
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID FATAL !!! $q->mysql_error\n";}
		while($blks=mysql_fetch_array($results,MYSQL_ASSOC)){
			$groupid=$blks["webfilter_blkid"];
			$blacklist=$blks["blacklist"];
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT groupname,enabled FROM webfilter_blkgp WHERE ID=$groupid"));
			if($GLOBALS["VERBOSE"]){echo "DEBUG: {$ligne2["groupname"]}: Group id:$groupid: blacklist=$blacklist\n";}
			
			if($ligne2["enabled"]==0){continue;}
				
			$sql="SELECT `category` FROM webfilter_blkcnt WHERE `webfilter_blkid`='$groupid'";
			$resultsCatz=$q->QUERY_SQL($sql);
			if(!$q->ok){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID webfilter_blkid=$groupid FATAL !!! $q->mysql_error\n";continue;}
			while($categoryLine=mysql_fetch_array($resultsCatz,MYSQL_ASSOC)){
				$category=$categoryLine["category"];
				$category=$q->CategoryShellEscape($category);
				if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." ufdbGuard ruleid:$RULE_ID {$categoryLine["category"]} $category\n";}
				if($blacklist==0){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$banns[$category]="!{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$banns[$category]="!{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$banns[$category]="!{$GLOBALS["UFDBART"][$category]}";}
				}
				
				if($blacklist==1){
					if(isset($GLOBALS["PERSONALCATZ"][$category])){$allows[$category]="{$GLOBALS["PERSONALCATZ"][$category]}";}
					if(isset($GLOBALS["UNIVTOULOUSE"][$category])){$allows[$category]="{$GLOBALS["UNIVTOULOUSE"][$category]}";}
					if(isset($GLOBALS["UFDBART"][$category])){$allows[$category]="{$GLOBALS["UFDBART"][$category]}";}
				}				
				
			}
			
		}
		
		//compileFinal
		
		$bannsFINAL=array();
		$allowsFINAL=array();
		while (list ($category, $line) = each ($banns) ){
			$bannsFINAL[]=$line;
		}
		while (list ($category, $line) = each ($allows) ){
			$allowsFINAL[]=$line;
		}		
		
			

		
		if(isset($this->BANNED_FILES_RULES[$RULE_ID])){
			$bannedfiles=" !".$this->BANNED_FILES_RULES[$RULE_ID]." ";
		}		

		if(isset($this->BANNED_EXTDOMS_RULES[$RULE_ID])){
			$bannedExtDomains=" !".$this->BANNED_EXTDOMS_RULES[$RULE_ID]." ";
		}	

			if(isset($this->AVAILABLE_REGEX_RULES[$RULE_ID])){
			$bannedRegexP=" !".$this->AVAILABLE_REGEX_RULES[$RULE_ID]." ";
		}		
		
		if($MyRuleGoogleSafeSearch==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build RULE:$RULE_ID GoogleSafeSearch enabled...\n";
			$banns[]="!safesearch";
		}
		
		if($UseSecurity==1){
			echo "Starting......: ".date("H:i:s")." ufdbGuard build RULE:$RULE_ID SSL check enabled...\n";
			$banns[]="!security";			
		}
			
		
		return "\t\tpass ".trim(@implode(" ", $allowsFINAL)."$bannedExtDomains$bannedfiles$bannedRegexP ".@implode(" ", $bannsFINAL)).$endofrule;
		
	}
	
	
	
	
	function compile_category($category){
		$unix=new unix();
		ufdbguard_admin_events("Order to compile $category",__FUNCTION__,__FILE__,__LINE__,"$category");
		$ufdbGenTable=$unix->find_program("ufdbGenTable");
		if(strlen($ufdbGenTable)<strlen("ufdbGenTable")){return;}
		$chmod=$unix->find_program("chmod");
		$t1=time();
		$q=new mysql_squid_builder();
		if(preg_match("#category_.+?#", $category)){$category=$q->tablename_tocat($category);}		
		
		if($category=="phishtank"){
			$main_path="/var/lib/squidguard/phishtank";
			$this->UfdbGenTable($main_path,$category);
			$compiledbs_took=$unix->distanceOfTimeInWords($t1,time(),true);
			ufdbguard_admin_events("$category compilation done\nUFDB Compilation took:$compiledbs_took",__FUNCTION__,__FILE__,__LINE__,"compile");
			return;
		}
		
		if($category=="checked"){
			$main_path="/var/lib/squidguard/checked";
			$this->UfdbGenTable($main_path,$category);
			$compiledbs_took=$unix->distanceOfTimeInWords($t1,time(),true);
			ufdbguard_admin_events("$category compilation done\nUFDB Compilation took:$compiledbs_took",__FUNCTION__,__FILE__,__LINE__,"compile");
			return;
		}


		
		if($this->EnableRemoteStatisticsAppliance==1){return;}
		$q=new mysql_squid_builder();
		$categorySource=$category;
		$category=str_replace("/", "_", $category);
		$main_path="/var/lib/squidguard/$category";
		$tablename="category_".$q->category_transform_name($category);
		$tablename=str_replace("category_category", "category", $tablename);
		$tablename_uris="categoryuris_".$q->category_transform_name($categorySource);
		$tablename_uris=str_replace("categoryuris_categoryuris", "categoryuris_", $tablename_uris);
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Domains table `$tablename`\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard check Uris table `$tablename_uris`\n";}
		ufdbguard_admin_events("$categorySource table `$tablename`",__FUNCTION__,__FILE__,__LINE__,"$categorySource");
		
		
		
		if($q->TABLE_EXISTS($tablename_uris)){
			$sql="SELECT pattern FROM $tablename_uris WHERE enabled=1 ORDER BY pattern";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$URIS[]=$ligne["pattern"];
			}
			
			if(count($URIS)>0){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $categorySource ". count($URIS)." URL(s) Items\n";}
				@file_put_contents("$main_path/urls", @implode("\n", $URIS));
				unset($URIS);
			}
			
		}
		
		
		if(!$q->TABLE_EXISTS($tablename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard $categorySource no such table \"$tablename\"\n";}
			$fake[]=md5(time()+1).".biz";
			$fake[]=md5(time()+2).".biz";
			$fake[]=md5(time()+3).".biz";
			@mkdir($main_path,0755,true);
			@file_put_contents("$main_path/domains", @implode("\n", $fake));
			if(!is_file("$main_path/urls")){shell_exec("/bin/touch $main_path/urls");}
			$this->UfdbGenTable($main_path,$category);
			return;
		}
		
		$tmpdir=$unix->TEMP_DIR();
		
		@mkdir($tmpdir,0777,true);
		shell_exec("$chmod 0777 $tmpdir/$tablename");
		$q=new mysql_squid_builder();
		$badDomains[""]=true;
		$badDomains["com"]=true;
		$badDomains["fr"]=true;
		$badDomains["de"]=true;
		$badDomains["nl"]=true;
		$badDomains["org"]=true;
		$badDomains["co"]=true;
		$badDomains["cz"]=true;
		$badDomains["de"]=true;
		$badDomains["net"]=true;
		$badDomains["us"]=true;
		$badDomains["biz"]=true;
		$badDomains["info"]=true;
		$badDomains["ee"]=true;
		
		
		while (list ($extensions,$none) = each ($badDomains) ){
			
			$q->QUERY_SQL("DELETE FROM $tablename WHERE pattern='$extensions'");
			if(!$q->ok){echo "$q->mysql_error !!! \n";}
		}
		
		
		//$sql="SELECT * FROM `$tablename` WHERE enabled=1";
		@mkdir("$tmpdir/$tablename",0777,true);
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 0777 /tmp");
		shell_exec("$chmod 0777 /tmp/$tablename");		
		
		$sql="SELECT pattern FROM $tablename WHERE enabled=1 ORDER BY pattern INTO OUTFILE '$tmpdir/$tablename/domains' LINES TERMINATED BY '\n';";
		
		$q->QUERY_SQL($sql);
		$mysqlquerry_took=$unix->distanceOfTimeInWords($t1,time(),true);
		$writetodisk_took=$unix->distanceOfTimeInWords($t1,time(),true);
		if(!$q->ok){
			if(preg_match("#writing file '(.*?)'#",$q->mysql_error,$re)){$df=$unix->find_program("df");exec("$df -h ".dirname($re[1]),$dfh);}
			squid_admin_mysql(1,"Compilation category, MySQL error",
			"`$q->mysql_error` aborting\n".@implode("\n",$dfh),__FILE__,__LINE__,"$categorySource");
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Failed to compile $categorySource $q->mysql_error\n";}
			echo $q->mysql_error."\n";
			@unlink("$tmpdir/$tablename/domains");
			return ;
		}
		
		
		@mkdir($main_path,0755,true);
		copy("$tmpdir/$tablename/domains", "$main_path/domains");
		@unlink("$tmpdir/$tablename/domains");
		if(!is_file("$main_path/urls")){shell_exec("/bin/touch $main_path/urls");}
		$t1=time();
		if(preg_match("#category_#", $category)){$category=$q->tablename_tocat($category);}
		
		$this->UfdbGenTable($main_path,$category);
		$compiledbs_took=$unix->distanceOfTimeInWords($t1,time(),true);
		
		squid_admin_mysql(2,"Compilation done SQL took:$mysqlquerry_took","Write to disk took:$writetodisk_took\nUFDB Compilation took:$compiledbs_took",__FILE__,__LINE__,"global-compile");
		$php5=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php5 /usr/share/artica-postfix/exec.squidguard.php --disks");
				
	}
	
	public function phishing_uris(){
		if(isset($GLOBALS["PHISHING_COMPILED"])){return;}
		$unix=new unix();
		$tmpdir=$unix->TEMP_DIR();
		$main_path="/var/lib/squidguard/phishing";
		@mkdir("$tmpdir/phishing_uris",0777,true);
		$chmod=$unix->find_program("chmod");
		shell_exec("$chmod 0777 $tmpdir");
		shell_exec("$chmod 0777 $tmpdir/phishing_uris");
		$sql="SELECT uri FROM uris_phishing INTO OUTFILE '$tmpdir/phishing_uris/urls' LINES TERMINATED BY '\n';";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if(preg_match("#writing file '(.*?)'#",$q->mysql_error,$re)){$df=$unix->find_program("df");exec("$df -h ".dirname($re[1]),$dfh);}
			ufdbguard_admin_events("$q->mysql_error\n".@implode("\n", $dfh),__FUNCTION__,__FILE__,__LINE__,"compile");
			echo $q->mysql_error."\n";
			@unlink("$tmpdir/phishing_uris/urls");
			return;
		}
		@copy("/$tmpdir/phishing_uris/urls", "$main_path/urls");
		@unlink("$tmpdir/phishing_uris/urls");
		$GLOBALS["PHISHING_COMPILED"]=true;
	}
	
	public function malwares_uris(){
		if(isset($GLOBALS["MALWARE_COMPILED"])){return;}
		$unix=new unix();
		$tmpdir=$unix->TEMP_DIR();
		$main_path="/var/lib/squidguard/malware";
		@mkdir("$tmpdir/phishing_uris");
		$chmod=$unix->find_program("chmod");
		@mkdir("$tmpdir/phishing_uris",0777,true);
		shell_exec("$chmod 0777 $tmpdir");
		shell_exec("$chmod 0777 $tmpdir/phishing_uris");		
		$sql="SELECT uri FROM uris_phishing INTO OUTFILE '$tmpdir/phishing_uris/urls' LINES TERMINATED BY '\n';";
		$q=new mysql_squid_builder();
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n";
			@unlink("$tmpdir/phishing_uris/urls");
			return;
		}
		@copy("$tmpdir/phishing_uris/urls", "$main_path/urls");
		@unlink("$tmpdir/phishing_uris/urls");
		$GLOBALS["MALWARE_COMPILED"]=true;
	}	
	
	
	public function UfdbGenTableCategoryName($category){
		$category_compile=$category;
		if(strpos($category, "other")>0){$category_compile=str_replace("/", "", $category);}
		if($category=="hobby/other"){$category_compile="hobbyother";}
		if(preg_match("#.+?\/(.+)#", $category_compile,$re)){$category_compile=$re[1];}	
		return $category_compile;
	}
	
	
	public function UfdbGenTable($directory,$category){
		$categoryLog=$category;
		ufdbguard_admin_events("Order to compile `$category`",__FUNCTION__,__FILE__,__LINE__,"$categoryLog");
		$unix=new unix();
		$sock=new sockets();
		$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
		if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
		$UnlockWebStats=$sock->GET_INFO("UnlockWebStats");
		if(!is_numeric($UnlockWebStats)){$UnlockWebStats=0;}				
		if($EnableRemoteStatisticsAppliance==1){return;}		
		$t=time();
		
		$ufdbGenTable=$unix->find_program("ufdbGenTable");
		if(strlen($ufdbGenTable)<strlen("ufdbGenTable")){return;}
		$category_compile=$this->UfdbGenTableCategoryName($category);
		
		
		if(strlen($category_compile)>15){
			$category_compile=str_replace("recreation_","recre_",$category_compile);
			$category_compile=str_replace("automobile_","auto_",$category_compile);
			$category_compile=str_replace("finance_","fin_",$category_compile);
			if(strlen($category_compile)>15){
				$category_compile=str_replace("_", "", $category_compile);
				if(strlen($category_compile)>15){$category_compile=substr($category_compile, strlen($category_compile)-15,15);}
			}
		}	
		
		ufdbguard_admin_events("`$category` transformed to $category_compile in $directory directory",__FUNCTION__,__FILE__,__LINE__,"$categoryLog");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." ufdbGuard Compiling $category in $directory\n";}
		
		if(!is_file("$directory/urls")){@file_put_contents("$directory/urls", "\n");}
		
		$u=" -u $directory/urls";
		$d=" -d $directory/domains";
		$EXEC_NICE=$unix->EXEC_NICE();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." $ufdbGenTable $directory/domains\n";}
		$cmd="$EXEC_NICE$ufdbGenTable -n -q -W -t $category_compile$d$u";
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." `$cmd`\n";}
		
		
		
		$results=shell_exec($cmd);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		
		
	}
	

	private function VerifyDomainCompiledPattern($pattern){
		if(!preg_match("#^(.+?)\.(.+)#",$pattern)){return false;}
		return true;
	}	
	
	
}