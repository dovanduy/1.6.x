<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.mysql.inc');
	include_once('ressources/class.active.directory.inc');
	include_once("ressources/class.harddrive.inc");
	include_once("ressources/class.ldap-extern.inc");

	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["buildtree"])){buildtree();exit;}
	if(isset($_GET["browser-infos"])){brower_infos();exit;}
	if(isset($_POST["create-folder"])){folder_create();exit;}
	if(isset($_POST["delete-folder"])){folder_delete();exit;}
	if(isset($_GET["browse-ou"])){browse_groups_for_ou();exit;}
	
	js();

function js(){
	header("content-type: application/x-javascript");
	$t=time();
	$title=$_GET["ou"];
	$_GET["ou"]=urlencode($_GET["ou"]);
	$page=CurrentPageName();
	if($title==null){$title="OpenLDAP server";}
	echo "YahooWinBrowse(650,'$page?popup=yes&ou={$_GET["ou"]}&function={$_GET["function"]}&t=$t','Browse::$title');";
}

function popup(){
	$tpl=new templates();
	$t=$_GET["t"];
	$page=CurrentPageName();
	$ldap=new clladp();
	
	if(!isset($_GET["ou"])){$_GET["ou"]=null;}
	if($_GET["ou"]==null){
		$title="OpenLDAP";
		$ous=$ldap->hash_get_ou(true);
	}else{
		$title=$_GET["ou"];
		$ous[$title]=true;
	}

	$style=" OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$organization=$tpl->_ENGINE_parse_body("{organization}");
	$f[]="<ul id='root-$t' class='jqueryFileTree'>";
	$f[]="<li class=root>Root: $title";
	$f[]="<ul id='mytree-$t' class='jqueryFileTree'>";
	

	
	while (list ($ou, $ligne) = each ($ous) ){
		$CLASS="directory";
		$id=md5($ou);
		$js=texttooltip("$organization $ou",$ou ,"TreeOuExpand$t('$id','$ou');");
		$f[]="<li class=$CLASS collapsed id='$id' $style>$js</li>";
		
	}
	
	$f[]="</ul>";
	$f[]="</li>";
	$f[]="</ul>";
	
$f[]="<script>
var mem_id$t='';
var mem_path$t='';

var xTreeOuExpand$t= function (obj) {
	var results=obj.responseText;
	$('#'+mem_id$t).removeClass('collapsed');
	if($('#'+mem_id$t).hasClass('directorys')){\$('#'+mem_id$t).addClass('expandeds');}
	if($('#'+mem_id$t).hasClass('directory')){\$('#'+mem_id$t).addClass('expanded');}
	$('#'+mem_id$t).append(results);
}

	function TreeOuExpand$t(id,ou){
		mem_id$t=id;
		mem_path$t=ou;
		var expanded=false;
		if($('#'+mem_id$t).hasClass('expanded')){expanded=true;}
		if(!expanded){if($('#'+mem_id$t).hasClass('expandeds')){expanded=true;}}
			
		if(!expanded){
			var XHR = new XHRConnection();
			XHR.appendData('browse-ou',ou);
			XHR.appendData('function','{$_GET["function"]}');
			XHR.sendAndLoad('$page', 'GET',xTreeOuExpand$t);
		}else{
			$('#'+mem_id$t).children('ul').empty();
			if($('#'+mem_id$t).hasClass('expanded')){\$('#'+mem_id$t).removeClass('expanded');}
			if($('#'+mem_id$t).hasClass('expandeds')){\$('#'+mem_id$t).removeClass('expandeds');}
			$('#'+mem_id$t).addClass('collapsed');
	
		}
	}
	
		
</script>";
	
echo @implode("\n", $f);

}

function browse_groups_for_ou(){
	$ou=$_GET["browse-ou"];
	$t=$_GET["t"];
	$function=$_GET["function"];
	$ldap=new clladp();
	$tpl=new templates();
	$groups=$ldap->hash_groups($ou,1);
	$id=md5($ou);
	if($GLOBALS["VERBOSE"]){print_r($groups);}
	if(!is_array($groups)){
		if($GLOBALS["VERBOSE"]){ echo "<H1>Not an array</H1>";}
		return null;}
	$style=" OnMouseOver=\";this.style.cursor='pointer';\" OnMouseOut=\";this.style.cursor='default';\"";
	$f[]="<ul id='$id' class='jqueryFileTree'>";
	ksort($groups);
	$group=$tpl->_ENGINE_parse_body("{group2}");
	while (list ($num, $groupname) = each ($groups) ){
		if($GLOBALS["VERBOSE"]){echo "$num -> $groupname<br>\n";}
		$id=$num;
		$CLASS="group";
		$f[]="<li class=$CLASS collapsed id='$id'>
			<a href=\"#\" OnClick=\"javascript:$function('$num','$groupname');YahooWinBrowseHide();\">$groupname</a></li>";
	}
	
	$f[]="</ul>";	
	echo @implode("\n", $f);
	
}


