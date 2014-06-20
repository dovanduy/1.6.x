<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.mysql.inc');	
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.system.nics.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$error=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	
	if(isset($_GET["iptables"])){iptables_tabs();exit;}
	if(isset($_GET["iptables-table"])){iptables_table();exit;}
	if(isset($_GET["rules"])){rules();exit;}
	if(isset($_GET["ruleid"])){rule_js();exit;}
	if(isset($_GET["rule-tabs"])){rule_tab();exit;}
	if(isset($_GET["rule-popup"])){rule_popup();exit;}
	if(isset($_POST["isFW"])){isFW_save();exit;}
	if(isset($_POST["rule-save"])){rule_save();exit;}
	if(isset($_GET["groupname"])){groupname();exit;}
	if(isset($_POST["rule-order"])){rule_order();exit;}
	if(isset($_POST["rule-delete"])){rule_delete();exit;}
	if(isset($_POST["rule-enable"])){rule_enable();exit;}
	if(isset($_GET["rule-time"])){rule_time();exit;}
	if(isset($_POST["time-save"])){time_save();exit;}
	if(isset($_GET["generic"])){generic_tabs();exit;}
	if(isset($_POST["EnableArticaAsGateway"])){EnableArticaAsGateway_save();exit;}
	tabs();
	
function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__)){return;}
	$page=CurrentPageName();
	$net=new networking();
	$interfaces=$net->Local_interfaces();
	$tpl=new templates();
	$generic=$tpl->_ENGINE_parse_body("{central_rules}");
	unset($interfaces["lo"]);
	ksort($interfaces);
	$fontsize="font-size:18px;";
	
	$html[]="<li><a href=\"$page?generic=yes\" style='$fontsize' ><span>$generic</span></a></li>\n";
	
	
	while (list ($interface, $ligne) = each ($interfaces) ){
		if(preg_match("#^dummy#", $interface)){continue;}
		if(preg_match("#^ip6t#", $interface)){continue;}
		if(preg_match("#^sit[0-9]+#", $interface)){continue;}
		if(preg_match("#^tunl#", $interface)){continue;}
		$eth=new system_nic($interface);
		$html[]="<li><a href=\"$page?iptables=yes&eth=$interface\" style='$fontsize' ><span>$interface $eth->NICNAME</span></a></li>\n";
	}
	
	
	$html=build_artica_tabs($html,'main_firewall',1100)."
		<script>LeftDesign('firewall-256-white-opac20.png');</script>";
	
	SET_CACHED(__FILE__, __FUNCTION__, __FUNCTION__, $html);
	echo $html;
}

function generic_tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$fontsize="font-size:18px;";
	$eth=$_GET["eth"];
	$ID=$_GET["ID"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	

	$array["firewall"]='{incoming_firewall}';
	$array["antihack"]='Anti-hack SSH';
	$array["firewall-white"]='{whitelist}';
	
	$fontsize="font-size:16px";
	while (list ($index, $ligne) = each ($array) ){
			if($index=="firewall"){
			$html[]= "<li><a href=\"system.firewall.in.php?no=no$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}	
		
		if($index=="antihack"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"postfix.iptables.php?tab-iptables-rules=yes&sshd=yes\"><span style='$fontsize'>$ligne</span></a></li>\n");
			continue;
		}

		if($index=="firewall-white"){
			$html[]= "<li><a href=\"whitelists.admin.php?popup-hosts=yes$linkadd\"><span style='$fontsize'>". $tpl->_ENGINE_parse_body($ligne)."</span></a></li>\n";
			continue;
		}
	}
	
	
	echo build_artica_tabs($html,'tabs_central_rules');	
	
}

function iptables_tabs(){
	
	$eth=$_GET["eth"];
	
	if(GET_CACHED(__FILE__, __FUNCTION__, $eth)){return;}
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	
	$array["STATUS"]="{status}";
	$array["INPUT"]="{INPUT}";
	$array["OUTPUT"]="{OUTPUT}";
	$array["FORWARD"]="{FORWARD}";
	while (list ($index, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?iptables-table=yes&eth=$eth&table=$index\" style='$fontsize' ><span>$ligne</span></a></li>\n";
	}
	
	
	$html=build_artica_tabs($html,"main_firewall_table_$eth",950);
	SET_CACHED(__FILE__, __FUNCTION__, $eth, $html);
	echo $html;
}

function rule_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$ID=$_GET["ruleid"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	if(!is_numeric($ID)){$ID=0;}
	if($ID==0){$title=$tpl->javascript_parse_text("$eth::{new_rule}::$table::");}
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
		$title="$eth::".$tpl->javascript_parse_text($ligne["rulename"]);
	}

	echo "YahooWin('900','$page?rule-tabs=yes&ID=$ID&t=$t&table=$table&eth=$eth','$title')";
}

