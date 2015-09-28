unit setup_ubuntu_class;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}

interface

uses
  Classes, SysUtils,RegExpr in 'RegExpr.pas',unix,setup_libs,distridetect;
type
  TStringDynArray = array of string;
  type
  tubuntu=class


private
       ArchStruct:integer;
       AsKimSuffi:boolean;
       kvmready:boolean;
       libs:tlibs;
       EXCLUDE_APPS:TstringList;
       LIGHT_INSTALL:boolean;
       function is_application_installed(appname:string):boolean;
       function InstallPackageLists(list:string):boolean;
       procedure sourcesList();

       function Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
       procedure DisableApparmor();
       function UbuntuName():string;
       procedure S00vzreboot();
       function isVPSDetected():boolean;
       function is_application_excluded(appname:string):boolean;

public
      DEBUG:boolean;
      constructor Create();
      procedure Free;
      procedure Show_Welcome;


      function InstallPackageListssilent(list:string):boolean;
      function checkApps(l:tstringlist):string;
      function CheckBaseSystem():string;
      procedure CheckvzQuota();

END;

implementation

constructor tubuntu.Create();
begin

libs:=tlibs.Create;
ArchStruct:=libs.ArchStruct();
AsKimSuffi:=libs.IsKImsuffi();
EXCLUDE_APPS:=TStringList.Create;
   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   LIGHT_INSTALL:=false;

     if length(Paramstr(1))>0 then begin
        if Paramstr(1)<>'--check-base-system' then begin
            writeln('you can use --silent in order to install packages without human interaction');
        end;

        if Paramstr(1)='--light' then begin
           ForceDirectories('/etc/artica-postfix');
           fpsystem('/bin/touch /etc/artica-postfix/LIGHT_INSTALL');
        end;

        if FileExists('/etc/artica-postfix/LIGHT_INSTALL') then LIGHT_INSTALL:=true;

     end;
end;
//#########################################################################################
procedure tubuntu.Free();
begin
  libs.Free;
end;
//#########################################################################################
procedure tubuntu.DisableApparmor();
begin
  if FileExists('/etc/init.d/apparmor') then begin
     writeln('Disable AppArmor.....');
     fpsystem('/etc/init.d/apparmor stop');
     fpsystem('update-rc.d -f apparmor remove');
     fpsystem('/bin/mv /etc/init.d/apparmor /etc/init.d/bk.apparmor');
     writeln('You need to reboot after installation');
     writeln('Enter key');
     readln();
  end;

end;
//#########################################################################################
procedure tubuntu.Show_Welcome;
var
   base,postfix,u,cyrus,samba,squid,nfs,pdns,zabbix,openvpn,kvmPackages:string;
   srclist:tstringlist;
   distri:tdistriDetect;
   country:string;
begin

    distri:=tdistriDetect.Create;
    if not FileExists('/usr/bin/apt-get') then begin
      writeln('Your system does not store apt-get utils, this program must be closed...');
      exit;
    end;



    libs.CheckResolvConf();
    sourcesList();

    if not FileExists('/tmp/apt-update') then begin
       fpsystem('touch /tmp/apt-update');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update');
    end;
    

    kvmready:=false;
    writeln('Checking.............: system...');
    writeln('Checking.............: AppArmor...');
    DisableApparmor();
    writeln('Checking.............: Base system...');
    base:=CheckBaseSystem();

    u:=libs.INTRODUCTION(base,postfix,cyrus,samba,squid,nfs,pdns,openvpn,kvmready,kvmPackages);
    u:=LowerCase(u);


    if length(u)=0 then begin
        if length(base)>0 then u:='B';
        writeln('Installing mandatories packages.....');
    end;

    writeln('You have selected the option : ' + u);

    if u='B' then begin
        if FileExists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 stop');
        if not FileExists('/tmp/apt-update-f') then begin
           fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
           fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
           fpsystem('/bin/touch /tmp/apt-update-f');
        end;
        InstallPackageLists(base);
        Show_Welcome();
        if FileExists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 start');
        exit;
    end;
    
    if length(u)=0 then begin
        Show_Welcome();
        exit;
    end;
    
    if lowercase(u)='a' then begin
       if FileExists('/usr/sbin/postconf') then fpsystem('/usr/sbin/postconf -e "mydomain = domain.tld"');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove exim*');
       InstallPackageLists(postfix+' '+cyrus+' '+samba+' '+squid);
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
       fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
       libs.InstallArtica();
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_ROUNDCUBE3');
          fpsystem('/etc/init.d/artica-postfix restart postfix >/dev/null 2>&1 &');
       Show_Welcome;
       exit;
    end;
    
    if u='1' then begin
           if FileExists('/usr/sbin/postconf') then fpsystem('/usr/sbin/postconf -e "mydomain = domain.tld"');
          InstallPackageLists(postfix);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove exim*');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart postfix >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          Show_Welcome;
          exit;
    end;
    
    if u='2' then begin
       if Not FileExists('/usr/bin/zarafa-server') then begin
          InstallPackageLists(cyrus);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart imap');
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_ROUNDCUBE3');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
       end;
          Show_Welcome;
          exit;
    end;
    
    if u='3' then begin
          InstallPackageLists(samba);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then  begin
             fpsystem('/etc/init.d/artica-postfix restart samba >/dev/null 2>&1 &');
             fpsystem('/usr/share/artica-postfix/bin/artica-install --nsswitch');
             fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          end;
          Show_Welcome;
          exit;
    end;
    
    if u='4' then begin
          InstallPackageLists(squid);
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          if FileExists('/etc/init.d/artica-postfix') then fpsystem('/etc/init.d/artica-postfix restart squid >/dev/null 2>&1 &');
          fpsystem('/usr/share/artica-postfix/bin/process1 --force');
          Show_Welcome;
          exit;
    end;
    
    if u='5' then begin
          libs.InstallArtica();
          Show_Welcome;
          exit;
    end;

    if u='6' then begin
          if FIleExists('/etc/init.d/portmap') then fpsystem('/etc/init.d/portmap start');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(nfs);
          if FIleExists('/etc/init.d/portmap') then fpsystem('/etc/init.d/portmap start');
          Show_Welcome;
          exit;
    end;

    if u='7' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove bind9 bind9-utils');
          InstallPackageLists(pdns);
          fpsystem('/etc/init.d/artica-postfix restart pdns');
          Show_Welcome;
          exit;
    end;

    if u='8' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" remove bind9 bind9-utils');
          InstallPackageLists(zabbix);
          fpsystem('/etc/init.d/artica-postfix restart zabbix');
          Show_Welcome;
          exit;
    end;

    if u='9' then begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(openvpn);
          Show_Welcome;
          exit;
    end;

     if u='k' then begin
      if kvmready then  begin
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" update --fix-missing');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" install -f');
          fpsystem('DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" autoremove');
          InstallPackageLists(kvmPackages);
          fpsystem('adduser `id -un` libvirtd >/dev/null 2>&1');
      end;
          Show_Welcome;
          exit;
    end;




    if lowercase(u)='c' then begin
          fpsystem('/usr/share/artica-postfix/bin/artica-make APP_AMAVISD_MILTER');
          Show_Welcome;
          exit;
    end;


