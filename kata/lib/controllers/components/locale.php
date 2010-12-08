<?php

/**
 * @package kata_component
 */

/**
 * locale-component. reads and caches an phpfile with language-strings
 * components are lightweight supportclasses for controllers
 * @package kata_component
 */

/**
 * global variable to cache the locale-class
 * @global object $GLOBALS['__cachedLocaleComponent']
 * @name __cachedLocaleComponent
 */
$GLOBALS['__cachedLocaleComponent']= null;

/**
 * global function used to access language-strings. returns warning-string if key does not exist
 * @param string $msgId name of the language-string to return
 * @param array $msgArgs any parameters for printf if you have
 * @return string
 */
function __($msgId, $msgArgs= NULL) {
	if (null == $GLOBALS['__cachedLocaleComponent']) {
		$GLOBALS['__cachedLocaleComponent']= classRegistry :: getObject('LocaleComponent');
	}
	return $GLOBALS['__cachedLocaleComponent']->getString($msgId, $msgArgs);
}

/**
 * The Locale-Component Class
 * @package kata_component
 */
class LocaleComponent {
	/**
	 * placeholder for controller that owns this component
	 * @var object
	 */
	public $controller= false;

	/**
	 * array of strings of component-names this component needs to function. null or false if none.
	 * @var array
	 */
	public $components= array ();

	/**
	 * placeholder for all languages
	 * @var array
	 */
	private $acceptedLanguages= null;

	/**
	 * which language-code is currently in use
	 * @var string
	 * @private
	 */
	private $code= false;

	/**
	 * the array with all locale-strings for the current language are cached here
	 * @var mixed
	 */
	private $messages= null;

	/**
	 * called by controller after the component was instanciated first
	 * @param object $controller the calling controller
	 */
	public function startup(& $controller) {
		$this->controller= $controller;
		$this->setCode($this->findLanguage());
	}

	/**
	* returns html with h()ed entities and tags. entities are _not_ double-encoded, certain tags survive als html
	*
	* @param string $html raw html with umlauts
	* @return string html with
	*/
	public function escapeHtml($html) {
		//[roman] da es die schöne double_encode Sache bei htmlentities erst ab der PHP 5.2.3 gibt hier ein fieser Mist...
		if (version_compare(PHP_VERSION, '5.2.3', '>=')) {
			$html= htmlentities($html, ENT_QUOTES, 'UTF-8', FALSE);
		} else {
			$html= html_entity_decode($html, ENT_QUOTES, 'UTF-8');
			$html= htmlentities($html, ENT_QUOTES, 'UTF-8');
		}
		return $html;
	}

	function getStringInternal($id,$messageArgs=null) {
		if (!$this->code) {
			throw new Exception('Locale: code not set yet');
		}

		if (empty ($this->messages)) {
			$this->getMessages();
		}

		if (!isset ($this->messages[$id])) {
			return null;
		}
		$ret= $this->messages[$id];

		if (defined('LANGUAGE_PRINTF') && (LANGUAGE_PRINTF)) {
			$ret = vsprintf($ret, (null === $messageArgs ? array () : $messageArgs));
		} else {
			$replaced= 0;
			if (!empty ($messageArgs)) {
				foreach ($messageArgs as $name => $value) {
					$ret= str_replace('%'.$name.'%', $value, $ret);
					$replaced++;
				}
			}
			if ((DEBUG > 0) && ($replaced != count($messageArgs))) {
				throw new Exception('locale: "'.$id.'" called with wrong number of arguments for language '.$this->code.' (i replaced:'.$replaced.' i was given:'.count($messageArgs).') key value is "'.$this->messages[$id].'"');
			}
		}
			/*** GF_SPECIFIC ***/
		if (defined('LANGUAGE_C2T') && (LANGUAGE_C2T) && !empty ($_GET['c2t'])) {
			$ret= '.<span class="c2t" ltlang=\''.$this->getCode().'\' ltname=\''.$id.'\'>'.$ret.'</span>';
		}
			/*** /GF_SPECIFIC ***/

		if (defined('LANGUAGE_ESCAPE') && LANGUAGE_ESCAPE) {
			return $this->escapeHtml($ret);
		}

		return $ret;
	}

	/**
	 * return the translation for the given string-identifier. throws expetions (if DEBUG>0) if key is missing or wrong parameters.
	 * @param string $id identifier to look up translation
	 * @param array $messageArgs optional parameters that will be formatted into the string with printf
	 */
	function getString($id, $messageArgs= null) {
		$ret = $this->getStringInternal($id,$messageArgs);
		if (null === $ret) {
		if (DEBUG > 0) {
			throw new exception('locale: cant find "'.$id.'" in language '.$this->code);
		} else {
			return '---UNSET('.$id.')---';
		}
		}
		return $ret;
	}

