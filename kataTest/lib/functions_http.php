<?php
/**
 * a wrapper around the buggy http://pear.php.net/package/HTTP_Request/
 *
 * @package katatest_lib
 * @author mnt@codeninja.de
 */


/**
 * include PEARs HTTP_Request, see http://pear.php.net
 */
require_once "HTTP/Request2.php";
//define('DEBUG1', 'n/a');

/**
 * extremly old wrapper around HTTP_Request, should probably be retired by extending HttpUtility
 * @author mnt@codeninja.de
 * @package katatest_lib
 */
class HTTPTester {

  private $_HTTPRequest = null;

  private $_URL = '';
  private $_COOKIESOUT = null; //cookies we sould send
  private $_COOKIESIN = null; //cookies we got
  private $_PARAMS_TO_SERVER = null; //parameters to issue on a request
  private $_PARAMS_FROM_SERVER = null; //returned headers by the webserver
  private $_POSTPARAMS = null;
  private $_POSTPARAMSOLD = null;
  private $_RESPONSECODE = null;
  private $_RESPONSEBODY = '';
  private $_USERNAME = ''; //for WWW-Authenticate
  private $_PASSWORD = '';
  private $_REDIRECTS = true;

  /**
   * prepare request by setting up all parameters
   */
  private function _prepare($url) {
    $this->_HTTPRequest = & new HTTP_Request2($url,array(
      'allowRedirects'=>$this->_REDIRECTS,
      'maxRedirects'=>4,
      'timeout'=>10
    ));
    $this->_HTTPRequest->clearPostData();

    $debugproxy = getenv('DEBUGPROXY');
    if (!empty ($debugproxy)) {
      $debugproxy = explode(':', $debugproxy);
      $this->_HTTPRequest->setProxy($debugproxy[0], $debugproxy[1]); // for fiddler debug proxy
    }

    if ($this->_USERNAME != '') {
      $this->_HTTPRequest->setBasicAuth($this->_USERNAME, $this->_PASSWORD);
    }

    $this->_HTTPRequest->setURL($url);
    $this->_URL = $url;

    unset ($this->_PARAMS_FROM_SERVER);
    unset ($this->_COOKIESIN);
    $this->_RESPONSECODE = null;
    $this->_RESPONSEBODY = false;

    if (count($this->_COOKIESOUT) > 0) {
      foreach ($this->_COOKIESOUT as $k => $v) {
        $this->_HTTPRequest->addCookie($k, $v);
        if (defined('DEBUG1')) {
          echo "added cookie $k=$v\n";
        }
      }
    }

    if (count($this->_PARAMS_TO_SERVER) > 0) {
      foreach ($this->_PARAMS_TO_SERVER as $k => $v) {
        $this->_HTTPRequest->addHeader($k, $v);
        if (defined('DEBUG1')) {
          echo "added header $k=$v\n";
        }
      }
    }
    $this->_HTTPRequest->addHeader('User-Agent', 'kataTest');

    if (count($this->_POSTPARAMS) > 0) {
      foreach ($this->_POSTPARAMS as $k => $v) {
        $this->_HTTPRequest->addPostData($k, $v);
        if (defined('DEBUG1')) {
          echo "added post parameter $k=$v\n";
        }
      }
    }
  }

  /**
   * execute request, take care of errors, clean up afterwards
   */
  private function _request($method) {
    if (defined('DEBUG1')) {
      echo "request to " . $this->_URL . " method " . $method . "\n";
    }

    $this->_HTTPRequest->setMethod($method);
    $this->_HTTPRequest->sendRequest();

    if (!PEAR :: isError($this->_HTTPRequest->sendRequest())) {
      // we throw a exception some lines below
    }

    $this->_RESPONSEBODY = $this->_HTTPRequest->getResponseBody();
    $this->_URL = $this->_HTTPRequest->getURL($this->_URL);
    $this->_RESPONSECODE = $this->_HTTPRequest->getResponseCode();
    $this->_PARAMS_FROM_SERVER = $this->_HTTPRequest->getResponseHeader();
    $this->_COOKIESIN = $this->_HTTPRequest->getResponseCookies();

    if ($this->_HTTPRequest->getResponseCode() > 399) {
      $out = '';
      if (count($this->_POSTPARAMS) > 0) {
        $out = "Post-parameters:\n" . print_r($this->_POSTPARAMS,true);
      }

      throw new Exception('Request to "' . $this->_URL . '" failed statuscode "' . $this->_RESPONSECODE . '"' . "\n" . $out);
    }

    $this->_POSTPARAMSOLD = $this->_POSTPARAMS;
    $this->_POSTPARAMS = array ();

    // we need to keep any cookies to simulate browsersessions
    if (is_array($this->_COOKIESIN) and (count($this->_COOKIESIN) > 0)) {
      $temp = array ();
      foreach ($this->_COOKIESIN as $cookie) {
        $temp[$cookie['name']] = $cookie['value'];
        if (defined('DEBUG1')) {
          echo "added cookie from last request " . $cookie['name'] . "=" . $cookie['value'] . "\n";
        }
      }
      $this->_COOKIESOUT = $temp;
      unset ($temp);
    }
  }

