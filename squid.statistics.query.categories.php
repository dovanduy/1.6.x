<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["query-perform"])){query_perform();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{category}:{$_GET["category"]}:{$_GET["TimeType"]} ({$_GET["day"]}):{$_GET["field-search"]} {$_GET["data-search"]}");
	$html="YahooWin4(1000,'$page?query-perform=yes&field-search={$_GET["field-search"]}&data-search={$_GET["data-search"]}&TimeType={$_GET["TimeType"]}&day={$_GET["day"]}&category={$_GET["category"]}','$title');";
	echo $html;
	
}

function query_perform(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$day=$_GET["day"];
	$time=strtotime("$day 00:00:00");
	$tableWeek=date("YW",$time)."_week";	
	$tableDay=date("Ymd",$time)."_day";
	$category=$_GET["category"];
	$DAYNUM=false;
	$weeksd=array(1 => "Sunday", 2 => "Monday",3=>"Tuesday",4=>"Wednesday",5=>"Thursday",6=>"Friday",7=>"Saturday");
	
	if($_GET["field-search"]<>null){
		$title_add="{$_GET["field-search"]}:{$_GET["data-search"]}";
		$FIELDADD="`{$_GET["field-search"]}`,";
		$HAVING=" AND `{$_GET["field-search"]}`='{$_GET["data-search"]}'";
	}
	
	switch ($_GET["TimeType"]) {
		case "week":$table=$tableWeek;$FIELD_TIME="day";$DAYNUM=true; break;
		default:$table=$tableDay;$FIELD_TIME="hour";
		break;
	}
	
	
	$sql="SELECT {$FIELDADD}SUM(hits) as thits,category,$FIELD_TIME FROM $table GROUP BY {$FIELDADD}category,$FIELD_TIME HAVING `category`='$category'$HAVING   ORDER BY $FIELD_TIME";
	$results=array();
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><hr><code style='font-size:12px'>$sql</code><hr>";}
	$title=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;$category&nbsp;{{$_GET["TimeType"]}}:{$_GET["day"]} ({requests})&nbsp;$title_add");
	if(mysql_num_rows($results)>1){
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$x=$ligne[$FIELD_TIME];
			if($DAYNUM){$x=$weeksd[$ligne[$FIELD_TIME]];}
			$xdata[]=$x;
			$ydata[]=$ligne["thits"];
		}
		$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
		$gp=new artica_graphs();
		$gp->width=400;
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
		
			
		$html[]="<div class=RoundedGrey>$title<img src='$targetedfile' style='margin:10px'></div>";
	}else{
		
		$ligne=array();
		$ligne=mysql_fetch_array($results);
		$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'><tr>";
		while (list ($a, $b) = each ($ligne) ){if(is_numeric($a)){continue;}if($a=="category"){continue;}$heads[]="<th>{{$a}}</th>";$datas[]="<td style='font-size:14px'>$b</td>";}
		$html[]="<div class=RoundedGrey>$title$htmlHeader". @implode("\n", $heads)."</tr><tr>". @implode("\n", $datas)."</tr></tbody></table></div>";
		
		
	}
		
	
// ------------------------------------------------------------------------------------------------------------------------------------------------
	@mysql_free_result($results);
	$sql="SELECT {$FIELDADD}SUM(size) as tsize,category,$FIELD_TIME FROM $table GROUP BY {$FIELDADD}category,$FIELD_TIME HAVING `category`='$category'$HAVING   ORDER BY $FIELD_TIME";
	$results=array();
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><hr><code style='font-size:12px'>$sql</code><hr>";}
	unset($xdata);unset($ydata);
	$title=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;$category&nbsp;{{$_GET["TimeType"]}}:{$_GET["day"]} ({downloaded_flow} MB)&nbsp;$title_add");
if(mysql_num_rows($results)>1){	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
			$x=$ligne[$FIELD_TIME];
			if($DAYNUM){$x=$weeksd[$ligne[$FIELD_TIME]];}		
			$xdata[]=$x;
			$ydata[]=round(($ligne["tsize"]/1024)/1000);
		}
		$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
		$gp=new artica_graphs();
		$gp->width=400;
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
		
			
		$html[]="<div class=RoundedGrey>$title<img src='$targetedfile' style='margin:10px'></div>";	
	}else{
		$ligne=array();
		$ligne=mysql_fetch_array($results);
		$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'><tr>";
		while (list ($a, $b) = each ($ligne) ){
			if(isset($already[$a])){continue;}
			if($a=="category"){continue;}
			if(is_numeric($a)){continue;}
			$heads[]="<th>{{$a}}</th>";
			$datas[]="<td style='font-size:14px'>$b</td>";
		}
		$html[]="<div class=RoundedGrey>$title$htmlHeader". @implode("\n", $heads)."</tr><tr>". @implode("\n", $datas)."</tr></tbody></table></div>";
		
		
	}
	
