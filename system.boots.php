<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.squid.inc');
include_once('ressources/class.ActiveDirectory.inc');
include_once('ressources/class.external.ldap.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}

if(isset($_GET["sys_alerts-js"])){table_sysalert_js();exit;}
if(isset($_GET["sysalert"])){table_sysalert();exit;}
if(isset($_GET["sysalert-search"])){search_sysalert();exit;}

if(isset($_GET["search"])){search();exit;}

table();



function table_sysalert_js(){
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=$_GET["md5"];
	$title=$tpl->javascript_parse_text("{load_alerts}");
	echo "YahooWin3('990','$page?sysalert=yes&md5=".urlencode($md5)."','$title')";
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}
function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$explain_section=$tpl->_ENGINE_parse_body("{routes_center_explain}");
	$t=time();
	$type=$tpl->_ENGINE_parse_body("{type}");
	$gateway=$tpl->_ENGINE_parse_body("{gateway}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$order=$tpl->javascript_parse_text("{order}");
	$title=$tpl->javascript_parse_text("{boots}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$date=$tpl->_ENGINE_parse_body("{date}");
	$subject=$tpl->_ENGINE_parse_body("{infos}");
	
	$q=new mysql();
	$sys_alerts=FormatNumber($q->COUNT_ROWS("last_boot", "artica_events"));

	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";

	$buttons="
	buttons : [
	{name: '$new_route', bclass: 'add', onpress : Add$t},
	{name: '$test_a_route', bclass: 'Search', onpress : TestRoute$t},
	{name: '$apply', bclass: 'apply', onpress : Apply$t},


	],";
	
	$buttons=null;

	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?search=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$date', name : 'zDate', width : 450, sortable : true, align: 'left'},
	{display: '$subject', name : 'subject', width : 341, sortable : true, align: 'left'},
	{display: '$ttl', name : 'load', width : 312, sortable : true, align: 'right'},
	
	],
	$buttons
	searchitems : [
	{display: '$date', name : 'zDate'},
	{display: '$subject', name : 'subject'},
	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title:: $sys_alerts',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});


function Add$t(){
Loadjs('$page?route-js=yes&zmd5=&t=$t');
}
function TestRoute$t(){
	Loadjs('$page?test-route-js=yes');
}

function Apply$t(){
	Loadjs('network.restart.php?t=$t')

}

var x_DansGuardianDelGroup= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
}

function DansGuardianDelGroup(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
		rowid=ID;
		var XHR = new XHRConnection();
		XHR.appendData('Delete-Group', ID);
		XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup);
	}
}
</script>
";
echo $html;

}

function search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_events";

	$t=$_GET["t"];
	$search='%';
	
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$table="last_boot";

	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$searchstring=string_to_flexquery();


	if($searchstring<>null){
		$search=$_POST["query"];
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=1;}
	
	
	$pageStart = ($page-1)*$rp;
	if(!is_numeric($rp)){$rp=50;}
	$limitSql = "LIMIT $pageStart, $rp";

$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
$results = $q->QUERY_SQL($sql,$database);

$data = array();
$data['page'] = $page;
$data['total'] = $total+1;
$data['rows'] = array();

if(!$q->ok){json_error_show($q->mysql_error,0);}


if(mysql_num_rows($results)==0){
	json_error_show("no data");
}

$fontsize=20;

	$tpl=new templates();
	while ($ligne = mysql_fetch_assoc($results)) {
		$color="black";
		//if($ligne["enabled"]==0){$color="#8a8a8a";}
		$style="style='font-size:{$fontsize}px;color:$color;'";
		$ms5=$ligne["zmd5"];
	
		$js="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?sys_alerts-js=yes&md5=$ms5&t=$t');\"
		style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
	
		
		$time=$ligne["ztime"];
		$time2=$ligne["ztime2"];
		$date2=$tpl->javascript_parse_text(distanceOfTimeInWords($time,time(),true));
		if($time2>0){
			$date2=$tpl->javascript_parse_text(distanceOfTimeInWords($time2,$time,true));
		}
			
		$date=$tpl->time_to_date($time,true);
		$subject=$ligne["subject"];
		$data['rows'][] = array(
		'id' => $ms5,
		'cell' => array(
			"<span $style>{$date}</a></span>",
			"<span $style>$subject</a></span>",
			"<span $style>$date2</a></span>",
			)
		);
	
	}
	echo json_encode($data);

}



