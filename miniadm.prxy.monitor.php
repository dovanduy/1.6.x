<?php
if( (isset($_GET["debug"])) OR  (isset($_GET["verbose"])) ){
		$GLOBALS["VERBOSE"]=true;
		ini_set('display_errors', 1);
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
}else{
	ini_set('display_errors', 0);
	ini_set('html_errors',0);
	ini_set('display_errors', 0);
	ini_set('error_reporting', 0);
}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.accesslogs.inc");



if(isset($_GET["monitor-parameters"])){monitor_parameters_js();exit;}
if(isset($_GET["monitor-parameters-popup"])){monitor_parameters_popup();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["toolbox"])){toolbox();exit;}
if(isset($_GET["status"])){services_status_start();exit;}
if(isset($_GET["services-status-table"])){services_status();exit;}
if(isset($_GET["accesslogs"])){accesslogs_table();exit;}
if(isset($_GET["events-list"])){events_search();exit;}
if(isset($_GET["proxy-service"])){proxy_service();exit;}
if(isset($_GET["radialValues"])){proxy_service_values();exit;}
if(isset($_POST["ReconfigureUfdb"])){ReconfigureUfdb();exit;}
if(isset($_GET["privileges"])){privileges();exit;}
if(isset($_POST["server_all_kbytes_in"])){monitor_parameters_save();exit;}

//PHP_AUTH_USER

$heads=GetHeads();
echo $heads;



function monitor_parameters_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$title=$tpl->javascript_parse_text("{parameters}");
	$html="YahooWin('680','$page?monitor-parameters-popup=yes&t=$t','$title')";
	echo $html;
}


function monitor_parameters_popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();	
	$server_all_kbytes_inT=$tpl->javascript_parse_text("{server_all_kbytes_in}");
	$server_all_kbytes_outT=$tpl->javascript_parse_text("{server_all_kbytes_out}");
	$HttpRequestsT=$tpl->javascript_parse_text("HTTP Requests/{seconds}");
	$active_requests=$tpl->javascript_parse_text("{active_requests}");
	
	$t=$_GET["t"];
	$buttonname="{apply}";
	
	$SquidMonitorParms=unserialize(base64_decode($sock->GET_INFO("SquidMonitorParms")));

	
	$server_all_kbytes_in=$SquidMonitorParms["server_all_kbytes_in"];
	$server_all_kbytes_out=$SquidMonitorParms["server_all_kbytes_out"];
	$HttpRequests=$SquidMonitorParms["HttpRequests"];
	$ActiveRequests=$SquidMonitorParms["ActiveRequests"];
	

	if(!is_numeric($server_all_kbytes_in)){$server_all_kbytes_in=250;}
	if(!is_numeric($server_all_kbytes_out)){$server_all_kbytes_out=250;}
	if(!is_numeric($HttpRequests)){$HttpRequests=150;}
	if(!is_numeric($ActiveRequests)){$ActiveRequests=150;}
	
	$html="
	<center id='anim-$t'></center>
	
	<div class=BodyContent >
	<table width=99% class=form>
	<tr>
		<td class=legend style='font-size:16px'>$server_all_kbytes_inT (max):</td>
		<td>". Field_text("server_all_kbytes_in-$t",$server_all_kbytes_in,"font-size:16px;width:120px",null,null,null,false,"CheckForm$t(event)")."&nbsp;kbytes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>$server_all_kbytes_outT (max):</td>
		<td>". Field_text("server_all_kbytes_out-$t",$server_all_kbytes_out,"font-size:16px;width:120px",null,null,null,false,"CheckForm$t(event)")."&nbsp;kbytes</td>
	</tr>
	<tr>
		<td class=legend style='font-size:16px'>$active_requests (max):</td>
		<td>". Field_text("ActiveRequests-$t",$ActiveRequests,"font-size:16px;width:120px",null,null,null,false,"CheckForm$t(event)")."&nbsp;rq/s</td>
	</tr>		
	<tr>
		<td class=legend style='font-size:16px'>$HttpRequestsT (max):</td>
		<td>". Field_text("HttpRequests-$t",$HttpRequests,"font-size:16px;width:120px",null,null,null,false,"CheckForm$t(event)")."&nbsp;rq/s</td>
	</tr>							
	<tr>
		<td colspan=2 align=right><hr>". button($buttonname, "Save$t()","18")."<td>
	</tr>
</table>
</div>
			<script>
	
			var x_Save$t=function (obj) {
				document.getElementById('anim-$t').innerHTML='';
				var results=obj.responseText;
				Loadjs('$page?radialValues=yes&t=$t');
				YahooWinHide();
				if(results.length>0){alert(results);return;}
			}
	
	function Save$t(){
		var XHR = new XHRConnection();
		XHR.appendData('server_all_kbytes_in',document.getElementById('server_all_kbytes_in-$t').value);
		XHR.appendData('server_all_kbytes_out',document.getElementById('server_all_kbytes_out-$t').value);
		XHR.appendData('HttpRequests',document.getElementById('HttpRequests-$t').value);
		XHR.appendData('ActiveRequests',document.getElementById('ActiveRequests-$t').value);
		AnimateDiv('anim-$t');
		XHR.sendAndLoad('$page', 'POST',x_Save$t);
	
	}
	 
	function CheckForm$t(e){
	if(checkEnter(e)){Save$t();}
	}
	 
	 
	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function monitor_parameters_save(){
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$tpl=new templates();
		echo $tpl->javascript_parse_text("{this_feature_is_disabled_corp_license}");
		return;
	}
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($_POST)), "SquidMonitorParms");
	
	
}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$ID=$_GET["ID"];
	$q=new mysql();
	
	$array["status"]='{services_status}';
	$array["proxy-service"]='{counters}';
	
	if(isset($_SESSION["uid"])){
		$array["accesslogs"]="{realtime_requests}";
		$array["privileges"]="{privileges}";
	}
	$t=time();
	
	if(isset($_GET["byenduser-interface"])){
		$byenduser="&byenduser-interface=yes";
		unset($array["status"]);
		unset($array["privileges"]);
		unset($array["accesslogs"]);
	}
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&size={$_GET["size"]}&t=$t$byenduser\" style='font-size:14px'><span>$ligne</span></a></li>\n");
	}
	echo "
	<div id='prxytab'>
	<ul>". implode("\n",$html)."</ul>
	</div>
	<script>
	$(document).ready(function(){
	$('#prxytab').tabs();
	});
	</script>";	
	
}

