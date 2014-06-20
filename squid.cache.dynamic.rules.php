<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["items"])){items_table();exit;}
	if(isset($_GET["items-search"])){items_search();exit;}
	if(isset($_POST["ENABLE"])){SaveParams();exit;}
	if(isset($_POST["main-rule-enable"])){rule_enable();exit;}
	if(isset($_POST["main-rule-delete"])){rule_delete();exit;}
	if(isset($_GET["main-rule-js"])){rule_js();exit;}
	if(isset($_GET["main-rule"])){rule_popup();exit;}
	if(isset($_POST["edit-www"])){rule_save();exit;}
	if(isset($_POST["apply-now"])){apply();exit;}
tabs();

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	
		$array["parameters"]="{parameters}";
		$array["items"]="{items}";
		$array["schedule"]="{schedule}";


	while (list ($num, $ligne) = each ($array) ){
		if($num=="schedule"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.databases.schedules.php?TaskType=55\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
	}
	echo build_artica_tabs($html, "squid_dynamic_caches");
}

function rule_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	echo "YahooWin2('815','$page?main-rule=yes&ID=$ID&SourceT={$_GET["SourceT"]}','$ID')";
	
}


function parameters(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$sock=new sockets();
	
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("SquidDynamicCaches")));
	if(!is_numeric($ARRAY["MAX_WWW"])){$ARRAY["MAX_WWW"]=100;}
	if(!is_numeric($ARRAY["ENABLED"])){$ARRAY["ENABLED"]=1;}
	if(!is_numeric($ARRAY["LEVEL"])){$ARRAY["LEVEL"]=5;}
	if(!is_numeric($ARRAY["INTERVAL"])){$ARRAY["INTERVAL"]=420;}
	if(!is_numeric($ARRAY["MAX_TTL"])){$ARRAY["MAX_TTL"]=15;}
	
	$t=time();
	for($i=1;$i<11;$i++){
		$ENFORCE[$i]="{level} $i";
	}
	$INTERVAL[0]="{schedule}";
	$INTERVAL[60]="1 {hour}";
	$INTERVAL[120]="2 {hours}";
	$INTERVAL[180]="3 {hours}";
	$INTERVAL[420]="4 {hours}";
	$INTERVAL[480]="8 {hours}";
	$INTERVAL[1440]="1 {day}";
	$INTERVAL[2880]="2 {days}";
	$INTERVAL[4320]="3 {days}";
	$INTERVAL[5760]="4 {days}";
	
	$MAX_TTLZ[5]="5 {days}";
	$MAX_TTLZ[7]="1 {week}";
	$MAX_TTLZ[15]="2 {weeks}";
	$MAX_TTLZ[30]="1 {month}";
	$MAX_TTLZ[60]="2 {months}";
	
	$p=Paragraphe_switch_img("{dynamic_enforce_rules}", "{squid_dynamic_cache_rules_explain}","ENABLE-$t",$ARRAY["ENABLED"],null,600);
	
	$html="<div style='font-size:26px;margin-bottom:16px'>{dynamic_enforce_rules}</div>
			
	
	
	<div style='width:98%' class=form>	
			
	$p
			
			
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{max_websites}:</td>
			<td>". Field_text("MAX_WWW-$t",$ARRAY["MAX_WWW"],"font-size:18px;width:110px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{enforce_level}:</td>
			<td>". Field_array_Hash($ENFORCE,"LEVEL-$t",$ARRAY["LEVEL"],"style:font-size:18px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{scan_interval}:</td>
			<td>". Field_array_Hash($INTERVAL,"INTERVAL-$t",$ARRAY["INTERVAL"],"style:font-size:18px")."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{rules_retention}:</td>
			<td>". Field_array_Hash($MAX_TTLZ,"MAX_TTL-$t",$ARRAY["MAX_TTL"],"style:font-size:18px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{only_images}:</td>
			<td>". Field_checkbox("OnlyImages-$t",$ARRAY["OnlyImages"],1)."</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:18px'>{only_internet_documents}:</td>
			<td>". Field_checkbox("OnlyeDoc-$t",$ARRAY["OnlyeDoc"],1)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{only_multimedia}:</td>
			<td>". Field_checkbox("OnlyMultimedia-$t",$ARRAY["OnlyMultimedia"],1)."</td>
		</tr>						
		<tr>
			<td class=legend style='font-size:18px'>{files}:</td>
			<td>". Field_checkbox("OnlyFiles-$t",$ARRAY["OnlyFiles"],1)."</td>
		</tr>																			
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td>
		</tr>
	</table>
	</div>	