	function safeGetString($id, $messageArgs=null) {
		$ret = $this->getStringInternal($id,$messageArgs);
		if (null === $ret) {
			return '';
		}
		return $ret;
	}

	/**
	 * sets a language-code. writes the code into the session of the user
	 * and sets Lang_Code for all views
	 * @param string $code short iso-code for language ("de" "en" "fr")
	 */
	function setCode($code) {
		if (empty ($code)) {
			return;
		}

		if ($this->code != $code) {
			$this->messages= null;
			/*
						if (isset ($this->Session)) {
							$this->Session->write('Lang.Code', $code);
						}
			*/
			$this->code= $code;
			$this->controller->set('Lang_Code', $code);
			if (function_exists('setlocale')) {
				setlocale(LC_COLLATE | LC_CTYPE | LC_TIME, $this->getLanguageFromTld($code));
			}
		}
	}

	function getCode() {
		return $this->code;
	}

	private function fillAcceptedLanguages() {
		if ($this->acceptedLanguages !== null) {
			return;
		}

		$this->acceptedLanguages= array ();
		if ($h= opendir(ROOT.'controllers'.DS.'lang'.DS)) {
			while (($file= readdir($h)) !== false) {
				if ($file {
					0 }
				== '.') {
					continue;
				}
				$temp= explode('.', $file);
				if (isset ($temp[1]) && ('php' == $temp[1])) {
					$this->acceptedLanguages[$temp[0]]= $temp[0];
				}
			}
			closedir($h);
		}

		if (isset ($this->acceptedLanguages['en'])) {
			unset ($this->acceptedLanguages['en']);
			$this->acceptedLanguages['en']= 'en';
		}

		if (isset ($this->acceptedLanguages['de'])) {
			unset ($this->acceptedLanguages['de']);
			$this->acceptedLanguages['de']= 'de';
		}
	}

	function doesLanguageExist($lang) {
		$this->fillAcceptedLanguages();
		return !empty ($this->acceptedLanguages[$lang]);
	}

	function getAcceptedLanguages() {
		$this->fillAcceptedLanguages();
		return $this->acceptedLanguages;
	}

	/**
	 * find the startup-language by looking at the LANGUAGE define in core/config.php
	 */
	function findLanguage() {
		if ((LANGUAGE == 'NULL') || (LANGUAGE === NULL)) {
			return null;
		}
		/*
				if (isset ($this->Session)) {
					$code= $this->Session->read('Lang.Code');
					if (isset ($code) && !empty ($code)) {
						return $code;
					}
				}
		*/
		if (LANGUAGE == 'VHOST') {
			$l= $this->getVhostLang();
			if (empty ($l)) {
				$l= $this->getBrowserLang();
			}
			return $l;
		}
		if (LANGUAGE == 'BROWSER') {
			return $this->getBrowserLang();
		}
		return LANGUAGE;
	}

	/**
	 * try to find an language that we have as a file and that has a high priority in the users browser.
	 * returns EN if anything fails
	 * @return string short iso-code
	 */
	function getBrowserLang() {
		$this->fillAcceptedLanguages();

		$wanted= env('HTTP_ACCEPT_LANGUAGE');
		if (isset ($wanted)) {
			$Languages= explode(",", $wanted);
			$SLanguages= array ();
			foreach ($Languages as $Key => $Language) {
				$Language= str_replace("-", "_", $Language);
				$Language= explode(";", $Language);
				if (isset ($Language[1])) {
					$Priority= explode("q=", $Language[1]);
					$Priority= $Priority[1];
				} else {
					$Priority= "1.0";
				}
				$SLanguages[]= array (
					'priority' => $Priority,
					'language' => $Language[0]
				);
			}

			foreach ($SLanguages as $key => $row) {
				$priority[$key]= $row['priority'];
				$language[$key]= $row['language'];
			}

			array_multisort($priority, SORT_DESC, $language, SORT_ASC, $SLanguages);

			$ALangString= implode(";", $this->acceptedLanguages);
			foreach ($SLanguages as $A) {
				$key= array_search($A['language'], $this->acceptedLanguages);
				if ($key === FALSE) {
					$GenericLanguage= explode("_", $A['language']);
					$pos1= strpos($ALangString, $GenericLanguage[0]);
					if (is_numeric($pos1)) {
						$key= $pos1 / 6;
					}
				}
				if (is_numeric($key) && (!isset ($Code))) {
					//$Code= $this->acceptedLanguages[$key];
					break;
				}
			}
		}

		return !empty ($Code) ? $this->getTldFromLanguage($Code) : '';
	}

