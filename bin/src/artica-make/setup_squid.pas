unit setup_squid;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}
//ln -s /usr/lib/libmilter/libsmutil.a /usr/local/lib/libsmutil.a
//apt-get install libmilter-dev
interface

uses
  Classes, SysUtils,strutils,RegExpr in 'RegExpr.pas',
  unix,IniFiles,setup_libs,distridetect,
  install_generic,logs,squid,zsystem,dansguardian;

  type
  tsetup_squid=class


private
     libs:tlibs;
     distri:tdistriDetect;
     install:tinstall;
     source_folder,cmd:string;
     SYS:Tsystem;
public
      constructor Create();
      procedure Free;
      procedure xinstall(sourcepackage:string='');
      procedure dansgardian_install();
      procedure kav4proxy_install(norestart:boolean=false);
      procedure kavupdateutility_install();
      procedure sarg_install();
      function command_line_squid(path:string=''):string;
      procedure msktutil();
     procedure  squidguard_install();
     function   command_line_squidguard():string;
     procedure  squid32(sourcepackage:string='');
     procedure  dansguardian2();
     procedure  remove_squid();
     procedure  ecapav();
     procedure  socat();
END;

implementation

constructor tsetup_squid.Create();
begin
distri:=tdistriDetect.Create();
libs:=tlibs.Create;
install:=tinstall.Create;
SYS:=Tsystem.Create();
source_folder:='';
end;
//#########################################################################################
procedure tsetup_squid.Free();
begin
  libs.Free;
end;
//#########################################################################################
procedure tsetup_squid.ecapav();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
Arch:Integer;
squidbinpath,package_name:string;
begin
  CODE_NAME:='APP_ECAPAV';
  install.INSTALL_PROGRESS(CODE_NAME,'{checking}');
  install.INSTALL_STATUS(CODE_NAME,30);
  distri:=tdistriDetect.Create;
  Arch:=libs.ArchStruct();
  writeln('RESULT.................: Architecture : ',Arch);
  writeln('RESULT.................: Distribution : ',distri.DISTRINAME,' (DISTRINAME)');
  writeln('RESULT.................: Major version: ',distri.DISTRI_MAJOR,' (DISTRI_MAJOR)');
  writeln('RESULT.................: Artica Code  : ',distri.DISTRINAME_CODE,' (DISTRINAME_CODE)');
  if arch=32 then package_name:='ecapav-i386';
  if arch=64 then package_name:='ecapav-amd64';

  source_folder:=libs.COMPILE_GENERIC_APPS(package_name);
  if not DirectoryExists(source_folder) then begin
         install.INSTALL_STATUS(CODE_NAME,110);
         install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
         exit;
      end;
      source_folder:=extractFilePath(source_folder);
      writeln('source.................: ',source_folder);
      install.INSTALL_STATUS(CODE_NAME,50);
      install.INSTALL_STATUS(CODE_NAME,60);
      install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
      writeln('copy.................: ',source_folder, ' => ','/');
      fpsystem('/bin/cp -rfd '+source_folder+'* /');
      install.INSTALL_STATUS(CODE_NAME,90);

      if FIleExists('/usr/libexec/squid/ecap_adapter_av.so') then begin
            install.INSTALL_STATUS(CODE_NAME,100);
            install.INSTALL_PROGRESS(CODE_NAME,'{success}');

      end else begin
             install.INSTALL_STATUS(CODE_NAME,110);
            install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
      end;
