<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Task::class);
        $this->entityManager = $entityManager;
    }

    public function getUserUpcomingTaskList(User $user): Query
    {
        $qb = $this
            ->createQueryBuilder('t')
            ->innerJoin('t.user', 'u')
            ->where('t.date > :today')
            ->andWhere('u.id = :user_id')
            ->setParameter('user_id', $user->getId())
            ->setParameter('today', date('Y-m-d H:i:s'))
            ->orderBy('t.date', 'ASC');

        return $qb->getQuery();
    }

    public function getUserTaskListFilterByDate(User $user, $from, $to): Query
    {
        $qb = $this->createQueryBuilder('t');
        $qb->innerJoin('t.user', 'u')
            ->where('t.date BETWEEN :from AND :to')
            ->andWhere('u.id = :user_id')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('user_id', $user->getId())
            ->orderBy('t.date', 'ASC');

        return $qb->getQuery();
    }

    public function createTask(Task $task, $user): Task
    {
        $newTask = new Task();

        $newTask
            ->setTitle($task->getTitle())
            ->setContent($task->getContent())
            ->setDate($task->getDate())
            ->setStatus($task->getStatus())
            ->setUser($user);

        $this->entityManager->persist($newTask);
        $this->entityManager->flush();

        return $newTask;
    }
}
