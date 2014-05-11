unit artica_cron;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,IniFiles, Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,kas3,isoqlog,squid,fetchmail,spamass,cyrus,postfix_class;



  type
  tcron=class


private
     LOGS:Tlogs;
     SYS:TSystem;
     zpostfix:tpostfix;
     artica_path:string;
     EnableMilterSpyDaemon:integer;
     RetranslatorEnabled:integer;
     RetranslatorCronMinutes:integer;
     EnableArticaStatus:integer;
     EnableArticaExecutor:integer;
     EnableArticaBackground:integer;
     IsoQlogRetryTimes:integer;
     isoqlog:tisoqlog;
     function ARTICA_VERSION():string;
     procedure save_cyrus_backup();
     procedure save_cyrus_scan();
public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);

    function    PID_NUM():string;
    procedure   STOP();
    procedure   Save_processes();
    procedure   Save_processes_watchdog();
    function    FCRON_VERSION():string;
    procedure   WATCHDOG_START();
    function    WATCHDOG_PID_NUM():string;
    procedure   STOP_WATCHDOG();
    function    STATUS():string;
    procedure   quarantine_report_schedules();



END;

implementation

constructor tcron.Create(const zSYS:Tsystem);
begin
       forcedirectories('/etc/artica-postfix');
       LOGS:=tlogs.Create();
       SYS:=zSYS;
EnableMilterSpyDaemon:=0;
RetranslatorEnabled:=0;
RetranslatorCronMinutes:=60;
IsoQlogRetryTimes:=30;

isoqlog:=tisoqlog.Create(SYS);

if not TryStrToInt(SYS.GET_INFO('RetranslatorEnabled'),RetranslatorEnabled) then RetranslatorEnabled:=0;
if not TryStrToInt(SYS.GET_INFO('RetranslatorCronMinutes'),RetranslatorCronMinutes) then RetranslatorCronMinutes:=60;
if not TryStrToInt(SYS.GET_INFO('EnableMilterSpyDaemon'),EnableMilterSpyDaemon) then EnableMilterSpyDaemon:=0;
if not TryStrToInt(SYS.GET_INFO('IsoQlogRetryTimes'),IsoQlogRetryTimes) then IsoQlogRetryTimes:=30;
if not TryStrToInt(SYS.GET_INFO('EnableArticaStatus'),EnableArticaStatus) then EnableArticaStatus:=1;
if not TryStrToInt(SYS.GET_INFO('EnableArticaExecutor'),EnableArticaExecutor) then EnableArticaExecutor:=1;
if not TryStrToInt(SYS.GET_INFO('EnableArticaBackground'),EnableArticaBackground) then EnableArticaBackground:=1;




       if not DirectoryExists('/usr/share/artica-postfix') then begin
              artica_path:=ParamStr(0);
              artica_path:=ExtractFilePath(artica_path);
              artica_path:=AnsiReplaceText(artica_path,'/bin/','');

      end else begin
          artica_path:='/usr/share/artica-postfix';
      end;
end;
//##############################################################################
procedure tcron.free();
begin
    logs.Free;
    isoqlog.Free;
end;
//##############################################################################
function tcron.PID_NUM():string;
var pid:string;
begin
     pid:=SYS.GET_PID_FROM_PATH('/var/run/artica-postfix.pid');
     if not SYS.PROCESS_EXIST(pid) then pid:=SYS.PIDOF_PATTERN('/usr/share/artica-postfix/bin/artica-cron.+?artica-cron.conf');
     result:=pid;
end;
//##############################################################################
function tcron.WATCHDOG_PID_NUM():string;
var pid:string;
begin
     pid:=SYS.GET_PID_FROM_PATH('/var/run/artica-watchdog.pid');
     if not SYS.PROCESS_EXIST(pid) then pid:=SYS.PIDOF_PATTERN('/usr/share/artica-postfix/bin/artica-cron.+?watchdog.conf');
     result:=pid;
end;
//##############################################################################

procedure tcron.WATCHDOG_START();
var
   l:TstringList;
   pid:string;
   parms:string;
   count:integer;
