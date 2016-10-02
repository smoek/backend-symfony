<?php

namespace ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GroupControllerTest extends WebTestCase
{
    public function testPost()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/group');
    }

    public function testGet()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/group');
    }

}
