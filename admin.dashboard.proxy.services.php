<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
include_once(dirname(__FILE__).'/ressources/class.html.pages.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/charts.php');
include_once(dirname(__FILE__).'/ressources/class.syslogs.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

$users=new usersMenus();
if(!$users->AsSquidAdministrator){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}
if(isset($_GET["start"])){start();exit;}

page();


function page(){
	$t=time();
	$page=CurrentPageName();
	
	$html="<div style='width:100%;height:1200px;overflow:auto' id='$t'></div>
	
	<script>
		LoadAjaxRound('$t','$page?start=yes&t=$t');
	</script>
	";
	
	
	echo $html;
}

function start(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$t=time();
	if(!isset($_GET["t"])){$_GET["t"]=$t;}
	if(!is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")){
		
		if(!isset($_GET["wait"])){
			$sock->getFrameWork("cmd.php?Global-Applications-Status=yes");
		}
		
		echo $tpl->_ENGINE_parse_body("<center style='margin-20px;font-size:20px'>{please_wait_waiting_services_status}</center>
				<script>
					function Wait$t(){
						if(!document.getElementById('$t')){return;}
						LoadAjaxRound('{$_GET["t"]}','$page?start=yes&=$t&wait=yes');
					}
					
				setTimeout('Wait$t()',1200);
				</script>");
		
		
		die();
	}
	
	$ini->loadFile("/usr/share/artica-postfix/ressources/logs/global.status.ini");
	
	
	
	$tr[]="<div style='margin-top:20px'>";
	$tr[]="<table style='width:100%'>";
	
	$tr[]="<tr style='height:70px'>
	<th style='font-size:22px;' colspan=4>{services}</th>
	<th style='font-size:22px;' colspan=2>{processes}/{memory}</th>
	<th style='font-size:22px;'>{uptime}</th>
	<th style='font-size:22px;' colspan=2>{action}</th>
	</tr>";
	
	while (list ($key, $array) = each ($ini->_params) ){
	
		$icon="ok48.png";
		$color="black";
		$text="{running}";
		$service_name=$array["service_name"];
		$service_disabled=intval($array["service_disabled"]);
		if($service_disabled==0){continue;}
		$running=intval($array["running"]);
		$master_version=$array["master_version"];
		$processes_number=$array["processes_number"];
		$uptime="{since}: {$array["uptime"]}";
		$master_memory=FormatBytes($array["master_memory"]);
		$service_cmd=urlencode($array["service_cmd"]);
		
		$start=imgtootltip("48-run.png","{start}","Loadjs('system.services.cmd.php?APPNAME=$service_name&action=start&cmd=$service_cmd&appcode=$key')");
		$action=imgtootltip("stop-48.png","{stop}","Loadjs('system.services.cmd.php?APPNAME=$service_name&action=stop&cmd=$service_cmd&appcode=$key')");
		$restart=imgtootltip("restart-48.png","{restart}","Loadjs('system.services.cmd.php?APPNAME=$service_name&action=restart&cmd=$service_cmd&appcode=$key')");
		
		
		if($running==0){
			$icon="danger48.png";
			$color="#d32d2d";
			$text="{stopped}";
			$processes_number=0;
			$action=$start;
			$uptime="-";
		}
			$tr[]="<tr style='height:70px'>
				<td style='font-size:22px;color:$color'><img src='img/$icon'></td>
				<td style='font-size:22px;color:$color'>{{$service_name}}</td>
				<td style='font-size:22px;color:$color'>$master_version</td>
				<td style='font-size:22px;color:$color'>$text</td>
				<td style='font-size:22px;color:$color'>$processes_number {processes}</td>
				<td style='font-size:22px;color:$color;'>$master_memory</td>
				<td style='font-size:22px;color:$color'>$uptime</td>
				<td style='font-size:22px;color:$color'>$action</td>
				<td style='font-size:22px;color:$color'>$restart</td>
			</tr>";
		
	}
	
	$tr[]="</table>";
	$tr[]="</div>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $tr));
	
	
}