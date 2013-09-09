<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die("<H1>Oups, please login..</H1>");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");

Privileges_members_ownstats();

if(isset($_GET["content"])){content();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["graph5"])){graph5();exit;}
if(isset($_GET["graph6"])){graph6();exit;}
if(isset($_GET["graph7"])){graph7();exit;}
if(isset($_GET["graph8"])){graph8();exit;}
if(isset($_GET["graph9"])){graph9();exit;}
if(isset($_GET["graph10"])){graph10();exit;}
if(isset($_GET["graph11"])){graph11();exit;}

if(isset($_GET["rqsize"])){rqsize_page();exit;}
if(isset($_GET["rqsize-graĥs"])){rqsize_graphs();exit;}
if(isset($_GET["rqsize-table"])){rqsize_table();exit;}

if(isset($_GET["www"])){www_page();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["www-table"])){www_table();exit;}
if(isset($_GET["www-search"])){www_search();exit;}

if(isset($_GET["categories"])){categories_page();exit;}
if(isset($_GET["categories-graĥs"])){categories_graphs();exit;}
if(isset($_GET["categories-table"])){categories_table();exit;}

if(isset($_GET["blocked"])){blocked_page();exit;}
if(isset($_GET["blocked-graphs"])){blocked_graphs();exit;}
if(isset($_GET["blocked-table"])){blocked_table();exit;}
if(isset($_GET["blocked-websites"])){blocked_websites();exit;}
if(isset($_GET["blocked-search"])){blocked_search();exit;}


if(isset($_GET["youtube"])){youtube();exit;}
if(isset($_GET["youtube-graphs"])){youtube_graphs();exit;}
if(isset($_GET["youtube-table"])){youtube_table();exit;}
if(isset($_GET["youtube-search"])){youtube_search();exit;}
if(isset($_GET["youtube-videos"])){youtube_videos();exit;}
if(isset($_GET["youtube-videos-search"])){youtube_videos_search();exit;}

if(isset($_GET["rescan"])){rescan_js();exit;}
if(isset($_POST["rescan"])){rescan();exit;}


$users=new usersMenus();

main_page();

function main_page(){
	$page=CurrentPageName();

	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);

	if($_GET["member-value"]==null){$_GET["member-value"]=urlencode($_SESSION["uid"]);}
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}')</script>", $content);
	echo $content;
}

function rescan_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{rescan_categories_ask} ?");
	$t=time();
	
	$html="
	var xdeletegp$t= function (obj) {
		var results=obj.responseText;
		if(results.length>5){alert(results);return;}
	
	}
	
	
	function deletegp$t(){
		if(!confirm('$title')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('rescan','yes');
		XHR.appendData('uid','{$_GET["uid"]}');
		XHR.sendAndLoad('$page', 'POST',xdeletegp$t);
	}
	
	deletegp$t()
	";
	echo $html;
	
}

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	//$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT COUNT( FROM youtube_objects WHERE youtubeid='{$_GET["thumbnail"]}'","artica_backup"));
	
	$youtuberq=$q->COUNT_ROWS("youtube_$uidtable");

	
	$array["{requests} & {size}"]="$page?rqsize=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{visited_websites}"]="$page?www=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{categories}"]="$page?categories=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	
	if(count($youtuberq)>0){
		$youtuberq=FormatNumber($youtuberq);
		$array["$youtuberq Youtube {hits}"]="$page?youtube=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	}
	
	$blockedtable="blocked_$uidtable";
	if($q->TABLE_EXISTS($blockedtable)){
		$array["{blocked_websites}"]="$page?blocked=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	}
	
	
	echo $boot->build_tab($array);	
	
}

function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$back=null;
	$users=new usersMenus();
	if($users->AsWebStatisticsAdministrator){
		$back="&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats.members2.php\">{web_statistics}:{members}</a>";
	}
	
	$memberurl=urlencode($_GET["member-value"]);
	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
	$back
	</div>
	<H1>{web_statistics}:{$_GET["member-value"]}</H1>
	<p>{web_statistics_member_intro}</p>
	</div>
	<div id='master-content'></div>

	<script>
	LoadAjax('master-content','$page?tabs=yes&member-value=$memberurl&by={$_GET["by"]}');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}


