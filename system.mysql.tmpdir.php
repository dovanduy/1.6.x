<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',1);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.httpd.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.os.system.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("alert('{ERROR_NO_PRIVS}');");
		die();
	}

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["MySQLTMPDIR"])){save();exit;}
	if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{mysql_tmp_mem}");
	echo "YahooWin3('550','$page?popup=yes','$title')";
	
	
}


function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
	$MySQLTMPMEMSIZE=trim($sock->GET_INFO("MySQLTMPMEMSIZE"));
	if($MySQLTMPDIR==null){$MySQLTMPDIR="/tmp";}
	if(!is_numeric($MySQLTMPMEMSIZE)){$MySQLTMPMEMSIZE=0;}
	$t=time();
	$html="
	<div style='width:100%;text-align:right'><a href=\"javascript:blur();\" 
	OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=287','1024','900');\" 
	style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong>
	</div>
	<center id='$t-div'></center>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td>". Field_text("MySQLTMPDIR",$MySQLTMPDIR,"font-size:16px;width:250px")."</td>
		<td width=1% align='left'>". button("{browse}", "Loadjs('SambaBrowse.php?field=MySQLTMPDIR&no-shares=yes');","14px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{memory}:</td>
		<td colspan=2 style='font-size:16px' align='left'>". Field_text("MySQLTMPMEMSIZE",$MySQLTMPMEMSIZE,"font-size:16px;width:90px")."&nbsp;M</td>
	</tr>	
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveMysqltmp$t()","18px")."</td>
	</tr>
	
	</table>
	<script>
var x_SaveMysqltmp$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	document.getElementById('$t-div').innerHTML='';
	}
	


function SaveMysqltmp$t(){
	var XHR = new XHRConnection();
	XHR.appendData('MySQLTMPDIR',document.getElementById('MySQLTMPDIR').value);
	XHR.appendData('MySQLTMPMEMSIZE',document.getElementById('MySQLTMPMEMSIZE').value);
	AnimateDiv('$t-div');
	XHR.sendAndLoad('$page', 'POST',x_SaveMysqltmp$t);	
}

function refreshMysqlTempDirStatus(){
		LoadAjax('$t-div','$page?getramtmpfs=yes');
	}

	refreshMysqlTempDirStatus();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function save(){
	
	$sock=new sockets();
	$sock->SET_INFO("MySQLTMPDIR", $_POST["MySQLTMPDIR"]);
	$sock->SET_INFO("MySQLTMPMEMSIZE", $_POST["MySQLTMPMEMSIZE"]);
	
}
function getramtmpfs(){
$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
	$MySQLTMPMEMSIZE=trim($sock->GET_INFO("MySQLTMPMEMSIZE"));
	if($MySQLTMPDIR==null){return;}
	if($MySQLTMPDIR=="/tmp"){return;}
	if(!is_numeric($MySQLTMPMEMSIZE)){return;}
	if($MySQLTMPMEMSIZE<1){return; }
	$array=unserialize(base64_decode($sock->getFrameWork("mysql.php?getramtmpfs=yes&dir=".base64_encode($MySQLTMPDIR))));
	if(!is_numeric($array["PURC"])){$array["PURC"]=0;}
	if(!isset($array["SIZE"])){$array["SIZE"]="0M";}
	
	$html="<table style='width:30%' class=form>
	<tr><td valing='middle'>".pourcentage($array["PURC"])."</td>
	<td style='font-size:14px'>{$array["PURC"]}%/{$array["SIZE"]}</td>
	<td width=1%>". imgtootltip("20-refresh.png","{refresh}","refreshMysqlTempDirStatus()")."</td>
	</tr>
	<tr>
		<td colspan=3 style='font-size:14px' nowrap align='center'>$MySQLTMPDIR</td>
	</tr>
	</table>
	";
	echo $html;
}	
