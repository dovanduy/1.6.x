<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");

if(!isset($_SESSION["uid"])){die("Oups");}

include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.youtube.inc");

if(isset($_GET["thumbnail"])){thumbnail();exit;}


if(!$_SESSION["AsWebStatisticsAdministrator"]){die("oups");}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["master-content"])){master_content();exit;}

if(isset($_GET["categories-list"])){categories_list();exit;}
if(isset($_GET["js"])){js();exit;}
if(isset($_GET["main-graphs"])){main_graphs();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["table-all-videos"])){table_all_videos();exit;}
if(isset($_GET["videos-search"])){table_all_videos_search();exit;}
if(isset($_GET["videos-search-js"])){table_all_videos_search_js();exit;}
if(isset($_GET["videos-search-popup"])){table_all_videos_search_popup();exit;}
if(isset($_POST["QUERY_YOUTUBE_LIMIT"])){table_all_videos_search_save();exit;}
main_page();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	if(!$_SESSION["CORP"]){
		
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		echo "alert('$onlycorpavailable');";	
		return;
	}
	$q=new mysql_squid_builder();
	$youtube_objects=$q->COUNT_ROWS("youtube_objects");
	$youtube_objects=numberFormat($youtube_objects,0,""," ");
	
	$title=$tpl->_ENGINE_parse_body("$youtube_objects Youtube {objects}");
	echo "YahooWin3('926','$page?master-content=yes','$title')";
	
}

function main_page(){
	$page=CurrentPageName();
	
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content=str_replace("{SCRIPT}", "<script>alert('$onlycorpavailable');document.location.href='miniadm.webstats.php';</script>", $content);
		echo $content;	
		return;
	}
	
	
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;	
}


function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	if(isset($_GET["xtime"])){
		$_GET["year"]=date("Y",$_GET["xtime"]);
		$_GET["month"]=date("m",$_GET["xtime"]);
		$_GET["day"]=date("d",$_GET["xtime"]);	
	}
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		echo "<p class=text-error>$onlycorpavailable</p>";
		die();
	}
	
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
		&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
		
		</div>
		<H1>Youtube {objects}</H1>
		<p>{youtube_objects_statistics_text}</p>
	</div>	
	<div id='master-content'></div>
	
	<script>
		LoadAjax('master-content','$page?master-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function master_content(){
	
	if(isset($_GET["title"])){
		$title="		<H3>Youtube {objects}</H3>
		<p>{youtube_objects_statistics_text}</p>";
	}
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body($title);
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		echo "<p class=text-error>$onlycorpavailable</p>";
		die();
	}	

	$t=time();
	$boot=new boostrap_form();
	$array["{requests} & {size}"]="$page?main-graphs=yes";
	$array["{videos}"]="$page?table-all-videos=yes";
	echo $title.$boot->build_tab($array);	
	
	
}

function table_all_videos(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$LINKS["LINKS"][]=array("LABEL"=>"{advanced_search}","JS"=>"Loadjs('$page?videos-search-js=yes')");
	$form=$boot->SearchFormGen("title","videos-search",null,$LINKS);
	echo $form;
	
}

function table_all_videos_search_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{advanced_search}";
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin2(600,'$page?videos-search-popup=yes','$title')";	
	
}

function table_all_videos_search_save(){
	
	while (list ($key, $value) = each ($_POST) ){
		$_SESSION[$key]=$value;
	}
	sleep(1);
}