end;
//#########################################################################################



procedure tubuntu.CheckvzQuota();
var
   cmd:string;
   u  :string;
   i  :integer;
   l :Tstringlist;
   ll:Tstringlist;
   RegExpr:TRegExpr;
begin
  if not FileExists('/etc/init.d/vzquota') then exit;

  l:=Tstringlist.Create();
  l.LoadFromFile('/etc/init.d/vzquota');
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='Required-Start';
  for i:=0 to l.Count-1 do begin
      if  RegExpr.Exec(l.Strings[i]) then begin
         RegExpr.free;
         l.free;
         exit;
      end;
  end;

  writeln('/etc/init.d/vzquota is bugged, this setup will patch it...');
  ll:=tstringlist.Create;
  ll.add('#!/bin/sh');
  ll.add('### BEGIN INIT INFO');
  ll.add('# Provides:                 vzquota');
  ll.add('# Required-Start:');
  ll.add('# Required-Stop:');
  ll.add('# Should-Start:             $local_fs $syslog');
  ll.add('# Should-Stop:              $local_fs $syslog');
  ll.add('# Default-Start:            0 1 2 3 4 5 6');
  ll.add('# Default-Stop:');
  ll.add('# Short-Description:        Fixed(?) vzquota init script');
  ll.add('### END INIT INFO');
  for i:=1 to l.Count-1 do begin
      ll.Add(l.Strings[i]);
  end;

  ll.SaveToFile('/etc/init.d/vzquota');
  ll.free;
  l.free;
  RegExpr.free;

end;
//#########################################################################################


function tubuntu.InstallPackageLists(list:string):boolean;
var
   cmd:string;
   u  :string;
   i  :integer;
   ll :TStringDynArray;
begin
if length(trim(list))=0 then exit;
result:=false;

writeln('');
writeln('The following package(s) must be installed in order to perform continue setup');
writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('"',list,'"');
writeln('-----------------------------------------------------------------------------');
writeln('');
writeln('Do you allow install these packages? [Y]');

if not libs.COMMANDLINE_PARAMETERS('--silent') then begin
   readln(u);
end else begin
    u:='y';
end;


if length(u)=0 then u:='y';

if LowerCase(u)<>'y' then exit;


   ll:=Explode(',',list);
   for i:=0 to length(ll)-1 do begin
       if length(trim(ll[i]))>0 then begin


writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('');
writeln(' Check ',i,'/',length(ll)-1,': "',ll[i],'"');
writeln('');
writeln('-----------------------------------------------------------------------------');
writeln('');

          if(trim(ll[i])='lighttpd') then begin
              writeln('Stopping apache2....');
              if Fileexists('/etc/init.d/apache2') then fpsystem('/etc/init.d/apache2 stop');
          end;

          cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install ' + ll[i];
          fpsystem(cmd);
       end;
   end;



   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   result:=true;


end;
//#########################################################################################
function tubuntu.InstallPackageListssilent(list:string):boolean;
var
   cmd:string;
   u  :string;
   i  :integer;
   ll :TStringDynArray;
   rebot:boolean;
