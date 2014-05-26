<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.amavis.inc');
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["max_servers"])){max_servers_save();exit;}
	if(isset($_GET["processes-list"])){status_table_list();exit;}
	if(isset($_POST["enable-amavis"])){enable_amavis();exit;}
	if(isset($_POST["disable-amavis"])){disable_amavis();exit;}
	if(isset($_GET["processes-popup"])){processes_popup();exit;}
	
	
status_table();	




function status_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cpu=$tpl->_ENGINE_parse_body("{cpu}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$context=$tpl->_ENGINE_parse_body("{context}");	
	$events=$tpl->_ENGINE_parse_body("{events}");	
	$processes=$tpl->_ENGINE_parse_body("{processes}");	
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");	
	$sock=new sockets();
	$jabberdhostname=$tpl->_ENGINE_parse_body("{jabberdhostname}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$add_default_www=$tpl->_ENGINE_parse_body("{add_default_www}");
	$delete_freeweb_text=$tpl->javascript_parse_text("{delete_freeweb_text}");
	$delete_freeweb_dnstext=$tpl->javascript_parse_text("{delete_freeweb_dnstext}");	
	$WebDavPerUser=$tpl->_ENGINE_parse_body("{WebDavPerUser}");
	$disabled=$tpl->_ENGINE_parse_body("{disabled}!");
	$help=$tpl->_ENGINE_parse_body("{help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$watchdog_parameters=$tpl->_ENGINE_parse_body("{watchdog_parameters}");
	$bt_default_www="{name: '$add_default_www', bclass: 'add', onpress : FreeWebAddDefaultVirtualHost},";
	$bt_webdav="{name: '$WebDavPerUser', bclass: 'add', onpress : FreeWebWebDavPerUsers},";
	//$bt_rebuild="{name: '$rebuild_items', bclass: 'Reconf', onpress : RebuildFreeweb},";
	$enable_amavisdeamon_ask=$tpl->javascript_parse_text("{enable_amavisdeamon_ask}");		
	$disable_amavisdeamon_ask=$tpl->javascript_parse_text("{disable_amavisdeamon_ask}");
	$EnableAmavisDaemon=trim($sock->GET_INFO("EnableAmavisDaemon",true));	
	if(!is_numeric($EnableAmavisDaemon)){$EnableAmavisDaemon=0;}	
	if($EnableAmavisDaemon<>1){
		$bt_enable="{name: '<strong style=color:#D20404>$disabled</strong>', bclass: 'Warn', onpress : EnablePopupAmavis},";	
	}else{
		$bt_enable="{name: '$enabled', bclass: 'Statok', onpress : DisablePopupAmavis},";	
		
	}
	
	$tablewidth=874;
	$servername_size=409;
	$bt_function_add="AddNewejabberServer";
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$processes</b>', bclass: 'Reconf', onpress : processes_form},
	{name: '<b>$watchdog_parameters</b>', bclass: 'Reconf', onpress : watchdog_parameters},$bt_enable
	],";
	
	$html="
	<div id='div$t'>
	
	<table class='jabberd-table-$t' style='display: none' id='jabberd-table-$t' style='width:100%;margin:-10px'></table>
	</center>
	</div>
<script>
FreeWebIDMEM='';
$('#jabberd-table-$t').flexigrid({
	url: '$page?processes-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		
		{display: '$cpu', name : 'cpu', width : 105, sortable : false, align: 'center'},
		{display: '-   ', name : 'none2', width :105, sortable : false, align: 'center'},
		{display: '$type', name : 'enabled', width :105, sortable : false, align: 'center'},
		{display: '-    ', name : 'none3', width :105, sortable : false, align: 'center'},
		{display: 'RSS  ', name : 'uid', width : 105, sortable : false, align: 'center'},
		{display: 'VMSIZE', name : 'none4', width : 105, sortable : false, align: 'center'},
	],
	$buttons
					

	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});   


function processes_form(){
	YahooWin3('550','$page?processes-popup=yes','$processes');
}

function watchdog_parameters(){
	Loadjs('amavis.daemon.watchdog.php')
}

	var x_EnablePopupAmavis= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_amavis');
	}	

