<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');

	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		echo "<H2>$alert</H2>";
		die();	
	}
	
	if(isset($_POST["aclname-save"])){acl_rule_save();exit;}
	if(isset($_GET["acls-list"])){acl_list();exit;}
	if(isset($_GET["Addacl-js"])){acl_js();exit;}
	if(isset($_GET["acl-rule-tabs"])){acl_rule_tab();exit;}
	if(isset($_GET["acl-rule-settings"])){acl_rule_settings();exit;}
	if(isset($_POST["acl-rule-delete"])){acl_rule_delete();exit;}
	if(isset($_POST["acl-rule-enable"])){acl_rule_enable();exit;}
	if(isset($_POST["acl-rule-move"])){acl_rule_move();exit;}
	if(isset($_POST["acl-rule-order"])){acl_rule_order();exit;}
	if(isset($_POST["aclrulename"])){acl_main_rule_edit();exit;}
	if(isset($_POST["ApplySquid"])){squid_compile();exit;}
	page();
	
	
function acl_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	if($ID<1){
		$title=$tpl->javascript_parse_text("{new_rule}");
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname FROM webfilters_sqacls WHERE ID='$ID'"));
		$title=utf8_encode($ligne["aclname"]);
	}
	$t=time();
	$html="
	var ID$t=$ID;
	var x_aclCallBack= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-{$_GET["t"]}').flexReload();
	}	
	
	
	function acl$t(){
		var aclname=prompt('$title');
		if(aclname){
		      var XHR = new XHRConnection();
		      XHR.appendData('aclname-save', aclname);
		      XHR.appendData('ID', '$ID');		      
		      XHR.sendAndLoad('$page', 'POST',x_aclCallBack);  			
		
		}
	
	}
	
	function aclShow$t(){
	
	
	}
	
	if(ID$t<0){acl$t();}else{
		YahooWin2(650,'$page?acl-rule-tabs=yes&ID=$ID&t={$_GET["t"]}','$title');
	}		
	
	";
	
	echo $html;
	
	
}

function acl_rule_tab(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	
	
	$array["acl-rule-settings"]='{settings}';
	$array["acl-items"]='{items}';
	
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="acl-items"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"squid.acls-rules.items.php?$num=yes&aclid=$ID&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:14px'><a href=\"$page?$num=yes&ID=$ID&t=$t\"><span>$ligne</span></a></li>\n");
	
	}

	
	echo "
	<div id=main_acl_rule_zoom style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_acl_rule_zoom').tabs();
			
			
			});
		</script>";	
}


