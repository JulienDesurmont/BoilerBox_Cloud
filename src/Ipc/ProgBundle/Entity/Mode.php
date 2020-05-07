<?php

namespace Ipc\ProgBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Mode
 *
 * @ORM\Table(name="t_mode")
 * @ORM\Entity(repositoryClass="Ipc\ProgBundle\Entity\ModeRepository")
*/

class Mode
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id",type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @var string
     * 
     * @ORM\Column(name="designation", type="string", length=10)
    */
    protected $designation;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
    */
    protected $description;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Localisation", mappedBy="mode")
    */
    protected $localisations;
  
    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\Module", mappedBy="mode")
    */
    protected $modules;

    /**
     * @ORM\OneToMany(targetEntity="Ipc\ProgBundle\Entity\InfosLocalisation", mappedBy="mode")
    */
    protected $infosLocalisation;

    /**
     * @ORM\OneToOne(targetEntity="Ipc\ProgBundle\Entity\FichierIpc", cascade={"persist"})
     * @ORM\JoinColumn(name="fichierIpc_id", referencedColumnName="id")
    */
    protected $fichierIpc;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->localisations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->modules = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Mode
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
     * @return Mode
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
     * @return Mode
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
     * Add modules
     *
     * @param \Ipc\ProgBundle\Entity\Module $modules
     * @return Mode
     */
    public function addModule(\Ipc\ProgBundle\Entity\Module $modules)
    {
        $this->modules[] = $modules;

        return $this;
    }

    /**
     * Remove modules
     *
     * @param \Ipc\ProgBundle\Entity\Module $modules
     */
    public function removeModule(\Ipc\ProgBundle\Entity\Module $modules)
    {
        $this->modules->removeElement($modules);
    }

    /**
     * Get modules
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getModules()
    {
        return $this->modules;
    }

    /**
     * Add infosLocalisation
     *
     * @param \Ipc\ProgBundle\Entity\InfosLocalisation $infosLocalisation
     * @return Mode
     */
    public function addInfosLocalisation(\Ipc\ProgBundle\Entity\InfosLocalisation $infosLocalisation)
    {
        $this->infosLocalisation[] = $infosLocalisation;

        return $this;
    }

    /**
     * Remove infosLocalisation
     *
     * @param \Ipc\ProgBundle\Entity\InfosLocalisation $infosLocalisation
     */
    public function removeInfosLocalisation(\Ipc\ProgBundle\Entity\InfosLocalisation $infosLocalisation)
    {
        $this->infosLocalisation->removeElement($infosLocalisation);
    }

    /**
     * Get infosLocalisation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInfosLocalisation()
    {
        return $this->infosLocalisation;
    }

    /**
     * Set fichierIpc
     *
     * @param \Ipc\ProgBundle\Entity\FichierIpc $fichierIpc
     * @return Mode
     */
    public function setFichierIpc(\Ipc\ProgBundle\Entity\FichierIpc $fichierIpc = null)
    {
        $this->fichierIpc = $fichierIpc;

        return $this;
    }

    /**
     * Get fichierIpc
     *
     * @return \Ipc\ProgBundle\Entity\FichierIpc 
     */
    public function getFichierIpc()
    {
        return $this->fichierIpc;
    }

}
