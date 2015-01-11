<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once('ressources/class.templates.inc');
session_start();
include_once('ressources/class.html.pages.inc');
include_once('ressources/class.cyrus.inc');
include_once('ressources/class.main_cf.inc');
include_once('ressources/charts.php');
include_once('ressources/class.syslogs.inc');
include_once('ressources/class.system.network.inc');
include_once('ressources/class.os.system.inc');
include_once('ressources/class.dansguardian.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");


if(isset($_GET["blacklist"])){blacklist_start();exit;}
if(isset($_GET["blacklist-perform"])){blacklist();exit;}
if(isset($_GET["whitelist-perform"])){whitelist();exit;}
if(isset($_GET["delete-personal-category-js"])){category_delete_js();exit;}
if(isset($_POST["delete-personal-category"])){category_delete();exit;}

if(isset($_GET["whitelist"])){whitelist_start();exit;}



tabs();

function category_delete_js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	
	$sql="SELECT category FROM usersisp_catztables WHERE zmd5='{$_GET["delete-personal-category-js"]}'";
	$ligne=@mysql_fetch_array($q->QUERY_SQL($sql));
	if(!$q->ok){echo "alert(\"Fatal $q->mysql_error\")";return;}
	if($ligne["category"]==null){
		echo "alert('Fatal: {$_GET["delete-personal-category-js"]} = None...')";
		return;
		
	}
	
	$deletethiscat=$tpl->javascript_parse_text("{delete_this_category_ask}");
	
	$html="
	
	var x_Del$t= function (obj) {
		var results=obj.responseText;
		if(results.length>3){alert(results);return;}
		ISPPersonalBlackList();
	}	
		
	function Del$t(){
	
		if(confirm('$deletethiscat:{$ligne["category"]}')){
			var XHR = new XHRConnection();
			XHR.appendData('delete-personal-category','{$_GET["delete-personal-category-js"]}');
			XHR.sendAndLoad('$page', 'POST',x_Del$t);		
		
		}
	}
	
	Del$t();
	";
	echo $html;
	
}

function category_delete(){
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DELETE FROM usersisp_catztables WHERE `zmd5`='{$_POST["delete-personal-category"]}'");
	if(!$q->ok){echo $q->mysql_error;return;}
	
	
}

function tabs(){
	
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;
	
	$array["blacklist"]="{banned_categories}";
	$array["whitelist"]="{authorized_categories}";
	
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){
		
		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize;font-weight:normal'><span>$ligne</span></a></li>\n");
	}
	
	
	
	echo "
	<div id=main_yserufdbguard_tabs style='width:99%;overflow:auto'>
		<ul>". implode("\n",$html)."</ul>
	</div>
		<script>
			$(document).ready(function(){
				$('#main_yserufdbguard_tabs').tabs();
			});
		</script>";	

}

function blacklist_start(){
	$t=time();
	$page=CurrentPageName();
	
	$html="<div id='personalcatz-$t' style='min-height:600px;overflow:auto;width:100%'></div>
	<script>
		function ISPPersonalBlackList(){
			LoadAjax('personalcatz-$t','$page?blacklist-perform=yes');
		}
	ISPPersonalBlackList();
	</script>
	";
	echo $html;
	
}

function whitelist_start(){
	$t=time();
	$page=CurrentPageName();
	
	$html="<div id='personalcatz-white-$t' style='min-height:600px;overflow:auto;width:100%'></div>
	<script>
		function ISPPersonalBlackList(){
			LoadAjax('personalcatz-white-$t','$page?whitelist-perform=yes');
		}
	ISPPersonalBlackList();
	</script>
	";
	echo $html;	
	
}

