<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.updateutility2.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.tasks.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$ERROR_NO_PRIVS');";return;
	}

	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_GET["products"])){products_tabs();exit;}
	if(isset($_GET["product-section"])){product_section();exit;}
	if(isset($_POST["ProductSubKey"])){product_section_save();exit;}
	if(isset($_POST["UpdateUtilityAllProducts"])){UpdateUtilitySave();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["UpdateUtilityStartTask"])){UpdateUtilityStartTask();exit;}
	if(isset($_GET["webevents"])){webevents_table();exit;}
	if(isset($_GET["web-events"])){webevents_list();exit;}
	if(isset($_GET["dbsize"])){dbsize();exit;}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["freewebs"])){frewebslist();exit;}
	if(isset($_GET["add-freeweb-js"])){add_freeweb_js();exit;}
	
tabs();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	
	$html="YahooWin2('920','$page','UpdateUtility');";
	echo $html;
}

function add_freeweb_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$addfree=$tpl->javascript_parse_text("{add_freeweb_explain}");
	$t=$_GET["t"];
	$html="
			
	var x_AddNewFreeWeb$t= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	}	

	function AddNewFreeWeb$t(){
			var servername=prompt('$addfree');
			if(!servername){return;}
			var XHR = new XHRConnection();
			XHR.appendData('ADD_DNS_ENTRY','');
			XHR.appendData('ForceInstanceZarafaID','');
			XHR.appendData('ForwardTo','');
			XHR.appendData('Forwarder','0');
			XHR.appendData('SAVE_FREEWEB_MAIN','yes');
			XHR.appendData('ServerIP','');
			XHR.appendData('UseDefaultPort','0');
			XHR.appendData('UseReverseProxy','0');
			XHR.appendData('gpid','');
			XHR.appendData('lvm_vg','');
			XHR.appendData('servername',servername);
			XHR.appendData('sslcertificate','');
			XHR.appendData('uid','');
			XHR.appendData('useSSL','0');
			XHR.appendData('force-groupware','UPDATEUTILITY');
			AnimateDiv('status-$t');
			XHR.sendAndLoad('freeweb.edit.main.php', 'POST',x_AddNewFreeWeb$t);	
		}	
	
	
	AddNewFreeWeb$t();
	
	";
	echo $html;

}


function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('services.php?Update-Utility-status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("APP_UPDATEUTILITYRUN",$ini,null).
	
	"
	<center>
	<table style='width:20%' class=form>
	<tr>
		<td width=1%>". imgtootltip("refresh-24.png","{refresh}","UpdateUtilityStatus()")."</td>
		<td width=1%>". imgtootltip("24-run.png","{run}","UpdateUtilityStartTask()")."</td>
	</tr>
				
	</table>
	</center>
	<div id='dbsize' style=width:300px></div>
	<script>
		LoadAjaxTiny('dbsize','$page?dbsize=yes&refresh=dbsize');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($status);

}


function products_tabs(){

	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	
	$update=new updateutilityv2();
	while (list ($num, $ArrayF) = each ($update->families) ){
		$array[$num]=$ArrayF["NAME"];
		
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]="<li><a href=\"$page?product-section=yes&product-key=$num\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
	}

	$html="
		<div id='main_upateutility_pkey' style='background-color:white'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_upateutility_pkey').tabs();
				});
		</script>
	
	";
		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


