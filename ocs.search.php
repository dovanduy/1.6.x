<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
}

if(isset($_GET["SearchComputers"])){SearchComputers();exit;}
if(isset($_GET["compt-status"])){comp_ping();exit;}
if(isset($_GET["PingRestart"])){PingRestart_js();exit;}
if(isset($_GET["js-in-front"])){js_in_front();exit;}
if(isset($_GET["js-in-front-popup"])){js_in_front_popup();exit;}
if(isset($_GET["js-ASPopUp"])){js_ASPopUp();exit;}
if(isset($_GET["delete-computer-js"])){delete_computer_js();exit;}
if(isset($_POST["delete-computer"])){delete_computer();exit;}
page();

function delete_computer_js(){
	$page=CurrentPageName();
	$t=time();
	if(!is_numeric($_GET["t"])){$_GET["t"]=0;}
	header("content-type: application/x-javascript");
	$MAC=$_GET["MAC"];
	$tpl=new templates();
	$IpClass=new IP();
	if(!$IpClass->IsvalidMAC($MAC)){
		$error=$tpl->javascript_parse_text("{invalid_mac_address}");
		echo "alert('$error \"$MAC\"');";
		return;
	}
	
	$delete=$tpl->javascript_parse_text("{delete}");
	
$html="
var xSave$t= function (obj) {
	var t={$_GET["t"]};
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(t>0){
		$('#flexRT{$_GET["t"]}').flexReload();
	}
}
	
function Save$t(){
	if(!confirm('$delete $MAC ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-computer','$MAC');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
Save$t();
";
echo $html;
}

function GetRights(){
	$users=new usersMenus();
	if($users->AsSystemAdministrator){return true;}
	if($users->ASDCHPAdmin){return true;}
	if($users->AsSambaAdministrator){return true;}

	return false;
}
function js_in_front(){
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	echo "
	document.getElementById('BodyContent').innerHTML='<div id=$t class=form></div>';
	LoadAjax('$t','$page?js-in-front-popup=yes&CorrectMac={$_GET["CorrectMac"]}&fullvalues={$_GET["fullvalues"]}');";
}

function js_in_front_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
$html="<div class=explain>{OCS_SEARCH_EXPLAIN}</div>
<span id='ocs-search-div'></span>
<script>
	LoadAjax('ocs-search-div','$page?def={$_GET["search"]}');
</script>
";
echo $tpl->_ENGINE_parse_body($html);

	
	
}

function js_ASPopUp(){
$page=CurrentPageName();	
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body("{computers}::{$_GET["search"]}");
$html="RTMMail(650,'$page?js-in-front-popup=yes&search={$_GET["search"]}&without-box=yes&CorrectMac={$_GET["CorrectMac"]}&fullvalues={$_GET["fullvalues"]}','$title');";
echo $html;

}


function PingRestart_js(){
	$page=CurrentPageName();
	$html="function PingRestart(md){
		if(document.getElementById('ip-'+md)){
			var ipaddr=document.getElementById('ip-'+md).value;
			LoadAjaxPreload('div-'+md,'$page?compt-status='+ipaddr+'&restart='+md);
		}
	}
	
	PingRestart('{$_GET["PingRestart"]}');
	";
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=400;
	$TB_WIDTH=635;	
	
	$new_entry=$tpl->_ENGINE_parse_body("{new_computer}");
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$lastest_scan=$tpl->_ENGINE_parse_body("{latest_scan}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$directories=$tpl->_ENGINE_parse_body("{directories}");
	$depth=$tpl->_ENGINE_parse_body("depth");
	$execute=$tpl->_ENGINE_parse_body("{execute}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$import=$tpl->javascript_parse_text("{import_artica_computers}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$alias_proxy=$tpl->javascript_parse_text("{proxy_alias}");
	$import_computers=$tpl->javascript_parse_text("{import_artica_computers}");
	$edit_networks=$tpl->javascript_parse_text("{edit_networks}");
	$my_proxy_aliases=$tpl->javascript_parse_text("{my_proxy_aliases}");
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:22px>$new_entry</strong>', bclass: 'Add', onpress : AddComputer$t},
	{name: '<strong style=font-size:22px>$import_computers</strong>', bclass: 'Add', onpress : ImportComputer$t},
	{separator: true},
	{name: '<strong style=font-size:22px>$edit_networks</strong>', bclass: 'link', onpress : EditNetworks$t},
	{name: '<strong style=font-size:22px>$my_proxy_aliases</strong>', bclass: 'link', onpress : GoToProxyAliases$t},
	],	";
	
	$uri="$page?SearchComputers=yes&mode={$_GET["mode"]}&value={$_GET["value"]}&callback={$_GET["callback"]}&CorrectMac={$_GET["CorrectMac"]}&fullvalues={$_GET["fullvalues"]}&t=$t";
	
	$html="
	<input type='hidden' id='OCS_SEARCH_TABLE' value='flexRT$t'>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$uri',
	dataType: 'json',
	colModel : [
		{display: '<span style=font-size:18px>$hostname</span>', name : 'NAME', width :299, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$ipaddr</span>', name : 'IPADDRESS', width :238, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>MAC</span>', name : 'MAC', width :218, sortable : true, align: 'left'},
		{display: '<span style=font-size:18px>$alias_proxy</span>', name : 'alias', width :218, sortable : false, align: 'left'},
		{display: '<span style=font-size:18px>DB</span>', name : 'DB', width :49, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>DHCP</span>', name : 'DHCP', width :49, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>INTERNET</span>', name : 'INTERNET', width :49, sortable : false, align: 'center'},
		{display: '<span style=font-size:18px>$delete</span>', name : 'DELETE', width :49, sortable : false, align: 'center'},
		
		 	

	],
	$buttons

	searchitems : [
		{display: '$hostname', name : 'NAME'},
		{display: '$ipaddr', name : 'IPADDRESS'},
		{display: 'MAC', name : 'MACADDR'},
	],
	sortname: 'NAME',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$computers</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function AddComputer$t(){
	Loadjs('ocs.add.php');
	
}

function EditNetworks$t(){
	GotoNetworkNETWORKS();
}
function GoToProxyAliases$t(){
	GoToProxyAliases();
}

function lastest_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri&latest-scan=yes'}).flexReload(); 
}

function ImportComputer$t(){
	Loadjs('ocs.import.php');
}

function all_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri'}).flexReload(); 
}
</script>
";
	
	echo $html;
	
}


