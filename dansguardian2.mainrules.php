<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["main-rules"])){rules();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["rules-toolbox"])){rules_toolbox();exit;}
if(isset($_GET["dansguardian-status"])){status_left();exit;}
if(isset($_POST["DansGuardianDeleteMainRule"])){delete_rule();exit;}
if(isset($_GET["rules-table"])){rules_table();exit;}
if(isset($_GET["rules-table-list"])){rules_table_list();exit;}
if(isset($_GET["rules-toolbox-left"])){rules_toolbox_left();exit;}
if(isset($_POST["EnableUFDB2"])){EnableUFDB2();exit;}
if(isset($_GET["CompileUfdbGuardRules"])){CompileUfdbGuardRules_js();exit;}
if(isset($_GET["CompileUfdbGuardRules-popup"])){CompileUfdbGuardRules_popup();exit;}
if(isset($_GET["CompileUfdbGuardRules-perform"])){CompileUfdbGuardRules_perform();exit;}
if(isset($_GET["CompileUfdbGuardRules-check"])){CompileUfdbGuardRules_check();exit;}
if(isset($_GET["UfdbguardEvents"])){UfdbguardEvents_js();exit;}
if(isset($_GET["UfdbguardEvents-popup"])){UfdbguardEvents_popup();exit;}



tabs();


function CompileUfdbGuardRules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$compile_rules=$tpl->_ENGINE_parse_body("{webfilter}::{compile_rules}");
	echo "YahooWinBrowse('700','$page?CompileUfdbGuardRules-popup=yes','$compile_rules')";
	
}

function UfdbguardEvents_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$compile_rules=$tpl->_ENGINE_parse_body("{webfilter}::{service_events}");
	echo "YahooWinBrowse('840','$page?UfdbguardEvents-popup=yes','$compile_rules')";	
	
}

function CompileUfdbGuardRules_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='CompileUfdbGuardRules-$t' style='width:98%;min-height:450px;overflow:auto' class=form></div>
	<script>
		LoadAjax('CompileUfdbGuardRules-$t','$page?CompileUfdbGuardRules-perform=yes');
	</script>
	
	";
	
	echo $html;
}

function UfdbguardEvents_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<div id='UfdbguardEvents-$t' style='width:98%;min-height:450px;overflow:auto' class=form></div>
	<script>
		LoadAjax('UfdbguardEvents-$t','ufdbguard.sevents.php');
	</script>
	
	";
	
	echo $html;	
	
	
}

function CompileUfdbGuardRules_perform(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock->getFrameWork("squid.php?ufdbguard-compile-smooth-tenir=yes");
	$t=time();
	$html="
	<input type='hidden' value='0' id='stop-refresh-$t'>
	<div id='$t'><center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}</center></div>
	<script>
		function CompileUfdbGuardRulesCheck$t(){
			if(!document.getElementById('stop-refresh-$t')){return;}
			var StopRefresh=document.getElementById('stop-refresh-$t').value;
			if(StopRefresh==1){return;}
			if(!YahooWinBrowseOpen()){return;}
			LoadAjax('$t','$page?CompileUfdbGuardRules-check=yes&t=$t');
		}
		
		setTimeout('CompileUfdbGuardRulesCheck$t()',3000);
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

	//
	
	
	
	while (list ($num, $ligne) = each ($datas) ){
		if($ligne==null){continue;}
		echo "<div><code style='font-size:12px'>$ligne</code></div>";
		
	}
}

