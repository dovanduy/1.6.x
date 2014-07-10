<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["explainthis"])){explainthis();exit;}
	
page();	
function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	$button_reconfigure=button("{reconfigure}","Loadjs('squid.compile.progress.php');",32);
$html="
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>	
<td style='vertical-align:top;width:50px'><div id=\"slider-vertical\" style=\"height:300px;width:45px;margin:30px\"></div></td>
<td style='vertical-align:top;width:99%;padding-left:30px'>
	<div style='font-size:26px;margin-bottom:20px'>{cache_level}:<span id='level-info-$t'>$SquidCacheLevel</span></div>
	<div style='font-size:16px;;margin-bottom:20px'>{cache_level_explain}</div>
	<div style='font-size:18px' class=explain id='text-$t'></div>
	<div style='margin:20px;margin-top:60px;text-align:right'>$button_reconfigure</div>
</tr>
</table>
</div>
<script>	
	$(function() {
		$( \"#slider-vertical\" ).slider({
			orientation: \"vertical\",
			range: \"min\",
			min: 0,
			max: 4,
			width:50,
			value: $SquidCacheLevel,
			slide: function( event, ui ) {
				var xval=ui.value;
				document.getElementById('level-info-$t').innerHTML=xval;
				LoadAjax('text-$t','$page?explainthis='+xval);	
				
			}
		});
		$( \"#amount\" ).val( $( \"#slider-vertical\" ).slider( \"value\" ) );
		$('.ui-slider-handle').height(20);
		$('.ui-slider-handle').width(50);  
	});
LoadAjax('text-$t','$page?explainthis=$SquidCacheLevel');	
";

echo $tpl->_ENGINE_parse_body($html);
}

function explainthis(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->SET_INFO("SquidCacheLevel",$_GET["explainthis"]);
	echo $tpl->_ENGINE_parse_body("{SquidCacheLevel{$_GET["explainthis"]}}");
	
}
	
	
