<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
include_once(dirname(__FILE__).'/ressources/class.rtmm.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.dansguardian.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.categorize.generic.inc');
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}
	

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["messaging-right"])){messaging_right();exit;}
if(isset($_GET["webstats-middle"])){webstats_middle();exit;}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["websites-test"])){websitelist();exit;}
if(isset($_POST["websites-add"])){websites_save();exit;}
if(isset($_POST["websites-analyze"])){websites_analyze();exit;}
if(isset($_POST["ffCatAdd"])){ffCatAdd();exit;}
if(isset($_GET["AddWebsites-popup"])){AddWebsites_popup();exit;}
if(isset($_POST["websites-delete"])){websites_delete();exit;}
if(isset($_POST["PerformProposal"])){PerformProposal();exit;}
if(isset($_POST["retry-analyze"])){retry_analyze();exit;}
if(isset($_POST["WEBTESTS"])){WEBTESTS();exit;}
if(isset($_POST["import-artica"])){import_artica_fr();exit;}
if(isset($_GET["main-domains"])){main_domains();exit; }
if(isset($_GET["main-domains-items"])){main_domains_items();exit;}
main_page();

function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}')</script>", $content);
	echo $content;	
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	$q=new mysql_squid_builder();
	$NotCategorizedTests=$q->COUNT_ROWS("webtests");
	$NotCategorizedTests=numberFormat($NotCategorizedTests,0,""," ");
	
	$jsadd="LoadAjax('statistics-$t','$page?webstats-stats=yes');";
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'>
			<a href=\"miniadm.index.php\">{myaccount}</a>
			&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php\">{web_statistics}</a>
		</div>
		<H1>$NotCategorizedTests {not_categorized}</H1>
		<p style='font-size:12px'>{squid_test_categories_explain}</p>
	</div>	
	<div id='webstats-middle-$ff'></div>
	
	<script>
		LoadAjax('webstats-middle-$ff','$page?webstats-middle=yes');
		$jsadd
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function webstats_middle(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$ff=time();
	
	$html="<div class=BodyContent id='table-$ff'></div>
	
	
	<script>
		LoadAjax('table-$ff','$page?table=yes');
	</script>
	";
	
	echo $html;
	
	
}

function main_domains(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$main_domains=$tpl->javascript_parse_text("{main_domains}");
	$hits=$tpl->javascript_parse_text("{hits}");
	
	$buttons="
	buttons : [
	{name: '$main_domains', bclass: 'Search', onpress : MainDomains$t},
	{name: '$verify', bclass: 'add', onpress : Analyze$t},
	{name: '$add_websites', bclass: 'add', onpress : AddWebsites$t},
	{name: '$retry', bclass: 'Reload', onpress : Retry$t},
	{name: '$import - Artica', bclass: 'add', onpress : ImportArt$t},
	],";

	$buttons=null;
	
	$html="
<table class='$tt' style='display: none' id='$tt' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$tt').flexigrid({
	url: '$page?main-domains-items=yes&t=$t&tt=$tt',
	dataType: 'json',
	colModel : [
		{display: '$websites', name : 'sitename', width : 527, sortable : true, align: 'left'},
		{display: '$hits', name : 'hits', width : 57, sortable : true, align: 'left'},		
		],
	$buttons
	searchitems : [
		{display: '$websites', name : 'sitename'},
	],
	sortname: 'hits',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 630,
	height: 400,
	singleSelect: true,
	rpOptions: [30, 50,100,200,500]
	
	});   
});

function SelectFamily$tt(domain){
	$('#$t').flexOptions({url: '$page?websites-test=yes&fix-search-domain='+domain}).flexReload(); 

}

