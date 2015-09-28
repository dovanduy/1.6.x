<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.influx.inc');
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["list"])){nodes_list();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["node-infos-js"])){node_infos_js();exit;}
	if(isset($_GET["node-infos-tabs"])){node_infos_tabs();exit;}
	if(isset($_GET["node-infos-status"])){node_infos_status();exit;}
	if(isset($_GET["node-infos-UserAgents"])){node_infos_UserAgents();exit;}
	if(isset($_GET["node-infos-UserAgents-list"])){node_infos_UserAgents_list();exit;}
	if(isset($_GET["node-infos-IPADDRS"])){node_infos_UserAgents();exit;}
	
	if(isset($_GET["node-infos-RTIME"])){node_infos_realtime();exit;}
	if(isset($_GET["node-infos-RTIME-LIST"])){node_infos_realtime_list();exit;}
	
	if(isset($_GET["link-user-js"])){link_user_js();exit;}
	if(isset($_GET["link-user-popup"])){link_user_popup();exit;}
	if(isset($_POST["link-user-save"])){link_user_save();exit;}
	
	if(isset($_GET["delete-member-js"])){delete_user_js();exit;}
	if(isset($_POST["delete-member-perform"])){delete_user_perform();exit;}
	
	if(isset($_GET["mac-lock-js"])){mac_lock_js();exit;}
	if(isset($_POST["mac-lock"])){mac_lock();exit;}
	
	if(isset($_GET["mac-unlock-js"])){mac_unlock_js();exit;}
	if(isset($_POST["mac-unlock"])){mac_unlock();exit;}
	
js();
function link_user_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{proxy_alias}: {link_to_an_user}");
	
	$MAC=$_GET["MAC"];
	$ipaddr=$_GET["ipaddr"];
	$ipaddr=urlencode($ipaddr);
	$MAC=urlencode($MAC);
	$html="YahooWin6('850','$page?link-user-popup=yes&MAC=$MAC&ipaddr=$ipaddr','$title');";
	echo $html;
}

function mac_lock_js(){
	header("content-type: application/x-javascript");
	$MAC=$_GET["MAC"];
	$page=CurrentPageName();
	$t=time();
echo "
var xdel$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);}
    if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
    if(IsFunctionExists('RefreshNodesSquidTbl')){ RefreshNodesSquidTbl();}
}	
	
function del$t(){
	var XHR = new XHRConnection();
	XHR.appendData('mac-lock','$MAC');
	XHR.sendAndLoad('$page', 'POST',xdel$t);			
}			
del$t();	
";
}
function mac_unlock_js(){
header("content-type: application/x-javascript");
$md5=$_GET["zmd5"];
$page=CurrentPageName();
$t=time();
echo "
var xdel$t=function(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
	if(IsFunctionExists('RefreshNodesSquidTbl')){ RefreshNodesSquidTbl();}
	 if(document.getElementById('OCS_SEARCH_TABLE')){
      	var id=document.getElementById('OCS_SEARCH_TABLE').value;
      	$('#'+id).flexReload();
      }
      
}
	
function del$t(){
	var XHR = new XHRConnection();
	XHR.appendData('mac-unlock','$md5');
	XHR.sendAndLoad('$page', 'POST',xdel$t);
}
del$t();
	";	
	
}

function mac_unlock(){
	$q=new mysql_squid_builder();
	$md5=$_POST["mac-unlock"];
	$sql="UPDATE webfilters_blkwhlts SET enabled=0 WHERE zmd5='$md5'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?quick-ban=yes");	
	
}

