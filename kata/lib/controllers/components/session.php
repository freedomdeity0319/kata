<?php
/**
 * @package kata_component
 */



/**
 * dummy-file for session classes. includes the correct session class
 * components are lightweight supportclasses for controllers
 * @package kata_component
 */
if (!defined('SESSION_STORAGE')) {
    require(LIB.'controllers'.DS.'components'.DS.'file.session.php');
} else {
    require(LIB.'controllers'.DS.'components'.DS.strtolower(SESSION_STORAGE).'.session.php');
}

