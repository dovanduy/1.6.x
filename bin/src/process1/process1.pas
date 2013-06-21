program process1;

{$mode objfpc}{$H+}

uses
  Classes, SysUtils,RegExpr,unix,baseUnix, principale,global_conf,logs,zSystem,monitorix,apachesrc,squid,artica_tcp,tcpip,lvm;
var
   P1:Tprocess1;
   i:integer;
   FileData:TStringlist;
   GLOBAL_INI:myconf;
   D:boolean;
   tcp_IP:ttcpip;
   master_exists:boolean;
   zsquid:Tsquid;
   zlogs:Tlogs;
   mypid,tmpstr,oldpid:string;
   tcp:ttcp;
   SYS:Tsystem;
   zmonitorix:tmonitorix;
   zapachesrc            :tapachesrc;
   zlvm:tlvm;

//##############################################################################
function TestAnCreatePid():boolean;
var
   Afile:TStringList;
   RegExpr:TRegExpr;
   PidString:String;
   PiDPath:string;
   myFile:TextFile;
   GLOBAL_INI:myconf;
   minutes_delayed:integer;

begin
PidString:='0';
      result:=false;
      PiDPath:='/etc/artica-postfix/artica-process1.pid';
      GLOBAL_INI:=myconf.Create;
      if FileExists(PiDPath) then begin
         minutes_delayed:=GLOBAL_INI.SYSTEM_FILE_BETWEEN_NOW(PiDPath);
      end;
      
      Afile:=TStringList.Create;
      GLOBAL_INI:=myconf.Create();
      if FIleExists(PiDPath) then Afile.LoadFromFile(PiDPath);

           RegExpr:=TRegExpr.Create;
           RegExpr.Expression:='([0-9]+)';
           if RegExpr.Exec(Afile.Text) then PidString:=RegExpr.Match[1];
           RegExpr.Free;
           Afile.Free;

           if length(PidString)>0 then begin
                 if PidString<>'0' then begin
                     if FileExists('/proc/' + PidString + '/exe') then begin
                        if minutes_delayed>4 then begin
                           fpsystem('/bin/kill -9 ' +  PidString);
                        end else begin
                            exit();
                        end;
                     end;
                 end;
           end;

     TRY
        ForceDirectories('/etc/artica-postfix');
        AssignFile(myFile, PiDPath);
        ReWrite(myFile);
        WriteLn(myFile, intTostr(fpgetpid));
        CloseFile(myFile);
      EXCEPT
            exit;
      END;
      result:=true;

end;
//##############################################################################


begin
D:=false;
GLOBAL_INI:=myconf.Create();
zlogs:=tlogs.Create;
SYS:=Tsystem.Create;

if ParamStr(1)='-V' then begin
   writeln('process1 start in debug mode');
   D:=True;
end;
if ParamStr(1)='--verbose' then begin
   writeln('process1 start in debug mode');
   D:=True;
end;
if ParamStr(2)='--verbose' then begin
   writeln('process1 start in debug mode');
   D:=True;
end;
if ParamStr(1)='--web-settings' then begin
        P1:=Tprocess1.Create(true);
        P1.web_settings(true);
        zlogs.Debuglogs('shutdown...');
        zlogs.OutputCmd('/bin/touch /etc/artica-postfix/process1.cron');
        halt(0);
end;



if ParamStr(1)='--force' then begin
        P1:=Tprocess1.Create();
        zlogs.Debuglogs('shutdown...');
        zlogs.OutputCmd('/bin/touch /etc/artica-postfix/process1.cron');
        halt(0);
end;
if ParamStr(2)='--force' then begin
        P1:=Tprocess1.Create();
        zlogs.Debuglogs('shutdown...');
        zlogs.OutputCmd('/bin/touch /etc/artica-postfix/process1.cron');
        halt(0);
end;


if ParamStr(1)='--nickernel' then begin
   tcp_IP:=ttcpip.Create;
   tcp_IP.InterfacesStringListMEM();
   for i:=0 to tcp_IP.MEMORY_LIST_NIC.Count -1 do begin
         writeln(tcp_IP.MEMORY_LIST_NIC.Strings[i]);
   end;
   halt(0);
end;

