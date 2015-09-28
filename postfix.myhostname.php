<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');


if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["myhostname"])){myhostname();exit;}

js();
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{your_bandwidth}:{options}");
	echo "YahooWin(990,'$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$sock=new sockets();
	$MyHostname=$sock->GET_INFO("myhostname");



	$html="
	<div style='width:98%' class=form>
	<div class=explain style='font-size:18px'>{myhostname_text}</div>


	<table style='width:100%'>
	<tbody>
	<tr>
	<td class=legend style='font-size:26px'>{myhostname}:</td>
	<td align='left' width=1% style='font-size:26px'>" . Field_text("myhostname-$t",$MyHostname,'width:500px;font-size:26px;padding:3px') ."</td>
	</tr>
	<tr>
	<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",45)."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>

var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	LoadAjaxRound('messaging-dashboard','admin.dashboard.postfix.php');
}

function Save$t(){
var XHR = new XHRConnection();
XHR.appendData('myhostname', document.getElementById('myhostname-$t').value);
XHR.sendAndLoad('$page', 'POST',xSave$t);
}

</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function myhostname(){
	$sock=new sockets();
	$sock->SET_INFO("myhostname", $_POST["myhostname"]);
	$sock->getFrameWork("postfix.php?myhostname=yes");
	
}