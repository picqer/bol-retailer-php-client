<?php

require_once __DIR__ . '/../vendor/autoload.php';

// get unhandled returns paginated by 50 items

$unhandledReturns = Picqer\BolRetailer\ReturnItem::all(1, false);

foreach ($unhandledReturns as $unhandledReturn) {
    var_dump($unhandledReturn);
}

// get handled returns paginated by 50 items

$handledReturns = Picqer\BolRetailer\ReturnItem::all(1, true);

foreach ($handledReturns as $handledReturn) {
    var_dump($handledReturn);
}

// get return by rma id

$return = Picqer\BolRetailer\ReturnItem::get(123456);

var_dump($return);


// handle return

Picqer\BolRetailer\ReturnItem::handle(
    $return->rmaId,
    Picqer\BolRetailer\ReturnItem::HANDLING_RESULT_RETURN_RECIEVED,
    $return->quantity
);

