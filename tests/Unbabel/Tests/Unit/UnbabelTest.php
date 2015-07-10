<?php

namespace Unbabel\Tests\Unit;

use Unbabel\HttpDriver\HttpDriverInterface;
use Unbabel\Unbabel;

class UnbabelTest extends \PHPUnit_Framework_TestCase
{

    /** @var Unbabel */
    protected $unbabel;

    /** @var HttpDriverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $httpDriver;

    protected function setUp()
    {
        parent::setUp();

        $this->httpDriver = $this->getMock('Unbabel\HttpDriver\HttpDriverInterface');
        $this->unbabel = new Unbabel($_ENV['UNBABEL_USERNAME'], $_ENV['UNBABEL_KEY'], true, $this->httpDriver);
    }

    public function testItShouldSubmitATranslation()
    {
        $post_data = $this->getRequestObject('Unbabel test string', 'en', 'pl');

        $this->httpDriver->expects($this->once())
            ->method('post')
            ->with($this->unbabel->buildRequestUrl('/translation/'), $this->unbabel->getHeaders(), json_encode($post_data));

        $this->unbabel->submitTranslation('Unbabel test string', 'pl', array(
            'source_language' => 'en',
            'callback_url' => 'http://unbabel.com/',
            'formality' => 'Informal',
            'instructions' => 'Go out of you way to be kind to somebody today.',
            'text_format' => 'text'
        ));
    }

    public function testItShouldSubmitMultipleTranslations()
    {
        $post_data = array(
            'objects' => array(
                $this->getRequestObject('Unbabel test string 1', 'en', 'pl'),
                $this->getRequestObject('Unbabel test string 2', 'en', 'zu'),
                $this->getRequestObject('Unbabel test string 3', 'en', 'fr')
            )
        );

        $this->httpDriver->expects($this->once())
            ->method('patch')
            ->with($this->unbabel->buildRequestUrl('/translation/'), $this->unbabel->getHeaders(), json_encode($post_data));

        $this->unbabel->submitBulkTranslation(array(
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
        ), array(
            'callback_url' => 'http://unbabel.com/',
            'formality' => 'Informal',
            'instructions' => 'Go out of you way to be kind to somebody today.',
            'text_format' => 'text'
        ));
    }

    public function testItShouldCheckTranslationStatusByUID()
    {
        $uid = 'f94ec485db';

        $this->httpDriver->expects($this->once())
            ->method('get')
            ->with($this->unbabel->buildRequestUrl('/translation/' . $uid . '/'), $this->unbabel->getHeaders(), array('query' => array()));

        $this->unbabel->getTranslation($uid);
    }

    public function testItShouldGetAllJobsWithASpecificStatus()
    {
        $status = 'translating';

        $this->httpDriver->expects($this->once())
            ->method('get')
            ->with($this->unbabel->buildRequestUrl('/translation/'), $this->unbabel->getHeaders(), array('query' => array('status' => $status)));

        $this->unbabel->getJobsWithStatus($status);
    }

    public function testGetAllJobsShouldRejectUnknownStatuses()
    {
        $status = 'beep beep beep';

        $this->httpDriver->expects($this->never())
            ->method('get');

        $this->setExpectedException('Unbabel\Exception\InvalidArgumentException');

        $this->unbabel->getJobsWithStatus($status);
    }

    public function testItShouldGetLanguagePairs()
    {
        $this->httpDriver->expects($this->once())
            ->method('get')
            ->with($this->unbabel->buildRequestUrl('/language_pair/'), $this->unbabel->getHeaders(), array('query' => array()));

        $this->unbabel->getLanguagePairs();
    }

    public function testItShouldGetTones()
    {
        $this->httpDriver->expects($this->once())
            ->method('get')
            ->with($this->unbabel->buildRequestUrl('/tone/'), $this->unbabel->getHeaders(), array('query' => array()));

        $this->unbabel->getTones();
    }

    public function testItShouldGetTopics()
    {
        $this->httpDriver->expects($this->once())
            ->method('get')
            ->with($this->unbabel->buildRequestUrl('/topic/'), $this->unbabel->getHeaders(), array('query' => array()));

        $this->unbabel->getTopics();
    }

    public function testItShouldGetWordCount()
    {
        $text = 'beep beep, I am a robot.';

        $this->httpDriver->expects($this->once())
            ->method('post')
            ->with($this->unbabel->buildRequestUrl('/wordcount/'), $this->unbabel->getHeaders(), json_encode(array('text' => $text)));

        $this->unbabel->getWordCount($text);
    }

    public function testItShouldThrowAnExceptionForInvalidMethods()
    {
        $this->setExpectedException('Unbabel\Exception\InvalidArgumentException');

        $unbabel = new BadUnbabel($_ENV['UNBABEL_USERNAME'], $_ENV['UNBABEL_KEY'], true, $this->httpDriver);

        $unbabel->badRequestMethod();
    }

    /**
     * @param string $text
     * @param string $from
     * @param string $to
     * @return array
     */
    protected function getRequestObject($text, $from, $to)
    {
        $post_data = array(
            'text' => $text,
            'target_language' => $to,
            'source_language' => $from,
            'callback_url' => 'http://unbabel.com/',
            'formality' => 'Informal',
            'instructions' => 'Go out of you way to be kind to somebody today.',
            'text_format' => 'text'
        );
        return $post_data;
    }
}

class BadUnbabel extends Unbabel
{
    public function badRequestMethod()
    {
        $this->request('/', array(), 'options');
    }
}