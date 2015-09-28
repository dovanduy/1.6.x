unit distriDetect;
{$MODE DELPHI}
//{$mode objfpc}{$H+}
{$LONGSTRINGS ON}

interface

uses
  Classes, SysUtils,RegExpr in 'RegExpr.pas',unix,IniFiles,setup_libs,md5;
type
  TStringDynArray = array of string;
  type
  tdistriDetect=class


private
    function COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
    function FILE_TEMP():string;
    function MD5FromString(value:string):string;
    function GET_INFO(key:string):string;
    function ReadFileIntoString(path:string):string;
    function ReadFromFile(TargetPath:string):string;


public
      DISTRINAME:string;
      DISTRINAME_CODE:string;
      DISTRINAME_VERSION:string;
      DISTRI_MAJOR:integer;
      DISTRI_MINOR:integer;
      constructor Create();
      procedure Free;
      function LINUX_DISTRIBUTION():string;
      function LINUX_CODE():string;
      procedure removePackage(packagename:string);
      function RpmOrDeb():string;
      function ArchStruct():integer;

END;

implementation

constructor tdistriDetect.Create();
begin

DISTRINAME:=LINUX_DISTRIBUTION();
LINUX_CODE();


end;
//#########################################################################################
procedure tdistriDetect.Free();
begin

end;
//#########################################################################################
function tdistriDetect.RpmOrDeb():string;
begin
  if DISTRINAME_CODE='UBUNTU' then begin
     result:='deb';
     exit;
  end;

  if DISTRINAME_CODE='DEBIAN' then begin
     result:='deb';
     exit;
  end;

  if DISTRINAME_CODE='SUSE' then begin
     result:='rpm';
     exit;
  end;
    if DISTRINAME_CODE='FEDORA' then begin
     result:='rpm';
     exit;
  end;
  if DISTRINAME_CODE='CENTOS' then begin
     result:='rpm';
     exit;
  end;

  if DISTRINAME_CODE='MANDRAKE' then begin
     result:='rpm';
     exit;
  end;
end;
//#########################################################################################
function tdistriDetect.LINUX_CODE():string;
var distri:string;
  RegExpr:TRegExpr;
  D:boolean;
begin
  D:=COMMANDLINE_PARAMETERS('--verbose');
  distri:=DISTRINAME;
  
  if D then writeln('DISTRINAME='+DISTRINAME);
  
  RegExpr:=TRegExpr.Create;
  RegExpr.Expression:='Ubuntu';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='UBUNTU';
     RegExpr.Expression:='\s+([0-9]+)\.([0-9]+)';
     if RegExpr.Exec(distri) then begin
        DISTRINAME_VERSION:=RegExpr.Match[1];
        TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
        TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
        exit;
     end;

     RegExpr.Expression:='\s+([0-9]+)';
     if RegExpr.Exec(distri) then begin
        DISTRINAME_VERSION:=RegExpr.Match[1];
        TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
        DISTRI_MINOR:=0;
        exit;
     end;

  end;
  
  RegExpr.Expression:='SUSE.+?Enterprise';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='SUSE';
     exit;
  end;

  RegExpr.Expression:='ArchLinux';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='ARCHLINUX';
     exit;
  end;


  
  RegExpr.Expression:='openSUSE';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='SUSE';
     exit;
  end;

  RegExpr.Expression:='Fedora';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='FEDORA';
     RegExpr.Expression:='release\s+([0-9]+)';
     if RegExpr.Exec(distri) then begin
        DISTRINAME_VERSION:=RegExpr.Match[1];
        TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
        exit;
     end;
     exit;
  end;
  
  RegExpr.Expression:='Debian';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='DEBIAN';
     RegExpr.Expression:='Debian\s+([0-9]+)';

     DISTRI_MINOR:=0;
     if RegExpr.Exec(distri) then begin
        DISTRINAME_VERSION:=RegExpr.Match[1];
        TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
        RegExpr.Expression:='Debian\s+([0-9]+)\.([0-9]+)';
        if RegExpr.Exec(distri) then begin
             TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
             TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
        end;
     end;
     exit;
  end;
  
  RegExpr.Expression:='CentOS';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='CENTOS';
     exit;
  end;

  RegExpr.Expression:='Scientific Linux release';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='CENTOS';
     exit;
  end;


  
  RegExpr.Expression:='Mandriva';
  if RegExpr.Exec(distri) then begin
     DISTRINAME_CODE:='MANDRAKE';
     exit;
  end;
  
end;



function tdistriDetect.LINUX_DISTRIBUTION():string;
var
   RegExpr:TRegExpr;
   FileTMP:TstringList;
   Filedatas:TstringList;
   i:integer;
   distri_name,distri_ver,distri_provider:string;
   D:boolean;
