<?php

namespace Unbabel\Tests\Unit;

use PHPUnit_Framework_TestCase;
use Unbabel\Unbabel;

/**
 * UnbabelTest, this class just holds unit tests of Unbabel PHP SDK, a suite of integration tests against the sandbox
 * is also desirable.
 */
class UnbabelTest extends PHPUnit_Framework_TestCase
{
    public function testSubmitTranslation()
    {
        $body = array(
            'status' => 'new',
            'text' => 'In the era of Siri',
            'target_language' => 'pt',
            'source_language' => 'en',
            'uid' => '5d10df62d3',
            'price' => 5
        );

        $unbabel = new Unbabel('u', 'apiKey');
        $unbabel->addMockResponse(200, json_encode($body));

        $res = $unbabel->submitTranslation('In the era of Siri', 'pt');
        $this->assertEquals($body, $res->json());
    }
}
