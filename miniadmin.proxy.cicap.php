<?php
session_start();
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class=error-text>");
ini_set('error_append_string',"</p>\n");

$_SESSION["MINIADM"]=true;
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
if(!isset($_SESSION["uid"])){writelogs("Redirecto to miniadm.logon.php...","NULL",__FILE__,__LINE__);header("location:miniadm.logon.php");}
if($_SESSION["uid"]=="-100"){writelogs("Redirecto to location:admin.index.php...","NULL",__FILE__,__LINE__);header("location:admin.index.php");die();}
$users=new usersMenus();
if(!$users->AsDansGuardianAdministrator){senderror("{ERROR_NO_PRIVS}");}

if(isset($_GET["daemon-settings"])){daemon_settings();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["section-logs"])){section_logs();exit;}
if(isset($_GET["logs-search"])){logs_search();exit;}
if(isset($_POST["CicapEnabled"])){save_settings_post();exit;}
if(isset($_GET["exclude-mime-section"])){exclude_mime_section();exit;}
if(isset($_GET["exclude-mime-search"])){exclude_mime_search();exit;}

if(isset($_GET["exclude-www-section"])){exclude_www_section();exit;}
if(isset($_GET["exclude-www-search"])){exclude_www_search();exit;}



function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}

	if($SQUIDEnable==0){
		echo $tpl->_ENGINE_parse_body(FATAL_ERROR_SHOW_128("{proxy_service_is_disabled}<hr>		<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.newbee.php?js_enable_disable_squid=yes')\" style='font-size:22px;text-decoration:underline'>
		{enable_squid_service}</a>"));
		return;
	}
	
	$array["{daemon_settings}"]="$page?daemon-settings=yes";
	
	$array["{exclude}:Mime"]="$page?exclude-mime-section=yes";
	$array["{exclude}:{websites}"]="$page?exclude-www-section=yes";
	$array["{icap_logs}"]="miniadm.system.syslog.php?prepend=C-ICAP";
	
	
	echo $boot->build_tab($array);
}



function daemon_settings(){
	$t=time();
	$sock=new sockets();
	$ci=new cicap();
	$page=CurrentPageName();
	$CicapEnabled=$sock->GET_INFO("CicapEnabled");
	$EnableClamavInCiCap2=$sock->GET_INFO("EnableClamavInCiCap2");
	if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}	
	
	$notifyVirHTTPServer=false;
	if($ci->main_array["CONF"]["ViralatorMode"]==1){
		if(preg_match('#https://(.*?)/exec#',$ci->main_array["CONF"]["VirHTTPServer"],$re)){
			if(trim($re[1])==null){$notifyVirHTTPServer=true;}
			if(trim($re[1])=="127.0.0.1"){$notifyVirHTTPServer=true;}
			if(trim($re[1])=="localhost"){$notifyVirHTTPServer=true;}
		}}
	
		if($notifyVirHTTPServer==true){
			$color="color:red;font-weight:bolder";
		}
	
	
		for($i=1;$i<13;$i++){
			$f[$i]=$i;
		}	
	
	$boot=new boostrap_form();
	
	$boot->set_spacertitle("{daemon_settings}");
	
	
	$boot->set_checkbox("CicapEnabled", "{enable}", $CicapEnabled,array("DISABLEALL"=>true));
	
	//set_field($field_name,$caption,$value,$params=array()){
	
	$boot->set_field("Timeout", "{Timeout} ({seconds})",
			$ci->main_array["CONF"]["Timeout"],
			array("TOOLTIP"=>"{Timeout_text}")
	);

	$boot->set_field("MaxKeepAliveRequests", "{MaxKeepAliveRequests}",
			$ci->main_array["CONF"]["Timeout"],
			array("TOOLTIP"=>"{Timeout_text}")
	);

	$boot->set_field("KeepAliveTimeout", "{KeepAliveTimeout}",
			$ci->main_array["CONF"]["KeepAliveTimeout"],
			array("TOOLTIP"=>"{KeepAliveTimeout_text}")
	);	
	$boot->set_field("MaxServers", "{MaxServers}",
			$ci->main_array["CONF"]["MaxServers"],
			array("TOOLTIP"=>"{MaxServers_text}")
	);	
	
	$boot->set_field("MaxServers", "{MinSpareThreads}",
			$ci->main_array["CONF"]["MinSpareThreads"],
			array("TOOLTIP"=>"{MinSpareThreads_text}")
	);	
	$boot->set_field("MaxSpareThreads", "{MaxSpareThreads}",
			$ci->main_array["CONF"]["MaxSpareThreads"],
			array("TOOLTIP"=>"{MaxSpareThreads_text}")
	);
	$boot->set_field("ThreadsPerChild", "{ThreadsPerChild}",
			$ci->main_array["CONF"]["ThreadsPerChild"],
			array("TOOLTIP"=>"{ThreadsPerChild_text}")
	);	

	$boot->set_field("MaxRequestsPerChild", "{MaxRequestsPerChild}",
			$ci->main_array["CONF"]["MaxRequestsPerChild"],
			array("TOOLTIP"=>"{MaxRequestsPerChild_text}")
	);

	$boot->set_list("DebugLevel", "{debug_mode}",$f,
			$ci->main_array["CONF"]["DebugLevel"],
			array("TOOLTIP"=>"{MaxRequestsPerChild_text}")
	);	
	$boot->set_checkbox("ViralatorMode", "{ViralatorMode}",
			$ci->main_array["CONF"]["ViralatorMode"],
			array("TOOLTIP"=>"{ViralatorMode_text}")
	);	
	$boot->set_field("VirSaveDir", "{VirSaveDir}",
			$ci->main_array["CONF"]["VirSaveDir"],
			array("TOOLTIP"=>"{VirSaveDir_text}")
	);	
	$boot->set_field("VirHTTPServer", "{VirHTTPServer}",
			$ci->main_array["CONF"]["VirHTTPServer"],
			array("TOOLTIP"=>"{VirHTTPServer_text}")
	);	

	

	
	$boot->set_spacertitle("{cicap_title}");
	
	$boot->set_field("srv_clamav.SendPercentData", "{srv_clamav.SendPercentData} (MB)", 
			$ci->main_array["CONF"]["srv_clamav.SendPercentData"],
			array("TOOLTIP"=>"{srv_clamav.SendPercentData_text}")
			);
	
	$boot->set_field("srv_clamav.StartSendPercentDataAfter", "{srv_clamav.StartSendPercentDataAfter} (MB)",
			$ci->main_array["CONF"]["srv_clamav.StartSendPercentDataAfter"],
			array("TOOLTIP"=>"{srv_clamav.StartSendPercentDataAfter_text}")
	);

	$boot->set_field("srv_clamav.MaxObjectSize", "{srv_clamav.MaxObjectSize} (MB)",
			$ci->main_array["CONF"]["srv_clamav.MaxObjectSize"],
			array("TOOLTIP"=>"{srv_clamav.MaxObjectSize_text}")
	);	
	$boot->set_field("srv_clamav.ClamAvMaxFilesInArchive", "{srv_clamav.ClamAvMaxFilesInArchive} {files}",
			$ci->main_array["CONF"]["srv_clamav.ClamAvMaxFilesInArchive"],
			array("TOOLTIP"=>"{srv_clamav.ClamAvMaxFilesInArchive}")
	);
	
	$boot->set_field("srv_clamav.ClamAvMaxFileSizeInArchive", "{srv_clamav.ClamAvMaxFileSizeInArchive} (MB)",
			$ci->main_array["CONF"]["srv_clamav.ClamAvMaxFileSizeInArchive"],
			array("TOOLTIP"=>"{srv_clamav.ClamAvMaxFileSizeInArchive}")
	);	
	
	$boot->set_field("srv_clamav.ClamAvMaxRecLevel", "{srv_clamav.ClamAvMaxRecLevel} (MB)",
			$ci->main_array["CONF"]["srv_clamav.ClamAvMaxRecLevel"],
			array("TOOLTIP"=>"{srv_clamav.ClamAvMaxRecLevel}")
	);		

	$boot->set_formtitle("Antivirus");
	$boot->set_button("{apply}");
	$form=$boot->Compile();
	
	$html="<table style=width:100%'>
	<tr>
		<td style='vertical-align:top;width:300px'>
			<div id='status-$t'></div>
			
			<div style='margin:10px;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('status-$t','$page?status=yes')")."</div>
			
		<td style='vertical-align:top;padding-left:10px'>$form</td>
	</tr>
	</table>	
	<script>
		LoadAjax('status-$t','$page?status=yes')
	</script>
		";
	
	echo $html;
	
	
	
	
}
function exclude_mime_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_item}", "AddByMimeType()"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{default_rules}", "AddDefaultMimeType()"));
	echo $boot->SearchFormGen("pattern","exclude-mime-search",null,$EXPLAIN);	
	
}
function exclude_www_section(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_website}", "NewGItemICAP()"));
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{compile_rules}", "Loadjs('squid.restart.php?onlySquid=yes');"));
	echo $boot->SearchFormGen("websitename","exclude-www-search",null,$EXPLAIN);	
	
}
function exclude_www_search(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="webfilter_avwhitedoms";
	$page=1;
	$t=time();
	$searchstring=string_to_flexquery($_GET["exclude-www-search"]);
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring ORDER BY `websitename`";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error."<hr>$sql");	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
		$zmd5=md5($ligne["websitename"]);
		
		
		$delete=imgsimple("delete-32.png","","Delete$t('{$ligne["websitename"]}','$zmd5')");
		

		$tr[]="
		<tr>
		<td style='font-size:18px'>{$ligne["websitename"]}</td>
		<td style='font-size:16px;text-align:center;vertical-align:middle' width=1% nowrap>$delete</td>
		</tr>
		";		

	}
	
	$websitename=$tpl->javascript_parse_text("{acls_add_dstdomaindst}");
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
			<tr>
			<th >{items}</th>
			<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody>").@implode("", $tr)."</tbody></table>
			<script>	