function acl_rule_settings(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];	
	$t=time();
	$please_choose_a_bandwith_rule=$tpl->javascript_parse_text("{please_choose_a_bandwith_rule}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT aclname,acltpl FROM webfilters_sqacls WHERE ID='$ID'"));
	$aclname=utf8_encode($ligne["aclname"]);
	$acltpl=$ligne["acltpl"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='url_rewrite_access_deny'"));
	$url_rewrite_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($url_rewrite_access_deny)){$url_rewrite_access_deny=0;}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_deny'"));
	$access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($access_deny)){$access_deny=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='adaptation_access_deny'"));
	$adaptation_access_deny=$ligne["httpaccess_value"];
	if(!is_numeric($adaptation_access_deny)){$adaptation_access_deny=0;}		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='cache_deny'"));
	$cache_deny=$ligne["httpaccess_value"];
	if(!is_numeric($cache_deny)){$cache_deny=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='access_allow'"));
	$access_allow=$ligne["httpaccess_value"];
	if(!is_numeric($access_allow)){$access_allow=0;}	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='deny_access_except'"));
	$deny_access_except=$ligne["httpaccess_value"];
	if(!is_numeric($deny_access_except)){$deny_access_except=0;}

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='tcp_outgoing_tos'"));
	$tcp_outgoing_tos=$ligne["httpaccess_value"];
	$tcp_outgoing_tos_value=$ligne["httpaccess_data"];
	if(!is_numeric($tcp_outgoing_tos)){$tcp_outgoing_tos=0;}	
	if($tcp_outgoing_tos_value==null){$tcp_outgoing_tos_value="0x20";}	
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT httpaccess_value,httpaccess_data FROM webfilters_sqaclaccess WHERE aclid='$ID' AND httpaccess='delay_access'"));
	
	$delay_access=$ligne["httpaccess_value"];
	$delay_access_id=$ligne["httpaccess_data"];
	if(!is_numeric($delay_access)){$delay_access=0;}	
	if(!is_numeric($delay_access_id)){$delay_access_id=0;}		
	
	
	if($acltpl==null){$acltpl="{default}";}
	
	
	else{
			$md5=$acltpl;
			$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			$acltpl=addslashes($ligne2["template_title"]);
			$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($acltpl)."');";
			$acltpl="<a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:14px;text-decoration:underline'>$acltpl</a>";
		
	}
	
	if($delay_access_id>0){
		$q2=new mysql();
		$sql="SELECT rulename FROM squid_pools WHERE ID='$delay_access_id'";
		$ligne=mysql_fetch_array($q2->QUERY_SQL($sql,"artica_backup"));	
		$delay_access_id_text=$ligne["rulename"];
	}
	
	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$html="
	<div id='FormToParse$t'>
	<div id='divid$t'></div> 
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{rule_name}:</td>
		<td>". Field_text("aclrulename",$aclname,"font-size:14px;width:220px")."</td>
	</tr>	
	</table>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{allow}:</td>
		<td>". Field_checkbox("access_allow",1,$access_allow,"access_allow_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{deny_access}:</td>
		<td>". Field_checkbox("access_deny",1,$access_deny,"access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{deny_access_except}:</td>
		<td>". Field_checkbox("deny_access_except",1,$deny_access_except,"deny_access_except_check()")."</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{pass_trough_thewebfilter_engine}:</td>
		<td>". Field_checkbox("url_rewrite_access_deny",1,$url_rewrite_access_deny,"url_rewrite_access_deny_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{pass_trough_antivirus_engine}:</td>
		<td>". Field_checkbox("adaptation_access_deny",1,$adaptation_access_deny,"adaptation_access_deny_check()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{do_not_cache}:</td>
		<td>". Field_checkbox("cache_deny",1,$cache_deny,"cache_deny_check()")."</td>
	</tr>	
	</table>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{limit_bandwidth}:</td>
		<td>". Field_checkbox("delay_access",1,$delay_access,"limit_bandwidth_check()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{bandwidth}:</td>
		<td>
			<span id='delay_access_id_text' style='font-size:14px;font-weight:bold'>$delay_access_id_text</span>
			<input type='hidden' id='delay_access_id' value='$delay_access_id'>
		</td>
		<td width=1%>". button('{browse}...',"Loadjs('squid.bandwith.php?browser-acl-js=yes&aclruleid=$ID')")."</td>
	</tr>			
	</table>	
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{tcp_outgoing_tos}:</td>
		<td>". Field_checkbox("tcp_outgoing_tos",1,$tcp_outgoing_tos,"tcp_outgoing_tosCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{tcp_outgoing_tos_value}:</td>
		<td>". Field_text("tcp_outgoing_tos_value",$tcp_outgoing_tos_value,'font-size:14px;width:90px')."</td>
	</tr>	
	</table>
	
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "SaveAclRule$ID()",16)."</td>
	</tr>
	</table>
	</div>
	
	<script>
	
		var x_SaveAclRule$ID= function (obj) {
			document.getElementById('divid$t').innerHTML='';	
			var res=obj.responseText;
			if(res.length>3){alert(res);return;}
			RefreshTab('main_acl_rule_zoom');
			$('#table-$t').flexReload();
			
		}
	
		function SaveAclRule$ID(){
			var XHR = new XHRConnection();
			XHR.appendData('aclrulename', document.getElementById('aclrulename').value);
			XHR.appendData('tcp_outgoing_tos_value', document.getElementById('tcp_outgoing_tos_value').value);
			
			var delay_access_id=document.getElementById('delay_access_id').value;
			
			if(document.getElementById('delay_access').checked){
				if(delay_access_id==0){
					alert('$please_choose_a_bandwith_rule');
					return;
				}
			}
			XHR.appendData('delay_access_id', document.getElementById('delay_access_id').value);
			XHR.appendData('ID', '$ID');
			if(document.getElementById('tcp_outgoing_tos').checked){XHR.appendData('tcp_outgoing_tos', '1');}else{XHR.appendData('tcp_outgoing_tos', '0');}
			if(document.getElementById('access_allow').checked){XHR.appendData('access_allow', '1');}else{XHR.appendData('access_allow', '0');}
			if(document.getElementById('deny_access_except').checked){XHR.appendData('deny_access_except', '1');}else{XHR.appendData('deny_access_except', '0');}
			if(document.getElementById('url_rewrite_access_deny').checked){XHR.appendData('url_rewrite_access_deny', '1');}else{XHR.appendData('url_rewrite_access_deny', '0');}
			if(document.getElementById('access_deny').checked){XHR.appendData('access_deny', '1');}else{XHR.appendData('access_deny', '0');}
			if(document.getElementById('adaptation_access_deny').checked){XHR.appendData('adaptation_access_deny', '1');}else{XHR.appendData('adaptation_access_deny', '0');}
			if(document.getElementById('cache_deny').checked){XHR.appendData('cache_deny', '1');}else{XHR.appendData('cache_deny', '0');}
			if(document.getElementById('delay_access').checked){XHR.appendData('delay_access', '1');}else{XHR.appendData('delay_access', '0');}
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveAclRule$ID);  		
		
		}
		
		

	
	function CheckAll(){
	var c=0;
	$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		if(document.getElementById(id).checked){c=c+1;}
		 	}
		 	
		});		
		
	if(c==0){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
			 	var \$t = $(this);
			 	var id=\$t.attr('id');
			 	var value=\$t.attr('value');
			 	var type=\$t.attr('type');
			 	if(type=='checkbox'){
			 		document.getElementById(id).disabled=false;
			 	}
			 	
			});			
	
		}
	
	}
	
	function DisableAllInstead(zid){
		$('input,select,hidden,textarea', '#FormToParse$t').each(function() {
		 	var \$t = $(this);
		 	var id=\$t.attr('id');
		 	if(zid==id){return;}
		 	var value=\$t.attr('value');
		 	var type=\$t.attr('type');
		 	if(type=='checkbox'){
		 		document.getElementById(id).checked=false;
		 		document.getElementById(id).disabled=true;
		 	}
		 	
		});			
	}
	
	function limit_bandwidth_check(){
		if(document.getElementById('delay_access').checked){DisableAllInstead('delay_access');}else{CheckAll();}
		
	}
	
	function access_allow_check(){
		if(document.getElementById('access_allow').checked){DisableAllInstead('access_allow');}else{CheckAll();}
	}
	
	function access_deny_check(){
		if(document.getElementById('access_deny').checked){DisableAllInstead('access_deny');}else{CheckAll();}
	}

	function cache_deny_check(){
		if(document.getElementById('cache_deny').checked){DisableAllInstead('cache_deny');}else{CheckAll();}
	
	}
	
	function adaptation_access_deny_check(){
		if(document.getElementById('adaptation_access_deny').checked){DisableAllInstead('adaptation_access_deny');}else{CheckAll();}
	}
	
	function url_rewrite_access_deny_check(){
		if(document.getElementById('url_rewrite_access_deny').checked){DisableAllInstead('url_rewrite_access_deny');}else{CheckAll();}
	}

	function tcp_outgoing_tosCheck(){
		document.getElementById('tcp_outgoing_tos_value').disabled=true;
		if(document.getElementById('tcp_outgoing_tos').checked){
			DisableAllInstead('tcp_outgoing_tos');
			document.getElementById('tcp_outgoing_tos_value').disabled=false;
		}else{
			document.getElementById('tcp_outgoing_tos_value').disabled=true;
			CheckAll();
		}
	}
	
	function deny_access_except_check(){
		if(document.getElementById('deny_access_except').checked){DisableAllInstead('deny_access_except');}else{CheckAll();}
	}
	limit_bandwidth_check();
	access_allow_check();
	access_deny_check();
	deny_access_except_check();
	tcp_outgoing_tosCheck();
	cache_deny_check();
	adaptation_access_deny_check();
	url_rewrite_access_deny_check();
	</script>
	
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function acl_main_rule_edit(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	if(!isset($_POST["tcp_outgoing_tos_value"])){$_POST["tcp_outgoing_tos_value"]=null;}
	try {
		
		$q=new mysql_squid_builder();
		$acl=new squid_acls_groups();
		$ID=$_POST["ID"];
		$aclname=$_POST["aclrulename"];
		$sql="UPDATE webfilters_sqacls SET aclname='$aclname' WHERE ID='$ID'";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		if(!$acl->aclrule_edittype($ID,"access_allow",$_POST["access_allow"])){return;}
		if(!$acl->aclrule_edittype($ID,"url_rewrite_access_deny",$_POST["url_rewrite_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"access_deny",$_POST["access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"adaptation_access_deny",$_POST["adaptation_access_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"cache_deny",$_POST["cache_deny"])){return;}
		if(!$acl->aclrule_edittype($ID,"deny_access_except",$_POST["deny_access_except"])){return;}
		if(!$acl->aclrule_edittype($ID,"tcp_outgoing_tos",$_POST["tcp_outgoing_tos"],$_POST["tcp_outgoing_tos_value"])){return;}
		if(!$acl->aclrule_edittype($ID,"delay_access",$_POST["delay_access"],$_POST["delay_access_id"])){return;}								
	} catch (Exception $e) {
		echo $e->getMessage();
		return ;
	}
	
	
	

}

function TemplateName($md5){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	if($md5==null){return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <strong>{default}</strong>");}
	$sql="SELECT template_title FROM squidtpls WHERE `zmd5`='{$acltpl}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$jstpl="Loadjs('squid.templates.php?Zoom-js=$md5&subject=". base64_encode($ligne["template_title"])."');";
	return $tpl->_ENGINE_parse_body("<br>{and_display_error_page}: <a href=\"javascript:blur();\" OnClick=\"$jstpl\" style='font-size:12px;text-decoration:underline'>{$ligne["template_title"]}</a>");
	}



function acl_rule_save(){
	$q=new mysql_squid_builder();
	$ID=$_POST["ID"];
	$aclname=$_POST["aclname-save"];
	
	if($ID<0){
		$sql="INSERT INTO webfilters_sqacls (aclname,enabled) VALUES ('$aclname',1)";
	}
	
	$q->CheckTables();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
		
}

function acl_rule_delete(){
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$ID=$_POST["acl-rule-delete"];
	$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE aclid='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}		
	
	
}

function squid_compile(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");	
	
}

function acl_rule_enable(){
		$q=new mysql_squid_builder();
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET enabled={$_POST["enable"]} WHERE ID={$_POST["acl-rule-enable"]}");
		if(!$q->ok){echo $q->mysql_error;return;}	
		
	}
	
function acl_rule_order(){
	$q=new mysql_squid_builder();
	$ID=$_POST["acl-rule-order"];
	$neworder=$_POST["acl-rule-value"];
	$sql="SELECT ID FROM webfilters_sqacls WHERE `xORDER`='{$neworder}'";	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["ID"]>0){
		$alt=$neworder+1;
		$sql="UPDATE webfilters_sqacls SET xORDER=$alt WHERE `ID`={$ligne["ID"]}";
		$q->QUERY_SQL($sql);
	}
	
	$sql="UPDATE webfilters_sqacls SET xORDER=$neworder WHERE `ID`={$ID}";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}	
	
	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}	
	
}
	
function acl_rule_move(){
	
	$q=new mysql_squid_builder();
	$sql="SELECT xORDER FROM webfilters_sqacls WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$xORDER_ORG=$ligne["xORDER"];
	$xORDER=$xORDER_ORG;
	if($_POST["acl-rule-dir"]==1){$xORDER=$xORDER_ORG-1;}
	if($_POST["acl-rule-dir"]==0){$xORDER=$xORDER_ORG+1;}
	if($xORDER<0){$xORDER=0;}
	$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER WHERE `ID`='{$_POST["acl-rule-move"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;;return;}
	//echo $sql."\n";
	
	if($_POST["acl-rule-dir"]==1){
		$xORDER2=$xORDER+1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' AND xORDER=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	if($_POST["acl-rule-dir"]==0){
		$xORDER2=$xORDER-1;
		if($xORDER2<0){$xORDER2=0;}
		$sql="UPDATE webfilters_sqacls SET xORDER=$xORDER2 WHERE `ID`<>'{$_POST["acl-rule-move"]}' AND xORDER=$xORDER";
		$q->QUERY_SQL($sql);
		//echo $sql."\n";
		if(!$q->ok){echo $q->mysql_error;return;}
	}

	$c=0;
	$sql="SELECT ID FROM webfilters_sqacls ORDER BY xORDER";
	$results = $q->QUERY_SQL($sql);

	while ($ligne = mysql_fetch_assoc($results)) {	
		$q->QUERY_SQL("UPDATE webfilters_sqacls SET xORDER=$c WHERE `ID`={$ligne["ID"]}");
		$c++;
	}
	
	
}

function page(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$description=$tpl->_ENGINE_parse_body("{description}");
	$rule=$tpl->_ENGINE_parse_body("{rule}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$groups=$tpl->_ENGINE_parse_body("{proxy_objects}");
	$delete_rule_ask=$tpl->javascript_parse_text("{delete_rule_ask}");
	$apply_params=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$t=time();
	$order=$tpl->javascript_parse_text("{order}");
	$squid_templates_error=$tpl->javascript_parse_text("{squid_templates_error}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var DeleteSquidAclGroupTemp=0;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?acls-list=yes&t=$t&toexplainorg=table-$t',
	dataType: 'json',
	colModel : [
		{display: '$rule', name : 'aclname', width : 249, sortable : true, align: 'left'},
		{display: '$description', name : 'items', width : 457, sortable : false, align: 'left'},
		{display: '', name : 'up', width : 13, sortable : false, align: 'center'},
		{display: '', name : 'xORDER', width : 13, sortable : true, align: 'center'},
		{display: '', name : 'none2', width : 15, sortable : true, align: 'left'},
		{display: '', name : 'none3', width : 25, sortable : false, align: 'left'},
		
	],
buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddAcl},
	{name: '$groups', bclass: 'Group', onpress : GroupsSection$t},
	{name: '$bandwith', bclass: 'Network', onpress : BandwithSection$t},
	{separator: true},
	{name: '$squid_templates_error', bclass: 'Script', onpress : SquidTemplatesErrors$t},
	{separator: true},
	{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
		],	
	searchitems : [
		{display: '$rule', name : 'aclname'},
		],
	sortname: 'xORDER',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 869,
	height: 450,
	singleSelect: true
	
	});   
});
function AddAcl() {
	Loadjs('$page?Addacl-js=yes&ID=-1&t=$t');
	
}	

