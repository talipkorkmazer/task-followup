<?php

namespace App\Tests\Controller;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;

class TaskControllerTest extends AbstractApiTest
{
    public function testGetUpcomingTaskList(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/tasks/upcoming');

        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testGetTaskList(): void
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

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $numberOfItemsBefore = count($taskRepository->findAll());

        $client->request('POST', '/api/tasks', [], [], [], json_encode([
            'title' => 'Unit test title',
            'content' => 'Unit test content',
            'date' => "2021-06-01 13:00:00",
        ], JSON_THROW_ON_ERROR));
        self::assertEquals(201, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $newItem = $taskRepository->find($response['id']);
        $numberOfItemsAfter = count($taskRepository->findAll());

        self::assertEquals($numberOfItemsBefore + 1, $numberOfItemsAfter);
        self::assertEquals($newItem->getId(), $response['id']);
    }

    public function testUpdateTask(): void
    {
        $client = $this->createAuthenticatedClient();

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->findOneBy(['title' => 'Test title']);

        $client->request('PATCH', '/api/tasks/' . $task->getId(), [], [], [], json_encode([
            'status' => true,
            'content' => 'New content from unit test',
        ], JSON_THROW_ON_ERROR));
        self::assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('New content from unit test', $response['content']);
        self::assertEquals(true, $response['status']);
    }

    public function testDeleteTask(): void
    {
        $client = $this->createAuthenticatedClient();

        /** @var TaskRepository $taskRepository */
        $taskRepository = $this->entityManager->getRepository(Task::class);
        $task = $taskRepository->findOneBy(['title' => 'Unit test title']);
        $numberOfItemsBefore = count($taskRepository->findAll());

        $client->request('DELETE', '/api/tasks/' . $task->getId());
        self::assertEquals(204, $client->getResponse()->getStatusCode());

        $numberOfItemsAfter = count($taskRepository->findAll());
        self::assertEquals($numberOfItemsBefore - 1, $numberOfItemsAfter);
    }


}