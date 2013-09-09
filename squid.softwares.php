<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["install-status"])){install_status();exit;}
	if(isset($_GET["is31"])){is31();exit;}
	if(isset($_GET["current"])){page();exit;}
	if(isset($_GET["stables"])){stables();exit;}
	tabs();
	
	
function is31(){
$sock=new sockets();	
$page=CurrentPageName();
$tpl=new templates();
$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;}	
$squid_version=	ParseAppli($GlobalApplicationsStatus,"APP_SQUID");	
if(preg_match("#^([0-9]+)\.([0-9]+)#", $squid_version,$re)){
	$MAJOR=$re[1];
	$MINOR=$re[2];
}

if($MAJOR>=3){
	if($MINOR>=2){
		echo $tpl->_ENGINE_parse_body("&nbsp;|&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:BackTo31x()\" style='font-size:18px;font-weight:bold;text-decoration:underline'>{back_to_31xbranch}</a>");
	}
}
	
}	


function tabs(){
	
	$page=CurrentPageName();
	$array["current"]='{current_versions}';
	$array["stables"]='{stable_releases}';
	if(!is_file("ressources/old-squid.ini")){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?SetupIndexFile=yes");
	}
	
	$tpl=new templates();
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:16px'><a href=\"$page?$num=yes\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "tab_squid_soft");
	
	
}

	
function page(){
$page=CurrentPageName();
$tpl=new templates();	
$sock=new sockets();
$ini=new Bs_IniHandler();
$ini->loadString(@file_get_contents(dirname(__FILE__). '/ressources/index.ini'));
$users=new usersMenus();
$ArchStruct=$users->ArchStruct;
if($ArchStruct=="32"){$ArchStruct="i386";}
if($ArchStruct=="64"){$ArchStruct="x64";}
$backto31xsquid_explain=$tpl->javascript_parse_text("{backto31xsquid_explain}");

$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
if($GlobalApplicationsStatus==null){$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;}	
$squid_version=	ParseAppli($GlobalApplicationsStatus,"APP_SQUID");

$availableversion=$ini->_params["NEXT"]["squid32-$ArchStruct"];

$availableversion31=$ini->_params["NEXT"]["squid3"];

$actualversion=$sock->getFrameWork("squid.php?full-version=yes");
if($actualversion==null){$actualversion="0.0.0";}

$availableversion_dansguardian=$ini->_params["NEXT"]["dansguardian2-$ArchStruct"];
$actualversion_dansguardian=$sock->getFrameWork("squid.php?full-dans-version=yes");

$availableversion_ufdbguard=$ini->_params["NEXT"]["ufdbGuard"];
$actualversion_ufdbguard=$sock->getFrameWork("squid.php?full-ufdbg-version=yes");
if($actualversion_ufdbguard==null){$actualversion_ufdbguard="0.0.0";}
//$html=$html.BuildRows("APP_UFDBGUARD",$GlobalApplicationsStatus,"ufdbGuard");
//$html=$html.BuildRows("APP_KAV4PROXY",$GlobalApplicationsStatus,"kav4proxy");

$availableversion_kav4proxy=$ini->_params["NEXT"]["kav4proxy"];
$actualversion_kav4proxy=ParseAppli($GlobalApplicationsStatus,"APP_KAV4PROXY");
if($actualversion_kav4proxy==null){$actualversion_kav4proxy="0.0.0";}


$availableversion_samba=$ini->_params["NEXT"]["samba"];
$actualversion_samba=$sock->getFrameWork("samba.php?fullversion=yes");
if($actualversion_samba==null){$actualversion_samba="0.0.0";}

$sock=new sockets();
$realsquidversion=$sock->getFrameWork("squid.php?full-version=yes");

if(preg_match("#^([0-9]+)\.([0-9]+)#", $squid_version,$re)){
	$MAJOR=$re[1];
	$MINOR=$re[2];
}

if($MAJOR>=3){
	if($MINOR>=2){
		$backTo31="&nbsp;|&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:BackTo31x()\" style='font-size:18px;font-weight:bold;text-decoration:underline'>{back_to_31xbranch}</a>";
	}
}

if($MAJOR==3 && $MINOR == 1){
	
	$available31="<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_SQUID} 3.1x</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion31</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$realsquidversion</td>
					</tr>					
					
				</tbody>
				
			</table>";
	
}


$dansguardian="<tr>
	<td valign='top' width=1%><img src='img/bg_dansguardian.jpg'></td>
	<td valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_DANSGUARDIAN}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_dansguardian</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_dansguardian</td>
					</tr>					
					
				</tbody>
				
			</table>
			<div style='font-size:12px' class=explain>{danseguardian_simple_intro}</div>
			<p>&nbsp;</p>
			<span id='dansguardian-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","dansguardian_install_status()")."</div>
	</td>
