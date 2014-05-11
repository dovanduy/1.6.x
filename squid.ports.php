<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"//");ini_set('error_append_string',"\n");


include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once('ressources/class.system.network.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["search"])){page_search();exit;}
if(isset($_GET["port-js"])){port_js();exit;}
if(isset($_GET["delete-port-js"])){delete_port_js();exit;}
if(isset($_GET["port-popup"])){port_popup();exit;}
if(isset($_POST["ipaddr"])){port_save();exit;}
if(isset($_POST["delete-port"])){port_delete();exit;}
page();


function port_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_port}");
	
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port FROM proxy_ports WHERE ID=$ID"));
		$title="{$ligne["ipaddr"]}:{$ligne["port"]}";
		
	}
	echo "YahooWin2('700','$page?port-popup=yes&ID=$ID','$title')";
	// https://services.assetmanagement.hsbc.fr/documents/fr_rme_hsbc_ee_diversifie_responsable_solidaire_f.pdf
	
}



function delete_port_js(){
	$page=CurrentPageName();
	$t=time();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr,port FROM proxy_ports WHERE ID=$ID"));
		$title="{$ligne["ipaddr"]}:{$ligne["port"]}";
	
	}
	echo "
var xdel$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	if(document.getElementById('TABLE_SQUID_PORTS')){
		$('#'+document.getElementById('TABLE_SQUID_PORTS').value).flexReload();
	}
		
}			
function del$t(){
	if(!confirm('$delete $title ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-port','$ID');
	XHR.sendAndLoad('$page', 'POST',xdel$t);				
}
	del$t();";
			
	
	
}

function port_delete(){
	$ID=intval($_POST["delete-port"]);
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM proxy_ports WHERE ID=$ID");
	if(!$q->ok){echo $q->mysql_error;}
}

