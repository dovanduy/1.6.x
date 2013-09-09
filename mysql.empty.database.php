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
	if(!$user->AsSystemAdministrator) {
		header("content-type: application/x-javascript");
		$tpl=new templates();
		$text=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
		$text=replace_accents(html_entity_decode($text));
		echo "alert('$text');";
		exit;
		}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["perform"])){perform();exit;}


js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$op_empty_database=$tpl->javascript_parse_text("{opempty_database}:{$_GET["db"]}");
	
	$html="
		if(confirm('$op_empty_database')){
			YahooWinBrowse('600','$page?popup=yes&db={$_GET["db"]}','{$_GET["db"]}');
		
		}
			
	";
	
	echo $html;
	
	
	
	
}

function popup(){
	$t=time();$page=CurrentPageName();$tpl=new templates();
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?empty-database={$_GET["db"]}");
	
	$html="<div id='main-$t'>
		<center><p style='font-size:18px'>{please_wait_empty_database}:{$_GET["db"]}...</p>
			<img src='img/wait_verybig_mini_red.gif'></center>
		</center>
	</div>
	
	<script>
		function Refresh$t(){	
			LoadAjax('main-$t','$page?perform=yes&t=$t&db={$_GET["db"]}');
			
			}
			
			setTimeout(\"Refresh$t()\",5000);
	
		</script>";echo $tpl->_ENGINE_parse_body($html);	
	
	
}

function perform(){
	$t=$_GET["t"];
	$page=CurrentPageName();$tpl=new templates();
	$db=$_GET["db"];
	$tt=time();
	$datas=@file_get_contents("ressources/logs/web/empty-{$_GET["db"]}.txt");
	if(strlen($datas)<100){
		$html="<center><p style='font-size:18px'>{please_wait_empty_database}:{$_GET["db"]}...</p>
			<img src='img/wait_verybig_mini_red.gif'></center>
		</center>
		<script>
			function Refresh$tt(){	
			 if(YahooWinBrowseOpen()){
					LoadAjax('main-$t','$page?perform=yes&t=$t&db={$_GET["db"]}');
				}
			}		
			setTimeout(\"Refresh$tt()\",5000);
		</script>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	echo "<textarea style='margin-top:5px;font-family:Courier New;
		font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px' 
		id='textarea$t'>$datas</textarea>
		<script>
			function Refresh$tt(){	
				if(YahooWinBrowseOpen()){
					RefreshAllTabs();	
					LoadAjax('main-$t','$page?perform=yes&t=$t&db={$_GET["db"]}');
				}
			}			
			if(YahooWinBrowseOpen()){
				setTimeout(\"Refresh$tt()\",15000);
			}
		</script>
		
		";
	
	
	
}
	
	
	

