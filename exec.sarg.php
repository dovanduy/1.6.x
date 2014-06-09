<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
$GLOBALS["TITLENAME"]="Sarg Statistics Generator";
$GLOBALS["LOGFILE"]="/var/log/sarg-exec.log";
$GLOBALS["OUTPUT"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

$sock=new sockets();
$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
if($EnableSargGenerator==0){ if($GLOBALS["VERBOSE"]){echo "SARG IS DISABLED BY EnableSargGenerator\n";} die(); }
if($argv[1]=="--exec-daily"){execute_daily();exit;}
if($argv[1]=="--exec-monthly"){execute_monthly();exit;}
if($argv[1]=="--exec-weekly"){execute_weekly();exit;}
if($argv[1]=="--exec-hourly"){execute_hourly();exit;}
if($argv[1]=="--index"){$GLOBALS["OUTPUT"]=true;build_index_page();exit;}



if($argv[1]=="--test-notifs"){sarg_admin_events("TESTS","NONE",__FILE__,__LINE__);die();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die();}

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();die();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();die();}
if($argv[1]=="--exec"){execute();die();}
if($argv[1]=="--backup"){backup();die();}
if($argv[1]=="--conf"){buildconf();die();}
if($argv[1]=="--restore-id"){restore_id($argv[2]);die();}
if($argv[1]=="--rotate"){rotate($argv[2]);die();}
if($argv[1]=="--tofile"){sargToFile($argv[2]);die();}
if($argv[1]=="--status"){status();die();}
// exec.sarg.php --conf



function SargDefault($SargConfig){
	if($SargConfig["report_type"]==null){$SargConfig["report_type"]="topusers topsites sites_users users_sites date_time denied auth_failures site_user_time_date downloads";}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if(!is_numeric($SargConfig["long_url"])){$SargConfig["long_url"]=0;}
	if(!is_numeric($SargConfig["graphs"])){$SargConfig["graphs"]=1;}
	if(!is_numeric($SargConfig["user_ip"])){$SargConfig["user_ip"]=1;}
	if(!is_numeric($SargConfig["resolve_ip"])){$SargConfig["resolve_ip"]=1;}
	if(!is_numeric($SargConfig["lastlog"])){$SargConfig["lastlog"]=0;}
	if(!is_numeric($SargConfig["topsites_num"])){$SargConfig["topsites_num"]=100;}
	if(!is_numeric($SargConfig["topuser_num"])){$SargConfig["topuser_num"]=0;}
	if($SargConfig["topsites_sort_order"]==null){$SargConfig["topsites_sort_order"]="D";}
	if($SargConfig["index_sort_order"]==null){$SargConfig["index_sort_order"]="D";}
	if($SargConfig["topsites_num"]<2){$SargConfig["topsites_num"]=100;}
	if($SargConfig["language"]==null){$SargConfig["language"]="English";}
	if($SargConfig["title"]==null){$SargConfig["title"]="Squid User Access Reports";}
	if($SargConfig["date_format"]==null){$SargConfig["date_format"]="e";}
	if($SargConfig["records_without_userid"]==null){$SargConfig["records_without_userid"]="ip";}
	if($SargConfig["graphs"]==1){$SargConfig["graphs"]="yes";}else{$SargConfig["graphs"]="no";}
	if($SargConfig["user_ip"]==1){$SargConfig["user_ip"]="yes";}else{$SargConfig["user_ip"]="no";}
	if($SargConfig["resolve_ip"]==1){$SargConfig["resolve_ip"]="yes";}else{$SargConfig["resolve_ip"]="no";}
	if($SargConfig["long_url"]==1){$SargConfig["long_url"]="yes";}else{$SargConfig["long_url"]="no";}
	return $SargConfig;
}

function restart(){
	stop();
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, build_index_page();\n";
	build_index_page();
	start();
}


function stop(){
	echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, success\n";
}

function start(){
	$sock=new sockets();
	$enabled=$sock->GET_INFO("EnableSargGenerator");
	if(!is_numeric($enabled)){$enabled=0;}
	if($enabled==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, disabled\n";
		return;}
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, building configuration\n";
	buildconf();
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, success\n";
	
}


