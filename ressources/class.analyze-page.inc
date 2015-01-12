<?php

class analyze_page{
	var $url;
	var $results=array();
	public $CURL_ALL_INFOS;
	public $CURLINFO_HTTP_CODE;
	public function analyze_page($url=null,$referer=null){
		if($url<>null){$this->url=$url;}
		
		
	}
	
	
	public function parse(){
		$q=new mysql_squid_builder();
		$AR=parse_url($this->url);
		$hostname=$AR["host"];
		$family=$q->GetFamilySites($hostname);
		$this->results[$family]=$hostname;
		
		
		$var = $this->fread_url();
		preg_match_all ("/a[\s]+[^>]*?href[\s]?=[\s\"\']+".
				"(.*?)[\"\']+.*?>"."([^<]+|.*?)?<\/a>/",
				$var, $matches);
		
		$matches = $matches[1];
		$list = array();
		
		$this->results=array();
		foreach($matches as $var){
			$res[]=$var;
		}
		
		
		foreach($res as $url){
			if(!preg_match("#^http.*?:#", $url)){continue;}
			$AR=parse_url($url);
			if(!isset($AR["host"])){continue;}
			$hostname=$AR["host"];
			$family=$q->GetFamilySites($hostname);
			$this->results[$family]=true;
			
		}
		
		
		
	}
	
	
	private function fread_url(){
		$cookie=dirname(__FILE__)."/web/logs/cookie.txt";
		$url=$this->url;
		$ref=null;
		if(!function_exists("curl_init")){$this->error=true;}
			$ch = curl_init();
			$user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt( $ch, CURLOPT_HTTPGET, 1 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION , 1 );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION , 1 );
			curl_setopt( $ch, CURLOPT_URL, $url );
			if($ref<>null){
				curl_setopt( $ch, CURLOPT_REFERER, $ref );
			}
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie);
			$html = curl_exec($ch);
			$this->CURL_ALL_INFOS=curl_getinfo($ch);
			$this->CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
			$error=curl_errno($ch);
			if($GLOBALS["VERBOSE"]){echo "$error CODE:$this->CURLINFO_HTTP_CODE<br>\n";}
			curl_close($ch);
			return $html;
	}
	
	
}



// The fread_url function allows you to get a complete
// page. If CURL is not installed replace the contents with
// a fopen / fget loop


