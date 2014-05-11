<?php
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.computers.inc');
$users=new usersMenus();
if(!GetRights()){		
	$tpl=new templates();
	echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
	die();exit();
	}
	
	
	if(isset($_GET["items-list"])){search();exit;}
	page();
	
	function GetRights(){
		$users=new usersMenus();
		if($users->AsSystemAdministrator){return true;}
		if($users->ASDCHPAdmin){return true;}
	}	
	
function page(){
	
	$tpl=new templates();
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=515;
	$TB_WIDTH=875;
	$path=base64_decode($_GET["path"]);
	$md5path=md5($path);
	$veto_files_explain=$tpl->_ENGINE_parse_body("{veto_files_explain}");
	$veto_files_add_explain=$tpl->javascript_parse_text("{veto_files_add_explain}");
	$t=time();
	$event=$tpl->_ENGINE_parse_body("{events}");
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$ask_delete_rule=$tpl->javascript_parse_text("{ask_delete_rule}");
	$help=$tpl->_ENGINE_parse_body("{online_help}");
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
		{display: '$event', name : 'description', width :683, sortable : true, align: 'left'},

	],
	$buttons

	searchitems : [
		{display: '$event', name : 'description'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$path',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
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
	$q=new mysql();
	$sock=new sockets();		
	
	$search='%';
	$table="dhcpd_logs";
	$database="artica_events";
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
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}	
	$computers=new computers();
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		$uid=null;
		$mac=null;
		$js="zBlur()";
		if(preg_match("#to\s+([0-9a-z:]+)\s+via#",$ligne["description"],$re)){$mac=$re[1];}
		if(preg_match("#from\s+([0-9a-z:]+)\s+via#",$ligne["description"],$re)){$mac=$re[1];}
		
		if($mac<>null){
		
			$uid=$computers->ComputerIDFromMAC($mac);
			if($uid<>null){
				$js=MEMBER_JS($uid,1,1);
				$uri="<a href=\"javascript:blur();\" OnClick=\"$js\" style='font-size:14px;font-weight:bold;color:$color;text-decoration:underline'>$mac</a>&nbsp;<span style='font-size:11px'>($uid)</span>";
				$ligne["description"]=str_replace($mac,$uri,$ligne["description"]);
			}
		}
		
	
	$data['rows'][] = array(
		'id' => "$ID",
		'cell' => array(
			"<span style='font-size:14px;color:$color'>$urljs{$ligne["zDate"]}</a></span>",
			"<span style='font-size:14px;color:$color'>{$ligne["description"]}</a></span>",
			)
		);
	}
	
	
echo json_encode($data);	
}