function GroupsSection$t(){
	Loadjs('squid.acls.groups.php?js=yes&toexplainorg=table-$t');
}

function BandwithSection$t(){
	Loadjs('squid.bandwith.php?by-acls-js=yes&t=$t');

}

	var x_EnableDisableAclRule= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}

function AclUpDown(ID,dir){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-move', ID);
		XHR.appendData('acl-rule-dir', dir);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule);  	
}

function ChangeRuleOrder(ID,xdef){
	var neworder=prompt('$order',xdef);
	if(neworder){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-order', ID);
		XHR.appendData('acl-rule-value', neworder);
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule);  	
	}
}

function SquidTemplatesErrors$t(){
	Loadjs('squid.templates.php');
}


	var x_DeleteSquidAclGroup= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		if(document.getElementById('main_filter_rule_edit')){RefreshTab('main_filter_rule_edit');}
		if(document.getElementById('main_dansguardian_tabs')){RefreshTab('main_dansguardian_tabs');}
		$('#rowtime'+TimeRuleIDTemp).remove();
	}
	

	
	var x_SquidBuildNow= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}	
	
	
	function SquidBuildNow$t(){
		Loadjs('squid.compile.php');
		
	
	}

	var x_DeleteSquidAclRule= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#rowacl'+DeleteSquidAclGroupTemp).remove();
	}	
	
	
	function DeleteSquidAclRule(ID){
		DeleteSquidAclGroupTemp=ID;
		if(confirm('$delete_rule_ask :'+ID)){
			var XHR = new XHRConnection();
			XHR.appendData('acl-rule-delete', ID);
			XHR.sendAndLoad('$page', 'POST',x_DeleteSquidAclRule);
		}  		
	}


	
	function EnableDisableAclRule(ID){
		var XHR = new XHRConnection();
		XHR.appendData('acl-rule-enable', ID);
		if(document.getElementById('aclid_'+ID).checked){XHR.appendData('enable', '1');}else{XHR.appendData('enable', '0');}
		XHR.sendAndLoad('$page', 'POST',x_EnableDisableAclRule);  		
	}		
	
	

	
