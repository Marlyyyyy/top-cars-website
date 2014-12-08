<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 30/10/14
 * Time: 23:59
 */

namespace Marton\TopCarsBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Marton\TopCarsBundle\Entity\Car;

class CarRepository extends EntityRepository {

    public function findAllCars(){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('c')
            ->from('MartonTopCarsBundle:Car', 'c')
            ->orderBy('c.model');

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function findAllCarsAsArray(){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('c')
            ->from('MartonTopCarsBundle:Car', 'c');

        $query = $qb->getQuery();
        $result = $query->getArrayResult();

        return $result;
    }

    public function findAllNotUserCars($cars){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('c')
            ->from('MartonTopCarsBundle:Car', 'c')
            ->orderBy('c.model');

        /* @var $car Car */
        foreach ($cars as $car){
            $qb->andWhere('NOT c.id ='.$car->getId());
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function findAllNotUserCarsWherePriceLessThan($price, $cars){
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('c')
            ->from('MartonTopCarsBundle:Car', 'c')
            ->where('c.price <= :price')
            ->setParameter('price', $price)
            ->orderBy('c.price');

        /* @var $car Car */
        foreach ($cars as $car){
            $qb->andWhere('NOT c.id ='.$car->getId());
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function findSelectedCarsOfUser($id){

        $em = $this->getEntityManager();

        $carTable = $em->getClassMetadata('MartonTopCarsBundle:Car')->getTableName();
        $joinTable = "user_selectedCar";

        $sql =
            'SELECT
              c.id AS id,
              c.model AS model,
              c.image AS image,
              c.speed AS speed,
              c.power AS power,
              c.torque AS torque,
              c.acceleration AS acceleration,
              c.weight AS weight
            FROM
              '.$carTable.' c
              INNER JOIN '.$joinTable.' j ON c.id = j.car_id
              WHERE j.user_id = '.$id;

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }

    public function countAllCars(){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $query = $qb ->select('COUNT(c.id)')
                     ->from('MartonTopCarsBundle:Car', 'c')
                     ->getQuery();

        return $query->getSingleScalarResult();
    }
} 