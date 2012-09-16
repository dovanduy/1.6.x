unit cwstring_fix;
interface
implementation
uses BaseUnix;
function getenv(env: pansichar): pansichar; cdecl; external clib name 'getenv';
function setenv(env: pansichar; val: pansichar; oflag: integer):integer; cdecl; external clib name 'setenv';
var
Lang: string;
initialization
Lang := getenv('LANG');
if Lang = '' then
Lang := 'en_US.UTF-8'
else
Lang := Copy(Lang, 1, Pos('.', Lang)) + 'UTF-8';
setenv('LANG', PAnsiChar(Lang), 1);
end.
