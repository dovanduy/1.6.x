<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.system.network.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.awstats.inc');
	include_once('ressources/class.pdns.inc');
	
	

	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["events-list"])){events_list();exit;}
	if(isset($_POST["restore-path"])){restore_perform();exit;}
	js();
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{restore}");
	$t=$_GET["t"];
	$html="YahooWin2('650','$page?popup=yes&t=$t','$title')";
	echo $html;
}

function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t=$_GET["t"];
	$tt=time();
	$events=$tpl->_ENGINE_parse_body("{events}");
	$restore_sitename_ask=$tpl->javascript_parse_text("{restore_sitename_ask}");
	$restore_from_container=$tpl->javascript_parse_text("{restore_from_container_ask}");
	$backup_container_path=$tpl->javascript_parse_text("{backup_container_path}");
	$html="
	<table style='width:100%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{backup_container_path}:</td>
		<td>". Field_text("restore-path-$tt",null,"font-size:14px;width:99%")."</td>
		<td>". button("{browse}...", "Loadjs('tree.php?select-file=gz&target-form=restore-path-$tt');",11)."</td>
	</tr>
	<tr>
		<td class=legend style='font-size:14px'>{mysql_instance}:</td>
		<td><span id='freeweb-mysql-instances-$tt'></span></td>
		<td>&nbsp;</td>
	</tr>	
	
	
	<tr>
		<td class=legend style='font-size:14px'>{sitename}:</td>
		<td>". Field_text("restore-site-$tt",null,"font-size:14px;width:99%")."</td>
		<td>&nbsp;</td>
	</tr>	
	<tr>
		<td colspan=3 align='right' style='padding-top:10px'>". button("{restore}", "ResTore$tt()",16)."</td>
	</tr>
	</table>
	
	<table class='restore-freeweb-list' style='display: none' id='restore-freeweb-list' style='width:99%'></table>
	
<script>	

	var x_ResTore$tt= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		$('#restore-freeweb-list').flexReload();
	 }	
	
	function ResTore$tt(){
		
		var ss=document.getElementById('restore-site-$tt').value;
		var sp=document.getElementById('restore-path-$tt').value;
		var instance_id=document.getElementById('mysql_instance_id$tt').value;
		if(sp.length==0){alert('$backup_container_path is not set');return;}
		var zconfirm='$restore_sitename_ask ' +ss+' ?'; 
		if(ss.length==0){zconfirm='$restore_from_container ' +sp+' ?';}
		
		if(confirm(zconfirm)){
			var XHR = new XHRConnection();
			XHR.appendData('restore-site',ss);
			XHR.appendData('restore-path',sp);
			XHR.appendData('instance-id',instance_id);
			XHR.sendAndLoad('$page', 'POST',x_ResTore$tt);
		}
		
	}

function FillTable$tt(){
	$(document).ready(function(){
	$('#restore-freeweb-list').flexigrid({
		url: '$page?events-list=yes',
		dataType: 'json',
		colModel : [
			{display: '&nbsp;', name : 'icon', width : 31, sortable : false, align: 'center'},
			{display: '$events', name : 'event', width : 555, sortable : true, align: 'left'},
		],
		searchitems : [
			{display: '$events', name : 'event'},
			],	
	
		sortname: 'zdate',
		sortorder: 'desc',
		usepager: true,
		title: '$events',
		useRp: true,
		rp: 15,
		showTableToggleBtn: true,
		width: 630,
		height: 300,
		singleSelect: true
		
		});   
	});	
}


	
	function freeweb_mysql_instances$tt(){
		LoadAjaxTiny('freeweb-mysql-instances-$tt','freeweb.edit.php?freeweb-mysql-instances-field=yes&servername=&t=$tt');
	
	}

	function mysql_instance_id_check(){}

freeweb_mysql_instances$tt();
FillTable$tt();
</script>	
	";
	
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
	
	
}

function restore_perform(){
	
	
	$container=$_POST["restore-path"];
	$sitename=trim(strtolower($_POST["restore-site"]));
	if($sitename==null){$sitename="DEFAULT";}
	@file_put_contents("ressources/logs/web/freewebs.restore", "{scheduled} {restore} container {from} &laquo;$container&raquo; {to} $sitename");
	$sock=new sockets();
	$container=base64_encode($container);
	$sitename=base64_encode($sitename);
	$sock->getFrameWork("freeweb.php?restore-site=yes&path=$container&sitename=$sitename&instance-id={$_POST["instance-id"]}");
	
	
}


function events_list(){
$page=CurrentPageName();
	$tpl=new templates();	
	if(!is_file("ressources/logs/web/freewebs.restore")){json_error_show("No data");}
	
	
	$data = array();
	$data['page'] = 1;
	$data['total'] = $total;
	$data['rows'] = array();	
	
	$f=file("ressources/logs/web/freewebs.restore");
	$data['total']=count($f);
	
	if($_POST["sortorder"]=="desc"){krsort($f);}
	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
	}
	$c=0;
	while (list ($num_line, $evenement) = each ($f)){
		$evenement=str_replace("\r\n", "", $evenement);
		$evenement=str_replace("\r", "", $evenement);
		$evenement=trim($evenement);
		if($evenement==null){continue;}
		
		$id=md5($evenement);
		$textadd=null;
		$evenement=$tpl->_ENGINE_parse_body("$evenement");
		
		$img="<img src='img/icon_info.gif'>";
		if(preg_match("#(failed|fatal|error|Notice)#i",$evenement)){
			$img="<img src='img/warning-panneau-24.png'>";
		}else{
			if(preg_match("#(success)#i",$evenement)){
				$img="<img src='img/status_ok.gif'>";
			}
		}
		
		if(preg_match("#Notice:\s+(.+?)\s+in(.+?)\s+on line\s+([0-9]+)#", $evenement,$re)){
			$evenement="{$re[1]}";
			$file=basename($re[2]);
			$textadd=$textadd."&nbsp;<i style='font-size:10px'>file: $file line:{$re[3]}</i>";	
		}
		
		if($search<>null){
			if(!preg_match("#$search#i", $evenement)){continue;}
		}
		if(preg_match("#\[([0-9]+)\]\s+\[DAEMON\]::::(.+?)::(.+)#",$evenement,$re)){
			$evenement=$re[3];
			$textadd="<i style='font-size:10px'>PID: {$re[1]} function:{$re[2]}</i>";
			
		} 
		
		if(preg_match("#(.+?)\s+in\s+(.+?)\s+line\s+(.+)#",$evenement,$re)){
			$evenement=$re[1];
			$line=$re[3];
			if(!is_numeric($line)){$line=0;}
			$textadd=$textadd."&nbsp;<i style='font-size:10px'>class: {$re[2]} line:$line</i>";			
		}

		
		
		
		
		
		$c++;
	$data['rows'][] = array(
		'id' => $id,
		'cell' => array($img,"<psan style='font-size:12px;'>$evenement<div>$textadd</div></span>",

		)
		);
	}
	
	$data['total'] = $c;
echo json_encode($data);			
	
}
	
	
	
