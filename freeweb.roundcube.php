<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["PLUGS"])){SaveRDCUBE_PLUGS();exit;}
	if(isset($_POST["rebuild-groupoffice"])){rebuild_group_office();exit;}
	if(isset($_POST["SMTP_SERVER"])){SaveRDCUBE();exit;}
	if(isset($_GET["plugin-list"])){pluginslist();exit;}
	
	if(isset($_GET["replic-js"])){replication_js();exit;}
	if(isset($_GET["replic-popup"])){replication_popup();exit;}
	if(isset($_POST["ENABLE_REPLIC"])){replication_save();exit;}
	
page();


function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$rebuild_groupware_warning=$tpl->javascript_parse_text("{rebuild_groupware_warning}");
	$t=time();
	$h=new vhosts();
	$hash=$h->listOfAvailableServices(true);
	$sql="SELECT groupware FROM freeweb WHERE servername='{$_GET["servername"]}'";
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$sock=new sockets();
	$ldap=new clladp();
	
	$users=new usersMenus();
	if($users->AsSystemAdministrator){
		$ous=$ldap->hash_get_ou(true);
		$OUFIELD=Field_array_Hash($ous, "NAB-OU-$t",$free->Params["ROUNDCUBE"]["NAB-OU"],"style:font-size:14px");
	}else{
		$OUFIELD="<span style='font-size:14px;font-weight:bold'>{$_SESSION["ou"]}<input type='hidden' id='NAB-OU-$t' value='{$_SESSION["ou"]}'></span>";
	}
	
	
	$EnableVirtualDomainsInMailBoxes=$sock->GET_INFO("EnableVirtualDomainsInMailBoxes");
	if(!is_numeric($EnableVirtualDomainsInMailBoxes)){$EnableVirtualDomainsInMailBoxes=0;}
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));		
	if($ligne["groupware"]<>null){
		$groupware_text="
		<table style='width:99%' class=form>
		<tr>
			<td width=1% valign='top'><img src='img/{$h->IMG_ARRAY_64[$ligne["groupware"]]}'></td>
			<td valign='top' width=99%>
				<div style='font-size:16px'>{current}:&nbsp;<strong>&laquo;&nbsp;{$hash[$ligne["groupware"]]}&nbsp;&raquo;</strong><hr>
					<i style='font-size:13px'>{{$h->TEXT_ARRAY[$ligne["groupware"]]["TEXT"]}}</i>
				</div>
			</td>
		</tr>
		</table>";
		
	}	
	
	$free=new freeweb($_GET["servername"]);
	$js="YahooWin4('650','mysql.browse.php?database={$free->mysql_database}&instance-id=$free->mysql_instance_id','&raquo;$free->mysql_database');";
	
	// javascript:LoadMysqlTables('rdcbe05261831');
	$SMTP_SERVER=$free->Params["ROUNDCUBE"]["SMTP_SERVER"];
	if($SMTP_SERVER==null){$SMTP_SERVER="127.0.0.1";}
	
	if(!isset($free->Params["ROUNDCUBE"]["SIEVE_SERVER"])){
		$free->Params["ROUNDCUBE"]["SIEVE_SERVER"]="127.0.0.1:2000";
	}
	
	
$html="<div style='width:100%' id='roundcubediv'>$groupware_text

<table style='width:99%' class=form>
<tbody>
<tr>
	<td class=legend style='font-size:14px'>MySQL {database}:</td>
	<td style='font-size:14px'>Instance $free->mysql_instance_id / <a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='text-decoration:underline'>$free->mysql_database</a>
	&nbsp;<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?replic-js=yes&servername={$_GET["servername"]}')\" style='text-decoration:underline'>{replication}</a>
	
	</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{smtp_server}:</td>
	<td style='font-size:14px'>". Field_text("RDCUBE_SMTP_SERVER-$t",$SMTP_SERVER,"font-size:14px;width:220px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{imap_server}:</td>
	<td style='font-size:14px'>". Field_text("RDCUBE_IMAP_SERVER-$t",$free->Params["ROUNDCUBE"]["IMAP_SERVER"],"font-size:14px;width:220px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>Sieve:</td>
	<td style='font-size:14px'>". Field_text("RDCUBE_SIEVE_SERVER-$t",$free->Params["ROUNDCUBE"]["SIEVE_SERVER"],"font-size:14px;width:220px")."</td>
