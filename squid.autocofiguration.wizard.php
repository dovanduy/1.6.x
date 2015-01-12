<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["step0"])){step0();exit;}
if(isset($_GET["step1"])){step1();exit;}
if(isset($_GET["step2"])){step2();exit;}
if(isset($_GET["step3"])){step3();exit;}
if(isset($_GET["step4"])){step4();exit;}

if(isset($_POST["DOMAIN"])){Save();exit;}
if(isset($_POST["LOCALNET"])){Save();exit;}
if(isset($_POST["PROXY"])){Save();exit;}
if(isset($_POST["agree"])){agree();exit;}


start_js();



function start_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{autoconfiguration_wizard}");
	echo "YahooWin2(900,'$page?step0=yes','$title',true)";	
	
}

function step0(){
	$page=CurrentPageName();
	$html="<div id='autoconfiguration_wizard_id'></div>
	<script>
		LoadAjax('autoconfiguration_wizard_id','$page?step1=yes');
	</script>
	";
	echo $html;
	
}


function step1(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$html="
	<div style='font-size:18px' class=text-info>{autoconfiguration_wizard1}</div>	
		<div style='width:98%' class=form>
		<div style='font-size:34px;margin-bottom:30px;'>{network_domain}:</div>
		<div style='padding-left:50px'>". Field_text("DOMAIN-$t",$_SESSION["autoconfiguration_wizard"]["DOMAIN"],"font-size:34px;width:650px")."</div>
		<div style='margin-top:30px;text-align:right'>". button("{next}", "Save$t()",32)."</div>
	</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	LoadAjax('autoconfiguration_wizard_id','$page?step2=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('DOMAIN',document.getElementById('DOMAIN-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
</script>
";
	
echo $tpl->_ENGINE_parse_body($html);
}
function step2(){
	$tpl=new templates();
	$page=CurrentPageName();
	$explain=$tpl->_ENGINE_parse_body("{autoconfiguration_wizard2}");
	$explain=str_replace("%a", $_SESSION["autoconfiguration_wizard"]["DOMAIN"], $explain);
	$t=time();
	$html="
	<div style='font-size:18px' class=text-info>$explain</div>
	<div style='width:98%' class=form>
	<div style='font-size:34px;margin-bottom:30px;'>{local_network}:</div>
	<div style='padding-left:50px'>". Field_text("LOCALNET-$t",$_SESSION["autoconfiguration_wizard"]["LOCALNET"],"font-size:30px;width:650px")."</div>
	<div style='margin-top:30px;text-align:right'>". button("{next}", "Save$t()",32)."</div>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	LoadAjax('autoconfiguration_wizard_id','$page?step3=yes');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('LOCALNET',document.getElementById('LOCALNET-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}
function step3(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	$explain=$tpl->_ENGINE_parse_body("{autoconfiguration_wizard3}");
	$explain=str_replace("%a", $_SESSION["autoconfiguration_wizard"]["DOMAIN"], $explain);
	$explain=str_replace("%b", $_SESSION["autoconfiguration_wizard"]["LOCALNET"], $explain);
	
	$html="
	<div style='font-size:18px' class=text-info>$explain</div>
	<div style='width:98%' class=form>
	<div style='font-size:34px;margin-bottom:30px;'>{proxy_address}:</div>
	<div style='padding-left:50px'>". Field_text("PROXY-$t",$_SESSION["autoconfiguration_wizard"]["PROXY"],"font-size:30px;width:650px;font-weight:bold")."</div>
	<div style='font-size:34px;margin-bottom:30px;margin-top:20px'>{proxy_port}:</div>
	<div style='padding-left:50px'>". Field_text("PORT-$t",$_SESSION["autoconfiguration_wizard"]["PORT"],"font-size:30px;width:150px;font-weight:bold")."</div>			
	<div style='margin-top:30px;text-align:right'>". button("{next}", "Save$t()",32)."</div>
	</div>
	<script>
	var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	LoadAjax('autoconfiguration_wizard_id','$page?step4=yes');
}

function Save$t(){
var XHR = new XHRConnection();
XHR.appendData('PROXY',document.getElementById('PROXY-$t').value);
XHR.appendData('PORT',document.getElementById('PORT-$t').value);
XHR.sendAndLoad('$page', 'POST',xSave$t);

}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}

function step4(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();

	$explain=$tpl->_ENGINE_parse_body("{autoconfiguration_wizard4}");
	$explain=str_replace("%a", $_SESSION["autoconfiguration_wizard"]["DOMAIN"], $explain);
	$explain=str_replace("%b", $_SESSION["autoconfiguration_wizard"]["LOCALNET"], $explain);
	$explain=str_replace("%c", $_SESSION["autoconfiguration_wizard"]["PROXY"], $explain);
	$explain=str_replace("%d", $_SESSION["autoconfiguration_wizard"]["PORT"], $explain);

	$html="
	<div style='font-size:18px' class=text-info>$explain</div>
	<div style='width:98%' class=form>
	<div style='margin-top:30px;text-align:center'>". button("{build_settings}", "Save$t()",50)."</div>
	</div>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	YahooWin2Hide();
	Loadjs('squid.autocofiguration.progress.php');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('agree','yes');
	XHR.sendAndLoad('$page', 'POST',xSave$t);

}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}


function agree(){
	while (list ($num, $ligne) = each ($_SESSION["autoconfiguration_wizard"]) ){
		$array[$num]=$ligne;
	}

	$sock=new sockets();
	$sock->SaveConfigFile(serialize($array), "SquidAutoconfWizard");


}


function Save(){
	
	while (list ($num, $ligne) = each ($_POST) ){
		$_SESSION["autoconfiguration_wizard"][$num]=$ligne;
	}
	
}
