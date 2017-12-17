<?php

/*
 * This file makes use of PHP CS Fixer. (available at https://github.com/FriendsOfPHP/PHP-CS-Fixer)
 *
 */

require_once 'vendor/autoload.php';
// add libary Neo4J
use Neoxygen\NeoClient\ClientBuilder;

// DB User
if (!isset($user)) {
    $user = 'wis';
}
// User Password
if (!isset($password)) {
    $password = 'neo4j';
}
// create connection Neo4j
$client = ClientBuilder::create()
// initial connection
    ->addConnection('default', 'http', 'localhost', 7474, true, $user, $password)
    ->build();
// $version = $client->getNeo4jVersion();

function createNodeProductNeo4j($Product, $storeID)
{
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    /*
      function to create the product in Neo4J, verifies if this product has any category,
      if it has it, it creates the category node if it does not exist and relates the product
      to the categories that it belongs to.
    */

    // but check if there are tags, if there are converts them to a string.
    // start string Brands as empty
    $vBrands = '';
    if (is_array($Product['brands'])) {
        //  Check if brands is an array, if true create var Brands
        // for each brand, add the string $Brands
        for ($i = 0; $i < count($Product['brands']); ++$i) {
            // concatenates the brands
            $vBrands = $vBrands.$Product['brands'][$i].',';
        }
    }
    /* create product node, after creating relationship with the store and
    remove relationship with category, if there is*/

    // create only one node Products and relationship with store
    //query seach store
    $query = 'MATCH (s:Store {id:{idStore}})';
    // query to create Product
    $query .= ' MERGE (p:Product {id:{idProduct}, storeID:{idStore}}) set p.name={nameProduct} set p.brands={brandsProduct}';
    // query to create relationship Product and Store
    $query .= ' MERGE (s)-[:Has]->(p)';
    // query to seach product relationship with  category
    $query .= ' WITH p MATCH (p)-[pc:BelongsTo]->()';
    // delete relationship
    $query .= ' DELETE pc';
    // parametres for products, id, name and brands
    $parameters = array(
        'idProduct' => $Product['_id'],
        'nameProduct' => $Product['name'],
        'brandsProduct' => $vBrands,
        'idStore' => $storeID
    );
    // execute query
    $client->sendCypherQuery($query, $parameters);
    // check categories, create category node and relationship with product and store, if the product has category
    if (is_array($Product['categoreis'])) {
        // Check if categories is an array, if true create category node
        // Categories is an array, create category node for each category exists in the array
        for ($i = 0; $i < count($Product['categoreis']); ++$i) {
            $query = 'MATCH (s:Store {id:{idStore}})';
            // query to create Product
            $query .= ' MATCH (p:Product {id:{idProduct}, storeID:{idStore}})';
            // query to create Category
            $query .= ' MERGE (c:Category {id:{idCategory}, storeID:{idStore}}) set c.name = {nameCategory}';
            // query to create relationship Product and Category
            $query .= ' MERGE (p)-[:BelongsTo]->(c)';
            // query to create relationship Category and Store
            $query .= ' MERGE (s)-[:Has]->(c)';
            // parametrs for query
            // parametrs for products, id, name, brands and StoreId
            // parametrs for category, id and name
            $parameters['idCategory'] = $Product['categoreis'][$i]['_id'];
            $parameters['nameCategory'] = $Product['categoreis'][$i]['name'],
            // execute query
            $client->sendCypherQuery($query, $parameters);
        }
    }
}