function mac_lock(){
	$q=new mysql_squid_builder();
	$MAC=$_POST["mac-lock"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zmd5 FROM webfilters_blkwhlts
	WHERE `pattern`='$MAC' AND `PatternType`=1 AND `blockType`=0"));
	
	if($ligne["zmd5"]==null){
		$zmd5=md5("{$MAC}10");
		$sql="INSERT IGNORE INTO webfilters_blkwhlts (zmd5,description,enabled,PatternType,blockType,pattern)
		VALUES('$zmd5','Block $MAC',1,1,0,'$MAC')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		$sock=new sockets();
		$sock->getFrameWork("squid.php?quick-ban=yes");
		return;
	}
	
	$sql="UPDATE webfilters_blkwhlts SET enabled=1 WHERE zmd5='{$ligne["zmd5"]}'";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?quick-ban=yes");
	
}

function delete_user_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$table=$_GET["table"];
	$member=$_GET["member"];
	$field=$_GET["field"];
	$value=$_GET["value"];
	$t=time();
	$ask=$tpl->javascript_parse_text("{unlink} $field $value -> $member ?");
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{link_to_an_user}");
	$html="
			
		
	var xdel$t=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>3){alert(tempvalue);}
      if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
      if(IsFunctionExists('RefreshNodesSquidTbl')){ RefreshNodesSquidTbl();}
       if(document.getElementById('OCS_SEARCH_TABLE')){
      	var id=document.getElementById('OCS_SEARCH_TABLE').value;
      	$('#'+id).flexReload();
      }
      
     
     }	
	
		function del$t(){
			if(!confirm('$ask')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('delete-member-perform','yes');
			XHR.appendData('uid','$member');
			XHR.appendData('table','$table');
			XHR.appendData('field','$field');
			XHR.appendData('value','$value');
			XHR.sendAndLoad('$page', 'POST',xdel$t);			
		}
del$t();
	";
	echo $html;	
	
}
function delete_user_perform(){
	$sql="DELETE FROM `{$_POST["table"]}` WHERE `uid`='{$_POST["uid"]}' AND `{$_POST["field"]}`='{$_POST["value"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("squid.php?user-retranslation=yes&update=yes");	
	
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->_ENGINE_parse_body("{computers}");
	$html="YahooWin4('592.6','$page?popup=yes&filterby={$_GET["filterby"]}&fieldname={$_GET["fieldname"]}','$title');";
	echo $html;
}

function node_infos_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$computer=new computers();
	header("content-type: application/x-javascript");
	if($_GET["MAC"]<>null){
		$uid=$computer->ComputerIDFromMAC($_GET["MAC"]);
		$title=$tpl->_ENGINE_parse_body("{status}::{computer}:{$_GET["MAC"]}::$uid");
		$html="YahooWin5('1200','$page?node-infos-tabs=yes&MAC={$_GET["MAC"]}','$title');";
	}
	
	if($_GET["ipaddr"]<>null){
		$title=$tpl->_ENGINE_parse_body("{status}::{computer}:{$_GET["ipaddr"]}");
		$html="YahooWin5('1200','$page?node-infos-tabs=yes&ipaddr={$_GET["ipaddr"]}','$title');";
	}	
	
	echo $html;	
	
}

function link_user_save(){
	
	
	$ipClass=new IP();
	if(!$ipClass->IsvalidMAC($_POST["MAC"])){$_POST["MAC"]=null;}
	if(!$ipClass->isValid($_POST["ipaddr"])){$_POST["ipaddr"]=null;}
	
	
	$_POST["MAC"]=str_replace("-", ":", $_POST["MAC"]);
	$_POST["MAC"]=strtolower($_POST["MAC"]);
	
	
	
	$q=new mysql_squid_builder();
	if(!$q->FIELD_EXISTS("webfilters_ipaddr", "ip")){
		$q->QUERY_SQL("ALTER TABLE `webfilters_ipaddr` ADD `ip` int(10) unsigned NOT NULL default '0',ADD INDEX ( `ip` )");
	}
	
	$_POST["uid"]=mysql_escape_string2($_POST["uid"]);
	

	
	if($_POST["MAC"]<>null){
		$sql="UPDATE webfilters_nodes SET uid='{$_POST["uid"]}' WHERE MAC='{$_POST["MAC"]}'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT MAC FROM webfilters_nodes WHERE MAC='{$_POST["MAC"]}'"));
	
		if($ligne["MAC"]==null){
			$sql="INSERT INTO webfilters_nodes (MAC,uid,hostname,nmapreport,nmap) 
			VALUES ('{$_POST["MAC"]}','{$_POST["uid"]}','','',0)";
		}
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			echo "Fatal:".$q->mysql_error;
			return;
		}
		
		return;
	}
	
	
	if($_POST["ipaddr"]<>null){
		$ip2Long2=ip2Long2($_POST["ipaddr"]);
		$sql="UPDATE webfilters_ipaddr SET uid='{$_POST["uid"]}',`ip`='$ip2Long2' WHERE ipaddr='{$_POST["ipaddr"]}'";
		$q=new mysql_squid_builder();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT ipaddr FROM webfilters_ipaddr WHERE ipaddr='{$_POST["ipaddr"]}'"));
	
		if($ligne["ipaddr"]==null){ $sql="INSERT INTO webfilters_ipaddr (ipaddr,uid,ip,hostname) VALUES ('{$_POST["ipaddr"]}','{$_POST["uid"]}','$ip2Long2','')"; }
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo "Fatal:".$q->mysql_error;return;}
		return;
	}	
	
	
	echo "Cannot associate a Proxy alias without any valid IP address or MAC address";
	
}

