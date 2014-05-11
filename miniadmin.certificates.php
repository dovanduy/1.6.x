<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>\n");
ini_set('error_append_string',"\n</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
if(!isset($_GET["t"])){$_GET["t"]=time();}
if(!is_numeric($_GET["t"])){$_GET["t"]=time();}

if(isset($_GET["DynamicDer"])){certificate_edit_DynCert_download();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["section-certificates"])){section_certificates();exit;}
if(isset($_GET["search-certificates"])){search_certificates();exit;}
if(isset($_GET["wizard-certificate-js"])){wizard_certificate_js();exit;}
if(isset($_GET["wizard-certificate-1"])){wizard_certificate_1();exit;}
if(isset($_POST["wizard-certificate-commonname"])){wizard_certificate_save();exit;}

if(isset($_GET["certificate-edit-js"])){certificate_edit_js();exit;}
if(isset($_GET["certificate-edit-tabs"])){certificate_edit_tabs();exit;}
if(isset($_GET["certificate-edit-settings"])){certificate_edit_settings();exit;}

if(isset($_GET["certificate-edit-csr"])){certificate_edit_csr();exit;}
if(isset($_GET["verify-csr"])){certificate_edit_csr_verify();exit;}

if(isset($_GET["verify-privkey"])){certificate_edit_privkey_verify();exit;}



if(isset($_GET["certificate-edit-privkey"])){certificate_edit_privkey();exit;}
if(isset($_GET["verify-crt"])){certificate_edit_crt_verify();exit;}


if(isset($_GET["certificate-upload-js"])){certificate_upload_js();exit;}
if(isset($_GET["certificate-upload-popup"])){certificate_upload_popup();exit;}
if(isset($_GET['uploaded-certificate-CommonName']) ){certificate_upload_perform();exit();}
if(isset($_POST["certificate-uploaded"])){certificate_upload_save();exit;}

if(isset($_POST["stateOrProvinceName"])){certificate_edit_settings_save();exit;}

if(isset($_GET["certificate-edit-bundle"])){certificate_edit_bundle();exit;}
if(isset($_POST["save-bundle"])){certificate_edit_bundle_save();exit;}

if(isset($_GET["certificate-edit-crt"])){certificate_edit_crt();exit;}
if(isset($_GET["certificate-info-crt-js"])){certificate_info_crt_js();exit;}
if(isset($_GET["certificate-info-crt-popup"])){certificate_info_crt_popup();exit;}

if(isset($_GET["certificate-info-privkey-js"])){certificate_info_privkey_js();exit;}
if(isset($_GET["certificate-info-privkey-popup"])){certificate_info_privkey_popup();exit;}
if(isset($_POST["certificate_edit_privkey_save"])){certificate_edit_privkey_save();exit;}



if(isset($_POST["save-crt"])){certificate_edit_crt_save();exit;}

if(isset($_GET["delete-certificate-js"])){certificate_delete_js();exit;}
if(isset($_POST["delete-certificate"])){certificate_delete();exit;}
if(isset($_GET["certificate-edit-DynCert"])){certificate_edit_DynCert();exit;}
if(isset($_POST["certificate_edit_DynCert_save"])){certificate_edit_DynCert_save();exit;}

main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}
function wizard_certificate_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{new_certificate}");
	echo "YahooWin6(800,'$page?wizard-certificate-1=yes&t={$_GET["t"]}','$title')";	
}
function certificate_upload_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{upload}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinBrowse(550,'$page?certificate-upload-popup=yes&RunAfter={$_GET["RunAfter"]}&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}&textid={$_GET["textid"]}','$title')";
}
function certificate_info_crt_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{info}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinBrowse(650,'$page?certificate-info-crt-popup=yes&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}','$title')";
}
function certificate_info_privkey_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{privkey}:{info}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinBrowse(650,'$page?certificate-info-privkey-popup=yes&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}','$title')";	
}

function certificate_edit_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWin6(950,'$page?certificate-edit-tabs=yes&CommonName=$CommonName&t={$_GET["t"]}&textid={$_GET["textid"]}','$title')";	
	
}
function certificate_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$CommonName=$_GET["delete-certificate-js"];
	$id=$_GET["id"];
	$ask=$tpl->javascript_parse_text("{delete} $CommonName {certificate}\\n{delete_certificate_ask}");
	$t=time();
	echo "
