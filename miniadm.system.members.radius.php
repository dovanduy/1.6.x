<?php
session_start();

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',"<p class='text-error'>");
ini_set('error_append_string',"</p>");
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.freeradius.inc");


$users=new usersMenus();
if(!Privileges_members_admins()){die();}

if(isset($_GET["list-groups"])){groups_section();exit;}
if(isset($_GET["search-groups"])){search_groups();exit;}
if(isset($_GET["delete-group-js"])){groups_delete_js();exit;}
if(isset($_POST["delete-group"])){groups_delete();exit;}
if(isset($_GET["gpid-js"])){groups_show();exit;}
if(isset($_GET["groups-tabs"])){groups_tabs();exit;}
if(isset($_GET["groups-form"])){groups_form();exit;}
if(isset($_POST["groupname"])){groups_save();exit;}
if(isset($_GET["groups-attributes"])){groups_attributes();exit;}
if(isset($_GET["search-attributes"])){groups_attributes_search();exit;}
if(isset($_GET["group-attribute-js"])){groups_attributes_js();exit;}
if(isset($_GET["group-attribute"])){groups_attributes_form();exit;}
if(isset($_GET["delete-attribute-js"])){groups_attributes_delete_js();exit;}
if(isset($_POST["delete-attribute"])){groups_attributes_delete();exit;}

if(isset($_GET["groups-hotspot"])){groups_hostpot();exit;}

if(isset($_POST["attribute"])){groups_attributes_save();exit;}
if(isset($_GET["list-members"])){members_section();exit;}
if(isset($_GET["search-members"])){search_members();exit;}
if(isset($_GET["userid-js"])){member_show();exit;}
if(isset($_GET["member-tabs"])){member_tabs();exit;}
if(isset($_GET["member-form"])){member_form();exit;}
if(isset($_POST["userid"])){member_save();exit;}
if(isset($_GET["delete-user-js"])){member_delete_js();exit;}
if(isset($_POST["delete-user"])){member_delete();exit;}
if(isset($_GET["verif-userid-js"])){member_verify_js();exit;}
if(isset($_POST["verif-userid"])){member_verify();exit;}

if(isset($_GET["assistance"])){assistance();exit;}
if(isset($_GET["rebuild-js"])){rebuild_js();exit;}
if(isset($_POST["rebuild-tables"])){rebuild_tables_perform();exit;}
tabs();

function tabs(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	PatchMysql();
	$array["{members}"]="$page?list-members=yes";
	$array["{groups2}"]="$page?list-groups=yes";
	$array["{assistance}"]="$page?assistance=yes";
	
	
	
	echo $boot->build_tab($array);	
}


function member_show(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{add_member}";
	$userid=$_GET["userid-js"];
	if($userid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE id={$userid}","artica_backup"));
		$title="{member}:{$ligne["username"]}";
	}
	
	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(700,'$page?member-tabs=yes&userid=$userid','$title')";
	
}
function groups_show(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title="{add_group}";
	 
	$gpid=$_GET["gpid-js"];
	if($gpid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
		$ligne["groupname"]=utf8_decode($ligne["groupname"]);
		$title="{group2}:{$ligne["groupname"]}";
	}

	$title=$tpl->javascript_parse_text($title);
	echo "YahooWin(700,'$page?groups-tabs=yes&gpid=$gpid','$title')";

}

function groups_attributes_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$title="{add_attributes}";
	$id=$_GET["group-attribute-js"];
	$gpid=$_GET["gpid"];
	$table=$_GET["table"];
	if($id>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `attribute`,`gpid` FROM `$table` WHERE id='$id'","artica_backup"));
		$gpid=$ligne["gpid"];
		$attribute="{$ligne["attribute"]}:";
	}
	
	
	$sql="SELECT groupname FROM radgroupcheck WHERE id={$gpid}";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	if(!$q->ok){
		echo "alert('" .$tpl->javascript_parse_text($q->mysql_error ." ".$sql)."');";
		return;
	}
	
	$groupname=utf8_decode($ligne["groupname"]);
	$title=$tpl->javascript_parse_text("{attribute}:$attribute$id:$groupname");
	echo "YahooWin2(700,'$page?group-attribute=$id&gpid=$gpid&table=$table','$title')";
	
}

