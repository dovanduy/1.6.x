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
	
	
	if(isset($_POST["QuickCategorize"])){QuickCategorize();exit;}
	if(isset($_GET["rescan-js"])){rescan_js();exit;}
	if(isset($_POST["ResCanVisited"])){rescan_perform();exit;}
	if(isset($_POST["rescan_perform"])){rescan_perform();exit;}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["visited"])){visited();exit;}
	if(isset($_GET["visited-list"])){visited_list();exit;}
	
	if(isset($_GET["no-cat"])){not_categorized();exit;}
	if(isset($_GET["no-cat-list"])){not_categorized_list();exit;}
	
	if(isset($_GET["yes-cat"])){categorized();exit;}
	if(isset($_GET["yes-cat-list"])){categorized_list();exit;}
		
	
	if(isset($_GET["free-cat-tabs"])){free_catgorized_tabs();exit;}
	if(isset($_GET["free-cat"])){free_catgorized();exit;}
	if(isset($_GET["free-refresh-catz"])){free_refresh_catgorized();exit;}
	
	
	
	if(isset($_POST["textToParseCats"])){free_catgorized_save();exit;}
	if(isset($_GET["free-cat-explain"])){free_catgorized_explain();exit;}
	
	if(isset($_GET["params"])){parameters();exit;}
	if(isset($_GET["EnableCommunityFilters"])){parameters_save();exit;}
	
	
	if(isset($_GET["CategorizeAll-js"])){CategorizeAll_js();exit;}
	if(isset($_GET["CategorizeAll"])){CategorizeAll_popup();exit;}
	if(isset($_GET["CategorizeAll_category"])){CategorizeAll_perform();exit;}
	if(isset($_GET["cat-explain"])){CategorizeAll_explain();exit;}
	
	
	if(isset($_GET["recategorize-day-js"])){echo recategorize_day_js();exit;}
	if(isset($_POST["recategorize-day-perform"])){recategorize_day_perform();exit;}
	
	
	
js();

function CategorizeAll_js(){
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{visited_websites}");
	$categorize_this_query=$tpl->_ENGINE_parse_body("{categorize_this_query}");
	$page=CurrentPageName();
	$query=urlencode($_GET["query"]);
	$html="
	
	function CategorizeAll(){
			YahooWin4(580,'$page?CategorizeAll=$query&day={$_GET["day"]}','$categorize_this_query');
		
		}	
	
	CategorizeAll();";
	echo $html;	
	
}

function recategorize_day_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?recategorize-day={$_POST["recategorize-day-perform"]}");
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{success}"); 
	echo $text;
}


function recategorize_day_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{WWW_RESCAN_ASK}");
	
	$html="
	
	var x_recategorizePerform= function (obj) {
		var res=obj.responseText;
		if (res.length>3){
			alert(res);
		}
	}		
	
	if(confirm('$text')){
		var XHR = new XHRConnection();
		XHR.appendData('recategorize-day-perform','{$_GET["recategorize-day-js"]}');
		XHR.sendAndLoad('$page', 'POST',x_recategorizePerform);		
		}
	";
	echo $html;	
	
}

function rescan_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$text=$tpl->javascript_parse_text("{WWW_RESCAN_ASK}");
	
	$html="
	if(confirm('$text')){
		var XHR = new XHRConnection();
		XHR.appendData('rescan_perform',1);
		XHR.sendAndLoad('$page', 'POST');		
		}
	";
	echo $html;
}

function rescan_perform(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?visited-sites=yes");
	
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$category=$_GET["category"];
	$title=$tpl->_ENGINE_parse_body("{visited_websites}");
	$t=$_GET["t"];
	$categorize_this_query=$tpl->_ENGINE_parse_body("{categorize_this_query}");
	if(isset($_GET["onlyNot"])){$onlyNot="&onlyNot=yes";}
	$start="YahooWin3('890','$page?popup=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}$onlyNot','$title');";
	if(isset($_GET["add-www"])){
		if($category<>null){$category_text="&raquo;&raquo;{category}&raquo;&raquo;$category";}
		$title=$tpl->_ENGINE_parse_body("{add_websites}$category_text");
		$start="YahooWin3('650','$page?free-cat-tabs=yes&websitetoadd={$_GET["websitetoadd"]}&category=$category&t=$t','$title');";
	}
	
	$html="
	$start
	
	function CategorizeAll(query){
			YahooWin4(580,'$page?CategorizeAll='+escape(query)+'&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}','$categorize_this_query');
		
		}	
		";
	echo $html;
	
}

function free_catgorized_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$category=$_GET["category"];
	$array["free-cat"]='{add_websites}';
	$array["test-cat"]='{test_categories}';
	$array["categorytables"]='{categories}';
	$array["family"]='{websites_families}';
	$array["compile"]='{compile}';
	$t=$_GET["t"];
	if(!is_numeric($t)){
		$t=time();
	}

	if($category<>null){
		unset($array["test-cat"]);
		unset($array["categorytables"]);
		unset($array["family"]);
		unset($array["compile"]);
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="free-cat"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?free-cat=yes&websitetoadd={$_GET["websitetoadd"]}&category=$category&t=$t\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="categorytables"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.categories.php?popup=yes&category=&website={$_GET["websitetoadd"]}&tablesize=620&rowebsite=321\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="test-cat"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.test.categories.php?popup=yes&category=&website={$_GET["websitetoadd"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}	

		if($num=="family"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.families.php?popup=yes&category=&website={$_GET["websitetoadd"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		

		if($num=="compile"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"dansguardian2.databases.php?categories=&minisize=yes\"><span>$ligne</span></a></li>\n");
			continue;
		}		
		
	}
	
	
	echo "
	<div id=main_config_visitedwebs$t style='width:100%;height:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_visitedwebs$t').tabs();
			
			
			});
		</script>";			
}

function popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$array["visited"]='{visited_websites}';
	$array["no-cat"]='{not_categorized}';
	$array["yes-cat"]='{categorized}';
	$array["free-cat"]='{add_websites}';
	$array["webalyzer"]='{webalyzer}';
	$array["params"]='{parameters}';

	if(isset($_GET["onlyNot"])){
		unset($array["visited"]);
		unset($array["yes-cat"]);
		unset($array["free-cat"]);
		
	}

	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="webalyzer"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.webalyzer.php\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_visitedwebs style='width:100%'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_visitedwebs').tabs();
			
			
			});
		</script>";			
}


function visited(){
$page=CurrentPageName();	
$html="
<table>
<td class=legend>{search}:</td>
<td>". Field_text("visited-search",$_COOKIE["SQUID_NOT_CAT_SEARCH"],"font-size:13px;padding:3px",null
,null,null,false,"SQUID_VISITED_SEARCH_CHECK(event)")."</td>
</tr>
</table>

<div id='visited_web_sites' style='height:450px;overflow:auto'></div>

<script>
	function SQUID_VISITED_SEARCH_CHECK(e){
		if(!checkEnter(e)){return;}
		var value=trim(document.getElementById('visited-search').value);
		document.getElementById('visited-search').value=value;
		Set_Cookie('SQUID_NOT_CAT_SEARCH',value, '3600', '/', '', '');
		LoadAjax('visited_web_sites','$page?visited-list=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}');
	}
	LoadAjax('visited_web_sites','$page?visited-list=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}');
	
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);
	
}

function parameters(){
	$sock=new sockets();
	$EnableCommunityFilters=$sock->GET_INFO("EnableCommunityFilters");
	if($EnableCommunityFilters==null){$EnableCommunityFilters=1;}
	
	$rescan_visited=Paragraphe("compile-database-64.png","{RESCAN_WWWVISISTED}","{WEB_RESCAN_VISITED_TEXT}","javascript:Loadjs('squid.visited.php?rescan-js=yes')");
	if(isset($_GET["day"])){
		$dayp=Paragraphe("64-categories-loupe.png","{recategorize_schedule} {$_GET["day"]}","{recategorize_schedule} {$_GET["day"]}","javascript:Loadjs('squid.visited.php?recategorize-day-js={$_GET["day"]}')");
	}
	
	if($_GET["week"]<>null){
		$time=strtotime("{$_GET["week"]} 00:00:00");
		$week=date("W",$time);		
		$dayp=Paragraphe("64-categories-loupe.png","{recategorize_schedule} {week} $week","{recategorize_schedule} {week} $week","javascript:Loadjs('squid.visited.php?recategorize-day-js={$_GET["week"]}')");
	}
	if($_GET["month"]<>null){
		$time=strtotime("{$_GET["month"]} 00:00:00");
		$month=date("F",$time);		
		$dayp=Paragraphe("64-categories-loupe.png","{recategorize_schedule} {$month}","{recategorize_schedule} {month} {{$month}}","javascript:Loadjs('squid.visited.php?recategorize-day-js={$_GET["month"]}')");
	}	
	
	$tr[]=$rescan_visited;
	$tr[]=$dayp;
	
	$table=CompileTr3($tr);
	$page=CurrentPageName();
	$html="
	<table style='width:100%'>
	<tr>
	<td class=legend>{alert_in_frontpage_website_categorize}:</td>
	<td>".Field_checkbox("EnableCommunityFilters",1,$EnableCommunityFilters,"EnableCommunityFiltersCheck()")."</td>
	</tr>
	</table> 
	$table
	<script>
		var x_EnableCommunityFiltersCheck=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
     	document.getElementById('ssl-bump-wl-id').innerHTML='';
     	sslBumpList();
     	}	
      


	function EnableCommunityFiltersCheck(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableCommunityFilters').checked){
			XHR.appendData('EnableCommunityFilters',1);}else{
			XHR.appendData('EnableCommunityFilters',0);
			}
		XHR.sendAndLoad('$page', 'GET');		
		}
	
	
</script>
";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function parameters_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableCommunityFilters",$_GET["EnableCommunityFilters"]);
	
}