function brower_infos(){
	$tpl=new templates();
	$page=CurrentPageName();
	$root=base64_decode($_GET["browser-infos"]);
	$RootTile=$root;
	$ldap=new ldapAD();
	if($RootTile==null){$RootTile=$ldap->suffix;}
	$RootTileS=explode(",", $RootTile);
	$titleOU=$RootTileS[0];
	
	if(trim($_GET["function"])<>null){
		$select="<table class=form><tbody>
		<tr>
		<td width=1%>". imgtootltip("32-plus.png","{select_this_ou}","{$_GET["function"]}('{$_GET["browser-infos"]}')")."</td>
		<td><a href=\"javascript:blur();\" OnClick=\"javascript:{$_GET["function"]}('{$_GET["browser-infos"]}')\"  style='font-size:14px;text-decoration:underline'>{select_this_ou}</td>
		</tr>
		</tbody>
		</table>
		";
		
	}
	
	$give_folder_name=$tpl->javascript_parse_text("{give_folder_name}");
	if(!is_numeric($_GET["replace-start-root"])){$_GET["replace-start-root"]=0;}

	$orginal_root=base64_decode($_GET["org-root"]);
	$strippedroot=str_replace($orginal_root, "",$root);
	$share_this=$tpl->javascript_parse_text("{share_this}: $RootTile ?");
	$delete_text=$tpl->javascript_parse_text("{delete} ?: $root ?");
	//$root_url=urlencode($root);
	if($_GET["field"]<>null){
		$select="
		<tr>
			<td width=1% valign='top'>
		" . imgtootltip('folder-granted-properties-48.png','{select_this_ou}',"SelectFolder()")."</td>
			<td><a href=\"javascript:blur();\" OnClick=\"javascript:SelectFolder()\"  style='font-size:14px;text-decoration:underline'>{select_this_ou}</td>
		</tr>
		";
		
	}
	
	if($ldap->suffix==$root){$select=null;}
	if($root==null){$select=null;}
	
$ldap=new ldapAD();
		if(count($res)>0){return $res;}
		$ld =$ldap->ldap_connection;
		$bind =$ldap->ldapbind;
		$suffix=$root;
		$res=array();
		$arr=array();
		
		$sr = @ldap_list($ld,$root,'(objectclass=group)',$arr);
		if ($sr) {
			
			$groups="
			<div id='groups-div' style='width:100%;height:250px;overflow:auto'>
			<table style='width:100%'>
			<tbody>
			";
			
			$hash=ldap_get_entries($ld,$sr);	
			writelogs("Checking: DN $root {$hash["count"]} group entries",__FUNCTION__,__FILE__,__LINE__);
			
			
			for($i=0;$i<$hash["count"];$i++){
				$groups=$groups."
					<tr>
						<td width=1%><img src='img/wingroup.png'></td>
						<td style='font-size:13px;font-weight:bold'>{$hash[$i]["cn"][0]}</td>
						<td style='font-size:13px;font-weight:bold'>{$hash[$i]["member"]["count"]}&nbsp;{users}</td>
					</tr>
					";
				
			}
			$groups=$groups."</tbody></table>";
			
			
		}else{
			writelogs("Checking: DN $root no entries of failed",__FUNCTION__,__FILE__,__LINE__);
		}
		
		$sr = @ldap_list($ld,$root,'(objectclass=user)',array('dn'));
		if ($sr) {$hash=ldap_get_entries($ld,$sr);}
		$countdeuser=$hash["count"];
		
	
	
	$rootBranchTitle=str_replace(',', ', ', $root);
	
	$html="<div style='font-size:16px' id='root-infos-title'>{ou}:&nbsp;&laquo;&nbsp;$titleOU&nbsp;&raquo;&nbsp;<span style='font-size:11px'>($countdeuser&nbsp;{members})</div>
	<div style='font-size:11px'>$rootBranchTitle</div>
	
	<div id='BrowserDiskDiv'>
	<table style='width:99%' class=form>$select
	
	
	</table>	
	$groups
	</div>
	
	<script>
	
		var x_AddSubFolder=function (obj) {
		 	text=obj.responseText;
		 	if(text.length>2){alert(text);}else{
		 		document.getElementById('browser-infos').innerHTML='';
		 	}
			ReloadTree();
			}

			
		function SelectFolder(){
			if(!document.getElementById('{$_GET["field"]}')){
				alert('{$_GET["field"]} No such field');
				return;
			}
			var stripped={$_GET["replace-start-root"]};
			if(stripped==0){document.getElementById('{$_GET["field"]}').value='$root';}else{document.getElementById('{$_GET["field"]}').value='$strippedroot';}
			YahooWinBrowseHide();
		}
	
	
	
		
		
	</script>	
	
	";
	echo $tpl->_ENGINE_parse_body($html);
}








