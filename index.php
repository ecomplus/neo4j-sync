<?php
// reference  https://stackoverflow.com/questions/16700960/how-to-use-curl-to-get-json-data-and-decode-the-data
//            https://ecomstore.docs.apiary.io/#reference/products/all-products/list-all-store-products
//            https://neo4j.com/docs/developer-manual/current/
//            https://github.com/neoxygen/neo4j-neoclient

require_once 'neo4j.php'; //
function getUrl($url, $storeID){ // function to get Json in the page, using cURL
  //  Initiate curl
  $ch = curl_init();
  // Set the url
  curl_setopt($ch, CURLOPT_URL, $url);
  // Will return the response, if false it print the response
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  //
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  // Send header to requisition
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "X-Store-ID:".$storeID
  ));
  // Execute
  $result = curl_exec($ch);
  // Closing
  curl_close($ch);
  // Will dump a beauty json
  $varRes = json_decode($result, true);
  return $varRes;
}

function getUrlAuth($url,$token,$xId,$storeID){ //get product with authentication
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);

  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "X-Store-ID: ".$storeID,
    "X-Access-Token:".$token,
    "X-My-ID: ".$xId
  ));

  $response = curl_exec($ch);
  curl_close($ch);
  // Will dump a beauty json
  $varRes = json_decode($result, true);
  return $varRes;
}

// function to get products
function getProduct($storeID){
  $varAllProduct = getUrl("https://sandbox.e-com.plus/v1/products.json",$storeID); // Object with all products
  // for each product, create node in NEO4J with the _id, sku, name and brand property.
  // var_dump($varAllProduct);// print all
  $status = $varAllProduct["status"]; // save status value
  if ($status === 412) { // if the status is equal to 412, no store found with this ID, exclude store in neo4j, if it exists
    deleteStoreByIdNeo4j($storeID); // Function to delete store in Neo4j that no longer exists
  }
  else {
    $allProduct = $varAllProduct["result"]; // Filter Object to display only products and their properties
    for ($i=0; $i < count($allProduct); $i++) {
      $Product = getUrl("https://sandbox.e-com.plus/v1/products/".$allProduct[$i]["_id"],$storeID);
      /* check if product has been deleted
      deleteProductNeo4j($storeID,$allProduct[$i]["_id"]); //function to delete product node
      */
      // Create product node and relationship with Categories
      createNodeProductNeo4j($Product[$i],$storeID); // in function, also create the relationship
    }
  }
}

function getOrder($storeID){
  $allOrder = getOrderNeo4j($storeID); // get orders from a store
  // for each order, create node and relationship with products
  for ($i=0; $i <count($allOrder) ; $i++)   {
    $order = getUrl("https://sandbox.e-com.plus/v1/orders/".$allOrder[$i]["id"],$storeID);
    // createOrderNeo4j($order[$i],$storeID);
  }
}
/* script run */
$store = getStoreNeo4j(); //Get all the stores on Neo4j, which are returned in an array
// for each Store,  get all products and save on Neo4j
// var_dump($store);
for ($i=0; $i <count($store) ; $i++) {
  getProduct($store[$i]['id']);
  ///getOrder($store[$i]['id']);
}


?>
