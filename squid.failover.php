<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	include_once('ressources/class.ccurl.inc');
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["eth"])){step0_save();exit;}
	if(isset($_POST["ucarp_vid"])){step0_save();exit;}
	if(isset($_POST["second_ipaddr"])){step0_save();exit;}
	if(isset($_POST["SLAVE"])){step0_save();exit;}
	
	
	
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["step1"])){step1();exit;}
	if(isset($_GET["step2"])){step2();exit;}
	if(isset($_GET["step3"])){step3();exit;}
	if(isset($_GET["step4"])){step4();exit;}
	if(isset($_GET["step5"])){step5();exit;}
	if(isset($_GET["step6"])){step6();exit;}
	if(isset($_GET["step7"])){step7();exit;}
	if(isset($_GET["step8"])){step8();exit;}
	if(isset($_GET["unlink-js"])){unlink_js();exit;}
	if(isset($_GET["unlink-step1"])){unlink_setp1();exit;}
	if(isset($_GET["unlink-step2"])){unlink_setp2();exit;}
	if(isset($_GET["unlink-notify-slave"])){unlink_notify_slave();exit;}
	if(isset($_GET["unlink-reconfigure"])){unlink_reconfigure();exit;}
	if(isset($_GET["unlink-step3"])){unlink_setp3();exit;}
	
	if(isset($_POST["StepR1"])){die();}
	
js();

function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{failover}");
	echo "YahooWin6('650','$page?popup=yes','$title')";

}

function unlink_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{failover}:{unlink}");
	echo "YahooWin2('650','$page?unlink-step1=yes','$title')";	
	
}