function buildconf(){
	
	$sock=new sockets();
	$unix=new unix();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	if($unix->IsProtectedDirectory($SargOutputDir,true)){
		$sock->SET_INFO("SargOutputDir", "/var/www/html/squid-reports");
		$SargOutputDir="/var/www/html/squid-reports";
	}
	
	if(!is_file("/etc/artica-postfix/old_SargOutputDir")){
		@file_put_contents("/etc/artica-postfix/old_SargOutputDir", $SargOutputDir);
	}
	
	if($SargOutputDir=="/usr/share/artica-postfix/squid"){
		$SargOutputDir="/var/www/html/squid-reports";
		$sock->SET_INFO("SargOutputDir", "/var/www/html/squid-reports");
	}
	
	if($SargOutputDir<>"/usr/share/artica-postfix/squid"){
		@mkdir("$SargOutputDir",0755,true);
		if(is_dir("/usr/share/artica-postfix/squid")){
			$cp=$unix->find_program("cp");
			$rm=$unix->find_program("rm");
			shell_exec("$cp -rf /usr/share/artica-postfix/squid/* \"$SargOutputDir/\"");
			shell_exec("$rm -rf \"/usr/share/artica-postfix/squid\"");
			@rmdir("/usr/share/artica-postfix/squid");
		}
	}
	
	$old_SargOutputDir=@file_get_contents("/etc/artica-postfix/old_SargOutputDir");
	if($old_SargOutputDir<>$SargOutputDir){
		@mkdir("$SargOutputDir",0755,true);
		if(is_dir($old_SargOutputDir)){
			$cp=$unix->find_program("cp");
			$rm=$unix->find_program("rm");
			shell_exec("$cp -rf \"$old_SargOutputDir/*\" \"$SargOutputDir/\"");
			if(!$unix->IsProtectedDirectory($old_SargOutputDir,true)){shell_exec("$rm -rf \"$old_SargOutputDir\"");@rmdir($old_SargOutputDir);}
			if(!is_dir($old_SargOutputDir)){
				@file_put_contents("/etc/artica-postfix/old_SargOutputDir", $SargOutputDir);
			}
		}else{
			@file_put_contents("/etc/artica-postfix/old_SargOutputDir", $SargOutputDir);
		}
	}
	

	
	events("Output dir: $SargOutputDir");
	$SargConfig=unserialize(base64_decode($sock->GET_INFO("SargConfig")));
	
	$SargConfig=SargDefault($SargConfig);	
	if($SargConfig["lastlog"]==0){$SargConfig["lastlog"]=90;}
	$SargConfig["lastlog"]*24;
	
	
	$conf[]="language {$SargConfig["language"]}";
	$conf[]="graphs {$SargConfig["graphs"]}";
	$conf[]="title \"{$SargConfig["title"]}\"";
	$conf[]="topsites_num {$SargConfig["topsites_num"]}";
	$conf[]="topuser_num {$SargConfig["topuser_num"]}";
	$conf[]="report_type {$SargConfig["report_type"]}";
	$conf[]="topsites_sort_order CONNECT {$SargConfig["topsites_sort_order"]}";
	$conf[]="index_sort_order {$SargConfig["index_sort_order"]}";
	$conf[]="resolve_ip {$SargConfig["resolve_ip"]}";
	$conf[]="user_ip {$SargConfig["user_ip"]}";
	$conf[]="exclude_hosts /etc/squid3/sarg.hosts";
	$conf[]="date_format {$SargConfig["date_format"]}";
	$conf[]="records_without_userid {$SargConfig["records_without_userid"]}";
	$conf[]="long_url {$SargConfig["long_url"]}";
	$conf[]="lastlog {$SargConfig["lastlog"]}";
	$conf[]="index yes";
	$conf[]="index_tree file";
	$conf[]="overwrite_report yes";
	$conf[]="mail_utility mail";
	$conf[]="temporary_dir /tmp";
	$conf[]="date_time_by bytes";
	$conf[]="show_sarg_info no";
	$conf[]="show_sarg_logo no";
	$conf[]="external_css_file /sarg.css";
	$conf[]="ulimit none";
	$conf[]="squid24 off";
	$conf[]="output_dir $SargOutputDir";
	$conf[]="logo_image /logo.gif";
	$conf[]="image_size 160 58";
	$conf[]="access_log /var/log/squid/access.log";
	$conf[]="realtime_access_log_lines 5000";
	$conf[]="graph_days_bytes_bar_color orange";
	$conf[]="";	

	
file_put_contents("/etc/squid3/sarg.conf",@implode("\n",$conf));


file_put_contents("/etc/squid3/sarg-configured-1.8.012202.conf",@implode("\n",$conf));
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, sarg.conf done\n";
events("/etc/squid3/sarg.conf done");


$ips=array();


@file_put_contents("/etc/squid3/sarg.hosts",@implode("\n",$ips));
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, sarg.hosts done\n";
// $sock=new sockets();$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}





$unix=new unix();
$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
$squidbin=$unix->LOCATE_SQUID_BIN();
echo "Starting......: ".date("H:i:s")." Apache user: $lighttpd_user\n";
@chown("$SargOutputDir/sarg.css",$lighttpd_user);
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, sarg.css done\n";
	$nice=EXEC_NICE();
	$unix=new unix();
	$sarg_bin=$unix->find_program("sarg");
	$squidbin=$unix->find_program("squid");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($sarg_bin)){
		sarg_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
		return;
	}
	unset($f);
	$f[]="#!/bin/sh";
	$f[]="export LC_ALL=C";
	$f[]="$nice $php5 ".__FILE__." --exec-daily >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.daily/0sarg.sh", @implode("\n",$f));
	@chmod("/etc/cron.daily/0sarg.sh",0755);
	events("cron.daily done");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, cron cron.daily done\n";
	unset($f);

	$f[]="#!/bin/sh";
	$f[]="export LC_ALL=C";
	$f[]="$nice $php5 ".__FILE__." --exec-hourly >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.hourly/0sarg.sh", @implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, cron cron.hourly done\n";
	@chmod("/etc/cron.hourly/0sarg.sh",0755);
	events("cron.hourly done");
	unset($f);

	$f[]="#!/bin/sh";
	$f[]="export LC_ALL=C";
	$f[]="$nice $php5 ".__FILE__." --exec-monthly >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.monthly/0sarg.sh", @implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, cron cron.monthly done\n";
	@chmod("/etc/cron.monthly/sarg.sh",0755);
	events("cron.monthly done");
	unset($f);
	

	$f[]="#!/bin/sh";
	$f[]="export LC_ALL=C";
	$f[]="$nice $php5 ".__FILE__." --exec-weekly >/dev/null 2>&1";
	$f[]="";
	@file_put_contents("/etc/cron.weekly/0sarg.sh", @implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, cron cron.weekly done\n";
	@chmod("/etc/cron.weekly/0sarg.sh",0755);
	events("cron.weekly done");
	unset($f);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ".__FUNCTION__." done\n";

}

