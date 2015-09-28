<?php
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.tcpip.inc');
	include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
	include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
	include_once(dirname(__FILE__) . "/ressources/class.sockets.inc");
	include_once(dirname(__FILE__) . "/ressources/class.pdns.inc");
	include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
	include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
	
	
	$user=new usersMenus();
	if($user->AsSquidAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_POST["UseSimplifiedCachePattern"])){UseSimplifiedCachePattern();exit;}
	if(isset($_GET["explainthis"])){explainthis();exit;}
	
page();	
function page(){
	$t=time();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$need_to_reload_proxy=$tpl->javascript_parse_text("{need_to_reload_proxy}");
	$SquidReloadIntoIMS=$sock->GET_INFO("SquidReloadIntoIMS");
	$SquidCacheLevel=$sock->GET_INFO("SquidCacheLevel");
	$EnableRangeOffset=intval($sock->GET_INFO("EnableRangeOffset"));
	$UseSimplifiedCachePattern=$sock->GET_INFO("UseSimplifiedCachePattern");
	if(!is_numeric($SquidCacheLevel)){$SquidCacheLevel=4;}
	if(!is_numeric($UseSimplifiedCachePattern)){$UseSimplifiedCachePattern=1;}
	if(!is_numeric($SquidReloadIntoIMS)){$SquidReloadIntoIMS=1;}
	$refresh_pattern_def_min=$sock->GET_INFO("refresh_pattern_def_min");
	$refresh_pattern_def_max=$sock->GET_INFO("refresh_pattern_def_max");
	$refresh_pattern_def_perc=$sock->GET_INFO("refresh_pattern_def_perc");
	$refresh_pattern_def_opts=unserialize(base64_decode($sock->GET_INFO("refresh_pattern_def_opts")));
	
	
	
	if(!is_numeric($refresh_pattern_def_min)){$refresh_pattern_def_min=0;}
	if(!is_numeric($refresh_pattern_def_max)){$refresh_pattern_def_max=43200;}
	if(!is_numeric($refresh_pattern_def_perc)){$refresh_pattern_def_perc=75;}
	
	for($i=0;$i<101;$i++){
		$precents[$i]="{$i}%";
	}
	
	$refresh_pattern_def_min_field=Field_array_Hash($q->CACHE_AGES,"refresh_pattern_def_min-$t",$refresh_pattern_def_min,"style:font-size:18px");
	
	$button_reconfigure=button("{apply}","Save$t()",40);
	
	$f["override-expire"]=true;
	$f["override-lastmod"]=true;
	$f["reload-into-ims"]=true;
	$f["ignore-reload"]=true;
	$f["ignore-no-store"]=true;
	$f["ignore-must-revalidate"]=true;
	$f["ignore-private"]=true;
	$f["ignore-auth"]=true;
	$f["refresh-ims"]=true;
	$f["store-stale"]=true;
	
	if($SquidReloadIntoIMS==0){unset($refresh_pattern_def_opts["reload-into-ims"]);}
	
	if(count($refresh_pattern_def_opts)<2){
		while (list ($key, $val) = each ($f) ){
			$refresh_pattern_def_opts[$key]=true;
		}
	}
	reset($f);
	$reload_into_ims_p=Paragraphe_switch_img("{reload_into_ims}", "{reload_into_ims_explain}",
			"SquidReloadIntoIMS-$t",$SquidReloadIntoIMS,
			null,1300);
	
	$EnableRangeOffset_p=Paragraphe_switch_img("{enforce_partial_content}", "{enforce_partial_content_explain}",
			"EnableRangeOffset-$t",$EnableRangeOffset,
			null,1300);
	
	while (list ($key, $val) = each ($f) ){
		$valueX=0;
		if(isset($refresh_pattern_def_opts[$key])){$valueX=1;}
		if(!isset($refresh_pattern_def_opts[$key])){$valueX=0;}
		$tr[]="
			<tr>
				<td class=legend style='font-size:22px'>".texttooltip($key,"{{$key}}").":</td>
				<td>". Field_checkbox_design("$key-$t",1,$valueX)."</td>
				
			</tr>";
		
		$js[]="if( document.getElementById('$key-$t').checked ){
					XHR.appendData('$key', 1);
				}else{
					XHR.appendData('$key', 0);
				}
				";
	
	}
	
	
