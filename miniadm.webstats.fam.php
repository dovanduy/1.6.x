<?php
session_start();$_SESSION["MINIADM"]=true;

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.calendar.inc");
if(!$_SESSION["AsWebStatisticsAdministrator"]){header("location:miniadm.index.php");die();}

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["section"])){section_websites();exit;}
if(isset($_GET["sites-search"])){section_websites_search();exit;}
main_page();

function main_page(){
	//annee=2012&mois=9&jour=22
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$prefix=prefix();
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes$prefix')</script>", $content);
	echo $content;
}

function prefix(){
	
	return "&catfam={$_GET["catfam"]}&xtime={$_GET["xtime"]}";
}


function content(){
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	$jsadd=null;
	$prefix=prefix();
	$mainjs="LoadAjax('webstats-left','$page?tabs=yes&t=$t$prefix');";

	$FAMS[1]["TITLE"]="{dangerous_websites}";
	$FAMS[1]["EXPLAIN"]="{dangerous_websites_fam_explain}";
	
	$FAMS[2]["TITLE"]="{websites_network_pollution}";
	$FAMS[2]["EXPLAIN"]="{websites_network_pollution_explain}";
	
	
	$FAMS[3]["TITLE"]="{websites_human_suspects}";
	$FAMS[3]["EXPLAIN"]="{websites_human_suspects_explain}";
	
	$FAMS[4]["TITLE"]="{websites_heavy_cat}";
	$FAMS[4]["EXPLAIN"]="{websites_heavy_cat_explain}";
	
	$FAMS[5]["TITLE"]="{websites_noprod}";
	$FAMS[5]["EXPLAIN"]="{websites_noprod_explain}";
	
	$title=$FAMS[$_GET["catfam"]]["TITLE"];
	$explain=$FAMS[$_GET["catfam"]]["EXPLAIN"];
	
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if($DisableArticaProxyStatistics==1){
		$error=$tpl->_ENGINE_parse_body("<p class=text-error>{DisableArticaProxyStatistics_disabled_explain}</p>
				<center style='margin:30px;font-size:18px;text-decoration:underline'>
				<a href=\"javascript:Loadjs('squid.artica.statistics.php')\">{ARTICA_STATISTICS_TEXT}</a>
				</center>
				");
		$mainjs=null;
	}

	if($users->PROXYTINY_APPLIANCE){
		$jsadd="LoadAjax('statistics-$t','miniadm.webstats.sarg.php?tabs=yes');";
		$mainjs=null;
		$error=null;
	}

	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
	&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
	<H1>$title</H1>
	<p>$explain</p>
	<div id='statistics-$t'></div>
	</div>	$error
	<div id='webstats-left' style='margin-top:15px'></div>

	<script>
	$mainjs
	$jsadd
	</script>
	";

	$html=$tpl->_ENGINE_parse_body($html);

	echo $html;
}

function tabs(){
	$page=CurrentPageName();
	$array["{websites}"]="$page?section=yes".prefix();
	$boot=new boostrap_form();
	echo $boot->build_tab($array);	
	
}

function section_websites(){

	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	echo $boot->SearchFormGen("familysite","sites-search",prefix());

}
function section_websites_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$current_month=date("Ym",$_GET["xtime"]);
	$table="{$current_month}_catfam";
	$searchstring=string_to_flexquery("sites-search");
	
	$ORDER=$boot->TableOrder(array("size"=>"DESC"));
	if(!$q->TABLE_EXISTS($table)){senderrors("no such table");}
	if($q->COUNT_ROWS($table)==0){senderrors("no data");}
	//zDate      | client        | uid               | hostname                | MAC               | familysite                                 | catfam | hits | size
	$table="( SELECT familysite,catfam,SUM(size) as size,SUM(hits) as hits FROM `$table` GROUP BY familysite HAVING catfam='{$_GET["catfam"]}') as t";
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$sitenameenc=urlencode($ligne["familysite"]);
		$js="Loadjs('miniadm.webstats.familysite.all.php?familysite=$sitenameenc')";
		$link=$boot->trswitch($js);
		$tr[]="
		<tr id='$md'>
			<td style='font-size:16px' width=10% nowrap $link>{$ligne["familysite"]}</td>
			<td style='font-size:16px' width=1% nowrap $link>{$ligne["hits"]}</td>
			<td style='font-size:16px' width=1% nowrap $link>{$ligne["size"]}</td>
		</tr>
		";
	}
	
	echo $boot->TableCompile(array("familysite"=>"{familysite}",
			
			"hits"=>"{hits}",
			"size"=>"{size}",
	),$tr);
	
}