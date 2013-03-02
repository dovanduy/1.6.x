<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="ANTISPAM";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.mimedefang.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}
	if(isset($_GET["compile-rules-js"])){compile_rules_js();exit;}
	if(isset($_GET["compile-rules-perform"])){	compile_rules_perform();exit;}
	if(isset($_POST["enable-mimedefang"])){enable_mimedefang();exit;}
	if(isset($_POST["disable-mimedefang"])){disable_mimedefang();exit;}
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	echo "$('#BodyContent').load('$page?tabs=yes&in-front-ajax=yes');";
}
	
function compile_rules_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$mailman=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}::{compile_rules}");
	$html="YahooWinBrowse('750','$page?compile-rules-perform=yes','$mailman::$cmd');";
	echo $html;		
	
}

function compile_rules_perform(){
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork("mimedefang.php?reload-tenir=yes"));
	echo "
	<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:450px;border:5px solid #8E8E8E;overflow:auto;font-size:13px' id='textToParseCats$t'>$datas</textarea>
<script>
	RefreshTab('main_config_mimedefang');
</script>
		
	";
	
}
	
function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->_ENGINE_parse_body("{APP_MIMEDEFANG}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd','$mailman::$cmd');";
	echo $html;	
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("mimedefang.php?service-cmds={$_GET["service-cmds-peform"]}&MyCURLTIMEOUT=120")));
	
		$html="
<div style='width:100%;height:350px;overflow:auto'>
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th>{events}</th>
	</tr>
</thead>
<tbody class='tbody'>";	
	
	while (list ($key, $val) = each ($datas) ){
		if(trim($val)==null){continue;}
		if(trim($val=="->")){continue;}
		if(isset($alread[trim($val)])){continue;}
		$alread[trim($val)]=true;
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$val=htmlentities($val);
			$html=$html."
			<tr class=$classtr>
			<td width=99%><code style='font-size:12px'>$val</code></td>
			</tr>
			";
	
	
}

$html=$html."
</tbody>
</table>
</div>
<script>
	RefreshTab('main_config_mimedefang');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}	
	
