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

class GroupControllerTest extends WebTestCase
{
    /** @var Client */
    private $client;

    public function setUp()
    {
        $this->loadFixtures(array());
        $this->client = static::createClient();
    }

    public function testThrows409OnDuplicateGroup()
    {
        $groupName = 'Smoekers';

        $this->client->request('POST', '/group', ['name' => $groupName]);
        $this->assertTrue($this->client->getResponse()->isSuccessful(),
            'Creating a group should be successful.');

        $this->client->request('POST', '/group', ['name' => $groupName]);
        $this->assertStatusCode(409, $this->client);

        $expectedResponse = [
            'id' => 'error.group.already_exists',
            'message' => "A group with name '$groupName' already exists."
        ];
        $this->assertEquals(json_encode($expectedResponse), $this->client->getResponse()->getContent(),
            'The error message should use the right error id and message.');
    }

    public function testCanCreateAndListExistingGroups()
    {
        $firstGroupName = 'Smoekers';
        $this->client->request('POST', '/group', ['name' => $firstGroupName]);
        $this->assertStatusCode(201, $this->client);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals($firstGroupName, $decodedResponse->name,
            'The group name should be contained in the response.');
        $uuid = $decodedResponse->id;
        $this->assertRegExp('/[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}/', $uuid,
            'The UUID of the group should be contained in the response.');

        $secondGroupName = 'Raucher';
        $this->client->request('POST', '/group', ['name' => $secondGroupName]);
        $this->assertStatusCode(201, $this->client);

        $this->client->request('GET', '/group');
        $this->assertTrue($this->client->getResponse()->isSuccessful(),
            'Listing the groups should be successful.');
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $decodedResponse,
            'All existing groups should be listed.');
        $firstGroup = $decodedResponse[0];
        $this->assertEquals($firstGroupName, $firstGroup->name,
            'The group name should be contained in the response.');
        $this->assertEquals($uuid, $firstGroup->id,
            'The UUID of the group should be contained in the response.');

    }

    public function testCanRetrieveASingleGroup()
    {
        $firstGroupName = 'Smoekers';
        $this->client->request('POST', '/group', ['name' => $firstGroupName]);
        $decodedResponse = json_decode($this->client->getResponse()->getContent());
        $uuid = $decodedResponse->id;

        $this->client->request('GET', '/group/' . $uuid);
        $this->assertStatusCode(200, $this->client);
    }

    public function testReturns404WhenGroupDoesNotExist()
    {
        $uuid = Uuid::uuid4()->toString();
        $this->client->request('GET', '/group/' . $uuid);
        $this->assertStatusCode(404, $this->client);

        $expectedResponse = [
            'id' => 'error.group.not_found',
            'message' => "A group with UUID '$uuid' does not exist."
        ];
        $this->assertEquals(json_encode($expectedResponse), $this->client->getResponse()->getContent(),
            'The error message should use the right error id and message.');
    }
}