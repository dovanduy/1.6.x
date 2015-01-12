<?php
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["NOTIME"]=false;
$GLOBALS["TITLENAME"]="Artica statistics Dameon";
$GLOBALS["TIMEFILE"]="/var/run/squid-stats-central.run";

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--stamp=([0-9\-]+)#",implode(" ",$argv),$re)){$GLOBALS["STAMP_DONE"]=$re[1];$GLOBALS["NOTIME"]=true;}
	
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.reports.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.stats.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.syslogs.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__).'/ressources/class.artica.graphs.inc');
include_once(dirname(__FILE__).'/ressources/externals/fpdf17/fpdf.php');


if($argv[1]=="--recategorize"){recategorize_tables($argv[2]);exit;}



xrun($argv[1]);





function build_progress($report_id,$text, $pourc){
	events("{$pourc}% $text");
	$q=new mysql_squid_builder();
	echo "{$pourc}% $text Report:[$report_id]\n";
	$text=mysql_escape_string2($text);
	$q->QUERY_SQL("UPDATE squid_reports SET report_progress=$pourc, `report_progress_text`='$text' WHERE ID=$report_id");
}

function events($text){
	$pid=getmypid();
	$filename=basename(__FILE__);
	$date=date("H:i:s");
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			$function=$trace[0]["function"];
			$line=$trace[0]["line"];
		}
	
		if(isset($trace[1])){
			$file=basename($trace[1]["file"]);
			$function=$trace[1]["function"];
			$line=$trace[1]["line"];
		}
	
	
	
	}
	
	
	$logFile="/var/log/report_{$GLOBALS["REPORT_ID"]}";
	$size=filesize($logFile);
	if($size>1000000){unlink($logFile);}
	$f = @fopen($logFile, 'a');
	
	$line="$date [$function::$line] $text\n";
	if($GLOBALS["VERBOSE"]){echo $line;}
	
	@fwrite($f,$line);
	@fclose($f);
}

function xrun($report_id){
	$unix=new unix();
	$GLOBALS["REPORT_ID"]=$report_id;
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$report_id.pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$report_id.time";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}
		return;
	}
	
	$q=new mysql_squid_builder();
	$GLOBALS["REPORT_DIRECTORY"]="/home/artica-scheduled-reports/report_$report_id";
	@unlink("/var/log/report_{$GLOBALS["REPORT_ID"]}");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_type FROM `squid_reports` WHERE ID='$report_id'"));
	if(!$q->ok){events($q->mysql_error);}
	build_progress($report_id,"Starting report Type:..ID:[$report_id] / {$ligne["report_type"]}",5);
	if(!is_numeric($ligne["report_type"])){$ligne["report_type"]=2;}
	$q->QUERY_SQL("UPDATE squid_reports SET report_time_start=".time()." WHERE ID='$report_id'");
	if($ligne["report_type"]==1){
		build_progress($report_id,"[$report_id]: Starting report Type:..By category",5);
		GENERATE_report_by_category($report_id);
	}
	if($ligne["report_type"]==2){
		build_progress($report_id,"[$report_id]: Starting report Type:..By Websites",5);
		GENERATE_report_by_websites($report_id);
	}
	
	
	
	$q->QUERY_SQL("UPDATE squid_reports SET report_time_end=".time()." WHERE ID='$report_id'");
	Push_logs($report_id);
	
}


function SQL_DAYS($report_id){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$report_id'"));
	$report_days=$ligne["report_days"];
	
	if($report_days==0){
		$report_build_time_start=$ligne["report_build_time_start"];
		$report_build_time_end=$ligne["report_build_time_end"];
		$report_build_time_start=date("Y-m-d",$report_build_time_start);
		$report_build_time_end=date("Y-m-d",$report_build_time_end);
		return "(`zDate` >='$report_build_time_start' AND `zDate` <='$report_build_time_end')";
	}
	
	if($report_days==-1){
		return "(MONTH(zDate)=MONTH(NOW()) AND YEAR(zDate)=YEAR(NOW()))";
	}
	
	return "`zDate`>= DATE_SUB(NOW(),INTERVAL $report_days DAY)";
	
}