function link_user_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	
	$IPclass=new IP();
	
	
	if($_GET["MAC"]<>null){
		if($IPclass->IsvalidMAC($_GET["MAC"])){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$_GET["MAC"]}'"));
			$member=$ligne["uid"];
		}
	}
	
	if($_GET["ipaddr"]<>null){
		if($member==null){
			if($IPclass->isValid($_GET["ipaddr"])){
				$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_ipaddr WHERE ipaddr='{$_GET["ipaddr"]}'"));
				$member=$ligne["uid"];
			}
		}
	}
	$t=time();
	if($_GET["MAC"]==null){
		if($_GET["ipaddr"]==null){
			
			$form_add="
			<tr>
				<td class=legend style='font-size:26px'>{MAC}:</td>
				<td>". Field_text("$t-MAC",null,"font-size:26px;width:250px",null,null,null,false,"LinkUserStatsDBcHeck(event)")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:26px'>{ipaddr}:</td>
				<td>". field_ipv4("$t-ipaddr",null,"font-size:26px;width:250px",null,null,null,false,"LinkUserStatsDBcHeck(event)")."</td>
			</tr>			
						
			";
			
		}
		
	}
	
	
	
	$bt_name="{apply}";
	if($member==null){$bt_name="{add}";}
	
	$you_need_to_reconfigure_proxy=$tpl->javascript_parse_text("{you_need_to_reconfigre_proxy}");
	
	$html="
	<div id='div-$t' style='width:98%' class=form>
	<div style='font-size:30px;margin-bottom:20px'>{proxy_alias}: {$_GET["MAC"]} / {$_GET["ipaddr"]}</div>
	<table style='width:100%'>$form_add
	<tr>
		<td class=legend style='font-size:26px'>{alias}:</td>
		<td>". Field_text("$t-uid",$member,"font-size:26px;width:550px",null,null,null,false,"LinkUserStatsDBcHeck(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button($bt_name,"LinkUserStatsDB()",32)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var x_LinkUserStatsDB=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>3){alert(tempvalue);return;}
      YahooWin6Hide();
      if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
      
      if(document.getElementById('OCS_SEARCH_TABLE')){
      	var id=document.getElementById('OCS_SEARCH_TABLE').value;
      	$('#'+id).flexReload();
      }
 	 if(document.getElementById('PROXY_ALIASES_TABLE')){
      	$('#PROXY_ALIASES_TABLE').flexReload();
      }  

      if(document.getElementById('container-computer-tabs')){
      	RefreshTab('container-computer-tabs');
      }
      
      if(IsFunctionExists('RefreshNodesSquidTbl')){ RefreshNodesSquidTbl();}
	  Loadjs('squid.macToUid.progress.php');
     }	

     function LinkUserStatsDBcHeck(e){
     	if(checkEnter(e)){LinkUserStatsDB();}
     
     }
	
	function LinkUserStatsDB(){
		var XHR = new XHRConnection();
		XHR.appendData('link-user-save','yes');
		XHR.appendData('uid',document.getElementById('$t-uid').value);
		if(!document.getElementById('$t-MAC') ){
			XHR.appendData('MAC','{$_GET["MAC"]}');
		}else{
			XHR.appendData('MAC',document.getElementById('$t-MAC').value);
		}
		
		if(!document.getElementById('$t-ipaddr') ){
			XHR.appendData('ipaddr','{$_GET["ipaddr"]}');
		}else{
			XHR.appendData('ipaddr',document.getElementById('$t-ipaddr').value);
		}		
		
		
		XHR.sendAndLoad('$page', 'POST',x_LinkUserStatsDB);
	}	
	
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function node_infos_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	
	$tablesrc="WEEK_RTTH";
	$tablesrc_hour="RTTH_".date("YmdH");
	
	$array["node-infos-status"]="{status}";
	$array["node-infos-UserAgents"]="{UserAgents}";
	
	
	if($_GET["MAC"]<>null){
		$array["SQUIDBLK"] = "{internet_access}";
	}
	
	$array["node-infos-RTIME"]="{realtime_requests}";
	


	
	if($users->PROXYTINY_APPLIANCE){
		unset($array["node-infos-WEBACCESS"]);
		unset($array["node-infos-RULES"]);
	}
	
	if($_GET["MAC"]==null){
		unset($array["node-infos-IPADDRS"]);
	}
	
	$textsize="22px";

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
	if($num=="node-infos-WEBACCESS"){
		$link=null;
			if($_GET["MAC"]<>null){
				$link="squid.users-stats.currentmonth.php?tabs=yes&field=MAC&value={$_GET["MAC"]}";
			}
			if($link==null){
				if($_GET["ipaddr"]<>null){
					$link="squid.users-stats.currentmonth.php?tabs=yes&field=ipaddr&value={$_GET["ipaddr"]}";
				}
			}
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
	if($num=="node-infos-GROUPS"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.groups.php?MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}\"><span>$ligne</span></a></li>\n");
			continue;
	}	
	
	if($num=="node-infos-this-hour"){
		if($_GET["MAC"]<>null){
			$link="squid.member.RTTH.current.php?popup=yes&field=MAC&value={$_GET["MAC"]}";
		}
		if($link==null){
			$link="squid.member.RTTH.current.php?popup=yes&field=ipaddr&value={$_GET["ipaddr"]}";
		}
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
		continue;
	}

	
	if($num=="SQUIDBLK"){
		$link="squid.computer.access.php?mac={$_GET["MAC"]}&t={$_GET["t"]}&increment=";
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$link\"><span>$ligne</span></a></li>\n");
		continue;
	}	
	

	if($num=="node-infos-RULES"){
			//$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.accessrules.php?MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}			
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_node_infos_tab");

		
}


