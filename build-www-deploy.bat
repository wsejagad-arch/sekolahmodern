@echo off
echo Building SIMANIS deployment package for www.simanis.sman1sumber.sch.id...

set STAGE_DIR=release\_stage\simanis-www-deploy
set ZIP_FILE=release\simanis-www-deploy.zip

echo Removing existing staging directory...
if exist "%STAGE_DIR%" rmdir /s /q "%STAGE_DIR%"

echo Creating staging directory...
mkdir "%STAGE_DIR%"

echo Copying application files...
xcopy /s /e /i /y "." "%STAGE_DIR%" > nul 2>&1

echo Excluding unnecessary files...
del /q /s "%STAGE_DIR%\node_modules" > nul 2>&1
del /q /s "%STAGE_DIR%\deploy_prod" > nul 2>&1
del /q /s "%STAGE_DIR%\scratch" > nul 2>&1
del /q /s "%STAGE_DIR%\.git" > nul 2>&1
del /q /s "%STAGE_DIR%\release" > nul 2>&1
del /q /s "%STAGE_DIR%\patch_date-to-tanggal" > nul 2>&1
del /q /s "%STAGE_DIR%\home5" > nul 2>&1
del /q /s "%STAGE_DIR%\backup" > nul 2>&1
del /q /s "%STAGE_DIR%\htaccess" > nul 2>&1
del /q /s "%STAGE_DIR%\deploy" > nul 2>&1

echo Removing test and debug files...
del /q "%STAGE_DIR%\test_*.php" > nul 2>&1
del /q "%STAGE_DIR%\debug_*.php" > nul 2>&1
del /q "%STAGE_DIR%\check_*.php" > nul 2>&1

echo Creating upload directories...
mkdir "%STAGE_DIR%\uploads" 2>nul
mkdir "%STAGE_DIR%\uploads\izin" 2>nul
mkdir "%STAGE_DIR%\uploads\tugas" 2>nul
mkdir "%STAGE_DIR%\uploads\twibbon" 2>nul
mkdir "%STAGE_DIR%\uploads\7kih" 2>nul
mkdir "%STAGE_DIR%\uploads\kurikulum-menu-icons" 2>nul

echo Copying deployment configuration...
copy "deploy\config.hosting.simanis.php" "%STAGE_DIR%\config.hosting.php" > nul 2>&1
copy "deploy\BACA-INI-DEPLOY.txt" "%STAGE_DIR%\BACA-INI-DEPLOY.txt" > nul 2>&1
copy "config.hosting.example.php" "%STAGE_DIR%\config.hosting.example.php" > nul 2>&1

echo Copying htaccess files...
xcopy /s /e /i /y "htaccess" "%STAGE_DIR%\htaccess" > nul 2>&1
copy "htaccess\.htaccess-litespeed" "%STAGE_DIR%\.htaccess" > nul 2>&1

echo Copying installation files...
mkdir "%STAGE_DIR%\_instalasi\sql" 2>nul
xcopy /s /e /i /y "sql" "%STAGE_DIR%\_instalasi\sql" > nul 2>&1

echo Copying documentation...
copy "PANDUAN_UPLOAD_HOSTING.md" "%STAGE_DIR%\PANDUAN_UPLOAD_HOSTING.md" > nul 2>&1

echo Creating .gitkeep files in upload directories...
echo. > "%STAGE_DIR%\uploads\.gitkeep"
echo. > "%STAGE_DIR%\uploads\izin\.gitkeep"
echo. > "%STAGE_DIR%\uploads\tugas\.gitkeep"
echo. > "%STAGE_DIR%\uploads\twibbon\.gitkeep"
echo. > "%STAGE_DIR%\uploads\7kih\.gitkeep"
echo. > "%STAGE_DIR%\uploads\kurikulum-menu-icons\.gitkeep"

echo Creating deployment package...
if exist "%ZIP_FILE%" del "%ZIP_FILE%"

cd release
powershell -Command "Compress-Archive -Path '_stage\simanis-www-deploy\*' -DestinationPath 'simanis-www-deploy.zip' -Force"
cd ..

echo.
echo ==============================================
echo DEPLOYMENT PACKAGE READY!
echo ==============================================
echo File: %ZIP_FILE%
echo.
echo Database Configuration:
echo   User: smasumb1_simanis1
echo   Password: W@hyu123!
echo   Database: smasumb1_simanis
echo   Site URL: https://www.simanis.sman1sumber.sch.id
echo.
echo Upload Instructions:
echo 1. Extract simanis-www-deploy.zip to www.simanis.sman1sumber.sch.id root
echo 2. Set PHP version in cPanel to 8.1 or 8.0
echo 3. Check permissions: folder 755, file 644
echo 4. Test: https://www.simanis.sman1sumber.sch.id/ping.php
echo 5. Access: https://www.simanis.sman1sumber.sch.id/splash.php
echo ==============================================
pause