var xDelete$t=function (obj) {
	var results=obj.responseText;
	if (results.length>3){alert(results);return;}
	if( document.getElementById('row$id') ){ $('#row$id').remove(); return;}
	if( document.getElementById('$id') ){ $('#$id').remove(); }
	
}
function Delete$t(){
	if(!confirm('$ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-certificate','$CommonName');
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
	Delete$t();";
}

function certificate_delete(){
	$q=new mysql();
	$commonName=$_POST["delete-certificate"];
	$q->QUERY_SQL("DELETE FROM sslcertificates WHERE CommonName='$commonName'","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function wizard_certificate_1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	$title=$tpl->_ENGINE_parse_body("{new_certificate}");
	$html="
	<div class=explain style='font-size:18px'>{wizard_certificate_1}<br><i>{CSR_MULTIPLE_EXPLAIN}</i></div>";
	
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	
	
	$boot=new boostrap_form();
	$boot->set_field("wizard-certificate-commonname", "{CommonName}", $hostname);
	$boot->set_list("wizard-certificate-levelenc", "{level_encryption}", $ENC,1024);
	$boot->set_fieldpassword("wizard-certificate-password", "{password}", "secret");
	$boot->set_formdescription("{wizard_certificate_1}");
	$boot->set_button("{add}");
	$boot->set_RefreshSearchs();
	$boot->set_RefreshFlex("flexRT{$_GET["t"]}");
	$boot->set_CloseYahoo("YahooWin6");
	echo $boot->Compile();
}

function certificate_edit_privkey(){
	$commonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `privkey` FROM sslcertificates WHERE CommonName='$commonName'";
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	$tt=time();
	
	$CommonNameURL=urlencode("$commonName");
	$button_upload=button("$upload_text", "Loadjs('$page?certificate-upload-js=yes&CommonName=$CommonNameURL&type=privkey&t={$_GET["t"]}&textid=text$t&RunAfter=VerifyCertificate$tt',true)");
	$button_extract=button("{info}", "Loadjs('$page?certificate-info-privkey-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt',true)");
	$button_save=button("{apply}", "Save$tt()");
	
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
	$html="
	
	<div class=explain style='font-size:18px'>$ssl_explain</div>
	<center>$button_upload&nbsp;$button_extract</center>
	<div id='verify-$tt'></div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$ligne["privkey"]}</textarea>
	<br>$button_save
	</center>
	</div>

<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
}
		
function Save$tt(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('certificate_edit_privkey_save','$commonName');
		XHR.appendData('certificate_content',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);				
	}

function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-privkey=yes&CommonName=$CommonNameURL',true);
}
VerifyCertificate$tt();
</script>		
	";
	echo $html;
}

