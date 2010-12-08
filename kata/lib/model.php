<?php


/**
 * The Base Model. used to access the database (via dbo_ objects)
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2008, mnt@codeninja.de
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata
 */

/**
 * validation string define to check if string is not empty
 * @deprecated 31.04.2009
 */
define('VALID_NOT_EMPTY', '/.+/');
/**
 * @deprecated 31.04.2009
 * validation string define to check if string is numeric
 */
define('VALID_NUMBER', '/^[0-9]+$/');

/**
 * @deprecated 31.04.2009
 * validation string define to check if string is an email-address
 */
define('VALID_EMAIL', '/\\A(?:^([a-z0-9][a-z0-9_\\-\\.\\+]*)@([a-z0-9][a-z0-9\\.\\-]{0,63}\\.(com|org|net|biz|info|name|net|pro|aero|coop|museum|[a-z]{2,4}))$)\\z/i');

/**
 * @deprecated 31.04.2009
 * validation string define to check if string is a numeric year
 */
define('VALID_YEAR', '/^[12][0-9]{3}$/');

/**
 * The base model-class that all models derive from
 * @package kata
 */
class Model {
	/**
	 * which connection to use of the ones defines inside config/database.php
	 * @var string
	 */
	public $connection= 'default';

	/**
	 * whether to use a specific table for this model. false if not specific, otherwise the tablename
	 * @var mixed
	 */
	public $useTable= false;

	/**
	 * the class used to access the database
	 * @var object
	 */
	protected $dboClass= null;

	/**
	 * lazy setup dbo the first time its used
	 * @return object intialized dbo-class
	 */
	function dbo() {
		if (null === $this->dboClass) {
			$this->setupDbo($this->connection);
		}
		return $this->dboClass;
	}

	/**
	 * load dbo-class, give dbconfig to class
	 * @param string $connName name of the connection to use
	 */
	protected function setupDbo($connName) {
		require_once (ROOT.'config'.DS.'database.php');
		if (!class_exists('DATABASE_CONFIG')) {
			throw new Exception('Incorrect config/database.php');
		}

		$dbvars= get_class_vars('DATABASE_CONFIG');
		$dboname= 'dbo_'.$dbvars[$connName]['driver'];

		if (!class_exists($dboname)) {
			kataUse($dboname);
		}
		$this->dboClass= classRegistry :: getObject($dboname, $connName);

		if (!isset ($this->dboClass->config)) {
			$this->dboClass->dbconfig= $dbvars[$connName];
		}
	}

	/**
	 * allowes you to switch the current connection dynamically.
	 *
	 * @param string $connName name of the new connection to use
	 */
	function changeConnection($connName) {
		$this->connection= $connName;
		$this->setupDbo($connName);
	}

	/**
	 * getter for the config options of the current model
	 * @var string
	 */
	public function getConfig() {
		return $this->dbo()->dbconfig;
	}

	/**
	 * getter for the database link of the current model. whats returned here depends greatly on the dbo-class
	 * @var mixed
	 */
	public function getLink() {
		return $this->dbo()->getLink();
	}

	/**
	 * utility function to generate correct tablename
	 *
	 * @param string $n tablename to use. if null uses $this->useTable. if that is also null uses modelname.
	 * @param bool $withPrefix if true adds prefix and adds the correct quote-signs to the name
	 * @return string
	 */
	private function getTableName($n= null, $withPrefix= true) {
		$name= get_class($this);

		if ($withPrefix) {
			if (null !== $n) {
				return $this->dbo()->quoteLeft.$this->getPrefix().$n.$this->dbo()->quoteRight;
			}
			if ($this->useTable) {
				return $this->dbo()->quoteLeft.$this->getPrefix().$this->useTable.$this->dbo()->quoteRight;
			}
			return $this->dbo()->quoteLeft.$this->getPrefix().strtolower($name).$this->dbo()->quoteRight;
		}

		if (null !== $n) {
			return $n;
		}
		if ($this->useTable) {
			return $this->useTable;
		}
		return strtolower($name);
	}

	/**
	 * return the prefix configured for this connection
	 *
	 * @return string
	 */
	public function getPrefix() {
		if (!empty ($this->dbo()->dboconfig['prefix'])) {
			return $this->dbo()->dboconfig['prefix'];
		}
		return '';
	}

	/**
	 * execute an actual query on the database
	 * @param string $s the sql to execute
	 * @param string $idname can be used to have the keys of the returned array equal the value ob the column given here (instead of just heaving 0..x as keys)
	 */
	function & query($s, $idname= false) {
		return $this->dbo()->query($s, $idname);
	}

	/**
	 * used to cache a reference to the CacheUtility
	 * @var class
	 */
	private $cacheUtilRef= false;

