<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){die();}
	if(isset($_GET["list-table"])){list_table();exit;}
	if(isset($_GET["list-month"])){list_month();exit;}
	
	
	
	if(isset($_GET["today"])){page();exit;}
	if(isset($_GET["NOW"])){page(true);exit;}
	if(isset($_GET["month"])){page_month();exit;}
	if(isset($_GET["hier"])){page_hier();exit;}

tabs();




function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql_postfix_builder();
	
	$hier=strtotime($q->HIER()." 00:00:00");
	
	$table_month="mgreym_".date("Ym");
	$table_hier=$table="mgreym_".date("Ymd",$hier);
	
	if($q->TABLE_EXISTS("MGREY_RTT")){
		$array["NOW"]='{this_hour}';
	}
	
	$array["today"]='{today}';
	if($q->TABLE_EXISTS($table_month)){
		$array["hier"]='{yesterday}';
	}
	
	if($q->TABLE_EXISTS($table_month)){
		$array["month"]='{this_month}';
	}
	
	$fontsize=18;
	while (list ($num, $ligne) = each ($array) ){
	
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			
	}
	
	
	
	$t=time();
	//
	
	echo build_artica_tabs($tab, "main_artica_miltergreylist_cnx",1100)."<script>LeftDesign('key-256-opac20');</script>";	
	
	
}
function page_month(){

	$table="mgreym_".date("Ym");

	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$q=new mysql_postfix_builder();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$build_parameters=$tpl->javascript_parse_text("{build_parameters}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$import=$tpl->javascript_parse_text("{import}");
	$title=$tpl->javascript_parse_text("{this_month}, {total}:&nbsp;").  $q->COUNT_ROWS($table);
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mailfrom=$tpl->javascript_parse_text("{sender}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$status=$tpl->javascript_parse_text("{status}");
	$time=$tpl->javascript_parse_text("{days}");
	$mailto=$tpl->javascript_parse_text("{recipient}");
	$action=$tpl->javascript_parse_text("{action}");
	$all=$tpl->javascript_parse_text("{all}");
	$arrayA["accept"]=$tpl->javascript_parse_text("{sent}");
	$arrayA["tempfail"]=$tpl->javascript_parse_text("{greylist}");
	$arrayA["reject"]=$tpl->javascript_parse_text("{blacklist}");


	// Hour | cnx | hostname                                       | domain                    |
	$buttons="
	buttons : [
	{name: '$all', bclass: 'Search', onpress : all$t},
	{name: '{$arrayA["accept"]}', bclass: 'Search', onpress : whitelist$t},
	{name: '{$arrayA["reject"]}', bclass: 'Search', onpress : blacklist$t},
	{name: '{$arrayA["tempfail"]}', bclass: 'Search', onpress : greylist$t},
	],";


	$html="


	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>

	<script>
	var memid$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?list-month=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
	colModel : [

	{display: '$time', name : 'zhour', width : 50, sortable : true, align: 'left'},
	{display: 'CNX', name : 'hits', width :69, sortable : true, align: 'left'},
	{display: '$hostname', name : 'senderhost', width : 123, sortable : false, align: 'left'},
	{display: '$mailfrom', name : 'mailfrom', width : 260, sortable : false, align: 'left'},
	{display: '$mailto', name : 'mailto', width : 237, sortable : false, align: 'left'},
	{display: '$action', name : 'failed', width : 123, sortable : false, align: 'left'},

	],
	$buttons
	searchitems : [
	{display: '$hostname', name : 'senderhost'},
	{display: '$mailfrom', name : 'mailfrom'},
	{display: '$mailto', name : 'mailto'},
	],
	sortname: 'zhour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function all$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-month=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t'}).flexReload();
}

function blacklist$t(){
$('#flexRT$t').flexOptions({url: '$page?list-month=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=reject'}).flexReload();
}
function whitelist$t(){
$('#flexRT$t').flexOptions({url: '$page?list-month=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=accept'}).flexReload();
}
function greylist$t(){
$('#flexRT$t').flexOptions({url: '$page?list-month=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=tempfail'}).flexReload();
}

</script>";
echo $html;
}

