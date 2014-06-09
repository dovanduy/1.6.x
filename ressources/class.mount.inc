<?php
include_once(dirname(__FILE__)."/class.ping.inc");
class mount{
	var $eventspath=null;
	var $events=array();
	var $mount_point="";
	
	
	public function mount($eventspath=null){
		$this->eventspath=$eventspath;
	}
	
	public function ismounted($mount_point){
		$mount_point=str_replace("/",'\/',$mount_point);
		$mount_point=str_replace(".",'\.',$mount_point);
		$mount_point=str_replace("$",'\$',$mount_point);
		
		$tbl=explode("\n",@file_get_contents("/proc/mounts"));
		
		if(!is_array($tbl)){
			$this->events("ismounted:: not an array in /proc/mounts");
			return false;
		}
		while (list ($index, $line) = each ($tbl) ){
			if(preg_match("#$mount_point#",$line)){return true;}
		}
		//$this->events("ismounted() failed found \"$mount_point\"");	
	}
	
	public function ftp_mount($mountpoint,$server,$username,$password,$path=null){
		if($this->ismounted($mountpoint)){
			$this->events("ftp_mount() already mounted");
			return true;
		}
		
		$unix=new unix();
		$curlftpfs=$unix->find_program("curlftpfs");
		if(!is_file("$curlftpfs")){$this->events("ftp_mount() curlftpfs no such binary");return false;}
		$password=$unix->shellEscapeChars($password);
		if($path<>null){
			$server="$server/$path";
			$server=str_replace("//", "/", $server);
		}
		exec("$curlftpfs $username:$password@$server $mountpoint 2>&1",$results);
		while (list ($index, $line) = each ($results) ){
			if(function_exists("writelogs")){writelogs("$line",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$this->events("LOG:\"$line\"");
		}
		
		if($this->ismounted($mountpoint)){
			$this->events("ftp_mount() mounted");
			return true;
		}
		
		return false;
		
		
	}
	
	
	public function ssh_mount($mountpoint,$server,$username,$password,$path){
		if($this->ismounted($mountpoint)){
			$this->events("ssh_mount() already mounted");
			return true;
		}

		$options[]="allow_other";
		$options[]="umask=755";
		
		$unix=new unix();
		if(preg_match("#^(.*?)\(([0-9]+),([0-9]+)#", $username,$re)){
			$username=$re[1];
			$uid=$re[2];
			$gid=$re[3];
			$options[]="uid=$uid,gid=$gid";
			
			
		}
		
		
		$sshfs=$unix->find_program("sshfs");
		$ssh=$unix->find_program("ssh");
		if(!is_file("$sshfs")){
			$this->events("ssh_mount() sshfs, no such binary");
			return false;
		}
		if(!is_file("/home/$username/.ssh/id_rsa")){
			$this->events("ssh_mount() /home/$username/.ssh/id_rsa no such file");
			return false;	
		}
		
		$options[]="ssh_command=\"$ssh -i /home/$username/.ssh/id_rsa\"";
		
		$options_lines="-o ".@implode(",", $options);
		
		$cmd="$sshfs $options_lines $username@$server:$path $mountpoint 2>&1";
		if(function_exists("writelogs")){writelogs("$cmd",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		$this->events("ssh_mount() mounting //$server/$path -> $mountpoint");
		if(!is_dir("$mountpoint")){@mkdir($mountpoint,null,true);}
		
		exec($cmd,$results);
		while (list ($index, $line) = each ($results) ){
			if(function_exists("writelogs")){writelogs("$line",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$this->events("LOG:\"$line\"");
		}
		
		if($this->ismounted($mountpoint)){
			if(function_exists("writelogs")){writelogs("ismounted($mountpoint) return SUCCESS",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			sleep(3);
			return true;}		
		$this->events("ssh_mount() Failed...");
		return false;
		
	}
	
	public function umount($mountpoint=null){
		if($mountpoint==null){
			$this->events("umount() no mount point specified Line::".__LINE__);
			return;
		}
		
		if(!$this->ismounted($mountpoint)){
			$this->events("umount() already unmounted Line::".__LINE__);
			return;
		}
		
		@chdir("/root");
		$this->events("umount() dismount \"$mountpoint\"".__LINE__);
		shell_exec("/bin/umount -l \"$mountpoint\" >/dev/null");
		
	}
	
	private function test_smb_protocols(){
		include_once("/usr/share/artica-postfix/framework/class.unix.inc");
		$unix=new unix();
		if(is_file($unix->find_program("mount.cifs"))){
			$this->events("test_smb_protocols() CIFS success: ");
			return true;
		}
		
		if(is_file($unix->find_program("mount.smbfs"))){
			$this->events("test_smb_protocols() SMBFS success: ");
			return true;
		}		
		return false;
	}
	
	
	public function smb_mount($mountpoint,$server,$username,$password,$path){
		$unix=new unix();
		$mount=$unix->find_program("mount");
		$mount_cifs=$unix->find_program("mount.cifs");
		$mount_smbfs=$unix->find_program("mount.smbfs");
		
		if(!$this->test_smb_protocols()){
			$apt=$unix->find_program("apt-get");
			if(!is_file($apt)){
				$this->events("smb_mount() Failed, no protocol found Line::".__LINE__);
				return false;
			}
			$this->events("smb_mount() Installing smbfs Line::".__LINE__);
			$cmd="DEBIAN_FRONTEND=noninteractive $apt -o Dpkg::Options::=\"--force-confnew\" --force-yes -y install samba-common cifs-utils smbfs >/tmp/install_smbfs 2>&1";
			shell_exec($cmd);
			$f=explode("\n",@file_get_contents("/tmp/install_smbfs"));
			while (list ($index, $line) = each ($f) ){
				$this->events("smb_mount() $line Line::".__LINE__);
			}
			if(!$this->test_smb_protocols()){
				$this->events("smb_mount() Failed, no protocol found Line::".__LINE__);
				return false;
			}
		}
		
		if(preg_match("#(.+?)@(.+)#", $server,$re)){$username=$re[1];$server=$re[2];}
		$server=str_replace("@", "", $server);
		
		if($this->ismounted($mountpoint)){
			$this->events("smb_mount() already mounted Line::".__LINE__);
			return true;
		}
		

		
		if($GLOBALS["VERBOSE"]){
			$this->events("smb_mount() Username...:$username Line::".__LINE__);
			$this->events("smb_mount() Password...:****** Line::".__LINE__);
			$this->events("smb_mount() server.....:$server Line::".__LINE__);
			$this->events("smb_mount() path.......:$path Line::".__LINE__);
			$this->events("smb_mount() mount......:$mount Line::".__LINE__);
			$this->events("smb_mount() mount.cifs.:$mount_cifs Line::".__LINE__);
			$this->events("smb_mount() mount.smbfs:$mount_smbfs Line::".__LINE__);
		}
		$password_cmdline=null;
		if($username==null){
			$this->events("smb_mount() WARNING, No username defined, are you sure web can access to share in this method ???");
		}
		
		if($password<>null){
			$password=$this->shellEscapeChars($password);
			$password_cmdline=",password=$password";
		}
		
		if($username<>null){
			if(trim($password)==null){$this->events("smb_mount() WARNING no password passed to the protocol...assume password null");}
			$credentials=" -o username=$username$password_cmdline ";
		}
		
		if($username==null){
			if($password==null){
				$credentials=" -o user=,password= ";
			}
		}
		
		if($username<>null){
			if($password==null){
				$credentials=" -o user=$username,password= ";
			}
		}		
		
		
		$ip=gethostbyname($server);
		if($ip==null){
			$this->events("smb_mount() failed while resolving \"$server\"");	
			return false;
		}
		$pp=new ping($ip);
		if(!$pp->Isping()){
			if(!$pp->Isping()){
				$this->events("smb_mount() Failed to ping $ip, aborting process.");
				return false;	
			}
		}	
		$this->events("smb_mount() ping ok");	
		
		if(is_file($mount_smbfs)){
			$cmd="$mount -t smbfs$credentials//$server/$path $mountpoint";
		}
		
		
		if(is_file($mount_cifs)){
			$cmd="$mount_cifs //$server/$path $mountpoint$credentials";
			if(function_exists("writelogs")){writelogs("$cmd",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
		}
		if($cmd==null){
			$this->events("smb_mount() unable to find the right filesystem type for NAS storage");
			return;
		}
		
		$this->events("smb_mount() mounting //$server/$path -> $mountpoint");
		if($GLOBALS["VERBOSE"]){$this->events("$cmd");}
		if(!is_dir("$mountpoint")){@mkdir($mountpoint,null,true);}
		//$this->events("$cmd");
		exec($cmd,$results);
		while (list ($index, $line) = each ($results) ){
			if(function_exists("writelogs")){writelogs("$line",__CLASS__."/".__FUNCTION__,__FILE__,__LINE__);}
			$this->events("LOG:\"$line\"");
		}
		
		if($this->ismounted($mountpoint)){return true;}
		
	}
	
	public function usb_mount($mountpoint,$type,$dev){	
		
	if($this->ismounted($mountpoint)){
			$this->events("usb_mount() already mounted");
			return true;
		}
		$cmd="/bin/mount -t $type $dev $mountpoint";	
		$this->events("usb_mount() mounting $dev -> $mountpoint");
		if(!is_dir("$mountpoint")){@mkdir($mountpoint,null,true);}
		exec($cmd,$results);
		while (list ($index, $line) = each ($results) ){
			$this->events("LOG:\"$line\"");
		}
		
		if($this->ismounted($mountpoint)){return true;}		
			
			
	}
	
	private function shellEscapeChars($path){
		$path=str_replace(" ","\ ",$path);
		$path=str_replace('$','\$',$path);
		$path=str_replace("&","\&",$path);
		$path=str_replace("?","\?",$path);
		$path=str_replace("#","\#",$path);
		$path=str_replace("[","\[",$path);
		$path=str_replace("]","\]",$path);
		$path=str_replace("{","\{",$path);
		$path=str_replace("}","\}",$path);
		$path=str_replace("*","\*",$path);
		$path=str_replace('"','\\"',$path);
		$path=str_replace("'","\\'",$path);
		$path=str_replace("(","\(",$path);
		$path=str_replace(")","\)",$path);
		$path=str_replace("<","\<",$path);
		$path=str_replace(">","\>",$path);
		$path=str_replace("!","\!",$path);
		$path=str_replace("+","\+",$path);
		$path=str_replace(";","\;",$path);
		return $path;
	}	
	
	
public function MountComputerRemote($computername,$ip,$username,$password){
	$computername=str_replace("$","",$computername);
	$sourcefile_base=basename($sourcefile);
	$this->mount_point="/opt/artica/mounts/remote-$computername";
	@mkdir($this->mount_point,null,true);
	$mount_bin=$this->find_program("mount");	
	if($mount_bin==null){
		$this->events[]="[$username/$ip]:: mount program, no such file";
		return false;
	}
	
	

	if(!$this->ismounted($this->mount_point)){
		$this->events[]="[$username/$ip]:: Mount point is not mounted, mount it";
		$this->events[]="[$username/$ip]:: Mount c$ on $uidPoint [$ip]";
		$cmd="$mount_bin -t smbfs -o username=$username,password=$password //$ip/c$ $this->mount_point 2>&1";
		exec($cmd,$results);
		while (list ($index, $line) = each ($results) ){if(trim($line)==null){continue;}$this->events[]=$line;}		
		
	}else{
		$this->events[]="[$username/$ip]:: Mount point is mounted";
		
	}
	
	if(!$mount->ismounted($this->mount_point)){return false;}
	$t=time();
	$tests_path="$this->mount_point/tmp/$t";
	
	
	if(!is_dir($tests_path)){@mkdir($tests_path,null,true);	}
	if(!is_dir($tests_path)){
		$this->events[]="[$username/$ip]:: c:\tmp\{$t}, permission denied";
		exec("umount -l $mountpoint");
		return false;
	}
	@rmdir($tests_path);
	unset($results);
	
	/*
	$GLOBALS["LOCAL_FILE_PATH"]="c:\\tmp\\ocs-agent\\$sourcefile_base";
	$this->events[]="[$username/$ip]:: {$GLOBALS["MOUNTED_PATH"]}\{$sourcefile_base}, success";
	*/
	return true;
	
}

	private function find_program($strProgram) {
		$key=md5($strProgram);
		if($_SESSION["find_program"]["$strProgram"]<>null){return $_SESSION["find_program"]["$strProgram"];}
		if($GLOBALS["find_program"]["$strProgram"]<>null){return $GLOBALS["find_program"]["$strProgram"];}	
		$value=$this->internal_find_program($strProgram);
		$_SESSION["find_program"]["$strProgram"]=$value;
		$GLOBALS["find_program"]["$strProgram"]=$value;
	    return $value;
	}
	
	private function internal_find_program($strProgram){
	  global $addpaths;	
	  $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin');
	  if (addPaths !== false) {
	    $addpaths = explode(',', addPaths);
	    $arrPath = array_merge($arrPath, $addpaths);
	  }
	  if (function_exists("is_executable")) {
	    foreach($arrPath as $strPath) {
	      $strProgrammpath = $strPath . "/" . $strProgram;
	      if (is_executable($strProgrammpath)) {
	      	  return $strProgrammpath;
	      }
	    }
	  } else {
	   	return strpos($strProgram, '.exe');
	  }
	}
	
private function events($text){
	if(!isset($_GET["logcomputer"])){$_GET["logcomputer"]=null;}
	$GLOBALS["MOUNT_EVENTS"][]=$text;
	if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefile=basename($trace[1]["file"]);$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}		
	if($_GET["logcomputer"]<>null){$f = @fopen($_GET["logcomputer"], 'a');@fwrite($f,$text);@fclose($f);}
	if(!isset($GLOBALS["CLASS_UNIX"])){if(class_exists("unix")){$GLOBALS["CLASS_UNIX"]=new unix();}}
	if($this->eventspath<>null){if(isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]->events($text,$this->eventspath,false,__CLASS__."/$sourcefunction",$sourceline,$sourcefile);}}
	if(function_exists("writelogs")){writelogs("$text",__CLASS__."/$sourcefunction",$sourcefile,$sourceline);return;}
}	
	
	
}
?>