begin
  exit;
  pid:=WATCHDOG_PID_NUM();
  count:=0;
   if SYS.PROCESS_EXIST(pid) then begin
      logs.DebugLogs('Starting......: artica-postfix daemon watchdog (fcron) is already running using PID ' + pid + '...');
      exit;
   end;
  fpsystem('/bin/rm -rf /etc/artica-cron/spool_watchdog/*');
  forceDirectories('/etc/artica-cron/spool_watchdog');
  l:=Tstringlist.Create;
  l.Add('fcrontabs=/etc/artica-cron/spool_watchdog');
  l.Add('pidfile=/etc/artica-cron/artica-watchdog.pid');
  l.Add('fifofile=/etc/artica-cron/artica-watchdog.fifo');
  l.Add('fcronallow=/etc/artica-cron/artica-watchdog.allow');
  l.Add('fcrondeny=/etc/artica-cron/artica-watchdog.deny');
  l.Add('shell=/bin/sh');
  l.SaveToFile('/etc/artica-cron/artica-watchdog.conf');
  l.SaveToFile('/etc/artica-cron/watchdog.conf');
  l.free;

  fpsystem('/bin/chown root:root /etc/artica-cron/watchdog.conf');
  fpsystem('/bin/chown -R root:root /etc/artica-cron/spool_watchdog');
  fpsystem('/bin/chmod 644 /etc/artica-cron/artica-watchdog.conf');



  parms:=artica_path + '/bin/artica-cron --background --savetime 1800 --maxserial 10 --firstsleep 10 --queuelen 20 --configfile /etc/artica-cron/artica-watchdog.conf';
  Save_processes_watchdog();
  logs.DebugLogs('Starting......: artica-postfix daemon watchdog (fcron)');
  logs.OutputCmd(parms);
  logs.DebugLogs('tcron.WATCHDOG_START(): delete root config');
  logs.DeleteFile('/etc/artica-cron/spool_watchdog/root');

  while not SYS.PROCESS_EXIST(WATCHDOG_PID_NUM()) do begin
        sleep(500);
        count:=count+1;
        logs.DebugLogs('tcron.START(): wait sequence ' + intToStr(count));
        if count>20 then begin
            logs.DebugLogs('Starting......: artica-postfix daemon watchdog (fcron) failed...');
            logs.DebugLogs('Starting......: '+parms);
            exit;
        end;
  end;
  logs.DebugLogs('Starting......: Installing watchdog crontab');
  fpsystem('/bin/chmod 644 /etc/artica-cron/artica-watchdog.conf');
  fpsystem('/bin/chmod 644 /etc/artica-cron/watchdog.conf');
  fpsystem('/bin/chown root:root /etc/artica-cron/artica-watchdog.conf');
  fpsystem(artica_path + '/bin/fcrontab -z root -c /etc/artica-cron/watchdog.conf >/tmp/watchdog.tmp');
  logs.DebugLogs('Starting......: artica-postfix daemon watchdog ' + logs.ReadFromFile('/tmp/watchdog.tmp'));
  logs.Syslogs('Success starting artica-cron watchdog daemon...');
  logs.DebugLogs('Starting......: artica-postfix daemon watchdog (fcron) success...');
  fpsystem('/usr/share/artica-postfix/bin/fcronsighup /etc/artica-cron/artica-cron.conf');
  fpsystem('/usr/share/artica-postfix/bin/fcronsighup /etc/artica-cron/artica-watchdog.conf');
  SYS.THREAD_COMMAND_SET(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.c-icap.php --maint-schedule');
end;
//##############################################################################
procedure tcron.STOP_WATCHDOG();
var
   pid:string;
   count:integer;
begin
pid:=PID_NUM();
count:=0;
if SYS.PROCESS_EXIST(WATCHDOG_PID_NUM()) then begin
   writeln('Stopping artica-cron watchdog (fcron).: ' + pid + ' PID..');
   fpsystem('/bin/kill ' + pid);
end;
  while SYS.PROCESS_EXIST(WATCHDOG_PID_NUM()) do begin
        sleep(100);
        count:=count+1;
        if count>20 then begin
            fpsystem('/bin/kill -9 ' + WATCHDOG_PID_NUM());
            break;
        end;
  end;
pid:=SYS.AllPidsByPatternInPath('bin/artica-cron --background');
if length(pid)>0 then begin
   writeln('Stopping artica-cron watchdog (fcron).: '+ pid + '...');
   fpsystem('/bin/kill ' + pid);
end;

logs.Syslogs('Stopping artica-cron watchdog (fcron).: success...');
writeln('Stopping artica-cron watchdog (fcron).: success...');
logs.NOTIFICATION('[ARTICA]:('+sys.HOSTNAME_g()+') Artica watchdog daemon was stopped !!','','system');
end;

//#############################################################################


procedure tcron.STOP();
var
   pid:string;
   count:integer;
begin
pid:=PID_NUM();
count:=0;
if SYS.PROCESS_EXIST(pid) then begin
   writeln('Stopping artica-cron (fcron).: ' + pid + ' PID..');
   fpsystem('/bin/kill ' + pid);
end;





  while SYS.PROCESS_EXIST(PID_NUM()) do begin
        sleep(100);
        count:=count+1;
        if count>20 then begin
            fpsystem('/bin/kill -9 ' + pid);
            break;
        end;
  end;
pid:=SYS.AllPidsByPatternInPath('bin/artica-cron --background');
 while length(pid)>0 do begin
   writeln('Stopping artica-cron (fcron).: other pid: '+ pid + '...');
   fpsystem('/bin/kill ' + pid);
   pid:=SYS.AllPidsByPatternInPath('bin/artica-cron --background');
end;

logs.Syslogs('Stopping artica-cron (fcron).: success...');
writeln('Stopping artica-cron (fcron).: success...');


   
end;

//##############################################################################
procedure tcron.Save_processes();

         const
            CR = #$0d;
            LF = #$0a;
            CRLF = CR + LF;

var l:TstringList;

Nice:integer;
Nicet:string;
cmdnice:string;
nolog:string;
backup_time:string;
backup_min:string;
backup_hour:string;
backup_min_int:Integer;
backup_hour_int:Integer;
backup_command:string;
schedule_time:string;
backups:Tstringlist;
fetchmailrcs:Tstringlist;
RegExpr:TRegExpr;
ini:TiniFile;
tmp:string;
i:integer;
systemMaxOverloaded:integer;
squid:Tsquid;
WifiAPEnable:integer;
EnableFetchmail:integer;
fetchmail:tfetchmail;
PostfixPostmaster:string;
php5bin:string;
spamass:Tspamass;
cyrus:Tcyrus;
ArticaStatusadded:boolean;
logrotatebin:string;
EnableSnort:integer;

begin
      nolog:=',nolog(true)';
      l:=TstringList.Create;
      backup_command:='';
      ArticaStatusadded:=false;
      zpostfix:=Tpostfix.Create(SYS);

      if not TryStrToInt(SYS.GET_INFO('EnableFetchmail'),EnableFetchmail) then EnableFetchmail:=0;
      if not TryStrToInt(SYS.GET_INFO('EnableArticaBackground'),EnableArticaBackground) then EnableArticaBackground:=1;
      if not TryStrToInt(SYS.GET_INFO('EnableSnort'),EnableSnort) then EnableSnort:=0;



      tmp:=SYS.GET_PERFS('ProcessNice');
      if not TryStrToInt(tmp,Nice) then Nice:=19;
      Nicet:='nice('+IntToStr(Nice)+'),mail(false)';
      cmdnice:=SYS.EXEC_NICE();
       logs.DebugLogs('Starting......: Daemon (fcron) nice command is "'+cmdnice+'"');
      logs.DeleteFile('/etc/cron.d/artica.cron.backups');
      logs.DeleteFile('/etc/cron.d/artica.cron.backup');
      logs.DeleteFile('/etc/cron.d/artica-cron-backup');
      logs.DeleteFile('/etc/cron.d/artica-cron-dansguardian');
      logs.DeleteFile('/etc/cron.d/artica-isoqlog');
      logs.DeleteFile('/etc/cron.d/artica-cron-sarg');
      logs.DeleteFile('/etc/cron.d/artica-cron-quarantine');
      logs.DeleteFile('/etc/cron.d/artica-cron-sharedfolders');
      logs.DeleteFile('/etc/cron.d/artica-cron-mailbackup');
      logs.DeleteFile('/etc/cron.d/artica-cron-mysqldb');
logs.DeleteFile('/etc/cron.d/artica-cron-urgency');
logs.DeleteFile('/etc/cron.d/artica-cron-orders');
logs.DeleteFile('/etc/cron.d/artica-cron-quar-disk');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-0');
logs.DeleteFile('/etc/cron.d/artica-cron-cups-drv');
logs.DeleteFile('/etc/cron.d/artica-isoqlog');
logs.DeleteFile('/etc/cron.d/artica-cron-orgstats');
logs.DeleteFile('/etc/cron.d/artica-cron-process1f');
logs.DeleteFile('/etc/cron.d/artica-watch-queue');
logs.DeleteFile('/etc/cron.d/artica-cron-watchdog');
logs.DeleteFile('/etc/cron.d/artica-cron-spamblacklists');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-120');
logs.DeleteFile('/etc/cron.d/artica-cron-postfixiptables');
logs.DeleteFile('/etc/cron.d/artica-cron-status');
logs.DeleteFile('/etc/cron.d/artica-cron-buildhomes');
logs.DeleteFile('/etc/cron.d/artica-cron-adminstatus1');
logs.DeleteFile('/etc/cron.d/artica-cron-process1k');
logs.DeleteFile('/etc/cron.d/artica-cron-postfixloggerflow');
logs.DeleteFile('/etc/cron.d/artica-cron-patchs');
logs.DeleteFile('/etc/cron.d/artica-cron-clamvupd');
logs.DeleteFile('/etc/cron.d/artica-cron-mysqlq');
logs.DeleteFile('/etc/cron.d/artica-clean-smtplogs');
logs.DeleteFile('/etc/cron.d/artica-cron-mailarchive');
logs.DeleteFile('/etc/cron.d/artica-cron-exec');
logs.DeleteFile('/etc/cron.d/artica-cron-vacation');
logs.DeleteFile('/etc/cron.d/artica-cron-apt');
logs.DeleteFile('/etc/cron.d/artica-cron-quarantines');
logs.DeleteFile('/etc/cron.d/artica-cron-notifs');
logs.DeleteFile('/etc/cron.d/artica-cron-checkvirusqueue');
logs.DeleteFile('/etc/cron.d/artica-cron-parse-dar');
logs.DeleteFile('/etc/cron.d/artica-cron-awstats');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-5');
logs.DeleteFile('/etc/cron.d/artica-remoteinstall');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-2');
logs.DeleteFile('/etc/cron.d/artica-cron-backcyrus0');
logs.DeleteFile('/etc/cron.d/artica-cron-syncmodules');
logs.DeleteFile('/etc/cron.d/artica-cron-geoip');
logs.DeleteFile('/etc/cron.d/artica-cron-cyrusav');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-10');
logs.DeleteFile('/etc/cron.d/artica-cron-process1');
logs.DeleteFile('/etc/cron.d/artica-squidRRD0');
logs.DeleteFile('/etc/cron.d/artica-process1');
logs.DeleteFile('/etc/cron.d/artica-cron-smtplastmails');
logs.DeleteFile('/etc/cron.d/artica-cron-fetchsql');
logs.DeleteFile('/etc/cron.d/artica-cron-sarg');
logs.DeleteFile('/etc/cron.d/artica-cron-postfixlogger');
logs.DeleteFile('/etc/cron.d/artica-cron-topcpumem');
logs.DeleteFile('/etc/cron.d/artica-cron-mailgraph');
logs.DeleteFile('/etc/cron.d/artica-cron-backcyrus2');
logs.DeleteFile('/etc/cron.d/artica-cron-adminsmtpflow');
logs.DeleteFile('/etc/cron.d/artica-cron-adminstatus2');
logs.DeleteFile('/etc/cron.d/artica-cron-iso');
logs.DeleteFile('/etc/cron.d/artica-cron-rsynclogs');
logs.DeleteFile('/etc/cron.d/artica-cron-update');
logs.DeleteFile('/etc/cron.d/artica-cron-wblphp');
logs.DeleteFile('/etc/cron.d/artica-cron-executor-300');
logs.DeleteFile('/etc/cron.d/artica-meta-agent');
logs.DeleteFile('/etc/cron.d/artica-meta-agent');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-1');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-2');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-3');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-4');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-5');
logs.DeleteFile('/etc/cron.d/artica-cron-kas3-6');
logs.DeleteFile('/etc/cron.d/artica-cron-resintchk');
logs.DeleteFile('/etc/cron.d/artica-cron-lighttpd');
logs.DeleteFile('/etc/cron.d/artica-cron-arpscan');
logs.DeleteFile('/etc/cron.d/artica-zarafaorphans');
logs.DeleteFile('/etc/cron.d/artica-setupcenter');
logs.DeleteFile('/etc/cron.d/artica-cron-dansguardian-update');
logs.DeleteFile('/etc/cron.daily/samba');
logs.DeleteFile('/etc/cron.daily/apt');
logs.DeleteFile('/etc/cron.daily/bsdmainutils');
logs.DeleteFile('/etc/cron.daily/lighttpd');
logs.DeleteFIle('/etc/cron.daily/cyrus22');
logs.DeleteFile('/etc/cron.daily/logrotate');
logs.DeleteFile('/etc/cron.daily/spamassassin');
logs.DeleteFile('/etc/cron.daily/amavisd-new');
logs.DeleteFile('/etc/cron.d/artica-dbstats');
logs.DeleteFile('/etc/cron.d/apps-upgrade');
logs.DeleteFile('/etc/cron.d/pkg-upgrade');
logs.DeleteFile('/etc/cron.d/artica-cron-dansguardianinject');
logs.DeleteFile('/etc/cron.d/artica-malwareswww');
logs.DeleteFile('/etc/cron.d/artica-cron-vpswatch');


