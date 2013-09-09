<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');

	
	
	$user=new usersMenus();
	if($user->AsMailBoxAdministrator==false){
		$tpl=new templates();
		header("content-type: application/x-javascript");
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["DenyFetchMailWriteConf"])){DenyFetchMailWriteConf();exit;}
	if(isset($_POST["FETCHMAIL_CONTENT"])){FETCHMAIL_CONTENT();exit;}
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_FETCHMAIL}::{configuration_file}");
	
	$html="
		YahooWin4('1200','$page?popup=yes','$title');
	";
	echo $html;
	
	
}


function popup(){
	
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("fetchmail.php?fetchmailrc=yes"));
	$sock=new sockets();
	$tpl=new templates();
	$DenyFetchMailWriteConf=$sock->GET_INFO("DenyFetchMailWriteConf");
	if(!is_numeric($DenyFetchMailWriteConf)){$DenyFetchMailWriteConf=0;}
	$t=time();
	$page=CurrentPageName();
	$html="
	<div id='$t'></div>
	<div style='width:95%' class=form>
	<table>
	<tr>
		<td class=legend style='font-size:14px'>". $tpl->_ENGINE_parse_body("{deny_artica_to_write_config}")."</td>	
		<td>". Field_checkbox("DenyFetchMailWriteConf", 1,$DenyFetchMailWriteConf,"DenyFetchMailWriteConfSave()")."</td>
	</tr>
	</table>
	
	<textarea 
		style='width:95%;height:550px;overflow:auto;border:5px solid #CCCCCC;font-size:14px;font-weight:bold;padding:3px'
		id='SQUID_CONTENT-$t'>$datas</textarea>
		
	<center><hr>". $tpl->_ENGINE_parse_body(button("{apply}", "SaveUserConfFile$t()",22))."</center>
	</div>
	<script>
		var x_DenySquidWriteConfSave$t= function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);return;}
		}
	
	function DenyFetchMailWriteConfSave(){
		var XHR = new XHRConnection();
		var DenyFetchMailWriteConf=0;
		if(document.getElementById('DenyFetchMailWriteConf').checked){
			DenyFetchMailWriteConf=1;
		}
		XHR.appendData('DenyFetchMailWriteConf', DenyFetchMailWriteConf);
		XHR.sendAndLoad('$page', 'POST',x_DenySquidWriteConfSave$t);
	}
	
	var x_SaveUserConfFile$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('$t').innerHTML='';
			if(results.length>3){alert(results);return;}
		}
	
	function SaveUserConfFile$t(){
		var XHR = new XHRConnection();
		XHR.appendData('FETCHMAIL_CONTENT', encodeURIComponent(document.getElementById('SQUID_CONTENT-$t').value));
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveUserConfFile$t);
	}	
</script>	
	";
echo $html;
	
}
function DenyFetchMailWriteConf(){
	$sock=new sockets();
	$sock->SET_INFO("DenyFetchMailWriteConf", $_POST["DenyFetchMailWriteConf"]);
	
}
function FETCHMAIL_CONTENT(){
	$_POST["FETCHMAIL_CONTENT"]=url_decode_special_tool($_POST["FETCHMAIL_CONTENT"]);
	$content=urlencode(base64_encode($_POST["FETCHMAIL_CONTENT"]));
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("fetchmail.php?SaveFetchmailContent=$content"));
	
}

?>