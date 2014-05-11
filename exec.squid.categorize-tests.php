<?php
	include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__) . '/ressources/class.artica.inc');
	include_once(dirname(__FILE__) . '/ressources/class.rtmm.tools.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	include_once(dirname(__FILE__) . '/ressources/class.dansguardian.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
	include_once(dirname(__FILE__) . '/framework/class.unix.inc');
	include_once(dirname(__FILE__) . '/framework/frame.class.inc');	
	include_once(dirname(__FILE__) . "/ressources/class.categorize.externals.inc");
	include_once(dirname(__FILE__) . "/ressources/class.squid.categorize.generic.inc");
	
	if(is_array($argv)){
		if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
		if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
		if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
		if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
		if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
		if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
		if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	}

	if($argv[1]=="--file-import"){file_import($argv[2]);die();}
	if($argv[1]=="--web-import"){web_import();die();}
	if($argv[1]=="--file-export"){file_export();die();}
	if($argv[1]=="--dbtrans"){dansguardian_community_nocat();die();}
	if($argv[1]=="--import-artica-cloud"){import_categories_cloud();die();}
	if($argv[1]=="--analyze"){GetPageInfos($argv[2]);die();}
	if($argv[1]=="--recat"){bright($argv[2]);die();}


	$unix=new unix();
	$mef=basename(__FILE__);
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$oldpid=@file_get_contents($pidfile);
	if($unix->process_exists($oldpid,$mef)){echo "Starting......: ".date("H:i:s")." Process Already exist pid $oldpid line:".__LINE__."\n";die();}	
	@file_put_contents($pidfile, getmypid());	
	
	
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$sql="DELETE FROM webtests WHERE LENGTH(sitename)=0";
	$results=$q->QUERY_SQL("$sql");
	
	$sql="DELETE FROM webtests WHERE sitename='.'";
	$results=$q->QUERY_SQL("$sql");	
	if($GLOBALS["OUTPUT"]){echo "SELECT sitename FROM webtests WHERE checked=0 ORDER BY sitename LIMIT 0,5000\n";}
	$sql="SELECT sitename FROM webtests WHERE checked=0 ORDER BY sitename LIMIT 0,5000";
	$results=$q->QUERY_SQL("$sql");
	if($GLOBALS["OUTPUT"]){echo mysql_num_rows($results)." items\n";}
	writelogs(mysql_num_rows($results)." items for $sql",__FUNCTION__,__FILE__,__LINE__);
	$IpClass=new IP();
	while ($ligne = mysql_fetch_assoc($results)) {
		$forcedelete=false;
		$www=$ligne["sitename"];
		if(strpos($www, ",")>0){$forcedelete=true;}
		if(strpos($www, " ")>0){$forcedelete=true;}
		if(strpos($www, ":")>0){$forcedelete=true;}
		if(strpos($www, "%")>0){$forcedelete=true;}
		if(strpos(" $www", "*")>0){$forcedelete=true;}
		
		if($forcedelete){
			if($GLOBALS["OUTPUT"]){echo "$www -> DELETE\n";}
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
			continue;
		}
		
		$articacats=null;
		$ligne["sitename"]=trim(strtolower($ligne["sitename"]));
		
		if(preg_match("#^www\.(.+)#", $www,$re)){
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
			$www=$re[1];
			$ligne["sitename"]=$www;
			$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) ('{$re[1]}')");
		}
		$delete=false;
		
		if($GLOBALS["OUTPUT"]){echo "$www -> SCANNING\n";}
		
		if($IpClass->isIPAddress($ligne["sitename"])){
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='{$ligne["sitename"]}'");
			$ligne["sitename"]=gethostbyaddr($ligne["sitename"]);
		}
		
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		if($familysite==$ligne["sitename"]){$familysite=null;}
		if($GLOBALS["OUTPUT"]){echo "$www -> STAMP\n";}
		$q->QUERY_SQL("UPDATE webtests SET checked=1 WHERE sitename='{$ligne["sitename"]}'");
		
		if($GLOBALS["OUTPUT"]){echo "$www -> GET_CATEGORIES\n";}
		$articacats=trim($q->GET_CATEGORIES($ligne["sitename"],true,false));
		if($GLOBALS["OUTPUT"]){echo "{$ligne["sitename"]} -> \"$articacats\"\n";}
		
		
		if($articacats<>null){
			$q->categorize($ligne["sitename"], $articacats);
			continue;
		}	
		
		if($familysite<>null){
			$articacats=trim($q->GET_CATEGORIES($familysite,true,false));
			if($GLOBALS["OUTPUT"]){echo "{$ligne["sitename"]} $familysite -> $articacats\n";}
			if($articacats<>null){
				$q->categorize($ligne["sitename"], $articacats);
				continue;
			}
		}


		
		
		$ipaddr=gethostbyname($ligne["sitename"]);
		if(!$IpClass->isIPAddress($ipaddr)){
			$q->categorize_reaffected($ligne["sitename"]);
			if($GLOBALS["OUTPUT"]){echo "{$ligne["sitename"]} -> Reaffected\n";}
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='{$ligne["sitename"]}'");
			continue;			
		}		
		
		if($GLOBALS["OUTPUT"]){echo "{$ligne["sitename"]} No category\n";}
		$already[$ligne["sitename"]]=true;
		
		$familysite=$q->GetFamilySites($ligne["sitename"]);
		$GetPageInfos=addslashes(GetPageInfos($ligne["sitename"]));
		$sql="UPDATE webtests SET ipaddr='$ipaddr',family='$familysite' WHERE sitename='{$ligne["sitename"]}'";
		$q->QUERY_SQL($sql);
		
		
	}
	
