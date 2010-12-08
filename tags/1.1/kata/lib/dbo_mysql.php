<?php

/**
 * Contains a wrapper-class for mysql, so models can access mysql
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2008, mnt@codeninja.de
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata
 */

/**
 * this class is used by the model to access the database itself
 * @package kata
 */
class dbo_mysql {

	/**
	 * a copy of the matching db-config entry in config/database.php
	 * @var array
	 */
	public $dbconfig= null;

	/**
	 * a placeholder for any result the database returned
	 * @var array
	 */
	private $result= null;

	/**
	 * a placeholder for the database link needed for this database
	 * @var int
	 */
	private $link= null;

	/**
	 * are we already connected? used to connect to the database in the last possible moment to save unneeded connects
	 * @var boolean
	 */
	private $connected= false;

	/**
	 * an array that holds all queries and some relevant information about them if DEBUG>1
	 * @var array
	 */
	private $queries= array ();

	/**
	 * constants used to quote table and field names
	 *
	 */
	public $quoteLeft= '`';
	public $quoteRight= '`';

	/**
	 * connect to the database
	 */
	function connect() {
		$this->link= mysql_connect($this->dbconfig['host'], $this->dbconfig['login'], $this->dbconfig['password']);
		if (!$this->link) {
			throw new DatabaseConnectException("Could not connect to Database ".$this->dbconfig['host']);
		}
		if (!mysql_select_db($this->dbconfig['database'], $this->link)) {
			throw new DatabaseConnectException("Could not select Database ".$this->dbconfig['database']);
		}

		$this->connected= true;
	}
	function isConnected() {
		return $this->connected;
	}

	/**
	 * return the current link to the database, connect first if needed
	 */
	public function getLink() {
		if (!$this->connected) {
			$this->connect();
		}
		return $this->link;
	}
	public function setLink($l) {
		$this->link= $l;
		$this->connected= true;
	}

	/**
	 * execute this query
	 * @return mixed
	 */
	private function execute($sql) {
		if (!$this->connected) {
			$this->connect();
		}

		$start= microtime(true);
		$this->result= mysql_query($sql, $this->link);

		if (false === $this->result) {
			writeLog(mysql_error($this->link).': '.$sql, KATA_ERROR);
			throw new DatabaseErrorException(mysql_error($this->link));
		}
		if (DEBUG > 0) {
			$this->queries[]= array (
				kataGetLineInfo(),
				$sql,
				mysql_affected_rows($this->link),
				mysql_error($this->link),
				 (microtime(true) - $start).'sec'
			);
		}
	}

	/**
	 * unused right now, later possibly used by model to set right encoding
	 */
	function setEncoding($enc) {
		$this->execute('SET NAMES '.$enc);
		return $this->result;
	}

	/**
	 * return numbers of rows affected by last query
	 * @return int
	 */
	private function lastAffected() {
		if ($this->link) {
			return mysql_affected_rows($this->link);
		}
		return null;
	}

	/**
	 * return id of primary key of last insert
	 * @return int
	 */
	private function lastInsertId() {
		$id= mysql_insert_id($this->link);
		if ($id) {
			return $id;
		}
		$this->execute("SELECT LAST_INSERT_ID() as id");
		if ($this->result) {
			$dummy= mysql_result($this->result, 0);
			return $dummy;
		}
		$dummy= null;
		return $dummy;
	}

	/**
	 * return the result of the last query.
	 * @param mixed $idname if $idname is false keys are simply incrementing from 0, if $idname is string the key is the value of the column specified in the string
	 */
	private function & lastResult($idnames= false) {
		$result= array ();
		if (mysql_num_rows($this->result) > 0) {
			if ($idnames === false) {
				while ($row= mysql_fetch_assoc($this->result)) {
					$result[]= $row;
				}
			} else {
				while ($row= mysql_fetch_assoc($this->result)) {
					$current= & $result;
					foreach ($idnames as $idname) {
						if (!isset ($row[$idname])) {
							throw new InvalidArgumentException('Cant order result by a field thats not in the resultset (forgot to select it?)');
						}
						$current= & $current[$row[$idname]];
					}//foreach
					$current= $row;
				}//while fetch
			}//idnames
			return $result;
		}//rows>0
		return $result;
	}

	/**
	 * execute query and return useful data depending on query-type
	 *
	 * @param string $s sql-statement
	 * @param string $idname which field-value to use as the key of the returned array (false=dont care)
	 * @return array
	 */
	function & query($s, $idnames= false) {
		$s= trim($s);
		$this->execute($s);

		$what= strtolower(substr($s, 0, 6));
		$result= null;
		switch ($what) {
			case 'replac' :
			case 'update' :
			case 'delete' :
				$result= $this->lastAffected();
				return $result;
				break;

			case 'insert' :
				$result= $this->lastInsertId();
				return $result;
				break;

			case 'select' :
			case 'show t' :
				if (is_string($idnames)) {
					$idnames= array (
						$idnames
					);
				}
				$result= $this->lastResult($idnames);
				return $result;
				break;

			case 'trunca' :
				return $result;
				break;
		}

		throw new Exception('Dont know what to do with your query');
	}

