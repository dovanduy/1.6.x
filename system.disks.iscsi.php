<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){
		$user=new usersMenus();
		if(!$user->AsSystemAdministrator){
			echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
			die();
		}
		if(!$user->ISCSID_INSTALLED){
			echo FATAL_ERROR_SHOW_128("{software_is_not_installed}");
			die();
		}
	}
	
	
	if(isset($_GET["status"])){section_status();exit;}
	if(isset($_GET["disks"])){section_disks();exit;}
	if(isset($_GET["item-js"])){item_js();exit;}
	if(isset($_GET["item-delete-js"])){item_delete_js();exit;}
	
	
	if(isset($_GET["iscsi-list"])){iscsi_list();exit;}
	if(isset($_POST["shared_folder"])){iscsi_save();exit;}
	if(isset($_POST["EnableISCSI"])){EnableISCSI();exit;}
	
	
	if(isset($_GET["popup-edit"])){iscsi_tabs();exit;}
	if(isset($_GET["popup-disk"])){iscsi_disk();exit;}
	if(isset($_GET["popup-params"])){iscsi_params();exit;}
	if(isset($_GET["ImmediateData"])){iscsi_params_save();exit;}
	
	
	if(isset($_GET["popup-security"])){iscsi_secu();exit;}
	if(isset($_POST["uid"])){iscsi_secu_save();exit;}
	
	if(isset($_GET["iscsi-status"])){iscsi_status();exit;}
	if(isset($_POST["iCsciDiskDelete"])){iscsi_disk_delete();exit;}
	tabs();

function tabs(){
	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$array["status"]='{status}';
	$array["disks"]='{disks}';
	$array["events"]='{events}';
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="events"){
			$html[]=$tpl->_ENGINE_parse_body("<li style='font-size:20px'><a href=\"syslog.php?popup=yes&prepend=ietd\"><span>$ligne</span></a></li>\n");
			continue;
		}
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:20px'><span>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_iscsi_master");
	
		
	
}

function iscsi_status(){
	$sock=new sockets();
	$tpl=new templates();
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork("cmd.php?iscsi-status=yes")));
	$status=DAEMON_STATUS_ROUND("APP_IETD",$ini,null,0);
	echo $tpl->_ENGINE_parse_body($status);		
}

function section_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$sock=new sockets();
	$EnableISCSI=intval($sock->GET_INFO("EnableISCSI"));
	$html="
	<table style='width:100%'>
	<tr>
		<td width='250px' valign='top'><div id='iscsi-status'></div></td>
		<td valign='top'>
			
			". Paragraphe_switch_img("{EnableISCSI}", "{iscsi_explain}","EnableISCSI",$EnableISCSI,null,850)."
			<hr>
			<div style='margin-top:20px;text-align:right'>". button("{apply}", "Save$t()",40)."</div>
			
			</td>
	</tr>
	</table>
	</div>
	
<script>
	function iscsi_status(){
		LoadAjax('iscsi-status','$page?iscsi-status=yes');
	
	}
	

	var xSave$t= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);}	
		Loadjs('system.disks.iscsi.progress.php');
		iscsi_status();
	}		
	
function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableISCSI',document.getElementById('EnableISCSI').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

	iscsi_status();
</script>
";

	echo $tpl->_ENGINE_parse_body($html);
	
}

