<?php
session_start();
$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");


if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");die();}


BuildSessionAuth();
		
	if(isset($_GET["content"])){content();exit;}
	if(isset($_GET["main-content"])){main_content();exit;}
	if(isset($_GET["container-section"])){container_section();exit;}
	if(isset($_GET["container-search"])){container_search();exit;}
	
	if(isset($_GET["wizard-js"])){wizard_js();exit;}
	if(isset($_GET["wizard-popup"])){wizard_popup();exit;}
	if(isset($_POST["container_name"])){wizard_save();exit;}
	if(isset($_POST["delete-container"])){delete_container();exit;}
	
	if(isset($_GET["webdav-cmd-js"])){CMD_WEBDAV_JS();exit;}
	if(isset($_GET["webdav-cmd-popup"])){CMD_WEBDAV_POPUP();exit;}
	if(isset($_GET["webdav-cmd-download"])){CMD_WEBDAV_DOWNLOAD();exit;}
	
	if(isset($_GET["iscsi-cmd-js"])){CMD_ISCSI_JS();exit;}
	if(isset($_GET["iscsi-cmd-popup"])){CMD_ISCSI_POPUP();exit;}
	
	main_page();
	
	
function CMD_WEBDAV_JS(){
	header("content-type: application/x-javascript");
	
	$tpl=new templates();
	$page=CurrentPageName();
	$id=$_GET["ID"];
	if(!is_numeric($id)){$id=0;}
	$title=$tpl->javascript_parse_text("{windows_command_line}");
	if($id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT container_name FROM users_containers WHERE `container_id`='$id'","artica_backup"));
		$title=$tpl->javascript_parse_text("{windows_command_line}:: {$ligne["container_name"]}");
	}
	$html="YahooWin4('890','$page?webdav-cmd-popup=yes&ID=$id','$title')";
	echo $html;
}	

function CMD_ISCSI_JS(){
	header("content-type: application/x-javascript");
	
	$tpl=new templates();
	$page=CurrentPageName();
	$id=$_GET["ID"];
	if(!is_numeric($id)){$id=0;}
	$title=$tpl->javascript_parse_text("iSCSI");
	if($id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT container_name FROM users_containers WHERE `container_id`='$id'","artica_backup"));
		$title=$tpl->javascript_parse_text("iSCSI:: {$ligne["container_name"]}");
	}
	$html="YahooWin4('890','$page?iscsi-cmd-popup=yes&ID=$id','$title')";
	echo $html;	
	
}
	