function free_catgorized(){
	$category=$_GET["category"];
	$dans=new dansguardian_rules();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$tt=$_GET["t"];
	if(!is_numeric($tt)){$tt=0;}
	
	if($category==null){
		$cats=$dans->LoadBlackListes();
		while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
		$newcat[null]="{select}";
		$field_category="<span id='catz$t'>".Field_array_Hash($newcat,"free-category-add$t",null,"free_catgorized_explain()","style:font-size:16px")."</span>";	
		$refresh=imgtootltip("20-refresh.png","{refresh}","RefreshCatz$t()");
		
		
	}else{
		$field_category="
		<input type='hidden' id='free-category-add$t' name='free-category-add$t' value='$category'>
		<strong style='font-size:14px'>$category</strong>
		
		";
	}
	
	if($_GET["websitetoadd"]<>null){$website_default="http://".$_GET["websitetoadd"]."\n";}
	
	$html="
	<div class=explain style='font-size:13px' id='free-cat-explain$t'>{free_catgorized_explain}</div>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{category}:</td>
		<td>$field_category</td>
		<td width=1%>$refresh</td>
	</tr>
		<td class=legend>{force}:</td>
		<td>". Field_checkbox("ForceCat$t", 1)."</td>
		<td width=1%>". help_icon("{free_cat_force_explain}")."</td>
	</tr>
	<tr>
	<td colspan=3 align='center'>
		<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:150px;border:5px solid #8E8E8E;overflow:auto;font-size:16px' id='textToParseCats$t'>$website_default</textarea>
	</td>
	</tr>
	<tr>
	<td colspan=3 align='right'>
	
		". button("{submit}","FreeCategoryPost()",16)."
	</td>
	</tr>
	</table>
	<script>
	var x_FreeCategoryPost$t= function (obj) {
		var res=obj.responseText;
		var tt=$tt;
		if (res.length>0){
			document.getElementById('textToParseCats$t').value=res;
		}
		if(tt>0){ if(document.getElementById(tt)){ $('#'+tt).flexReload();} }
		
	}	

	function free_catgorized_explain(){
		if(!document.getElementById('free-category-add$t')){return;}
		var catz=document.getElementById('free-category-add$t').value;
		if(catz.length>0){
			LoadAjaxTiny('free-cat-explain$t','$page?free-cat-explain='+escape(catz));
		}
	}
	
	function FreeCategoryPost(){
		var XHR = new XHRConnection();
		var cat=document.getElementById('free-category-add$t').value;
		if(cat.length==0){return;}
		XHR.appendData('category',cat);
		XHR.appendData('textToParseCats',document.getElementById('textToParseCats$t').value);
		if(document.getElementById('ForceCat$t').checked){XHR.appendData('ForceCat',1);}else{XHR.appendData('ForceCat',0);}
		document.getElementById('textToParseCats$t').value='Processing....\\n\\n'+document.getElementById('textToParseCats$t').value;
		XHR.sendAndLoad('$page', 'POST',x_FreeCategoryPost$t);	
	}	

	function RefreshCatz$t(){
		LoadAjax('catz$t','$page?free-refresh-catz=yes');
	}
	
	free_catgorized_explain();
	
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function free_refresh_catgorized(){
	$tpl=new templates();
	$dans=new dansguardian_rules();
	$dans->CleanCategoryCaches();
	$cats=$dans->LoadBlackListes();	
	while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
	$newcat[null]="{select}";	
	echo $tpl->_ENGINE_parse_body(Field_array_Hash($newcat,"free-category-add",null,"free_catgorized_explain()","style:font-size:16px"));
}


function free_catgorized_explain(){
	$dans=new dansguardian_rules();
	$cats=$dans->LoadBlackListes();
	echo $cats[$_GET["free-cat-explain"]];
}

function already_Cats($www){
	$array[]="addthis.com";
	//$array[]="google.";
	$array[]="w3.org";
	$array[]="icra.org";
	$array[]="facebook.";
	while (list ($num, $wwws) = each ($array)){
		$pattern=str_replace(".", "\.", $wwws);
		if(preg_match("#$pattern#", $www)){return true;}
		
	}
	return false;
}

function QuickCategorize(){
	
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));	
	
	$www=$_POST["sitename"];
	$day=$_POST["day"];
	$category=$_POST["category"];

	$category_table="category_".$q->category_transform_name($category);
	if(!$q->TABLE_EXISTS($category_table)){
		$q->CreateCategoryTable($_POST["category"]);
		if(!$q->ok){echo "create table  $category_table failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";continue;}
	}	
	
	$md5=md5($category.$www);
	$q->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
	if(!$q->ok){echo "categorize $www failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";return;}
	
	$q->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
	if($day<>null){
		$timeday=strtotime("$day 00:00:00");
		$table=date("Ymd",$timeday)."_hour";
		$sql=$q->QUERY_SQL("UPDATE $table SET category='$category' WHERE sitename='$www'");
		if(!$q->ok){echo "categorize $www failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";return;}
	}
	
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?export-community-categories=yes");	
	$sock->getFrameWork("squid.php?NoCategorizedAnalyze=yes");
	
}

