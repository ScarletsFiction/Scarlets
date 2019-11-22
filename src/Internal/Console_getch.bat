@Rem Windows console getch for Scarlets Framework
@Rem https://github.com/ScarletsFiction/Scarlets
@Rem Keep this header on this script

@Echo off
SetLocal EnableDelayedExpansion

:loop
Set "key="
For /F "usebackq delims=" %%L in (`xcopy /L /w "%~f0" "%~f0" 2^>NUL`) Do (
  If not Defined key Set "key=%%L"
)
Set "myKey=!key:~-1!"
Echo %myKey%z

call :loop