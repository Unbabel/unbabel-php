<?php

namespace Unbabel;

use Unbabel\Exception\InvalidArgumentException;
use Guzzle\Http\Message\Response;
use Unbabel\HttpDriver\HttpDriverInterface;

/**
 * Unbabel's PHP SDK is a wrapper around the HTTP API found at https://github.com/Unbabel/unbabel_api
 *
 * USAGE
 *
 * // just needed if you don't use composer
 * require 'Unbabel.php';
 *
 * use Unbabel\Unbabel;
 * use Unbabel\HttpDriver\Guzzle\GuzzleHttpDriver;
 * use Guzzle\Http\Client
 *
 * $httpDriver = new GuzzleHttpDriver(new Client());
 * $unbabel = new Unbabel('username', 'apiKey', $sandbox = false, $httpDriver);
 *
 * // $resp is an instance of \Unbabel\HttpDriver\Response
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
 * var_dump($unbabel->submitTranslation('This is a test', 'pt')->json());
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
    const IN_PROGRESS = 'translating';
    const READY = 'completed';
    const FAILED = 'failed';
    const CANCELED = 'canceled';
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';

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
     * @var HttpDriverInterface
     */
    protected $httpDriver;

    /**
     * @param string $username
     * @param string $apiKey
     * @param bool $sandbox
     * @param HttpDriverInterface $httpDriver
     */
    public function __construct($username, $apiKey, $sandbox = false, HttpDriverInterface $httpDriver)
    {
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->sandbox = $sandbox;
        $this->httpDriver = $httpDriver;
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
     * @return Response
     */
    public function submitBulkTranslation($data, $options = array())
    {
        $toSend = array();
        foreach ($data as $row) {
            $t = array_merge($row, $options);
            $toSend[] = $t;
        }

        $data = array('objects' => $toSend);

        return $this->request('/translation/', $data, 'patch');
    }

    public function getTranslation($uid)
    {
        return $this->request(sprintf('/translation/%s/', $uid), array(), 'get');
    }

    /**
     * Submit a XLIFF to translate.
     *
     * @param (string) $content
     * @param (string) $targetLanguage eg: "pt"
     *
     * @param (array) $options eg:
     *   $options = array(
     *       "uid" => "123",                                                   (string)
     *       "source_language" => "en",                                        (string)
     *       "callback_url" => "http://example.com/example/",                  (string)
     *       "tone" => "Informal",                                             (string)
     *       "topics" => array("politics", "crafts"),                          (array)
     *       "instructions" => "Please keep the tone informal for my example." (string)
     *   );
     *
     * $unbabel->submitXliffOrder('<xliff version="1.2">\n<file>...', 'pt', $options)->json();
     *
     * @return HTTP Response
     */
    public function submitXliffOrder($content, $targetLanguage, $options=array())
    {
        $data = array_merge(
            array(
                'content' => $content,
                'target_language' => $targetLanguage
            ),
            $options
        );

        return $this->request('/xliff_order/', $data, 'post');
    }

    /**
     * @param (string) $uid
     *
     * @return HTTP Response
     */
    public function getXliffOrder($uid)
    {
        return $this->request(sprintf('/xliff_order/%s/', $uid), array(), 'get');
    }

    /**
     * Get all data for all jobs with the specified status.
     *
     * @param string $status
     *
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    public function getJobsWithStatus($status)
    {
        $possible = array(self::NEW_, self::READY, self::IN_PROGRESS, self::FAILED, self::CANCELED, self::ACCEPTED, self::REJECTED);

        if (!in_array($status, $possible)) {
            throw new InvalidArgumentException(sprintf('Expected status to be one of %s', implode(',', $possible)));
        }

        $query = array('status' => $status);

        return $this->request('/translation/', $query, 'get');
    }

    /**
     * Get all language pairs available
     *
     * @return Response
     */
    public function getLanguagePairs()
    {
        return $this->request('/language_pair/', array(), 'get');
    }

    /**
     *  Get all tones available in the platform
     *
     * @return Response
     */
    public function getTones()
    {
        return $this->request('/tone/', array(), 'get');
    }

    /**
     * @return Response
     */
    public function getTopics()
    {
        return $this->request('/topic/', array(), 'get');
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
     * @return Response
     *
     * @throws InvalidArgumentException
     */
    protected function request($path, array $data, $method = 'get')
    {
        $url = $this->buildRequestUrl($path);
        $headers = $this->getHeaders();

        $response = null;
        switch ($method) {
            case 'get':
                $response = $this->httpDriver->get($url, $headers, array('query' => $data));
                break;
            case 'post':
                $body = json_encode($data);
                $response = $this->httpDriver->post($url, $headers, $body);
                break;
            case 'patch':
                $body = json_encode($data);
                $response = $this->httpDriver->patch($url, $headers, $body);
                break;
            default:
                throw new InvalidArgumentException(sprintf('Invalid method: %s', $method));
        }

        return $response;
    }

    /**
     * @param $path
     * @return string
     */
    public function buildRequestUrl($path)
    {
        $endpoint = 'https://unbabel.com/tapi/v2';
        if ($this->sandbox) {
            $endpoint = 'https://sandbox.unbabel.com/tapi/v2';
        }
        $url = sprintf('%s%s', $endpoint, $path);

        return $url;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = array(
            'Authorization' => sprintf('ApiKey %s:%s', $this->username, $this->apiKey),
            'Content-Type' => 'application/json'
        );
        return $headers;
    }
}