function rqsize_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	$array["{graphs}"]="$page?rqsize-graĥs=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{days} {values}"]="$page?rqsize-table=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	echo $boot->build_tab($array);

}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function rqsize_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,uid,zDate FROM members_uid 
	GROUP BY uid,zDate HAVING uid='{$_GET["member-value"]}' ORDER BY zDate DESC";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$dateT=date("{l} {F} d",$date);
		if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$date);}	
		$dateT=$tpl->_ENGINE_parse_body($dateT);
		
		$js="Loadjs('miniadm.webstats.ByMember.ByDay.php?member-value={$_GET["member-value"]}&xtime=$date')";
		
		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-time'></i> $dateT</a></td>
		<td $link><i class='icon-info-sign'></i> $size</td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";	
	

	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
}


function categories_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$users=new usersMenus();
	$uidenc=urlencode($_GET["member-value"]);
	if($users->AsWebStatisticsAdministrator){
		$bt=$tpl->_ENGINE_parse_body(button("{rescan}","Loadjs('$page?rescan=yes&uid=$uidenc')"));
	}
	
	
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,category FROM `www_$uidtable`
	GROUP BY category  ORDER BY size DESC,hits DESC";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					$size=FormatBytes($ligne["size"]/1024);
					$hits=FormatNumber($ligne["hits"]);
					$category=$ligne["category"];
					$category_text=$ligne["category"];
					if(trim($ligne["category"])==null){$category_text=$unknown;}
					
					
					$categoryenc=urlencode($ligne["category"]);
					$js="Loadjs('miniadm.webstats.ByMember.ByCategory.php?uid={$_GET["member-value"]}&category=$categoryenc')";
	
					$link=$boot->trswitch($js);
					$tr[]="
					<tr>
					<td $link><i class='icon-tag'></i> $category_text</a></td>
					<td $link><i class='icon-info-sign'></i> $size</td>
					<td $link><i class='icon-info-sign'></i> $hits</td>
					</tr>";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{category}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>
				<div style='text-align:right;margin-top:10px'>$bt</div>";	
	
	
}

function blocked_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$users=new usersMenus();
	$uidenc=urlencode($_GET["member-value"]);
	
	
	if(!$q->TABLE_EXISTS("`blocked_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`blocked_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	
	$sql="SELECT SUM(hits) as hits, zDate FROM `www_$uidtable`
	GROUP BY zDate  ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";return;}
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$hits=FormatNumber($ligne["hits"]);
		$zDate=$ligne["zDate"];
		$xtime=strtotime($zDate." 00:00:00");
		$zDateT=time_to_date($xtime);
		$categoryenc=urlencode($ligne["category"]);
		//$js="Loadjs('miniadm.webstats.ByMember.ByCategory.php?uid={$_GET["member-value"]}&category=$categoryenc')";
	
		$link=$boot->trswitch($js);
		$tr[]="
		<tr>
		<td $link><i class='icon-time'></i>&nbsp;$zDateT</a></td>
		<td $link><i class='icon-info-sign'></i> $hits</td>
		</tr>";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	<H3>{blocked}/{day}</H3>
	<table class='table table-bordered table-hover'>
	
	<thead>
	<tr>
		<th>{date}</th>
		<th>{hits}</th>
	</tr>
			</thead>
			 <tbody>
				").@implode("", $tr)."</tbody></table>
				<div style='text-align:right;margin-top:10px'>$bt</div>";
	
		
	
}


function rqsize_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="
	<div id='$t-1' style='width:990px;height:450px'></div>
	<div id='$t-2' style='width:990px;height:450px'></div>
	<div id='$t-3' style='width:990px;height:450px'></div>			
	<script>
		AnimateDiv('$t-1');
		AnimateDiv('$t-2');
		Loadjs('$page?graph1=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
		Loadjs('$page?graph2=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
	</script>
	";
	echo $html;
	
}

function www_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);

	$array["{graphs}"]="$page?www-graĥs=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{values}"]="$page?www-table=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	echo $boot->build_tab($array);

}

function categories_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	$array["{graphs}"]="$page?categories-graĥs=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{values}"]="$page?categories-table=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	echo $boot->build_tab($array);	
	
}
function blocked_page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	
	$array["{graphs}"]="$page?blocked-graphs=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{days} {values}"]="$page?blocked-table=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{websites} {values}"]="$page?blocked-websites=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	echo $boot->build_tab($array);	
	
}

function youtube(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);

	$array["{graphs}"]="$page?youtube-graphs=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	$array["{days} {values}"]="$page?youtube-table=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	
	
	$sock=new sockets();
	$PerMembersYoutubeDetails=$sock->GET_INFO("PerMembersYoutubeDetails");
	if(!is_numeric($PerMembersYoutubeDetails)){$PerMembersYoutubeDetails=0;}	
	
	if($PerMembersYoutubeDetails==1){
		$array["{videos} {values}"]="$page?youtube-videos=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}";
	}
	echo $boot->build_tab($array);	
	
}