function rule_tab(){
	$page=CurrentPageName();
	$fontsize="font-size:16px;";
	$eth=$_GET["eth"];
	$ID=$_GET["ID"];
	$table=$_GET["table"];
	$eth=$_GET["eth"];
	$t=$_GET["t"];
	
	
	$array["rule-popup"]="{rule}";
	if($ID>0){
		$array["rule-time"]="{time_restriction}";
	}
	$fontsize="font-size:16px";
	while (list ($index, $ligne) = each ($array) ){
		$html[]="<li><a href=\"$page?$index=yes&eth=$eth&table=$table&ID=$ID&t=$t\" style='$fontsize' ><span>$ligne</span></a></li>\n";
	}
	
	
	echo build_artica_tabs($html,'main_firewall_rule_'.$ID);	
	
}

function rule_time(){
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$table=$_GET["table"];
	$ID=$_GET["ID"];
	$t=time();	
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
	$title="{time_restriction}: $eth::".$tpl->javascript_parse_text($ligne["rulename"]);
	$enabled=$ligne["enabled"];
	$table=$ligne["MOD"];
	$eth=$ligne["eth"];
	$bt="{apply}";	
	
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);
	
	$TTIME=unserialize($ligne["time_restriction"]);
	
	$tr[]="<table>";
	
	while (list ($num, $maks) = each ($array_days)){
		
		$tr[]="<tr>
				<td class=legend style='font-size:16px'>{{$maks}}</td>
				<td>". Field_checkbox("D{$num}-$t", 1,$TTIME["D{$num}"])."</td>
			</tr>";
		$jsF[]="if(document.getElementById('D{$num}-$t').checked){XHR.appendData('D{$num}',1); }else{ XHR.appendData('D{$num}',0); }";
		$jsD[]="document.getElementById('D{$num}-$t').disabled=true;";
		$jsE[]="document.getElementById('D{$num}-$t').disabled=false;";
		
	}
	$tr[]="</table>";
	
	if($TTIME["ftime"]==null){$TTIME["ftime"]="20:00:00";}
	if($TTIME["ttime"]==null){$TTIME["ttime"]="23:59:00";}
	
	$html="
<div style='width:98%' class=form>
	<div style='font-size:18px;margin-bottom:25px;margin-top:10px;margin-left:5px'>[$table] $title</div>
	<table style='width:100%'>
	<tr>
	<td class=legend style='font-size:16px'>{enabled}:</td>
	<td style='font-size:16px'>". Field_checkbox("enabled-$t", 1,$ligne["enablet"],"EnableCK$t()")."
	</tr>
	<tr>
	<td class=legend style='font-size:16px'>{from_time}:</td>
	<td style='font-size:16px'>". field_text("ftime-$t",$TTIME["ftime"],"font-size:16px;width:110px")."
	</tr>
	<tr>
	<td class=legend style='font-size:16px'>{to_time}:</td>
	<td style='font-size:16px'>". field_text("ttime-$t",$TTIME["ttime"],"font-size:16px;width:110px")."
	</tr>	
	<tr>
		<td style='font-size:22px'>{days}:</td>
		<td colspan=2>".@implode("", $tr)."</td>
	</tr>
	<tr>
		<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",22)."</td>
	</tr>	
	</table>
</div>
<script>

var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID=$ID;
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
}

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('time-save',  '$ID');
	XHR.appendData('ttime',  document.getElementById('ttime-$t').value);
	XHR.appendData('ftime',  document.getElementById('ftime-$t').value);
	if(document.getElementById('enabled-$t').checked){ XHR.appendData('enablet',1); }else{ XHR.appendData('enablet',0); }
	".@implode("\n", $jsF)."
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
	
function EnableCK$t(){
	if(document.getElementById('enabled-$t').checked){ 
		document.getElementById('ttime-$t').disabled=false;
		document.getElementById('ftime-$t').disabled=false;
		".@implode("\n", $jsE)."
	}else{
		document.getElementById('ttime-$t').disabled=true;
		document.getElementById('ftime-$t').disabled=true;
		".@implode("\n", $jsD)."				
	
	}
	

}
				
