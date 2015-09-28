<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsSquidAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["listonly"])){servers();exit;}
if(isset($_GET["servers"])){servers();exit;}
if(isset($_GET["nodes-list"])){serverlist();exit;}
if(isset($_GET["events"])){events();exit;}
if(isset($_GET["statsremoteservers-list-search"])){events_search();exit;}
if(isset($_POST["BuildRemoteConfig"])){BuildRemoteConfig();exit;}
if(isset($_POST["AddArticaAgent"])){AddArticaAgent();exit;}
if(isset($_POST["refresh-node"])){RefreshNode();exit;}




tabs();


function tabs(){
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	
	$array["servers"]="{servers}";
	$array["events"]="{events}";
	


	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$time\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id='main_statsremoteservers_tabs'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_statsremoteservers_tabs').tabs();
			});
		</script>";	

}

function serverlist(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$q=new mysql_blackbox();
	$add_artica_agent_explain=$tpl->javascript_parse_text("{add_artica_agent_explain}");
	
	$t=time();
	$apply=imgtootltip("arrow-down-32.png","{apply} {all}","BuildRemoteConfig()");

	
	$sql="SELECT * FROM nodes";
	$results=$q->QUERY_SQL($sql);
	$classtr=null;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		
		$nodeid=$ligne["nodeid"];
		$server=$ligne["ipaddress"];
		$hostid=$ligne["hostid"];
		$port=$ligne["port"];
		$hostname=$ligne["hostname"];
		$laststatus=distanceOfTimeInWords(time(),strtotime($ligne["laststatus"]));
		$perfs=unserialize(base64_decode($ligne["perfs"]));
		$perftext="&nbsp;";
		$settings=unserialize(base64_decode($ligne["settingsinc"]));
		
		$fqdn_hostname=$settings["fqdn_hostname"];
		if($fqdn_hostname==null){$fqdn_hostname=$server;}
		
		if(is_array($perfs["REALMEM"])){
				$hash_mem=$perfs["REALMEM"];
				$mem_used_p=$hash_mem["ram"]["percent"];
				$mem_used_kb=FormatBytes($hash_mem["ram"]["used"]);
				$total=FormatBytes($hash_mem["ram"]["total"]);
				$color="#5DD13D";
				
				$swapar_perc=$hash_mem['swap']['percent'];
				$swap_color="rgb(93, 209, 61)";
				$swap_text="<br><span style='font-size:9px'>swap: {$swapar_perc}% {used}</span>";
				if($swapar_perc>30){$swap_color="#F59C44";}
				if($swapar_perc>50){$swap_color="#D32D2D";}	
				$swap="<div style=\"border: 1px solid $swap_color; width: 100px; background-color: white; padding-left: 0px; margin-top: 3px;\" ". CellRollOver($swap_js).">
						<div style=\"width: {$swapar_perc}px; text-align: center; color: white; padding-top: 3px; padding-bottom: 3px; background-color:$swap_color;\"> </div>
				</div>";
				
				
				if($mem_used_p>70){$color="#F59C44";}
				if($mem_used_p>79){$color="#D32D2D";}		
				$memtext="<div style='width:100px;background-color:white;padding-left:0px;border:1px solid $color'>
				<div style='width:{$mem_used_p}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:$color'><strong>{$mem_used_p}%</strong></div>
				</div>$swap"	;
			
			//print_r($perfs["MEMORY"]);
		}
		
		if(is_numeric($perfs["LOAD_POURC"])){
			$perfsColor="white";
			if($perfs["LOAD_POURC"]==0){$perfsColor="black";}
		$perftext="
		<table style='width:100%' margin=0 padding=0>
		<tr style='background-color:transparent'>
		<td padding=0px style='border:0px'><span style='font-size:11px'>{load}:</span></td>
		<td padding=0px style='border:0px'>
		<div style='width:100px;background-color:white;padding-left:0px;border:1px solid {$perfs["LOAD_COLOR"]};margin-top:3px'>
			<div style='width:{$perfs["LOAD_POURC"]}px;text-align:center;color:white;padding-top:3px;padding-bottom:3px;background-color:{$perfs["LOAD_COLOR"]}'>
				<span style='color:$perfsColor;font-size:11px;font-weight:bold'>{$perfs["LOAD_POURC"]}%</span>
			</div>
		</div>
		</td >
		</tr'>
		<tr padding=0px style='background-color:transparent'>
			<td style='border:0px'><span style='font-size:11px'>{memory}:</span></td>
			<td style='border:0px'>$memtext</td>
		</tr>
		</table>";

		
		}
		
		$tooltip="<li>Load:{$perfs["LOAD_POURC"]}%</li><li>Swap:{$swapar_perc}%</li><li>Mem:{$mem_used_p}%</li>";		
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('nodes.php?nodeid=$nodeid&hostid=$hostid')\" style='font-size:16px;text-decoration:underline'>";
		
			
		$tr[]=Paragraphe("64-network-server.png", "$fqdn_hostname", 
		"<div style='font-size:11px'><i>{since}:$laststatus</i></div>$perftext",
		"javascript:Loadjs('nodes.php?nodeid=$nodeid&hostid=$hostid')",$tooltip,340,null,1);

			
		
	}
	
	$html=CompileTr2($tr,"form");

	echo $tpl->_ENGINE_parse_body($html);
	
}