function www_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$form=$boot->SearchFormGen("familysite","www-search","&member-value={$_GET["member-value"]}&by={$_GET["by"]}");
	echo $form;
}
function youtube_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$form=$boot->SearchFormGen("zDate","youtube-search","&member-value={$_GET["member-value"]}&by={$_GET["by"]}");
	echo $form;	
}

function blocked_websites(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$form=$boot->SearchFormGen("familysite","blocked-search","&member-value={$_GET["member-value"]}&by={$_GET["by"]}");
	echo $form;	
	
}
function youtube_videos(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$form=$boot->SearchFormGen("title,catz","youtube-videos-search","&member-value={$_GET["member-value"]}&by={$_GET["by"]}");
	echo $form;	
}
function format_time($t,$f=':') // t = seconds, f = separator
{
	return sprintf("%02d%s%02d%s%02d%s", floor($t/3600), "h ", ($t/60)%60, "mn ", $t%60,"s");
}
function youtube_videos_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("requests-search");
	$xtime=$_GET["xtime"];
	$q=new mysql_squid_builder();
	$category=$tpl->javascript_parse_text("{category}");
	$title=$tpl->javascript_parse_text("{video_title}");
	$created=$tpl->javascript_parse_text("{created}");
	$duration=$tpl->javascript_parse_text("{duration}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$categories=$tpl->javascript_parse_text("{categories}");
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	
	
	$search=string_to_flexquery("youtube-videos-search");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$_GET["uid"]=mysql_escape_string2($_GET["member-value"]);
	$sql="SELECT youtube_objects.category as catz,youtube_objects.youtubeid,
	youtube_objects.uploaded,youtube_objects.duration,youtube_objects.title 
	FROM 
	(SELECT SUM(hits) as hits,youtubeid FROM `youtube_$uidtable` GROUP BY
	youtubeid ORDER BY hits) as t,youtube_objects
		WHERE t.youtubeid=youtube_objects.youtubeid $search
		LIMIT 0,250";
		$results=$q->QUERY_SQL($sql);
	
		$results = $q->QUERY_SQL($sql);
		$boot=new boostrap_form();
	
		if(!$q->ok){die("<p class=text-error>$q->mysql_error<br>$sql</p>");}
	
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
			<tr>
			<td $jsvideo><img src='miniadm.webstats.youtube.php?thumbnail=$youtubeid' class=img-polaroid></td>
			<td $link nowrap><i class='icon-time'></i>&nbsp;{$ligne["uploaded"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["title"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["category"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["duration"]}</td>
		<td $link><i class='icon-info-sign'></i>&nbsp;{$ligne["hits"]}</td>
		</tr>";
		}
	
		echo $tpl->_ENGINE_parse_body("
				<table class='table table-bordered table-hover'>
				<thead>
				<tr>
				<th>&nbsp;</th>
				<th>$created</th>
				<th>$title</th>
				<th>$category</th>
				<th>$duration</th>
				<th>$hits</th>
				</tr>
				</thead>
				<tbody>
				").@implode("", $tr)."</tbody></table>";
	
	
	
	
}

function youtube_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`youtube_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`youtube_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$search=string_to_flexquery("youtube-search");
	$sql="SELECT * FROM (SELECT COUNT(youtubeid) as hits ,zDate FROM(SELECT youtubeid,zDate FROM `youtube_$uidtable` GROUP BY zDate,youtubeid ORDER BY zDate)as t GROUP BY zDate) as i WHERE 1 $search";
	
	
	$results=$q->QUERY_SQL($sql);
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
	$sock=new sockets();
	$PerMembersYoutubeDetails=$sock->GET_INFO("PerMembersYoutubeDetails");
	if(!is_numeric($PerMembersYoutubeDetails)){$PerMembersYoutubeDetails=0;}
		
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$xtime=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$dateT=time_to_date($xtime);
		$jslink=null;
		if($PerMembersYoutubeDetails==1){
			$jslink="Loadjs('miniadm.webstats.ByMember.youtube.Byday.php?xtime=$xtime&member-value={$_GET["member-value"]}&by={$_GET["by"]}')";
		}
		
		
		$link=$boot->trswitch($jslink);
			$tr[]="
			<tr>
				<td $link><i class='icon-time'></i>&nbsp;$dateT</a></td>
				<td $link><i class='icon-info-sign'></i>&nbsp;$hits</td>
			</tr>";
	
	
	
		}
	
		echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{date}</th>
					<th>{videos}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
	
		
	
}

function www_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$search=string_to_flexquery("www-search");
	$sql="SELECT * FROM (SELECT SUM(hits) as hits,SUM(size) as size,familysite FROM `www_$uidtable` GROUP BY
	familysite ORDER BY familysite) as t WHERE 1 $search ORDER BY size DESC,hits DESC,familysite LIMIT 0,250";
	
	
	$results=$q->QUERY_SQL($sql);
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$hits=FormatNumber($ligne["hits"]);
		$fsite=urlencode($ligne["familysite"]);
		$jslink="Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}')";
		$link=$boot->trswitch($jslink);
			$tr[]="
				<tr id='$id'>
					<td $link><i class='icon-globe'></i> {$ligne["familysite"]}</a></td>
					<td $link><i class='icon-info-sign'></i> $size</td>
					<td $link><i class='icon-info-sign'></i> $hits</td>
				</tr>";
	
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
	
		<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
		
	
	
}

