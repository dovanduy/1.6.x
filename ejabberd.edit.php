<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["ICON_FAMILY"]="POSTFIX";
	if(posix_getuid()==0){die();}
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.main_cf.inc');
	include_once('ressources/class.ejabberd.inc');

	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["uid"])){hostname_config_save();exit;}
	if(isset($_POST["delete-hostname"])){hostname_delete();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["hostname"])){hostname_save();exit;}
	if(isset($_GET["hostname-config"])){hostname_config();exit;}
	
	
	js();
	
	
	
function js(){
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	$INSTANT_MESSAGING=$tpl->_ENGINE_parse_body("{INSTANT_MESSAGING}");
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$hostname=$_GET["hostname"];
	$title=$hostname;
	if($hostname==null){
		$title=$new_server;
	}
	
	echo "YahooWin(600,'$page?tabs=yes&hostname=$hostname&t=$t','$INSTANT_MESSAGING::$title')";
	
}

function tabs(){
	$t=$_GET["t"];
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;
	$new_server=$tpl->_ENGINE_parse_body("{new_server}");
	$hostname=$_GET["hostname"];
	if($hostname==null){
		$array["popup"]="$new_server";
	}else{
		$array["hostname-config"]=$hostname;
	}
	
	
	
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&t=$t&hostname=$hostname\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_ejabberd_$t style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_ejabberd_$t').tabs();
			});
		</script>";	

}

function popup(){
	$t=$_GET["t"];
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$hostname=$_GET["hostname"];
	$buttonname="{apply}";
	if($hostname==null){$buttonname="{add}";}
	
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{hostname}:</td>
		<td>". Field_text("hostname-$t",$hostname,"font-size:18px;width:220px")."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". button($buttonname,"SaveeJabberdHost()",22)."</td>
	</tr>
	</table>
	
	<script>
	var memhostname='';
	var x_SaveeJabberdHost= function (obj) {
		var res=obj.responseText;
		var currenthost='$hostname';
		if (res.length>3){alert(res);}
		if(currenthost.length==0){YahooWinHide();}
		$('#jabberd-table-$t').flexReload();
		if(currenthost.length==0){Loadjs('$page?hostname='+memhostname);}
		if(currenthost.length>0){RefreshTab('main_ejabberd_$t');}
	}			
		
		function SaveeJabberdHost(){
			memhostname=document.getElementById('hostname-$t').value;
			var XHR = new XHRConnection();
			XHR.appendData('hostname',document.getElementById('hostname-$t').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveeJabberdHost);	
		}	
	
	</script>
	
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function hostname_config(){
	
	$t=$_GET["t"];
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$hostname=$_GET["hostname"];
	$buttonname="{apply}";
	$jaberd=new ejabberd($hostname);	
	$status="{activew}";
	if($jaberd->enabled==0){$status="{inactive}";}
	$ldap=new clladp();
	$ou=$ldap->ou_by_smtp_domain($hostname);
	
	
	$html="<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:18px'>{status}:</td>
		<td style='font-size:18px'>$status</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:18px'>{administrator}:</td>
		<td>". Field_text("uid-$t",$jaberd->uid,"font-size:18px;width:220px")."</td>
		<td>". button("{browse}...","Loadjs('MembersBrowse.php?OnlyUsers=1&NOComputers=1&field-user=uid-$t&Zarafa=1&organization=$ou')",12)."</td>
	</tr>

	</table>
<hr>
<div style='text-align:right'>".button($buttonname,"SaveeJabberdCHost()",22)."</div>	
	<script>
	
	var x_SaveeJabberdCHost= function (obj) {
		var res=obj.responseText;
		var currenthost='$hostname';
		if (res.length>3){alert(res);}
		$('#jabberd-table-$t').flexReload();
		RefreshTab('main_ejabberd_$t');
	}			
		
		function SaveeJabberdCHost(){
			var XHR = new XHRConnection();
			XHR.appendData('uid',document.getElementById('uid-$t').value);
			XHR.appendData('hostname','$hostname');
			XHR.sendAndLoad('$page', 'POST',x_SaveeJabberdCHost);	
		}	
	
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function hostname_delete(){
	$jb=new ejabberd($_POST["delete-hostname"]);
	if(!$jb->Delete()){return;}	
	
}

function hostname_config_save(){
	$jb=new ejabberd($_POST["hostname"]);
	$jb->uid=$_POST["uid"];
	writelogs("uid={$_POST["uid"]}",__FUNCTION__,__FILE__,__LINE__);
	if(!$jb->SaveHostname()){return;}
}

function hostname_save(){
	$jb=new ejabberd($_POST["hostname"]);
	if(!$jb->SaveHostname()){return;}
	
}



