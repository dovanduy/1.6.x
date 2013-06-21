<?php
define ('CURLOPTTYPE_OBJECTPOINT ',  10000);
define ('CURLOPT_NOPROXY',CURLOPTTYPE_OBJECTPOINT + 177);
class ccurl{
	var $uri;
	var $UseDirect=false;
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
	var $WriteProgress=false;
	var $DebugProgress=true;
	var $ProgressFile=null;
	var $ProgressFunction=null;
	var $x_www_form_urlencoded=true;
	var $CURLOPT_NOPROXY="localhost,127.0.0.1";
	var $CURLOPT_HEADER=true;
	var $CURLINFO_HTTP_CODE=0;
	private $called=null;
	private $headers=null;
	
	// CURLOPT_INTERFACE
	
	function ccurl($uri=null,$noproxyload=false,$interface=null){
			$trace=@debug_backtrace();if(isset($trace[1])){$this->called="called by source ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$this->uri=$uri;
			$this->interface=$interface;
			$sock=new sockets();
			if(!isset($GLOBALS["WgetBindIpAddress"])){
				$GLOBALS["WgetBindIpAddress"]=$sock->GET_INFO("WgetBindIpAddress");
			}
			$this->WgetBindIpAddress=$GLOBALS["WgetBindIpAddress"];
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
	
	private function OutPutlogs($text){
		if($GLOBALS["VERBOSE"]){echo "$text\n";}
		if(!isset($GLOBALS["CURLDEBUG"])){$GLOBALS["CURLDEBUG"]=array();}
		$GLOBALS["CURLDEBUG"][]=$text;
	}
	
	
	private function getdirect_HTTP_Request2(){
	
				$config = array(
						'adapter'           => 'HTTP_Request2_Adapter_Socket',
						'connect_timeout'   => $this->Timeout,
						'timeout'           => 0,
						'use_brackets'      => true,
						'protocol_version'  => '1.1',
						'buffer_size'       => 16384,
						'store_body'        => true,				
						'ssl_verify_peer'   => true,
						'ssl_verify_host'   => true,
						'ssl_cafile'        => null,
						'ssl_capath'        => null,
						'ssl_local_cert'    => null,
						'ssl_passphrase'    => null,
				
						'digest_compat_ie'  => false,
				
						'follow_redirects'  => false,
						'max_redirects'     => 5,
						'strict_redirects'  => false
				);
				
				$request = new Http_Request2($this->uri);
				$request->setConfig($config);
				
				
				try {
					$response = $request->send();
				} catch (Exception $exc) {
					$es = $exc->getTraceAsString();
					$ets=$exc->__toString();
					$egc=$exc->getCode();
					$egl=$exc->getLine();
					$egm=$exc->getMessage();
					$egt=$exc->getTrace();
					$response = null;
					$this->error="$es $ets $egc $egl $egm $egt";
					return false;
					
				}
				$this->data = $response->getBody();	
				return true;			
			
	
}

	private function pear_install_HTTP_Request2(){
		if(!$GLOBALS["AS_ROOT"]){return false;}
		$unix=new unix();
		$pear=$unix->find_program("pear");
		if(!is_file($pear)){return;}
		$hohup=$unix->find_program("nohup");
		shell_exec("$hohup $pear install HTTP_Request2 >/dev/null 2>&1 &");
	}
	
	
	private function getdirect(){
		include_once('HTTP/Request2.php');
		if(!class_exists("Http_Request2")){$this->pear_install_HTTP_Request2();}
		if(class_exists("Http_Request2")){return $this->getdirect_HTTP_Request2();}
		//$auth = base64_encode('user:password');
		//$header = array("Authorization: Basic $auth");
		$socket=array();
		
		
		if($this->interface<>null){
			$this->OutPutlogs("getdirect() -> Interface:$this->interface");
			$socket=array('socket' => array('bindto' => "$this->interface:0"));
		}
		$opts = array('http' =>
			  array(
			    'method'  => 'GET',
			    'header'=>"Content-Type: text/html; charset=utf-8",
			    //  "Authorization: Basic ".base64_encode("$https_user:$https_password")."\r\n",
			  	$socket,
			    'timeout' => $this->Timeout
			  )
			);
		$context = stream_context_create($opts);
		$this->OutPutlogs("getdirect() -> $this->uri");
		

		try {
			$result =file_get_contents($this->uri,false,$context);
		}
		catch (Exception $e) {
			
			$this->error=$e->getMessage();
			$this->OutPutlogs("getdirect() -> $this->error");
			return false;
		}
		
		
		
		if(!$result){
			$this->error=$this->error ." Failed to get $this->uri in context (timeout ($this->Timeout)";
			if($GLOBALS["VERBOSE"]){echo "Failed to get $this->uri in context\n";}
			return false;
		}
		
		$this->OutPutlogs("getdirect() -> ".strlen($result)." bytes");
		$this->data=$result;
		
		if(!$this->ParseTextError()){
			$this->error="ParseTextError() -> Return False data:".strlen($result)." bytes";
			$this->OutPutlogs("ParseTextError() -> Return False....");
			return false;
		}
		
		return true;
	}

	
	function get(){
		if (!extension_loaded('curl')) {return $this->wget();}
		if($this->UseDirect){return $this->getdirect();}
		
		
		$curlPost=null;
			if(isset($this->parms)){
				if(count($this->parms)>0){
					while (list ($num, $ligne) = each ($this->parms)){$curlPost .='&'.$num.'=' . urlencode($ligne);}	
				}
			}	
				
			$ch = curl_init();
			if($this->interface<>null){
				$this->OutPutlogs("CURLOPT_INTERFACE $this->interface");
				curl_setopt($ch, CURLOPT_INTERFACE,$this->interface);}
			else{
				
				if($this->WgetBindIpAddress<>null){
					$this->OutPutlogs("CURLOPT_INTERFACE $this->WgetBindIpAddress");
					curl_setopt($ch, CURLOPT_INTERFACE,$this->WgetBindIpAddress);
				}
			}
			curl_setopt($ch, CURLOPT_URL, "$this->uri");
			curl_setopt($ch, CURLOPT_HEADER, $this->CURLOPT_HEADER);
			$this->OutPutlogs("PHP: ".PHP_VERSION);
			//curl_setopt($ch, CURLINFO_HEADER_OUT, $this->CURLOPT_HEADER);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			
			if($this->NoHTTP_POST){curl_setopt($ch, CURLOPT_POST, 0);}else{curl_setopt($ch, CURLOPT_POST, 1);}
			if($this->UserAgent<>null){curl_setopt($ch, CURLOPT_USERAGENT, $this->UserAgent);}else{curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");}
			if($this->FollowLocation){curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);}
			
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
			
			if($this->authname<>null){
				if($GLOBALS["VERBOSE"]){writelogs("Enable website authentication has $this->authname",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				curl_setopt($ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");
			}else{
				//if($GLOBALS["VERBOSE"]){writelogs("no website authentication has enabled",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
			}
			
			curl_setopt ($ch, CURLOPT_TIMEOUT, $this->Timeout);
			
			if($this->ArticaProxyServerEnabled=="yes"){
				$this->OutPutlogs("Using proxy $this->ArticaProxyServerName:$this->ArticaProxyServerPort");
				//if($GLOBALS["VERBOSE"]){echo "Loading proxy settings...\n";}
				//curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,FALSE); 
				curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				//if($GLOBALS["VERBOSE"]){echo "setting proxy for $this->ArticaProxyServerName:$this->ArticaProxyServerPort\n";}
				curl_setopt ($ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
				
				//curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC | CURLAUTH_NTLM | CURLAUTH_ANYSAFE);
				if($this->ArticaProxyServerUsername<>null){
					$this->OutPutlogs("Using proxy with authentication [$this->ArticaProxyServerUsername:$this->ArticaProxyServerUserPassword]");
					curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_ANY);
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
				}
			}else{
				$this->OutPutlogs("Not using proxy...");
				$this->ArticaProxyServerEnabled="no";
				$this->ArticaProxyServerName=null;
				$this->ArticaProxyServerPort=null;
				curl_setopt($ch, CURLOPT_NOPROXY,$this->CURLOPT_NOPROXY);
			}		
			
			
			if($GLOBALS["VERBOSE"]){curl_setopt($ch, CURLINFO_HEADER_OUT, 1);}
			
			if($curlPost<>null){$this->OutPutlogs("Add posts datas....");curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);}	
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			$this->data=curl_exec($ch);
			$this->CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
			if($GLOBALS["VERBOSE"]){echo curl_getinfo($ch, CURLINFO_HEADER_OUT);}
			if($GLOBALS["VERBOSE"]){echo "***** $this->CURLINFO_HTTP_CODE *****\n";}
			
			
			$header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
			$this->headers=substr($this->data, 0, $header_size);
			$this->data=substr( $this->data, $header_size );
			$this->OutPutlogs($this->headers);
			$error=curl_errno($ch);	
			$this->OutPutlogs(__CLASS__."/".__FUNCTION__."(): Error:$error");
			if($error>0){$this->OutPutlogs(__CLASS__."/".__FUNCTION__."(): Error:$error: datas: $this->uri (". strlen($this->data)." bytes) ");}
			curl_close($ch);
			
			
			if(!$this->ParseTextError()){
				$this->OutPutlogs("Return False....");
				return false;
			}
			
			
			return $this->ParseError($error);
	}
	
	public function ParseTextError(){
			if($this->CURLINFO_HTTP_CODE<400){return true;}
		
		
		
			if(strpos($this->headers, "407 Proxy Authentication Required")){
				
				$this->data=null;
				$this->error="407 Proxy Authentication Required";
				$this->error_num=407;
				return false;
			}
		
		
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
				
				if(strpos(strtolower($this->data), strtolower("X-Squid-Error:"))>0){
					
					if(preg_match("#X-Squid-Error:\s+([0-9]+)\s+(.+?)$#is", $this->data,$re)){
						$this->error="Error {$re[1]}: {$re[2]}";
					}				
					$this->error_num=$re[1];
					$this->data=null;
					if($this->error_num<400){return true;}
					return false;
				}
				
				

			return true;
		
	}
	
	
	public function ParseError($error){
		
			
		
		if($error>0){
			if($GLOBALS["VERBOSE"]){echo "ccurl/ParseError($error)\n";}
			$trace=@debug_backtrace();if(isset($trace[1])){
			$called="called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";
			}
		}
			if($error==6){
				writelogs("error 6 Couldn't resolve host. The given remote host was not resolved $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_COULDNT_RESOLVE_HOST}";
				return false;
			}
			
			if($error==7){
				writelogs("error 7 Failed to connect() to host or proxy (Proxy: $this->ArticaProxyServerEnabled, $this->ArticaProxyServerName:$this->ArticaProxyServerPort) $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_COULDNT_CONNECT} (Proxy: $this->ArticaProxyServerEnabled,$this->ArticaProxyServerName:$this->ArticaProxyServerPort)";
				return false;
			}
			if($error==26){
				writelogs("Curl:: error 26 There was a problem reading a local file or an error returned by the read callback. $trace",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_READ_ERROR}";
				return false;
			}
			if($error==56){
				writelogs("Curl:: error 56 Failure with receiving network data. $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_RECV_ERROR}";
				return false;
			}
			if($error==52){
				writelogs("Curl:: error 52 Nothing was returned from the server. $called",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_52_ERROR}";
				return false;
			}
			

			if($error==28){
				writelogs("Curl:: error 28 Operation timeout. The specified time-out period was reached according to the conditions. $this->called $trace",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_OPERATION_TIMEDOUT}";
				return false;
			}
			
			if($error==18){
				writelogs("Curl:: error 18 Partial file. ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_CURLE_PARTIAL_FILE}";
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
			
			if($this->WriteProgress){
				if($this->ProgressFile<>null){
					$callBack="CurlprogressCallback";
					$GLOBALS["CurlprogressCallbackFile"]=$this->ProgressFile;
				}
				if($this->ProgressFunction<>null){$callBack=$this->ProgressFunction;}
				
				curl_setopt( $ch, CURLOPT_NOPROGRESS, false );
				curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, $callBack);
			}
			
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
	    
	    if($GLOBALS["VERBOSE"]){
	    	curl_setopt($ch, CURLOPT_HEADER, 1);
	    	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	    }else{
	    	curl_setopt($ch, CURLOPT_HEADER, 0);
	    	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	    }
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	    curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); 
		
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->Timeout);
		
		if($this->interface<>null){
			if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: $this->interface\n";}
			curl_setopt($ch, CURLOPT_INTERFACE,$this->interface);}
		else{
			if($this->WgetBindIpAddress<>null){
				if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: $this->WgetBindIpAddress\n";}
				curl_setopt($ch, CURLOPT_INTERFACE,$this->WgetBindIpAddress);
			}
		}
		
			
	    // same as <input type="file" name="file_box">
		if(count($array_fields)>0){while (list ($num, $line) = each ($array_fields) ){$post[$num]=$line;}}
		if($this->x_www_form_urlencoded){
			$post[$fieldname]=base64_encode(@file_get_contents($filepath));
		}else{
			$post[$fieldname]="@$filepath";
		}
	   
		if($this->x_www_form_urlencoded){
	    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post,"","&"));
		}else{
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
	    curl_setopt($ch,CURLOPT_HTTPHEADER,array("Expect:"));
	    
	    
	    if($this->DebugProgress){
	    	curl_setopt( $ch, CURLOPT_NOPROGRESS, 0 );
	    	curl_setopt( $ch, CURLOPT_PROGRESSFUNCTION, "CurlprogressCallbackDebug");
	    }	    
	    
	    
	    
	    $size=@filesize("$filepath");
	    $size=$size/1024;
	    $UNIT="KB";
	    if($size>1000){$UNIT="MB";$size=$size/1024;}
	    $size=round($size,2);
	    if($GLOBALS["VERBOSE"]){echo "CURLOPT_TIMEOUT: {$this->Timeout}s\n";}
	    
	    writelogs("$this->uri: Executing posting _FILE(S)[$fieldname] $filepath ($size $UNIT)",__CLASS__.'/'.__FUNCTION__,__FILE__);
	    $this->data = curl_exec($ch);
	    if($GLOBALS["VERBOSE"]){
	    	print_r(curl_getinfo($ch));
	    }
		$error=curl_errno($ch);	
		writelogs("Returned datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(!$this->ParseError($error)){
			writelogs("Returned datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			curl_close($ch);
			return false;
		}
		
		curl_close($ch);
		return true;
}

}
function CurlprogressCallbackDebug( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]=0;}

	if ( $download_size == 0 )
		$progress = 0;
	else
		$progress = round( $downloaded_size * 100 / $download_size );
	 

	if ( $progress > $GLOBALS["previousProgress"]){
		echo $progress."%\n";
		$GLOBALS["previousProgress"] = $progress;
	}
}

function CurlprogressCallback( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
    static $previousProgress = 0;
    
    if ( $download_size == 0 )
        $progress = 0;
    else
        $progress = round( $downloaded_size * 100 / $download_size );
       
    
    if ( $progress > $previousProgress){
    	if($GLOBALS["VERBOSE"]){echo $progress."\n";}
        $previousProgress = $progress;
        $fp = fopen( $GLOBALS["CurlprogressCallbackFile"], 'w' );
        fputs( $fp, "$progress\n" );
        fclose( $fp );
    }
}