function CompileUfdbGuardRules_check(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$StopRefresh=$tpl->_ENGINE_parse_body("{StopRefresh}");
	$t=$_GET["t"];
	if(!is_file("ressources/logs/web/compile.ufdbguard.interface.txt")){
		echo $tpl->_ENGINE_parse_body("<center style='margin:30px;font-size:18px'>{please_wait_compiling_rules}<img src='img/ajax-loader.gif' style='margin:20px'></center>
		<script>
			setTimeout('CompileUfdbGuardRulesCheck$t()',3000);
		</script>
		
		");
		return;
		
	}
	
	$datas=file("ressources/logs/web/compile.ufdbguard.interface.txt");
	while (list ($num, $ligne) = each ($datas) ){
		$ligne=str_replace("\r", "", $ligne);
		if($ligne==null){continue;}
		echo "<div><code style='font-size:12px'>$ligne</code></div>";
		
	}

	
	echo
	
	"
	<center><span id='img-$t'><img src='img/ajax-loader.gif' style='margin:20px'></span>
		<center>
			<span id='bt-$t'>". button("$StopRefresh","document.getElementById('stop-refresh-$t').value=1;document.getElementById('img-$t').innerHTML='';document.getElementById('bt-$t').innerHTML='';","14px")."</span>
		</center>
	</center>
	<script>
			setTimeout('CompileUfdbGuardRulesCheck$t()',8000);
	</script>";
	
	
}


function tabs(){
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["main-rules"]='{rules}';
	$array["quotas"]='{quotas}';
	
	
	if($users->APP_UFDBGUARD_INSTALLED){
		$array["rewrite-rules"]='{rewrite_rules}';
	}
	$array["section_basic_filters-bandwith"]='{bandwith_limitation_full}';
	$array["section_basic_filters-time"]='{connection_time}';
	$array["section_basic_filters-terms"]='{terms_groups}';
	
	
	
	if($users->C_ICAP_INSTALLED){
		if($users->C_ICAP_DNSBL){
			//$array["c-icap-dnsbl"]='{CICAP_DNSBL}';
		}
	}	
	
	$array["ufdbguard-status"]="{service_status}";
	$fontsize=14;
	if(count($array)>5){$fontsize=11.5;}
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="rewrite-rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.rewrite.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}
		
			
		if($num=="quotas"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.helpers.quotas.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		if($num=="section_basic_filters-bandwith"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.bandwith.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}	

		if($num=="section_basic_filters-time"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.connection-time.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}
		
		if($num=="section_basic_filters-terms"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.terms.groups.php\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
			
		}		

		if($num=="c-icap-dnsbl"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"c-icap.dnsbl2.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
				
		if($num=="ufdbguard-status"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.status.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<center><div id='rules-toolbox'></div></center>
	<div id=main_dansguardian_mainrules style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_dansguardian_mainrules').tabs();
			});
		</script>";	

}




function TimeToText($TimeSpace){
	$RuleBH=array("inside"=>"{inside_time}","outside"=>"{outside_time}","none"=>"{disabled}");
	if($TimeSpace["RuleMatchTime"]==null){$TimeSpace["RuleMatchTime"]="none";}
	if($TimeSpace["RuleAlternate"]==null){$TimeSpace["RuleAlternate"]="none";}	
	if($TimeSpace["RuleMatchTime"]=="none"){return;}
	$q=new mysql_squid_builder();
	
	$RULESS["none"]="{none}";
	$RULESS[0]="{default}";
	$sql="SELECT ID,enabled,groupmode,groupname FROM webfilter_rules WHERE enabled=1 ORDER BY groupname";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){$RULESS[$ligne["ID"]]=$ligne["groupname"];}	
	
	
	$daysARR=array("m"=>"Monday","t"=>"Tuesday","w"=>"Wednesday","h"=>"Thursday","f"=>"Friday","a"=>"Saturday","s"=>"Sunday");	
	while (list ($TIMEID, $array) = each ($TimeSpace["TIMES"]) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$dd=array();
		if(!is_array($array["DAYS"])){return;}
		
		while (list ($day, $val) = each ($array["DAYS"])){if($val==1){$dd[]="{{$daysARR[$day]}}";}}
		$daysText=@implode(", ", $dd);
		
	if(strlen($array["BEGINH"])==1){$array["BEGINH"]="0{$array["BEGINH"]}";}
	if(strlen($array["BEGINM"])==1){$array["BEGINM"]="0{$array["BEGINM"]}";}
	if(strlen($array["ENDH"])==1){$array["ENDH"]="0{$array["ENDH"]}";}
	if(strlen($array["ENDM"])==1){$array["ENDM"]="0{$array["ENDM"]}";}

	$f[]="<div style='font-weight:normal'>{$RuleBH[$TimeSpace["RuleMatchTime"]]} $daysText {from} {$array["BEGINH"]}:{$array["BEGINM"]} {to} {$array["ENDH"]}:{$array["ENDM"]} {then}
	 {alternate_rule} {to} {$RULESS[$TimeSpace["RuleAlternate"]]}</div>";		
		
	}
	
	
	return @implode("\n", $f);

	
}


function rules_ufdb_not_installed(){
	$tpl=new templates();
	$page=CurrentPageName();	
	
	$title=$tpl->javascript_parse_text('{ERROR_NOT_INSTALLED_REDIRECT}');
		$html="
		<center>
		<table style='width:80%' class=form>
		<tr>
			<td valign='top' width=1%><img src='img/software-remove-128.png'></td>
			<td valign='top' width=99%><div style='font-size:18px'>{ERROR_UFDBGUARD_NOTINSTALLED}</div>
			<div style='float:right'>". imgtootltip("48-refresh.png","{refresh}","RefreshTab('main_dansguardian_mainrules');")."</div>
			<p style='font-size:14px'>{ufdbguard_simple_intro}
			</p>
			<div style='text-align:right'><hr>". button("{install}","InstallUfdbGuard()",18)."</div>
		</td>
		</tr>
		</table>
		</center>
			<script>
				function InstallUfdbGuard(){
					Loadjs('setup.index.progress.php?product=APP_UFDBGUARD&start-install=yes');
				}
			</script>";
		echo $tpl->_ENGINE_parse_body($html);
	
}


function rules(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	
	if(!$users->APP_UFDBGUARD_INSTALLED){rules_ufdb_not_installed();return;}
	
	$html="<table style='width:100%'>
	<tr>
	<td valign='top' width=5%><div id='rules-toolbox-left' style='margin-left:-18px;'></div></td>
	<td valign='top' width=99% style='padding-left:8px'><div id='rules-table'></div></td>
	</tr>
	</table>
	<script>
		LoadAjax('rules-table','$page?rules-table=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function rules_toolbox_left(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	$mouse="OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	
	$Computers=$q->COUNT_ROWS("webfilters_nodes");
	$Computers=numberFormat($Computers,0,""," ");

	$UsersRequests=$q->COUNT_ROWS("webfilters_usersasks");

	$tablescat=$q->LIST_TABLES_CATEGORIES();
	$CountDeCategories=numberFormat(count($tablescat),0,""," ");
	
	
	$html="
	<table style='width:95%' class=form>
	<tr>
	<td valign='top' width=1%><img src='img/computer-32.png'></td>
	<td valign='top' width=99%>
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
			<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
			OnClick=\"javascript:Loadjs('squid.nodes.php')\" nowrap><b>$Computers</b> {computers}</td>
		</tr>
		</table>
	</td>
	</tr>
	
	<tr>
		<td valign='top' width=1%><img src='img/members-32.png'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squidguardweb.unblock.console.php')\" nowrap><b>$UsersRequests</b> {unblocks}</td>
			</tr>
			</table>
		</td>
	</tr>

	<tr>
		<td valign='top' width=1%><img src='img/service-restart-32.png'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.php?force-reload-js=yes')\" nowrap>{reload_service}</td>
			</tr>
			</table>
		</td>
	</tr>	
	<tr>
		<td valign='top' width=1%><img src='img/events-32.png'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.sevents.php?js=yes')\" nowrap>{service_events}</td>
			</tr>
			</table>
		</td>
	</tr>		
	<tr>
		<td valign='top' width=1%><img src='img/32-categories.png'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('squid.categories.php')\" nowrap><strong>$CountDeCategories&nbsp;{categories}</td>
			</tr>
			</table>
		</td>
	</tr>	

	<tr>
		<td valign='top' width=1%><img src='img/script-32.png'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td valign='top' width=1%><img src='img/arrow-right-16.png'></td>
				<td valign='top' $mouse style='font-size:13px;text-decoration:underline' 
				OnClick=\"javascript:Loadjs('ufdbguard.databases.php?scripts=config-file');\" nowrap>{config_file_tiny}</td>
			</tr>
			</table>
		</td>
	</tr>		
	
	</table> 
	
	
	";
	
	$sock=new sockets();
	$EnableUfdbGuard=$sock->GET_INFO("EnableUfdbGuard");
	if(!is_numeric($EnableUfdbGuard)){$EnableUfdbGuard=0;}
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$UseRemoteUfdbguardService=$sock->GET_INFO("UseRemoteUfdbguardService");
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");		
	
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($UseRemoteUfdbguardService)){$UseRemoteUfdbguardService=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableUfdbGuard=1;}

	
	$users=new usersMenus();
	if(!$users->APP_UFDBGUARD_INSTALLED){$EnableUfdbGuard=0;}
	
	
	if($EnableUfdbGuard==1){
		if(!$users->CORP_LICENSE){
		echo $tpl->_ENGINE_parse_body("
		<div id='$t'>
			<table style='width:95%;margin-bottom:20px' class=form>
			<tr>
			<td valign='top' width=99%>
				<div style='font-size:14px;color:#CC0A0A'>
				<img src='img/info-48.png' style='float:left;margin:3px'>
				<span style='font-size:11px'>{warn_ufdbguard_no_license}</span>
				<table style='width:100%'>
				<tr>
					<td width=1%><img src='img/arrow-right-16.png'></td>
					<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('artica.license.php');\" 
					style='text-decoration:underline;color:black'>{artica_license}</a></td>
					</tr>
					</table>
				</div>
			</td>
			</tr>
			</table>
			</div>");			
			
			
		}
		
		
		
	}
	
	$t=time();
	if($EnableUfdbGuard==0){
		echo $tpl->_ENGINE_parse_body("
		<div id='$t'>
	<table style='width:95%;margin-bottom:20px' class=form>
	<tr>
	<td valign='top' width=99%>
		<div style='font-size:14px;color:#CC0A0A'>
		<img src='img/warning-panneau-42.png' style='float:left;margin:3px'>
		<span style='font-size:11px'>{warn_ufdbguard_not_activated_explain}</span>
		<table style='width:100%'>
		<tr>
			<td width=1%><img src='img/arrow-right-16.png'></td>
			<td width=99%><a href=\"javascript:blur();\" OnClick=\"javascript:EnableUFDB2();\" 
			style='text-decoration:underline;color:black'>{activate_webfilter_engine}</a></td>
			</tr>
			</table>
		</div>
	</td>
	</tr>
	</table>
	</div>
	<script>
	var x_EnableUFDB2=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>3){alert(tempvalue);}
      RefreshTab('main_dansguardian_mainrules');
	  }
	
	function EnableUFDB2(){
	  var XHR = new XHRConnection();
      XHR.appendData('EnableUFDB2','yes');
      AnimateDiv('$t');
      XHR.sendAndLoad('$page', 'POST',x_EnableUFDB2);
	 }	
	
	</script>
	
	");
		
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rules_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();	
	$add_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$rule_text=$tpl->_ENGINE_parse_body("{rule}");
	$TimeSpace=TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}	
	$groups=$tpl->_ENGINE_parse_body("{groups}");
	$blacklists=$tpl->_ENGINE_parse_body("{blacklists}");
	$whitelists=$tpl->_ENGINE_parse_body("{whitelists}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$service_events=$tpl->_ENGINE_parse_body("{service_events}");
	$global_parameters=$tpl->_ENGINE_parse_body("{global_parameters}");
	$ldap_parameters=$tpl->_ENGINE_parse_body("{ldap_parameters2}");
	$config_file=$tpl->_ENGINE_parse_body("{config_file}");
	$error_ldap=null;
	$buttons="
	buttons : [
	{name: '$add_rule', bclass: 'add', onpress : DansGuardianNewRule},
	{name: '$compile_rules', bclass: 'Reconf', onpress : CompileUfdbGuardRules},
	{name: '$global_parameters', bclass: 'Settings', onpress : UfdbGuardConfigs},
	
	
	],";
	
	
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");		
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==1){
		$ad=new ActiveDirectory();
		if($ad->ldapFailed){
			$ad->ldap_last_error=nl2br($ad->ldap_last_error);
			$error_ldap=$tpl->_ENGINE_parse_body("
		<div id='$t'>
	<table style='width:95%' class=form>
	<tr>
	<td valign='top' width=95%>
		<div style='font-size:14px;color:#CC0A0A'>
		<img src='img/error-64.png' style='float:left;margin:3px'>
		<strong style='font-size:14px'>{error}:LDAP&nbsp;&raquo;&nbsp;Active Directory ($ad->ldap_host:$ad->ldap_port)</strong><hr>
		<span style='font-size:11px'>$ad->ldap_last_error</span>
		</div>
		<div style='text-align:right;width:100%'>
		<table style='width:5%' align='right'>
			<tr>
			<td width=1%><img src='img/arrow-right-24.png'></td>
			<td nowrap>		
				<a href=\"javascript:blur();\" 
					OnClick=\"javascript:YahooSearchUser('650','squid.adker.php?ldap-params=yes','$ldap_parameters');\" 
					style='font-size:14px;text-decoration:underline;font-weight:bold'>$ldap_parameters</a>
				</td>
		</tr>
		</table>
		</div>
	</td>
	</tr>
	</table>
	</div>");}
	}	
	
$TBSIZE=223;
$TBWIDTH=615;
if($tpl->language=="fr"){$TBSIZE=204;$TBWIDTH=610;}
	//{display: '&nbsp;', name : 'dup', width :31, sortable : false, align: 'center'}, 
	
$html="
<div style='margin-left:-10px;margin-right:-10px'>
$error_ldap
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rules-table-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$rule_text', name : 'groupname', width : $TBSIZE, sortable : true, align: 'left'},	
		{display: '$groups', name : 'topattern', width :57, sortable : false, align: 'center'},
		{display: '$blacklists', name : 'enabled', width : 101, sortable : false, align: 'center'},
		{display: '$whitelists', name : 'delete', width : 91, sortable : false, align: 'center'},
		{display: '&nbsp;', name : 'dup', width :31, sortable : false, align: 'center'},
		{display: '$delete', name : 'delete', width : 32, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$rule_text', name : 'groupname'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TBWIDTH,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function DansGuardianNewRule(){
		DansGuardianEditRule(-1)
	}

	function DansGuardianEditRule(ID,rname){
		YahooWin3('935','dansguardian2.edit.php?ID='+ID+'&t=$t','$rule_text::'+ID+'::'+rname);
	}
	
	function CompileUfdbGuardRules(){
		Loadjs('$page?CompileUfdbGuardRules=yes');
	}
	
	function UfdbGuardConfigs(){
		Loadjs('ufdbguard.php');
	}
	
	function UfdbguardEvents(){
		Loadjs('$page?UfdbguardEvents=yes');
	}
	

	
		var x_DansGuardianDeleteMainRule= function (obj) {
			var res=obj.responseText;
			if (res.length>3){alert(res);}
			$('#row'+rowid).remove();
		}		
		
		function DansGuardianDeleteMainRule(ID){
			rowid=ID;
			if(confirm('$action_delete_rule')){
				var XHR = new XHRConnection();
		     	XHR.appendData('DansGuardianDeleteMainRule', ID);
		      	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDeleteMainRule);  
			}
		}
		
		function RulesToolBox(){
			LoadAjaxTiny('rules-toolbox','$page?rules-toolbox=yes');
		}
	
	RulesToolBox();	
	LoadAjaxTiny('rules-toolbox-left','$page?rules-toolbox-left=yes');
	
</script>

";	
	
	echo $html;
	
}
	
	
function rules_table_list(){
	
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="webfilter_rules";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	writelogs($sql." ==> ". mysql_num_rows($results)." items",__FUNCTION__,__FILE__,__LINE__);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}		
	
	
	$js="DansGuardianEditRule('0','default')";
	$delete="&nbsp;";
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>Default</a>
			<i style='font-size:10px'>$TimeSpace</i>". rules_dans_time_rule(0)."
			
			",
			"<span style='font-size:14px'>-</span>",
			"<span style='font-size:14px'>". COUNTDEGBLKS(0)."</span>",
			"<span style='font-size:14px'>". COUNTDEGBWLS(0)."</span>",
			"&nbsp;",
			$delete )
		);
	
