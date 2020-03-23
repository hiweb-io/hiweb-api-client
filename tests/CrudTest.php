<?php

use PHPUnit\Framework\TestCase;
use Hiweb\HiwebApiClient\Client;
use Hiweb\HiwebApiClient\ResourceManager;
use Illuminate\Support\Str;

// API Client
$client = new Client('http://hiweb-product-api.local/');
$client->setWebsiteId((string) Str::uuid());

// Resource manager
$resourceManager = new ResourceManager('products', $client);

class CrudTest extends TestCase {

    protected static $exampleId;

    /**
     * Test create
     */
    public function testCreate()
    {
        global $resourceManager;

        for ($i = 1; $i <= 3; $i++) {
            $title = 'Test API Client - '.$i.' - '.time();
            $create = $resourceManager->create([
                'title' => $title
            ]);

            $product = $create->getData();

            $this->assertTrue($product->getId() ? true : false);
            $this->assertTrue($product->getType() === 'products');
            $this->assertTrue($product->getAttributes()['title'] === $title);
        }
        
    }

    /**
     * Test view single product
     */
    public function testRead()
    {
        global $resourceManager;

        $products = $resourceManager->get([], 0, 1);

        $this->assertTrue(count($products->getData()) === 3);

        // Test read single
        $product = $resourceManager->find($products->getData()[0]['id']);
        $this->assertTrue($product->getData()['id'] === $products->getData()[0]['id']);
        static::$exampleId = $product->getData()['id'];
    }

    /**
     * Test update product
     */
    public function testUpdate()
    {
        global $resourceManager;

        $title = 'New title - '.microtime(true);
        $update = $resourceManager->update(static::$exampleId, [
            'title' => $title
        ]);

        $this->assertTrue($title === $update->getData()['attributes']['title']);
    }

    /**
     * Test delete
     */
    public function testDelete()
    {
        global $resourceManager;
        $delete = $resourceManager->delete(static::$exampleId);

        $this->assertTrue($resourceManager->find(static::$exampleId) === null);
    }
}