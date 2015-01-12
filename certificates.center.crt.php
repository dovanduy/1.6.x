<?php
    //ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
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
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["certificate-edit-crt"])){certificate_edit_crt();exit;}
	if(isset($_GET["certificate-info-crt-js"])){certificate_info_crt_js();exit;}
	if(isset($_GET["certificate-info-crt-popup"])){certificate_info_crt_popup();exit;}
	if(isset($_GET["verify-crt"])){certificate_edit_crt_verify();exit;}
	if(isset($_POST["save-crt"])){certificate_edit_crt_save();exit;}
	
	certificate_edit_crt();
	
	function js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{CSR}:{$_GET["CommonName"]}");
		$CommonName=urlencode($_GET["CommonName"]);
		echo "YahooWinT(1025,'$page?CommonName=$CommonName&t={$_GET["t"]}','$title')";
	}
	
	function certificate_info_crt_js(){
		header("content-type: application/x-javascript");
		$page=CurrentPageName();
		$tpl=new templates();
		$title=$tpl->_ENGINE_parse_body("{certificate}:{info}:{$_GET["CommonName"]}");
		$CommonName=urlencode($_GET["CommonName"]);
		echo "YahooWinBrowse(650,'$page?certificate-info-crt-popup=yes&CommonName=$CommonName&type={$_GET["type"]}&t={$_GET["t"]}','$title')";
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
	$sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$warn_gen_x50=$tpl->javascript_parse_text("{warn_gen_x509}");
	$CommonNameURL=urlencode($CommonName);
	$button_upload=button("$upload_text", "Loadjs('certificates.center.upload.php?certificate-upload-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt&RunAfter=VerifyCertificate$tt',true)",22);
	$button_extract=$tpl->_ENGINE_parse_body(button("{info}", "Loadjs('$page?certificate-info-crt-js=yes&CommonName=$CommonNameURL&type=crt&t={$_GET["t"]}&textid=crt$tt',true)",22));
	$button_save=$tpl->_ENGINE_parse_body(button($apply,"SaveCRT$tt()",22));
	
	
	
	$field="crt";
	if($ligne["UsePrivKeyCrt"]==0){
		$field="SquidCert";
		$button_upload=null;
		$button_save=null;
		
	}
	
	
	$html="
		<div class=text-info style='font-size:18px' id='$tt-adddis'>{public_key_ssl_explain}</div>
		<div id='verify-$tt'></div>
		<center>$button_upload&nbsp;$button_extract</center>
		<textarea
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px' id='crt$tt'>{$ligne[$field]}</textarea>
		<center style='margin:10px'>$button_save</center>
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
	function certificate_edit_crt_verify(){
		$CommonName=$_GET["CommonName"];
		$q=new mysql();
		$sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`  FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		$field="crt";
		if($ligne["UsePrivKeyCrt"]==0){
			$field="SquidCert";
			$button_upload=null;
		
		}
		
		$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
		@file_put_contents($filepath, $ligne[$field]);
		exec("/usr/bin/openssl verify -verbose $filepath 2>&1",$results);
	
		$class="text-info";
	
		while (list ($num, $ligne) = each ($results) ){
			if(preg_match("#[0-9]+:error:[0-9A-Z]+:PEM routines:#",$ligne)){$class="text-error";}
			if(preg_match("#unable to load#",$ligne)){$class="text-error";}
			$ligne=str_replace($filepath, "Info", $ligne);
			$ligne=htmlentities($ligne);
			$f[]="$ligne";
	
		}
	
		echo "<p class='$class' style='font-size:18px'>".@implode("<br>", $f)."</p><script>UnlockPage();</script>";
	}
	
	function certificate_info_crt_popup(){
		$CommonName=$_GET["CommonName"];
		$q=new mysql();
		$sql="SELECT `crt`,`SquidCert`,`UsePrivKeyCrt`  FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		
		$field="crt";
		if($ligne["UsePrivKeyCrt"]==0){
			$field="SquidCert";
			
		
		}
		$filepath=dirname(__FILE__)."/ressources/conf/upload/Cert.pem";
		@file_put_contents($filepath, $ligne[$field]);
		exec("/usr/bin/openssl x509 -text -in $filepath 2>&1",$results);
	
		while (list ($num, $ligne) = each ($results) ){
			$ligne=trim($ligne);
			$tt[]=$ligne;
		}
	
		echo "<textarea
		style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:12px !important;width:99%;height:390px'>".@implode("\n", $tt)."</textarea>";
	
	}