function status(){
	$page=CurrentPageName();
	$qs=new mysql();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$ini=new Bs_IniHandler();
	
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{this_feature_is_disabled_corp_license}")."</p>";
	}
	
	if(!$users->UCARP_INSTALLED){
		$error="<p class=text-error>".$tpl->_ENGINE_parse_body("{error_missing_software}")."</p>";
	}
	
	$sql="SELECT COUNT(*) as tcount FROM nics WHERE `ucarp-enable`=1";
	$ligne2=mysql_fetch_array($qs->QUERY_SQL($sql,"artica_backup"));
	
	
	$data=base64_decode($sock->getFrameWork('system.php?ucarp-status-service=yes'));
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$array=unserialize(base64_decode($sock->getFrameWork('system.php?ucarp-isactive=yes')));
	
	if(isset($array["NIC"])){
		$l[]="[UCARP_NIC]";
		$l[]="service_name={$array["NIC"]}";
		$l[]="master_version={$array["IP"]}";
		$l[]="service_cmd=/etc/init.d/artica-failover";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="running=1";
		$l[]="master_pid=1";
		$l[]="processes_number=1";
		$l[]="";
		
		
	}else{
		$l[]="[UCARP_NIC]";
		$l[]="service_name={$MAIN["eth"]}";
		$l[]="master_version=0.0.0.0";
		$l[]="service_cmd=/etc/init.d/artica-failover";
		$l[]="service_disabled=1";
		$l[]="watchdog_features=1";
		$l[]="running=0";
		$l[]="master_pid=0";
		$l[]="processes_number=0";
		$l[]="";
		
	}
	
	$ini->loadString($data.@implode("\n", $l));
	
	$UCARP_MASTER=DAEMON_STATUS_ROUND("UCARP_MASTER",$ini,null,1);
	$UCARP_SLAVE=DAEMON_STATUS_ROUND("UCARP_SLAVE",$ini,null,1);
	$UCARP_NIC=DAEMON_STATUS_ROUND("UCARP_NIC",$ini,null,0);
	
	
	$SLAVE_IP=$MAIN["SLAVE"];
	$ucarp_vid=$MAIN["ucarp_vid"];
	
	
	
	
	
	
	
	

	
	if($SLAVE_IP<>null){
		$ip=new system_nic($MAIN["eth"]);
		$statTable=
		"<table style='width:99%;background-color:#D5EED9;padding:10px;border:1px solid #005447;margin:10px;border-radius:5px 5px 5px 5px'>
		<tr>
			<td class=legend style='font-size:18px'>{interface}:</td>
			<td style='font-size:18px'>{$MAIN["eth"]} - $ip->ucarp_vip</td>
		</tr>
		<tr>
			<td class=legend style='font-size:18px'>{second_ipaddr}:</td>
			<td style='font-size:18px'>{$MAIN["eth"]} - {$MAIN["second_ipaddr"]}</td>
		</tr>		
		
		<tr>
			<td class=legend style='font-size:18px'>{netzone}:</td>
			<td style='font-size:18px'>{$ucarp_vid}</td>
		</tr>			
		<tr>
			<td class=legend style='font-size:18px'>{slave}:</td>
			<td style='font-size:18px'>{$MAIN["SLAVE"]}</td>
		</tr>				
	</table>
	<br>
		<div style='width:90%;text-align:right;margin-bottom:20px'>".button("{unlink}", "Loadjs('$page?unlink-js=yes')",22)."</div>
	";
		
		
		
		
	}else{
		
		$statTable="<div style='width:90%;text-align:center;margin:20px'>".button("{failover_wizard}", "Loadjs('$page')",42)."</div>";
	}
	

	
	
	
	
	$html="<div style='width:98%' class=form>
	<table style='width:99%'>
	<tr>
		<td style='text-align:left;vertical-align:top;width:30%'>
			<p>&nbsp;</p>$UCARP_MASTER<br>$UCARP_SLAVE<br>$UCARP_NIC
			
		<div style='width:100%;text-align:right'>". imgtootltip("refresh-32.png","{refresh}","RefreshTab('main_failover_tabs')")."</div>	
		</td>
		<td	style='text-align:left;vertical-align:top;width:70%'>
				<div style='font-size:30px;margin-bottom:15px'>{failover}</div>
				<div class=text-info style='font-size:14px;'>{failover_explain}</div>
				$error
				$statTable
		</td>
	</tr>
	</table>
	</div>		
			
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function tabs(){
	
	
	
		$tpl=new templates();
		$page=CurrentPageName();
		$array["status"]='{status}';
		$array["events"]='{events}';

		while (list ($num, $ligne) = each ($array) ){
			
			if($num=="events"){
				$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"squid.watchdog-events.php?text-filter=failover\" style='font-size:16px'><span>$ligne</span></a></li>\n");
				continue;
			}
			
			
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes\" style='font-size:16px'><span>$ligne</span></a></li>\n");
			
		}
	
	
	
		echo build_artica_tabs($html, "main_failover_tabs")."<script>LeftDesign('failover-256-opac20.png');</script>";
	
	
	
	
}


function popup(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	
	$interfaces=$net->Local_interfaces();
	while (list ($eth, $line) = each ($interfaces) ){
		if($eth=="lo"){continue;}
		$ip=new system_nic($eth);
		$array[$eth]=$ip->IPADDR." (" .$ip->NICNAME .")";
	}
	
	$t=time();
	
	$html="
	<div id='failover-title' style='font-size:22px'></div>
	<div id='failover-div'>		
	<div style='font-size:16px' class=text-info>{welcome_php_failover_explain}</div>
	<p>&nbsp;</p>
	<div style='font-size:16px' class=text-info>{welcome_php_failover_explain_1}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td>". Field_array_Hash($array, "eth-$t",$MAIN["eth"],null,null,0,"font-size:16px")."</td>
	</tr>
	</table>
	<div style='text-align:right'><hr>". button("{next}", "Step0()",18)."</div>
	</div>
<script>
function xStep0(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step1=yes',true);

}			
			
function Step0(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',document.getElementById('eth-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStep0);	
}
			
			
</script>
			
	";
echo $tpl->_ENGINE_parse_body($html);
}

