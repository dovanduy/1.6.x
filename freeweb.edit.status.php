<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.artica.graphs.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["status"])){status();exit;}
	if(isset($_GET["tabs"])){tabs();exit;}
	if(isset($_GET["traffic-today"])){traffic_today();exit;}
	if(isset($_GET["memory-today"])){memory_today();exit;}
	if(isset($_GET["today"])){stats_today();exit;}
	if(isset($_GET["tasks"])){tasks();exit;}
	
	
tabs_start();


function tabs_start(){
	$tpl=new templates();	
	$page=CurrentPageName();	
	$md=md5($_GET["servername"]);
	$html="
	<div id='stats-$md'></div>
	<script>
		function RfreshTabsFreewbs(){
			LoadAjax('stats-$md','$page?tabs=yes&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}');
		}	
		RfreshTabsFreewbs();	
	</script>
	";
	
	echo $html;
	
}



function tabs(){
	$tpl=new templates();	
	$page=CurrentPageName();
	
	
	$array["tasks"]="{tasks}";
	
	if($_GET["servername"]<>null){
		$q=new mysql();
		$table_name=$q->APACHE_TABLE_NAME($_GET["servername"]);
		if($q->TABLE_EXISTS($table_name, "apachelogs")){$array["statistics"]='{statistics}';}
		$table_name="apache_stats_".date('Ym');
		$sql="SELECT COUNT(servername) as tcount FROM $table_name WHERE servername='{$_GET["servername"]}'";
		if($q->mysql_error){if(!preg_match("#doesn.+?t exist#", $q->mysql_error)){echo "<H2>$q->mysql_error</H2>";}}else{$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));}
		if($ligne["tcount"]>0){	
			$array["status"]=$_GET["servername"];
			$array["today"]="{last_24h}";
		}
	}

	
	$array["errors"]="{errors}";
	$array["requests"]="{requests}";
	$font=18;
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="errors"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.events.php?type=errors&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}\"><span style='font-size:{$font}px'>$ligne</span></a></li>\n");
			continue;
		}
		
		if($num=="requests"){
			$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"freeweb.events.php?type=requests&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}\"><span span style='font-size:{$font}px'>$ligne</span></a></li>\n");
			continue;
		}		
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}\"><span span style='font-size:{$font}px'>$ligne</span></a></li>\n");
	}
	
	
	echo build_artica_tabs($html, "main_config_freewebstatus");
		
	
	
}

function tasks(){
	$servername=$_GET["servername"];
	$servernameenc=urlencode($_GET["servername"]);
	$tpl=new templates();
	
	$free=new freeweb($servername);
	if($free->groupware=="WORDPRESS"){
		
		$duplicate="<div style='margin:30px'>". button("{duplicate}", 
				"Loadjs('wordpress.php?duplicate-js=$servernameenc')",30)."
				<p style='font-size:20px'>{wordpress_duplicate_explain}</p>
			</div><div style='margin:30px'>". button("{restore_from_website}", 
				"Loadjs('wordpress.php?restore-js=$servernameenc')",30)."
				<p style='font-size:20px'>{restore_from_website_explain}</p>
			</div>	";
		
		
		
		
	}
	
	$check_configuration=$tpl->_ENGINE_parse_body("{check_configuration}");
	$reconfigure=ParagrapheTEXT("48-settings-refresh.png", "{rebuild}", "{rebuild_apache_text}","javascript:FreeWebsRebuildvHosts()");
	
	$see_config=ParagrapheTEXT("48-notes.png", "{display_config}", "{display_config_text}",
			"javascript:Loadjs('freeweb.edit.php?display-config-js={$_GET["servername"]}')");
	
	
	$checksDebug=ParagrapheTEXT("setup-check-icon-48.png", "{check_configuration}", "{check_configuration_text}",
			"javascript:FreeWebsCheckGroupware()");
	
	
	$html="<center style='width:98%' class=form>
		
			<div style='margin:30px'>". button("{rebuild}", 
						"Loadjs('freeweb.rebuild.progress.php?servername=$servernameenc')",30)."
				<p style='font-size:20px'>{rebuild_apache_text}</p>
			</div>
								
			<div style='margin:30px'>". button("{display_config}", 
						"Loadjs('freeweb.edit.php?display-config-js=$servernameenc')",30)."
				<p style='font-size:20px'>{display_config_text}</p>
			</div>								
								
			<div style='margin:30px'>". button("{check_configuration}", 
						"Loadjs('freeweb.edit.php?display-config-js=$servernameenc')",30)."
				<p style='font-size:20px'>{check_configuration_text}</p>
			</div>	
			$duplicate								
		</center>
								
		<script>
			function FreeWebsCheckGroupware(){
				LoadWinORG2('650','freeweb.edit.php?FreeWebsCheck=yes&servername={$ligne["servername"]}','{$check_configuration}::{$ligne["servername"]}');
			}
		</script>
		";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}