function table_all_videos_search_popup(){
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	
	if(isset($_SESSION["QUERY_YOUTUBE_CATZ"])){
		$testCatz=unserialize($_SESSION["QUERY_YOUTUBE_CATZ"]);
		if(!is_array($testCatz)){unset($_SESSION["QUERY_YOUTUBE_CATZ"]);}
	}
	
	
	if(!isset($_SESSION["QUERY_YOUTUBE_CATZ"])){
		$sql="SELECT category  FROM youtube_objects GROUP BY category";
		$results=$q->QUERY_SQL($sql);
	
	
		$dayz[null]="{select}";
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$time=time_to_date(strtotime($ligne["filetime"]." 00:00:00"));
			$dayz[$ligne["category"]]=$ligne["category"];
		}
		$_SESSION["QUERY_YOUTUBE_CATZ"]=serialize($dayz);
	}
	

	$LIMITS[50]=50;
	$LIMITS[250]=250;
	$LIMITS[500]=250;
	$LIMITS[1000]=1000;
	$LIMITS[2000]=2000;
	
	
	if(!isset($_SESSION["QUERY_YOUTUBE_LIMIT"])){$_SESSION["QUERY_YOUTUBE_LIMIT"]=250;}
	
	$boot->set_list("QUERY_YOUTUBE_CATE", "{category}", 
			unserialize($_SESSION["QUERY_YOUTUBE_CATZ"]),$_SESSION["QUERY_YOUTUBE_CATE"]);
	$boot->set_list("QUERY_YOUTUBE_LIMIT", "{rows}", $LIMITS,$_SESSION["QUERY_YOUTUBE_LIMIT"]);
	$boot->set_button("{search}");
	$boot->set_CloseYahoo("YahooWin2");
	$boot->set_formdescription("{advanced_search_explain}");
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
	
}

