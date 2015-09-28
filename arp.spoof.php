<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsSystemAdministrator){
		if(!$usersmenus->AsSquidAdministrator){
			die();
		}
	}
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["arpquery"])){arp_query();exit;}
	if(isset($_POST["rulename"])){arp_save();exit;}
	if(isset($_GET["delete-arp-js"])){delete_arp_js();exit;}
	if(isset($_GET["arp-js"])){arp_js();exit;}
	if(isset($_GET["arp-popup"])){arp_popup();exit;}
	if(isset($_GET["arp-settings"])){arp_settings();exit;}
	if(isset($_GET["arp-objects"])){objects_table();exit;}
	if(isset($_GET["arp-objects-list"])){objects_list();exit;}
	if(isset($_POST["delete-object"])){object_delete();exit;}
	if(isset($_GET["arpd-options-popup"])){ARPD_OPTIONS_POPUP();exit;}
	if(isset($_POST["ipaddr"])){ippadr_save();exit;}
	if(isset($_GET["arp-form"])){arp_options();exit;}
	if(isset($_POST["arp-delete"])){arp_delete();exit;}
	if(isset($_GET["arp-global-settings"])){arp_global_settings();exit;}
	if(isset($_POST["ArpSpoofEnabled"])){ArpSpoofEnabled_save();exit;}
	if(isset($_GET["reconfigure"])){reconfigure();exit;}
	if(isset($_GET["stop"])){stop();exit;}
	if(isset($_GET["start"])){start();exit;}
	page();
	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	echo "YahooWin3('852','$page','ARP Poisonning')";
	
}
	
function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$rulename=$tpl->_ENGINE_parse_body("{rulename}");
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$title=$tpl->_ENGINE_parse_body("{arp_table}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$sock=new sockets();
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$EnableArpDaemon=intval($sock->GET_INFO("EnableArpDaemon"));
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
	$settings=$tpl->_ENGINE_parse_body("{parameters}");
	$delete_rule=$tpl->javascript_parse_text("{delete_rule}");
	$reconfigure=$tpl->javascript_parse_text("{reconfigure}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$t=time();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?arpquery=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'delete', width : 56, sortable : false, align: 'center'},
	{display: '$rulename', name : 'rulename', width :355, sortable : true, align: 'left'},
	{display: '$gateway', name : 'gateway', width :140, sortable : true, align: 'left'},
	{display: '$nic', name : 'iface', width :140, sortable : true, align: 'left'},
	{display: '$delete', name : 'delete', width : 56, sortable : false, align: 'center'},
	
	
	],
	
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddArpSpoofRule$t},
	{separator: true},
	{name: '$settings', bclass: 'Search', onpress : ArpTableForm},
	{separator: true},
	{name: '$reconfigure', bclass: 'Reconf', onpress : Reconfigure$t},
	{separator: true},
	{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
	],
	
	
	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	{display: '$gateway', name : 'gateway'},
	{display: '$nic', name : 'iface'}
	],
	sortname: 'rulename',
	sortorder: 'asc',
	usepager: true,
	title: 'ARP Poisoning',
	useRp: true,
	rp: 50,
	showTableToggleBtn: true,
	width: 836,
	height: 430,
	singleSelect: true
	
	});
	});
	
	function ArpTableForm(){
		YahooWin5('650','$page?arp-global-settings=yes&t=$t','$settings');
	}
	
	function AddArpSpoofRule$t(){
		Loadjs('$page?arp-js=yes&ID=0&t=$t');
	}
	function EditArpSpoofRule(ID){
		Loadjs('$page?arp-js=yes&ID='+ID+'&t=$t');
	}	
	
	function Reconfigure$t(){
		YahooWin5('650','$page?reconfigure=yes&t=$t','$reconfigure');
	}
	
	function ItemHelp$t(){
		s_PopUpFull('http://proxy-appliance.org/index.php?cID=363','1024','900');
	}
	
