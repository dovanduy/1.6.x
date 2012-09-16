<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.samba.inc');


	
	
	$user=new usersMenus();
	if($user->AsSambaAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["SambaAclBrowseFilter"])){SambaAclBrowseFilter();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["query"])){query();exit;}
	
	
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	if(!isset($_GET["OnlyUsers"])){$_GET["OnlyUsers"]=0;}
	if(!isset($_GET["OnlyGroups"])){$_GET["OnlyGroups"]=0;}
	if(!isset($_GET["OnlyGUID"])){$_GET["OnlyGUID"]=0;}
	if(!isset($_GET["NOComputers"])){$_GET["NOComputers"]=0;}
	if(!isset($_GET["Zarafa"])){$_GET["Zarafa"]=0;}
	if(!isset($_GET["OnlyAD"])){$_GET["OnlyAD"]=0;}
	if(isset($_GET["security"])){$_GET["security"]=null;}
	
	
	
	
	$title=$tpl->_ENGINE_parse_body("{browse}::{members}::");
	echo "YahooUser('534','$page?popup=yes&field-user={$_GET["field-user"]}&NOComputers={$_GET["NOComputers"]}&prepend={$_GET["prepend"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&OnlyGUID={$_GET["OnlyGUID"]}&callback={$_GET["callback"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD={$_GET["OnlyAD"]}&security={$_GET["security"]}','$title');";	
	
	
	
}
function popup(){
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_comps"])){$_SESSION["SambaAclBrowseFilter"]["acls_comps"]=0;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_gps"])){$_SESSION["SambaAclBrowseFilter"]["acls_gps"]=1;}
	if(isset($_SESSION["SambaAclBrowseFilter"]["acls_users"])){$_SESSION["SambaAclBrowseFilter"]["acls_users"]=1;}	
	$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=$_GET["acls_onlyad"];
	if(!is_numeric($_SESSION["SambaAclBrowseFilter"]["acls_onlyad"])){$_SESSION["SambaAclBrowseFilter"]["acls_onlyad"]=0;}	
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);YahooUserHide();return;";}	
	
	$sock=new sockets();
	$EnableKerbAuth=$sock->GET_INFO("EnableKerbAuth");
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	$page=CurrentPageName();
	$tpl=new templates();	
	$dansguardian2_members_groups_explain=$tpl->_ENGINE_parse_body("{dansguardian2_members_groups_explain}");
	$t=time();
	$group=$tpl->_ENGINE_parse_body("{group}");
	$type=$tpl->_ENGINE_parse_body("{type}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$delete=$tpl->_ENGINE_parse_body("{delete}");
	$do_you_want_to_delete_this_group=$tpl->javascript_parse_text("{do_you_want_to_delete_this_group}");
	$new_group=$tpl->_ENGINE_parse_body("{new_group}");
	$title=null;
	$filter=$tpl->_ENGINE_parse_body("{filter}");
	
	
	$SUFFIX[]="&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}";
	$SUFFIX[]="&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}";
	$SUFFIX[]="&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}";
	$SUFFIX[]="&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD&t=$t&security={$_GET["security"]}";
	$SUFFIX_FORMATTED=@implode("", $SUFFIX);
	$buttons="
	buttons : [
	{name: '$filter', bclass: 'Search', onpress : SambaAclBrowseFilter$t},$BrowsAD
	],";		
	
$html="
<div style='margin-left:-10px'>
<table class='flexRT$t' style='display: none' id='flexRT$t' style='width:100%'></table>
</div>
<script>
var rowid=0;
$(document).ready(function(){
$('#flexRT$t').flexigrid({
	url: '$page?query=yes$SUFFIX_FORMATTED',
	dataType: 'json',
	colModel : [
		{display: '&nbsp;', name : 'groupname', width : 31, sortable : true, align: 'center'},	
		{display: '$members', name : 'members', width :405, sortable : false, align: 'left'},
		{display: '&nbsp;', name : 'members', width :31, sortable : false, align: 'left'},
		
		],
	$buttons
	searchitems : [
		{display: '$members', name : 'members'},
		],
	sortname: 'groupname',
	sortorder: 'asc',
	usepager: true,
	title: '$title',
	useRp: true,
	rp: 50,
	showTableToggleBtn: false,
	width: 524,
	height: 350,
	singleSelect: true,
	rpOptions: [10, 20, 30, 50,100,200]
	
	});   
});

function SambaAclBrowseFilter$t(){
	RTMMail('505','$page?SambaAclBrowseFilter=yes$SUFFIX_FORMATTED','$filter');
}

	function SambaBrowseSelect(id,prependText,guid){
			$callback
			var prepend={$_GET["prepend"]};
			var prepend_gid={$_GET["prepend-guid"]};
			var OnlyGUID=$OnlyGUID;
			if(document.getElementById('{$_GET["field-user"]}')){
				var selected=id;
				if(OnlyGUID==1){
					document.getElementById('{$_GET["field-user"]}').value=guid;
					YahooUserHide();
					return;
				}
				
				if(prepend==1){selected=prependText+id;}
				if(prepend_gid==1){
					if(guid>1){
						selected=prependText+id+':'+guid;
					}
				}
				document.getElementById('{$_GET["field-user"]}').value=selected;
				YahooUserHide();
			}
		}

</script>";	
	
	echo $html;
	
	
}
function SambaAclBrowseFilter(){
	$t=$_GET["t"];
	$tt=time();
	$tpl=new templates();
	$page=CurrentPageName();
	$sock=new sockets();
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	
	$OnlyAD=$_GET["OnlyAD"];
	$OnlyGUID=$_GET["OnlyAD"];
	$OnlyUsers=$_GET["OnlyUsers"];
	$NOComputers=$_GET["NOComputers"];
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	if(!is_numeric($NOComputers)){$NOComputers=1;}
	if($NOComputers==0){$NOComputers=1;}
	
	
	unset($_GET["SambaAclBrowseFilter"]);
	while (list ($key, $value) = each ($_GET) ){
		$keyBin=$key;
		$keyBin=str_replace("-", "_", $keyBin);
		$jss[]="var $keyBin='$value';";
		$jssUri[]="'&$key='+$keyBin+";
	}
	
	
	$jssUriT=@implode("", $jssUri);
	if(substr($jssUriT,strlen($jssUriT)-1,1)=='+'){$jssUriT=substr($jssUriT, 0,strlen($jssUriT)-1);}
	$jssT=@implode("\n\t", $jss);
	if($EnableSambaActiveDirectory==1){
		$config_activedirectory=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
		$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"]);
		$onlyAd="
		<tr>
		<td width=1%><img src='img/wink3-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{only_from_activedirectory} ($WORKGROUP):</td>
		<td>". Field_checkbox("OnlyAD-$tt", 1,$OnlyAD,"CheckAclsFilter()")."</td>
		</tr>";
	}
	
	$html="
	<table style='width:99%' class=form>
	<tr>
		<td width=1%><img src='img/computer-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{computers}:</td>
		<td width=1%>". Field_checkbox("NOComputers-$tt", 1,$NOComputers,"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td width=1%><img src='img/member-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{groupsF}:</td>
		<td width=1%>". Field_checkbox("OnlyGUID-$tt", 1,$OnlyGUID,"CheckAclsFilter()")."</td>
	</tr>
	<tr>
		<td width=1%><img src='img/user-32.png'></td>
		<td valign='top' class=legend style='font-size:16px'>{members}:</td>
		<td width=1%>". Field_checkbox("OnlyUsers-$tt", 1,$OnlyUsers,"CheckAclsFilter()")."</td>
	</tr>
	$onlyAd		
	</table>
	<script>
		function CheckAclsFilter(){
			$jssT
			if(document.getElementById('NOComputers-$tt').checked){NOComputers=0;}else{NOComputers=1;}
			if(document.getElementById('OnlyGUID-$tt').checked){OnlyGUID=1;}else{OnlyGUID=0;}
			if(document.getElementById('OnlyUsers-$tt').checked){OnlyUsers=1;}else{OnlyUsers=0;}
			if(document.getElementById('OnlyAD-$tt')){
				if(document.getElementById('OnlyAD-$tt').checked){OnlyAD=1;}else{OnlyAD=0;}
			}
			$('#flexRT$t').flexOptions({url: '$page?query=yes'+$jssUriT}).flexReload(); 
		}
	</script>
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}


