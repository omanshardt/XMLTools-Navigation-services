	# XMLTools / Navigation Services
	**XMLTools** are mainly two PHP classes that helps dealing with mySQL results from SELECT statements. Whereas **XMLResult** passes the SELECT statement to the db and returns them as XML, **XMLTransformer** provides a convenient way to transform the XML using arbitrary XSL(T) documents.

	**Navigation Services** are a set of *XSL(T)* files that display various types of navigations as
	- Sitemaps,
	- Child routes of current page,
	- Classic navigations
	- Breadcrumb navigations
	- Any other type of navigation with little customization

	## The key feature are:
	### XMLResult
	- Allow nesting of SELECT queries with an easy syntax. This allows to perform subqueries related to the results of a main query. This is useful for small result sets where some main objects with their child objects should be dislayed.
	- Simple customization of all element's and attribute's names.
	- Ability to choose between attribute and element output for each field.

	### XMLTransformer
	- Easy to use.

	### Navigation Services
	- Display any kind of navigation structure based on one single data source, that can be an output from the db (transformed to xml with the **XMLResult** class and then brought to a standardized nested XML structure with the **XMLTransformer** class) or a simple xml file (that was hand-written or pre-processed).

	## Usecase
	Although the navigation services might be the best usecase for the provided components (and a really good example to demonstrate the interaction of these components) there are lots of usecases where data should be provided as xml (then only **XMLResult** is in charge) or where data should be displayed on a webpage (then **XMLResult**, **XMLTransformer** and XSL(T) tansformation come into play) and you did not want to use ORMs (Object-Relation-Manager) or any other technologies or you need it fast and uncomplicated.

	## Misc
	The classes are not very big and they can easily be customized. It's not a big deal to implement some kind of caching mechanism by writing the results (either the xml results or the transformed html results) into a file with a timestamp and then retrieving this file instead of requesting the db when the file is not too old.

	## How to use

	### XMLTools

	Getting the result of an SQL reqeuest as xml is as simple as that:
	````php
	$query_routes = "SELECT * FROM `table`";
	$res = XMLResult::getInstance($query_routes);
	$res->executeQuery();
	return $res->getXmlAsHtmlString(); // This gets an html representation for debugging purpose
	// or 
	return $res->getXmlAsString(); // This gets xml as a string
	// or
	return $res->getDomDocument(); // This gets xml as an object
	````
	Per default the root element's name is „document“ and the data row element's names are „record“. To change these names do the following:
	````php
	$query_routes = "SELECT * FROM `pages`";
	$res = XMLResult::getInstance($query_routes);

	$res->setRootElementName('siteMap');
	$res->setRecordName('siteMapNode');

	$res->executeQuery();
	return $res->getXmlAsHtmlString();
	````
	Per default the record's fields were rendered as xml elements. to render the record's fields as xml attributes do the following:
	````php
	$query_routes = "SELECT * FROM `pages`";
	$res = XMLResult::getInstance($query_routes);

	$res->fieldsAsAttributes(true);
	$res->fieldsAsElements(false);

	$res->executeQuery();
	return $res->getXmlAsHtmlString();
	````
	Alternatively you can specify fields to be rendered as xml attributes and fields to be rendered as xml elements
	````php
	$query_routes = "SELECT * FROM `pages`";
	$res = XMLResult::getInstance($query_routes);

	$res->fieldsAsAttributes(array('page_id', 'parent_id', 'position'));
	$res->fieldsAsElements(array('url', 'title', 'target'));

	$res->executeQuery();
	return $res->getXmlAsHtmlString();
	````
	To transform an xml result to html you do so by using the **XMLTransformer** and providing the return value from the **XMLResult** and an *XSL(T)* file.
	````php
	$query_routes = "SELECT * FROM `table`";
	$res = XMLResult::getInstance($query_routes);
	$res->executeQuery();
	$dom =  $res->getDomDocument();
	$trans = XMLTransformer::getInstance($dom);
	return $trans->transformToHtml("Global_Resources/xsl/html.xsl");
	````
	In case of the **navigation services** we transform the result from the **XMLResult** to another xml structure and then again transform this new xml structure to html:
	````php
	$query_routes = "SELECT * FROM `table`";
	$res = XMLResult::getInstance($query_routes);
	$res->setRootElementName('siteMap');
	$res->setRecordName('siteMapNode');
	$res->fieldsAsAttributes(true);
	$res->fieldsAsElements(false);
	$res->executeQuery();

	$dom =  $res->getDomDocument();

	$trans = XMLTransformer::getInstance($dom);
	$trans->transformToXml("Global_Resources/xsl/nestedXmlSitemap.xsl");

	$mainnavi = XMLTransformer::getInstance($trans->getDomDocument());
	// This additionally passes an array of variables into the *XSL(T)* file
	return $mainnavi->transformToHtml("sitemap.xsl", array('upid' => $page_path));
	````

	### Navigation Services

	The *XSL(T)* files provide minimalistic output (nested) html ul-list structures that is easy to understand and customize.
	Beside showing the requested navigation outline these files also assign classnames to specific li-elements to make css styling more easy:
	- **first:** first li-element in an ul-list
	- **last:** last li-element in an ul-list
	- **active:** current page is a child of the li-element that represent's this page
	- **current:** current page matches the page that is represented by this li-element

	The underlying data structure is very simple and can be extended to any custom need. The key fields that are needed by the *XSL(T)* files are:
	- **page_id:** id of the page
	- **parent_id:** id of the parent's page
	- **position:** position within the sibling pages of the parent's page
	- **url:** url of the page
	- **title:** text reprensentation of the link to the page
	- **target:** frame / window in which to show the page

	All *XSL(T)* files are based on an xml structure that meets the following conditions:
	- root element name: **siteMap**
	- page element name: **siteMapNode**
	- all fields have to be provided as **xml attributes**, not a sxml elements. 

	````xml
	<siteMap>
		<siteMapNode page_id="1" parent_id="0" position="1" url="/home" title="Home" target="_self" />
		<siteMapNode page_id="2" parent_id="0" position="2" url="/products" title="Products" target="_self" />
		<siteMapNode page_id="3" parent_id="2" position="1" url="/product1" title="Product1" target="_self" />
		<siteMapNode page_id="4" parent_id="2" position="2" url="/product2" title="Product2" target="_self" />
	</siteMap>
	````

	#### nestedXmlSitemap.xsl
	This *XSL(T)* file is a very generic one and is needed for transforming a flat xml navigation structure into the nested xml structure all other *XSL(T)* files do rely on.

	````xml
	<siteMap>
		<siteMapNode page_id="1" parent_id="0" position="1" url="/home" title="Home" target="_self" />
		<siteMapNode page_id="2" parent_id="0" position="2" url="/products" title="Products" target="_self">
			<siteMapNode page_id="3" parent_id="2" position="1" url="/product1" title="Product1" target="_self" />
			<siteMapNode page_id="4" parent_id="2" position="2" url="/product2" title="Product2" target="_self" />
		</siteMapNode>
	</siteMap>
	````
	As long as the node names match the conventions (siteMap and siteMapNode) and fields are provided as attributes any flat xml file will be converted to a nested xml file by matching page_ids and parent_ids.

	#### sitemap.xsl
	This *XSL(T)* file provides a complete sitemap with all pages in their hierarchical order as nested html ul lists. It can be used if the website consists of only a few pages or if you want to provide a sitemap on a special sitemap page. With specific css styles applied it also can be used to provide a menu-like navigation with horizontal or vertical root level pages and vertical sub-levels.

	The **sitemap.xsl** relies on a unique page id (upid) that is passed as variable into the *XSL(T)* file. In this case the url (or path portion of the url) as it is given in the *url* attribute of the siteMapNodes is used as the unique page id. If you might want to use a numeric id or anything else instead, make sure that it is checked against the corresponding attribute.

	#### classic.xsl
	This *XSL(T)* file provides all root level pages with the current page's branch expanded up to the current page's child pages. This is a very common (and classic) way to display a site navigation.

	The **classic.xsl** relies on a unique page id (upid) that is passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.

	#### singlelevel.xsl
	This *XSL(T)* file provides the current page and it's siblings if the current page is at the specified level or the ancestor of the current page at the specified level and it's siblings if the current page is at a deeper level then specified. This allows to place the different levels of the navigation independently at different locations on the page.

	The **singlelevel.xsl** relies on a unique page id (upid) and the level to be dispayed that are passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.

	#### children.xsl
	This *XSL(T)* file provides all children of the current page.

	The **children.xsl** relies on a unique page id (upid) that is passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.

	#### branch.xsl
	This *XSL(T)* file provides a branch of the sitemap starting from the current page.

	The **branch.xsl** relies on a unique page id (upid) that is passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.

	#### breadcrumb_….xsl
	This *XSL(T)* files provide a breadcrumb navigation that is only the current page and it's ancestors. **breadcrumb_nested.xsl** provides the nested pages in nested html ul lists that can be considered as some kind of oversized for the standard purpose. **breadcrumb_flat_list.xsl** provides the nested pages as flat html ul list what makes html markup a little bit more streamlined. **breadcrumb_flat_list.xsl** simply provides anchor tags separated by „>“ what is sufficient for most usecases.

	The **breadcrumb_….xsl** relies on a unique page id (upid) that is passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.

	#### level.xsl
	This *XSL(T)* file provides **all** pages at the specified level no matter if they are siblings of current page or one of it's ancestors or not.

	The **level.xsl** relies on a unique page id (upid) and the level to be dispayed that are passed as variable into the *XSL(T)* file. For further information see remarks on *sitemap.xsl*.