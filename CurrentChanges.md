# Introduction #

What has changed in the trunk, what will be in the next version


# Details #

```
- Fix for apache +=space problem
- MongoModel
- MongoVersionedModel, adds a versioning layer above mongodb
- loadModel no longer does file_exists for speed reasons,
- set iconv_settings to utf-8,
- component now it's own class,
- workaround for http://bugs.php.net/bug.php?id=35050,
- dbo_mysql workaround for connection pooling,
- IE6-workaround for https,
- lockutil does not cleanup after itself (that does service.php) so we dont - kill renewed locks on scheduling conflicts,
- added feedutility,
- added geoiputility (with triple fallback: geoip_country_code_by_name()->/usr/bin/geoiplookup->Net_GeoIP
```