function proxy_service(){
	
	
	$defaultsize=250;
	

	
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$users=new usersMenus();
	$seconds=$tpl->javascript_parse_text("{seconds}");
	$server_all_kbytes_in=$tpl->javascript_parse_text("{server_all_kbytes_in}");
	$server_all_kbytes_out=$tpl->javascript_parse_text("{server_all_kbytes_out}");
	$active_requests=$tpl->javascript_parse_text("{active_requests}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$ipaddr=$tpl->_ENGINE_parse_body("{ipaddr}");
	$cpunum=intval($users->CPU_NUMBER);
	$maxload=$cpunum+1;	
	$parameters_link=null;
	$Storage_Capacity=$tpl->javascript_parse_text("{Storage_Capacity}");
	$parameters=$tpl->_ENGINE_parse_body("{parameters}");
	$t=$_GET["t"];
	$currentload="<canvas id='CurrentLoad' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>Current System load<span id='CurrentLoad-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$Memory="<canvas id='realMemory' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>System Memory Usage<span id='realMemory-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$cpuUSage="<canvas id='CpuUsage' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>Proxy CPU Usage<span id='CpuUsage-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$TotalAccounted	="<canvas id='TotalAccounted' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>Proxy Memory Usage<span id='TotalAccounted-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$HttpRequests="<canvas id='HttpRequests' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>HTTP Requests/$seconds<span id='HttpRequests-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$ActiveRequests="<canvas id='ActiveRequests' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>$active_requests<span id='ActiveRequests-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$ActiveRequestsMembers="<canvas id='ActiveRequestsMembers' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>$active_requests $members<span id='ActiveRequestsMembers-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$ActiveRequestsIpaddr="<canvas id='ActiveRequestsIpaddr' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>$active_requests $ipaddr<span id='ActiveRequestsIpaddr-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	
	
	
	$server_all_kbytes_inTR="<canvas id='server_all_kbytes_in' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>$server_all_kbytes_in/$seconds<span id='server_all_kbytes_in-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	$server_all_kbytes_outTR="<canvas id='server_all_kbytes_out' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>$server_all_kbytes_out/$seconds<span id='server_all_kbytes_out-title' style='padding-left:5px;font-weight:bold'></span></center>";	
	
	$FilesDescriptors="<canvas id='FilesDescriptors' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
	<center style='font-size:16px'>File Descriptors<span id='FilesDescriptors-title' style='padding-left:5px;font-weight:bold'></span></center>";
	
	
	$f[]=$currentload;
	$f[]=$Memory;
	$f[]=$cpuUSage;
	$f[]=$ActiveRequests;
	$f[]=$ActiveRequestsMembers;
	$f[]=$ActiveRequestsIpaddr;
	$f[]=$TotalAccounted;
	$f[]=$FilesDescriptors;
	$f[]=$HttpRequests;
	$f[]=$server_all_kbytes_inTR;
	$f[]=$server_all_kbytes_outTR;
	
	
	$StorageCapacity=unserialize(base64_decode($sock->getFrameWork("squid.php?StorageCapacity=yes")));
	$countStorages=count($StorageCapacity);
	for($i=0;$i<$countStorages;$i++){
		$f[]="<canvas id='Storage$i' width='$defaultsize' height='$defaultsize ' style='margin:10px'></canvas>
		<center style='font-size:16px'>Storage Capacity Kid ". ($i+1) ."</center>";
		
		$js[]="        Storage$i = new steelseries.RadialBargraph('Storage$i', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Storage ". ($i+1) ."',
                            unitString: '%',
                            threshold: 70,
							maxValue:100,
                            lcdVisible: true
                        });	";
	}
	
	$storages=CompileTr4($f,false,null,true);
	
	if(isset($_GET["byenduser-interface"])){
		$storages=CompileTr3($f,false,null,true);
		
	}
	
	if(isset($_SESSION["uid"])){
		$parameters_link="<div style='width:100%;text-align:right'><a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$page?monitor-parameters=yes&t=$t');\"
		style='font-size:16px;text-decoration:underline;font-weight:bold;color:#DF0000'>$parameters</a></div>";
	}
	
	if(isset($_GET["loadjs"])){
		$loadthis="<script type='text/javascript' language='javascript' src='/js/tween-min.js'></script>";	
		
		$parameters_link="<div style='width:1160;text-align:right'>
				<a href=\"javascript:blur()\" OnClick=\"javascript:Loadjs('$page?monitor-parameters=yes&t=$t')\" 
	id='none' class=\"Button2014 Button2014-success Button2014-lg\"  style=\"font-size:30px;text-transform:capitalize\" 
	>&laquo;&nbsp;$parameters&nbsp;&raquo;</a></div>";
				
			
	}
	
	$html="
		$loadthis
		<script type='text/javascript' language='javascript' src='/js/steelseries-min.js'></script>		
		$parameters_link
		<div id='counter-$t'>
		
		$storages
		</div>	
			