function blocked_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	
	$q=new mysql_squid_builder();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`blocked_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`blocked_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$search=string_to_flexquery("www-search");
	$sql="SELECT * FROM (SELECT SUM(hits) as hits,sitename FROM `blocked_$uidtable` GROUP BY
		sitename ORDER BY hits DESC) as t WHERE 1 $search LIMIT 0,250";
	
	
		$results=$q->QUERY_SQL($sql);
		$_GET["member-value"]=urlencode($_GET["member-value"]);
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<br>$sql</p>";return;}
	
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
					
				$date=strtotime($ligne["zDate"]."00:00:00");
				$hits=FormatNumber($ligne["hits"]);
				$fsite=urlencode($ligne["sitename"]);
				//$jslink="Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}')";
				$link=$boot->trswitch($jslink);
					$tr[]="
					<tr id='$id'>
					<td $link><i class='icon-globe'></i>&nbsp;{$ligne["sitename"]}</a></td>
					<td $link><i class='icon-info-sign'></i> $hits</td>
					</tr>";
	
	
	
		}
	
		echo $tpl->_ENGINE_parse_body("
	
				<table class='table table-bordered table-hover'>
	
			<thead>
				<tr>
					<th>{website}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
	
	
		
}

function www_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
	$html="
	<div id='$t-1' style='width:990px;height:450px'></div>
	<div id='$t-2' style='width:990px;height:450px'></div>
	<script>
	AnimateDiv('$t-1');
	AnimateDiv('$t-2');
	Loadjs('$page?graph4=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
	Loadjs('$page?graph5=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
	</script>
	";
	echo $html;	
	
}

function categories_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`www_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`www_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
			$html="
			<div id='$t-1' style='width:990px;height:450px'></div>
			<div id='$t-2' style='width:990px;height:450px'></div>
			<script>
			AnimateDiv('$t-1');
			AnimateDiv('$t-2');
			Loadjs('$page?graph6=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
			Loadjs('$page?graph7=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
			</script>
			";
			echo $html;	
	
}

function blocked_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`blocked_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`blocked_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
			$html="
			<div id='$t-1' style='width:990px;height:450px'></div>
			<div id='$t-2' style='width:990px;height:450px'></div>
			<script>
			AnimateDiv('$t-1');
			AnimateDiv('$t-2');
			Loadjs('$page?graph8=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
			Loadjs('$page?graph9=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
			</script>
			";
			echo $html;	
	
}

function youtube_graphs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	if(!$q->TABLE_EXISTS("`youtube_$uidtable`")){
		echo "<p class=text-error>No table &laquo;`youtube_$uidtable`&raquo; for {$_GET["member-value"]}</p>";
		return;
	}
	
	$_GET["member-value"]=urlencode($_GET["member-value"]);
			$html="
			<div id='$t-1' style='width:990px;height:450px'></div>
			<div id='$t-2' style='width:990px;height:450px'></div>
			<script>
			AnimateDiv('$t-1');
			AnimateDiv('$t-2');
			Loadjs('$page?graph10=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-1');
			Loadjs('$page?graph11=yes&member-value={$_GET["member-value"]}&by={$_GET["by"]}&container=$t-2');
			</script>
			";
			echo $html;	
	
}

function graph2(){
	$q=new mysql_squid_builder();
	
	
	
	$sql="SELECT SUM(size) as size,uid,zDate FROM members_uid GROUP BY uid,zDate HAVING uid='{$_GET["member-value"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=round($size/1024,2);
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;
	
	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{size}";
	$highcharts->yAxisTtitle="{size} MB";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{size}"=>$ydata);
	echo $highcharts->BuildChart();	
}
function graph1(){
	$q=new mysql_squid_builder();
	$sql="SELECT SUM(hits) as hits,uid,zDate FROM members_uid GROUP BY uid,zDate HAVING uid='{$_GET["member-value"]}' ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="{requests}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();
}

function graph10(){
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as hits,zDate FROM `youtube_$uidtable` GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	$tpl=new templates();
	
	if(!$q->ok){
		$tpl->javascript_senderror("$q->mysql_error", $_GET["container"]);
	}
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;
	
	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="Youtube {requests}";
	$highcharts->yAxisTtitle="{hits}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{requests}"=>$ydata);
	echo $highcharts->BuildChart();	
	
}
function graph11(){
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT COUNT(youtubeid) as hits,zDate FROM `youtube_$uidtable` GROUP BY zDate,youtubeid ORDER BY zDate";
	$sql="SELECT COUNT(youtubeid) as hits ,zDate FROM(SELECT youtubeid,zDate FROM `youtube_$uidtable` GROUP BY zDate,youtubeid ORDER BY zDate)as t GROUP BY zDate";
	$results=$q->QUERY_SQL($sql);
	$tpl=new templates();

	if(!$q->ok){
		$tpl->javascript_senderror("$q->mysql_error", $_GET["container"]);
	}

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$date=strtotime($ligne["zDate"]."00:00:00");
		$xdata[]=date("m-d",$date);
		$ydata[]=$size;

	}
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->xAxis=$xdata;
	$highcharts->Title="Youtube {videos}";
	$highcharts->yAxisTtitle="{videos}";
	$highcharts->xAxisTtitle="{days}";
	$highcharts->datas=array("{videos}"=>$ydata);
	echo $highcharts->BuildChart();

}
function graph4(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as hits,familysite FROM `www_$uidtable` GROUP BY 
	familysite ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		$PieData[$ligne["familysite"]]=$size;
	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{hits}");
	echo $highcharts->BuildChart();
	
}

