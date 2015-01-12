<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["search-stats-forms"])){left_forms();exit;}
	if(isset($_GET["search-stats-results"])){right_datas();exit;}
	if(isset($_GET["ok"])){if(isset($_POST["qtype"])){json();exit;}}
	if(isset($_GET["familysite-show"])){jstable_familysite();exit;}
	if(isset($_GET["familysite"])){json_familysite();exit;}
	if(isset($_GET["site-infos"])){site_infos();exit;}
	if(isset($_GET["whoissave-js"])){whoissave_js();exit;}
	if(isset($_POST["whoissave"])){whoissave_perform();exit;}
	if(isset($_GET["search-stats-categories"])){site_infos_categories();exit;}
	if(isset($_POST["action_delete_from_category"])){action_delete_from_category();exit;}
	if(isset($_POST["action_compile_category"])){action_compile_category();exit;}
	if(isset($_GET["ChangeFilterCatz-popup"])){ChangeFilterCatz_popup();exit;}
	
	
	// json();exit;}
	start();
	
function whoissave_js(){
	$page=CurrentPageName();
	$idcallbackJS=null;
	$idcallback=$_GET["idcallback"];
	if($idcallback<>null){
		$idcallbackJS="if(document.getElementById('$idcallback')){LoadAjax('$idcallback','squid.search.statistics.php?site-infos={$_GET["whoissave-js"]}&idcallback=$idcallback&disposition={$_GET["disposition"]}&gen-thumbnail={$_GET["gen-thumbnail"]}');}";
	}
	
	$html="
var x_WhoisSave= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	if(document.getElementById('search-stats-forms')){SearchLeftInfos();}
	$idcallbackJS
}		
	
	
	function WhoisSave(){
		var XHR = new XHRConnection();
		XHR.appendData('whoissave','{$_GET["whoissave-js"]}');
		AnimateDiv('search-stats-forms');
		XHR.sendAndLoad('$page', 'POST',x_WhoisSave);	
	}	
	WhoisSave();
	";
	echo $html;
}
	
function start(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="
	
	
	
	<div style='width:104%;margin:-10px;margin-left:-15px'>
			<div style='width:98%' class=form>
			<div id='search-stats-results'></div>
			<div class=text-info>{SQUID_SEARCH_STATS_EXPLAIN}</div>
			</div>
	</div>

	<script>
		
		LoadAjax('search-stats-results','$page?search-stats-results=yes');
	</script>
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function right_datas(){
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$categories=$tpl->_ENGINE_parse_body("{categories}");
	
$buttons="buttons : [
		{name: '$categories', bclass: 'Search', onpress : ChangeFilterCatz},
		],	";
$buttons=null;	
	
	$html="
	<table class='flex1' style='display: none' id='flex1' style='width:99%'></table>
	<div id='table-1-selected'></div>
<script>
$(document).ready(function(){
$('#flex1').flexigrid({
	url: '$page?ok=yes',
	dataType: 'json',
	colModel : [
		{display: '$webservers', name : 'familysite', width :653, sortable : true, align: 'left'},
		{display: '$hits', name : 'HitsNumber', width : 73, sortable : true, align: 'left'},
		{display: '$size', name : 'Querysize', width : 60, sortable : true, align: 'left'}
		],
		
		

	searchitems : [
		{display: '$webservers', name : 'familysite'}
		],
	sortname: 'HitsNumber',
	sortorder: 'desc',
	usepager: true,
	title: '$webservers',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 845,
	height: 300,
	singleSelect: true
	
	});   
});

function SelectGrid(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
		LoadAjax('table-1-selected','$page?familysite-show='+id);
	}
$('flexRT').remove();
		
		
		
 }
 
 function ChangeFilterCatz(){
	YahooWin3('550','$page?ChangeFilterCatz-popup=yes','$categories');
}
 
 function SelectFamilysiteGridInline(sitename) {
	
		LoadAjax('table-1-selected','$page?familysite-show='+sitename);
	
	
 }

</script>
	
	
	";
	
	echo $html;
	
}