function popup_old(){
	$page=CurrentPageName();
	$tpl=new templates();		
	if($_GET["prepend"]==null){$_GET["prepend"]=0;}
	if($_GET["prepend-guid"]==null){$_GET["prepend-guid"]=0;}
	$OnlyGUID=$_GET["OnlyGUID"];
	$OnlyAD=$_GET["OnlyAD"];
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyAD)){$OnlyAD=0;}
	if($_GET["callback"]<>null){$callback="{$_GET["callback"]}(id,prependText,guid);YahooUserHide();return;";}
	
	$html="
	<center>
	<table class=form>
		<tr>
		<td>" . Field_text('BrowseUserQuery',null,'width:100%;font-size:14px;padding:3px',null,null,null,null,"BrowseFindUserGroupClick(event);")."</td>
		<td align='right'><input type='button' OnClick=\"javascript:BrowseFindUserGroup();\" value='{search}&nbsp;&raquo;'></td>
		</tR>
	</table>
	</center>
	<br>
	<div style='padding:5px;height:350px;overflow:auto' id='finduserandgroupsidBrwse'></div>
	<script>
	function BrowseFindUserGroupClick(e){
		if(checkEnter(e)){BrowseFindUserGroup();}
	}
	
	var x_BrowseFindUserGroup=function (obj) {
		tempvalue=obj.responseText;
		document.getElementById('finduserandgroupsidBrwse').innerHTML=tempvalue;
	}


	function BrowseFindUserGroup(){
		LoadAjax('finduserandgroupsidBrwse','$page?query='+escape(document.getElementById('BrowseUserQuery').value)+'&prepend={$_GET["prepend"]}&field-user={$_GET["field-user"]}&prepend-guid={$_GET["prepend-guid"]}&OnlyUsers={$_GET["OnlyUsers"]}&OnlyGUID={$_GET["OnlyGUID"]}&organization={$_GET["organization"]}&OnlyGroups={$_GET["OnlyGroups"]}&callback={$_GET["callback"]}&NOComputers={$_GET["NOComputers"]}&Zarafa={$_GET["Zarafa"]}&OnlyAD=$OnlyAD');
	
	}	
	
	
	function SambaBrowseSelect(id,prependText,guid){
			$callback
			var prepend={$_GET["prepend"]};
			var prepend_gid={$_GET["prepend-guid"]};
			var OnlyGUID=$OnlyGUID;
			if(document.getElementById('{$_GET["field-user"]}')){
				var selected=id;
				if(OnlyGUID==1){
					document.getElementById('{$_GET["field-user"]}').value=guid;
					YahooUserHide();
					return;
				}
				
				if(prepend==1){selected=prependText+id;}
				if(prepend_gid==1){
					if(guid>1){
						selected=prependText+id+':'+guid;
					}
				}
				document.getElementById('{$_GET["field-user"]}').value=selected;
				YahooUserHide();
			}
		}

		BrowseFindUserGroup();
	