function unlink_setp1(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();	
	$html="
	<div id='failover-unlink-div'>
		<div style='font-size:16px' class=text-info>{welcome_php_failoverunlink_explain_2}</div>
		<p>&nbsp;</p>
		<div style='text-align:right'><hr>". button("{next}", "StepR1()",18)."</div>
	</div>
<script>
function xStepR1(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-unlink-div','$page?unlink-step2=yes',true);

}			
			
function StepR1(){
	var XHR = new XHRConnection();
	XHR.appendData('StepR1','yes');
	XHR.sendAndLoad('$page', 'POST',xStepR1);	
}
</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function unlink_setp2(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$t=time();
	$html="
	<div style='font-size:30px'>{please_wait_notify_slave}</div>
	<div id='unlink_setp2$t'></div>
	<script>LoadAjax('unlink_setp2$t','$page?unlink-notify-slave=yes')</script>		
	";
	
	echo $tpl->_parse_body($html);
	
}

function unlink_setp3(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$t=time();
	$html="
	<div style='font-size:30px'>{please_wait_reconfigure_network}</div>
	<div id='unlink_setp3$t'></div>
	<script>LoadAjax('unlink_setp3$t','$page?unlink-reconfigure=yes')</script>
	";
	
	echo $tpl->_parse_body($html);	
	
}

function step1(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));	
	$eth=$MAIN["eth"];
	$t=time();
	
	$nic=new system_nic($eth);
	for($i=1;$i<256;$i++){
		$ucarp_vids[$i]=$i;
	}
	$ip=new system_nic($MAIN["eth"]);
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	
	$html="
	<div id='failover-div'>
	<div style='font-size:16px' class=text-info>{welcome_php_failover_explain_2}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - $ip->IPADDR</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td>". Field_array_Hash($ucarp_vids, "ucarp_vid-$t",$MAIN["ucarp_vid"],null,null,0,"font-size:16px")."</td>
	</tr>
	</table>
				
	<div style='text-align:right'><hr>". button("{next}", "Step1()",18)."</div>
	</div>
<script>
function xStep1(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step2=yes',true);

}			
			
function Step1(){
	var XHR = new XHRConnection();
	XHR.appendData('ucarp_vid',document.getElementById('ucarp_vid-$t').value);
	XHR.sendAndLoad('$page', 'POST',xStep1);	
}
			
			
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}
function step2(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();

	$nic=new system_nic($eth);

	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	
	$welcome_php_failover_explain_net=$tpl->_ENGINE_parse_body("{welcome_php_failover_explain_net}");
	$welcome_php_failover_explain_net=str_replace("%s", $ip->IPADDR, $welcome_php_failover_explain_net);
	$html="
	<div style='font-size:16px' class=text-info>$welcome_php_failover_explain_net</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - $nic->IPADDR</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>". field_ipv4("second_ipaddr-$t", $MAIN["second_ipaddr"],"font-size:16px")."</td>
	</tr>	
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step1=yes')",18)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step2()",18)."</div></td>
		</tr>
	</table>
<script>
function xStep1(obj){
	var tempvalue=obj.responseText;
	if(tempvalue.length>3){alert(tempvalue);return;}
	LoadAjax('failover-div','$page?step3=yes',true);
}
				
function Step2(){
	var XHR = new XHRConnection();
	XHR.appendData('second_ipaddr',document.getElementById('second_ipaddr-$t').value);
	XHR.appendData('first_ipaddr','$nic->IPADDR');
	XHR.sendAndLoad('$page', 'POST',xStep1);
}
						
	
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
}

