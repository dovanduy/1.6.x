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
if Not DirectoryExists('/var/lib/nfs') then forceDirectories('/var/lib/nfs');
if Not DirectoryExists('/lib/init/rw/sendsigs.omit.d') then forceDirectories('/lib/init/rw/sendsigs.omit.d');

//l.Add('dhcp3-client');
l.Add('cron');
l.Add('debconf-utils');
l.Add('file');
l.Add('less');
l.Add('rsync');
l.Add('openssh-client ');
l.Add('openssh-server');
l.Add('strace');
l.add('mtools');
l.add('re2c');
l.add('cron');
l.add('debconf-utils');
l.add('file');
l.add('less');
l.add('rsync');
l.add('sudo');
l.add('iproute');
l.add('curl');
l.add('lm-sensors');
l.add('bison');
l.add('e2fsprogs');



if ArchStruct=64 then begin
   l.add('libc6-i386');
   l.add('lib32stdc++6');
end;

l.add('libc6-dev');
l.add('iptables-dev');
l.add('libssl-dev');
l.add('byacc');
l.Add('gcc');
l.Add('make');
l.Add('cmake');
l.Add('build-essential');
l.Add('flex');
l.add('libsasl2-dev');
l.add('libcdb-dev');
l.add('fuse-utils');

l.Add('time');
l.Add('eject');
l.Add('locales');

l.Add('pciutils ');
l.Add('usbutils');

l.Add('slapd');
l.add('ldap-utils');
if not FileExists('/usr/sbin/mysqld') then l.Add('mysql-server');
l.Add('openssl');
l.Add('strace');
l.Add('time');
l.Add('eject');
l.Add('locales');
l.Add('pciutils');
l.Add('usbutils');
l.add('iotop');


//PHP engines
l.Add('php5-cgi');
l.Add('php5-cli');
l.Add('php5-ldap');
l.Add('php5-mysql');
l.Add('php5-fpm');
l.Add('php5-gd');
l.add('php5-curl');
l.Add('php-pear');
l.add('php5-dev'); // To compile PHP5
l.Add('php5-imap');
l.add('php-net-sieve');
l.Add('php5-mcrypt');
l.Add('php-log');
l.add('php5-geoip');

l.add('iputils-arping');
l.add('libmodule-build-perl');
l.Add('librrds-perl');
l.add('dnsmasq');
l.Add('libwww-perl');
l.Add('libnss-ldap');
l.Add('libpam-ldap');
l.Add('ldap-utils');
l.Add('sasl2-bin');
l.add('libsasl2-dev');
l.Add('sudo');
l.Add('ntp');
l.Add('iproute');
l.add('bzip2');
l.add('zip');
l.add('re2c');
l.Add('libexpat1-dev');
l.add('scons');
l.add('binutils');
l.add('rsync');


l.add('zlib1g-dev');
l.Add('libpcre3-dev');
l.add('pkg-config');
l.add('libldap2-dev');
l.add('libpam0g-dev');
l.add('libcdio-dev');
l.add('libusb-dev');
l.add('libkrb5-dev');
l.add('zlib1g-dev');
l.add('libfreetype6-dev');
l.add('libt1-dev');
l.add('libpaper-dev');
l.add('libbz2-dev');
l.add('libxml2-dev');
l.add('libaudit-dev');
l.add('libgd-tools');
l.add('libfuse2');
l.add('libssl-dev');
l.add('libpcap0.8-dev');
l.add('libsasl2-dev');
l.add('libcdb-dev');
l.add('libpspell-dev');
l.add('libpng12-dev');
l.add('libaio-dev');
l.add('libattr1-dev');
l.add('libevent-dev');
l.add('python-dev');
l.add('libgeoip-dev');
l.add('libgeoip1');
l.add('libwrap0-dev');
l.add('gettext');
l.add('ruby');
l.add('dnsutils');
l.add('curlftpfs');
L.add('davfs2');
l.add('mtools');

//groupOffice:
//l.add('apache2-mpm-itk');

l.add('apache2');
l.add('apache2-mpm-prefork');
l.add('apache2-utils');
l.add('apache2.2-bin');
l.add('apache2.2-common');

l.add('apache2-prefork-dev');
l.add('libapache2-mod-php5');
l.add('libapache2-mod-evasive');
L.add('libapache2-mod-geoip');
l.Add('libapache2-mod-perl2');
l.Add('libapache2-mod-python');
l.add('libslp-dev');
l.add('libperl-dev');

l.add('libjpeg62-dev');
l.Add('discover');
l.Add('console-common');
l.Add('libmcrypt-dev');
l.Add('lighttpd');
      l.Add('rrdtool');
      l.Add('librrdp-perl');
      l.Add('libfile-tail-perl');
      l.Add('libgeo-ipfree-perl');
      l.Add('libgeo-ip-perl');
      l.add('sshfs');
      l.Add('hdparm');
      l.add('unrar-free');
      l.Add('libgssapi-perl');
      l.add('libdotconf-dev');
      l.add('dar');
      l.add('monit');
      l.add('stunnel4');
      l.add('libwbxml2-utils');
      l.add('memtester');
      l.add('procinfo');
      l.Add('libgeo-ip-perl');
      l.add('libcurl4-openssl-dev');
      l.add('libsnmp-dev');
      l.add('perl-modules');
      l.add('libmysqlclient-dev');
      l.add('update-notifier-common');
      l.add('libreadline-gplv2-dev');




        l.add('libltdl-dev');
        l.add('memtester');
        l.add('procinfo');
        l.Add('libgeo-ip-perl');
        l.add('libcurl4-openssl-dev');
        l.add('php5-geoip');
        l.add('libsnmp-dev');
        l.add('perl-modules');
        l.add('update-notifier-common');
        l.add('iputils-ping');
        l.add('libapache-mod-security');
        L.add('xtables-addons-common');
        L.add('xtables-addons-source');
        l.add('virt-what');
        l.add('hddtemp');
        l.add('libcurl4-openssl-dev');
        l.add('libsnmp-dev');
        l.Add('bzip2');
        l.add('unzip');
        l.Add('telnet');
        l.Add('lsof');
        // ZARAFA
        l.add('libboost-filesystem1.49.0');
        l.add('libicu48');



// Hamachi (  /usr/lib/lsb/install_initd )





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

end.
