:: This script will copy the platform folder inside angie installation folder

@echo off

set DIR=%cd%

IF "%1" == "" GOTO MISSING_ARG

SET SYMLINK=%DIR%/angie/installation/platform
SET PLATFORM=%DIR%/angie/platforms/%1

IF NOT EXIST "%PLATFORM%" GOTO WRONG_PLATFORM

IF EXIST "%SYMLINK%" (
    echo Removing existing symlink
    rmdir "%SYMLINK%"
)

MKLINK /D "%SYMLINK%" "%PLATFORM%"

GOTO DONE

:MISSING_ARG
echo Missing platform name to link
GOTO BLANK

:WRONG_PLATFORM
echo Platform "%1" not found
GOTO BLANK

:DONE
echo Symlink created

:BLANK
:: Do nothing