function build_index_page(){
	$sock=new sockets();
	$unix=new unix();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir\n";}
	
	
	@copy("/usr/share/artica-postfix/bin/install/sarg.css","$SargOutputDir/sarg.css");
	@copy("/usr/share/artica-postfix/img/logo-artica-160.gif", "$SargOutputDir/logo.gif");
	@copy("/usr/share/artica-postfix/css/images/pattern.png", "$SargOutputDir/pattern.png");
	@copy("/usr/share/artica-postfix/ressources/templates/default/images/ui-bg_highlight.png", "$SargOutputDir/ui-bg_highlight.png");
	@copy("/usr/share/artica-postfix/img/arrow-right-16.png", "$SargOutputDir/arrow-right-16.png");
	@chmod("$SargOutputDir/arrow-right-16.png", 0755);
	@chmod("$SargOutputDir/ui-bg_highlight.png", 0755);
	@chmod("$SargOutputDir/sarg.css", 0755);
	@chmod("$SargOutputDir/logo.gif", 0755);
	@chmod("$SargOutputDir/pattern.png", 0755);

$f[]="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
$f[]="<html>";
$f[]="<head>";
$f[]="  <meta http-equiv=\"Content-Type\" content=\"text/html; charset=ISO-8859-1\">";
$f[]="<title>SARG reports</title>";
$f[]="<link rel=\"stylesheet\" href=\"/sarg.css\" type=\"text/css\">";
$f[]="</head>";
$f[]="<body>";
$f[]="<div class=\"logo\"><img src=\"/logo.gif\">&nbsp;</div>";
$f[]="<div class=\"title\"><table cellpadding=\"0\" cellspacing=\"0\">";
$f[]="<tr><th class=\"title_c\">Squid User Access Reports</th></tr>";
$f[]="</table>";
$f[]="</div>";
$f[]="<table cellpadding=\"0\" cellspacing=\"0\">

";
if(is_file("$SargOutputDir/hourly/index.html")){
	$f[]="<tr><td align='center'><a href=\"hourly/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Hourly reports&nbsp;&raquo;</td></tr>";
}else{
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir/hourly/index.html no such file\n";}
}
if(is_file("$SargOutputDir/daily/index.html")){
	$f[]="<tr><td align='center'><a href=\"daily/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Daily reports&nbsp;&raquo;</td></tr>";
}else{
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir/daily/index.html no such file\n";}
}
if(is_file("$SargOutputDir/weekly/index.html")){
	$f[]="<tr><td align='center'><a href=\"weekly/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Weekly reports&nbsp;&raquo;</td></tr>";
}else{
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir/weekly/index.html no such file\n";}
}
if(is_file("$SargOutputDir/monthly/index.html")){
	$f[]="<tr><td align='center'><a href=\"monthly/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Monthly reports&nbsp;&raquo;</td></tr>";
}else{
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir/monthly/index.html no such file\n";}
}

$dirs=$unix->dirdir($SargOutputDir);

$monthsz = array( 'jan'=>1,  'ene'=>1,  'feb'=>2,  'mar'=>3, 'apr'=>4, 'abr'=>4,
		'may'=>5,  'jun'=>6,  'jul'=>7,  'aug'=>8, 'ago'=>8, 'sep'=>9,
		'oct'=>10, 'nov'=>11, 'dec'=>12, 'dic'=>12 );

while (list ($index, $line) = each ($dirs) ){
	$dir=basename($line);
	if(!preg_match("#\/([0-9]+)([A-Za-z]+)([0-9]+)-([0-9]+)([A-Za-z]+)([0-9]+)$#", $line,$re)){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $dir, no match\n";
		continue;	
	}
	
	
		$day=$re[1];
		$month=$re[2];
		$year=$re[3];
		
		$day1=$re[4];
		$month1=$re[5];
		$year1=$re[6];
		
	if(strlen($year)<4){
		if(strlen($day)==4){
			$year=$re[1];
			$month=$re[2];
			$day=$re[3];
			
			$day1=$re[6];
			$month1=$re[5];
			$year1=$re[4];
		}

	}
	
	if(strlen($year)<4){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $dir, no match\n";
		continue;
	}
		
		
		$monthNum=$monthsz[strtolower($month)];
		if(strlen($monthNum)==1){$monthNum="0{$monthNum}";}
		$time=strtotime("$year-$monthNum-$day 00:00:00");
		

		
		$ARRAY[$year][$month][$day]["DIR"]=$dir;
		$too=array();
		if($day1<>$day){ $too[]=$day1; }
		if($month1<>$month){ $too[]=$month1; }	
		if($year1<>$year){ $too[]=$year1; }		
		if($month1==$month){ $to="$day1"; }
		if(count($to)>0){$to=@implode("/", $to);}else{$to=null;}
		$ARRAY[$year][$month][$day]["TO"]="$to";
		$ARRAY[$year][$month][$day]["TITLE"]=date("l $day",$time);
		
	
	
}

while (list ($year, $array1) = each ($ARRAY) ){
	$f[]="<tr><td>&nbsp;</td></td>";
	$f[]="<tr><td align='center'><span style='font-size:22px;font-weight:bold'>&laquo;&nbsp;$year reports&nbsp;&raquo;</td></tr>";
	$f[]="<tr><td align='center'>";
	$TR=array();
	while (list ($month, $array2) = each ($array1) ){
		
		$ttr=array();
		$ttr[]="<table style='width:100%;marign:5px'>";
		$c=1;
		while (list ($day, $array3) = each ($array2) ){
			$c++;
			$ttr[]="<tr>
				<td width=1% nowrap><img src='arrow-right-16.png'></td>
				<td style='font-size:14px'><a href=\"{$array3["DIR"]}/index.html\">{$array3["TITLE"]} {$array3["TO"]}</a></td>
				</tr>";
			if(strpos(" ".$array3["TITLE"],"Sunday")>0){
				$ttr[]="<tr><td colspan=2><hr></td></tr>";
			
			}
		}
		$ttr[]="</table>";
		$TR[]="<div style='font-size:22px;font-weight:bold'>&laquo;&nbsp;$month reports&nbsp;&raquo;</div>".@implode("\n", $ttr);
	}
	$f[]=CompileTr4($TR);
	$f[]="</td></tr>";
}



$f[]="</table>
</body>
</html>";
events("$SargOutputDir/index.html done");
events("$SargOutputDir/index.php done");
@file_put_contents("$SargOutputDir/index.html", @implode("\n", $f));
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $SargOutputDir/index.html done\n";}

@file_put_contents("$SargOutputDir/index.php","<?php\nheader('location:index.html')\n?>");
}


