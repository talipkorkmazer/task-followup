<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\EmptyBodyException;
use App\Repository\TaskRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
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

    public function __construct(
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
    }

    public function filterByDateList(Request $request): JsonResponse
    {
        $pageSize = 25;
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $date = $request->get('date', date('Y-m-d'));
        $dateObject = DateTime::createFromFormat('Y-m-d', $date);
        $from = new DateTime($dateObject->format("Y-m-d") . " 00:00:00");
        $to = new DateTime($dateObject->format("Y-m-d") . " 23:59:59");
        $tasksQuery = $this->taskRepository->getUserTaskListFilterByDate($user, $from, $to);
        //dd($tasksQuery);
        $paginator = new Paginator($tasksQuery);
        $paginator
            ->getQuery()
            ->setFirstResult($pageSize * ($request->get('page', 1) - 1)) // set the offset
            ->setMaxResults($pageSize); // set the limit

        return $this->json($paginator->getQuery()->getResult(), Response::HTTP_OK, [], ['groups' => ['task.summary']]);
    }

    public function upcomingList(Request $request): JsonResponse
    {
        $pageSize = 25;
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $tasksQuery = $this->taskRepository->getUserUpcomingTaskList($user);
        $paginator = new Paginator($tasksQuery);
        $paginator
            ->getQuery()
            ->setFirstResult($pageSize * ($request->get('page', 1) - 1)) // set the offset
            ->setMaxResults($pageSize); // set the limit

        return $this->json($paginator->getQuery()->getResult(), Response::HTTP_OK, [], ['groups' => ['task.summary']]);
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
