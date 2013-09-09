<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.tasks.inc");

if(isset($_GET["settings"])){settings();exit;}
if(isset($_POST["MirrorEnableDebian"])){SaveConfig();exit;}
if(isset($_GET["rsync-debian-status"])){rsync_debian_status();exit;}
if(isset($_GET["events-section"])){events_section();exit;}
if(isset($_GET["search-events"])){events_search();exit;}
if(isset($_GET["execute-debian-js"])){execute_debian_js();exit;}
if(isset($_POST["execute-debian-perform"])){execute_debian_perform();exit;}



tabs();
function tabs(){
	$users=new usersMenus();
	$sock=new sockets();
	$page=CurrentPageName();
	$array["{parameters}"]="$page?settings=yes";
	$array["{schedules}"]="miniadm.system.schedules-engine.php?task-section=68";
	$array["{events}"]="$page?events-section=yes";
	$page=CurrentPageName();
	$mini=new boostrap_form();
	echo $mini->build_tab($array);
}


function execute_debian_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=$_GET["t"];
	
$html="
var xExecute$t= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}	
	LoadAjax('$t','$page?rsync-debian-status=yes');
}	

function Execute$t(){
	var XHR = new XHRConnection();
	XHR.appendData('execute-debian-perform','yes');
	AnimateDiv('$t');
	XHR.sendAndLoad('$page', 'POST',xExecute$t);
}			
Execute$t();
";
echo $html;
}

function execute_debian_perform(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?execute-debian-mirror-rsync=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{operation_in_background}");

}