function status(){
	$servername=$_GET["servername"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$table_name="apache_stats_".date('Ym');
	$sql="SELECT * FROM $table_name WHERE servername='{$_GET["servername"]}' ORDER by zDate DESC LIMIT 0,1";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
	
	$html="
	<div style='font-size:16px'><div style='float:right'>". imgtootltip("refresh-24.png","{refresh}","RfreshTabsFreewbs()")."</div>{status}:$servername</div>
	<p>&nbsp;</p>
	<table style='width:99%' class=form>
	<tr>
		<td class=legend>{date}:</td>
		<td style='font-size:14px;font-weight:bold'>{$ligne["zDate"]}</td>
	</tr>	
	<tr>
		<td class=legend>{uptime}:</td>
		<td style='font-size:14px;font-weight:bold'>{$ligne["UPTIME"]}</td>
	</tr>	
	<tr>
		<td class=legend>{memory}:</td>
		<td style='font-size:14px;font-weight:bold'>". FormatBytes($ligne["total_memory"]/1024)."</td>
	</tr>
	<tr>
		<td class=legend>{total_traffic}:</td>
		<td style='font-size:14px;font-weight:bold'>". FormatBytes($ligne["total_traffic"]/1024)."</td>
	</tr>	
	<tr>
		<td class=legend>{requests_second}:</td>
		<td style='font-size:14px;font-weight:bold'>{$ligne["requests_second"]}/{second}</td>
	</tr>	
	<tr>
		<td class=legend>{traffic_second}:</td>
		<td style='font-size:14px;font-weight:bold'>". FormatBytes($ligne["traffic_second"]/1024)."/{second}</td>
	</tr>
	<tr>
		<td class=legend>{traffic_request}:</td>
		<td style='font-size:14px;font-weight:bold'>". FormatBytes($ligne["traffic_request"]/1024)."/{request}</td>
	</tr>
	</table>	
	
	<div id='traffic-today'></div>
	
	
	<script>
		LoadAjax('traffic-today','$page?traffic-today=yes&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}&time=mn');
	
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function traffic_today(){
	$servername=$_GET["servername"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();	
	$timeF="HOUR(zDate)";
	$INTERVAL="24 HOUR";
	if($_GET["time"]=="mn"){$timeF="DATE_FORMAT(zDate,'%h:%i')";$INTERVAL="200 MINUTE";}
	$field="total_traffic";
	if(isset($_GET["field"])){$field=$_GET["field"];}
	$table_name="apache_stats_".date('Ym');
	$sql="SELECT AVG($field) AS size, $timeF as ttime FROM $table_name WHERE zDate > DATE_SUB( NOW( ) , INTERVAL $INTERVAL) 
	AND servername='{$_GET["servername"]}' GROUP BY $timeF ORDER by $timeF";	
	$results=$q->QUERY_SQL($sql,"artica_events");
	$mysql_num_rows=mysql_num_rows($results);
		
	
	if(!$q->ok){echo $q->mysql_error;}
	
	if($mysql_num_rows>0){
			$c=0;
			while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
				$size=round($ligne["size"]/1024);
				
				$xdata[]=$ligne["ttime"];
				$ydata[]=$size;
				$c++;
				
			
			$targetedfile="ressources/logs/{$_GET["servername"]}.".basename(__FILE__).".$field.".__FUNCTION__.".png";
			if(is_file($targetedfile)){@unlink($targetedfile);}
			$gp=new artica_graphs();
			$gp->RedAreas=$area;
			$gp->width=700;
			$gp->height=300;
			$gp->filename="$targetedfile";
			$gp->xdata=$xdata;
			$gp->ydata=$ydata;
			$gp->y_title="KB";
			$gp->x_title="H";
			$gp->title=null;
			$gp->margin0=true;
			$gp->Fillcolor="blue@0.9";
			$gp->color="146497";
			
			}
			$title=$tpl->_ENGINE_parse_body("{{$field}} (Kb/h)");
			if($field=="requests_second"){
				$title=$tpl->_ENGINE_parse_body("{{$field}}");
				$gp->y_title=$tpl->_ENGINE_parse_body("{requests}");
			}
			
			$gp->line_green();
			if(!is_file($targetedfile)){writelogs("Fatal \"$targetedfile\" no such file! ",__FUNCTION__,__FILE__,__LINE__);return;}
			writelogs("Checking -> $targetedfile",__FUNCTION__,__FILE__,__LINE__);
			echo "
			<center>
			<div>
			<h3 style='text-transform: none;margin-bottom:5px'>$title</h3>
			<img src='$targetedfile'>
			</div>
			</center>";	
			
			}
}

function stats_today(){
	$page=CurrentPageName();
	$html="
	<div id='traffic-today2'></div>
	<div id='memory-today2'></div>
	<div id='traffic_second'></div>
	<div id='requests_second'></div>
	
	
	<script>
		LoadAjax('traffic-today2','$page?traffic-today=yes&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}');
		LoadAjax('memory-today2','$page?traffic-today=yes&field=total_memory&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}');
		LoadAjax('traffic_second','$page?traffic-today=yes&field=traffic_second&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}');
		LoadAjax('requests_second','$page?traffic-today=yes&field=requests_second&servername={$_GET["servername"]}&group_id={$_REQUEST["group_id"]}');
		
		
	</script>	
	
	";
	echo $html;
}




