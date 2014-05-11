<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.mysql.archive.builder.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsMailBoxAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	if(isset($_GET["items-list"])){search();exit;}

	page();

function page(){

	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$t=time();
	$path=base64_decode($_GET["path"]);
	$md5path=md5($path);
	$veto_files_explain=$tpl->_ENGINE_parse_body("{veto_files_explain}");
	$veto_files_add_explain=$tpl->javascript_parse_text("{veto_files_add_explain}");
	$t=time();
	$from=$tpl->_ENGINE_parse_body("{from}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$mailto=$tpl->javascript_parse_text("{recipient}");
	$subject=$tpl->javascript_parse_text("{subject}");
	$help=$tpl->_ENGINE_parse_body("{online_help}");
	
	$title=$tpl->_ENGINE_parse_body(date("{l} d {F} Y"));
	
	$buttons="
	buttons : [
	{name: '$new_entry', bclass: 'Add', onpress : NewGItem$t},
	{name: '$help', bclass: 'Help', onpress : ItemHelp$t},
	],	";

	$buttons=null;
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?items-list=yes&t=$t&md5path=$md5path',
	dataType: 'json',
	colModel : [
	{display: '$zDate', name : 'zDate', width :146, sortable : true, align: 'left'},
	{display: '$from', name : 'mailfrom', width :149, sortable : true, align: 'left'},
	{display: '$mailto', name : 'mailto', width :149, sortable : true, align: 'left'},
	{display: '$subject', name : 'subject', width :430, sortable : true, align: 'left'},
	
	
	
	],
	$buttons

	searchitems : [
	{display: '$from', name : 'mailfrom'},
	{display: '$mailto', name : 'mailto'},
	{display: '$subject', name : 'subject'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]

});
});
</script>
";

echo $tpl->_ENGINE_parse_body($html);


}

function search(){

	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_mailarchive_builder();
	$sock=new sockets();

	$search='%';
	$table="`".date("Ymd")."`";
	$page=1;
	$FORCE_FILTER="";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();
	if($searchstring<>null){
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];

	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";

	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	if(mysql_num_rows($results)==0){json_error_show("$table no data",1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(!$q->ok){json_error_show($q->mysql_error);}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$zDate=$ligne["zDate"];
		$mailfrom=$ligne["mailfrom"];
		$mailto=$ligne["mailto"];
		$subject=utf8_encode($ligne["subject"]);
		$MessageID=$ligne["MessageID"];

		$data['rows'][] = array(
				'id' => "$MessageID",
				'cell' => array(
						"<span style='font-size:14px;color:$color'>$zDate</a></span>",
						"<span style='font-size:14px;color:$color'>$mailfrom</a></span>",
						"<span style='font-size:14px;color:$color'>$mailto</a></span>",
						"<span style='font-size:14px;color:$color'>$subject</a></span>",
				)
		);
	}


	echo json_encode($data);
}

/*MessageID,
zDate,
mailfrom,
mailfrom_domain,
subject,
MessageBody,
organization,
mailto,
file_path,
original_messageid,
message_size,
BinMessg,filename,filesize
$tableDest=date("Ymd",$timeMessage);

*/
	