	/**
	 * Do a query that is cached via the cacheUtility
	 *
	 * @param string $s sql-string
	 * @param string $idname if set the key of the array is set to the value of this field of the result-array. So the result is not numbered from 0..x but for example the value of the primary key
	 * @param string $cacheid the id used to store this query in the cache. if ommited we try to build a suitable key
	 * @param int $ttl time to live in seconds (0=infinite)
	 */
	function & cachedQuery($s, $idname= false, $cacheid= false, $ttl= 0) {
		if (!$cacheid) {
			$bt= debug_backtrace();
			$cacheid= $bt[1]['class'].'.'.$bt[1]['function'].'.'.$bt[1]['line'];

			if (isset ($bt[1]['args']) && is_array($bt[1]['args'])) {
				foreach ($bt[1]['args'] as $arg) {
					if (null === $arg) {
						$cacheid .= '-null';
					}
					elseif (false === $arg) {
						$cacheid .= '-false';
					} else {
						$cacheid .= '-'.$arg;
					}
				}
			}
		}

		if (!$this->cacheUtilRef) {
			$this->cacheUtilRef= getUtil('Cache');
		}

		$res= $this->cacheUtilRef->read($cacheid);
		if (false !== $res) {
			return $res;
		}

		$res= $this->query($s, $idname);
		$this->cacheUtilRef->write($cacheid, $res, $ttl);
		return $res;
	}

	/**
	 * escape possibly harmful strings so you can safely append them to an sql-string
	 */
	function escape($s) {
		return $this->dbo()->escape($s);
	}

	/**
	 * enclose string in quotes and escape it
	 */
	function quote($s) {
		return '\''.$this->dbo()->escape($s).'\'';
	}

	/**
	 * turn a unix timestamp into datetime-suitable data
	 * @param integer $t unix time
	 * @return string sql-statement
	 */
	function makeDateTime($t) {
		return $this->dbo()->makeDateTime($t);
	}

	/**
	 * convenience method for writeLog
	 * @param string $what what to log
	 * @param string $where where to log (KATA_DEBUG OR KATA_ERROR)
	 */
	function log($what, $where) {
		writeLog($what, $where);
	}

	/**
	 * turn the given array into "name=value,name=value" pairs suitable for INSERT or UPDATE-sqls. strings are automatically escaped
	 * @param array $params the data
	 */
	function pairs($params) {
		if (!is_array($params)) {
			throw new InvalidArgumentException('pairs: params must be an array');
		}
		$out= '';
		foreach ($params as $v => $k) {
			if (is_null($k)) {
				$out .= $this->dbo()->quoteLeft.$v.$this->dbo()->quoteRight."=NULL,";
			} else {
				$out .= $this->dbo()->quoteLeft.$v.$this->dbo()->quoteRight."=".$this->quote($k).",";
			}
		}
		return substr($out, 0, strlen($out) - 1);
	}

	/**
	 * construct a suitable where-clause for a query
	 * @param mixed $id
	 * @param string $tableName needed to generate a primary key name
	 * @return string full 'WHERE x=' string
	 */
	function getWhereString(& $id, $tableName) {
		if (empty ($id)) {
			return '';
		}
		return ' WHERE '.$this->getWhereStringHelper($id, $tableName);
	}

	/**
	 * do the actual work for getWhereString(). analyse strings and branch for arrays
	 * @param mixed $id
	 * @param string $tableName needed to generate a primary key name
	 * @return string 'x=y AND x=z' string without 'WHERE'
	 */
	function getWhereStringHelper(& $id, $tableName) {
		if (!is_array($id)) {
			return $this->dbo()->quoteLeft.'id'.$this->dbo()->quoteRight.'='.$this->quote($id);
		}

		$orMode= in_array('or', $id);
		reset($id);

		$num= 0;
		$s = '';
		$skipBoolean = true;
		foreach ($id as $name => $value) {
			$num++;

			if (!$skipBoolean) {//&& ($num != count($id))) {
				$s .= ($orMode ? ' OR ' : ' AND ');
			}

			if (!is_array($value) && (('or' == strtolower($value)) || ('and' == strtolower($value)))) {
				$skipBoolean=true;
				continue;
			}
			$temp= explode(' ', $name);

			if ((count($temp) > 1)) {
				$operator= strtolower($temp[1]);

				if ($operator == 'in') {
					if (!is_array($value)) {
						throw new Exception($name.' needs to have array() as value');
					}
					$s .= $temp[0].' IN (\''.implode('\',\'', $value).'\')';
					$skipBoolean=false;
					continue;
				}
				if (in_array($operator, array (
						'>',
						'<',
						'<>',
						'>=',
						'<=',
						'like'
					))) {
					$s .= $this->dbo()->quoteLeft.$temp[0].$this->dbo()->quoteRight.' '.$operator.' '.$this->quote($value);
					$skipBoolean=false;
					continue;
				}

			}//count($temp)

			if (is_array($value)) {
				$s .= ' ( '.$this->getWhereStringHelper($value, $tableName).' ) ';
				$skipBoolean=false;
				continue;
			}

			$s .= $this->dbo()->quoteLeft.$name.$this->dbo()->quoteRight.'='.$this->quote($value);
			$skipBoolean=false;
		} //foreach

		return $s;
	}