</script>
";
	echo $html;
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$retry=$tpl->_ENGINE_parse_body("{retry}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	$import=$tpl->_ENGINE_parse_body("{import}");
	$import_catz_art_expl=$tpl->javascript_parse_text("{import_catz_art_expl}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$country=$tpl->_ENGINE_parse_body("{country}");
	$createdon=$tpl->_ENGINE_parse_body("{creationdate}");
	$main_domains=$tpl->javascript_parse_text("{main_domains}");
	
	
	$buttons="
	buttons : [
	{name: '$main_domains', bclass: 'Search', onpress : MainDomains$t},
	{name: '$verify', bclass: 'add', onpress : Analyze$t},
	{name: '$add_websites', bclass: 'add', onpress : AddWebsites$t},
	{name: '$retry', bclass: 'Reload', onpress : Retry$t},
	{name: '$import - Artica', bclass: 'add', onpress : ImportArt$t},
	],";	
	
	$html="
	<span id='analyze-img-$t'></span>
	
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?websites-test=yes',
	dataType: 'json',
	colModel : [
		{display: '$createdon', name : 'zDate', width : 128, sortable : true, align: 'left'},	
		{display: '$websites', name : 'sitename', width : 224, sortable : true, align: 'left'},	
		{display: '$category', name : 'category', width : 135, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'null', width : 30, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'null', width : 30, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$websites', name : 'sitename'},
		{display: '$country', name : 'Country'},
		
		
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 910,
	height: 500,
	singleSelect: true,
	rpOptions: [30, 50,100,200,500]
	
	});   
});

	
		function x_CheckSingleSite(obj){
			var tempvalue=obj.responseText;
			if(document.getElementById('analyze-img-$t')){document.getElementById('analyze-img-$t').innerHTML='';}
			if(tempvalue.length>3){alert(tempvalue);}
	
			
		}

function CheckSingleSite(e){
		if(!checkEnter(e)){return;}
		var XHR = new XHRConnection();
		XHR.appendData('WEBTESTS',document.getElementById('WEBTESTS').value);
		AnimateDiv('analyze-img-$t');
		XHR.sendAndLoad('$page', 'POST',x_CheckSingleSite);	

}

function MainDomains$t(){
	YahooWin3('650','$page?main-domains=yes&t=$t','$main_domains');
}
	
		function x_PostTestWebsites$t(obj){
			var tempvalue=obj.responseText;
			if(document.getElementById('analyze-img-$t')){document.getElementById('analyze-img-$t').innerHTML='';}
			if(tempvalue.length>3){alert(tempvalue);}else{
				if(document.getElementById('test-cat-form-$t')){document.getElementById('test-cat-form-$t').value='';}
			}
			
			$('#$t').flexReload();
	
			
		}

		function RefreshTableauTestCategories(){
			$('#$t').flexReload();
		}
		
		
		function Retry$t(){
			var XHR = new XHRConnection();
			XHR.appendData('retry-analyze','ok');
			AnimateDiv('analyze-img-$t');
			XHR.sendAndLoad('$page', 'POST',x_PostTestWebsites$t);			
		}
		
		function ImportArt$t(){
			if(confirm('$import_catz_art_expl')){
			var XHR = new XHRConnection();
			XHR.appendData('import-artica','ok');
			AnimateDiv('analyze-img-$t');
			XHR.sendAndLoad('$page', 'POST',x_PostTestWebsites$t);				
			
			}
		
		}

		
		function Analyze$t(){
			var XHR = new XHRConnection();
			XHR.appendData('websites-analyze','ok');
			AnimateDiv('analyze-img-$t');
			XHR.sendAndLoad('$page', 'POST',x_PostTestWebsites$t);				
		
		}
		
		function DeleteTestCatSitename(site){
			var XHR = new XHRConnection();
			XHR.appendData('websites-delete',site);
			XHR.sendAndLoad('$page', 'POST',x_PostTestWebsites$t);			
		}
		
	var x_ffCatAdd$t=function(obj){
     	var tempvalue=obj.responseText;
     	document.getElementById('analyze-img-$t').innerHTML='';
      	if(tempvalue.length>3){alert(tempvalue);return;}
      	$('#row'+xsite).remove();
     
     	}	

     	
     function AddWebsites$t(){
     	YahooWin5('550','$page?AddWebsites-popup=yes&t=$t','$add_websites');
     
     }
     
     function PerformProposal(category,sitename,md){
     	xsite=md;
     	var XHR = new XHRConnection();
		XHR.appendData('PerformProposal','yes');
		XHR.appendData('category',category);
		XHR.appendData('sitename',sitename);
		AnimateDiv('analyze-img-$t');
		XHR.sendAndLoad('$page', 'POST',x_ffCatAdd$t);
     }
		
	
		
		function ffCatAdd(sitename,serial){
			xsite=sitename;
			var XHR = new XHRConnection();
			XHR.appendData('ffCatAdd',serial);
			AnimateDiv('analyze-img-$t');
			XHR.sendAndLoad('$page', 'POST',x_ffCatAdd$t);			
		
		}
		
		
	
