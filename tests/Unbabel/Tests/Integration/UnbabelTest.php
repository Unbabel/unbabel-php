<?php

namespace Unbabel\Tests\Integration;

use Guzzle\Http\Client;
use Unbabel\HttpDriver\Guzzle\GuzzleHttpDriver;
use Unbabel\Unbabel;

/**
 * UnbabelTest, this class just holds unit tests of Unbabel PHP SDK, a suite of integration tests against the sandbox
 * is also desirable.
 */
class UnbabelTest extends \PHPUnit_Framework_TestCase
{

    /** @var Unbabel */
    protected $unbabel;

    public function setUp() {
        parent::setUp();
        $client = new Client();
        $httpDriver = new GuzzleHttpDriver($client);
        $this->unbabel = new Unbabel($_ENV['UNBABEL_USERNAME'], $_ENV['UNBABEL_KEY'], $sandbox = true, $httpDriver);
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
        $job = $res->json();
        $jobids = array($job['objects'][0]['uid'], $job['objects'][1]['uid']);
        $this->checkSubmissionProgress($jobids);
    }

    public function testSubmitTranslation()
    {
        $text = 'In the era of Siri';
        $source_language = 'en';
        $target_language = 'pt';
        $res = $this->unbabel->submitTranslation($text, $target_language);
        $json = $res->json();
        $this->checkSubmissionProgress(array($json['uid']));
    }

    private function checkSubmissionProgress($jobids) {
        //$res = $unbabel->getLanguagePairs();
        sleep(30);
        foreach($jobids as $jobid) {
            //Sleep for 30 seconds and check to make sure the job is in progress
            $res = $this->unbabel->getTranslation($jobid);
            $this->assertEquals($res->getStatusCode(), 200);
            $job = $res->json();
            $this->assertEquals($job['status'], Unbabel::NEW_);
        }
        sleep(60);
        foreach($jobids as $jobid) {
            //Sleep for 60 more seconds to make sure the job is done
            $res = $this->unbabel->getTranslation($jobid);
            $job = $res->json();
            $this->assertEquals($job['status'], Unbabel::READY);
        }

    }

}