end;
//#########################################################################################
procedure tsetup_squid.squid32(sourcepackage:string);
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
Arch:Integer;
squidbinpath,package_name:string;
begin
  CODE_NAME:='APP_SQUID2';

  if length(sourcepackage)>2 then begin
     if sourcepackage='squid2' then CODE_NAME:='APP_SQUID0';
     xinstall('squid2');
     exit;
  end;
  install.INSTALL_PROGRESS(CODE_NAME,'{checking}');
  install.INSTALL_STATUS(CODE_NAME,30);
  distri:=tdistriDetect.Create;
  Arch:=libs.ArchStruct();
  writeln('RESULT.................: Architecture : ',Arch);
  writeln('RESULT.................: Distribution : ',distri.DISTRINAME,' (DISTRINAME)');
  writeln('RESULT.................: Major version: ',distri.DISTRI_MAJOR,' (DISTRI_MAJOR)');
  writeln('RESULT.................: Artica Code  : ',distri.DISTRINAME_CODE,' (DISTRINAME_CODE)');
  if arch=32 then package_name:='squid32-i386';
  if arch=64 then package_name:='squid32-x64';

  source_folder:=libs.COMPILE_GENERIC_APPS(package_name);
  if not DirectoryExists(source_folder) then begin
         install.INSTALL_STATUS(CODE_NAME,110);
         install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
         exit;
      end;
      source_folder:=extractFilePath(source_folder);
      writeln('source.................: ',source_folder);



      install.INSTALL_STATUS(CODE_NAME,50);
      install.INSTALL_PROGRESS(CODE_NAME,'{uninstall}');
      fpsystem('/etc/init.d/artica-postfix stop squid');

      if FileExists('/usr/bin/apt-get') then fpsystem('/usr/bin/apt-get -y remove squid3 squid-client --purge');
      if DirectoryExists('/usr/share/squid3') then fpsystem('/bin/rm -rf /usr/share/squid3');
      if DirectoryExists('/lib/squid3') then fpsystem('/bin/rm -rf /lib/squid3');
      if DirectoryExists('/usr/share/squid-langpack') then fpsystem('/bin/rm -rf /usr/share/squid-langpack/*');


      if DirectoryExists('/lib64/squid3') then fpsystem('/bin/rm -rf /lib64/squid3');
      squidbinpath:=SYS.LOCATE_GENERIC_BIN('squid');
      if FileExists(squidbinpath) then fpsystem('/bin/rm -f '+squidbinpath );
      squidbinpath:=SYS.LOCATE_GENERIC_BIN('squid3');
      if FileExists(squidbinpath) then fpsystem('/bin/rm -f '+squidbinpath );
      if FileExists('/usr/bin/purge') then fpsystem('/bin/rm -f /usr/bin/purge');
      if FileExists('/usr/bin/squidclient') then fpsystem('/bin/rm -f /usr/bin/squidclient');
      install.INSTALL_STATUS(CODE_NAME,60);
      install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
      writeln('copy.................: ',source_folder, ' => ','/');
      if Not DirectoryExists('/usr/share/squid-langpack') then ForceDirectories('/usr/share/squid-langpack');
      fpsystem('/bin/cp -rfd '+source_folder+'* /');
      install.INSTALL_STATUS(CODE_NAME,90);
      install.INSTALL_PROGRESS(CODE_NAME,'{reconfigure}');
      fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --build --force');
      fpsystem('/etc/init.d/artica-postfix start squid-cache');
      install.INSTALL_STATUS(CODE_NAME,100);
      install.INSTALL_PROGRESS(CODE_NAME,'{success}');


end;
//#########################################################################################
procedure tsetup_squid.dansguardian2();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
Arch:Integer;
squidbinpath,package_name:string;
begin
  CODE_NAME:='APP_DANSGUARDIAN2';
  install.INSTALL_PROGRESS(CODE_NAME,'{checking}');
  install.INSTALL_STATUS(CODE_NAME,30);
  distri:=tdistriDetect.Create;
  Arch:=libs.ArchStruct();
  writeln('RESULT.................: Architecture : ',Arch);
  writeln('RESULT.................: Distribution : ',distri.DISTRINAME,' (DISTRINAME)');
  writeln('RESULT.................: Major version: ',distri.DISTRI_MAJOR,' (DISTRI_MAJOR)');
  writeln('RESULT.................: Artica Code  : ',distri.DISTRINAME_CODE,' (DISTRINAME_CODE)');
  if arch=32 then package_name:='dansguardian2-i386';
  if arch=64 then package_name:='dansguardian2-x64';

  source_folder:=libs.COMPILE_GENERIC_APPS(package_name);
  if not DirectoryExists(source_folder) then begin
         install.INSTALL_STATUS(CODE_NAME,110);
         install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
         exit;
      end;
      source_folder:=extractFilePath(source_folder);
      writeln('source.................: ',source_folder);
      install.INSTALL_STATUS(CODE_NAME,50);
      install.INSTALL_STATUS(CODE_NAME,60);
      install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
      writeln('copy.................: ',source_folder, ' => ','/');
      fpsystem('/bin/cp -rfd '+source_folder+'* /');
      install.INSTALL_STATUS(CODE_NAME,90);
      install.INSTALL_PROGRESS(CODE_NAME,'{reconfigure}');
      fpsystem('/etc/init.d/artica-postfix restart dansguardian');
      install.INSTALL_STATUS(CODE_NAME,100);
      install.INSTALL_PROGRESS(CODE_NAME,'{success}');

end;

procedure tsetup_squid.kavupdateutility_install();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion,cp:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
begin

 CODE_NAME:='APP_KAVUTILITY2';
 squid:=tsquid.Create;
 logs:=Tlogs.Create;
 SYS:=Tsystem.Create();
 cp:=SYS.LOCATE_GENERIC_BIN('cp');

 install.INSTALL_PROGRESS(CODE_NAME,'{checking}');
 install.INSTALL_PROGRESS(CODE_NAME,'{downloading}');
 install.INSTALL_STATUS(CODE_NAME,30);
 source_folder:=libs.COMPILE_GENERIC_APPS('kavupdater2');
 writeln('Exploded in '+source_folder);

 if Not FileExists(source_folder+'/UpdateUtility-Console') then begin
         writeln('Exploded in '+source_folder+'/UpdateUtility-Console no such file');
         source_folder:=ExcludeTrailingBackslash(extractFilePath(source_folder));
        if Not FileExists(source_folder+'/UpdateUtility-Console') then begin
           writeln('Exploded in '+source_folder+'/UpdateUtility-Console no such file exiting');
           install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
           install.INSTALL_STATUS(CODE_NAME,100);
           exit;
       end;
 end;