function free_catgorized_save(){
	include_once(dirname(__FILE__)."/ressources/class.html2text.inc");
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	
	
	$h2t =new html2text($_POST["textToParseCats"]);
	$h2t->get_text();
	
	while (list ($num, $ligne) = each (	$h2t->_link_array)){
		if(trim($ligne)==null){continue;}
		$ligne=strtolower($ligne);
		$ligne=str_replace("(whois)", "", $ligne);
		$ligne=trim($ligne);
		if(preg_match("#[0-9]+\.[0-9]+.[0-9]+.[0-9]+:[0-9]+#", $ligne)){continue;}
		if(strpos(" $ligne", "http")==0){$ligne="http://$ligne";}
		$hostname=parse_url($ligne,PHP_URL_HOST);
		
		
		
		if(preg_match("#^www\.(.+)#", $hostname,$re)){$hostname=$re[1];}
		if(preg_match("#^\.(.+)#", $hostname,$re)){$hostname=$re[1];}
		if(preg_match("#^\*\.(.+)#", $hostname,$re)){$hostname=$re[1];}
		writelogs("$ligne = $hostname",__FUNCTION__,__FILE__,__LINE__);
		$websitesToscan[]=$ligne;
	}	
	
	
	
	$_POST["textToParseCats"]=str_replace("<", "\n<", $_POST["textToParseCats"]);
	$_POST["textToParseCats"]=str_replace(' rel="nofollow"', "", $_POST["textToParseCats"]);
	$_POST["textToParseCats"]=str_replace("\r", "\n", $_POST["textToParseCats"]);
	$_POST["textToParseCats"]=str_replace("https:", "http:", $_POST["textToParseCats"]);
	$f=explode("\n",$_POST["textToParseCats"] );
	$ForceCat=$_POST["ForceCat"];
	if(!is_numeric($ForceCat)){$ForceCat=0;}
	while (list ($num, $www) = each ($f) ){
		writelogs("Scanning $www",__FUNCTION__,__FILE__,__LINE__);
		$www=str_replace("(whois)", "", $www);
		$www=trim($www);		
		$www=trim(strtolower($www));
		if($www==null){continue;}
		$www=stripslashes($www);
		if(preg_match("#href=\"(.+?)\">#", $www,$re)){$www=$re[1];}
		if(preg_match('#<a rel=.+?href="(.+?)"#', $www,$re)){$www=$re[1];}
		if(preg_match("#<a href.*?http://(.+?)([\/\"'>]#i",$www,$re)){$www=$re[1];}
		if(preg_match("#<span>www\.(.+?)\.([a-z]+)</span>#i",$www,$re)){$www=$re[1].".".$re[2];}
		$www=str_replace("http://", "", $www);
		if(preg_match("#\/\/.+?@(.+)#",$www,$re)){$websitesToscan[]=$re[1];}
		if(preg_match("#http.*?:\/\/(.+?)[\/\s]+#",$www,$re)){$websitesToscan[]=$re[1];continue;}
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		$www=str_replace("<a href=", "", $www);
		$www=str_replace("<img src=", "", $www);
		$www=str_replace("title=", "", $www);
		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/"));}
		if(preg_match("#\.php$#", $www,$re)){echo "$www php script...\n";continue;}
		if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){continue;}
		$www=str_replace('"', "", $www);
		writelogs("Success pass $www",__FUNCTION__,__FILE__,__LINE__);
		$websitesToscan[]=$www;
	}
	
	while (list ($num, $www) = each ($websitesToscan) ){$cleaned[$www]=$www;}
	$websitesToscan=array();
	while (list ($num, $www) = each ($cleaned) ){$websitesToscan[]=$www;}	
	
	
	while (list ($num, $www) = each ($websitesToscan) ){
		writelogs("Scanning $www",__FUNCTION__,__FILE__,__LINE__);
		$www=strtolower($www);
		$www=replace_accents($www);
		if($www=="www"){continue;}
		if($www=="ssl"){continue;}
		$www=str_replace("http://", "", $www);
		$www=str_replace("https://", "", $www);
		$www=str_replace("ftp://", "", $www);
		$www=str_replace("ftps://", "", $www);
		if(preg_match("#.+?@(.+)#",$www,$ri)){$www=$ri[1];}
		if(preg_match("#^www\.(.+?)$#i",$www,$ri)){$www=$ri[1];}
		if($ForceCat==0){
			if(already_Cats($www)){continue;}
		}
			
		if(strpos($www, '"')>0){$www=substr($www, 0,strpos($www, '"'));}
		if(strpos($www, "'")>0){$www=substr($www, 0,strpos($www, "'"));}
		if(strpos($www, ">")>0){$www=substr($www, 0,strpos($www, ">"));}
		if(strpos($www, "?")>0){$www=substr($www, 0,strpos($www, "?"));}
		if(strpos($www, "\\")>0){$www=substr($www, 0,strpos($www, "\\"));}
		if(strpos($www, "/")>0){$www=substr($www, 0,strpos($www, "/")-1);}
		if(preg_match("#^\.(.+)#", $www,$re)){$www=$re[1];}
		if(preg_match("#^\*\.(.+)#", $www,$re)){$www=$re[1];}		
		if(preg_match("#\.html$#i",$www,$re)){continue;}
		if(preg_match("#\.htm$#i",$www,$re)){continue;}
		if(preg_match("#\.gif$#i",$www,$re)){continue;}
		if(preg_match("#\.png$#i",$www,$re)){continue;}
		if(preg_match("#\.jpeg$#i",$www,$re)){continue;}
		if(preg_match("#\.jpg$#i",$www,$re)){continue;}
		if(preg_match("#\.php$#i",$www,$re)){continue;}
		if(preg_match("#\.js$#i",$www,$re)){continue;}
		if(!preg_match("#\.[a-z0-9]+$#",$www,$re)){;continue;}
		if(strpos(" ", trim($www))>0){continue;}
		$sites[$www]=$www;
		}
		
	
	$q=new mysql_squid_builder();
	$q->CheckTable_dansguardian();
	
	if(count($sites)==0){echo "NO websites\n";return;}
	$category=$_POST["category"];
	echo "\n----------------\nanalyze ".count($sites)." websites into $category\n";
	while (list ($num, $www) = each ($sites) ){
		$www=trim($www);
		if($www==null){continue;}
		if(preg_match("#^www\.(.+?)$#", $www,$re)){$www=$re[1];}
		writelogs("Analyze $www",__FUNCTION__,__FILE__,__LINE__);
		$md5=md5($category.$www);
		if($ForceCat==0){
			$cats=$q->GET_CATEGORIES($www,true,true,true);
			if($cats<>null){echo "FALSE: $www already categorized ($cats)\n";continue;}	
		}	
		
		$category_table="category_".$q->category_transform_name($category);
		if(!$q->TABLE_EXISTS($category_table)){
			$q->CreateCategoryTable($_POST["category"]);
			if(!$q->ok){echo "create table  $category_table failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";continue;}
		
		}
		
		$q->QUERY_SQL("INSERT IGNORE INTO $category_table (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
		if(!$q->ok){echo "categorize $www failed $q->mysql_error line ". __LINE__ ." in file ".__FILE__."\n";continue;}
		echo "TRUE: $www Added\n";
		$q->QUERY_SQL("INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')");
		if(!$q->ok){echo $q->mysql_error."\n";echo $sql;}
	}		
			
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?export-community-categories=yes");
	
	
}



