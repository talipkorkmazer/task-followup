<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserFixtures
 *
 * @package App\DataFixtures
 */
class UserFixtures extends Fixture
{

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * UserFixtures constructor.
     */
    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setName('Talip Can Korkmazer');
        $user->setEmail('talipcank@hotmail.com');
        $user->setPassword($this->passwordEncoder->encodePassword($user, 'password'));
        $manager->persist($user);

        $this->addReference("user.1", $user);

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->setName($this->faker->firstName . ' ' . $this->faker->lastName);
            $user->setEmail($this->faker->email);
            $user->setPassword($this->passwordEncoder->encodePassword($user, 'password'));
            $manager->persist($user);

            $index = $i + 1;
            $this->addReference("user.$index", $user);
        }

        $manager->flush();
    }
}
