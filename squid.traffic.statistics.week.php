<?php
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}

	
	if(!isset($_GET["day"])){$_GET["day"]=date("Y-m-d");}
	if(!isset($_GET["type"])){$_GET["type"]="size";}
	if($_GET["type"]==null){$_GET["type"]="size";}
	if($_GET["day"]==null){$q=new mysql_squid_builder();$_GET["day"]=date("Y-m-d");}		
	
	if(isset($_GET["week-right-infos"])){right();die();}
	if(isset($_GET["week-left-menus"])){left();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["left-status"])){left_status();exit;}
	if(isset($_GET["members-query"])){member_query_js();exit;}
	if(isset($_GET["members-query-popup"])){member_query();exit;}
	if(isset($_GET["members-query-list"])){member_query_list();exit;}
	
	
page();



function getDaysInWeek ($weekNumber, $year) {

  $time = strtotime($year . '0104 +' . ($weekNumber - 1)
                    . ' weeks');

  $mondayTime = strtotime('-' . (date('w', $time) - 1) . ' days',
                          $time);
 
  $dayTimes = array ();
  for ($i = 0; $i < 7; ++$i) {
    $dayTimes[] = strtotime('+' . $i . ' days', $mondayTime);
  }

  return $dayTimes;
}

function member_query_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{members}::{$_GET["day"]}::{$_GET["t"]}");
	$html="YahooWin2(650,'$page?members-query-popup=yes&day={$_GET["day"]}&t={$_GET["t"]}','$title');";
	echo $html;
}

function left(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	
	
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$mindate=$ligne["tdate"];

	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d') as tdate FROM tables_day ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$maxdate=date('Y-m-d');
	
	$html="
		<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{from_date}:</td>
		<td>". field_date('sWeekDate',$_GET["day"],"font-size:16px;padding:3px;width:95px","mindate:$mindate;maxdate:$maxdate")."</td>	
		<td>". button("{go}","SquidFlowWeekQuery()")."</td>
	</tr>
		
	</tbody>
	</table>
	
	<div id='statistics-week-left-status'></div>
	
<script>
		function SquidFlowWeekQuery(){
			var sdate=document.getElementById('sWeekDate').value;
			LoadAjax('week-right-infos','$page?week-right-infos=yes&day='+sdate);
		}
		
		var sdate=document.getElementById('sWeekDate').value;
		LoadAjax('statistics-week-left-status','$page?left-status=yes&day='+sdate);
		
</script>
";
	echo $tpl->_ENGINE_parse_body($html);	
}


function page(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$html="<table style='width:100%'>
	<tbody>
	<tr>
		<td valign='top' width=1%><div id='week-left-menus'></div></td>
		<td valign='top' width=99%><div id='week-right-infos' style='padding:10px'></div></td>
	</tr>
	</tbody>
	</table>
	
	<script>
		LoadAjax('week-left-menus','$page?week-left-menus=yes');
		LoadAjax('week-right-infos','$page?week-right-infos=yes');
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}