function SQL_WEBSITES($report_id){
	$f=array();
	if(isset($GLOBALS["SQL_WEBSITES_$report_id"])){return $GLOBALS["SQL_WEBSITES_$report_id"];}
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT familysite FROM squid_reports_websites WHERE report_id=$report_id";
	
	$results=$q->QUERY_SQL($sql);
	if($GLOBALS["VERBOSE"]){echo "$sql ". mysql_num_rows($results)." entries [".__LINE__."]\n";}
	if(!$q->ok){echo $q->mysql_error."\n";return false;}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="`familysite`='{$ligne["familysite"]}'";
		$eEXPLAIN_QUERY[]="Website is {$ligne["familysite"]}";
		
	}
	if(count($f)>0){
		$GLOBALS["EXPLAIN_QUERY"][]=@implode(" or ", $eEXPLAIN_QUERY);
		$GLOBALS["SQL_WEBSITES_$report_id"]= "(".@implode(" OR ", $f).")";
		if($GLOBALS["VERBOSE"]){echo "{$GLOBALS["SQL_WEBSITES_$report_id"]}[".__LINE__."]\n";}
		return $GLOBALS["SQL_WEBSITES_$report_id"];
	}
	
}

function SQL_CATEGORIES($report_id){
	if(isset($GLOBALS["SQL_CATEGORIES_$report_id"])){return $GLOBALS["SQL_CATEGORIES_$report_id"];}
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT category FROM squid_reports_categories WHERE report_id=$report_id";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="`category`='{$ligne["category"]}'";
		$eEXPLAIN_QUERY[]="category is {$ligne["category"]}";
	
	}
	if(count($f)>0){
		$GLOBALS["EXPLAIN_QUERY"][]=@implode(" or ", $eEXPLAIN_QUERY);
		$GLOBALS["SQL_CATEGORIES_$report_id"]= "(".@implode(" OR ", $f).")";	
		return $GLOBALS["SQL_CATEGORIES_$report_id"];
	}
	
}
function SQL_MEMBERS($report_id){
	if(isset($GLOBALS["SQL_MEMBERS_$report_id"])){return $GLOBALS["SQL_MEMBERS_$report_id"];}
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT * FROM squid_reports_members WHERE report_id=$report_id";
	$results=$q->QUERY_SQL($sql);
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="`{$ligne["member_field"]}`='{$ligne["member_value"]}'";
		$eEXPLAIN_QUERY[]="{$ligne["member_field"]} is {$ligne["member_value"]}";

	}
	if(count($f)==0){
		$GLOBALS["SQL_MEMBERS_$report_id"] = null;
	}else{
		$GLOBALS["SQL_MEMBERS_$report_id"]= "(".@implode(" OR ", $f).")";
		$GLOBALS["EXPLAIN_QUERY"][]=@implode(" or ", $eEXPLAIN_QUERY);
	}
	return $GLOBALS["SQL_MEMBERS_$report_id"];

}

function REPAIR_WORKING_TABLES(){
	
	$q=new mysql_squid_builder();
	$LIST_TABLES_DAYS=$q->LIST_TABLES_HOURS();
	events(count($LIST_TABLES_DAYS)." daily tables");
	while (list ($tablename, $ligne) = each ($LIST_TABLES_DAYS) ){
		$xtime=$q->TIME_FROM_HOUR_TABLE($tablename);
		$xdate=date("Y-m-d",$xtime);
		$danstable="dansguardian_events_".date("Ymd",$xtime);
		if($GLOBALS["VERBOSE"]){echo "$tablename: $danstable = $xdate\n";}
		$q->QUERY_SQL("INSERT IGNORE INTO tables_day (tablename,zDate) VALUES ('$tablename','$xdate')");
		
	}
	
	$currentTABLE=date("Ymd")."_hour";
	$LIST_TABLES_dansguardian_events=$q->LIST_TABLES_dansguardian_events();
	while (list ($tablename, $ligne) = each ($LIST_TABLES_dansguardian_events) ){
		$xtime=$q->TIME_FROM_DANSGUARDIAN_EVENTS_TABLE($tablename);
		$hour_table=date("Ymd",$xtime)."_hour";
		if($currentTABLE==$hour_table){continue;}
		if(!$q->TABLE_EXISTS($hour_table)){
			events(" ####### WARNING - NO TABLE $hour_table #############");
		}
		
	}
	
}