function page_hier(){
	$q=new mysql_postfix_builder();
	
	
	$hier=strtotime($q->HIER()." 00:00:00");
	$table="mgreym_".date("Ymd",$hier);
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	
	$build_parameters=$tpl->javascript_parse_text("{build_parameters}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$import=$tpl->javascript_parse_text("{import}");
	$title=$tpl->javascript_parse_text("{today}, {total}:&nbsp;").  $q->COUNT_ROWS($table);
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mailfrom=$tpl->javascript_parse_text("{sender}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$status=$tpl->javascript_parse_text("{status}");
	$time=$tpl->javascript_parse_text("{time}");
	$mailto=$tpl->javascript_parse_text("{recipient}");
	$action=$tpl->javascript_parse_text("{action}");
	$all=$tpl->javascript_parse_text("{all}");
	$arrayA["accept"]=$tpl->javascript_parse_text("{sent}");
	$arrayA["tempfail"]=$tpl->javascript_parse_text("{greylist}");
	$arrayA["reject"]=$tpl->javascript_parse_text("{blacklist}");
	
	
	// Hour | cnx | hostname                                       | domain                    |
	$buttons="
	buttons : [
	{name: '$all', bclass: 'Search', onpress : all$t},
	{name: '{$arrayA["accept"]}', bclass: 'Search', onpress : whitelist$t},
	{name: '{$arrayA["reject"]}', bclass: 'Search', onpress : blacklist$t},
	{name: '{$arrayA["tempfail"]}', bclass: 'Search', onpress : greylist$t},
	],";
	
	
	$html="
	
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>
	
	<script>
	var memid$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
			url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t',
	dataType: 'json',
		colModel : [
	
		{display: '$time', name : 'zhour', width : 50, sortable : true, align: 'left'},
		{display: 'CNX', name : 'hits', width :69, sortable : true, align: 'left'},
		{display: '$hostname', name : 'senderhost', width : 123, sortable : false, align: 'left'},
		{display: '$mailfrom', name : 'mailfrom', width : 260, sortable : false, align: 'left'},
		{display: '$mailto', name : 'mailto', width : 237, sortable : false, align: 'left'},
		{display: '$action', name : 'failed', width : 123, sortable : false, align: 'left'},
	
		],
		$buttons
		searchitems : [
		{display: '$hostname', name : 'senderhost'},
		{display: '$mailfrom', name : 'mailfrom'},
		{display: '$mailto', name : 'mailto'},
		],
		sortname: 'zhour',
		sortorder: 'desc',
		usepager: true,
		title: '<span style=font-size:18px>$title</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: '99%',
		height: 500,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	function all$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hier=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t'}).flexReload();
	}
	
			function blacklist$t(){
			$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hier=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=reject'}).flexReload();
	}
	function whitelist$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hier=yes&hier=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=accept'}).flexReload();
	}
	function greylist$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hier=yes&hier=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=tempfail'}).flexReload();
	}
	
	</script>";
	echo $html;
	}	
	



function page($NOW=false){
	
$table="mgreyd_".date("Ymd");

if($NOW){$NOW_Q="&now=yes";$table="MGREY_RTT";}
	
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$sock=new sockets();
	$tt=$_GET["t"];
	$t=time();
	$q=new mysql_postfix_builder();
	$are_you_sure_to_delete=$tpl->javascript_parse_text("{are_you_sure_to_delete}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$items=$tpl->_ENGINE_parse_body("{items}");

	$build_parameters=$tpl->javascript_parse_text("{build_parameters}");
	$new_item=$tpl->javascript_parse_text("{new_item}");
	$import=$tpl->javascript_parse_text("{import}");
	$title=$tpl->javascript_parse_text("{today}, {total}:&nbsp;").  $q->COUNT_ROWS($table);
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mailfrom=$tpl->javascript_parse_text("{sender}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$status=$tpl->javascript_parse_text("{status}");
	$time=$tpl->javascript_parse_text("{time}");
	$mailto=$tpl->javascript_parse_text("{recipient}");
	$action=$tpl->javascript_parse_text("{action}");
	$all=$tpl->javascript_parse_text("{all}");
	$arrayA["accept"]=$tpl->javascript_parse_text("{sent}");
	$arrayA["tempfail"]=$tpl->javascript_parse_text("{greylist}");
	$arrayA["reject"]=$tpl->javascript_parse_text("{blacklist}");
	
	
	// Hour | cnx | hostname                                       | domain                    | 
	$buttons="
	buttons : [
		{name: '$all', bclass: 'Search', onpress : all$t},
		{name: '{$arrayA["accept"]}', bclass: 'Search', onpress : whitelist$t},
		{name: '{$arrayA["reject"]}', bclass: 'Search', onpress : blacklist$t},
		{name: '{$arrayA["tempfail"]}', bclass: 'Search', onpress : greylist$t},
	],";


	$html="


	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%;'></table>

	<script>
	var memid$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t{$NOW_Q}',
	dataType: 'json',
	colModel : [
	
	{display: '$time', name : 'zhour', width : 50, sortable : true, align: 'left'},
	{display: 'CNX', name : 'hits', width :69, sortable : true, align: 'left'},
	{display: '$hostname', name : 'senderhost', width : 123, sortable : false, align: 'left'},
	{display: '$mailfrom', name : 'mailfrom', width : 260, sortable : false, align: 'left'},
	{display: '$mailto', name : 'mailto', width : 237, sortable : false, align: 'left'},
	{display: '$action', name : 'failed', width : 123, sortable : false, align: 'left'},
	
	],
	$buttons
	searchitems : [
	{display: '$hostname', name : 'senderhost'},
	{display: '$mailfrom', name : 'mailfrom'},
	{display: '$mailto', name : 'mailto'},
	],
	sortname: 'zhour',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 500,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function all$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t'}).flexReload(); 
}

function blacklist$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=reject'}).flexReload(); 
}
function whitelist$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=accept'}).flexReload();  
}
function greylist$t(){
	$('#flexRT$t').flexOptions({url: '$page?list-table=yes&hostname=$hostname&ou={$_GET["ou"]}&t=$t&failed=tempfail'}).flexReload(); 
}