function tabs(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["settings"]="{parameters}";
	$array["products"]="{kaspersky_products}";
	$array["webevents"]="{update_events}";

// Total downloaded: 100%, Result: Retranslation successful and update is not requested
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="webevents"){
			$tab[]="<li><a href=\"UpdateUtility.events.php\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			continue;
		}
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
	}

	$html="
		<div id='main_upateutility_config' style='background-color:white'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_upateutility_config').tabs();
				});
		</script>
	
	";
		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$UpdateUtilityEnableHTTP=$sock->GET_INFO("UpdateUtilityEnableHTTP");
	$UpdateUtilityHTTPPort=$sock->GET_INFO("UpdateUtilityHTTPPort");
	$UpdateUtilityHTTPIP=$sock->GET_INFO("UpdateUtilityHTTPIP");
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	$UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	$users=new usersMenus();
	$APP_UFDBGUARD_INSTALLED=0;
	if($users->APP_UFDBGUARD_INSTALLED){
		$APP_UFDBGUARD_INSTALLED=1;
	}
	
	if(!is_numeric($UpdateUtilityRedirectEnable)){$UpdateUtilityRedirectEnable=0;}
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}
	if(!is_numeric($UpdateUtilityHTTPPort)){$UpdateUtilityHTTPPort=9222;}
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	
	$containerjs="Loadjs('UpdateUtility.container-wizard.php');";
	if($UpdateUtilityUseLoop==1){$containerjs="Loadjs('system.disks.loop.php?js=yes');";}
	$new_schedule=$tpl->javascript_parse_text("{new_schedule}");
	
	$run_update_task_now=$tpl->javascript_parse_text("{run_update_task_now}");
	$ip=new networking();
	$hash=$ip->ALL_IPS_GET_ARRAY();
	$t=time();
	unset($hash["127.0.0.1"]);
	
	$html="
	<div class=explain style='font-size:14px'>{UpdateUtilityEnableHTTP_explain}</div>
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:1%'><div id='status-$t'></div></td>
	<td valign='top' style='width:99%'>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px' colspan=2>{update_for_all_products}:</td>
			<td>". Field_checkbox("UpdateUtilityAllProducts", 1,$UpdateUtilityAllProducts)."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px'>{directory}:</td>
			<td>". Field_text("UpdateUtilityStorePath", $UpdateUtilityStorePath,"font-size:14px;width:250px")."</td>
			<td>". button("{browse}", "Loadjs('SambaBrowse.php?field=UpdateUtilityStorePath&no-shares=yes');","12px")."</td>
		</tr>	
		<tr>
			<td colspan=3 align='right'><hr>". button("{apply}","SaveUpdateUtilityConf()",16)."</td>
		</tr>						
		<tr>
			<td colspan=3>
				<table style='width:100%' style='margin-top:10px'>
					<tr>
						<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99%>
						<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.UpdateUtility.php');\"
						 style='font-size:14px;text-decoration:underline'>{enable_filter_redirection}</a>
						</td>
					</tr>
					<tr>
						<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99%>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:$containerjs;\"
					 		style=\"font-size:14px;text-decoration:underline\">{create_a_dedicated_container}</a>
						</td>
					</tr>
					<tr>
						<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99%>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:Loadjs('$page?add-freeweb-js=yes&t=$t');\"
					 		style=\"font-size:14px;text-decoration:underline\">{add_a_web_service}</a>
						</td>
					</tr>	
					<tr>
						<td width=1%><img src='img/arrow-blue-left-24.png'></td>
						
						<td width=99%>
							<a href=\"javascript:blur();\" 
							OnClick=\"javascript:YahooWin3('650','schedules.php?AddNewSchedule-popup=yes&ID=0&t=$t&ForceType=63&YahooWin=3','$new_schedule');\"
					 		style=\"font-size:14px;text-decoration:underline\">$new_schedule</a>
						</td>
					</tr>									
				</table>
			</td>
		</tr>
					
	
	</tbody>
	</table>
	<div id='freewebs-$t'></div>
	</td>
	</tr>
	</table>
	
	
	<script>
		function UpdateUtilityStatus(){
			var UpdateUtilityUseLoop=$UpdateUtilityUseLoop;
			if(UpdateUtilityUseLoop==1){
				document.getElementById('UpdateUtilityStorePath').disabled=true;
			}
		
			LoadAjax('status-$t','$page?status=yes');
		}
	
		

		
	var x_SaveUpdateUtilityConf= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	}	

	function SaveUpdateUtilityConf(){
			var XHR = new XHRConnection();
			if(document.getElementById('UpdateUtilityAllProducts').checked){XHR.appendData('UpdateUtilityAllProducts','1');}else{XHR.appendData('UpdateUtilityAllProducts','0');}
			XHR.appendData('UpdateUtilityStorePath',document.getElementById('UpdateUtilityStorePath').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);	
		}		
	
	function UpdateUtilityStartTask(){
		if(confirm('$run_update_task_now ?')){
			var XHR = new XHRConnection();
			XHR.appendData('UpdateUtilityStartTask','yes');
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);
		}
	
	}
	
	function UpdateUtilityFreeWebs$t(){
		LoadAjax('freewebs-$t','$page?freewebs=yes');
	}
	
	
	UpdateUtilityStatus();		
	UpdateUtilityFreeWebs$t();
	YahooWin3Hide();
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function UpdateUtilitySave(){
	$sock=new sockets();
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	$sock->SET_INFO("UpdateUtilityAllProducts", $_POST["UpdateUtilityAllProducts"]);
	$sock->SET_INFO("UpdateUtilityRedirectEnable", $_POST["UpdateUtilityRedirectEnable"]);
	if($UpdateUtilityUseLoop==0){
		$sock->SET_INFO("UpdateUtilityStorePath", $_POST["UpdateUtilityStorePath"]);
	}
	$sock->getFrameWork("services.php?restart-updateutility=yes");
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
	$sock->getFrameWork("freeweb.php?reconfigure-updateutility=yes");
	
}