</script>	
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function query_ad_groups_old(){
	
	$q=new mysql();
	$_GET["query"]="*{$_GET["query"]}*";
	$_GET["query"]=str_replace("**", "*", $_GET["query"]);
	$_GET["query"]=str_replace("**", "*", $_GET["query"]);
	$_GET["query"]=str_replace("*", "%", $_GET["query"]);
	$nogetent=false;	
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$sql="SELECT * FROM activedirectory_groupsNames WHERE groupname LIKE '{$_GET["query"]}' ORDER BY groupname LIMIT 0,50";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	
	$html=$html."
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{active_directory_group}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$prepend="ad:";
		$gid=$ligne["dn"];
		$num=$ligne["groupname"];
		$num=str_replace("'", "`", $num);
		$Displayname=$num;
		$gid=base64_encode($gid);
		$img="wingroup.png";
		$js="SambaBrowseSelect('$num','$prepend','$gid')";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$num','$prepend','$gid')";}
		
	$html=$html."
		<tr class=$classtr>
		<td width=1% align='center' valign='middle'><img src='img/$img'></td>
		<td 
		onMouseOver=\"this.style.cursor='pointer'\" 
		OnMouseOut=\"this.style.cursor='default'\"
		OnClick=\"javascript:$js;\"
		><strong style='font-size:14px;text-decoration:underline' >$Displayname</td>
		</tr>
	";
	}
	
	$html=$html."
	</tbody>
	</table>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");	
	
	
	
}
function query(){
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	$users=new user();
	$query=$_POST["query"];
	
	$nogetent=false;	
		
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$ObjectZarafa=false;
	$Zarafa=$_GET["Zarafa"];
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	$hash=array();
	if(!isset($_GET["prepend"])){$_GET["prepend"]=0;}else{if($_GET["prepend"]=='yes'){$_GET["prepend"]=1;}if($_GET["prepend"]=='no'){$_GET["prepend"]=0;}}
	$WORKGROUP=null;
	if(!is_numeric($OnlyGUID)){$OnlyGUID=0;}
	if(!is_numeric($OnlyUsers)){$OnlyUsers=0;}
	
	
	
	
	
	if($_GET["OnlyAD"]==1){
		$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
		if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
		if($EnableSambaActiveDirectory==1){
			$config_activedirectory=unserialize(base64_decode($sock->GET_INFO("SambaAdInfos")));
			$WORKGROUP=strtoupper($config_activedirectory["WORKGROUP"])."/";
		}
	}
	
	if($query=='*'){
		if($WORKGROUP<>null){
				$query="$WORKGROUP/*";
			}else{
				$query=null;}
	}else{
		if($WORKGROUP<>null){$query="$WORKGROUP/$query";}
		
	}
	
	$hash=$users->find_ldap_items($query,$_GET["organization"],$nogetent,$ObjectZarafa,$_POST["rp"],$OnlyGUID,$OnlyUsers);

	
	$data = array();
	$data['page'] = 1;
	$data['total'] = count($hash);
	$data['rows'] = array();	
	$c=0;
	
	while (list ($num, $ligne) = each ($hash) ){
		if($num==null){continue;}
		$gid=0;
		
		
		if(preg_match("#^@(.+?):([0-9]+)#",$ligne,$re)){
			if($OnlyUsers==1){continue;}
			$img="wingroup.png";
			$Displayname="{$re[1]}";
			$prepend="group:";
			$gid=$re[2];
		}else{
			if($OnlyGroups==1){continue;}
			$Displayname=$ligne;
			$img="winuser.png";
			$prepend="user:";
		}
		
		if(substr($num,strlen($num)-1,1)=='$'){
			if($_GET["NOComputers"]==1){continue;}
			$Displayname=str_replace('$','',$Displayname);
			$img="base.gif";
			$prepend="computer:";
			
		}
		
		$js="SambaBrowseSelect('$num','$prepend',$gid)";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$num','$prepend',$gid)";}

		$c++;
		if($c>$_POST["rp"]){break;}
		
		$data['rows'][] = array(
		'id' => md5(serialize($ligne["displayname"])),
		'cell' => array(
			"<img src='img/$img'>",
			"<span style='font-size:14px;font-weight:bolder'>$Displayname</span>",
			"<span style='font-size:14px'>".imgsimple("arrow-right-24.png","{add}",$js)."</span>",
			)
		);		
		
		
	
	}
	$data['total'] = $c;
	echo json_encode($data);	

	
}


