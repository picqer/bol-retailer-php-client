# Bol.com Retailer API client for PHP
This is an open source PHP client for the [Bol.com Retailer API](https://developers.bol.com/newretailerapiv3/) version 3.

## Installation
This project can easily be installad through Composer:

```
composer require picqer/bol-retailer-php-client
```

## Usage
First configure the client by setting the credentials:
```php
Picqer\BolRetailer\Client::setCredentials('your-client-id', 'your-client-secret');
```

Then you can get the first page of open orders by using the Order model:
```php
$reducedOrders = Picqer\BolRetailer\Order::all();

foreach ($reducedOrders as $reducedOrder) {
    echo 'hello, I am order ' . $reducedOrder->orderId . PHP_EOL;
}
```

## Reduced orders and full orders
In the list of orders, Bol only gives you a reduced amount of details per order. That is what we call a "reduced order". If you need all details of the order, retrieve the order by its id:

```php
$order = Picqer\BolRetailer\Order::get($reducedOrder->orderId);
```

## Supported modules
At this moment, this client supports:
- Offer
- Order
- ProcessStatus
- ReturnItem
- Shipment

## Examples
See more examples in the [examples/](https://github.com/picqer/bol-retailer-php-client/tree/master/examples) directory.
