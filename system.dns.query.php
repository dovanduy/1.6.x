<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.tcpip.inc');
	include_once('ressources/externals/dns/class.dns.inc');
	include_once('ressources/class.resolv.conf.inc');
	include_once(dirname(__FILE__)."/ressources/externals/Net_DNS2/DNS2.php");
	
	if(!Privs()){die();}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){query();exit;}
	
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$title=$tpl->javascript_parse_text("{dns_query}");
	echo "YahooWinBrowse('700','$page?popup=yes','$title',true)";
	
}
	
function Privs(){
	$user=new usersMenus();
	if($user->AsSystemAdministrator){return true;}
	if($user->AsPostfixAdministrator){return true;}
	if($user->AsMailBoxAdministrator){return true;}
	if($user->AsSquidAdministrator){return true;}
	if($user->AsDnsAdministrator){return true;}
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$dns=new DNSTypes();
	
	$t=time();
	
	$html="
	<div style='width:98%' class=form>
	<table style='width:100%'>
	<tr>
		<td class=legend style='font-size:18px'>{hostname_or_domain}:</td>
		<td>".Field_text("query-$t","www.google.com","font-size:18px;width:99%",null,null,null,false,"CheckSave$t(event)")."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:18px'>{type}:</td>
		<td>".Field_array_Hash($dns->types_by_id, "QTYPE-$t",1,null,null,0,"font-size:18px")."</td>
	</tr>				
	<tr>
		<td class=legend style='font-size:18px'>{DNS_SERVER}:</td>
		<td>".Field_text("dns-host-$t","8.8.8.8","font-size:18px;width:99%",null,null,null,false,"CheckSave$t(event)")."</td>
	</tr>
	<tr>
		<td colspan=2 align=right><hr>". button("{send}","Save$t()",22)	."</td>
	</tr>				
</table>			
</div>	
<div id='dns-query-results-$t'></div>
<script>
function CheckSave$t(e){
	if(!checkEnter(e)){return;}
	Save$t();
}

function Save$t(){
	var query=document.getElementById('query-$t').value;
	var type=document.getElementById('QTYPE-$t').value;
	var dns=document.getElementById('dns-host-$t').value;
	LoadAjax('dns-query-results-$t','$page?query='+query+'&type='+type+'&dns='+dns,true);
}


</script>
	";
	
echo $tpl->_ENGINE_parse_body($html);
	
}

function query(){
	$resolv=new resolv_conf();
	$dns=new DNSTypes();
	$DNS=array();
	$extendanswer=true;
	$q=new mysql_squid_builder();
	$sql="SELECT *  FROM dns_servers ORDER BY zOrder";
	$results = $q->QUERY_SQL($sql);
	$c=1;
	$DNS[]=$_GET["dns"];
	while ($ligne = mysql_fetch_assoc($results)) {
		$DNS[]=$ligne["dnsserver"];
		
	}
	
	$query=$_GET["query"];
	if($query==null){$query="www.artica.fr";}
	
	if($resolv->MainArray["DNS1"]<>null){$DNS[]=$resolv->MainArray["DNS1"];}
	if($resolv->MainArray["DNS2"]<>null){$DNS[]=$resolv->MainArray["DNS2"];}
	if($resolv->MainArray["DNS3"]<>null){$DNS[]=$resolv->MainArray["DNS3"];}
	
	$type=$dns->types_by_id[$_GET["type"]];
	echo "<div style='width:98%' class=form>";
	
	while (list ($index, $dnsA) = each ($DNS) ){
		$type = "A";
		echo "<hr><div style='font-size:18px;margin-top:10px'>DNS $dnsA - $type</div>";
		$rs = new Net_DNS2_Resolver(array('nameservers' => array($dnsA)));
		
		try {
			$result = $rs->query($query, "A");
		} catch(Net_DNS2_Exception $e) {
			echo "<div style='font-size:16px;color:red'>Failed to query: " . $e->getMessage() . "</div>";
			continue;
		}
		
		echo "<ul>";
		foreach($result->answer as $record){
		
			echo "<li><strong>Name:</strong> {$record->name}</li>";
			echo "<li><strong>Type:</strong> {$record->type}</li>";
			echo "<li><strong>Address:</strong> {$record->address}</li>";
			echo "<li><strong>TTL:</strong> {$record->ttl}</li>";

		}
		echo "</ul>";
		
	}
	
	echo "</div>";

}
	
	


