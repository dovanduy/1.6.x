<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
include(dirname(__FILE__)."/ressources/class.influx.inc");


	$user=new usersMenus();
	if(!$user->AsWebStatisticsAdministrator){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	if(isset($_GET["stats-requeteur"])){stats_requeteur();exit;}
	if(isset($_GET["requeteur-popup"])){requeteur_popup();exit;}
	if(isset($_GET["requeteur-js"])){requeteur_js();exit;}	
	if(isset($_GET["query-js"])){build_query_js();exit;}
	if(isset($_GET["table1"])){table1();exit;}
	
	
	

function stats_requeteur(){
	$tpl=new templates();
	$page=CurrentPageName();


}
function requeteur_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$build_the_query=$tpl->javascript_parse_text("{build_the_query}::{members}");
	echo "YahooWin('670','$page?requeteur-popup=yes&t={$_GET["t"]}','$build_the_query');";
}

function requeteur_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	squid_stats_default_values();
	
	$t=$_GET["t"];
	$per["1m"]="{minute}";
	$per["5m"]="5 {minutes}";
	$per["10m"]="10 {minutes}";
	$per["1h"]="{hour}";
	$per["1d"]="{day}";
	
	
	$members["MAC"]="{MAC}";
	$members["USERID"]="{uid}";
	$members["IPADDR"]="{ipaddr}";
	$date_start=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_START")));
	$date_end=date("Y-m-d",intval(@file_get_contents("{$GLOBALS["BASEDIR"]}/DATE_END")));
	
	$q=new influx();

	$Selectore="mindate:$date_start;maxdate:$date_end";
	
	
	$html="<div style='width:98%;margin-bottom:20px' class=form>
	<table style='width:100%'>
	<tr>
			
		<td style='vertical-align:top;font-size:18px' class=legend>{members}:</td>
		<td style='vertical-align:top;font-size:18px;'>". Field_array_Hash($members,"members-$t",$_SESSION["SQUID_STATS_MEMBER"],"blur()",null,0,"font-size:18px;")."</td>
	</tr>
	<tr>			
	
		<td style='vertical-align:top;font-size:18px' class=legend>{from_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("from-date-$t",$_SESSION["SQUID_STATS_DATE1"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("from-time-$t",$_SESSION["SQUID_STATS_TIME1"],";font-size:18px;width:82px")."</td>
	</tr>
	<tr>
		<td style='vertical-align:top;font-size:18px' class=legend>{to_date}:</td>
		<td style='vertical-align:top;font-size:18px'>". field_date("to-date-$t",$_SESSION["SQUID_STATS_DATE2"],";font-size:18px;width:160px",$Selectore)."
		&nbsp;". Field_text("to-time-$t",$_SESSION["SQUID_STATS_TIME2"],";font-size:18px;width:82px")."</td>
		
	</tr>
	<tr>
		<td style='vertical-align:middle;font-size:18px' class=legend>{search}:</td>
		<td style='vertical-align:top;font-size:18px'>". Field_text("search-$t",$_SESSION["SQUID_STATS_MEMBER_SEARCH"],";font-size:18px;width:98%")."</td>
	</tr>
	<tr>
		<td style='vertical-align:top;font-size:18px;' colspan=2 align='right'><hr>". button("{generate_statistics}","Run$t()",18)."</td>
	</tr>
	</table>
	</div>
<script>
function Run$t(){
	var date1=document.getElementById('from-date-$t').value;
	var time1=document.getElementById('from-time-$t').value;
	var date2=document.getElementById('to-date-$t').value
	var time2=document.getElementById('to-time-$t').value;
	var user=document.getElementById('members-$t').value;
	var search=encodeURIComponent(document.getElementById('search-$t').value);
	var interval=0;
	Loadjs('$page?query-js=yes&t=$t&container=graph-$t&date1='+date1+'&time1='+time1+'&date2='+date2+'&time2='+time2+'&interval='+interval+'&user='+user+'&search='+search);
	
}
</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}

function build_query_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$from=strtotime("{$_GET["date1"]} {$_GET["time1"]}");
	$to=strtotime("{$_GET["date2"]} {$_GET["time2"]}");
	$interval=$_GET["interval"];
	$search=url_decode_special_tool($_GET["search"]);
	$t=$_GET["t"];
	$user=$_GET["user"];
	$md5=md5("MEMBERS:$from$to$interval$user$search");
	$_SESSION["SQUID_STATS_DATE1"]=$_GET["date1"];
	$_SESSION["SQUID_STATS_TIME1"]=$_GET["time1"];

	$_SESSION["SQUID_STATS_DATE2"]=$_GET["date2"];
	$_SESSION["SQUID_STATS_TIME2"]=$_GET["time2"];
	
	$timetext1=$tpl->time_to_date(strtotime("{$_GET["date1"]} {$_GET["time1"]}"),true);
	$timetext2=$tpl->time_to_date(strtotime("{$_GET["date2"]} {$_GET["time2"]}"),true);


	$nextFunction="LoadAjax('table-$t','$page?table1=yes&zmd5=$md5');";
	$nextFunction_encoded=urlencode(base64_encode($nextFunction));
	$q=new mysql_squid_builder();
	$q->CheckReportTable();

	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ID,builded FROM reports_cache WHERE `zmd5`='$md5'"));
	if(intval($ligne["ID"])==0){
		$array["FROM"]=$from;
		$array["TO"]=$to;
		$array["INTERVAL"]=$interval;
		$array["USER"]=$user;
		$array["SEARCH"]=$search;
		$serialize=mysql_escape_string2(serialize($array));
		$title="{members}: $timetext1 - $timetext2 - $user/$search";
		$sql="INSERT IGNORE INTO `reports_cache` (`zmd5`,`title`,`report_type`,`zDate`,`params`) VALUES
		('$md5','$title','MEMBERS',NOW(),'$serialize')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "alert('". $tpl->javascript_parse_text($q->mysql_errror)."')";return;}
		echo "Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded')";
		return;
	}

	if(intval($ligne["builded"]==0)){
	echo "
		function Start$t(){
		Loadjs('squid.statistics.progress.php?zmd5=$md5&NextFunction=$nextFunction_encoded&t=$t');
	}

	if(document.getElementById('graph-$t')){
	document.getElementById('graph-$t').innerHTML='<center><img src=img/loader-big.gif></center>';
	}
	LockPage();
	setTimeout('Start$t()',800);
	";
	return;
	}
	
	echo $nextFunction;

}
	

	
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=null;
	
	
	echo "<div style='float:right;margin:5px;margin-top:5px'>".button($tpl->_ENGINE_parse_body("{build_the_query}"), "Loadjs('$page?requeteur-js=yes&t=$t')",16)."</div>";
	
	
	$content="<center style='margin:50px'>". button("{build_the_query}","Loadjs('$page?requeteur-js=yes&t=$t')",42)."</center>";
	
	
	
	$q=new mysql_squid_builder();
	$q->CheckReportTable();
	if($_GET["zmd5"]==null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE report_type='MEMBERS' ORDER BY zDate DESC LIMIT 0,1"));
		if(!$q->ok){echo $q->mysql_error_html();}
	}else{
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT title,zmd5 FROM reports_cache WHERE zmd5='{$_GET["zmd5"]}'"));
	
	}
	
	
	
	if($ligne["zmd5"]<>null){
		$nextFunction="LoadAjax('table-$t','$page?table1=yes&zmd5={$ligne["zmd5"]}');";
		$title="<div style='font-size:30px;margin-bottom:20px'>".$tpl->javascript_parse_text($ligne["title"])."</div>";
		$content="<center><img src=img/loader-big.gif></center>";
	}
	
	
	$html="$title<div style='width:99%;margin-bottom:10px' id='table-$t'>$content</div>	

	
	
