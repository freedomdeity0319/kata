#!/bin/sh

set MYSQL_HOST=localhost
set MYSQL_USER=root
set MYSQL_PASS=stuhl
set MYSQL_DB=opengc_martin_dev
set BASEURL=http://localhost/trunk/

#set DEBUGPROXY=localhost:8888

rm logs\* -rf
php phpunit.php %1 %2 %3 %4 %5 %6 %7 %8
