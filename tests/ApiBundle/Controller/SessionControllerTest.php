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
        $this->client->request('POST', '/group', ['name' => $groupName]);
        $groupRepository = $this->client->getContainer()->get('doctrine')->getManager()->getRepository('ApiBundle:Group');
        $group = $groupRepository->findOneByName($groupName);
        $this->groupUuid = $group->getUuid();
    }

    public function testCanCreateNewSession()
    {
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
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
        $this->client->request('POST', '/group/' . $nonExistentUuid . '/session', ['name' => $sessionName]);
        $this->assertStatusCode(404, $this->client,
            'Creating a new session should be successful.');
    }

    public function testCanDeleteExistingSession()
    {
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
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

    public function testThrows409WhenSessionNameAlreadyExistsInGroup()
    {
        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
        $this->assertStatusCode(201, $this->client,
            'Creating a new session should be successful.');

        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
        $this->assertStatusCode(409, $this->client,
            'Using the same session name twice should not be successful.');
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $errorId = $decodedResponse->id;
        $this->assertEquals('error.session.already_exists', $errorId,
            'The error message should signify that the session name already exists');
    }

    public function testCanCreateTwoSessionsWithTheSameNameInDifferentGroups()
    {
        $otherGroupName = 'Rauchvereinigung';
        $this->client->request('POST', '/group', ['name' => $otherGroupName]);
        $groupRepository = $this->client->getContainer()->get('doctrine')->getManager()->getRepository('ApiBundle:Group');
        $group = $groupRepository->findOneByName($otherGroupName);
        $otherGroupUuid = $group->getUuid();

        $sessionName = 'TheSmoeker';
        $this->client->request('POST', '/group/' . $this->groupUuid . '/session', ['name' => $sessionName]);
        $this->assertStatusCode(201, $this->client,
            'Creating a new session should be successful.');

        $this->client->request('POST', '/group/' . $otherGroupUuid . '/session', ['name' => $sessionName]);
        $this->assertStatusCode(201, $this->client,
            'Creating a new session with the same name in another group should be successful.');

    }

}