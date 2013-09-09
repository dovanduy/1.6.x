<?php
	if(isset($_GET["verbose"])){
			echo "<H1>VERBOSED</H1>\n";
			$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			ini_set('error_prepend_string',null);
			ini_set('error_append_string',null);
	}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){die("NO PRIVS");}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["SquidEnableRockStore"])){SquidEnableRockStore_save();exit;}
js();	
function js(){
	header("content-type: application/x-javascript");
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	echo "YahooWin3Hide(); YahooWin3('650','$page?popup=yes','Rock Store')";
	
}	

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$DisableSquidSNMPMode=$sock->GET_INFO("DisableSquidSNMPMode");
	if(!is_numeric($DisableSquidSNMPMode)){$DisableSquidSNMPMode=1;}
	if($DisableSquidSNMPMode==1){
		echo FATAL_ERROR_SHOW_128("{error_squid_snmp_not_enabled}");
		return; 
	}
	
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	
	if($DisableAnyCache==1){
		$html=FATAL_ERROR_SHOW_128("{all_cache_method_are_globally_disabled}");
		$html=$html."
		<div style='width:100%'>
				<div style='margin-top:10px;width:99%' class=form>
					<a href=\"javascript:blur();\" OnClick=\"Loadjs('squid.caches.disable.php')\"
					style='font-size:16px;text-decoration:underline;font-weight:bold'
					>{access_to_parameters}</a>
				</div>
		</div>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}	
	
	$SquidEnableRockStore=$sock->GET_INFO("SquidEnableRockStore");
	$SquidRockStoreSize=$sock->GET_INFO("SquidRockStoreSize");
	$SquidRockStorePath=$sock->GET_INFO("SquidRockStorePath");
	if(!is_numeric($SquidEnableRockStore)){$SquidEnableRockStore=0;}
	if(!is_numeric($SquidRockStoreSize)){$SquidRockStoreSize=2000;}
	if($SquidRockStorePath==null){$SquidRockStorePath="/home/squid";}
	$t=time();
	$html="
	<div style='font-size:14px' class=explain>{SQUID_ROCK_STORE_EXPLAIN}</div>
	<div style='width:95%' class=form>
	<div id='waitcache-$t'></div>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td>". Field_checkbox("SquidEnableRockStore", 1,$SquidEnableRockStore)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{size}:</td>
		<td style='font-size:16px'>". Field_text("SquidRockStoreSize", $SquidRockStoreSize,"font-size:16px;width:90px")."&nbsp;MB</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td style='font-size:16px'>". Field_text("SquidRockStorePath", $SquidRockStorePath,"font-size:16px;width:220px").button_browse("SquidRockStorePath")."</td>
	</tr>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
	</tr>
	</table>
	</div>
<script>
	var x_Save$t= function (obj) {
			var results=obj.responseText;
			document.getElementById('waitcache-$t').innerHTML='';
			if(results.length>3){alert(results);return;}
			RefreshTab('squid_main_caches_new');
			RefreshTab('squid_main_svc');
			Loadjs('squid.restart.php?ApplyConfToo=yes&ask=yes');
			
		}			
		
	function Save$t(){
			var enabled=1;
			var XHR = new XHRConnection();
			if(!document.getElementById('SquidEnableRockStore').checked){enabled=0;}
			XHR.appendData('SquidRockStoreSize',document.getElementById('SquidRockStoreSize').value);
			XHR.appendData('SquidRockStorePath',document.getElementById('SquidRockStorePath').value);
			XHR.appendData('SquidEnableRockStore',enabled);
			AnimateDiv('waitcache-$t');
			XHR.sendAndLoad('$page', 'POST',x_Save$t);
		}
</script>					
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function SquidEnableRockStore_save(){
	$sock=new sockets();
	$sock->SET_INFO("SquidRockStoreSize", $_POST["SquidRockStoreSize"]);
	$sock->SET_INFO("SquidRockStorePath", $_POST["SquidRockStorePath"]);
	$sock->SET_INFO("SquidEnableRockStore", $_POST["SquidEnableRockStore"]);
	
}