<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.user.inc');
	
	//if(count($_POST)>0)
	$usersmenus=new usersMenus();
	if(!$usersmenus->AllowAddUsers){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";die();
		
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["script_data"])){SAVE_SCRIPT();exit;}
	if(isset($_GET["delete"])){DELETE_SCRIPT();exit;}

	js();
	
function js(){	
	
$ou=$_GET["ou"];
	$page=CurrentPageName();
	$ou=$_GET["ou"];
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body('{LOGON_SCRIPT}');
	$html="
		var mem_dev='';
		function LOGON_SCRIPT_LOAD(){
			mem_dev='';
			YahooWin('700','$page?popup=yes&gpid={$_GET["gpid"]}','$title')
		}
		
var x_LOGON_SCRIPT_SAVE= function (obj) {
				var results=obj.responseText;
				if(results.length>0){alert(results);}
				
			}	
var x_LOGON_SCRIPT_DEL= function (obj) {
		LOGON_SCRIPT_LOAD();
				
			}				
		
		function LOGON_SCRIPT_SAVE(){
		var XHR = new XHRConnection();
		var pp=encodeURIComponent(document.getElementById('script_code').value);
		XHR.appendData('script_data',pp);
		XHR.appendData('gpid','{$_GET["gpid"]}');
		XHR.sendAndLoad('$page', 'POST',x_LOGON_SCRIPT_SAVE);	
		
		}
		
		function LOGON_SCRIPT_DEL(){
		var XHR = new XHRConnection();
		XHR.appendData('delete','{$_GET["gpid"]}');
		XHR.sendAndLoad('$page', 'GET',x_LOGON_SCRIPT_DEL);	
		
		}
		
		
	LOGON_SCRIPT_LOAD();";	
	
	echo $html;	
}

function popup(){
	$gpid=$_GET["gpid"];
	$sql="SELECT script_code FROM logon_scripts WHERE gpid=$gpid";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	$ligne["script_code"]=base64_decode($ligne["script_code"]);
	$_POST["script_data"]=str_replace("\n\n","\n",$_POST["script_data"]);
	$html="
	<div style='font-size:13px' class=explain>{LOGON_SCRIPT_TEXT}<br>
	{LOGON_SCRIPT_PUT}</div>
	<div style='float:right;margin-bottom:8px;'>". imgtootltip("delete-32.png","{delete}","LOGON_SCRIPT_DEL()")."</div>
	<textarea id='script_code' style='width:100%;height:350px;overflow:auto; font-family: \"Courier New\", Courier, monospace;padding:3px;border:3px solid #CCCCCC'>". $ligne["script_code"]."</textarea>
	<div style='text-align:right'><hr>". button("{apply}","LOGON_SCRIPT_SAVE()","18px")."</div>";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
}

function DELETE_SCRIPT(){
	$gpid=$_GET["delete"];
	$sql="DELETE FROM logon_scripts WHERE gpid=$gpid";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?smb-logon-scripts=yes");
}

function SAVE_SCRIPT(){
	$gpid=$_POST["gpid"];
	$_POST["script_data"]=url_decode_special_tool($_POST["script_data"]);
	$_POST["script_data"]=str_replace("\n\n","\n",$_POST["script_data"]);
	$datas=base64_encode($_POST["script_data"]);
	$sql="SELECT gpid FROM logon_scripts WHERE gpid=$gpid";
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	$sql_edit="UPDATE logon_scripts SET script_code='$datas' WHERE gpid=$gpid";
	$sql_add="INSERT INTO logon_scripts(gpid,script_code) VALUES($gpid,'$datas');";
	if($ligne["gpid"]==null){$sql=$sql_add;}else{$sql=$sql_edit;}
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}else{
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{success}");
		$sock=new sockets();
		$sock->getFrameWork("cmd.php?smb-logon-scripts=yes");
	}
	
}

?>