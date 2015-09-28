<?php
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["RESTART"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["WRITELOGS"]=false;
$GLOBALS["TITLENAME"]="URLfilterDB daemon";
include_once(dirname(__FILE__)."/ressources/class.access-log.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

parse($argv[1]);

function build_progress($pourc,$text){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ufdbcat.check.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}



function parse($filename){
	$unix=new unix();
	$LastScannLine=0;
	$GLOBALS["MYSQL_CATZ"]=new mysql_catz();
	$GLOBALS["SQUID_FAMILY_CLASS"]=new squid_familysite();
	if(!isset($GLOBALS["MYHOSTNAME"])){$unix=new unix();$GLOBALS["MYHOSTNAME"]=$unix->hostname_g();}
	$filesource=dirname(__FILE__)."/ressources/conf/upload/$filename";

	if(!is_file($filesource)){
		build_progress(110,"$filename no such file");
		return;
	}
	
	$tmpfile=$unix->FILE_TEMP();
	if(!@copy($filesource, $tmpfile)){
		@unlink($filesource);
		build_progress(110,"$filename -> $tmpfile {failed}");
		return;
	}
	
	@unlink($filesource);
	$SumOflines=$unix->COUNT_LINES_OF_FILE($tmpfile);
	
	echo "$tmpfile $SumOflines lines\n";
	
	$handle = @fopen($tmpfile, "r");
	if (!$handle) {
		echo "Fopen failed on $tmpfile\n";
		build_progress(110,"$tmpfile {failed}");
		@unlink($tmpfile);
		return false;
	}
	
	$c=0;$d=0;$e=0;$prc=0;$prc_text=0;$mysql_first_time=0;
	
	$SITES=array();
	$NOT_CATEGORIZED=array();
	$CATEGORIZED=array();
	$RQS=array();
	$IPClass=new IP();
	$FIRSTTIME=0;
	$LASTTIME=0;
	$TIME1=time();
	while (!feof($handle)){
			$c++;$d++;$e++;
			$prc=$c/$SumOflines;
			$prc=round($prc*100);
			$buffer =trim(fgets($handle));
			if($buffer==null){continue;}
			$stats_sites=count($SITES);
			$stats_categorized=count($CATEGORIZED);
			$stats_not_categorized=count($NOT_CATEGORIZED);
			
			if(!isset($GLOBALS["LAST_PRC"])){
				build_progress($prc,"$c/$SumOflines {please_wait}");
				$GLOBALS["LAST_PRC"]=$prc;
			}
			
					
			if($prc>5){
				if($prc<95){
					if($GLOBALS["LAST_PRC"]<>$prc){
						$array_load=sys_getloadavg();
						$internal_load=$array_load[0];
						$mem=round(((memory_get_usage()/1024)/1000),2);
						echo "Load: $internal_load, Memory {$mem}MB\n";
						echo "Categorized: ".FormatNumber($stats_categorized)."\n";
						echo "Unknown....: ".FormatNumber($stats_not_categorized)."\n";
						echo "Web sites..: ".FormatNumber($stats_sites)."\n";
						build_progress($prc,FormatNumber($c)."/".FormatNumber($SumOflines)." {please_wait} - {$mem}MB {memory}");$GLOBALS["LAST_PRC"]=$prc;
					}
				}
				
			}


	
			
			$array=parseAccessLine($buffer);
			if(count($array)==0){continue;}
			
	
			$TIME=$array["TIME"];
			$LASTTIME=$TIME;
			if($FIRSTTIME==0){$FIRSTTIME=$TIME;}
			$CATEGORY=$array["CATEGORY"];
			$FAMILYSITE=$array["FAMILYSITE"];
			$SIZE=intval($array["SIZE"]);
			
			if($IPClass->isIPAddress($FAMILYSITE)){
				if(!isset($IPADDRESSES[$FAMILYSITE]["RQS"])){
					$IPADDRESSES[$FAMILYSITE]["RQS"]=1;
					$IPADDRESSES[$FAMILYSITE]["SIZE"]=0;
					$IPADDRESSES[$FAMILYSITE]["CATEGORY"]=$CATEGORY;
				}else{
					$IPADDRESSES[$FAMILYSITE]["RQS"]=$IPADDRESSES[$FAMILYSITE]["RQS"]+1;
					$IPADDRESSES[$FAMILYSITE]["SIZE"]=$IPADDRESSES[$FAMILYSITE]["SIZE"]+$SIZE;
					
				}
				
				continue;
			}
			
			
			
			if(!isset($SITES[$FAMILYSITE])){$SITES[$FAMILYSITE]=0;}
			if(!isset($RQS[$FAMILYSITE])){$RQS[$FAMILYSITE]=0;}
			$SITES[$FAMILYSITE]=$SITES[$FAMILYSITE]+$SIZE;
			$RQS[$FAMILYSITE]=$RQS[$FAMILYSITE]+1;
			
			if($CATEGORY<>null){
				$CATEGORIZED[$FAMILYSITE]=$CATEGORY;
				continue;
			}
			$NOT_CATEGORIZED[$FAMILYSITE]=true;

			
			
			
		}
		fclose($handle);
		@unlink($tmpfile);
		build_progress(91,"{building_report}");
	
		$TIME2=time();
		$stats_sites=count($SITES);
		$stats_categorized=count($CATEGORIZED);
		$stats_not_categorized=count($NOT_CATEGORIZED);
	
		$ARRAY["DURATION"]=$unix->distanceOfTimeInWords($TIME1,$TIME2);
		$ARRAY["SumOflines"]=$SumOflines;
		$ARRAY["stats_sites"]=$stats_sites;
		$ARRAY["stats_ip"]=count($IPADDRESSES);
		$ARRAY["firsttime"]=$FIRSTTIME;
		$ARRAY["lasttime"]=$LASTTIME;
		
		
		$ARRAY["stats_categorized"]=$stats_categorized;
		$ARRAY["stats_not_categorized"]=$stats_not_categorized;
	
		build_progress(92,"{building_report}");
		$CSV1[]=array("website","size","requests");
		while (list ($familysite, $ligne) = each ($NOT_CATEGORIZED) ){
			$CSV1[]=array($familysite,$SITES[$familysite],$RQS[$familysite]);
		}
		
		build_progress(95,"{building_report}");
		$CSV2[]=array("website","category","size","requests");
		while (list ($familysite, $category) = each ($CATEGORIZED) ){
			$CSV2[]=array($familysite,$category,$SITES[$familysite],$RQS[$familysite]);
		}
		build_progress(97,"{building_report}");
		$CSV3[]=array("Public IP addresses","category","size","requests");
		while (list ($ip, $ARRAYIPS) = each ($IPADDRESSES) ){
			$category=$ARRAYIPS["CATEGORY"];
			$size=$ARRAYIPS["SIZE"];
			$RQS=$ARRAYIPS["RQS"];
			$CSV3[]=array($ip,$category,$size,$RQS);
		}		
		
		
		build_progress(99,"{saving_reports}");
		outputCSV($CSV1,"/usr/share/artica-postfix/ressources/logs/notcategorized.csv");
		outputCSV($CSV2,"/usr/share/artica-postfix/ressources/logs/categorized.csv");
		outputCSV($CSV3,"/usr/share/artica-postfix/ressources/logs/ipcategorized.csv");
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/categorized.array", serialize($ARRAY));
		@chmod("/usr/share/artica-postfix/ressources/logs/notcategorized.csv", 0755);
		@chmod("/usr/share/artica-postfix/ressources/logs/ipcategorized.csv", 0755);
		@chmod("/usr/share/artica-postfix/ressources/logs/categorized.csv", 0755);
		@chmod("/usr/share/artica-postfix/ressources/logs/categorized.array", 0755);
		build_progress(100,"{done}");
		
}

function outputCSV($data,$filename) {
	if(is_file($filename)){@unlink($filename);}
	$fp = fopen("$filename", 'w');

	foreach ($data as $row) {
		fputcsv($fp, $row); // here you can change delimiter/enclosure
	}
	fclose($fp);
}

function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){$tmp1 = round((float) $number, $decimals); while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)$tmp1 = $tmp2; return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));}