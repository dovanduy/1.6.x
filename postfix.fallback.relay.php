<?php
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
session_start();
include_once("ressources/class.templates.inc");
include_once("ressources/class.ldap.inc");
include_once("ressources/class.main_cf.inc");

if(isset($_GET["PostfixAddFallBackServer"])){PostfixAddFallBackServer();exit;}
if(isset($_POST["PostfixAddFallBackerserverSave"])){PostfixAddFallBackerserverSave();exit;}
if(isset($_GET["PostfixAddFallBackerserverLoad"])){PostfixAddFallBackerserverList();exit;}
if(isset($_POST["PostfixAddFallBackerserverDelete"])){PostfixAddFallBackerserverDelete();exit;}
if(isset($_GET["PostfixAddFallBackServerMove"])){PostfixAddFallBackServerMove();exit;}
if(isset($_GET["popup-index"])){popup_index();exit;}
if(isset($_GET["about"])){about();exit;}
js();

function js(){
$prefix=str_replace(".","_",CurrentPageName());
$page=CurrentPageName();
$tpl=new templates();
$title=$tpl->_ENGINE_parse_body('{smtp_fallback_relay}');
if($_GET["hostname"]==null){$_GET["hostname"]="master";}	
	$users=new usersMenus();
	if(!$users->AsPostfixAdministrator){
		$error=$tpl->_ENGINE_parse_body("{ERROR_NO_PRIVS}");
		echo "alert('$error')";
		die();
	}
	


	
$html="function {$prefix}Loadpage(){
	YahooWin5('650','$page?popup-index=yes&hostname={$_GET["hostname"]}','$title:: instance: {$_GET["hostname"]}');
	}


 {$prefix}Loadpage();
";	
 
 echo $html;
	
}

function popup_index(){

	$sock=new sockets();
	
	$page=CurrentPageName();
	$tpl=new templates();
	$t=time();
	$time=$t;
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$MX_lookups=$tpl->_ENGINE_parse_body("{MX_lookups}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$smtp_port=$tpl->_ENGINE_parse_body("{smtp_port}");
	$relay_address=$tpl->_ENGINE_parse_body("{relay_address}");
	$title=$tpl->_ENGINE_parse_body("{smtp_fallback_relay}");
	$add_server=$tpl->_ENGINE_parse_body("{new_server}");

	$buttons="
	buttons : [
	{name: '$add_server', bclass: 'add', onpress : PostfixAddFallBackServerNew},
	{name: 'About', bclass: 'Help', onpress : About$t},
	],";
	
	$html="<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
	<script>
	var rowid=0;
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?PostfixAddFallBackerserverLoad=yes&hostname={$_GET["hostname"]}&time=$t',
	dataType: 'json',
	colModel : [
	{display: '$relay_address', name : 'relay_address', width : 285, sortable : true, align: 'left'},
	{display: '$smtp_port', name : 'smtp_port', width : 80, sortable : true, align: 'center'},
	{display: '$MX_lookups', name : 'MX_lookups', width :72, sortable : false, align: 'center'},
	{display: '&nbsp;', name : 'none', width :70, sortable : false, align: 'center'},
	{display: '$delete', name : 'delete', width : 35, sortable : false, align: 'center'},
	],
	$buttons
	searchitems : [
	{display: '$relay_address', name : 'relay_address'},
	],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 630,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});
	});
	
	function BrowseAD(){
	Loadjs('BrowseActiveDirectory.php');
	}
	
	function GroupsDansSearch(){
	$('#flexRT$t').flexReload();
	
	}
	
	function About$t(){
		YahooWinBrowse('500','$page?about=yes','About...');
	}
	
	function PostfixAddFallBackServerNew(){
		PostfixAddFallBackServer('');
	}
	
	function PostfixAddFallBackServer(Routingdomain){
		if(!Routingdomain){Routingdomain='';}
		YahooWin6(430,'$page?PostfixAddFallBackServer=yes&t=$t&hostname={$_GET["hostname"]}&domainName='+Routingdomain,'$add_server')
	}
	
	var x_PostfixAddFallBackerserverDelete=function(obj){
    	var tempvalue=trim(obj.responseText);
	  	if(tempvalue.length>3){alert(tempvalue);}
		$('#flexRT$t').flexReload();
	}		
	
	function PostfixAddFallBackerserverDelete(index){
		var XHR = new XHRConnection();	
		XHR.appendData('PostfixAddFallBackerserverDelete',index);
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.sendAndLoad('$page', 'POST',x_PostfixAddFallBackerserverDelete);
	}	

	var x_PostfixAddFallBackServerMove=function(obj){
    	var tempvalue=trim(obj.responseText);
	  	if(tempvalue.length>3){alert(tempvalue);}
		RefreshFailBackServers();
		}	


	function PostfixAddFallBackServerMove(num,move){
		var XHR = new XHRConnection();	
		XHR.appendData('PostfixAddFallBackServerMove',num);
		XHR.appendData('move',num);
		XHR.appendData('hostname','{$_GET["hostname"]}');			
		XHR.sendAndLoad('$page', 'GET',x_PostfixAddFallBackServerMove);	
				
	}	
	
	function DansGuardianEditGroup(ID,rname){
	YahooWin3('712','dansguardian2.edit.group.php?ID='+ID+'&t=$t','$group::'+ID+'::'+rname);
	
	}
	
	var x_DansGuardianDelGroup= function (obj) {
	var res=obj.responseText;
	if (res.length>3){alert(res);}
	$('#row'+rowid).remove();
	}
	
	function DansGuardianDelGroup(ID){
	if(confirm('$do_you_want_to_delete_this_group ?')){
	rowid=ID;
	var XHR = new XHRConnection();
	XHR.appendData('Delete-Group', ID);
	XHR.sendAndLoad('$page', 'POST',x_DansGuardianDelGroup);
	}
	}
	
	</script>
	";
	
	echo $html;
	
	}