while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$md5=md5($ligne["ID"]);
		$ligne["groupname"]=utf8_encode($ligne["groupname"]);
		$delete=imgtootltip("delete-24.png","{delete}","DansGuardianDeleteMainRule('{$ligne["ID"]}')");
		
		$js="DansGuardianEditRule('{$ligne["ID"]}','{$ligne["groupname"]}');";
		$TimeSpace=TimeToText(unserialize(base64_decode($ligne["TimeSpace"])));
		
		$color="black";
		if($ligne["enabled"]==0){$color="#CCCCCC";}
		
		$rules_dans_time_rule=rules_dans_time_rule($ligne["ID"]);
		if($ligne["groupmode"]==0){
			$warn="<div style='float:right'><img src='img/stop-24.png'></div>";
		}		
		$duplicate=imgsimple("duplicate-24.png",null,"Loadjs('dansguardian2.duplicate.php?from={$ligne['ID']}&t=$t')");
		

	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;color:$color;text-decoration:underline'>{$ligne["groupname"]}</a>
			<i style='font-size:10px'>$TimeSpace</i>$rules_dans_time_rule",
			"<span style='font-size:14px;color:$color;'>". COUNTDEGROUPES($ligne["ID"])."</span>",
			"<span style='font-size:14px;color:$color;'>". COUNTDEGBLKS($ligne["ID"])."</span>",
			"<span style='font-size:14px;color:$color;'>". COUNTDEGBWLS($ligne["ID"])."</span>",
			$duplicate,
			$delete )
		);
	}
	
	
	
	