</script>";
	
	echo $html;
		
	
}
function WEBTESTS(){
	
$www=$_POST["WEBTESTS"];
$q=new mysql_squid_builder();
$www=$q->WebsiteStrip($www);

if($www==null){echo "corrupted\n";return;}
$catz=str_replace(",", "\n- ", $q->GET_CATEGORIES($www,true));
echo "\nFinal:\n\"".$q->GET_CATEGORIES($www)."\"\n";
	
}


function AddWebsites_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];

	
	$html="
	<div id='anim-$t'>
	<textarea style='font-size:13px;height:350px;width:100%;overflow:auto;margin-bottom:10px' id='test-cat-form-$t'></textarea>
	<hr>
	<div style='text-align:right'>". button("{add}", "PostTestWebsites$t()")."</div>
	</div>
	<script>
		function x_PostTestWebsites2$t(obj){
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue);}
			if(document.getElementById('tableau-test-categories')){RefreshTableauTestCategories();}
			YahooWin5Hide();
			
		}			
			
		function PostTestWebsites$t(){
			var XHR = new XHRConnection();
			XHR.appendData('websites-add',document.getElementById('test-cat-form-$t').value);
			AnimateDiv('anim-$t');
			XHR.sendAndLoad('$page', 'POST',x_PostTestWebsites2$t);	
		}	
	</script>
	";

	echo $tpl->_ENGINE_parse_body($html);
	
}

function PerformProposal(){
	$extcat=$_POST["category"];
	$sitename=$_POST["sitename"];
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$newmd5=md5("$extcat$sitename");
	$q=new mysql_squid_builder();
	$category_table="category_".$q->category_transform_name($extcat);
	$q->QUERY_SQL("INSERT IGNORE INTO categorize_changes (zmd5,sitename,category) VALUES('$newmd5','$sitename','$extcat')");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$newmd5',NOW(),'$extcat','$sitename','$uuid')");
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
	if(!$q->ok){echo $q->mysql_error;return;}		
}

function websites_delete(){
	$q=new mysql_squid_builder();
	$_POST["websites-delete"]=addslashes($_POST["websites-delete"]);
	$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='{$_POST["websites-delete"]}'");
}

function websites_save(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$numS=$q->COUNT_ROWS("webtests");
	$f=explode("\n", $_POST["websites-add"]);
	while (list ($num, $www) = each ($f) ){
		$www=trim(strtolower($www));
		if(preg_match("#http:\/\/(.+)#", $www,$re)){$www=$re[1];}
		writelogs("$www",__FUNCTION__,__FILE__,__LINE__);
		$www=str_replace("www.", "", $www);
		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/"));}
		$d[]=$www;
	}
	
	if(count($d)==0){return;}
	while (list ($num, $www) = each ($d) ){
		$www=trim($www);
		if(trim($www)==null){continue;}
		if(strpos($www, ",")>0){continue;}
		if(strpos($www, " ")>0){continue;}
		if(strpos($www, ":")>0){continue;}
		if(strpos($www, "%")>0){continue;}
		
		if(trim($www)==null){continue;}
		$qA[]="('$www')";
	}
	
	if(count($qA)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) VALUES ".@implode(",", $qA));
		if(!$q->ok){
			if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();}
			$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) VALUES ".@implode(",", $qA));
			if(!$q->ok){echo $q->mysql_error;}
		}
	}
	
	$num2=$q->COUNT_ROWS("webtests");
	$tot=$num2-$numS;
	echo $tpl->javascript_parse_text("$tot {added_websites}");
	$sock=new sockets();
	$sock->getFrameWork("squid.php?categorize-tests=yes");

	
}

