<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["remove-table"])){remove_table();exit;}
	if(isset($_POST["reconstruct"])){reconstruct();exit;}
js();


function js() {
	$tpl=new templates();
	$page=CurrentPageName();
	if(!is_numeric($_GET["t"])){$t=time();}
	$database=$_GET["database"];
	$table=$_GET["table"];
	
	$title=$tpl->javascript_parse_text("$database/$table");
	echo "YahooWinBrowse('500','$page?popup=yes&t=$t&database=$database&table=$table&t=$t','$title')";
	
	
}


function popup(){
	$database=$_GET["database"];
	$table=$_GET["table"];
	$t=$_GET["t"];

	$tpl=new templates();
	$page=CurrentPageName();
	$delete=$tpl->javascript_parse_text("{delete}");
	
	
	$html="<center style='font-size:16px'><hr>". button("{delete} $table", "Remove$t()","18")."</center>
	<script>
	
	var x_Remove2$t= function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);}
		YahooWinBrowseHide();
		if(document.getElementById('mysql-table-$t')){ $('#mysql-table-$t').flexReload();}
	}		

	var x_Remove$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		var XHR = new XHRConnection();
		XHR.appendData('reconstruct','$database');
		XHR.sendAndLoad('$page', 'POST',x_Remove2$t);		
	}	
	
function Remove$t(){
		if(confirm('$delete `$table/$database` ?')){
		var XHR = new XHRConnection();
		XHR.appendData('database','$database');
		XHR.appendData('remove-table','$table');
		XHR.sendAndLoad('$page', 'POST',x_Remove$t);	
		}
		
	}			
	
	</script>		
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function remove_table(){
	$q=new mysql();
	$q->DELETE_TABLE($_POST["remove-table"],$_POST["database"]);
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("mysql.php?filstats=yes");	
}

function reconstruct(){
	$database=$_POST["reconstruct"];
	$tpl=new templates();
	if($database=="squidlogs"){$q=new mysql_squid_builder();$q->CheckTables();echo $tpl->javascript_parse_text("{success}");return;}
	

	
	$q=new mysql();
	$q->BuildTables();
	echo $tpl->javascript_parse_text("{success}");
}