function certificate_edit_privkey_save(){
	$commonName=$_POST["certificate_edit_privkey_save"];
	$content=url_decode_special_tool($_POST["certificate_content"]);
	$sql="UPDATE sslcertificates SET `privkey`='".mysql_escape_string2($content)."' WHERE CommonName='$commonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}


function certificate_edit_csr(){
	$commonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `csr` FROM sslcertificates WHERE CommonName='$commonName'";
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	$tt=time();
	if(strlen($ligne["csr"])<50){
		$sock=new sockets();
		$CommonName=urlencode($CommonName);
		echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$commonName"));
	    $ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	$CommonNameURL=urlencode("$commonName");
	$button_upload=button("$upload_text", "Loadjs('$page?certificate-upload-js=yes&CommonName=$CommonNameURL&type=csr&t={$_GET["t"]}&textid=text$t&RunAfter=VerifyCertificate$tt',true)");
	
	$csr_ssl_explain=$tpl->_ENGINE_parse_body("{csr_ssl_explain}");
	$html="
	
	<div class=explain style='font-size:18px'>$csr_ssl_explain</div>
	<div id='verify-$tt'></div>
	<center>$button_upload</center>
		<center style='margin:10px'>
			<textarea id='text$t' style='font-family:Courier New;
			font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
			overflow:auto;font-size:16px !important;width:99%;height:390px'>{$ligne["csr"]}</textarea>
		</center>
	</div>		
<script>
function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-csr=yes&CommonName=$CommonNameURL',true);
}
VerifyCertificate$tt();
</script>			
	";
	echo $html;
}
function certificate_edit_csr_verify(){
	
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	
	
	$sql="SELECT `csr` FROM sslcertificates WHERE CommonName='$CommonName'";
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);return;}
	$tt=time();
	if(strlen($ligne["csr"])<50){
		$sock=new sockets();
		$CommonName=urlencode($CommonName);
		echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	
	
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.csr";
	@file_put_contents($filepath, $ligne["csr"]);
	exec("/usr/bin/openssl req -text -noout -verify -verbose -in $filepath 2>&1",$results);
	$INFO=array();
	$class="text-info";
	$f[]="File: $filepath ".strlen($ligne["csr"])." bytes";
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="text-error";}
		if(preg_match("#unable to load#",$ligne)){$class="text-error";}
		if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
		if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
		$ligne=str_replace($filepath, "Info", $ligne);
		$ligne=htmlentities($ligne);
		$f[]="$ligne";
	
	}
	if($class=="text-error"){
		echo "<p class='$class' style='font-size:14px'>".@implode("<br>", $f)."</p><script>UnlockPage();</script>";	
	}else{
		echo "<p class='$class' style='font-size:14px'>".@implode("<br>", $INFO)."</p><script>UnlockPage();</script>";
	}
	
	
	
	
}



function certificate_edit_DynCert_download(){
	$commonName=$_GET["CommonName"];
	$q=new mysql();
	$commonNameFile=str_replace("*", "_ALL_", $commonName);
	$sql="SELECT `DynamicDer` FROM sslcertificates WHERE CommonName='$commonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	header('Content-type: application/x-x509-ca-cert');
	header('Content-Transfer-Encoding: binary');
	header("Content-Disposition: attachment; filename=\"$commonNameFile.der\"");
	header("Pragma: public");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
	$fsize = strlen($ligne["DynamicDer"]);
	header("Content-Length: ".$fsize);
	ob_clean();
	flush();
	echo $ligne["DynamicDer"];
	
}

function certificate_edit_DynCert(){
	$commonName=$_GET["CommonName"];
	$t=$_GET["t"];
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	$sql="SELECT `DynamicCert`,`DynamicDer` FROM sslcertificates WHERE CommonName='$commonName'";
	$encCommonName=urlencode($commonName);
	if(!is_numeric($t)){$t=time();}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	$img="<img src='img/certificate-download-64-grey.png'><center>".strlen($ligne["DynamicDer"])." bytes</center>";
	if(strlen($ligne["DynamicDer"])>100){
		$img=imgsimple("certificate-download-64.png",null,"s_PopUp('$page?DynamicDer=yes&CommonName=$encCommonName',50,50)");
	}

	$csr_ssl_explain=$tpl->_ENGINE_parse_body("{DynamicCert_ssl_explain}");
	$CommonNameURL=urlencode($commonName);
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$button_upload=button("$upload_text", "Loadjs('$page?certificate-upload-js=yes&CommonName=$CommonNameURL&type=DynamicCert&t={$_GET["t"]}&textid=Content-$t',true)");
	
	
	$html="
	<table style='width:100%'>
	<td style='vertical-align:top' width=1% nowrap>$img</td>
	<td style='vertical-align:top'>
	<div class=explain style='font-size:18px'>$csr_ssl_explain</div>
	<center>$button_upload</center>
	</td>
	</tr>
	</table>
	<center style='margin:10px'>
	<textarea style='font-family:Courier New;
		font-weight:bold;width:100%;
		height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;
		width:99%;height:390px' id='Content-$t'>{$ligne["DynamicCert"]}</textarea>
	
	<center style='margin:20px'>". button($apply, "Save$t()",22)."</center>
	</center>
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
}
		