begin
if length(trim(list))=0 then exit;
result:=false;
rebot:=false;

   writeln('Checking bad installations with -f install...');
   cmd:='/usr/bin/apt-get -y -f install';
   fpsystem(cmd);
   writeln('Checking Autoremove packages....');
   cmd:='/usr/bin/apt-get -y autoremove';
   fpsystem(cmd);
   writeln('Checking Autoremove packages....');
   if FileExists('/etc/artica-postfix/apt_get_update.time') then begin
      if libs.FILE_TIME_BETWEEN_MIN('/etc/artica-postfix/apt_get_update.time')>480 then begin
           cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get update';
           fpsystem(cmd);
           fpsystem('/bin/rm /etc/artica-postfix/apt_get_update.time');
           fpsystem('/bin/touch /etc/artica-postfix/apt_get_update.time');
      end;
   end else begin
           cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get update';
           fpsystem(cmd);
           fpsystem('/bin/rm /etc/artica-postfix/apt_get_update.time');
           fpsystem('/bin/touch /etc/artica-postfix/apt_get_update.time');
   end;

   writeln('****** Installing the following packages ******');
   writeln(list);
   writeln('');

   ll:=Explode(',',list);
   for i:=0 to length(ll)-1 do begin
       if length(trim(ll[i]))>0 then begin
          cmd:='DEBIAN_FRONTEND=noninteractive /usr/bin/apt-get -o Dpkg::Options::="--force-confnew" --force-yes -fuy install ' + ll[i];
          fpsystem(cmd);
          rebot:=true;
       end;
   end;


   if rebot then begin
      if FileExists('/usr/share/artica-postfix/exec.initslapd.php') then fpsystem('php /usr/share/artica-postfix/exec.initslapd.php');
   end;
   if FileExists('/tmp/packages.list') then fpsystem('/bin/rm -f /tmp/packages.list');
   result:=true;


end;
//#########################################################################################
function tubuntu.is_application_excluded(appname:string):boolean;
var

   RegExpr:TRegExpr;
   i:integer;
   D:boolean;
   tmpstr:string;
begin
     result:=false;
     if EXCLUDE_APPS.Count>0 then begin
        for i:=0 to EXCLUDE_APPS.Count-1 do begin
            if trim(EXCLUDE_APPS.Strings[i])=trim(appname) then exit(true);
        end;
        exit(false);
     end;
     if not FileExists('/root/exclude-apps.txt') then exit(false);
     if EXCLUDE_APPS.Count=0 then EXCLUDE_APPS.LoadFromFile('/root/exclude-apps.txt');


     if EXCLUDE_APPS.Count>0 then begin
        for i:=0 to EXCLUDE_APPS.Count-1 do begin
            if trim(EXCLUDE_APPS.Strings[i])=trim(appname) then exit(true);
        end;
        exit(false);
     end;

end;
//#########################################################################################
function tubuntu.is_application_installed(appname:string):boolean;
var
   l:TstringList;
   RegExpr:TRegExpr;
   RegExpr2:TRegExpr;
   i:integer;
   D:boolean;
   tmpstr:string;
begin
    D:=false;
    result:=false;
    appname:=trim(appname);
    if is_application_excluded(appname) then exit(true);


    D:=libs.COMMANDLINE_PARAMETERS('--verbose');
    if D then  DEBUG:=true;
    if not FileExists('/tmp/packages.list') then begin
       if DEBUG then writeln('/usr/bin/dpkg -l >/tmp/packages.list');
       fpsystem('/usr/bin/dpkg -l >/tmp/packages.list');
    end;

    l:=TstringList.Create;
    try
    l.LoadFromFile('/tmp/packages.list');
    except
          writeln('FATAL ERROR WHILE READING /tmp/packages.list! (L.542)');
    end;


    if l.Count<10 then begin
       fpsystem('/bin/rm -rf /tmp/packages.list');
       result:=is_application_installed(appname);
       exit;
    end;
    RegExpr:=TRegExpr.Create;
    RegExpr2:=TRegExpr.Create;

    
    for i:=0 to l.Count-1 do begin
        RegExpr.Expression:='ii\s+(.+?)(:|\s+)';
        if RegExpr.Exec(l.Strings[i]) then begin
           if lowercase(appname)='xtables-addons-modules' then begin
                tmpstr:=RegExpr.Match[1];
                RegExpr.Expression:='xtables-addons-modules.+?';
                if RegExpr.Exec(tmpstr) then begin
                 if DEBUG then writeln(appname,' already installed [OK]');
                 result:=true;
                 break;
                end;
           end;
           if lowercase(trim(RegExpr.Match[1]))=trim(lowercase(appname)) then begin
              if DEBUG then writeln(appname,' already installed [OK]');
              result:=true;
              break;
           end;
        end;

    end;
    if D then writeln('Search ',RegExpr.Expression,' failed');
    l.free;
    RegExpr.free;

end;

//#########################################################################################
function tubuntu.CheckBaseSystem():string;
var
   l:TstringList;
   f:string;
   i:integer;
   distri:tdistriDetect;
   UbuntuIntVer:integer;
   libs:tlibs;
   non_free:boolean;
   KERNEL_VERSION:string;
begin
f:='';
UbuntuIntVer:=9;
l:=TstringList.Create;
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
KERNEL_VERSION:=libs.KERNEL_VERSION();

