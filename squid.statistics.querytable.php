<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.status.inc');
	include_once('ressources/class.artica.graphs.inc');
	
	$users=new usersMenus();
	if(!$users->AsWebStatisticsAdministrator){die();}
	
	if(isset($_GET["query-perform"])){query_perform();exit;}
	
js();


function js(){
	$page=CurrentPageName();
	$html="LoadAjax('{$_GET["span"]}','$page?query-perform=yes&query={$_GET["query"]}&title={$_GET["title"]}&div={$_GET["span"]}&TimeType={$_GET["TimeType"]}&day={$_GET["day"]}');";
	echo $html;
	
}

function query_perform(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$q=new mysql_squid_builder();	
	$sql=base64_decode($_GET["query"]);
	$title=base64_decode($_GET["title"]);
	$TimeType=$_GET["TimeType"];
	$title="
	<div style='float:right;margin:-5px'>". imgtootltip("close-grey-48.png","{close}","document.getElementById('{$_GET["div"]}').innerHTML=''")."</div>
	<center style='font-size:14px;margin:5px'>$title</center>";
	
	
	
	if(preg_match("#HAVING\s+(.+?)='(.+?)'#", $sql,$re)){
		$field_search=$re[1];
		$field_search=str_replace("`", "", $field_search);
		$data_search=urlencode($re[2]);
	}
	
	
	echo $tpl->_ENGINE_parse_body($title);
	
	
	$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'>
	<tr>
		<th width=1% nowrap>{time}</th>
		<th width=50% colspan=2 nowrap>{website}</th>
		<th width=50% nowrap>{member}</th>
		<th width=10% nowrap>{size}</th>
	</tr>
</thead>
<tbody class='tbody'>";

	$results=$q->QUERY_SQL($sql);
	$header=false;
	if(!$q->ok){echo "<H2>$q->mysql_error</H2><center style='font-size:11px'><code>$sql</code></center>";}
	while($ligne=@mysql_fetch_array($results,MYSQL_ASSOC)){
		if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
		if(!$header){
			$htmlHeader="<center><table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'><thead class='thead'><tr>";
			while (list ($a, $b) = each ($ligne) ){$htmlHeader=$htmlHeader."<th>{{$a}}</th>";}
			$htmlHeader=$htmlHeader."</tr></thead><tbody class='tbody'>";
			$header=true;
		}
		
		$html=$html."<tr class=$classtr>";
		while (list ($a, $b) = each ($ligne) ){
			if($a=="category"){
				$category=urlencode($b);
				$js="Loadjs('squid.statistics.query.categories.php?TimeType=$TimeType&field-search=$field_search&data-search=$data_search&category=$category&day={$_GET["day"]}')";
				if($b==null){$b="unknown";}
				$html=$html."<td style='font-size:14px'><a href=\"javascript:blur();\" OnClick=\"javascript:$js\" style='font-size:14px;text-decoration:underline'>$b</a></td>";
				continue;
			}
			if($b==null){$b="&nbsp;";}
			$html=$html."<td style='font-size:14px'>$b</td>";
		}
		$html=$html."</tr>";
		
	
	
}
$html=$html."</tbody></table>";
echo $tpl->_ENGINE_parse_body("<div class=RoundedGrey>$htmlHeader$html</div>");

}

