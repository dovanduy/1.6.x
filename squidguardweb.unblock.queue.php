<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["rules-table-list"])){table_list();exit;}
if(isset($_POST["DeleteWhiteListed"])){DeleteWhiteListed();exit;}

table();

function table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();	
	
	
	if(!$q->TABLE_EXISTS("ufdbunlock")){
		$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`ufdbunlock` (
			`md5` VARCHAR( 90 ) NOT NULL ,
			`logintime` BIGINT UNSIGNED ,
			`finaltime` INT UNSIGNED ,
			`uid` VARCHAR(128) NOT NULL,
			`MAC` VARCHAR( 90 ) NULL,
			`www` VARCHAR( 128 ) NOT NULL ,
			`ipaddr` VARCHAR( 128 ) ,
			PRIMARY KEY ( `md5` ) ,
			KEY `MAC` (`MAC`),
			KEY `logintime` (`logintime`),
			KEY `finaltime` (`finaltime`),
			KEY `uid` (`uid`),
			KEY `www` (`www`),
			KEY `ipaddr` (`ipaddr`)
			)  ENGINE = MEMORY;";
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo FATAL_ERROR_SHOW_128($q->mysql_error_html());
			return;
		}
	}
	
	
	//	

	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$uid=$tpl->_ENGINE_parse_body("{uid}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{created}");
	$finish=$tpl->_ENGINE_parse_body("{finish}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$member=$tpl->javascript_parse_text("{members}");
	$parameters=$tpl->javascript_parse_text("{settings}");
	$title=$tpl->_ENGINE_parse_body("{unblock_queue}");
	
	$buttons="
	buttons : [
		{name: '$parameters', bclass: 'Reconf', onpress : unblock_parms},
	
	],";

	$buttons=null;
	
$html="

<table class='UFDBGUARD_QUEUE_RELEASE' style='display: none' id='UFDBGUARD_QUEUE_RELEASE' style='width:100%'></table>
<script>
var rowid$t='';
$(document).ready(function(){
$('#UFDBGUARD_QUEUE_RELEASE').flexigrid({
	url: '$page?rules-table-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'logintime', width : 180, sortable : true, align: 'left'},	
		{display: '$ipaddr', name : 'ipaddr', width : 160, sortable : true, align: 'left'},
		{display: '$uid', name : 'uid', width : 160, sortable : true, align: 'left'},		
		{display: '$sitename', name : 'www', width : 299, sortable : true, align: 'left'},
		{display: '$finish', finaltime : 'uid', width :180, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 70, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$member', name : 'uid'},
		{display: '$sitename', name : 'www'},
		],
	sortname: 'logintime',
	sortorder: 'desc',
	usepager: true,
	title: '<strong style=font-size:22px>$title</strong>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 400,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	function unblock_parms(){
		Loadjs('squidguardweb.unblock.php')
	}

	var xBannedDeleteQueueUFDB= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#row'+rowid$t).remove();
		$('#UFDBGUARD_QUEUE_RELEASE').flexReload();
	}		
		
	function BannedDeleteQueueUFDB(md5){
		rowid$t=md5;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteWhiteListed', md5);
		XHR.sendAndLoad('$page', 'POST',xBannedDeleteQueueUFDB);  
	}

</script>
";
echo $html;	
	
}

function DeleteWhiteListed(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM ufdbunlock WHERE `md5`='{$_POST["DeleteWhiteListed"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?reconfigure-unlock=yes");		
	
}

function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="ufdbunlock";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"]+1;
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
		
	if(mysql_num_rows($results)==0){json_error_show("no row");}
	
	//	$q->QUERY_SQL("INSERT IGNORE INTO `ufdbunlock` (`md5`,`logintime`,`finaltime`,`uid`,`www`,`ipaddr`)
///VALUES('$md5','$time','$EnOfLife','$user','$familysite','$IPADDR')");
	
	
while ($ligne = mysql_fetch_assoc($results)) {
	if($ligne["uid"]==null){$ligne["uid"]="-";}
	
	$logintime=date("Y-m-d H:i:s",$ligne["logintime"]);
	$finaltime=date("Y-m-d H:i:s",$ligne["finaltime"]);
	$distance=$tpl->_ENGINE_parse_body(distanceOfTimeInWords(time(),$ligne["finaltime"],true));
	
	$catz="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.unblock.php?www={$ligne["www"]}');\"
	style='font-size:18px;color:$color;text-decoration:underline'>";
	
	$delete=imgsimple("delete-24.png",null,"BannedDeleteQueueUFDB('{$ligne['md5']}')");
	$data['rows'][] = array(
		'id' => $ligne['md5'],
		'cell' => array(
			"<span style='font-size:18px;color:$color;'>$logintime</span>",
			"<span style='font-size:18px;color:$color;'>{$ligne["ipaddr"]}</span>",
			"<span style='font-size:18px;color:$color;'>{$ligne["uid"]}</span>",
			"<span style='font-size:18px;color:$color;'>$catz{$ligne["www"]}</a></span>",
			"<span style='font-size:18px;color:$color;'>$finaltime<br><i style='font-size:11px'>$distance</i></span>",
			$delete )
		);
	}
	
	
	
	
echo json_encode($data);		
	
}