function table_sysalert(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$user=$tpl->_ENGINE_parse_body("{user}");
	$pid=$tpl->_ENGINE_parse_body("{pid}");
	$cpu=$tpl->_ENGINE_parse_body("{cpu}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$version=$tpl->_ENGINE_parse_body("{version}");
	$events=$tpl->_ENGINE_parse_body("{events}");
	$memory=$tpl->_ENGINE_parse_body("{memory}");
	$daemon=$tpl->_ENGINE_parse_body("{daemon}");
	$product=$tpl->javascript_parse_text("{product}");
	$processes=$tpl->javascript_parse_text("{processes}");
	$running=$tpl->javascript_parse_text("{running}");
	$cpu_time=$tpl->javascript_parse_text("{time}");
	$empty_events_text_ask=$tpl->javascript_parse_text("{empty_events_text_ask}");
	$TB_HEIGHT=450;
	$TB_WIDTH=927;
	$TB2_WIDTH=551;
	$all=$tpl->_ENGINE_parse_body("{all}");
	$t=time();
	$extension="&uuid=".urlencode($_GET["uuid"])."&sysalert-search=yes&md5={$_GET["md5"]}";
	$title=$tpl->javascript_parse_text("{task_manager}");
	
	$buttons="
	buttons : [
	{name: 'CPU+', bclass: 'Err', onpress :  Err$t},
	{name: '$all', bclass: 'Statok', onpress :  All$t},
	
	
	
	],	";
	
	
	
	
	$html="
	<table class='events-table-$t' style='display: none' id='events-table-$t' style='width:99%'></table>
	<script>
	
	function BuildTable$t(){
	$('#events-table-$t').flexigrid({
	url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension',
	dataType: 'json',
	colModel : [
	{display: 'UID', name : 'user', width :52, sortable : false, align: 'center'},
	{display: '$pid', name : 'pid', width :52, sortable : false, align: 'right'},
	{display: '$cpu', name : 'CPU', width : 52, sortable : false, align: 'center'},
	{display: '$memory', name : 'MEM', width :52, sortable : false, align: 'center'},
	{display: 'VSZ', name : 'VSZ', width :55, sortable : false, align: 'right'},
	{display: 'RSS', name : 'RSS', width :55, sortable : false, align: 'right'},
	{display: '$cpu_time', name : 'pTIME', width :55, sortable : false, align: 'left'},
	{display: 'CMD', name : 'pcmd', width :467, sortable : false, align: 'left'},
	
	],
	$buttons
	
	searchitems : [
	{display: 'CMD', name : 'pcmd'},
	{display: '$user', name : 'user'},
	],
	sortname: 'CPU',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 500,
	showTableToggleBtn: false,
	width: '99%',
	height: $TB_HEIGHT,
	singleSelect: true,
	rpOptions: [500]
	
	});
	}
	
function Err$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension&CPU=1'}).flexReload(); 
}	
function All$t(){
	$('#events-table-$t').flexOptions({url: '$page?events-table=yes&text-filter={$_GET["text-filter"]}$extension'}).flexReload(); 
}	
	function articaShowEvent(ID){
	YahooWin6('750','$page?ShowID='+ID,'$title::'+ID);
	}

	
	setTimeout(\" BuildTable$t()\",800);
	</script>";
	
	echo $html;
	
	}

function search_sysalert(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$total=0;
	
	
	$sql="SELECT `content` FROM sys_alerts WHERE zmd5='{$_GET["md5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	$results=explode("\n",$ligne["content"]);
	
	
	if(count($results)==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	
	$searchstring=string_to_flexregex();
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=1;}
	
	
	$pageStart = ($page-1)*$rp;
	$data = array();
	$data['page'] = $page;
	$data['total'] = count($data);
	$data['rows'] = array();
	
	$CurrentPage=CurrentPageName();
	$c=0;
	while (list ($index, $line) = each ($results) ){
		
		if($searchstring<>null){
			if(!preg_match("#$searchstring#", $line)){continue;}
		}
		
		if(!preg_match("#(.*?)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+(.*?)\s+(.*?)\s+(.+?)\s+([0-9\:]+)\s+(.*)#", $line,$re)){continue;}
		$c++;
			
			$user=$re[1];
			$pid=$re[2];
			$CPU=$re[3];
			$MEM=$re[4];
			$VSZ=FormatBytes($re[5]);
			$RSS=FormatBytes($re[6]);
			$pTIME=$re[10];
			$pcmd=$re[11];
			$CPUINT=intval($CPU);
			
			if($_GET["CPU"]==1){
				if($CPUINT<2){continue;}
			}
			
			if($CPU=="0"){
				if($MEM=="0"){continue;}
			}
	
			if(strlen($CPU)==1){$CPU="$CPU.0";}
			if(strlen($MEM)==1){$MEM="$MEM.0";}
			
			$pcmd=str_replace("/usr/bin/php5 /usr/share/artica-postfix/", "", $pcmd);
			$pcmd=str_replace("/bin/sh -c /usr/bin/ionice -c2 -n7 /usr/bin/nice --adjustment=19 ", "", $pcmd);
			$pcmd=str_replace("/usr/bin/php -q /usr/share/artica-postfix/", "", $pcmd);
			$pcmd=str_replace("php5 /usr/share/artica-postfix/", "", $pcmd);
			$pcmd=str_replace("/usr/sbin/apache2 -f /etc/artica-postfix/httpd.conf -k start", "Apache (Web interface)", $pcmd);
			$pcmd=str_replace("/usr/sbin/slapd -4 -h ldapi://%2Fvar%2Frun%2Fslapd%2Fslapd.sock ldap://127.0.0.1:389/ -f /etc/ldap/slapd.conf -u root -g root -l local4", "OpenLDAP Server.. ", $pcmd);
			$pcmd=str_replace("/usr/sbin/mysqld --pid-file=/var/run/mysqld/mysqld.pid --log-error=/var/lib/mysql/mysqld.err --socket=/var/run/mysqld/mysqld.sock --datadir=/var/lib/mysql", "MySQL Server... ", $pcmd);
			$pcmd=str_replace("/usr/bin/ufdbguardd -c /etc/squid3/ufdbGuard.conf -U squid", "WebFiltering service...", $pcmd);
			$pcmd=str_replace("/usr/sbin/mysqld --defaults-file=/opt/squidsql/my.cnf --innodb=OFF --user=root --pid-file=/var/run/squid-db.pid --basedir=/opt/squidsql --datadir=/opt/squidsql/data --socket=/var/run/mysqld/squid-db.sock --general-log-file=/opt/squidsql/general_log.log --slow-query-log-file=/opt/squidsql/slow-query.log --log-error=/opt/squidsql/error.log","MySQL Statistics service... ", $pcmd);
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							$user,$pid,"{$CPU}%","{$MEM}%",$VSZ,$RSS,$pTIME,$pcmd)
							);
		}
	
		$data['total']=$c;
		echo json_encode($data);
	
	}