function dbsize(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$refresh=$_GET["refresh"];
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$array=unserialize(@file_get_contents($arrayfile));
	if(!is_array($array)){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		echo "<script>LoadAjaxTiny('$refresh','$page?dbsize=yes&refresh=$refresh')</script>";
		return;

	}

	if(isset($_GET["recalc"])){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		$array=unserialize(@file_get_contents($arrayfile));
	}
	$arrayT["DBSIZE"]=$array["DBSIZE"];
	$t=time();
	$color="black";
	$UpdateUtilityUseLoop=$sock->GET_INFO("UpdateUtilityUseLoop");
	if(!is_numeric($UpdateUtilityUseLoop)){$UpdateUtilityUseLoop=0;}
	
	$SIZEDSK="<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["SIZE"])."</td>";
	$SIZEDSKU="<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["USED"])."</td>";
	$SIZEDSKA="<td nowrap style='font-weight:bold;font-size:13px;color:$color'>". FormatBytes($array["AIVA"])." {$array["POURC"]}%</td>";
	if($UpdateUtilityUseLoop==1){
		$sql="SELECT `path`,`loop_dev` FROM loop_disks WHERE `disk_name`='UpdateUtility'";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$array=unserialize(base64_decode($sock->getFrameWork("system.php?tune2fs-values=".base64_encode($ligne["loop_dev"])."&dirscan=".base64_encode("/automounts/UpdateUtility"))));
		$array["IPOURC"]=$array["INODES_POURC"];
		$array["IUSED"]=$array["INODES_USED"];
		$array["ISIZE"]=$array["INODES_MAX"];
		$SIZEDSK="<td nowrap style='font-weight:bold;font-size:13px'>". $array["SIZE"]."</td>";
		$SIZEDSKU="<td nowrap style='font-weight:bold;font-size:13px'>". $array["USED"]."</td>";
		$array["POURC"]=100-$array["POURC"];
		$SIZEDSKA="<td nowrap style='font-weight:bold;font-size:13px;color:$color'>{$array["AIVA"]} {$array["POURC"]}%</td>";
		
	}
	
	
	
	if($array["IPOURC"]>99){$color="red";}
	if($array["POURC"]>99){$color="red";}
	
	$html="

	<table style='width:95%;margin-top:20px' class=form>
	<tr>
		<td class=legend>{current_size}:</td>
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($arrayT["DBSIZE"])."</td>
	</tr>
	<tr>
		<td class=legend>{hard_drive}:</td>
		$SIZEDSK
	</tr>
	<tr>
		<td class=legend>{used}:</td>
		$SIZEDSKU
	</tr>
	<tr>
		<td class=legend>{free}:</td>
		$SIZEDSKA
		
	</tr>
	<tr>
		<td class=legend>inodes:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>{$array["IUSED"]}/{$array["ISIZE"]} ({$array["IPOURC"]}%)</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjax('$refresh','$page?dbsize=yes&recalc=yes&refresh=$refresh')")."</td>
	</tr>
	</table>

	";


	echo $tpl->_ENGINE_parse_body($html);

}

