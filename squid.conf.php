<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');

	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["DenySquidWriteConf"])){DenySquidWriteConf();exit;}
	if(isset($_POST["SQUID_CONTENT"])){SQUID_CONTENT();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_SQUID}::{configuration_file}");
	
	$html="
		YahooWin4('1200','$page?popup=yes','$title');
	";
	echo $html;
	
	
}


function popup(){
	
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("cmd.php?squid-conf-view=yes"));
	$sock=new sockets();
	$tpl=new templates();
	$DenySquidWriteConf=$sock->GET_INFO("DenySquidWriteConf");
	if(!is_numeric($DenySquidWriteConf)){$DenySquidWriteConf=0;}
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t'></div>
	<div style='width:95%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:14px'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>	
		<td>". Field_checkbox("DenySquidWriteConf", 1,$DenySquidWriteConf,"DenySquidWriteConfSave()")."</td>
	</tr>
	</table>
	
	<textarea 
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>$datas</textarea>
		
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile()",22))."</center>
	</div>
	<script>
		var x_DenySquidWriteConfSave= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}
	
	function DenySquidWriteConfSave(){
		var XHR = new XHRConnection();
		var DenySquidWriteConf=0;
		if(document.getElementById('DenySquidWriteConf').checked){
			DenySquidWriteConf=1;
		}
		XHR.appendData('DenySquidWriteConf', DenySquidWriteConf);
		XHR.sendAndLoad('$page', 'POST',x_DenySquidWriteConfSave);
	}
	
	var x_SaveUserConfFile= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
		}
	
	function SaveUserConfFile(){
		var XHR = new XHRConnection();
		XHR.appendData('SQUID_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile);
	}	
</script>	
	";
echo $html;
	
}
function DenySquidWriteConf(){
	$sock=new sockets();
	$sock->SET_INFO("DenySquidWriteConf", $_POST["DenySquidWriteConf"]);
	
}
function SQUID_CONTENT(){
	$_POST["SQUID_CONTENT"]=url_decode_special_tool($_POST["SQUID_CONTENT"]);
	$content=urlencode(base64_encode($_POST["SQUID_CONTENT"]));
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("squid.php?saveSquidContent=$content"));
	echo $datas;
}

?>