if ParamStr(1)='--nicstatus' then begin
       GLOBAL_INI:=myconf.Create();
       tmpstr:=GLOBAL_INI.SYSTEM_GET_LOCAL_IP(ParamStr(2));
       tmpstr:=tmpstr+';'+GLOBAL_INI.SYSTEM_GET_LOCAL_MAC(ParamStr(2));
       tmpstr:=tmpstr+';'+GLOBAL_INI.SYSTEM_GET_LOCAL_MASK(ParamStr(2));
       tmpstr:=tmpstr+';'+GLOBAL_INI.SYSTEM_GET_LOCAL_BROADCAST(ParamStr(2));
       tmpstr:=tmpstr+';'+GLOBAL_INI.SYSTEM_GET_LOCAL_GATEWAY(ParamStr(2));
       if GLOBAL_INI.IsWireless(ParamStr(2)) then tmpstr:=tmpstr+';yes' else tmpstr:=tmpstr+';no';
       if GLOBAL_INI.IsIfaceDown(ParamStr(2)) then tmpstr:=tmpstr+';yes' else tmpstr:=tmpstr+';no';
       writeln(tmpstr);
       halt(0);
end;


if ParamStr(1)='--disk-scan' then begin
   zlvm:=tlvm.Create(SYS);
   FileData:=Tstringlist.CReate;
   GLOBAL_INI:=myconf.Create;
   FileData.Add('<?php');
   FileData.Add(GLOBAL_INI.SCAN_USB());
   FileData.Add('');
   FileData.Add('// Disks list...');
   FileData.Add('');
   FileData.Add(GLOBAL_INI.SCAN_DISK_PHP());
   FileData.Add('');
   FileData.Add('// lvm list...');
   FileData.Add('');
   FileData.Add(zlvm.SCAN_DISKS());
   FileData.Add(zlvm.SCAN_DEV());
   FileData.Add('');
   FileData.Add('// lvm group list...');
   FileData.Add('');
   FileData.Add(zlvm.SCAN_VG());
   FileData.Add('?>');
   if DirectoryExists('/opt/artica-agent/usr/share/artica-agent/ressources') then zlogs.WriteToFile(FileData.Text,'/opt/artica-agent/usr/share/artica-agent/ressources/usb.scan.inc');
   if DirectoryExists(GLOBAL_INI.get_ARTICA_PHP_PATH()+'/ressources')  then begin
      zlogs.WriteToFile(FileData.Text,GLOBAL_INI.get_ARTICA_PHP_PATH()+'/ressources/usb.scan.inc');
      zlogs.OutputCmd('/bin/chmod 755 '+GLOBAL_INI.get_ARTICA_PHP_PATH()+'/ressources/usb.scan.inc >/dev/null 2>&1');
   end;
   FileData.free;
   zlvm.free;
   GLOBAL_INI.free;
   halt(0);
end;


if ParamStr(1)='--nicinfos' then begin
       GLOBAL_INI:=myconf.Create();
       writeln(GLOBAL_INI.SYSTEM_NETWORK_INFO_NIC(ParamStr(2)));
       halt(0);
end;


 if ParamStr(1)='--itk-module' then begin
    zapachesrc:=tapachesrc.CREATE(SYS);
    if zapachesrc.MODULE_EXISTS('mpm_itk_module') then writeln('$_GLOBAL["MPM_ITK_MODULE"]=True;') else  writeln('$_GLOBAL["MPM_ITK_MODULE"]=False;');
    halt(0);
 end;
  if ParamStr(1)='--squid-arp' then begin
    zsquid:=tsquid.CREATE();
    writeln('SQUID_ARP_ACL_ENABLED:', zsquid.SQUID_ARP_ACL_ENABLED());
    halt(0);
 end;

  if ParamStr(1)='--agent-os-softs' then begin
    GLOBAL_INI.CGI_ALL_APPLIS_INSTALLED();
    zlogs.WriteToFile(GLOBAL_INI.ArrayList.Text,'/opt/artica-agent/usr/share/artica-agent/ressources/versions.conf');
    halt(0);
 end;





if not D then  D:= GLOBAL_INI.COMMANDLINE_PARAMETERS('debug');
mypid:=IntToStr(fpgetpid);
if ParamStr(1)='--local-sid' then begin
        GLOBAL_INI.SYSTEM_LOCAL_SID();
        halt(0);
end;

if ParamStr(1)='--ldap' then begin
   P1:=Tprocess1.Create();
   P1.TestLDAP();
   halt(0);
end;

if ParamStr(1)='--cpup' then begin
       try
       writeln(SYS.GET_CPU_POURCENT(StrToInt(ParamStr(2))));
       finally
       end;
       halt(0);
end;
oldpid:=SYS.PIDOF_PATTERN('process1 '+ParamStr(1));
if SYS.PROCESS_EXIST(oldpid) then begin
     zlogs.Debuglogs('Aborting command line process1 '+ParamStr(1) +' Already executed PID:'+oldpid);
     halt(0);
end;