function json_categories(){
	
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$table="visited_sites_catz";
	$page=1;
	$FORCE_FILTER="";
	
	if($q->COUNT_ROWS($table)==0){echo "<H2>".$tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}")."</H2>";return;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page =$_POST['page'];}
	
	$table="(SELECT visited_sites_catz.category,visited_sites.HitsNumber,visited_sites.Querysize,visited_sites.familysite FROM visited_sites_catz,visited_sites
	WHERE visited_sites_catz.familysite=visited_sites.familysite AND visited_sites_catz.category = '{$_GET["category"]}') as t";
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos(" $search", "%")>0){
			$FILTER="AND (`familysite` LIKE '$search') $FORCE_FILTER";
		}else{
			$FILTER="AND (`familysite` = '$search') $FORCE_FILTER";
		}
		
		$sql="SELECT COUNT(familysite) as TCOUNT FROM $table WHERE (`familysite` LIKE '$search')$FORCE_FILTER";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		if($FORCE_FILTER<>null){
			$sql="SELECT COUNT(familysite) as TCOUNT FROM `visited_sites` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];	
			
		}else{
			$total = $q->COUNT_ROWS("visited_sites");
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if($FILTER==null){$FILTER=$FORCE_FILTER;}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT familysite,SUM(HitsNumber) as HitsNumber,SUM(Querysize) AS Querysize,category FROM $table GROUP BY familysite,category HAVING 1 $FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["Querysize"]=FormatBytes($ligne["Querysize"]/1024);
		
		$www="<a href=\"javascript:blur()\" OnClick=\"javascript:SelectFamilysiteGridInline('{$ligne['familysite']}');\" style='text-decoration:underline;font-size:14px'>{$ligne['familysite']}</a>";
		$ligne['HitsNumber']=numberFormat($ligne['HitsNumber'],0,""," ");
		$data['rows'][] = array(
		'id' => $ligne['familysite'].$text1,
		'cell' => array($www, $ligne['HitsNumber'], $ligne["Querysize"], $ligne['familysite'])
		);
	}
echo json_encode($data);	
	
}

function json(){
	$tpl=new templates();
$q=new mysql_squid_builder();	
	
	$table="visited_sites";
	$page=1;
	$ORDER="ORDER BY HitsNumber DESC";
	if(isset($_GET["category"])){
		if(trim($_GET["category"])<>null){
			json_categories();
			return;
		}
	}
	
	
	if($q->COUNT_ROWS($table)==0){echo "<H2>".$tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}")."</H2>";return;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page =$_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		if(strpos(" $search", "%")>0){
			$FILTER="AND (`familysite` LIKE '$search') $FORCE_FILTER";
		}else{
			$FILTER="AND (`familysite` = '$search') $FORCE_FILTER";
		}
		
		$sql="SELECT COUNT(familysite) as TCOUNT FROM `$table` WHERE (`familysite` LIKE '$search')$FORCE_FILTER";
		
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		if($FORCE_FILTER<>null){
			$sql="SELECT COUNT(familysite) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
			$total = $ligne["TCOUNT"];	
			
		}else{
			$total = $q->COUNT_ROWS("visited_sites");
		}
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	if($FILTER==null){$FILTER=$FORCE_FILTER;}

	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT familysite,SUM(HitsNumber) as HitsNumber,SUM(Querysize) AS Querysize$field1 FROM `$table` GROUP BY familysite$group1 HAVING 1 $FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["Querysize"]=FormatBytes($ligne["Querysize"]/1024);
		
		$www="<a href=\"javascript:blur()\" OnClick=\"javascript:SelectFamilysiteGridInline('{$ligne['familysite']}');\" style='text-decoration:underline;font-size:14px'>{$ligne['familysite']}</a>";
		$ligne['HitsNumber']=numberFormat($ligne['HitsNumber'],0,""," ");
		$data['rows'][] = array(
		'id' => $ligne['familysite'].$text1,
		'cell' => array($www, $ligne['HitsNumber'], $ligne["Querysize"], $ligne['familysite'])
		);
	}
echo json_encode($data);
	
}