function not_categorized(){
$page=CurrentPageName();
$tpl=new templates();
$country=$tpl->_ENGINE_parse_body("{country}");
$website=$tpl->_ENGINE_parse_body("{website}");
$hits=$tpl->_ENGINE_parse_body("{hits}");
$t=time();
$rescan=$tpl->javascript_parse_text("{rescan}");

	$table="visited_sites";
	$country_select=null;
	if($day<>null){
		$qDay=$day;
		$time=strtotime("$day 00:00:00");
		$table=date("Ymd",$time)."_hour";
		$country_select=",country";
	}	
	
	$week=trim($_GET["week"]);
	if($week<>null){
		$qDay=$week;
		$time=strtotime("{$_GET["week"]} 00:00:00");
		$table=date("YW",$time)."_week";
		
	}

	$month=trim($_GET["month"]);
	if($month<>null){
		$qDay=$month;
		$time=strtotime("{$_GET["month"]} 00:00:00");
		$table=date("Ym",$time)."_day";
	}

	if($table=="visited_sites"){
	$buttons="
	buttons : [
	
	{name: '$rescan', bclass: 'Reload', onpress : ResCanVisited$t},
	
	],	";
	
	}


$html="
<span id='SQUIDNOCATREFRESHTABLEID'></span>
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var mem$t;
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?no-cat-list=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}',
	dataType: 'json',
	colModel : [
		{display: '$country', name : 'country', width :25, sortable : false, align: 'center'},
		{display: '$website', name : 'sitename', width :334, sortable : true, align: 'left'},
		{display: 'Google', name : 'google', width :31, sortable : false, align: 'center'},
		{display: 'Link', name : 'link', width :31, sortable : false, align: 'center'},
		{display: '$hits', name : 'HitsNumber', width :47, sortable : true, align: 'left'},
		{display: '$categorize', name : 'client', width :46, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'ffff', width :215, sortable : false, align: 'left'},

	],
$buttons
	searchitems : [
		{display: '$website', name : 'sitename'},
		],
	sortname: 'HitsNumber',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 25,
	showTableToggleBtn: false,
	width: 839,
	height: 369,
	singleSelect: true
	
	});   
});

	function SQUIDNOCATREFRESHTABLE(){
		$('#table-$t').flexReload();
	}
	
	var x_ResCanVisited$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');return;}
		$('#table-$t').flexReload();
	}	
	
	var x_QuickCategorize= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert('\"'+results+'\"');return;}
		$('#row'+mem$t).remove();
	}

	function ResCanVisited$t(){
			var XHR = new XHRConnection();
			XHR.appendData('ResCanVisited','yes');
			XHR.sendAndLoad('$page', 'POST',x_ResCanVisited$t);		
	}
	
	
	function QuickCategorize(sitename,id){
			mem$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('QuickCategorize','yes');
			XHR.appendData('sitename',sitename);
			var category=document.getElementById('dropdown-'+id).value;
			XHR.appendData('category',category);
			XHR.appendData('day','{$_GET["day"]}');
			if(category.length==0){return;}
			XHR.sendAndLoad('$page', 'POST',x_QuickCategorize);		
	
	}


	function SQUID_NOT_CAT_SEARCH_CHECK(e){
		if(!checkEnter(e)){return;}
		var value=document.getElementById('not-cat-search').value;
		value=trim(value);
		Set_Cookie('SQUID_NOT_CAT_SEARCH',value, '3600', '/', '', '');
		LoadAjax('not_categorized_sites','$page?no-cat-list=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}');
	}
	LoadAjax('not_categorized_sites','$page?no-cat-list=yes&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}');
	