function Save$t(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('certificate_edit_DynCert_save','$commonName');
		XHR.appendData('certificate_content',encodeURIComponent(document.getElementById('Content-$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$t);				
	}
</script>				
		
	";
	echo $html;



}
function certificate_edit_DynCert_save(){
	$CommonName=$_POST["certificate_edit_DynCert_save"];
	$q=new mysql();
	$certificate_content=url_decode_special_tool($_POST["certificate_content"]);
	$sql="UPDATE sslcertificates SET `DynamicCert`='$certificate_content' WHERE CommonName='$CommonName'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}
}

function certificate_edit_settings(){
	$commonName=$_GET["CommonName"];
	$q=new mysql();
	
	
	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);
			$array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
		}
	}
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	
	
	if(!$q->FIELD_EXISTS("sslcertificates","UsePrivKeyCrt","artica_backup")){$sql="ALTER TABLE `sslcertificates` ADD `UsePrivKeyCrt` smallint(1) DEFAULT 0";$q->QUERY_SQL($sql,'artica_backup');}
	
	
	$sql="SELECT * FROM sslcertificates WHERE CommonName='$commonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
	if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
	if(!is_numeric($ligne["levelenc"])){$ligne["levelenc"]=1024;}
	
	
	$boot=new boostrap_form();
	$boot->set_formtitle($commonName);
	$boot->set_hidden("CommonName", $commonName);
	
	
	$boot->set_checkbox("UsePrivKeyCrt", "{UsePrivKeyCrt}", $ligne["UsePrivKeyCrt"]);
	$boot->set_list("CountryName", "{countryName}", $array_country_codes,$ligne["CountryName"]);
	$boot->set_field("stateOrProvinceName", "{stateOrProvinceName}", $ligne["stateOrProvinceName"]);
	$boot->set_field("localityName", "{localityName}", $ligne["localityName"]);
	$boot->set_field("OrganizationName", "{organizationName}", $ligne["OrganizationName"]);
	$boot->set_field("OrganizationalUnit", "{organizationalUnitName}", $ligne["OrganizationalUnit"]);
	$boot->set_field("emailAddress", "{emailAddress}", $ligne["emailAddress"]);
	$boot->set_field("CertificateMaxDays", "{CertificateMaxDays} ({days})", $ligne["CertificateMaxDays"]);
	$boot->set_list("levelenc", "{level_encryption}", $ENC,$ligne["levelenc"]);
	$boot->set_fieldpassword("password", "{password}", "secret");
	$boot->set_button("{apply}");
	$boot->set_RefreshFlex("flexRT{$_GET["t"]}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function certificate_edit_settings_save(){
	$q=new mysql();
	$q->BuildTables();
	
	
	
	$CommonName=strtolower(trim($_POST["CommonName"]));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($num, $vl) = each ($_POST) ){$_POST[$num]=addslashes($vl);}
	
	
	$sql="SELECT CommonName,csr  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));

	$sql="UPDATE `sslcertificates` SET
		CountryName='{$_POST["CountryName"]}',
		stateOrProvinceName='{$_POST["stateOrProvinceName"]}',
		CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
		OrganizationName='{$_POST["OrganizationName"]}',
		OrganizationalUnit='{$_POST["OrganizationalUnit"]}',
		emailAddress='{$_POST["emailAddress"]}',
		localityName='{$_POST["localityName"]}',
		levelenc='{$_POST["levelenc"]}',
		CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
		password='{$_POST["password"]}',
		UsePrivKeyCrt='{$_POST["UsePrivKeyCrt"]}'
		WHERE CommonName='$CommonName'";
	
						
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "MySQL Error:\n$q->mysql_error\n\n$sql\n";return;}
	$sock=new sockets();
	$CommonName=str_replace('*', "_ALL_", $CommonName);
	$CommonName=urlencode($CommonName);
	if($_POST["UsePrivKeyCrt"]==0){
		echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
	}
		
	
}

function certificate_edit_bundle_save(){
	$sock=new sockets();
	$data=url_decode_special_tool($_POST["save-bundle"]);
	$CommonName=$_POST["CommonName"];
	$sql="UPDATE sslcertificates SET `bundle`='$data' WHERE `CommonName`='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	$CommonName=urlencode($CommonName);
	echo $tpl->javascript_parse_text(base64_decode($sock->getFrameWork("openssl.php?tomysql=$CommonName")));

}

