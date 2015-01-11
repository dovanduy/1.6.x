<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["table"])){table();exit;}
if(isset($_GET["icap-search"])){search();exit;}
if(isset($_GET["route-js"])){route_js();exit;}
if(isset($_GET["route-popup"])){route_popup();exit;}
if(isset($_GET["test-route-js"])){route_test_js();exit;}
if(isset($_GET["test-route-popup"])){route_test_popup();exit;}
if(isset($_POST["test-route"])){route_test_perform();exit;}
if(isset($_GET["route-move-js"])){route_move_js();exit;}
if(isset($_POST["move"])){route_move();exit;}
if(isset($_POST["zmd5"])){route_save();exit;}
if(isset($_GET["route-delete-js"])){route_delete_js();exit;}
if(isset($_POST["route-delete"])){route_delete();exit;}
tabs();

function route_move_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$zmd5=$_GET["zmd5"];
	
	
echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}	
	$('#flexRT{$_GET["t"]}').flexReload();
}
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('move', '{$_GET["zmd5"]}');
	XHR.appendData('dir', '{$_GET["dir"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}



Save$t();
";	
}

function route_delete(){
	$q=new mysql();
	$zmd5=$_POST["route-delete"];
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("DELETE FROM nic_routes WHERE zmd5='$zmd5'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}	
	
}

function  route_delete_js(){
	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$zmd5=$_GET["zmd5"];
	$delete=$tpl->javascript_parse_text("{delete}");
	$t=time();
	$q=new mysql();
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nic_routes WHERE zmd5='$zmd5'","artica_backup"));
	
	
	echo "
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>5){alert(results);return;}
	$('#flexRT{$_GET["t"]}').flexReload();
}
	
	
function Save$t(){
	if(!confirm('$delete {$ligne["pattern"]} ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('route-delete', '{$_GET["zmd5"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
Save$t();
";	
	
}

function route_move(){
	$zmd5=$_POST["move"];
	$dir=$_POST["dir"];
	$q=new mysql();
	$database="artica_backup";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nic_routes WHERE zmd5='$zmd5'","artica_backup"));	
	
	$zOrder=$ligne["zOrder"];
	if($dir=="up"){
		$NewzOrder=$zOrder-1;
	}else{
		$NewzOrder=$zOrder+1;
	}
	
	$q->QUERY_SQL("UPDATE nic_routes SET zOrder='$zOrder' WHERE zOrder='$NewzOrder' AND zmd5<>'$zmd5'",$database);
	$q->QUERY_SQL("UPDATE nic_routes SET zOrder='$NewzOrder' WHERE zmd5='$zmd5'",$database);
	
	$results=$q->QUERY_SQL("SELECT * FROM nic_routes ORDER BY zOrder",$database);
	$c=0;
	while ($ligne = mysql_fetch_assoc($results)) {
		$c++;
		$q->QUERY_SQL("UPDATE nic_routes SET zOrder='$c' WHERE zmd5='{$ligne["zmd5"]}'",$database);
		if(!$q->ok){echo $q->mysql_error;}
	}
	
	
}

function route_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{new_route}");
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	if(strlen($zmd5)>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `pattern` FROM nic_routes WHERE zmd5='$zmd5'","artica_backup"));
		$title=$ligne["pattern"];
		if(!$q->ok){echo $q->mysql_error_html();}
	}
	
	
	$YahooWin="YahooWin";
	echo "$YahooWin('800','$page?route-popup=yes&t=$t&zmd5=$zmd5','$title',true);";
	
}

function route_test_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{test_a_route}");
	$zmd5=$_GET["zmd5"];
	$YahooWin="YahooWin";
	echo "$YahooWin('700','$page?test-route-popup=yes','$title');";	
	
}

function route_test_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$btname="{test}";
	
	
	
	$html="
	<div style='font-size:22px;margin-bottom:20px'>{test_a_route}</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{item}:</td>
		<td>". Field_text("pattern-$t",$_SESSION["TEST_A_ROUTE"],"font-size:18px;width:95%",null,null,null,false,"SaveCk$t(event)")."</td>
	</tr>
	<tr>
	<td colspan=2><textarea style='margin-top:5px;font-family:Courier New;
	font-weight:bold;width:99%;height:320px;border:5px solid #8E8E8E;overflow:auto;font-size:14px !important'
	id='textarea$t'></textarea></td>
	<tr>
		<td colspan=2 align='right'><hr>". button($btname,"Save$t()",22)."</td>
	</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('textarea$t').value=results;
}

function SaveCk$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('test-route',encodeURIComponent(document.getElementById('pattern-$t').value));
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>
	
		";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function route_test_perform(){
	$_POST["test-route"]=url_decode_special_tool($_POST["test-route"]);
	$_SESSION["TEST_A_ROUTE"]=$_POST["test-route"];
	$item=urlencode($_POST["test-route"]);
	echo "$item\n******************\n";
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?test-a-route=$item"));
}


