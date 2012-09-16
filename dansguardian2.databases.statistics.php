<?php
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.dansguardian.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.artica.graphs.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}


if(isset($_GET["instant_update_daily"])){instant_update_daily();exit;}
if(isset($_GET["global_update_daily"])){global_update_daily();exit;}


page();


function page(){
	$page=CurrentPageName();
	$t=time();
	$html="<table style='width:100%' class=form>
	<tr>
		<td align='center'><div id='gp0-$t'></div>
	</tr>	
	<tr>
		<td align='center'><div id='gp1-$t'></div>
	</tr>
	</table>
	<script>
		LoadAjax('gp1-$t','$page?global_update_daily=yes');
		LoadAjax('gp0-$t','$page?instant_update_daily=yes');
	</script>
	";
	
	echo $html;
	
}


function global_update_daily(){
	$q=new mysql_squid_builder();
	$tpl=new templates();	
	
	$sql="SELECT SUM(AddedItems)as tsum,DATE_FORMAT(zDate,'%Y-%m-%d') as tdate ,DATE_FORMAT(zDate,'%H') as thour 
	FROM webfilters_bigcatzlogs 
	GROUP BY tdate,thour HAVING tdate=DATE_FORMAT(NOW(),'%Y-%m-%d') order by tdate DESC";
	
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		$xdata[]=$ligne["thour"];
		$ydata[]=$ligne["tsum"];
	}
	$title=$tpl->_ENGINE_parse_body("{graph_instant_update_squid_byhour}");
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". time().".png";
	$gp=new artica_graphs();
	$gp->width=800;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{hour}");
	$gp->title=$title;
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";	
	
	$gp->line_green();
	if(is_file($targetedfile)){
		$html[]="<center><img src='$targetedfile'></center>";
	}

	echo @implode("\n", $html);
}




function instant_update_daily(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$sql="SELECT SUM(CountItems) as tcount FROM instant_updates WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d')";
	
	$TOTALITEMOS=$q->COUNT_ROWS("instant_updates");
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$itemsToday=$ligne["tcount"];
	if(!is_numeric($itemsToday)){$itemsToday=0;}
	writelogs("$sql=`$itemsToday/$TOTALITEMOS`",__FUNCTION__,__FILE__,__LINE__);
	
	if(!$q->ok){$html[]="<span style='color:#AE0000'>$q->mysql_error</span>";}
	
	$sql="SELECT ID,DATE_FORMAT(zDate,'%H:%i:%s') as tcount 
	FROM instant_updates 
	WHERE DATE_FORMAT(zDate,'%Y-%m-%d')=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$itemsH=$ligne["tcount"];
	$idt=date("Y-m-d H:i:s",$ligne["ID"]);
	$itemsH =$itemsH ." (v $idt)";
	if(!$q->ok){$html[]="<span style='color:#AE0000'>$q->mysql_error</span>";}
	
	
	if($itemsToday>0){
		$itemsToday=numberFormat($itemsToday,0,""," ");
		$text=$tpl->_ENGINE_parse_body("{today_squid_instant_update}");
		$text=str_replace("XX", "<strong>$itemsToday</strong>", $text);
		$text=str_replace("YY", "<strong>$itemsH</strong>", $text);
		$html[]="<div style='font-size:16px;text-align:left'>$text</div>";		
			
		$sql="SELECT SUM(CountItems) as tcount , HOUR(zDate) as thour,
		DATE_FORMAT(zDate,'%Y-%m-%d') as tday FROM instant_updates 
		GROUP BY thour,tday HAVING tday=DATE_FORMAT(NOW(),'%Y-%m-%d') ORDER BY thour";
		
		$results=$q->QUERY_SQL($sql);
	
		if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
		while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){$xdata[]=$ligne["thour"];$ydata[]=$ligne["tcount"];}
		
		$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".". time().".png";
		$gp=new artica_graphs();
		$gp->width=800;
		$gp->height=350;
		$gp->filename="$targetedfile";
		$gp->xdata=$xdata;
		$gp->ydata=$ydata;
		$gp->y_title=null;
		$gp->x_title=$tpl->_ENGINE_parse_body("{hours}");
		$gp->title=$tpl->_ENGINE_parse_body("{graph_instant_update_squid_byhour} {today}");
		$gp->margin0=true;
		$gp->Fillcolor="blue@0.9";
		$gp->color="146497";
	
		$gp->line_green();
		if(is_file($targetedfile)){$html[]="<center><img src='$targetedfile'></center>";}
	}
	
	$sql="SELECT SUM(CountItems) as tcount , WEEK(zDate) as thour,
	DAY(zDate) as tday FROM instant_updates 
	GROUP BY thour,tday HAVING thour=WEEK(NOW()) ORDER BY tday";	
	$xdata=array();
	$ydata=array();
	$results=$q->QUERY_SQL($sql);

	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){$xdata[]=$ligne["tday"];$ydata[]=$ligne["tcount"];}
	
	$targetedfile="ressources/logs/".basename(__FILE__).".".__FUNCTION__.".WEEK.". time().".png";
	$gp=new artica_graphs();
	$gp->width=800;
	$gp->height=350;
	$gp->filename="$targetedfile";
	$gp->xdata=$xdata;
	$gp->ydata=$ydata;
	$gp->y_title=null;
	$gp->x_title=$tpl->_ENGINE_parse_body("{days}");
	$gp->title=$tpl->_ENGINE_parse_body("{graph_instant_update_squid_byday}");
	$gp->margin0=true;
	$gp->Fillcolor="blue@0.9";
	$gp->color="146497";

	$gp->line_green();
	if(is_file($targetedfile)){$html[]="<center><img src='$targetedfile'></center>";}





echo @implode("\n", $html);
}