<?php
/**
 * model related exception
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata_model
 */



/**
 * Thrown if an sql-query generates an duplication error due du primary/unique constraints
 * 
 * @package kata_model
 */
class DatabaseDuplicateException extends DatabaseErrorException {

}
