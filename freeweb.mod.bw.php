<?php
	session_start();
	if($_SESSION["uid"]==null){echo "window.location.href ='logoff.php';";die();}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.apache.inc');
	include_once('ressources/class.freeweb.inc');
	include_once('ressources/class.user.inc');
	$user=new usersMenus();
	if($user->AsWebMaster==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_POST["BW_DELETE"])){rule_popup_delete();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_GET["table"])){table_list();exit;}
	if(isset($_GET["rule-popup"])){rule_popup();exit;}
	if(isset($_GET["BW_ENGINE"])){rule_popup_type();exit;}
	if(isset($_POST["BW_ENGINE"])){rule_popup_save();exit;}
	
	
	
	js();
	
	
function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$title=$tpl->_ENGINE_parse_body("{bandwith_limitation}");
	$html="YahooWin3('600','$page?popup=yes&servername={$_GET["servername"]}','{$_GET["servername"]}::$title');";
	echo $html;
	}
	
function popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$t=time();
	
	$html="<div id='$t' style='width:100%;height:250px;overflow:auto'></div>
	<script>
		function RefreshBWTable(){
			LoadAjax('$t','$page?table=yes&servername={$_GET["servername"]}');
		}
		RefreshBWTable();
	</script>";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function table_list(){
	
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_GET["servername"]);
	$CONF=$free->Params["ModBwParams"];
	
	$add=imgtootltip("plus-24.png","{add} {rule}","BandwithRuleAdd(-1)");
	
	
		$html="
<table cellspacing='0' cellpadding='0' border='0' class='tableView' style='width:100%'>
<thead class='thead'>
	<tr>
	<th width=1%>$add</th>
	<th>{rule}</th>
	<th>&nbsp;</th>
	</tr>
</thead>
<tbody class='tbody'>";	
		
	$type["BandWidth"]="{limit_client}";
	$type["LargeFileLimit"]="{limit_download_file}";
	$type["MaxConnection"]="{limit_connections}";
		
while (list ($index, $RuleArray) = each ($CONF) ){
	if($classtr=="oddRow"){$classtr=null;}else{$classtr="oddRow";}
	$speed=$RuleArray["BW_MAX"]/1000;
	$speed="$speed kb/s";
	
	if($RuleArray["BW_ENGINE"]=="MaxConnection"){$speed="{$RuleArray["BW_MAX"]} {connections}";}
	if($RuleArray["BW_ENGINE"]=="LargeFileLimit"){$speed="$speed {if} >{$RuleArray["BW_SIZE"]} Kb";}
	$text="<a href=\"javascript:blur();\" OnClick=\"javascript:BandwithRuleAdd($index)\" 
	style='font-size:16px;text-decoration:underline'>{$type[$RuleArray["BW_ENGINE"]]}&nbsp;({$RuleArray["BW_SOURCE"]}) $speed</a> ";
	
	$delete=imgtootltip("delete-32.png","{delete} $index","DeleteBW($index)");
	
	
			$html=$html."
			<tr class=$classtr>
			<td colspan=2 width=100% style='font-size:16px'>$text</td>
			<td width=1%>$delete</td>
			</tr>
			";
	
	
}

$html=$html."
</tbody>
</table>
<script>

	function BandwithRuleAdd(index){
		YahooWin4('550','$page?rule-popup='+index+'&servername={$_GET["servername"]}','{$_GET["servername"]}::'+index);
	
	}
	
		var x_DeleteBW=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}	
			RefreshBWTable();
		}		
	
	function DeleteBW(index){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');			
			XHR.appendData('index',index);
			XHR.appendData('BW_DELETE','yes');
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_DeleteBW);
		}	

	</script>
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
}

function rule_popup_delete(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_POST["servername"]);
	unset($free->Params["ModBwParams"][$_POST["index"]]);
	$free->SaveParams();
}


function rule_popup(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_GET["servername"]);
	$CONF=$free->Params["ModBwParams"][$_GET["rule-popup"]];
	$buttonName="{apply}";
	if($_GET["rule-popup"]==-1){$buttonName='{add}';}
	$type[null]="{select}";
	$type["BandWidth"]="{limit_client}";
	$type["LargeFileLimit"]="{limit_download_file}";
	$type["MaxConnection"]="{limit_connections}";
	$t=time();
	
	$html="
	<table style='width:99%;' class=form>
	<tbody>
	<tr>
		<td class=legend style='font-size:14px'>{limitation_engine}:</td>
		<td>". Field_array_Hash($type, "BW_ENGINE",$CONF["BW_ENGINE"],"Changebwtype()",null,0,"font-size:14px")."</td>
	</tr>
	</tbody>
	</table>
	<span id='$t'></span>
	
	<div style='width:100%;text-align:right'><hr>". button($buttonName,"SaveBwRule()","16")."</div>
	
	<script>
		function Changebwtype(){
		var BW_ENGINE=document.getElementById('BW_ENGINE').value;
		LoadAjax('$t','$page?BW_ENGINE='+BW_ENGINE+'&index={$_GET["rule-popup"]}&servername={$_GET["servername"]}');
		
	}
	
		var x_SaveBwRule=function (obj) {
			var results=obj.responseText;
			if(results.length>3){alert(results);}	
			RefreshBWTable();
			YahooWin4Hide();
		}		
	
	function SaveBwRule(){
			var XHR = new XHRConnection();
			XHR.appendData('servername','{$_GET["servername"]}');			
			XHR.appendData('index','{$_GET["rule-popup"]}');
			XHR.appendData('BW_ENGINE',document.getElementById('BW_ENGINE').value);
			XHR.appendData('BW_SOURCE',document.getElementById('BW_SOURCE').value);
			XHR.appendData('BW_MAX',document.getElementById('BW_MAX').value);
			if(document.getElementById('BW_SIZE')){XHR.appendData('BW_SIZE',document.getElementById('BW_SIZE').value);}
			AnimateDiv('$t');
			XHR.sendAndLoad('$page', 'POST',x_SaveBwRule);
		}
	
	
	Changebwtype();
	</script>
	
	";
	echo $tpl->_ENGINE_parse_body($html);

}

