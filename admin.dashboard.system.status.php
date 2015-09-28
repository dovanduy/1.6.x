<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
if(!$GLOBALS["AS_ROOT"]){session_start();}
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');


page();


function page(){
	
	$time=time();

	if(is_file("ressources/logs/web/INTERFACE_LOAD_AVG.db")){
		$f1[]="<div style='width:1500px;height:240px' id='$time-2'></div>";
		$f2[]="function FDeux$time(){
		AnimateDiv('$time-2');
		Loadjs('admin.index.loadvg.php?graph2=yes&container=$time-2',true);
	}
	setTimeout(\"FDeux$time()\",500);";
	}
	
	
	if(is_file("ressources/logs/web/cpustats.db")){
		$f1[]="<div style='width:1500px;height:240px' id='$time-cpustats'></div>";
		$f2[]="function Fcpustats$time(){AnimateDiv('$time-cpustats');Loadjs('admin.index.loadvg.php?cpustats=yes&container=$time-cpustats',true);} setTimeout(\"Fcpustats$time()\",500);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/cpustats.db no such file</H1>\n";}
	}
	
	
	
	if(is_file("ressources/logs/web/INTERFACE_LOAD_AVG2.db")){
		$f1[]="<div style='width:1500px;height:240px' id='$time-1'></div>";
		$f2[]="function FOne$time(){AnimateDiv('$time-1');Loadjs('admin.index.loadvg.php?graph1=yes&container=$time-1',true);} setTimeout(\"FOne$time()\",500);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>ressources/logs/web/INTERFACE_LOAD_AVG2.db no such file</H1>\n";}
	}
	
	
	$t=time();
	$html="<table style='width:98%'>
	<tr>
		<td valign='top' style='width:75%'><div id='left-$t'></div></td>
		<td valign='top' style='width:25%'><div id='right-$t' class=form></div></td>	
	</tr>
	</table>
	<hr>
	". @implode("\n", $f1)."
	<script>
		LoadAjaxRound('left-$t','quicklinks.php?function=section_computer_header&font-size=22&no-picture=yes&height=435');
		LoadAjaxRound('right-$t','admin.index.php?memcomputer=yes');
		". @implode("\n", $f2)."
	</script>
	
			
			
	";
	
	echo $html;
	
	
	
}