function step3(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));	
	$eth=$MAIN["eth"];
	$t=time();
	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	if(!is_numeric($MAIN["SLAVE_PORT"])){$MAIN["SLAVE_PORT"]=9000;}
	$ip=new networking();
	
	while (list ($eth, $cip) = each ($ip->array_TCP) ){
		if($cip==null){continue;}
		$arrcp[$cip]=$cip;
	}
	
	$arrcp[null]="{default}";
	$nic=new system_nic($MAIN["eth"]);
	$WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
	$WgetBindIpAddress=Field_array_Hash($arrcp,"WgetBindIpAddress",$WgetBindIpAddress,null,null,0,"font-size:19px;padding:3px;");
	
	$html="
	<div id='$t'>
	<div class=text-info style='font-size:16px'>{welcome_php_failover_explain_3}</div>
	<div class=form style='width:95%'>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - {$MAIN["first_ipaddr"]}</td>
		<td>&nbsp;</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>{$MAIN["second_ipaddr"]}</td>
		<td>&nbsp;</td>
	</tr>		
	<tr>
	<td class=legend style='font-size:16px'>{WgetBindIpAddress}:</td>
	<td style='font-size:14px'>$WgetBindIpAddress</td>
	<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{hostname}/IP ({slave}):</td>
		<td style='font-size:14px'>". Field_text("SLAVE-$t",$MAIN["SLAVE"],"font-size:19px;font-weight:bold;width:200px")."</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{listen_port}:</td>
		<td style='font-size:14px'>". Field_text("SLAVE_PORT-$t",$MAIN["SLAVE_PORT"],"font-size:19px;width:90px")."</td>
	   <td>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{use_ssl}:</td>
		<td style='font-size:14px'>". Field_checkbox("SLAVE_SSL-$t",1,$MAIN["SLAVE_SSL"])."</td>
		<td>&nbsp;</td>
	</tr>
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step2=yes')",18)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step3()",18)."</div></td>
		</tr>
	</table>	
<script>
	var xStep3=function (obj) {
	var results=obj.responseText;
	if(results.length>10){alert(results);}
	LoadAjax('$t','$page?step4=yes&t=$t');
}
	
function Step3(){
	var XHR = new XHRConnection();
	if(document.getElementById('SLAVE_SSL-$t').checked){XHR.appendData('SLAVE_SSL','1');}else{XHR.appendData('SLAVE_SSL','0');}
	XHR.appendData('SLAVE',document.getElementById('SLAVE-$t').value);
	XHR.appendData('SLAVE_PORT',document.getElementById('SLAVE_PORT-$t').value);
	XHR.appendData('WgetBindIpAddress',document.getElementById('WgetBindIpAddress').value);
	AnimateDiv('$t');
	XHR.sendAndLoad('$page', 'POST',xStep3);
}
	

</script>";
echo $tpl->_ENGINE_parse_body($html);
}

function step4(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();

	$nic=new system_nic($eth);
	$connecting=$tpl->_ENGINE_parse_body("{connecting_to}");
	
	if(!is_numeric($MAIN["ucarp_vid"])){$MAIN["ucarp_vid"]=3;}
$html="
	<div style='font-size:16px' class=text-info>{welcome_php_failover_explain_4}</div>
	<p>&nbsp;</p>
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px'>{interface}:</td>
		<td style='font-size:16px'>{$MAIN["eth"]} - {$MAIN["first_ipaddr"]}</td>
	</tr>			
	<tr>
		<td class=legend style='font-size:16px'>{netzone}:</td>
		<td style='font-size:16px'>{$MAIN["ucarp_vid"]}</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>{second_ipaddr}:</td>
		<td style='font-size:16px'>{$MAIN["second_ipaddr"]}</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px'>{hostname}/IP ({slave}):</td>
		<td style='font-size:16px'>{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}</td>
	</tr>
	</table>
	<table style='width:100%'>
		<tr>
			<td width=50%><div style='text-align:left'><hr>". button("{back}", "LoadAjax('failover-div','$page?step3=yes')",18)."</div></td>
			<td width=50%><div style='text-align:right'><hr>". button("{next}", "Step4()",18)."</div></td>
		</tr>
	</table>
<script>
function Step4(){
	document.getElementById('failover-title').innerHTML='$connecting {$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}';
	LoadAjax('failover-div','$page?step5=yes')
}
						
	
</script>
";
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
}

function step0_save(){
	$sock=new sockets();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	while (list ($key, $val) = each ($_POST) ){
		$MAIN[$key]=$val;
	}
	//first_ipaddr
	$sock->SaveConfigFile(base64_encode(serialize($MAIN)), "HASettings");
}


