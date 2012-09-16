<?php
session_start();
$GLOBALS["ICON_FAMILY"]="COMPUTERS";
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.user.inc');
	include_once('ressources/class.computers.inc');
	include_once('ressources/class.ocs.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsInventoryAdmin){die();}
	if(isset($_GET["last24h"])){GraphLast24H();exit;}
	
	page();
	
function page(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$sock=new sockets();
	$EnableScanComputersNet=$sock->GET_INFO("EnableScanComputersNet");
	if(!is_numeric($EnableScanComputersNet)){$EnableScanComputersNet=0;}
	
	
	if($EnableScanComputersNet==0){
		$ScanComputersNet=Paragraphe("64-infos.png","{periodic_scan}",'{periodic_scan_net_text}',"javascript:Loadjs('network.periodic.scan.php')","periodic_scan_net_text",210);
		$html="<center style='font-size:18px;margin:30px'>{ERRRO_SCANNET_MUST_ENABLED}<p>&nbsp;</p>$ScanComputersNet</center>";
		echo $tpl->_ENGINE_parse_body($html);
		return;
	}
	
	
	$html="<div id='last24h'></div>
	
	
	<script>
		LoadAjax('last24h','$page?last24h=yes&uid={$_GET["uid"]}');
	</script>
	";
	
	
	echo $html;
	
	
	
}


function GraphLast24H(){
	$page=CurrentPageName();
	$tpl=new templates();		
	$uid=$_GET["uid"];
	$cmp=new computers($uid);
	$MAC=$cmp->ComputerMacAddress;
	
	$sql="SELECT DATE_FORMAT(zDate,'%Y-%m-%d %H') as tdate , SUM(live) as tl,MAC FROM computers_available
	GROUP BY DATE_FORMAT(zDate,'%Y-%m-%d %H'),MAC
	HAVING MAC='$MAC' AND tdate>DATE_SUB(NOW(),INTERVAL 24 HOUR) ORDER BY tdate";
	
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}	
	if(mysql_num_rows($results)==0){echo $tpl->_ENGINE_parse_body("$title<center style='margin:50px'><H2>{error_no_datas}</H2>$sql</center>");return;}	
	
	
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$ttdate=explode(" ", $ligne["tdate"]);
		$xdata[]=$ttdate[1];
		$ydata[]=$ligne["tl"];
	}		
	
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".day.". md5("{$_GET["uid"]}").".png";
	$gp=new artica_graphs();
	$gp->width=550;
	$gp->height=220;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
	$gp->title=null;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";
	$gp->line_green();
	
	$image="<center style='margin-top:10px'><img src='$targetedfile'></center>";
	if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file!",__FUNCTION__,__FILE__,__LINE__);$image=null;}	
	echo $tpl->_ENGINE_parse_body($html.$image);
	
}