function product_section(){
	$sock=new sockets();
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$productKey=$_GET["product-key"];
	$update=new updateutilityv2();
	$Array=$update->families[$productKey]["LIST"];
	$html="<center><center class=form style='width:65%'>";
	while (list ($ProductKey, $ProductKeyArray) = each ($Array) ){
		$ProductName=$ProductKeyArray["NAME"];
		if(count($ProductKeyArray["PRODUCTS"])==0){continue;}
		$html=$html."
		
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' >
		<thead class='thead'>
			<tr>
			<th colspan=2 style='font-size:14px'>{$ProductName}</th>
			</tr>
		</thead>
		<tbody class='tbody'>";		
		$classtr=null;	
		while (list ($ProductSubKey, $ProductVersion) = each ($ProductKeyArray["PRODUCTS"]) ){
				if($ProductVersion=="Administration Tools"){continue;}
				if($ProductVersion=="Kaspersky Administration Kit"){continue;}
				if($ProductVersion=="Kaspersky Security Center"){continue;}
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$enabled=0;
				if($update->MAIN_ARRAY["ComponentSettings"][$ProductSubKey]=="true"){
					$img=imgtootltip("check-32.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}else{
					$img=imgtootltip("check-32-grey.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}
				
				if($UpdateUtilityAllProducts==1){
					$img="<img src='img/service-check-32.png'>";
				}
				
				
			$html=$html . "
		<tr class=$classtr>
			
			<td style='font-size:16px'>$ProductVersion</td>
			<td style='font-size:16px' width=1%>$img</td>
		</tr>";
			
		}
		
		$html=$html . "</tbody>
		</table><br>";
		
	}
	
	
	$html=$html."</center></center>
	<script>
		function UpdateUtilityEnable(ProductSubKey){
			var XHR = new XHRConnection();
			XHR.appendData('ProductSubKey',ProductSubKey);
			var img=document.getElementById(ProductSubKey).src;
			if(img.indexOf('32-grey')>0){
				document.getElementById(ProductSubKey).src='/img/check-32.png';
				XHR.appendData('value','true');
			}else{
				document.getElementById(ProductSubKey).src='/img/check-32-grey.png';
				XHR.appendData('value','false');
			}
			
			XHR.sendAndLoad('$page', 'POST');
		}
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function product_section_save(){
	$update=new updateutilityv2();
	$update->MAIN_ARRAY["ComponentSettings"][$_POST["ProductSubKey"]]=$_POST["value"];
	$update->Save();
}

function UpdateUtilityStartTask(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?UpdateUtilityStartTask=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
	
	
}


function frewebslist(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$sql="SELECT * FROM freeweb WHERE groupware='UPDATEUTILITY'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$servername=$ligne["servername"];
		
		$tr[]="
		<tr>
			<td width=1%><img src=\"img/arrow-right-24.png\"></td>
			<td width=99%>
				<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('freeweb.edit.php?hostname=$servername');\" style=\"font-size:16px;text-decoration:underline\">http://$servername</a>
				</td>
		</tr>
		";
		
	}
	
	$html="
			<div style=\"font-size:18px;margin-top:10px\">{web_services}:</div>
			<table style=\"width:99%\" class=\"form\">".@implode("\n", $tr)."</table>";
	
	$tr=array();
	$task=new system_tasks();
	$sql="SELECT * FROM system_schedules WHERE TaskType='63'";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysql_fetch_assoc($results)) {
		$TimeDescription=$ligne["TimeDescription"];
		$TimeText=$task->PatternToHuman($ligne["TimeText"]);
		if(preg_match("#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+(.+?)#", $TimeDescription,$re)){$TimeDescription=$TimeText;$TimeText=null;}
		$ID=$ligne["ID"];
		$tr[]="
		<tr>
		<td width=1%><img src=\"img/arrow-right-24.png\"></td>
		<td width=99%>
		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('schedules.php?AddNewSchedule-js=yes&ID=$ID&YahooWin=3');\" style=\"font-size:16px;text-decoration:underline\">$TimeDescription</a>
		<div style='font-size:10px'><i>$TimeText</div></div>
		</td>
		</tr>
		";
	
	}
	
		$html=$html."
		<div style=\"font-size:18px;margin-top:10px\">{schedules}:</div>
			<table style=\"width:99%\" class=\"form\">".@implode("\n", $tr)."</table>";
	echo $tpl->_ENGINE_parse_body($html);	
	
}

