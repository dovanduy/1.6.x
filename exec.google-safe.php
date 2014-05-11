<?php
ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.categorize.generic.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.categorize.externals.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
$unix=new unix();
if(is_file("/usr/share/phpGSB-master/phpgsb.class.php")){
	include_once("/usr/share/phpGSB-master/phpgsb.class.php");
	if(!isset($GLOBALS["OVHMySQLPass"])){if(is_file("/etc/artica-postfix/settings/Daemons/OVHMySQLPass")){$GLOBALS["OVHMySQLPass"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/OVHMySQLPass");}}
	if(!isset($GLOBALS["GoogleApiKey"])){if(is_file("/etc/artica-postfix/settings/Daemons/GoogleApiKey")){$GLOBALS["GoogleApiKey"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/GoogleApiKey");}}
}

if($argv[1]=="--mysql"){tests_mysql();exit; }
if($argv[1]=="--db"){DATABASE_INFOS();exit; }

$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
$time=$unix->file_time_min($timefile);

if($time<1440){
	echo "Only each 1440mn\n";
	if(!$GLOBALS["FORCE"]){die();}
}

@unlink($timefile);
@file_put_contents($timefile, time());

$q=new mysql_squid_builder();

if(!$q->FIELD_EXISTS("webtests","SafeBrws")){
	$sql="ALTER TABLE `webtests` ADD `SafeBrws` smallint( 1 ) NOT NULL, ADD INDEX (SafeBrws)";
	$q->QUERY_SQL($sql);
}


echo "noporotolozaza.2waky.com => ".UBoxGoogleSafeBrowsingGetCurl("noporotolozaza.2waky.com")."\n";
echo "getfreefollower.com => ".UBoxGoogleSafeBrowsingGetCurl("getfreefollower.com")."\n";

//return;

echo "SELECT * FROM webtests WHERE SafeBrws=0\n";
$results= $q->QUERY_SQL("SELECT * FROM webtests WHERE SafeBrws=0");


$i=1;
while ($ligne = mysql_fetch_assoc($results)) {
	$i++;
	$sitename=trim($ligne["sitename"]);
	if($sitename==null){continue;}
	$category=UBoxGoogleSafeBrowsingGetCurl($sitename);
	if($category==null){echo "$i] $sitename -> NONE\n";
	usleep(500);
	$q->QUERY_SQL("UPDATE webtests SET SafeBrws=1 WHERE sitename='$sitename'");
	continue;}
	
	if(strpos($category, "We're sorry")){
		echo "STOP!\n";
		return;
	}
	
	echo "$i] $sitename -> $category\n";
	$q->categorize($sitename, $category);
	$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$sitename'");
}


function UBoxGoogleSafeBrowsingGetCurl($szUrl){
	
	$f=new external_categorize(null);
	return $f->UBoxGoogleSafeBrowsingPhpGsbLookup($szUrl);
	
	$API_KEY=$GLOBALS["GoogleApiKey"];
	$client = "artica";
	$appver = "1.5.2";
	$pver = "3.0";

	$szPath = "https://sb-ssl.google.com/safebrowsing/api/lookup?client=".$client.
	"&apikey=".$API_KEY.
	"&appver=".$appver.
	"&pver=".$pver.
	"&url=".$szUrl;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $szPath);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

	$data = curl_exec($ch);
	curl_close($ch);
	// phishing,malware
	return $data;
}

function tests_mysql(){
	$f=new external_categorize(null);
	echo $f->UBoxGoogleSafeBrowsingPhpGsbLookup("abu-farhan.com");
	
}
 function DATABASE_INFOS(){
 	$link = mysql_connect(":/var/run/mysqld/mysqld.sock", "root", $GLOBALS["OVHMySQLPass"]);
 	$ok=@mysql_select_db("phpGSB",$link);
	$sql="show TABLE STATUS";
	$results=@mysql_query($sql,$link);
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$dbsize += $ligne['Rows'];
		$count=$count+1;}
		echo "$dbsize\n";

}

