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
if(isset($_POST["stateOrProvinceName"])){certificate_edit_settings_save();exit;}

if(isset($_GET["certificate-edit-bundle"])){certificate_edit_bundle();exit;}
if(isset($_POST["save-bundle"])){certificate_edit_bundle_save();exit;}

if(isset($_GET["certificate-edit-crt"])){certificate_edit_crt();exit;}
if(isset($_POST["save-crt"])){certificate_edit_crt_save();exit;}

if(isset($_GET["delete-certificate-js"])){certificate_delete_js();exit;}
if(isset($_POST["delete-certificate"])){certificate_delete();exit;}

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
	echo "YahooWin6(800,'$page?wizard-certificate-1=yes','$title')";	
	
}
function certificate_edit_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificate}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWin6(800,'$page?certificate-edit-tabs=yes&CommonName=$CommonName','$title')";	
	
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
	$('#$id').remove();
}
function Delete$t(){
	if(confirm('$ask')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-certificate','$CommonName');
		XHR.sendAndLoad('$page', 'POST',xDelete$t);
	}
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
	echo $boot->Compile();
}
function certificate_edit_csr(){
	$commonName=$_GET["CommonName"];
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `csr` FROM sslcertificates WHERE CommonName='$commonName'";

	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	
	if(strlen($ligne["csr"])<50){
		$sock=new sockets();
		$CommonName=urlencode($CommonName);
		echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$commonName"));
	    $ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	}
	
	$csr_ssl_explain=$tpl->_ENGINE_parse_body("{csr_ssl_explain}");
	$html="
	<div class=explain style='font-size:18px'>$csr_ssl_explain</div>
		<center style='margin:10px'>
			<textarea style='font-family:Courier New;font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:18px !important;width:99%;height:390px'>{$ligne["csr"]}</textarea>
		</center>
	</div>		
			
	";
	echo $html;
	
	
	
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
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}
function certificate_edit_settings_save(){
	$q=new mysql();
	$q->BuildTables();
	
	
	
	$CommonName=strtolower(trim($_POST["CommonName"]));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($num, $vl) = each ($_POST) ){$_POST[$num]=addslashes($vl);}
	
	
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
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
		password='{$_POST["password"]}'
		WHERE CommonName='$CommonName'";
	
						
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "MySQL Error:\n$q->mysql_error\n\n$sql\n";return;}
	$sock=new sockets();
	$CommonName=urlencode($CommonName);
	echo base64_decode($sock->getFrameWork("system.php?BuildCSR=$CommonName"));
		
	
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
$html="
<div class=explain style='font-size:14px' id='$tt-adddis'>{certificate_chain_explain}</div>
<textarea 
	style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:18px !important;width:99%;height:390px'
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
	$tt=time();
	$sql="SELECT crt  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$warn_gen_x50=$tpl->javascript_parse_text("{warn_gen_x509}");
	$html="
	<div class=explain style='font-size:14px' id='$tt-adddis'>{public_key_ssl_explain}</div>
	<textarea 
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:18px !important;width:99%;height:390px' id='crt$tt'>{$ligne["crt"]}</textarea>
	<center style='margin:10px'>". button("{apply}","SaveCRT$tt()","18px")."</center>
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
	$array["{CSR}"]="$page?certificate-edit-csr=yes&CommonName=$CommonName";
	$array["{certificate}"]="$page?certificate-edit-crt=yes&CommonName=$CommonName";
	$array["{apache_chain}"]="$page?certificate-edit-bundle=yes&CommonName=$CommonName";
	
	
	
	$boot=new boostrap_form();
	echo $boot->build_tab($array);
	
	//csr_ssl_explain
}

function section_certificates(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_certificate}", "Loadjs('$page?wizard-certificate-js=yes')"));
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