fpsystem('/bin/rm -f /etc/cron.d/artica-cron-executor/*');

ForceDirectories('/root/fcron/bin/');
if Not FileExists('/root/fcron/bin/fcronsighup') then fpsystem(SYS.LOCATE_GENERIC_BIN('ln')+' -s /usr/share/artica-postfix/bin/fcronsighup /root/fcron/bin/fcronsighup');

      SYS.DirFiles('/etc/cron.d','*');
      for i:=0 to l.Count-1 do begin
          writeln('Uninstall schedule ' + l.Strings[i]);
      end;

      php5bin:=SYS.LOCATE_PHP5_BIN();
      fpsystem(php5bin+' /usr/share/artica-postfix/exec.mailsync.php --cron');
      fpsystem(php5bin+' /usr/share/artica-postfix/exec.schedules.php');

      if not TryStrToInt(SYS.GET_INFO('systemMaxOverloaded'),systemMaxOverloaded) then begin
         SYS.isoverloadedTooMuch();
             if not TryStrToInt(SYS.GET_INFO('systemMaxOverloaded'),systemMaxOverloaded) then begin
                  systemMaxOverloaded:=(SYS.CPU_NUMBER()+1);
                  SYS.set_INFO('systemMaxOverloaded',IntToStr(systemMaxOverloaded));
             end;
      end;

      if systemMaxOverloaded<2 then systemMaxOverloaded:=6;














