<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.ini.inc');
include_once('ressources/class.squid.inc');
$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){$tpl=new templates();echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."')";die();}

if(isset($_POST["DeleteAll"])){DeleteAll();exit;}
if(isset($_GET["csv-js"])){csv_js();exit;}
if(isset($_GET["csv-popup"])){csv_popup();exit;}
if(isset($_GET["popup"])){popup();exit;}
if(isset($_GET["list"])){table_list();exit;}
if(isset($_GET["csv-builder"])){csv_builder();exit;}


popup();


function csv_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `title` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$build_the_query=$tpl->javascript_parse_text("{cvs_export} {$ligne["title"]}");
	echo "YahooWin('954','$page?csv-popup=yes&zmd5=$zmd5','$build_the_query');";
}

function DeleteAll(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE reports_cache");
	if(!$q->ok){echo $q->mysql_error;}
	
	REMOVE_TABLES_CHRONOS();
	
}

function REMOVE_TABLES_CHRONOS(){
	$array=array();
	$q=new mysql_squid_builder();
	$sql="SELECT table_name as c FROM information_schema.tables WHERE table_schema = 'squidlogs'
				AND table_name LIKE 'chronos%' ORDER BY table_name";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if(preg_match("#^chronos#", $ligne["c"])){
			$q->QUERY_SQL("DROP TABLE `{$ligne["c"]}`");
		}
	}
	
}


