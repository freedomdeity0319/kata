<?php
/**
 * @package kata
 */






/**
 * nano-helper for javascript. simply add() javascript in your view, and get() it inside the head-section of your layout
 * @author mnt@codeninja.de
 * @package kata
 */

class JsHelper extends Helper {

	private $js='';

/**
 * add javascript to buffer inside a view
 */
	function add($js) {
		$this->js = $this->js."\n".$js;
	}

/**
 * return buffer. for use in your layout:
 * <code>
 * ...inside head-element inside your layout
 * [script type="application/javascript"]
 * echo $js->get();
 * [/script]
 * </code>
 */
	function get() {
	   return $this->js;
	}

}
