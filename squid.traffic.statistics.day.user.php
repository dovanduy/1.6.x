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
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["display-synthesis"])){synthesis();exit;}
	
	if(isset($_GET["visited"])){visited();exit;}
	if(isset($_GET["websites-search"])){visited_search();exit;}
	
	
	
	if(isset($_GET["users-form"])){users_form();exit;}
	if(isset($_GET["users-search"])){users_search();exit;}
	if(isset($_GET["paragraphe1"])){paragraphe1();exit;}
	if(isset($_GET["paragraphe2"])){paragraphe2();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{day}&raquo;&raquo;{member}&raquo;&raquo;{$_GET["user"]}");
	$html="YahooWin4('890','$page?tabs=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}','$title')";
	echo $html;
	
}


function visited(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();	
	$titleW="{day}";
	$t=time();
	$websites=$tpl->_ENGINE_parse_body("{websites}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hitsTitl=$tpl->_ENGINE_parse_body("{hits}");	
	
	
	$TITLE_PAGE="{$_GET["field"]}:{$_GET["user"]} $titleW";
	if($_GET["field"]=="MAC"){
		$userText=$q->UID_FROM_MAC($_GET["user"]);
		if($userText<>null){
			$TITLE_PAGE="$userText ({$_GET["user"]}) $titleW";
		}
	}	
	
	$html="<div style='font-size:16px;font-weight:bold'>$TITLE_PAGE</div>
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#events-table-$t').flexigrid({
	url: '$page?websites-search=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}',
	dataType: 'json',
	colModel : [
		{display: '$hitsTitl', name : 'hits', width :60, sortable : false, align: 'left'},
		{display: '$size', name : 'size', width :84, sortable : true, align: 'left'},
		{display: '$websites', name : 'sitename', width :625, sortable : true, align: 'left'},
	],

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
	width: 833,
	height: 503,
	singleSelect: true
	
	});   
});

</script>";		
	echo $tpl->_ENGINE_parse_body($html);
	
}


function visited_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	
	$page=1;
	$search='%';
	$table=$_GET["table"];
	$field=$_GET["field"];
	
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring=" AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT SUM(hits) as hits,SUM(size) as size,`sitename`,`$field`
		 FROM  $table GROUP BY `sitename`,`$field` HAVING `$field`='{$_GET["user"]}' $searchstring";

		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($results);
		
	}else{
		$sql="SELECT SUM(hits) as hits,SUM(size) as size,`sitename`,`$field`
		 FROM  $table GROUP BY `sitename`,`$field`";
		$results=$q->QUERY_SQL($sql,"artica_events");
		$total = mysql_num_rows($ligne);
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,`sitename`,`$field`
	FROM  $table GROUP BY `sitename`,`$field` HAVING `$field`='{$_GET["user"]}' $searchstring $ORDER $limitSql";
	
	
		
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	
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
	
	while ($ligne = mysql_fetch_assoc($results)) {
	$ligne["size"]=FormatBytes($ligne["size"]/1024);
	$data['rows'][] = array(
		'id' => $ligne['client'],
		'cell' => array($ligne["hits"],$ligne["size"],$ligne["sitename"])
		);
	}
	
	
echo json_encode($data);		

}

