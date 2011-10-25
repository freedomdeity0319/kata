<?php
require_once('lib'.DIRECTORY_SEPARATOR.'webTest.class.php');
require(dirname(__FILE__).DIRECTORY_SEPARATOR.'PortalTestVars.php');

class PortalTest extends webTestClass {

	function PortalTest(){
		parent::webTestClass(3);
	}

	function testInitialize(){
		$this->baseUrl = BASEURL;
		$this->baseUrlParts = $this->getUrlParts(BASEURL);
		//Definiert einen Übergeordneten Container zum lokalisieren des Logins
		$loginContainerPieces = explode(",",LOGINCONTAINER);
		$this->loginContainer = array();
		foreach($loginContainerPieces as $loginContainerPiece){
			$temp = explode("=",$loginContainerPiece);
			$this->loginContainer[$temp[0]] = $temp[1];
		}
		//all definiert Teile die beim TestSets ausgeführt werden sollen.
		$this->get($this->baseUrl);
		$this->assertLandOnUrl($this->baseUrl);
		$this->all = array("testLogin","testBuddies","testMessages","testGuestBook","testJoinGames","testLogin","testPlayGames","testSecurity","testSetData","testClearTestUser");
		//guestBook
		//$this->all = array("testGuestBook");
		//playgames
		//$this->all = array("testPlayGames");
		//joingames
		//$this->all = array("testJoinGames");
		//buddies
		//$this->all = array("testClearTestUser","testBuddies");
		//messages
		//$this->all = array("testClearTestUser","testBuddies","testMessages");
		//clearTestUser
		//$this->all = array("testClearTestUser");
		$this->addHeader(USERAGENT_MOZILLA);
		$this->addHeader(ACCEPT_CHARSET);
	}