// -------------------------------------------------------------------------------------------------------------------------------------------------------




      SYS.DirFiles('/etc/artica-postfix/ad-import','import-ad-*');
      for i:=0 to SYS.DirListFiles.Count-1 do begin
          logs.DebugLogs('Starting......: Daemon (cron) importing Active Directory task '+ SYS.DirListFiles.Strings[i]);
          l.Add(trim(logs.ReadFromFile('/etc/artica-postfix/ad-import/'+SYS.DirListFiles.Strings[i])));
      end;



      //tous les 5 jours Ã 2H30 
      SYS.CRON_CREATE_SCHEDULE('30 2 1,5,10,15,20,30 * *','/usr/share/artica-postfix/bin/artica-make APP_CLAMAV','artica-cron-clamvupd');

      //A 2H
      SYS.CRON_CREATE_SCHEDULE('0 2 * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.smtp.events.clean.php','artica-clean-smtplogs');

      //toutes les heures
      SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+artica_path+'/bin/artica-update','artica-cron-update');
      SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+php5bin+ ' ' +artica_path+'/cron.mysql-databases.php','artica-cron-mysqldb');
      SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+php5bin+ ' ' +artica_path+'/exec.vacationtime.php','artica-cron-vacation');
      SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+php5bin+ ' ' +artica_path+'/exec.quotaroot.php --quota-check','artica-cron-quotas');



     // ################  SQUID

      if squid.isMustBeExecuted() then begin
         // Bases proxy...
         SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+php5bin+ ' ' +artica_path+'/exec.update.squid.tlse.php','universite-toulouse');
         SYS.CRON_CREATE_SCHEDULE('@hourly',cmdnice+php5bin+ ' ' +artica_path+'/exec.update.blacklist.instant.php','artica-webdb');

         //Le Dimanche à 5h00
         squid:=Tsquid.Create;
         if FileExists(squid.SQUID_BIN_PATH()) then begin
            SYS.CRON_CREATE_SCHEDULE('0 5 * * 0',php5bin+' '+artica_path+'/exec.mysql.build.php --squid-events-purge','artica-cron-mysqlpurge-squid');
            end;
      end;

     // ################




      //toutes les 30 Minutes
      SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+artica_path+'/bin/artica-install --verify-artica-iso','artica-cron-iso'); //iso
      SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+php5bin+' '+artica_path+'/exec.artica.meta.php --emergency','artica-meta-agent');
      SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+php5bin+' '+artica_path+'/exec.quotaroot.php --quota-sql','artica-repquota');
      if FileExists(isoqlog.BIN_PATH()) then SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+artica_path+'/bin/artica-install --isoqlog','artica-isoqlog');

      logrotatebin:=SYS.LOCATE_GENERIC_BIN('logrotate');
      if FileExists(logrotatebin) then  SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+php5bin+' '+artica_path+'/exec.logrotate.php >/dev/null 2>&1','artica-logrotate');
      if FileExists(SYS.LOCATE_GENERIC_BIN('zarafa-admin'))  then  SYS.CRON_CREATE_SCHEDULE('0,30 * * * *',cmdnice+php5bin+' '+artica_path+'/exec.zarafa.build.stores.php --orphans >/dev/null 2>&1','artica-zarafaorphans');





      //toutes les 20 Minutes
      SYS.CRON_CREATE_SCHEDULE('0,20,40 * * * *',cmdnice+artica_path+'/bin/process1 --kill','artica-cron-process1k');  //watchdog kill
      SYS.CRON_CREATE_SCHEDULE('0,20,40 * * * *',cmdnice+artica_path+'/bin/artica-install --lighttpd-perms','artica-cron-lighttpd');  //watchdog kill
      SYS.CRON_CREATE_SCHEDULE('0,20,40 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.postfix-logger.php','artica-postfix-logger');  //watchdog kill
      SYS.CRON_CREATE_SCHEDULE('0,20,40 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.squid.ad.import.php --by=cron','artica-postfix-logger');  //watchdog kill


      //toutes les 5 minutes
      SYS.CRON_CREATE_SCHEDULE('0,5,10,15,20,25,30,35,40,45,50,55 * * * *','/etc/init.d/artica-postfix start daemon','artica-cron-watchdog');
      SYS.CRON_CREATE_SCHEDULE('0,5,10,15,20,25,30,35,40,45,50,55 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.arpscan.php --tomysql','artica-cron-arpscan');
      SYS.CRON_CREATE_SCHEDULE('0,5,10,15,20,25,30,35,40,45,50,55 * * * *',cmdnice+' ' +artica_path+'/bin/artica-update --reinstall','artica-cron-resintchk');

     //toutes les 10 minutes
     SYS.CRON_CREATE_SCHEDULE('0,10,20,30,40,50 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.clean.logs.php --clean-tmp2','artica-cron-cleantmp2');
     SYS.CRON_CREATE_SCHEDULE('0,10,20,30,40,50 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.mysql.build.php --dbstats','artica-dbstats');





      if FileExists(zpostfix.POSFTIX_POSTCONF_PATH()) then begin
         SYS.CRON_CREATE_SCHEDULE('0,7,13,18,23,28,33,38,43,48,53,58 * * * *',cmdnice+php5bin+ ' ' +artica_path+'/exec.smtp-senderadv.php','artica-cron-smtpadv');
      end;

      //specifiques
      quarantine_report_schedules();

      if FIleExists('/usr/local/ap-mailfilter3/control/bin/sfmonitoring') then begin
         logs.OutputCmd('/usr/bin/crontab -u mailflt3 -r');
         SYS.AddUserToGroup('mailflt3','mailflt3','','');
         SYS.CRON_CREATE_SCHEDULE('8,28,48 * * * *',cmdnice+'su mailflt3 -c "/usr/local/ap-mailfilter3/bin/sfupdates -q" && chown -R mailflt3:mailflt3 /usr/local/ap-mailfilter3/cfdata','artica-cron-kas3-1');
         SYS.CRON_CREATE_SCHEDULE('2,13,24,35,46,57 * * * *',cmdnice+'/usr/local/ap-mailfilter3/bin/uds-rtts.sh -q','artica-cron-kas3-2');
         SYS.CRON_CREATE_SCHEDULE('*/5 * * * *',cmdnice+'su mailflt3 -c "/usr/local/ap-mailfilter3/control/bin/sfmonitoring -q"','artica-cron-kas3-3');
         SYS.CRON_CREATE_SCHEDULE('* * * * *',cmdnice+php5bin+' '+artica_path+'/exec.kasfilter.php --dograph','artica-cron-kas3-4');

     end;

      //cyrus
      save_cyrus_backup();
      save_cyrus_scan();


      if SYS.GET_INFO('EnableFDMFetch')='1' then begin
         logs.DebugLogs('Starting......: Daemon (fcron) enable FDM polling every 10mn');
         l.add('@'+Nicet+' 30 '+  artica_path+'/bin/artica-ldap -fdm');
      end;



      try
      l.SaveToFile('/etc/artica-cron/spool/root.orig');
      logs.syslogs('Saving croned scripts');

      fpsystem('/bin/rm -f /etc/cron.d/artica-avcomp-*');
      fpsystem(php5bin+' '+ artica_path+'/exec.rsync.events.php --computers-schedule >/dev/null &');
      fpsystem(php5bin+' '+ artica_path+'/exec.computer.scan.php --schedules >/dev/null &');

      except
         logs.syslogs('Saving croned scripts failed !');
      end;
      l.free;
        squid.free;
