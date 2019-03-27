<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

// Un type de générateur peut pointer sur plusieurs localisations

    /**
     * Type Générateur
     *
     * @ORM\Table(name="t_typeGenerateur")
     * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\TypeGenerateurRepository")
     * @ORM\HasLifecycleCallbacks
    */
class TypeGenerateur
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
     * @ORM\Column(name="mode", type="string", length=5, nullable=false, unique=true)
    */
    protected $mode;

    /** 
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
    */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Localisation", mappedBy="typeGenerateur", cascade={"persist"})
    */
    protected $localisations;

    /**
     * @ORM\ManyToMany(targetEntity="Ipc\ProgBundle\Entity\ModuleEnteteLive", cascade={"persist"})
     * @ORM\OrderBy({"description" = "ASC"})
    */
    protected $modulesEnteteLive;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->localisations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->modulesEnteteLive = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set mode
     *
     * @param string $mode
     * @return TypeGenerateur
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode
     *
     * @return string 
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return TypeGenerateur
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
     * Add localisations
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisations
     * @return TypeGenerateur
     */
    public function addLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisations)
    {
        $this->localisations[] = $localisations;

        return $this;
    }

    /**
     * Remove localisations
     *
     * @param \Ipc\ProgBundle\Entity\Localisation $localisations
     */
    public function removeLocalisation(\Ipc\ProgBundle\Entity\Localisation $localisations)
    {
        $this->localisations->removeElement($localisations);
    }

    /**
     * Get localisations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getLocalisations()
    {
        return $this->localisations;
    }

    /**
     * Add modulesEnteteLive
     *
     * @param \Ipc\ProgBundle\Entity\ModuleEnteteLive $modulesEnteteLive
     * @return TypeGenerateur
     */
    public function addModulesEnteteLive(\Ipc\ProgBundle\Entity\ModuleEnteteLive $modulesEnteteLive)
    {
        $this->modulesEnteteLive[] = $modulesEnteteLive;

        return $this;
    }

    /**
     * Remove modulesEnteteLive
     *
     * @param \Ipc\ProgBundle\Entity\ModuleEnteteLive $modulesEnteteLive
     */
    public function removeModulesEnteteLive(\Ipc\ProgBundle\Entity\ModuleEnteteLive $modulesEnteteLive)
    {
        $this->modulesEnteteLive->removeElement($modulesEnteteLive);
    }

    /**
     * Get modulesEnteteLive
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getModulesEnteteLive()
    {
        return $this->modulesEnteteLive;
    }
}
