<?php
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	include_once("ressources/class.templates.inc");
	include_once("ressources/class.ldap.inc");
	
	
	$user=new usersMenus();
	if($user->AsPostfixAdministrator==false){
		$tpl=new templates();
		echo "alert('". $tpl->javascript_parse_text("{ERROR_NO_PRIVS}")."');";
		die();exit();
	}
	
	if(isset($_GET["popup"])){popup();exit;}
	if(isset($_POST["smtp_events_backup_dir"])){perform_backup();exit;}
	if(isset($_POST["reset-table"])){perform_reset();exit;}
js();

function js(){
	$page=CurrentPageName();
	$tpl=new templates();
	
	$title=$tpl->_ENGINE_parse_body("{smtp_events_table}");
	echo "YahooWin3('550','$page?popup=yes','$title')";
}


function popup(){
	$page=CurrentPageName();
	$tpl=new templates();
	$q=new mysql();
	$table_smtp_logs=$q->COUNT_ROWS("smtp_logs","artica_events");
	$reset_smtp_logs_ask=$tpl->javascript_parse_text("{reset_smtp_logs_ask}");
	$t=time();	
	$html="
	<div id='a$t'></div>
	<div style='font-size:14px' class=explain>{smtp_events_database_explain}</div>
	
	
	<table style='width:99%' class=form>
	<tr>
		<td class=legend style='font-size:14px'>{backup_table_to}:</td>
		<td>". Field_text("smtp_events_backup_dir","/home/server/smtp-logs-backup","font-size:14px;width:220px")."</td>
		<td width=1%><input type='button' OnClick=\"Loadjs('SambaBrowse.php?no-shares=yes&field=cache_directory')\" value='{browse}...' style='font-size:14px'></td>
	</tr>
	<tr>
		<td colspan=3 align='right'><hr>". button("{backup}", "BackupPerformSmtpLogs()",16)."</td>
	</tr>
	</table>
	
	<table style='width:99%' class=form>
	<tr>
		<td align='right'><hr>". button("{reset_table}", "ResetSmtpLogs()",16)."</td>
	</tr>
	</table>	
	
	<script>
	var x_BackupPerformSmtpLogs=function(obj){
	      var tempvalue=obj.responseText;
		  alert(tempvalue);
		  document.getElementById('a$t').innerHTML='';
	      }


		function BackupPerformSmtpLogs(){
			var XHR = new XHRConnection();
			var TargetDir=document.getElementById('smtp_events_backup_dir').value;
			AnimateDiv('a$t');
			XHR.appendData('smtp_events_backup_dir',TargetDir);
			XHR.sendAndLoad('$page', 'POST',x_BackupPerformSmtpLogs);
		
		}
		
		function ResetSmtpLogs(){
			if(confirm('$reset_smtp_logs_ask')){
				var XHR = new XHRConnection();
				AnimateDiv('a$t');
				XHR.appendData('reset-table','yes');
				XHR.sendAndLoad('$page', 'POST',x_BackupPerformSmtpLogs);
			}		
		}
		
</script>	
	
	";
	
	echo $tpl->_ENGINE_parse_body($html);
	
	
}

function perform_backup(){
	$sock=new sockets();
	$q=new mysql();
	$PARAMS["ROOT"]=$q->mysql_admin;
	$PARAMS["PASS"]=$q->mysql_password;
	$PARAMS["HOST"]=$q->mysql_server;
	$PARAMS["PORT"]=$q->mysql_port;
	$PARAMS["DB"]="artica_events";
	$PARAMS["PATH"]=$_POST["smtp_events_backup_dir"];
	$PARAMS["TABLE"]="smtp_logs";
	
	$PARAMSX=base64_encode(serialize($PARAMS));

	
	
	echo base64_decode($sock->getFrameWork("mysql.php?backuptable=$PARAMSX"));

	$PARAMS["TABLE"]="smtp_logs_day";	
	$PARAMSX=base64_encode(serialize($PARAMS));
	echo base64_decode($sock->getFrameWork("mysql.php?backuptable=$PARAMSX"));
	
	
	
}

function perform_reset(){
	$q=new mysql();
	$table_smtp_logs=$q->COUNT_ROWS("smtp_logs","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `smtp_logs`","artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	$table_smtp_logs2=$q->COUNT_ROWS("smtp_logs","artica_events");
	echo $tpl->javascript_parse_text("{success} smtp_logs: $table_smtp_logs -> $table_smtp_logs2 {rows}")."\n";
	
	$table_smtp_logs=$q->COUNT_ROWS("smtp_logs_day","artica_events");
	$q->QUERY_SQL("TRUNCATE TABLE `smtp_logs_day`","artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}
	$tpl=new templates();
	$table_smtp_logs2=$q->COUNT_ROWS("smtp_logs_day","artica_events");
	echo $tpl->javascript_parse_text("{success}: smtp_logs_day $table_smtp_logs -> $table_smtp_logs2 {rows}")."\n";	
}