<?php

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.status.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["NewDir"])){save();exit;}
	if(isset($_GET["stats-var-spool"])){stats_var_spool();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{move_the_spooldir}");
	$html="YahooWin5('501','$page?popup=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}','$title')";
	echo $html;
}


function popup(){
	$hostname=$_GET["hostname"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();	
	$t=time();
	
	$sock=new sockets();
	
	$varspool=base64_decode($sock->getFrameWork("postfix.php?varspool=yes"));
	

	
	$html="
	<div id='$t'></div>
	<div class=explain style='font-size:14px'>{move_the_spooldir_explain}</div>
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>
		<td class=legend style='font-size:16px'>". Field_text("NewDir-$t",$varspool,"font-size:16px;width:250px;font-weight:bold")."</td>
	</tr>
	<tr>
		<td align='right' colspan=2><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>
	</table>
	</div>
	<script>
	
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t').innerHTML='';
		LoadAjax('$t','$page?stats-var-spool=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
		}		
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('hostname','$hostname');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('NewDir',document.getElementById('NewDir-$t').value);
		AnimateDiv('$t');
		XHR.sendAndLoad('$page', 'POST',X_Save$t);
	}
	
	LoadAjax('$t','$page?stats-var-spool=yes&hostname={$_GET["hostname"]}&ou={$_GET["ou"]}');
	
	</script>		
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function stats_var_spool(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("postfix.php?stats-var-spool=yes")));
	if(!is_array($array)){return;}
	$html="
	<table style='width:99%' class=form>
		<tr>
		<td class=legend style='font-size:14px'>{size}:</td>
		<td>". pourcentage($array["POURC"], "{$array["DISP"]}/{$array["SIZE"]}")."</td>
		<td class=legend style='font-size:14px'>{$array["DISP"]}/{$array["SIZE"]}</td>
		</tr>
		<tr>
		<td class=legend style='font-size:14px'>Inodes:</td>
		<td>". pourcentage($array["IPOURC"])."</td>
		<td class=legend style='font-size:14px'>{$array["IDISP"]}/{$array["INODES"]}</td>
		</tr>				
	</table>	
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}



function save(){
	if($_POST["NewDir"]=="/var/spool"){
		echo "/var/spool is the original directory...";
		return;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("postfix.php?changeSpool=yes&dir=".base64_encode($_POST["NewDir"]));
	$tpl=new templates();
	sleep(3);
	echo $tpl->javascript_parse_text("{success}");
}


