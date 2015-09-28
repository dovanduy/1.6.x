<?php
//ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');



if(isset($_POST["cloneFrom"])){Save();exit;}
page();



function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$uuid=$_GET["uuid"];
	$t=time();
	$q=new mysql_meta();
	$sql="SELECT uuid,hostname,public_ip,hostag FROM metahosts";
	$results=$q->QUERY_SQL($sql);
	
	$CLONEZ[null]="{none}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$CLONEZ[$ligne["uuid"]]="{$ligne["hostname"]} - {$ligne["hostag"]} - [{$ligne["public_ip"]}]";
		
	}
	
	$def=$q->CloneSource($uuid);
	$html="<div class=explain style='font-size:26px'>{artica_meta_cloning_explain}</div>
			<div class=form style='width:98%'>
			<table style='width:100%'>
			<tr>
				<td class=legend style='font-size:26px'>{source_server}:</td>
				<td>". Field_array_Hash($CLONEZ, "cloneFrom-$t",$def,null,null,0,"font-size:26px")."</td>
			</tr>
			<tr>
				<td colspan=2 align='right'><p>&nbsp;</p></td>
			</tr>						
			<tr>
				<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",32)."</td>
			</tr>
			</table>
		</div>
<script>
	var xSave$t=function (obj) {
		var ID='{$_GET["ID"]}';
		var results=obj.responseText;
		if(results.length>0){alert(results);}		
		RefreshTab('meta-hosts-{$uuid}');
	}	


	function Save$t(){
		var XHR = new XHRConnection();
	  	XHR.appendData('cloneFrom',document.getElementById('cloneFrom-$t').value);
	  	XHR.appendData('uuid','$uuid');
	  	XHR.sendAndLoad('$page', 'POST',xSave$t);
	}
</script>
";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function Save(){
	
	$q=new mysql_meta();
	
	if(!$q->FIELD_EXISTS("metahosts", "cloneFrom")){
		$q->QUERY_SQL("ALTER TABLE `metahosts` ADD `cloneFrom` VARCHAR(90),ADD INDEX ( `cloneFrom` )");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	$q->QUERY_SQL("UPDATE metahosts SET cloneFrom='{$_POST["cloneFrom"]}' WHERE uuid='{$_POST["uuid"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	$sock=new sockets();
	$sock->getFrameWork("artica.php?meta-metaclient-clonesource=yes&uuid={$_POST["uuid"]}");
	
	
}
