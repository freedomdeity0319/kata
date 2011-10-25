@echo off

REM copy D:\projects\kata\trunk\mnt\mnt.ini D:\projects\kata\trunk\mnt\PhpDocumentor\phpDocumentor.ini

cd D:\projects\kata\trunk\mnt\PhpDocumentor\

phpdoc.bat -f D:\projects\kata\trunk\kata\lib\*,D:\projects\kata\trunk\kataTest\* -t D:\projects\kata\trunk\kataDoku\phpdoc\ -o HTML:frames:earthli -c default -i *tags.php

REM phpdoc.bat -f D:\projects\kata\trunk\kata\lib\*,D:\projects\kata\trunk\kataTest\* -t D:\projects\kata\trunk\kataDoku\ -o HTML:frames:

cd ..

