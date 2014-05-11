<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	// CicapEnabled
	
	$user=new usersMenus();
	if($user->AsDansGuardianAdministrator==false){die('not allowed');}
	
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["CICAPEnableSquidGuard"])){CICAPEnableSquidGuardSave();exit;}
	
tabs();

function tabs(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	
	
	$array["status"]='{status}';
	//$array["logs"]='{icap_logs}';
	$fontsize="16";
	while (list ($num, $ligne) = each ($array) ){
		if($num=="rules"){
			$html[]= "<li><a href=\"c-icap.rules.php\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
			continue;
		}
	
		$html[]= "<li><a href=\"$page?$num=yes\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n";
	}
	
	echo build_artica_tabs($html, "main_config_cicap_filter",900);	
	
	
}

function status(){
	$users=new usersMenus();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$t=time();
	$CICAPEnableSquidGuard=intval($sock->GET_INFO("CICAPEnableSquidGuard"));
	$p=Paragraphe_switch_img("{activate_icap_content_filter}", "{activate_icap_content_filter_explain}","CICAPEnableSquidGuard-$t",$CICAPEnableSquidGuard,null,600);
	
	$html="
	<div style='font-size:30px'>Beta mode - don't use!</div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr>
			<td style='vertical-align:top;width:450px' ><div id='cicap_web_status'></div>
			<td style='vertical-align:top;width:99%'>
			$p
			<hr>
				<div style='text-align:right'>". button("{apply}","Save$t()",26)."</div>
			</td>
		</tr>
	</table>
	</div>	
<script>
var xSave$t=function(obj){
     var tempvalue=obj.responseText;
	  if(tempvalue.length>3){alert(tempvalue);}
	  RefreshTab('main_config_cicap');
	  RefreshTab('squid_main_svc');
	  Loadjs('squid.compile.progress.php?ask=yes');
	
	}	
	
	function Save$t(){
		var XHR = new XHRConnection();
	    XHR.appendData('CICAPEnableSquidGuard',document.getElementById('CICAPEnableSquidGuard-$t').value);
       	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>						
";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function CICAPEnableSquidGuardSave(){
	
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$q->CheckTablesICAP();
	$sock->SET_INFO("CICAPEnableSquidGuard",$_POST["CICAPEnableSquidGuard"]);
	
	if($_POST["CICAPEnableSquidGuard"]==1){
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=3 WHERE ID=12");
		
	}else{
		$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=12");
	}
	
	
	
}