$html="
<div style='width:98%' class=form>
<table style='width:100%'>
<tr>	
<td style='vertical-align:top;width:50px'><div id=\"slider-vertical\" style=\"height:300px;width:45px;margin:30px\"></div></td>
<td style='vertical-align:top;width:99%;padding-left:30px'>
	<div style='font-size:50px;margin-bottom:20px'>{cache_level}:<span id='level-info-$t'>$SquidCacheLevel</span></div>
	<div style='font-size:18px;;margin-bottom:20px' class=explain>{cache_level_explain}</div>
	<div style='font-size:18px' class=explain id='text-$t'></div>
	<div style='margin:20px;margin-top:20px;text-align:right'>$button_reconfigure</div>
	". Paragraphe_switch_img("{simple_cache_configuration}", "{simple_cache_configuration_explain}",
			"UseSimplifiedCachePattern",$UseSimplifiedCachePattern,null,1300)."
	$reload_into_ims_p
	$EnableRangeOffset_p
	
	
	
	
	<table style='width:100%'>
	<tr>
		<tr>
			<td class=legend style='font-size:22px'>".texttooltip("{minimal_time}","{caches_rules_min}").":</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"refresh_pattern_def_min-$t",$refresh_pattern_def_min,
					"style:font-size:22px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>".texttooltip("{max_time}","{caches_rules_max}").":</td>
			<td>". Field_array_Hash($q->CACHE_AGES,"refresh_pattern_def_max-$t",$refresh_pattern_def_max,
					"style:font-size:22px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:22px'>".texttooltip("{refresh_percent}","{caches_rules_percent}").":</td>
			<td>". Field_array_Hash($precents,"refresh_pattern_def_perc-$t",$refresh_pattern_def_perc,
					"style:font-size:22px",null,null,null,false,"SaveCheck$t(event)")."</td>
		</tr>
		<tr style='height:70px'>
		<td colspan=2 style='font-size:30px'>{default_behavior}</td>
		".@implode("\n", $tr)."
	</table>
	<div style='margin:20px;margin-top:60px;text-align:right'>$button_reconfigure</div>
</tr>
</table>
</div>
<script>	
	$(function() {
		$( \"#slider-vertical\" ).slider({
			orientation: \"vertical\",
			range: \"min\",
			min: 0,
			max: 4,
			width:50,
			value: $SquidCacheLevel,
			slide: function( event, ui ) {
				var xval=ui.value;
				document.getElementById('level-info-$t').innerHTML=xval;
				LoadAjax('text-$t','$page?explainthis='+xval);	
				
			}
		});
		$( \"#amount\" ).val( $( \"#slider-vertical\" ).slider( \"value\" ) );
		$('.ui-slider-handle').height(20);
		$('.ui-slider-handle').width(50);  
	});
LoadAjax('text-$t','$page?explainthis=$SquidCacheLevel');	





var xSave$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;}
	if(confirm('$need_to_reload_proxy')){
		Loadjs('squid.compile.progress.php');
	}
}
	
function Save$t(){
	var XHR = new XHRConnection();
		XHR.appendData('UseSimplifiedCachePattern', document.getElementById('UseSimplifiedCachePattern').value);
		XHR.appendData('EnableRangeOffset', document.getElementById('EnableRangeOffset-$t').value);
		XHR.appendData('SquidReloadIntoIMS', document.getElementById('SquidReloadIntoIMS-$t').value);
		XHR.appendData('refresh_pattern_def_min', encodeURIComponent(document.getElementById('refresh_pattern_def_min-$t').value));
		XHR.appendData('refresh_pattern_def_max', encodeURIComponent(document.getElementById('refresh_pattern_def_max-$t').value));
		XHR.appendData('refresh_pattern_def_perc', encodeURIComponent(document.getElementById('refresh_pattern_def_perc-$t').value));
		".@implode("\n", $js)."		
		XHR.sendAndLoad('$page', 'POST',xSave$t);
	}


</script>



";

echo $tpl->_ENGINE_parse_body($html);
}

function UseSimplifiedCachePattern(){
	
	$f["override-expire"]=true;
	$f["override-lastmod"]=true;
	$f["reload-into-ims"]=true;
	$f["ignore-reload"]=true;
	$f["ignore-no-store"]=true;
	$f["ignore-must-revalidate"]=true;
	$f["ignore-private"]=true;
	$f["ignore-auth"]=true;
	$f["refresh-ims"]=true;
	$f["store-stale"]=true;
	
	while (list ($key, $val) = each ($f) ){
		if(isset($_POST[$key])){
			if($_POST[$key]==1){
				$refresh_pattern_def_opts[$key]=true;
				unset($_POST[$key]);
			}
		}
		
	}
	
	
	$sock=new sockets();
	$sock->SaveConfigFile(base64_encode(serialize($refresh_pattern_def_opts)), "refresh_pattern_def_opts");
	
	while (list ($key, $val) = each ($_POST) ){
		$sock->SET_INFO($key,$val);
		
	}
}

function explainthis(){
	$tpl=new templates();
	$sock=new sockets();
	$sock->SET_INFO("SquidCacheLevel",$_GET["explainthis"]);
	echo $tpl->_ENGINE_parse_body("{SquidCacheLevel{$_GET["explainthis"]}}");
	
}
	
	
