<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	include_once('ressources/class.computers.inc');
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
	
	if(isset($_GET["link-user-js"])){link_user_js();exit;}
	if(isset($_GET["link-user-popup"])){link_user_popup();exit;}
	if(isset($_POST["link-user-save"])){link_user_save();exit;}
	
	
js();
function link_user_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{link_to_an_user}");
	$html="YahooWin6('500','$page?link-user-popup=yes&MAC={$_GET["MAC"]}','$title');";
	echo $html;
}


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{computers}");
	$html="YahooWin4('562.6','$page?popup=yes','$title');";
	echo $html;
}

function node_infos_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$computer=new computers();
	$uid=$computer->ComputerIDFromMAC($_GET["MAC"]);
	$title=$tpl->_ENGINE_parse_body("{status}::{computer}:{$_GET["MAC"]}::$uid");
	
	$html="YahooWin5('650','$page?node-infos-tabs=yes&MAC={$_GET["MAC"]}','$title');";
	echo $html;	
	
}

function link_user_save(){
	$_POST["uid"]=mysql_escape_string($_POST["uid"]);
	$sql="UPDATE webfilters_nodes SET uid='{$_POST["uid"]}' WHERE MAC='{$_POST["MAC"]}'";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;}
}

function link_user_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$_GET["MAC"]}'"));
	$t=time();
	$html="
	<div id='div-$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{username}:</td>
		<td>". Field_text("$t-uid",$ligne["uid"],"font-size:16px;width:220px",null,null,null,false,"LinkUserStatsDBcHeck(event)")."</td>
		<td>". button("{browse}","Loadjs('MembersBrowse.php?field-user=$t-uid&NOComputers=1&OnlyUsers=1')",12)."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}","LinkUserStatsDB()",16)."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var x_LinkUserStatsDB=function(obj){
      var tempvalue=obj.responseText;
      if(tempvalue.length>3){alert(tempvalue);}
      YahooWin6Hide();
      if(document.getElementById('main_node_infos_tab')){RefreshTab('main_node_infos_tab');}
      RefreshNodesSquidTbl();
      
     }	

     function LinkUserStatsDBcHeck(e){
     	if(checkEnter(e)){LinkUserStatsDB();}
     
     }
	
	function LinkUserStatsDB(){
			var XHR = new XHRConnection();
			XHR.appendData('link-user-save','yes');
			XHR.appendData('uid',document.getElementById('$t-uid').value);
			XHR.appendData('MAC','{$_GET["MAC"]}');
			XHR.sendAndLoad('$page', 'POST',x_LinkUserStatsDB);
			 
			}	
	
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function node_infos_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$array["node-infos-status"]="{status}";
	$array["node-infos-UserAgents"]="{UserAgents}";
	$array["node-infos-IPADDRS"]="{ip_addresses}";
	$array["node-infos-GROUPS"]="{proxy_objects}";
	$array["node-infos-WEBACCESS"]="{webaccess}";
	$array["node-infos-RULES"]="{access_rules}";
	$textsize="13px";

	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
	if($num=="node-infos-WEBACCESS"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.access.php?MAC={$_GET["MAC"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
	if($num=="node-infos-GROUPS"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.groups.php?MAC={$_GET["MAC"]}\"><span>$ligne</span></a></li>\n");
			continue;
	}		

	if($num=="node-infos-RULES"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"squid.nodes.accessrules.php?MAC={$_GET["MAC"]}\"><span>$ligne</span></a></li>\n");
			continue;
		}			
		
		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:$textsize'><a href=\"$page?$num=yes&MAC={$_GET["MAC"]}\"><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_node_infos_tab style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_node_infos_tab').tabs();
			
			
			});
		</script>";		
	
		
}


