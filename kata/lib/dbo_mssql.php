<?php


/**
 * Contains a wrapper-class for mssql, so models can access mssql
 *
 * Kata - Lightweight MVC Framework <http://www.codeninja.de/>
 * Copyright 2007-2009 mnt@codeninja.de, gameforge ag
 *
 * Licensed under The GPL License
 * Redistributions of files must retain the above copyright notice.
 * @package kata_model
 */

/**
 * interface...
 */
require_once (ROOT.'lib'.DS.'dbo_interface.php');

/**
 * this class is used by the model to access the database itself
 * @package kata_model 
 * @author mnt@codeninja.de
 * @author marcel.boessendoerfer@gameforge.de
 */

class dbo_mssql implements dbo_interface {

	/**
	 * a copy of the matching db-config entry in config/database.php
	 * @var array
	 */
	private $dbconfig= null;

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
	 * an array that holds all queries and some relevant information about them if DEBUG
	 * @var array
	 */
	private $queries= array ();

	/**
	 * constants used to quote table and field names
	 *
	 */
	private $quoteLeft= '[';
	private $quoteRight= ']';

	/**
	 * an array that holds all valid SQL-Operators
	 * @var array
	 */
	private $validOperators= array (
		'=',
		'>',
		'<',
		'>=',
		'<=',
		'<>',
		'!=',
		'!<',
		'>!',
		'is null',
		'is not null',
		'between',
		'in',
		'not in',
		'like',
		'not like'
	);

	/**
	 * connect to the database
	 */
	function connect() {
		$this->link= mssql_connect($this->dbconfig['host'], $this->dbconfig['login'], $this->dbconfig['password']);
		if (!$this->link) {
			throw new DatabaseConnectException("Could not connect to Database ".$this->dbconfig['host']);
		}
		if (!empty ($this->dbconfig['database'])) {
			if (!mssql_select_db($this->dbconfig['database'], $this->link)) {
				throw new DatabaseConnectException("Could not select Database ".$this->dbconfig['database']);
			}
		}

		if (!empty ($this->dbconfig['encoding'])) {
			//TODO well... do semething $this->query("FOO '".$this->dbconfig['encoding']."'");
		}

		$this->connected= true;

		//freetds hack
		if (!function_exists("mssql_next_result")) {
			function mssql_next_result($res= null) {
				return false;
			}
		}
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

	/**
		 * inject db link into dbo
		 */
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
		$this->result= mssql_query($sql, $this->link);

		if (false === $this->result) {
			writeLog(mssql_get_last_message().': '.$sql, KATA_ERROR);
			throw new DatabaseErrorException(mssql_get_last_message());
		}
		if (DEBUG > 0) {
			$this->queries[]= array (
				kataGetLineInfo(),
				trim($sql),
				'',
				mssql_get_last_message(),
				 (microtime(true) - $start).'sec'
			);
		}
	}

	/**
	 * unused right now, later possibly used by model to set right encoding
	 */
	function setEncoding($enc) {
		//TODO
	}

	/**
	 * return numbers of rows affected by last query
	 * @return int
	 */
	private function lastAffected() {
		if ($this->link) {
			if (function_exists('mssql_rows_affected')) {
				return mssql_rows_affected($this->link);
			} else {
				$result= mssql_query("select @@rowcount as rows", $this->link);
				$rows= mssql_fetch_assoc($result);
				return $rows['rows'];
			}
		}
		$null= null;
		return $null;
	}

	/**
	 * return id of primary key of last insert
	 * @return int
	 */
	private function lastInsertId() {
		$this->execute("select SCOPE_IDENTITY() AS id");
		if ($this->result) {
			$res= mssql_fetch_assoc($this->result);
			if ($res) {
				$id= $res['id'];
				if ($this->result) {
					mssql_free_result($this->result);
				}
				return $id;
			}
		}
		return null;
	}