</tr>			
<tr>
	<td class=legend style='font-size:14px'>{login_domain}:</td>
	<td style='font-size:14px'>". Field_text("username_domain-$t",$free->Params["ROUNDCUBE"]["username_domain"],"font-size:14px;width:220px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{default_domain}:</td>
	<td style='font-size:14px'>". Field_text("mail_domain-$t",$free->Params["ROUNDCUBE"]["mail_domain"],"font-size:14px;width:220px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{product_name}:</td>
	<td style='font-size:14px'>". Field_text("product_name-$t",$free->Params["ROUNDCUBE"]["product_name"],"font-size:14px;width:220px")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{global_addressbook}:</td>
	<td style='font-size:14px'>". Field_checkbox("ENABLE_NAB-$t",1,$free->Params["ROUNDCUBE"]["ENABLE_NAB"],"ENABLE_NAB_CHECK$t()")."</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{organization}:</td>
	<td style='font-size:14px'>$OUFIELD</td>
</tr>
<tr>
	<td class=legend style='font-size:14px'>{writeable}:</td>
	<td style='font-size:14px'>". Field_checkbox("WRITE_NAB-$t",1,$free->Params["ROUNDCUBE"]["WRITE_NAB"])."</td>
</tr>


<tr>
<td colspan=2><div style='text-align:right'><hr>". button("{apply}","SaveRDCUBESETTS()",16)."</div></td>
</tr>

</table>
<table class='table-$t' style='display: none' id='table-$t' style='width:100%;margin:-10px'></table>
</div>




</div>
	<script>
FreeWebIDMEM='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?plugin-list=yes&servername={$_GET["servername"]}&force-groupware={$_GET["force-groupware"]}&ForceInstanceZarafaID={$_GET["ForceInstanceZarafaID"]}&t=$t&tabzarafa={$_GET["tabzarafa"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
		{display: 'Plugins', name : 'Plugins', width :652, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'none1', width : 31, sortable : false, align: 'left'},
	],
	$buttons

	searchitems : [
		{display: 'Plugins', name : 'Plugins'},
		],
	sortname: 'servername',
	sortorder: 'desc',
	usepager: true,
	title: 'Plugins',
	useRp: false,
	rp: 50,
	showTableToggleBtn: false,
	width: 874,
	height: 200,
	singleSelect: true
	
	});   
});	
	
	
	
		var x_SaveRDCUBESETTS=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}

			if(document.getElementById('main_config_freewebedit')){
				RefreshTab('main_config_freewebedit');
			}
			
		}	
		
		var x_SaveRDCUBESETTSILENT=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}

			
			
		}			

		
		function PluginEnable(plug){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');
			XHR.appendData('PLUGS',plug);
			if(document.getElementById('plugin_'+plug).checked){XHR.appendData('plugin_'+plug,1);}else{XHR.appendData('plugin_'+plug,0);}
			XHR.sendAndLoad('$page', 'POST',x_SaveRDCUBESETTSILENT);
		}
	
	
		function SaveRDCUBESETTS(){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');
			XHR.appendData('SMTP_SERVER',document.getElementById('RDCUBE_SMTP_SERVER-$t').value);
			XHR.appendData('IMAP_SERVER',document.getElementById('RDCUBE_IMAP_SERVER-$t').value);
			XHR.appendData('SIEVE_SERVER',document.getElementById('RDCUBE_SIEVE_SERVER-$t').value);
			
			
			
			XHR.appendData('username_domain',document.getElementById('username_domain-$t').value);
			XHR.appendData('mail_domain',document.getElementById('mail_domain-$t').value);
			XHR.appendData('product_name',document.getElementById('product_name-$t').value);
			if(document.getElementById('ENABLE_NAB-$t').checked){XHR.appendData('ENABLE_NAB',1);}else{XHR.appendData('ENABLE_NAB',0);}
			if(document.getElementById('WRITE_NAB-$t').checked){XHR.appendData('WRITE_NAB',1);}else{XHR.appendData('WRITE_NAB',0);}
			XHR.appendData('NAB-OU',document.getElementById('NAB-OU-$t').value);
			
			
			AnimateDiv('roundcubediv');
			XHR.sendAndLoad('$page', 'POST',x_SaveRDCUBESETTS);
		}
	
		function EnableVirtualDomainsInMailBoxesCheck(){
			var EnableVirtualDomainsInMailBoxes=$EnableVirtualDomainsInMailBoxes;
			document.getElementById('username_domain-$t').disabled=true;
			if(EnableVirtualDomainsInMailBoxes==1){
				document.getElementById('username_domain-$t').disabled=false;
				document.getElementById('username_domain-$t').value='';
			}
		}
		
		function ENABLE_NAB_CHECK$t(){
			document.getElementById('NAB-OU-$t').disabled=true;
			document.getElementById('WRITE_NAB-$t').disabled=true;
			if(document.getElementById('ENABLE_NAB-$t').checked){
				document.getElementById('NAB-OU-$t').disabled=false;
				document.getElementById('WRITE_NAB-$t').disabled=false;

			}
		}
		
		EnableVirtualDomainsInMailBoxesCheck();
		ENABLE_NAB_CHECK$t();
	</script>
	
	";

	$tpl=new templates();
	$datas=$tpl->_ENGINE_parse_body($html);	
	echo $datas;	
	
	
	
}

