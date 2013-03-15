<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ActiveDirectory.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.cron.inc');
	include_once('ressources/class.backup.inc');

	
	

	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["users-list"])){users_list();exit;}
	if(isset($_GET["var-export-js"])){var_export_js();exit;}
	if(isset($_GET["var-export-popup"])){var_export_popup();exit;}
	if(isset($_GET["var-export-tabs"])){var_export_tabs();exit;}
	if(isset($_GET["var-dump-ad-group"])){var_export_popup();exit;}
	if(isset($_GET["var-dump-ad-members"])){var_export_members();exit;}
	if(isset($_POST["field"])){prepare_connection();exit;}
	if(isset($_POST["dnenc"])){prepare_dnenc();exit;}
	
	
	
	js();	

function js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	$tt=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}");
	header("content-type: application/x-javascript");
	$ConnectionEnc=$_GET["ConnectionEnc"];
	if($ConnectionEnc<>null){$ConnectionEnc=urlencode($ConnectionEnc);}
	$html="
			
	var x_Prepare$tt= function (obj) {
		
		var results=obj.responseText;
		if(results.length==0){alert('Wrong form');return;}
		YahooSearchUser('677','$page?popup=yes&CallBack={$_GET["CallBack"]}&field-user={$_GET["field-user"]}&OnlyGroups={$_GET["OnlyGroups"]}&ADID={$_GET["ADID"]}&OnlyUsers={$_GET["OnlyUsers"]}&ConnectionEnc='+results,'$title');
		
	}	
	
	
		function Prepare$tt(){
				var ConnectionEnc='$ConnectionEnc';
				
				if(ConnectionEnc.length>5){
					YahooSearchUser('677','$page?popup=yes&CallBack={$_GET["CallBack"]}&field-user={$_GET["field-user"]}&OnlyGroups={$_GET["OnlyGroups"]}&ADID={$_GET["ADID"]}&OnlyUsers={$_GET["OnlyUsers"]}&ConnectionEnc='+ConnectionEnc,'$title');
					return;
				}
			
				var XHR = new XHRConnection();
				XHR.appendData('field', '{$_GET["field"]}');
				if(document.getElementById('LDAP_SERVER-$tt')){
					XHR.appendData('LDAP_SERVER', encodeURIComponent(document.getElementById('LDAP_SERVER-$tt').value));
				}
				if(document.getElementById('LDAP_PORT-$tt')){
					XHR.appendData('LDAP_PORT', encodeURIComponent(document.getElementById('LDAP_PORT-$tt').value));
				}
				if(document.getElementById('LDAP_SUFFIX-$tt')){
					XHR.appendData('LDAP_SUFFIX', encodeURIComponent(document.getElementById('LDAP_SUFFIX-$tt').value));
				}
				
				if(document.getElementById('LDAP_DN-$tt')){
					XHR.appendData('LDAP_DN', encodeURIComponent(document.getElementById('LDAP_DN-$tt').value));
				}
				if(document.getElementById('LDAP_PASSWORD-$tt')){
					XHR.appendData('LDAP_PASSWORD', encodeURIComponent(document.getElementById('LDAP_PASSWORD-$tt').value));
				}
				if(document.getElementById('GROUP_ATTRIBUTE-$tt')){
					XHR.appendData('GROUP_ATTRIBUTE', encodeURIComponent(document.getElementById('GROUP_ATTRIBUTE-$tt').value));
				}	

				if(document.getElementById('LDAP_FILTER-$tt')){
					XHR.appendData('LDAP_FILTER', encodeURIComponent(document.getElementById('LDAP_FILTER-$tt').value));
				}					
				 
				
				XHR.sendAndLoad('$page', 'POST',x_Prepare$tt);
		}
	
	Prepare$tt();";
	
	echo $html;
	
}

function prepare_connection(){
	if($_POST["LDAP_DN"]==null){return null;}
	if($_POST["LDAP_SERVER"]==null){return null;}
	if($_POST["LDAP_PASSWORD"]==null){return null;}
	while (list ($num, $line) = each ($_POST)){
		$ligne[$num]=url_decode_special_tool($_POST[$num]);
	}
	
	echo urlencode(base64_encode(serialize($ligne)));
}

function prepare_dnenc(){
	echo base64_decode(url_decode_special_tool($_POST["dnenc"]));
	
}


function UsersBrowse_js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}::{$_GET["GroupName"]}");
	$html="YahooLogWatcher('550','$page?UsersGroup-popup=yes&dn={$_GET["dn"]}&ADID={$_GET["ADID"]}','$title');";
	echo $html;
	
}

