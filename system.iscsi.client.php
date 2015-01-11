<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once("ressources/class.os.system.inc");
	include_once("ressources/class.lvm.org.inc");
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsSystemAdministrator){echo "alert('no privileges');";die();}
	
	
	if(isset($_GET["add"])){add_js();exit;}
	if(isset($_GET["add-popup"])){add_popup();exit;}
	if(isset($_GET["iscsi-search"])){add_search();exit;}
	if(isset($_GET["add-select"])){add_select_popup();exit;}
	if(isset($_POST["Params"])){add_select_sql();exit;}
	if(isset($_GET["iScsiClientDelete"])){delete_client();exit;}
	if(isset($_GET["iScsciReconnect"])){iScsciReconnect();exit;}
	
function add_js(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{add_iscsi_disk}");
	
	$html="YahooWin2('850','$page?add-popup=yes','$title');";
	echo $html;
	
}

function add_popup(){
	$page=CurrentPageName();
	$tpl=new templates();

	$html="<div class=text-info style='font-size:18px'>{add_iscsi_explain}</div>
	<hr>
	<center style='width:98%' class=form>
		<table style='width:100%'>
		<tr>
			<td class=legend nowrap style='font-size:22px'>{addr}:</td>
			<td>". Field_text("iscsi_search",null,"font-size:22px;font-weight:bold;width:350px",null,null,null,false,"SearchIscsiCheck(event)")."</td>
			<td width=1%>". button("{search}","SearchIscsi()",22)."</td>
		</tr>
		</table>	
	
		<div style='text-align:right;margin-bottom:10px;margin-top:10px'>". imgtootltip("32-refresh.png","{refresh}","SearchIscsi()")."</td>
		<div id='iscsi-search-list' style='width:100%;height:250px;overflow:auto;margin-top:15px'></div>
		
		
		<script>
			function SearchIscsiCheck(e){
				if(checkEnter(e)){SearchIscsi();}
			}
		
			function SearchIscsi(){
				LoadAjax('iscsi-search-list','$page?iscsi-search='+document.getElementById('iscsi_search').value);
			}
			
			
		var x_iScsiClientDelete=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			SearchIscsi();
		}		
		
		function iScsiClientDelete(ID){
			var XHR = new XHRConnection();
			XHR.appendData('iScsiClientDelete',ID);					
    		XHR.sendAndLoad('$page', 'GET',x_iScsiClientDelete);		
			}				
			
			
		SearchIscsi();
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function add_search(){
	// sudo iscsiadm --mode discovery --type sendtargets --portal 192.168.1.106
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();	
	$ip=trim($_GET["iscsi-search"]);
	$sock->getFrameWork("iscsi.php?iscsi-sessions=yes");
	
	if($ip<>null){
		$sock->getFrameWork("iscsi.php?iscsi-search=$ip");
		$array=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi-search.array"));
	}
	
	$array_sessions=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/iscsi-sessions.array"));
	
	while (list ($ip, $subarray) = each ($array_sessions)){
		while (list ($ip, $subarray2) = each ($subarray)){
			$ids="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
			$MSESSIONS[$ids]=true;
		}
		
	}
	
	
	
	$sql="SELECT ID,hostname,directory FROM iscsi_client";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){		
		$TABLE["{$ligne["hostname"]}:{$ligne["directory"]}"]=$ligne["ID"];
		
	}
	
		
	$html="<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
	<thead class='thead'>
		<tr>
			<th>&nbsp;</th>
			<th style='font-size:18px;padding:15px'>{port}</th>
			<th style='font-size:18px;padding:15px'>{disk}</th>
			<th style='font-size:18px;padding:15px'>{directory}</th>
			<th style='font-size:18px;padding:15px'>{status}</th>
			<th style='font-size:18px;padding:15px'>&nbsp;</th>
			<th style='font-size:18px;padding:15px'>&nbsp;</th>
		</tr>
	</thead>
	<tbody class='tbody'>";	

	if(is_array($array)){
		while (list ($ip, $subarray) = each ($array)){
			while (list ($ip, $subarray2) = each ($subarray)){
			if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$ID=null;
				$delete=null;
				$content=base64_encode(serialize($subarray2));
				$delete="&nbsp;";
				$ids="{$subarray2["ISCSI"]}:{$subarray2["PORT"]}:{$subarray2["FOLDER"]}";
				if(isset($TABLE[$ids])){
					if(is_numeric($TABLE[$ids])){
						$ID=$TABLE[$ids];
						$remove[$ID]=true;
						$delete=imgtootltip("delete-32.png","{delete}","iScsiClientDelete('$ID');");
					}
				}
				
				$stat="ok32-grey.png";
				if($MSESSIONS[$ids]){$stat="ok32.png";}
				
				$select=imgtootltip("arrow-blue-left-32.png","{select}<hr>{$subarray2["FOLDER"]}",
				"iscsciSelect('$content','{$subarray2["ISCSI"]}/{$subarray2["FOLDER"]}','$ID')");
				$html=$html . "
				<tr  class=$classtr>
					
					<td width=1%  align='center' nowrap><strong style='font-size:18px'>{$subarray2["ID"]}</strong></td>
					<td width=1% align='center' nowrap><strong style='font-size:18px'>{$subarray2["PORT"]}</strong></td>
					<td width=99% align='left'><strong style='font-size:18px'>{$subarray2["ISCSI"]}</strong><br><span style='font-size:12px'>{$subarray2["IP"]}</span></td>
					<td width=1% align='center' nowrap><strong style='font-size:18px'>{$subarray2["FOLDER"]}</strong></td>
					<td width=1% align='center' nowrap><img src='img/$stat'></td>
					<td width=1% align='center'>$select</td>
					<td width=1% align='center'>$delete</td>
				</tr>";		
			}		
				
				
			}
		}
		
	$sql="SELECT * FROM iscsi_client";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){echo "<H2>$q->mysql_error</H2>";}
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){		
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if($remove[$ligne["ID"]]){continue;}
		$subarray2=unserialize(base64_decode($ligne["Params"]));
		$select=imgtootltip("arrow-blue-left-32.png","{select}<hr>{$subarray2["FOLDER"]}",
		"iscsciSelect('$content','{$subarray2["ISCSI"]}/{$subarray2["FOLDER"]}','{$ligne["ID"]}')");
		$delete=imgtootltip("delete-32.png","{delete}","iScsiClientDelete('{$ligne["ID"]}');");
		$stat="ok32-grey.png";
		if($MSESSIONS[$ids]){$stat="ok32.png";}
		$html=$html . "
			<tr  class=$classtr>
				
				<td width=1% align='center' nowrap><strong style='font-size:18px'>{$ligne["ID"]}</strong></td>
				<td width=1% align='center' nowrap><strong style='font-size:18px'>{$subarray2["PORT"]}</strong></td>
				<td width=99% align='left'><strong style='font-size:18px'>{$subarray2["ISCSI"]}</strong></td>
				<td width=1% align='center' nowrap><strong style='font-size:18px'>{$subarray2["FOLDER"]}</strong></td>
				<td width=1% align='center'><img src='img/$stat'></td>
				<td width=1% align='center'>$select</td>
				<td width=1% align='center'>$delete</td>
			</tr>";		
	}			
		
	
			
		