function whitelist(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;	
	$ss=new dansguardian_rules();
	$array_blacksites=$ss->array_blacksites;
	$array_pics=$ss->array_pics;
	$q=new mysql_squid_builder();
	//usersisp_blkwcatz
	$add_categories=Paragraphe("64-categories-add.png", "{add_category}", "{add_category_text}","javascript:YahooWin2('770','squid.users.choose.catz.php?blk=1','{add_category}')");
	$html="<div class=text-info style='font-size:14px'>{whitelist_categories_explain}</div>";
	
	$count_webfilters_categories_caches=$q->COUNT_ROWS("webfilters_categories_caches");
	writelogs("webfilters_categories_caches $count_webfilters_categories_caches rows",__FUNCTION__,__FILE__,__LINE__);
	if($count_webfilters_categories_caches==0){
		$ss->CategoriesTableCache();
	}	
	$tr[]=$add_categories;
	$t=time();
	
	$sql="SELECT `category` FROM usersisp_blkwcatz";
	$results=$q->QUERY_SQL($sql);	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$img2=null;
		$sql2="SELECT * FROM webfilters_categories_caches WHERE categorykey='{$ligne["category"]}'";
		$ligne2=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(isset($array_pics[$ligne["category"]])){$img2=":{$array_pics[$ligne["category"]]}";}
		if(trim($ligne2["description"])==null){$ligne2["description"]="{$array_blacksites[$ligne["category"]]}";}
		$tr[]=Paragraphe("64-categories-lock.png$img2", "{$ligne["category"]}:({locked})", "{$ligne2["description"]}","javascript:blur('$t')");
		
	}	

	
	$sql="SELECT `category`,zmd5 FROM usersisp_catztables WHERE userid={$_SESSION["uid"]} AND blck=1";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sql2="SELECT * FROM webfilters_categories_caches WHERE categorykey='{$ligne["category"]}'";
		$ligne2=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(isset($array_pics[$ligne["category"]])){$img2=":{$array_pics[$ligne["category"]]}";}
		if(trim($ligne2["description"])==null){$ligne2["description"]="{$array_blacksites[$ligne["category"]]}";}
		$tr[]=Paragraphe("64-categories-white.png$img2", "{$ligne["category"]}", "{$ligne2["description"]}","javascript:Loadjs('$page?delete-personal-category-js={$ligne["zmd5"]}')");
				
		
	}
	
	
	
	$table=CompileTr3($tr);
	$html=$tpl->_ENGINE_parse_body($html);
	echo "<div class=form style='width:97%;min-height:590px'>$html<div style='width:80%;padding-left:60px'>".$tpl->_ENGINE_parse_body("{$table}")."</div></div>";
	
}

function blacklist(){
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();
	$fontsize=16;	
	$ss=new dansguardian_rules();
	$array_blacksites=$ss->array_blacksites;
	$array_pics=$ss->array_pics;
	$q=new mysql_squid_builder();
	$add_categories=Paragraphe("64-categories-add.png", "{add_category}", "{add_category_text}","javascript:YahooWin2('770','squid.users.choose.catz.php','{add_category}')");
	$html="<div class=text-info style='font-size:14px'>{banned_categories_explain}</div>";
	

	
	$count_webfilters_categories_caches=$q->COUNT_ROWS("webfilters_categories_caches");
	writelogs("webfilters_categories_caches $count_webfilters_categories_caches rows",__FUNCTION__,__FILE__,__LINE__);
	if($count_webfilters_categories_caches==0){
		$ss->CategoriesTableCache();
	}	
	$tr[]=$add_categories;
	$t=time();
	$sql="SELECT `category` FROM usersisp_blkcatz";
	$results=$q->QUERY_SQL($sql);	
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$img2=null;
		$sql2="SELECT * FROM webfilters_categories_caches WHERE categorykey='{$ligne["category"]}'";
		$ligne2=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(isset($array_pics[$ligne["category"]])){$img2=":{$array_pics[$ligne["category"]]}";}
		if(trim($ligne2["description"])==null){$ligne2["description"]="{$array_blacksites[$ligne["category"]]}";}
		$tr[]=Paragraphe("64-categories-lock.png$img2", "{$ligne["category"]}:({locked})", "{$ligne2["description"]}","javascript:blur('$t')");
		
	}
	
	$sql="SELECT `category`,zmd5 FROM usersisp_catztables WHERE userid={$_SESSION["uid"]} AND blck=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$sql2="SELECT * FROM webfilters_categories_caches WHERE categorykey='{$ligne["category"]}' ";
		$ligne2=@mysql_fetch_array($q->QUERY_SQL($sql));
		if(isset($array_pics[$ligne["category"]])){$img2=":{$array_pics[$ligne["category"]]}";}
		if(trim($ligne2["description"])==null){$ligne2["description"]="{$array_blacksites[$ligne["category"]]}";}
		$tr[]=Paragraphe("64-categories-ban.png$img2", "{$ligne["category"]}", "{$ligne2["description"]}","javascript:Loadjs('$page?delete-personal-category-js={$ligne["zmd5"]}')");
				
		
	}
	
	
	
	$table=CompileTr3($tr);
	$html=$tpl->_ENGINE_parse_body($html);
	echo "<div class=form style='width:97%;min-height:590px'>$html<div style='width:80%;padding-left:60px'>".$tpl->_ENGINE_parse_body("{$table}")."</div></div>";
	
}
?>
