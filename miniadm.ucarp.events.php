<?php
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");

$users=new usersMenus();
if(!$users->AsAnAdministratorGeneric){throw new ErrorException("Bad gateway",500);}
if(isset($_GET["table"])){table();exit;}
events();



function events(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error="<p class=text-error>{this_feature_is_disabled_corp_license}</p>";
		
	}
	
	echo $tpl->_ENGINE_parse_body("$error<div class='form-search' style='margin:10px;text-align:right'>
			<input type='text' id='s-$t' class='input-medium search-query' OnKeyPress=\"javascript:SearchQueryQ$t(event)\">
			<button type='button' class='btn' OnClick=\"javascript:SearchQuery$t()\">{search}</button>
			</div>
			<div class=BodyContentWork id='$t'></div>
	
			<script>
			function SearchQueryQ$t(e){
			if(!checkEnter(e)){return;}
			SearchQuery$t();
	}
	
			function SearchQuery$t(){
			var pp=encodeURIComponent(document.getElementById('s-$t').value);
			LoadAjax('$t','$page?table=yes&query='+pp)
	}
	
			SearchQuery$t();
			</script>");	
	
	
}
function table(){
	$_GET["query"]=url_decode_special_tool($_GET["query"]);
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	if(!isset($_GET["rp"])){$_GET["rp"]=150;}
	if($_GET["query"]<>null){
		$search=base64_encode($_GET["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?syslog-query=$search&prepend=ucarp&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));

	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("cmd.php?syslog-query=&prepend=ucarp&rp={$_POST["rp"]}&prefix={$_GET["prefix"]}")));
		$total=count($datas);
	}
	
	$today=$tpl->_ENGINE_parse_body("{today}");
	
	while (list ($key, $line) = each ($datas) ){
				$color="black";
			if(preg_match("#(ERROR|WARN|FATAL|UNABLE|Failed|not found|denied)#i", $line)){$color="#D61010";}
				
			$style="<span style='color:$color'>";
			$styleoff="</span>";
			
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?)\[([0-9]+)\]:\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=$re[6];
			$line=$re[7];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){$date=$today." ".date('H:i:s',strtotime($date));}else{$date=date('m-d H:i:s',strtotime($date));}
			$class=LineToClass($line);
			

			$tr[]="
			<tr class='$class'>
			<td style='font-size:12px' width=1% nowrap>$date</td>
			<td style='font-size:12px' width=1% nowrap>$host</td>
			<td style='font-size:12px' width=1% nowrap>$service</td>
			<td style='font-size:12px' width=1% nowrap>$pid</td>
			<td style='font-size:12px' width=99%>$line</td>
			</tr>
			";
			
			
			continue;	
		}
		
		if(preg_match("#^(.*?)\s+([0-9]+)\s+([0-9:]+)\s+(.*?)\s+(.*?):\s+(.*)#",$line,$re)){
			$date="{$re[1]} {$re[2]} ".date('Y')." {$re[3]}";
			$host=$re[4];
			$service=$re[5];
			$pid=null;
			$line=$re[6];
			$strtotime=strtotime($date);
			if(date("Y-m-d",$strtotime)==date("Y-m-d")){$date=$today." ".date('H:i:s',strtotime($date));}else{$date=date('m-d H:i:s',strtotime($date));}
			$class=LineToClass($line);
			$tr[]="
			<tr class='$class'>
			<td style='font-size:12px' width=1% nowrap>$date</td>
			<td style='font-size:12px' width=1% nowrap>$host</td>
			<td style='font-size:12px' width=1% nowrap>$service</td>
			<td style='font-size:12px' width=1% nowrap>$pid</td>
			<td style='font-size:12px' width=99%>$line</td>
			</tr>
			";
			continue;				
		}
	}

	echo $tpl->_ENGINE_parse_body("<table class='table table-bordered'>
		
			<thead>
				<tr>
					<th>{date}</th>
					<th>{host}</th>
					<th>&nbsp;</th>
					<th>PID</th>
					<th>{event}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
			
			</table>";

}