<script>
	LoadAjaxTiny('stats-requeteur','$page?stats-requeteur=yes&t=$t');
	$nextFunction
</script>";
	
echo $tpl->_ENGINE_parse_body($html);
		
}


function table1(){
	$page=CurrentPageName();
	$tpl=new templates();

	
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	if($zmd5==null){echo "alert('no key sended');UnlockPage();";die();}
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `params`,`values` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	if(strlen($values)==0){echo "alert('NO data...{$ligne["values"]}');";$q->QUERY_SQL("DELETE FROM reports_cache WHERE `zmd5`='$zmd5'");return;}
	$MAIN=unserialize(base64_decode($values));
	$params=unserialize($ligne["params"]);
	$influx=new influx();
	$from=$params["FROM"];
	$to=$params["TO"];
	$interval=$params["INTERVAL"];
	$USER_FIELD=$params["USER"];
	$search=$params["SEARCH"];
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	$color=null;				
	while (list ($USER, $size) = each ($MAIN) ){
		if(!is_numeric($size)){continue;}
		if($color==null){$color="#F2F0F1";}else{$color=null;}
		$size=FormatBytes($size/1024);
		
		$js="Loadjs('squid.statistics.report.member.php?from-zmd5=$zmd5&USER_DATA=".urlencode($USER)."');";
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:26px;text-decoration:underline'>";
		
		$html[]="<tr style='background-color:$color'>";
		$html[]="<td style='font-size:26px;width:600px;padding:10px;font-weight:bold'>$href{$USER}</a></td>";
		$html[]="<td style='font-size:26px;width:5%;text-align:right;padding:10px' nowrap>{$size}</td>";
		$html[]="</tr>";
	}
	$html[]="</table>";
	$html[]="</div>
	<script>
	UnlockPage();
	</script>";

	echo $tpl->_ENGINE_parse_body(@implode("\n", $html));
}