l.add('libtool'); // libtoolize -> Compile SQUID 3.4.5
l.add('libcap2-bin'); // squid pinger issue
l.add('libdb-dev'); // C-ICAP squidguard issue
l.add('libunix-syslog-perl'); // Mail Archiver
l.add('libsendmail-pmilter-perl'); // Mail Archiver
l.add('libmail-imapclient-perl'); // Mail Archiver
l.add('apache2-utils');
l.add('strace');
l.add('ebtables');
l.add('whois');
l.add('iotop');
l.add('lshw');
l.add('acl');
l.add('socat');
l.add('apache2-suexec');
l.add('apache2-utils');
l.add('apache2.2-common');
l.add('arj');
l.add('bridge-utils');
l.add('build-essential');
l.add('byacc');
l.add('cifs-utils');
l.add('clamav');
l.add('clamav-freshclam');
l.add('console-common');
l.add('console-data');
l.add('console-setup');
	//$f["console-setup-mini');
//      l.add('console-tools');
l.add('curlftpfs');
l.add('davfs2');
l.add('discover');
l.add('dnsmasq');
l.add('dnsutils');
l.add('dsniff');
l.add('firmware-bnx2');
l.add('freeradius-common');
l.add('freeradius-utils');
l.add('flex');
l.add('freeradius');
l.add('freeradius-krb5');
l.add('freeradius-ldap');
l.add('freeradius-mysql');
l.add('ftp-proxy');
l.add('g++');
l.add('gcc');
l.add('htop');
l.add('geoip-bin');
l.add('geoip-database');
l.add('ipband');
l.add('iptables-dev');
l.add('iputils-arping');
l.add('isc-dhcp-client');
l.add('isc-dhcp-server');
l.add('krb5-clients');
l.add('krb5-config');
l.add('krb5-kdc');
l.add('krb5-user');
l.add('mingetty');
l.add('python-dev'); //samba

l.add('lighttpd');
l.add('locales');
l.add('lsof');
l.add('make');
l.add('mc');
l.add('monit');
l.add('mysql-client-5.5');
l.add('mysql-server-5.5');
l.add('nginx');
l.add('ntpdate');
l.add('openssh-client');
l.add('openssh-server');
l.add('openssl');
l.add('php-apc');
l.add('php-log');
l.add('php-net-sieve');
l.add('php-pear');
l.add('php-radius-legacy');
l.add('php5-cgi');
l.add('php5-cli');
l.add('php5-common');
l.add('php5-memcache');
l.add('php5-curl');
l.add('php5-dev');
l.add('php5-fpm');
l.add('php5-gd');
l.add('php5-geoip');
l.add('php5-imap');
l.add('php5-ldap');
l.add('php5-mcrypt');
l.add('php5-ming');
l.add('php5-mysql');
l.add('php5-pspell');
l.add('php5-sqlite');
l.add('php5-xmlrpc');
l.add('python-mysqldb');
l.add('rdate');
l.add('rrdtool');
l.add('sasl2-bin');
l.add('scons');
l.add('slapd');
l.add('sshfs');
l.add('tcsh');
l.add('telnet');
l.add('ucarp');
l.add('unzip');
l.add('util-linux-locales');
l.add('vde2');
l.add('vnstat');
l.add('apt-file');
l.add('hdparm');
l.add('btrfs-tools');