function CREATE_WORKING_TABLES($report_id){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$report_id'"));
	$categorize=$ligne["categorize"];
	$report_days=$ligne["report_days"];
	$recategorize=$ligne["recategorize"];
	$report_cat=$ligne["report_cat"];
	$SQL_DAYS=SQL_DAYS($report_id);
	$q->QUERY_SQL("DROP DATABASE `squidreport_$report_id`");
	
	$CATEGORIZE=FALSE;
	if($recategorize==1){$CATEGORIZE=true;}
	if($categorize==1){$CATEGORIZE=true;}
	
	build_progress($report_id,"Repair working database",10);
	REPAIR_WORKING_TABLES();
	
	build_progress($report_id,"Gathering data...",15);
	
	
	$sql="SELECT zDate FROM tables_day WHERE $SQL_DAYS";
	
	
	$results=$q->QUERY_SQL($sql);
	events($sql);
	events("[$report_id]: ************* ". mysql_num_rows($results)." days *************");
	
	if(!$q->ok){events($q->mysql_error);}
	// MERGE LES DATES --------------------------------------------------
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
	
		if($CATEGORIZE){
			build_progress($report_id,"Categorize:{$ligne["zDate"]}",15);
			events("Categorize table {$ligne["zDate"]}");
			recategorize_tables($ligne["zDate"],$recategorize,$report_id);
		}
		build_progress($report_id,"Gathering data:{$ligne["zDate"]}",15);
		events("Injecting table {$ligne["zDate"]} Report ID [$report_id]");
		if(!merge_tables($ligne["zDate"],$report_id)){
			events(" ************* {$ligne["zDate"]},$report_id FAILED !*************");
			return;
		}
	}
	// ------------------------------------------------------------------

	build_progress($report_id,"Building sum by day...",20);
	if(!dayz_sum_size($report_id)){
		build_progress($report_id,"Error on dayz_sum_size",110);
		Push_logs($report_id);
		return;
	}
	
	build_progress($report_id,"Building sum - Members -...",11);
	if(!dayz_sum_users_size($report_id)){
		build_progress($report_id,"Error on dayz_sum_users_size",110);
		Push_logs($report_id);
		return;
	}	
	
	
	return true;
	
}

function Push_logs($report_id){
	$logFile="/var/log/report_{$GLOBALS["REPORT_ID"]}";
	if(!is_file($logFile)){return;}
	$data=@file_get_contents($logFile);
	$q=new mysql_squid_builder();
	
	if(!$q->FIELD_EXISTS("squid_reports", "report_log")){
		$q->QUERY_SQL("ALTER TABLE `squid_reports` ADD `report_log` longblob");}
	
	$data=mysql_escape_string2($data);
	$q->QUERY_SQL("UPDATE squid_reports SET `report_log`='$data' WHERE ID='$report_id'");
	@unlink($logFile);
	
}




function GENERATE_report_by_websites($report_id){
	
	build_progress($report_id,"Starting report",5);
	if(!CREATE_WORKING_TABLES($report_id)){
		build_progress($report_id,"Failed CREATE_WORKING_TABLES",110);
		return;
	}

	build_progress($report_id,"Building Report...",21);
	contruct_report($report_id);
	build_progress($report_id,"Building CSV Reports...",50);
	reports_csv($report_id);
	build_progress($report_id,"{done}...",100);
	
}



function GENERATE_report_by_category($report_id){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$report_id'"));
	
	build_progress($report_id,"Starting report",5);
	if(!CREATE_WORKING_TABLES($report_id)){
		build_progress($report_id,"Failed CREATE_WORKING_TABLES",110);
		return;
	}
	
	$report_days=$ligne["report_days"];
	$recategorize=$ligne["recategorize"];
	$report_cat=$ligne["report_cat"];
	
	build_progress($report_id,"Building Report...",21);
	contruct_report($report_id);
	build_progress($report_id,"Building CSV Reports...",50);
	reports_csv($report_id);
	build_progress($report_id,"{done}...",100);
}


