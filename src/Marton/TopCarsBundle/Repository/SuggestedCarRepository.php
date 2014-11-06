<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 05/11/14
 * Time: 19:08
 */

namespace Marton\TopCarsBundle\Repository;


use Doctrine\ORM\EntityRepository;

class SuggestedCarRepository extends EntityRepository{

    public function selectAllSuggestedCars(){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('s')
            ->from('MartonTopCarsBundle:SuggestedCar', 's');

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

} 