function var_export_js(){
	if(!is_numeric($_GET["ADID"])){$_GET["ADID"]=0;}
	if(!is_numeric($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}::DUMP::{$_GET["cn"]}");
	$html="YahooWinS('874','$page?var-export-tabs=yes&data={$_GET["var-export-js"]}&ADID={$_GET["ADID"]}','$title');";
	echo $html;	
	
}

function var_export_tabs(){
	
	$t=$_GET["t"];
	$tpl=new templates();
	$page=CurrentPageName();
	$array["var-dump-ad-group"]='{group}';
	$array["var-dump-ad-members"]='{members}';
	$t=$_GET["t"];
	

	
	
	if(!is_numeric($t)){$t=time();}
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&data={$_GET["data"]}&ADID={$_GET["ADID"]}\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_ad_dump_group style='width:100%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_ad_dump_group').tabs();
			
			
			});
		</script>";		
	
	
	
}


function var_export_popup($array=null,$return=false){
	if(!is_array($array)){
		$dn=base64_decode($_GET["data"]);
		$dnText=utf8_decode($dn);
		$ad=new ActiveDirectory($_GET["ADID"]);
		$array=$ad->DumpDN($dn);
		if($ad->ldap_last_error<>null){
			echo "<div style='color:red;font-size:12px'>$ad->ldap_last_error<hr></div>";
		}
	}else{
		if($dnText==null){
			$dn=base64_decode($_GET["data"]);
			$dnText=utf8_decode($dn);	
		}
		
		
	}
	
	
	
	if(!is_array($array)){
		echo "<div style='color:red;font-size:12px'>Not an Array()<hr>".base64_decode($_GET["data"])."<hr></div>";
	}
	$html="<table>";
	while (list ($num, $ligne) = each ($array) ){
		
		
		
		if(is_array($ligne)){
			$ligne=var_export_popup($ligne,true);
		}else{
			$ligne=utf8_decode($ligne);
			$ligne=htmlentities($ligne);
			$ligne=str_replace("'", "`", $ligne);
		}
		
		$html=$html."
		<tr>
			<td class=legend style='font-size:13px' valign='top'>$num:</td>
			<td style='font-size:13px'><strong>$ligne</strong></td>
		</tr>
		
		";
		
		
	}
	
	$html=$html."</table>";
	if($return){return $html;}
	echo "
	<div style='font-size:16px'>DN:$dnText</div>
	<div style='width:95%;height:450px;overflow:auto' class=form>$html</div>";
	
	
}

function var_export_members(){
	
	if(!is_array($array)){
			$dn=base64_decode($_GET["data"]);
			$dnText=utf8_decode($dn);
			$ad=new ActiveDirectory($_GET["ADID"]);
			if($ad->ldap_last_error<>null){
				echo "<div style='color:red;font-size:12px'>$ad->ldap_last_error<hr></div>";
			}	
		
		}
		
		//$link_identifier, $base_dn, $filter, array $attributes = null, $attrsonly = null, $sizelimit = null, $timelimit = null, $deref = null
		if(!is_numeric($entriesNumber)){$entriesNumber=50;}
		$res=@ldap_read($ad->ldap_connection,$dn,"(objectClass=*)",array("member","MemberOf"),null,$entriesNumber,20);
		
		$log[]="Parse DN: $dn for member, MemberOf";
		
		
		
		if(!$res){
			$log[]='Error LDAP search number ' . ldap_errno($ad->ldap_connection) . "\nAction:LDAP search\ndn:$this->suffix\n$filter\n" . 
			ldap_err2str(ldap_errno($ad->ldap_connection));
			echo @implode("<br>", $log);
			return array();
		}
		
		
		$hash=ldap_get_entries($ad->ldap_connection,$res);
		$log[]="Attribute member =".$hash[0]["member"]["count"];		
		
		
		
		
	for($i=0;$i<$hash[0]["member"]["count"];$i++){
			$dn=$hash[0]["member"][$i];
			$log[]="Found dn = &laquo;$dn&raquo;";
			if($dn==null){continue;}
			$log[]="Dump dn = &laquo;$dn&raquo;";
			$Props=$ad->DumpDN($dn);
			if(!is_array($Props)){continue;}
			
			$html=$html."<table style='width:99%' class=form>
			<tr>
				<td colspan=2 style='font-size:16px;'> &laquo;$dn&raquo;</td>
			</tr>
			";
			
			while (list ($num, $ligne) = each ($Props) ){	
				if(is_array($ligne)){
					$ligne=var_export_popup($ligne,true);
				}else{
					$ligne=utf8_decode($ligne);
					$ligne=htmlentities($ligne);
					$ligne=str_replace("'", "`", $ligne);
				}
				
				$html=$html."
				<tr>
					<td class=legend style='font-size:13px' valign='top'>$num:</td>
					<td style='font-size:13px'><strong>$ligne</strong></td>
				</tr>
				
				";
			}
			
			$html=$html."</table>";
	}

	
	echo "</div style='font-size:12px'><code>".@implode("<br>", $log)."</div>$html"; 
		

}