writeln('Working directory is `',source_folder,'`');
install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
install.INSTALL_STATUS(CODE_NAME,50);
fpsystem(cp+' '+source_folder+'/UpdateUtility-Console /usr/sbin/');
fpsystem(cp+' '+source_folder+'/UpdateUtility-Gui /usr/sbin/');
ForceDirectories('/etc/UpdateUtility/lib');
fpsystem(cp+' '+source_folder+'/important_legal_notice.txt /etc/UpdateUtility/');
fpsystem(cp+' '+source_folder+'/license.txt /etc/UpdateUtility/');
fpsystem(cp+' '+source_folder+'/locale.ini /etc/UpdateUtility/');
fpsystem(cp+' '+source_folder+'/ReleaseNotes.txt /etc/UpdateUtility/');
fpsystem(cp+' '+source_folder+'/updater.ini /etc/UpdateUtility/');
fpsystem(cp+' '+source_folder+'/updater.xml /etc/UpdateUtility/');
fpsystem(cp+' -r '+source_folder+'/lib/*  /etc/UpdateUtility/lib/');
Writeln('Done...');
install.INSTALL_PROGRESS(CODE_NAME,'{success}');
install.INSTALL_STATUS(CODE_NAME,100);
end;


procedure tsetup_squid.msktutil();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
begin

 CODE_NAME:='APP_MSKTUTIL';
 squid:=tsquid.Create;
 logs:=Tlogs.Create;
 SYS:=Tsystem.Create();

 install.INSTALL_PROGRESS(CODE_NAME,'{checking}');

  if FileExists(SYS.LOCATE_GENERIC_BIN('msktutil')) then begin
       install.INSTALL_STATUS(CODE_NAME,100);
       install.INSTALL_PROGRESS(CODE_NAME,'{installed}');
       exit;
  end;
  install.INSTALL_PROGRESS(CODE_NAME,'{downloading}');
  install.INSTALL_STATUS(CODE_NAME,30);


  source_folder:=libs.COMPILE_GENERIC_APPS('msktutil');
  if not DirectoryExists(source_folder) then begin
     writeln('Install msktutil failed...');
     install.INSTALL_STATUS(CODE_NAME,110);
     install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
     exit;
  end;
  SetCurrentDir(source_folder);
  install.INSTALL_PROGRESS(CODE_NAME,'{compiling}');
  if FileExists(source_folder+'/msktutil_0.3.16-7.diff') then fpsystem('patch < '+source_folder+'/msktutil_0.3.16-7.diff');

       cmd:='./configure --prefix=/usr --includedir="\${prefix}/include" --mandir="\${prefix}/share/man"';
       cmd:=cmd + ' --infodir="\${prefix}/share/info" --sysconfdir=/etc --localstatedir=/var';
       writeln(cmd);

       fpsystem(cmd);
       install.INSTALL_STATUS(CODE_NAME,60);
       fpsystem('make');
       install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
       fpsystem('make install');
       install.INSTALL_STATUS(CODE_NAME,80);

       if not FileExists(SYS.LOCATE_GENERIC_BIN('msktutil')) then begin
          writeln('Compilation failed....');
          writeln('');
          install.INSTALL_STATUS(CODE_NAME,110);
          install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
          exit;
       end;

  install.INSTALL_STATUS(CODE_NAME,100);
  install.INSTALL_PROGRESS(CODE_NAME,'{installed}');
  SetCurrentDir('/root');
  writeln('success');
end;
//#########################################################################################
procedure tsetup_squid.sarg_install();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
CODE_NAME:string;
begin

 CODE_NAME:='APP_SARG';
 squid:=tsquid.Create;
 logs:=Tlogs.Create;
 SYS:=Tsystem.Create();

 install.INSTALL_PROGRESS(CODE_NAME,'{checking}');
 source_folder:=libs.COMPILE_GENERIC_APPS('sarg');
  if not DirectoryExists(source_folder) then begin
     writeln('Install sarg failed...');
     install.INSTALL_STATUS(CODE_NAME,110);
     install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
     exit;
  end;
  SetCurrentDir(source_folder);
  install.INSTALL_PROGRESS(CODE_NAME,'{compiling}');
       if FileExists('/usr/sbin/sarg') then fpsystem('/bin/rm /usr/sbin/sarg');

       cmd:='./configure --prefix=/usr --mandir=\${prefix}/share/man';
       cmd:=cmd+' --enable-htmldir=/usr/share/artica-postfix/sarg --enable-bindir=/usr/bin --enable-sysconfdir=/etc/squid3 --enable-mandir=/usr/share/man/man1 --localstatedir=/var --infodir="\${prefix}/share/info" --enable-extraprotection';
       cmd:=cmd+' --enable-sargphp=/usr/share/artica-postfix/sarg';
       writeln(cmd);

       fpsystem(cmd);
       install.INSTALL_STATUS(CODE_NAME,60);
       fpsystem('make');
       install.INSTALL_PROGRESS(CODE_NAME,'{installing}');
       fpsystem('make install');
       install.INSTALL_STATUS(CODE_NAME,80);

       if not FileExists('/usr/bin/sarg') then begin
          writeln('Compilation failed....');
          writeln('');
          install.INSTALL_STATUS(CODE_NAME,110);
          install.INSTALL_PROGRESS(CODE_NAME,'{failed}');
          exit;
       end;

  if DirectoryExists(source_folder+'/sarg-php') then begin
     forceDirectories('/usr/share/artica-postfix/squid');

  end;

  squid.SARG_CONFIG();

  install.INSTALL_STATUS(CODE_NAME,100);
  install.INSTALL_PROGRESS(CODE_NAME,'{installed}');
  SetCurrentDir('/root');
  writeln('success');
