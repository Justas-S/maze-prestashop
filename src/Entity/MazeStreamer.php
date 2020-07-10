<?php

namespace Maze\MazeTv\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Maze\MazeTv\Repository\MazeStreamerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MazeStreamer
{
    /** 
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id_manufacturer", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="mazetv_key", type="string", length=255)
     */
    private $key;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_add", type="datetime")
     */
    private $dateAdd;

    public function __construct()
    {
    }

    /**
     * @return int
     */
    public function getManufacturerId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * 
     * @return self
     */
    public function setManufacturerId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string $key
     * 
     * @return self
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param DateTime $date
     * 
     * @return self
     */
    public function setDateAdd($date)
    {
        $this->dateAdd = $date;
        return $this;
    }
}
