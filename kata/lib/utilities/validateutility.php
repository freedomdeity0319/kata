<?php
/**
 * @package kata
 */

/**
 * check if an array of parameters matches certain criterias.
 * you can still use the (deprecated) defines of model.php
 *
 * @package kata_utility
 * @author mnt@codeninja.de
 */
class ValidateUtility {

	private $rules= array (
		'VALID_NOT_EMPTY' => '/.+/',
		'VALID_NUMBER' => '/^[0-9]+$/',
		'VALID_EMAIL' => '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i',
		'VALID_YEAR' => '/^[12][0-9]{3}$/',
		'VALID_IP' => '\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b',

	);

	/**
	 * checks the given values of the array match certain criterias
	 *
	 * <code>
	 * check(array(
	 * 'email'=>'VALID_EMAIL'
	 * ),$this->params['form'])
	 * </code>
	 *
	 * @param array $params key/value pair. key is the name of the key inside the $what-array, value is a "VALID_" define (see above) or simply the name of the define, the name of a function that is given the string (should return bool wether the string validates)
	 * @param array $what the actual data
	 * @return true if data validates OR $params-key, for example 'VALID_EMAIL' if no correct email given
	 */
	function check($params, $what) {
		if (!is_array($params)) {
			return false;
		}
		foreach ($params as $param => $how) {
			if (!isset ($what[$param])) {
				return $how;
			}
			if (function_exists($how)) {
				if (!$how ($what[$param])) {
					return $how;
				}
			} else {
				if (isset ($this->rules[$how])) {
					if (!preg_match($this->rules[$how], $what[$param])) {
						return $how;
					}
				} else {
					//regex per constant, gaaanz alt
					if (substr($how,0,1)=='/') {
						if (!preg_match($how, $what[$param])) {
							return $this->getConstantName($how);
						}
						return true;
					}
					throw new Exception('validateUtil: Dont know how to do '.$how);
				}
			}
		}
		return true;
	}

/**
 * given the value of a constant find the name of the constant. needed for backwards compatibility, because validate() was in the model earlier
 * @param string $constValue value of the constant
 * @return string name of the constant
 */
	private function getConstantName($constValue) {
		$all = &get_defined_constants();
		foreach ($all as $name=>&$value) {
			if($value === $constValue) {
				return $name;
			}
		}
		return false;
	}

}