echo json_encode($data);	

}

function rules_dans_time_rule($RULEID){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT * FROM webfilters_dtimes_rules WHERE ruleid='$RULEID' and enabled=1";
	$results = $q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){return;}
	$text="<table style='width:100%'><tbody>";
	while ($ligne = mysql_fetch_assoc($results)) {
$ligne['TimeName']=utf8_encode($ligne['TimeName']);
		$TimeSpace=unserialize($ligne["TimeCode"]);
		$days=array("0"=>"Monday","1"=>"Tuesday","2"=>"Wednesday","3"=>"Thursday","4"=>"Friday","5"=>"Saturday","6"=>"Sunday");
		$f=array();
		while (list ($num, $val) = each ($TimeSpace["DAYS"]) ){	
			if($num==array()){continue;}
			if(!isset($days[$num])){continue;}
			if($days[$num]==array()){continue;}
			if($val<>1){continue;}
			$f[]= "{{$days[$num]}}";
		}	
		
		
		if(strlen($TimeSpace["BEGINH"])==1){$TimeSpace["BEGINH"]="0{$TimeSpace["BEGINH"]}";}
		if(strlen($TimeSpace["BEGINM"])==1){$TimeSpace["BEGINM"]="0{$TimeSpace["BEGINM"]}";}
		if(strlen($TimeSpace["ENDH"])==1){$TimeSpace["ENDH"]="0{$TimeSpace["ENDH"]}";}
		if(strlen($TimeSpace["ENDM"])==1){$TimeSpace["ENDM"]="0{$TimeSpace["ENDM"]}";}

		
		$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks 
		WHERE webfilter_id={$ligne["ID"]} AND modeblk=0"));
		$blacklist=$ligneTOT["tcount"];
		
		$ligneTOT=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(ID) as tcount FROM webfilters_dtimes_blks 
		WHERE webfilter_id={$ligne["ID"]} AND modeblk=1"));
		$whitelist=$ligneTOT["tcount"];	

		
		
		$text=$text."<tr style='background-color:transparent'>
			<td width=1%><img src='img/clock_24.png'></td>
			<td width=99%><div style='font-size:11px'>
				<strong>{$ligne['TimeName']}</strong>: {from} {$TimeSpace["BEGINH"]}:{$TimeSpace["BEGINM"]} {to} {$TimeSpace["ENDH"]}:{$TimeSpace["ENDM"]} (".@implode(", ", $f).")
				<div><i>{blacklist}:<b>$blacklist</b> {whitelist}:<b>$whitelist</b></div>
			</td>
	</tR>";
		
		

	}
	
	$text=$text."</tbody></table>";
	return $text;
}