function groups_attributes_form(){
	$id=$_GET["group-attribute"];
	$gpid=$_GET["gpid"];
	$table=$_GET["table"];
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$boot=new boostrap_form();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `attribute`,`op`,`value`  FROM `$table` WHERE id='$id'","artica_backup"));
	$bttitle="{add}";
	
	if($id>0){$bttitle="{apply}";}
	$GPS[null]="{select}";
	
	if($q->COUNT_ROWS("radattribute","artica_backup")==0){
		echo "<p class=text-error>radattribute: empty table..</p>";
	}
	
	$sql="SELECT attribute FROM radattribute WHERE `gp_database` LIKE '%$table%' ORDER BY attribute ";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error</p>";}
	if(mysql_num_rows($results)==0){echo "<p class=text-error>$table no attributes...</p>";}
	while ($pg = mysql_fetch_assoc($results)) {
		$GPS[$pg["attribute"]]=$pg["attribute"];
	}	
	
	$f["="]=true;
	$f[":="]=true;
	$f["=="]=true;
	$f["+="]=true;
	$f["!="]=true;
	$f[">"]=true;
	$f[">="]=true;
	$f["<"]=true;
	$f["<="]=true;
	$f["=~"]=true;
	$f["!~"]=true;
	$f["=*"]=true;
	$f["!*"]=true;	
	
	while (list ($key, $value) = each ($f) ){
		$OP[$key]=$key;
	}
	$boot->set_formdescription("{{$table}_explain}");
	$boot->set_hidden("id", $id);
	$boot->set_hidden("gpid", $gpid);
	$boot->set_hidden("table", $table);
	$boot->set_list("attribute", "{attribute}", $GPS,$ligne["attribute"]);
	$boot->set_list("op", "{operator}", $OP,$ligne["op"]);
	$boot->set_field("value", "{value}", $ligne["value"],array("ENCODE"=>true,"MANDATORY"=>true));
	$boot->set_button($bttitle);
	if($id==0){$boot->set_CallBack("YahooWin2Hide()");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
}

function groups_attributes_save(){
	$id=$_POST["id"];
	$gpid=$_POST["gpid"];
	$table=$_POST["table"];
	$value=url_decode_special_tool($_POST["value"]);
	$op=$_POST["op"];
	$attribute=$_POST["attribute"];
	$q=new mysql();
	$value=mysql_escape_string($value);
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
	$groupname=$ligne["groupname"];
	$sql="INSERT IGNORE INTO $table (`gpid`, `groupname`,`attribute`, `value`,`op`) 
	VALUES ('$gpid', '$groupname', '$attribute','$value','$op');";
	if($id>0){
		$sql="UPDATE $table SET `attribute`='$attribute',
		`value`='$value',
		`op`='$op'
		WHERE id=$id";
	}
	
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;return;}
	
}

function rebuild_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->javascript_parse_text("{rebuild_tables} ?");
	$t=time();
	
	$html="
	
	
var xdeletegp$t= function (obj) {
	var results=obj.responseText;
	if(results.length>32){alert(results);return;}
	
	}
	
	
function deletegp$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('rebuild-tables','yes');
	XHR.sendAndLoad('$page', 'POST',xdeletegp$t);
	}
		
	deletegp$t()
	";
	echo $html;	
	
}
function groups_attributes_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$id=$_GET["delete-attribute-js"];
	$table=$_GET["table"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT attribute FROM $table WHERE id={$id}","artica_backup"));
	$title=$tpl->javascript_parse_text("{delete}:{attribute}: {$ligne["attribute"]} ?");
	$t=time();

$html="
var xdeletegp$t= function (obj) {
	var results=obj.responseText;
	if(results.length>32){alert(results);return;}
	$('#Attrs$id').remove();
}


function deletegp$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-attribute',$id);
	XHR.appendData('table','$table');
	XHR.sendAndLoad('$page', 'POST',xdeletegp$t);
}
	
deletegp$t()
";
	echo $html;

}
function groups_attributes_delete(){
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM {$_POST["table"]} WHERE id={$_POST["delete-attribute"]}","artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
}


