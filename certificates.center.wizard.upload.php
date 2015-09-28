<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.certificate-center.inc');
	if(!isset($_GET["t"])){$_GET["t"]=time();}
	if(!is_numeric($_GET["t"])){$_GET["t"]=time();}
	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}
	
	if(isset($_POST["CA_CERTIFICATE"])){WIZARD_CA_CERTIFICATE_SAVE();exit;}
	if(isset($_POST["CERTIFICATE_REQUEST"])){WIZARD_CERTIFICATE_REQUEST_SAVE();exit;}
	if(isset($_POST["CERTIFICATE"])){WIZARD_CERTIFICATE_SAVE();exit;}
	if(isset($_POST["CERTIFICATE_FINAL"])){CERTIFICATE_FINAL();exit;}
	
	if(isset($_GET["wizard-0"])){wizard_certificate_0();exit;}
	if(isset($_GET["wizard-1"])){wizard_certificate_1();exit;}
	if(isset($_GET["wizard-2"])){wizard_certificate_2();exit;}
	if(isset($_GET["wizard-3"])){wizard_certificate_3();exit;}
	if(isset($_GET["wizard-4"])){wizard_certificate_4();exit;}
	
	
wizard_certificate_js();
	
	function wizard_certificate_js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->javascript_parse_text("{upload_your_certificate}");
		echo "YahooWin6Hide();YahooWin6(800,'$page?wizard-0=yes&t={$_GET["t"]}','$title')";
	}	
	
	
function wizard_certificate_0(){
		$page=CurrentPageName();
		$tpl=new templates();
		$t=time();
	
	
		$html="
		<div id='$t'></div>
		<script>LoadAjax('$t','$page?wizard-1=yes&div=$t&t={$_GET["t"]}');</script>";
		echo $html;

}



function wizard_certificate_1(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");
	
	if($_SESSION["CA_CERTIFICATE"]==null){$_SESSION["CA_CERTIFICATE"]="-----BEGIN CERTIFICATE-----\n PUT CONTENT HERE\n-----END CERTIFICATE-----\n";}
	
	
	$html="
	<center style='font-size:32px'>{CA_CERTIFICATE}</center>
	<div class=explain style='font-size:18px'>{CA_CERTIFICATE_EXPLAIN}</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["CA_CERTIFICATE"]}</textarea>
	<br>$button_save
	</center>
	</div>
	
	<script>
	
	var xSave$tt= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
		LoadAjax('{$_GET["div"]}','$page?wizard-2&t={$_GET["t"]}&div={$_GET["div"]}');
			
	}	
	
	function Save$tt(CommonName,md5){
		var XHR = new XHRConnection();
		XHR.appendData('CA_CERTIFICATE',encodeURIComponent(document.getElementById('text$t').value));
		XHR.sendAndLoad('$page', 'POST',xSave$tt);
	}
	
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function wizard_certificate_2(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	$ssl_explain=$tpl->_ENGINE_parse_body("{privkey_ssl_explain}");

	if($_SESSION["CERTIFICATE"]==null){$_SESSION["CERTIFICATE"]="-----BEGIN CERTIFICATE-----\n PUT CONTENT HERE\n-----END CERTIFICATE-----\n";}


	$html="
	<center style='font-size:32px'>{certificate}</center>
	<div class=explain style='font-size:18px'>{CERTIFICATE_EXPLAIN}</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["CERTIFICATE"]}</textarea>
	<br>$button_save
	</center>
	</div>

	<script>

var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	LoadAjax('{$_GET["div"]}','$page?wizard-3&t={$_GET["t"]}&div={$_GET["div"]}');
		
}