function settings(){
	$page=CurrentPageName();
	$sock=new sockets();
	$MirrorEnableDebian=$sock->GET_INFO("MirrorEnableDebian");
	$MirrorDebianDirSizeText=null;
	
	
	$MirrorDebianBW=$sock->GET_INFO("MirrorDebianBW");
	if(!is_numeric($MirrorEnableDebian)){$MirrorEnableDebian=0;}
	if(!is_numeric($MirrorDebianBW)){$MirrorDebianBW=500;}
	
	$MirrorDebianDir=$sock->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	$MirrorDebianDirSize=$sock->GET_INFO("MirrorDebianDirSize");
	if(!is_numeric($MirrorDebianDirSize)){$MirrorDebianDirSize=0;}
	$MirrorDebianMaxExecTime=$sock->GET_INFO("MirrorDebianMaxExecTime");
	
	$MirrorDebianEachMn=$sock->GET_INFO("MirrorDebianEachMn");
	if(!is_numeric($MirrorDebianEachMn)){$MirrorDebianEachMn=2880;}
	$MirrorDebianExclude=unserialize(base64_decode($sock->GET_INFO("MirrorDebianExclude")));
	$MirrorDebianExcludeOS=unserialize(base64_decode($sock->GET_INFO("MirrorDebianExcludeOS")));
	
	if(!is_numeric($MirrorDebianMaxExecTime)){$MirrorDebianMaxExecTime=0;}
	
	
	
	$MirrorEnableDebianSchedule=$sock->GET_INFO("MirrorEnableDebianSchedule");
	if(!is_numeric($MirrorEnableDebianSchedule)){$MirrorEnableDebianSchedule=0;}
	
	
	
	$boot=new boostrap_form();
	
	$timeZ[60]="1 {hour}";
	$timeZ[120]="2 {hours}";
	$timeZ[300]="5 {hours}";
	$timeZ[720]="12 {hours}";
	$timeZ[1440]="1 {day}";
	$timeZ[2880]="2 {days}";
	$timeZ[10080]="1 {week}";
	
	if($MirrorDebianDirSize>0){$MirrorDebianDirSizeText=" (".FormatBytes($MirrorDebianDirSize/1024)." )";}
	$boot->set_formdescription("{debian_mirror_howto}<br>{rsync_out_port_explain}");
	$boot->set_spacertitle("Debian$MirrorDebianDirSizeText");
	$boot->set_checkbox("MirrorEnableDebian","{enable_debian_systems}", $MirrorEnableDebian);
	
	$boot->set_field("MirrorDebianDir", "{directory}$MirrorDebianDirSizeText", $MirrorDebianDir,array("ENCODE"=>true));
	
	$boot->set_field("MirrorDebianBW", "{max_bandwidth} KB/s", $MirrorDebianBW);
	$boot->set_list("MirrorDebianEachMn", "{execute_each}", $timeZ,$MirrorDebianEachMn);
	$boot->set_checkbox("MirrorEnableDebianSchedule","{use_schedule}", $MirrorEnableDebianSchedule,array("TOOLTIP"=>"{MirrorEnableDebianSchedule_explain}"));
	$boot->set_field("MirrorDebianMaxExecTime", "{max_execution_time} ({minutes})", $MirrorDebianMaxExecTime,array("TOOLTIP"=>"{MirrorDebianMaxExecTime_explain}"));
	
	
	//$boot->set_subtitle("{linux_distribution}");
	
	if(!is_array($MirrorDebianExcludeOS)){
		$MirrorDebianExcludeOS["sid"]=true;
		$MirrorDebianExcludeOS["jessie"]=true;
		$MirrorDebianExcludeOS["wheezy"]=true;
		$MirrorDebianExcludeOS["oldstable"]=true;
		$MirrorDebianExcludeOS["stable"]=true;
		$MirrorDebianExcludeOS["oldstable"]=true;
		$MirrorDebianExcludeOS["unstable"]=true;
	}
	
	$DEBVERS[]="sid";
	$DEBVERS[]="testing";
	$DEBVERS[]="jessie";

	$DEBVERS[]="squeeze";
	$DEBVERS[]="wheezy";
	
	$DEBVERS[]="oldstable";
	$DEBVERS[]="stable";
	$DEBVERS[]="unstable";	
	
	
/*	while (list ($none, $pattern) = each ($DEBVERS) ){
		$enabled=0;
		if($MirrorDebianExclude["$pattern"]==1){$enabled=1;}
		$boot->set_checkbox("debian-exclude-$pattern","{exclude}:&nbsp;&laquo;$pattern&raquo;",$enabled);
	}	
	
*/	
	$boot->set_subtitle("{architecture}");
	
	$f=array();$f[]="source";$f[]="alpha";$f[]="amd64";$f[]="arm";$f[]="armel";$f[]="armhf";$f[]="hppa";$f[]="hurd-i386";$f[]="i386";$f[]="ia64";$f[]="mips";$f[]="mipsel";$f[]="powerpc";$f[]="s390";$f[]="s390x";$f[]="sparc";$f[]="kfreebsd-i386";$f[]="kfreebsd-amd64";
	if(!is_array($MirrorDebianExclude)){
		while (list ($none, $pattern) = each ($f) ){
			if($pattern=="i386"){continue;}
			if($pattern=="amd64"){continue;}
			$MirrorDebianExclude[$pattern]=1;
		}
	  reset($f);
	}
	
	
	while (list ($none, $pattern) = each ($f) ){
		$enabled=0;
		if($MirrorDebianExclude["$pattern"]==1){$enabled=1;}
		$boot->set_checkbox("debian-exclude-$pattern","{exclude}:&nbsp;&laquo;$pattern&raquo;",$enabled);
	}
	

	$t=time();
	$boot->set_button("{apply}");
	$boot->set_Newbutton("{execute}", "Loadjs('$page?execute-debian-js=yes&t=$t')");
	$form=$boot->Compile();
	
	$html="<table style='width:100%'>
	<tr>
		<td style='vertical-align:top;width:350px'>
		
		<div id='$t'>
		
		</div>
		<div style='text-align:right'>". imgtootltip("refresh-32.png",null,"LoadAjax('$t','$page?rsync-debian-status=yes')")."</div>
	</td>
	<td style='vertical-align:top;padding-left:20px'>		
		$form
	</td>
	</tr>
	</table>
	<script>
		LoadAjax('$t','$page?rsync-debian-status=yes');
	</script>	
	";
	
	
	echo $html;
	
}

