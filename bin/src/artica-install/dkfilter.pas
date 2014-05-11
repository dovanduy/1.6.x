unit dkfilter;

{$MODE DELPHI}
{$LONGSTRINGS ON}

interface

uses
    Classes, SysUtils,variants,strutils,Process,logs,unix,RegExpr in 'RegExpr.pas',zsystem,IniFiles;



  type
  tdkfilter=class


private

public
    procedure   Free;
    constructor Create(const zSYS:Tsystem);

END;

implementation

constructor tdkfilter.Create(const zSYS:Tsystem);
begin

end;
//##############################################################################
procedure tdkfilter.free();
begin

end;
//##############################################################################
end.
