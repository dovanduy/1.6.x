<?php
//http://ftp.linux.org.tr/slackware/slackware_source/n/network-scripts/scripts/netconfig
if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/framework/class.unix.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");

if($argv[1]=="--menu"){menu();exit;}
if($argv[1]=="--restore"){restore();exit;}

function update_find_latest_nightly(){

	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$MAIN=$array["NIGHT"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function update_find_latest(){

	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$MAIN=$array["OFF"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}
function menu(){
	$sock=new sockets();
$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
$unix=new unix();
$HOSTNAME=$unix->hostname_g();
$DIALOG=$unix->find_program("dialog");	
$php=$unix->LOCATE_PHP5_BIN();
$FILENAME="/tmp/bash_update_menu.sh";
$unix=new unix();
$freshclam=$unix->find_program("freshclam");
$nohup=$unix->find_program("nohup");
$EnableArticaMetaServer=intval($sock->GET_INFO("EnableArticaMetaServer"));
$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
$INFOSMETA=null;

if($EnableArticaMetaServer==1){
	$INFOSMETA="\\nThis server is connected to the Meta server\\nPlease refer to the meta server to update firmware.";
	
}

$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
$diag[]="--title \"[ S N A P S H O T S  M E N U ]\"";
$diag[]="--menu \"{$INFOSMETA}You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
if(is_file($freshclam)){
$diag[]="ClamAV \"Update Clamav pattern databases\"";
$diag[]="ClamStat \"Clamav pattern databases status\"";
}

if($EnableArticaMetaServer==0){
	$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
	$OFFICIALS=$array["OFF"];
	$key=update_find_latest();
	$Lastest=$OFFICIALS[$key]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key]["URL"];
	$MAIN_MD5=$OFFICIALS[$key]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
	$diag[]="FirmOFF \"Update to Official version $Lastest\"";
	
	if($ArticaAutoUpateNightly==1){
		
		$array=unserialize(@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaUpdateRepos"));
		$OFFICIALS=$array["NIGHT"];
		$key=update_find_latest_nightly();
		$MyNextVersion=$key;
		$Lastest=$OFFICIALS[$key]["VERSION"];
		$MAIN_URI=$OFFICIALS[$key]["URL"];
		$MAIN_MD5=$OFFICIALS[$key]["MD5"];
		$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
		$uri=$MAIN_URI;
		$Lastest=trim(strtolower($Lastest));
		$diag[]="FirmNIGHT \"Update to Intermediate version $Lastest\"";
		
	
	}
	
	
}

$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";

$f[]="#!/bin/bash";
$f[]="INPUT=/tmp/menu.sh.$$";
$f[]="OUTPUT=/tmp/output.sh.$$";
$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
$f[]="DIALOG=\${DIALOG=dialog}";

$f[]="function ClamAV(){";
$f[]="\t/usr/bin/nohup $php /usr/share/artica-postfix/exec.freshclam.php --execute --force --progress --cli >/tmp/dns.log 2>&1 &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="}";
$f[]="";
$f[]="function ClamStatp(){";
$f[]="\tTEXT_CLAM=`$php /usr/share/artica-postfix/exec.freshclam.php --sigtool-ouput 2>&1`";
$f[]="\techo \$TEXT_CLAM";
$f[]="\t$DIALOG --title \"ClamAV Pattern Databases status\" --msgbox \"\$TEXT_CLAM\" 30 70";
$f[]="}";
$f[]="function FirmOFF(){";
$f[]="\t/usr/bin/nohup $php /usr/share/artica-postfix/exec.nightly.php --force --progress --cli >/tmp/dns.log 2>&1 &";
$f[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
$f[]="}";




$f[]="while true";
$f[]="do";
$f[]=@implode(" ", $diag);
$f[]="menuitem=$(<\"\${INPUT}\")";
$f[]="case \$menuitem in";
$f[]="ClamAV) ClamAV;;";
$f[]="ClamStat) ClamStatp;;";
$f[]="FirmOFF) FirmOFF;;";
$f[]="FirmNIGHT) FirmOFF;;";

$f[]="Quit) break;;";
$f[]="esac";
$f[]="done\n";

if($GLOBALS["VERBOSE"]){echo "Writing $FILENAME\n";}
@file_put_contents("$FILENAME", @implode("\n",$f));
@chmod("$FILENAME",0755);
	
}


function restore(){
	$ARTICAVERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$unix=new unix();
	$HOSTNAME=$unix->hostname_g();
	$DIALOG=$unix->find_program("dialog");
	$php=$unix->LOCATE_PHP5_BIN();
	$q=new mysql();
	$table="snapshots";
	$database="artica_snapshots";
	
	$sql="SELECT * FROM $table";
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	if(mysql_num_rows($results)==0){
		$f[]="#!/bin/bash";
		$f[]="INPUT=/tmp/menu.sh.$$";
		$f[]="OUTPUT=/tmp/output.sh.$$";
		$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
		$f[]="DIALOG=\${DIALOG=dialog}";
		$f[]="\t$DIALOG --title \"R E S T O R E\" --msgbox \"Sorry, no snapshot created\" 9 70";
		if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_snapshots_restore.sh\n";}
		@file_put_contents("/tmp/bash_snapshots_restore.sh", @implode("\n",$f));
		@chmod("/tmp/bash_snapshots_restore.sh",0755);
		return;
	}
	
	
	
	$diag[]="$DIALOG --clear  --nocancel --backtitle \"Software version $ARTICAVERSION on $HOSTNAME\"";
	$diag[]="--title \"[ S N A P S H O T S  - R E S T O R E - M E N U ]\"";
	$diag[]="--menu \"You can use the UP/DOWN arrow keys\nChoose the TASK\" 20 100 10";
	$tpl=new templates();
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$xdate=$ligne["zDate"];
		$xtime=strtotime($xdate);
		$date=$tpl->time_to_date($xtime,true);
		$diag[]="Restore$ID \"Restore $xdate - $date\"";
		$funcs[]="function restore_$ID(){";
		$funcs[]="\t$DIALOG --title \"Restore a Snapshot\" --yesno \"Do you need to restore $xdate - $date operation ? Press 'Yes' to continue, or 'No' to exit\" 0 0";
		$funcs[]="\tcase $? in";
		$funcs[]="\t\t0)";
		$funcs[]="\tif [ -f /tmp/dns.log ]; then";
		$funcs[]="\t\trm /tmp/dns.log";
		$funcs[]="\tfi";
		$funcs[]="\t$php /usr/share/artica-postfix/exec.backup.artica.php --snapshot-id $ID >/tmp/dns.log &";
		$funcs[]="\t$DIALOG --tailbox /tmp/dns.log  25 150";
		$funcs[]="\t\treturn;;";
		$funcs[]="\t1)";
		$funcs[]="\t\treturn;;";
		$funcs[]="\t255)";
		$funcs[]="\t\treturn;;";
		$funcs[]="\tesac";
		$funcs[]="}";
		$funcs[]="";
		$cases[]="Restore$ID) restore_$ID;;";
		
		
	}
	

	$diag[]="Quit \"Return to main menu\" 2>\"\${INPUT}\"";
	
	$f[]="#!/bin/bash";
	$f[]="INPUT=/tmp/menu.sh.$$";
	$f[]="OUTPUT=/tmp/output.sh.$$";
	$f[]="trap \"rm \$OUTPUT; rm \$INPUT; exit\" SIGHUP SIGINT SIGTERM";
	$f[]="DIALOG=\${DIALOG=dialog}";
	
	@implode("\n", $funcs);

	
	$f[]="while true";
	$f[]="do";
	$f[]=@implode(" ", $diag);
	$f[]="menuitem=$(<\"\${INPUT}\")";
	$f[]="case \$menuitem in";
	$f[]=@implode("\n", $cases);
	$f[]="Quit) break;;";
	$f[]="esac";
	$f[]="done\n";
	
	if($GLOBALS["VERBOSE"]){echo "Writing /tmp/bash_snapshots_restore.sh\n";}
	@file_put_contents("/tmp/bash_snapshots_restore.sh", @implode("\n",$f));
	@chmod("/tmp/bash_snapshots_restore.sh",0755);	
	
	
	
}




