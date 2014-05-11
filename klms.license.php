<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.sqlgrey.inc');
	include_once('ressources/class.main_cf.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsPostfixAdministrator){die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["license-info"])){license_info();exit;}
	if(isset($_POST["InstallLicenseFile"])){InstallLicenseFile();exit;}
	if(isset($_POST["RemoveLicenseFile"])){RemoveLicenseFile();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tasks=$tpl->_ENGINE_parse_body("{license_info}");
	$title="Kaspersky Mail Security Suite:$tasks";
	echo "YahooWin3('550','$page?popup=yes','$title')";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$t=time();
	
	
	$html="
	<div style='font-size:18px'>{license_info}:</div>
	<center><div style='padding:15px;font-size:14px;text-align:left;width:90%' class=form id='$t-license-info'></div></center>
	
	<div style='font-size:18px;margin-top:20px'>{new_license}:</div>
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{path}:</td>
		<td>". Field_text("license-$t",null,"font-size:16px;width:300px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?select-file=key&target-form=license-$t')","12px")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{add}", "klmsAddKey()","16px")."</td>
	</tr>
	</table>
	<script>
	
		function RefreshLicenseInfo$t(){
			LoadAjax('$t-license-info','$page?license-info=yes');
		
		}
		
		var x_AddKey$t=function(obj){
	      var tempvalue=obj.responseText;
	      if(tempvalue.length>3){alert(tempvalue);}
	      document.getElementById('$t-license-info').innerHTML=tempvalue;
	      RefreshLicenseInfo$t();
	      }		
		
		function klmsAddKey(){
				var XHR = new XHRConnection();
				XHR.appendData('InstallLicenseFile',document.getElementById('license-$t').value);
				AnimateDiv('$t-license-info');
				XHR.sendAndLoad('$page', 'POST',x_AddKey$t);	
		}
		
		function DeleteKlmsKey(){
				var XHR = new XHRConnection();
				XHR.appendData('RemoveLicenseFile','yes');
				AnimateDiv('$t-license-info');
				XHR.sendAndLoad('$page', 'POST',x_AddKey$t);			
		}
	
		RefreshLicenseInfo$t();
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}
function license_info(){
	$sock=new sockets();
	$tpl=new templates();
	$status=base64_decode($sock->getFrameWork("klms.php?license-status=yes"));
	if(strpos($status, "</root>")>0){
		$f=new SimpleXMLElement($status);
		$array = SimpleXMLElementToArray($f);
		$arrayT["kirValid"]="<img src='img/20-check.png'>";
		$arrayT["flFullFunctionality"]="{full_features}";
		$arrayT["ktCommercial"]="{commercial}";
		$arrayT["ExpiresSoon"]="{ExpiresSoon}";
		
		$arrayK["activeLicenseSerial"]="{serial}";
		$arrayK["keyType"]="{type}";
		$arrayK["expirationDays"]="{expire_in_days}";
		$arrayK["invalidReason"]="{is_valid}";
		
		
		
		$html="
		<table style='width:100%'>
		<tr>
			<td valign='top' width=1%><img src='img/kl-license.png'></td>
			<td valign='top' style='padding-left:10px'>
		<table>";
		while (list ($num, $ligne) = each ($array["@attributes"]) ){
			if(isset($arrayT[$ligne])){$ligne=$arrayT[$ligne];}
			if(isset($arrayK[$num])){$num=$arrayK[$num];}
			$html=$html."
			<tr>
				<td class=legend style='font-size:16px;font-weight:normal'>$num:</td>
				<td style='font-size:16px;font-weight:bold'>$ligne</td>
			</tr>";
			
		}
		if(strlen($array["expirationDate"]["@attributes"]["month"])==1){$array["expirationDate"]["@attributes"]["month"]="0{$array["expirationDate"]["@attributes"]["month"]}";}
		if(strlen($array["expirationDate"]["@attributes"]["day"])==1){$array["expirationDate"]["@attributes"]["day"]="0{$array["expirationDate"]["@attributes"]["day"]}";}
		$expire="{$array["expirationDate"]["@attributes"]["year"]}-{$array["expirationDate"]["@attributes"]["month"]}-{$array["expirationDate"]["@attributes"]["day"]}";
		
		
		
		$html=$html."
			<tr>
			<td class=legend style='font-size:16px;font-weight:normal'>Expiration Date:</td>
			<td style='font-size:16px;font-weight:bold'>$expire</td>
			</tr>";
		$html=$html."
		<tr>
			<td colspan=2 align='right'>". imgtootltip("delete-24.png","{delete}","DeleteKlmsKey()")."</td>
		</tr>
		
		</table>
		</td>
		</tr>
		</table>";
		echo $tpl->_ENGINE_parse_body($html);
		
		
	}else{
		echo nl2br(htmlentities($status));
	}
	
}
function InstallLicenseFile(){
	
	$license_path=base64_encode($_POST["InstallLicenseFile"]);
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("klms.php?license-install=yes&key-path=$license_path"));
	
}
function RemoveLicenseFile(){
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("klms.php?license-remove=yes"));	
}



