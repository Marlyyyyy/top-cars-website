<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 18/11/14
 * Time: 00:19
 */

namespace Marton\TopCarsBundle\Tests\Entity;


use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserDetails;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CarRepositoryFunctionalTest extends KernelTestCase{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Car
     */
    private $car;


    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        self::bootKernel();
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->car = new Car();
        $this->car->setModel("Test");
        $this->car->setImage("test.jpg");
        $this->car->setSpeed(300);
        $this->car->setPower(300);
        $this->car->setTorque(300);
        $this->car->setAcceleration(4.5);
        $this->car->setWeight(1300);
        $this->car->setPrice(400);
        $this->em->persist($this->car);
        $this->em->flush();
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

        /* @var $carRepository CarRepository */
        $carRepository = $this->em->getRepository('MartonTopCarsBundle:Car');

        // Simulate that the user has already purchased every car
        $cars = $carRepository->findAll();

        $notUserCars = $carRepository->findAllNotUserCars($cars);

        $this->assertEquals(0, count($notUserCars));

        // Simulate that the user has purchased only one car
        $car = $carRepository->findOneById('1');

        $notUserCars = $carRepository->findAllNotUserCars(array($car));

        $this->assertEquals(count($cars)-1, count($notUserCars));

        // Simulate that the user hasn't purchased any cars yet
        $notUserCars = $carRepository->findAllNotUserCars(array());

        $this->assertEquals(count($cars), count($notUserCars));
    }

    public function testFindAllNotUserCarsWherePriceLessThan(){

        /* @var $carRepository CarRepository */
        $carRepository = $this->em->getRepository('MartonTopCarsBundle:Car');

        // Simulate that the user has already purchased every car
        $cars = $carRepository->findAll();

        $notUserCars = $carRepository->findAllNotUserCarsWherePriceLessThan(5000, $cars);

        $this->assertEquals(0, count($notUserCars));

        // Simulate that the user has purchased only one car
        $car = $carRepository->findOneById('1');

        $notUserCars = $carRepository->findAllNotUserCarsWherePriceLessThan(5000, array($car));

        $this->assertGreaterThan(0, count($notUserCars));

        // Simulate that the user hasn't purchased any cars yet
        $notUserCars = $carRepository->findAllNotUserCarsWherePriceLessThan(5000, array());

        $this->assertEquals(count($cars), count($notUserCars));
    }

    public function testFindSelectedCarsOfUser(){

        /* @var $carRepository CarRepository */
        $carRepository = $this->em->getRepository('MartonTopCarsBundle:Car');

        $user = new User();
        $user->setUsername("TestUser");
        $user->setEmail("test@test.hu");
        $user->setPassword("testpw");
        $progress = new UserProgress();
        $user->setProgress($progress);
        $details = new UserDetails();
        $user->setDetails($details);

        $role = $this->em->getRepository('MartonTopCarsBundle:Role')->findOneBy(array('role' => 'ROLE_USER'));
        $user->addRole($role);

        $car = $carRepository->findOneById('1');
        $user->addCar($car);
        $user->addSelectedCars($car);

        $this->em->persist($user);
        $this->em->flush();

        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findOneBy(array('username' => 'TestUser'));
        $userId = $user->getId();
        $selectedCars = $carRepository->findSelectedCarsOfUser($userId);

        $this->assertEquals(1, $selectedCars[0]["id"]);

        $this->em->remove($user);
    }

    public function testCountAllCars(){

        /* @var $carRepository CarRepository */
        $carRepository = $this->em->getRepository('MartonTopCarsBundle:Car');

        $countCars = $carRepository->countAllCars();

        $this->assertGreaterThan(0, $countCars);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->em->remove($this->car);
        $this->em->flush();
        $this->em->close();
    }
} 