function main_page(){
	$page=CurrentPageName();
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);
	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$html="
	<div class=BodyContent>
		<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a></div>
		<H1>{storage_area}</H1>
		<p>{endusers_storage_explain}</p>
	</div>	
	<div id='main-content'></div>
	
	<script>
		LoadAjax('main-content','$page?main-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}
function main_content(){
	$page=CurrentPageName();
	$boot=new boostrap_form();
	$tpl=new templates();
	$array=$_SESSION["BackupContainerPermissions"];
	$c=0;
	if(count($array)==0){senderror("no permission");}
	
	while (list ($gpid, $size) = each ($array) ){
		$c++;
		$sizetex=null;
		if($size>0){
			$SIZEUN="M";
			if($size>=1000){$size=$size/1000;$SIZEUN="G";}
			$sizetext="({$size}$SIZEUN)";}
		$gpidenc=urlencode($gpid);
		$arrayZ["{container} $c $sizetext"]="$page?container-section=yes&gpid=$gpidenc&size=$size";
		
	}
	
	echo $boot->build_tab($arrayZ);
	
}

function container_section(){
	$t=time();
	ini_set('display_errors', 1);
	ini_set('error_prepend_string',"<p class=text-error>");
	ini_set('error_append_string',"</p>\n");	
	$gpidenc=urlencode($_GET["gpid"]);
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$new_container=$tpl->_ENGINE_parse_body("{new_container}");
	$OPTIONS["BUTTONS"][]=button("$new_container","Loadjs('$page?wizard-js=yes&gpid=$gpidenc')",16);
	echo $boot->SearchFormGen("container_name","container-search","&gpid=$gpidenc",$OPTIONS);
	
	
	
}

function container_search(){
	$t=time();
	ini_set('display_errors', 1);
	ini_set('error_prepend_string',"<p class=text-error>");
	ini_set('error_append_string',"</p>\n");
	
	$q=new mysql();
	$users=new usersMenus();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$ORDER=$boot->TableOrder(array("container_name"=>"ASC"));
	$searchstring=string_to_flexquery("container-search");
	$table="users_containers";
	$gpidenc=urlencode($_GET["gpid"]);
	$sql="SELECT * FROM `$table` WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr>$sql</p>\n";}
	$available=$tpl->_ENGINE_parse_body("{available}");
	$used_text=$tpl->_ENGINE_parse_body("{used}");
	$error=$tpl->_ENGINE_parse_body("{error}");
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT maxsize FROM storage_containers WHERE `groupid`='".mysql_escape_string2($_GET["gpid"])."'","artica_backup"));
	$maxsize=$ligne["maxsize"];
	
	if($maxsize>0){
		$uidenc=mysql_escape_string2($_SESSION["uid"]);
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(container_size) as tSize FROM users_containers WHERE `uid`='$uidenc'","artica_backup"));
		$tSize=$ligne["tSize"];
		$prc=($tSize/$maxsize)*100;
		$status="
		<div style='width:100%;text-align:right;margin-top:25px'>{global_size}: ". pourcentage($prc)."
		</div>";
		
	}
	
	
	
	$iscsi_chap_secret_explain=$tpl->_ENGINE_parse_body("{iscsi_chap_secret_explain}");
	
	$delete_container_ask=$tpl->javascript_parse_text("{delete_container_ask}");
	$tr=array();
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$ID=$ligne["container_id"];
		$addtext=null;
		$md5=md5(serialize($ligne));
		$container_name=$ligne["container_name"];
		$container_size=$ligne["container_size"];
		$container_size_UNIT="MB";$per=null;$iscsi_error=null;
		$CMD_WEBDAV_ICON=null;
		$CMD_ISCSI_JS=null;
		$CMD_ISCSI_ICON=null;
		if($container_size>=1000){$container_size=$container_size/1000;$container_size_UNIT="GB";}
		$delete=imgsimple("delete-32.png",null,"Delete$t('$ID','$md5')");
		$color="black";$availble=null;
		$lock="<img src='img/lock.gif' style='float:right'>";
		$status_img="ok32-grey.png";
		if($ligne["created"]==0){
			$color="#C1C1C1";
			$addtext=$tpl->_ENGINE_parse_body("&nbsp;{building} {please_wait}...");
		}
		
		if($ligne["onerror"]==1){
			$color="#A10000";
			$addtext="&nbsp;$error:".$ligne["onerrortext"];
		}
		
		$statusA=unserialize(base64_decode($ligne["status"]));
		
		if(is_array($statusA)){
			if($statusA["MOUNTED"]<>null){$status_img="ok32.png";}
			$availble="&nbsp;$available:".FormatBytes($statusA["STATUS"]["AIVA"])." $used_text {$statusA["STATUS"]["POURC"]}%";
			$per=pourcentage($statusA["STATUS"]["POURC"]);
		}else{
			$addtext="&nbsp;No status...";
		}
		
		$webdav_creds=unserialize(base64_decode($ligne["webdav_creds"]));
		
		
		if($webdav_creds["username"]==null){
			$lock=null;
		}
		if($ligne["iscsid"]==1){
			$length=strlen($webdav_creds["password"]);
			if($length<12){$iscsi_error="<div style='color:#C50808'>$iscsi_chap_secret_explain</div>";}
			if($length>16){$iscsi_error="<div style='color:#C50808'>$iscsi_chap_secret_explain</div>";}
			$CMD_ISCSI_JS=$boot->trswitch("Loadjs('$page?iscsi-cmd-js=yes&ID=$ID')");
			$CMD_ISCSI_ICON="<img src='img/32-infos.png'>";
			
			////http://nas-appliance.org/index.php?cID=220
		}
		
		
		$trjs=$boot->trswitch("Loadjs('$page?wizard-js=yes&ID=$ID&gpid=$gpidenc')");
		
		if($ligne["webdav"]==1){
			$CMD_WEBDAV_ICON="<img src='img/cmd-32.png'>";
			$CMD_WEBDAV_JS=$boot->trswitch("Loadjs('$page?webdav-cmd-js=yes&ID=$ID')");
		}
		
		
		
		
		$tr[]="
		<tr id='$md5'>
		<td style='font-size:18px;color:$color' nowrap $trjs>{$lock}[disk{$ID}] $container_name ($container_size$container_size_UNIT) $addtext$availble$iscsi_error</td>
		<td style='font-size:18px;color:$color;text-align:center' nowrap width=1% $CMD_ISCSI_JS>$CMD_ISCSI_ICON</td>
		<td style='font-size:18px;color:$color;text-align:center' nowrap width=1% $CMD_WEBDAV_JS>$CMD_WEBDAV_ICON</td>
		<td style='font-size:18px;color:$color' nowrap width=1% $trjs><img src='img/$status_img'></td>
		<td style='font-size:18px;color:$color' nowrap width=1% $trjs>$per</td>
		<td style='font-size:18px;color:$color' nowrap width=1% >$delete</td>
		</tr>";
	
	}
	echo $status.$boot->TableCompile(
			array("container_name"=>"{name}",
				"5:no"=>"iSCSI",
				"3:no"=>"WebDav",
				"1:no"=>"{status}",
				"delete"=>"{delete}",
				"2:no"=>"",
			),
			$tr
			)."
				
