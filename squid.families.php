<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.rtmm.tools.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	
	if(isset($_GET["websitelist"])){websitelist();exit;}
	
	if(isset($_GET["family-js"])){family_js();exit;}
	if(isset($_GET["family-popup"])){family_popup();exit;}
	if(isset($_GET["family-list"])){family_list();exit;}
	if(isset($_POST["family-analyze"])){family_analyze();exit;}
	
page();


function family_js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$familysite=$_GET["family-js"];
	$title=$familysite;
	$html="YahooWin4('650','$page?family-popup=yes&familysite=$familysite','$title');";
	echo $html;
	
	
}

function family_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$susbsites=$tpl->_ENGINE_parse_body("{subsites}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	
	$buttons="
	buttons : [
	{name: '$verify', bclass: 'add', onpress : Analyze$t},
	],";	
	
	$html="
	<span id='analyze-img-$t'></span>
	
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?family-list=yes&familysite={$_GET["familysite"]}',
	dataType: 'json',
	colModel : [
		{display: '$websites', name : 'familysite', width : 338, sortable : true, align: 'left'},	
		{display: '$category', name : 'category', width : 190, sortable : true, align: 'left'},
		{display: '$hits', name : 'HitsNumber', width : 50, sortable : true, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$websites', name : 'familysite'},
		{display: '$category', name : 'category'},
		],
	sortname: 'HitsNumber',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 635,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	
		function x_PostTestWebsites$t(obj){
			var tempvalue=obj.responseText;
			if(document.getElementById('analyze-img-$t')){document.getElementById('analyze-img-$t').innerHTML='';}
			$('#$t').flexReload();
			RefreshTableauFamilyMain();
	
			
		}

		function RefreshTableauTestCategories(){
			$('#$t').flexReload();
		}

		
		function Analyze$t(){
			var XHR = new XHRConnection();
			XHR.appendData('family-analyze','{$_GET["familysite"]}');
			AnimateDiv('analyze-img-$t');
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




function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$susbsites=$tpl->_ENGINE_parse_body("{subsites}");
	$add=$tpl->_ENGINE_parse_body("{add}");
	$add_websites=$tpl->_ENGINE_parse_body("{add} $websites");
	$verify=$tpl->_ENGINE_parse_body("{analyze}");
	$squid_test_categories_explain=$tpl->_ENGINE_parse_body("{squid_test_categories_explain}");
	
	$buttons="
	buttons : [
	{name: '$verify', bclass: 'add', onpress : Analyze$t},
	],";	
	
	$html="
	<span id='analyze-img-$t'></span>
	<div class=explain style='font-size:12px' id='tableau-test-categories'>$squid_test_categories_explain</div>
<table class='$t' style='display: none' id='$t' style='width:100%'></table>
<script>
var xsite='';
$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?websitelist=yes',
	dataType: 'json',
	colModel : [
		{display: '$websites', name : 'sitename', width : 374, sortable : true, align: 'left'},	
		{display: '$susbsites', name : 'category', width : 136, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'null', width : 30, sortable : false, align: 'left'},
		],
	$buttons
	searchitems : [
		{display: '$websites', name : 'familysite'},
		],
	sortname: 'tcount',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 600,
	height: 250,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});
	
		function x_PostTestWebsites$t(obj){
			var tempvalue=obj.responseText;
			if(document.getElementById('analyze-img-$t')){document.getElementById('analyze-img-$t').innerHTML='';}
			if(tempvalue.length>3){alert(tempvalue);}else{
				if(document.getElementById('test-cat-form-$t')){document.getElementById('test-cat-form-$t').value='';}
			}
			
			$('#$t').flexReload();
	
			
		}

		function RefreshTableauFamilyMain(){
			$('#$t').flexReload();
		}

		
		function Analyze$t(){
			var XHR = new XHRConnection();
			XHR.appendData('websites-analyze','ok');
			AnimateDiv('analyze-img-$t');
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






function websitelist(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="visited_sites";
	$country_select=null;
	$search='%';
	$page=1;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(sitename) as tcount,familysite FROM $table GROUP BY familysite HAVING tcount >1 $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}else{
		$sql="SELECT COUNT(sitename) as tcount,familysite FROM $table GROUP BY familysite HAVING tcount >1";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);		

		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT COUNT(sitename) as tcount,familysite FROM $table GROUP BY familysite HAVING tcount >1 $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$q->mysql_error=wordwrap($q->mysql_error,80,"<br>");
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	if(mysql_num_rows($results)==0){
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => time(),'cell' => array($sql,"", "",""));}
		
		
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$button=null;
		
		
		
	while ($ligne = mysql_fetch_assoc($results)) {
			
			
			$jscat="javascript:Loadjs('$MyPage?family-js={$ligne['familysite']}')";
			
			$data['rows'][] = array(
			'id' => md5($ligne['familysite']),
			'cell' => array(
				 "<a href=\"javascript:blur();\" OnClick=\"$jscat\" style='font-size:12px;font-weight:bold;text-decoration:underline'>{$ligne['familysite']}</span>",
				"<span style='font-size:12px;font-weight:bold'>{$ligne["tcount"]}</span>",$button)
			);
	}
	
	
echo json_encode($data);		

	
}

