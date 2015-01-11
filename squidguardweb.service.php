<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsDansGuardianAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_GET["service-cmds"])){service_cmds_js();exit;}
	if(isset($_GET["service-cmds-peform"])){service_cmds_perform();exit;}	
	
page();
function service_cmds_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$cmd=$_GET["service-cmds"];
	$mailman=$tpl->javascript_parse_text("{APP_SQUIDGUARD_HTTP}");
	$html="YahooWin4('650','$page?service-cmds-peform=$cmd&MyCURLTIMEOUT=120','$mailman::$cmd');";
	echo $html;
}
function service_cmds_perform(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$datas=unserialize(base64_decode($sock->getFrameWork("squidguardweb.php?service-cmds={$_GET["service-cmds-peform"]}&MyCURLTIMEOUT=120")));

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
	RefreshTab('main_squidguardweb_error_pages');
</script>

";
	echo $tpl->_ENGINE_parse_body($html);
}

function page(){
	//APP_SQUIDGUARD_HTTP --squidguard-http
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('squidguardweb.php?status=yes')));
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null);	
	echo $tpl->_ENGINE_parse_body($APP_SQUIDGUARD_HTTP);
		
}
