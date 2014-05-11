<?php
include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.clamav.inc');

	$user=new usersMenus();
	if($user->AsSystemAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["CloseWizard"])){CloseWizard();exit;}
	if(isset($_POST["MySQLNgnixType"])){MySQLNgnixType_save();exit;}
	if(isset($_POST["ListenPort"])){MySQLNgnixType_save();exit;}
	if(isset($_POST["apply"])){apply();exit;}
	if(isset($_POST["RemotePort"])){remote_save();exit;}
	
	if(isset($_GET["Next1"])){Next1();exit;}
	if(isset($_GET["Next2"])){Next2();exit;}
	if(isset($_GET["Next3"])){Next3();exit;}
js();
	
function js(){
	$usersmenus=new usersMenus();
	$tpl=new templates();
	$page=CurrentPageName();		
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{database_statistics_wizard}");
	echo "YahooWin3('700','$page?popup=yes','$title')";
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div id='$t'>
	<div class=explain style='font-size:14px'>{MYSQLNGINX_EXPLAIN}</div>
	<table style='width:100%'>
	<tr>
		<td align='left' style='width:50%'>". button("{close}","Close$t()",18)."</td>
		<td align='right' style='width:50%'>". button("{next}","Next1$t()",18)."</td>
	</tr>
	</table>
	</div>	
	<script>
		var Close$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin3Hide();
		}		

		function Close$t(){
			var XHR = new XHRConnection();
			XHR.appendData('CloseWizard','yes');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',xClose$t);
		}
		
		function Next1$t(){
			LoadAjax('$t','$page?Next1=yes&t=$t');
		}
		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);

}
function CloseWizard(){
	$sock=new sockets();
	
	
}
function MySQLNgnixType_save(){
	$sock=new sockets();
	if(isset($_POST["MySQLNgnixType"])){$sock->SET_INFO("MySQLNgnixType", $_POST["MySQLNgnixType"]);}
	if(isset($_POST["ListenPort"])){
		$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
		$TuningParameters["ListenPort"]=$_POST["ListenPort"];
		$TuningParametersEnc=base64_encode(serialize($TuningParameters));
		$sock->SaveConfigFile($TuningParametersEnc, "MySQLNginxParams");
	}
	if(isset($_POST["MySQLNginxWorkDir"])){$sock->SET_INFO("MySQLNginxWorkDir", $_POST["MySQLNginxWorkDir"]);}
	
	
}

function remote_save(){
	$sock=new sockets();
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
	$_POST["password"]=url_decode_special_tool($_POST["password"]);
	
	if(isset($_POST["RemotePort"])){
		if($_POST["RemotePort"]==389){echo "389 LDAP ?? Are you sure ? Aborting...\n";return;}
		if($_POST["RemotePort"]==25){echo "25 SMTP ?? Are you sure ? Aborting...\n";return;}
		if($_POST["RemotePort"]==465){echo "465 SMTP ?? Are you sure ? Aborting...\n";return;}
		if($_POST["RemotePort"]==80){echo "465 HTTP ?? Are you sure ? Aborting...\n";return;}
		if($_POST["RemotePort"]==443){echo "443 HTTPS ?? Are you sure ? Aborting...\n";return;}
		if($_POST["RemotePort"]==9000){echo "9000 HTTPS/Artica ?? Are you sure ? Aborting...\n";return;}
	}
	
	while (list ($num, $ligne) = each ($_POST) ){
		$TuningParameters[$num]=$ligne;
		
	}
	$TuningParametersEnc=base64_encode(serialize($TuningParameters));
	$sock->SaveConfigFile($TuningParametersEnc, "MySQLNginxParams");	
}