$html=$html."</table>
<div style='text-align:right;width:100%;margin-top:15px'>". button("{connect}:{all}","iScsciReconnect()",18)."</td>

<script>
	function iscsciSelect(base,title,ID){
		YahooWin3Hide();
		YahooWin3('850','$page?add-select='+base+'&ID='+ID,title);
	
	}
	var x_iScsciReconnect=function (obj) {
		var results=obj.responseText;
		if(results.length>0){alert(results);return;}
		SearchIscsi();
	}		
		
	function iScsciReconnect(){
		var XHR = new XHRConnection();
		XHR.appendData('iScsciReconnect','yes');					
    	XHR.sendAndLoad('$page', 'GET',x_iScsciReconnect);		
	}	

</script>
";
echo $tpl->_ENGINE_parse_body($html);

	
}

function add_select_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$ID=$_GET["ID"];
	if(!is_numeric($ID)){$ID=0;}	
	if($ID>0){
		$sql="SELECT * FROM iscsi_client WHERE ID='{$_GET["ID"]}'";
		$q=new mysql();
		$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
		$subarray2=unserialize(base64_decode($ligne["Params"]));
	}
	
	
	if($ID==0){$subarray2=unserialize(base64_decode($_GET["add-select"]));}

	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	
		<tr>
			<td style='font-size:20px;' class=legend>{addr}:</td>
			<td style='font-size:20px;font-weight:bold'>{$subarray2["IP"]}:{$subarray2["PORT"]}</td>
		</tR>
		<tr>
			<td style='font-size:20px;' class=legend>{disk}:</td>
			<td style='font-size:20px;font-weight:bold'>{$subarray2["ISCSI"]}</td>
		</tR>
		<tr>
			<td style='font-size:20px;' class=legend>{directory}:</td>
			<td style='font-size:20px;font-weight:bold'>{$subarray2["FOLDER"]}</td>
		</tR>
		<tr>
			<td style='font-size:20px;' class=legend>{enable_authentication}:</td>
			<td style='font-size:20px;font-weight:bold'>". Field_checkbox_design("iscsi-EnableAuth",1,$ligne["EnableAuth"],"EnableAuthCCheck()")."</td>
		</tR>			
		<tr>
			<td style='font-size:20px;' class=legend nowrap>{username}:</td>
			<td style='font-size:20px;font-weight:bold'>". Field_text("iscsi-username",$ligne["username"],"font-size:20px;padding:3px;width:530px")."</td>
		</tR>
		<tr>
			<td style='font-size:20px;' class=legend>{password}:</td>
			<td style='font-size:20px;font-weight:bold'>". Field_password("iscsi-password",$ligne["password"],"font-size:20px;padding:3px;width:530px")."</td>
		</tR>		
		<tr>
			<td style='font-size:20px;' class=legend>{persistante_connection}:</td>
			<td style='font-size:20px;font-weight:bold'>". Field_checkbox_design("iscsi-persistante",1,$ligne["Persistante"])."</td>
		</tR>	
		<tr>
			<td colspan=2 align='right'><hr>". button("{connect}","iscsi_client_connect()",26)."</td>
		</tr>	
		</table>
	</div>	
	
	<script>
	
		function EnableAuthCCheck(){
			document.getElementById('iscsi-password').disabled=true;
			document.getElementById('iscsi-username').disabled=true;
			if(document.getElementById('iscsi-EnableAuth').checked){
				document.getElementById('iscsi-password').disabled=false;
				document.getElementById('iscsi-username').disabled=false;
			}			
		}

		
		var x_iscsi_client_connect=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);return;}
			YahooWin3Hide();
			SearchIscsi();
		}		
		
		function iscsi_client_connect(){
			var XHR = new XHRConnection();
			var password=document.getElementById('iscsi-password').value;
			XHR.appendData('username',document.getElementById('iscsi-username').value);
			XHR.appendData('password',password);
			XHR.appendData('Params','{$_GET["add-select"]}');
			XHR.appendData('ID','$ID');					
			if(document.getElementById('iscsi-persistante').checked){XHR.appendData('persistante',1);}else{XHR.appendData('persistante',0);}
			if(document.getElementById('iscsi-EnableAuth').checked){XHR.appendData('EnableAuth',1);}else{XHR.appendData('EnableAuth',0);}
    		XHR.sendAndLoad('$page', 'POST',x_iscsi_client_connect);		
			}	
	EnableAuthCCheck();
	</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function add_select_sql(){
	$subarray2=unserialize(base64_decode($_POST["Params"]));
	
	$ID=$_POST["ID"];
	if(!is_numeric($ID)){$ID=0;}
	$q=new mysql();
	
	if(!$q->FIELD_EXISTS("iscsi_client","EnableAuth","artica_backup")){
		$sql="ALTER TABLE `iscsi_client` ADD `EnableAuth` smallint(1) NOT NULL DEFAULT 1";
		$q->QUERY_SQL($sql,'artica_backup');
	}
	
	
	
	$sql="INSERT INTO iscsi_client(username,password,Params,hostname,directory,Persistante,EnableAuth)
	VALUES('{$_POST["username"]}','{$_POST["password"]}','{$_POST["Params"]}','{$subarray2["ISCSI"]}:{$subarray2["PORT"]}','{$subarray2["FOLDER"]}','{$_POST["persistante"]}','{$_POST["EnableAuth"]}')";
	
	$sql_edit="UPDATE `iscsi_client`
	SET `username`='{$_POST["username"]}',
	`password`='{$_POST["password"]}',
	`Persistante`='{$_POST["persistante"]}',
	`EnableAuth`='{$_POST["EnableAuth"]}'
	WHERE `ID`='{$_POST["ID"]}'
	";
	if($ID>0){$sql=$sql_edit;}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?iscsi-client=yes");	
}
function delete_client(){
	$ID=$_GET["iScsiClientDelete"];
	if(!is_numeric($ID)){$ID=0;}		
	$sql="DELETE FROM iscsi_client WHERE ID='$ID'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?iscsi-client=yes");
}
function iScsciReconnect(){
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?iscsi-client=yes");	
}


	
	
