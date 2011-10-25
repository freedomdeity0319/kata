@echo off

REM set MYSQL_HOST=localhost
REM set MYSQL_USER=root
REM set MYSQL_PASS=stuhl
REM set MYSQL_DB=opengc_martin_dev
REM set BASEURL=http://localhost/trunk/

REM a nice debugging proxy: fiddler
REM set DEBUGPROXY=localhost:8888

del logs\* /F /Q
php.exe phpunit.php %1 %2 %3 %4 %5 %6 %7 %8