function json_familysite(){
$q=new mysql_squid_builder();	
	$search='%';
	$table="visited_sites";
	$page=1;
	$ORDER="ORDER BY HitsNumber DESC";
	
	
	if($q->COUNT_ROWS($table)==0){echo "<H2>".$tpl->_ENGINE_parse_body("TABLE:$table<br>{error_no_datas}")."</H2>";return;}
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$sql="SELECT COUNT(sitename) as TCOUNT FROM `$table` WHERE (`sitename` LIKE '$search') AND familysite='{$_GET["familysite"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(sitename) as TCOUNT FROM `$table` WHERE familysite='{$_GET["familysite"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT sitename,HitsNumber ,Querysize,category FROM `$table` WHERE (`sitename` LIKE '$search') AND familysite='{$_GET["familysite"]}' $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
$div="<div style=\"padding-top:10px;font-size:14px\">";
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$thumbs=$q->GET_THUMBNAIL($ligne['sitename'],48);
		$ligne["Querysize"]=FormatBytes($ligne["Querysize"]/1024);
		$ligne['HitsNumber']=numberFormat($ligne['HitsNumber'],0,""," ");
		$data['rows'][] = array(
		'id' => $ligne['sitename'],
		'cell' => array($thumbs,"$div{$ligne['sitename']}</div>", $div.$ligne['HitsNumber']."</div>", $div.$ligne["Querysize"]."</div>", $div.$ligne['category']."</div>")
		);
	}
echo json_encode($data);	
}

