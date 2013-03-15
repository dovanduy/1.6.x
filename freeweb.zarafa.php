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
	$t=$_GET["t"];
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
	$EnableZarafaSecondInstance=$PARAMS["EnableZarafaSecondInstance"];
	$post_max_size=$PARAMS["post_max_size"];
	$upload_max_filesize=$PARAMS["upload_max_filesize"];
	$zPushInside=$PARAMS["zPushInside"];
	$ZarafaXMPPDomain=$PARAMS["ZarafaXMPPDomain"];
	$AutoDiscoverUri=$PARAMS["AutoDiscoverUri"];
	$t=time();
	if(!is_numeric($ZarafaWebNTLM)){$ZarafaWebNTLM=0;}
	if(!is_numeric($ZarafaEnablePlugins)){$ZarafaEnablePlugins=0;}
	if(!is_numeric($ZarafaAspellEnabled)){$ZarafaAspellEnabled=0;}
	if(!is_numeric($EnableZarafaRemoteServer)){$EnableZarafaRemoteServer=0;}
	if(!is_numeric($EnableDebugMode)){$EnableDebugMode=0;}
	if(!is_numeric($post_max_size)){$post_max_size=50;}
	if(!is_numeric($upload_max_filesize)){$upload_max_filesize=50;}
	if(!is_numeric($ZarafaEnablePluginPassword)){$ZarafaEnablePluginPassword=0;}
	if(!is_numeric($zPushInside)){$zPushInside=1;}
	if(!is_numeric($EnableZarafaSecondInstance)){$EnableZarafaSecondInstance=0;}
	$zPushInstalled=0;
	if(!is_numeric($t)){$t=time();}
	if($users->Z_PUSH_INSTALLED){$zPushInstalled=1;}
	
	if($users->EJABBERD_INSTALLED){
		$ejabberdEnabled=$sock->GET_INFO("ejabberdEnabled");
		$ejabberdInsideZarafa=$sock->GET_INFO("ejabberdInsideZarafa");
		if(!is_numeric($ejabberdEnabled)){$ejabberdEnabled=1;}
		if(!is_numeric($ejabberdInsideZarafa)){$ejabberdInsideZarafa=0;}	
		if($ejabberdEnabled==1){
			if($ejabberdInsideZarafa==1){
				$sql="SELECT hostname FROM ejabberd WHERE enabled=1";
				$results = $q->QUERY_SQL($sql,"artica_backup");
				$TT[null]="{select}";
				while ($ligne = mysql_fetch_assoc($results)) {
					$TT[$ligne["hostname"]]=$ligne["hostname"];
				}
				
				$ejjaberd_field=Field_array_Hash($TT, "ZarafaXMPPDomain-$t",$ZarafaXMPPDomain,null,null,0,"font-size:14px");
				$ejjaberd_row="
					<tr>
						<td class=legend style='font-size:14px'>{InstantMessagingDomain}:</td>
						<td>$ejjaberd_field</td>
					</tr>		
				";
			}
		}
		
		
	}
	
	
	$sock=new sockets();
	$ZarafaDBEnable2Instance=$sock->GET_INFO("ZarafaDBEnable2Instance");
	if(!is_numeric($ZarafaDBEnable2Instance)){$ZarafaDBEnable2Instance=0;}
		
$html="<div style='width:100%' id='$t'></div>

$groupware_text

<table style='width:99%' class=form>
<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{EnableZpush}:</td>
		<td>". Field_checkbox("zPushInside-$t",1,$zPushInside,"zPushInsideCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px' valign='top'>{AutoDiscover_webserver}:</td>
		<td>". Field_text("AutoDiscoverUri-$t",$AutoDiscoverUri,"font-size:14px;width:98%;font-weight:bold")."
		<div style='text-align:right'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:s_PopUpFull('http://mail-appliance.org/index.php?cID=387','1024','900');\"
				style='font-size:11px;text-decoration:underline'>&laquo;{online_help}&raquo;</a></div>	
		</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:14px'>{debug_mode}:</td>
		<td>". Field_checkbox("EnableDebugMode-$t",1,$EnableDebugMode)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaWebNTLM}:<div style='font-size:11px'>{ZarafaWebNTLM_explain}</div></td>
		<td>". Field_checkbox("ZarafaWebNTLM-$t",1,$ZarafaWebNTLM)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{ZarafaEnablePlugins}:</td>
		<td>". Field_checkbox("ZarafaEnablePlugins-$t",1,$ZarafaEnablePlugins)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{spell_checker}&nbsp;$ZarafaAspellInstalled_text&nbsp;:</td>
		<td>". Field_checkbox("ZarafaAspellEnabled-$t",1,$ZarafaAspellEnabled)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{UseRemoteServer}:</td>
		<td>". Field_checkbox("EnableZarafaRemoteServer-$t",1,$EnableZarafaRemoteServer,"EnableZarafaRemoteServerCheck$t()")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{remote_server}:</td>
		<td>". Field_text("EnableZarafaRemoteServerAddr-$t",$EnableZarafaRemoteServerAddr,"font-size:14px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{use_second_instance}:</td>
		<td>". Field_checkbox("EnableZarafaSecondInstance-$t",1,$EnableZarafaSecondInstance,"ZarafaDBEnable2InstanceCheck$t()")."</td>
	</tr>	
	$ejjaberd_row
	<tr>
		<td class=legend style='font-size:14px'>{post_max_size}:</td>
		<td style='font-size:14px'>". Field_text("post_max_size-$t",$post_max_size,"font-size:14px;width:60px")."&nbsp;M</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{upload_max_filesize}:</td>
		<td style='font-size:14px'>". Field_text("upload_max_filesize-$t",$upload_max_filesize,"font-size:14px;width:60px")."&nbsp;M</td>
	</tr>
	
											