var x_SpoofRuleDelete$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row-rule-'+mem$t).remove();
	
}		
	
	function SpoofRuleDelete$t(ID){
		if(confirm('$delete_rule ?')){
			mem$t=ID;
			var XHR = new XHRConnection();
			XHR.appendData('arp-delete',ID);
			XHR.sendAndLoad('$page', 'POST',x_SpoofRuleDelete$t);
		}
	}
	</script>";
	
	echo $html;
	}
	
function reconfigure(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=time();
	$html="
	<center style='font-size:18px'>{reconfigure}...</center>		
	<div id='$tt' style='font-size:16px;width:95%;padding:5px'></div>
	<script>
		LoadAjax('$tt','$page?stop=yes&t=$t&tt=$tt');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function stop(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?arp-poisonning-stop=yes&MyCURLTIMEOUT=120")));
	$html=@implode("<br>\n", $datas)."
	<script>
		LoadAjax('$tt','$page?start=yes&t=$t&tt=$tt');
	</script>			
			
	";
	echo $html;
}
function start(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=$_GET["t"];
	$tt=$_GET["tt"];
	$sock=new sockets();
	$datas=unserialize(base64_decode($sock->getFrameWork("services.php?arp-poisonning-start=yes&MyCURLTIMEOUT=120")));
	$html=@implode("<br>\n", $datas)."
	<script>
		 $('#flexRT$t').flexReload();
	</script>
		
	";
	echo $html;
}	
function arp_global_settings(){
	$tpl=new templates();
	$page=CurrentPageName();	
	$sock=new sockets();
	$t=$_GET["t"];
	$ArpSpoofEnabled=$sock->GET_INFO("ArpSpoofEnabled");
	if(!is_numeric($ArpSpoofEnabled)){$ArpSpoofEnabled=0;}
	$ArpSpoofEnabled_form=Paragraphe_switch_img("{enable_arpspoofing}", 
			"{enable_arpspoofing_explain}","ArpSpoofEnabled",$ArpSpoofEnabled,null,590);
	$tt=time();
	$html="
	<div id='animate-$tt'></div>
	<table style='width:99%' class=form>
	<tr>
		<td>$ArpSpoofEnabled_form<td>
	</tr>
	<tr>
		<td align='right'>". button("{apply}", "Save$t()",18)."</td>
	</tr>
	</table>
	<script>
var x_Save$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('animate-$tt').innerHTML='';
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
	YahooWin5Hide();
}	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ArpSpoofEnabled',document.getElementById('ArpSpoofEnabled').value);
	AnimateDiv('animate-$tt');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);	
}			
</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ArpSpoofEnabled_save(){
	$sock=new sockets();
	$sock->SET_INFO("ArpSpoofEnabled", $_POST["ArpSpoofEnabled"]);
	
}
	
