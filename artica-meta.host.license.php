<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){popup();exit;}
$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){$tpl=new templates();echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}


js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{events}");
	$page=CurrentPageName();
	$artica_meta=new mysql_meta();
	if(isset($_GET["uuid"])){
		$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
		$tag=$artica_meta->uuid_to_tag($_GET["uuid"]);
	}
	
	echo "YahooWin4('770','$page?popup=yes&gpid={$_GET["gpid"]}&uuid=".urlencode($_GET["uuid"])."','$hostname - $tag')";
}


function popup(){
	$artica_meta=new mysql_meta();
	$LicenseInfos=$artica_meta->LicenseInfos($_GET["uuid"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$FINAL_TIME=0;
	
	if(isset($LicenseInfos["FINAL_TIME"])){$FINAL_TIME=intval($LicenseInfos["FINAL_TIME"]);}
	$LICT="Community Edition";
	if($LicenseInfos["CORP_LICENSE"]){$LICT="Entreprise Edition";}
	if($LicenseInfos["ExpiresSoon"]>0){if($LicenseInfos["ExpiresSoon"]<31){$LICT="<span style='color:red'>{trial_mode}</span>";}}
	
	
	if($FINAL_TIME>0){
		$ExpiresSoon=intval(time_between_day_Web($FINAL_TIME));
		if($ExpiresSoon<7){
			$ExpiresSoon_text="<strong style='color:red;font-size:16px'>&nbsp;{ExpiresSoon}</strong>";
		}
		$licenseTime="
			<tr>
				<td class=legend style='font-size:24px'>{expiredate}:</td>
				<td style='font-size:24px'>". $tpl->time_to_date($FINAL_TIME)." (".distanceOfTimeInWords(time(),$FINAL_TIME)."$ExpiresSoon_text)</td>
					</tr>";
	
	}
	
	if(is_numeric($LicenseInfos["TIME"])){
		$tt=distanceOfTimeInWords($LicenseInfos["TIME"],time());
		$last_access="
		<tr>
		<td class=legend style='font-size:24px'>{last_update}:</td>
		<td style='font-size:24px'>{since} $tt</td>
		</tr>";
	}
	
	$html="<div style='font-size:30px'>$LICT</div><div style='width:98%' class=form>
<table style='width:100%'>
	</tr>
	$last_access
	<tr>
		<td class=legend style='font-size:24px'>{company}:</td>
		<td><span style='font-size:24px;font-weight:bold'>{$LicenseInfos["COMPANY"]}</span></td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{your_email_address}:</td>
				<td><span style='font-size:24px;font-weight:bold'>{$LicenseInfos["EMAIL"]}</span></td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{nb_employees}:</td>
				<td><span style='font-size:24px;font-weight:bold'>".FormatNumber($LicenseInfos["EMPLOYEES"])."</span></td>
			</tr>
			<tr>
				<td class=legend style='font-size:24px'>{license_number}:</td>
				<td style='font-size:24px'>{$LicenseInfos["license_number"]}</td>
			</tr>
		<tr>
			<td class=legend style='font-size:24px'>{license_status}:</td>
			<td style='font-size:24px;'>{$LicenseInfos["license_status"]}</td>
		</tr>
		$licenseTime
	</table>
</td>
</table></div>";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}