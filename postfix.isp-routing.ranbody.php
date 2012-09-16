<?php
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.maincf.multi.inc');
	include_once('ressources/class.system.network.inc');
	if(isset($_GET["org"])){$_GET["ou"]=$_GET["org"];}
	
	if(!PostFixMultiVerifyRights()){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	if(isset($_GET["list"])){popup_list();exit;}
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["word-add"])){add();exit;}
	if(isset($_POST["word-del"])){del();exit;}
js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();	
	$keyword=base64_decode($_GET["keyword"]);
	$title=$tpl->_ENGINE_parse_body("{keyword}&raquo;$keyword");
	echo "RTMMail('548','$page?popup=yes&keyword={$_GET["keyword"]}&zmd5={$_GET["zmd5"]}&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}&t={$_GET["t"]}','$title');";
}
function popup(){
	$t=time();
	$tt=$_GET["t"];
	$page=CurrentPageName();
	$tpl=new templates();	
	$word=$tpl->_ENGINE_parse_body("{word}");
	$new_word=$tpl->javascript_parse_text("{new_word}");
	
$html="
<table class='table-$t' style='display: none' id='table-$t' style='width:99%'></table>
<script>
var tmp$t='';
$(document).ready(function(){
$('#table-$t').flexigrid({
	url: '$page?list=yes&t=$t&keyword={$_GET["keyword"]}&zmd5={$_GET["zmd5"]}&ou={$_GET["ou"]}&hostname={$_GET["hostname"]}',
	dataType: 'json',
	colModel : [
		{display: '$word', name : 'keyword', width : 453, sortable : true, align: 'left'},
		{display: '&nbsp;', name : 'delete', width : 31, sortable : false, align: 'left'},
		
		
	],
buttons : [
	{name: '$new_word', bclass: 'add', onpress : NewWord},

		],	
	searchitems : [
		{display: '$word', name : 'word'},
	
		],
	sortname: 'bweight',
	sortorder: 'asc',
	usepager: true,
	title: '',
	useRp: false,
	rp: 15,
	showTableToggleBtn: false,
	width: 530,
	height: 350,
	singleSelect: true
	
	});   
});	
	var x_NewWord= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue);return;}
		$('#table-$t').flexReload();
		$('#table-$tt').flexReload();
	}	

	function NewWord(){
		var keyword=prompt('$word');
		if(keyword){
			var XHR = new XHRConnection();
			XHR.appendData('word-add',keyword);
			XHR.appendData('keyword','{$_GET["keyword"]}');
			XHR.appendData('hostname','{$_GET["hostname"]}');
			XHR.appendData('ou','{$_GET["ou"]}');
			XHR.appendData('zmd5','{$_GET["zmd5"]}');
			XHR.sendAndLoad('$page', 'POST',x_NewWord);			
		
		}
	
	}
	

	var x_KeyWordDelete= function (obj) {
		var tempvalue=obj.responseText;
		if(tempvalue.length>3){alert(tempvalue)};
		$('#row'+tmp$t).remove();
	}	
	
	function KeyWordDelete(wrdenc,md5){
		tmp$t=md5;
		var XHR = new XHRConnection();
		XHR.appendData('word-del',wrdenc);
		XHR.appendData('keyword','{$_GET["keyword"]}');
		XHR.appendData('hostname','{$_GET["hostname"]}');
		XHR.appendData('ou','{$_GET["ou"]}');
		XHR.appendData('zmd5','{$_GET["zmd5"]}');
		XHR.sendAndLoad('$page', 'POST',x_KeyWordDelete);			
	}
</script>	

	";	
	echo $html;
}

function add(){
	$q=new mysql();
	$keyword=base64_decode($_POST["keyword"]);
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));	

	if(strpos($_POST["word-add"], ",")>0){
		$tt=explode(",", $_POST["word-add"]);
		while(list( $a, $b ) = each ($tt)){
			if(trim($b)==null){continue;}
			$params["BODY_REPLACE"][$keyword][$b]=true;
		}
	}else{
		$params["BODY_REPLACE"][$keyword][$_POST["word-add"]]=true;
	}
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
	
}

function del(){
	$q=new mysql();
	$_POST["word-del"]=base64_decode($_POST["word-del"]);
	$keyword=base64_decode($_POST["keyword"]);
	$sql="SELECT params FROM postfix_smtp_advrt WHERE zmd5='{$_POST["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));			
	unset($params["BODY_REPLACE"][$keyword][$_POST["word-del"]]);
	$newval=base64_encode(serialize($params));
	$sql="UPDATE postfix_smtp_advrt SET params='$newval'  WHERE zmd5='{$_POST["zmd5"]}'";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}		
	
}

function popup_list(){
	$zmd5=$_GET["zmd5"];
	$keyword=base64_decode($_GET["keyword"]);
	$q=new mysql();
	$sql="SELECT hostname,domainname,params FROM postfix_smtp_advrt WHERE zmd5='{$_GET["zmd5"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
	$params=unserialize(base64_decode($ligne["params"]));

	$BODY_REPLACE=$params["BODY_REPLACE"][$keyword];
	$c=0;
	$data = array();
	$data['rows'] = array();	
	if($_POST["query"]<>null){
		$search=$_POST["query"];
		$search=str_replace(".", "\.", $search);
		$search=str_replace("*", ".*?", $search);
		
		
	}
	
	while (list ($word, $null) = each ($BODY_REPLACE) ){
		$color="black";
		$md5=md5("$word$keyword");
		if($search<>null){
			if(!preg_match("#$search#", $word)){continue;}
		}
		$word_enc=base64_encode($word);
		
		$delete=imgsimple("delete-24.png",null,"KeyWordDelete('$word_enc','$md5')");
		
		$c++;
	$data['rows'][] = array(
		'id' => "$md5",
		'cell' => array(
		"<span style='font-size:16px;color:$color'>$word</span>",
		"<span style='font-size:14px;color:$color'>$delete</span>",
		)
		);		
		
	}
	
	if($c==0){json_error_show("No word set...");}
	$data['page'] = 1;
	$data['total'] = $c;
	echo json_encode($data);	
	
}

