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
	<div id=main_statsremoteservers_tabs style='width:99%;overflow:auto'>
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
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th width=1%>". imgtootltip("plus-24.png","{add}","AddArticaAgent()")."</th>
		<th width=99%>{servers}:{listen_port}</th>
		<th>{load}</th>
		<th width=1%>{refresh}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	$sql="SELECT * FROM nodes";
	$results=$q->QUERY_SQL($sql);
	$classtr=null;
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$nodeid=$ligne["nodeid"];
		$server=$ligne["ipaddress"];
		$port=$ligne["port"];
		$hostname=$ligne["hostname"];
		$laststatus=distanceOfTimeInWords(time(),strtotime($ligne["laststatus"]));
		$perfs=unserialize(base64_decode($ligne["perfs"]));
		$perftext="&nbsp;";
		$settings=unserialize(base64_decode($ligne["settingsinc"]));
		
		$fqdn_hostname=$settings["fqdn_hostname"];
		if($fqdn_hostname==null){$fqdn_hostname=$server;}
		
		if(is_array($perfs["MEMORY"])){
				$hash_mem=$perfs["MEMORY"];
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
		
		
		$href="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('nodes.php?nodeid=$nodeid')\" style='font-size:16px;text-decoration:underline'>";
		
		$graphs="&nbsp;";
		if(is_file("ressources/conf/upload/$nodeid/connections.day.png")){$graphs=imgtootltip("graphs-32.png","{graphs}","Loadjs('squid.graphs.php?hostname-js=$hostname')");} 
		
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
			$html=$html."
			<tr class=$classtr>
			<td width=1% align=center valign='middle'><img src='img/32-white-computer.png'></td>
			<td style='font-size:16px' width=99%><strong>$href$fqdn_hostname:$port</a></strong><div style='font-size:11px'><i>{since}:$laststatus</i></div></td>
			<td>$perftext</td>
			<td width=1% align=center valign='middle'>". imgtootltip("refresh-32.png","{refresh}","RefreshNode($nodeid)")."</td>
			</tr>
			
			
			";
			
		
	}
	
	$html=$html."</tbody></table>";

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
	<div class=explain style='font-size:14px'>{prodservers_statsappliance_explain}</div>
	<center id='$t'></div>
	
	</center>
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
	$tpl=new templates();
	$page=CurrentPageName();
	
	
	
	$html="
	<table style='width:99%' class=form>
		<tbody>
			<tr>
				<td class=legend>{search}:</td>
				<td>". Field_text("statsremoteservers-search",null,"font-size:16px",null,null,null,false,"statsremoteservers_SearchEventsCheck(event)")."</td>
				<td>". button("{search}","statsremoteservers_SearchEvents()")."</td>
			</tr>
		</tbody>
	</table>
	<div id='statsremoteservers-list-table' style='width:100%;height:350px;overflow:auto;background-color:white'></div>
	
	<script>
		function statsremoteservers_SearchEventsCheck(e){
			if(checkEnter(e)){statsremoteservers_SearchEvents();}
		}
	
		function statsremoteservers_SearchEvents(){
			var se=escape(document.getElementById('statsremoteservers-search').value);
			LoadAjax('statsremoteservers-list-table','$page?statsremoteservers-list-search=yes&search='+se);
		}
	
	statsremoteservers_SearchEvents();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function events_search(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$search="*".$_GET["search"]."*";
	$search=str_replace("**", "*", $search);
	$search=str_replace("**", "*", $search);
	$search=str_replace("*", "%", $search);
	$emailing_campain_linker_delete_confirm=$tpl->javascript_parse_text("{emailing_campain_linker_delete_confirm}");
	
	$style="style='font-size:14px;'";
	
	
	$sql="SELECT * FROM stats_appliance_events WHERE `events` LIKE '$search' ORDER BY zDate DESC LIMIT 0,150";
	
	$html="<center>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>{date}</th>
		<th>{hostname}</th>
		<th>{events}&nbsp;|&nbsp;$search</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
		$q=new mysql_squid_builder();
		writelogs("$sql",__FUNCTION__,__FILE__,__LINE__);
		$results=$q->QUERY_SQL($sql);
		$cs=0;
		if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
		while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(preg_match("#line:([0-9]+)\s+script:(.+)#", $ligne["text"],$re)){
			$ligne["text"]=str_replace("line:{$re[1]} script:{$re[2]}", "", $ligne["text"]);
		}
		$line=$re[1];
		$file=$re[2];
		$ligne["text"]=htmlentities($ligne["text"]);
		$ligne["text"]=nl2br($ligne["text"]);
		
		$html=$html."
		<tr class=$classtr>
			<td width=1% $style nowrap>{$ligne["zDate"]}</td>
			<td width=1% $style nowrap>{$ligne["hostname"]}</td>
			<td width=99% $style nowrap>{$ligne["events"]}</td>
			
			
		</tr>
		";
	}
	$html=$html."</tbody></table>
	
	<script>

	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
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
