<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 18/11/14
 * Time: 00:19
 */

namespace Marton\TopCarsBundle\Tests\Entity;


use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CarRepositoryFunctionalTest extends KernelTestCase{

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

    // Testing Queries
    public function testFindAllCars(){

        $cars = $this->em
            ->getRepository('MartonTopCarsBundle:Car')
            ->findAllCars();

        $this->assertGreaterThan(100, count($cars));
    }

    public function testFindAllCarsAsArray(){

        $cars = $this->em
            ->getRepository('MartonTopCarsBundle:Car')
            ->findAllCarsAsArray();

        $this->assertGreaterThan(100, count($cars));
    }

    public function testFindAllNotUserCars(){

        $car_repository = $this->em->getRepository('MartonTopCarsBundle:Car');

        // Simulate that the user has already purchased every car
        $cars = $car_repository->findAll();

        $not_user_cars = $car_repository->findAllNotUserCars($cars);

        $this->assertEquals(0, count($not_user_cars));

        // Simulate that the user has purchased only one car
        $car = $car_repository->findOneById('5');

        $not_user_cars = $car_repository->findAllNotUserCars(array($car));

        $this->assertEquals(count($cars)-1, count($not_user_cars));

        // Simulate that the user hasn't purchased any cars yet
        $not_user_cars = $car_repository->findAllNotUserCars(array());

        $this->assertEquals(count($cars), count($not_user_cars));
    }

    public function testFindAllNotUserCarsWherePriceLessThan(){

        $car_repository = $this->em->getRepository('MartonTopCarsBundle:Car');

        // Simulate that the user has already purchased every car
        $cars = $car_repository->findAll();

        $not_user_cars = $car_repository->findAllNotUserCarsWherePriceLessThan(5000, $cars);

        $this->assertEquals(0, count($not_user_cars));

        // Simulate that the user has purchased only one car
        $car = $car_repository->findOneById('5');

        $not_user_cars = $car_repository->findAllNotUserCarsWherePriceLessThan(5000, array($car));

        $this->assertGreaterThan(0, count($not_user_cars));

        // Simulate that the user hasn't purchased any cars yet
        $not_user_cars = $car_repository->findAllNotUserCarsWherePriceLessThan(5000, array());

        $this->assertEquals(count($cars), count($not_user_cars));
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