function certificate_edit_bundle(){
$q=new mysql();
$page=CurrentPageName();
$t=$_GET["t"];
$CommonName=$_GET["CommonName"];
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$users=new usersMenus();
$q=new mysql();
$tt=time();
$sql="SELECT bundle  FROM sslcertificates WHERE CommonName='$CommonName'";
$warn_gen_x50=$tpl->javascript_parse_text("{warn_gen_x509}");
$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
$CommonNameURL=urlencode($CommonName);
$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
$button_upload=button("$upload_text", "Loadjs('$page?certificate-upload-js=yes&CommonName=$CommonNameURL&type=bundle&t={$_GET["t"]}&textid=bundl$tt',true)");

$html="
<div class=explain style='font-size:18px' id='$tt-adddis'>{certificate_chain_explain}</div>
<center>$button_upload</center>
<textarea 
	style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'
id='bundl$tt'>{$ligne["bundle"]}</textarea>
<center style='margin:10px'>". button("{apply}","SaveBundle$tt()","18px")."</center>
		<script>
var x_SaveBundle$tt=function (obj) {
	var results=obj.responseText;
	document.getElementById('$tt-adddis').innerHTML='';
	if (results.length>3){alert(results);return;}
}
function SaveBundle$tt(){
	if(confirm('$warn_gen_x50')){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('bundl$tt').value);
		XHR.appendData('save-bundle',pp);
		XHR.appendData('CommonName','$CommonName');
		AnimateDiv('$tt-adddis');
		XHR.sendAndLoad('$page', 'POST',x_SaveBundle$tt);
}
					
}
</script>
";
echo $tpl->_ENGINE_parse_body($html);
}

function certificate_edit_crt(){
	$t=$_GET["t"];
	$CommonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql();
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	$tt=time();
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$sql="SELECT crt  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$warn_gen_x50=$tpl->javascript_parse_text("{warn_gen_x509}");
	$CommonNameURL=urlencode($CommonName);
	$button_upload=button("$upload_text", "Loadjs('$page?certificate-upload-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt&RunAfter=VerifyCertificate$tt',true)");
	
	$button_extract=button("{info}", "Loadjs('$page?certificate-info-crt-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt',true)");
	
	
	
	
	//unable to load certificate
	
	$html="
	<div class=explain style='font-size:18px' id='$tt-adddis'>{public_key_ssl_explain}</div>
	<div id='verify-$tt'></div>
	<center>$button_upload&nbsp;$button_extract</center>
	<textarea 
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px' id='crt$tt'>{$ligne["crt"]}</textarea>
	<center style='margin:10px'>". button($apply,"SaveCRT$tt()","18px")."</center>
<script>
var x_SaveCRT$tt=function (obj) {
	var results=obj.responseText;
	document.getElementById('$tt-adddis').innerHTML='';
	if (results.length>3){alert(results);return;}
}
function SaveCRT$tt(){
	if(confirm('$warn_gen_x50')){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('crt$tt').value);
		XHR.appendData('save-crt',pp);
		XHR.appendData('CommonName','$CommonName');
		AnimateDiv('$tt-adddis');
		XHR.sendAndLoad('$page', 'POST',x_SaveCRT$tt);
	}
}

function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-crt=yes&CommonName=$CommonNameURL',true);
}
VerifyCertificate$tt();
</script>
";
echo $tpl->_ENGINE_parse_body($html);


}
function certificate_edit_crt_save(){
	$data=url_decode_special_tool($_POST["save-crt"]);
	$CommonName=$_POST["CommonName"];
	$sql="UPDATE sslcertificates SET `crt`='$data' WHERE `CommonName`='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text($sock->getFrameWork("openssl.php?tomysql=$CommonName"));

}

function certificate_edit_crt_verify(){
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT crt  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
	@file_put_contents($filepath, $ligne["crt"]);
	exec("/usr/bin/openssl verify -verbose $filepath 2>&1",$results);
	
	$class="text-info";
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="text-error";}
		if(preg_match("#unable to load#",$ligne)){$class="text-error";}
		$ligne=str_replace($filepath, "Info", $ligne);
		$ligne=htmlentities($ligne);
		$f[]="$ligne";
		
	}
	
	echo "<p class='$class' style='font-size:14px'>".@implode("<br>", $f)."</p><script>UnlockPage();</script>";
}

