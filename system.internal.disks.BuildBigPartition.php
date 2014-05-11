<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["dev"])){perform();exit;}
	if(isset($_GET["GetLogs"])){GetLogs();exit;}
js();


function js(){
	$page=CurrentPageName();
	header("content-type: application/x-javascript");
	$dev=$_GET["dev"];
	$devenc=urlencode($dev);
	echo "YahooWinBrowse('600','$page?popup=yes&dev=$devenc','$dev',true)";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$sock=new sockets();
	$fsarray=unserialize(base64_decode($sock->getFrameWork("system.php?fsarray=yes")));
	$macro_build_bigpart_warning=$tpl->javascript_parse_text('{macro_build_bigpart_warning}');
	$devenc=urlencode($_GET["dev"]);
	$t=time();
	$html="
	
	<div style='width:98%' class=form>	
	<div style='font-size:18px'>{macro_build_bigpart}</td>
	<div style='font-size:14px' class=explain>{macro_build_bigpart_explain}</div>	
	<table style='width:100%'>
	<tr>
	<td style='font-size:16px'>{filesystem_type}:</td>
	<td>".Field_array_Hash($fsarray, "fs_type-$t","ext4","style:font-size:16px")."</td>
	<td width=1%></td>
	</tr>
	<tr>
	<td style='font-size:16px'>{label}:</td>
	<td>". Field_text("labeldev-$t","NewDisk","font-size:16px;width:100px",null,null,null,false,"SaveCK$t(event)")."</td>
	</tr>	
	<tr>
	<td colspan=2 align=right><hr>". button("{apply}","Save$t()",18)."</td>
	</tr>
	</table>
	</div>
	<div style='width:95%' id='$t-results'></div>	
	<script>
	
function GetLogs$t(){
	LoadAjaxTiny('$t-results','$page?GetLogs=yes&dev=$devenc');
	if(!YahooWinBrowseOpen()){return;}
	setTimeout('GetLogs$t()',5000);
}
	
	
var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);}
	UnlockPage();
	setTimeout('GetLogs$t()',3000);
}

function SaveCK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}
		
function Save$t(){
	if(!confirm('$macro_build_bigpart_warning')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('dev','{$_GET["dev"]}');
	XHR.appendData('fs_type',document.getElementById('fs_type-$t').value);
	XHR.appendData('label',document.getElementById('labeldev-$t').value);
	LockPage();
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
</script>			
";
	echo $tpl->_ENGINE_parse_body($html);
}
function perform(){
	$dev=$_POST["dev"];
	$label=$_POST["label"];
	$label=substr($label,0,16);
	$label=trim($label);
	$label=replace_accents($label);
	$sock=new sockets();
	$fs_type=$_POST["fs_type"];
	//--format-b-part
	$dev=urlencode($dev);
	$label=urlencode($label);
	
	echo "Disk:$dev\nLabel: $label\nFile type:$fs_type\n";
	
	$datas=base64_decode($sock->getFrameWork("cmd.php?fdisk-build-big-partitions=yes&dev=$dev&label=$label&fs_type=$fs_type&MyCURLTIMEOUT=240"));
}

function GetLogs(){
	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?usb-scan-write=yes&force=yes&tenir=yes&MyCURLTIMEOUT=120");
	
	$filelogs="/usr/share/artica-postfix/ressources/logs/web/".md5($_GET["dev"]);
	$f=explode("\n",@file_get_contents($filelogs));
	while (list ($cat, $line) = each ($f) ){
		echo "<div>".htmlentities($line)."</div>";
		
	}
	
}
