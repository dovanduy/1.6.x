<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	
	


	$user=new usersMenus();
	if($user->AllowViewStatistics==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){tabs();exit;}
	if(isset($_GET["etch"])){etch();exit;}
	if(isset($_POST["EnableVnStat"])){EnableVnStat();exit;}


	js();
	
function js(){

	$page=CurrentPageName();
	$tpl=new templates();
	if(isset($_GET["newinterface"])){$newinterface="&newinterface=yes";}
	$title=$tpl->_ENGINE_parse_body("{network_stats}");
	
	$html="$('#BodyContent').load('$page?popup=yes$newinterface');";
	echo $html;
}

function tabs(){
	$tpl=new templates();
	$page=CurrentPageName();
	$html=array();
/*
 * 		$error="<div class=form><center style='font-size:18px;color:#d32d2d;margin:5px;font-weight:bolder'>{ERROR_VNSTAT_NOT_INSTALLED}</center><center><img src='img/report-warning-256.png'></center></div>";
		echo $tpl->_ENGINE_parse_body($error);
		return;
		$error="<div class=form><center style='font-size:18px;color:#d32d2d;margin:5px;font-weight:bolder'>{NO_DATA_COME_BACK_LATER}</center><center><img src='img/report-warning-256.png'></center></div>";
 */	
if(isset($_GET["newinterface"])){$newinterface="style='font-size:14px'";}
	$users=new usersMenus();
if($users->APP_VNSTAT_INSTALLED){
		$array=unserialize(@file_get_contents("ressources/logs/vnstat-array.db"));
		if(is_array($array)){
			while (list ($num, $eth) = each ($array) ){
				if($eth=="command"){continue;}
				if($eth=="available"){continue;}
				$html[]= "<li><a href=\"$page?etch=$eth\"><span $newinterface>{nic}:&nbsp;$eth</span></a></li>\n";
		}
	}
}

if($users->IPBAN_INSTALLED){
	$html[]= "<li><a href=\"ipband.php\"><span $newinterface>{hosts}:{bandwith}</span></a></li>\n";
	
}
	
echo $tpl->_ENGINE_parse_body("
	<div id=main_config_vnstat style='width:100%;height:850px;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
		  $(document).ready(function() {
			$(\"#main_config_vnstat\").tabs();});
		</script>");		
}

function etch(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$EnableVnStat=$sock->GET_INFO("EnableVnStat");
	if(!is_numeric($EnableVnStat)){$EnableVnStat=0;}
	$t=time();
	if($EnableVnStat==0){
		$html=FATAL_ERROR_SHOW_128("{APP_VNSTAT_DISABLED_EXPLAIN}")." <center style='margin:30px' id='$t'>".button("{activate}","EnableVnStat()","22px")."</center>
		<script>
		
		var x_EnableVnStat= function (obj) {
			var tempvalue=obj.responseText;
			if(tempvalue.length>3){alert(tempvalue)}
		    RefreshTab('main_config_vnstat');
			}
		
		
		function EnableVnStat(){
			var XHR = new XHRConnection();
			XHR.appendData('EnableVnStat',1);
		    XHR.sendAndLoad('$page', 'POST',x_EnableVnStat);
		}</script>
		";
		echo $tpl->_ENGINE_parse_body($html);
		return;
		
	}
	
	
	
	
		$interface=$_GET["etch"];
		$cmdr[]="ressources/logs/vnstat-$interface-resume.png";
		$cmdr[]="ressources/logs/vnstat-$interface-hourly.png";
		$cmdr[]="ressources/logs/vnstat-$interface-daily.png";
		$cmdr[]="ressources/logs/vnstat-$interface-monthly.png";
		$cmdr[]="ressources/logs/vnstat-$interface-top.png";
		$imgs=array();
		
		
		$error="<center style='font-size:18px;color:#d32d2d;margin:5px;font-weight:bolder'>{NO_DATA_COME_BACK_LATER}</center><center><img src='img/report-warning-256.png'></center>";		
		
		while (list ($num, $filename) = each ($cmdr) ){
			if(!is_file($filename)){continue;}
			$t=time();
			$imgs[]="<center style='margin:10px'><img src='$filename?$t'></center>";
			
		}
		
		
		if(count($imgs)==0){echo $tpl->_ENGINE_parse_body("$error");return;}
		echo $tpl->_ENGINE_parse_body(@implode("\n",$imgs));
	
}

function EnableVnStat(){
	$sock=new sockets();
	$sock->SET_INFO("EnableVnStat",1);
	$sock->getFrameWork("cmd.php?restart-artica-status=yes");
	$sock->getFrameWork("cmd.php?RestartVnStat=yes");
	
	
}
