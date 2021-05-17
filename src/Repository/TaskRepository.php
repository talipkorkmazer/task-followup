<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use DateTime;
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

    public function getUserUpcomingTaskListElastic(User $user, $filter): \Elastica\Query
    {
        $boolQuery = new \Elastica\Query\BoolQuery();

        $userQuery = new \Elastica\Query\MatchQuery();
        $userQuery->setFieldQuery('user.id', $user->getId());

        $boolQuery->addMust($userQuery);

        $dateObject = DateTime::createFromFormat('Y-m-d', date('Y-m-d'));

        $dateQuery = new \Elastica\Query\Range();
        $dateQuery->addField('date', [
            'gte' => $dateObject->format("Y-m-d H:i:s"),
            'format' => "yyyy-MM-dd HH:mm:ss",
        ]);

        $boolQuery->addMust($dateQuery);

        return $this->filterCollectionElastic($filter, $boolQuery);
    }

    public function getUserTaskListFilterByDateElastic(User $user, $filter): \Elastica\Query
    {
        $boolQuery = new \Elastica\Query\BoolQuery();

        $userQuery = new \Elastica\Query\MatchQuery();
        $userQuery->setFieldQuery('user.id', $user->getId());

        $boolQuery->addMust($userQuery);

        $date = date('Y-m-d');
        if (array_key_exists('date', $filter)) {
            $date = $filter['date'];
        }

        $dateObject = DateTime::createFromFormat('Y-m-d', $date);

        $dateQuery = new \Elastica\Query\Range();
        $dateQuery->addField('date', [
            'gte' => $dateObject->format("Y-m-d") . " 00:00:00",
            'lte' => $dateObject->format("Y-m-d") . " 23:59:59",
            'format' => "yyyy-MM-dd HH:mm:ss",
        ]);

        $boolQuery->addMust($dateQuery);

        return $this->filterCollectionElastic($filter, $boolQuery);
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

    private function filterCollectionElastic($filter, \Elastica\Query\BoolQuery $boolQuery): \Elastica\Query
    {
        if (array_key_exists('filter', $filter) && !empty($filter['filter'])) {
            $matchQuery = new \Elastica\Query\MultiMatch();
            $matchQuery->setFields(['title', 'content', 'status']);
            $matchQuery->setQuery($filter['filter']);
            $boolQuery->addMust($matchQuery);
        }

        $pageSize = 25;
        $page = 1;
        if (array_key_exists('page', $filter)) {
            $page = $filter['page'];
        }
        $mainQuery = new \Elastica\Query();
        $mainQuery->addSort(array('date' => array('order' => 'asc')));
        $mainQuery->setSize($pageSize);
        $mainQuery->setFrom(($page - 1) * $pageSize);

        return $mainQuery->setQuery($boolQuery);
    }
}