function right(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["day"])){$_GET["day"]=date('Y-m-d');}
	$title_style="font-size:16px;width:100%;font-weight:bold;text-decoration:underline";
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");
	$table=date("YW",$time)."_week";	
	echo "<!-- LINE:".__LINE__.">\n";
	
	$_GET["week"]=date("W",$time);
	$_GET["year"]=date("Y",$time);

	
	$tt=getDaysInWeek($_GET["week"],$_GET["year"]);
	foreach ($tt as $dayTime) {
  		$f[]=date('{l} d {F}', $dayTime);
	}	
	
	if($_GET["field-user"]<>null){
		$title_add="<br>{{$_GET["field-user"]}}:{$_GET["field-user-value"]}";
		$fieldadd="{$_GET["field-user"]},";
		$GROUP_SELECT=" HAVING `{$_GET["field-user"]}`='{$_GET["field-user-value"]}'";
	}
	
	 
	
	echo "<script>
		LoadAjax('statistics-week-left-status','$page?left-status=yes&day={$_GET["day"]}');
	</script>
	";
	echo "<!-- LINE:".__LINE__.">\n";
	$sourcetable="{$_GET["year"]}{$_GET["week"]}_week";
	$title00=$tpl->_ENGINE_parse_body("
	<center style='font-size:16px;width:100%;font-weight:bold;margin:10px'>
			{week}:&nbsp;{from}&nbsp;{$f[0]}&nbsp;{to}&nbsp;{$f[6]}$title_add
	</center>");
	

	
	if(!$q->TABLE_EXISTS($sourcetable)){
		echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'>
		<H2>$sourcetable:{error_no_datas} ($sourcetable no such table)</H2></center>");
		
	}
	echo "<!-- LINE:".__LINE__.">\n";
	$sql="SELECT {$fieldadd}category,SUM(hits) as totalsize FROM $sourcetable GROUP BY {$fieldadd}category {$GROUP_SELECT} ORDER BY totalsize DESC LIMIT 0,10";
	$title0=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{week}:{$_GET["week"]} ({categories} TOP 10)$title_add");
	
	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["totalsize"];
		$ydata[]=$ligne["category"];
	}
	
	echo "<!-- LINE:".__LINE__.">\n";
	$targetedfilePie="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".pie.png";
	$gp=new artica_graphs($targetedfilePie);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_websites}");
	$gp->pie();		
	
	echo "<!-- LINE:".__LINE__.">\n";
	$sql="SELECT {$fieldadd}SUM(hits) as totalsize,`day` FROM $sourcetable GROUP BY {$fieldadd}`day` {$GROUP_SELECT} ORDER BY `day`";
	$title1=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{week}:{$_GET["week"]} ({requests})$title_add");
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body(
	"$title00
	<center style='margin:50px'>
		<H2>{error_no_datas}</H2>
		<i>$sourcetable (no such data in line ". __LINE__.")</i>
	</center>");return;}

	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	
	$xdata=array();
	$ydata=array();	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		$ydata[]=$ligne["totalsize"];
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=350;
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
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);}	
	
	
	$sql="SELECT {$fieldadd}SUM(size) as totalsize,`day` FROM $sourcetable GROUP BY {$fieldadd}`day` {$GROUP_SELECT} ORDER BY `day`";
	$title2=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{week}:{$_GET["week"]} ({downloaded_flow} MB)$title_add");
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){
		echo $tpl->_ENGINE_parse_body("
		$title00
		<center style='margin:50px'>
			<H2>{error_no_datas}</H2><i>$sourcetable no such data (no such data in line ". __LINE__.")</i>
		</center>");
	}
	
	$weeksd=array(
	1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday"
	);
	
	$xdata=array();
	$ydata=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$tpl->_ENGINE_parse_body("{{$weeksd[$ligne["day"]]}}");
		$ydata[]=round(($ligne["totalsize"]/1024)/1000);
		
	}	
	
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=350;
	$gp->filename="$targetedfile2";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(!is_file($targetedfile2)){writelogs("Fatal \"$targetedfile2\" no such file!",__FUNCTION__,__FILE__,__LINE__);}		
	
	$html="
	$title00
	<div class=RoundedGrey>
		$title0
		<img src='$targetedfilePie' style='margin:10px'>
	</div>
	<div class=RoundedGrey>
	$title1<img src='$targetedfile' style='margin:10px'>
	</div>
	<div class=RoundedGrey>
	$title2
	<img src='$targetedfile2' style='margin:10px'>
	</div>
	";

	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}


