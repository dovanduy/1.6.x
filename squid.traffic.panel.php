<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){echo "<H2>No rights</H2>";die();}

	if(isset($_GET["master"])){master_table();exit;}
	if(isset($_GET["panel-categories-week"])){week_categories();exit;}
	if(isset($_GET["panel-topwebistes-week"])){week_topwebsites_graph();exit;}
	if(isset($_GET["users-usually"])){users_usually();exit;}
	
	popup();
function popup(){
	$page=CurrentPageName();
	echo "<div id='panel-start-point'></div>
	
	<script>
		LoadAjax('panel-start-point','$page?master=yes');
	</script>
	";
}


function master_table(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$page=CurrentPageName();
	$year=date("Y");$week=intval(date('W'));
	if($_GET["table"]==null){$table="$year{$week}_week";}else{$table=$_GET["table"];}
	
	$array=array();
	$tables=$q->LIST_TABLES_WEEKS();
	while (list ($index, $tablez) = each ($tables) ){
		$array[$tablez]=$q->WEEK_TITLE_FROM_TABLENAME($tablez);
	}
	

	$MasterTitle=$q->WEEK_TITLE_FROM_TABLENAME($table);
	
	$field=Field_array_Hash($array,"table-query-$t",$table,"ChangeWeekPanel$t()",null,0,"font-size:12px");
	$array=array();
	
	$field="<table style='width:100%'>
	<tbody>
	<tr><td width=100%' style='font-size:16px;font-weight:bold'>$MasterTitle</td>
	<td width=1%>
	<table><tbody><tr><td class=legend>{week}:</td>$field</td></tr></table>
	</td>
	</tr>
	</tbody>
	</table>";	
	
	$html="
	<div id='master-$t'>
	$field<table style='width:100%'>
	<tbody>
	<tr>
		<td width='33.33%' valign='top'><div id='panel-left-top'></div></td>
		<td width='33.33%' valign='top'><div id='panel-middle-top'></div></td>
		<td width='33.33%' valign='top'><div id='panel-right-top'></div></td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
		LoadAjax('panel-left-top','$page?panel-categories-week=yes&table={$_GET["table"]}');
		
		function ChangeWeekPanel$t(){
			$('master-$t').remove();	
			var sdate=document.getElementById('table-query-$t').value;
			LoadAjax('panel-start-point','$page?master=yes&table='+sdate);
		}
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function semaine_title(){
	if($_GET["table"]==null){$table=date("YW")."_week";}else{$table=$_GET["table"];}
	
	
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


function week_categories(){
	$tpl=new templates();
	$page=CurrentPageName();
	
	$year=date("Y");$week=intval(date('W'));
	if($_GET["table"]==null){$table="$year{$week}_week";}else{$table=$_GET["table"];}	
	
	
	$q=new mysql_squid_builder();
	if($q->COUNT_ROWS($table)==0){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql ($table {empty})</center>");
		return;
	}
	
	$separator="<center><hr style='border:1px dotted #CCCCCC;width:80%'></center>";
	
	if(!$q->TABLE_EXISTS($table)){echo "<H3>".$tpl->_ENGINE_parse_body("{week_table_was_not_builded}$field")."</h3>";}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table"));
	$downloadedINT=$ligne["tsize"];
	$downloaded=FormatBytes($downloadedINT/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE cached=1"));
	$cachedINT=$ligne["tsize"];
	$cached=FormatBytes($cachedINT/1024);
	$pourc=$cachedINT/$downloadedINT;
	$pourc=$pourc*100;
	$pourc=round($pourc,2);
		
	$line=$tpl->_ENGINE_parse_body("$field{this_week_your_users_has_downloadedXD-XS-XP}$separator");
	$line=str_replace("XD", "<strong>$downloaded</strong>", $line);
	$line=str_replace("XS", "<strong>$cached</strong>", $line);
	$line=str_replace("XP", "<strong style='color:#CF1717'>$pourc%</strong>", $line);
	$html[]="<div style='font-size:12px;text-align:justify;margin-bottom:5px'>$line</div>";
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize,familysite FROM $table GROUP BY familysite ORDER BY tsize DESC LIMIT 0,1"));
	$XXWWWS=$ligne["familysite"];
	$XWSZE=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize,familysite FROM $table GROUP BY familysite ORDER BY tsize DESC LIMIT 0,1"));
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
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE familysite='$XXWWWS'"));
	$XXWWWSPRC_bin=$ligne["tsize"];
	$XXWWWSPRC_size=FormatBytes($XXWWWSPRC_bin/1024);
	$XXWWWSPRC=$XXWWWSPRC_bin/$downloadedINT;
	$XXWWWSPRC=$XXWWWSPRC*100;
	$XXWWWSPRC=round($XXWWWSPRC,2);	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE familysite='$XXWWWS' AND cached=1"));
	$XXWWWSCHE_bin=$ligne["tsize"];
	$XXWWWSCHE_size=FormatBytes($XXWWWSCHE_bin/1024);

	

	
	$line=str_replace("XXWWWSPRC", "<strong>$XXWWWSPRC% ($XXWWWSPRC_size)</strong>", $line);
	$line=str_replace("XXWWWSCHE", "<strong>$XXWWWSCHE_size</strong>", $line);	
	$line=str_replace("XXWWWS", "$linkWebsite1$XXWWWS</a>", $line);

	$html[]="<div style='font-size:12px;text-align:justify;margin-top:10px'>
	<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$XXWWWS:</div>
	$line</div>";
	
// ******************************************************************************************************	

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `day`,SUM(hits) as thits,SUM(size) as tsize  FROM $table GROUP BY `day` ORDER BY thits DESC LIMIT 0,1"));
	if(!$q->ok){echo $q->mysql_error;}
	$MostActiveDayNum=$ligne["day"]-1;
	$MostActiveDaySize=FormatBytes($ligne["tsize"]/1024);
	$Cyear=substr($table, 0,4);
	$Cweek=substr($table,4,2);
	$Cweek=str_replace("_", "", $Cweek);	
	$days=getDaysInWeek($Cweek,$Cyear);
	$dayText=date('{l}', $days[$MostActiveDayNum]);
	$TimeWeek=strtotime($Cyear . '0104 +' . ($Cweek - 1). ' weeks');
	$DayTime=date('Y-m',$TimeWeek)."-".date('d',$days[$MostActiveDayNum]);
	$DayTable=date('Ymd',strtotime($DayTime))."_hour";
	$dayText="<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.traffic.panel.day.php?js=yes&table=$DayTable');\"
	style='color:#CF1717;font-weight:bold;text-decoration:underline'>$dayText</a>";
	$title="$dayText {phrase_most_day_activeday}";
	$prc=round($ligne["tsize"]/$downloadedINT,2)*100;
	
	$html[]="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$title</div>
	<div style='font-size:12px;text-align:justify;'>
	{with} <strong>{$ligne["thits"]} {hits}</strong> {or} <strong>$MostActiveDaySize</strong> {it_represents} <strong>$prc%</strong> {of_bandwith}</div>
	
	<div style='font-size:12px;text-align:justify;margin-top:10px;margin-bottom:15px'>{phrase_thisisthegraph1}:</div>"; 
	
	$sql="SELECT `day`,SUM(hits) as thits FROM $table GROUP BY `day` ORDER BY `day`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$day=$ligne["day"]-1;	
		$dayText=date('{l} d', $days[$day]);
		$DayTable=date('Ymd',$days[$day])."_hour";	
		$TimeWeek=strtotime($Cyear . '0104 +' . ($Cweek - 1). ' weeks');
		$DayTime=date('Y-m',$TimeWeek)."-".date('d',$days[$day]);
		
		$dayText="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.traffic.panel.day.php?js=yes&table=$DayTable');\" style='font-weight:bold;text-decoration:underline'>$dayText</a>";		
		$tr[]="
		<tr>
			<td class=legend nowrap>$dayText:</td>
			<td nowrap><strong style='font-size:13px'>". numberFormat($ligne["thits"],0,""," ")." {hits}</td>
		</tr>
		";
			
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
	$html[]="<center style='margin-top:5px'><img src='$targetedfile'></center>
	<table style='width:50%'><tbody>".@implode("\n", $tr)."</tbody></table>
	";
	
	$html[]="
	<script>
		LoadAjax('panel-middle-top','$page?panel-topwebistes-week=yes&table=$table');
	</script>";
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
	
}

function week_topwebsites_graph(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	
	
if($_GET["table"]==null){$table=date("YW")."_week";}else{$table=$_GET["table"];}
	if(!$q->TABLE_EXISTS($table)){echo "<H3>".$tpl->_ENGINE_parse_body("{week_table_was_not_builded}")."</h3>";}

	
	$results=$q->QUERY_SQL("SELECT SUM(size) as tsize,familysite FROM $table GROUP BY familysite ORDER BY tsize DESC LIMIT 0,5");
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
	 	
	")."<script>LoadAjax('panel-right-top','$page?users-usually=yes&table=$table')</script>";
	
}

function users_usually(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dans=new dansguardian_rules();
	$q=new mysql_squid_builder();
	$separator="<center><hr style='border:1px dotted #CCCCCC;width:80%'></center>";
	if($_GET["table"]==null){$table=date("YW")."_week";}else{$table=$_GET["table"];}
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$phrase=$tpl->_ENGINE_parse_body("{phrase_your_users_categories}");
	$sql="SELECT SUM(hits) as tsize, category FROM $table GROUP BY category HAVING category NOT LIKE '%updatesites%' ORDER BY tsize DESC LIMIT 0,5";
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
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as thits,master_category FROM(SELECT $table.hits,$table.category,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category) as t
	GROUP BY master_category ORDER BY thits DESC LIMIT 0,1"));
	$CATZGRP_KEY=$ligne["master_category"];
	$CATZGRP="<strong>$CATZGRP_KEY</strong>";
	$phrase=str_replace("CATZGRP", $CATZGRP, $phrase);
	$html[]="<div style='font-size:12px;text-align:justify;margin-top:5px'>$phrase</div>";
	
	$phrase=$tpl->_ENGINE_parse_body("{categories_inside_group_XGRPTLE}");
	$phrase=$tpl->_ENGINE_parse_body("$separator<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$phrase</div>");
	$phrase=str_replace("XGRPTLE", "&laquo;$CATZGRP_KEY&raquo;", $phrase);
	$html[]=$phrase;
	
	$sql="SELECT SUM(hits) as thits,category FROM(SELECT $table.hits,$table.category,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category AND webfilters_categories_caches.master_category='$CATZGRP_KEY') as t
	GROUP BY category ORDER BY thits DESC";
	
	$results=$q->QUERY_SQL($sql);
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
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,client,MAC,hostname,uid FROM $table GROUP BY client,MAC,hostname,uid ORDER BY thits DESC LIMIT 0,5";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$html[]="$separator<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>{user_most_active} :</div>";
	$results=$q->QUERY_SQL($sql);
	$qa=$q;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q="uid";
		$ligne["uid"]=trim($ligne["uid"]);
		if($ligne["uid"]=="-"){$ligne["uid"]=null;}
		$q2="hostname";
		$q3="MAC";
		$uidText=$ligne["uid"];
		
		
		if(trim($ligne["hostname"])==null){$ligne["hostname"]=$ligne["client"];$q2="client";}
		if($ligne["MAC"]<>null){
			if($ligne["uid"]==null){
				$ligne["uid"]=$ligne["MAC"];
				if($uidText==null){$uidText=$qa->UID_FROM_MAC($ligne["MAC"]);}
				$q="MAC";
			}
		}
		
		if($uidText==null){$uidText=$ligne["hostname"];$q=$q2;}
		
		
		$eght=strlen($usertext);
		if($eght>25){$usertext=substr($usertext,0,22)."...";}
		$size=FormatBytes($ligne["tsize"]/1024);
		
		$jsbymac="Loadjs('squid.traffic.statistics.week.user.php?user={$ligne["uid"]}&field=MAC&table=$table')";
		
		
		$rows[]="<tr>
		<td width=1% valign='top'><IMG SRC='img/user-18.png'></TD>
		<td><a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.statistics.week.user.php?user={$ligne["uid"]}&field=$q&table=$table')\" 
		style='font-weight:bold;font-size:12px;text-decoration:underline'>$uidText</a>
		<i style='font-size:9px;text-align:right'>({$ligne["thits"]} $hits/$size)</i>
		</td></tr>";
		
	}
	$html[]="<table style='width:100%'><tbody>".@implode("\n", $rows)."</tbody></table>";

// ******************************************************************************************************	
	
	
	
	
	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}

function getDaysInWeek ($weekNumber, $year) {

  $time = strtotime($year . '0104 +' . ($weekNumber - 1). ' weeks');

  $mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days',$time);
 
  $dayTimes = array ();
  for ($i = 0; $i < 7; ++$i) {
    $dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
  }

  return $dayTimes;
}

