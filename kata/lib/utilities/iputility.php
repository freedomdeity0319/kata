<?php
/**
 * @package kata
 */





/**
 * contains IP-class
 * @package kata
 */

/**
 * some ip utility functions
 * @package kata_utility
 * @author mnt@codeninja.de
 */
class IpUtility {

	private $simpleHeaders = array (
		'REMOTE_ADDR',
		'HTTP_CLIENT_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'HTTP_X_COMING_FROM',
		'HTTP_COMING_FROM'
	);

	private $proxyHeaders = array (
		'HTTP_VIA',
		'HTTP_PROXY_CONNECTION',
		'HTTP_XROXY_CONNECTION',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'HTTP_X_COMING_FROM',
		'HTTP_COMING_FROM',
		'HTTP_CLIENT_IP',
		'HTTP_PC_REMOTE_ADDR',
		'HTTP_CLIENTADDRESS',
		'HTTP_CLIENT_ADDRESS',
		'HTTP_SP_HOST',
		'HTTP_SP_CLIENT',
		'HTTP_X_ORIGINAL_HOST',
		'HTTP_X_ORIGINAL_REMOTE_ADDR',
		'HTTP_X_ORIG_CLIENT',
		'HTTP_X_CISCO_BBSM_CLIENTIP',
		'HTTP_X_AZC_REMOTE_ADDR',
		'HTTP_10_0_0_0',
		'HTTP_PROXY_AGENT',
		'HTTP_X_SINA_PROXYUSER',
		'HTTP_XXX_REAL_IP',
		'HTTP_X_REMOTE_ADDR',
		'HTTP_RLNCLIENTIPADDR',
		'HTTP_REMOTE_HOST_WP',
		'HTTP_X_HTX_AGENT',
		'HTTP_XONNECTION',
		'HTTP_X_LOCKING',
		'HTTP_PROXY_AUTHORIZATION',
		'HTTP_MAX_FORWARDS',
		'HTTP_X_IWPROXY_NESTING',
		'HTTP_X_TEAMSITE_PREREMAP',
		'HTTP_X_SERIAL_NUMBER',
		'HTTP_CACHE_INFO',
		'HTTP_X_BLUECOAT_VIA'
	);

	/**
	 * try to do an educated guess about the users real ip, even if he is behind proxies
	 */
	public function getIp() {
		foreach ($this->simpleHeaders as $header) {
			$h = env($header);
			if (isset ($h) && !empty ($h)) {
				return $h;
			}
		}
		return '0.0.0.0';
	}

	public function isUsingProxy() {
		foreach ($this->proxyHeaders as $header) {
			$h = env($header);
			if (isset($h) && !empty($h)) {
				return true;
				break;
			}
		}
		return false;
	}

}