function COUNTDEGROUPES($ruleid){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(ID) as tcount FROM webfilter_assoc_groups WHERE webfilter_id='$ruleid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
	return $ligne["tcount"];
}

function COUNTDEGBLKS($ruleid){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=0" ;
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
	return $ligne["tcount"];	
}
function COUNTDEGBWLS($ruleid){
	$q=new mysql_squid_builder();
	$sql="SELECT COUNT(ID) as tcount FROM webfilter_blks WHERE webfilter_id='$ruleid' AND modeblk=1" ;
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!is_numeric($ligne["tcount"])){$ligne["tcount"]=0;}
	return $ligne["tcount"];	
}

function delete_rule(){
	$q=new mysql_squid_builder();
	$ID=$_POST["DansGuardianDeleteMainRule"];
	$q->QUERY_SQL("DELETE FROM webfilter_assoc_groups WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_blks WHERE webfilter_id='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webfilter_rules WHERE ID='$ID'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("webfilter.php?compile-rules=yes");
	
}

function rules_toolbox(){
	
	$tpl=new templates();

	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=0"));
	$countblk=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=1"));
	$countwhl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=2"));
	$countWwhl=$ligne["tcount"];	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=3"));
	$countBwhl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=4"));
	$countCacheWl=$ligne["tcount"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=5"));
	$countUserAgentWL=$ligne["tcount"];	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(pattern) as tcount FROM webfilters_blkwhlts WHERE blockType=6"));
	$countMimeType=$ligne["tcount"];		
	
	
	
	
	$html="<table style='width:5%' class=form>
	<tbody>
	<tr>
		<td width=1% style='font-size:22px'>$countblk</td>
		<td width=1% style='font-size:22px'>". imgtootltip("32-black-computer.png","{black_ip_group_text}","Loadjs('squid.hosts.blks.php?blk=0')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countwhl</td>
		<td width=1% style='font-size:22px'>". imgtootltip("32-white-computer.png","{white_ip_group_text}","Loadjs('squid.hosts.blks.php?blk=1')")."</td>
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countWwhl</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("domain-whitelist-w32.png","{dansguardian_exception_site_list}","Loadjs('squid.hosts.blks.php?blk=2')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countCacheWl</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("database-32-delete.png","{notcaching_websites}","Loadjs('squid.hosts.blks.php?blk=4')")."</td>	
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countUserAgentWL</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("user-agent-ok-32.png","{ban_browsers_text}","Loadjs('squid.hosts.blks.php?blk=5')")."</td>				
		<td width=1% style='font-size:22px'>&nbsp;|&nbsp;</td>
		<td width=1% style='font-size:22px'>$countMimeType</td>	
		<td width=1% style='font-size:22px'>". imgtootltip("32-mime.png","{white_mime_type}","Loadjs('squid.hosts.blks.php?blk=6')")."</td>				
				
		
	</tr>
	
	</tbody>
	</table>
		
		
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function EnableUFDB2(){
	$sock=new sockets();
	$sock->SET_INFO("EnableUfdbGuard",1);
	$sock->getFrameWork("cmd.php?reload-dansguardian=yes");
	$sock->getFrameWork("cmd.php?squidnewbee=yes");	
}