function jstable_familysite(){
	$page=CurrentPageName();
	$tpl=new templates();
	$webservers=$tpl->_ENGINE_parse_body("{webservers} - {$_GET["familysite-show"]}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$categories=$tpl->_ENGINE_parse_body("{categories}");
	$html="
	<table class='flex2' style='display: none' id='flex2' style='width:99%'></table>
	
<script>
$(document).ready(function(){
$('#flex2').flexigrid({
	url: '$page?familysite={$_GET["familysite-show"]}',
	dataType: 'json',
	colModel : [
		{display: 'thumbs', name : 'thumbs', width :48, sortable : false, align: 'center'},
		{display: '$webservers', name : 'sitename', width :391, sortable : true, align: 'left'},
		{display: '$hits', name : 'HitsNumber', width : 65, sortable : true, align: 'left'},
		{display: '$size', name : 'Querysize', width : 60, sortable : true, align: 'left'},
		{display: '$category', name : 'category', width : 200, sortable : true, align: 'left'}

		],
		
	
		
	searchitems : [
		{display: '$webservers', name : 'sitename'}
		],
	sortname: 'HitsNumber',
	sortorder: 'desc',
	usepager: true,
	title: '{$_GET["familysite-show"]} $webservers ',
	useRp: true,
	rp: 15,
	showTableToggleBtn: true,
	width: 845,
	height: 300,
	singleSelect: true
	
	});   
});
function SearchLeftInfos() {
	LoadAjax('search-stats-forms','$page?site-infos={$_GET["familysite-show"]}');
}

function ZoomWebSite(sitename) {
	LoadAjax('table-1-selected','$page?familysite='+sitename);
}



SearchLeftInfos();
</script>
	
	
	";
	
	echo $html;	
	
}

function ChangeFilterCatz_popup(){
	$page=CurrentPageName();
	$tpl=new templates(); 
	$q=new mysql_squid_builder();
	$t=time();
	if(!isset($_SESSION["ChangeFilterCatz"])){
	$sql="SELECT category FROM visited_sites GROUP BY category";
	$results = $q->QUERY_SQL($sql);
		while ($ligne = mysql_fetch_assoc($results)) {
			if(strpos($ligne["category"], ",")>0){
				$c=explode(",",$ligne["category"]);
				while (list ($a, $b) = each ($c) ){
					$_SESSION["ChangeFilterCatz"][$b]=$b;
				}
				
			continue;}
			$_SESSION["ChangeFilterCatz"][$ligne["category"]]=$ligne["category"];
			
		}
	}
	$_SESSION["ChangeFilterCatz"][null]="{select}";
	$html="
	<table style='width:98%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{categories}:</td>
		<td>". Field_array_Hash($_SESSION["ChangeFilterCatz"], "ChangeFilterCatz-$t",null,"xChangeFilterCatz()",null,0,"font-size:14px")."</td>
	</tr>
	</table>
	<script>
		function xChangeFilterCatz(){
			var ChangeFilterCatz=document.getElementById('ChangeFilterCatz-$t').value;
			$('#flex1').flexOptions({url: '$page?ok=yes&category='+ChangeFilterCatz}).flexReload();
			YahooWin3Hide();
		}
	</script>		
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}


function site_infos(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	
	$Familysite=$q->GetFamilySites($_GET["site-infos"]);
	$sql="SELECT whois FROM `visited_sites` WHERE familysite='$Familysite'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	writelogs("WHOIS::$Familysite:: ".strlen($ligne["whois"])." bytes",__FUNCTION__,__FILE__,__LINE__);
	$whois=unserialize($ligne["whois"]);
	$jssecond="<script>LoadAjax('search-stats-categories','$page?search-stats-categories={$_GET["site-infos"]}&idcallback={$_GET["idcallback"]}&disposition={$_GET["disposition"]}&disposition={$_GET["disposition"]}&gen-thumbnail={$_GET["gen-thumbnail"]}');</script>";
	
	if(isset($_GET["idcallback"])){
		$t=time();
		$jsThird="
		<div id='tasks-$t'></div>
		<script>
			function TasksCallBacks$t(){
				LoadAjax('tasks-$t','squid.miniwebsite.tasks.php?sitename={$_GET["site-infos"]}&idcallback={$_GET["idcallback"]}&TasksCallBacks=TasksCallBacks$t&disposition={$_GET["disposition"]}&gen-thumbnail={$_GET["gen-thumbnail"]}');
			}
			TasksCallBacks$t();		
		</script>";
	
	}
	
	if(!isset($whois["regrinfo"])){
		if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $_GET["site-infos"])){
			echo $tpl->_ENGINE_parse_body("$jssecond$jsThird");
			return;
		}
		$html="
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td width=1%><img src='img/question-48.png'></td>
			<td width=99%>
				<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('$page?whoissave-js={$_GET["site-infos"]}&idcallback={$_GET["idcallback"]}&disposition={$_GET["disposition"]}&gen-thumbnail={$_GET["gen-thumbnail"]}');\" 
				style='text-decoration:underline;font-size:14px'>{website_nowhois_error}</a></td>
		</tr>
		</tbody>
		</table>$jssecond$jsThird";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	$lengthMail=strlen($whois["regrinfo"]["owner"]["email"]);
	if($lengthMail>30){
		$tt=explode("@", $whois["regrinfo"]["owner"]["email"]);
		$whois["regrinfo"]["owner"]["email"]="...@{$tt[1]}";
		
	}
	$created=$whois["regrinfo"]["domain"]["created"];
	$sponsor=$whois["regrinfo"]["domain"]["sponsor"];
	$whois["regrinfo"]["owner"]["email"]=str_replace(";", "<br>", $whois["regrinfo"]["owner"]["email"]);
	$owner="
	<table style='width:100%'>
	<tbody>
	<tr>
		<td style='font-size:11px' class=legend valign='top'>mail:</td>
		<td style='font-size:11px'>{$whois["regrinfo"]["owner"]["email"]}</td>
	</tr>
	<tr>
		<td style='font-size:11px' class=legend valign='top'>{name}:</td>
		<td style='font-size:11px'>{$whois["regrinfo"]["owner"]["name"]}</td>
	</tr>	
	<tr>
		<td style='font-size:11px' class=legend valign='top'>Tel.:</td>
		<td style='font-size:11px'>{$whois["regrinfo"]["owner"]["phone"]}</td>
	</tr>
	<tr>
		<td style='font-size:11px' class=legend valign='top'>{address}:</td>
		<td style='font-size:11px'>".@implode(" ", $whois["regrinfo"]["owner"]["address"])."</td>
	</tr>	
	</tbody>
	</table>		
	";
	
	
$html="$jsThird
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			
			<td style='font-size:16px;height:40px;border-bottom:1px solid #CCCCCC' colspan=2>$Familysite<div style='font-size:11px;text-align:right'>sponsor:$sponsor</div></td>
		</tr>		
		<tr>
			<td width=1% nowrap class=legend>{created_on}:</td>
			<td style='font-size:14px'>$created</td>
		</tr>
		<tr>
			<td width=1% nowrap class=legend valign='top'>{owner}:</td>
			<td style='font-size:14px'>$owner</td>
		</tr>		
		</tbody>
		</table>$jssecond";	
	echo $tpl->_ENGINE_parse_body($html);
}



