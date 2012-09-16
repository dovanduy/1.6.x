<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--withoutloading#",implode(" ",$argv))){$GLOBALS["NO_USE_BIN"]=true;}

if($argv[1]=="--update-config"){SetKeepUp2date();die();}




function SetKeepUp2date(){
$users=new usersMenus();
$arch=$users->ArchStruct;
$f[]="[path]";
$f[]="BasesPath=/var/opt/kaspersky/kav4proxy/bases";
$f[]="MetaBasesPath=/opt/kaspersky/khse/libexec";
$f[]="LicensePath=/var/opt/kaspersky/kav4proxy/licenses";
$f[]="TempPath=/tmp";
$f[]="";
$f[]="[locale]";
$f[]="DateFormat=%d-%m-%Y";
$f[]="TimeFormat=%H:%M:%S";
$f[]="";
$f[]="[updater.path]";
$f[]="BackUpPath=/opt/kaspersky/khse/bases.backup";
$f[]="";
$f[]="[updater.options]";
$f[]="UpdateComponentsList=KHSE";
$f[]="OsFilter=Linux";
if($arch==64){
$f[]="ArchFilter=kernel:x64;user:x64;";
}
if($arch==32){
	$f[]="ArchFilter=kernel:i386;user:i386;";
}
$f[]="AppFilter=Artica 1.0.0.0";
$f[]="KeepSilent=no";
$f[]="#UpdateServerUrl=";
$f[]="UseUpdateServerUrl=no";
$f[]="UseUpdateServerUrlOnly=no";
$f[]="RegionSettings=Europe";
$f[]="ConnectTimeout=30";
$f[]="ProxyAddress=";
$f[]="UseProxy=no";
$f[]="PassiveFtp=no";
$f[]="[updater.report]";
$f[]="ReportFileName=/var/log/kaspersky/kav4proxy/keepup2date_artica.log";
$f[]="ReportLevel=4";
$f[]="Append=true";
@file_put_contents("/opt/kaspersky/khse/etc/keepup2date_artica.conf", @implode("\n", $f));

	
}