	function testLogin(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		$this->restart();
		//login
		//Test Einloggen mit Account (keine Fehler auf der Seite, landet auf standard login Seite, es gibt ein Logout button und kein Loginform mehr)
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD));
			$this->assertNotHasElement("div",array("innerText"=>LOGIN_MISENTRY));
			$this->assertLandOnUrl($this->baseUrl.STANDARD_LOGIN_URL);
			$loginBox = $this->getElements("div",$this->loginContainer);
			$this->assertHasElement("a",array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertNotHasElement("form",array("action"=>$this->baseUrl.LOGIN_FORM_URL));
		//kurzer Account test, überprüfe anzahl Spiele des Accounts
			$this->get($this->baseUrl.MY_MMOGAME_URL);
			$this->assertLandOnUrl($this->baseUrl.MY_MMOGAME_URL);
			//Vorsicht Dev runden sind mit falscher IP-Adresse disabled
			$ContentContainer = $this->getElements("div",array("class"=>CONTENTCONTAINER_CLASS));
			$GameContainer = $this->getElements("h2",array("innerText"=>H2_HEADER_MY_MMOGAME),$ContentContainer,false);
			$this->assertNotEqual(count($GameContainer),0,"There is no GameContainer holding Headline H2=".H2_HEADER_MY_MMOGAME);
			$GameNodes = $this->getElements("div",array("class ="=>MY_MMOGAME_GAMESCONTAINER_CLASS),$GameContainer);
			$this->assertEqual(count($GameNodes),COUNT_GAMEACCOUNTS,"Some Gameaccounts are missing (".count($GameNodes)."/".COUNT_GAMEACCOUNTS.")");
		//logout
			//testeLogout
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//passwort vergessen
			$this->followLink(array("href"=>$this->baseUrl.PASSWORD_LOST_URL));
			$this->assertLandOnUrl($this->baseUrl.PASSWORD_LOST_URL);
			$this->submitForm(array("action"=>$this->baseUrl.PASSWORD_LOST_URL),array(PASSWORD_LOST_EMAIL_NAME=>USERNAME));
			$this->assertLandOnUrl($this->baseUrl.PASSWORD_LOST_URL);
			//Wir müssten einen Password_lost_success Text auf der Seite finden es sei denn wir haben heute zu häufig das Passwort vergessen.
			$toMuchMails = $this->getElements("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
			if(!empty($toMuchMails)){
				$this->assertHasElement("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
			}else{
				$this->assertHasElement("div",array("innerText"=>PASSWORD_LOST_SUCCESS));
			}
		//register
			$this->followLink(array("href"=>$this->baseUrl.REGISTER_URL));
			$this->assertLandOnUrl($this->baseUrl.REGISTER_URL);
			$this->submitForm(array("action"=>$this->baseUrl.REGISTER_URL),array(REGISTER_FORM_AGB_NAME=>1,REGISTER_FORM_EMAIL_NAME=>NEW_REGISTER_MAIL,REGISTER_FORM_PASSWORD_NAME=>NEW_REGISTER_PASSWORD));
			//Wir müssten einen Refister_success Text auf der Seite finden, es sei denn wir haben heute zu häufig mit dieser Mail einen Registrier antrag gestellt
			//print_r($this->_browser->getContent());
			$toMuchMails = $this->getElements("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
			if(!empty($toMuchMails)){
				$this->assertHasElement("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
				$this->assertLandOnUrl($this->baseUrl.REGISTER_URL);
			}else{
				$this->assertLandOnUrl($this->baseUrl.AUTHENTICATE_URL);
				$this->assertHasElement("div",array("innerText"=>REGISTER_SUCCESS));
			}
		//anforderung eines neuen Authentifizierungs Schlüssel
			$this->followLink(array("href"=>$this->baseUrl.RE_AUTHENTICATE_URL));
			$this->assertLandOnUrl($this->baseUrl.RE_AUTHENTICATE_URL);
			$this->submitForm(array("action"=>$this->baseUrl.RE_AUTHENTICATE_URL),array(RE_AUTHENTICATE_FORM_EMAIL_NAME=>NEW_REGISTER_MAIL));
			$this->assertLandOnUrl($this->baseUrl.RE_AUTHENTICATE_URL);
			//suche nach Successmeldung oder zuviele Mails wenn zu vielen reauthentifizierungen am Tag durchgeführt wurden
			//print_r($this->_browser->getContent());
			$toMuchMails = $this->getElements("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
			if(!empty($toMuchMails)){
				$this->assertHasElement("div",array("innerText"=>TO_MUCH_MAILS_TEXT));
			}else{
				$this->assertHasElement("div",array("innerText"=>RE_AUTHENTICATE_SUCCESS));
			}
	}
	function testPlayGames(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		//login
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD));
		//folge/teste den Link auf der main Tab Leiste zu meine-Spiele
			$this->followLink(array("href"=>$this->baseUrl.MY_MMOGAME_URL),$this->getElements("ul",array("id"=>MAIN_TAB_ID)));
			$this->assertLandOnUrl($this->baseUrl.MY_MMOGAME_URL);
		//teste sortieren meiner Spiele
			$this->submitForm(array("action"=>$this->baseUrl.SORTGAMES_URL));
			$this->assertLandOnUrl($this->baseUrl.MY_MMOGAME_URL);
		//teste Menge an Gamecontaineren
			$GameNodes = $this->getElements("div",array("class ="=>MY_MMOGAME_GAMESCONTAINER_CLASS));
			//Vorsicht Dev runden sind mit falscher IP-Adresse disabled
			$this->assertEqual(count($GameNodes),COUNT_GAMEACCOUNTS,"Some Gameaccounts are missing (".count($GameNodes)." of ".COUNT_GAMEACCOUNTS.")");
		//für jeden Game Container teste ob er runden hat
			foreach($GameNodes as $gameNode){
				$AllRoundLinks = $this->getElements("a",array("href"=>$this->baseUrl.PLAYGAMES_URL),array($gameNode));
				$this->assertNotEqual(count($AllRoundLinks),0,"Some Gameaccounts are Listed without any Round");
			}
		//für jede Runde teste go button
			$OpenRoundLinks = $this->getElements("a",array("class !"=>MY_GAME_LINK_DISABLED_CLASS,"href"=>$this->baseUrl.PLAYGAMES_URL),$GameNodes);
			foreach($OpenRoundLinks as $OpenRoundLink){
				$attributeValues = $this->getAttributes("href",array($OpenRoundLink));
				$urlParts = $this->getUrlParts($attributeValues[0]);
				$this->followLink($OpenRoundLink);
				$this->sendMessage("<br>".$this->getUrl()."<br>");
				$this->assertLandOnUrl($urlParts["url"]);
				$this->get($this->baseUrl.MY_MMOGAME_URL);
				$this->assertLandOnUrl($this->baseUrl.MY_MMOGAME_URL);
			}
		//ausloggen
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}
	function testJoinGames(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		//login mit leerem Account
		$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY));
		//teste Account es darf sich kein Spiel in MY_MMOGAME befinden aber dennoch ein Meine Spiele Container mit Information xyz
			$this->get($this->baseUrl.MY_MMOGAME_URL);
			$this->assertLandOnUrl($this->baseUrl.MY_MMOGAME_URL);
			$ContentContainer = $this->getElements("div",array("class"=>CONTENTCONTAINER_CLASS));
			$GameContainer = $this->getElements("h2",array("innerText"=>H2_HEADER_MY_MMOGAME),$ContentContainer,false);
			$this->assertNotEqual(count($GameContainer),0,"There is no GameContainer holding Headline H2=".H2_HEADER_MY_MMOGAME);
			$GameNodes = $this->getElements("div",array("class ="=>MY_MMOGAME_GAMESCONTAINER_CLASS),$GameContainer);
			$this->assertEqual(count($GameNodes),0);
		//teste Link zur GameListe im GameContainer
			$this->followLink(array("href"=>$this->baseUrl.GAMELIST_URL),$GameContainer);
			$this->assertLandOnUrl($this->baseUrl.GAMELIST_URL);
		//für jedes Spiele teste gamedetails und teste dort den joingame button
		//Problem mit joingameButtons da diese erst mittels Javascript reinkommen //Code anpassung wird benötigt
		//Probleme mit IP und Dev Kisten (keine Runde)
		//Joingame wird nur simuliert //Code anpassung !!! Weiterleitung auf team_url wenn alles korrekt war.
			$GameNodes = $this->getElements("div",array("class"=>GAMESLIST_GAMESCONTAINER_CLASS));
			foreach($GameNodes as $GameNode){
				$this->followLink(array("href"=>$this->baseUrl.GAMEDETAILS_URL),array($GameNode),0);
				$this->sendMessage("<br>".$this->getUrl()."<br>");
				$this->assertLandOnUrl($this->baseUrl.GAMEDETAILS_URL);
				$this->followLink(array("href"=>$this->baseUrl.JOINGAMES_URL));
				$this->assertLandOnUrl($this->baseUrl.JOINGAMES_URL);
				$this->submitForm(array("action"=>$this->baseUrl.JOINGAMES_URL),array(JOINGAME_NICKNAME_NAME=>JOINGAME_NICKNAME_EMPTY));
				$this->assertLandOnUrl($this->baseUrl.TEAM_URL);
			}
		//ausloggen
		$this->get($this->baseUrl.LOGOUT_URL);
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}
	function testGuestBook(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2));
			$this->assertLandOnUrl($this->baseUrl.STANDARD_LOGIN_URL);
		//es muss ein Link zum eigenen Profil da sein folge dem ersten Link
			$this->followLink(array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),null,0);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
		//hole ein Token
			$form = $this->getElements("form",(array("action"=>DIREKT_EDIT_PROFILE_URL)));
			$attributeValues = $this->getAttributes("action",$form);
			$urlParam = $this->getUrlParameter($attributeValues[0]);
			$this->assertTrue(isset($urlParam["__token"]));
		//schreibe x gästebucheinträge x = anzahl GBEinträge pro seite
			for($x=0;$x<GUESTBOOK_ENTRIES_PER_PAGE;$x++){
				$this->post($this->baseUrl.INSERT_GUESTBOOK_URL."/".USERID2."?__token=".$urlParam["__token"],array("data"=>"ein TestEintrag"));
			}
		//schreibe einen weiteren GBE.
			$this->post($this->baseUrl.INSERT_GUESTBOOK_URL."/".USERID2."?__token=".$urlParam["__token"],array("data"=>"noch ein TestEintrag"));
		//überprüfe die GBE (Content wird überprüft)
			$gbEntries = $this->getElements("div",array("id"=>GUESTBOOK_ENTRY_CONTAINER_ID));
			$newestEntry = $this->getElements("div",array("class"=>GUESTBOOK_CONTENT_CLASS,"innerText ="=>"noch ein TestEintrag"),array($gbEntries[0]),false);
			$secondNewestEntry = $this->getElements("div",array("class"=>GUESTBOOK_CONTENT_CLASS,"innerText ="=>"ein TestEintrag"),array($gbEntries[1]),false);
			$this->assertNotEqual(count($newestEntry),0);
			$this->assertNotEqual(count($secondNewestEntry),0);
		//überprüfe den Link in den GBE zum Profil des eintragendens -> also zum eigenen.
			$this->followLink(array("href"=>$this->baseUrl.PROFILE_URL),$newestEntry);
		//teste die Pager Funktion des GB es muss 2 Seiten geben da wir x posts getätigt haben
			$this->post($this->baseUrl.GET_GUESTBOOK_URL."/".USERID2."/2"."?__token=".$urlParam["__token"]);
			$gbEntries = $this->getElements("div",array("id"=>GUESTBOOK_ENTRY_CONTAINER_ID));
		//es muss einen Post mit meinem Content auf der zweiten Seite geben
			$entry = $this->getElements("div",array("class"=>GUESTBOOK_CONTENT_CLASS,"innerText ="=>"ein TestEintrag"),array($gbEntries[0]),false);
			$this->assertNotEqual(count($entry),0);
		//reportet den Post
			$containerIds = $this->getAttributes("id",$entry);
			$entryID = substr($containerIds[0],strlen(GUESTBOOK_ENTRY_CONTAINER_ID));
			$this->assertHasElement("a",array("onclick"=>GUESTBOOK_REPORT_ONCLICK),$entry);
			$this->post($this->baseUrl.REPORT_GUESTBOOK_URL."/".USERID2."/".$entryID."?__token=".$urlParam["__token"]);
			//lade erneut und teste vorhanden sein des report GB buttons
			$this->post($this->baseUrl.GET_GUESTBOOK_URL."/".USERID2."/2"."?__token=".$urlParam["__token"]);
			$gbEntries = $this->getElements("div",array("id"=>GUESTBOOK_ENTRY_CONTAINER_ID));
			$entry = $this->getElements("div",array("class"=>GUESTBOOK_CONTENT_CLASS,"innerText ="=>"ein TestEintrag"),array($gbEntries[0]),false);
			$this->assertNotHasElement("a",array("onclick"=>GUESTBOOK_REPORT_ONCLICK),$entry);
		//lösche den Post
			$containerIds = $this->getAttributes("id",$entry);
			$entryID = substr($containerIds[0],strlen(GUESTBOOK_ENTRY_CONTAINER_ID));
			$this->post($this->baseUrl.DELETE_GUESTBOOK_URL."/".$entryID."?__token=".$urlParam["__token"]);
		//lösche restlichen Posts
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$gbEntries = $this->getElements("div",array("id"=>GUESTBOOK_ENTRY_CONTAINER_ID));
			$containerIds = $this->getAttributes("id",$gbEntries);
			foreach($containerIds as $containerId){
				$entryID = substr($containerId,strlen(GUESTBOOK_ENTRY_CONTAINER_ID));
				$this->post($this->baseUrl.DELETE_GUESTBOOK_URL."/".$entryID."?__token=".$urlParam["__token"]);
			}
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$gbEntries = $this->getElements("div",array("id"=>GUESTBOOK_ENTRY_CONTAINER_ID));
			//in den GBE sind keine Einträge von Account2 mehr dabei
			$this->assertNotHasElement("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$gbEntries);
		//ausloggen
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}
	function testBuddies(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		//einloggen leerer Account
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//gehe zur Suche und Suche Account Spalte in der result Liste
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
		//folge dem Link des results zur Buddyanfrage
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$resultLine);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_ASK_URL);
			//weiter ohne Nickname
			$mainColumn = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$mainColumn);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
		//entferne eigene Buddyanfrage
			$mainTables = $this->getElements("table",array("class"=>BUDDIES_INVITATION_TABLE_CLASS));
			$buddyRow = $this->getElements("tr",array("innerText"=>NICKNAME),$mainTables);
			$this->assertNotEqual(count($buddyRow),0);
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL),$buddyRow);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
		//$this->sendMessage($this->_browser->getContent());
		//lade ihn nochmals ein
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$resultLine);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_ASK_URL);
			$mainColumn = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$mainColumn);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
		//invite other
			//Suche Account2 und teste den Link im Result zum Profil
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID2));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID2),$resultLines,false);
			$this->followLink(array("href"=>$this->baseUrl.PROFILE_URL),$resultLine,0);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
		//$this->sendMessage($this->_browser->getContent());
		//suche in der Hauptseite des Profils nach dem ersten BUDDY_ASK und führe es aus
			$mainColumn = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$mainColumn,0);
			//ohne Nickname weiter
			$mainColumn = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ASK_URL),$mainColumn);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
		//überprüfe ob beide eingeladen wurden
			$mainTables = $this->getElements("table",array("class"=>BUDDIES_INVITATION_TABLE_CLASS));
			$this->assertEqual(count($mainTables),2);
			$mainTable = $this->getElements("tr",array("innerText"=>NICKNAME2),$mainTables,false);
			$this->assertNotEqual(count($mainTable),0);
			$mainTable = $this->getElements("tr",array("innerText"=>NICKNAME),$mainTables,false);
			$this->assertNotEqual(count($mainTable),0);
			//$this->sendMessage($this->_browser->getContent());
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//überprüfe/akzeptiere Einladung von User_Empty
			$this->get($this->baseUrl.BUDDIES_INVITATION_URL);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
			$mainTables = $this->getElements("table",array("class"=>BUDDIES_INVITATION_TABLE_CLASS));
			$buddyRow = $this->getElements("tr",array("innerText"=>NICKNAME_EMPTY),$mainTables);
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ACCEPT_URL),$buddyRow);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
			//$this->sendMessage($this->_browser->getContent());
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD),null,0);
		//überprüfe/akzeptiere Einladung von User_Empty
			$this->get($this->baseUrl.BUDDIES_INVITATION_URL);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
			$mainTables = $this->getElements("table",array("class"=>BUDDIES_INVITATION_TABLE_CLASS));
			$buddyRow = $this->getElements("tr",array("innerText"=>NICKNAME_EMPTY),$mainTables);
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_ACCEPT_URL),$buddyRow);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_INVITATION_URL);
		//überprüf und entferne Freundschaft zu Empty mit PM benachrichtigung !
			$this->get($this->baseUrl.BUDDIES_URL);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_URL);
			$friendsBeforeRemove = $this->getElements("a",array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL));
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL."/".USERID_EMPTY),null,0);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_URL);
			$friendsAfterRemove = $this->getElements("a",array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL));
			$this->assertEqual(count($friendsBeforeRemove),count($friendsAfterRemove)+2);
		//$this->sendMessage($this->_browser->getContent());
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als User_empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//überprüfe ob Account2 Freund ist.
			$this->get($this->baseUrl.BUDDIES_URL);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_URL);
			$this->assertHasElement("a",array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL."/".USERID2));
		//ausloggen
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}
	function testMessages(){
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->sendMessage("<hr>");
		//einloggen als Empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//zu den Messages gehn und überprüfen ob read button/ und inhalt der MSGs passt.
			$this->get($this->baseUrl.MESSAGES_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$messages = $this->getElements("a",array("href"=>$this->baseUrl.MESSAGES_READ_URL));
			//überprüfe die neuerFreund Message und die Freundschaft entfernen Nachricht
			while(count($messages) >0){
				$this->followLink(array("href"=>$this->baseUrl.MESSAGES_READ_URL),null,0);
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
				//print_r($this->_browser->getContent());
				$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REMOVE_URL),null,0);
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
				$messages = $this->getElements("a",array("href"=>$this->baseUrl.MESSAGES_READ_URL));
			}
		//gehe zum Profil und teste den ersten Msg write link auf der Main seite == der des Profils
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainColumn = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_WRITE_URL),$mainColumn,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_WRITE_URL);
		//teste schreiben einer Nachricht an diesen User
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage1",MESSAGES_FORM_CONTEXT_NAME=>"test test test",MESSAGES_FORM_RECIPIENT_NAME=>array(USERID2)),null,1);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
		//teste schreiben einer Nachricht wenn kein User ausgewählt wurde
			$this->get($this->baseUrl.MESSAGES_WRITE_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_WRITE_URL);
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage",MESSAGES_FORM_CONTEXT_NAME=>"test test test"),null,1);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_WRITE_URL);
		//teste schreiben einer Nachricht wenn Textinhalt fehlt
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage",MESSAGES_FORM_CONTEXT_NAME=>"",MESSAGES_FORM_RECIPIENT_NAME=>array(USERID2)),null,1);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_WRITE_URL);
		//teste schreiben einer Nachricht an mich selbst
			$this->get($this->baseUrl.MESSAGES_WRITE_URL);
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage2",MESSAGES_FORM_CONTEXT_NAME=>"test test test",MESSAGES_FORM_RECIPIENT_NAME=>array(USERID_EMPTY)),null,1);
		//teste schreiben einer Nachricht an mich selbst und eine weitere Person
			$this->get($this->baseUrl.MESSAGES_WRITE_URL);
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage3",MESSAGES_FORM_CONTEXT_NAME=>"test test test",MESSAGES_FORM_RECIPIENT_NAME=>array(USERID_EMPTY,USERID2)),null,1);
		//teste schreiben einer Nachricht an mich selbst und zwei weitere Person
			$this->get($this->baseUrl.MESSAGES_WRITE_URL);
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL),array(MESSAGES_FORM_SUBJECT_NAME=>"testMessage4",MESSAGES_FORM_CONTEXT_NAME=>"test test test",MESSAGES_FORM_RECIPIENT_NAME=>array(USERID_EMPTY,USERID2,USERID)),null,1);
		//gehe zu sent Mails und überprüfe Anzahl versendeter Mails
			$this->get($this->baseUrl.MESSAGES_SENT_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			$this->assertEqual(count($messages),4);
		//teste readbutton der ersten Mail und inhalt
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_READ_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->assertText("test test test");
			$this->assertText("testMessage4");
		//teste Zapper innerhalb der ReadMsg und Inhalt der Mails auf Fehler
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_ZAPPER_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_ZAPPER_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_ZAPPER_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
		//teste Entfernen aller MSGs via entfern-checkboxen im POSTausgang
			$this->get($this->baseUrl.MESSAGES_SENT_URL);
			$messagesBefore = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			$checkbox = $this->getElements("input",array("type"=>"checkbox","name"=>MESSAGES_DELETE_FORM_CHECKBOX_NAME));
			$this->assertTrue($this->setAttributes($checkbox,array("checked"=>"checked")));
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_REMOVE_URL),array(MESSAGES_DELETE_FORM_SELECT_NAME=>"delete"));
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
			$messagesAfter= $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			$this->assertNotEqual(count($messagesBefore),count($messagesAfter));
			$this->assertTrue(count($messagesAfter)==0);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Empfänger der MSGs
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//überprüfe eingehende Nachrichten
			$this->get($this->baseUrl.MESSAGES_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_INCOME_TABLE_ROW_CLASS));
			$myMsgs = $this->getElements("td",array("class"=>MESSAGES_INCOME_COLUMN_NAME_CLASS,"innerText"=>NICKNAME_EMPTY),$messages,false);
			$this->assertTrue(count($myMsgs)>=3,count($myMsgs).' von '.count($messages).' Msgs von mir enthalten ');
			//teste einen Inhalt
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_READ_URL),$myMsgs,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->assertText("test test test");
			$this->assertText("testMessage4");
		//teste/antworten mit erster Nachricht. schreibe öfters als Pager groß ist
			$this->get($this->baseUrl.MESSAGES_URL);
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_INCOME_TABLE_ROW_CLASS));
			for($x=0;$x<=MESSAGES_ENTRIES_PER_PAGE;$x++){
				$this->followLink(array("href"=>$this->baseUrl.MESSAGES_WRITE_URL),$messages,0);
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_WRITE_URL);
				$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_WRITE_URL));
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
			}
		//entferne Messages via checkbox im Posteingang
			$this->get($this->baseUrl.MESSAGES_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$messagesBefore = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS,"innerText"=>NICKNAME_EMPTY));
			$checkbox = $this->getElements("input",array("type"=>"checkbox","name"=>MESSAGES_DELETE_FORM_CHECKBOX_NAME));
			$this->assertTrue($this->setAttributes($checkbox,array("checked"=>"checked")));
			$this->submitForm(array("action"=>$this->baseUrl.MESSAGES_REMOVE_URL),array(MESSAGES_DELETE_FORM_SELECT_NAME=>"delete"));
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$messagesAfter= $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS,"innerText"=>NICKNAME_EMPTY));
			$this->assertNotEqual($messagesBefore,$messagesAfter);
		//teste/benutze den Pager im Postausgang
			$this->get($this->baseUrl.MESSAGES_SENT_URL."/2");
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
		//teste entfern Button im Read-Message für PostausgansMSGs
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_READ_URL),$messages,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REMOVE_URL));
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
		//teste Löschen aller MSGs im Postausgang mit Lösch button
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			foreach($messages as $message){
				$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REMOVE_URL),array($message));
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_SENT_URL);
			}
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS));
			$this->assertEqual(count($messages),0,"Es wurden nicht alle gesendeten Nachrichten gelöscht ".count($messages)." noch übrig");
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//überprüfe Posteingangs-Pager(mehr als Pager_nachrichten) und clicke auf 2.Page
			$this->get($this->baseUrl.MESSAGES_URL);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$pager = $this->getElements("div",array("class"=>PAGER_CONTAINER_CLASS));
			$pagerText = $this->getElements("span",array("class"=>PAGER_TEXT),$pager);
			$pagerText0 = $this->getAttributes("innerText",$pagerText);
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_URL),$pager,1);
			$pager = $this->getElements("span",array("class"=>PAGER_TEXT));
			$pagerText1 = $this->getAttributes("innerText",$pager);
			$this->assertNotEqual($pagerText0[0],$pagerText1[0]);
		//klicke auf Income msg der 2.Seite
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_INCOME_TABLE_ROW_CLASS));
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_READ_URL),$messages,0);
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
		//melde die Nachricht
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REPORT_URL,"class in"=>MESSAGES_REPORT_CLASS));
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_READ_URL);
			$this->assertNotHasElement("a",array("href"=>$this->baseUrl.MESSAGES_REPORT_URL,"class in"=>MESSAGES_REPORT_CLASS));
		//lösche die Nachricht aus Message read des Posteingangs
			$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REMOVE_URL));
			$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			$pager = $this->getElements("span",array("class"=>PAGER_TEXT));
			$pagerText2 = $this->getAttributes("innerText",$pager);
			$this->assertNotEqual($pagerText1[0],$pagerText2[0]);
		//lösche sämtliche Nachrichten des Posteingangs der 1.Seite mit dem Lösch button
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_INCOME_TABLE_ROW_CLASS));
			$this->assertNotEqual(count($messages),0);
			foreach($messages as $message){
				$this->followLink(array("href"=>$this->baseUrl.MESSAGES_REMOVE_URL),array($message));
				$this->assertLandOnUrl($this->baseUrl.MESSAGES_URL);
			}
			$messages = $this->getElements("tr",array("class in"=>MESSAGES_OUT_TABLE_ROW_CLASS,"innerText"=>"TestMessage"));
			$this->assertEqual(count($messages),0,"Es wurden nicht alle gesendeten Nachrichten gelöscht ".count($messages)." noch übrig");
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}
	function testSetData(){
		$this->sendMessage("<hr>");
		if(!in_array(__FUNCTION__,$this->all)){return;}
		//einloggen als Empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//den link zum Profil finden und benutzen
			$this->followLink(array("href"=>$this->baseUrl.PROFILE_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
		//den link zum Profil bearbeiten finden und benutzen
			$this->followLink(array("href"=>$this->baseUrl.PERSONAL_URL),null,0);
			$this->assertLandOnUrl($this->baseUrl.PERSONAL_URL);
		//die persönlichen Profildaten bearbeiten
			$this->submitForm(array("action"=>$this->baseUrl.PERSONAL_URL),array(#
			PERSONAL_FORM_VORNAME_NAME=>TESTCHARS,
			PERSONAL_FORM_NACHNAME_NAME=>TESTCHARS,
			PERSONAL_FORM_BIRTHDAY_NAME=>1,
			PERSONAL_FORM_BIRTHMONTH_NAME=>1,
			PERSONAL_FORM_BIRTHYEAR_NAME=>2001,
			PERSONAL_FORM_GESCHLECHT_NAME=>1,
			PERSONAL_FORM_LAND_NAME=>TESTCHARS,
			PERSONAL_FORM_STADT_NAME=>TESTCHARS,
			PERSONAL_FORM_SPRACHE_NAME=>"de",
			PERSONAL_FORM_SPRACHE2_NAME=>array("en"),
			PERSONAL_FORM_HOMEPAGE_NAME=>TESTCHARS,
			PERSONAL_FORM_IMS_NAME=>array("icq"),
			PERSONAL_FORM_IMSVALUE_NAME=>array(TESTCHARS),
			PERSONAL_FORM_ABOUT_NAME=>TESTCHARS
			));
			$this->assertLandOnUrl($this->baseUrl.PERSONAL_URL);
		//avatar
		//den Avatar bearbeiten und änderung testen
			$this->followLink(array("href"=>$this->baseUrl.AVATAR_URL));
			$this->assertLandOnUrl($this->baseUrl.AVATAR_URL);
			$this->submitForm(array("enctype !"=>"multipart","action"=>$this->baseUrl.AVATAR_URL),array(PERSONAL_FORM_AVATAR_NAME=>PERSONAL_FORM_AVATAR_VALUE2));
			$this->assertLandOnUrl($this->baseUrl.AVATAR_URL);
			$before = $this->getElements("img",array("src"=>PERSONAL_FORM_IMG_SRC));
			$this->submitForm(array("enctype !"=>"multipart","action"=>$this->baseUrl.AVATAR_URL),array(PERSONAL_FORM_AVATAR_NAME=>PERSONAL_FORM_AVATAR_VALUE));
			$this->assertLandOnUrl($this->baseUrl.AVATAR_URL);
			$after = $this->getElements("img",array("src"=>PERSONAL_FORM_IMG_SRC));
			$this->assertEqual(count($before)+2,(count($after)));
		//die DirektEdit funktion des Profils testen
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS));
		//test der vorherigen Eingaben
			$this->assertEqual(count($contents),7);
		//teste einzelne Direkt eingaben
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_VORNAME_NAME=>TESTCHARS2,PERSONAL_FORM_VORNAME_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),1);
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_NACHNAME_NAME=>TESTCHARS2,PERSONAL_FORM_NACHNAME_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),2);
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_LAND_NAME=>TESTCHARS2,PERSONAL_FORM_LAND_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),3);
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_STADT_NAME=>TESTCHARS2,PERSONAL_FORM_STADT_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),4);
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_HOMEPAGE_NAME=>TESTCHARS2,PERSONAL_FORM_HOMEPAGE_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),5);
			$this->submitForm(array("action"=>$this->baseUrl.DIREKT_EDIT_PROFILE_URL),array(PERSONAL_FORM_ABOUT_NAME=>TESTCHARS2,PERSONAL_FORM_ABOUT_NAME."Set"=>"1"));
			$contents = $this->getElements("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS));
			$this->assertEqual(count($contents),6);
		//teste eigenes Profil auf Daten
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS),$mainFrame);
			$this->assertHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS),$mainFrame);
			$this->assertHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS),$mainFrame);
			$this->assertHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}

	function testSecurity(){
		$this->sendMessage("<hr>");
		if(!in_array(__FUNCTION__,$this->all)){return;}
		//einloggen als Empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//zum Profilbearbeiten(Privacy)
		$this->get($this->baseUrl.PRIVACY_URL);
		$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		//alle Radioboxen auf Privacy 0 setzen
			$radioboxes = $this->getElements("input",array("type"=>"radio"));
			//entfernen der orginal Privacy Setting
			$this->assertTrue($this->setAttributes($radioboxes,array("checked"=>false)));
			$rows = $this->getElements("input",array("type"=>"radio"),$this->getElements("tr"),false);
			$this->AssertNotEqual(count($rows),0);
			//pro Spalte den ersten Radiobox checken
			foreach($rows as $row){
				$radios = $this->getElements("input",array("type"=>"radio"),array($row));
				$this->assertTrue($this->setAttributes(array($radios[0]),array("checked"=>"checked")));
			}
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL));
		//überprüfe veränderte Einstellung
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$rows = $this->getElements("input",array("type"=>"radio"),$this->getElements("tr"),false);
			foreach($rows as $row){
				$radios = $this->getElements("input",array("type"=>"radio"),array($row));
				$checkedValues = $this->getAttributes("checked",$radios);
				$this->assertEqual($checkedValues[0],"checked");
			}
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//suche berücksichtigt derzeit nicht partial offene Privacies für Suchende die das Profile sehen dürfen.
		//Suche nach Privaten Einstellungen mit eigenem Account
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_FIRSTNAME_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_LASTNAME_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_COUNTRY_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_CITY_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertHasElement("td",array("innerText"=>NICKNAME_EMPTY));
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//Suche nach Privaten Einstellungen mit fremden Account
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_FIRSTNAME_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertNotHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_LASTNAME_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertNotHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_COUNTRY_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertNotHasElement("td",array("innerText"=>NICKNAME_EMPTY));
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_CITY_FIELD=>TESTCHARS2));
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->assertNotHasElement("td",array("innerText"=>NICKNAME_EMPTY));
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//ausgelogt die Profildaten testen (volle privacy an)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertNotHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertNotHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//einloggen als irgendein Account
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD),null,0);
		//test als irgendein Account die Profildaten (volle privacy an)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertNotHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertNotHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//irgendein Account sucht nach Emptyuser anhand der uid und Testet die Privacy (volle privacy an)
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID_EMPTY));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID_EMPTY),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$resultLine);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2 = Freund
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//teste als Freund die Profildaten(volle privacy an)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertNotHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertNotHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),0);
		//Freund sucht nach Emptyuser anhand der uid und Testet die Privacy (volle privacy an)
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID_EMPTY));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID_EMPTY),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$resultLine);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Empty
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//alle Radioboxen auf Privacy 1 setzen
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$radioboxes = $this->getElements("input",array("type"=>"radio"));
			//checkboxen erstmal leeren
			$this->assertTrue($this->setAttributes($radioboxes,array("checked"=>false)));
			$rows = $this->getElements("input",array("type"=>"radio"),$this->getElements("tr"),false);
			$this->AssertNotEqual(count($rows),0);
			//in jeder Zeile die 2. checkbox anklicken
			foreach($rows as $row){
				$radios = $this->getElements("input",array("type"=>"radio"),array($row));
				$this->assertTrue($this->setAttributes(array($radios[1]),array("checked"=>"checked")));
			}
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_ADD_BUDDY=>2));
		//teste die neuen Privacy Einstellungen
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$rows = $this->getElements("input",array("type"=>"radio","name !"=>PRIVACY_ADD_BUDDY),$this->getElements("tr"),false);
			foreach($rows as $row){
				$radios = $this->getElements("input",array("type"=>"radio"),array($row));
				$checkedValues = $this->getAttributes("checked",$radios);
				$this->assertEqual($checkedValues[1],"checked");
			}
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//ausgelogt die Profildaten testen (privacy für Freunde)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertNotHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertNotHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//einloggen als irgendein User
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD),null,0);
		//test als irgendein Account die Profildaten (privacy für Freunde)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertNotHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertNotHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertNotHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//irgendein Account sucht nach Emptyuser anhand der uid und Testet die Privacy (privacy für Freunde)
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID_EMPTY));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID_EMPTY),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$this->assertHasElement("img",array("src"=>DEFAULT_AVATAR),$resultLine);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2 = Freund
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//test als Freund die Profildaten (privacy für Freunde)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$mainFrame = $this->getElements("div",array("class"=>MAIN_COLUMN_CLASS));
			$avatar = $this->getElements("div",array("class"=>PROFILE_AVATAR_CONTAINER),$mainFrame);
			$this->assertHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$avatar);
			$this->assertNotHasElement("img",array("src"=>DEFAULT_AVATAR),$avatar);
			$this->assertHasElement("div",array("innerText"=>TESTCHARS2,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertHasElement("div",array("innerText"=>TESTCHARS,"class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertHasElement("div",array("innerText"=>"01.01.2001"),$mainFrame);
			$this->assertHasElement("div",array("innerText ="=>"M","class"=>PROFILE_DATACONTENT_CLASS2),$mainFrame);
			$this->assertHasElement("h4",array("innerText"=>GUESTBOOK_HEADLINE),$mainFrame);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$mainFrame);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$mainFrame);
		//teste FreundesBox des Accounts2
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
		//token holen
			$form = $this->getElements("form");
			$attributeValues = $this->getAttributes("action",$form);
			$urlParam = $this->getUrlParameter($attributeValues[0]);
			$this->assertTrue(isset($urlParam["__token"]));
		//überprüfe den Freund Empty in der FreundesBox als Freund auf korrekte Privacy (privacy für Freunde)
		//suche auf bis zu 10 pager seiten nach Empty als Freund
		$after = "";
		for($x=0;$x<10;$x++){
			$this->post($this->baseUrl.GET_FRIENDS_URL."/".USERID2."/".$x."?__token=".$urlParam["__token"]);
			$before = $this->_browser->getContent();
			//breche ab wenn user nicht in Liste ist
			if($before == $after){break;}
			$after = $this->_browser->getContent();
			$a = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID_EMPTY));
			if(count($a)>0){
				$li = $this->getElements("li",null);
				$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID_EMPTY),$li,false);
				$this->assertEqual(count($resultLine),1);
				$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
				$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
				$this->assertNotHasElement("img",array("src"=>DEFAULT_AVATAR),$resultLine);
				$this->assertHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$resultLine);
				break;
			}
		}
		//Freund sucht nach Emptyuser anhand der uid und Testet die Privacy (privacy für Freunde)
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID_EMPTY));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID_EMPTY),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$this->assertNotHasElement("img",array("src"=>DEFAULT_AVATAR),$resultLine);
			$this->assertHasElement("img",array("src"=>PERSONAL_FORM_AVATAR_VALUE),$resultLine);
		//Account2 mit Games setzt Privacy auf sichtbare FreundesBox und die Privacy für Games,MSG und buddyanfragen auf verboten
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_SHOW_BUDDIES=>3,PRIVACY_SHOW_GAMES=>1,PRIVACY_ADD_BUDDY=>1,PRIVACY_RECIVE_MSG=>1));
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//anmelden als EMPTY
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//freundesBox auf für alle sichtbar setzen
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_SHOW_BUDDIES=>3));
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		//teste FreundesBox auf User Account2 und teste die Enthaltenen Privacy (nicht vorhandensein von Games!)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//teste das Profil von Account2 auf nicht vorhandensein von Games
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertNotHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));
		//teste Suchergebniss von Account2 auf Privacy nicht vorhanden sein von Games
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID2));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID2),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$games = $this->getElements("td",array("class"=>GAME_ICONS_CONTAINER_CLASS),$resultLine);
			$this->assertEqual(count($games),1);
			$this->assertNotHasElement("a",null,$games);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//teste ausgelogt die SidebarBox des Profiles EMPTY (sichtbar) und überprüfe Privacy
		$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
		$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//teste ausgeloggt das vorhandensein von Games auf dem Profile Account2(disabled)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertNotHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//Setze die Privacy von Account2 auf Freunde für Games,MSG und Buddies
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_SHOW_BUDDIES=>3,PRIVACY_SHOW_GAMES=>2,PRIVACY_ADD_BUDDY=>2,PRIVACY_RECIVE_MSG=>2));
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Empty = Freund
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		//überPrüfe als Freund die Privacy des Freundes in der FreundesBox (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//überPrüfe vorhanden sein der Games im Profil des Freundes (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));
		//überprüfe vorhanden sein der Games/privacy im Ergebniss der Suche nach Freund(privacy for Friends)
			$this->get($this->baseUrl.SEARCH_URL);
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID2));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID2),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$games = $this->getElements("td",array("class"=>GAME_ICONS_CONTAINER_CLASS),$resultLine);
			$this->assertEqual(count($games),1);
			$this->assertHasElement("a",null,$games);
		//ausloggen
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//überPrüfe ausgeloggt die Privacy des Accounts2 in der FreundesBox von Account Empty (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//überprüfe ausgeloggt das Profil des Accounts2 auf Games (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertNotHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));
		//einloggen als Account = Fremder
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD),null,0);
		//überPrüfe als Fremder die Privacy des Accounts2 in der FreundesBox von Account Empty (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//überprüfe als Fremder das Profil des Accounts2 auf Games (privacy for Friends)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertNotHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));$this->get($this->baseUrl.SEARCH_URL);
		//überprüfe als Fremder das Ergebniss der Suche nach Account2 (Gameprivacy for Friends)
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID2));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID2),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertNotHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertNotHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$games = $this->getElements("td",array("class"=>GAME_ICONS_CONTAINER_CLASS),$resultLine);
			$this->assertEqual(count($games),1);
			$this->assertNotHasElement("a",null,$games);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//einloggen als Account2
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME2,LOGIN_FORM_PASSWORD_NAME=>PASSWORD2),null,0);
		//Setze Privacy  Games,Msg,Buddy für alle sichtbar
			$this->get($this->baseUrl.PRIVACY_URL);
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
			$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_SHOW_BUDDIES=>3,PRIVACY_SHOW_GAMES=>3,PRIVACY_ADD_BUDDY=>3,PRIVACY_RECIVE_MSG=>3));
			$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//überprüfe ausgeloggt die FreundesBox von Account Empty nach der Privacy von Account2 (privacy offen)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//überprüfe ausgeloggt das Profil von Account2 auf Games (privacy offen)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));
		//einloggen als Fremder
			$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME,LOGIN_FORM_PASSWORD_NAME=>PASSWORD),null,0);
		//überprüfe als Fremder die FreundesBox von Account Empty nach der Privacy von Account2 (privacy offen)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID_EMPTY);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$sidebarBox =$this->getElements("div",array("class"=>SIDEBAR_BOX_CLASS));
			$friendsBox = $this->getElements("h2",array("innerText"=>SIDEBAR_FRIENDS_HEADLINE),$sidebarBox,false);
			$this->assertEqual(count($friendsBox),1);
			$li = $this->getElements("li",null,$friendsBox);
			$resultLine = $this->getElements("a",array("href"=>$this->baseUrl.PROFILE_URL."/".USERID2),$li,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("img",array("class"=>FRIENDS_GAMES_IMG_CLASS),$resultLine);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
		//überprüfe als Fremder das Profil von Account2 auf Games (privacy offen)
			$this->get($this->baseUrl.PROFILE_URL."/".USERID2);
			$this->assertLandOnUrl($this->baseUrl.PROFILE_URL);
			$this->assertHasElement("h4",array("innerText"=>PROFILE_GAMES_HEADLINE));$this->get($this->baseUrl.SEARCH_URL);
		//überprüfe als Fremder das Suchergebniss von Account2 (privacy offen)
			$this->assertLandOnUrl($this->baseUrl.SEARCH_URL);
			$this->submitForm(array("action"=>$this->baseUrl.SEARCH_URL),array(SEARCH_FORM_SEARCH_FIELD=>USERID2));
			$resultLines = $this->getElements("tr",array("class"=>SEARCH_LINE_CONTAINER_CLASS));
			$resultLine = $this->getElements("td",array("class"=>SEARCH_CONTENT_MMOID_CLASS,"innerText ="=>USERID2),$resultLines,false);
			$this->assertEqual(count($resultLine),1);
			$this->assertHasElement("a",array("href"=>MESSAGES_WRITE_URL),$resultLine);
			$this->assertHasElement("a",array("href"=>BUDDIES_ASK_URL),$resultLine);
			$games = $this->getElements("td",array("class"=>GAME_ICONS_CONTAINER_CLASS),$resultLine);
			$this->assertEqual(count($games),1);
			$this->assertHasElement("a",null,$games);
		//ausloggen
			$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
			$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
	}

	function testClearTestUser(){
		//aufräumen der Accounts gehört nicht mehr zum Test wird nur nicht ausgeführt wenn ich nicht test vor die Funktionschreibe.
		//alle Buddies entfernen
		$this->sendMessage("<hr>");
		if(!in_array(__FUNCTION__,$this->all)){return;}
		$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		$this->get($this->baseUrl.BUDDIES_URL);
		$this->assertLandOnUrl($this->baseUrl.BUDDIES_URL);
		$friends = $this->getElements("a",array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL));
		while(count($friends)>0){
			$this->followLink(array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL."/".USERID2),null,1);
			$this->assertLandOnUrl($this->baseUrl.BUDDIES_URL);
			$friends = $this->getElements("a",array("href"=>$this->baseUrl.BUDDIES_REMOVE_URL));
		}
		$this->followLink(array("href"=>$this->baseUrl.LOGOUT_URL));
		$this->assertLandOnUrl($this->baseUrl.LOGOUT_URL);
		//Privacy wieder auf offen setzen
		$this->submitForm(array("action"=>$this->baseUrl.LOGIN_FORM_URL),array(LOGIN_FORM_USERNAME_NAME=>USERNAME_EMPTY,LOGIN_FORM_PASSWORD_NAME=>PASSWORD_EMPTY),null,0);
		$this->get($this->baseUrl.PRIVACY_URL);
		$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		$radioboxes = $this->getElements("input",array("type"=>"radio"));
		$this->assertTrue($this->setAttributes($radioboxes,array("checked"=>false)));
		$rows = $this->getElements("input",array("type"=>"radio","name !"=>PRIVACY_ADD_BUDDY),$this->getElements("tr"),false);
		$this->AssertNotEqual(count($rows),0);
		foreach($rows as $row){
			$radios = $this->getElements("input",array("type"=>"radio"),array($row));
			if(isset($radios[2])){
				$this->assertTrue($this->setAttributes(array($radios[2]),array("checked"=>"checked")));
			}
		}
		$this->submitForm(array("action"=>$this->baseUrl.PRIVACY_URL),array(PRIVACY_ADD_BUDDY=>3));
		$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		$this->get($this->baseUrl.PRIVACY_URL);
		//ausloggen
		$this->assertLandOnUrl($this->baseUrl.PRIVACY_URL);
		$this->sendMessage($this->_browser->getContent());
	}
}
?>