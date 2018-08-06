@Rem Windows console handler for Scarlets Framework
@Rem https://github.com/ScarletsFiction/Scarlets
@Rem Keep this header on this script

@Echo off
SetLocal DisableDelayedExpansion
Set "Char="

@Rem 0x08
Set "ENT=%%#"

:loop
	@Rem Reset key
    Set "Key="

    @Rem xcopy allow prompt before copying and displaying the character while 'pause' command is not
    @Rem this can be used to get a single character and continue the code
    For /F "delims=" %ENT% In ('xcopy "%~f0" "%~f0" /L /W  2^> Nul') Do (
        If Not Defined Key Set "Key=%ENT%"
    )

    @Rem Get the last character that was obtained
    Set "Key=%Key:~-1%"
    SetLocal EnableDelayedExpansion

    @Rem If nothing then exit immediately
    If Not Defined Key Goto :end

    @Rem Set /P "=*" <Nul

    @Rem Move string to Char variable
    If Not Defined Char (
        EndLocal
        Set "Char=%Key%"
    ) Else (
        For /F "delims=" %ENT% In ("!Char!") Do (
            EndLocal
            Set "Char=%ENT%%Key%"
        )
    )
    Goto :loop
:end

@Rem Escape and echo Char variable
Echo !Char!