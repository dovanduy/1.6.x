<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["display-synthesis"])){synthesis();exit;}
	
	if(isset($_GET["form"])){form();exit;}
	if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["paragraphe1"])){paragraphe1();exit;}
	if(isset($_GET["paragraphe2"])){paragraphe2();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{week}&raquo;&raquo;{category}&raquo;&raquo;{$_GET["category"]}");
	$html="YahooWin4('890','$page?popup=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}&category={$_GET["category"]}','$title')";
	echo $html;
	
}

function form(){
	$t=time();
	$table=$_GET["table"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="<center>
			". Field_text("category-$t-search",null,"font-size:16px;width:220px",null,null,null,false,"WeekCategorySearchCheck$t(event)")."
		</center>
	<div id='category-$t-list' style='margin-top:10px'></div>
	<script>
		function WeekCategorySearchCheck$t(e){
			if(checkEnter(e)){WeekCategorySearch();}
		}
	
	
		function WeekCategorySearch(){
			var search=escape(document.getElementById('category-$t-search').value);
			LoadAjaxTiny('category-$t-list','$page?search=yes&table=$table&user={$_GET["user"]}&field={$_GET["field"]}&category={$_GET["category"]}&query='+search);
		}
		
		WeekCategorySearch();
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
		
	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$html="
	<table style='width:101%;margin-left:-15px;margin-right:-15px'>
	<tbody>
	<tr>
		<td valign='top' width=1%><div id='week-search-users-list-$t'></div></td>
		<td valign='top' width=99%>
			<div id='$t'></div>
		</td>
	</tr>
	</tbody>
	</table>
	
	<script>
		function RefreshLeftPanel(user,field,table,category){
			if(!table){table='{$_GET["table"]}';}
			if(!user){user='{$_GET["user"]}';}
			if(!field){field='{$_GET["field"]}';}
			if(!category){category='{$_GET["category"]}';}
			LoadAjax('$t','$page?display-synthesis=yes&user='+user+'&field='+field+'&table='+table+'&category='+category+'&divkey=$t');
		
		}
		RefreshLeftPanel();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function search(){
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];	
	$category=$_GET["category"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();
	$query=$_GET["query"];
	$query="$query*";
	$query=str_replace("**", "*", $query);
	$query=str_replace("**", "*", $query);
	$query=str_replace("*", "%", $query);

	$t=$_GET["divkey"];	
	if(!isset($_SESSION["SQUIDSTATS"]["WEEK-CAT"][$table])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(category) as tcount from $table GROUP BY category"));
		$_SESSION["SQUIDSTATS"]["WEEK-CAT"][$table]=$ligne["tcount"];
	}	
	
	$title="<div style='font-size:12px;'>
		<strong>{$_SESSION["SQUIDSTATS"]["WEEK-CAT"][$table]}</strong> {others_categories_visited}</div>";
	
	
	$sql="SELECT category from $table GROUP BY category HAVING category LIKE '$query' ORDER BY category LIMIT 0,25";
	$results=$q->QUERY_SQL($sql);
	
	$rows[]="<table style='width:99%' class=form>
	<tr>
		<td colspan=2>$title</td>
	</tr>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$usertext=$ligne["category"];
		if($usertext==null){continue;}
		$eght=strlen($usertext);
		if($eght>25){$usertext=substr($usertext,0,22)."...";}
		
		$rows[]="<tr>
		<td width=1% valign='top'><IMG SRC='img/20-categories-personnal.png'></TD>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?user=$user&field=$field&table=$table&category={$ligne["category"]}')\" style='font-weight:bold;font-size:12px;text-decoration:underline'>$usertext</a>
		
		</td></tr>";
		
	}
	
	$html=$html.@implode("\n", $rows)."</tbody></table>
	<script>
		function LoadUserReport1(){
			LoadAjaxTiny('paragraphecat1','$page?paragraphe1=yes&table=$table&user=$user&field=$field&category=$category');
		}
		
	function LoadUserReport2(){
			LoadAjaxTiny('paragraphecat2','$page?paragraphe2=yes&table=$table&user=$user&field=$field&category=$category');
		}		
		
		LoadUserReport1();
	</script>	
	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}

function paragraphe2(){
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];	
	$category=$_GET["category"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();
	

		$qs="category='$category'";
	
	
	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,category,sitename FROM $table
	GROUP BY category,sitename
	HAVING $qs ORDER BY hits DESC LIMIT 0,10";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H4>$q->mysql_error</H4>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$ydata[]="{$ligne["sitename"]}";
		$xdata[]=$ligne["thits"];			
		$js="Loadjs('squid.traffic.statistics.week.website.php?www={$ligne["sitename"]}&field=sitename&table=$table')";
		$rows[]="<tr>
		<td width=1%><img src='img/web-22.png'></td>
		<td style='font-size:12px;'><a href=\"javascript:blur();\" style='font-size:12px;font-weight:bold;text-decoration:underline' OnClick=\"javascript:$js\">{$ligne["sitename"]}</a> ({$ligne["thits"]} {hits} - {$ligne["tsize"]})</strong></td></tr>";
	}	
	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".".time().".png";
	$gp=new artica_graphs($targetedfile);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=250;
	$gp->height=400;
	$gp->ViewValues=true;
	//$gp->PieLegendHide=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{cache}");
	$gp->pie();		
	
	
	$html="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>{top_visited_websites} :</div>
	<table style='width:100%'>
	<tbody>
	". @implode("\n", $rows)."
	</tbody>
	</table>
	<center style='margin-top:10px'><img src='$targetedfile'></center>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function paragraphe1(){
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];	
	$category=$_GET["category"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$categoriesExplain=$dans->LoadBlackListes();
	$separator="<center><hr style='border:1px dotted #CCCCCC;width:80%'></center>";
	
	if(strpos($category, ",")>0){
		$categories=explode(",", $category);
		while (list ($a, $b) = each ($categories) ){
			$titleCat=$titleCat. "<div style='font-weight:bold;border-bottom:1px solid #CCCCCC'>$b:</div>
			<div style='font-size:12px;margin-bottom:8px;font-style:italic'><i>{$categoriesExplain["$b"]}</i></div>";
		}
		
	}else{$titleCat=
	"<div style='font-weight:bold;border-bottom:1px solid #CCCCCC;font-size:13.5px'>{category}: $category</div>
	<div style='font-size:12px;margin-bottom:8px;font-style:italic'>{$categoriesExplain["$category"]}</div>";}
	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table"));
	$downloadedINT=$ligne["tsize"];	
	
	$sql="SELECT COUNT(sitename) as tcount FROM $table WHERE category='$category'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$NombreDeSites=$ligne["tcount"];
	
	$sql="SELECT SUM(size) as tsize FROM $table WHERE `category`='$category'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$total_category_size_bin=$ligne["tsize"];
	$total_category_size_text="<strong>".FormatBytes($total_category_size_bin/1024)."</strong>";

	$sql="SELECT SUM(hits) as tsize FROM $table WHERE `category`='$category'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$total_category_hits_bin=$ligne["tsize"];
	
	
	
	$sql="SELECT SUM(size) as tsize FROM $table WHERE `category`='$category' AND cached=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$total_category_cache_bin=$ligne["tsize"];
	$total_category_cache_text="<strong>".FormatBytes($total_category_size_bin/1024)."</strong>";
	
	
	$PRC=(($total_category_size_bin/$downloadedINT)*100);
	$PRC=round($PRC,3);
	
	
	$text2=$tpl->_ENGINE_parse_body("{webstatsCatIntro2}");
	$text2=str_replace("XMB", "<strong>$total_category_size_text</strong>", $text2);
	$text2=str_replace("XRQ", "<strong>$total_category_hits_bin</strong>", $text2);
	$text2=str_replace("XPRC", "<strong>$PRC%</strong>", $text2);
	 
	//*****************************************************************************************
	
	$sql="SELECT SUM(size) as tsize,sitename,category FROM $table GROUP BY sitename,category HAVING `category`='$category' ORDER BY tsize DESC";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$siteWebLeplusLourd=$ligne["sitename"];
	$siteWebLeplusLourd_bin=$ligne["tsize"];
	$siteWebLeplusLourd_text=FormatBytes($ligne["tsize"]/1024);
		
	
	$sql="SELECT SUM(hits) as tsize,sitename,category FROM $table GROUP BY sitename,category HAVING `category`='$category' ORDER BY tsize DESC";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$siteWebLeplusVisite=$ligne["sitename"];
	$siteWebLeplusVisite_bin=$ligne["tsize"];

	
	$catz=sitename_MergeCategories(array($siteWebLeplusLourd,$siteWebLeplusVisite));
	
	$phrase_the_most_websites=$tpl->_ENGINE_parse_body("{phrase_the_most_websites}");
	$phrase_the_most_websites=str_replace("XXWWWS", "<strong>$siteWebLeplusLourd</strong>", $phrase_the_most_websites);
	$phrase_the_most_websites=str_replace("XWSZE", "<strong>$siteWebLeplusLourd_text</strong>", $phrase_the_most_websites);
	$phrase_the_most_websites=str_replace("XXWWWR", "<strong>$siteWebLeplusVisite</strong>", $phrase_the_most_websites);
	$phrase_the_most_websites=str_replace("XWSZR", "<strong>$siteWebLeplusVisite_bin</strong>", $phrase_the_most_websites);
	$phrase_the_most_websites=str_replace("XWSCAT", $catz, $phrase_the_most_websites);	
	
	$phrase_the_most_websites="<div style='font-size:12px;'>$phrase_the_most_websites</div>";	
	
	
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize FROM $table WHERE `$field`='$user'"));
	$X2_bin=$ligne["tsize"];
	$X2_text="<strong>$X2_bin</strong>";		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(familysite) as familysite,`$field` FROM $table WHERE `$field`='$user'"));
	$X0_bin=$ligne["familysite"];
	$X0_text="<strong>$X0_bin</strong>";	
	
	$webstatsUserIntro1=$tpl->_ENGINE_parse_body("{webstatsUserIntro1}");
	$webstatsUserIntro1=str_replace("X3PRC", "<strong style='color:#CF1717'>$PRC%</strong>", $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X0", $X0_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X1", $X1_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X2", $X2_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X3", $X3_text, $webstatsUserIntro1);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize,familysite,`$field` FROM $table GROUP BY familysite,`$field` HAVING `$field`='$user' ORDER BY tsize DESC LIMIT 0,1"));
	$XXWWWS=$ligne["familysite"];
	$XWSZE=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize,familysite,`$field` FROM $table GROUP BY familysite,`$field` HAVING `$field`='$user' ORDER BY tsize DESC LIMIT 0,1"));
	$XXWWWR=$ligne["familysite"];
	$XWSZR=$ligne["tsize"];
	$XWSCAT=familysite_MergeCategories(array($XXWWWS,$XXWWWR));
	
	$line=$tpl->_ENGINE_parse_body("{phrase_the_most_websites}");
	$line=str_replace("XXWWWS", "<strong>$XXWWWS</strong>", $line);
	$line=str_replace("XWSZE", "<strong>$XWSZE</strong>", $line);
	$line=str_replace("XXWWWR", "<strong>$XXWWWR</strong>", $line);
	$line=str_replace("XWSZR", "<strong>$XWSZR</strong>", $line);
	$line=str_replace("XWSCAT", "<strong>$XWSCAT</strong>", $line);		
	$webstatsUserIntro2="<div style='font-size:12px;text-align:justify'>$line</div>";	
	
	
//*********************************************************************** GRAPHIQUE PAR JOUR

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `day`,SUM(hits) as thits,SUM(size) as tsize,
	`category`  FROM $table GROUP BY `day`,`category`  HAVING `category`='$category' ORDER BY thits DESC LIMIT 0,1"));
	if(!$q->ok){echo $q->mysql_error;}
	$MostActiveDayNum=$ligne["day"]-1;
	$MostActiveDaySize=FormatBytes($ligne["tsize"]/1024);
	$Cyear=substr($table, 0,4);
	$Cweek=substr($table,4,2);
	$Cweek=str_replace("_", "", $Cweek);	
	$days=$q->getDaysInWeek($Cweek,$Cyear);
	$dayText=date('{l}', $days[$MostActiveDayNum]);
	$title="$dayText {phrase_most_day_activeday} {for} $category";
	$prc=round($ligne["tsize"]/$downloadedINT,2)*100;
	
	$webstatsUserIntro3="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$title</div>
	<div style='font-size:12px;text-align:justify;'>{with} <strong>{$ligne["thits"]} {hits}</strong> {or} <strong>$MostActiveDaySize</strong> {it_represents} <strong>$prc%</strong> {of_bandwith}</div>
	<div style='font-size:12px;text-align:justify;margin-top:10px;margin-bottom:15px'>{phrase_thisisthegraph1}:</div>"; 
	
	$sql="SELECT `day`,SUM(hits) as thits,`category` FROM $table GROUP BY `day`,category HAVING category='$category'  ORDER BY `day`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$xdata[]=$ligne["day"];
		$ydata[]=$ligne["thits"];
	}	
	$time=time();
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".$table.$time.png";
	$gp=new artica_graphs();
	$gp->width=270;
	$gp->height=150;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	
	if(!is_file($targetedfile)){$targetedfile="img/nograph-000.png";}
	$graph1="<center style='margin-top:5px'><img src='$targetedfile'></center>";
	
	
	$HTML="$titleCat
	<p style='font-size:12px'><strong>$NombreDeSites</strong> {webstatsCatIntro1}.</p>
	<p style='font-size:12px'>$text2</p>
	$phrase_the_most_websites
	$separator
	$webstatsUserIntro3
	$graph1
	
	<script>LoadUserReport2()</script>
	";
	echo $tpl->_ENGINE_parse_body($HTML);
	
}


function synthesis(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];
	$t=$_GET["divkey"];
	$category=$_GET["category"];
	$titleW=$q->WEEK_TITLE_FROM_TABLENAME($table);
	
	$html="
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%><img src='img/64-categories-loupe.png'></td>
		<td><div style='width:100%;font-size:16px;font-weight:bold'>{category} $category <br>$titleW</div></td>
	</tr>
	</tbody>
	</table>
	<table style='width:100%'>
	<tbody>
		<tr>
			<td width=50% valign='top'><div id='paragraphecat1'></div></td>
			<td width=50% valign='top'><div id='paragraphecat2'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		function LoadUserSearchSection(){
			LoadAjaxTiny('week-search-users-list-$t','$page?form=yes&table=$table&user=$user&field=$field&category=$category');
		}
		
		LoadUserSearchSection();
	</script>
			
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function sitename_MergeCategories($array){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$catz=array();
	
	while (list ($index, $familysite) = each ($array) ){
		
		$sql="SELECT category FROM visited_sites WHERE sitename='$familysite'";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

			if(strpos($ligne["category"], ",")>0){
				$as=explode(",", $ligne["category"]);
				while (list ($a, $b) = each ($as) ){$catz[$b]=$b;}
				continue;
			}
			$catz[$ligne["category"]]=$ligne["category"];
		}
		
	}
	
	while (list ($a, $b) = each ($catz) ){if($b==null){continue;}$c[]=$b;}
	
	$cats=texttooltip(": ".count($c)." {categories}",@implode(",<br>", $c),null,null,0,"font-size:12px;nodiv");
	return $tpl->_ENGINE_parse_body($cats);
}


function familysite_MergeCategories($array){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$catz=array();
	
	while (list ($index, $familysite) = each ($array) ){
		
		$sql="SELECT category FROM visited_sites WHERE familysite='$familysite'";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){

			if(strpos($ligne["category"], ",")>0){
				$as=explode(",", $ligne["category"]);
				while (list ($a, $b) = each ($as) ){$catz[$b]=$b;}
				continue;
			}
			$catz[$ligne["category"]]=$ligne["category"];
		}
		
	}
	
	while (list ($a, $b) = each ($catz) ){if($b==null){continue;}$c[]=$b;}
	
	$cats=texttooltip(": ".count($c)." {categories}",@implode(",<br>", $c),null,null,0,"font-size:12px;nodiv");
	return $tpl->_ENGINE_parse_body($cats);
}