</tr>
<tr>
	<td colspan=2><hr></td>
</tr>";

$html="

<div style='font-size:18px'>{current}:&nbsp;{APP_SQUID}:&nbsp;<strong>$squid_version</strong>&nbsp;<span style='font-size:11px;'>($realsquidversion)</span>&nbsp;&nbsp;|&nbsp;{architecture}:&nbsp;<strong>$ArchStruct</strong><span id='is31'></span></div>

<table style='width:100%;margin-top:15px'>
<tbody>
<tr>
	<td valign='top' width=1%><img src='img/bg_squid.jpg'></td>
	<td valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_SQUID2}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion</td>
					</tr>					
					
				</tbody>
				
			</table>
			$available31
			
			<div style='font-size:12px' class=explain>{APP_SQUID_TEXT}</div>
			<p>&nbsp;</p>
			<span id='squid-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","squid_install_status()")."</div>
	</td>
</tr>

<tr>
	<td colspan=2><hr></td>
</tr>
<tr>
	<td valign='top' width=1%><img src='img/bg_kav4proxy.jpg'></td>
	<td valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_KAV4PROXY}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_kav4proxy</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_kav4proxy</td>
					</tr>					
					
				</tbody>
				
			</table>
			<div style='font-size:12px' class=explain>{APP_KAV4PROXY_TEXT}</div>
			<p>&nbsp;</p>
			<span id='kav4proxy-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","kav4proxy_install_status()")."</div>
	</td>
</tr>
<tr>
	<td colspan=2><hr></td>
</tr>


<tr>
	<td valign='top' width=1%><img src='img/bg_ufdbguard.png'></td>
	<td valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_UFDBGUARD}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_ufdbguard</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_ufdbguard</td>
					</tr>					
					
				</tbody>
				
			</table>
			<div style='font-size:12px' class=explain>{ufdbguard_simple_intro}</div>
			<p>&nbsp;</p>
			<span id='ufdbguard-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","ufdbguard_install_status()")."</div>
	</td>
</tr>
<tr>
	<td colspan=2><hr></td>
</tr>


<tr>
	<td valign='top' width=1%><img src='img/artica-samba-350.png'></td>
	<td valign='top'>
			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:14px'>{available_software}:</td>
						<td style='font-size:14px;font-weight:bold'>{APP_SAMBA}</div></td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{available}:</td>
						<td style='font-size:14px;font-weight:bold'>$availableversion_samba</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{current}:</td>
						<td style='font-size:14px;font-weight:bold'>$actualversion_samba</td>
					</tr>					
					
				</tbody>
				
			</table>
			<div style='font-size:12px' class=explain>{sambasquid_simple_intro}</div>
			<p>&nbsp;</p>
			<span id='samba-install-status'></span>
			<div style='text-align:right;width:100%'>". imgtootltip("refresh-24.png","{refresh}","samba_install_status()")."</div>
	</td>
</tr>