	/**
	 * return the result of the last query.
	 * @param mixed $idname if $idname is false keys are simply incrementing from 0, if $idname is string the key is the value of the column specified in the string
	 */
	private function & lastResult($idnames= false) {
		do {
			$result= array ();
			if (mssql_num_rows($this->result) > 0) {
				if ($idnames === false) {
					while ($row= mssql_fetch_assoc($this->result)) {
						$result[]= $row;
					}
				} else {
					while ($row= mssql_fetch_assoc($this->result)) {
						$current= & $result;
						foreach ($idnames as $idname) {
							if (!in_array($idname, array_keys($row))) {
								throw new InvalidArgumentException('Cant order result by a field thats not in the resultset (forgot to select it?)');
							}
							if ($row[$idname] === null) {
								$row[$idname]= 'null';
							}
							$current= & $current[$row[$idname]];
						}
						$current= $row;
					} //while
				} //idnames!=false
			} //num_rows>0
		} //do
		while (mssql_next_result($this->result));
		return $result;
	}
	/**
	 * REPLACE works like INSERT,
	 * except that if an old row in the table has the same value as a new row for a PRIMARY KEY or a UNIQUE index,
	 * the old row is deleted before the new row is inserted
	 *
	 * @param string $tableName replace from this table
	 * @param array $fields name=>value pairs of new values
	 * @param string $pairs enquoted names to escaped pairs z.B.[name]='value'
	 * @return int modified rows.
	 */
	function replace($tableName, $fields, $pairs) {
		throw new Exception('Not easily supportable on MSSQL. Direct your thanks for this to Microsoft.');
	}

	/**
	 * execute query and return useful data depending on query-type
	 *
	 * @param string $s sql-statement
	 * @param string $idname which field-value to use as the key of the returned array (false=dont care)
	 * @return array
	 */
	function & query($s, $idnames= false, $fields= false) {
		$result= null;
		switch ($this->getSqlCommand($s)) {
			case 'update' :
			case 'delete' :
				$this->execute($s);
				$return= $this->lastAffected();
				return $return;
				break;
			case 'insert' :
				$this->execute($s);
				$return= $this->lastInsertId();
				return $return;
				break;
			case 'select' :
			case '/*select*/if' : //paging
				if (is_string($idnames)) {
					$idnames= array (
						$idnames
					);
				}
				$this->execute($s);
				$result= $this->lastResult($idnames, $fields);
				return $result;
				break;
			case 'truncate' :
				$this->execute($s);
				break;
			case 'set':
				break;
			default :
				throw new DatabaseUnshureWhatToReturn($s);
				break;
		}
		return $result;
	}

	/**
	 * escape the given string so it can be safely appended to any sql
	 * @param string $sql string to escape
	 * @return string
	 */
	function escape($sql) {
		return str_replace("'", "''", $sql); //seems odd but in mssql a single ' can be escaped by another
	}

	/**
	 * output any queries made, how long it took, the result and any errors if DEBUG
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
			mssql_close($this->link);
		}
	}

	/**
	 * return the Sql-Command of given Query
	 * @param string $sql query
	 * @return string Sql-Command
	 */
	private function getSqlCommand($sql) {
		$sql= str_replace("(", " ", $sql);
		$Sqlparts= explode(" ", trim($sql));
		return strtolower($Sqlparts[0]);
	}

	/**
	 * build a sql-string that returns first matching row
	 * @param string $sql query
	 * @param string $perPage expression
	 * @return string (limited) Query
	 */
	function getFirstRowQuery($sql, $perPage) {
		//TODO UNION,EXCEPT,INTERSECT... not implemented, mostly not supported anyway
		$command= $this->getSqlCommand($sql);
		$validTopComands= array (
			"select",
			"insert",
			"update",
			"delete",
			"merge"
		);
		//set TOP after first Command
		if (in_array($command, $validTopComands)) {
			$first= mb_strpos(strtolower($sql), $command);
			$firstPart= substr($sql, 0, $first);
			$secondPart= substr($sql, ($first +strlen($command)));
			return $firstPart.$command." TOP(".$perPage.")".$secondPart;
		}
		return $sql;
	}

