<?php
class ccurl{
	var $uri;
	var $parms=array();
	var $data='';
	var $NoHTTP_POST=false;
	var $error;
	var $ArticaProxyServerEnabled="no";
	var $ArticaProxyServerName='';
	var $ArticaProxyServerPort=3128;
	var $ArticaProxyServerUsername=null;
	var $ArticaProxyServerUserPassword=null;
	var $interface=null;
	var $noproxyload=false;
	var $authname=null;
	var $authpass=null;
	var $WgetBindIpAddress=null;
	var $FollowLocation=false;
	var $UserAgent=null;
	var $Timeout=120;
	var $error_num=0;
	
	// CURLOPT_INTERFACE
	
	function ccurl($uri=null,$noproxyload=false,$interface=null){
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$this->uri=$uri;
			$this->interface=$interface;
			$sock=new sockets();
			$this->WgetBindIpAddress=$sock->GET_INFO("WgetBindIpAddress");
			if(!$noproxyload){
				$ini=new Bs_IniHandler();
				$sock=new sockets();
				$datas=$sock->GET_INFO("ArticaProxySettings");
				if(trim($datas)<>null){
					$ini->loadString($datas);
					//if($GLOBALS["VERBOSE"]){echo "ArticaProxySettings: PROXY: ArticaProxyServerEnabled -> {$ini->_params["PROXY"]["ArticaProxyServerEnabled"]}\n";}
					$this->ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
					$this->ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
					$this->ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
					$this->ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
					$this->ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
				}
			}			
	}
	
	private function locate_wget(){
		if(is_file("/usr/bin/wget")){return "/usr/bin/wget";}
		
		
	}
	
	
	private function wget(){
		
		$cmds[]=$this->locate_wget();
		
		if(isset($this->parms)){
				if(count($this->parms)>0){
					while (list ($num, $ligne) = each ($this->parms)){$curlPost .='&'.$num.'=' . urlencode($ligne);}	
				}
			}
			
			if($this->interface<>null){$cmds[]="--bind-address=$this->interface";}else{
				if($this->WgetBindIpAddress<>null){$cmds[]="--bind-address=$this->WgetBindIpAddress";}
			}
			
			$cmds[]="-q --output-document=/tmp/artica-wget.txt";
			$cmd=@implode(" ", $cmds);
			if($GLOBALS["VERBOSE"]){writelogs($cmd,__CLASS__.'/'.__FUNCTION__,__FILE__);}
			shell_exec($cmd);
			$this->data=@file_get_contents("/tmp/artica-wget.txt");
		
		
	}
	
	
	