<script>
var id$t='';
var xDelete$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#'+id$t).remove();
}
	
function Delete$t(ID,md){
	if(!confirm('$delete_container_ask')){return;}
	id$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('delete-container',ID);
	XHR.sendAndLoad('$page', 'POST',xDelete$t);
}
</script>";
}
function wizard_js(){
	header("content-type: application/x-javascript");
	$gpidenc=urlencode($_GET["gpid"]);
	$tpl=new templates();
	$page=CurrentPageName();
	$id=$_GET["ID"];
	if(!is_numeric($id)){$id=0;}
	$title=$tpl->javascript_parse_text("{new_container}");
	if($id>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT container_name FROM users_containers WHERE `container_id`='$id'","artica_backup"));
		$title=$tpl->javascript_parse_text("{container}:: {$ligne["container_name"]}");
	}
	
	$html="YahooWin4('700','$page?wizard-popup=yes&gpid=$gpidenc&ID=$id','$title')";
	echo $html;	
}

function wizard_popup(){
	
	$boot=new boostrap_form();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$sock=new sockets();
	$ApacheDisableModDavFS=$sock->GET_INFO("ApacheDisableModDavFS");
	if(!is_numeric($ApacheDisableModDavFS)){$ApacheDisableModDavFS=0;}
	$users=new usersMenus();
	
	
	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT maxsize FROM storage_containers WHERE `groupid`='".mysql_escape_string2($_GET["gpid"])."'","artica_backup"));
	$maxsize=$ligne["maxsize"];
	if($maxsize==0){
		$maxsizeText=$tpl->_ENGINE_parse_body("{unlimited} ");
	}else{$maxsizeText=$maxsize;}
	
	if($maxsize>0){
		if($ID==0){
			$uidenc=mysql_escape_string2($_SESSION["uid"]);
			$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT SUM(container_size) as tSize FROM users_containers WHERE `uid`='$uidenc'","artica_backup"));
			$tSize=$ligne["tSize"];
			if($tSize>=$maxsize){
				senderror("{error_quota_exceed}");
			}
		}
		
	}
	
	
	$ligne=array();
	
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM users_containers WHERE `container_id`='$ID'","artica_backup"));
		$boot->set_formtitle("{$ligne["container_name"]} ({$ligne["container_size"]}MB)");
	}
	
	if($ligne["container_name"]==null){$ligne["container_name"]=$tpl->_ENGINE_parse_body("{new_container}");}
	if($maxsize>0){
		if(!is_numeric($ligne["container_size"])){$ligne["container_size"]=$maxsize;}
	}
	$new_container_user_explain=$tpl->_ENGINE_parse_body("{new_container_user_explain}");
	$new_container_user_explain=str_replace("%S", $maxsize, $new_container_user_explain);
	if($ID==0){$boot->set_formdescription($new_container_user_explain);}
	$boot->set_hidden("gpid", $_GET["gpid"]);
	$boot->set_hidden("ID", $_GET["ID"]);
	$boot->set_field("container_name", "{container_name}", $ligne["container_name"],array("ENCODE"=>true));
	if($ID==0){
		$boot->set_field("container_size", "{container_size}", $ligne["container_size"],array("TOOLTIP"=>"{container_size_explain}"));
	}else{
		$boot->set_hidden("container_size", $ligne["container_size"]);
	}	
	$webdav_creds=unserialize(base64_decode($ligne["webdav_creds"]));
	
	
	if($webdav_creds["username"]==null){$webdav_creds["username"]=$_SESSION["uid"];}
	if($ApacheDisableModDavFS==0){
		$boot->set_checkbox("webdav", "{http_sharing}", $ligne["webdav"],array("TOOLTIP"=>"{container_http_sharing_explain}"));

	}
	
	if($users->ISCSI_INSTALLED){
		
		
		if(!is_numeric($ligne["iscsid"])){$ligne["iscsid"]=0;}
		$boot->set_checkbox("iscsid", "{network_disk}", $ligne["iscsid"],array("TOOLTIP"=>"{container_iscsid_sharing_explain}"));

	}
	
	$lengthpass=strlen($webdav_creds["password"]);
	$boot->set_field("webdav_username", "{username}", $webdav_creds["username"],array("ENCODED"=>true));
	$boot->set_fieldpassword("webdav_password", "{password} ($lengthpass chars)", $webdav_creds["password"],array("ENCODED"=>true));	
	if($ID==0){$boot->set_button("{create}");}else{$boot->set_button("{apply}");}
	$boot->set_RefreshSearchs();
	$boot->set_RefreshSearchsForced();
	echo $boot->Compile();	
	
}

