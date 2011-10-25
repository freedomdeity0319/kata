<?php
require_once('lib'.DIRECTORY_SEPARATOR.'kataTestBase.simpleTest.class.php');

class DboTest  extends kataTestBaseClass {
	private $myModel = null;
	private $defaultSQL = "mssql"; //mssql,mysql hier kann man zwischen den beiden Modi mysql und mssql wechseln.
	private $sQLQuote	= array();
	private $dbo	= null;

	function __construct($SQL=null) {
		parent::__construct();
		if(strtolower($SQL) == "mysql"){
			$this->defaultSQL = "mysql";
		}else if(strtolower($SQL) == "mssql"){
			$this->defaultSQL = "mssql";
		}
    }

	function identyInsertON($table='idTable'){
		if($this->defaultSQL == "mssql"){
		mssql_query('set IDENTITY_INSERT '.$table.' ON',$this->getLink());
		}
	}

	function identyInsertOFF($table='idTable'){
		if($this->defaultSQL == "mssql"){
			mssql_query('set IDENTITY_INSERT '.$table.' OFF',$this->getLink());
		}
	}
	function getLink(){
		$dbo = $this->myModel->dbo();
		return $dbo->getLink();
	}

	function initializeMYSQL(){
		$link = $this->getLink();
		mysql_query('Drop Table if exists xy',$link);
		mysql_query('Drop Table if exists xz',$link);
		mysql_query('Drop Table if exists idTable',$link);
		mysql_query('Drop Table if exists model',$link);
		mysql_query('Drop Table if exists multi',$link);
		mysql_query('Create Table xy (x int null, y int null,bool boolean null)',$link);
		mysql_query('Create Table idTable (PK int NOT NULL AUTO_INCREMENT,name varchar(10),number int null,Primary key(PK),Unique key(number))',$link);
		mysql_query('Create Table model (x int, y int,bool boolean)',$link);
		mysql_query('Create Table xz (x int null, z int null,id int NOT NULL Primary key)',$link);
		mysql_query('Create Table multi (x tinyint , y int not null AUTO_INCREMENT,name varchar(10) null,name2 char(20) not null,Primary key(x,y),Unique key(name2),Unique key(name))',$link);

	}
	function initializeMSSQL(){
		$link = $this->myModel->dbo()->getLink();
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "xy") Drop Table xy',$link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "zy") Drop Table zy',$link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "xz") Drop Table xz',$link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "idTable") Drop Table idTable',$link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "model") Drop Table model',$link);
		mssql_query('IF EXISTS(SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES where TABLE_NAME = "multi") Drop Table multi',$link);
		mssql_query('Create Table xy (x int NULL, y int NULL,bool bit NULL)',$link);
		mssql_query('Create Table xz (x int NULL, z int NULL,id int NOT NULL)',$link);
		mssql_query('Create Table idTable (PK int NOT NULL IDENTITY PRIMARY KEY,name varchar(10) NULL,number int null UNIQUE)',$link);
		mssql_query('Create Table model (x int NULL, y int NULL,bool bit NULL)',$link);
		mssql_query('Create Table multi (x tinyint not NULL, y int not NULL,name varchar(10) null,name2 char(20) not null,PRIMARY KEY(x,y),UNIQUE (name),UNIQUE (name2))',$link);
	}

	function initializeDB(){
		$this->myModel->changeConnection($this->defaultSQL);
		if($this->defaultSQL == "mysql"){
			$this->sendMessage('Initializing MySQL<br />');
			$this->initializeMYSQL();
		}else if($this->defaultSQL == "mssql"){
			$this->sendMessage('Initializing MSSQL<br />');
			$this->initializeMSSQL();
		}
	}

	function quoteName($value){
		return $this->myModel->quoteName($value);
	}

	function testInitialize() {
		//entfernt die Coverage Berechnung des Startens von den in den einzelnen Funktionen tatsächlich benutzten...
		//sonst hat erste Funktion mehr Coverage als eigentlich verbraucht.
		$this->sendMessage('<hr>');
		$this->bootstrapKata();
		$this->myModel = new Model;
		$this->initializeDB();
		$this->dbo = $this->myModel->dbo();
		$this->assertTrue(!empty($this->myModel->connection));
		$this->myModel->query("insert into model(x,y,bool) Values(1,4,1)");
		$this->myModel->query("insert into model(x,y,bool) Values(2,3,1)");
		$this->myModel->query("insert into model(x,y,bool) Values(2,2,1)");
		$this->myModel->query("insert into model(x,y,bool) Values(1,1,1)");
		$this->myModel->query("insert into xz(x,z,id) Values(1,4,1)");
		$this->myModel->query("insert into xz(x,z,id) Values(2,3,2)");
		$this->myModel->query("insert into xz(x,z,id) Values(2,2,3)");
		$this->myModel->query("insert into xz(x,z,id) Values(1,1,4)");
	}


	function testLIMITWithoutKeyWithoutOrderby(){
		$this->sendMessage('<hr>');
		$this->myModel->useTable = false;
		$return = $this->dbo->getPageQuery('SELECT * FROM model',1,1);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT * FROM model',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM model',2,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['y'],2);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT x FROM model ',3,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual(count($return2),0);
		$return = $this->dbo->getPageQuery('SELECT x FROM model ',4,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual(count($return2),0);
		$return = $this->dbo->getPageQuery('SELECT y FROM model ',2,3);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['y'],1);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT * FROM model ',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['y'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM model where y=3',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],2);
		$this->assertEqual($return2[0]['y'],3);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM model group by bool',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],6);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT * FROM (SELECT * FROM model) as a',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['y'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['z'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT * FROM model) as a group by bool',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],6);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);
	}
	function testLIMITContainingIDENTICALWithoutOrderby(){
		$return = $this->dbo->getPageQuery('SELECT * FROM xz',1,1);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz',2,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],2);
		$this->assertEqual($return2[1]['z'],1);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT x FROM xz ',3,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual(count($return2),0);
		$return = $this->dbo->getPageQuery('SELECT x FROM xz ',4,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual(count($return2),0);
		$return = $this->dbo->getPageQuery('SELECT z FROM xz ',2,3);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['z'],1);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz ',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['z'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz where z=3',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],2);
		$this->assertEqual($return2[0]['z'],3);
		$this->assertEqual(count($return2),1);
		$return = $this->dbo->getPageQuery('SELECT x,sum(z) as z FROM xz group by x',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['z'],5);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM (SELECT * FROM xz) as a',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['z'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT * FROM xz',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[1]['z'],3);
		$this->assertEqual(count($return2),2);
		$return = $this->dbo->getPageQuery('SELECT x,sum(z) as z FROM (SELECT * FROM xz) as a group by x',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['z'],5);
		$this->assertEqual(count($return2),2);

	}

	function testLIMITContainingOrderby(){

		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT * FROM model) as a group by bool order by 1',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],6);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);

		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT x,bool,sum(y) as y FROM model group by x,bool) as a group by bool order by 1',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],3);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);

		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT x,bool,sum(y) as y FROM model group by x,bool having sum(y) > 4) as a group by bool order by 1',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],3);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);

		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT x,bool,sum(y) as y FROM model group by x,bool having sum(y) > 4) as a where x in (SELECT 1 UNION SELECT 2) group by bool order by 1',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],3);
		$this->assertEqual($return2[0]['y'],10);
		$this->assertEqual(count($return2),1);

		$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT * FROM model) as a group by x order by x',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],5);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual($return2[1]['y'],5);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT * FROM model) as a group by x order by x asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],5);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual($return2[1]['y'],5);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT TOP(4)* FROM model order by x) as a group by x order by x asc',1,2);
		if($this->defaultSQL == "mysql"){
			$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT * FROM model order by x) as a group by x order by x asc',1,2);
		}
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],5);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual($return2[1]['y'],5);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT TOP(4)* FROM model order by x) as a group by x',1,2);
		if($this->defaultSQL == "mysql"){
			$return = $this->dbo->getPageQuery('SELECT x,sum(y) as y FROM (SELECT * FROM model order by x) as a group by x',1,2);
		}
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],5);
		$this->assertEqual($return2[1]['x'],2);
		$this->assertEqual($return2[1]['y'],5);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM (SELECT TOP(4)* FROM model order by x) as a',1,2);
		if($this->defaultSQL == "mysql"){
			$return = $this->dbo->getPageQuery('SELECT * FROM (SELECT * FROM model order by x) as a',1,2);
		}
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x asc,bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x asc, bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x asc , bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x , bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x, bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x,bool asc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x,bool ',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x,bool  ',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x,bool desc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x,bool',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by x asc,bool desc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by 1, bool desc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);

		$return = $this->dbo->getPageQuery('SELECT * FROM model order by 1 asc, bool desc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);


		$return = $this->dbo->getPageQuery('SELECT * FROM model order by '.$this->quoteName('x').'asc, bool desc',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertEqual($return2[0]['x'],1);
		$this->assertEqual($return2[0]['y'],4);
		$this->assertEqual($return2[1]['x'],1);
		$this->assertEqual($return2[1]['y'],1);
		$this->assertEqual(count($return2),2);
	}

	function testTime(){
		return;
		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT x,bool,sum(y) as y FROM model group by x,bool having sum(y) > 3) as a where x in (SELECT 1 UNION SELECT 2) group by bool ',1,2);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT sum(x) as x,sum(y) as y FROM (SELECT x,bool,sum(y) as y FROM model group by x,bool having sum(y) > 3) as a where x in (SELECT 1 UNION SELECT 2) group by bool order by 1',1,2);
		$return2 = $this->dbo->query($return);
		$this->assertTrue(true);
		$link = $this->dbo->getLink();
		for($x=0;$x<5000;$x++){
			mssql_query("/*select*/insert into zy(z,y) Values(1,4)", $link);
			mssql_query("/*select*/insert into xy(x,y) Values(1,4)", $link);
		}
		$return = $this->dbo->getPageQuery('SELECT * from zy order by 1',50,100);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from zy order by 1',49,100,false);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from zy order by 1',500,10,false);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from zy order by 1',499,10);
		$return2 = $this->dbo->query($return);

		$return = $this->dbo->getPageQuery('SELECT * from xy order by 1',50,100);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from xy order by 1',49,100,false);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from xy order by 1',500,10,false);
		$return2 = $this->dbo->query($return);
		$return = $this->dbo->getPageQuery('SELECT * from xy order by 1',499,10);
		$return2 = $this->dbo->query($return);
	}


}
?>
