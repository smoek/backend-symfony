<?php
/**
 * Created by PhpStorm.
 * User: georg
 * Date: 02.10.16
 * Time: 00:23
 */

namespace tests\ApiBundle\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Client;

class SessionControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;
    private $groupUuid;

    public function setUp()
    {
        $this->loadFixtures(array());
        $this->client = static::createClient();

        $groupName = 'Smoekers';
        $this->client->request('POST', '/group', ['group' => [
            'name' => $groupName]
        ]);
        $groupRepository = $this->client->getContainer()->get('doctrine')->getManager()->getRepository('ApiBundle:Group');
        $group = $groupRepository->findOneByName($groupName);
        $this->groupUuid = $group->getUuid();
    }

    public function testCanCreateNewSession()
    {
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['session' => [
            'name' => $sessionName
        ]]);
        $this->assertStatusCode(201, $this->client,
            'Creating a new session should be successful.');
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($sessionName, $decodedResponse->name,
            'The group name should be contained in the response.');
        $uuid = $decodedResponse->id;
        $this->assertRegExp('/[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}/', $uuid,
            'The UUID of the group should be contained in the response.');

        $this->client->request('GET', '/group/' . $this->groupUuid);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(1, $decodedResponse->sessions,
            'A group should list its sessions.');
        $firstSession = $decodedResponse->sessions[0];
        $this->assertEquals($sessionName, $firstSession->name,
            'The session info should contain the name.');
    }

    public function testThrows404WhenGroupDoesNotExist()
    {
        $nonExistentUuid = '1234';
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $nonExistentUuid . '/session', ['session' => [
            'name' => $sessionName
        ]]);
        $this->assertStatusCode(404, $this->client,
            'Creating a new session should be successful.');
        $expectedResponse = [
            'id' => 'error.group.not_found',
            'message' => "A group with UUID '$nonExistentUuid' does not exist."
        ];
        $this->assertEquals(json_encode($expectedResponse), $this->client->getResponse()->getContent(),
            'The error message should use the right error id and message.');
    }

    public function testCanDeleteExistingSession()
    {
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['session' => [
            'name' => $sessionName
        ]]);
        $this->assertStatusCode(201, $this->client,
            'Creating a new session should be successful.');

        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $uuid = $decodedResponse->id;
        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $uuid);
        $this->assertStatusCode(200, $this->client,
            'Deleting a session should be successful');

        $this->client->request('GET', '/group/' . $this->groupUuid);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(0, $decodedResponse->sessions,
            'Deleting a session should remove that session from its group.');
    }

    public function testThrows404WhenDeletingNonExistentSession()
    {
        $nonExistentUuid = '1234';
        $this->client->request('DELETE', '/group/' . $this->groupUuid . '/session/' . $nonExistentUuid);
        $this->assertStatusCode(404, $this->client,
            'Deleting a non-existent session should not be successful.');
    }

}