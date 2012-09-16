<?php
	if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.blackboxes.inc');
	include_once('ressources/class.os.system.inc');
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsAnAdministratorGeneric){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}


if(isset($_GET["rows-table"])){rows_table();exit;}
table();


function table(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	
	$path=$tpl->_ENGINE_parse_body("{path}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$model=$tpl->_ENGINE_parse_body("{model}");
	$partitions=$tpl->_ENGINE_parse_body("{partitions}");
	$TB_HEIGHT=450;
	$TABLE_WIDTH=836;
	$TB2_WIDTH=400;
	$ROW1_WIDTH=629;
	$ROW2_WIDTH=163;
	
	
	$t=time();
	
	$buttons="
	buttons : [
	{name: '$empty', bclass: 'Delz', onpress : EmptyEvents},
	
		],	";
	$html="
	<table class='node-table-$t' style='display: none' id='node-table-$t' style='width:99%'></table>
<script>

$(document).ready(function(){
$('#node-table-$t').flexigrid({
	url: '$page?rows-table=yes&nodeid={$_GET["nodeid"]}',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'ipaddr1', width :69, sortable : false, align: 'left'},
		{display: '$path', name : 'path', width :87, sortable : true, align: 'left'},
		{display: '$size', name : 'size', width :72, sortable : true, align: 'left'},
		{display: '$model', name : 'gateway', width :120, sortable : false, align: 'left'},
		{display: '$partitions', name : 'partitions', width :408, sortable : false, align: 'left'},
	],
	
	
	
	sortname: '	path',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: $TABLE_WIDTH,
	height: $TB_HEIGHT,
	singleSelect: true
	
	});   
});

	
	
</script>";
	
	echo $html;	
	
}

function rows_table(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql_blackbox();
	$nodeid=$_GET["nodeid"];
	$mountedon=$tpl->_ENGINE_parse_body("{mounted}");
	$search='%';
	$table="harddrives";
	$page=1;
	$ORDER="ORDER BY path desc";
	
	$total=0;
	if($q->COUNT_ROWS($table)==0){$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();echo json_encode($data);return ;}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", "%", $_POST["query"]);
		$search=$_POST["query"];
		$searchstring="AND ((`{$_POST["qtype"]}` LIKE '$search' AND nodeid=$nodeid) OR (`mac` LIKE '$search' AND nodeid=$nodeid))";
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE 1 $searchstring";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
		
	}else{
		$sql="SELECT COUNT(*) as TCOUNT FROM `$table` WHERE nodeid=$nodeid";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
		$total = $ligne["TCOUNT"];
	}
	
	if (isset($_POST['rp'])) {$rp = $_POST['rp'];}	
	

	
	$pageStart = ($page-1)*$rp;
	$limitSql = "LIMIT $pageStart, $rp";
	
	$sql="SELECT *  FROM `$table` WHERE nodeid=$nodeid $searchstring $ORDER $limitSql";	
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		
	}
	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
	if(!$q->ok){
		$data['rows'][] = array('id' => $ligne[time()+1],'cell' => array($q->mysql_error,"", "",""));
		$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));
		echo json_encode($data);
		return;
	}	
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	$usb=new usb();
	while ($ligne = mysql_fetch_assoc($results)) {

		
		$model=$ligne["ID_MODEL_1"]." ".$ligne["ID_MODEL_2"]." ".$ligne["ID_VENDOR"]." ".$ligne["ID_FS_LABEL"];
		
		$partitions=unserialize(base64_decode($ligne["PARTITIONS"]));
		$ppp=array();
		if(count($partitions)>0){
			
			while (list ($num, $line) = each ($partitions)){
				
				$MOUNTED=$line["MOUNTED"];
				$free_size=explode(";",$line["free_size"]);
				$bigsize=$free_size[0];
				$used=$free_size[1];
				$free=$free_size[2];
				$pourcent=$free_size[3];				
				$perc=pourcentage($pourcent);
				if($used==null){$line["USED"]=0;}
				if($bigsize==null){$bigsize=0;}
				if($line["TYPE"]==5){$perc="-";}
				if($line["TYPE"]==82){$perc="-";}					
				
			
			
			
			$ppp[]="<tr style='border:0px'>
					<td  style='border:0px'><span style='font-size:12px'>". basename($num)."&nbsp;$label</td>
					<td valign='middle' style='width:99%'>$perc
					<div style='margin-top:-10px'>$used/$bigsize <span style='font-size:12px' >{$usb->getPartypename($line["TYPE"])} ({$line["TYPE"]}) $mountedon $MOUNTED</div>
					
					</td>
					
					";
			}
		
		}
		
		
		$ligne["size"]=FormatBytes($ligne["size"]*1000);
	$data['rows'][] = array(
		'id' => $ligne['mac'],
		'cell' => array(
			"<img src='img/disk-64.png'>",
			"<span style='font-size:16px'>{$ligne["path"]}</span>",
			"<span style='font-size:16px'>{$ligne["size"]}</span>",
			"<span style='font-size:16px'>$model</span>",
			"<table style='border:0px'>".@implode("",$ppp)."</table>",			 
	
		)
		);
	}
	
	
echo json_encode($data);		

}