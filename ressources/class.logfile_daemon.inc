<?php
class logfile_daemon{
	
	public function GetYoutubeID($url){
		$Youtuberegex[]="youtube.*?stream.*?&v=(.+?)&fmt";
		$Youtuberegex[]="youtube.*?ptracking.*?&video_id=(.+?)$";
		$Youtuberegex[]="youtube.*?user_watch.*?&video_id=(.+?)&";
		$Youtuberegex[]="youtube.com.*?set_awesome.*?&video_id=(.+?)&";
		$Youtuberegex[]="youtube.*?watchtime.*?&docid=(.+?)&";
		$Youtuberegex[]="youtube\..*?\/watch\?v=(.+?)\&";
		while (list ($index, $regex) = each ($Youtuberegex) ){
			if(preg_match("#$regex#", $url,$re)){
				$youtubeid=$re[1];
				if(strpos($youtubeid, "&")>0){
					$tt=explode("&",$youtubeid);
					$this->events("YOUTUBE: $youtubeid = " .$tt[0]);
					$youtubeid=$tt[0];
				}
				return $youtubeid;
			}
		}
	
	}
	
	private function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/squid/logfile_daemon.debug";
	
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$pid `$text`\n");
		@fclose($f);
	}
	
	public function SearchWords($uri){
		$sitename=null;
		if(preg_match("#google\.[a-z\.]+\/search.+?q=(.+?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#yahoo\..*?p=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#bing\..*?search\?q=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#baidu\..*?\/s\?wd=(.*?)&#i",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#yandsearch\?text=(.*?)&#",$uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#amazon.*?field-keywords=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;		}
		if(preg_match("#ebay.*?_nkw=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#wikipedia\..*?search=(.*?)&#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#duckduckgo.*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#ask\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#clusty\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#mahalo\..*?q=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#youtube\..*?query=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#deezer.*?\/search\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#priceminister.+?\/s\/(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		if(preg_match("#leboncoin.+?q=(.*?)($|&|\/)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[1]);$searchWords["SITENAME"]=$sitename;}
		
		if(!isset($searchWords["WORDS"])){
			if(preg_match("#\..*?\?(q|queryString|query|search|recherche|find|searchstring|keywords)=(.*?)($|&)#", $uri,$re)){$searchWords["WORDS"]=urldecode($re[2]);$searchWords["SITENAME"]=$sitename;}
		}
		if(isset($searchWords["WORDS"])){
			if(trim($searchWords["WORDS"]=="query")){unset($searchWords["WORDS"]);}
			if(is_numeric($searchWords["WORDS"])){return;}
			$searchWords["WORDS"]=str_replace('"', "`", $searchWords["WORDS"]);
			$searchWords["WORDS"]=str_replace("'", "`", $searchWords["WORDS"]);
		}
		
		if(isset($searchWords["WORDS"])){
			if(preg_match("#gstatic\.[a-z\.]+$#",$searchWords["SITENAME"])){if(preg_match("#^tbn:.*?#", $searchWords["WORDS"])){unset($searchWords["WORDS"]);}}
		}
		
		if(!isset($searchWords["WORDS"])){return null;}
		return $searchWords;
		
		
	}
	
	
	public function codeToString($code){
		$squid_error["000"]="None";
		$squid_error["100"]="Continue";
		$squid_error["101"]="Switching Protocols";
		$squid_error["102"]="Processing";
		$squid_error["200"]="Pass";
		$squid_error["201"]="Created";
		$squid_error["202"]="Accepted";
		$squid_error["203"]="Non-Authoritative Information";
		$squid_error["204"]="No Content";
		$squid_error["205"]="Reset Content";
		$squid_error["206"]="Partial Content";
		$squid_error["207"]="Multi Status";
		$squid_error["300"]="Multiple Choices";
		$squid_error["301"]="Moved Permanently";
		$squid_error["302"]="Moved Temporarily";
		$squid_error["303"]="See Other";
		$squid_error["304"]="Not Modified";
		$squid_error["305"]="Use Proxy";
		$squid_error["307"]="Temporary Redirect";
		$squid_error["400"]="Bad Request";
		$squid_error["401"]="Unauthorized";
		$squid_error["402"]="Payment Required";
		$squid_error["403"]="Forbidden";
		$squid_error["404"]="Not Found";
		$squid_error["405"]="Method Not Allowed";
		$squid_error["406"]="Not Acceptable";
		$squid_error["407"]="Proxy Authentication Required";
		$squid_error["408"]="Request Timeout";
		$squid_error["409"]="Conflict";
		$squid_error["410"]="Gone";
		$squid_error["411"]="Length Required";
		$squid_error["412"]="Precondition Failed";
		$squid_error["413"]="Request Entity Too Large";
		$squid_error["414"]="Request URI Too Large";
		$squid_error["415"]="Unsupported Media Type";
		$squid_error["416"]="Request Range Not Satisfiable";
		$squid_error["417"]="Expectation Failed";
		$squid_error["424"]="Locked";
		$squid_error["424"]="Failed Dependency";
		$squid_error["433"]="Unprocessable Entity";
		$squid_error["500"]="Internal Server Error";
		$squid_error["501"]="Not Implemented";
		$squid_error["502"]="Bad Gateway";
		$squid_error["503"]="Service Unavailable";
		$squid_error["504"]="Gateway Timeout";
		$squid_error["505"]="HTTP Version Not Supported";
		$squid_error["507"]="Insufficient Storage";
		$squid_error["600"]="Squid header parsing error";
		return $squid_error[$code];
	}
	
	
	public function CACHEDORNOT($SquidCode){
		if(preg_match("#(.+?):(.+)#", $SquidCode,$re)){
			$SquidCode=$re[1];
		}
		
		if(preg_match("#_HIT$#", $SquidCode)){return 1;}
		
		switch ($SquidCode) {
	
			case "TCP_HIT":
	
			case "TCP_REFRESH_UNMODIFIED":
	
			case "TCP_REFRESH_HIT":
	
			case "TCP_REFRESH_FAIL_HIT":
	
			case "TCP_REFRESH_MISS":
	
			case "TCP_IMS_HIT":
	
			case "TCP_MEM_HIT":
	
			case "TCP_IMS_MISS":
	
			case "TCP_OFFLINE_HIT":
	
			case "TCP_STALE_HIT":
	
			case "TCP_ASYNC_HIT":
	
			case "UDP_HIT":
	
			case "UDP_DENIED":
	
			case "UDP_INVALID":
	
				return 1;
	
				break;
	
			default:
	
				return 0;
	
				break;
	
		}
	
	}
	
}