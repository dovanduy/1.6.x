unit dhcp_server;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils, Process,logs,unix,tcpip,
    RegExpr in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/RegExpr.pas',
    zsystem in '/home/dtouzeau/developpement/artica-postfix/bin/src/artica-install/zsystem.pas';


  type
  tdhcp3=class


private
     LOGS:Tlogs;
     artica_path:string;
     SYS:Tsystem;
     EnableDHCPServer:integer;
     function DAEMON_PID():string;
     function READ_PID():string;
     function PID_PATH():string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    function  STATUS():string;
    function  BIN_PATH():string;
    procedure START();
    procedure STOP();
    function  VERSION():string;
    function  INIT_PATH():string;
    function  DEFAULT_PATH():string;
    function  CONF_PATH():string;

    procedure RELOAD();

END;

implementation

constructor tdhcp3.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       EnableDHCPServer:=0;
       SYS:=zSYS;
       
       if not TryStrToInt(SYS.GET_INFO('EnableDHCPServer'),EnableDHCPServer) then EnableDHCPServer:=0;
       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tdhcp3.free();
begin
    logs.Free;
end;
//##############################################################################
function tdhcp3.BIN_PATH():string;
begin
    if FileExists('/usr/sbin/dhcpd') then exit('/usr/sbin/dhcpd');
    if FileExists('/usr/sbin/dhcpd3') then exit('/usr/sbin/dhcpd3');
end;
//#############################################################################
function tdhcp3.INIT_PATH():string;
begin
    if FileExists('/etc/init.d/dhcpd') then exit('/etc/init.d/dhcpd');
    if FileExists('/etc/init.d/dhcp3-server') then exit('/etc/init.d/dhcp3-server');
//etc/sysconfig/dhcpd
///etc/default/dhcp3-server
end;
//#############################################################################
function tdhcp3.CONF_PATH():string;
begin
    if FIleExists('/etc/dhcp3/dhcpd.conf') then exit('/etc/dhcp3/dhcpd.conf');
    if FIleExists('/etc/dhcpd.conf') then exit('/etc/dhcpd.conf');
    if FIleExists('/etc/dhcpd/dhcpd.conf') then exit('/etc/dhcpd/dhcpd.conf');
    result:='/etc/dhcp3/dhcpd.conf';
end;
//#############################################################################
function tdhcp3.DEFAULT_PATH():string;
begin
    if FIleExists('/etc/default/dhcp3-server') then exit('/etc/default/dhcp3-server');
    if FIleExists('/etc/sysconfig/dhcpd') then exit('/etc/sysconfig/dhcpd');
end;
//#############################################################################
function tdhcp3.VERSION():string;
var
   i:integer;
   l:Tstringlist;
   RegExpr:TRegExpr;
   tmpstr:string;
begin
   if not FileExists(BIN_PATH()) then exit;
   
   result:=SYS.GET_CACHE_VERSION('APP_DHCP');
   if length(result)>0 then exit;
   
   RegExpr:=TRegExpr.Create;
   tmpstr:=LOGS.FILE_TEMP();
   fpsystem(BIN_PATH() + ' -h >'+tmpstr+' 2>&1');
   if not FileExists(tmpstr) then exit;
   l:=TstringList.Create;
   l.LoadFromFile(tmpstr);
   logs.DeleteFile(tmpstr);
   for i:=0 to l.Count-1 do begin
       RegExpr.Expression:='V([0-9\.]+)';
       if RegExpr.Exec(l.Strings[i]) then begin
          result:=RegExpr.Match[1];
          break;
       end;

       RegExpr.Expression:='Internet Systems Consortium DHCP Server\s+([0-9\.a-z]+)';
       if RegExpr.Exec(l.Strings[i]) then begin
          result:=RegExpr.Match[1];
          break;
       end;



   end;
   
   l.Free;
   RegExpr.Free;
   SYS.SET_CACHE_VERSION('APP_DHCP',result);
   
end;
//#############################################################################
function tdhcp3.DAEMON_PID():string;
var
   pid:string;
begin
   pid:=READ_PID();
   if length(pid)=0 then pid:=SYS.PIDOF(BIN_PATH());
   exit(pid);
end;
//##############################################################################
function tdhcp3.PID_PATH():string;
begin
if FileExists('/var/run/dhcpd.pid') then exit('/var/run/dhcpd.pid');
if FileExists('/var/run/dhcpd/dhcpd.pid') then exit('/var/run/dhcpd/dhcpd.pid');
if FileExists('/var/run/dhcp3-server/dhcpd.pid') then exit('/var/run/dhcp3-server/dhcpd.pid');
result:='/var/run/dhcp3-server/dhcpd.pid';
end;
function tdhcp3.READ_PID():string;
begin
exit(SYS.GET_PID_FROM_PATH(PID_PATH()));
end;
//##############################################################################
procedure tdhcp3.RELOAD();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.dhcpd.compile.php --reload');
end;
//##############################################################################

procedure tdhcp3.START();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.dhcpd.compile.php --start');
end;
//##############################################################################

procedure tdhcp3.STOP();
begin
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.dhcpd.compile.php --stop');
end;
//##############################################################################
function tdhcp3.STATUS():string;
var
   ini:TstringList;
   pidpath:string;
begin
SYS.MONIT_DELETE('APP_DHCP');
if not FileExists(BIN_PATH()) then exit;
pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --dhcpd >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath);
ini:=TstringList.Create;
end;
//#########################################################################################
end.