function certificate_edit_privkey_verify(){
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT `crt`  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
	@file_put_contents($filepath, $ligne["crt"]);
	$md5=trim(exec("/usr/bin/openssl x509 -noout -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));	
	
	$sql="SELECT  `privkey`  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.key";
	@file_put_contents($filepath, $ligne["privkey"]);
	$md52=trim(exec("/usr/bin/openssl rsa -noout -modulus -in $filepath | /usr/bin/openssl md5 2>&1"));

	if($md5<>$md52){
		echo "<p class='text-error' style='font-size:14px'>ID: $CommonName<br>Private Key failed &laquo;$md5&raquo; / &laquo;$md52&raquo;</p><script>UnlockPage();</script>";
	}else{
		echo "<p class='text-info' style='font-size:14px'>ID: $CommonName<br>Private Key Success</p><script>UnlockPage();</script>";
	}
	
}

function certificate_info_privkey_popup(){
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT `privkey`  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.key";
	@file_put_contents($filepath, $ligne["privkey"]);
	exec("/usr/bin/openssl rsa -noout -text -in  $filepath 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#unable to load#", $ligne)){
			$addon="\n--------------\n{$ligne["privkey"]}\n--------------\n";
		}
		
		$ligne=trim($ligne);
		$tt[]=$ligne;
	}
	
	echo "<textarea
	style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:12px !important;width:99%;height:390px' id='crt$tt'>".@implode("\n", $tt)."$addon</textarea>";
		
	
	
}




function certificate_info_crt_popup(){
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT crt  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
	@file_put_contents($filepath, $ligne["crt"]);
	exec("/usr/bin/openssl x509 -text -in $filepath 2>&1",$results);
	
	while (list ($num, $ligne) = each ($results) ){
		$ligne=trim($ligne);
		$tt[]=$ligne;
	}
	
	echo "<textarea 
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:12px !important;width:99%;height:390px' id='crt$tt'>".@implode("\n", $tt)."</textarea>";
	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{certificates_center}</H1>
		<p>{ssl_certificates_center_text}</p>
		<div id='statistics-$t'></div>
	</div>	
	<div id='content-$t'></div>
	<script>
		LoadAjax('content-$t','$page?tabs=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function tabs(){
	$title=null;
	$tpl=new templates();
	if(isset($_GET["title"])){
		$title=$tpl->_ENGINE_parse_body("<H3>{certificates_center}</H3><p>{ssl_certificates_center_text}</p>");
	}
	
	$page=CurrentPageName();
	
	$array["certificates"]="$page?section-certificates=yes";
	
	$boot=new boostrap_form();
	echo $title.$boot->build_tab($array);
}
function certificate_edit_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$CommonName=urlencode($_GET["CommonName"]);
	$array["{settings}"]="$page?certificate-edit-settings=yes&CommonName=$CommonName";
	$array["{privkey}"]="$page?certificate-edit-privkey=yes&CommonName=$CommonName";
	$array["{CSR}"]="$page?certificate-edit-csr=yes&CommonName=$CommonName";
	$array["{certificate}"]="$page?certificate-edit-crt=yes&CommonName=$CommonName";
	$array["{apache_chain}"]="$page?certificate-edit-bundle=yes&CommonName=$CommonName";
	$array["{dynamic_chain}"]="$page?certificate-edit-DynCert=yes&CommonName=$CommonName";
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
	
	//csr_ssl_explain
}

function section_certificates(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_certificate}", "Loadjs('$page?wizard-certificate-js=yes&t={$_GET["t"]}')"));
	echo $boot->SearchFormGen("CommonName,OrganizationName,OrganizationalUnit,emailAddress",
			"search-certificates",null,$EXPLAIN);
	
}
function wizard_certificate_save(){
	$password=url_decode_special_tool($_POST["wizard-certificate-password"]);
	$password=mysql_escape_string2($password);
	$CommonName=$_POST["wizard-certificate-commonname"];
	$CommonName=strtolower(trim($CommonName));
	if($CommonName==null){
		echo "CommonName, no such data";
		return;
	}
	$q=new mysql();
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]<>null){echo "$CommonName Already exists...\n";return;}
	
	$sql="INSERT IGNORE INTO sslcertificates (CommonName,keyPassword,password) VALUES ('$CommonName','$password','$password')";
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "MySQL Error:\n".$q->mysq_error."\n$sql";return;}
	
	
	$sock=new sockets();
	$CommonName=urlencode($CommonName);
	echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
	
	
}


