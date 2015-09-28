<?php

if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.system.network.inc');
$usersmenus=new usersMenus();
if($usersmenus->AsArticaAdministrator==false){
	echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
	die();
}

if(isset($_POST["ArticaAutoUpateOfficial"])){Save();exit;}


page();


function page(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($sock->GET_INFO("ArticaAutoUpateNightly"));
	$ArticaUpdateIntervalAllways=intval($sock->GET_INFO("ArticaUpdateIntervalAllways"));
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}
	$ArticaUpdateRepos=unserialize($sock->GET_INFO("ArticaUpdateRepos"));
	$EnableArticaMetaClient=intval($sock->GET_INFO("EnableArticaMetaClient"));
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$CurlBandwith=$sock->GET_INFO("CurlBandwith");
	
	$CurlTimeOut=$sock->GET_INFO("CurlTimeOut");
	if(!is_numeric($CurlBandwith)){$CurlBandwith=0;}
	if(!is_numeric($CurlTimeOut)){$CurlTimeOut=3600;}
	if($CurlTimeOut<720){$CurlTimeOut=3600;}
	
	
	$t=time();
	$latest_release=null;
	$info=null;
	
	$ip=new networking();
	
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$arrcp[null]="{default}";
	
	
	$key_offical=update_find_latest($ArticaUpdateRepos);
	$CURVER=@file_get_contents("VERSION");
	$CURVER_KEY=str_replace(".", "", $CURVER);
	
	$OFFICIALS=$ArticaUpdateRepos["OFF"];
	$NIGHTLYS=$ArticaUpdateRepos["NIGHT"];
	
	
	$Lastest=$OFFICIALS[$key_offical]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key_offical]["URL"];
	$MAIN_MD5=$OFFICIALS[$key_offical]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key_offical]["FILENAME"];
	
	$ZINFO=false;
	
	if($key_offical>$CURVER_KEY){
		$you_can_update_release=$tpl->_ENGINE_parse_body("{NEW_RELEASE_TEXT}");
		$ZINFO=true;
		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
			<td valign='top' style='width:65px'><img src=img/download-64.png></td>
			<td valign='top' style='width:99%;padding-left:20px'>
				<div style='font-size:20px'>$you_can_update_release</div>
				<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
				
				". button("{update_now}: $Lastest","Loadjs('artica.update.progress.php',true)",20)."
				
				
				</div>
			</td>
		</tr>
		</table>
		</div>
		";
		
	}
	
	if(!$ZINFO){
		if($ArticaAutoUpateNightly==1){
			$key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
				if($key_nightly>$CURVER_KEY){
					$you_can_update_release=$tpl->_ENGINE_parse_body("{NEW_RELEASE_TEXT}");
					$Lastest=$NIGHTLYS[$key_nightly]["VERSION"];
					$info="
					<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
					<table style='width:100%'>
					<tr>
					<td valign='top' style='width:65px'><img src=img/download-64.png></td>
					<td valign='top' style='width:99%;padding-left:20px'>
					<div style='font-size:20px'>$you_can_update_release</div>
					<div style='font-size:20px;text-align:right;margin-top:30px;text-align:right'>
					". button("{update_now}: $Lastest","Loadjs('artica.update.progress.php',true)",20)."
					
					</div>
					</td>
					</tr>
					</table>
					</div>
					";
				
				}	
		}
	}
	
	
	if($EnableArticaMetaClient==1){
		$info="
		<div style='width:98%;margin-bottom:30px;background-color:white;padding:5px;border:1px solid #CCCCCC'>
		<table style='width:100%'>
		<tr>
		<td valign='top' style='width:65px'><img src=img/64-info.png></td>
		<td valign='top' style='width:99%;padding-left:20px'>
		<div style='font-size:20px'>{update_use_meta_text}</div>
		
		</div>
		</td>
		</tr>
		</table>
		</div>
		";
		
	}
	
	
	if($MAIN_MD5<>null){
		$latest_release="
		<tr>
		<td style='font-size:22px' class=legend>{official}:</td>
		<td style='font-size:22px'><a href=\"$MAIN_URI\"style='text-decoration:underline'>$Lastest</a></td>
		</tr>";
		
	}
	
	
	if($ArticaAutoUpateNightly==1){
	
		$key_nightly=update_find_latest_nightly($ArticaUpdateRepos);
		$NIGHTLY=$ArticaUpdateRepos["NIGHT"];
		$Lastest=$NIGHTLY[$key_nightly]["VERSION"];
		$MAIN_URI=$NIGHTLY[$key_nightly]["URL"];
		$MAIN_MD5=$NIGHTLY[$key_nightly]["MD5"];
		$MAIN_FILENAME=$NIGHTLY[$key_nightly]["FILENAME"];
		
		if($MAIN_MD5<>null){
			$latest_nightly="
			<tr>
				<td style='font-size:22px' class=legend>{nightly}:</td>
				<td style='font-size:22px'><a href=\"$MAIN_URI\"style='text-decoration:underline'>$Lastest</a></td> 
			</tr>";
		
		}
	}
	
	
	
	if(preg_match("#^2\.#", $CURVER)){
		$UpgradeTov10=intval($sock->GET_INFO("UpgradeTov10"));
		if($UpgradeTov10==0){
	
			$html=FATAL_ERROR_SHOW_128("{need_to_upgrade_to_v10}").
			"<center style='margin:50px'>".button("{perform_upgrade}","Loadjs('squid.upgradev10.progress.php')",42)."</center>";
			echo $tpl->_ENGINE_parse_body($html);
			return;
		}
	
	}
	
	
	$p1=Paragraphe_switch_img("{update_artica_official}", "{update_artica_official_explain}",
			"ArticaAutoUpateOfficial-$t",$ArticaAutoUpateOfficial,null,890);
	
	
	$p2=Paragraphe_switch_img("{update_artica_nightly}", "{update_artica_nightly_explain}",
			"ArticaAutoUpateNightly-$t",$ArticaAutoUpateNightly,null,890);	
	
	$p3=Paragraphe_switch_img("{free_update_during_the_day}","{free_update_during_the_day_explain2}",
			"ArticaUpdateIntervalAllways-$t",$ArticaUpdateIntervalAllways,null,890);
	
	
	$WgetBindIpAddress=Field_array_Hash($arrcp,"WgetBindIpAddress",
			$WgetBindIpAddress,null,null,0,"font-size:26px;padding:3px;");
	
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/web/$CURVER.txt")){
		$whatsnew="
		<tr>
		<td style='font-size:18px;text-align:right' colspan=2 ><i>&laquo;<a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('artica.whatsnew.php')\" 
				style='text-decoration:underline'>WhatsNew</a>&raquo;</i></td>
		</tr>";
		
	}


	$html="<div style='width:99%' class=form>
	<table style='width:100%'>
	<tr>
		<td valign='top' style='width:400px'>
			<center style='font-size:22px;margin-bottom:20px'>
			<table style='width:100%'>
			<tr>
				<td style='font-size:22px' class=legend>{current}:</td>
				<td style='font-size:22px'>$CURVER</td> 
			</tr>
			
			$latest_release$latest_nightly$whatsnew
			</table>
			</center>
			
			<center style='margin-bottom:15px'>". button("{http_proxy}","Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes');",22,250)."</center>
			<center style='margin-bottom:15px'>". button("{manual_update}","Loadjs('artica.update-manu.php');",22,250)."</center>
			<center style='margin-bottom:15px'>". button("{verify}","Loadjs('artica.verify.updates.php');",22,250)."</center>
		
			
		</td>
		<td valign='top' style='width:1050px;padding-left:50px'>$info
	$p1$p2$p3
	<table style='width:100%'>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:26px'>{WgetBindIpAddress}:</strong></td>
		<td align='left'>$WgetBindIpAddress</td>
	</tr>			
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:26px'>{HTTP_TIMEOUT}:</strong></td>
		<td align='left' style='font-size:26px'>" . Field_text('CurlTimeOut',$CurlTimeOut,'font-size:26px;padding:3px;width:90px' )."&nbsp;{seconds}</td>
	</tr>
	<tr>
		<td width=1% nowrap align='right' class=legend style='font-size:26px'>{limit_bandwidth}:</strong></td>
		<td align='left' style='font-size:26px'>" . Field_text('CurlBandwith',$CurlBandwith,'font-size:26px;padding:3px;width:90px' )."&nbsp;kb/s</td>
	</tr>	