function left_status(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");
	$table=date("YW",$time)."_week";
	
	if($q->TABLE_EXISTS($table)){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table` WHERE LENGTH(category)=0"));
		$notcategorized=$ligne["tcount"];
		if(!$q->ok){$err1=icon_mysql_error($q->mysql_error);}
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table` WHERE LENGTH(category)>2"));
		$categorized=$ligne["tcount"];	
		if(!$q->ok){$err2=icon_mysql_error($q->mysql_error);}
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(sitename) as tcount FROM `$table`"));
		$visited=$ligne["tcount"];	
		if(!$q->ok){$err3=icon_mysql_error($q->mysql_error);}		
		$notcategorized=texthref($notcategorized, "Loadjs('squid.visited.php?week=$day&onlyNot=yes')");
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(hostname) as tcount FROM 
		(SELECT client,hostname,MAC,uid FROM $table GROUP BY client,hostname,MAC,uid) as t"));
		if(!$q->ok){$err4=icon_mysql_error($q->mysql_error);}	
		$members=texthref($ligne["tcount"], "Loadjs('$page?members-query=yes&day=$day&t=week')");	
		
		$html="
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend>{not_categorized}:</td>
			<td style='font-size:14px;font-weight:bold'>$notcategorized</td>
			<td width=1%>$err1</td>
		</tr>
		<tr>
			<td class=legend>{categorized}:</td>
			<td style='font-size:14px;font-weight:bold'>$categorized</td>
			<td width=1%>$err2</td>
		</tr>	
		<tr>
			<td class=legend>{visited}:</td>
			<td style='font-size:14px;font-weight:bold'>$visited</td>
			<td width=1%>$err3</td>
		</tr>	
		<tr>
			<td class=legend>{members}:</td>
			<td style='font-size:14px;font-weight:bold'>$members</td>
			<td width=1%>$err4</td>
		</tr>				
		</tbody>
		</table>
		";
		
	}
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function member_query(){
	$page=CurrentPageName();
	$tpl=new templates();	
		
	$html="
		<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend nowrap>{members}:</td>
		<td>". Field_text('smemsearch',null,"font-size:16px;padding:3px;width:99%",null,null,null,false,"smemsearchCheck(event)")."</td>	
		<td>". button("{go}","smemsearch()")."</td>
	</tr>
		
	</tbody>
	</table>
	
	<div id='members-search-squis-stats' style='height:350px;overflow:auto'></div>
<script>
		function smemsearch(){
			var se=escape(document.getElementById('smemsearch').value);
			LoadAjax('members-search-squis-stats','$page?members-query-list=yes&search='+se+'&day={$_GET["day"]}&t={$_GET["t"]}');
		}
		
		function smemsearchCheck(e){
			if(checkEnter(e)){smemsearch();}
		}
		smemsearch();
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function member_query_list(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");
	$table=date("YW",$time)."_week";	
	$sql="SELECT SUM(hits) as thits,client,hostname,MAC,uid FROM $table GROUP BY client,hostname,MAC,uid ORDER BY thits DESC,uid,hostname,client LIMIT 0,35";
	if($_GET["search"]<>null){
		$search=$_GET["search"];
		$search="*$search*";
		$search=str_replace("**", "*", $search);
		$search=str_replace("**", "*", $search);
		$search=str_replace("*", "%", $search);
		$sql0="(SELECT hits,client,hostname,MAC,uid FROM $table WHERE client LIKE '$search' OR hostname LIKE '$search' OR uid LIKE '$search') as t";
		$sql="SELECT SUM(hits) as thits,client,hostname,MAC,uid FROM $sql0 GROUP BY client,hostname,MAC,uid ORDER BY thits DESC,uid,hostname,client LIMIT 0,35";
	}
	
	$results=$q->QUERY_SQL($sql);
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=50% colspan=2 nowrap>{hostname}</th>
		<th width=50% nowrap>{member}</th>
		<th width=10% nowrap>{hits}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$select_client="<a href=\"javascript:blur();\" OnClick=\"javascript:QueryWeekByField('client','{$ligne["client"]}');\" style='font-size:11px;text-decoration:underline'>";
		$select_hostname="<a href=\"javascript:blur();\" OnClick=\"javascript:QueryWeekByField('hostname','{$ligne["hostname"]}');\" style='font-size:14px;text-decoration:underline'>";
		$select_uid="<a href=\"javascript:blur();\" OnClick=\"javascript:QueryWeekByField('uid','{$ligne["uid"]}');\" style='font-size:14px;text-decoration:underline'>";
		$html=$html."
		<tr class=$classtr>
		<td width=1%><img src='img/view_members-32.png'></td>
		<td width=50% style='font-size:14px'>$select_hostname{$ligne["hostname"]}</a> <div style='font-size:11px'>$select_client{$ligne["client"]}</a>&nbsp;|&nbsp;{$ligne["MAC"]}</div></td>
		<td width=50% style='font-size:14px' nowrap>$select_uid{$ligne["uid"]}</a></td>
		<td style='font-size:14px' nowrap width=1%>{$ligne["thits"]}</td>
		</tr>	
		
		";
	}	
	
	$html=$html."</tbody></table>
	
	<script>
		function QueryWeekByField(field,value){
			value=escape(value);
			LoadAjax('week-right-infos','$page?week-right-infos=yes&day={$_GET["day"]}&t={$_GET["t"]}&field-user='+field+'&field-user-value='+value);
		
		}
	
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
} 