function Save$tt(CommonName,md5){
var XHR = new XHRConnection();
XHR.appendData('CERTIFICATE',encodeURIComponent(document.getElementById('text$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);

}
function wizard_certificate_3(){
	$tt=time();
	$t=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$button_save=$tpl->_ENGINE_parse_body(button("{next}", "Save$tt()",32));
	

	if($_SESSION["CERTIFICATE_REQUEST"]==null){$_SESSION["CERTIFICATE_REQUEST"]="-----BEGIN CERTIFICATE REQUEST-----\n PUT CONTENT HERE\n-----END CERTIFICATE REQUEST-----\n";}


	$html="
	<center style='font-size:32px'>{CSR}</center>
	<div class=explain style='font-size:18px'>{CERTIFICATE_REQUEST_EXPLAIN}</div>
	<center style='margin:10px'>
	<textarea id='text$t' style='font-family:Courier New;
	font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
	overflow:auto;font-size:16px !important;width:99%;height:390px'>{$_SESSION["CERTIFICATE_REQUEST"]}</textarea>
	<br>$button_save
	</center>
	</div>

	<script>

	var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	LoadAjax('{$_GET["div"]}','$page?wizard-4&t={$_GET["t"]}&div={$_GET["div"]}');

}

function Save$tt(CommonName,md5){
var XHR = new XHRConnection();
XHR.appendData('CERTIFICATE_REQUEST',encodeURIComponent(document.getElementById('text$t').value));
XHR.sendAndLoad('$page', 'POST',xSave$tt);
}

</script>
";
	echo $tpl->_ENGINE_parse_body($html);

}
function WIZARD_CA_CERTIFICATE_SAVE(){
	$_SESSION["CA_CERTIFICATE"]=url_decode_special_tool($_POST["CA_CERTIFICATE"]);
	
}

function WIZARD_CERTIFICATE_SAVE(){
	$_SESSION["CERTIFICATE"]=url_decode_special_tool($_POST["CERTIFICATE"]);
	
	
	$CAfile=dirname(__FILE__)."/ressources/conf/upload/CA_CERTIFICATE.ca";
	$certfile=dirname(__FILE__)."/ressources/conf/upload/CERTIFICATE.cert";
	
	@file_put_contents($CAfile, $_SESSION["CA_CERTIFICATE"]);
	@file_put_contents($certfile, $_SESSION["CERTIFICATE"]);
	
	//exec("/usr/bin/openssl req -in $CERTIFICATE_PATH -noout -text 2>&1",$results);
	// openssl x509 -subject -issuer -enddate -noout -in certificate.pem
	$results=array();
	$ERRORS=array();
	exec("/usr/bin/openssl verify -verbose -CAfile $CAfile $certfile 2>&1",$results);
	while (list ($num, $ligne) = each ($results) ){
		if(preg_match("#error#i", $ligne)){
			$ERRORS[]=$ligne;
		}
		
	}
	@unlink($CAfile);
	@unlink($certfile);
	if(count($ERRORS)>0){echo @implode("\n", $ERRORS);}
	
}

function WIZARD_CERTIFICATE_REQUEST_SAVE(){
	$tpl=new templates();
	$_SESSION["CERTIFICATE_REQUEST"]=url_decode_special_tool($_POST["CERTIFICATE_REQUEST"]);
	
	
	$CERTIFICATE_PATH=dirname(__FILE__)."/ressources/conf/upload/CERTIFICATE_REQUEST.csr";
	@file_put_contents($CERTIFICATE_PATH, $_SESSION["CERTIFICATE_REQUEST"]);
	exec("/usr/bin/openssl req -in $CERTIFICATE_PATH -noout -text 2>&1",$results);
	@unlink($CERTIFICATE_PATH);
	
	$MAIN=certificate_center_parse_array($results);
	
	$CountryName=$MAIN["CountryName"];
	$OrganizationalUnit=$MAIN["OrganizationalUnit"];
	$OrganizationName=$MAIN["OrganizationName"];
	$CommonName=$MAIN["CommonName"];
	$emailAddress=$MAIN["emailAddress"];
	$localityName=$MAIN["localityName"];
	$stateOrProvinceName=$MAIN["stateOrProvinceName"];
	$levelenc=$MAIN["levelenc"];
	$_SESSION["CERTIFICATE_ARRAY"]=$MAIN;
	
	if($CommonName==null){
		echo $tpl->javascript_parse_text("{unable_to_find_commonname_incsr}");
		return;
	}
	
	
	
}
function wizard_certificate_4(){
		$MAIN=$_SESSION["CERTIFICATE_ARRAY"];

		$CountryName=$MAIN["CountryName"];
		$OrganizationalUnit=$MAIN["OrganizationalUnit"];
		$OrganizationName=$MAIN["OrganizationName"];
		$CommonName=$MAIN["CommonName"];
		$emailAddress=$MAIN["emailAddress"];
		$localityName=$MAIN["localityName"];
		$stateOrProvinceName=$MAIN["stateOrProvinceName"];
		$levelenc=$MAIN["levelenc"];
	
	
		
		$tt=time();
		$t=time();
		$tpl=new templates();
		$page=CurrentPageName();
		$button_save=$tpl->_ENGINE_parse_body(button("{save_certificate}", "Save$tt()",26));
		
		$html[]="
		<div style='font-size:32px;margin-bottom:20px'>{certificate_details}</div>
		<table style='width:100%'>
		<tr>
		<td class=legend style='font-size:22px'>{CommonName}:</td>
		<td style='font-size:22px;font-weight:bold'>$CommonName</td>
		</tr>
		
		<tr>
		<td class=legend style='font-size:22px'>{CountryName}:</td>
		<td style='font-size:22px;font-weight:bold'>$CountryName</td>
		</tr>		
		
		<tr>
		<td class=legend style='font-size:22px'>{stateOrProvinceName}:</td>
		<td style='font-size:22px;font-weight:bold'>$stateOrProvinceName</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{localityName}:</td>
		<td style='font-size:22px;font-weight:bold'>$localityName</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{organizationName}:</td>
		<td style='font-size:22px;font-weight:bold'>$OrganizationName</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{organizationalUnitName}:</td>
		<td style='font-size:22px;font-weight:bold'>$OrganizationalUnit</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{emailAddress}:</td>
		<td style='font-size:22px;font-weight:bold'>$emailAddress</td>
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{level_encryption}:</td>
		<td style='font-size:22px;font-weight:bold'>$levelenc</td>
		</tr>
	
		</tr>
		<tr>
		<td class=legend style='font-size:22px'>{CSR}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["CERTIFICATE_REQUEST"])." Bytes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{certificate}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["CERTIFICATE"])." Bytes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{CA_CERTIFICATE}:</td>
		<td style='font-size:22px;font-weight:bold'>". strlen($_SESSION["CA_CERTIFICATE"])." Bytes</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>$button_save</td>
	</tr>
	</table>
</div>
<script>
var xSave$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#TABLE_CERTIFICATE_CENTER_MAIN').flexReload();
	YahooWin6Hide();
	Loadjs('certificates.center.php?certificate-edit-js=yes&CommonName=$CommonName&t={$_GET["t"]}');
}
	
function Save$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('CERTIFICATE_FINAL','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$tt);
}
</script>
	
					";
		echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	}