function port_popup(){
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$btname="{add}";
	$q=new mysql_squid_builder();
	$title=$tpl->javascript_parse_text("{new_port}");
	if(!$q->FIELD_EXISTS("proxy_ports", "transparent")){
		$q->QUERY_SQL("ALTER TABLE `proxy_ports` ADD `transparent` smallint(1) NOT NULL DEFAULT '0'");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	if($ID>0){
		$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT * FROM proxy_ports WHERE ID=$ID"));
		$title="{$ligne["ipaddr"]}:{$ligne["port"]}";
		$btname="{apply}";
	}
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ips["0.0.0.0"]="{all}";

	if($ligne["ipaddr"]==null){$ligne["ipaddr"]="0.0.0.0";}
	if($ligne["port"]==0){$ligne["port"]=rand(1024,63000);}
	if(!is_numeric($ligne["enabled"])){$ligne["enabled"]=1;}
	$html="<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=2><div style='font-size:32px;margin-bottom:15px'>$title</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{enabled}:</td>
		<td style='font-size:18px'>". Field_checkbox("enabled-$t", 1,$ligne["enabled"],"Check$t()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{transparent}:</td>
		<td style='font-size:18px'>". Field_checkbox("transparent-$t", 1,$ligne["transparent"])."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{listen_addr}:</td>
		<td style='font-size:18px'>". Field_array_Hash($ips, "ipaddr-$t",$ligne["ipaddr"],"style:font-size:18px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{listen_port}:</td>
		<td style='font-size:18px'>". field_text("port-$t", $ligne["port"],"font-size:18px;width:90px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{info}:</td>
		<td style='font-size:18px'>". field_text("xnote-$t", $ligne["xnote"],"font-size:18px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",28)."</td>
	</tr>						
	</table>
<script>
	var xSave$t=function (obj) {
		var tempvalue=obj.responseText;
		if (tempvalue.length>3){alert(tempvalue);return;}
		var ID=$ID;
		if(ID==0){YahooWin2Hide();}
		if(document.getElementById('TABLE_SQUID_PORTS')){
			$('#'+document.getElementById('TABLE_SQUID_PORTS').value).flexReload();
		}
		
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ID','$ID');
		XHR.appendData('ipaddr',document.getElementById('ipaddr-$t').value);
		XHR.appendData('port',document.getElementById('port-$t').value);
		XHR.appendData('xnote',encodeURIComponent(document.getElementById('xnote-$t').value));
		if(document.getElementById('enabled-$t').checked){
				XHR.appendData('enabled',1);
		}else{
			XHR.appendData('enabled',0);
		}
		
		
		if(document.getElementById('transparent-$t').checked){
				XHR.appendData('transparent',1);
		}else{
			XHR.appendData('transparent',0);
		}		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);	
	}
	
	function Check$t(){
		document.getElementById('ipaddr-$t').disabled=true;
		document.getElementById('port-$t').disabled=true;
		document.getElementById('xnote-$t').disabled=true;
		document.getElementById('transparent-$t').disabled=true;
		
		if(document.getElementById('enabled-$t').checked){
			document.getElementById('transparent-$t').disabled=false;
			document.getElementById('ipaddr-$t').disabled=false;
			document.getElementById('port-$t').disabled=false;
			document.getElementById('xnote-$t').disabled=false;		
		}
	
	}

Check$t();
</script>					
				
				
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function port_save(){
	$ID=$_POST["ID"];
	$ipaddr=$_POST["ipaddr"];
	$port=$_POST["port"];
	$xnote=mysql_escape_string2(url_decode_special_tool($_POST["xnote"]));
	$enabled=$_POST["enabled"];
	$transparent=$_POST["transparent"];
	$sqladd="INSERT INTO proxy_ports (ipaddr,port,xnote,enabled,transparent) VALUES ('$ipaddr','$port','$xnote','$enabled','$transparent')";
	$sqledit="UPDATE proxy_ports SET ipaddr='$ipaddr',port='$port',xnote='$xnote',enabled='$enabled',transparent='$transparent'
	WHERE ID=$ID";
	$q=new mysql_squid_builder();
	if($ID>0){
		$q->QUERY_SQL($sqledit);
	}else{$q->QUERY_SQL($sqladd);}
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_port}");
	$port=$tpl->javascript_parse_text("{listen_port}");
	$address=$tpl->javascript_parse_text("{listen_address}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$rewrite_rules_fdb_explain=$tpl->_ENGINE_parse_body("{rewrite_rules_fdb_explain}");
	$rebuild_tables=$tpl->javascript_parse_text("{rebuild_tables}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("proxy_ports")){$q->CheckTables(null,true);}
	$title="<strong style=font-size:16px>".$tpl->javascript_parse_text("{listen_ports}")."</strong>";
	
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : NewRule$tt},
	{name: '$apply', bclass: 'Reconf', onpress : Apply$tt},
	],";
	
	$html="
	<input type='hidden' ID='TABLE_SQUID_PORTS' value='flexRT$tt'>
	<table class='flexRT$tt' style='display: none' id='flexRT$tt' style='width:100%'></table>
	<script>
	function Start$tt(){
	$('#flexRT$tt').flexigrid({
	url: '$page?search=yes&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
	{display: '&nbsp;', name : 'ID', width :70, sortable : true, align: 'center'},
	{display: '$address', name : 'ipaddr', width :228, sortable : true, align: 'left'},
	{display: '$port', name : 'port', width :499, sortable : true, align: 'left'},
	{display: '&nbsp;', name : 'enabled', width : 70, sortable : true, align: 'center'},
	{display: '&nbsp;', name : 'delete', width : 61, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$port', name : 'port'},
	{display: '$address', name : 'ipaddr'},
	
	],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
}
	
var xNewRule$tt= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}

function Apply$tt(){
	Loadjs('squid.reconfigure.php');
}
	
	
function NewRule$tt(){
	Loadjs('$page?port-js=yes&ID=0&t=$tt');
}
function RuleDestinationDelete$tt(zmd5){
	if(!confirm('$delete')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rules-destination-delete', zmd5);
	XHR.sendAndLoad('$page', 'POST',xNewRule$tt);
}
var xRuleEnable$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	$('#flexRT$tt').flexReload();
}
	
	
function RuleEnable$tt(ID,md5){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	if(document.getElementById(md5).checked){XHR.appendData('enable', 1);}else{XHR.appendData('enable', 0);}
	XHR.sendAndLoad('$page', 'POST',xRuleEnable$tt);
}
Start$tt();
</script>
";
echo $html;
}
function page_search(){
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql_squid_builder();

$t=$_GET["t"];
$search='%';
$table="proxy_ports";
$page=1;
$FORCE_FILTER=null;
$total=0;

if($q->COUNT_ROWS($table)==0){events_search_defaults();return;}
if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
if(isset($_POST['page'])) {$page = $_POST['page'];}

$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];

	}else{
		$total = $q->COUNT_ROWS($table);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}

	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql);

	$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){events_search_defaults();return;}


	

	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$ID=$ligne["ID"];
		$zMD5=$ligne["zMD5"];
		$ipaddr=$ligne["ipaddr"];
		$port=$ligne["port"];
		$enabled=$ligne["enabled"];
		$icon="folder-network-48.png";
		$check="check-48.png"; //check-48-grey.png
		$xnote=utf8_encode($ligne["xnote"]);
		$script=imgsimple("script-24.png",null,"Loadjs('$MyPage?events-script=yes&zmd5={$ligne["zmd5"]}',true)");
		$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-port-js=yes&ID=$ID',true)");
		$transparent=null;
		
		if($ligne["enabled"]==0){
			$color="#A0A0A0";
			$check="check-48-grey.png";
		
			}
		
		if($ligne["transparent"]==1){
			$xnote=$xnote." - transparent";
			$icon="folder-network-48-tr.png";
			if($ligne["enabled"]==0){$icon="folder-network-48-trg.png";}
		}
			
		$xnote=wordwrap($xnote,130,"<br>");
		$EditJs="<a href=\"javascript:blur();\"
		OnClick=\"javascript:Loadjs('$MyPage?port-js=yes&ID=$ID&t={$_GET["tt"]}');\"
		style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
		

		$data['rows'][] = array(
				'id' => $ID,
				'cell' => array(
						"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
						"$EditJs$ipaddr</a> ",
						"$EditJs$port</a> <div style='font-size:14px;color:$color'>$xnote</div>",
						"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
						"<span style='font-size:30px;font-weight:normal;color:$color'>$delete</span>",)
		);
	}
	
	if($searchstring==null){
		$array=events_search_defaults(true);
		while (list ($index, $dnarr) = each ($array) ){
			$data['rows'][]=$dnarr;
		}
		
		$data['total']=$data['total']+6;
	}

	echo json_encode($data);
}