</script>";
	echo $html;
}

function list_month(){

	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();
	if(!isset($_GET["failed"])){$_GET["failed"]=null;}
	$table="mgreym_".date("Ym");
	$q=new mysql_postfix_builder();
	
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table");}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}
	$t=$_GET["t"];
	$database=null;
	$FORCE_FILTER=1;
	if($_GET["failed"]<>null){$FORCE_FILTER="`failed`='{$_GET["failed"]}'";}

	
	$table="(SELECT COUNT(hits) as hits, DAY(zday) as zhour,mailfrom,mailto,domainfrom,domainto,senderhost,failed
			FROM $table GROUP BY zhour,mailfrom,mailto,domainfrom,domainto,senderhost,failed) as t";
	
	
	

	

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $searchstring");}

	}else{
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER");}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=1;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
	$today=date('Y-m-d');
	$style="font-size:14px;";
	$color="black";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");

	$arrayA["accept"]=$tpl->javascript_parse_text("{sent}");
	$arrayA["tempfail"]=$tpl->javascript_parse_text("{greylist}");
	$arrayA["reject"]=$tpl->javascript_parse_text("{blacklist}");

	//Hour | cnx | hostname                                       | domain                    |

	while ($ligne = mysql_fetch_assoc($results)) {

		$style="background-color:#03BA2F;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		$failed=$ligne["failed"];


		if($failed=="tempfail"){
			$style="background-color:#949494;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		}
		if($failed=="reject"){
			$style="background-color:#DD1212;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		}

		$md=md5(serialize($ligne));
		$cells=array();
		$cells[]="<span style='font-size:14px;'>{$ligne["zhour"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["hits"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["senderhost"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["mailfrom"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["mailto"]}</span>";
		$cells[]="<span style='font-size:14px;'><div style='$style'>{$arrayA[$failed]}</div></span>";



			
			
		$data['rows'][] = array(
				'id' =>$line["zmd5"],
				'cell' => $cells
		);


	}

	echo json_encode($data);
}

function list_table(){
	$Now=false;
	$MyPage=CurrentPageName();
	$page=1;
	$tpl=new templates();
	if(!isset($_GET["failed"])){$_GET["failed"]=null;}
	$table="mgreyd_".date("Ymd");
	$q=new mysql_postfix_builder();
	if(isset($_GET["hier"])){
		$hier=strtotime($q->HIER()." 00:00:00");
		$table="mgreyd_".date("Ymd",$hier);
	}
	
	if(isset($_GET["now"])){
		$Now=true;
		$table="MGREY_RTT";
	}
	
	
	if(!$q->TABLE_EXISTS($table)){json_error_show("$table no such table");}
	
	$t=$_GET["t"];
	$database=null;
	$FORCE_FILTER=1;
	if($_GET["failed"]<>null){$FORCE_FILTER="`failed`='{$_GET["failed"]}'";}

	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No item");}

	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}

	if (isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		if(!$q->ok){json_error_show("$q->mysql_error");}
		$total = $ligne["TCOUNT"];
		if($total==0){json_error_show("No rows for $searchstring");}

	}else{
		$total = $q->COUNT_ROWS($table, $database);
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=1;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";


	$sql="SELECT *  FROM $table WHERE $FORCE_FILTER $searchstring $ORDER $limitSql";

	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){json_error_show("$q->mysql_error<hr>$sql<hr>");}


	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	if(mysql_num_rows($results)==0){json_error_show("No data...",1);}
	$today=date('Y-m-d');
	$style="font-size:14px;";
	$color="black";
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	
	$arrayA["accept"]=$tpl->javascript_parse_text("{sent}");
	$arrayA["tempfail"]=$tpl->javascript_parse_text("{greylist}");
	$arrayA["reject"]=$tpl->javascript_parse_text("{blacklist}");
	
	//Hour | cnx | hostname                                       | domain                    | 
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$style="background-color:#03BA2F;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		$failed=$ligne["failed"];
		
		
		if($failed=="tempfail"){
			$style="background-color:#949494;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		}
		if($failed=="reject"){
			$style="background-color:#DD1212;color:#FFFFFF;margin:-5px;padding:5px;font-weight:bold;text-transform:capitalize;font-size:14px;";
		}		
		
		if($Now){$ligne["hits"]=1;}
		$text_hour="{$ligne["zhour"]}h";
		if($Now){$text_hour=date("H:i:s",strtotime($ligne["ztime"]));}
		
		
		$md=md5(serialize($ligne));
		$cells=array();
		$cells[]="<span style='font-size:14px;'>$text_hour</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["hits"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["senderhost"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["mailfrom"]}</span>";
		$cells[]="<span style='font-size:14px;'>{$ligne["mailto"]}</span>";
		$cells[]="<span style='font-size:14px;'><div style='$style'>{$arrayA[$failed]}</div></span>";
		
		

			
			
		$data['rows'][] = array(
				'id' =>$line["zmd5"],
				'cell' => $cells
		);


	}

	echo json_encode($data);
}