function objects_table(){
	$tpl=new templates();
	$page=CurrentPageName();
	$tt=time();
	$ID=$_GET["ID"];
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$objects=$tpl->_ENGINE_parse_body("{objects}");
	$nic=$tpl->_ENGINE_parse_body("{nic}");
	$title=$tpl->_ENGINE_parse_body("{arp_table}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$sock=new sockets();
	$new_object=$tpl->_ENGINE_parse_body("{new_object}");
	$EnableArpDaemon=intval($sock->GET_INFO("EnableArpDaemon"));
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
	$settings=$tpl->_ENGINE_parse_body("{parameters}");
	$delete_object=$tpl->javascript_parse_text("{delete_object}");
	$ettercap_howto_ipaddr=$tpl->javascript_parse_text("{ettercap_howto_ipaddr}");
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM arpspoof_rules WHERE ID=$ID","artica_backup"));
	$title="$objects:: {$ligne["rulename"]}";
	$t=$_GET["t"];
	$html="
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:99%'></table>
	<script>
	var mem$tt='';
	$(document).ready(function(){
	$('#flexRT$tt').flexigrid({
	url: '$page?arp-objects-list=yes&t=$t&tt=$tt&ID=$ID',
	dataType: 'json',
	colModel : [
	{display: '$objects', name : 'ipaddr', width :432, sortable : true, align: 'left'},
	{display: '$delete', name : 'delete', width : 56, sortable : false, align: 'center'},
	
	
	],
	
	buttons : [
	{name: '$new_object', bclass: 'add', onpress : AddObject$tt},
	{separator: true},
	],
	
	
	searchitems : [
	{display: '$ipaddr', name : 'ipaddr'},
	],
	sortname: 'ipaddr',
	sortorder: 'asc',
	usepager: true,
	title: 'ARP Poisoning::$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: true,
	width: 535,
	height: 350,
	singleSelect: true
	
	});
	});
	
	function ArpTableForm(){
	YahooWin2('650','$page?arp-form=yes','$settings')
	}
	
var x_AddObject$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(document.getElementById('flexRT$t')){  $('#flexRT$t').flexReload();}
	if(document.getElementById('flexRT$tt')){  $('#flexRT$tt').flexReload();}
	}	
	
	function AddObject$tt(){
		var ipaddr=prompt('$ettercap_howto_ipaddr');
		if(ipaddr){
			var XHR = new XHRConnection();
			XHR.appendData('ID','$ID');		
			XHR.appendData('ipaddr',ipaddr);
			XHR.sendAndLoad('$page', 'POST',x_AddObject$tt);	
		}
	}
	
var x_DeleteObject$tt= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row-object-'+mem$tt).remove();
	if(document.getElementById('flexRT$t')){  $('#flexRT$t').flexReload();}
}	
		

	function DeleteObject$tt(ID){
		mem$tt=ID;
		if(confirm('$delete_object ?')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-object','$ID');		
			XHR.sendAndLoad('$page', 'POST',x_DeleteObject$tt);			
		
		}
	}
	
	
	function EditArpSpoofRule(ID){
	Loadjs('$page?arp-js=yes&ID='+ID+'&t=$t');
	}
	
	function SaveARpd(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArpDaemon',document.getElementById('EnableArpDaemon').value);
	XHR.sendAndLoad('$page', 'GET');
	YahooWin2Hide();
	}
	</script>";
	
	echo $html;	
	
	
}

