<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.kav4proxy.inc');

$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}

	if(isset($_GET["Kav4proxy-license-popup"])){echo kav4proxy_license_popup();exit;}
	if(isset($_GET["Kav4proxy-license-delete"])){echo kav4proxy_license_delete();exit;}
	if(isset($_GET["kav4proxy-license-iframe"])){echo kav4proxy_license_iframe();exit;}	
	if(isset($_GET["Kav4ProxyLicenseInfos"])){license_info();exit;}
	
	if( isset($_POST['InstallLicenseFile']) ){kav4proxy_license_upload();exit();}
	
kav4proxy_license_js();

function kav4proxy_license_delete(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('kav4proxy.php?license-infosDelete&type='.$_GET["license-type"]));	
}

function kav4proxy_license_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_KAV4PROXY}::{license_info}');
	if($_GET["license-type"]=="milter"){$title=$tpl->_ENGINE_parse_body('{APP_KAVMILTER}::{license_info}','squid.index.php');}
	if($_GET["license-type"]=="kas"){$title=$tpl->_ENGINE_parse_body('{APP_KAS3}::{license_info}','squid.index.php');}
	
	$html="
	function Kav4ProxyLicenseStart(){
		YahooWin5('700','$page?Kav4proxy-license-popup=yes&license-type={$_GET["license-type"]}','$title');
	}
	
	
	var x_Kav4ProxyDeleteKey= function (obj) {
		Kav4ProxyLicenseStart();
	}
		
	function Kav4ProxyDeleteKey(){
		var XHR = new XHRConnection();
		document.getElementById('kav4licenseDiv').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
		XHR.appendData('Kav4proxy-license-delete','yes');
		XHR.appendData('license-type','{$_GET["license-type"]}');
		XHR.sendAndLoad('$page', 'GET',x_Kav4ProxyDeleteKey);
	}
	
	Kav4ProxyLicenseStart();
";
	echo $html;	
	}
	
function kav4proxy_license_popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('kav4proxy.php?license-infos&type='.$_GET["license-type"]));
	
	
	
	
	$html="
	<table style='width:99%'>
	<tr>
	<td width=1%>".imgtootltip("delete-32.png","{delete}","Kav4ProxyDeleteKey()")."</td>
	<td align='center' width=100%><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUp('http://www.kaspersky.com/downloads/free-trials/business-security/internet-gateway/',1024,900,true);\"
	style=\"font-size:22px;font-weight:bold;text-decoration:underline\">
	{get_free_license_trial}</a>
	<td width=1%>".imgtootltip("refresh-32.png","{refresh}","LicenseInfos()")."</td>
	
	</tr>
	</table>
	
	
	<div style='width:100%;height:440px' id='kav4licenseDiv'>
	<div id='kav4LicenseUploaded'></div>	
	<div id='Kav4ProxyLicenseInfos'></div>
		
	</div>
	
	
	
<center>
		".button("{upload_new_license}","Loadjs('Kav4Proxy.license.upload.php')",32)."</td>
</center>

<script>
	function LicenseInfos(){
		LoadAjax('Kav4ProxyLicenseInfos','$page?Kav4ProxyLicenseInfos=yes&license-type={$_GET["license-type"]}');
	}
	
		
		var x_AddKey=function(obj){
	      var tempvalue=obj.responseText;
	      document.getElementById('kav4LicenseUploaded').innerHTML=tempvalue;
	      LicenseInfos();
	      }
	      			
		
		function AddKey(){
				var XHR = new XHRConnection();
				XHR.appendData('InstallLicenseFile',document.getElementById('license-path').value);
				XHR.appendData('license-type','{$_GET["license-type"]}');
				AnimateDiv('kav4LicenseUploaded');
				XHR.sendAndLoad('$page', 'POST',x_AddKey);		
			}
	
	LicenseInfos();
</script>
";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function license_info(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('kav4proxy.php?license-infos&type='.$_GET["license-type"]));	
	$tp=explode("\n",$datas);
	$html="<center style='width:98%'>
	<div class=explain>		
	<table style='width:100%' >
	<tbody>";
	while (list ($num, $val) = each ($tp)){
			if(trim($val)==null){continue;}
			$val=htmlspecialchars($val);
			if(strlen($val)>89){$val=texttooltip(substr($val,0,86).'...',$val,null,null,1);}
			if(preg_match("#Error checking#", $val)){$val="<strong style='color:#d32d2d'>$val</strong>";}
			if(preg_match("#No installed keys found#", $val)){$val="<strong style='color:#d32d2d'>$val</strong>";}
			if(preg_match("#Expiration date#", $val)){$val="<strong style='color:blue'>$val</strong>";}
			if(preg_match("#Count:#", $val)){$val="<strong style='color:blue'>$val</strong>";}
			if(preg_match("#Lifespan:#", $val)){$val="<strong style='color:blue'>$val</strong>";}
			if(preg_match("#Objs:#", $val)){$val="<strong style='color:blue'>$val</strong>";}
			$html=$html . "
			<tr>
				<td style='font-size:16px'>
					<code style='font-size:16px'>$val</code>
				</td>
			</tr>";
				
	}
	
	$html=$html . "</tbody>
	</table></div></center>";
	
	echo $html;
}
function kav4proxy_license_iframe($error=null){
	$page=CurrentPageName();
	$html="
	<span style='color:#d32d2d;font-weight:bold;font-size:12px;padding-left:5px'>$error</span>
	<input type=\"hidden\" name=\"upload\" value=\"1\">
	<form method=\"post\" enctype=\"multipart/form-data\" action=\"$page\">
	<table style='width:99%' class=form align='center'>
	<tr>
		<td class=legend valign='middle'>{upload_new_license}:</td>
		<td>
			<input type=\"file\" name=\"fichier\" size=\"30\">
			<input type=\"hidden\" name=\"license-type\" value='{$_GET["license-type"]}'>
		</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>
			<input type='submit' name='upload' value='{upload file}&nbsp;&raquo;' style='width:220px'>
		</td>
	</tr>
</table>
</form>	
	";
$tpl=new templates();
$html=$tpl->_ENGINE_parse_body($html);
echo iframe($html,0);
	
}

function kav4proxy_license_upload(){
	
    $socket=new sockets();
    writelogs("Kav4ProxyUploadLicense:{$_POST["InstallLicenseFile"]}",__FUNCTION__,__FILE__);
 	$res=base64_decode($socket->getFrameWork("cmd.php?Kav4ProxyUploadLicense={$_POST["InstallLicenseFile"]}&type={$_POST["license-type"]}"));
	$tp=explode("\n",$res);
	$tp[]="ltp:{$_POST["license-type"]}";
	
	while (list ($num, $val) = each ($tp)){
		if(trim($val)==null){continue;}
		$val=htmlspecialchars($val);
		$color="black";
		if(preg_match("#Error#i", $val)){$color="red;font-weight:bold";}
		if(preg_match("#successfully#i", $val)){$color="#55BE5D;font-weight:bold";}
		$html=$html . "<div style='text-align:left'><code style='color:$color'>$val</code></div>";
		
		
		
		
		}
  	
	echo "<center><div style='width:98%' class=form>$html</div></center>";	
		
}
