<?php

$GLOBALS["AS_ROOT"]=false;
$GLOBALS["CACHE_RIGHT_IMAGE"]="/usr/share/artica-postfix/ressources/logs/web/status.right.image.cache";

if(count($argv)>0){if($argv[1]=="--verbose"){$_GET["status-debug"]="yes";}}

if(isset($_GET["status-debug"])){
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',"");ini_set('error_append_string',"<br>\n");
	$GLOBALS["VERBOSE"]=true;
	status_right_image2();
}

if(function_exists("posix_getuid")){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}}
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");

if(isset($_GET["refresh-image-js"])){refresh_image_js();exit;}

if($GLOBALS["AS_ROOT"]){
	
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/framework/class.unix.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.settings.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time>5){
			shell_exec($unix->find_program("kill")." -9 $pid >/dev/null 2>&1");
		}else{
			die();
		}
	}
	@file_put_contents($pidfile, getmypid());
}




	if(!$GLOBALS["AS_ROOT"]){
	$data=@file_get_contents($GLOBALS["CACHE_RIGHT_IMAGE"]);
	if(strlen($data)>50){
			include_once('ressources/class.templates.inc');
			$page=CurrentPageName();
			$time=filemtime($GLOBALS["CACHE_RIGHT_IMAGE"]);
			$tpl=new templates();
			$cacheTime_text=date("Y {F} {l} H:i:s",$time);
			$subtext=$tpl->_ENGINE_parse_body("
				<div style='text-align:right;border-top:1px solid #CCCCCC;padding-top:200px'>
					<i>{generated_on} $cacheTime_text</i>
					<br><a href=\"javascript:Loadjs('$page?refresh-image-js=yes');\" style='text-decoration:underline'>&laquo;&nbsp;{refresh}&nbsp;&raquo;</a>
				</div>");
			
			
			
			echo $tpl->_ENGINE_parse_body($data).$subtext;
			return;
		}
	}

build();

function build(){
	$script="<script> LoadAjax('mem_status_computer','admin.index.php?memcomputer=yes',true); </script>";
	$html=status_right_image2().$script;
	@file_put_contents($GLOBALS["CACHE_RIGHT_IMAGE"], $html);
	@chmod($GLOBALS["CACHE_RIGHT_IMAGE"], 0755);
	if($GLOBALS["AS_ROOT"]){return;}
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function refresh_image_js(){
	header("content-type: application/x-javascript");
	@unlink($GLOBALS["CACHE_RIGHT_IMAGE"]);
	echo "LoadAjax('IMAGE_STATUS_INFO','admin.index.right-image.php',true);";
}


function status_right_image2(){
	include_once(dirname(__FILE__)."/ressources/logs.inc");
	include_once(dirname(__FILE__)."/ressources/class.templates.inc");
	include_once(dirname(__FILE__)."/ressources/class.html.pages.inc");
	include_once(dirname(__FILE__)."/ressources/class.cyrus.inc");
	include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");
	include_once(dirname(__FILE__)."/ressources/charts.php");
	include_once(dirname(__FILE__)."/ressources/class.syslogs.inc");
	include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
	include_once(dirname(__FILE__)."/ressources/class.os.system.inc");	
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$users=new usersMenus();
	$NOCACHE=true;
	$newfrontend=false;
	$sock=new sockets();
	$SambaEnabled=$sock->GET_INFO("SambaEnabled");
	if(!is_numeric($SambaEnabled)){$SambaEnabled=1;}
	if($SambaEnabled==0){$users->SAMBA_INSTALLED=false;}
	$DisableMessaging=intval($sock->GET_INFO("DisableMessaging"));
	
	if($GLOBALS["VERBOSE"]){echo " DisableMessaging = $DisableMessaging\n";}
	
	if($DisableMessaging==1){
			$users->POSTFIX_INSTALLED=false;
			$users->ZARAFA_INSTALLED=false;
	}
	
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	if($SQUIDEnable==0){$users->SQUID_INSTALLED=false;}
	
	
	$NOCACHE=false;
	if($GLOBALS["VERBOSE"]){echo " -> Loading status()\n";}
	$status=new status();
	

	if($GLOBALS["VERBOSE"]){echo " -> Checking\n";}
	if($users->WEBSTATS_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo " -> WEBSTATS()\n";}
		return $tpl->_ENGINE_parse_body($status->WEBSTATS());
		
	}
	
	if($users->ZARAFA_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo " -> ZARAFA()\n";}
		return $tpl->_ENGINE_parse_body($status->ZARAFA());
	}

	if($users->HAPRROXY_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo " -> haproxy_status()\n";}
		return $tpl->_ENGINE_parse_body($status->haproxy_status());
	}

	if($users->LOAD_BALANCE_APPLIANCE){
		if($GLOBALS["VERBOSE"]){echo " -> xr_status()\n";}
		return $tpl->_ENGINE_parse_body($status->xr_status());
	}
	if($users->POSTFIX_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo " -> status_postfix()\n";}
		return status_postfix();
	}

	
	if($users->SQUID_INSTALLED){
		$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($SQUIDEnable==0){
			if($users->KASPERSKY_WEB_APPLIANCE){
				if($GLOBALS["VERBOSE"]){echo " -> KASPERSKY_WEB_APPLIANCE()\n";}
				return status_kav4proxy($NOCACHE);
			}
		}

		if($users->KASPERSKY_WEB_APPLIANCE){
			return status_squid_kav($NOCACHE);
		}
		if($GLOBALS["VERBOSE"]){echo " -> status_squid()\n";}
		return status_squid($NOCACHE);
			
			
	}else{
		if($users->KASPERSKY_WEB_APPLIANCE){
			if($GLOBALS["VERBOSE"]){echo " -> status_kav4proxy()\n";}
			return status_kav4proxy($NOCACHE);
		}
	}

	if($users->SAMBA_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo " -> StatusSamba()\n";}
		return StatusSamba();
		
	}


	if($users->APACHE_INSTALLED){
		if($GLOBALS["VERBOSE"]){echo " -> StatusApache()\n";}
		return StatusApache();
		
	}
}

function status_postfix(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$status=new status();
	$users=new usersMenus();
	$postfix=$status->Postfix_satus($users->ZARAFA_INSTALLED);
	return $tpl->_ENGINE_parse_body($postfix);

}
function StatusApache(){
	$page=CurrentPageName();
	$tpl=new templates();
	$status=new status();
	$html=$status->Apache_status();
	return $tpl->_ENGINE_parse_body($html);		
	
}
function status_kav4proxy(){
	$page=CurrentPageName();
	$tpl=new templates();
	$status=new status();
	$html=$status->kav4proxy_status();
	return $tpl->_ENGINE_parse_body($html);
}

function status_squid($NOCACHE=false){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "<strong style='color:red'>$page LINE:".__LINE__."</strong><br>\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Squid_status($NOCACHE);
	return $tpl->_ENGINE_parse_body($html);
}

function status_squid_kav(){
	$page=CurrentPageName();
	$tpl=new templates();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$status=new status();
	if($GLOBALS["VERBOSE"]){echo "$page LINE:".__LINE__."\n";}
	$html=$status->Squid_status();
	return $tpl->_ENGINE_parse_body($html);
}