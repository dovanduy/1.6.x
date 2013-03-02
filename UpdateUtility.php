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

	if(isset($_GET["settings"])){settings();exit;}
	if(isset($_GET["products"])){products_tabs();exit;}
	if(isset($_GET["product-section"])){product_section();exit;}
	if(isset($_POST["ProductSubKey"])){product_section_save();exit;}
	if(isset($_POST["UpdateUtilityAllProducts"])){UpdateUtilitySave();exit;}
	if(isset($_GET["status"])){status();exit;}
	if(isset($_POST["UpdateUtilityStartTask"])){UpdateUtilityStartTask();exit;}
	if(isset($_GET["webevents"])){webevents_table();exit;}
	if(isset($_GET["web-events"])){webevents_list();exit;}
	if(isset($_GET["dbsize"])){dbsize();exit;}
	
	
	
tabs();

function status(){
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$datas=base64_decode($sock->getFrameWork('services.php?Update-Utility-status=yes'));
	$ini=new Bs_IniHandler();
	$ini->loadString($datas);
	$status=DAEMON_STATUS_ROUND("APP_UPDATEUTILITYHTTP",$ini,null).
	DAEMON_STATUS_ROUND("APP_UPDATEUTILITYRUN",$ini,null).
	
	"
	<center>
	<table style='width:20%' class=form>
	<tr>
		<td width=1%>". imgtootltip("refresh-24.png","{refresh}","UpdateUtilityStatus()")."</td>
		<td width=1%>". imgtootltip("24-run.png","{run}","UpdateUtilityStartTask()")."</td>
	</tr>
				
	</table>
	</center>
	<div id='dbsize' style=width:300px></div>
	<script>
		LoadAjaxTiny('dbsize','$page?dbsize=yes&refresh=dbsize');
	</script>	
	";
	echo $tpl->_ENGINE_parse_body($status);

}


function products_tabs(){

	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	
	$update=new updateutilityv2();
	while (list ($num, $ArrayF) = each ($update->families) ){
		$array[$num]=$ArrayF["NAME"];
		
	}
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]="<li><a href=\"$page?product-section=yes&product-key=$num\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
	}

	$html="
		<div id='main_upateutility_pkey' style='background-color:white'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_upateutility_pkey').tabs();
				});
		</script>
	
	";
		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
	
	
}


function tabs(){
	
	$page=CurrentPageName();
	$users=new usersMenus();
	$tpl=new templates();
	$array["settings"]="{parameters}";
	$array["products"]="{kaspersky_products}";
	$array["webevents"]="{webevents}";

// Total downloaded: 100%, Result: Retranslation successful and update is not requested
	
	
	while (list ($num, $ligne) = each ($array) ){
		
		$tab[]="<li><a href=\"$page?$num=yes\"><span style='font-size:14px'>$ligne</span></a></li>\n";
			
	}

	$html="
		<div id='main_upateutility_config' style='background-color:white'>
		<ul>
		". implode("\n",$tab). "
		</ul>
	</div>
		<script>
				$(document).ready(function(){
					$('#main_upateutility_config').tabs();
				});
		</script>
	
	";
		
	
	echo $tpl->_ENGINE_parse_body($html);	
	
}