</script>
";
$tpl=new templates();
echo $tpl->_ENGINE_parse_body($html);	
	
}
function not_categorized_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$categorize_all=null;
	$day=trim($_GET["day"]);
	
	$table="visited_sites";
	$country_select=null;
	if($day<>null){
		$qDay=$day;
		$time=strtotime("$day 00:00:00");
		$table=date("Ymd",$time)."_hour";
		$country_select=",country";
	}	
	
	$week=trim($_GET["week"]);
	if($week<>null){
		$qDay=$week;
		$time=strtotime("{$_GET["week"]} 00:00:00");
		$table=date("YW",$time)."_week";
		
	}

	$month=trim($_GET["month"]);
	if($month<>null){
		$qDay=$month;
		$time=strtotime("{$_GET["month"]} 00:00:00");
		$table=date("Ym",$time)."_day";
	}	
	
	
	$search='%';
	$page=1;
	
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".trim($_POST["query"])."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$categorize_all="CategorizeAll('{$_POST["query"]}');";
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT SUM(hits) as HitsNumber,sitename,category$country_select FROM $table GROUP BY sitename,category$country_select HAVING LENGTH(category)=0 $searchstring";
		if($table=="visited_sites"){$sql="SELECT sitename FROM $table WHERE LENGTH(category)=0 $searchstring";}
		$results=$q->QUERY_SQL($sql);
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
		
		
			
		
		
	}else{
		$sql="SELECT SUM(hits) as HitsNumber,sitename,category$country_select FROM $table GROUP BY sitename,category$country_select HAVING LENGTH(category)=0";
		
		if($table=="visited_sites"){$sql="SELECT sitename FROM $table WHERE LENGTH(category)=0";}		
		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){writelogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__);}
		$total = mysql_num_rows($results);
		writelogs("$sql = `$total`",__FUNCTION__,__FILE__,__LINE__);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	if($OnlyEnabled){$limitSql=null;}
	$sql="SELECT SUM(hits) as HitsNumber,sitename,category$country_select 
	FROM $table GROUP BY sitename,category$country_select HAVING LENGTH(category)=0 $searchstring $ORDER $limitSql";
	
	if($table=="visited_sites"){
		$sql="SELECT sitename,HitsNumber$country_select FROM visited_sites WHERE LENGTH(category)=0 $searchstring $ORDER $limitSql";
	}
	
		
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
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
		
		$dans=new dansguardian_rules();
		$cats=$dans->LoadBlackListes();
		while (list ($num, $ligne) = each ($cats) ){$newcat[$num]=$num;}
		$newcat[null]=$tpl->_ENGINE_parse_body("{select}");
			
				
		
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$categorize_link=null;
		if($ligne["country"]<>null){
			if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]=GetFlags($ligne["country"]);}
		}	
		if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]="flags/a2.png";}

			$catjs="Loadjs('squid.categorize.php?www={$ligne["sitename"]}&day={$_GET["day"]}&week={$_GET["week"]}&month={$_GET["month"]}')";
			if($categorize_all<>null){
				
				$categorize_link=$tpl->_ENGINE_parse_body("&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:$catjs\" 
				style='text-decoration:underline'>[{categorize}]</a>");
				$catjs=$categorize_all;
			}
			
			
			$categorize=imgtootltip("add-database-32.png","{categorize} {$ligne["sitename"]}","$catjs");
		
			$sitename="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.websites.infos.php?www={$ligne["sitename"]}&day=$qDay');\"
			style='font-size:14px;text-decoration:underline'>{$ligne["sitename"]}</a>";
			
			$id=md5($ligne['sitename']);
			
			$field_category=Field_array_Hash($newcat,"dropdown-$id",null,"blur()","style:font-size:12.5px");	
			$field_category="<table style=\"margin:0;padding:0;border:0\">
			<tr style=\"margin:0;padding:0;border:0\">
				<td style=\"margin:0;padding:0;border:0\">$field_category</td>
				<td style=\"margin:0;padding:0;border:0;padding-left:5px\">". imgsimple("ok-blue-left-24.png",null,"QuickCategorize('{$ligne['sitename']}','$id')")."</td>
			</tr>
			</table>
			";
			
			
		
			$data['rows'][] = array(
			'id' => $id,
			'cell' => array(
				"<img src='img/{$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]}>",
				 "$sitename$categorize_link",
				imgsimple("Google-24.png",null,"s_PopUpFull('http://www.google.com/search?q=%22{$ligne["sitename"]}%22&ie=utf-8&oe=utf-8&client=ubuntu&channel=fs&safe=active&safeui=on',800,800)"),
				imgsimple("link-24.png",null,"s_PopUpFull('http://{$ligne["sitename"]}',800,800)"),
				
				"<span style='font-size:14px;font-weight:bold'>{$ligne["HitsNumber"]}</span>",$categorize,$field_category)
			);
	}
	
	
echo json_encode($data);		

	
}
function categorized(){
$page=CurrentPageName();	
$tpl=new templates();
$html="
<table>
<td class=legend>{search}:</td>
<td>". Field_text("cat-search",trim($_COOKIE["SQUID_NOT_CAT_SEARCH"]),"font-size:13px;padding:3px",null
,null,null,false,"SQUID_NOT_CAT_SEARCH_CHECK(event)")."</td>
</tr>
</table>

<div id='categorized_sites' style='height:450px;overflow:auto'></div>
<script>
	function SQUID_NOT_CAT_SEARCH_CHECK(e){
		if(!checkEnter(e)){return;}
		var value=trim(document.getElementById('cat-search').value);
		Set_Cookie('SQUID_NOT_CAT_SEARCH',value, '3600', '/', '', '');
		LoadAjax('categorized_sites','$page?yes-cat-list=yes&day={$_GET["day"]}&week={$_GET["week"]}');
	}
	LoadAjax('categorized_sites','$page?yes-cat-list=yes&day={$_GET["day"]}&week={$_GET["week"]}');
	
</script>
";

echo $tpl->_ENGINE_parse_body($html);		
	
}

function visited_list(){
	
	$_COOKIE["SQUID_NOT_CAT_SEARCH"]=trim($_COOKIE["SQUID_NOT_CAT_SEARCH"]);
	if($_COOKIE["SQUID_NOT_CAT_SEARCH"]<>null){$pattern=" WHERE sitename LIKE '%{$_COOKIE["SQUID_NOT_CAT_SEARCH"]}%' ";$pattern=str_replace("*","%",$pattern);}
	$sql="SELECT sitename as website,visited_sites.*  FROM visited_sites $pattern ORDER BY HitsNumber DESC LIMIT 0,100";
	
	
	$day=trim($_GET["day"]);
	if($day<>null){
		$time=strtotime("$day 00:00:00");
		$table=date("Ymd",$time)."_hour";
		
		if($_COOKIE["SQUID_NOT_CAT_SEARCH"]<>null){$pattern=" HAVING website LIKE '%{$_COOKIE["SQUID_NOT_CAT_SEARCH"]}%' ";$pattern=str_replace("*","%",$pattern);}
		$sql="SELECT SUM(hits) as HitsNumber, sitename as website,category,country FROM $table 
		GROUP BY sitename,category,country $pattern ORDER BY HitsNumber DESC LIMIT 0,150";
	}	

	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{country}</th>
	<th colspan=2>{website}&nbsp;$day</th>
	<th>{hits}</th>
	</tr>
</thead>
<tbody class='tbody'>";

	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["country"]<>null){
			if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){
				$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]=GetFlags($ligne["country"]);
			}
		}
		
			if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]="flags/a2.png";}
		
			$country_text="<strong style=font-size:13px>{$ligne["country"]}<br>{$ligne["ipaddr"]}</strong>";
			$country=imgtootltip($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]],$country_text);
			$delete=imgtootltip("delete-24.png","{delete}","RoutesServerDelete('{$ligne["ipaddr"]}')");
			
			$html=$html."
			<tr class=$classtr>
			<td width=1% valign='middle' align='center'>$country</td>
			<td width=99%><strong style='font-size:14px'>{$ligne["website"]}<br><i style='font-size:11px;color:#970909'>{$ligne["category"]}</i></td>
			<td width=1% valign='middle' align='center'><strong style='font-size:14px'>{$ligne["hits"]}</strong></td>
			<td width=1%>". imgtootltip("add-database-32.png","{categorize}","Loadjs('squid.categorize.php?www={$ligne["website"]}')")."</td>
			</tr>
			";
			
	
			
				
		
	}	
	$html=$html."</tbody></table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

	

