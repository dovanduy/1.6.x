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
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=="--nocatz"){nocatz();exit();}
	if($argv[1]=="--all"){catzall();exit();}
	if($argv[1]=="--router"){nocatz($argv[2]);exit();}
	if($argv[1]=="--tests"){testcatz($argv[2]);exit();}
	if($argv[1]=="--parseblogs"){parseblogs();exit();}
	
	
	
	catz();
	
function catzall(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5(__FUNCTION__).".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$timefile=$unix->file_time_min($pidfile);
		ufdbguard_admin_events("Already executed pid $oldpid since $timefile minutes.. aborting the process",__FUNCTION__,__FILE__,__LINE__,"categorize");
		return;
	}
	
	$t=time();
	
	$GLOBALS["CATEGORIZELOGS-COUNT"]=0;
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=0;
	catz();
	nocatz();
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Cloud categorized took $took {$GLOBALS["CATEGORIZELOGS-COUNTED"]} items scanned, {$GLOBALS["CATEGORIZELOGS-COUNT"]} new items categorized",__FUNCTION__,__FILE__,__LINE__,"categorize");
}	

function testcatz($sitename){
	$www=$sitename;
	$q=new mysql_squid_builder();
	$GLOBALS["BIGDEBUG"]=true;
	$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, ",")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, " ")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, ":")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, "%")>0){deleteWebsite($md5,$www,__LINE__);continue;}	
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsite($md5,$www,__LINE__);continue;}	
		$articacats=null;
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		echo "$www -> \"$articacats\"\n";	
	
}

function parseblogs(){
	$f=file("/var/log/bluealpha.log");
	$bb=new external_categorize();
	$BC=$bb->UboxBlueaCoatAlter();
	while (list ($index, $line) = each ($f) ){
		if(!preg_match("#([A-Z0-9]+)\s+\"(.+?)\"#", $line,$re)){continue;}
		if(isset($BC[$re[1]])){continue;}
		$array[$re[1]][]=$re[2];
	}
	
	print_r($array);
}
	
function catz(){	
	$GLOBALS["OUTPUT_EXTCATZ"]=true;
	$curl=new ccurl("http://www.artica.fr/categories.manage.php?ExportCLCats=yes");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "http://www.artica.fr/categories.manage.php -> error: \n".$curl->error."\n";return;}
	if(!preg_match("#<CATZ>(.*)</CATZ>#is", $curl->data,$rz)){echo "No preg_match\n$curl->data\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize(base64_decode($dd));
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}

	$q=new mysql_squid_builder();
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=$GLOBALS["CATEGORIZELOGS-COUNTED"]+count($datas);
	$max=count($datas);
	$c=0;
	while (list ($md5, $www) = each ($datas) ){
		$c++;
		if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, ",")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, " ")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, ":")>0){deleteWebsite($md5,$www,__LINE__);continue;}
		if(strpos($www, "%")>0){deleteWebsite($md5,$www,__LINE__);continue;}	
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsite($md5,$www,__LINE__);continue;}	
		$articacats=null;
		
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		//if(count($GLOBALS["CATEGORIZELOGS"])>0){
			//while (list ($a, $b) = each ($GLOBALS["CATEGORIZELOGS"]) ){echo "$md5 -> `LOG` \"$b\" by line $line [".__LINE__."]\n";}
		//}
		if($articacats<>null){
			$MEM[$www]=$articacats;
			deleteWebsite($md5,$www,__LINE__,$articacats);
			continue;
		}

		echo "$c/$max $md5 -> `SKIP` \"$www\" by line $line [".__LINE__."]\n";
		
	}
	
}

