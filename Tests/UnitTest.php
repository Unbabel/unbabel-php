<?php

namespace Unbabel\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Unbabel\Exception\InvalidArgumentException;
use Unbabel\Unbabel;

class UnitTest extends TestCase
{
    /** @var Unbabel */
    private $unbabel;

    /** @var Client|MockObject */
    private $client;

    protected function setUp()
    {
        $this->client = $this->getMockBuilder(Client::class)->getMock();
        $this->unbabel = new Unbabel(
            isset($_ENV['UNBABEL_USERNAME']) ? $_ENV['UNBABEL_USERNAME'] : 'fake_user',
            isset($_ENV['UNBABEL_KEY']) ? $_ENV['UNBABEL_KEY'] : 'fake_key',
            true,
            $this->client
        );
    }

    public function testItShouldSubmitATranslation()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'post',
                $this->unbabel->buildRequestUrl('/translation/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'json' => $this->getRequestObject('Unbabel test string', 'en', 'pl'),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->unbabel->submitTranslation('Unbabel test string', 'pl', array(
                'source_language' => 'en',
                'callback_url' => 'http://unbabel.com/',
                'formality' => 'Informal',
                'instructions' => 'Go out of you way to be kind to somebody today.',
                'text_format' => 'text'
            ))
        );
    }

    public function testItShouldSubmitMultipleTranslations()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'patch',
                $this->unbabel->buildRequestUrl('/translation/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'json' => array(
                        'objects' => array(
                            $this->getRequestObject('Unbabel test string 1', 'en', 'pl'),
                            $this->getRequestObject('Unbabel test string 2', 'en', 'zu'),
                            $this->getRequestObject('Unbabel test string 3', 'en', 'fr')
                        )
                    )
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(
            ResponseInterface::class,
            $this->unbabel->submitBulkTranslation(
                array(
                    array(
                        'text' => 'Unbabel test string 1',
                        'target_language' => 'pl',
                        'source_language' => 'en'
                    ),
                    array(
                        'text' => 'Unbabel test string 2',
                        'target_language' => 'zu',
                        'source_language' => 'en'
                    ),
                    array(
                        'text' => 'Unbabel test string 3',
                        'target_language' => 'fr',
                        'source_language' => 'en'
                    ),
                ),
                array(
                    'callback_url' => 'http://unbabel.com/',
                    'formality' => 'Informal',
                    'instructions' => 'Go out of you way to be kind to somebody today.',
                    'text_format' => 'text'
                )
            )
        );
    }

    public function testItShouldCheckTranslationStatusByUID()
    {
        $uid = 'f94ec485db';

        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'get',
                $this->unbabel->buildRequestUrl('/translation/'.$uid.'/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'query' => array(),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getTranslation($uid));
    }

    public function testItShouldGetAllJobsWithASpecificStatus()
    {
        $status = 'translating';

        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'get',
                $this->unbabel->buildRequestUrl('/translation/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'query' => array('status' => $status),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getJobsWithStatus($status));
    }

    public function testGetAllJobsShouldRejectUnknownStatuses()
    {
        $status = 'beep beep beep';

        $this->client->expects($this->never())
            ->method('request')
            ->with('get');

        $this->expectException(InvalidArgumentException::class);

        $this->unbabel->getJobsWithStatus($status);
    }

    public function testItShouldGetLanguagePairs()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'get',
                $this->unbabel->buildRequestUrl('/language_pair/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'query' => array(),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getLanguagePairs());
    }

    public function testItShouldGetTones()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'get',
                $this->unbabel->buildRequestUrl('/tone/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'query' => array(),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getTones());
    }

    public function testItShouldGetTopics()
    {
        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'get',
                $this->unbabel->buildRequestUrl('/topic/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'query' => array(),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getTopics());
    }

    public function testItShouldGetWordCount()
    {
        $text = 'beep beep, I am a robot.';

        $this->client->expects($this->any())
            ->method('request')
            ->with(
                'post',
                $this->unbabel->buildRequestUrl('/wordcount/'),
                array(
                    'headers' => $this->unbabel->getHeaders(),
                    'json' => array('text' => $text),
                )
            )
            ->willReturn(new Response())
        ;

        $this->assertInstanceOf(ResponseInterface::class, $this->unbabel->getWordCount($text));
    }

    public function testItShouldThrowAnExceptionForInvalidMethods()
    {
        $this->expectException(InvalidArgumentException::class);

        $unbabel = new BadUnbabel(
            isset($_ENV['UNBABEL_USERNAME']) ? $_ENV['UNBABEL_USERNAME'] : 'fake_user',
            isset($_ENV['UNBABEL_KEY']) ? $_ENV['UNBABEL_KEY'] : 'fake_key',
            true,
            $this->client
        );
        $unbabel->badRequestMethod();
    }

    /**
     * @param string $text
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    private function getRequestObject($text, $from, $to)
    {
        return array(
            'text' => $text,
            'target_language' => $to,
            'source_language' => $from,
            'callback_url' => 'http://unbabel.com/',
            'formality' => 'Informal',
            'instructions' => 'Go out of you way to be kind to somebody today.',
            'text_format' => 'text'
        );
    }
}

class BadUnbabel extends Unbabel
{
    public function badRequestMethod()
    {
        $this->request('/', array(), 'options');
    }
}