<script>
	
	var sections = [steelseries.Section(0, 25, 'rgba(0, 0, 220, 0.3)'),
    	steelseries.Section(25, 50, 'rgba(0, 220, 0, 0.3)'),
        steelseries.Section(50, 75, 'rgba(220, 220, 0, 0.3)') ],

        areas = [steelseries.Section(75, 100, 'rgba(220, 0, 0, 0.3)')],

            // Define value gradient for bargraph
            valGrad = new steelseries.gradientWrapper(  0,
                                                        100,
                                                        [ 0, 0.33, 0.66, 0.85, 1],
                                                        [ new steelseries.rgbaColor(0, 0, 200, 1),
                                                          new steelseries.rgbaColor(0, 200, 0, 1),
                                                          new steelseries.rgbaColor(200, 200, 0, 1),
                                                          new steelseries.rgbaColor(200, 0, 0, 1),
                                                          new steelseries.rgbaColor(200, 0, 0, 1) ]);	
       CpuUsageRadial = new steelseries.RadialBargraph('CpuUsage', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
							maxValue:100,
                            titleString: 'CPU Usage',
                            unitString: '%',
                            threshold: 50,
                            lcdVisible: true
                        });
                        
                        
       LoadCurrent = new steelseries.Radial('CurrentLoad', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'LOAD',
                            threshold: 2,
                            maxValue: '$maxload',
                            lcdVisible: true
                        }); 
                        
         FilesDescriptors = new steelseries.Radial('FilesDescriptors', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Files',
                            threshold: 2,
                            maxValue: '100',
                            lcdVisible: true
                        });  

         ActiveRequests = new steelseries.Radial('ActiveRequests', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Files',
                            threshold: 2,
                            maxValue: '150',
                            lcdVisible: true
                        });   
                        
                
         ActiveRequestsMembers = new steelseries.Radial('ActiveRequestsMembers', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Files',
                            threshold: 2,
                            maxValue: '150',
                            lcdVisible: true
                        });    

                        
         ActiveRequestsIpaddr = new steelseries.Radial('ActiveRequestsIpaddr', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Files',
                            threshold: 2,
                            maxValue: '150',
                            lcdVisible: true
                        });                        

                        
                       
                        

                        
	realMemory = new steelseries.Radial('realMemory', {
                            gaugeType: steelseries.GaugeType.TYPE1,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Memory',
                             unitString: '%',
                            threshold: 70,
                            maxValue: '100',
                            lcdVisible: true
                        }); 

                        
                        
                        
                        
                       
			
       HttpRequestsRadial = new steelseries.RadialBargraph('HttpRequests', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'HTTP requests',
                            unitString: '$seconds',
                            threshold: 50,
							maxValue:150,
                            lcdVisible: true
                        });	
                        
       TotalAccounted = new steelseries.RadialBargraph('TotalAccounted', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Memory %',
                            threshold: 50,
							maxValue:100,
                            lcdVisible: true
                        });	                        
                  

                        
        server_all_kbytes_in = new steelseries.RadialBargraph('server_all_kbytes_in', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Kb IN',
                            unitString: 'KB',
                            threshold: 50,
							maxValue:250,
                            lcdVisible: true
                        });	
                        
                        
        server_all_kbytes_out = new steelseries.RadialBargraph('server_all_kbytes_out', {
                            gaugeType: steelseries.GaugeType.TYPE3,
                            size: $defaultsize,
                            section: sections,
                            area: areas,
                            titleString: 'Kb OUT',
                            unitString: 'KB',
                            threshold: 50,
							maxValue:250,
                            lcdVisible: true
                        });	                        
                        
        ".@implode("\n", $js)."
			
		function RefreshRadial$t(){
			if(!document.getElementById('counter-$t')){return;}
			Loadjs('$page?radialValues=yes&t=$t');
		}
		$('#flexRT$t').remove();
		RefreshRadial$t();
</script>
			
			
		
			
			
";
	
	echo $html;
	
	
}

