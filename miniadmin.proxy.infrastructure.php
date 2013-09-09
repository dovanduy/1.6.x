<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

$users=new usersMenus();




if(isset($_GET["tab-rules"])){tabs_rules();exit;}


if(isset($_GET["ports-behavior"])){section_ports();exit;}
if(isset($_POST["visible_hostname"])){section_ports_save();exit;}
if(isset($_GET["delete_all_js"])){delete_all_js();exit;}
if(isset($_GET["search-rules"])){section_rules_search();exit;}

if(isset($_GET["web-rules"])){section_webrules();exit;}
if(isset($_GET["search-webrules"])){section_webrules_search();exit;}
if(isset($_GET["section_webrules_add_js"])){section_webrules_add_js();exit;}

tabs();


function tabs(){
	$page=CurrentPageName();
	$sock=new sockets();

	$mini=new boostrap_form();
	$array["{behavior_listen_ports}"]="$page?ports-behavior=yes";
	$array["{authenticate_users}"]="miniadmin.proxy.authentication.php";
	echo $mini->build_tab($array);
}

function section_ports(){
	$boot=new boostrap_form();
	$sock=new sockets();
	$squid=new squidbee();
	$tpl=new templates();
	
	$sock=new sockets();
	$arrayParams=unserialize(base64_decode($sock->getFrameWork("squid.php?compile-list=yes")));
	$SSL=1;
	if(!isset($arrayParams["--enable-ssl"])){
		echo $tpl->_ENGINE_parse_body("<p class=text-error>{SSL_NOT_COMPILED}</p>");
		
	}
	
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	$SquidTransparentMixed=$sock->GET_INFO("SquidTransparentMixed");
	
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	if(!is_numeric($SquidTransparentMixed)){$SquidTransparentMixed=0;}
	
	$sql="SELECT CommonName FROM sslcertificates ORDER BY CommonName";
	$q=new mysql();
	$sslcertificates[null]="{select}";
	$results=$q->QUERY_SQL($sql,'artica_backup');
	while($ligneZ=mysql_fetch_array($results,MYSQL_ASSOC)){$sslcertificates[$ligneZ["CommonName"]]=$ligneZ["CommonName"];}	
	
	$boot->set_formtitle("{behavior}");
	$boot->set_field("visible_hostname","{visible_hostname}",$squid->visible_hostname,array("TOOLIP"=>"{visible_hostname_text}"));
	$boot->set_checkbox("hasProxyTransparent", "{transparent_mode}", 
				$squid->hasProxyTransparent,
				array("TOOLIP"=>"{transparent_mode_text}",
					 "LINK"=>"SquidTransparentMixed,KernelSendRedirects"));
	
	$boot->set_checkbox("SquidTransparentMixed", 
				"{SquidTransparentMixed}", 
					$SquidTransparentMixed,
					array("TOOLIP"=>"{SquidTransparentMixed_text}"));
	
	$boot->set_checkbox("KernelSendRedirects", "{KernelSendRedirects}", $KernelSendRedirects,
			array("TOOLIP"=>"{KernelSendRedirects_explain}"));	
		
	$boot->set_spacertitle("{listen_ports}");
	$boot->set_spacerexplain("{listen_port_text}");
	
	$boot->set_field("listen_port","HTTP",$squid->listen_port);
	$boot->set_field("second_listen_port","HTTP (2)",$squid->second_listen_port,array("TOOLTIP"=>"{squid_second_port_explain}"));
	
	
	$boot->set_field("ssl_port","HTTPS",$squid->ssl_port,array("TOOLTIP"=>"{squid_ssl_port_explain}"));
	$boot->set_list("certificate_center", "{certificate}", $sslcertificates,$squid->certificate_center);
	
	
	
	$boot->set_field("icp_port","{icp_port}",$squid->ICP_PORT,array("TOOLTIP"=>"{icp_port_explain}"));
	$boot->set_field("htcp_port","{htcp_port}",$squid->HTCP_PORT,array("TOOLTIP"=>"{htcp_port_explain}"));
	$boot->set_button("{apply}");
	
	$users=new usersMenus();
	if(!$users->AsSquidAdministrator){$boot->set_form_locked();}
	
	echo $boot->Compile();
		
}