function settings(){
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$UpdateUtilityEnableHTTP=$sock->GET_INFO("UpdateUtilityEnableHTTP");
	$UpdateUtilityHTTPPort=$sock->GET_INFO("UpdateUtilityHTTPPort");
	$UpdateUtilityHTTPIP=$sock->GET_INFO("UpdateUtilityHTTPIP");
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	$UpdateUtilityRedirectEnable=$sock->GET_INFO("UpdateUtilityRedirectEnable");
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	
	$users=new usersMenus();
	$APP_UFDBGUARD_INSTALLED=0;
	if($users->APP_UFDBGUARD_INSTALLED){
		$APP_UFDBGUARD_INSTALLED=1;
	}
	
	if(!is_numeric($UpdateUtilityRedirectEnable)){$UpdateUtilityRedirectEnable=0;}
	if(!is_numeric($UpdateUtilityEnableHTTP)){$UpdateUtilityEnableHTTP=0;}
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}
	if(!is_numeric($UpdateUtilityHTTPPort)){$UpdateUtilityHTTPPort=9222;}
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	$run_update_task_now=$tpl->javascript_parse_text("{run_update_task_now}");
	$ip=new networking();
	$hash=$ip->ALL_IPS_GET_ARRAY();
	$t=time();
	unset($hash["127.0.0.1"]);
	
	$html="
	<div class=explain style='font-size:14px'>{UpdateUtilityEnableHTTP_explain}</div>
	<table style='width:100%'>
	<tr>
	<td valign='top' style='width:1%'><div id='status-$t'></div></td>
	<td valign='top' style='width:99%'>
	<table style='width:99%' class=form>
	<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{update_for_all_products}:</td>
			<td>". Field_checkbox("UpdateUtilityAllProducts", 1,$UpdateUtilityAllProducts)."</td>
			<td>&nbsp;</td>
		</tr>	
		<tr>
			<td class=legend style='font-size:14px'>{directory}:</td>
			<td>". Field_text("UpdateUtilityStorePath", $UpdateUtilityStorePath,"font-size:14px;width:250px")."</td>
			<td>". button("{browse}", "Loadjs('SambaBrowse.php?field=UpdateUtilityStorePath&no-shares=yes');","12px")."</td>
		</tr>					
		<tr>
			<td colspan=3 align='right'>
					<div style='margin-top:10px'>
					<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('ufdbguard.UpdateUtility.php');\"
					 style='font-size:14px;text-decoration:underline'>{enable_filter_redirection}</a>
			</div>		
			</td>
		</tr>
		<tr>
			<td colspan=3 align='right'><hr>". button("{apply}","SaveUpdateUtilityConf()",16)."</td>
		</tr>	
	</tbody>
	</table>
	</td>
	</tr>
	</table>
	
	<script>
		function UpdateUtilityStatus(){
			LoadAjax('status-$t','$page?status=yes');
		}
	
		

		
	var x_SaveUpdateUtilityConf= function (obj) {
	      var results=obj.responseText;
	      if(results.length>3){alert(results);}
	      RefreshTab('main_upateutility_config');
	}	

	function SaveUpdateUtilityConf(){
			var XHR = new XHRConnection();
			if(document.getElementById('UpdateUtilityAllProducts').checked){XHR.appendData('UpdateUtilityAllProducts','1');}else{XHR.appendData('UpdateUtilityAllProducts','0');}
			XHR.appendData('UpdateUtilityStorePath',document.getElementById('UpdateUtilityStorePath').value);
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);	
		}		
	
	function UpdateUtilityStartTask(){
		if(confirm('$run_update_task_now ?')){
			var XHR = new XHRConnection();
			XHR.appendData('UpdateUtilityStartTask','yes');
			XHR.sendAndLoad('$page', 'POST',x_SaveUpdateUtilityConf);
		}
	
	}
	
	
	UpdateUtilityStatus();		


	</script>
	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function UpdateUtilitySave(){
	$sock=new sockets();
	$sock->SET_INFO("UpdateUtilityAllProducts", $_POST["UpdateUtilityAllProducts"]);
	$sock->SET_INFO("UpdateUtilityRedirectEnable", $_POST["UpdateUtilityRedirectEnable"]);
	$sock->SET_INFO("UpdateUtilityStorePath", $_POST["UpdateUtilityStorePath"]);
	$sock->getFrameWork("services.php?restart-updateutility=yes");
	$sock->getFrameWork("squid.php?rebuild-filters=yes");	
	$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
	
}