function family_analyze(){
	$sql="SELECT sitename FROM visited_sites WHERE familysite='{$_POST["family-analyze"]}'";
	$q=new mysql_squid_builder();
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$f=$q->GetFamilySites($ligne["sitename"]);
		$q->QUERY_SQL("UPDATE visited_sites SET familysite='$f' WHERE sitename='{$ligne["sitename"]}'");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$tpl=new templates();
	echo mysql_num_rows($results)." ".$tpl->javascript_parse_text("{items} {success}");
	
	
}

function family_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="visited_sites";
	$country_select=null;
	$search='%';
	$page=1;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT sitename,familysite FROM $table WHERE familysite='{$_GET["familysite"]}' $searchstring";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
		
		
			
		
		
	}else{
		$sql="SELECT familysite FROM $table WHERE familysite='{$_GET["familysite"]}'";
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);		

		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT sitename,familysite,category,HitsNumber FROM $table WHERE familysite='{$_GET["familysite"]}' $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$q->mysql_error=wordwrap($q->mysql_error,80,"<br>");
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	if(mysql_num_rows($results)==0){
		$sql=wordwrap($sql,80,"<br>");
		$data['rows'][] = array('id' => time(),'cell' => array($sql,"", "",""));}
		
		
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$button=null;
		
		
		
	while ($ligne = mysql_fetch_assoc($results)) {
			$error=null;
			$color="black";
			$myFamily=$q->GetFamilySites($ligne['sitename']);
			if($myFamily<>$_GET["familysite"]){$error=$tpl->_ENGINE_parse_body("<br><i style='font-weight:normal'>false should be $myFamily instead of {$_GET["familysite"]}</i>");$color="#BD0000";}
			$jscat="javascript:Loadjs('$MyPage?family-js={$ligne['familysite']}')";
			
			if($ligne["category"]==null){$ligne["category"]="&laquo;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categorize.php?www={$ligne['sitename']}&day=&week=&month=');\"> categorize &raquo;</A>";}
			
			$data['rows'][] = array(
			'id' => md5($ligne['sitename']),
			'cell' => array(
			 "<spanstyle='font-size:12px;color:$color;font-weight:bold;text-decoration:underline'>{$ligne['sitename']}$error</span>",
			"<span style='font-size:12px;font-weight:bold'>{$ligne["category"]}</span>",
			"<span style='font-size:12px;font-weight:bold'>{$ligne["HitsNumber"]}</span>",
			
			$button)
			);
	}
	
	
echo json_encode($data);		
	
	
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
