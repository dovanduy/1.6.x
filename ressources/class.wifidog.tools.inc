<?php



function generateToken($MAC=null) {
	if(isset($GLOBALS["generateToken"])){return $GLOBALS["generateToken"];}
	if($MAC<>null){
		$sock=new sockets();
		$sock->getFrameWork("hotspot.php?wifidog-check-status=yes");
		$MAIN=unserialize(@file_get_contents("/usr/share/artica/postfix/ressources/logs/web/wifidog.status"));
		if(isset($MAIN["SESSIONS"][$MAC])){
			$GLOBALS["generateToken"]=$MAIN["SESSIONS"][$MAC];
			if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("**** OLD TOKEN {$GLOBALS["generateToken"]}");}
			return $GLOBALS["generateToken"];
		}
	}

	$GLOBALS["generateToken"]= md5(uniqid(rand(), 1));
	if($GLOBALS["HOTSPOT_DEBUG"]){wifidog_logs("**** New Token: {$GLOBALS["generateToken"]}");}
	return $GLOBALS["generateToken"];
}

function wifidog_build_uri(){
	reset($_REQUEST);
	
	if(isset($_REQUEST["ruleid"])){$_SESSION["WIFIDOG_RULES"]=$_REQUEST["ruleid"];}
	
	while (list ($num, $ligne) = each ($_REQUEST) ){
		if($num=="wifidog-login"){continue;}
		if($num=="wifidog-register"){continue;}
		if($num=="register-member"){continue;}
		if($num=="wifidog-recover"){continue;}
		if($num=="register-recover"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="wifidog-password"){continue;}
		if($num=="confirm-password"){continue;}
		if($num=="wifidog-terms"){continue;}
		$URIZ[]="$num=".urlencode($ligne);
		$inputz[]="<input type='hidden' id='$num' name='$num' value='$ligne'>";

	}
	
	
	if(!isset($_SESSION["WIFIDOG_RULES"])){
		$wifidog_templates=new wifidog_rules();
		$_SESSION["WIFIDOG_RULES"]=$wifidog_templates->ruleid;
	}
	$inputz[]="<input type='hidden' id='ruleid' name='ruleid' value='{$_SESSION["WIFIDOG_RULES"]}'>";
	$URIZ[]="ruleid={$_SESSION["WIFIDOG_RULES"]}";

	return array(@implode("&", $URIZ),@implode("\n", $inputz));

}

function wifidog_logs($text,$function=null,$line=0){
	
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
	
		if(isset($trace[0])){
			$file=basename($trace[0]["file"]);
			if($function==null){$function=$trace[0]["function"];}
			if($line==0){$line=$trace[0]["line"];}
		}
	

	
	
	}
	
	
	
	$date=date("Y-m-d H:i:s");
	$logFile="/var/log/artica-wifidog.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$date [$file/$function/$line]: $text\n");
	@fclose($f);
	
	
	
}