function section_ports_save(){
	$squid=new squidbee();
	$sock=new sockets();
	$squid->visible_hostname=$_POST["visible_hostname"];
	
	
	$sock=new sockets();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	
	
	$FreeWebListenSSLPort=$sock->GET_INFO("FreeWebListenSSLPort");
	$FreeWebListen=$sock->GET_INFO("FreeWebListen");
	if(!is_numeric($FreeWebListenSSLPort)){$FreeWebListenSSLPort=443;}
	if(!is_numeric($FreeWebListen)){$FreeWebListen=80;}
	
	if($_POST["listen_port"]==$FreeWebListen){$sock->SET_INFO("FreeWebListen",$_GET["listenport"]+1);}
	if($_POST["ssl_port"]==$FreeWebListenSSLPort){$sock->SET_INFO("FreeWebListenSSLPort",$_GET["ssl_port"]+1);}
	
	if($_POST["hasProxyTransparent"]==1){
		if($_POST["SquidTransparentMixed"]==1){
			if($_POST["second_listen_port"]==0){
				$_POST["second_listen_port"]=3140;
			}
		}
		
	}
	
	$squid=new squidbee();
	$squid->hasProxyTransparent=$_POST["hasProxyTransparent"];
	$squid->listen_port=$_POST["listen_port"];
	$squid->second_listen_port=$_POST["second_listen_port"];
	$squid->ICP_PORT=$_POST["icp_port"];
	$squid->HTCP_PORT=$_POST["htcp_port"];
	$squid->ssl_port=$_POST["ssl_port"];
	$squid->certificate_center=$_POST["certificate_center"];
	
	$sock->SET_INFO("KernelSendRedirects", $_POST["KernelSendRedirects"]);
	$sock->SET_INFO("SquidTransparentMixed", $_POST["SquidTransparentMixed"]);
	$sock->SET_INFO("SquidOldHTTPPort",$squid->listen_port);
	$sock->SET_INFO("SquidOldSSLPort",$squid->ssl_port);
	$sock->SET_INFO("SquidOldHTTPPort2",$squid->second_listen_port);
	
	if(!$squid->SaveToLdap()){
		echo $squid->ldap_error;
		return;
	}
	
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{listen_port}:{$_POST["listen_port"]}\n",1);
	echo $tpl->javascript_parse_text("HTTP (2):{$_POST["second_listen_port"]}\n",1);
	echo $tpl->javascript_parse_text("{ssl_port}:{$_POST["ssl_port"]}\n",1);
	echo $tpl->javascript_parse_text("{icp_port}:{$_POST["icp_port"]}\n",1);
	echo $tpl->javascript_parse_text("{htcp_port}:{$_POST["htcp_port"]}\n",1);
	if($EnableWebProxyStatsAppliance==1){
		echo $tpl->javascript_parse_text("{proxy_clients_was_notified}\n",1);
	}
			
	echo $tpl->javascript_parse_text("{success}\n",1);
	$sock->getFrameWork("squid.php.php?restart-squid=yes");
	$sock->getFrameWork("cmd.php?restart-apache-src=yes");
		
	
	
		
	
}


function rules_add_default_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->javascript_parse_text("{onlycorpavailable}");
		$content="alert('$onlycorpavailable');";
		echo $content;
		return;
	}	
	
	$t=time();
	
$html="		
	var x_add_default_settings$t= function (obj) {	
			var results=obj.responseText;
			if(results.length>3){alert(results);}
			ExecuteByClassName('SearchFunction');		
				
		}		
		
		function add_default_settings$t(){
		 	var XHR = new XHRConnection();
			XHR.appendData('add_default_settings','yes');
			XHR.sendAndLoad('squid.cached.sitesinfos.php', 'POST',x_add_default_settings$t);
		}


add_default_settings$t();";

echo $html;
	
}

function section_webrules_add_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$website=$tpl->javascript_parse_text("{website}");
	$t=time();	
$html="
		function AddNewCachedWebsite$t(){
			var sitename=prompt('$website ?');
			if(sitename){
				Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&table-t=$t&sitename='+sitename);
			}
		}
		
		AddNewCachedWebsite$t()";

echo $html;
	
}


function delete_all_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$delete_all=$tpl->javascript_parse_text("{delete_all}");
	$t=time();

	$html="
	var xFunct$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);}
		ExecuteByClassName('SearchFunction');

	}

	function Funct$t(){
		if(confirm('$delete_all ?')){
			var XHR = new XHRConnection();
			XHR.appendData('delete_all','yes');
			XHR.sendAndLoad('squid.cached.sitesinfos.php', 'POST',xFunct$t);
		}
	}



Funct$t();";
echo $html;

}



function section_rules(){
	$page=CurrentPageName();
	$tpl=new templates();
	$refresh_pattern_intro=$tpl->_ENGINE_parse_body("{refresh_pattern_intro}");
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<div class=explain style='font-size:16px'>$refresh_pattern_intro</div> <p class=text-error>$onlycorpavailable</p>";
		echo $content;
		return;
	}
	
	$boot=new boostrap_form();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}", "Loadjs('squid.cached.sitesinfos.php?AddCachedSitelist-js=yes&t=$t')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{add_default_settings}", "Loadjs('$page?rules_add_default_js=yes')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{apply}", "Loadjs('squid.restart.php?onlySquid=yes&ApplyConfToo=yes');"));
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>$refresh_pattern_intro</div>"). $boot->SearchFormGen("domain","search-rules",null,$EXPLAIN);
	
	
}
function section_webrules(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$refresh_pattern_intro=$tpl->_ENGINE_parse_body("{refresh_pattern_intro}");
	
	if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<div class=explain style='font-size:16px'>$refresh_pattern_intro</div> <p class=text-error>$onlycorpavailable</p>";
		echo $content;
		return;
	}
	$t=time();
	$boot=new boostrap_form();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_rule}", "Loadjs('$page?section_webrules_add_js=yes')"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{apply}", "Loadjs('squid.restart.php?onlySquid=yes&ApplyConfToo=yes');"));
	echo $tpl->_ENGINE_parse_body("<div class=explain style='font-size:16px'>$refresh_pattern_intro</div>"). 
	$boot->SearchFormGen("sitename","search-webrules",null,$EXPLAIN);
		
	
}