if ParamStr(1)='time' then begin
   writeln('running since: ',SYS.PROCCESS_TIME_MIN(ParamStr(2)),' mn');
   halt(0);
end;



 if ParamStr(1)='--pear' then begin
    writeln(SYS.PEAR_MODULES());
     halt(0);
 end;

if ParamStr(1)='--checkout' then begin
   P1:=Tprocess1.Create();
   halt(0);
end;



if ParamStr(1)='--help' then begin
   writeln('-V................: Run in debug mode');
   writeln('debug.............: Add debug infos');
   writeln('-perm.............: Set permissions');
   writeln('-mysql............: Parse mysql queue and perform queries');
   writeln('--start...........: Start services');
   writeln('--start --force...: Start services even the master process doesn''t exists');


   D:=True;
   halt(0);
end;

    if length(ParamStr(1))>0 then zlogs.Debuglogs('Receive "' +ParamStr(1)+'"');

 if ParamStr(1)='--iostat' then begin
          P1:=Tprocess1.Create();
          writeln(P1.IOSTAT());
          halt(0);
     end;

 if ParamStr(1)='--cpulimit' then begin
          P1:=Tprocess1.Create();
          P1.CleanCpulimit();
          halt(0);
     end;


 if ParamStr(1)='--inodes' then begin
          SYS.verbosed:=true;
          writeln(SYS.DISKS_INODE_DEV());
          halt(0);
     end;




     if ParamStr(1)='-perm' then begin
          halt(0);
     end;
     
     if ParamStr(1)='-kasstat' then begin
          P1:=Tprocess1.Create();
          P1.move_kas3_stats();
          halt(0);
     end;
     

     if ParamStr(1)='-exec' then begin
          if SYS.croned_seconds(5) then begin
          writeln('start ' +  ParamStr(2));
          fpsystem(ParamStr(2));
          writeln('handle done');
          end;
          halt(0);
     end;

    if ParamStr(1)='--mysql-status' then begin
       writeln(SYS.MYSQL_STATUS());
       halt(0);
    end;

 if ParamStr(1)='--kill' then begin
         P1:=Tprocess1.Create();
         P1.KillsfUpdatesBadProcesses();
         halt(0);
     end;
     

 if ParamStr(1)='--cleanlogs' then begin
         P1:=Tprocess1.Create();
         P1.cleanlogs();
         writeln('Memory used: ',SYS.PROCESS_MEMORY(mypid));
         halt(0);
     end;

 if ParamStr(1)='--mailgraph' then begin
         P1:=Tprocess1.Create();
         P1.mailgraph_log();
         halt(0);
     end;
     

 if ParamStr(1)='--monitorix' then begin
          zmonitorix:=Tmonitorix.Create;
          zmonitorix.Start();

          halt(0);
     end;



     
     
     master_exists:=GLOBAL_INI.SYSTEM_PROCESS_EXIST(GLOBAL_INI.ARTICA_DAEMON_GET_PID());
     if ParamStr(1)='--force' then begin
        master_exists:=true;
        P1:=Tprocess1.Create();
        halt(0);
     end;
        
     
     if ParamStr(1)='--start' then begin
              if D then writeln('starting all artica-postfix services....');
              if master_exists then begin
                 if D then writeln('Sleep 700');
                 sleep(700);
                 if D then writeln('-> SYSTEM_START_ARTICA_ALL_DAEMON()');
                 GLOBAL_INI.SYSTEM_START_ARTICA_DAEMON();
              end else begin
                 if D then writeln('Artica-postfix daemon is stopped, aborting');
              end;
      halt(0);
     end;
     

     mypid:=SYS.GET_PID_FROM_PATH('/etc/artica-postfix/artica-process1.pid');
     if SYS.PROCESS_EXIST(mypid) then begin
         zlogs.Debuglogs('Tprocess1.Create():: '+mypid+' PID Already exists..Abort');
         halt(0);
     end;


     if not TestAnCreatePid() then begin
        zlogs.Debuglogs('TestAnCreatePid() return false, shutdown...');
        halt(0);
     end;
     
     if Not FileExists('/etc/artica-postfix/process1.cron') then zlogs.OutputCmd('/bin/touch /etc/artica-postfix/process1.cron');
     
     if SYS.FILE_TIME_BETWEEN_SEC('/etc/artica-postfix/process1.cron')>20 then begin
        zlogs.Debuglogs('Tprocess1.Create();');
        P1:=Tprocess1.Create();
        zlogs.Debuglogs('shutdown...');
        zlogs.OutputCmd('/bin/touch /etc/artica-postfix/process1.cron');
     end;

     
     zlogs.Debuglogs('----------------------------------------------------------------------');
halt(0);
end.