function proxy_service_values(){
	$t=$_GET["t"];
	$sock=new sockets();
	$users=new usersMenus();
	
	$cpunum=intval($users->CPU_NUMBER);
	$array_load=sys_getloadavg();
	$org_load=$array_load[2];
	$maxload=$cpunum+1;	
	
	$t=$_GET["t"];
	$squid5mn=unserialize(base64_decode($sock->getFrameWork("squid.php?5mncounter=yes")));
	$realMemory=unserialize(base64_decode($sock->getFrameWork("services.php?realMemory=yes")));
	$CounterInfos=unserialize(base64_decode($sock->getFrameWork("squid.php?CounterInfos=yes")));
	$StorageCapacity=unserialize(base64_decode($sock->getFrameWork("squid.php?StorageCapacity=yes")));
	
	
	
	$countStorages=count($StorageCapacity);
	for($i=0;$i<$countStorages;$i++){
		$js[]=" Storage$i.setValueAnimated('{$StorageCapacity[$i]}');";
	}
	
	$SquidMonitorParms=unserialize(base64_decode($sock->GET_INFO("SquidMonitorParms")));
	
	$server_all_kbytes_in=$SquidMonitorParms["server_all_kbytes_in"];
	$server_all_kbytes_out=$SquidMonitorParms["server_all_kbytes_out"];
	$HttpRequests=$SquidMonitorParms["HttpRequests"];
	$ActiveRequests=$SquidMonitorParms["ActiveRequests"];
	
	if(!is_numeric($server_all_kbytes_in)){$server_all_kbytes_in=250;}
	if(!is_numeric($server_all_kbytes_out)){$server_all_kbytes_out=250;}
	if(!is_numeric($HttpRequests)){$HttpRequests=150;}
	if(!is_numeric($ActiveRequests)){$ActiveRequests=150;}
	
	if(!isset($squid5mn["cpu_usage"])){$squid5mn["cpu_usage"]=0;}
	
	$squid5mn["cpu_usage"]=round($squid5mn["cpu_usage"],2);
	$squid5mn["client_http.requests"]=round($squid5mn["client_http.requests"],2);
	$squid5mn["server.all.kbytes_in"]=round($squid5mn["server.all.kbytes_in"],2);
	$squid5mn["server.all.kbytes_out"]=round($squid5mn["server.all.kbytes_out"],2);
	
	$sock->getFrameWork("squid.php?active-requests=yes");
	$ActiveRequestsR=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/active_requests.inc"));
	$ActiveRequestsNumber=count($ActiveRequestsR["CON"]);
	$ActiveRequestsIpaddr=count($ActiveRequestsR["IPS"]);
	$ActiveRequestsMembers=count($ActiveRequestsR["USERS"]);
	
	if(!is_numeric($ActiveRequestsNumber)){$ActiveRequestsNumber=0;}
	if(!is_numeric($ActiveRequestsIpaddr)){$ActiveRequestsIpaddr=0;}
	if(!is_numeric($ActiveRequestsMembers)){$ActiveRequestsMembers=0;}
	
	      
	
	
	echo "
	CpuUsageRadial.setValueAnimated('{$squid5mn["cpu_usage"]}');
	if(document.getElementById('CpuUsage-title')){
		document.getElementById('CpuUsage-title').innerHTML='{$squid5mn["cpu_usage"]}%';
	}		

	HttpRequestsRadial.setMaxValue('$HttpRequests');
	HttpRequestsRadial.setValueAnimated('{$squid5mn["client_http.requests"]}');
	if(document.getElementById('HttpRequests-title')){
		document.getElementById('HttpRequests-title').innerHTML='{$squid5mn["client_http.requests"]}&nbsp;RQ/s';
	}		
	
	ActiveRequests.setMaxValue('$ActiveRequests');
	ActiveRequests.setValueAnimated('$ActiveRequestsNumber');
	if(document.getElementById('ActiveRequests-title')){
		document.getElementById('ActiveRequests-title').innerHTML='$ActiveRequestsNumber';
	}

	ActiveRequests.setMaxValue('$ActiveRequests');
	ActiveRequests.setValueAnimated('$ActiveRequestsNumber');
	if(document.getElementById('ActiveRequests-title')){
		document.getElementById('ActiveRequests-title').innerHTML='$ActiveRequestsNumber';
	}

	ActiveRequestsIpaddr.setMaxValue('$ActiveRequests');
	ActiveRequestsIpaddr.setValueAnimated('$ActiveRequestsIpaddr');
	if(document.getElementById('ActiveRequestsIpaddr-title')){
		document.getElementById('ActiveRequestsIpaddr-title').innerHTML='$ActiveRequestsIpaddr';
	}	
	
	ActiveRequestsMembers.setMaxValue('$ActiveRequests');
	ActiveRequestsMembers.setValueAnimated('$ActiveRequestsMembers');
	if(document.getElementById('ActiveRequestsMembers-title')){
		document.getElementById('ActiveRequestsMembers-title').innerHTML='$ActiveRequestsMembers';
	}	
	
	LoadCurrent.setValueAnimated('$org_load');
	if(document.getElementById('CurrentLoad-title')){
		document.getElementById('CurrentLoad-title').innerHTML='$org_load';
	}		
	
	realMemory.setValueAnimated('{$realMemory["ram"]["percent"]}');
	if(document.getElementById('realMemory-title')){
		document.getElementById('realMemory-title').innerHTML='{$realMemory["ram"]["percent"]}%';
	}	
	
	server_all_kbytes_in.setMaxValue('$server_all_kbytes_in');
	server_all_kbytes_out.setMaxValue('$server_all_kbytes_out');
	if(document.getElementById('server_all_kbytes_in-title')){
		document.getElementById('server_all_kbytes_in-title').innerHTML='$server_all_kbytes_in&nbsp;kbytes';
	}
	if(document.getElementById('server_all_kbytes_out-title')){
		document.getElementById('server_all_kbytes_out-title').innerHTML='$server_all_kbytes_out&nbsp;kbytes';
	}	
	
	server_all_kbytes_in.setValueAnimated('{$squid5mn["server.all.kbytes_in"]}');
	server_all_kbytes_out.setValueAnimated('{$squid5mn["server.all.kbytes_out"]}');
	
	FilesDescriptors.setMaxValue('{$CounterInfos["MAXFD"]}');
	FilesDescriptors.setValueAnimated('{$CounterInfos["CURFD"]}');
	if(document.getElementById('FilesDescriptors-title')){
		document.getElementById('FilesDescriptors-title').innerHTML='{$CounterInfos["CURFD"]}';
	}
	
	TotalAccounted.setValueAnimated('{$CounterInfos["TotalAccounted"]}');
	if(document.getElementById('TotalAccounted-title')){
		document.getElementById('TotalAccounted-title').innerHTML='{$CounterInfos["TotalAccounted"]}';
	}	
	
	
	
	
	
	 ".@implode("\n", $js)."
	setTimeout('RefreshRadial$t()',8000);	
	";
	
}