function websites_analyze(){
		$sock=new sockets();
		$sock->getFrameWork("squid.php?categorize-tests=yes");
}
function retry_analyze(){
	$q=new mysql_squid_builder();
	$sql="UPDATE webtests SET checked=0";
	$q->QUERY_SQL($sql);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?categorize-tests=yes");	
}

function main_domains_items(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="webtests";
	$tt=$_GET["tt"];
	$country_select=null;
	$search='%';
	$page=1;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	
	$table="(SELECT family,COUNT(`sitename`) as hits FROM webtests GROUP BY family) as t";
if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT * FROM $table WHERE 1 $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}	
	
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}	
		
		
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$button=null;
		
		
		
	while ($ligne = mysql_fetch_assoc($results)) {
			$jscat="SelectFamily$tt('{$ligne['family']}')";
	
			$data['rows'][] = array(
			'id' => md5($ligne['family']),
			'cell' => array(
				"<a href=\"javascript:blur();\" OnClick=\"$jscat\" style='font-size:11px;font-weight:bold;text-decoration:underline'>{$ligne['family']}</a></span>",
				"<a href=\"javascript:blur();\" OnClick=\"$jscat\" style='font-size:11px;font-weight:bold;text-decoration:underline'>{$ligne['hits']}</a></span>$country",
				)
			);
	}
	
	
echo json_encode($data);		
	
}


function websitelist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="webtests";
	$country_select=null;
	$search='%';
	$page=1;
	$total=0;
	$FORCE=null;
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	if(isset($_GET["fix-search-domain"])){
		$FORCE="AND family='{$_GET["fix-search-domain"]}'";
	}
	

	if(($_POST["query"]<>null) OR ($FORCE<>null)){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT * FROM $table WHERE 1 $FORCE $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}else{

		$total = $q->COUNT_ROWS($table);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT * FROM $table WHERE 1 $FORCE $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error,1);}	
	
	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}	
		
		
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$button=null;
		
		
		
	while ($ligne = mysql_fetch_assoc($results)) {
			if($ligne["sitename"]=="http:"){$q->QUERY_SQL("DELETE FROM $table WHERE sitename='{$ligne["sitename"]}'");continue;}
			$button=null;
			if(trim($ligne["category"])<>null){
				$md=md5($ligne["sitename"]);
				$fff=base64_encode(serialize(array($ligne["sitename"],$ligne["category"])));
				$button=imgtootltip("plus-24.png","{$ligne["sitename"]} = {$ligne["category"]}","ffCatAdd('$md','$fff')");
			}
			$delte=imgtootltip("delete-24.png","{delete} {$ligne["sitename"]}","DeleteTestCatSitename('{$ligne["sitename"]}')");
			$jscat="javascript:Loadjs('squid.categorize.php?www={$ligne['sitename']}&day=&week=&month=')";
			
			if($ligne["checked"]==1){
				$button="<img src='img/error-24.png'>";
			}
			
			if(trim($ligne["category"])==null){
				$ligne["category"]=proposal("{$ligne['sitename']}");
			}
			
			$country=$ligne["Country"];
			if($country<>null){$country="<div>$country</div>";}
			
			$data['rows'][] = array(
			'id' => md5($ligne['sitename']),
			'cell' => array(
				"{$ligne['zDate']}</span>",
				"<a href=\"javascript:blur();\" OnClick=\"$jscat\" style='font-size:11px;font-weight:bold;text-decoration:underline'>{$ligne['sitename']}</a></span>$country",
				"<span style='font-size:11px;font-weight:bold'>{$ligne["category"]}</span>",$button,$delte)
			);
	}
	
	
echo json_encode($data);		

	
}

