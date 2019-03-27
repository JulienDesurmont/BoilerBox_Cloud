<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;



/**
 * TypeFamilleLive
 *
 * @ORM\Table(name="t_typefamillelive")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\TypeFamilleLiveRepository")
 * @UniqueEntity("designation")
*/
class TypeFamilleLive
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
     * @ORM\Column(name="informations", type="string", length=255)
    */
    protected $informations;


    /**
     * @var integer
     *
     * @ORM\Column(name="disposition", type="integer")
    */
    protected $disposition;


    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\DonneeLive", mappedBy="typeFamille", cascade={"remove"})
    */
    protected $donneesLive;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\ModuleEnteteLive", mappedBy="typeFamilleLive", cascade={"remove"})
    */
    protected $moduleEnteteLive;


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
     * @return TypeFamilleLive
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
     * Set informations
     *
     * @param string $informations
     * @return TypeFamilleLive
     */
    public function setInformations($informations)
    {
        $this->informations = $informations;

        return $this;
    }

    /**
     * Get informations
     *
     * @return string 
     */
    public function getInformations()
    {
        return $this->informations;
    }

    /**
     * Add donneesLive
     *
     * @param \Ipc\ProgBundle\Entity\DonneeLive $donneesLive
     * @return TypeFamilleLive
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

    /**
     * Add moduleEnteteLive
     *
     * @param \Ipc\ProgBundle\Entity\ModuleEnteteLive $moduleEnteteLive
     * @return TypeFamilleLive
     */
    public function addModuleEnteteLive(\Ipc\ProgBundle\Entity\ModuleEnteteLive $moduleEnteteLive)
    {
        $this->moduleEnteteLive[] = $moduleEnteteLive;

        return $this;
    }

    /**
     * Remove moduleEnteteLive
     *
     * @param \Ipc\ProgBundle\Entity\ModuleEnteteLive $moduleEnteteLive
     */
    public function removeModuleEnteteLive(\Ipc\ProgBundle\Entity\ModuleEnteteLive $moduleEnteteLive)
    {
        $this->moduleEnteteLive->removeElement($moduleEnteteLive);
    }

    /**
     * Get moduleEnteteLive
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getModuleEnteteLive()
    {
        return $this->moduleEnteteLive;
    }

    /**
     * Set disposition
     *
     * @param integer $disposition
     * @return TypeFamilleLive
     */
    public function setDisposition($disposition)
    {
        $this->disposition = $disposition;

        return $this;
    }

    /**
     * Get disposition
     *
     * @return integer
     */
    public function getDisposition()
    {
        return $this->disposition;
    }
}
