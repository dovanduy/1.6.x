<?php
	if(isset($_GET["verbose"])){ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.updateutility2.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.system.network.inc');
	
	$users=new usersMenus();
	if(!$users->AsSystemAdministrator){
		$tpl=new templates();
		$ERROR_NO_PRIVS=$tpl->javascript_parse_text("{ERROR_NO_PRIVS}");
		echo "alert('$ERROR_NO_PRIVS');";return;
	}
	
	if(isset($_GET["js"])){js();exit;}
	if(isset($_GET["web-events"])){webevents_list();exit;}

events_table();	


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{webfiltering_service_events}");
	$html="LoadWinORG(817.6,'$page','$title')";
	echo $html;
}
	

function events_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$event=$tpl->_ENGINE_parse_body("{event}");
	$title=$tpl->_ENGINE_parse_body("{webfiltering_service_events}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddBandRule},
	],";		
		$buttons=null;

	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
function flexigridStart$t(){
$('#flexRT$t').flexigrid({
	url: '$page?web-events=yes',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'date', width : 134, sortable : true, align: 'center'},
		{display: 'Pid', name : 'code', width : 36, sortable : true, align: 'left'},	
		{display: '$event', name : 'url', width :574, sortable : false, align: 'left'},
		
		
		],
	$buttons
	searchitems : [
		{display: '$zDate', name : 'zDate'},
		{display: 'Pid', name : 'Pid'},
		{display: '$event', name : 'event'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 800,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
}
setTimeout('flexigridStart$t()',800);

</script>

";	
	echo $html;
}


function webevents_list(){

	$sock=new sockets();
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$q=new mysql();
	$search='%';
	$table="squid_pools";
	$database="artica_backup";
	$page=1;
	$FORCE_FILTER=null;
	$total=0;
	$search=null;
	if(isset($_POST["qtype"])){
		if($_POST["query"]<>null){
			
			$_POST["query"]=str_replace("**", "*", $_POST["query"]);
			$_POST["query"]=str_replace("**", "*", $_POST["query"]);
			$_POST["query"]=str_replace(".", "\.", $_POST["query"]);
			$_POST["query"]=str_replace("/", "\/", $_POST["query"]);
			$_POST["query"]=str_replace("*", ".*?", $_POST["query"]);
			$search=$_POST["query"];

			if($_POST["qtype"]=="zDate"){
				$search="^.*?$search\s+\[";
			}
			
			if($_POST["qtype"]=="Pid"){
				$search='\s+\['.$search."";
			}

			if($_POST["qtype"]=="event"){
				$search="[0-9]+\]\s+.*?$search";
			}				
			
		}
		
	}
	
	if($search<>null){$search="&search=".base64_encode($search);}
	
	$tables=unserialize(base64_decode($sock->getFrameWork("squid.php?ufdbguard-events=yes&rp={$_POST["rp"]}$search")));
	
		
	if(count($tables)==0){
		writelogs("$table, no row",__FILE__,__FUNCTION__,__FILE__,__LINE__);
		$data['page'] = $page;$data['total'] = $total;$data['rows'] = array();
		echo json_encode($data);
		return ;
	}
	if(isset($_POST["sortname"])){if($_POST["sortname"]<>null){$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";}}	
	if(isset($_POST['page'])) {$page = $_POST['page'];}
	

	if($_POST["query"]<>null){
		$_POST["query"]="*".$_POST["query"]."*";
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("**", "*", $_POST["query"]);
		$_POST["query"]=str_replace("*", ".+?", $_POST["query"]);
		$search=$_POST["query"];
	}
	

	
	$total=count($tables);
	$data = array();
	$data['page'] = $page;
	$data['total'] = $total;
	$data['rows'] = array();
	
		
	
	//if(mysql_num_rows($results)==0){$data['rows'][] = array('id' => $ligne[time()],'cell' => array($sql,"", "",""));}
	

		//<td>". Paragraphe("bandwith-limit-64.png","{$ligne["rulename"]}","$text","javascript:SquidBandRightPanel('{$ligne["ID"]}')")."</td>
	
	while (list ($ID, $line) = each ($tables) ){
		if(!preg_match('#(.+?)\s+\[(.+?)\]\s+(.+)#', $line,$re)){continue;}
		$color="black";
		$date=$re[1];
		$pid=$re[2];
		$event=$re[3];
		
		if(preg_match("#(fatal|error|warn)#i", $event)){$color="#BA0000";}
		
		
		$data['rows'][] = array(
		'id' => $ID,
		'cell' => array(
			"<span style='font-size:13px;color:$color'>$date</span>",
			"<span style='font-size:13px;color:$color'>$pid</span>",
			"<span style='font-size:13px;color:$color'>$event</span>",
		
		
		)
		);
		
		
	}
	
	
echo json_encode($data);		
	
}
