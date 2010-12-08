<?php
/**
 * small stackmachine to extract links,forms,images from html.
 * uses HTML_PHP_Parser http://starnetsys.com
 *
 * @package katatest_lib
 * @author mnt@codeninja.de
 */


/**
 * include html-parser
 */
require ("HTMLParser/htmlparser.inc");


/**
 * html parser with lots of convenience functions so we dont have to deal with html by hand
 * @author mnt@codeninja.de
 * @package katatest_lib
 */
class FormParser {

	const STATE_NORMAL= 0;
	const STATE_INLINK= 1;
	const STATE_INFORM= 2;
	const STATE_IMAGE= 3;

	private $_forms;
	private $_links;
	private $_extlinks;
	private $_images;
	private $_cached= false;
	private $_html;
	private $_baseurl;
	private $_basehost;

	/**
	 * all links found in current page
	 * @return array returns array of array (a=>link title,href=>url)
	 */
	public function getLinks() {
		if (!$this->_cached) {
			$this->parseHelper();
		}
		return $this->_links;
	}

	public function getExternalLinks() {
		if (!$this->_cached) {
			$this->parseHelper();
		}
		return $this->_extlinks;
	}

	/**
	 * return all forms and variables in current page
	 * @return array array-of-array (form-action=>array(name=>value))
	 */
	public function getForms() {
		if (!$this->_cached) {
			$this->parseHelper();
		}
		return $this->_forms;
	}

	/**
	 * @return array returns array ([]=url)
	 */
	public function getImages() {
		if (!$this->_cached) {
			$this->parseHelper();
		}
		return $this->_images;
	}

	/**
	 * set base URL so we can translate all found links/forms accordingly
	 * @param string baseurl
	 */
	public function setBaseURL($url) {
		if (strpos($url, '://') !== false) {
			// fixme: parse_url assumes wrong path component on most cake-urls
			// ( /cake/main -> cake/main instead of cake )
			$temp= parse_url($url);
			$this->_baseurl= $temp['path'];
		} else {
			$this->_baseurl= $url;
		}

		if (substr($this->_baseurl, -1, 1) != '/') {
			$this->_baseurl= $this->_baseurl.'/';
		}

		$this->_basehost= $temp['scheme'].'://'.$temp['host'].$this->_baseurl;
	}

	private function normalizeLink($url) {
		if (substr($url, 0, 11) == 'javascript:') {
			return '';
		}
		if (substr($url, 0, 1) == '#') {
			return '';
		}
		if (substr($url, 0, 7) == 'mailto:') {
			return '';
		}

		if (substr($url, -1, 1) == '/') {
			$url= substr($url, 0, strlen($url) - 1);
		}

		//echo "\n".$this->_basehost."\n".$this->_baseurl."\n".$url."\n";
		if (substr($url, 0, strlen($this->_basehost)) == $this->_basehost) {
			return substr($url, strlen($this->_basehost));
		}
		if (substr($url, 0, strlen($this->_baseurl)) == $this->_baseurl) {
			return substr($url, strlen($this->_baseurl));
		}

		$this->_extlinks[$url]= true;
		return '';
	}

	/**
	 * parse given html and extract forms+links
	 * @param string bodyhtml
	 */
	public function parse($html) {
		$this->_html= $html;
		$this->_cached= false;
	}

	private $_parserState;
	private $_stateStack;

	private function stateLeave() {
		//echo "stateLeave() old: ".$this->_parserState;
		if (count($this->_stateStack)==0) {
			throw new Exception('stateStack empty. normally a sign of malformed html');
		}
		$this->_parserState = array_pop($this->_stateStack);
		//echo " new: ".$this->_parserState."\n";
	}

	private function stateEnter($newState) {
		//echo "stateEnter() old: ".$this->_parserState;
		array_push($this->_stateStack, $this->_parserState);
		$this->_parserState= $newState;
		//echo " new: ".$this->_parserState."\n";
	}

	private function parseHelper() {
		$this->_parserState= self :: STATE_NORMAL;
		$this->_stateStack= array ();

		$_linkHrefPuffer= '';
		$_linkTextPuffer= '';
		$_formActionPuffer= '';

		unset ($this->_forms);
		$this->_forms= array ();
		unset ($this->_links);
		$this->_links= array ();
		unset ($this->_images);
		$this->_images= array ();
		unset ($this->_extlinks);
		$this->_extlinks= array ();

		$parser= new HtmlParser($this->_html);
		while ($parser->parse()) {

			//echo $this->_parserState." <".($parser->iNodeType==NODE_TYPE_ENDELEMENT?'/':'').$parser->iNodeName."> ".print_R($parser->iNodeAttributes, true)."\n";

			switch ($this->_parserState) {

				case self :: STATE_INFORM :
					if ($parser->iNodeType == NODE_TYPE_ENDELEMENT) {
						if (strcasecmp($parser->iNodeName, 'form') == 0) {
							$this->stateLeave();
						}
					}

					if ($parser->iNodeType == NODE_TYPE_ELEMENT) {
						if (strcasecmp($parser->iNodeName, 'input') == 0) {
							$type= strtolower($parser->iNodeAttributes['type']);
							if (($type != 'submit') and ($type != 'file')) {
								if (isset ($parser->iNodeAttributes['name'])) {
									$this->_forms[$_formActionPuffer][$parser->iNodeAttributes['name']]= (isset ($parser->iNodeAttributes['value']) ? $parser->iNodeAttributes['value'] : '');
								}
							}
						}
						if (strcasecmp($parser->iNodeName, 'textarea') == 0) {
							if (isset ($parser->iNodeAttributes['name'])) {
								$this->_forms[$_formActionPuffer][$parser->iNodeAttributes['name']]= '';
							}
						}
					}

					break;

				case self :: STATE_INLINK :
					if ($parser->iNodeType == NODE_TYPE_ENDELEMENT) {
						if (strcasecmp($parser->iNodeName, 'a') == 0) {
							$this->_links[]= array (
								'a' => trim($_linkTextPuffer),
								'href' => $_linkHrefPuffer,

							);
							$this->stateLeave();
						}
					}

					if ($parser->iNodeType == NODE_TYPE_TEXT) {
						$_linkTextPuffer .= $parser->iNodeValue." ";
					}

					break;

			} //switch

			if ($parser->iNodeType == NODE_TYPE_ELEMENT) {

				if ((strcasecmp($parser->iNodeName, 'a') == 0) && (isset ($parser->iNodeAttributes['href']))) {
					$_linkHrefPuffer= $this->normalizeLink($parser->iNodeAttributes['href']);
					if (!empty ($_linkHrefPuffer)) {
						$_linkTextPuffer= '';
						$this->stateEnter(self :: STATE_INLINK);
					}
				}

				if (strcasecmp($parser->iNodeName, 'form') == 0) {
					$_formActionPuffer= $this->normalizeLink(isset ($parser->iNodeAttributes['action']) ? $parser->iNodeAttributes['action'] : '-');
					$this->_forms[$_formActionPuffer]= array ();
					$this->stateEnter(self :: STATE_INFORM);
				}

				if (strcasecmp($parser->iNodeName, 'img') == 0) {
					$this->_images[]= $this->normalizeLink(isset ($parser->iNodeAttributes['src']) ? $parser->iNodeAttributes['src'] : '-');
				}
			} //element

		} //while

		$this->_cached= true;
	} //func

} //class