end;
//#########################################################################################
procedure tcron.quarantine_report_schedules();
var
   i:integer;
   RegExpr:TRegExpr;
   path:string;
   ini:TiniFile;
   ou:string;
   pattern:string;
   cmdnice:string;
begin

   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='^OuSendQuarantineReports.+';
   SYS.DirFiles('/etc/artica-postfix/settings/Daemons','*');
   cmdnice:=SYS.EXEC_NICE();

   for i:=0 to SYS.DirListFiles.Count-1 do begin
        if RegExpr.Exec(SYS.DirListFiles.Strings[i]) then begin
           path:='/etc/artica-postfix/settings/Daemons/'+SYS.DirListFiles.Strings[i];
           ini:=TiniFile.Create(path);
           ou:=ini.ReadString('NEXT','org','');
           pattern:=ini.ReadString('NEXT','cron','59 23 * * *');
           if ini.ReadInteger('NEXT','Enabled',0)=0 then begin
              if FileExists('/etc/cron.d/artica-cron-quarsched-'+ou) then begin
                 logs.Debuglogs('Starting......: uninstall artica-cron-quarsched-'+ou);
                 logs.DeleteFile('/etc/cron.d/artica-cron-quarsched-'+ou);
              end;
              continue;
           end;

           logs.DebugLogs('Starting......: Scheduled ' + ou+' organization for end-users quarantine reports');
           SYS.CRON_CREATE_SCHEDULE(pattern,cmdnice+SYS.LOCATE_PHP5_BIN()+ ' ' +artica_path+'/exec.quarantine.reports.php '+ou,'artica-cron-quarsched-'+ou);
        end;
   end;




