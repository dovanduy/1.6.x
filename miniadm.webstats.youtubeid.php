<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mini.admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.youtube.inc");

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["video"])){video();exit;}

$users=new usersMenus();if(!$users->AsWebStatisticsAdministrator){die();}
if(isset($_GET["who"])){who();exit;}
if(isset($_GET["who-items"])){who_items();exit;}

js();



function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	SEND_CORP_LICENSE_JAVASCRIPT();
	$youtubeid=$_GET["youtubeid"];
	
	$sql="SELECT title FROM youtube_objects WHERE youtubeid='$youtubeid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));	
	//$title=utf8_encode($ligne["title"]);
	$title=str_replace("'", "`", $ligne["title"]);
	echo "YahooWin('800','$page?tabs=yes&youtubeid=$youtubeid&xtime={$_GET["xtime"]}','$title')";
}

function tabs(){
	$youtubeid=$_GET["youtubeid"];
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	$sql="SELECT title FROM youtube_objects WHERE youtubeid='$youtubeid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$title="<H3>{$ligne["title"]}</H3>";
	$boot=new boostrap_form();
	$array["{video}"]="$page?video=yes&youtubeid=$youtubeid&xtime={$_GET["xtime"]}";
	if($users->AsWebStatisticsAdministrator){
		$array["{who} ?"]="$page?who=yes&youtubeid=$youtubeid&xtime={$_GET["xtime"]}";
	}
	echo $title.$boot->build_tab($array);	
	
}

function who(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$form=$boot->SearchFormGen("uid,zDate,MAC","who-items","&youtubeid={$_GET["youtubeid"]}");
	echo $form;
	return;
}

function who_items(){
	$t=$_GET["t"];
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$users=new usersMenus();
	$sock=new sockets();
	
	$youtubeid=$_GET["youtubeid"];
	$searchstring=string_to_flexregex("who-items");
	$table="youtube_all";
	$page=1;
	$FORCE_FILTER=" AND ";
	
	if($searchstring<>null){$searchstring="$searchstring";}
	
	
	$sql="SELECT zDate,uid,MAC,youtubeid,SUM(hits) as hits FROM $table GROUP BY youtubeid,zDate,uid,MAC HAVING youtubeid='$youtubeid' $searchstring ORDER BY zDate DESC LIMIT 0,250";	
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){throw new Exception("ERROR $q->mysql_error `$sql`",500);}
	$boot=new boostrap_form();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=md5(serialize($ligne));
		$color="black";
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid')";
		$link=$boot->trswitch($urljsSIT);
		$jsvideo=$boot->trswitch("Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid');");
		$urljsSIT="Loadjs('miniadm.webstats.youtubeid.php?youtubeid=$youtubeid&xtime={$_GET["xtime"]}');";
		$link=$boot->trswitch($urljsSIT);
		$ligne["hits"]=numberFormat($ligne["hits"],0,""," ");
		$urljsSIT=null;
		$link=null;
		
		$xtime=strtotime($ligne["zDate"]." 00:00:00");
		
		
		$linkuid=$boot->trswitch("Loadjs('miniadm.webstats.ByMember.ByYoutubeByHour.php?filterBy=uid&xtime=$xtime&value={$ligne["uid"]}&youtubeid=$youtubeid')");
		$linkMAC=$boot->trswitch("Loadjs('miniadm.webstats.ByMember.ByYoutubeByHour.php?filterBy=MAC&xtime=$xtime&value={$ligne["MAC"]}&youtubeid=$youtubeid')");
		
		$tr[]="
		<tr id='$id'>
		<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["zDate"]}</td>
		<td $linkuid><i class='icon-info-sign'></i>&nbsp;{$ligne["uid"]}</td>
		<td $linkMAC><i class='icon-info-sign'></i>&nbsp;{$ligne["MAC"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
	
	}
	echo $tpl->_ENGINE_parse_body("
	<table class='table table-bordered table-hover'>
		<thead>
			<tr>
				<th>{zDate}</th>
				<th>{member}</th>
				<th>{MAC}</th>
				<th>{hits}</th>
			</tr>
		</thead>
	<tbody>
	").@implode("", $tr)."</tbody></table>";
	
	
}

function video(){
	$youtubeid=$_GET["youtubeid"];
	$xtime=$_GET["xtime"];
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();	
	$sql="SELECT * FROM youtube_objects WHERE youtubeid='$youtubeid'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$title=$ligne["title"];
	$category=$ligne["category"];
	$uploaded=$ligne["uploaded"];
	$duration=format_time($ligne["duration"]);
	$content=base64_decode($ligne["content"]);
	$infos=json_decode($content);
	$contentz=$infos->data->content;
	
	
	foreach ($contentz as $index => $value) {
		if(is_numeric($index)){
			$filename=basename($value);
			if(strpos($filename, "app=youtube_gdata")>0){$filename=null;}
			$links[]="<li><a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('$value','1024','900');\">{link} $index :$filename</a></li>";
			
		}
	}
	
	$html="
	<div class=BodyContent style='width:95%' class=form>
	<table style='width:99%'>
	<tr>
		<td valign='top' width=1% nowrap><img src='miniadm.webstats.youtube.php?thumbnail=$youtubeid'></td>
		<td valign='top' width=99%>
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{video_title}:</td>
				<td><strong style='font-size:14px'>$title</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{duration}:</td>
				<td><strong style='font-size:14px'>$duration</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{uploaded}:</td>
				<td><strong style='font-size:14px'>$uploaded</strong>
			</tr>
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{category}:</td>
				<td><strong style='font-size:14px'>$category</strong>
			</tr>	
			<tr>
				<td class=legend style='font-size:14px' valign='top'>{links}:</td>
				<td><div style='font-size:14px;margin-left:15px'>".@implode("", $links)."</strong>
			</tr>	
			
			</table>
		</td>
	</tr>
	</table>
	</div>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function format_time($t,$f=':') // t = seconds, f = separator 
{
  return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}