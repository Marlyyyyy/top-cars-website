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
        /* @var $test_user User */
        $user_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $test_user = $user_repository->findOneById(1);

        // Get test suggested car
        /* @var $suggested_car SuggestedCar */
        $suggested_car_repository = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar');
        $suggested_car = $suggested_car_repository->findAll()[0];

        // Let the test user upvote the suggested car only if she hasn't upvoted it yet
        /* @var $upvoted_cars ArrayCollection */
        $upvoted_cars = $test_user->getVotedSuggestedCars();
        if (!$upvoted_cars->contains($suggested_car)){
            $test_user->addVotedSuggestedCars($suggested_car);
            $this->em->flush();
        }

        $object_array = $suggested_car_repository->selectIdOfSuggestedCarsVotedByUserId($test_user->getId());
        $id_array = array();

        foreach($object_array as $object){
            array_push($id_array, $object['id']);
        }

        $this->assertContains($suggested_car->getId(), $id_array);

        $test_user->removeVotedSuggestedCars($suggested_car);
        $this->em->flush();
    }

    public function testSelectAllSuggestedCars(){

        // Get test user
        /* @var $test_user User */
        $user_repository = $this->em->getRepository('MartonTopCarsBundle:User');
        $test_user = $user_repository->findOneById(2);

        // Create new suggested car
        /* @var $suggested_car SuggestedCar */
        $suggested_car = new SuggestedCar();
        $suggested_car->setUser($test_user);
        $suggested_car->setModel("TEST");

        $this->em->persist($suggested_car);
        $this->em->flush();

        $suggested_car_repository = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar');
        $object_array = $suggested_car_repository->selectAllSuggestedCars();

        $user_id_array = array();

        foreach($object_array as $object){
            array_push($user_id_array, $object['userId']);
        }

        $this->assertContains($test_user->getId(), $user_id_array);

        $this->em->remove($suggested_car);
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