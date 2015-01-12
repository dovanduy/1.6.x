<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";die();

}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["ArticaMetaHost"])){Save();exit;}

js();

function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->javascript_parse_text("{new_server}");
	echo "YahooWin3('700','$page?popup=yes','$title',true)";	
	
	
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ArticaMetaAddNewServ=unserialize($sock->GET_INFO("ArticaMetaAddNewServ"));
	$ArticaMetaHost=$ArticaMetaAddNewServ["ArticaMetaHost"];
	$ArticaMetaPort=$ArticaMetaAddNewServ["ArticaMetaPort"];
	$ArticaMetaUsername=$ArticaMetaAddNewServ["ArticaMetaUsername"];
	$ArticaMetaPassword=$ArticaMetaAddNewServ["ArticaMetaPassword"];
	$ArticaMetaServHost=$ArticaMetaAddNewServ["ArticaMetaServHost"];
	$ArticaMetaServPort=$ArticaMetaAddNewServ["ArticaMetaServPort"];
	$change_uuid=$ArticaMetaAddNewServ["change_uuid"];
	$t=time();
	
	if(!is_numeric($ArticaMetaPort)){$ArticaMetaPort=9000;}
	if(!is_numeric($ArticaMetaServPort)){$ArticaMetaServPort=9000;}
	if($ArticaMetaServHost==null){$ArticaMetaServHost=$_SERVER["SERVER_NAME"];}
	if($ArticaMetaUsername==null){$ArticaMetaUsername="Manager";}
	
	$html="	<div style='width:98%' class=form>
		<table style='width:100%'>
					
		<tr>
			<td class=legend style='font-size:18px'>{hostname} (client):</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaHost-$t",$ArticaMetaHost,"font-size:18px;width:240px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{change_uuid}:</td>
			<td style='font-size:18px'>". Field_checkbox("change_uuid-$t", 1,$change_uuid)."</td>
		</tr>							
		<tr>
			<td class=legend style='font-size:18px'>{port}:</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaPort-$t",$ArticaMetaPort,"font-size:18px;width:110px")."</td>
		</tr>				
			
		<tr>
			<td class=legend style='font-size:18px'>{username}:</td>
			<td style='font-size:18px'>". Field_text("username-$t",$ArticaMetaUsername,"font-size:18px;width:240px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{password}:</td>
			<td style='font-size:18px'>". Field_password("password-$t",$ArticaMetaPassword,"font-size:18px;width:240px")."</td>
		</tr>
		<tr><td colspan=2><hr></td></tR>
		<tr>
			<td class=legend style='font-size:18px'>{hostname} ({server_mode}):</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaServHost-$t",$ArticaMetaServHost,"font-size:18px;width:240px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{port} ({server_mode}):</td>
			<td style='font-size:18px'>". Field_text("ArticaMetaServPort-$t",$ArticaMetaServPort,"font-size:18px;width:110px")."</td>
		</tr>		
		

		<tr>
			<td colspan=2 align='right'><hr>". button("{add}","Save$t()",24)."</td>
		</tr>
		</table>
				<script>
	
	var xSave$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		Loadjs('artica-meta.NewServ.progress.php');
	}
	
	
	function Save$t(){
	var XHR = new XHRConnection();
	change_uuid=0;
	if(document.getElementById('change_uuid-$t').checked){change_uuid=1;}
	XHR.appendData('ArticaMetaHost',document.getElementById('ArticaMetaHost-$t').value);
	XHR.appendData('ArticaMetaPort',document.getElementById('ArticaMetaPort-$t').value);
	XHR.appendData('ArticaMetaServHost',document.getElementById('ArticaMetaServHost-$t').value);
	XHR.appendData('ArticaMetaServPort',document.getElementById('ArticaMetaServPort-$t').value);
	
	XHR.appendData('change_uuid',change_uuid);
	
	XHR.appendData('ArticaMetaUsername',document.getElementById('username-$t').value);
	XHR.appendData('ArticaMetaPassword',encodeURIComponent(document.getElementById('password-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	$_POST["ArticaMetaPassword"]=url_decode_special_tool($_POST["ArticaMetaPassword"]);
	$sock=new sockets();
	$sock->SaveConfigFile(serialize($_POST), "ArticaMetaAddNewServ");
	
	
}


