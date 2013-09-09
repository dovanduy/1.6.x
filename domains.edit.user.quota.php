<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.groups.inc');
	

			
	if(!CheckRights()){
		$tpl=new templates();
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}	
	
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["block-softlimit"])){SaveInfos();exit;}

js();


function CheckRights(){
	if(isset($_POST["uid"])){$_GET["uid"]=$_POST["uid"];}
	if(!$_GET["uid"]){return false;}
	$usersprivs=new usersMenus();
	if($usersprivs->AsAnAdministratorGeneric){return true;}
	if($usersprivs->AllowAddGroup){return true;}
	if($usersprivs->AllowAddUsers){return true;}
	return false;
}

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$uid=$_GET["uid"];
	$uidText=$uid;
	if(preg_match("#@([0-9]+)#", $uid,$re)){
		$gp=new groups($re[1]);
		$uidText=$gp->groupName;
	}
	
	
	$title=$tpl->_ENGINE_parse_body($uidText.'::{disk_user_quota}');	
	
	
	
$html="

function user_quota_load(){
	YahooWin4('450','$page?popup=yes&uid={$_GET["uid"]}&userid={$_GET["uid"]}&ou={$_GET["ou"]}','$title');
	}
	
user_quota_load();
";
echo $html;
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sql="SELECT * FROM quotaroot WHERE uid='{$_GET["uid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$t=time();
	
	
	$sql="SELECT * FROM repquota WHERE uid='{$_GET["uid"]}'";
	$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));	
	if(is_numeric($ligne2["blockused"])){$status="<div style='font-size:14px;color:#9F0000'>{used}: ".FormatBytes($ligne2["blockused"])."&nbsp;|&nbsp;{$ligne2["filesusers"]} {files}</div>";}
		
	
	
	$html="
	$status
	<div id='$t'>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{enabled}:</td>
		<td style='font-size:14px'>". Field_checkbox("quota-enabled",1,$ligne["enabled"],"EnableQutotaRott()")."</tD>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{size}:{soft_limit}:</td>
		<td style='font-size:14px'>". Field_text("block-softlimit",$ligne["block-softlimit"],"font-size:14px;width:60px")."&nbsp;MB</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{size}:{hard_limit}:</td>
		<td style='font-size:14px'>". Field_text("block-hardlimit",$ligne["block-hardlimit"],"font-size:14px;width:60px")."&nbsp;MB</tD>
	</tr>		
	<tr>
		<td class=legend style='font-size:14px'>{filesnumber}:{soft_limit}:</td>
		<td style='font-size:14px'>". Field_text("inode-softlimit",$ligne["inode-softlimit"],"font-size:14px;width:60px")."&nbsp;{files}</tD>
	</tr>	
	<tr>
		<td class=legend style='font-size:14px'>{filesnumber}:{hard_limit}:</td>
		<td style='font-size:14px'>". Field_text("inode-hardlimit",$ligne["inode-hardlimit"],"font-size:14px;width:60px")."&nbsp;{files}</tD>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{grace_period}:</td>
		<td style='font-size:14px'>". Field_text("GraceTime",10080,"font-size:14px;width:60px")."&nbsp;{minutes}</tD>
	</tr>	
	
	
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","SaveQuotaRootUser()")."</td>
	</tr>
	</table>
	</div>
	<script>
	
var x_SaveQuotaRootUser= function (obj) {
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);}
	YahooWin4Hide();
}
		

function EnableQutotaRott(){

		document.getElementById('block-softlimit').disabled=true;
		document.getElementById('block-hardlimit').disabled=true;
		document.getElementById('inode-softlimit').disabled=true;
		document.getElementById('inode-hardlimit').disabled=true;
		document.getElementById('GraceTime').disabled=true;
	if(document.getElementById('quota-enabled').checked){
		document.getElementById('block-softlimit').disabled=false;
		document.getElementById('block-hardlimit').disabled=false;
		document.getElementById('inode-softlimit').disabled=false;
		document.getElementById('inode-hardlimit').disabled=false;
		//document.getElementById('GraceTime').disabled=false;
	
	}

}
	
function SaveQuotaRootUser(){
  	 var XHR = new XHRConnection();
     XHR.appendData('ou','{$_GET["ou"]}');
     XHR.appendData('uid','{$_GET["uid"]}');
	 XHR.appendData('block-softlimit',document.getElementById('block-softlimit').value);
	 XHR.appendData('block-hardlimit',document.getElementById('block-hardlimit').value);
	 XHR.appendData('inode-softlimit',document.getElementById('inode-softlimit').value);
	 XHR.appendData('inode-hardlimit',document.getElementById('inode-hardlimit').value);
	 XHR.appendData('GraceTime',document.getElementById('GraceTime').value);  
	 if(document.getElementById('quota-enabled').checked){  XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
     AnimateDiv('$t');                                    		      	
     XHR.sendAndLoad('$page', 'POST',x_SaveQuotaRootUser);		  
}


EnableQutotaRott();
</script>	
	
	";
	
	
echo $tpl->_ENGINE_parse_body($html);	
}

function SaveInfos(){
	$q=new mysql();
	$_POST["uid"]=addslashes($_POST["uid"]);
	$sql="SELECT uid FROM quotaroot WHERE uid='{$_POST["uid"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));	
	
	if(!isset($_POST["GraceTime"])){$_POST["GraceTime"]=10080;}
	if(!is_numeric($_POST["GraceTime"])){$_POST["GraceTime"]=10080;}
	
	if($ligne["uid"]==null){
		$sql="INSERT INTO quotaroot (`uid`,`block-hardlimit`,`block-softlimit`,`inode-softlimit`,`inode-hardlimit`,`enabled`,`GraceTime`) VALUES
		('{$_POST["uid"]}','{$_POST["block-hardlimit"]}','{$_POST["block-softlimit"]}','{$_POST["inode-softlimit"]}','{$_POST["inode-hardlimit"]}','{$_POST["enabled"]}','{$_POST["GraceTime"]}')";
		
	}else{
		$sql="UPDATE quotaroot SET 
			`block-hardlimit`='{$_POST["block-hardlimit"]}',
			`block-softlimit`='{$_POST["block-softlimit"]}',
			`inode-hardlimit`='{$_POST["inode-hardlimit"]}',
			`inode-softlimit`='{$_POST["inode-softlimit"]}',
			`GraceTime`='{$_POST["GraceTime"]}',
			`enabled`='{$_POST["enabled"]}'
			WHERE `uid`='{$_POST["uid"]}'
			";
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\nMySQL Command:\n$sql\n";return;}
	$sock=new sockets();
	$sock->getFrameWork("services.php?setquotas=yes");
}