function step5(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp=$SEND_SETTING";
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr>
		<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>$deb<hr>".wizard_restart());
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo FATAL_WARNING_SHOW_128("<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<br>
				{protocol_error}
				<br>
				{check_same_version_artica}<br>$curl->data<br>$deb".wizard_restart());
		return;
	}
	
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo FATAL_WARNING_SHOW_128("<hr><strong>{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}
		</strong><hr>{$array["ERROR_SHOW"]}<br>$deb".wizard_restart());
		return;		
	}
	
	$t=time();
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px'>{success}<br>{$array["ERROR_SHOW"]}<br>{please_wait}</center>").
	"<script>
		function Start$t(){
			LoadAjax('failover-div','$page?step6=yes');
		}
		setTimeout('Start$t()',3000);
	</script>
	";
}
function wizard_restart(){
	$page=CurrentPageName();
	return "<center style='margin:20px'>
			". button("{back}", "LoadAjax('failover-div','$page?step4=yes')",22)."
		  </center>";
}
function debug_curl($array){
	$t[]="<table style='width:100%'>";

	while (list ($num, $val) = each ($array) ){
		if(is_array($val)){
			while (list ($a, $b) = each ($val) ){$tt[]="<li>$a = $b</li>";}
			$val=null;
			$val=@implode("\n", $tt);
		}
		$val=wordwrap($val,90,"<br>");
		if(strtolower($num)=="url"){continue;}
		$t[]="<tr>
		<td class=legend style='font-size:14px'>$num:</td>
		<td style='font-size:14px'>$val</td>
		</tr>";

	}
	$t[]="</table>";

	return @implode("\n", $t);

}

function unlink_reconfigure(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	$you_should_reboot_the_server=$tpl->javascript_parse_text("{you_should_reboot_the_server}");
	$nic=new system_nic($eth);
	$nic->ucarp_enabled=0;
	$nic->ucarp_vip=null;
	$nic->ucarp_vid=0;
	$nic->ucarp_master=0;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("<hr>
				<strong>Failed</strong>
				<br>
				{mysql_error}
				<br>
				
				<table style='width:100%'>
				<tr>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{back}", "Loadjs('$page?unlink-js=yes')",18)."</td>
					
				</tr>
				</table>"));
		return;
		
	}
	
	echo "<script>
				alert('$you_should_reboot_the_server');
				YahooWin2Hide();
				RefreshTab('main_failover_tabs');
			</script>";

	
	$sock->SET_INFO("HASettings", base64_encode(serialize(array())));
	$sock->getFrameWork("network.php?reconfigure-restart=yes");

	}