l.add('conntrack');
l.add('conntrackd');
l.add('xfsprogs');
l.add('reiserfsprogs');
l.add('btrfs-tools');
l.add('xfsdump');
l.add('attr');
l.add('quota');
l.add('libnetfilter-conntrack3');
l.add('liblzo2-2');
l.add('liblzo2-dev');
l.add('libdbus-1-dev');
l.add('libnetfilter-conntrack-dev');
l.add('less');
l.add('lib32tinfo5');
l.add('libacl1');
l.add('libalgorithm-diff-perl');
l.add('libalgorithm-diff-xs-perl');
l.add('libalgorithm-merge-perl');
l.add('libapache2-mod-perl2');
l.add('libaprutil1');
l.add('libaprutil1-dbd-sqlite3');
l.add('libaprutil1-ldap');
l.add('libapt-inst1.5');
l.add('libapt-pkg-perl');
l.add('libapt-pkg4.12');
l.add('libaspell15');
l.add('libasprintf0c2');
l.add('libattr1');
l.add('libaudit0');
l.add('libavahi-client3');
l.add('libavahi-common-data');
l.add('libavahi-common3');
l.add('libavahi-core7');
l.add('libbind9-80');
l.add('libblkid1');
l.add('libboost-iostreams1.49.0');
l.add('libboost-program-options1.49-dev');
l.add('libboost-program-options1.49.0');
l.add('libboost-serialization1.49-dev');
l.add('libboost-serialization1.49.0');
l.add('libboost1.49-dev');
l.add('libbsd-resource-perl');
l.add('libbsd0');
l.add('libbz2-1.0');
l.add('libbz2-dev');
l.add('libc-bin');
l.add('libc-client2007e');
l.add('libc-dev-bin');
l.add('libc6-dev');
l.add('libc6');
l.add('libcairo2');
l.add('libcdio13');
l.add('libclamav6');
l.add('libclass-isa-perl');
l.add('libcomerr2');
l.add('libconfuse-common');
l.add('libconsole');
l.add('libcurl3-gnutls');
l.add('libcurl3');
l.add('libcwidget3');
l.add('libdaemon0');
l.add('libdatrie1');
l.add('libdb5.1');
l.add('libdbi-perl');
l.add('libdbi1');
l.add('libdbus-1-3');
l.add('libdevel-symdump-perl');
l.add('libdevmapper-event1.02.1');
l.add('libdevmapper1.02.1');
l.add('libdiscover2');
l.add('libdns88');
l.add('libdpkg-perl');
l.add('libdrm-intel1');
l.add('libdrm-nouveau1a');
l.add('libdrm-radeon1');
l.add('libdrm2');
l.add('libedit2');
l.add('libencode-locale-perl');
l.add('libept1.4.12');
l.add('libev4');
l.add('libevent-2.0-5');
l.add('libexpat1');
l.add('libfam0');
l.add('libffi5');
l.add('libfile-fcntllock-perl');
l.add('libfile-listing-perl');
l.add('libfont-afm-perl');
l.add('libfontconfig1');
l.add('libfontenc1');
l.add('libfreeradius2');
l.add('libfreetype6');
l.add('libgc1c2');
l.add('libgcc1');
l.add('libgcrypt11-dev');
l.add('libgcrypt11');
l.add('libgd2-xpm');
l.add('libgdbm3');
l.add('libgeoip1');
l.add('libgif4');
l.add('libgl1-mesa-dri');
l.add('libglib2.0-0');
l.add('libglib2.0-data');
l.add('libgmp10');
l.add('libgnutls-dev');
l.add('libgnutls-openssl27');
l.add('libgnutls26');
l.add('libgnutlsxx27');
l.add('libgomp1');
l.add('libgpg-error-dev');
l.add('libgpg-error0');
l.add('libgpgme11');
l.add('libgpm2');
l.add('libgraph-perl');
l.add('libgssapi-krb5-2');
l.add('libgssglue1');
l.add('libgssrpc4');
l.add('libheap-perl');
l.add('libhtml-form-perl');
l.add('libhtml-format-perl');
l.add('libhtml-parser-perl');
l.add('libhtml-tagset-perl');
l.add('libhtml-template-perl');
l.add('libhtml-tree-perl');
l.add('libhttp-cookies-perl');
l.add('libhttp-daemon-perl');
l.add('libhttp-date-perl');
l.add('libhttp-message-perl');
l.add('libhttp-negotiate-perl');
l.add('libice6');
l.add('libicu48');
l.add('libidn11-dev');
l.add('libidn11');
l.add('libio-socket-ip-perl');
l.add('libio-socket-ssl-perl');
l.add('libisc84');
l.add('libisccc80');
l.add('libisccfg82');
l.add('libitm1');
l.add('libjpeg8');
l.add('libk5crypto3');
l.add('libkadm5clnt-mit8');
l.add('libkadm5srv-mit8');
l.add('libkdb5-6');
l.add('libkeyutils1');
l.add('libklibc');
l.add('libkmod2');
l.add('libkrb5support0');
l.add('libldap-2.4-2');
l.add('liblocale-gettext-perl');
l.add('liblockfile-bin');
l.add('liblockfile1');
l.add('libltdl-dev');
l.add('libltdl7');
l.add('liblua5.1-0');
l.add('liblua50');
l.add('liblua50-dev');
l.add('liblualib50');
l.add('liblualib50-dev ');
l.add('liblwp-mediatypes-perl');
l.add('liblwp-protocol-https-perl');
l.add('liblwres80');
l.add('liblzma5');
l.add('libmagic1');
l.add('libmailtools-perl');
l.add('libmcrypt4');
l.add('libming1');
l.add('libmount1');
l.add('libmpc2');
l.add('libmpfr4');
l.add('libmysqlclient18');
l.add('libncurses5');
l.add('libncursesw5');
l.add('libneon27-gnutls');
l.add('libnet-daemon-perl');
l.add('libnet-http-perl');
l.add('libnet-ssleay-perl');
l.add('libnet1');
l.add('libnewt0.52');
l.add('libnfnetlink0');
l.add('libnfsidmap2');
l.add('libnids1.21');
l.add('libnss-winbind');
l.add('libntlm0');
l.add('libodbc1');
l.add('libonig2');
l.add('libp11-kit-dev');
l.add('libp11-kit0');
l.add('libpam-modules-bin');
l.add('libpam-runtime');
l.add('libpam-winbind');
l.add('libpam0g');
l.add('libpango1.0-0');
l.add('libpcap0.8');
l.add('libpci3');
l.add('libpciaccess0');
l.add('libpcre3');
l.add('libperl5.14');
l.add('libpipeline1');
l.add('libpixman-1-0');

l.add('libpng12-0');
l.add('libpopt0');
l.add('libpq5');
l.add('libprocps0');
l.add('libpth20');
l.add('libpython2.7');
l.add('libqdbm14');
l.add('libquadmath0');
l.add('libreadline-dev');
l.add('libreadline5');
l.add('libreadline6-dev');
l.add('libreadline6');
l.add('librrd4');
l.add('librtmp-dev');
l.add('librtmp0');
l.add('libruby1.8');
l.add('libruby1.9.1');
l.add('libsasl2-2');
l.add('libselinux1');
l.add('libsemanage-common');
l.add('libsemanage1');
l.add('libsepol1-dev');
l.add('libsepol1');
l.add('libsigc++-2.0-0c2a');
l.add('libslang2');
l.add('libslp1');
l.add('libsm6');
l.add('libsocket-perl');
l.add('libsqlite3-0');
l.add('libss2');
l.add('libssh2-1-dev');
l.add('libssh2-1');
l.add('libssl-doc');
l.add('libssl1.0.0');
l.add('libstdc++6-4.4-dev');
l.add('libstdc++6-4.7-dev');
l.add('libstdc++6');
l.add('libswitch-perl');
l.add('libsysfs2');
l.add('libsystemd-login0');
l.add('libtasn1-3-dev');
l.add('libtasn1-3');
l.add('libtdb1');

