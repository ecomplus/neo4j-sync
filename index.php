<?php

/*
 * This file makes use of PHP CS Fixer. (available at https://github.com/FriendsOfPHP/PHP-CS-Fixer)
 *
 */

// https://ecomstore.docs.apiary.io/#reference/products/all-products/list-all-store-products
// https://neo4j.com/docs/developer-manual/current/
// https://github.com/neoxygen/neo4j-neoclient

echo PHP_EOL;
echo date('d/m h:i:s');
echo PHP_EOL;
echo 'Start: Neo4j Sync';
echo PHP_EOL;

if (isset($argv[1])) {
    $user = $argv[1];
}
if (isset($argv[2])) {
    $password = $argv[2];
}
require 'neo4j.php';

// function to get Json in the page, using cURL
function getUrl($url, $storeID)
{
    // Initiate curl
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
    usleep(500); // pauses the script 500 milliseconds, to conduct a new store query
    // Object with all products
    $varAllProduct = getUrl('https://api.e-com.plus/v1/products.json', $storeID);
    // for each product, create node in NEO4J with the _id, sku, name and brand property.

    // Filter Object to display only products and their properties
    $allProduct = $varAllProduct['result'];
    // attempts for eventual error
    $attempts = 0;
    for ($i = 0; $i < count($allProduct); ++$i) {
        $Product = getUrl('https://api.e-com.plus/v1/products/'.$allProduct[$i]['_id'].'.json', $storeID);
        if (array_key_exists('error_code', $Product)) {
            if (412 === $Product['status']) {
                // if the status is equal to 412, no store found with this ID, exclude store in neo4j, if it exists
                // Function to delete store in Neo4j that no longer exists
                deleteStoreByIdNeo4j($storeID);
                // break;
            } elseif (404 === $Product['status']) {
                // if the status is equal to 404, no product found with this ID, delete the product in neo4j, if it exists
                // function to delete product node
                deleteProductNeo4j($storeID, $allProduct[$i]['_id']);
            } elseif ($Product['status'] >= 400 and $Product['status'] <= 499) {
                // to try error 4xx
                echo 'Error: Unexpected '.$Product['message'].' Product id: '.$allProduct[$i]['_id'];
                echo PHP_EOL;
            } elseif ($Product['status'] >= 500 and $Product['status'] <= 599) {
                // to try error 5xx
                if ($attempts < 3) {
                    // only 3 attempts are allowed
                    // repeat getUrl
                    --$i;
                    // increase attempts
                    ++$attempts;
                    // pauses the script 500 milliseconds, for another try
                    usleep(500);
                } else {
                    // exceeded the number of attempts allowed
                    // reseat attempts
                    $attempts = 0;
                    echo 'Error: Unexpected '.$Product['message'].
                    'more than three attempts were made Product id: '.$allProduct[$i]['_id'];
                    echo PHP_EOL;
                    // keep server alive
                    sleep(5);
                }
            }
        } else {
            // no error
            // Create product node and relationship with Categories
            // in function, also create the relationship
            createNodeProductNeo4j($Product[$i], $storeID);
        }
        // pauses the script 500 milliseconds, to conduct a new product query
        usleep(500);
    }
}

function getOrder($storeID)
{
    // get orders from a store
    $allOrder = getOrderNeo4j($storeID);
    // for each order, create node and relationship with products
    // attempts for eventual error
    $attempts = 0;
    for ($i = 0; $i < count($allOrder); ++$i) {
        $order = getUrl('https://api.e-com.plus/v1/orders/'.$allOrder[$i]['id'].'.json', $storeID);
        if (array_key_exists('error_code', $order)) {
            if (404 === $order['status']) {
                // delete Order
                deleteOrderNeo4j($allOrder[$i]['id'], $storeID);
            } elseif ($order['status'] >= 400 and $order['status'] <= 499) {
                echo 'Error: Unexpected '.$order['message'].' Order id: '.$allOrder[$i]['id'];
                echo PHP_EOL;
            } elseif ($order['status'] >= 500 and $order['status'] <= 599) {
                if ($attempts < 3) {
                    // only 3 attempts are allowed
                    // repeat getUrl
                    --$i;
                    // increase attempts
                    ++$attempts;
                    // pauses the script 500 milliseconds, for another try
                    usleep(500);
                } else {
                    // exceeded the number of attempts allowed
                    // reseat attempts
                    $attempts = 0;
                    echo 'Error: Unexpected '.$order['message'].
                    'more than three attempts were made Order id: '.$allOrder[$i]['id'];
                    echo PHP_EOL;
                    // keep server alive
                    sleep(5);
                }
            }
        } else {
            // no error
            createOrderNeo4j($order[$i]);
        }
        // pauses the script 500 milliseconds, to conduct a new order query
        usleep(500);
    }
}

// script run
// Get all the stores on Neo4j, which are returned in an array
$store = getStoreNeo4j();
// for each Store,  get all products and save on Neo4j
for ($i = 0; $i < count($store); ++$i) {
    echo 'Store #'.$store[$i]['id'];
    echo PHP_EOL;

    getProduct($store[$i]['id']);
    getOrder($store[$i]['id']);
    updateStore($store[$i]['id'], date('h:i:s'));
}

echo 'End: Neo4j Sync';
echo PHP_EOL;
echo date('h:i:s');
echo PHP_EOL;
