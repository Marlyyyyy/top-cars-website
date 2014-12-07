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
        /* @var $test_user User */
        $user_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $test_user = $user_repository->findOneById(1);

        $user_progress_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $highscores = $user_progress_repository->findHighscores();

        $user_id_array = array();
        foreach($highscores as $highscore){
            array_push($user_id_array, $highscore->getId());
        }

        $this->assertContains($test_user->getId(), $user_id_array);
    }

    public function testFindDetailsOfUser(){

        // Get test user
        /* @var $test_user User */
        $user_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $test_user = $user_repository->findOneById(1);

        $user_progress_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $user_details = $user_progress_repository->findDetailsOfUser($test_user->getUsername());

        $this->assertEquals($test_user->getUsername(), $user_details->getUsername());
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