function EnablePopupAmavis(){
	if(confirm('$enable_amavisdeamon_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('enable-amavis','yes');
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_EnablePopupAmavis);
	}
}

function DisablePopupAmavis(){
	if(confirm('$disable_amavisdeamon_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('disable-amavis','yes');
		AnimateDiv('div$t');
		XHR.sendAndLoad('$page', 'POST',x_EnablePopupAmavis);
	}
}

</script>";	
	
	
echo $html;	
	
}

function status_table_list(){
$amavis=new amavis();
	$max_servers=$amavis->main_array["BEHAVIORS"]["max_servers"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!is_file("ressources/logs/amavis.infos.array")){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?amavis-watchdog=yes");
	}
	$datas=unserialize(@file_get_contents("ressources/logs/amavis.infos.array"));	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
$childs=0;
$c=0;
		while (list ($pid, $array) = each ($datas)){
			$c++;
			$TYPE=$array["TYPE"];
			$CPU=$array["CPU"];
			$TIME=$array["TIME"];
			$RSS=$array["RSS"];
			$VMSIZE=$array["VMSIZE"];
			$color="#5DD13D";
			if($CPU>75){$color="#F59C44";}
			if($CPU>85){$color="#D32D2D";}			
			$childs_text='-';
			if($TYPE<>"master"){if($TYPE<>"virgin"){if($TYPE<>"virgin child"){$childs++;$childs_text=$childs;}}}
			$pourc=pourcentage($CPU);
			$TOT_RSS=$TOT_RSS+$RSS;
			$TOT_VMSIZE=$TOT_VMSIZE+$VMSIZE;
			$pourc;
			
			$data['rows'][] = array(
							'id' => $c,
							'cell' => array(
								
								"<span style='font-size:18px'>$CPU%</span>",
								"<span style='font-size:18px'>$childs_text</span>",
								"<span style='font-size:18px'>$TYPE</span>",
								"<span style='font-size:18px'>{$TIME}Mn</span>",
								"<span style='font-size:18px'>{$RSS}Mb</span>",
								"<span style='font-size:18px'>{$VMSIZE}Mb</span>",
								)
							);					
		}

		$data['total'] = $c;
		echo json_encode($data);
	
}

function processes_popup(){
	$amavis=new amavis();
	$max_servers=$amavis->main_array["BEHAVIORS"]["max_servers"];
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=time();
	
	
		if(preg_match("#([0-9]+)\*([0-9]+)#",$amavis->main_array["BEHAVIORS"]["child_timeout"],$re)){
			$seconds=intval($re[2]);
			$int=intval($re[1]);
			$total_seconds=round($int*$seconds)/60;
		}
		
		for($i=1;$i<60;$i++){
			if($i<10){$t="0$i";}else{$t=$i;}
			$mins[$i]=$t;
		}	
	
	$html="	
	<div class=explain style='font-size:14px'>{amavis_max_server_explain}</div>
	<div id='$t'>
	<table style='width:100%' class=form>
			<tr>
				<td class=legend style='font-size:16px'>{processes}:</td>
				<td>". Field_text("max_servers",$max_servers,"font-size:16px;padding:3px;border:3px solid #717171;width:60px;")."</td>
			</tr>
			<tr>
				<td class=legend nowrap style='font-size:16px'>{child_ttl}:</td>
				<td style='font-size:16px;padding:3px'>". Field_array_Hash($mins,"child_timeout",$total_seconds,"style:font-size:16px;padding:3px;")."&nbsp;Mn</td>
			</tr>	
			<tr><td colspan=2 align='right'><i style='font-size:16px'>{$amavis->main_array["BEHAVIORS"]["child_timeout"]} = {$total_seconds}Mn</i></td></tr>
			<tr>
				<td colspan=2 align=right><hr>". button("{apply}","SaveMaxProcesses()",18)."</td>
			</tr>
			</table>
		</div>
	<script>
	var x_SaveMaxProcesses= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		YahooWin3Hide();
	}		
	
	function SaveMaxProcesses(){
		var XHR = new XHRConnection();
		XHR.appendData('max_servers',document.getElementById('max_servers').value);
		XHR.appendData('child_timeout',document.getElementById('child_timeout').value);		
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'GET',x_SaveMaxProcesses);
		}	

	</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


	
