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
page();

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
	
	
$html="<div class=text-info>{OCS_SEARCH_EXPLAIN}</div>
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
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : AddComputer$t},
	],	";
	
	$uri="$page?SearchComputers=yes&mode={$_GET["mode"]}&value={$_GET["value"]}&callback={$_GET["callback"]}&CorrectMac={$_GET["CorrectMac"]}&fullvalues={$_GET["fullvalues"]}&t=$t";
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$uri',
	dataType: 'json',
	colModel : [
		{display: '$hostname', name : 'NAME', width :299, sortable : true, align: 'left'},
		{display: '$ipaddr', name : 'IPADDRESS', width :238, sortable : true, align: 'left'},
		{display: 'MAC', name : 'MAC', width :218, sortable : true, align: 'left'},
		{display: 'DB', name : 'DB', width :49, sortable : false, align: 'center'},
		{display: 'DHCP', name : 'DHCP', width :49, sortable : false, align: 'center'},
		{display: 'INTERNET', name : 'INTERNET', width :49, sortable : false, align: 'center'},
		
		 	

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
	title: '<span style=font-size:18px>$computers</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function AddComputer$t(){
	YahooUser(1051,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');
}

function lastest_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri&latest-scan=yes'}).flexReload(); 
}

function ImportComputer$t(){
	YahooWin3('450','$page?artica-importlist-popup=yes','$import');
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
		$uid=null;
		$OSNAME=null;
		if($ligne["OSNAME"]=="Unknown"){$ligne["OSNAME"]=null;}
		$color="#7D7D7D";
		$md=md5($ligne["MACADDR"]);
		$uri=strtolower($ligne["NAME"]);
		$uid=$computer->ComputerIDFromMAC($ligne["MACADDR"]);
		$view="&nbsp;";
		$jslink=null;
		$jsfiche=null;
		$ISDB="ok24-grey.png";
		$DHCP="ok24-none.png";
		$SQUID="ok24-none.png";
		if($DHC_MAIN){
			$DHCP="ok24-grey.png";
			if($computer->dhcpfixedFromMac($ligne["MACADDR"])){$DHCP="ok24.png";}
		}
		
		if($SQUID_MAIN){
			
			$ligne2=mysql_fetch_array($q2->QUERY_SQL("SELECT enabled FROM computers_time WHERE `MAC`='{$ligne["MACADDR"]}'","artica_backup"));
			if(intval($ligne2["enabled"])<>0){
				$SQUID="warning24.png";
			}
		}
		
		
		
		if($uid<>null){
			$ISDB="ok24.png";
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
			
			$jslink="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;text-decoration:underline'>";
			
			$view="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;text-decoration:underline'>$uid</a>";
			
			
		}
		$js[]="LoadAjaxTiny('cmp-$md','$page?compt-status={$ligne["IPADDRESS"]}');";
		
		
		
		
		if($EnableScanComputersNet==1){if($ligne["isActive"]==1){$isActive="img/ok24.png";}else{$isActive="img/danger24.png";}}
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
		$cs++;
		
	$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array(
		$view.$zdate,
		"<span style='font-size:$fontsize'>$jslink{$ligne["IPADDRESS"]}</a></span>",
		"<span style='font-size:$fontsize'>$jslink{$ligne["MACADDR"]}</a></span>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\">$icon</a>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\"><img src='img/$DHCP'></a>",
		"<a href=\"javascript:blur();\" OnClick=\"javascript:$jsfiche\"><img src='img/$SQUID'></a>"
		
		 
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
	if($R=="TRUE"){echo "<img src='img/ok24.png' id='$time'>";return;}
	
	$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart=$time')");
	if(isset($_GET["restart"])){
		$img=imgtootltip("unknown24.png","{check}","Loadjs('$page?PingRestart={$_GET["restart"]}');");
		echo $tpl->_ENGINE_parse_body("<input type='hidden' id='ip-{$_GET["restart"]}' value='{$_GET["compt-status"]}'>$img</div>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<div id='div-$time'><input type='hidden' id='ip-$time' value='{$_GET["compt-status"]}'>$img</div>");
}


