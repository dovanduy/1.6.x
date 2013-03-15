<?php

	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.squid.inc');
	
	
$usersmenus=new usersMenus();
if(!$usersmenus->AsDansGuardianAdministrator){
	$tpl=new templates();
	$alert=$tpl->_ENGINE_parse_body('{ERROR_NO_PRIVS}');
	echo "alert('$alert');";
	die();	
}

if(isset($_POST["duplicate-from"])){duplicate_rule();exit;}


js();


function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	$t2=time();
	$t=$_GET["t"];
	$rulefrom=$_GET["from"];
	if(is_numeric($rulefrom)){
		$q=new mysql_squid_builder();
		$sql="SELECT groupname FROM webfilter_rules WHERE ID=$rulefrom";
		$results=$q->QUERY_SQL($sql);
		$ligne=mysql_fetch_array($results);
		$tmpname=$ligne["groupname"]." (copy)";
		$tmpname=addslashes($tmpname);
	}
	
	if(isset($_GET["default-rule"])){
		$rulefrom="default";
		$tmpname=$tpl->javascript_parse_text("{default} (copy)");
	}
	header("content-type: application/x-javascript");
	$ask=$tpl->javascript_parse_text("{duplicate_the_ruleid_give_name}");
	$html="
		var x_Duplicaterule$t2= function (obj) {
			var res=obj.responseText;
			if (res.length>0){alert(res);}
			$('#flexRT$t').flexReload();
		}
	
	
		function Duplicaterule$t2(){
			var rulename=prompt('$ask $rulefrom','$tmpname');
			if(!rulename){return;}
			 var XHR = new XHRConnection();
		     XHR.appendData('duplicate-from', '$rulefrom');
		     XHR.appendData('duplicate-name', rulename);
		     XHR.sendAndLoad('$page', 'POST',x_Duplicaterule$t2); 
		
		}
		
	
	Duplicaterule$t2();";
	echo $html;
}

function duplicate_default_rule(){
	$idname=addslashes($_POST["duplicate-name"]);
	$sock=new sockets();
	$ligne=unserialize(base64_decode($sock->GET_INFO("DansGuardianDefaultMainRule")));
	$ligne["groupmode"]=1;
	$ligne["enabled"]=1;
	$ligne["endofrule"]="any";
	$f["groupmode"]=true;
	$f["enabled"]=true;
	$f["BypassSecretKey"]=true;
	$f["endofrule"]=true;
	$f["blockdownloads"]=true;
	$f["naughtynesslimit"]=true;
	$f["searchtermlimit"]=true;
	$f["bypass"]=true;
	$f["deepurlanalysis"]=true;
	$f["UseExternalWebPage"]=true;
	$f["ExternalWebPage"]=true;
	$f["freeweb"]=true;
	$f["sslcertcheck"]=true;
	$f["sslmitm"]=true;
	$f["GoogleSafeSearch"]=true;
	$f["TimeSpace"]=true;
	$f["TemplateError"]=true;
	$f["RewriteRules"]=true;
	$idname=addslashes($_POST["duplicate-name"]);
	$fields[]="`groupname`";
	$values[]="'".$idname."'";
	while (list ($key, $none) = each ($f)){
		$fields[]="`$key`";
		$values[]="'".addslashes($ligne[$key])."'";
		
	}
		
		
	
	$q=new mysql_squid_builder();
	$sql="INSERT INTO webfilter_rules (".@implode(",", $fields).")
	VALUES (".@implode(",", $values).")";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$newruleid=$q->last_id;
	if($newruleid<1){echo "Failed";return;}
	
	$sql="SELECT * FROM webfilter_assoc_groups WHERE webfilter_id=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$groupid=$ligne["group_id"];
		$md5=md5("$newruleid$groupid");
		$sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
	
	}
	
	$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$category=$ligne["category"];
		$category=addslashes($category);
		$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");
		if(!$q->ok){echo $q->mysql_error;return;}
	
	}
	$sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=0";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$description=addslashes($ligne["description"]);
		$md5=md5("$newruleid{$ligne["ext"]}");
		$enabled=$ligne["enabled"];
		$q->QUERY_SQL("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,'$md5','{$ligne["ext"]}','$description',$newruleid);");
		if(!$q->ok){echo $q->mysql_error;return;}
	}
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");
	
}


function duplicate_rule(){
	$idfrom=$_POST["duplicate-from"];
	if($idfrom=="default"){duplicate_default_rule();exit;}
	$idname=addslashes($_POST["duplicate-name"]);

	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilter_rules WHERE ID=$idfrom";
	$results=$q->QUERY_SQL($sql);
	$len = mysql_num_fields($results);
	$ligne=mysql_fetch_array($results);
	for ($i = 0; $i < $len; $i++) {
		$name = mysql_field_name($results, $i);
		if($name=="ID"){continue;}
		$fields[]="`$name`";
		if($name=="groupname"){$ligne[$name]=$idname;}
		$values[]="'".addslashes($ligne[$name])."'";
	}
	
	$sql="INSERT INTO webfilter_rules (".@implode(",", $fields).") 
	VALUES (".@implode(",", $values).")";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$newruleid=$q->last_id;
	if($newruleid<1){echo "Failed";return;}
	
	$sql="SELECT * FROM webfilter_assoc_groups WHERE webfilter_id=$idfrom";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$groupid=$ligne["group_id"];
		$md5=md5("$newruleid$groupid");
		$sql="INSERT INTO webfilter_assoc_groups (zMD5,webfilter_id,group_id) VALUES('$md5','$newruleid','$groupid')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error;return;}
		
	}
	
	$sql="SELECT * FROM webfilter_blks WHERE webfilter_id=$idfrom";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$category=$ligne["category"];
		$category=addslashes($category);
		$q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,	modeblk,category) VALUES('$newruleid','{$ligne["modeblk"]}','$category')");	
		if(!$q->ok){echo $q->mysql_error;return;}		
		
	}
	$sql="SELECT * FROM webfilter_bannedexts WHERE ruleid=$idfrom";
	$results=$q->QUERY_SQL($sql);
	while($ligne=mysql_fetch_array($results,MYSQL_ASSOC)){
		$description=addslashes($ligne["description"]);
		$md5=md5("$newruleid{$ligne["ext"]}");
		$enabled=$ligne["enabled"];
		$q->QUERY_SQL("INSERT INTO webfilter_bannedexts (enabled,zmd5,ext,description,ruleid) VALUES($enabled,'$md5','{$ligne["ext"]}','$description',$newruleid);");	
		if(!$q->ok){echo $q->mysql_error;return;}	
	}
	
	$tpl=new templates();
	echo $tpl->javascript_parse_text("{success}");

}


