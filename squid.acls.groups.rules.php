<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');$GLOBALS["VERBOSE"]=true;}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.external.ad.inc');
	include_once('ressources/class.ldap-extern.inc');
	
	$usersmenus=new usersMenus();
	if(!$usersmenus->AsDansGuardianAdministrator){
		$tpl=new templates();
		$alert=$tpl->javascript_parse_text('{ERROR_NO_PRIVS}');
		echo "alert('$alert');";
		die();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["search"])){search();exit;}
	js();
	
	
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$ID=$_GET["ID"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupName=utf8_encode($ligne["GroupName"]);
	echo "YahooWin('590','$page?popup=yes&ID=$ID','$GroupName')";
}

function popup(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$items=$tpl->_ENGINE_parse_body("{items}");
	$new_item=$tpl->_ENGINE_parse_body("{new_item}");
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	$t=time();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$GroupName=utf8_encode($ligne["GroupName"]);
	
	$html="
	<table class='table-$t' style='display: none' id='table-$t' style='width:99%;margin:-1px'></table>
<script>
function flexigridStart$t(){
	$('#table-$t').flexigrid({
	url: '$page?search=yes&ID=$ID',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$rules</strong>', name : 'aclname', width : 537, sortable : true, align: 'left'},
	],
	
	searchitems : [
	{display: '$rules', name : 'aclname'},
	],
	sortname: 'aclname',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:22px>$GroupName</strong>',
	useRp: true,
	rp: 15,
	rpOptions: [10, 15,20, 30, 50,100,200,300,500],
	showTableToggleBtn: false,
	width: '99%',
	height: 350,
	singleSelect: true
	
	});
}
setTimeout('flexigridStart$t()',800);
</script>
";
echo $html;
}
function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ID=$_GET["ID"];
	
	$search='%';
	$table="(SELECT webfilters_sqacls.ID,webfilters_sqacls.aclname FROM  webfilters_sqacllinks,webfilters_sqacls WHERE webfilters_sqacllinks.gpid=$ID AND webfilters_sqacllinks.aclid=webfilters_sqacls.ID) as t";
		$page=1;
		
		
		if(isset($_POST["sortname"])){
			if($_POST["sortname"]<>null){
				$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
			}
		}
	
		if (isset($_POST['page'])) {$page = $_POST['page'];}
		$searchstring=string_to_flexquery();
	
		if($searchstring<>null){
	
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		$sql="SELECT *  FROM $table WHERE 1 $searchstring  $ORDER $limitSql";
		writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql);
		if(!$q->ok){json_error_show("$q->mysql_error",2);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
		if(mysql_num_rows($results)==0){json_error_show("No data...",2);}
	
	
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$aclname=utf8_encode($ligne['aclname']);
			
			$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.acls-rules.php?Addacl-js=yes&ID={$ligne['ID']}');\"
			style='font-size:22px;text-decoration:underline'>";
			
			
			$data['rows'][] = array(
					'id' => "item{$ligne['ID']}",
					'cell' => array(
							"$js$aclname</a>",

					)
			);
		}
	
	
		echo json_encode($data);
	}