function SearchComputers(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$EnableIntelCeleron=intval($sock->GET_INFO("EnableIntelCeleron"));
	$q=new mysql();	
	$sock=new sockets();
	$fontsize="14px";
	$cs=0;
	$page=1;
	if(!$q->DATABASE_EXISTS("ocsweb")){$sock->getFrameWork("services.php?mysql-ocs=yes");}
	if(!$q->TABLE_EXISTS("hardware", "ocsweb")){$sock->getFrameWork("services.php?mysql-ocs=yes");}
	if(!$q->TABLE_EXISTS("networks", "ocsweb",true)){$sock->getFrameWork("services.php?mysql-ocs=yes");}
	if(!$q->FIELD_EXISTS("networks", "isActive", "ocsweb")){$q->QUERY_SQL("ALTER TABLE `networks` ADD `isActive` SMALLINT( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( `isActive` ) ","ocsweb");}
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$table="(SELECT networks.HARDWARE_ID,networks.MACADDR,networks.STATUS,networks.IPADDRESS,networks.isActive,
			hardware.* FROM networks,hardware WHERE networks.HARDWARE_ID=hardware.ID) as t";
	
	$searchstring=string_to_flexquery();
	
	

	
if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"ocsweb"));
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){json_error_show($q->mysql_error."<hr>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}

	
	$users=new usersMenus();
	$DHC_MAIN=false;
	if($users->dhcp_installed){
		$EnableDHCPServer=intval($sock->GET_INFO('EnableDHCPServer'));
		if($EnableDHCPServer==1){
			$DHC_MAIN=true;
				
		}
	}
	$SQUID_MAIN=false;
	if($users->SQUID_INSTALLED){
		$SQUID_MAIN=true;
		
	}
	
	
		
	$fontsize="22px";
	$computer=new computers();
	$q2=new mysql_squid_builder();
	
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["MACADDR"]=="unknown"){continue;}
		$ligne["MACADDR"]=strtolower($ligne["MACADDR"]);
		$HARDWARE_ID=$ligne["HARDWARE_ID"];
		$uid=null;
		$OSNAME=null;
		if($ligne["OSNAME"]=="Unknown"){$ligne["OSNAME"]=null;}
		$color="#7D7D7D";
		$md=md5($ligne["MACADDR"]);
		$uri=strtolower($ligne["NAME"]);
		if($EnableIntelCeleron==0){
			$uid=$computer->ComputerIDFromMAC($ligne["MACADDR"]);
		}
		$view="&nbsp;";
		$jslink=null;
		$jsfiche=null;
		$ISDB="ok32-grey.png";
		$DHCP="ok32-none.png";
		$SQUID="ok32-none.png";
		if($DHC_MAIN){
			$DHCP="ok32-grey.png";
			if($computer->dhcpfixedFromMac($ligne["MACADDR"])){$DHCP="ok32.png";}
		}
		
		if($SQUID_MAIN){
			
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT enabled FROM computers_time WHERE `MAC`='{$ligne["MACADDR"]}'","artica_backup"));
			if(intval($ligne2["enabled"])<>0){
				$SQUID="warning24.png";
			}
		}
		
		
		
		if($uid<>null){
			$ISDB="ok32.png";
			$jsfiche=MEMBER_JS($uid,1,1);
			$view="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:$jsfiche\" 
			style='font-size:$fontsize;text-decoration:underline'>". str_replace("$", "", strtolower($uid))."</a>";
		
			$jslink="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:$jsfiche\" 
			style='font-size:$fontsize;text-decoration:underline'>";
		
		}else{
			$uid=$ligne["NAME"];
			
			if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+#", $uid)){
				$q=new mysql_squid_builder();
				$NAME2=$q->UID_FROM_MAC($ligne["MACADDR"]);
				if($NAME2<>null){$uid=$NAME2;}
			}

			if($uid==null){$uid="Unknown";}
			$jsfiche="Loadjs('domains.computer.autoadd.php?mac=".urlencode($ligne["MACADDR"])."&ipaddr=".urlencode($ligne["IPADDRESS"])."&computername=".urlencode($uid)."&t={$_GET["t"]}')";
			if($EnableIntelCeleron==1){$jsfiche="Loadjs('domains.computer.mysql.php?HARDWARE_ID=$HARDWARE_ID&t={$_GET["t"]}')"; }
				
			
			
			$jslink="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;text-decoration:underline'>";
			
			$view="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;text-decoration:underline'>$uid</a>";
			
			
		}
		

		
		$js[]="LoadAjaxTiny('cmp-$md','$page?compt-status={$ligne["IPADDRESS"]}');";
		
		
		
		
		
		$icon="<img src='img/$ISDB'>";
		
		if($ligne["OSNAME"]<>null){$OSNAME="<div style='font-size:9px'><i>{$ligne["OSNAME"]}</i></div>";}
		if($_GET["callback"]<>null){
			
				$color="black";
				
				if($_GET["fullvalues"]==1){
					$viewjs="{$_GET["callback"]}('$uid','{$ligne["IPADDRESS"]}','{$ligne["MACADDR"]}')";
				}else{
					$viewjs="{$_GET["callback"]}('$uid')";
				}				
				
				$view="<a href=\"javascript:blur();\" 
						OnClick=\"javascript:$viewjs\" 
						style='font-size:$fontsize;text-decoration:underline'>". str_replace("$", "", strtolower($uid))."</a>";

				$icon=imgtootltip("arrow-blue-left-32.png",null,$viewjs);
		}
		
		if(!IsPhysicalAddress($ligne["MACADDR"])){if($_GET["CorrectMac"]==1){continue;}}
		$AlreadyMAC[$ligne["MACADDR"]]=true;
		$zdate=null;
		if(isset($ligne["zDate"])){$zdate="<div style='font-size:11px;color:#7D7D7D'>{$ligne["zDate"]}</div>";}
		$macenc=urlencode($ligne["MACADDR"]);
		$ipenc=urlencode($ligne["IPADDRESS"]);
		$jsDelete="Loadjs('$MyPage?delete-computer-js=yes&MAC=$macenc&t={$_GET["t"]}');";
		
		
		$alias=$q2->UID_FROM_MAC($ligne["MACADDR"]);
		if($alias<>null){$alias_uri="Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$macenc');";}
		if($alias==null){
				$alias=$q2->UID_FROM_IP($ligne["IPADDRESS"]);
				if($alias<>null){$alias_uri="Loadjs('squid.nodes.php?node-infos-js=yes&ipaddr=$ipenc');";}
				
		}
				
		if($alias==null){
			$alias="<center><img src='img/32-plus.png'></center>";
			$alias_uri="Loadjs('squid.nodes.php?link-user-js=yes&MAC=$macenc&ipaddr=$ipenc',true)";
		}
		
		if($EnableIntelCeleron==1){
			$jsfiche="Loadjs('domains.computer.mysql.php?HARDWARE_ID=$HARDWARE_ID&t={$_GET["t"]}')";
		}
		
		$cs++;
		
	$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array(
		$view.$zdate,
		"<span style='font-size:$fontsize'>$jslink{$ligne["IPADDRESS"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["MACADDR"]}</a></span>",
		"<span style='font-size:$fontsize'><a href=\"javascript:blur();\" 
			OnClick=\"javascript:$alias_uri\" style='text-decoration:underline'>{$alias}</a></span>",
		"<center><a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\">$icon</a></center>",
		"<center><a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\"><img src='img/$DHCP'></center></a>",
		"<center><a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\"><img src='img/$SQUID'></center></a>",
		"<center><a href=\"javascript:blur();\" OnClick=\"javascript:$jsDelete\"><img src='img/delete-32.png'></center></a>"
		
		 
		)
		);		

	}

	if($cs>$_POST["rp"]){
		$data['total'] = $cs;
		echo json_encode($data);
		return;
	}
	
	
	
	
	if($cs==0){
		json_error_show("no item");
	}
	$data['total'] = $cs;
	echo json_encode($data);
	
	
}

function delete_computer(){
	$ocs=new ocs($_POST["delete-computer"]);
	$ocs->DeleteComputer();
	
	
}

function comp_ping(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");		
	$time=md5(microtime());
	$R=$sock->getFrameWork("network.php?ping={$_GET["compt-status"]}");
	writelogs("network.php?ping={$_GET["compt-status"]} -> '$R'",__FUNCTION__,__FILE__,__LINE__);
	if($R=="TRUE"){echo "<img src='img/ok32.png' id='$time'>";return;}
	
	$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart=$time')");
	if(isset($_GET["restart"])){
		$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart={$_GET["restart"]}');");
		echo $tpl->_ENGINE_parse_body("<input type='hidden' id='ip-{$_GET["restart"]}' value='{$_GET["compt-status"]}'>$img</div>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<div id='div-$time'><input type='hidden' id='ip-$time' value='{$_GET["compt-status"]}'>$img</div>");
}


