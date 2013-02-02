<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["AsSquidLoadBalancer"])){Save();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{load_balancer}");
	$page=CurrentPageName();
	$html="YahooWin3('550','$page?popup=yes','$title')";
	echo $html;	
	
}

function popup(){
	$tpl=new templates();
	$t=time();
	$page=CurrentPageName();	
	$sock=new sockets();
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}	
	
	
	$switch=Paragraphe_switch_img("{activate_loadbalancing}",
	 "{activate_loadbalancing_text}","AsSquidLoadBalancer",$AsSquidLoadBalancer,null,400);
	
	if($EnableWebProxyStatsAppliance==1){
	$switch=Paragraphe_switch_disable("{activate_loadbalancing}",
	 "{activate_loadbalancing_text}","",400);		
	}
	
	
	$html="
	<div id='$t-div'></div>
	<table style='width:99%' class=form>
	<tr>
		<td>$switch</td>
	</tr>
	<tr>
		<td  align='right'><hr>".button("{apply}","SaveLoadBalance$t()",18)."</td>
	</tr>
	</table>
	
	
	
	
	<script>
	var x_SaveLoadBalance$t=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>5){alert(tempvalue);}
      	CacheOff();
      	QuickLinkSystems('section_architecture');
      	YahooWin3Hide();
     	}	

	function SaveLoadBalance$t(){
			var XHR = new XHRConnection();
			XHR.appendData('AsSquidLoadBalancer',document.getElementById('AsSquidLoadBalancer').value);
			AnimateDiv('$t-div');
			XHR.sendAndLoad('$page', 'POST',x_SaveLoadBalance$t);		
	}		
		
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function Save(){
	$sock=new sockets();
	$sock->SET_INFO("AsSquidLoadBalancer", $_POST["AsSquidLoadBalancer"]);
	
	
}

