<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 31/10/14
 * Time: 23:33
 */

namespace Marton\TopCarsBundle\Repository;


use Doctrine\ORM\EntityRepository;

class UserProgressRepository extends EntityRepository{

    public function findHighscores(){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('uprog, partial u.{id, username}')
            ->from('MartonTopCarsBundle:User', 'u')
            ->innerJoin('u.progress', 'uprog');

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

} 