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
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.stats-appliance.inc');
if($GLOBALS["VERBOSE"]){echo "Memory:(".__LINE__.") " .round(memory_get_usage(true)/1024)."Ko<br>\n";}

if(isset($_GET["rules"])){rules();exit;}
if(isset($_GET["add-js"])){add_js();exit;}
if(isset($_GET["add-popup"])){add_popup();exit;}
if(isset($_POST["email"])){Save();exit;}
if(isset($_POST["delete"])){Delete();exit;}
table();




function Save(){
	
$sock=new sockets();
	
	$tbl=explode("\n",$sock->GET_INFO("SmtpNotificationConfigCC"));
	$tbl[]=$_POST["email"];
	while (list ($num, $ligne) = each ($tbl) ){
		if(trim($ligne)==null){continue;}
		$cc[$ligne]=$ligne;
	}
	
	while (list ($num, $ligne) = each ($cc) ){
		$cc_final[]=$num;
	}	
	
	$sock->SaveConfigFile(implode("\n",$cc_final),"SmtpNotificationConfigCC");
	
	
	
}

function Delete(){
	$sock=new sockets();
	$tbl=explode("\n",$sock->GET_INFO("SmtpNotificationConfigCC"));
	unset($tbl[$_POST["delete"]]);
	if(!is_array($tbl)){
		$final=null;
	}else{
		$final=implode("\n",$tbl);
	}
	
	$sock->SaveConfigFile(implode("\n",$final),"SmtpNotificationConfigCC");
	
}


function table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{recipients}");
	$new=$tpl->javascript_parse_text("{add_recipient}");
	$directory=$tpl->javascript_parse_text("{recipients}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$LIGHTTPD_IP_ACCESS_TEXT=$tpl->javascript_parse_text("{LIGHTTPD_IP_ACCESS_TEXT}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$about=$tpl->javascript_parse_text("{about2}");
	$add_recipient_text=$tpl->javascript_parse_text("{add_recipient_text}");
	$t=time();
	$html="

	<table class='ARTICA_SMTP_BCC' style='display: none' id='ARTICA_SMTP_BCC' style='width:99%'></table>
	<script>
	function LoadTable$t(){
	$('#ARTICA_SMTP_BCC').flexigrid({
	url: '$page?rules=yes&t=$t',
	dataType: 'json',
	colModel : [
	
	{display: '<strong style=font-size:20px>$directory</strong>', name : 'directory', width : 880, sortable : true, align: 'left'},
	{display: '<strong style=font-size:20px>$delete</strong>', name : 'del', width : 163, sortable : false, align: 'center'},

	],
	buttons : [
	{name: '<strong style=font-size:20px>$new</strong>', bclass: 'add', onpress : NewRule$t},
		],
	searchitems : [
	{display: '$directory', name : 'pattern'},
	],
	sortname: 'directory',
	sortorder: 'asc',
	usepager: true,
	title: '<div style=\"font-size:30px\">$title</div>',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
}

function About$t(){
alert('$LIGHTTPD_IP_ACCESS_TEXT');
}

var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#ARTICA_SMTP_BCC').flexReload();
}



function Delete$t(ID){
	if(!confirm('$delete $directory:'+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
	Loadjs('firehol.progress.php');
}

function NewRule$t() {
	var email=prompt('$add_recipient_text');
	if(!email){return;}
	var XHR = new XHRConnection();
	XHR.appendData('email', email);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}
LoadTable$t();
</script>
";
	echo $html;
}
function rules(){
	//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	
	$sock=new sockets();
	$tbl=explode("\n",$sock->GET_INFO("SmtpNotificationConfigCC"));
	if(!is_array($tbl)){return null;}
	
	$html="<table style='width:99%'>";
	

	$data = array();
	$data['page'] = 1;
	$data['total'] = count($tbl);
	$data['rows'] = array();
	$icon="check-48-grey.png";
	



	while (list ($num, $ligne) = each ($tbl) ){
		$val=0;
		$delete=imgsimple("delete-48.png",null,"Delete{$_GET["t"]}('$num')");
		
		if($ligne["write"]==1){
			$icon="check-48.png";
		}
		$writeimg=imgsimple($icon);
		$data['rows'][] = array(
				'id' => "{$ligne["ID"]}",
				'cell' => array(
						"<span style='font-size:28px;font-weight:bold;color:$color;'>$ligne</span>",
						"<center>$delete</center>")
		);
	}

	echo json_encode($data);

}