</table>

<div style='text-align:right'><hr>". button("{apply}","SaveZarafaWebFree$t()",18)."</div>




	<script>
		var x_SaveZarafaWebFree$t=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			document.getElementById('$t').innerHTML='';
		}	

		function EnableZarafaRemoteServerCheck$t(){
			var zPushInstalled=$zPushInstalled;
			
			document.getElementById('zPushInside-$t').disabled=true;
			document.getElementById('EnableZarafaRemoteServerAddr-$t').disabled=true;
			if(!document.getElementById('EnableZarafaRemoteServer-$t').disabled){
				if(document.getElementById('EnableZarafaRemoteServer-$t').checked){
					document.getElementById('EnableZarafaRemoteServerAddr-$t').disabled=false;
				}
			}
			if(zPushInstalled==1){
				document.getElementById('zPushInside-$t').disabled=false;
			}
		
		}
		
		
		function zPushInsideCheck$t(){
			document.getElementById('AutoDiscoverUri-$t').disabled=true;
			if(document.getElementById('zPushInside-$t').checked){
				document.getElementById('AutoDiscoverUri-$t').disabled=false;
			}
		
		}
		
		function ZarafaDBEnable2InstanceCheck$t(){
			var ZarafaDBEnable2Instance=$ZarafaDBEnable2Instance;
			document.getElementById('EnableZarafaSecondInstance-$t').disabled=true;
			document.getElementById('EnableZarafaRemoteServer-$t').disabled=true;
			
			if(ZarafaDBEnable2Instance==0){
				document.getElementById('EnableZarafaRemoteServer-$t').disabled=false;
				return;
			}
			document.getElementById('EnableZarafaSecondInstance-$t').disabled=false;
			if(document.getElementById('EnableZarafaSecondInstance-$t').checked){
				document.getElementById('EnableZarafaRemoteServer-$t').disabled=true;			
			}else{
				document.getElementById('EnableZarafaRemoteServer-$t').disabled=false;	
			
			}
			
			EnableZarafaRemoteServerCheck$t();
			
		}
		
	
	
		function SaveZarafaWebFree$t(){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');
			ZarafaXMPPDomain='';
			if(document.getElementById('EnableDebugMode-$t').checked){XHR.appendData('EnableDebugMode',1);}else{XHR.appendData('EnableDebugMode',0);}
			if(document.getElementById('EnableZarafaRemoteServer-$t').checked){XHR.appendData('EnableZarafaRemoteServer',1);}else{XHR.appendData('EnableZarafaRemoteServer',0);}
			if(document.getElementById('ZarafaWebNTLM-$t').checked){XHR.appendData('ZarafaWebNTLM',1);}else{XHR.appendData('ZarafaWebNTLM',0);}
			if(document.getElementById('ZarafaEnablePlugins-$t').checked){XHR.appendData('ZarafaEnablePlugins',1);}else{XHR.appendData('ZarafaEnablePlugins',0);}
			if(document.getElementById('ZarafaAspellEnabled-$t').checked){XHR.appendData('ZarafaAspellEnabled',1);}else{XHR.appendData('ZarafaAspellEnabled',0);}
			if(document.getElementById('zPushInside-$t').checked){XHR.appendData('zPushInside',1);}else{XHR.appendData('zPushInside',0);}
			if(document.getElementById('EnableZarafaSecondInstance-$t').checked){XHR.appendData('EnableZarafaSecondInstance',1);}else{XHR.appendData('EnableZarafaSecondInstance',0);}
			
			XHR.appendData('EnableZarafaRemoteServerAddr',document.getElementById('EnableZarafaRemoteServerAddr-$t').value);
			if(document.getElementById('ZarafaXMPPDomain')){ZarafaXMPPDomain=document.getElementById('ZarafaXMPPDomain-$t').value;}
			XHR.appendData('ZarafaXMPPDomain',ZarafaXMPPDomain);
			XHR.appendData('post_max_size',document.getElementById('post_max_size-$t').value);
			XHR.appendData('AutoDiscoverUri',document.getElementById('AutoDiscoverUri-$t').value);
			XHR.appendData('upload_max_filesize',document.getElementById('upload_max_filesize-$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveZarafaWebFree$t);
		}
		ZarafaDBEnable2InstanceCheck$t();
		
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