function node_infos_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$users=new usersMenus();
	
	$computer=new computers();
	$uid=$computer->ComputerIDFromMAC($_GET["MAC"]);
	$uidORG=$uid;
	if($uid==null){$uid="{no_entry}";}else{
		$jsfiche=MEMBER_JS($uid,1,1);
		$uid=str_replace("$", "", $uid);
		$uid="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$jsfiche\" 
		style='font-size:18px;font-weight:bolder;text-decoration:underline'>$uid</a>";
	}
	
	
	if($_GET["MAC"]<>null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$_GET["MAC"]}'"));
		$member=$ligne["uid"];
		$member=$ligne["uid"];
		$member_enc=urlencode($member);
		$value_enc=urlencode($_GET["MAC"]);
		$member_delete=imgtootltip("delete-48.png","{unlink}","Loadjs('$page?delete-member-js=yes&table=webfilters_nodes&member=$member_enc&field=MAC&value=$value_enc',true)");
	}
	
	if($_GET["ipaddr"]<>null){
		if($member==null){
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_ipaddr WHERE ipaddr='{$_GET["ipaddr"]}'"));
			$member=$ligne["uid"];
			$member_enc=urlencode($member);
			$value_enc=urlencode($_GET["ipaddr"]);
			$member_delete=imgtootltip("delete-48.png","{unlink}","Loadjs('$page?delete-member-js=yes&table=webfilters_ipaddr&member=$member_enc&field=ipaddr&value=$value_enc',true)");
		}
	}
	
	if($member==null){
		$imagedegauche=imgtootltip("folder-useradd-64.png","{link_to_an_user}",
				"Loadjs('$page?link-user-js=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}')");
		$textImage="{link_to_an_user}";
		$member="{link_to_an_user}";
	}
	
	
	$ArrayNMap=unserialize(base64_decode($ligne["nmapreport"]));
		if(is_array($ArrayNMap)){
			if($ArrayNMap["OS"]<>null){$NMAPS[]="
			<tr>
				<td>&nbsp;</td>
				<td class=legend style='font-size:18px' nowrap>{OS}:</td>
				<td style='font-size:18px;font-weight:bolder'>{$ArrayNMap["OS"]}</td>
			</tr>
			";}
			
			if($ArrayNMap["UPTIME"]<>null){$NMAPS[]="
			<tr>
				<td>&nbsp;</td>
				<td class=legend style='font-size:18px' nowrap>{uptime}:</td>
				<td style='font-size:18px;font-weight:normal'>{$ArrayNMap["UPTIME"]}</td>
			</tr>
			";}			
			if(count($ArrayNMap["PORTS"])>0){$NMAPS[]="
			<tr>
				<td>&nbsp;</td>
				<td class=legend style='font-size:18px' nowrap>{opened_ports}:</td>
				<td style='font-size:18px;font-weight:bolder'>".count($ArrayNMap["PORTS"])."</td>
			</tr>
			";}				
			
			if(count($NMAPS)>0){$NMAPS_TXT=@implode("", $NMAPS);}
			
			
		}	
	
	
	$ipClass=new IP();
	if($ipClass->IsvalidMAC($_GET["MAC"])){
		
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT zmd5 FROM webfilters_blkwhlts
		WHERE `pattern`='{$_GET["MAC"]}' and enabled=1 AND `PatternType`=1 AND `blockType`=0"));
		if(!$q->ok){
			$error="<tr><td colspan=2><p class=text-error>$q->mysql_error</p></td></tr>";
		}
		//32-stop.png
		//32-run.png
		if($ligne["zmd5"]<>null){
			$blockMAC="<tr>
				<td>&nbsp;</td>
				<td class=legend style='font-size:18px;color:#B90808'>
				<div style='float:left'><img src='img/warn-red-24.png'></div>
				{blocked}:</td>
				<td style='font-size:18px;font-weight:bolder'>
					". imgtootltip("32-run.png","{unlock}","Loadjs('$page?mac-unlock-js=yes&zmd5={$ligne["zmd5"]}')")."</td>
					</td>
			</tr>$error";
			
		}else{
			$blockMAC="<tr>
				<td>&nbsp;</td>
				<td class=legend style='font-size:18px;'>{deny}:</td>
				<td style='font-size:18px;font-weight:bolder'>
					". imgtootltip("32-run.png","{deny}","Loadjs('$page?mac-lock-js=yes&MAC={$_GET["MAC"]}')")."</td>
					</td>
			</tr>$error";			
			
		}
		
		////pattern           | description                                        | enabled | PatternType | blockType | zmd5
		
		
	}

	//$sql="INSERT IGNORE INTO webfilters_blkwhlts (description,enabled,PatternType,blockType,pattern)
	//VALUES('$description',1,{$_POST["PatternType"]},{$_POST["blk"]},'{$_POST["pattern"]}')";
	$main_incon="<img src='img/computer-tour-128.png'>";
	if($users->nmap_installed){
		$main_incon=imgtootltip("scan-128.png","{scan_this_computer}","Loadjs('nmap.progress.php?MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}')");
	}
	
		
	$uidORG=str_replace("$", "", $uidORG);
	$jsnode="<a href=\"javascript:blur();\" 
		style='font-size:18px;font-weight:bolder;text-decoration:underline'
		OnClick=\"javascript:Loadjs('$page?link-user-js=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}',true)\">";
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%;margin:-8px'>
	<tr>
		<td valign='top' width=1%>
			<center style='width:90%;margin-left:10px;margin-top:15px;margin-right:40px'>$main_incon </center></td>
		<td valign='top' width=99% style='padding-left:15px'>
			<div style='font-size:32px;text-align:left'>{$_GET["MAC"]}&nbsp;|&nbsp;$uidORG</div>
			<table>
			$NMAPS_TXT
			</table>
			<p>&nbsp;</p>
			<table style='width:100%'>
				<tbody>
					<tr>
						<td width=1% nowrap><img src='img/folder-network-48.png'></td>
						<td class=legend style='font-size:18px' width=5% nowrap>{MAC}:</td>
						<td style='font-size:18px;font-weight:bolder' width=70% nowrap>{$_GET["MAC"]}</td>
					</tr>
					$blockMAC
					<tr>
						<td class=legend colspan=3 align=right><p>&nbsp;</p></td>
					</tr>					
					<tr>
						<td width=1% nowrap><img src='img/48-computer.png'></td>
						<td class=legend style='font-size:18px' nowrap>{in_database}:</td>
						<td style='font-size:18px;font-weight:bolder'>$uid</a></td>
					</tr>	
					<tr>
						<td class=legend colspan=3 align=right><p>&nbsp;</p></td>
					</tr>
					<tr>
						<td width=1% nowrap><img src='img/user-48.png'></td>
						<td class=legend style='font-size:18px' nowrap>{member}:</td>
						<td style='font-size:18px;font-weight:bolder'>
							<table>
								<tr>
									<td>$jsnode$member</a></td>
									<td><span style='margin-left:10px'>$member_delete</span></td>
								</tr>
								<tr>
									<td colspan=2 align='right'><i style='font-size:12px'>{squid_node_alias_explain}</i></td>
								</tr>
							</table>
						</td>
					</tr>														
				</tbody>
			</table>
		</td>
	</tr>
	</table></div>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

	

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$ComputerMacAddress=$tpl->_ENGINE_parse_body("{ComputerMacAddress}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$group=$tpl->_ENGINE_parse_body("{group}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$TB_WIDTH=570;
	$t=time();
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?list=yes&filterby={$_GET["filterby"]}&fieldname={$_GET["fieldname"]}&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$ComputerMacAddress', name : 'MAC', width : 147, sortable : true, align: 'left'},
		{display: '$hostname', name : 'MAC', width : 201, sortable : false, align: 'left'},
		{display: '$member', name : 'uid', width : 98, sortable : true, align: 'left'},
		{display: 'active', name : 'active', width : 31, sortable : false, align: 'left'},
	],
	searchitems : [
		{display: '$ComputerMacAddress', name : 'MAC'},
		{display: '$member', name : 'uid'},
		],
	sortname: 'MAC',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});
});

