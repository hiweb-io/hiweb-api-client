<?php

namespace Hiweb\HiwebApiClient;

use Exception;
use GuzzleHttp\Client as HttpClient;
use HackerBoy\JsonApi\Helpers\Validator;
use HackerBoy\JsonApi\Flexible\Document as FlexibleDocument;

class Client {

    /**
     * Authentication token
     */
    protected $token;

    /**
     * Website ID
     */
    protected $websiteId;

    /**
     * Http client
     */
    protected $httpClient;

    /**
     * Create http client instance
     * 
     * @param string Base URI
     * @return \GuzzleHttp\Client
     */
    public function __construct($baseUri = '')
    {
        $this->httpClient = new HttpClient([
            'base_uri' => $baseUri
        ]);
    }

    /**
     * Set token
     * 
     * @param string Token string
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Set website id
     * 
     * @param string Website ID
     * @return $this
     */
    public function setWebsiteId($id)
    {
        $this->websiteId = $id;
        return $this;
    }

    /**
     * Get shortcut
     * 
     * @param string endpoint
     * @param array options
     * @return object
     */
    public function get($endpoint, array $options = [])
    {
        return $this->request('get', $endpoint, $options);
    }

    /**
     * Post shortcut
     * 
     * @param string endpoint
     * @param array options
     * @return object
     */
    public function post($endpoint, array $options = [])
    {
        return $this->request('post', $endpoint, $options);
    }

    /**
     * Patch shortcut
     * 
     * @param string endpoint
     * @param array options
     * @return object
     */
    public function patch($endpoint, array $options = [])
    {
        return $this->request('patch', $endpoint, $options);
    }

    /**
     * Delete shortcut
     * 
     * @param string endpoint
     * @param array options
     * @return object
     */
    public function delete($endpoint, array $options = [])
    {
        return $this->request('delete', $endpoint, $options);
    }

    /**
     * Request
     */
    public function request($method, $endpoint, array $options = [])
    {
        // Check method valid
        $method = strtolower($method);
        $validMethods = ['get', 'post', 'patch', 'delete'];

        if (!in_array($method, $validMethods)) {
            throw new Exceptions\InvalidRequestException('Request method '.$method.' is invalid');
        }

        // Build request headers
        if (!array_key_exists('headers', $options) or !is_array($options['headers'])) {
            $options['headers'] = [];
        }

        // Content type
        $options['headers']['Content-Type'] = 'application/vnd.api+json';

        // Token
        if ($this->token) {
            $options['headers']['Authorization'] = 'Bearer '.$this->token;
        }

        // Website id
        if ($this->websiteId) {
            $options['headers']['Website-Id'] = $this->websiteId;
        }

        // Make request
        $response = $this->httpClient->request($method, $endpoint, $options);
        $jsonapi = (string) $response->getBody();

        // Validate data
        if (!Validator::isValidResponseString($jsonapi)) {
            throw new Exceptions\InvalidJsonApiResponseException('Invalid JSON:API response data');
        }

        $return = new \StdClass;
        $return->response = $response;
        $return->document = FlexibleDocument::parseFromString($jsonapi);

        return $return;
    }
}