function accesslogs_table(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	$GLOBAL_SIZE=$_GET["size"];	
	$title=$tpl->_ENGINE_parse_body("{realtime_requests}");
	$stopRefresh=$tpl->javascript_parse_text("{stop_refresh}");
	$refresh=$tpl->javascript_parse_text("{refresh}");
	$button1="{name: '<strong id=refresh-$t>$stopRefresh</stong>', bclass: 'Reload', onpress : StartStopRefresh$t},";
	$table_size=1560;
	$events_size=1113;
	
	if($GLOBAL_SIZE==1600){
		$table_size=1560;
		$events_size=1113;
	}
	
	$html="
	<span id=\"StopRefreshNewTable$t\"></span>
	<input type='hidden' id='refresh$t' value='1'>		
	<table class='flexRT$t' style='display: none' id='flexRT$t'></table>
<script>
$(document).ready(function(){
	$('#flexRT$t').flexigrid({
		url: '$page?events-list=yes',
		dataType: 'json',
		colModel : [
			{display: '$zdate', name : 'zDate', width :120, sortable : true, align: 'left'},
			{display: '$proto', name : 'proto', width :33, sortable : false, align: 'left'},
			{display: '$uri', name : 'events', width : $events_size, sortable : false, align: 'left'},
			{display: '$member', name : 'mmeber', width : 216, sortable : false, align: 'left'},
			],
			
	buttons : [
			$button1$button2
			],
			
		
		searchitems : [
			{display: '$events', name : 'events'}
			],
		sortname: 'zDate',
		sortorder: 'desc',
		usepager: true,
		title: '$title',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $table_size,
		height: 640,
		singleSelect: true,
		rpOptions: [100,200,300,500,1000,1500,2000,2500,5000]
		
		});   
	});	
	
function  StartStopRefresh$t(){
	var ratxt='$stopRefresh';
	var rstxt='$refresh';
	var refresh=document.getElementById('refresh$t').value;
	if(refresh==1){
		document.getElementById('refresh$t').value=0;
		document.getElementById('refresh-$t').innerHTML='$refresh';
	}else{
		document.getElementById('refresh$t').value=1;
		document.getElementById('refresh-$t').innerHTML='$stopRefresh';	
		$('#flexRT$t').flexReload();
	}
}	
	
function StartRefresh$t(){
	if(!document.getElementById('flexRT$t')){return;}
	var refresh=document.getElementById('refresh$t').value;
	
	if(refresh==1){
		if(!document.getElementById('StopRefreshNewTable$t')){
			$('#flexRT$t').flexReload();
		}
	}
	
	$('#counter-$t').remove();	
	setTimeout('StartRefresh$t()',8000);
	
}	
	
</script>	
	";
	
	echo $html;
	
}



function GetHeads(){
	$page=CurrentPageName();
	if(isset($_SERVER["PHP_AUTH_USER"])){
		BuildSession($_SERVER["PHP_AUTH_USER"]);
	
	}
	
	$tpl=new templates();
	$APP_ARTICA_PRXYLOGS=$tpl->javascript_parse_text("{APP_ARTICA_PRXYLOGS}");	
	$p=new pagebuilder();
	$size=1600;
$html="
<!DOCTYPE html>
<html lang=\"$tpl->language\">
<head>
  <meta http-equiv=\"X-UA-Compatible\" content=\"IE=9; IE=8\">
  <meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-type\" />
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"css/squid.default.css\" />
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"/ressources/templates/endusers/css/s.css\" charset=\"utf-8\"  />
  <link  rel=\"stylesheet\" type=\"text/css\" href=\"/ressources/templates/endusers/css/jquery.css\" charset=\"utf-8\"  />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/thickbox.css\" media=\"screen\"/>
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.qtip.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.jgrowl.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.cluetip.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.treeview.css\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/thickbox.css\" media=\"screen\"/>
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/jquery.qtip.css\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/flexigrid.pack.css\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/ui.selectmenu.css\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/fileuploader.css\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/fonts.css.php\" />
 <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/mobiscroll-2.1.custom.min.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"/css/rounded.css\" />
  
  	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-1.8.0.min.js\"></script>
  	<script type=\"text/javascript\" language=\"javascript\" src=\"/ressources/templates/endusers/js/jquery-ui-1.8.23.custom.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/mouse.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/md5.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/XHRConnection.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/float-barr.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/TimersLogs.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/artica_confapply.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/edit.user.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/cookies.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/default.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jqueryFileTree.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.easing.1.3.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/thickbox-compressed.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.simplemodal-1.3.3.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.jgrowl_minimized.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cluetip.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.blockUI.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.treeview.async.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.tools.min.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.qtip.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.kwicks-1.5.1.pack.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/flexigrid.pack.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery-ui-timepicker-addon.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/ui.selectmenu.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/jquery.cookie.js\"></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/fileuploader.js\"></script>  
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/tween-min.js\"></script>
	<script type='text/javascript' language='javascript' src='/js/jquery.uilock.min.js'></script>
	<script type=\"text/javascript\" language=\"javascript\" src=\"/js/steelseries-min.js\"></script>	
	<script type='text/javascript' language='javascript' src='/js/jquery.blockUI.js'></script>  
    <title>$APP_ARTICA_PRXYLOGS</title>
</head>
<body>". $p->YahooBody()."
<div class=BodyContent id='start-section' style='width:{$size}px'></div>
<script>
	LoadAjax('start-section','$page?tabs=yes&size=$size');
	MessagesTophideAllMessages();
</script>
";
return $html;
}