end;
//#########################################################################################
function tsetup_squid.command_line_squid(path:string):string;
var cmd:string;
ntlm_auth:string;
ntlm_auth_helper:string;
enable_ntlm_auth_helpers:string;
enable_basic_auth_helpers:string;
enable_negotiate_auth_helpers:string;
squid_kerb_auth,negotiate,digest:string;
sourcepackage:string;
begin



       if not DirectoryExists(path) then begin
          writeln('Some token needs to parse the source directory.');
          writeln('In your case, there is no directory set, parameters will be by default');
          writeln('');
       end;



       cmd:='./configure ';
       cmd:=cmd+' --prefix=/usr ';
       cmd:=cmd+' --includedir=${prefix}/include ';
       cmd:=cmd+' --mandir=${prefix}/share/man ';
       cmd:=cmd+' --infodir=${prefix}/share/info ';
       cmd:=cmd+' --sysconfdir=/etc ';
       cmd:=cmd+' --localstatedir=/var ';
       cmd:=cmd+' --libexecdir=${prefix}/lib/squid3 ';
       cmd:=cmd+' --disable-maintainer-mode ';
       cmd:=cmd+' --disable-dependency-tracking ';
       cmd:=cmd+' --srcdir=. ';
       cmd:=cmd+' --datadir=/usr/share/squid3';
       cmd:=cmd+' --sysconfdir=/etc/squid3';
       cmd:=cmd+' --mandir=/usr/share/man';
       cmd:=cmd+' --enable-gnuregex';
       cmd:=cmd+' --enable-forward-log';
       cmd:=cmd+' --enable-removal-policy=heap';
       cmd:=cmd+' --enable-follow-x-forwarded-for';
       cmd:=cmd+' --enable-cache-digests';
       cmd:=cmd+' --enable-http-violations';
       cmd:=cmd+' --enable-large-cache-files';
       cmd:=cmd+' --enable-removal-policies=lru,heap';
       cmd:=cmd+' --enable-err-languages=English';
       cmd:=cmd+' --enable-default-err-language=English';
       cmd:=cmd+' --with-maxfd=32000 ';
       cmd:=cmd+' --with-large-files ';
       cmd:=cmd+' --disable-dlmalloc ';
       cmd:=cmd+' --with-pthreads ';
       cmd:=cmd+' --enable-esi';
       cmd:=cmd+' --enable-storeio=aufs,diskd,ufs';
       cmd:=cmd+' --with-aufs-threads=10';
       cmd:=cmd+' --with-maxfd=16384';
       cmd:=cmd+' --enable-useragent-log ';
       cmd:=cmd+' --enable-referer-log ';
       cmd:=cmd+' --enable-x-accelerator-vary ';
       cmd:=cmd+' --with-dl ';
       cmd:=cmd+' --enable-basic-auth-helpers=LDAP';
       cmd:=cmd+' --enable-truncate';
       cmd:=cmd+' --enable-linux-netfilter';
       cmd:=cmd+' --with-filedescriptors=16384';
       cmd:=cmd+' --enable-http-violations';
       cmd:=cmd+' --enable-wccpv2';
       cmd:=cmd+' --enable-arp-acl';

       digest:=',digest';

       if FileExists(SYS.LOCATE_GENERIC_BIN('smbd')) then begin
          ntlm_auth:=',ntlm';
          enable_ntlm_auth_helpers:=' --enable-ntlm-auth-helpers=no_check';
          if DirectoryExists(path+'/helpers/ntlm_auth/smb_lm') then enable_ntlm_auth_helpers:=enable_ntlm_auth_helpers+',smb_lm';
          if DirectoryExists(path+'/helpers/ntlm_auth/SMB') then enable_ntlm_auth_helpers:=enable_ntlm_auth_helpers+',SMB';
          enable_basic_auth_helpers:=',MSNT,multi-domain-NTLM,SMB ';
       end;

       if FileExists(SYS.LOCATE_GENERIC_BIN('msktutil')) then begin
         enable_negotiate_auth_helpers:=' --enable-negotiate-auth-helpers=squid_kerb_auth --enable-stacktraces';
         digest:='';
         negotiate:=',negotiate';
       end;



       cmd:=cmd+' --enable-auth=basic'+digest+ntlm_auth+negotiate;
       cmd:=cmd+' --enable-digest-auth-helpers=ldap,password';
       cmd:=cmd+' --enable-external-acl-helpers=ip_user,ldap_group,unix_group,wbinfo_group';
       cmd:=cmd+' --enable-basic-auth-helpers=LDAP'+enable_basic_auth_helpers;
       cmd:=cmd+enable_negotiate_auth_helpers;
       cmd:=cmd+enable_ntlm_auth_helpers;
       cmd:=cmd+' --with-default-user=squid ';
       cmd:=cmd+' --enable-icap-client';
       cmd:=cmd+' --enable-cache-digests';
       cmd:=cmd+' --enable-icap-support';
       cmd:=cmd+' --enable-poll ';
       cmd:=cmd+' --enable-epoll ';
       cmd:=cmd+' --enable-async-io ';
       cmd:=cmd+' --enable-delay-pools ';
       cmd:=cmd+' --enable-ssl';
       cmd:=cmd+' --enable-ssl-crtd';
       cmd:=cmd+' CFLAGS="-DNUMTHREADS=60 -O3 -pipe -fomit-frame-pointer -funroll-loops -ffast-math -fno-exceptions" CPPFLAGS="-I../libltdl"';
       writeln(cmd);
       result:=cmd;

