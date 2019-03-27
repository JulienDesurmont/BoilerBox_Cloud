<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

// Un mode d'exploitation peut pointer sur 	plusieurs localisation

    /**
     * Mode Exploitation
     *
     * @ORM\Table(name="t_moduleEnteteLive")
     * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\ModuleEnteteLiveRepository")
    */
class ModuleEnteteLive
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
     * @ORM\Column(name="designation", type="string", length=255, nullable=false, unique=true)
    */
    protected $designation;

    /** 
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, unique=true)
    */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Ipc\ProgBundle\Entity\TypeFamilleLive", inversedBy="moduleEnteteLive", cascade={"persist"})
     * @ORM\JoinColumn(name="typeFamilleLive_id", referencedColumnName="id", nullable=true)
    */
    protected $typeFamilleLive;


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
     * @return ModuleEnteteLive
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
     * Set description
     *
     * @param string $description
     * @return ModuleEnteteLive
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set typeFamilleLive
     *
     * @param \Ipc\ProgBundle\Entity\TypeFamilleLive $typeFamilleLive
     * @return ModuleEnteteLive
     */
    public function setTypeFamilleLive(\Ipc\ProgBundle\Entity\TypeFamilleLive $typeFamilleLive = null)
    {
        $this->typeFamilleLive = $typeFamilleLive;

        return $this;
    }

    /**
     * Get typeFamilleLive
     *
     * @return \Ipc\ProgBundle\Entity\TypeFamilleLive
     */
    public function getTypeFamilleLive()
    {
        return $this->typeFamilleLive;
    }
}