function tabs(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$fontsize=16;
	
	$array["table"]="{routes}";
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="rules"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.mainrules.php\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;
	
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	$html=build_artica_tabs($html,'main_routes_center',1020)."<script>LeftDesign('routes-opac20.png');</script>";
	
	echo $html;	
	
	
}

function route_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$btname="{add}";
	$zmd5=$_GET["zmd5"];
	$t=$_GET["t"];
	$title="{new_route}";
	if($zmd5<>null){
		$btname="{apply}";
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM nic_routes WHERE zmd5='$zmd5'","artica_backup"));
		if(!$q->ok){echo $q->mysql_error_html();}
		$title=$ligne["pattern"];
	}
	
	
	$net=new networking();
	
	$ETHs=$net->Local_interfaces();
	unset($ETHs["lo"]);
	while (list ($int, $none) = each ($ETHs) ){
		$nic=new system_nic($int);
		$ETHZ[$int]="{$int} - $nic->NICNAME - $nic->IPADDR";
		
	}
	
	
	$types[1]="{network_nic}";
	$types[2]="{host}";
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=0;}
	if(!is_numeric($ligne["metric"])){$ligne["metric"]=0;}
	
	
	$html="
		<div style='font-size:24px;margin-bottom:20px'>{$ligne["pattern"]}</div>
		<div style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:24px'>{nic}:</td>
			<td>". Field_array_Hash($ETHZ,"nic-$t",$ligne["nic"],"style:font-size:24px")."</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:24px'>{type}:</td>
			<td>". Field_array_Hash($types,"type-$t",$ligne["type"],"style:font-size:24px")."</td>
		</tr>	
							
		<tr>
			<td class=legend style='font-size:24px'>{item} <span style='font-size:14px'>({address}/{network2})</span>:</td>
			<td>". Field_text("pattern-$t",$ligne["pattern"],"font-size:24px;width:95%")."</td>
		</tr>
				
		<tr>
			<td class=legend style='font-size:24px'>{gateway}:</td>
			<td>". Field_ipv4("gateway-$t",$ligne["gateway"],"font-size:24px;width:95%")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:24px'>{order}:</td>
			<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:24px;width:90px")."</td>
		</tr>										
		<tr>
			<td class=legend style='font-size:24px'>{metric}:</td>
			<td>". Field_text("metric-$t",$ligne["metric"],"font-size:24px;width:90px")."</td>
		</tr>	
											
		<tr>
			<td colspan=2 align='right'><hr>". button($btname,"Save$t()",32)."</td>
		</tr>
		</table>
		</div>
