<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	if(posix_getuid()==0){die();}
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["parameters"])){parameters_main();exit;}
	if(isset($_GET["popup-settings"])){parameters_main();exit;}
	if(isset($_GET["EnableSSLBump"])){parameters_enable_save();exit;}
	if(isset($_GET["whitelist"])){whitelist_popup();exit;}
	if(isset($_GET["whitelist-list"])){whitelist_list();exit;}
	if(isset($_GET["website_ssl_wl"])){whitelist_add();exit;}
	if(isset($_GET["website_ssl_eble"])){whitelist_enabled();exit;}
	if(isset($_GET["website_ssl_del"])){whitelist_del();exit;}
	
	if(isset($_GET["add-params"])){parameters_main();exit;}
	
	
	js();
	
	
function js() {

	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{squid_sslbump}");
	$page=CurrentPageName();
	
	$start="SSLBUMP_START()";
	if(isset($_GET["in-front-ajax"])){$start="SSLBUMP_START2()";}
	
	$html="
	
	function SSLBUMP_START(){YahooWin2('650','$page?popup=yes','$title');}
	
	function SSLBUMP_START2(){
		$('#BodyContent').load('$page?popup=yes');}		
	

	
	$start;
	";
	
	echo $html;	
	
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$array["parameters"]='{global_parameters}';
	$array["whitelist"]='{whitelist}';
	$array["http-safe-ports-ssl"]=$tpl->_ENGINE_parse_body('{http_safe_ports} (SSL)');
	//$array["popup-bandwith"]='{bandwith}';
	

	while (list ($num, $ligne) = each ($array) ){
		if($num=="http-safe-ports-ssl"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"squid.advParameters.php?http-safe-ports-ssl=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
			continue;
		}
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n");
	}
	
	
	echo "
	<div id=main_config_sslbump style='width:100%;'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_config_sslbump').tabs({
				    load: function(event, ui) {
				        $('a', ui.panel).click(function() {
				            $(ui.panel).load(this.href);
				            return false;
				        });
				    }
				});
			
			
			});
		</script>";	
}


function parameters_main(){
$sock=new sockets();
$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}	
	$squid=new squidbee();
	$page=CurrentPageName();
	$sslbumb=false;
	$users=new usersMenus();
	$t=time();
	if(preg_match("#^([0-9]+)\.([0-9]+)#",$users->SQUID_VERSION,$re)){
		
	    	if($re[1]>=3){if($re[2]>=1){$sslbumb=true;}}}
		
		$enableSSLBump=Paragraphe_switch_img("{activate_ssl_bump}",
	"{activate_ssl_bump_text}","EnableSSLBump-$t",$squid->SSL_BUMP,null,450);
		
    if(!is_numeric($squid->ssl_port)){$squid->ssl_port =$squid->listen_port+5;}		
	if($squid->ssl_port<3){$squid->ssl_port =$squid->listen_port+5;}	
	if($EnableRemoteStatisticsAppliance==0){
		if(!$sslbumb){$enableSSLBump=Paragraphe_switch_disable("{wrong_squid_version}: &laquo;$users->SQUID_VERSION&raquo;","{wrong_squid_version_feature_text}",null,450);}
	}
	$html="
	<div style='font-size:14px' id='sslbumpdiv$t'></div>
	<table style='width:99%' class=form>
	<tr>
		<td colspan=2>$enableSSLBump</td>
	</tr>
	<tr>
		<td style='font-size:14px' class=legend>{ssl_port}:</td>
		<td><a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.popups.php?script=listen_port')\"
			style='font-size:14px;font-weight:bold;text-decoration:underline'>
			$squid->ssl_port</td>
	</tr>
	<tr>
		<td style='font-size:14px' class=legend>{whitelist_all_domains}:</td>
		<td>". Field_checkbox("SSL_BUMP_WHITE_LIST-$t",1,$squid->SSL_BUMP_WHITE_LIST)."</td>
	</tr>
	
	</table>
	<hr>
	<div style='text-align:right'>". button("{apply}","SaveEnableSSLDump()",16)."</div>
	
	<script>
		var x_SaveEnableSSLDump=function(obj){
     	 var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
     	document.getElementById('sslbumpdiv$t').innerHTML='';
     	Loadjs('squid.restart.php?onlySquid=yes');
     	RefreshTab('main_config_sslbump');
	 }	

	function SaveEnableSSLDump(){
		var XHR = new XHRConnection();
		if(!document.getElementById('EnableSSLBump-$t')){return;}
		XHR.appendData('EnableSSLBump',document.getElementById('EnableSSLBump-$t').value);
		if(document.getElementById('SSL_BUMP_WHITE_LIST-$t').checked){XHR.appendData('SSL_BUMP_WHITE_LIST',1);}else{XHR.appendData('SSL_BUMP_WHITE_LIST',0);}
		AnimateDiv('sslbumpdiv$t');
		XHR.sendAndLoad('$page', 'GET',x_SaveEnableSSLDump);		
	
	}
	</script>
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
}

function parameters_enable_save(){
	$squid=new squidbee();
	$tpl=new templates();

	
	$squid->SSL_BUMP=$_GET["EnableSSLBump"];
	if($_GET["EnableSSLBump"]==1){
		if(!is_numeric($squid->ssl_port)){$squid->ssl_port=$squid->listen_port+10;}
		if($squid->ssl_port==443){$squid->ssl_port=$squid->listen_port+10;}	
	
	}
	
	
	$squid->SSL_BUMP_WHITE_LIST=$_GET["SSL_BUMP_WHITE_LIST"];
	$squid->SaveToLdap(true);

	
}

