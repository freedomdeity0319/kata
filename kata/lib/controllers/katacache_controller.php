<?php
/**
 * controller to serve the combined css and js files as created by html->javascriptTag() and html->cssTag()
 *
 * @package kata
 */
class KatacacheController extends Controller {

	private $expireSeconds = WEEK;

	private function cacheHeaders($file=null) {
		header('Expires: '.gmdate('D, d M Y H:i:s', time() + (60*$this->expireSeconds)) . ' GMT');
		if (null !== $file) {
			$fileTime = filemtime($file);
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $fileTime).' GMT');
			header('ETag: '.md5($fileTime));
		}
	}

	function css() {
		$file = ROOT.'tmp'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'css.cache';

		header('Content-Type: text/css');
		$this->cacheHeaders($file);
		ob_start('ob_gzhandler');
		readfile($file);
		die;
	}

	function js() {
		$file = ROOT.'tmp'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'js.cache';

		header('Content-Type: text/javascript');
		$this->cacheHeaders($file);
		ob_start('ob_gzhandler');
		readfile($file);
		die;
	}

}