<script>
var xSave$t=function (obj) {
	RefreshTab('squid_dynamic_caches');
}
	
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('ENABLE',document.getElementById('ENABLE-$t').value);
	XHR.appendData('MAX_WWW',document.getElementById('MAX_WWW-$t').value);
	XHR.appendData('LEVEL',document.getElementById('LEVEL-$t').value);
	XHR.appendData('INTERVAL',document.getElementById('INTERVAL-$t').value);
	XHR.appendData('MAX_TTL',document.getElementById('MAX_TTL-$t').value);
	if(document.getElementById('OnlyImages-$t').checked){XHR.appendData('OnlyImages',1);}else{XHR.appendData('OnlyImages',0);}
	if(document.getElementById('OnlyeDoc-$t').checked){XHR.appendData('OnlyeDoc',1);}else{XHR.appendData('OnlyeDoc',0);}
	if(document.getElementById('OnlyFiles-$t').checked){XHR.appendData('OnlyFiles',1);}else{XHR.appendData('OnlyFiles',0);}
	if(document.getElementById('OnlyMultimedia-$t').checked){XHR.appendData('OnlyMultimedia',1);}else{XHR.appendData('OnlyMultimedia',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);						
}
</script>												
	";
	
echo $tpl->_ENGINE_parse_body($html);
}



function SaveParams(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidDynamicCaches");
	
	
}