function SelectUser$t(val){
	if(!document.getElementById('{$_GET["fieldname"]}')){
		alert('id: {$_GET["fieldname"]} no such item');
		return;
	}
	document.getElementById('{$_GET["fieldname"]}').value=val;
	
}

function RefreshNodesSquidTbl(){
	$('#$t').flexReload();
}

</script>	";
echo $tpl->_ENGINE_parse_body($html);	
}
function nodes_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$defaultday=$q->HIER();
	$TableActive=date('Ymd',strtotime($defaultday." 00:00:00"))."_hour";
	$t=$_GET["t"];
	
	$filterby=$_GET["filterby"];
	$fieldname=$_GET["fieldname"];
	
	$search='%';
	$table="webfilters_nodes";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	
	if($q->COUNT_ROWS($table)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	$results = $q->QUERY_SQL($sql);
	
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	

	$uptime=$tpl->_ENGINE_parse_body("{uptime}");
	$ports=$tpl->_ENGINE_parse_body("{ports}");
	
	if(!isset($_SESSION["PROXY_MAC_ACTIVE"])){
		$results3 = $q->QUERY_SQL("SELECT COUNT(sitename) AS TCOUNT,MAC FROM $TableActive GROUP BY MAC");
		while ($ligne = mysql_fetch_assoc($results3)) {
			$_SESSION["PROXY_MAC_ACTIVE"][$ligne["MAC"]]=true;
		}
	}
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["MAC"];
		$md5=md5($ligne["MAC"]);
		$ligne["uid"]=utf8_encode($ligne["uid"]);
		$enabled=0;
		$textToAdd=null;
		$js="Loadjs('$MyPage?node-infos-js=yes&MAC={$ligne["MAC"]}');";
		$results2=$q->QUERY_SQL("SELECT hostname FROM (SELECT hostname FROM UserAutDB WHERE MAC='{$ligne["MAC"]}') as t GROUP BY hostname");
		if(!$q->ok){$tt[]=$q->mysql_error;}
		$tt=array();
		$ArrayNMap=array();
		$NMAPS=array();
		
		$ArrayNMap=unserialize(base64_decode($ligne["nmapreport"]));
		if(is_array($ArrayNMap)){
			if($ArrayNMap["OS"]<>null){$NMAPS[]="OS:{$ArrayNMap["OS"]}";}
			if($ArrayNMap["UPTIME"]<>null){$NMAPS[]="$uptime:{$ArrayNMap["UPTIME"]}";}
			if(count($ArrayNMap["PORTS"])>0){$NMAPS[]=count($ArrayNMap["PORTS"])." $ports";}
			if(count($NMAPS)>0){$textToAdd="<div style='font-size:10px'>".@implode(" ", $NMAPS)."</div>";}
		}
		
		$img_active="<img src='img/20-check-grey.png'>";
			if($_SESSION["PROXY_MAC_ACTIVE"][$ligne["MAC"]]){$img_active="<img src='img/20-check.png'>";
		}
		
		
		while ($ligne2 = mysql_fetch_assoc($results2)) {
			$link=null;
			if(trim($ligne2["hostname"])==null){continue;}
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne2["hostname"])){
				if($filterby=="ipaddr"){
					$link="<a href=\"javascript:blur();\" 
					OnClick=\"javascript:SelectUser$t('{$ligne2["hostname"]}');\" 
					style='text-decoration:underline'>";
				}
				$tt[]="$link{$ligne2["hostname"]}</a>";
				continue;
			}
			if(strpos($ligne2["hostname"], ".")>0){
				$ss=explode(".", $ligne2["hostname"]);
				$hostname=$ss[0];}
				else{$hostname=$ligne2["hostname"];}
				
				if($filterby=="hostname"){
					$link="<a href=\"javascript:blur();\" 
					OnClick=\"javascript:SelectUser$t('$hostname');\" 
					style='text-decoration:underline'>";
				}				
				
			$tt[]="$link{$hostname}</a>";
		}
		
		$maclink="<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:18px;text-decoration:underline'>";
		
		if($filterby<>null){
			
			if($filterby=="MAC"){
				$maclink="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:SelectUser$t('{$ligne["MAC"]}');\" 
				style='font-size:18px;text-decoration:underline'>";
			}
			
			
			if($filterby=="uid"){
				$uidlink="<a href=\"javascript:blur();\" 
				OnClick=\"javascript:SelectUser$t('{$ligne["uid"]}');\" 
				style='font-size:18px;text-decoration:underline'>";
			}			
			
		}
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"$maclink{$ligne["MAC"]}</a></span>",
			"<span style='font-size:11px'>". @implode(", " , $tt)."</span>$textToAdd",
			"<span style='font-size:18px'>$uidlink{$ligne["uid"]}</a></span>",
			$img_active)
		);
	}
	
	
