<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
$GLOBALS["LOGFILE"]="/var/log/sarg-exec.log";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"");
	$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

$sock=new sockets();
$EnableSargGenerator=$sock->GET_INFO("EnableSargGenerator");
if(!is_numeric($EnableSargGenerator)){$EnableSargGenerator=0;}
if($EnableSargGenerator==0){
	ufdbguard_admin_events("SARG IS DISABLED BY EnableSargGenerator", "MAIN", __FILE__, __LINE__, "sarg");
	if($GLOBALS["VERBOSE"]){echo "SARG IS DISABLED BY EnableSargGenerator\n";}
	die();
}
if($argv[1]=="--exec-daily"){execute_daily();exit;}
if($argv[1]=="--exec-monthly"){execute_monthly();exit;}
if($argv[1]=="--exec-weekly"){execute_weekly();exit;}
if($argv[1]=="--exec-hourly"){execute_hourly();exit;}


if($argv[1]=="--exec"){execute();die();}
if($argv[1]=="--backup"){backup();die();}
if($argv[1]=="--conf"){buildconf();die();}
if($argv[1]=="--restore-id"){restore_id($argv[2]);die();}
if($argv[1]=="--rotate"){rotate($argv[2]);die();}





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


function buildconf(){
	
	$sock=new sockets();
	$unix=new unix();
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}
	if($unix->IsProtectedDirectory($SargOutputDir,true)){
		$sock->SET_INFO("SargOutputDir", "/usr/share/artica-postfix/squid");
		$SargOutputDir="/usr/share/artica-postfix/squid";
	}
	
	if(!is_file("/etc/artica-postfix/old_SargOutputDir")){
		@file_put_contents("/etc/artica-postfix/old_SargOutputDir", $SargOutputDir);
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
	$conf[]="access_log /var/log/squid/sarg.log";
	$conf[]="realtime_access_log_lines 5000";
	$conf[]="graph_days_bytes_bar_color orange";
	$conf[]="";	

	
@file_put_contents("/etc/squid3/sarg.conf",@implode("\n",$conf));
echo "Starting......: Sarg, sarg.conf done\n";
events("/etc/squid3/sarg.conf done");

$ips[]="127.0.0.1";
$ips[]="localhost";


@file_put_contents("/etc/squid3/sarg.hosts",@implode("\n",$ips));
if($GLOBALS["VERBOSE"]){"/etc/squid3/sarg.hosts done\n";}
echo "Starting......: Sarg, sarg.hosts done\n";
// $sock=new sockets();$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}

if(!is_file("$SargOutputDir/sarg.css")){
	if($GLOBALS["VERBOSE"]){"$SargOutputDir/sarg.css done\n";}
	@copy("/usr/share/artica-postfix/bin/install/sarg.css","$SargOutputDir/sarg.css");
}

if(!is_file("/usr/share/artica-postfix/squid/logo.gif")){
	@copy("/usr/share/artica-postfix/img/logo-artica-160.gif", "$SargOutputDir/logo.gif");
}

if(!is_file("/usr/share/artica-postfix/squid/pattern.png")){
	@copy("/usr/share/artica-postfix/css/images/pattern.png", "$SargOutputDir/pattern.png");
}



$unix=new unix();
$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();

echo "Starting......: Apache user: $lighttpd_user\n";
@chown("/usr/share/artica-postfix/squid/sarg.css",$lighttpd_user);
echo "Starting......: Sarg, css done\n";
	$nice=EXEC_NICE();
	$unix=new unix();
	$sarg_bin=$unix->find_program("sarg");
	$squidbin=$unix->find_program("squid");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
	if(!is_file($sarg_bin)){
		ufdbguard_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
		return;
	}
unset($f);
$f[]="#!/bin/bash";
$f[]="#Get current date";
$f[]="TODAY=\$(date +%d/%m/%Y)"; 
$f[]="YESTERDAY=\$(date --date \"1 day ago\" +%d/%m/%Y)"; 
$f[]="mkdir -p \"$SargOutputDir/daily\"";
$f[]="chown -R  $lighttpd_user:$lighttpd_user \"$SargOutputDir/daily\"";
$f[]="NAAT=\"/var/www-naat/html/genfiles/modules/squid-reports/daily\"";
$f[]="if [ -d \${NAAT} ]; then";
$f[]="    chown -R $lighttpd_user \${NAAT}";
$f[]="fi";
$f[]="export LC_ALL=C";
$f[]="$nice$sarg_bin -f /etc/squid3/sarg.conf -l /var/log/squid/sarg.log -o \"$SargOutputDir/daily\" -z -d \$YESTERDAY-\$TODAY -x";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
$f[]="";
@file_put_contents("/bin/sarg-daily.sh", @implode("\n",$f));
@chmod("/bin/sarg-daily.sh",0755);
events("cron.daily done");
echo "Starting......: Sarg, cron cron.daily done\n";
unset($f);

$f[]="#!/bin/bash";
$f[]="#Get current date";
$f[]="TODAY=\$(date +%d/%m/%Y)"; 
$f[]="LASTHOUR=\$(date +%H -d \"1 hour ago\")";
$f[]="HOUR=\$(date +%H)";
$f[]="mkdir -p \"$SargOutputDir/hourly\"";
$f[]="chown -R  $lighttpd_user:$lighttpd_user \"$SargOutputDir/hourly\"";
$f[]="NAAT=\"/var/www-naat/html/genfiles/modules/squid-reports/hourly\"";
$f[]="if [ -d \${NAAT} ]; then";
$f[]="    chown -R $lighttpd_user \${NAAT}";
$f[]="fi";
$f[]="export LC_ALL=C";
$f[]="CMD=\"$nice$sarg_bin -f /etc/squid3/sarg.conf -l /var/log/squid/sarg.log -o \"$SargOutputDir/hourly\" -z -d \$TODAY-\$TODAY -t \$LASTHOUR:00-\$HOUR:00\"";
$f[]="\$CMD";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
$f[]="";
@file_put_contents("/bin/sarg-hourly.sh", @implode("\n",$f));
@chmod("/bin/sarg-hourly.sh",0755);
events("cron hourly done");
echo "Starting......: Sarg, cron hourly done\n";
unset($f);


$f[]="#!/bin/bash";
$f[]="if [ \$cnt -eq 4 ]; then";
$f[]="#Get yesterday date";
$f[]="YESTERDAY=\$(date --date \"1 day ago\" +%d/%m/%Y)";
$f[]="";
$f[]="#Get 4 weeks ago date";
$f[]="WEEKSAGO=\$(date --date \"4 weeks ago\" +%d/%m/%Y)";
$f[]="";
$f[]="mkdir -p  \"$SargOutputDir/monthly\"";
$f[]="#chown -R $lighttpd_user \"$SargOutputDir/monthly\"";
$f[]="";
$f[]="#NAAT=\"/var/www-naat/html/genfiles/modules/squid-reports/monthly \"";
$f[]="#if [ -d \${NAAT} ]; then";
$f[]="#    chown -R $lighttpd_user \${NAAT}";
$f[]="#fi";
$f[]="";
$f[]="export LC_ALL=C";
$f[]="$nice$sarg_bin -f /etc/squid3/sarg.conf -l /var/log/squid/sarg.log -o \"$SargOutputDir/monthly\" -d \$WEEKSAGO-\$YESTERDAY > /dev/null 2>&1";
$f[]="";
$f[]="/usr/sbin/squid -k rotate";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
$f[]="";
$f[]="#don't move next line to upper, reason is that sed change the cnt assignment of the first 7 lines";
$f[]="cnt=1";
$f[]="else";
$f[]="let cnt++";
$f[]="fi";
$f[]="#echo Will rename itself \(\$0\) with cnt \(\$cnt\) increased. 1>&2";
$f[]="sargtmp=/var/tmp/`basename \$0`";
$f[]="sed \"1,7s/^cnt=.*/cnt=\$cnt/";
$f[]="\" \$0 >|\$sargtmp";
$f[]="chmod -f 775 \$sargtmp";
$f[]="mv -f \$sargtmp \$0";

@file_put_contents("/bin/sarg-monthly.sh", @implode("\n",$f));
@chmod("/bin/sarg-monthly.sh",0755);

unset($f);
$f[]="#!/bin/bash";
$f[]="$php5 ".__FILE__." --exec-monthly\n";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
@file_put_contents("/etc/cron.monthly/0sarg",@implode("\n",$f));
@chmod("/etc/cron.monthly/0sarg",0755);
events("cron.monthly done");
echo "Starting......: Sarg, cron cron.monthly done\n";



unset($f);
$f[]="#!/bin/bash";
$f[]="";
$f[]="#Get current date";
$f[]="TODAY=\$(date +%d/%m/%Y) ";
$f[]="";
$f[]="#Get one week ago today";
$f[]="LASTWEEK=\$(date --date \"1 week ago\" +%d/%m/%Y)";
$f[]="";
$f[]="mkdir -p \"$SargOutputDir/weekly\"";
$f[]="chown -R $lighttpd_user:$lighttpd_user \"$SargOutputDir/weekly\"";
$f[]="";
$f[]="NAAT=\"/var/www-naat/html/genfiles/modules/squid-reports/weekly\"";
$f[]="if [ -d \${NAAT} ]; then";
$f[]="    chown -R $lighttpd_user \${NAAT}";
$f[]="fi";
$f[]="";
$f[]="export LC_ALL=C";
$f[]="$nice$sarg_bin -f /etc/squid3/sarg.conf -l /var/log/squid/sarg.log -o \"$SargOutputDir/weekly\" -z -d \$LASTWEEK-\$TODAY >/dev/null 2>";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
$f[]="";
@file_put_contents("/bin/sarg-weekly.sh",@implode("\n",$f));
@chmod("/bin/sarg-weekly.sh",0755);


unset($f);
$f[]="#!/bin/bash";
$f[]="$php5 ".__FILE__." --exec-weekly\n";
$f[]="$nohup $nice $php5 ".__FILE__." --backup >/dev/null 2>&1 &";
@file_put_contents("/etc/cron.weekly/0sarg",@implode("\n",$f));
@chmod("/etc/cron.weekly/0sarg",0755);
events("cron.weekly done");
echo "Starting......: Sarg, cron cron.weekly done\n";



}