function SaveRDCUBE_PLUGS(){
	$free=new freeweb($_POST["servername"]);
	$field="plugin_".$_POST["PLUGS"];
	$free->Params["ROUNDCUBE"][$field]=$_POST[$field];
	$free->SaveParams();

	$q=new mysql();
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='REBUILD_GROUPWARE' AND `servername`='{$_POST["servername"]}'";
	$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('REBUILD_GROUPWARE','{$_POST["servername"]}')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");		
	$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername={$_POST["servername"]}");			
}

function SaveRDCUBE(){
	$free=new freeweb($_POST["servername"]);
	$free->Params["ROUNDCUBE"]["SMTP_SERVER"]=$_POST["SMTP_SERVER"];
	$free->Params["ROUNDCUBE"]["IMAP_SERVER"]=$_POST["IMAP_SERVER"];
	$free->Params["ROUNDCUBE"]["SIEVE_SERVER"]=$_POST["SIEVE_SERVER"];
	$free->Params["ROUNDCUBE"]["username_domain"]=$_POST["username_domain"];
	$free->Params["ROUNDCUBE"]["mail_domain"]=$_POST["mail_domain"];
	$free->Params["ROUNDCUBE"]["product_name"]=$_POST["product_name"];
	$free->Params["ROUNDCUBE"]["ENABLE_NAB"]=$_POST["ENABLE_NAB"];
	$free->Params["ROUNDCUBE"]["NAB-OU"]=$_POST["NAB-OU"];
	$free->Params["ROUNDCUBE"]["WRITE_NAB"]=$_POST["WRITE_NAB"];
	
	
	
	$free->SaveParams();
	
	$q=new mysql();
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='REBUILD_GROUPWARE' AND `servername`='{$_POST["servername"]}'";
	$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('REBUILD_GROUPWARE','{$_POST["servername"]}')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");	
	$sock->getFrameWork("freeweb.php?rebuild-vhost=yes&servername={$_POST["servername"]}");			
	
	
}

