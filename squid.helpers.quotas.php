<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["list-items"])){list_items();exit;}
if(isset($_GET["new-quota-rule"])){new_quota_rule_js();exit;}
if(isset($_GET["get-quota-rule"])){quota_rule_js();exit;}

	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-popup"])){service_cmds_popup();exit;}
	if(isset($_GET["service-cmds-perform"])){service_cmds_perform();exit;}
	
	

if(isset($_GET["ID"])){quota_rule();exit;}
if(isset($_GET["explain-ident"])){explain_ident();exit;}
if(isset($_POST["ID"])){quota_rule_save();exit;}
if(isset($_GET["quota-params"])){quota_params_js();exit;}
if(isset($_GET["quota-params-popup"])){quota_params_popup();exit;}
if(isset($_POST["TEMPLATE"])){quota_params_save();exit;}
if(isset($_POST["delete"])){quota_delete();exit;}
page();

function quota_params_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{squidquota}::{parameters}");
	echo "YahooWin2('650','$page?quota-params-popup=yes&t={$_GET["t"]}','$title')";	
}


function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{squidquota}::{reconfigure}");
	$html="YahooWin4('650','$page?service-cmds-popup=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$cmd=$_GET["service-cmds-popup"];
	$t=time();
	$html="
	<div id='pleasewait-$t''><center><div style='font-size:22px;margin:50px'>{please_wait}</div><img src='img/wait_verybig_mini_red.gif'></center></div>
	<div id='results-$t'></div>
	<script>LoadAjax('results-$t','$page?service-cmds-perform=$cmd&t=$t');</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function service_cmds_perform(){
	$sock=new sockets();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?reconfigure-quotas-tenir=yes")));
	$html="<textarea style='height:450px;overflow:auto;width:100%;font-size:12px'>".@implode("\n", $datas)."</textarea>
<script>
	 document.getElementById('pleasewait-$t').innerHTML='';
	
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	if(!isset($_GET["day"])){$_GET["day"]=$q->HIER();}
	if($_GET["day"]==null){$_GET["day"]=$q->HIER();}		
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$new_rule=$tpl->_ENGINE_parse_body("{new_rule}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$day=$tpl->_ENGINE_parse_body("{day}");
	$week=$tpl->_ENGINE_parse_body("{week}");
	$duration=$tpl->_ENGINE_parse_body("{duration}");
	$quotas=$tpl->_ENGINE_parse_body("{quotas}");
	$maxquota=$tpl->_ENGINE_parse_body("{maxquota}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$apply_parameters=$tpl->_ENGINE_parse_body("{apply_parameters}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$TB_WIDTH=550;
	$t=time();
	
	$buttons="
	buttons : [
	{name: '<b>$new_rule</b>', bclass: 'add', onpress : NewRule$t},
	{name: '<b>$parameters</b>', bclass: 'Settings', onpress :Params$t},
	{name: '<b>$apply_parameters</b>', bclass: 'Reconf', onpress :Reconf$t},
	{name: '<b>$online_help</b>', bclass: 'Help', onpress :help$t},
	
	
		],";		
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?list-items=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$member', name : 'value', width : 184, sortable : true, align: 'left'},
		{display: '$type', name : 'xtype', width : 238, sortable : true, align: 'left'},
		{display: '$duration', name : 'duration', width : 161, sortable : true, align: 'left'},
		{display: '$maxquota', name : 'maxquota', width : 124, sortable : false, true: 'left'},
		{display: '&nbsp;', name : 'del', width : 31, sortable : false, true: 'center'},
		
		
		
	],$buttons
	searchitems : [
		{display: '$member', name : 'value'},
		],
	sortname: 'maxquota',
	sortorder: 'asc',
	usepager: true,
	title: '$members&raquo;{$quotas}',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: 832,
	height: 450,
	singleSelect: true
	
	});
});

function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
}

function NewRule$t(){
	Loadjs('$page?new-quota-rule=yes&t=$t');
}
function GetRule$t(ID){
	Loadjs('$page?get-quota-rule=yes&t=$t&ID='+ID);
}

function Reconf$t(){
	Loadjs('$page?service-cmds=yes&t=$t');
}

function Params$t(){
	Loadjs('$page?quota-params=yes&t=$t');
}

	var x_DeleteQuota$t= function (obj) {
		var results=obj.responseText;
		if(results.length>2){alert(results);return;}
		$('#row'+mem$t).remove();
		
	}

function DeleteQuota$t(ID){
	mem$t=ID;
	var XHR = new XHRConnection();
	XHR.appendData('delete',ID);
	XHR.sendAndLoad('$page', 'POST',x_DeleteQuota$t);	
	
}
function help$t(){
	s_PopUpFull('http://www.proxy-appliance.org/index.php?cID=296','1024','900');
}
</script>";
	
	echo $html;
	
	
}