function search_certificates(){
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();

	$search=string_to_flexquery("search-certificates");
	$sql="SELECT * FROM sslcertificates WHERE 1 $search ORDER BY CommonName";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){senderror($q->mysql_error);}
	if(mysql_num_rows($results)==0){senderrors("{this_request_contains_no_data}");}
	
	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);
			$array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
		}
	}	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$id=md5(serialize($ligne));
		if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}
		if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
		if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
		if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
		if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
		if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}
		
		$CountryName=$array_country_codes[$ligne["CountryName"]];
		
		$CommonName=urlencode($ligne["CommonName"]);
		$link=$boot->trswitch("Loadjs('$page?certificate-edit-js=yes&CommonName=$CommonName')");
		$delete=imgsimple("delete-64.png",null,"Loadjs('$page?delete-certificate-js=$CommonName&id=$id')");
		
		$tr[]="
		<tr id='$id'>
		<td width=1% nowrap $link><img src='img/certificate-download-64.png'></td>
		<td width=90% nowrap $link><div style='font-size:18px'>{$ligne["CommonName"]}</div>
			<div style='font-size:16px'>$CountryName,{$ligne["stateOrProvinceName"]},{$ligne["localityName"]}</div>
			<div style='font-size:12px'>{$ligne["OrganizationName"]} {$ligne["OrganizationalUnit"]}</div>
		
		</td>
		<td width=5% nowrap $link>{$ligne["emailAddress"]}</td>
		<td width=1% nowrap>$delete</td>
		</tr>";
	}	
	
	echo $tpl->_ENGINE_parse_body("
	
			<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th colspan=2>{certificate}</th>
					<th >{email}</th>
					<th >&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
}

function certificate_upload_save(){
	$fileName=url_decode_special_tool($_POST["certificate-uploaded"]);
	$CommonName=url_decode_special_tool($_POST["CommonName"]);
	$type=url_decode_special_tool($_POST["certificate-uploaded-type"]);
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$filePath="$content_dir$fileName";
	if(!is_file($filePath)){echo "$filePath no such file\n";return;}
	$q=new mysql();
	$CONTENT=@file_get_contents($filePath);
	$certificate_content=mysql_escape_string2($CONTENT);
	$sql="UPDATE sslcertificates SET `$type`='$certificate_content' WHERE CommonName='$CommonName'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	echo $CONTENT;
}

function certificate_upload_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$allowedExtensions=null;
	$UploadAFile=str_replace(" ", "&nbsp;", $UploadAFile);
	$type=$_GET["type"];
	$typeText=$type;
	if($type=="crt"){$typeText="crt,pem";}
	if($type=="privkey"){$typeText="key";}
	$UploadAFile=$tpl->javascript_parse_text("{upload_certificate} - $typeText");
	$CommonName=$_GET["CommonName"];
	if($_GET["RunAfter"]<>null){$RunAfter="{$_GET["RunAfter"]}();";}
	$html="
	<div id='file-uploader-$t' style='width:100%;text-align:center'>
	<noscript>
	<!-- or put a simple form for upload here -->
	</noscript>
	</div>
	<script>
	
	
