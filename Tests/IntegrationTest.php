<?php

namespace Unbabel\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use PHPUnit\Framework\TestCase;
use Unbabel\Unbabel;

/**
 * This class just holds unit tests of Unbabel PHP SDK, a suite of integration tests against the sandbox
 * is also desirable.
 */
class IntegrationTest extends TestCase
{
    private static $skipIntegrationTests = false;

    /** @var Unbabel */
    private $unbabel;

    protected function setUp()
    {
        if (self::$skipIntegrationTests) {
            $this->markTestSkipped('Invalid "UNBABEL_USERNAME" and/or "UNBABEL_KEY" used.');
        }

        $this->unbabel = new Unbabel(
            isset($_ENV['UNBABEL_USERNAME']) ? $_ENV['UNBABEL_USERNAME'] : 'fake_user',
            isset($_ENV['UNBABEL_KEY']) ? $_ENV['UNBABEL_KEY'] : 'fake_key',
            true,
            new Client()
        );

        try {
            $this->unbabel->getLanguagePairs();
        } catch (BadResponseException $e) {
            if (401 === $e->getCode()) {
                self::$skipIntegrationTests = true;

                $this->markTestSkipped('Invalid "UNBABEL_USERNAME" and/or "UNBABEL_KEY" used.');
            }
        }
    }

    public function testLanguagePair()
    {
        $res = $this->unbabel->getLanguagePairs();
        $this->assertEquals($res->getStatusCode(), 200);
    }

    public function testGetTones()
    {
        $res = $this->unbabel->getTones();
        $this->assertEquals($res->getStatusCode(), 200);
    }

    public function testGetTopics()
    {
        $res = $this->unbabel->getTopics();
        $this->assertEquals($res->getStatusCode(), 200);
    }

    public function testSubmitBulkTranslation()
    {
        $bulk = array(
            array('text' => 'In the era of Siri', 'target_language' => 'pt'),
            array('text' => 'In the era of Siri', 'target_language' => 'es')
        );
        $res = $this->unbabel->submitBulkTranslation($bulk);
        $this->assertEquals($res->getStatusCode(), 202);

        $job = json_decode($res->getBody()->getContents(), true);
        $this->checkSubmissionProgress(array($job['objects'][0]['uid'], $job['objects'][1]['uid']));
    }

    public function testSubmitTranslation()
    {
        $text = 'In the era of Siri';
        $target_language = 'pt';
        $res = $this->unbabel->submitTranslation($text, $target_language);

        $json = json_decode($res->getBody()->getContents(), true);
        $this->checkSubmissionProgress(array($json['uid']));
    }

    private function checkSubmissionProgress($jobids)
    {
        // Sleep for 30 seconds and check to make sure the job is in progress
        sleep(30);

        foreach($jobids as $jobid) {
            $res = $this->unbabel->getTranslation($jobid);
            $this->assertEquals($res->getStatusCode(), 200);

            $job = json_decode($res->getBody()->getContents(), true);
            $this->assertEquals($job['status'], Unbabel::NEW);
        }
        sleep(60);

        foreach($jobids as $jobid) {
            //Sleep for 60 more seconds to make sure the job is done
            $res = $this->unbabel->getTranslation($jobid);

            $job = json_decode($res->getBody()->getContents(), true);
            $this->assertEquals($job['status'], Unbabel::READY);
        }
    }
}