function categorized_list(){
	$_COOKIE["SQUID_NOT_CAT_SEARCH"]=trim($_COOKIE["SQUID_NOT_CAT_SEARCH"]);
	if($_COOKIE["SQUID_NOT_CAT_SEARCH"]<>null){
		$pattern=" AND sitename LIKE '%{$_COOKIE["SQUID_NOT_CAT_SEARCH"]}%' ";
		$pattern=str_replace("*","%",$pattern);
	}	
	
	$sql="SELECT * FROM `squid_events_sites` WHERE `category` != '' $pattern ORDER by hits DESC LIMIT 0 , 100";
	$sql="SELECT sitename ,visited_sites.*  FROM visited_sites WHERE 1 $pattern AND LENGTH(category)>1 ORDER BY HitsNumber DESC LIMIT 0,100";
	$page=CurrentPageName();

	
	$day=trim($_GET["day"]);
	if($day<>null){
		$time=strtotime("$day 00:00:00");
		$table=date("Ymd",$time)."_hour";
		
		if($_COOKIE["SQUID_NOT_CAT_SEARCH"]<>null){$pattern=" HAVING sitename LIKE '%{$_COOKIE["SQUID_NOT_CAT_SEARCH"]}%' ";$pattern=str_replace("*","%",$pattern);}
		if($pattern==null){$pattern="HAVING LENGTH(category)>2";}else{$pattern=$pattern ." AND LENGTH(category)>2";}
		$sql="SELECT SUM(hits) as HitsNumber, sitename,category,country FROM $table 
		GROUP BY sitename,category,country $pattern ORDER BY HitsNumber DESC LIMIT 0,100";
	}	

	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	$maxrow=mysql_num_rows($results);	
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{country}</th>
	<th>{website}&nbsp;($maxrow {items}) $day</th>
	<th>{hits}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";


	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($ligne["country"]<>null){
			if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){
				$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]=GetFlags($ligne["country"]);
				}
			}	
			if($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]==null){$_SESSION["COUNTRIES_FLAGS"][$ligne["country"]]="flags/a2.png";}
			$ligne["dbpath"]=str_replace(",",", ",$ligne["dbpath"]);
			$country_text="<strong style=font-size:13px>{$ligne["country"]}<br>{$ligne["ipaddr"]}</strong>";
			$country=imgtootltip($_SESSION["COUNTRIES_FLAGS"][$ligne["country"]],$country_text);
			$delete=imgtootltip("delete-24.png","{delete}","RoutesServerDelete('$ip')");
			$html=$html."
			<tr class=$classtr>
			<td width=1% valign='middle' aling='center'>$country</td>
			<td width=99%><strong style='font-size:14px'>{$ligne["sitename"]}<br><i style='font-size:11px;color:#970909'>{$ligne["category"]}</i></td>
			<td width=1%><strong style='font-size:14px'>{$ligne["HitsNumber"]}</td>
			<td width=1%>". imgtootltip("add-database-32.png","{categorize}","Loadjs('squid.categorize.php?www={$ligne["sitename"]}')")."</td>
			</tr>
			";
			
		
		
	}	
	$html=$html."</tbody></table>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function CategorizeAll_popup(){
	
	$pattern=" AND sitename LIKE '%{$_GET["CategorizeAll"]}%' ";
	$pattern=str_replace("*","%",$pattern);
	$sql="SELECT COUNT( sitename ) AS tcount
	FROM `visited_sites`
	WHERE LENGTH( `category` )=0
	$pattern";
	
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	if(!$q->ok){echo "<H3>$q->mysql_error</H3>";}
	$count=$ligne["tcount"];
	$dans=new dansguardian_rules();
	$array_blacksites=$dans->LoadBlackListes();
	while (list($num,$val)=each($array_blacksites)){	
		$blcks[$num]=$num;
		
	}
	$blcks[null]="{select}";
	$field=Field_array_Hash($blcks,"CategorizeAll_category",null,"CategorizeAllDef()",null,0, "font-size:16px;padding:3px");
	
	$html="
	<div id='cat-perf-all'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{pattern}:</td>
		<td><strong style='font-size:13px'>{$_GET["CategorizeAll"]}</td>
	</tr>	
	<tr>
		<td class=legend>{websites}:</td>
		<td><strong style='font-size:13px'>$count</td>
	</tr>
	<tr>
		<td class=legend>{category}:</td>
		<td>$field</td>
	</tr>
	<tr><td colspan=2><div id='cat-explain'></div></td>	
	<tr>
		<td colspan=2 align='right'><hr>". button("{categorize}","CategorizeAllPerform();",16)."</td>
	</tr>
	</table>
	</div>
	<script>
		function CategorizeAllDef(){
			LoadAjax('cat-explain','$page?cat-explain='+escape(document.getElementById('CategorizeAll_category').value)+'&day={$_GET["day"]}');
		}
		var x_CategorizeAllPerform=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	if(document.getElementById('SQUIDNOCATREFRESHTABLEID')){SQUIDNOCATREFRESHTABLE();}
     	YahooWin4Hide();
     
     	}	
      


	function CategorizeAllPerform(){
		var CategorizeAll_category=document.getElementById('CategorizeAll_category').value;
		if(CategorizeAll_category.length>0){
			var XHR = new XHRConnection();
			XHR.appendData('CategorizeAll_category',CategorizeAll_category);
			if(confirm('*{$_GET["CategorizeAll"]}*: -> $count $websites -> '+CategorizeAll_category+'?')){
				var XHR = new XHRConnection();
				XHR.appendData('CategorizeAll_category',CategorizeAll_category);
				XHR.appendData('pattern','{$_GET["CategorizeAll"]}');
				XHR.appendData('day','{$_GET["day"]}');
				XHR.appendData('week','{$_GET["week"]}');
				document.getElementById('cat-perf-all').innerHTML='<center><img src=\"img/wait_verybig.gif\"></center>';
				XHR.sendAndLoad('$page', 'GET',x_CategorizeAllPerform);		
			}
			}
	}		
		
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);

}

