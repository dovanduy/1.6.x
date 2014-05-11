<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/class.system.network.inc');
	
$users=new usersMenus();
$tpl=new templates();
if(!$users->AsSystemAdministrator){echo $tpl->javascript_parse_text("alert('{ERROR_NO_PRIVS}');");die();}
	if(isset($_GET["search"])){search();exit;}
	page();
	
	


function page(){
	$page=CurrentPageName();
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td class=legend valign='middle'>{search}:</td>
		<td>". Field_text("hamachi-events-search",null,"font-size:14px;padding:3px;",null,null,null,false,"SyslogSearchPress(event)")."</td>
		<td align='right' width=1%>". imgtootltip("32-refresh.png","{refresh}","HamachiEventsRefresh()")."</td>
	</tr>
	</table>
	
	<div style='widht:99%;height:490px;overflow:auto;margin:5px' id='hamachi-events-table'></div>
	<script>
		function SyslogSearchPress(e){
			if(checkEnter(e)){SearchSyslog();}
		}
	
	
		function SearchSyslog(){
			var pat=escape(document.getElementById('hamachi-events-search').value);
			LoadAjax('hamachi-events-table','$page?search='+pat);
		
		}
		
		function HamachiEventsRefresh(){
			SearchSyslog();
		}
	
	SearchSyslog();
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function search(){
	
	$pattern=base64_encode($_GET["search"]);
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?syslog-query=$pattern&syslog-path=/var/lib/logmein-hamachi/h2-engine.log");
	$array=explode("\n", @file_get_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query"));
	if(!is_array($array)){return null;}
	
	$html="<table class=TableView>";
	
	while (list ($key, $line) = each ($array) ){
		$line=trim($line);
		if($line==null){continue;}
		if($tr=="class=oddrow"){$tr=null;}else{$tr="class=oddrow";}
		
			$html=$html."
			<tr $tr>
			<td><code>$line</cod>
			</tr>
		
		";
		
	}
	
	
	$html=$html."</table>";

	echo $html;
}



?>