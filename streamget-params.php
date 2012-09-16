<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	$user=new usersMenus();
	if(!$user->AsSquidAdministrator){
		$tpl=new templates();
		echo "alert('".$tpl->javascript_parse_text("{ERROR_NO_PRIVS}").");";
		exit;
		
	}
	if(isset($_POST["SquidGuardApachePort"])){save();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	
js();	
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{streamGetService}");
	
	$html="
		YahooWin5('530','$page?popup=yes','$title');
	";
	echo $html;
		
		
}


function popup(){
	$page=CurrentPageName();
	$sock=new sockets();
	$SquidGuardApachePort=$sock->GET_INFO("SquidGuardApachePort");
	if(!is_numeric($SquidGuardApachePort)){$SquidGuardApachePort=9020;}
	$SquidGuardServerName=$sock->GET_INFO("SquidGuardServerName");
	$SquidGuardStorageDir=$sock->GET_INFO("SquidGuardStorageDir");
	$SquidGuardMaxStorageDay=$sock->GET_INFO("SquidGuardMaxStorageDay");
	$StreamCacheYoutubeEnable=$sock->GET_INFO("StreamCacheYoutubeEnable");
	$t=time();
	
	if($SquidGuardStorageDir==null){$SquidGuardStorageDir="/home/artica/cache";}
	if(!is_numeric($SquidGuardMaxStorageDay)){$SquidGuardMaxStorageDay=30;}	
	if($SquidGuardServerName==null){$SquidGuardServerName=$_SERVER['SERVER_ADDR'];}
	
	
	$html="
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enable_youtube_caching}:</td>
		<td>". Field_checkbox("StreamCacheYoutubeEnable",1,$StreamCacheYoutubeEnable,"StreamCacheYoutubeEnableCheck()")."</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{listen_port}:</td>
		<td>". Field_text("SquidGuardApachePort",$SquidGuardApachePort,"font-size:14px;padding:3px;width:60px",null,null,null,false,"")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{servername}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardServerName",$SquidGuardServerName,"font-size:14px;padding:3px;width:180px",null,null,null,false,"")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{storage_directory}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardStorageDir","$SquidGuardStorageDir","font-size:14px;padding:3px;width:290px",null,null,null,false,"")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{MaxStorageDay}:</td>
		<td style='font-size:14px'>". Field_text("SquidGuardMaxStorageDay",$SquidGuardMaxStorageDay,"font-size:14px;padding:3px;width:60px",null,null,null,false,"")."&nbsp;{days}</td>
	</tr>		
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveStreamGet()")."</td>
	</tr>	
	</table>
	</div>
	<script>
	
		var x_SaveStreamGet=function(obj){
		  YahooWin5Hide();
      	  Loadjs('$page');
		}
		
	function StreamCacheYoutubeEnableCheck(){
		document.getElementById('SquidGuardApachePort').disabled=true;
		document.getElementById('SquidGuardServerName').disabled=true;
		document.getElementById('SquidGuardStorageDir').disabled=true;
		document.getElementById('SquidGuardMaxStorageDay').disabled=true;
		if(document.getElementById('StreamCacheYoutubeEnable').checked){
			document.getElementById('SquidGuardApachePort').disabled=false;
			document.getElementById('SquidGuardServerName').disabled=false;
			document.getElementById('SquidGuardStorageDir').disabled=false;
			document.getElementById('SquidGuardMaxStorageDay').disabled=false;		
		}
	}

	function SaveStreamGet(){
      var XHR = new XHRConnection();
     XHR.appendData('SquidGuardApachePort',document.getElementById('SquidGuardApachePort').value);
     XHR.appendData('SquidGuardServerName',document.getElementById('SquidGuardServerName').value);
     XHR.appendData('SquidGuardStorageDir',document.getElementById('SquidGuardStorageDir').value);
     XHR.appendData('SquidGuardMaxStorageDay',document.getElementById('SquidGuardMaxStorageDay').value);
     AnimateDiv('$t');
     XHR.sendAndLoad('$page', 'POST',x_SaveStreamGet);     	
	
	}
	StreamCacheYoutubeEnableCheck();
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function save(){
	
	$sock=new sockets();
	
	while (list ($num, $ligne) = each ($_POST) ){
		$sock->SET_INFO($num, $ligne);
	}
	
	$sock->getFrameWork("cmd.php?reload-squidguardWEB=yes");
	$sock->getFrameWork("squid.php?squid-reconfigure=yes");
	
	
}

?>