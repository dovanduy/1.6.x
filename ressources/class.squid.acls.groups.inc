<?php
include_once(dirname(__FILE__)."/class.squid.acls.quotas.inc");

class squid_acls_groups{
	var $GroupTypesTxt=array();
	var $ACL_ARP_ENABLED=false;
	var $RequestedGroupeType=null;
	var $UseDynamicGroupsAcls=0;
	var $EnableKerbAuth=0;
	var $AUTH=0;	
	var $GroupTypes=array();
	var $ASROOT=false;
	private $hasProxyTransparent=0;
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
		$this->hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");
		if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
		$squid=new squidbee();
		if($squid->LDAP_AUTH==1){$this->AUTH=1;}
		if($squid->LDAP_EXTERNAL_AUTH==1){$this->AUTH=1;}
		if($squid->EnableKerbAuth==1){$this->AUTH=1;}
		if($this->hasProxyTransparent==1){$this->AUTH=0;$this->EnableKerbAuth=0;$this->UseDynamicGroupsAcls=0;}	
		$this->ARRAY_NO_ITEM["proxy_auth_ads"]=true;
		$this->ARRAY_NO_ITEM["NudityScan"]=true;
		$this->ARRAY_NO_ITEM["all"]=true;
		$this->ARRAY_NO_ITEM["dynamic_acls"]=true;
		$this->ARRAY_NO_ITEM["radius_auth"]=true;
		$this->ARRAY_NO_ITEM["ad_auth"]=true;
		$this->ARRAY_NO_ITEM["ldap_auth"]=true;
		$this->ARRAY_NO_ITEM["hotspot_auth"]=true;
		
	}
	
	private function debugOutput($text,$function,$line){
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!$GLOBALS["VERBOSE"]){return;}
		echo "DEBUG ACLS:$function::$text [$line]\n";
	}
	
	function buildacls_order(){
		$function=__FUNCTION__;
		$q=new mysql_squid_builder();
		$sql="SELECT webfilters_sqacls.ID,
		webfilters_sqacls.xORDER,
		webfilters_sqaclaccess.httpaccess_data,
		webfilters_sqaclaccess.httpaccess
		FROM webfilters_sqacls, webfilters_sqaclaccess 
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		ORDER BY webfilters_sqacls.xORDER";

		$results = $q->QUERY_SQL($sql);
		if($this->ASROOT){echo "Starting......: [ACLS]: buildacls_order() ". mysql_num_rows($results). "... (Line ".__LINE__.")\n";}
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		$this->debugOutput("Find `by order` -> ". mysql_num_rows($results)." items", __FUNCTION__, __LINE__);
		if(!$q->ok){$this->debugOutput("$q->mysql_error", __FUNCTION__, __LINE__);}
		$conf=array();
		while ($ligne = mysql_fetch_assoc($results)) {
			$httpaccess=$ligne["httpaccess"];
			$firstToken=null;
			if($httpaccess=="tcp_outgoing_tos"){continue;}
			if($httpaccess=="url_rewrite_access_deny"){continue;}
			if($httpaccess=="adaptation_access_deny"){continue;}
			if($httpaccess=="delay_access"){continue;}
			if($httpaccess=="tcp_outgoing_address"){continue;}
			if($httpaccess=="snmp_access_allow"){continue;}
			if($httpaccess=="log_access"){continue;}
			if($httpaccess=="request_header_add"){continue;}
			if($httpaccess=="deny_log"){continue;}
			
			$reverse=false;
			
			$aclid=$ligne["ID"];
			if($httpaccess=="deny_access_except"){$reverse=true;$firstToken="http_access deny";}
			if($httpaccess=="access_allow"){$firstToken="http_access allow";}
			if($httpaccess=="access_deny"){$firstToken="http_access deny";}
			if($httpaccess=="cache_deny"){$firstToken="cache deny";}
			if($httpaccess=="deny_quota_rule"){
				$deny_quota_rule_id=$ligne["httpaccess_data"];
				if($deny_quota_rule_id==0){continue;}
				$aclquota=new squid_acls_quotas_time($deny_quota_rule_id);
				if(!$aclquota->IsValid($deny_quota_rule_id)){continue;}
				$firstToken="http_access deny !time_quota$deny_quota_rule_id";
			}
			
			
			
			
			
			$this->debugOutput("[$httpaccess] ->buildacls_bytype_items($aclid,$reverse)", __FUNCTION__, __LINE__);
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			$this->debugOutput("Checks: ACL $aclid Access type:`$httpaccess` ($firstToken) groups:". count($groups),__FUNCTION__,__LINE__);
			if(count($groups)==0){
				if($this->ASROOT){echo "Starting......: [ACLS]: buildacls_order() SKIP! type:`$httpaccess` ($firstToken) groups:". count($groups)." (Line ".__LINE__.")\n";}
				continue;
			}
			$conf[]="$firstToken ".@implode(" ", $groups);
			
			
		}	
		if($this->ASROOT){echo "Starting......: [ACLS]: buildacls_order() FINAL:". count($conf)." rules (Line ".__LINE__.")\n";}
		return @implode("\n", $conf);
		
	}
	
	
	function buildacls_bytype($ruletype){
		
		if(isset($GLOBALS["buildacls_bytype$ruletype"])){return $GLOBALS["buildacls_bytype$ruletype"];}
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: Find `$ruletype`\n";}
		$rulesAcls=array();
		$sql="SELECT webfilters_sqacls.ID,webfilters_sqacls.xORDER,webfilters_sqaclaccess.httpaccess_data
		FROM webfilters_sqacls, webfilters_sqaclaccess 
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		AND webfilters_sqaclaccess.httpaccess='$ruletype' ORDER BY webfilters_sqacls.xORDER";
		$reverse=false;
		$q=new mysql_squid_builder();
		if($ruletype=="deny_access_except"){$reverse=true;}
		
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		
		
		
		$this->debugOutput("`$ruletype` -> ". mysql_num_rows($results)." items",__FUNCTION__,__LINE__);
		
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclid=$ligne["ID"];
			if($ruletype=="deny_log"){$reverse=true;}
			
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			if(count($groups)==0){continue;}
			$valueToAdd=null;
			
			
			if($ruletype=="tcp_outgoing_tos"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($valueToAdd==null){continue;}
				$valueToAdd=$valueToAdd." ";
			}
			
			if($ruletype=="tcp_outgoing_address"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($valueToAdd==null){continue;}
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
				
			$rulesAcls[]=$valueToAdd.@implode(" ", $groups);
			
			
		}
		if($GLOBALS["VERBOSE"]){echo "Return: `$rulesAcls`\n";}
		$GLOBALS["buildacls_bytype$ruletype"]=$rulesAcls;
		return $rulesAcls;
	}
	
	public function buildacls_bytype_items($aclid,$reverse=false){
		$prefix=null;
		if($reverse){$prefix="!";}
		$acls_name=array();
		$sql="SELECT 
		webfilters_sqacllinks.gpid,
		webfilters_sqacllinks.negation,
		webfilters_sqgroups.GroupType,
		webfilters_sqgroups.GroupName,
		webfilters_sqgroups.ID 
		FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid
		ORDER BY webfilters_sqacllinks.negation";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
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
				if($this->hasProxyTransparent==1){continue;}
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
			

			if(isset($ARRAY_AUTH[$GroupType])){
				if($this->hasProxyTransparent==1){continue;}
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
				if($this->hasProxyTransparent==1){continue;}
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
		
		if($type=="delay_access"){
			$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$aclid'");
		}
		
		$sql="INSERT IGNORE INTO webfilters_sqaclaccess (zmd5,aclid,httpaccess,httpaccess_value,httpaccess_data) VALUES('$md5','$aclid','$type',1,'$NexValue')";
		//echo $sql;
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;
		
	}
	
	function ACL_MULTIPLE_EXPLAIN($aclid,$ACCESSEnabled){
		$linkcolor="black";
		$WarnColor="#EA0C0C";
		if($ACCESSEnabled==0){$linkcolor="#9C9C9C";$WarnColor="#9C9C9C";}
		$errorNoObjects="<div style='font-size:11px;fonct-weight:bold;color:$WarnColor'><img src='img/icon_mini_warning.gif' align='left' style='margin-right:4px'>{error_acl_no_object}</div>";
		$q=new mysql_squid_builder();
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

		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='delay_access'"));
		$delay_access=$ligne["httpaccess_value"];
		$delay_access_id=$ligne["httpaccess_data"];
		if(!is_numeric($delay_access)){$delay_access=0;}	
		if(!is_numeric($delay_access_id)){$delay_access_id=0;}

		
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
		if($ACCESSEnabled==0){$explain_deny_color="#9C9C9C";$explain_allow_color="#9C9C9C";}
			
		
		$reverse_text=false;
		if(!is_numeric($cache_deny)){$cache_deny=0;}		
		if($access_deny==1){$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;}
		if($access_deny==1){$explain[]="<strong style=\"color:$explain_deny_color\">{deny_access}</strong>";}
		if($access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">{allow}</strong>";}
		if($snmp_access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">{allow_snmp_access}</strong>";}
		if($log_access==1){$explain[]="<strong style=\"color:$explain_allow_color\">{log_to_csv} <a href=\"squid.acls-rules.php?csv=$aclid\" style='text-decoration:underline'>&laquo;download&raquo;</a></strong>";}
		if($deny_access_except==1){$explain[]="{deny_access_except}";$reverse_text=true;}
		if($request_header_add==1){$explain[]="<strong style=\"color:$explain_allow_color\">{add_http_header}:</strong> &laquo;<strong>$request_header_add_name</strong>: $request_header_add_value&raquo;";}
		if($deny_log==1){$explain[]="<strong style=\"color:$explain_allow_color\">{deny_logging}</strong>";}
		if($deny_quota_rule==1){
			$objects=$this->getobjectsNameFromAclrule($aclid,$linkcolor);
			if(count($objects)==0){
				$error=$error.$errorNoObjects;
			}
			$explain[]="<strong style=\"color:$explain_deny_color\">{affect_quota_rule} &laquo;$js_quota$deny_quota_rule_value</a>&raquo;</strong>";}
		
		
		
		if($delay_access==1){
			$q2=new mysql();
			$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
			$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));	
			$delay_access_id_text=$ligne["rulename"];
			$delay_access_id_textjs="Loadjs('squid.bandwith.php?bandwith-rule-js=yes&ID=$delay_access_id&t={$_GET["t"]}&by-acls=yes')";
			$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;$access_deny=0;
			$explain[]="<strong style=\"color:$explain_deny_color\">{limit_bandwidth}&nbsp;&laquo;<a href=\"javascript:blur();\" OnClick=\"javascript:$delay_access_id_textjs\" style='text-decoration:underline'>$delay_access_id_text</a>&raquo;</strong>";
		}
		
		if($url_rewrite_access_deny==1){$explain[]="{pass_trough_thewebfilter_engine}";}
		if($adaptation_access_deny==1){$explain[]="{pass_trough_antivirus_engine}";}
		if($tcp_outgoing_tos==1){$explain[]="{set_specific_dcsp} $tcp_outgoing_tos_value";}
		if($tcp_outgoing_address==1){$explain[]="{acl_tcp_outgoing_address} $tcp_outgoing_address_value";}
		
		
		if($cache_deny==1){$explain[]="{do_not_cache}";}
		if(count($explain)==0){
			$final_explain="<strong style='color:$explain_deny_color'>{disable_this_rule_no_access_set}</strong>";
		}else{
			$final_explain=@implode(" {and} ", $explain);
		}
		$objects=$this->getobjectsNameFromAclrule($aclid,$linkcolor);
		if(count($objects)>0){
			if(!$reverse_text){
				return "{for_objects} ". @implode(" <br>{and} ", $objects)."<br>{then} $final_explain$error";
			}else{
				return $final_explain."&nbsp;{for_objects} ". @implode(" {and} ", $objects).$error;
			}
		}else{
			return $final_explain.$error;
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
		
		
		
		$sql="SELECT template_name,lang FROM squidtpls WHERE `zmd5`='{$ligne["acltpl"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$subject=base64_encode(addslashes($ligne["template_name"]));
		$js="Loadjs('squid.templates.php?Zoom-js=$zmd5&subject=$subject');";
		
		if(trim($ligne["template_name"])==null){return;}
		return "<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style=\"text-decoration:underline;color:$linkcolor\">{$ligne["template_name"]}</a>";	
		}
	
	
	private function getobjectsNameFromAclrule($aclid,$linkcolor="black"){
		if(isset($GLOBALS["getobjectsNameFromAclrule$aclid"])){return $GLOBALS["getobjectsNameFromAclrule$aclid"];}
		$f=array();
		$tableorg=null;
		
		$sock=new sockets();
		$EnableSplashScreen=$sock->GET_INFO("EnableSplashScreen");
		$EnableSplashScreenAsObject=$sock->GET_INFO("EnableSplashScreenAsObject");
		if(!is_numeric($EnableSplashScreen)){$EnableSplashScreen=0;}
		if(!is_numeric($EnableSplashScreenAsObject)){$EnableSplashScreenAsObject=0;}
		$SplashScreenURI=$sock->GET_INFO("SplashScreenURI");
		if(trim($SplashScreenURI)==null){$EnableSplashScreen=0;}
		$URLAR=parse_url($SplashScreenURI);
		if(isset($URLAR["host"])){$SplashScreenURI="http://$SplashScreenURI";}		
		 
		
		
		$ARRAY_NO_ITEM["proxy_auth_ads"]=true;
		$ARRAY_NO_ITEM["NudityScan"]=true;
		$ARRAY_NO_ITEM["all"]=true;
		$ARRAY_NO_ITEM["dynamic_acls"]=true;
		$ARRAY_NO_ITEM["radius_auth"]=true;
		$ARRAY_NO_ITEM["ad_auth"]=true;
		$ARRAY_NO_ITEM["ldap_auth"]=true;
		$ARRAY_NO_ITEM["hotspot_auth"]=true;
		
		
		
		$ARRAY_AUTH["radius_auth"]=true;
		$ARRAY_AUTH["ad_auth"]=true;
		$ARRAY_AUTH["ldap_auth"]=true;
		$ARRAY_AUTH["hotspot_auth"]=true;
		
		
		

		$sql="SELECT webfilters_sqacllinks.gpid,webfilters_sqacllinks.negation,webfilters_sqgroups.* FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] type:{$ligne["GroupType"]} enabled:{$ligne["enabled"]}<br>\n";}
			if($ligne["enabled"]==0){
				if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] Not enabled, aborting<br>\n";}
				continue;}
			$butnot=null;
			$GroupName=utf8_encode($ligne["GroupName"]);
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1"));
			$items=$ligne2["tcount"];
			$items_text="$items {items}";
			if(!isset($ARRAY_NO_ITEM[$ligne["GroupType"]])){
				if($items==0){
					if($GLOBALS["VERBOSE"]){echo "ACL[$aclid]: Checking Group[{$ligne['ID']}] No item, aborting<br>\n";}
					continue;
				}
			}
			if($ligne["GroupType"]=="proxy_auth_ads"){$items_text=null;}
			if($ligne["GroupType"]=="NudityScan"){$items_text=null;}
			if($ligne["GroupType"]=="dynamic_acls"){
				$ligne3=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='{$ligne['ID']}'"));
				$items_text="&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs={$ligne['ID']}');\"
				style=\"font-size:12px;font-weight:normal;text-decoration:underline;color:$linkcolor\">{$ligne3["tcount"]} {rules}</a>&nbsp;";
				
			}
			if($ligne["GroupType"]=="all"){$items_text="{all}";}
			if(isset($ARRAY_NO_ITEM[$ligne["GroupType"]])){$items_text=null;}
			
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
				OnClick=\"javascript:Loadjs('squid.auth-objects-events.php?ID={$ligne['ID']}');\"
				style=\"font-size:12px;text-decoration:underline;color:$linkcolor\">{events}</a>
				";
			}
			
			if($ligne["GroupType"]=="hotspot_auth"){
				$warn="<img src='img/icon_mini_warning.gif' align='left' style='margin-right:4px'>";
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
			OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}$tableorg');\" 
			style=\"font-size:12px;text-decoration:underline;color:$linkcolor\">";		

			
			
			$f[]="$butnot&laquo;$href$GroupName</a>&raquo; <span style=\"font-size:10px;color:$linkcolor\">$finalItemText</span>";
		}
		
		
		$GLOBALS["getobjectsNameFromAclrule$aclid"]=$f;
		return $f;
		
	}
	
	public function FlexArray($gpid,$enable=1,$fontsize=14){
		$q=new mysql_squid_builder();
		
		$ARRAY_NO_ITEM["proxy_auth_ads"]=true;
		$ARRAY_NO_ITEM["NudityScan"]=true;
		$ARRAY_NO_ITEM["all"]=true;
		$ARRAY_NO_ITEM["dynamic_acls"]=true;
		$ARRAY_NO_ITEM["radius_auth"]=true;
		$ARRAY_NO_ITEM["ad_auth"]=true;
		$ARRAY_NO_ITEM["ldap_auth"]=true;
		$ARRAY_NO_ITEM["hotspot_auth"]=true;		
		
		$t=$_GET["t"];
		$color="black";
		if($enable==0){$color="#9C9C9C";}
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
				

		
		if(isset($ARRAY_NO_ITEM[$GroupType])){$items='-';}
		
		
		
		if($GroupType=="dynamic_acls"){
				$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilter_aclsdynamic WHERE gpid='$gpid'"));
					$items="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs=$gpid');\"
					style='font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color'>{$ligne2["tcount"]}</a>";
					$additional_text="&nbsp;&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('miniadmin.proxy.dynamic.acls.php?ByJs=$gpid');\"
					style='font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color'>[$rules]</a>";
				}
		
		if($GroupType=="all"){$items="*";}
		
		
		
		$href[]="<a href=\"javascript:blur();\" ";
		$href[]="OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$gpid&table-org=table-items-$t');\" ";
		$href[]="style=\"font-size:{$fontsize}px;font-weight:bold;text-decoration:underline;color:$color;text-transform:capitalize\">";	
		$hrefT=@implode("", $href);
		
		$hrefAdd[]="<a href=\"javascript:blur();\" ";
		$hrefAdd[]="OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddItem-js=yes&item-id=-1&ID=$gpid&table-t=$t&table-org={$_GET["table-org"]}');\" ";
		$hrefAdd[]="style=\"font-size:{$fontsize2}px;color:$color;text-decoration:underline\">";
		
		if(isset($ARRAY_NO_ITEM[$GroupType])){unset($hrefAdd);}
		$hrefAdd[]="($GroupTypeName)";
		$hrefAddT=@implode("", $hrefAdd);
		
		return array("ROW"=>"$hrefT$GroupName</a>&nbsp;&nbsp;$hrefAddT</a>$additional_text",
				"ITEMS"=>$items
				
				);
		
		
	}
	
	
}