function events_search(){
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();
$q=new mysql_squid_builder();
$GLOBALS["Q"]=$q;
	
	
		
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}

	if($_POST["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=$search&rp={$_POST["rp"]}")));
		$total=count($datas);
		
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=&rp={$_POST["rp"]}")));
		$total=count($datas);
	}
	
		
	$pageStart = ($page-1)*$rp;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){
		if($_POST["sortname"]=="zDate"){
			if($_POST["sortorder"]=="asc"){
				krsort($datas);
			}
		}
	$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	$today=date("Y-m-d");
	
	$squidacc=new accesslogs();
	$squidacc->stats=false;
	
	$c=0;
	while (list ($key, $line) = each ($datas) ){
		$color="black";
		$return_code_text=null;
		$ff=array();
			$color="black";
			if(preg_match('#(.+?)\s+(.+?)\s+squid\[.+?:\s+MAC:(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#i',$line,$re)){
				$re[6]=trim($re[6]);
				if($re[5]=="-"){
					if( ($re[6]<>"-") && !is_null($re[6])){
						$re[5]=$re[6];
						$re[6]="-";
					}	
				}
				
			
				//$ff[]="F=1";
				//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
				//$array["RE"]=@implode("<br>", $ff);
				$uri=$re[9];
				
				$date=date("Y-m-d H:i:s",strtotime($re[7]));
				$mac=$re[3];
				$ip=$re[4];
				$user=$re[5];
				$dom=$re[6];
				$proto=$re[8];
				$return_code=$re[10];
				$size=$re[11];
				
				$array["IP"]=$ip;
				$array["URI"]=$uri;
				$array["DATE"]=$date;
				$array["MAC"]=$mac;
				$array["USER"]=$user;
				$array["USER"]=$user;
				$array["PROTO"]=$proto;
				$array["CODE"]=$return_code;
				$array["SIZE"]=$size;
				$array["LINE"]=$line;
				$mline=$squidacc->Buildline($array);
				if(is_array($mline)){$data['rows'][] =$mline;$c++;}
				continue;
						
			}
			
			
			if(preg_match('#(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+.*?\s+MAC:(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#',$line,$re)){
				$time=$re[3];
				$prox=$re[4];
				$mac=$re[5];
				$date=date("Y-m-d H:i:s",strtotime($re[9]));
				$ip=$re[6];
				$user=$re[8];
				
				$proto=$re[10];
				$return_code=$re[12];
				$size=$re[13];	
				$uri=$re[11];
				//$ff[]="F=2";
				//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
				//$array["RE"]=@implode("<br>", $ff);
				$array["PROXY"]=$prox;
				$array["IP"]=$ip;
				$array["URI"]=$uri;
				$array["DATE"]=$date;
				$array["MAC"]=$mac;
				$array["USER"]=$user;
				$array["USER"]=$user;
				$array["PROTO"]=$proto;
				$array["CODE"]=$return_code;
				$array["SIZE"]=$size;
				$array["LINE"]=$line;
				
				$mline=$squidacc->Buildline($array);
				if(is_array($mline)){$data['rows'][] =$mline;$c++;}
				continue;
				
			}
			

			
			if(preg_match('#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\]:\s+(.*?)\s+(.+)\s+(.+)\s+.+?"([A-Z]+)\s+(.+?)\s+.*?"\s+([0-9]+)\s+([0-9]+)#',$line,$re)){
	
			if(preg_match("#(TCP_DENIED|ERR_CONNECT_FAIL)#", $line)){
					$color="#BA0000";
				}			
				    $dates="{$re[1]} {$re[2]} ".date('Y'). " {$re[3]}";
					$ip=$re[4];
					$user=$re[5];
					
					$re[6]=trim($re[6]);
					if($re[5]=="-"){
						if( ($re[6]<>"-") && !is_null($re[6])){
							$re[5]=$re[6];
							$re[6]="-";
						}	
					}

					//$ff[]="F=3";
					//while (list ($a, $b) = each ($re) ){$ff[]="$a=$b";}
					//$array["RE"]=@implode("<br>", $ff);
					
					$date=date("Y-m-d H:i:s",strtotime($dates));
					$uri=$re[8];
					$proto=$re[7];
					$return_code=$re[8];
					$size=$re[9];
					
					$array["IP"]=$ip;
					$array["URI"]=$uri;
					$array["DATE"]=$date;
					$array["MAC"]=$mac;
					$array["USER"]=$user;
					$array["USER"]=$user;
					$array["PROTO"]=$proto;
					$array["CODE"]=$return_code;
					$array["SIZE"]=$size;
					$array["LINE"]=$line;					
					$mline=$squidacc->Buildline($array);
					if(is_array($mline)){$data['rows'][] =$mline;$c++;}				
					
					continue;
						
			}				

		writelogs("Not Filtered: $line",__FUNCTION__,__FILE__,__LINE__);

	}
	$data['total'] = $c;
	echo json_encode($data);	
}

function services_status_start(){
	$page=CurrentPageName();
	$t=$_GET["t"];
	$html="
	<div id='toolbox'></div>
	<div id='services-status'></div>

	<script>
		LoadAjax('toolbox','$page?toolbox=yes');
		$('#flexRT$t').remove();
		$('#counter-$t').remove();
	</script>
	
			
	";
	
	echo $html;
}

