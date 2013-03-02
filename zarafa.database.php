<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.cyrus.inc');
	include_once('ressources/class.cron.inc');
	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["mysql-dir"])){mysql_dir_popup();exit;}
	if(isset($_POST["ChangeMysqlDir"])){ChangeMysqlDir();exit;}
	if(isset($_GET["dbsize"])){dbsize();exit;}
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	
	
	$movefolder=Paragraphe('folder-64.png','{storage_directory}',
	'{change_mysql_directory_text}',"javascript:YahooWin3(405,'$page?mysql-dir=yes','{storage_directory}');",null);

	$restore=Paragraphe('database-restore-64.png','{restore_from_backup}',
			'{restore_from_backup_text}',"javascript:Loadjs('zarafa.dabatase.restore.php');",null);
	
	$tasks=Paragraphe('folder-tasks2-64.png','{processes_list}',
			'{processes_list_mysql_explain}',"javascript:Loadjs('zarafa.dabatase.processlist.php');",null);	
	

	$zarafaSeconds=Paragraphe('zarafa-web-64.png','{zarafa_second_instance}',
			'{zarafa_second_instance_text}',"javascript:Loadjs('zarafa.dabatase.second-instance.php');",null);
	
	// mysqladmin --socket /var/run/mysqld/zarafa-db.sock -u root processlist
	
	$tr[]=$movefolder;
	$tr[]=$restore;
	$tr[]=$tasks;
	$tr[]=$zarafaSeconds;
	
	
	$table=CompileTr2($tr,"form");
	$html="
	<table style='width:100%'>
	<tr>
	<td valign='top'><div id='dbsize' style=width:300px></div></td>		
	<td valign='top'><div style=width:550px><center>$table</center></div></td>
	</tr>
	</table>
	
	<script>
		LoadAjaxTiny('dbsize','$page?dbsize=yes&refresh=dbsize');
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);	
}

function dbsize(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$refresh=$_GET["refresh"];
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/zarafadb.size.db";
	$array=unserialize(@file_get_contents($arrayfile));
	if(!is_array($array)){
		$sock->getFrameWork("zarafa.php?artica-dbsize=yes");
		echo "<script>LoadAjaxTiny('$refresh','$page?dbsize=yes&refresh=$refresh')</script>";
		return;
		
	}
	
	if(isset($_GET["recalc"])){
		$sock->getFrameWork("zarafa.php?artica-dbsize=yes");
		$array=unserialize(@file_get_contents($arrayfile));
	}
	
	$color="black";
	if($array["IPOURC"]>99){$color="red";}
	if($array["POURC"]>99){$color="red";}	
	
	$t=time();
	$html="
	
	<table style='width:95%' class=form>
	<tr>
		<td class=legend>{current_size}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["DBSIZE"])."</td>
	</tr>	
	<tr>
		<td class=legend>{hard_drive}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["SIZE"])."</td>
	</tr>	
	<tr>
		<td class=legend>{used}:</td>		
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["USED"])."</td>
	</tr>	
	<tr>
		<td class=legend>{free}:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>". FormatBytes($array["AIVA"])." {$array["POURC"]}%</td>
	</tr>
	<tr>
		<td class=legend>inodes:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>{$array["IUSED"]}/{$array["ISIZE"]} ({$array["IPOURC"]}%)</td>
	</tr>
	<tr>
		<td colspan=2 align='right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjax('$refresh','$page?dbsize=yes&recalc=yes&refresh=$refresh')")."</td>
	</tr>														
	</table>	
	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function mysql_dir_popup(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$ChangeMysqlDir=base64_decode($sock->getFrameWork("zarafa.php?ChangeMysqlDir-zarafa=yes"));
	if($ChangeMysqlDir==null){$ChangeMysqlDir="/opt/zarafa-db/data";}
	$t=time();
	$html="
	<div id='ChangeMysqlDirDiv$t'></div>
	<div class=explain>{ChangeMysqlDir_explain}</div>
	<p>&nbsp;</p>
	<table style='width:100%'>
	<tr>
		<td class=legend>{directory}:</td>
		<td>". Field_text("ChangeMysqlDir-zarafa",$ChangeMysqlDir,"font-size:16px;padding:3px;width:220px")."</td>
		<td><input type='button' value='{browse}...' OnClick=\"Loadjs('SambaBrowse.php?no-shares=yes&field=ChangeMysqlDir-zarafa')\"></td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>". button("{apply}","SaveChangeMysqlDir$t()","18")."</td>
			</tr>
			</table>
<script>
	var x_SaveChangeMysqlDir= function (obj) {
			var tempvalue=obj.responseText;
			document.getElementById('ChangeMysqlDirDiv$t').innerHTML='';
			if(tempvalue.length>3){alert(tempvalue)};
			
	}

	function SaveChangeMysqlDir$t(){
		var XHR = new XHRConnection();
		XHR.appendData('ChangeMysqlDir',document.getElementById('ChangeMysqlDir-zarafa').value);
		AnimateDiv('ChangeMysqlDirDiv$t');
		XHR.sendAndLoad('$page', 'POST',x_SaveChangeMysqlDir);
	}
</script>
</div>
";

echo $tpl->_ENGINE_parse_body($html);

}

function ChangeMysqlDir(){
	$sock=new sockets();
	$sock->getFrameWork("zarafa.php?ChangeMysqlDir-articadb=yes&dir=".base64_decode($_POST["ChangeMysqlDir"]));
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{zarafadb_changedir_exp}");
	
	
}