	/**
	 * build a sql-string that returns paged data
	 * every computed output has to be named !!! so 'max(x)' has to be 'max(x) as maxX' or something like that...
	 *
	 * @see getPageQuery Interface
	 * @param boolean $orderd true is depreacated fliping TOPS
	 * @return string finished query
	 */
	function getPageQuery($sql, $page, $perPage) {
		//IF There is no IDENTITY FIELD we can numbering Rows with temp Table
		//IF There is an IDENTITY FIELD we have to execute the much slower EXCEPT Query
		//Also we know there is an IDENTITY FIELD we can't use it, because we do not! know which column it is !
		$command= $this->getSqlCommand($sql);
		if ($command != "select") {
			throw new InvalidArgumentException('paging is not possible for given query');
			return $sql;
		}
		$fastQuery= $this->getFirstRowQuery($sql, 1);
		$fastInsertQuery= 'IF OBJECT_ID(\'tempdb..#temp\') IS NOT NULL DROP TABLE #temp;SELECT * INTO #temp FROM ('.$fastQuery.') as a';
		$this->execute($fastInsertQuery);
		$ID= $this->lastInsertId();
		if ($ID === null) {
			$fastQuery= $this->getFirstRowQuery($sql, $page * $perPage);
			$tmptable= '/*SELECT*/IF OBJECT_ID(\'tempdb..#table\') IS NOT NULL DROP TABLE #table;SELECT IDENTITY(int,1,1) as tempRowNumID,* INTO #table FROM ('.$fastQuery.') as a;';
			$tmptableAndQuery= $tmptable.'SELECT * FROM #table where tempRowNumID between '. (($page -1) * $perPage +1).' AND '. (($page) * $perPage);
			return $tmptableAndQuery;
		} else {
			$topPages= $this->getFirstRowQuery($sql, $page * $perPage);
			$lastPages= $this->getFirstRowQuery($sql, ($page -1) * $perPage);
			$Query= 'SELECT * FROM ('.$topPages.') as a EXCEPT SELECT * FROM ('.$lastPages.') as a';
			return $Query;
		}
	}
	/**
	 * return the sql needed to convert a unix timestamp to datetime
	 * @param integer $t unixtime
	 * @return string
	 */
	function makeDateTime($t) {
		//may lie to you: mssql does not calculate summertime
		return "CONVERT(char(20),dateadd(ss,".$t."+DATEDIFF(ss, GetUtcDate(), GetDate()),'1970-01-01 00:00:00'),120)";
	}
	/**
	 * Remove sql_quotes from string
	 * @param string $s
	 * @return string String without sql_quotes
	 */
	protected function dequote($s) {
		return str_replace($this->quoteLeft, "", str_replace($this->quoteRight, "", $s));
	}