function services_status(){
	$page=CurrentPageName();
	$sock=new sockets();
	$ini=new Bs_IniHandler();
	$ini2=new Bs_IniHandler();
	$tpl=new templates();
	$users=new usersMenus();
	$html=null;
	$ini->loadString(base64_decode($sock->getFrameWork('cmd.php?squid-ini-status=yes')));
	$ini2->loadString(base64_decode($sock->getFrameWork('cmd.php?cicap-ini-status=yes')));
	
	$DisableAnyCache=$sock->GET_INFO("DisableAnyCache");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	$AsSquidLoadBalancer=$sock->GET_INFO("AsSquidLoadBalancer");
	$EnableRemoteStatisticsAppliance=$sock->GET_INFO("EnableRemoteStatisticsAppliance");

	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	$SquidBoosterMem=$sock->GET_INFO("SquidBoosterMem");
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if(!is_numeric($SquidBoosterMem)){$SquidBoosterMem=0;}
	if(!is_numeric($DisableAnyCache)){$DisableAnyCache=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if(!is_numeric($AsSquidLoadBalancer)){$AsSquidLoadBalancer=0;}
	if(!is_numeric($EnableRemoteStatisticsAppliance)){$EnableRemoteStatisticsAppliance=0;}
	
	$squid_status=DAEMON_STATUS_ROUND("SQUID",$ini,null,1);
	$dansguardian_status=DAEMON_STATUS_ROUND("DANSGUARDIAN",$ini,null,1);
	$kav=DAEMON_STATUS_ROUND("KAV4PROXY",$ini,null,1);
	$cicap=DAEMON_STATUS_ROUND("C-ICAP",$ini2,null,1);
	$APP_PROXY_PAC=DAEMON_STATUS_ROUND("APP_PROXY_PAC",$ini,null,1);
	$APP_SQUIDGUARD_HTTP=DAEMON_STATUS_ROUND("APP_SQUIDGUARD_HTTP",$ini,null,1);
	$APP_UFDBGUARD=DAEMON_STATUS_ROUND("APP_UFDBGUARD",$ini,null,1);
	$APP_FRESHCLAM=DAEMON_STATUS_ROUND("APP_FRESHCLAM",$ini,null,1);
	$APP_ARTICADB=DAEMON_STATUS_ROUND("APP_ARTICADB",$ini,null,1);
	if($users->PROXYTINY_APPLIANCE){$APP_ARTICADB=null;}
	if($EnableRemoteStatisticsAppliance==1){$APP_ARTICADB=null;}
	$squid=new squidbee();
	
	
	if($EnableKerbAuth==1){
		$APP_SAMBA_WINBIND=DAEMON_STATUS_ROUND("SAMBA_WINBIND",$ini,null,1);
	}
	
	$md=md5(date('Ymhis'));
	if($_SESSION["uid"]<>null){
		if(!$users->WEBSTATS_APPLIANCE){
			$swappiness=intval($sock->getFrameWork("cmd.php?sysctl-value=yes&key=".base64_encode("vm.swappiness")));
			$sock=new sockets();
			$swappiness_saved=unserialize(base64_decode($sock->GET_INFO("kernel_values")));
			if(!is_numeric($swappiness_saved["swappiness"])){
				if($swappiness>30){
					$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{high_swap_value}",
							"{high_swap_value_text}","Loadjs('squid.perfs.php')");
				}
					
			}
		
			if($AsSquidLoadBalancer==1){$SquidAsSeenDNS=1;}
			if($SquidActHasReverse==1){$SquidAsSeenDNS=1;}
		
			$SquidAsSeenDNS=$sock->GET_INFO("SquidAsSeenDNS");
			if(!is_numeric($SquidAsSeenDNS)){$SquidAsSeenDNS=0;}
			if( count($squid->dns_array)==0){
				if($SquidAsSeenDNS==0){
					$tr[]=DAEMON_STATUS_ROUND_TEXT("warning-panneau-42.png","{add_dns_in_config}",
							"{add_dns_in_config_perf_explain}","Loadjs('squid.popups.php?script=dns')");
				}
			}
		
		}
	}
	
	$CicapEnabled=0;
	if($users->C_ICAP_INSTALLED){
		$CicapEnabled=$sock->GET_INFO("CicapEnabled");
		if(!is_numeric($CicapEnabled)){$CicapEnabled=0;}
	}
	
	$squid_status=null;
	$ini=new Bs_IniHandler();
	$ini->loadString(base64_decode($sock->getFrameWork('squid.php?smp-status=yes')));
	
	while (list ($index, $line) = each ($ini->_params) ){
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__."::$index -> DAEMON_STATUS_ROUND<br>\n";}
		$tr[]=DAEMON_STATUS_ROUND($index,$ini,null,1);
	}
	
	
	
	
	
	if($SquidBoosterMem>0){
		if($DisableAnyCache==0){
			$tr[]=squid_booster_smp();
		}
	}
	
	
	$tr[]=$squid_status;
	$tr[]=$APP_SAMBA_WINBIND;
	$tr[]=$dansguardian_status;
	$tr[]=$kav;
	$tr[]=$cicap;
	$tr[]=$APP_PROXY_PAC;
	$tr[]=$APP_SQUIDGUARD_HTTP;
	$tr[]=$APP_UFDBGUARD;
	$tr[]=$APP_FRESHCLAM;
	$tr[]=$APP_ARTICADB;
	
		$html=$tpl->_ENGINE_parse_body(CompileTr4($tr,true,null,true));
	
	$html="
			
	<div style='float:right'>". imgtootltip("refresh-32.png","{refresh}","LoadAjax('services-status','$page?services-status-table=yes');")."</div>
	<center ><div style='width:100%;margin:10px'>$html</div></center>";
	echo $html;
	
}