	/**
	 * select rows using various methods (see $method for full list)
	 *
	 * <code>
	 * $rows = $this->find('all',array(
	 * 	'conditions' => array( // WHERE conditions to use. default all elements are AND, just add 'or' to the condition-array to change this
	 * 		'field' => $thisValue,
	 * 		'or',
	 * 		'field2'=>$value2,
	 * 		'field3'=>$value3,
	 * 		'field4'=>$value4
	 * 	),
	 * 	'fields' => array( //array of field names that we should return. first field name is used as array-key if you use method 'list' and listby is unset
	 * 		'field1',
	 * 		'field2'
	 * 	),
	 *  'order' => array( //string or array defining order. you can add DESC or ASC
	 * 		'created',
	 * 		'field3 DESC'
	 * 	),
	 *  'group' => array( //fields to GROUP BY
	 * 		'field'
	 *	),
	 *  'listby' => array( //only if find('list'): fields to arrange result-array by
	 *		'field1','field2'
	 *  ),
	 *  'limit' => 50, //int, how many rows per page
	 *  'page' => 1, //int, which page, starting at 1
	 * ),'mytable');
	 * </code>
	 *
	 * @param string $method can be 'all','list','count','first','neighbors'
	 * @param array $params see example
	 * @param mixed $tableName string or null to use modelname
	 */
	function find($method, $params= array (), $tableName= null) {
		$orderBy= '';
		if (!empty ($params['order'])) {
			if (!is_array($params['order'])) {
				throw new InvalidArgumentException('order must be an array');
			}
			$orderBy= ' ORDER BY '.implode(',', $params['order']);
		}
		$groupBy= '';
		if (!empty ($params['group'])) {
			if (!is_array($params['group'])) {
				throw new InvalidArgumentException('group must be an array');
			}
			$groupBy= ' GROUP BY '.implode(',', $params['group']);
		}
		$fields= '*';
		if (!empty ($params['fields'])) {
			if (!is_array($params['fields'])) {
				throw new InvalidArgumentException('fields must be an array');
			}
			$fields= implode(',', $params['fields']);
		}
		$where= '';
		if (!empty ($params['conditions'])) {
			if (!is_array($params['conditions'])) {
				throw new InvalidArgumentException('conditions must be an array');
			}
			$where= $this->getWhereString($params['conditions'], $tableName);
		}

		$indexFields= false;
		switch ($method) {
			case 'list' :
				if (empty ($params['listby'])) {
					if (empty ($params['field'])) {
						reset($params['fields']);
						$indexFields= array(key($params['fields']));
					} else {
						$indexFields= array('id');
					}
				} else {
					if (!is_array($params['listby'])) {
						throw new InvalidArgumentException('listby must be an array');
					}
					$indexFields= $params['listby'];
				}

			case 'all' :
				$sql= 'SELECT '.$fields.' FROM '.$this->getTableName($tableName).$where.$orderBy.$groupBy;
				if (!empty ($params['page']) && is_numeric($params['page'])) {
					$page= (int) $params['page'];
					$perPage= 50;
					if (!empty ($params['limit']) && is_numeric($params['limit'])) {
						$perPage= $params['limit'];
					}
					$sql= $this->dbo()->getPageQuery($sql, $page, $perPage);
				}
				return $this->query($sql, $indexFields);
				break;

			case 'count' :
				$r= $this->query('SELECT count(*) AS c FROM '.$this->getTableName($tableName).$where);
				return isset ($r[0]['c']) ? $r[0]['c'] : 0;
				break;

			case 'first' :
				$sql= 'SELECT '.$fields.' FROM '.$this->getTableName($tableName).$where.$orderBy.$groupBy;
				$sql= $this->dbo()->getPageQuery($sql, 1, 1);
				return $this->query($sql);
				break;

			case 'neighbors' :
				die('not implemented yet');
				break;

			default :
				throw new InvalidArgumentException('model: find() doesnt know method '.$method);
				break;
		}
	}

