<?php

namespace Hiweb\HiwebApiClient;

use Hiweb\HiwebApiClient\Client;
use HackerBoy\JsonApi\Flexible\Document;
use GuzzleHttp\Exception\RequestException;

class ResourceManager {

    /**
     * Resource type
     * 
     * @access protected
     */
    protected $type;

    /**
     * Http client
     */
    protected $client;

    /**
     * Constructor
     * 
     * @param string Resource type
     * @param \Hiweb\HiwebApiClient\Client
     */
    public function __construct(string $type, Client $client)
    {
        $this->type = $type;
        $this->client = $client;
    }

    /**
     * Create resource
     * 
     * @param array Attributes
     * @param array Relationships
     * @return \HackerBoy\JsonApi\Flexible\Document|null
     */
    public function create(array $attributes = [], array $relationships = [])
    {
        // Document to send
        $document = new Document;
        $resource = $document->makeFlexibleResource();
        $resource->setType($this->type);
        
        if (count($attributes)) {
            $resource->setAttributes($attributes);
        }

        if (count($relationships)) {
            $resource->setRelationships($relationships);
        }

        // Add resource to document
        $document->setData($resource);

        // Try to create
        $response = $this->client->post($this->type, [
            'json' => $document->toArray()
        ]);

        return $response->document;
    }

    /**
     * Update resource
     * 
     * @param string Resource id
     * @param array Attributes
     * @param array Relationships
     * @return \HackerBoy\JsonApi\Flexible\Document|null
     */
    public function update(string $id, array $attributes = [], array $relationships = [])
    {
        // Document to send
        $document = new Document;
        $resource = $document->makeFlexibleResource();
        $resource->setType($this->type);
        $resource->setId($id);
        
        if (count($attributes)) {
            $resource->setAttributes($attributes);
        }

        if (count($relationships)) {
            $resource->setRelationships($relationships);
        }

        // Add resource to document
        $document->setData($resource);

        // Try to create
        $response = $this->client->patch($this->type.'/'.$id, [
            'json' => $document->toArray()
        ]);

        return $response->document;
    }

    /**
     * Find resource by id
     * 
     * @param string Resource ID
     * @return \HackerBoy\JsonApi\Flexible\Document|null
     */
    public function find($id)
    {
        try {

            // Try to get from api
            $response = $this->client->get($this->type.'/'.$id);
            return $response->document;

        } catch (RequestException $e) {

            // If 404
            if ($e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            // Not 404 - throw error
            throw $e;

        } catch (\Exception $e) {

            // Unknown error
            throw $e;
        }
    }
    
    /**
     * Get resources
     * 
     * @param array Filter or query data
     * @param integer Max crawl pages, default 1, set 0 for unlimited
     * @param integer Resource limit / page
     * @return \HackerBoy\JsonApi\Flexible\Document|null
     */
    public function get(array $filter = [], $maxCrawlPages = 1, $resourceLimit = 100)
    {
        // Request options
        $options = [];

        // Filter data is set
        if (count($filter)) {

            // If is query data (single condition)
            if (array_key_exists('field', $filter)) {

                $options['headers'] = [
                    'query' => json_encode($filter)
                ];

            } elseif (isset($filter[0]) and isset($filter[0]['field'])) { // If is query data (multiple conditions)

                // Check filter data valid
                foreach ($filter as $queryData) {

                    // If invalid data
                    if (!isset($queryData['field'])) {
                        throw new \Exception('Invalid query data');
                    }
                    
                }

                // Add query data to header
                $options['headers'] = [
                    'query' => json_encode($filter)
                ];

            } else {

                $queryData = [];

                foreach ($filter as $key => $value) {
                    $queryData[] = [
                        'field' => $key,
                        'value' => $value
                    ];
                }

                $options['headers'] = [
                    'query' => json_encode($queryData)
                ];
            }

        }
        
        // Document
        $document = new Document;

        $resources = [];
        $continue = true;
        $page = 1;
        $limit = ($resourceLimit >= 1 and $resourceLimit <= 100) ? $resourceLimit : 100;

        while ($continue) {

            // Set page
            $options['query'] = [
                'page' => $page,
                'limit' => 100
            ];

            try {

                // Try to get from api
                $response = $this->client->get($this->type.'/'.$id, $options);

                // Increase page
                $page++;

            } catch (RequestException $e) {

                // If 404
                if ($e->getResponse()->getStatusCode() === 404) {
                    $continue = false;
                    break;
                }

                // Not 404
                throw $e;

            } catch (\Exception $e) {

                // Failed with unknown error
                throw $e;

            }
            
            // API data
            $data = $response->document->getData();
            $included = $response->document->getIncluded();
            
            // Resource found
            if ($data and count($data)) {
                
                // Add resources to document data
                foreach ($data as $resource) {
                    $resources[] = $resource;
                }

            }

            // Included data found
            if ($included and count($included)) {

                // Add resources to document included data
                foreach ($included as $resource) {
                    $document->addIncluded($resource);
                }
            }
            
            // Data length < limit
            if (count($data) < $limit) {
                $continue = false;
                break;
            }

            // If next link is not set - stop
            if (!$links = $response->document->getLinks() or !is_array($links) or !array_key_exists('next', $links)) {
                $continue = false;
                break;
            }

            // If max crawl pages is set
            if ($maxCrawlPages and $page >= $maxCrawlPages) {
                $continue = false;
                break;
            }
        }

        return $document->setData($resources);
    
    }

    /**
     * Delete a resource
     * 
     * @param string Resource ID
     * @return \HackerBoy\JsonApi\Flexible\Document
     */
    public function delete($id)
    {
        // Try to get from api
        $response = $this->client->delete($this->type.'/'.$id);
        return $response->document;
    }
}