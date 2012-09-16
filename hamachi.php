<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
$users=new usersMenus();
$tpl=new templates();
if(!$users->AsSystemAdministrator){
		echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");
		die();
	}
	if(isset($_GET["help"])){help();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["LOGIN"])){SAVE();exit;}
	if(isset($_GET["hamachilist"])){GLIST();exit;}
	if(isset($_GET["status"])){STATUS();exit;}
	if(isset($_GET["sessions"])){SESSIONS();exit;}
	if(isset($_GET["DELETE"])){DELETE();exit;}
	if(isset($_GET["DELETE-NET"])){DELETE_NET();exit;}
	if(isset($_GET["EnableHamachi"])){EnableHamachi();exit;}
	if(isset($_GET["edit-net-js"])){edit_net_js();exit;}
	if(isset($_GET["edit-net-popup"])){edit_net_popup();exit;}
	if(isset($_POST["ID"])){edit_net_save();exit;}
	if(isset($_GET["HamchStat"])){NET_STATUS();exit;}
	if(isset($_GET["gateway"])){gateway_config();exit;}
	if(isset($_POST["EnableArticaAsGateway"])){gateway_save();exit;}
	if(isset($_GET["peer-infos"])){peer_infos();exit;}
	if(isset($_GET["net-infos"])){net_infos();exit;}
	
	
	js();
	
function edit_net_js(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();
	$id=$_GET["edit-net-js"];
	$sql="SELECT pattern FROM hamachi WHERE ID='$id'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$array=unserialize(base64_decode($ligne["pattern"]));
	$title=$tpl->_ENGINE_parse_body("{networkid}: {$array["NETWORK"]}");
	$html="YahooWin5('480','$page?edit-net-popup=yes&ID=$id','$title')";
	echo $html;
	
}	


function help(){
	
	$tpl=new templates();
	$html="<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td valign='top' width=1%><a href='http://www.artica.fr/download/artica-hamachi.pdf'><img src='img/pdf-128.png' style='border:0px'></a></td>
		<td valign='top'><div class=explain style='font-size:16px'>{HELP_PDF_DOWNLOAD_EXPLAIN}</div>
	</tr>
	</tbody>
	</table>
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function edit_net_popup(){
	$q=new mysql();
	$page=CurrentPageName();
	$tpl=new templates();	
	$sql="SELECT pattern FROM hamachi WHERE ID='{$_GET["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$array=unserialize(base64_decode($ligne["pattern"]));
	
$array_TYPE["CREATE_NET"]="{create_network}";
$array_TYPE["JOIN_NET"]="{join_network}";	
	
	$time=time();	
	$html="	
	
	<div id='hamachiid$time'>
	<table style='width:99%' class=form>
	
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{type}:</td>
		<td valign='top' style='font-size:16px'>{$array_TYPE[$array["TYPE"]]}</td>
	</tr>
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{login_name}:</td>
		<td valign='top'>". Field_text("LOGIN$time",$array["LOGIN"],"font-size:14px;padding:4px")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{network_password}:</td>
		<td valign='top'>". Field_password("PASSWORD$time",$array["PASSWORD"],"font-size:14px;padding:4px")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend style='font-size:16px'>{network_name}:</td>
		<td valign='top' style='font-size:16px'>{$array["NETWORK"]}</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","HAMACHI_EDIT()")."</td>
	</tr>	
	</table>
	</div>
	<script>
var X_HAMACHI_EDIT= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	YahooWin5Hide();
	RefreshTab('hamashi_config_postfix');
	}	
		
	function HAMACHI_EDIT(){
		var XHR = new XHRConnection();
		XHR.appendData('ID',{$_GET["ID"]});
		XHR.appendData('LOGIN',document.getElementById('LOGIN$time').value);
		XHR.appendData('PASSWORD',document.getElementById('PASSWORD$time').value);
		AnimateDiv('hamachiid$time');
		XHR.sendAndLoad('$page', 'POST',X_HAMACHI_EDIT);				
	}	
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function edit_net_save(){
	$q=new mysql();
	$sql="SELECT pattern FROM hamachi WHERE ID='{$_POST["ID"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$array=unserialize(base64_decode($ligne["pattern"]));	
	$array["LOGIN"]=$_POST["LOGIN"];
	$array["PASSWORD"]=$_POST["PASSWORD"];
	
	$datas=base64_encode(serialize($array));
	$sql="UPDATE hamachi SET pattern='$datas' WHERE ID='{$_POST["ID"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?hamachi-net=yes");	
	
}


	
function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{APP_AMACHI}");
	$prefix="amachi";
	$ERROR_SPECIFY_AN_ACTION_FIRST=$tpl->javascript_parse_text("{ERROR_SPECIFY_AN_ACTION_FIRST}");
	$startjs="HAMACHI_START()";
	if(isset($_GET["in-line"])){
		$startjs="HAMACHI_START_INLINE()";
	}
	$html="
	var {$prefix}tant=0;


