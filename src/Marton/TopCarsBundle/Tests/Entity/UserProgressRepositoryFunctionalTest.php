<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 13:36
 */

namespace Marton\TopCarsBundle\Tests\Entity;


use Marton\TopCarsBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserProgressRepositoryFunctionalTest extends KernelTestCase{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager()
        ;
    }

    // Testing queries
    public function testFindHighscores(){

        // Get test user
        /* @var $test_user User */
        $user_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $test_user = $user_repository->findOneById(1);

        $user_progress_repository = $this->em->getRepository('MartonTopCarsBundle:UserProgress');
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

        $user_progress_repository = $this->em->getRepository('MartonTopCarsBundle:UserProgress');
        $user_details = $user_progress_repository->findDetailsOfUser($test_user->getUsername());

        $this->assertEquals($test_user->getUsername(), $user_details[0]->getUsername());
    }
    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->close();
    }

} 