function node_infos_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	
	$results=$q->QUERY_SQL("SELECT UserAgent,MAC FROM UserAutDB GROUP BY UserAgent,MAC HAVING MAC='{$_GET["MAC"]}' AND LENGTH(UserAgent)>0");
	$UsersAgents=mysql_num_rows($results);
	
	
	$results=$q->QUERY_SQL("SELECT ipaddr,MAC FROM UserAutDB GROUP BY ipaddr,MAC HAVING MAC='{$_GET["MAC"]}' AND LENGTH(ipaddr)>0");
	if(!$q->ok){echo $q->mysql_error;}
	
	$ipaddr=mysql_num_rows($results);
	
	$computer=new computers();
	$uid=$computer->ComputerIDFromMAC($_GET["MAC"]);
	$uidORG=$uid;
	if($uid==null){$uid="{no_entry}";}else{
		$jsfiche=MEMBER_JS($uid,1,1);
		$uid=str_replace("$", "", $uid);
		$uid="<a href=\"javascript:blur();\" 
		OnClick=\"javascript:$jsfiche\" 
		style='font-size:14px;font-weight:bolder;text-decoration:underline'>$uid</a>";
	}
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM webfilters_nodes WHERE MAC='{$_GET["MAC"]}'"));
	$member=$ligne["uid"];
	if($ligne["uid"]==null){
		$imagedegauche=imgtootltip("folder-useradd-64.png","{link_to_an_user}","Loadjs('$page?link-user-js=yes&MAC={$_GET["MAC"]}')");
		$textImage="{link_to_an_user}";
		$member="{none}";
	}
	
	
	$ArrayNMap=unserialize(base64_decode($ligne["nmapreport"]));
		if(is_array($ArrayNMap)){
			if($ArrayNMap["OS"]<>null){$NMAPS[]="
			<tr>
				<td class=legend style='font-size:14px'>{OS}:</td>
				<td style='font-size:14px;font-weight:bolder'>{$ArrayNMap["OS"]}</td>
			</tr>
			";}
			
			if($ArrayNMap["UPTIME"]<>null){$NMAPS[]="
			<tr>
				<td class=legend style='font-size:14px'>{uptime}:</td>
				<td style='font-size:12px;font-weight:normal'>{$ArrayNMap["UPTIME"]}</td>
			</tr>
			";}			
			if(count($ArrayNMap["PORTS"])>0){$NMAPS[]="
			<tr>
				<td class=legend style='font-size:14px'>{opened_ports}:</td>
				<td style='font-size:14px;font-weight:bolder'>".count($ArrayNMap["PORTS"])."</td>
			</tr>
			";}				
			
			if(count($NMAPS)>0){$NMAPS_TXT=@implode("", $NMAPS);}
			
			
		}	
	
	
	$uidORG=str_replace("$", "", $uidORG);
	$jsnode="<a href=\"javascript:blur();\" 
		style='font-size:14px;font-weight:bolder;text-decoration:underline'
		OnClick=\"javascript:Loadjs('$page?link-user-js=yes&MAC={$_GET["MAC"]}')\">";
	$html="
	<table style='width:100%;margin:-8px'>
	<tr>
		<td valign='top' width=1%>
			<center class=form style='width:90%'>
				<img src='img/computer-tour-64.png'><p>&nbsp;</p>$imagedegauche<strong>$textImage</strong></center></td>
		<td valign='top' width=99% style='padding-left:15px'>

			<table style='width:99%' class=form>
				<tbody>
					<tr>
						<td class=legend style='font-size:16px' colspan=2 align=right>{$_GET["MAC"]}&nbsp;|&nbsp;$uidORG</td>
					</tr>	
					$NMAPS_TXT			
					<tr>
						<td class=legend style='font-size:14px'>{UserAgents}:</td>
						<td style='font-size:14px;font-weight:bolder'>$UsersAgents</td>
					</tr>
					<tr>
						<td class=legend style='font-size:14px'>{ip_addresses}:</td>
						<td style='font-size:14px;font-weight:bolder'>$ipaddr</td>
					</tr>	
					<tr>
						<td class=legend style='font-size:14px'>{in_database}:</td>
						<td style='font-size:14px;font-weight:bolder'>$uid</a></td>
					</tr>	
					<tr>
						<td class=legend style='font-size:14px'>{member}:</td>
						<td style='font-size:14px;font-weight:bolder'>$jsnode$member</a></td>
					</tr>														
				</tbody>
			</table>
		</td>
	</tr>
	</table>
	
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
	$TB_WIDTH=550;
	$t=time();
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?list=yes',
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
	width: $TB_WIDTH,
	height: 450,
	singleSelect: true
	
	});
});

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
		$results2=$q->QUERY_SQL("SELECT hostname FROM (SELECT hostname FROM  UserAutDB WHERE MAC='{$ligne["MAC"]}') as t GROUP BY hostname");
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
			if(trim($ligne2["hostname"])==null){continue;}
			if(preg_match("#[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+#", $ligne2["hostname"])){$tt[]=$ligne2["hostname"];continue;}
			if(strpos($ligne2["hostname"], ".")>0){$ss=explode(".", $ligne2["hostname"]);$hostname=$ss[0];}else{$hostname=$ligne2["hostname"];}
			$tt[]=$hostname;
		}
		
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\" 
			style='font-size:16px;text-decoration:underline'>{$ligne["MAC"]}</span>",
			"<span style='font-size:11px'>". @implode(", " , $tt)."</span>$textToAdd",
			"<span style='font-size:16px'>{$ligne["uid"]}</span>",
			$img_active)
		);
	}
	
	