function {$prefix}demarre(){
	{$prefix}tant = {$prefix}tant+1;
	{$prefix}reste=20-{$prefix}tant;
	if(!YahooWin3Open()){return false;}
	
	if ({$prefix}tant < 10 ) {                           
		setTimeout(\"{$prefix}demarre()\",2000);
      } else {
		{$prefix}tant = 0;
		LoadAjax('hamachi-status','$page?status=yes');
		{$prefix}demarre(); 
			                              
	   }
	}	
	
		function HAMACHI_START(){
			YahooWin3('700','$page?popup=yes','$title');
		}
		
		function HAMACHI_START_INLINE(){
			$('#mainlevel').load('$page?popup=yes');
		}
var X_FREENETKILL= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('hamashi_config_postfix');
	}	
	
var X_HAMACHI_SAVE= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	$startjs
	}	
		
	function HAMACHI_SAVE(){
		var XHR = new XHRConnection();
		var XTYPE=document.getElementById('TYPE').value;
		if(XTYPE.length==0){alert('$ERROR_SPECIFY_AN_ACTION_FIRST');return;}
		
		
		XHR.appendData('LOGIN',document.getElementById('LOGIN').value);
		XHR.appendData('TYPE',document.getElementById('TYPE').value);
		XHR.appendData('NETWORK',document.getElementById('NETWORK').value);
		XHR.appendData('PASSWORD',document.getElementById('PASSWORD').value);
		AnimateDiv('hamachiid');
		XHR.sendAndLoad('$page', 'GET',X_HAMACHI_SAVE);				
	}	
	
var X_HAMACHI_ENABLE= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	CheckHamachiForm();
	}	
	
	
	function HAMACHI_ENABLE(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableHamachi').checked){
			XHR.appendData('EnableHamachi',1);}else{XHR.appendData('EnableHamachi',0);}
			XHR.sendAndLoad('$page', 'GET',X_HAMACHI_ENABLE);
		}

	function HAMACHI_DELETE(ID,net){
		var XHR = new XHRConnection();
		XHR.appendData('DELETE',ID);
		XHR.appendData('NETWORK',net);
		AnimateDiv('hamachiid');
		XHR.sendAndLoad('$page', 'GET',X_HAMACHI_SAVE);
	}
	
	function FREENETKILL(net){
		var XHR = new XHRConnection();
		XHR.appendData('DELETE-NET',net);
		XHR.sendAndLoad('$page', 'GET',X_FREENETKILL);	
	}
	
	$startjs;
	{$prefix}demarre();";
	
	echo $html;
	
}


function popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$array["CREATE_NET"]="{create_network}";
	$array["JOIN_NET"]="{join_network}";
	$array[null]="{select}";
	
	$EnableHamachi=$sock->GET_INFO("EnableHamachi");
	if(!is_numeric($EnableHamachi)){$EnableHamachi=1;}
	
	//96-smtp-auth.png
	
	$field=Field_array_Hash($array,"TYPE",$ini->_params["SETUP"]["TYPE"],"CheckHamachiForm()",null,0,"font-size:14px;padding:4px");

	
	
	$html="
	<center class=form>
	<div id='hamachiid'>
	<table style='width:100%'>
	<tr>
	<td valign='top'>
		 <div style='text-align:left;padding-bottom:4px'><img src='img/logmein_logo.gif'></div>
		<div id='hamachi-status'></div>
	</td>
	<td valign='top'>
	<table style='width:99%' class=form>
	<tr>
		<td valign='top' class=legend>{ENABLE_APP_AMACHI}:</td>
		<td valign='top'>". Field_checkbox("EnableHamachi",1,$EnableHamachi,"HAMACHI_ENABLE()")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend>{type}:</td>
		<td valign='top'>$field</td>
	</tr>
	<tr>
		<td valign='top' class=legend>{login_name}:</td>
		<td valign='top'>". Field_text("LOGIN",null,"font-size:14px;padding:4px")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend>{network_password}:</td>
		<td valign='top'>". Field_password("PASSWORD",null,"font-size:14px;padding:4px")."</td>
	</tr>	
	<tr>
		<td valign='top' class=legend>{network_name}:</td>
		<td valign='top'>". Field_text("NETWORK",null,"font-size:14px;padding:4px")."</td>
	</tr>
	
	
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{add}","HAMACHI_SAVE()")."</td>
	</tr>	
	</table>
	</td>
	</tr>
	</table>
	
	
	</div>	
	". TABS()."
	</center>
	<script>
		
		
		
		function CheckHamachiForm(){
			document.getElementById('TYPE').disabled=true;
			document.getElementById('LOGIN').disabled=true;
			document.getElementById('PASSWORD').disabled=true;
			document.getElementById('NETWORK').disabled=true;
			if(document.getElementById('EnableHamachi').checked){
				var NET=document.getElementById('NETWORK').value;
				var TYPE=document.getElementById('TYPE').value;
				document.getElementById('TYPE').disabled=false;
				if(TYPE=='CREATE_NET'){
					document.getElementById('NETWORK').disabled=false;
					document.getElementById('LOGIN').disabled=false;
					document.getElementById('PASSWORD').disabled=false;
				}
				
				if(TYPE=='JOIN_NET'){
					document.getElementById('NETWORK').disabled=false;
					document.getElementById('PASSWORD').disabled=false;	
				}						
			}
		
		}
		
		CheckHamachiForm();
		LoadAjax('hamachi-status','$page?status=yes');
	</script>

	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
	
}
function TABS(){
	
	$page=CurrentPageName();
	$array["hamachilist"]='{networks}';
	$array["sessions"]='{sessions}';
	$array["gateway"]='{gateway}';
	$array["events"]='{events}';
	$array["help"]='{help}';
	$tpl=new templates();
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"hamachi-events.php\"><span style='font-size:16px'>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&hostname=$hostname\"><span style='font-size:16px'>$ligne</span></a></li>\n");
	}
	
	
	return "
	<div id=hamashi_config_postfix style='width:99%;height:600px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#hamashi_config_postfix').tabs();
			
			
			});
		</script>";		
}



function GLIST(){
	$sock=new sockets();

	$page=CurrentPageName();
	$sql="SELECT * FROM hamachi ORDER BY ID DESC";
	$time=time();
	$html="
		<span id='$time'></span>
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
			<thead class='thead'>
				<tr>
					<th width=1%>$add</th>
					<th>{type}</th>
					<th>{username}</th>
					<th>{networkid}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
		<tbody class='tbody'>";
	
$array_TYPE["CREATE_NET"]="{create_network}";
$array_TYPE["JOIN_NET"]="{join_network}";	
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$img=imgtootltip("32-entire-network.png","{edit}","Loadjs('$page?edit-net-js={$ligne["ID"]}')");
		$array=unserialize(base64_decode($ligne["pattern"]));	
		$html=$html."
		<tr class=$classtr>
		<td width=1%>$img</td>
		<td><strong style='font-size:14px'>{$array_TYPE[$array["TYPE"]]}</strong></td>
		<td><strong style='font-size:14px'>{$array["LOGIN"]}</strong></td>
		<td><strong style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"NetworkHamachiInfos('{$array["NETWORK"]}');\" 
		style='font-size:14px;text-decoration:underline;font-weight:bold'>{$array["NETWORK"]}</a></td>
		<td width=1%>". imgtootltip("delete-32.png","{delete}","HAMACHI_DELETE({$ligne["ID"]},'{$array["NETWORK"]}')")."</td>
		</tr>
			";
		}
		
	$html=$html."</table></center>
	
	<script>
		function RefreshHamchStat(){
			LoadAjax('$time','$page?HamchStat=yes');
			}
			
		function NetworkHamachiInfos(netid){
			YahooWin2('550','$page?net-infos=yes&netid='+netid,netid+'::')
		
		}					
		RefreshHamchStat();
		LoadAjax('hamachi-status','$page?status=yes');
	</script>	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function SAVE(){
	//EnableHamachi
	
	if($_GET["TYPE"]=="JOIN_NET"){
		if(!preg_match("#^[0-9]+-[0-9]+#", $_GET["NETWORK"])){
			$tpl=new templates();
			echo $tpl->javascript_parse_text("{value}:`{$_GET["NETWORK"]}`\n{ERROR_HAMACHI_NET_PATTERN}");
			return;
		}
	}
	
	$datas=base64_encode(serialize($_GET));
	$sql="INSERT INTO hamachi (pattern) VALUES('$datas')";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?hamachi-net=yes");
	}
