<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Icone
 *
 * @ORM\Table(name="t_icone")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\IconeRepository")
 * @UniqueEntity("designation")
*/
class Icone
{
    /** 
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="designation", type="string", length=100, nullable=false, unique=true) 
    */
    protected $designation;

    /**
     * @var string
     *
     * @ORM\Column(name="alt", type="string", length=10, nullable=false)
    */
    protected $alt;



    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255)
     *
     * @Assert\Url()
    */
    protected $url;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\DonneeLive", mappedBy="icone", cascade={"remove"})
    */
    protected $donneesLive;


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
     * @return Icone
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
     * Set alt
     *
     * @param string $alt
     * @return Icone
     */
    public function setAlt($alt)
    {
        $this->alt = $alt;

        return $this;
    }

    /**
     * Get alt
     *
     * @return string
     */
    public function getAlt()
    {
        return $this->alt;
    }


    /**
     * Set url
     *
     * @param string $url
     * @return Icone
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Add donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     * @return Icone
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


	public function getIconeForSelect()
	{
		return $this->url.'__'.$this->designation;
	}

}
