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
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{unblocks}");
	echo "YahooWin2('750','$page?popup=yes','$title');";
}

function popup(){
	
	$fontsize=18;
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$webfilters_usersasks=$q->COUNT_ROWS("webfilters_usersasks");
	$array["table"]="$webfilters_usersasks {unblocks}";
	$array["queue"]="{queue}";
	
	
	
	while (list ($num, $ligne) = each ($array) ){
	
		if($num=="queue"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squidguardweb.unblock.queue.php\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
			continue;
		}
		
	
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:{$fontsize}px'><span>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "main_squid_unlock_tabs")."<script>LeftDesign('logs-white-256-opac20.png');</script>";
	
	

	
	
}


function table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();	

	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$sitename=$tpl->_ENGINE_parse_body("{sitename}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$member=$tpl->javascript_parse_text("{members}");
	$parameters=$tpl->javascript_parse_text("{settings}");
	$title=$tpl->_ENGINE_parse_body("{unblocks}");
	
	$buttons="
	buttons : [
		{name: '$parameters', bclass: 'Reconf', onpress : unblock_parms},
	
	],";

	$buttons=null;
	
$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var rowid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rules-table-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 286, sortable : true, align: 'left'},	
		{display: '$ipaddr', name : 'ipaddr', width : 160, sortable : true, align: 'left'},	
		{display: '$member', name : 'uid', width :179, sortable : true, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 299, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 70, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$ipaddr', name : 'ipaddr'},
		{display: '$member', name : 'uid'},
		{display: '$sitename', name : 'sitename'},
		],
	sortname: 'zDate',
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

	var x_BannedDeleteUslks= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);return;}
		$('#row'+rowid$t).remove();
		LoadAjaxTiny('rules-toolbox-left','dansguardian2.mainrules.php?rules-toolbox-left=yes');
	}		
		
	function BannedDeleteUslks(md5){
		rowid$t=md5;
		var XHR = new XHRConnection();
		XHR.appendData('DeleteWhiteListed', md5);
		XHR.sendAndLoad('$page', 'POST',x_BannedDeleteUslks);  
	}

</script>
";
echo $html;	
	
}

function DeleteWhiteListed(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM webfilters_usersasks WHERE zmd5='{$_POST["DeleteWhiteListed"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?build-smooth=yes");		
	
}

function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$search='%';
	$table="webfilters_usersasks";
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
	
	
	
while ($ligne = mysql_fetch_assoc($results)) {
	if($ligne["uid"]==null){$ligne["uid"]="-";}
	
	$catz="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.unblock.php?www={$ligne["sitename"]}');\"
	style='font-size:18px;color:$color;text-decoration:underline'>";
	
	$delete=imgsimple("delete-24.png",null,"BannedDeleteUslks('{$ligne['zmd5']}')");
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array(
			"<span style='font-size:18px;color:$color;'>{$ligne["zDate"]}</span>",
			"<span style='font-size:18px;color:$color;'>{$ligne["ipaddr"]}</span>",
			"<span style='font-size:18px;color:$color;'>{$ligne["uid"]}</span>",
			"<span style='font-size:18px;color:$color;'>$catz{$ligne["sitename"]}</a></span>",
			$delete )
		);
	}
	
	
	
	
echo json_encode($data);		
	
}

