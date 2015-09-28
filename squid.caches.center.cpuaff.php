<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["affectprocess"])){affectprocess();exit;}
	
	js();
function js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{cpu_affinity}");
	echo "YahooWin6('905','$page?popup=yes','$title')";
}
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$CPU_NUMBER=$sock->getFrameWork("services.php?CPU-NUMBER=yes");
	$q=new mysql();
	$t=time();
	$ARRAY_CPU[0]="{all}";
	for($i=1;$i<$CPU_NUMBER+1;$i++){
		$ARRAY_CPU[$i]="CPU #$i";
	}
	
	if(!$q->FIELD_EXISTS("squid_caches_center","CPUAF","artica_backup")){$sql="ALTER TABLE `squid_caches_center` ADD `CPUAF` smallint(2) NOT NULL DEFAULT 0";$q->QUERY_SQL($sql,"artica_backup");}
	
	
	$sql="SELECT cpu,CPUAF FROM squid_caches_center GROUP BY cpu,CPUAF ORDER BY cpu";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	
	$html[]="<div style='width:98%' class=form>";
	$html[]="<table style='width:100%'>";
	
	
	while ($ligne = mysql_fetch_assoc($results)) {
		
	 
		
	 $html[]="<tr>";
	 $html[]="<td class=legend style='font-size:26px'>{process} {$ligne["cpu"]}:<td>";
	 $html[]="<td style='font-size:26px'>". Field_array_Hash($ARRAY_CPU, "CPUFOR-{$ligne["cpu"]}",$ligne["CPUAF"],"style:font-size:26px")."</td>";
	 $html[]="<td>". button("{apply}","SaveCPU{$ligne["cpu"]}()",26)."</td>";
	 $html[]="</tR>";
	 
	 $js[]="function SaveCPU{$ligne["cpu"]}(){";
	 $js[]="\tvar XHR = new XHRConnection();";
	 $js[]="\tXHR.appendData('affectprocess','{$ligne["cpu"]}');";
	 $js[]="\tXHR.appendData('affectcpu',document.getElementById('CPUFOR-{$ligne["cpu"]}').value);";
	 $js[]="\tXHR.sendAndLoad('$page', 'POST',xSave$t);";	
	 $js[]="}";
	 
		
	}
	
	$html[]="</table>
		<center style='margin:20px;margin-top:50px'>". button("{restart}","Loadjs('squid.restart.php');",28)."
		</center>";
			
			
			
	
	$html[]="</div>";
	$html[]="<script>";
	$html[]="var xSave$t= function (obj) {";
	$html[]="\tvar results=obj.responseText;";
	$html[]="\tif(results.length>0){alert(results);}";
	$html[]="\tif(document.getElementById('CACHE_CENTER_TABLEAU')){";
	$html[]="\t\tvar CACHE_CENTER_TABLEAU=document.getElementById('CACHE_CENTER_TABLEAU').value;";
	$html[]="\t\t$('#'+CACHE_CENTER_TABLEAU).flexReload();";
	$html[]="\t}";
	$html[]="}";
	$html[]=@implode("\n", $js);
	$html[]="</script>";
	
	$html_final=@implode("\n", $html);
	echo $tpl->_ENGINE_parse_body($html_final);
	
}

function affectprocess(){
	
	$sql="UPDATE squid_caches_center SET CPUAF='{$_POST["affectcpu"]}' WHERE cpu='{$_POST["affectprocess"]}'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{you_need_to_restart_service_take_effet}",1);
	$sock=new sockets();
	$sock->getFrameWork("squid.php?prepare-build=yes");
	
	
	
	
}
	