end;
//#########################################################################################
procedure tcron.save_cyrus_backup();
var

   f:Tinifile;
   i:integer;
   Sections:Tstringlist;
   schedule,cmdnice:string;
begin
    cmdnice:=SYS.EXEC_NICE();
    SYS.DirFiles('/etc/cron.d','artica-cron-backcyrus*');
    for i:=0 to  SYS.DirListFiles.Count-1 do begin
        logs.Debuglogs('Starting......: artica-postfix Daemon (cron) uninstall '+SYS.DirListFiles.Strings[i]);
        logs.DeleteFile('/etc/cron.d/'+SYS.DirListFiles.Strings[i]);
    end;


    logs.DeleteFile('/etc/cron.d/artica-cron-backcyrus');
    if not FileExists('/etc/artica-postfix/settings/Daemons/CyrusBackupRessource') then exit;
    f:=tinifile.Create('/etc/artica-postfix/settings/Daemons/CyrusBackupRessource');
    Sections:=Tstringlist.Create;
    f.ReadSections(Sections);
    for i:=0 to  Sections.Count-1 do begin
       if length(trim(Sections.Strings[i]))=0 then continue;
       schedule:=f.ReadString(Sections.Strings[i],'schedule','');
       if length(trim(schedule))=0 then continue;
       logs.Debuglogs('Starting......: artica-postfix Daemon (cron) install artica-cron-backcyrus'+IntTostr(i));
       SYS.CRON_CREATE_SCHEDULE(schedule,cmdnice+'/usr/share/artica-postfix/bin/artica-backup --single-cyrus "'+Sections.Strings[i]+'"','artica-cron-backcyrus'+IntTostr(i));
    end;

    f.free;
    Sections.free;