function bright(){
	$q=new mysql_squid_builder();
	$sql="SELECT sitename FROM webtests WHERE checked=0 ORDER BY sitename";
	$results=$q->QUERY_SQL("$sql");
	
	writelogs(mysql_num_rows($results)." items for $sql",__FUNCTION__,__FILE__,__LINE__);
	$heristic=new generic_categorize();
	while ($ligne = mysql_fetch_assoc($results)) {
		$forcedelete=false;
		$www=$ligne["sitename"];
		if(strpos($www, ",")>0){$forcedelete=true;}
		if(strpos($www, " ")>0){$forcedelete=true;}
		if(strpos($www, ":")>0){$forcedelete=true;}
		if(strpos($www, "%")>0){$forcedelete=true;}
	
		if($forcedelete){$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");continue;}
		$articacats=null;
	
		$ligne["sitename"]=trim(strtolower($ligne["sitename"]));
		
		$IPADDR=gethostbyname($ligne["sitename"]);
		if($IPADDR==$ligne["sitename"]){
			$q->categorize_reaffected($ligne["sitename"]);
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
			continue;
		}
		
		
		
		
		if(preg_match("#^www\.(.+)#", $www,$re)){
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='$www'");
			$www=$re[1];
			$ligne["sitename"]=$www;
			$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) ('{$re[1]}')");
		}
		$delete=false;
		writelogs("CHECK: {$ligne["sitename"]}",__FUNCTION__,__FILE__,__LINE__);
	
		$q->QUERY_SQL("UPDATE webtests SET checked=1 WHERE sitename='{$ligne["sitename"]}'");
		
		$category=$heristic->GetCategories($ligne["sitename"]);
		if($category<>null){
			echo "{$ligne["sitename"]} -> $category\n";
			writelogs("SUCCESS: {$ligne["sitename"]} `$category` parse next",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='{$ligne["sitename"]}'");
			$q->ADD_CATEGORYZED_WEBSITE($ligne["sitename"], $category);
			continue;
		}
		
		$f=new external_categorize($ligne["sitename"]);
		
		$category=$f->K9();
		
		if($category<>null){
			echo "{$ligne["sitename"]} -> $category\n";
			writelogs("SUCCESS: {$ligne["sitename"]} `$category` parse next",__FUNCTION__,__FILE__,__LINE__);
			$q->QUERY_SQL("DELETE FROM webtests WHERE sitename='{$ligne["sitename"]}'");
			$q->ADD_CATEGORYZED_WEBSITE($ligne["sitename"], $category);
			continue;
		}		
		
		
	}
	
	
}	
	
	
function GetPageInfos($sitename){
	include_once(dirname(__FILE__)."/ressources/class.html2text.inc");
	if(strpos(" $sitename", "http")==0){$sitename="http://$sitename";}
	echo "$sitename\n";
	$curl=new ccurl("$sitename");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "$sitename -> error: \n".$curl->error."\n";return;}
	$datas=explode("\n",$curl->data);
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#^Location:\s+(.+)#", $ligne,$re)){return GetPageInfos($re[1]);}
		
	}
	
	if(preg_match("#<head>(.*?)</head>#is", $curl->data,$re)){$HEAD=utf8_encode($re[1]);}
	if(preg_match("#<body.*?>(.*?)</body>#is", $curl->data,$re)){$BODY=utf8_encode($re[1]);}
	if(preg_match("#<title>(.*?)</title>#is", $HEAD,$re)){$TITLE=$re[1];}
	if(preg_match_all("#http.*?:\/\/(.+?)[\/\s\"]+#is",$curl->data,$re)){
		while (list ($num, $ligne) = each ($re[1]) ){
			if(preg_match("#^www\.(.*)#",$ligne, $Z)){$ligne=$Z[1];}
			$SUBSITES[$ligne]=true;}
		while (list ($num, $ligne) = each (	$SUBSITES)){
			$sitenames[]=$num;
		}
		
	}
	
	
	if(preg_match("#<meta name=.*?description.*?content=(.+?)>#is",$HEAD,$re)){
		$re[1]=str_replace('"', "", $re[1]);
		$array["DESCRIPTION"]=$re[1];
		
	}
	if(preg_match("#<meta name=.*?keywords.*?content=(.+?)>#is",$HEAD,$re)){
		$re[1]=str_replace('"', "", $re[1]);
		$array["KEYWORDS"]=$re[1];
	}	
	
	$array["TITLE"]=$TITLE;
	
	while (list ($num, $ligne) = each (	$sitenames)){
		$array["SITENAMES"][$ligne]=$ligne;
	}
	
	
	// http://www.chuggnutt.com/html2text
	$h2t =new html2text($BODY);
	$text = $h2t->get_text();
	while (list ($num, $ligne) = each (	$h2t->_link_array)){if(trim($ligne)==null){continue;}$array["URIS"][]=$ligne;}
	
	
	$array["TEXT"]=$h2t->_TEXT;
	
	include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
	$whois = new Whois();	
	$array["WHOIS"] = $whois->Lookup($sitename);	
	return unserialize($array);
}	
	
	
function file_import($filename){
	
	$f=file($filename);
	
	while (list ($num, $ligne) = each ($f) ){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		if(preg_match("#www\.(.+?)#", $ligne,$re)){$ligne=$re[1];}
		if(!preg_match("#\..+?$#", $ligne)){continue;}
		$ligne=mysql_escape_string2($ligne);
		$tt[]="('$ligne')";
	}
	
	$q=new mysql_squid_builder();
	echo "SQL --> ".count($tt)."\n";
	$before=$q->COUNT_ROWS("webtests");
	$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) VALUES ".@implode(",", $tt));
	if(!$q->ok){echo $q->mysql_error."\n";}
	$after=$q->COUNT_ROWS("webtests");
	$sum=$after-$before;
	echo "SQL --> $sum added items\n";
	
}	
	
