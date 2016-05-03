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
- Works with xml string, XMLDocument and iXMLProvider (that is XMLResult or XMLTransform)

### Navigation Services
- Display any kind of navigation structure based on one single data source, that can be an output from the db (transformed to xml with the **XMLResult** class and then brought to a standardized nested XML structure with the **XMLTransformer** class) or a simple xml file (that was hand-written or pre-processed).

## Usecase
Although the navigation services might be the best usecase for the provided components (and a really good example to demonstrate the interaction of these components) there are lots of usecases where data should be provided as xml (then only **XMLResult** is in charge) or where data shold be displayed on a webpage (then **XMLResult**, **XMLTransformer** and XSL(T) tansformation come into play) and you did not want to use ORMs (Object-Relation-Manager) or any other technologies or you need it fast and uncomplicated.

## Misc
The classes are not very big and they can easily be customized. It's not a big deal to implement some kind of caching mechanism by writing the results (either the xml results or the transformed html results) into a file with a timestamp and then retrieving this file instead of requesting the db when the file is not too old.

## How to use
`````
to be done
