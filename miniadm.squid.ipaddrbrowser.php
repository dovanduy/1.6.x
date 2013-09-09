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
if(!$_SESSION["AsWebStatisticsAdministrator"]){die();}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["search-records"])){search_records();exit;}
if(isset($_GET["visits-day-js"])){visists_days_js();exit;}
if(isset($_GET["visits-day-popup"])){visists_days_popup();exit;}
js();

function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{ipaddr}");
	echo "YahooWin4('700','$page?popup=yes&field={$_GET["field"]}','$title')";


}
function visists_days_js(){
	$tpl=new templates();
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{ipaddr}::{$_GET["visits-day-js"]}");
	echo "YahooWin5('700','$page?visits-day-popup=yes&MAC={$_GET["visits-day-js"]}','$title')";	
	
}
function popup(){
	$sock=new sockets();
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	
	echo $boot->SearchFormGen("ipaddr","search-records")."<script>
	ExecuteByClassName('SearchFunction');
	</script>";
	
	
}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function search_records(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$limitSql="LIMIT 0,150";
	$t=time();

	$searchstring=string_to_flexquery("search-records");

	$sql="SELECT * FROM (SELECT SUM(hits) as hits,SUM(size) as size,MAC,ipaddr FROM members_macip GROUP BY MAC,ipaddr ORDER BY size DESC,hits DESC) as t WHERE 1 $searchstring";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	$sock=new sockets();
	$boot=new boostrap_form();

	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}

	while ($ligne = mysql_fetch_assoc($results)) {
		$ligne["MAC"]=strtolower($ligne["MAC"]);
		$ligne["MAC"]=str_replace("-", ":", $ligne["MAC"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT uid FROM webfilters_nodes WHERE MAC='{$ligne["MAC"]}'"));
		$uid=$ligne2["uid"];
		$explainMac=null;
		if($uid<>null){$uid="&nbsp;($uid)";}
		
		$linkVisit=$boot->trswitch("Loadjs('$page?visits-day-js={$ligne["ipaddr"]}')");
		

		$ips=array();
		$results2 = $q->QUERY_SQL("SELECT ipaddr FROM members_macip WHERE MAC='{$ligne["MAC"]}' ORDER BY ipaddr");
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$ips[]=$ligne2["ipaddr"];
		}
		if(count($ips)>0){$explainMac="$explainMac<div><i style='font-size:11px'>".@implode(", ", $ips)."</i></div>";}		
		
		$tr[]="
		<tr id='$id'>
		<td $link><i class='icon-globe'></i> {$ligne["MAC"]}$uid</a>$explainMac</td>
		<td $linkVisit><i class='icon-download'></i> {$ligne["size"]} </td>
		<td $linkVisit><i class='icon-signal'></i> {$ligne["hits"]} </td>
		</tr>";


	}




	echo $tpl->_ENGINE_parse_body("

		<table class='table table-bordered table-hover'>

			<thead>
				<tr>
					<th>{MAC}</th>
					<th>{size}</th>
					<th>{hits}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
			</table>
			";
}

function visists_days_popup(){
	$q=new mysql_squid_builder();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	
	$sql="SELECT SUM(hits) as hits,SUM(size) as size,MAC,zDate 
	FROM members_mac GROUP BY MAC,zDate HAVING MAC='{$_GET["MAC"]}' ORDER BY zDate ";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	$sock=new sockets();
	$boot=new boostrap_form();
	
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
			
					$ligne["size"]=FormatBytes($ligne["size"]/1024);
					$ligne["hits"]=FormatNumber($ligne["hits"]);
					$xtime=strtotime($ligne["zDate"]." 00:00:00");
					$dateT=date("{l} {F} d",$xtime);
					if($tpl->language=="fr"){$dateT=date("{l} d {F} ",$xtime);}
					
					
					
					$linkVisit=$boot->trswitch("Loadjs('miniadm.webstats.ByMAC.popup.php?MAC={$ligne["MAC"]}&xtime=$xtime')");
					
					$tr[]=$tpl->_ENGINE_parse_body("
					<tr id='{$ligne["MAC"]}'>
					<td $linkVisit><i class='icon-time'></i> {$dateT}</a></td>
					<td $linkVisit><i class='icon-download'></i> {$ligne["size"]} </td>
					<td $linkVisit><i class='icon-signal'></i> {$ligne["hits"]} </td>
					</tr>");
	
	
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
			").@implode("\n", $tr)." </tbody>
			</table>
				";
	}
	

