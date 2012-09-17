<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');

	
	$user=new usersMenus();
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["items"])){items();exit;}
	if(isset($_GET["certificate-popup"])){certificate_infos();exit;}
	if(isset($_GET["certificate-tabs"])){certificate_tabs();exit;}
	if(isset($_POST["commonName"])){certificate_save();exit;}
	if(isset($_GET["PrivateKey"])){PrivateKey();exit;}
	if(isset($_GET["csr"])){csr();exit;}
	if(isset($_POST["generate-key"])){generate_key();exit;}
	if(isset($_GET["generate-x509"])){generate_x509();exit;}
	if(isset($_GET["crt"])){crt();exit;}
	if(isset($_GET["bundle"])){bundle();exit;}
	if(isset($_GET["tools"])){tools();exit;}
	if(isset($_GET["x509-js"])){x509_js();exit;}
	if(isset($_POST["save-crt"])){save_crt();exit;}
	if(isset($_POST["save-bundle"])){save_bundle();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{certificates_center}");
	$html="YahooWin2('850','$page?popup=yes','$title')";
	echo $html;
}
function x509_js(){
	$CommonName=$_GET["x509-js"];
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->_ENGINE_parse_body("$CommonName::{generate_x509}");
	$warn_gen_x509=$tpl->javascript_parse_text("{warn_gen_x509}");
	$html="
	function Gen$t(){
		if(confirm('$warn_gen_x509')){
			YahooWin2('750','$page?generate-x509=$CommonName','$title');
			}
		}	
		Gen$t();
	";
	echo $html;	
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$Organization=$tpl->_ENGINE_parse_body("{organizationName}");
	$organizationalUnitName=$tpl->_ENGINE_parse_body("{organizationalUnitName}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$emailAddress=$tpl->javascript_parse_text("{emailAddress}");
	$new_certificate=$tpl->javascript_parse_text("{new_certificate}");
	$title=$tpl->_ENGINE_parse_body("{certificates_center}");
	
	$buttons="
	buttons : [
	{name: '$new_certificate', bclass: 'Add', onpress : new_certificate$t},
	],";		
	
$html="
<div style='margin-left:0px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'CommonName', width : 237, sortable : true, align: 'left'},	
		{display: '$Organization', name : 'Organization', width :189, sortable : true, align: 'left'},
		{display: '$organizationalUnitName', name : 'OrganizationalUnit', width :163, sortable : true, align: 'left'},
		{display: '$emailAddress', name : 'emailAddress', width :128, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'del', width :31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$hostname', name : 'CommonName'},
		{display: '$Organization', name : 'Organization'},
		{display: '$organizationalUnitName', name : 'OrganizationalUnit'},
		{display: '$emailAddress', name : 'emailAddress'},
		],
		
	sortname: 'CommonName',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 830,
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function new_certificate$t(){
	YahooWin3('700','$page?certificate-tabs=yes&t=$t&CommonName=&YahooWin=YahooWin3','$new_certificate');
}
function certificate$t(CommonName){
	YahooWin3('700','$page?certificate-tabs=yes&t=$t&CommonName='+CommonName+'&YahooWin=YahooWin3',CommonName);
}
</script>
";

	echo $html;	
	
}
function items(){
	//1.4.010916
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$search='%';
	$table="sslcertificates";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER="";
	
	if(!$q->TABLE_EXISTS("sslcertificates", $database)){$q->BuildTables();}
	

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$zmd5=md5($ligne["filename"]);
	$delete=imgsimple("delete-24.png","","DeleteFileNameHosting$t('{$ligne["filename"]}','$zmd5')");
	
	
	$urljs="<a href=\"javascript:blur();\" OnClick=\"javascript:certificate$t('{$ligne["CommonName"]}');\"
	style='font-size:16px;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
		'id' => "D$zmd5",
		'cell' => array(
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["CommonName"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["OrganizationName"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["OrganizationalUnit"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$urljs{$ligne["emailAddress"]}</a></span>",
			"<span style='font-size:16px;color:$color'>$delete</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
	
}

function certificate_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	$CommonName=$_GET["CommonName"];
	$YahooWin=$_GET["YahooWin"];
	$array["certificate-popup"]='{parameters}';
	if($CommonName<>null){
		$array["PrivateKey"]='{private_key}';
		$array["csr"]='CSR';
		$array["crt"]='{certificate}';
		$array["bundle"]='{certificate_chain}';
		$array["tools"]="{tools}";
		
	}
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&CommonName=$CommonName&YahooWin={$_GET["YahooWin"]}&t={$_GET["t"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}

	echo "
	<div id=main_config_certificate style='width:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_certificate').tabs();
			
			
			});
		</script>";		

	
}

function certificate_infos(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql();
	$buttonname="{add}";
	$CommonName=$_GET["CommonName"];
	$tt=time();
	
	$db=file_get_contents(dirname(__FILE__) . '/ressources/databases/ISO-3166-Codes-Countries.txt');
	$tbl=explode("\n",$db);
	while (list ($num, $ligne) = each ($tbl) ){
		if(preg_match('#(.+?);\s+([A-Z]{1,2})#',$ligne,$regs)){
			$regs[2]=trim($regs[2]);
			$regs[1]=trim($regs[1]);
			$array_country_codes["{$regs[1]}_{$regs[2]}"]=$regs[1];
			}
		}
		
	if($CommonName<>null){
		$sql="SELECT *  FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$buttonname="{apply}";
	}		
		
	
	
	if($ligne["CountryName"]==null){$ligne["CountryName"]="UNITED STATES_US";}	
	if($ligne["stateOrProvinceName"]==null){$ligne["stateOrProvinceName"]="New York";}
	if($ligne["localityName"]==null){$ligne["localityName"]="Brooklyn";}
	if($ligne["emailAddress"]==null){$ligne["emailAddress"]="postmaster@localhost.localdomain";}
	if($ligne["OrganizationName"]==null){$ligne["OrganizationName"]="MyCompany Ltd";}
	if($ligne["OrganizationalUnit"]==null){$ligne["OrganizationalUnit"]="IT service";}						
	
	
	
	
	$country_name=Field_array_Hash($array_country_codes,"CountryName-$tt",$ligne["CountryName"],
	"style:font-size:14px;padding:3px");
	
	
	if(!is_numeric($ligne["CertificateMaxDays"])){$ligne["CertificateMaxDays"]=730;}
	if($ligne["CommonName"]==null){$ligne["CommonName"]=$users->hostname;}
	$html="
	<div id='$tt-adddis'></div>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{commonName}:</strong></td>
			<td align='left'>" . Field_text("commonName-$tt",$ligne["CommonName"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>
	
		<tr>
			<td align='left' class=legend style='font-size:14px'><strong>{countryName}</strong>:</td>
			<td >$country_name</td>
		</tr>
		<tr>
		<td class=legend style='font-size:14px'>{stateOrProvinceName}:</strong></td>
		<td align='left'>" . Field_text("stateOrProvinceName-$tt",$ligne["stateOrProvinceName"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>
		<tr>
		<td class=legend style='font-size:14px'>{localityName}:</strong></td>
		<td align='left'>" . Field_text("localityName-$tt",$ligne["localityName"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>	
		<tr>
		<td class=legend style='font-size:14px'>{CertificateMaxDays}:</strong></td>
		<td align='left' style='font-size:14px;width:40px;padding:3px'>" . Field_text("CertificateMaxDays-$tt",$ligne["CertificateMaxDays"],"font-size:14px;width:40px;padding:3px")  . "&nbsp;{days}</td>
		</tr>	
		<tr>
		<td class=legend style='font-size:14px'>{organizationName}:</strong></td>
		<td align='left'>" . Field_text("OrganizationName-$tt",$ligne["OrganizationName"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>				
		<tr>
		<td class=legend style='font-size:14px'>{organizationalUnitName}:</strong></td>
		<td align='left'>" . Field_text("OrganizationalUnit-$tt",$ligne["OrganizationalUnit"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>	

		<tr>
		<td class=legend style='font-size:14px'>{emailAddress}:</strong></td>
		<td align='left'>" . Field_text("emailAddress-$tt",$ligne["emailAddress"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>	
		<tr>
		<td class=legend style='font-size:14px'>{password}:</strong></td>
		<td align='left'>" . Field_text("password-$tt",$ligne["password"],"font-size:14px;width:250px;padding:3px")  . "</td>
		</tr>	
		<tr><td colspan=2 align='right'>
		<hr>
			". button("$buttonname","SaveSSLCert$tt()","18px"). "
		
		</td>
		</tr>
		</tbody>
	</table>
	
	<script>
		var x_SaveSSLCert$tt=function (obj) {
			var results=obj.responseText;	
			var CommonName='$CommonName';
			document.getElementById('$tt-adddis').innerHTML='';
			
			if (results.length>3){alert(results);return;}
			if(CommonName.length>0){
				RefreshTab('main_config_certificate');
			}else{
				{$_GET["YahooWin"]}Hide();
			
			}
			$('#flexRT$t').flexReload();
		}	
	
	
		function SaveSSLCert$tt(){
		  var XHR = new XHRConnection();  
		  var pp=encodeURIComponent(document.getElementById('password-$tt').value);
		  XHR.appendData('commonName',document.getElementById('commonName-$tt').value);
		  XHR.appendData('CountryName',document.getElementById('CountryName-$tt').value);
		  XHR.appendData('stateOrProvinceName',document.getElementById('stateOrProvinceName-$tt').value);
		  XHR.appendData('CertificateMaxDays',document.getElementById('CertificateMaxDays-$tt').value);
		  XHR.appendData('OrganizationName',document.getElementById('OrganizationName-$tt').value);
		  XHR.appendData('localityName',document.getElementById('localityName-$tt').value);
	      XHR.appendData('OrganizationalUnit',document.getElementById('OrganizationalUnit-$tt').value);
	      XHR.appendData('emailAddress',document.getElementById('emailAddress-$tt').value);
	      XHR.appendData('password',pp);
		  AnimateDiv('$tt-adddis');
		  XHR.sendAndLoad('$page', 'POST',x_SaveSSLCert$tt);
		}
		
		function checkFileds$tt(){
			var CommonName='$CommonName';
			if(CommonName.length>2){
				document.getElementById('commonName-$tt').disabled=true;
			}
		}
	
	</script>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}
function certificate_save(){
	$q=new mysql();
	$q->BuildTables();
	$CommonName=strtolower(trim($_POST["CommonName"]));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	while (list ($num, $vl) = each ($_POST) ){$_POST[$num]=addslashes($vl);}
	$generate=false;
	
	$sql="SELECT CommonName  FROM sslcertificates WHERE CommonName='$CommonName'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if($ligne["CommonName"]==null){
		$sql="INSERT INTO sslcertificates 
			(commonName,CountryName,stateOrProvinceName,CertificateMaxDays,OrganizationName,OrganizationalUnit,
			emailAddress,localityName,password) VALUES
			('{$_POST["commonName"]}','{$_POST["CountryName"]}','{$_POST["stateOrProvinceName"]}','{$_POST["CertificateMaxDays"]}',
			'{$_POST["OrganizationName"]}','{$_POST["OrganizationalUnit"]}','{$_POST["emailAddress"]}'
			,'{$_POST["localityName"]}','{$_POST["password"]}')";
			$generate=true;
	}else{
		$sql="UPDATE sslcertificates SET 
			CountryName='{$_POST["commonName"]}',
			stateOrProvinceName='{$_POST["stateOrProvinceName"]}',
			CertificateMaxDays='{$_POST["CertificateMaxDays"]}',
			OrganizationName='{$_POST["OrganizationName"]}',
			OrganizationalUnit='{$_POST["OrganizationalUnit"]}',
			emailAddress='{$_POST["emailAddress"]}',
			localityName='{$_POST["localityName"]}',
			password='{$_POST["password"]}'
			WHERE CommonName='$CommonName'";
		
	}
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n\n$sql\n";return;}
	if($generate){
		$sock=new sockets();
		$sock->getFrameWork("openssl.php?generate-key=$CommonName");
	}
	
}

function PrivateKey(){
		$t=$_GET["t"];
		$CommonName=$_GET["CommonName"];
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$users=new usersMenus();
		$q=new mysql();
		$sql="SELECT privkey  FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$html="
		<div class=explain style='font-size:14px' id='$tt-adddis'>{private_key_ssl_explain}</div>
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px' 
		id='textToParseCats$t'>{$ligne["privkey"]}</textarea>
		<center style='margin:10px'>". button("{generate_key}","GenerateKey$tt()","18px")."</center>
		<script>
		var x_GenerateKey$tt=function (obj) {
			var results=obj.responseText;	
			document.getElementById('$tt-adddis').innerHTML='';
			if (results.length>3){alert(results);return;}
			RefreshTab('main_config_certificate');
			$('#flexRT$t').flexReload();
		}	
	
	
		function GenerateKey$tt(){
		  var XHR = new XHRConnection();  
		  AnimateDiv('$tt-adddis');
    	 XHR.appendData('generate-key','$CommonName');
		  XHR.sendAndLoad('$page', 'POST',x_GenerateKey$tt);
		}
		</script>
		
		";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function csr(){
		$t=$_GET["t"];
		$CommonName=$_GET["CommonName"];
		$page=CurrentPageName();
		$tpl=new templates();
		$sock=new sockets();
		$users=new usersMenus();
		$q=new mysql();
		$sql="SELECT csr FROM sslcertificates WHERE CommonName='$CommonName'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$html="
		<div class=explain style='font-size:14px'>{csr_ssl_explain}</div>
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px' 
		id='textToParseCats$t'>{$ligne["csr"]}</textarea>
		";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function crt(){
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
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px' 
		id='crt$tt'>{$ligne["crt"]}</textarea>
		<center style='margin:10px'>". button("{apply}","SaveCRT$tt()","18px")."</center>
		<script>
		var x_SaveCRT$tt=function (obj) {
			var results=obj.responseText;	
			document.getElementById('$tt-adddis').innerHTML='';
			if (results.length>3){alert(results);return;}
			RefreshTab('main_config_certificate');
			$('#flexRT$t').flexReload();
		}	
		function SaveCRT$tt(){
			if(confirm('$warn_gen_x50')){
				var XHR = new XHRConnection();  
				XHR.appendData('save-crt',document.getElementById('crt$tt').value);
				XHR.appendData('CommonName','$CommonName');
				AnimateDiv('$tt-adddis');
				XHR.sendAndLoad('$page', 'POST',x_SaveCRT$tt);
			}
		}
		</script>
		
		";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function save_crt(){
	$data=$_POST["save-crt"];
	$CommonName=$_POST["CommonName"];
	$sql="UPDATE sslcertificates SET `crt`='$data' WHERE `CommonName`='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$tpl=new templates();
	echo $tpl->javascript_parse_text($sock->getFrameWork("openssl.php?tomysql=$CommonName"));
	
}
function save_bundle(){
	$data=$_POST["save-bundle"];
	$CommonName=$_POST["CommonName"];
	$sql="UPDATE sslcertificates SET `bundle`='$data' WHERE `CommonName`='$CommonName'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	echo $tpl->javascript_parse_text($sock->getFrameWork("openssl.php?tomysql=$CommonName"));
	
}
function bundle(){
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
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:14.5px' 
		id='bundl$tt'>{$ligne["bundle"]}</textarea>
		<center style='margin:10px'>". button("{apply}","SaveBundle$tt()","18px")."</center>
		<script>
		var x_SaveBundle$tt=function (obj) {
			var results=obj.responseText;	
			document.getElementById('$tt-adddis').innerHTML='';
			if (results.length>3){alert(results);return;}
			RefreshTab('main_config_certificate');
			$('#flexRT$t').flexReload();
		}	
		function SaveBundle$tt(){
			if(confirm('$warn_gen_x50')){
				var XHR = new XHRConnection();  
				XHR.appendData('save-bundle',document.getElementById('bundl$tt').value);
				XHR.appendData('CommonName','$CommonName');
				AnimateDiv('$tt-adddis');
				XHR.sendAndLoad('$page', 'POST',x_SaveBundle$tt);
			}
			
			}
		</script>	
		";
	
	echo $tpl->_ENGINE_parse_body($html);		
	
}

function generate_key(){
	$sock=new sockets();
	$sock->getFrameWork("openssl.php?generate-key={$_GET["generate-key"]}");	
	
}
function generate_x509(){
	$sock=new sockets();
	$tpl=new templates();
	$datas=base64_decode($sock->getFrameWork("openssl.php?generate-x509={$_GET["generate-x509"]}"));
	$html="
	
		<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:620px;border:5px solid #8E8E8E;overflow:auto;font-size:12px' 
		id='textToParseCats$t'>$datas</textarea>
			
		";
	
	echo $tpl->_ENGINE_parse_body($html);		
				
}

function tools(){
	$t=$_GET["t"];
	$CommonName=$_GET["CommonName"];
	$page=CurrentPageName();
	$tpl=new templates();	
	
	$tt=time();
	$tr[]=Paragraphe("vpn-rebuild.png", "{generate_x509}", "{generate_x509_text}","javascript:Loadjs('$page?x509-js=$CommonName');");
	
	$table=CompileTr3($tr);
	
	
	$html="
	$table
	<script>
		var x_GenerateX509$tt=function (obj) {
			var results=obj.responseText;	
			document.getElementById('$tt-adddis').innerHTML='';
			if (results.length>3){alert(results);return;}
			RefreshTab('main_config_certificate');
			$('#flexRT$t').flexReload();
		}	
	
	
		function GenerateX509$tt(){
		  var XHR = new XHRConnection();  
		  AnimateDiv('$tt-adddis');
    	 XHR.appendData('generate-x509','$CommonName');
		  XHR.sendAndLoad('$page', 'POST',x_GenerateX509$tt);
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