EnableCK$t();
</script>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function time_save(){
	
	$ID=$_POST["time-save"];
	
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);

	while (list ($num, $maks) = each ($array_days)){	
		if($_POST["D{$num}"]==1){$TTIME["D{$num}"]=1;}
	}
	$TTIME["ttime"]=$_POST["ttime"];
	$TTIME["ftime"]=$_POST["ftime"];
	
	$TTIMEZ=mysql_escape_string2(serialize($TTIME));
	
	
	$q=new mysql();
	if(!$q->FIELD_EXISTS("iptables_main","time_restriction","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `time_restriction` TEXT";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	if(!$q->FIELD_EXISTS("iptables_main","enablet","artica_backup")){
		$sql="ALTER TABLE `iptables_main` ADD `enablet` smallint( 1 ) NOT NULL DEFAULT '0',ADD INDEX ( enablet ) ";
		$q->QUERY_SQL($sql,"artica_backup");
	}
	
	$sql="UPDATE iptables_main SET `enablet`='{$_POST["enablet"]}',`time_restriction`='$TTIMEZ' WHERE ID='$ID'";
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";}	
	
}


function rule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$table=$_GET["table"];
	$ID=$_GET["ID"];
	$t=time();
	$title="$eth::".$tpl->_ENGINE_parse_body("{new_rule}");
	$bt="{add}";
	$enabled=1;
	$LOCKFORWARD=1;
	if($ID>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT * FROM iptables_main WHERE ID='$ID'","artica_backup"));
		$title="$eth::".$tpl->javascript_parse_text($ligne["rulename"]);
		$enabled=$ligne["enabled"];
		$table=$ligne["MOD"];
		$eth=$ligne["eth"];
		$bt="{apply}";
		
	}
/*`ID` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`zOrder` INT UNSIGNED DEFAULT 1,
	`rulename` VARCHAR( 128 ) NOT NULL,
	`MOD` VARCHAR( 20 ) NOT NULL,
	`eth` VARCHAR( 10 ) NOT NULL ,
	`proto` VARCHAR( 5 ) NOT NULL,
	`destport_group` INT( 10 ) NOT NULL,
	`source_group` INT( 10 ) NOT NULL,
	`dest_group` INT( 10 ) NOT NULL,
	`enabled` SMALLINT( 1 ) NOT NULL,
	`accepttype` VARCHAR( 20 ) NOT NULL,
	KEY `MOD`(`MOD`),
	KEY `zOrder`(`zOrder`),
			KEY `enabled`(`enabled`),
					KEY `rulename`(`rulename`),
							KEY `eth`(`eth`),
							KEY `proto`(`proto`),
									KEY `accept_type`(`accept_type`)
*/									
	if($table=="FORWARD"){$LOCKFORWARD=0;}
	$rulename=$ligne["rulename"];
	$proto=$ligne["proto"];
	$accepttype=$ligne["accepttype"];
	$source_group=intval($ligne["source_group"]);
	$dest_group=intval($ligne["dest_group"]);
	
	$destport_group=intval($ligne["destport_group"]);
	
	if($proto==null){$proto="tcp";}
	$protos[null]="{all}";
	$protos["udp"]="UDP";
	$protos["tcp"]="tcp";
	
	$accepttypes["ACCEPT"]="{accept}";
	$accepttypes["DROP"]="{drop}";
	$accepttypes["RETURN"]="{return}";
	
	$nic=new networking();
	$nicZ=$nic->Local_interfaces();
	unset($nicZ[$eth]);
	$ForwardNICs[null]="{none}";
	while (list ($yinter, $line) = each ($results) ){
		$znic=new system_nic($yinter);
		if($znic->Bridged==1){continue;}
		$ForwardNICs[$yinter]="$yinter - $znic->NICNAME";
	}
	
	
	$AllSystems=$tpl->javascript_parse_text("{AllSystems}");
	$AllPorts=$tpl->javascript_parse_text("{AllPorts}");
	
	if($source_group==0){
		$inbound_object=$AllSystems;
	}
	if($dest_group==0){
		$outbound_object=$AllSystems;
	}
	
	if($destport_group==0){
		$destports_object=$AllPorts;
	}	
	
	if(!is_numeric($ligne["zOrder"])){$ligne["zOrder"]=1;}
	$jsGroup1="squid.BrowseAclGroups.php?callback=LinkInBoundGroup$t&FilterType=FW-IN";
	$jsGroup2="squid.BrowseAclGroups.php?callback=LinkOutbBoundGroup$t&FilterType=FW-OUT";
	$jsGroup3="squid.BrowseAclGroups.php?callback=LinkPortGroup$t&FilterType=FW-PORT";
	
	$sDel1=imgtootltip("22-delete.png","{unlink}","Delgroup1$t()");
	$sDel2=imgtootltip("22-delete.png","{unlink}","Delgroup2$t()");
	$sDel3=imgtootltip("22-delete.png","{unlink}","Delgroup3$t()");
	
	$html="
<div style='width:98%' class=form>
	". Field_hidden("source_group-$t", $ligne["source_group"])."
	". Field_hidden("dest_group-$t", $ligne["dest_group"])."
	". Field_hidden("destport_group-$t", $ligne["destport_group"])."				
	<div style='font-size:18px;margin-bottom:25px;margin-top:10px;margin-left:5px'>[$table] $title</div>
	
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{rulename}:</td>
		<td>". Field_text("rulename-$t",$rulename,"font-size:16px;width:450px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{order}:</td>
		<td>". Field_text("zOrder-$t",$ligne["zOrder"],"font-size:16px;width:90px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>						
	<tr>
		<td class=legend style='font-size:16px' nowrap>{enabled}:</td>
		<td>". Field_checkbox("enabled-$t", 1,$enabled)."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
	<tr>
		<td class=legend style='font-size:16px' nowrap>{OverideNet}:</td>
		<td>". Field_checkbox("OverideNet-$t", 1,$ligne["OverideNet"])."</td>
		<td width=1%>". help_icon("{OverideNet_explain}")."</td>
	</tr>				
				
				

	<tr>
		<td class=legend style='font-size:16px' nowrap>{protocol}:</td>
		<td>". Field_array_Hash($protos,"proto-$t",$proto,"style:font-size:16px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>	
<tr><td colspan=3>
<center style='width:90%;margin:20px;border:1px solid #DDDDDD;border-radius:4px 4px 4px 4px'>
<table >
	<tr><td colspan=4>&nbsp;</td></tr>	
	<tr>
		<td width=42% align='center' style='font-size:16px;font-weight:bold;'>{inbound_object}</td>
		<td width=5% align='center'>&nbsp;</td>				
		<td width=42% align='center' style='font-size:16px;font-weight:bold'>{outbound_object}</td>		
	</tr>	
	<tr><td colspan=4>&nbsp;</td></tr>	
	<tr>
		<td width=42% align='center'><a href=\"javascript:Loadjs('$jsGroup1');\" 
			style='font-size:16px;text-decoration:underline'>
				<span id='in-$t'>$inbound_object</span></a>$sDel1</td>
		<td width=5% align='center'><img src='img/arrow-blue-left-64.png'></td>				
		<td width=42% align='center'><a href=\"javascript:Loadjs('$jsGroup2');\" 
		style='font-size:16px;text-decoration:underline'>
			<span id='out-$t'>$outbound_object</span></a>$sDel2</td>		
	</tr>
	<tr><td colspan=4>&nbsp;</td></tr>		
</table>
</center>
</td>
</tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{dest_ports}:</td>
		<td><a href=\"javascript:Loadjs('$jsGroup3');\" 
		style='font-size:16px;text-decoration:underline'>
			<span id='port-$t'>$destports_object</span></a>$sDel3</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr><td colspan=3>&nbsp;</td></tr>
	<tr>
		<td class=legend style='font-size:16px' nowrap>{action}:</td>
		<td>". Field_array_Hash($accepttypes,"accepttype-$t",$accepttype,"style:font-size:16px")."</td>
		<td width=1%>&nbsp;</td>
	</tr>
	<tr>
		<td colspan=3>
		<center style='width:90%;margin:20px;border:1px solid #DDDDDD;border-radius:4px 4px 4px 4px'>
			<table>
				<tr><td colspan=3>&nbsp;</td></tr>
				<tr>
					<td class=legend style='font-size:16px' nowrap>{output_interface}:</td>
					<td>". Field_array_Hash($ForwardNICs,"ForwardNIC-$t",$ligne["ForwardNIC"],"style:font-size:16px")."</td>
					<td width=1%>&nbsp;</td>
				</tr>				
				<tr>
					<td class=legend style='font-size:16px' nowrap>{forward_to}:</td>
					<td>". Field_text("ForwardTo-$t",$ligne["ForwardTo"],"font-size:16px;width:450px")."</td>
					<td width=1%>". help_icon("{forward_to_iptables_explain}")."</td>
				</tr>
				<tr><td colspan=3>&nbsp;</td></tr>
			</table>
		</center>
	</td>				
	</tr>				
				
	<tr>
		<td colspan=3 align='right'><hr>". button($bt,"Save$t()",22)."</td>
	</tr>		
	</table>
</div>			
<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	var ID=$ID;
	$('#flexRT{$_GET["t"]}').flexReload();
	ExecuteByClassName('SearchFunction');
	if(ID==0){YahooWinHide();}
}