function Next1(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$MySQLNgnixType=$sock->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLNgnixType)){$MySQLNgnixType=1;}
	
	$array[1]="{server}";
	$array[2]="{client}";
	

	$html="<div class=explain style='font-size:14px'>{MYSQLSYSLOG_TYPE_EXPLAIN}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td align='middle'>". Field_array_Hash($array, "MySQLNgnixType-$t",$MySQLNgnixType,null,null,0,"font-size:32px")."</td>
		</tr>
	</table>		
	<p><hr></p>			
	<table style='width:100%'>
	<tr>
		<td align='left' style='width:50%'>". button("{close}","Close$tt()",18)."</td>
		<td align='right' style='width:50%'>". button("{next}","Next$tt()",18)."</td>
	</tr>
	</table>
	</div>	
	<script>
		var xClose$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			YahooWin3Hide();
		}		

		function Close$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('CloseWizard','yes');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',xClose$t);
		}
		
		var xNext$tt= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			LoadAjax('$t','$page?Next2=yes&t=$t');
		}			
		
		function Next$tt(){
			var XHR = new XHRConnection();
			XHR.appendData('MySQLNgnixType',document.getElementById('MySQLNgnixType-$t').value);
			XHR.sendAndLoad('$page', 'POST',xNext$tt);
		}
		
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function Next2(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();

	$sock=new sockets();
	$MySQLNgnixType=$sock->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLNgnixType)){$MySQLNgnixType=1;}
	if($MySQLNgnixType==1){Next2_server();exit;}
	if($MySQLNgnixType==2){Next2_client();exit;}
}

function Next2_client(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$sock=new sockets();
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
	$username=$TuningParameters["username"];
	$password=$TuningParameters["password"];
	$mysqlserver=$TuningParameters["mysqlserver"];
	$ListenPort=$TuningParameters["RemotePort"];
	
	$array[1]="{server}";
	$array[2]="{client}";
	
	
	$html="<div class=explain style='font-size:14px'>{MYSQLSYSLOG_TYPE_CLIENT_EXPLAIN}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{mysqlserver}:</strong></td>
			<td align='left'>" . Field_text("mysqlserver-$tt",$mysqlserver,'width:350px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{remote_port}:</strong></td>
			<td align='left'>" . Field_text("RemotePort-$tt",$ListenPort,'width:90px;padding:3px;font-size:18px',null,null,'')."</td>
		</tr>				
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{username}:</strong></td>
			<td align='left'>" . Field_text("username-$tt",$username,'width:350px;padding:3px;font-size:18px',null,null)."</td>
		</tr>
		<tr>
			<td align='right' nowrap class=legend style='font-size:18px'>{mysqlpass}:</strong></td>
			<td align='left'>" . Field_password("password-$tt",$password,"width:300px;padding:3px;font-size:18px")."</td>
		</tr>
	</table>
	<p><hr></p>
<table style='width:100%'>
	<tr>
		<td align='left' style='width:50%'>". button("{previous}","Close$tt()",18)."</td>
		<td align='right' style='width:50%'>". button("{next}","Next$tt()",18)."</td>
			</tr>
			</table>
			</div>
			<script>
			function Close$tt(){
			LoadAjax('$t','$page?Next1=yes&t=$t');
	}
	
	var xNext$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	LoadAjax('$t','$page?Next3=yes&t=$t');
	}
	
	function Next$tt(){
	var XHR = new XHRConnection();
		XHR.appendData('mysqlserver',document.getElementById('mysqlserver-$tt').value);
		XHR.appendData('RemotePort',document.getElementById('RemotePort-$tt').value);
		XHR.appendData('username',document.getElementById('username-$tt').value);
		XHR.appendData('password',encodeURIComponent(document.getElementById('password-$tt').value));
		XHR.sendAndLoad('$page', 'POST',xNext$tt);
	}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function Next2_server(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();

	$sock=new sockets();
	$MySQLNginxWorkDir=$sock->GET_INFO("MySQLNginxWorkDir");
	if($MySQLNginxWorkDir==null){$MySQLNginxWorkDir="/home/nginxdb";}
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
	$ListenPort=$TuningParameters["ListenPort"];
	if(!is_numeric($ListenPort)){
		$ListenPort=rand(18000, 64000);
	}

	$array[1]="{server}";
	$array[2]="{client}";


	$html="<div class=explain style='font-size:14px'>{MYSQLSYSLOG_TYPE_SERVER_EXPLAIN}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:16px'>{listen_port}:</td>
			<td>". Field_text("ListenPort-$tt",$ListenPort,"font-size:16px;width:90px")."</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{directory}:</td>
			<td>". Field_text("MySQLNginxWorkDir-$tt",$MySQLNginxWorkDir,"font-size:16px;width:350px")."</td>
			<td>". button("{browse}...","Loadjs('browse-disk.php?field=MySQLNginxWorkDir-$tt')",16)."</td>
		</tr>					
	</table>
	<p><hr></p>	
<table style='width:100%'>
	<tr>
		<td align='left' style='width:50%'>". button("{previous}","Close$tt()",18)."</td>
		<td align='right' style='width:50%'>". button("{next}","Next$tt()",18)."</td>
	</tr>
</table>
</div>
<script>
function Close$tt(){
	LoadAjax('$t','$page?Next1=yes&t=$t');	
}

var xNext$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	LoadAjax('$t','$page?Next3=yes&t=$t');
}