function proposal($www){
	$f=array();
	$md5=md5($www);
	$www=trim($www);
	if(preg_match("#music#", $www)){$f["music"]=true;}
	if(preg_match("#movie#", $www)){
		$f["movies"]=true;
		$f["audio-video"]=true;		
	}
	if(strpos($www, ".amazonaws.com")>0){$f["filehosting"]=true;}	
	if(preg_match("#radio#", $www)){$f["webradio"]=true;}
	if(preg_match("#skyrock#", $www)){$f["webradio"]=true;}
	if(preg_match("#journal#", $www)){$f["blog"]=true;}
	if(preg_match("#shop#", $www)){$f["shopping"]=true;}
	if(preg_match("#vintage#", $www)){$f["shopping"]=true;}		
	if(preg_match("#xxx#", $www)){$f["porn"]=true;}
	if(preg_match("#career#", $www)){$f["jobsearch"]=true;}
	if(preg_match("#[-\_]fm#", $www)){$f["webradio"]=true;}
	if(preg_match("#about\.com$#",$www)){$f["dictionaries"]=true;}
	if(preg_match("#politic#",$www)){$f["politic"]=true;}
	if(preg_match("#soiree#",$www)){$f["recreation/nightout"]=true;}
	if(preg_match("#tv\.#",$www)){$f["webtv"]=true;}
	if(preg_match("#school#",$www)){$f["recreation/schools"]=true;}
	if(preg_match("#mobile#",$www)){$f["mobile-phone"]=true;}
	if(preg_match("#tvprogram#",$www)){$f["webtv"]=true;}
	if(preg_match("#.musiwave.com$#",$www)){$f["ringtones"]=true;}
	if(preg_match("#\.2o7\.net#",$www)){$f["tracker"]=true;}
	if(preg_match("#warcraft#",$www)){$f["games"]=true;}
	if(preg_match("#\.fm$#",$www)){$f["webradio"]=true;}
	if(preg_match("#soft#",$www)){$f["science/computing"]=true;}
	if(preg_match("#tvideos#",$www)){$f["webtv"]=true;}
	if(preg_match("#sex#",$www)){$f["porn"]=true;}
	if(preg_match("#blip\.tv$#",$www)){$f["webtv"]=true;}
	if(preg_match("#car.*insurance#",$www)){$f["finance/insurance"]=true;}
	if(preg_match("#health.*insurance#",$www)){$f["finance/insurance"]=true;}
	if(preg_match("#home.*insurance#",$www)){$f["finance/insurance"]=true;}
	if(preg_match("#\.disqus\.com$#",$www)){$f["socialnet"]=true;}
	if(preg_match("#twenga\.[a-z]+$#",$www)){$f["shopping"]=true;}
	if(preg_match("#\.maases\.com$#", $www)){$f["music"]=true;}
	if(preg_match("#\.zankyou\.com$#", $www)){$f["socialnet"]=true;}
	if(preg_match("#\.wikipedia\.org$#",$www)){$f["dictionaries"]=true;}
	if(preg_match("#\.wikia.com$#",$www)){$f["dictionaries"]=true;}
	if(preg_match("#\.gameleads.ru$#",$www)){$f["publicite"]=true;}
	if(preg_match("#immobilier#", $www)){$f["finance/realestate"]=true;}
	if(preg_match("#\.icplatform.com$#", $www)){$f["reaffected"]=true;}
	if(preg_match("#mailing#", $www)){$f["mailing"]=true;}
	if(preg_match("#porn#", $www)){$f["porn"]=true;}
	
	if(preg_match("#video#", $www)){
			$f["movies"]=true;
			$f["audio-video"]=true;		
	}
	
	if(preg_match("#game#", $www)){$f["games"]=true;}
	
	$p=new generic_categorize();
	$ccc=$p->GetCategories($www);
	if($ccc<>null){
		$f[$ccc]=true;
	}
	
	while (list ($category, $rows) = each ($f) ){
		

	$s[]="<div>
			<a href=\"javascript:blur();\" Onclick=\"javascript:PerformProposal('$category','$www','$md5')\" 
			style='font-size:11px;text-decoration:underline'>$category ?</a>
			</div>";
	
		}
	
	return @implode(" ", $s);
	
}


function ffCatAdd(){
	$array=unserialize(base64_decode($_POST["ffCatAdd"]));
	if(!is_array($array)){echo "Not an array....";return;}
	$sitename=$array[0];
	$categories=$array[1];
	if($sitename==null){echo "Sitename=null";return;}
	$q=new mysql_squid_builder();
	if(!$q->ADD_CATEGORYZED_WEBSITE($sitename,$categories)){return;}
	$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
	
}
function import_artica_fr(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?import_nocatz-artica=yes");
	
}