begin
  D:=COMMANDLINE_PARAMETERS('--verbose');
  RegExpr:=TRegExpr.Create;
  
   //Suse
   if FileExists('/etc/arch-release') then begin
        result:='ArchLinux';
       exit;
   end;




   if FileExists('/etc/SuSE-release') then begin
       if D then writeln('/etc/SuSE-release found');
       Filedatas:=TstringList.Create;
       Filedatas.LoadFromFile('/etc/SuSE-release');
       result:=trim(Filedatas.Strings[0]);
       if D then writeln(result);
       RegExpr.Expression:='([0-9]+)\.([0-9]+)';
       if RegExpr.Exec(result) then begin
        TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
        TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
       end;
       Filedatas.Free;
       exit;
   end;
  
  
  
  if FileExists('/etc/lsb-release') then begin
      if not FileExists('/etc/redhat-release') then begin
             if D then Writeln('/etc/lsb-release detected (not /etc/redhat-release detected)');
             FileTMP:=TstringList.Create;
             FileTMP.LoadFromFile('/etc/lsb-release');
             for i:=0 to  FileTMP.Count-1 do begin
                 RegExpr.Expression:='DISTRIB_ID=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_provider:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_RELEASE=([0-9\.]+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_ver:=trim(RegExpr.Match[1]);
                 RegExpr.Expression:='DISTRIB_CODENAME=(.+)';
                 if RegExpr.Exec(FileTMP.Strings[i]) then distri_name:=trim(RegExpr.Match[1]);
             end;

             result:=distri_provider + ' ' +  distri_ver + ' ' +  distri_name;
             RegExpr.Free;
             FileTMP.Free;
             exit();
      end;
  end;
  Filedatas:=TstringList.Create;
  if FileExists('/etc/debian_version') then begin
       if D then Writeln('/etc/debian_version detected');
       Filedatas:=TstringList.Create;
       Filedatas.LoadFromFile('/etc/debian_version');
       RegExpr.Expression:='([0-9\.]+)';
       if RegExpr.Exec(Filedatas.Strings[0]) then begin
          result:='Debian ' + RegExpr.Match[1] +' Gnu-linux';
          RegExpr.Free;
          Filedatas.Free;
          exit();
       end;
       RegExpr.Expression:='squeeze/sid';
       if RegExpr.Exec(Filedatas.Strings[0]) then begin
          result:='Debian 6.0 Gnu-linux';
          RegExpr.Free;
          Filedatas.Free;
          exit();
       end;

  end;
  //Fedora
  if FileExists('/etc/redhat-release') then begin
     Filedatas:=TstringList.Create;
     Filedatas.LoadFromFile('/etc/redhat-release');
     if D then Writeln('/etc/redhat-release detected -> ' + Filedatas.Strings[0]);

     RegExpr.Expression:='Fedora Core release\s+([0-9]+)';
     if RegExpr.Exec(Filedatas.Strings[0]) then begin
          result:='Fedora Core release ' + RegExpr.Match[1];
          RegExpr.Free;
          Filedatas.Free;
          exit();
       end;
      RegExpr.Expression:='Fedora release\s+([0-9]+)';
      if RegExpr.Exec(Filedatas.Strings[0]) then begin
         result:='Fedora release ' + RegExpr.Match[1];
         RegExpr.Free;
         Filedatas.Free;
         exit();
      end;

      RegExpr.Expression:='Scientific Linux release\s+([0-9\.]+)';
      if RegExpr.Exec(Filedatas.Strings[0]) then begin
         result:='Scientific Linux release ' + RegExpr.Match[1];
         RegExpr.Expression:='release\s+([0-9]+)\.([0-9]+)';
          if RegExpr.Exec(Filedatas.Strings[0]) then begin
              TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
              TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
         end;
         RegExpr.Free;
         Filedatas.Free;
         exit();
      end;

      //Mandriva
      RegExpr.Expression:='Mandriva Linux release\s+([0-9\.]+)';
      if RegExpr.Exec(Filedatas.Strings[0]) then begin
         result:='Mandriva Linux release ' + RegExpr.Match[1];
         RegExpr.Expression:='release\s+([0-9]+)\.([0-9]+)';
          if RegExpr.Exec(Filedatas.Strings[0]) then begin
              TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
              TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
         end;
         RegExpr.Free;
         Filedatas.Free;
         exit();
      end;
      //CentOS
      RegExpr.Expression:='CentOS release\s+([0-9]+)';
      if RegExpr.Exec(Filedatas.Strings[0]) then begin
         result:='CentOS release ' + RegExpr.Match[1];
         RegExpr.Expression:='release\s+([0-9]+)\.([0-9]+)';
         if RegExpr.Exec(Filedatas.Strings[0]) then begin
              TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
              TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
         end;
         RegExpr.Free;
         Filedatas.Free;
         exit();
      end;
      RegExpr.Expression:='CentOS Linux release\s+([0-9]+)';
       if RegExpr.Exec(Filedatas.Strings[0]) then begin
         result:='CentOS release ' + RegExpr.Match[1];
         RegExpr.Expression:='release\s+([0-9]+)\.([0-9]+)';
         if RegExpr.Exec(Filedatas.Strings[0]) then begin
              TryStrtoInt(RegExpr.Match[1],DISTRI_MAJOR);
              TryStrtoInt(RegExpr.Match[2],DISTRI_MINOR);
         end;
         RegExpr.Free;
         Filedatas.Free;
         exit();
      end;


    end;





