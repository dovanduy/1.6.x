<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	header("content-type: application/javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["DYN_TTL"])){save();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->_ENGINE_parse_body("{options}");
	echo "YahooWin(650,'$page?popup=yes&t={$_GET["t"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$sock=new sockets();
	$ACLS_OPTIONS=unserialize(base64_decode($sock->GET_INFO("AclsOptions")));
	if(!is_numeric($ACLS_OPTIONS["DYN_TTL"])){$ACLS_OPTIONS["DYN_TTL"]=120;}
	if(!is_numeric($ACLS_OPTIONS["CHILDREN_STARTUP"])){$ACLS_OPTIONS["CHILDREN_STARTUP"]=5;}
	if(!is_numeric($ACLS_OPTIONS["CHILDREN_IDLE"])){$ACLS_OPTIONS["CHILDREN_IDLE"]=5;}
	if(!is_numeric($ACLS_OPTIONS["DYN_LOG_LEVEL"])){$ACLS_OPTIONS["DYN_LOG_LEVEL"]=0;}

	$UseDynamicGroupsAcls=$sock->GET_INFO("UseDynamicGroupsAcls");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	$DynamicGroupsAclsTTL=$sock->GET_INFO("DynamicGroupsAclsTTL");
	if(!is_numeric($UseDynamicGroupsAcls)){$UseDynamicGroupsAcls=0;}
	if(!is_numeric($DynamicGroupsAclsTTL)){$DynamicGroupsAclsTTL=3600;}
	if($DynamicGroupsAclsTTL<5){$DynamicGroupsAclsTTL=5;}	
	$SquidDebugAcls=$sock->GET_INFO("SquidDebugAcls");
	if(!is_numeric($SquidDebugAcls)){$SquidDebugAcls=0;}	
	
	for($i=0;$i<6;$i++){$DYN_LOG_LEVEL[$i]=$i;	}
	
	$export=Paragraphe("64-export.png", "{export_rules}", "{export_acl_rules_explain}",
			"javascript:Loadjs('squid.acls.export.php')");
	
	$import=Paragraphe("64-import.png", "{import_rules}", "{import_acl_rules_explain}",
			"javascript:Loadjs('squid.acls.import.php')");	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td align='center'>$export</td>
		<td align='center'>$import</td>
	</tr>
	</table>
	<div id='serverkerb-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2 style='font-size:16px'>{dynamic_acls_newbee}:<hr style='border-top:1px solid;margin:5px 0'></td>
	<tr>
		<td class=legend style='font-size:14px'>{TTL_CACHE}:</td>
		<td style='font-size:14px'>". Field_text("DYN_TTL",$ACLS_OPTIONS["DYN_TTL"],"font-size:14px;padding:3px;width:90px")."&nbsp;{seconds}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{CHILDREN_STARTUP}:</td>
		<td style='font-size:14px'>". Field_text("CHILDREN_STARTUP",$ACLS_OPTIONS["CHILDREN_STARTUP"],"font-size:14px;padding:3px;width:60px")."&nbsp;{processes}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{CHILDREN_IDLE}:</td>
		<td style='font-size:14px'>". Field_text("CHILDREN_IDLE",$ACLS_OPTIONS["CHILDREN_IDLE"],"font-size:14px;padding:3px;width:60px")."&nbsp;{processes}</td>
	</tr>									
	<tr>
		<td class=legend style='font-size:14px'>{log_level}:</td>
		<td style='font-size:14px'>". Field_array_Hash($DYN_LOG_LEVEL, "DYN_LOG_LEVEL",$ACLS_OPTIONS["DYN_LOG_LEVEL"],null,null,0,"font-size:14px")."</td>
	</tr>			
	<tr>
		<td colspan=2 style='font-size:16px'>{dynamic_acls_group}:<hr style='border-top:1px solid;margin:5px 0'></td>
	<tr>
	<tr>
		<td class=legend style='font-size:14px'>{use_dynamic_groups_acls}:</td>
		<td>". Field_checkbox("UseDynamicGroupsAcls-$t",1,$UseDynamicGroupsAcls,"UseDynamicGroupsAclsCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{TTL_CACHE}:</td>
		<td style='font-size:14px'>". Field_text("DynamicGroupsAclsTTL-$t",$DynamicGroupsAclsTTL,"font-size:14px;padding:3px;width:90px")."&nbsp;{seconds}</td>
	</tr>	

	<tr>
		<td colspan=2 style='font-size:16px'>{service_options}:<hr style='border-top:1px solid;margin:5px 0'></td>
	<tr>				
	<tr>
		<td class=legend style='font-size:14px'>{debug_acls}:</td>
		<td>". Field_checkbox("SquidDebugAcls-$t",1,$SquidDebugAcls)."</td>
	</tr>				
				
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",16)."</td>
	</tr>
		</table>
		<script>
			var x_Save$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);document.getElementById('serverkerb-$t').innerHTML='';return;}
			document.getElementById('serverkerb-$t').innerHTML='';
			$('#table-$t').flexReload();
		}
	
	function Save$t(){
	var XHR = new XHRConnection();
	var UseDynamicGroupsAcls=0;
	var SquidDebugAcls=0;
	if(document.getElementById('UseDynamicGroupsAcls-$t').checked){UseDynamicGroupsAcls=1;}
	if(document.getElementById('SquidDebugAcls-$t').checked){SquidDebugAcls=1;}
	XHR.appendData('UseDynamicGroupsAcls',UseDynamicGroupsAcls);
	XHR.appendData('SquidDebugAcls',SquidDebugAcls);
	XHR.appendData('DynamicGroupsAclsTTL',document.getElementById('DynamicGroupsAclsTTL-$t').value);
	
	XHR.appendData('DYN_TTL',document.getElementById('DYN_TTL').value);
	XHR.appendData('CHILDREN_STARTUP',document.getElementById('CHILDREN_STARTUP').value);
	XHR.appendData('CHILDREN_IDLE',document.getElementById('CHILDREN_IDLE').value);
	XHR.appendData('DYN_LOG_LEVEL',document.getElementById('DYN_LOG_LEVEL').value);
	AnimateDiv('serverkerb-$t');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);
	
	}
	
		function UseDynamicGroupsAclsCheck$t(){
			document.getElementById('DynamicGroupsAclsTTL-$t').disabled=true;
			if(document.getElementById('UseDynamicGroupsAcls-$t').checked){
				document.getElementById('DynamicGroupsAclsTTL-$t').disabled=false;
			}
		}
		UseDynamicGroupsAclsCheck$t();	
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function save(){
	$sock=new sockets();
	$sock->SET_INFO("UseDynamicGroupsAcls", $_POST["UseDynamicGroupsAcls"]);
	$sock->SET_INFO("DynamicGroupsAclsTTL", $_POST["DynamicGroupsAclsTTL"]);
	$sock->SET_INFO("SquidDebugAcls", $_POST["SquidDebugAcls"]);
	
	
	unset($_POST["SquidDebugAcls"]);
	unset($_POST["UseDynamicGroupsAcls"]);
	unset($_POST["DynamicGroupsAclsTTL"]);
	
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "AclsOptions");
}