function servers(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();	
	$q=new mysql_blackbox();
	$add_artica_agent_explain=$tpl->javascript_parse_text("{add_artica_agent_explain}");
	
	$t=time();
	$apply=imgtootltip("arrow-down-32.png","{apply} {all}","BuildRemoteConfig()");
	
	$html="
	<div style='width:100%;text-align:right;float:right'>". imgtootltip("refresh-24.png","{refresh}","RefreshTableau()")."</div>
	
	<center id='$t'></div>
	
	</center>
	<div class=explain style='font-size:14px'>{prodservers_statsappliance_explain}</div>
	<script>
	
		
	var x_BuildRemoteConfig= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTableau();
		
	}	
	
	var x_BuildRemoteConfig$t= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTableau();
	}	
	
	function BuildRemoteConfig(){
		var XHR = new XHRConnection();
		XHR.appendData('BuildRemoteConfig','yes');
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_BuildRemoteConfig);	
	}	

	function AddArticaAgent(){
		var ipport=prompt('$add_artica_agent_explain');
		if(!ipport){return;}
		var XHR = new XHRConnection();
		XHR.appendData('AddArticaAgent',ipport);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_BuildRemoteConfig);
	}
	
	function RefreshNode(ID){
		var XHR = new XHRConnection();
		XHR.appendData('refresh-node',ID);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',x_BuildRemoteConfig$t);	
	
	}
	
	function RefreshTableau(){
		LoadAjax('$t','$page?nodes-list=yes');
	}
	
	RefreshTableau();
</script>	
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function BuildRemoteConfig(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");	
}

function events(){
	$t=time();
	
	$html="
	<div id='$t'></div>
	<script>
		LoadAjax('$t','squid.update.events.php?popup=yes&filename=&taskid=&category=communicate&tablesize=&descriptionsize=&table=&tablesize=835&descriptionsize=658')
	</script>
	";
	echo $html;
	
}




function RefreshNode(){
	$node=new blackboxes($_POST["refresh-node"]);
	if(!$node->chock()){
		$tpl=new templates();
		echo $tpl->javascript_parse_text($node->last_error);
	}
	
	
}

function AddArticaAgent(){
	$tpl=new templates();
	$sock=new sockets();
	$pattern=$_POST["AddArticaAgent"];
	$ip=$pattern;
	if(preg_match("#(.+?):([0-9]+)#", $pattern,$re)){$ip=$re[1];$port=$re[2];}
	if(!is_numeric($port)){$port=9001;}
	include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
	
	
	$ArticaHttpsPort=$sock->GET_INFO("ArticaHttpsPort");
	$ArticaHttpUseSSL=$sock->GET_INFO("ArticaHttpUseSSL");
	if(!is_numeric($ArticaHttpUseSSL)){$ArticaHttpUseSSL=1;}
	if(!is_numeric($ArticaHttpsPort)){$ArticaHttpsPort="9000";}	
	
	
	$time=date('Y-m-d H:i:s');
	if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ip)){
		$hostname=gethostbyaddr($ip);
	}else{
		$hostname=$ip;
		$ip=gethostbyname($hostname);
	}

	$q=new mysql_blackbox();
	$q->CheckTables();
	$sql="INSERT IGNORE INTO `nodes` (ipaddress,hostname,port,laststatus) VALUES ('$ip','$hostname','$port','$time')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	if($q->last_id==0){
		echo "Unable to get Last ID\n";return;
	}
	$curl=new ccurl("http://$ip:$port/listener.php");
	$curl->parms["REGISTER"]="yes";
	$curl->parms["SSL"]=$ArticaHttpUseSSL;
	$curl->parms["PORT"]=$ArticaHttpsPort;
	$curl->parms["NODE_ID"]=$q->last_id;
	if(!$curl->get()){echo $tpl->javascript_parse_text("{$curl->error}")."\nhttp://$ip:$port";return;}
	if(!preg_match("#<SUCCESS>#is", $curl->data)){echo $tpl->javascript_parse_text("{failed} `http://$ip:$port`");return;}	
	
}	