function report_head($report_id){
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$report_id'"));
	
	build_progress($report_id,"Building Report...",15);
	$pdf=new FPDF();
	$pdf->SetFont('times','',12);
	$pdf->SetTextColor(50,60,100);
	
	$linetime=report_by_category_pdf_range_dayz($report_id)." - ( ".report_by_category_pdf_sumsize($report_id)." )";
	//set up a page
	$pdf->AddPage('P');
	$pdf->SetTitle($ligne["report_name"]);
	$pdf->SetDisplayMode(real,'default');
	$pdf->SetXY(5,10);
	$pdf->SetFontSize(32);
	$pdf->Write(5,$ligne["report_name"]);
	
	
	
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetFontSize(18);
	
	$x1 = $pdf->GetX();
	$y1=$pdf->GetY();
	$pdf->Line(0, $y1, 210, $y1);
	$pdf->SetFont('times','I',12);
	$pdf->Write(15,$ligne["description"]." $linetime");
	
	$pdf->SetFont('times','I',12);
	$pdf->Write(15," ".@implode(" and ", $GLOBALS["EXPLAIN_QUERY"]));
	
	$pdf->Ln();
	$pdf->SetFontSize(16);
	$pdf->SetFont('times','',12);
	return $pdf;
	
}

function reports_csv($report_id){
	$unix=new unix();
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM `squid_reports` WHERE ID='$report_id'"));
	$report_not_categorized=$ligne["report_not_categorized"];
	
	
	$q=new mysql_squid_reports($report_id);
	
	@mkdir($GLOBALS["REPORT_DIRECTORY"],0755,true);
	
	$sql="SELECT sitename as websites, SUM(size) as size_bytes FROM size_dayz GROUP BY websites ORDER BY size_bytes DESC";
	$q->QUERY_CSV($sql, "{$GLOBALS["REPORT_DIRECTORY"]}/by_websites.csv");
	
	
	$sql="SELECT client as ipaddr,uid as members,SUM(size) as size_bytes FROM users_dayz GROUP BY ipaddr,members 
			ORDER BY size_bytes DESC";
	$q->QUERY_CSV($sql, "{$GLOBALS["REPORT_DIRECTORY"]}/by_members.csv");
	
	
	$sql="SELECT SUM(size) as size_bytes,zDate as `day` FROM dayz GROUP BY `day` ORDER BY `day`";
	$q->QUERY_CSV($sql, "{$GLOBALS["REPORT_DIRECTORY"]}/history_bysize.csv");
	
	$sql="SELECT * FROM dayz";
	$q->QUERY_CSV($sql, "{$GLOBALS["REPORT_DIRECTORY"]}/main_data.csv");	
	
	if($report_not_categorized==1){
		$sql="SELECT familysite as `unkown` FROM nocatz ORDER BY `familysite`";
		$q->QUERY_CSV($sql, "{$GLOBALS["REPORT_DIRECTORY"]}/not_categorized.csv");
	}
	
	$zip=$unix->find_program("zip");
	$rm=$unix->find_program("rm");
	$files=$unix->DirFiles($GLOBALS["REPORT_DIRECTORY"],"\.csv$");
	
	while (list ($basename, $ligne) = each ($files)){
		$ZZIP[]="{$GLOBALS["REPORT_DIRECTORY"]}/$basename";
		
	}
			
	
	if(is_file($zip)){
		shell_exec("$zip {$GLOBALS["REPORT_DIRECTORY"]}/$report_id.zip ".@implode(" ", $ZZIP));
		$EXT="zip";
		
		$data=@file_get_contents("{$GLOBALS["REPORT_DIRECTORY"]}/$report_id.zip");
		$q=new mysql_squid_builder();
		$data=mysql_escape_string2($data);
		$q->QUERY_SQL("UPDATE squid_reports SET `report_csv`='$data' ,`report_csv_ext`='$EXT' WHERE ID='$report_id'");
		shell_exec("$rm -rf {$GLOBALS["REPORT_DIRECTORY"]}");
		return;
	}
	
	$tar=$unix->find_program("tar");
	shell_exec("$tar -czf {$GLOBALS["REPORT_DIRECTORY"]}/$report_id.tgz {$GLOBALS["REPORT_DIRECTORY"]}/*.csv");
	$EXT="tgz";
	$data=@file_get_contents("{$GLOBALS["REPORT_DIRECTORY"]}/$report_id.tgz");
	$q=new mysql_squid_builder();
	$data=mysql_escape_string2($data);
	$q->QUERY_SQL("UPDATE squid_reports SET `report_csv`='$data',`report_csv_ext`='$EXT' WHERE ID='$report_id'");
	shell_exec("$rm -rf {$GLOBALS["REPORT_DIRECTORY"]}");
	
	
}


