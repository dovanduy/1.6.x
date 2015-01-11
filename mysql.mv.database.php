
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
	if(isset($_POST["NewDir"])){perform();exit;}
	if(isset($_GET["stats-mv"])){stats_mv();exit;}

js();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$op_empty_database=$tpl->javascript_parse_text("{move_database_disk}:{$_GET["db"]}");
	$html="YahooWin6('600','$page?popup=yes&db={$_GET["db"]}','$op_empty_database');";
	echo $html;
	
	
	
	
}

function popup(){
	
	$t=time();$page=CurrentPageName();$tpl=new templates();
	$sock=new sockets();
	$move_database_disk=$tpl->javascript_parse_text("{move_database_disk}");
	$dir=base64_decode($sock->getFrameWork("mysql.php?database-path=". base64_encode($_GET["db"])));
	$html="
	<div style='font-size:14px' class=text-info>{mv_database_explain}</div>
	<table style='width:100%'>
	<tr>
	 <td style='font-size:16px' class=legend>{current_directory}:</td>
	 <td>". Field_text("mv$t",$dir,"font-size:16px;width:80%",null,null,null,false,"blur()")."</td>
	 <td width=1%>". button("{browse}...","Loadjs('tree.php?target-dir=mv$t')",14)."</td>
	</tr>
	<tr>
		<td align='right' colspan=3><hr>". button("{apply}","Save$t()","18px")."</td>
	</tr>	 		
	</table>
	<div id='$t'></div>
	<script>
	
	var X_Save$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		document.getElementById('$t').innerHTML='';
		LoadAjax('$t','$page?stats-mv=yes&db={$_GET["db"]}&t=$t');
		}		
	
	function Save$t(){
		if(confirm('$move_database_disk ?')){
			var XHR = new XHRConnection();
			XHR.appendData('db','{$_GET["db"]}');
			XHR.appendData('NewDir',document.getElementById('mv$t').value);
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',X_Save$t);
		}
	}
	
	
	
	</script>					
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}
function perform(){
	$NewDir=$_POST["NewDir"];
	$database=$_POST["db"];
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?movedb=$database&path=".base64_encode($NewDir));
	
}

function stats_mv(){
	$t=$_GET["t"];
	$tt=time();
	$page=CurrentPageName();$tpl=new templates();
	$db=$_GET["db"];
	
	$datas=@file_get_contents("ressources/logs/web/empty-{$_GET["db"]}.txt");
	if(strlen($datas)<100){
		$html="<center><p style='font-size:18px'>{please_wait}:{$_GET["db"]}...</p>
		<img src='img/wait_verybig_mini_red.gif'></center>
		</center>
		<script>
		function Refresh$tt(){
		if(YahooWin6Open()){
		LoadAjax('$t','$page?stats-mv=yes&db={$_GET["db"]}&t=$t');
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
	if(YahooWin6Open()){
	RefreshAllTabs();	
	LoadAjax('$t','$page?stats-mv=yes&db={$_GET["db"]}&t=$t');
	}
	}
	if(YahooWin6Open()){
	setTimeout(\"Refresh$tt()\",15000);
	}
	</script>
	
	";	
	
}
	
	
	

