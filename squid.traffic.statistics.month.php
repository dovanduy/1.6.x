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
	
	if(isset($_GET["month-right-infos"])){right();die();}
	if(isset($_GET["month-left-menus"])){left();die();}
	if(isset($_GET["today-zoom"])){today_zoom_js();exit;}
	if(isset($_GET["today-zoom-popup"])){today_zoom_popup();exit;}
	if(isset($_GET["left-status"])){left_status();exit;}
	if(isset($_GET["members-query"])){member_query_js();exit;}
	if(isset($_GET["members-query-popup"])){member_query();exit;}
	if(isset($_GET["members-query-list"])){member_query_list();exit;}
	
	
page();





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
	
	
	
	$sql="SELECT MONTHNAME(zDate) as monthN,MONTH(zDate) as tmonth,YEAR(zDate) as tYear FROM 
		tables_day WHERE YEAR(zDate)=YEAR(NOW()) GROUP BY  MONTHNAME(zDate),MONTH(zDate),YEAR(zDate) ORDER BY tmonth DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	$html="
	<table style='width:210px' class=form><tbody>
	<tr>
		<td colspan=2 style='font-weight:bolder;text-align:center;font-size:14px'>".date('Y')."</td>
	</tr>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(strlen($ligne["tmonth"])==1){$ligne["tmonth"]="0".$ligne["tmonth"];}
		$dayVirt="{$ligne["tYear"]}-{$ligne["tmonth"]}-01";
		
		$js[]="document.getElementById('status-infos-{$ligne["tmonth"]}').innerHTML='';";
		
		
		$html=$html."
		<tr>
			<td width=1%><img src='img/month-32.png'></td>
			<td style='font-size:14px'>
				<a href=\"javascript:blur()\" OnClick=\"javascript:ChooseMonth('$dayVirt');\" style='font-size:14px;text-decoration:underline'>{{$ligne["monthN"]}}</a>
			</td>
		</tr>
		<tr>
			<td colspan=2><span id='status-infos-{$ligne["tmonth"]}'></span></td>
		</tr>
		
		";
		
		
	}
	
	
	
	$html=$html."	
	</tbody>
	</table>
	
	<div id='statistics-month-left-status'></div>
	
<script>
		function ChooseMonth(sday){
			". @implode("\n", $js)."
			LoadAjax('month-right-infos','$page?month-right-infos=yes&day='+sday);
		}

		
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
		<td valign='top' width=1%><div id='month-left-menus'></div></td>
		<td valign='top' width=99%><div id='month-right-infos' style='padding:10px'></div></td>
	</tr>
	</tbody>
	</table>
	
	<script>
		LoadAjax('month-left-menus','$page?month-left-menus=yes');
		LoadAjax('month-right-infos','$page?month-right-infos=yes');
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
	$sourcetable=date("Ym",$time)."_day";	
	$_GET["month"]=date("m",$time);
	$_GET["week"]=date("W",$time);
	$_GET["year"]=date("Y",$time);
	$monthName=date('F',$time);
	
	if($_GET["field-user"]<>null){
		$title_add="<br>{{$_GET["field-user"]}}:{$_GET["field-user-value"]}";
		$fieldadd="{$_GET["field-user"]},";
		$GROUP_SELECT=" HAVING `{$_GET["field-user"]}`='{$_GET["field-user-value"]}'";
	}
	
	 
	
	echo "<script>
		LoadAjax('status-infos-{$_GET["month"]}','$page?left-status=yes&day={$_GET["day"]}');
	</script>
	";
	
	
	$title00="<center style='font-size:16px;width:100%;font-weight:bold;margin:10px'>{month}:&nbsp;{from}&nbsp;{{$monthName}} - {$_GET["year"]}$title_add</center>";
	

	
	if(!$q->TABLE_EXISTS($sourcetable)){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>$sourcetable:{error_no_datas}</H2></center>");return;}
	
	$sql="SELECT {$fieldadd}category,SUM(hits) as totalsize FROM $sourcetable GROUP BY {$fieldadd}category {$GROUP_SELECT} ORDER BY totalsize DESC LIMIT 0,10";
	$title0=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{month}:{{$monthName}} ({categories} TOP 10)$title_add");
	
	
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["totalsize"];
		$ydata[]=$ligne["category"];
	}
	
	$targetedfilePie="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".month.pie.png";
	$gp=new artica_graphs($targetedfilePie);	
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;	
	$gp->width=550;
	$gp->height=550;
	$gp->ViewValues=true;
	$gp->x_title=$tpl->_ENGINE_parse_body("{top_websites}");
	$gp->pie();		
	
	
	$sql="SELECT {$fieldadd}SUM(hits) as totalsize,`day` FROM $sourcetable GROUP BY {$fieldadd}`day` {$GROUP_SELECT} ORDER BY `day`";
	$title1=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{month}:{{$monthName}} ({requests})$title_add");
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title00<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}

	
	
	$xdata=array();
	$ydata=array();	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["day"];
		$ydata[]=$ligne["totalsize"];
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".month.png";
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
	$title2=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;{month}:{{$monthName}} ({downloaded_flow} MB)$title_add");
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title00<center style='margin:50px'><H2>{error_no_datas}</H2></center>");return;}
	
	
	$xdata=array();
	$ydata=array();
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["day"];
		$ydata[]=round(($ligne["totalsize"]/1024)/1000);
		
	}	
	
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".month.png";
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
	$table=date("Ym",$time)."_day";
	
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
		$notcategorized=texthref($notcategorized, "Loadjs('squid.visited.php?month=$day&onlyNot=yes')");
		
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(hostname) as tcount FROM 
		(SELECT client,hostname,MAC,uid FROM $table GROUP BY client,hostname,MAC,uid) as t"));
		if(!$q->ok){$err4=icon_mysql_error($q->mysql_error);}	
		$members=texthref($ligne["tcount"], "Loadjs('$page?members-query=yes&day=$day&t=month')");	
		
		$html="
		<table style='width:99%' class=form>
		<tbody>
		<tr>
			<td class=legend nowrap>{not_categorized}:</td>
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