function section_rules_search(){
	$q=new mysql();
	$database="artica_backup";
	$sock=new sockets();
	$tpl=new templates();
	$search='%';
	$table="squid_speed";
	$searchstring=string_to_flexquery("search-rules");
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring ORDER BY domain";
	
	
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror($q->mysql_error);}
	$boot=new boostrap_form();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=md5($ligne["domain"].$ligne["ID"]);
		$color="black";
		$t=time();
		$delete=imgtootltip("delete-24.png","{delete}","Loadjs('squid.cached.sitesinfos.php?AddCachedSitelist-delete={$ligne["ID"]}&t={$_GET["t"]}&IDROW={$ID}')");
		$select="Loadjs('squid.cached.sitesinfos.php?AddCachedSitelist-js=yes&id={$ligne["ID"]}&t={$_GET["t"]}');";
	
		$ligne["refresh_pattern_min"]=$ligne["refresh_pattern_min"];
		$ligne["refresh_pattern_min"]=distanceOfTimeInWords(time(),mktime()+($ligne["refresh_pattern_min"]*60),true);
		$ligne["refresh_pattern_min"]=$tpl->javascript_parse_text($ligne["refresh_pattern_min"]);
	
		$ligne["refresh_pattern_max"]=$ligne["refresh_pattern_max"];
		$ligne["refresh_pattern_max"]=distanceOfTimeInWords(time(),mktime()+($ligne["refresh_pattern_max"]*60),true);
		$ligne["refresh_pattern_max"]=$tpl->javascript_parse_text($ligne["refresh_pattern_max"]);
	

		$link=$boot->trswitch($select);

		$tr[]="
		<tr id='$ID'>
		<td $link><i class='icon-globe'></i>&nbsp;{$ligne["domain"]}</td>
		<td $link width=1% nowrap>{$ligne["refresh_pattern_min"]}</td>
		<td $link width=1% nowrap>{$ligne["refresh_pattern_perc"]}%</td>
		<td $link width=1% nowrap>{$ligne["refresh_pattern_max"]}</td>
		<td width=1% nowrap>$delete</td>
		</tr>";
	}
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{website}</th>
			<th>{expire_time}</th>
			<th>%</th>
			<th>{limit}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>";
	
	}
	
function section_webrules_search(){
	$q=new mysql_squid_builder();
	$database="squidlogs";
	$sock=new sockets();
	$tpl=new templates();
	$search='%';
	$table="websites_caches_params";
	$searchstring=string_to_flexquery("search-webrules");
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring ORDER BY sitename";
	
	
	$results = $q->QUERY_SQL($sql,$database);
	if(!$q->ok){senderror($q->mysql_error);}
	$boot=new boostrap_form();
	$t=time();
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=md5($ligne["sitename"]);
		$delete=imgtootltip("delete-24.png","{delete}","DeleteWebsiteCached$t('{$ligne["sitename"]}','$ID')");
		$select="Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&sitename={$ligne["sitename"]}&table-t={$_GET["t"]}');";
		
		$ligne["MIN_AGE"]=$ligne["MIN_AGE"];
		$ligne["MIN_AGE"]=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),mktime()+($ligne["MIN_AGE"]*60),true));
		
		
		$ligne["MAX_AGE"]=$ligne["MAX_AGE"];
		$ligne["MAX_AGE"]=$tpl->javascript_parse_text(distanceOfTimeInWords(time(),mktime()+($ligne["MAX_AGE"]*60),true));
			
		if(trim($ligne["sitename"])=='.'){$ligne["sitename"]=$tpl->_ENGINE_parse_body("{all}");}
	
	
		$link=$boot->trswitch($select);
		
		
	
		$tr[]="
		<tr id='$ID'>
		<td $link><i class='icon-globe'></i>&nbsp;{$ligne["sitename"]}</td>
		<td $link width=1% nowrap>{$ligne["MIN_AGE"]}</td>
		<td $link width=1% nowrap>{$ligne["PERCENT"]}%</td>
		<td $link width=1% nowrap>{$ligne["MAX_AGE"]}</td>
		<td width=1% nowrap>$delete</td>
		</tr>";
	}
	
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
	
			<thead>
			<tr>
			<th>{website}</th>
			<th>{expire_time}</th>
			<th>%</th>
			<th>{limit}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>
			").@implode("", $tr)."</tbody></table>
<script>
var websiteMem$t='';
		var x_DeleteWebsiteCached$t= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			$('#'+websiteMem$t).remove();			
				
		}	

		function DeleteWebsiteCached$t(domain,id){
			websiteMem$t=id;
			var XHR = new XHRConnection();
			XHR.appendData('DELETE',domain);
			XHR.sendAndLoad('squid.caches32.caches-www.php', 'POST',x_DeleteWebsiteCached$t);
		}
</script>										
					
";
	
	}	
	
	
