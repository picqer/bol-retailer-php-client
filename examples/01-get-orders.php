<?php

require_once __DIR__ . '/../vendor/autoload.php';

// The first thing you need to do is set the authentication credentials
// of the client otherwise any calls to the Bol.com API will fail.

Picqer\BolRetailer\Client::setDemoMode(true);
Picqer\BolRetailer\Client::setCredentials(
    getenv('CLIENT_ID'),
    getenv('CLIENT_SECRET')
);

// You can fetch all orders by using the `Order::all` method.
// This method return reduced order instances (`Picqer/BolRetailer/Model/ReducedOrder`)
// that contain less information than an order fetched by its identifier. That
// is why we loop over the orders after fetching them.
//
// It is not recommended to do this in production because it will cause you
// to hit the rate limits really fast. For more information about rate
// limiting, see: https://api.bol.com/retailer/public/conventions/index.html

$reducedOrders = Picqer\BolRetailer\Order::all();

foreach ($reducedOrders as $reducedOrder) {
    $order = Picqer\BolRetailer\Order::get($reducedOrder->orderId);

    printf(
        "Ordered by \"%s %s\":\n",
        $order->customerDetails->billingDetails->firstName,
        $order->customerDetails->billingDetails->surName
    );

    foreach ($order->orderItems as $orderItem) {
        printf(
            "\t%s:\t%s (%dx) à € %.2f\n",
            $orderItem->orderItemId,
            $orderItem->title,
            $orderItem->quantity,
            $orderItem->offerPrice
        );
    }
}
