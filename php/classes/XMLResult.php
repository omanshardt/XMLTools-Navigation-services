<?php
	class XMLResult implements iXMLProvider {
		protected static $dbhost;
		protected static $dbuser;
		protected static $dbpassword;
		protected static $dbname;
		protected static $lc_time_names = 'de_DE';

		protected $instanceDbhost = null;
		protected $instanceDbuser = null;
		protected $instanceDbpassword = null;
		protected $instanceDbname = null;

		protected $db;
		protected $query;
		protected $numrows;

		protected $parentQueryObj = null;
		protected $subQueryObjects = array();

		protected $encoding = "UTF-8"; //iso-8859-1
		protected $contentTypeDeclaration = false;
		protected $queryHeader = false;
		protected $fieldInformation = false;
		protected $rootElementName = "document";
		protected $recordName = "record";
		protected $keys = array();
		protected $doc = null;
		protected $fieldsAsAttributes = false;
		protected $fieldsAsElements = true;
		protected $fieldsWithCDATA = array();
		protected $fieldsWithStrippedTags = array();
		protected $omitRootElement = false;

		protected static $gb_data;
		protected $dataArray = array();

		protected static $counter = 0;
		protected $id;
		private static $instances = array();

		protected $mysqli;

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Static Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		public static function setConnection($dbhost = '', $dbuser = '', $dbpassword = '', $dbname = null) {
			self::$dbhost = $dbhost;
			self::$dbuser = $dbuser;
			self::$dbpassword = $dbpassword;
			self::$dbname = $dbname;
		}

		public static function getInstance($query=null, $dbhost=null, $dbuser=null, $dbpassword=null, $dbname=null) {
			self::$counter ++;
			return new XMLResult($query, $dbhost, $dbuser, $dbpassword, $dbname);
		}

		public static function setLocale($lc_time_names) {
			self::$lc_time_names = $lc_time_names;
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Static Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Private / Protected Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		protected function __construct($query, $dbhost, $dbuser, $dbpassword, $dbname) {
			if ($query !== null) $this->query = $query;
			if ($dbhost !== null && $dbuser !== null && $dbpassword !== null) {
				$this->setInstanceConnection($dbhost, $dbuser, $dbpassword, $dbname);
			}
			$this->id = self::$counter;
		}

		private function __clone() {
		}

		protected function DBConnect() {
			try
			{
				if ($this->instanceDbhost !== null && $this->instanceDbuser !== null && $this->instanceDbpassword != null) {
					$dbhost = substr($this->instanceDbhost, 0, strpos($this->instanceDbhost,':'));
					$dbport = (strpos(self::$dbhost,':') > -1) ? substr($this->instanceDbhost, strpos($this->instanceDbhost,':') + 1) : '3306';
					$dbuser = $this->instanceDbuser; $dbpassword = $this->instanceDbpassword; $dbname = $this->instanceDbname;
				}
				else {
					$dbhost = substr(self::$dbhost, 0, strpos(self::$dbhost,':'));
					$dbport = (strpos(self::$dbhost,':') > -1) ? substr(self::$dbhost, strpos(self::$dbhost,':') + 1) : '3306';
					$dbuser = self::$dbuser; $dbpassword = self::$dbpassword; $dbname = self::$dbname;
				}
				$dbport = (strlen($dbport) > 2) ? $dbport : 3306;
				$this->mysqli = new mysqli($dbhost, $dbuser, $dbpassword, $dbname, $dbport);
			}
			catch(Exception $e)
			{
				return false;
			}
		}

		protected function DBClose() {
			//mysql_close($conn);
			$this->mysqli->close();
		}

		protected function generateQueryHeader($res) {
			$df = $this->doc->createDocumentFragment();
			$message = ($this->mysqli->affected_rows == 1) ? 'Es wurden '.$this->mysqli->affected_rows.' Eintrag gefunden' : 'Es wurden '.$this->mysqli->affected_rows.' Einträge gefunden';
			$header = $this->doc->createElement("header");
			$query_infos = $this->doc->createElement("query_infos");
			$query_infos->appendChild($this->doc->createElement('query',$this->query));
			$query_infos->appendChild($this->doc->createElement('field_count',$res->field_count));
			$query_infos->appendChild($this->doc->createElement('num_rows',$res->num_rows));
			$query_infos->appendChild($this->doc->createElement('affected_rows',$this->mysqli->affected_rows));
			$query_infos->appendChild($this->doc->createElement('insert_id',$this->mysqli->insert_id));
			$query_infos->appendChild($this->doc->createElement('message',$message));

			$header->appendChild($query_infos);
			$df->appendChild($header);
			return $df;
		}

		protected function createDocument($res) {
			$this->doc = new DomDocument();
			$this->doc->formatOutput = true;
			$this->doc->encoding = $this->encoding;

			$df = $this->doc->createDocumentFragment();
			$root = $this->doc->createElement($this->rootElementName);
			if ($this->queryHeader) $df->appendChild($this->generateQueryHeader($res));
			if ($this->fieldsAsElements === true && $this->fieldsAsAttributes === false &&  count($this->keys) == 0 &&  count($this->fieldsWithCDATA) == 0)
			{
				$retdf = $this->simpleXMLTagsGenerator($res);
			}
			else if ($this->fieldsAsElements === false && $this->fieldsAsAttributes === true &&  count($this->keys) == 0 &&  count($this->fieldsWithCDATA) == 0)
			{
				$retdf = $this->simpleXMLAttributesGenerator($res);
			}
			else
			{
				$retdf = $this->complexXMLGenerator($res);
			}
			if ($retdf->hasChildNodes()) $df->appendChild($retdf);

			if ($this->omitRootElement === false)
			{
				if ($df->hasChildNodes()) $root->appendChild($df);
				$df->appendChild($root);
			}

			if ($df->hasChildNodes()) $this->doc->appendChild($df->cloneNode(true));
			return $df;
		}

		protected function simpleXMLTagsGenerator($res) {
			$df = $this->doc->createDocumentFragment();
			while($gb_data=$res->fetch_assoc())
			{
				$rec = $this->doc->createElement($this->recordName);
				while(list($key,$val) = each($gb_data))
				{
					if ((is_array($this->fieldsWithStrippedTags) && in_array($key,$this->fieldsWithStrippedTags))) $val = strip_tags($val);
					$txt = $this->doc->createTextNode($val);
					$field = $this->doc->createElement($key);
					$field->appendChild($txt);
					$rec->appendChild($field);
				}
				for($i = 0; $i < count($this->subQueryObjects); $i++)
				{
					$this->initSubQuery($rec ,$gb_data, $i);
				}
				$df->appendChild($rec);
			}
			return $df;
		}

		protected function simpleXMLAttributesGenerator($res) {
			$df = $this->doc->createDocumentFragment();
			while($gb_data=$res->fetch_assoc())
			{
				$rec = $this->doc->createElement($this->recordName);
				while(list($key,$val) = each($gb_data))
				{
					if ((is_array($this->fieldsWithStrippedTags) && in_array($key,$this->fieldsWithStrippedTags))) $val = strip_tags($val);
					$txt = $this->doc->createTextNode($val);
					$field = $this->doc->createAttribute($key);
					$field->appendChild($txt);
					$rec->appendChild($field);
				}
				for($i = 0; $i < count($this->subQueryObjects); $i++)
				{
					$this->initSubQuery($rec ,$gb_data, $i);
				}
				$df->appendChild($rec);
			}
			return $df;
		}

		protected function complexXMLGenerator($res) {
			$df = $this->doc->createDocumentFragment();
			while($gb_data=$res->fetch_assoc())
			{
				$rec = $this->doc->createElement($this->recordName);
				while(list($key,$val) = each($gb_data))
				{
					if ((is_array($this->fieldsWithStrippedTags) && in_array($key,$this->fieldsWithStrippedTags))) $val = strip_tags($val);
					$xmlFieldName = (isset($this->keys[$key])) ? $this->keys[$key] : $key;
					if ($this->fieldsAsAttributes === true || (is_array($this->fieldsAsAttributes) && in_array($key,$this->fieldsAsAttributes)))
					{
						$txt = $this->doc->createTextNode($val);
						$field = $this->doc->createAttribute($xmlFieldName);
						$field->appendChild($txt);
						$rec->appendChild($field);
					}
					if ($this->fieldsAsElements === true || (is_array($this->fieldsAsElements) && in_array($key,$this->fieldsAsElements)))
					{
						//$val = "echo <u>lustige Öre</u>";
						$txt = (in_array($key,$this->fieldsWithCDATA)) ? $this->doc->createCDATASection($val) : $this->doc->createTextNode($val);
						$field = $this->doc->createElement($xmlFieldName);
						$field->appendChild($txt);
						$rec->appendChild($field);
					}
				}
				for($i = 0; $i < count($this->subQueryObjects); $i++)
				{
					$this->initSubQuery($rec, $gb_data, $i);
				}
				$df->appendChild($rec);
			}
			return $df;
		}

		protected function initsubQuery($rec, $gb_data, $counter) {
			if (!isset($this->subQueryObjects[$counter][1]))
			{
				$this->subQueryObjects[$counter][1] = $this->subQueryObjects[$counter][0]->query;
			}
			$q = preg_replace_callback('/{(.*?)}/',
			function($matches) use ($gb_data) {
				return $gb_data[$matches[1]];
			},
			$this->subQueryObjects[$counter][1]);
			$df = $this->subQueryObjects[$counter][0]->executeQuery($q);
			if ($df->hasChildNodes()) $rec->appendChild($this->doc->importNode($df,true));
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Private / Protected Fuctions END */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Public Functions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		public function setInstanceConnection($dbhost, $dbuser, $dbpassword, $dbname = '') {
			$this->instanceDbhost = $dbhost;
			$this->instanceDbuser = $dbuser;
			$this->instanceDbpassword = $dbpassword;
			$this->instanceDbname = $dbname;
		}

		public function __tostring() {
			return 'XMLResult'.$this->id;
		}

		public function executeQuery($query=null) {
			if ($query !== null) $this->query = $query;
			$this->DBConnect();
			$this->mysqli->query("SET CHARACTER SET 'utf8'"); // Ausgabe-Codierung der DB festlegen
			$this->mysqli->query("SET @@lc_time_names = '".self::$lc_time_names."'", $this->db); // Ausgabe-Codierung der DB festlegen
			$res = $this->mysqli->query($this->query);
			
			while ($row = $res->fetch_assoc()) {
				$this->dataArray[] = $row;
			}
			if ($res->num_rows >= 1) {
				$res->data_seek(0);
			}

			$this->numrows = $res->num_rows;
			$df = $this->createDocument($res);
			$res->close();
			if ($this->parentQueryObj === null) $this->DBClose();
			return $df;
		}

		public function getNumRows() {
			return $this->numrows;
		}

		public function insertSubQuery(XMLResult $XMLResult) {
			$currentSubQuery = $this->subQueryObjects[][0] = $XMLResult;
			$currentSubQuery->parentQueryObj = $this;
		}

		public function setEncoding($encoding) {
			$this->encoding = $encoding;
		}

		public function includeContentTypeDeclaration($contentTypeDeclaration=true) {
			$this->contentTypeDeclaration = $contentTypeDeclaration;
		}

		public function getQueryHeader($queryHeader=true) {
			$this->queryHeader = $queryHeader;
		}

		public function setRootElementName($rootElementName) {
			$this->rootElementName = $rootElementName;
		}

		public function setRecordName($recordName) {
			$this->recordName = $recordName;
		}

		public function setCustomTagName($fieldname,$tagname) {
			$this->keys[$fieldname] = $tagname;
		}

		public function fieldsAsElements($val=false) {
			$this->fieldsAsElements = $val;
		}

		public function fieldsAsAttributes($val=true) {
			$this->fieldsAsAttributes = $val;
		}

		public function fieldsWithCDATA($val=false) {
			$this->fieldsWithCDATA = $val;
		}

		public function fieldsWithStrippedTags($val=false) {
			$this->fieldsWithStrippedTags = $val;
		}

		public function omitRootElement($val=true) {
			$this->omitRootElement = $val;
		}

		public function getDataArray() {
			return $this->dataArray;
		}
		
		public function getDomDocument() {
			return $this->doc;
		}

		public function getXMLAsString() {
			if ($this->contentTypeDeclaration === true) Header ("Content-type: text/xml");
			return $this->doc->saveXML();
		}

		public function getXmlAsHtmlString() {
			return "<pre style=\"border:4px solid #888888; background-color:#eeeeee; padding:12px; overflow:auto;\">".htmlspecialchars($this->doc->saveXML())."</pre>";
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Public Fuctions END */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */
	}
?>