// 1636b7346f2e261c5b21abfcaef45a69
	
	
function nocatz($router=null){
	$q=new mysql();
	$q->BuildTables();
	if($router<>null){
		$curl=new ccurl("http://www.artica.fr/categories.manage.php?ExportCLNoCats=$router");
	}else{
		$curl=new ccurl("http://www.artica.fr/categories.manage.php?ExportCLNoCats=yes");
	}
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){echo "http://www.artica.fr/categories.manage.php -> error: \n".$curl->error."\n";return;}
	if(!preg_match("#<CATZ>(.*)</CATZ>#is", $curl->data,$rz)){echo "No preg_match\n$curl->data\n";return;}
	$dd=$rz[1];
	echo (strlen($dd)/1024)." Ko lenth\n";
	$datas=unserialize(base64_decode($dd));
	if(!is_array($datas)){echo "Not an array, die\n";echo $dd."\n";return;}
	$sock=new sockets();
	$uuid=base64_decode($sock->getFrameWork("cmd.php?system-unique-id=yes"));
	$q=new mysql_squid_builder();
	$GLOBALS["CATEGORIZELOGS-COUNTED"]=$GLOBALS["CATEGORIZELOGS-COUNTED"]+count($datas);
	$max=count($datas);
	$c=0;	
	$GoodCatz=0;
	while (list ($md5, $www) = each ($datas) ){	
		$c++;
	if(!preg_match("#([a-z0-9\-_\.]+)\.([a-z]+)$#i",$www,$re)){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ",")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ">")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, "<")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, " ")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, ":")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}
		if(strpos($www, "%")>0){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}	
		if(preg_match("#^www\.(.+)#", $www,$re)){$www=$re[1];}
		if(isset($MEM[$www])){deleteWebsiteNocatz($md5,$www,__LINE__);continue;}	
		$articacats=null;
		
		$articacats=trim($q->GET_CATEGORIES($www,true,false));
		//if(count($GLOBALS["CATEGORIZELOGS"])>0){
			//while (list ($a, $b) = each ($GLOBALS["CATEGORIZELOGS"]) ){echo "$md5 -> `LOG` \"$b\" by line $line [".__LINE__."]\n";}
		//}
		if($articacats<>null){
			$MEM[$www]=$articacats;
			deleteWebsiteNocatz($md5,$www,__LINE__,$articacats,"$c/$max ");
			continue;
		}
		
		$ipaddr=gethostbyname($www);
		if($ipaddr==$www){
			$q->categorize_reaffected($www);
			$GLOBALS["CATEGORIZELOGS-COUNT"]++;
			deleteWebsiteNocatz($md5,$www,__LINE__,"reaffected","$c/$max ");
			continue;			
		}

		echo "$c/$max $md5 -> `SKIP` \"$www\" ($ipaddr) by line $line [".__LINE__."]\n";
		not_categorized_add($www,$ipaddr);
	}	
	
	echo "Success {$GLOBALS["CATEGORIZELOGS-COUNT"]} categorized websites\n";
}


function not_categorized_add($www,$ipaddr){
	$country=null;
	if(function_exists("geoip_record_by_name")){
		$record = geoip_record_by_name($ipaddr);
		if ($record) {$country=$record["country_name"];$city=$record["city"];}
	}
	
	$q=new mysql_squid_builder();
	$family=$q->GetFamilySites($www);
	$country=addslashes($country);
	$date=date('Y-m-d H:i:s');
	$sql="INSERT IGNORE INTO webtests (`sitename`,`family`,`Country`,`zDate`,`ipaddr`) VALUES ('$www','$family','$country','$date','$ipaddr')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		if(preg_match("#Country#", $q->mysql_error)){
			$q->QUERY_SQL("ALTER TABLE `webtests` ADD `Country`  VARCHAR( 50 ) NOT NULL ,ADD INDEX ( `Country` )");
			$q->QUERY_SQL($sql);
			
		}
	}
	if(!$q->ok){
		
		echo getmypid()." Error $q->mysql_error\n";}
	
}
	
	
	

function deleteWebsiteNocatz($md5,$www,$line=0,$cat=null,$output){
	echo "$output$md5 -> `DELETE` \"$www\" by line $line [$cat]\n";
	$curl=new ccurl("http://www.artica.fr/categories.manage.php?killNoCatz=$md5");
	$curl->NoHTTP_POST=true;
	writelogs("deleteWebsite: $md5",__FUNCTION__,__FILE__,__LINE__);
	if(!$curl->get()){echo $curl->error."\n";return;}
	if(preg_match("#<ERROR>(.*?)</ERROR>#", $curl->data,$re)){echo "{$re[1]}\n";}
}	
	
function deleteWebsite($md5,$www,$line=0,$cat=null){
	echo "$md5 -> `DELETE` \"$www\" by line $line [$cat]\n";
	$curl=new ccurl("http://www.artica.fr/categories.manage.php?kill=$md5");
	$curl->NoHTTP_POST=true;
	writelogs("deleteWebsite: $md5",__FUNCTION__,__FILE__,__LINE__);
	if(!$curl->get()){echo $curl->error."\n";return;}
}