function build_index_page(){
$sock=new sockets();$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}
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
}
if(is_file("$SargOutputDir/daily/index.html")){
	$f[]="<tr><td align='center'><a href=\"daily/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Daily reports&nbsp;&raquo;</td></tr>";
}	
if(is_file("$SargOutputDir/weekly/index.html")){
	$f[]="<tr><td align='center'><a href=\"weekly/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Weekly reports&nbsp;&raquo;</td></tr>";
}
if(is_file("$SargOutputDir/monthly/index.html")){
	$f[]="<tr><td align='center'><a href=\"monthly/index.html\" style='font-size:22px;font-weight:bold'>&laquo;&nbsp;Monthly reports&nbsp;&raquo;</td></tr>";
}

$f[]="</table>
</body>
</html>";
events("$SargOutputDir/index.html done");
events("$SargOutputDir/index.php done");
@file_put_contents("$SargOutputDir/index.html", @implode("\n", $f));
@file_put_contents("$SargOutputDir/index.php","<?php\nheader('location:index.html')\n?>");
}


function execute_monthly(){
	$unix=new unix();
	$t=time();
	buildconf();
	if(!is_file("/bin/sarg-monthly.sh")){
		ufdbguard_admin_events("Monthly report Failed /bin/sarg-monthly.sh no such script".
	null, __FUNCTION__, __FILE__, __LINE__, "sarg");
	return;}
	exec("/bin/sarg-monthly.sh 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Monthly report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
}
function execute_weekly(){
	$unix=new unix();
	$t=time();
	buildconf();
	if(!is_file("/bin/sarg-weekly.sh")){ufdbguard_admin_events("Weekly report Failed /bin/sarg-weekly.sh no such script", __FUNCTION__, __FILE__, __LINE__, "sarg");
	return;}
	exec("/bin/sarg-weekly.sh 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Weekly report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");
	build_index_page();
}
function execute_daily(){
	$unix=new unix();
	$t=time();
	buildconf();
	if(!is_file("/bin/sarg-daily.sh")){ufdbguard_admin_events("Daily report Failed /bin/sarg-daily.sh no such script", __FUNCTION__, __FILE__, __LINE__, "sarg");
	return;}
	if($GLOBALS["VERBOSE"]){echo "EXEC: /bin/sarg-daily.sh\n";}
	exec("/bin/sarg-daily.sh 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Daily report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");	
	build_index_page();
}

function execute_hourly(){
	$unix=new unix();
	$t=time();
	buildconf();
	if(!is_file("/bin/sarg-hourly.sh")){ufdbguard_admin_events("Daily report Failed /bin/sarg-hourly.sh no such script", __FUNCTION__, __FILE__, __LINE__, "sarg");
	return;}
	if($GLOBALS["VERBOSE"]){echo "EXEC: /bin/sarg-hourly.sh\n";}
	exec("/bin/sarg-hourly.sh 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time());
	ufdbguard_admin_events("Daily report generated took: $took\n".@implode("\n",$results), __FUNCTION__, __FILE__, __LINE__, "sarg");	
	build_index_page();	
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
	if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}	
	$nice=EXEC_NICE();
	$usersauth=false;
	
	$squid=new squidbee();
	if($squid->LDAP_AUTH==1){$usersauth=true;}
	if($squid->LDAP_EXTERNAL_AUTH==1){$usersauth=true;}
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
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){
			progress("Ask squid to rotate",10);
			shell_exec("$squidbin -k rotate");
		}
	}
	
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
		ufdbguard_admin_events("FATAL, ($filePath) unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
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
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}
	$sarg_bin=$unix->find_program("sarg");
	if(!is_file($sarg_bin)){
		ufdbguard_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
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
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}
	$nice=EXEC_NICE();
	$unix=new unix();
	$today=date("d/m/Y");
	$sarg_bin=$unix->find_program("sarg");
	if(!is_file($sarg_bin)){
		ufdbguard_admin_events("FATAL, unable to locate sarg binary, aborting...", __FUNCTION__, __FILE__, __LINE__, "sarg");
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
		echo "Starting......: Sarg, user authentification enabled\n";
		$u=" -i ";
	}else{
		events("User authentification disabled");
		echo "Starting......: Sarg, user authentification disabled\n";
	}
	$cmd="$nice$sarg_bin -d {$today}-{$today} $u-f /etc/squid3/sarg.conf -l /var/log/squid/sarg.log -o \"$SargOutputDir\" -x -z 2>&1";
	$t1=time();
	echo "Starting......: Sarg, $cmd\n";
	exec($cmd,$results);
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		progress("Ask squid to rotate",10);
		shell_exec("$squidbin -k rotate");
	}
	
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
			shell_exec("/etc/init.d/artica-postfix restart squid-tail");
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
			shell_exec("/etc/init.d/artica-postfix restart squid-tail");
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
			shell_exec("/etc/init.d/artica-postfix restart squid-tail");
			return;
			}
	}
	$NICE=EXEC_NICE();
	$unix=new unix();
	$lighttpd_user=$unix->APACHE_SRC_ACCOUNT();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	echo "Starting......: Sarg, lighttpd user: $lighttpd_user\n";
	$chown=$unix->find_program("chown");
	echo "Starting......: Sarg,$chown -R $lighttpd_user:$lighttpd_user $SargOutputDir/*\n";
	exec("$chown -R $lighttpd_user:$lighttpd_user $SargOutputDir/* >/dev/null 2>&1",$results2);	
	echo "Starting......: Sarg,\n". @implode("\n".$results2)."\n";
	
	shell_exec("$nohup $php ".__FILE__." --backup >/dev/null 2>&1 &");
	
	$t2=time();
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);
	echo "Starting......: Sarg, $distanceOfTimeInWords\n";
	events("Statistics generated ($distanceOfTimeInWords)");
	if($GLOBALS["VERBOSE"]){
		
		echo "SARG: Statistics generated ($distanceOfTimeInWords)\n\n";
		echo @implode("\n",$results)."\n";
		
	}
	ufdbguard_admin_events("SARG: Statistics generated ($distanceOfTimeInWords) $subject_add","Command line:\n-----------\n$cmd\n".@implode("\n",$results),__FUNCTION__,__FILE__,__LINE__,"sarg");
	}