function squid_booster_smp(){
	$sock=new sockets();
	$array=unserialize(base64_decode($sock->getFrameWork("squid.php?smp-booster-status=yes")));
	if(count($array)==0){return;}
	$html[]="
			<div style='min-height:115px'>
			<table>
			<tr><td colspan=2 style='font-size:14px;font-weight:bold'>Cache(s) Booster</td></tr>
			";
	while (list ($proc, $pourc) = each ($array)){
		$html[]="<tr>
		<td width=1% nowrap style='font-size:13px;font-weight:bold'>Proc #$proc</td><td width=1% nowrap>". pourcentage($pourc)."</td></tr>";
	}
	$html[]="</table></div>";

	return RoundedLightGreen(@implode("\n", $html));
}


function toolbox(){
	$page=CurrentPageName();
	if(!isset($_SESSION["uid"])){
		echo "<script>LoadAjax('services-status','$page?services-status-table=yes');</script>";
		return;}
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	$EnableUfdbGuard=$sock->EnableUfdbGuard();
	
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}

	
	$scripts="	var x_ReconfigureUfdb= function (obj) {
		var res=obj.responseText;
		if (res.length>3){alert(res);}
		RefreshTab('prxytab');
	}		
		
	function ReconfigureUfdb(){
			var XHR = new XHRConnection();
		    XHR.appendData('ReconfigureUfdb', 'yes');
		    AnimateDiv('squid-services');
		    XHR.sendAndLoad('$page', 'POST',x_ReconfigureUfdb); 
		
	}";
	
	if($EnableUfdbGuard==1){
		$ufdbbutt=Paragraphe("service-check-64.png", "UfdbGuard", "{reconfigure_webfilter_service}",
				"javascript:ReconfigureUfdb()");
	}

	$squid_rotate=Paragraphe("events-rotate-64.png", "Logs rotation", "{squid_logrotate_perform}",
			"javascript:Loadjs('squid.perf.logrotate.php?img=events-rotate-32-squid&src=events-rotate-32.png')");

	$reconfigure=Paragraphe("rebuild-64.png", "{reconfigure}", "{reconfigure}",
			"javascript:Loadjs('squid.compile.progress.php');");	
	
	
	$debug_compile=Paragraphe("64-logs.png", "{compile_in_debug}", "{compile_in_debug}",
			"javascript:Loadjs('squid.debug.compile.php');");
	
	$current_sessions=Paragraphe("64-logs.png", "{sessions}", "{display_current_sessions}",
			"javascript:Loadjs('squid.squidclient.clientlist.php');");
	$performances=Paragraphe("performance-tuning-64.png", "{performance}", "{display_performance_status}",
			"javascript:Loadjs('squid.squidclient.info.php');");
	
	$restart_all_services=Paragraphe("service-restart-64.png", "{restart}", "{restart_all_services}",
			"javascript:Loadjs('squid.restart.php');");

	
	$restart_service_only=Paragraphe("service-restart-32.png", "{restart_onlysquid}", "{restart_onlysquid}",
			"javascript:Loadjs('squid.restart.php?onlySquid=yes');");
	

	$html="<table style='width:100%'>
	<tr>
		<td>$ufdbbutt</td>
		<td>$current_sessions</td>
		<td>$performances</td>
		<td>$squid_rotate</td>
	</tr>
		<td>$reconfigure</td>
		<td>$debug_compile</td>
		<td>$restart_service_only</td>
		<td>&nbsp;</td>				
	</tr>
	</table>
	
	<script>
		LoadAjax('services-status','$page?services-status-table=yes');
	</script>
		
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function ReconfigureUfdb(){
	$sock=new sockets();
	$sock->getFrameWork("squid.php?rebuild-filters=yes&force=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");

}
function privileges(){
	$tpl=new templates();
	$sock=new sockets();
	$EnableSambaVirtualsServers=0;
	include_once(dirname(__FILE__)."/ressources/class.translate.rights.inc");
	$cr=new TranslateRights(null, null);
	$r=$cr->GetPrivsArray();
	
	
	

	$ht=array();
	
	$ht[]=$tpl->_ENGINE_parse_body("<H2>{$_SESSION["uid"]}::{privileges}</H2>");
	
	$ht[]="
			<center>
			<table style='width:80%' class=form>";
	while (list ($key, $val) = each ($r) ){
		if(!isset($_SESSION["privileges_array"][$key])){continue;}
		if($_SESSION["privileges_array"][$key]){
			$ht[]="<tr><td width=1%><img src='img/arrow-right-16.png'></td><td><span style='font-size:14px'>{{$key}}</span></td></tr>";
		}
	}

	$users=new usersMenus();
	if($users->SAMBA_INSTALLED){
		$EnableSambaVirtualsServers=$sock->GET_INFO("EnableSambaVirtualsServers");
		if(!is_numeric($EnableSambaVirtualsServers)){$EnableSambaVirtualsServers=0;}
	}

	if($EnableSambaVirtualsServers==1){
		if(count($_SESSION["VIRTUALS_SERVERS"])>0){
			$ht[]="<tr><td colspan=2 style='font-size:16px;font-weight:bolder'>{virtual_servers}</td></tr>";
			while (list ($key, $val) = each ($_SESSION["VIRTUALS_SERVERS"]) ){
				$ht[]="<tr><td width=1%><img src='img/arrow-right-16.png'></td><td><span style='font-size:14px'>$key</span></td></tr>";
			}
		}
	}

	$ht[]="</table></center>";
	echo $tpl->_ENGINE_parse_body(@implode("\n", $ht));
}

?>