	/**
	 * escape the given string so it can be safely appended to any sql
	 * @param string $sql string to escape
	 * @return string
	 */
	function escape($sql) {
		if (!$this->connected) {
			$this->connect();
		}
		return mysql_real_escape_string($sql, $this->link);
	}

	/**
	 * return sql needed to convert unix timestamp to datetime
	 * @param integer $t unixtime
	 * @return string
	*/
	function makeDateTime($t) {
		return 'FROM_UNIXTIME('.$t.')';
	}

	/**
	 * output any queries made, how long it took, the result and any errors if DEBUG>1
	 */
	function __destruct() {
		if (DEBUG > 0) {
			array_unshift($this->queries, array (
				'line',
				'',
				'affected',
				'error',
				'time'
			));
			kataDebugOutput($this->queries, true);
		}
		if ($this->connected) {
			mysql_close($this->link);
		}
	}

	private function getFieldSize($str) {
		$x1= strpos($str, '(');
		$x2= strpos($str, ')');
		if ((false !== $x1) && (false !== $x2)) {
			return substr($str, $x1 +1, $x2 - $x1 -1);
		}
		return 0;
	}

	/**
	 * build a sql-string that returns paged data
	 * @return string finished query
	 */
	function getPageQuery($sql, $page, $perPage) {
		return sprintf('%s LIMIT %d,%d', $sql, ($page -1) * $perPage, $perPage);
	}

	/**
	 * try to reduce the fields of given table to the basic types bool, unixdate, int, string, float, date, enum
	 *
	 * <code>example:
	 *
	 * Array
	 * (
	 *     [table] => test
	 *     [primary] => a
	 *     [cols] => Array
	 *         (
	 *             [a] => Array
	 *                 (
	 *                     [default] => CURRENT_TIMESTAMP
	 *                     [null] =>
	 *                     [unique] =>
	 *                     [length] => 0
	 *                     [type] => date
	 *                 )
	 *
	 *             [g] => Array
	 *                 (
	 *                     [default] =>
	 *                     [null] =>
	 *                     [unique] =>
	 *                     [length] => 0
	 *                     [type] => unsupported:time
	 *                 )
	 *
	 *             [j] => Array
	 *                 (
	 *                     [default] =>
	 *                     [null] =>
	 *                     [unique] =>
	 *                     [length] => 0
	 *                     [type] => enum
	 *                     [values] => Array
	 *                         (
	 *                             [0] => a
	 *                             [1] => B
	 *                             [2] => c
	 *                         )
	 *
	 *                 )
	 *
	 *         )
	 *
	 * )
	 * </code>
	 *
	 * @param string $tableName name of the table to analyze
	 * @return unknown
	 */
	function & describe($tableName) {
		$primaryKey= false;
		$desc= array ();
		$r= mysql_query("SHOW COLUMNS FROM ".$tableName, $this->getLink());
		if (false == $r) {
			throw new Exception('model: cant describe, missing rights?');
		}
		while ($row= mysql_fetch_assoc($r)) {
			$data= array ();
			$data['default']= empty ($row['Default']) ? false : $row['Default'];
			$data['null']= 'NO' != $row['Null'];
			$data['unique']= 'UNI' == $row['Key'];
			$data['length']= 0;

			if ('PRI' == $row['Key']) {
				$primaryKey= $row['Field'];
			}

			if ('tinyint(1)' == $row['Type']) {
				$data['type']= 'bool';
			} else
				if (strpos($row['Type'], 'int(') !== false) {
					if (strpos($row['Type'], 'int(11)') !== false) {
						$data['type']= 'unixdate';
					} else {
						$data['type']= 'int';
					}
					$data['length']= $this->getFieldSize($row['Type']);
				} else
					if (strpos($row['Type'], 'char(') !== false) {
						$data['type']= 'string';
						$data['length']= $this->getFieldSize($row['Type']);
					} else
						if (strpos($row['Type'], 'text') !== false) {
							$data['type']= 'text';
						} else
							if ('float' == $row['Type']) {
								$data['type']= 'float';
							} else
								if (('timestamp' == $row['Type']) || ('datetime' == $row['Type'])) {
									$data['type']= 'date';
								} else
									if (strpos($row['Type'], 'enum(') !== false) {
										$data['type']= 'enum';
										$temp= explode(',', $this->getFieldSize($row['Type'])); //TODO a bad idea (!)
										foreach ($temp as $n => & $v) {
											if ((strlen($v) > 2) && ("'" == substr($v, 0, 1)) && ("'" == substr($v, -1, 1))) {
												$v= substr($v, 1, strlen($v) - 2);
											}
										}
										$data['values']= $temp;
									} else {
										$data['type']= 'unsupported:'.$row['Type'];
									}

			$desc[$row['Field']]= $data;
		}

		$desc= array (
			'table' => str_replace(array (
				$this->quoteLeft,
				$this->quoteRight
			), '', $tableName),
			'primary' => $primaryKey,
			'cols' => $desc
		);
		return $desc;
	}
}