function whitelist_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$new_webiste=$tpl->_ENGINE_parse_body("{new_website}");
	$email=$tpl->_ENGINE_parse_body("{email}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$delete_this_member_ask=$tpl->javascript_parse_text("{delete_this_member_ask}");
	$SSL_BUMP_WL=$tpl->_ENGINE_parse_body("{SSL_BUMP_WL}");
	$website_ssl_wl_help=$tpl->javascript_parse_text("{website_ssl_wl_help}");
	$parameters=$tpl->javascript_parse_text("{parameters}");
	
	$squid=new squidbee();
	if($squid->hasProxyTransparent==1){
		$explain="<div style='font-weight:bold;color:#BD0000'>{sslbum_wl_not_supported_transp}</div>";
	}
	
	//$q=new mysql_squid_builder();
	//$q->QUERY_SQL("ALTER TABLE `usersisp` ADD UNIQUE (`email`)");
	
	$buttons="
	buttons : [
	{name: '<b>$new_webiste</b>', bclass: 'Add', onpress : sslBumbAddwl},
	{name: '<b>$parameters</b>', bclass: 'Reconf', onpress : sslBumSettings},$bt_enable
	],";	
	
$html="
<div class=explain style='font-size:13px'>$SSL_BUMP_WL$explain</div>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
row_id='';
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?whitelist-list=yes&t=$t',
	dataType: 'json',
	colModel : [
		{display: '$website_name', name : 'website_name', width : 474, sortable : false, align: 'left'},	
		{display: '$enabled', name : 'enabled', width : 31, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'center'},
		],
	$buttons
	searchitems : [
		{display: '$website_name', name : 'website_name'},
		],
	sortname: 'website_name',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 590,
	height: 310,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

	var x_sslBumbAddwl=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
     	$('#flexRT$t').flexReload();
     	}	
      
     function sslBumbAddwlCheck(e){
    	if(checkEnter(e)){sslBumbAddwl();} 
		}

	function sslBumbAddwl(){
		var www=prompt('$website_ssl_wl_help');
		if(www){
			var XHR = new XHRConnection();
			XHR.appendData('website_ssl_wl',www);
			XHR.sendAndLoad('$page', 'GET',x_sslBumbAddwl);		
		}
	}
	
	var x_sslbumpEnableW=function(obj){
     	var tempvalue=obj.responseText;
      	if(tempvalue.length>3){alert(tempvalue);}
      	if(row_id.length>0){ $('#row'+row_id).remove();}
     	}	
	
		function sslbumpEnableW(idname){
			var XHR = new XHRConnection();
			if(document.getElementById(idname).checked){
			XHR.appendData('enable',1);}else{XHR.appendData('enable',0);}
			XHR.appendData('website_ssl_eble',idname);
			XHR.sendAndLoad('$page', 'GET',x_sslbumpEnableW);		
		}
		
	function sslBumSettings(){
		YahooWin3('550','$page?add-params=yes','$parameters');
	}
		
		
	function sslbumpDeleteW(ID,rowid){
			row_id=rowid;
			var XHR = new XHRConnection();
			XHR.appendData('website_ssl_del',ID);
			XHR.sendAndLoad('$page', 'GET',x_sslbumpEnableW);	
		}
		
	
</script>

";
	
	echo $html;
}




function whitelist_enabled(){
	if(preg_match("#ENABLE_([0-9]+)#",$_GET["website_ssl_eble"],$re)){
		$sql="UPDATE squid_ssl SET enabled={$_GET["enable"]} WHERE ID={$re[1]}";
		$q=new mysql();
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		$s=new squidbee();
		$s->SaveToLdap();		
	}
}


function whitelist_add(){
	$_GET["website_ssl_wl"]=str_replace("https://","",$_GET["website_ssl_wl"]);
	if(preg_match("#^www\.(.+)#", $_GET["website_ssl_wl"],$re)){$_GET["website_ssl_wl"]=".".$re[1];}
	if(substr($_GET["website_ssl_wl"], 0,1)<>"."){$_GET["website_ssl_wl"]=".".$_GET["website_ssl_wl"];}
	$sql="INSERT INTO squid_ssl(website_name,enabled,`type`) VALUES('{$_GET["website_ssl_wl"]}',1,'ssl-bump-wl');";	
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$s=new squidbee();
	$s->SaveToLdap();	
	}
function whitelist_del(){
	$sql="DELETE FROM squid_ssl WHERE ID={$_GET["website_ssl_del"]}";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}	
	$s=new squidbee();
	$s->SaveToLdap();
}

function whitelist_list(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$database="artica_backup";	
	$search='%';
	$table="squid_ssl";
	$page=1;
	$FORCE_FILTER="AND `type`='ssl-bump-wl'";
	$squid=new squidbee();
	
	if($q->COUNT_ROWS($table,$database)==0){json_error_show("Empty table");}
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND (`{$_POST["qtype"]}` LIKE '$search')";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,$database);
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){json_error_show($q->mysql_error);}
		
	
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5(serialize($ligne));
		$color="black";	
		$delete="<a href=\"javascript:blur()\" OnClick=\"javascript:sslbumpDeleteW('{$ligne["ID"]}','$id');\"><img src='img/delete-24.png'></a>";   
		$enable=Field_checkbox("ENABLE_{$ligne["ID"]}",1,$ligne["enabled"],"sslbumpEnableW('ENABLE_{$ligne["ID"]}')");
		if($ligne["enabled"]==0){$color="#AFAFAF";}
		if($squid->SSL_BUMP_WHITE_LIST==1){$color="#AFAFAF";}
		
			
		
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array("<span style='font-size:16px;color:$color'>{$ligne["website_name"]}</span>"
		,$enable,$delete )
		);
	}
	
	
echo json_encode($data);		

}	





?>