function SaveCHK$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function LinkInBoundGroup$t(ID){
	var RID=$ID;
	document.getElementById('source_group-$t').value=ID;
	LoadAjaxTiny('in-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}
function LinkOutbBoundGroup$t(ID){
	var RID=$ID;
	document.getElementById('dest_group-$t').value=ID;
	LoadAjaxTiny('out-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}	
function LinkPortGroup$t(ID){
	var RID=$ID;
	document.getElementById('destport_group-$t').value=ID;
	LoadAjaxTiny('port-$t','$page?groupname=yes&gpid='+ID);
	if(RID>0){Save$t();}
}
function Delgroup1$t(){
	var RID=$ID;
	document.getElementById('source_group-$t').value=0;
	document.getElementById('in-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}
function Delgroup2$t(){
	var RID=$ID;
	document.getElementById('dest_group-$t').value=0;
	document.getElementById('out-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}
function Delgroup3$t(){
	var RID=$ID;
	document.getElementById('destport_group-$t').value=0;
	document.getElementById('port-$t').innerHTML='$AllSystems';
	if(RID>0){Save$t();}
}


function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('rule-save',  '$ID');
	XHR.appendData('rulename',  encodeURIComponent(document.getElementById('rulename-$t').value));
	XHR.appendData('proto',  document.getElementById('proto-$t').value);
	XHR.appendData('accepttype',  document.getElementById('accepttype-$t').value);
	XHR.appendData('table',  '$table');
	XHR.appendData('interface',  '$eth');
	if(document.getElementById('enabled-$t').checked){
	XHR.appendData('enabled',1); }else{ XHR.appendData('enabled',0); }
	
	if(document.getElementById('OverideNet-$t').checked){
	XHR.appendData('OverideNet',1); }else{ XHR.appendData('OverideNet',0); }
	XHR.appendData('source_group',  document.getElementById('source_group-$t').value);
	XHR.appendData('dest_group',  document.getElementById('dest_group-$t').value);
	XHR.appendData('destport_group',  document.getElementById('destport_group-$t').value);
	XHR.appendData('zOrder',  document.getElementById('zOrder-$t').value);
	XHR.appendData('ForwardTo',  document.getElementById('ForwardTo-$t').value);
	XHR.appendData('ForwardNIC',  document.getElementById('ForwardNIC-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}
function Dyn$t(){
	var RID=$ID;
	var LOCKFORWARD=$LOCKFORWARD;
	var source_group=$source_group;
	var dest_group={$dest_group};
	var destport_group=$destport_group;
	
	if(LOCKFORWARD==1){
		document.getElementById('ForwardNIC-$t').disabled=true;
		document.getElementById('ForwardTo-$t').disabled=true;
	}
	
	if(RID==0){return;}
	if(source_group>0){
		LoadAjaxTiny('in-$t','$page?groupname=yes&gpid='+source_group);
	}
	if(dest_group>0){
		LoadAjaxTiny('out-$t','$page?groupname=yes&gpid='+dest_group);
	}
	if(destport_group>0){
		LoadAjaxTiny('port-$t','$page?groupname=yes&gpid='+destport_group);
	}

}
		
Dyn$t();

</script>";
echo $tpl->_ENGINE_parse_body($html);
	
}

function groupname(){
	$ID=$_GET["gpid"];
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$ligne['GroupName']=utf8_encode($ligne['GroupName']);
	echo $ligne['GroupName'];
	
}

function rule_save(){
	$ID=$_POST["rule-save"];
	$_POST["rulename"]=mysql_escape_string2(url_decode_special_tool($_POST["rulename"]));

	
	if($ID==0){
		$sql="INSERT IGNORE INTO iptables_main (`rulename`,`proto`,`accepttype`,`enabled`,
		`OverideNet`,`MOD`,`eth`,`source_group`,`dest_group`,`destport_group`,`zOrder`,`ForwardTo`,`ForwardNIC`)
		VALUES('{$_POST["rulename"]}','{$_POST["proto"]}','{$_POST["accepttype"]}',
		'{$_POST["enabled"]}','{$_POST["OverideNet"]}','{$_POST["table"]}','{$_POST["interface"]}',
		'{$_POST["source_group"]}','{$_POST["dest_group"]}','{$_POST["destport_group"]}',
		'{$_POST["zOrder"]}','{$_POST["ForwardTo"]}','{$_POST["ForwardNIC"]}')";
	}else{
		$sql="UPDATE iptables_main SET `rulename`='{$_POST["rulename"]}',
		`proto`='{$_POST["proto"]}',
		`MOD`='{$_POST["table"]}',
		`eth`='{$_POST["interface"]}',
		`accepttype`='{$_POST["accepttype"]}',
		`enabled`='{$_POST["enabled"]}',
		`OverideNet`='{$_POST["OverideNet"]}',
		`source_group`='{$_POST["source_group"]}',
		`dest_group`='{$_POST["dest_group"]}',
		`destport_group`='{$_POST["destport_group"]}',
		`ForwardTo`='{$_POST["ForwardTo"]}',
		`ForwardNIC`='{$_POST["ForwardNIC"]}',
		`zOrder`='{$_POST["zOrder"]}'
		WHERE ID='$ID'";
		
	}
	
	$q=new mysql();
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n$sql";}
}


function iptables_status(){
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$sock=new sockets();
	$EnableArticaAsGateway=intval($sock->GET_INFO("EnableArticaAsGateway"));
	
	$ethC=new system_nic($eth);
	$isFW=$ethC->isFW;
	$isFWAcceptNet=$ethC->isFWAcceptNet;
	$isFWLogBlocked=$ethC->isFWLogBlocked;
	if($ethC->Bridged==1){
		$text=$tpl->_ENGINE_parse_body("{warn_this_interface_is_bridged_to}");
		$text=str_replace("%s", $ethC->BridgedTo, $text);
		$error_bridge=FATAL_WARNING_SHOW_128($text);
		
	}
	
	if(preg_match("#^br([0-9]+)#", $eth,$re)){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT isFW,isFWAcceptNet,isFWLogBlocked FROM nics_bridge WHERE ID='{$re[1]}'","artica_backup"));
		if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
		$isFW=$ligne["isFW"];
		$isFWAcceptNet=$ligne["isFWAcceptNet"];
		$isFWLogBlocked=$ligne["isFWLogBlocked"];
		$error_bridge=null;
		
	}
	if(!is_numeric($isFWAcceptNet)){$isFWAcceptNet=1;}
	if(!is_numeric($isFWLogBlocked)){$isFWLogBlocked=0;}
	$t=time();
	
	$EnableArticaAsGateway=Paragraphe_switch_img('{ARTICA_AS_GATEWAY}','{ARTICA_AS_GATEWAY_EXPLAIN}',"EnableArticaAsGateway-$t",$EnableArticaAsGateway,null,550);
	
	$p=Paragraphe_switch_img("{activate_firewall_nic}", "{activate_firewall_nic_explain}","isFW-$t",$isFW,null,550);
	$p1=Paragraphe_switch_img("{trust_local_networks}", "{trust_local_networks_explain}","isFWAcceptNet-$t",$isFWAcceptNet,null,550);
	$p2=Paragraphe_switch_img("{isFWLogBlocked}", "{isFWLogBlocked_explain}","isFWLogBlocked-$t",$isFWLogBlocked,null,550);
	
	$html="<div style='width:98%' class=form>
	$error_bridge
	<table style='width:100%'>
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply_firewall_rules}","Save$t();Loadjs('firewall.restart.php')",22)."</td>
		</tr>
		<tr>
			<td colspan=2>
				$EnableArticaAsGateway
				<div style='text-align:right'><hr>". button("{apply}","SaveGateway$t()",22)."</div>
				</td>
		</tr>		
		<tr>
			<td colspan=2>$p</td>
		</tr>
		<tr>
			<td colspan=2>$p1</td>
		</tr>	
		<tr>
			<td colspan=2>$p2</td>
		</tr>			
		<tr>
			<td colspan=2 align='right'><hr>". button("{apply}","Save$t()",22)."</td>
		</tr>
	</table>
	</div>
	<script>
var xSave$t= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	RefreshTab('main_firewall_table_$eth');
}

	

function Save$t(){
	var XHR = new XHRConnection();
	XHR.appendData('eth',  '$eth');
	XHR.appendData('isFW',  document.getElementById('isFW-$t').value);
	XHR.appendData('isFWLogBlocked',  document.getElementById('isFWLogBlocked-$t').value);
	XHR.appendData('isFWAcceptNet',  document.getElementById('isFWAcceptNet-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
		
	}	

function SaveGateway$t(){
	var XHR = new XHRConnection();
	XHR.appendData('EnableArticaAsGateway',  document.getElementById('EnableArticaAsGateway-$t').value);
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}
	
</script>					
					
";
	
	
echo $tpl->_ENGINE_parse_body($html);	
	
}

function isFW_save(){
	$eth=$_POST["eth"];
	
	if(preg_match("#^br([0-9]+)#", $eth,$re)){
		$q=new mysql();
		$q->QUERY_SQL("UPDATE `nics_bridge` SET 
		`isFW`='{$_POST["isFW"]}',
		`isFWLogBlocked`='{$_POST["isFWLogBlocked"]}',
		`isFWAcceptNet`='{$_POST["isFWAcceptNet"]}'		
		WHERE `ID`='{$re[1]}'","artica_backup");
		if(!$q->ok){echo $q->mysql_error;}
		return;
	}	
	
	
	$ethC=new system_nic($eth);
	$ethC->isFW=$_POST["isFW"];
	$ethC->isFWAcceptNet=$_POST["isFWAcceptNet"];
	$ethC->isFWLogBlocked=$_POST["isFWLogBlocked"];
	$ethC->SaveNic();
}

	
function iptables_table(){
	
	if($_GET["table"]=="STATUS"){iptables_status();exit;}
	
	$page=CurrentPageName();
	$tpl=new templates();
	$eth=$_GET["eth"];
	$ethC=new system_nic($eth);
	$iptable=$_GET["table"];
	$title=$tpl->javascript_parse_text("$eth $ethC->NICNAME {{$iptable}}");
	$new=$tpl->javascript_parse_text("{new_rule}");
	$rulename=$tpl->javascript_parse_text("{rulename}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$type=$tpl->javascript_parse_text("{type}");
	$delete=$tpl->javascript_parse_text("{delete}");
	$apply=$tpl->javascript_parse_text("{apply}");
	
	$t=time();
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
<script>

function LoadTable$t(){
	$('#flexRT$t').flexigrid({
	url: '$page?rules=yes&eth=$eth&t=$t&table=$iptable',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'zOrder', width :20, sortable : true, align: 'center'},
		{display: '$rulename', name : 'rulename', width : 423, sortable : true, align: 'left'},
		{display: '$enabled', name : 'enabled', width : 70, sortable : true, align: 'center'},
		{display: '$type', name : 'accepttype', width : 70, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'up', width : 70, sortable : true, align: 'center'},
		{display: '&nbsp;', name : 'down', width : 70, sortable : true, align: 'center'},
		{display: '$delete', name : 'del', width : 70, sortable : false, align: 'center'},
	
	],
	buttons : [
		{name: '$new', bclass: 'add', onpress : NewRule$t},
		{name: '$apply', bclass: 'Apply', onpress : Apply$t},
		
	],
	searchitems : [
		{display: '$rulename', name : 'rulename'},
	],
	sortname: 'zOrder',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 15,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true
	
	});
}
var xRuleGroupUpDown$t= function (obj) {
	var res=obj.responseText;
	if(res.length>3){alert(res);return;}
	$('#flexRT$t').flexReload();
	ExecuteByClassName('SearchFunction');
}

function RuleGroupUpDown$t(ID,direction){
	var XHR = new XHRConnection();
	XHR.appendData('rule-order', ID);
	XHR.appendData('direction', direction);
	XHR.appendData('eth', '$eth');
	XHR.appendData('table', '$iptable');
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function DeleteRule$t(ID){
	if(!confirm('$delete '+ID+' ?')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rule-delete', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function Apply$t(){
	Loadjs('firewall.restart.php');
}

function ChangEnabled$t(ID){
	var XHR = new XHRConnection();
	XHR.appendData('rule-enable', ID);
	XHR.sendAndLoad('$page', 'POST',xRuleGroupUpDown$t);
}

function NewRule$t() {
	Loadjs('$page?ruleid=0&eth=$eth&t=$t&table=$iptable',true);
}	
LoadTable$t();
</script>
";
echo $html;	
	
}
function rule_delete(){
	$ID=$_POST["rule-delete"];
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM iptables_main WHERE ID='$ID'","artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	
}
function rule_enable(){
	$ID=$_POST["rule-enable"];
	$q=new mysql();
	$sql="SELECT `enabled` FROM iptables_main WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	
	if($ligne["enabled"]==0){
		$sql="UPDATE iptables_main SET enabled='1' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	}
	if($ligne["enabled"]==1){
		$sql="UPDATE iptables_main SET enabled='0' WHERE ID='$ID'";
		$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	}	
	
	
	

}
function rule_order(){
	$ID=$_POST["rule-order"];
	$direction=$_POST["direction"];
	$eth=$_POST["eth"];
	$table=$_POST["table"];
	
	
	//up =1, Down=0
	$q=new mysql();
	$sql="SELECT `zOrder`,`MOD`,`eth` FROM iptables_main WHERE ID='$ID'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;return;}
	$table=$ligne["MOD"];
	$eth=$ligne["eth"];
	
	$OlOrder=$ligne["zOrder"];
	if($direction==1){$NewOrder=$OlOrder+1;}else{$NewOrder=$OlOrder-1;}
	$sql="UPDATE iptables_main SET zOrder='$OlOrder' WHERE `zOrder`='$NewOrder' AND `MOD`='$table' AND `eth`='$eth'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
	$sql="UPDATE iptables_main SET zOrder='$NewOrder' WHERE ID='$ID'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
	
	$results=$q->QUERY_SQL("SELECT ID FROM iptables_main WHERE `MOD`='$table' AND `eth`='$eth' ORDER BY zOrder","artica_backup");
	if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
	$c=1;
	while ($ligne = mysql_fetch_assoc($results)) {
		$ID=$ligne["ID"];
		$q->QUERY_SQL("UPDATE iptables_main SET zOrder='$c' WHERE ID='$ID'","artica_backup");
		if(!$q->ok){echo "Error line:".__LINE__."\n".$q->mysql_error;}
		$c++;
	
	}
		
	
	
}


function rules(){
//ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');
$tpl=new templates();
$MyPage=CurrentPageName();
$q=new mysql();
$eth=$_GET["eth"];
$table_type=$_GET["table"];

$t=$_GET["t"];
$FORCE_FILTER=null;
$search='%';
$table="(SELECT iptables_main.* FROM iptables_main WHERE iptables_main.eth='$eth' AND iptables_main.MOD='$table_type' 
	ORDER BY zOrder ) as t";
$page=1;
	
if($q->COUNT_ROWS("iptables_main","artica_backup")==0){json_error_show("No datas - COUNT_ROWS",1);}
if(isset($_POST["sortname"])){
	if($_POST["sortname"]<>null){
		$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
	}
}
	
if (isset($_POST['page'])) {$page = $_POST['page'];}
	
$searchstring=string_to_flexquery();
if($searchstring<>null){
	$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$total = $ligne["TCOUNT"];
	
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){json_error_show($q->mysql_error."\n$sql",1);}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	if(mysql_num_rows($results)==0){json_error_show($q->mysql_error,1);}
	$rules=$tpl->_ENGINE_parse_body("{rules}");
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$val=0;
		$color="black";
		$time=null;
		$mkey=md5(serialize($ligne));
		$delete=imgsimple("delete-24.png",null,"DeleteRule$t('{$ligne["ID"]}')");
		$enabled=Field_checkbox("enabled-{$ligne["ID"]}", 1,$ligne["enabled"],"ChangEnabled{$_GET["t"]}('{$ligne["ID"]}')");
		$up=imgsimple("arrow-up-16.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',0)");
		$down=imgsimple("arrow-down-18.png","","RuleGroupUpDown{$_GET["t"]}('{$ligne["ID"]}',1)");
		$source_group=inbound_object($ligne["source_group"]);
		$dest_group=inbound_object($ligne["dest_group"]);
		$destport_group=inbound_object($ligne["destport_group"],1);
		$FORWARD_TEXT=null;
		
		$explain=$tpl->_ENGINE_parse_body("<div style='font-size:12px'>{from} $source_group {to} $dest_group $destport_group</div>");
		$rulename=utf8_encode($ligne["rulename"]);
		if($ligne["enabled"]==0){$color="#8a8a8a";}
		
		$js="Loadjs('$MyPage?ruleid={$ligne["ID"]}&eth=$eth&t={$_GET["t"]}&table=$table_type',true);";
		
		$ACTION="cloud-goto-32.png";
		if($ligne["accepttype"]=="DROP"){
			$ACTION="cloud-deny-32.png";
		}
		
		if($ligne["accepttype"]=="RETURN"){
			$ACTION="arrow-right-32.png";
		}
		
		
		if($table_type=="FORWARD"){
			if($ligne["accepttype"]=="ACCEPT"){
				if($ligne["ForwardNIC"]<>null){$FORWARD_TEXT="{$ligne["ForwardNIC"]}:";}
				$FORWARD_TEXT=$FORWARD_TEXT.$tpl->_ENGINE_parse_body("<div style='font-size:12px'>{forward_to} {$ligne["ForwardTo"]}</div>");
			}
			
		}
		
		if($ligne["enablet"]==1){
			$time=$tpl->_ENGINE_parse_body("<span style='font-size:12px;color:$color'>{time_restriction}</span>").":&nbsp;".buildtime($ligne);
		}
		
		
		
		if($ligne["zOrder"]==1){$up=null;}
		if($ligne["zOrder"]==0){$up=null;}
		$data['rows'][] = array(
			'id' => "$mkey",
			'cell' => array(
			"<span style='font-size:14px;font-weight:bold;color:$color'>{$ligne["zOrder"]}</span>",
			"<a href=\"javascript:blur();\" OnClick=\"javascript:$js\"
			style='font-size:14px;font-weight:bold;text-decoration:underline;color:$color'>$rulename</a>$explain$FORWARD_TEXT$time",
			"<div style=\"margin-top:5px\">$enabled</div>",
			"<span style='font-size:14px;font-weight:bold;color:$color'><img src='img/$ACTION'></span>",
			"<div style=\"margin-top:5px\">$up</div>",
			"<div style=\"margin-top:5px\">$down</div>",
			"<div style=\"margin-top:4px\">$delete</div>")
		);
	}
	
	echo json_encode($data);	
	
}

function buildtime($ligne){
	$color="black";
	if($ligne["enabled"]==0){$color="#8a8a8a";}
	$tpl=new templates();
	
	$array_days=array(
			1=>"monday",
			2=>"tuesday",
			3=>"wednesday",
			4=>"thursday",
			5=>"friday",
			6=>"saturday",
			7=>"sunday",
	);
	
	$TTIME=unserialize($ligne["time_restriction"]);
	
	$DDS=array();
	
	while (list ($num, $maks) = each ($array_days)){
		if($TTIME["D{$num}"]==1){$DDS[]="{{$maks}}";}
		
	}
	
	if( (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ftime"])) AND  (preg_match("#^[0-9]+:[0-9]+#", $TTIME["ttime"]))  ){
		$DDS[]="{from} {$TTIME["ftime"]} {to_time} {$TTIME["ttime"]}";
	}
	
	if(count($DDS)>0){
		return $tpl->_ENGINE_parse_body("<div style='font-size:12px'>".@implode(" ", $DDS))."<div>";
	}
	
	
}

function inbound_object($ID,$asport=0){
	if($ID==0){
		$val="{AllSystems}";
		if($asport==1){$val="{port} {AllPorts}";}
		return $val;
	}
	
	$q=new mysql_squid_builder();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT GroupName FROM webfilters_sqgroups WHERE ID='$ID'"));
	$ligne['GroupName']=utf8_encode($ligne['GroupName']);
	if($asport==1){return "{port}: 
	<a href=\"javascript:blur();\" 
	OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$ID&table-acls-t=0');\"
	style=\"font-size:12px;text-decoration:underline\">
	{$ligne['GroupName']}</a>";}
	
	
	return "<a href=\"javascript:blur();\"
	OnClick=\"javascript:Loadjs('squid.acls.groups.php?AddGroup-js=yes&ID=$ID&table-acls-t=0');\"
	style=\"font-size:12px;text-decoration:underline\">
	{$ligne['GroupName']}</a>";
	
	
	
}
function EnableArticaAsGateway_save(){
	$sock=new sockets();
	$sock->SET_INFO("EnableArticaAsGateway", $_POST["EnableArticaAsGateway"]);
	$sock->getFrameWork("services.php?KernelTuning=yes");
	
}	