function DELETE(){
	$_GET["NETWORK"]=base64_encode($_GET["NETWORK"]);
	$sql="DELETE FROM hamachi WHERE ID={$_GET["DELETE"]}";
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?hamachi-delete-net='{$_GET["NETWORK"]}");	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");	
}

function STATUS(){
	$sock=new sockets();
	$params=unserialize(base64_decode($sock->getFrameWork("cmd.php?hamachi-status=yes")));
	
	$ini=new Bs_IniHandler();
	$ini->_params=$params;
	$tpl=new templates();
	$proxy=texttooltip("{http_proxy}","{http_proxy_text}","Loadjs('artica.settings.php?js=yes&func-ProxyInterface=yes')",null,0,"font-size:14px;text-decoration:underline;margin-top:-12px");
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("APP_AMACHI",$ini).$proxy);
	
}

function NET_STATUS(){
	$sock=new sockets();
	$page=CurrentPageName();
	$ip=$sock->getFrameWork("cmd.php?hamachi-ip");
	$Hid=trim($sock->getFrameWork("hamachi.php?hamachi-id"));
	$etat=trim($sock->getFrameWork("hamachi.php?hamachi-status"));
	$img="ok24-grey.png";
	if($etat=="logged in"){$img="ok24.png";}

	$html="
	<center>
		<table style='width:99%' class=form>
			<tbody>
				<tr>
					<td width=1%><img src='img/$img'></td>
					<td width=33% style='font-size:14px' nowrap>{tcp_address}:&nbsp;$ip</td>
					<td width=33% style='font-size:14px' nowrap>{networkid}:&nbsp;$Hid</td>
					<td width=33% style='font-size:14px' nowrap>{status}:&nbsp;$etat</td>
					<td width=1%>".imgtootltip("refresh-24.png","{refresh}","RefreshHamchStat()")."</td>
				</tr>
			</tbody>
		</table>
	</center>
	
	<script>
	LoadAjax('hamachi-status','$page?status=yes');
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}



