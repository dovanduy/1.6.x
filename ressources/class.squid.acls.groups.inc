<?php
include_once(dirname(__FILE__)."/class.squid.acls.quotas.inc");

class squid_acls_groups{
	var $GroupTypesTxt=array();
	var $ACL_ARP_ENABLED=false;
	var $RequestedGroupeType=null;
	var $UseDynamicGroupsAcls=0;
	var $smartphones_port=0;
	var $EnableKerbAuth=0;
	var $AUTH=0;	
	var $GroupTypes=array();
	var $ASROOT=false;
	var $error=null;
	private $hasProxyTransparent=0;
	private $EnableTransparent27=0;
	private $SquidBubbleMode=0;
	
	public $ARRAY_NO_ITEM=array();

	function squid_acls_groups(){
		if(posix_getuid()==0){$this->ASROOT=true;}
		$q=new mysql_squid_builder();
		$this->GroupTypes=$q->acl_GroupType;
		$sock=new sockets();
		$users=new usersMenus();
		$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
		$this->UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
		if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=0;}
		$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
		$this->hasProxyTransparent=intval($sock->GET_INFO("hasProxyTransparent"));
		$this->EnableTransparent27=intval($sock->GET_INFO("EnableTransparent27"));
		$this->SquidBubbleMode=$sock->GET_INFO("SquidBubbleMode");
		$this->smartphones_port=$sock->GET_INFO("smartphones_port");
		
		
		if(!is_numeric($this->smartphones_port)){$this->smartphones_port=0;}
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		if(!is_numeric($this->SquidBubbleMode)){$this->SquidBubbleMode=0;}
		$squid=new squidbee();
		if($squid->LDAP_AUTH==1){$this->AUTH=1;}
		if($squid->LDAP_EXTERNAL_AUTH==1){$this->AUTH=1;}
		if($squid->EnableKerbAuth==1){$this->AUTH=1;$this->UseDynamicGroupsAcls=1;}
		$this->ARRAY_NO_ITEM=$q->acl_ARRAY_NO_ITEM;

		
	}
	
	private function debugOutput($text,$function=null,$line=null){
		
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!$GLOBALS["VERBOSE"]){return;}
		if(!is_numeric($line)){
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
					
				}
			}
		}
		
		echo "DEBUG ACLS:$function::$text [$line]\n";
	}
	
	function buildacls_order($aclport=0,$aclgpid=0){
		$function=__FUNCTION__;
		$q=new mysql_squid_builder();
		if(!isset($GLOBALS["webfilters_sqacls_tested"])){
			if(!$q->FIELD_EXISTS("webfilters_sqacls", "PortDirection")){$q->QUERY_SQL("ALTER TABLE `webfilters_sqacls` ADD `PortDirection` smallint(1) NOT NULL DEFAULT '0',ADD INDEX(`PortDirection`)");}
			$GLOBALS["webfilters_sqacls_tested"]=true;
		}
		
		if(!is_numeric($aclport)){$aclport=0;}
		if(!is_numeric($aclgpid)){$aclgpid=0;}
		
		if($aclport>0){
			if($this->SquidBubbleMode==0){return;}
		}
		
		$sql="SELECT 
		webfilters_sqacls.ID,
		webfilters_sqacls.aclname,
		webfilters_sqacls.PortDirection,
		webfilters_sqacls.xORDER,
		webfilters_sqacls.aclport,
		webfilters_sqacls.aclgroup
		FROM webfilters_sqacls 
		WHERE webfilters_sqacls.enabled=1
		AND webfilters_sqacls.aclport=$aclport
		AND webfilters_sqacls.aclgpid=$aclgpid
		ORDER BY webfilters_sqacls.xORDER";
		
		$this->debugOutput("$sql", __FUNCTION__, __LINE__);

		$results = $q->QUERY_SQL($sql);
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: buildacls_order(`$aclport`,`$aclgpid`) ". mysql_num_rows($results). "... (Line ".__LINE__.")\n";}
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		$this->debugOutput("Find `by order` -> ". mysql_num_rows($results)." items", __FUNCTION__, __LINE__);
		if(!$q->ok){$this->debugOutput("$q->mysql_error", __FUNCTION__, __LINE__);}
		$conf=array();
		
		$PrefixAcls=null;
		if($aclport==0){
			if($this->SquidBubbleMode==1){
				$PrefixAcls=" MyStandPort ";
			}
		}
		if($aclport>0){$PrefixAcls=" MyPort$aclport ";}
		$PortDirectionS[1]=" NormalPorts ";
		$PortDirectionS[2]=" MyTransparentPort ";
		$PortDirectionS[3]=" MyMobilesPort ";

		$resultsPorts = $q->QUERY_SQL("SELECT ID FROM proxy_ports WHERE enabled=1");
		while ($lignePorts = mysql_fetch_assoc($resultsPorts)) {
			$ID=$lignePorts["ID"];
			$PortDirectionS[$ID]=" MyPort$ID ";
		}
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$PrefixAclsStandard=null;
			$PortDirection=$ligne["PortDirection"];
			$aclid=$ligne["ID"];
			if($PrefixAcls==null){
				if($PortDirection>0){
					if($PortDirection==2){if($this->isTransParent()==0){continue;}}
					if($PortDirection==3){if($this->smartphones_port==0){continue;}}
					if(isset($PortDirectionS[$PortDirection])){ $PrefixAclsStandard=$PortDirectionS[$PortDirection]; }
				}
			}
			
			
			$this->debugOutput("******* {$ligne["aclname"]} ******* ", __FUNCTION__, __LINE__);
			
			$this->debugOutput("buildacls_order($aclport,$aclid): SELECT httpaccess,httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
			$ligneWhat=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess,httpaccess_value,httpaccess_data 
					FROM webfilters_sqaclaccess WHERE aclid='$aclid'"));
			if(!$q->ok){echo "*** FATAL **** $q->mysql_error in line: ".__LINE__."\n";}
			
			$httpaccess_data=$ligneWhat["httpaccess_data"];
			$httpaccess=$ligneWhat["httpaccess"];
			
			$aclgroup=$ligne["aclgroup"];
			$this->debugOutput("buildacls_order($aclport,$aclid) http_access: ($httpaccess_data) [$aclid] $httpaccess/is an acl group ? `$aclgroup`",__FUNCTION__,__FILE__,__LINE__);
			if($aclgroup==1){
				$conf[]="#";
				$conf[]="#-------- ACL Group `{$ligne["aclname"]}`";
				$conf[]=$this->buildacls_order($aclport,$aclid);
				$conf[]="#----------------------------------------";
				$conf[]="#";
				continue;
			}
			
			$firstToken=null;
			if($httpaccess==null){
				$this->debugOutput("buildacls_order($aclport,$aclid) httpaccess is null, aborting!!");
				continue;
			}
			if($httpaccess=="tcp_outgoing_tos"){continue;}
			if($httpaccess=="reply_body_max_size"){continue;}
			
			if($httpaccess=="url_rewrite_access_deny"){continue;}
			if($httpaccess=="adaptation_access_deny"){continue;}
			if($httpaccess=="delay_access"){continue;}
			if($httpaccess=="tcp_outgoing_address"){continue;}
			if($httpaccess=="cache_parent"){continue;}
			if($httpaccess=="snmp_access_allow"){continue;}
			if($httpaccess=="log_access"){continue;}
			if($httpaccess=="request_header_add"){continue;}
			if($httpaccess=="deny_log"){continue;}
			
			$reverse=false;
			
			
			if($httpaccess=="deny_access_except"){$reverse=true;$firstToken="http_access deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="access_allow"){$firstToken="http_access allow$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="access_deny"){$firstToken="http_access deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="cache_deny"){$firstToken="cache deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="http_reply_access_deny"){$firstToken="http_reply_access deny$PrefixAclsStandard$PrefixAcls";}
			if($httpaccess=="http_reply_access_allow"){$firstToken="http_reply_access allow$PrefixAclsStandard$PrefixAcls";}
			
			
			
			if($httpaccess=="deny_quota_rule"){
				$this->debugOutput("  **************  deny_quota_rule ******************");
				$this->debugOutput("buildacls_order($aclport,$aclid)  -> deny_quota_rule");
				$deny_quota_rule_id=$httpaccess_data;
				if($deny_quota_rule_id==0){
					$this->debugOutput("deny_quota_rule: deny_quota_rule_id = $deny_quota_rule_id SKIP !");
					continue;
				}
				$aclquota=new squid_acls_quotas_time($deny_quota_rule_id);
				if(!$aclquota->IsValid($deny_quota_rule_id)){
					$this->debugOutput("deny_quota_rule: RULE:$deny_quota_rule_id is not valid !");
					continue;}
				$firstToken="http_access deny {$PrefixAclsStandard}{$PrefixAcls}!time_quota$deny_quota_rule_id";
			}
			
			
			
			
			
			$this->debugOutput("[$httpaccess] ->buildacls_bytype_items($aclid,$reverse)", __FUNCTION__, __LINE__);
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			$this->debugOutput("Checks: ACL $aclid Access type:`$httpaccess` ($firstToken) groups:". count($groups),__FUNCTION__,__LINE__);
			if(count($groups)==0){
				if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: buildacls_order() SKIP! type:`$httpaccess` ($firstToken) groups:". count($groups)." (Line ".__LINE__.")\n";}
				continue;
			}
			
			$this->debugOutput("buildacls_order($aclport,$aclid): *** $firstToken ".@implode(" ", $groups)." ***");
			$conf[]="$firstToken ".@implode(" ", $groups);
			
			
		}	
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [ACLS]: buildacls_order() FINAL:". count($conf)." rules (Line ".__LINE__.")\n";}
		return @implode("\n", $conf);
		
	}
	

	public function external_acl_helper_quota_builder(){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_quotas WHERE enabled=1";
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){return;}
		while ($ligne = mysql_fetch_assoc($results)) {
			$array[$ligne["xtype"]]["MAX"]=$ligne["maxquota"];
			$array[$ligne["xtype"]]["DUR"]=$ligne["duration"];
			$array[$ligne["xtype"]]["PATTERN"]=$ligne["value"];
			$DESTINATIONS=$this->external_acl_helper_quota_builder_dest($ligne["ID"]);
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [QUOTA]: {$ligne["ID"]} = ". count($DESTINATIONS)." rules\n";}
			$array[$ligne["xtype"]]["DESTINATIONS"]=$DESTINATIONS;
			
		}
		
		
		@file_put_contents("/etc/squid3/quotas_artica.db", serialize($array));
		@chmod("/etc/squid3/quotas_artica.db",0755);
		@chown("/etc/squid3/quotas_artica.db","squid");
		
	}
	
	private function external_acl_helper_quota_builder_dest($RULEID){
		$q=new mysql_squid_builder();
		
		$sql="SELECT webfilters_quotas_grp.gpid,
		webfilters_quotas_grp.zmd5, webfilters_quotas_grp.ID as LINKID,
		webfilters_sqgroups.* FROM webfilters_quotas_grp, webfilters_sqgroups
		WHERE webfilters_quotas_grp.gpid=webfilters_sqgroups.ID 
		AND webfilters_sqgroups.enabled =1
		AND webfilters_quotas_grp.ruleid=$RULEID";
		
		
		$results = $q->QUERY_SQL($sql);
		if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [QUOTA]: $RULEID = ". mysql_num_rows($results)." groups\n";}
		while ($ligne = mysql_fetch_assoc($results)) {
			
			$items=$this->ItemsInArray($ligne["gpid"]);
			if($this->ASROOT){echo "Starting......: ".date("H:i:s")." [QUOTA]: $RULEID = {$ligne["gpid"]}/{$ligne["GroupType"]} = ".count($items)." items\n";}
			$array[$ligne["GroupType"]]=$this->ItemsInArray($ligne["gpid"]);
			
		}
		
		return $array;
		
	}
	
	private function ItemsInArray($gpid){
		$q=new mysql_squid_builder();
		$sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid AND enabled=1";
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$array[$ligne["pattern"]]=$ligne["pattern"];
			
		}
		return $array;
	}
	
	public function cache_peer_explode($cache_id){
		$q=new mysql();
		$sql="SELECT * FROM squid_parents WHERE ID=$cache_id";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(trim($ligne["servername"])==null){return;}
		if(trim($ligne["server_port"])==null){return;}
		if(trim($ligne["server_type"])==null){$ligne["server_type"]="parent";}
		$NET="{$ligne["servername"]}:{$ligne["server_port"]}";
		if(isset($GLOBALS["ALREADY_CACHE_PEER"][$NET])){return;}
		$GLOBALS["ALREADY_CACHE_PEER"][$NET]=true;
		$array=unserialize(base64_decode($ligne["options"]));
		echo "Starting......: ".date("H:i:s")." [ LB ]: add `{$ligne["servername"]} backend...\n";
		$htcp=null;
		if($ligne["htcp_port"]>0){$ligne["icp_port"]=$ligne["htcp_port"];$htcp=" htcp";}
		
		
		if(!is_array($array)){
			if((!is_numeric($ligne["icp_port"]))  OR ($ligne["icp_port"]==0)){
				$array["no-query"]=null;
				$array["no-digest"]=null;
			}else{
				$array=array();
			}
		
		}
		$login=null;$parmType=null;
		
		
		while (list($num,$val)=each($array)){
			echo "\n\n***** \n $num $val *****\n\n";
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
			
			$options=implode(" ",$z)." name=Peer{$cache_id}";

			return 
			array("NET"=>$NET,
			"NAME"=>"Peer{$cache_id}",
			"LINE"=>"cache_peer {$ligne["servername"]} {$ligne["server_type"]} {$ligne["server_port"]} {$ligne["icp_port"]}$htcp $parmType$options");
		
	}
	
	
	private function cache_peer($aclid){
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='cache_parent'"));
		$cache_id=$ligne["httpaccess_data"];
		if(!is_numeric($cache_id)){$cache_id=0;}
		if($cache_id==0){
			$this->debugOutput("cache_peer:: $aclid -> no cache_peer");
			return array();
		}
		
		return $this->cache_peer_explode($cache_id);
		
		
		
	}
	
	
	function buildacls_bytype($ruletype,$aclport=0){
		$this->error=null;
		if($aclport>0){
			if($this->SquidBubbleMode==0){
				$this->error="SquidBubbleMode is not enabled";
				return;
			}
		}
		$LOCALSIPS=array();
		if($ruletype=="tcp_outgoing_address"){
			if($this->ASROOT){
				if(class_exists("unix")){
					$unix=new unix();
					$LOCALSIPS=$unix->NETWORK_ALL_INTERFACES(true);
					
				}
			}
		}
		
		$q=new mysql_squid_builder();
		$PortDirectionS[1]=" NormalPorts";
		$PortDirectionS[2]=" MyTransparentPort ";
		$PortDirectionS[3]=" MyMobilesPort ";
		$resultsPorts = $q->QUERY_SQL("SELECT ID FROM proxy_ports WHERE enabled=1");
		while ($lignePorts = mysql_fetch_assoc($resultsPorts)) {
			$ID=$lignePorts["ID"];
			$PortDirectionS[$ID]=" MyPort$ID ";
		}
		
		
		if(isset($GLOBALS["buildacls_bytype$ruletype$aclport"])){return $GLOBALS["buildacls_bytype$ruletype$aclport"];}
		
		if($GLOBALS["VERBOSE"]){echo "######### $ruletype:$aclport #########\n";}
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: Find `$ruletype`\n";}
		$rulesAcls=array();
		$sql="SELECT webfilters_sqacls.ID,
		webfilters_sqacls.aclport, 
		webfilters_sqacls.xORDER,
		webfilters_sqacls.PortDirection,
		webfilters_sqaclaccess.httpaccess_data
		FROM webfilters_sqacls, webfilters_sqaclaccess 
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		AND webfilters_sqacls.aclport=$aclport
		AND webfilters_sqaclaccess.httpaccess='$ruletype' 
		ORDER BY webfilters_sqacls.xORDER";
		
		
		$reverse=false;
		
		if($ruletype=="deny_access_except"){$reverse=true;}
		$this->debugOutput("$ruletype:$aclport MySQL AND webfilters_sqacls.aclport=$aclport",__FUNCTION__,__LINE__);
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		if(!$q->ok){$this->error=str_replace("\n"," ",$q->mysql_error);}
		
		if(mysql_num_rows($results)==0){
			$this->debugOutput("$ruletype:$aclport MySQL > ABORTING ! NO items",__FUNCTION__,__LINE__);
			$this->error="MySQL:No item";
		}
		$this->debugOutput("$ruletype:$aclport MySQL > ". mysql_num_rows($results)." items",__FUNCTION__,__LINE__);
		
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$MainRuleID=$ligne["ID"];
			
			$AddPort=true;
			$PrefixAclsPort=null;
			if($this->SquidBubbleMode==0){
				$PortDirection=$ligne["PortDirection"];
				if($PortDirection>0){
					if($PortDirection==2){if($this->isTransParent()==0){continue;}}
					if(isset($PortDirectionS[$PortDirection])){
						$PrefixAclsPort=$PortDirectionS[$PortDirection];
					}
				}	
			}
			
			$aclid=$ligne["ID"];
			if($ruletype=="deny_log"){$reverse=true;}
			
			$this->debugOutput("$ruletype:$aclport RuleID=$MainRuleID buildacls_bytype_items($aclid,$reverse)",__FUNCTION__,__LINE__);
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			if(count($groups)==0){
				$this->debugOutput("$ruletype:$aclport No groups..");
				$this->error=$this->error." $aclid=0;";
				continue;
			}
			$valueToAdd=null;
			
			
			if($ruletype=="tcp_outgoing_tos"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($valueToAdd==null){continue;}
				$valueToAdd=$valueToAdd." ";
			}
			
			if($ruletype=="reply_body_max_size"){
				$valueToAdd=$ligne["httpaccess_data"];
				if(!is_numeric($valueToAdd)){continue;}
				$valueToAdd=$valueToAdd." MB ";
			}
			
			
			if($ruletype=="cache_parent"){
				$cache_peer=$this->cache_peer($aclid);
				if(!is_array($cache_peer)){continue;}
				$NET=$cache_peer["NET"];
				$LINE=$cache_peer["LINE"];
				$NAME=$cache_peer["NAME"];
				$rulesAcls[$NET]["LINE"]=$LINE;
				$rulesAcls[$NET]["NAME"]=$NAME;
				$rulesAcls[$NET]["PORTS_ACLS"]=$PrefixAclsPort;
				$rulesAcls[$NET]["GROUPS"][]=$groups;
				continue;
			}
			
			
			
			if($ruletype=="tcp_outgoing_address"){
				
				$valueToAdd=$ligne["httpaccess_data"];
				if($GLOBALS["VERBOSE"]){echo "tcp_outgoing_address \"$valueToAdd\" PORT:{$aclport}\n";}
				if($valueToAdd==null){continue;}
				if(count($LOCALSIPS)>0){
					if(preg_match("#[0-9\.]+#", $valueToAdd)){
						$valueToAdd=trim($valueToAdd);
						if(!isset($LOCALSIPS[$valueToAdd])){
							$GLOBALS["tcp_outgoing_address_errors"][]="Error tcp_outgoing_address $valueToAdd NO SUCH ADDRESS";
							if($GLOBALS["VERBOSE"]){echo "tcp_outgoing_address \"$valueToAdd\" PORT:{$aclport} NO SUCH ADDRESS !!!\n";}
							continue;
						}
					}
				}else{
					$GLOBALS["tcp_outgoing_address_errors"][]="LOCALSIPS Array = 0";
				}
				
				
				$valueToAdd=$valueToAdd." ";
			}
			

			
			if($ruletype=="request_header_add"){
				$httpaccess_data=unserialize(base64_decode($ligne["httpaccess_data"]));
				$request_header_add_name=$httpaccess_data["header_name"];
				$request_header_add_value=$httpaccess_data["header_value"];
				if(trim($request_header_add_name)==null){continue;}
				if(trim($request_header_add_value)==null){continue;}				
				$valueToAdd="$request_header_add_name \"$request_header_add_value\" ";
				
			}
			
			if($ruletype=="log_access"){
				$valueToAdd="stdio:/var/log/squid/access_acl_$aclid.csv csv_acls ";
			}	
				
			
			$rulesAcls[]=$valueToAdd.$PrefixAclsPort.@implode(" ", $groups);
			
			
		}
		
		$GLOBALS["buildacls_bytype$ruletype"]=$rulesAcls;
		return $rulesAcls;
	}
	
	public function buildacls_bytype_items($aclid=null,$reverse=false){
		$prefix=null;
		if($reverse){$prefix="!";}
		$acls_name=array();
		if(!is_numeric($aclid)){
			
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
						
				}
			}
			
			$this->debugOutput("$aclid: no aclid as been sent function:$function() in $line", __FUNCTION__, __LINE__);
		}
		$sql="SELECT 
		webfilters_sqacllinks.gpid,
		webfilters_sqacllinks.negation,
		webfilters_sqacllinks.zOrder,
		webfilters_sqgroups.GroupType,
		webfilters_sqgroups.GroupName,
		webfilters_sqgroups.ID 
		FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND webfilters_sqacllinks.aclid=$aclid
		ORDER BY webfilters_sqacllinks.zOrder";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		
		if(!$q->ok){
			
			if(function_exists("debug_backtrace")){
				$trace=@debug_backtrace();
				if(isset($trace[1])){
					$function=$trace[1]["function"];
					$line=$trace[1]["line"];
			
				}
			}
			
			echo "Sent by function:$function() in $line -> FATAL !!! $q->mysql_error ".__FUNCTION__."() in line: ".__LINE__."\n";
			echo "$sql\n";
		}
		
		$this->debugOutput("$aclid: ". mysql_num_rows($results)." items", __FUNCTION__, __LINE__);
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;
		$ARRAY_AUTH["dynamic_acls"]=true;
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$GroupType=$ligne["GroupType"];
			$neg=null;
			$this->debugOutput("$aclid `$GroupType` ID:{$ligne['ID']}", __FUNCTION__, __LINE__);
			
			
			if($GroupType=="proxy_auth_ads"){
				if($this->EnableKerbAuth==0){
					$this->debugOutput("$aclid: proxy_auth_ads by EnableKerbAuth is disabled, SKIP", __FUNCTION__, __LINE__);
					continue;
				}
				
				if($this->UseDynamicGroupsAcls==0){
					$this->debugOutput("$aclid: proxy_auth_ads by UseDynamicGroupsAcls is disabled, SKIP", __FUNCTION__, __LINE__);
					continue;
				}
				
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: proxy_auth_ads -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";	
				continue;
			}
			
			if($GroupType=="categories"){
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: categories -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";
				continue;
			}
			
			

			if(isset($ARRAY_AUTH[$GroupType])){
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: Is an Auth [$GroupType] -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";
				continue;
			}
			
			

			if($GroupType=="all"){
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: all -> {$neg}{$prefix}all", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}all";
				continue;
			}	

			if(isset($q->acl_ARRAY_NO_ITEM[$GroupType])){
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: acl_ARRAY_NO_ITEM -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";
				continue;
				
			}
			
			
			
			if($GroupType=="NudityScan"){
				if(!$GLOBALS["ArtcExtrnNudeScanner"]){
					$this->debugOutput("$aclid: NudityScan ArtcExtrnNudeScanner is disabled, SKIP", __FUNCTION__, __LINE__);
					continue;
				}
				
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: NudityScan -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";	
				continue;
			}			
			
			if($GroupType=="proxy_auth"){
				if($this->AUTH==0){continue;}
			}
			
			
			$sql="SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1";
			//$this->debugOutput("$aclid `$GroupType` ID:{$ligne['ID']} `$sql`", __FUNCTION__, __LINE__);
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$items=$ligne2["tcount"];
			$this->debugOutput("$aclid: `$GroupType` ID:{$ligne['ID']} webfilters_sqitems  => $items item(s), CONTINUE", __FUNCTION__, __LINE__);
			if($items==0){
				$this->debugOutput("$aclid:GroupID {$ligne['ID']} 0 items, STOP", __FUNCTION__, __LINE__);
				continue;
			}
			
			if($ligne["negation"]==1){$neg="!";}
			$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";	
			
		}	
		$this->debugOutput("$aclid:". count($acls_name)." final items", __FUNCTION__, __LINE__);
		return $acls_name;
	}
	
	private function acl_tpl($aclid){
		$sql="SELECT acltpl FROM webfilters_sqgroups WHERE ID='$aclid'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["acltpl"]==null){return;}
		
		if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){
			return;
		}
		
		$sql="SELECT template_name,lang FROM squidtpls WHERE `zmd5`='{$ligne["acltpl"]}'";
		if(is_numeric($ligne["lang"])){$ligne["lang"]="en";}
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!preg_match("#ERR_.+#", $ligne["template_name"])){$ligne["template_name"]="ERR_".$ligne["template_name"];}
		$template_path="/usr/share/squid3/errors/{$ligne["lang"]}/{$ligne["template_name"]}";
		if(!is_file($template_path)){return;}
		
	}
	
	
	function GroupTypeToText($GroupType){
		return $this->GroupTypes[$GroupType];
		
	}
	
	function aclrule_edittype($aclid,$type,$value,$NexValue=null){
		$md5=md5("$aclid$type");
		$q=new mysql_squid_builder();
		if(!$q->FIELD_EXISTS("webfilters_sqaclaccess", "httpaccess_data")){
			$q->QUERY_SQL("ALTER TABLE `webfilters_sqaclaccess` ADD `httpaccess_data` TEXT NOT NULL");
		}
		
		if($q->FIELD_TYPE("webfilters_sqaclaccess","httpaccess_data","squidlogs")<>"text"){
			$sql="ALTER TABLE `webfilters_sqaclaccess` CHANGE `httpaccess_data` `httpaccess_data` TEXT NOT NULL";
			$q->QUERY_SQL($sql,'squidlogs');
			if(!$q->ok){writelogs("Fatal: $this->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}	

		
		
		if($value==0){
			
			$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE zmd5='$md5'");
			if(!$q->ok){echo $q->mysql_error;return false;}
			return true;
		}
		
		
		//$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
		$sql="INSERT IGNORE INTO webfilters_sqaclaccess (zmd5,aclid,httpaccess,httpaccess_value,httpaccess_data) VALUES('$md5','$aclid','$type',1,'$NexValue')";
		//$sql="UPDATE webfilters_sqaclaccess SET httpaccess='$type',httpaccess_value=1,httpaccess_data='$NexValue' WHERE aclid='$aclid'";
		
		
		//echo $sql;
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;
		
	}
	private function isTransParent(){
		if(isset($GLOBALS[__CLASS__."/".__FUNCTION__])){return $GLOBALS[__CLASS__."/".__FUNCTION__];}		
		$sock=new sockets();
		$GLOBALS[__CLASS__."/".__FUNCTION__]=$sock->GET_INFO("hasProxyTransparent");
		if($GLOBALS[__CLASS__."/".__FUNCTION__]==null){$GLOBALS[__CLASS__."/".__FUNCTION__]=0;}
		return $GLOBALS[__CLASS__."/".__FUNCTION__];
	}
	private function isBubble(){
		if(isset($GLOBALS[__CLASS__."/".__FUNCTION__])){return $GLOBALS[__CLASS__."/".__FUNCTION__];}		
		$sock=new sockets();
		$GLOBALS[__CLASS__."/".__FUNCTION__]=$sock->GET_INFO("SquidBubbleMode");
		if($GLOBALS[__CLASS__."/".__FUNCTION__]==null){$GLOBALS[__CLASS__."/".__FUNCTION__]=0;}
		return $GLOBALS[__CLASS__."/".__FUNCTION__];
	}
	
	
	
	
	function ACL_MULTIPLE_EXPLAIN($aclid,$ACCESSEnabled,$aclgroup=0,$miniadm=false){
		$q=new mysql_squid_builder();
		$cache_parent_info=null;
		$Mainjs="Loadjs('squid.acls-rules.php?Addacl-js=yes&ID=$aclid&t={$_GET["t"]}',true)";
		if($miniadm){
			$prefix="<a href=\"javascript:blur();\" OnClick=\"javascript:$Mainjs\">";
		}
		
		if($aclgroup==1){
			$explains=array();
			$sql="SELECT ID,enabled FROM webfilters_sqacls WHERE aclgpid=$aclid";
			$results = $q->QUERY_SQL($sql);
			while ($ligne = mysql_fetch_assoc($results)) {
				$explains[]="<div>".$this->ACL_MULTIPLE_EXPLAIN($ligne["ID"],$ligne["enabled"],0)."</div>";
			}
			
				return @implode("", $explains);
		}
		
		$errorTransparentModeIsDisabled=" <i>{the_transarent_mode_is_disabled_acl}</i>";
		$isTransParent=$this->isTransParent();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT PortDirection FROM webfilters_sqacls WHERE ID='$aclid'"));
		$PortDirection=$ligne["PortDirection"];
		if(!is_numeric($PortDirection)){$PortDirection=0;}
		
		$isBubble=$this->isBubble();
		$methodConnect=null;
		if(!isset($GLOBALS["PORTSDIRECTIONS"])){
			$GLOBALS["PORTSDIRECTIONS"][0]="{all_methods}";
			$GLOBALS["PORTSDIRECTIONS"][1]="{standard_method}";
			$GLOBALS["PORTSDIRECTIONS"][2]="{transparent_method}";
			$GLOBALS["PORTSDIRECTIONS"][3]="{smartphones_port}";
			
			$resultsPorts = $q->QUERY_SQL("SELECT ID,port,ipaddr FROM proxy_ports WHERE enabled=1");
			while ($lignePorts = mysql_fetch_assoc($resultsPorts)) {
				$GLOBALS["PORTSDIRECTIONS"][$lignePorts["ID"]]="TCP {$lignePorts["ipaddr"]}:{$lignePorts["port"]}";
			}
		
		}
		
		if($isBubble==0){
			$methodConnect="{when_connecting_to_method} &laquo;{$GLOBALS["PORTSDIRECTIONS"][$PortDirection]}&raquo;<br>";
				
		}
		
		
		$linkcolor="black";
		$WarnColor="#EA0C0C";
		if($ACCESSEnabled==0){$linkcolor="#8a8a8a";$WarnColor="#8a8a8a";}
		$errorNoObjects="<div><img src='img/status_warning.png' align='left' style='margin-right:4px'><div style='font-size:11px;font-weight:bold;color:$WarnColor;margin-top:1px'>{error_acl_no_object}</div></div>";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='url_rewrite_access_deny'"));
		$url_rewrite_access_deny=$ligne["httpaccess_value"];
		if(!is_numeric($url_rewrite_access_deny)){$url_rewrite_access_deny=0;}
	
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='access_deny'"));
		$access_deny=$ligne["httpaccess_value"];
		if(!is_numeric($access_deny)){$access_deny=0;}	
	
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='adaptation_access_deny'"));
		$adaptation_access_deny=$ligne["httpaccess_value"];
		if(!is_numeric($adaptation_access_deny)){$adaptation_access_deny=0;}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='cache_deny'"));
		$cache_deny=$ligne["httpaccess_value"];
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='access_allow'"));
		$access_allow=$ligne["httpaccess_value"];
		if(!is_numeric($access_allow)){$access_allow=0;}	

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='snmp_access_allow'"));
		$snmp_access_allow=$ligne["httpaccess_value"];
		if(!is_numeric($snmp_access_allow)){$snmp_access_allow=0;}	

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='log_access'"));
		$log_access=$ligne["httpaccess_value"];
		if(!is_numeric($log_access)){$log_access=0;}		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='deny_log'"));
		$deny_log=$ligne["httpaccess_value"];
		if(!is_numeric($deny_log)){$deny_log=0;}
				
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='deny_access_except'"));
		$deny_access_except=$ligne["httpaccess_value"];
		if(!is_numeric($deny_access_except)){$deny_access_except=0;}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='cache_parent'"));
		$cache_parent=$ligne["httpaccess_value"];
		if(!is_numeric($cache_parent)){$cache_parent=0;}
		if($cache_parent==1){
			$parentid=trim($ligne["httpaccess_data"]);
			if($parentid>0){
				$qz=new mysql();
				$ligne2=mysql_fetch_array($qz->QUERY_SQL("SELECT * FROM squid_parents WHERE ID='$parentid'",'artica_backup'));
				if($ligne2["servername"]<>null){
					$cache_parent_info="{$ligne2["servername"]}:{$ligne2["server_port"]}";
				}
			}
		}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='tcp_outgoing_tos'"));
		$tcp_outgoing_tos=$ligne["httpaccess_value"];
		$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
		if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}	
		if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='tcp_outgoing_address'"));
		$tcp_outgoing_address=$ligne["httpaccess_value"];
		$tcp_outgoing_address_value=$ligne["httpaccess_data"];
		if(!is_numeric($tcp_outgoing_address)){$tcp_outgoing_address=0;}	
		if($tcp_outgoing_tos_value==null){$tcp_outgoing_address=0;}		

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='reply_body_max_size'"));
		$reply_body_max_size=$ligne["httpaccess_value"];
		$reply_body_max_size_value=$ligne["httpaccess_data"];
		if(!is_numeric($reply_body_max_size)){$reply_body_max_size=0;}
		if(!is_numeric($reply_body_max_size_value)){$reply_body_max_size=0;}		
		
		

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='delay_access'"));
		$delay_access=$ligne["httpaccess_value"];
		$delay_access_id=$ligne["httpaccess_data"];
		if(!is_numeric($delay_access)){$delay_access=0;}	
		if(!is_numeric($delay_access_id)){$delay_access_id=0;}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='http_reply_access_deny'"));
		$http_reply_access_deny=$ligne["httpaccess_value"];
		if(!is_numeric($http_reply_access_deny)){$http_reply_access_deny=0;}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='http_reply_access_allow'"));
		$http_reply_access_allow=$ligne["http_reply_access_allow"];
		if(!is_numeric($http_reply_access_allow)){$http_reply_access_allow=0;}		


		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='request_header_add'"));
		$request_header_add=$ligne["httpaccess_value"];
		$request_header_add_value=unserialize(base64_decode($ligne["httpaccess_data"]));
		if(!is_numeric($request_header_add)){$request_header_add=0;}else{
			$request_header_add_name=$request_header_add_value["header_name"];
			$request_header_add_value=$request_header_add_value["header_value"];
		}

		
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='deny_quota_rule'"));
		$deny_quota_rule=$ligne["httpaccess_value"];
		$deny_quota_rule_id=$ligne["httpaccess_data"];
		if(!is_numeric($deny_quota_rule)){$deny_quota_rule=0;}
		if($deny_quota_rule_id>0){
			$q3=new mysql();
			$ligne3=mysql_fetch_array($q3->QUERY_SQL("SELECT QuotaName FROM ext_time_quota_acl WHERE ID=$deny_quota_rule_id","artica_backup"));
			$deny_quota_rule_value=$ligne3["QuotaName"];
			$js_quota="<a href=\"javascript:blur();\"
			OnClick=\"javascript:Loadjs('squid.ext_time_quota_acl.php?session-rule-js=yes&ID=$deny_quota_rule_id&t={$_GET["t"]}&tOrg={$_GET["t"]}');\"
			style='text-decoration:underline;color:$linkcolor'>";
		}		
		
		
		
		$explain_deny_color="#A71A05";
		$explain_allow_color="#0AAB3D";
		if($ACCESSEnabled==0){$explain_deny_color="#8a8a8a";$explain_allow_color="#8a8a8a";}
		
		

		

			
		
		$reverse_text=false;
		if(!is_numeric($cache_deny)){$cache_deny=0;}		
		if($access_deny==1){$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;}
		if($access_deny==1){$explain[]="<strong style=\"color:$explain_deny_color\">$prefix{deny_access}</a></strong>";}
		if($http_reply_access_deny==1){$explain[]="<strong style=\"color:$explain_deny_color\">$prefix{deny_reply_access}</a></strong>";}
		if($cache_parent==1){
			if($cache_parent_info<>null){
				$objectsText=null;
				$objects=$this->getobjectsNameFromAclrule($aclid,$linkcolor);
				if(count($objects)==0){$error=$error.$errorNoObjects;}
				$explain[]="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.acls.proxy.parent.php?aclid=$aclid');\"
				style=\"color:$explain_allow_color;font-weight:bold;text-decoration:underline\">$prefix{use_parent_proxy}</a>&nbsp;<span style=\"color:$explain_allow_color;font-weight:bold;\">-</span>&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.parent.proxy.php?parent-js=yes&ID=$parentid&t={$_GET["t"]}');\" style=\"color:$explain_allow_color;font-weight:bold;text-decoration:underline\">$cache_parent_info</a>";
			}else{
				$explain[]="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.acls.proxy.parent.php?aclid=$aclid');\"
				style=\"color:$explain_deny_color;font-weight:bold;text-decoration:underline\">$prefix{use_parent_proxy} - {no_defined_parent}:$parentid</a>";
			}
		}
		
				
		if($http_reply_access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">$prefix{allow_reply_access}</a></strong>";}
		if($access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">$prefix{allow}</a></strong>";}
		if($snmp_access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">{allow_snmp_access}</a></strong>";}
		if($log_access==1){$explain[]="<strong style=\"color:$explain_allow_color\">$prefix{log_to_csv}</a> <a href=\"squid.acls-rules.php?csv=$aclid\" style='text-decoration:underline'>&laquo;download&raquo;</a></strong>";}
		if($deny_access_except==1){$explain[]="{deny_access_except}";$reverse_text=true;}
		if($request_header_add==1){$explain[]="<strong style=\"color:$explain_allow_color\">$prefix{add_http_header}</a>:</strong> &laquo;<strong>$request_header_add_name</strong>: $request_header_add_value&raquo;";}
		if($deny_log==1){$explain[]="<strong style=\"color:$explain_allow_color\">$prefix{deny_logging}</a></strong>";}
		if($deny_quota_rule==1){
			$objects=$this->getobjectsNameFromAclrule($aclid,$linkcolor);
			if(count($objects)==0){
				$error=$error.$errorNoObjects;
			}
			$explain[]="<strong style=\"color:$explain_deny_color\">$prefix{affect_quota_rule}</a> &laquo;$js_quota$deny_quota_rule_value</a>&raquo;</strong>";}
		
		
			if($isTransParent==0){
				if($PortDirection==2){
					$error=$error.$errorTransparentModeIsDisabled;
				}
			}		
			
		
		if($delay_access==1){
			$q2=new mysql();
			$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
			$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));	
			$delay_access_id_text=$ligne["rulename"];
			$delay_access_id_textjs="Loadjs('squid.bandwith.php?bandwith-rule-js=yes&ID=$delay_access_id&t={$_GET["t"]}&by-acls=yes')";
			$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;$access_deny=0;
			$explain[]="<strong style=\"color:$explain_deny_color\">$prefix{limit_bandwidth}</a>&nbsp;&laquo;<a href=\"javascript:blur();\" OnClick=\"javascript:$delay_access_id_textjs\" style='text-decoration:underline'>$delay_access_id_text</a>&raquo;</strong>";
		}
		
		if($url_rewrite_access_deny==1){$explain[]="$prefix{pass_trough_thewebfilter_engine}</a>";}
		if($adaptation_access_deny==1){$explain[]="$prefix{pass_trough_antivirus_engine}</a>";}
		if($tcp_outgoing_tos==1){$explain[]="$prefix{set_specific_dcsp}</a> $tcp_outgoing_tos_value";}
		if($tcp_outgoing_address==1){$explain[]="$prefix{acl_tcp_outgoing_address}</a> $tcp_outgoing_address_value";}
		if($reply_body_max_size==1){$explain[]="$prefix{acl_reply_body_max_size}</a> {$reply_body_max_size}MB";}
		

		
		if($cache_deny==1){$explain[]="$prefix{do_not_cache}</a>";}
		if(count($explain)==0){
			$final_explain="$prefix<strong style='color:$explain_deny_color'>{disable_this_rule_no_access_set}</strong></a>";
		}else{
			$final_explain=@implode(" {and} ", $explain);
		}
		$objects=$this->getobjectsNameFromAclrule($aclid,$linkcolor);
		
		
		if(count($objects)>0){
			if(!$reverse_text){
				return "$methodConnect{for_objects} ". @implode(" <br>{and} ", $objects)."<br>{then} $final_explain$error";
			}else{
				return $methodConnect.$final_explain."&nbsp;{for_objects} ". @implode(" {and} ", $objects).$error;
			}
		}else{
			return $methodConnect.$final_explain.$error;
		}
		
	}
	
	private function template_name($groupid,$linkcolor="black"){
		$sql="SELECT acltpl FROM webfilters_sqgroups WHERE ID='$groupid'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["acltpl"]==null){return;}
		$zmd5=$ligne["acltpl"];
		
		if($ligne["acltpl"]=="ARTICA_SLASH_SCREEN"){
			$js="Loadjs('squid.webauth.php');";
			return "<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style=\"text-decoration:underline;color:$linkcolor\">HotSpot</a>";
		}
		
		
		
		$sql="SELECT template_name,zmd5,lang,emptytpl FROM squidtpls WHERE `zmd5`='{$ligne["acltpl"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$subject=base64_encode(addslashes($ligne["template_name"]));
		$js="Loadjs('squid.templates.php?Zoom-js=$zmd5&subject=$subject');";
		if($ligne["emptytpl"]==1){
			$js="Loadjs('squid.templates.php?template-settings-js=yes&zmd5={$ligne["zmd5"]}');";
		}
		
		if(trim($ligne["template_name"])==null){return;}
		return "<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style=\"text-decoration:underline;color:$linkcolor\">{$ligne["template_name"]}</a>";	
		}
	
	
	private function getobjectsNameFromAclrule($aclid,$linkcolor="black"){
		if(isset($GLOBALS["getobjectsNameFromAclrule$aclid"])){return $GLOBALS["getobjectsNameFromAclrule$aclid"];}
		$f=array();
		$tableorg=null;
		$users=new usersMenus();
		$sock=new sockets();
		$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
		if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
		$URLAR=parse_url($SplashScreenURI);
		if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}		
		
		if(!$users->CORP_LICENSE){ $text_license="<img src='img/warning-16.png' align=left style='margin:5px'>
				<div style='color:red;padding:5px;margin:5px;font-style:italic;color:#EA0000'>{category_no_license_explain}</div>"; }
		
		
		$q=new mysql_squid_builder();
		

		
		
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;
		$ARRAY_AUTH["hotspot_auth"]=true;
		
		
		

		$sql="SELECT webfilters_sqacllinks.gpid,webfilters_sqacllinks.negation,webfilters_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND webfilters_sqacllinks.aclid=$aclid
		ORDER BY webfilters_sqacllinks.zOrder
		";
		
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			$PREFIX_TEXT=null;
			if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] type:{$ligne["GroupType"]} enabled:{$ligne["enabled"]}<br>\n";}
			if($ligne["enabled"]==0){
				if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] Not enabled, aborting<br>\n";}
				continue;}
			$butnot=null;
			$GroupName=utf8_encode($ligne["GroupName"]);
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1"));
			$items=$ligne2["tcount"];
			$items_text="$items {items}";
			if(!isset($q->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])){
				if($items==0){
					if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] No item, aborting<br>\n";}
					continue;
				}
			}
			if($ligne["GroupType"]=="proxy_auth_ads"){$items_text=null;}
			if($ligne["GroupType"]=="NudityScan"){$items_text=null;}
			if($ligne["GroupType"]=="categories"){
				$PREFIX_TEXT=$text_license;
			}
			
			
			
			if($ligne["GroupType"]=="dynamic_acls"){
				$ligne3=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='{$ligne['ID']}'"));
				$items_text="&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs={$ligne['ID']}');\"
				style=\"font-size:12px;font-weight:normal;text-decoration:underline;color:$linkcolor\">{$ligne3["tcount"]} {rules}</a>&nbsp;";
				
			}
			if($ligne["GroupType"]=="all"){$items_text="{all}";}
			if(isset($q->acl_ARRAY_NO_ITEM[$ligne["GroupType"]])){$items_text=null;}
			
			$template=$this->template_name($ligne['ID'],$linkcolor);
			if($template<>null){$template=" - {template} $template";}else{$template=" - {template} {default}";}
			if(isset($_GET["toexplainorg"])){$tableorg="&table-org={$_GET["toexplainorg"]}";}
			
			if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
				$butnot="{authenticated_trough}&nbsp;";
			}
			
			if($ligne["negation"]==1){
				$butnot="{notfor}&nbsp;";
				if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
					$butnot="{not_authenticated_trough}&nbsp;";
				}
				$template=null;
			}
			
			if(isset($ARRAY_AUTH[$ligne["GroupType"]])){
				$items_text="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.auth-objects-events.php?ID={$ligne['ID']}',true);\"
				style=\"font-size:12px;text-decoration:underline;color:$linkcolor\">{events}</a>
				";
			}
			
			if($ligne["GroupType"]=="hotspot_auth"){
				$warn="<img src='img/status_warning.png' align='left' style='margin-right:4px'>";
				$items_text="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('squid.webauth.php');\"
				style=\"font-size:12px;text-decoration:underline;color:$linkcolor\">{parameters}</a>
				";
				
				if($EnableSplashScreen==0){
					$items_text=$warn.$items_text;
				}
				
				if($EnableSplashScreenAsObject==0){
					$items_text=$warn.$items_text;
				}				
				
			}
			
			$finalItemText="$items_text$template";
			if($finalItemText<>null){$finalItemText="($finalItemText)";}
			
			
			$href="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}$tableorg',true);\" 
			style=\"font-size:12px;text-decoration:underline;color:$linkcolor\">";		

			
			
			$f[]="$PREFIX_TEXT$butnot&laquo;$href$GroupName</a>&raquo; <span style=\"font-size:10px;color:$linkcolor\">$finalItemText</span>";
		}
		
		
		$GLOBALS["getobjectsNameFromAclrule$aclid"]=$f;
		return $f;
		
	}
	
	public function FlexArray($gpid,$enable=1,$fontsize=14){
		$q=new mysql_squid_builder();
		

		
		$t=$_GET["t"];
		$color="black";
		if($enable==0){$color="#8a8a8a";}
		$tpl=new templates();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType 
				FROM webfilters_sqgroups WHERE ID='$gpid'"));
		$GroupName=utf8_encode($ligne["GroupName"]);
		$GroupType=$ligne["GroupType"];
		$GroupTypeName=$tpl->_ENGINE_parse_body($q->acl_GroupType[$GroupType]);
		$rules=$tpl->_ENGINE_parse_body("{rules}");
		$fontsize2=12;
		if($fontsize<14){$fontsize2=10;}
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM 
				webfilters_sqitems WHERE gpid='$gpid'"));
				$items=$ligne2["tcount"];
				

		
		if(isset($q->acl_ARRAY_NO_ITEM[$GroupType])){$items='-';}
		
		
		
		if($GroupType=="dynamic_acls"){
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='$gpid'"));
					$items="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs=$gpid');\"
					style='font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color'>{$ligne2["tcount"]}</a>";
					$additional_text="&nbsp;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs=$gpid');\"
					style='font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color'>[$rules]</a>";
				}
		
		if($GroupType=="all"){$items="*";}
		
		
		
		$href[]="<a href=\"javascript:blur();\" ";
		$href[]="OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-org=table-items-$t',true);\" ";
		$href[]="style=\"font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color;text-transform:capitalize\">";	
		$hrefT=@implode("", $href);
		
		$hrefAdd[]="<a href=\"javascript:blur();\" ";
		$hrefAdd[]="OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddItem-js=yes&item-id=-1&ID=$gpid&table-t=$t&table-org={$_GET["table-org"]}',true);\" ";
		$hrefAdd[]="style=\"font-size:{$fontsize2}px;color:$color;text-decoration:underline\">";
		
		if(isset($q->acl_ARRAY_NO_ITEM[$GroupType])){unset($hrefAdd);}
		$hrefAdd[]="($GroupTypeName)";
		$hrefAddT=@implode("", $hrefAdd);
		
		return array("ROW"=>"$hrefT$GroupName</a>&nbsp;&nbsp;$hrefAddT</a>$additional_text",
				"ITEMS"=>$items
				
				);
		
		
	}
	
	
}