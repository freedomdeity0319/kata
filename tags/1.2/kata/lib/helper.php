<?php

/**
 * Contains the base-class for all helpers
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata_helper
 */

/**
 * helper base-class. helpers are the classes you can access via $this->helpername inside a view
 * @package kata_helper
 */
class Helper {

	/**
	 * name of the action of the current controller the dispatcher called
	 * @var string
	 */
	public $action;

	/**
	 * absolute filesystem path to the webroot folder
	 * @var string
	 */
	public $webroot;

	/**
	 * array which holds all relevant information for the current view:
	 * [isAjax] => false (boolean, tells you if view got called with /ajax/)
	 * [url] => Array (
	 *       [url] => locations
	 *       [foo] => bar (if url read ?foo=bar)
	 * )
	 * [form] => Array (
	 * 	  (all post-variables, automatically dequoted if needed)
	 * )
	 * [controller] => main (name of the controller of this request)
	 * [action] => index (name of the view of this request)
	 * @var array
	 */
	public $params;

	/**
	 * placeholder for the tag-templates inside the config folder
	 * @param array
	 */
	public $tags= null;

	/**
	 * @var string complete url (including http) to the base of our framework
	 */
	public $base= null;

	/**
	 * @var string path to the base of our framework, sans http
	 */
	public $basePath= null;

	/**
	 * @var array view-vars
	 */
	public $vars= null;

	/**
	 * constructor, loads tags-templates from config folder
	 */
	function __construct() {
		$defaultTags= array (
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
		$tags = array();
		if (file_exists(ROOT.'config'.DS.'tags.php')) {
			require ROOT.'config'.DS.'tags.php';
		}
		$this->tags= array_merge($defaultTags, $tags);
	}

	/**
	 * construct an relative url with the base url of the framework
	 * @param string $url url to expand
	 * @return string
	 */
	public function urlRel($url= null) {
		if (empty ($url)) {
			return $this->basePath;
		}
		if ($url[0] == '/') {
			return $this->basePath.substr($url, 1);
		}
		if ((strlen($url) > 4) && ($url[4] == ':' || $url[5] == ':')) {
			return $url;
		}

		if (defined('CDN_URL') && (DEBUG < 1)) {
			$ext= strtolower(substr($url, -4, 4));
			if (($ext == '.jpg') || ($ext == '.gif') || ($ext == '.png')) {
				return sprintf(CDN_URL, ord($url[0]) % 4).$url;
			}
		}

		return $this->basePath.$url;
	}

	/**
	 * shortcut. this is what your are normally using everywhere inside a view
	 * @param string $url url to expand
	 * @return string
	 */
	public function url($url= null) {
		return $this->urlAbs($url);
	}

	/**
	 * construct an absolute url (including http(s)) with the base url of the framework. normally needed if you send a view via email and you need the http-part
	 * @param string $url url to expand
	 * @return string
	 */
	public function urlAbs($url= null) {
		if (empty ($url)) {
			return $this->base;
		}
		if ($url {
			0 }
		== '/') {
			return $this->base.substr($url, 1);
		}
		if ((strlen($url) > 5) && ($url[4] == ':' || $url[5] == ':')) {
			return $url;
		}
		return $this->base.$url;
	}

	/**
	 * build an attribute-string of an html-tag out of an array
	 * @param array $options the name=>value pairs to append to the tag
	 * @param mixed $exlude null or array of attribute-names not to append (eg. when they are framework-parameters, not html-attributes)
	 * @param string $insertBefore string to prepand
	 * @param mixed $insertAfter string to append, or null if you want nothing appended
	 * @return string attributes as html
	 */
	public function parseAttributes($options, $exclude= null, $insertBefore= ' ', $insertAfter= null) {
		if (is_array($options)) {
			$default= array (
				'escape' => true
			);
			$options= am($default, $options);
			if (!is_array($exclude)) {
				$exclude= array ();
			}
			$exclude= am($exclude, array (
				'escape'
			));
			$keys= array_diff(array_keys($options), $exclude);
			$values= array_intersect_key(array_values($options), $keys);
			$escape= $options['escape'];
			$attributes= array ();
			foreach ($keys as $index => $key) {
				$attributes[]= $this->formatAttribute($key, $values[$index], $escape);
			}
			$out= implode(' ', $attributes);
		} else {
			$out= $options;
		}
		return $out ? $insertBefore.$out.$insertAfter : '';
	}

	/**
	 * turn a single html-attribute into html
	 * @param string $key attribute-name
	 * @param string $value attribute-value
	 * @param boolean $escape if you want the attribute-value to be escaped (htmlentities)
	 * @return string attribute as html
	 */
	private function formatAttribute($key, $value, $escape= true) {
		$attribute= '';
		$attributeFormat= '%s="%s"';
		$minimizedAttributes= array (
			'compact',
			'checked',
			'declare',
			'readonly',
			'disabled',
			'selected',
			'defer',
			'ismap',
			'nohref',
			'noshade',
			'nowrap',
			'multiple',
			'noresize'
		);

		if (in_array($key, $minimizedAttributes)) {
			if ($value === 1 || $value === true || $value === 'true' || $value == $key) {
				$attribute= sprintf($attributeFormat, $key, $key);
			}
		} else {
			if (empty ($escape)) {
				$bla= h($value);
			} else {
				$bla= $value;
			}
			$attribute= sprintf($attributeFormat, $key, $bla);
		}
		return $attribute;
	}

	/**
	 * @deprecated 1.1 - 09.11.2008 not needed anymore. use url() instead
	 * @return string
	 */
	public function urlWebroot($url) {
		return $this->url($url);
	}
}
