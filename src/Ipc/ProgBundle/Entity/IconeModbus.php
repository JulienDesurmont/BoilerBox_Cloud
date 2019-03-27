<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * IconeModbus
 */
class IconeModbus
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $designation;

    /**
     * @var url
     */
    private $url;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $donneesLive;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->donneesLive = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set designation
     *
     * @param string $designation
     * @return IconeModbus
     */
    public function setDesignation($designation)
    {
        $this->designation = $designation;

        return $this;
    }

    /**
     * Get designation
     *
     * @return string 
     */
    public function getDesignation()
    {
        return $this->designation;
    }

    /**
     * Set url
     *
     * @param \url $url
     * @return IconeModbus
     */
    public function setUrl(\url $url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return \url 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Add donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     * @return IconeModbus
     */
    public function addDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive[] = $donneesLive;

        return $this;
    }

    /**
     * Remove donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     */
    public function removeDonneesLive(\Ipc\ProgBundle\Entity\DonneeLive $donneesLive)
    {
        $this->donneesLive->removeElement($donneesLive);
    }

    /**
     * Get donneesLive
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDonneesLive()
    {
        return $this->donneesLive;
    }
}