function rule_popup_save(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_POST["servername"]);
	
	if($_POST["BW_ENGINE"]=="BandWidth"){$_POST["BW_MAX"]=$_POST["BW_MAX"]*1000;}
	if($_POST["BW_ENGINE"]=="MinBandWidth"){$_POST["BW_MAX"]=$_POST["BW_MAX"]*1000;}
	if($_POST["BW_ENGINE"]=="LargeFileLimit"){$_POST["BW_MAX"]=$_POST["BW_MAX"]*1000;}
	
	if($_POST["index"]>0){
		while (list ($key, $val) = each ($_POST) ){
			$free->Params["ModBwParams"][$_POST["index"]][$key]=$val;
		}
		
	}else{
		while (list ($key, $val) = each ($_POST) ){$newarray[$key]=$val;}
		$CONF=$free->Params["ModBwParams"][]=$newarray;
	}

	$free->SaveParams();
	
}

function rule_popup_type(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$free=new freeweb($_GET["servername"]);
	$CONF=$free->Params["ModBwParams"][$_GET["index"]];
	if(!is_array($CONF)){$CONF=array();}
	$type=$_GET["BW_ENGINE"];
	$t=time();	
	
	
	
	if($type=="BandWidth"){
		$html="
		<div class=text-info>{apache_explain_BandWidth}</div>
		
		<table style='width:99%;' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{source}:</td>
			<td>". Field_text( "BW_SOURCE",$CONF["BW_SOURCE"],"font-size:14px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{speed}:</td>
			<td style='font-size:14px'>". Field_text( "BW_MAX",$CONF["BW_MAX"]/1000,"font-size:14px;width:60px")."&nbsp;k/sec</td>
		</tr>	
		</tbody>
		</table>";	
		echo $tpl->_ENGINE_parse_body($html);
		return;	
	}
	if($type=="MinBandWidth"){
		$html="
		<div class=text-info>{apache_explain_BandWidth}</div>
		
		<table style='width:99%;' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{source}:</td>
			<td>". Field_text("BW_SOURCE",$CONF["BW_SOURCE"],"font-size:14px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{speed}:</td>
			<td style='font-size:14px'>". Field_text( "BW_MAX",$CONF["BW_MAX"]/1000,"font-size:14px;width:60px")."&nbsp;k/sec</td>
		</tr>	
		</tbody>
		</table>";	
		echo $tpl->_ENGINE_parse_body($html);
		return;	
	}
	if($type=="LargeFileLimit"){
		$html="
		<div class=text-info>{apache_explain_LargeFileLimit}</div>
		
		<table style='width:99%;' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{type}:</td>
			<td>". Field_text("BW_SOURCE",$CONF["BW_SOURCE"],"font-size:14px;width:120px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{filesize}:</td>
			<td style='font-size:14px'>". Field_text( "BW_SIZE",$CONF["BW_SIZE"],"font-size:14px;width:60px")."&nbsp;Kb</td>
		</tr>		
		<tr>
			<td class=legend style='font-size:14px'>{speed}:</td>
			<td style='font-size:14px'>". Field_text( "BW_MAX",$CONF["BW_MAX"]/1000,"font-size:14px;width:60px")."&nbsp;k/sec</td>
		</tr>	
		</tbody>
		</table>";	
		echo $tpl->_ENGINE_parse_body($html);
		return;	
	}		
	
	if($type=="MaxConnection"){
		$html="
		<div class=text-info>{apache_explain_MaxConnection}</div>
		
		<table style='width:99%;' class=form>
		<tbody>
		<tr>
			<td class=legend style='font-size:14px'>{source}:</td>
			<td>". Field_text( "BW_SOURCE",$CONF["BW_SOURCE"],"font-size:14px")."</td>
		</tr>
		<tr>
			<td class=legend style='font-size:14px'>{max_connections}:</td>
			<td style='font-size:14px'>". Field_text( "BW_MAX",$CONF["BW_MAX"],"font-size:14px;width:60px")."</td>
		</tr>	
		</tbody>
		</table>";	
		echo $tpl->_ENGINE_parse_body($html);
		return;	
	}	
	
	
}


function save(){
	$free=new freeweb($_POST["servername"]);
	$CONF=$free->Params["PageSpeedParams"];
	while (list ($num, $ligne) = each ($_POST) ){
		$free->Params["PageSpeedParams"][$num]=$ligne;
	}

	$free->SaveParams();
}
