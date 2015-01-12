<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["step0"])){step0();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["step3"])){step3();exit;}
	
	if(isset($_POST["connected_port"])){Save();exit;}
	if(isset($_POST["transparent_ssl_port"])){Save();exit;}
	
	
	
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{wizard_transparent_button}");
	echo "YahooWin6(850,'$page?step0=yes','$title')";
	
}

function step0(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="<div id='wizard_transparent_button'></div>
	<script>
		LoadAjax('wizard_transparent_button','$page?step1=yes');	
	</script>		
	";
	echo $html;
}

function step1(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$squid=new squidbee();
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	$connected_port=intval($WizardProxyTransparent["connected_port"]);
	$transparent_port=intval($WizardProxyTransparent["transparent_port"]);
	
	if($transparent_port==0){$transparent_port=$squid->listen_port;}
	if($connected_port==0){$connected_port=$squid->second_listen_port;}
	
	if($connected_port==0){
		if($transparent_port==3128){$connected_port=8080;}
		if($transparent_port==8080){$connected_port=3128;}
		
	}
	
	if($connected_port==0){
		$connected_port=3129;
	
	}
	
	$t=time();
	$html="<div style='width:98%' class=form>
	<div class='text-info' style='font-size:18px;margin-bottom:20px'>{wizard_transparent_button1}</div>
	<table style='width:100%'>
	". Field_text_table("connected_port-$t","{connected_port}",$connected_port,18,"{wizard_transparent_buttonC}",120).
	Field_text_table("transparent_port-$t","{transparent_port}",$transparent_port,18,"{wizard_transparent_buttonC}",120).
	Field_button_table_autonome("{next}", "Save$t",26)."</table>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	LoadAjax('wizard_transparent_button','$page?step2=yes');	
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('connected_port',document.getElementById('connected_port-$t').value);
	XHR.appendData('transparent_port',document.getElementById('transparent_port-$t').value);
	XHR.sendAndLoad('$page', 'POST', xSave$t);		
}	
</script>";
echo $tpl->_ENGINE_parse_body($html);	
	
}
function step2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$squid=new squidbee();
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	$connected_port=intval($WizardProxyTransparent["connected_port"]);
	$transparent_port=intval($WizardProxyTransparent["transparent_port"]);
	$transparent_ssl_port=intval($WizardProxyTransparent["transparent_ssl_port"]);
	$EnableSSLBump=intval($WizardProxyTransparent["EnableSSLBump"]);
	$t=time();
	
	
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port =$squid->listen_port+5;}
	if($squid->ssl_port<3){$squid->ssl_port =$squid->listen_port+5;}
	if($squid->ssl_port==443){$squid->ssl_port=$squid->listen_port+10;}
	if($transparent_ssl_port==0){$transparent_ssl_port=$squid->ssl_port;}
	if($transparent_ssl_port==$transparent_port){$transparent_ssl_port=0;}
	if($transparent_ssl_port==$connected_port){$transparent_ssl_port=0;}
	
	if($transparent_ssl_port==0){$transparent_ssl_port=rand(8080,9090);}
	
	$enableSSLBump=Paragraphe_switch_img("{activate_ssl_bump}",
			"{activate_ssl_bump_text2}","EnableSSLBump-$t",$EnableSSLBump,null,650);
	
	
	
$html="<div style='width:98%' class=form>
	<div style='font-size:32px;margin-bottom:20px'>{UseSSL}</div>
	$enableSSLBump
<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px;font-weight:bold'>{connected_port}:</td>
		<td  style='font-size:18px;font-weight:normal'>$connected_port</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px;font-weight:bold'>{transparent_port}:</td>
		<td  style='font-size:18px;font-weight:normal'>$transparent_port</td>
	</tr>	
	". Field_text_table("transparent_ssl_port-$t","{ssl_port}",$transparent_ssl_port,18,"{wizard_transparent_buttonE}",120).
	Field_button_table_autonome("{next}", "Save$t",26)."</table>
	</div>
<script>
var xSave$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue)};
	LoadAjax('wizard_transparent_button','$page?step3=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableSSLBump',document.getElementById('EnableSSLBump-$t').value);
	XHR.appendData('transparent_ssl_port',document.getElementById('transparent_ssl_port-$t').value);
	XHR.sendAndLoad('$page', 'POST', xSave$t);
}
</script>";
echo $tpl->_ENGINE_parse_body($html);

}
function step3(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$squid=new squidbee();
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	$connected_port=intval($WizardProxyTransparent["connected_port"]);
	$transparent_port=intval($WizardProxyTransparent["transparent_port"]);
	$transparent_ssl_port=intval($WizardProxyTransparent["transparent_ssl_port"]);
	$EnableSSLBump=intval($WizardProxyTransparent["EnableSSLBump"]);
	$t=time();


	$wizard_transparent_button_final=$tpl->_ENGINE_parse_body("{wizard_transparent_button_final}");
	$wizard_transparent_button_final=str_replace("%p1", $connected_port, $wizard_transparent_button_final);
	$wizard_transparent_button_final=str_replace("%p2", $transparent_port, $wizard_transparent_button_final);
	
	if($EnableSSLBump==1){$enabled=$tpl->_ENGINE_parse_body("{enabled}");}else{$enabled=$tpl->_ENGINE_parse_body("{disabled}");}
	$wizard_transparent_button_final=str_replace("%enabled", $enabled, $wizard_transparent_button_final);
	$html="<div style='width:98%' class=form>
	<div style='font-size:32px;margin-bottom:20px'>{ready_to_build}</div>
	<div class='text-info' style='font-size:18px;margin-bottom:20px'>$wizard_transparent_button_final</div>
	<table style='width:100%'>
	".
	Field_button_table_autonome("{apply}", "Save$t",26)."</table>
	</div>
<script>


function Save$t(){
	YahooWin6Hide();
	Loadjs('squid.transparent.progress.php');
}
</script>";
	echo $tpl->_ENGINE_parse_body($html);

}

function Save(){
	$sock=new sockets();
	$WizardProxyTransparent=unserialize($sock->GET_INFO("WizardProxyTransparent"));
	while (list ($key, $val) = each ($_POST) ){
		$WizardProxyTransparent[$key]=$val;
	}
	$sock->SaveConfigFile(serialize($WizardProxyTransparent),"WizardProxyTransparent");
}



