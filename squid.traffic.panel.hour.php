<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){echo "<H2>No rights</H2>";die();}
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["master"])){master_table();exit;}
	if(isset($_GET["panel-categories"])){panel_categories();exit;}
	if(isset($_GET["panel-topwebistes"])){topwebsites_graph();exit;}
	if(isset($_GET["users-usually"])){users_usually();exit;}
	
	popup();
function popup(){
	$page=CurrentPageName();
	$hour=$_GET["hour"];
	if(strlen($hour)==1){$hour="0$hour";}		
	$id=md5($_GET["table"].$hour);
	echo "<div id='$t-start-point'></div>
	
	<script>
		LoadAjax('$t-start-point','$page?master=yes&time={$_GET["time"]}&hour={$_GET["hour"]}&table={$_GET["table"]}');
	</script>
	";
}


function js(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$_GET["table"]=date('Ymd',$_GET["time"])."_hour";
	$hour=$_GET["hour"];
	if(strlen($hour)==1){$hour="0$hour";}
	$MasterTitle=$tpl->_ENGINE_parse_body($q->DAY_TITLE_FROM_TABLENAME($_GET["table"]))." {$hour}h00";
	$html="YahooWin2(890,'$page?time={$_GET["time"]}&hour={$_GET["hour"]}&table={$_GET["table"]}','$MasterTitle')";
	echo $html;
	
}