function CERTIFICATE_FINAL(){
	$MAIN=$_SESSION["CERTIFICATE_ARRAY"];
	while (list ($num, $ligne) = each ($MAIN) ){
		$ARRAY[$num]=mysql_escape_string2($ligne);
	
	}
	
	
	$CommonName=$ARRAY["CommonName"];
	$stateOrProvinceName=$ARRAY["stateOrProvinceName"];
	$localityName=$ARRAY["localityName"];
	$OrganizationName=$ARRAY["OrganizationName"];
	$OrganizationalUnit=$ARRAY["OrganizationalUnit"];
	$emailAddress=$ARRAY["emailAddress"];
	$levelenc=$ARRAY["levelenc"];
	$CountryName=$ARRAY["CountryName"];
	
	$CSR=mysql_escape_string2($_SESSION["CERTIFICATE_REQUEST"]);
	$WIZARD_CERTIFICATE=mysql_escape_string2($_SESSION["CERTIFICATE"]);
	$SCRCA=mysql_escape_string2($_SESSION["CA_CERTIFICATE"]);
	$q=new mysql();
	
	
	if(!$q->FIELD_EXISTS("sslcertificates","UploadCertWizard","artica_backup")){
		$sql="ALTER TABLE `sslcertificates` ADD `UploadCertWizard` smallint(1) NOT NULL DEFAULT '0',ADD INDEX ( `UploadCertWizard` )";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error;}
	}
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	
	
	if($ligne["CommonName"]==null){
		$sql="INSERT INTO sslcertificates
		(commonName,CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,
		emailAddress,localityName,password,`crt`,`privkey`,`srca`,`levelenc`,`bundle`,`csr`,UsePrivKeyCrt,UploadCertWizard) VALUES
		('$CommonName','$CountryName','$stateOrProvinceName','720',
		'$OrganizationName','$OrganizationalUnit','$emailAddress'
		,'$localityName','',
			'$WIZARD_CERTIFICATE',
			'$SCRCA','$SCRCA','$levelenc','','$CSR',1,1)";
		$generate=true;
	}else{
		$sql="UPDATE sslcertificates SET
		CountryName='$CountryName',
		stateOrProvinceName='$stateOrProvinceName',
		CertificateMaxDays='720',
		OrganizationName='$OrganizationName',
		OrganizationalUnit='$OrganizationalUnit',
		emailAddress='$emailAddress',
		localityName='$localityName',
		`crt`='$WIZARD_CERTIFICATE',
		`csr`='$CSR',
		`privkey`='$SCRCA',
		`srca`='$SCRCA',
		`bundle`='',
		`levelenc`='$levelenc',
		password='',
		UsePrivKeyCrt=1,
		UploadCertWizard=1 
		WHERE CommonName='$CommonName'";
	
	}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
}
		