function report_by_category($pdf,$report_id){
	$unix=new unix();
	
	$pdf->AddPage('P');
	$pdf->SetFontSize(32);
	$pdf->Write(5,"Categories");
	$pdf->Ln();
	$pdf->Ln();
	$q=new mysql_squid_reports($report_id);
	$results=$q->QUERY_SQL("SELECT category, SUM(size) as size FROM dayz GROUP BY category ORDER BY size DESC LIMIT 0,15");
	
	
	$TMP_FILE=$unix->FILE_TEMP().".png";
	
	
	$gp=new artica_graphs($TMP_FILE,0);
	
	$pdf->SetFillColor(224,235,255);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024,true);
		$sizeK=$ligne["size"]/1024;
		$sizeM=round($sizeK/1024);
		if($c<15){
			$gp->xdata[]=$sizeM;
			$gp->ydata[]=$ligne["category"];
		}
		$c++;
		
	}
	
	$gp->width=800;
	$gp->height=600;
	$gp->PieExplode=5;
	$gp->PieLegendHide=false;
	$gp->ViewValues=true;
	$gp->title="Top categories by size (MB)";
	$gp->pie();
	
	
	if(is_file($TMP_FILE)){
		$pdf->Ln();
		$pdf->Cell(0, 6, "Top categories by size (MB)", 0, 0, 'C');
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Image($TMP_FILE);
		@unlink($TMP_FILE);
	}
	
	return $pdf;
	
}

function report_by_websites($pdf,$report_id){
	$unix=new unix();


	$pdf->SetFontSize(32);
	$pdf->Write(5,"Websites");
	$pdf->Ln();
	$pdf->Ln();
	$q=new mysql_squid_reports($report_id);
	$results=$q->QUERY_SQL("SELECT sitename, SUM(size) as size FROM size_dayz GROUP BY sitename ORDER BY size DESC");


	$TMP_FILE=$unix->FILE_TEMP().".png";
	build_progress($report_id,"Building Report...",20);

	$gp=new artica_graphs($TMP_FILE,0);

	$pdf->SetFillColor(224,235,255);
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$size=FormatBytes($ligne["size"]/1024,true);
		$sizeK=$ligne["size"]/1024;
		$sizeM=round($sizeK/1024);
		if($c<15){
			$gp->xdata[]=$sizeM;
			$gp->ydata[]=$ligne["sitename"];
		}
		$c++;

	}

	$gp->width=800;
	$gp->height=600;
	$gp->PieExplode=5;
	$gp->PieLegendHide=false;
	$gp->ViewValues=true;
	$gp->title="Top websites by size (MB)";
	$gp->pie();


	if(is_file($TMP_FILE)){
		$pdf->Ln();
		$pdf->Cell(0, 6, "Top websites by size (MB)", 0, 0, 'C');
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Image($TMP_FILE);
		@unlink($TMP_FILE);
	}

	return $pdf;

}

