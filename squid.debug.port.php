<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');


if(isset($_POST["SquidDebugPortNum"])){SquidDebugPortSave();exit;}
if(isset($_GET["restrictions"])){restrictions_table();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["details-tablerows"])){restrictions_rows();exit;}
if(isset($_GET["ipaddr-js-add"])){restrictions_add();exit;}
if(isset($_GET["ipaddr-popup"])){restrictions_popup();exit;}
if(isset($_POST["ipaddr"])){restrictions_save();exit;}
if(isset($_POST["ipaddr-del"])){restrictions_del();exit;}
tabs();

function tabs(){

	$page=CurrentPageName();
	$users=new usersMenus();
	$array["status"]='{status}';
	$array["restrictions"]='{restrictions}';
	$sock=new sockets();


	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
		//$html=$html . "<li><a href=\"javascript:LoadAjax('squid_main_config','$page?main=$num&hostname={$_GET["hostname"]}')\" $class>$ligne</a></li>\n";
			
	}
	echo build_artica_tabs($html, "debug_squid_port");


}

function restrictions_add(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_item}");
	echo "YahooWin2('650','$page?ipaddr-popup=yes&t={$_GET["t"]}','$title',true);";	
}

function restrictions_del(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM `debugport_addr` WHERE `ID`=". intval($_POST["ipaddr-del"]));
	if (!$q->ok){echo $q->mysql_error;}	
}


function restrictions_save(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("INSERT IGNORE INTO `debugport_addr` (`ipaddr`) VALUES ('{$_POST["ipaddr"]}')");
	if (!$q->ok){echo $q->mysql_error;}
	
}

function restrictions_popup(){
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();

	

$html="
<div style='font-size:32px;margin-bottom:20px'>{new_item}</div>
<div style='font-size:18px' class=text-info>{acl_src_text}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px' nowrap>{tcp_address}:</td>
		<td>". field_ipv4("ipaddr-$t",null,"font-size:18px;width:250px",false,"SaveCK$t(event)")."</td>
	</tr>	
																	
<tr>
	<td colspan=2 align='right'><hr>". button("{add}","Save$t();","24")."</td>
</tr>
</table>
<script>
	var xSave$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ipaddr', document.getElementById('ipaddr-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

</script>
";
echo $tpl->_ENGINE_parse_body($html);
		
	
	
}


function status(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidDebugPort=intval($sock->GET_INFO("SquidDebugPort"));
	$SquidDebugPortNum=intval($sock->GET_INFO("SquidDebugPortNum"));
	$SquidDebugPortInterface=intval($sock->GET_INFO("SquidDebugPortInterface"));
	if($SquidDebugPortNum==0){
		$SquidDebugPortNum=rand(55678, 65000);
	}
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips["0.0.0.0"]="{all}";
	
	$p=Paragraphe_switch_img("{enable_test_port}", "{enable_test_port_explain}","SquidDebugPort",$SquidDebugPort,null,650);
	
	$html="<div class=form style='width:95%'>
	<table style='width:100%'>
			<tr>
				<td colspan=2>
					$p
				</td>
			</tr>
			<tr>
				<td style='font-size:18px' class=legend>{listen_port}:</td>
				<td>". Field_text("SquidDebugPortNum",$SquidDebugPortNum,"font-size:18px;width:110px")."</td>
			</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_address}:</td>
		<td style='font-size:16px'>". Field_array_Hash($ips,"SquidDebugPortInterface",$SquidDebugPortInterface,"style:font-size:18px")."<td>
		
	</tr>						
			<tr>
				<td colspan=2 align='right'>
				<hr>". button("{apply}","Save$t()",26)."</td>
			</tr>
	</table>								
<script>
	
function xSave$t(obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue); }
	RefreshTab('debug_squid_port');
	Loadjs('squid.compile.progress.php?ask=yes');
}	