function unlink_notify_slave(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["first_ipaddr"];
	
	$SEND_SETTING=base64_encode(serialize($MAIN));
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp2-remove=$SEND_SETTING&continue=true";	
	$curl=new ccurl($uri,true);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128($curl->error."<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<hr>
				$deb<hr>")."
				<table style='width:100%'>
				<tr>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{back}", "Loadjs('$page?unlink-js=yes')",18)."</td>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{continue}", "LoadAjax('failover-unlink-div','$page?unlink-step3=yes',true);",18)."</td>
				</tr>
				</table>");
					
				
				
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<br>
				{protocol_error}<br>
				<code style='font-size:12px'>$uri</code>
				<br>
				{check_same_version_artica}<br>$curl->data<br>$deb")."
				<table style='width:100%'>
				<tr>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{back}", "Loadjs('$page?unlink-js=yes')",18)."</td>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{continue}", "LoadAjax('failover-unlink-div','$page?unlink-step3=yes',true)",18)."</td>
				</tr>
				</table>");
		return;
	}	
	
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo $tpl->_ENGINE_parse_body(FATAL_WARNING_SHOW_128("<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<br>
				{$array["ERROR_SHOW"]}
				<br>
				$deb")."
				<table style='width:100%'>
				<tr>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{back}", "Loadjs('$page?unlink-js=yes')",18)."</td>
					<td style='width:50%;vertical-align:middle;text-align:center'>". button("{continue}", "LoadAjax('failover-unlink-div','$page?unlink-step3=yes',true)",18)."</td>
				</tr>
				</table>");
		return;
	}
	
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px'>{success}<br>{$array["ERROR_SHOW"]}<br>{please_wait}</center>").
	"<script>
	function Start$t(){
		LoadAjax('failover-unlink-div','$page?unlink-step3=yes');
	}
	setTimeout('Start$t()',3000);
	</script>
	";	
	
	
	
	
}

function step6(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["first_ipaddr"];
	
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp2=$SEND_SETTING";
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<hr>
				$deb<hr>".wizard_restart());
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo FATAL_WARNING_SHOW_128("<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<br>
				{protocol_error}
				<br>
				{check_same_version_artica}<br>$curl->data<br>$deb".wizard_restart());
		return;
	}
	
	$array=unserialize(base64_decode($re[1]));
	if($array["ERROR"]){
		echo FATAL_WARNING_SHOW_128("<hr><strong>{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
		<hr>{$array["ERROR_SHOW"]}<br>$deb".wizard_restart());
		return;
	}
	
	$saving_local_parameters=$tpl->javascript_parse_text("{saving_local_parameters}");
	$t=time();
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px'>{$array["ERROR_SHOW"]}<br>{please_wait}</center>").
	"<script>
	function Start$t(){
	document.getElementById('failover-title').innerHTML='$saving_local_parameters';
	LoadAjax('failover-div','$page?step7=yes');
	}
	setTimeout('Start$t()',2000);
	</script>
	";	
	
}

function step7(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$MAIN["first_ipaddr"];

	$nic->IPADDR=$MAIN["second_ipaddr"];
	$nic->ucarp_enabled=1;
	$nic->ucarp_vip=$MAIN["BALANCE_IP"];
	$nic->ucarp_vid=$MAIN["ucarp_vid"];
	$nic->ucarp_master=1;
	$nic->NoReboot=true;
	if(!$nic->SaveNic()){
		echo FATAL_WARNING_SHOW_128("<hr><strong>Unable to save local settings</strong>".wizard_restart());
		return;
	}
	
	$t=time();
	$reboot_remote_server_net=$tpl->javascript_parse_text("{reboot_networks}");
	echo $tpl->_ENGINE_parse_body("<center style='font-size:22px'>{please_wait}</center>").
	"<script>
	function Start$t(){
	document.getElementById('failover-title').innerHTML='$reboot_remote_server_net';
	LoadAjax('failover-div','$page?step8=yes');
	}
	setTimeout('Start$t()',2000);
	</script>
	";	
	
}
function step8(){
	$tpl=new templates();
	$sock=new sockets();
	$net=new networking();
	$page=CurrentPageName();
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$t=time();
	if(!is_numeric($MAIN["SLAVE_SSL"])){$MAIN["SLAVE_SSL"]=1;}
	$proto="http";
	if($MAIN["SLAVE_SSL"]==1){$proto="https";}
	
	$MAIN=unserialize(base64_decode($sock->GET_INFO("HASettings")));
	$eth=$MAIN["eth"];
	$nic=new system_nic($eth);
	$MAIN["BALANCE_IP"]=$nic->IPADDR;
	
	$SEND_SETTING=urlencode(base64_encode(serialize($MAIN)));
	
	$uri="$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]}/nodes.listener.php?ucarp3=$SEND_SETTING";
	
	$curl=new ccurl($uri);
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		$deb=debug_curl($curl->CURL_ALL_INFOS);
		echo FATAL_WARNING_SHOW_128($curl->error."<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<hr>
				$deb<hr>".wizard_restart());
		return;
	}
	
	if(!preg_match("#<RESULTS>(.+?)</RESULTS>#is", $curl->data,$re)){
		echo FATAL_WARNING_SHOW_128("<hr>
				<strong>$proto://{$MAIN["SLAVE"]}:{$MAIN["SLAVE_PORT"]} SSL:{$MAIN["SLAVE_SSL"]}</strong>
				<br>
				{protocol_error}
				<br>
				{check_same_version_artica}<br>$curl->data<br>$deb".wizard_restart());
		return;
	}
	
	$sock=new sockets();
	$sock->getFrameWork("network.php?reconfigure-restart=yes");
	$reboot_remote_server_net=$tpl->javascript_parse_text("{success}");
	echo "<script>
	function Start$t(){
		document.getElementById('failover-title').innerHTML='$reboot_remote_server_net';
		YahooWin6Hide();
		CacheOff();
		RefreshTab('main_failover_tabs');
	}
	setTimeout('Start$t()',2000);
	
	</script>
	";
}
