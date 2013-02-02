<?php

class accesslogs{
	Public $stats=true;
	
	
	function accesslogs(){}
	
	public function Buildline($array){
		$date=$array["DATE"];
		$mac=$array["MAC"];
		$user=$array["USER"];
		$proto=$array["PROTO"];
		$return_code=$array["CODE"];
		$size=$array["SIZE"];
		$line=$array["LINE"];
		$uri=$array["URI"];
		$ip=$array["IP"];
		$today=date("Y-m-d");
		$proxy=null;
		
		if(!isset($GLOBALS["Q"])){$GLOBALS["Q"]=new mysql_squid_builder();}
		
		if(preg_match('#Forwarded:"(.*?)"#', $line,$re)){
			if($re[1]<>"-"){$ip=$re[1];}
		}
		if(isset($array["PROXY"])){
	
	
			if(!isset($GLOBALS["IPPROXY"][$array["PROXY"]])){
				$ipprox=gethostbyname($array["PROXY"]);
				$GLOBALS["IPPROXY"][$array["PROXY"]]=$ipprox;
			}else{
				$ipprox=$GLOBALS["IPPROXY"][$array["PROXY"]];
			}
	
			$proxy=$array["PROXY"]."[$ipprox]&nbsp;,";
		}
		$ident[]=$ip;
	
		if($user=="-"){$user=null;}
		$http_status_codes=$this->http_status_codes();
	
		if(preg_match("#(TCP_DENIED|ERR_CONNECT_FAIL)#", $line)){
	
			$color="#BA0000";
			$return_code_text="<div style='color:$color;font-size:11px'><i>$proxy&laquo;($return_code) TCP_DENIED/ERR_CONNECT_FAIL&raquo;$line</i></div>";
	
		}
		if($return_code>399){$color="#D60808";$return_code_text="<div style='color:$color;font-size:11px'><i>$proxy&laquo;{$http_status_codes[$return_code]}&raquo;</i></div>";}
	
		if($return_code<399){
			if(isset($array["PROXY"])){
				if(!isset($GLOBALS["IPPROXY"][$array["PROXY"]])){
					$ipprox=gethostbyname($array["PROXY"]);
					$GLOBALS["IPPROXY"][$array["PROXY"]]=$ipprox;
				}else{
					$ipprox=$GLOBALS["IPPROXY"][$array["PROXY"]];
				}
				$return_code_text="<div style='font-size:11px;color:$color;'><i>{$array["PROXY"]}[$ipprox]</i></div>";
			}
		}
	
		if($user<>null){
			$GLOBALS["IPUSERS"][$ip]=$user;
		}else{
			if(isset($GLOBALS["IPUSERS"][$ip])){
				$ident[]="<i>{$GLOBALS["IPUSERS"][$ip]}</i>";
			}
		}
	
	
		$host=$GLOBALS["Q"]->GetFamilySites($this->GetDomainFromURl($uri));
		if($host=="cache_object"){return null;}
	
		if($this->stats){
		$uri=str_replace($host, "<a href=\"javascript:blur()\"
				OnClick=\"javascript:Loadjs('squid.traffic.statistics.days.php?today-zoom=yes&type=req&familysite=$host&day=$today');\"
				style='text-decoration:underline;color:$color'>$host</a>", $uri);
		}
		$spanON="<span style='color:$color'>";
		$spanOFF="</span>";
		if($mac=="00:00:00:00:00:00"){$mac=null;}
	
		if($user<>null){$ident[]=$user;}
	
		if($mac<>null){
			$ident[]=$mac;
			$mac="<a href=\"javascript:blur()\"
			OnClick=\"javascript:Loadjs('squid.nodes.php?node-infos-js=yes&MAC=$mac');\"
			style='text-decoration:underline;color:$color'>$mac</a>";
				
		}
	
		return array('id' => md5($line),
				'cell' => array("$spanON$date$spanOFF", "$spanON$proto$spanOFF","$spanON$uri.$return_code_text$spanOFF","$spanON".@implode("&nbsp;|&nbsp;", $ident)."$spanOFF")
		);
	
	
	}
	
	private function http_status_codes(){
		return array(0=>"Used mostly with UDP traffic.",
				100=>"Continue",
				101=>"Switching Protocols",
				102=>"Processing",
				200=>"OK",
				201=>"Created",
				202=>"Accepted",
				203=>"Non-Authoritative Information",
				204=>"No Content",
				205=>"Reset Content",
				206=>"Partial Content",
				207=>"Multi Status",
				300=>"Multiple Choices",
				301=>"Moved Permanently",
				302=>"Moved Temporarily",
				303=>"See Other",
				304=>"Not Modified",
				305=>"Use Proxy",
				307=>"Temporary Redirect",
				400=>"Bad Request",
				401=>"Unauthorized",
				402=>"Payment Required",
				403=>"Forbidden",
				404=>"Not Found",
				405=>"Method Not Allowed",
				406=>"Not Acceptable",
				407=>"Proxy Authentication Required",
				408=>"Request Timeout",
				409=>"Conflict",
				410=>"Gone",
				411=>"Length Required",
				412=>"Precondition Failed",
				413=>"Request Entity Too Large",
				414=>"Request URI Too Large",
				415=>"Unsupported Media Type",
				416=>"Request Range Not Satisfiable",
				417=>"Expectation Failed",
				422=>"Unprocessable Entity",
				424=>"Locked",
				424=>"Failed Dependency",
				433=>"Unprocessable Entity",
				500=>"Internal Server Error",
				501=>"Not Implemented",
				502=>"Bad Gateway",
				503=>"Service Unavailable",
				504=>"Gateway Timeout",
				505=>"HTTP Version Not Supported",
				507=>"Insufficient Storage",
				600=>"Squid: header parsing error",
				601=>"Squid: header size overflow detected while parsing",
				601=>"roundcube: software configuration error",
				603=>"roundcube: invalid authorization");
	}	
	
	private function GetDomainFromURl($myurl){
		$raw_url = parse_url($myurl);
		$domain_only =str_replace ('www.','', $raw_url);
		return $domain_only['host'];
	
	}	
	
}