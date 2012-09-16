<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');

	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perfs"])){perfs();exit;}
	if(isset($_POST["cache_mem"])){save();exit;}
	js();

	
function js(){

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cache_mem}");
	$page=CurrentPageName();
	$html="
		YahooWin3('350','$page?popup=yes','$title');
	
	";
		echo $html;
	
	
	
	
}

function save(){
	$squid=new squidbee();
	$squid->global_conf_array["cache_mem"]=trim($_POST["cache_mem"])." MB";
	$squid->SaveToLdap();
}




function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$squid=new squidbee();
	$cache_mem=$squid->global_conf_array["cache_mem"];
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
		<td colspan=3 align='right'><hr>". button("{apply}","SaveCacheMem()",16)."</td>
	</tr>
	</tbody>
	</table>
	</div>
<script>
	var x_SaveCacheMem=function (obj) {
		var tempvalue=obj.responseText;
		YahooWin3Hide();
	}	
	
	function SaveCacheMem(){
		var XHR = new XHRConnection();
		XHR.appendData('cache_mem',document.getElementById('cache_mem-$t').value);
		AnimateDiv('$t'); 
		XHR.sendAndLoad('$page', 'POST',x_SaveCacheMem);	
	}		
</script>	
";
	echo $tpl->_ENGINE_parse_body($html);
}	