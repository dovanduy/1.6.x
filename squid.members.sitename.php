<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}	
	
	//
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["history"])){history_content();exit;}
	if(isset($_GET["days"])){days_popup();exit;}
	if(isset($_GET["zoom-day"])){zoom_day();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];
	$title="{member}::$field - $value - {$_GET["familysite"]}";
	$title=$tpl->_ENGINE_parse_body($title);
	if(isset($_GET["table"])){
		$q=new mysql_squid_builder();
		$tablejs="&table={$_GET["table"]}";
		if(preg_match("#_week#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
				
		}
		if(preg_match("#_day#", $_GET["table"])){
			$title_add="&raquo;".$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
		}		
	
	}
	$html="YahooWin2('750','$page?tabs=yes&field=$field&value=$value$tablejs&familysite={$_GET["familysite"]}','$title$title_add')";
	echo $html;
}
function tabs(){
$page=CurrentPageName();
	$tpl=new templates();
	$array["history"]='{history}';
	$array["days"]='{days}';
	
	
	$field=$_GET["field"];
	$value=$_GET["value"];	
	if(isset($_GET["table"])){$tablejs="&table={$_GET["table"]}";}
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= "<li><a href=\"$page?$num=yes&field=$field&value=$value$tablejs&familysite={$_GET["familysite"]}\"><span>$ligne</span></a></li>\n";
	}
	
	
	echo $tpl->_ENGINE_parse_body( "
	<div id=squid_members_stats_zoom-family style='width:100%;font-size:14px'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#squid_members_stats_zoom-family').tabs();
			
			
			});
		</script>");		
}
function history_content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();		
	$xdata=array();
	$ydata=array();	
	$field=$_GET["field"];
	$value=$_GET["value"];
	$familysite=$_GET["familysite"];
	
	
		
	if(isset($_GET["table"])){
		if($field=="ipaddr"){$field="client";}
		$sql="SELECT day as tday,SUM(size) as QuerySize,SUM(hits) as hits FROM 
		`{$_GET["table"]}`  WHERE `$field`='$value' AND familysite='$familysite' GROUP BY tday ORDER BY tday";
		
		if(preg_match("#_week#", $_GET["table"])){
			$title_add=$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
			$time=$q->WEEK_TIME_FROM_TABLENAME($_GET["table"]);
			$month=date("m",$time);
			$year=date("Y",$time);
			
		}
		if(preg_match("#_day#", $_GET["table"])){
			$title_add=$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
			$year=substr($_GET["table"], 0,4);
			$month=substr($_GET["table"],4,2);						
		}		
		
	}
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
	}
	
	if(mysql_num_rows($results)<2){
		
		if(mysql_num_rows($results)==1){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=FormatBytes($ligne["QuerySize"]/1024);
			$day=$ligne["tday"];
			$timstr=strtotime("$year-$month-$day 00:00:00");
			$html=$html."<div style='width:99%' style='font-size:16px;' class=form>
				$field:$value&nbsp;&raquo; {size}:$size {$ligne["hits"]} {hits} ". date('{l} d {F}', $timstr)."
			
			</div>";
			}
				echo $tpl->_ENGINE_parse_body($html);
				return;
			
		}
		
		echo FATAL_ERROR_SHOW_128("{this_request_contains_no_data}");
		return;
		
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["tday"];
		$xdata[]=$day;
		$xdata2[]=$day;
		$ydata[]=$size;
		$ydata2[]=$ligne["hits"];
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".2.png";
	$gp=new artica_graphs();
	
	$gp->width=650;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=$tpl->_ENGINE_parse_body("{size}");;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	$gp2=new artica_graphs();
	$gp2->width=650;
	$gp2->height=350;
	$gp2->filename="$targetedfile2";
	$gp2->xdata=$xdata2;
	$gp2->ydata=$ydata2;
	$gp2->y_title=$tpl->_ENGINE_parse_body("{hits}");;
	$gp2->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp2->title=null;
	$gp2->margin0=true;
	$gp2->Fillcolor="blue@0.9";
	$gp2->color="146497";	
	$gp2->line_green();
	
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<center>
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{downloaded_size_per_day} (MB)</div>
				<img src='$targetedfile'>
			</div>
			
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{requests_per_day}</div>
				<img src='$targetedfile2'>
			</div>			
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);
}

