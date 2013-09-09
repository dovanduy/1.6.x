<?php
session_start();
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
if(!isset($_SESSION["uid"])){header("location:miniadm.logon.php");}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.miniadm.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.postfix.builder.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
if(isset($_GET["content"])){content();exit;}
if(isset($_GET["master-content"])){master_content();exit;}
if(isset($_GET["title-not-categorized"])){title_not_categorized();exit;}
if(isset($_GET["search-websites"])){search_websites();exit;}
if(isset($_POST["CATEGORIZE"])){CATEGORIZE();exit;}

$users=new usersMenus();
if(!$users->AsWebStatisticsAdministrator){header("location:miniadm.logon.php");die();}
main_page();

function main_page(){
	$page=CurrentPageName();
	buildCategoriesCache(true);
	$tplfile="ressources/templates/endusers/index.html";
	if(!is_file($tplfile)){echo "$tplfile no such file";die();}
	$content=@file_get_contents($tplfile);


	$content=str_replace("{SCRIPT}", "<script>LoadAjax('globalContainer','$page?content=yes')</script>", $content);
	echo $content;
}
function content(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$t=time();
	$html="
	<div class=BodyContent>
	<div style='font-size:14px'><a href=\"miniadm.index.php\">{myaccount}</a>
	&nbsp;&raquo;&nbsp;<a href=\"miniadm.webstats-start.php\">{web_statistics}</a>
	</div>
	<H1 id='title-not-categorized'></H1>
	<p>{not_categorized_explain_why}<hr><span style='font-weight:bold'>{categorize_becare}</span></p>
	</div>
	<div id='master-content'></div>

	<script>
		LoadAjax('master-content','$page?master-content=yes');
	</script>
	";
	echo $tpl->_ENGINE_parse_body($html);
}

function master_content(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	$boot=new boostrap_form();
	$SearchQuery=$boot->SearchFormGen("sitename,familysite,domain,country","search-websites")."
	<script>
		function RefreshNotCategorizedTitle(){
			LoadAjaxTiny('title-not-categorized','$page?title-not-categorized=yes');
		}
		RefreshNotCategorizedTitle();
	</script>
			";
	echo $SearchQuery;	
}

function title_not_categorized(){
	$tpl=new templates();
	$q=new mysql_squid_builder();
	$number=FormatNumber($q->COUNT_ROWS("notcategorized"));
	echo $tpl->_ENGINE_parse_body("$number {not_categorized}");
}


function FormatNumber($number, $decimals = 0, $thousand_separator = '&nbsp;', $decimal_point = '.'){
	$tmp1 = round((float) $number, $decimals);
	while (($tmp2 = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $tmp1)) != $tmp1)
		$tmp1 = $tmp2;
	return strtr($tmp1, array(' ' => $thousand_separator, '.' => $decimal_point));
}

function CATEGORIZE(){
	$category=$_POST["CATEGORIZE"];
	$www=$_POST["WWW"];
	$q=new mysql_squid_builder();
	$q->categorize($www, $category);
	$q->QUERY_SQL("DELETE FROM notcategorized WHERE sitename='$www'");
	if(!$q->ok){echo $q->mysql_error;}
	
}

