<?php
    
	if($_GET["verbose"]){
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		$GLOBALS["VERBOSE"]=true;
	}
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
	if(isset($_GET["certificate-edit-csr"])){certificate_edit_csr();exit;}
	if(isset($_GET["verify-srca"])){certificate_edit_srca_verify();exit;}
	
	
	certificate_edit_csr();
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{ROOT_CERT}:{$_GET["CommonName"]}");
	$CommonName=urlencode($_GET["CommonName"]);
	echo "YahooWinT(1025,'$page?CommonName=$CommonName&t={$_GET["t"]}','$title')";	
	
}
	
function certificate_edit_csr(){
	$commonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$q=new mysql();
	$tpl=new templates();
	$sql="SELECT `srca`,`UsePrivKeyCrt` FROM sslcertificates WHERE CommonName='$commonName'";
	$upload_text=$tpl->_ENGINE_parse_body("{upload_content}");
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);}
	$tt=time();
	
	$CommonNameURL=urlencode("$commonName");
	$button_upload=button("$upload_text", "Loadjs('certificates.center.upload.php?certificate-upload-js=yes&CommonName=$CommonNameURL&type=srca&t={$_GET["t"]}&textid=text$tt&RunAfter=VerifyCertificate$tt',true)");
	
	
	
	$csr_ssl_explain=$tpl->_ENGINE_parse_body("{ROOT_CERT}");
	$html="
<div style='font-size:40px'>$csr_ssl_explain</div>
	<div id='verify-$tt'></div>
		<center>$button_upload</center>
		<center style='margin:10px'>
		<textarea id='text$t' style='font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;
		overflow:auto;font-size:16px !important;width:99%;height:390px'>{$ligne["srca"]}</textarea>
		</center>
	</div>
<script>
	function VerifyCertificate$tt(){
	LoadAjax('verify-$tt','$page?verify-srca=yes&CommonName=$CommonNameURL',true);
	}
	VerifyCertificate$tt();
</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	}
function certificate_edit_srca_verify(){
	
	$CommonName=$_GET["CommonName"];
	$q=new mysql();
	$sql="SELECT `srca`,`SquidCert`,`UsePrivKeyCrt`,`crt` FROM sslcertificates WHERE CommonName='$CommonName'";
	$t=$_GET["t"];
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){senderror($q->mysql_error);return;}
	$tt=time();
	
	if(strlen($ligne["srca"])<50){
		$sock=new sockets();
		echo "<p class='text-error' style='font-size:18px'>No content</p>
		<script>UnlockPage();</script>";
		return;
		}
	
	$certificate=$ligne["SquidCert"];
	if($ligne["UsePrivKeyCrt"]==1){
		$certificate=$ligne["crt"];
	}
	
	
	$main_path=dirname(__FILE__)."/ressources/conf/upload";
	$certificate_path="$main_path/server.pem";
	$root_certificate="$main_path/ca.pem";
	@file_put_contents($certificate_path, $certificate);
	@file_put_contents($root_certificate, $ligne["srca"]);
	
	$CMD[]="/usr/bin/openssl verify -verbose ";
	$CMD[]="-CAfile $root_certificate";
	$CMD[]="-purpose any $certificate_path 2>&1";
	
	
	$cmdline=@implode(" ", $CMD);
	if($GLOBALS["VERBOSE"]){echo "<hr>".$cmdline."<br>";}
	$f[]=$cmdline;
	exec($cmdline,$results);
	$INFO=array();
	$class="text-info";
	
	while (list ($num, $ligne) = each ($results) ){
		if($GLOBALS["VERBOSE"]){echo "<li style='font-size:12px>$ligne</li>\n";}
		
		$ligne=str_replace($main_path."/", "", $ligne);
		
		if(preg_match("#[0-9]+:error:[0-9A-Z]+:#",$ligne)){$class="text-error";}
		if(preg_match("#unable to load#",$ligne)){$class="text-error";}
		if(preg_match("#Subject:(.*)#",$ligne)){$INFO[]=$ligne;}
		if(preg_match("#server\.pem#",$ligne)){$INFO[]=$ligne;}
		if(preg_match("#verify OK#i",$ligne)){$INFO[]=$ligne;}
		if(preg_match("#OK#",$ligne)){$INFO[]=$ligne;}
		$ligne=htmlentities($ligne);
		$f[]="$ligne";
	}
	if($class=="text-error"){
		echo "<p class='$class' style='font-size:18px'>".@implode("<br>", $f)."</p><script>UnlockPage();</script>";
	}else{
		echo "<p class='$class' style='font-size:18px'>".@implode("<br>", $INFO)."</p><script>UnlockPage();</script>";
	}
	@unlink($root_certificate);
	@unlink($certificate_path);
	
	
}