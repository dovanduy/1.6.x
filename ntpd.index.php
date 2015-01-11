<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.ntpd.inc');
	$user=new usersMenus();
	if($user->AsArticaAdministrator==false){header('location:users.index.php');exit();}
	if(isset($_GET["main"])){main_switch();exit;}
	if(isset($_GET["status"])){echo main_status();exit;}
	if(isset($_GET["ntpdAdd"])){ntpdAdd();exit;}
	if(isset($_GET["ntpdservermove"])){ntpdservermove();exit;}
	if(isset($_GET["ntpdserverdelete"])){ntpdserverdelete();exit;}
	if(isset($_GET["NTPDEnabled"])){NTPDEnabled();exit;}
	if(isset($_GET["op"])){main_switch_op();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["enable-ntpd-switch"])){ntpd_switch();exit;}
	if(isset($_GET["list"])){echo main_server_list();exit;}
	if(isset($_POST["country"])){ntpdAddCountry();exit;}
	if(isset($_GET["ntpd-server"])){ntpd_server_mode();exit;}
	if(isset($_GET["bytabs"])){bytabs();exit;}
	if(isset($_GET["client-conf"])){client_conf();exit;}
	if(isset($_POST["NTPDClientPool"])){client_conf_save();exit;}
	js();
	
	
function bytabs(){
	
	echo "
	<div id='ntp_index_php'></div>		
	<script>";
	js();
	echo "</script>";
}	
	
	
function js(){
	$bytabs=false;
	$page=CurrentPageName();
	$prefix="ntpdpage";
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{APP_NTPD}');
	if(isset($_GET["bytabs"])){$bytabs=true;}
	$give_server_name=$tpl->_ENGINE_parse_body('{give_server_name}');
	
	$start="YahooSetupControl(1077,'$page?popup=yes','$title');";
	if($bytabs){
		$start="LoadAjax('ntp_index_php','$page?popup=yes&bytabs=yes')";
	}
	
	
$html= "

		function {$prefix}load(){
			$start
			{$prefix}timeout=0;
			
		}

		
		function {$prefix}FILL(){
			{$prefix}timeout={$prefix}timeout+1;
			if({$prefix}timeout>10){alert('timeout');return;}
			if(!document.getElementById('ntpd_main_config')){
				setTimeout(\"{$prefix}FILL()\",900);
				return;
			}
			
			LoadAjax('ntpd_main_config','$page?main=yes');
			if(YahooWinSOpen()){YahooWinSHide();}
			
			ChargeLogs();
			{$prefix}demarre();
	}
	
	
var refresh_server_list= function (obj) {
			LoadAjax('serverlist','$page?list=yes');
			}		
		
		function ntpdAdd(){
		    var server=prompt('$give_server_name');
		    if(server){
	         var XHR = new XHRConnection();
		      XHR.appendData('ntpdAdd',server);
		      XHR.sendAndLoad('ntpd.index.php', 'GET',refresh_server_list);      
		    }
		}

		function ntpdservermove(num,dir){
		      var XHR = new XHRConnection();
		      XHR.appendData('ntpdservermove',num);
		      XHR.appendData('direction',dir);
		      XHR.sendAndLoad('ntpd.index.php', 'GET',refresh_server_list);    
		    }
		    
		function ntpdserverdelete(num){
		      var XHR = new XHRConnection();
		      XHR.appendData('ntpdserverdelete',num);
		      XHR.sendAndLoad('ntpd.index.php', 'GET',refresh_server_list);      
		    }
		
		function ntpdSave(){
		 YahooWin(440,'ntpd.index.php?op=-1');
		        for(var i=0;i<5;i++){
		                setTimeout('ntpdSave_run('+i+')',1500);
		        }
		}
		function ntpdSave_run(number){
		        LoadAjax2('message_'+number,'ntpd.index.php?op='+number)
		        }

{$prefix}load();
";
		
		echo $html;

}

function ntpd_server_mode(){
	
	
	
}
	
	
function popup(){
	echo main_tabs();
}



function main_tabs(){
	$bytabs=false;
	$page=CurrentPageName();
	$tpl=new templates();
	
	$fontsize=20;	
	$array["index"]="{index}";
	$array["yes"]='{main_settings}';
	$array["client-conf"]='{client_mode}';
	$array["logs"]='{events}';	
	$array["ntpdconf"]='{ntpdconf}';
	
	if(isset($_GET["bytabs"])){$bytabs=true;$suffix="&bytabs=yes";$fontsize=18;}
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]=$tpl->_parse_body("<li><a href=\"$page?main=$num\"><span style='font-size:{$fontsize}px'>$ligne</span></a></li>\n");
		}
	
	return build_artica_tabs($html, "ntpd_main_config");
		
}