l.add('libterm-readkey-perl');
l.add('libterm-readline-perl-perl');
l.add('libtext-charwidth-perl');
l.add('libtext-iconv-perl');
l.add('libtext-wrapi18n-perl');
l.add('libthai-data');
l.add('libthai0');
l.add('libtimedate-perl');
l.add('libtinfo-dev');
l.add('libtinfo5');
l.add('libtirpc1');
l.add('libtokyocabinet9');
l.add('libtommath-dev');
l.add('libtommath-docs');
l.add('libtommath0');
l.add('libudev0');
l.add('liburi-perl');
l.add('libusb-0.1-4');
l.add('libusb-1.0-0');
l.add('libustr-1.0-1');
l.add('libuuid-perl');
l.add('libuuid1');
l.add('libv4lconvert0');
l.add('libvde0');
l.add('libvdeplug2');
l.add('libverto-libev1');
l.add('libverto1');
l.add('libwbclient0');
l.add('libwrap0');
l.add('libwww-perl');
l.add('libwww-robotrules-perl');
l.add('libx11-6');
l.add('libx11-data');
l.add('libxapian22');
l.add('libxau6');
l.add('libxaw7');
l.add('libxcb-render0');
l.add('libxcb-shm0');
l.add('libxcb1');
l.add('libxcomposite1');
l.add('libxdamage1');
l.add('libxdmcp6');
l.add('libxext6');
l.add('libxfixes3');
l.add('libxfont1');
l.add('libxft2');
l.add('libxkbfile1');
l.add('libxml2-dev');
l.add('libxml2');
l.add('libxmu6');
l.add('libxmuu1');
l.add('libxpm4');
l.add('libxrandr2');
l.add('libxrender1');
l.add('libxslt1.1');
l.add('libxt6');
l.add('libyaml-0-2');
l.add('lib32asound2');
l.add('lib32bz2-1.0');
l.add('lib32gcc1');
l.add('lib32ncurses5');
l.add('lib32stdc++6');
l.add('lib32v4l-0');
l.add('lib32z1');
l.add('libaio1');
l.add('libapache-dbi-perl');
l.add('libapache2-mod-evasive');
l.add('libapache2-mod-fastcgi');
l.add('libapache2-mod-geoip');
l.add('libapache2-mod-php5');
l.add('libapache2-mod-proxy-html');
l.add('libapache2-reload-perl');
l.add('libapr1');
l.add('libasound2');
l.add('libattr1-dev');
l.add('libauthen-sasl-perl');
l.add('libboost-dev');
l.add('libboost-program-options-dev');
l.add('libboost-serialization-dev');
l.add('libc6-i386');
l.add('libcairo-ruby1.8');
l.add('libcap2');
l.add('libcdio-dev');
l.add('libclamav-dev');
l.add('libconfuse0');
l.add('libcrypt-openssl-random-perl');
l.add('libcups2');
l.add('libcurl4-openssl-dev');
l.add('libdb-ruby1.8');
l.add('libdbd-mysql-perl');
l.add('libfuse-dev');
l.add('libfuse2');
l.add('libgeo-ip-perl');
l.add('libgeoip-dev');
l.add('libgsasl7');
l.add('libiodbc2');
l.add('libjpeg8-dev');
l.add('libkrb5-3');
l.add('libkrb5-dev');
l.add('libldap2-dev');
l.add('liblua5.1-0-dev');
l.add('liblualib50-dev');
l.add('libmcrypt-dev');
l.add('libmhash2');
l.add('libmysqlclient-dev');
l.add('libnl1');

l.add('libnss-ldap');
l.add('libnss-mdns');
l.add('libpam-krb5');
l.add('libpam-ldap');
l.add('libpam-modules');
l.add('libpam0g-dev');
l.add('libpcrecpp0');
l.add('libperl-dev');
l.add('libpq-dev');
l.add('librrdp-perl');
l.add('libsasl2-modules');
l.add('libsasl2-modules-gssapi-mit');
l.add('libsasl2-modules-ldap');
l.add('libselinux1-dev');
l.add('libsqlite3-dev');
l.add('libssl-dev');
l.add('libtevent0');
l.add('libtalloc2');
l.add('libtool');
l.add('libusb-dev');
l.add('libv4l-0');
l.add('libwrap0-dev');
l.add('libxslt1-dev');
l.add('libgsasl7-dev');
l.add('libblkid-dev');
l.add('libcap-dev');
l.add('libmysqlclient-dev');
l.add('libnetfilter-conntrack-dev');
l.add('libtevent-dev');
l.add('libtevent0');

l.add('httrack');
l.add('clamav-daemon');
l.add('vlan');
	// NGINX

l.add('libpcre3-dev');