function table_all_videos_search(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$users=new usersMenus();
	$TB_HEIGHT=500;
	$TB_WIDTH=910;
	$uid=$_GET["uid"];
		
	$t=time();
	$new_entry=$tpl->javascript_parse_text("{new_backup_rule}");
	$imapserv=$tpl->_ENGINE_parse_body("{imap_server}");
	$account=$tpl->_ENGINE_parse_body("{account}");
//	$title=$tpl->_ENGINE_parse_body("$attachments_storage {items}:&nbsp;&laquo;$size&raquo;");
	$filessize=$tpl->_ENGINE_parse_body("{filesize}");
	$action_delete_rule=$tpl->javascript_parse_text("{action_delete_rule}");
	$enable=$tpl->_ENGINE_parse_body("{enable}");
	$compile_rules=$tpl->_ENGINE_parse_body("{compile_rules}");
	$online_help=$tpl->_ENGINE_parse_body("{online_help}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$error_want_operation=$tpl->javascript_parse_text("{error_want_operation}");
	$events=$tpl->javascript_parse_text("{events}");
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$created=$tpl->javascript_parse_text("{created}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$categories=$tpl->javascript_parse_text("{categories}");
	if(!isset($_SESSION["QUERY_YOUTUBE_LIMIT"])){$_SESSION["QUERY_YOUTUBE_LIMIT"]=250;}
	
	$q=new mysql_squid_builder();
	$search=string_to_flexquery("videos-search");
	
	$filters=array();
	$filters[]=SearchToSql("category",$_SESSION["QUERY_YOUTUBE_CATE"]);
	
	$sql="SELECT *  FROM youtube_objects WHERE 1 $search ".@implode($filters," ")." ORDER BY uploaded DESC LIMIT 0,{$_SESSION["QUERY_YOUTUBE_LIMIT"]}";
	
	$results = $q->QUERY_SQL($sql);
	$boot=new boostrap_form();
	
	if(!$q->ok){die("<p class=text-error>$q->mysql_error</p>");}	
	
	$seconds=$tpl->_ENGINE_parse_body("{seconds}");
	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$hours=$tpl->_ENGINE_parse_body("{hours}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$youtubeid=$ligne["youtubeid"];
		$color="black";
		$unit=$seconds;
		$ligne["duration"]=format_time($ligne["duration"]);
		
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$link=$boot->trswitch($urljsSIT);
		$jsvideo=$boot->trswitch("Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid');");
		$tr[]="
		<tr id='$id'>
		<td $jsvideo><img  src='$page?thumbnail=$youtubeid' class=img-polaroid></td>
		<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["uploaded"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["title"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["category"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["duration"]}</td>
		<td $link nowrap><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	}

	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>$created</th>
					<th>$title</th>
					<th>$category {$_SESSION["QUERY_YOUTUBE_CATE"]}</th>
					<th>$duration</th>
					<th>$hits</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";	
	
}

function thumbnail(){
	$q=new mysql_squid_builder();
	
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT thumbnail FROM youtube_objects WHERE youtubeid='{$_GET["thumbnail"]}'","artica_backup"));
	$t=time();
	header('Content-type: image/jpeg');
	header('Content-Disposition: inline; filename="'.$t.'.jpg"');
 	print($ligne["thumbnail"]);
}

function categories_list(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM youtube_objects GROUP BY category ORDER BY category";
	$results = $q->QUERY_SQL($sql,$database);
	$array[null]="{select}";
	while ($ligne = mysql_fetch_assoc($results)) {
		$array[$ligne["category"]]=$ligne["category"];
	}
	$category_label=$tpl->javascript_parse_text("{category}");
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{category}:</td>
		<td>".Field_array_Hash($array,"category-choose-$t",null,"CategoryChoosen$t()",null,0,"font-size:14px")."</td>
	</tr>
	</table>
	<script>
		function CategoryChoosen$t(){
			var category=document.getElementById('category-choose-$t').value;
			$('.ftitle').html('$category_label&raquo;&raquo;'+category);
			$('#flexRT$t').flexOptions({url: '$page?items=yes&category='+category}).flexReload();			
		
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}
function format_time($t,$f=':') // t = seconds, f = separator 
{
  return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}

function main_graphs(){
	
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}

	
	$page=CurrentPageName();
	$t=time().rand(0,time());
	$html="
	<div id='$t-1' style='width:990px;height:450px'></div>
	
	<div id='$t-2' style='width:990px;height:450px'></div>
	
	<div id='$t-3' style='width:990px;height:450px'></div>
	<script>
		AnimateDiv('$t-1');
		AnimateDiv('$t-2');
		AnimateDiv('$t-3');
		
		function F1$t(){
			Loadjs('$page?graph1=yes&container=$t-1');
		}
		
		function F2$t(){
			Loadjs('$page?graph2=yes&container=$t-2');
		}		

		
		function F3$t(){
			Loadjs('$page?graph3=yes&container=$t-3');
		}		
		setTimeout('F1$t()',500);
		setTimeout('F2$t()',1000);
		setTimeout('F3$t()',1500);
		
	</script>
	";
	SET_CACHED(__FILE__,__FUNCTION__, null, $html);
	echo $html;	
	
}

function graph1(){
	$tpl=new templates();
	
	
	if(isset($_SESSION[basename(__FILE__)][$_GET["container"]])){
		header("content-type: application/x-javascript");
		echo $_SESSION[basename(__FILE__)][$_GET["container"]];
	}
	
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	
	$sql="SELECT SUM(hits) as hits,category FROM `youtube_all` GROUP BY
	category ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	$size=$ligne["hits"];
			if(trim($ligne["category"])==null){$ligne["category"]=$unknown;}
			$PieData[$ligne["category"]]=$size;
	}
	
	if(!$q->ok){
	$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}
	
	
	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{categories}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{hits}");
	$_SESSION[basename(__FILE__)][$_GET["container"]]= $highcharts->BuildChart();
	echo $_SESSION[basename(__FILE__)][$_GET["container"]];
	
}
function graph2(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$youtube=new YoutubeStats();
	$sql="SELECT SUM(hits) as hits,youtubeid FROM `youtube_all` GROUP BY
	youtubeid ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$title=$youtube->youtube_title($ligne["youtubeid"]);
		if(strlen($title)>20){$title=substr($title, 0,17)."...";}
		$PieData[$title]=$size;
	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{videos}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_videos}/{hits}");
	echo $highcharts->BuildChart();

}
function graph3(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$youtube=new YoutubeStats();
	$sql="SELECT SUM(hits) as hits,uid FROM `youtube_all` GROUP BY
	uid ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$title=$ligne["uid"];
		if($title==null){$title=$unknown;}
		if(strlen($title)>20){$title=substr($title, 0,17)."...";}
		$PieData[$title]=$size;
	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{members}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_members}/{hits}");
	echo $highcharts->BuildChart();

}