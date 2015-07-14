<?php

namespace Unbabel\HttpDriver\Guzzle;

class Response implements \Unbabel\HttpDriver\Response
{

    protected $guzzleResponse;

    public function __construct(\Guzzle\Http\Message\Response $guzzleResponse) {
        $this->guzzleResponse = $guzzleResponse;
    }

    /**
     * return JSON-decoded response
     * @return array
     */
    public function json()
    {
        return $this->guzzleResponse->json();
    }

    /**
     * Return raw response
     * @return string
     */
    public function raw()
    {
        return $this->guzzleResponse->getBody(true);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->guzzleResponse->getStatusCode();
    }


}