function days_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$t=time();
	$table=$_GET["table"];
	$field=$_GET["field"];
	$value=$_GET["value"];
	$familysite=$_GET["familysite"];	
	if(preg_match("#_week#", $_GET["table"])){
		if($field=="ipaddr"){$field="client";}	
		$dayZ=$q->WEEK_HASHTIME_FROM_TABLENAME($table);
		$title_add=$tpl->_ENGINE_parse_body($q->WEEK_TITLE_FROM_TABLENAME($_GET["table"]));
	}

	if(preg_match("#_day#", $_GET["table"])){
		if($field=="ipaddr"){$field="client";}
		$title_add=$tpl->_ENGINE_parse_body($q->MONTH_TITLE_FROM_TABLENAME($_GET["table"]));
		$sql="SELECT `day` FROM {$_GET["table"]} WHERE $field='$value' AND familysite='$familysite' GROUP BY `day` ORDER BY `day` ";
		$results=$q->QUERY_SQL($sql);
		$Cyear=substr($_GET["table"], 0,4);
		$month=substr($_GET["table"],4,2);		
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$time=strtotime("$Cyear-$month-{$ligne["day"]} 00:00:00");
			$dayZ[$time]=date('{l} d {F}', $time);
		}
		
	}		
		
	
	$fieldz=Field_array_Hash($dayZ, "daytime-$t",null,"ChangeInterval$t()",null,0,"font-size:16px");
$t=time();	
$html="
<table style='width:99%' class=form>
<tr>
	<td class=legend style='font-size:16px'>{day}:</td>
	<td style='font-size:16px'>$fieldz</td>
	
</tr>
</table>
<div id='$t-content'></div>


<script>
	function ChangeIntervalCheck$t(e){
		if(checkEnter(e)){ChangeInterval$t();}
	}

function ChangeInterval$t(){
	var table='{$_GET["table"]}';
	if(table.length==0){document.getElementById('daytime-$t').disabled=true;}
	var days=document.getElementById('daytime-$t').value;
	LoadAjax('$t-content','$page?zoom-day=yes&field=$field&value=$value&familysite={$_GET["familysite"]}&daytime='+days);
	}
	ChangeInterval$t();
</script>
"	;
	
echo $tpl->_ENGINE_parse_body($html);
}


function zoom_day(){
	$page=CurrentPageName();
	$tpl=new templates();
	$field=$_GET["field"];
	$value=$_GET["value"];	
	$familysite=$_GET["familysite"];
	$daytime=$_GET["daytime"];
	$table_name=date("Ymd",$daytime)."_hour";
	$daytitle=date("{l} d {F}",$daytime);
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS($table_name)){
		echo FATAL_ERROR_SHOW_128("{sorry_table_is_missing}:$table_name");
		return;
	}
	
	if($field=="ipaddr"){$field="client";}
	$sql="SELECT `hour` as thour,SUM(size) as QuerySize,SUM(hits) as hits FROM 
	`$table_name`  WHERE `$field`='$value' AND familysite='$familysite' GROUP BY thour ORDER BY thour";
		
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "<H3>Warning<hr>$sql<hr>$q->mysql_error</H3>";
	}
	
	if(mysql_num_rows($results)==0){
		$reqests="{search} {requests} {from} $value {to} $familysite {day} $daytitle";
		echo FATAL_ERROR_SHOW_128("{this_request_contains_no_data}<hr>$reqests");
		return;
		
	}
	
	if(mysql_num_rows($results)<2){
		
		if(mysql_num_rows($results)==1){
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$size=FormatBytes($ligne["QuerySize"]/1024);
			$day=$ligne["thour"];
			$timstr=strtotime(date("Y-m-d",$daytime)." $day:00:00");
			$html=$html."<div style='width:99%' style='font-size:16px;' class=form>
				$field:$value&nbsp;&raquo; {size}:$size, {$ligne["hits"]} {hits} ". date('{l} d {F} H:00', $timstr)."
			
			</div>";
			}
				echo $tpl->_ENGINE_parse_body($html);
				return;
			
		}
	}	
	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=round(($ligne["QuerySize"]/1024)/1000);
		$day=$ligne["thour"];
		$xdata[]=$day;
		$xdata2[]=$day;
		$ydata[]=$size;
		$ydata2[]=$ligne["hits"];
		
	}	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
	$targetedfile2="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".2.png";
	$gp=new artica_graphs();
	
	$gp->width=650;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=$tpl->_ENGINE_parse_body("{size}");;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	$gp2=new artica_graphs();
	$gp2->width=650;
	$gp2->height=350;
	$gp2->filename="$targetedfile2";
	$gp2->xdata=$xdata2;
	$gp2->ydata=$ydata2;
	$gp2->y_title=$tpl->_ENGINE_parse_body("{hits}");;
	$gp2->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp2->title=null;
	$gp2->margin0=true;
	$gp2->Fillcolor="blue@0.9";
	$gp2->color="146497";	
	$gp2->line_green();
	
	if(!is_file($targetedfile)){
		writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);
	
	}else{
		$html=$html."
		<center>
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{downloaded_size_per_hour} (MB)</div>
				<img src='$targetedfile'>
			</div>
			
			<div style='width:99%' class=form>
				<div style='font-size:18px;margin:8px'>&laquo;$value&raquo;$familysite&nbsp;{requests_per_hour}</div>
				<img src='$targetedfile2'>
			</div>			
		</center>
		
		";
		
	}	
		
	echo $tpl->_ENGINE_parse_body($html);	
}