function Save$t(){					
	var XHR = new XHRConnection();
	XHR.appendData('SquidDebugPortNum',document.getElementById('SquidDebugPortNum').value);
	XHR.appendData('SquidDebugPort',document.getElementById('SquidDebugPort').value);
	XHR.appendData('SquidDebugPortInterface',document.getElementById('SquidDebugPortInterface').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>			
	";
	
echo $tpl->_ENGINE_parse_body($html);
}


function SquidDebugPortSave(){
	$sock=new sockets();
	$sock->SET_INFO("SquidDebugPort", $_POST["SquidDebugPort"]);
	$sock->SET_INFO("SquidDebugPortNum", $_POST["SquidDebugPortNum"]);
	$sock->SET_INFO("SquidDebugPortInterface", $_POST["SquidDebugPortInterface"]);
}


function restrictions_table(){
$tpl=new templates();
$page=CurrentPageName();
$sock=new sockets();
$t=$_GET["t"];
if(!is_numeric($t)){$t=time();}
$dns_nameservers=$tpl->javascript_parse_text("{ipaddr}");
$new=$tpl->_ENGINE_parse_body("{new_item}");
$restart_service=$tpl->javascript_parse_text("{apply}");

$texttoadd=$tpl->_ENGINE_parse_body("<div class=text-info style='font-size:14px'>{enable_test_port_table_explain}</div>");

	


	
$buttons="
	buttons : [
	{name: '$new', bclass: 'add', onpress : ipaddr$t},
	{name: '$restart_service', bclass: 'ReConf', onpress : RestartService$t},
],";
	
$html="	$texttoadd<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>
	var xmemnum=0;
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
		url: '$page?details-tablerows=yes&t=$t',
		dataType: 'json',
		colModel : [
			{display: '$dns_nameservers', name : 'ipaddr', width :699, sortable : false, align: 'left'},
			{display: '&nbsp;', name : 'none2', width :80, sortable : false, align: 'center'},
		],
		$buttons
		sortname: 'ipaddr',
		sortorder: 'asc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 15,
		showTableToggleBtn: false,
		width: '99%',
		height: 350,
		singleSelect: true
	
	});
});
	
function RestartService$t(){
	Loadjs('squid.compile.progress.php?onlySquid=yes&ask=yes');
}
var x_dnsadd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
	if(document.getElementById('squid-services')){
	LoadAjax('squid-services','squid.main.quicklinks.php?squid-services=yes');
	}
	}
	
var xIpaddrDel$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#rowipaddr-'+xmemnum).remove();
	$('#flexRT$t').flexReload();
}
	
function ipaddr$t(){
	Loadjs('$page?ipaddr-js-add=yes&t=$t',true);
	
}
	
function IpaddrDel$t(num){
	xmemnum=num;
	var XHR = new XHRConnection();
	XHR.appendData('ipaddr-del',num);
	XHR.sendAndLoad('$page', 'POST',xIpaddrDel$t);
}
var x_dnsupd= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}
	
function SquidDNSUpDown(ID,dir){
	var XHR = new XHRConnection();
	XHR.appendData('SquidDNSUpDown',ID);
	XHR.appendData('direction',dir);
	XHR.sendAndLoad('$page', 'POST',x_dnsupd);
}
</script>";
	echo $html;
}
function restrictions_rows(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="debugport_addr";
	
	if(!$q->TABLE_EXISTS($table)){
		$q->QUERY_SQL("CREATE TABLE `squidlogs`.`debugport_addr` 
				( `ID` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY ,
				 `ipaddr` VARCHAR( 90 ) NOT NULL ,
				  UNIQUE KEY `ipaddr` (`ipaddr`) 
				) ENGINE=MYISAM;" );
	}
	
	$page=1;
	$FORCE_FILTER=null;
	
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);
	
	$no_rule=$tpl->_ENGINE_parse_body("{no data}");
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$fontsize="16";
	
	while ($ligne = mysql_fetch_assoc($results)) {
	
		$delete=imgtootltip('delete-48.png','{delete}',"IpaddrDel$t('{$ligne["ID"]}')");

		$data['rows'][] = array(
				'id' => "ipaddr-{$ligne["ID"]}",
				'cell' => array(
						"<div style='font-size:22px;margin-top:8px'>{$ligne["ipaddr"]}</div>",
						"<span style='font-size:12.5px'>$delete</span>",
				)
		);
	
	}
	
	echo json_encode($data);
	}