function deleteStoreByIdNeo4j($storeID)
{
    // function to delete the store node, all relationships, and all store-related nodes
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    // parametrs for seach
    $parameters = ['storeId' => $storeID];
    //**********
    // query to search product relationship with order
    $query = 'MATCH (p:Product {storeID:{storeId}}) MATCH (p)-[po:Buy]->()';
    // delete relationship
    $query .= ' DELETE po';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach product relationship with  category
    $query = 'MATCH (p:Product {storeID:{storeId}}) MATCH (p)-[pc:BelongsTo]->()';
    // delete relationship
    $query .= ' DELETE pc';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach store relationship with category,product and order
    $query = 'MATCH (s:Store {id:{storeId}}) MATCH (s)-[sp:Has]->()';
    // delete relationship
    $query .= ' DELETE sp';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach order by StoreId
    $query = 'MATCH (o:Order {storeID:{storeId}})';
    // delete NOdes Order
    $query .= ' DELETE o';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach category by StoreId
    $query = 'MATCH (c:Category {storeID:{storeId}})';
    // delete Nodes Category
    $query .= ' DELETE c';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach product by StoryId
    $query = 'MATCH (p:Product {storeID:{storeId}})';
    // delete Nodes Product
    $query .= ' DELETE p';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach store by id
    $query = 'MATCH (s:Store {id:{storeId}})';
    // delete node Store
    $query .= ' DELETE s';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
}

function deleteProductNeo4j($storeID, $productID)
{
    // function to delete the product node
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    // parametrs for seach
    $parameters = ['storeId' => $storeID, 'productId' => $productID];
    //**********
    // query to search product relationship with order
    $query = 'MATCH (p:Product {id:{productId}, storeID:{storeId}}) MATCH (p)-[po:Buy]->()';
    // delete relationship
    $query .= ' DELETE po';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach product relationship with  category
    $query = 'MATCH (p:Product {id:{productId}, storeID:{storeId}}) MATCH (p)-[pc:BelongsTo]->()';
    // delete relationship
    $query .= ' DELETE pc';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach product by StoryId
    $query = 'MATCH (p:Product {id:{productId}, storeID:{storeId}})';
    // delete Nodes Product
    $query .= ' DELETE p';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
}

function getStoreNeo4j()
{
    $client = $GLOBALS['client']; // retrieves value from global client variable and saves to a local client variable
    //cypher to ..
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

function createOrderNeo4j($order, $storeID)
{
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    if (is_array($order['items'])) {
        $allProducts = $order['items'];
        // parametrs for seach
        $parameters = array(
            'idOrder' => $order['_id'],
            'idStore' => $storeID
        );
        for ($i = 0; $i < count($allProducts); ++$i) {
            // create relationships with Products and orders
            $parameters['productId'] = $allProducts[$i]['product_id'];
            // marge or match
            $query = 'MATCH (o:Order {id:{idOrder}, storeID:{idStore}})';
            // seach product by id
            $query .= 'MATCH (p:Product {id:{productId}, storeID:{idStore}})';
            // create relationship product
            $query .= 'MERGE (p)-[:Buy]->(o)';
            // execute query with parametrs
            $client->sendCypherQuery($query, $parameters);
        }
    }
}

function getOrderNeo4j($storeID)
{
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    // parametrs for seach
    $parameters = ['idStore' => $storeID];
    //cypher to ..
    $query = 'MATCH (o:Order {storeID:{idStore}}) RETURN o';
    // function to search orders from a store
    $result = $client->sendCypherQuery($query, $parameters);
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

function deleteOrderNeo4j($storeID, $orderID)
{
    // function to delete the product node
    // retrieves value from global client variable and saves to a local client variable
    $client = $GLOBALS['client'];
    // parametrs for seach
    $parameters = ['storeId' => $storeID, 'ordertId' => $orderID];
    //**********
    // query seach Order by StoreId and id
    $query = 'MATCH (o:Order {id:{orderId}, storeID:{storeId}}) MATCH ()-[po:Buy]->(o)';
    // delete relationship
    $query .= ' DELETE po';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
    // query to seach order by StoryId and id
    $query = 'MATCH (o:Order {id:{orderId}, storeID:{storeId}})';
    // delete Node Order
    $query .= ' DELETE o';
    // execute query with parametrs
    $client->sendCypherQuery($query, $parameters);
    //**********
}
