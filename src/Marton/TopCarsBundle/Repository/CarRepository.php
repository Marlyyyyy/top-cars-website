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

    public function updatePriceWhereId($id, $price){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->update('MartonTopCarsBundle:Car', 'c')
            ->set('c.price', ':price')
            ->where('c.id = :id')
            ->setParameter('price', $price)
            ->setParameter('id', $id);

        $query = $qb->getQuery();
        $query->execute();
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
} 