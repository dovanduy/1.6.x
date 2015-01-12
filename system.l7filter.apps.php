<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.users.menus.inc');
include_once('ressources/class.mysql.inc');
include_once('ressources/class.mysql.builder.inc');
include_once('ressources/class.system.nics.inc');

$usersmenus=new usersMenus();
if(!$usersmenus->AsSystemAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();
}


if(isset($_GET["items"])){l7apps_items();exit;}
if(isset($_GET["enable-js"])){l7apps_items_enable_js();exit;}
if(isset($_POST["enable"])){l7apps_items_enable();exit;}


l7apps();

function l7apps_items_enable_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	header("content-type: application/x-javascript");
	$t=time();

	$html="
	var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){ alert(results); return; }
	$('#TABLEAU_MAIN_l7_CONTAINERS').flexReload();
}
function Save$t(){
var XHR = new XHRConnection();
	XHR.appendData('enable','{$_GET["ID"]}');
	XHR.sendAndLoad('$page', 'POST',xSave$t);
}

Save$t();

";

echo $html;

}

function l7apps_items_enable(){
	
	$q=new mysql();
	$ID=$_POST["enable"];
	
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT enabled FROM l7filters_items WHERE ID='$ID'","artica_backup"));
	if(!$q->ok){echo $q->mysql_error;}
	if($ligne["enabled"]==0){
		$q->QUERY_SQL("UPDATE l7filters_items SET enabled=1 WHERE ID=$ID","artica_backup");
		return;
	}
	$q->QUERY_SQL("UPDATE l7filters_items SET enabled=0 WHERE ID=$ID","artica_backup");
}

function l7apps(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$title=$tpl->javascript_parse_text("{Q.O.S}: {interfaces}");
	$t=time();
	$type=$tpl->javascript_parse_text("{type}");
	$nic_bandwith=$tpl->javascript_parse_text("{nic_bandwith}");
	$level=$tpl->javascript_parse_text("{level}");
	$ceil=$tpl->javascript_parse_text("{ceil}");
	$nic=$tpl->javascript_parse_text("{nic}");
	$explain=$tpl->javascript_parse_text("{explain}");
	$title=$tpl->javascript_parse_text("{appl7}: {applications}");
	$apps=$tpl->javascript_parse_text("{applications}");
	$enabled=$tpl->javascript_parse_text("{enabled}");
	$apply=$tpl->javascript_parse_text("{apply}");
	$mark=$tpl->javascript_parse_text("{MARK}");
	$bandwith=$tpl->javascript_parse_text("{bandwith}");
	$new_container=$tpl->javascript_parse_text("{new_container}");
	// 	$sql="INSERT INTO nic_routes (`type`,`gateway`,`pattern`,`zmd5`,`nic`)
	// VALUES('$type','$gw','$pattern/$cdir','$md5','$route_nic');";
	//{name: '$apply', bclass: 'apply', onpress : Apply$t},
	
	$LEVEL[0]="ok24.png";
	$LEVEL[1]="24-blue.png";
	$LEVEL[2]="warning24.png";
	$LEVEL[3]="24-red.png";
	$LEVEL[4]="ok24-grey.png";
	
	$LEVELT[0]="{good}";
	$LEVELT[1]="{good} {medium}";
	$LEVELT[2]="{probable}";
	$LEVELT[3]="{marginal}";
	$LEVELT[4]="{poor}";
	

	while (list ($keyitem, $ligne) = each ($LEVEL) ){
		$tt[]=$tpl->_ENGINE_parse_body("<span style='font-size:14px'><img src=img/$ligne> {$LEVELT[$keyitem]}</span>");
	}
	
	
	$tttxt=$tpl->_ENGINE_parse_body("<div style='font-size:18px'>{detection_rate} {level}:</div>").CompileTr5($tt,true);
	
	$buttons="
	buttons : [
	{name: '$apply', bclass: 'add', onpress : appli$t},



	],";
	
	$html="
	$tttxt
	<table class='TABLEAU_MAIN_l7_CONTAINERS' style='display: none' id='TABLEAU_MAIN_l7_CONTAINERS' style='width:100%'></table>
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#TABLEAU_MAIN_l7_CONTAINERS').flexigrid({
	url: '$page?items=yes&t=$t',
	dataType: 'json',
	colModel : [
	{display: '$mark', name : 'ID', width : 75, sortable : true, align: 'center'},
	{display: '$level', name : 'level', width :55, sortable : true, align: 'center'},
	{display: '$apps', name : 'keyitem', width : 211, sortable : true, align: 'left'},
	{display: '$explain', name : 'explain', width : 499, sortable : true, align: 'left'},
	{display: '$enabled', name : 'enabled', width : 50, sortable : true, align: 'center'},
	
	],
	$buttons
	searchitems : [
	{display: '$apps', name : 'keyitem'},
	{display: '$explain', name : 'explain'},
	],
	sortname: 'keyitem',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:22px>$title</span>',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: '99%',
	height: 450,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]

});
});