</tbody>
</table>
<script>
	function squid_install_status(){
		LoadAjaxTiny('squid-install-status','$page?install-status=yes&APPLI=APP_SQUID2');
	}
	
	function dansguardian_install_status(){
		LoadAjaxTiny('dansguardian-install-status','$page?install-status=yes&APPLI=APP_DANSGUARDIAN2');
	}

	function ufdbguard_install_status(){
		LoadAjaxTiny('ufdbguard-install-status','$page?install-status=yes&APPLI=APP_UFDBGUARD');
	}	
	
	function kav4proxy_install_status(){
		LoadAjaxTiny('kav4proxy-install-status','$page?install-status=yes&APPLI=APP_KAV4PROXY');
	}
	
	function samba_install_status(){
		LoadAjaxTiny('samba-install-status','$page?install-status=yes&APPLI=APP_SAMBA');
	}	
	
	function BackTo31x(){
		if(confirm('$backto31xsquid_explain')){
			Loadjs('setup.index.progress.php?product=APP_SQUID31&start-install=yes')
		}
	}
	
squid_install_status();
dansguardian_install_status();
ufdbguard_install_status();
kav4proxy_install_status();
samba_install_status();
LoadAjaxTiny('is31','$page?is31=yes');
</script>
";

echo $tpl->_ENGINE_parse_body($html);
	
}	
function ParseAppli($status,$key){

if(!is_array($GLOBALS["GLOBAL_VERSIONS_CONF"])){BuildVersions();}
return $GLOBALS["GLOBAL_VERSIONS_CONF"][$key];	
}

function BuildVersions(){
	if(is_file("ressources/logs/global.versions.conf")){
		$GlobalApplicationsStatus=@file_get_contents("ressources/logs/global.versions.conf");
	}else{
		if(is_file("ressources/logs/web/global.versions.conf")){
			$GlobalApplicationsStatus=@file_get_contents("ressources/logs/web/global.versions.conf");
		}
	}
	$tb=explode("\n",$GlobalApplicationsStatus);
	while (list ($num, $line) = each ($tb) ){
		if(preg_match('#\[(.+?)\]\s+"(.+?)"#',$line,$re)){
			$GLOBALS["GLOBAL_VERSIONS_CONF"][trim($re[1])]=trim($re[2]);
		}
		
	}
}