function graph6(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");	
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(size) as size,category FROM `www_$uidtable` GROUP BY category
	ORDER BY size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
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
	$highcharts->PiePlotTitle="{categories} (MB)";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{size}");
	echo $highcharts->BuildChart();	
}


function graph5(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(size) as size,familysite FROM `www_$uidtable` GROUP BY familysite 
	ORDER BY size DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
		$size=$size/1024;
		$size=$size/1024;
		$size=round($size,2);
		$PieData[$ligne["familysite"]]=$size;


	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{size} (MB)";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_websites}/{size}");
	echo $highcharts->BuildChart();

}


function graph7(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as hits,category FROM `www_$uidtable` GROUP BY
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
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{top_categories}/{hits}");
	echo $highcharts->BuildChart();

}
function graph8(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as size,category FROM `blocked_$uidtable` GROUP BY category
	ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["size"];
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
	$highcharts->Title=$tpl->_ENGINE_parse_body("{blocked}/{top_categories}/{hits}");
	echo $highcharts->BuildChart();
}
function graph9(){
	$tpl=new templates();
	$unknown=$tpl->_ENGINE_parse_body("{unknown}");
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$uidtable=$q->uid_to_tablename($_GET["member-value"]);
	$sql="SELECT SUM(hits) as hits,sitename FROM `blocked_$uidtable` GROUP BY
	sitename ORDER BY hits DESC LIMIT 0,15";
	$results=$q->QUERY_SQL($sql);

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=$ligne["hits"];
		if(trim($ligne["sitename"])==null){$ligne["sitename"]=$unknown;}
		$PieData[$ligne["sitename"]]=$size;
	}

	if(!$q->ok){
		$tpl->javascript_senderror($q->mysql_error,$_GET["container"]);
	}


	$tpl=new templates();
	$highcharts=new highcharts();
	$highcharts->container=$_GET["container"];
	$highcharts->PieDatas=$PieData;
	$highcharts->ChartType="pie";
	$highcharts->PiePlotTitle="{hits}";
	$highcharts->Title=$tpl->_ENGINE_parse_body("{blocked}/{top_websites}/{hits}");
	echo $highcharts->BuildChart();

}
function rescan(){
	$sock=new sockets();
	$sock->getFrameWork("squidstats.php?category-uid={$_POST["uid"]}");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{cyrreconstruct_wait}");
	
}