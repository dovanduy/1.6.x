<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.backup.inc');
	include_once('ressources/class.os.system.inc');
	
	$users=new usersMenus();
	if(!$users->AsArticaAdministrator){die();}
	if(isset($_POST["ApacheGroupware"])){Save();exit;}
	if(isset($_GET["popup"])){popup2();exit;}
	
	popup();
	
	
function popup(){
	
	$page=CurrentPageName();
	$html="<div id='mysqlopt'></div>
	<script>LoadAjax('mysqlopt','$page?popup=yes',true);</script>
	";
	echo $html;
	
}	
	
function popup2(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$disable_mysql=0;
	$ApacheGroupware=$sock->GET_INFO("ApacheGroupware");
	$EnableNSCD=$sock->GET_INFO("EnableNSCD");
	$LighttpdRunAsminimal=$sock->GET_INFO("LighttpdRunAsminimal");
	$SlapdThreads=$sock->GET_INFO("SlapdThreads");	
	$EnableArticaStatus=$sock->GET_INFO("EnableArticaStatus");	
	$EnableArticaExecutor=$sock->GET_INFO("EnableArticaExecutor");	
	$EnableArticaBackground=$sock->GET_INFO("EnableArticaBackground");	
	$EnableClamavDaemon=$sock->GET_INFO("EnableClamavDaemon");
	$memory=intval($sock->getFrameWork("services.php?total-memory=yes"));
	$MysqlConfigLevel=$sock->GET_INFO("MysqlConfigLevel");
	$EnableNginx=$sock->GET_INFO("EnableNginx");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	$DisableWinbindd=$sock->GET_INFO("DisableWinbindd");
	if($memory<550){$disable_mysql=1;}
	if(!is_numeric($EnableNSCD)){$EnableNSCD=1;}
	if(!is_numeric($LighttpdRunAsminimal)){$LighttpdRunAsminimal=0;}
	if(!is_numeric($EnableArticaStatus)){$EnableArticaStatus=1;}
	if(!is_numeric($EnableArticaExecutor)){$EnableArticaExecutor=1;}
	if(!is_numeric($EnableArticaBackground)){$EnableArticaBackground=1;}
	if(!is_numeric($EnableNginx)){$EnableNginx=1;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	
	

	
	$SlapdThreads=$sock->GET_INFO("SlapdThreads");
	$EnableArpDaemon=$sock->GET_INFO("EnableArpDaemon");
	$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
	$EnableVnStat=$sock->GET_INFO("EnableVnStat");
	
	
	if(!is_numeric($EnableArpDaemon)){$EnableArpDaemon=1;}
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	if(!is_numeric($EnableVnStat)){$EnableVnStat=1;}
	if(!is_numeric($SlapdThreads)){$SlapdThreads=0;}
	
	
	if(!is_numeric($EnableClamavDaemon)){$EnableClamavDaemon=0;}
	$EnableClamavDaemonForced=$sock->GET_INFO("EnableClamavDaemonForced");
	if(!is_numeric($EnableClamavDaemonForced)){$EnableClamavDaemonForced=0;}
	if($EnableClamavDaemonForced==1){$EnableClamavDaemon=1;}
	
	
	
	if(!is_numeric($MysqlConfigLevel)){$MysqlConfigLevel=0;}
	$users=new usersMenus();
	
	if($users->CLAMD_INSTALLED){
		$clamav="
		<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_CLAMAV}:</td>
		<td valign='top'>". Field_checkbox("EnableClamavDaemon",1,$EnableClamavDaemon)."</td>
		<td><div class=explain>{CLAMAV_DISABLE_EXPLAIN}</div></td>
		</tr>	
		
		";
	}
	
	
	if($users->NSCD_INSTALLED){
		$nscd="
		<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_NSCD}:</td>
		<td valign='top'>". Field_checkbox("EnableNSCD",1,$EnableNSCD)."</td>
		<td><div class=explain>{NSCD_DISABLE_EXPLAIN}</div></td>
		</tr>	
		
		";
	}
	
	$mysqlr[0]="{default}";
	$mysqlr[1]="{lower_config}";
	$mysqlr[2]="{very_lower_config}";
	$mysqlf=Field_array_Hash($mysqlr,"MysqlConfigLevel", $MysqlConfigLevel,"style:font-size:16px;padding:3px");
	
	
	$html="
	<input type='hidden' id='arcoptze_text' value='{artica_optimize_explain}'>
	<div class=explain id='arcoptze' style='font-size:16px'>{artica_optimize_explain}</div>
	
	<div style='width:98%' class=form><table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{EnableNginx}:</td>
		<td valign='top'>". Field_checkbox("EnableNginx",1,$EnableNginx)."</td>
		<td><div class=explain>{EnableNginx_disable_explain}</div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{EnableFreeWeb}:</td>
		<td valign='top'>". Field_checkbox("EnableFreeWeb",1,$EnableFreeWeb)."</td>
		<td><div class=explain>{EnableFreeWeb_disable_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{EnableArpDaemon}:</td>
		<td valign='top'>". Field_checkbox("EnableArpDaemon",1,$EnableArpDaemon)."</td>
		<td><div class=explain>{EnableArpDaemon_disable_explain}</div></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{EnablePHPFPM}:</td>
		<td valign='top'>". Field_checkbox("EnablePHPFPM",1,$EnablePHPFPM)."</td>
		<td><div class=explain>{EnablePHPFPM_disable_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{EnableVnStat}:</td>
		<td valign='top'>". Field_checkbox("EnableVnStat",1,$EnableVnStat)."</td>
		<td><div class=explain>{EnableVnStat_disable_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{disable_winbindd}:</td>
		<td valign='top'>". Field_checkbox("DisableWinbindd",1,$DisableWinbindd)."</td>
		<td><div class=explain>{DisableWinbindd_explain}</div></td>
	</tr>				

				
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_GROUPWARE_APACHE}:</td>
		<td valign='top'>". Field_checkbox("ApacheGroupware",1,$ApacheGroupware)."</td>
		<td><div class=explain>{APACHE_GROUPWARE_DISABLE_EXPLAIN}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{LighttpdRunAsminimal}:</td>
		<td valign='top'>". Field_checkbox("LighttpdRunAsminimal",1,$LighttpdRunAsminimal)."</td>
		<td><div class=explain>{reduce_artica_web_explain}</div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_MYSQL}:</td>
		<td valign='top'>$mysqlf</td>
		<td><div class=explain>{Reduce_mysql_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{SlapdThreads}:</td>
		<td valign='top'>". Field_text("SlapdThreads",$SlapdThreads,"font-size:13px;width:60px")."</td>
		<td><div class=explain>{SlapdThreads_explain}</div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_ARTICA_STATUS}:</td>
		<td valign='top'>". Field_checkbox("EnableArticaStatus",1,$EnableArticaStatus)."</td>
		<td><div class=explain>{DisableArticaStatusService_explain}</div></td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_ARTICA_EXECUTOR}:</td>
		<td valign='top'>". Field_checkbox("EnableArticaExecutor",1,$EnableArticaExecutor)."</td>
		<td><div class=explain>{DisableArticaExecutorService_explain}</div></td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px;vertical-align:top'>{APP_ARTICA_BACKGROUND}:</td>
		<td valign='top'>". Field_checkbox("EnableArticaBackground",1,$EnableArticaBackground)."</td>
		<td><div class=explain>{DisableEnableArticaBackgroundService_explain}</div></td>
	</tr>	
	$clamav
	$nscd
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","SaveOptimize()",18)."</td>
	</tR>
	
	
	</table></div>
	
	<script>
	function FieldsChecks(){
		var disable_mysql=$disable_mysql;
		if(disable_mysql==1){document.getElementById('MysqlConfigLevel').disabled=true;}
	}
	
	
	var x_SaveOptimize= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('arcoptze').innerHTML=document.getElementById('arcoptze_text').value;
		
	}		
	
	function SaveOptimize(){
		var disable_mysql=$disable_mysql;
		var XHR = new XHRConnection();
		XHR.appendData('SlapdThreads',document.getElementById('SlapdThreads').value);
		if(disable_mysql==0){XHR.appendData('MysqlConfigLevel',document.getElementById('MysqlConfigLevel').value);}
		if(document.getElementById('ApacheGroupware').checked){XHR.appendData('ApacheGroupware',1);}else{XHR.appendData('ApacheGroupware',0);}
		if(document.getElementById('EnableArticaStatus').checked){XHR.appendData('EnableArticaStatus',1);}else{XHR.appendData('EnableArticaStatus',0);}
		if(document.getElementById('EnableArticaExecutor').checked){XHR.appendData('EnableArticaExecutor',1);}else{XHR.appendData('EnableArticaExecutor',0);}
		if(document.getElementById('EnableArticaBackground').checked){XHR.appendData('EnableArticaBackground',1);}else{XHR.appendData('EnableArticaBackground',0);}
		if(document.getElementById('LighttpdRunAsminimal').checked){XHR.appendData('LighttpdRunAsminimal',1);}else{XHR.appendData('LighttpdRunAsminimal',0);}
		if(document.getElementById('EnableNSCD')){if(document.getElementById('EnableNSCD').checked){XHR.appendData('EnableNSCD',1);}else{XHR.appendData('EnableNSCD',0);}}
		if(document.getElementById('EnableClamavDaemon')){if(document.getElementById('EnableClamavDaemon').checked){XHR.appendData('EnableClamavDaemon',1);}else{XHR.appendData('EnableClamavDaemon',0);}}
		if(document.getElementById('EnableNginx')){if(document.getElementById('EnableNginx').checked){XHR.appendData('EnableNginx',1);}else{XHR.appendData('EnableNginx',0);}}
		
		    
		if(document.getElementById('EnableFreeWeb')){if(document.getElementById('EnableFreeWeb').checked){XHR.appendData('EnableFreeWeb',1);}else{XHR.appendData('EnableFreeWeb',0);}}
		if(document.getElementById('EnableArpDaemon')){if(document.getElementById('EnableArpDaemon').checked){XHR.appendData('EnableArpDaemon',1);}else{XHR.appendData('EnableArpDaemon',0);}}
		if(document.getElementById('EnablePHPFPM')){if(document.getElementById('EnablePHPFPM').checked){XHR.appendData('EnablePHPFPM',1);}else{XHR.appendData('EnablePHPFPM',0);}}
		if(document.getElementById('EnableVnStat')){if(document.getElementById('EnableVnStat').checked){XHR.appendData('EnableVnStat',1);}else{XHR.appendData('EnableVnStat',0);}}
		if(document.getElementById('DisableWinbindd')){if(document.getElementById('DisableWinbindd').checked){XHR.appendData('DisableWinbindd',1);}else{XHR.appendData('DisableWinbindd',0);}}
		
		AnimateDiv('arcoptze');
		XHR.sendAndLoad('$page', 'POST',x_SaveOptimize);
	
	}	

	FieldsChecks();
