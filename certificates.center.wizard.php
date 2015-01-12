<?php
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
	
	if(isset($_POST["wizard-certificate-commonname"])){wizard_certificate_save();exit;}
	if(isset($_GET["wizard-certificate-js"])){wizard_certificate_js();exit;}
	if(isset($_GET["wizard-certificate-1"])){wizard_certificate_1();exit;}
	if(isset($_POST["wizard-certificate-commonname"])){wizard_certificate_save();exit;}

	wizard_certificate_js();	
	
function wizard_certificate_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_certificate}");
	echo "YahooWin6(800,'$page?wizard-certificate-1=yes&t={$_GET["t"]}','$title')";
}
	
	
function wizard_certificate_1(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$hostname=$sock->GET_INFO("myhostname");
	$t=time();
	$ENC[1024]=1024;
	$ENC[2048]=2048;
	$ENC[4096]=4096;
	
	if($hostname==null){$hostname=$sock->getFrameWork("system.php?hostname-g=yes");$sock->SET_INFO($hostname,"myhostname");}
	$title=$tpl->_ENGINE_parse_body("{new_certificate}");
	$html[]="<div class=text-info style='font-size:18px'>{wizard_certificate_1}<br><i>{CSR_MULTIPLE_EXPLAIN}</i></div>";
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$html[]=Field_text_table("wizard-certificate-commonname","{CommonName}",$hostname,22,null,400);
	$html[]=Field_list_table("wizard-certificate-levelenc","{level_encryption}",2048,22,$ENC);
	$html[]=Field_password_table("wizard-certificate-password","{password}","secret",22,null,300);
	$html[]=Field_button_table_autonome("{add}","Submit$t",30);
	$html[]="</table>";
	$html[]="</div>
<script>
var xSubmit$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	$('#flexRT{$_GET["t"]}').flexReload();
	YahooWin6Hide();
}
	
	
function Submit$t(){
	var XHR = new XHRConnection();
	XHR.appendData('wizard-certificate-commonname',encodeURIComponent(document.getElementById('wizard-certificate-commonname').value));
	XHR.appendData('wizard-certificate-levelenc',document.getElementById('wizard-certificate-levelenc').value);
	XHR.appendData('wizard-certificate-password',encodeURIComponent(document.getElementById('wizard-certificate-password').value));
	XHR.sendAndLoad('$page', 'POST',xSubmit$t);
}
</script>
		
	";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));	
}	

function wizard_certificate_save(){
	$password=url_decode_special_tool($_POST["wizard-certificate-password"]);
	$password=mysql_escape_string2($password);
	$CommonName=url_decode_special_tool($_POST["wizard-certificate-commonname"]);
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