function rule_popup(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$sock=new sockets();
	$ARRAY=unserialize(base64_decode($sock->GET_INFO("SquidDynamicCaches")));
	if(!is_numeric($ARRAY["MAX_WWW"])){$ARRAY["MAX_WWW"]=100;}
	if(!is_numeric($ARRAY["ENABLED"])){$ARRAY["ENABLED"]=1;}
	if(!is_numeric($ARRAY["LEVEL"])){$ARRAY["LEVEL"]=5;}
	if(!is_numeric($ARRAY["INTERVAL"])){$ARRAY["INTERVAL"]=420;}
	if(!is_numeric($ARRAY["MAX_TTL"])){$ARRAY["MAX_TTL"]=15;}
	
	$MAX_TTL=$ARRAY["MAX_TTL"];
	$MAX_TTL=$MAX_TTL*24;
	$MAX_TTL=$MAX_TTL*60;
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM main_cache_dyn WHERE familysite='$ID'"));
	$t=time();
	for($i=1;$i<11;$i++){
		$ENFORCE[$i]="{level} $i";
	}
	
	$ttime=strtotime($ligne['zDate']);
	$ttime=strtotime("+$MAX_TTL minutes", $ttime);
	
	
	$html="<div style='font-size:26px;margin-bottom:16px'>$ID</div>
	<p class=text-info style='font-size:16px'>{next_check}:". $tpl->time_to_date($ttime)."</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td class=legend style='font-size:18px'>{enforce_level}:</td>
			<td>". Field_array_Hash($ENFORCE,"level-$t",$ligne["level"],"style:font-size:18px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{enabled}:</td>
			<td>". Field_checkbox("enabled-$t",$ligne["enabled"],1,"enabledCheck$t()")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{only_pictures}:</td>
			<td>". Field_checkbox("OnlyImages-$t",$ligne["OnlyImages"],1)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{only_multimedia}:</td>
			<td>". Field_checkbox("OnlyMultimedia-$t",$ligne["OnlyMultimedia"],1)."</td>
		</tr>					
		<tr>
			<td class=legend style='font-size:18px'>{only_internet_documents}:</td>
			<td>". Field_checkbox("OnlyeDoc-$t",$ARRAY["OnlyeDoc"],1)."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{only_files}:</td>
			<td>". Field_checkbox("OnlyFiles-$t",$ARRAY["OnlyFiles"],1)."</td>
		</tr>					
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",26)."</td>
		</tr>
</table>
</div>
<script>
var xSave$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#flexRT{$_GET["SourceT"]}').flexReload();
	YahooWin2Hide();
	
}

	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('level',document.getElementById('level-$t').value);
	XHR.appendData('edit-www','$ID');
	if(document.getElementById('enabled-$t').checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
	if(document.getElementById('OnlyImages-$t').checked){XHR.appendData('OnlyImages',1);}else{XHR.appendData('OnlyImages',0);}
	if(document.getElementById('OnlyeDoc-$t').checked){XHR.appendData('OnlyeDoc',1);}else{XHR.appendData('OnlyeDoc',0);}
	if(document.getElementById('OnlyFiles-$t').checked){XHR.appendData('OnlyFiles',1);}else{XHR.appendData('OnlyFiles',0);}	
	if(document.getElementById('OnlyMultimedia-$t').checked){XHR.appendData('OnlyMultimedia',1);}else{XHR.appendData('OnlyMultimedia',0);}
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

function enabledCheck$t(){
	document.getElementById('level-$t').disabled=true;
	document.getElementById('OnlyImages-$t').disabled=true;
	document.getElementById('OnlyeDoc-$t').disabled=true;
	document.getElementById('OnlyFiles-$t').disabled=true;
	document.getElementById('OnlyMultimedia-$t').disabled=true;
	if(!document.getElementById('enabled-$t').checked){return;}
	document.getElementById('OnlyImages-$t').disabled=false;
	document.getElementById('OnlyeDoc-$t').disabled=false;
	document.getElementById('OnlyFiles-$t').disabled=false;
	document.getElementById('OnlyMultimedia-$t').disabled=false;
	document.getElementById('level-$t').disabled=false;	
}
enabledCheck$t();
</script>
";
echo $tpl->_ENGINE_parse_body($html);	
	
	
}
function rule_save(){
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("main_cache_dyn", "OnlyImages")){
		$q->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyImages`  smallint( 1 ) DEFAULT '0'");
	}
	if(!$q->FIELD_EXISTS("main_cache_dyn", "OnlyeDoc")){
		$q->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyeDoc`  smallint( 1 ) DEFAULT '0'");
	}
	if(!$q->FIELD_EXISTS("main_cache_dyn", "OnlyFiles")){
		$q->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyFiles`  smallint( 1 ) DEFAULT '0'");
	}
	if(!$q->FIELD_EXISTS("main_cache_dyn", "OnlyMultimedia")){
		$q->QUERY_SQL("ALTER TABLE `main_cache_dyn` ADD `OnlyMultimedia`  smallint( 1 ) DEFAULT '0'");
	}

	$sql="UPDATE main_cache_dyn SET `OnlyImages`='{$_POST["OnlyImages"]}',
	 `OnlyeDoc`='{$_POST["OnlyeDoc"]}', `OnlyFiles`='{$_POST["OnlyFiles"]}',`OnlyMultimedia`='{$_POST["OnlyMultimedia"]}',
	 `enabled`='{$_POST["enabled"]}'
	 WHERE familysite='{$_POST["edit-www"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}

}

function items_table(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$enable=$tpl->javascript_parse_text("{enable}");
	$date=$tpl->javascript_parse_text("{date}");
	$familysite=$tpl->javascript_parse_text("{familysite}");
	$level=$tpl->javascript_parse_text("{level}");
	
	$apply_params=$tpl->_ENGINE_parse_body("{apply}");
	$options=$tpl->javascript_parse_text("{options}");
	$restart=$tpl->javascript_parse_text("{restart}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$warn_proxy_service_reloaded=$tpl->javascript_parse_text("{warn_proxy_service_reloaded}");
	
	$tablewidht=883;
	$t=time();
	$tt=time();
	// main_cache_dyn
	$buttons="buttons : [
		{name: '$apply_params', bclass: 'Reload', onpress : SquidBuildNow$t},
		],	";
	
	
	
		echo "$onlycorpavailable
<table class='$t' style='display: none' id='flexRT$t' style='width:99%;text-align:left'></table>
<script>
function start$t(){
	$('#flexRT$t').flexigrid({
		url: '$page?items-search=yes&t=$t',
		dataType: 'json',
		colModel : [
		{display: '$date', name : 'zDate', width : 185, sortable : false, align: 'left'},
		{display: '$familysite', name : 'familysite', width : 533, sortable : false, align: 'left'},
		{display: '$level', name : 'level', width : 50, sortable : false, align: 'center'},
		{display: '$enable', name : 'enable', width : 50, sortable : false, align: 'center'},
		{display: '$delete', name : 'level', width : 50, sortable : false, align: 'center'},
		],
		$buttons
		searchitems : [
		{display: '$familysite', name : 'familysite'},
		],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 450,
		singleSelect: true
	});
}

var x_Refresh$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#flexRT$t').flexReload();
}

function MainRuleEnable$t(www){
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-enable',www);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

function SquidBuildNow$t(){
	if(!confirm('$warn_proxy_service_reloaded')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('apply-now','yes');
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

function RuleDelete$t(www){
	if(!confirm('$delete: '+www+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('main-rule-delete',www);
	XHR.sendAndLoad('$page', 'POST',x_Refresh$t);
}

start$t();
</script>
";
}

function apply(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?dynamic-cache-apply=yes");
	
}

function items_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$t=$_GET["t"];
	$search='%';
	$table="main_cache_dyn";
	$page=1;
	$data = array();
	$data['rows'] = array();
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(!$q->TABLE_EXISTS($table)){$q->CheckTables(null,true);}
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(!$q->ok){json_error_show("$q->mysql_error",1);}
		$total = $ligne["TCOUNT"];
	
	}else{
		$total =$q->COUNT_ROWS($table);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	if(is_numeric($rp)){
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	}
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error,$sql",1);}
	
	
	
	$data['page'] = $page;
	$data['total'] = $total+1;
	$alltext=$tpl->_ENGINE_parse_body("{all}");
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$val=0;
	$md5=md5(serialize($ligne));
	$color="black";
	$lic=null;
	$familysite=$ligne["familysite"];
	$familysiteenc=urlencode($familysite);
	$level=$ligne["level"];
	$delete=imgsimple("delete-32.png",null,"RuleDelete$t('$familysite')");
	$enable=Field_checkbox($md5,1,$ligne["enabled"],"MainRuleEnable$t('$familysite','$md5')");
	$OnlyImages=intval($ligne["OnlyImages"]);
	$OnlyeDoc=intval($ligne["OnlyeDoc"]);
	$OnlyMultimedia=intval($ligne["OnlyMultimedia"]);
	$OnlyFiles=intval($ligne["OnlyFiles"]);
	$CZ=array();
	
	if($OnlyImages==1){$CZ[]="{only_pictures}";}
	if($OnlyeDoc==1){$CZ[]="{only_internet_documents}";}
	if($OnlyMultimedia==1){$CZ[]="{only_multimedia}";}
	if($OnlyFiles==1){$CZ[]="{only_files}";}

	
	if($ligne["enabled"]==0){$color="#C5C5C5";}
	
	$href="<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('$MyPage?main-rule-js=yes&ID=$familysiteenc&t=$t&SourceT={$_GET["t"]}');\"
	style=\"font-size:18px;text-decoration:underline;color:$color\">";
	
	
	$href_move="<a href=\"javascript:blur();\"
	OnClick=\"javascript:MoveRuleDestinationAsk$t({$ligne["ID"]},{$ligne['zorder']});\"
	style=\"font-size:14px;text-decoration:underline;color:$color\">";
	
	
	if(count($CZ)==0){$explainThis="Cache: $alltext";}else{ $explainThis=@implode(", ", $CZ); }
	
	$data['rows'][] = array(
	'id' => $md5,
	'cell' => array(
	"<span style=font-size:18px;color:$color>{$ligne['zDate']}</span>",
	"<span style=font-size:18px;color:$color>$href{$familysite}</a></span><div><i style='font-size:16px;color:$color'>$explainThis</i></div>",
	"<span style=font-size:18px;color:$color>$level</span>",
	$enable,$delete
	)
	);
	}
	echo json_encode($data);
}
function rule_enable(){
	$ID=$_POST["main-rule-enable"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM main_cache_dyn WHERE familysite='$ID'"));
	if($ligne["enabled"]==1){$enabled=0;}else{$enabled=1;}
	$q->QUERY_SQL("UPDATE main_cache_dyn SET enabled='$enabled' WHERE familysite='$ID'");	
	if(!$q->ok){echo $q->mysql_error;}
	
}
function rule_delete(){
	$ID=$_POST["main-rule-delete"];
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM main_cache_dyn WHERE familysite='$ID'");
	if(!$q->ok){echo $q->mysql_error;}
	
}