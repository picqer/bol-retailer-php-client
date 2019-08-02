<?php

require_once __DIR__ . '/../vendor/autoload.php';

// The first thing you need to do is set the authentication credentials
// of the client otherwise any calls to the Bol.com API will fail.

Picqer\BolRetailer\Client::setDemoMode(true);
Picqer\BolRetailer\Client::setCredentials(
    getenv('CLIENT_ID'),
    getenv('CLIENT_SECRET')
);

// We can create a new offer by using the `Offer::create` method

$offer = Picqer\BolRetailer\Offer::create([
    "ean" => "0000007740404",
    "condition" => [
        "name" => "AS_NEW",
        "category" => "SECONDHAND",
        "comment" => "Heeft een koffie vlek op de kaft."
    ],
    "referenceCode" => "REF12345",
    "onHoldByRetailer" => false,
    "unknownProductTitle" => "Unknown Product Title",
    "pricing" => [
        "bundlePrices" => [
            [
                "quantity" => 1,
                "price" => 9.99
            ]
        ]
    ],
    "stock" => [
        "amount" => 6,
        "managedByRetailer" => false
    ],
    "fulfilment" => [
        "type" => "FBR",
        "deliveryCode" => "24uurs-23"
    ]
]);

// You can use the update method to update offers. This will return a process status because it runs asynchronously.
// To update the stock level of an offer, you can use the `updateStock` method.

$offer         = Picqer\BolRetailer\Offer::get('13722de8-8182-d161-5422-4a0a1caab5c8');
$processStatus = $offer->updateStock(5, true);

// Wait until the process is complete.
// $processStatus->waitUntilComplete();

// And refresh the offer model
$offer->refresh();
printf("Current stock level: %d\n", $offer->stock->amount);

// And you can also delete an offer.
$offer->delete();

// And finally, to get an offer by its ID you can do the same as with orders
$offer = Picqer\BolRetailer\Offer::get('13722de8-8182-d161-5422-4a0a1caab5c8');