function section_disks(){

	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$shared_folder=$tpl->_ENGINE_parse_body("{shared_folder}");
	$status=$tpl->javascript_parse_text("{status}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$ISCSI_share=$tpl->javascript_parse_text("{ISCSI_share}");
	$share_a_drive=$tpl->javascript_parse_text("{add_iscsi_disk}");
	$TABLE_WIDTH=705;
	$size=$tpl->javascript_parse_text("{size}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$buttons="
	buttons : [
		{name: '$share_a_drive', bclass: 'add', onpress : AddShared$t},
		{name: '$apply', bclass: 'apply', onpress : Apply$t},
	],";
	
	$html="
	<table class='ISCSI_TABLE1' style='display: none' id='ISCSI_TABLE1' style='width:100%;'></table>
<script>
var IDTMP=0;
$(document).ready(function(){
	$('#ISCSI_TABLE1').flexigrid({
	url: '$page?iscsi-list=yes',
	dataType: 'json',
	colModel : [
	{display: '$status', name : 'status', width :67, sortable : false, align: 'center'},
	{display: '$shared_folder', name : 'shared_folder', width :200, sortable : false, align: 'left'},
	{display: '$hostname', name : 'hostname', width :280, sortable : true, align: 'left'},
	{display: '$size', name : 'file_size', width :110, sortable : true, align: 'left'},
	{display: 'DEL', name : 'DEL', width : 70, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$shared_folder', name : 'shared_folder'},
	{display: '$hostname', name : 'hostname'},
	],	
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '<span style=font-size:18px>$ISCSI_share</span>',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
function btrfsSubdisk(uuid){
	LoadAjax('BRTFS_TABLE2','$page?uuid='+uuid);
}
function AddShared$t(){
	Loadjs('$page?item-js=yes&ID=0');
}

function Apply$t(){
	Loadjs('system.disks.iscsi.progress.php');
}

</script>
";
	
	echo $html;
	
	}

 

function item_js(){
	header("content-type: application/x-javascript");
	$ID=$_GET["ID"];
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{add_iscsi_disk}");
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT shared_folder,hostname FROM iscsi_params WHERE ID='$ID'","artica_backup"));
		$title=$tpl->javascript_parse_text($ligne["hostname"]."::".$ligne["shared_folder"]);

	}
	echo "YahooWin3('890','$page?popup-edit=yes&ID=$ID','$title');";

}

function item_delete_js(){
	header("content-type: application/x-javascript");
	$q=new mysql();
	$ID=$_GET["item-delete-js"];
	$page=CurrentPageName();
	$t=time();
	$tpl=new templates();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT type,dev,shared_folder,hostname FROM iscsi_params WHERE ID='$ID'","artica_backup"));
	$title=$tpl->javascript_parse_text($ligne["hostname"]."::".$ligne["shared_folder"]);
	$delete=$tpl->javascript_parse_text("{delete}");
	$type=$ligne["type"];
	$explain2=null;
	if($type=="file"){
		$explain2=$tpl->javascript_parse_text("{iscsi_delete_explain_file}");
		$explain2=str_replace("%p", $ligne["dev"], $explain2);
	}
	
	echo "
var xiCsciDiskDelete$t=function (obj) {
	var results=obj.responseText;
	if(results.length>0){alert(results);return;}
	$('#ISCSI_TABLE1').flexReload();
}

function iCsciDiskDelete$t(){
	if(!confirm('$delete $title ?\\n$explain2')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('iCsciDiskDelete','$ID');
    XHR.sendAndLoad('$page', 'POST',xiCsciDiskDelete$t);	
}			
			
			
	iCsciDiskDelete$t();";
	
}


function iscsi_tabs(){
	$ID=$_GET["ID"];
	$array["popup-disk"]='{disk}';
	if($ID>0){
		$array["popup-security"]='{security}';
		$array["popup-params"]='{parameters}';
		
		
	}
	$page=CurrentPageName();
	$tpl=new templates();
	
	while (list ($num, $ligne) = each ($array) ){
		$html[]= "<li><a href=\"$page?$num=yes&ID={$_GET["ID"]}\" style='font-size:18px'><span>$ligne</span></a></li>\n";
		}
	
	
	echo build_artica_tabs($html, "iscsid$ID");
		
	
	
}

function iscsi_params(){
	$sql="SELECT Params FROM iscsi_params WHERE ID='{$_GET["ID"]}'";
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));			
	$Params=unserialize(base64_decode($ligne["Params"]));
	
	if(!is_numeric($Params["MaxConnections"])){$Params["MaxConnections"]=1;}
	if(!is_numeric($Params["ImmediateData"])){$Params["ImmediateData"]=1;}
	if(!is_numeric($Params["Wthreads"])){$Params["Wthreads"]=8;}
	if($Params["IoType"]==null){$Params["IoType"]="fileio";}
	if($Params["mode"]==null){$Params["mode"]="wb";}

	$hashIoType=array("fileio"=>"{fileio}","blockio"=>"{blockio}");
	$hashMode=array("ro"=>"{ro}","wb"=>"{wb}");
	
	
	$html="
	<div id='SaveiscsciSettings-div'></div>
<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:22px'>{MaxConnections}:</td>
		<td>". Field_text("iscsi-MaxConnections",$Params["MaxConnections"],"font-size:22px;padding:3px;width:90px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend  style='font-size:22px'>{IoType}:</td>
		<td>". Field_array_Hash($hashIoType,"iscsi-IoType",$Params["IoType"],"style:font-size:22px;padding:3px;")."</td>
		<td>". help_icon("{iscsi_IoType_explain}")."</td>
	</tr>
	<tr>
		<td class=legend  style='font-size:22px'>{mode}:</td>
		<td>". Field_array_Hash($hashMode,"iscsi-mode",$Params["mode"],"style:font-size:22px;padding:3px;")."</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend  style='font-size:22px'>{ImmediateData}:</td>
		<td>". Field_checkbox_design("iscsi-ImmediateData",1,$Params["ImmediateData"])."</td>
		<td>". help_icon("{ImmediateData_explain}")."</td>
	</tr>	
	<tr>
		<td class=legend  style='font-size:22px'>{Wthreads}:</td>
		<td>". Field_text("iscsi-Wthreads",$Params["Wthreads"],"font-size:22px;padding:3px;width:90px")."</td>
		<td>". help_icon("{Wthreads_explain}")."</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>". button("{apply}","SaveiscsciSettings()",30)."</td>
	</tr>
	</table>
	<script>
	
		
		var x_SaveiscsciSettings=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			var ID={$_GET["ID"]};
			$('#ISCSI_TABLE1').flexReload();
			RefreshTab('iscsid{$_GET["ID"]}');
		}		
		
		function SaveiscsciSettings(){
			var ID={$_GET["ID"]};
			var XHR = new XHRConnection();
			XHR.appendData('ID',{$_GET["ID"]});
			if(document.getElementById('iscsi-ImmediateData').checked){XHR.appendData('ImmediateData',1);}else{XHR.appendData('ImmediateData',0);}
			XHR.appendData('Wthreads',document.getElementById('iscsi-Wthreads').value);
			XHR.appendData('mode',document.getElementById('iscsi-mode').value);
			XHR.appendData('iscsi-IoType',document.getElementById('iscsi-mode').value);
			XHR.appendData('MaxConnections',document.getElementById('iscsi-MaxConnections').value);
    		XHR.sendAndLoad('$page', 'GET',x_SaveiscsciSettings);		
			}	

	</script>
	
	";

	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function iscsi_params_save(){
	$sql="SELECT Params FROM iscsi_params WHERE ID='{$_GET["ID"]}'";
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
	$Params=unserialize(base64_decode($ligne["Params"]));
	while (list ($num, $ligne) = each ($_GET) ){
		$Params[$num]=$ligne;
	}
	
	$newParams=base64_encode(serialize($Params));
	$sql="UPDATE iscsi_params SET `Params`='$newParams' WHERE ID='{$_GET["ID"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	//$sock->getFrameWork("cmd.php?reload-iscsi=yes");	
}


function iscsi_secu(){
	$sql="SELECT * FROM iscsi_params WHERE ID='{$_GET["ID"]}'";
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql();
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td colspan=3>". Paragraphe_switch_img("{enable_authentication}", "{iscsi-secu-explain}",
				"iscsi-EnableAuth",$ligne["EnableAuth"],null,730,"EnableAuthCheck()")."
		</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:22px;'>{member}:</td>
		<td>". Field_text("iscsi-member",$ligne["uid"],"font-size:22px;padding:3px;width:320px")."</td>
		<td width=1%>
				". button("{browse}...","Loadjs('MembersBrowse.php?field-user=iscsi-member&OnlyUsers=1');").
				
				"</td>
	</tr>
	<tr>
		<td colspan=3 align='right'>
			<hr>
				". button("{apply}","SaveAuthParams()",30)."
		</td>
	</tr>
	</table>
	</div>
	<script>
		function EnableAuthCheck(){
			document.getElementById('iscsi-member').disabled=true;
			if(document.getElementById('iscsi-EnableAuth').value==1){
				document.getElementById('iscsi-member').disabled=false;
			}
		
		}
		
		var x_SaveAuthParams=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			var ID={$_GET["ID"]};
			RefreshTab('iscsid{$_GET["ID"]}');
		}		
		
		function SaveAuthParams(){
			var ID={$_GET["ID"]};
			var XHR = new XHRConnection();
			XHR.appendData('ID',{$_GET["ID"]});
			XHR.appendData('uid',document.getElementById('iscsi-member').value);
			XHR.appendData('iscsi-EnableAuth',document.getElementById('iscsi-EnableAuth').value);
    		XHR.sendAndLoad('$page', 'POST',x_SaveAuthParams);		
			}	

			EnableAuthCheck();
		</script>
		";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function iscsi_secu_save(){
	$sql="UPDATE iscsi_params SET `uid`='{$_POST["uid"]}',`EnableAuth`='{$_POST["EnableAuth"]}' WHERE ID={$_POST["ID"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	//$sock->getFrameWork("cmd.php?reload-iscsi=yes");
	
	
	
}

function iscsi_disk(){
	$sql="SELECT * FROM iscsi_params WHERE ID='{$_GET["ID"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$t=time();
	$button_text="{apply}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
	include_once 'ressources/usb.scan.inc';
	while (list ($num, $line) = each ($_GLOBAL["disks_list"])){
		if($num=="size (logical/physical)"){continue;}
		$ID_MODEL_2=$line["ID_MODEL_2"];
		$PARTITIONS=$line["PARTITIONS"];
		//print_r($line);
		if(is_array($PARTITIONS)){
			while (list ($dev, $part) = each ($PARTITIONS)){
				$MOUNTED=$part["MOUNTED"];
				if(strlen($MOUNTED)>20){$MOUNTED=substr($MOUNTED,0,17)."...";}
				$SIZE=$part["SIZE"];
				$TYPE=$part["TYPE"];
				if($TYPE==82){continue;}
				if($TYPE==5){continue;}
				$devname=basename($dev);
				$devs[$dev] ="($devname) $MOUNTED $SIZE";
			}
		}
	}
	
	$iscsar=array("disk"=>"{disk}","file"=>"{file}");
	$iscsarF=Field_array_Hash($iscsar,"iscsi-type",$ligne["type"],"ChangeIscsiType()",null,0,"font-size:32px;padding:10px");
	$devsF=Field_array_Hash($devs,"iscsi-part",$ligne["dev"],"style:font-size:22px;padding:3px");
	if($ligne["hostname"]==null){
		$users=new usersMenus();
		$ligne["hostname"]=$users->fqdn;
	}
	
	if($_GET["ID"]==0){$button_text="{add}";}
	
	if(!is_numeric($ligne["file_size"])){$ligne["file_size"]=5;}
	$html="
<div styl='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:22px'>{type}:</td>
		<td style='font-size:22px'>$iscsarF</td>
		<td width=1% style='font-size:22px'>". help_icon("{iscsi_type_edit_explain}")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{path}:</td>
		<td style='font-size:22px'>". Field_text("iscsi-path",$ligne["dev"],"font-size:22px;padding:3px;width:390px")."</td>
		<td width=1% style='font-size:22px'>".button_browse("iscsi-path")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{size}:</td>
		<td style='font-size:22px;'>". Field_text("iscsi-size",$ligne["file_size"],"font-size:22px;padding:3px;width:90px")."&nbsp;G</td>
		<td width=1%>&nbsp;</td>
	</tr>					
	<tr>
		<td class=legend style='font-size:22px'>{partition}:</td>
		<td>$devsF</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:22px'>{hostname}:</td>
		<td colspan=2>". Field_text("iscsi-hostname",$ligne["hostname"],"font-size:22px;padding:3px;width:490px")."</td>
		
	</tr>
	<tr>
		<td class=legend align='left' style='font-size:22px'>{shared_folder}:</td>
		<td colspan=2>". Field_text("iscsi-folder",$ligne["shared_folder"],"font-size:22px;padding:3px;width:490px")."</td>
</tr>			
<tr>
	<td colspan=3 align='right'><hr>
	". button("$button_text","SaveIscsi$t()",40)	."</td>
</tr>	
</table>
</div>
	<script>
		function ChangeIscsiType(){
			document.getElementById('iscsi-path').disabled=true;
			document.getElementById('iscsi-part').disabled=true;
			document.getElementById('iscsi-size').disabled=true;
			var type=document.getElementById('iscsi-type').value;
			if(type=='disk'){
				document.getElementById('iscsi-part').disabled=false;
			}
			if(type=='file'){
				document.getElementById('iscsi-path').disabled=false;
				document.getElementById('iscsi-size').disabled=false;
			}
		
		}
		
		var x_SaveIscsi$t=function (obj) {
			var results=obj.responseText;
			$('#ISCSI_TABLE1').flexReload();
			if(results.length>0){alert(results);return;}
			var ID={$_GET["ID"]};
			if(ID>0){	
				RefreshTab('iscsid{$_GET["ID"]}');
			}else{
				YahooWin3Hide();
			}
			
			
		}		
		
		function SaveIscsi$t(){
			var ID={$_GET["ID"]};
			var XHR = new XHRConnection();
			XHR.appendData('ID',{$_GET["ID"]});
			XHR.appendData('hostname',document.getElementById('iscsi-hostname').value);
			XHR.appendData('path',document.getElementById('iscsi-path').value);
			XHR.appendData('type',document.getElementById('iscsi-type').value);
			XHR.appendData('partition',document.getElementById('iscsi-part').value);
			XHR.appendData('file_size',document.getElementById('iscsi-size').value);
			XHR.appendData('shared_folder',document.getElementById('iscsi-folder').value);
    		XHR.sendAndLoad('$page', 'POST',x_SaveIscsi$t);		
		
		}
		
		function lock$t(){
			var ID={$_GET["ID"]};
			if(ID>0){ document.getElementById('iscsi-size').disabled=true;}
		}
		
		
	ChangeIscsiType();
	lock$t();
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
	
}

function iscsi_save(){
	$hostname=$_POST["hostname"];
	$type=$_POST["type"];
	$size=$_POST["file_size"];
	$ID=$_POST["ID"];
	$foldername=$_POST["shared_folder"];
	$foldername=strtolower($foldername);
	$foldername=replace_accents($foldername);
	$foldername=str_replace(" ","_",$foldername);
	$foldername=str_replace(".","-",$foldername);
	
	
	$q=new mysql();
	$tpl=new templates();
	if($ID==0){
		$sql="SELECT ID FROM iscsi_params WHERE shared_folder='$foldername'";
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if($ligne["ID"]>0){
			echo $tpl->javascript_parse_text("$foldername {ERROR_OBJECT_ALREADY_EXISTS}");
			return;
		}
	}
	
	
	if($foldername==null){$foldername=time();}
	if($type=='file'){$dev=$_POST["path"];}else{$dev=$_POST["partition"];}
	if(!is_numeric($size)){$size=5;}
	if(!is_numeric($ID)){$ID=0;}
	$sql="INSERT INTO iscsi_params (`hostname`,`dev`,`type`,`file_size`,`shared_folder`)
	VALUES('$hostname','$dev','$type','{$_POST["file_size"]}','$foldername')";
	
	$sqlu="UPDATE iscsi_params SET hostname='$hostname',`dev`='$dev',
	`type`='$type',`shared_folder`='$foldername' WHERE ID=$ID";
	
	if($ID>0){$sql=$sqlu;}
	
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	//$sock=new sockets();
	//$sock->getFrameWork("cmd.php?restart-iscsi=yes");	
	
	
}

function iscsi_list(){
	
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableISCSI=intval($sock->GET_INFO("EnableISCSI"));
	$q=new mysql();
	$t=$_GET["t"];
	$table="iscsi_params";
	
	$sock->getFrameWork("iscsi.php?volumes=yes");
	$f=explode("\n",@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/proc.net.iet.volume"));
	
	while (list ($num, $line) = each ($f) ){
		$line=trim($line);
		if(!preg_match("#^tid:([0-9]+)\s+name:iqn\.([0-9\-]+)\.(.+?):(.+)#", $line,$re)){
			continue;
		}
		$iqn_id=$re[1];
		$prefix=$re[2];
		$inversed_hostname=$re[3];
		$foldername=$re[4];
		$tt=explode(".",$inversed_hostname);
		krsort($tt);
		$newhost=@implode(".", $tt);
		$MASTER[$newhost][$foldername]["IQN"]=$iqn_id;
		
		
	}
	
	
	
	
	$searchstring=string_to_flexquery();
	$page=1;
	
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){ $ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}"; }}
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
	if($searchstring<>null){
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	
	}else{
		$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: <br>$q->mysql_error.<br>$sql",1);}
		$total = $ligne["tcount"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	if(!is_numeric($rp)){$rp=50;}
	
	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
	
	if(mysql_num_rows($results)==0){json_error_show("no data",1);}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	$fontsize="22";
	
	
	$free_text=$tpl->javascript_parse_text("{free}");
	$computers=$tpl->javascript_parse_text("{computers}");
	$overloaded_text=$tpl->javascript_parse_text("{overloaded}");
	$orders_text=$tpl->javascript_parse_text("{orders}");
	$directories_monitor=$tpl->javascript_parse_text("{directories_monitor}");
	$dns_destination=$tpl->javascript_parse_text("{direct_mode}");
	$all_others_domains=$tpl->javascript_parse_text("{all_others_domains}");
	while ($ligne = mysql_fetch_assoc($results)) {
		$LOGSWHY=array();
		$overloaded=null;
		$loadcolor="black";
		$StatHourColor="black";
		$size=$ligne["file_size"];
		$color="black";
		$hostname=$ligne["hostname"];
		$type=$ligne["type"];
		$shared_folder=$ligne["shared_folder"];
		$ID=$ligne["ID"];
		
		$icon_grey="ok32-grey.png";
		$icon_warning_32="warning32.png";
		$icon_red_32="32-red.png";
		$icon="ok-32.png";
		$icon_f=$icon_grey;
		$size_text="-";
		
		if($EnableISCSI==0){$color="#8a8a8a";}
		$styleHref=" style='font-size:{$fontsize}px;text-decoration:underline;color:$color'";
		$style=" style='font-size:{$fontsize}px;color:$color'";
		
		$urijs="Loadjs('$MyPage?item-js=yes&ID=$ID');";
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:$urijs\" $styleHref>";
	
		$orders=imgtootltip("48-settings.png",null,"Loadjs('artica-meta.menus.php?gpid={$ligne["ID"]}');");
		$delete=imgtootltip("delete-32.png",null,"Loadjs('$MyPage?item-delete-js={$ligne["ID"]}')");
	
		$up=imgsimple("arrow-up-32.png",null,"MoveSubRuleLinks$t('$zmd5','up')");
		$down=imgsimple("arrow-down-32.png",null,"MoveSubRuleLinks$t('$zmd5','down')");
		
		if(isset($MASTER[$hostname][$shared_folder])){
			$icon_f=$icon;
			
		}

		if($type=="file"){
			$size_text="{$size}G";
		}
	
		$cell=array();
		$cell[]="<span $style><img src='img/$icon_f'></a></span>";
		$cell[]="<span $style>$link$shared_folder ($type)</a></span>";
		$cell[]="<span $style>$link$hostname</a></span>";
		$cell[]="<span $style>$link$size_text</a></span>";
		$cell[]="<span $style>$delete</a></span>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => $cell
		);
	}
	
	
	echo json_encode($data);	
	
	
}


function iscsi_disk_delete(){
	$sql="DELETE FROM iscsi_params WHERE ID='{$_POST["iCsciDiskDelete"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	//$sock=new sockets();
	//$sock->getFrameWork("cmd.php?restart-iscsi=yes");
	
}



function EnableISCSI(){
	$sock=new sockets();
	$sock->SET_INFO("EnableISCSI",$_POST["EnableISCSI"]);
	
	
	
}