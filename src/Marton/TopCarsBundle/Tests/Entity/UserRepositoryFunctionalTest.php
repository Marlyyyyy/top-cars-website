<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 13:36
 */

namespace Marton\TopCarsBundle\Tests\Entity;


use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserDetails;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserProgressRepositoryFunctionalTest extends KernelTestCase{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var User
     */
    private $user;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->user = new User();
        $this->user->setUsername("Test");
        $this->user->setEmail("test@test.hu");
        $this->user->setPassword("testpw");
        $this->user->setDetails(new UserDetails());
        $this->user->setProgress(new UserProgress());
        $this->em->persist($this->user);
        $this->em->flush();
    }

    // Testing queries
    public function testFindHighscores(){

        // Get test user
        /* @var $userRepository UserRepository */
        $userRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        /* @var $testUser User */
        $testUser = $userRepository->findOneById(1);

        $userProgressRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        $highscores = $userProgressRepository->findHighscores();

        $userIdArray = array();
        foreach($highscores as $highscore){
            array_push($userIdArray, $highscore->getId());
        }

        $this->assertContains($testUser->getId(), $userIdArray);
    }

    public function testFindDetailsOfUser(){

        // Get test user
        /* @var $userRepository UserRepository */
        $userRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        /* @var $testUser User */
        $testUser = $userRepository->findOneById(1);

        $userProgressRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        $user_details = $userProgressRepository->findDetailsOfUser($testUser->getUsername());

        $this->assertEquals($testUser->getUsername(), $user_details->getUsername());
    }
    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->remove($this->user);
        $this->em->flush();
        $this->em->close();
    }

} 