function rsync_debian_status(){
	$sock=new sockets();
	
	$tpl=new templates();
	$datas=base64_decode($sock->getFrameWork("system.php?rsync-debian-status=yes"));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$MirrorDebianDir=$sock->GET_INFO("MirrorDebianDir");
	if($MirrorDebianDir==null){$MirrorDebianDir="/home/mirrors/Debian";}
	$MirrorDebianDirSize=$sock->GET_INFO("MirrorDebianDirSize");
	if(!is_numeric($MirrorDebianDirSize)){$MirrorDebianDirSize=0;}
	
	if($MirrorDebianDirSize>0){$MirrorDebianDirSizeText=" {directory_size}:".FormatBytes($MirrorDebianDirSize/1024)."";}
	
	$status=DAEMON_STATUS_ROUND("APP_RSYNC_DEBIAN",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($status)."<div style='font-size:16px'>".$tpl->_ENGINE_parse_body($MirrorDebianDirSizeText)."</div>";
	
}


function SaveConfig(){
	$sock=new sockets();
	$_POST["MirrorDebianDir"]=url_decode_special_tool($_POST["MirrorDebianDir"]);
	$sock->SET_INFO("MirrorDebianDir", $_POST["MirrorDebianDir"]);
	$sock->SET_INFO("MirrorEnableDebian", $_POST["MirrorEnableDebian"]);
	$sock->SET_INFO("MirrorDebianBW", $_POST["MirrorDebianBW"]);
	$sock->SET_INFO("MirrorDebianMaxExecTime", $_POST["MirrorDebianMaxExecTime"]);
	while (list ($key, $pattern) = each ($_POST) ){
		if(preg_match("#debian-exclude-(.+)#", $key,$re)){
			$MirrorDebianExclude[$re[1]]=$pattern;
		}
	}
	$sock->SaveConfigFile(base64_encode(serialize($MirrorDebianExclude)), "MirrorDebianExclude");
	
}


function events_section(){
	$boot=new boostrap_form();
	echo $boot->SearchFormGen("zDate,error,pid","search-events");
}

function events_search(){
	
	$boot=new boostrap_form();
	$tpl=new templates();
	$q=new mysql();
	$page=CurrentPageName();
	$table="mirror_logs";
	$searchstring=string_to_flexquery("search-events");
	$ORDER=$boot->TableOrder(array("zDate"=>"DESC"));
	if($q->COUNT_ROWS($table,"artica_events")==0){
		senderrors("no data");
	}
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){senderrors($q->mysql_error."<br>$sql");}
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$md=md5(serialize($ligne));
		if($ligne["totalsize"]>0){$ligne["totalsize"]=FormatBytes($ligne["totalsize"]/1024);}
		
		$distance=$tpl->_ENGINE_parse_body(distanceOfTimeInWords($ligne["starton"],$ligne["endon"]));
		$link=null;
		
		$tr[]="
		<tr id='$md'>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["zDate"]}</td>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["pid"]}</td>
		<td style='font-size:16px' width=1% nowrap $link>$distance</td>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["filesnumber"]}</td>
		<td style='font-size:16px' width=1% nowrap $link>{$ligne["totalsize"]}</td>
		<td style='font-size:12px' width=99% $link>{$ligne["error"]}</td>
		</tr>
		";
	}
	
	echo $boot->TableCompile(array("zDate"=>"{date}","pid"=>"pid",
			"endon"=>"{duration}",
			"filesnumber"=>"{files}",
			"size"=>"{size}",
			"error"=>"{error}",
	),$tr);	
}