  private function _cleanup() {
    unset ($this->_HTTPRequest);
  }

  /////////////////////////////////////////////////////////////////////////////

  /**
   * authentification
   * @param string username
   * @param string password
   */
  public function setAuth($username, $password) {
    $this->_USERNAME = $username;
    $this->_PASSWORD = $password;
  }

  /**
   * needs an array cookiename=value
   * @param array cookies (name=>value)
   */
  public function setCookies($cookies) {
    $this->_COOKIESOUT = array_merge($this->_COOKIESOUT, $cookies);
  }

  /**
   * kill session, end browser-like behaviour
   */
  public function clearSession() {
    $this->_COOKIESOUT = array ();
    $this->_COOKIESIN = array ();
  }

  /**
   * what headers to send to the server on request
   * @param array headers (name=>value)
   */
  public function setHeaders($headers) {
    $this->_PARAMS_TO_SERVER = $headers;
  }

  /**
   * post parameters for POST request
   * @param array post-variables (name=>value)
   */
  public function setPost($params) {
    $this->_POSTPARAMS = $params;
  }

  /**
   * cookies got from server, as an array of array with cookie-properties
   * @return array returns an array of cookie-information-tokens
   */
  public function getCookies() {
    return $this->_COOKIESIN;
  }

  /**
   * return a single cookie by name
   * @param string $name name of cookie
   * @return mixed false if cookie not found, array() if cookie found
   */
  public function getCookie($name) {
    if (is_array($this->_COOKIESIN)) {
      foreach ($this->_COOKIESIN as $cookie) {
        if ($cookie['name'] == $name) {
          return $cookie;
        }
      }
    }

    return false;
  }

  /**
   * headers we got from server after request. keynames are lowercased to enshure
   * compability between certain PEAR-verions of HTTP_Request
   * @return array well...
   */
  public function getHeaders() {
    $data = $this->_PARAMS_FROM_SERVER;
    $out = array();
    foreach ($data as $n=>$v) {
    	$out[strtolower($n)] = $v;
    }
    return $out;
  }

  /**
   * the returned html itself
   * @return string returnbody
   */
  public function getBody() {
    if ($this->_RESPONSEBODY === false) {
      if (($this->_RESPONSECODE!=301) && ($this->_RESPONSECODE!=302) && ($this->_RESPONSECODE!=303) && ($this->_RESPONSECODE!=307)) {
        throw new Exception("No HTML and no redirect while visiting '".$this->_URL."'. Something fundamental with this request failed, code ".$this->_RESPONSECODE);
      }
      return '';
    }
    return $this->_RESPONSEBODY;
  }

  public function getBodyWithoutJS() {
  	$body = $this->getBody();
  	$body = preg_replace('@<script[^>]*?>.*?</script>@si','',$body);
  	return $body;
  }

  /**
   * http1.1 return code
   * @return integer http-statuscode
   */
  public function getCode() {
    return $this->_RESPONSECODE;
  }

  /**
   * url we ended at after request (after considering redirects)
   * @return string url
   */
  public function getURL() {
    return $this->_URL;
  }

  /**
   * post parameters after request (buffer, because the original parameters are already unset)
   * @returns array postvariables
   */
  public function getPost() {
    return $this->_POSTPARAMSOLD;
  }

  /**
   * should we follow 301/302 redirects? default = yes
   * @param bool follow
   */
  public function setFollowRedirects($follow) {
    $this->_REDIRECTS = $follow;
  }

  /////////////////////////////////////////////////////////////////////////////

  public function GETRequest($url) {
  	print_r($this);
    $this->_prepare($url);
    $this->_request(HTTP_REQUEST_METHOD_GET);
    $this->_cleanup();
  }

  public function POSTRequest($url) {
    $this->_prepare($url);
    $this->_request(HTTP_REQUEST_METHOD_POST);
    $this->_cleanup();
  }

}