function backup(){
	$sock=new sockets();
	$unix=new unix();
	
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$oldpid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($oldpid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($oldpid);
		echo "Starting......: [INIT]: nginx Already Artica task running PID $oldpid since {$time}mn\n";
		return;
	}
	
	$time=$unix->file_time_min($pidTime);
	if($time<60){
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	@unlink($pidTime);
	@file_put_contents($pidTime, getmypid());
	
	
	$SargOutputDir=$sock->GET_INFO("SargOutputDir");
	if($SargOutputDir==null){$SargOutputDir="/usr/share/artica-postfix/squid";}
	$BackupSargUseNas=$sock->GET_INFO("BackupSargUseNas");
	if(!is_numeric($BackupSargUseNas)){$BackupSargUseNas=0;}
	$nice=EXEC_NICE();
	$mount=new mount("/var/log/sarg-exec.log");
	if($BackupSargUseNas==1){
		$BackupSargNASIpaddr=$sock->GET_INFO("BackupSargNASIpaddr");
		$BackupSargNASFolder=$sock->GET_INFO("BackupSargNASFolder");
		$BackupSargNASUser=$sock->GET_INFO("BackupSargNASUser");
		$BackupSargNASPassword=$sock->GET_INFO("BackupSargNASPassword");
		$mountPoint="/mnt/BackupSargUseNas";
		if(!$mount->smb_mount($mountPoint,$BackupSargNASIpaddr,$BackupSargNASUser,$BackupSargNASPassword,$BackupSargNASFolder)){
			ufdbguard_admin_events("SARG: Unable to connect to NAS storage system: $BackupSargNASUser@$BackupSargNASIpaddr",__FUNCTION__,__FILE__,__LINE__,"sarg");
			return;
		}
		$BackupDir="$mountPoint/sarg";
		
		@mkdir("$BackupDir",0755);
		if(!is_dir($BackupDir)){
			if($GLOBALS["VERBOSE"]){echo "FATAL $BackupDir permission denied\n";}
			ufdbguard_admin_events("FATAL $BackupDir permission denied",__FUNCTION__,__FILE__,__LINE__,"sarg");
			$mount->umount($mountPoint);
			return false;
		}	

		$t=time();
		@file_put_contents("$BackupDir/$t", time());
		if(!is_file("$BackupDir/$t")){
			ufdbguard_admin_events("FATAL $BackupDir permission denied",__FUNCTION__,__FILE__,__LINE__,"sarg");
			$mount->umount($mountPoint);
			return false;
		}
		@unlink("$BackupDir/$t");		
		$cp=$unix->find_program("cp");
		shell_exec(trim("$nice $cp -dpR $SargOutputDir/* $BackupDir/"));
		$mount->umount($mountPoint);
		ufdbguard_admin_events("Copy to $BackupSargNASIpaddr/$BackupSargNASFolder done",__FUNCTION__,__FILE__,__LINE__,"sarg");
		
	}	
}
?>