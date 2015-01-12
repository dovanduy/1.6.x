<?php
if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.services.inc');



$unix=new unix();

$dirs=$unix->DirFiles($argv[1]);


while (list ($num, $ligne) = each ($dirs) ){
	$unix->uncompress($argv[1]."/$num", $argv[1]."/$num.log");
	
	
}


