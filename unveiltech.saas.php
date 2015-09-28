<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["enable-js"])){enable_js();exit;}
if(isset($_GET["enable-popup"])){enable_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["UtDNSEnable"])){UtDNSEnable();exit;}
if(isset($_GET["30-days"])){trial_js();exit;}
if(isset($_POST["30-days"])){trial_exec();exit;}
tabs();


function enable_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{enable_disable_cloud_protection}");
	echo "YahooWin2(650,'$page?enable-popup=yes','$title')";
}

function trial_js(){
$page=CurrentPageName();
$t=time();
header("content-type: application/x-javascript");
$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);}
	CacheOff();
	RefreshTab('main_WebFilter_SaaS_tabs');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('30-days','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
 Save$t();
";
	echo $html;
}

function trial_exec(){
	
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("squid.php?UtDNSRegister=yes"));
	echo $datas;
}

function enable_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$UtDNSEnable=$sock->GET_INFO("UtDNSEnable");
	if(!is_numeric($UtDNSEnable)){$UtDNSEnable=0;}
	$UtDNSIPAddr=$sock->GET_INFO("UtDNSIPAddr");
	$ip=new IP();
	if(!$ip->isValid($UtDNSIPAddr)){$UtDNSIPAddr=$_SERVER["REMOTE_ADDR"];}
	$t=time();
	$html="
	<div style='width:98%' class=form>

	".Paragraphe_switch_img("{enable_disable_cloud_protection}", "{enable_disable_cloud_protection_explain}","UtDNSEnable-$t",$UtDNSEnable,null,450)."
			
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{redirect_ip_address}:</td>
		<td>". Field_text("UtDNSIPAddr",$UtDNSIPAddr,"font-size:16px;")."</td>
	</tr>
	</table>
			
			
	<div style='width:99%;text-align:right;margin-top:20px'><hr>". button("{apply}","Save$t()",32)."</div>
	</div>
<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>5){alert(results);}
		CacheOff();
		Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
	}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('UtDNSEnable',document.getElementById('UtDNSEnable-$t').value);
		XHR.appendData('UtDNSIPAddr',document.getElementById('UtDNSIPAddr-$t').value);
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}

</script>			
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function UtDNSEnable(){
	$sock=new sockets();
	$sock->SET_INFO("UtDNSEnable", $_POST["UtDNSEnable"]);
	$sock->SET_INFO("UtDNSIPAddr", $_POST["UtDNSIPAddr"]);
	$sock->getFrameWork("squid.php?UtDNSUpdate=yes");
	
}


function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$fontsize=16;
	$array["popup"]="{WebFilter_SaaS}";
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_WebFilter_SaaS_tabs',950)."<script>LeftDesign('webfiltering-white-256-opac20.png');</script>";
	
	echo $html;	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$ldap=new clladp();
	
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$LicenseInfos=unserialize(base64_decode($sock->GET_INFO("LicenseInfos")));
	$WizardSavedSettings=unserialize(base64_decode($sock->GET_INFO("WizardSavedSettings")));
	$smtp_domainname=$WizardSavedSettings["smtp_domainname"];
	$telephone=$WizardSavedSettings["telephone"];
	if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
	if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
	if(!is_numeric($LicenseInfos["EMPLOYEES"])){$LicenseInfos["EMPLOYEES"]=$WizardSavedSettings["employees"];}

	$ldap=new clladp();
	$password=md5($ldap->ldap_password);
	
	
	$UtDNSArticaUser=json_decode(base64_decode($sock->GET_INFO("UtDNSArticaUser")));
	if($UtDNSArticaUser->success){
		$uri[]="https://myaccount.unveiltech.com/autologin.php";
		$uri[]="?email={$LicenseInfos["EMAIL"]}&pwd=$password";
		$uri[]="&goto=https://utdns.unveiltech.com/dashboard.php?hash=$UtDNSArticaUser->hash";
		$urie=@implode("", $uri);
		$t[]=Paragraphe("panel-64.png", "{cloud_panel}", "{cloud_panel_explain}","javascript:s_PopUpFull('$urie',1024,1024)");
		$t[]=Paragraphe("shutdown-green-64.png", "{enable_disable_cloud_protection}", 
				"{enable_disable_cloud_protection_explain}","javascript:Loadjs('$page?enable-js=yes');");
	}else{
		
		$bas="
		<div class=explain style='font-size:16px'>
			{WebFilter_SaaS_AS_EVAL}
		</div>
		<center style='margin:30px'>
					". button("{start_30daybt}","Loadjs('$page?30-days=yes')",34)."</center>
				
		";
		
	}
	
	
	
	
	if(count($t)>0){
		$bas=CompileTr4($t);
		
	}

	$html="<div class=explain style='font-size:16px'>{WebFilter_SaaS_Explain}</div>$bas";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