function SESSIONS(){
	$sock=new sockets();
	$HamachiExtDomain=trim($sock->GET_INFO("HamachiExtDomain"));
	$params=unserialize(base64_decode($sock->getFrameWork("hamachi.php?hamachi-sessions=yes")));
	if(!is_array($params)){return null;}
	$page=CurrentPageName();
	$time=time();
	
	$html="<span id='$time'></span>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
		<th>{networkid}</th>
		<th>{hostname}</th>
		<th>{ip_address} ({public})</th>
		<th>{proto}</th>
	</tr>
</thead>
<tbody class='tbody'>";	

	//$session[$net][]=array("NETID"=>$re[1],"HOST"=>$re[2],"IPPUB"=>$re[3],"TYPE"=>$re[4],"PROTO"=>$re[5],"LOCALIP"=>$re[6]);
	//<td width=1%>". imgtootltip("ed_delete.gif","{delete} $server","FREENETKILL('$server')")."</td>
	while (list ($network,$arrayNET) = each ($params) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		while (list ($index,$array) = each ($arrayNET) ){
			if($HamachiExtDomain<>null){
				$hostss=explode(".", $array["HOST"]);
				$array["HOST"]=strtolower($hostss[0].".$HamachiExtDomain");
				}
			
			$hrefhost="<a href=\"javascript:blur();\" OnClick=\"PeerHamachiInfos('{$array["NETID"]}','{$array["HOST"]}');\" 
		style='font-size:14px;text-decoration:underline;font-weight:bold'>";
		$html=$html."
		<tr class=$classtr>
		<td style='font-size:14px;font-weight:bold'>
			<a href=\"javascript:blur();\" OnClick=\"NetworkHamachiInfos('$network');\" 
		style='font-size:14px;text-decoration:underline;font-weight:bold'>$network</a>&nbsp;|&nbsp;$hrefhost{$array["NETID"]}</a></td>
		<td><strong style='font-size:14px'>$hrefhost{$array["HOST"]}</a><div style='font-size:12px'>{$array["LOCALIP"]}</div></strong></td>
		<td><strong style='font-size:14px'width=1% nowrap>{$array["IPPUB"]}</strong></td>
		<td><strong style='font-size:14px' width=1%>{$array["PROTO"]}</strong></td>
		
		
		</tr>";
		}
		
		
	}
	
	$html=$html."</table></center>
	<script>
		function RefreshHamchStat(){
			LoadAjax('$time','$page?HamchStat=yes');
			}
			
		function PeerHamachiInfos(netid,name){
			YahooWin2('620','$page?peer-infos=yes&netid='+netid,netid+'::'+name)
		
		}
		
		function NetworkHamachiInfos(netid){
			YahooWin2('550','$page?net-infos=yes&netid='+netid,netid+'::')
		
		}		
		
		RefreshHamchStat();
		LoadAjax('hamachi-status','$page?status=yes');
	</script>
	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function EnableHamachi(){
	$sock=new sockets();
	$sock->SET_INFO("EnableHamachi",$_GET["EnableHamachi"]);
	$sock->getFrameWork("cmd.php?hamachi-restart=yes");
}

function DELETE_NET(){
	$sock=new sockets();
	$_GET["DELETE-NET"]=base64_encode($_GET["DELETE-NET"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?hamachi-delete-net='{$_GET["DELETE-NET"]}");	
	
}
function gateway_config(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
	$EnableArticaAsGateway=$sock->GET_INFO("EnableArticaAsGateway");	
	$HamachiFwInterface=$sock->GET_INFO("HamachiFwInterface");	
	$HamachiExtDomain=$sock->GET_INFO("HamachiExtDomain");	
	if(!is_numeric($EnableArticaAsGateway)){$EnableArticaAsGateway=0;}
	$DisableEtcHosts=$sock->GET_INFO("DisableEtcHosts");
	if(!is_numeric($DisableEtcHosts)){$DisableEtcHosts=0;}
	$tcp=new networking();
	$interfaces=$tcp->Local_interfaces(true);
	$interfaces[null]="{none}";
	$t=time();
	$HAMACHI_DOMAIN_DNS2=$tpl->_ENGINE_parse_body("{HAMACHI_DOMAIN_DNS2}");
	if(strpos($HAMACHI_DOMAIN_DNS2, "}")>0){$HAMACHI_DOMAIN_DNS2="Hosts database domain";}
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{ARTICA_AS_GATEWAY}:</td>
		<td>". Field_checkbox("EnableArticaAsGateway", 1,$EnableArticaAsGateway,"SaveHamachiNetCheckForm()")."</td>
		<td width=1%>". help_icon("{ARTICA_AS_GATEWAY_HAMACHI}")."<td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{FORWARD_LOCALNET_TONIC}:</td>
		<td>". Field_array_Hash($interfaces,"HamachiFwInterface", $HamachiFwInterface,"style:font-size:14px")."</td>
		<td width=1%>". help_icon("{FORWARD_LOCALNET_TONIC_EXPLAIN}")."<td>
	</tr>
	
	<tr>
		<td class=legend style='font-size:14px'>$HAMACHI_DOMAIN_DNS2:</td>
		<td>". Field_text("HamachiExtDomain", $HamachiExtDomain,"font-size:14px")."</td>
		<td width=1%>". help_icon("{HAMACHI_DOMAIN_DNS}")."<td>
	</tr>	
	<tr>
		<td colspan=3 align=right><hr>". button("{apply}","SaveHamachiNetConf()")."</td>
	</tr>
	</tbody>
	</table>
	</div>
	<script>
	
	function SaveHamachiNetCheckForm(){
		var DisableEtcHosts=$DisableEtcHosts;
		document.getElementById('HamachiFwInterface').disabled=true;
		document.getElementById('HamachiExtDomain').disabled=true;
		document.getElementById('HamachiFwInterface').disabled=true;
		if(document.getElementById('EnableArticaAsGateway').checked){
			document.getElementById('EnableArticaAsGateway').disabled=true;
			document.getElementById('HamachiFwInterface').disabled=false;
			document.getElementById('HamachiExtDomain').disabled=false;
			document.getElementById('HamachiFwInterface').disabled=false;		
		}
		
		if(DisableEtcHosts==1){
			document.getElementById('HamachiExtDomain').disabled=true;
			document.getElementById('HamachiExtDomain').value='';
		}
	}
	
var X_SaveHamachiNetConf= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('hamashi_config_postfix');
	}	
		
	function SaveHamachiNetConf(){
		var XHR = new XHRConnection();
		if(document.getElementById('EnableArticaAsGateway').checked){XHR.appendData('EnableArticaAsGateway',1);}else{XHR.appendData('EnableArticaAsGateway',0);}
		XHR.appendData('HamachiExtDomain',document.getElementById('HamachiExtDomain').value);
		XHR.appendData('HamachiFwInterface',document.getElementById('HamachiFwInterface').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_SaveHamachiNetConf);				
	}		
	SaveHamachiNetCheckForm();
	LoadAjax('hamachi-status','$page?status=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function gateway_save(){
	$sock=new sockets();
	if($_POST["EnableArticaAsGateway"]==1){$sock->SET_INFO("EnableArticaAsGateway", 1);}
	$sock->SET_INFO("HamachiFwInterface", $_POST["HamachiFwInterface"]);
	$sock->SET_INFO("HamachiExtDomain", $_POST["HamachiExtDomain"]);
	$sock->getFrameWork("hamachi.php?hamachi-gateway=yes");
	$sock->getFrameWork("hamachi.php?hamachi-init=yes");
	$sock->getFrameWork("hamachi.php?hamachi-restart=yes");
}

function net_infos(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$datas=unserialize(base64_decode($sock->getFrameWork("hamachi.php?net-infos={$_GET["netid"]}")));
	$html="
<table style='width:99%' class=form>
<tbody>
<tr>
	<td valign='top' width=1%><img src='img/64-entire-network.png'>
	<td valign='top'>
	<div class=form style='width:95%'>	
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
</thead>
<tbody class='tbody'>";	

	//$session[$net][]=array("NETID"=>$re[1],"HOST"=>$re[2],"IPPUB"=>$re[3],"TYPE"=>$re[4],"PROTO"=>$re[5],"LOCALIP"=>$re[6]);
	//<td width=1%>". imgtootltip("ed_delete.gif","{delete} $server","FREENETKILL('$server')")."</td>
	while (list ($key,$val) = each ($datas) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$html=$html."
		<tr class=$classtr>	
			<td class=legend style='font-size:14px' width=1% nowrap>$key:</td>
			<td><strong style='font-size:14px'width=99% nowrap>$val</strong></td>
		</tr>";
		}	
		
	$html=$html."</tbody></table></div>
	</td>
	</tr>
	</tbody>
	</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function peer_infos(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$datas=unserialize(base64_decode($sock->getFrameWork("hamachi.php?peer-infos={$_GET["netid"]}")));
	$html="
<table style='width:99%' class=form>
<tbody>
<tr>
	<td valign='top' width=1%><img src='img/computer-tour-64.png'>
	<td valign='top'>
	<div class=form style='width:95%'>	
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
</thead>
<tbody class='tbody'>";	

	//$session[$net][]=array("NETID"=>$re[1],"HOST"=>$re[2],"IPPUB"=>$re[3],"TYPE"=>$re[4],"PROTO"=>$re[5],"LOCALIP"=>$re[6]);
	//<td width=1%>". imgtootltip("ed_delete.gif","{delete} $server","FREENETKILL('$server')")."</td>
	while (list ($key,$val) = each ($datas) ){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		
		$html=$html."
		<tr class=$classtr>	
			<td class=legend style='font-size:14px' width=1% nowrap>$key:</td>
			<td><strong style='font-size:14px'width=99% nowrap>$val</strong></td>
		</tr>";
		}	
		
	$html=$html."</tbody></table></div>
	</td>
	</tr>
	</tbody>
	</table>";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}





?>