function search_websites(){
	$q=new mysql_squid_builder();
	$tpl=new templates();
	$searchstring=string_to_flexquery("search-websites");
	$Params=url_decode_special_tool($_GET["Params"]);
	$table="notcategorized";
	
	$boot=new boostrap_form();
	$ORDER=$boot->TableOrder(array("hits"=>"DESC"));
	
	$sql="SELECT *  FROM $table WHERE 1 $searchstring ORDER BY $ORDER LIMIT 0,150";
	writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
	$results = $q->QUERY_SQL($sql);

	
	if(!$q->ok){
		echo "<p class=text-error>$q->mysql_error<hr><code>$sql</code></p>";
	}
	
	while ($ligne = mysql_fetch_assoc($results)) {
		$id=md5($ligne["sitename"]);
		$ligne["size"]=FormatBytes($ligne["size"]/1024);
		$ligne["hits"]=FormatNumber($ligne["hits"]);
		$dates=unserialize($ligne["seen"]);
		$trg=array();
		if(is_array($dates)){
			while (list ($none, $xtime) = each ($dates) ){
				$trg[]=time_to_date($xtime);
			}
		}
		
		$params=BuildDiv($ligne["sitename"]);
		$link=$boot->trswitch($params[2]);
		$content=$params[0];
		$js[]=$params[1];
	$tr[]="
	<tr id='$id'>
	<td $link><i class='icon-globe'></i> {$ligne["sitename"]}$content<br><i style='font-size:10px'>". @implode(", ", $trg)."</td>
	<td $link nowrap><i class='icon-globe'></i> {$ligne["familysite"]}</td>
	<td $link nowrap><i class='icon-globe'></i> {$ligne["country"]}</td>
	<td $link nowrap><i class='icon-globe'></i> {$ligne["size"]}</td>
	<td $link nowrap><i class='icon-star'></i> {$ligne["hits"]}</td>
	</tr>";
	
	
	}

	
echo $boot->TableCompile(
			array("sitename"=>"{website}","familysite"=>"{familysite}","country"=>"{country}",
					"size"=>"{size}",
					"hits"=>"{hits}"
					
					),
			$tr
			)."
			<script>".@implode("\n", $js)."</script>	
					
			";
}


