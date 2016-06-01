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
Although the navigation services might be the best usecase for the provided components (and a really good example to demonstrate the interaction of these components) there are lots of usecases where data should be provided as xml (then only **XMLResult** is in charge) or where data shold be displayed on a webpage (then **XMLResult**, **XMLTransformer** and XSL(T) tansformation come into play) and you did not want to use ORMs (Object-Relation-Manager) or any other technologies or you need it fast and uncomplicated.

## Misc
The classes are not very big and they can easily be customized. It's not a big deal to implement some kind of caching mechanism by writing the results (either the xml results or the transformed html results) into a file with a timestamp and then retrieving this file instead of requesting the db when the file is not too old.

## How to use
### XMLTools

### Navigation Services

The *XSL(T)* files provide minimalistic output (nested) html ul-list structures that is easy to understand and customize.
Beside showing the requested navigation outline these files also assign classnames to specific li-elements to make css styling more easy:
- **first:** first li-element in an ul-list
- **last:** last li-element in an ul-list
- **active:** current page matches the page that is represented by this li-element or current page is a child of this li-element
- **current:** current page matches the page that is represented by this li-element

The underlying data structure is very simple and can be extended to any custom need. The key fields that are needed by the XSL(T)* files are:
- **page_id:** id of the page
- **parent_id:** id of the parent's page
- **position:** position within the sibling pages of the parent's page
- **url:** url of the page
- **title:** text reprensentation of the link to the page
- **target:** frame / window in which to show the page

All XSL(T)* files are based on an xml structure that meets the following conditions:
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
This XSL(T)* file is a very generic one and is needed for transforming a flat xml navigation structure into the nested xml structure all other XSL(T)* files do rely on.

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