<?php

namespace Unbabel\Tests\Unit\HttpDriver;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Unbabel\HttpDriver\Guzzle\GuzzleHttpDriver;

class GuzzleHttpDriverTest extends \PHPUnit_Framework_TestCase
{

    /** @var Client|\PHPUnit_Framework_MockObject_MockObject */
    protected $client;

    /** @var GuzzleHttpDriver */
    protected $httpDriver;

    protected function setUp()
    {
        parent::setUp();

        $this->client = $this->getMockBuilder('Guzzle\Http\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpDriver = new GuzzleHttpDriver($this->client);
    }

    public function testGet()
    {
        $statusCode = 200;
        $url = '/translation/';
        $headers = array('Authorization' => 'ApiKey none:none');

        $this->client->expects($this->once())
            ->method('get')
            ->with($url, $headers, array('query' => array('status' => 'translating')))
            ->willReturn(new MockRequest($statusCode, $headers, '{"objects": [{"uid": "aaaaaa"}]}'));

        $response = $this->httpDriver->get($url, $headers, array('query' => array('status' => 'translating')));

        $this->assertEquals(array('objects' => array(0 => array('uid' => 'aaaaaa'))), $response->json());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPost()
    {
        $statusCode = 201;
        $url = '/translation/';
        $headers = array('Authorization' => 'ApiKey none:none');
        $post_data = json_encode(array(
            'text' => 'Some kind of text',
            'target_language' => 'ru'
        ));

        $this->client->expects($this->once())
            ->method('post')
            ->with($url, $headers, $post_data, array())
            ->willReturn(new MockRequest($statusCode, $headers, '{"uid": "bbbbbb"}'));

        $response = $this->httpDriver->post($url, $headers, $post_data);

        $this->assertEquals(array('uid' => 'bbbbbb'), $response->json());
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testPatch()
    {
        $statusCode = 200;
        $url = '/translation/';
        $headers = array('Authorization' => 'ApiKey none:none');
        $post_data = json_encode(array(
            'objects' => array(
                0 => array(
                    'text' => 'Some kind of text',
                    'target_language' => 'ru'
                ),
                1 => array(
                    'text' => 'Some kind of other text',
                    'target_language' => 'latin'
                )
            )

        ));

        $response_json = '{"objects": [{"uid": "vvvvvv"}, {"uid": "eeeeee"}]}';
        $this->client->expects($this->once())
            ->method('patch')
            ->with($url, $headers, $post_data, array())
            ->willReturn(new MockRequest($statusCode, $headers, $response_json));

        $response = $this->httpDriver->patch($url, $headers, $post_data);

        $this->assertEquals(json_decode($response_json, true), $response->json());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($response_json, $response->raw());
    }
}

class MockRequest
{
    protected $response;

    public function __construct($statusCode, $headers, $body)
    {
        $this->response = new Response($statusCode, $headers, $body);
    }

    public function send()
    {
        return $this->response;
    }
}