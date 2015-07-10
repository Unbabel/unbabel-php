<?php

namespace Unbabel\HttpDriver;

interface HttpDriverInterface
{
    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param array $options
     * @return Response
     */
    public function get($uri = null, $headers = null, $options = array());

    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param string|null $postBody
     * @param array $options
     * @return Response
     */
    public function post($uri = null, $headers = null, $postBody = null, array $options = array());

    /**
     * @param string|null $uri
     * @param array|null $headers
     * @param string|null $body
     * @param array $options
     * @return Response
     */
    public function patch($uri = null, $headers = null, $body = null, array $options = array());
}