function master_table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$hour=$_GET["hour"];
	if(!is_numeric($_GET["hour"])){echo "<H1> NO Hour set</H1>";die();}
	if(strlen($hour)==1){$hour="0$hour";}	
	
	
	$MasterTitle=$q->DAY_TITLE_FROM_TABLENAME($_GET["table"])." <span style='font-size:18px;color:#CF1717;'>&laquo;{$hour}h00&raquo;</span>";
	$id=md5($_GET["table"].$_GET["hour"]);
	
	$html="
	<div style='font-size:16px;font-weight:bold;width:100%;border-bottom:2px solid #CCCCCC;margin-bottom:8px'>$MasterTitle</div>
	<div id='master-$id'>
	<table style='width:100%'>
	<tbody>
	<tr>
		<td width='33.33%' valign='top'><div id='panel-left-$id'></div></td>
		<td width='33.33%' valign='top'><div id='panel-middle-$id'></div></td>
		<td width='33.33%' valign='top'><div id='panel-right-$id'></div></td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		LoadAjax('panel-left-$id','$page?panel-categories=yes&time={$_GET["time"]}&hour={$_GET["hour"]}&table={$_GET["table"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
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


function panel_categories(){
	$tpl=new templates();
	$page=CurrentPageName();
	$hour=$_GET["hour"];
	if(strlen($hour)==1){$hour="0$hour";}		
	$table=$_GET["table"];
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS($table)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_data}</H2>$sql</center>");
		return;
	}
	
	$separator="<center><hr style='border:1px dotted #CCCCCC;width:80%'></center>";
	
	if(!$q->TABLE_EXISTS($table)){echo "<H3>".$tpl->_ENGINE_parse_body("{ERROR_NO_DATA}$field")."</h3>";}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE `hour`={$_GET["hour"]}"));
	$downloadedINT=$ligne["tsize"];
	$downloaded=FormatBytes($downloadedINT/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE `hour`={$_GET["hour"]} AND cached=1"));
	$cachedINT=$ligne["tsize"];
	$cached=FormatBytes($cachedINT/1024);
	$pourc=$cachedINT/$downloadedINT;
	$pourc=$pourc*100;
	$pourc=round($pourc,2);
		
	$line=$tpl->_ENGINE_parse_body("$field{your_users_has_downloadedXD-XS-XP}$separator");
	$line=str_replace("XD", "<strong>$downloaded</strong>", $line);
	$line=str_replace("XS", "<strong>$cached</strong>", $line);
	$line=str_replace("XP", "<strong style='color:#CF1717'>$pourc%</strong>", $line);
	$html[]="<div style='font-size:12px;text-align:justify;margin-bottom:5px'>$line</div>";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize,familysite,`hour` FROM $table 
	GROUP BY familysite,`hour` HAVING `hour`={$_GET["hour"]} ORDER BY tsize DESC LIMIT 0,1"));
	$XXWWWS=$ligne["familysite"];
	$XWSZE=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize,familysite,`hour` FROM $table 
	GROUP BY familysite,`hour` HAVING `hour`={$_GET["hour"]} ORDER BY tsize DESC LIMIT 0,1"));
	$XXWWWR=$ligne["familysite"];
	$XWSZR=$ligne["tsize"];
	$XWSCAT=familysite_MergeCategories(array($XXWWWS,$XXWWWR));
	
	$linkWebsite1="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.traffic.statistics.week.website.php?table=$table&field=familysite&www=$XXWWWS');\"
	style='font-weight:bold;text-decoration:underline'
	>";
	
	$linkWebsite2="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.traffic.statistics.week.website.php?table=$table&field=familysite&www=$XXWWWR');\"
	style='font-weight:bold;text-decoration:underline'
	>";	
	
	$line=$tpl->_ENGINE_parse_body("{phrase_the_most_websites}");
	$line=str_replace("XXWWWS", "$linkWebsite1$XXWWWS</a>", $line);
	$line=str_replace("XWSZE", "<strong>$XWSZE</strong>", $line);
	$line=str_replace("XXWWWR", "$linkWebsite2$XXWWWR</a>", $line);
	$line=str_replace("XWSZR", "<strong>$XWSZR</strong>", $line);
	$line=str_replace("XWSCAT", "<strong>$XWSCAT</strong>", $line);		
	$html[]="<div style='font-size:12px;text-align:justify'>$line</div>";
	
	$line=$tpl->_ENGINE_parse_body("{phrase_the_most_website_represent}");
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE familysite='$XXWWWS' AND `hour`={$_GET["hour"]}"));
	$XXWWWSPRC_bin=$ligne["tsize"];
	$XXWWWSPRC_size=FormatBytes($XXWWWSPRC_bin/1024);
	$XXWWWSPRC=$XXWWWSPRC_bin/$downloadedINT;
	$XXWWWSPRC=$XXWWWSPRC*100;
	$XXWWWSPRC=round($XXWWWSPRC,2);	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE familysite='$XXWWWS' AND `hour`={$_GET["hour"]} AND cached=1"));
	$XXWWWSCHE_bin=$ligne["tsize"];
	$XXWWWSCHE_size=FormatBytes($XXWWWSCHE_bin/1024);

	

	
	$line=str_replace("XXWWWSPRC", "<strong>$XXWWWSPRC% ($XXWWWSPRC_size)</strong>", $line);
	$line=str_replace("XXWWWSCHE", "<strong>$XXWWWSCHE_size</strong>", $line);	
	$line=str_replace("XXWWWS", "$linkWebsite1$XXWWWS</a>", $line);

	$html[]="<div style='font-size:12px;text-align:justify;margin-top:10px'>
	<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$XXWWWS:</div>
	$line</div>";
	
// ******************************************************************************************************	
	$tabledetails="dansguardian_events_".date("Ymd");
	$sql="SELECT mins,COUNT(ID) as thits,SUM(QuerySize) as tsize 
	FROM (SELECT MINUTE(zDate) as mins,ID,QuerySize FROM $tabledetails WHERE HOUR(zDate)={$_GET["hour"]}) as t
	GROUP BY `mins` ORDER BY `mins` LIMIT 0,1";
	
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo $q->mysql_error;}
	$MostActiveMinNum=$ligne["mins"];
	if(!is_numeric($MostActiveMinNum)){$MostActiveMinNum=0;}
	$MostActiveMinNumText=$MostActiveMinNum;
	if(strlen($MostActiveMinNumText)==1){$MostActiveMinNumText="0$MostActiveMinNumText";}
	$MostActiveDaySize=FormatBytes($ligne["tsize"]/1024);
	$title="{$hour}h$MostActiveMinNumText {phrase_most_time_activehour}";
	$prc=round($ligne["tsize"]/$downloadedINT,2)*100;
	
	$html[]="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$title</div>
	<div style='font-size:12px;text-align:justify;'>{with} <strong>{$ligne["thits"]} {hits}</strong> {or} <strong>$MostActiveDaySize</strong> {it_represents} <strong>$prc%</strong> {of_bandwith}</div>
	
	<div style='font-size:12px;text-align:justify;margin-top:10px;margin-bottom:15px'>{phrase_thisisthegraph2}:</div>"; 
	
	$sql="SELECT mins,COUNT(ID) as thits 
	FROM (SELECT MINUTE(zDate) as mins,ID FROM $tabledetails WHERE HOUR(zDate)={$_GET["hour"]}) as t
	GROUP BY `mins` ORDER BY `mins`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$xdata[]=$ligne["mins"];
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
	$gp->x_title=$tpl->_ENGINE_parse_body("{minutes}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	
	if(!is_file($targetedfile)){$targetedfile="img/nograph-000.png";}
	$html[]="<center style='margin-top:5px'><img src='$targetedfile'></center>";
	
	
	$id=md5($_GET["table"].$_GET["hour"]);
	$html[]="
	<script>
		if(!document.getElementById('panel-middle-$id')){alert('panel-middle-$id, no such id');}
		LoadAjax('panel-middle-$id','$page?panel-topwebistes=yes&time={$_GET["time"]}&hour={$_GET["hour"]}&table={$_GET["table"]}');
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function topwebsites_graph(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
	$table=$_GET["table"];
	$id=md5($_GET["table"].$_GET["hour"]);
	if(!$q->TABLE_EXISTS($table)){echo "<H3>".$tpl->_ENGINE_parse_body("{ERROR_NO_DATA}")."</h3>";}

	$sql="SELECT SUM(size) as tsize,`hour`,familysite FROM $table GROUP BY familysite,`hour` HAVING `hour`='{$_GET["hour"]}' ORDER BY tsize DESC LIMIT 0,5";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$downloaded_bin=$ligne["tsize"];
		$downloaded_text=FormatBytes($downloaded_bin/1024);
		$downloaded_text=str_replace("&nbsp;", " ", $downloaded_text);
		$downloaded_bin=round((($downloaded_bin/1024)/1000));
		
		$website=$ligne["familysite"];
		$ydata[]="MB $website $downloaded_text";
		$xdata[]=$downloaded_bin;	
		
		
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
	echo $tpl->_ENGINE_parse_body("<center style='margin:0px;margin-bottom:5px;padding:3px;border:1px solid #CCCCCC'>
		<strong style='font-size:12px'>{phrase_topwebsize_bysize}</strong>
		<img src='$targetedfile' style='margin-bottom:5px'>
		
	 	</center>
	 	
	")."<script>LoadAjax('panel-right-$id','$page?users-usually=yes&time={$_GET["time"]}&hour={$_GET["hour"]}&table={$_GET["table"]}')</script>";
	
}

function users_usually(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dans=new dansguardian_rules();
	$q=new mysql_squid_builder();
	$separator="<center><hr style='border:1px dotted #CCCCCC;width:80%'></center>";
	
	$table=$_GET["table"];
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$phrase=$tpl->_ENGINE_parse_body("{phrase_your_users_categories}");
	$sql="SELECT SUM(hits) as tsize,`hour`, category FROM $table 
	GROUP BY category,`hour` HAVING category NOT LIKE '%updatesites%' AND `hour`={$_GET["hour"]} ORDER BY tsize DESC LIMIT 0,5";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		$ctz[$ligne["category"]]=$ligne["category"];
	}
	$UXCATZ=@implode(",", $ctz);
	$UXCATZ=str_replace(",,", ",", $UXCATZ);
	$UXCATZ=str_replace(",", ", ", $UXCATZ);
	$UXCATZ="<strong>$UXCATZ</strong>";
	$phrase=str_replace("UXCATZ", $UXCATZ, $phrase);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as thits,`hour`,master_category 
	FROM(SELECT $table.hits,$table.category,$table.`hour`,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category AND `hour`={$_GET["hour"]}) as t
	GROUP BY master_category,`hour` ORDER BY thits DESC LIMIT 0,1"));
	$CATZGRP_KEY=$ligne["master_category"];
	$CATZGRP="<strong>$CATZGRP_KEY</strong>";
	$phrase=str_replace("CATZGRP", $CATZGRP, $phrase);
	$html[]="<div style='font-size:12px;text-align:justify;margin-top:5px'>$phrase</div>";
	
	$phrase=$tpl->_ENGINE_parse_body("{categories_inside_group_XGRPTLE}");
	$phrase=$tpl->_ENGINE_parse_body("$separator<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$phrase</div>");
	$phrase=str_replace("XGRPTLE", "&laquo;$CATZGRP_KEY&raquo;", $phrase);
	$html[]=$phrase;
	
	$sql="SELECT SUM(hits) as thits,`hour`,category FROM(SELECT $table.hits,$table.category,$table.`hour`,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category 
	AND webfilters_categories_caches.master_category='$CATZGRP_KEY' AND `hour`={$_GET["hour"]}) as t
	GROUP BY category ORDER BY thits DESC";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<hr><code>$sql</code><hr><strong style='color:red'>$q->mysql_error</strong>";
	}
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		$rows[]="<tr>
		<td width=1%><img src='img/20-categories-personnal.png'></td>
		<td><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.week.category.php?category={$ligne["category"]}&table=$table');\" 
		style='font-size:12px;text-decoration:underline'>{$ligne["category"]} ({$ligne["thits"]} $hits)</strong></td></tr>";
	}

	$html[]="<table><tbody>".@implode("\n", $rows)."</tbody></table>";