	/**
	 * try to find an language that we have as a file depending on the current domain name
	 * foo.example.tr -> "tr"
	 * [foo.bar.]tr.example.com -> "tr"
	 */
	function getVhostLang($useTld= false) {
		$this->fillAcceptedLanguages();

		$name= explode('.', env('SERVER_NAME'));
		if (count($name) < 2) {
			return '';
		}

		foreach ($this->acceptedLanguages as & $lang) {
			// www.DE.example.com DE.example.com
			if (($name[0] == $lang) || ($name[1] == $lang)) {
				return $this->getTldFromLanguage($lang);
			}
			if ($useTld) {
				// www.example.DE
				if (isset ($name[count($name) - 1]) && ($name[count($name) - 1] == $lang)) {
					return $this->getTldFromLanguage($lang);
				}
			}
		}

		return '';
	}

	/**
	 * return the array containing all locale-codes by reference
	 */
	public function & getMessageArray() {
		if (null === $this->messages) {
			$this->getMessages();
		}
		return $this->messages;
	}

	/**
	 * load the message-array by loading controllers/lang/XX.php
	 * tries to cache the array via APC (if loaded)
	 */
	function getMessages() {
		$pre= $this->code;
		if (defined('CACHE_IDENTIFIER')) {
			$pre= CACHE_IDENTIFIER;
		}

		if (function_exists('apc_fetch') && (DEBUG < 1)) {
			$temp= apc_fetch($pre.'_lang_'.$this->code);
			if (false != $temp) {
				$this->messages= & $temp;
				return;
			}
		} else {
			//TODO eaccellerator? cacheutil?
		}

		$messages= array ();
		include (ROOT.'controllers'.DS.'lang'.DS.$this->code.'.php');
		$this->messages= & $messages;

		if (function_exists('apc_store') && (DEBUG < 1)) {
			apc_store($pre.'lang_'.$this->code, $this->messages, 300);
		} else {
			//TODO eaccellerator? cacheutil?
		}
	}

	private $tldToLanguageArr= array (
		'ae' => 'ar_AE',
		'ar' => 'es_AR',
		'bg' => 'bg_BG',
		'br' => 'pt_BR',
		'by' => 'be_BY',
		'cl' => 'es_CL',
		'cn' => 'zh_CN',
		'co' => 'es_CO',
		'cz' => 'cs_CZ',
		'de' => 'de_DE',
		'dk' => 'da_DK',
		'ee' => 'et_EE',
		'eg' => 'ar_EG',
		'en' => 'en_UK',
		'es' => 'es_ES',
		'fi' => 'fi_FI',
		'fr' => 'fr_FR',
		'gr' => 'el_GR',
		'hk' => 'zh_HK',
		'hr' => 'hr_HR',
		'hu' => 'hu_HU',
		'id' => 'id_ID',
		'il' => 'he_IL',
		'in' => 'en_IN',
		'ir' => 'fa_IR',
		'it' => 'it_IT',
		'jp' => 'ja_JP',
		'kr' => 'ko_KR',
		'lt' => 'lt_LT',
		'lv' => 'lv_LV',
		'mx' => 'es_MX',
		'nl' => 'nl_NL',
		'no' => 'nb_NO',
		'pe' => 'es_PE',
		'ph' => 'tl_PH',
		'pk' => 'ur_PK',
		'pl' => 'pl_PL',
		'pt' => 'pt_PT',
		'ro' => 'ro_RO',
		'rs' => 'sr_RS',
		'ru' => 'ru_RU',
		'se' => 'sv_SE',
		'si' => 'sl_SI',
		'sk' => 'sk_SK',
		'th' => 'th_TH',
		'tr' => 'tr_TR',
		'tw' => 'zh_TW',
		'ua' => 'ru_UA',
		'us' => 'en_US',
		've' => 'es_VE',
		'vn' => 'vi_VN',
		'yu' => 'yu_YU',
		'com' => 'en_UK',
		'dev' => 'de_DE',
		'int' => 'en_US',
		'00' => '00_00',

	);

	/**
	 * map given language-tld-code to DINISO code for setLocale()
	 * @param string language-code
	 * @return string DINISO-code
	 */
	private function getLanguageFromTld($lang) {
		return empty ($this->tldToLanguageArr[$lang]) ? '' : $this->tldToLanguageArr[$lang];
	}

	/**
	 * map given DINISO to language-tld-code code for setLocale()
	 * @param string DINISO-code
	 * @return string language-code
	 */
	function getTldFromLanguage($langcode) {
		$langcode= strtolower($langcode);
		if (strlen($langcode) == 2) {
			foreach ($this->tldToLanguageArr as $tld => $code) {
				if (substr($code, 0, 2) == $langcode) {
					return $tld;
				}
			}
		} else {
			foreach ($this->tldToLanguageArr as $tld => $code) {
				if (strtolower($code) == $langcode) {
					return $tld;
				}
			}
		}
		return '';
	}

}