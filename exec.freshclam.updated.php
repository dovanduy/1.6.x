#!/usr/bin/php -q
<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["TITLENAME"]="Clam AntiVirus virus database updater";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


$sock=new sockets();
@unlink("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");
$sock->getFrameWork("clamav.php?sigtool=yes");

$bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));


$DBS[]="<table style='width:100%; -webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;border:2px solid #CCCCCC'>
		<tr style='height:80px'>
					<td style='font-size:26px' colspan=2><strong>{clamav_antivirus_patterns_status}</td>
			</tr>";
while (list ($db, $MAIN) = each ($bases) ){
	
	$f[]="$db - {$MAIN["zDate"]} - {$MAIN["version"]} {$MAIN["signatures"]} signatures";
	
}

squid_admin_mysql(2, "New ClamAV databases downloaded", @implode("\n", $f),__FILE__,__LINE__);
shell_exec("/etc/init.d/clamav-daemon reload");
?>