function CategorizeAll_explain(){
	$tpl=new templates();
	$dans=new dansguardian_rules();
	$text=$dans->array_blacksites[$_GET["cat-explain"]];
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:14px'>$text</div>");
	
}

function CategorizeAll_perform(){
	if($_GET["pattern"]==null){return;}
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	if($uuid==null){echo "UUID=NULL; Aborting";return;}	
	$pattern=" AND sitename LIKE '%{$_GET["pattern"]}%' ";
	$pattern=str_replace("*","%",$pattern);
	$sql="SELECT sitename FROM `visited_sites` WHERE  LENGTH( `category` )=0 $pattern";
	$category=$_GET["CategorizeAll_category"];
	if($category==null){return;}
	if($category=="teans"){$category="teens";}
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__;
		return;
	}
	if($_GET["week"]<>null){$_GET["day"]=$_GET["week"];}
	
	$category_table="category_".$q->category_transform_name($category);
	
	if(!$q->TABLE_EXISTS($category_table)){$q->CreateCategoryTable($category);}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$website=$ligne["sitename"];
		if($website==null){return;}
		$www=trim(strtolower($website));
		if(preg_match("#^www\.(.+?)$#i",trim($www),$re)){$www=$re[1];}
		$md5=md5($category.$www);
		$enabled=1;
		if($www==null){echo "Alert: website is null...\n";return;}
		
		$sql_add="INSERT INTO $category_table (zmd5,zDate,category,pattern,uuid,enabled) VALUES('$md5',NOW(),'$category','$www','$uuid',1)";
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT category,pattern FROM `$category_table` WHERE zmd5='$md5'"));
		if($ligne["category"]==null){$sql_add="UPDATE $category_table SET `category`='$category',pattern='$www' WHERE zmd5='$md5'";}
		if($ligne["pattern"]==null){$sql_add="UPDATE $category_table SET `category`='$category',pattern='$www' WHERE zmd5='$md5'";}
		$q->QUERY_SQL($sql_add);
		if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;return;}
		
		$sql="INSERT IGNORE INTO categorize (zmd5,zDate,category,pattern,uuid) VALUES('$md5',NOW(),'$category','$www','$uuid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;return;}	
		
		
	$sql="UPDATE `visited_sites` SET `category`='$category' WHERE LENGTH( `category` )=0 $pattern";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;return;}			
			
		if($_GET["day"]<>null){
			if($pattern<>null){
				$time=strtotime($_GET["day"]." 00:00:00");
				$tableSrc=date('Ymd',$time)."_hour";
				$categories=$q->GET_CATEGORIES($www,true);
				if($categories==null){$categories=$category;}
				
				$sql="UPDATE $tableSrc SET category='$categories' WHERE LENGTH( `category` )=0 $pattern";
				$q->QUERY_SQL($sql);
				writelogs($sql,__FUNCTION__,__FILE__,__LINE__);                                                                                                                                                                                                                                                                                                                                        
				if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;}
				
				
				$tableWeek=date("YW",$time)."_week";
				$sql="UPDATE $tableWeek SET category='$categories' WHERE LENGTH( `category` )=0 $pattern";
				$q->QUERY_SQL($sql);
				writelogs($sql,__FUNCTION__,__FILE__,__LINE__);  
				if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;}
				
				
				$tableSrcDay=date('Ym',$time)."_day";
				if($q->TABLE_EXISTS($tableSrcDay)){
					$sql="UPDATE $tableSrcDay SET category='$categories' WHERE LENGTH( `category` )=0 $pattern";
					writelogs($sql,__FUNCTION__,__FILE__,__LINE__);  
					$q->QUERY_SQL($sql);
					if(!$q->ok){echo $q->mysql_error."\n".basename(__FILE__)."\nLine".__LINE__."\n";echo $sql;}
				}
				
				
			}else{
				echo "Pattern is null!!\n";
			}
		}		
		
	}
	

	$sock=new sockets();
	$sock->getFrameWork("cmd.php?export-community-categories=yes");	
	
}

