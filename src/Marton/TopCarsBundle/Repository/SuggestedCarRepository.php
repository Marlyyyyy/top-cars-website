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

    public function selectIdOfSuggestedCarsVotedByUserId($id){

        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();

        $qb ->select('sc.id')
            ->from('MartonTopCarsBundle:SuggestedCar', 'sc')
            ->innerJoin('sc.upVotedUsers', 'u')
            ->where('u.id = :user_id')
            ->setParameter('user_id', $id);

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }

    public function selectAllSuggestedCars(){

        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();

        $user_table = $em->getClassMetadata('MartonTopCarsBundle:User')->getTableName();
        $suggestedCar_table = $em->getClassMetadata('MartonTopCarsBundle:SuggestedCar')->getTableName();

        $sql =
            'SELECT
              sc.id AS id,
              sc.model AS model,
              sc.image AS image,
              sc.speed AS speed,
              sc.power AS power,
              sc.torque AS torque,
              sc.acceleration AS acceleration,
              sc.weight AS weight,
              sc.comment AS comment,
              sc.createdAt AS createdAt,
              u.id AS userId,
              u.username AS username,
              CASE WHEN sc_count.upvotes IS NULL THEN 0 ELSE sc_count.upvotes END AS upvotes
            FROM
              '.$suggestedCar_table.' sc
              INNER JOIN '.$user_table.' u ON sc.user_id = u.id
              LEFT JOIN (  SELECT votes.suggestedCar_id, COUNT(votes.user_id) AS upvotes
                                                    FROM upvotedUser_suggestedCar votes
                                                    GROUP BY votes.suggestedCar_id
                                                    ) AS sc_count ON sc.id = sc_count.suggestedCar_id
            ORDER BY upvotes DESC';

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();

        return $result;
    }

} 