function groups_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();	
	$gpid=$_GET["delete-group-js"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
	$title=$tpl->javascript_parse_text("{DeleteThisGroup}:{$ligne["groupname"]} ?");
	$t=time();
	
	$html="
		
		
	var xdeletegp$t= function (obj) {
		var results=obj.responseText;
		if(results.length>32){alert(results);return;}
		$('#radgroupcheck$gpid').remove();
	}		
		
		
	function deletegp$t(){
		if(!confirm('$title')){return;}
		var XHR = new XHRConnection();
		XHR.appendData('delete-group',$gpid);
		XHR.sendAndLoad('$page', 'POST',xdeletegp$t);	
	}
			
	deletegp$t()
";
	echo $html;
	
}

function member_verify_js(){
	$tpl=new templates();
	$sock=new sockets();
	$page=CurrentPageName();
	$EnableFreeRadius=$sock->GET_INFO("EnableFreeRadius");
	if(!is_numeric($EnableFreeRadius)){$EnableFreeRadius=0;}
	if($EnableFreeRadius==0){
		echo "alert('".$tpl->javascript_parse_text("{freeradius_not_enabled}")."');";
		return;
	}
	
	$t=time();
	$gpid=$_GET["verif-userid-js"];
	$html="
	
	
	var xdeletegp$t= function (obj) {
		var results=obj.responseText;
		if(results.length>10){alert(results);return;}
	
	}
	
	
	function deletegp$t(){
		var XHR = new XHRConnection();
		XHR.appendData('verif-userid',$gpid);
		XHR.sendAndLoad('$page', 'POST',xdeletegp$t);
	}
	
	deletegp$t()
	";
	echo $html;	
	
}

function PatchMysql(){
	$q=new mysql();
	if(!$q->FIELD_EXISTS("radacct","username","artica_backup")){
		$sql="ALTER TABLE `radacct` ADD `username` varchar(64) NOT NULL default '' ,ADD INDEX ( `username` )";
		$q->QUERY_SQL($sql,"artica_backup");
	}

	if(!$q->FIELD_EXISTS("radcheck","username","artica_backup")){
		$sql="ALTER TABLE `radcheck` ADD `username` varchar(64) NOT NULL default '' ,ADD INDEX ( `username` )";
		$q->QUERY_SQL($sql,"artica_backup");
	}	
	
	
}

function member_verify(){
	$page=CurrentPageName();
	$tpl=new templates();
	$gpid=$_POST["verif-userid"];
	$q=new mysql();
	$sock=new sockets();
	//	$sql="INSERT IGNORE INTO radcheck (UserName, Attribute, Value) VALUES ('$UserName', 'Password', '$Password');";
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `username`,`value` FROM radcheck WHERE id={$gpid} 
	AND `attribute`='Cleartext-Password'","artica_backup"));	
	
	if($ligne["value"]==null){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `username`,`value` FROM radcheck WHERE id={$gpid}
		AND `attribute`='Password'","artica_backup"));		
	}
	
	
	$TESTAUTHPASS=urlencode(base64_encode(url_decode_special_tool($ligne["value"])));
	if($TESTAUTHPASS==null){
		echo $tpl->javascript_parse_text("{no_password_set_for_this_account}");
		return;
	}
	$TESTAUTHUSER=urlencode(base64_encode($ligne["username"]));
	echo(base64_decode($sock->getFrameWork("freeradius.php?test-auth=yes&username=$TESTAUTHUSER&password=$TESTAUTHPASS")));
		
}