function index(){
	$status=main_status();	
	$page=CurrentPageName();
	

	$users=new usersMenus();
	if(!$users->NTPD_INSTALLED){
		$error=FATAL_ERROR_SHOW_128("{ntpd_server_not_installed_only_client_mode}");
	}
	
	

	$html="
	<div class=text-info style='font-size:16px'>{ntp_about}</div>
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:220px'>
		
		<div id='ntpd-status'></div>
		<div id='ntpd-server'></div>
		
	</td>
	<td valign='top' style='width:95%'>
		$error
		<div id='enable-ntpd' style='width:98%' class=form></div>
	</td>
	</tr>
	</table>
	
	
	<script>
		
		
	
		var X_SaveEnableNTPDSwitch= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			LoadAjax('enable-ntpd','$page?enable-ntpd-switch=yes');
			NTPD_STATUS();
			}		
		
		function SaveEnableNTPDSwitch(){
			var XHR = new XHRConnection();
      		XHR.appendData('NTPDEnabled',document.getElementById('NTPDEnabled').value);
      		document.getElementById('enable-ntpd').innerHTML='<center style=\"margin:20px;padding:20px\"><img src=\"img/wait_verybig.gif\"></center>';
      		XHR.sendAndLoad('$page', 'GET',X_SaveEnableNTPDSwitch);    
		
		}
		
		
		function NTPD_STATUS(){
			LoadAjax('ntpd-status','$page?status=yes');
			LoadAjax('ntpd-server','$page?ntpd-server=yes');
		
		}
		LoadAjax('enable-ntpd','$page?enable-ntpd-switch=yes');
		NTPD_STATUS();
	</script>
	";
	
	
	
	
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);		
}

function ntpd_switch(){
	$sock=new sockets();
	$NTPDEnabled=$sock->GET_INFO("NTPDEnabled");	
	$NTPDServerEnabled=$sock->GET_INFO("NTPDServerEnabled");

	if($NTPDEnabled==0){
		if($NTPDServerEnabled==1){$NTPDEnabled=1;}
	}
	
	
	$enable=Paragraphe_switch_img("{ENABLE_APP_NTPD}","{APP_NTPD_ENABLE_TEXT}","NTPDEnabled",$NTPDEnabled,null,750);
	$tpl=new templates();
	
	$enable=$enable."
	<div style='text-align:right'><hr>".button("{apply}","SaveEnableNTPDSwitch()",32)."</div>";
	
	echo $tpl->_ENGINE_parse_body($enable);	
}	


function client_conf(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$t=time();
	$NTPDClientEnabled=intval($sock->GET_INFO("NTPDClientEnabled"));
	$NTPDClientPool=intval($sock->GET_INFO("NTPDClientPool"));
	if($NTPDClientPool==0){$NTPDClientPool=120;}
	
	$enable=Paragraphe_switch_img("{ENABLE_APP_NTPDCLI}","{ENABLE_APP_NTPDCLI_TEXT}","NTPDClientEnabled",$NTPDClientEnabled,null,750);
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
		<tr><td colspan=2>$enable</td></tr>
		". Field_text_table("NTPDClientPool","{each} ({minutes})",$NTPDClientPool,22,null,140)
		.Field_button_table_autonome("{apply}", "Save$t",30).
		"</table>
	</div>
				
	 	<script>
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	RefreshTab('ntpd_main_config');
}	
	 	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('NTPDClientPool',document.getElementById('NTPDClientPool').value);
	XHR.appendData('NTPDClientEnabled',document.getElementById('NTPDClientEnabled').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);    
}
</script>				
";
echo $tpl->_ENGINE_parse_body($html);
	
}

function client_conf_save(){
	$sock=new sockets();
	$sock->SET_INFO("NTPDClientPool", $_POST["NTPDClientPool"]);
	$sock->SET_INFO("NTPDClientEnabled", $_POST["NTPDClientEnabled"]);
	$sock->getFrameWork("system.php?ntp-client=yes");
	
}


function main_switch(){
	
	switch ($_GET["main"]) {
		case "client-conf":client_conf();exit;break;
		case "index":index();exit;break;
		case "yes":ntpd_main_config();exit;break;
		case "logs":main_logs();exit;break;
		case "syncevents":main_sync();exit;break;
		case "conf":echo main_conf();exit;break;
		case "ntpdconf":echo main_ntpdconf();exit;break;
		case "server_list":echo main_server_list();exit;break;
		default:
			break;
	}
	
	
}	