function about(){
$page=CurrentPageName();
$tpl=new Templates();
$time=time();

$html="
<div class=text-info style='font-size:14px'>{smtp_fallback_relay_tiny}<br>{smtp_fallback_relay_text}</div>

";




echo $tpl->_ENGINE_parse_body($html);
}


function PostfixAddFallBackServer(){
	$ldap=new clladp();
	$page=CurrentPageName();
	$t=$_GET["t"];
	if($_GET["domainName"]<>null){
		$main=new main_cf();
		$tool=new DomainsTools();
		$arr=explode(',',$main->main_array["smtp_fallback_relay"]);
		if(is_array($arr)){
			$array=$tool->transport_maps_explode($arr[$_GET["domainName"]]);
			$relay_address=$array[1];
			$smtp_port=$array[2];
			$MX_lookup=$array[3];
			$hidden="<input type='hidden' name='TableIndex' value='{$_GET["domainName"]}'>";
		}
	}
	
	if($smtp_port==null){$smtp_port=25;}
	if($MX_lookup==null){$MX_lookup='yes';}
	
	$html="<div id='PostfixAddFallBackerserverSaveID'></div>
	$hidden
	<input type='hidden' name='PostfixAddFallBackerserverSave' value='yes'>
	<table style='width:99%' class=form>
	<td align='right' nowrap class=legend><strong>{relay_address}:</strong></td>
	<td>" . Field_text('relay_address',$relay_address,"font-size:14px;witdh:210px") . "</td>	
	</tr>
	</tr>
	<td align='right' nowrap class=legend><strong>{smtp_port}:</strong></td>
	<td>" . Field_text('relay_port',$smtp_port,"font-size:14px;witdh:60px") . "</td>	
	</tr>	
	<tr>
	
	<td class=legend>{MX_lookups}</td>	
	<td align='right' nowrap>" . Field_checkbox('MX_lookups',1,$MX_lookup)."</td>
	</tr>

	<tr>
	<td align='right' colspan=2><hr>". button("{add}","XHRPostfixAddFallBackerserverSave()",16)."</td>
	</tr>		
	<tr>
	<td align='left' colspan=2><div class=text-info>{MX_lookups}</strong><br>{MX_lookups_text}</div></td>
	</tr>			
		
	</table>
	<script>
	
	var x_XHRPostfixAddFallBackerserverSave=function(obj){
    	var tempvalue=trim(obj.responseText);
	  	if(tempvalue.length>3){alert(tempvalue);}
		document.getElementById('PostfixAddFallBackerserverSaveID').innerHTML='';
		$('#flexRT$t').flexReload();
		}	
	
		function XHRPostfixAddFallBackerserverSave(){
		var XHR = new XHRConnection();	
			if(document.getElementById('MX_lookups').checked){XHR.appendData('MX_lookups','yes');}else{XHR.appendData('MX_lookups','no');}
			XHR.appendData('PostfixAddFallBackerserverSave','yes');
			XHR.appendData('relay_port',document.getElementById('relay_port').value);
			XHR.appendData('relay_address',document.getElementById('relay_address').value);
			XHR.appendData('relay_port',document.getElementById('relay_port').value);
			XHR.appendData('hostname','{$_GET["hostname"]}');
			AnimateDiv('PostfixAddFallBackerserverSaveID');
			XHR.sendAndLoad('$page', 'POST',x_XHRPostfixAddFallBackerserverSave);				
		
		}
		

		
	</script>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function PostfixAddFallBackerserverSave(){
	$relay_address=$_POST["relay_address"];
	$tpl=new templates();
	
	if($relay_address==null){echo $tpl->_ENGINE_parse_body('{error_give_server}');return null;}
	
	$smtp_port=$_POST["relay_port"];
	$MX_lookups=$_POST["MX_lookups"];
	
	writelogs("Edit $relay_address $smtp_port $MX_lookups tool->transport_maps_implode($relay_address,$smtp_port,null,$MX_lookups)",__FUNCTION__,__FILE__);
	
	$tool=new DomainsTools();
	$line=$tool->transport_maps_implode($relay_address,$smtp_port,null,$MX_lookups);
	$line=str_replace("smtp:",'',$line);
	$main=new maincf_multi($_POST["hostname"]);
	$arr=explode(',',$main->GET_BIGDATA("smtp_fallback_relay"));
	
	
	if(isset($_GET["TableIndex"])){
		writelogs("Edit " . $arr[$_GET["TableIndex"]] . " to " . $line,__FUNCTION__,__FILE__);
		$arr[$_GET["TableIndex"]]=$line;
	}
	
	
	if(is_array($arr)){
		while (list ($index, $ligne) = each ($arr) ){
				if($ligne<>null){$array[]=$ligne;}
			}
		}

	if(!isset($_GET["TableIndex"])){$array[]=$line;}
	$main->SET_BIGDATA("smtp_fallback_relay",implode(",",$array));
	
}

function PostfixAddFallBackerserverList(){
	$main=new maincf_multi($_GET["hostname"]);
	$tpl=new templates();
	$Mypage=CurrentPageName();
	$add=imgtootltip("plus-24.png","{add_server_domain}","PostfixAddFallBackServer()");
	$hash=explode(',',$main->GET_BIGDATA("smtp_fallback_relay"));
	$tool=new DomainsTools();

	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash);
	$data['rows'] = array();	
	$search=string_to_flexregex();
	$c=0;
	if(is_array($hash)){
		while (list ($index, $ligne) = each ($hash) ){
				
				if($ligne==null){continue;}
				
				$arr=$tool->transport_maps_explode("smtp:$ligne");
				if($search<>null){if(!preg_match("#$search#", $arr[1])){continue;}}
				$cell_up="<td width=1%>" . imgsimple('arrow_up.gif','{up}',"PostfixAddFallBackServerMove('$index','up')") ."</td>";
				$cell_down="<td width=1%>" . imgsimple('arrow_down.gif','{down}',"PostfixAddFallBackServerMove('$index','down')") ."</td>";	

				$data['rows'][] = array(
						'id' => $ligne['ID'],
						'cell' => array(
								"<code style='font-size:14px'><a href=\"javascript:PostfixAddFallBackServer('$index');\">{$arr[1]}</a></code>",
								"<span style='font-size:14px;color:$color;'>{$arr[2]}</span>",
								"<span style='font-size:14px;color:$color;'>{$arr[3]}</span>",
								"<span style='font-size:14px;color:$color;'><table><tr>$cell_up$cell_down</tr></table></span>",
								imgsimple("delete-32.png",'{delete}',"PostfixAddFallBackerserverDelete('$index')")
						)
						);				
				$c++;
			}
	}
	$data['total'] = $c;
	echo json_encode($data);	
}
function PostfixAddFallBackerserverDelete(){
	$main=new maincf_multi($_POST["hostname"]);
	$arr=explode(',',$main->GET_BIGDATA("smtp_fallback_relay"));

		if(is_array($arr)){
			unset($arr[$_POST["PostfixAddFallBackerserverDelete"]]);
		}
	$main->SET_BIGDATA("smtp_fallback_relay",implode(",",$arr));
	
}
function PostfixAddFallBackServerMove(){
	$main=new main_cf();
	$main=new maincf_multi($_GET["hostname"]);
	$hash=explode(',',$main->GET_BIGDATA("smtp_fallback_relay"));	
	$newarray=array_move_element($hash,$hash[$_GET["PostfixAddFallBackServerMove"]],$_GET["move"]);
	$main->SET_BIGDATA("smtp_fallback_relay",implode(",",$newarray));
	
}



?>