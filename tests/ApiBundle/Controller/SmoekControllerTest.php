<?php
/**
 * Created by PhpStorm.
 * User: georg
 * Date: 02.10.16
 * Time: 15:57
 */

namespace tests\ApiBundle\Controller;

use ApiBundle\Entity\Group;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class SmoekControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;
    private $groupUuid;
    /**
     * @var string[]
     */
    private $sessionUuids = [];

    public function setUp()
    {
        $this->loadFixtures(array());
        $this->client = static::createClient();

        $groupName = 'Smoekers';
        $this->client->request('POST', '/group', ['name' => $groupName]);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->groupUuid = $decodedResponse->id;

        $sessionNames = ['The Smoker', 'La Cigaretta', 'Benson & Hedges', "Let's Smoke"];
        foreach ($sessionNames as $i => $sessionName) {
            $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
            $this->assertTrue($this->client->getResponse()->isSuccessful());
            $decodedResponse = json_decode($this->client->getResponse()->getContent());
            $this->sessionUuids[$i] = $decodedResponse->id;
        }
    }

    public function testCanRequestSmoek()
    {
        /*
         * Do not pick the first session, to force the JSON serialization to have to decide between an array and
         * and an object. See \ApiBundle\Entity\Group::getSupporters for more details.
         */
        $sessionUuid = $this->sessionUuids[1];

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $sessionUuid . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $status = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($status->requested);
        $this->assertTrue(is_array($status->supporters), 'The list of supporters must be an array to be API conform');
        $this->assertCount(1, $status->supporters);
        $this->assertEquals($sessionUuid, $status->supporters[0]->id);

        $expectedExpiresAt = new \DateTime('now +10 minutes');
        $this->assertEquals($expectedExpiresAt, new \DateTime($status->expiresAt), 10);
    }

    public function testMaintainsExpiresAtForSubsequentSmoekRequests()
    {
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[0] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $status = json_decode($this->client->getResponse()->getContent());
        $expectedExpiresAt = $status->expiresAt;
        $this->assertNotNull($expectedExpiresAt,
            'The Smoek expiry time should be set after the first Smoek request.');

        /* Wait a while to make sure that the Smoek requests don't hit the same second */
        sleep(2);

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[1] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $status = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($expectedExpiresAt, $status->expiresAt,
            'The Smoek expiry time should not change after subsequent Smoek requests.');

        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[0] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $status = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($expectedExpiresAt, $status->expiresAt,
            'The Smoek vote expiry time should not change after the initiating Smoek request has been deleted.');
    }

    public function testCanDeleteSmoek()
    {
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[0] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[0] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $smoekStatus = json_decode($this->client->getResponse()->getContent());
        $this->assertObjectNotHasAttribute('expiresAt', $smoekStatus,
            'The Smoek vote expiry time should be reset when there are no more supporters left.');
    }

    public function testRequires50PercentQuorum()
    {
        $this->client->request('GET', '/group/' . $this->groupUuid);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $smoekStatus = $decodedResponse->status;
        $this->assertFalse($smoekStatus->requested,
            'No Smoek should be requested when there are no supporters.');
        $this->assertFalse($smoekStatus->confirmed,
            'The Smoek request should not be confirmed when there are no supporters.');

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[0] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $smoekStatus = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($smoekStatus->requested,
            'The Smoek should be requested when there is at least one supporter.');
        $this->assertFalse($smoekStatus->confirmed,
            'The Smoek should not be confirmed when there are less than 50% supporters.');

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[1] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[2] . '/smoek');
        $this->assertStatusCode(200, $this->client);

        $smoekStatus = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue($smoekStatus->requested,
            'The Smoek should be requested when there is at least one supporter.');
        $this->assertTrue($smoekStatus->confirmed,
            'The Smoek should be confirmed when there are more than 50% supporters.');
    }

    public function testCannotDeleteSmoekWhenVoteWasSuccessful()
    {
        foreach ($this->sessionUuids as $sessionUuid) {
            $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $sessionUuid . '/smoek');
            $this->assertStatusCode(200, $this->client);
        }

        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[1]. '/smoek');
        $this->assertStatusCode(409, $this->client);
        $error = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('error.smoek.smoek_confirmed', $error->id);
        $this->assertEquals('SMØK vote has already been confirmed.', $error->message);
    }

    public function testStillRequires50PercentQuorumWhenSessionsAreLeavingTheGroup()
    {
        for ($i = 0; $i < 2; $i++) {
            $this->client->request('POST', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[$i]. '/smoek');
            $this->assertStatusCode(200, $this->client);
        }

        $this->client->request('GET', '/group/' . $this->groupUuid);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $smoekStatus = $decodedResponse->status;
        $this->assertFalse($smoekStatus->confirmed,
            'The Smoek should not be confirmed at this point for the test to make sense.');

        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $this->sessionUuids[3]);

        $this->client->request('GET', '/group/' . $this->groupUuid);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $smoekStatus = $decodedResponse->status;
        $this->assertTrue($smoekStatus->confirmed,
            'The Smoek should be confirmed when enough sessions left the group so that a quorom is achieved with the ' .
            'remaining sessions');
    }
}