function main_status(){
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$ini->loadString(base64_decode($sock->getFrameWork('services.php?ntpd-status=yes')));	
	$status=DAEMON_STATUS_ROUND("NTPD",$ini,null);
	$tpl=new templates();
	
	$help=Paragraphe("help-64.png","{help}","{online_help}","javascript:s_PopUpFull('http://nas-appliance.org/index.php?cID=143','1024','900');");
	
	$refresh="<div style='text-align:right'>".imgtootltip("refresh-24.png","{refresh}","NTPD_STATUS()")."</div>";
	
	return $tpl->_ENGINE_parse_body($help.$status.$refresh);		
	
	
}


function ntpd_main_config(){
$ntp=new ntpd(true);
$sock=new sockets();
$array=$ntp->ServersList();
$arrayTimzone=$ntp->timezonearray();
$timezone_def=trim($sock->GET_INFO('timezones'));
$t=time();
while (list ($num, $val) = each ($array) ){
	$i[$num]=$num;
}
$i[null]="{choose}";

$choose=Field_array_Hash($i,'ntpd_servers_choosen',null,null,null,0,'font-size:14px;padding:3px');

	 $page=CurrentPageName();
	 $form="
	 <table style='width:99%' class=form>
	 <tr>
	 	<td valign='middle' class=legend nowrap style='font-size:14px'>{servers}:</td>
	 	<td valign='top' style='width:2%;padding-top:5px'>$choose</td>
	 	
	 </tr>
	<tr>
		<td valign='middle' class=legend nowrap style='font-size:14px'>{timezone}:</td>
		<td valign='top'>".Field_array_Hash($arrayTimzone,"timezones$t",$timezone_def,null,null,"style:font-size:14px;padding:3px")."</td>
		
	</tr> 
	<tr>
		<td colspan=2 align='right'>
				<div style='font-size:11px;text-align:right'><i>{today}: ". date("{l} d {F} Y H:i:s")."</i></div>
				<hr>". button('{apply}',"ntpd_choose_server()",14)."</td>
	</tr>
				
				
	 </table>
	 <div class=text-info>{how_to_find_timeserver}</div><hr>

	 
	
		<div style='text-align:right;width:100%'>". button("{add}","ntpdAdd()")."&nbsp;|&nbsp;". button("{ntpd_apply}","ntpdSave()")."</div>
	 	<div id=serverlist style='width:100%;height:250px;overflow:auto'>" .main_server_list() . "</div>
	 	<hr>
	 	<div style='text-align:right;width:100%'></div>
	 	
	 	
	 	<script>
	 		function ntpd_choose_server(){
				var XHR = new XHRConnection();
	      		XHR.appendData('country',document.getElementById('ntpd_servers_choosen').value);
	      		XHR.appendData('timezones',document.getElementById('timezones$t').value);
	      		AnimateDiv('serverlist');
	      		XHR.sendAndLoad('$page', 'POST',X_ntpd_choose_server);    
			}
	 		
		var X_ntpd_choose_server= function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}
			RefreshTab('ntpd_main_config');
			}		
		
	
	 		
	 	</script>
		";
	 
	 
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$entete$form");
	
}

function main_server_list(){
	$ntp=new ntpd();
	$q=new mysql();
	
	$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%;margin-top:10px'>
<thead class='thead'>
	<tr>
	<th colspan=3>&nbsp;</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	$sql="SELECT * FROM ntpd_servers ORDER BY `ntpd_servers`.`order` ASC";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysql_num_rows($results)==0){
		$ntp->builddefaults_servers();
		$results=$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->ok){echo "$q->mysql_error<br>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$order=$ligne["order"];
		$html=$html . "<tr class=$classtr>
		<td nowrap><strong><code style='font-size:14px'>[$order]&nbsp;{$ligne["ntp_servers"]}</code></strong></td>
		<td width=1% valign='middle'>" . imgtootltip('arrow-down-32.png','{down}',"ntpdservermove('{$ligne["ntp_servers"]}','down')")."</TD>
		<td width=1% valign='middle'>" . imgtootltip('arrow-up-32.png','{up}',"ntpdservermove('{$ligne["ntp_servers"]}','up')")."</TD>
		<td width=1% valign='middle'>" . imgtootltip('delete-32.png','{delete}',"ntpdserverdelete('{$ligne["ntp_servers"]}')")."</TD>		
		</tr>
		";
		
	}
	
	$html=$html . "</table>";
	return $html;
	
}