function Next$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('ListenPort',document.getElementById('ListenPort-$tt').value);
	XHR.appendData('MySQLNginxWorkDir',document.getElementById('MySQLNginxWorkDir-$tt').value);
	XHR.sendAndLoad('$page', 'POST',xNext$tt);
}

</script>";

echo $tpl->_ENGINE_parse_body($html);

}

function Next3(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$results[]="<div style='width:98%' class=form>
		<table style='width:100%'>";
	$MySQLNgnixType=$sock->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLNgnixType)){$MySQLNgnixType=1;}
	
	$MySQLNginxWorkDir=$sock->GET_INFO("MySQLNginxWorkDir");
	if($MySQLNginxWorkDir==null){$MySQLNginxWorkDir="/home/syslogsdb";}
	$TuningParameters=unserialize(base64_decode($sock->GET_INFO("MySQLNginxParams")));
	$ListenPort=$TuningParameters["ListenPort"];	
	
	
	
	if($MySQLNgnixType==1){
		$results[]="
		<tr>
			<td class=legend style='font-size:16px'>{type}:</td>
			<td style='font-size:16px;font-weight:bold'>{server}</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:16px'>{listen_port}:</td>
			<td style='font-size:16px;font-weight:bold'>$ListenPort</td>
		</tr>
		<tr>
			<td class=legend style='font-size:16px'>{directory}:</td>
			<td style='font-size:16px;font-weight:bold'>$MySQLNginxWorkDir</td>
		</tr>";
		
	}else{
		$username=$TuningParameters["username"];
		$password=$TuningParameters["password"];
		$mysqlserver=$TuningParameters["mysqlserver"];
		$ListenPort=$TuningParameters["RemotePort"];
		
		$results[]="
		<tr>
		<td class=legend style='font-size:16px'>{type}:</td>
		<td style='font-size:16px;font-weight:bold'>{client}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:16px'>{mysqlserver}:</td>
		<td style='font-size:16px;font-weight:bold'>$mysqlserver:$ListenPort</td>
		</tr>
		<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td style='font-size:16px;font-weight:bold'>$username</td>
		</tr>";		
		
	}
	
	$results[]="
	</table>
	<p><hr></p>
	<table style='width:100%'>
	<tr>
		<td align='left' style='width:50%'>". button("{previous}","Close$tt()",18)."</td>
		<td align='right' style='width:50%'>". button("{apply}","Next$tt()",18)."</td>
	</tr>
</table>
<script>
function Close$tt(){
	LoadAjax('$t','$page?Next2=yes&t=$t');	
}

var xNext$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);}
	setTimeout('CacheOff()',5000);
	YahooWin3Hide();
}

function Next$tt(){
	var XHR = new XHRConnection();
	XHR.appendData('apply','yes');
	XHR.sendAndLoad('$page', 'POST',xNext$tt);
}

</script>				
";
	echo $tpl->_ENGINE_parse_body(@implode("", $results));
}

function apply(){
	$sock=new sockets();
	$MySQLNgnixType=$sock->GET_INFO("MySQLNgnixType");
	if(!is_numeric($MySQLNgnixType)){$MySQLNgnixType=1;}
	$sock->SET_INFO("EnableNginxStats", 1);
	if($MySQLNgnixType==1){
		$sock->getFrameWork("nginx.php?mysqldb-restart=yes");
		$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	}
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{mysqlsyslog_finish}",1);
	
}