</script>	
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("ApacheGroupware", $_POST["ApacheGroupware"]);
	$sock->SET_INFO("LighttpdRunAsminimal", $_POST["LighttpdRunAsminimal"]);
	$sock->SET_INFO("EnableArticaStatus", $_POST["EnableArticaStatus"]);
	$sock->SET_INFO("EnableArticaExecutor", $_POST["EnableArticaExecutor"]);
	$sock->SET_INFO("EnableArticaBackground", $_POST["EnableArticaBackground"]);
	if(isset($_POST["MysqlConfigLevel"])){$sock->SET_INFO("MysqlConfigLevel", $_POST["MysqlConfigLevel"]);}	
	if(isset($_POST["EnableNSCD"])){$sock->SET_INFO("EnableNSCD", $_POST["EnableNSCD"]);}
	if(isset($_POST["EnableClamavDaemon"])){$sock->SET_INFO("EnableClamavDaemon", $_POST["EnableClamavDaemon"]);}
	if(isset($_POST["EnableNginx"])){
		$sock->SET_INFO("EnableNginx", $_POST["EnableNginx"]);
		$sock->getFrameWork("nginx.php?restart=yes&enabled={$_POST["EnableNginx"]}");	
		
	}
	if(isset($_POST["EnableArpDaemon"])){
		$sock->SET_INFO("EnableArpDaemon", $_POST["EnableArpDaemon"]);
		$sock->getFrameWork("services.php?restart-arp-daemon=yes&enabled={$_POST["EnableArpDaemon"]}");
	}	
	if(isset($_POST["EnablePHPFPM"])){
		$sock->SET_INFO("EnablePHPFPM", $_POST["EnablePHPFPM"]);
		$sock->getFrameWork("services.php?restart-phpfpm=yes&enabled={$_POST["EnablePHPFPM"]}");
	}	
	
	if(isset($_POST["EnableVnStat"])){
		$sock->SET_INFO("EnableVnStat", $_POST["EnableVnStat"]);
		$sock->getFrameWork("services.php?restart-vnstat=yes&enabled={$_POST["EnablePHPFPM"]}");
	}	
	
	if(isset($_POST["DisableWinbindd"])){
		$sock->SET_INFO("DisableWinbindd", $_POST["DisableWinbindd"]);
		$sock->getFrameWork("services.php?restart-winbindd=yes&enabled={$_POST["DisableWinbindd"]}");
	}	
	
	
	
	
	$sock->SET_INFO("SlapdThreads", $_POST["SlapdThreads"]);
	$sock->getFrameWork("services.php?restart-apache-groupware=yes");
	$sock->getFrameWork("services.php?restart-lighttpd=yes");
	$sock->getFrameWork("services.php?restart-ldap=yes");
	$sock->getFrameWork("services.php?restart-cron=yes");
	

	if(isset($_POST["EnableNSCD"])){
		$sock->getFrameWork("services.php?restart-artica-status=yes");
		if($_POST["EnableNSCD"]==0){$sock->getFrameWork("services.php?stop-nscd=yes");}
	}
	
	if(isset($_POST["EnableClamavDaemon"])){
		$sock->getFrameWork("services.php?restart-artica-status=yes");
		$sock->getFrameWork("cmd.php?clamd-restart=yes");
	}
	
	if(isset($_POST["MysqlConfigLevel"])){$sock->getFrameWork("services.php?restart-mysql=yes");}
}




