<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 29/10/14
 * Time: 20:37
 */

namespace Marton\TopCarsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Marton\TopCarsBundle\Repository\User")
 * @ORM\Table(name="tbl_user_progress")
 */

class UserProgress {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $score = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $gold = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $level = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $streak = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $allRound = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $roundWin = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $roundLose = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $skill = 0;

    /**
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    public function getId()
    {
        return $this->id;
    }

    public function setScore($score)
    {
        $this->score = $score;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setGold($gold)
    {
        $this->gold = $gold;
    }

    public function getGold()
    {
        return $this->gold;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setAllRound($allRound)
    {
        $this->allRound = $allRound;
    }

    public function getAllRound()
    {
        return $this->allRound;
    }

    public function setRoundWin($roundWin)
    {
        $this->roundWin = $roundWin;
    }

    public function getRoundWin()
    {
        return $this->roundWin;
    }

    public function setRoundLose($roundLose)
    {
        $this->roundLose = $roundLose;
    }

    public function getRoundLose()
    {
        return $this->roundLose;
    }

    public function setStreak($streak)
    {
        $this->streak = $streak;
    }

    public function getStreak()
    {
        return $this->streak;
    }

    public function setSkill($skill)
    {
        $this->skill = $skill;
    }

    public function getSkill()
    {
        return $this->skill;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
} 