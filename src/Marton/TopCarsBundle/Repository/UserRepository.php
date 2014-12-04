<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 18/11/14
 * Time: 21:50
 */

namespace Marton\TopCarsBundle\Repository;


use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository{

    public function findHighscores($sort = 'score', $username = ""){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('uprog, partial u.{id, username}')
            ->from('MartonTopCarsBundle:User', 'u')
            ->innerJoin('u.progress', 'uprog');

        if ($username !== ""){
            $qb ->where('u.username LIKE :username')
                ->setParameter('username', '%'.$username.'%');
        }

        $qb ->addOrderBy('uprog.'.$sort, 'DESC')
            ->addOrderBy('uprog.score', 'DESC')
            ->setMaxResults( 100 );

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function findDetailsOfUser($username){

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb ->select('udet, uprog, partial u.{id, username}')
            ->from('MartonTopCarsBundle:User', 'u')
            ->where('u.username = :username')
            ->innerJoin('u.progress', 'uprog')
            ->innerJoin('u.details', 'udet')
            ->setParameter('username', $username);

        $query = $qb->getQuery();
        try{
            $result = $query->getSingleResult();
        }catch (\Doctrine\ORM\NoResultException $e){
            $result = array();
        }

        return $result;
    }
} 