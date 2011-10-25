@echo off

if "%1" == "" goto raushier

echo remove old version
svn del https://sub.gameforge.de/svn/kata/tags/%1 -m "delete old version"

echo copying
svn mkdir https://sub.gameforge.de/svn/kata/tags/%1 -m "creating %1"
svn cp https://sub.gameforge.de/svn/kata/trunk/kataDoku https://sub.gameforge.de/svn/kata/tags/%1/kataDoku -m "adding doku %1"
svn cp https://sub.gameforge.de/svn/kata/trunk/kataTest https://sub.gameforge.de/svn/kata/tags/%1/kataTest -m "adding test %1"
svn cp https://sub.gameforge.de/svn/kata/trunk/kata https://sub.gameforge.de/svn/kata/tags/%1/kata -m "adding kata %1"

echo done: %1
goto raushier2

:raushier
echo Usage: %0 tagno
echo        %0 2.9.3

:raushier2
