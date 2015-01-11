<?php
$GLOBALS["VERBOSE"]=true;
$GLOBALS["DEBUG_INCLUDES"]=true;
$GLOBALS["DEBUG_LANG"]=true;
$GLOBALS["DEBUG_MEM"]=true;
$GLOBALS["DEBUG_PROCESS"]=true;
include_once(dirname(__FILE__)."/ressources/logs.inc");
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
echo "<H1>Nothing loaded...</H1>\n";
echo "<H1>include class.templates.inc</H1>\n";
include_once(dirname(__FILE__)."/ressources/class.templates.inc");



$url="http://cdn.api.twitter.com/1/urls/count.json?url=http://www.dailymotion.com/video/x1aqcxp_maitre-gims-zombie-clip-officiel_music&callback=twttr.receiveCount)";

$main=parse_url($url);
if(preg_match("#\/video\/(.+?)&#", $main["query"],$re)){echo $re[1];}




//$tpl=new templates();

?>