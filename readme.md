# Hiweb API Client (PHP)

## Installation
```
composer require hiweb/hiweb-api-client
```

## Usage

```
<?php

use Hiweb\HiwebApiClient\Client;

// API Endpoint
$endpoint = 'https://example.hiweb.io';

// Create a HTTP Client
$client = new Client($endpoint);

// Set website id
$client->setWebsiteId(...);

// Set token
$client->setToken(...);

// Send api request
$data = $client->get('products'); // GET
$data = $client->post('product', [...]); // POST
$data = $client->patch('products/{ID}', [...]); // PATCH
$data = $client->delete('products/{ID}'); // DELETE

// Http response object
$response = $data->response;

// JSON:API Response Document
$document = $data->document; // Instance of HackerBoy\JsonApi\Flexible\Document;

// Get data
$data = $document->getData();

// Find a resource in the document by query
$find = $document->getQuery()->where('type', 'products')->where('id', '...')->first();

// Find resources in the document by query
$products = $document->getQuery()->where('type', 'products');

// Read more at: https://github.com/hackerboydotcom/json-api/

```