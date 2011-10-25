<?php
require_once ('lib'.
DIRECTORY_SEPARATOR.'kataTestBase.simpleTest.class.php');

class ModelTest extends kataTestBaseClass {
	private $myModel= null;
	private $defaultSQL= "mysql"; //mssql,mysql hier kann man zwischen den beiden Modi mysql und mssql wechseln.

	function __construct($SQL= '') {
		parent :: __construct();
		if (strtolower($SQL) == "mysql") {
			$this->defaultSQL= "mysql";
		} else
			if (strtolower($SQL) == "mssql") {
				$this->defaultSQL= "mssql";
			}
	}

	/* Differences between
	 * MySQL 												MSSQL
	 * show Tables										 ->	|Select Table_Name from INFORMATION_SCHEMA.TABLES;
	 *
	 * SELECT TABLE_Name FROM INFORMATION_SCHEMA.TABLES <-	|Select Table_Name from INFORMATION_SCHEMA.TABLES;
	 * where TABLE_SCHEMA=DATABASE()						|
	 *
	 * Drop Table if exists xy							<->	|IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "xy") Drop Table xy
	 *
	 * Replace INTO xy SET x=1,y=2						 ->	|nichts ab 2008 kann MERGE verwendet werden aber !! man muss die Key Felder identifizieren, dann ein for each Key Delete where key = xyz, dann ein insert into...
	 *
	 * SELECT LAST_INSERT_ID()							<->	|select scope_identity()
	 *
	 *
	 * Select x from xy LIMIT z							<->	|Select TOP z x from xy
	 * Select x from xy LIMIT z,z2						 -> |Select TOP z from xy where <ID> not in (Select Top z2 <ID> from xy)
	 *
	 * VALUE TRUE										 -> |1
	 * VALUE FALSE										 -> |0
	 * TYPE BOOLEAN										 -> |BIT
	 * FUNCTION DATABASE()								<-> |db_name()
	 * Operator <=>										 -> |nothing
	 * Operator ISNULL(x)								 -> |x IS NULL
	 * ATTRIBUTE AUTO_INCREMENT							<->	|IDENTITY
	 *
	 *
	 * DEFAULT SETTINGS
	 * erstellte Felder dürfen null entahlten				| dürfen nicht null enthalten
	 *
	 * autoincrement Wert kann manuell geändert werden muss | autoincrement kann nicht manuell geändert werden,
	 * aber nicht											| set IDENTITY_INSERT idTable ON
	 *														| muss der autoincrement Wert explizit angegeben werden!
	 **/
	function identyInsertON($table= 'idTable') {
		if ($this->defaultSQL == "mssql") {
			$this->myModel->query('SET IDENTITY_INSERT '.$table.' ON');
		}
	}

	function identyInsertOFF($table= 'idTable') {
		if ($this->defaultSQL == "mssql") {
			$this->myModel->query('SET IDENTITY_INSERT '.$table.' OFF');
		}
	}
	function getLink() {
		return $this->myModel->getLink();
	}

	function initializeMYSQL() {
		$link= $this->getLink();
		mysql_query('Drop Table if exists xy', $link);
		mysql_query('Drop Table if exists xz', $link);
		mysql_query('Drop Table if exists idTable', $link);
		mysql_query('Drop Table if exists model', $link);
		mysql_query('Drop Table if exists multi', $link);
		mysql_query('Create Table xy (x int null, y int null,bool boolean null)', $link);
		mysql_query('Create Table idTable (PK int NOT NULL AUTO_INCREMENT,name varchar(10),number int null,Primary key(PK),Unique key(number))', $link);
		mysql_query('Create Table model (x int, y int,bool boolean)', $link);
		mysql_query('Create Table multi (x tinyint , y int not null AUTO_INCREMENT,name varchar(10) null,name2 char(20) not null,Primary key(x,y),Unique key(name2),Unique key(name))', $link);

		mysql_query("USE katatest");
		mysql_query("DROP TABLE IF EXISTS tableno", $link);
		mysql_query("CREATE TABLE `tableno` (`id` INT NOT NULL DEFAULT '0') ENGINE = MYISAM;", $link);
		mysql_query("INSERT INTO `tableno` ( `id` ) VALUES ('1');", $link);
		mysql_query("USE katatest2");
		mysql_query("DROP TABLE IF EXISTS tableno", $link);
		mysql_query("CREATE TABLE `tableno` (`id` INT NOT NULL DEFAULT '0') ENGINE = MYISAM;", $link);
		mysql_query("INSERT INTO `tableno` ( `id` ) VALUES ('2');", $link);
		mysql_query("USE katatest3");
		mysql_query("DROP TABLE IF EXISTS tableno", $link);
		mysql_query("CREATE TABLE `tableno` (`id` INT NOT NULL DEFAULT '0') ENGINE = MYISAM;", $link);
		mysql_query("INSERT INTO `tableno` ( `id` ) VALUES ('3');", $link);
	}

