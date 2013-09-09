<?php
if(isset($_GET["verbose"])){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
$GLOBALS["KAV4PROXY_NOSESSION"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RELOAD"]=false;
$_GET["LOGFILE"]="/var/log/artica-postfix/dansguardian.compile.log";
if(posix_getuid()<>0){
	if(isset($_GET["SquidGuardWebAllowUnblockSinglePass"])){parseTemplate_SinglePassWord();die();}
	
	if(isset($_POST["USERNAME"])){parseTemplate_LocalDB_receive();die();}
	if(isset($_POST["password"])){parseTemplate_SinglePassWord_receive();die();}
	if(isset($_GET["parseTemplate-SinglePassWord-popup"])){parseTemplate_SinglePassWord_popup();die();}
	if(isset($_GET["SquidGuardWebUseLocalDatabase"])){parseTemplate_LocalDB();die();}
	parseTemplate();die();}

if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);


include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.dansguardian.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');

if($argv[1]=="download"){

// http://www.namejet.com/Download/1-01-2011.txt
$currentYear=date("Y");
$currentmonth=intval(date('m'));
$currentDay=intval(date('d'));

for ($i=1;$i<$currentmonth;$i++){
	$month=$i;
	for($y=1;$y<30;$y++){
		$day=$y;
		if(strlen($day)==1){$day="0{$day}";}
		echo "http://www.namejet.com/Download/$month-$day-$currentYear.txt\n";
		@mkdir("/home/namejet",0755,true);
		shell_exec("wget http://www.namejet.com/Download/$month-$day-$currentYear.txt -O /home/namejet/$month-$day-$currentYear.txt");
	}
	
}

die();

}


$filename=$argv[1];



if(!is_file($filename)){echo "$filename No such file\n";die();}


$handle = @fopen($filename, "r");
if (!$handle) {echo "Failed to open file\n";return;}
$q=new mysql_squid_builder();

$c=0;
$Catgorized=0;
while (!feof($handle)){
	$c++;
	$www =trim(fgets($handle, 4096));
	$www=str_replace('"', "", $www);
	$www=trim(strtolower($www));
	echo "$www ";
	$category=$q->GET_CATEGORIES($www,true,true,true);
	if($category<>null){
		$Catgorized++;
		echo " $Catgorized/$c Already categorized as $category\n";
		continue;
	}
	
	$category=$q->GET_CATEGORIES($www);
	if($category<>null){
		$Catgorized++;
		echo " $Catgorized/$c New categorized as $category {$GLOBALS["CATZWHY"]}\n";
		cloudlogs("$Catgorized/$c $www New categorized as $category {$GLOBALS["CATZWHY"]}");
		continue;
	}
	
	cloudlogs("$Catgorized/$c $www unknown");
	echo " $Catgorized/$c Unknown\n";
	
}

@fclose($handle);
@unlink($filename);


function cloudlogs($text=null){
	$logFile="/var/log/cleancloud.log";
	$time=date("Y-m-d H:i:s");
	$PID=getmypid();
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$time [$PID]: $text\n");
	@fclose($f);
}