// ------------------------------------------------------------------------------------------------------------------------------------------------	
	@mysql_free_result($results);unset($xdata);unset($ydata);
	$sql="SELECT {$FIELDADD}SUM(hits) as requests,category,familysite as sitename FROM $table GROUP BY {$FIELDADD}category,familysite HAVING `category`='$category'$HAVING   ORDER BY requests DESC LIMIT 0,10";
	$results=array();
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><hr><code style='font-size:12px'>$sql</code><hr>";}
	unset($xdata);unset($ydata);
	$title=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;$category&nbsp;{{$_GET["TimeType"]}}:{$_GET["day"]} (TOP 10 {websites} {requests})&nbsp;$title_add");
if(mysql_num_rows($results)>1){	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["requests"];
		$ydata[]=$ligne["sitename"];
		}
		$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
		$gp=new artica_graphs();
		$gp->width=400;
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
	
		$gp->pie();
		if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);}		
		
			
		$html2[]="<div class=RoundedGrey>$title<img src='$targetedfile' style='margin:10px'></div>";	
	}else{
		$ligne=array();
		$ligne=mysql_fetch_array($results);
		$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'><tr>";
		while (list ($a, $b) = each ($ligne) ){
			if(isset($already[$a])){continue;}
			if($a=="category"){continue;}
			if(is_numeric($a)){continue;}
			$heads[]="<th>{{$a}}</th>";
			$datas[]="<td style='font-size:14px'>$b</td>";
		}
		$html2[]="<div class=RoundedGrey>$title$htmlHeader". @implode("\n", $heads)."</tr><tr>". @implode("\n", $datas)."</tr></tbody></table></div>";
		
		
	}	
	
// ------------------------------------------------------------------------------------------------------------------------------------------------	
	@mysql_free_result($results);unset($xdata);unset($ydata);
	$sql="SELECT {$FIELDADD}SUM(size) as totalsize,category,familysite as sitename FROM $table GROUP BY {$FIELDADD}category,familysite HAVING `category`='$category'$HAVING   ORDER BY totalsize DESC LIMIT 0,10";
	$results=array();
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><hr><code style='font-size:12px'>$sql</code><hr>";}
	unset($xdata);unset($ydata);
	$title=TITLE_SQUID_STATSTABLE($sql,"{statistics}:&nbsp;$category&nbsp;{{$_GET["TimeType"]}}:{$_GET["day"]} (TOP 10 {websites} {downloaded_flow})&nbsp;$title_add");
if(mysql_num_rows($results)>1){	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["totalsize"];
		$ydata[]=$ligne["sitename"];
		}
		$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". md5($sql).".png";
		$gp=new artica_graphs();
		$gp->width=400;
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
	
		$gp->pie();
		if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);}		
		
			
		$html2[]="<div class=RoundedGrey>$title<img src='$targetedfile' style='margin:10px'></div>";	
	}else{
		$ligne=array();
		$ligne=mysql_fetch_array($results);
		$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'><tr>";
		while (list ($a, $b) = each ($ligne) ){
			if(isset($already[$a])){continue;}
			if($a=="category"){continue;}
			if(is_numeric($a)){continue;}
			$heads[]="<th>{{$a}}</th>";
			$datas[]="<td style='font-size:14px'>$b</td>";
		}
		$html2[]="<div class=RoundedGrey>$title$htmlHeader". @implode("\n", $heads)."</tr><tr>". @implode("\n", $datas)."</tr></tbody></table></div>";
		
		
	}	
	echo 
	"<table style='width:100%'>
	<tbody>
	<tr>
	<td width=50% valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $html))."</td>
	<td width=50% valign='top'>".$tpl->_ENGINE_parse_body(@implode("\n", $html2))."</td>
	</tr>
	</tbody>
	</table>
	";
	
	
}
