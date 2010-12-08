<?php
/**
 * @package kata
 */




/**
 * the default html-helper, available in each view.
 * modify this helper, or build your own one.
 * @package kata_helper
 * @author mnt@codeninja.de
 */
class HtmlHelper extends Helper {

	/**
	 * build a href link
	 * 
	 * @param string $title what the link should say
	 * @param string url where the link should point (is automatically expanded if relative link)
	 * @param array $htmlAttributes array of attributes of the link, for example "class"=>"dontunderline"
	 * @param string an optional message that asks if you are really shure if you click on the link and aborts navigation if user clicks cancel, ignored if false
	 * @param boolean $escapeTitle if we should run htmlspecialchars over the links title
	 */
	function link($title, $url, $htmlAttributes = array (), $confirmMessage = false, $escapeTitle = true) {
		if ($escapeTitle) {
			$title = htmlspecialchars($title, ENT_QUOTES);
		}

		if ($confirmMessage) {
			$confirmMessage = $this->escape($confirmMessage, true);
			$htmlAttributes['onclick'] = 'return confirm(\'' . $confirmMessage . '\');';
		}

		if (((strpos($url, '://')) || (strpos($url, 'javascript:') === 0) || (strpos($url, 'mailto:') === 0) || substr($url, 0, 1) == '#')) {
			return sprintf($this->tags['link'], $url, $this->parseAttributes($htmlAttributes), $title);
		} else {
			return sprintf($this->tags['link'], $this->url($url, true), $this->parseAttributes($htmlAttributes), $title);
		}
	}

	/**
	 * builds a complete link to an image. if relative it is assumed the image can be found in "webroot/"
	 * 
	 * @param string $url filename of the image (is automatically expanded if relative link)
	 * @param array $htmlAttributes array of attributes of the link, for example "class"=>"dontunderline"
	 */
	function image($url, $htmlAttributes = array ()) {
		return sprintf($this->tags['image'], $this->url($url), $this->parseAttributes($htmlAttributes));
	}

	/**
	 * build select/option tags
	 * 
	 * <samp>
	 * $arr = array('blue'=>'Blue color','red'=>'Red color');
	 * echo $html->selectTag('gameinput',$arr,'red');
	 * </samp>
	 * 
	 * @param string $fieldName name-part of the select-tag
	 * @param array $optionElements array with key/value elements (key=option-tags value-part, name=between option tag)
	 * @param string $selected keyname of the element to be default selected
	 * @param array $selectAttr array of attributes of the select-tag, for example "class"=>"dontunderline"
	 * @param array $optionAttr array of attributes for each option-tag, for example "class"=>"dontunderline"
	 * @param boolean $showEmpty if we should display an empty option as the default selection so the user knows (s)he has to select something
	 */
	function selectTag($fieldName, $optionElements, $selected = null, $selectAttr = array (), $optionAttr = array (), $showEmpty = false) {
		$select = array ();
		if (!is_array($optionElements)) {
			return null;
		}
		if (isset ($selectAttr) && array_key_exists("multiple", $selectAttr)) {
			$select[] = sprintf($this->tags['selectmultiplestart'], $fieldName, $this->parseAttributes($selectAttr));
		} else {
			$select[] = sprintf($this->tags['selectstart'], $fieldName, $this->parseAttributes($selectAttr));
		}
		if ($showEmpty == true) {
			$select[] = sprintf($this->tags['selectempty'], $this->parseAttributes($optionAttr));
		}
		foreach ($optionElements as $name => $title) {

			$optionsHere = $optionAttr;

			if (($selected != null) && ($selected == $name)) {
				$optionsHere['selected'] = 'selected';
			}
			elseif (is_array($selected) && in_array($name, $selected)) {
				$optionsHere['selected'] = 'selected';
			}

			$select[] = sprintf($this->tags['selectoption'], $name, $this->parseAttributes($optionsHere), h($title));

		}

		$select[] = sprintf($this->tags['selectend']);
		return implode("\n", $select);
	}