function quota_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_quotas WHERE ID={$_POST["delete"]}");
	if(!$q->ok){echo $q->mysql_error;}
}

function quota_params_popup(){
	$sock=new sockets();
	$t=$_GET["t"];
	$array=unserialize(base64_decode($sock->GET_INFO("SquidQuotasParams")));
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
	if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}
	if($array["TEMPLATE"]==null){$array["TEMPLATE"]="ERR_ACCESS_DENIED";}
	$html="
	<span id='explain-div-$t'></span>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{disable}:</td>
		<td style='font-size:16px'>". Field_checkbox("DISABLE_MODULE-$t", 1,$array["DISABLE_MODULE"],"DISABLE_MODULE_CHECK$t()")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{template}:</td>
		<td style='font-size:16px'><span id='TEMPLATEtext-$t'>{$array["TEMPLATE"]}</span>&nbsp;".Field_hidden("TEMPLATE-$t", $array["TEMPLATE"])."</td>
		<td>". button("{browse}...", "Loadjs('squid.templates.php?choose-generic=TEMPLATE-$t&divid=TEMPLATEtext-$t')","13px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{cache_time}:</td>
		<td style='font-size:16px' colspan=2>". Field_text("CACHE_TIME-$t",$array["CACHE_TIME"],"font-size:16px;width:90")."&nbsp;{seconds}</td>
	</tr>	
	
	
	<tr>
		<td colspan=3 align='right'><hr>".button("{apply}", "SaveFormRule$t()","18px")."</tr>
	</tr>
	</table>
	
	<script>
		function ExplainIndet$t(){
			var exp=document.getElementById('identification-$t').value;
			LoadAjax('explain-div-$t','$page?explain-ident='+exp);
		
		}
		
	var x_SaveFormRule$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('explain-div-$t').innerHTML='';
		if(results.length>2){alert(results);return;}
		$('#$t').flexReload();
		YahooWin2Hide();
	}	

	function DISABLE_MODULE_CHECK$t(){
		if(document.getElementById('DISABLE_MODULE-$t').checked){
			document.getElementById('TEMPLATE-$t').disabled=true;
			document.getElementById('CACHE_TIME-$t').disabled=true;
		}else{
			document.getElementById('TEMPLATE-$t').disabled=false;
			document.getElementById('CACHE_TIME-$t').disabled=false;		
		}
	}
		
	function SaveFormRule$t(){	
		var XHR = new XHRConnection();
		if(document.getElementById('DISABLE_MODULE-$t').checked){XHR.appendData('DISABLE_MODULE',1);}else{XHR.appendData('DISABLE_MODULE',0);}
		XHR.appendData('TEMPLATE',document.getElementById('TEMPLATE-$t').value);
		XHR.appendData('CACHE_TIME',document.getElementById('CACHE_TIME-$t').value);
		AnimateDiv('explain-div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveFormRule$t);		
		}	
		
		 DISABLE_MODULE_CHECK$t();
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}
function quota_params_save(){
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidQuotasParams");
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
}

function new_quota_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{squidquota}::{new_rule}");
	echo "YahooWin2('650','$page?ID=0&t={$_GET["t"]}','$title')";
	
}
function quota_rule_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$sql="SELECT xtype,value,maxquota FROM webfilters_quotas WHERE `ID`='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$title=$tpl->javascript_parse_text("{squidquota}::{$ligne["xtype"]} ({$ligne["value"]}) max:{$ligne["maxquota"]}M");
	echo "YahooWin2('650','$page?ID={$_GET["ID"]}&t={$_GET["t"]}','$title')";
}


