<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if($argv[1]=="verbose"){echo "Verbosed\n";$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.mysql-server.inc');
	include_once('ressources/class.mysql-multi.inc');
		
	
	$user=new usersMenus();
	if(!$GLOBALS["EXECUTED_AS_ROOT"]){
	if(($user->AsSystemAdministrator==false) OR ($user->AsSambaAdministrator==false)) {
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
		}
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["restore-db"])){restore_perform();exit;}
	if(isset($_GET["isrun"])){isrun();exit;}
js();


function js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$database=$_GET["database"];
	$title=$tpl->_ENGINE_parse_body("{restore}::{database}::{$_GET["database"]}");
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$html="YahooWinS('650','$page?popup=yes&instance-id={$_GET["instance-id"]}&database={$_GET["database"]}','$title')";
	echo $html;
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$warn_restore_db=$tpl->javascript_parse_text("{warn_restore_db}");

	$t=time();	
	$html="
	<div id='ttl-$t'></div>
	<div style='text-align:right'>". imgtootltip("20-refresh.png","{refresh}","ttl$t()")."</div>
	<div id='anim-$t'></div>
	<table style='width:99%' class=form>
	
	<tr>
		<td class=legend style='font-size:16px'>{source_file}:</td>
		<td>". Field_text("source-$t",null,"font-size:16px;padding:3px;width:300px")."</td>
		<td>". button("{browse}","Loadjs('tree.php?select-file=sql,gz&target-form=source-$t')")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{restore}","Restore$t()",18)."</td>
	</tr>
	</table>
	
	<script>
	
	var x_Restore$t= function (obj) {
		document.getElementById('anim-$t').innerHTML='';
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		ttl$t();
	}	
	
	
	function Restore$t(){
		if(confirm('$warn_restore_db')){
			var XHR = new XHRConnection();
			XHR.appendData('restore-db','{$_GET["database"]}');
			XHR.appendData('restore-file',document.getElementById('source-$t').value);
			XHR.appendData('instance-id','{$_GET["instance-id"]}');	
			AnimateDiv('anim-$t');
			XHR.sendAndLoad('$page', 'POST',x_Restore$t);	

			}
			
		}
		
	function ttl$t(){
		LoadAjaxTiny('ttl-$t','$page?isrun=yes&database={$_GET["database"]}');
	}
	ttl$t();
	
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function isrun(){
	$tpl=new templates();
	$sock=new sockets();
	$data=base64_decode($sock->getFrameWork("mysql.php?restore-exists={$_GET["database"]}"));
	if(trim($data)==null){return;}
	$data=str_replace("uptime=", "",$data);
	$html="
	<table style='width:99%' class=form>
		<tr>
			<td width=1%><img src='img/ajax-loader.gif'></td>
			<td style='font-size:16px' valign='middle'>{running}: {since} <strong>$data</strong></td>
		</tr>
	</table>
	";
	echo $tpl->_ENGINE_parse_body($html);
		
}

function restore_perform(){
	$database=$_POST["restore-db"];
	$source=base64_encode($_POST["restore-file"]);
	$instance_id=$_POST["instance-id"];
	$sock=new sockets();
	$tpl=new templates();
	$sock->getFrameWork("mysql.php?restore-db=$database&source=$source&instance-id=$instance_id");
	sleep(2);
	echo $tpl->javascript_parse_text("{warn_restore_db_background}",1)."\nDatabase:$database\nFile:$source\nMySQL Instance:$instance_id";
	
	
}