function query_old(){
	if($_GET["OnlyUsers"]=="yes"){$_GET["OnlyUsers"]=1;}
	if(($_GET["OnlyAD"]==1) && ($_GET["OnlyUsers"]==0)){query_ad_groups();exit;}
	$users=new user();
	if($_GET["query"]=='*'){$_GET["query"]=null;}
	$nogetent=false;	
	
	$OnlyUsers=$_GET["OnlyUsers"];
	$OnlyGroups=$_GET["OnlyGroups"];
	$OnlyGUID=$_GET["OnlyGUID"];
	$ObjectZarafa=false;
	$Zarafa=$_GET["Zarafa"];
	if($Zarafa==1){$nogetent=true;$ObjectZarafa=true;}
	$hash=$users->find_ldap_items($_GET["query"],$_GET["organization"],$nogetent,$ObjectZarafa);
	
	

	if(!isset($_GET["prepend"])){$_GET["prepend"]=0;}else{
		if($_GET["prepend"]=='yes'){$_GET["prepend"]=1;}
		if($_GET["prepend"]=='no'){$_GET["prepend"]=0;}
	}
	if(!is_array($hash)){return null;}
	
	$html=$html."
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th colspan=2>{members}/{groups}</th>
	</tr>
</thead>
<tbody class='tbody'>";		
	
	
while (list ($num, $ligne) = each ($hash) ){
		if($num==null){continue;}
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		$gid=0;
		
		
		if(preg_match("#^@(.+?):([0-9]+)#",$ligne,$re)){
			if($OnlyUsers==1){continue;}
			$img="wingroup.png";
			$Displayname="{$re[1]}";
			$prepend="group:";
			$gid=$re[2];
		}else{
			if($OnlyGroups==1){continue;}
			$Displayname=$ligne;
			$img="winuser.png";
			$prepend="user:";
		}
		
		if(substr($num,strlen($num)-1,1)=='$'){
			if($_GET["NOComputers"]==1){continue;}
			$Displayname=str_replace('$','',$Displayname);
			$img="base.gif";
			$prepend="computer:";
			
		}
		
		$js="SambaBrowseSelect('$num','$prepend',$gid)";
		if($_GET["callback"]<>null){$js="{$_GET["callback"]}('$num','$prepend',$gid)";}
		
	$html=$html."
		<tr class=$classtr>
		<td width=1% align='center' valign='middle'><img src='img/$img'></td>
		<td 
		onMouseOver=\"this.style.cursor='pointer'\" 
		OnMouseOut=\"this.style.cursor='default'\"
		OnClick=\"javascript:$js;\"
		><strong style='font-size:14px;text-decoration:underline' >$Displayname</td>
		</tr>
	";
	}
	
	$html=$html."</table>
	
	";
	
	$tpl=new templates();
	echo $tpl->_ENGINE_parse_body("$html");	
	
}

