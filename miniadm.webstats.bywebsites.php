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


if(isset($_GET["main-content"])){section_websites();exit;}
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["sites-search"])){section_websites_search();exit;}
main_page();

function main_page(){
	//annee=2012&mois=9&jour=22
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}&week={$_GET["week"]}')</script>", $content);
	echo $content;
}

function content(){
	$sock=new sockets();
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$t=time();
	

	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
	&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php?t=$t&year={$_GET["year"]}&month={$_GET["month"]}&day={$_GET["day"]}\">{web_statistics}</a>
	</div>
	<H1>{visited_websites}</H1>
	<div id='statistics-$t'></div>
	</div>
	<div id='main-content' style='margin-top:15px'></div>

	<script>
		LoadAjax('main-content','$page?main-content=yes');
	</script>
	";

	$html=$tpl->_ENGINE_parse_body($html);

	echo $html;
}

function section_websites(){
	
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	echo $boot->SearchFormGen("familysite","sites-search",null,$EXPLAIN);

}

function section_websites_search(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$table="visited_sites_tot";
	$searchstring=string_to_flexquery("sites-search");
	$ORDER=$boot->TableOrder(array("size"=>"ASC"));
	if($q->COUNT_ROWS($table)==0){
		senderrors("no data");
	}
	
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


