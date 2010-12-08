<?php
/**
 * simpletest MUST be in your include-path, so we can find eg. 'unit_test.php'
 *
 * @package katatest_lib
 * @author Dietmar Riess
 */

/**
 * simpletest MUST be in your include-path, so we can find eg. 'unit_test.php'
 */
require_once 'web_tester.php';
require_once 'autorun.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'simpleTestExtension.php';

SimpleTest::prefer(new MyHTMLReporter());

/**
 * webTest.class arbeitet auf $doc DOMDocument nicht auf $browser;
 *
 * @package katatest_lib
 * @author Dietmar Riess
 */
class webTestClass extends WebTestCase {
	public $doc = null;
	public $forms = null;
	private $settings = array("failHTMLs"=>30,"debugLevel"=>0);
	private $countFailHTMLs = 0;
	public $tmpFiles = array();
	public $tmpDirs = array();
	public $extension = null;
	private $kataInitialized = false;

	public $errorList =  array(
		"<?php in Context"=>'<?php',
		"<?= in Context"=>'<?=',
		"<? in Context"=>'<? ',
		"Warnings in Context"=>'Warning: ',
		"Errors in Context"=>'Fatal error: ',
		"Notices in Context"=>'Notice: ',
		"Exceptions in Context"=>'Framework Exception',
		"undefined Constants"=>'Use of undefined constant ',
		"MissingLocaKeys"=>'---UNSET(',
		"No Page found"=>"Page not found"
		);
	public function webTestClass($debugLevel = null){
		if(!empty($debugLevel)){
			$this->settings["debugLevel"] = $debugLevel ;
		}
		if($this->settings["debugLevel"]<=1){
			$this->errorList["Array in Context"]="Array (";
		}
		$this->extension = new SimpleTestExtension($this);
	}

	/**
	 * bootstrap kata if not already done
	 */
	public function bootstrapKata() {
		if (!$this->kataInitialized) {
			require_once (BASEPATH.'lib'.DIRECTORY_SEPARATOR.'defines.php');
			require_once (LIB.'boot.php');
			$this->kataInitialized= true;
		}
	}

	/**
 	* load the given controller from kata and initialize it. you cant dispatch any url, use get() or post() for this.
 	* @param string $name name of the controller, eg 'Main'
 	*/
	public function loadController($name) {
		$this->bootstrapKata();

		require ROOT.'controllers'.DS.strtolower($name).'_controller.php';

		$name= $name.'Controller';

		$controller = new $name;
		$this->$name= $controller;
		$controller->_constructClasses();
		$controller->_base = BASEURL;
		$tempUrl = explode('/',BASEURL);
		array_shift($tempUrl);
		array_shift($tempUrl);
		array_shift($tempUrl);
		$controller->_basePath = '/'.implode('/',$tempUrl);
		$controller->webroot = $controller->base.'webroot/';
	}

