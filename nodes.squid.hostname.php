<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["visible_hostname_save"])){save();exit;}

js();

function js(){
		$page=CurrentPageName();
		echo "
		YahooWin6(450,'$page?popup=yes&nodeid={$_GET["nodeid"]}','Hostname...','');
		
		var x_visible_hostname= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin6Hide();
		}
		
		function visible_hostname(){
			var XHR = new XHRConnection();
			XHR.appendData('visible_hostname_save',document.getElementById('visible_hostname_to_save').value);
			XHR.appendData('nodeid',{$_GET["nodeid"]});
			XHR.sendAndLoad('$page', 'POST',x_visible_hostname);	
		}		
		";
}


function popup(){
	$squid=new squidnodes($_GET["nodeid"]);
	$form="	
		<table style='width:99%' class=form>
			<tr>
			<td class=legend nowrap>{visible_hostname}:</td>
			<td>" . Field_text('visible_hostname_to_save',$squid->visible_hostname,'width:195px;font-size:16px')."</td>
			</tr>
			<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","visible_hostname();",16)."</td>
			</tr>
		</table>			
		";
		
		
		
		$html="$form<div class=text-info>{visible_hostname_text}</div>";
		
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body($html,'squid.index.php');	
	
}
function save(){
	$squid=new squidnodes($_POST["nodeid"]);
	$squid->SET("visible_hostname",$_POST["visible_hostname_save"]);
	$squid->SaveToLdap();
}