// ******************************************************************************************************	
	$rows=array();
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,client,hostname,uid,`hour`
	FROM $table GROUP BY client,hostname,uid,`hour` HAVING  `hour`={$_GET["hour"]} ORDER BY thits DESC LIMIT 0,5";
	$html[]="$separator<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>{user_most_active} :</div>";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q="uid";
		$q2="hostname";
		if(trim($ligne["hostname"])==null){$ligne["hostname"]=$ligne["client"];$q2="client";}
		if($ligne["uid"]=="-"){$ligne["uid"]=$ligne["hostname"];$q=$q2;}
		
		$usertext=$ligne["uid"];
		$eght=strlen($usertext);
		if($eght>25){$usertext=substr($usertext,0,22)."...";}
		$size=FormatBytes($ligne["tsize"]/1024);
		$rows[]="<tr>
		<td width=1% valign='top'><IMG SRC='img/user-18.png'></TD>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.traffic.statistics.week.user.php?user={$ligne["uid"]}&field=$q&table=$table')\" style='font-weight:bold;font-size:12px;text-decoration:underline'>$usertext</a>
		<i style='font-size:9px;text-align:right'>({$ligne["thits"]} $hits/$size)</i>
		</td></tr>";
		
	}
	$html[]="<table style='width:100%'><tbody>".@implode("\n", $rows)."</tbody></table>";

// ******************************************************************************************************	
	
	
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}