var xUploadStep2$t=function (obj) {
	var results=obj.responseText;
	document.getElementById('file-uploader-$t').innerHTML='';
	if (results.length>50){
		if( document.getElementById('{$_GET["textid"]}') ){
			document.getElementById('{$_GET["textid"]}').value=results;
			$RunAfter
		}
	
	}
	
	
	ExecuteByClassName('SearchFunction');
	YahooWinBrowseHide();
}
function UploadStep2$t(fileName){
	var XHR = new XHRConnection();
	XHR.appendData('certificate-uploaded',encodeURIComponent(fileName));
	XHR.appendData('certificate-uploaded-type','$type');
	XHR.appendData('CommonName',encodeURIComponent('$CommonName'));
	AnimateDiv('file-uploader-$t');
	XHR.sendAndLoad('$page', 'POST',xUploadStep2$t);
}
	
	
	function createUploader$t(){
		var uploader = new qq.FileUploader({
		element: document.getElementById('file-uploader-$t'),
		action: '$page',$allowedExtensions
		template: '<div class=\"qq-uploader\">' +
			'<div class=\"qq-upload-drop-area\"><span>Drop files here to upload</span></div>' +
				'<div class=\"qq-upload-button\" style=\"width:100%\">&nbsp;&laquo;&nbsp;$UploadAFile&nbsp;&raquo;&nbsp;</div>' +
				'<ul class=\"qq-upload-list\"></ul>' +
			'</div>',
		debug: false,
		params: {
			   'uploaded-certificate-type': '$type',
			   'uploaded-certificate-CommonName': '$CommonName'
				    },		
		onComplete: function(id, fileName){
			 UploadStep2$t(fileName);
			
		}
	});
}

createUploader$t();
</script>
";

//$html="<iframe style='width:100%;height:250px;border:1px' src='$page?form-upload={$_GET["upload-file"]}&select-file={$_GET["select-file"]}'></iframe>";
	echo $html;
}
function certificate_upload_perform(){
	usleep(300);
	writelogs("OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
	$sock=new sockets();
	$sock->getFrameWork("services.php?lighttpd-own=yes");
	
	if (isset($_GET['qqfile'])){
		$fileName = $_GET['qqfile'];
		if(function_exists("apache_request_headers")){
			$headers = apache_request_headers();
			if ((int)$headers['Content-Length'] == 0){writelogs("content length is zero",__FUNCTION__,__FILE__,__LINE__);die ('{error: "content length is zero"}');}
		}else{
			writelogs("apache_request_headers() no such function",__FUNCTION__,__FILE__,__LINE__);
		}
	} elseif (isset($_FILES['qqfile'])){
		$fileName = basename($_FILES['qqfile']['name']);
		writelogs("_FILES['qqfile']['name'] = $fileName",__FUNCTION__,__FILE__,__LINE__);
		if ($_FILES['qqfile']['size'] == 0){writelogs("file size is zero",__FUNCTION__,__FILE__,__LINE__);die ('{error: "file size is zero"}');}
	} else {
		writelogs("file not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "file not passed"}');
	}
	
	writelogs("OK {$_GET['qqfile']}",__FUNCTION__,__FILE__,__LINE__);
	
	if (count($_GET)){
		$datas=json_encode(array_merge($_GET, array('fileName'=>$fileName)));
		writelogs($datas,__FUNCTION__,__FILE__,__LINE__);
	
	} else {
		writelogs("query params not passed",__FUNCTION__,__FILE__,__LINE__);
		die ('{error: "query params not passed"}');
	}
	writelogs("OK {$_GET['qqfile']} upload_max_filesize=".ini_get('upload_max_filesize')." post_max_size:".ini_get('post_max_size'),__FUNCTION__,__FILE__,__LINE__);
	include_once(dirname(__FILE__)."/ressources/class.file.upload.inc");
	$allowedExtensions = array();
	$sizeLimit = qqFileUploader::toBytes(ini_get('upload_max_filesize'));
	$sizeLimit2 = qqFileUploader::toBytes(ini_get('post_max_size'));
	if($sizeLimit2<$sizeLimit){$sizeLimit=$sizeLimit2;}
	
	$content_dir=dirname(__FILE__)."/ressources/conf/upload/";
	$uploader = new qqFileUploader($allowedExtensions, $sizeLimit);
	$result = $uploader->handleUpload($content_dir);
	
	writelogs("OK -> check $content_dir$fileName",__FUNCTION__,__FILE__,__LINE__);
	
	
	
	if(is_file("$content_dir$fileName")){
		writelogs("upload_form_perform() -> $content_dir$fileName ok",__FUNCTION__,__FILE__,__LINE__);
		echo htmlspecialchars(json_encode(array('success'=>true)), ENT_NOQUOTES);
		return;
	}
	echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
	return;
}	