function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();


	
	$enable_amavisdeamon_ask=$tpl->javascript_parse_text("{enable_mimedefang_ask}");		
	$disable_amavisdeamon_ask=$tpl->javascript_parse_text("{disable_mimedefang_ask}");	
	$MimeDefangEnabled=trim($sock->GET_INFO("MimeDefangEnabled",true));	
	if(!is_numeric($MimeDefangEnabled)){$MimeDefangEnabled=0;}

	if($MimeDefangEnabled==0){
		$EnableDaemonP=Paragraphe32("disabled", "mimedefang_is_currently_disabled_text", "EnablePopupMimeDefang()", "warning32.png");
	}else{
		$EnableDaemonP=Paragraphe32("enabled", "mimedefang_is_currently_enabled_text", "DisablePopupMimeDefang()", 
		"ok32.png");
	}
	
	
	$tr[]=$EnableDaemonP;
	$tr[]=Paragraphe32("service_options", "service_options_text", "Loadjs('mimedefang.service.php')", "32-parameters.png");
	$tr[]=Paragraphe32("reload_service", "reload_service_text", "MimeDefangCompileRules()", "service-restart-32.png");
	$tr[]=Paragraphe32("online_help", "online_help", "s_PopUpFull('http://www.mail-appliance.org/index.php?cID=305','1024','900');", "help_bg32.png");
	
	
	http://www.mail-appliance.org/index.php?cID=305&
	
	$table=CompileTr2($tr,"form");
		
	
	
	$html="<table style='width:100%'>
	<tr>
		<td width=1% valign='top'>
			<div id='status-$t'></div>
		</td>
		<td valign='top'>
			<div style='font-size:18px;margin:bottom:10px;text-align:right'>{APP_MIMEDEFANG}</div>
			<div style='font-size:13px' class=explain>{MIMEDEFANG_DEF}</div>
			<div id='explain-$t'>$table</div>
		</td>
	</tr>
	</table>
	<script>
	
	var x_Enablemimedefang= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		RefreshTab('main_config_mimedefang');
	}	
	
		function EnablePopupMimeDefang(){
			if(confirm('$enable_amavisdeamon_ask')){
				var XHR = new XHRConnection();
				XHR.appendData('enable-mimedefang','yes');
				AnimateDiv('explain-$t');
				XHR.sendAndLoad('$page', 'POST',x_Enablemimedefang);
			}
		}
		
		function DisablePopupMimeDefang(){
			if(confirm('$disable_amavisdeamon_ask')){
				var XHR = new XHRConnection();
				XHR.appendData('disable-mimedefang','yes');
				AnimateDiv('explain-$t');
				XHR.sendAndLoad('$page', 'POST',x_Enablemimedefang);
			}
		}
	
	
	
		LoadAjax('status-$t','$page?status=yes&t=$t');
		
		
	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function status(){
	$t=$_GET["t"];
	$ini=new Bs_IniHandler();
	$sock=new sockets();
	$page=CurrentPageName();
	$ini->loadString(base64_decode($sock->getFrameWork('mimedefang.php?status=yes')));
	$APP_MIMEDEFANG=DAEMON_STATUS_ROUND("APP_MIMEDEFANG",$ini,null);
	$APP_MIMEDEFANGX=DAEMON_STATUS_ROUND("APP_MIMEDEFANGX",$ini,null);
	$Param=unserialize(base64_decode($sock->GET_INFO("MimeDefangServiceOptions")));
	if(!is_numeric($Param["MX_TMPFS"])){$Param["MX_TMPFS"]=0;}	
	$tpl=new templates();
	
	
	if($Param["MX_TMPFS"]>5){
		$array=unserialize(base64_decode($sock->getFrameWork("mimedefang.php?getramtmpfs=yes")));
		if(!is_numeric($array["PURC"])){$array["PURC"]=0;}
		if(!isset($array["SIZE"])){$array["SIZE"]="0M";}
		$tmpfs[]="
		<tr>
			<td colspan=2 style='font-size:16px' align='left'>tmpfs:</td>
		</tr>
			<tr>
				<td valing='middle'>".pourcentage($array["PURC"])."</td>
				<td style='font-size:14px'>{$array["PURC"]}%/{$array["SIZE"]}</td>
			</tr>

			";
	}
	
	
	$q=new mysql_mimedefang_builder();
	$attachments_storage=$q->COUNT_ROWS("storage");
	
	if($attachments_storage>0){
		$sql="SELECT SUM(filesize) as tcount FROM `storage`";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$size=FormatBytes($ligne["tcount"]/1024);
	
	$tmpfs[]="
	<tr>
		<td colspan=2>
		<hr>
	<table>
		<tr>
			<td style='font-size:16px' align='left'>{attachments_storage}:</td>
		</tr>
			<tr>
				<td style='font-size:16px'><a href=\"javascript:blur();\" 
				OnClick=\"javascript:Loadjs('mimedefang.filehosting.table.php');\"
				style='font-size:16px;text-decoration:underline'>$attachments_storage {items} ($size)</td>
			</tr>
		</table>
		</td>
	</tr>
	

			";	
		
		
	}
	
	if(count($tmpfs)>0){
		$tmpfs_builded="<table style='width:30%;margin-top:15px' class=form>".@implode("\n", $tmpfs)."</table>";
	}
	
	$html="<table style='width:99%' class=form>
	<tr>
	<td>$APP_MIMEDEFANG$APP_MIMEDEFANGX
		<center style='margin-top:10px;margin-bottom:10px;width:95%' class=form>
		<table style='width:70%'>
		<tbody>
		<tr>
			<td width=10% align='center;'>". imgtootltip("32-stop.png","{stop}","Loadjs('$page?service-cmds=stop')")."</td>
			<td width=10% align='center'>". imgtootltip("restart-32.png","{stop} & {start}","Loadjs('$page?service-cmds=restart')")."</td>
			<td width=10% align='center'>". imgtootltip("32-run.png","{start}","Loadjs('$page?service-cmds=start')")."</td>
		</tr>
		</tbody>
		</table>
		</center>	
	
	</td>
	</tr>
	</table>
	<center>
		$tmpfs_builded
	</center>
	<div style='text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('status-$t','$page?status=yes&t=$t');")."</div>";
	
	
	
	echo $tpl->_ENGINE_parse_body($html);		
		
	}
	
function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$t=time();
	$q=new mysql();
	$q->BuildTables();
	
	
	$array["popup"]='{status}';
	$array["disclaimers"]='{disclaimers}';
	$array["autocompress"]='{automated_compression}';
	$array["filehosting"]='{mimedefang_filehosting}';
	$array["events"]='{events}';
	
	while (list ($num, $ligne) = each ($array) ){
		if($num=="events"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.events.new.php?mimedefang-filter=yes&noform=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		
	if($num=="disclaimers"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.disclaimers.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}		
	if($num=="autocompress"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.autocompress.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}

	if($num=="filehosting"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"mimedefang.filehosting.php\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}			
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&section=$num&$ajaxpop\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	$width="750px";
	$height="600px";
	$width="100%";$height="100%";
	
	echo "
	<div id=main_config_mimedefang style='width:{$width};height:{$height};overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_mimedefang').tabs();
			
			
			});
		</script>";		

	
}
function enable_mimedefang(){
	$sock=new sockets();
	$sock->SET_INFO("MimeDefangEnabled", 1);
	$sock->getFrameWork("mimedefang.php?restart=yes");
	$sock->getFrameWork("mimedefang.php?postfix-milter=yes");
	$sock->getFrameWork("cmd.php?artica-filter-reload=yes");	
}
function disable_mimedefang(){
	$sock=new sockets();
	
	$sock->SET_INFO("MimeDefangEnabled", 1);
	$sock->getFrameWork("mimedefang.php?restart=yes");
	$sock->getFrameWork("mimedefang.php?postfix-milter=yes");
	$sock->getFrameWork("cmd.php?artica-filter-reload=yes");		
}
	
?>	