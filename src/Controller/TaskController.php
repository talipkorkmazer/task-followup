<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\EmptyBodyException;
use App\Repository\TaskRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function __construct(
        PaginatedFinderInterface $finder,
        TokenStorageInterface $tokenStorage,
        TaskRepository $taskRepository,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->taskRepository = $taskRepository;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->finder = $finder;
    }

    public function filterByDateList(Request $request): JsonResponse
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

    /**
     * @throws EmptyBodyException
     */
    public function create(Request $request): JsonResponse
    {
        if (empty($request->getContent())) {
            throw new EmptyBodyException();
        }
        $suggestedTask = $this->serializer->deserialize($request->getContent(), Task::class, 'json');
        $context['groups'] = 'post';
        $errors = $this->validator->validate($suggestedTask, null, $context);
        if ($errors->count() > 0) {
            return $this->json($errors, 422);
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $newTask = $this->taskRepository->createTask($suggestedTask, $user);

        return $this->json($newTask, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function complete($taskId): JsonResponse
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

        $task->setStatus(true);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json($task, Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }
}
