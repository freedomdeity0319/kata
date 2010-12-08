<?php
/**
 * some tag-templates used by helpers
 * @package kata
 */




$tags=array(
	'link' => '<a href="%s" %s>%s</a>',
	'selectstart' => '<select name="%s" %s>',
	'selectmultiplestart' => '<select name="%s[]" %s>',
	'selectempty' => '<option value="" %s></option>',
	'selectoption' => '<option value="%s" %s>%s</option>',
	'selectend' => '</select>',
	'image' => '<img src="%s" %s/>',
	'cssfile' => '<link rel="stylesheet" type="text/css" href="%s" />',
	'jsfile' => '<script type="text/javascript" src="%s"></script>'
	);