var mem$t='';
var x_Delete$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    $('#'+mem$t).remove();
}
function Delete$t(www,md5){
	if(confirm('$delete '+www+'?')){
		mem$t=md5;
 		var XHR = new XHRConnection();
      	XHR.appendData('delete-item',www);
      	XHR.sendAndLoad('c-icap.wwwex.php', 'POST',x_Delete$t);		
		}
	}
	
var x_NewGItem$t=function(obj){
	var tempvalue=obj.responseText;
    if(tempvalue.length>3){alert(tempvalue);return;}
    ExecuteByClassName('SearchFunction');
}	
function NewGItemICAP(){
	var www=prompt('$websitename');
	if(!www){return;}
 	var XHR = new XHRConnection();
    XHR.appendData('add-item',www);
    XHR.sendAndLoad('c-icap.wwwex.php', 'POST',x_NewGItem$t);		

}
</script>";			
}

function exclude_mime_search(){
	$tpl=new templates();
	$page=CurrentPageName();
	$q=new mysql_squid_builder();
	$table="webfilters_blkwhlts";
	$page=1;
	$FORCE_FILTER="AND blockType=6";
	$searchstring=string_to_flexquery($_GET["exclude-mime-search"]);
	
	$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER ORDER BY `pattern`";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){senderror($q->mysql_error."<hr>$sql");}

	$PatternTypeH[1]="{ComputerMacAddress}";
	$PatternTypeH[0]="{addr}";
	$PatternTypeH[2]="{SquidGroup}";
	$PatternTypeH[3]="{browser}";
	$PatternTypeH[6]="{BannedMimetype}";
	
	
	$t=time();
	
	$GLOBALS["GroupType"]["src"]="{addr}";
	$GLOBALS["GroupType"]["arp"]="{ComputerMacAddress}";
	$GLOBALS["GroupType"]["dstdomain"]="{dstdomain}";
	$GLOBALS["GroupType"]["proxy_auth"]="{members}";
	$GLOBALS["GroupType"]["browser"]="{browser}";
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["pattern"]);
		$PatternTypeInt=$ligne["PatternType"];
		$PatternType=$tpl->_ENGINE_parse_body($PatternTypeH[$ligne["PatternType"]]);
		if($ligne["PatternType"]==0){$PatternType=$tpl->_ENGINE_parse_body("{addr}");}
		if($PatternType==null){
			if($_GET["blk"]>1){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==0){
			if($_GET["blk"]==2){$PatternType=$tpl->_ENGINE_parse_body("{website}");}
		}
		
		if($PatternTypeInt==1){
			if($_GET["blk"]==6){$PatternType=$tpl->_ENGINE_parse_body("{BannedMimetype}");}
		}
		
		$PatternAffiche=$ligne["pattern"];
		$description=$tpl->_ENGINE_parse_body($ligne["description"]);
		
		
		if($ligne["PatternType"]==2){
			$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName,GroupType FROM webfilters_sqgroups WHERE ID='{$ligne["pattern"]}'"));
			$description=$tpl->_ENGINE_parse_body($GLOBALS["GroupType"][$ligne2["GroupType"]]);
			$PatternAffiche=$ligne2["GroupName"];
		}
		
		if($ligne["zmd5"]==null){$q->QUERY_SQL("UPDATE webfilters_blkwhlts SET zmd5='$id' WHERE pattern='". mysql_escape_string2($ligne["pattern"])."'");$ligne["zmd5"]=$id;}
		$md5=$ligne["zmd5"];
		
		$delete=imgtootltip("delete-32.png","{delete} {$ligne["pattern"]}","BlksProxyDelete('$md5')");
		$enable=Field_checkbox($id,1,$ligne["enabled"],"BlksProxyEnable('$md5','$id')");
		
		
		$tr[]="
		<tr>
			<td style='font-size:16px'>$PatternAffiche</td>
			<td style='font-size:16px' width=5% nowrap>$description</td>
			<td style='font-size:16px;text-align:center;vertical-align:middle'>$enable</td>
			<td style='font-size:16px;text-align:center;vertical-align:middle'>$delete</td>					
		</tr>		
		";

			}		
			$type=$tpl->_ENGINE_parse_body("{sourcetype}");
			$pattern=$tpl->_ENGINE_parse_body("{pattern}");
			$description=$tpl->_ENGINE_parse_body("{description}");
			$add_mime_type_explain=$tpl->javascript_parse_text("{add_mime_type_white_explain}");