</script>
	
	";
	
	echo $html;
	
}	


function acl_list(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="webfilters_sqacls";
	$page=1;

	if($q->COUNT_ROWS($table)==0){json_error_show("No rules....");}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No rules....");}
	
	$acls=new squid_acls_groups();
	$order=$tpl->_ENGINE_parse_body("{order}:");
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$disable=Field_checkbox("aclid_{$ligne['ID']}", 1,$ligne["enabled"],"EnableDisableAclRule('{$ligne['ID']}')");
		$ligne['aclname']=utf8_encode($ligne['aclname']);
		$delete=imgsimple("delete-24.png",null,"DeleteSquidAclRule('{$ligne['ID']}')");
		if($ligne["enabled"]==0){$color="#9C9C9C";}
		
		$explain=$tpl->_ENGINE_parse_body($acls->ACL_MULTIPLE_EXPLAIN($ligne['ID'],$ligne["enabled"]));
		
		$up=imgsimple("arrow-up-16.png","","AclUpDown('{$ligne['ID']}',1)");
		$down=imgsimple("arrow-down-18.png","","AclUpDown('{$ligne['ID']}',0)");
		
		
	$data['rows'][] = array(
		'id' => "acl{$ligne['ID']}",
		'cell' => array("<a href=\"javascript:blur();\"  OnClick=\"javascript:Loadjs('$MyPage?Addacl-js=yes&ID={$ligne['ID']}&t={$_GET["t"]}');\" 
		style='font-size:16px;text-decoration:underline;color:$color'>{$ligne['aclname']}</span></A>
		<div style='font-size:11px'><i>$order&laquo;<a href=\"javascript:blur();\"
		Onclick=\"javascript:ChangeRuleOrder({$ligne['ID']},{$ligne["xORDER"]});\"
		style=\"text-decoration:underline\">{$ligne["xORDER"]}</a>&raquo;</i></div>",
		"<span style='font-size:12px;color:$color'>$explain</span>",
		$up,$down,$disable,$delete)
		);
	}
	
	
	echo json_encode($data);	
}