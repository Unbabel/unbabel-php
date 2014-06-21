<?php

namespace Unbabel;

use Unbabel\Exception\InvalidArgumentException;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Client;
use Guzzle\Plugin\Mock\MockPlugin;
use Guzzle\Http\Message\Response;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Common\ToArrayInterface;

/**
 * Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api
 *
 * USAGE
 *
 * // just needed if you don't use composer
 * require 'Unbabel.php';
 *
 * use Unbabel/Unbabel;
 *
 * $unbabel = new Unbabel('username', 'apiKey', $sandbox = false);
 *
 * // $resp is an instance of a guzzle response object http://docs.guzzlephp.org/en/latest/http-messages.html#responses
 * $opts = array('callback_url' => 'http://my-awesome-app/unbabel_callback.php');
 * $resp = $unbabel->getTranslation($text, $target_language, $opts);
 * if ($resp->getStatusCode() == 201) {
 *     // Hooray! Now we need to get the uid so when we are called back we know which translation it corresponds to.
 *     $uid = $resp->json()['uid'];
 *     save_uid_for_callback($uid);
 * } else {
 *    // If you think everything should be working correctly and you still get an error,
 *    // send email to sam@unbabel.com to complain.
 * }
 *
 * ////////////////////////////////////////////////
 * //       Other examples
 * ////////////////////////////////////////////////
 *
 * var_dump($unbabel->getTopics()->json());
 * var_dump($unbabel->submit_translation('This is a test', 'pt')->json());
 * var_dump($unbabel->getJobsWithStatus('new')->json());
 * var_dump($unbabel->getTranslation('8a82e622dbBS')->json());
 * var_dump($unbabel->getTones()->json());
 * var_dump($unbabel->getLanguagePairs()->json());
 *
 * $bulk = [
 *     ['text' => 'This is a test', 'target_language' => 'pt'],
 *     ['text' => 'This is a test', 'target_language' => 'es']
 * ];
 * var_dump(Unbabel::submit_bulk_translation($bulk)->json());
 *
 * @author Samuel Hopkins <sam@unbabel.com>
 * @author Eduardo Oliveira <entering@gmail.com>
 */
class Unbabel
{
    // NEW is a reserved keyword on PHP
    const NEW_ = 'new';
    const READY = 'ready';
    const IN_PROGRESS = 'in_progress';
    const PROCESSING = 'processing';

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var bool
     */
    protected $sandbox;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @param string               $username
     * @param string               $apiKey
     * @param bool                 $sandbox
     */
    public function __construct($username, $apiKey, $sandbox = false)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->sandbox = $sandbox;
        $this->httpClient = new Client();
    }

    /**
     * @param string                                   $statusCode The response status code (e.g. 200, 404, etc)
     * @param string|resource|EntityBodyInterface|null $body       The body of the response
     * @param ToArrayInterface|array|null              $headers    The response headers
     */
    public function addMockResponse($statusCode, $body = null, $headers = null)
    {
        // this is a hack to solve 'queue empty problem'
        $this->httpClient = new Client();
        $plugin = new MockPlugin();
        $plugin->addResponse(new Response($statusCode, $headers, $body));
        $this->httpClient->addSubscriber($plugin);
    }

    /**
     * Submit a single translation. Full set of options can be found here:
     * {@link https://github.com/Unbabel/unbabel_api#request-translation}
     *
     * For example, to translation a phrase from english to pt, do the following:
     *
     * $unbabel->submit_translation('This is a test', 'pt')->json();
     *
     * @param string $text
     * @param string $targetLanguage eg: pt
     * @param array  $options
     *
     * @return Response
     */
    public function submitTranslation($text, $targetLanguage, $options = array())
    {
        $data = array_merge(
            array(
                'text' => $text,
                'target_language' => $targetLanguage
            ),
            $options
        );

        return $this->request('/translation/', $data, 'post');
    }

    /**
     * Submit an array where each entry in the array is an assoc array with a minimum of two
     * key / value pairs: 'text' and 'target_language'. For example:
     *
     * $bulk = [
     *     ['text' => 'This is a test', 'target_language' => 'pt'],
     *     ['text' => 'This is a test', 'target_language' => 'es']
     * ];
     *
     * $unbabel->submitBulkTranslation($bulk)->json()
     *
     * @param array $data
     * @param array $options
     *
     * @return ResponseInterface
     */
    public function submitBulkTranslation($data, $options = array())
    {
        $toSend = array();
        foreach ($data as $row) {
            $t = array_merge($row, $options);
            $toSend[] = $t;
        }

        $data = array('objects' => $data);

        return $this->request('/translation/', $data, 'patch');
    }

    /**
     * @param string $uid
     *
     * @return ResponseInterface
     */
    public function getTranslation($uid)
    {
        return $this->request(sprintf('/translation/%s/', $uid), array(), 'get');
    }

    /**
     * Get all data for all jobs with the specified status.
     *
     * @param string $status
     *
     * @return ResponseInterface
     *
     * @throws InvalidArgumentException
     */
    public function getJobsWithStatus($status)
    {
        $possible = array(self::NEW_, self::READY, self::IN_PROGRESS, self::PROCESSING);

        if (!in_array($status, $possible)) {
            throw new InvalidArgumentException(sprintf('Expected status to be one of %s', implode(',', $possible)));
        }

        $query = array('status' => $status);

        return $this->request('/translation/', $query, 'get');
    }

    /**
     * Get all language pairs available
     *
     * @return ResponseInterface
     */
    public function getLanguagePairs()
    {
        return $this->request('/language_pair/', array(), 'get');
    }

    /**
     *  Get all tones available in the platform
     *
     * @return ResponseInterface
     */
    public function getTones()
    {
        return $this->request('/tone/', array(), 'get');
    }

    /**
     * @return ResponseInterface
     */
    public function getTopics()
    {
        return $this->request('/topic/', array(), 'get');
    }

    /**
     * @param string $path
     * @param array  $data
     * @param string $method
     *
     * @return ResponseInterface
     *
     * @throws InvalidArgumentException
     */
    protected function request($path, array $data, $method = 'get')
    {
        $endpoint = 'https://www.unbabel.co/tapi/v2';
        if ($this->sandbox) {
            $endpoint = 'http://sandbox.unbabel.com/tapi/v2';
        }
        $url = sprintf('%s%s', $endpoint, $path);

        $args = array(
            'headers' => array(
            'Authorization' => sprintf('ApiKey %s:%s', $this->username, $this->apiKey),
            'Content-Type' => 'application/json'
            )
        );

        $response = null;
        switch ($method) {
            case 'get':
                $args['query'] = $data;
                $response = $this->httpClient->get($url, $args)->send();
                break;
            case 'post':
                $args['json'] = $data;
                $response = $this->httpClient->post($url, $args)->send();
                break;
            case 'patch':
                $args['json'] = $data;
                $response = $this->httpClient->patch($url, $args)->send();
                break;
            default:
                throw new InvalidArgumentException(sprintf('Invalid method: %s', $method));
        }

        return $response;
    }
}
