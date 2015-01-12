<?php

$GLOBALS["ARTICALOGDIR"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/ArticaLogDir"); 
if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 

$array["zdate"]=date("Y-m-d H:i:s");
$array["subject"]="Networks was reset and reloaded.";
$array["text"]="This is a notification warning of rebooted network";
$array["severity"]=1;
$array["function"]="NONE";
$array["file"]=basename(__FILE__);
$array["line"]=__LINE__;
$array["pid"]=getmypid();
$array["TASKID"]=0;
$serialize=serialize($array);
$md5=md5($serialize);
if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/mysql_admin_mysql",0755,true);}
@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/squid_admin_mysql/$md5.log", $serialize);
