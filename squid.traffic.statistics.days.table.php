<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$GLOBALS["title_array"]["size"]="{downloaded_flow}";
	$GLOBALS["title_array"]["req"]="{requests}";	
	
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die("no rights");}	
	if(isset($_GET["items"])){items();exit;}
popup();
function popup(){
		$page=CurrentPageName();
		$tpl=new templates();
		$users=new usersMenus();
		$TB_HEIGHT=500;
		$TB_WIDTH=620;
		$field_query=$_GET["field_query"];
		$hour_table=$_GET["hour_table"];
		$q=new mysql_squid_builder();
		$t=time();
		$size=$tpl->javascript_parse_text("{size}");
		$webservers=$tpl->_ENGINE_parse_body("{webservers}");
		$cache=$tpl->_ENGINE_parse_body("{cache}");
		$events_text=$tpl->_ENGINE_parse_body("{events}");
		$events=$q->COUNT_ROWS($hour_table, $database);
		
		$buttons="
		buttons : [
	
		{name: '$online_help', bclass: 'Help', onpress : ItemHelp$t},
	
		],	";
		
		$buttons=null;
		
		$html="
	
		<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	
		<script>
		var mem$t='';
		$(document).ready(function(){
		$('#flexRT$t').flexigrid({
		url: '$page?items=yes&t=$t&field_query=$field_query&hour_table=$hour_table',
		dataType: 'json',
		colModel : [
		{display: '$size', name : 'totalsize', width :120, sortable : true, align: 'left'},
		{display: '$webservers', name : 'familysite', width :383, sortable : true, align: 'left'},
		{display: '$cache', name : 'size', width :48, sortable : true, align: 'center'},

	
	
		],
		$buttons
	
		searchitems : [
		{display: '$webservers', name : 'familysite'},
	
	
	
		],
		sortname: 'totalsize',
		sortorder: 'desc',
		usepager: true,
		title: '<span id=\"title-$t\">$events $events_text</span>',
		useRp: true,
		rp: 50,
		showTableToggleBtn: false,
		width: $TB_WIDTH,
		height: $TB_HEIGHT,
		singleSelect: true,
		rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	});
	
	function ItemHelp$t(){
	//s_PopUpFull('http://www.mail-appliance.org/index.php?cID=339','1024','900');
	}
	
	
	</script>";
	
	echo $html;
	
	
	
	}	
	
	
function items(){
		$t=$_GET["t"];
		$tpl=new templates();
		$MyPage=CurrentPageName();
		$q=new mysql_squid_builder();
		$users=new usersMenus();
		$sock=new sockets();
		$database="squidlogs";
	
		$search='%';
		$table=$_GET["hour_table"];
		$time=$q->TIME_FROM_HOUR_TABLE($table);
		$_GET["day"]=date("Y-m-d",$time);
	
		$page=1;
		$FORCE_FILTER=null;
		if($_GET["field"]<>null){
			$FORCE_FILTER=" AND `{$_GET["field"]}`='{$_GET["value"]}'";
		}
		
		
	
		if(!$q->TABLE_EXISTS($table, $database)){json_error_show("$table doesn't exists...");}
		if($q->COUNT_ROWS($table, $database)==0){json_error_show("No data");}

		
		
		$field_query=$_GET["field_query"];
		$hour_table=$_GET["hour_table"];		
		$table="(SELECT SUM($field_query) as totalsize,familysite FROM $hour_table GROUP BY familysite) as t";
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
		if($searchstring<>null){
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM $table WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		$sql="SELECT *  FROM $table WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql,$database);
		if(mysql_num_rows($results)==0){
			json_error_show("$sql<hr>No row",1);
		}
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error);}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$zmd5=md5(serialize($ligne));
			$color="black";
	
			$date=strtotime($ligne["zDate"]);
			$Hour=date("H:i",$date);
			$day=$_GET["day"];
			//familysite 	size 	hits
	
			$urljsSIT="<a href=\"javascript:blur();\" 
			OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type={$_GET["type"]}&familysite={$ligne["familysite"]}&day=$day')\" 
			style='font-size:16px;font-weight:bold;text-decoration:underline'>";
	
			$urijs="s_PopUpFull('{$ligne["uri"]}','1024','900');";
	
			
			$ligne["totalsize"]=FormatBytes($ligne["totalsize"]/1024);
			
			$addcache=imgsimple("net-disk-add-32.png","null","Loadjs('squid.caches32.caches-www.php?addjs-silent=yes&website={$ligne["familysite"]}')");
			
			$sql="SELECT sitename FROM websites_caches_params WHERE sitename='{$ligne["familysite"]}'";
			$ligne2=mysql_fetch_array($q->QUERY_SQL($sql));
			if($ligne2<>null){
				$select="Loadjs('squid.miniwebsite.tasks.php?cache-params-js=yes&sitename={$ligne["familysite"]}');";
				$addcache=imgsimple("disk_share_enable-32.png","null",$select);
			}
			

			$data['rows'][] = array(
					'id' => "$zmd5",
					'cell' => array(
							"<span style='font-size:16px;color:$color'>{$ligne["totalsize"]}</a></span>",
							"<span style='font-size:12px;color:$color'>$urljsSIT{$ligne["familysite"]}</a></span>",
							"<span style='font-size:12px;color:$color'>$addcache</span>",
					)
			);
		}
	
	
		echo json_encode($data);
	
	
	}	