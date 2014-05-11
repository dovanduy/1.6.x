<?php
session_start();

ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");

$users=new usersMenus();
if(!$users->AsHotSpotManager){die();}

if(isset($_GET["section-sessions"])){section_sessions();exit;}
if(isset($_GET["search-sessions"])){sessions_search();exit;}

if(isset($_GET["section-members"])){section_hotspot();exit;}
if(isset($_GET["search-members"])){members_search();exit;}
tabs();


function tabs(){
	$users=new usersMenus();
	$sock=new sockets();
	$page=CurrentPageName();
	$SQUIDEnable=trim($sock->GET_INFO("SQUIDEnable"));
	if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
	$array["{members}"]="$page?section-members=yes";
	$array["{sessions}"]="$page?section-sessions=yes";
	
	$mini=new boostrap_form();
	echo $mini->build_tab($array);
}


function section_hotspot(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	$ID=$_GET["parent-options"];
	$new_account=$tpl->_ENGINE_parse_body("{new_account}");
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("$new_account", "YahooWin4('600','miniadmin.hotspot.php?uid=&t=$t','$new_account');"));
	echo $boot->SearchFormGen("uid","search-members","",$EXPLAIN);
	
	
}

function section_sessions(){
	$page=CurrentPageName();
	$tpl=new templates();
	$boot=new boostrap_form();
	$sock=new sockets();
	$t=time();
	$ID=$_GET["parent-options"];
	$new_account=$tpl->_ENGINE_parse_body("{new_account}");
	$EXPLAIN["BUTTONS"][]=$tpl->_ENGINE_parse_body(button("$new_account", "YahooWin4('600','miniadmin.hotspot.php?uid=&t=$t','$new_account');"));
	
	unset($EXPLAIN);
	echo $boot->SearchFormGen("uid","search-sessions","",$EXPLAIN);
		
	
}

function sessions_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("uid"=>"ASC"));


	$searchstring=string_to_flexquery("search-members");
	$table="hotspot_sessions";


	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$unlimited=$tpl->_ENGINE_parse_body("{unlimited}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$finaltime=$tpl->_ENGINE_parse_body("{re_authenticate_each}");
	$endtime=$tpl->_ENGINE_parse_body("{endtime}");
	$title=$tpl->_ENGINE_parse_body("{sessions} ".date("{l} d {F}"));
	$q=new mysql_squid_builder();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_account=$tpl->_ENGINE_parse_body("{new_account}");

	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}

	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$color="black";
		$urljs="Loadjs('miniadmin.hotspot.php?MessageID-js=$MessageID&table=$table_query')\"
		style='font-size:11px;text-decoration:underline'>";
		$resend=imgsimple("arrow-blue-left-24.png",null,"javascript:Loadjs('$myPage?MessageID-resend-js=$MessageID&table=$table_query')");
		
		$AddMinutes=intval($ligne["maxtime"]);
		$logintime=intval($ligne["logintime"]);
		$Start=$tpl->_ENGINE_parse_body(date("{l} d H:i",$logintime));
		$delete=imgsimple("delete-48.png",null,"Loadjs('miniadmin.hotspot.php?delete-session-js={$ligne["md5"]}&t=$t')");
		$End=$tpl->_ENGINE_parse_body(date("Y {l} d H:i",$ligne["finaltime"]));
		
		if($ligne["finaltime"]<time()){
			$color="#CD0D0D";
			
		}
		
		$hostname=$ligne["hostname"];
		$nextcheck=$ligne["nextcheck"];
		$nextcheckDate=$tpl->_ENGINE_parse_body("{next_check}: ".date("{l} d H:i",$nextcheck));

					
		

		$tr[]="
		<tr>
		<td style='font-size:18px;color:$color' nowrap  width=1% >{$ligne["uid"]}<br><i style='font-size:12px'>$hostname</i></a></span></td>
		<td style='font-size:18px;color:$color' nowrap >{$ligne["MAC"]}</td>
		<td style='font-size:18px;color:$color' nowrap width=1% >$Start</td>
		<td style='font-size:18px;color:$color' nowrap width=1%>{$ligne["maxtime"]} $minutes</a><br>$nextcheckDate</span></td>
		<td style='font-size:18px;color:$color' nowrap width=1% >$End</td>
		<td style='font-size:18px;color:$color' nowrap width=1% >$delete</td>

		</tr>";


	}
	echo $boot->TableCompile(
			array("uid"=>"$members",
					"MAC"=>"{MAC}",
					"logintime"=>"{logintime}",
					"finaltime"=>"{duration}",
					"endtime"=>"{endtime}",
					"delete"=>"&nbsp;"
		
			),
			$tr
	);
}


