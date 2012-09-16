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
	
	
	$buttons="
	buttons : [
		{name: '$parameters', bclass: 'Reconf', onpress : unblock_parms},
	
	],";


	
$html="

<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
<script>
var rowid$t='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?rules-table-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 133, sortable : true, align: 'left'},	
		{display: '$ipaddr', name : 'ipaddr', width : 94, sortable : true, align: 'left'},	
		{display: '$member', name : 'uid', width :100, sortable : true, align: 'left'},
		{display: '$sitename', name : 'sitename', width : 299, sortable : true, align: 'left'},
		{display: '$delete', name : 'delete', width : 32, sortable : false, align: 'center'},
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
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 734,
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
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
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
	
	
		
	
	
	
	
while ($ligne = mysql_fetch_assoc($results)) {
	if($ligne["uid"]==null){$ligne["uid"]="-";}
	$delete=imgsimple("delete-24.png",null,"BannedDeleteUslks('{$ligne['zmd5']}')");
	$data['rows'][] = array(
		'id' => $ligne['zmd5'],
		'cell' => array(
			"<span style='font-size:14px;color:$color;'>{$ligne["zDate"]}</span>",
			"<span style='font-size:14px;color:$color;'>{$ligne["ipaddr"]}</span>",
			"<span style='font-size:14px;color:$color;'>{$ligne["uid"]}</span>",
			"<span style='font-size:14px;color:$color;'>{$ligne["sitename"]}</span>",
			$delete )
		);
	}
	
	
	
	
echo json_encode($data);		
	
}