function member_delete_js(){
	header("content-type: application/x-javascript");
	$page=CurrentPageName();
	$tpl=new templates();
	$gpid=$_GET["delete-user-js"];
	$q=new mysql();
	$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE id={$gpid}","artica_backup"));
	$title=$tpl->javascript_parse_text("{delete}:{$ligne["UserName"]} ?");
	$t=time();

	$html="


var xdeletegp$t= function (obj) {
	var results=obj.responseText;
	if(results.length>32){alert(results);return;}
	$('#radcheck$gpid').remove();
}


function deletegp$t(){
	if(!confirm('$title')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-user',$gpid);
	XHR.sendAndLoad('$page', 'POST',xdeletegp$t);
}
	
deletegp$t()
";
echo $html;

}

function member_delete(){
	$userid=$_POST["delete-user"];
	$free=new freeradius();
	$free->MemberDelete($userid);
}


function groups_delete(){
	$free=new freeradius();
	$gpid=$_POST["delete-group"];
	$free->GroupDelete($gpid);

	
}


function member_tabs(){
	$userid=$_GET["userid"];
	$title="{add_member}";
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	if($userid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT username FROM radcheck WHERE id={$userid}","artica_backup"));
		$title="{member}:{$ligne["username"]}";
	}	
	$array[$title]="$page?member-form=yes&userid=$userid";
	echo $boot->build_tab($array);	
}
function groups_tabs(){
	$gpid=$_GET["gpid"];
	$title="{add_group}";
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	if($gpid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
		$title="{group2}:{$ligne["groupname"]}";
		$array[$title]="$page?groups-form=yes&gpid=$gpid";
		$array["{hotspot}"]="$page?groups-hotspot=yes&gpid=$gpid";
		
		$array["{attributes}:{reply}"]="$page?groups-attributes=yes&gpid=$gpid&table=radgroupreply";
	}else{
		$array[$title]="$page?groups-form=yes&gpid=$gpid";
	}
	echo $boot->build_tab($array);	
	
}


function member_form(){
	$userid=$_GET["userid"];
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$bttitle="{add}";
	$q=new mysql();
	if($userid>0){
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT `username`,`value` FROM radcheck WHERE id={$userid}","artica_backup"));
		$ligne2=mysql_fetch_array($q->QUERY_SQL("SELECT gpid FROM radusergroup WHERE username='{$ligne["username"]}'","artica_backup"));
		$gpid=$ligne2["gpid"];
		if(!is_numeric($gpid)){$gpid=0;}
		
		
		$bttitle="{apply}";
	}
	
	$GROUPS[0]="{select}";
	$sql="SELECT id,groupname FROM radgroupcheck ORDER BY groupname";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while ($pg = mysql_fetch_assoc($results)) {
		$GROUPS[$pg["id"]]=$pg["groupname"];
	}
	
	
	$boot->set_hidden("userid", $userid);
	$boot->set_field("username", "{uid}", $ligne["username"],array("ENCODE"=>true,"MANDATORY"=>true));
	$boot->set_fieldpassword("Password", "{password}", $ligne["value"],array("ENCODE"=>true,"MANDATORY"=>true));
	$boot->set_list("GroupID", "{group2}", $GROUPS,$gpid);
	$boot->set_button($bttitle);
	if($userid==0){$boot->set_CallBack("YahooWinHide()");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
}

function member_save(){
	$id=$_POST["userid"];
	$gpid=$_POST["GroupID"];
	$UserName=url_decode_special_tool($_POST["username"]);
	$Password=url_decode_special_tool($_POST["Password"]);
	$free=new freeradius();
	$free->MemberSave($UserName,$Password,$id,$gpid);
}


function groups_form(){
	$gpid=$_GET["gpid"];
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	$bttitle="{add}";
	if($gpid>0){
		$q=new mysql();
		$ligne=mysql_fetch_array($q->QUERY_SQL("SELECT groupname FROM radgroupcheck WHERE id={$gpid}","artica_backup"));
		if(!$q->ok){
			echo "<p class=text-error>$q->mysql_error</p>";
		}
		$bttitle="{apply}";
	}
	
	$boot->set_hidden("gpid", $gpid);
	$boot->set_field("groupname", "{groupname}", $ligne["groupname"],array("ENCODE"=>true,"MANDATORY"=>true));
	
	$boot->set_button($bttitle);
	if($gpid==0){$boot->set_CallBack("YahooWinHide()");}
	$boot->set_RefreshSearchs();
	echo $boot->Compile();	
	
}
function groups_save(){
	$id=$_POST["gpid"];
	$q=new mysql();
	$groupname=url_decode_special_tool($_POST["groupname"]);
	$free=new freeradius();
	$free->GroupSave($groupname,$id);

}





function members_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	$buttonAdd=button("{add_member}", "Loadjs('$page?userid-js=0&t=$t');",16);
	//$OPTIONS["EXPLAIN"]="<strong>{$title}</strong><br>";
	$OPTIONS["BUTTONS"][]=$buttonAdd;
	$html=$boot->SearchFormGen("username","search-members","&t=$t",$OPTIONS);
	echo $tpl->_ENGINE_parse_body($html);
	
}

function groups_section(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	$buttonAdd=button("{add_group}", "Loadjs('$page?gpid-js=0&t=$t');",16);
	//$OPTIONS["EXPLAIN"]="<strong>{$title}</strong><br>";
	$OPTIONS["BUTTONS"][]=$buttonAdd;
	$html=$boot->SearchFormGen("groupname","search-groups","&t=$t",$OPTIONS);
	echo $tpl->_ENGINE_parse_body($html);	
	
}
function groups_attributes(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$t=time();
	$table=$_GET["table"];
	$gpid=$_GET["gpid"];
	$buttonAdd=button("{add_attribute}", "Loadjs('$page?group-attribute-js=0&gpid=$gpid&table=$table');",16);
	$OPTIONS["EXPLAIN"]="{{$table}_explain}<br>";
	$OPTIONS["BUTTONS"][]=$buttonAdd;
	$html=$boot->SearchFormGen("attribute","search-attributes","&gpid=$gpid&table=$table",$OPTIONS);
	echo $tpl->_ENGINE_parse_body($html);	
	
}

function search_groups(){
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
	$searchstring=string_to_flexquery("search-groups");
	
	/*if(!$_SESSION["CORP"]){
	 $tpl=new templates();
	$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
	$content="<p class=text-error>$onlycorpavailable</p>";
	echo $content;
	return;
	}*/
	
	
	
	$sql="SELECT `groupname`,`id` FROM radgroupcheck WHERE 1 $searchstring ORDER BY groupname LIMIT 0,500";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$boot=new boostrap_form();
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=$ligne["id"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('$page?delete-group-js=$id')");
		
		$link=$boot->trswitch("Loadjs('$page?gpid-js=$id');");
		$tr[]="
		<tr id='radgroupcheck$id'>
		<td $link><i class='icon-user'></i><i class='icon-user'></i>&nbsp;<span style='font-size:16px'>{$ligne["groupname"]}</span></td>
		<td width=1%>$delete</td>
		</tr>";
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>{groups2}</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
";	
	
	
}

function groups_attributes_search(){
	$q=new mysql();
	$tpl=new templates();
	$searchstring=string_to_flexquery("search-attributes");
	$page=CurrentPageName();
	$table=$_GET["table"];
	/*if(!$_SESSION["CORP"]){
	 $tpl=new templates();
	$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
	$content="<p class=text-error>$onlycorpavailable</p>";
	echo $content;
	return;
	}*/
	
	$sql="SELECT `attribute`,`op`,`value`,`id`  FROM `$table` WHERE 1 $searchstring 
	AND `gpid`='{$_GET["gpid"]}' ORDER BY `attribute` LIMIT 0,500";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	
	$boot=new boostrap_form();
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$gpid=$ligne["gpid"];
		$id=$ligne["id"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('$page?delete-attribute-js=$id&table=$table&gpid=$gpid')");
		$link=$boot->trswitch("Loadjs('$page?group-attribute-js=$id&table=$table')");
		
		$tr[]="
		<tr id='Attrs$id'>
		<td $link><i class='icon-flag'></i> {$ligne["attribute"]}</a></td>
		<td $link nowrap><i class='icon-filter'></i>&nbsp;{$ligne["op"]}</a></td>
		<td $link><i class='icon-file'></i> {$ligne["value"]}</a></td>
		<td width=1%>$delete</td>
		</tr>";
	
	
	}
	
	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>{attribute}</th>
					<th width=1%>&nbsp;</th>
					<th>{value}</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
";	
	
	
}

function search_members(){
	$q=new mysql();
	$tpl=new templates();
	$verify=$tpl->_ENGINE_parse_body("{verify}");
	$users=new usersMenus();
	$searchstring=string_to_flexquery("search-members");
	$page=CurrentPageName();
	/*if(!$_SESSION["CORP"]){
		$tpl=new templates();
		$onlycorpavailable=$tpl->_ENGINE_parse_body("{onlycorpavailable}");
		$content="<p class=text-error>$onlycorpavailable</p>";
		echo $content;
		return;
	}*/

	$sql="SELECT `username`,`id`  FROM radcheck WHERE 1 $searchstring ORDER BY `username` LIMIT 0,500";
	$results = $q->QUERY_SQL($sql,"artica_backup");

	$boot=new boostrap_form();
	if(!$q->ok){echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";}

	while ($ligne = mysql_fetch_assoc($results)) {
		
		$id=$ligne["id"];
		$delete=imgsimple("delete-32.png",null,"Loadjs('$page?delete-user-js=$id')");
		$link=$boot->trswitch("Loadjs('$page?userid-js=$id')");
		$linkVerif=$boot->trswitch("Loadjs('$page?verif-userid-js=$id')");
		$verifytd="<i class='icon-check'></i>&nbsp;$verify</a>";
		if(!$users->FREERADIUS_INSTALLED){
			$verifytd=null;
			$linkVerif=null;
		}
		$tr[]="
		<tr id='radcheck$id'>
		<td $link><i class='icon-user'></i> {$ligne["username"]}</a></td>
		<td $linkVerif nowrap></td>
		
		<td width=1%>$delete</td>
		</tr>";


	}

	echo $tpl->_ENGINE_parse_body("
			<table class='table table-bordered table-hover'>
			<thead>
				<tr>
					<th>{member}</th>
					<th width=1%>&nbsp;</th>
					<th>&nbsp;</th>
				</tr>
			</thead>
			 <tbody>
			").@implode("\n", $tr)." </tbody>
		</table>
";
}
function assistance(){
	$q=new mysql();
	$tpl=new templates();
	$page=CurrentPageName();
		
	$tr[]=Paragraphe("table-delete-64.png", "{rebuild_tables}", "{rebuild_tables_explain}",
			"javascript:Loadjs('$page?rebuild-js=yes')"
			);
	
	echo $tpl->_ENGINE_parse_body(CompileTr4($tr,true));
	
}
function rebuild_tables_perform(){
	$q=new mysql();
	$tables[]="radacct";
	$tables[]="radcheck";
	$tables[]="radgroupcheck"; 
	$tables[]="radgroupreply"; 
	
	$tables[]="radreply"; 
	$tables[]="radusergroup"; 
	$tables[]="radpostauth"; 
	$tables[]="nas";
	
	
	while (list ($key, $value) = each ($tables) ){
		$q->QUERY_SQL("DROP TABLE `$value`","artica_backup");
		if(!$q->ok){echo $q->mysql_error;return;}
		echo "$value: DONE\n";
		
	}
	$GLOBALS["VERBOSE"]=true;
	$q->BuildTables();
	
}


function groups_hostpot(){
	$freeradius=new freeradius();	
	$gpid=$_GET["gpid"];
	$boot=new boostrap_form();
	$page=CurrentPageName();
	$tpl=new templates();
	
	$MaxOctets=$freeradius->GroupCheckValue($gpid, "Max-Octets");
	if($MaxOctets>0){$MaxOctets=round($MaxOctets/1000000);}
	
	$bttitle="{add}";
	$boot->set_hidden("gpid", $gpid);
	$boot->set_hidden("hotspot", $gpid);
	$boot->set_field("Idle-Timeout", "{session_timeout} ({seconds})", $freeradius->GroupCheckValue($gpid, "Idle-Timeout"));
	$boot->set_field("Acct-Session-Time", "{session_time} ({seconds})", $freeradius->GroupCheckValue($gpid, "Acct-Session-Time"));
	$boot->set_field("ChilliSpot-Bandwidth-Max-Up", "{max_upload} (kbits/{second})", $freeradius->GroupCheckValue($gpid, "ChilliSpot-Bandwidth-Max-Up"));
	$boot->set_field("ChilliSpot-Bandwidth-Max-Down", "{max_download} (kbits/{second})", $freeradius->GroupCheckValue($gpid, "ChilliSpot-Bandwidth-Max-Down"));
	$boot->set_field("Max-Octets", "{max_size} (MB)", $MaxOctets);
	$boot->set_RefreshSearchs();
	echo $boot->Compile();
	
	
}



