<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.groups.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.proxypac.inc');
	include_once('ressources/class.squid.users.inc');
	
	
	
	if(isset($_GET["events-list"])){events_search();exit;}

page();

function page(){
	$page=CurrentPageName();
	$tpl=new templates();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$zdate=$tpl->_ENGINE_parse_body("{zDate}");
	$proto=$tpl->_ENGINE_parse_body("{proto}");
	$uri=$tpl->_ENGINE_parse_body("{url}");
	$member=$tpl->_ENGINE_parse_body("{member}");
	
	
	$sql="SELECT userid,publicip FROM usersisp WHERE userid='{$_SESSION["uid"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));		
	
	$title=$tpl->_ENGINE_parse_body("{today}: {realtime_requests} ".date("H")."h {member}:".gethostbyaddr($ligne["publicip"]));
	
	$t=time();
	$html="
	<div style='' class=form>
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	</div>
	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?events-list=yes&publicip={$ligne["publicip"]}',
	dataType: 'json',
	colModel : [
		{display: '$zdate', name : 'zDate', width :120, sortable : true, align: 'left'},
		{display: '$proto', name : 'proto', width :33, sortable : false, align: 'left'},
		{display: '$uri', name : 'events', width : 681, sortable : false, align: 'left'},
		],
	
	searchitems : [
		{display: '$events', name : 'events'}
		],
	sortname: 'zDate',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 891,
	height: 420,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function SelectGrid2(com, grid) {
	var items = $('.trSelected',grid);
	var id=items[0].id;
	id = id.substring(id.lastIndexOf('row')+3);
	if (com == 'Select') {
			LoadAjax('table-1-selected','$page?familysite-show='+id);
		}
	}
	 
	$('table-1-selected').remove();
	$('flex1').remove();		 

</script>
	
	
	";
	
	echo $html;
	
}

function events_search(){
$page=CurrentPageName();
$tpl=new templates();
$sock=new sockets();


	
	
		
	if(isset($_POST['page'])) {$page = $_POST['page'];}	
	if(isset($_POST['rp'])) {$rp = $_POST['rp'];}
	$OnlyIpAddr=$_GET["publicip"];
	
	if($_POST["query"]<>null){
		$search=base64_encode($_POST["query"]);
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=$search&rp={$_POST["rp"]}&OnlyIpAddr=$OnlyIpAddr")));
		$total=count($datas);
		
	}else{
		$datas=unserialize(base64_decode($sock->getFrameWork("squid.php?accesslogs=&rp={$_POST["rp"]}&OnlyIpAddr=$OnlyIpAddr")));
		$total=count($datas);
	}
	
		
	$pageStart = ($page-1)*$rp;
	
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){
		if($_POST["sortname"]=="zDate"){
			if($_POST["sortorder"]=="desc"){
				krsort($datas);
			}
		}
	$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	while (list ($key, $line) = each ($datas) ){
		

			
			
			if(preg_match('#(.+?)\s+(.+?)\s+squid\[.+?:\s+MAC:(.+?)\s+(.+?)\s+(.+?)\s+(.+?)\s+\[(.+?)\]\s+\"([A-Z]+)\s+(.+?)\s+#i',$line,$re)){

				$uri=$re[9];
				$date=date("Y-m-d H:i:s",strtotime($re[7]));
				$mac=$re[3];
				$ip=$re[4];
				$user=$re[5];
				$dom=$re[6];
				$proto=$re[8];
				
					$data['rows'][] = array(
						'id' => md5($line),
						'cell' => array($date, $proto,$uri)
					);					
					
					continue;
						
			}

			
			if(preg_match('#(.*?)\s+([0-9]+)\s+([0-9:]+).*?\]:\s+(.*?)\s+(.+)\s+(.+)\s+.+?"([A-Z]+)\s+(.+?)\s+#',$line,$re)){
	
				
				    $dates="{$re[1]} {$re[2]} ".date('Y'). " {$re[3]}";
					$ip=$re[4];
					$user=$re[5];
					$date=date("Y-m-d H:i:s",strtotime($dates));
					$uri=$re[8];
					$proto=$re[7];

					$data['rows'][] = array(
						'id' => md5($line),
						'cell' => array($date, $proto,$uri,"$ip ($user)")
					);					
					
					continue;
						
			}				

		writelogs("Not Filtered: $line",__FUNCTION__,__FILE__,__LINE__);

	}
	echo json_encode($data);	
}	