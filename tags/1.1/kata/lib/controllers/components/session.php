<?php
/**
 * @package kata
 */



/**
 * dummy-file for session classes. includes the correct session class
 * components are lightweight supportclasses for controllers
 */
if (!defined('SESSION_STORAGE')) {
    require(LIB.'controllers'.DS.'components'.DS.'file.session.php');
} else {
    require(LIB.'controllers'.DS.'components'.DS.strtolower(SESSION_STORAGE).'.session.php');
}

