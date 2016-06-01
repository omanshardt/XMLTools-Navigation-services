<?php
	// This implements the standard for every XML-providing class
	
	interface iXMLProvider
	{
		public function getDomDocument();
		public function getXmlAsString();
		public function getXmlAsHtmlString();
	}
?>