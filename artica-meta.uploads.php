<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.mysql-meta.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.system.nics.inc');


$users=new usersMenus();
if(!$users->AsArticaMetaAdmin){
	$tpl=new templates();
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");die();

}
if(isset($_GET["table"])){table();exit;}
if(isset($_GET["list"])){showlist();exit;}
tabs();
function tabs(){

	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$array["hour"]='{this_hour}';
	$array["day"]='{this_day}';
	$array["month"]='{this_month}';
	
	while (list ($num, $ligne) = each ($array) ){

		if($num=="hosts"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.hosts.php\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?table=yes&type=$num&uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}

	echo build_artica_tabs($html, "meta-start-uploads");

}

function table(){
	$tpl=new templates();
	$page=CurrentPageName();

	$zdate=$tpl->javascript_parse_text("{time}");
	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$mac=$tpl->javascript_parse_text("{MAC}");
	$hostname=$tpl->javascript_parse_text("{hostname}");
	$hits=$tpl->javascript_parse_text("{files}");
	$size=$tpl->javascript_parse_text("{size}");
	$tag=$tpl->javascript_parse_text("{tag}");

	$t=time();
	$title=$tpl->javascript_parse_text("{proxys_clients_and_uploaded_files}");
	$html="
	<table class='flexRT$t' style='display:none' id='flexRT$t'></table>
	<script>
	function StartLogsSquidTable$t(){

	$('#flexRT$t').flexigrid({
	url: '$page?list=yes&type={$_GET["type"]}',
	dataType: 'json',
	colModel : [
	{display: '$hostname', name : 'hostname', width : 436, sortable : false, align: 'left'},
	{display: '$tag', name : 'tag', width : 436, sortable : true, false: 'left'},
	{display: 'UPLOAD $hits', name : 'hits', width : 142, sortable : false, align: 'right'},
	{display: 'UPLOAD $size', name : 'size', width : 142, sortable : false, align: 'right'},
	],


	sortname: 'size',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500,1000,1500]

});

}

StartLogsSquidTable$t();
</script>
";
echo $html;
}
function showlist(){
$page=1;
$q=new mysql_meta();
$type=$_GET["type"];
if($type=="hour"){
	$tablename="metastats_size_".date("YmdH");
	$table="(SELECT COUNT(zmd5) as hits,SUM(size) as size,uuid FROM `$tablename` GROUP BY `uuid` ) as t";
}
if($type=="day"){
	$tablename="metastats_sized_".date("Ymd");
	$table="(SELECT SUM(hits) as hits,SUM(size) as size,uuid FROM `$tablename` GROUP BY `uuid` ) as t";
}




	


	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}


	$searchstring=string_to_flexquery();
	if($searchstring<>null){
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$total = $ligne["TCOUNT"];

	}else{
	$sql="SELECT COUNT(*) as TCOUNT FROM $table";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$total = $ligne["TCOUNT"];
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}


	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";



	$sql="SELECT *  FROM $table WHERE 1 $searchstring $ORDER $limitSql";

	if(isset($_GET["verbose"])){echo "<hr><code>$sql</code></hr>";}
	$results = $q->QUERY_SQL($sql);

	if(!$q->ok){json_error_show($q->mysql_error,0);}

	if(mysql_num_rows($results)==0){
	if(!$q->TABLE_EXISTS($table)){$add=" no table!";}
		json_error_show("no data $add <i>$sql</i>",1);
	}

	$data = array();
	$data['page'] = 1;
	$data['total'] = mysql_num_rows($results);
	$data['rows'] = array();


	while ($ligne = mysql_fetch_assoc($results)) {
		$ipaddr=$ligne["ipaddr"];
		$uuid=$ligne["uuid"];
		$hostname=$q->uuid_to_host($uuid);
		$tag=$q->uuid_to_tag($uuid);
		
		$size=FormatBytes($ligne["size"]/1024);
		$hits=FormatNumber($ligne["hits"]);

		$ipaddr_enc=urlencode($ipaddr);
		$loupe_mac="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.access.log.php?js=yes&SearchString=$ipaddr&data=&minsize=1')\">
		<img src='img/loupe-32.png' style='float:right'>
		</a>";


		$data['rows'][] = array(
		'id' => md5(serialize($ligne)),
		'cell' => array("<span style='font-size:18px'>$hostname</span>",
				"<span style='font-size:18px'>$tag</a></span>",
				"<span style='font-size:18px'>$hits</a></span>",
				"<span style='font-size:18px'>$size</span>",
		)
		);
	}


				echo json_encode($data);
	}

	function GetSizeFrom($uuid){

		$month=date("Ym");
		$Hour=date("YmdH");
		$tablemonth="{$month}_Mstatsuapp";
		$tableHour="{$Hour}_statsuapp";

		$q=new mysql_squid_builder();

		$sql="SELECT SUM(size) as size, SUM(hits) as hits FROM $tablemonth WHERE uuid='$uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		if(intval($ligne["hits"]>0)){
		return array($ligne["hits"],$ligne["size"]);
		}

		$sql="SELECT SUM(filesize) as size, COUNT(filename) as hits FROM $tableHour WHERE uuid='$uuid'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		return array($ligne["hits"],$ligne["size"]);



		}
function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
			$tmp1 = $tmp2;
			return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
		}
