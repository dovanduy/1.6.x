<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["pattern-js"])){pattern_js();exit;}
if(isset($_GET["pattern-popup"])){pattern_popup();exit;}
if(isset($_POST["pattern-save"])){pattern_save();exit;}
if(isset($_POST["delete-pattern"])){pattern_delete();exit;}
if(isset($_GET["delete-pattern-js"])){pattern_delete_js();exit;}
table();

function pattern_js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_network}");
	echo "YahooWin3('600','$page?pattern-popup=yes&portid={$_GET["portid"]}&include={$_GET["include"]}','$title')";
}

function pattern_delete_js(){
	
	$page=CurrentPageName();
	$t=time();
	header("content-type: application/x-javascript");
	$ID=intval($_GET["ID"]);
	$tpl=new templates();
echo "
var xdel$t=function (obj) {
	var tempvalue=obj.responseText;
	if (tempvalue.length>3){alert(tempvalue);return;}
	$('#TABLE_SQUID_WBL_PORTS{$_GET["include"]}').flexReload();
}
	

function del$t(){
	var XHR = new XHRConnection();
	XHR.appendData('delete-pattern','$ID');
	XHR.sendAndLoad('$page', 'POST',xdel$t);
}
del$t();";
}

function pattern_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	if(!is_numeric($_GET["include"])){if($_GET["include"]=="yes"){$_GET["include"]=1;}else{$_GET["include"]=0;}}
	
	$html="
		<div style='font-size:30px;margin-bottom:20px'>{new_network}</div>
		<div style='font-size:18px;margin-bottom:10px' class=explain>{subnet_simple_explain}</div>
		<div style='width:98%' class=form>
		<textarea style='margin-top:5px;margin-bottom:20px;
		font-family:Courier New;font-weight:bold;width:98%;height:250px;
		border:5px solid #8E8E8E;overflow:auto;font-size:18px !important'
		id='textToParseCats-$t'></textarea>
		
		<center><hr>". button("{add}", "SaveItemsMode$t()",26)."</center>
		</div>
<script>
var x_SaveItemsMode$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	YahooWin3Hide();
	$('#TABLE_SQUID_WBL_PORTS{$_GET["include"]}').flexReload();
}

function SaveItemsMode$t(){
	var XHR = new XHRConnection();
	XHR.appendData('pattern-save', document.getElementById('textToParseCats-$t').value);
	XHR.appendData('include', '{$_GET["include"]}');
	XHR.appendData('portid', '{$_GET["portid"]}');
	XHR.sendAndLoad('$page', 'POST',x_SaveItemsMode$t);
	}
</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function pattern_save(){
	$q=new mysql_squid_builder();
	$ipclass=new IP();
	$tr=explode("\n",$_POST["pattern-save"]);
	$f=array();
	while (list ($num, $ligne) = each ($tr) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(!$ipclass->isIPAddressOrRange($ligne)){continue;}
		$f[]="('{$_POST["portid"]}','{$_POST["include"]}','$ligne')";
	}		
	
	
	if(count($f)>0){
		$sql="INSERT IGNORE INTO `proxy_ports_wbl` (portid,`include`,`pattern`) VALUES ".@implode(",", $f)	;
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;}
		
	}
	
}
function pattern_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM proxy_ports_wbl WHERE ID={$_POST["delete-pattern"]}");
	if(!$q->ok){echo $q->mysql_error;}
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$tt=time();
	$t=$_GET["t"];
	$type=$tpl->javascript_parse_text("{type}");
	$networks=$tpl->_ENGINE_parse_body("{networks}");
	$new_rule=$tpl->_ENGINE_parse_body("{new_network}");
	$port=$tpl->javascript_parse_text("{listen_port}");
	$address=$tpl->javascript_parse_text("{listen_address}");
	$delete=$tpl->javascript_parse_text("{delete} {rule} ?");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("proxy_ports_wbl")){$q->CheckTables(null,true);}
	
	
	$ex[1]='{whitelisted_destination_networks}';
	$ex[0]="{whitelisted_src_networks}";
	$title="<strong style=font-size:30px>".$tpl->javascript_parse_text("{listen_ports}: {$ex[$_GET["include"]]}")."</strong>";
	
	$buttons="
	buttons : [
	{name: '<strong style=font-size:18px>$new_rule</strong>', bclass: 'add', onpress : NewRule$tt},
	{name: '<strong style=font-size:18px>$apply</strong>', bclass: 'Reconf', onpress : Apply$tt},
	],";
	
	$html="
<table class='TABLE_SQUID_WBL_PORTS{$_GET["include"]}' style='display: none' id='TABLE_SQUID_WBL_PORTS{$_GET["include"]}' style='width:100%'></table>
<script>
function Start$tt(){
	$('#TABLE_SQUID_WBL_PORTS{$_GET["include"]}').flexigrid({
		url: '$page?search=yes&include={$_GET["include"]}&port-id={$_GET["port-id"]}',
		dataType: 'json',
		colModel : [
		
			{display: '<strong style=font-size:18px>$networks</strong>', name : 'networks', width :720, sortable : true, align: 'left'},
			{display: '&nbsp;', name : 'delete', width : 70, sortable : false, align: 'center'},
			],
			$buttons
			searchitems : [
				{display: '$networks', name : 'networks'},
			],
			sortname: 'ID',
			sortorder: 'desc',
			usepager: true,
			title: '$title',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: '99%',
			height: 350,
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
	Loadjs('firehol.progress.php');
}
	
	
function NewRule$tt(){
	Loadjs('$page?pattern-js=yes&portid={$_GET["port-id"]}&include={$_GET["include"]}');
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

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("proxy_ports_wbl")){$q->CheckTables(null,true);}
	$sock=new sockets();
	$t=$_GET["t"];
	$search='%';
	$table="proxy_ports_wbl";
	$page=1;
	$total=0;
	$OKFW=true;
	
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE include={$_GET["include"]} AND portid={$_GET["portid"]} $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
		$pageStart = ($page-1)*$rp;
		if(is_numeric($rp)){$limitSql = "LIMIT $pageStart, $rp";}
	
		$sql="SELECT *  FROM `$table` WHERE include={$_GET["include"]} AND portid={$_GET["port-id"]} $searchstring $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql);
	
		$no_rule=$tpl->_ENGINE_parse_body("{no_rule}");
		$all=$tpl->javascript_parse_text("{all}");
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error."<br>$sql");}
		if(mysql_num_rows($results)==0){json_error_show($no_rule);}
	
		$error_firwall_not_configured=$tpl->javascript_parse_text("{error_firwall_not_configuredisquid}");
		$tpl=new templates();
		$all=$tpl->javascript_parse_text("{all}");
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$ID=$ligne["ID"];
			$pattern=$ligne["pattern"];
			$delete=imgsimple("delete-48.png",null,"Loadjs('$MyPage?delete-pattern-js=yes&ID=$ID&include={$_GET["include"]}',true)");
	
			$data['rows'][] = array(
					'id' => $ID,
					'cell' => array(
							"<span style='font-size:30px;font-weight:normal;color:$color'>$pattern</span>",
							"<center style='margin-top:3px;font-size:30px;font-weight:normal;color:$color'>$delete</center>",)
			);
		}

		echo json_encode($data);
	}