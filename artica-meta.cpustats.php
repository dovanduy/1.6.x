<?php
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.ini.inc');


if(isset($_GET["js"])){js();exit;}
if(isset($_GET["events-table"])){events_table();exit;}
if(isset($_GET["ShowID"])){ShowID();exit;}
if(isset($_GET["ShowID-js"])){ShowID_js();exit;}
if(isset($_POST["empty-table"])){empty_table();exit;}
if(isset($_GET["system"])){popup();exit;}
tabs();


function js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{events}");
	$page=CurrentPageName();
	$artica_meta=new mysql_meta();
	$hostname=$artica_meta->uuid_to_host($_GET["uuid"]);
	echo "YahooWin3('850','$page?uuid=".urlencode($_GET["uuid"])."','$hostname')";
}

function tabs(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$artica_meta=new mysql_meta();
	$array["system"]='{system}';
	$array["processes"]='{task_manager}';
	//$isProxy=$artica_meta->isProxy($_GET["uuid"]);
	$array["services"]='{services}';
	
	while (list ($num, $ligne) = each ($array) ){
		
		if($num=="processes"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.processes.php?uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
		if($num=="services"){
			$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"artica-meta.services.php?uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
			continue;
		}
	
		$html[]=$tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=yes&uuid=".urlencode($_GET["uuid"])."\"><span style='font-size:18px'>$ligne</span></a></li>\n");
	}
	
	echo build_artica_tabs($html, "meta-hosts-{$_GET["uuid"]}");	
	
	
	
}


function popup(){
	$page=CurrentPageName();
	$time=time();
	$uuid=$_GET["uuid"];
	$basedir="/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/$uuid";
	
	
	if(is_file("$basedir/INTERFACE_LOAD_AVG.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-2'></div>";
		$f2[]="function FDeux$time(){
		AnimateDiv('$time-2');
		Loadjs('admin.index.loadvg.php?graph2=yes&container=$time-2&uuid=$uuid',true);
	}
	setTimeout(\"FDeux$time()\",500);";
	}
	
	
	if(is_file("$basedir/cpustats.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-cpustats'></div>";
		$f2[]="function Fcpustats$time(){AnimateDiv('$time-cpustats');Loadjs('admin.index.loadvg.php?cpustats=yes&container=$time-cpustats&uuid=$uuid',true);} setTimeout(\"Fcpustats$time()\",500);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>$basedir/cpustats.db no such file</H1>\n";}
	}
	
	
	
	if(is_file("$basedir/INTERFACE_LOAD_AVG2.db")){
		$f1[]="<div style='width:665px;height:240px' id='$time-1'></div>";
		$f2[]="function FOne$time(){AnimateDiv('$time-1');Loadjs('admin.index.loadvg.php?graph1=yes&container=$time-1&uuid=$uuid',true);} setTimeout(\"FOne$time()\",500);";
	}else{
		if($GLOBALS["VERBOSE"]){echo "<H1>$basedir/INTERFACE_LOAD_AVG2.db no such file</H1>\n";}
	}

	$html=@implode("\n", $f1)."<script>".@implode("\n", $f2)."</script>";
	echo $html;

}

function events_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();

	$FORCE=1;
	
	
	
	$search='%';
	$table="meta_admin_mysql";
	$page=1;
	$ORDER="ORDER BY zDate DESC";
	if(is_numeric($_GET["critical"])){
		$FORCE="severity={$_GET["critical"]}";
	}
	
	if($_GET["text-filter"]<>null){
		$FORCE=" subject LIKE '%{$_GET["text-filter"]}%'";
		if(is_numeric($_GET["critical"])){
			$FORCE=$FORCE." AND severity={$_GET["critical"]}";
		}
	}
	
	if($_GET["hostname"]<>null){
		$FORCE=$FORCE." AND hostname='{$_GET["hostname"]}'";
	}
	

	$total=0;
	if($q->COUNT_ROWS($table,"artica_events")==0){json_error_show("no data",1);}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
	if(isset($_POST['page'])) {$page = $_POST['page'];}

	$severity[0]="22-red.png";
	$severity[1]="22-warn.png";
	$severity[2]="22-infos.png";
	$currentdate=date("Y-m-d");

	$searchstring=string_to_flexquery();
	
	if($searchstring<>null){
		
		
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
		
		$total = $ligne["TCOUNT"];

	}else{
		if(strlen($FORCE)>2){
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE $FORCE";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_events"));
			if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
			$total = $ligne["TCOUNT"];
		}else{
			$total = $q->COUNT_ROWS($table, "artica_events");
		}
	}

	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}



	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE $FORCE $searchstring $ORDER $limitSql";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){ if(preg_match("#marked as crashed#", $q->mysql_error)){ $q->QUERY_SQL("DROP TABLE `$table`","artica_events"); } }
	if(!$q->ok){json_error_show($q->mysql_error,1);}

	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();

	$CurrentPage=CurrentPageName();

	if(mysql_num_rows($results)==0){json_error_show("no data");}

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$hostname=$ligne["hostname"];
		$ligne["zDate"]=str_replace($currentdate, "", $ligne["zDate"]);
		$severity_icon=$severity[$ligne["severity"]];
		$link="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$CurrentPage?ShowID-js={$ligne["ID"]}')\" style='text-decoration:underline'>";
		$text=$link.$tpl->_ENGINE_parse_body($ligne["subject"]."</a><div style='font-size:10px'>{host}:$hostname {function}:{$ligne["function"]}, {line}:{$ligne["line"]}</div>");
		
		
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => array(
						"<img src='img/$severity_icon'>",
						
						$ligne["zDate"],$text,$ligne["filename"],$ligne["hostname"] )
		);
	}


	echo json_encode($data);

}