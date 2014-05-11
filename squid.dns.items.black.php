<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["hosts"])){hosts();exit;}
if(isset($_GET["host-js"])){hosts_js();exit;}
if(isset($_GET["delete-host-js"])){hosts_js_delete();exit;}
if(isset($_POST["delete-host-id"])){hosts_delete();exit;}
if(isset($_POST["domain"])){domain_save();exit;}

if(isset($_GET["table"])){table();exit;}


js();



function js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{blacklist}");
	echo "YahooWin3('550','$page?table=yes','$title');";
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$dnsmasq_address_text=$tpl->_ENGINE_parse_body("{dnsmasq_address_text}");
	$hosts=$tpl->_ENGINE_parse_body("{domains}");
	$addr=$tpl->_ENGINE_parse_body("{addr}");
	$new_computer=$tpl->_ENGINE_parse_body("{new_domain}");
	$blacklist=$tpl->_ENGINE_parse_body("{blacklist}");
	$aliases=$tpl->_ENGINE_parse_body("{aliases}");
	$appy=$tpl->_ENGINE_parse_body("{apply}");
	$buttons="
	buttons : [
	{name: '$new_computer', bclass: 'add', onpress : AddHost$t},
	
	
	
	],";
	
	$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
$(document).ready(function(){
	var md5H='';
	$('#flexRT$t').flexigrid({
	url: '$page?hosts=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$hosts', name : 'hostname', width : 432, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 46, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$hosts', name : 'hostname'},
	
	],
	sortname: 'hostname',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 150,
	showTableToggleBtn: false,
	width: '99%',
	height: 300,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});


function AddHost$t(){
	Loadjs('$page?host-js=yes&ID=0&t=$t',true);
	
}
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
}	

function domain_save(){
	
		$sql="INSERT IGNORE INTO dnsmasq_blacklist(`hostname`)
		VALUES ('{$_POST["domain"]}');
		";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n***$sql\n****\n";}
	
}





function hosts_js_delete(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=$_GET["t"];	
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{delete}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hostname` FROM dnsmasq_blacklist WHERE ID='$ID'"));
	$html="
var xDelete{$t}{$ID} = function (obj) {
	var ID=$ID;
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}			
	
function Delete{$t}{$ID}(){
	if( !confirm('$delete {$ligne["hostname"]}  ?') ){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-host-id','$ID');
	XHR.sendAndLoad('$page', 'POST',xDelete{$t}{$ID},true);	
}
Delete{$t}{$ID}();";
echo $html;
}

function hosts_delete(){
	$ID=$_POST["delete-host-id"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM dnsmasq_blacklist WHERE ID='$ID'");
	
}

function hosts_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$text=$tpl->javascript_parse_text("{dnsmasq_blk_domain}");
	
$html="
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT{$_GET["t"]}').flexReload();
	
}

function add$t(){
	var domain=prompt('$text');
	if(!domain){return;}
	var XHR = new XHRConnection();
	XHR.appendData('domain',domain);
	XHR.sendAndLoad('$page', 'POST',xSave$t,true);
}
add$t();";
	
	echo $html;
	
}



	
	
function hosts(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$t=$_GET["t"];
	$search='%';
	$table="dnsmasq_blacklist";
	
	
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
		$total = $q->COUNT_ROWS($table);
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
	
	if(!$q->ok){
		if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();$results = $q->QUERY_SQL($sql);}
	}
		
	if(!$q->ok){	json_error_show($q->mysql_error."<br>$sql");}
	if(mysql_num_rows($results)==0){json_error_show("no data");}
	
	$fontsize="16";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?delete-host-js=yes&ID={$ligne["ID"]}&t=$t&tt={$_GET["tt"]}')");
	
		
		
		$hostname=$ligne["hostname"];
		
	
	$data['rows'][] = array(
			'id' => $ligne['ID'],
			'cell' => array(
					"<span style='font-size:{$fontsize}px;font-weight:bold;color:$color'>$hostname</a><br><i style='font-size:12px'>&nbsp;$grouptype</i></span>",
					"<span style='font-size:{$fontsize}px;font-weight:normal;color:$color'>$delete</span>",)
	);
	}
	
	
	echo json_encode($data);
	
	}