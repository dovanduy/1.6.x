<?php
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");


if(posix_getuid()<>0){
	$user=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();exit();
	}
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["import-path"])){import();exit;}
js();


function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->AsDnsAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
}
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_POWERDNS}&nbsp;|{import}");
	$html="YahooWin('680','$page?popup=yes&t={$_GET["t"]}','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$default_domain=$tpl->javascript_parse_text("{default_domain}");
	$restore_from_container=$tpl->javascript_parse_text("{restore_from_container_ask}");
	$html="
	<div style='font-size:14px' class=explain>{PDNS_IMPORT_EXPLAIN}</div>
	<div id='$t'></div>
	<table style='width:99%' class=form>
		<tr>
			<td class=legend style='font-size:14px'>$default_domain:</td>
			<td>". Field_text("domain-$t",null,"font-size:14px;width:300px")."</td>
			<td>&nbsp;</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px'>{file}:</td>
			<td>". Field_text("import-$t",null,"font-size:14px;width:300px")."</td>
			<td>". button("{browse}...", "Loadjs('tree.php?select-file=txt&target-form=import-$t');",11)."</td>
		</tr>
		<tr>
			<td colspan=3 align='right'><hr>". button("{import}","ResTore$t()",18)."</td>
		</tr>
	</table>	
	
	<script>
	var x_ResTore$t= function (obj) {
		var tempvalue=obj.responseText;
		document.getElementById('$t').innerHTML='';
		if(tempvalue.length>3){alert(tempvalue)};
		$('#flexRT$t').flexReload();
	 }	
	
	function ResTore$t(){
		var sp=encodeURIComponent(document.getElementById('import-$t').value);
		var sd=document.getElementById('domain-$t').value;
		if(sd.length<2){
			alert('$default_domain ??');
			return;
		}
		
		if(sp.length<2){return;}		
		var zconfirm='$restore_from_container ' +sp+' ?';
		
		if(confirm(zconfirm)){
			var XHR = new XHRConnection();
			XHR.appendData('import-path',sp);
			XHR.appendData('default-domain',document.getElementById('domain-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_ResTore$t);
		}
		
	}					
		
</script>					
	
	";
	
echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function import(){
	$sock=new sockets();
	$defaultdomain=$_POST["default-domain"];
	$path=base64_encode(url_decode_special_tool($_POST["import-path"]));
	echo base64_decode($sock->getFrameWork("pdns.php?import-file=$path&domain=$defaultdomain"));
	
}