	/**
	 * @see SimpleTestExtension
	 */
	function after($method){
		parent::after($method);
		$this->extension->after($method);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function createTmpFile($name,$content){
		$this->extension->createTmpFile($name,$content);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function assertContain($needle,$haystack,$message=null){
		$this->extension->assertContain($needle,$haystack,$message);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function assertNotContain($needle,$haystack,$message=null){
		$this->extension->assertNotContain($needle,$haystack,$message);
	}
	/**
	 * @see SimpleTestExtension
	 */
	function replaceTmpFile($name,$content,$replace){
		$this->extension->replaceTmpFile($name,$content,$replace);
	}

	/**
	 * zeigt mir die HTMLSeiten der Fehlerhaften HTMLMails an.
	 * Per default werden nur die ersten 30 Fehler HTMLs angezeigt da sonst der Browser stark ausgebremst wird.
	 */
	public function fail($message = "Fail"){
		parent::fail($message);
		//zeige ersten x Webseiten die Fehler erzeugt haben
		if($this->settings["failHTMLs"]>$this->countFailHTMLs){
			$this->countFailHTMLs ++;
			$this->sendMessage($this->_browser->getContent());
		}
	}
	/**
	 * es wird eine neue Seite geladen
	 * die Seite wird als DOMDocument() in $this->doc geladen, hat jedoch keine Beziehung zu $this->browser;
	 * die neue Seite wird auf validitÃ¤t Ã¼berprÃ¼ft
	 *
	 * @see  parent::get();
	 * @see validatePage();
	 */
	public function get($url, $parameters = false){
		parent::get($url,$parameters);
		$this->doc = new DOMDocument();
		//hier wird es immer Fehler geben da HTML nicht immer sonderlich wohlgeformt ist
		@$this->doc->loadHTML($this->_browser->getContent());
		$this->isValidPage();
		$this->lastParams = $parameters;
	}

	/**
	 * es wird eine neue Seite geladen
	 * die Seite wird als DOMDocument() in $this->doc geladen, hat jedoch keine Beziehung zu $this->browser;
	 * die neue Seite wird auf validitÃ¤t Ã¼berprÃ¼ft
	 *
	 * @see  parent::post();
	 * @see validatePage();
	 */
	public function post($url, $parameters = false){
		parent::post($url, $parameters);
		$this->doc = new DOMDocument();
		//hier wird es immer Fehler geben da HTML nicht immer sonderlich wohlgeformt ist
		@$this->doc->loadHTML($this->_browser->getContent());
		$this->isValidPage();
		$this->lastParams = $parameters;
	}

	/**
	 * Es wird das Abschicken eines Forms emuliert. Kann allerdings nicht mit file upload Feldern umgehen.
	 *
	 * @param array $attributes definiert attribute des gesuchten forms
	 * @param array $parameter Ã¼berschreibt/setzt weitere post parameter
	 * @param DOMNodeList $inElements Liste von Elementen in denen nach dem form gesucht werden soll
	 * @param integer $number bestimmt welches der gefundenen forms nun gemeint ist
	 * @see post()
	 * @see get()
	 **/
	public function submitForm($attributes,$parameter = array(),$inElements=null,$number=null){
		$forms = $this->getElements("form",$attributes,$inElements);
		if(empty($forms)){
			if($inElements !== null){
				$this->assertTrue(false,'At the specified location is no Form containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			}else{
				$this->assertTrue(false,'There is no Form containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			}
			return;
		}
		if($number !== null){
			if(isset($forms[$number])){
				$form = $forms[$number];
			}else{
				$this->assertTrue(false,'There are less then '.($number+1).' Forms containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
				return;
			}
		}else{
			$form = array_shift($forms);
		}
		if(($number === null) && !empty($forms)){
			$this->assertTrue(false,'There are multiple Forms containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			return;
		}
		$method = $form->getAttribute("method");
		$action = $form->getAttribute("action");
		$inputs = $form->getElementsByTagName("input");
		foreach ($inputs as $input){
			$type = $input->getAttribute("type");
			$name = $input->getAttribute("name");
			$value = $input->getAttribute("value");
			$checked = $input->getAttribute("checked");
			if(!isset($parameter[$name])){
				if($type == "text" or $type == "hidden" or $type == "password" or $type == "checkbox" or $type == "radio"){
					if(empty($checked) && ($type == "checkbox" || $type == "radio")){
						continue;
					}
					$nameParts = explode("[]",$name);
					if(count($nameParts) > 1){
						if(!isset($parameter[$nameParts[0]])){
							$parameter[$nameParts[0]] = array();
							$parameter[$nameParts[0]][] = $value;
							$setableParameter[$nameParts[0]] = true;
						}else if(!empty($setableParameter[$nameParts[0]])){
							$parameter[$nameParts[0]][] = $value;
						}
					}else{
						$parameter[$name] = $value;
					}
				}
			}
		}
		$areas = $form->getElementsByTagName("textarea");
		foreach ($areas as $area){
			$name = $area->getAttribute("name");
			$value = $area->textContent;
			if(!isset($parameter[$name])){
				$parameter[$name] = $value;
			}
		}
		$selections = $form->getElementsByTagName("select");
		foreach ($selections as $selection){
			$name = $selection->getAttribute("name");
			if(!isset($parameter[$name])){
				$options = $form->getElementsByTagName("option");
				foreach ($options as $option){
					if($option->hasAttribute("selected")){
						if($option->hasAttribute("value")){
							$value = $option->getAttribute("value");
						}else{
							$value = $option->textContent;
						}
						$parameter[$name] = $value;
					}
				}
			}
		}
		//print_r($parameter);
		if($method == "post"){
			$this->post($action,$parameter);
		}else{
			$this->get($action,$parameter);
		}
	}
	public function toString($array){
		if(!is_array($array)){
			return $array;
		}
		$output = "";
		foreach($array as $key=>$value){
			$output .= $key."=".$this->toString($value)." ";
		}
		return $output;
	}
	/**
	 * Es wird das anclicken eines Links emuliert. Reagiert jedoch nicht auf javascript.
	 *
	 * @param array|DOMElement $attributes definiert attribute des gesuchten Links oder ist der gesuchte Link
	 * @param DOMNodeList $inElements Liste von Elementen in denen nach dem Link gesucht werden soll
	 * @param integer $number bestimmt welches der gefundenen Links nun gemeint ist
	 * @see get()
	 **/
	public function followLink($attributes,$inElements=null,$number=null){
		if(get_class($attributes) == "DOMElement"){
			$links=array($attributes);
		}else{
			$links = $this->getElements("a",$attributes,$inElements);
		}
		if(empty($links)){
			$this->assertTrue(false,'There is no Link containing Attributes:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			return;
		}
		if($number !== null){
			if(isset($links[$number])){
				$link = $links[$number];
			}else{
				$this->assertTrue(false,'There are less then '.($number+1).' Links containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
				return;
			}
		}else{
			$link = array_shift($links);
		}
		if(($number=== null) && !empty($links)){
			$this->assertTrue(false,'There are multiple Links containing Attributs:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			return;
		}
		$url = $link->getAttribute("href");
		$this->get($url);
	}

	/**
	 * Ã¼berprÃ¼ft die momentane Seite auf standard Fehler, z.B. ob Fatal error: als Text enthalten ist.
	 */
	public function isValidPage(){
		$this->sendMessage("<ul>");
		foreach($this->errorList as $description =>$error){
			$this->assertNoText($error,$description);
		}
		//$this->assertHasElement("a",array("href"=>IMPRESSUM_URL));
		//$this->assertHasElement("a",array("href"=>AGB_URL));
		//$this->assertHasElement("a",array("href"=>DATENSCHUTZ_URL));
		$this->sendMessage("</ul>");
	}

	/**
	 * ersetzt oder erweitert die in einer url definierten parameter
	 * @param string $url die zu verÃ¤ndernde url
	 * @param array $parameters die zu ersetzenden/erweiternden parameter
	 * @return string verÃ¤nderte url
	 */
	public function changeUrlParameter($url,$parameters){
		$teile = explode("?",$url);
		$orginalUrl = $teile[0];
		$newParameters = $this->getUrlParameter($url);
		foreach($parameters as $key =>$parameter){
			if(empty($parameter)){
				unset($newParameters[$key]);
			}else{
				$newParameters[$key]=$parameter;
			}
		}
		$parameterList = "";
		$count = 0;
		foreach ($newParameters as $key =>$parameter){
			if($count >0){
				$parameterList .= "&";
			}
			$count++;
			$parameterList .= $key."=".$parameter;
		}
		return $orginalUrl."?".$parameterList;
	}

	/**
	 * gibt aus einer Url alle Get Parameter zurÃ¼ck
	 * @param string $url die zu untersuchende url
	 * @return array Liste an Get-Parametern der url
	 */
	public function getUrlParameter($url){
		$teile = explode("?",$url);
		if(count($teile)<=1){
			return null;
		}
		$urlParameters = explode("&",$teile[1]);
		$parameter = array();
		foreach($urlParameters as $urlParameter){
			$name = substr($urlParameter,0,strpos($urlParameter,"="));
			$value = substr($urlParameter,strpos($urlParameter,"=")+1,strlen($urlParameter));
			$parameter[$name] = $value;
		}
		return($parameter);
	}
	/**
	 * zerlegt eine Url in einzelteile und gibt diese zurÃ¼ck
	 * @param string $url die zu untersuchende url
	 * @return array url => orginal url, base => Domaine der url, path => pfad nach der Domaine, parameter => Liste mit getParametern
	 */
	public function getUrlParts($url){
		$teile = explode("?",$url);
		$orginalUrl = $teile[0];
		$urlPieces= explode("/",$orginalUrl);
		$return= array();
		$return["url"] = $orginalUrl;
		$return["base"] = $urlPieces[2];
		$pathParts = explode($return["base"],$return["url"]);
		$return["path"]= $pathParts[1];
		$return["parameter"]= $this->getUrlParameter($url);
		return  $return;
	}
	/**
	 * Ã¼berprÃ¼ft ob der url string in der momentan gelandeten url enthalten ist
	 * @param string $url ein teil der erwarteten url
	 * @param array $parameter Liste an getParametern die in der gelandeten url enthalten sein mÃ¼ssen
	 */
	public function assertLandOnUrl($url,$parameter=null){
		$urlParts = $this->getUrlParts($this->getUrl());
		$urlExtended = $url."/";
		if($urlParts["url"] != $url){
			$url = $url."/";
		}
		if(count(explode($url,$urlParts["url"]))>1 ){
			if($parameter !== null){
				$urlParameter = $urlParts["parameter"];
				foreach($urlParameter as $key =>$value){
					if(! isset($parameter[$key]) || $parameter[$key] != $value){
						$this->assertTrue(false,'We expected to land at: ['.$url.'] We landed at: ['.$this->getUrl().']');
						return;
					}
				}
				foreach($parameter as $key =>$value){
					if(! isset($urlParameter[$key]) || $urlParameter[$key] != $value){
						$this->assertTrue(false,'We expected to land at: ['.$url.'] We landed at: ['.$this->getUrl().']');
						return;
					}
				}
			}
			$this->assertTrue(true);
			return;
		}
		$this->assertFalse(true,'We expected to land at: ['.$url.'] We landed at: ['.$urlParts["url"].']');
	}
	private function distinctDOMElements(&$elements,$DOMElement){
		foreach($elements as $key=>$element){
			if($element === $DOMElement){
				return;
			}
		}
		array_push($elements,$DOMElement);
	}

	/**
	* Setzt in einer Liste an Elementen jeweils die entsprechenden Attribute.
	* Wirkt sich nicht auf $this->browser aus.
	* Gibt false zurÃ¼ck wenn DOMNodeListe leer ist.
	*
	* Besonderheiten:
	* Key = "attributeName &" bedeutet das der ursprÃ¼ngliche inhalt erweitert wird
	* Key = "attributeName !" bedeutet das aus dem ursprÃ¼ngliche inhalt jedes vorkommen des values entfernt wird
	* Value = false bedeutet das dieses Attribut entfernt wird, wenn enthalten...
	*
	*
	* @param DOMNodeList $elemtlist Liste mit elementen in denen gesucht werden soll;
	* @param array $attributes
	* @return boolean gibt false zurÃ¼ck wenn DOMNodeListe leer ist.
	**/
	public function setAttributes($elemtlist,$attributes){
		if(empty($elemtlist)){
			return false;
		}
		foreach($elemtlist as $element){
			foreach($attributes as $key => $value){
				$keyParts = explode(" ",$key);
				$value2 = "";
				if(count($keyParts)>1){
					if($element->hasAttribute($keyParts[0])){
						if($keyParts[1]=="&"){
							$value2 = $element->getAttribute($key);
						}else if($keyParts[1]=="!"){
							$temp = $element->getAttribute($key);
							$value = str_replace($value,"",$temp);
						}
					}
				}
				if($value == false && $element->hasAttribute($keyParts[0])){
					$element->removeAttribute($key);
				}else{
					$element->setAttribute($key,$value2.$value);
				}
			}
		}
		return true;
	}
	/**
	* fÃ¼r jedes Key=>Value Element in Attributes wird ein attribut mit dem namen des Keys gesucht welches den Inhalt Value hat.
	* Wirkt sich nicht auf $this->browser aus.
	*
	* Besonderheiten:
	*	Key = innerText bedeutet der textuelle Innhalt zwischen den Nodes
	*	nachfolgende Operatoren im Key werden unterstÃ¼tzt
	*	Key =key :Vergleich value == Inhalt
	*	Key =key != :vergleich value != Inhalt
	*	Key =key like :value kommt im Inhalt vor
	*	Key =key ! or key !like : value kommt nicht im Inhalt vor
	*	Key =key in : value kommt als Wort im Inhalt vor
	*	Key = key key !in : value kommt nicht als Wort im Inhalt vor
	*	Ohne Operator angabe wird like verwendet
	*
	*
	* @param DOMNodeList $elemtlist Liste mit elementen in denen gesucht werden soll;
	* @param array $attributes Liste an attributen
	* @param array $elemnts RÃ¼ckgabewert enthÃ¤lt eine Liste mit unterschiedlichen Elementen die durch Elemente erweitert wird, welche dem Filter genÃ¼gen
	**/
	private function filterElementsByAttribut($elemtlist,$attributes,&$elements){
		foreach ($elemtlist as $element){
			if(empty($attributes) || !is_array($attributes)){
				$this->distinctDOMElements($elements,$element);
				continue;
			}
			$passed = true;
			foreach($attributes as $attribute=>$value){
				$attributParts = explode(" ",$attribute);
				$attributKey = $attributParts[0];
				$attributeOperator = "like";
				if(isset($attributParts[1])){
					$attributeOperator = $attributParts[1];
				}
				if($attributKey != "innerText"){
					$currentValue = $element->getAttribute($attributKey);
				}else{
					$currentValue = $element->textContent;
				}
				if($attributeOperator == "like"){
					$menge = count(explode($value,$currentValue));
					if($menge <= 1){
						$passed = false;
						break;
					}
				}
				if($attributeOperator == "in"){
					$words = explode(" ",$currentValue);
					$in = false;
					foreach($words as $word){
						if($value == $word){
							$in = true;
							break;
						}
					}
					if(!$in){
						$passed = false;
						break;
					}
				}
				if($attributeOperator == "!in"){
					$words = explode(" ",$currentValue);
					$in = false;
					foreach($words as $word){
						if($value == $word){
							$in = true;
							break;
						}
					}
					if($in){
						$passed = false;
						break;
					}
				}
				if($attributeOperator == "="){
					if($value!=$currentValue){
						$passed = false;
						break;
					}
				}
				if($attributeOperator == "!="){
					if($value==$currentValue){
						$passed = false;
						break;
					}
				}
				if($attributeOperator == "!" or $attributeOperator == "!like"){
					$menge = count(explode($value,$currentValue));
					if($menge > 1){
						$passed = false;
						break;
					}
				}
			}
			if($passed == true){
				$this->distinctDOMElements($elements,$element);
			}
		}
	}
	/**
	 * gibt eine Liste an Elementen zurÃ¼ck welche sich innerhalb einer Liste an Elementen befinden und die definierten Attribute besitzen.
	 * Wirkt sich nicht auf $this->browser aus.
	 *
	 * @param string $tag nodeName der Elemente die man mÃ¶chte
	 * @param array $attributes Liste an Attributen, die die Elemente besitzen mÃ¼ssen
	 * @param DOMNodeList $inElements Liste an Elementen welche die gesuchten Elemente besitzen
	 * @param boolean $inner bei true werden Elemente aus der $inElements Liste zurÃ¼ckgegeben welche entsprechende Elemente enthalten.
	 * @return DOMNodeList  Liste an zutreffenden Elementen
	 **/
	public function getElements($tag,$attributes=null,$inElements = null,$inner = true){
		$innerElements = array();
		$outerElements = array();
		if($inElements === null){
			$tags = $this->doc->getElementsByTagName($tag);
			$this->filterElementsByAttribut($tags,$attributes,$innerElements);
		}else{
			foreach($inElements as $inElement){
				if(get_class($inElement)=="DOMElement"){
					$tags = $inElement->getElementsByTagName($tag);
					$before = count($innerElements);
					$this->filterElementsByAttribut($tags,$attributes,$innerElements);
					if($before < count($innerElements)){
						$this->distinctDOMElements($outerElements,$inElement);
					}
				}else{
					$this->fail("wrong parameter for getElements");
				}
			}
		}
		if($inner){
			return $innerElements;
		}
		return $outerElements;
	}
	/**
	 * gibt eine Liste an Attributwerten fÃ¼r definiertes Attribut der definierten Elemente wieder.
	 *
	 *
	 * @param string $attribute
	 * @param DOMNodeList $elements
	 * @return array Liste mit Attributwerten
	 */
	public function getAttributes($attribute,$elements){
		$attributes = array();
		foreach($elements as $element){
			if($attribute == "innerText"){
				$attributes[] = $element->textContent;
			}else{
				$attributes[] = $element->getAttribute($attribute);
			}
		}
		return $attributes;
	}
	/**
	 * @see getElements()
	 */
	public function assertHasElement($tag,$attributes,$inElements = null){
		$elements = $this->getElements($tag,$attributes,$inElements);
		if(empty($elements)){
			if($inElements !== null){
				$this->assertTrue(false,'At the specified location is no Element named: ['.$tag.'] with specified attribute:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			}else{
				$this->assertTrue(false,'There is no Element named: ['.$tag.'] with specified attribute:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
			}
			return;
		}
		$this->assertTrue(true);
	}

	/**
	 * @see getElements()
	 */
	public function assertNotHasElement($tag,$attributes,$inElements = null){
		$elements = $this->getElements($tag,$attributes,$inElements);
		if(empty($elements)){
			$this->assertTrue(true);
			return;
		}
		$this->assertTrue(false,'There is an Element named: ['.$tag.'] with specified attribute:['.$this->toString($attributes).'] in ['.$this->getUrl().']');
	}
}
