<?php

namespace Unbabel;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Unbabel\Exception\InvalidArgumentException;

/**
 * Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api
 *
 * USAGE
 *
 * require 'vendor/autoload.php';
 *
 * use Unbabel\Unbabel;
 * use GuzzleHttp\Client;
 *
 * $httpClient = new Client();
 * $unbabel = new Unbabel(
 *     'username',
 *     'apiKey',
 *     false, // call sandbox?
 *     $httpClient
 * );
 *
 * $opts = array('callback_url' => 'http://my-awesome-app/unbabel_callback.php');
 * $resp = $unbabel->submitTranslation('This is a test', 'pt', $opts);
 * if ($resp->getStatusCode() === 201) {
 *     // Hooray! Now we need to get the uid so when we are called back we know which translation it corresponds to.
 *     var_dump(json_decode($resp->getBody()->getContents())['uid']);
 * } else {
 *    // If you think everything should be working correctly and you still get an error,
 *    // send email to sam@unbabel.com to complain.
 * }
 *
 * ////////////////////////////////////////////////
 * //       Other examples
 * ////////////////////////////////////////////////
 *
 * var_dump($unbabel->getTopics()->getBody());
 * var_dump($unbabel->getJobsWithStatus('new')->getBody());
 * var_dump($unbabel->getTranslation('8a82e622dbBS')->getBody());
 * var_dump($unbabel->getTones()->getBody());
 * var_dump($unbabel->getLanguagePairs()->getBody());
 *
 * $bulk = [
 *     ['text' => 'This is a test', 'target_language' => 'pt'],
 *     ['text' => 'This is a test', 'target_language' => 'es']
 * ];
 * var_dump($unbabel->submitBulkTranslation($bulk)->getBody());
 *
 * @author Samuel Hopkins <sam@unbabel.com>
 * @author Eduardo Oliveira <entering@gmail.com>
 * @author Joseph Bielawski <stloyd@gmail.com>
 */
class Unbabel
{
    const NEW = 'new';
    const IN_PROGRESS = 'translating';
    const READY = 'completed';
    const FAILED = 'failed';
    const CANCELED = 'canceled';
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';

    private $statuses = array(
        self::NEW,
        self::READY,
        self::IN_PROGRESS,
        self::FAILED,
        self::CANCELED,
        self::ACCEPTED,
        self::REJECTED
    );

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
     * @var Client
     */
    protected $httpClient;

    /**
     * @param string $username
     * @param string $apiKey
     * @param bool   $sandbox
     * @param Client $client
     */
    public function __construct($username, $apiKey, $sandbox = false, Client $client)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->sandbox = $sandbox;
        $this->httpClient = $client;
    }

    /**
     * Submit a single translation. Full set of options can be found here:
     * {@link https://github.com/Unbabel/unbabel_api#request-translation}
     *
     * For example, to translation a phrase from english to pt, do the following:
     *
     * $unbabel->submit_translation('This is a test', 'pt');
     *
     * @param string $text
     * @param string $targetLanguage eg: pt
     * @param array  $options
     *
     * @return ResponseInterface
     */
    public function submitTranslation($text, $targetLanguage, $options = array())
    {
        return $this->request(
            '/translation/',
            array_merge(
                array(
                    'text' => $text,
                    'target_language' => $targetLanguage
                ),
                $options
            ),
            'post'
        );
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
     * $unbabel->submitBulkTranslation($bulk)
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
            $toSend[] = array_merge($row, $options);
        }

        return $this->request('/translation/', array('objects' => $toSend), 'patch');
    }

    /**
     * @param string $uid
     *
     * @return null|ResponseInterface
     */
    public function getTranslation($uid)
    {
        return $this->request(sprintf('/translation/%s/', $uid), array());
    }

    /**
     * Submit a XLIFF to translate.
     *
     * @param string $content
     * @param string $targetLanguage eg: "pt"
     * @param array  $options eg:
     *   $options = array(
     *       "uid" => "123",                                                   string
     *       "source_language" => "en",                                        string
     *       "callback_url" => "http://example.com/example/",                  string
     *       "tone" => "Informal",                                             string
     *       "topics" => array("politics", "crafts"),                          array
     *       "instructions" => "Please keep the tone informal for my example." string
     *   );
     *
     * $unbabel->submitXliffOrder('<xliff version="1.2">\n<file>...', 'pt', $options);
     *
     * @return ResponseInterface
     */
    public function submitXliffOrder($content, $targetLanguage, $options = array())
    {
        return $this->request(
            '/xliff_order/',
            array_merge(
                array(
                    'content' => $content,
                    'target_language' => $targetLanguage
                ),
                $options
            ),
            'post'
        );
    }

    /**
     * @param string $uid
     *
     * @return ResponseInterface
     */
    public function getXliffOrder($uid)
    {
        return $this->request(sprintf('/xliff_order/%s/', $uid), array());
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
        if (!\in_array($status, $this->statuses, true)) {
            throw new InvalidArgumentException(sprintf('Expected status to be one of %s', implode(',', $this->statuses)));
        }

        return $this->request('/translation/', array('status' => $status));
    }

    /**
     * Get all language pairs available
     *
     * @return ResponseInterface
     */
    public function getLanguagePairs()
    {
        return $this->request('/language_pair/', array());
    }

    /**
     *  Get all tones available in the platform
     *
     * @return ResponseInterface
     */
    public function getTones()
    {
        return $this->request('/tone/', array());
    }

    /**
     * @return ResponseInterface
     */
    public function getTopics()
    {
        return $this->request('/topic/', array());
    }

    public function getWordCount($text)
    {
        return $this->request('/wordcount/', array('text' => $text), 'post');
    }

    /**
     * @param string $path
     * @param array  $data
     * @param string $method
     *
     * @return null|ResponseInterface
     *
     * @throws InvalidArgumentException
     */
    protected function request($path, array $data, $method = 'get')
    {
        $url = $this->buildRequestUrl($path);
        $headers = $this->getHeaders();

        switch ($method) {
            case 'get':
                return $this->httpClient->request('get', $url, array('query' => $data, 'headers' => $headers));

            case 'post':
                return $this->httpClient->request('post', $url, array('json' => $data, 'headers' => $headers));

            case 'patch':
                return $this->httpClient->request('patch', $url, array('json' => $data, 'headers' => $headers));

            default:
                throw new InvalidArgumentException(sprintf('Invalid method: %s', $method));
        }
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function buildRequestUrl($path)
    {
        $endpoint = !$this->sandbox ? 'https://unbabel.com/tapi/v2' : 'https://sandbox.unbabel.com/tapi/v2';

        return sprintf('%s%s', $endpoint, $path);
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return array(
            'Authorization' => sprintf('ApiKey %s:%s', $this->username, $this->apiKey),
            'Content-Type' => 'application/json'
        );
    }
}