function execute_monthly(){
	$unix=new unix();
	$t=time();
	buildconf();
	$t=time();
	$TODAY=date("d/m/Y");
	$sock=new sockets();
	$date = new DateTime();
	$date->sub(new DateInterval('P1D'));
	$YESTERDAY=$date->format("d/m/Y");
	$nice=$unix->EXEC_NICE();
	$sarg_bin=$unix->find_program("sarg");
	$results[]="Today: $TODAY";
	$results[]="Yesterday: $YESTERDAY";
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	
	$results[]="Output directory: $SargOutputDir\n";
	$results[]="Web service user: $lighttpd_user\n";
	$results[]="Sarg binary: $sarg_bin";
	$results[]="Nice command: $nice";
	$date=$unix->find_program("date");
	@mkdir("$SargOutputDir/monthly",0755,true);
	
	$WEEKSAGO=exec("$date --date \"4 weeks ago\" +%d/%m/%Y 2>&1");
	$results[]="4 weeks ago: $WEEKSAGO";
	
	$squid=new squidbee();
	if($squid->is_auth()){$usersauth=true;}
	if($usersauth){events("User authentification enabled");$u=" -i ";}else{events("User authentification disabled");}
	
	$cmds[]="$nice$sarg_bin $u-f /etc/squid3/sarg.conf -l /var/log/squid/access.log";
	$cmds[]="-o \"$SargOutputDir/monthly\" -d $WEEKSAGO-$YESTERDAY 2>&1";
	
	exec(@implode(" ", $cmds)." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	sarg_admin_events("Monthly report $WEEKSAGO-$YESTERDAY generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");

}
function execute_weekly(){
	$unix=new unix();
	$t=time();
	buildconf();
	$TODAY=date("d/m/Y");
	$sock=new sockets();
	$date = new DateTime();
	$date->sub(new DateInterval('P1D'));
	$YESTERDAY=$date->format("d/m/Y");
	$nice=$unix->EXEC_NICE();
	$sarg_bin=$unix->find_program("sarg");
	$results[]="Today: $TODAY";
	$results[]="Yesterday: $YESTERDAY";
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	$date=$unix->find_program("date");
	
	$results[]="Output directory: $SargOutputDir\n";
	$results[]="Web service user: $lighttpd_user\n";
	$results[]="Sarg binary: $sarg_bin";
	$results[]="Nice command: $nice";
	$LASTWEEK=exec("$date --date \"1 week ago\" +%d/%m/%Y 2>&1");
	
	unset($f);
	$squid=new squidbee();
	if($squid->is_auth()){$usersauth=true;}
	if($usersauth){events("User authentification enabled");$u=" -i ";}else{events("User authentification disabled");}
	
	$cmds[]="$nice$sarg_bin $u-f /etc/squid3/sarg.conf -l /var/log/squid/access.log";
	$cmds[]="-o \"$SargOutputDir/weekly\" -z -d $LASTWEEK-$TODAY 2&>1";
	buildconf();
	exec(@implode(" ", $cmds)." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	sarg_admin_events("Weekly report $LASTWEEK-$TODAY generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");
}
function execute_daily(){
	$unix=new unix();
	$t=time();
	$TODAY=date("d/m/Y");
	$sock=new sockets();
	$date = new DateTime();
	$date->sub(new DateInterval('P1D'));
	$YESTERDAY=$date->format("d/m/Y");
	$nice=$unix->EXEC_NICE();
	$sarg_bin=$unix->find_program("sarg");
	$results[]="Today: $TODAY";
	$results[]="Yesterday: $YESTERDAY";
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	
	$results[]="Output directory: $SargOutputDir\n";
	$results[]="Web service user: $lighttpd_user\n";
	$results[]="Sarg binary: $sarg_bin";
	$results[]="Nice command: $nice";
	
	@mkdir("$SargOutputDir/daily",0755,true);
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");
	
	$squid=new squidbee();
	if($squid->is_auth()){$usersauth=true;}
	if($usersauth){events("User authentification enabled");$u=" -i ";}else{events("User authentification disabled");}
	
	
	$cmds[]="$nice$sarg_bin $u-f /etc/squid3/sarg.conf -l /var/log/squid/access.log";
	$cmds[]="-o \"$SargOutputDir/daily\"";
	$cmds[]="-z -d $YESTERDAY-$TODAY -x";

	buildconf();
	exec(@implode(" ", $cmds)." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	sarg_admin_events("Daily $YESTERDAY-$TODAY report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");	
	build_index_page();
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");
}

function execute_hourly(){
	$unix=new unix();
	$t=time();
	buildconf();
	
	$unix=new unix();
	$t=time();
	$TODAY=date("d/m/Y");
	$sock=new sockets();
	$date = new DateTime();
	$date->sub(new DateInterval('P1D'));
	$YESTERDAY=$date->format("d/m/Y");
	$LASTHOUR = date("H", time() - 3600);
	$HOUR= date("H", time());
	$nice=$unix->EXEC_NICE();
	$sarg_bin=$unix->find_program("sarg");
	$results[]="Today: $TODAY";
	$results[]="Last Hour: $LASTHOUR";
	$results[]="Current Hour: $LASTHOUR";
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	
	$results[]="Output directory: $SargOutputDir\n";
	$results[]="Web service user: $lighttpd_user\n";
	$results[]="Sarg binary: $sarg_bin";
	$results[]="Nice command: $nice";
	
	@mkdir("$SargOutputDir/daily",0755,true);
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");
	@mkdir("$SargOutputDir/hourly",0755);
	
	$squid=new squidbee();
	if($squid->is_auth()){$usersauth=true;}
	if($usersauth){events("User authentification enabled");$u=" -i ";}else{events("User authentification disabled");}
	
	
	$cmds[]="$nice$sarg_bin $u-f /etc/squid3/sarg.conf";
	$cmds[]="-l /var/log/squid/access.log -o \"$SargOutputDir/hourly\" -z -d $TODAY-$TODAY";
	$cmds[]="-t \"$LASTHOUR:00-$HOUR:00\"";
	
	
	buildconf();
	exec(@implode(" ", $cmds)." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	sarg_admin_events("Hourly $LASTHOUR:00-$HOUR:00 report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
	$unix->chown_func($lighttpd_user, "$SargOutputDir/*");

}
function progress($text,$num){events($text);}
function events($text){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	$GLOBALS["CLASS_UNIX"]->events($text,$GLOBALS["LOGFILE"],false,$sourcefunction,$sourceline,__FILE__);
	
}
function file_extension($filename){
	return pathinfo($filename, PATHINFO_EXTENSION);
}


function sargToFile($filePath){
	if(!is_file($filePath)){
		progress("FATAL $filePath no such file",10);
		return;
	}
	$unix=new unix();
	$sarg_bin=$unix->find_program("sarg");
	$linesNumber=$unix->COUNT_LINES_OF_FILE($filePath);
	$basename=basename($filePath);
	progress("Open $filePath $linesNumber lines",10);
	$sock=new sockets();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}	
	$nice=EXEC_NICE();
	$usersauth=false;
	$t=time();
	$squid=new squidbee();
	if($squid->is_auth()){$usersauth=true;}
	if($usersauth){events("User authentification enabled");$u=" -i ";}else{events("User authentification disabled");}
		
	$t=time();
	$cmd="$nice$sarg_bin $u-f /etc/squid3/sarg.conf -l \"$filePath\" -o \"$SargOutputDir\" -x -z 2>&1";
	progress("Open $cmd",10);
	exec($cmd,$results);
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#SARG: OPTION:#", $line)){continue;}
		events($line);
	
	}	
	
	if($basename=="sarg.log"){
			continue;
	}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	sarg_admin_events("$basename generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
	
}

function rotate($filename){
	$filename=basename($filename);
	$filePath="/var/log/squid/$filename";
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$filename.pid";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Process $pid already exists...aborting");
		die();
	}	
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
	
	$sarg_bin=$unix->find_program("sarg");
	$q=new mysql_storelogs();
	
	if(!is_file($filePath)){
		events("$filePath no such file");
	}
	
	if(!is_file($sarg_bin)){
		sarg_admin_events("FATAL, ($filePath) unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
		$q->ROTATE_TOMYSQL($filePath);
		return;
	}	
	$t=time();
	sargToFile($filePath);
	$q->ROTATE_TOMYSQL($filePath);
	progress("$filename done ".$unix->distanceOfTimeInWords($t,time()));
	backup();	
	
}

function restore_id($storeid){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$storeid.pid";
	$pid=@file_get_contents("$pidfile");
	if($unix->process_exists($pid,basename(__FILE__))){
		events("Process $pid already exists...aborting");
		die();
	}
	
	
	@file_put_contents($pidfile, getmypid());	
	
	include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
	$sock=new sockets();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$sarg_bin=$unix->find_program("sarg");
	if(!is_file($sarg_bin)){
		sarg_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
		return;
	}	
	
	$bzip2=$unix->find_program("bzip2");
	$gunzip=$unix->find_program("gunzip");
	$TempDir="/home/artica-extract-temp";
	@mkdir($TempDir,0777);
	@chown($TempDir, "mysql");
	@chdir($TempDir, "mysql");	
	
	if(!is_file("/etc/squid3/sarg.conf")){buildconf();}

	$q=new mysql_storelogs();
	
	$ligne=@mysql_fetch_array($q->QUERY_SQL("SELECT filename FROM files_info WHERE storeid='$storeid'"));
	$filename=$ligne["filename"];
	events("Extracting infos from $filename");
	$EnableSyslogDB=@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableSyslogDB");
	if(!is_numeric($EnableSyslogDB)){$EnableSyslogDB=0;}	
	if($EnableSyslogDB==0){events("Extracting infos from $filename failed, SyslogDB is not enabled");return;}

	$q=new mysql_storelogs();
	$sql="SELECT filecontent INTO DUMPFILE '$TempDir/$filename' FROM files_store WHERE ID = '$storeid'";
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){events("Failed!!! $q->mysql_error",100);return;}
	
	$file_extension=file_extension($filename);
	progress("Extract $filename extension: $file_extension",5);
	$newtFile=$filename.".log";
	
	if($file_extension=="bz2"){
		$cmdline="$bzip2 -d \"$TempDir/$filename\" -c >\"$TempDir/$newtFile.log\" 2>&1";
		exec($cmdline,$results);
	}
	if($file_extension=="gz"){
		$cmdline="$gunzip -d \"$TempDir/$filename\" -c >\"$TempDir/$newtFile.log\" 2>&1";
	}
	if($cmdline<>null){
		exec($cmdline,$results);
		progress("Extract done ".@implode(" ", $results),7);
	}else{
		if(!@copy("$TempDir/$filename","$TempDir/$newtFile.log")){
			progress("Failed!!! Copy error",100);
			return;
		}
	}
	@unlink("$TempDir/$filename");
	if(!is_file("$TempDir/$newtFile.log")){
		progress("Failed!!! $TempDir/$newtFile.log error no such file",100);
		return;
	}
	$t=time();
	sargToFile("$TempDir/$newtFile.log");
	progress("$filename ($storeid) done ".$unix->distanceOfTimeInWords($t,time()));
	backup();
	
}

function execute(){
	$nice=EXEC_NICE();
	if(is_file(dirname(__FILE__)."/exec.sarg.gilou.php")){
		events("Executing exec.sarg.gilou.php instead...");
		shell_exec($nice.LOCATE_PHP5_BIN2()." ".dirname(__FILE__)."/exec.sarg.gilou.php --exec");
		return;
	}
	$sock=new sockets();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$nice=EXEC_NICE();
	$unix=new unix();
	$today=date("d/m/Y");
	$sarg_bin=$unix->find_program("sarg");
	if(!is_file($sarg_bin)){
		sarg_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
		return;
	}
	events("Building settings..");
	buildconf();
	
	$usersauth=false;
	
	$squid=new squidbee();
	if($squid->LDAP_AUTH==1){$usersauth=true;}
	if($squid->LDAP_EXTERNAL_AUTH==1){$usersauth=true;}
	
	if(!is_file("/etc/squid/exclude_codes")){@file_put_contents("/etc/squid/exclude_codes","\nNONE/400\n");}
	@mkdir("$SargOutputDir",0755,true);
	
	if($usersauth){
		events("User authentification enabled");
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, user authentification enabled\n";
		$u=" -i ";
	}else{
		events("User authentification disabled");
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, user authentification disabled\n";
	}
	$cmd="$nice$sarg_bin -d {$today}-{$today} $u-f /etc/squid3/sarg.conf -l /var/log/squid/access.log -o \"$SargOutputDir\" -x -z 2>&1";
	$t1=time();
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $cmd\n";
	exec($cmd,$results);
	

	
	while (list ($index, $line) = each ($results) ){
		if(preg_match("#SARG: No records found#",$line)){
			events("No records found");
			$subject_add="(No records found)";}
		
		if(preg_match("#SARG:\s+.+?mixed records format#",$line)){
			send_email_events("SARG: Error, squid was reloaded",
			"It seems that there is a mixed log file format detected in squid
			This reason is Artica change squid log format from orginial to http access mode.
			In this case, the log will be moved and squid will be reloaded 
			in order to build a full log file with only one log format.
			\n".@implode("\n",$results),"proxy");
			shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.squid.php --reconfigure");
			shell_exec($unix->LOCATE_SQUID_BIN() ." -k rotate");
			shell_exec("/etc/init.d/auth-tail restart >/dev/null 2>&1");
			shell_exec("/etc/init.d/cache-tail restart >/dev/null 2>&1");
			
			return;
			}
		
		if(preg_match("#SARG:\s+.+?enregistrements de plusieurs formats#",$line)){
			send_email_events("SARG: Error, squid was reloaded",
			"It seems that there is a mixed log file format detected in squid
			This reason is Artica change squid log format from orginial to http access mode.
			In this case, the log will be moved and squid will be reloaded 
			in order to build a full log file with only one log format.
			\n".@implode("\n",$results),"proxy");
			shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.squid.php --reconfigure");
			shell_exec($unix->LOCATE_SQUID_BIN() ." -k rotate");
			shell_exec("/etc/init.d/auth-tail restart >/dev/null 2>&1");
			shell_exec("/etc/init.d/cache-tail restart >/dev/null 2>&1");
			return;
			}
			
		if(preg_match("#SARG.+?Unknown input log file format#",$line)){
			send_email_events("SARG: \"Unknown input log file format\", squid was reloaded",
			"It seems that there is a input log file format log file format detected in squid
			This reason is Artica change squid log format from orginial to log_fqn on, this will be disabled
			In this case, the log will be moved and squid will be reloaded 
			in order to build a full log file with only one log format.
			\n".@implode("\n",$results),"proxy");
			shell_exec(LOCATE_PHP5_BIN2()." ". dirname(__FILE__)."/exec.squid.php --reconfigure");
			shell_exec($unix->LOCATE_SQUID_BIN() ." -k rotate");
			shell_exec("/etc/init.d/auth-tail restart >/dev/null 2>&1");
			shell_exec("/etc/init.d/cache-tail restart >/dev/null 2>&1");
			return;
			}
	}
	$NICE=EXEC_NICE();
	$unix=new unix();
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, lighttpd user: $lighttpd_user\n";
	$chown=$unix->find_program("chown");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},$chown -R $lighttpd_user:$lighttpd_user $SargOutputDir/*\n";
	exec("$chown -R $lighttpd_user:$lighttpd_user $SargOutputDir/* >/dev/null 2>&1",$results2);	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]},\n". @implode("\n".$results2)."\n";
	
	shell_exec("$nohup $php ".__FILE__." --backup >/dev/null 2>&1 &");
	
	$t2=time();
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, $distanceOfTimeInWords\n";
	events("Statistics generated ($distanceOfTimeInWords)");
	if($GLOBALS["VERBOSE"]){
		echo "SARG: Statistics generated ($distanceOfTimeInWords)\n\n";
		echo @implode("\n",$results)."\n";
		
	}
	status(true);
	sarg_admin_events("SARG: Statistics generated ($distanceOfTimeInWords) $subject_add","Command line:\n-----------\n$cmd\n".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__,"sarg");
	}

function backup(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: nginx Already Artica task running PID $pid since {$time}mn\n";
		return;
	}
	
	$time=$unix->file_time_min($pidTime);
	if($time<60){return;}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, getmypid());
	
	
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$BackupSargUseNas=$sock->GET_INFO("BackupSargUseNas");
	if(!is_numeric($BackupSargUseNas)){$BackupSargUseNas=0;}
	$nice=EXEC_NICE();
	$mount=new mount("/var/log/sarg-exec.log");
	if($BackupSargUseNas==0){return;}
	
	$BackupSargNASIpaddr=$sock->GET_INFO("BackupSargNASIpaddr");
	$BackupSargNASFolder=$sock->GET_INFO("BackupSargNASFolder");
	$BackupSargNASUser=$sock->GET_INFO("BackupSargNASUser");
	$BackupSargNASPassword=$sock->GET_INFO("BackupSargNASPassword");
	$mountPoint="/mnt/BackupSargUseNas";
	if(!$mount->smb_mount($mountPoint,$BackupSargNASIpaddr,$BackupSargNASUser,$BackupSargNASPassword,$BackupSargNASFolder)){
		sarg_admin_events("SARG: Unable to connect to NAS storage system: $BackupSargNASUser@$BackupSargNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"sarg");
		return;
	}
	$BackupDir="$mountPoint/sarg";
	
	@mkdir("$BackupDir",0755);
	if(!is_dir($BackupDir)){
		if($GLOBALS["VERBOSE"]){echo "FATAL $BackupDir permission denied\n";}
		sarg_admin_events("FATAL $BackupDir permission denied",__FUNCTION__,__FILE__,__LINE__,"sarg");
		$mount->umount($mountPoint);
		return false;
	}	

	$t=time();
	@file_put_contents("$BackupDir/$t", time());
	if(!is_file("$BackupDir/$t")){
		sarg_admin_events("FATAL $BackupDir permission denied",__FUNCTION__,__FILE__,__LINE__,"sarg");
		$mount->umount($mountPoint);
		return false;
	}
	@unlink("$BackupDir/$t");		
	$cp=$unix->find_program("cp");
	shell_exec(trim("$nice $cp -dpR $SargOutputDir/* $BackupDir/"));
	$mount->umount($mountPoint);
	sarg_admin_events("Copy to $BackupSargNASIpaddr/$BackupSargNASFolder done",__FUNCTION__,__FILE__,__LINE__,"sarg");
		
	
}

function status($aspid=false){
	
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$filetime="/etc/artica-postfix/settings/Daemons/SargDirStatus";
	$sock=new sockets();
	
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/var/www/html/squid-reports";}
	$SIZE=$unix->DIRSIZE_KO($SargOutputDir);
	$FILES=$unix->DIR_COUNT_OF_FILES($SargOutputDir);
	$AIV=$unix->DIRECTORY_FREEM($SargOutputDir);
	$array["SIZE"]=$SIZE;
	$array["FILES"]=$FILES;
	$array["FREE"]=$AIV;
	$array["F_FREE"]=$unix->DIRECTORY_FREE_FILES($SargOutputDir);
	@unlink($filetime);
	@file_put_contents($filetime, serialize($array));
	@chmod($filetime, 0755);
}


?>