function main_ntpdconf(){
	$ntpd=new ntpd();
	$conf=explode("\n",$ntpd->ntpdConf);
	
	while (list ($num, $val) = each ($conf) ){
		if($val==null){continue;}
		$dats[]="<div><code>".htmlspecialchars($val)."</code></div>";
	}
	
	
	$entete="	 ". RoundedLightWhite("
	 <div style='padding:5px;margin:10px;width:95%;height:300px;overflow:auto'>
	 ". implode("\n",$dats)."
	 </div>");	
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$entete");	
}

function main_sync(){
	$sql="SELECT * FROM events WHERE event_id='5' ORDER BY ID DESC LIMIT 0,100";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_events');
	$table="<table style='width:550px'>";
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){	
		
		$ligne["text"]=htmlspecialchars($ligne["text"]);
		$ligne["text"]=nl2br($ligne["text"]);
		$table=$table . 
		
		"
		<tr><td colspan=3><hr></td></tr>
		<tr>
			<td valign='top' width=1%><img src='img/fw_bold.gif'></td>
			<td valign='top' nowrap><strong>{$ligne["zDate"]}</strong></td>
			<td valign='top'><code>{$ligne["text"]}</code></td>	
		</tr>		
			";
			
	}$table=$table . "</table>"			;
	
	$table=RoundedLightGrey($table);
	
$entete="<br>
	 <H5>{syncevents}</H5>
	 <br><div style='width:550px'>$table</div>";

$tpl=new templates();
echo $tpl->_ENGINE_parse_body($entete);
	
}

function main_switch_op_save(){
	$ntp=new ntpd();
	$ntp->SaveToLdap();
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body( "<strong>{save_ntpd_ok}</strong>");
}

function main_switch_op_server(){
	$ntp=new ntpd();
	$ntp->SaveToServer();
$tpl=new templates();
	echo $tpl->_ENGINE_parse_body( "<strong>{save_toserver_ok}</strong>");	
	
}

function main_switch_op_end(){
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body( "<p class=caption>{close_windows}</p>");		
	
}

function main_switch_op(){
	
	switch ($_GET["op"]) {
		case 0:main_switch_op_save();exit;break;
		case 1:main_switch_op_server();exit;break;
		case 2:main_switch_op_end();exit;break;
		default:
			break;
	}
	
	
	$html="
	<H5>{ntpd_apply}</H5>
	<table style='width:100%'>
	<tr>
	<td width=1% valign='top'><img src='img/folder-tasks-64.jpg'></td>
	<td valign='top'>
		<div id='message_0' style='margin:3px'></div>
		<div id='message_1' style='margin:3px'></div>
		<div id='message_2' style='margin:3px'></div>
	
	</td>
	</tr>
	</table>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
}



function main_logs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$html="<iframe src='ntpd.events.php' style='width:100%;height:700px;border:0px'></iframe>";
	echo $tpl->_ENGINE_parse_body($html);
	}

	
function ntpdAddCountry(){
	$sock=new sockets();
	$GLOBALS["TIMEZONES"]=$_POST["timezones"];
	$_SESSION["TIMEZONES"]=$_POST["timezones"];
	$sock->SET_INFO("timezones",$_POST["timezones"]);
	$sock->getFrameWork("system.php?zoneinfo-set=".base64_encode($_POST["timezones"]));
	
	$ntp=new ntpd();
	$countries=$ntp->ServersList();
	$q=new mysql();
	$array=$countries[$_POST["country"]];
	writelogs("{$_POST["country"]}! TRUNCATE TABLE ntpd_servers",__FUNCTION__,__FILE__,__LINE__);
	$q->QUERY_SQL("TRUNCATE TABLE ntpd_servers","artica_backup");
	
	
	while (list ($num, $server) = each ($array) ){
		$added[]=$server;
		$sql="INSERT IGNORE INTO ntpd_servers (`ntp_servers`,`ntpd_servers`.`order`) VALUES ('$server','$num')";
		writelogs("{$_POST["country"]}! $sql",__FUNCTION__,__FILE__,__LINE__);
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){$q->mysql_error;}
		
	}
	$ntp->SaveToLdap();
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{added}\n".@implode("\n", $array),1);
	
}
	
function ntpdAdd(){
	$ntp=new ntpd();
	$ntp->AddServer($_GET["ntpdAdd"]);
	
	}
function ntpdservermove(){
	$ntp=new ntpd();
	$servername=$_GET["ntpdservermove"];
	$direction=$_GET["direction"];
	$ntp->MoveServer($servername,$direction);
	}
function ntpdserverdelete(){
	$ntp=new ntpd();
	$ntp->DeleteServer($_GET["ntpdserverdelete"]);
	}
function NTPDEnabled(){
	$sock=new sockets();
	$sock->SET_INFO("NTPDEnabled",$_GET["NTPDEnabled"]);
	$sock->SET_INFO("NTPDServerEnabled",$_GET["NTPDEnabled"]);
	$sock->getFrameWork("cmd.php?ntpd-restart=yes");

}


?>