	function get(){
		if (!extension_loaded('curl')) {
			$this->wget();
			return;
		}
		
		
		$curlPost=null;
			if(isset($this->parms)){
				if(count($this->parms)>0){
					while (list ($num, $ligne) = each ($this->parms)){$curlPost .='&'.$num.'=' . urlencode($ligne);}	
				}
			}	
				
			$ch = curl_init();
			if($this->interface<>null){
				if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE $this->interface\n";}
				curl_setopt($ch, CURLOPT_INTERFACE,$this->interface);}
			else{
				
				if($this->WgetBindIpAddress<>null){
					if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE $this->WgetBindIpAddress\n";}
					curl_setopt($ch, CURLOPT_INTERFACE,$this->WgetBindIpAddress);
				}
			}
			curl_setopt($ch, CURLOPT_URL, "$this->uri");
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			if($this->NoHTTP_POST){
				//if($GLOBALS["VERBOSE"]){writelogs("Ban post protocol",__CLASS__.'/'.__FUNCTION__,__FILE__);}
				curl_setopt($ch, CURLOPT_POST, 0);
			}else{
				curl_setopt($ch, CURLOPT_POST, 1);
			}
			if($this->UserAgent<>null){
				curl_setopt($ch, CURLOPT_USERAGENT, $this->UserAgent);
			}else{
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");
			}
			
			if($this->FollowLocation){
				
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
			}
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
			
			if($this->authname<>null){
				if($GLOBALS["VERBOSE"]){writelogs("Enable website authentication has $this->authname",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				curl_setopt($ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");
			}else{
				//if($GLOBALS["VERBOSE"]){writelogs("no website authentication has enabled",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
			if($this->ArticaProxyServerEnabled=="yes"){
				//if($GLOBALS["VERBOSE"]){echo "Loading proxy settings...\n";}
				//curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				//if($GLOBALS["VERBOSE"]){echo "setting proxy for $this->ArticaProxyServerName:$this->ArticaProxyServerPort\n";}
				curl_setopt ($ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
				curl_setopt ($ch, CURLOPT_TIMEOUT, $this->Timeout);
				//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
				if($this->ArticaProxyServerUsername<>null){
					curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
				}
			}		
			
			
			
			if($curlPost<>null){
				if($GLOBALS["VERBOSE"]){echo "Add posts datas....\n";}
				curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
			}	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			$this->data=curl_exec($ch);
			$error=curl_errno($ch);		
			if($error>0){
				writelogs("datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			}
			curl_close($ch);
			
			
			if(!$this->ParseTextError()){return false;}
			
			
			return $this->ParseError($error);
	}
	
	public function ParseTextError(){
			if(strpos(strtolower($this->data), strtolower("<title>Artica-postfix 404</title>"))>0){
					$this->error="404 Not found ".strlen($this->data)." bytes length";
					writelogs("404 Not found !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->data=null;
					$this->error_num=404;
					return false;
				}
				
				if(strpos(strtolower($this->data), strtolower("<title>400 - Bad Request</title>"))>0){
					$this->error="400 Bad Request ".strlen($this->data)." bytes length";
					writelogs("400 Bad Request !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->data=null;
					$this->error_num=400;
					return false;				
					
				}
					
				if(strpos(strtolower($this->data), strtolower("<title>Access Denied"))>0){
					$this->error="Access denied ".strlen($this->data)." bytes length";
					writelogs("Access Denied !!",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
					$this->data=null;
					return false;
				}	
				if(strpos(strtolower($this->data), strtolower("<title>403 Forbidden"))>0){
					$this->error="403: Access denied ".strlen($this->data)." bytes length";
					if(preg_match("#please try again in\s+([0-9]+)\s+(.*?)[\.|\s+]#is", $this->data,$re)){
						$this->error="Error 403: Access denied please try again in {$re[1]} {$re[2]}";
					}
					$this->error_num=403;
					$this->data=null;
					return false;
				}
				
				 if(strpos(strtolower($this->data), strtolower("<title>500 - Internal Server Error"))>0){
					$this->error="500 - Internal Server Error ".strlen($this->data)." bytes length";
					$this->error_num=500;
					$this->data=null;
					return false;
				}
				
				

			return true;
		
	}
	
	
	public function ParseError($error){
		if($error>0){
			if($GLOBALS["VERBOSE"]){echo "ccurl/ParseError($error)\n";}
		}
			if($error==6){
				writelogs("error 6 Couldn't resolve host. The given remote host was not resolved",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_COULDNT_RESOLVE_HOST}";
				return false;
			}
			
			if($error==7){
				writelogs("error 7 Failed to connect() to host or proxy",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_COULDNT_CONNECT}";
				return false;
			}
			if($error==26){
				writelogs("Curl:: error 26 There was a problem reading a local file or an error returned by the read callback.",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_READ_ERROR}";
				return false;
			}
			if($error==56){
				writelogs("Curl:: error 56 Failure with receiving network data. ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_RECV_ERROR}";
				return false;
			}

			if($error==28){
				writelogs("Curl:: error 28 Operation timeout. The specified time-out period was reached according to the conditions. ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_OPERATION_TIMEDOUT}";
				return false;
			}
			
		return true;
		
		
	}
	
	
	function GetFile($targetpath){
		 if (!extension_loaded('curl') or ($ch = curl_init($this->uri)) === false) {echo "No curl extentions can be loaded\n";return;}
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
			curl_setopt($ch, CURLOPT_FILE, $fp = fopen($targetpath,'w'));
			curl_setopt($ch, CURLOPT_URL, "$this->uri");
			if(!is_numeric($this->Timeout)){$this->Timeout=120;}
			if($GLOBALS["DEBUG"]){
				curl_setopt($ch, CURLINFO_HEADER_OUT,TRUE);
			}else{
				curl_setopt($ch, CURLINFO_HEADER_OUT,FALSE);
			}
			
			//curl_setopt($ch, CURLOPT_NOBODY,true);
			
			if($this->interface<>null){
				if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE $this->interface\n";}
				curl_setopt($ch, CURLOPT_INTERFACE,$this->interface);}
			else{
				
				if($this->WgetBindIpAddress<>null){
					if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE $this->WgetBindIpAddress\n";}
					curl_setopt($ch, CURLOPT_INTERFACE,$this->WgetBindIpAddress);
				}
			}			
			
			if(preg_match("#https:#",$this->uri)){
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->Timeout);
			//$this->ArticaProxyServerEnabled=null;
			
			if($this->ArticaProxyServerEnabled=="yes"){
				//if($GLOBALS["VERBOSE"]){echo "Loading proxy settings...\n";}
				//curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				//if($GLOBALS["VERBOSE"]){echo "setting proxy for $this->ArticaProxyServerName:$this->ArticaProxyServerPort\n";}
				curl_setopt ($ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
				curl_setopt ($ch, CURLOPT_TIMEOUT, $this->Timeout);
				//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM);
				if($this->ArticaProxyServerUsername<>null){
					curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
				}
			}
			
			
			if($GLOBALS["VERBOSE"]){echo "Downloading \"$this->uri\"\n";}
			$result=curl_exec($ch); 
			fclose($fp);
			
			$error=curl_errno($ch);	
			curl_close($ch);
			if(!is_file($targetpath)){
				if($GLOBALS["VERBOSE"]){echo "path \"$targetpath\" no such file\n";}
			}
			
			
			if(!$this->ParseError($error)){
				writelogs("datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
				return false;
			}
			$filesize=filesize($targetpath);
			$filesizeText=$filesize;
			$filesizeUnit="Bytes";
			if($filesize>1024){$filesizeText=$filesize/1024;$filesizeUnit="Ko";}
			
			if($error>0){
				writelogs("datas: $this->uri (". strlen($result)." bytes) filesize=$filesizeText $filesizeUnit error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			}
			
			if($filesize<10000){
				$this->data=@file_get_contents($targetpath);
				if(!$this->ParseTextError()){$this->data=null;return false;}
				$this->data=null;
				return true;
			}	
			//@file_put_contents($this->data,$targetpath);
			return true;
	}
	
	
	function postFile($fieldname,$filepath,$array_fields=array()){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$this->uri");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
    curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));   
    // same as <input type="file" name="file_box">
    $post[$fieldname]="@$filepath";
    if(count($array_fields)>0){while (list ($num, $line) = each ($array_fields) ){$post[$num]=$line;}}
    
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post); 
    writelogs("Executing posting $fieldname/$filepath",__CLASS__.'/'.__FUNCTION__,__FILE__);
    $this->data = curl_exec($ch);

	$error=curl_errno($ch);		
			writelogs("datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			
			if($error==6){
				writelogs("error 6 ($this->uri) in protocol",__CLASS__.'/'.__FUNCTION__,__FILE__);
				curl_close($ch);
				return false;
			}
			
			
			curl_close($ch);
			return true;    
    
    
	}
	
	
	
	
}