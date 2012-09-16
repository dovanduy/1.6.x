<?php
class squid_acls_groups{
	var $GroupTypesTxt=array();
	

	function squid_acls_groups(){
	$this->GroupTypes["src"]="{addr}";
	$this->GroupTypes["arp"]="{ComputerMacAddress}";
	$this->GroupTypes["dstdomain"]="{dstdomain}";
	$this->GroupTypes["proxy_auth"]="{members}";
		
		
	}
	
	function buildacls_bytype($ruletype){
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: Find `$ruletype`\n";}
		$rulesAcls=array();
		$sql="SELECT webfilters_sqacls.ID,webfilters_sqaclaccess.httpaccess_data
		FROM webfilters_sqacls, webfilters_sqaclaccess 
		WHERE webfilters_sqacls.ID=webfilters_sqaclaccess.aclid
		AND webfilters_sqacls.enabled=1
		AND webfilters_sqaclaccess.httpaccess='$ruletype'";
		$reverse=false;
		$q=new mysql_squid_builder();
		if($ruletype=="deny_access_except"){$reverse=true;}
		
		
		
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){if(preg_match("#Error Table.*?doesn't exist#",$q->mysql_error)){$q->CheckTables();$results = $q->QUERY_SQL($sql);}}
		
		
		
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: Find `$ruletype` -> ". mysql_num_rows($results)." items\n";}
		
		
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
		$sql="SELECT webfilters_sqacllinks.gpid,webfilters_sqgroups.ID FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: ID:$aclid -> ". mysql_num_rows($results)." items\n";}
		while ($ligne = mysql_fetch_assoc($results)) {
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1"));
			$items=$ligne2["tcount"];
			if($items==0){
				if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: ID:$aclid -> GroupID {$ligne['ID']} 0 items skip". mysql_num_rows($results)." items\n";}continue;}

				
				$acls_name[]="{$prefix}Group{$ligne["ID"]}";	
			
		}	
		if($GLOBALS["VERBOSE"]){echo "DEBUG: ACLS: ID:$aclid ->". count($acls_name)." final items\n";}
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
	
	function ACL_MULTIPLE_EXPLAIN($aclid){
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
		
		
		
		
		$reverse_text=false;
		if(!is_numeric($cache_deny)){$cache_deny=0;}		
		if($access_deny==1){$url_rewrite_access_deny=0;$adaptation_access_deny=0;$cache_deny=0;$access_allow=0;}
		if($access_deny==1){$explain[]="{deny_access}";}
		if($access_allow==1){$explain[]="{allow}";}
		if($deny_access_except==1){$explain[]="{deny_access_except}";$reverse_text=true;}
		
		
		if($url_rewrite_access_deny==1){$explain[]="{pass_trough_thewebfilter_engine}";}
		if($adaptation_access_deny==1){$explain[]="{pass_trough_antivirus_engine}";}
		
		if($tcp_outgoing_tos==1){$explain[]="{set_specific_dcsp} $tcp_outgoing_tos_value";}
		
		
		if($cache_deny==1){$explain[]="{do_not_cache}";}
		$final_explain=@implode(" {and} ", $explain);
		$objects=$this->getobjectsNameFromAclrule($aclid);
		if(count($objects)>0){
			if(!$reverse_text){
				return "{for_objects} ". @implode(" {and} ", $objects)."<br>{then} $final_explain";
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
		$sql="SELECT webfilters_sqacllinks.gpid,webfilters_sqgroups.* FROM webfilters_sqacllinks,webfilters_sqgroups 
		WHERE webfilters_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqacllinks.aclid=$aclid";
		$q=new mysql_squid_builder();
		$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["enabled"]==0){continue;}
			$GroupName=utf8_encode($ligne["GroupName"]);
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_sqitems WHERE gpid='{$ligne['ID']}' AND enabled=1"));
			$items=$ligne2["tcount"];
			if($items==0){continue;}
			$template=$this->template_name($ligne['ID']);
			if($template<>null){$template=" - {template} $template";}else{$template=" - {template} {default}";}
			
$href="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID={$ligne['ID']}');\" 
		style='font-size:12px;text-decoration:underline'>";				
			
			$f[]="&laquo;$href$GroupName</a>&raquo; <span style='font-size:10px'>($items {items}$template)</span>";
		}
		return $f;
		
	}
	
	
	
	
}