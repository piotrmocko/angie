:: This script will copy and symlink ANGIE git folders to ANGIE dev site
:: Usage: link_angie_site.bat path/to/angie/dev/site

@echo off
setlocal enableDelayedExpansion

SET SITE=%~1

IF "%SITE%" == "" GOTO MISSING_ARG
IF NOT EXIST "%SITE%" GOTO MISSING_SITE

SET SOURCE_DIR=%cd%/angie/installation

SET "DIRS=angie framework platform template tmp"
SET "FILES=defines.php index.php version.php"

FOR %%a IN (%DIRS%) DO (
    SET TARGET_DIR=%SITE%/installation/%%a

    IF EXIST "!TARGET_DIR!" (
        echo Removing symlink
        rmdir /S /Q "!TARGET_DIR!"
    )

    MKLINK /D "!TARGET_DIR!" "%SOURCE_DIR%/%%a"
)

COPY /Y "%SOURCE_DIR%" "%SITE%/installation"


GOTO DONE

:MISSING_ARG
echo Missing platform name to link
GOTO BLANK

:MISSING_SITE
echo Target site does not exist
GOTO BLANK

:DONE
echo Symlink created

:BLANK
:: Do nothing

::
::# With files the problem is different, since there are a lot of __DIR__ inside it and they play bad with links
::# However, since they rarely change, I can simply copy them in the installation folder
::for FILE in "${FILES[@]}"
::do
::	echo "Copying \"${FILE}\"..."
::	cp ${SOURCE_DIR}/${FILE} ${1}/installation/${FILE}
::done::