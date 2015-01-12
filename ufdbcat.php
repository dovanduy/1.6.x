<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ccurl.inc');
	include_once("ressources/class.compile.ufdbguard.expressions.inc");
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["page"])){page();exit;}
	if(isset($_GET["parameters"])){parameters();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["ufdbCatInterface"])){Save();exit;}
	tabs();
	
	
	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();
	
	$fontsize=18;
	$array["page"]='{APP_UFDBCAT}';
	$array["verify"]='{databases_status}';
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	if($AsCategoriesAppliance==1){
		$array["events"]='{service_events}';
		$array["watchdog"]="{watchdog}";
	}
		
	while (list ($num, $ligne) = each ($array) ){
			if($num=="events"){
					
				$tab[]="<li style='font-size:18px'><a href=\"ufdbcat.events.php?$num=yes\"><span >$ligne</span></a></li>\n";
				continue;
			}
			
			if($num=="verify"){
					
				$tab[]="<li style='font-size:18px'><a href=\"ufdbcat.verify.php?$num=yes\"><span >$ligne</span></a></li>\n";
				continue;
			}			
			
			if($num=="watchdog"){
				$tab[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
				<a href=\"squid.watchdog-events.php\">
				<span>$ligne</span></a></li>\n");
				continue;
			}
	
	
			$tab[]="<li style='font-size:18px'><a href=\"$page?$num=yes\"><span >$ligne</span></a></li>\n";
		}
	
		$html=build_artica_tabs($tab, "main_ufdbcat_config");
	
	
		echo $tpl->_ENGINE_parse_body($html);
	}	
	
function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$users=new usersMenus();
	$sock=new sockets();	
	$html="
	<div style='font-size:42px;margin-bottom:20px'>{APP_UFDBCAT}</div>
	<div style='width:98%' class=form>
		<table style='width:100%'>		
			<tr>
				<td style='vertical-align:top;width:240px' nowrap><div id='UFDBCAT_STATUS'></div></td>
				<td style='vertical-align:top;width:99%'><div id='UFDBCAT_PARAMETERS'></div></td>
			</tr>
		</table>
</div>
<script>
	LoadAjax('UFDBCAT_STATUS','$page?status=yes');
	LoadAjax('UFDBCAT_PARAMETERS','$page?parameters=yes');
</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}
function status(){
	$tpl=new templates();
	$sock=new sockets();
	$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
	$page=CurrentPageName();
	$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	if($AsCategoriesAppliance==1){$RemoteUfdbCat=0;$EnableLocalUfdbCatService=1;}
	
	if($RemoteUfdbCat==1){
		$catz=new mysql_catz();
		$categories=$catz->ufdbcat("google.com");
		if($catz->ok){
			echo $tpl->_ENGINE_parse_body(Paragraphe32("{available}", "noacco:
		<span style='font-size:12px'><strong>google.com:</strong><br>{category} <strong>$categories</strong><br>{execution_time} {$catz->TimeExec}s</span>",null,
		"ok48.png","LoadAjax('UFDBCAT_STATUS','$page?status=yes')"));
		echo"<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('UFDBCAT_STATUS','$page?status=yes');")."</div>";
		return;
		}
		
		
		echo $tpl->_ENGINE_parse_body(Paragraphe32("{connection_error}", "noacco:
		<span style='font-size:12px'>{error} &laquo;$catz->mysql_error&raquo;</span>",null,
		"error-48.png"));
		echo"<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('UFDBCAT_STATUS','$page?status=yes');")."</div>";
		return;
		
	}
	
	
	
	$data=$sock->getFrameWork('cmd.php?ufdbcat-ini-status=yes');
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($data));
	$APP_UFDBCAT=DAEMON_STATUS_ROUND("APP_UFDBCAT",$ini,null,1);
	echo $tpl->_ENGINE_parse_body($APP_UFDBCAT);
	
	$ufdbCatInterface=$sock->GET_INFO("ufdbCatInterface");
	$ufdbCatPort=intval($sock->GET_INFO("ufdbCatPort"));
	$UfdbCatThreads=intval($sock->GET_INFO("UfdbCatThreads"));
	if($ufdbCatInterface==null){$ufdbCatInterface="127.0.0.1";}
	if($ufdbCatInterface=="all"){$ufdbCatInterface="127.0.0.1";}
	
	
	
	if($EnableLocalUfdbCatService==0){
		$html="<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('UFDBCAT_STATUS','$page?status=yes');")."</div>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	
	$catz=new mysql_catz();
	$categories=$catz->ufdbcat("google.com");
	if($catz->ok){
		echo $tpl->_ENGINE_parse_body(Paragraphe32("{available}", "noacco:
				<span style='font-size:12px'><strong>google.com:</strong><br>{category} <strong>$categories</strong><br>{execution_time} {$catz->TimeExec}s</span>",null,
				"ok48.png","LoadAjax('UFDBCAT_STATUS','$page?status=yes')"));
		echo $tpl->_ENGINE_parse_body("<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('UFDBCAT_STATUS','$page?status=yes');")."</div>");
		return;
	}
	
	
	echo $tpl->_ENGINE_parse_body(Paragraphe32("{connection_error}", "noacco:
			<span style='font-size:12px'>{error} &laquo;$catz->mysql_error&raquo;</span>",null,
			"error-48.png"));
	echo $tpl->_ENGINE_parse_body("<div style='text-align:right'>".imgtootltip("refresh-32.png","{refresh}","LoadAjax('UFDBCAT_STATUS','$page?status=yes');")."</div>");
	
	
	
	
	
}