echo json_encode($data);		

}



function node_infos_UserAgents(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$SquidPerformance=intval($sock->GET_INFO("SquidPerformance"));
	if($SquidPerformance>1){
		echo FATAL_ERROR_SHOW_128("{SQUID_LOCAL_STATS_DISABLED}");
		die();
	}
	
	$UserAgentsStatistics=intval($sock->GET_INFO("UserAgentsStatistics"));
	if($UserAgentsStatistics==0){
		echo FATAL_ERROR_SHOW_128("{UserAgentsStatistics_disabled_error}");
		die();
		
	}
	
	$UserAgents=$tpl->_ENGINE_parse_body("{UserAgents}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$group=$tpl->_ENGINE_parse_body("{group}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$hits=$tpl->javascript_parse_text("{hits}");
	$last_4hours=$tpl->javascript_parse_text("{last_4_hours}");
	$TB_WIDTH=607;
	$t=time();
	$UserAgentsF="UserAgent";
	$FilterField="UserAgent";
	if(isset($_GET["node-infos-IPADDRS"])){
		$UserAgents=$tpl->_ENGINE_parse_body("{ip_addresses}");
		$UserAgentsF="ipaddr";
		$listAdd="&ipaddr=yes";
		$FilterField="ipaddr";
	}
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?node-infos-UserAgents-list=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}$listAdd&field=$FilterField',
	dataType: 'json',
	colModel : [
		{display: '<strong style=font-size:22px>$UserAgents</strong>', name : 'USERAGENT', width : 832, sortable : true, align: 'left'},
		{display: '<strong style=font-size:22px>$hits</strong>', name : 'hits', width : 118, sortable : true, align: 'right'},
		{display: '<strong style=font-size:22px>$size</strong>', name : 'size', width : 118, sortable : true, align: 'right'},
		
	],

	sortname: 'size',
	sortorder: 'asc',
	usepager: true,
	title: '<strong style=font-size:26px>$last_4hours</strong>',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true
	
	});   
});
</script>	";
echo $tpl->_ENGINE_parse_body($html);
}
function node_infos_UserAgents_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_squid_builder();
	$ip=new IP();
	$field=$_GET["field"];
	$search='%';
	$table="USERAGENTS4H";
	$Select="UID";
	$page=1;
	$influx=new influx();
	if($ip->isIPAddress($_GET["ipaddr"])){
		$Select="ipaddr";
		
	}
	
	if($ip->IsvalidMAC($_GET["MAC"])){
		$Select="MAC";
		$value="{$_GET["MAC"]}";
	}
	
	$searchstring=string_to_flexquery();
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE $Select='$value' $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM $table WHERE $Select='$value' $searchstring $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){json_error_show($q->mysql_error);}
	if(mysql_num_rows($results)==0){json_error_show("no data $sql" );}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	
	while ($ligne = mysql_fetch_assoc($results)) {

		$USERAGENT=$ligne["USERAGENT"];
		$size=FormatBytes($ligne["size"]/1024);
		$md5=md5($USERAGENT);
		$hits=$ligne["hits"];
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:18px;'>$USERAGENT</span>",
			"<span style='font-size:18px;'>$hits</span>",
			"<span style='font-size:18px;'>$size</span>",
			)
		);
	}
	
	$data['total']=count($data['rows']);
	
	