	function initializeMSSQL() {
		$link= $this->myModel->getLink();
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "xy") Drop Table xy', $link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "zy") Drop Table zy', $link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "xz") Drop Table xz', $link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "idTable") Drop Table idTable', $link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "model") Drop Table model', $link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "multi") Drop Table multi', $link);
		mssql_query('Create Table xy (x int NULL, y int NULL,bool bit NULL)', $link);
		mssql_query('Create Table idTable (PK int NOT NULL IDENTITY PRIMARY KEY,name varchar(10) NULL,number int null UNIQUE)', $link);
		mssql_query('Create Table model (x int NULL, y int NULL,bool bit NULL)', $link);
		mssql_query('Create Table multi (x tinyint not NULL, y int not NULL IDENTITY,name varchar(10) null,name2 char(20) not null,PRIMARY KEY(x,y),UNIQUE (name),UNIQUE (name2))', $link);
	}

	function initializeDB() {
		$this->createTmpFile('config'.DS.'database.php', '<?
		class DATABASE_CONFIG
		{
		 //mssql test
		  public static $mssql = array(
		        \'driver\' => \'mssql\',
		        \'host\' => \'79.110.88.9:2526\',
		        \'login\' => \'mfeldkamp\',
		        \'password\' => \'fg327h34\',
		        \'database\' => \'katatest\',
		        \'prefix\' => \'\'
		  );
		  public static $mysql = array(
		        \'driver\' => \'mysql\',
		        \'host\' => \'localhost\',
		        \'login\' => \'katatest\',
		        \'password\' => \'6vxnr2Jn\',
		        \'database\' => \'katatest\',
		        \'prefix\' => \'\'
		  );
		  public static $second = array(
		        \'driver\' => \'mysql\',
		        \'host\' => \'localhost\',
		        \'login\' => \'katatest\',
		        \'password\' => \'6vxnr2Jn\',
		        \'database\' => \'katatest2\',
		        \'prefix\' => \'pre_\',
		        \'encoding\' => \'\'
		  );
		  public static $third = array(
		        \'driver\' => \'mysql\',
		        \'host\' => \'\',
		        \'login\' => \'\',
		        \'password\' => \'\',
		        \'database\' => \'\',
		        \'prefix\' => \'\',
		        \'encoding\' => \'\'
		  );

		 public static $conn1 = array(
		         \'driver\' => \'mysql\',
		         \'host\' => \'localhost\',
		         \'login\' => \'katatest\',
		         \'password\' => \'6vxnr2Jn\',
		         \'database\' => \'katatest\',
		         \'prefix\' => \'\'
		 );

		  public static $conn2 = array(
		          \'driver\' => \'mysql\',
		          \'host\' => \'localhost\',
		          \'login\' => \'katatest\',
		          \'password\' => \'6vxnr2Jn\',
		          \'database\' => \'katatest2\',
		          \'prefix\' => \'\',
		          \'encoding\' => \'\'
		  );
		  public static $conn3 = array(
		          \'driver\' => \'mysql\',
		          \'host\' => \'localhost\',
		          \'login\' => \'katatest\',
		          \'password\' => \'6vxnr2Jn\',
		          \'database\' => \'katatest3\',
		          \'prefix\' => \'\',
		          \'encoding\' => \'\'
		  );
		}');

		$this->myModel->changeConnection($this->defaultSQL);
		if ($this->defaultSQL == "mysql") {
			$this->initializeMYSQL();
		} else
			if ($this->defaultSQL == "mssql") {
				$this->initializeMSSQL();
			}
	}

	function quoteName($value) {
		return $this->myModel->quoteName($value);
	}

	function testInitialize() {
		//entfernt die Coverage Berechnung des Startens von den in den einzelnen Funktionen tatsächlich benutzten...
		//sonst hat erste Funktion mehr Coverage als eigentlich verbraucht.
		//MNT drauf geschissen.
		$this->bootstrapKata();
		$this->myModel= new Model;
		$this->initializeDB();
		$result= $this->myModel->getConfig();
		$this->assertEqual($result['database'], 'katatest');
		$this->assertTrue(!empty ($this->myModel->connection));
		$this->bedingungExpectation= array (
			"empty1" => array (
				"helper" => "",
				"where" => "1=1"
			),
			"empty2" => array (
				"helper" => "",
				"where" => "1=1"
			),
			"empty3" => array (
				"helper" => "",
				"where" => "1=1"
			),
			//"id"=>array("helper"=>$this->quoteName("PK")." = '1'"),
	"null3" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." is null "
			),
			//"and1" =>array("helper"=>$this->quoteName("PK")." = 'x' AND ".$this->quoteName("PK")." = 'y'"),
	"and2" => array (
				"delete" => 0,
				"helper" => $this->quoteName("x")." = '1' AND ".$this->quoteName("y")." = '2'"
			),
			//"or1" =>array("helper"=>$this->quoteName("PK")." = 'x' OR ".$this->quoteName("PK")." = 'y'"),
	"or2" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." = '1' OR ".$this->quoteName("y")." = '2'"
			),
			//"or3" =>array("helper"=>$this->quoteName("x")." = '2' OR ".$this->quoteName("x")." = '1'"),
	"encapsulatedAND" => array (
				"delete" => 0,
				"helper" => $this->quoteName("x")." = '1' AND  ( ".$this->quoteName("x")." = '2' ) "
			),
			"encapsulatedOR" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." = '1' OR  ( ".$this->quoteName("x")." = '2' ) "
			),
			"encapsulatedComplex" => array (
				"delete" => 2,
				"helper" => " ( ".$this->quoteName("x")." = '1' )  OR  ( ".$this->quoteName("x")." = '2' ) "
			),
			"operatorGreater" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." > '1'"
			),
			"operatorSmaller" => array (
				"delete" => 0,
				"helper" => $this->quoteName("x")." < '1'"
			),
			"operatorGreaterEqual" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." >= '1'"
			),
			"operatorSmallerEqual" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." <= '1'"
			),
			"operatorUnEqual1" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." <> '1'"
			),
			"operatorUnEqual2" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." != '1'"
			),
			"operatorLike" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." like '1'"
			),
			"operatorLike2" => array (
				"delete" => 0,
				"helper" => $this->quoteName("x")." like '4%0'"
			),
			"operatorInArray" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." in ( '1' , '2' )"
			),
			"operatorNotInArray" => array (
				"delete" => 0,
				"helper" => $this->quoteName("x")." not in ( '1' , '2' )"
			),
			"operatorBetween" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." between '1' and '2'"
			),
			"true" => array (
				"delete" => 1,
				"helper" => $this->quoteName("bool")." = '1'"
			),
			"false" => array (
				"delete" => 2,
				"helper" => $this->quoteName("bool")." = '0'"
			),
			"all" => array (
				"delete" => 3,
				"helper" => "1=1"
			),
			"none" => array (
				"delete" => 0,
				"helper" => "1=0"
			),
			"operatorEqual" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." = '1'"
			),
			"operatorIsNull1" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." is null "
			),
			"operatorIsNull2" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." is null "
			),
			"operatorIsNull3" => array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." is null "
			),
			"operatorIsNotNull1" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." is not null "
			),
			"operatorIsNotNull2" => array (
				"delete" => 2,
				"helper" => $this->quoteName("x")." is not null "
			)
		);
		//spezial HACK eigentlich passt das nicht not in ist kein null sensitiver Operator
		if ($this->defaultSQL == 'mssql') {
			$this->bedingungExpectation["operatorNotInArray"]= array (
				"delete" => 1,
				"helper" => $this->quoteName("x")." not in ( '1' , '2' )"
			);
		}
	}

	public $bedingungen= array (
		"empty1" => null,
		"empty2" => "",
		"empty3" => array (),
		//"id"=>1,
	"null3" => array (
			"x" => null
		),
		//"and1" =>array("x","y"),
	"and2" => array (
			"x" => "1",
			"y" => "2"
		),
		//"or1" =>array("x","or","y"),
	"or2" => array (
			"x" => "1",
			"or",
			"y" => "2"
		),
		//"or3" =>array("x"=>"2","or","x"=>"1"),
	"encapsulatedAND" => array (
			"x" => "1",
			array (
				"x" => "2"
			)
		),
		"encapsulatedOR" => array (
			"x" => "1",
			"or",
			array (
				"x" => "2"
			)
		),
		"encapsulatedComplex" => array (
			array (
				"x" => "1"
			),
			"or",
			array (
				"x" => "2"
			)
		),
		"operatorGreater" => array (
			"x >" => 1
		),
		"operatorSmaller" => array (
			"x <" => 1
		),
		"operatorGreaterEqual" => array (
			"x >=" => 1
		),
		"operatorSmallerEqual" => array (
			"x <=" => 1
		),
		"operatorUnEqual1" => array (
			"x <>" => 1
		),
		"operatorUnEqual2" => array (
			"x !=" => 1
		),
		"operatorLike" => array (
			"x like" => 1
		),
		"operatorLike2" => array (
			"x like" => "4%0"
		),
		"operatorInArray" => array (
			"x in" => array (
				1,
				2
			)
		),
		"operatorNotInArray" => array (
			"x not in" => array (
				1,
				2
			)
		),
		"operatorBetween" => array (
			"x between" => array (
				1,
				2
			)
		),
#		"true" => array (
#			"bool" => true
#		),
#		"false" => array (
#			"bool" => false
#		),
#		"all" => true,
#		"none" => false,
		"operatorEqual" => array (
			"x =" => 1
		),
		"operatorIsNull1" => array (
			"x IS" => null
		),
		"operatorIsNull2" => array (
			"x IS Null" => null
		),
		"operatorIsNull3" => array (
			"x IS Null" => 'blub'
		),
		"operatorIsNotNull1" => array (
			"x IS NOT" => null
		),
		"operatorIsNotNull2" => array (
			"x IS NOT NULL" => null
		)
	);
	public $bedingungExpectation= array ();

	function testCreateExceptionEmpty() {
		$this->sendMessage("<hr>");
		$this->expectException("InvalidArgumentException");
		$this->myModel->create();
	}


	function testCreateExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->create('');
	}


	function testCreateExceptionEmptyArray() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->create(array ());
	}


	function testGetConfig() {
		$this->sendMessage("<hr>");
		$this->myModel->changeConnection("mysql");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest");
		$this->myModel->changeConnection("mssql");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mssql");
		$this->assertEqual($config['database'], "katatest");
		$this->myModel->changeConnection("second");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest2");
		$this->myModel->changeConnection("mysql");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest");
	}

	function testChangeConnection() {//mnt
		if ($this->defaultSQL!='mysql') {
			return;
		}
		$this->sendMessage("<hr>");
		$oldConnection = $this->myModel->getConnectionName();

		$this->myModel->changeConnection('conn2');
		$this->assertFalse($this->myModel->dbo()->isConnected());
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 2);
		unset($data);
		$this->myModel->changeConnection('conn3');
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 3);
		unset($data);
		$this->myModel->changeConnection('conn1');
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 1);
		unset($data);
		$this->myModel->changeConnection('conn3');
		$this->assertTrue($this->myModel->dbo()->isConnected());
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 3);
		unset($data);
		$this->myModel->changeConnection('conn1');
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 1);
		unset($data);
		$this->myModel->changeConnection('conn1');
		$data = $this->myModel->find('all',array(),'tableno');
		$this->assertTrue(isset($data[0]['id']));
		$this->assertEqual($data[0]['id'], 1);

		$this->myModel->changeConnection($oldConnection);
	}

	function testGetLink() {
		$this->sendMessage("<hr>");

		$this->myModel->changeConnection("mysql");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest");
		$this->assertEqual($this->myModel->getLink(),$this->myModel->dbo()->getLink());

		$this->myModel->changeConnection("second");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest2");
		$this->assertEqual($this->myModel->getLink(),$this->myModel->dbo()->getLink());

		$this->myModel->changeConnection("mysql");
		$config= $this->myModel->getConfig();
		$this->assertEqual($config['driver'], "mysql");
		$this->assertEqual($config['database'], "katatest");
		$this->assertEqual($this->myModel->getLink(),$this->myModel->dbo()->getLink());

		$this->myModel->changeConnection("mysql");
	}


	function testCreateParam() {
		//reset Tables
		$this->myModel->query("truncate table model");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("truncate table xy");
		$this->myModel->useTable= false;
		//test Create mit definierter Tabelle
		$return= $this->myModel->create(array (
			"x" => 1,
			"y" => 1
		));
		$this->assertEqual(0, $return);
		$return= $this->myModel->create(array (
			"x" => 2,
			"y" => 2
		));
		$this->assertEqual(0, $return);
		$return= $this->myModel->query("Select * from model");
		$this->assertEqual(2, count($return));
		//test Create mit nicht definierter Tabelle
		$this->myModel->useTable= "xy";
		$return= $this->myModel->create(array (
			"x" => 1,
			"y" => 1
		));
		$this->assertEqual(0, $return);
		$this->myModel->create(array (
			"x" => 2,
			"y" => 2
		));
		$return= $this->myModel->query("Select * from xy");
		$this->assertEqual(2, count($return));
		//test Create mit NULL Werten
		$this->myModel->query("truncate table xy");
		$return= $this->myModel->create(array (
			"x" => null,
			"y" => 2
		));
		$return= $this->myModel->query("Select * from xy");
		$this->assertEqual(1, count($return));
		$return= $this->myModel->query("Select * from xy where x IS NULL");
		$this->assertEqual(1, count($return));
		//test Create auf autoincrement Tabelle
		$return= $this->myModel->create(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->create(array (
			"name" => "Hans",
			"Number" => 6
		), 'idTable');
		$this->assertEqual(2, $return);
		$this->identyInsertON();
		$return= $this->myModel->create(array (
			"PK" => 8,
			"name" => "Hans",
			"Number" => 7
		), 'idTable');
		$this->identyInsertOFF();
		$this->assertEqual(8, $return);
	}


	function testCreateExceptionDublicate() {
		$this->expectException("DatabaseErrorException");
		$this->myModel->create(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
	}

	function testDeleteParams() {
		$this->sendMessage("<hr>");
		$this->myModel->useTable= "idTable";
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("insert into idTable (name,number) Values ('Hans',1)");
		$row2= $this->myModel->delete(array (
			"name like" => "%an%"
		), "idTable");
		$this->assertEqual($row2, 1);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$this->myModel->query("truncate table xy");
				$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
				$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
				$this->myModel->query("insert into xy (y,bool) values (4,0)");
				//$this->sendMessage($this->myModel->query("Select * from xy"));
				$this->sendMessage($bedingung);
				$string= $this->myModel->delete($bedingung, "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				//$this->sendMessage(count($this->myModel->query("Select * from xy")));
				//$this->sendMessage($sql);
				//delete from xy where x not in (1,2) bugged bei null und dieser mssql Version
				$this->assertEqual($string2, $string);
			}
		}
		$this->identyInsertON();
		$this->myModel->query("truncate table idTable");
		$this->myModel->create(array (
			"PK" => 8,
			"name" => "Hubert",
			"number" => 7
		), 'idTable');
		$this->identyInsertOFF();
		$this->assertEqual(1, $this->myModel->delete(array (
			"PK" => 8
		)));
		//TODO Einheitlich halten empty Anweisung heist ohne Condition also alles Überschreiben
		/*
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return = $this->myModel->delete(null);
		$this->assertEqual($return,3);
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return = $this->myModel->delete();
		$this->assertEqual($return,3);
		*/
	}


	function testUpdateExceptionEmpty() {
		$this->sendMessage("<hr>");
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$this->expectException("InvalidArgumentException");
		$this->myModel->update(array (
			"y" => 4
		), null);

	}


	function testUpdateExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->update(array (
			"y" => 4
		), '');
	}


	function testUpdateExceptionEmptyArray() {
		//TODO Einheitlich halten
		$this->expectException("InvalidArgumentException");
		$this->myModel->update(array (
			"y" => 4
		), array ());
	}

	function testUpdateParam() {
		$this->myModel->query("truncate table model");
		$this->myModel->useTable= false;
		$this->myModel->query("insert into model (y) values (4)");
		$return= $this->myModel->update(array (
			"y" => 4
		), array (
			"x" => 1,
			"y" => 4
		));
		$this->assertEqual(1, $return);
		$result1= $this->myModel->query("Select * from model");
		$this->assertEqual(1, count($result1));
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (y) values (4)");
		$result1= $this->myModel->query("Select * from xy");
		$return= $this->myModel->update(array (
			"y" => 4
		), array (
			"x" => 1,
			"y" => 4
		));
		$this->assertEqual(1, $return);
		$this->myModel->update(array (
			"y" => 4
		), array (
			"x" => null,
			"y" => 4
		));
		$result2= $this->myModel->query("Select * from xy");
		$this->assertIdentical($result1, $result2);
		$return= $this->myModel->query("Select * from xy where x IS NULL");
		$this->assertEqual(1, count($return));
		foreach ($this->bedingungen as $value => $bedingung) {
			$this->sendMessage($bedingung);
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$this->myModel->query("truncate table xy");
				$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
				$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
				$this->myModel->query("insert into xy (y,bool) values (4,0)");
				$string= $this->myModel->update($bedingung, array (
					"y" => 5
				), "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				$this->assertEqual($string2, $string);
			}
		}
		$this->identyInsertON();
		$this->myModel->query("truncate table idTable");
		$this->myModel->create(array (
			"PK" => 8,
			"name" => "Hubert",
			"number" => 7
		), 'idTable');
		$return= $this->myModel->update(array (
			"PK" => 8
		), array (
			"name" => "Hans"
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->query("Select * from idTable");
		$this->assertEqual(count($return), 1);
		$this->identyInsertOFF();

		//TODO einheitlich halten keine Condition heist ohne where bedingung also alles.
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$a= $this->myModel->query("Select * from xy where x is null");
		$return= $this->myModel->update(true, array (
			"x" => 1
		));
		//mysql updated nur Zeilen die es updaten muss... mssql updated alle Zeilen
		if ($this->defaultSQL == 'mssql') {
			$this->assertEqual(3, $return);
		} else
			if ($this->defaultSQL == 'mysql') {
				$this->assertEqual(2, $return);
			}
		$b= $this->myModel->query("Select * from xy where x is null");
		$this->assertNotEqual($a, $b);

	}


	function testReplaceExceptionEmpty() {
		$this->sendMessage("<hr>");
		$this->expectException("InvalidArgumentException");
		$this->myModel->replace();
	}


	function testReplaceExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->replace('');
	}


	function testReplaceExceptionEmptyArray() {
		$this->expectException("InvalidArgumentException");
		//TODO Einheitlich halten
		$this->myModel->replace(array ());
	}


	function testReplaceParam() {
		//TODO (Im Model falsch kommentiert "update a row or insert a new record if no previous row is found"
		if ($this->defaultSQL == 'mssql') {
			$this->expectException('Exception');
			$return= $this->myModel->replace(array (
				"x" => 1,
				"y" => 1
			));
		}
		$this->myModel->query("truncate table model");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("truncate table xy");
		$this->myModel->useTable= false;
		$return= $this->myModel->replace(array (
			"x" => 1,
			"y" => 1
		));
		$this->assertEqual(1, $return);
		$return= $this->myModel->replace(array (
			"x" => 2,
			"y" => 2
		));
		$this->assertEqual(1, $return);
		$return= $this->myModel->query("Select * from model");
		$this->assertEqual(2, count($return));
		$this->myModel->useTable= "xy";
		$return= $this->myModel->replace(array (
			"x" => 1,
			"y" => 1
		));
		$this->assertEqual(1, $return);
		$this->myModel->replace(array (
			"x" => 1,
			"y" => 1
		));
		$return= $this->myModel->query("Select * from xy");
		$this->assertEqual(2, count($return));
		$this->myModel->query("truncate table xy");
		$this->myModel->replace(array (
			"x" => null,
			"y" => 2
		));
		$return= $this->myModel->query("Select * from xy where x IS NULL");
		$this->assertEqual(1, count($return));
		//Replace != Update
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("Insert INTO idTable (name,Number) VALUES ('Hans',2)");
		$return= $this->myModel->replace(array (
			"PK" => 1,
			"Number" => 3
		), 'idTable');
		$this->assertEqual(count($return), 1);
		$return= $this->myModel->query("Select * from idTable");
		$this->assertNotEqual($return[0]['name'], 'Hans');
		//Replace mit 2 Keys in unterschiedlichen Spalten
		$this->myModel->query("truncate table idTable");
		$return= $this->myModel->query("Insert INTO idTable (name,Number) VALUES ('Hans',2)");
		$return= $this->myModel->query("Insert INTO idTable (name,Number) VALUES ('PETER',1)");
		$return= $this->myModel->replace(array (
			"PK" => 2,
			"Number" => 2
		), 'idTable');
		$this->assertEqual(3, $return);
		$return= $this->myModel->query("Select * from idTable");
		$this->assertEqual(count($return), 1);
		$this->myModel->query("truncate table idTable");
		$return= $this->myModel->replace(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->replace(array (
			"name" => "Hans",
			"Number" => 6
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->replace(array (
			"PK" => 8,
			"name" => "Hans",
			"Number" => 7
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->replace(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->assertEqual(2, $return);
		$return= $this->myModel->replace(array (
			"PK" => 8,
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->assertEqual(3, $return);
		$return= $this->myModel->query("Select * from idTable");
		$this->assertEqual(count($return), 2);
		//Replace mit key null
		$this->myModel->query("truncate table idTable");
		$return= $this->myModel->replace(array (
			"name" => "Hans",
			"Number" => null
		), 'idTable');
		$this->assertEqual(1, $return);
		$return= $this->myModel->replace(array (
			"name" => "Hans",
			"Number" => null
		), 'idTable');
		if ($this->defaultSQL == 'mysql') {
			$this->assertEqual(1, $return);
		}
	}


	function testReadParam() {
		$this->sendMessage("<hr>");
		$this->myModel->query("truncate table model");
		$this->myModel->query("insert into model(x,y) Values(1,2)");
		$this->myModel->query("insert into model(x,y) Values(2,4)");
		$this->myModel->useTable= false;
		$return= $this->myModel->read(array (
			"x" => 1
		));
		$this->assertEqual(2, $return[0]["y"]);
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return= $this->myModel->read(array (
			"x" => 1
		));
		$this->assertEqual(4, $return[0]["y"]);
		$return= $this->myModel->read(array (
			"x" => 1
		), array (
			"x"
		));
		$this->assertFalse(isset ($return[0]["y"]));
		$this->assertTrue(isset ($return[0]["x"]));
		$return= $this->myModel->read(array (
			"y" => 4
		), array (
			"count(y) as c"
		));
		$this->assertEqual($return[0]["c"], 3);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$string= $this->myModel->read($bedingung, null, "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				$this->assertEqual($string2, count($string));
			}
		}
		$this->myModel->query("truncate table idTable");
		$this->myModel->create(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->identyInsertON();
		$this->myModel->create(array (
			"PK" => 8,
			"name" => "Klaus",
			"Number" => 7
		), 'idTable');
		$this->identyInsertOFF();
		$return= $this->myModel->read(array (
			"PK" => 1
		), null, 'idTable');
		$this->assertEqual("Hans", $return[0]["name"]);
		$return= $this->myModel->read(array (
			"PK" => 8
		), null, 'idTable');
		$this->assertEqual("Klaus", $return[0]["name"]);
		$return= $this->myModel->read(); //ohne condition
		$this->assertEqual(count($return), 3);
		$return= $this->myModel->read('');
		$this->assertEqual(count($return), 3);
		$return= $this->myModel->read(array ());
		$this->assertEqual(count($return), 3);
	}


	function testFindExceptionEmpty() {
		//TODO eigentlich ersetzt find, read komplett. Read kann also auf deprecated gesetz werden
		//TODO als standard auf all verweisen somit find()=read()=> select * from xyz
		$this->sendMessage("<hr>");
		//$this->expectException("InvalidArgumentException");
		$return1= $this->myModel->find();
		$return2= $this->myModel->find('all');
		$this->assertEqual($return1, $return2);
	}


	function testFindExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$return= $this->myModel->find('');
	}


	function testFindExceptionEmptyArray() {
		$this->expectException("InvalidArgumentException");
		$return= $this->myModel->find(array ());
	}


	function testFindExceptionMethodeUnknownCommand() {
		$this->expectException("InvalidArgumentException");
		$return= $this->myModel->find('unkownCommand');
	}


	function testFindExceptionAllFieldsNoArray() {
		$this->expectException(new InvalidArgumentException('fields must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('all', array (
			'fields' => "test"
		));
	}


	function testFindExceptionAllOrderByNoArray() {
		$this->expectException(new InvalidArgumentException('order must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('all', array (
			'order' => "test"
		));
	}


	function testFindParamAll() {
		//TODO LIMIT ist MYSQL Syntax funktioniert so nicht siehe oben
		$this->myModel->query("truncate table model");
		$this->myModel->query("insert into model(x,y) Values(1,2)");
		$this->myModel->query("insert into model(x,y) Values(2,4)");
		$this->myModel->useTable= false;
		$return= $this->myModel->find('all', array (
			'conditions' => null
		));
		$this->assertEqual(2, count($return));
		$return= $this->myModel->find('all', array (
			'conditions' => ""
		));
		$this->assertEqual(2, count($return));
		$return= $this->myModel->find('all', array (
			'conditions' => array ()
		));
		$this->assertEqual(2, count($return));
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(2, $return[0]["y"]);
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(4, $return[0]["y"]);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"x" => 3
			)
		));
		$this->assertEqual(count($return), 0);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"x" => 1
			),
			"fields" => array (
				"x"
			)
		));
		$this->assertFalse(isset ($return[0]["y"]));
		$this->assertTrue(isset ($return[0]["x"]));
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"count(y) as c"
			)
		));
		$this->assertEqual($return[0]["c"], 3);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"count(x) as c"
			)
		));
		$this->assertEqual($return[0]["c"], 2);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"y"
			)
		));
		$this->assertTrue(isset ($return[0]["bool"]));
		$this->assertEqual($return[1]["x"], 2);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"bool"
			)
		));
		$this->assertTrue(isset ($return[0]["bool"]));
		$this->assertEqual($return[1]["x"], null);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x asc"
			)
		));
		$this->assertEqual($return[0]["x"], null);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x desc"
			)
		));
		$this->assertEqual($return[0]["x"], 2);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"page" => "2",
			"limit" => "1"
		));
		$this->assertEqual(count($return), 1);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"page" => 2,
			"limit" => "1"
		));
		$this->assertEqual(count($return), 1);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"page" => 2,
			"limit" => 1
		));
		$this->assertEqual(count($return), 1);
		$this->assertEqual($return[0]["x"], 1);
		//TODO einheitlich halten, Normalerweise wird ein empty Array zurückgegeben z.B. wenn bedingung nicht trifft. gleiches Verhalten beibehalten.
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"page" => 2
		));
		$this->assertEqual(count($return), 0);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$string= $this->myModel->find("all", array (
					"conditions" => $bedingung
				), "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				$this->assertEqual($string2, count($string));
			}
		}
		//TODO groupby before order !!
		//test Group parameter
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"group" => array (
				"bool",
				"x"
			)
		));
		$this->assertEqual($return[1]["x"], 2);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"group" => array (
				"bool",
				"x"
			)
		));
		$this->assertEqual($return[1]["x"], 1);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			),
			"order" => array (
				"x"
			),
			"group" => array (
				"bool",
				"x"
			),
			"page" => 2,
			"limit" => 1
		));
		$this->assertEqual(count($return), 1);
		$this->assertEqual($return[0]['x'], 1);
		$this->myModel->query("truncate table idTable");
		$this->myModel->create(array (
			"name" => "Hans",
			"Number" => 5
		), 'idTable');
		$this->identyInsertON();
		$this->myModel->create(array (
			"PK" => 8,
			"name" => "Klaus",
			"Number" => 7
		), 'idTable');
		$this->identyInsertOFF();
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				'PK' => 1
			)
		), 'idTable');
		$this->assertEqual("Hans", $return[0]["name"]);
		$return= $this->myModel->find("all", array (
			"conditions" => array (
				'PK' => 8
			)
		), 'idTable');
		$this->assertEqual("Klaus", $return[0]["name"]);
	}


	function testFindExceptionListEmptyNoID() {
		$this->sendMessage("<hr>");
		$this->expectException("InvalidArgumentException");
		$this->myModel->useTable= false;
		$this->myModel->find('list');
	}


	function testFindExceptionListNoArray() {
		$this->expectException('InvalidArgumentException');
		$this->myModel->useTable= false;
		$this->myModel->find('list', array (
			'listby' => "test"
		));
	}


	function testFindExceptionListFieldsNoArray() {
		$this->expectException('InvalidArgumentException');
		$this->myModel->useTable= false;
		$this->myModel->find('list', array (
			'fields' => "test"
		));
	}


	function testFindExceptionListOrderByNoArray() {
		$this->expectException('InvalidArgumentException');
		$this->myModel->useTable= false;
		$this->myModel->find('list', array (
			'order' => "test"
		));
	}


	function testFindExceptionListConditionsNoArray() {
		$this->expectException('InvalidArgumentException');
		$this->myModel->useTable= false;
		$this->myModel->find('list', array (
			'conditions' => "test"
		));
	}


	function testFindParamList() {
		$this->myModel->query("truncate table model");
		$this->myModel->query("truncate table xy");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("insert into model(x,y,bool) Values(1,2,1)");
		$this->myModel->query("insert into model(x,y,bool) Values(2,2,0)");
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y"
			)
		));
		$this->assertEqual($return[2]["x"], 2);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"x"
			)
		));
		$this->assertEqual(count($return), 2);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			)
		));
		$this->assertEqual($return[2][2]["x"], 2);
		$this->assertEqual($return[2][1]["x"], 1);
		$return= $this->myModel->find('list', array (
			"listby" => array (
				"x"
			),
			'conditions' => null
		));
		$this->assertEqual(2, count($return));
		$return= $this->myModel->find('list', array (
			"listby" => array (
				"x"
			),
			'conditions' => ""
		));
		$this->assertEqual(2, count($return));
		$return= $this->myModel->find('list', array (
			"listby" => array (
				"x"
			),
			'conditions' => array ()
		));
		$this->assertEqual(2, count($return));
		$this->myModel->useTable= "xy";
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y"
			)
		));
		$this->assertEqual(count($return), 0);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"PK"
			)
		), "idTable");
		$this->assertEqual(count($return), 0);
		$this->myModel->query("truncate table model");
		$this->myModel->query("insert into model(x,y) Values(1,2)");
		$this->myModel->query("insert into model(x,y) Values(2,4)");
		$this->myModel->useTable= false;
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y"
			),
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(1, $return[2]["x"]);
		$this->assertFalse(isset ($return[4]["x"]));
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$result1= $this->myModel->find("list", array (
			"listby" => array (
				"y"
			)
		));
		$this->assertEqual($result1[4]['x'], null);
		//TODO eventuell stoßend, dass listby das letzte Element anzeigt und groupby das erste.
		//$result2 = $this->myModel->find("all",array("group"=>array("y")));
		//$this->assertEqual($result1[4],$result1[1]);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y"
			),
			"conditions" => array (
				"x" => 2
			)
		));
		$this->assertEqual(2, $return[4]["x"]);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x" => 1
			),
			"fields" => array (
				"x",
				"y"
			)
		));
		$this->assertFalse(isset ($return[4][1]["bool"]));
		$this->assertTrue(isset ($return[4][1]["x"]));
		$return1= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x asc"
			)
		));
		$return2= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x desc"
			)
		));
		$this->assertNotEqual(array_shift($return1[4]), array_shift($return2[4]));
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x"
			),
			"page" => "2",
			"limit" => "1"
		));
		$this->assertEqual(count($return[4]), 1);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x"
			),
			"page" => 2,
			"limit" => "1"
		));
		$this->assertEqual(count($return[4]), 1);
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x"
			),
			"page" => 2,
			"limit" => 1
		));
		$this->assertEqual(count($return[4]), 1);
		$this->assertTrue(isset ($return[4][2]));
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"x in" => array (
					1,
					2
				)
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"x"
			),
			"page" => 2
		));
		$this->assertEqual(count($return), 0);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$string2= $this->bedingungExpectation[$value]["delete"];
				$string= $this->myModel->find("list", array (
					'listby' => array (
						"x",
						"y",
						"bool"
					),
					"conditions" => $bedingung
				));
				$this->assertEqual($string2, count($string));
			}
		}
		//TODO null Felder beachten entweder verwerfen oder besser irgendwie unique kenntlich machen d.h. key="null" wobei abgrenzung zu KeyString null schwierig wird.
		$return1= $this->myModel->find("list", array (
			"listby" => array (
				"x"
			)
		));
		$this->assertEqual(count($return1), 3);
		$return2= $this->myModel->find('list', array (
			"fields" => array (
				"x"
			)
		));
		$this->assertEqual(count($return2), 3);
		$this->assertEqual($return2[1], array (
			'x' => 1
		));
		//TODO mindestens alles was sich im listby befindet auch in fields aufnehmen aber nicht im letzten Array
		//also das Fields wird im Ergebniss berücksichtigt nicht aber beim Aufbau des Arrays
		//Beispiel: Bei fields["x"] und  listby["y"] sollte array(y=>array(x=>"blub")) herrauskommen
		$return= $this->myModel->find("list", array (
			"listby" => array (
				"y",
				"x"
			),
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"bool"
			)
		));
		$this->assertTrue(isset ($return[4][1]["bool"]));
	}


	function testFindExceptionCountFieldsNoArray() {
		$this->sendMessage("<hr>");
		$this->expectException(new InvalidArgumentException('fields must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('count', array (
			'fields' => "test"
		));
	}


	function testFindExceptionCountOrderByNoArray() {
		$this->expectException(new InvalidArgumentException('order must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('count', array (
			'order' => "test"
		));
	}


	function testFindParamCount() {
		$this->myModel->query("truncate table model");
		$this->myModel->query("insert into model(x,y) Values(1,2)");
		$this->myModel->query("insert into model(x,y) Values(2,4)");
		$this->myModel->useTable= false;
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(1, $return);
		$return= $this->myModel->find('count', array (
			'conditions' => null
		));
		$this->assertEqual(2, $return);
		$return= $this->myModel->find('count', array (
			'conditions' => ""
		));
		$this->assertEqual(2, $return);
		$return= $this->myModel->find('count', array (
			'conditions' => array ()
		));
		$this->assertEqual(2, $return);
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(1, $return);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"x" => 3
			)
		));
		$this->assertEqual($return, 0);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			)
		));
		$this->assertEqual($return, 3);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			)
		));
		$this->assertEqual($return, 3);
		//TODO page und limit auch bei count einheitlich halten.
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"page" => "2",
			"limit" => "1"
		));
		$this->assertEqual($return, 1);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"page" => 2,
			"limit" => "1"
		));
		$this->assertEqual($return, 1);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"page" => 2,
			"limit" => 1
		));
		$this->assertEqual($return, 1);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"page" => 2
		));
		$this->assertEqual($return, 0);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"group" => array (
				"bool"
			)
		));
		$this->assertEqual($return, 2);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$string= $this->myModel->find("count", array (
					"conditions" => $bedingung
				), "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				$this->assertEqual($string2, $string);
			}
		}
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"order" => array (
				"x"
			),
			"group" => array (
				"bool"
			)
		));
		$this->assertEqual($return, 2);
		$return= $this->myModel->find("count", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"y"
			),
			"order" => array (
				"x"
			),
			"group" => array (
				"bool"
			),
			"page" => 1,
			"limit" => 1
		));
		$this->assertEqual($return, 1);
	}


	function testFindExceptionFirstFieldsNoArray() {
		$this->sendMessage("<hr>");
		$this->expectException(new InvalidArgumentException('fields must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('first', array (
			'fields' => "test"
		));
	}
	function testFindExceptionFirstOrderByNoArray() {
		$this->expectException(new InvalidArgumentException('order must be an array'));
		$this->myModel->useTable= false;
		$this->myModel->find('first', array (
			'order' => "test"
		));
	}
	function testFindParamFirst() {
		//TODO first in MSSQL mit TOP implementieren sonst geht hier nix siehe LIMIT (fixed)
		$this->myModel->query("truncate table model");
		$this->myModel->query("insert into model(x,y) Values(1,2)");
		$this->myModel->query("insert into model(x,y) Values(2,4)");
		$this->myModel->useTable= false;
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(2, $return[0]["y"]);
		$return= $this->myModel->find('first', array (
			'conditions' => null
		));
		$this->assertEqual(2, $return[0]["y"]);
		$return= $this->myModel->find('first', array (
			'conditions' => ""
		));
		$this->assertEqual(2, $return[0]["y"]);
		$return= $this->myModel->find('first', array (
			'conditions' => array ()
		));
		$this->assertEqual(2, $return[0]["y"]);
		$this->myModel->useTable= "xy";
		$this->myModel->query("truncate table xy");
		$this->myModel->query("insert into xy (x,y,bool) values (1,4,1)");
		$this->myModel->query("insert into xy (x,y,bool) values (2,4,0)");
		$this->myModel->query("insert into xy (y,bool) values (4,0)");
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"x" => 1
			)
		));
		$this->assertEqual(4, $return[0]["y"]);
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"x" => 3
			)
		));
		$this->assertEqual(count($return), 0);
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"y"
			)
		));
		$this->assertEqual($return[0]["x"], 1);
		$this->assertFalse(isset ($return[0]["bool"]));
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"bool asc"
			)
		));
		$this->assertEqual($return[0]["x"], 2);
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x",
				"y"
			),
			"order" => array (
				"bool desc"
			)
		));
		$this->assertEqual($return[0]["x"], 1);
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"bool"
			),
			"group" => array (
				"bool"
			)
		));
		$this->assertEqual(count($return), 1);
		foreach ($this->bedingungen as $value => $bedingung) {
			if (isset ($this->bedingungExpectation[$value]["delete"])) {
				$string= $this->myModel->find("first", array (
					"conditions" => $bedingung
				), "xy");
				$string2= $this->bedingungExpectation[$value]["delete"];
				$this->assertEqual(($string2 == 0) ? 0 : 1, count($string));
			}
		}
		$return= $this->myModel->find("first", array (
			"conditions" => array (
				"y" => 4
			),
			"fields" => array (
				"x,y"
			),
			"order" => array (
				"y,bool desc"
			),
			"group" => array (
				"x,y,bool"
			)
		));
		//$this->sendMessage($return);
		$this->assertEqual($return[0]['x'], 1);
	}


	function testQueryExceptionEmpty() {
		$this->sendMessage("<hr>");
		//TODO einheitlich halten
		$this->initializeDB();
		$this->expectException("InvalidArgumentException");
		$this->myModel->query();
	}


	function testQueryExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->query('');
	}


	function testQueryExceptionEmptyArray() {
		$this->expectException("InvalidArgumentException");
		//TODO produziert das totalle Disaster in MSSQL !!!!!!!
		$this->myModel->query(array ());
	}

	function testQueryParameter() {
		$this->myModel->query("truncate table model");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("truncate table xy");
		$this->assertTrue(true);
		if ($this->defaultSQL == "mysql") {
			$this->assertEqual(count($this->myModel->query('show tables')), 5);
		} else {
			//TODO show Tables in MSSQL als ungültig angeben(fixed)
			//oder show Tables eventuell ummodellieren auf siehe oben
			$return= $this->myModel->query('Select Table_Name from INFORMATION_SCHEMA.TABLES');
			$this->assertEqual(count($return), 5);
		}
		$this->assertEqual(count($this->myModel->query('select * from xy')), 0);
		$this->assertEqual($this->myModel->query('insert into xy(x,y) values (1,1)'), 0);
		$this->assertEqual($this->myModel->query('insert into xy(x,y) values(2,4)'), 0);
		$this->assertEqual($this->myModel->query('insert into idTable (name,number) values ("Hans",2)'), 1);
		$this->assertEqual($this->myModel->query('insert into idTable (name,number) values ("Hans",4)'), 2);
		$this->identyInsertON();
		$this->assertEqual($this->myModel->query('insert into idTable (PK,name,number) values (8,"Hans",6)'), 8);
		$this->assertEqual($this->myModel->query('insert into idTable (PK,name,number) values (16,"Hans",8)', 'number'), 16);
		$this->identyInsertOFF();
		$this->assertEqual($this->myModel->query('update idTable set name="Peter" where PK=16'), 1);
		$this->assertEqual($this->myModel->query('update idTable set name="Klaus" where name="Hans"'), 3);
		$array= $this->myModel->query('select * from idTable', "PK");
		$this->assertTrue(isset ($array[16]));
		$array= $this->myModel->query('select * from idTable', "number");
		$this->assertTrue(isset ($array[6]));
		$this->assertEqual($this->myModel->query('delete from xy where x=1'), 1);
		$this->assertEqual($this->myModel->query('delete from idTable where name="Klaus"'), 3);
		$a= $this->myModel->query("select * from xy");
		$this->myModel->query("truncate table xy");
		$b= $this->myModel->query("select * from xy");
		$this->assertNotEqual(count($a), count($b));
	}


	function testQueryUnknownCommandThrowsException() {
		$this->expectException("DatabaseUnshureWhatToReturn");
		//TODO Unkown Command produziert das totalle Disaster in MSSQL !!!!!!!(fixed)
		$this->myModel->query('dowhat ever');
	}


	function testQueryDontThrowExeptionWhenItPhysicallyIsExecuted() {
		//TODO zuerstüberprüfen ob der Syntax gültig ist dann den Code ausführen(fixed)
		// sonst hat man ausgeführtes SQL und bekommt als Feedback das es nicht geht.
		$this->swallowErrors();
		if ($this->defaultSQL == "mysql") {
			$array1= $this->myModel->query('show tables');
		} else {
			$array1= $this->myModel->query('Select Table_Name from INFORMATION_SCHEMA.TABLES');
		}
		$array2= array ();
		try {
			$this->myModel->query('//\ncreate table xz (x int, z int)');
			$this->assertTrue(false, "miss expected exception");
			if ($this->defaultSQL == "mysql") {
				$array2= $this->myModel->query('show tables');
			} else {
				$array2= $this->myModel->query('Select Table_Name from INFORMATION_SCHEMA.TABLES');
			}
		} catch (Exception $e) {
			$this->assertTrue(true, "miss expected exception");
			if ($this->defaultSQL == "mysql") {
				$array2= $this->myModel->query('show tables');
			} else {
				$array2= $this->myModel->query('Select Table_Name from INFORMATION_SCHEMA.TABLES');
			}
		}
		$this->assertEqual(count($array2), count($array1));
		//TODO this Code throws exception but is executed (fixed)
		$this->expectException("DatabaseUnshureWhatToReturn");
		$this->myModel->query('//\nDrop Table xz)');
	}
	/*
	function testCachedQueryExceptionEmpty() {
		$this->sendMessage("<hr>");
		//TODO einheitlich halten
		$this->initializeDB();
		$this->expectException("InvalidArgumentException");
		$this->myModel->cachedQuery();
	}
	function testCachedQueryExceptionEmptyString() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->cachedquery('');
	}
	function testCachedQueryExceptionEmptyArray() {
		$this->expectException("InvalidArgumentException");
		//produziert das totalle Disaster in MSSQL !!!!!!!
		//nix geht mehr anschließend(fixed)
		$this->myModel->cachedquery(array());
	}
	function testCachedQueryParameter() {
		$this->myModel->query("truncate table model");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("truncate table xy");
		if($this->defaultSQL == "mysql"){
			$this->assertEqual(count($this->myModel->cachedquery('show tables')),4);
		}else{
			//show Tables in MSSQL als ungültig angeben
			//oder show Tables ummodellieren auf siehe oben
			//zuerstüberprüfen ob der Syntax gültig ist dann den Code ausführen (fixed)
			// sonst hat man ausgeführtes SQL und bekommt als Feedback das es nicht geht.
			$return = $this->myModel->cachedquery('Select Table_Name from INFORMATION_SCHEMA.TABLES');
			$this->assertEqual(count($return),4);
		}
		//TODO Cache mit hash des SQL strings benutzen wenn nicht expliziter Cache angegeben.
		$this->assertEqual(count($this->myModel->cachedQuery('select * from xy')),0);
		$this->assertEqual($this->myModel->cachedQuery('insert into xy(x,y) values (1,1)',false,"a"),0);
		$return = $this->CacheOK();
		$return2 = $this->CacheNotOK('model');
		$this->assertEqual($this->myModel->cachedQuery('insert into xy(x,y) values(2,4)',false,"b"),0);
		$return3 = $this->CacheOK();
		$return4 = $this->CacheNotOK('xy');
		$this->assertEqual($return3,$return);
		$this->assertNotEqual($return4,$return2);
		$this->assertEqual($this->myModel->cachedQuery('insert into idTable (name,number) values ("Hans",2)',false,"c"),1);
		$this->assertEqual($this->myModel->cachedQuery('insert into idTable (name,number) values ("Hans",4)',false,"d"),2);
		$this->identyInsertON();
		$this->assertEqual($this->myModel->cachedQuery('insert into idTable (PK,name,number) values (8,"Hans",6)',false,"e"),8);
		$this->assertEqual($this->myModel->cachedQuery('insert into idTable (PK,name,number) values (16,"Hans",8)',false,"f"),16);
		$this->identyInsertOFF();
		$this->assertEqual($this->myModel->cachedQuery('update idTable set name="Peter" where PK=16',false,"g"),1);
		$this->assertEqual($this->myModel->cachedQuery('update idTable set name="Klaus" where name="Hans"',false,"h"),3);
		$array = $this->myModel->cachedQuery('select * from idTable',"PK","i");
		$this->assertTrue(isset($array[16]));
		$array = $this->myModel->cachedQuery('select * from idTable',"number","j");
		$this->assertTrue(isset($array[6]));
		$this->assertEqual($this->myModel->cachedQuery('delete from xy where x=1',false,"k"),1);
		$this->assertEqual($this->myModel->cachedQuery('delete from idTable where name="Klaus"',false,"l"),3);
		$a = $this->myModel->cachedQuery("select * from xy",false,"m");
		$this->myModel->cachedQuery("truncate table xy");
		$b = $this->myModel->cachedQuery("select * from xy",false,"m");
		$this->assertEqual($a,$b);
		$this->myModel->query('insert into xy (x,y) values(1,400)');
		$array1 = $this->myModel->cachedQuery('select * from xy',false,"meck3",1);
		$this->myModel->query('truncate table xy');
		$array2 = $this->myModel->cachedQuery('select * from xy',false,"meck3",1);
		$this->assertClone($array2,$array1);
		sleep(2);
		//TODO Cache wird nach ablauf der Zeit nicht ! gelöscht und ist somit nicht zum überschreiben verfügbar... siehe unten.
		//TODO Wäre gut wenn man die Möglichkeit hat einen Value im Cache explizit zu überschreiben
		//Momentan kann nur in Cache geschrieben werden wenn der CacheCode nicht schon drin steht...
		//Wenn nun ein Update vohanden ist von dem man weiß sollte der Cache überschrieben werden können.
		//damit das Update dann den Cache belegt und nicht alte Leichen rumgeistern...
		$this->myModel->query('truncate table xy');
		$array2 = $this->myModel->cachedQuery('select * from xy',false,"meck3",0);
		$this->assertNotEqual($array1,$array2);
	}

	function CacheOK(){
		return $this->myModel->cachedQuery('select * from xy');
	}
	function CacheNotOK($x){
		return $this->myModel->cachedQuery('select * from '.$x);
	}
	function testCachedQuerrUnknownCommandThrowsException() {
		$this->expectException("Exception");
		//produziert das totalle Disaster in MSSQL !!!!!!!
		//nix geht mehr anschließend
		$this->myModel->cachedQuery('dowhat ever)');
	}
	*/

	function testPairsParameterNullThrowsException() {
		$this->sendMessage("<hr>");
		$this->expectException("InvalidArgumentException");
		$this->myModel->pairs(null);
	}

	function testPairsParameterEmptyStringThrowsException() {
		$this->expectException("InvalidArgumentException");
		$this->myModel->pairs("");
	}

	function testPairsParameterEmptyArrayThrowsException() {
		$this->expectException("InvalidArgumentException");
		//TODO einheitlich halten
		$this->myModel->pairs(array ());
	}

	function testPairsParameter() {
		$string= $this->myModel->pairs(array (
			"x" => null
		));
		$this->assertEqual($string, $this->quoteName("x")."=NULL");
		$string= $this->myModel->pairs(array (
			"x" => 42
		));
		$this->assertEqual($string, $this->quoteName("x")."='42'");
		$output1= $this->myModel->query('select * from xy');
		$this->myModel->query('insert into xy(y) values(100)');
		$update= $this->myModel->query('update xy set '.$string.' where y=100');
		$output2= $this->myModel->query('select * from xy');
		$this->assertNotEqual(count($output1), count($output2));
		$sql= $this->myModel->pairs(array (
			"x" => 42
		));
		$this->myModel->query('Select * from xy where '.$sql);
		$this->assertEqual($output2[0]['y'], 100);
	}
	/*private fixed
	function testGetWhereStringHelperParameter(){
		//TODO helper helper als privat markieren da der Helper nur in kombination mit Where funktioniert.
		//wenn für etwas anderes als Where verwendet wird z.B. insert sollte das Verhalten bei leerString anders sein da SET("leer") problematisch ist und es eher SET(primaryKey = null) wäre.
		$this->sendMessage("<hr>");
		foreach($this->bedingungen as $value => $bedingung){
			$string = $this->myModel->getWhereStringHelper($bedingung,'idTable');
			$string2 = $this->bedingungExpectation[$value]["helper"];
			$this->assertEqual($string2,$string);
		}
	}
	function testGetWhereStringHelperInException(){
		$id = array("x in"=>"1");
		$this->expectException(new Exception('in operator needs to have array() as value'));
		//Todo eine vom eingabewert unabhängige Exception ist besser damit darauf reagiert werden kann "x in needs to have array() as value" -> "in needs to have array() as value (fixed)"
		$this->myModel->getWhereStringHelper($id,'xy');
	}
	function testGetWhereStringHelperNotInExceptions(){
		$id = array("x not in"=>"1");
		$this->expectException("Exception");
		$this->myModel->getWhereStringHelper($id,'xy');
	}
	*/
	function testGetWhereStringParameter() {
		$this->sendMessage("<hr>");
		foreach ($this->bedingungen as $value => $bedingung) {
			$string= $this->myModel->getWhereString($bedingung, 'idTable');
			$string2= isset ($this->bedingungExpectation[$value]["where"]) ? $this->bedingungExpectation[$value]["where"] : $this->bedingungExpectation[$value]["helper"];
			if ($string2 == '') {
				$this->assertEqual($string2, $string);
			} else {
				$this->assertEqual(" WHERE ".$string2, $string);
			}
		}
	}

	function testGetWhereStringInException() {
		$id= array (
			"x in" => "1"
		);
		$this->expectException(new InvalidArgumentException('in operator needs to have array() as value'));
		$this->myModel->getWhereString($id, 'xy');
	}
	function testGetWhereStringNotInExceptions() {
		$id= array (
			"x not in" => "1"
		);
		$this->expectException(new InvalidArgumentException('not in operator needs to have array() as value'));
		$this->myModel->getWhereString($id, 'xy');
	}

	function testGetPrefix() {
		$this->sendMessage("<hr>");
		$this->assertEqual(gettype($this->myModel->getPrefix()), "string");
		$this->myModel->changeConnection("second");
		//TODO (fixed); geht so nicht "$this->dbo()->dboconfig['prefix']" sondern $this->dbo()dboconfig['prefix']
		//TODO die Frage ist ob man diese Funktion nicht wegschmeißt,  und statt dessen getConfig im Model!(nicht in den dbos) um einem Parameter erweitert ... ohne Parameter bekommt man weiterhin das gesammte Array.
		/*getConfig($attribute=null){
		 * 	$config = $this->dbo()->dbconfig;
		 * 	if(empty($attribute)){
		 * 		return $config
		 * }else{
		 * 	 return $config[$attribute];
		 * }
		 */
		$this->assertEqual($this->myModel->getPrefix(), "pre_");
		$this->myModel->changeConnection("third");
		$this->assertEqual($this->myModel->getPrefix(), "");
		$this->myModel->changeConnection($this->defaultSQL);
	}
	function testEscapeparameter() {
		$this->sendMessage("<hr>");
		$this->myModel->changeConnection($this->defaultSQL);
		$this->myModel->changeConnection($this->defaultSQL);
		if ($this->defaultSQL == "mysql") {
			$escape1= $this->myModel->escape("\n");
			$this->assertEqual($escape1, "\\n");
			$escape1= $this->myModel->escape("'");
			$this->assertEqual($escape1, "\'");
			$escape1= $this->myModel->escape('"');
			$this->assertEqual($escape1, '\"');
		}
		$this->myModel->query("truncate table idTable");
		$return= $this->myModel->query('insert into idTable(name,number) Values("\n..\.\'",1)');
		$this->assertEqual($return, 1);
		$return= $this->myModel->query('insert into idTable(name,number) Values("'.$this->myModel->escape("\n..\.\'").'",2)');
		$this->assertEqual($return, 2);
	}
	function testDescribe() {
		$this->myModel->changeConnection($this->defaultSQL);
		$config= $this->myModel->getConfig();
		//TODO MSSQL->describe() umbauen so gehts nicht ! (fixed)
		/*z.B.
		 *print_r($this->myModel->query("Select a.COLUMN_NAME,[IS_NULLABLE],[COLUMN_DEFAULT],[DATA_TYPE],[CHARACTER_MAXIMUM_LENGTH],[CONSTRAINT_NAME] from INFORMATION_SCHEMA.COLUMNS a left join (SELECT COLUMN_NAME,CONSTRAINT_NAME from INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE where TABLE_NAME='idTable' AND TABLE_CATALOG ='".$config["database"]."') b on (a.COLUMN_NAME = b.COLUMN_NAME)
		 * where a.TABLE_NAME='idTable' AND a.TABLE_CATALOG ='".$config["database"]."'"));
		 */
		$this->sendMessage("<hr>");
		$this->myModel->useTable= false;
		$describe1= $this->myModel->describe();
		$this->assertEqual(count($describe1["cols"]), 3);
		$this->myModel->useTable= "xy";
		$describe3= $this->myModel->describe();
		$this->assertNotEqual($describe1, $describe3);
		$this->assertEqual($describe3["table"], "xy");
		$describe3= $this->myModel->describe("idTable");
		$this->assertEqual($describe3["primary"][0], "PK");
		$this->assertEqual(count($describe3["primary"]), 1);
		$this->assertEqual($describe3['cols']['number']['key'], "UNI");
		$this->assertEqual($describe3['cols']['PK']['key'], "PRI");
		$describe4= $this->myModel->describe("multi");
		$this->assertEqual(count($describe4["primary"]), 2);
		$this->assertEqual(count($describe4['cols']), 4);
		$this->assertEqual($describe4['cols']['x']['null'], false);
		$this->assertEqual($describe4['cols']['x']['type'], 'int');
		$length= 3;
		$length2= 10;
		if ($this->defaultSQL == 'mysql') {
			$length= 4;
			$length2= 11;
		}
		$this->assertEqual($describe4['cols']['x']['length'], $length);
		$this->assertEqual($describe4['cols']['y']['type'], 'int');
		$this->assertEqual($describe4['cols']['y']['length'], $length2);
		$this->assertEqual($describe4['identity'], 'y');
		$this->assertEqual($describe4['cols']['name']['type'], 'string');
		$this->assertEqual($describe4['cols']['name']['length'], 10);
		$this->assertEqual($describe4['cols']['name2']['type'], 'string');
		$this->assertEqual($describe4['cols']['name2']['length'], 20);
		$this->assertEqual($describe4['cols']['name']['default'], null);
		$this->assertEqual($describe4['cols']['name2']['default'], '');
	}
	function testQuoteParameter() {
		$this->sendMessage("<hr>");
		$escape1= $this->myModel->escape("blub");
		$escape2= $this->myModel->quote("blub");
		$this->assertEqual("'".$escape1."'", $escape2);
		$quote= $this->myModel->quote("'bu\"ble\n'g");
		$this->myModel->query("truncate table idTable");
		$this->myModel->query("insert into idTable(name,number) VALUES (".$quote.",1)");
		$result= $this->myModel->query("Select * from idTable");
		$this->assertEqual($result[0]['name'], "'bu\"ble\n'g");
	}
	function testMakeDateTimeParameter() {
		$this->sendMessage("<hr>");
		$string= $this->myModel->makeDateTime(0);
		$string2= $this->myModel->makeDateTime(3600);
		//print_r($this->myModel->query("Select CONVERT(char(20),dateadd(ss,0+DATEDIFF(ss, GetUtcDate(), GetDate()),'1970-01-01 00:00:00'),120)"));
		$result= $this->myModel->query("Select ".$string);
		$result2= $this->myModel->query("Select ".$string2);
		//TODO unterschiedliche Timestamp Formate vereinheitlichen ! (fixed)
		//... TODO ... warum ist der mssql Timestamp(0) auf 12 Uhr gesetzt und nicht 1 Uhr (fixed)
		//Test ist auf Sommerzeit eingestellt...
		//MYSQL berechnet nicht die Sommerzeit mit !!! BUG
		if ($this->defaultSQL == "mysql") {
			$this->assertEqual($result[0]["FROM_UNIXTIME(0)"], "1970-01-01 01:00:00");
			$this->assertEqual($result2[0]["FROM_UNIXTIME(3600)"], "1970-01-01 02:00:00");
		} else
			if ($this->defaultSQL == "mssql") {
				$this->assertEqual($result[0]["computed"], "1970-01-01 02:00:00 ");
				$this->assertEqual($result2[0]["computed"], "1970-01-01 03:00:00 ");
			}

	}
	function testValidateParameter() {
		$this->sendMessage("<hr>");
		$string= $this->myModel->validate("VALID_NUMBER");
		$this->assertFalse($string);
		$string= $this->myModel->validate(array (
			"number" => VALID_NUMBER
		), array (
			"number" => 1234567890
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"number" => VALID_NUMBER
		), array (
			"number" => "1234567890"
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"number" => VALID_NUMBER
		), array (
			"number" => "xy"
		));
		$this->assertNotIdentical($string, true);

		$string= $this->myModel->validate(array (
			"empty" => VALID_NOT_EMPTY
		), array (
			"empty" => "xy"
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"empty" => VALID_NOT_EMPTY
		), array (
			"empty" => ""
		));
		$this->assertNotIdentical($string, true);
		$string= $this->myModel->validate(array (
			"empty" => VALID_NOT_EMPTY
		), array (
			"empty" => null
		));
		$this->assertNotIdentical($string, true);

		$string= $this->myModel->validate(array (
			"empty" => VALID_YEAR
		), array (
			"empty" => "1998"
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"empty" => VALID_YEAR
		), array (
			"empty" => 1991
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"empty" => VALID_YEAR
		), array (
			"empty" => "x91"
		));
		$this->assertNotIdentical($string, true);

		$string= $this->myModel->validate(array (
			"empty" => VALID_EMAIL
		), array (
			"empty" => "pyrania-blub@arcor.de"
		));
		$this->assertIdentical($string, true);
		$string= $this->myModel->validate(array (
			"empty" => VALID_EMAIL
		), array (
			"empty" => "nix.de"
		));
		$this->assertNotIdentical($string, true);
	}

}
?>