</table>	
	<hr>
	<div style='text-align:right'>". button("{apply}","Save$t()",40)."</div>
	</div>				
	</td>
	</tr>
	</table>	
<script>

var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	RefreshTab('main_config_artica_update');
}

function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ArticaAutoUpateOfficial',document.getElementById('ArticaAutoUpateOfficial-$t').value);
		XHR.appendData('ArticaAutoUpateNightly',document.getElementById('ArticaAutoUpateNightly-$t').value);
		XHR.appendData('ArticaUpdateIntervalAllways',document.getElementById('ArticaUpdateIntervalAllways-$t').value);
		
		if(document.getElementById('WgetBindIpAddress')){
			XHR.appendData('WgetBindIpAddress',document.getElementById('WgetBindIpAddress').value);
		}
		if(document.getElementById('CurlBandwith')){
    		XHR.appendData('CurlBandwith',document.getElementById('CurlBandwith').value);
    	}
		if(document.getElementById('CurlTimeOut')){
    		XHR.appendData('CurlTimeOut',document.getElementById('CurlTimeOut').value);
    	}  		
		
		
		XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	while (list ($key, $value) = each ($_POST)){
		$sock->SET_INFO($key, $value);
	}
}

function update_find_latest_nightly($array){

	
	$MAIN=$array["NIGHT"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function update_find_latest($array){

	
	$MAIN=$array["OFF"];
	$keyMain=0;
	while (list ($key, $ligne) = each ($MAIN)){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}