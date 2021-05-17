<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Class TaskFixtures
 *
 * @package App\DataFixtures
 */
class TaskFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * TaskFixtures constructor.
     */
    public function __construct()
    {
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $index = 1;
        $task = new Task();
        $task->setTitle('Test title');
        $task->setContent('Test Content');
        $task->setDate($this->faker->dateTime->setDate(date('Y'), date('m'), random_int((int) date('d'), 30)));
        /** @var User $user */
        $user = $this->getReference("user.$index");
        $task->setUser($user);
        $manager->persist($task);
        for ($i = 1; $i <= 10000; $i++) {
            $task = new Task();
            $task->setTitle($this->faker->realText(30));
            $task->setContent($this->faker->realText(250));
            $task->setDate($this->faker->dateTime->setDate(date('Y'), date('m'), random_int((int) date('d'), 30)));
            /** @var User $user */
            $user = $this->getReference("user.$index");
            $task->setUser($user);
            $manager->persist($task);

            if ($i % 100 === 0) {
                $index++;
            }
        }

        $manager->flush();
    }

    /**
     * @return string[]
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}
