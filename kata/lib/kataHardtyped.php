<?php
/**
 * experimental type-enforcement class
 * @package kata
 */


/**
 * experimental type-enforcement class. just base your class on this class
 * - dont forget to call parent::__construct() if you override the c'tor
 * - member-variables are also protected automatically
 * @package kata_internal
 */
class kataHardtyped {
      protected $___hardTypes = array();
      protected $___hardVars = array();

      function __construct() {
         $vars = get_class_vars(get_class($this));
         foreach ($vars as $name=>$value) {
                 $dummy = $this->$name;
                 unset($this->$name);
         }
      }


      function __get($name) {
          if ($name=='__types') {
             throw new InvalidArgumentException('you cant access __$types');
          }
          if (isset($this->___hardVars[$name])) {
             return $this->___hardVars[$name];
          }
          throw new Exception("'$name' is no known property of class '".get_class($this)."'");
      }
      
      function __set($name,$value) {
          if ($name=='__types') {
             throw new InvalidArgumentException('you cant access __$types');
          }
          if (!isset($this->___hardVars[$name])) {
             $this->___hardVars[$name] = $value;
             $this->___hardTypes[$name] = gettype($value);
             return;
          }
          if (gettype($value) != $this->___hardTypes[$name]) {
             throw new InvalidArgumentException("setting '$name' to type '".gettype($value)."' disallowed, is of type '".$this->___hardTypes[$name]."'");
          }
          $this->___hardVars[$name] = $value;
      }
      
}

