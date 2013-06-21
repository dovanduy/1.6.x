<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["params"])){params();exit;}
	if(isset($_POST["DisableArticaProxyStatistics"])){Save();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{ARTICA_STATISTICS}");
	$html="YahooWin4('725','$page?popup=yes','$title');";	
	echo $html;
	}

function popup(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$array["params"]="{parameters}";
	
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	$id=time();
	
	echo "
	<div id='artica_stats_tabs' style='width:100%;height:590px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#artica_stats_tabs').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";		
	
	
}

function params(){
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$CleanArticaSquidDatabases=$sock->GET_INFO("CleanArticaSquidDatabases");
	if(!is_numeric($DisableArticaProxyStatistics)){$DisableArticaProxyStatistics=0;}
	if(!is_numeric($CleanArticaSquidDatabases)){$CleanArticaSquidDatabases=0;}
	$p=Paragraphe_switch_img("{DisableArticaProxyStatistics}", "{DisableArticaProxyStatistics_explain}","DisableArticaProxyStatistics",$DisableArticaProxyStatistics,null,450);
	$p1=Paragraphe_switch_img("{CleanArticaSquidDatabases}", "{CleanArticaSquidDatabases_explain}","CleanArticaSquidDatabases",$CleanArticaSquidDatabases,null,450);
	$html="
	<div id=$t></div>
	<div style='width:95%' class=form>
	<table>
	<tr>
		<td colspan=2>$p</td>
	</tr>
	<tr><td colspan=2><hr></td></tr>
	<tr>
		<td colspan=2>$p1</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button("{apply}", "SaveStopArticaStats()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	var x_SaveStopArticaStats= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		RefreshTab('artica_stats_tabs');
		CacheOff();
		ConfigureYourserver();
	}

		
		
	function SaveStopArticaStats(){
			
			var XHR = new XHRConnection();	
			XHR.appendData('DisableArticaProxyStatistics',document.getElementById('DisableArticaProxyStatistics').value);
			XHR.appendData('CleanArticaSquidDatabases',document.getElementById('CleanArticaSquidDatabases').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveStopArticaStats);
			}
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("DisableArticaProxyStatistics", $_POST["DisableArticaProxyStatistics"]);
	$sock->SET_INFO("CleanArticaSquidDatabases", $_POST["CleanArticaSquidDatabases"]);
	$sock->getFrameWork("squid.php?clean-mysql-stats=yes");
	$sock->getFrameWork('cmd.php?restart-artica-status=yes');
	
	
}