echo json_encode($data);		

}

function node_infos_realtime(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	
	$xtime=$tpl->time_to_date(strtotime('-1 hour'),true);
	
	$title=$tpl->_ENGINE_parse_body("{today}: {from} $xtime | {$_GET["MAC"]} | {$_GET["ipaddr"]} | {realtime_requests}");
	$zoom=$tpl->_ENGINE_parse_body("{zoom}");
	$button1="{name: 'Zoom', bclass: 'Search', onpress : ZoomSquidAccessLogs},";
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$logs_container=$tpl->javascript_parse_text("{logs_container}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{add} {category}");
	$date=$tpl->_ENGINE_parse_body("{zDate}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$new_schedule=$tpl->_ENGINE_parse_body("{new_rotate}");
	$explain=$tpl->_ENGINE_parse_body("{explain_squid_tasks}");
	$run=$tpl->_ENGINE_parse_body("{run}");
	$task=$tpl->_ENGINE_parse_body("{task}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$filename=$tpl->_ENGINE_parse_body("{filename}");
	$empty=$tpl->_ENGINE_parse_body("{empty}");
	$askdelete=$tpl->javascript_parse_text("{empty_store} ?");
	$files=$tpl->_ENGINE_parse_body("{files}");
	$ext=$tpl->_ENGINE_parse_body("{extension}");
	$back_to_events=$tpl->_ENGINE_parse_body("{back_to_events}");
	$Compressedsize=$tpl->_ENGINE_parse_body("{compressed_size}");
	$realsize=$tpl->_ENGINE_parse_body("{realsize}");
	$delete_file=$tpl->javascript_parse_text("{delete_file}");
	$rotate_logs=$tpl->javascript_parse_text("{rotate_logs}");
	$table_size=855;
	$url_row=555;
	$member_row=276;
	$table_height=420;
	$distance_width=230;
	$tableprc="100%";
	$margin="-10";
	$margin_left="-15";
	if(is_numeric($_GET["table-size"])){$table_size=$_GET["table-size"];}
	if(is_numeric($_GET["url-row"])){$url_row=$_GET["url-row"];}
	$hits=$tpl->javascript_parse_text("{hits}");

	

	$ipaddr=$tpl->javascript_parse_text("{ipaddr}");
	$error=$tpl->javascript_parse_text("{error}");
	$sitename=$tpl->javascript_parse_text("{sitename}");
	
	
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	<script>
	var mem$t='';
function StartLogsSquidTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?node-infos-RTIME-LIST=yes&MAC={$_GET["MAC"]}&ipaddr={$_GET["ipaddr"]}',
	dataType: 'json',
	colModel : [
	{display: '$zdate', name : 'zDate', width :95, sortable : false, align: 'left'},
	{display: '$sitename', name : 'events', width : 746, sortable : false, align: 'left'},
	{display: '$hits', name : 'hits', width : 110, sortable : false, align: 'right'},
	{display: '$size', name : 'size', width : 110, sortable : false, align: 'right'},
	],
		
	
	
	searchitems : [
	{display: '$sitename', name : 'SITE'},

	],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	
}
setTimeout('StartLogsSquidTable$t()',800);
</script>
";
echo $html;
	
	}
function node_infos_realtime_list(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$influx=new influx();
	$from=strtotime('-1 hour');
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	$ip=new IP();
	
	if($ip->isIPAddress($_GET["ipaddr"])){
		$Select="IPADDR";
		$FORCE_FILTER=" IPADDR='{$_GET["ipaddr"]}'";
	}
	
	if($ip->IsvalidMAC($_GET["MAC"])){
		$Select="MAC";
		$FORCE_FILTER=" MAC='{$_GET["MAC"]}'";
	}
	
	
	$sql="SELECT * FROM access_log where time >{$from}s and $FORCE_FILTER ORDER BY time DESC LIMIT $rp";
	

		$data = array();
		$data['page'] = $page;
		$data['total'] = 0;
		$data['rows'] = array();
		$today=date("Y-m-d");
		$tcp=new IP();
	
		$main=$influx->QUERY_SQL($sql);
		$c=0;
	foreach ($main as $row) {
			$color="black";
			$return_code_text=null;
			$ff=array();
			$color="black";
			$uri=$row->SITE;
			$xtimelog=null;
			
			
			$date=date("H:i:s",InfluxToTime($row->time));
			$mac=$row->MAC;
			$ip=$row->IPADDR;
			$user=$row->uid;
			$size=$row->SIZE;
			$rqs=$row->RQS;
			$ident=array();
			$md=md5(serialize($row));
			$c++;
		
			$spanON="<span style='color:$color;font-size:16px'>";
			$spanOFF="</span>";
			$cached_text=null;
			$size=FormatBytes($size/1024);
			
			$data['rows'][] = array(
					'id' => $md,
					'cell' => array(
							"$spanON$date$spanOFF",
							"$spanON$uri$spanOFF",
							"$spanON$rqs$spanOFF",
							"$spanON$size$spanOFF",
					)
			);
	
	
				
	
		}
		$data['total'] = $c;
		echo json_encode($data);
	}	