	/**
	 * escape string stuitable for javascript or php output
	 * 
	 * @param string $s string to escape
	 * @param bool $singleQuotes if single quotes should be escaped (true) or double-quotes (false)
	 */
	function escape($s, $singleQuotes = true) {
		if ($singleQuotes) {
			return str_replace(array (
				"'",
				"\n",
				"\r"
			), array (
				'\\\'',
				"\\n",
				""
			), $s);
		}
		return str_replace(array (
			'"',
			"\n",
			"\r"
		), array (
			'\\"',
			"\\n",
			""
		), $s);
	}


/**
 * generate individual tags if DEBUG>0 OR pack all files into a single one, chache the file,
 * replace with a single tag that points to a url that reads the cached+joined file
 * 
 * @param array $files individual files, relative to webroot
 * @param string $target js/css
 * @param string $tagFormat printf-able string of the individual tag
 * @param bool $cacheAndMinify if we should join+compress+cache given target
 */
	private function joinFiles($files,$target,$tagFormat,$cacheAndMinify) {
		// debugging? just return individual tags
		if ((DEBUG>0) || (!$cacheAndMinify)) {
			$html = '';
			foreach ($files as $file) {
				$x = strpos($file,'?');
				if ($x>0) { $file = substr($file,0,$x); }

				$html .= sprintf($tagFormat,$this->url($target.'/'.$file));
			}
			return $html;
		}

		kataMakeTmpPath('cache');

		// cachefile exists and is young enough?
		$cacheFile = KATATMP.'cache'.DS.$target.'.cache'.(defined('VERSION')?urlencode(VERSION):'');
		if (file_exists($cacheFile) && (time()-filemtime($cacheFile)<HOUR)) {
			return sprintf($tagFormat,$this->url($target.'/cache-'.(defined('VERSION')?VERSION:'')));
		}

		// build cachefile
		$content = '';
		foreach ($files as $file) {
			$x = strpos($file,'?');
			if ($x>0) { $file = substr($file,0,$x); }

			$txt = file_get_contents(WWW_ROOT.$target.DS.$file);
			if (false === $file) {
				throw new Exception("html: cant find $target-file '$file'");
			}
			$content .= $txt."\n\n\n\n\n\n";
		}

		if ('css' == $target) {
			$miniUtil = getUtil('Minify');
			$content = $miniUtil->css($content);
		}
		if ('js' == $target) {
			$miniUtil = getUtil('Minify');
			$content = $miniUtil->js($content);
		}

		file_put_contents($cacheFile,$content);
		return sprintf($tagFormat,$this->url($target.'/cache-'.(defined('VERSION')?VERSION:'')));
	}

/**
 * return javascript-tags for all given files.
 * if DEBUG<=0 all files are joined into a single one and are sent compressed to the browser
 * otherweise individual javascript-src-tags are generated
 * 
 * @param array $files filename of script to include, relative to webroot/js/
 * @return string javascript-tag(s)
 * @param bool $cacheAndMinify if we should join+compress+cache given target
 */
	function javascriptTag($files,$cacheAndMinify=false) {
		if (is_string($files)) { $files = array($files); }
		return $this->joinFiles($files,'js',$this->tags['jsfile'],$cacheAndMinify);
	}

/**
 * return css-tags for all given files.
 * if DEBUG<=0 all files are joined into a single one and are sent compressed to the browser,
 * otherweise individual link-tags are generated
 * 
 * @param array $files filename of css to include, relative to webroot/css/
 * @return string css-tag(s)
 * @param bool $cacheAndMinify if we should join+compress+cache given target
 */
	function cssTag($files,$cacheAndMinify=false) {
		if (is_string($files)) { $files = array($files); }
		return $this->joinFiles($files,'css',$this->tags['cssfile'],$cacheAndMinify);
	}

/**
 * output a url with __token get parameter appended. used for xsrf-detection
 * 
 * @param string $url url to add __token to
 * @return string url with token appended
 */
	function tokenUrl($url) {
		$url = $this->url($url);
		$token = is($this->vars['__token'],'');
		if ('' == $token) {
			return $url;
		}

		$x = strpos($url,'?');
		if (false !== $x) {
			return substr($url,0,$x).'?__token='.$token.'&'.substr($url,$x+1);
		}

		$x = strpos($url,'#');
		if (false !== $x) {
			return substr($url,0,$x).'?__token='.$token.substr($url,$x+1);
		}

		return $url.'?__token='.$token;
	}

/**
 * compress given css-string
 * 
 * @param string $css styles to compress
 * @return string the compressed css
 */
	function compressCss($css) {
		$minifyUtil = getUtil('Minify');
		return $minifyUtil->css($css);
	}

}