function CMD_WEBDAV_POPUP(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$html="<H3>{windows_command_line}</H3>
	<p style='font-size:16px;margin:15px'>{windows_command_line_webdav_explain}</p>
	<center>". button("{download2}","MyHref('$page?webdav-cmd-download=yes&ID=$ID')")."</center>		
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function CMD_ISCSI_POPUP(){
	$ID=$_GET["ID"];
	$page=CurrentPageName();
	$users=new usersMenus();
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM users_containers WHERE `container_id`='$ID'","artica_backup"));
	
	$hostname=$users->hostname;
	$tbl=explode(".",$hostname);
	krsort($tbl);
	$newhostname=@implode(".",$tbl);
	$container_time=$ligne["container_time"];
	if(!is_numeric($container_time)){$container_time=0;}
	if($container_time==0){
		$container_time=time();
		$q->QUERY_SQL("UPDATE users_containers SET container_time=$container_time WHERE container_id=$ID","artica_backup");
	}
	
	$year=date("Y",$container_time);
	$month=date("m",$container_time);
	$lun="iqn.$year-$month.$newhostname:disk$ID";
	
	
	
	
	
	$html="<H3>{iscsi_howto_title}</H3>
	<p style='font-size:16px;margin:15px'>
	<table>
	<tr> 
		<td><strong>{server}:</td>
		<td><strong>{$_SERVER["SERVER_ADDR"]}</strong></td>
	</tr>	
	<tr> 
		<td><strong>{disk}:</td>
		<td><strong>disk{$ID}</strong></td>
	</tr>
	<tr> 
		<td><strong>LUN:</strong></td>
		<td><strong>$lun</strong></td>
	</tr>	
	</table>
	 </p>
	<p style='font-size:16px;margin:15px'>{iscsi_howto_title_explain}</p>
	<center>". button("Connecting Windows 7 to an iSCSI Artica based","MyHref('http://nas-appliance.org/index.php?cID=220')")."</center>
	";
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function CMD_WEBDAV_DOWNLOAD(){
	$ID=$_GET["ID"];
	$q=new mysql();
	$uid=urlencode($_SESSION["uid"]);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM users_containers WHERE `container_id`='$ID' AND uid='{$uid}'","artica_backup"));
	if($ligne["uid"]==null){die();}
	$webdav_creds=unserialize(base64_decode($ligne["webdav_creds"]));
	$f[]="@echo off";
	$f[]="@title ..:: Connecting to  {$ligne["container_name"]} ::..";
	$f[]="@cls";
	$f[]="@color f2";
	$f[]="net use * http://{$_SERVER["SERVER_ADDR"]}/disk{$ID} {$webdav_creds["password"]} /USER:{$webdav_creds["username"]}";
	$f[]="@echo.";
	$f[]="@echo. ";
	$f[]="";	
$data=@implode("\r\n", $f);
header('Content-type: application/octet-stream');
header('Content-Transfer-Encoding: binary');
header("Content-Disposition: attachment; filename=\"disk{$ID}.bat\"");
header("Pragma: public");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé
$fsize = strlen($data);
header("Content-Length: ".$fsize);
ob_clean();
flush();
echo 	$data;
	
}


function wizard_save(){
	$gpid=$_POST["gpid"];
	$ID=$_POST["ID"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT directory,maxsize FROM storage_containers WHERE `groupid`='$gpid'","artica_backup"));
	$directory=$ligne["directory"];
	if(!is_numeric($_POST["container_size"])){$_POST["container_size"]=$ligne["maxsize"];}
	if($_POST["container_size"]>$ligne["maxsize"]){$ligne["container_size"]=$ligne["maxsize"];}
	$container_name=mysql_escape_string2(url_decode_special_tool($_POST["container_name"]));
	$gpid=mysql_escape_string2($gpid);
	$container_size=$_POST["container_size"];
	$webdav=$_POST["webdav"];
	$webdav_creds["username"]=url_decode_special_tool($_POST["webdav_username"]);
	$webdav_creds["password"]=url_decode_special_tool($_POST["webdav_password"]);
	$webdav_creds=mysql_escape_string2(base64_encode(serialize($webdav_creds)));
	if(!is_numeric($webdav)){$webdav=0;}
	
	$iscsid=$_POST["iscsid"];
	if(!is_numeric($iscsid)){$iscsid=0;}
	if($iscsid==1){
		$sock=new sockets();
		$sock->SET_INFO("EnableISCSI",1);
	}
	
	if(!$q->FIELD_EXISTS("users_containers", "iscsid", "artica_backup")){
		$sql="ALTER TABLE `users_containers` ADD `iscsid` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( iscsid ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	if(!$q->FIELD_EXISTS("users_containers", "container_time", "artica_backup")){
		$sql="ALTER TABLE `users_containers` ADD `container_time` INT( 10 ) NOT NULL DEFAULT '0',ADD INDEX ( container_time ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	
	if($ID==0){
		$container_time=time();
		$sql="INSERT IGNORE INTO users_containers (container_name,groupid,container_size,uid,directory,webdav,webdav_creds,iscsid,container_time)
		VALUES('$container_name','$gpid','$container_size','{$_SESSION["uid"]}','$directory',$webdav,'$webdav_creds','$iscsid','$container_time')";
		
	}else{
		$sql="UPDATE users_containers SET container_name='$container_name', 
		webdav='$webdav', webdav_creds='$webdav_creds',iscsid=$iscsid WHERE container_id=$ID";
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	$sock=new sockets();
	$sock->getFrameWork("containers.php?build=yes");
}
function delete_container(){
	$sock=new sockets();
	$results=base64_decode($sock->getFrameWork("containers.php?delete-container={$_POST["delete-container"]}"));
	echo "success...\n";
	
}