end;
//#########################################################################################
procedure tcron.save_cyrus_scan();
var
   CyrusEnableAV:integer;
   inif:TiniFile;
   Schedule:string;
begin
    logs.DeleteFile('/etc/cron.d/artica-cron-cyrusav');
    CyrusEnableAV:=0;
    if not TryStrToInt(SYS.GET_INFO('CyrusEnableAV'),CyrusEnableAV) then CyrusEnableAV:=0;
    if CyrusEnableAV=0 then exit;
    if not FileExists('/etc/artica-postfix/settings/Daemons/CyrusAVConfig') then exit;
    inif:=TiniFile.Create('/etc/artica-postfix/settings/Daemons/CyrusAVConfig');
    Schedule:=inif.ReadString('SCAN','schedule','');
    if length(trim(Schedule))=0 then exit;
    SYS.CRON_CREATE_SCHEDULE(Schedule,SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.cyrus.av-scan.php','artica-cron-cyrusav');
    inif.free;
end;
//#########################################################################################


procedure tcron.Save_processes_watchdog();
var l:TstringList;
Nice:integer;
Nicet:string;
nolog:string;
RegExpr:TRegExpr;
tmp:string;
php5bin:string;
cmdnice:string;
EnableRemoteStatisticsAppliance:integer;
ZarafaSaLearnSchedule:string;
squid:Tsquid;
begin
      squid:=Tsquid.Create;
      cmdnice:=SYS.EXEC_NICE();
      php5bin:=SYS.LOCATE_PHP5_BIN();
      nolog:=',nolog(true)';
      l:=TstringList.Create;
      tmp:=SYS.GET_PERFS('ProcessNice');
      if not TryStrToInt(tmp,Nice) then Nice:=19;
      Nicet:='nice('+IntToStr(Nice)+'),mail(false)';
      l.Add('!mailto(root)');
      l.add('!serial(true),b(0)');
      if not TryStrToInt(SYS.GET_INFO('EnableRemoteStatisticsAppliance'),EnableRemoteStatisticsAppliance) then EnableRemoteStatisticsAppliance:=0;


      //Quarantine croned...
      if not FileExists(SYS.LOCATE_PHP5_BIN()) then begin
         logs.Syslogs('Starting......: artica-postfix watchdog (fcron) unable to stat PHP/php5 binary !!' );
      end else begin
            l.Add('@'+Nicet+' 8h '+php5bin+' '+artica_path+'/cron.quarantine.php');
      end;





      //roundcube croned....
      if not FileExists(SYS.LOCATE_PHP5_BIN()) then begin
         logs.Syslogs('Starting......: artica-postfix watchdog (fcron) unable to stat PHP/php5 binary !!' );
      end else begin
          if FileExists('/usr/share/roundcube/config/db.inc.php') then begin
             logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) enable roundcube user auto-update');
             l.Add('@'+Nicet+nolog+' 30 '+  SYS.LOCATE_PHP5_BIN() + ' ' + artica_path+'/exec.roundcube.php');
             l.Add('@'+Nicet+nolog+' 3h '+  SYS.LOCATE_PHP5_BIN() + ' ' + artica_path+'/cron.endoflife.php');
          end;
      end;




     //  ########## SQUID
     if squid.isMustBeExecuted() then begin
        logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) checking SQUID specific schedules...');
        fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --build-schedules');
        if FileExists('/etc/artica-postfix/squid.schedules') then l.Add(logs.ReadFromFile('/etc/artica-postfix/squid.schedules'));
     end else begin
        logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) this is not a Proxy system...');
     end;
     // ####################
     fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.schedules.php --defaults');

     if Not FileExists('/etc/artica-postfix/system.schedules') then fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.schedules.php --no-restart');

     if FileExists('/etc/artica-postfix/system.schedules') then begin
        logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) checking System specific schedules...');
        l.Add(logs.ReadFromFile('/etc/artica-postfix/system.schedules'));
     end else begin
         logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) /etc/artica-postfix/system.schedules no such file');
     end;
      //obm croned....
      tmp:=SYS.GET_INFO('OBMSyncCron');
      if length(tmp)=0 then tmp:='2h';
      if DirectoryExists(SYS.LOCATE_OBM_SHARE()) then begin
         RegExpr:=TRegExpr.CReate();
         RegExpr.Expression:='([0-9]+)(m|h|d)';
         if RegExpr.Exec(tmp) then begin
            if RegExpr.Match[2]='m' then begin
               l.Add('@'+Nicet+' '+RegExpr.Match[2]+' '+SYS.LOCATE_PHP5_BIN() + ' ' + artica_path+'/cron.obm.synchro.php');
            end else begin
               l.Add('@'+Nicet+' '+tmp+' '+SYS.LOCATE_PHP5_BIN() + ' ' + artica_path+'/cron.obm.synchro.php');
            end;
         end;
         logs.DebugLogs('Starting......: artica-postfix watchdog (fcron) enable OBM Sync users every '+tmp);
      end;


      try
      l.SaveToFile('/etc/artica-cron/spool_watchdog/root.orig');
      logs.syslogs('Saving watchdog croned scripts');
      except
         logs.syslogs('Saving watchdog croned scripts failed !');
      end;
      l.free;
      squid.free;
