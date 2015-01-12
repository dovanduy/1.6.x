<?php
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');

if(posix_getuid()<>0){
	$users=new usersMenus();
	if(!GetRights()){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
}

if(isset($_GET["search"])){search();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["popup"])){page();exit;}
if(isset($_GET["delete-js"])){delete_js();exit;}
if(isset($_POST["delete"])){delete();exit;}
js();

function GetRights(){
	$users=new usersMenus();
	if($users->AsArticaMetaAdmin){return true;}
	return false;
}


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();	
	$tpl=new templates();
	$uuid=$_GET["uuid"];
	$q=new mysql_meta();
	$hostname=$q->uuid_to_host($uuid);
	$uuid=urlencode($uuid);
	$tag=$q->uuid_to_tag($_GET["uuid"]);
	$title=$tpl->javascript_parse_text("{orders}::{$hostname} - $tag");
	$html="RTMMail(990,'$page?popup=yes&uuid=$uuid&t={$_GET["t"]}','$title');";
	echo $html;

}
function delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql_meta();
	$tpl=new templates();
	$ID=$_GET["delete-js"];
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ordersubject FROM `metaorders` WHERE `orderid`='$ID'"));
	$text=$tpl->javascript_parse_text("{delete} {order} $ID - {$ligne["ordersubject"]} ?");
	
	$page=CurrentPageName();
	$t=time();
	$html="
var xcall$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$('#ARTICA_META_MAIN_TABLE').flexReload();
	$('#TABLE_META_ORDERS_LIST').flexReload();
}

function xFunct$t(){
	if(!confirm('$text')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete','$ID');
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xcall$t);
}

xFunct$t();
";
	echo $html;

}

function delete(){
	$q=new mysql_meta();
	$q->QUERY_SQL("DELETE FROM `metaorders` WHERE `orderid`='{$_POST["delete"]}'");
	if(!$q->ok){echo $q->mysql_error;}
}



function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$q=new mysql_meta();
	$TB_HEIGHT=400;
	$TB_WIDTH=635;	
	
	if($_GET["uuid"]<>null){
		$uuid_enc=urlencode($_GET["uuid"]);
		$uuid_name="&nbsp;".$q->uuid_to_host($_GET["uuid"]);
	}
	$new_entry=$tpl->_ENGINE_parse_body("{new_computer}");
	$t=time();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$lastest_scan=$tpl->_ENGINE_parse_body("{latest_scan}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$directories=$tpl->_ENGINE_parse_body("{directories}");
	$depth=$tpl->_ENGINE_parse_body("depth");
	$OSNAME=$tpl->_ENGINE_parse_body("{OSNAME}");
	$all=$tpl->_ENGINE_parse_body("{all}");
	$import=$tpl->javascript_parse_text("{import_artica_computers}");
	$orders=$tpl->javascript_parse_text("{orders}");
	$zDate=$tpl->javascript_parse_text("{zDate}");
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : AddComputer$t},
	{name: '$all', bclass: 'search', onpress : all_scan$t},
	],	";
	$buttons=null;
	$uri="$page?search=yes&uuid=$uuid_enc&t=$t";
	
	$html="
	<table class='TABLE_META_ORDERS_LIST' style='display: none' id='TABLE_META_ORDERS_LIST' style='width:99%'></table>
<script>
var mem$t='';
$(document).ready(function(){
$('#TABLE_META_ORDERS_LIST').flexigrid({
	url: '$uri',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'zDate', width :147, sortable : true, align: 'left'},
		{display: '$orders', name : 'ordersubject', width :636, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width :51, sortable : false, align: 'center'},
	 	

	],
	$buttons

	searchitems : [
		{display: '$orders', name : 'ordersubject'},
		{display: '$zDate', name : 'zDate'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$orders:$uuid_name</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});   
});

function AddComputer$t(){
	YahooUser(1051,'domains.edit.user.php?userid=newcomputer$&ajaxmode=yes','New computer');
}

function lastest_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri&latest-scan=yes'}).flexReload(); 
}

function ImportComputer$t(){
	YahooWin3('450','$page?artica-importlist-popup=yes','$import');
}

function all_scan$t(){
	$('#flexRT$t').flexOptions({url: '$uri'}).flexReload(); 
}
</script>
";
	
	echo $html;
	
}


function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_meta();	
	
	$fontsize="14px";
	$cs=0;
	$page=1;
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$_POST["query"]=trim($_POST["query"]);
	
	
	
	
	$FORCE=1;
	if($_GET["uuid"]<>null){
		$FORCE="uuid='{$_GET["uuid"]}'";
	}
	
	$search='%';
	$table="metaorders";
	$page=1;
	
	
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}		
	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE  $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	
		$total = $ligne["TCOUNT"];
	
	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}
	
	
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";

	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	if(!$q->ok){json_error_show($q->mysql_error."<br>$sql",0);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}

	$uuid=urlencode($_GET["uuid"]);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?delete-js={$ligne["orderid"]}')");
		$cs++;
		$ss=array();
		$ordercontent=unserialize(base64_decode($ligne["ordercontent"]));
		while (list ($a, $b) = each ($ordercontent) ){
			$ss[]="<strong>$a = &laquo;$b&raquo;</strong>";
		}
		
		//$jslink="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?mac-js=$macenc&uuid=$uuid&t={$_GET["t"]}');\" style='font-size:$fontsize;text-decoration:underline'>";
		
	$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array(
		"<span style='font-size:$fontsize'>$jslink{$ligne["zDate"]}</a></span>",
		"<span style='font-size:18px'>$jslink{$ligne["ordersubject"]}<br></span><span style='font-size:12px'>".@implode("<br>", $ss)."</span>",
		"<span style='font-size:$fontsize'>$jslink{$delete}</a></span>",
		)
		);		

	}

echo json_encode($data);
	
	
}




