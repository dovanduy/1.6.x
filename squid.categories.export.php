<?php
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	if(!CategoriesCheckRightsRead()){
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}<hr>".@implode("<br>", $GLOBALS["CategoriesCheckRights"]));
		exit;
		
	}
	if(isset($_GET["download"])){download();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();


function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{export}");
	echo "RTMMail('540','$page?popup=yes&category={$_GET["category"]}','$title');";
	
	
	
}

function popup(){
	$page=CurrentPageName();
	$html="<center style='padding:50px;width:78%' class=form>
			<a href='$page?download=yes&category={$_GET["category"]}'>
			<img src='img/csv-256.png'>
			<br>
			<span style='font-size:28px;text-decoration:underline'>{$_GET["category"]}.csv</span>
			</a>
			
	</center>";
	echo $html;
	
}
	
	
	



function download(){
	$category=$_GET["category"];
	header("Content-Type: text/csv");
	header("Content-Disposition: attachment; filename=$category.csv");
	header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
	header("Pragma: no-cache"); // HTTP 1.0
	header("Expires: 0"); // Proxies
	
	
	
	$q=new mysql_squid_builder();
	$table="category_".$q->category_transform_name($category);
	
	$sql="SELECT * FROM $table ORDER BY `pattern`";
	$results = $q->QUERY_SQL($sql);
	$f[]=array("Date","Website");
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$f[]=array($ligne['zDate'],$ligne["pattern"]);
		
	}
	
	outputCSV($f);
}

function outputCSV($data) {
	$output = fopen("php://output", "w");
	foreach ($data as $row) {
		fputcsv($output, $row); // here you can change delimiter/enclosure
	}
	fclose($output);
}




