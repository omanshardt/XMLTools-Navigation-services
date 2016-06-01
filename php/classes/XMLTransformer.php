<?php
	// This object transforms any XML-source with an XSL(T)-Style
	// XML-data can be provided as url to an xml-file or as an XML-Object

	class XMLTransformer implements iXMLProvider {
		protected $host;
		protected $xmlsrc = null; // This hold an xml-source (a file or an iXMLProvider-object or a DomDocument)
		protected $xslsrc = null; // This hold an xsl-source (a file)
		protected $xmldoc; // This holds the xsl-document
		protected $xsldoc; // This holds the xsl-document
		protected $proc = null; // This holds the xslt-tarnsformer
		protected $contentTypeDeclaration = false;
		protected $doc;

		protected static $counter = 0;
		protected $id;
		protected $parameters = null;
		private static $instances = array();

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Static Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		public static function getInstance($xmlsrc) {
			self::$counter ++;
			if (is_string($xmlsrc))
			{
				if (!isset(self::$instances[$xmlsrc]))
				{
					self::$instances[$xmlsrc] = new XMLTransformer($xmlsrc);
				}
				return self::$instances[$xmlsrc];
			}
			else if($xmlsrc instanceof iXMLProvider)
			{
				if (!isset(self::$instances[$xmlsrc->__toString()]))
				{
					self::$instances[$xmlsrc->__toString()] = new XMLTransformer($xmlsrc);
				}
				return self::$instances[$xmlsrc->__toString()];
			}
			else if ($xmlsrc instanceof DomDocument)
			{
				return new XMLTransformer($xmlsrc);
			}
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Static Fuctions END */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Private / Protected Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		protected function __construct($xmlsrc) {
			$this->xmlsrc = $xmlsrc;
			$this->host = $_SERVER['HTTP_HOST'];
			$this->id = self::$counter;
			$this->extractXML();
		}

		private function __clone() {
		}

		public function __tostring() {
			return 'XMLTransformer'.$this->id;
		}

		// This retrieves the xml-document depending on the provided input and assigns it to $xmldoc
		protected function extractXML() {
			if (is_string($this->xmlsrc))
			{
				$this->xmldoc = new DOMDocument;
				return $this->xmldoc->load($this->xmlsrc);
			}
			else if ($this->xmlsrc instanceof iXMLProvider)
			{
				$this->xmldoc = new DOMDocument;
				$xmlfile = $this->xmlsrc->getXmlAsString();
				return $this->xmldoc->loadXML($xmlfile);
			}
			else if ($this->xmlsrc instanceof DomDocument)
			{
				$this->xmldoc = $this->xmlsrc;
				return $this->xmldoc;
			}
		}

		// This retrieves the xsl-document and returns it to $xsldoc
		protected function extractXSL($xslsrc) {
			$this->xslsrc = $xslsrc;
			$this->xsldoc = new DOMDocument;
			$this->xsldoc->load($this->xslsrc);
		}

		protected function configureXSLTProcessor() {
			if ($this->proc === null) $this->proc = new XSLTProcessor;
			$this->proc->importStyleSheet($this->xsldoc);
			$this->proc->registerPHPFunctions();
		}

		protected function setProcessingParameters($params) {
			$this->parameters = &$params;
			if ($this->parameters != null)
			{
				foreach ($this->parameters as $key => $value)
				{
					$this->proc->setParameter('',$key, $value);
				}
			}
		}

		protected function transform($xslsrc,$params=null) {
			if ($this->xslsrc === null || $xslsrc != $this->xslsrc)
			{
				$this->extractXSL($xslsrc);
				$this->configureXSLTProcessor();
			}
			if ($this->parameters === null || ($params != null && is_array($params))) $this->setProcessingParameters($params);
			$this->doc = $this->proc->transformToDoc($this->xmldoc);
			$this->doc->formatOutput = true;
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Private / Protected Fuctions END */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Public Fuctions START */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */

		public function includeContentTypeDeclaration($contentTypeDeclaration=true) {
			$this->contentTypeDeclaration = $contentTypeDeclaration;
		}

		public function transformXML($xslsrc,$params=null) {
			$this->transform($xslsrc,$params);
			return $this->doc->saveXML();
		}

		public function transformToXML($xslsrc,$params=null) {
			$this->transform($xslsrc,$params);
			if ($this->contentTypeDeclaration === true) Header ("Content-type: text/xml");
			return $this->doc->saveXML();
		}

		public function transformToHtml($xslsrc,$params=null) {
			$this->transform($xslsrc,$params);
			return $this->doc->saveHTML();
		}

		public function getSourceXML() {
			Header ("Content-type: text/xml");
			if (is_string($this->xmlsrc)|| $this->xmlsrc instanceof DomDocument)
			{
				return $this->xmldoc->saveXML();
			}
			elseif ($this->xmlsrc instanceof iXMLProvider)
			{
				return $this->xmlsrc->getXmlAsString();;
			}
		}

		public function getDomDocument() {
			return $this->doc;
		}

		public function getXmlAsString() {
			if ($this->contentTypeDeclaration === true) Header ("Content-type: text/xml");
			return $this->doc->saveXML();
		}

		public function getXmlAsHtmlString() {
			$this->doc->encoding = "utf-8";
			return "<pre style=\"border:4px solid #888888; background-color:#eeeeee; padding:12px; overflow:auto;\">".htmlentities($this->doc->saveXML())."</pre>";
		}

		/* ******* ******* ******* ******* ******* ******* ******* ******* */
		/* Public Fuctions END */
		/* ******* ******* ******* ******* ******* ******* ******* ******* */
	}
?>