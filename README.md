# Bol.com Retailer API client for PHP
This is an open source PHP client for the [Bol.com Retailer API](https://api.bol.com/retailer/public/Retailer-API/v10/releasenotes.html) version 10.6.

## Installation
This project can easily be installed through Composer:

```
composer require picqer/bol-retailer-php-client "^10"
```

## Usage
Create an instance of the client and authenticate using the [Client Credentials flow](https://api.bol.com/retailer/public/Retailer-API/authentication.html#_client_credentials_flow)
```php
$client = new \Picqer\BolRetailerV10\Client();
$client->authenticateByClientCredentials('your-client-id', 'your-client-secret');
```

Then you can get the first page of open orders by calling the getOrders() method on the client
```php
$reducedOrders = $client->getOrders();

foreach ($reducedOrders as $reducedOrder) {
    echo 'hello, I am order ' . $reducedOrder->orderId . PHP_EOL;
}
```

To save requests to Bol.com, you may reuse the access token:
```php
$accessToken = ... // your implementation of getting the access token from the storage

$client = new \Picqer\BolRetailerV10\Client();
$client->setAccessToken($accessToken);

$client->setAccessTokenExpiredCallback(function(\Picqer\BolRetailerV10\Client $client) {
  // Called at the beginning of a request to the Retailer API when the access token was expired (or
  // non-existent) and after a request that resulted in an error about an expired access token.
  
  // Authenticate and fetch the new access token
  $client->authenticateByClientCredentials('{your-client-id}', '{your-client-secret}');
  $accessToken = $client->getAccessToken();
  ... // store $accessToken for future use
});
```

### Code flow Authentication
When authenticating using the [Code flow](https://api.bol.com/retailer/public/Retailer-API/intermediary-authorization.html), after receiving and validating the shortcode on your callback uri, you need to retrieve the first access and refresh token:

```php
$client = new \Picqer\BolRetailerV10\Client();

$refreshToken = $client->authenticateByAuthorizationCode(
    '{your-client-id}',
    '{your-client-secret}',
    '{received-shortcode}',
    '{callback-uri}'
);
$accessToken = $client->getAccessToken();
... // store $accessToken and $refreshToken for future use

$orders = $client->getOrders();
```

The access token needs to be (re)used to make requests to the Retailer API.
```php
$client = new \Picqer\BolRetailerV10\Client();

$accessToken = ... // your implementation of getting the access token from the storage
$client->setAccessToken($accessToken);

$orders = $client->getOrders();
```

The access token code is valid for a limited amount of time (600 seconds at time of writing), so it needs to be refreshed regularly using the refresh token:

```php

$client = new \Picqer\BolRetailerV10\Client();

$accessToken = ... // your implementation of getting the access token from the storage
$client->setAccessToken($accessToken);
$client->setAccessTokenExpiredCallback(function(\Picqer\BolRetailerV10\Client $client) {
  // Called at the beginning of a request to the Retailer API when the access token was expired or
  // non-existent and after a request that resulted in an error about an expired access token.
  
  // This callback can attempt to refresh the access token. If after this callback the Client has
  // a valid access token, the request will continue or retried once. Otherwise, it will be
  // aborted with an Exception.
  
  $refreshToken = ... // your implementation of getting the refresh token from the storage
  $client->authenticateByRefreshToken('{your-client-id}', '{your-client-secret}', $refreshToken);
  $accessToken = $client->getAccessToken();
  ... // store $accessToken for future use
});

$orders = $client->getOrders();
```

The example above assumed your Bol.com integration account uses a refresh token that does not change after use (named 'Method 1' by Bol.com).

If your refresh token changes after each use ('Method 2'), then you need to store the new refresh token after refreshing. In this case a refresh token can only be used once. When multiple processes are refreshing simultaneously, there is a risk that due to race conditions a used refresh token is stored last. This means that from then on it's impossible to refresh and the user needs to manually log in again. To prevent this, you need to work with locks, in such a way that it guarantees that only the latest refresh token is stored and used. The example below uses a blocking mutex.

```php
$client = new \Picqer\BolRetailerV10\Client();

$accessToken = ... // your implementation of getting the access token from the storage
$client->setAccessToken($accessToken);

$client->setAccessTokenExpiredCallback(function(\Picqer\BolRetailerV10\Client $client) use ($mutex) {
  // Called at the beginning of a request to the Retailer API when the access token was expired or
  // non-existent and after a request that resulted in an error about an expired access token.
  
  // Ensure only 1 process can be in the critical section, others are blocked and one is let in
  // when that process leaves the critical section
  $mutex->withLock(function () use ($client) {
    // your implementation of getting the latest access token from the storage (it might be
    // refreshed by another process)
    $accessToken = ... 
    
    if (! $accessToken->isExpired()) {
      // No need to refresh the token, as it was already refreshed by another proces. Make sure the
      // client uses it.
      $client->setAccessToken($accessToken);
      return;
    }
  
    $refreshToken = ... // your implementation of getting the refresh token from the storage
    $newRefreshToken = $client->authenticateByRefreshToken(
        '{your-client-id}',
        '{your-client-secret}',
        $refreshToken
    );
    $accessToken = $client->getAccessToken();
    
    ... // store $accessToken and $newRefreshToken for future use
  }
});

$orders = $client->getOrders();
```

## Exceptions
Methods on the Client may throw Exceptions. All Exceptions have the parent class `Picqer\BolRetailerV10\Exception\Exception`:
- `ConnectException` is thrown when a problem occurred in the connection (e.g. API server is down or a network issue). You may retry later.
- `ServerException` (extends `ConnectException`) is thrown when a problem occurred on the Server (e.g. 500 Internal Server Error). You may retry later.
- `ResponseException` is thrown when the received response could not be handled (e.g. not of proper format or unexpected type). Retrying will not help, investigation is needed.
- `UnauthorizedException` is thrown when the server responded with 400 Unauthorized (e.g. invalid credentials).
- `RateLimitException` is thrown when the throttling limit has been reached for the API user.
- `Exception` is thrown when an error occurred in the HTTP library that is not covered by the cases above. We aim to map as much as possible to either `ConnectionException` or `ResponseException`.

## Contributing
Please follow the guidelines below if you want to contribute.
- Add the latest API specs of the version you want to contribute to and generate the models and client (see: 'Generated Models and Client').
- Sometimes generation fails due to an error or outputs unexpected code. Fix this in the generator class, do not alter generated classes manually.
- If a generator required a change due to a quirk in the Bol.com API specs, please add that case to the 'Known quirks' section of this README. It would be great if you check whether the current known quirks are still relevant.
- If you contribute with a new major version, any references to 'v10' have to be replaced with the new version:
  - Rename the namespaces in `/src`, `/tests` and `composer.json`.
  - Replace 'v10' with the new version in the test fixtures and in `BaseClient`.
  - Update this README with links to the new migration guide(s) and replace 'v10' with the new version.

## Generated Models and Client
The Client and all models are generated by the supplied [Retailer API specifications](https://api.bol.com/retailer/public/apispec/Retailer%20API%20-%20v10) (`src/OpenApi/retailer.json`) and [Shared API specification](https://api.bol.com/retailer/public/apispec/Shared%20API%20-%20v10) (`src/OpenApi/shared.json`). These specifications are merged. Generating the code ensures there are no typos, not every operation needs a test and future (minor) updates to the specifications can easily be applied.

The generated classes contain all data required to properly map method arguments and response data to the models: the specifications are only used to generate them.

To build the classes for the latest Bol Retailer API version, let the code download the newest specs with this script: 
```
composer run-script download-specs
```

### Client
The Client contains all operations specified in the specifications. The 'operationId' value is converted to camelCase and used as method name for each operation.

The specifications define types for each request and response (if it needs to send data). If a model for such a type encapsulates just one field, that model is abstracted from the operation have a smoother development experience:
- A method in the Client accepts that field as argument instead of the model
- A method in the Client returns the field from that model instead of the model itself

To generate the Client, the following composer script may be used:
```
composer run-script generate-client
```

### Models
The class names for models are equal to the keys of the array 'definitions' in the specifications.

To generate the Models, the following composer script may be used:
```
composer run-script generate-models
```

## Known quirks
- Some type definitions in de specifications are sentences, for example 'Container for the order items that have to be cancelled.'. These are converted to CamelCase and dots are removed.
- Some operations (get-offer-export and get-unpublished-offer-report) in the specifications have no response schema (type) specified, while there is a response. Currently, this is only the case for operations that return CSV.
- There a type 'Return' defined in the specifications. As this is a reserved keyword in PHP, it can't be used as class name for the model (in PHP <= 7), so for now it's replaced with 'ReturnObject'.
- If an array field in a response is empty, the field is (sometimes?) omitted from the response. E.g. the raw JSON response for getOrders is
  ```
  { }
  ```
  where you might expect 
  ```
  {
    "orders": [ ]
  }
  ``` 
- Operation 'get-invoices' is specified to have a string as response, while there is clearly some data model returned in JSON or XML.
- The description of the operation 'get-invoices' contains a weird space marked as 'ENSP'.
