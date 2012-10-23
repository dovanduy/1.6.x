<?php
class squid_acls_groups{
	var $GroupTypesTxt=array();
	var $ACL_ARP_ENABLED=false;
	var $RequestedGroupeType=null;
	var $UseDynamicGroupsAcls=0;
	var $EnableKerbAuth=0;
	var $AUTH=0;	
	var $GroupTypes=array();

	function squid_acls_groups(){
	$this->GroupTypes["src"]="{addr}";
	$this->GroupTypes["arp"]="{ComputerMacAddress}";
	$this->GroupTypes["dstdomain"]="{dstdomain}";
	$this->GroupTypes["proxy_auth"]="{members}";
	$this->GroupTypes["proto"]="{proto}";
	$sock=new sockets();
	$users=new usersMenus();
	$this->ACL_ARP_ENABLED=$users->SQUID_ARP_ACL_ENABLED;
	$this->UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($this->UseDynamicGroupsAcls)){$this->UseDynamicGroupsAcls=0;}
	$this->EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($this->EnableKerbAuth)){$this->EnableKerbAuth=0;}
	$squid=new squidbee();
	if($squid->LDAP_AUTH==1){$this->AUTH=1;}
	if($squid->LDAP_EXTERNAL_AUTH==1){$this->AUTH=1;}
	if($squid->EnableKerbAuth==1){$this->AUTH=1;}		
		
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
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		$this->debugOutput("Find `by order` -> ". mysql_num_rows($results)." items", __FUNCTION__, __LINE__);
		if(!$q->ok){$this->debugOutput("$q->mysql_error", __FUNCTION__, __LINE__);}
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$httpaccess=$ligne["httpaccess"];
			if($httpaccess=="tcp_outgoing_tos"){continue;}
			if($httpaccess=="url_rewrite_access_deny"){continue;}
			if($httpaccess=="adaptation_access_deny"){continue;}
			$reverse=false;
			
			$aclid=$ligne["ID"];
			if($httpaccess=="deny_access_except"){$reverse=true;$firstToken="http_access deny";}
			if($httpaccess=="access_allow"){$firstToken="http_access allow";}
			if($httpaccess=="access_deny"){$firstToken="http_access deny";}
			
			$this->debugOutput("->buildacls_bytype_items($aclid,$reverse)", __FUNCTION__, __LINE__);
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			$this->debugOutput("Checks: ACL $aclid Access type:`$httpaccess` ($firstToken) groups:". count($groups),__FUNCTION__,__LINE__);
			if(count($groups)==0){
				$this->debugOutput("SKIP! type:`$httpaccess` ($firstToken) groups:". count($groups),__FUNCTION__,__LINE__);
				continue;
			}
			$conf[]="$firstToken ".@implode(" ", $groups);
			
			
		}	
		
		return @implode("\n", $conf);
		
	}
	
	
	function buildacls_bytype($ruletype){
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
			$groups=$this->buildacls_bytype_items($aclid,$reverse);
			if(count($groups)==0){continue;}
			$valueToAdd=null;
			
			
			if($ruletype=="tcp_outgoing_tos"){
				$valueToAdd=$ligne["httpaccess_data"];
				if($valueToAdd==null){continue;}
				$valueToAdd=$valueToAdd." ";
			}
			
			
			$rulesAcls[]=$valueToAdd.@implode(" ", $groups);
			
			
		}
		
		return $rulesAcls;
	}
	
	private function buildacls_bytype_items($aclid,$reverse=false){
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
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		$this->debugOutput("$aclid: ". mysql_num_rows($results)." items", __FUNCTION__, __LINE__);
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$GroupType=$ligne["GroupType"];
			$neg=null;
			$this->debugOutput("$aclid `$GroupType` ID:{$ligne['ID']}", __FUNCTION__, __LINE__);
			if($GroupType=="proxy_auth_ads"){
				if($this->EnableKerbAuth==0){
					$this->debugOutput("$aclid: proxy_auth_ads by EnableKerbAuth is disabled, SKIP", __FUNCTION__, __LINE__);
					continue;
				}
				
				if($ligne["negation"]==1){$neg="!";}
				$this->debugOutput("$aclid: proxy_auth_ads -> {$neg}{$prefix}Group{$ligne["ID"]}", __FUNCTION__, __LINE__);
				$acls_name[]="{$neg}{$prefix}Group{$ligne["ID"]}";	
				continue;
			}
			
			
			if($GroupType=="proxy_auth"){if($this->AUTH==0){continue;}}
			
			
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
		if(!$q->FIELD_EXISTS("webfilters_sqaclaccess", "httpaccess_data")){$q->QUERY_SQL("ALTER TABLE `webfilters_sqaclaccess` ADD `httpaccess_data` VARCHAR( 255 ) NOT NULL");}
		if($value==0){
			$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE zmd5='$md5'");
			if(!$q->ok){echo $q->mysql_error;return false;}
			return true;
		}
		
		$q->QUERY_SQL("INSERT IGNORE INTO webfilters_sqaclaccess (zmd5,aclid,httpaccess,httpaccess_value,httpaccess_data) VALUES('$md5','$aclid','$type',1,'$NexValue')");
		if(!$q->ok){echo $q->mysql_error;return false;}
		return true;
		
	}
	
	function ACL_MULTIPLE_EXPLAIN($aclid,$ACCESSEnabled){
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
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='deny_access_except'"));
		$deny_access_except=$ligne["httpaccess_value"];
		if(!is_numeric($deny_access_except)){$deny_access_except=0;}

		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$aclid' AND httpaccess='tcp_outgoing_tos'"));
		$tcp_outgoing_tos=$ligne["httpaccess_value"];
		$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
		if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}	
		if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}			
		
		
		$explain_deny_color="#A71A05";
		$explain_allow_color="#0AAB3D";
		if($ACCESSEnabled==0){$explain_deny_color="#9C9C9C";$explain_allow_color="#9C9C9C";}
			
		
		$reverse_text=false;
		if(!is_numeric($cache_deny)){$cache_deny=0;}		
		if($access_deny==1){$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;}
		if($access_deny==1){$explain[]="<strong style=\"color:$explain_deny_color\">{deny_access}</strong>";}
		if($access_allow==1){$explain[]="<strong style=\"color:$explain_allow_color\">{allow}</strong>";}
		if($deny_access_except==1){$explain[]="{deny_access_except}";$reverse_text=true;}
		
		
		if($url_rewrite_access_deny==1){$explain[]="{pass_trough_thewebfilter_engine}";}
		if($adaptation_access_deny==1){$explain[]="{pass_trough_antivirus_engine}";}
		if($tcp_outgoing_tos==1){$explain[]="{set_specific_dcsp} $tcp_outgoing_tos_value";}
		
		
		if($cache_deny==1){$explain[]="{do_not_cache}";}
		if(count($explain)==0){
			$final_explain="<strong style='color:$explain_deny_color'>{disable_this_rule_no_access_set}</strong>";
		}else{
			$final_explain=@implode(" {and} ", $explain);
		}
		$objects=$this->getobjectsNameFromAclrule($aclid);
		if(count($objects)>0){
			if(!$reverse_text){
				return "{for_objects} ". @implode(" <br>{and} ", $objects)."<br>{then} $final_explain";
			}else{
				return $final_explain."&nbsp;{for_objects} ". @implode(" {and} ", $objects);
			}
		}else{
			return $final_explain;
		}
		
	}
	
	private function template_name($groupid){
		$sql="SELECT acltpl FROM webfilters_sqgroups WHERE ID='$groupid'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if($ligne["acltpl"]==null){return;}
		$sql="SELECT template_name,lang FROM squidtpls WHERE `zmd5`='{$ligne["acltpl"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		return $ligne["template_name"];	
		}
	
	
	private function getobjectsNameFromAclrule($aclid){
		$f=array();
		$tableorg=null;
		$sql="SELECT webfilters_sqacllinks.gpid,webfilters_sqacllinks.negation,webfilters_sqgroups.* FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["enabled"]==0){continue;}
			$butnot=null;
			$GroupName=utf8_encode($ligne["GroupName"]);
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1"));
			$items=$ligne2["tcount"];
			$items_text="$items {items}";
			if($ligne["GroupType"]<>"proxy_auth_ads"){
				if($items==0){continue;}
			}
			if($ligne["GroupType"]=="proxy_auth_ads"){$items_text=null;}
			$template=$this->template_name($ligne['ID']);
			if($template<>null){$template=" - {template} $template";}else{$template=" - {template} {default}";}
			if(isset($_GET["toexplainorg"])){$tableorg="&table-org={$_GET["toexplainorg"]}";}
			if($ligne["negation"]==1){
				$butnot="{notfor}&nbsp;";
				$template=null;
			}
			
			
$href="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}$tableorg');\" 
		style='font-size:12px;text-decoration:underline'>";				
			
			$f[]="$butnot&laquo;$href$GroupName</a>&raquo; <span style='font-size:10px'>($items_text$template)</span>";
		}
		return $f;
		
	}
	
	
	
	
}