function install_status(){
	$appname=$_GET["APPLI"];
	$users=new usersMenus();
	$page=CurrentPageName();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$tpl=new templates();
	$dbg_exists=false;
	if(file_exists(dirname(__FILE__). "/ressources/install/$appname.ini")){
	    $data=file_get_contents(dirname(__FILE__). "/ressources/install/$appname.ini");
		$ini->loadString($data);
		$status=$ini->_params["INSTALL"]["STATUS"];
		$text_info=$ini->_params["INSTALL"]["INFO"];
		if(strlen($text_info)>0){$text_info="<span style='color:black;font-size:10px'>$text_info...</span>";}
		
	}else{
		if($appname=="APP_SQUID2"){
			if(file_exists(dirname(__FILE__). "/ressources/install/APP_SQUID.ini")){
	   		 	$data=file_get_contents(dirname(__FILE__). "/ressources/install/$appname.ini");
				$ini->loadString($data);
				$status=$ini->_params["INSTALL"]["STATUS"];
				$text_info=$ini->_params["INSTALL"]["INFO"];
				if(strlen($text_info)>0){$text_info="<span style='color:black;font-size:10px'>$text_info...</span>";}
			}
		 }
	}
	
	
	if($status==null){
		if($appname=="APP_SQUID2"){
			if(file_exists(dirname(__FILE__). "/ressources/install/APP_SQUID.ini")){
	   		 $data=file_get_contents(dirname(__FILE__). "/ressources/install/$appname.ini");
			$ini->loadString($data);
			$status=$ini->_params["INSTALL"]["STATUS"];
			$text_info=$ini->_params["INSTALL"]["INFO"];
		
			if(strlen($text_info)>0){$text_info="<span style='color:black;font-size:10px'>$text_info...</span>";}
			}
		 }
	}

	if($appname=="APP_SQUID2"){
		$GlobalApplicationsStatus=$sock->APC_GET("GlobalApplicationsStatus",2);
		if($GlobalApplicationsStatus==null){$GlobalApplicationsStatus=base64_decode($sock->getFrameWork('cmd.php?Global-Applications-Status=yes'));$sock->APC_SAVE("GlobalApplicationsStatus",$GlobalApplicationsStatus);$GLOBALS["GlobalApplicationsStatus"]=$GlobalApplicationsStatus;}	
		$squid_version=	ParseAppli($GlobalApplicationsStatus,"APP_SQUID");	
		if(preg_match("#^([0-9]+)\.([0-9]+)#", $squid_version,$re)){$MAJOR=$re[1];$MINOR=$re[2];}
		if($MAJOR==3 && $MINOR==1){
			if($users->LinuxDistriCode<>"CENTOS"){
				$button31="<div style='margin-top:8px'>".button("{install_upgrade} 3.1x", "Loadjs('setup.index.progress.php?product=APP_SQUID&start-install=yes')",14)."</div>";
			}
		}
	}		
		
	
	
		
	if($status==null){$status=0;}
	if($status==0){
		
		if($appname<>"APP_SQUID2"){
			echo $tpl->_ENGINE_parse_body("<center style='margin:10px'>".button("{install_upgrade}", "Loadjs('setup.index.progress.php?product=$appname&start-install=yes')",14)."$button31</center>");
			return;
		}else{
			if($users->LinuxDistriCode<>"CENTOS"){echo $tpl->_ENGINE_parse_body("<center style='margin:10px'>".button("{install_upgrade}", "Loadjs('setup.index.progress.php?product=$appname&start-install=yes')",14)."$button31</center>");}
			return;
		}
	}
	if($status>100){$color="#D32D2D";$status=100;$text='{failed}';}else{$color="#5DD13D";$text=$status.'%';}
	if($status==0){$color="transparent";}
	
	$pourc=pourcentage($status);
	$html="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td>$pourc</td>
		<td style='font-size:12px;font-weight:bold;background-color:$color'>{$text}&nbsp;$text_info</td>
	</tr>
	</tbody>
	</table>
	<script>
	LoadAjaxTiny('is31','$page?is31=yes');
	</script>
	";
	echo  $tpl->_ENGINE_parse_body($html);

}


function stables(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$error=false;
	$ArchStruct=$users->ArchStruct;
	if($ArchStruct=="32"){$ArchStruct="i386";}
	if($ArchStruct=="64"){$ArchStruct="amd64";}

	if($users->LinuxDistriCode<>"DEBIAN"){
		if($users->LinuxDistriCode<>"UBUNTU"){
			FATAL_ERROR_SHOW_128("{ERROR_OPERATING_SYSTEM_NOT_SUPPORTED}");
			$error=true;
		}
	}
	
	if(!is_file("ressources/old-squid.ini")){
		
		$sock->getFrameWork("cmd.php?SetupIndexFile=yes");
	}
	
	$ini=new Bs_IniHandler("ressources/old-squid.ini");
	$current=base64_decode($sock->getFrameWork("squid.php?current-version=yes"));
	$html[]="
	<div style='font-size:18px;margin-bottom:20px;text-align:right'>Squid-Cache v.$current</div>		
	<div style='font-size:16px' class=explain>{squid_old_stable_explain}</div>
	<div style='width:95%;text-align:center' class=form >
	<table style='width:100%'>		
	";
	
	
	while (list ($versions, $array) = each ($ini->_params) ){
	$filename=urlencode($array[$ArchStruct]);
	
		$html[]="<tr style='height:50px'>
			<td style='font-size:32px' width=33%>$versions</td>
			<td style='font-size:18px' width=33%>{released_on} {$array["date"]}</td>";
		
		if(!$error){$html[]="
			<td width=33%>". button("{install_this_version}","Loadjs('squid.downgrade.php?file=$filename&ask=yes')",18)."</td>";
		}
		$html[]="</tr>";
		
	}
	$html[]="</table></div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