l.add('wget');
l.add('udev');
l.add('usbutils');
l.add('python');
l.add('python-apt');
l.add('python-apt-common');
l.add('python-chardet');
l.add('python-debian');
l.add('python-debianbts');
l.add('python-fpconst');
l.add('python-minimal');
l.add('python-reportbug');
l.add('python-soappy');
l.add('python-support');
l.add('python2.6');
l.add('python2.6-minimal');
l.add('python2.7');
l.add('python2.7-minimal');
l.add('acpi');
l.add('acpid');
l.add('adduser');
l.add('apache2');
l.add('apache2-mpm-prefork');
l.add('apache2.2-bin');
l.add('apt');
l.add('apt-listchanges');
l.add('apt-utils');
l.add('aptitude');
l.add('aptitude-common');
l.add('aspell');
l.add('aspell-en');
l.add('at');
l.add('autoconf');
l.add('automake');
l.add('autotools-dev');
l.add('avahi-daemon');
l.add('base-files');
l.add('base-passwd');
l.add('bash');
l.add('bash-completion');
l.add('bc');
l.add('bind9-host');
l.add('binutils');
l.add('bsd-mailx');
l.add('bsdmainutils');
l.add('bsdutils');
l.add('busybox');
l.add('bzip2');
l.add('ca-certificates');
l.add('chkconfig');
l.add('clamav-base');
l.add('comerr-dev');
l.add('console-setup-linux');
l.add('coreutils');
l.add('cpio');
l.add('cpp');
l.add('cpp-4.4');
l.add('cpp-4.6');
l.add('cpp-4.7');
l.add('cron');
l.add('cryptsetup');
l.add('dash');
l.add('db-util');
l.add('db5.1-util');
l.add('dbus');
l.add('dc');
l.add('debconf');
l.add('debconf-i18n');
l.add('debconf-utils');
l.add('debian-archive-keyring');
l.add('debian-faq');
l.add('debianutils');
l.add('debootstrap');
l.add('dictionaries-common');
l.add('diffutils');
l.add('discover-data');
l.add('dkms');
l.add('dmidecode');
l.add('dmsetup');
l.add('doc-debian');
l.add('dpkg');
l.add('dpkg-dev');
l.add('e2fslibs');
l.add('e2fsprogs');
l.add('eject');
l.add('fakeroot');
l.add('file');
l.add('findutils');
l.add('fontconfig');
l.add('fontconfig-config');

l.add('ftp');
l.add('ftp-proxy-doc');
l.add('fuse');
l.add('g++-4.4');
l.add('g++-4.7');
l.add('gcc-4.4');
l.add('gcc-4.4-base');
l.add('gcc-4.6');
l.add('gcc-4.6-base');
l.add('gcc-4.7');
l.add('gcc-4.7-base');

l.add('grub-common');
l.add('grub-pc');


l.add('gettext-base');
l.add('gnupg');
l.add('gpgv');
l.add('grep');
l.add('groff-base');
l.add('gzip');
l.add('hostname');
l.add('iamerican');
l.add('ibritish');
l.add('ienglish-common');
l.add('ifupdown');
l.add('info');
l.add('initramfs-tools');
l.add('initscripts');
l.add('insserv');
l.add('install-info');
l.add('installation-report');
l.add('iproute');
l.add('iptables');
l.add('iputils-ping');
l.add('isc-dhcp-common');
l.add('iso-codes');
l.add('ispell');
l.add('jfsutils');
l.add('keyboard-configuration');
l.add('keyutils');
l.add('klibc-utils');
l.add('kmod');
l.add('krb5-locales');
l.add('krb5-multidev');
l.add('laptop-detect');

l.add('linux-base');
l.add('linux-headers-3.2.0-4-amd64');
l.add('linux-headers-3.2.0-4-common');
l.add('linux-headers-amd64');
l.add('linux-image-3.2.0-4-amd64');
l.add('linux-image-amd64');
l.add('linux-kbuild-3.2');
l.add('linux-libc-dev');
l.add('localization-config');
l.add('lockfile-progs');
l.add('login');
l.add('logrotate');
l.add('lsb-base');
l.add('lsb-release');
l.add('lua50');
l.add('lvm2');
l.add('m4');
l.add('man-db');
l.add('manpages');
l.add('manpages-dev');
l.add('mawk');
l.add('mc-data');
l.add('mdadm');
l.add('menu');
l.add('mime-support');
l.add('mlocate');
l.add('mlock');
l.add('module-init-tools');
l.add('mount');
l.add('multiarch-support');
l.add('mutt');
l.add('mysql-common');
l.add('mysql-server-core-5.5');
l.add('nano');
l.add('ncurses-base');
l.add('ncurses-bin');
l.add('ncurses-term');
l.add('net-tools');
l.add('netbase');
l.add('netcat-traditional');
l.add('nfs-common');
l.add('nscd');
l.add('openssh-blacklist');
l.add('openssh-blacklist-extra');
l.add('os-prober');
l.add('passwd');
l.add('patch');
l.add('pciutils');
l.add('perl');
l.add('perl-base');
l.add('perl-modules');
l.add('php-net-socket');
l.add('pkg-config');
l.add('popularity-contest');
l.add('procps');
l.add('psmisc');

l.add('readline-common');