function appli$t(){
	Loadjs('system.services.cmd.php?APPNAME=APP_l7FILTER&action=restart&cmd=%2Fetc%2Finit.d%2Fl7filter&appcode=APP_l7FILTER');
}
function Add$t(){
Loadjs('$page?container-js=yes&ID=0');

}
</script>
";
echo $html;

}

function l7apps_items(){
		$tpl=new templates();
		$MyPage=CurrentPageName();
		
		$q=new mysql_builder();
		$q->CheckTables_qos();
		$database="artica_backup";
		$q=new mysql();
		$t=$_GET["t"];
		$search='%';
		$table="l7filters_items";
		$page=1;
		$FORCE_FILTER=null;
		$total=0;
	
		if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY `{$_POST["sortname"]}` {$_POST["sortorder"]}";}}
		if(isset($_POST['page'])) {$page = $_POST['page'];}
	
		$searchstring=string_to_flexquery();
	
	
		if($searchstring<>null){
			$search=$_POST["query"];
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER $searchstring";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"$database"));
			$total = $ligne["TCOUNT"];
	
		}else{
			$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $FORCE_FILTER";
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,$database));
			$total = $ligne["TCOUNT"];
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		if(!is_numeric($rp)){$rp=50;}
		$limitSql = "LIMIT $pageStart, $rp";
	
		$sql="SELECT *  FROM `$table` WHERE 1 $searchstring $FORCE_FILTER $ORDER $limitSql";
		$results = $q->QUERY_SQL($sql,$database);
	
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total+1;
		$data['rows'] = array();
	
		if(!$q->ok){json_error_show($q->mysql_error,1);}
	
	
		if(mysql_num_rows($results)==0){
	
			json_error_show("????");
			return;
		}
	
	
		if($searchstring==null){
	
			$data['total']=$data['total']+$array[0];
			$data['rows']=$array[1]["rows"];
		}
	
		$fontsize=14;
		
		$LEVEL[0]="ok24.png";
		$LEVEL[1]="24-blue.png";
		$LEVEL[2]="warning24.png";
		$LEVEL[3]="24-red.png";
		$LEVEL[4]="ok24-grey.png";
		
		$ENABLEDI[0]="ok32-grey.png";
		$ENABLEDI[1]="ok32.png";
		
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
	
	
			
	
			$lsprime="javascript:Loadjs('$MyPage?enable-js=yes&ID={$ligne["ID"]}')";
	
	
	
			$enabled=$ligne["enabled"];
			$icon="ok24.png";
			if($enabled==0){$icon="ok24-grey.png";$color="#8a8a8a";}
	
			$nic=new system_nic($ligne["eth"]);
			if($nic->QOS==0){
				$icon="ok24-grey.png";
				$color="#8a8a8a";
			}
	
			$QOSMAX=intval($ligne["QOSMAX"]);
			if($QOSMAX<10){$QOSMAX=100;}
			$style="style='font-size:{$fontsize}px;color:$color;'";
			$js="<a href=\"javascript:blur();\" OnClick=\"$lsprime;\"
			style='font-size:{$fontsize}px;color:$color;text-decoration:underline'>";
	
	
			$ligne["name"]=utf8_encode($ligne["name"]);
			
			$icon=$LEVEL[$ligne["level"]];
			$icon_enabled=$ENABLEDI[$ligne["enabled"]];

			$ligne["keyitem"]=strtoupper($ligne["keyitem"]);
			$data['rows'][] = array(
					'id' => $ligne['ID'],
					'cell' => array(
							"<span $style>{$ligne["ID"]}</a></span>",
							"<span $style><img src='img/$icon'></a></span>",
							"<span $style><span style='font-size:16px'>{$ligne["keyitem"]}</span></a></span>",
							"<span $style>{$ligne["explain"]}</a></span>",
							
							"<span $style>{$js}<img src='img/$icon_enabled'></a></span>",
							
	
	
					)
			);
	
		}
	
	
		echo json_encode($data);
	
	}

	