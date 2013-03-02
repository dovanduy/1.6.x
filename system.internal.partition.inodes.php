<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	include_once("ressources/class.autofs.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["INODES_MAX"])){INODES_MAX();exit;}
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("Inodes:{partition}:{$_GET["dev"]}");
	$html="YahooWin6('500','$page?popup=yes&dev={$_GET["dev"]}','$title');";
	echo $html;
	
}

function popup(){
	$sock=new sockets();
	$tpl=new templates();
	$page=CurrentPageName();
	$dev=$_GET["dev"];
	$devenc=base64_encode($dev);
	$array=unserialize(base64_decode($sock->getFrameWork("system.php?tune2fs-values=$devenc")));
	$t=time();
	$LAST_MOUNTED_ON=$array["LAST_MOUNTED_ON"];
	
	$INODES_SIZEZ[128]=128;
	$INODES_SIZEZ[256]=256;
	$INODES_SIZEZ[1024]=1024;

	$html="
	<div id='$t'></div>		
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{used}:</td>		
		<td style='font-size:16px;font-weight:bold'>{$array["INODES_USED"]}&nbsp;{files}</td>
		<td style='font-size:16px;font-weight:bold'>". pourcentage($array["INODES_POURC"]	)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{inode_size}:</td>		
		<td style='font-size:16px;font-weight:bold'>". Field_array_Hash($INODES_SIZEZ, "INODE_SIZE",$array["INODE_SIZE"],null,'',0,"font-size:16px")."</td>
		<td style='font-size:16px;font-weight:bold'></td>
	</tr>				
	<tr>
		<td class=legend style='font-size:16px'>Max:</td>		
		<td style='font-size:16px;font-weight:bold'>". Field_text("INODES_MAX",$array["INODES_MAX"],"font-size:16px;width:100px")."&nbsp;{files}</td>
		<td style='font-size:16px;font-weight:bold'></td>
	</tr>
	<tr><td colspan=3 align='right'><hr>". button("{apply}","Save$t()",18)."</td></tr>
	
	</table>	

				
				
	<script>

		function check$t(){
			var LAST_MOUNTED_ON='$LAST_MOUNTED_ON';
			if(LAST_MOUNTED_ON=='/'){
				document.getElementById('INODES_MAX').disabled=true;
				document.getElementById('INODE_SIZE').disabled=true;
			}
		
		}
		
		var x_save$t= function (obj) {
			var results=obj.responseText;
			if(results.length>5){alert(results);}
			Loadjs('$page?dev=$dev');
			
		}
		
		
		function Save$t(){
			var LAST_MOUNTED_ON='$LAST_MOUNTED_ON';
			if(LAST_MOUNTED_ON=='/'){return;}
			var XHR = new XHRConnection();
			XHR.appendData('dev','$devenc');
			XHR.appendData('INODES_MAX',document.getElementById('INODES_MAX').value);
			XHR.appendData('INODE_SIZE',document.getElementById('INODE_SIZE').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_save$t);		
		}
		
		
	check$t();";
	
	
	echo $tpl->_ENGINE_parse_body($html);
}
function INODES_MAX(){
	$sock=new sockets();
	echo base64_decode($sock->getFrameWork("system.php?INODES_MAX={$_POST["INODES_MAX"]}&INODE_SIZE={$_POST["INODE_SIZE"]}&dev={$_POST["dev"]}"));
	
}
