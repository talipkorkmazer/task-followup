<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\EmptyBodyException;
use App\Repository\TaskRepository;
use App\Service\TaskUpsertService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Finder\PaginatedFinderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskController extends AbstractController
{
    private TokenStorageInterface $tokenStorage;
    private TaskRepository $taskRepository;
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;
    private PaginatedFinderInterface $finder;
    private TaskUpsertService $taskUpsertService;

    public function __construct(
        PaginatedFinderInterface $finder,
        TaskUpsertService $taskUpsertService,
        TokenStorageInterface $tokenStorage,
        TaskRepository $taskRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->taskUpsertService = $taskUpsertService;
        $this->taskRepository = $taskRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->finder = $finder;
    }

    public function list(Request $request): JsonResponse
    {
        $data = [];
        if (!empty(trim($request->getContent()))) {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $finalQuery = $this->taskRepository->getUserTaskListFilterByDateElastic($user, $data);
        $results = $this->finder->find($finalQuery);

        return $this->json($results, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function upcomingList(Request $request): JsonResponse
    {
        $data = [];
        if (!empty(trim($request->getContent()))) {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $finalQuery = $this->taskRepository->getUserUpcomingTaskListElastic($user, $data);
        $results = $this->finder->find($finalQuery);

        return $this->json($results, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function item($taskId): JsonResponse
    {
        if (is_null($taskId)) {
            throw new NotFoundHttpException('Task not exist!');
        }
        $task = $this->taskRepository->find($taskId);

        if (is_null($task)) {
            throw new NotFoundHttpException('Task not exist!');
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if ($task->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Task not exist!');
        }

        return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function create(Request $request): JsonResponse
    {
        if (empty($request->getContent())) {
            throw new EmptyBodyException();
        }
        /** @var Task $suggestedTask */
        $suggestedTask = $this->serializer->deserialize($request->getContent(), Task::class, 'json');

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        $newTask = $this->taskUpsertService->create($suggestedTask);
        $newTask->setUser($user);

        $context['groups'] = 'post';
        $errors = $this->validator->validate($newTask, null, $context);
        if ($errors->count() > 0) {
            return $this->json($errors, 422);
        }

        $this->entityManager->persist($newTask);
        $this->entityManager->flush();

        return $this->json($newTask, Response::HTTP_CREATED, [], ['groups' => ['task.summary']]);
    }

    public function update(Request $request, $taskId): JsonResponse
    {
        if (empty($request->getContent())) {
            throw new EmptyBodyException();
        }

        if (is_null($taskId)) {
            throw new NotFoundHttpException('Task not exist!');
        }

        $task = $this->taskRepository->find($taskId);

        if (is_null($task)) {
            throw new NotFoundHttpException('Task not exist!');
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if ($task->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Task not exist!');
        }

        $suggestedTask = $this->serializer->deserialize($request->getContent(), Task::class, 'json');
        $newTask = $this->taskUpsertService->patch($task, $suggestedTask);

        $context['groups'] = 'update';
        $errors = $this->validator->validate($newTask, null, $context);
        if ($errors->count() > 0) {
            return $this->json($errors, 422);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function delete($taskId): JsonResponse
    {
        if (is_null($taskId)) {
            throw new NotFoundHttpException('Task not exist!');
        }

        $task = $this->taskRepository->find($taskId);

        if (is_null($task)) {
            throw new NotFoundHttpException('Task not exist!');
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        if ($task->getUser()->getId() !== $user->getId()) {
            throw new NotFoundHttpException('Task not exist!');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
