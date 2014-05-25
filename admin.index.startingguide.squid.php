<?php
/*
 ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;
$GLOBALS["VERBOSE_SYSLOG"]=true;
*/
if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
if(!$GLOBALS["AS_ROOT"]){session_start();unset($_SESSION["MINIADM"]);unset($_COOKIE["MINIADM"]);}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
$GLOBALS["AS_ROOT"]=false;
$GLOBALS["VERBOSE"]=false;
if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_PROCESS"]=true;$GLOBALS["VERBOSE_SYSLOG"]=true;}
if(isset($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}}
$GLOBALS["ICON_FAMILY"]="SYSTEM";
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}
include_once("ressources/logs.inc");
include_once('ressources/class.templates.inc');
include_once('ressources/class.html.pages.inc');


page();


function page(){
	
	
	$tr=array();
	$tr[]=paragraph_youtube("Use Parent proxy in acls","//www.youtube.com/embed/-zqElEzAR08?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	
	$tr[]=paragraph_youtube("Block FaceBook during productive time","//www.youtube.com/embed/tuuM_jC0cBs?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("Artica Webfiltering databases in acls rules","//www.youtube.com/embed/1JZUHrQzdNc?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("Allow only domains gob.mx, .gob","//www.youtube.com/embed/sPaMjM6g9fA?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$acls=CompileTr3($tr);

	$tr=array();
	$tr[]=paragraph_youtube("Starting Guide","//www.youtube.com/embed/7ZUqX8_5NGk?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("How to connect Proxy to Active Directory ?","//www.youtube.com/embed/C106liv9GAk?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("How to turn your proxy to transparent mode ?","//www.youtube.com/embed/gh5oh_gYJX8?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("NAT compatibility mode + Zywall","//www.youtube.com/embed/IaHB9HVLFKI?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("How to use a proxy parent ?","//www.youtube.com/embed/J8Z_5k_J-9w?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("HTTP compression with remote sites","//www.youtube.com/embed/oGdezHsmeH0?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("Multiple Processors wihout disk caches - howto","//www.youtube.com/embed/2cA-fB2Wo20?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("Youtube and video streaming caching feature - howto","//www.youtube.com/embed/EUvW28nmDVg?list=UUYbS4gGDNP62LsEuDWOMN1Q");
	$tr[]=paragraph_youtube("TSE/RDP Gateway Howto","//www.youtube.com/embed/1_z9IF-Ghtc");
	
	$A=CompileTr3($tr);
	
	$tr=array();
	$tr[]=paragraph_youtube("MAC to Member translation feature","//www.youtube.com/embed/_DGLLTvamF4");
	$A1=CompileTr3($tr);
	
	
	
	$tr=array();
	$tr[]=paragraph_youtube("How to Bridge 2 network interfaces ?","//www.youtube.com/embed/pvBWxOUl4OU");
	$tr[]=paragraph_youtube("Play with the Firewall - basic demonstration","//www.youtube.com/embed/Qk1em2kOBHQ");
	$tr[]=paragraph_youtube("Traffic analysis daemon - howto","//www.youtube.com/embed/LiHE5lK58Qc");
	
	$B=CompileTr3($tr);
	
	$tr=array();
	$tr[]=paragraph_youtube("Update Manually Artica software","//www.youtube.com/embed/1fxAVMpcrXs");
	$tr[]=paragraph_youtube("Update the Proxy version Software","//www.youtube.com/embed/1x00u3AfIDU");
	
	
	
	$C=CompileTr3($tr);	
	
	
	
	$html="
	<div style='font-size:24px;margin-bottom:15px'>Proxy Architectures & Settings</div>$A
	<div style='font-size:24px;margin-bottom:15px'>Advanced security rules ( ACLs )</div>$acls
	
	
	<div style='font-size:24px;margin-bottom:15px'>Proxy Monitoring</div>$A1
	
	
	<div style='font-size:24px;margin-bottom:15px;margin-top:20px'>Networks videos</div>$B
	<div style='font-size:24px;margin-bottom:15px;margin-top:20px'>Maintain</div>$C		
			
	";
	echo $html;
	
}