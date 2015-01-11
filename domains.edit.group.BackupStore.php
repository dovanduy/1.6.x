<?php
// table storage_containers
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
$GLOBALS["CURRENT_PAGE"]=CurrentPageName();
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.artica.inc');
include_once('ressources/class.groups.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.samba.inc');
include_once('ressources/class.external.ad.inc');


if(isset($_POST["gid"])){Save();exit;}

page();



function page(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$gid=$_GET["gid"];
	$t=time();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM storage_containers WHERE `groupid`='".mysql_escape_string2($gid)."'","artica_backup"));
	if(!is_numeric($ligne["maxsize"])){$ligne["maxsize"]="5000";}
	$html="
	<div style='font-size:16px' class=text-info>{BACKUP_STORAGE_ENDUSERS_CONTAINER_EXPLAIN}</div>
	<div id='animate-$t'></div>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{enable}:</td>
		<td>". Field_checkbox("enable-$t", 1,$ligne["enabled"])."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{max_size}:</td>	
		<td style='font-size:16px'>". Field_text("maxsize-$t", $ligne["maxsize"],"font-size:16px;width:120px")."&nbsp;M</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{directory}:</td>	
		<td style='font-size:16px'>". Field_text("directory-$t", $ligne["directory"],"font-size:16px;width:220px").button_browse("directory-$t")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{apply}", "Save$t()",18)."</td>
	</tr>
	</table>	
	<script>
	var xSave$t= function (obj) {
		var results=obj.responseText;
		document.getElementById('animate-$t').innerHTML='';
		if(results.length>0){alert(results);return;}
		
		YahooWinHide();
	}
	
	function Save$t(){
		var enabled=0;
		var XHR = new XHRConnection();
		XHR.appendData('gid','{$_GET["gid"]}');
	
		if(document.getElementById('enable-$t').checked){enabled=1;}
		XHR.appendData('enabled',enabled);
		XHR.appendData('maxsize',document.getElementById('maxsize-$t').value);
		XHR.appendData('directory',document.getElementById('directory-$t').value);
		AnimateDiv('animate-$t');
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}	
				
				
	</script>
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function Save(){
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("storage_containers", "artica_backup")){
		$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`storage_containers` (
				`groupid` VARCHAR( 255 ) NOT NULL,
				`enabled` smallint( 1 ) NOT NULL,
				`maxsize` INT UNSIGNED ,
				`directory` VARCHAR( 255 ) NOT NULL,
				 PRIMARY KEY ( `groupid` ),
				 KEY `enabled`(`enabled`)
				) ENGINE=MYISAM;";
		$q->QUERY_SQL($sql,'artica_backup');
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	$_POST["directory"]=mysql_escape_string2($_POST["directory"]);
	$gid=mysql_escape_string2($_POST["gid"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM storage_containers WHERE `groupid`='$gid'","artica_backup"));
	
	//echo "$gid = {$ligne["directory"]} Enabled={$_POST["enabled"]}\n";
	if($ligne["directory"]<>null){
		$q->QUERY_SQL("UPDATE storage_containers SET `maxsize`='{$_POST["maxsize"]}',
		enabled='{$_POST["enabled"]}',`directory`='{$_POST["directory"]}' WHERE `groupid`='$gid'","artica_backup");
	}else{
		$q->QUERY_SQL("INSERT IGNORE INTO storage_containers (groupid,enabled,maxsize,`directory`)
				VALUES ('$gid','{$_POST["enabled"]}','{$_POST["maxsize"]}','{$_POST["directory"]}')","artica_backup");
	}
	if(!$q->ok){echo $q->mysql_error;return;}
}


