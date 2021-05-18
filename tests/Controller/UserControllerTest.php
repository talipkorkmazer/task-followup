<?php


namespace App\Tests\Controller;


class UserControllerTest extends AbstractApiTest
{
    public function testGetProfile(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/profile');

        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}