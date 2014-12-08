<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 18/11/14
 * Time: 21:20
 */

namespace Marton\TopCarsBundle\Tests\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Repository\SuggestedCarRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuggestedCarRepositoryFunctionalTest extends KernelTestCase{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var SuggestedCar
     */
    private $suggestedCar;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->suggestedCar = new SuggestedCar();
        $this->suggestedCar->setModel("Test");
        $this->em->persist($this->suggestedCar);
        $this->em->flush();
    }

    // Testing queries
    public function testSelectIdOfSuggestedCarsVotedByUserId(){

        // Get test user
        /* @var $testUser User */
        $userRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        $testUser = $userRepository->findOneById(1);

        // Get test suggested car
        /* @var $suggestedCarRepository SuggestedCarRepository */
        $suggestedCarRepository = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar');
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = $suggestedCarRepository->findAll()[0];

        // Let the test user upvote the suggested car only if she hasn't upvoted it yet
        /* @var $upvotedCars ArrayCollection */
        $upvotedCars = $testUser->getVotedSuggestedCars();
        if (!$upvotedCars->contains($suggestedCar)){
            $testUser->addVotedSuggestedCars($suggestedCar);
            $this->em->flush();
        }

        $objectArray = $suggestedCarRepository->selectIdOfSuggestedCarsVotedByUserId($testUser->getId());
        $idArray = array();

        foreach($objectArray as $object){
            array_push($idArray, $object['id']);
        }

        $this->assertContains($suggestedCar->getId(), $idArray);

        $testUser->removeVotedSuggestedCars($suggestedCar);
        $this->em->flush();
    }

    public function testSelectAllSuggestedCars(){

        // Get test user
        /* @var $testUser User */
        $userRepository = $this->em->getRepository('MartonTopCarsBundle:User');
        $testUser = $userRepository->findOneById(2);

        // Create new suggested car
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = new SuggestedCar();
        $suggestedCar->setUser($testUser);
        $suggestedCar->setModel("TEST");

        $this->em->persist($suggestedCar);
        $this->em->flush();

        /* @var $suggestedCarRepository SuggestedCarRepository */
        $suggestedCarRepository = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar');
        $objectArray = $suggestedCarRepository->selectAllSuggestedCars();

        $user_id_array = array();

        foreach($objectArray as $object){
            array_push($user_id_array, $object['userId']);
        }

        $this->assertContains($testUser->getId(), $user_id_array);

        $this->em->remove($suggestedCar);
        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->remove($this->suggestedCar);
        $this->em->flush();
        $this->em->close();
    }

} 