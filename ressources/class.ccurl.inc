<?php
include_once(dirname(__FILE__)."/class.system.network.inc");

if (!defined('CURLOPTTYPE_OBJECTPOINT')){define('CURLOPTTYPE_OBJECTPOINT', 10000);}
if (!defined('CURLOPT_NOPROXY')){define('CURLOPT_NOPROXY', CURLOPTTYPE_OBJECTPOINT + 177);}
if (!defined('CURLOPT_MAX_SEND_SPEED_LARGE')){define('CURLOPT_MAX_SEND_SPEED_LARGE', 145);}
if (!defined('CURLOPT_MAX_RECV_SPEED_LARGE')){define('CURLOPT_MAX_RECV_SPEED_LARGE', 146);}
if(!isset($GLOBALS["NOT_FORCE_PROXY"])){$GLOBALS["NOT_FORCE_PROXY"]=false;}
//scandir($dir,SCANDIR_SORT_NONE);

class ccurl{
	var $uri;
	var $orginal_data=null;
	var $UseDirect=false;
	var $parms=array();
	var $data='';
	var $NoHTTP_POST=false;
	var $GET_HEADERS=false;
	var $error;
	var $ArticaProxyServerEnabled="no";
	var $ArticaProxyServerName='';
	var $ArticaProxyServerPort=3128;
	var $ArticaProxyServerUsername=null;
	var $ArticaProxyServerUserPassword=null;
	public $interface=null;
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
	var $CURL_ALL_INFOS=array();
	var $CurlBandwith=0;
	
	var $forceCache=false;
	private $called=null;
	private $headers=null;
	private $NoCheckSquid=false;
	private $ch;
	
	// CURLOPT_INTERFACE
	