	/**
	 * try to reduce the fields of given table to the basic types bool, unixdate, int, string, float, date, enum
	 *
	 * <code>example:
	 *
	Array
	 * (
	 *     [table] => test
	 *     [primary] => a
	 *     [cols] => Array
	 *         (
	 *             [a] => Array
	 *                 (
	 *                     [default] => CURRENT_TIMESTAMP
	 *                     [null] =>
	 *                     [length] => 0
	 *                     [type] => date
	 *                 )
	 *
	 *             [g] => Array
	 *                 (
	 *                     [default] =>
	 *                     [null] =>
	 *                     [length] => 0
	 *                     [type] => unsupported:time
	 *                 )
	 *         )
	 *
	 * )
	 * </code>
	 *
	 * @param string $tableName name of the table to analyze
	 * @return unknown
	 */
	function & describe($tableName) {
		$primaryKey= array ();
		$identity= null;
		$desc= array ();
		$cols= array ();
		$tableName= $this->dequote($tableName);
		/*possibly incomplete*/
		$sql= "Select a.COLUMN_NAME,a.[IS_NULLABLE],a.[COLUMN_DEFAULT],a.[DATA_TYPE],a.[CHARACTER_MAXIMUM_LENGTH],a.[NUMERIC_PRECISION],b.[CONSTRAINT_NAME],COLUMNPROPERTY(OBJECT_ID('".$tableName."'),a.COLUMN_NAME, 'IsIdentity') as [identity]
								From INFORMATION_SCHEMA.COLUMNS a left join INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE as b on (a.COLUMN_NAME = b.COLUMN_NAME AND a.TABLE_NAME=b.TABLE_NAME)
								where a.TABLE_NAME='".$tableName."'";
		/*tested this do it ,cause CONSTRAINT_NAME can be defined by user!(fehlender Beweis für:"unkorrelierte Subqueries werden nur einmal ausgeführt")
		$sql = "Select a.COLUMN_NAME,[IS_NULLABLE],[COLUMN_DEFAULT],[DATA_TYPE],[CHARACTER_MAXIMUM_LENGTH],[NUMERIC_PRECISION],[CONSTRAINT_NAME],[CONSTRAINT_TYPE],a.COLUMN_NAME, 'IsIdentity') as [identity]
						from INFORMATION_SCHEMA.COLUMNS a left join
							(SELECT a.COLUMN_NAME,b.CONSTRAINT_TYPE,b.CONSTRAINT_NAME
							from INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE as a,INFORMATION_SCHEMA.TABLE_CONSTRAINTS as b
							where a.TABLE_NAME='".$tableName."' AND a.TABLE_CATALOG = db_name()
							AND b.TABLE_NAME='".$tableName."' AND b.TABLE_CATALOG = db_name()
							AND b.CONSTRAINT_NAME=a.CONSTRAINT_NAME
							)as b on (a.COLUMN_NAME = b.COLUMN_NAME)
						where a.TABLE_NAME='".$tableName."' AND a.TABLE_CATALOG =db_name()";
		*/
		$r= mssql_query($sql, $this->getLink());
		if (false === $r) {
			throw new Exception('model: cant describe, missing rights?');
		}
		$noResult= true;
		while ($row= mssql_fetch_assoc($r)) {
			$noResult= false;
			$data= array ();
			$data['default']= empty ($row['COLUMN_DEFAULT']) ? false : $row['COLUMN_DEFAULT'];
			$data['null']= 'NO' != $row['IS_NULLABLE'];
			$data['length']= 0;
			if ($row['identity'] == 1) {
				$identity= $row['COLUMN_NAME'];
			}
			/*deprecated
			if('UNIQUE' == $row['CONSTRAINT_TYPE'] ){
				if(!isset($uniqueKeys[$row['CONSTRAINT_NAME']])){
					$uniqueKeys[$row['CONSTRAINT_NAME']] = array();
				}
				$uniqueKeys[$row['CONSTRAINT_NAME']][] = $row['COLUMN_NAME'];
			}
			if ('PRIMARY KEY' == $row['CONSTRAINT_TYPE']) {
				$primaryKey[] = $row['COLUMN_NAME'];
			}
			*/
			//keys
			$data['key']= null;
			if (isset ($row['CONSTRAINT_NAME'])) {
				$key= substr($row['CONSTRAINT_NAME'], 0, 2);
				if ($key == 'PK') {
					$primaryKey[]= $row['COLUMN_NAME'];
					$data['key']= 'PRI';
				} else
					if ($key == 'UQ') {
						$data['key']= 'UNI';
					}
			}
			//types
			switch ($row['DATA_TYPE']) {
				case 'bit' :
					$data['type']= 'bool';
					$data['length']= $row['NUMERIC_PRECISION'];
					break;
				case 'bigint' :
				case 'int' :
				case 'smallint' :
				case 'tinyint' :
					$data['length']= $row['NUMERIC_PRECISION'];
					$data['type']= 'int';
					break;
				case 'char' :
				case 'varchar' :
					$data['length']= $row['CHARACTER_MAXIMUM_LENGTH'];
					$data['type']= 'string';
					break;
				case 'text' :
					$data['type']= 'text';
					break;
				case 'float' :
				case 'real' :
					$data['length']= $row['NUMERIC_PRECISION'];
					$data['type']= 'float';
					break;
				case 'date' :
				case 'datetime' :
				case 'datetime2' :
				case 'smalldatetime' :
				case 'datetimeoffset' :
				case 'time' :
					$data['type']= 'date';
			}
			$cols[$row['COLUMN_NAME']]= $data;
		}

		if ($noResult === true) {
			throw new Exception('table does not exists in selected Database');
		}
		$desc= array (
			'table' => str_replace(array (
				$this->quoteLeft,
				$this->quoteRight
			), '', $tableName),
			'primary' => $primaryKey,
			'identity' => $identity,
			'cols' => $cols
		);
		return $desc;
	}

	/**
	 * a copy of the matching db-config entry in config/database.php
	 * @param string $what spezifies what to get ... null=complete config array
	 * @return array|string
	 */
	function getConfig($what= null) {
		if (!empty ($what)) {
			return (isset ($this->dbconfig[$what])) ? $this->dbconfig[$what] : '';
		}
		return $this->dbconfig;
	}

	/**
	 * set db-config entry
	 * @param $array $config
	 */
	function setConfig($config) {
		if (empty ($this->dbconfig)) {
			$this->dbconfig= $config;
		}
	}

	/**
	 * used to quote table and field names
	 * @param string $s string to enquote;
	 * @return string enquoted string
	 */
	function quoteName($s) {
		return $this->quoteLeft.$s.$this->quoteRight;
	}

	/**
	 * checks if given operator is valid
	 * @param string $operator
	 * @return boolean
	 */
	function isValidOperator($operator) {
		if (empty ($operator)) {
			return false;
		}
		return in_array($operator, $this->validOperators);
	}
}