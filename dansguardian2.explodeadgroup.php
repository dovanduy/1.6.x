<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}
if(isset($_GET["fill"])){fill();exit;}
if(isset($_GET["dump"])){dump();exit;}
if(isset($_GET["display"])){display();exit;}

js();


function js(){
	header("content-type: application/javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{dump_group}");
	$q=new mysql_squid_builder();
	$sql="SELECT groupname FROM webfilter_rules WHERE ID={$_GET["rule-id"]}";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$ligne["groupname"]=utf8_encode($ligne["groupname"]);
	$title="{$ligne["groupname"]}::$title";
	echo "YahooWin5('650','$page?dump=yes&rule-id={$_GET["rule-id"]}','$title')";
	
}

function dump(){
	$t=time();
	$sock=new sockets();
	$tpl=new templates();
	
	$_GET["rule-id"]=intval($_GET["rule-id"]);
	$please_wait=$tpl->javascript_parse_text("{please_wait}...");
	$page=CurrentPageName();
	$sock->getFrameWork("ufdbguard.php?ad-dump={$_GET["rule-id"]}");
	
	$html="
	<center id='title-$t' style='font-size:22px'>$please_wait</center>
	<div id='wait-$t'></div>		
	<script>
			
		
		function Fill$t(){
				AnimateDiv('wait-$t');
				rowid={$_GET["rule-id"]};
				Loadjs('$page?fill=yes&ruleid='+rowid+'&t=$t');
			}
		
		setTimeout(\"Fill$t()\",1000);
			
	</script>
			
	";
echo $html;	
	
	
}

function fill(){
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$please_wait=$tpl->javascript_parse_text("{please_wait}...");
	$content=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.wt");
	if(($content==0) OR ($content==null)){
		echo "AnimateDiv('wait-$t');
		if(YahooWin5Open()){
			document.getElementById('title-$t').innerHTML='$please_wait';
			setTimeout(\"Fill$t()\",2000);
		}";
		return;
	}

	echo "LoadAjax('wait-$t','$page?display=yes&ruleid=$ruleid&t=$t');";
}

function display(){
	$ruleid=$_GET["ruleid"];
	$t=$_GET["t"];
	$data=@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/ufdb-dump-$ruleid.txt");
	echo "	<textarea style='width:100%;height:550px;font-size:13px;border:4px solid #CCCCCC;font-family:\"Courier New\",
	Courier,monospace;background-color:white;color:black' id='rewrite-source-edit'>$data</textarea>
	<script>
		document.getElementById('title-$t').innerHTML='';
	</script>";
	
}