echo $tpl->_ENGINE_parse_body("
<table class='table table-bordered table-hover'>			
			<thead>
				<tr>
					<th >$pattern</th>
					<th>$description</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>").@implode("", $tr)."</tbody></table>
<script>
	var x_AddByMac= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		ExecuteByClassName('SearchFunction');
	}
			

function AddDefaultMimeType(){
		var XHR = new XHRConnection();
		XHR.appendData('AddDefaultMimeType-white','yes');
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST',x_AddByMac);	
}

function AddByMimeType(){
	var mac=prompt('$add_mime_type_explain');
	if(mac){
		var XHR = new XHRConnection();
		XHR.appendData('pattern',mac);
		XHR.appendData('PatternType',1);
		XHR.appendData('blk',6);
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST',x_AddByMac);		
	}
}

function BlksProxyDelete(pattern){
		var XHR = new XHRConnection();
		XHR.appendData('delete-pattern',pattern);
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST',x_AddByMac);
}

function BlksProxyEnable(pattern,id){
		var XHR = new XHRConnection();
		if(document.getElementById(id).checked){XHR.appendData('enabled',1);}else{XHR.appendData('enabled',0);}
		XHR.appendData('enable-pattern',pattern);
		XHR.sendAndLoad('squid.hosts.blks.php', 'POST');
}
</script>
			";
				
		
	
	
	
}

