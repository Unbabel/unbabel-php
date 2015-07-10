<?php

namespace Unbabel\HttpDriver\Guzzle;

use Guzzle\Http\Client;
use Unbabel\HttpDriver\HttpDriverInterface;

class GuzzleHttpDriver implements HttpDriverInterface
{

    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param array $options
     * @return Response
     */
    public function get($uri = null, $headers = null, $options = array())
    {
        return new Response($this->client->get($uri, $headers, $options)->send());
    }

    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param string|null $postBody
     * @param array $options
     * @return Response
     */
    public function post($uri = null, $headers = null, $postBody = null, array $options = array())
    {
        return new Response($this->client->post($uri, $headers, $postBody, $options)->send());
    }

    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param string|null $body
     * @param array $options
     * @return Response
     */
    public function patch($uri = null, $headers = null, $body = null, array $options = array())
    {
        return new Response($this->client->patch($uri, $headers, $body, $options)->send());
    }


}