function report_by_members($pdf,$report_id){
	$unix=new unix();
	$pdf->AddPage('P');
	$pdf->SetFontSize(32);
	$pdf->Write(5,"Members");
	$pdf->Ln();

	
	
	$TMP_FILE=$unix->FILE_TEMP().".png";
	$TMP_FILE2=$unix->FILE_TEMP().".png";
	$q=new mysql_squid_reports($report_id);
	$results=$q->QUERY_SQL("SELECT client,uid,SUM(size) as size FROM users_dayz GROUP BY client,uid ORDER BY size DESC");
	$gp=new artica_graphs($TMP_FILE,0);
	$gp2=new artica_graphs($TMP_FILE2,0);
	
	$c=0;
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pdf->SetFontSize(14);
	
		$size=FormatBytes($ligne["size"]/1024,true);
		$sizeK=$ligne["size"]/1024;
		$sizeM=round($sizeK/1024);
		if($ligne["uid"]==null){$ligne["uid"]=$ligne["client"];}
	
	
		if($c<15){
			$gp->xdata[]=$sizeM;
			$gp2->xdata[]=$sizeM;
			$gp->ydata[]=$ligne["uid"];
			$gp2->ydata[]=$ligne["client"];
		}
		$c++;
	
	
	}
	
	$gp->width=700;
	$gp->height=400;
	$gp->PieExplode=5;
	$gp->PieLegendHide=false;
	$gp->ViewValues=true;
	$gp->title="Top User by size (MB)";
	$gp->pie();
	
	
	$gp2->width=700;
	$gp2->height=400;
	$gp2->PieExplode=5;
	$gp2->PieLegendHide=false;
	$gp2->ViewValues=true;
	$gp2->title="Top IP address by size (MB)";
	$gp2->pie();
	
	if(is_file($TMP_FILE)){
		$pdf->Ln();
		$pdf->Cell(0, 6, "Top User by size (MB)", 0, 0, 'C');
		$pdf->Ln();
		$positiondemonimage = (210-600)/2;
		$pdf->Image($TMP_FILE);
		@unlink($TMP_FILE);
	}
	
	if(is_file($TMP_FILE2)){
		$pdf->Ln();
		$pdf->Cell(0, 6, "Top Ip Address by size (MB)", 0, 0, 'C');
		$pdf->Ln();
		$pdf->Image($TMP_FILE2);
		@unlink($TMP_FILE2);
	}
	return $pdf;	
}


function contruct_report($report_id){

	$unix=new unix();
	$pdf=report_head($report_id);
	$pdf=report_by_websites($pdf,$report_id);
	$pdf=report_by_category($pdf,$report_id);
	$pdf=report_by_members($pdf,$report_id);

	build_progress($report_id,"Building Report...",50);
	$TMP_FILE=$unix->FILE_TEMP().".pdf";
	$pdf=report_history_by_day($pdf,$report_id);
	$pdf->Output($TMP_FILE);
	$data=mysql_escape_string2(@file_get_contents($TMP_FILE));
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("UPDATE squid_reports SET report_bin='$data' WHERE ID='$report_id'");
	@unlink($TMP_FILE);
	
	
}

function report_history_by_day($pdf,$report_id){
	$q=new mysql_squid_reports($report_id);
	$unix=new unix();
	$pdf->AddPage('P');
	$pdf->SetFontSize(22);
	$pdf->Write(5,"History");
	$pdf->Ln();
	$pdf->Ln();
	
	
	
	$TMP_FILE=$unix->FILE_TEMP().".png";
	$gp=new artica_graphs($TMP_FILE,0);
	
	$sql="SELECT SUM(size) as size,zDate FROM dayz GROUP BY zDate ORDER BY zDate";
	$results=$q->QUERY_SQL($sql);
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$pdf->SetFontSize(14);
	
		$size=FormatBytes($ligne["size"]/1024,true);
		$sizeK=$ligne["size"]/1024;
		$sizeM=round($sizeK/1024);
		$timeDtr=strtotime($ligne["zDate"]." 00:00:00");
		
	
	
		$gp->ydata[]=$sizeM;
		$gp->xdata[]=$ligne["zDate"];
	}
		
	$gp->width=700;
	$gp->height=300;
	$gp->PieLegendHide=false;
	$gp->ViewValues=true;
	$gp->title=null;
	$gp->line_green();
	
	if(is_file($TMP_FILE)){
		$pdf->Ln();
		$pdf->Cell(0, 6, "History in MB/Day", 0, 0, 'C');
		$pdf->Ln();
		$pdf->Image($TMP_FILE);
		@unlink($TMP_FILE);
	}
	
	return $pdf;
	
}


