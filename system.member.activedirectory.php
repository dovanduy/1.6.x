<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.contacts.inc');
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

if(isset($_GET["popup"])){popup();exit;}

js();


function js(){
	$page=CurrentPageName();
	$ad=new external_ad_search();
	$tpl=new templates();
	$DNDUMP=$ad->DNDUMP($_GET["DN"]);
	$DNENC=urlencode($_GET["DN"]);
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{member}");
	echo "YahooUser('995','$page?popup=yes&DN=$DNENC','$title')";
	
}

function popup() {
	$users=new usersMenus();
	$ad=new external_ad_search();
	$DNDUMP=$ad->DNDUMP($_GET["DN"]);
	$tpl=new templates();
	
	if(isset($DNDUMP["description"][0])){
		$description=$DNDUMP["description"][0];
	}
	
	$title=$DNDUMP["samaccountname"][0];
	
	if(isset($DNDUMP["givenname"][0])){
		$title="{$DNDUMP["givenname"][0]} {$DNDUMP["sn"][0]}";
	}
	
	$MAIN_SQUIDRULES=array();
	$MAIN_WEBRULES=array();
	
	for($i=0;$i<$DNDUMP["memberof"]["count"];$i++){
		
		$DN=$DNDUMP["memberof"][$i];
		$XGRP=$ad->DNinfos($DN);
		$GroupName=$XGRP[0]["samaccountname"][0];
		
		if($users->SQUID_INSTALLED){
			$acl=new squid_acls();
			$RULES=$acl->GetRulesFromADGroup($GroupName);
			if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>$GroupName:: ".count($RULES)." RULES</span><br>\n";}
			if(is_array($RULES)){
				while (list ($key, $ligne) = each ($RULES) ){$MAIN_SQUIDRULES[$key]=$ligne;}
				if($GLOBALS["VERBOSE"]){echo "<span style='color:red;font-size:22px'>$GroupName:: \$MAIN_SQUIDRULES:".count($MAIN_SQUIDRULES)." RULES</span><br>\n";}
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
			
			
			
		}
		

		$jsGRP="Loadjs('domains.edit.group.php?js=yes&group-id=".urlencode($DN)."',true)";
		$XTRG[]="<tr>
					<td style='width:16px'><img src='img/wingroup.png'></td>
					<td style='font-size:16px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\" style='text-decoration:underline'>$GroupName</a></td>
				</tr>";
	}
	
	
	
	if(count($MAIN_SQUIDRULES)>0){
		
		$rules_title="{rules}";
		if(count($MAIN_SQUIDRULES)<2){$rules_title="{rule}";}
		
		while (list ($aclid, $aclname) = each ($MAIN_SQUIDRULES) ){
			$jsGRP="Loadjs('squid.acls-rules.php?Addacl-js=yes&ID=$aclid');";
			$XTRGB[]="<tr>
			<td style='width:16px'><img src='img/scripts-16.png'></td>
			<td style='font-size:16px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\" 
				style='text-decoration:underline'>$aclname</a></td>
			</tr>";
			
		}
		
		$proxay_acls="</tr>
		<tr style='height:70px'>
		<td valign=middle style='font-size:26px' class=legend>".count($MAIN_SQUIDRULES)." $rules_title (Proxy):</td>
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
			<td style='width:16px'><img src='img/scripts-16.png'></td>
			<td style='font-size:16px'><a href=\"javascript:blur();\" OnClick=\"javascript:$jsGRP\"
			style='text-decoration:underline'>$aclname</a></td>
			</tr>";
				
		}
		$rules_title="{rules}";
		if(count($MAIN_WEBRULES)<2){$rules_title="{rule}";}
		$webfilter_acls="</tr>
		<tr style='height:70px'>
		<td valign=middle style='font-size:26px' class=legend>".count($MAIN_WEBRULES)." $rules_title ({webfiltering}):</td>
		</tr>
		<tr>
		<td></td>
		<td><table style='width:100%'>". @implode("", $XTRGB)."</table></td>
		</tr>";		
		
		
	}
	
	
	
	
	
	$picture_link="img/impersonate-photo.png";
	$html= "
			
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td style='width:190px;vertical-align:top'>
		<center style='margin-top:15px'>
		<img style='border-radius: 50% 50% 50% 50%;
    		box-shadow: 0 0 5px silver;height: 180px;margin: 0 32px;width: 180px;' src='$picture_link'></a>
    <center style='font-size: 24px;line-height: 1.2;word-wrap: break-word;margin-top:30px;margin-bottom:30px'>$title</center>

    
    </center>	
	</td>
		<td style='width:80%;vertical-aglin:top'>
			<table style='width:100%'>
					</tr>			
				<tr style='height:70px'>
				<td valign=middle style='font-size:26px' class=legend><div>{Contact_Information}:</div><i style='font-size:16px'>$description</i></td>
			</tr>

			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{member}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["givenname"][0]} {$DNDUMP["sn"][0]}</strong></td>
			</tr>			
			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{name}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["name"][0]}</strong></td>
			</tr>
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{account}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["samaccountname"][0]}</strong></td>
			</tr>
		
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{email}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["mail"][0]}</strong></td>
			</tr>
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{telephoneNumber}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["telephonenumber"][0]}</strong></td>
			</tr>			
			<tr>
				<td valign=middle style='font-size:18px' class=legend>{mobile}:</td>
				<td valign=middle style='font-size:18px'><strong>{$DNDUMP["mobile"][0]}</strong></td>
			</tr>			
				<tr style='height:70px'>
				<td valign=middle style='font-size:26px' class=legend>{$DNDUMP["memberof"]["count"]} {groups}:</td>
			</tr>			
			<tr>
				<td></td>
				<td><table style='width:100%'>". @implode("", $XTRG)."</table></td>
			</tr>
			$proxay_acls
			$webfilter_acls
			</table>
		</td>
	</tr>
	</table>
	<p>&nbsp;</p>
	</div>
			
	";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);

	
}