<?php
	if(isset($_GET["verbose"])){
		$GLOBALS["VERBOSE"]=true;
		ini_set('html_errors',0);ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "<H2>$alert</H2>";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){items();exit;}
popup();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browsers}");
	$html="YahooWinBrowse('650','$page?popup=yes&ShowOnly={$_GET["ShowOnly"]}','$title')";
	echo $html;
	
}

function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$q->CheckTables();
	$type=$tpl->javascript_parse_text("{type}");
	$browsers=$tpl->javascript_parse_text("{browsers}");
	$pattern=$tpl->javascript_parse_text("{pattern}");
	$items=$tpl->javascript_parse_text("{items}");
	$add=$tpl->javascript_parse_text("{add}");
	$description=$tpl->javascript_parse_text("{description}");
	$title=$tpl->javascript_parse_text("{blocked_members}");
	$t=time();		
	$table_width=630;
	$table_height=450;

	$buttons="buttons : [
	{name: '$new_group', bclass: 'add', onpress : AddGroup},
		],	";
	$buttons=null;
	
	$html=$tpl->_ENGINE_parse_body("")."
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes',
	dataType: 'json',
	colModel : [
		{display: '$type', name : 'PatternType', width : 231, sortable : true, align: 'left'},
		{display: '$pattern', name : 'pattern', width : 231, sortable : false, align: 'left'},
		{display: '$description', name : 'description', width : 231, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'enable', width : 60, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 60, sortable : false, align: 'left'},
		
		
		
	],

	searchitems : [
		{display: '$pattern', name : 'pattern'},
		],
	sortname: 'pattern',
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

	var xBlksProxyDelete= function (obj) {
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



function BlksProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.setLockOff();
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST');
}
</script>
	";
	echo $html;	

}
function items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$RULEID=$_GET["RULEID"];
	
	$search='%';
	$table="(SELECT * FROM webfilters_blkwhlts WHERE  PatternType=1 ) as t";
	$page=1;
$FORCE=1;

	$PatternTypeH[1]="{ComputerMacAddress}";
	$PatternTypeH[0]="{addr}";
	$PatternTypeH[2]="{SquidGroup}";
	$PatternTypeH[3]="{browser}";
	$PatternTypeH[6]="{BannedMimetype}";
	
	
	
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";
	$GLOBALS["GroupType"]["browser"]="{browser}";
	
	
	

	
	
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
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$id=md5($ligne["pattern"]);
		$PatternTypeInt=$ligne["PatternType"];
		$PatternType=$tpl->_ENGINE_parse_body($PatternTypeH[$ligne["PatternType"]]);
		if($ligne["PatternType"]==0){$PatternType=$tpl->_ENGINE_parse_body("{addr}");}
		if($PatternType==null){
			if($_GET["blk"]>1){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==0){
			if($_GET["blk"]==2){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==1){
			if($_GET["blk"]==6){$PatternType=$tpl->_ENGINE_parse_body("{BannedMimetype}");}
		}		
		
		$PatternAffiche=$ligne["pattern"];
		$description=$tpl->_ENGINE_parse_body($ligne["description"]);
		
		
		if($ligne["PatternType"]==2){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='{$ligne["pattern"]}'"));
			$description=$tpl->_ENGINE_parse_body($GLOBALS["GroupType"][$ligne2["GroupType"]]);
			$PatternAffiche=$ligne2["GroupName"];
		}
		
		if($ligne["zmd5"]==null){$q->QUERY_SQL("UPDATE webfilters_blkwhlts SET zmd5='$id' WHERE pattern='". mysql_escape_string2($ligne["pattern"])."'");$ligne["zmd5"]=$id;}
		$md5=$ligne["zmd5"];
		
		$delete=imgsimple("delete-32.png","{delete} {$ligne["pattern"]}","BlksProxyDelete('$md5')");
		$enable=Field_checkbox($id,1,$ligne["enabled"],"BlksProxyEnable('$md5','$id')");	
		
	$data['rows'][] = array(
		'id' => $ligne['ID'],
		'cell' => array("<span style='font-size:18px'>$PatternType</span>"
		,"<span style='font-size:18px'>$PatternAffiche</span>",
		"<span style='font-size:18px'>$description</span>",
		"<center>$enable</center>","<center>$delete</center>" )
		);
	}
	
	
	echo json_encode($data);	
}