function report_by_category_pdf_range_dayz($report_id){
	$q=new mysql_squid_reports($report_id);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zDate  FROM `dayz` ORDER BY zDate LIMIT 0,1"));	
	$first_day=$ligne["zDate"];
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zDate  FROM `dayz` ORDER BY zDate DESC LIMIT 0,1"));
	$last_day=$ligne["zDate"];
	return "from $first_day to $last_day";
	
}
function report_by_category_pdf_sumsize($report_id){
	$q=new mysql_squid_reports($report_id);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(size) as size  FROM `dayz` LIMIT 0,1"));
	return "bandwidth total: ".FormatBytes($ligne["size"]/1024,true);

}

function dayz_sum_users_size($report_id){
	$q=new mysql_squid_reports($report_id);
	$q->CheckTables();
	$results=$q->QUERY_SQL("SELECT SUM(size) as size,client,hostname,uid,MAC FROM dayz GROUP BY client,hostname,uid,MAC");
	$prefix="INSERT IGNORE INTO users_dayz (zMD5,size,client,hostname,uid,MAC) VALUES ";
	$numz=mysql_num_rows($results);
	echo "dayz_sum_size $numz entrie(s)\n";

	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		$f[]="('$zMD5','{$ligne["size"]}','{$ligne["client"]}','{$ligne["hostname"]}','{$ligne["uid"]}','{$ligne["MAC"]}')";

		if(count($f)>1000){
			$sql=$prefix.@implode(",", $f);
			$q->QUERY_SQL($sql);
			if(!$q->ok){
				echo $q->mysql_error."\n$sql\n";
				return false;
			}
		}

	}

	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n$sql\n";
			return false;
		}
	}

	return true;


}
function dayz_sum_size($report_id){
	$f=array();
	$q=new mysql_squid_reports($report_id);
	$q->CheckTables();
	$results=$q->QUERY_SQL("SELECT sitename, SUM(size) as size, zDate FROM dayz GROUP BY sitename, zDate");
	$prefix="INSERT IGNORE INTO size_dayz (zMD5,zDate,sitename,size) VALUES ";
	$numz=mysql_num_rows($results);
	echo "[$report_id]: dayz_sum_size $numz entrie(s)\n";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$zMD5=md5(serialize($ligne));
		$f[]="('$zMD5','{$ligne["zDate"]}','{$ligne["sitename"]}','{$ligne["size"]}')";
	
			if(count($f)>1000){
				$sql=$prefix.@implode(",", $f);
				$q->QUERY_SQL($sql);
				if(!$q->ok){
				echo $q->mysql_error."\n$sql\n";
				return false;
				}
			}
	
		}
	
	
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo $q->mysql_error."\n$sql\n";
			return false;
			}
		}
	
		return true;	
	
	
}


