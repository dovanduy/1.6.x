<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	
	$checkon="check-32.png";
	$checkoff="check-32-grey.png";
	
	if($users->CORP_LICENSE){
		$lic="Entreprise Edition";
		$check=$checkon;
	}else{
		$lic="Community Edition";
		$check=$checkoff;
	}
	
	
	$support_payant="<div style='width:98%;margin-top:20px' class=form>
<table style='width:100%'>
<tr>
	<td valign='top' style='padding:5px;width:135px'><img src='img/64-infos.png'></td>
	<td valign='top'><div style='font-size:22px'>{articaboxxcom}</div>
	<div style='margin-top:20px;text-align:right'>". button("ArticaBox.fr", "window.location.href='http://www.articabox.fr/?pk_campaign=FromArticaInstall';",26)."</div>
</tr>
</table>
</div>";
	
	if($tpl->language<>"fr"){$support_payant=null;}
	if($users->WEBSECURIZE){$support_payant=null;}
	if($users->LANWANSAT){$support_payant=null;}
	if($users->BAMSIGHT){$support_payant=null;}
	
	$html="
		
<table style='width:100%'>
<tr>
<td valign='top' style='width:40%'>
	<div style='font-size:32px;margin-bottom:30px'>{features} - $lic</div>	
	<table style='width:100%'>
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{skin_error_pages}</td>
	</tr>	
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{unlimited_statistics_retention}</td>
	</tr>
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{unlimited_caches}</td>
	</tr>		
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{multiple_cpus}</td>
	</tr>		
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{active_directory_connection}</td>
	</tr>
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{failover}</td>
	</tr>
	<tr>
		<td style='width:35px'><img src='img/$checkon'></td>
		<td style='width:100%;font-size:28px'>{open_source_webfiltering_database}</td>
	</tr>	
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{extended_webfiltering_database}</td>
	</tr>
	<tr>
		<td style='width:35px'><img src='img/$check'></td>
		<td style='width:100%;font-size:28px'>{personal_categories}</td>
	</tr>
</table>
$support_payant
</td>
<td valign='top'><div id='lic-status'></div></td>
</tr>
</table>
<script>LoadAjaxRound('lic-status','artica.license.php?lic-status=yes');</script>				
";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}