function dbsize(){
	$sock=new sockets();
	$page=CurrentPageName();
	$tpl=new templates();
	$refresh=$_GET["refresh"];
	$arrayfile="/usr/share/artica-postfix/ressources/logs/web/UpdateUtilitySize.size.db";
	$array=unserialize(@file_get_contents($arrayfile));
	if(!is_array($array)){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		echo "<script>LoadAjaxTiny('$refresh','$page?dbsize=yes&refresh=$refresh')</script>";
		return;

	}

	if(isset($_GET["recalc"])){
		$sock->getFrameWork("services.php?UpdateUtility-dbsize=yes");
		$array=unserialize(@file_get_contents($arrayfile));
	}

	$t=time();
	$color="black";
	if($array["IPOURC"]>99){$color="red";}
	if($array["POURC"]>99){$color="red";}
	
	$html="

	<table style='width:95%;margin-top:20px' class=form>
	<tr>
		<td class=legend>{current_size}:</td>
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["DBSIZE"])."</td>
	</tr>
	<tr>
		<td class=legend>{hard_drive}:</td>
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["SIZE"])."</td>
	</tr>
	<tr>
		<td class=legend>{used}:</td>
		<td nowrap style='font-weight:bold;font-size:13px'>". FormatBytes($array["USED"])."</td>
	</tr>
	<tr>
		<td class=legend>{free}:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>". FormatBytes($array["AIVA"])." {$array["POURC"]}%</td>
	</tr>
	<tr>
		<td class=legend>inodes:</td>
		<td nowrap style='font-weight:bold;font-size:13px;color:$color'>{$array["IUSED"]}/{$array["ISIZE"]} ({$array["IPOURC"]}%)</td>
	</tr>	
	
	<tr>
		<td colspan=2 align='right'>". imgtootltip("20-refresh.png","{refresh}","LoadAjax('$refresh','$page?dbsize=yes&recalc=yes&refresh=$refresh')")."</td>
	</tr>
	</table>

	";


	echo $tpl->_ENGINE_parse_body($html);

}

