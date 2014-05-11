<?php

class haarp{
	private $BySquid=false;
	
	function haarp($BySquid=false){
		$this->BySquid=$BySquid;
		
		$this->checksTables();
	}
	
	
	
	private function checksTables(){
		$q=new mysql_squid_builder();
		
		$sql="CREATE TABLE IF NOT EXISTS `haarp_redirpats` (
				`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`pattern` VARCHAR(255) NOT NULL,
				UNIQUE KEY `pattern` (`pattern`)
				) ENGINE = MYISAM;
				";
		
		
		$q->QUERY_SQL($sql);
		if(!$q->ok){
			if($this->BySquid){echo "Starting......: ".date("H:i:s")." [HAA]: FATAL (".__CLASS__.") $q->mysql_error $sql\n";}
			writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;
		}		
		
		$sql="CREATE TABLE IF NOT EXISTS `haarp_caches` (
				`ID` INT( 5 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`directory` VARCHAR(255) NOT NULL,
				`size` BIGINT(128) NOT NULL,
				 UNIQUE KEY `directory` (`directory`)
				) ENGINE = MYISAM;
				";		
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if($this->BySquid){echo "Starting......: ".date("H:i:s")." [HAA]: FATAL (".__CLASS__.") $q->mysql_error $sql\n";}
			writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;
		}
		
		if($q->COUNT_ROWS("haarp_redirpats")==0){
			if($this->BySquid){echo "Starting......: ".date("H:i:s")." [HAA]: (".__CLASS__.") building default rules...\n";}
			$f[]="http.*\.4shared\.com.*(\.exe|\.iso|\.torrent|\.zip|\.rar|\.pdf|\.doc|\.tar|\.mp3|\.mp4|\.avi|\.wmv)";
			$f[]="http.*\.avast\.com.*(\.def|\.vpu|\.vpaa|\.stamp)";
			$f[]="http.*(\.avg\.com|\.grisoft\.com|\.grisoft\.cz).*(\.bin|\.exe)";
			$f[]="http.*(\.avgate\.com|\.avgate\.net|\.freeav\.net|\.freeav\.com).*(\.gz)";
			$f[]="http.*\.bitgravity\.com.*(\.flv\.mp4)";
			$f[]="http.*\.etrustdownloads\.ca\.com.*(\.tar|\.zip|\.exe|\.pkg)";
			$f[]="http.*flashvideo\.globo\.com.*(\.mp4|\.flv)";
			$f[]="http.{1,4}vsh\.r7\.com\/.*(\.mp4)\$";
			$f[]="74\.125\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)";
			$f[]="http.*(\.kaspersky-labs\.com|\.geo\.kaspersky\.com|kasperskyusa\.com).*(\.avc|\.kdc|\.klz|\.bz2|\.dat|\.dif)";
			$f[]="http.*\.metacafe\.com.*\.flv";
			$f[]="http.{1,4}media\w*\.justin.tv\/archives\/(\w|\/|-)*\.flv(\?.*|\$)";
			$f[]="http.{1,4}\w*juegos\w*\.juegosdiarios\.com\/(\w|\/|-)*\.swf\$";
			$f[]="http.{1,4}\w*\.juegosjuegos\.com\/games(\w|\/|-)*\.swf\$";
			$f[]="http.*(\.windowsupdate\.com|(update|download|dlservice|windowsupdate)\.microsoft\.com)\/.*(\.cab|\.exe|\.iso|\.zip|\.psf|\.txt|\.crt)\$";
			$f[]="http.*\.pornotube\.com.*\.flv";
			$f[]="http.*\.terra\.com.*\.flv";
			$f[]="http.*(porn|img).*\.xvideos\.com\/videos\/(thumbs\/)?.*(\.jpg|\.flv\?.*|\.mp4\?.*)\$";
			$f[]="http.*\.youtube\.com.*videoplayback\?";
			$f[]="http.*\.ziddu\.com.*(\.exe|\.iso|\.torrent|\.zip|\.rar|\.pdf|\.doc|\.tar|\.mp3|\.mp4|\.avi|\.wmv)";
			$f[]="http.*edgecastcdn\.net/.*(\.mp4|\.flv)";
			$f[]="http.*adobe\.com/.*(\.cab|\.aup|\.exe|\.msi|\.upd|\.msp)";
			$f[]="http.*\.eset\.com.*\.nup";
			$f[]="http.*\.nai\.com.*(\.zip|\.tar|\.exe|\.gem)";
			$f[]="http.*\.pop6\.com.*(\.flv)";
			$f[]="http.*\.symantecliveupdate\.com.*(\.zip|\.exe)";
			$f[]="http.*\.xpg\.com\.br.*";
			$f[]="http.{1,4}\w*\.ytimg\.com.*(hqdefault(\.jpg|\.mp4)\$|M[0-9]+\.jpg\?sigh=)";
			$f[]="http.{1,4}\w*google(\.\w|\w)*\.doubleclick\.net\/pagead\/ads\?.*";
			$f[]="http.*img[0-9]\.submanga\.com\/(hd)?pages\/.*(\.jpg|\.webp)";
			$f[]="http.*(profile|s?photos|video).{0,5}\.ak\.fbcdn\.net\/.*(\.mp4\?.*|\_n\.jpg\$|\.mp4\$|\_n\.png\$)";
			$f[]="http.*\.video\.pornhub\.\w*\.com\/videos\/.*\.flv\?.*";
			$f[]="http.*\.(publicvideo|publicphoto)\.xtube\.com\/(videowall\/)?videos?\/.*(\.flv\?.*|\_Thumb\.flv\$)";
			$f[]="http.*public\.tube8\.com\/.*\.mp4.*";
			$f[]="http.*videos\..*\.redtubefiles\.com\/.*\.flv";
			$f[]="http.*\.vimeo.com\/.*\.mp4(\?.*)?\$";
			$f[]="http.*images\.orkut\.com\/orkut\/photos\/.*\.jpg\$";
			$f[]="http.{1,4}(\w|\/|\.|-)*media\.tumblr\.com\/(\w|\/|-|\.)*tumblr(\w|\/|-)*(\.png|\.jpg)\$";
			
			$prefix="INSERT IGNORE INTO haarp_redirpats (`pattern`) VALUES ";
			
			while (list ($index, $line) = each ($f) ){
				$line=$this->mysql_escape_string2($line);
				$N[]="('$line')";
				
				
			}
			
			$q->QUERY_SQL($prefix.@implode(",", $N));
			if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		}
		
		if($q->COUNT_ROWS("haarp_caches")==0){
			$sql="INSERT IGNORE INTO haarp_caches (`directory`) VALUES ('/home/haarp-1')";
			$q->QUERY_SQL($sql);
			if(!$q->ok){writelogs("$q->mysql_error",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);return;}
		}
		
		
	}
	
	private function mysql_escape_string2($line){
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search,$replace,$line);
		if(function_exists("mysql_real_escape_string")){
			return mysql_real_escape_string($line);
		}
		return mysql_escape_string($line);
	
	}
	
	
}