<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	var ID='$zmd5';
	if(results.length>5){alert(results);return;}
	if(ID.length==0){YahooWinHide();}
	$('#flexRT{$_GET["t"]}').flexReload();
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('zmd5','$zmd5');
	XHR.appendData('zOrder',document.getElementById('zOrder-$t').value);
	XHR.appendData('type',document.getElementById('type-$t').value);
	XHR.appendData('pattern',document.getElementById('pattern-$t').value);
	XHR.appendData('gateway',document.getElementById('gateway-$t').value);
	XHR.appendData('metric',document.getElementById('metric-$t').value);
	XHR.appendData('nic',document.getElementById('nic-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);	
}

function check$t(){
	var ID='$zmd5';
	if(ID.length==0){return;}
	document.getElementById('pattern-$t').disabled=true;
	document.getElementById('gateway-$t').disabled=true;
	document.getElementById('nic-$t').disabled=true;
}
check$t();
</script>
		
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function route_save(){
	include_once(dirname(__FILE__)."/class.html.tools.inc");
	$html=new htmltools_inc();
	$zmd5=$_POST["zmd5"];


	if($zmd5==null){
		$md5=md5("{$_POST["nic"]}{$_POST["pattern"]}");
		$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`,`metric`,`zOrder`)
		VALUES('{$_POST["type"]}','{$_POST["gateway"]}','{$_POST["pattern"]}','$md5','{$_POST["nic"]}','{$_POST["metric"]}','{$_POST["zOrder"]}');";
	}else{
		$sql="UPDATE nic_routes SET
				`metric`='{$_POST["metric"]}',
				`zOrder`='{$_POST["zOrder"]}',
				`type`='{$_POST["type"]}' WHERE `zmd5`='$zmd5'";
		
	}
	

	
	
	$q=new mysql();
	if(!$q->FIELD_EXISTS("nic_routes", "metric", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `nic_routes` ADD `metric` INT(10) NOT NULL, ADD INDEX (`metric`)","artica_backup");
	}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain_section=$tpl->_ENGINE_parse_body("{routes_center_explain}");
	$t=time();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$order=$tpl->javascript_parse_text("{order}");
	$title=$tpl->javascript_parse_text("{routes}");
	$new_route=$tpl->_ENGINE_parse_body("{new_route}");
	$test_a_route=$tpl->_ENGINE_parse_body("{test_a_route}");
	$apply=$tpl->_ENGINE_parse_body("{apply}");
	
	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";
	
	$buttons="
	buttons : [
	{name: '$new_route', bclass: 'add', onpress : Add$t},
	{name: '$test_a_route', bclass: 'Search', onpress : TestRoute$t},
	{name: '$apply', bclass: 'apply', onpress : Apply$t},
	
	
	],";
	
	$html="
			
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?icap-search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$order', name : 'zOrder', width : 50, sortable : true, align: 'center'},
	{display: '$nic', name : 'nic', width : 50, sortable : true, align: 'center'},
	{display: '$items', name : 'pattern', width : 255, sortable : true, align: 'left'},
	{display: '$type', name : 'type', width : 151, sortable : true, align: 'left'},
	{display: '$gateway', name : 'gateway', width :255, sortable : false, align: 'left'},
	{display: '&nbsp;', name : 'up', width : 35, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'down', width : 35, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'del', width : 35, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$items', name : 'pattern'},
	{display: '$gateway', name : 'gateway'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$explain_section</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});

	
	function Add$t(){
		Loadjs('$page?route-js=yes&zmd5=&t=$t');
	}
	function TestRoute$t(){
		Loadjs('$page?test-route-js=yes');
	}	
	
	function Apply$t(){
		Loadjs('network.restart.php?t=$t')
		
	}
	
	var x_DansGuardianDelGroup= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
	}
	
	function DansGuardianDelGroup(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
	rowid=ID;
	var XHR = new XHRConnection();
	XHR.appendData('Delete-Group', ID);
	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup);
	}
	}
	
	</script>
	";
	
	echo $html;
	
}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";
	
	$t=$_GET["t"];
	$search='%';
	$table="nic_routes";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if(!$q->FIELD_EXISTS("nic_routes", "zOrder", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `nic_routes` ADD `zOrder` INT(10) NOT NULL, ADD INDEX (`zOrder`)","artica_backup");
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	}
	
	
	if(!$q->FIELD_EXISTS("nic_routes", "metric", "artica_backup")){
		$q->QUERY_SQL("ALTER TABLE `nic_routes` ADD `metric` INT(10) NOT NULL, ADD INDEX (`metric`)","artica_backup");
	}
		

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,$database);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total+1;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
	if(mysql_num_rows($results)==0){
		$array=routes_default();
		echo json_encode($array[1]);
		return;
	}
	
	if($searchstring==null){
		$array=routes_default();
		$data['total']=$data['total']+$array[0];
		$data['rows']=$array[1]["rows"];
	}
	
	$fontsize=18;
	
	$types[1]=$tpl->_ENGINE_parse_body("{network_nic}");
	$types[2]=$tpl->_ENGINE_parse_body("{host}");
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		//if($ligne["enabled"]==0){$color="#8a8a8a";}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$delete=imgsimple("delete-32.png",null,"Loadjs('$MyPage?route-delete-js=yes&zmd5={$ligne["zmd5"]}&t=$t');");
		
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?route-js=yes&zmd5={$ligne["zmd5"]}&t=$t');\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
		
		$down=imgsimple("arrow-down-32.png",null,"Loadjs('$MyPage?route-move-js=yes&zmd5={$ligne["zmd5"]}&t=$t&dir=down');");
		$up=imgsimple("arrow-up-32.png",null,"Loadjs('$MyPage?route-move-js=yes&zmd5={$ligne["zmd5"]}&t=$t&dir=up');");
		
		
		if($ligne["gateway"]==null){$ligne["gateway"]=$ligne["nic"];}
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js{$ligne["zOrder"]}</a></span>",
						"<span $style>$js{$ligne["nic"]}</a></span>",
						"<span $style>{$js}{$ligne["pattern"]}</a></span>",
						"<span $style>$js". $types[$ligne["type"]]."</a></span>",
						"<span $style>$js{$ligne["gateway"]}</span>",
						"<span $style>$up</span>",
						"<span $style>$down</span>",
						"<span $style>$delete</span>",
				)
		);

	}
	
	
		echo json_encode($data);
	
}

function routes_default(){
	$tpl=new templates();
	$fontsize=18;
	$color="black";
	$delete="&nbsp;";
	$js=null;
	$style="style='font-size:{$fontsize}px;color:$color;'";
	$sql="SELECT * FROM `nics` WHERE enabled=1 ORDER BY Interface,metric";
	$default_route=utf8_decode($tpl->javascript_parse_text("{default_route}"));
	$network2=$tpl->_ENGINE_parse_body("{network2}");
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	$c=0;
	
	$data = array();
	$data['page'] = 1;
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$eth=trim($ligne["Interface"]);
		$ID=md5(serialize($ligne));
		$eth=str_replace("\r\n", "", $eth);
		$eth=str_replace("\r", "", $eth);
		$eth=str_replace("\n", "", $eth);
		$GATEWAY=$ligne["GATEWAY"];
		$NETMASK=$ligne["NETMASK"];
		$CDIR=$ligne["NETWORK"];
		if($ligne["GATEWAY"]==null){continue;}
		if($ligne["GATEWAY"]=="0.0.0.0"){continue;}
		$c++;
		
		if($GLOBALS["VERBOSE"]){echo " $eth $default_route $network2 $GATEWAY<br>\n";}
			
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<span $style>$js-</a></span>",
						"<span $style>$js{$eth}</a></span>",
						"<span $style>{$js}0.0.0.0/0 ( $default_route )</span>",
						"<span $style>$js{$network2}</span>",
						"<span $style>$js{$GATEWAY}</span>",
						"<span $style>$js&nbsp;</span>",
						"<span $style>$js&nbsp;</span>",
						"<span $style>&nbsp;</span>",
				)
		);		
		
		
	}
	

	$data['total'] = $c;
	
	return array($data['total'],$data);
	
	
}