	/**
	 * read data from the database.
	 * <code>
	 * $rows = $this->read(5);
	 * $rows = $this->read(array('foobarId'=>5));
	 * $rows = $this->read(array(
	 * 	'foobarId'=>6,
	 *  'and',
	 *  'someId'=>2
	 * ));
	 *
	 * </code>
	 *
	 * @param mixed $id read the row with this primary key (if null: all rows)
	 * @param array $fields return these colums (if null: all fields)
	 * @param string $tableName read from this table (if ommitted: use tablename of this model, including prefix)
	 */
	function read($id= null, $fields= null, $tableName= null) {
		$fieldName= false;

		if (($fields !==null) && !is_array($fields)) {
			throw new InvalidArgumentException('fields must be an array');

		}

		return $this->query('SELECT '.
		 ($fields == null ? '*' : implode(',', $fields)).
		' FROM '.$this->getTableName($tableName).
		$this->getWhereString($id, $tableName), $fieldName);
	}

	/**
	 * insert a record into the database.
	 *
	 * <code>
	 * $this->create(array('fooId'=>1,'int1'=>10,'int2'=>20));
	 * </code>
	 *
	 * @param array $fields name=>value pairs to be inserted into the table
	 * @param string $tableName insert into this table (if ommitted: use tablename of this model, including prefix)
	 */
	function create($fields, $tableName= null) {
		$fieldstr= '';
		$valuestr= '';
		if (!is_array($fields)) {
			throw new InvalidArgumentException('fields must be an array');
		}
		foreach ($fields as $fieldname => $value) {
			$fieldstr .= $this->dbo()->quoteLeft.$fieldname.$this->dbo()->quoteRight.',';
			$valuestr .= $this->quote($value).',';
		}

		return $this->query('INSERT INTO '.
		$this->getTableName($tableName).
		' ('.substr($fieldstr, 0, -1).') VALUES ('.substr($valuestr, 0, -1).')');
	}

	/**
	 * delete the row whose id is matching
	 *
	 * <code>
	 * $this->delete(5);
	 * $this->delete(array('rowId'=>10));
	 * $this->delete(array(
	 * 	'rowId'=>20,
	 * 	'and',
	 *  'parentId'=>10
	 * ));
	 * </code>
	 *
	 * @param mixed $id primary key of row to delete
	 * @param string $tableName delete from this table (if ommitted: use tablename of this model, including prefix)
	 */
	function delete($id, $tableName= null) {
		if (empty ($id)) {
			throw new InvalidArgumentException('delete without id, seems odd');
		}
		return $this->query('DELETE FROM '.
		$this->getTableName($tableName).
		$this->getWhereString($id, $tableName));
	}

	/**
	 * update a row whose id is matching
	 *
	 * <code>
	 * $this->update(array(
	 * 	'fooId'=>10,
	 *  'data1'=>20
	 * ));
	 * </code>
	 *
	 * @param mixed $id primary key of row to update
	 * @param array $fields name=>value pairs of new values
	 * @param string $tableName update data in this table (if ommitted: use tablename of this model, including prefix)
	 */
	function update($id, $fields, $tableName= null) {
		return $this->query('UPDATE '.
		$this->getTableName($tableName).
		' SET '.$this->pairs($fields).
		$this->getWhereString($id, $tableName));
	}

	/**
	 * update a row or insert a new record if no previous row is found
	 *
	 * <code>
	 * $this->replace(array(
	 * 	'fooId'=>10,
	 *  'data1'=>20
	 * ));
	 * </code>
	 *
	 * @param mixed $id primary key of row to replace
	 * @param array $fields name=>value pairs of new values
	 * @param string $table replace from this table (if ommitted: use tablename of this model, including prefix)
	 */
	function replace($fields, $tableName= null) {
		return $this->query('REPLACE INTO '.
		$this->getTableName($tableName).
		' SET '.$this->pairs($fields));
	}

	/**
	 * tries to reduce all fields of the given table to basic datatypes
	 *
	 * @param string $tableName optional tablename to use
	 * @return array
	 */
	function & describe($tableName= null) {
		$tableName= $this->getTableName($tableName);

		$cacheUtil= getUtil('Cache');
		$cacheId= 'describe.'.$this->connection.'.'.$tableName;
		$data= $cacheUtil->read($cacheId, CacheUtility :: CM_FILE);
		if (false !== $data) {
			return $data;
		}

		$data= $this->dbo()->describe($tableName);
		$cacheUtil->write($cacheId, $data, MINUTE, CacheUtility :: CM_FILE);
		return $data;
	}

	/**
	 * checks the given values of the array match certain criterias
	 * @param array $params key/value pair. key is the name of the key inside the $what-array, value is a "VALID_" define (see above) or the name of a function that is given the string (should return bool wether the string validates)
	 * @param array $what the actual data
	 * @deprecated 31.04.2009
	 */
	function validate($params, $what) {
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
				if (!preg_match($how, $what[$param])) {
					return $how;
				}
			}
		}
		return true;
	}
}

/**
 * our own exception so user can react to connection-errors
 */
class DatabaseConnectException extends Exception {

}

/**
 * our own sql-error exception so user can react to bad queries
 */
class DatabaseErrorException extends Exception {

}