![logo](https://avatars3.githubusercontent.com/u/34305306?s=200&v=4"Logo")
# ecomplus-neo4j
PHP scripts to sync Neo4j (recommendations DB) with E-Com Plus Store API [[1]]
# Summary
1. [Introduction](#introduction)
   * [Requirements](#requirements)
   * [Installing dependency manager for PHP](#installing-dependency-manager-for-php)
   * [Dependency installation](#dependency-installation)
2. [Settings and basic usage](#settings-and-basic-usage)
   * [Neo4j Settings](#neo4j-settings)
   * [Basic use](#basic-use)
     * [Installing the Neo4j library in php](#installing-the-neo4j-library-in-php)
     * [Creating a node in Neo4j](#creating-a-node-in-neo4j)
     * [Creating relationship in Neo4j](#creating-relationship-in-neo4j)
     * [Get node of the Neo4j](#get-node-of-the-neo4j)
     * [Get API Product and create nodes](#get-api-product-and-create-nodes)
3. [E-Com Plus Graphs API](#e-com-plus-graphs-api)
4. [Reference](#reference)

# Introduction
[Neo4j](https://neo4j.com/) is a non-relational graph-oriented database and is very common in social networks.
The idea of using Neo4j in the E-Com Plus API is to use the recommendations system that is widely used in social networks, where in this specific case when choosing a product the API will recommend another product in which it has the highest number of relations with the chosen product.

The scripts present in this repository are used to get all the public API information and synchronize them with the DB in order to create a product recommendation system.
## Requirements
* [PHP](http://www.php.net/) 7
* Neo4j Database (version 3.3)
## Installing dependency manager for PHP
For more information on how to install the manager [[2]]
## Dependency installation
For more information on installing the dependency required for this project [[3]]
# Settings and basic usage
## Neo4j Settings
To initialize Neo4j it is recommended that you create a new user in the DB and remove the default user, for this procedure, you use the following command lines[[4]]:
`$cypher-shell`

username:`neo4j`

password: `neo4j`

By default Neo4j is required to change the default user password:
``` cypher-shell
> CALL dbms.security.changePassword('newpassword');
```
Here in this example changed to * new password *.

And only after changing the default user password that it is possible to add the new user.
``` cypher-shell
> CALL dbms.security.createUser('user', 'password', 'false');
```
**_ Note:_** _requirePasswordChange_: this is optional, with a default of `true`. If this is true, (i) the user will be forced to change their password when they first log in, and (ii) until the user has changed their password, they will be prohibited from performing any other operation. [[5]]. In the example, the _requirePasswordChange_ is `false`, since it will no longer be necessary to change the password for this user.

After the new user is created it is necessary to _logout_:
```
>:exit
```
_Login_ with the new user:
```
$cypher-shell  -u user -p password
```
And delete the default user:
```cypher-shell
> CALL dbms.security.deleteUser('neo4j');
```
## Basic use
All codes described here are made in php.
After you install the requirements, the dependencies and execute the settings in Neo4j.
#### Installing the Neo4j library in php
Adding library to php dependencies
```
$composer require neoxygen/neoclient
```
In this project a script was created only for the manipulation of the data with Neo4j (neo4j.php). And to connect the php to Neo4j it is necessary to add the settings in the script, as follows[[3]]:
```php
neo4j.php
<?php
require_once 'vendor/autoload.php';
use Neoxygen\NeoClient\ClientBuilder;// add libary Neo4J
$user = 'user'; // DB User
$password = 'password'; //User Password
$client = ClientBuilder::create() // create connection Neo4j
    ->addConnection('default', 'http', 'localhost', 7474, true, $user, $password ) // initial connection
    ->build();
```
###  Creating a node in Neo4j
To create a node in Neo4j it is necessary to use the following functions, in this example if you create a product node with the properties id, storeId, name and brands. These properties will be passed as a function parameter to execute the request.
```php
neo4j.php
<?php
function createNode($storeID,$Product)
{
    // start string Brands as empty
    $Brands = 'exemple';
    // create only one node Products
    // query to create Product
    $query .= ' MERGE (p:Product {id:{idProduct}, storeID:{idStore}}) set p.name={nameProduct} set p.brands={brandsProduct}';
    // parametrs for products, id, name and brands
    $parameters = [
      'idProduct' => $Product['_id'],
      'nameProduct' => $Product['name'],
      'brandsProduct' => $Brands,
      'idStore:' => $storeID,
    ];
    // execute query
    $client->sendCypherQuery($query, $parameters);
}
```
### Creating relationship in Neo4j
In this example, you create a relationship between Store and Product using store id, product id as a function parameter.
```php
neo4j.php
<?php
function createRelationship($storeID,$Product)
{
    $query = 'MATCH (s:Store {id:{idStore}})';
    $query .= ' MATCH (p:Product {id:{idProduct}, storeID:{idStore}})'; // query to create Product
    $query .= ' MERGE (s)-[:Has]->(p)'; // query to create relationship Product and Store
    $parameters = [
      'idProduct' => $Product['_id'],
      'idStore:' => $storeID
    ];
    // execute query
    $client->sendCypherQuery($query, $parameters);
}
```
### Get node of the Neo4j
In this example the function will fetch nodes in Neo4j and return them. It was observed that when the result of a request is captured the response of this result (in this case the Body) is protected, using this library, and to be able to manipulate this data uses the function `getBody()`which can be observed in the library file available in: `../vendor/neoxygen/neoClient/src/Request/Response.php`. The following function looks up all the store nodes in Neo4j and returns the id's of them, doing all filtering of the data so that the desired answer is obtained.
```php
neo4j.php
<?php
function getStoreNeo4j()
{
    $client = $GLOBALS['client']; // retrieves value from global client variable and saves to a local client variable
    $query = 'MATCH (s:Store) RETURN s';
    // function seach Store
    $result = $client->sendCypherQuery($query);
    /* get public reponse, because $result is protected
    see ../vendor/neoxygen/neoClient/src/Request/Response.php */
    $publicResult = $result->getBody();
    $response = $publicResult['results'][0]['data'];
    // filtering results
    // exemple of filtering
    $res = [];
    for ($i = 0; $i < count($response); ++$i) {
        $sid = $response[$i]['row'][0]['id'];
        array_push($res, ['id' => $sid]);
    }

    return $res; // return result
}

```
### Get API Product and create nodes
To get all the products of a store in the API, you need to get the store ID on Neo4j, since they are only available in the database. Soon after obtaining the ids of the stores, for each store will be listed all products and for each product listed, a product node will be created with the properties of that product and the store ID. To get the json of the API response, the functions `curl_()` and `json_decode()`.
Function used to get json from a store in the API:
```php
index.php
<?php
function getUrl($url, $storeID)
{ // function to get Json in the page, using cURL
    //  Initiate curl
    $ch = curl_init();
    // Set the url
    curl_setopt($ch, CURLOPT_URL, $url);
    // Will return the response, if false it print the response
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_HEADER, false);
    // Send header to requisition
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Store-ID:'.$storeID,
  ]);
    // Execute
    $result = curl_exec($ch);
    // Closing
    curl_close($ch);
    // Will dump a beauty json
    $varRes = json_decode($result, true);

    return $varRes;
}
```
Function to get product in API and create the node:
```php
index.php
<?php
function getProduct($storeID)
{
    // usleep ( 500 ); // pauses the script 500 milliseconds, to conduct a new store query
    // Object with all products
    $varAllProduct = getUrl('https://api.e-com.plus/v1/products.json', $storeID);
    // for each product, create node in NEO4J with the _id, sku, name and brand property.

    // Filter Object to display only products and their properties
    $allProduct = $varAllProduct['result'];
    // attempts for eventual error
    $attempts = 0;
    for ($i = 0; $i < count($allProduct); ++$i) {
        $Product = getUrl('https://api.e-com.plus/v1/products/'.$allProduct[$i]['_id'].'.json', $storeID);
        createNode($Product[$i], $storeID);
        createRelationship( $storeID$Product[$i]);
    }
}
```
Function to get the stores in neo4j and run the function to get of API and create the products node, this function is responsible for running the whole script:
```php
index.php
<?php
// script run
// Get all the stores on Neo4j, which are returned in an array
$store = getStoreNeo4j();
// for each Store,  get all products and save on Neo4j
for ($i = 0; $i < count($store); ++$i) {
    getProduct($store[$i]['id']);
}

```




# E-Com Plus Graphs API
Neo4j data is public at the _E-Com Plus Graphs REST API_,
documentation is available here:

https://ecomgraphs.docs.apiary.io/#



# Reference
[[1]] <https://ecomstore.docs.apiary.io/#>

[[2]] <https://getcomposer.org/download/>

[[3]] <https://github.com/neoxygen/neo4j-neoclient>

[[4]] <https://neo4j.com/docs/operations-manual/current/reference/user-management-community-edition/>

[[5]] <https://neo4j.com/docs/operations-manual/current/reference/user-management-community-edition/#userauth-add-user-ce>

[1]: https://github.com/ecomclub/ecomplus-neo4j#reference
[2]: https://github.com/ecomclub/ecomplus-neo4j#reference
[3]: https://github.com/ecomclub/ecomplus-neo4j#reference
[4]: https://github.com/ecomclub/ecomplus-neo4j#reference
[5]: https://github.com/ecomclub/ecomplus-neo4j#reference