function whoissave_perform(){
	$GLOBALS["Q"]=new mysql_squid_builder();
	include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
	$domain=$_POST["whoissave"];
	$FamilySite=$GLOBALS["Q"]->GetFamilySites($domain);
	$whois = new Whois();	
	
	writelogs("->Lookup($domain)",__FUNCTION__,__FILE__,__LINE__);
	$result = $whois->Lookup($domain);
	if(!is_array($result)){
		writelogs("->Lookup($FamilySite)",__FUNCTION__,__FILE__,__LINE__);
		$result = $whois->Lookup($FamilySite);
	}
	
	if(!is_array($result)){
		writelogs("Not an array....",__FUNCTION__,__FILE__,__LINE__);
		return;
		
	}
	$whoisdatas=addslashes(serialize($result));
	writelogs("$whoisdatas",__FUNCTION__,__FILE__,__LINE__);
	$sql="SELECT familysite FROM `visited_sites` WHERE familysite='$Familysite'";
	$ligne=mysql_fetch_array($GLOBALS["Q"]->QUERY_SQL($sql));
	if($ligne["familysite"]==null){
		$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE familysite='$FamilySite'";
	}else{
		$sql="UPDATE visited_sites SET whois='$whoisdatas' WHERE sitename='$domain'";
	}
	
	
	
	
	$GLOBALS["Q"]->QUERY_SQL($sql);
	if(!$GLOBALS["Q"]->ok){
		writelogs("$sql:{$GLOBALS["Q"]->mysql_error}",__FUNCTION__,__FILE__,__LINE__);
		echo "{$GLOBALS["Q"]->mysql_error}\n";return;}
	
}

function site_infos_categories(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
	$delete=$tpl->javascript_parse_text("{delete}");
	$from=$tpl->javascript_parse_text("{from}");
	$do_you_want_to_recompile_database=$tpl->javascript_parse_text("{do_you_want_to_recompile_database}");
	$success=$tpl->javascript_parse_text("{sucess}");
	$t=time();
	$catz=$q->GET_CATEGORIES($_GET["search-stats-categories"]);
	if($catz<>null){
		if(strpos($catz, ",")>0){$cats=explode(",", $catz);}else{$cats[]=$catz;}
	}
		
	
	$catNum=count($cats);
	
	if($catNum==0){
		$html="
		
		<table style='width:99%'>
			<tbody><tr><td width=1%>
				<img src='img/warning-panneau-32.png'></td>
				<td width=99% style='font-size:13px;font-weight:bold'>{this_website_hasnocat}</td>
				</tr></tbody></table>";
		echo $tpl->_ENGINE_parse_body("$html");return;
	}
	$dd=new dansguardian_rules();
	$catstext=$tpl->_ENGINE_parse_body("{this_website_is_categorized_inXX}");
	$catstext=str_replace("XX", count($cats), $catstext);
	https://192.168.1.106:9000/squid.categories.php?query=yes&category=porn&search=megavideo.&strictSearch=0
	
	$html="<table style='width:99%'>
		<tbody>
		<tr>
			<td colspan=2 style='font-size:13px'>$catstext</td>
		</tr>
	";
	while (list ($a, $b) = each ($cats) ){
		if($b==null){continue;}
		
		$img=$dd->array_pics[$b];
		if($img==null){$img="20-categories-personnal.png";}
		$id=md5($b);
	$html=$html."<tr id='tt$id'>
			<td width=1%><img src='img/$img'></td>
			<td style='font-size:13px;font-weight:bold'>
			<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.categorize.php?www={$_GET["search-stats-categories"]}&day=&week=&month=');\" 
			style='font-size:13px;text-decoration:underline;;font-weight:bold'>$b</td>
			</tr>";
	
	}
	$html=$html."</tbody></table>
	
	<script>
	var MEMCAT$t='';
	var MEMID$t='';
	
		var x_DeleteCategoryFromSite$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#tt'+ MEMID$t).remove();
			if(confirm('$do_you_want_to_recompile_database ?')){
				var XHR = new XHRConnection();
				XHR.appendData('action_compile_category','yes');
				XHR.appendData('category',MEMCAT$t);
				XHR.sendAndLoad('$page', 'POST');			
				alert('$success');
			}
			
		}		
	
	
		function DeleteCategoryFromSite$t(category,www,id){
			MEMID$t=id;
			if(confirm('$delete '+category+' $from '+www+' ?')){
				var XHR = new XHRConnection();
				MEMCAT$t=category;
				XHR.appendData('action_delete_from_category','yes');
				XHR.appendData('sitename',www);
				XHR.appendData('category',category);
				XHR.sendAndLoad('$page', 'POST',x_DeleteCategoryFromSite$t);
			}
		
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body("$html");return;
}

function action_delete_from_category(){
	$q=new mysql_squid_builder();
	$q->REMOVE_CATEGORIZED_SITENAME($_POST["sitename"],$_POST["category"]);
	
}
function action_compile_category(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?ufdbguard-compile-database={$_POST["category"]}");	
	
}
