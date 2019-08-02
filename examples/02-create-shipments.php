<?php

require_once __DIR__ . '/../vendor/autoload.php';

// The first thing you need to do is set the authentication credentials
// of the client otherwise any calls to the Bol.com API will fail.

Picqer\BolRetailer\Client::setDemoMode(true);
Picqer\BolRetailer\Client::setCredentials(
    getenv('CLIENT_ID'),
    getenv('CLIENT_SECRET')
);

// Next thing: we need to fetch an order to create a shipment for.
$order = Picqer\BolRetailer\Order::get('1043946570');

$processStatus = Picqer\BolRetailer\Shipment::create($order->orderItems[0], [
    'transport' => [
        'transporterCode' => 'TNT',
        'trackAndTrace' => '3SBOL0987654321'
    ]
]);

// You can now choose to wait until the process completes:
//
// ```php
// $processStatus->waitUntilComplete(20, 3);
// ```
//
// Since the demo API of Bol.com does not support dynamic process statuses, we will not wait.

printf("Waiting for process with ID \"%s\"\n", $processStatus->id);

// You can also opt to create a shipment for an entire order. This will return an array of `ProcessStatus` objects.

$processStatuses = Picqer\BolRetailer\Shipment::createForOrder($order, [
    'transport' => [
        'transporterCode' => 'TNT',
        'trackAndTrace' => '3SBOL0987654321'
    ]
]);