function merge_tables($zdate,$report_id){
	
	
	if(isset($GLOBALS["merge_tables_{$zdate}_{$report_id}"])){
		events("$zdate report [$report_id] already done");
		return true;
	}
	
	$GLOBALS["merge_tables_{$zdate}_{$report_id}"]=true;
	$xtime=strtotime("$zdate 00:00:00");
	
	$tday=date("Ymd",$xtime);
	$tzdate=date("Y-m-d",$xtime);
	events("$zdate = $xtime -> $tday -> $tzdate");
	
	$table_hour="{$tday}_hour";
	$q=new mysql_squid_builder();
	
	$TOTAL_ROWS=$q->COUNT_ROWS($table_hour);
	
	if($TOTAL_ROWS==0){
		build_progress($report_id,"NO ROWS IN SOURCE TABLE $table_hour ( $tzdate )",20);
		return true;
	}
	
	build_progress($report_id,"Merging $zdate $TOTAL_ROWS rows in this table",20);
	
	$q2=new mysql_squid_reports($report_id);

	
	if(!isset($GLOBALS["report_type"])){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT report_type FROM `squid_reports` WHERE ID='$report_id'"));
		$GLOBALS["report_type"]=$ligne["report_type"];
	}
	
	
	
	
	$filter=SQL_WEBSITES($report_id);
	if($filter<>null){
		$SQL_FILTERS[]=$filter;
	}	
	
	$filter=SQL_CATEGORIES($report_id);
	if($filter<>null){
		$SQL_FILTERS[]=$filter;
	}
	
	$filter=SQL_MEMBERS($report_id);
	if($filter<>null){
		$SQL_FILTERS[]=$filter;
	}
	
	if(count($SQL_FILTERS)>0){
		$HAVING="HAVING ".@implode(" AND ", $SQL_FILTERS);
	}
	
	
	if(!$q2->CheckTables()){
		events($q2->mysql_error);
		build_progress($report_id,"CheckTables()...MySQL error",110);
		return false;
	}

	
	$qyery[]="SELECT familysite,category,client,hostname,uid,MAC,SUM(size) as size,SUM(hits) as hits";
	$qyery[]="FROM $table_hour GROUP BY familysite,category,client,hostname,uid,MAC";
	$qyery[]=$HAVING;
			
	$sql=@implode(" ", $qyery);
	
	events($sql);
	
	
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		events($q->mysql_error);
		return false;
	}	
	
	$q->QUERY_SQL("UPDATE $table_hour SET category='' WHERE category=','");
	
	$f=array();
	$prefix="INSERT IGNORE INTO dayz (zMD5,sitename,client,hostname,zDate,MAC,size,hits,uid,category) VALUES ";
	$numz=mysql_num_rows($results);
	events("$table_hour: $numz entrie(s)");
	if($numz==0){return true;}
	
	if($GLOBALS["VERBOSE"]){echo "$table_hour $numz entrie(s)\n";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$zMD5=md5(serialize($ligne));
		$f[]="('$zMD5','{$ligne["familysite"]}','{$ligne["client"]}','{$ligne["hostname"]}',
		'$zdate','{$ligne["MAC"]}','{$ligne["size"]}','{$ligne["hits"]}','{$ligne["uid"]}','{$ligne["category"]}')";
		
		if(count($f)>1000){
			$sql=$prefix.@implode(",", $f);
			$q2->QUERY_SQL($sql);
			if(!$q2->ok){
				echo $q2->mysql_error."\n$sql\n";
				return false;
			}
		}
		
	}
	

	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){
			echo $q2->mysql_error."\n$sql\n";
			return false;
		}
	}

	$f=array();
	$sql="SELECT familysite FROM $table_hour WHERE LENGTH(category)=0 GROUP BY familysite";
	$prefix="INSERT IGNORE INTO nocatz (familysite) VALUE";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error."\n";
		if($GLOBALS["VERBOSE"]){echo "******************\n$sql\n******************\n";}
		return false;
	}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$f[]="('{$ligne["familysite"]}')";
		
	}
	
	if(count($f)>0){
		$sql=$prefix.@implode(",", $f);
		$q2->QUERY_SQL($sql);
		if(!$q2->ok){
			echo $q2->mysql_error."\n$sql\n";
			return false;
		}
	}
	
	
	return true;
	
	
}

function recategorize_tables($zdate,$recategorize=0,$report_id){
	$xtime=strtotime("$zdate 00:00:00");
	$tday=date("Ymd",$xtime);
	$table_hour="{$tday}_hour";
	
	$q=new mysql_squid_builder();
	$qr=new mysql_squid_reports($report_id);
	$q->QUERY_SQL("UPDATE $table_hour SET category='' WHERE category=','");
	
	
	$sql="SELECT familysite FROM $table_hour GROUP BY familysite";
	if($recategorize==1){
		$sql="SELECT familysite FROM $table_hour WHERE LENGTH(category)=0 GROUP BY familysite";
	}
	
	$results=$q->QUERY_SQL("SELECT familysite FROM $table_hour GROUP BY familysite");
	if(!$q->ok){
		echo $q->mysql_error."\n";
	}
	
	echo "Recategorize table {$table_hour} ". mysql_num_rows($results)."\n";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$familysite=$ligne["familysite"];
		$categories=trim($q->GET_CATEGORIES($familysite));
		if($categories==null){
			$qr->QUERY_SQL("INSERT IGNORE INTO nocatz (familysite) VALUE ('$familysite')");
			continue;
		}
		echo "$familysite = `$categories`\n";
		$q->QUERY_SQL("UPDATE $table_hour SET `category`='$categories' WHERE `familysite`='$familysite'");
	}
}