function tabs(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$id=md5("user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}");
	$array["popup"]=$tpl->_ENGINE_parse_body('{status}');
	$array["visited"]=$tpl->_ENGINE_parse_body('{visited_websites}');
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&user={$_GET["user"]}&field={$_GET["field"]}&table={$_GET["table"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n";
		
		
			
		}
	echo "<div id='$id' style='width:100%;height:700px;overflow:auto;background-color:white;'>
				<ul>". implode("\n",$html)."</ul>
		</div>
		<script>
				$(document).ready(function(){
					$('#$id').tabs();
			

			});
		</script>"	;
	
}

function users_form(){
	$t=time();
	$table=$_GET["table"];
	$page=CurrentPageName();
	$tpl=new templates();		
	$html="<center>
			". Field_text("member-$t-search",null,"font-size:16px;width:220px",null,null,null,false,"DayMembersSearchCheck$t(event)")."
		</center>
	<div id='member-$t-list' style='margin-top:10px'></div>
	<script>
		function DayMembersSearchCheck$t(e){
			if(checkEnter(e)){WeekMembersSearch();}
		}
	
	
		function DayMembersSearch(){
			var search=escape(document.getElementById('member-$t-search').value);
			LoadAjaxTiny('member-$t-list','$page?users-search=yes&table=$table&user={$_GET["user"]}&field={$_GET["field"]}&query='+search);
		}
		
		DayMembersSearch();
	
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
		<td valign='top' width=1%><div id='week-search-users-list'></div></td>
		<td valign='top' width=99%>
			<div id='$t'></div>
		</td>
	</tr>
	</tbody>
	</table>
	
	<script>
		function RefreshStatsUser(user,field,table){
			if(!table){table='{$_GET["table"]}';}
			if(!user){user='{$_GET["user"]}';}
			if(!field){field='{$_GET["field"]}';}
			LoadAjax('$t','$page?display-synthesis=yes&user='+user+'&field='+field+'&table='+table+'&divkey=$t');
		
		}
		RefreshStatsUser();
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function users_search(){
	$table=$_GET["table"];
	$user=$_GET["user"];
	$field=$_GET["field"];	
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();
	$query=$_GET["query"];
	$query="$query*";
	$query=str_replace("**", "*", $query);
	$query=str_replace("**", "*", $query);
	$query=str_replace("*", "%", $query);	

	$t=$_GET["divkey"];	
	if(!isset($_SESSION["SQUIDSTATS"]["WEEK"][$table])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(client) as tcount,client,hostname,uid,MAC 
		FROM $table GROUP BY client,hostname,uid,MAC ORDER BY uid,MAC,hostname,client"));
		$_SESSION["SQUIDSTATS"]["WEEK"][$table]=$ligne["tcount"];
	}	
	
	$title="<div style='font-size:12px;'>
		<strong>{$_SESSION["SQUIDSTATS"]["WEEK"][$table]}</strong> {others_users_have_browsed_internet_too}</div>";
	
	
	$sql="SELECT client,hostname,uid,$field,MAC from $table GROUP BY client,hostname,uid,$field,MAC
	HAVING `$field` LIKE '$query'
	ORDER BY client,hostname,uid,$field,MAC LIMIT 0,25";
	$results=$q->QUERY_SQL($sql);
	
	$rows[]="<table style='width:99%' class=form>
	<tr>
		<td colspan=2>$title</td>
	</tr>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$q="uid";
		$q2="hostname";
		
		if($ligne[$field]<>null){
			$ligne["uid"]=$ligne[$field];
			
		}
		$ligne["uid"]=trim($ligne["uid"]);
		if(trim($ligne["hostname"])==null){$ligne["hostname"]=$ligne["client"];$q2="client";}
		if($ligne["uid"]=="-"){$ligne["uid"]=$ligne["hostname"];$q=$q2;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$ligne["hostname"];$q=$q2;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$ligne["client"];$q=$q2;}
		if(trim($ligne["uid"])==null){$ligne["uid"]=$ligne["MAC"];$q=$q2;}		
		
		
		$usertext=$ligne["uid"];
		$eght=strlen($usertext);
		if($eght>25){$usertext=substr($usertext,0,22)."...";}
		
		$rows[]="<tr>
		<td width=1% valign='top'><IMG SRC='img/winuser.png'></TD>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.traffic.statistics.day.user.php?user={$ligne["uid"]}&field=$q&table=$table')\" style='font-weight:bold;font-size:12px;text-decoration:underline'>$usertext</a>
		
		</td></tr>";
		
	}
	
	$html=$html.@implode("\n", $rows)."</tbody></table>
	<script>
		function LoadUserReport1(){
			LoadAjaxTiny('paragraphe1','$page?paragraphe1=yes&table=$table&user=$user&field=$field');
		}
		
	function LoadUserReport2(){
			LoadAjaxTiny('paragraphe2','$page?paragraphe2=yes&table=$table&user=$user&field=$field');
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
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();
	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,category FROM(SELECT $table.hits,$table.size,$table.category,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category 
	AND $table.`$field`='$user'
	) as t
	GROUP BY category ORDER BY tsize DESC LIMIT 0,1";	
	$ligne2=mysql_fetch_array($sql);
	$p0=$tpl->_ENGINE_parse_body("{top_visited_categories_mostwieght}");
	$p0=str_replace("X", $ligne["category"], $p0);
	$p0=str_replace("Y", FormatBytes($ligne["tsize"]/1024), $p0);
	$p0="<div style='color:#CF1717;margin-top:5px;font-size:12px'>$p0</div>";
	

	
	$sql="SELECT SUM(hits) as thits,SUM(size) as tsize,category FROM(SELECT $table.hits,$table.size,$table.category,
	webfilters_categories_caches.master_category FROM webfilters_categories_caches,$table
	WHERE webfilters_categories_caches.categorykey=$table.category 
	AND $table.`$field`='$user'
	) as t
	GROUP BY category ORDER BY thits DESC LIMIT 0,10";
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){echo "<H4>$q->mysql_error</H4>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($ligne["category"]==null){continue;}
		$ligne["tsize"]=FormatBytes($ligne["tsize"]/1024);
		$ydata[]="{$ligne["category"]}";
		$xdata[]=$ligne["thits"];			
		
		$rows[]="<tr>
		<td width=1%><img src='img/20-categories-personnal.png'></td>
		<td><strong style='font-size:12px'>
		<a href=\"javascript:blur();\" 
		OnClick=\"javascript:Loadjs('squid.traffic.week.user.category.php?category={$ligne["category"]}&table=$table&field=$field&user=$user');\" 
		style='font-size:12px;text-decoration:underline;font-weight:bold'>
		{$ligne["category"]}</a> ({$ligne["thits"]} {hits} - {$ligne["tsize"]})</strong></td></tr>";
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
	
	
	$html="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>{top_visited_categories} :</div>$p0
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
	$page=CurrentPageName();
	$tpl=new templates();		
	$q=new mysql_squid_builder();

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table"));
	$downloadedINT=$ligne["tsize"];	
	
	$sql="SELECT SUM(size) as tsize FROM $table WHERE `$field`='$user' AND cached=1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$X3_bin=$ligne["tsize"];
	$X3_text="<strong>".FormatBytes($X3_bin/1024)."</strong>";
	
	$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize FROM $table WHERE `$field`='$user'"));
	$X1_bin=$ligne2["tsize"];
	$X1_text="<strong>".FormatBytes($X1_bin/1024)."</strong>";	
	
	$PRC=(($X3_bin/$X1_bin)*100);
	$PRC=round($PRC,3);
	
	
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize FROM $table WHERE `$field`='$user'"));
	if(!$q->ok){echo "<H4>$q->mysql_error line ".__LINE__."</H4>";}
	$X2_bin=$ligne["tsize"];
	$X2_text="<strong>$X2_bin</strong>";		
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT COUNT(familysite) as familysite,`$field` FROM $table WHERE `$field`='$user'"));
	if(!$q->ok){echo "<H4>$q->mysql_error line ".__LINE__."</H4>";}
	$X0_bin=$ligne["familysite"];
	$X0_text="<strong>$X0_bin</strong>";	
	
	$webstatsUserIntro1=$tpl->_ENGINE_parse_body("{webstatsUserIntro1}");
	$webstatsUserIntro1=str_replace("X3PRC", "<strong style='color:#CF1717'>$PRC%</strong>", $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X0", $X0_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X1", $X1_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X2", $X2_text, $webstatsUserIntro1);
	$webstatsUserIntro1=str_replace("X3", $X3_text, $webstatsUserIntro1);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as tsize,familysite,`$field` FROM $table GROUP BY familysite,`$field` HAVING `$field`='$user' ORDER BY tsize DESC LIMIT 0,1"));
	if(!$q->ok){echo "<H4>$q->mysql_error line ".__LINE__."</H4>";}
	$XXWWWS=$ligne["familysite"];
	$XWSZE=FormatBytes($ligne["tsize"]/1024);
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(hits) as tsize,familysite,`$field` FROM $table GROUP BY familysite,`$field` HAVING `$field`='$user' ORDER BY tsize DESC LIMIT 0,1"));
	if(!$q->ok){echo "<H4>$q->mysql_error line ".__LINE__."</H4>";}
	
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

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `hour`,SUM(hits) as thits,SUM(size) as tsize,`$field`  
	FROM $table GROUP BY `hour`,`$field`  HAVING `$field`='$user' ORDER BY thits DESC LIMIT 0,1"));
	if(!$q->ok){echo $q->mysql_error;}
	$MostActiveHourNum=$ligne["hour"];
	$MostActiveDaySize=FormatBytes($ligne["tsize"]/1024);

	if(strlen($MostActiveHourNum)==1){$MostActiveHourNum="0$MostActiveHourNum";}	
	$title="{$MostActiveHourNum}:00 {phrase_most_day_activehour} {for} $user";
	$prc=round($ligne["tsize"]/$downloadedINT,2)*100;
	
	$webstatsUserIntro3="<div style='color:#CF1717;font-weight:bold;margin-top:5px;font-size:13.5px'>$title</div>
	<div style='font-size:12px;text-align:justify;'>{with} <strong>{$ligne["thits"]} {hits}</strong> {or} <strong>$MostActiveDaySize</strong> {it_represents} <strong>$prc%</strong> {of_bandwith}</div>
	<div style='font-size:12px;text-align:justify;margin-top:10px;margin-bottom:15px'>{phrase_thisisthegraph1}:</div>"; 
	
	$sql="SELECT `hour`,SUM(hits) as thits,`$field` FROM $table GROUP BY `hour`,`$field` HAVING  `$field`='$user' ORDER BY `hour`";
		$results=$q->QUERY_SQL($sql);
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		$xdata[]=$ligne["hour"];
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
	$gp->x_title=$tpl->_ENGINE_parse_body("{hour}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	
	if(!is_file($targetedfile)){$targetedfile="img/nograph-000.png";}
	$graph1="<center style='margin-top:5px'><img src='$targetedfile'></center>";
	
	
	$HTML="<p style='font-size:12px'>$webstatsUserIntro1</p>
	<p style='font-size:12px'>$webstatsUserIntro2</p>
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
	
	$year=substr($table,0,4);
	$month=substr($table,4, 2);
	$day=substr($table,6, 2);
	$time=strtotime("$year-$month-$day 00:00:00");
	$dayTitle=date('{l}, {F} Y',$time);
	
	
	$TITLE_PAGE=">$user ({{$field}}) <br>$dayTitle";
	
	if($field=="MAC"){
		$userText=$q->UID_FROM_MAC($user);
		if($userText<>null){
			$TITLE_PAGE="$userText ({$user}) <br>$dayTitle";
		}
	}
	
	
	$html="
	
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td width=1%><img src='img/user-black-48.png'></td>
		<td><div style='width:100%;font-size:16px;font-weight:bold'>$TITLE_PAGE</div></td>
	</tr>
	</tbody>
	</table>
	<table style='width:100%'>
	<tbody>
		<tr>
			<td width=50% valign='top'><div id='paragraphe1'></div></td>
			<td width=50% valign='top'><div id='paragraphe2'></div></td>
		</tr>
	</tbody>
	</table>
	<script>
		function LoadUserSearchSection(){
			if(!document.getElementById('week-search-users-list')){alert('week-search-users-list no such id');}
			LoadAjaxTiny('week-search-users-list','$page?users-form=yes&table=$table&user=$user&field=$field');
		}
		
		LoadUserSearchSection();
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
	if(count($c)==0){$c[]="{unknown}";}
	$cats=texttooltip(": ".count($c)." {categories}",@implode(",<br>", $c),null,null,0,"font-size:12px;nodiv");
	return $tpl->_ENGINE_parse_body($cats);
}