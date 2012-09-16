<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["rebuild-groupoffice"])){rebuild_group_office();exit;}
	if(isset($_POST["ZarafaWebNTLM"])){SaveConf();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$rebuild_groupware_warning=$tpl->javascript_parse_text("{rebuild_groupware_warning}");
	$sock=new sockets();
	$h=new vhosts();
	$hash=$h->listOfAvailableServices(true);
	$sql="SELECT groupware FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
	if($ligne["groupware"]<>null){
		$groupware_text="
		<table style='width:99%' class=form>
		<tr>
			<td width=1% valign='top'><img src='img/{$h->IMG_ARRAY_64[$ligne["groupware"]]}'></td>
			<td valign='top' width=99%>
				<div style='font-size:16px'>{current}:&nbsp;<strong>&laquo;&nbsp;{$hash[$ligne["groupware"]]}&nbsp;&raquo;</strong><hr>
					<i style='font-size:13px'>{{$h->TEXT_ARRAY[$ligne["groupware"]]["TEXT"]}}</i>
				</div>
			</td>
		</tr>
		</table>";
		
	}	
	$ZarafaAspellInstalled_text="({not_installed})";
	$ZarafaAspellInstalled=0;
	
	if($users->ASPELL_INSTALLED){
		$ZarafaAspellInstalled=1;
		$ZarafaAspellInstalled_text="({installed})";
	}		
	
	$free=new freeweb($_GET["servername"]);
	$PARAMS=$free->Params["ZARAFAWEB_PARAMS"];
	if(!isset($PARAMS["ZarafaWebNTLM"])){$PARAMS["ZarafaWebNTLM"]=$sock->GET_INFO("ZarafaWebNTLM");}
	if(!isset($PARAMS["ZarafaEnablePlugins"])){$PARAMS["ZarafaEnablePlugins"]=$sock->GET_INFO("ZarafaEnablePlugins");}
	if(!isset($PARAMS["ZarafaAspellEnabled"])){$PARAMS["ZarafaAspellEnabled"]=$sock->GET_INFO("ZarafaAspellEnabled");}
	if(!isset($PARAMS["EnableZarafaRemoteServer"])){$PARAMS["EnableZarafaRemoteServer"]=0;}
	if(!isset($PARAMS["EnableDebugMode"])){$PARAMS["EnableDebugMode"]=0;}
	
	
	
	$EnableDebugMode=$PARAMS["EnableDebugMode"];
	$ZarafaWebNTLM=$PARAMS["ZarafaWebNTLM"];
	$ZarafaEnablePlugins=$PARAMS["ZarafaEnablePlugins"];
	$ZarafaAspellEnabled=$PARAMS["ZarafaAspellEnabled"];
	$EnableZarafaRemoteServer=$PARAMS["EnableZarafaRemoteServer"];
	$EnableZarafaRemoteServerAddr=$PARAMS["EnableZarafaRemoteServerAddr"];
	$ZarafaEnablePluginPassword=$PARAMS["ZarafaEnablePluginPassword"];
	$post_max_size=$PARAMS["post_max_size"];
	$upload_max_filesize=$PARAMS["upload_max_filesize"];
	$zPushInside=$PARAMS["zPushInside"];
	
	
	
	if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
	if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
	if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
	if(!is_numeric($EnableZarafaRemoteServer)){$EnableZarafaRemoteServer=0;}
	if(!is_numeric($EnableDebugMode)){$EnableDebugMode=0;}
	if(!is_numeric($post_max_size)){$post_max_size=50;}
	if(!is_numeric($upload_max_filesize)){$upload_max_filesize=50;}
	if(!is_numeric($ZarafaEnablePluginPassword)){$ZarafaEnablePluginPassword=0;}
	if(!is_numeric($zPushInside)){$zPushInside=1;}
	
	$zPushInstalled=0;
	if($users->Z_PUSH_INSTALLED){$zPushInstalled=1;}
	
	
	$sock=new sockets();
		
$html="<div style='width:100%' id='$t'>$groupware_text

<table style='width:99%' class=form>
<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{EnableZpush}:</td>
		<td>". Field_checkbox("zPushInside",1,$zPushInside)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{debug_mode}:</td>
		<td>". Field_checkbox("EnableDebugMode",1,$EnableDebugMode)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaWebNTLM}:<div style='font-size:11px'>{ZarafaWebNTLM_explain}</div></td>
		<td>". Field_checkbox("ZarafaWebNTLM",1,$ZarafaWebNTLM)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaEnablePlugins}:</td>
		<td>". Field_checkbox("ZarafaEnablePlugins",1,$ZarafaEnablePlugins)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{spell_checker}&nbsp;$ZarafaAspellInstalled_text&nbsp;:</td>
		<td>". Field_checkbox("ZarafaAspellEnabled",1,$ZarafaAspellEnabled)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{UseRemoteServer}:</td>
		<td>". Field_checkbox("EnableZarafaRemoteServer",1,$EnableZarafaRemoteServer,"EnableZarafaRemoteServerCheck()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{remote_server}:</td>
		<td>". Field_text("EnableZarafaRemoteServerAddr",$EnableZarafaRemoteServerAddr,"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{post_max_size}:</td>
		<td style='font-size:14px'>". Field_text("post_max_size",$post_max_size,"font-size:14px;width:60px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{upload_max_filesize}:</td>
		<td style='font-size:14px'>". Field_text("upload_max_filesize",$upload_max_filesize,"font-size:14px;width:60px")."&nbsp;M</td>
	</tr>
	
											
</table>

<div style='text-align:right'><hr>". button("{apply}","SaveZarafaWebFree()",18)."</div>



</div>
	<script>
		var x_SaveZarafaWebFree=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}

			if(document.getElementById('main_config_freewebedit')){
				RefreshTab('main_config_freewebedit');
			}
			
		}	

		function EnableZarafaRemoteServerCheck(){
			var zPushInstalled=$zPushInstalled;
			
			document.getElementById('zPushInside').disabled=true;
			document.getElementById('EnableZarafaRemoteServerAddr').disabled=true;
			if(document.getElementById('EnableZarafaRemoteServer').checked){
				document.getElementById('EnableZarafaRemoteServerAddr').disabled=false;
			}
			
			if(zPushInstalled==1){
				document.getElementById('zPushInside').disabled=false;
			}
		
		}
		
	
	
		function SaveZarafaWebFree(){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');
			if(document.getElementById('EnableDebugMode').checked){XHR.appendData('EnableDebugMode',1);}else{XHR.appendData('EnableDebugMode',0);}
			if(document.getElementById('EnableZarafaRemoteServer').checked){XHR.appendData('EnableZarafaRemoteServer',1);}else{XHR.appendData('EnableZarafaRemoteServer',0);}
			if(document.getElementById('ZarafaWebNTLM').checked){XHR.appendData('ZarafaWebNTLM',1);}else{XHR.appendData('ZarafaWebNTLM',0);}
			if(document.getElementById('ZarafaEnablePlugins').checked){XHR.appendData('ZarafaEnablePlugins',1);}else{XHR.appendData('ZarafaEnablePlugins',0);}
			if(document.getElementById('ZarafaAspellEnabled').checked){XHR.appendData('ZarafaAspellEnabled',1);}else{XHR.appendData('ZarafaAspellEnabled',0);}
			if(document.getElementById('zPushInside').checked){XHR.appendData('zPushInside',1);}else{XHR.appendData('zPushInside',0);}
			XHR.appendData('EnableZarafaRemoteServerAddr',document.getElementById('EnableZarafaRemoteServerAddr').value);
			XHR.appendData('post_max_size',document.getElementById('post_max_size').value);
			XHR.appendData('upload_max_filesize',document.getElementById('upload_max_filesize').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveZarafaWebFree);
		}
	
		EnableZarafaRemoteServerCheck();
	</script>
	
	";

	$tpl=new templates();
	$datas=$tpl->_ENGINE_parse_body($html);	
	echo $datas;	
	
	
	
}

function SaveConf(){
	$free=new freeweb($_POST["servername"]);
	$free->Params["ZARAFAWEB_PARAMS"]=$_POST;
	$free->SaveParams();
	
	$q=new mysql();
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='REBUILD_GROUPWARE' AND `servername`='{$_POST["servername"]}'";
	$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('REBUILD_GROUPWARE','{$_POST["servername"]}')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");			
	
	
}


function rebuild_group_office(){
	$q=new mysql();
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='REBUILD_GROUPWARE' AND `servername`='{$_POST["servername"]}'";
	$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('REBUILD_GROUPWARE','{$_POST["servername"]}')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");		
}