function events_search_defaults($return=false){
	//Loadjs('squid.popups.php?script=listen_port');
	$squid=new squidbee();
	$users=new usersMenus();
	$sock=new sockets();
	$tpl=new templates();
	if(!is_numeric($squid->second_listen_port)){$squid->second_listen_port=0;}
	if(!is_numeric($squid->ssl_port)){$squid->ssl_port=0;}
	if($squid->isNGnx()){$users->SQUID_REVERSE_APPLIANCE=false;}
	$SquidBinIpaddr=$sock->GET_INFO("SquidBinIpaddr");
	$transparent=null;
	if($squid->hasProxyTransparent==1){
		$transparent="{transparent}";
	}
	
	$sock=new sockets();
	$EnableCNTLM=$sock->GET_INFO("EnableCNTLM");
	$CNTLMPort=$sock->GET_INFO("CnTLMPORT");
	$DisableSSLStandardPort=$sock->GET_INFO("DisableSSLStandardPort");
	if(!is_numeric($DisableSSLStandardPort)){$DisableSSLStandardPort=1;}
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($CNTLMPort)){$CNTLMPort=3155;}
	if($SquidBinIpaddr==null){$SquidBinIpaddr="0.0.0.0";}
	$SquidAsMasterPeerPort=intval($sock->GET_INFO("SquidAsMasterPeerPort"));
	$SquidAsMasterPeerIPAddr=$sock->GET_INFO("SquidAsMasterPeerIPAddr");
	if($SquidAsMasterPeerIPAddr==null){$SquidAsMasterPeerIPAddr="0.0.0.0";}
	
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	$delete="delete-48-grey.png";
	$color="black";
	$explainStyle="font-size:13px";
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = 5;
	$data['rows'] = array();
	
	$listen_port=$tpl->_ENGINE_parse_body("<strong>{main_port}</strong> $transparent {CnTLMPORT_explain}</strong>");
	$second_port=$tpl->_ENGINE_parse_body("<strong>{second_port}</strong><br>{squid_second_port_explain}");
	$smartphones_port=$tpl->_ENGINE_parse_body("<strong>{smartphones_port}</strong><br>{smartphones_port_explain}");
	$cntlm_port=$tpl->_ENGINE_parse_body("<strong>{cntlm_port}</strong><br>{CnTLMPORT_explain2}");
	$ssl_port=$tpl->_ENGINE_parse_body("<strong>{ssl_port}</strong> $transparent<br>{squid_ssl_port_explain}");
	$parent_port=$tpl->_ENGINE_parse_body("<strong>{parent_port}</strong><br>{parent_port_explain}");
	
	$smartphones_port=wordwrap($smartphones_port,130,"<br>");
	$second_port=wordwrap($second_port,130,"<br>");
	$cntlm_port=wordwrap($cntlm_port,130,"<br>");
	$ssl_port=wordwrap($ssl_port,130,"<br>");
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$SpanJs="<a href=\"javascript:blur();\" style='font-size:30px;font-weight:normal;color:$color;'>";
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	
	$data['rows'][] = array(
			'id' => "001",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a> ",
					"$EditJs$squid->listen_port</a> <div style='font-size:14px'>$listen_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	if($squid->second_listen_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$data['rows'][] = array(
			'id' => "0002",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a> ",
					"$EditJs$squid->second_listen_port</a> <div style='$explainStyle'><span style='color:$color'>$second_port</span></div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	$EnableCNTLM=intval($sock->GET_INFO("EnableCNTLM"));
	if($EnableCNTLM==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}

	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "0003",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'>
						<img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$CNTLMPort</a><div style='$explainStyle'><span style='color:$color'>$cntlm_port</span></div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png

	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	if($squid->ssl_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";	
	
	$data['rows'][] = array(
			'id' => "0004",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$squid->ssl_port</a><div style='$explainStyle'><span style='color:$color'>$ssl_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	
	if($squid->smartphones_port==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	
	$data['rows'][] = array(
			'id' => "0005",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$EditJs$SquidBinIpaddr</a>",
					"$EditJs$squid->smartphones_port</a> <div style='$explainStyle'><span style='color:$color'>$smartphones_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	
	
	$color="black";
	$icon="folder-network-48.png";
	$check="check-48.png"; //check-48-grey.png
	if($SquidAsMasterPeerPort==0){$color="#A0A0A0";$check="check-48-grey.png";$icon="folder-network-48-grey.png";}
	
	$EditJs="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port');\"
	style='font-size:30px;font-weight:normal;color:$color;text-decoration:underline'>";
	
	$data['rows'][] = array(
			'id' => "0005",
			'cell' => array(
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$icon'></span>",
					"$SpanJs<span style='color:$color'>$SquidAsMasterPeerIPAddr</a>",
					"$SpanJs<span style='color:$color'>$SquidAsMasterPeerPort</a> <div style='$explainStyle'><span style='color:$color'>$parent_port</div>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$check'></span>",
					"<span style='font-size:30px;font-weight:normal;color:$color'><img src='img/$delete'></span>",)
	);	
	

	if($return){return $data['rows'];}
	echo json_encode($data);
	
}