function BuildDiv($sitename){
	$tpl=new templates();
	$page=CurrentPageName();
	$md5=md5($sitename);
	$boot=new boostrap_form();
	$sitename=urlencode($sitename);
	$categories=buildCategoriesCache();
	$ARTICA=$categories["CATEGORIES_ARTICA"];
	$CATEGORIES_PERSO=$categories["CATEGORIES_PERSO"];
	$categorize=$tpl->_ENGINE_parse_body("{categorize}");
	$generic_categories=$tpl->_ENGINE_parse_body("{categories}");
	$your_categories=$tpl->_ENGINE_parse_body("{your_categories}");
	$find=$tpl->_ENGINE_parse_body("{search}");
	$t=time();
	$ART[]="
	<center id='animate-$md5'></center>
	<div style='text-align:right;margin-bottom:10px' >
	<input id='s-$md5' class='input-medium search-query' onkeypress='javascript:SearchQuery$md5()' type='text'>
   	<button type='button' id='$md5-$t' class='btn' OnClick=\"javascript:SearchQuery$md5()\">$find</button>
	</div>
			
	<table class='table table-bordered table-hover'>
			<thead>
				<tr>
				<th colspan=2>". $tpl->_ENGINE_parse_body("{category}")."</th>
				<th>". $tpl->_ENGINE_parse_body("{description}")."</th>
				</tr>
			</thead>
				";
	
	while (list ($category, $params) = each ($ARTICA) ){
		$categoryID=trim($category);
		$categoryID=str_replace(" ", "", $categoryID);
		$categoryID=str_replace("/", "-", $categoryID);
		$link=$boot->trswitch("Categorize$md5('$category','$sitename','$md5')");
		$ART[]="
		<tr id='$md5-tr-$categoryID'>
		<td $link width=1% nowrap>{$params["IMG"]}</td>
		<td $link width=1% nowrap>$category</td>
		<td $link>{$params["DESC"]}</td>
		</tr>";
		
		
	
	}
	$ART[]="</table>";
	
	$PERS[]="<table class='table table-bordered table-hover'>
			<thead>
				<tr>
				<th colspan=2>". $tpl->_ENGINE_parse_body("{category}")."</th>
				<th>". $tpl->_ENGINE_parse_body("{description}")."</th>
				</tr>
			</thead>
				";
	
	
	while (list ($category, $params) = each ($CATEGORIES_PERSO) ){
		$categoryID=trim($category);
		$categoryID=str_replace(" ", "", $categoryID);
		$categoryID=str_replace("/", "-", $categoryID);
		$link=$boot->trswitch("Categorize$md5('$category','$sitename','$md5')");
		$PERS[]="
		<tr id='$md5-tr-$categoryID'>
		<td $link width=1% nowrap>{$params["IMG"]}</td>
		<td $link width=1% nowrap>$category</td>
		<td $link>{$params["DESC"]}</td>
		</tr>";
	
	}	
	$PERS[]="</table>";
	$html="
	
	<div id='dialog-$md5' title='$sitename'>
	<div id='logs-$md5'></div>
	<H3>$sitename</H3>
	<ul class='nav nav-tabs' id='site-$md5'>
		<li class='active' id='gen-$md5'><a href='javascript:blur();' OnClick=\"javascript:Gecat$md5()\">$generic_categories</a></li>
		";
		
	if(count($CATEGORIES_PERSO)>0){$html=$html."<li><a href='javascript:blur();'  OnClick=\"javascript:Pers$md5()\" id='pers-$md5'>$your_categories</a></li>";}
	$html=$html."</ul>
		<div class='tab-pane active' id='site-$md5-art'>
			".@implode("\n",$ART)."
		</div>
		<div class='tab-pane' id='site-$md5-pers' style='display:none'>
			".@implode("\n",$PERS)."
		</div>
	</div>
					
	<script>
	var md{$md5}='';
		function Gecat$md5(){
			document.getElementById('gen-$md5').className='active';
			
			document.getElementById('site-$md5-art').className='tab-pane active';
			document.getElementById('site-$md5-art').style.display='block';
			
			if(document.getElementById('pers-$md5')){
				document.getElementById('pers-$md5').className='';
				document.getElementById('site-$md5-pers').classList.remove('active');
				document.getElementById('site-$md5-pers').style.display='none';
			}
		}
		
		function SearchQuery$md5(){
			var sid='';
			var xlogs='';
			var pos=0;
			var category='';
			var pattern=document.getElementById('s-$md5').value;
			
			if(pattern.length==0){
				$('#dialog-$md5 [id^=\"$md5-tr-\"]').show();
				return;
			}
			$('#dialog-$md5 [id^=\"$md5-tr-\"]').hide();
			$('#dialog-$md5 [id^=\"$md5-tr-\"]').each(function(){
    			if(this.id){
    				sid=this.id;
    				var myRegexp = /$md5-tr-(.*?)$/;
    				var match = myRegexp.exec(sid);
    				category=' '+match[1];
    				xlogs=xlogs+'Found <b>'+category+'</b>';
    				pos=category.search(pattern);
    				if ( pos>0 ) {
    					$('#'+sid).show();
    					xlogs=xlogs+' <span style=color:red>found</span><br>';
    				}else{
    					xlogs=xlogs+' '+pattern+' '+'<b>'+pos+'</b> not found<br>';
    				}
    				
    			}
			});
			
			//document.getElementById('logs-$md5').innerHTML=xlogs;
			
		}
		
	var xCategorize$md5= function (obj) {
		var results=obj.responseText;
		document.getElementById('animate-$md5').innerHTML='';
		if(results.length>2){
			alert(results);
			return;
		 }
		$('#dialog-$md5').empty();
		$('#dialog-$md5').dialog('destroy');
		$('#'+md{$md5}).remove();
		RefreshNotCategorizedTitle();
		 
		}		
		
		function Categorize$md5(cat,site,md){
			md{$md5}=md;
			if(!confirm(site +' --> '+cat+' ?')){return;}
			var XHR = new XHRConnection();
			XHR.appendData('CATEGORIZE',cat);
			XHR.appendData('WWW',site);
			AnimateDiv('animate-$md5');
			XHR.sendAndLoad('$page', 'POST',xCategorize$md5);	
		}
		
		

		
				
		
		function Pers$md5(){
			document.getElementById('gen-$md5').className='';
			document.getElementById('pers-$md5').className='active';
			document.getElementById('site-$md5-pers').className='tab-pane active';
			document.getElementById('site-$md5-art').classList.remove('active');
			document.getElementById('site-$md5-art').style.display='none';
			document.getElementById('site-$md5-pers').style.display='block';
		}	
	</script>				
";
	
	return array($html,
			"$('#dialog-$md5').dialog({autoOpen: false,width: '800px',position: 'top',zIndex:8999});\n$('#dialog-$md5').dialog( 'option', 'height', 700 );"
			,"$( '#dialog-$md5' ).dialog( 'open' );");

}
?>