function section_logs(){
	$boot=new boostrap_form();
	$tpl=new templates();
	$page=CurrentPageName();
	
	//$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("{new_pool}", "Loadjs('$page?poolid-js=0')"));
	echo $boot->SearchFormGen(null,"logs-search",null,null);
	
}

function status(){
	
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$users=new usersMenus();
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?cicap-ini-status=yes')));
	echo $tpl->_ENGINE_parse_body(DAEMON_STATUS_ROUND("C-ICAP",$ini,null,0));	
	
}

function save_settings_post(){
	$sock=new sockets();
	if(isset($_POST["CicapEnabled"])){
		$sock->SET_INFO("EnableClamavInCiCap",$_POST["CicapEnabled"]);
		$sock->SET_INFO("CicapEnabled",$_POST["CicapEnabled"]);
		writelogs("EnableClamavInCiCap -> `{$_POST["EnableClamavInCiCap"]}`",__FUNCTION__,__FILE__,__LINE__);
		$sock->getFrameWork("cmd.php?squid-reconfigure=yes");
		$sock->getFrameWork("services.php?restart-artica-status=yes");
	}

	$ci=new cicap();
	while (list ($num, $line) = each ($_POST)){
		if(preg_match('#^srv_clamav_(.+)#',$num,$re)){
			$num="srv_clamav.{$re[1]}";
		}

		writelogs("Save $num => $line",__FUNCTION__,__FILE__,__LINE__);
		$ci->main_array["CONF"][$num]=$line;
	}

	$tpl=new templates();
	$ci->Save();
	NotifyServers();
}

function NotifyServers(){
	$sock=new sockets();
	$users=new usersMenus();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	if($users->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}
	if($EnableWebProxyStatsAppliance==1){
		$sock->getFrameWork("squid.php?notify-remote-proxy=yes");
	}

}


