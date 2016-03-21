# 1.4 #

thx for helping @ martin contento, joachim eckert, andreas stahl :)

## Changes ##

htmlhelper:
> tokenUrl now in base-helper class

session:
> auto-renewal
> simplified ip detection
> localhost-fix
> uses katas session directory again, global session directory gets messed
> > up by cronjobs, set SESSION\_SYSPATH if you want previous behaviour.

all:

> removed nearly all file\_exists() in favor of require fatal errors

controller:
> redirect() only adds html when headers already sent. you can change the
> > layout inside beforeFilter()

css/js caching/compression:

> supports multiple calls + RTL

## New ##

service.php:
> clean up sessions/locking/etc, rotate logfiles, update locafiles and more

html->cssFile:
> supports parsing rtl/ltr statements to have a single css-file for
> > both directions

dbo\_mssql:

> exec() support

dbo\_pdo:
> enable access to msql,sqlite and so on

cacheUtil:
> now works with both memcache and memcached-extensions

extCacheUtil:
> adds the following functions to memcache(d):
> > increment()
> > decrement()
> > readCas()
> > getMulti()
> > compareAndSwap()

rsaUtil:

> simplify rsa-crypto, multiple encoding options

lockUtil:
> automatic garbage collection of lockfiles

validateUtil:
> validate for BOOL\_LAZY and BOOL\_TRUE, useful for form-helper

writeLog:
> now accepts any filenames as second parameter

components:
> may depend on other components, will initialize in right order

controller:
> addComponent() addHelper() lazy load

model:
> bulkCreate() getModel()

view:
> render(../foo) does not require initial viewdir to actually exist

helper:
> getHelper() lazy load helpers inside views

imageUtil:
> setQuality()

DatabaseErrorException:
> getQueryString() to display offending query

kataExt:
> dynamically inject methods

ipUtil:
> isMobileDevice()

locale:
> loggt fehlende/leere keys immer in tmp/logs/locale.log

## Deprecated ##

## Fixes ##

dispatcher:
> ignore calls to internal controller-functions like log()

routes:
> query-strings of targets will appear in $this->params

model:
> or-mode no longer uses broken in\_array()



# 1.3 #

## Changes ##

speed improvements

controller:
> controller now dispatches itself for improved exception handling

## New ##

form-helper:
> super-easy 

&lt;form&gt;

management with default values, automatic use of submitted
> > values, error messages, validate against ruleset OR model (read:
> > table-schema) ...see doku.

new debugger support:

> boot\_formaldehyde: also puts error-messages in firebug-console if DEBUG=1
> boot\_xhprof

kataGlob:
> static helper class to use global variables in a OO way

model:
> read() now supports $fieldName to create nested result-arrays
> > like find() or query()

> meaningful returns for most sql-commands
> by popular demand model throws now DatabaseDuplicateException() for
> > uniqe collisions (but use 'ON DULICATE KEY UPDATE' anyway, goddamit)

session:

> total rewrite, lazy load for any type
> you can disable the ip-check (SESSION\_UNSAFE)
> you can use the session for subdomains (SESSION\_BASEDOMAIN)

kataReg:
> delete($id)

validate-utility:
> major overhaul, now uses filter-extension
> can automatically validate against a table of a model

controller:
> addHelper() in case you only need a helper for a single action
> validate() to manually validate forms (in case you dont use form-helper)

Smartyview:
> Use smartytemplates for views (view,layout,element) - alpha

Doku:
> Use kata with lighttpd, thanks martin!

## Deprecated ##

## Fixes ##

locale-component users with APC enabled and laaaarge locale-files had
> a huuuge slowdown

# 1.2 #

## Changes ##

internal:
> built-in GF\_Lib autoloader
> MINUTE,HOUR-defines now available in core.php
> CLI define (1 if kata invoked from command line)
> better css/js caching (see $html->jsTag $html->cssTag)
> better active record functions
> fairly good unittest coverage

controller:
> redirect() with previous output ie6 kludge fix

dispatcher:
> XAMPP-workaround for backslashes in basepath

cacheUtility:
> override auto-selection of caching-method via core.php:CACHE\_USEMETHOD

session:
> lazy start, now uses the systems session-directory instead of katas

## New ##

kataReg - the global configuration registry, see doku

core.php: LANGUAGE\_ESCAPE Locale will do htmlentities with double-encode for
> all strings returnedif true

kataTest: supports phpunit and simpletest, various utility functions
> to test code/webwise

## Deprecated ##

LANGUAGENOTPRINTF -> dropped, now Locale never uses printf


# 1.1 #

## Changes ##

Moved all components,utilities and so on that kata has by default to lib folder.
In the future you only have to update the lib-folder, nothing else.

## New ##

internal:
> gameinstaller-now-broken-tmppath fix, see core.php: //define('TMPPATH')
> STRICT proof
> XP Framework proof
> ZEND proof

clusterlockUtility:
> locking über mehrere server hinweg

CacheUtility:
> - autoselects caching-method: memcached,apc,eaccellerator,xcache,filesystem
> - Now has an internal cache, so queries for the same data are only polled once
> - Now correctly checks if APC is enabled for cgi/cli
> - Can now output debugging data

Session-component:
> - Is able to use sessions via memcache, just set SESSION\_STORAGE to
> > 'memcached'

html-helper:

> - $html->javascripTag(array('foo.js'),true)
> > $html->cssTag(array('foo.css'),true)
> > returns the neccessary html. if DEBUG==0 the js/css will be joined,
> > compressed,minified and cached.

core.php:

> - supports routing: sample:
> > $routes = array('from.php'=>'to/controller','oldcontroller/'=>'newcontroller/');

> - display code-coverage (which lines of code are actually executed):
> > include (LIB.'boot\_coverage.php');

> - display code-profiling (which code-parts are most costly):
> > include (LIB.'boot\_profile.php');

> - display debug-output in firebug console if DEBUG==1:
> > include (LIB.'boot\_firephp.php');

ImageUtility:

> - getNudity() returns 0..100 how much skin it detected (black+white)

HttpUtility: (new)
> - ultra-simple and reliable get/post via Streamwrapper
> > (no more fopen('http://...) or curl)

MinifyUtility: (new)

> - compress css or javascript, also used by html-helper

Model: full active record support, see documentation of each method
> - lazy-loads database.php
> - swap connections at runtime (even between different vendors)
> - query() and active record functions can deep-resort any resultset because
> > $idnames is now an array. sample:
> > `$this->query('SELECT game_id,country,gameblob FROM games',array('game_id','country'))`
> > gives:
```
    Array(
       [1] => array(
           ['de'] => array(
                  'gameblob' => ...
           )
       )
```