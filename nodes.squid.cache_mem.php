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
if(isset($_POST["cache_mem"])){save();exit;}

js();

function js(){
		$tpl=new templates();
		$title=$tpl->javascript_parse_text("{cache_mem}");
		$page=CurrentPageName();
		echo "YahooWin6(450,'$page?popup=yes&nodeid={$_GET["nodeid"]}','$title');";
}


function popup(){
	$squid=new squidnodes($_GET["nodeid"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$cache_mem=$squid->GET("cache_mem");
	if(!is_numeric($cache_mem)){$cache_mem=128;}
	if(preg_match("#([0-9]+)\s+#", $cache_mem,$re)){$cache_mem=$re[1];}
	$t=time();
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:16px'>{memory}:</td>
		<td style='font-size:16px'>". Field_text("cache_mem-$t",$cache_mem,"font-size:16px;width:65px")."&nbsp;MB<td>
		<td style='font-size:16px' width=1%>". help_icon("{cache_mem_text}")."<td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveCacheMem$t()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_SaveCacheMem$t=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin6Hide();
	}	
	
	function SaveCacheMem$t(){
		var XHR = new XHRConnection();
		XHR.appendData('nodeid',{$_GET["nodeid"]});
		XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem$t);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}
function save(){
	$squid=new squidnodes($_POST["nodeid"]);
	$squid->SET("cache_mem",$_POST["cache_mem"]);
	$squid->SaveToLdap();
}