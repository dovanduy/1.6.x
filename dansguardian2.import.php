<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.squid.builder.php');
	include_once('ressources/class.mysql.dump.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert')";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["importYES"])){importYES();exit;}


js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->javascript_parse_text("{import_rules}");
	$t=time();
	$html="YahooWin4('550','$page?popup=yes','$title');";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$confirm=$tpl->javascript_parse_text("{confirm_import_rules_text}");
	$html="
			
	<div id='text-$t' style='font-size:16px' class=explain>{import_acl_rules_explain}</div>
	<div style='font-size:16px' id='$t-wait'></div>
	<div style='width:98%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:16px'>{backup_file}:</td>
		<td class=legend style='font-size:16px'>". Field_text("backupctner-$t",null,"font-size:16px;width:95%")."</td>
		<td width=1%>". button("{browse}...","Loadjs('tree.php?target-form=backupctner-$t&select-file=gz,acl')","12px")."</td>
	</tr>
				
<td colspan=3 align='right'><hr>". button("{import}...","Save$t()","18px")."</td></tr>
	</table>
</div>
<script>
			var x_Save$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t-wait').innerHTML='';
			if(results.length>3){alert(results);return;}
			RefreshTab('main_dansguardian_tabs');
			YahooWin4Hide();
			ExecuteByClassName('SearchFunction');
			
		}
	
	function Save$t(){
		if(!confirm('$confirm')){return;}
		var XHR = new XHRConnection();
		AnimateDiv('$t-wait');
		XHR.appendData('importYES',document.getElementById('backupctner-$t').value);
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	}		
</script>		
";
		
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function importYES(){
	$file=urlencode($_POST["importYES"]);
	$sock=new sockets();
	echo @implode("\n",unserialize(base64_decode($sock->getFrameWork("squid.php?import-webfiltering-rules=$file"))));
	
	
}	
?>