function product_section(){
	$sock=new sockets();
	$UpdateUtilityAllProducts=$sock->GET_INFO("UpdateUtilityAllProducts");
	if(!is_numeric($UpdateUtilityAllProducts)){$UpdateUtilityAllProducts=1;}	
	
	$page=CurrentPageName();
	$tpl=new templates();
	$productKey=$_GET["product-key"];
	$update=new updateutilityv2();
	$Array=$update->families[$productKey]["LIST"];
	$html="<center><center class=form style='width:65%'>";
	while (list ($ProductKey, $ProductKeyArray) = each ($Array) ){
		$ProductName=$ProductKeyArray["NAME"];
		if(count($ProductKeyArray["PRODUCTS"])==0){continue;}
		$html=$html."
		
		<table cellspacing='0' cellpadding='0' border='0' class='tableView' >
		<thead class='thead'>
			<tr>
			<th colspan=2 style='font-size:14px'>{$ProductName}</th>
			</tr>
		</thead>
		<tbody class='tbody'>";		
		$classtr=null;	
		while (list ($ProductSubKey, $ProductVersion) = each ($ProductKeyArray["PRODUCTS"]) ){
				if($ProductVersion=="Administration Tools"){continue;}
				if($ProductVersion=="Kaspersky Administration Kit"){continue;}
				if($ProductVersion=="Kaspersky Security Center"){continue;}
				if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
				$enabled=0;
				if($update->MAIN_ARRAY["ComponentSettings"][$ProductSubKey]=="true"){
					$img=imgtootltip("check-32.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}else{
					$img=imgtootltip("check-32-grey.png","{enable}","UpdateUtilityEnable('$ProductSubKey')",null,$ProductSubKey);
				}
				
				if($UpdateUtilityAllProducts==1){
					$img="<img src='img/service-check-32.png'>";
				}
				
				
			$html=$html . "
		<tr class=$classtr>
			
			<td style='font-size:16px'>$ProductVersion</td>
			<td style='font-size:16px' width=1%>$img</td>
		</tr>";
			
		}
		
		$html=$html . "</tbody>
		</table><br>";
		
	}
	
	
	$html=$html."</center></center>
	<script>
		function UpdateUtilityEnable(ProductSubKey){
			var XHR = new XHRConnection();
			XHR.appendData('ProductSubKey',ProductSubKey);
			var img=document.getElementById(ProductSubKey).src;
			if(img.indexOf('32-grey')>0){
				document.getElementById(ProductSubKey).src='/img/check-32.png';
				XHR.appendData('value','true');
			}else{
				document.getElementById(ProductSubKey).src='/img/check-32-grey.png';
				XHR.appendData('value','false');
			}
			
			XHR.sendAndLoad('$page', 'POST');
		}
	
	
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
}

function product_section_save(){
	$update=new updateutilityv2();
	$update->MAIN_ARRAY["ComponentSettings"][$_POST["ProductSubKey"]]=$_POST["value"];
	$update->Save();
}

function UpdateUtilityStartTask(){
	$sock=new sockets();
	$sock->getFrameWork("services.php?UpdateUtilityStartTask=yes");
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{apply_upgrade_help}");
	
	
	
}

function webevents_table(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$zDate=$tpl->_ENGINE_parse_body("{zDate}");
	$url=$tpl->_ENGINE_parse_body("{url}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$buttons="
	buttons : [
	{name: '$new_rule', bclass: 'add', onpress : AddBandRule},
	],";		
		$buttons=null;

	
$html="
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>

	
<script>
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?web-events=yes',
	dataType: 'json',
	colModel : [
		{display: '$zDate', name : 'date', width : 134, sortable : true, align: 'center'},
		{display: 'Code', name : 'code', width : 36, sortable : true, align: 'left'},	
		{display: '$url', name : 'url', width :542, sortable : false, align: 'left'},
		{display: '$size', name : 'size', width :50, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$zDate', name : 'zDate'},
		{display: 'Code', name : 'Code'},
		{display: '$url', name : 'uri'},
		],
	sortname: 'ID',
	sortorder: 'desc',
	usepager: true,
	title: '',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 830,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

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
				$search="\[.*?$search";
			}
			
			if($_POST["qtype"]=="Code"){
				$search='"\s+'.$search."\s+";
			}

			if($_POST["qtype"]=="uri"){
				$search='".*?'.$search.'.*?"';
			}				
			
		}
		
	}
	
	if($search<>null){$search="&search=".base64_encode($search);}
	
	$tables=unserialize(base64_decode($sock->getFrameWork("squid.php?UpdateUtility-webevents=yes&rp={$_POST["rp"]}$search")));
	
		
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
		if(!preg_match('#(.+?)\s+(.+?)\s+(.*?)\s+\[(.+?)\+.*?\]\s+"(.+?)"\s+([0-9]+)\s+([0-9]+)#', $line,$re)){continue;}
		$color="black";
		$from=$re[1];
		$to=$re[2];
		$uid=$re[3];
		$date=$re[4];
		$url=$re[5];
		$code=$re[6];
		$size=intval($re[7]);
		$size=$size/1024;
		$size=FormatBytes($size);
		if(preg_match("#(.*?)\s+(.*)\s+#", $url,$ri)){$url=$ri[2];}
		
		if($code==404){$color="#BA0000";}
		
		
		$data['rows'][] = array(
		'id' => $ID,
		'cell' => array(
			"<span style='font-size:13px;color:$color'>$date</span>",
			"<span style='font-size:13px;color:$color'>$code</span>",
			"<span style='font-size:13px;color:$color'>$url</span>",
			"<span style='font-size:13px;color:$color'>$size</span>",
		
		
		)
		);
		
		
	}
	
	
echo json_encode($data);		
	
}

