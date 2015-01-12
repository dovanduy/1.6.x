<?php
	if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once ('ressources/class.computers.inc');
	include_once ('ressources/class.ocs.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){items();exit;}
if(isset($_POST["enable-pattern"])){enabled_save();exit;}
if(isset($_POST["delete"])){delete();exit;}
if(isset($_GET["delete-pattern"])){delete_js();exit;}
popup();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browsers}");
	$html="YahooWinBrowse('650','$page?popup=yes&ShowOnly={$_GET["ShowOnly"]}','$title')";
	echo $html;
	
}
function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_meta();
	$tpl=new templates();
	$MAC=$_GET["MAC"];


	
	$text=$tpl->javascript_parse_text("{remove_internet_restriction_for} $MAC ?");

	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#table-{$_GET["t"]}').flexReload();
	
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$MAC');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function delete(){
	
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM computers_time WHERE `MAC`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$mac=$tpl->javascript_parse_text("{MAC}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$Apply=$tpl->javascript_parse_text("{apply}");
	$description=$tpl->javascript_parse_text("{description} / {allowed}");
	$title=$tpl->javascript_parse_text("{members}: {internet_access_restrictions}");
	$t=time();		
	$table_width=630;
	$table_height=450;

	$buttons="buttons : [
	{name: '$Apply', bclass: 'Apply', onpress : Apply$t},
		],	";
	
	
	$html=$tpl->_ENGINE_parse_body("")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$mac', name : 'MAC', width : 152, sortable : true, align: 'left'},
		{display: '$hostname', name : 'hostname', width : 315, sortable : false, align: 'left'},
		{display: '$description', name : 'description', width : 452, sortable : false, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 54, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : false, align: 'left'},
		
		
		
	],

	searchitems : [
		{display: '$mac', name : 'MAC'},
		],
	sortname: 'MAC',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: $table_height,
	singleSelect: true
	
	});   
});

var xRtResProxyEnable= function (obj) {
		var res=obj.responseText;
		if(res.length>3){alert(res);return;}
		$('#table-$t').flexReload();
	}

function BlksProxyDelete(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('delete-pattern',pattern);
		XHR.setLockOff();
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST',xBlksProxyDelete);
}


function Apply$t(){
	Loadjs('squid.computer.access.progress.php');
}



function RtResProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.setLockOff();
		XHR.sendAndLoad('$page', 'POST',xRtResProxyEnable);
}
</script>
	";
	echo $html;	

}

function enabled_save(){
	$MAC=$_POST["enable-pattern"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM computers_time WHERE `MAC`='$MAC'","artica_backup"));
	if($ligne["enabled"]==1){$_POST["enabled"]=0;}else{$_POST["enabled"]=1;}
	
	$q->QUERY_SQL("UPDATE computers_time SET enabled={$_POST["enabled"]} WHERE MAC='$MAC'");
	if(!$q->ok){echo $q->mysql_error;}
}


function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="computers_time";
	$page=1;
	$FORCE=1;
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}"; }}	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexquery();
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		if(!$q->ok){json_error_show("$q->mysql_error");}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM $table WHERE $FORCE $searchstring $ORDER $limitSql";	
	if($GLOBALS["VERBOSE"]){echo "<p style='color:red'>$sql</p>";}
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show("$q->mysql_error");}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("no rule $sql");}
	$fontsize=18;
	
	
	$computer=new computers();
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne["pattern"]));
		$mac=$ligne["MAC"];
		$macec=urlencode($mac);
		$uid=$computer->ComputerIDFromMAC($mac);
		$color="black";
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		$view_mac=$mac;
		$view_hostname=null;
		
		if($uid<>null){
			$jsfiche=MEMBER_JS($uid,1,1);
			$view_hostname="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;color:$color;text-decoration:underline'>". str_replace("$", "", strtolower($uid))."</a>";
			
			
			$view_mac="<a href=\"javascript:blur();\"
			OnClick=\"javascript:$jsfiche\"
			style='font-size:$fontsize;;color:$color;text-decoration:underline'>$mac</a>";
			
		}
		
		$description=explainThis($ligne);
		
		$delete=imgsimple("delete-48.png","{delete} {$ligne["pattern"]}","Loadjs('$MyPage?delete-pattern=yes&MAC=$macec&t={$_GET["t"]}')");
		$enable=Field_checkbox($id,1,$ligne["enabled"],"RtResProxyEnable('$mac','$id')");	
		
	$data['rows'][] = array(
		'id' => $ligne['mac'],
		'cell' => array("<span style='font-size:18px;color:$color;'>$view_mac</span>"
		,"<span style='font-size:18px;color:$color;'>$view_hostname</span>",
		"<span style='font-size:16px;color:$color;'>$description</span>",
		"<center>$enable</center>",
		"<center>$delete</center>" )
		);
	}
	
	
	echo json_encode($data);	
}

function explainThis($ligne){
$tpl=new templates();
	$array["0"]="00:00";
	$array["3600"]="01:00";
	$array["7200"]="02:00";
	$array["10800"]="03:00";
	$array["14400"]="04:00";
	$array["18000"]="05:00";
	$array["21600"]="06:00";
	$array["25200"]="07:00";
	$array["28800"]="08:00";
	$array["32400"]="09:00";
	$array["36000"]="10:00";
	$array["39600"]="11:00";
	$array["43200"]="12:00";
	$array["46800"]="13:00";
	$array["50400"]="14:00";
	$array["54000"]="15:00";
	$array["57600"]="16:00";
	$array["61200"]="17:00";
	$array["64800"]="18:00";
	$array["68400"]="19:00";
	$array["72000"]="20:00";
	$array["75600"]="21:00";
	$array["79200"]="22:00";
	$array["82800"]="23:00";
	
	
$ARRAYF["MONDAY"]=true;
$ARRAYF["TUESDAY"]=true;
$ARRAYF["WEDNESDAY"]=true;
$ARRAYF["THURSDAY"]=true;
$ARRAYF["FRIDAY"]=true;
$ARRAYF["SATURDAY"]=true;
$ARRAYF["SUNDAY"]=true;

	while (list ($day, $line) = each ($ARRAYF)){
		$dayname="{".strtolower($day)."}";
		$AM=explode(";",$ligne["{$day}_AM"]);
		$AM_1=$array[$AM[0]];
		$AM_2=$array[$AM[1]];
		$PM=explode(";",$ligne["{$day}_PM"]);
		$PM_1=$array[$PM[0]];
		$PM_2=$array[$PM[1]];
		
		$f[]="$dayname: {allowed} $AM_1 - $AM_2 {and} $PM_1 - $PM_2";
		
		
		
	}

$html=@implode("<br>" ,$f);
return $tpl->_ENGINE_parse_body($html);


}