function file_export(){
	$sql="SELECT sitename FROM webtests ORDER BY sitename";
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("$sql");
	if(!$q->ok){echo $q->mysql_error."\n";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$articacats=null;
		$tt[]=$ligne["sitename"];
	}

	@file_put_contents("/root/exported.txt", @implode("\n",$tt));
}

function web_import(){
	$curl=new ccurl("http://www.articatech.net/shalla-orders.php");
	$curl->parms["COMMUNITY_POST_CATEGORIZED"]=true;
	if(!$curl->get()){echo $curl->error."\n";return;}
	if(!preg_match("#<DATAS>(.*)</DATAS>#is", $curl->data,$rz)){echo "No preg_match\n\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize($dd);
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}

	$prefix="INSERT IGNORE INTO webtests (sitename) VALUES ";
	
	while (list ($num, $ligne) = each ($datas) ){
		if(preg_match("#www\.(.+?)#", $num,$re)){$num=$re[1];}
		$f[]="('$num')";
		
	}
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($prefix.@implode(",", $f));
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	echo count($datas)." entries done\n";
}



function dansguardian_community_nocat(){
	
	$sql="SELECT * FROM dansguardian_community_nocat";
	$q=new mysql();
	$q2=new mysql_squid_builder();
	$results=$q->QUERY_SQL($sql,"articafr");
	$prefix="INSERT IGNORE INTO webtests (sitename) VALUES ";
	
	$count1=$q2->COUNT_ROWS("webtests");
	while ($ligne = mysql_fetch_assoc($results)) {
		if(preg_match("#www\.(.+?)#", $ligne["sitename"],$re)){$ligne["sitename"]=$re[1];}
		$f[]="('{$ligne["sitename"]}')";
		
		if(count($f)>5000){
			$q2->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
		}
		
	}
	
	
if(count($f)>0){
			$q2->QUERY_SQL($prefix.@implode(",", $f));
			$f=array();
		}

$count2=$q2->COUNT_ROWS("webtests");	
$sum=$count2-$count1;	
	echo "$sum added\n";
	
}

function import_categories_cloud(){
	$curl=new ccurl("http://www.articatech.net/categories.manage.php?ExportCLNoCats=yes");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "http://www.articatech.net/categories.manage.php -> error: \n".$curl->error."\n";return;}
	if(!preg_match("#<CATZ>(.*)</CATZ>#is", $curl->data,$rz)){echo "No preg_match\n$curl->data\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize(base64_decode($dd));
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}
	$q=new mysql_squid_builder();
	$q->CheckTables();
	$qA=array();
	while (list ($md5, $www) = each ($datas) ){	
		$www=trim(strtolower($www));
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){continue;}
		if(strpos($www, ",")>0){continue;}
		if(strpos($www, " ")>0){continue;}
		if(strpos($www, ":")>0){continue;}
		if(strpos($www, "%")>0){continue;}	
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){continue;}	
		$sitefam=$q->GetFamilySites($www);
		$ipaddr=gethostbyname($www);
		if($GLOBALS["VERBOSE"]){echo "'$www','$ipaddr','$sitefam'\n";}
		$qA[]="('$www','$ipaddr','$sitefam')";
	}
	
	if(count($qA)>0){
		$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename,ipaddr,family) VALUES ".@implode(",", $qA));
		if(!$q->ok){
			if(strpos($q->mysql_error, "doesn't exist")>0){$q->CheckTables();}
			$q->QUERY_SQL("INSERT IGNORE INTO webtests (sitename) VALUES ".@implode(",", $qA));
			if(!$q->ok){echo $q->mysql_error;}
		}
	}
		
	
}

