<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;

class TaskControllerTest extends AbstractApiTest
{
    public function testGetUpcomingTasks(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks/upcoming');

        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testGetTasks(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks');

        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testGetTaskItem(): void
    {
        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->findOneBy(['title' => 'Test title']);

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/tasks/' . $task->getId());
        self::assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Test title', $response['title']);
    }

    public function testCreateTask(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/tasks', [], [], [], json_encode([
            'title' => 'Unit test title',
            'content' => 'Unit test content',
            'date' => "2021-06-01 13:00:00"
        ], JSON_THROW_ON_ERROR));
        self::assertEquals(201, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('Unit test title', $response['title']);
    }

    public function testUpdateStatusTask(): void
    {
        $client = $this->createAuthenticatedClient();

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->findOneBy(['title' => 'Test title']);

        $client->request('PATCH', '/api/tasks/' . $task->getId(), [], [], [], json_encode([
            'status' => true
        ], JSON_THROW_ON_ERROR));
        self::assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals(true, $response['status']);
    }
}