function pluginslist(){

	$free=new freeweb($_GET["servername"]);
	$sock=new sockets();
	$plugins=unserialize(base64_decode($sock->getFrameWork("freeweb.php?rouncube-plugins=yes&servername={$_GET["servername"]}")));
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace("*", ".*?", $search);
		
	}
	$total =0;
	$data['page'] = 1;
	$data['total'] = 1;	
	
	while (list ($num, $plug) = each ($plugins) ){
		if(trim($plug)==null){continue;}

		
		
		$md5S=md5($plug);
			if($search<>null){if(!preg_match("#$search#", $plug)){continue;}}
		
			$c++;
			$data['rows'][] = array(
				'id' => $md5S,
				'cell' => array(
					"<img src='img/plugins-24.png'>", 
					"<strong style='font-size:16px;style='color:$color'>$plug</span>",
					
					Field_checkbox("plugin_$plug",1,$free->Params["ROUNDCUBE"]["plugin_$plug"],"PluginEnable('$plug')")
					)
				);		
		

		}
$data['total'] =$c;
	echo json_encode($data);
	
	
}


function rebuild_group_office(){
	$q=new mysql();
	$sql="SELECT ID FROM drupal_queue_orders WHERE `ORDER`='REBUILD_GROUPWARE' AND `servername`='{$_POST["servername"]}'";
	$ligneDrup=@mysql_fetch_array($q->QUERY_SQL($sql,'artica_backup'));	
	if(!is_numeric($ligneDrup["ID"])){$ligneDrup["ID"]=0;}
	if($ligneDrup["ID"]==0){
		$sql="INSERT INTO drupal_queue_orders(`ORDER`,`servername`) VALUES('REBUILD_GROUPWARE','{$_POST["servername"]}')";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
$sock=new sockets();
	$sock->getFrameWork("drupal.php?perform-orders=yes");		
}

function replication_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{$_GET["servername"]}::{replication}");
	echo "YahooWin6('550','$page?replic-popup=yes&servername={$_GET["servername"]}','$title')";
}

function replication_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_GET["servername"]);
	$bname="{apply}";
	$t=time();
	if(!is_numeric($free->Params["ROUNDCUBE"]["ARTICA_PORT"])){$free->Params["ROUNDCUBE"]["ARTICA_PORT"]=9000;}
	if($free->Params["ROUNDCUBE"]["ARTICA_ADMIN"]==null){$free->Params["ROUNDCUBE"]["ARTICA_ADMIN"]="Manager";}
	$html="
<center><div id='anime-$t'></div></center>
<table style='width:99%' class=form>
<tr>	
	<td class=legend style='font-size:14px' nowrap colspan=2 align='right'>
	<a href=\"javascript:blur();\" OnClick=\"javascript:s_PopUpFull('http://www.mail-appliance.org/index.php?cID=275','1024','900');\" style='font-weight:bold;text-decoration:underline'>{online_help}</a></strong></td>
<tr>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{enable}:</strong></td>
	<td align=left>". Field_checkbox("ENABLE_REPLIC-$t",1,$free->Params["ROUNDCUBE"]["ENABLE_REPLIC"],"replicCheck$t()")."</strong></td>
<tr>
<tr>	
	<td class=legend style='font-size:14px' nowrap>{artica_server_address}:</strong></td>
	<td align=left>". field_ipv4("ARTICA_HOST-$t",$free->Params["ROUNDCUBE"]["ARTICA_HOST"],'font-size:14px')."</strong></td>
<tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{artica_console_port}:</strong></td>
	<td align=left>". Field_text("ARTICA_PORT-$t",$free->Params["ROUNDCUBE"]["ARTICA_PORT"],"width:90px;font-size:14px","script:SaveArticaSrvCheck$t(event)")."</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{artica_manager}:</strong></td>
	<td align=left>". Field_text("ARTICA_ADMIN-$t",$free->Params["ROUNDCUBE"]["ARTICA_ADMIN"],"width:180px;font-size:14px","script:SaveArticaSrvCheck$t(event)")."</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{password}:</strong></td>
	<td align=left>". Field_password("ARTICA_PASSWORD-$t",$free->Params["ROUNDCUBE"]["ARTICA_PASSWORD"],"width:90px;font-size:14px","script:SaveArticaSrvCheck$t(event)")."</strong></td>
