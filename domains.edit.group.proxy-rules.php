<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.samba.inc');
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
//if(count($_POST)>0)
$usersmenus=new usersMenus();
if(!$usersmenus->AllowAddUsers){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
	

page();


function page(){

	$LoadGroupSettings=$_GET["LoadGroupSettings"];
	
	if(strpos(" ".strtolower($LoadGroupSettings), "cn=")>0){
		return page_active_directory();
		
		
	}
	

}
function page_active_directory(){
	
	$acl=new squid_acls();
	$ad=new external_ad_search();
	$DNDUMP=$ad->DNDUMP($_GET["LoadGroupSettings"]);
	$GroupName=$DNDUMP["samaccountname"][0];
	
	$RULES=$acl->GetRulesFromADGroup($GroupName);
	if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>$GroupName:: ".count($RULES)." RULES</span><br>\n";}
	if(is_array($RULES)){
		while (list ($key, $ligne) = each ($RULES) ){$MAIN_SQUIDRULES[$key]=$ligne;}
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:28px'>$GroupName:: \$MAIN_SQUIDRULES:".count($MAIN_SQUIDRULES)." RULES</span><br>\n";}
	}
	
	$sock=new sockets();
	if($sock->EnableUfdbGuard()==1){
		$MAIN_WEBRULES[0]="{default}";
		$RULES=$acl->GetWebfilteringRulesFromADGroup($GroupName);
		if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>$GroupName:: ".count($RULES)." RULES</span><br>\n";}
		if(is_array($RULES)){
			while (list ($key, $ligne) = each ($RULES) ){$MAIN_WEBRULES[$key]=$ligne;}
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>$GroupName:: \$MAIN_WEBRULES:".count($MAIN_WEBRULES)." RULES</span><br>\n";}
		}
	}
	
	if(count($MAIN_SQUIDRULES)>0){
	
		$rules_title="{rules}";
		if(count($MAIN_SQUIDRULES)<2){$rules_title="{rule}";}
	
		while (list ($aclid, $aclname) = each ($MAIN_SQUIDRULES) ){
			$jsGRP="Loadjs('squid.acls-rules.php?Addacl-js=yes&ID=$aclid');";
			$XTRGB[]="<tr>
			<td style='width:48px'><img src='img/folder-script-database-48.png'></td>
			<td style='font-size:22px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\"
			style='text-decoration:underline'>$aclname</a></td>
			</tr>";
				
		}
	
		$proxay_acls="</tr>
		<tr style='height:70px'>
		<td valign=middle style='font-size:30px' class=legend>".count($MAIN_SQUIDRULES)." $rules_title (Proxy):</td>
					</tr>
					<tr>
					<td></td>
					<td><table style='width:100%'>". @implode("", $XTRGB)."</table></td>
		</tr>";
	
	
	}
	
	
	if(count($MAIN_WEBRULES)>0){
	$XTRGB=array();
	while (list ($aclid, $aclname) = each ($MAIN_WEBRULES) ){
	$jsGRP="YahooWin3('1100','dansguardian2.edit.php?ID=$aclid&t=0','$aclid $aclname');";
	$XTRGB[]="<tr>
	<td style='width:48px'><img src='img/folder-script-database-48.png'></td>
	<td style='font-size:22px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\"
	style='text-decoration:underline'>$aclname</a></td>
	</tr>";
	
	}
	$rules_title="{rules}";
	if(count($MAIN_WEBRULES)<2){$rules_title="{rule}";}
	$webfilter_acls="</tr>
	<tr style='height:70px'>
	<td valign=middle style='font-size:30px' class=legend>".count($MAIN_WEBRULES)." $rules_title ({webfiltering}):</td>
	</tr>
	<tr>
	<td></td>
	<td><table style='width:100%'>". @implode("", $XTRGB)."</table></td>
	</tr>";
	
	
	}	
	
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("<center style='width:98%' class=form><table style='width:100%'>$proxay_acls$webfilter_acls</table></center>");
	
}