end;
//#########################################################################################



function tcron.STATUS():string;
var pidpath:string;
begin

pidpath:=logs.FILE_TEMP();
fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.status.php --fcron >'+pidpath +' 2>&1');
result:=logs.ReadFromFile(pidpath);
logs.DeleteFile(pidpath)
end;
//#########################################################################################
function tcron.FCRON_VERSION():string;
var
   F:TstringList;
   t:string;
   i:integer;
   RegExpr:TRegExpr;
begin

   if not FileExists(artica_path + '/bin/artica-cron') then exit('0.00');
   result:=SYS.GET_CACHE_VERSION('APP_FCRON');
   if length(result)>0 then exit;
   t:=logs.FILE_TEMP();
   fpsystem(artica_path + '/bin/artica-cron -V >' + t + ' 2>&1');
   if not FileExists(t) then exit;
   
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:='fcron\s+([0-9\.]+)';
   
   F:=TstringList.Create;
   F.LoadFromFile(t);
   for i:=0 to F.Count-1 do begin
       if RegExpr.Exec(F.Strings[i]) then begin
          result:=RegExpr.Match[1];
          break;
       end;
   end;
   logs.DeleteFile(t);
   RegExpr.free;
   F.Free;
   SYS.SET_CACHE_VERSION('APP_FCRON',result);
end;
//#########################################################################################
function tcron.ARTICA_VERSION():string;
var
   l:string;
   F:TstringList;

begin
   l:=artica_path + '/VERSION';
   if not FileExists(l) then exit('0.00');
   F:=TstringList.Create;
   F.LoadFromFile(l);
   result:=trim(F.Text);
   F.Free;
end;
//#############################################################################
  
  


end.