end;
//##############################################################################
function tdistriDetect.COMMANDLINE_PARAMETERS(FoundWhatPattern:string):boolean;
var
   i:integer;
   s:string;
   RegExpr:TRegExpr;

begin
 s:='';
 result:=false;
 if ParamCount>0 then begin
     for i:=1 to ParamCount do begin
        s:=s  + ' ' +ParamStr(i);
     end;
 end;
   RegExpr:=TRegExpr.Create;
   RegExpr.Expression:=FoundWhatPattern;
   if RegExpr.Exec(s) then begin
      RegExpr.Free;
      result:=True;
   end;


end;
//##############################################################################
procedure tdistriDetect.removePackage(packagename:string);
   begin
    if DISTRINAME_CODE='CENTOS' then begin
           fpsystem('/usr/sbin/yum -y remove '+ packagename);
           exit;
    end;

    if DISTRINAME_CODE='MANDRAKE' then begin
           fpsystem('/usr/sbin/urpme '+ packagename);
           exit;
    end;

    if DISTRINAME_CODE='UBUNTU' then begin
           fpsystem('/usr/bin/apt-get remove '+packagename+' -y');
           exit;
    end;

    if DISTRINAME_CODE='DEBIAN' then begin
           fpsystem('/usr/bin/apt-get remove '+packagename+' -y');
           exit;
    end;

    if DISTRINAME_CODE='FEDORA' then begin
           fpsystem('/usr/sbin/yum -y remove '+ packagename);
           exit;
    end;

end;
//##############################################################################
function tdistriDetect.FILE_TEMP():string;
var
   stmp:string;
   mypid:string;
   SysTmpDir:string;
begin
stmp:=MD5FromString(FormatDateTime('yyyy-mm-dd hh:nn:ss', Now));
SysTmpDir:=trim(GET_INFO('SysTmpDir'));
if length(SysTmpDir)=0 then SysTmpDir:='/home/artica/tmp';
if not DirectoryExists(SysTmpDir) then forceDirectories(SysTmpDir);
result:=GetTempFileName(SysTmpDir,ExtractFileName(ParamStr(0))+'-'+stmp+'-');

end;

function tdistriDetect.MD5FromString(value:string):string;
var
Digest:TMD5Digest;
begin
Digest:=MD5String(value);
exit(MD5Print(Digest));
end;
//##############################################################################
function tdistriDetect.GET_INFO(key:string):string;
var
   str:string;
begin



str:='';
   if FileExists('/etc/artica-postfix/settings/Daemons/'+key) then begin
      str:=trim(ReadFileIntoString('/etc/artica-postfix/settings/Daemons/'+key));
      result:=str;
   end;

end;
//#########################################################################################

function tdistriDetect.ArchStruct():integer;
var
   tmpstr,data:string;
   RegExpr:TRegExpr;
begin
tmpstr:=FILE_TEMP();
fpsystem('uname -m >'+tmpstr +' 2>&1');
data:=trim(ReadFromFile(tmpstr));
fpsystem('/bin/rm '+tmpstr+' >/dev/null 2>&1');
RegExpr:=TRegExpr.Create;
RegExpr.Expression:='i[0-9]86';
if RegExpr.Exec(data) then begin
   RegExpr.free;
   result:=32;
   exit;
end;
 RegExpr.Expression:='686-bigmem';
if RegExpr.Exec(data) then begin
   RegExpr.free;
   result:=32;
   exit;
end;


RegExpr.Expression:='x86_64';
if RegExpr.Exec(data) then begin
   RegExpr.free;
   result:=64;
   exit;
end;
end;
//##############################################################################
function tdistriDetect.ReadFileIntoString(path:string):string;
         const
            CR = #$0d;
            LF = #$0a;
            CRLF = CR + LF;
var
   Afile:text;
   datas:string;
   datas_file:string;
begin
       datas_file:='';
       if not FileExists(path) then exit;


      TRY
     assign(Afile,path);
     reset(Afile);
     while not EOF(Afile) do
           begin
           readln(Afile,datas);
           datas_file:=datas_file + datas +CRLF;
           end;

close(Afile);
             EXCEPT

           end;
result:=datas_file;


end;
//#####################################################################################
function tdistriDetect.ReadFromFile(TargetPath:string):string;
var
  fs: TFileStream;
  teststr: string;
  a: char;
  i: integer;
begin
  if not FileExists(TargetPath) then exit;
  a:=Char('');
  teststr := '';

  if not FileExists(TargetPath) then exit;
  fs := TFileStream.Create(TargetPath, fmOpenRead);
  try
    fs.Position := 0;
    for i := 1 to fs.size do begin
      fs.Read(a, sizeof(char));
      teststr := teststr + a;
    end;
  finally
    fs.free;
  end;

  result:=teststr;
end;
//#############################################################################
end.
