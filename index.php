<?php

/*
 * This file makes use of PHP CS Fixer. (available at https://github.com/FriendsOfPHP/PHP-CS-Fixer)
 * Developed by: Wisley Alves
 */

//            https://ecomstore.docs.apiary.io/#reference/products/all-products/list-all-store-products
//            https://neo4j.com/docs/developer-manual/current/
//            https://github.com/neoxygen/neo4j-neoclient

require_once 'neo4j.php';
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

// function to get products
function getProduct($storeID)
{
    // usleep ( 500 ); // pauses the script 500 milliseconds, to conduct a new store query
  $varAllProduct = getUrl('https://api.e-com.plus/v1/products.json', $storeID); // Object with all products
  // for each product, create node in NEO4J with the _id, sku, name and brand property.
  // var_dump($varAllProduct);// print all
  $allProduct = $varAllProduct['result']; // Filter Object to display only products and their properties
  $attempts = 0; // attempts for eventual error
  for ($i = 0; $i < count($allProduct); ++$i) {
      $Product = getUrl('https://api.e-com.plus/v1/products/'.$allProduct[$i]['_id'].'.json', $storeID);
      if (array_key_exists('error_code', $Product)) {
          if (412 === $Product['status']) {
              // if the status is equal to 412, no store found with this ID, exclude store in neo4j, if it exists
        deleteStoreByIdNeo4j($storeID); // Function to delete store in Neo4j that no longer exists
        //break;
          } elseif (404 === $Product['status']) {
              // if the status is equal to 404, no product found with this ID, delete the product in neo4j, if it exists
        deleteProductNeo4j($storeID, $allProduct[$i]['_id']); //function to delete product node
          } elseif ($Product['status'] >= 400 and $Product['status'] <= 499) { // to try error 4xx
        echo 'Error: Unexpected '.$Product['message'].' Product id: '.$allProduct[$i]['_id'];
              echo PHP_EOL;
          } elseif ($Product['status'] >= 500 and $Product['status'] <= 599) {// to try error 5xx
        if ($attempts < 3) {// only 3 attempts are allowed
          --$i; // repeat getUrl
          ++$attempts; // increase attempts
          usleep(500); // pauses the script 500 milliseconds, for another try
        } else { // exceeded the number of attempts allowed
          $attempts = 0; // reseat attempts
          echo 'Error: Unexpected '.$Product['message'].
          'more than three attempts were made Product id: '.$allProduct[$i]['_id'];
            echo PHP_EOL;
        }
          }
      } else { // no error
      // Create product node and relationship with Categories
      createNodeProductNeo4j($Product[$i], $storeID); // in function, also create the relationship
      }
      usleep(500); // pauses the script 500 milliseconds, to conduct a new product query
  }
}

function getOrder($storeID)
{
    $allOrder = getOrderNeo4j($storeID); // get orders from a store
  // for each order, create node and relationship with products
  $attempts = 0; // attempts for eventual error
  for ($i = 0; $i < count($allOrder); ++$i) {
      $order = getUrl('https://api.e-com.plus/v1/orders/'.$allOrder[$i]['id'].'.json', $storeID);
      if (array_key_exists('error_code', $order)) {
          if (404 === $order['status']) {
              deleteOrderNeo4j($allOrder[$i]['id'], $storeID); // create function in neo4j.php
          } elseif ($order['status'] >= 400 and $order['status'] <= 499) {
              echo 'Error: Unexpected '.$order['message'].' Order id: '.$allOrder[$i]['id'];
              echo PHP_EOL;
          } elseif ($order['status'] >= 500 and $order['status'] <= 599) {
              if ($attempts < 3) {// only 3 attempts are allowed
          --$i; // repeat getUrl
          ++$attempts; // increase attempts
          usleep(500); // pauses the script 500 milliseconds, for another try
              } else { // exceeded the number of attempts allowed
          $attempts = 0; // reseat attempts
          echo 'Error: Unexpected '.$order['message'].
          'more than three attempts were made Order id: '.$allOrder[$i]['id'];
                  echo PHP_EOL;
              }
          }
      } else { // no error
          createOrderNeo4j($order[$i], $storeID);
      }
      usleep(500); // pauses the script 500 milliseconds, to conduct a new order query
  }
}
// script run
$store = getStoreNeo4j(); //Get all the stores on Neo4j, which are returned in an array
// for each Store,  get all products and save on Neo4j
for ($i = 0; $i < count($store); ++$i) {
    getProduct($store[$i]['id']);
    getOrder($store[$i]['id']);
}
