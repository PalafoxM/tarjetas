@echo off
setlocal

set "PROJECT_DIR=C:\wamp64\www\tarjetas"
set "PHP_BIN=C:\wamp64\bin\php\php8.2.29\php.exe"
set "LOG_DIR=%PROJECT_DIR%\writable\logs"
set "LOG_FILE=%LOG_DIR%\depositos_programados.log"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

pushd "%PROJECT_DIR%"
"%PHP_BIN%" spark depositos:programar >> "%LOG_FILE%" 2>&1
set "EXIT_CODE=%ERRORLEVEL%"
popd

exit /b %EXIT_CODE%
