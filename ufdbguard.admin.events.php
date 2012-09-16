<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["ufdbguard-artica"])){page();exit;}
if(isset($_GET["ufdbguard-list-search"])){search();exit;}
if(isset($_GET["ufdbguard-list-catz"])){catzchoose();exit;}
tabs();



function tabs(){
	
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["ufdbguard-events"]='{service_events}';
	$array["ufdbguard-artica"]='{artica_events}';
	
	

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		if($num=="ufdbguard-events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"ufdbguard.sevents.php\" style='font-size:14px;font-weight:normal'><span>$ligne</span></a></li>\n");
			continue;			
		}

		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_ufdbguards_events_tab style='width:101%;overflow:auto;margin:-10px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_ufdbguards_events_tab').tabs();
			});
		</script>";	
}




function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	
	$tpl=new templates();
	$t=time();
	$date=$tpl->_ENGINE_parse_body("{date}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$form=$tpl->_ENGINE_parse_body("
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{website}:</td>
		<td>". Field_text("WEBTESTS",null,"font-size:14px;padding:3px;border:2px solid #808080",
	null,null,null,false,"CheckSingleSite(event)")."</td>
	</tr>
	</table>
	");
	

	$buttons="
	buttons : [
	{name: '$category', bclass: 'Catz', onpress : ChangeCategory$t},
	
	],";	
	
	$html="
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?ufdbguard-list-search=yes&category={$_GET["category"]}',
	dataType: 'json',
	colModel : [
		{display: '$date', name : 'zDate', width : 142, sortable : true, align: 'left'},	
		{display: '$events', name : 'description', width : 627, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$events', name : 'description'},
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 813,
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function ChangeCategory$t(){
	YahooWin5('450','$page?ufdbguard-list-catz=yes&t=$t','$category');
}

	</script>";
	echo $html;
	

	
	
}

function catzchoose(){
$q=new mysql();
$page=CurrentPageName();
$tpl=new templates();
	$sql="SELECT category FROM ufdbguard_admin_events GROUP BY category ORDER BY category";
	$results=$q->QUERY_SQL($sql,"artica_events");	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$cat[$ligne["category"]]=$ligne["category"];
	}
	$cat[null]="{select}";
$t=$_GET["t"];
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{category}:</td>
		<td>". Field_array_Hash($cat, "CatzChoose",null,"CatzChoosePerform()","style:font-size:14px")."</td>
	</tr>
	</table>
	<script>
		function CatzChoosePerform(){
			var catz=document.getElementById('CatzChoose').value;
			$('#$t').flexOptions({ url: '$page?ufdbguard-list-search=yes&category='+catz }).flexReload();
			YahooWin5Hide();
		}
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);

	
	
}

function search(){
	
	$Mypage=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$table="ufdbguard_admin_events";
	$database="artica_events";
	$FORCE=1;
	if($_GET["category"]<>null){$FORCE=" `category`='{$_GET["category"]}'";}
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("No data");}
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT * FROM $table WHERE $FORCE $searchstring";
		$results=$q->QUERY_SQL($sql,$database);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}else{
		$sql="SELECT COUNT(*) AS TCOUNT FROM $table WHERE $FORCE";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	$style="style='font-size:14px;'";
	
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE $FORCE $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	if(mysql_num_rows($results)==0){
		json_error_show("Category: {$_GET["category"]} $searchstring No data");
	}
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	

  while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["description"]=$tpl->_ENGINE_parse_body($ligne["description"]);
		$textadd=$tpl->_ENGINE_parse_body("<div style='font-size:11px;margin-left:-5px'><strong>{$ligne["category"]}</strong> - {$ligne["filename"]} - {$ligne["function"]}() {line}:{$ligne["line"]}</div>");
		$ligne["description"]=str_replace("\n",",<br>", $ligne["description"]);
		$data['rows'][] = array(
			'id' => md5("{$ligne["zDate"]}{$ligne["description"]}"),
			'cell' => array(
				 "<span $style>{$ligne["zDate"]}</span>",
				"<span $style>".table_error_showZoom("{$ligne["description"]}",0)."$textadd</span>")
			);		
		
		
	}
echo json_encode($data);	

}