</tr>
<tr>
	<td class=legend style='font-size:14px' nowrap>{acl_dstdomain}:</strong></td>
	<td align=left>". Field_text("ARTICA_RMWEB-$t",$free->Params["ROUNDCUBE"]["ARTICA_RMWEB"],"width:280px;font-size:14px","script:SaveArticaSrvCheck$t(event)")."</strong></td>
</tr>
<tr>	
	<td colspan=2 align='right'><hr>". button("$bname","SaveArticaReplic$t();","18px")."</td>
</tr>
</table>

<script>

		
		function SaveArticaSrvCheck$t(e){
			
			if(checkEnter(e)){SaveArticaReplic$t();return;}
			
		}
		
		var x_SaveArticaReplic$t=function (obj) {
			var results=obj.responseText;
			document.getElementById('anime-$t').innerHTML='';
			if (results.length>0){alert(results);return;}
			
		}				
		
		function SaveArticaReplic$t(){
			var XHR = new XHRConnection();
			if(document.getElementById('ENABLE_REPLIC-$t').checked){XHR.appendData('ENABLE_REPLIC','1');}else{XHR.appendData('ENABLE_REPLIC','0');}
			XHR.appendData('ARTICA_PORT',document.getElementById('ARTICA_PORT-$t').value);
			XHR.appendData('ARTICA_HOST',document.getElementById('ARTICA_HOST-$t').value);
			XHR.appendData('ARTICA_ADMIN',document.getElementById('ARTICA_ADMIN-$t').value);
			XHR.appendData('ARTICA_RMWEB',document.getElementById('ARTICA_RMWEB-$t').value);
			XHR.appendData('servername','{$_GET["servername"]}');
			var pp=encodeURIComponent(document.getElementById('ARTICA_PASSWORD-$t').value);
			XHR.appendData('ARTICA_PASSWORD',pp);
			AnimateDiv('anime-$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveArticaReplic$t);
		
		}
		
		function replicCheck$t(){
			document.getElementById('ARTICA_PORT-$t').disabled=true;
			document.getElementById('ARTICA_ADMIN-$t').disabled=true;
			document.getElementById('ARTICA_PASSWORD-$t').disabled=true;
			document.getElementById('ARTICA_RMWEB-$t').disabled=true;
			
			
			if(document.getElementById('ENABLE_REPLIC-$t').checked){
				document.getElementById('ARTICA_PORT-$t').disabled=false;
				document.getElementById('ARTICA_ADMIN-$t').disabled=false;
				document.getElementById('ARTICA_PASSWORD-$t').disabled=false;
				document.getElementById('ARTICA_RMWEB-$t').disabled=false;			
			}
		}
		
		replicCheck$t();

</script>

";	
echo $tpl->_ENGINE_parse_body($html);	

	
}
function replication_save(){
	$free=new freeweb($_POST["servername"]);
	$_POST["ARTICA_PASSWORD"]=url_decode_special_tool($_POST["ARTICA_PASSWORD"]);
	$free->Params["ROUNDCUBE"]["ARTICA_PORT"]=$_POST["ARTICA_PORT"];
	$free->Params["ROUNDCUBE"]["ARTICA_ADMIN"]=$_POST["ARTICA_ADMIN"];
	$free->Params["ROUNDCUBE"]["ARTICA_PASSWORD"]=$_POST["ARTICA_PASSWORD"];
	$free->Params["ROUNDCUBE"]["ARTICA_HOST"]=$_POST["ARTICA_HOST"];
	$free->Params["ROUNDCUBE"]["ARTICA_RMWEB"]=$_POST["ARTICA_RMWEB"];
	$free->Params["ROUNDCUBE"]["ENABLE_REPLIC"]=$_POST["ENABLE_REPLIC"];		
	$free->SaveParams();
	$sock=new sockets();
	$sock->getFrameWork("freeweb.php?roudce-replic-host=yes&servername={$_POST["servername"]}");
}