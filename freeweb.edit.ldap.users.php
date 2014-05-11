<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.pure-ftpd.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["freeweb-member-list"])){memberslist();exit;}
	if(isset($_POST["freeweb-member-add"])){membersAdd();exit;}
	if(isset($_POST["freeweb-member-del"])){membersDel();exit;}
	
	
	
	js();
	
function js(){
	$tpl=new templates();
	$page=CurrentPageName();
	$title=$tpl->_ENGINE_parse_body("{authentication}&nbsp;&raquo;{members}&nbsp;&raquo;{$_GET["servername"]}");
	$html="YahooWin6('550','$page?popup=yes&servername={$_GET["servername"]}','$title')";
	echo $html;
	}
	
function popup(){
	
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql();
	$TB_HEIGHT=350;
	$TB_WIDTH=350;
	$t=time();
	$tt=time();
	$freeweb=new freeweb($_GET["servername"]);
	if($freeweb->ou<>null){$suffix="&organization=$freeweb->ou";}	
	$title=$tpl->_ENGINE_parse_body("{members}/{groups}");
	$new_entry=$tpl->_ENGINE_parse_body("{new_item}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$new_volume=$tpl->_ENGINE_parse_body("{new_volume}");
	$ipaddr=$tpl->_ENGINE_parse_body("{addr}");
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$link_member=$tpl->_ENGINE_parse_body("{link_member}");
	
	$buttons="
	buttons : [
	{name: '$link_member', bclass: 'Add', onpress : LinkMember$t},

	],	";
	//$('#flexRT$t').flexReload();
	$html="
	<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:99%'></table>
	<script>
	var mem$t='';
	$(document).ready(function(){
	$('#flexRT$t').flexigrid({
	url: '$page?freeweb-member-list=yes&servername={$_GET["servername"]}&t=$t',
			dataType: 'json',
			colModel : [
			{display: '&nbsp;', name : 'icon', width :31, sortable : true, align: 'center'},
			{display: '$members', name : 'name', width :414, sortable : true, align: 'left'},
			{display: '&nbsp;', arrow : 'name', width :31, sortable : true, align: 'center'},
			],
			$buttons
	
			searchitems : [
			{display: '$members', name : 'name'},
			],
				
			sortname: 'name',
			sortorder: 'asc',
			usepager: true,
			title: '$title',
			useRp: true,
			rp: 50,
			showTableToggleBtn: false,
			width: 535,
			height: $TB_HEIGHT,
			singleSelect: true,
			rpOptions: [10, 20, 30, 50,100,200,500]
	
	});
	});
	
	
	function LinkMember$t(){
		Loadjs('MembersBrowse.php?callback=LinkMemberCallBack$t$suffix&prepend=1&prepend-guid=1');
	}
	
		var x_LinkMemberCallBack$t=function (obj) {
			var results=obj.responseText;
			if(results.length>0){alert(results);}	
			$('#flexRT$t').flexReload();	
		}			
	
	function LinkMemberCallBack$t(id,prependText,guid){
			var XHR = new XHRConnection();
			XHR.appendData('freeweb-member-add',prependText+id);
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_LinkMemberCallBack$t);	
	}
	
		function DeleteLDAPAUthMember$t(val){
			var XHR = new XHRConnection();
			XHR.appendData('freeweb-member-del',val);
			XHR.appendData('servername','{$_GET["servername"]}');
    		XHR.sendAndLoad('$page', 'POST',x_LinkMemberCallBack$t);
		}	
	
	</script>";
	
	echo $html;	
}

function membersAdd(){
	$freeweb=new freeweb($_POST["servername"]);
	$users=$freeweb->Params["LDAP"]["members"][$_POST["freeweb-member-add"]]=true;
	$freeweb->SaveParams();
}
function membersDel(){
	$freeweb=new freeweb($_POST["servername"]);
	unset($freeweb->Params["LDAP"]["members"][$_POST["freeweb-member-del"]]);
	$freeweb->SaveParams();	
}

function memberslist(){
	$freeweb=new freeweb($_GET["servername"]);
	$users=$freeweb->Params["LDAP"]["members"];
	$tpl=new templates();
	$page=1;
	$t=$_GET["t"];
	if(!is_array($users)){
		json_error_show("No member",1);
	}
	
	if(count($users)==0){
		json_error_show("No member",1);
	}
	
	$data = array();
	$data['page'] = $page;
	$data['total'] = 0;
	$data['rows'] = array();

	$search=string_to_flexregex();
	$c=0;
	while (list ($num, $ligne) = each ($users) ){
		if($search<>null){
			if(!preg_match("#$search#", $ligne)){continue;}
		}
		$gid=0;
		$id=md5($ligne);
		$delete=imgsimple("delete-32.png","{delete}","DeleteLDAPAUthMember$t('$num')");
		$Displayname=null;
		
		if(preg_match("#^group:@(.+?):([0-9]+)#",$num,$re)){
			$img="wingroup.png";
			$Displayname="{$re[1]} ({$re[2]})";
			$gid=$re[2];
		}
		
		if($Displayname==null){
			if(preg_match("#^user:(.+)#",$num,$re)){
				$img="user-18.png";
				$Displayname="{$re[1]}";
			}
		}
		if($Displayname==null){
			if(preg_match("#^group:@(.+?)$#",$num,$re)){
				$img="wingroup.png";
				$Displayname="{$re[1]}";
			}		
		}	
		if($Displayname==null){$Displayname=$num;}
		
		$c++;
		$data['rows'][] = array(
				'id' => $id,
				'cell' => array(
						"<img src='img/$img'>",
						"<span style='font-size:16px;font-weight:bold'>$Displayname</span>",
						$delete
						
				)
		);		
		
		
		
	}
	$data['total'] = $c;
	echo json_encode($data);
}



