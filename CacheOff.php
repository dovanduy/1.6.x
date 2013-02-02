<?php
session_start();
include_once('ressources/class.templates.inc');
include_once('ressources/class.ldap.inc');
include_once('ressources/class.user.inc');
include_once('ressources/class.langages.inc');
include_once('ressources/class.sockets.inc');

$GLOBALS["langs"]=array("fr","en","po","es","it");


if(function_exists("apc_clear_cache")){
	
	$apc_cache_info=apc_cache_info();
	$date=date('M d D H:i:s',$apc_cache_info["start_time"]);
	$cache_mb=FormatBytes(($apc_cache_info["mem_size"]/1024));
	$files=count($apc_cache_info["cache_list"]);	
	$text="{cached_files_number}:$files\n";
	$text=$text."{start_time}:$date\n";
	$text=$text."{mem_size}:$cache_mb\n";
	
	apc_clear_cache("user");
	apc_clear_cache();
}
		if(!isset($_SESSION["detected_lang"])){$_SESSION["detected_lang"]=$_COOKIE["artica-language"];}			
		$sock=new sockets();
		$sock->getFrameWork("squid.php?clean-catz-cache=yes");
	echo "\n";
	$cc=0;
	while (list ($num, $val) = each ($_SESSION)){
		if(preg_match("#\/.+?\.php$#", $num)){$cc++;unset($_SESSION[$num]);}
		
	}
	
	foreach (glob("/usr/share/artica-postfix/ressources/logs/web/cache/{$_SESSION["uid"]}.*") as $filename) {
		@unlink($filename);
		
	}
	
	
	
	if(class_exists("Memcache")){
		$memcache = new Memcache();
		$memcache->connect('unix:///var/run/memcached.sock', 0);
		$ARRAY=unserialize($memcache->get('ARTICACACHEARRAY'));
		$memcacheBytes=strlen(serialize($ARRAY));
		$memcache->set('ARTICACACHEARRAY', serialize(array()), 0, 300); 
		if($memcacheBytes>1024){
			$memcacheBytes=$memcacheBytes/1024;
			$memcacheBytes=FormatBytes($memcacheBytes/1024);
		}else{
			$memcacheBytes=$memcacheBytes." bytes";
		}

	}
	
	
	
	while (list ($num, $val) = each ($GLOBALS["langs"]) ){
		$datas=$sock->LANGUAGE_DUMP($val);
		$bb=strlen(serialize($datas));
		$a=$a+$bb;
		$bb=str_replace("&nbsp;"," ",FormatBytes($bb/1024));
		$tt[]="\tDumping language $val $bb";
	}	
	
	
			$dataSess=strlen(serialize($_SESSION));
			$bytes=$sock->SHARED_INFO_BYTES(3);
			$text=$text."Processes memory Cache............: ".str_replace("&nbsp;"," ",FormatBytes($bytes/1024))."/". str_replace("&nbsp;"," ",FormatBytes($sock->semaphore_memory/1024))."\n";
			$bytes=$sock->SHARED_INFO_BYTES(1);
			$text=$text."DATA Cache........................: ".str_replace("&nbsp;"," ",FormatBytes($bytes/1024))."/". str_replace("&nbsp;"," ",FormatBytes($sock->semaphore_memory/1024))."\n";
			
			$text=$text."Session Cache.....................: ".str_replace("&nbsp;"," ",FormatBytes($dataSess/1024))."\n";
			$text=$text."Session Page Cache................: $cc page(s)\n";
			
			
			$bytes=$a;
			$text=$text."Language Cache....................: ".str_replace("&nbsp;"," ",FormatBytes($bytes/1024))."/". str_replace("&nbsp;"," ",FormatBytes($sock->semaphore_memory/1024))."\n";
			$text=$text.implode("\n",$tt)."\n";
			$text=$text."Console Cache.....................: ".str_replace("&nbsp;"," ",FormatBytes(REMOVE_CACHED()))."\n";
			$text=$text."Mem Cached........................: ".str_replace("&nbsp;"," ",$memcacheBytes)."\n";
			
			
			
			$text=$text."\n\n{cache_cleaned}\n";
			$text=$text."language : {$_SESSION["detected_lang"]}\n";
			$text=$text."icons cache : ".count($_SESSION["ICON_MYSQL_CACHE"])."\n";
			$sock->DATA_CACHE_EMPTY();			
			
		
			writelogs("Clean cache, language was {$_SESSION["detected_lang"]}",__FUNCTION__,__FILE__,__LINE__);	
			unset($_SESSION["CACHE_PAGE"]);			
			unset($_SESSION["APC"]);
			unset($_SESSION["cached-pages"]);
			unset($_SESSION["translation-en"]);
			unset($_SESSION["translation"]);
			unset($_SESSION["privileges"]);
			unset($_SESSION["qaliases"]);
			unset($_SERVER['PHP_AUTH_USER']);
			unset($_SESSION["ARTICA_HEAD_TEMPLATE"]);
			unset($_SESSION['smartsieve']['authz']);
			unset($_SESSION["passwd"]);
			unset($_SESSION["LANG_FILES"]);
			unset($_SESSION["TRANSLATE"]);
			unset($_SESSION["__CLASS-USER-MENUS"]);
			unset($_SESSION["translation"]);
			unset($_SESSION["ICON_MYSQL_CACHE"]);
			unset($_SESSION["CATZ"]);
			unset($_SESSION[md5("statusPostfix_satus")]);
			@unlink("ressources/logs/postfix.status.html");

			$workdir="/usr/share/artica-postfix/ressources/logs/web";
			$ToDelete["admin.index.tabs.html"]=true;
			$ToDelete["admin.index.memory.html"]=true;
			$ToDelete["admin.index.notify.html"]=true;
			$ToDelete["admin.index.quicklinks.html"]=true;
			$ToDelete["logon.html"]=true;
			$ToDelete["traffic.statistics.html"]=true;
			while (list ($filename, $val) = each ($ToDelete) ){@unlink("$workdir/$filename");}

			include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
			$q=new mysql_squid_builder();
			$q->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");
			$q=new mysql();
			$q->QUERY_SQL("UPDATE setup_center SET CODE_NAME_STRING='',CODE_NAME_ABOUT=''",'artica_backup');


			$tpl=new templates();
			$html=$tpl->javascript_parse_text($text,1);
			$html=str_replace("\n", "<br>", $html);
			echo "<div class=explain style='font-size:14px'>".$html."</div>";
			
			
		

?>