function csv_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `title` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$html="
		<div style='font-size:30px'>{cvs_export}</div>
		<div style='font-size:18px'><i>{$ligne["title"]}</i></div>
		<center id='csv-$zmd5'></center>
		
		
		<script>
		LoadAjaxRound('csv-$zmd5','$page?csv-builder=yes&zmd5=$zmd5');
		</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function csv_builder(){
	$q=new mysql_squid_builder();
	$zmd5=$_GET["zmd5"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `params`,`values`,`report_type` FROM reports_cache WHERE `zmd5`='$zmd5'"));
	$values=$ligne["values"];
	$report_type=$ligne["report_type"];
	$MAIN=unserialize(base64_decode($values));
	$params=unserialize($ligne["params"]);
	
	echo "<center style='font-size:30px;margin-bottom:30px;margin-top:30px'>$report_type</center>
	<center style='width:95%'>
	";
	
	if($report_type=="MEMBERS"){
		$f[]=array("Member","Size Bytes");
		while (list ($USER, $size) = each ($MAIN) ){
			$f[]=array($USER,$size);
		}
		
		outputCSV($f,"ressources/logs/web/MEMBERS.csv");
		echo "<div style='float:left;margin:10px' class=form><center style='font-size:18px;margin-top:20px'>MEMBERS.csv</center>
			<center><a href='ressources/logs/web/MEMBERS.csv'><img src='img/csv-256.png'></a></center>
			</div>
				
			";
		echo "</center>";
		return;
		
	}
	
	
	if($report_type=="CATEGORIES"){
		outputCSV($MAIN["csv"],"ressources/logs/web/CATEGORIES.csv");
		echo "<div style='margin:10px' class=form><center style='font-size:18px;margin-top:20px'>CATEGORIES.csv</center>
			<center><a href='ressources/logs/web/CATEGORIES.csv'><img src='img/csv-256.png'></a></center>
			</div>
	
			";
		echo "</center>";
		return;
	}

	if($report_type=="WEBFILTERING"){
		outputCSV($MAIN["csv"],"ressources/logs/web/WEBFILTERING.csv");
		echo "<div style='margin:10px' class=form><center style='font-size:18px;margin-top:20px'>WEBFILTERING.csv</center>
			<center><a href='ressources/logs/web/WEBFILTERING.csv'><img src='img/csv-256.png'></a></center>
			</div>
	
			";
		echo "</center>";
		return;
	}	
	
	
	
	
	if($report_type=="FLOW"){
		$f=array();
		
		outputCSV($MAIN["CSV1"],"ressources/logs/web/FLOW.csv");
		echo "<div style='float:left;margin:10px' class=form><center style='font-size:18px;margin-top:20px'>FLOW.csv</center>
			<center><a href='ressources/logs/web/FLOW.csv'><img src='img/csv-256.png'></a></center></div>";
		
		
		$f=array();
		outputCSV($MAIN["CSV2"],"ressources/logs/web/MEMBERS.csv");
		echo "<div style='float:left;margin:10px' class=form><center style='font-size:18px;margin-top:20px'>MEMBERS.csv</center>
			<center><a href='ressources/logs/web/MEMBERS.csv'><img src='img/csv-256.png'></a></center></div>";
		
		echo "</center>";
		return;
		
		
	}
	
	
	if($report_type=="MEMBER_UNIQ"){
		$ROWS=$MAIN["IDENT"];
		$f[]=array("MAC","ipaddr","userid");
		
		
		while (list ($index, $array) = each ($ROWS) ){
			$IPADDR=$array["IPADDR"];
			$USERID=$array["USERID"];
			$MAC=$array["MAC"];
			$f[]=array($MAC,$IPADDR,$USERID);
		}
		
		outputCSV($f,"ressources/logs/web/IDENTITY.csv");
		echo "
			<div style='float:left;margin:10px' class=form>
			<center style='font-size:18px;margin-top:20px'>IDENTITY.csv</center>
			<center><a href='ressources/logs/web/IDENTITY.csv'><img src='img/csv-256.png'></a></center>
			</div>";
					
		
	
		$f=array();
		$ROWS=$MAIN["FAMS"];
		$f[]=array("Webiste","Size bytes");
		while (list ($website, $size) = each ($ROWS) ){
			$f[]=array($website,$size);
		}
		outputCSV($f,"ressources/logs/web/WEBSITES.csv");
		echo "
			<div style='float:left;margin:10px' class=form>	
			<center style='font-size:18px;margin-top:20px'>WEBSITES.csv</center>
			<center><a href='ressources/logs/web/WEBSITES.csv'><img src='img/csv-256.png'></a></center></div>";
			
		$f=array();
		
		outputCSV($MAIN["CSV"],"ressources/logs/web/CHRONOLOGY.csv");
		echo "
			<div style='float:left;margin:10px' class=form>
			<center style='font-size:18px;margin-top:20px'>CHRONOLOGY.csv</center>
			<center><a href='ressources/logs/web/CHRONOLOGY.csv'><img src='img/csv-256.png'></a></center></div>";
		
		$f=array();
		$xdata=$MAIN["GRAPH1"]["xdata"];
		$ydata=$MAIN["GRAPH1"]["ydata"];
		$f[]=array("Time","Size MB");
		while (list ($index, $value) = each ($xdata) ){$f[]=array($value,$ydata[$index]);}
		outputCSV($f,"ressources/logs/web/FLOW.csv");
		echo "<div style='float:left;margin:10px' class=form><center style='font-size:18px;margin-top:20px'>FLOW.csv</center>
			<center><a href='ressources/logs/web/FLOW.csv'><img src='img/csv-256.png'></a></center></div>";
		
		$f=array();
		$f[]=array("Site","Size MB");
		while (list ($index, $value) = each ($MAIN["GRAPH2"]["TABLE"]) ){$f[]=array($index,$value);}
		outputCSV($f,"ressources/logs/web/SITES_MB.csv");
		echo "<div style='float:left;margin:10px' class=form><center style='font-size:18px;margin-top:20px'>TOP_SITES.csv</center>
			<center><a href='ressources/logs/web/TOP_SITES.csv'><img src='img/csv-256.png'></a></center></div>";
		
		
		
		outputCSV($MAIN["WEBFILTERING"],"ressources/logs/web/WEBFILTERING.csv");
		echo "<div style='float:left;margin:10px' class=form>
				<center style='font-size:18px;margin-top:20px'>WEBFILTERING.csv</center>
				<center>
					<a href='ressources/logs/web/WEBFILTERING.csv'><img src='img/csv-256.png'></a>
				</center>
			</div>";
		
		
		
		echo "</center>";
		
		
		
		
		
		
		
		return;
	}
	
	
}

function outputCSV($data,$filename) {
	if(is_file($filename)){@unlink($filename);}
	$fp = fopen("$filename", 'w');

	foreach ($data as $row) {
		fputcsv($fp, $row); // here you can change delimiter/enclosure
	}
	fclose($fp);
}


function popup(){

	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$report=$tpl->_ENGINE_parse_body("{report}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->_ENGINE_parse_body("{hits}");
	$familysite=$tpl->_ENGINE_parse_body("{familysite}");
	$title=$tpl->javascript_parse_text("{browse_cache}:{$_GET["report_type"]}");
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$date=$tpl->javascript_parse_text("{date}");
	$buttons="
	 buttons : [
	{name: '<strong style=font-size:18px>$delete_all</strong>', bclass: 'Delz', onpress : DeleteAll$t},
	],";
		
	
	
	$html="
	<table class='BROWSE_STATISTICS_CACHES2' style='display: none' id='BROWSE_STATISTICS_CACHES2' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#BROWSE_STATISTICS_CACHES2').flexigrid({
	url: '$page?list=yes&report_type={$_GET["report_type"]}&t={$_GET["t"]}',
	dataType: 'json',
	colModel : [
	{display: '<span style=font-size:22px>$report</span>', name : 'title', width :867, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$date</span>', name : 'zDate', width :192, sortable : true, align: 'left'},
	{display: '<span style=font-size:22px>$size</span>', name : 'values_size', width :161, sortable : true, align: 'right'},
	{display: '<span style=font-size:22px>CSV</span>', name : 'CSV', width :94, sortable : false, align: 'center'},
	{display: '<span style=font-size:22px>DEL</span>', name : 'del', width :94, sortable : false, align: 'center'},
	],
	$buttons
	
	searchitems : [
	{display: '$report', name : 'title'},
	],

	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});

var xDeleteAll$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#BROWSE_STATISTICS_CACHES2').flexReload();
}

function DeleteAll$t(){
	if(!confirm('$delete_all ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('DeleteAll', 'yes');
	XHR.sendAndLoad('$page', 'POST',xDeleteAll$t);
}

</script>

";


	echo $tpl->_ENGINE_parse_body($html);

}


function table_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("reports_cache")){$q->CheckReportTable();}
	
	$search='%';
	if($_GET["report_type"]<>null){
		$table="(SELECT title,zmd5,values_size,report_type,zDate FROM reports_cache WHERE report_type='{$_GET["report_type"]}') as t";
	}else{
		$table="(SELECT title,zmd5,values_size,report_type,zDate FROM reports_cache) as t";
	}
	$page=1;
	
	
		
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql" );}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	
	while ($ligne = mysql_fetch_assoc($results)) {
		$zmd5=$ligne["zmd5"];
		$title=$tpl->javascript_parse_text($ligne["title"]);
		$values_size=$ligne["values_size"];
		$ahref=null;
		if($values_size>1024){
			$values_size=FormatBytes($values_size/1024);
		}else{
			$values_size="{$values_size} Bytes";
		}
	
	
		$delete=imgsimple("delete-32.png",null,"Loadjs('squid.statistics.flow.php?remove-cache-js=yes&zmd5=$zmd5')");
		$csv=imgsimple("csv-32.png",null,"Loadjs('$MyPage?csv-js=yes&zmd5=$zmd5')");
		$ligne["title"]=$tpl->javascript_parse_text($ligne["title"]);
		
		
		if($ligne["report_type"]=="MEMBER_UNIQ"){
			$ahref="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.statistics.report.member.php?zmd5=$zmd5');\"
			style='text-decoration:underline'>";
		}
		if($ligne["report_type"]=="FLOW"){
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"GoToStatsFlow('$zmd5');\"
			style='text-decoration:underline'>";
		}
		if($ligne["report_type"]=="MEMBERS"){
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"GoToStatsMembers('$zmd5');\"
			style='text-decoration:underline'>";
		}
		if($ligne["report_type"]=="WEBSITES"){
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"GoToWebsitesStats('$zmd5');\"
			style='text-decoration:underline'>";
		}
		if($ligne["report_type"]=="CATEGORIES"){
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"GoToStatisticsByCategories('$zmd5');\"
			style='text-decoration:underline'>";
		}		
		if($ligne["report_type"]=="WEBFILTERING"){
			$ahref="<a href=\"javascript:blur();\"
			OnClick=\"GoToStatisticsByWebFiltering('$zmd5');\"
			style='text-decoration:underline'>";
		}		
		
		
		
		
			$data['rows'][] = array(
					'id' => $zmd5,'cell' => array(
					"<span style='font-size:18px'>$ahref{$ligne["title"]}</a></span>",
					"<span style='font-size:18px'>$ahref{$ligne["zDate"]}</a></span>",
					
					"<span style='font-size:18px'>$values_size</a></span>",
					"<center>$csv</center>",
					"<center>$delete</center>",
					 
			)
			
			);
			
		}
	
	
		echo json_encode($data);
	
	}
	
	function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}
	