end;

procedure tsetup_squid.xinstall(sourcepackage:string);
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
PCKG_NAME:string;
begin
 squid:=tsquid.Create;
 logs:=Tlogs.Create;
 SYS:=Tsystem.Create();
 PCKG_NAME:='APP_SQUID';
 if length(sourcepackage)>3 then begin
      if sourcepackage='squid2' then PCKG_NAME:='APP_SQUID0';
 end;
 install.INSTALL_STATUS(PCKG_NAME,10);

 if length(sourcepackage)=0 then  begin
 localversion:=squid.SQUID_VERSION();


 LocalBinVersion:=squid.SQUID_BIN_VERSION(localversion);
 if LocalBinVersion>=320000000000 then begin
    writeln('Local version is a 3.2x this command will return back to 3.1x branch');
    LocalBinVersion:=300000000000;
    PCKG_NAME:='APP_SQUID31';
 end;


 if ParamStr(2)<>'--reconfigure' then begin
    writeln('Check versions...');
    remoteversion:=libs.COMPILE_VERSION_STRING('squid3');
    remoteBinVersion:=squid.SQUID_BIN_VERSION(remoteversion);
    writeln('Local version...........: ',LocalBinVersion,' as ',localversion);
    writeln('Remote version..........: ',remoteBinVersion,' as ',remoteversion);
 
    if LocalBinVersion>=remoteBinVersion then begin
       writeln('No changes..........: Success');
       install.INSTALL_PROGRESS(PCKG_NAME,'{installed}');
       install.INSTALL_STATUS(PCKG_NAME,100);
       exit();
    end;
 end;
 end;
 msktutil();

 writeln('Prepare installation or upgrade....');
 install.INSTALL_STATUS(PCKG_NAME,30);
 writeln('whereis ??? ->');
 fpsystem('whereis gcc');
 fpsystem('whereis make');

 install.INSTALL_PROGRESS(PCKG_NAME,'{downloading}');
 
   if length(sourcepackage)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('squid3');
    if length(sourcepackage)>2 then source_folder:=libs.COMPILE_GENERIC_APPS(sourcepackage);
  if not DirectoryExists(source_folder) then begin
     writeln('Install '+PCKG_NAME+' failed...');
     install.INSTALL_STATUS(PCKG_NAME,110);
     exit;
  end;
  SetCurrentDir(source_folder);
  install.INSTALL_PROGRESS(PCKG_NAME,'{compiling}');
  cmd:=command_line_squid(source_folder);
  fpsystem(cmd);


  install.INSTALL_PROGRESS(PCKG_NAME,'{compiling}');
  install.INSTALL_STATUS('APP_SQUID',60);
  if FileExists('/usr/sbin/squid3') then fpsystem('/bin/rm -f /usr/sbin/squid3');
  if fileExists(SYS.LOCATE_GENERIC_BIN('squid3')) then fpsystem('/bin/rm -f '+SYS.LOCATE_GENERIC_BIN('squid3'));

  install.INSTALL_PROGRESS(PCKG_NAME,'{compiling}');
  install.INSTALL_STATUS(PCKG_NAME,70);
  fpsystem('make');

  install.INSTALL_PROGRESS(PCKG_NAME,'{removing}');
  install.INSTALL_STATUS(PCKG_NAME,75);
  remove_squid();
  install.INSTALL_PROGRESS(PCKG_NAME,'{installing}');
  install.INSTALL_STATUS(PCKG_NAME,80);
  fpsystem('make install');


       if not FileExists(squid.SQUID_BIN_PATH()) then begin
          writeln('Compilation failed....');
          writeln('');
          install.INSTALL_STATUS(PCKG_NAME,110);
          install.INSTALL_PROGRESS(PCKG_NAME,'{failed}');
          exit;
       end;


       if not FileExists(source_folder + '/helpers/digest_auth/ldap/digest_ldap_auth') then begin
          writeln('Compilation failed....' +source_folder + '/helpers/digest_auth/ldap/digest_ldap_auth does not exists');
          writeln('');
          install.INSTALL_STATUS(PCKG_NAME,110);
          install.INSTALL_PROGRESS(PCKG_NAME,'{failed}');
          SetCurrentDir('/root');
          exit;
       end;

  install.INSTALL_PROGRESS(PCKG_NAME,'{installing}');
  if FileExists(source_folder + '/helpers/digest_auth/ldap/digest_ldap_auth') then fpsystem('/bin/cp -rfv ' + source_folder + '/helpers/digest_auth/ldap/digest_ldap_auth /usr/lib/squid3/');
  logs.DeleteFile('/etc/artica-postfix/versions.cache');
  install.INSTALL_STATUS(PCKG_NAME,90);
  fpsystem('/usr/share/artica-postfix/bin/process1 --force');
  fpsystem(SYS.LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.squid.php --build --force');
  fpsystem('/etc/init.d/artica-postfix restart squid');
  install.INSTALL_STATUS(PCKG_NAME,100);
  install.INSTALL_PROGRESS(PCKG_NAME,'{installed}');
  SetCurrentDir('/root');
  writeln('success');
end;
//#########################################################################################
procedure tsetup_squid.dansgardian_install();
var
source_folder:string;
logs:Tlogs;
SYS:TSystem;
squid:tsquid;
localversion,cmd:string;
remoteversion:string;
remoteBinVersion:int64;
LocalBinVersion:int64;
dans:tdansguardian;
begin
 squid:=tsquid.Create;
 logs:=Tlogs.Create;
 SYS:=Tsystem.Create();
 install.INSTALL_STATUS('APP_DANSGUARDIAN',10);
 install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{checking}');
 dans:=tdansguardian.Create(SYS);
 SetCurrentDir('/root');

 localversion:=dans.DANSGUARDIAN_VERSION;
 LocalBinVersion:=dans.DANSGUARDIAN_BIN_VERSION(localversion);

remoteversion:=libs.COMPILE_VERSION_STRING('dansguardian');
remoteBinVersion:=dans.DANSGUARDIAN_BIN_VERSION(remoteversion);

 if LocalBinVersion>=remoteBinVersion then begin
     writeln('No changes..........: Success ( remote=',remoteBinVersion,' local=',LocalBinVersion,')');
     install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{installed}');
     install.INSTALL_STATUS('APP_DANSGUARDIAN',100);
     exit();
 end;
 writeln('Prepare installation or upgrade....');
 install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{downloading}');

  source_folder:=libs.COMPILE_GENERIC_APPS('dansguardian');
  if not DirectoryExists(source_folder) then begin
     writeln('Install dansguardian failed...');
     install.INSTALL_STATUS('APP_DANSGUARDIAN',110);
     install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{failed}');
     exit;
  end;

cmd:='./configure';
cmd:=cmd+' --mandir=/usr/share/man/';
cmd:=cmd+' --enable-clamd=yes';
cmd:=cmd+' --with-proxyuser=squid';
cmd:=cmd+' --with-proxygroup=squid';
cmd:=cmd+' --prefix=/usr';
cmd:=cmd+' --mandir=\${prefix}/share/man';
cmd:=cmd+' --infodir=\${prefix}/share/info';
cmd:=cmd+' --sysconfdir=/etc';
cmd:=cmd+' --localstatedir=/var';
cmd:=cmd+' --enable-commandline=no';
cmd:=cmd+' --enable-fancydm=no';
cmd:=cmd+' --enable-trickledm=yes';
cmd:=cmd+' --enable-email=yes';
cmd:=cmd+' --enable-ntlm=yes';
  SetCurrentDir(source_folder);
  install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{compiling}');
writeln('Using : '+cmd);
fpsystem(cmd);
fpsystem('make && make install');
SetCurrentDir('/root');

localversion:=dans.DANSGUARDIAN_VERSION;
LocalBinVersion:=dans.DANSGUARDIAN_BIN_VERSION(localversion);

 remoteversion:=libs.COMPILE_VERSION_STRING('dansguardian');
 remoteBinVersion:=dans.DANSGUARDIAN_BIN_VERSION(remoteversion);
 fpsystem('/etc/init.d/artica-postfix restart dansguardian');
 logs.DeleteFile('/etc/artica-postfix/versions.cache');

 if not fileExists(dans.BIN_PATH()) then begin
    install.INSTALL_STATUS('APP_DANSGUARDIAN',110);
    install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{failed}');
    exit;
 end;


install.INSTALL_STATUS('APP_DANSGUARDIAN',100);
     install.INSTALL_PROGRESS('APP_DANSGUARDIAN','{installed}');
if LocalBinVersion=remoteBinVersion then begin
     writeln('success "',LocalBinVersion,'"');


end else begin
    SetCurrentDir('/root');
end;

end;
//#########################################################################################
procedure tsetup_squid.remove_squid();
var
   l:Tstringlist;
begin
     fpsystem('/etc/init.artica-postfix stop squid');
     if DirectoryExists('/lib/squid3') then fpsystem('/bin/rm -rf /lib/squid3');
     if DirectoryExists('/usr/share/squid-langpack') then fpsystem('/bin/rm -rf /usr/share/squid-langpack');
     if FileExists('/usr/sbin/squid') then fpsystem('/bin/rm /usr/sbin/squid');
     if FileExists('/usr/bin/squidclient') then fpsystem('/bin/rm /usr/bin/squidclient');
end;
//#########################################################################################
procedure tsetup_squid.socat();
begin

    if FileExists('/usr/bin/socat') then exit;

    if Not FileExists('/usr/share/artica-postfix/bin/install/socat.tar.gz') then begin
       writeln('/usr/share/artica-postfix/bin/install/socat.tar.gz no such file');
       exit;
    end;


    source_folder:=libs.ExtractLocalPackage('/usr/share/artica-postfix/bin/install/socat.tar.gz');
    if not DirectoryExists(source_folder) then begin
     writeln('Install socat failed...');
     exit;
   end;

   SetCurrentDir(source_folder);
   fpsystem('./configure --prefix=/usr');
   fpsystem('make');
   fpsystem('make install');
   if FileExists('/usr/bin/socat') then writeln('Success...');
end;
//#########################################################################################





procedure tsetup_squid.kav4proxy_install(norestart:boolean);
var
source_folder:string;
SYS:Tsystem;
autoanswers_conf:TstringList;
zsquid:Tsquid;
begin
 writeln('Prepare installation or upgrade....');
 install.INSTALL_PROGRESS('APP_KAV4PROXY','{downloading}');

 if FileExists('/home/artica/packages/kav4proxy-5.5-62.tar.gz') then begin
    fpsystem('/bin/tar -xf /home/artica/packages/kav4proxy-5.5-62.tar.gz -C /root/');
    source_folder:='/root/kav4proxy-5.5-62';
 end;

 if FileExists('/home/artica/packages/kav4proxy-5.5-80.tar.gz')  then begin
    fpsystem('/bin/tar -xf /home/artica/packages/kav4proxy-5.5-80.tar.gz -C /root/');
    source_folder:='/root/kav4proxy-5.5-80';

 end;
 if DirectoryExists('/root/kav4proxy_5.5-86') then  source_folder:='/root/kav4proxy_5.5-86';
 if length(source_folder)=0 then source_folder:=libs.COMPILE_GENERIC_APPS('kav4proxy');

if not DirectoryExists(source_folder) then begin
     writeln('Install Kav4Proxy failed...');
     install.INSTALL_STATUS('APP_KAV4PROXY',110);
     install.INSTALL_PROGRESS('APP_KAV4PROXY','{failed}');
     exit;
  end;


forceDirectories('/opt/kaspersky/kav4proxy/sbin');
forceDirectories('/etc/opt/kaspersky');


  install.INSTALL_PROGRESS('APP_KAV4PROXY','{installing}');
  install.INSTALL_STATUS('APP_KAV4PROXY',50);
fpsystem('/bin/rm -rf /var/opt/kaspersky/kav4proxy/bases >/dev/null');
forceDirectories('/var/opt/kaspersky/kav4proxy/bases');
fpsystem('cp -rfv ' + source_folder+'/opt /');
fpsystem('cp -rfv ' + source_folder+'/etc /');
fpsystem('cp -rfv ' + source_folder+'/var /');

   if not FileExists('/opt/kaspersky/kav4proxy/sbin/kav4proxy-kavicapserver') then begin
       install.INSTALL_STATUS('APP_KAV4PROXY',110);
       install.INSTALL_PROGRESS('APP_KAV4PROXY','{failed}');
       exit;
   end;

install.INSTALL_PROGRESS('APP_KAV4PROXY','{compiling}');
install.INSTALL_STATUS('APP_KAV4PROXY',70);


 fpsystem('ln -s --force /opt/kaspersky/kav4proxy/lib/bin/kav4proxy /etc/init.d/kav4proxy');
 install.INSTALL_SERVICE('kav4proxy');
 fpsystem('/usr/share/artica-postfix/bin/install/kavgroup/kav4prox_predoinst.sh');
 SYS:=TSystem.Create();
 SYS.CreateGroup('klusers');
 SYS.AddUserToGroup('kluser','klusers','','');
 writeln('creating klusers:kluser account OK');
 fpsystem('/bin/chown -R kluser:klusers /var/log/kaspersky/kav4proxy');
 fpsystem('/bin/chown -R kluser:klusers /var/opt/kaspersky/kav4proxy');
 fpsystem('/bin/chown -R kluser:klusers /var/run/kav4proxy');
 fpsystem('/bin/chown -R kluser:klusers /var/opt/kaspersky/kav4proxy');
 fpsystem('/bin/chmod 0755 /var/opt/kaspersky/kav4proxy');




autoanswers_conf:=TStringList.Create;
autoanswers_conf.Add('EULA_AGREED=yes');
autoanswers_conf.Add('');
autoanswers_conf.SaveToFile('/var/opt/kaspersky/kav4proxy/installer.dat');
autoanswers_conf.free;

         zsquid:=Tsquid.Create();
         autoanswers_conf:=TStringList.Create;
         autoanswers_conf.Add('CONFIGURE_ENTER_KEY_PATH=/usr/share/artica-postfix/bin/install');
         autoanswers_conf.Add('KAVMS_SETUP_LICENSE_DOMAINS=*');
         autoanswers_conf.Add('CONFIGURE_KEEPUP2DATE_ASKPROXY=no');
         autoanswers_conf.Add('CONFIGURE_RUN_KEEPUP2DATE=no');
         autoanswers_conf.Add('CONFIGURE_WEBMIN_ASKCFGPATH=');
         autoanswers_conf.Add('KAV4PROXY_SETUP_TYPE=3');
         autoanswers_conf.Add('KAV4PROXY_SETUP_LISTENADDRESS=127.0.0.1:1344');
         autoanswers_conf.Add('KAV4PROXY_SETUP_CONFPATH='+zsquid.SQUID_CONFIG_PATH());
         autoanswers_conf.Add('KAV4PROXY_SETUP_BINPATH='+zsquid.SQUID_BIN_PATH());
         autoanswers_conf.Add('KAV4PROXY_CONFIRM_FOUND=Y');
         autoanswers_conf.Add('KAVICAP_SETUP_NONICAPCFG=Y');
         autoanswers_conf.SaveToFile('/opt/kaspersky/kav4proxy/lib/bin/setup/autoanswers.conf');
         autoanswers_conf.Free;

 install.INSTALL_PROGRESS('APP_KAV4PROXY','{installing}');
 install.INSTALL_STATUS('APP_KAV4PROXY',90);

         SetCurrentDir('/opt/kaspersky/kav4proxy/lib/bin/setup');
         fpsystem('./postinstall.pl');

         if not norestart then fpsystem('/opt/kaspersky/kav4proxy/bin/kav4proxy-licensemanager -a /usr/share/artica-postfix/bin/install/KAVPROXY.key');
         if not norestart then begin
            fpSystem('/opt/kaspersky/kav4proxy/bin/kav4proxy-keepup2date -q -d /var/run/kav4proxy/keeup2date.pid &');
             sleep(500);
             writeln('running updates OK');
         end;
 install.INSTALL_PROGRESS('APP_KAV4PROXY','{installed}');
 install.INSTALL_STATUS('APP_KAV4PROXY',100);
 SetCurrentDir('/root');
 kavupdateutility_install();
 if FileExists('/home/artica/packages/kav4proxy-5.5-62.tar.gz') then fpsystem('/bin/rm -f /home/artica/packages/kav4proxy-5.5-62.tar.gz');
 if FileExists('/home/artica/packages/kav4proxy-5.5-80.tar.gz') then fpsystem('/bin/rm -f /home/artica/packages/kav4proxy-5.5-80.tar.gz');
 if not norestart then fpsystem('/etc/init.d/artica-postfix restart squid');




end;
//#########################################################################################
function tsetup_squid.command_line_squidguard():string;
var
   cmd:string;

begin


cmd:='./configure --prefix=/usr --mandir=\${prefix}/share/man';
cmd:=cmd+' --infodir=\${prefix}/share/info';
cmd:=cmd+' --with-sg-config=/etc/squid/squidGuard.conf';
cmd:=cmd+' --with-sg-logdir=/var/log/squid';
cmd:=cmd+' --with-sg-dbhome=/var/lib/squidguard/db';
cmd:=cmd+' --with-db=/usr --with-ldap';

result:=cmd;

end;
//#########################################################################################
procedure tsetup_squid.squidguard_install();
var
source_folder:string;
SYS:Tsystem;
autoanswers_conf:TstringList;
zsquid:Tsquid;
begin
 writeln('Prepare installation or upgrade....');

 install.INSTALL_PROGRESS('APP_SQUIDGUARD','{downloading}');
 source_folder:=libs.COMPILE_GENERIC_APPS('squidGuard');
  install.INSTALL_STATUS('APP_SQUIDGUARD',30);

if not DirectoryExists(source_folder) then begin
     writeln('Install squidGuard failed...');
     install.INSTALL_STATUS('APP_SQUIDGUARD',110);
     install.INSTALL_PROGRESS('APP_SQUIDGUARD','{failed}');
     exit;
  end;

 ForceDirectories('/root/artica/squidguard');
 fpsystem('/bin/cp -rf '+source_folder+'/* /root/artica/squidguard/');
 fpsystem('/bin/rm -rf '+source_folder);
 SetCurrentDir('/root/artica/squidguard');

  install.INSTALL_PROGRESS('APP_SQUIDGUARD','{compiling}');
  install.INSTALL_STATUS('APP_SQUIDGUARD',40);
  cmd:=command_line_squidguard();
  fpsystem(cmd);
  install.INSTALL_STATUS('APP_SQUIDGUARD',60);
  fpsystem('make');
  install.INSTALL_STATUS('APP_SQUIDGUARD',70);
  fpsystem('/etc/init.d/artica-postfix stop squid');
  fpsystem('make install');
  install.INSTALL_STATUS('APP_SQUIDGUARD',80);

  if not FileExists('/usr/bin/squidGuard') then begin
     install.INSTALL_PROGRESS('APP_SQUIDGUARD','{failed}');
     install.INSTALL_STATUS('APP_SQUIDGUARD',110);

  end else begin
     install.INSTALL_PROGRESS('APP_SQUIDGUARD','{installed}');
     install.INSTALL_STATUS('APP_SQUIDGUARD',100);
  end;
     SetCurrentDir('/root');
     fpsystem('/bin/rm -rf /root/artica/squidguard');
     fpsystem('/etc/init.d/artica-postfix start squid');

end;
//#########################################################################################
end.