function popup(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$OnlyGroups=$_GET["OnlyGroups"];
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	$OnlyUsers=$_GET["OnlyUsers"];
	$groups=$tpl->_ENGINE_parse_body("{adgroups}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$title=$tpl->_ENGINE_parse_body("{browse_active_directory_members}");
	$field=$_GET["field-user"];
	$groupsSearch="{display: '$groups', name : 'adgroups'},";
	$memberssearch="{display: '$members', name : 'members'},";
	if($OnlyGroups==1){$memberssearch=null;}
	if($OnlyUsers==1){$groupsSearch=null;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=1;}
	$ConnectionEnc=urlencode($_GET["ConnectionEnc"]);
	
	$Array=unserialize(base64_decode($ConnectionEnc));
	$LDAP_SUFFIX=$Array["LDAP_SUFFIX"];
	$html="
	
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?users-list=yes&CallBack={$_GET["CallBack"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyUsers={$_GET["OnlyUsers"]}&field-user={$_GET["field-user"]}&t=$t&ConnectionEnc=$ConnectionEnc',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zDate', width :31, sortable : false, align: 'left'},
		{display: '$members', name : 'members', width : 528, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'select', width : 31, sortable : false, align: 'left'},
		],
	
	searchitems : [
		$groupsSearch
		$memberssearch
		
		],
	sortname: 'members',
	sortorder: 'desc',
	usepager: true,
	useRp: true,
	title: '$LDAP_SUFFIX',
	rp: 50,
	showTableToggleBtn: false,
	width: 652,
	height: 420,
	singleSelect: true,
	rpOptions: [50,100,200,500,1000]
	
	});   
});

	function xPutDN$t(dn){
		var field='$field';
		if(field.length==0){return;}
		if(document.getElementById('$field')){
			document.getElementById('$field').value=dn;
			YahooSearchUserHide();
			YahooLogWatcherHide();
		}
	
	}
	

	var xPutDN$t= function (obj) {
		var results=obj.responseText;
		if(results.length==0){alert('Wrong selection');return;}
		var field='$field';
		if(field.length==0){alert('No field set');return;}
		if(document.getElementById('$field')){
			document.getElementById('$field').value=results;
			YahooSearchUserHide();
			YahooLogWatcherHide();
		}
		
	}	
	
	
		function PutDN$t(dnenc){
			var XHR = new XHRConnection();
			XHR.appendData('dnenc', encodeURIComponent(dnenc));
			XHR.sendAndLoad('$page', 'POST',xPutDN$t);
		}	
	
	
</script>
	
	
	";
	
	echo $html;
	

	
	
}

function users_list(){
	$tpl=new templates();
	$CurPage=CurrentPageName();
	$search=$_POST["query"];
	
	$t=$_GET["t"];
	$ad=new external_ad_search($_GET["ConnectionEnc"]);
	if(!is_numeric($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	$icon="user7-32.png";
	
	
	if($_GET["OnlyGroups"]==1){
		$OnlyGroups=1;
		$icon="win7groups-32.png";
		$Array=$ad->flexRTGroups($search,$_POST["rp"]);
		if($ad->error<>null){json_error_show($ad->error,1);}
	}
	


	if($_GET["OnlyUsers"]==1){
		$OnlyGroups=0;
		$icon="win7groups-32.png";
		$Array=$ad->flexRTUsers($search,$_POST["rp"]);
		if($ad->error<>null){json_error_show($ad->error,1);}
	}	
	
	
	if(count($Array)==0){json_error_show("No item",1);}
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($Array);
	$data['rows'] = array();	
	$members=$tpl->_ENGINE_parse_body("{members}");
	
	while (list ($dn, $itemname) = each ($Array) ){
		$GroupxSourceName=$itemname;
		$GroupxName=$itemname;
		$GroupxName=replace_accents($GroupxName);
		$GroupxName=str_replace("'", "`", $itemname);
		$link="<span style='font-size:14px;'>";
		$addtitile=null;
		$select=null;			
		$dn_enc=base64_encode($dn);
		$itemnameenc=base64_encode($itemname);
		
		$image=imgsimple($icon,null,"PutDN$t('$dn_enc')");
		$select=imgsimple("arrow-right-24.png",null,"PutDN$t('$dn_enc')");
		if($_GET["CallBack"]<>null){
			$select=imgsimple("arrow-right-24.png",null,"YahooSearchUserHide();{$_GET["CallBack"]}('$dn_enc','$itemnameenc')");
			$image=imgsimple($icon,null,"YahooSearchUserHide();{$_GET["CallBack"]}('$dn_enc','$itemnameenc')");
		}
		
		
		

		
		
		$md5=md5($dn);
		$data['rows'][] = array(
			'id' => $md5,
			'cell' => array(
				$image,
				"<span style='font-size:14px;'>$link$GroupxName</a>",
				$select )
			);		
	}
	
	
	echo json_encode($data);	
}


//BrowseActiveDirectory.php