l.add('reportbug');
l.add('rpcbind');
l.add('rsyslog');
l.add('ruby');
l.add('ruby-bdb');
l.add('ruby-cairo');
l.add('ruby1.9.1');
l.add('samba-common');
l.add('samba-common-bin');
l.add('sed');
l.add('sensible-utils');
l.add('sgml-base');
l.add('shared-mime-info');
l.add('shtool');
l.add('spawn-fcgi');
l.add('ssl-cert');
l.add('syslinux');
l.add('syslinux-common');
l.add('sysv-rc');
l.add('sysvinit');
l.add('sysvinit-utils');
l.add('tar');
l.add('task-english');
l.add('tasksel');
l.add('tasksel-data');
l.add('tcpd');
l.add('texinfo');
l.add('time');
l.add('traceroute');
l.add('ttf-dejavu');
l.add('ttf-dejavu-core');
l.add('ttf-dejavu-extra');
l.add('tzdata');
l.add('ucf');

l.add('util-linux');
l.add('vim-common');
l.add('vim-tiny');
l.add('w3m');
l.add('wamerican');
l.add('whiptail');
l.add('whois');
l.add('winbind');
l.add('x11-common');
l.add('x11-xkb-utils');
l.add('xauth');
l.add('xfonts-base');
l.add('xfonts-encodings');
l.add('xfonts-utils');
l.add('btrfs-tools');
l.add('xfsprogs');
l.add('xkb-data');
l.add('xml-core');
l.add('xserver-common');
l.add('xserver-xorg-core');
l.add('xz-utils');
l.add('zlib1g-dev');
l.add('zlib1g');

// PATCH Dec 2014
l.add('autofs');
l.add('open-iscsi');
l.add('iscsitarget');
l.add('iscsitarget-dkms');
l.add('smartmontools');
l.add('redis-server');

fpsystem('/bin/rm -rf /tmp/packages.list');
if DEBUG then writeln('Verify ',l.Count,' packages...');
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
end;
//#########################################################################################
procedure tubuntu.S00vzreboot();
var
   l:Tstringlist;
begin
l:=Tstringlist.Create;
l.Add('#!/bin/bash');
l.add('./reboot');
l.SaveToFile('/etc/rc6.d/S00vzreboot');
l.free;
writeln('Patching /etc/rc6.d/S00vzreboot done...');
end;

function tubuntu.checkApps(l:tstringlist):string;
var
   f:string;
   i:integer;

begin
f:='';
for i:=0 to l.Count-1 do begin
     if not is_application_installed(l.Strings[i]) then begin
          f:=f + ',' + l.Strings[i];
     end;
end;
 result:=f;
 l.free;
end;
//########################################################################################
function tubuntu.UbuntuName():string;
var
   FileTMP:TstringList;
   RegExpr:TRegExpr;
   distri_provider,distri_ver,distri_name:string;
   i:integer;
begin
      RegExpr:=TRegExpr.Create;
             fpsystem('/bin/cp /etc/lsb-release /tmp/lsb-release');
             FileTMP:=TstringList.Create;
             FileTMP.LoadFromFile('/tmp/lsb-release');
             for i:=0 to  FileTMP.Count-1 do begin
                 RegExpr.Expression:='DISTRIB_ID=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_provider:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_RELEASE=([0-9\.]+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_ver:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_CODENAME=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_name:=trim(RegExpr.Match[1]);
             end;

             result:=trim(lowercase(distri_name));
             RegExpr.Free;
             FileTMP.Free;

end;
//########################################################################################
function tubuntu.Explode(const Separator, S: string; Limit: Integer = 0):TStringDynArray;
var
  SepLen       : Integer;
  F, P         : PChar;
  ALen, Index  : Integer;
begin
  SetLength(Result, 0);
  if (S = '') or (Limit < 0) then
    Exit;
  if Separator = '' then
  begin
    SetLength(Result, 1);
    Result[0] := S;
    Exit;
  end;
  SepLen := Length(Separator);
  ALen := Limit;
  SetLength(Result, ALen);

  Index := 0;
  P := PChar(S);
  while P^ <> #0 do
  begin
    F := P;
    P := StrPos(P, PChar(Separator));
    if (P = nil) or ((Limit > 0) and (Index = Limit - 1)) then
      P := StrEnd(F);
    if Index >= ALen then
    begin
      Inc(ALen, 5); // mehrere auf einmal um schneller arbeiten zu können
      SetLength(Result, ALen);
    end;
    SetString(Result[Index], F, P - F);
    Inc(Index);
    if P^ <> #0 then
      Inc(P, SepLen);
  end;
  if Index < ALen then
    SetLength(Result, Index); // wirkliche Länge festlegen
end;
//#########################################################################################

function tubuntu.isVPSDetected():boolean;
begin
result:=false;
if FIleExists('/etc/rc6.d/S00vzreboot') then exit(true);
if FIleExists('/etc/init.d/vzquota') then exit(true);
if FIleExists('/proc/vz/vestat') then exit(true);
end;
//#########################################################################################
procedure tubuntu.sourcesList();
var
   l:Tstringlist;
begin
l:=Tstringlist.Create;
l.add('deb http://http.debian.net/debian wheezy main contrib non-free');
l.add('deb-src http://http.debian.net/debian wheezy main contrib non-free');
l.add('deb http://http.debian.net/debian wheezy-updates main contrib non-free');
l.add('deb-src http://http.debian.net/debian wheezy-updates main contrib non-free');
l.add('deb http://security.debian.org/ wheezy/updates main contrib non-free');
l.add('deb-src http://security.debian.org/ wheezy/updates main contrib non-free');
l.SaveToFile('/etc/apt/sources.list');
end;


end.