function ippadr_save(){
	$q=new mysql();
	$q->QUERY_SQL("INSERT IGNORE INTO arpspoof_objects (ruleid,ipaddr) VALUES ('{$_POST["ID"]}','{$_POST["ipaddr"]}')","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function object_delete(){
	$ID=$_POST["delete-object"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM arpspoof_objects WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	
	
}
function arp_delete(){
	$ID=$_POST["arp-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM arpspoof_objects WHERE ruleid=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM arpspoof_rules WHERE ID=$ID","artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}		
}

function objects_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$q->BuildTables();
	$ID=$_GET["ID"];
	$t=$_GET["tt"];
	$search='%';
	$table="arpspoof_objects";
	$page=1;
	$ORDER="ORDER BY ipaddr ASC";
	$FORCE=" AND ruleid=$ID ";
	if(!$q->TABLE_EXISTS($table,"artica_backup")){json_error_show("`$table` No such table...",0);}
	if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("No object set",0);}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="(`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(ID) as TCOUNT FROM `$table` WHERE 1 $searchstring$FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(ID) as TCOUNT FROM `$table` WHERE 1 $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE$ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show("$q->mysql_error",0);}
	$divstart="<span style='font-size:14px;font-weight:bold'>";
	$divstop="</div>";
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		
		$delete=imgsimple("delete-24.png","{delete}","DeleteObject$t({$ligne['ID']})");
		
	
	
		$data['rows'][] = array(
				'id' => '-object-'.$ligne['ID'],
				'cell' => array(
						"<span style='font-size:16px'>{$ligne["ipaddr"]}</span></a>",
						$delete)
		);
	}
	
	
	echo json_encode($data);	
	
}
	
	function arp_query(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$sock=new sockets();
		$ArpSpoofEnabled=$sock->GET_INFO("ArpSpoofEnabled");
		if(!is_numeric($ArpSpoofEnabled)){$ArpSpoofEnabled=0;}		
		$q=new mysql();
		$q->BuildTables();
		$t=$_GET["t"];
		$search='%';
		$table="arpspoof_rules";
		$page=1;
		$ORDER="ORDER BY ipaddr ASC";
		if(!$q->TABLE_EXISTS($table,"artica_backup")){json_error_show("`$table` No such table...",1);}
		if($q->COUNT_ROWS($table,"artica_backup")==0){json_error_show("No rule set",1);}
	
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
		if($_POST["query"]<>null){
			$_POST["query"]=str_replace("*", "%", $_POST["query"]);
			$search=$_POST["query"];
			$searchstring="WHERE (`{$_POST["qtype"]}` LIKE '$search')";
			$sql="SELECT COUNT(ID) as TCOUNT FROM `$table` $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
			$total = $q->COUNT_ROWS($table,"artica_backup");
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
		$sql="SELECT *  FROM `$table` $searchstring $ORDER $limitSql";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		
		$divstop="</div>";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$srvstatus="okdanger32.png";
			$status=@implode("\n",unserialize(base64_decode($sock->getFrameWork("services.php?arp-poisonning-status=yes&ID={$ligne["ID"]}"))));
			$ini=new Bs_IniHandler();
			$ini->loadString($status);
			$textservice=null;
			if(DAEMON_STATUS_IS_OK("APP_ARPSOOF",$ini)){
				$srvstatus="ok32.png";
				
				$master_pid=$ini->_params["APP_ARPSOOF"]["master_pid"];
				$memory=FormatBytes($ini->_params["APP_ARPSOOF"]["master_memory"]);
				$version=$ini->_params["APP_ARPSOOF"]["master_version"];
				$since="{since}: {$ini->_params["APP_ARPSOOF"]["uptime"]}";
				$textservice=$tpl->_ENGINE_parse_body("<div style='font-size:11px'><i>{running} $since pid:$master_pid version:$version</i></div>");
			}			
			
			
			if($ArpSpoofEnabled==0){
				$color="#949494";
				$srvstatus="okdanger32.png";
			}
			
			if($ligne["enabled"]==0){
				$color="#949494";
				$srvstatus="ok32-grey.png";
			}
			$divstart="<span style='font-size:14px;font-weight:bold;color:$color'>";
			$ligne["rulename"]=utf8_encode($ligne["rulename"]);
			$delete=imgsimple("delete-24.png","{delete}","SpoofRuleDelete$t({$ligne["ID"]})");
			$edit="<a href=\"javascript:blur();\" OnClick=\"javascript:EditArpSpoofRule('{$ligne['ID']}');\"
			style='font-size:14px;font-weight:bold;text-decoration:underline;color:$color'>";
			
			
			
			$ipaddr=array();
			$results2 = $q->QUERY_SQL("SELECT ipaddr FROM arpspoof_objects WHERE ruleid={$ligne["ID"]}","artica_backup");
			if(!$q->ok){$ipaddr[]=$q->mysql_error;}
			while ($ligne2 = mysql_fetch_assoc($results2)) {$ipaddr[]=$ligne2["ipaddr"];}
						
			if(count($ipaddr)>0){
				$ipaddrT="<div style='font-size:11px;color:$color'><i>".@implode(", ", $ipaddr)."</i></div>";
			}
			

			
			$data['rows'][] = array(
					'id' => "-rule-".$ligne['ID'],
					'cell' => array(
						"<img src='img/$srvstatus'>",
						"$edit{$ligne["rulename"]}</a>$ipaddrT$textservice", 
						$divstart.$ligne["gateway"].$divstop, 
						$divstart.$ligne['iface'].$divstop,
						$delete)
			);
		}
	
	
		echo json_encode($data);
	}
	
