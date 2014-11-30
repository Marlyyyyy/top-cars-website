<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 30/10/14
 * Time: 23:59
 */

namespace Marton\TopCarsBundle\Repository;

use Doctrine\ORM\EntityRepository;

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

        foreach ($cars as $car){
            $qb->andWhere('NOT c.id ='.$car->getId());
        }

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function findSelectedCarsOfUser($id){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $car_table = $em->getClassMetadata('MartonTopCarsBundle:Car')->getTableName();
        $join_table = "user_selectedCar";

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
              '.$car_table.' c
              INNER JOIN '.$join_table.' j ON c.id = j.car_id
              WHERE j.user_id = '.$id;

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }
} 