function members_search(){
	$tpl=new templates();
	$MyPage=CurrentPageName();
	$boot=new boostrap_form();
	$q=new mysql_squid_builder();
	$sock=new sockets();
	$fontsize="14px";
	$page=1;
	$t=time();
	$ORDER=$boot->TableOrder(array("uid"=>"ASC"));


	$searchstring=string_to_flexquery("search-members");
	$table="hotspot_members";


	$minutes=$tpl->_ENGINE_parse_body("{minutes}");
	$unlimited=$tpl->_ENGINE_parse_body("{unlimited}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$members=$tpl->_ENGINE_parse_body("{members}");
	$MAC=$tpl->_ENGINE_parse_body("{MAC}");
	$ttl=$tpl->_ENGINE_parse_body("{ttl}");
	$finaltime=$tpl->_ENGINE_parse_body("{re_authenticate_each}");
	$endtime=$tpl->_ENGINE_parse_body("{endtime}");
	$title=$tpl->_ENGINE_parse_body("{sessions} ".date("{l} d {F}"));
	$q=new mysql_squid_builder();
	$hostname=$tpl->_ENGINE_parse_body("{hostname}");
	$enabled=$tpl->_ENGINE_parse_body("{enabled}");
	$new_account=$tpl->_ENGINE_parse_body("{new_account}");
	
	$sql="SELECT * FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,250";
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error_html();}
	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$md5=md5(serialize($ligne));
		$uid= $ligne["uid"];
		$ttl=intval($ligne["ttl"]);
		$logintime=intval($ligne["logintime"]);
		$Start=$tpl->_ENGINE_parse_body(date("{l} d H:i",$logintime));
		$delete=imgsimple("delete-48.png",null,"DeleteMember$t('{$ligne["uid"]}','$md5')");
		$End=$tpl->_ENGINE_parse_body(date("{l} d H:i",$ligne["finaltime"]));
		$hostname=$ligne["hostname"];
		$MAC=$ligne["MAC"];
		$ipaddr=$ligne["ipaddr"];
		$ttl=$ligne["ttl"];
		
		if($ttl==0){$ttl=$unlimited;}else{$ttl=$tpl->_ENGINE_parse_body(date("{l} d H:i",$ttl));}
		
		
		$enabled=Field_checkbox("enable_$uid", 1,$ligne["enabled"],"MemberEnable$t('$uid')");
		$color="black";
		if($ligne["enabled"]==0){$color="#A4A1A1";}
		$uid_url=urlencode($ligne["uid"]);
		
		$uid_url=urlencode($ligne["uid"]);
		$js=$boot->trswitch("YahooWin4('600','miniadmin.hotspot.php?uid=$uid_url&t=$t','{$ligne["uid"]}');");
		$jsttl=$boot->trswitch("YahooWin4('500','miniadmin.hotspot.php?ttl=$uid_url&t=$t','$ttl:{$ligne["uid"]}');");

		$tr[]="
		<tr>
		<td style='font-size:18px;color:$color' nowrap  width=1% $js>{$ligne["uid"]}</td>
		<td style='font-size:18px;color:$color' nowrap $jsttl>$ttl</td>
		<td style='font-size:18px;color:$color' nowrap width=1% $js>{$ligne["sessiontime"]} $minutes</td>
		<td style='font-size:18px;color:$color' nowrap width=1%>$enabled</td>
		<td style='font-size:18px;color:$color' nowrap width=1% $js>$delete</td>

		</tr>";


	}
	echo $boot->TableCompile(
				array("uid"=>"$members",
						"ttl"=>"{ttl}",
						"sessiontime"=>"$finaltime",
						"enabled"=>"$enabled",
						"delete"=>"&nbsp;"
			
					),
						$tr
				)."	
<script>				
var x_DeleteSession$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row'+mem$t).remove();
}

function DeleteSession$t(md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteSession',md);	
	XHR.sendAndLoad('miniadmin.hotspot.php', 'POST',x_DeleteSession$t);	

}

function NewAccount$t(){
	YahooWin4('600','miniadmin.hotspot.php?uid=&t=$t','$new_account');
}

var x_MemberEnable$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#flexRT$t').flexReload();
}

function MemberEnable$t(uid){
	var enabled=0;
	if(document.getElementById('enable_'+uid).checked){enabled=1;}
	var XHR = new XHRConnection();
	XHR.appendData('EnableMember',uid);
	XHR.appendData('value',enabled);		
	XHR.sendAndLoad('miniadmin.hotspot.php', 'POST',x_MemberEnable$t);	
	
}

var x_DeleteMember$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	$('#row'+mem$t).remove();
}

function DeleteMember$t(uid,md){
	mem$t=md;
	var XHR = new XHRConnection();
	XHR.appendData('DeleteMember',uid);
	XHR.sendAndLoad('miniadmin.hotspot.php', 'POST',x_DeleteMember$t);		
}

</script>";
}