	function ccurl($uri=null,$noproxyload=false,$interface=null,$NoCheckSquid=false){
			if(!isset($GLOBALS["AS_ROOT"])){if(posix_getuid()==0){$GLOBALS["AS_ROOT"]=true;}else{$GLOBALS["AS_ROOT"]=false;}}
			$trace=@debug_backtrace();if(isset($trace[1])){$this->called="called by source ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}
			if(!isset($GLOBALS["CLASS_SOCKETS"])){$sock=new sockets();$GLOBALS["CLASS_SOCKETS"]=$sock;}else{$sock=$GLOBALS["CLASS_SOCKETS"];}
			$this->uri=$uri;
			$this->interface=$interface;
			$this->noproxyload=$noproxyload;
			$this->NoCheckSquid=$NoCheckSquid;
			$this->OutPutlogs("*");
			$this->OutPutlogs("******************************");
			$this->OutPutlogs("*");
			$sock=new sockets();
			if(!isset($GLOBALS["WgetBindIpAddress"])){
				$GLOBALS["WgetBindIpAddress"]=$sock->GET_INFO("WgetBindIpAddress");
			}
			
			$this->CurlBandwith=$sock->GET_INFO("CurlBandwith");
			$this->Timeout=$sock->GET_INFO("CurlTimeOut");
			$NoCheckSquid=$sock->GET_INFO("NoCheckSquid");
			
			if(is_numeric($NoCheckSquid)){ 
				if($NoCheckSquid==1){
					$this->OutPutlogs("ccurl(): NoCheckSquid -> TRUE");
					$this->NoCheckSquid=true;
				} 
			}
			
			if(!is_numeric($this->CurlBandwith)){$this->CurlBandwith=0;}
			if(!is_numeric($this->Timeout)){$this->Timeout=3600;}
			if($this->Timeout<720){$this->Timeout=3600;}
			
			$this->OutPutlogs("ccurl(): CurlBandwith -> $this->CurlBandwith");
			$this->OutPutlogs("ccurl(): Timeout -> $this->Timeout seconds");
			
			$this->WgetBindIpAddress=$GLOBALS["WgetBindIpAddress"];
			$this->LoadProxy();

			
	}
	
	private function LoadProxy(){
		if($GLOBALS["NOT_FORCE_PROXY"]){
			$this->ArticaProxyServerEnabled="no";
			$this->ArticaProxyServerUsername=null;
			$this->ArticaProxyServerPort=null;
			return;
		}
		if(!$this->noproxyload){
			$ini=new Bs_IniHandler();
			$sock=new sockets();
			$datas=$sock->GET_INFO("ArticaProxySettings");
			if(trim($datas)<>null){
				$ini->loadString($datas);
				$this->ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
				$this->ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
				$this->ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
				$this->ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
				$this->ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			}
		}
		
		if($this->ArticaProxyServerEnabled=="yes"){
			if($this->ArticaProxyServerName==null){$this->ArticaProxyServerEnabled=null;}
			if(!is_numeric($this->ArticaProxyServerPort)){$this->ArticaProxyServerEnabled=null;}
		}
		if($this->ArticaProxyServerEnabled=="yes"){return;}
		if($this->NoCheckSquid){
			
			$this->OutPutlogs("LoadProxy() -> NoCheckSquid - TRUE, abort");
			return;}
		
		
		if(!$GLOBALS["AS_ROOT"]){return;}
		if(!class_exists("unix")){include_once("/usr/share/artica-postfix/framework/class.unix.inc");}
		$unix=new unix();
		$squid=$unix->LOCATE_SQUID_BIN();
		if(!is_file($squid)){return;}
		$SQUIDEnable=@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable");
		if(!is_numeric($SQUIDEnable)){$SQUIDEnable=1;}
		if($GLOBALS["VERBOSE"]){echo __FUNCTION__."::".__LINE__." SQUIDEnable=$SQUIDEnable\n";}
		if($SQUIDEnable==0){return;}
		$SquidMgrListenPort=trim(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidMgrListenPort"));
		if(!is_numeric($SquidMgrListenPort)){return;}
		if($GLOBALS["VERBOSE"]){echo "Using Proxy 127.0.0.1:$SquidMgrListenPort\n";}
		$this->ArticaProxyServerName="127.0.0.1";
		$this->ArticaProxyServerPort=$SquidMgrListenPort;
		if($this->TestProxy()){
			$this->ArticaProxyServerEnabled="yes";
			$this->ArticaProxyServerUsername=null;
		}else{
			$this->ArticaProxyServerName=null;
			$this->ArticaProxyServerPort=0;
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
			@unlink("/tmp/artica-wget.txt");
		
	}
	
	private function OutPutlogs($text){
		if($GLOBALS["VERBOSE"]){echo "DEBUG:: $text\n";}
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
				
				$this->OutPutlogs("getdirect_HTTP_Request2() -> $this->uri");
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
					$this->OutPutlogs("getdirect_HTTP_Request2() -> $es $ets $egc $egl $egm $egt");
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
		if(!is_file($pear)){if($GLOBALS["VERBOSE"]){echo "pear_install_HTTP_Request2() `pear` no such binary\n";}return;}
		$hohup=$unix->find_program("nohup");
		shell_exec("$hohup $pear install HTTP_Request2 >/dev/null 2>&1 &");
	}
	private function  pear_install_Net_URL2(){
		if(!$GLOBALS["AS_ROOT"]){return false;}
		$unix=new unix();
		$pear=$unix->find_program("pear");
		if(!is_file($pear)){if($GLOBALS["VERBOSE"]){echo "pear_install_Net_URL2() `pear` no such binary\n";}return;}
		$hohup=$unix->find_program("nohup");
		shell_exec("$hohup $pear install Net_URL2 >/dev/null 2>&1 &");
		
	}
	
	
	
	private function getdirect(){
		
		if($GLOBALS["VERBOSE"]){echo "getdirect() -> HTTP/Request2.php\n";}
		include_once(dirname(__FILE__).'/HTTP/Request2.php');
		
		
		
		
		if($GLOBALS["VERBOSE"]){echo "getdirect() -> Tests classes\n";}
		if(!class_exists("Http_Request2")){
			$this->OutPutlogs("getdirect() -> Class Http_Request2 did not exists");
			if($GLOBALS["VERBOSE"]){echo " Class Http_Request2 did not exists -> pear_install_HTTP_Request2\n";}
			$this->pear_install_HTTP_Request2();
		}else{
			if($GLOBALS["VERBOSE"]){echo " Class Http_Request2 OK\n";}
		}
			
		if(!class_exists("Net_URL2")){
			$this->OutPutlogs("getdirect() -> Class Net_URL2 did not exists");
			if($GLOBALS["VERBOSE"]){echo " Class Net_URL2 did not exists -> pear_install_Net_URL2\n";}
			$this->pear_install_Net_URL2();
		}else{
			$this->OutPutlogs("getdirect() -> Class Net_URL2 OK");
			if($GLOBALS["VERBOSE"]){echo " Class Net_URL2 OK\n";}
		}
			
		if(class_exists("Http_Request2")){
			$this->OutPutlogs("getdirect() -> Class getdirect_HTTP_Request2()");
			return $this->getdirect_HTTP_Request2();}
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
	
	
	public function getHeaders(){
		
		$this->ch = curl_init();
		$this->CheckInterface();
		
		
		if($this->interface<>null){
			$this->OutPutlogs("CURLOPT_INTERFACE \"$this->interface\" Line ".__LINE__);
			curl_setopt($this->ch, CURLOPT_INTERFACE,$this->interface);
		}
		
		if($this->UserAgent<>null){curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);}else{curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");}
		if($this->FollowLocation){curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,true);}
				
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
		curl_setopt($this->ch, CURLOPT_URL, $this->uri);
		curl_setopt($this->ch, CURLOPT_FILETIME, true);
		curl_setopt($this->ch, CURLOPT_NOBODY, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		
		if($this->authname<>null){curl_setopt($this->ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");}
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
		$this->CheckProxy();
		
		
		$this->header = curl_exec($this->ch);
		$Infos= curl_getinfo($this->ch);
		curl_close($this->ch);
		return $Infos;
	}
	
	
	private function TestProxy(){
		if($this->NoCheckSquid){$GLOBALS["CCURL_TEST_PROXY"]=false;return false;}
		if(isset($GLOBALS["CCURL_TEST_PROXY"])){return $GLOBALS["CCURL_TEST_PROXY"];}
		$f=@fsockopen ($this->ArticaProxyServerName, $this->ArticaProxyServerPort, $errno, $errstr, 2);

		if(!$f){
			$this->OutPutlogs("TestProxy() $this->ArticaProxyServerName:$this->ArticaProxyServerPort failed");
			$GLOBALS["CCURL_TEST_PROXY"]=false;
			return false;
		}
		
		fclose($f);
		$this->OutPutlogs("CCURL_TEST_PROXY $this->ArticaProxyServerName:$this->ArticaProxyServerPort OK");
		$GLOBALS["CCURL_TEST_PROXY"]=true;
		return true;
	}
	
	public function getFiletime()
	{
		return $this->info['filetime'];
	}
	
	public function NoLocalProxy(){
		$this->OutPutlogs( __CLASS__."/".__FUNCTION__.":: ".__LINE__.":: -> NoCheckSquid TRUE");
		$this->NoCheckSquid=true;
	}
	
	function get(){
		if (!extension_loaded('curl')) {return $this->wget();}
		if($this->UseDirect){
			$this->OutPutlogs( __CLASS__."/".__FUNCTION__.":: ".__LINE__.":: ->getdirect()");
			return $this->getdirect();
		}
			
		$this->ch = curl_init();
		$curlPost=null;
			if(isset($this->parms)){
				if(count($this->parms)>0){
					while (list ($num, $ligne) = each ($this->parms)){$curlPost .='&'.$num.'=' . urlencode($ligne);}	
				}
			}	
				
			
			
			
			$this->OutPutlogs("CURLOPT_TIMEOUT: {$this->Timeout}s");
			curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
			curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
			curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
			curl_setopt($this->ch, CURLOPT_HEADER, $this->CURLOPT_HEADER);
			$this->OutPutlogs("PHP: ".PHP_VERSION);
			//curl_setopt($this->ch, CURLINFO_HEADER_OUT, $this->CURLOPT_HEADER);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
			
			if($this->NoHTTP_POST){curl_setopt($this->ch, CURLOPT_POST, 0);}else{curl_setopt($this->ch, CURLOPT_POST, 1);}
			if($this->UserAgent<>null){curl_setopt($this->ch, CURLOPT_USERAGENT, $this->UserAgent);}else{curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:10.0) Gecko/20100101 Firefox/10.0");}
			if($this->FollowLocation){curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION,true);}
			
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache", "Cache-Control: no-cache"));
			
			if($this->authname<>null){
				if($GLOBALS["VERBOSE"]){writelogs("Enable website authentication has $this->authname",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);}
				curl_setopt($this->ch, CURLOPT_USERPWD, "$this->authname:$this->authpass");
			}
			
			$this->CheckInterface();
			$this->CheckProxy();
	
			
			if($this->CurlBandwith>0){
				$this->OutPutlogs("CURLOPT_MAX_RECV_SPEED_LARGE = $this->CurlBandwith KB/S");
				curl_setopt($this->ch,CURLOPT_MAX_RECV_SPEED_LARGE,$this->CurlBandwith*1024);
			}
			
			
			if($GLOBALS["VERBOSE"]){curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);}
			
			if($curlPost<>null){$this->OutPutlogs("Add posts datas....");curl_setopt($this->ch, CURLOPT_POSTFIELDS, $curlPost);}	
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
			$this->data=curl_exec($this->ch);
			$this->orginal_data=$this->data;
			$this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
			$this->CURL_ALL_INFOS=curl_getinfo($this->ch);
			if($GLOBALS["VERBOSE"]){echo curl_getinfo($this->ch, CURLINFO_HEADER_OUT);}
			if($GLOBALS["VERBOSE"]){echo "***** $this->CURLINFO_HTTP_CODE *****\n";}
			if(!$this->ParseCodeError()){$this->OutPutlogs("ParseCodeError return false -> Failed");return false;}
			
			$header_size = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
			$this->headers=substr($this->data, 0, $header_size);
			$this->data=substr( $this->data, $header_size );
			$this->OutPutlogs($this->headers);
			$error=curl_errno($this->ch);	
			$this->OutPutlogs(__CLASS__."/".__FUNCTION__."(): Error:$error");
			if($error>0){$this->OutPutlogs(__CLASS__."/".__FUNCTION__."(): Error:$error: datas: $this->uri (". strlen($this->data)." bytes) ");}
			curl_close($this->ch);
			
			
			if(!$this->ParseTextError()){
				$this->OutPutlogs("Return False....");
				return false;
			}
			
			
			return $this->ParseError($error);
	}
	
	
	private function ParseCodeError(){
		
		if($this->CURLINFO_HTTP_CODE==300){
			if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE == 300 (300 Multiple Choices) return false\n";}
			$this->data=null;
			$this->error="503 Multiple Choices";
			$this->error_num=300;
			return false;
		}
		
		if($this->CURLINFO_HTTP_CODE<400){
			if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: $this->CURLINFO_HTTP_CODE < 400 return true\n";}
			return true;}
		
		if($this->CURLINFO_HTTP_CODE==503){
			$this->data=null;
			$this->error="503 Service Unavailable";
			$this->error_num=503;
			return false;
		}
		
		if($GLOBALS["VERBOSE"]){echo "ParseCodeError:: Parsing CURLINFO_HTTP_CODE:$this->CURLINFO_HTTP_CODE\n";}

		$this->OutPutlogs("Parsing code $this->CURLINFO_HTTP_CODE");
		$array[407]="Proxy Authentication Required";
		$array[404]="Not found";
		$array[400]="Bad Request";
		$array[403]="Access denied";
		$array[500]="Internal Server Error";
		if(isset($array[$this->CURLINFO_HTTP_CODE])){
			$this->OutPutlogs($array[$this->CURLINFO_HTTP_CODE]);
			$this->data=null;
			$this->error_num=$this->CURLINFO_HTTP_CODE;
			$this->error=$array[$this->CURLINFO_HTTP_CODE];
			return false;
		}
		
		return true;
		
	}
	
	public function ParseTextError(){
		if($this->CURLINFO_HTTP_CODE<400){return true;}
			
		if($this->CURLINFO_HTTP_CODE==503){
			$this->data=null;
			$this->error="503 Service Unavailable";
			$this->error_num=503;
			return false;
			}
			
			if($this->CURLINFO_HTTP_CODE==403){
				$this->data=null;
				$this->error="403: Access denied";
				$this->error_num=403;
				return false;
			}			
		
		
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
			
		if($error==23){
			writelogs("Curl:: error 23 An error occurred when writing received data to a local file. $trace",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
			$this->error="{CURLE_WRITE_ERROR}";
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
				$this->error="{CURLE_OPERATION_TIMEDOUT} - {$this->Timeout}s";
				return false;
			}
			
			if($error==18){
				writelogs("Curl:: error 18 Partial file. ",__CLASS__.'/'.__FUNCTION__,__FILE__,__LINE__);
				$this->error="{CURLE_CURLE_PARTIAL_FILE}";
				return false;
			}			
			
		return true;
		
		
	}
	
	private function ChooseInterface(){
		if(isset($GLOBALS["CURL_ChooseInterface"])){return $GLOBALS["CURL_ChooseInterface"];}
		$ips=new networking();
		$array=$ips->ALL_IPS_GET_ARRAY();
		unset($array["127.0.0.1"]);
		unset($array["127.0.0.2"]);
		unset($array["127.0.0.3"]);
		unset($array["127.0.0.4"]);
		while (list ($ip, $line) = each ($array) ){
			if(preg_match("#127\.0\.0#", $ip)){continue;}
			$f[]=$ip;
		}
		$GLOBALS["CURL_ChooseInterface"]=$f[0];
		return $GLOBALS["CURL_ChooseInterface"];
		
	}
	
	private function EXEC_NO_PROXY(){
		$this->ArticaProxyServerEnabled="no";
		$this->ArticaProxyServerName=null;
		$this->ArticaProxyServerPort=null;
		$this->OutPutlogs("CURLOPT_NOPROXY = TRUE");
		curl_setopt($this->ch, CURLOPT_NOPROXY,$this->CURLOPT_NOPROXY);
		$this->EXEC_INTERFACE_NOLOCAL();
		
	}
	
	private function EXEC_INTERFACE_NOLOCAL(){
		if($this->interface<>"127.0.0.1"){return;}
		$this->interface=$this->ChooseInterface();
		if(trim($this->interface==null)){return;}
		$this->OutPutlogs("CURLOPT_INTERFACE = \"$this->interface\" Line: ".__LINE__);
		curl_setopt($this->ch, CURLOPT_INTERFACE,$this->interface);
		
	}
	
	private function EXEC_PROXY_USERNAME(){
		if($this->ArticaProxyServerUsername==null){return;}
		
		curl_setopt($this->ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
		if($this->ArticaProxyServerUserPassword<>null){
			curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername.':'.$this->ArticaProxyServerUserPassword);
			return;
		}
		curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $this->ArticaProxyServerUsername);
		
		
	}
	
	private function EXEC_PROXY(){
		curl_setopt($this->ch,CURLOPT_HTTPPROXYTUNNEL,FALSE);
		curl_setopt ($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		if($this->forceCache){
			if($GLOBALS["VERBOSE"]){echo "$this->ArticaProxyServerName:$this->ArticaProxyServerPort Force cache...\n";}
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
		}
		$this->OutPutlogs("CURLOPT_PROXY = $this->ArticaProxyServerName:$this->ArticaProxyServerPort");
		curl_setopt ($this->ch, CURLOPT_PROXY,"$this->ArticaProxyServerName:$this->ArticaProxyServerPort");
		
		if($this->ArticaProxyServerName<>"127.0.0.1"){$this->EXEC_INTERFACE_NOLOCAL();}
		$this->EXEC_PROXY_USERNAME();
		
		
	}
	
	
	private function CheckProxy(){
		
		

		if($this->ArticaProxyServerEnabled<>"yes"){$this->ArticaProxyServerName=null;}
		
		if($this->ArticaProxyServerName==null){
			$this->OutPutlogs("CURLOPT_PROXY_ENABLED = FALSE ( ArticaProxyServerEnabled = `$this->ArticaProxyServerEnabled`)");
			$this->EXEC_NO_PROXY();
			return;
		}
		
		if(!$this->TestProxy()){
			$this->OutPutlogs("CURLOPT_PROXY_ENABLED = FALSE -> UNAVAILABLE");
			$this->EXEC_NO_PROXY();
			return;
		}
		$this->OutPutlogs("CURLOPT_PROXY_ENABLED = $this->ArticaProxyServerEnabled");
		$this->OutPutlogs("CURLOPT_PROXY_SERVER = $this->ArticaProxyServerName");
		$this->OutPutlogs("CURLOPT_PROXY_PORT = $this->ArticaProxyServerPort");
		$this->EXEC_PROXY();
		
		
	}
	
	private function CheckInterface(){
		if($this->WgetBindIpAddress<>null){$this->interface=$this->WgetBindIpAddress;}
		
		if($this->interface==null){
			$this->interface=$this->ChooseInterface();
			$GLOBALS["CURLDEBUG"][]="CURLOPT_INTERFACE: \"$this->interface\" Line ". __LINE__;
			if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: \"$this->interface\" Line ". __LINE__."\n";}
		}
		
		if($this->interface=="127.0.0.1"){$this->EXEC_INTERFACE_NOLOCAL();}
		if(trim($this->interface)==null){
			$GLOBALS["CURLDEBUG"][]="CURLOPT_INTERFACE: \"NONE\" Line ". __LINE__;
			if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: NONE\n";}
			return;
		}
		if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: `$this->interface`\n";}
		$GLOBALS["CURLDEBUG"][]="CURLOPT_INTERFACE: \"$this->interface\" Line ". __LINE__;
		curl_setopt($this->ch, CURLOPT_INTERFACE,$this->interface);
		
		
	}
	
	function GetHeads(){
		$SUFFIX_VERBOSE=__CLASS__."/".__FUNCTION__;
		if (!extension_loaded('curl') or ($this->ch = curl_init($this->uri)) === false) {echo "No curl extentions can be loaded\n";return;}
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
		curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
		curl_setopt($this->ch, CURLOPT_NOBODY, TRUE);
		curl_setopt($this->ch, CURLOPT_FILETIME, TRUE);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
		if(!is_numeric($this->Timeout)){$this->Timeout=120;}
		if(preg_match("#https:#",$this->uri)){
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
		$result=curl_exec($this->ch);
		$this->CURL_ALL_INFOS=curl_getinfo($this->ch);
		$this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
		$error=curl_errno($this->ch);
		if($GLOBALS["VERBOSE"]){echo "GetHeads:: Error N.$error\n";}
		
		curl_close($this->ch);
		if(!$this->ParseError($error)){return false;}
		return $this->ParseCodeError();
	}
	
	
	function GetFile($targetpath){
		$SUFFIX_VERBOSE=__CLASS__."/".__FUNCTION__;
		if (!extension_loaded('curl') or ($this->ch = curl_init($this->uri)) === false) {echo "No curl extentions can be loaded\n";return;}
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER,true);
		$this->OutPutlogs("CURLOPT_FILE = $targetpath");
		curl_setopt($this->ch, CURLOPT_FILE, $fp = fopen($targetpath,'w'));
		curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
		curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, TRUE);
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
		if(!is_numeric($this->Timeout)){$this->Timeout=120;}
		
		if($GLOBALS["DEBUG"]){
			curl_setopt($this->ch, CURLINFO_HEADER_OUT,TRUE);
		}else{
			curl_setopt($this->ch, CURLINFO_HEADER_OUT,FALSE);
		}
			
		
			
			
		if(preg_match("#https:#",$this->uri)){
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		
		curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
			
		if($this->WriteProgress){
			if($this->ProgressFile<>null){
				$callBack="CurlprogressCallback";
				$GLOBALS["CurlprogressCallbackFile"]=$this->ProgressFile;
			}
			if($this->ProgressFunction<>null){$callBack=$this->ProgressFunction;}
			curl_setopt( $this->ch, CURLOPT_NOPROGRESS, false );
			curl_setopt( $this->ch, CURLOPT_PROGRESSFUNCTION, $callBack);
		}
			
		$this->CheckInterface();
		$this->CheckProxy();
		
		curl_setopt ($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
		
		if($this->CurlBandwith>0){
			$this->OutPutlogs("CURLOPT_MAX_RECV_SPEED_LARGE = $this->CurlBandwith KB/S");
			curl_setopt($this->ch,CURLOPT_MAX_RECV_SPEED_LARGE,$this->CurlBandwith*1024);
		}
		
		$this->OutPutlogs("CURL_EXEC: \"$this->uri\" [".__LINE__."]");
		$result=curl_exec($this->ch); 
		
		
		$this->CURLINFO_HTTP_CODE=curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
		$this->OutPutlogs("CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]");
		
		
		$this->CURL_ALL_INFOS=curl_getinfo($this->ch);
		
		fclose($fp);
		$error=curl_errno($this->ch);	
		$this->OutPutlogs("CURL_ERROR_TEXT: $error");
		curl_close($this->ch);
		
		if($GLOBALS["VERBOSE"]){echo "Check \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]\n";}
		
		if(!$this->ParseTextError()){
			$this->OutPutlogs("* * * ParseTextError() return false * * * ");
			@unlink($targetpath);
			$this->OutPutlogs("Return False....");
			writelogs("*** FAILED *** datas: $this->uri Error.$this->CURLINFO_HTTP_CODE",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;
		}
		
		
		if($this->CURLINFO_HTTP_CODE==404){
			$this->OutPutlogs("* * * 404 not found * * * ");
			@unlink($targetpath);
			$this->error_num=404;
			if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
			writelogs("*** FAILED *** datas: $this->uri 404 not Found error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;
		}
		
		if($GLOBALS["VERBOSE"]){echo "Check \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]\n";}
		if(!is_file($targetpath)){
			$this->OutPutlogs("* * * $targetpath no such file * * * ");
			if($GLOBALS["VERBOSE"]){echo "*** FAILED *** CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE [".__LINE__."]\n";}
			if($GLOBALS["VERBOSE"]){echo "path \"$targetpath\" CURLINFO_HTTP_CODE: $this->CURLINFO_HTTP_CODE no such file\n";}
			return false;
		}
			
		if($GLOBALS["VERBOSE"]){echo "Check \"ParseError($error) - ERR: $this->CURLINFO_HTTP_CODE\" [".__LINE__."]\n";}	
		if(!$this->ParseError($error)){
			$this->OutPutlogs("* * * $error return false * * * ");
			if($GLOBALS["VERBOSE"]){echo "*** FAILED *** [".__LINE__."]\n";}
			writelogs("*** FAILED *** datas: $this->uri (". strlen($this->data)." bytes) error: $error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			return false;
		}
		
		$filesize=filesize($targetpath);
		$filesizeText=$filesize;
		$filesizeUnit="Bytes";
		if($filesize>1024){$filesizeText=$filesize/1024;$filesizeUnit="Ko";}
			
		if($error>0){
			$this->OutPutlogs("* * * $error > 0 * * * ");
			writelogs("datas: $this->uri (". strlen($result)." bytes) filesize=$filesizeText $filesizeUnit error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
		}
			
		if($filesize<10000){
			$this->data=@file_get_contents($targetpath);
			if(!$this->ParseTextError()){$this->data=null;return false;}
			$this->data=null;
			return true;
		}	
		//@file_put_contents($this->data,$targetpath);
		if($GLOBALS["VERBOSE"]){echo "SUCCESS [".__LINE__."]\n";}
		return true;
	}
	
	
	function postFile($fieldname,$filepath,$array_fields=array()){
	    $this->ch = curl_init();
	    curl_setopt($this->ch, CURLOPT_URL, "$this->uri");
	    
	    if($GLOBALS["VERBOSE"]){
	    	curl_setopt($this->ch, CURLOPT_HEADER, 1);
	    	curl_setopt($this->ch, CURLOPT_VERBOSE, 1);
	    }else{
	    	curl_setopt($this->ch, CURLOPT_HEADER, 0);
	    	curl_setopt($this->ch, CURLOPT_VERBOSE, 0);
	    }
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($this->ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	    curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0); 
		
		curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->Timeout);
		
		if($this->interface<>null){
			if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: \"$this->interface\" Line ".__LINE__."\n";}
			curl_setopt($this->ch, CURLOPT_INTERFACE,$this->interface);}
		else{
			if($this->WgetBindIpAddress<>null){
				if($GLOBALS["VERBOSE"]){echo "CURLOPT_INTERFACE: \"$this->WgetBindIpAddress\" Line ".__LINE__."\n";}
				curl_setopt($this->ch, CURLOPT_INTERFACE,$this->WgetBindIpAddress);
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
	    	curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($post,"","&"));
		}else{
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
		}
	    curl_setopt($this->ch,CURLOPT_HTTPHEADER,array("Expect:"));
	    
	    
	    if($this->DebugProgress){
	    	curl_setopt( $this->ch, CURLOPT_NOPROGRESS, 0 );
	    	curl_setopt( $this->ch, CURLOPT_PROGRESSFUNCTION, "CurlprogressCallbackDebug");
	    }	    
	    
	    
	    
	    $size=@filesize("$filepath");
	    $size=$size/1024;
	    $UNIT="KB";
	    if($size>1000){$UNIT="MB";$size=$size/1024;}
	    $size=round($size,2);
	    if($GLOBALS["VERBOSE"]){echo "CURLOPT_TIMEOUT: {$this->Timeout}s\n";}
	    
	    writelogs("$this->uri: Executing posting _FILE(S)[$fieldname] $filepath ($size $UNIT)",__CLASS__.'/'.__FUNCTION__,__FILE__);
	    $this->data = curl_exec($this->ch);
	    if($GLOBALS["VERBOSE"]){
	    	print_r(curl_getinfo($this->ch));
	    }
		$error=curl_errno($this->ch);	
		writelogs("Returned datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
		if(!$this->ParseError($error)){
			writelogs("Returned datas: $this->uri (". strlen($this->data)." bytes) error:$error",__CLASS__.'/'.__FUNCTION__,__FILE__);
			curl_close($this->ch);
			return false;
		}
		
		curl_close($this->ch);
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