echo json_encode($data);		

}



function node_infos_UserAgents(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();	
	$UserAgents=$tpl->_ENGINE_parse_body("{UserAgents}");
	$description=$tpl->_ENGINE_parse_body("{description}");
	$member=$tpl->_ENGINE_parse_body("{member}");	
	$delete=$tpl->_ENGINE_parse_body("{delete}");	
	$group=$tpl->_ENGINE_parse_body("{group}");
	$add=$tpl->_ENGINE_parse_body("{add}:{extension}");
	$addDef=$tpl->_ENGINE_parse_body("{add}:{default}");
	$new_category=$tpl->_ENGINE_parse_body("{new_category}");
	$TB_WIDTH=607;
	$t=time();
	$UserAgentsF="UserAgent";
	if(isset($_GET["node-infos-IPADDRS"])){
		$UserAgents=$tpl->_ENGINE_parse_body("{ip_addresses}");
		$UserAgentsF="ipaddr";
		$listAdd="&ipaddr=yes";
	}
	
	$html="
	<table class='$t' style='display: none' id='$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#$t').flexigrid({
	url: '$page?node-infos-UserAgents-list=yes&MAC={$_GET["MAC"]}$listAdd',
	dataType: 'json',
	colModel : [
		{display: '$UserAgents', name : '$UserAgentsF', width : 573, sortable : true, align: 'left'},
	],
	searchitems : [
		{display: '$UserAgents', name : '$UserAgentsF'},
		],
	sortname: '	UserAgent',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: $TB_WIDTH,
	height: 250,
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

	$UserAgent="UserAgent";
	$search='%';
	$table="UserAutDB";
	$page=1;
	$FORCE_FILTER="AND MAC='{$_GET["MAC"]}' AND LENGTH(UserAgent)>0";
	if(isset($_GET["ipaddr"])){
		$FORCE_FILTER="AND MAC='{$_GET["MAC"]}' AND LENGTH(ipaddr)>0";
		$UserAgent="ipaddr";
	}
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
		$sql="SELECT COUNT(*) as TCOUNT,MAC,$UserAgent FROM `$table` GROUP BY MAC,$UserAgent HAVING 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT,MAC,$UserAgent FROM `$table` GROUP BY MAC,$UserAgent HAVING 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT MAC,$UserAgent FROM `$table` GROUP BY MAC,$UserAgent HAVING 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
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

	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne[$UserAgent];
		$md5=md5($ligne[$UserAgent]);
		
	$data['rows'][] = array(
		'id' => $md5,
		'cell' => array(
			"<span style='font-size:14px;'>{$ligne[$UserAgent]}</span>",
			)
		);
	}
	
	
echo json_encode($data);		

}