function parameters(){
	$tpl=new templates();
	$sock=new sockets();
	$AsCategoriesAppliance=intval($sock->GET_INFO("AsCategoriesAppliance"));
	if($AsCategoriesAppliance==0){
		parameters_client();
		return;
	}
	
	
	$page=CurrentPageName();
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ufdbCatInterface=$sock->GET_INFO("ufdbCatInterface");
	$ufdbCatPort=intval($sock->GET_INFO("ufdbCatPort"));
	$UfdbCatThreads=intval($sock->GET_INFO("UfdbCatThreads"));
	
	for($i=1;$i<65;$i++){
		$threads[$i]=$i;
	}
	
	if($UfdbCatThreads==0){$UfdbCatThreads=4;}
	if($ufdbCatPort==0){$ufdbCatPort=3978;}
	$ips[null]="{all}";
	reset($ips);
	$t=time();
	$html="
			
			
			
<div style='width:98%' class=form>
	<table style='width:100%'>
	". Field_list_table("ufdbCatInterface", "{listen_address}", $ufdbCatInterface,22,$ips).
	  Field_text_table("ufdbCatPort", "{listen_port}", $ufdbCatPort,22,null,120).
	  Field_list_table("UfdbCatThreads", "{threads}", $UfdbCatThreads,22,$threads).
	  
	  
	  
	  Field_button_table_autonome("{apply}","Save$t()",32)."
	</table>
</div>
<script>
	var xSave$t=function (obj) {
		RefreshTab('main_ufdbguard_config');
		Loadjs('ufdbcat.compile.progress.php');
	}	

	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ufdbCatInterface',document.getElementById('ufdbCatInterface').value);
    	XHR.appendData('ufdbCatPort',document.getElementById('ufdbCatPort').value);
    	XHR.appendData('UfdbCatThreads',document.getElementById('UfdbCatThreads').value);
    	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>	
	";	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function parameters_client(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$RemoteUfdbCat=intval($sock->GET_INFO("RemoteUfdbCat"));
	$ufdbCatPort=intval($sock->GET_INFO("ufdbCatPort"));
	$ufdbCatInterface=$sock->GET_INFO("ufdbCatInterface");
	$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
	if($ufdbCatPort==0){$ufdbCatPort=3978;}
	
	$p0=Paragraphe_switch_img("{use_local_categories_services}", "{use_remote_categories_services_explain}<br><strong>{use_local_categories_services_explain_warn}</strong>",
			"EnableLocalUfdbCatService",$EnableLocalUfdbCatService,null,800,"EnableLocalUfdbCatServiceCheck()"
	);
	
	
	$p1=Paragraphe_switch_img("{use_remote_categories_services}", "{use_remote_categories_services_explain}",
			"RemoteUfdbCat",$RemoteUfdbCat,null,800
			);
	
	$ip=new networking();
	$ips=$ip->ALL_IPS_GET_ARRAY();
	$ufdbCatInterface=$sock->GET_INFO("ufdbCatInterface");
	$ufdbCatPort=intval($sock->GET_INFO("ufdbCatPort"));
	$UfdbCatThreads=intval($sock->GET_INFO("UfdbCatThreads"));
	unset($ips["127.0.0.1"]);
	
	for($i=1;$i<65;$i++){
		$threads[$i]=$i;
	}
	
	if($UfdbCatThreads==0){$UfdbCatThreads=4;}
	if($ufdbCatPort==0){$ufdbCatPort=3978;}
	$ips[null]="{none}";
	reset($ips);
	$t=time();

	$html="
<div style='width:98%' class=form>
			$p0
			<table style='width:100%'>".
		Field_list_table("ufdbCatInterface1", "{listen_address}", $ufdbCatInterface,22,$ips).
		Field_text_table("ufdbCatPort1", "{listen_port}", $ufdbCatPort,22,null,120).
		Field_list_table("UfdbCatThreads1", "{threads}", $UfdbCatThreads,22,$threads)."</table>
			<div id='design1-$t'>$p1
	<table style='width:100%'>
	 ". Field_text_table("ufdbCatInterface", "{remote_address}", $ufdbCatInterface,22,null,230).
		Field_text_table("ufdbCatPort", "{listen_port}", $ufdbCatPort,22,null,120).

	"</table>
	</div>"."
			
			
<table style='width:100%'>".
		Field_button_table_autonome("{apply}","Save$t()",32)."
		</table>
		</div>
		<script>
var xSave$t=function (obj) {
	RefreshTab('main_ufdbguard_config');
	Loadjs('squid.reconfigure.simple.php');
}
	
function Save$t(){
	var XHR = new XHRConnection();
	var EnableLocalUfdbCatService=document.getElementById('EnableLocalUfdbCatService').value;
	
	if(EnableLocalUfdbCatService==0){
		XHR.appendData('ufdbCatInterface',document.getElementById('ufdbCatInterface').value);
		XHR.appendData('ufdbCatPort',document.getElementById('ufdbCatPort').value);
		XHR.appendData('RemoteUfdbCat',document.getElementById('RemoteUfdbCat').value);
	}else{
		XHR.appendData('ufdbCatInterface',document.getElementById('ufdbCatInterface1').value);
		XHR.appendData('ufdbCatPort',document.getElementById('ufdbCatPort1').value);
		XHR.appendData('UfdbCatThreads',document.getElementById('UfdbCatThreads1').value);
	
	}
	XHR.appendData('EnableLocalUfdbCatService',document.getElementById('EnableLocalUfdbCatService').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
	
	function EnableLocalUfdbCatServiceCheck(){
	var EnableLocalUfdbCatService=document.getElementById('EnableLocalUfdbCatService').value;
			document.getElementById('ufdbCatInterface').disabled=false;
			document.getElementById('ufdbCatPort').disabled=false;
			document.getElementById('RemoteUfdbCat').disabled=false;
			document.getElementById('ufdbCatInterface1').disabled=true;
			document.getElementById('ufdbCatPort1').disabled=true;
			document.getElementById('UfdbCatThreads1').disabled=true;
			
			document.getElementById('design1-$t').style.visibility='visible';
			
		
		if(EnableLocalUfdbCatService==1){
			document.getElementById('RemoteUfdbCat').disabled=true;
			document.getElementById('ufdbCatInterface').disabled=true;
			document.getElementById('ufdbCatPort').disabled=true;
			document.getElementById('RemoteUfdbCat').disabled=true;
			document.getElementById('ufdbCatInterface1').disabled=false;
			document.getElementById('ufdbCatPort1').disabled=false;
			document.getElementById('UfdbCatThreads1').disabled=false;
			
			document.getElementById('design1-$t').style.visibility='hidden';
		}
		
		CheckBoxDesignHidden();
	}
	EnableLocalUfdbCatServiceCheck();
	</script>
	";	
	echo $tpl->_ENGINE_parse_body($html);
}


function Save(){
	$sock=new sockets();
	$RESTART_SERVICES=false;
	$EnableLocalUfdbCatService=intval($sock->GET_INFO("EnableLocalUfdbCatService"));
	if($EnableLocalUfdbCatService<>$_POST["EnableLocalUfdbCatService"]){
		$RESTART_SERVICES=true;
	}
	
	$sock->SET_INFO("ufdbCatInterface", $_POST["ufdbCatInterface"]);
	$sock->SET_INFO("ufdbCatPort", $_POST["ufdbCatPort"]);
	$sock->SET_INFO("UfdbCatThreads", $_POST["UfdbCatThreads"]);
	$sock->SET_INFO("EnableLocalUfdbCatService", $_POST["EnableLocalUfdbCatService"]);
	
	
	if($RESTART_SERVICES){
		$sock->getFrameWork("squid.php?ufdbcat-restart-interface=yes");
	}
	
	if($_POST["EnableLocalUfdbCatService"]==1){
		$sock->SET_INFO("RemoteUfdbCat", 0);
		return;
	}else{
		$sock->getFrameWork("squid.php?ufdbcat-restart-interface=yes");
		
	}
	

	if(isset($_POST["RemoteUfdbCat"])){$sock->SET_INFO("RemoteUfdbCat", $_POST["RemoteUfdbCat"]);}
	
}
