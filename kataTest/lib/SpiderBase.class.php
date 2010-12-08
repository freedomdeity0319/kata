<?php
/**
 * baseclass to visit all pages of a project and inject bad stuff into get/post
 * 
 * @package katatest_lib
 */

/**
 * visits all pages it can find and reports kata/php-errors or missing translations
 *
 * @package katatest_lib
 * @author mnt@codeninja.de
 */

class SpiderBaseTest extends kataTestBaseClass {

	function __construct() {
		parent :: __construct();
	}

/**
 * if spidertest should be evil (inject bad stuff) or simply spider all pages
 * @var bool be evil
 */
	private $_evil= true;

/**
 * setter for $this->_evil
 * @param bool $b be evil or not
 */
	function beEvil($b) {
		$this->_evil= $b;
	}

	//////////////////////////////////////////////////////////////////////////////////////////

/**
 * if you return false the given url won't be visited
 * @param string $url url to visit
 * @param bool should we visit?
 */
	function urlCallback($url) {
		return true;
	}

/**
 * wether or not we should try to inject bad stuff at this url
 * @param string $url url to manipulate
 * @return bool should we manipulate?
 */
	function manipulateUrlCallback($url) {
		switch (strtolower(substr($url, -4, 4))) {
			case '.jpg' :
			case '.gif' :
			case '.png' :
			case '.css' :
			case '.txt' :
			case '.ico' :
			case '.xml' :
			case '.htc' :
				return false;
				break;
		}
		switch (strtolower(substr($url, -3, 3))) {
			case '.js' :
				return false;
				break;
		}
		return true;
	}

/**
 * this routine fiddles with get/post params
 * @param string $url
 * @param string $name name of the get/post-field, is always false for kata-params (/p1/p2/p3)
 * @param string $value value by reference, feel free to modify the string
 * @return bool if the value has been manipulated
 */
	function valueCallback($url, $name, & $value) {
		return false;
	}

/**
 * the hard working main part. not recursive because phps memory hunger will blow
 * us out of the water.
 *
 * @param string $url url to start spidering
 * @param string $callee url the test started with
 */
	function visit($url, $callee) {
		$_visited= array (); // pages we already visited
		$_visit= array (); //pages we have to visit
		$_ext= array (); // external links

		$_visit[]= array (
			'url' => $url,
			'callee' => $callee,
			'postvars' => false, //this link has post-forms


		);

		while (true) {
			$temp= array_pop($_visit);
			if (empty ($temp)) {
				break;
			}

			if (strtolower(substr($temp['url'], 0, 11)) == 'javascript:') {
				continue;
			}

			$url= $temp['url'];
			$callee= $temp['callee'];
			$postvars= $temp['postvars'];

			$redirlimit= 0;
			while (!empty ($url)) {
				$completeUrl= $url;
				if (strpos($url, '#') > 0) {
					$url= substr($url, 0, strpos($url, '#'));
				}

				// already visited this session?
				if (in_array($completeUrl, $_visited)) {
					break;
				}

				if (!$this->urlCallback($url)) {
					break;
				}

				$this->log("==== $url");
				$_visited[]= $completeUrl;

				if (is_array($postvars) && (count($postvars) > 0)) {
					$this->log('POST '.print_r($postvars, true));
					try {
						$this->post($url, $postvars);
					} catch (Exception $ex) {
						$this->dumpWebsiteHtml('failed');
						throw new Exception($ex->getMessage()." for url [".$url."] (link found on url [".$callee."])");
					}
				} else {
					try {
						$this->get($url);
					} catch (Exception $ex) {
						$this->dumpWebsiteHtml('failed');
						throw new Exception($ex->getMessage()." for url [".$url."] (link found on url [".$callee."])");
					}
				} //postvars

				if (!$this->isHtml()) {
					break;
				}

				// add all links in this page
				$links= $this->_html->getLinks();
				if (count($links) > 0) {
					foreach ($links as $link) {
						if (!in_array($link['href'], $_visited)) {
							$_visit[]= array (
								'url' => $link['href'],
								'callee' => $url,
								'postvars' => false
							);
							$this->log("adding link ".$link['href']);
						}
					}
				}

				//add post-forms as-is and visit next time
				$forms= $this->_html->getForms();
				if (count($forms) > 0) {
					foreach ($forms as $action => $vars) {
						$_visit[]= array (
							'url' => $link['href'],
							'callee' => $url,
							'postvars' => $vars
						);
						$this->log("adding form $action");
					}
				}

				foreach ($this->_html->getExternalLinks() as $link => $xxx) {
					$_ext[$link]= true;
				}

				////////////////////////////////////////////////////////////////////////////////
				if ($this->_evil) {

					// manipulate each parameter of each url, one by one
					if (count($links) > 0) {
						foreach ($links as $link) {
							if (in_array($link['href'], $_visited)) {
								continue;
							}
							if (!$this->manipulateUrlCallback($link['href'])) {
								continue;
							}

							$valuecnt= count(explode('/', $link['href']));
							if ($valuecnt <= 2) {
								continue;
							}

							for ($i= 2; $i < $valuecnt; $i++) {
								$temp= explode('/', $link['href']);
								if ($this->valueCallback($link['href'], false, $temp[$i])) {
									$newlink= implode('/', $temp);
									$_visit[]= array (
										'url' => $newlink,
										'callee' => $url,
										'postvars' => false
									);
									$this->log("adding manipulated link ".$newlink);
								}
							}
						} //foreach $link
					} //links

					//add POST-request if initially GET-request
					if (count($forms) == 0) {
						if ($this->manipulateUrlCallback($link['href'])) {
							$_visit[]= array (
								'url' => $url,
								'callee' => $url,
								'postvars' => array ()
							);
							$this->log("adding empty form ".$url);
						}
					}

					//add post-forms, inject bad stuff and visit next time
					if (count($forms) > 0) {
						foreach ($forms as $action => $vars) {
							$varsCnt= count($vars);
							if (0 == $varsCnt) {
								continue;
							}

							for ($i= 0; $i < $varsCnt; $i++) {
								$varCopy= $vars;
								$curr= -1;
								$didChange= false;
								foreach ($varCopy as $name => & $value) {
									$curr++;
									if ($curr == $i) {
										if ($this->valueCallback($action, $name, $value)) {
											$varCopy[$name]= $value;
											$didChange= true;
											break;
										}
									}
								}
								unset ($value);
								if ($didChange) {
									$_visit[]= array (
										'url' => $action.'#xss'.$i,
										'callee' => $url,
										'postvars' => $varCopy
									);
									$this->log("adding manipulated form $action = ".
									print_r($varCopy, true));
								}
							} //for i
						} //foreach forms
					} //count forms
				} //evil
				////////////////////////////////////////////////////////////////////////////////
				// 200? bail.
				if ($this->_http->getCode() == 200) {
					break;
				} // not a readirect? bail.
				if (($this->_http->getCode() != 301) && ($this->_http->getCode() != 302)) {
					break;
				} // check if we get loop-redirected
				$redirlimit= $redirlimit +1;
				if ($redirlimit > 16) {
					throw new exception("infinite redirection for url [$url] (found in [$callee])");
				} // get+check new location we were redirected to
				$h= $this->_http->getHeaders();
				if (substr($h['location'], 0, strlen(BASEURL)) != BASEURL) {
					throw new exception("Redirect outside webapp url=[".$h['Location']."] (found in [$callee])");
				}
				$redirurl= substr($h['location'], strlen(BASEURL));
				if (empty ($redirurl)) {
					throw new exception("Redirect 301/302, but no Location: set for url [$url] (found in [$callee])");
				}

				$this->log("Redirect: $url to $redirurl");
				$url= $redirurl;
			} //while true / redirect
		} //while true
		foreach ($_ext as $link => $xxx) {
			$this->log("external: $link");
		}
	} //visit
}
?>