function list_items(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->GET_INFO("SquidQuotasParams")));
	$tpl=new templates();	
	if(!is_numeric($array["CACHE_TIME"])){$array["CACHE_TIME"]=120;}
	if(!is_numeric($array["DISABLE_MODULE"])){$array["DISABLE_MODULE"]=0;}	
	$q=new mysql_squid_builder();	
	$search=trim($_GET["search"]);
	$dayfull="{$_GET["day"]} 00:00:00";
	$date=strtotime($dayfull);
	$table="webfilters_quotas";
	$t=$_GET["t"];
	$tpl=new templates();
	$daysuffix=$tpl->_ENGINE_parse_body(date("{l} d",$date));
	$search='%';
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){json_error_show("Table empty");}
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			if($_POST["sortname"]=="zDate"){$_POST["sortname"]="hour";}
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
		
	if($searchstring<>null){	
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);	
	if(!$q->ok){json_error_show("$q->mysql_error");}
		
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$durations[1]="{per_day}";
	$durations[2]="{per_hour}";	
	
	$identifications["ipaddr"]="{ipaddr}";
	$identifications["uid"]="{member}";
	$identifications["uidAD"]="{active_directory_member}";
	$identifications["MAC"]="{MAC}";
	$identifications["hostname"]="{hostname}";		
	$color=";color:black;";
	if($array["DISABLE_MODULE"]==1){$color=";color:#969696;";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$delete=imgsimple("delete-32.png","","DeleteQuota$t({$ligne["ID"]})");
		$uri="<a href=\"javascript:blur();\" Onclick=\"javascript:GetRule$t({$ligne["ID"]});\" 
		style=\"font-size:14px;text-decoration:underline$color\">";
	$data['rows'][] = array(
		'id' => $ligne["ID"],
		'cell' => array(
			"<span style='font-size:14px$color'>$uri{$ligne["value"]}</a></span>",
			"<span style='font-size:14px$color'>$uri". $tpl->_ENGINE_parse_body("{$identifications[$ligne["xtype"]]}")."</a></span>",
			"<span style='font-size:14px$color'>$uri". $tpl->_ENGINE_parse_body("{$durations[$ligne["duration"]]}")."</a></span>",
			"<span style='font-size:14px$color'>{$ligne["maxquota"]} MB</span>",$delete
	
	 	 	
			)
		);
	}
	
	
echo json_encode($data);		
}

function quota_rule(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$t=$_GET["t"];	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$bt_text="{apply}";
	if($ID<1){$bt_text="{add}";}
	if($ID>0){
		$q=new mysql_squid_builder();
		$sql="SELECT * FROM webfilters_quotas WHERE `ID`='{$_GET["ID"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$ident=$ligne["xtype"];
		$duration=$ligne["duration"];
		$maxquota=$ligne["maxquota"];
		$value=$ligne["value"];
	}
	
	$identifications["ipaddr"]="{ipaddr}";
	$identifications["uid"]="{member}";
	//$identifications["uidAD"]="{active_directory_member}";
	$identifications["MAC"]="{MAC}";
	$identifications["hostname"]="{hostname}";		
	
	$durations[1]="{per_day}";
	$durations[2]="{per_hour}";
	
	$html="
	<span id='explain-div-$t'></span>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{identification}:</td>
		<td>". Field_array_Hash($identifications, "identification-$t",$ident,"ExplainIndet$t()",null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{pattern}:</td>
		<td>". Field_text("value-$t",$value,"font-size:16px")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{duration}:</td>
		<td>". Field_array_Hash($durations, "duration-$t",$duration,null,null,0,"font-size:16px")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{maxquota} (MB):</td>
		<td>". Field_text("maxquota-$t",$maxquota,"font-size:16px;width:90px")."</td>
	</tr>
	
	<tr>
		<td colspan=2 align='right'><hr>".button("$bt_text", "SaveFormRule$t()","18px")."</tr>
	</tr>
	</table>
	
	<script>
		function ExplainIndet$t(){
			var exp=document.getElementById('identification-$t').value;
			LoadAjax('explain-div-$t','$page?explain-ident='+exp);
		
		}
		
	var x_SaveFormRule$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('explain-div-$t').innerHTML='';
		if(results.length>2){alert(results);return;}
		$('#$t').flexReload();
		if($ID==0){
			YahooWin2Hide();
		}
	}		
		
	function SaveFormRule$t(){	
		var XHR = new XHRConnection();
		XHR.appendData('ID','{$_GET["ID"]}');
		XHR.appendData('xtype',document.getElementById('identification-$t').value);
		XHR.appendData('value',document.getElementById('value-$t').value);
		XHR.appendData('maxquota',document.getElementById('maxquota-$t').value);	
		XHR.appendData('duration',document.getElementById('duration-$t').value);
		AnimateDiv('explain-div-$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveFormRule$t);		
		}	
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function quota_rule_save(){
	$ID=$_POST["ID"];
	$q=new mysql_squid_builder();
	$sql="INSERT INTO webfilters_quotas (xtype,value,maxquota,duration) 
	VALUES ('{$_POST["xtype"]}','{$_POST["value"]}','{$_POST["maxquota"]}','{$_POST["duration"]}')";
	
	if($ID>0){
		$sql="UPDATE webfilters_quotas SET 
			`xtype`='{$_POST["xtype"]}',
			`value`='{$_POST["value"]}',
			`maxquota`='{$_POST["maxquota"]}',
			`duration`='{$_POST["duration"]}'
			WHERE ID='$ID'
			";
		
	}
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;
	return;}
	
}

function explain_ident(){
	$tpl=new templates();
	echo "<div class=explain style='font-size:14px'>".$tpl->_ENGINE_parse_body("{squidqota_{$_GET["explain-ident"]}}")."</div>";
	
}