function status(){
	
	$amavis=new amavis();
	$max_servers=$amavis->main_array["BEHAVIORS"]["max_servers"];
	$tpl=new templates();
	$page=CurrentPageName();
	
	if(!is_file("ressources/logs/amavis.infos.array")){
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?amavis-watchdog=yes");
	}
	
	
	$table="<table style='width:250px'>
	<th colspan=2>{cpu}</th>
	<th>&nbsp;</th>
	<th>{type}</th>
	<th>&nbsp;</th>
	<th>RSS</th>
	<th>VMSIZE</th>
	</tr>
	
	";
	
	$datas=unserialize(@file_get_contents("ressources/logs/amavis.infos.array"));
	
	
	$childs=0;
		while (list ($pid, $array) = each ($datas)){
			$TYPE=$array["TYPE"];
			$CPU=$array["CPU"];
			$TIME=$array["TIME"];
			$RSS=$array["RSS"];
			$VMSIZE=$array["VMSIZE"];
			$color="#5DD13D";
			if($CPU>75){$color="#F59C44";}
			if($CPU>85){$color="#D32D2D";}			
			$childs_text='-';
			if($TYPE<>"master"){if($TYPE<>"virgin"){if($TYPE<>"virgin child"){$childs++;$childs_text=$childs;}}}
			$pourc=pourcentage_basic($CPU,$color,"&nbsp;$CPU%");
			$TOT_RSS=$TOT_RSS+$RSS;
			$TOT_VMSIZE=$TOT_VMSIZE+$VMSIZE;
		$table=$table."
		<tr>
			<td>$pourc</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>$CPU%</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>$childs_text</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>$TYPE</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>{$TIME}Mn</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>{$RSS}Mb</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>{$VMSIZE}Mb</td>
		</tr>
		
		";
			
			
			
		}
		

		

			
	$table=$table."
		<tr>
			<td>&nbsp;</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>&nbsp;</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>&nbsp;</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>&nbsp;</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>&nbsp;</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>{$TOT_RSS}Mb</td>
			<td width=1% nowrap style='font-size:11px;font-weight:bold'>{$TOT_VMSIZE}Mb</td>
		</tr>	
	
	</table>";	

	$html="<table style='width:100%'>
	<tr>
	<td valign='top'>$table</td>
		<td valign='top' align='left' width=99%>
			<div style='font-size:14px;font-weight:bold;margin-bottom:10px'>{processes}:$childs/$max_servers {used}</div>

			<br>
			<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_config_amavis');")."</div>
			<br>
			<div class=explain id='mmmdiv'>{}</div>
			
	</tr>
	</table>
	

	
	
	";
	
			
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}	

function disable_amavis(){
	$sock=new sockets();
	$sock->SET_INFO("EnableAmavisDaemon", 0);
	$sock->getFrameWork("cmd.php?amavis-restart=yes");
	$sock->getFrameWork("cmd.php?RestartDaemon=yes");
	$sock->getFrameWork("cmd.php?postfix-ssl=yes");
	$sock->getFrameWork("cmd.php?artica-filter-reload=yes");		
	
}

function max_servers_save(){
	$amavis=new amavis();
	$amavis->main_array["BEHAVIORS"]["max_servers"]=$_GET["max_servers"];
	$amavis->main_array["BEHAVIORS"]["child_timeout"]="{$_GET["child_timeout"]}*60";
	$amavis->Save();
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-ssl=yes");
	
}
function enable_amavis(){
	$sock=new sockets();
	$sock->SET_INFO("EnableAmavisDaemon", 1);
	$sock->getFrameWork("cmd.php?amavis-restart=yes");
	$sock->getFrameWork("cmd.php?RestartDaemon=yes");
	$sock->getFrameWork("cmd.php?postfix-ssl=yes");
	$sock->getFrameWork("cmd.php?artica-filter-reload=yes");
}