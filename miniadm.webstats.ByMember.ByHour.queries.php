<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=text-error>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){die();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
Privileges_members_ownstats();

if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["www-graĥs"])){www_graphs();exit;}
if(isset($_GET["graph1"])){graph1();exit;}
if(isset($_GET["graph2"])){graph2();exit;}
if(isset($_GET["graph3"])){graph3();exit;}
if(isset($_GET["graph4"])){graph4();exit;}
if(isset($_GET["www-categories"])){www_categories();exit;}
if(isset($_GET["www-requests"])){www_requests();exit;}
if(isset($_GET["requests-search"])){www_requests_search();exit;}

js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title="{$_GET["uid"]}";
	$uid=urlencode($_GET["uid"]);
	$dateT=date("{l} {F} d",$_GET["xtime"]);
	if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$_GET["xtime"]);}	
	$dateT=$tpl->javascript_parse_text("$dateT {$_GET["hour"]}H");
	$sitename=$_GET["sitename"];
	$sitenameenc=urlencode($sitename);
	$html="YahooWin5('900','$page?tabs=yes&sitename=$sitenameenc&uid=$uid&xtime={$_GET["xtime"]}&hour={$_GET["hour"]}','$dateT::{$_GET["uid"]}')";
	echo $html;
//Loadjs('miniadm.webstats.ByMember.website.php?familysite=$fsite&member-value={$_GET["member-value"]}&by={$_GET["by"]}	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$q=new mysql_squid_builder();
	$boot=new boostrap_form();
	$date=time_to_date($_GET["xtime"]);
	$dateT=$tpl->javascript_parse_text("$date {$_GET["hour"]}H");
	$sitename=$_GET["sitename"];
	$familysite=$q->GetFamilySites($sitename);
	$sitenameenc=urlencode($sitename);
	$title=$tpl->_ENGINE_parse_body("{member}:{$_GET["uid"]}, $dateT &laquo;$sitename&raquo;");
	$_GET["uid"]=urlencode($_GET["uid"]);
	$array["{requests}"]="$page?www-requests=yes&sitename=$sitenameenc&hour={$_GET["hour"]}&uid={$_GET["uid"]}&xtime={$_GET["xtime"]}";
	$array[$familysite]="miniadm.webstats.website.infos.php?familysite=$familysite";
	echo "<H3>".$title."</H3>".$boot->build_tab($array);
}


function www_requests(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$sitename=$_GET["sitename"];
	$sitenameenc=urlencode($sitename);	
	$_GET["uid"]=urlencode($_GET["uid"]);
	$form=$boot->SearchFormGen("uri","requests-search","&sitename=$sitenameenc&uid={$_GET["uid"]}&hour={$_GET["hour"]}&xtime={$_GET["xtime"]}");
	echo $form;

}
function www_requests_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$boot=new boostrap_form();
	$search=string_to_flexquery("requests-search");
	$xtime=$_GET["xtime"];
	$dansguardian_table= "dansguardian_events_".date("Ymd",$xtime);

	$q=new mysql_squid_builder();

	$sql="SELECT uri,uid,QuerySize as size,hits FROM $dansguardian_table
	WHERE `sitename`='{$_GET["sitename"]}'
	AND HOUR(zDate)={$_GET["hour"]}
	AND uid='{$_GET["uid"]}' $search";
	$results=$q->QUERY_SQL($sql);


	$results=$q->QUERY_SQL($sql);
	if(mysql_num_rows($results)==0){senderrors("No data");}
	$_GET["uid"]=urlencode($_GET["uid"]);
	if(!$q->ok){senderrors($q->mysql_error);}
	

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		
		
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);
		$link=$boot->trswitch("blur()");
		$uri=$ligne["uri"];
		
		$tr[]="
		<tr>
		<td><i class='icon-globe'></i>$uri</td>
		</tr>";
	}

	echo $tpl->_ENGINE_parse_body("

			<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{requests}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("", $tr)."</tbody></table>";
}