function arp_js(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$title=$tpl->_ENGINE_parse_body("{new_rule}");
	if($ID<1){
		$title=$tpl->_ENGINE_parse_body("{new_rule}");
	}else{
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT rulename FROM arpspoof_rules WHERE ID=$ID","artica_backup"));
		$title=utf8_encode($ligne["rulename"]);
	}
	echo "YahooWin4('600','$page?arp-popup=yes&ID=$ID&t={$_GET["t"]}','$title')";
}	

function arp_popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$fontsize="font-size:14px";
	$array["arp-settings"]="{parameters}";
	if($ID>0){
		$array["arp-objects"]="{objects}";
	}

	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&ID=$ID&t={$_GET["t"]}\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
		
	}
			
	echo "
	<div id='tabs_arpspoof_edit' style=''>
	<ul>". implode("\n",$html)."</ul>
	</div>
	
	<script>
		$(document).ready(function() {
			$(\"#tabs_arpspoof_edit\").tabs();});
		
	</script>";	
	
}

function arp_settings(){
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$btname="{add}";
	if($ID>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM arpspoof_rules WHERE ID=$ID","artica_backup"));
		$btname="{apply}";
		$ligne["rulename"]=utf8_encode($ligne["rulename"]);
	}
	
	
	$nics=new networking();
	$array=$nics->Local_interfaces();
	unset($array["lo"]);
	$iface=Field_array_Hash($array, "iface",$ligne["iface"],null,null,0,"font-size:16px");
	
	$html="
	<div id='animate-$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t",1,$ligne["enabled"],"EnabledCheck$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{rule_name}:</td>
		<td>". Field_text("rulename",$ligne["rulename"],"font-size:16px;width:220px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{gateway}:</td>
		<td>". Field_text("gateway",$ligne["gateway"],"font-size:16px;width:220px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{listen_address}:</td>
		<td>$iface</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()","18")."</td>
	</tr>
	</table>	
<script>
var x_Save$t= function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	document.getElementById('animate-$t').innerHTML='';
	if(results.length>3){alert(results);return;}
	if(document.getElementById('flexRT$t')){  $('#flexRT$t').flexReload();}
	if(ID==0){YahooWin4Hide();return;}
	if(document.getElementById('tabs_arpspoof_edit')){RefreshTab('tabs_arpspoof_edit');}
}	
	
function Save$t(){
	var XHR = new XHRConnection();
		var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	XHR.appendData('ID','$ID');
	XHR.appendData('enabled',enabled);
	XHR.appendData('rulename',document.getElementById('rulename').value);
	XHR.appendData('gateway',document.getElementById('gateway').value);
	XHR.appendData('iface',document.getElementById('iface').value);
	AnimateDiv('animate-$t');
	XHR.sendAndLoad('$page', 'POST',x_Save$t);	
}

function EnabledCheck$t(){
	var enabled=0;
	if(document.getElementById('enabled-$t').checked){enabled=1;}
	document.getElementById('rulename').disabled=true;
	document.getElementById('gateway').disabled=true;
	document.getElementById('iface').disabled=true;
	if(enabled==1){
	document.getElementById('rulename').disabled=false;
	document.getElementById('gateway').disabled=false;
	document.getElementById('iface').disabled=false;	
	}
}
EnabledCheck$t();
</script>			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}
function arp_save(){
	$q=new mysql();
	$ID=$_POST["ID"];
	$_POST["rulename"]=addslashes(trim($_POST["rulename"]));
	if(trim($_POST["rulename"])==null){$_POST["rulename"]="New Rule ".time();}
	if($ID==0){
	$sql="INSERT IGNORE INTO arpspoof_rules (rulename,gateway,iface,enabled) 
			VALUES ('{$_POST["rulename"]}','{$_POST["gateway"]}','{$_POST["iface"]}',{$_POST["enabled"]})
			";
	}else{
		$sql="UPDATE arpspoof_rules SET
			rulename='{$_POST["rulename"]}',
			gateway='{$_POST["gateway"]}',
			iface='{$_POST["iface"]}',
			enabled='{$_POST["enabled"]}'
			WHERE ID=$ID";
		
	}
	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}	

	
}
	

