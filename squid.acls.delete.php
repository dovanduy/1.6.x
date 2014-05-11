<?php
	if(isset($_GET["verbose"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	

	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	header("content-type: application/javascript");
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_GET["popup"])){popup();exit;}
if(isset($_POST["DeleteGroups"])){delete();exit;}

js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/javascript");
	$title=$tpl->_ENGINE_parse_body("{delete_all_acls}");
	echo "YahooWin2(550,'$page?popup=yes&t={$_GET["t"]}','$title');";
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];	
	$tt=time();
	$html="
	<div id='id-final-$t'>
	<div id='serverkerb-$tt'></div>
		<div style='width:98%' class=form>
			<table style='width:100%'>
				<tr>
					<td class=legend style='font-size:14px'>{delete_associated_groups}:</td>
					<td>". Field_checkbox("DeleteGroups-$t",1,0)."</td>
				</tr>
				<tr>
					<td colspan=2 align='right'><hr>". button("{delete}","Save$tt()",16)."</td>
				</tr>
			</table>
		</div>
	</div>
	<script>
	var x_Save$tt= function (obj) {
		var results=obj.responseText;
		document.getElementById('serverkerb-$tt').innerHTML='';
		
		$('#table-$t').flexReload();
		if(results.length>3){
			document.getElementById('id-final-$t').innerHTML=\"<textarea style='margin-top:5px;font-family:Courier New;font-weight:bold;width:100%;height:520px;border:5px solid #8E8E8E;overflow:auto;font-size:11.5px' id='mibtxt$t'>\"+results+\"</textarea>\";
		}else{
			YahooWin2Hide();
		}
		
	}
	
	function Save$tt(){
		var XHR = new XHRConnection();
		var DeleteGroups=0;
		if(document.getElementById('DeleteGroups-$t').checked){DeleteGroups=1;}
		XHR.appendData('DeleteGroups',DeleteGroups);
		AnimateDiv('serverkerb-$tt');
		XHR.sendAndLoad('$page', 'POST',x_Save$tt);
	
	}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);	
}

function delete(){
	$q=new mysql_squid_builder();
	
	$sql="SELECT ID FROM webfilters_sqacls";
	$results=$q->QUERY_SQL($sql);
	while ($ligne = mysql_fetch_assoc($results)) {
		$aclid=$ligne["ID"];
		if(!is_numeric($aclid)){$aclid=0;}
		if($aclid==0){echo "ACL ID = 0 ???\n";return;}
		if(!delete_link($aclid)){return;}
		echo "\n\n**********************\nACL $aclid Delete Access rules\n";
		$q->QUERY_SQL("DELETE FROM webfilters_sqaclaccess WHERE aclid=$aclid");
		
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		echo "ACL $aclid Delete ACL rule\n";
		$q->QUERY_SQL("DELETE FROM webfilters_sqacls WHERE ID=$aclid");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	
	
}
function delete_link($aclid){
	
	$q=new mysql_squid_builder();
	$sql="SELECT zmd5,gpid FROM webfilters_sqacllinks WHERE aclid='$aclid'";
	$results=$q->QUERY_SQL($sql);
	
	echo "ACL $aclid ". mysql_num_rows($results)." links\n";
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$zmd5=$ligne["zmd5"];
		if($_POST["DeleteGroups"]==1){
				echo "ACL $aclid Delete group id $gpid\n";
				$q->QUERY_SQL("DELETE FROM webfilters_sqgroups WHERE ID=$gpid");
				if(!$q->ok){echo $q->mysql_error."\n";return;}
			}
		echo "ACL $aclid Delete link id $zmd5\n";
		$q->QUERY_SQL("DELETE FROM webfilters_sqacllinks WHERE zmd5='$zmd5'");
		if(!$q->ok){echo $q->mysql_error."\n";return;}
	}
	
	return true;
}




