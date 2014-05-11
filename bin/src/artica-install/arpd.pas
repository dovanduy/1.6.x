unit arpd;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,IniFiles,tcpip;



  type
  tarpd=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     artica_path:string;
     EnableArpDaemon:integer;
     binpath:string;
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);
    procedure   START();
    procedure   STOP();
    function    BIN_PATH():string;
    function    PID_NUM():string;
   procedure    RELOAD();



END;

implementation

constructor tarpd.Create(const zSYS:Tsystem);
begin

       LOGS:=tlogs.Create();
       SYS:=zSYS;
       binpath:=BIN_PATH();
       if not TryStrToInt(SYS.GET_INFO('EnableArpDaemon'),EnableArpDaemon) then EnableArpDaemon:=1;

end;
//##############################################################################
procedure tarpd.free();
begin
    logs.Free;
end;
//##############################################################################
procedure tarpd.STOP();
var
   count:integer;
   RegExpr:TRegExpr;
   cmd:string;
   pids:Tstringlist;
   pidstring:string;
   fpid,i:integer;
begin
if not FileExists(binpath) then begin
   writeln('Stopping ARP Daemon..........: Not installed');
   exit;
end;

if not SYS.PROCESS_EXIST(PID_NUM()) then begin
      writeln('Stopping ARP Daemon..........: Already Stopped');
      exit;
end;
   pidstring:=PID_NUM();
   writeln('Stopping ARP Daemon..........: ' + pidstring + ' PID..');
   cmd:=SYS.LOCATE_GENERIC_BIN('kill')+' -9 '+pidstring+' >/dev/null 2>&1';
   fpsystem(cmd);

   count:=0;
   while SYS.PROCESS_EXIST(pidstring) do begin
        sleep(200);
        count:=count+1;
        if count>50 then begin
            if length(pidstring)>0 then begin
               if SYS.PROCESS_EXIST(pidstring) then begin
                  writeln('Stopping ARP Daemon..........: kill pid '+ pidstring+' after timeout');
                  fpsystem('/bin/kill -9 ' + pidstring);
               end;
            end;
            break;
        end;
        pidstring:=PID_NUM();
  end;

  count:=0;
  pids:=Tstringlist.Create;
  pids.AddStrings(SYS.PIDOF_PATTERN_PROCESS_LIST(bin_path));
  writeln('Stopping ARP Daemon..........: ',pids.Count,' childrens.');
  for i:=0 to pids.Count-1 do begin
        if not TryStrToInt(pids.Strings[i],fpid) then continue;
        if fpid>2 then begin
              writeln('Stopping ARP Daemon..........: kill pid ',fpid);
              fpsystem('/bin/kill -9 '+ IntToStr(fpid));
        end;
  end;

  if not SYS.PROCESS_EXIST(PID_NUM()) then    writeln('Stopping ARP Daemon..........: success');
end;

//##############################################################################
function tarpd.BIN_PATH():string;
begin
result:=SYS.LOCATE_GENERIC_BIN('arpd');
end;
//##############################################################################
procedure tarpd.RELOAD();
var
   pid:string;
begin
pid:=PID_NUM();

if SYS.PROCESS_EXIST(pid) then begin
   logs.DebugLogs('Starting......: ARP Daemon reload PID ' +pid+ '...');
   fpsystem('/bin/kill -HUP '+ pid);
   exit;
end;
   START();

end;
//##############################################################################


procedure tarpd.START();
var
   count,i:integer;
   cmd:string;
   su,nohup:string;
   conf:TiniFile;
   enabled:integer;
   RegExpr:TRegExpr;
   servername:string;
   tmpfile:string;
   cmdline:string;
   zinterfaces:string;
   tcp_IP:ttcpip;
   ArpdKernelLevel:integer;
   ArpdKernelLevel_string:string;
begin

     if not TryStrToInt(SYS.GET_INFO('ArpdKernelLevel'),ArpdKernelLevel) then ArpdKernelLevel:=0;


   if not FileExists(binpath) then begin
         logs.DebugLogs('Starting......: ARP Daemon is not installed');
         exit;
   end;



if EnableArpDaemon=0 then begin
   logs.DebugLogs('Starting......: ARP Daemon is disabled');
   STOP();
   exit;
end;
if ArpdKernelLevel=0 then begin
          logs.DebugLogs('Starting......: ARP Daemon without kernel helper');
end else begin
    logs.DebugLogs('Starting......: ARP Daemon kernel level '+IntTOStr(ArpdKernelLevel));
end;

if SYS.PROCESS_EXIST(PID_NUM()) then begin
   logs.DebugLogs('Starting......: ARP Daemon Already running using PID ' +PID_NUM()+ '...');
   exit;
end;

   tcp_IP:=ttcpip.Create;
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^.+?:.+';
   tcp_IP.InterfacesStringListMEM();
   for i:=0 to tcp_IP.MEMORY_LIST_NIC.Count -1 do begin
         if trim(tcp_IP.MEMORY_LIST_NIC.Strings[i])='lo' then continue;
         if RegExpr.Exec(tcp_IP.MEMORY_LIST_NIC.Strings[i]) then continue;
         logs.DebugLogs('Starting......: ARP Daemon hook '+tcp_IP.MEMORY_LIST_NIC.Strings[i]+' interface');
         zinterfaces:=zinterfaces+' '+ tcp_IP.MEMORY_LIST_NIC.Strings[i];
   end;

   zinterfaces:=trim(zinterfaces);
   if length(zinterfaces)=0 then begin
        logs.DebugLogs('Starting......: ARP Daemon no interface found, aborting !');
        exit;
   end;
  if ArpdKernelLevel>0 then ArpdKernelLevel_string:=' -a '+IntToStr(ArpdKernelLevel);
   ForceDirectories('/var/lib/arpd');
   cmd:=binpath +' -b /var/lib/arpd/arpd.db'+ArpdKernelLevel_string+' -k '+zinterfaces+' &';
   fpsystem(cmd);
   count:=0;
   while not SYS.PROCESS_EXIST(PID_NUM()) do begin
     sleep(300);
     inc(count);
     if count>50 then begin
       logs.DebugLogs('Starting......: ARP Daemon (timeout!!!)');
       logs.DebugLogs('Starting......: ARP Daemon "'+cmd+'"');
       break;
     end;
   end;

   if not SYS.PROCESS_EXIST(PID_NUM()) then begin
       logs.DebugLogs('Starting......: ARP Daemon (failed!!!)');
       logs.DebugLogs('Starting......: ARP Daemon "'+cmd+'"');
   end else begin
       logs.DebugLogs('Starting......: ARP Daemon started with new PID '+PID_NUM());
   end;

end;
//##############################################################################
 function tarpd.PID_NUM():string;
begin
  result:=SYS.PIDOF(binpath);
  if sys.verbosed then logs.Debuglogs('PID_NUM():: '+binpath+'  -> '+result);
end;
 //##############################################################################
end.
